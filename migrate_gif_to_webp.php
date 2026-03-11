<?php
/**
 * GIF封面迁移到WebP格式脚本
 * 将已有视频场景模板的GIF动态封面重新生成为Animated WebP格式
 * 
 * 使用方式：
 *   php migrate_gif_to_webp.php              # 默认每批10条
 *   php migrate_gif_to_webp.php --limit=20   # 每批20条
 *   php migrate_gif_to_webp.php --all        # 循环处理所有记录
 * 
 * 注意事项：
 *   - 确保FFmpeg已安装且支持libwebp_anim编码器
 *   - 建议在低峰期执行，避免FFmpeg并发过高
 *   - 迁移失败的记录会保留原GIF封面，不影响前端显示
 */

// 初始化ThinkPHP框架
require __DIR__ . '/vendor/autoload.php';

// 定义根路径
define('ROOT_PATH', __DIR__ . '/');

$app = new think\App(ROOT_PATH);
$app->initialize();

use think\facade\Db;
use think\facade\Log;

echo "=== GIF封面迁移到WebP格式工具 ===\n\n";

// 解析命令行参数
$options = getopt('', ['limit:', 'all', 'dry-run']);
$limit = isset($options['limit']) ? intval($options['limit']) : 10;
$processAll = isset($options['all']);
$dryRun = isset($options['dry-run']);

if ($limit < 1) $limit = 10;
if ($limit > 50) $limit = 50; // 限制单批最大50条

// 1. 环境检测
echo "[1/3] 环境检测...\n";

// 检查FFmpeg
$ffmpegPath = null;
$ffmpegPaths = ['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/opt/bin/ffmpeg'];
foreach ($ffmpegPaths as $path) {
    if (file_exists($path) && is_executable($path)) {
        $ffmpegPath = $path;
        break;
    }
}
if (!$ffmpegPath) {
    exec('which ffmpeg 2>/dev/null', $whichOutput, $whichRet);
    if ($whichRet === 0 && !empty($whichOutput[0])) {
        $ffmpegPath = trim($whichOutput[0]);
    }
}

if (!$ffmpegPath) {
    echo "  ✗ FFmpeg未安装，无法执行迁移\n";
    exit(1);
}
echo "  ✓ FFmpeg: {$ffmpegPath}\n";

// 检查libwebp_anim
$encoderOutput = [];
exec("{$ffmpegPath} -encoders 2>/dev/null | grep libwebp_anim", $encoderOutput);
if (empty($encoderOutput)) {
    echo "  ✗ FFmpeg不支持libwebp_anim编码器，无法生成WebP\n";
    exit(1);
}
echo "  ✓ libwebp_anim编码器可用\n";

// 检查PHP imagewebp
echo "  " . (function_exists('imagewebp') ? '✓' : '✗') . " PHP GD imagewebp: " . (function_exists('imagewebp') ? '可用' : '不可用') . "\n";

// 2. 统计待迁移数据
echo "\n[2/3] 数据统计...\n";
$totalGif = Db::name('generation_scene_template')
    ->where('generation_type', 2)
    ->where('status', 1)
    ->where('gif_cover', 'like', '%.gif')
    ->where('cover_image', '<>', '')
    ->count();

$totalWebp = Db::name('generation_scene_template')
    ->where('generation_type', 2)
    ->where('status', 1)
    ->where('gif_cover', 'like', '%.webp')
    ->count();

$totalEmpty = Db::name('generation_scene_template')
    ->where('generation_type', 2)
    ->where('status', 1)
    ->where('gif_cover', '')
    ->count();

echo "  待迁移(GIF): {$totalGif} 条\n";
echo "  已为WebP:    {$totalWebp} 条\n";
echo "  无动态封面:  {$totalEmpty} 条\n";

if ($totalGif === 0) {
    echo "\n无待迁移记录，退出。\n";
    exit(0);
}

if ($dryRun) {
    echo "\n[dry-run模式] 仅展示统计，不执行迁移。\n";
    exit(0);
}

// 3. 执行迁移
echo "\n[3/3] 开始迁移（每批{$limit}条）...\n";

$generationService = new \app\service\GenerationService();

$totalProcessed = 0;
$totalSuccess = 0;
$totalFailed = 0;
$batchNum = 0;

do {
    $batchNum++;
    echo "\n--- 第{$batchNum}批 ---\n";
    
    $result = $generationService->batchMigrateToWebpCovers($limit);
    
    $totalProcessed += $result['processed'];
    $totalSuccess += $result['success'];
    $totalFailed += $result['failed'];
    
    echo "  处理: {$result['processed']} | 成功: {$result['success']} | 失败: {$result['failed']} | 跳过: {$result['skipped']}\n";
    
    // 如果本批没有处理任何记录，说明已经全部迁移完毕
    if ($result['processed'] === 0) {
        echo "  无更多待处理记录。\n";
        break;
    }
    
    // 每批之间休眠2秒，降低服务器负载
    if ($processAll && $result['processed'] > 0) {
        echo "  等待2秒...\n";
        sleep(2);
    }
    
} while ($processAll);

// 4. 输出汇总
echo "\n=== 迁移完成 ===\n";
echo "总处理: {$totalProcessed}\n";
echo "总成功: {$totalSuccess}\n";
echo "总失败: {$totalFailed}\n";

// 重新统计
$remainGif = Db::name('generation_scene_template')
    ->where('generation_type', 2)
    ->where('status', 1)
    ->where('gif_cover', 'like', '%.gif')
    ->count();
echo "剩余GIF: {$remainGif} 条\n";

if ($remainGif > 0 && !$processAll) {
    echo "\n提示: 还有 {$remainGif} 条未迁移，可使用 --all 参数处理全部记录。\n";
}

echo "\n完成！\n";

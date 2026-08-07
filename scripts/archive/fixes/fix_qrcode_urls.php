<?php
/**
 * 批量修复 H5 选片二维码图片 URL
 * 
 * 问题：generateQrcodeImage() 之前生成的二维码内容只有 ?qrcode=XXX，
 *       微信扫码后无法跳转到完整选片页。
 * 修复：重新生成所有 qrcode_type=1 的二维码图片，内容改为完整的
 *       https://domain/public/pick/index.html?qr=XXX
 * 
 * 使用方式：php fix_qrcode_urls.php
 */

// ThinkPHP 6 框架引导
define('ROOT_PATH', __DIR__ . '/');
require __DIR__ . '/vendor/autoload.php';

// 初始化 App，使 Facade 可用
(new \think\App())->initialize();

use think\facade\Db;

// ============================================================
// 配置
// ============================================================
$qrCodeDir = __DIR__ . '/upload/qrcode_temp/';
$ossPathPrefix = 'ai_travel_photo/qrcode/';
$size = 300;
$margin = 10;

// 加载 phpqrcode 库
require_once __DIR__ . '/extend/phpqrcode/phpqrcode.php';

// ============================================================
// 辅助函数：获取当前域名
// ============================================================
function getBaseUrl(): string {
    $confUrl = config('ai_travel_photo.qrcode.base_url');
    if (!empty($confUrl)) {
        return rtrim($confUrl, '/');
    }
    // 使用配置中的域名
    $domain = config('app.app_host', '');
    if (!empty($domain)) {
        return rtrim($domain, '/');
    }
    // 从项目根目录名推断域名
    $rootName = basename(ROOT_PATH);
    if (strpos($rootName, '.') !== false && preg_match('/^[\w\-\.]+$/', $rootName)) {
        return 'https://' . $rootName;
    }
    die("错误：无法确定域名，请在 config/ai_travel_photo.php 中设置 qrcode.base_url\n");
}

// ============================================================
// 辅助函数：上传文件到 OSS
// ============================================================
function uploadToOss(string $localPath, string $ossPath): string {
    $ossHelper = new \app\common\OssHelper();
    return $ossHelper->uploadFile($localPath, $ossPath);
}

// ============================================================
// 主逻辑
// ============================================================
echo "============================================================\n";
echo "批量修复 H5 选片二维码\n";
echo "============================================================\n\n";

// 1. 查询需要修复的二维码记录
$records = Db::name('ai_travel_photo_qrcode')
    ->where('qrcode_type', 1)   // 选片页二维码
    ->where('status', 1)        // 有效状态
    ->whereNotNull('qrcode_url')
    ->where('qrcode_url', '<>', '')
    ->select()
    ->toArray();

$total = count($records);
echo "找到 {$total} 条需要处理的二维码记录\n\n";

if ($total === 0) {
    echo "没有需要修复的记录，退出。\n";
    exit(0);
}

// 2. 获取基础 URL
$baseUrl = getBaseUrl();
echo "选片页 URL 前缀: {$baseUrl}/public/pick/index.html?qr=\n\n";

// 3. 创建临时目录
if (!is_dir($qrCodeDir)) {
    mkdir($qrCodeDir, 0755, true);
}

// 4. 逐条修复
$success = 0;
$failed = 0;
$skipped = 0;
$errors = [];

foreach ($records as $idx => $record) {
    $num = $idx + 1;
    $qrcode = $record['qrcode'];
    $id = $record['id'];

    // 构建正确的完整 URL
    $pickUrl = $baseUrl . '/public/pick/index.html?qr=' . $qrcode;

    try {
        // 生成二维码图片到临时文件
        $tempFile = $qrCodeDir . $qrcode . '.png';
        \QRcode::png($pickUrl, $tempFile, 'L', intval($size / 30), $margin);

        // 上传到 OSS
        $ossPath = $ossPathPrefix . date('Ymd') . '/' . $qrcode . '.png';
        $newUrl = uploadToOss($tempFile, $ossPath);

        // 更新数据库
        Db::name('ai_travel_photo_qrcode')
            ->where('id', $id)
            ->update([
                'qrcode_url' => $newUrl,
                'update_time' => time(),
            ]);

        // 清理临时文件
        @unlink($tempFile);

        $success++;
        echo "[{$num}/{$total}] OK  id={$id}  qrcode={$qrcode}\n";
        echo "        旧: {$record['qrcode_url']}\n";
        echo "        新: {$newUrl}\n\n";

    } catch (\Throwable $e) {
        $failed++;
        $msg = "[{$num}/{$total}] FAIL  id={$id}  qrcode={$qrcode}  err=" . $e->getMessage() . "\n";
        echo $msg;
        $errors[] = $msg;
        @unlink($tempFile ?? '');
    }
}

// 5. 清理临时目录
@rmdir($qrCodeDir);

// 6. 输出汇总
echo "============================================================\n";
echo "修复完成\n";
echo "============================================================\n";
echo "总计: {$total}\n";
echo "成功: {$success}\n";
echo "失败: {$failed}\n";
echo "跳过: {$skipped}\n";

if (!empty($errors)) {
    echo "\n失败记录:\n";
    foreach ($errors as $e) {
        echo "  " . $e;
    }
}

echo "\n";
echo "提示: 可运行以下 SQL 验证修复结果:\n";
echo "  SELECT id, qrcode, qrcode_url FROM ddwx_ai_travel_photo_qrcode WHERE qrcode_type=1 AND status=1 LIMIT 5;\n";

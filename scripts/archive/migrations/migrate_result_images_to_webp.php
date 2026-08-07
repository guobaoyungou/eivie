<?php
/**
 * 批量迁移历史生图成片数据为 WebP 格式
 *
 * 用法：
 *   php migrate_result_images_to_webp.php --limit=20                    # 处理20条记录
 *   php migrate_result_images_to_webp.php --all                         # 处理所有记录
 *   php migrate_result_images_to_webp.php --type=travel_result          # 仅处理旅拍成片
 *   php migrate_result_images_to_webp.php --type=travel_watermark       # 仅处理旅拍水印图
 *   php migrate_result_images_to_webp.php --type=generation_output      # 仅处理通用照片生成输出
 *   php migrate_result_images_to_webp.php --dry-run                     # 仅预览，不实际执行
 *
 * 说明：
 *   - 该工具会逐条下载成片/水印图片，转换为 WebP 格式后重新上传
 *   - 转换失败的记录会保留原格式，不影响前端显示
 *   - 支持断点续跑（按 ID 升序处理，可多次执行）
 *   - 建议在服务器低峰期执行，避免影响业务
 */

// 初始化 ThinkPHP 框架
define('ROOT_PATH', __DIR__ . '/');
require ROOT_PATH . 'vendor/autoload.php';

// 解析命令行参数
$options = getopt('', ['limit:', 'all', 'type:', 'dry-run', 'batch-size:']);
$limit = isset($options['all']) ? 0 : (intval($options['limit'] ?? 50));
$type = $options['type'] ?? 'all';
$dryRun = isset($options['dry-run']);
$batchSize = intval($options['batch-size'] ?? 10);

echo "========================================\n";
echo " 历史成片 WebP 批量迁移工具\n";
echo "========================================\n";
echo "迁移类型: {$type}\n";
echo "处理数量: " . ($limit > 0 ? $limit : '全部') . "\n";
echo "批次大小: {$batchSize}\n";
echo "模式: " . ($dryRun ? '预览(dry-run)' : '执行') . "\n";
echo "----------------------------------------\n\n";

try {
    $app = new \think\App();
    $app->initialize();

    // 定义 PRE_URL（如果未定义）
    if (!defined('PRE_URL')) {
        $siteUrl = \think\facade\Db::name('sysset')->where('name', 'site_url')->value('value');
        if ($siteUrl) {
            define('PRE_URL', $siteUrl);
        } else {
            define('PRE_URL', 'https://ai.eivie.cn');
        }
    }
    if (!defined('aid')) {
        define('aid', 0);
    }

    $db = \think\facade\Db::class;
    $persistService = new \app\service\ImagePersistService();

    $stats = [
        'total' => 0,
        'processed' => 0,
        'success' => 0,
        'failed' => 0,
        'skipped' => 0,
    ];

    // ============================================================
    // [1] 迁移 ai_travel_photo_result.url
    // ============================================================
    if ($type === 'all' || $type === 'travel_result') {
        echo "[1] 迁移旅拍成片 (ai_travel_photo_result.url)...\n";

        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
        $query = $db::name('ai_travel_photo_result')
            ->where('status', '>', 0)
            ->where('url', '<>', '')
            ->where(function ($q) use ($imageExts) {
                foreach ($imageExts as $ext) {
                    $q->whereOr('url', 'like', '%.' . $ext);
                }
            })
            ->order('id asc');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $records = $query->field('id, aid, url, file_size')->select()->toArray();
        echo "  找到 " . count($records) . " 条待处理记录\n";

        foreach ($records as $record) {
            $stats['total']++;
            $url = $record['url'];
            $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));

            if ($ext === 'webp' || !in_array($ext, $imageExts)) {
                $stats['skipped']++;
                continue;
            }

            if ($dryRun) {
                echo "  [预览] 记录#{$record['id']} url将转为WebP: " . substr($url, -40) . "\n";
                $stats['processed']++;
                continue;
            }

            echo "  [处理] 记录#{$record['id']}...";
            try {
                $aid = intval($record['aid'] ?? 0);
                $newUrl = $persistService->persistAndCompress($url, $aid, 'ai_travel_photo');
                if ($newUrl && $newUrl !== $url) {
                    $newFileSize = \app\service\ImagePersistService::getFileSize($newUrl);
                    $db::name('ai_travel_photo_result')
                        ->where('id', $record['id'])
                        ->update([
                            'url' => $newUrl,
                            'file_size' => $newFileSize > 0 ? $newFileSize : $record['file_size'],
                            'update_time' => time()
                        ]);
                    echo " 成功 (size: {$record['file_size']} -> {$newFileSize})\n";
                    $stats['success']++;
                } else {
                    echo " 跳过(已是webp或转换失败)\n";
                    $stats['skipped']++;
                }
            } catch (\Exception $e) {
                echo " 异常: {$e->getMessage()}\n";
                $stats['failed']++;
            }
            $stats['processed']++;
        }
        echo "\n";
    }

    // ============================================================
    // [2] 迁移 ai_travel_photo_result.result_url_watermark
    // ============================================================
    if ($type === 'all' || $type === 'travel_watermark') {
        echo "[2] 迁移旅拍水印图 (ai_travel_photo_result.result_url_watermark)...\n";

        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
        $query = $db::name('ai_travel_photo_result')
            ->where('status', '>', 0)
            ->where('result_url_watermark', '<>', '')
            ->where(function ($q) use ($imageExts) {
                foreach ($imageExts as $ext) {
                    $q->whereOr('result_url_watermark', 'like', '%.' . $ext);
                }
            })
            ->order('id asc');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $records = $query->field('id, aid, result_url_watermark')->select()->toArray();
        echo "  找到 " . count($records) . " 条待处理记录\n";

        foreach ($records as $record) {
            $stats['total']++;
            $url = $record['result_url_watermark'];
            $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));

            if ($ext === 'webp' || !in_array($ext, $imageExts)) {
                $stats['skipped']++;
                continue;
            }

            if ($dryRun) {
                echo "  [预览] 记录#{$record['id']} result_url_watermark将转为WebP\n";
                $stats['processed']++;
                continue;
            }

            echo "  [处理] 记录#{$record['id']} watermark...";
            try {
                $aid = intval($record['aid'] ?? 0);
                $newUrl = $persistService->persistAndCompress($url, $aid, 'watermark');
                if ($newUrl && $newUrl !== $url) {
                    $db::name('ai_travel_photo_result')
                        ->where('id', $record['id'])
                        ->update([
                            'result_url_watermark' => $newUrl,
                            'update_time' => time()
                        ]);
                    echo " 成功\n";
                    $stats['success']++;
                } else {
                    echo " 跳过\n";
                    $stats['skipped']++;
                }
            } catch (\Exception $e) {
                echo " 异常: {$e->getMessage()}\n";
                $stats['failed']++;
            }
            $stats['processed']++;
        }
        echo "\n";
    }

    // ============================================================
    // [3] 迁移 generation_output.output_url (图片类型)
    // ============================================================
    if ($type === 'all' || $type === 'generation_output') {
        echo "[3] 迁移通用照片生成输出 (generation_output.output_url)...\n";

        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
        $query = $db::name('generation_output')
            ->where('output_type', 'image')
            ->where('output_url', '<>', '')
            ->where(function ($q) use ($imageExts) {
                foreach ($imageExts as $ext) {
                    $q->whereOr('output_url', 'like', '%.' . $ext);
                }
            })
            ->order('id asc');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $records = $query->field('id, output_url, file_size')->select()->toArray();
        echo "  找到 " . count($records) . " 条待处理记录\n";

        foreach ($records as $record) {
            $stats['total']++;
            $url = $record['output_url'];
            $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));

            if ($ext === 'webp' || !in_array($ext, $imageExts)) {
                $stats['skipped']++;
                continue;
            }

            if ($dryRun) {
                echo "  [预览] 记录#{$record['id']} output_url将转为WebP: " . substr($url, -40) . "\n";
                $stats['processed']++;
                continue;
            }

            echo "  [处理] 记录#{$record['id']}...";
            try {
                $newUrl = $persistService->persistAndCompress($url, 0, 'generation');
                if ($newUrl && $newUrl !== $url) {
                    $newFileSize = \app\service\ImagePersistService::getFileSize($newUrl);
                    $db::name('generation_output')
                        ->where('id', $record['id'])
                        ->update([
                            'output_url' => $newUrl,
                            'file_size' => $newFileSize > 0 ? $newFileSize : $record['file_size'],
                        ]);
                    echo " 成功 (size: {$record['file_size']} -> {$newFileSize})\n";
                    $stats['success']++;
                } else {
                    echo " 跳过(已是webp或转换失败)\n";
                    $stats['skipped']++;
                }
            } catch (\Exception $e) {
                echo " 异常: {$e->getMessage()}\n";
                $stats['failed']++;
            }
            $stats['processed']++;
        }
        echo "\n";
    }

    // 输出统计
    echo "========================================\n";
    echo " 迁移完成统计\n";
    echo "========================================\n";
    echo "总计: {$stats['total']}\n";
    echo "已处理: {$stats['processed']}\n";
    echo "成功: {$stats['success']}\n";
    echo "失败: {$stats['failed']}\n";
    echo "跳过: {$stats['skipped']}\n";

} catch (\Exception $e) {
    echo "\n[错误] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

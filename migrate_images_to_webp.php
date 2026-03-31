<?php
/**
 * 批量迁移历史图片数据为 WebP 格式
 * 
 * 用法：
 *   php migrate_images_to_webp.php --limit=20           # 处理20条模板记录
 *   php migrate_images_to_webp.php --all                # 处理所有模板记录
 *   php migrate_images_to_webp.php --type=gif_cover     # 仅处理视频动态封面
 *   php migrate_images_to_webp.php --type=cover         # 仅处理封面图
 *   php migrate_images_to_webp.php --type=images        # 仅处理原图和效果图
 *   php migrate_images_to_webp.php --dry-run            # 仅预览，不实际执行
 * 
 * 说明：
 *   - 该工具会逐条下载模板的图片，转换为 WebP 格式后重新上传
 *   - 视频动态封面会使用 FFmpeg 重新生成5秒 480px 宽的 Animated WebP
 *   - 转换失败的记录会保留原格式，不影响前端显示
 *   - 建议在服务器低峰期执行，避免影响业务
 */

// 初始化 ThinkPHP 框架
define('ROOT_PATH', __DIR__ . '/');
require ROOT_PATH . 'vendor/autoload.php';

// 解析命令行参数
$options = getopt('', ['limit:', 'all', 'type:', 'dry-run']);
$limit = isset($options['all']) ? 0 : (intval($options['limit'] ?? 20));
$type = $options['type'] ?? 'all';
$dryRun = isset($options['dry-run']);

echo "========================================\n";
echo " 历史图片 WebP 批量迁移工具\n";
echo "========================================\n";
echo "迁移类型: {$type}\n";
echo "处理数量: " . ($limit > 0 ? $limit : '全部') . "\n";
echo "模式: " . ($dryRun ? '预览(dry-run)' : '执行') . "\n";
echo "----------------------------------------\n\n";

try {
    $app = new \think\App();
    $app->initialize();

    $db = \think\facade\Db::class;

    $stats = [
        'total' => 0,
        'processed' => 0,
        'success' => 0,
        'failed' => 0,
        'skipped' => 0,
    ];

    // 处理视频动态封面
    if ($type === 'all' || $type === 'gif_cover') {
        echo "[1] 处理视频动态封面 (gif_cover)...\n";
        $generationService = new \app\service\GenerationService();

        $query = $db::name('generation_scene_template')
            ->where('generation_type', 2)
            ->where('status', 1)
            ->where('cover_image', '<>', '')
            ->where(function ($q) {
                $q->where('gif_cover', '')
                  ->whereOr('gif_cover', 'like', '%.gif');
            });

        if ($limit > 0) {
            $query->limit($limit);
        }

        $templates = $query->select()->toArray();
        echo "  找到 " . count($templates) . " 条待处理记录\n";

        foreach ($templates as $tpl) {
            $stats['total']++;
            $videoUrl = $tpl['cover_image'];

            // 检查 cover_image 是否为视频 URL
            $ext = strtolower(pathinfo(parse_url($videoUrl, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
            if (!in_array($ext, ['mp4', 'webm', 'mov', 'avi', 'mkv', 'flv', 'm4v'])) {
                echo "  [跳过] 模板#{$tpl['id']} cover_image非视频: {$ext}\n";
                $stats['skipped']++;
                continue;
            }

            if ($dryRun) {
                echo "  [预览] 模板#{$tpl['id']} 将重新生成5秒Animated WebP\n";
                $stats['processed']++;
                continue;
            }

            echo "  [处理] 模板#{$tpl['id']}...";
            try {
                $result = $generationService->generateGifCover($tpl['id'], $videoUrl);
                if ($result) {
                    echo " 成功\n";
                    $stats['success']++;
                } else {
                    echo " 失败\n";
                    $stats['failed']++;
                }
            } catch (\Exception $e) {
                echo " 异常: {$e->getMessage()}\n";
                $stats['failed']++;
            }
            $stats['processed']++;
        }
        echo "\n";
    }

    // 处理封面图（cover_image 为图片的记录）
    if ($type === 'all' || $type === 'cover') {
        echo "[2] 处理封面图 (cover_image)...\n";

        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
        $likeConditions = array_map(function ($ext) { return '%.'. $ext; }, $imageExts);

        $query = $db::name('generation_scene_template')
            ->where('status', 1)
            ->where('cover_image', '<>', '')
            ->where(function ($q) use ($likeConditions) {
                foreach ($likeConditions as $like) {
                    $q->whereOr('cover_image', 'like', $like);
                }
            });

        if ($limit > 0) {
            $query->limit($limit);
        }

        $templates = $query->select()->toArray();
        echo "  找到 " . count($templates) . " 条待处理记录\n";

        foreach ($templates as $tpl) {
            $stats['total']++;
            $coverUrl = $tpl['cover_image'];

            if ($dryRun) {
                echo "  [预览] 模板#{$tpl['id']} cover_image将转为WebP\n";
                $stats['processed']++;
                continue;
            }

            echo "  [处理] 模板#{$tpl['id']} cover_image...";
            try {
                $newUrl = convertRemoteImageToWebp($coverUrl);
                if ($newUrl && $newUrl !== $coverUrl) {
                    $db::name('generation_scene_template')
                        ->where('id', $tpl['id'])
                        ->update(['cover_image' => $newUrl, 'update_time' => time()]);
                    echo " 成功\n";
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

    // 处理原图和效果图（JSON数组字段）
    if ($type === 'all' || $type === 'images') {
        echo "[3] 处理原图和效果图 (original_images, effect_images)...\n";

        $query = $db::name('generation_scene_template')
            ->where('status', 1)
            ->where(function ($q) {
                $q->where('original_images', '<>', '')
                  ->whereOr('effect_images', '<>', '')
                  ->whereOr('sample_images', '<>', '');
            });

        if ($limit > 0) {
            $query->limit($limit);
        }

        $templates = $query->select()->toArray();
        echo "  找到 " . count($templates) . " 条待处理记录\n";

        $imageFields = ['original_images', 'effect_images', 'sample_images'];

        foreach ($templates as $tpl) {
            $stats['total']++;
            $updateData = [];
            $hasChange = false;

            foreach ($imageFields as $field) {
                if (empty($tpl[$field])) continue;
                $images = is_string($tpl[$field]) ? json_decode($tpl[$field], true) : $tpl[$field];
                if (!is_array($images)) continue;

                $newImages = [];
                $fieldChanged = false;
                foreach ($images as $imgUrl) {
                    if (empty($imgUrl) || $imgUrl === '') {
                        continue; // 过滤空字符串
                    }
                    $ext = strtolower(pathinfo(parse_url($imgUrl, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
                    if ($ext === 'webp' || !in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
                        $newImages[] = $imgUrl;
                        continue;
                    }

                    if ($dryRun) {
                        echo "  [预览] 模板#{$tpl['id']} {$field} 图片将转为WebP: " . substr($imgUrl, -30) . "\n";
                        $newImages[] = $imgUrl;
                        $fieldChanged = true;
                        continue;
                    }

                    $newUrl = convertRemoteImageToWebp($imgUrl);
                    if ($newUrl && $newUrl !== $imgUrl) {
                        $newImages[] = $newUrl;
                        $fieldChanged = true;
                    } else {
                        $newImages[] = $imgUrl;
                    }
                }

                if ($fieldChanged) {
                    $updateData[$field] = json_encode($newImages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $hasChange = true;
                }
            }

            if ($hasChange && !$dryRun) {
                $updateData['update_time'] = time();
                $db::name('generation_scene_template')
                    ->where('id', $tpl['id'])
                    ->update($updateData);
                echo "  [处理] 模板#{$tpl['id']} 图片字段已更新\n";
                $stats['success']++;
            } elseif ($hasChange) {
                $stats['processed']++;
            } else {
                $stats['skipped']++;
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

/**
 * 将远程图片下载并转换为 WebP 格式后重新上传
 * @param string $imageUrl 原始图片URL
 * @return string|false 新的WebP URL，失败返回false
 */
function convertRemoteImageToWebp($imageUrl)
{
    if (empty($imageUrl)) return false;

    // 已经是 webp 格式，跳过
    $ext = strtolower(pathinfo(parse_url($imageUrl, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
    if ($ext === 'webp') return $imageUrl;

    // 不是图片格式，跳过
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) return false;

    // 下载图片
    $tempDir = ROOT_PATH . 'runtime/temp/migrate_webp/';
    if (!is_dir($tempDir)) {
        @mkdir($tempDir, 0777, true);
    }

    $uniqueId = md5($imageUrl . time() . mt_rand());
    $tempFile = $tempDir . $uniqueId . '.' . $ext;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $imageUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($content === false || $httpCode != 200 || empty($content)) {
        return false;
    }

    @file_put_contents($tempFile, $content);

    // 转换为 WebP
    $webpPath = \app\common\Pic::convertToWebp($tempFile);
    if ($webpPath === false) {
        @unlink($tempFile);
        return false;
    }

    // 上传到存储
    $aid = defined('aid') ? aid : 0;
    $uploadDir = 'upload/generation_template/' . $aid . '/' . date('Ym') . '/';
    $localDir = ROOT_PATH . $uploadDir;
    if (!is_dir($localDir)) {
        @mkdir($localDir, 0777, true);
    }

    $webpFilename = $uniqueId . '.webp';
    $localPath = $localDir . $webpFilename;
    @copy($webpPath, $localPath);
    @unlink($webpPath);

    // 上传到 OSS
    $newUrl = '';
    if (defined('PRE_URL')) {
        $localUrl = PRE_URL . '/' . $uploadDir . $webpFilename;
        $newUrl = \app\common\Pic::uploadoss($localUrl);
        if ($newUrl === false) {
            $newUrl = $localUrl;
        }
    } else {
        $newUrl = '/' . $uploadDir . $webpFilename;
    }

    return $newUrl;
}

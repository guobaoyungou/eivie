<?php
/**
 * 数据库迁移脚本：business表新增人脸识别水印开关字段
 * 
 * ai_pick_face_watermark_enabled - 选片中心人脸识别水印开关（0关闭 1开启）
 * 
 * 执行方式: php migrate_pick_face_watermark.php
 */

define('ROOT_PATH', __DIR__ . '/');

require __DIR__ . '/vendor/autoload.php';

// 初始化ThinkPHP应用
$app = new \think\App();
$app->initialize();

use think\facade\Db;

echo "=== 开始迁移：business表新增人脸识别水印字段 ===\n\n";

try {
    // 检查 ai_pick_face_watermark_enabled 字段是否存在
    $columns = Db::query("SHOW COLUMNS FROM ddwx_business LIKE 'ai_pick_face_watermark_enabled'");
    if (empty($columns)) {
        Db::execute("ALTER TABLE ddwx_business ADD COLUMN `ai_pick_face_watermark_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '选片中心人脸识别水印开关 0关闭 1开启' AFTER `ai_pick_article_desc`");
        echo "✅ 已添加字段: ai_pick_face_watermark_enabled\n";
    } else {
        echo "⏭️  字段已存在: ai_pick_face_watermark_enabled\n";
    }

    echo "\n=== 迁移完成 ===\n";
} catch (\Exception $e) {
    echo "❌ 迁移失败: " . $e->getMessage() . "\n";
    exit(1);
}

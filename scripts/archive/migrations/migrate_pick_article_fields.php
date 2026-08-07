<?php
/**
 * 数据库迁移脚本：business表新增选片图文消息自定义字段
 * 
 * ai_pick_article_title - 选片图文消息标题
 * ai_pick_article_desc  - 选片图文消息描述
 * 
 * 执行方式: php migrate_pick_article_fields.php
 */

define('ROOT_PATH', __DIR__ . '/');

require __DIR__ . '/vendor/autoload.php';

// 初始化ThinkPHP应用
$app = new \think\App();
$app->initialize();

use think\facade\Db;

echo "=== 开始迁移：business表新增选片图文消息字段 ===\n\n";

try {
    // 检查 ai_pick_article_title 字段是否存在
    $columns = Db::query("SHOW COLUMNS FROM ddwx_business LIKE 'ai_pick_article_title'");
    if (empty($columns)) {
        Db::execute("ALTER TABLE ddwx_business ADD COLUMN `ai_pick_article_title` varchar(100) NOT NULL DEFAULT '' COMMENT '选片图文消息标题' AFTER `ai_max_scenes`");
        echo "✅ 已添加字段: ai_pick_article_title\n";
    } else {
        echo "⏭️  字段已存在: ai_pick_article_title\n";
    }

    // 检查 ai_pick_article_desc 字段是否存在
    $columns = Db::query("SHOW COLUMNS FROM ddwx_business LIKE 'ai_pick_article_desc'");
    if (empty($columns)) {
        Db::execute("ALTER TABLE ddwx_business ADD COLUMN `ai_pick_article_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '选片图文消息描述' AFTER `ai_pick_article_title`");
        echo "✅ 已添加字段: ai_pick_article_desc\n";
    } else {
        echo "⏭️  字段已存在: ai_pick_article_desc\n";
    }

    echo "\n=== 迁移完成 ===\n";
} catch (\Exception $e) {
    echo "❌ 迁移失败: " . $e->getMessage() . "\n";
    exit(1);
}

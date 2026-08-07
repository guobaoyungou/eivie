<?php
/**
 * 修复合成流程缺失的数据库字段
 * 访问: /migrate_synthesis_columns.php
 * 日期: 2026-03-17
 */

// 定义ROOT_PATH以便database.php能正确加载config.php
define('ROOT_PATH', __DIR__ . '/');

require __DIR__ . '/vendor/autoload.php';

$app = new \think\App();
$app->initialize();

use think\facade\Db;

echo "<h2>修复合成流程缺失的数据库字段</h2>\n";
echo "<pre>\n";

$results = [];

// 1. generation表新增template_id字段
try {
    $columns = Db::query("SHOW COLUMNS FROM ddwx_ai_travel_photo_generation LIKE 'template_id'");
    if (empty($columns)) {
        Db::execute("ALTER TABLE `ddwx_ai_travel_photo_generation` ADD COLUMN `template_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联场景模板ID' AFTER `scene_id`");
        Db::execute("ALTER TABLE `ddwx_ai_travel_photo_generation` ADD INDEX `idx_template_id` (`template_id`)");
        $results[] = "✅ generation表：template_id 字段已添加";
    } else {
        $results[] = "⏩ generation表：template_id 字段已存在，跳过";
    }
} catch (\Exception $e) {
    $results[] = "❌ generation表 template_id 添加失败: " . $e->getMessage();
}

// 2. result表新增bid字段
try {
    $columns = Db::query("SHOW COLUMNS FROM ddwx_ai_travel_photo_result LIKE 'bid'");
    if (empty($columns)) {
        Db::execute("ALTER TABLE `ddwx_ai_travel_photo_result` ADD COLUMN `bid` int(11) NOT NULL DEFAULT 0 COMMENT '商家/门店ID' AFTER `aid`");
        Db::execute("ALTER TABLE `ddwx_ai_travel_photo_result` ADD INDEX `idx_bid` (`bid`)");
        $results[] = "✅ result表：bid 字段已添加";
    } else {
        $results[] = "⏩ result表：bid 字段已存在，跳过";
    }
} catch (\Exception $e) {
    $results[] = "❌ result表 bid 添加失败: " . $e->getMessage();
}

// 输出结果
foreach ($results as $r) {
    echo $r . "\n";
}

// 验证
echo "\n--- 验证 generation 表结构 ---\n";
$genCols = Db::query("SHOW COLUMNS FROM ddwx_ai_travel_photo_generation");
foreach ($genCols as $col) {
    if (in_array($col['Field'], ['id', 'template_id', 'portrait_id', 'scene_id', 'bid', 'prompt', 'status', 'error_msg'])) {
        echo "  {$col['Field']} | {$col['Type']} | {$col['Null']} | Default: {$col['Default']}\n";
    }
}

echo "\n--- 验证 result 表结构 ---\n";
$resCols = Db::query("SHOW COLUMNS FROM ddwx_ai_travel_photo_result");
foreach ($resCols as $col) {
    if (in_array($col['Field'], ['id', 'aid', 'bid', 'generation_id', 'portrait_id', 'scene_id', 'url', 'desc', 'status'])) {
        echo "  {$col['Field']} | {$col['Type']} | {$col['Null']} | Default: {$col['Default']}\n";
    }
}

echo "\n完成！\n";
echo "</pre>\n";

<?php
/**
 * 数据库迁移脚本：ddwx_model_info 新增 is_recommend 字段
 * 用于模型广场Tab页优化 — 支持推荐模型筛选
 * 
 * 执行方式: php migrate_model_recommend.php
 */

// 读取数据库配置
$config = require __DIR__ . '/config.php';

$pdo = new PDO(
    "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']}",
    $config['username'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "=== 开始迁移：ddwx_model_info 新增 is_recommend 字段 ===\n\n";

// 辅助函数：检查字段是否存在
function fieldExists($pdo, $table, $field) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$field}'");
    return $stmt->rowCount() > 0;
}

// 辅助函数：检查索引是否存在
function indexExists($pdo, $table, $indexName) {
    $stmt = $pdo->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'");
    return $stmt->rowCount() > 0;
}

$table = 'ddwx_model_info';

// 检查表是否存在
try {
    $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
    echo "✓ 表 {$table} 存在\n";
} catch (\PDOException $e) {
    echo "✗ 表 {$table} 不存在，请先创建该表\n";
    exit(1);
}

// 添加 is_recommend 字段
if (!fieldExists($pdo, $table, 'is_recommend')) {
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `is_recommend` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '是否推荐：0=否，1=是' AFTER `is_active`");
    echo "✓ 已添加 is_recommend 字段\n";
} else {
    echo "- is_recommend 字段已存在，跳过\n";
}

// 添加组合索引
if (!indexExists($pdo, $table, 'idx_recommend_active')) {
    $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `idx_recommend_active` (`is_recommend`, `is_active`)");
    echo "✓ 已添加 idx_recommend_active 组合索引\n";
} else {
    echo "- idx_recommend_active 索引已存在，跳过\n";
}

// 验证
if (fieldExists($pdo, $table, 'is_recommend')) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE 'is_recommend'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ 验证通过：is_recommend 字段存在\n";
    echo "  类型: " . $col['Type'] . "\n";
    echo "  默认值: " . $col['Default'] . "\n";
} else {
    echo "✗ 验证失败：is_recommend 字段不存在\n";
}

echo "\n=== 迁移完成 ===\n";

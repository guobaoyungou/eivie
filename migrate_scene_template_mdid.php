<?php
/**
 * 数据库迁移：场景模板表新增 mdid 字段
 * 执行方式：php migrate_scene_template_mdid.php
 */

// 读取数据库配置
$config = require __DIR__ . '/config.php';

$pdo = new PDO(
    "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']}",
    $config['username'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "=== 场景模板表新增 mdid 字段迁移 ===\n\n";

// 辅助函数：检查字段是否存在
function fieldExists($pdo, $table, $field) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$field}'");
    return $stmt->rowCount() > 0;
}

$table = 'ddwx_generation_scene_template';

// 检查表是否存在
try {
    $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
    echo "✓ 表 {$table} 存在\n";
} catch (\PDOException $e) {
    echo "✗ 表 {$table} 不存在，请先创建该表\n";
    exit(1);
}

// 添加 mdid 字段
if (!fieldExists($pdo, $table, 'mdid')) {
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `mdid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联门店ID，0表示不限门店/通用模板' AFTER `category_ids`");
    echo "✓ 已添加 mdid 字段\n";
} else {
    echo "- mdid 字段已存在，跳过\n";
}

// 验证
if (fieldExists($pdo, $table, 'mdid')) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE 'mdid'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ 验证通过：mdid 字段存在\n";
    echo "  类型: " . $col['Type'] . "\n";
    echo "  默认值: " . $col['Default'] . "\n";
} else {
    echo "✗ 验证失败：mdid 字段不存在\n";
}

echo "\n=== 迁移完成 ===\n";

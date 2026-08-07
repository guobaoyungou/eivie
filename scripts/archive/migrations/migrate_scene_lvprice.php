<?php
/**
 * 场景模板会员价格设置 - 数据库迁移脚本
 * 在 ddwx_generation_scene_template 表中新增以下字段：
 * - lvprice: tinyint(1) 会员价开关
 * - lvprice_data: text 会员价格数据(JSON)
 * - base_price: decimal(10,2) 基础价格
 * - price_unit: varchar(20) 计价单位
 */

// 读取数据库配置
$config = require __DIR__ . '/config.php';

$pdo = new PDO(
    "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']}",
    $config['username'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "=== 场景模板会员价格字段迁移 ===\n\n";

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

// 1. 添加 base_price 字段
if (!fieldExists($pdo, $table, 'base_price')) {
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `base_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '基础价格（游客价格）'");
    echo "✓ 已添加 base_price 字段\n";
} else {
    echo "- base_price 字段已存在，跳过\n";
}

// 2. 添加 price_unit 字段
if (!fieldExists($pdo, $table, 'price_unit')) {
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `price_unit` varchar(20) NOT NULL DEFAULT 'per_image' COMMENT '计价单位：per_image=按张，per_second=按秒'");
    echo "✓ 已添加 price_unit 字段\n";
} else {
    echo "- price_unit 字段已存在，跳过\n";
}

// 3. 添加 lvprice 字段
if (!fieldExists($pdo, $table, 'lvprice')) {
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `lvprice` tinyint(1) NOT NULL DEFAULT '0' COMMENT '会员价开关：0=关闭，1=开启'");
    echo "✓ 已添加 lvprice 字段\n";
} else {
    echo "- lvprice 字段已存在，跳过\n";
}

// 4. 添加 lvprice_data 字段
if (!fieldExists($pdo, $table, 'lvprice_data')) {
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `lvprice_data` text NULL COMMENT '会员价格数据（JSON格式）'");
    echo "✓ 已添加 lvprice_data 字段\n";
} else {
    echo "- lvprice_data 字段已存在，跳过\n";
}

echo "\n=== 迁移完成 ===\n";

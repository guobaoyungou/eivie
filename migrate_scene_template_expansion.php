<?php
/**
 * 场景模板功能扩展 - 数据库迁移脚本
 * 在 ddwx_generation_scene_template 表中新增以下字段：
 * - prompt_visible: tinyint(1) 提示词是否对用户可见（1=可见，0=隐藏），默认1
 * - is_id_photo: tinyint(1) 是否为证件照模式（0=否，1=是），默认0
 * - id_photo_type: tinyint(2) 证件照类型（0=未设置，1=身份证照，2=护照/港澳通行证，3=驾驶证，4=一寸照，5=二寸照），默认0
 */

// 读取数据库配置
$config = require __DIR__ . '/config.php';

$pdo = new PDO(
    "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']}",
    $config['username'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "=== 场景模板功能扩展字段迁移 ===\n\n";

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

// 1. 添加 prompt_visible 字段（追加在 output_quantity 之后）
if (!fieldExists($pdo, $table, 'prompt_visible')) {
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `prompt_visible` tinyint(1) NOT NULL DEFAULT '1' COMMENT '提示词是否对用户可见：1=可见，0=隐藏' AFTER `output_quantity`");
    echo "✓ 已添加 prompt_visible 字段\n";
} else {
    echo "- prompt_visible 字段已存在，跳过\n";
}

// 2. 添加 is_id_photo 字段（追加在 prompt_visible 之后）
if (!fieldExists($pdo, $table, 'is_id_photo')) {
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `is_id_photo` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为证件照模式：0=否，1=是' AFTER `prompt_visible`");
    echo "✓ 已添加 is_id_photo 字段\n";
} else {
    echo "- is_id_photo 字段已存在，跳过\n";
}

// 3. 添加 id_photo_type 字段（追加在 is_id_photo 之后）
if (!fieldExists($pdo, $table, 'id_photo_type')) {
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `id_photo_type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '证件照类型：0=未设置，1=身份证照，2=护照/港澳通行证，3=驾驶证，4=一寸照，5=二寸照' AFTER `is_id_photo`");
    echo "✓ 已添加 id_photo_type 字段\n";
} else {
    echo "- id_photo_type 字段已存在，跳过\n";
}

echo "\n=== 迁移完成 ===\n";

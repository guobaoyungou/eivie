<?php
/**
 * 数据库迁移脚本：签到设置功能扩展 — 员工号、上传照片、自定义字段
 * 
 * 1. ddwx_signset 表新增 5 个开关字段
 * 2. ddwx_sign_record 表新增 3 个扩展字段
 * 3. 新建 ddwx_sign_custom_field 表
 * 
 * 执行方式：php migrate_sign_custom_fields.php
 */

define('ROOT_PATH', __DIR__ . '/');
define('APP_PATH', __DIR__ . '/app');

require_once __DIR__ . '/vendor/autoload.php';

// 初始化ThinkPHP应用
$app = new \think\App();
$app->initialize();

use think\facade\Db;

echo "=== 开始迁移：签到设置功能扩展（员工号/上传照片/自定义字段） ===\n\n";

// 辅助函数：检查字段是否存在
function fieldExists($table, $field) {
    $exists = Db::query("SHOW COLUMNS FROM `{$table}` LIKE '{$field}'");
    return !empty($exists);
}

// 辅助函数：检查表是否存在
function tableExists($table) {
    try {
        Db::query("SELECT 1 FROM `{$table}` LIMIT 1");
        return true;
    } catch (\Exception $e) {
        return false;
    }
}

// ============================================================
// 1. ddwx_signset 表新增字段
// ============================================================
echo "--- 1. ddwx_signset 表新增字段 ---\n";

$signsetFields = [
    'show_employee_no'    => "ALTER TABLE `ddwx_signset` ADD COLUMN `show_employee_no` tinyint(1) NOT NULL DEFAULT 0 COMMENT '员工号字段开关：0关闭 1开启'",
    'require_employee_no' => "ALTER TABLE `ddwx_signset` ADD COLUMN `require_employee_no` tinyint(1) NOT NULL DEFAULT 0 COMMENT '员工号是否必填：0非必填 1必填'",
    'show_photo'          => "ALTER TABLE `ddwx_signset` ADD COLUMN `show_photo` tinyint(1) NOT NULL DEFAULT 0 COMMENT '上传照片字段开关：0关闭 1开启'",
    'require_photo'       => "ALTER TABLE `ddwx_signset` ADD COLUMN `require_photo` tinyint(1) NOT NULL DEFAULT 0 COMMENT '上传照片是否必填：0非必填 1必填'",
    'show_custom_fields'  => "ALTER TABLE `ddwx_signset` ADD COLUMN `show_custom_fields` tinyint(1) NOT NULL DEFAULT 0 COMMENT '自定义字段功能总开关：0关闭 1开启'",
];

foreach ($signsetFields as $fieldName => $sql) {
    try {
        if (fieldExists('ddwx_signset', $fieldName)) {
            echo "✓ 字段 ddwx_signset.{$fieldName} 已存在，跳过\n";
            continue;
        }
        Db::execute($sql);
        echo "✓ 成功添加字段 ddwx_signset.{$fieldName}\n";
    } catch (\Exception $e) {
        echo "✗ 添加字段 ddwx_signset.{$fieldName} 失败: " . $e->getMessage() . "\n";
    }
}

// ============================================================
// 2. ddwx_sign_record 表新增字段
// ============================================================
echo "\n--- 2. ddwx_sign_record 表新增字段 ---\n";

$recordFields = [
    'employee_no'  => "ALTER TABLE `ddwx_sign_record` ADD COLUMN `employee_no` varchar(50) DEFAULT NULL COMMENT '用户填写的员工号'",
    'sign_photo'   => "ALTER TABLE `ddwx_sign_record` ADD COLUMN `sign_photo` varchar(500) DEFAULT NULL COMMENT '上传照片的URL'",
    'custom_data'  => "ALTER TABLE `ddwx_sign_record` ADD COLUMN `custom_data` text DEFAULT NULL COMMENT '自定义字段数据，JSON格式存储'",
];

foreach ($recordFields as $fieldName => $sql) {
    try {
        if (fieldExists('ddwx_sign_record', $fieldName)) {
            echo "✓ 字段 ddwx_sign_record.{$fieldName} 已存在，跳过\n";
            continue;
        }
        Db::execute($sql);
        echo "✓ 成功添加字段 ddwx_sign_record.{$fieldName}\n";
    } catch (\Exception $e) {
        echo "✗ 添加字段 ddwx_sign_record.{$fieldName} 失败: " . $e->getMessage() . "\n";
    }
}

// ============================================================
// 3. 新建 ddwx_sign_custom_field 表
// ============================================================
echo "\n--- 3. 新建 ddwx_sign_custom_field 表 ---\n";

if (tableExists('ddwx_sign_custom_field')) {
    echo "✓ 表 ddwx_sign_custom_field 已存在，跳过\n";
} else {
    try {
        $createSql = "CREATE TABLE `ddwx_sign_custom_field` (
            `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
            `aid` int(11) NOT NULL COMMENT '应用ID',
            `field_name` varchar(100) NOT NULL COMMENT '字段显示名称',
            `field_type` varchar(20) NOT NULL DEFAULT 'text' COMMENT '字段类型：text/select/checkbox/image',
            `field_options` text DEFAULT NULL COMMENT '选项值（select/checkbox时为JSON数组）',
            `is_required` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否必填：0否 1是',
            `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序值，越小越靠前',
            `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1启用',
            `createtime` int(11) DEFAULT NULL COMMENT '创建时间戳',
            PRIMARY KEY (`id`),
            KEY `idx_aid` (`aid`),
            KEY `idx_aid_status` (`aid`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='签到自定义字段定义表'";
        Db::execute($createSql);
        echo "✓ 成功创建表 ddwx_sign_custom_field\n";
    } catch (\Exception $e) {
        echo "✗ 创建表 ddwx_sign_custom_field 失败: " . $e->getMessage() . "\n";
    }
}

echo "\n=== 迁移完成 ===\n";

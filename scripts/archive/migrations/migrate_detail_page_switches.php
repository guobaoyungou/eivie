<?php
/**
 * 数据库迁移脚本：为 ddwx_business 表新增详情页展示开关字段
 * ai_show_store_info      - 详情页是否显示门店信息
 * ai_show_commission       - 详情页是否显示分销佣金
 * ai_show_upgrade_discount - 详情页是否显示升级优惠
 * 
 * 执行方式：php migrate_detail_page_switches.php
 */

define('ROOT_PATH', __DIR__ . '/');
define('APP_PATH', __DIR__ . '/app');

require_once __DIR__ . '/vendor/autoload.php';

// 初始化ThinkPHP应用
$app = new \think\App();
$app->initialize();

use think\facade\Db;

echo "=== 开始迁移：ddwx_business 表新增详情页展示开关字段 ===\n\n";

$fields = [
    'ai_show_store_info' => "ALTER TABLE `ddwx_business` ADD COLUMN `ai_show_store_info` tinyint(1) NOT NULL DEFAULT 0 COMMENT '详情页是否显示门店信息(0关闭/1开启)'",
    'ai_show_commission' => "ALTER TABLE `ddwx_business` ADD COLUMN `ai_show_commission` tinyint(1) NOT NULL DEFAULT 0 COMMENT '详情页是否显示分销佣金(0关闭/1开启)'",
    'ai_show_upgrade_discount' => "ALTER TABLE `ddwx_business` ADD COLUMN `ai_show_upgrade_discount` tinyint(1) NOT NULL DEFAULT 0 COMMENT '详情页是否显示升级优惠(0关闭/1开启)'",
];

foreach ($fields as $fieldName => $sql) {
    try {
        // 检查字段是否已存在
        $exists = Db::query("SHOW COLUMNS FROM `ddwx_business` LIKE '{$fieldName}'");
        if (!empty($exists)) {
            echo "✓ 字段 {$fieldName} 已存在，跳过\n";
            continue;
        }
        
        Db::execute($sql);
        echo "✓ 成功添加字段 {$fieldName}\n";
    } catch (\Exception $e) {
        echo "✗ 添加字段 {$fieldName} 失败: " . $e->getMessage() . "\n";
    }
}

echo "\n=== 迁移完成 ===\n";

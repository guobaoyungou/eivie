<?php
/**
 * 商户首页"旅拍商品"Tab 数据库迁移脚本
 * 
 * 功能：
 * 1. business_sysset 表新增 show_travel_photo 和 show_travel_phototext 字段
 * 
 * 使用方法：php migrate_business_travel_photo_tab.php
 */

require_once __DIR__ . '/index.php';

use think\facade\Db;

echo "=== 商户首页旅拍商品Tab 数据库迁移 ===\n\n";

try {
    $prefix = config('database.connections.mysql.prefix');
    $table = $prefix . 'business_sysset';

    // 1. business_sysset 表新增 show_travel_photo 字段
    echo "1. 检查 {$table} 表 show_travel_photo 字段...\n";
    $columns = Db::query("SHOW COLUMNS FROM `{$table}` LIKE 'show_travel_photo'");
    if (empty($columns)) {
        Db::execute("ALTER TABLE `{$table}` ADD COLUMN `show_travel_photo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否在商户首页显示旅拍商品Tab 0=隐藏 1=显示' AFTER `show_detail`");
        echo "   ✓ 已添加 show_travel_photo 字段\n";
    } else {
        echo "   - show_travel_photo 字段已存在，跳过\n";
    }

    // 2. business_sysset 表新增 show_travel_phototext 字段
    echo "2. 检查 {$table} 表 show_travel_phototext 字段...\n";
    $columns = Db::query("SHOW COLUMNS FROM `{$table}` LIKE 'show_travel_phototext'");
    if (empty($columns)) {
        Db::execute("ALTER TABLE `{$table}` ADD COLUMN `show_travel_phototext` varchar(50) NOT NULL DEFAULT '旅拍商品' COMMENT '旅拍商品Tab自定义显示文本' AFTER `show_travel_photo`");
        echo "   ✓ 已添加 show_travel_phototext 字段\n";
    } else {
        echo "   - show_travel_phototext 字段已存在，跳过\n";
    }

    echo "\n=== 迁移完成 ===\n";

} catch (\Exception $e) {
    echo "迁移失败：" . $e->getMessage() . "\n";
    echo "文件：" . $e->getFile() . " 行：" . $e->getLine() . "\n";
    exit(1);
}

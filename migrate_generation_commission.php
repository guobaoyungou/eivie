<?php
/**
 * 数据库迁移脚本：为 generation_order 表新增分销佣金字段
 * 用于支持分享赚佣金功能
 * 
 * 使用方式：php migrate_generation_commission.php
 * 或通过浏览器访问
 */

require_once __DIR__ . '/vendor/autoload.php';

// 引入ThinkPHP框架
require_once __DIR__ . '/public/index.php';

use think\facade\Db;

echo "=== 生成订单分销字段迁移开始 ===\n";

try {
    // 检查 generation_order 表是否存在
    $tableExists = Db::query("SHOW TABLES LIKE 'generation_order'");
    if (empty($tableExists)) {
        echo "错误：generation_order 表不存在\n";
        exit(1);
    }

    // 获取现有字段
    $columns = Db::query("SHOW COLUMNS FROM generation_order");
    $existingColumns = array_column($columns, 'Field');

    // 需要新增的字段
    $fieldsToAdd = [
        'parent1' => "ALTER TABLE `generation_order` ADD COLUMN `parent1` int(11) NOT NULL DEFAULT 0 COMMENT '一级分销人ID' AFTER `updatetime`",
        'parent2' => "ALTER TABLE `generation_order` ADD COLUMN `parent2` int(11) NOT NULL DEFAULT 0 COMMENT '二级分销人ID' AFTER `parent1`",
        'parent3' => "ALTER TABLE `generation_order` ADD COLUMN `parent3` int(11) NOT NULL DEFAULT 0 COMMENT '三级分销人ID' AFTER `parent2`",
        'parent1commission' => "ALTER TABLE `generation_order` ADD COLUMN `parent1commission` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '一级佣金' AFTER `parent3`",
        'parent2commission' => "ALTER TABLE `generation_order` ADD COLUMN `parent2commission` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '二级佣金' AFTER `parent1commission`",
        'parent3commission' => "ALTER TABLE `generation_order` ADD COLUMN `parent3commission` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '三级佣金' AFTER `parent2commission`",
        'iscommission' => "ALTER TABLE `generation_order` ADD COLUMN `iscommission` tinyint(1) NOT NULL DEFAULT 0 COMMENT '佣金是否已结算：0=未结算 1=已结算' AFTER `parent3commission`",
        'pid' => "ALTER TABLE `generation_order` ADD COLUMN `pid` int(11) NOT NULL DEFAULT 0 COMMENT '推荐人用户ID（来自分享链接）' AFTER `iscommission`",
    ];

    $addedCount = 0;
    $skippedCount = 0;

    foreach ($fieldsToAdd as $fieldName => $sql) {
        if (in_array($fieldName, $existingColumns)) {
            echo "字段 {$fieldName} 已存在，跳过\n";
            $skippedCount++;
        } else {
            Db::execute($sql);
            echo "✅ 已新增字段：{$fieldName}\n";
            $addedCount++;
        }
    }

    echo "\n=== 迁移完成 ===\n";
    echo "新增字段：{$addedCount} 个\n";
    echo "跳过字段：{$skippedCount} 个\n";

} catch (\Exception $e) {
    echo "迁移失败：" . $e->getMessage() . "\n";
    exit(1);
}

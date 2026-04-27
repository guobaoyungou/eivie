<?php
/**
 * AI旅拍选片订单 - 分销字段迁移脚本
 * 
 * 1. ai_travel_photo_package 表新增: commissionset, commissiondata1, commissiondata2, commissiondata3
 * 2. ai_travel_photo_order 表新增: parent1~3, parent1commission~3commission, iscommission
 * 
 * 执行方式: php think migrate_ai_pick_commission 或 直接 php migrate_ai_pick_commission.php
 */

define('ROOT_PATH', __DIR__ . '/');

require __DIR__ . '/vendor/autoload.php';

// 初始化ThinkPHP应用
$app = new \think\App();
$app->initialize();

use think\facade\Db;

// 检查字段是否存在的辅助函数
if (!function_exists('pdo_fieldexists2')) {
    function pdo_fieldexists2($tablename, $fieldname) {
        try {
            $result = Db::query("SHOW COLUMNS FROM `ddwx_{$tablename}` LIKE '{$fieldname}'");
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }
}

echo "=== AI旅拍选片分销字段迁移 ===\n";
echo "开始时间: " . date('Y-m-d H:i:s') . "\n\n";

$success = 0;
$skipped = 0;
$errors = [];

// ========== 1. ai_travel_photo_package 表 ==========
echo "【1】ai_travel_photo_package 表 - 新增分销设置字段\n";

// commissionset - 分销模式
if (!pdo_fieldexists2('ai_travel_photo_package', 'commissionset')) {
    try {
        Db::execute("ALTER TABLE `ddwx_ai_travel_photo_package` ADD COLUMN `commissionset` tinyint(2) DEFAULT '-1' COMMENT '分销模式：0按会员等级 1价格比例 2固定金额 3分销送积分 -1不参与分销' AFTER `stock`");
        echo "   ✓ commissionset: 添加成功 (默认-1不参与分销)\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "commissionset: " . $e->getMessage();
        echo "   ✗ commissionset: " . $e->getMessage() . "\n";
    }
} else {
    echo "   - commissionset: 已存在，跳过\n";
    $skipped++;
}

// commissiondata1 - 按比例分销参数
if (!pdo_fieldexists2('ai_travel_photo_package', 'commissiondata1')) {
    try {
        Db::execute("ALTER TABLE `ddwx_ai_travel_photo_package` ADD COLUMN `commissiondata1` text DEFAULT NULL COMMENT '按比例分销参数（JSON序列化，按等级ID索引）' AFTER `commissionset`");
        echo "   ✓ commissiondata1: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "commissiondata1: " . $e->getMessage();
        echo "   ✗ commissiondata1: " . $e->getMessage() . "\n";
    }
} else {
    echo "   - commissiondata1: 已存在，跳过\n";
    $skipped++;
}

// commissiondata2 - 按固定金额参数
if (!pdo_fieldexists2('ai_travel_photo_package', 'commissiondata2')) {
    try {
        Db::execute("ALTER TABLE `ddwx_ai_travel_photo_package` ADD COLUMN `commissiondata2` text DEFAULT NULL COMMENT '按固定金额分销参数（JSON序列化）' AFTER `commissiondata1`");
        echo "   ✓ commissiondata2: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "commissiondata2: " . $e->getMessage();
        echo "   ✗ commissiondata2: " . $e->getMessage() . "\n";
    }
} else {
    echo "   - commissiondata2: 已存在，跳过\n";
    $skipped++;
}

// commissiondata3 - 分销送积分参数
if (!pdo_fieldexists2('ai_travel_photo_package', 'commissiondata3')) {
    try {
        Db::execute("ALTER TABLE `ddwx_ai_travel_photo_package` ADD COLUMN `commissiondata3` text DEFAULT NULL COMMENT '分销送积分参数（JSON序列化）' AFTER `commissiondata2`");
        echo "   ✓ commissiondata3: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "commissiondata3: " . $e->getMessage();
        echo "   ✗ commissiondata3: " . $e->getMessage() . "\n";
    }
} else {
    echo "   - commissiondata3: 已存在，跳过\n";
    $skipped++;
}

// ========== 2. ai_travel_photo_order 表 ==========
echo "\n【2】ai_travel_photo_order 表 - 新增分销佣金字段\n";

// parent1 - 一级分销人ID
if (!pdo_fieldexists2('ai_travel_photo_order', 'parent1')) {
    try {
        Db::execute("ALTER TABLE `ddwx_ai_travel_photo_order` ADD COLUMN `parent1` int(11) DEFAULT '0' COMMENT '一级分销人ID' AFTER `status`");
        echo "   ✓ parent1: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "parent1: " . $e->getMessage();
        echo "   ✗ parent1: " . $e->getMessage() . "\n";
    }
} else {
    echo "   - parent1: 已存在，跳过\n";
    $skipped++;
}

// parent2 - 二级分销人ID
if (!pdo_fieldexists2('ai_travel_photo_order', 'parent2')) {
    try {
        Db::execute("ALTER TABLE `ddwx_ai_travel_photo_order` ADD COLUMN `parent2` int(11) DEFAULT '0' COMMENT '二级分销人ID' AFTER `parent1`");
        echo "   ✓ parent2: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "parent2: " . $e->getMessage();
        echo "   ✗ parent2: " . $e->getMessage() . "\n";
    }
} else {
    echo "   - parent2: 已存在，跳过\n";
    $skipped++;
}

// parent3 - 三级分销人ID
if (!pdo_fieldexists2('ai_travel_photo_order', 'parent3')) {
    try {
        Db::execute("ALTER TABLE `ddwx_ai_travel_photo_order` ADD COLUMN `parent3` int(11) DEFAULT '0' COMMENT '三级分销人ID' AFTER `parent2`");
        echo "   ✓ parent3: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "parent3: " . $e->getMessage();
        echo "   ✗ parent3: " . $e->getMessage() . "\n";
    }
} else {
    echo "   - parent3: 已存在，跳过\n";
    $skipped++;
}

// parent1commission - 一级佣金
if (!pdo_fieldexists2('ai_travel_photo_order', 'parent1commission')) {
    try {
        Db::execute("ALTER TABLE `ddwx_ai_travel_photo_order` ADD COLUMN `parent1commission` decimal(12,2) DEFAULT '0.00' COMMENT '一级佣金' AFTER `parent3`");
        echo "   ✓ parent1commission: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "parent1commission: " . $e->getMessage();
        echo "   ✗ parent1commission: " . $e->getMessage() . "\n";
    }
} else {
    echo "   - parent1commission: 已存在，跳过\n";
    $skipped++;
}

// parent2commission - 二级佣金
if (!pdo_fieldexists2('ai_travel_photo_order', 'parent2commission')) {
    try {
        Db::execute("ALTER TABLE `ddwx_ai_travel_photo_order` ADD COLUMN `parent2commission` decimal(12,2) DEFAULT '0.00' COMMENT '二级佣金' AFTER `parent1commission`");
        echo "   ✓ parent2commission: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "parent2commission: " . $e->getMessage();
        echo "   ✗ parent2commission: " . $e->getMessage() . "\n";
    }
} else {
    echo "   - parent2commission: 已存在，跳过\n";
    $skipped++;
}

// parent3commission - 三级佣金
if (!pdo_fieldexists2('ai_travel_photo_order', 'parent3commission')) {
    try {
        Db::execute("ALTER TABLE `ddwx_ai_travel_photo_order` ADD COLUMN `parent3commission` decimal(12,2) DEFAULT '0.00' COMMENT '三级佣金' AFTER `parent2commission`");
        echo "   ✓ parent3commission: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "parent3commission: " . $e->getMessage();
        echo "   ✗ parent3commission: " . $e->getMessage() . "\n";
    }
} else {
    echo "   - parent3commission: 已存在，跳过\n";
    $skipped++;
}

// iscommission - 佣金是否已结算
if (!pdo_fieldexists2('ai_travel_photo_order', 'iscommission')) {
    try {
        Db::execute("ALTER TABLE `ddwx_ai_travel_photo_order` ADD COLUMN `iscommission` tinyint(1) DEFAULT '0' COMMENT '佣金是否已结算：0未结算 1已结算' AFTER `parent3commission`");
        echo "   ✓ iscommission: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "iscommission: " . $e->getMessage();
        echo "   ✗ iscommission: " . $e->getMessage() . "\n";
    }
} else {
    echo "   - iscommission: 已存在，跳过\n";
    $skipped++;
}

// ========== 汇总 ==========
echo "\n=== 迁移完成 ===\n";
echo "成功: {$success} 个字段\n";
echo "跳过: {$skipped} 个字段(已存在)\n";
echo "失败: " . count($errors) . " 个字段\n";

if (!empty($errors)) {
    echo "\n错误详情:\n";
    foreach ($errors as $err) {
        echo "  - {$err}\n";
    }
}

echo "\n结束时间: " . date('Y-m-d H:i:s') . "\n";

<?php
/**
 * 场景模板商品化设置字段迁移脚本
 * 为ddwx_generation_scene_template表添加分销、分红、积分抵扣、显示/购买条件等字段
 */
require_once __DIR__ . '/vendor/autoload.php';

// 初始化ThinkPHP应用
$app = new \think\App();
$app->initialize();

use think\facade\Db;

echo "=== 场景模板商品化设置字段迁移 ===\n";
echo "开始时间: " . date('Y-m-d H:i:s') . "\n\n";

// 检查字段是否存在的辅助函数
function pdo_fieldexists2($tablename, $fieldname) {
    try {
        $result = Db::query("SHOW COLUMNS FROM `ddwx_{$tablename}` LIKE '{$fieldname}'");
        return !empty($result);
    } catch (\Exception $e) {
        return false;
    }
}

$tableName = 'generation_scene_template';
$success = 0;
$skipped = 0;
$errors = [];

// ========== 分销设置字段 ==========
echo "1. 添加分销设置字段...\n";

// commissionset - 分销模式
if (!pdo_fieldexists2($tableName, 'commissionset')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `commissionset` tinyint(2) DEFAULT '0' COMMENT '分销模式：0按会员等级 1价格比例 2固定金额 3分销送积分 -1不参与分销' AFTER `lvprice_data`");
        echo "   - commissionset: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "commissionset: " . $e->getMessage();
    }
} else {
    echo "   - commissionset: 已存在，跳过\n";
    $skipped++;
}

// commissiondata1 - 按比例分销参数
if (!pdo_fieldexists2($tableName, 'commissiondata1')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `commissiondata1` text COMMENT '按比例分销参数（JSON序列化）' AFTER `commissionset`");
        echo "   - commissiondata1: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "commissiondata1: " . $e->getMessage();
    }
} else {
    echo "   - commissiondata1: 已存在，跳过\n";
    $skipped++;
}

// commissiondata2 - 按固定金额参数
if (!pdo_fieldexists2($tableName, 'commissiondata2')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `commissiondata2` text COMMENT '按固定金额参数（JSON序列化）' AFTER `commissiondata1`");
        echo "   - commissiondata2: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "commissiondata2: " . $e->getMessage();
    }
} else {
    echo "   - commissiondata2: 已存在，跳过\n";
    $skipped++;
}

// commissiondata3 - 分销送积分参数
if (!pdo_fieldexists2($tableName, 'commissiondata3')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `commissiondata3` text COMMENT '分销送积分参数（JSON序列化）' AFTER `commissiondata2`");
        echo "   - commissiondata3: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "commissiondata3: " . $e->getMessage();
    }
} else {
    echo "   - commissiondata3: 已存在，跳过\n";
    $skipped++;
}

// commissionset4 - 极差分销开关
if (!pdo_fieldexists2($tableName, 'commissionset4')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `commissionset4` tinyint(1) DEFAULT '0' COMMENT '是否开启极差分销：0关闭 1开启' AFTER `commissiondata3`");
        echo "   - commissionset4: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "commissionset4: " . $e->getMessage();
    }
} else {
    echo "   - commissionset4: 已存在，跳过\n";
    $skipped++;
}

// ========== 分红设置字段 ==========
echo "\n2. 添加分红设置字段...\n";

// fenhongset - 分红总开关
if (!pdo_fieldexists2($tableName, 'fenhongset')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `fenhongset` tinyint(1) DEFAULT '1' COMMENT '是否参与分红：0不参与 1参与' AFTER `commissionset4`");
        echo "   - fenhongset: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "fenhongset: " . $e->getMessage();
    }
} else {
    echo "   - fenhongset: 已存在，跳过\n";
    $skipped++;
}

// ========== 团队分红字段 ==========
echo "\n3. 添加团队分红字段...\n";

// teamfenhongset - 团队分红模式
if (!pdo_fieldexists2($tableName, 'teamfenhongset')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `teamfenhongset` tinyint(2) DEFAULT '0' COMMENT '团队分红模式：0按等级 1比例 2金额 3送积分 -1不参与' AFTER `fenhongset`");
        echo "   - teamfenhongset: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "teamfenhongset: " . $e->getMessage();
    }
} else {
    echo "   - teamfenhongset: 已存在，跳过\n";
    $skipped++;
}

// teamfenhongdata1 - 团队分红比例参数
if (!pdo_fieldexists2($tableName, 'teamfenhongdata1')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `teamfenhongdata1` text COMMENT '团队分红比例参数' AFTER `teamfenhongset`");
        echo "   - teamfenhongdata1: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "teamfenhongdata1: " . $e->getMessage();
    }
} else {
    echo "   - teamfenhongdata1: 已存在，跳过\n";
    $skipped++;
}

// teamfenhongdata2 - 团队分红金额参数
if (!pdo_fieldexists2($tableName, 'teamfenhongdata2')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `teamfenhongdata2` text COMMENT '团队分红金额参数' AFTER `teamfenhongdata1`");
        echo "   - teamfenhongdata2: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "teamfenhongdata2: " . $e->getMessage();
    }
} else {
    echo "   - teamfenhongdata2: 已存在，跳过\n";
    $skipped++;
}

// ========== 股东分红字段 ==========
echo "\n4. 添加股东分红字段...\n";

// gdfenhongset - 股东分红模式
if (!pdo_fieldexists2($tableName, 'gdfenhongset')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `gdfenhongset` tinyint(2) DEFAULT '0' COMMENT '股东分红模式：0按等级 1比例 2金额 3送积分 -1不参与' AFTER `teamfenhongdata2`");
        echo "   - gdfenhongset: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "gdfenhongset: " . $e->getMessage();
    }
} else {
    echo "   - gdfenhongset: 已存在，跳过\n";
    $skipped++;
}

// gdfenhongdata1 - 股东分红比例参数
if (!pdo_fieldexists2($tableName, 'gdfenhongdata1')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `gdfenhongdata1` text COMMENT '股东分红比例参数' AFTER `gdfenhongset`");
        echo "   - gdfenhongdata1: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "gdfenhongdata1: " . $e->getMessage();
    }
} else {
    echo "   - gdfenhongdata1: 已存在，跳过\n";
    $skipped++;
}

// gdfenhongdata2 - 股东分红金额参数
if (!pdo_fieldexists2($tableName, 'gdfenhongdata2')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `gdfenhongdata2` text COMMENT '股东分红金额参数' AFTER `gdfenhongdata1`");
        echo "   - gdfenhongdata2: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "gdfenhongdata2: " . $e->getMessage();
    }
} else {
    echo "   - gdfenhongdata2: 已存在，跳过\n";
    $skipped++;
}

// ========== 区域代理分红字段 ==========
echo "\n5. 添加区域代理分红字段...\n";

// areafenhongset - 区域分红模式
if (!pdo_fieldexists2($tableName, 'areafenhongset')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `areafenhongset` tinyint(2) DEFAULT '0' COMMENT '区域分红模式：0按等级 1比例 2金额 -1不参与' AFTER `gdfenhongdata2`");
        echo "   - areafenhongset: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "areafenhongset: " . $e->getMessage();
    }
} else {
    echo "   - areafenhongset: 已存在，跳过\n";
    $skipped++;
}

// areafenhongdata1 - 区域分红比例参数
if (!pdo_fieldexists2($tableName, 'areafenhongdata1')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `areafenhongdata1` text COMMENT '区域分红比例参数' AFTER `areafenhongset`");
        echo "   - areafenhongdata1: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "areafenhongdata1: " . $e->getMessage();
    }
} else {
    echo "   - areafenhongdata1: 已存在，跳过\n";
    $skipped++;
}

// areafenhongdata2 - 区域分红金额参数
if (!pdo_fieldexists2($tableName, 'areafenhongdata2')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `areafenhongdata2` text COMMENT '区域分红金额参数' AFTER `areafenhongdata1`");
        echo "   - areafenhongdata2: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "areafenhongdata2: " . $e->getMessage();
    }
} else {
    echo "   - areafenhongdata2: 已存在，跳过\n";
    $skipped++;
}

// ========== 积分抵扣字段 ==========
echo "\n6. 添加积分抵扣字段...\n";

// scoredkmaxset - 积分抵扣设置
if (!pdo_fieldexists2($tableName, 'scoredkmaxset')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `scoredkmaxset` tinyint(1) DEFAULT '0' COMMENT '积分抵扣设置：0按系统设置 1单独设置比例 2单独设置金额 -1不可抵扣' AFTER `areafenhongdata2`");
        echo "   - scoredkmaxset: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "scoredkmaxset: " . $e->getMessage();
    }
} else {
    echo "   - scoredkmaxset: 已存在，跳过\n";
    $skipped++;
}

// scoredkmaxval - 积分抵扣最大值
if (!pdo_fieldexists2($tableName, 'scoredkmaxval')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `scoredkmaxval` decimal(11,2) DEFAULT '0.00' COMMENT '积分抵扣最大值（比例/金额）' AFTER `scoredkmaxset`");
        echo "   - scoredkmaxval: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "scoredkmaxval: " . $e->getMessage();
    }
} else {
    echo "   - scoredkmaxval: 已存在，跳过\n";
    $skipped++;
}

// ========== 显示/购买条件字段 ==========
echo "\n7. 添加显示/购买条件字段...\n";

// showtj - 显示条件
if (!pdo_fieldexists2($tableName, 'showtj')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `showtj` varchar(255) DEFAULT '-1' COMMENT '显示条件：-1不限 其他为等级ID逗号分隔' AFTER `scoredkmaxval`");
        echo "   - showtj: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "showtj: " . $e->getMessage();
    }
} else {
    echo "   - showtj: 已存在，跳过\n";
    $skipped++;
}

// gettj - 购买条件
if (!pdo_fieldexists2($tableName, 'gettj')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `gettj` varchar(255) DEFAULT '-1' COMMENT '购买条件：-1不限 0关注用户 其他为等级ID' AFTER `showtj`");
        echo "   - gettj: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "gettj: " . $e->getMessage();
    }
} else {
    echo "   - gettj: 已存在，跳过\n";
    $skipped++;
}

// gettjurl - 不满足购买条件时的跳转链接
if (!pdo_fieldexists2($tableName, 'gettjurl')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `gettjurl` varchar(255) DEFAULT NULL COMMENT '不满足购买条件时的跳转链接' AFTER `gettj`");
        echo "   - gettjurl: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "gettjurl: " . $e->getMessage();
    }
} else {
    echo "   - gettjurl: 已存在，跳过\n";
    $skipped++;
}

// gettjtip - 不满足购买条件时的提示文案
if (!pdo_fieldexists2($tableName, 'gettjtip')) {
    try {
        Db::execute("ALTER TABLE `ddwx_{$tableName}` ADD COLUMN `gettjtip` varchar(255) DEFAULT NULL COMMENT '不满足购买条件时的提示文案' AFTER `gettjurl`");
        echo "   - gettjtip: 添加成功\n";
        $success++;
    } catch (\Exception $e) {
        $errors[] = "gettjtip: " . $e->getMessage();
    }
} else {
    echo "   - gettjtip: 已存在，跳过\n";
    $skipped++;
}

// ========== 迁移结果汇总 ==========
echo "\n=== 迁移完成 ===\n";
echo "成功添加: {$success} 个字段\n";
echo "已存在跳过: {$skipped} 个字段\n";

if (!empty($errors)) {
    echo "\n错误信息:\n";
    foreach ($errors as $error) {
        echo "   - {$error}\n";
    }
}

echo "\n结束时间: " . date('Y-m-d H:i:s') . "\n";

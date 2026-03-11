<?php
/**
 * 创作会员体系 + AI创作积分支付 数据库迁移脚本
 * 包含：
 * 1. ddwx_admin_set 新增AI积分支付字段
 * 2. ddwx_generation_order 新增积分支付字段
 * 3. ddwx_creative_member_plan 创作会员套餐表
 * 4. ddwx_creative_member_subscription 创作会员订阅表
 * 5. ddwx_creative_member_score_log 创作积分流水表
 */

$config = include __DIR__ . '/config.php';

$host = $config['hostname'];
$dbname = $config['database'];
$user = $config['username'];
$pass = $config['password'];
$port = $config['hostport'] ?: 3306;
$prefix = $config['prefix'];

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "数据库连接成功\n";
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage() . "\n");
}

$results = [];

// ========== 1. ddwx_admin_set 新增AI积分支付字段 ==========
$table = $prefix . 'admin_set';
$fieldsToAdd = [
    'ai_score_pay_status' => "ALTER TABLE `{$table}` ADD COLUMN `ai_score_pay_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'AI创作积分支付开关 0关闭 1开启'",
    'ai_score_exchange_rate' => "ALTER TABLE `{$table}` ADD COLUMN `ai_score_exchange_rate` decimal(10,4) NOT NULL DEFAULT 0.0100 COMMENT 'AI创作积分兑换比例(1积分=多少元)'",
    'ai_score_pay_mode' => "ALTER TABLE `{$table}` ADD COLUMN `ai_score_pay_mode` tinyint(1) NOT NULL DEFAULT 0 COMMENT '支付模式 0=全额积分 1=积分优先余额补足'",
];

foreach ($fieldsToAdd as $field => $sql) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$field}'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec($sql);
            $results[] = "[OK] {$table}.{$field} 字段添加成功";
        } else {
            $results[] = "[SKIP] {$table}.{$field} 字段已存在";
        }
    } catch (PDOException $e) {
        $results[] = "[ERROR] {$table}.{$field}: " . $e->getMessage();
    }
}

// ========== 2. ddwx_generation_order 新增积分支付字段 ==========
$table = $prefix . 'generation_order';
$fieldsToAdd = [
    'score_pay_amount' => "ALTER TABLE `{$table}` ADD COLUMN `score_pay_amount` int(11) NOT NULL DEFAULT 0 COMMENT '积分支付数量'",
    'score_pay_money' => "ALTER TABLE `{$table}` ADD COLUMN `score_pay_money` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '积分抵扣金额'",
    'pay_mode' => "ALTER TABLE `{$table}` ADD COLUMN `pay_mode` varchar(20) NOT NULL DEFAULT 'money' COMMENT '支付方式 money=余额/微信 score=积分 mixed=混合'",
];

foreach ($fieldsToAdd as $field => $sql) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$field}'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec($sql);
            $results[] = "[OK] {$table}.{$field} 字段添加成功";
        } else {
            $results[] = "[SKIP] {$table}.{$field} 字段已存在";
        }
    } catch (PDOException $e) {
        $results[] = "[ERROR] {$table}.{$field}: " . $e->getMessage();
    }
}

// ========== 3. ddwx_creative_member_plan 创作会员套餐表 ==========
$table = $prefix . 'creative_member_plan';
try {
    $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE `{$table}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `aid` int(11) NOT NULL DEFAULT 0 COMMENT '账户ID',
            `version_code` varchar(20) NOT NULL DEFAULT '' COMMENT '版本代码 basic/pro/master/flagship/premium',
            `version_name` varchar(50) NOT NULL DEFAULT '' COMMENT '版本名称',
            `purchase_mode` varchar(20) NOT NULL DEFAULT '' COMMENT '购买模式 yearly/monthly_auto/monthly',
            `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '售价',
            `original_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '原价',
            `discount_text` varchar(20) NOT NULL DEFAULT '' COMMENT '折扣文案',
            `monthly_score` int(11) NOT NULL DEFAULT 0 COMMENT '每月赠送积分',
            `daily_login_score` int(11) NOT NULL DEFAULT 20 COMMENT '每日登录赠送积分',
            `max_concurrency` int(11) NOT NULL DEFAULT 1 COMMENT '最大并发任务数',
            `cloud_storage_gb` int(11) NOT NULL DEFAULT 0 COMMENT '云端存储空间GB',
            `model_rights` text COMMENT '专属模型权益JSON',
            `features` text COMMENT '功能权益描述JSON',
            `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
            `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 0禁用 1启用',
            `createtime` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
            PRIMARY KEY (`id`),
            KEY `idx_aid` (`aid`),
            KEY `idx_version_mode` (`version_code`, `purchase_mode`),
            KEY `idx_status_sort` (`status`, `sort`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='创作会员套餐表';");
        $results[] = "[OK] {$table} 表创建成功";
    } else {
        $results[] = "[SKIP] {$table} 表已存在";
    }
} catch (PDOException $e) {
    $results[] = "[ERROR] {$table}: " . $e->getMessage();
}

// ========== 4. ddwx_creative_member_subscription 创作会员订阅表 ==========
$table = $prefix . 'creative_member_subscription';
try {
    $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE `{$table}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `aid` int(11) NOT NULL DEFAULT 0 COMMENT '账户ID',
            `mid` int(11) NOT NULL DEFAULT 0 COMMENT '会员ID',
            `plan_id` int(11) NOT NULL DEFAULT 0 COMMENT '套餐ID',
            `version_code` varchar(20) NOT NULL DEFAULT '' COMMENT '版本代码',
            `purchase_mode` varchar(20) NOT NULL DEFAULT '' COMMENT '购买模式',
            `start_time` int(11) NOT NULL DEFAULT 0 COMMENT '生效时间',
            `expire_time` int(11) NOT NULL DEFAULT 0 COMMENT '到期时间',
            `next_renew_time` int(11) NOT NULL DEFAULT 0 COMMENT '下次续费时间',
            `auto_renew` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否自动续费',
            `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0已过期 1生效中 2已取消',
            `remaining_score` int(11) NOT NULL DEFAULT 0 COMMENT '当月剩余积分',
            `total_score_used` int(11) NOT NULL DEFAULT 0 COMMENT '累计已使用积分',
            `orderid` int(11) NOT NULL DEFAULT 0 COMMENT '关联支付订单ID',
            `createtime` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
            PRIMARY KEY (`id`),
            KEY `idx_aid_mid` (`aid`, `mid`),
            KEY `idx_mid_status` (`mid`, `status`),
            KEY `idx_expire` (`expire_time`),
            KEY `idx_next_renew` (`next_renew_time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='创作会员订阅表';");
        $results[] = "[OK] {$table} 表创建成功";
    } else {
        $results[] = "[SKIP] {$table} 表已存在";
    }
} catch (PDOException $e) {
    $results[] = "[ERROR] {$table}: " . $e->getMessage();
}

// ========== 5. ddwx_creative_member_score_log 创作积分流水表 ==========
$table = $prefix . 'creative_member_score_log';
try {
    $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE `{$table}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `aid` int(11) NOT NULL DEFAULT 0 COMMENT '账户ID',
            `mid` int(11) NOT NULL DEFAULT 0 COMMENT '会员ID',
            `subscription_id` int(11) NOT NULL DEFAULT 0 COMMENT '订阅ID',
            `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '类型 1=月度发放 2=每日登录 3=消费扣除 4=退款返还',
            `amount` int(11) NOT NULL DEFAULT 0 COMMENT '变动数量(正增负减)',
            `balance` int(11) NOT NULL DEFAULT 0 COMMENT '变动后余额',
            `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注说明',
            `related_order_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联订单ID',
            `createtime` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
            PRIMARY KEY (`id`),
            KEY `idx_aid_mid` (`aid`, `mid`),
            KEY `idx_subscription` (`subscription_id`),
            KEY `idx_type` (`type`),
            KEY `idx_related_order` (`related_order_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='创作积分流水表';");
        $results[] = "[OK] {$table} 表创建成功";
    } else {
        $results[] = "[SKIP] {$table} 表已存在";
    }
} catch (PDOException $e) {
    $results[] = "[ERROR] {$table}: " . $e->getMessage();
}

// 输出结果
echo "\n========== 迁移结果 ==========\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "==============================\n";
echo "迁移完成！\n";

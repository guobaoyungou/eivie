<?php
/**
 * 系统API Key池重构 - 数据库迁移脚本
 * 为ddwx_system_api_key表添加多Key池所需的新字段和索引
 */
define('ROOT_PATH', __DIR__ . '/');
$config = include(ROOT_PATH . 'config.php');

$host = $config['hostname'];
$dbname = $config['database'];
$user = $config['username'];
$pass = $config['password'];
$port = $config['hostport'] ?? 3306;
$prefix = $config['prefix'] ?? 'ddwx_';
$charset = 'utf8mb4';

echo "=== 系统API Key池重构 - 数据库迁移 ===\n\n";

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "[OK] 数据库连接成功\n";
} catch (PDOException $e) {
    echo "[ERROR] 数据库连接失败: " . $e->getMessage() . "\n";
    exit(1);
}

$tableName = $prefix . 'system_api_key';

// 检查表是否存在
$stmt = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
if ($stmt->rowCount() == 0) {
    echo "[ERROR] 表 {$tableName} 不存在\n";
    exit(1);
}
echo "[OK] 表 {$tableName} 存在\n";

// 获取当前表字段
$stmt = $pdo->query("DESCRIBE `{$tableName}`");
$existingColumns = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $existingColumns[] = $row['Field'];
}

echo "\n--- 添加新字段 ---\n";

$newColumns = [
    'max_concurrency' => [
        'sql' => "ADD COLUMN `max_concurrency` int(11) unsigned NOT NULL DEFAULT 5 COMMENT '单Key最大并发数，默认5' AFTER `extra_config`",
        'after' => 'extra_config'
    ],
    'current_concurrency' => [
        'sql' => "ADD COLUMN `current_concurrency` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '当前并发占用数，默认0' AFTER `max_concurrency`",
        'after' => 'max_concurrency'
    ],
    'weight' => [
        'sql' => "ADD COLUMN `weight` int(11) unsigned NOT NULL DEFAULT 100 COMMENT '负载均衡权重1-100，默认100' AFTER `current_concurrency`",
        'after' => 'current_concurrency'
    ],
    'total_calls' => [
        'sql' => "ADD COLUMN `total_calls` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '累计调用次数' AFTER `weight`",
        'after' => 'weight'
    ],
    'fail_calls' => [
        'sql' => "ADD COLUMN `fail_calls` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '累计失败次数' AFTER `total_calls`",
        'after' => 'total_calls'
    ],
    'last_used_time' => [
        'sql' => "ADD COLUMN `last_used_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '最后使用时间戳' AFTER `fail_calls`",
        'after' => 'fail_calls'
    ],
    'last_error_time' => [
        'sql' => "ADD COLUMN `last_error_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '最后出错时间戳' AFTER `last_used_time`",
        'after' => 'last_used_time'
    ],
    'last_error_msg' => [
        'sql' => "ADD COLUMN `last_error_msg` varchar(500) NOT NULL DEFAULT '' COMMENT '最后错误信息' AFTER `last_error_time`",
        'after' => 'last_error_time'
    ],
];

foreach ($newColumns as $colName => $colInfo) {
    if (in_array($colName, $existingColumns)) {
        echo "[SKIP] 字段 {$colName} 已存在\n";
    } else {
        try {
            $pdo->exec("ALTER TABLE `{$tableName}` {$colInfo['sql']}");
            echo "[OK] 添加字段 {$colName} 成功\n";
        } catch (PDOException $e) {
            echo "[ERROR] 添加字段 {$colName} 失败: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n--- 修改索引 ---\n";

// 获取当前索引
$stmt = $pdo->query("SHOW INDEX FROM `{$tableName}`");
$existingIndexes = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $existingIndexes[$row['Key_name']] = $row;
}

// 删除旧的单供应商唯一约束
if (isset($existingIndexes['uk_provider_id'])) {
    try {
        $pdo->exec("ALTER TABLE `{$tableName}` DROP INDEX `uk_provider_id`");
        echo "[OK] 删除旧索引 uk_provider_id 成功（移除单供应商唯一约束）\n";
    } catch (PDOException $e) {
        echo "[ERROR] 删除索引 uk_provider_id 失败: " . $e->getMessage() . "\n";
    }
} else {
    echo "[SKIP] 索引 uk_provider_id 不存在\n";
}

// 添加防重复Key唯一索引
if (!isset($existingIndexes['uk_api_key'])) {
    try {
        $pdo->exec("ALTER TABLE `{$tableName}` ADD UNIQUE KEY `uk_api_key` (`api_key`(100)) COMMENT '防止重复添加相同Key'");
        echo "[OK] 添加索引 uk_api_key 成功\n";
    } catch (PDOException $e) {
        echo "[ERROR] 添加索引 uk_api_key 失败: " . $e->getMessage() . "\n";
    }
} else {
    echo "[SKIP] 索引 uk_api_key 已存在\n";
}

// 添加供应商+状态复合索引
if (!isset($existingIndexes['idx_provider_active'])) {
    try {
        $pdo->exec("ALTER TABLE `{$tableName}` ADD KEY `idx_provider_active` (`provider_code`, `is_active`) COMMENT '按供应商查询启用Key'");
        echo "[OK] 添加索引 idx_provider_active 成功\n";
    } catch (PDOException $e) {
        echo "[ERROR] 添加索引 idx_provider_active 失败: " . $e->getMessage() . "\n";
    }
} else {
    echo "[SKIP] 索引 idx_provider_active 已存在\n";
}

echo "\n--- 验证最终表结构 ---\n";
$stmt = $pdo->query("DESCRIBE `{$tableName}`");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("  %-25s %-25s %s\n", $row['Field'], $row['Type'], $row['Default'] ?? 'NULL');
}

echo "\n=== 迁移完成 ===\n";

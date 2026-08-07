<?php
/**
 * PC支付重构 - 数据库迁移脚本
 * 为 ddwx_admin_setapp_pc 表新增 ali_return_url 字段
 * 
 * 运行方式: php migrate_pc_pay_refactor.php
 */

define('ROOT_PATH', __DIR__ . '/');
$config = include(ROOT_PATH . 'config.php');

$host = $config['hostname'];
$db   = $config['database'];
$user = $config['username'];
$pass = $config['password'];
$port = $config['hostport'] ?: '3306';
$prefix = $config['prefix'] ?: 'ddwx_';

echo "=== PC支付重构 - 数据库迁移 ===\n\n";

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $table = $prefix . 'admin_setapp_pc';

    // 检查 ali_return_url 字段是否已存在
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE 'ali_return_url'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `ali_return_url` varchar(255) DEFAULT NULL COMMENT '支付宝同步回跳地址' AFTER `ali_publickey`");
        echo "[OK] 已添加 ali_return_url 字段\n";
    } else {
        echo "[SKIP] ali_return_url 字段已存在\n";
    }

    echo "\n=== 迁移完成 ===\n";
} catch (\Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}

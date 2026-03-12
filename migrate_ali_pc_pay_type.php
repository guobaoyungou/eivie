<?php
/**
 * 数据库迁移脚本：ddwx_admin_setapp_pc 表新增 ali_pc_pay_type 字段
 * 用途：支付宝PC端支付模式选择
 *   0 = 当面付（预创建二维码），需开通「当面付」产品
 *   1 = 电脑网站支付（跳转支付宝收银台），需开通「电脑网站支付」产品
 *   2 = 手机网站支付（跳转支付宝H5收银台），需开通「手机网站支付」产品（最常见）
 */

define('ROOT_PATH', __DIR__ . '/');
$config = include(ROOT_PATH . 'config.php');

$host = $config['hostname'];
$db   = $config['database'];
$user = $config['username'];
$pass = $config['password'];
$port = $config['hostport'] ?: '3306';
$prefix = $config['prefix'] ?: 'ddwx_';

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $table = $prefix . 'admin_setapp_pc';

    // 检查字段是否已存在
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE 'ali_pc_pay_type'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `ali_pc_pay_type` tinyint(1) NOT NULL DEFAULT 2 COMMENT '支付宝PC支付模式 0:当面付 1:电脑网站支付 2:手机网站支付' AFTER `ali_return_url`");
        echo "[OK] ali_pc_pay_type 字段已添加，默认值为 2（手机网站支付）\n";
    } else {
        echo "[SKIP] ali_pc_pay_type 字段已存在\n";
    }
} catch (PDOException $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}

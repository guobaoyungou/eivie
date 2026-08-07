<?php
/**
 * 迁移脚本：创建 ddwx_admin_setapp_pc 表（PC端支付配置）
 */

// 读取数据库配置
$config = include __DIR__ . '/config.php';

$host = $config['hostname'];
$db   = $config['database'];
$user = $config['username'];
$pass = $config['password'];
$port = $config['hostport'] ?? 3306;
$prefix = $config['prefix'] ?? 'ddwx_';

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tableName = $prefix . 'admin_setapp_pc';

    // 检查表是否已存在
    $stmt = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
    if ($stmt->rowCount() > 0) {
        echo "表 {$tableName} 已存在，跳过创建。\n";
    } else {
        $sql = "CREATE TABLE `{$tableName}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `aid` int(11) DEFAULT NULL COMMENT '账户ID',
            `wxpay` tinyint(1) NOT NULL DEFAULT 0 COMMENT '微信支付开关 0关闭 1开启',
            `wxpay_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '微信支付模式 0普通模式 1服务商模式',
            `wxpay_mchid` varchar(100) DEFAULT NULL COMMENT '微信支付商户号',
            `wxpay_mchkey` varchar(100) DEFAULT NULL COMMENT '微信支付APIv2密钥',
            `wxpay_sub_mchid` varchar(100) DEFAULT NULL COMMENT '子商户号(服务商模式)',
            `wxpay_appid` varchar(100) DEFAULT NULL COMMENT '微信支付关联AppID',
            `wxpay_apiclient_cert` varchar(100) DEFAULT NULL COMMENT 'PEM证书路径',
            `wxpay_apiclient_key` varchar(100) DEFAULT NULL COMMENT '证书密钥路径',
            `wxpay_serial_no` varchar(100) DEFAULT NULL COMMENT '商户证书序列号',
            `wxpay_mchkey_v3` varchar(255) DEFAULT NULL COMMENT 'APIv3密钥',
            `wxpay_wechatpay_pem` varchar(255) DEFAULT NULL COMMENT '微信支付平台证书路径',
            `wxpay_plate_serialno` varchar(100) DEFAULT NULL COMMENT '平台证书序列号',
            `sign_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '签名验签方式 0平台证书 1微信支付公钥',
            `public_key_id` varchar(100) DEFAULT NULL COMMENT '微信支付公钥ID',
            `public_key_pem` varchar(255) DEFAULT NULL COMMENT '微信支付公钥文件路径',
            `alipay` tinyint(1) NOT NULL DEFAULT 0 COMMENT '支付宝支付开关 0关闭 1开启',
            `ali_appid` varchar(100) DEFAULT NULL COMMENT '支付宝应用APPID',
            `ali_privatekey` text DEFAULT NULL COMMENT '支付宝应用私钥',
            `ali_publickey` text DEFAULT NULL COMMENT '支付宝公钥',
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_aid` (`aid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='PC端支付配置表';";

        $pdo->exec($sql);
        echo "表 {$tableName} 创建成功。\n";
    }

    echo "迁移完成！\n";
} catch (PDOException $e) {
    echo "数据库错误: " . $e->getMessage() . "\n";
    exit(1);
}

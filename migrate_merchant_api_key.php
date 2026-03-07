<?php
/**
 * 商家API Key配置表迁移脚本
 * 执行: php migrate_merchant_api_key.php
 */
define('ROOT_PATH', __DIR__ . '/');
$config = include(ROOT_PATH . 'config.php');

$host = $config['hostname'];
$dbname = $config['database'];
$user = $config['username'];
$pass = $config['password'];
$port = $config['hostport'] ?? 3306;
$prefix = $config['prefix'] ?? 'ddwx_';

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "数据库连接成功\n";

    // 读取SQL文件
    $sqlFile = ROOT_PATH . 'database/migrations/merchant_api_key_tables.sql';
    $sql = file_get_contents($sqlFile);

    // 替换表前缀
    $sql = str_replace('ddwx_', $prefix, $sql);

    // 分割SQL语句执行
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if (empty($stmt) || strpos($stmt, '--') === 0) continue;
        try {
            $pdo->exec($stmt);
            // 从语句中提取表名
            if (preg_match('/CREATE TABLE.*?`([^`]+)`/i', $stmt, $m)) {
                echo "✓ 表 {$m[1]} 创建成功\n";
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                if (preg_match('/CREATE TABLE.*?`([^`]+)`/i', $stmt, $m)) {
                    echo "○ 表 {$m[1]} 已存在，跳过\n";
                }
            } else {
                echo "✗ 执行失败: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\n迁移完成！\n";
} catch (PDOException $e) {
    echo "数据库连接失败: " . $e->getMessage() . "\n";
    exit(1);
}

<?php
/**
 * AI创作积分计量单位自定义 数据库迁移脚本
 * 为 admin_set 表新增 ai_score_unit_name 字段
 * 
 * 执行方式: php migrate_ai_score_unit_name.php
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

// ========== ddwx_admin_set 新增 ai_score_unit_name 字段 ==========
$table = $prefix . 'admin_set';
$fieldsToAdd = [
    'ai_score_unit_name' => "ALTER TABLE `{$table}` ADD COLUMN `ai_score_unit_name` varchar(20) NOT NULL DEFAULT '词元' COMMENT 'AI创作积分计量单位展示名称'",
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

// 输出结果
echo "\n========== 迁移结果 ==========\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "==============================\n";
echo "迁移完成！\n";

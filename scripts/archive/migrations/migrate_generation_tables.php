<?php
/**
 * 执行生成模块数据库迁移
 */
define('ROOT_PATH', __DIR__ . '/');
$config = include(ROOT_PATH . 'config.php');

try {
    $pdo = new PDO(
        "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // 读取SQL文件
    $sqlFile = ROOT_PATH . 'database/migrations/generation_module_tables.sql';
    $sql = file_get_contents($sqlFile);
    
    // 分割多条SQL语句
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && strpos($stmt, '--') !== 0;
        }
    );
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', trim($statement))) {
            $pdo->exec($statement);
            echo "执行成功: " . substr(trim($statement), 0, 50) . "...\n";
        }
    }
    
    echo "\n数据库迁移完成！\n";
    
} catch (PDOException $e) {
    echo "数据库错误: " . $e->getMessage() . "\n";
    exit(1);
}

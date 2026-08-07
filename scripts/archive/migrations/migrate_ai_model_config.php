<?php
/**
 * AI模型配置功能数据库迁移脚本
 */

// 引入项目配置
define('ROOT_PATH', __DIR__ . '/');
require_once ROOT_PATH . 'vendor/autoload.php';

$config = include(ROOT_PATH . 'config.php');

// 连接数据库
$mysqli = new mysqli(
    $config['hostname'],
    $config['username'],
    $config['password'],
    $config['database'],
    $config['hostport']
);

if ($mysqli->connect_error) {
    die("数据库连接失败: " . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');

echo "开始执行数据库迁移...\n\n";

// 1. 执行建表脚本
echo "=== 创建数据表 ===\n";
$sql_tables = file_get_contents(ROOT_PATH . 'database/migrations/ai_model_config_tables.sql');
$statements = explode(';', $sql_tables);

foreach ($statements as $statement) {
    $statement = trim($statement);
    if (empty($statement)) continue;
    
    if ($mysqli->query($statement)) {
        // 提取表名
        if (preg_match('/CREATE TABLE.*`([^`]+)`/i', $statement, $matches)) {
            echo "✓ 创建表: {$matches[1]}\n";
        }
    } else {
        echo "✗ 执行失败: " . $mysqli->error . "\n";
    }
}

echo "\n=== 插入初始化数据 ===\n";
// 2. 执行初始化数据脚本
$sql_init = file_get_contents(ROOT_PATH . 'database/migrations/ai_model_config_init_data.sql');
$statements = explode(';', $sql_init);

foreach ($statements as $statement) {
    $statement = trim($statement);
    if (empty($statement)) continue;
    
    if ($mysqli->query($statement)) {
        if (preg_match('/INSERT INTO `([^`]+)`/i', $statement, $matches)) {
            echo "✓ 插入数据到: {$matches[1]}, 影响行数: " . $mysqli->affected_rows . "\n";
        }
    } else {
        echo "✗ 执行失败: " . $mysqli->error . "\n";
    }
}

echo "\n=== 验证数据 ===\n";
// 3. 验证数据
$tables = [
    'ddwx_ai_model_instance' => '模型实例',
    'ddwx_ai_model_parameter' => '参数定义',
    'ddwx_ai_model_response' => '响应定义',
    'ddwx_ai_model_pricing' => '定价配置'
];

foreach ($tables as $table => $name) {
    $result = $mysqli->query("SELECT COUNT(*) as cnt FROM `$table`");
    $row = $result->fetch_assoc();
    echo "✓ {$name}表({$table}): {$row['cnt']} 条记录\n";
}

echo "\n迁移完成！\n";

$mysqli->close();

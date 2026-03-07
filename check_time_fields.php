<?php
define('ROOT_PATH', __DIR__ . '/');
require_once ROOT_PATH . 'vendor/autoload.php';

$config = include(ROOT_PATH . 'config.php');

$mysqli = new mysqli(
    $config['hostname'],
    $config['username'],
    $config['password'],
    $config['database'],
    $config['hostport']
);

if ($mysqli->connect_error) {
    die("连接失败: " . $mysqli->connect_error);
}

echo "检查模型配置相关表的时间字段:\n\n";

$tables = [
    'ddwx_ai_model_instance',
    'ddwx_ai_model_parameter',
    'ddwx_ai_model_pricing',
    'ddwx_ai_model_response'
];

foreach ($tables as $table) {
    echo "=== {$table} ===\n";
    $result = $mysqli->query("SHOW COLUMNS FROM {$table} WHERE Field IN ('create_time', 'update_time')");
    while ($row = $result->fetch_assoc()) {
        echo "字段: {$row['Field']}\n";
        echo "  类型: {$row['Type']}\n";
        echo "  允许NULL: {$row['Null']}\n";
        echo "  默认值: " . ($row['Default'] ?? 'NULL') . "\n";
        echo "\n";
    }
}

$mysqli->close();

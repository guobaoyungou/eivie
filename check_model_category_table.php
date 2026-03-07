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

// 检查表是否存在
$result = $mysqli->query("SHOW TABLES LIKE 'ddwx_ai_model_category'");

if ($result->num_rows > 0) {
    echo "✓ ddwx_ai_model_category 表已存在\n\n";
    
    // 查询数据
    $count = $mysqli->query("SELECT COUNT(*) as cnt FROM ddwx_ai_model_category")->fetch_assoc()['cnt'];
    echo "数据记录数: {$count}\n\n";
    
    if ($count > 0) {
        echo "一级分类:\n";
        $result = $mysqli->query("SELECT id, code, name, icon FROM ddwx_ai_model_category WHERE level=1 ORDER BY sort DESC LIMIT 5");
        while ($row = $result->fetch_assoc()) {
            echo "  - [{$row['id']}] {$row['code']}: {$row['icon']} {$row['name']}\n";
        }
    }
} else {
    echo "✗ ddwx_ai_model_category 表不存在\n";
    echo "提示: 需要运行AI模型三级分类迁移脚本\n";
}

$mysqli->close();

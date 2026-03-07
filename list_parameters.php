<?php
$host = 'localhost';
$dbname = 'guobaoyungou_cn';
$username = 'guobaoyungou_cn';
$password = '5ArfhRr9xzyScrF5';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("
        SELECT id, param_name, param_label, param_type, is_required, default_value, description
        FROM ddwx_ai_model_parameter 
        WHERE model_id = 1 
        ORDER BY sort ASC, id ASC
    ");
    $stmt->execute();
    $params = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== 通义千问图像编辑增强版 - 参数列表 ===\n\n";
    echo "共 " . count($params) . " 个参数\n\n";
    
    foreach ($params as $idx => $param) {
        echo ($idx + 1) . ". {$param['param_label']} ({$param['param_name']})\n";
        echo "   类型: {$param['param_type']}\n";
        echo "   必填: " . ($param['is_required'] ? '是' : '否') . "\n";
        echo "   默认值: " . ($param['default_value'] ?: '无') . "\n";
        echo "   说明: " . mb_substr($param['description'], 0, 100) . "...\n\n";
    }
    
} catch (PDOException $e) {
    echo "错误：" . $e->getMessage() . "\n";
}

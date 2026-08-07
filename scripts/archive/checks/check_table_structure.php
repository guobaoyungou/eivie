<?php
// check_table_structure.php - 检查表结构

$host = '127.0.0.1';
$dbname = 'guobaoyungou_cn';
$username = 'guobaoyungou_cn';
$password = '5ArfhRr9xzyScrF5';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== ddwx_ai_travel_photo_portrait 表结构 ===\n";
    $stmt = $pdo->query("DESCRIBE ddwx_ai_travel_photo_portrait");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "列名 | 类型 | 允许NULL | 键 | 默认值 | 额外\n";
    echo str_repeat("-", 80) . "\n";
    foreach($columns as $col) {
        echo "{$col['Field']} | {$col['Type']} | {$col['Null']} | {$col['Key']} | {$col['Default']} | {$col['Extra']}\n";
    }
    
    echo "\n=== ddwx_generation_scene_template 表结构 ===\n";
    $stmt = $pdo->query("DESCRIBE ddwx_generation_scene_template");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "列名 | 类型 | 允许NULL | 键 | 默认值 | 额外\n";
    echo str_repeat("-", 80) . "\n";
    foreach($columns as $col) {
        echo "{$col['Field']} | {$col['Type']} | {$col['Null']} | {$col['Key']} | {$col['Default']} | {$col['Extra']}\n";
    }
    
    echo "\n=== 检查portrait表样本数据 ===\n";
    $stmt = $pdo->query("SELECT * FROM ddwx_ai_travel_photo_portrait LIMIT 3");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($rows as $row) {
        echo "\nID: {$row['id']}\n";
        foreach($row as $key => $value) {
            if ($value !== null && $value !== '') {
                $display = strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value;
                echo "  $key: $display\n";
            }
        }
    }
    
} catch (PDOException $e) {
    echo "数据库错误: " . $e->getMessage() . "\n";
}

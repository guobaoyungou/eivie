<?php
// check_age_standalone.php - 独立检查年龄字段格式（不依赖ThinkPHP）

$host = '127.0.0.1';
$dbname = 'guobaoyungou_cn';
$username = 'guobaoyungou_cn';
$password = '5ArfhRr9xzyScrF5';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Portrait表年龄字段检查 ===\n";
    $stmt = $pdo->query("SELECT id, age_tag, age_group, gender_tag, analysis_result FROM ddwx_ai_travel_photo_portrait LIMIT 20");
    $portraits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "样本数据（前20条）:\n";
    foreach($portraits as $p) {
        echo "ID:{$p['id']} - age_tag: " . ($p['age_tag'] ?: 'NULL') . 
             " - age_group: " . ($p['age_group'] ?: 'NULL') . 
             " - gender_tag: " . ($p['gender_tag'] ?: 'NULL') . "\n";
        
        // 解析analysis_result查看原始数据
        if ($p['analysis_result']) {
            $result = json_decode($p['analysis_result'], true);
            if ($result && isset($result['age'])) {
                echo "  原始age值: {$result['age']}\n";
            }
        }
    }
    
    echo "\n=== Scene Template表年龄字段检查 ===\n";
    $stmt = $pdo->query("SELECT gst.id, gst.age_tag, gst.face_count, gst.is_multi_template, gsp.name as scene_name 
                          FROM ddwx_generation_scene_template gst 
                          LEFT JOIN ddwx_ai_travel_photo_scene gsp ON gsp.id = gst.scene_id 
                          LIMIT 20");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "样本数据（前20条）:\n";
    foreach($templates as $t) {
        echo "ID:{$t['id']} - age_tag: " . ($t['age_tag'] ?: 'NULL') . 
             " - scene: " . ($t['scene_name'] ?: 'NULL') . "\n";
    }
    
    echo "\n=== InsightFace API返回的年龄格式检查 ===\n";
    // 查看ImageAnalysisService如何处理年龄
    echo "需要检查ImageAnalysisService.php中的年龄处理逻辑\n";
    
} catch (PDOException $e) {
    echo "数据库错误: " . $e->getMessage() . "\n";
}

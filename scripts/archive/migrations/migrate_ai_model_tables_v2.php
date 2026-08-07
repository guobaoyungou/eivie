<?php
/**
 * AI模型管理系统 - 数据库迁移脚本（改进版）
 * 执行方式: php migrate_ai_model_tables_v2.php
 */

// 引入配置
$config = include(__DIR__ . '/config.php');

// 数据库连接信息
$hostname = $config['hostname'];
$database = $config['database'];
$username = $config['username'];
$password = $config['password'];
$hostport = $config['hostport'];

echo "========================================\n";
echo "AI模型管理系统 - 数据库迁移 V2\n";
echo "========================================\n";
echo "数据库: {$database}\n";
echo "========================================\n\n";

try {
    // 创建mysqli连接
    $mysqli = new mysqli($hostname, $username, $password, $database, $hostport);
    
    if ($mysqli->connect_error) {
        throw new Exception("数据库连接失败: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8mb4");
    echo "✓ 数据库连接成功\n\n";
    
    // 读取SQL文件
    $sqlFile = __DIR__ . '/database/migrations/ai_model_management_tables.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("迁移文件不存在: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "✓ 读取迁移文件成功\n\n";
    
    echo "开始执行迁移...\n";
    echo "----------------------------------------\n";
    
    // 执行multi_query
    if ($mysqli->multi_query($sql)) {
        do {
            // 获取结果
            if ($result = $mysqli->store_result()) {
                $result->free();
            }
            
            // 检查是否有更多结果
            if ($mysqli->more_results()) {
                echo ".";
            }
        } while ($mysqli->next_result());
        
        echo "\n✓ SQL执行完成\n";
    } else {
        throw new Exception("SQL执行错误: " . $mysqli->error);
    }
    
    echo "----------------------------------------\n\n";
    
    // 验证表是否创建成功
    echo "验证表结构...\n";
    echo "----------------------------------------\n";
    
    $tables = [
        'ddwx_ai_model_category' => '模型分类表',
        'ddwx_ai_travel_photo_model' => '模型配置表',
        'ddwx_ai_model_usage_log' => '使用记录表'
    ];
    
    foreach ($tables as $table => $desc) {
        $result = $mysqli->query("SHOW TABLES LIKE '{$table}'");
        if ($result && $result->num_rows > 0) {
            echo "✓ {$desc} ({$table}) 存在\n";
            
            // 检查记录数
            if ($table === 'ddwx_ai_model_category') {
                $countResult = $mysqli->query("SELECT COUNT(*) as cnt FROM {$table} WHERE is_system=1");
                if ($countResult) {
                    $row = $countResult->fetch_assoc();
                    echo "  系统预置分类数量: {$row['cnt']}\n";
                }
            }
        } else {
            echo "✗ {$desc} ({$table}) 不存在\n";
        }
    }
    
    // 检查字段是否添加
    echo "\n验证字段扩展...\n";
    echo "----------------------------------------\n";
    
    $checkFields = ['category_code', 'mdid', 'max_concurrent', 'image_price'];
    $result = $mysqli->query("SHOW COLUMNS FROM ddwx_ai_travel_photo_model");
    $fields = [];
    while ($row = $result->fetch_assoc()) {
        $fields[] = $row['Field'];
    }
    
    foreach ($checkFields as $field) {
        if (in_array($field, $fields)) {
            echo "✓ 字段 {$field} 已添加\n";
        } else {
            echo "✗ 字段 {$field} 未添加\n";
        }
    }
    
    echo "----------------------------------------\n";
    echo "\n✅ 数据库迁移全部完成！\n\n";
    echo "下一步操作：\n";
    echo "1. 刷新浏览器页面\n";
    echo "2. 访问后台菜单：AI旅拍 > 模型设置\n";
    echo "3. 查看模型分类列表\n";
    echo "4. 添加API配置\n\n";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "\n❌ 迁移失败：" . $e->getMessage() . "\n\n";
    exit(1);
}

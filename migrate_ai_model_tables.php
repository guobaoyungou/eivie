<?php
/**
 * AI模型管理系统 - 数据库迁移脚本
 * 执行方式: php migrate_ai_model_tables.php
 */

// 引入配置
$config = include(__DIR__ . '/config.php');

// 数据库连接信息
$hostname = $config['hostname'];
$database = $config['database'];
$username = $config['username'];
$password = $config['password'];
$hostport = $config['hostport'];
$prefix = $config['prefix'];

echo "========================================\n";
echo "AI模型管理系统 - 数据库迁移\n";
echo "========================================\n";
echo "数据库: {$database}\n";
echo "表前缀: {$prefix}\n";
echo "========================================\n\n";

try {
    // 创建PDO连接
    $dsn = "mysql:host={$hostname};port={$hostport};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ 数据库连接成功\n\n";
    
    // 读取SQL文件
    $sqlFile = __DIR__ . '/database/migrations/ai_model_management_tables.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("迁移文件不存在: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "✓ 读取迁移文件成功\n\n";
    
    // 分割SQL语句
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^\s*--/', $stmt) && 
                   !preg_match('/^\s*\/\*/', $stmt);
        }
    );
    
    echo "开始执行迁移...\n";
    echo "----------------------------------------\n";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        try {
            // 跳过注释行
            if (empty(trim($statement))) {
                continue;
            }
            
            // 执行SQL
            $pdo->exec($statement);
            
            // 提取表名或操作类型
            if (preg_match('/CREATE TABLE.*?`(\w+)`/i', $statement, $matches)) {
                echo "✓ 创建表: {$matches[1]}\n";
            } elseif (preg_match('/ALTER TABLE.*?`(\w+)`/i', $statement, $matches)) {
                echo "✓ 修改表: {$matches[1]}\n";
            } elseif (preg_match('/INSERT INTO.*?`(\w+)`/i', $statement, $matches)) {
                echo "✓ 插入数据: {$matches[1]}\n";
            } elseif (preg_match('/UPDATE.*?`(\w+)`/i', $statement, $matches)) {
                echo "✓ 更新数据: {$matches[1]}\n";
            } else {
                echo "✓ 执行SQL成功\n";
            }
            
            $successCount++;
            
        } catch (PDOException $e) {
            // 检查是否是"已存在"类型的错误，这类错误可以忽略
            if (strpos($e->getMessage(), 'already exists') !== false ||
                strpos($e->getMessage(), 'Duplicate column') !== false ||
                strpos($e->getMessage(), 'Duplicate key') !== false) {
                echo "⚠ 跳过（已存在）\n";
            } else {
                echo "✗ 错误: " . $e->getMessage() . "\n";
                $errorCount++;
            }
        }
    }
    
    echo "----------------------------------------\n";
    echo "迁移完成！\n";
    echo "成功: {$successCount} 条\n";
    echo "错误: {$errorCount} 条\n\n";
    
    // 验证表是否创建成功
    echo "验证表结构...\n";
    echo "----------------------------------------\n";
    
    $tables = [
        'ddwx_ai_model_category' => '模型分类表',
        'ddwx_ai_travel_photo_model' => '模型配置表',
        'ddwx_ai_model_usage_log' => '使用记录表'
    ];
    
    foreach ($tables as $table => $desc) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "✓ {$desc} ({$table}) 存在\n";
            
            // 检查记录数
            if ($table === 'ddwx_ai_model_category') {
                $countStmt = $pdo->query("SELECT COUNT(*) as cnt FROM {$table} WHERE is_system=1");
                $count = $countStmt->fetch(PDO::FETCH_ASSOC)['cnt'];
                echo "  系统预置分类数量: {$count}\n";
            }
        } else {
            echo "✗ {$desc} ({$table}) 不存在\n";
        }
    }
    
    echo "----------------------------------------\n";
    echo "\n✅ 数据库迁移全部完成！\n\n";
    echo "下一步操作：\n";
    echo "1. 访问后台菜单：AI旅拍 > 模型设置\n";
    echo "2. 查看模型分类列表\n";
    echo "3. 添加API配置\n";
    echo "4. 测试功能\n\n";
    
} catch (Exception $e) {
    echo "\n❌ 迁移失败：" . $e->getMessage() . "\n\n";
    exit(1);
}

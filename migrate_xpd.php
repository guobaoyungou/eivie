<?php
// 执行数据库迁移
$config = include(__DIR__ . '/config.php');

try {
    $pdo = new PDO(
        "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 检查字段是否已存在
    $checkSql = "SELECT COUNT(*) as count FROM information_schema.COLUMNS 
                 WHERE TABLE_SCHEMA = '{$config['database']}' 
                 AND TABLE_NAME = '{$config['prefix']}mendian' 
                 AND COLUMN_NAME = 'xpd_template'";
    
    $stmt = $pdo->query($checkSql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        // 字段不存在，执行添加
        $sql = "ALTER TABLE `{$config['prefix']}mendian` 
                ADD COLUMN `xpd_template` VARCHAR(50) DEFAULT 'template_1' COMMENT '选片端展示模板' AFTER `status`";
        
        $pdo->exec($sql);
        echo "字段添加成功！\n";
    } else {
        echo "字段已存在，无需添加。\n";
    }
    
    echo "数据库迁移完成！\n";
    
} catch (PDOException $e) {
    echo "数据库操作失败: " . $e->getMessage() . "\n";
    exit(1);
}

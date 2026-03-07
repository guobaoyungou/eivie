<?php
// 执行选片端轮播时间配置数据库迁移
$config = include(__DIR__ . '/config.php');

try {
    $pdo = new PDO(
        "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "开始执行数据库迁移...\n\n";
    
    // 检查 xpd_image_duration 字段是否已存在
    $checkSql1 = "SELECT COUNT(*) as count FROM information_schema.COLUMNS 
                  WHERE TABLE_SCHEMA = '{$config['database']}' 
                  AND TABLE_NAME = '{$config['prefix']}mendian' 
                  AND COLUMN_NAME = 'xpd_image_duration'";
    
    $stmt = $pdo->query($checkSql1);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        echo "添加字段: xpd_image_duration...\n";
        $sql1 = "ALTER TABLE `{$config['prefix']}mendian` 
                 ADD COLUMN `xpd_image_duration` INT UNSIGNED DEFAULT 1000 
                 COMMENT '单张图片展示时长(毫秒)' AFTER `xpd_template`";
        $pdo->exec($sql1);
        echo "✓ xpd_image_duration 字段添加成功\n\n";
    } else {
        echo "✓ xpd_image_duration 字段已存在\n\n";
    }
    
    // 检查 xpd_group_duration 字段是否已存在
    $checkSql2 = "SELECT COUNT(*) as count FROM information_schema.COLUMNS 
                  WHERE TABLE_SCHEMA = '{$config['database']}' 
                  AND TABLE_NAME = '{$config['prefix']}mendian' 
                  AND COLUMN_NAME = 'xpd_group_duration'";
    
    $stmt = $pdo->query($checkSql2);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        echo "添加字段: xpd_group_duration...\n";
        $sql2 = "ALTER TABLE `{$config['prefix']}mendian` 
                 ADD COLUMN `xpd_group_duration` INT UNSIGNED DEFAULT 5000 
                 COMMENT '单组展示时长(毫秒)' AFTER `xpd_image_duration`";
        $pdo->exec($sql2);
        echo "✓ xpd_group_duration 字段添加成功\n\n";
    } else {
        echo "✓ xpd_group_duration 字段已存在\n\n";
    }
    
    echo "========================================\n";
    echo "数据库迁移完成！\n";
    echo "========================================\n\n";
    
    echo "字段说明：\n";
    echo "• xpd_image_duration: 单张图片展示时长(毫秒)，默认1000ms\n";
    echo "• xpd_group_duration: 单组展示时长(毫秒)，默认5000ms\n\n";
    
    echo "建议配置范围：\n";
    echo "• 图片时长: 500-3000ms\n";
    echo "• 组时长: 3000-10000ms\n\n";
    
} catch (PDOException $e) {
    echo "❌ 数据库操作失败: " . $e->getMessage() . "\n";
    exit(1);
}

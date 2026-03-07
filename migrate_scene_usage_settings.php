<?php
/**
 * 数据库迁移：场景模板使用次数与每单输出数量设置
 * 
 * 为 ddwx_generation_scene_template 表添加:
 * - use_count: 使用次数，管理员可设置初始值，每次调用模板自动+1
 * - output_quantity: 每单输出数量，受绑定模型的max_output能力限制
 * 
 * @date 2026-03-03
 */

echo "======================================\n";
echo "场景模板使用次数与每单输出数量设置 - 数据库迁移\n";
echo "======================================\n\n";

// 读取数据库配置
$config = require __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $table = 'ddwx_generation_scene_template';
    
    echo "数据库表: {$table}\n";
    
    // 检查表是否存在
    try {
        $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
        echo "✓ 表 {$table} 存在\n";
    } catch (\PDOException $e) {
        echo "✗ 表 {$table} 不存在，请先创建该表\n";
        exit(1);
    }
    
    // 辅助函数：检查字段是否存在
    function fieldExists($pdo, $tbl, $field) {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$tbl}` LIKE '{$field}'");
        return $stmt->rowCount() > 0;
    }
    
    $migrationsExecuted = 0;
    
    // 1. 添加 use_count 字段
    if (!fieldExists($pdo, $table, 'use_count')) {
        echo "添加 use_count 字段...\n";
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `use_count` INT(11) NOT NULL DEFAULT 0 COMMENT '使用次数，管理员可设置初始值，每次调用自动+1'");
        echo "✓ use_count 字段添加成功\n";
        $migrationsExecuted++;
    } else {
        echo "- use_count 字段已存在，跳过\n";
    }
    
    // 2. 添加 output_quantity 字段
    if (!fieldExists($pdo, $table, 'output_quantity')) {
        echo "添加 output_quantity 字段...\n";
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `output_quantity` INT(11) NOT NULL DEFAULT 1 COMMENT '每单输出数量，受绑定模型max_output限制'");
        echo "✓ output_quantity 字段添加成功\n";
        $migrationsExecuted++;
    } else {
        echo "- output_quantity 字段已存在，跳过\n";
    }
    
    // 3. 为 use_count 添加索引（用于热度排序）
    echo "\n检查索引...\n";
    $stmt = $pdo->query("SHOW INDEX FROM `{$table}` WHERE Key_name = 'idx_use_count'");
    if ($stmt->rowCount() == 0) {
        echo "添加 idx_use_count 索引...\n";
        $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `idx_use_count` (`use_count`)");
        echo "✓ idx_use_count 索引添加成功\n";
        $migrationsExecuted++;
    } else {
        echo "- idx_use_count 索引已存在，跳过\n";
    }
    
    echo "\n======================================\n";
    echo "迁移完成！共执行 {$migrationsExecuted} 项变更\n";
    echo "======================================\n\n";
    
    // 显示字段说明
    echo "字段说明:\n";
    echo "  - use_count: 使用次数（int, 默认0）\n";
    echo "    管理员可设置初始值，每次模板被调用生成任务后自动+1\n";
    echo "  - output_quantity: 每单输出数量（int, 默认1）\n";
    echo "    受绑定模型的max_output能力限制，任务转模板时自动填充为源任务输出数量\n";
    
} catch (PDOException $e) {
    echo "数据库错误: " . $e->getMessage() . "\n";
    exit(1);
}

<?php
/**
 * 场景模板自动标签功能 - 数据库迁移脚本
 * 为 ddwx_generation_scene_template 表添加自动标签相关字段
 * @date 2026-04-16
 */
define('ROOT_PATH', __DIR__ . '/');
$config = include(ROOT_PATH . 'config.php');

try {
    $pdo = new PDO(
        "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $prefix = $config['prefix'] ?? 'ddwx_';
    $table = $prefix . 'generation_scene_template';

    echo "=== 场景模板自动标签字段迁移 ===\n\n";

    // 检查表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
    if ($stmt->rowCount() == 0) {
        echo "错误：表 {$table} 不存在，请先创建场景模板表\n";
        exit(1);
    }

    // 获取现有字段
    $existingColumns = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $row['Field'];
    }

    $migrations = [
        [
            'field' => 'auto_tags',
            'sql'   => "ALTER TABLE `{$table}` ADD COLUMN `auto_tags` JSON DEFAULT NULL COMMENT '自动识别的标签数据（结构化JSON）' AFTER `category`",
        ],
        [
            'field' => 'auto_tag_status',
            'sql'   => "ALTER TABLE `{$table}` ADD COLUMN `auto_tag_status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '自动标签状态：0=未识别，1=识别中，2=已完成，3=识别失败' AFTER `auto_tags`",
        ],
        [
            'field' => 'auto_tag_time',
            'sql'   => "ALTER TABLE `{$table}` ADD COLUMN `auto_tag_time` INT(11) NOT NULL DEFAULT 0 COMMENT '最后一次自动标签识别时间戳' AFTER `auto_tag_status`",
        ],
        [
            'field' => 'auto_tag_source_url',
            'sql'   => "ALTER TABLE `{$table}` ADD COLUMN `auto_tag_source_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '用于识别的源图片URL' AFTER `auto_tag_time`",
        ],
    ];

    $addedCount = 0;
    $skippedCount = 0;

    foreach ($migrations as $migration) {
        if (in_array($migration['field'], $existingColumns)) {
            echo "跳过：字段 {$migration['field']} 已存在\n";
            $skippedCount++;
        } else {
            $pdo->exec($migration['sql']);
            echo "✅ 添加字段：{$migration['field']}\n";
            $addedCount++;
        }
    }

    // 添加索引（用于批量补全查询）
    $indexName = 'idx_auto_tag_status';
    $stmt = $pdo->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`auto_tag_status`, `status`)");
        echo "✅ 添加索引：{$indexName}\n";
        $addedCount++;
    } else {
        echo "跳过：索引 {$indexName} 已存在\n";
        $skippedCount++;
    }

    echo "\n=== 迁移完成 ===\n";
    echo "新增：{$addedCount} 项，跳过：{$skippedCount} 项\n";

} catch (PDOException $e) {
    echo "数据库错误: " . $e->getMessage() . "\n";
    exit(1);
}

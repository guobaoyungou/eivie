<?php
/**
 * 合成模板管理重构 - 数据库迁移脚本
 * 为 ddwx_ai_travel_photo_synthesis_template 表添加场景模板关联字段
 * @date 2026-04-20
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
    $table = $prefix . 'ai_travel_photo_synthesis_template';

    echo "=== 合成模板场景关联字段迁移 ===\n\n";

    // 检查表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
    if ($stmt->rowCount() == 0) {
        echo "错误：表 {$table} 不存在\n";
        exit(1);
    }

    // 获取现有字段
    $existingColumns = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $row['Field'];
    }

    $success = 0;
    $skipped = 0;
    $errors = [];

    // 迁移定义
    $migrations = [
        [
            'field' => 'scene_template_id',
            'sql'   => "ALTER TABLE `{$table}` ADD COLUMN `scene_template_id` int(11) NOT NULL DEFAULT 0 COMMENT '来源场景模板ID，关联generation_scene_template.id' AFTER `bid`",
        ],
        [
            'field' => 'default_params',
            'sql'   => "ALTER TABLE `{$table}` ADD COLUMN `default_params` text DEFAULT NULL COMMENT '完整默认参数JSON（从场景模板复制）' AFTER `prompt`",
        ],
        [
            'field' => 'description',
            'sql'   => "ALTER TABLE `{$table}` ADD COLUMN `description` varchar(500) NOT NULL DEFAULT '' COMMENT '模板描述（从场景模板复制）' AFTER `default_params`",
        ],
        [
            'field' => 'cover_image',
            'sql'   => "ALTER TABLE `{$table}` ADD COLUMN `cover_image` varchar(500) NOT NULL DEFAULT '' COMMENT '封面图URL（从场景模板复制）' AFTER `description`",
        ],
    ];

    foreach ($migrations as $m) {
        if (in_array($m['field'], $existingColumns)) {
            echo "  - {$m['field']}: 已存在，跳过\n";
            $skipped++;
        } else {
            try {
                $pdo->exec($m['sql']);
                echo "  ✓ {$m['field']}: 添加成功\n";
                $success++;
            } catch (Exception $e) {
                echo "  ✗ {$m['field']}: " . $e->getMessage() . "\n";
                $errors[] = $m['field'] . ': ' . $e->getMessage();
            }
        }
    }

    // 添加索引
    echo "\n添加索引...\n";
    $existingIndexes = [];
    $stmt = $pdo->query("SHOW INDEX FROM `{$table}`");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingIndexes[] = $row['Key_name'];
    }

    if (!in_array('idx_scene_template_id', $existingIndexes)) {
        try {
            $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `idx_scene_template_id` (`scene_template_id`)");
            echo "  ✓ idx_scene_template_id: 添加成功\n";
            $success++;
        } catch (Exception $e) {
            echo "  ✗ idx_scene_template_id: " . $e->getMessage() . "\n";
            $errors[] = 'idx_scene_template_id: ' . $e->getMessage();
        }
    } else {
        echo "  - idx_scene_template_id: 已存在，跳过\n";
        $skipped++;
    }

    echo "\n=== 迁移完成 ===\n";
    echo "成功: {$success}, 跳过: {$skipped}, 失败: " . count($errors) . "\n";

    if (!empty($errors)) {
        echo "\n失败详情:\n";
        foreach ($errors as $err) {
            echo "  - {$err}\n";
        }
    }

    // 验证最终表结构
    echo "\n最终表结构:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  {$row['Field']} | {$row['Type']} | Default: {$row['Default']}\n";
    }

} catch (PDOException $e) {
    echo "数据库连接失败: " . $e->getMessage() . "\n";
    exit(1);
}

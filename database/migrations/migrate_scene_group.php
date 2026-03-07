<?php
/**
 * 场景分组功能数据库迁移脚本
 * 
 * 1. 创建场景分组表 ddwx_generation_scene_group
 * 2. 为场景模板表添加 group_ids 字段
 */

require __DIR__ . '/../../vendor/autoload.php';

$config = include(__DIR__ . '/../../config.php');

// 数据库配置
$hostname = $config['hostname'];
$username = $config['username'];
$password = $config['password'];
$database = $config['database'];
$hostport = $config['hostport'] ?? '3306';
$prefix = $config['prefix'];

try {
    $pdo = new PDO(
        "mysql:host={$hostname};port={$hostport};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "数据库连接成功\n";
    
    // 1. 创建场景分组表
    $tableName = $prefix . 'generation_scene_group';
    $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
        `aid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '账户ID',
        `bid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户ID（0表示平台级）',
        `generation_type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '生成类型：1=照片，2=视频',
        `name` varchar(100) NOT NULL DEFAULT '' COMMENT '分组名称',
        `pic` varchar(255) NOT NULL DEFAULT '' COMMENT '分组图标',
        `description` varchar(500) NOT NULL DEFAULT '' COMMENT '分组描述',
        `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序值（越大越靠前）',
        `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态：0=禁用，1=启用',
        `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
        `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
        PRIMARY KEY (`id`),
        KEY `idx_aid` (`aid`),
        KEY `idx_bid` (`bid`),
        KEY `idx_generation_type` (`generation_type`),
        KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='场景分组表';";
    
    $pdo->exec($sql);
    echo "✓ 场景分组表 {$tableName} 创建成功\n";
    
    // 2. 检查并添加 group_ids 字段到场景模板表
    $templateTable = $prefix . 'generation_scene_template';
    
    // 检查字段是否存在
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$templateTable}` LIKE 'group_ids'");
    $stmt->execute();
    $exists = $stmt->fetch();
    
    if (!$exists) {
        $sql = "ALTER TABLE `{$templateTable}` ADD COLUMN `group_ids` varchar(500) NOT NULL DEFAULT '' COMMENT '分组ID列表（逗号分隔）' AFTER `category_ids`";
        $pdo->exec($sql);
        echo "✓ 场景模板表添加 group_ids 字段成功\n";
    } else {
        echo "- 场景模板表已存在 group_ids 字段，跳过\n";
    }
    
    echo "\n=== 迁移完成 ===\n";
    
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);
}

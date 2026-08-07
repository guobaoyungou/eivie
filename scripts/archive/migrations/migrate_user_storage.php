<?php
/**
 * 用户云端存储空间 数据库迁移脚本
 * 包含：
 * 1. ddwx_user_storage_usage  用户存储用量表
 * 2. ddwx_user_storage_file   用户存储文件表
 * 3. ddwx_member 新增 storage_used_bytes 冗余字段
 */

$config = include __DIR__ . '/config.php';

$host = $config['hostname'];
$dbname = $config['database'];
$user = $config['username'];
$pass = $config['password'];
$port = $config['hostport'] ?: 3306;
$prefix = $config['prefix'];

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "数据库连接成功\n";
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage() . "\n");
}

$results = [];

// ========== 1. ddwx_user_storage_usage 用户存储用量表 ==========
$table = $prefix . 'user_storage_usage';
try {
    $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE `{$table}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `aid` int(11) NOT NULL DEFAULT 0 COMMENT '账户ID',
            `mid` int(11) NOT NULL DEFAULT 0 COMMENT '会员ID',
            `total_quota_bytes` bigint(20) NOT NULL DEFAULT 5368709120 COMMENT '总配额(字节) 默认5GB',
            `used_bytes` bigint(20) NOT NULL DEFAULT 0 COMMENT '已用空间(字节)',
            `file_count` int(11) NOT NULL DEFAULT 0 COMMENT '文件总数',
            `image_count` int(11) NOT NULL DEFAULT 0 COMMENT '图片文件数',
            `video_count` int(11) NOT NULL DEFAULT 0 COMMENT '视频文件数',
            `last_warning_time` int(11) NOT NULL DEFAULT 0 COMMENT '上次告警时间戳',
            `updatetime` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
            `createtime` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_aid_mid` (`aid`, `mid`),
            KEY `idx_mid` (`mid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户存储用量表';");
        $results[] = "[OK] {$table} 表创建成功";
    } else {
        $results[] = "[SKIP] {$table} 表已存在";
    }
} catch (PDOException $e) {
    $results[] = "[ERROR] {$table}: " . $e->getMessage();
}

// ========== 2. ddwx_user_storage_file 用户存储文件表 ==========
$table = $prefix . 'user_storage_file';
try {
    $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE `{$table}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `aid` int(11) NOT NULL DEFAULT 0 COMMENT '账户ID',
            `mid` int(11) NOT NULL DEFAULT 0 COMMENT '会员ID',
            `file_url` varchar(500) NOT NULL DEFAULT '' COMMENT '文件URL',
            `thumbnail_url` varchar(500) NOT NULL DEFAULT '' COMMENT '缩略图URL',
            `file_type` varchar(20) NOT NULL DEFAULT 'image' COMMENT '文件类型 image/video',
            `source_type` varchar(20) NOT NULL DEFAULT 'upload' COMMENT '来源类型 upload/generated',
            `source_id` int(11) NOT NULL DEFAULT 0 COMMENT '来源ID(generation_output.id或upload记录ID)',
            `file_size` bigint(20) NOT NULL DEFAULT 0 COMMENT '文件大小(字节)',
            `width` int(11) NOT NULL DEFAULT 0 COMMENT '图片/视频宽度',
            `height` int(11) NOT NULL DEFAULT 0 COMMENT '图片/视频高度',
            `duration` int(11) NOT NULL DEFAULT 0 COMMENT '视频时长(毫秒)',
            `is_template_ref` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否被场景模板引用',
            `template_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '关联的模板ID列表(逗号分隔)',
            `is_deleted` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已删除(软删除)',
            `createtime` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
            PRIMARY KEY (`id`),
            KEY `idx_aid_mid` (`aid`, `mid`),
            KEY `idx_mid_type` (`mid`, `file_type`),
            KEY `idx_source` (`source_type`, `source_id`),
            KEY `idx_template_ref` (`is_template_ref`),
            KEY `idx_deleted` (`is_deleted`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户存储文件表';");
        $results[] = "[OK] {$table} 表创建成功";
    } else {
        $results[] = "[SKIP] {$table} 表已存在";
    }
} catch (PDOException $e) {
    $results[] = "[ERROR] {$table}: " . $e->getMessage();
}

// ========== 3. ddwx_member 新增 storage_used_bytes 冗余字段 ==========
$table = $prefix . 'member';
$field = 'storage_used_bytes';
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$field}'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$field}` bigint(20) NOT NULL DEFAULT 0 COMMENT '已用存储空间(字节) 冗余字段'");
        $results[] = "[OK] {$table}.{$field} 字段添加成功";
    } else {
        $results[] = "[SKIP] {$table}.{$field} 字段已存在";
    }
} catch (PDOException $e) {
    $results[] = "[ERROR] {$table}.{$field}: " . $e->getMessage();
}

// 输出结果
echo "\n========== 迁移结果 ==========\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "==============================\n";
echo "迁移完成！\n";

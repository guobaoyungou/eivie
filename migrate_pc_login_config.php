<?php
/**
 * 数据库迁移脚本：为 ddwx_admin_setapp_pc 表新增登录设置相关字段
 * 用于模板三PC端登录弹窗 - 微信公众号关注引导功能
 * 
 * 执行方式：php migrate_pc_login_config.php
 */

$config = include(__DIR__.'/config.php');

$host = $config['hostname'];
$db   = $config['database'];
$user = $config['username'];
$pass = $config['password'];
$port = $config['hostport'];
$prefix = $config['prefix'];

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "数据库连接成功\n";
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage() . "\n");
}

$table = $prefix . 'admin_setapp_pc';

// 要新增的字段
$fields = [
    [
        'name'    => 'require_follow',
        'sql'     => "ALTER TABLE `{$table}` ADD COLUMN `require_follow` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '登录需关注公众号开关：0=关闭，1=开启'",
    ],
    [
        'name'    => 'follow_qrcode',
        'sql'     => "ALTER TABLE `{$table}` ADD COLUMN `follow_qrcode` varchar(500) DEFAULT NULL COMMENT '公众号二维码图片URL'",
    ],
    [
        'name'    => 'follow_guide_text',
        'sql'     => "ALTER TABLE `{$table}` ADD COLUMN `follow_guide_text` varchar(200) DEFAULT '扫码关注公众号后即可登录' COMMENT '引导文案'",
    ],
    [
        'name'    => 'follow_appname',
        'sql'     => "ALTER TABLE `{$table}` ADD COLUMN `follow_appname` varchar(100) DEFAULT NULL COMMENT '公众号名称'",
    ],
    [
        'name'    => 'new_user_follow_guide',
        'sql'     => "ALTER TABLE `{$table}` ADD COLUMN `new_user_follow_guide` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '新用户注册后引导关注：0=关闭，1=开启'",
    ],
];

// 检查表是否存在
$stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
if ($stmt->rowCount() == 0) {
    die("错误：表 {$table} 不存在\n");
}

// 获取已有字段列表
$stmt = $pdo->query("DESCRIBE `{$table}`");
$existingFields = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $existingFields[] = $row['Field'];
}

$added = 0;
$skipped = 0;

foreach ($fields as $field) {
    if (in_array($field['name'], $existingFields)) {
        echo "字段 {$field['name']} 已存在，跳过\n";
        $skipped++;
        continue;
    }
    
    try {
        $pdo->exec($field['sql']);
        echo "✓ 成功添加字段: {$field['name']}\n";
        $added++;
    } catch (PDOException $e) {
        echo "✗ 添加字段 {$field['name']} 失败: " . $e->getMessage() . "\n";
    }
}

echo "\n迁移完成！新增 {$added} 个字段，跳过 {$skipped} 个已存在字段\n";

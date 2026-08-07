<?php
/**
 * 选片端人脸检测功能数据库迁移
 * 为 ai_travel_photo_qrcode 表新增 mp_qrcode_url 和 qrcode_type 字段
 */
$config = include(__DIR__ . '/config.php');

try {
    $pdo = new PDO(
        "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "开始执行选片端人脸检测功能数据库迁移...\n\n";

    $table = $config['prefix'] . 'ai_travel_photo_qrcode';

    // 1. 检查并添加 mp_qrcode_url 字段
    $checkSql1 = "SELECT COUNT(*) as count FROM information_schema.COLUMNS 
                  WHERE TABLE_SCHEMA = '{$config['database']}' 
                  AND TABLE_NAME = '{$table}' 
                  AND COLUMN_NAME = 'mp_qrcode_url'";
    $stmt = $pdo->query($checkSql1);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] == 0) {
        echo "添加字段: mp_qrcode_url...\n";
        $sql1 = "ALTER TABLE `{$table}` 
                 ADD COLUMN `mp_qrcode_url` VARCHAR(500) DEFAULT NULL 
                 COMMENT '微信公众号带参数二维码图片URL' AFTER `qrcode_url`";
        $pdo->exec($sql1);
        echo "✓ mp_qrcode_url 字段添加成功\n\n";
    } else {
        echo "✓ mp_qrcode_url 字段已存在\n\n";
    }

    // 2. 检查并添加 qrcode_type 字段
    $checkSql2 = "SELECT COUNT(*) as count FROM information_schema.COLUMNS 
                  WHERE TABLE_SCHEMA = '{$config['database']}' 
                  AND TABLE_NAME = '{$table}' 
                  AND COLUMN_NAME = 'qrcode_type'";
    $stmt = $pdo->query($checkSql2);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] == 0) {
        echo "添加字段: qrcode_type...\n";
        $sql2 = "ALTER TABLE `{$table}` 
                 ADD COLUMN `qrcode_type` TINYINT(1) UNSIGNED DEFAULT 1 
                 COMMENT '二维码类型：1=选片页二维码 2=公众号二维码' AFTER `mp_qrcode_url`";
        $pdo->exec($sql2);
        echo "✓ qrcode_type 字段添加成功\n\n";
    } else {
        echo "✓ qrcode_type 字段已存在\n\n";
    }

    // 3. 添加 qrcode_type 索引
    $checkIdx = "SELECT COUNT(*) as count FROM information_schema.STATISTICS 
                 WHERE TABLE_SCHEMA = '{$config['database']}' 
                 AND TABLE_NAME = '{$table}' 
                 AND INDEX_NAME = 'idx_qrcode_type'";
    $stmt = $pdo->query($checkIdx);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] == 0) {
        echo "添加索引: idx_qrcode_type...\n";
        $sql3 = "ALTER TABLE `{$table}` ADD INDEX `idx_qrcode_type` (`qrcode_type`)";
        $pdo->exec($sql3);
        echo "✓ idx_qrcode_type 索引添加成功\n\n";
    } else {
        echo "✓ idx_qrcode_type 索引已存在\n\n";
    }

    echo "========================================\n";
    echo "数据库迁移完成！\n";
    echo "========================================\n\n";

    echo "字段说明：\n";
    echo "• mp_qrcode_url: 微信公众号带参数二维码图片URL\n";
    echo "• qrcode_type: 二维码类型（1=选片页二维码, 2=公众号二维码）\n\n";

} catch (PDOException $e) {
    echo "❌ 数据库操作失败: " . $e->getMessage() . "\n";
    exit(1);
}

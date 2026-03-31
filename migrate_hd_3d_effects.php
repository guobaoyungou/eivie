<?php
/**
 * 3D签到功能重构 - 数据库迁移脚本
 * 1. weixin_threedimensional 表新增 play_mode 字段
 * 2. 新建 ddwx_hd_3d_effects 效果条目表
 *
 * 使用方法: php migrate_hd_3d_effects.php
 */

$config = include(__DIR__ . '/config.php');

$conn = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database'], (int)$config['hostport']);
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error . "\n");
}
$conn->set_charset('utf8mb4');

echo "======================================\n";
echo "  3D签到重构 - 数据库迁移\n";
echo "======================================\n\n";

// ============================================================
// 1. weixin_threedimensional 表新增 play_mode 字段
// ============================================================
echo "--- Step 1: ALTER weixin_threedimensional ---\n";

$checkCol = $conn->query("SHOW COLUMNS FROM `weixin_threedimensional` LIKE 'play_mode'");
if ($checkCol && $checkCol->num_rows > 0) {
    echo "[SKIP] play_mode 字段已存在\n";
} else {
    $alterSql = "ALTER TABLE `weixin_threedimensional` ADD COLUMN `play_mode` varchar(16) NOT NULL DEFAULT 'sequential' COMMENT '播放模式: sequential / random'";
    if ($conn->query($alterSql)) {
        echo "[OK] weixin_threedimensional 新增 play_mode 字段成功\n";
    } else {
        echo "[FAIL] ALTER TABLE 失败: " . $conn->error . "\n";
    }
}

// ============================================================
// 2. 新建 ddwx_hd_3d_effects 表
// ============================================================
echo "\n--- Step 2: CREATE ddwx_hd_3d_effects ---\n";

$createSql = "CREATE TABLE IF NOT EXISTS `ddwx_hd_3d_effects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0 COMMENT '平台账户ID',
  `bid` int(11) NOT NULL DEFAULT 0 COMMENT '商家ID',
  `activity_id` int(11) NOT NULL DEFAULT 0 COMMENT '活动ID',
  `type` varchar(32) NOT NULL DEFAULT '' COMMENT '效果类型: preset_shape / image_logo / text_logo / countdown',
  `content` varchar(512) NOT NULL DEFAULT '' COMMENT '效果内容',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序序号，值越小越靠前',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否为系统预置默认效果 1=是 0=否',
  `created_at` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间戳',
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`),
  KEY `idx_aid_bid` (`aid`, `bid`),
  KEY `idx_sort` (`activity_id`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-3D签到效果条目';";

if ($conn->query($createSql)) {
    echo "[OK] ddwx_hd_3d_effects 表创建成功\n";
} else {
    echo "[FAIL] CREATE TABLE 失败: " . $conn->error . "\n";
}

echo "\n======================================\n";
echo "  迁移完成\n";
echo "======================================\n";

$conn->close();

<?php
/**
 * 数据库迁移：为 hd_participant 表添加 is_admin 和 is_verifier 字段
 * 用于活动管理员移动端大屏控制和核销员功能
 * 
 * 执行：php migrate_hd_admin_verifier.php
 */

define('ROOT_PATH', __DIR__ . '/');
require_once __DIR__ . '/vendor/autoload.php';

// 初始化 ThinkPHP 应用
$app = (new \think\App())->initialize();
$db = \think\facade\Db::connect();

echo "=== 开始迁移：hd_participant 添加 is_admin, is_verifier 字段 ===\n";

try {
    // 检查 is_admin 字段是否已存在
    $columns = $db->query("SHOW COLUMNS FROM `ddwx_hd_participant` LIKE 'is_admin'");
    if (empty($columns)) {
        $db->execute("ALTER TABLE `ddwx_hd_participant` ADD COLUMN `is_admin` tinyint(1) NOT NULL DEFAULT 0 COMMENT '管理员标识：0=普通用户，1=活动管理员' AFTER `custom_data`");
        echo "[OK] 已添加 is_admin 字段\n";
    } else {
        echo "[SKIP] is_admin 字段已存在\n";
    }

    // 检查 is_verifier 字段是否已存在
    $columns = $db->query("SHOW COLUMNS FROM `ddwx_hd_participant` LIKE 'is_verifier'");
    if (empty($columns)) {
        $db->execute("ALTER TABLE `ddwx_hd_participant` ADD COLUMN `is_verifier` tinyint(1) NOT NULL DEFAULT 0 COMMENT '核销员标识：0=普通用户，1=核销员' AFTER `is_admin`");
        echo "[OK] 已添加 is_verifier 字段\n";
    } else {
        echo "[SKIP] is_verifier 字段已存在\n";
    }

    // 添加索引（优化管理员和核销员查询）
    $indexes = $db->query("SHOW INDEX FROM `ddwx_hd_participant` WHERE Key_name = 'idx_is_admin'");
    if (empty($indexes)) {
        $db->execute("ALTER TABLE `ddwx_hd_participant` ADD INDEX `idx_is_admin` (`activity_id`, `is_admin`)");
        echo "[OK] 已添加 idx_is_admin 索引\n";
    } else {
        echo "[SKIP] idx_is_admin 索引已存在\n";
    }

    $indexes = $db->query("SHOW INDEX FROM `ddwx_hd_participant` WHERE Key_name = 'idx_is_verifier'");
    if (empty($indexes)) {
        $db->execute("ALTER TABLE `ddwx_hd_participant` ADD INDEX `idx_is_verifier` (`activity_id`, `is_verifier`)");
        echo "[OK] 已添加 idx_is_verifier 索引\n";
    } else {
        echo "[SKIP] idx_is_verifier 索引已存在\n";
    }

    echo "=== 迁移完成 ===\n";
} catch (\Exception $e) {
    echo "[ERROR] 迁移失败：" . $e->getMessage() . "\n";
    exit(1);
}

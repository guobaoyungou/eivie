<?php
/**
 * 大屏互动 - 微信授权登录数据库迁移
 * 为 admin_user 表新增 openid 字段
 * 
 * 执行方式: php migrate_hd_wx_auth.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// 初始化 ThinkPHP 应用
$app = new \think\App();
$app->initialize();

use think\facade\Db;

echo "========================================\n";
echo "大屏互动 - 微信授权登录迁移\n";
echo "========================================\n\n";

try {
    // 1. 检查 admin_user 表是否已有 openid 字段
    $columns = Db::query("SHOW COLUMNS FROM `admin_user` LIKE 'openid'");
    
    if (empty($columns)) {
        echo "[1/2] 为 admin_user 表添加 openid 字段...\n";
        Db::execute("ALTER TABLE `admin_user` ADD COLUMN `openid` varchar(64) DEFAULT NULL COMMENT '微信公众号openid' AFTER `tel`");
        echo "  ✅ openid 字段添加成功\n";
    } else {
        echo "[1/2] admin_user 表已存在 openid 字段，跳过\n";
    }

    // 2. 添加唯一索引（openid 可为空，允许多个NULL）
    $indexes = Db::query("SHOW INDEX FROM `admin_user` WHERE Key_name = 'idx_openid'");
    if (empty($indexes)) {
        echo "[2/2] 为 admin_user 表添加 openid 索引...\n";
        Db::execute("ALTER TABLE `admin_user` ADD INDEX `idx_openid` (`openid`)");
        echo "  ✅ openid 索引添加成功\n";
    } else {
        echo "[2/2] admin_user 表已存在 openid 索引，跳过\n";
    }

    echo "\n✅ 迁移完成！\n";

} catch (\Exception $e) {
    echo "\n❌ 迁移失败: " . $e->getMessage() . "\n";
    exit(1);
}

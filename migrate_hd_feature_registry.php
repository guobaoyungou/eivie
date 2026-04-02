<?php
/**
 * 大屏互动 - 功能注册表数据库迁移
 * 1. 创建 ddwx_hd_feature_registry 表（平台级功能注册表）
 * 2. 预置 19 条系统功能记录
 * 3. 为 ddwx_hd_activity_feature 表新增 parent_code 和 display_name 字段
 *
 * 执行方式: php migrate_hd_feature_registry.php
 */

$config = include(__DIR__ . '/config.php');

$conn = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database'], (int)$config['hostport']);
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error . "\n");
}
$conn->set_charset('utf8mb4');

echo "========================================\n";
echo "大屏互动 - 功能注册表迁移\n";
echo "========================================\n\n";

try {
    // -------------------------------------------------------
    // 1. 创建 ddwx_hd_feature_registry 表
    // -------------------------------------------------------
    $result = $conn->query("SHOW TABLES LIKE 'ddwx_hd_feature_registry'");
    if ($result->num_rows == 0) {
        echo "[1/3] 创建 ddwx_hd_feature_registry 表...\n";
        $sql = "
            CREATE TABLE `ddwx_hd_feature_registry` (
                `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
                `feature_code` varchar(32) NOT NULL DEFAULT '' COMMENT '功能唯一标识',
                `feature_name` varchar(50) NOT NULL DEFAULT '' COMMENT '功能显示名称',
                `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '菜单图标',
                `parent_id` int(11) NOT NULL DEFAULT 0 COMMENT '父级功能ID（0=一级菜单）',
                `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序序号（越小越靠前）',
                `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=启用 2=停用',
                `is_system` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=系统预置 0=自定义新增',
                `description` varchar(200) NOT NULL DEFAULT '' COMMENT '功能描述',
                `config` text DEFAULT NULL COMMENT '扩展配置JSON',
                `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
                `updatetime` int(11) DEFAULT NULL COMMENT '更新时间',
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_feature_code` (`feature_code`),
                KEY `idx_parent_sort` (`parent_id`, `sort`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-功能注册表（平台级）'
        ";
        if (!$conn->query($sql)) {
            throw new Exception("创建表失败: " . $conn->error);
        }
        echo "  ✅ ddwx_hd_feature_registry 表创建成功\n";
    } else {
        echo "[1/3] ddwx_hd_feature_registry 表已存在，跳过\n";
    }

    // -------------------------------------------------------
    // 2. 预置 19 条系统功能记录
    // -------------------------------------------------------
    $result = $conn->query("SELECT COUNT(*) as cnt FROM ddwx_hd_feature_registry");
    $row = $result->fetch_assoc();
    $existCount = (int)$row['cnt'];

    if ($existCount == 0) {
        echo "[2/3] 插入 19 条预置功能记录...\n";
        $now = time();
        $features = [
            ['qdq',                  '签到墙',     1,  '现场签到展示'],
            ['threedimensionalsign',  '3D签到',     2,  '3D效果签到展示'],
            ['wall',                  '微信上墙',   3,  '微信消息上墙互动'],
            ['lottery',               '大屏抽奖',   4,  '大屏端抽奖'],
            ['choujiang',             '手机抽奖',   5,  '手机端抽奖'],
            ['ydj',                   '摇大奖',     6,  '摇一摇抽奖'],
            ['game',                  '互动游戏',   7,  '多种互动游戏'],
            ['redpacket',             '红包雨',     8,  '红包雨互动'],
            ['importlottery',         '导入抽奖',   9,  '导入名单抽奖'],
            ['xiangce',               '相册',       10, '活动相册展示'],
            ['xyh',                   '幸运号码',   11, '幸运号码抽奖'],
            ['xysjh',                 '幸运手机号', 12, '幸运手机号抽奖'],
            ['danmu',                 '弹幕',       13, '弹幕消息互动'],
            ['vote',                  '投票',       14, '现场投票互动'],
            ['shake',                 '摇一摇竞技', 15, '摇一摇竞技游戏'],
            ['kaimu',                 '开幕墙',     16, '活动开幕展示'],
            ['bimu',                  '闭幕墙',     17, '活动闭幕展示'],
            ['lvpai',                 '旅拍大屏',   18, '旅拍照片大屏展示'],
            ['scan_lottery',          '扫码抽奖',   19, '扫码参与抽奖'],
        ];

        $stmt = $conn->prepare("INSERT INTO ddwx_hd_feature_registry (feature_code, feature_name, icon, parent_id, sort, status, is_system, description, config, createtime, updatetime) VALUES (?, ?, '', 0, ?, 1, 1, ?, NULL, ?, ?)");
        foreach ($features as $f) {
            $stmt->bind_param('ssissi', $f[0], $f[1], $f[2], $f[3], $now, $now);
            $stmt->execute();
            echo "  ✅ 插入功能: {$f[0]} ({$f[1]})\n";
        }
        $stmt->close();
        echo "  ✅ 全部 19 条预置功能记录插入完成\n";
    } else {
        echo "[2/3] ddwx_hd_feature_registry 表已有 {$existCount} 条记录，跳过预置\n";
    }

    // -------------------------------------------------------
    // 3. 为 ddwx_hd_activity_feature 表新增字段
    // -------------------------------------------------------
    echo "[3/3] 检查 ddwx_hd_activity_feature 表字段...\n";

    // 检查 parent_code 字段
    $result = $conn->query("SHOW COLUMNS FROM `ddwx_hd_activity_feature` LIKE 'parent_code'");
    if ($result->num_rows == 0) {
        if (!$conn->query("ALTER TABLE `ddwx_hd_activity_feature` ADD COLUMN `parent_code` varchar(32) NOT NULL DEFAULT '' COMMENT '父级功能标识' AFTER `config`")) {
            throw new Exception("添加 parent_code 失败: " . $conn->error);
        }
        echo "  ✅ parent_code 字段添加成功\n";
    } else {
        echo "  ⏭ parent_code 字段已存在，跳过\n";
    }

    // 检查 display_name 字段
    $result = $conn->query("SHOW COLUMNS FROM `ddwx_hd_activity_feature` LIKE 'display_name'");
    if ($result->num_rows == 0) {
        if (!$conn->query("ALTER TABLE `ddwx_hd_activity_feature` ADD COLUMN `display_name` varchar(50) NOT NULL DEFAULT '' COMMENT '活动级自定义显示名' AFTER `parent_code`")) {
            throw new Exception("添加 display_name 失败: " . $conn->error);
        }
        echo "  ✅ display_name 字段添加成功\n";
    } else {
        echo "  ⏭ display_name 字段已存在，跳过\n";
    }

    echo "\n✅ 迁移全部完成！\n";

} catch (Exception $e) {
    echo "\n❌ 迁移失败: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();

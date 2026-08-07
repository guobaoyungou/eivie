<?php
/**
 * 互动抽奖功能重构 - 数据库迁移脚本
 * 1. 新增 ddwx_hd_lottery_winner (中奖记录表)
 * 2. 新增 ddwx_hd_lottery_designated (内定记录表)
 * 3. ddwx_hd_lottery_config 扩展字段 (show_type, title, win_again, show_style, theme_id)
 * 4. ddwx_hd_prize 扩展字段 (prizename, type, leftnum, draw_count, imageid, plug_name)
 *
 * 执行: php migrate_hd_lottery_refactor.php
 */

$config = include(__DIR__ . '/config.php');

$conn = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database'], (int)$config['hostport']);
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error . "\n");
}
$conn->set_charset('utf8mb4');

function runSQL($conn, $sql, $label) {
    if ($conn->query($sql)) {
        echo "  ✅ {$label}\n";
    } else {
        echo "  ⚠ {$label}: " . $conn->error . "\n";
    }
}

function columnExists($conn, $table, $col) {
    $r = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'");
    return $r && $r->num_rows > 0;
}

echo "=== 互动抽奖功能重构迁移 ===\n\n";

// 1. 新增 ddwx_hd_lottery_winner
echo "[1/4] 创建 ddwx_hd_lottery_winner ...\n";
runSQL($conn, "CREATE TABLE IF NOT EXISTS `ddwx_hd_lottery_winner` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `aid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '租户ID',
    `bid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商家ID',
    `activity_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '活动ID',
    `round_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '轮次ID',
    `prize_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '奖品ID',
    `participant_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '中奖用户ID',
    `nickname` varchar(100) NOT NULL DEFAULT '' COMMENT '昵称冗余',
    `avatar` varchar(500) NOT NULL DEFAULT '' COMMENT '头像冗余',
    `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号冗余',
    `status` tinyint(1) unsigned NOT NULL DEFAULT '2' COMMENT '2=未发奖 3=已发奖',
    `verify_code` varchar(20) NOT NULL DEFAULT '' COMMENT '兑奖码',
    `win_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '中奖时间',
    `give_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发奖时间',
    `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_activity` (`activity_id`),
    KEY `idx_round` (`round_id`),
    KEY `idx_prize` (`prize_id`),
    KEY `idx_participant` (`participant_id`),
    KEY `idx_aid_bid` (`aid`,`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-中奖记录表'", 'ddwx_hd_lottery_winner 创建成功');

// 2. 新增 ddwx_hd_lottery_designated
echo "[2/4] 创建 ddwx_hd_lottery_designated ...\n";
runSQL($conn, "CREATE TABLE IF NOT EXISTS `ddwx_hd_lottery_designated` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `aid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '租户ID',
    `bid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商家ID',
    `activity_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '活动ID',
    `participant_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
    `prize_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '奖品ID',
    `designated` tinyint(1) unsigned NOT NULL DEFAULT '2' COMMENT '2=必中 3=不中',
    `plug_name` varchar(50) NOT NULL DEFAULT '' COMMENT '来源模块标识',
    `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_activity` (`activity_id`),
    KEY `idx_participant` (`participant_id`),
    KEY `idx_aid_bid` (`aid`,`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-内定记录表'", 'ddwx_hd_lottery_designated 创建成功');

// 3. ddwx_hd_lottery_config 扩展字段
echo "[3/4] 扩展 ddwx_hd_lottery_config ...\n";
$configFields = [
    ['show_type',  "VARCHAR(20) NOT NULL DEFAULT 'normal' COMMENT '展示类型: normal/3d/egg/box'"],
    ['title',      "VARCHAR(100) NOT NULL DEFAULT '' COMMENT '轮次名称'"],
    ['win_again',  "TINYINT(1) unsigned NOT NULL DEFAULT '1' COMMENT '1=不允许 2=允许重复中奖'"],
    ['show_style', "VARCHAR(20) NOT NULL DEFAULT 'nickname' COMMENT '显示方式'"],
    ['theme_id',   "INT(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联主题ID'"],
];
foreach ($configFields as $f) {
    if (!columnExists($conn, 'ddwx_hd_lottery_config', $f[0])) {
        runSQL($conn, "ALTER TABLE `ddwx_hd_lottery_config` ADD COLUMN `{$f[0]}` {$f[1]}", "字段 {$f[0]} 已添加");
    } else {
        echo "  ⏭ 字段 {$f[0]} 已存在\n";
    }
}

// 4. ddwx_hd_prize 扩展字段
echo "[4/4] 扩展 ddwx_hd_prize ...\n";
$prizeFields = [
    ['prizename',  "VARCHAR(100) NOT NULL DEFAULT '' COMMENT '奖品名称'"],
    ['type',       "TINYINT(1) unsigned NOT NULL DEFAULT '1' COMMENT '奖品级别 1~5'"],
    ['leftnum',    "INT(11) NOT NULL DEFAULT '0' COMMENT '剩余数量'"],
    ['draw_count', "INT(11) unsigned NOT NULL DEFAULT '1' COMMENT '每次抽取数量'"],
    ['imageid',    "VARCHAR(500) NOT NULL DEFAULT '' COMMENT '奖品图片'"],
    ['plug_name',  "VARCHAR(50) NOT NULL DEFAULT '' COMMENT '来源模块标识'"],
    ['num',        "INT(11) unsigned NOT NULL DEFAULT '0' COMMENT '奖品总数量'"],
];
foreach ($prizeFields as $f) {
    if (!columnExists($conn, 'ddwx_hd_prize', $f[0])) {
        runSQL($conn, "ALTER TABLE `ddwx_hd_prize` ADD COLUMN `{$f[0]}` {$f[1]}", "字段 {$f[0]} 已添加");
    } else {
        echo "  ⏭ 字段 {$f[0]} 已存在\n";
    }
}

$conn->close();
echo "\n=== 迁移完成 ===\n";

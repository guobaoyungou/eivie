<?php
/**
 * 旅拍用户自拍端（笑脸抓拍）数据库迁移脚本
 * 
 * 新增表：
 * 1. ai_travel_photo_selfie_qrcode - 门店自拍二维码表
 * 2. ai_travel_photo_selfie_notify - 自拍通知请求表
 * 
 * 扩展字段：
 * 3. ai_travel_photo_portrait - 新增 source_type, user_openid
 * 4. mendian - 新增 selfie_push_title, selfie_push_desc, selfie_push_cover, selfie_enabled
 * 
 * @date 2026-04-10
 */

// 引入配置
$config = include(__DIR__ . '/config.php');

// 数据库连接信息
$hostname = $config['hostname'];
$database = $config['database'];
$username = $config['username'];
$password = $config['password'];
$hostport = $config['hostport'];
$prefix   = $config['prefix'] ?? 'ddwx_';

echo "========================================\n";
echo "  旅拍用户自拍端 - 数据库迁移\n";
echo "========================================\n\n";

try {
    $mysqli = new mysqli($hostname, $username, $password, $database, $hostport);

    if ($mysqli->connect_error) {
        throw new Exception("数据库连接失败: " . $mysqli->connect_error);
    }

    $mysqli->set_charset("utf8mb4");
    echo "✓ 数据库连接成功\n\n";

    $success = 0;
    $skipped = 0;
    $failed  = 0;

    // 1. 创建门店自拍二维码表
    $tblQrcode = $prefix . 'ai_travel_photo_selfie_qrcode';
    echo "1. 创建 {$tblQrcode} 表...\n";
    $result = $mysqli->query("SHOW TABLES LIKE '{$tblQrcode}'");
    if ($result && $result->num_rows > 0) {
        echo "   [跳过] 表已存在\n";
        $skipped++;
    } else {
        $sql = "
            CREATE TABLE `{$tblQrcode}` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增主键',
                `aid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '平台ID',
                `bid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '商家ID',
                `mdid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '门店ID',
                `scene_str` varchar(128) NOT NULL DEFAULT '' COMMENT '二维码场景值',
                `qrcode_url` varchar(512) NOT NULL DEFAULT '' COMMENT '二维码图片URL',
                `push_title` varchar(256) NOT NULL DEFAULT '' COMMENT '推文标题',
                `push_desc` varchar(512) NOT NULL DEFAULT '' COMMENT '推文描述',
                `push_cover` varchar(512) NOT NULL DEFAULT '' COMMENT '推文封面图URL',
                `scan_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '累计扫码次数',
                `follow_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '通过此码关注的人数',
                `selfie_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '自拍抓拍次数',
                `match_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '命中成片次数',
                `status` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '状态（1正常/0禁用）',
                `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
                `update_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_aid_bid_mdid` (`aid`, `bid`, `mdid`),
                UNIQUE KEY `uk_scene_str` (`scene_str`),
                KEY `idx_bid` (`bid`),
                KEY `idx_mdid` (`mdid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='门店自拍二维码表'
        ";
        if ($mysqli->query($sql)) {
            echo "   [成功] 表创建完成\n";
            $success++;
        } else {
            echo "   [失败] " . $mysqli->error . "\n";
            $failed++;
        }
    }

    // 2. 创建自拍通知请求表
    $tblNotify = $prefix . 'ai_travel_photo_selfie_notify';
    echo "\n2. 创建 {$tblNotify} 表...\n";
    $result = $mysqli->query("SHOW TABLES LIKE '{$tblNotify}'");
    if ($result && $result->num_rows > 0) {
        echo "   [跳过] 表已存在\n";
        $skipped++;
    } else {
        $sql = "
            CREATE TABLE `{$tblNotify}` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增主键',
                `aid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '平台ID',
                `bid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '商家ID',
                `mdid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '门店ID',
                `portrait_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联的人像ID',
                `openid` varchar(64) NOT NULL DEFAULT '' COMMENT '用户微信openid',
                `uid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '会员ID',
                `notify_type` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '通知方式（1客服消息/2模板消息）',
                `notify_status` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '通知状态（0待通知/1已通知/2通知失败）',
                `pick_url` varchar(512) NOT NULL DEFAULT '' COMMENT '付费选片页URL',
                `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '请求时间',
                `notify_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '实际通知时间',
                PRIMARY KEY (`id`),
                KEY `idx_portrait_id` (`portrait_id`),
                KEY `idx_openid_aid` (`openid`, `aid`),
                KEY `idx_notify_status` (`notify_status`),
                UNIQUE KEY `uk_openid_portrait` (`openid`, `portrait_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='自拍通知请求表'
        ";
        if ($mysqli->query($sql)) {
            echo "   [成功] 表创建完成\n";
            $success++;
        } else {
            echo "   [失败] " . $mysqli->error . "\n";
            $failed++;
        }
    }

    // 3. 扩展人像表字段
    $tblPortrait = $prefix . 'ai_travel_photo_portrait';
    echo "\n3. 扩展 {$tblPortrait} 表字段...\n";

    // 3a. source_type 字段
    $result = $mysqli->query("SHOW COLUMNS FROM `{$tblPortrait}` LIKE 'source_type'");
    if ($result && $result->num_rows > 0) {
        echo "   [跳过] source_type 字段已存在\n";
        $skipped++;
    } else {
        if ($mysqli->query("ALTER TABLE `{$tblPortrait}` ADD COLUMN `source_type` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '来源类型（1商家上传/2笑脸抓拍/3用户自拍）' AFTER `type`")) {
            echo "   [成功] source_type 字段添加完成\n";
            $success++;
        } else {
            echo "   [失败] source_type: " . $mysqli->error . "\n";
            $failed++;
        }
    }

    // 3b. user_openid 字段
    $result = $mysqli->query("SHOW COLUMNS FROM `{$tblPortrait}` LIKE 'user_openid'");
    if ($result && $result->num_rows > 0) {
        echo "   [跳过] user_openid 字段已存在\n";
        $skipped++;
    } else {
        $ok = $mysqli->query("ALTER TABLE `{$tblPortrait}` ADD COLUMN `user_openid` varchar(64) NOT NULL DEFAULT '' COMMENT '用户自拍时的openid' AFTER `source_type`");
        if ($ok) {
            $mysqli->query("ALTER TABLE `{$tblPortrait}` ADD INDEX `idx_user_openid` (`user_openid`)");
            echo "   [成功] user_openid 字段和索引添加完成\n";
            $success++;
        } else {
            echo "   [失败] user_openid: " . $mysqli->error . "\n";
            $failed++;
        }
    }

    // 4. 扩展门店表字段
    $tblMendian = $prefix . 'mendian';
    echo "\n4. 扩展 {$tblMendian} 表字段...\n";

    $mendianFields = [
        'selfie_push_title' => "varchar(256) NOT NULL DEFAULT '' COMMENT '自拍推文标题'",
        'selfie_push_desc' => "varchar(512) NOT NULL DEFAULT '' COMMENT '自拍推文描述'",
        'selfie_push_cover' => "varchar(512) NOT NULL DEFAULT '' COMMENT '自拍推文封面图'",
        'selfie_enabled' => "tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '是否启用自拍端（0否/1是）'",
    ];

    foreach ($mendianFields as $field => $definition) {
        $result = $mysqli->query("SHOW COLUMNS FROM `{$tblMendian}` LIKE '{$field}'");
        if ($result && $result->num_rows > 0) {
            echo "   [跳过] {$field} 字段已存在\n";
            $skipped++;
        } else {
            if ($mysqli->query("ALTER TABLE `{$tblMendian}` ADD COLUMN `{$field}` {$definition}")) {
                echo "   [成功] {$field} 字段添加完成\n";
                $success++;
            } else {
                echo "   [失败] {$field}: " . $mysqli->error . "\n";
                $failed++;
            }
        }
    }

    echo "\n========================================\n";
    echo "  迁移完成\n";
    echo "  成功: {$success}  跳过: {$skipped}  失败: {$failed}\n";
    echo "========================================\n";

    $mysqli->close();

} catch (Exception $e) {
    echo "\n❌ 迁移失败：" . $e->getMessage() . "\n\n";
    exit(1);
}

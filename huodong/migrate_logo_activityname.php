<?php
/**
 * 迁移脚本：
 * 1. 在 wall_config 表新增 activity_name 字段（varchar(200)，默认空字符串）
 * 2. 在 system_config 表插入 show_logo 记录（默认值 1，即显示）
 * 访问此文件即可执行迁移
 */
@header("Content-type: text/html; charset=utf-8");

require_once dirname(__FILE__) . '/common/db.class.php';

$results = array();
$link = MysqliConnection::getlink();

// 1. 确保 weixin_wall_config 表存在
$table_check = mysqli_query($link, "SHOW TABLES LIKE 'weixin_wall_config'");
if (mysqli_num_rows($table_check) == 0) {
    $create_sql = "CREATE TABLE `weixin_wall_config` (
        `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
        `success` text NOT NULL COMMENT '消息发送成功但是没有审核时的提醒信息',
        `shenghe` int(11) NOT NULL COMMENT '0自动审柨1手动审核',
        `cjreplay` tinyint(4) NOT NULL DEFAULT '0',
        `timeinterval` int(3) NOT NULL DEFAULT '0',
        `shakeopen` tinyint(4) NOT NULL DEFAULT '1',
        `voteopen` tinyint(4) NOT NULL DEFAULT '1',
        `votetitle` text NOT NULL,
        `votefresht` tinyint(4) NOT NULL,
        `circulation` tinyint(1) NOT NULL DEFAULT '0',
        `refreshtime` tinyint(2) NOT NULL DEFAULT '0',
        `voteshowway` tinyint(1) DEFAULT '1',
        `votecannum` varchar(255) DEFAULT '1',
        `black_word` text,
        `screenpaw` varchar(255) NOT NULL DEFAULT 'admin',
        `rentweixin` tinyint(1) NOT NULL DEFAULT '0',
        `copyright` varchar(200) DEFAULT '',
        `copyrightlink` varchar(500) DEFAULT '',
        `msg_showstyle` tinyint(1) DEFAULT '0',
        `msg_historynum` int(3) DEFAULT '30',
        `msg_showbig` tinyint(1) DEFAULT '0',
        `msg_showbigtime` tinyint(3) DEFAULT '5',
        `verifycode` varchar(255) DEFAULT NULL,
        `maxplayers` int(11) unsigned DEFAULT '0',
        `msg_color` varchar(7) DEFAULT '#4B9E09',
        `nickname_color` varchar(7) DEFAULT '#4B9E09',
        `qrcodetoptext` varchar(255) DEFAULT '扫描下面的二维码参与签到',
        `msg_num` tinyint(1) DEFAULT '3',
        `isclosed` tinyint(1) DEFAULT '1',
        `logoimg` int(11) NOT NULL DEFAULT '0' COMMENT '活动LOGO图片附件ID',
        `bottom_logoimg` int(11) NOT NULL DEFAULT '0' COMMENT '底部LOGO图片附件ID',
        `activity_name` varchar(200) NOT NULL DEFAULT '' COMMENT '活动名称',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC";
    if (mysqli_query($link, $create_sql)) {
        $results[] = 'weixin_wall_config: 表创建成功';
        // 插入默认记录
        $wall_m = new M('wall_config');
        $wall_m->add(array(
            'success' => '你已经成功发送，等待审核通过即可上墙了',
            'shenghe' => 0,
            'votetitle' => '你最喜欢微信墙的哪个功能？',
            'votefresht' => 3,
            'screenpaw' => 'admin',
            'copyright' => '',
            'copyrightlink' => '',
            'activity_name' => ''
        ));
        $results[] = '默认wall_config记录: 已插入';
    } else {
        $results[] = 'weixin_wall_config: 表创建失败 - ' . mysqli_error($link);
    }
} else {
    // 表已存在，检查并添加 activity_name 字段
    $col_check = mysqli_query($link, "SHOW COLUMNS FROM `weixin_wall_config` LIKE 'activity_name'");
    if ($col_check && mysqli_num_rows($col_check) == 0) {
        $alter_result = mysqli_query($link, "ALTER TABLE `weixin_wall_config` ADD COLUMN `activity_name` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '活动名称'");
        $results[] = 'wall_config.activity_name: ' . ($alter_result ? '字段添加成功' : '添加失败 - ' . mysqli_error($link));
    } else {
        $results[] = 'wall_config.activity_name: 字段已存在，跳过';
    }
    // 检查并添加 logoimg 字段
    $col_check2 = mysqli_query($link, "SHOW COLUMNS FROM `weixin_wall_config` LIKE 'logoimg'");
    if ($col_check2 && mysqli_num_rows($col_check2) == 0) {
        mysqli_query($link, "ALTER TABLE `weixin_wall_config` ADD COLUMN `logoimg` INT(11) NOT NULL DEFAULT '0' COMMENT '活动LOGO图片附件ID'");
        $results[] = 'wall_config.logoimg: 字段添加成功';
    }
    // 检查并添加 bottom_logoimg 字段
    $col_check3 = mysqli_query($link, "SHOW COLUMNS FROM `weixin_wall_config` LIKE 'bottom_logoimg'");
    if ($col_check3 && mysqli_num_rows($col_check3) == 0) {
        mysqli_query($link, "ALTER TABLE `weixin_wall_config` ADD COLUMN `bottom_logoimg` INT(11) NOT NULL DEFAULT '0' COMMENT '底部LOGO图片附件ID'");
        $results[] = 'wall_config.bottom_logoimg: 字段添加成功';
    }
}

// 2. 确保 weixin_system_config 表存在
$table_check2 = mysqli_query($link, "SHOW TABLES LIKE 'weixin_system_config'");
if (mysqli_num_rows($table_check2) == 0) {
    $create_sql2 = "CREATE TABLE `weixin_system_config` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `configkey` varchar(50) NOT NULL DEFAULT '',
        `configvalue` varchar(500) NOT NULL DEFAULT '',
        `configname` varchar(50) NOT NULL DEFAULT '',
        `configcomment` varchar(200) NOT NULL DEFAULT '',
        PRIMARY KEY (`id`),
        KEY `configkey` (`configkey`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    if (mysqli_query($link, $create_sql2)) {
        $results[] = 'weixin_system_config: 表创建成功';
        $m = new M('system_config');
        $default_configs = array(
            array('configkey' => 'menucolor', 'configvalue' => '#dbb902', 'configname' => '菜单颜色', 'configcomment' => '16进制颜色代码'),
            array('configkey' => 'showcountsign', 'configvalue' => '2', 'configname' => '显示签到人数', 'configcomment' => '1不显示2显示'),
            array('configkey' => 'qrcodepos', 'configvalue' => '', 'configname' => '二维码位置', 'configcomment' => ''),
            array('configkey' => 'mobilemenufontcolor', 'configvalue' => '#000', 'configname' => '签到菜单文字颜色', 'configcomment' => ''),
            array('configkey' => 'show_company_name', 'configvalue' => '1', 'configname' => '显示公司名称', 'configcomment' => '1显示2隐藏'),
            array('configkey' => 'show_activity_name', 'configvalue' => '1', 'configname' => '显示活动名称', 'configcomment' => '1显示2隐藏'),
            array('configkey' => 'show_copyright', 'configvalue' => '1', 'configname' => '显示版权信息', 'configcomment' => '1显示2隐藏'),
            array('configkey' => 'show_logo', 'configvalue' => '1', 'configname' => '显示活动LOGO', 'configcomment' => '1显示2隐藏'),
        );
        foreach ($default_configs as $cfg) {
            $m->add($cfg);
        }
        $results[] = '默认配置: 已插入';
    } else {
        $results[] = 'weixin_system_config: 表创建失败 - ' . mysqli_error($link);
    }
}

// 3. 插入 show_logo 记录（如果表已存在但记录不存在）
$m = new M('system_config');
$existing = $m->find('configkey="show_logo"');
if (empty($existing)) {
    $config = array(
        'configkey' => 'show_logo',
        'configvalue' => '1',
        'configname' => '显示活动LOGO',
        'configcomment' => '1显示2隐藏'
    );
    $result = $m->add($config);
    $results[] = 'show_logo: ' . ($result ? '插入成功' : '插入失败');
} else {
    $results[] = 'show_logo: 已存在，跳过';
}

// 清除缓存
require_once dirname(__FILE__) . '/common/CacheFactory.php';
$cache = new CacheFactory(CACHEMODE);
$cache->delete('system_config');
$cache->delete('wall_config');

echo "<h3>活动LOGO与活动名称迁移结果</h3>";
echo "<ul>";
foreach ($results as $r) {
    echo "<li>" . $r . "</li>";
}
echo "</ul>";
echo "<p>缓存已清除。</p>";
echo "<p><a href='frame.php'>返回大屏</a> | <a href='myadmin/systemsettings.php'>系统设置</a></p>";

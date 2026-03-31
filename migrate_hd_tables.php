<?php
/**
 * 大屏互动系统 - 数据库迁移脚本
 * 创建所有 ddwx_hd_* 业务表
 * 使用方法: php migrate_hd_tables.php
 */

$config = include(__DIR__ . '/config.php');

$conn = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database'], (int)$config['hostport']);
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error . "\n");
}
$conn->set_charset('utf8mb4');

$sqls = [];

// ============================================================
// 1. 活动表
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0 COMMENT '平台账户ID',
  `bid` int(11) NOT NULL DEFAULT 0 COMMENT '商家ID',
  `mdid` int(11) NOT NULL DEFAULT 0 COMMENT '门店ID(0=未绑定)',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '活动标题',
  `access_code` varchar(16) NOT NULL COMMENT '活动访问码',
  `started_at` int(11) DEFAULT NULL COMMENT '开始时间',
  `ended_at` int(11) DEFAULT NULL COMMENT '结束时间',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1未开始 2进行中 3已结束',
  `verifycode` varchar(16) DEFAULT NULL COMMENT '活动验证码',
  `screen_config` text COMMENT '大屏全局配置JSON',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_access_code` (`access_code`),
  KEY `idx_aid_bid` (`aid`,`bid`),
  KEY `idx_bid_status` (`bid`,`status`),
  KEY `idx_mdid` (`mdid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-活动表';";

// ============================================================
// 2. 活动功能配置表
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_activity_feature` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0 COMMENT '活动ID',
  `feature_code` varchar(32) NOT NULL DEFAULT '' COMMENT '功能标识',
  `enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1启用 2禁用',
  `config` text COMMENT '功能配置JSON',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`),
  KEY `idx_aid_bid` (`aid`,`bid`),
  UNIQUE KEY `uk_activity_feature` (`activity_id`,`feature_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-活动功能配置';";

// ============================================================
// 3. 活动参与者表
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_participant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `mid` int(11) NOT NULL DEFAULT 0 COMMENT '会员ID',
  `openid` varchar(100) NOT NULL DEFAULT '' COMMENT '微信OpenID',
  `nickname` varchar(120) DEFAULT NULL COMMENT '昵称',
  `avatar` text COMMENT '头像URL',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `signname` varchar(32) NOT NULL DEFAULT '' COMMENT '签到姓名',
  `flag` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1未签到 2已签到',
  `signorder` int(11) DEFAULT NULL COMMENT '签到顺序',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1正常 2禁用',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`),
  KEY `idx_aid_bid` (`aid`,`bid`),
  KEY `idx_openid` (`openid`),
  KEY `idx_mid` (`mid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-活动参与者';";

// ============================================================
// 4. 微信上墙消息
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_wall_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `participant_id` int(11) NOT NULL DEFAULT 0 COMMENT '参与者ID',
  `openid` varchar(100) NOT NULL DEFAULT '',
  `nickname` varchar(120) DEFAULT NULL,
  `avatar` text,
  `content` text COMMENT '消息内容',
  `imgurl` text COMMENT '图片URL',
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1文字 2图片 3图文',
  `is_approved` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0待审 1通过 2拒绝',
  `is_top` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否置顶',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_approved` (`activity_id`,`is_approved`),
  KEY `idx_aid_bid` (`aid`,`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-上墙消息';";

// ============================================================
// 5. 抽奖轮次配置
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_lottery_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `round_name` varchar(50) NOT NULL DEFAULT '' COMMENT '轮次名称',
  `round_num` int(11) NOT NULL DEFAULT 1 COMMENT '轮次序号',
  `prize_id` int(11) NOT NULL DEFAULT 0 COMMENT '奖品ID',
  `win_num` int(11) NOT NULL DEFAULT 1 COMMENT '中奖人数',
  `is_repeat` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否可重复中奖',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1待抽 2已抽',
  `winners` text COMMENT '中奖者JSON',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`),
  KEY `idx_aid_bid` (`aid`,`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-抽奖轮次配置';";

// ============================================================
// 6. 抽奖主题
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_lottery_theme` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(50) NOT NULL DEFAULT '',
  `bg_image` text COMMENT '背景图',
  `config` text COMMENT '主题配置JSON',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-抽奖主题';";

// ============================================================
// 7. 奖品管理
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_prize` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '奖品名称',
  `image` text COMMENT '奖品图片',
  `total_num` int(11) NOT NULL DEFAULT 0 COMMENT '总数量',
  `used_num` int(11) NOT NULL DEFAULT 0 COMMENT '已用数量',
  `sort` int(11) NOT NULL DEFAULT 0,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`),
  KEY `idx_aid_bid` (`aid`,`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-奖品管理';";

// ============================================================
// 8. 手机端抽奖配置
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_choujiang_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `max_times` int(11) NOT NULL DEFAULT 1 COMMENT '每人最多抽奖次数',
  `bg_image` text,
  `config` text COMMENT '配置JSON',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-手机抽奖配置';";

// ============================================================
// 9. 手机端抽奖用户次数
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_choujiang_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `participant_id` int(11) NOT NULL DEFAULT 0,
  `openid` varchar(100) NOT NULL DEFAULT '',
  `used_times` int(11) NOT NULL DEFAULT 0 COMMENT '已用次数',
  `prize_id` int(11) NOT NULL DEFAULT 0 COMMENT '中奖奖品ID(0未中)',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_openid` (`activity_id`,`openid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-手机抽奖用户';";

// ============================================================
// 10. 摇一摇配置
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_shake_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `duration` int(11) NOT NULL DEFAULT 30 COMMENT '持续时间(秒)',
  `max_winners` int(11) NOT NULL DEFAULT 10 COMMENT '获奖名次数',
  `max_participants` int(11) NOT NULL DEFAULT 0 COMMENT '最大参与人数(0不限)',
  `prize_id` int(11) NOT NULL DEFAULT 0 COMMENT '奖品ID',
  `bg_image` text,
  `config` text COMMENT '配置JSON',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1待开始 2进行中 3已结束',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-摇一摇配置';";

// ============================================================
// 11. 摇一摇记录
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_shake_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `shake_config_id` int(11) NOT NULL DEFAULT 0,
  `participant_id` int(11) NOT NULL DEFAULT 0,
  `openid` varchar(100) NOT NULL DEFAULT '',
  `nickname` varchar(120) DEFAULT NULL,
  `avatar` text,
  `score` int(11) NOT NULL DEFAULT 0 COMMENT '摇一摇分数',
  `rank` int(11) NOT NULL DEFAULT 0 COMMENT '排名',
  `is_winner` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否获奖',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shake_config` (`shake_config_id`),
  KEY `idx_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-摇一摇记录';";

// ============================================================
// 12. 摇一摇主题
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_shake_theme` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(50) NOT NULL DEFAULT '',
  `bg_image` text,
  `config` text,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-摇一摇主题';";

// ============================================================
// 13. 游戏配置
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_game_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `game_type` varchar(32) NOT NULL DEFAULT '' COMMENT '游戏类型',
  `duration` int(11) NOT NULL DEFAULT 30,
  `max_winners` int(11) NOT NULL DEFAULT 10,
  `prize_id` int(11) NOT NULL DEFAULT 0,
  `bg_image` text,
  `config` text,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-游戏配置';";

// ============================================================
// 14. 游戏记录
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_game_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `game_config_id` int(11) NOT NULL DEFAULT 0,
  `participant_id` int(11) NOT NULL DEFAULT 0,
  `openid` varchar(100) NOT NULL DEFAULT '',
  `nickname` varchar(120) DEFAULT NULL,
  `avatar` text,
  `score` int(11) NOT NULL DEFAULT 0,
  `rank` int(11) NOT NULL DEFAULT 0,
  `is_winner` tinyint(1) NOT NULL DEFAULT 0,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_game_config` (`game_config_id`),
  KEY `idx_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-游戏记录';";

// ============================================================
// 15. 游戏主题
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_game_theme` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `game_type` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(50) NOT NULL DEFAULT '',
  `bg_image` text,
  `config` text,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-游戏主题';";

// ============================================================
// 16. 猴子爬树配置
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_pashu_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `duration` int(11) NOT NULL DEFAULT 30,
  `max_winners` int(11) NOT NULL DEFAULT 10,
  `prize_id` int(11) NOT NULL DEFAULT 0,
  `config` text,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-猴子爬树配置';";

// ============================================================
// 17. 猴子爬树记录
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_pashu_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `pashu_config_id` int(11) NOT NULL DEFAULT 0,
  `participant_id` int(11) NOT NULL DEFAULT 0,
  `openid` varchar(100) NOT NULL DEFAULT '',
  `nickname` varchar(120) DEFAULT NULL,
  `score` int(11) NOT NULL DEFAULT 0,
  `rank` int(11) NOT NULL DEFAULT 0,
  `is_winner` tinyint(1) NOT NULL DEFAULT 0,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pashu_config` (`pashu_config_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-爬树记录';";

// ============================================================
// 18. 数钱游戏配置
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_shuqian_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `duration` int(11) NOT NULL DEFAULT 30,
  `max_winners` int(11) NOT NULL DEFAULT 10,
  `prize_id` int(11) NOT NULL DEFAULT 0,
  `config` text,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-数钱配置';";

// ============================================================
// 19. 数钱游戏记录
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_shuqian_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `shuqian_config_id` int(11) NOT NULL DEFAULT 0,
  `participant_id` int(11) NOT NULL DEFAULT 0,
  `openid` varchar(100) NOT NULL DEFAULT '',
  `nickname` varchar(120) DEFAULT NULL,
  `score` int(11) NOT NULL DEFAULT 0,
  `rank` int(11) NOT NULL DEFAULT 0,
  `is_winner` tinyint(1) NOT NULL DEFAULT 0,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shuqian_config` (`shuqian_config_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-数钱记录';";

// ============================================================
// 20. 红包配置
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_redpacket_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '总金额',
  `total_num` int(11) NOT NULL DEFAULT 0 COMMENT '红包总数',
  `min_amount` decimal(10,2) NOT NULL DEFAULT 0.01 COMMENT '最小金额',
  `max_amount` decimal(10,2) NOT NULL DEFAULT 10.00 COMMENT '最大金额',
  `duration` int(11) NOT NULL DEFAULT 30 COMMENT '持续时间(秒)',
  `config` text,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-红包配置';";

// ============================================================
// 21. 红包轮次
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_redpacket_round` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `redpacket_config_id` int(11) NOT NULL DEFAULT 0,
  `round_num` int(11) NOT NULL DEFAULT 1,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0,
  `sent_amount` decimal(10,2) NOT NULL DEFAULT 0,
  `total_num` int(11) NOT NULL DEFAULT 0,
  `sent_num` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1待开始 2进行中 3已结束',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`),
  KEY `idx_config_id` (`redpacket_config_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-红包轮次';";

// ============================================================
// 22. 红包中奖记录
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_redpacket_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `round_id` int(11) NOT NULL DEFAULT 0,
  `participant_id` int(11) NOT NULL DEFAULT 0,
  `openid` varchar(100) NOT NULL DEFAULT '',
  `nickname` varchar(120) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '中奖金额',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1待发放 2已发放 3发放失败',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_round_id` (`round_id`),
  KEY `idx_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-红包中奖';";

// ============================================================
// 23. 红包订单
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_redpacket_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `redpacket_user_id` int(11) NOT NULL DEFAULT 0,
  `order_no` varchar(64) NOT NULL DEFAULT '',
  `amount` decimal(10,2) NOT NULL DEFAULT 0,
  `pay_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0未支付 1已支付',
  `pay_time` int(11) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_no` (`order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-红包订单';";

// ============================================================
// 24. 投票选项
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_vote_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '选项标题',
  `image` text COMMENT '选项图片',
  `vote_count` int(11) NOT NULL DEFAULT 0 COMMENT '票数',
  `sort` int(11) NOT NULL DEFAULT 0,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-投票选项';";

// ============================================================
// 25. 投票记录
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_vote_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `vote_item_id` int(11) NOT NULL DEFAULT 0,
  `participant_id` int(11) NOT NULL DEFAULT 0,
  `openid` varchar(100) NOT NULL DEFAULT '',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_item` (`activity_id`,`vote_item_id`),
  KEY `idx_openid` (`openid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-投票记录';";

// ============================================================
// 26. 弹幕配置
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_danmu_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `speed` int(11) NOT NULL DEFAULT 5 COMMENT '弹幕速度',
  `font_size` int(11) NOT NULL DEFAULT 24 COMMENT '字体大小',
  `opacity` decimal(3,2) NOT NULL DEFAULT 0.80 COMMENT '透明度',
  `config` text,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-弹幕配置';";

// ============================================================
// 27. 附件/资源管理
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_attachment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `file_name` varchar(200) NOT NULL DEFAULT '',
  `file_path` text,
  `file_type` varchar(20) NOT NULL DEFAULT '',
  `file_size` int(11) NOT NULL DEFAULT 0,
  `category` varchar(32) NOT NULL DEFAULT '' COMMENT '分类:background/music/prize等',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`),
  KEY `idx_aid_bid` (`aid`,`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-附件管理';";

// ============================================================
// 28. 背景图配置
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_background` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `feature_code` varchar(32) NOT NULL DEFAULT '' COMMENT '功能标识',
  `image_url` text,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `sort` int(11) NOT NULL DEFAULT 0,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_feature` (`activity_id`,`feature_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-背景图';";

// ============================================================
// 29. 背景音乐
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_music` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(100) NOT NULL DEFAULT '',
  `file_url` text,
  `duration` int(11) NOT NULL DEFAULT 0 COMMENT '时长(秒)',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `sort` int(11) NOT NULL DEFAULT 0,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-背景音乐';";

// ============================================================
// 30. 功能插件开关
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_plug` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `plug_code` varchar(32) NOT NULL DEFAULT '' COMMENT '插件标识',
  `plug_name` varchar(50) NOT NULL DEFAULT '',
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `config` text,
  `sort` int(11) NOT NULL DEFAULT 0,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-插件开关';";

// ============================================================
// 31. 导入抽奖数据
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_importlottery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `code` varchar(50) NOT NULL DEFAULT '' COMMENT '编号',
  `extra` text COMMENT '扩展数据JSON',
  `is_winner` tinyint(1) NOT NULL DEFAULT 0,
  `prize_id` int(11) NOT NULL DEFAULT 0,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-导入抽奖';";

// ============================================================
// 32. 开幕墙配置
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_kaimu_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `title` varchar(100) NOT NULL DEFAULT '',
  `subtitle` varchar(200) NOT NULL DEFAULT '',
  `bg_image` text,
  `video_url` text,
  `config` text,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-开幕墙配置';";

// ============================================================
// 33. 闭幕墙配置
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_bimu_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `activity_id` int(11) NOT NULL DEFAULT 0,
  `title` varchar(100) NOT NULL DEFAULT '',
  `subtitle` varchar(200) NOT NULL DEFAULT '',
  `bg_image` text,
  `config` text,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-闭幕墙配置';";

// ============================================================
// 34. 套餐表
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_plan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '套餐名称',
  `price` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '价格(元)',
  `duration_days` int(11) NOT NULL DEFAULT 0 COMMENT '有效天数',
  `max_stores` int(11) NOT NULL DEFAULT 1 COMMENT '最大门店数',
  `max_activities` int(11) NOT NULL DEFAULT 1 COMMENT '最大活动数',
  `max_participants` int(11) NOT NULL DEFAULT 100 COMMENT '单活动最大参与人数',
  `features` text COMMENT '可用功能列表(逗号分隔)',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1上架 2下架',
  `sort` int(11) NOT NULL DEFAULT 0,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_aid` (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-套餐';";

// ============================================================
// 35. 套餐订单表
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_plan_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0 COMMENT '商家ID',
  `plan_id` int(11) NOT NULL DEFAULT 0 COMMENT '套餐ID',
  `plan_name` varchar(50) NOT NULL DEFAULT '',
  `order_no` varchar(64) NOT NULL DEFAULT '' COMMENT '订单号',
  `amount` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '支付金额',
  `pay_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0未付 1已付',
  `pay_time` int(11) DEFAULT NULL,
  `start_time` int(11) DEFAULT NULL COMMENT '生效时间',
  `end_time` int(11) DEFAULT NULL COMMENT '到期时间',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bid` (`bid`),
  KEY `idx_order_no` (`order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-套餐订单';";

// ============================================================
// 36. 商家扩展配置表(大屏互动专用)
// ============================================================
$sqls[] = "CREATE TABLE IF NOT EXISTS `ddwx_hd_business_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `bid` int(11) NOT NULL DEFAULT 0,
  `plan_id` int(11) NOT NULL DEFAULT 0 COMMENT '当前套餐ID',
  `plan_expire_time` int(11) DEFAULT NULL COMMENT '套餐到期时间',
  `trial_used` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已用试用',
  `wxfw_appid` varchar(100) NOT NULL DEFAULT '' COMMENT '自有公众号AppID',
  `wxfw_appsecret` varchar(200) NOT NULL DEFAULT '' COMMENT '自有公众号Secret',
  `createtime` int(11) DEFAULT NULL,
  `updatetime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bid` (`bid`),
  KEY `idx_aid` (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='大屏互动-商家扩展配置';";

// ============================================================
// 执行迁移
// ============================================================
echo "======================================\n";
echo "  大屏互动系统 - 数据库迁移\n";
echo "======================================\n\n";

$success = 0;
$failed = 0;

foreach ($sqls as $index => $sql) {
    // 从 SQL 中提取表名
    preg_match('/CREATE TABLE IF NOT EXISTS `(\w+)`/', $sql, $matches);
    $tableName = $matches[1] ?? "第" . ($index + 1) . "个表";

    if ($conn->query($sql) === TRUE) {
        echo "[OK] {$tableName} 创建成功\n";
        $success++;
    } else {
        echo "[FAIL] {$tableName} 创建失败: " . $conn->error . "\n";
        $failed++;
    }
}

// 插入默认套餐数据
echo "\n--- 插入默认套餐数据 ---\n";

$checkPlan = $conn->query("SELECT COUNT(*) as cnt FROM ddwx_hd_plan");
$row = $checkPlan->fetch_assoc();
if ($row['cnt'] == 0) {
    $planSqls = [
        "INSERT INTO `ddwx_hd_plan` (`aid`,`name`,`price`,`duration_days`,`max_stores`,`max_activities`,`max_participants`,`features`,`status`,`sort`,`createtime`) VALUES
        (0,'试用版',0.00,7,1,1,50,'qdq,wall,lottery,vote',1,100," . time() . ")",
        "INSERT INTO `ddwx_hd_plan` (`aid`,`name`,`price`,`duration_days`,`max_stores`,`max_activities`,`max_participants`,`features`,`status`,`sort`,`createtime`) VALUES
        (0,'基础版',299.00,365,3,5,200,'qdq,threedimensionalsign,wall,danmu,vote,lottery,choujiang',1,90," . time() . ")",
        "INSERT INTO `ddwx_hd_plan` (`aid`,`name`,`price`,`duration_days`,`max_stores`,`max_activities`,`max_participants`,`features`,`status`,`sort`,`createtime`) VALUES
        (0,'专业版',599.00,365,10,20,500,'qdq,threedimensionalsign,wall,danmu,vote,lottery,choujiang,ydj,shake,game,redpacket,importlottery,kaimu,bimu,xiangce,xyh,xysjh',1,80," . time() . ")",
        "INSERT INTO `ddwx_hd_plan` (`aid`,`name`,`price`,`duration_days`,`max_stores`,`max_activities`,`max_participants`,`features`,`status`,`sort`,`createtime`) VALUES
        (0,'企业版',1299.00,365,50,100,2000,'qdq,threedimensionalsign,wall,danmu,vote,lottery,choujiang,ydj,shake,game,redpacket,importlottery,kaimu,bimu,xiangce,xyh,xysjh',1,70," . time() . ")"
    ];
    foreach ($planSqls as $psql) {
        if ($conn->query($psql)) {
            echo "[OK] 套餐数据插入成功\n";
        } else {
            echo "[FAIL] 套餐数据插入失败: " . $conn->error . "\n";
        }
    }
} else {
    echo "[SKIP] 套餐数据已存在，跳过\n";
}

echo "\n======================================\n";
echo "  迁移完成: 成功 {$success}, 失败 {$failed}\n";
echo "======================================\n";

$conn->close();

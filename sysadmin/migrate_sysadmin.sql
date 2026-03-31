-- ========================================================
-- 授权管控系统数据库迁移脚本
-- 数据库：guobaosysadmin
-- 表前缀：sa_
-- ========================================================

CREATE DATABASE IF NOT EXISTS `guobaosysadmin` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `guobaosysadmin`;

-- ---------------------------------------------------
-- 独立管理员表
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `sa_admin` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '登录账号',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码（bcrypt哈希）',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '显示昵称',
  `role` tinyint(1) NOT NULL DEFAULT 1 COMMENT '角色：1=超级管理员 2=运营',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=禁用 1=启用',
  `last_login_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '最后登录时间',
  `last_login_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='授权系统管理员表';

-- 插入默认超级管理员（密码: admin123456）
INSERT INTO `sa_admin` (`username`, `password`, `nickname`, `role`, `status`, `create_time`, `update_time`)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '超级管理员', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ---------------------------------------------------
-- 套餐等级表
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `sa_license_edition` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '套餐名称',
  `code` varchar(20) NOT NULL DEFAULT '' COMMENT '套餐代码(basic/pro/premium)',
  `features` text COMMENT '功能清单（JSON）',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '价格',
  `duration_days` int(11) NOT NULL DEFAULT 365 COMMENT '默认有效天数',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=禁用 1=启用',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='授权套餐等级表';

-- 初始化套餐数据
INSERT INTO `sa_license_edition` (`name`, `code`, `features`, `price`, `duration_days`, `sort`, `status`, `create_time`, `update_time`) VALUES
('基础版', 'basic', '["基础商城功能","微信支付","订单管理"]', 1980.00, 365, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('专业版', 'pro', '["全部基础版功能","多商户","分销系统","营销插件"]', 4980.00, 365, 2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('旗舰版', 'premium', '["全部专业版功能","AI功能","视频号","定制开发支持"]', 9980.00, 365, 3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ---------------------------------------------------
-- 授权主表
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `sa_license` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `license_code` varchar(32) NOT NULL DEFAULT '' COMMENT '授权码明文（32位）',
  `license_cipher` text COMMENT '加密混淆后的授权码密文',
  `domain` varchar(255) NOT NULL DEFAULT '' COMMENT '绑定域名',
  `domain_hash` varchar(64) NOT NULL DEFAULT '' COMMENT '域名哈希索引',
  `edition_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '套餐等级ID',
  `contact_name` varchar(50) NOT NULL DEFAULT '' COMMENT '联系人',
  `contact_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `contact_company` varchar(100) NOT NULL DEFAULT '' COMMENT '公司名称',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0=未激活 1=正常 2=已吊销 3=已过期 4=盗版封禁',
  `activate_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '首次激活时间',
  `expire_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '到期时间（0永久）',
  `server_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '绑定服务器公网IP',
  `server_mac` varchar(50) NOT NULL DEFAULT '' COMMENT '绑定服务器MAC地址',
  `mac_hash` varchar(64) NOT NULL DEFAULT '' COMMENT 'MAC哈希索引',
  `hardware_fingerprint` varchar(128) NOT NULL DEFAULT '' COMMENT '硬件综合指纹',
  `file_hash` varchar(128) NOT NULL DEFAULT '' COMMENT '最近一次文件指纹',
  `last_heartbeat` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '最后心跳时间',
  `last_version` varchar(20) NOT NULL DEFAULT '' COMMENT '最后上报版本',
  `hmac_secret` varchar(64) NOT NULL DEFAULT '' COMMENT '实例专属HMAC密钥',
  `encrypt_key` varchar(64) NOT NULL DEFAULT '' COMMENT '实例授权码加密密钥',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_license_code` (`license_code`),
  KEY `idx_domain_hash` (`domain_hash`),
  KEY `idx_mac_hash` (`mac_hash`),
  KEY `idx_status` (`status`),
  KEY `idx_expire_time` (`expire_time`),
  KEY `idx_edition_id` (`edition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='授权主表';

-- ---------------------------------------------------
-- 黑名单表
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `sa_blacklist` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) NOT NULL DEFAULT '' COMMENT '域名',
  `domain_hash` varchar(64) NOT NULL DEFAULT '' COMMENT '域名哈希索引',
  `ip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP地址',
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '类型：1=域名拉黑 2=IP拉黑',
  `reason` varchar(500) NOT NULL DEFAULT '' COMMENT '拉黑原因',
  `source_license_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联授权ID',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '拉黑时间',
  `expire_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '解封时间（0=永久）',
  PRIMARY KEY (`id`),
  KEY `idx_domain_hash` (`domain_hash`),
  KEY `idx_ip` (`ip`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='黑名单表';

-- ---------------------------------------------------
-- 心跳日志表
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `sa_heartbeat_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `license_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '授权ID',
  `domain` varchar(255) NOT NULL DEFAULT '' COMMENT '上报域名',
  `server_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '上报IP',
  `server_mac` varchar(50) NOT NULL DEFAULT '' COMMENT '上报MAC',
  `version` varchar(20) NOT NULL DEFAULT '' COMMENT '上报版本',
  `file_hash` varchar(128) NOT NULL DEFAULT '' COMMENT '文件指纹',
  `verify_result` tinyint(1) NOT NULL DEFAULT 0 COMMENT '验证结果：1=通过 0=失败',
  `fail_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '失败原因',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '上报时间',
  PRIMARY KEY (`id`),
  KEY `idx_license_id` (`license_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='心跳日志表';

-- ---------------------------------------------------
-- 激活日志表
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `sa_activation_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `license_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '授权ID',
  `domain` varchar(255) NOT NULL DEFAULT '' COMMENT '激活域名',
  `server_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '激活IP',
  `server_mac` varchar(50) NOT NULL DEFAULT '' COMMENT '激活MAC地址',
  `server_info` text COMMENT '服务器环境信息（JSON）',
  `result` tinyint(1) NOT NULL DEFAULT 0 COMMENT '结果：1=成功 0=失败',
  `fail_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '失败原因',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '激活时间',
  PRIMARY KEY (`id`),
  KEY `idx_license_id` (`license_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='激活日志表';

-- ---------------------------------------------------
-- 盗版告警表
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `sa_piracy_alert` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `license_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联授权ID',
  `domain` varchar(255) NOT NULL DEFAULT '' COMMENT '可疑域名',
  `server_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '可疑IP',
  `server_mac` varchar(50) NOT NULL DEFAULT '' COMMENT '可疑MAC地址',
  `alert_type` tinyint(2) NOT NULL DEFAULT 0 COMMENT '告警类型：1=域名不匹配 2=文件篡改 3=多实例部署 4=黑名单访问 5=MAC不匹配 6=客户端自动上报',
  `source` tinyint(1) NOT NULL DEFAULT 1 COMMENT '来源：1=管控端检测 2=客户端自动上报 3=用户举报',
  `detail` text COMMENT '详情（JSON）',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '处理状态：0=未处理 1=已确认盗版 2=误报 3=已批量封禁',
  `auto_synced` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已自动同步到黑名单：0=否 1=是',
  `handle_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '处理时间',
  `handle_remark` varchar(500) NOT NULL DEFAULT '' COMMENT '处理备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '告警时间',
  PRIMARY KEY (`id`),
  KEY `idx_license_id` (`license_id`),
  KEY `idx_status` (`status`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='盗版告警表';

-- ---------------------------------------------------
-- 升级版本表
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `sa_upgrade_version` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(20) NOT NULL DEFAULT '' COMMENT '版本号（语义化版本）',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '版本标题',
  `changelog` text COMMENT '更新日志',
  `package_path` varchar(500) NOT NULL DEFAULT '' COMMENT '升级包存储路径',
  `package_hash` varchar(128) NOT NULL DEFAULT '' COMMENT '升级包文件哈希',
  `package_size` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '包大小（字节）',
  `is_force` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否强制升级',
  `min_version` varchar(20) NOT NULL DEFAULT '' COMMENT '最低适用版本',
  `target_editions` varchar(500) NOT NULL DEFAULT '[]' COMMENT '适用套餐（JSON数组）',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0=草稿 1=已发布 2=已撤回',
  `publish_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '发布时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='升级版本表';

-- ---------------------------------------------------
-- 升级下载日志表
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `sa_upgrade_download_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `license_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '授权ID',
  `version_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '版本ID',
  `from_version` varchar(20) NOT NULL DEFAULT '' COMMENT '升级前版本',
  `result` tinyint(1) NOT NULL DEFAULT 0 COMMENT '结果：1=成功 0=失败',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '下载时间',
  PRIMARY KEY (`id`),
  KEY `idx_license_id` (`license_id`),
  KEY `idx_version_id` (`version_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='升级下载日志表';

-- ---------------------------------------------------
-- 系统设置表（键值对）
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `sa_setting` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL DEFAULT '' COMMENT '设置键',
  `value` text COMMENT '设置值',
  `remark` varchar(200) NOT NULL DEFAULT '' COMMENT '备注',
  `update_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统设置表';

-- 初始化系统设置
INSERT INTO `sa_setting` (`key`, `value`, `remark`, `update_time`) VALUES
('heartbeat_interval', '21600', '心跳周期（秒）', UNIX_TIMESTAMP()),
('alert_email', '', '告警通知邮箱', UNIX_TIMESTAMP()),
('auto_blacklist_threshold', '80', '自动拉黑评分阈值', UNIX_TIMESTAMP()),
('file_baseline_hash', '', '当前版本官方文件基线指纹', UNIX_TIMESTAMP());

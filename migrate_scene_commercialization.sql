-- 场景模板商品化设置字段迁移SQL
-- 为ddwx_generation_scene_template表添加分销、分红、积分抵扣、显示/购买条件等字段
-- 执行前请先备份数据库

-- ========== 分销设置字段 ==========
-- commissionset - 分销模式
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `commissionset` tinyint(2) DEFAULT '0' COMMENT '分销模式：0按会员等级 1价格比例 2固定金额 3分销送积分 -1不参与分销' AFTER `lvprice_data`;

-- commissiondata1 - 按比例分销参数
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `commissiondata1` text COMMENT '按比例分销参数（JSON序列化）' AFTER `commissionset`;

-- commissiondata2 - 按固定金额参数
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `commissiondata2` text COMMENT '按固定金额参数（JSON序列化）' AFTER `commissiondata1`;

-- commissiondata3 - 分销送积分参数
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `commissiondata3` text COMMENT '分销送积分参数（JSON序列化）' AFTER `commissiondata2`;

-- commissionset4 - 极差分销开关
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `commissionset4` tinyint(1) DEFAULT '0' COMMENT '是否开启极差分销：0关闭 1开启' AFTER `commissiondata3`;

-- ========== 分红设置字段 ==========
-- fenhongset - 分红总开关
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `fenhongset` tinyint(1) DEFAULT '1' COMMENT '是否参与分红：0不参与 1参与' AFTER `commissionset4`;

-- ========== 团队分红字段 ==========
-- teamfenhongset - 团队分红模式
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `teamfenhongset` tinyint(2) DEFAULT '0' COMMENT '团队分红模式：0按等级 1比例 2金额 3送积分 -1不参与' AFTER `fenhongset`;

-- teamfenhongdata1 - 团队分红比例参数
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `teamfenhongdata1` text COMMENT '团队分红比例参数' AFTER `teamfenhongset`;

-- teamfenhongdata2 - 团队分红金额参数
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `teamfenhongdata2` text COMMENT '团队分红金额参数' AFTER `teamfenhongdata1`;

-- ========== 股东分红字段 ==========
-- gdfenhongset - 股东分红模式
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `gdfenhongset` tinyint(2) DEFAULT '0' COMMENT '股东分红模式：0按等级 1比例 2金额 3送积分 -1不参与' AFTER `teamfenhongdata2`;

-- gdfenhongdata1 - 股东分红比例参数
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `gdfenhongdata1` text COMMENT '股东分红比例参数' AFTER `gdfenhongset`;

-- gdfenhongdata2 - 股东分红金额参数
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `gdfenhongdata2` text COMMENT '股东分红金额参数' AFTER `gdfenhongdata1`;

-- ========== 区域代理分红字段 ==========
-- areafenhongset - 区域分红模式
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `areafenhongset` tinyint(2) DEFAULT '0' COMMENT '区域分红模式：0按等级 1比例 2金额 -1不参与' AFTER `gdfenhongdata2`;

-- areafenhongdata1 - 区域分红比例参数
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `areafenhongdata1` text COMMENT '区域分红比例参数' AFTER `areafenhongset`;

-- areafenhongdata2 - 区域分红金额参数
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `areafenhongdata2` text COMMENT '区域分红金额参数' AFTER `areafenhongdata1`;

-- ========== 积分抵扣字段 ==========
-- scoredkmaxset - 积分抵扣设置
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `scoredkmaxset` tinyint(1) DEFAULT '0' COMMENT '积分抵扣设置：0按系统设置 1单独设置比例 2单独设置金额 -1不可抵扣' AFTER `areafenhongdata2`;

-- scoredkmaxval - 积分抵扣最大值
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `scoredkmaxval` decimal(11,2) DEFAULT '0.00' COMMENT '积分抵扣最大值（比例/金额）' AFTER `scoredkmaxset`;

-- ========== 显示/购买条件字段 ==========
-- showtj - 显示条件
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `showtj` varchar(255) DEFAULT '-1' COMMENT '显示条件：-1不限 其他为等级ID逗号分隔' AFTER `scoredkmaxval`;

-- gettj - 购买条件
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `gettj` varchar(255) DEFAULT '-1' COMMENT '购买条件：-1不限 0关注用户 其他为等级ID' AFTER `showtj`;

-- gettjurl - 不满足购买条件时的跳转链接
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `gettjurl` varchar(255) DEFAULT NULL COMMENT '不满足购买条件时的跳转链接' AFTER `gettj`;

-- gettjtip - 不满足购买条件时的提示文案
ALTER TABLE `ddwx_generation_scene_template` ADD COLUMN IF NOT EXISTS `gettjtip` varchar(255) DEFAULT NULL COMMENT '不满足购买条件时的提示文案' AFTER `gettjurl`;

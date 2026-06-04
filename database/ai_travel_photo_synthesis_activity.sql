-- AI旅拍合成模板活动二维码表
-- 用于存储合成模板的活动配置及其二维码，用户扫码后进入自助生成流程
CREATE TABLE IF NOT EXISTS `ddwx_ai_travel_photo_synthesis_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '商户ID',
  `bid` int(11) NOT NULL DEFAULT '0' COMMENT '门店ID',
  `template_id` int(11) NOT NULL DEFAULT '0' COMMENT '合成模板ID',
  `qrcode_token` varchar(64) NOT NULL DEFAULT '' COMMENT '二维码唯一标识',
  `qrcode_url` varchar(500) NOT NULL DEFAULT '' COMMENT '二维码图片URL',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '活动名称',
  `scan_count` int(11) NOT NULL DEFAULT '0' COMMENT '扫码次数',
  `unique_scan_count` int(11) NOT NULL DEFAULT '0' COMMENT '独立扫码用户数',
  `gen_count` int(11) NOT NULL DEFAULT '0' COMMENT '生成次数',
  `order_count` int(11) NOT NULL DEFAULT '0' COMMENT '付费订单数',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '付费总额',
  `price` decimal(10,2) NOT NULL DEFAULT '9.90' COMMENT '下载单价',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：0禁用 1启用',
  `expire_time` int(11) NOT NULL DEFAULT '0' COMMENT '过期时间，0表示永不过期',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token` (`qrcode_token`),
  KEY `idx_aid_bid` (`aid`,`bid`),
  KEY `idx_template_id` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI旅拍合成模板活动二维码表';

-- AI旅拍合成活动用户照片生成记录表
-- 记录用户扫码后在活动中的照片上传、标签识别、提示词改写、AI生成、支付全流程状态
CREATE TABLE IF NOT EXISTS `ddwx_ai_travel_photo_synthesis_user_photo` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `activity_id` int(11) NOT NULL DEFAULT '0' COMMENT '活动ID',
  `openid` varchar(64) NOT NULL DEFAULT '' COMMENT '用户OpenID',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '会员ID',
  `photo_url` varchar(500) NOT NULL DEFAULT '' COMMENT '用户上传照片URL',
  `tag_gender` varchar(20) NOT NULL DEFAULT '' COMMENT '检测性别 Male/Female/Unknown',
  `tag_age_group` varchar(20) NOT NULL DEFAULT '' COMMENT '检测年龄段',
  `tag_is_multi` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否多人 0单人 1多人',
  `tag_raw` text COMMENT '原始标签JSON',
  `tag_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '标签状态：0待识别 1识别中 2已完成 3失败',
  `rewritten_prompt` text COMMENT '改写后的提示词',
  `result_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联的生成结果ID（ai_travel_photo_result）',
  `result_url` varchar(500) NOT NULL DEFAULT '' COMMENT '生成结果URL（无水印原图）',
  `result_watermark_url` varchar(500) NOT NULL DEFAULT '' COMMENT '水印结果URL',
  `gen_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '生成状态：0待生成 1生成中 2已完成 3失败',
  `gen_error` varchar(500) NOT NULL DEFAULT '' COMMENT '生成失败原因',
  `order_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联订单ID',
  `paid` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已支付 0未支付 1已支付',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`),
  KEY `idx_openid` (`openid`),
  KEY `idx_gen_status` (`gen_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI旅拍合成活动用户照片生成记录表';

-- 合成模板表增加是否允许创建扫码活动字段
ALTER TABLE `ddwx_ai_travel_photo_synthesis_template`
ADD COLUMN `allow_qr_activity` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否允许创建扫码活动：0不允许 1允许';

-- 商户表(business)增加扫码活动相关配置字段
ALTER TABLE `ddwx_business`
ADD COLUMN `synthesis_qr_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启合成模板扫码活动',
ADD COLUMN `synthesis_qr_prompt_rewrite_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '扫码活动是否自动改写提示词',
ADD COLUMN `synthesis_qr_prompt_rewrite_provider` varchar(20) NOT NULL DEFAULT 'aliyun' COMMENT '改写提示词LLM供应商';

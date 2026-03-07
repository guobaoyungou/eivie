-- ============================================
-- AI模型管理系统数据库迁移脚本
-- 版本: 1.0.0
-- 创建时间: 2026-02-03
-- ============================================

-- 1. 创建模型分类表
CREATE TABLE IF NOT EXISTS `ddwx_ai_model_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '平台ID',
  `name` varchar(50) NOT NULL COMMENT '分类名称',
  `code` varchar(50) NOT NULL COMMENT '分类代码',
  `description` varchar(200) DEFAULT NULL COMMENT '分类描述',
  `icon` varchar(200) DEFAULT NULL COMMENT '分类图标URL',
  `is_system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否系统分类 1=系统 0=自定义',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序权重，值越大越靠前',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1=启用 0=禁用',
  `create_time` int(11) NOT NULL COMMENT '创建时间戳',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_code` (`code`),
  KEY `idx_aid_status` (`aid`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI模型分类表';

-- 2. 扩展现有模型配置表 ddwx_ai_travel_photo_model
ALTER TABLE `ddwx_ai_travel_photo_model` 
  ADD COLUMN `mdid` int(11) NOT NULL DEFAULT '0' COMMENT '门店ID，0=商家通用' AFTER `bid`,
  ADD COLUMN `category_code` varchar(50) DEFAULT NULL COMMENT '模型分类代码' AFTER `model_type`,
  ADD COLUMN `provider` varchar(50) DEFAULT NULL COMMENT '服务提供商' AFTER `category_code`,
  ADD COLUMN `max_concurrent` int(11) NOT NULL DEFAULT '5' COMMENT '最大并发数' AFTER `provider`,
  ADD COLUMN `current_concurrent` int(11) NOT NULL DEFAULT '0' COMMENT '当前并发数' AFTER `max_concurrent`,
  ADD COLUMN `priority` int(11) NOT NULL DEFAULT '100' COMMENT '优先级，值越大优先级越高' AFTER `current_concurrent`,
  ADD COLUMN `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否激活 1=激活 0=未激活' AFTER `is_default`,
  ADD COLUMN `test_passed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '连通性测试 1=通过 0=未通过' AFTER `is_active`,
  ADD COLUMN `last_test_time` int(11) DEFAULT NULL COMMENT '最后测试时间' AFTER `test_passed`,
  ADD COLUMN `last_error` varchar(500) DEFAULT NULL COMMENT '最后错误信息' AFTER `last_test_time`;

-- 添加索引
ALTER TABLE `ddwx_ai_travel_photo_model` 
  ADD KEY `idx_mdid` (`mdid`),
  ADD KEY `idx_category_code` (`category_code`),
  ADD KEY `idx_status_active` (`status`,`is_active`);

-- 3. 创建使用记录表
CREATE TABLE IF NOT EXISTS `ddwx_ai_model_usage_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '平台ID',
  `bid` int(11) NOT NULL DEFAULT '0' COMMENT '商家ID',
  `mdid` int(11) NOT NULL DEFAULT '0' COMMENT '门店ID',
  `model_id` int(11) NOT NULL COMMENT '模型配置ID',
  `category_code` varchar(50) DEFAULT NULL COMMENT '模型分类代码',
  `business_type` varchar(50) DEFAULT NULL COMMENT '业务类型 cutout/image_gen/video_gen',
  `request_params` text COMMENT '请求参数(JSON)',
  `response_data` text COMMENT '响应数据(JSON)',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '调用状态 1=成功 0=失败',
  `error_msg` varchar(500) DEFAULT NULL COMMENT '错误信息',
  `cost_amount` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '本次消耗金额',
  `response_time` int(11) DEFAULT NULL COMMENT '响应时长(毫秒)',
  `retry_count` tinyint(1) NOT NULL DEFAULT '0' COMMENT '重试次数',
  `create_time` int(11) NOT NULL COMMENT '创建时间戳',
  PRIMARY KEY (`id`),
  KEY `idx_aid_bid_mdid` (`aid`,`bid`,`mdid`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_category_code` (`category_code`),
  KEY `idx_business_type` (`business_type`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI模型使用记录表';

-- 4. 初始化系统预置分类数据
INSERT INTO `ddwx_ai_model_category` (`aid`, `name`, `code`, `description`, `icon`, `is_system`, `sort`, `status`, `create_time`) VALUES
(0, '千问', 'qianwen', '阿里云通义千问大模型', '🤖', 1, 100, 1, UNIX_TIMESTAMP()),
(0, '豆包', 'doubao', '字节跳动豆包大模型', '🎨', 1, 90, 1, UNIX_TIMESTAMP()),
(0, '可灵', 'kling', '快手可灵AI视频生成', '🎬', 1, 80, 1, UNIX_TIMESTAMP()),
(0, '即梦', 'jimeng', '即梦AI图像生成', '⚡', 1, 70, 1, UNIX_TIMESTAMP()),
(0, 'OpenAI', 'openai', 'OpenAI GPT系列模型', '🔧', 1, 60, 1, UNIX_TIMESTAMP()),
(0, 'Ollama', 'ollama', 'Ollama本地大模型', '🦙', 1, 50, 1, UNIX_TIMESTAMP()),
(0, '通义万相', 'tongyi_wanxiang', '阿里云通义万相图像生成', '✨', 1, 40, 1, UNIX_TIMESTAMP()),
(0, '其他', 'other', '其他自定义AI模型', '🔮', 1, 10, 1, UNIX_TIMESTAMP());

-- 5. 迁移现有数据的category_code字段
UPDATE `ddwx_ai_travel_photo_model` 
SET `category_code` = CASE 
  WHEN `model_type` LIKE '%tongyi%' OR `model_type` LIKE '%通义%' THEN 'tongyi_wanxiang'
  WHEN `model_type` LIKE '%kling%' OR `model_type` LIKE '%可灵%' THEN 'kling'
  WHEN `model_type` LIKE '%qianwen%' OR `model_type` LIKE '%千问%' THEN 'qianwen'
  WHEN `model_type` LIKE '%doubao%' OR `model_type` LIKE '%豆包%' THEN 'doubao'
  WHEN `model_type` LIKE '%openai%' THEN 'openai'
  ELSE 'other'
END
WHERE `category_code` IS NULL OR `category_code` = '';

-- 6. 创建成本配置字段（扩展模型配置表）
ALTER TABLE `ddwx_ai_travel_photo_model` 
  ADD COLUMN `image_price` decimal(10,4) NOT NULL DEFAULT '0.0500' COMMENT '图片单价(元)' AFTER `total_cost`,
  ADD COLUMN `video_price` decimal(10,4) NOT NULL DEFAULT '0.5000' COMMENT '视频单价(元)' AFTER `image_price`,
  ADD COLUMN `token_price` decimal(10,6) NOT NULL DEFAULT '0.000001' COMMENT 'Token单价(元)' AFTER `video_price`,
  ADD COLUMN `timeout` int(11) NOT NULL DEFAULT '180' COMMENT '请求超时(秒)' AFTER `token_price`,
  ADD COLUMN `max_retry` tinyint(1) NOT NULL DEFAULT '3' COMMENT '最大重试次数' AFTER `timeout`;

-- ============================================
-- 迁移脚本执行完成
-- ============================================

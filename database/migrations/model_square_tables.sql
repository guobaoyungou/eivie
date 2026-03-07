-- ============================================================
-- 模型广场功能 - 数据库表创建脚本
-- ============================================================

-- 1. 模型供应商表
CREATE TABLE IF NOT EXISTS `ddwx_model_provider` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '平台ID，0表示系统级',
  `provider_code` varchar(50) NOT NULL DEFAULT '' COMMENT '供应商唯一标识',
  `provider_name` varchar(100) NOT NULL DEFAULT '' COMMENT '供应商显示名称',
  `logo` varchar(255) NOT NULL DEFAULT '' COMMENT 'Logo图片地址',
  `website` varchar(255) NOT NULL DEFAULT '' COMMENT '官网地址',
  `api_doc_url` varchar(255) NOT NULL DEFAULT '' COMMENT 'API文档地址',
  `description` text COMMENT '供应商描述',
  `auth_config` json DEFAULT NULL COMMENT '认证配置模板',
  `is_system` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '是否系统预置(1=系统,0=自定义)',
  `status` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '状态(1=启用,0=禁用)',
  `sort` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '排序权重',
  `create_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provider_code` (`provider_code`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='模型供应商表';

-- 2. 模型类型表
CREATE TABLE IF NOT EXISTS `ddwx_model_type` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '平台ID，0表示系统级',
  `type_code` varchar(50) NOT NULL DEFAULT '' COMMENT '类型唯一标识',
  `type_name` varchar(100) NOT NULL DEFAULT '' COMMENT '类型显示名称',
  `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '类型图标',
  `description` text COMMENT '类型描述',
  `input_types` json DEFAULT NULL COMMENT '支持的输入类型列表',
  `output_types` json DEFAULT NULL COMMENT '支持的输出类型列表',
  `is_system` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '是否系统预置',
  `status` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '状态(1=启用,0=禁用)',
  `sort` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '排序',
  `create_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type_code` (`type_code`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='模型类型表';

-- 3. 模型信息主表
CREATE TABLE IF NOT EXISTS `ddwx_model_info` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '平台ID',
  `provider_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联供应商ID',
  `type_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联模型类型ID',
  `model_code` varchar(100) NOT NULL DEFAULT '' COMMENT '模型唯一标识',
  `model_name` varchar(200) NOT NULL DEFAULT '' COMMENT '模型显示名称',
  `model_version` varchar(50) NOT NULL DEFAULT '' COMMENT '模型版本号',
  `description` text COMMENT '模型功能描述',
  `input_schema` json DEFAULT NULL COMMENT '输入参数规范',
  `output_schema` json DEFAULT NULL COMMENT '输出格式规范',
  `endpoint_url` varchar(500) NOT NULL DEFAULT '' COMMENT 'API端点地址',
  `pricing_config` json DEFAULT NULL COMMENT '价格配置',
  `limits_config` json DEFAULT NULL COMMENT '限制配置',
  `task_type` varchar(50) NOT NULL DEFAULT 'sync' COMMENT '任务类型(sync/async)',
  `capability_tags` json DEFAULT NULL COMMENT '能力标签',
  `is_system` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '是否系统预置',
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '是否激活',
  `sort` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '排序',
  `create_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_model_code` (`model_code`),
  KEY `idx_provider_id` (`provider_id`),
  KEY `idx_type_id` (`type_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_task_type` (`task_type`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='模型信息主表';

-- 4. 商家模型配置表
CREATE TABLE IF NOT EXISTS `ddwx_merchant_model_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `model_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联模型ID',
  `aid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '平台ID',
  `bid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '商家ID，0表示平台级',
  `api_key` varchar(500) NOT NULL DEFAULT '' COMMENT 'API密钥(加密存储)',
  `api_secret` varchar(500) NOT NULL DEFAULT '' COMMENT 'API密钥Secret(加密存储)',
  `extra_config` json DEFAULT NULL COMMENT '扩展配置参数',
  `custom_pricing` json DEFAULT NULL COMMENT '自定义定价',
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '是否启用',
  `expire_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '过期时间，0表示永久',
  `create_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_aid_bid` (`aid`, `bid`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商家模型配置表';

-- ============================================================
-- 预置供应商数据
-- ============================================================
INSERT INTO `ddwx_model_provider` (`aid`, `provider_code`, `provider_name`, `logo`, `website`, `api_doc_url`, `description`, `auth_config`, `is_system`, `status`, `sort`, `create_time`, `update_time`) VALUES
(0, 'volcengine', '火山引擎', '', 'https://www.volcengine.com', 'https://www.volcengine.com/docs', '字节跳动旗下云服务平台，提供豆包大模型等AI服务', '{"fields":[{"name":"api_key","label":"API Key","type":"text","required":true},{"name":"api_secret","label":"Secret Key","type":"password","required":false}]}', 1, 1, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(0, 'aliyun', '阿里云', '', 'https://www.aliyun.com', 'https://help.aliyun.com/zh/model-studio/', '阿里巴巴云计算平台，提供通义千问等AI模型服务', '{"fields":[{"name":"api_key","label":"API Key","type":"text","required":true}]}', 1, 1, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(0, 'tencent', '腾讯云', '', 'https://cloud.tencent.com', 'https://cloud.tencent.com/document/product/1729', '腾讯云服务平台，提供混元大模型等AI服务', '{"fields":[{"name":"api_key","label":"SecretId","type":"text","required":true},{"name":"api_secret","label":"SecretKey","type":"password","required":true}]}', 1, 1, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(0, 'baidu', '百度智能云', '', 'https://cloud.baidu.com', 'https://cloud.baidu.com/doc/WENXINWORKSHOP/', '百度AI云平台，提供文心一言等AI模型服务', '{"fields":[{"name":"api_key","label":"API Key","type":"text","required":true},{"name":"api_secret","label":"Secret Key","type":"password","required":true}]}', 1, 1, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(0, 'openai', 'OpenAI', '', 'https://openai.com', 'https://platform.openai.com/docs', 'ChatGPT、DALL-E等AI模型提供商', '{"fields":[{"name":"api_key","label":"API Key","type":"text","required":true}]}', 1, 1, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(0, 'zhipu', '智谱AI', '', 'https://www.zhipuai.cn', 'https://open.bigmodel.cn/dev/api', 'GLM系列大模型提供商', '{"fields":[{"name":"api_key","label":"API Key","type":"text","required":true}]}', 1, 1, 60, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ============================================================
-- 预置模型类型数据
-- ============================================================
INSERT INTO `ddwx_model_type` (`aid`, `type_code`, `type_name`, `icon`, `description`, `input_types`, `output_types`, `is_system`, `status`, `sort`, `create_time`, `update_time`) VALUES
(0, 'deep_thinking', '深度思考', 'layui-icon-survey', '深度思考模型，适用于复杂推理和分析任务', '["text"]', '["text"]', 1, 1, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(0, 'text_generation', '文本生成', 'layui-icon-edit', '文本生成模型，适用于内容创作和对话', '["text"]', '["text"]', 1, 1, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(0, 'video_generation', '视频生成', 'layui-icon-video', '视频生成模型，支持文本或图片生成视频', '["text","image"]', '["video"]', 1, 1, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(0, 'image_generation', '图片生成', 'layui-icon-picture', '图片生成模型，支持文生图和图生图', '["text","image"]', '["image"]', 1, 1, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(0, 'speech_model', '语音模型', 'layui-icon-speaker', '语音模型，支持语音合成和语音识别', '["text","audio"]', '["audio","text"]', 1, 1, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(0, 'embedding', '向量模型', 'layui-icon-template', '向量模型，用于文本和图片的向量化表示', '["text","image"]', '["vector"]', 1, 1, 60, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

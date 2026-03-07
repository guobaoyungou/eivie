-- ============================================
-- AI模型配置功能数据表
-- 创建时间: 2026-02-04
-- 功能: 管理AI模型实例、参数定义、定价配置、响应定义
-- ============================================

-- 1. 模型配置主表（ddwx_ai_model_instance）
CREATE TABLE IF NOT EXISTS `ddwx_ai_model_instance` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '平台ID，0表示系统级配置',
  `category_code` varchar(50) NOT NULL DEFAULT '' COMMENT '模型分类代码，关联模型分类表',
  `model_code` varchar(100) NOT NULL DEFAULT '' COMMENT '模型唯一标识，如qwen-image-edit-max',
  `model_name` varchar(100) NOT NULL DEFAULT '' COMMENT '模型显示名称',
  `model_version` varchar(50) NOT NULL DEFAULT '' COMMENT '模型版本，如v1.0、v2.0',
  `provider` varchar(50) NOT NULL DEFAULT '' COMMENT '服务提供商，如aliyun、baidu、openai',
  `description` text COMMENT '模型描述，功能说明、适用场景',
  `capability_tags` varchar(500) NOT NULL DEFAULT '' COMMENT '能力标签，JSON数组格式',
  `is_system` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否系统预置，1=系统预置，0=自定义',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否激活，1=激活可用，0=停用',
  `sort` int(11) NOT NULL DEFAULT '100' COMMENT '排序权重，数值越大越靠前',
  `cost_per_call` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '每次调用成本价，单位：元',
  `cost_unit` varchar(20) NOT NULL DEFAULT 'per_call' COMMENT '成本计量单位，per_call/per_image/per_video/per_token',
  `billing_mode` varchar(20) NOT NULL DEFAULT 'fixed' COMMENT '计费模式，fixed/token/duration',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_model_code` (`model_code`),
  KEY `idx_aid` (`aid`),
  KEY `idx_category_code` (`category_code`),
  KEY `idx_provider` (`provider`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI模型配置主表';

-- 2. 模型参数定义表（ddwx_ai_model_parameter）
CREATE TABLE IF NOT EXISTS `ddwx_ai_model_parameter` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `model_id` int(11) NOT NULL DEFAULT '0' COMMENT '模型实例ID，关联模型配置表',
  `param_name` varchar(100) NOT NULL DEFAULT '' COMMENT '参数名称，如reference_image、mask_image',
  `param_label` varchar(100) NOT NULL DEFAULT '' COMMENT '参数中文标签，如"参考图像"',
  `param_type` varchar(50) NOT NULL DEFAULT 'string' COMMENT '参数数据类型，string/integer/float/boolean/file/array',
  `data_format` varchar(100) NOT NULL DEFAULT '' COMMENT '数据格式约束，如url、base64、json等',
  `is_required` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否必填，1=必填，0=可选',
  `default_value` text COMMENT '默认值，JSON格式存储',
  `value_range` text COMMENT '取值范围，JSON格式',
  `enum_options` text COMMENT '枚举选项，JSON数组格式',
  `description` text COMMENT '参数说明，用途、注意事项',
  `validation_rule` varchar(500) NOT NULL DEFAULT '' COMMENT '校验规则，正则表达式或校验函数名',
  `sort` int(11) NOT NULL DEFAULT '100' COMMENT '显示顺序',
  PRIMARY KEY (`id`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_param_name` (`param_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI模型参数定义表';

-- 3. 模型定价配置表（ddwx_ai_model_pricing）
CREATE TABLE IF NOT EXISTS `ddwx_ai_model_pricing` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `model_id` int(11) NOT NULL DEFAULT '0' COMMENT '模型实例ID，关联模型配置表',
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '平台ID，0=系统默认定价',
  `bid` int(11) NOT NULL DEFAULT '0' COMMENT '商家ID，0=平台级定价',
  `cost_price` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '成本价，平台向服务商支付的价格',
  `platform_price` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '平台售价，平台销售给商家的价格',
  `merchant_price` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '商家售价，商家销售给C端的价格',
  `platform_profit_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '平台利润率，百分比',
  `merchant_profit_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '商家利润率，百分比',
  `min_price` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '最低售价，防止恶意低价',
  `max_price` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '最高售价，价格上限',
  `currency` varchar(10) NOT NULL DEFAULT 'CNY' COMMENT '货币单位，CNY/USD等',
  `price_type` varchar(20) NOT NULL DEFAULT 'image' COMMENT '定价类型，image/video/token/call',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否生效，1=生效，0=失效',
  `effective_time` int(11) NOT NULL DEFAULT '0' COMMENT '生效时间戳',
  `expire_time` int(11) NOT NULL DEFAULT '0' COMMENT '过期时间戳，0表示永久',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注说明',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_aid_bid` (`aid`,`bid`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI模型定价配置表';

-- 4. 模型响应定义表（ddwx_ai_model_response）
CREATE TABLE IF NOT EXISTS `ddwx_ai_model_response` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `model_id` int(11) NOT NULL DEFAULT '0' COMMENT '模型实例ID，关联模型配置表',
  `response_field` varchar(100) NOT NULL DEFAULT '' COMMENT '响应字段名，如task_id、image_url',
  `field_label` varchar(100) NOT NULL DEFAULT '' COMMENT '字段中文标签',
  `field_type` varchar(50) NOT NULL DEFAULT 'string' COMMENT '字段数据类型，string/integer/object/array',
  `field_path` varchar(200) NOT NULL DEFAULT '' COMMENT '字段路径，JSONPath表达式',
  `is_critical` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否关键字段，1=必须解析，0=可选',
  `description` text COMMENT '字段说明，用途、处理方式',
  PRIMARY KEY (`id`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_response_field` (`response_field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI模型响应定义表';

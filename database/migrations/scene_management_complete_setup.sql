-- ================================================================
-- AI旅拍场景管理完整安装脚本
-- 创建时间：2026-02-04
-- 说明：一键执行完成所有表结构调整和初始化数据
-- ================================================================

USE ddwx;

-- ----------------------------------------------------------------
-- 第一部分：修改场景表结构（添加API配置关联字段）
-- ----------------------------------------------------------------
ALTER TABLE `ddwx_ai_travel_photo_scene` 
ADD COLUMN `api_config_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联API配置ID(ddwx_api_config.id)' AFTER `model_id`;

ALTER TABLE `ddwx_ai_travel_photo_scene` 
ADD INDEX `idx_api_config_id` (`api_config_id`);

ALTER TABLE `ddwx_ai_travel_photo_scene` 
MODIFY COLUMN `model_params` text COMMENT '模型参数JSON(格式：{"prompt":"...","negative_prompt":"...","steps":50})';

-- ----------------------------------------------------------------
-- 第二部分：检查并创建AI模型配置相关表（如果不存在）
-- ----------------------------------------------------------------

-- 表1：AI模型实例表
CREATE TABLE IF NOT EXISTS `ddwx_ai_model_instance` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '平台ID',
  `category_code` varchar(50) NOT NULL COMMENT '模型分类代码',
  `model_code` varchar(100) NOT NULL COMMENT '模型唯一标识',
  `model_name` varchar(200) NOT NULL COMMENT '模型名称',
  `model_version` varchar(50) DEFAULT NULL COMMENT '模型版本',
  `provider` varchar(50) NOT NULL COMMENT '服务提供商',
  `description` text COMMENT '模型描述',
  `capability_tags` json DEFAULT NULL COMMENT '能力标签JSON',
  `is_system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否系统预置',
  `is_public` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否公开',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
  `sort` int(11) NOT NULL DEFAULT '100' COMMENT '排序权重',
  `cost_per_call` decimal(10,4) DEFAULT '0.0000' COMMENT '单次调用成本',
  `cost_unit` varchar(20) DEFAULT NULL COMMENT '成本单位',
  `billing_mode` varchar(20) DEFAULT NULL COMMENT '计费模式',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_model_code` (`model_code`),
  KEY `idx_aid` (`aid`),
  KEY `idx_category` (`category_code`),
  KEY `idx_provider` (`provider`),
  KEY `idx_is_public` (`is_public`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI模型实例配置表';

-- 表2：AI模型参数定义表
CREATE TABLE IF NOT EXISTS `ddwx_ai_model_parameter` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `model_id` int(11) NOT NULL COMMENT '关联模型实例ID',
  `param_name` varchar(100) NOT NULL COMMENT '参数名称',
  `param_code` varchar(100) NOT NULL COMMENT '参数代码',
  `param_type` varchar(20) NOT NULL COMMENT '参数类型',
  `default_value` text COMMENT '默认值',
  `is_required` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否必填',
  `options_json` text COMMENT '选项列表JSON',
  `sort` int(11) NOT NULL DEFAULT '100' COMMENT '排序',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
  `description` text COMMENT '参数描述',
  `validation_rule` varchar(500) DEFAULT NULL COMMENT '验证规则',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI模型参数定义表';

-- 表3：API配置表
CREATE TABLE IF NOT EXISTS `ddwx_api_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '平台ID',
  `bid` int(11) NOT NULL DEFAULT '0' COMMENT '商家ID',
  `mdid` int(11) NOT NULL DEFAULT '0' COMMENT '门店ID',
  `model_id` int(11) DEFAULT '0' COMMENT '关联AI模型实例ID',
  `api_code` varchar(100) NOT NULL COMMENT 'API唯一标识码',
  `api_name` varchar(200) NOT NULL COMMENT 'API显示名称',
  `api_type` varchar(50) NOT NULL COMMENT 'API类型',
  `provider` varchar(50) NOT NULL COMMENT '服务提供商',
  `api_key` varchar(500) NOT NULL COMMENT 'API密钥',
  `api_secret` varchar(500) DEFAULT NULL COMMENT 'API密钥Secret',
  `endpoint_url` varchar(500) NOT NULL COMMENT 'API端点地址',
  `config_json` text COMMENT '其他配置参数',
  `is_system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否系统预置',
  `owner_uid` int(11) DEFAULT '0' COMMENT '配置创建者UID',
  `scope_type` tinyint(1) NOT NULL DEFAULT '2' COMMENT '作用域',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
  `description` text COMMENT 'API描述说明',
  `sort` int(11) NOT NULL DEFAULT '100' COMMENT '排序权重',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_api_code` (`api_code`),
  KEY `idx_aid` (`aid`),
  KEY `idx_bid` (`bid`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API配置表';

-- ----------------------------------------------------------------
-- 第三部分：插入示例数据（仅在表为空时插入）
-- ----------------------------------------------------------------

-- 3.1 插入示例AI模型实例（通义千问图像生成）
INSERT INTO `ddwx_ai_model_instance` (
  `aid`, `category_code`, `model_code`, `model_name`, `model_version`, 
  `provider`, `description`, `capability_tags`, `is_system`, `is_public`, `is_active`, 
  `sort`, `cost_per_call`, `cost_unit`, `billing_mode`, `create_time`, `update_time`
)
SELECT 0, 'image_generation', 'qwen-turbo-image', '通义千问图像生成', 'v1.0',
  'aliyun', '阿里云通义千问AI图像生成模型，支持文生图功能',
  '["文生图","高清图像","多种风格"]', 1, 1, 1, 100, 0.0500, 'per_image', 'fixed',
  UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `ddwx_ai_model_instance` WHERE `model_code` = 'qwen-turbo-image'
);

-- 获取插入的模型ID（用于后续参数定义）
SET @model_id = (SELECT id FROM `ddwx_ai_model_instance` WHERE `model_code` = 'qwen-turbo-image' LIMIT 1);

-- 3.2 插入模型参数定义
INSERT INTO `ddwx_ai_model_parameter` (
  `model_id`, `param_name`, `param_code`, `param_type`, `default_value`, 
  `is_required`, `options_json`, `sort`, `is_active`, `description`, `create_time`, `update_time`
)
SELECT @model_id, '正向提示词', 'prompt', 'textarea', '', 1, NULL, 10, 1, '描述想要生成的图像内容，支持中英文', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE @model_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `ddwx_ai_model_parameter` WHERE `model_id` = @model_id AND `param_code` = 'prompt'
  );

INSERT INTO `ddwx_ai_model_parameter` (
  `model_id`, `param_name`, `param_code`, `param_type`, `default_value`, 
  `is_required`, `options_json`, `sort`, `is_active`, `description`, `create_time`, `update_time`
)
SELECT @model_id, '负面提示词', 'negative_prompt', 'textarea', '低质量,模糊,变形', 0, NULL, 20, 1, '排除不想要的元素', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE @model_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `ddwx_ai_model_parameter` WHERE `model_id` = @model_id AND `param_code` = 'negative_prompt'
  );

INSERT INTO `ddwx_ai_model_parameter` (
  `model_id`, `param_name`, `param_code`, `param_type`, `default_value`, 
  `is_required`, `options_json`, `sort`, `is_active`, `description`, `create_time`, `update_time`
)
SELECT @model_id, '生成步数', 'steps', 'number', '50', 0, NULL, 30, 1, '推荐20-50，步数越多质量越高但速度越慢', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE @model_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `ddwx_ai_model_parameter` WHERE `model_id` = @model_id AND `param_code` = 'steps'
  );

INSERT INTO `ddwx_ai_model_parameter` (
  `model_id`, `param_name`, `param_code`, `param_type`, `default_value`, 
  `is_required`, `options_json`, `sort`, `is_active`, `description`, `create_time`, `update_time`
)
SELECT @model_id, '图像尺寸', 'size', 'select', '1024*1024', 0, 
  '[{"value":"512*512","label":"512x512"},{"value":"1024*1024","label":"1024x1024"},{"value":"1024*1792","label":"1024x1792 竖版"},{"value":"1792*1024","label":"1792x1024 横版"}]',
  40, 1, '生成图像的尺寸', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE @model_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `ddwx_ai_model_parameter` WHERE `model_id` = @model_id AND `param_code` = 'size'
  );

INSERT INTO `ddwx_ai_model_parameter` (
  `model_id`, `param_name`, `param_code`, `param_type`, `default_value`, 
  `is_required`, `options_json`, `sort`, `is_active`, `description`, `create_time`, `update_time`
)
SELECT @model_id, '图像风格', 'style', 'select', '<auto>', 0, 
  '[{"value":"<auto>","label":"自动"},{"value":"<photography>","label":"摄影"},{"value":"<portrait>","label":"人像"},{"value":"<anime>","label":"动漫"},{"value":"<sketch>","label":"素描"}]',
  50, 1, '图像生成风格', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE @model_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `ddwx_ai_model_parameter` WHERE `model_id` = @model_id AND `param_code` = 'style'
  );

-- 3.3 插入示例API配置（需要用户自行填写真实的API密钥）
INSERT INTO `ddwx_api_config` (
  `aid`, `bid`, `mdid`, `model_id`, `api_code`, `api_name`, `api_type`, `provider`,
  `api_key`, `api_secret`, `endpoint_url`, `config_json`, `is_system`, `scope_type`,
  `is_active`, `description`, `sort`, `create_time`, `update_time`
)
SELECT 0, 0, 0, @model_id, 'aliyun_dashscope_default', '阿里云通义千问默认配置', 'image_generation', 'aliyun',
  'YOUR_API_KEY_HERE', NULL, 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis',
  '{"async":true}', 1, 1, 0, '请替换YOUR_API_KEY_HERE为真实的阿里云API Key，启用后方可使用', 100,
  UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE @model_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `ddwx_api_config` WHERE `api_code` = 'aliyun_dashscope_default'
  );

-- ----------------------------------------------------------------
-- 第四部分：验证安装结果
-- ----------------------------------------------------------------
SELECT '=== 安装完成 ===' AS status;
SELECT CONCAT('场景表字段检查: ', IF(COUNT(*) > 0, '✓ api_config_id字段已添加', '✗ 字段添加失败')) AS result
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'ddwx' 
  AND TABLE_NAME = 'ddwx_ai_travel_photo_scene' 
  AND COLUMN_NAME = 'api_config_id';

SELECT CONCAT('AI模型实例: ', COUNT(*), ' 条记录') AS result
FROM `ddwx_ai_model_instance`;

SELECT CONCAT('模型参数定义: ', COUNT(*), ' 条记录') AS result
FROM `ddwx_ai_model_parameter`;

SELECT CONCAT('API配置: ', COUNT(*), ' 条记录 (请检查并更新API密钥)') AS result
FROM `ddwx_api_config`;

-- ----------------------------------------------------------------
-- 安装完成提示
-- ----------------------------------------------------------------
SELECT '
┌─────────────────────────────────────────────┐
│  ✓ 场景管理重构安装完成                     │
├─────────────────────────────────────────────┤
│  下一步操作：                               │
│  1. 检查API配置表，更新YOUR_API_KEY_HERE    │
│  2. 启用API配置（is_active=1）              │
│  3. 访问场景管理页面测试                    │
│  4. 如有问题请查看使用指南                  │
└─────────────────────────────────────────────┘
' AS notice;

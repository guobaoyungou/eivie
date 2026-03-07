-- ================================================================
-- 预置豆包SeeDream 5.0模型
-- 创建时间：2026-02-27
-- 说明：添加支持6种能力的 doubao-seedream-5-0-260128 模型
-- ================================================================

USE guobaoyungou_cn;

-- 获取火山引擎供应商ID
SET @volcengine_provider_id = (SELECT id FROM ddwx_model_provider WHERE provider_code = 'volcengine' LIMIT 1);
SET @volcengine_provider_id = IFNULL(@volcengine_provider_id, 1);

-- 获取图片生成类型ID
SET @image_type_id = (SELECT id FROM ddwx_model_type WHERE type_code = 'image_generation' LIMIT 1);
SET @image_type_id = IFNULL(@image_type_id, 4);

-- 当前时间戳
SET @now = UNIX_TIMESTAMP();

-- ================================================================
-- 豆包SeeDream 5.0 - 图像生成（标准版）
-- 支持6种能力：文生图单张/组图、图生图单入单出/多出、多图入单出/多出
-- ================================================================
INSERT INTO `ddwx_model_info` (
    `aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`,
    `description`, `input_schema`, `output_schema`, `endpoint_url`,
    `pricing_config`, `limits_config`, `task_type`, `capability_tags`,
    `is_system`, `is_active`, `sort`, `create_time`, `update_time`
) VALUES (
    0,
    @volcengine_provider_id,
    @image_type_id,
    'doubao-seedream-5-0-260128',
    '豆包SeeDream 5.0',
    'v5.0',
    '豆包SeeDream 5.0是火山引擎推出的新一代图像生成模型，支持文生图和图生图功能，可输出高达4K分辨率的高清图像。支持多图输入融合、多图批量生成、流式输出，适用于高清海报、专业设计、创意内容生产等场景。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '图像描述文字'),
            JSON_OBJECT('name', 'image', 'label', '参考图像', 'type', 'mixed', 'required', false, 'description', '支持单图URL字符串或多图数组(1-10张)'),
            JSON_OBJECT('name', 'size', 'label', '输出尺寸', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('1K', '2K', '2560x1440', '3840x2160', '4096x4096'), 'default', '2K', 'description', '输出图像尺寸'),
            JSON_OBJECT('name', 'response_format', 'label', '响应格式', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('url', 'b64_json'), 'default', 'url', 'description', '图像返回格式'),
            JSON_OBJECT('name', 'watermark', 'label', '水印', 'type', 'boolean', 'required', false, 'default', false, 'description', '是否添加水印'),
            JSON_OBJECT('name', 'sequential_image_generation', 'label', '连续生成模式', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('disabled', 'auto'), 'default', 'disabled', 'description', '多图生成时设置为auto'),
            JSON_OBJECT('name', 'sequential_image_generation_options', 'label', '连续生成选项', 'type', 'object', 'required', false, 'description', '包含max_images字段(1-10)'),
            JSON_OBJECT('name', 'stream', 'label', '流式输出', 'type', 'boolean', 'required', false, 'default', false, 'description', '是否启用流式输出')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'created', 'label', '创建时间', 'type', 'integer', 'path', '$.created'),
            JSON_OBJECT('field', 'url', 'label', '图像URL', 'type', 'string', 'path', '$.data[*].url', 'is_critical', true),
            JSON_OBJECT('field', 'b64_json', 'label', '图像Base64', 'type', 'string', 'path', '$.data[*].b64_json')
        )
    ),
    'https://ark.cn-beijing.volces.com/api/v3/images/generations',
    JSON_OBJECT(
        'billing_mode', 'per_image',
        'cost_price', 0.25,
        'suggested_price', 2.00,
        'currency', 'CNY'
    ),
    JSON_OBJECT(
        'min_resolution', '1024x1024',
        'max_resolution', '4096x4096',
        'ipm_limit', 500,
        'timeout', 120,
        'stream_support', true,
        'multi_image_support', true,
        'max_images', 10
    ),
    'sync',
    JSON_ARRAY('文生图', '图生图', '高清输出', '2K-4K分辨率', '多图生成', '流式输出', '多图融合', 'text2image', 'image2image', 'batch_generation', 'multi_input'),
    1,
    1,
    85,
    @now,
    @now
) ON DUPLICATE KEY UPDATE
    `model_name` = VALUES(`model_name`),
    `model_version` = VALUES(`model_version`),
    `description` = VALUES(`description`),
    `input_schema` = VALUES(`input_schema`),
    `output_schema` = VALUES(`output_schema`),
    `pricing_config` = VALUES(`pricing_config`),
    `limits_config` = VALUES(`limits_config`),
    `capability_tags` = VALUES(`capability_tags`),
    `update_time` = @now;

-- ================================================================
-- 更新现有模型的 capability_tags（如果需要）
-- ================================================================
-- 更新 doubao-seedream-4-5-251128 模型以支持6种能力
UPDATE `ddwx_model_info` 
SET `capability_tags` = JSON_ARRAY('文生图', '图生图', '高清输出', '2K-4K分辨率', '多图生成', '流式输出', '多图融合', 'text2image', 'image2image', 'batch_generation', 'multi_input'),
    `update_time` = @now
WHERE `model_code` = 'doubao-seedream-4-5-251128';

-- 更新 doubao-seedream-5-0-lite 模型
UPDATE `ddwx_model_info` 
SET `capability_tags` = JSON_ARRAY('文生图', '图生图', '轻量级', '快速响应', 'text2image', 'image2image'),
    `update_time` = @now
WHERE `model_code` = 'doubao-seedream-5-0-lite';

-- ================================================================
-- 验证查询
-- ================================================================
-- SELECT id, model_code, model_name, capability_tags FROM ddwx_model_info WHERE model_code LIKE 'doubao%';

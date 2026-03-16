-- ============================================================
-- 可灵AI(KLING)供应商及模型配置
-- 版本: 1.0.0
-- 创建时间: 2026-03-13
-- 描述: 添加可灵AI供应商及完整的模型配置
--       包含视频生成、图像生成、虚拟试穿等模型
-- ============================================================

START TRANSACTION;

-- 获取视频生成类型ID
SET @video_type_id = (SELECT id FROM ddwx_model_type WHERE type_code = 'video_generation' LIMIT 1);
-- 获取图像生成类型ID
SET @image_type_id = (SELECT id FROM ddwx_model_type WHERE type_code = 'image_generation' LIMIT 1);
-- 当前时间戳
SET @now = UNIX_TIMESTAMP();

-- ============================================================
-- 1. 插入可灵AI供应商
-- ============================================================
INSERT INTO `ddwx_model_provider` 
(`aid`, `provider_code`, `provider_name`, `logo`, `website`, `api_doc_url`, `description`, `auth_config`, `is_system`, `status`, `sort`, `create_time`, `update_time`) 
VALUES 
(0, 'kling', '可灵AI', '', 'https://www.klingai.com', 'https://app.klingai.com/global/dev/document-api', '快手可灵AI - 全球领先的AI视频和图像生成平台，支持文生视频、图生视频、文生图、图生图、虚拟试穿等功能。', 
'{"fields":[{"name":"api_key","label":"AccessKey","type":"text","required":true,"placeholder":"请输入可灵AI的AccessKey"},{"name":"api_secret","label":"SecretKey","type":"password","required":true,"placeholder":"请输入可灵AI的SecretKey"}]}', 
1, 1, 5, @now, @now) 
ON DUPLICATE KEY UPDATE 
    `provider_name` = VALUES(`provider_name`),
    `logo` = VALUES(`logo`),
    `website` = VALUES(`website`),
    `api_doc_url` = VALUES(`api_doc_url`),
    `description` = VALUES(`description`),
    `auth_config` = VALUES(`auth_config`),
    `update_time` = @now;

-- 获取可灵供应商ID
SET @kling_provider_id = (SELECT id FROM ddwx_model_provider WHERE provider_code = 'kling' LIMIT 1);

-- ============================================================
-- 2. 视频生成模型配置
-- ============================================================

-- Kling-V3-Omni (视频生成)
INSERT INTO `ddwx_model_info` 
(`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`, `input_schema`, `output_schema`, `endpoint_url`, `pricing_config`, `limits_config`, `task_type`, `capability_tags`, `is_system`, `is_active`, `sort`, `create_time`, `update_time`) 
VALUES (
    0, @kling_provider_id, @video_type_id, 
    'kling-v3-omni', 
    'Kling-V3-Omni', 
    'v3.0',
    '可灵V3系列最新模型，支持文生视频、图生视频，可生成最长10秒1080P高质量视频。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '视频内容描述文字'),
            JSON_OBJECT('name', 'image_url', 'label', '参考图像', 'type', 'string', 'required', false, 'description', '图生视频时的首帧图像URL'),
            JSON_OBJECT('name', 'mode', 'label', '模式', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('std', 'pro'), 'default', 'std', 'description', 'std标准模式/pro高质量模式'),
            JSON_OBJECT('name', 'duration', 'label', '视频时长', 'type', 'integer', 'required', false, 'min', 1, 'max', 10, 'default', 5, 'description', '生成视频的秒数'),
            JSON_OBJECT('name', 'fps', 'label', '帧率', 'type', 'integer', 'required', false, 'options', JSON_ARRAY(24, 30), 'default', 24, 'description', '视频帧率'),
            JSON_OBJECT('name', 'with_video_clip', 'label', '包含视频参考', 'type', 'boolean', 'required', false, 'default', false, 'description', '是否包含视频参考'),
            JSON_OBJECT('name', 'with_audio', 'label', '生成音频', 'type', 'boolean', 'required', false, 'default', false, 'description', '是否生成音频')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'task_id', 'label', '任务ID', 'type', 'string', 'path', '$.data.task_id', 'is_critical', true, 'description', '异步任务ID，用于查询结果'),
            JSON_OBJECT('field', 'task_status', 'label', '任务状态', 'type', 'string', 'path', '$.data.task_status', 'description', '任务状态')
        )
    ),
    'https://api.klingai.com/v1/videos/generations',
    JSON_OBJECT('billing_mode', 'per_second', 'cost_price', 0.10, 'suggested_price', 0.50, 'currency', 'CNY', 'unit', '秒'),
    JSON_OBJECT('max_duration', 10, 'max_fps', 30, 'resolution', '1080p', 'timeout', 300, 'rate_limit', 10),
    'async',
    JSON_ARRAY('文生视频', '图生视频', '高质量', 'V3系列'),
    1, 1, 100, @now, @now
) ON DUPLICATE KEY UPDATE `model_name` = VALUES(`model_name`), `input_schema` = VALUES(`input_schema`), `output_schema` = VALUES(`output_schema`), `pricing_config` = VALUES(`pricing_config`), `limits_config` = VALUES(`limits_config`), `update_time` = @now;

-- Kling-Video-O1
INSERT INTO `ddwx_model_info` 
(`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`, `input_schema`, `output_schema`, `endpoint_url`, `pricing_config`, `limits_config`, `task_type`, `capability_tags`, `is_system`, `is_active`, `sort`, `create_time`, `update_time`) 
VALUES (
    0, @kling_provider_id, @video_type_id, 
    'kling-video-o1', 
    'Kling-Video-O1', 
    'v1.0',
    '可灵O1系列视频生成模型，支持文生视频和图生视频功能。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '视频内容描述'),
            JSON_OBJECT('name', 'image_url', 'label', '参考图像', 'type', 'string', 'required', false, 'description', '图生视频时的图像URL'),
            JSON_OBJECT('name', 'mode', 'label', '模式', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('std', 'pro'), 'default', 'std', 'description', '生成模式'),
            JSON_OBJECT('name', 'duration', 'label', '视频时长', 'type', 'integer', 'required', false, 'min', 1, 'max', 10, 'default', 5, 'description', '视频时长'),
            JSON_OBJECT('name', 'with_audio', 'label', '生成音频', 'type', 'boolean', 'required', false, 'default', false, 'description', '是否生成音频')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'task_id', 'label', '任务ID', 'type', 'string', 'path', '$.data.task_id', 'is_critical', true, 'description', '异步任务ID'),
            JSON_OBJECT('field', 'task_status', 'label', '任务状态', 'type', 'string', 'path', '$.data.task_status', 'description', '任务状态')
        )
    ),
    'https://api.klingai.com/v1/videos/generations',
    JSON_OBJECT('billing_mode', 'per_second', 'cost_price', 0.12, 'suggested_price', 0.60, 'currency', 'CNY', 'unit', '秒'),
    JSON_OBJECT('max_duration', 10, 'timeout', 300, 'rate_limit', 10),
    'async',
    JSON_ARRAY('文生视频', '图生视频', 'O1系列'),
    1, 1, 95, @now, @now
) ON DUPLICATE KEY UPDATE `model_name` = VALUES(`model_name`), `input_schema` = VALUES(`input_schema`), `pricing_config` = VALUES(`pricing_config`), `update_time` = @now;

-- Kling-V2-6
INSERT INTO `ddwx_model_info` 
(`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`, `input_schema`, `output_schema`, `endpoint_url`, `pricing_config`, `limits_config`, `task_type`, `capability_tags`, `is_system`, `is_active`, `sort`, `create_time`, `update_time`) 
VALUES (
    0, @kling_provider_id, @video_type_id, 
    'kling-v2-6', 
    'Kling-V2.6', 
    'v2.6',
    '可灵V2.6视频生成模型，支持文生视频、图生视频、视频延长等功能。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '视频内容描述'),
            JSON_OBJECT('name', 'image_url', 'label', '参考图像', 'type', 'string', 'required', false, 'description', '图生视频图像URL'),
            JSON_OBJECT('name', 'mode', 'label', '模式', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('std', 'pro'), 'default', 'std', 'description', 'std标准/pro高质量'),
            JSON_OBJECT('name', 'duration', 'label', '视频时长', 'type', 'integer', 'required', false, 'min', 5, 'max', 10, 'default', 5, 'description', '5-10秒'),
            JSON_OBJECT('name', 'first_frame_image', 'label', '首帧图像', 'type', 'string', 'required', false, 'description', '图生视频首帧URL'),
            JSON_OBJECT('name', 'end_frame_image', 'label', '尾帧图像', 'type', 'string', 'required', false, 'description', '图生视频尾帧URL')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'task_id', 'label', '任务ID', 'type', 'string', 'path', '$.data.task_id', 'is_critical', true, 'description', '异步任务ID'),
            JSON_OBJECT('field', 'task_status', 'label', '任务状态', 'type', 'string', 'path', '$.data.task_status', 'description', '任务状态'),
            JSON_OBJECT('field', 'video_url', 'label', '视频URL', 'type', 'string', 'path', '$.data.video_url', 'description', '生成视频URL')
        )
    ),
    'https://api.klingai.com/v1/videos/generations',
    JSON_OBJECT('billing_mode', 'per_call', 'cost_price', 0.35, 'suggested_price', 1.00, 'currency', 'CNY', 'unit', '次', 'duration_default', 5),
    JSON_OBJECT('max_duration', 10, 'timeout', 180, 'rate_limit', 20),
    'async',
    JSON_ARRAY('文生视频', '图生视频', '视频延长', 'V2系列'),
    1, 1, 90, @now, @now
) ON DUPLICATE KEY UPDATE `model_name` = VALUES(`model_name`), `input_schema` = VALUES(`input_schema`), `pricing_config` = VALUES(`pricing_config`), `update_time` = @now;

-- Kling-V2-5-Turbo
INSERT INTO `ddwx_model_info` 
(`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`, `input_schema`, `output_schema`, `endpoint_url`, `pricing_config`, `limits_config`, `task_type`, `capability_tags`, `is_system`, `is_active`, `sort`, `create_time`, `update_time`) 
VALUES (
    0, @kling_provider_id, @video_type_id, 
    'kling-v2-5-turbo', 
    'Kling-V2.5-Turbo', 
    'v2.5-turbo',
    '可灵V2.5 Turbo加速版视频生成模型，适合快速生成视频场景。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '视频内容描述'),
            JSON_OBJECT('name', 'image_url', 'label', '参考图像', 'type', 'string', 'required', false, 'description', '图生视频图像URL'),
            JSON_OBJECT('name', 'mode', 'label', '模式', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('std', 'pro'), 'default', 'std', 'description', '生成模式'),
            JSON_OBJECT('name', 'duration', 'label', '视频时长', 'type', 'integer', 'required', false, 'min', 5, 'max', 10, 'default', 5, 'description', '5-10秒')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'task_id', 'label', '任务ID', 'type', 'string', 'path', '$.data.task_id', 'is_critical', true, 'description', '异步任务ID'),
            JSON_OBJECT('field', 'task_status', 'label', '任务状态', 'type', 'string', 'path', '$.data.task_status', 'description', '任务状态')
        )
    ),
    'https://api.klingai.com/v1/videos/generations',
    JSON_OBJECT('billing_mode', 'per_call', 'cost_price', 0.28, 'suggested_price', 0.80, 'currency', 'CNY', 'unit', '次'),
    JSON_OBJECT('max_duration', 10, 'timeout', 120, 'rate_limit', 30),
    'async',
    JSON_ARRAY('文生视频', '图生视频', '快速生成', 'Turbo'),
    1, 1, 85, @now, @now
) ON DUPLICATE KEY UPDATE `model_name` = VALUES(`model_name`), `pricing_config` = VALUES(`pricing_config`), `update_time` = @now;

-- Kling-V1-6
INSERT INTO `ddwx_model_info` 
(`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`, `input_schema`, `output_schema`, `endpoint_url`, `pricing_config`, `limits_config`, `task_type`, `capability_tags`, `is_system`, `is_active`, `sort`, `create_time`, `update_time`) 
VALUES (
    0, @kling_provider_id, @video_type_id, 
    'kling-v1-6', 
    'Kling-V1.6', 
    'v1.6',
    '可灵V1.6视频生成模型，支持文生视频、图生视频、多图参考、视频延长等功能。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '视频内容描述'),
            JSON_OBJECT('name', 'image_url', 'label', '参考图像', 'type', 'string', 'required', false, 'description', '图生视频图像URL'),
            JSON_OBJECT('name', 'mode', 'label', '模式', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('std', 'pro'), 'default', 'std', 'description', '生成模式'),
            JSON_OBJECT('name', 'duration', 'label', '视频时长', 'type', 'integer', 'required', false, 'min', 5, 'max', 10, 'default', 5, 'description', '5-10秒'),
            JSON_OBJECT('name', 'reference_images', 'label', '多图参考', 'type', 'array', 'required', false, 'description', '多图参考(1-4张)')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'task_id', 'label', '任务ID', 'type', 'string', 'path', '$.data.task_id', 'is_critical', true, 'description', '异步任务ID'),
            JSON_OBJECT('field', 'task_status', 'label', '任务状态', 'type', 'string', 'path', '$.data.task_status', 'description', '任务状态')
        )
    ),
    'https://api.klingai.com/v1/videos/generations',
    JSON_OBJECT('billing_mode', 'per_call', 'cost_price', 0.30, 'suggested_price', 0.90, 'currency', 'CNY', 'unit', '次'),
    JSON_OBJECT('max_duration', 10, 'timeout', 180, 'rate_limit', 20),
    'async',
    JSON_ARRAY('文生视频', '图生视频', '多图参考', '视频延长', 'V1系列'),
    1, 1, 80, @now, @now
) ON DUPLICATE KEY UPDATE `model_name` = VALUES(`model_name`), `input_schema` = VALUES(`input_schema`), `pricing_config` = VALUES(`pricing_config`), `update_time` = @now;

-- Kling-V1-5
INSERT INTO `ddwx_model_info` 
(`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`, `input_schema`, `output_schema`, `endpoint_url`, `pricing_config`, `limits_config`, `task_type`, `capability_tags`, `is_system`, `is_active`, `sort`, `create_time`, `update_time`) 
VALUES (
    0, @kling_provider_id, @video_type_id, 
    'kling-v1-5', 
    'Kling-V1.5', 
    'v1.5',
    '可灵V1.5视频生成模型，支持文生视频、图生视频、运动笔刷等功能。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '视频内容描述'),
            JSON_OBJECT('name', 'image_url', 'label', '参考图像', 'type', 'string', 'required', false, 'description', '图生视频图像URL'),
            JSON_OBJECT('name', 'mode', 'label', '模式', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('std', 'pro'), 'default', 'std', 'description', '生成模式'),
            JSON_OBJECT('name', 'duration', 'label', '视频时长', 'type', 'integer', 'required', false, 'min', 5, 'max', 10, 'default', 5, 'description', '5-10秒')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'task_id', 'label', '任务ID', 'type', 'string', 'path', '$.data.task_id', 'is_critical', true, 'description', '异步任务ID'),
            JSON_OBJECT('field', 'task_status', 'label', '任务状态', 'type', 'string', 'path', '$.data.task_status', 'description', '任务状态')
        )
    ),
    'https://api.klingai.com/v1/videos/generations',
    JSON_OBJECT('billing_mode', 'per_call', 'cost_price', 0.25, 'suggested_price', 0.70, 'currency', 'CNY', 'unit', '次'),
    JSON_OBJECT('max_duration', 10, 'timeout', 180, 'rate_limit', 20),
    'async',
    JSON_ARRAY('文生视频', '图生视频', '运动笔刷', 'V1系列'),
    1, 1, 75, @now, @now
) ON DUPLICATE KEY UPDATE `model_name` = VALUES(`model_name`), `pricing_config` = VALUES(`pricing_config`), `update_time` = @now;

-- ============================================================
-- 3. 图像生成模型配置
-- ============================================================

-- kling-image-o1
INSERT INTO `ddwx_model_info` 
(`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`, `input_schema`, `output_schema`, `endpoint_url`, `pricing_config`, `limits_config`, `task_type`, `capability_tags`, `is_system`, `is_active`, `sort`, `create_time`, `update_time`) 
VALUES (
    0, @kling_provider_id, @image_type_id, 
    'kling-image-o1', 
    'Kling-Image-O1', 
    'v1.0',
    '可灵图像O1模型，支持文生图、图生图、图像编辑等功能，60+风格支持。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '图像描述'),
            JSON_OBJECT('name', 'image_url', 'label', '参考图像', 'type', 'string', 'required', false, 'description', '图生图参考图URL'),
            JSON_OBJECT('name', 'size', 'label', '输出尺寸', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('1:1', '16:9', '4:3', '3:2', '2:3', '3:4', '9:16', '21:9', 'auto'), 'default', '1:1', 'description', '输出图像尺寸'),
            JSON_OBJECT('name', 'mode', 'label', '生成模式', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('text_to_image', 'image_to_image', 'image_edit'), 'default', 'text_to_image', 'description', '文生图/图生图/图像编辑')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'image_url', 'label', '图像URL', 'type', 'string', 'path', '$.data[0].image_url', 'description', '生成图像URL'),
            JSON_OBJECT('field', 'image_base64', 'label', 'Base64图像', 'type', 'string', 'path', '$.data[0].image_base64', 'description', 'Base64编码图像')
        )
    ),
    'https://api.klingai.com/v1/images/generations',
    JSON_OBJECT('billing_mode', 'per_call', 'cost_price', 0.20, 'suggested_price', 0.50, 'currency', 'CNY', 'unit', '张'),
    JSON_OBJECT('max_size', '4096x4096', 'timeout', 60, 'rate_limit', 50),
    'sync',
    JSON_ARRAY('文生图', '图生图', '图像编辑', 'O1系列'),
    1, 1, 60, @now, @now
) ON DUPLICATE KEY UPDATE `model_name` = VALUES(`model_name`), `input_schema` = VALUES(`input_schema`), `pricing_config` = VALUES(`pricing_config`), `update_time` = @now;

-- kling-v2-1 (图像)
INSERT INTO `ddwx_model_info` 
(`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`, `input_schema`, `output_schema`, `endpoint_url`, `pricing_config`, `limits_config`, `task_type`, `capability_tags`, `is_system`, `is_active`, `sort`, `create_time`, `update_time`) 
VALUES (
    0, @kling_provider_id, @image_type_id, 
    'kling-v2-1-image', 
    'Kling-Image-V2.1', 
    'v2.1',
    '可灵V2.1图像生成模型，支持文生图、图生图，高性价比。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '图像描述'),
            JSON_OBJECT('name', 'image_url', 'label', '参考图像', 'type', 'string', 'required', false, 'description', '图生图参考图URL'),
            JSON_OBJECT('name', 'size', 'label', '输出尺寸', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('1:1', '16:9', '4:3', '3:2', '2:3', '3:4', '9:16', '21:9'), 'default', '1:1', 'description', '输出尺寸')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'image_url', 'label', '图像URL', 'type', 'string', 'path', '$.data[0].image_url', 'description', '生成图像URL')
        )
    ),
    'https://api.klingai.com/v1/images/generations',
    JSON_OBJECT('billing_mode', 'per_call', 'cost_price', 0.10, 'suggested_price', 0.30, 'currency', 'CNY', 'unit', '张'),
    JSON_OBJECT('max_size', '2048x2048', 'timeout', 60, 'rate_limit', 50),
    'sync',
    JSON_ARRAY('文生图', '图生图', '高性价比', 'V2系列'),
    1, 1, 55, @now, @now
) ON DUPLICATE KEY UPDATE `model_name` = VALUES(`model_name`), `pricing_config` = VALUES(`pricing_config`), `update_time` = @now;

-- kling-v1-5 (图像)
INSERT INTO `ddwx_model_info` 
(`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`, `input_schema`, `output_schema`, `endpoint_url`, `pricing_config`, `limits_config`, `task_type`, `capability_tags`, `is_system`, `is_active`, `sort`, `create_time`, `update_time`) 
VALUES (
    0, @kling_provider_id, @image_type_id, 
    'kling-v1-5-image', 
    'Kling-Image-V1.5', 
    'v1.5',
    '可灵V1.5图像生成模型，支持文生图、角色参考、面部参考等功能。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '图像描述'),
            JSON_OBJECT('name', 'image_url', 'label', '参考图像', 'type', 'string', 'required', false, 'description', '图生图/角色参考图像URL'),
            JSON_OBJECT('name', 'reference_type', 'label', '参考类型', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('subject', 'face'), 'description', '角色参考/面部参考'),
            JSON_OBJECT('name', 'size', 'label', '输出尺寸', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('1:1', '16:9', '4:3', '3:2', '2:3', '3:4', '9:16', '21:9'), 'default', '1:1', 'description', '输出尺寸')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'image_url', 'label', '图像URL', 'type', 'string', 'path', '$.data[0].image_url', 'description', '生成图像URL')
        )
    ),
    'https://api.klingai.com/v1/images/generations',
    JSON_OBJECT('billing_mode', 'per_call', 'cost_price', 0.20, 'suggested_price', 0.50, 'currency', 'CNY', 'unit', '张'),
    JSON_OBJECT('max_size', '2048x2048', 'timeout', 60, 'rate_limit', 50),
    'sync',
    JSON_ARRAY('文生图', '图生图', '角色参考', '面部参考', 'V1系列'),
    1, 1, 50, @now, @now
) ON DUPLICATE KEY UPDATE `model_name` = VALUES(`model_name`), `input_schema` = VALUES(`input_schema`), `pricing_config` = VALUES(`pricing_config`), `update_time` = @now;

-- kling-v1 (图像)
INSERT INTO `ddwx_model_info` 
(`aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`, `description`, `input_schema`, `output_schema`, `endpoint_url`, `pricing_config`, `limits_config`, `task_type`, `capability_tags`, `is_system`, `is_active`, `sort`, `create_time`, `update_time`) 
VALUES (
    0, @kling_provider_id, @image_type_id, 
    'kling-v1-image', 
    'Kling-Image-V1', 
    'v1.0',
    '可灵V1图像生成模型，基础版本，性价比高。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '图像描述'),
            JSON_OBJECT('name', 'image_url', 'label', '参考图像', 'type', 'string', 'required', false, 'description', '图生图参考图URL'),
            JSON_OBJECT('name', 'size', 'label', '输出尺寸', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('1:1', '16:9', '4:3', '3:2', '2:3', '3:4', '9:16'), 'default', '1:1', 'description', '输出尺寸')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'image_url', 'label', '图像URL', 'type', 'string', 'path', '$.data[0].image_url', 'description', '生成图像URL')
        )
    ),
    'https://api.klingai.com/v1/images/generations',
    JSON_OBJECT('billing_mode', 'per_call', 'cost_price', 0.03, 'suggested_price', 0.10, 'currency', 'CNY', 'unit', '张'),
    JSON_OBJECT('max_size', '1024x1024', 'timeout', 30, 'rate_limit', 100),
    'sync',
    JSON_ARRAY('文生图', '图生图', '基础版', '高性价比'),
    1, 1, 45, @now, @now
) ON DUPLICATE KEY UPDATE `model_name` = VALUES(`model_name`), `pricing_config` = VALUES(`pricing_config`), `update_time` = @now;

COMMIT;

-- ============================================================
-- 验证查询
-- ============================================================
SELECT '供应商信息' as info, id, provider_code, provider_name, status FROM ddwx_model_provider WHERE provider_code = 'kling';
SELECT '视频生成模型' as info, id, model_code, model_name, task_type, is_active FROM ddwx_model_info WHERE provider_id = @kling_provider_id AND type_id = @video_type_id;
SELECT '图像生成模型' as info, id, model_code, model_name, task_type, is_active FROM ddwx_model_info WHERE provider_id = @kling_provider_id AND type_id = @image_type_id;

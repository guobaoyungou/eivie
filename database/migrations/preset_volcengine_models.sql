-- ============================================================
-- 模型广场预置模型 - 火山引擎豆包系列AI模型
-- 版本: 1.0.0
-- 创建时间: 2026-02-27
-- 描述: 预置4个火山引擎豆包系列AI模型
--       - Doubao-Seedream-5.0-lite (图像生成-轻量版)
--       - Doubao-Seedream-4.5 (图像生成-标准版)
--       - Doubao-Seedance-2.0 (视频生成-标准版)
--       - Doubao-Seedance-1.5-pro (视频生成-专业版)
-- ============================================================

-- 开始事务
START TRANSACTION;

-- 获取火山引擎供应商ID (默认为1)
SET @volcengine_provider_id = (SELECT id FROM ddwx_model_provider WHERE provider_code = 'volcengine' LIMIT 1);

-- 获取图片生成类型ID (默认为4)
SET @image_type_id = (SELECT id FROM ddwx_model_type WHERE type_code = 'image_generation' LIMIT 1);

-- 获取视频生成类型ID (默认为3)
SET @video_type_id = (SELECT id FROM ddwx_model_type WHERE type_code = 'video_generation' LIMIT 1);

-- 当前时间戳
SET @now = UNIX_TIMESTAMP();

-- ============================================================
-- 1. Doubao-Seedream-5.0-lite - 图像生成（轻量版）
-- ============================================================
INSERT INTO `ddwx_model_info` (
    `aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`,
    `description`, `input_schema`, `output_schema`, `endpoint_url`,
    `pricing_config`, `limits_config`, `task_type`, `capability_tags`,
    `is_system`, `is_active`, `sort`, `create_time`, `update_time`
) VALUES (
    0,
    @volcengine_provider_id,
    @image_type_id,
    'doubao-seedream-5-0-lite',
    '豆包SeeDream 5.0 Lite',
    'v5.0-lite',
    '豆包SeeDream 5.0 Lite是火山引擎推出的轻量级图像生成模型，支持文生图和图生图功能，适用于快速预览和批量处理场景。具有响应速度快、成本低的特点。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '描述生成图像的文字'),
            JSON_OBJECT('name', 'image', 'label', '参考图像', 'type', 'string', 'required', false, 'description', '图生图时的参考图URL'),
            JSON_OBJECT('name', 'size', 'label', '输出尺寸', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('1K', '2K'), 'default', '1K', 'description', '输出图像尺寸'),
            JSON_OBJECT('name', 'response_format', 'label', '响应格式', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('url', 'b64_json'), 'default', 'url', 'description', '返回图像的格式'),
            JSON_OBJECT('name', 'watermark', 'label', '水印', 'type', 'boolean', 'required', false, 'default', false, 'description', '是否添加水印')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'url', 'label', '图像URL', 'type', 'string', 'path', '$.data[0].url', 'description', '图像访问地址'),
            JSON_OBJECT('field', 'b64_json', 'label', 'Base64图像', 'type', 'string', 'path', '$.data[0].b64_json', 'description', 'Base64编码图像'),
            JSON_OBJECT('field', 'revised_prompt', 'label', '优化提示词', 'type', 'string', 'path', '$.data[0].revised_prompt', 'description', '优化后的提示词')
        )
    ),
    'https://ark.cn-beijing.volces.com/api/v3/images/generations',
    JSON_OBJECT(
        'billing_mode', 'per_image',
        'cost_price', 0.15,
        'suggested_price', 1.00,
        'currency', 'CNY'
    ),
    JSON_OBJECT(
        'max_resolution', '2K',
        'ipm_limit', 500,
        'timeout', 60
    ),
    'sync',
    JSON_ARRAY('文生图', '图生图', '轻量级', '快速响应'),
    1,
    1,
    100,
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

-- ============================================================
-- 2. Doubao-Seedream-4.5 - 图像生成（标准版）
-- ============================================================
INSERT INTO `ddwx_model_info` (
    `aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`,
    `description`, `input_schema`, `output_schema`, `endpoint_url`,
    `pricing_config`, `limits_config`, `task_type`, `capability_tags`,
    `is_system`, `is_active`, `sort`, `create_time`, `update_time`
) VALUES (
    0,
    @volcengine_provider_id,
    @image_type_id,
    'doubao-seedream-4-5-251128',
    '豆包SeeDream 4.5',
    'v4.5',
    '豆包SeeDream 4.5是火山引擎推出的标准版图像生成模型，支持文生图和图生图功能，可输出高达4K分辨率的高清图像。支持多图生成、流式输出，适用于高清海报、专业设计输出等场景。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '图像描述文字'),
            JSON_OBJECT('name', 'image', 'label', '参考图像', 'type', 'mixed', 'required', false, 'description', '支持单图URL字符串或多图数组(1-10张)'),
            JSON_OBJECT('name', 'sequential_image_generation_options', 'label', '多图生成配置', 'type', 'object', 'required', false, 'description', '多图生成配置对象，含max_images字段(1-10)'),
            JSON_OBJECT('name', 'size', 'label', '输出尺寸', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('1K', '2K', '2560x1440', '4096x4096'), 'default', '2K', 'description', '输出图像尺寸'),
            JSON_OBJECT('name', 'response_format', 'label', '响应格式', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('url', 'b64_json'), 'default', 'url', 'description', '返回图像的格式'),
            JSON_OBJECT('name', 'stream', 'label', '流式输出', 'type', 'boolean', 'required', false, 'default', false, 'description', '是否启用流式输出'),
            JSON_OBJECT('name', 'watermark', 'label', '水印', 'type', 'boolean', 'required', false, 'default', false, 'description', '是否添加水印')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'url', 'label', '图像URL', 'type', 'string', 'path', '$.data[0].url', 'description', '图像访问地址'),
            JSON_OBJECT('field', 'b64_json', 'label', 'Base64图像', 'type', 'string', 'path', '$.data[0].b64_json', 'description', 'Base64编码图像'),
            JSON_OBJECT('field', 'revised_prompt', 'label', '优化提示词', 'type', 'string', 'path', '$.data[0].revised_prompt', 'description', '优化后的提示词')
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
        'min_resolution', '2560x1440',
        'max_resolution', '4096x4096',
        'ipm_limit', 500,
        'timeout', 120,
        'stream_support', true,
        'multi_image_support', true,
        'max_images', 10
    ),
    'sync',
    JSON_ARRAY('文生图', '图生图', '高清输出', '2K-4K分辨率', '多图生成', '流式输出'),
    1,
    1,
    90,
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

-- ============================================================
-- 3. Doubao-Seedance-2.0 - 视频生成（标准版）
-- ============================================================
INSERT INTO `ddwx_model_info` (
    `aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`,
    `description`, `input_schema`, `output_schema`, `endpoint_url`,
    `pricing_config`, `limits_config`, `task_type`, `capability_tags`,
    `is_system`, `is_active`, `sort`, `create_time`, `update_time`
) VALUES (
    0,
    @volcengine_provider_id,
    @video_type_id,
    'doubao-seedance-2-0',
    '豆包SeeDance 2.0',
    'v2.0',
    '豆包SeeDance 2.0是火山引擎推出的标准版视频生成模型，支持文生视频和图生视频功能，可生成最长10秒、最高1080P分辨率的高质量视频。适用于短视频创作、社交媒体内容生成等场景。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '视频内容描述'),
            JSON_OBJECT('name', 'image', 'label', '首帧图像', 'type', 'string', 'required', false, 'description', '图生视频时的首帧URL'),
            JSON_OBJECT('name', 'duration', 'label', '视频时长', 'type', 'integer', 'required', false, 'min', 1, 'max', 10, 'default', 5, 'description', '生成视频的秒数(1-10)'),
            JSON_OBJECT('name', 'resolution', 'label', '分辨率', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('720p', '1080p'), 'default', '1080p', 'description', '输出视频分辨率'),
            JSON_OBJECT('name', 'fps', 'label', '帧率', 'type', 'integer', 'required', false, 'options', JSON_ARRAY(24, 30), 'default', 24, 'description', '视频帧率')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'task_id', 'label', '任务ID', 'type', 'string', 'path', '$.output.task_id', 'is_critical', true, 'description', '异步任务ID'),
            JSON_OBJECT('field', 'task_status', 'label', '任务状态', 'type', 'string', 'path', '$.output.task_status', 'is_critical', true, 'description', '任务状态'),
            JSON_OBJECT('field', 'video_url', 'label', '视频URL', 'type', 'string', 'path', '$.output.video_url', 'is_critical', true, 'description', '视频访问地址')
        )
    ),
    'https://ark.cn-beijing.volces.com/api/v3/videos/generations',
    JSON_OBJECT(
        'billing_mode', 'per_second',
        'cost_price', 0.50,
        'suggested_price', 3.00,
        'currency', 'CNY'
    ),
    JSON_OBJECT(
        'max_duration', 10,
        'max_resolution', '1920x1080',
        'max_fps', 30,
        'concurrent_limit', 5,
        'timeout', 300
    ),
    'async',
    JSON_ARRAY('文生视频', '图生视频', '高质量', '1080P'),
    1,
    1,
    80,
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

-- ============================================================
-- 4. Doubao-Seedance-1.5-pro - 视频生成（专业版）
-- ============================================================
INSERT INTO `ddwx_model_info` (
    `aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`,
    `description`, `input_schema`, `output_schema`, `endpoint_url`,
    `pricing_config`, `limits_config`, `task_type`, `capability_tags`,
    `is_system`, `is_active`, `sort`, `create_time`, `update_time`
) VALUES (
    0,
    @volcengine_provider_id,
    @video_type_id,
    'doubao-seedance-1-5-pro',
    '豆包SeeDance 1.5 Pro',
    'v1.5-pro',
    '豆包SeeDance 1.5 Pro是火山引擎推出的专业版视频生成模型，提供更精细的运动控制和更高的输出质量。支持最长15秒、最高4K分辨率、60fps高帧率视频生成，适用于专业广告、影视制作等高端应用场景。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '视频内容描述'),
            JSON_OBJECT('name', 'image', 'label', '首帧图像', 'type', 'string', 'required', false, 'description', '图生视频首帧'),
            JSON_OBJECT('name', 'duration', 'label', '视频时长', 'type', 'integer', 'required', false, 'min', 1, 'max', 15, 'default', 5, 'description', '生成视频的秒数(1-15)'),
            JSON_OBJECT('name', 'resolution', 'label', '分辨率', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('720p', '1080p', '4K'), 'default', '1080p', 'description', '输出视频分辨率'),
            JSON_OBJECT('name', 'fps', 'label', '帧率', 'type', 'integer', 'required', false, 'options', JSON_ARRAY(24, 30, 60), 'default', 30, 'description', '视频帧率'),
            JSON_OBJECT('name', 'motion_intensity', 'label', '运动强度', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('low', 'medium', 'high'), 'default', 'medium', 'description', '画面运动强度'),
            JSON_OBJECT('name', 'camera_motion', 'label', '相机运动', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('static', 'pan', 'zoom'), 'default', 'static', 'description', '相机运动方式')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'task_id', 'label', '任务ID', 'type', 'string', 'path', '$.output.task_id', 'is_critical', true, 'description', '异步任务ID'),
            JSON_OBJECT('field', 'task_status', 'label', '任务状态', 'type', 'string', 'path', '$.output.task_status', 'is_critical', true, 'description', '任务状态'),
            JSON_OBJECT('field', 'video_url', 'label', '视频URL', 'type', 'string', 'path', '$.output.video_url', 'is_critical', true, 'description', '视频访问地址')
        )
    ),
    'https://ark.cn-beijing.volces.com/api/v3/videos/generations',
    JSON_OBJECT(
        'billing_mode', 'per_second',
        'cost_price', 0.80,
        'suggested_price', 5.00,
        'currency', 'CNY'
    ),
    JSON_OBJECT(
        'max_duration', 15,
        'max_resolution', '3840x2160',
        'max_fps', 60,
        'concurrent_limit', 3,
        'timeout', 600,
        'motion_control', true,
        'camera_motion_support', true
    ),
    'async',
    JSON_ARRAY('文生视频', '图生视频', '专业版', '运动控制', '高帧率', '4K'),
    1,
    1,
    70,
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

-- ============================================================
-- 5. Doubao-Seedance-1.0-pro - 视频生成（1.0专业版）
-- ============================================================
INSERT INTO `ddwx_model_info` (
    `aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`,
    `description`, `input_schema`, `output_schema`, `endpoint_url`,
    `pricing_config`, `limits_config`, `task_type`, `capability_tags`,
    `is_system`, `is_active`, `sort`, `create_time`, `update_time`
) VALUES (
    0,
    @volcengine_provider_id,
    @video_type_id,
    'doubao-seedance-1-0-pro',
    '豆包SeeDance 1.0 Pro',
    'v1.0-pro',
    '豆包SeeDance 1.0 Pro是火山引擎推出的1.0系列专业版视频生成模型，支持文生视频、首帧图生视频、首尾帧图生视频功能，可生成最长10秒、720P分辨率的高质量视频。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '视频内容描述'),
            JSON_OBJECT('name', 'first_frame_image', 'label', '首帧图像', 'type', 'string', 'required', false, 'description', '首帧图生视频时的首帧URL'),
            JSON_OBJECT('name', 'last_frame_image', 'label', '尾帧图像', 'type', 'string', 'required', false, 'description', '首尾帧图生视频时的尾帧URL'),
            JSON_OBJECT('name', 'duration', 'label', '视频时长', 'type', 'integer', 'required', false, 'min', 1, 'max', 10, 'default', 5, 'description', '生成视频的秒数(1-10)'),
            JSON_OBJECT('name', 'resolution', 'label', '分辨率', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('720p'), 'default', '720p', 'description', '输出视频分辨率')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'task_id', 'label', '任务ID', 'type', 'string', 'path', '$.output.task_id', 'is_critical', true, 'description', '异步任务ID'),
            JSON_OBJECT('field', 'task_status', 'label', '任务状态', 'type', 'string', 'path', '$.output.task_status', 'is_critical', true, 'description', '任务状态'),
            JSON_OBJECT('field', 'video_url', 'label', '视频URL', 'type', 'string', 'path', '$.output.video_url', 'is_critical', true, 'description', '视频访问地址')
        )
    ),
    'https://ark.cn-beijing.volces.com/api/v3/videos/generations',
    JSON_OBJECT(
        'billing_mode', 'per_second',
        'cost_price', 0.30,
        'suggested_price', 2.00,
        'currency', 'CNY'
    ),
    JSON_OBJECT(
        'max_duration', 10,
        'max_resolution', '1280x720',
        'concurrent_limit', 5,
        'timeout', 300
    ),
    'async',
    JSON_ARRAY('文生视频', '首帧图生视频', '首尾帧图生视频', '720P'),
    1,
    1,
    65,
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

-- ============================================================
-- 6. Doubao-Seedance-1.0-pro-fast - 视频生成（1.0快速版）
-- ============================================================
INSERT INTO `ddwx_model_info` (
    `aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`,
    `description`, `input_schema`, `output_schema`, `endpoint_url`,
    `pricing_config`, `limits_config`, `task_type`, `capability_tags`,
    `is_system`, `is_active`, `sort`, `create_time`, `update_time`
) VALUES (
    0,
    @volcengine_provider_id,
    @video_type_id,
    'doubao-seedance-1-0-pro-fast',
    '豆包SeeDance 1.0 Pro Fast',
    'v1.0-pro-fast',
    '豆包SeeDance 1.0 Pro Fast是火山引擎推出的快速生成版本，支持文生视频和首帧图生视频功能，以更快的速度生成最长5秒、720P分辨率的视频，适合快速预览场景。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '视频内容描述'),
            JSON_OBJECT('name', 'first_frame_image', 'label', '首帧图像', 'type', 'string', 'required', false, 'description', '首帧图生视频时的首帧URL'),
            JSON_OBJECT('name', 'duration', 'label', '视频时长', 'type', 'integer', 'required', false, 'min', 1, 'max', 5, 'default', 5, 'description', '生成视频的秒数(1-5)'),
            JSON_OBJECT('name', 'resolution', 'label', '分辨率', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('720p'), 'default', '720p', 'description', '输出视频分辨率')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'task_id', 'label', '任务ID', 'type', 'string', 'path', '$.output.task_id', 'is_critical', true, 'description', '异步任务ID'),
            JSON_OBJECT('field', 'task_status', 'label', '任务状态', 'type', 'string', 'path', '$.output.task_status', 'is_critical', true, 'description', '任务状态'),
            JSON_OBJECT('field', 'video_url', 'label', '视频URL', 'type', 'string', 'path', '$.output.video_url', 'is_critical', true, 'description', '视频访问地址')
        )
    ),
    'https://ark.cn-beijing.volces.com/api/v3/videos/generations',
    JSON_OBJECT(
        'billing_mode', 'per_second',
        'cost_price', 0.15,
        'suggested_price', 1.00,
        'currency', 'CNY'
    ),
    JSON_OBJECT(
        'max_duration', 5,
        'max_resolution', '1280x720',
        'concurrent_limit', 10,
        'timeout', 120
    ),
    'async',
    JSON_ARRAY('文生视频', '首帧图生视频', '快速生成', '720P'),
    1,
    1,
    60,
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

-- ============================================================
-- 7. Doubao-Seedance-1.0-lite-t2v - 视频生成（1.0轻量文生版）
-- ============================================================
INSERT INTO `ddwx_model_info` (
    `aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`,
    `description`, `input_schema`, `output_schema`, `endpoint_url`,
    `pricing_config`, `limits_config`, `task_type`, `capability_tags`,
    `is_system`, `is_active`, `sort`, `create_time`, `update_time`
) VALUES (
    0,
    @volcengine_provider_id,
    @video_type_id,
    'doubao-seedance-1-0-lite-t2v',
    '豆包SeeDance 1.0 Lite (文生)',
    'v1.0-lite-t2v',
    '豆包SeeDance 1.0 Lite (文生)是火山引擎推出的轻量级文生视频模型，仅支持文字描述生成视频，可生成最长5秒、720P分辨率的视频，具有响应快、成本低的特点。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '视频内容描述'),
            JSON_OBJECT('name', 'duration', 'label', '视频时长', 'type', 'integer', 'required', false, 'min', 1, 'max', 5, 'default', 5, 'description', '生成视频的秒数(1-5)'),
            JSON_OBJECT('name', 'resolution', 'label', '分辨率', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('720p'), 'default', '720p', 'description', '输出视频分辨率')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'task_id', 'label', '任务ID', 'type', 'string', 'path', '$.output.task_id', 'is_critical', true, 'description', '异步任务ID'),
            JSON_OBJECT('field', 'task_status', 'label', '任务状态', 'type', 'string', 'path', '$.output.task_status', 'is_critical', true, 'description', '任务状态'),
            JSON_OBJECT('field', 'video_url', 'label', '视频URL', 'type', 'string', 'path', '$.output.video_url', 'is_critical', true, 'description', '视频访问地址')
        )
    ),
    'https://ark.cn-beijing.volces.com/api/v3/videos/generations',
    JSON_OBJECT(
        'billing_mode', 'per_second',
        'cost_price', 0.10,
        'suggested_price', 0.80,
        'currency', 'CNY'
    ),
    JSON_OBJECT(
        'max_duration', 5,
        'max_resolution', '1280x720',
        'concurrent_limit', 20,
        'timeout', 120
    ),
    'async',
    JSON_ARRAY('文生视频', '轻量级', '低成本', '720P'),
    1,
    1,
    55,
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

-- ============================================================
-- 8. Doubao-Seedance-1.0-lite-i2v - 视频生成（1.0轻量图生版）
-- ============================================================
INSERT INTO `ddwx_model_info` (
    `aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`,
    `description`, `input_schema`, `output_schema`, `endpoint_url`,
    `pricing_config`, `limits_config`, `task_type`, `capability_tags`,
    `is_system`, `is_active`, `sort`, `create_time`, `update_time`
) VALUES (
    0,
    @volcengine_provider_id,
    @video_type_id,
    'doubao-seedance-1-0-lite-i2v',
    '豆包SeeDance 1.0 Lite (图生)',
    'v1.0-lite-i2v',
    '豆包SeeDance 1.0 Lite (图生)是火山引擎推出的轻量级图生视频模型，支持首帧图生视频、首尾帧图生视频和参考图生视频功能，可生成最长5秒、720P分辨率的视频，支持最多4张参考图。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', false, 'description', '视频内容描述'),
            JSON_OBJECT('name', 'first_frame_image', 'label', '首帧图像', 'type', 'string', 'required', false, 'description', '首帧图生视频时的首帧URL'),
            JSON_OBJECT('name', 'last_frame_image', 'label', '尾帧图像', 'type', 'string', 'required', false, 'description', '首尾帧图生视频时的尾帧URL'),
            JSON_OBJECT('name', 'reference_images', 'label', '参考图像', 'type', 'array', 'required', false, 'max_items', 4, 'description', '参考图生视频的图像URL数组(最多4张)'),
            JSON_OBJECT('name', 'duration', 'label', '视频时长', 'type', 'integer', 'required', false, 'min', 1, 'max', 5, 'default', 5, 'description', '生成视频的秒数(1-5)'),
            JSON_OBJECT('name', 'resolution', 'label', '分辨率', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('720p'), 'default', '720p', 'description', '输出视频分辨率')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'task_id', 'label', '任务ID', 'type', 'string', 'path', '$.output.task_id', 'is_critical', true, 'description', '异步任务ID'),
            JSON_OBJECT('field', 'task_status', 'label', '任务状态', 'type', 'string', 'path', '$.output.task_status', 'is_critical', true, 'description', '任务状态'),
            JSON_OBJECT('field', 'video_url', 'label', '视频URL', 'type', 'string', 'path', '$.output.video_url', 'is_critical', true, 'description', '视频访问地址')
        )
    ),
    'https://ark.cn-beijing.volces.com/api/v3/videos/generations',
    JSON_OBJECT(
        'billing_mode', 'per_second',
        'cost_price', 0.12,
        'suggested_price', 0.90,
        'currency', 'CNY'
    ),
    JSON_OBJECT(
        'max_duration', 5,
        'max_resolution', '1280x720',
        'max_reference_images', 4,
        'concurrent_limit', 20,
        'timeout', 120
    ),
    'async',
    JSON_ARRAY('首帧图生视频', '首尾帧图生视频', '参考图生视频', '轻量级', '720P'),
    1,
    1,
    50,
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

-- ============================================================
-- 二级分类：豆包视频生成
-- ============================================================
INSERT INTO `ddwx_ai_model_category` (`code`, `level`, `parent_code`, `name`, `description`, `icon`, `sort`, `status`, `is_system`, `create_time`) 
SELECT 'seedance', 2, 'video_generation', '豆包视频', '火山引擎豆包SeeDance视频生成模型', '🎥', 110, 1, 1, UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `ddwx_ai_model_category` WHERE `code` = 'seedance');

-- ============================================================
-- AI模型实例配置 - 火山引擎视频生成模型
-- 用于场景编辑页面的模型下拉列表
-- ============================================================

-- Seedance 1.5 Pro 实例
INSERT INTO `ddwx_ai_model_instance` (
    `aid`, `category_code`, `model_code`, `model_name`, `model_version`,
    `provider`, `description`, `capability_tags`, `is_system`, `is_active`,
    `sort`, `cost_per_call`, `cost_unit`, `billing_mode`, `create_time`, `update_time`
) SELECT 0, 'video_generation', 'doubao-seedance-1-5-pro', '豆包SeeDance 1.5 Pro', 'v1.5-pro',
    'volcengine', '专业版视频生成，支持文生视频、首帧/首尾帧图生视频、有声视频，最长10秒，1080P',
    '["文生视频","首帧图生视频","首尾帧图生视频","有声视频","1080P"]', 1, 1,
    200, 0.40, 'per_second', 'per_second', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `ddwx_ai_model_instance` WHERE `model_code` = 'doubao-seedance-1-5-pro');

-- Seedance 1.0 Pro 实例
INSERT INTO `ddwx_ai_model_instance` (
    `aid`, `category_code`, `model_code`, `model_name`, `model_version`,
    `provider`, `description`, `capability_tags`, `is_system`, `is_active`,
    `sort`, `cost_per_call`, `cost_unit`, `billing_mode`, `create_time`, `update_time`
) SELECT 0, 'video_generation', 'doubao-seedance-1-0-pro', '豆包SeeDance 1.0 Pro', 'v1.0-pro',
    'volcengine', '1.0专业版视频生成，支持文生视频、首帧/首尾帧图生视频，最长10秒，720P',
    '["文生视频","首帧图生视频","首尾帧图生视频","720P"]', 1, 1,
    190, 0.30, 'per_second', 'per_second', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `ddwx_ai_model_instance` WHERE `model_code` = 'doubao-seedance-1-0-pro');

-- Seedance 1.0 Pro Fast 实例
INSERT INTO `ddwx_ai_model_instance` (
    `aid`, `category_code`, `model_code`, `model_name`, `model_version`,
    `provider`, `description`, `capability_tags`, `is_system`, `is_active`,
    `sort`, `cost_per_call`, `cost_unit`, `billing_mode`, `create_time`, `update_time`
) SELECT 0, 'video_generation', 'doubao-seedance-1-0-pro-fast', '豆包SeeDance 1.0 Pro Fast', 'v1.0-pro-fast',
    'volcengine', '快速版视频生成，支持文生视频、首帧图生视频，最长5秒，720P',
    '["文生视频","首帧图生视频","快速生成","720P"]', 1, 1,
    180, 0.15, 'per_second', 'per_second', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `ddwx_ai_model_instance` WHERE `model_code` = 'doubao-seedance-1-0-pro-fast');

-- Seedance 1.0 Lite T2V 实例
INSERT INTO `ddwx_ai_model_instance` (
    `aid`, `category_code`, `model_code`, `model_name`, `model_version`,
    `provider`, `description`, `capability_tags`, `is_system`, `is_active`,
    `sort`, `cost_per_call`, `cost_unit`, `billing_mode`, `create_time`, `update_time`
) SELECT 0, 'video_generation', 'doubao-seedance-1-0-lite-t2v', '豆包SeeDance 1.0 Lite (文生)', 'v1.0-lite-t2v',
    'volcengine', '轻量版文生视频，仅支持文字描述生成视频，最长5秒，720P，成本低',
    '["文生视频","轻量级","低成本","720P"]', 1, 1,
    170, 0.10, 'per_second', 'per_second', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `ddwx_ai_model_instance` WHERE `model_code` = 'doubao-seedance-1-0-lite-t2v');

-- Seedance 1.0 Lite I2V 实例
INSERT INTO `ddwx_ai_model_instance` (
    `aid`, `category_code`, `model_code`, `model_name`, `model_version`,
    `provider`, `description`, `capability_tags`, `is_system`, `is_active`,
    `sort`, `cost_per_call`, `cost_unit`, `billing_mode`, `create_time`, `update_time`
) SELECT 0, 'video_generation', 'doubao-seedance-1-0-lite-i2v', '豆包SeeDance 1.0 Lite (图生)', 'v1.0-lite-i2v',
    'volcengine', '轻量版图生视频，支持首帧/首尾帧/参考图生视频，最长5秒，720P',
    '["首帧图生视频","首尾帧图生视频","参考图生视频","轻量级","720P"]', 1, 1,
    160, 0.12, 'per_second', 'per_second', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `ddwx_ai_model_instance` WHERE `model_code` = 'doubao-seedance-1-0-lite-i2v');

-- Seedance 2.0 实例
INSERT INTO `ddwx_ai_model_instance` (
    `aid`, `category_code`, `model_code`, `model_name`, `model_version`,
    `provider`, `description`, `capability_tags`, `is_system`, `is_active`,
    `sort`, `cost_per_call`, `cost_unit`, `billing_mode`, `create_time`, `update_time`
) SELECT 0, 'video_generation', 'doubao-seedance-2-0', '豆包SeeDance 2.0', 'v2.0',
    'volcengine', '标准版视频生成，支持文生视频、首帧/首尾帧图生视频，最长10秒，1080P',
    '["文生视频","首帧图生视频","首尾帧图生视频","1080P"]', 1, 1,
    195, 0.50, 'per_second', 'per_second', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `ddwx_ai_model_instance` WHERE `model_code` = 'doubao-seedance-2-0');

-- 提交事务
COMMIT;

-- ============================================================
-- 验证查询
-- ============================================================
SELECT 
    m.id,
    m.model_code,
    m.model_name,
    m.model_version,
    p.provider_name,
    t.type_name,
    m.task_type,
    m.is_system,
    m.is_active
FROM ddwx_model_info m
LEFT JOIN ddwx_model_provider p ON m.provider_id = p.id
LEFT JOIN ddwx_model_type t ON m.type_id = t.id
WHERE m.model_code IN (
    'doubao-seedream-5-0-lite',
    'doubao-seedream-4-5-251128',
    'doubao-seedance-2-0',
    'doubao-seedance-1-5-pro',
    'doubao-seedance-1-0-pro',
    'doubao-seedance-1-0-pro-fast',
    'doubao-seedance-1-0-lite-t2v',
    'doubao-seedance-1-0-lite-i2v'
)
ORDER BY m.sort ASC;

-- 验证模型实例
SELECT 
    model_code,
    model_name,
    provider,
    capability_tags,
    is_active
FROM ddwx_ai_model_instance
WHERE model_code LIKE 'doubao-seedance%'
ORDER BY sort DESC;

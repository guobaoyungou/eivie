-- ============================================================
-- 模型广场预置模型 - 阿里云万相Wan系列AI模型
-- 版本: 1.0.0
-- 创建时间: 2026-03-13
-- 描述: 预置6个阿里云万相Wan系列AI模型
--       - wan2.6-image (图像生成)
--       - wan2.5-i2i-preview (图生图预览)
--       - wanx2.1-imageedit (图像编辑)
--       - wan2.6-i2v-flash (图生视频快速版)
--       - wan2.6-i2v (图生视频标准版)
--       - wan2.5-i2v-preview (图生视频预览版)
-- 参考文档:
--   - https://help.aliyun.com/zh/model-studio/wan-image-generation-api-reference
--   - https://help.aliyun.com/zh/model-studio/wan-video-to-video-api-reference
--   - https://help.aliyun.com/zh/model-studio/image-to-video-by-first-and-last-frame-api-reference
-- ============================================================

-- 开始事务
START TRANSACTION;

-- 获取阿里云供应商ID
SET @aliyun_provider_id = (SELECT id FROM ddwx_model_provider WHERE provider_code = 'aliyun' LIMIT 1);

-- 获取图片生成类型ID
SET @image_type_id = (SELECT id FROM ddwx_model_type WHERE type_code = 'image_generation' LIMIT 1);

-- 获取视频生成类型ID
SET @video_type_id = (SELECT id FROM ddwx_model_type WHERE type_code = 'video_generation' LIMIT 1);

-- 当前时间戳
SET @now = UNIX_TIMESTAMP();

-- ============================================================
-- 1. Wan2.6-image - 万相2.6图像生成模型
-- ============================================================
INSERT INTO `ddwx_model_info` (
    `aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`,
    `description`, `input_schema`, `output_schema`, `endpoint_url`,
    `pricing_config`, `limits_config`, `task_type`, `capability_tags`,
    `is_system`, `is_active`, `sort`, `create_time`, `update_time`
) VALUES (
    0,
    @aliyun_provider_id,
    @image_type_id,
    'wan2.6-image',
    '万相2.6图像生成',
    'v2.6',
    '阿里云万相Wan2.6系列图像生成模型，支持文生图功能，基于最新的Wan架构，可生成高质量、高分辨率的图像。适用于创意设计、电商配图、社交媒体内容等多种场景。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '图像内容描述文字，支持中英文'),
            JSON_OBJECT('name', 'negative_prompt', 'label', '负面提示词', 'type', 'string', 'required', false, 'description', '不希望在图像中出现的内容'),
            JSON_OBJECT('name', 'size', 'label', '图像尺寸', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('1024*1024', '1024*576', '576*1024', '1280*720', '720*1280', '1440*720', '720*1440'), 'default', '1024*1024', 'description', '输出图像的分辨率尺寸'),
            JSON_OBJECT('name', 'n', 'label', '生成数量', 'type', 'integer', 'required', false, 'min', 1, 'max', 4, 'default', 1, 'description', '生成图像的数量'),
            JSON_OBJECT('name', 'seed', 'label', '随机种子', 'type', 'integer', 'required', false, 'description', '随机种子，用于复现结果'),
            JSON_OBJECT('name', 'response_format', 'label', '响应格式', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('url', 'b64_json'), 'default', 'url', 'description', '返回图像的格式')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'task_id', 'label', '任务ID', 'type', 'string', 'path', '$.output.task_id', 'is_critical', true, 'description', '异步任务ID'),
            JSON_OBJECT('field', 'task_status', 'label', '任务状态', 'type', 'string', 'path', '$.output.task_status', 'is_critical', true, 'description', '任务状态'),
            JSON_OBJECT('field', 'image_url', 'label', '图像URL', 'type', 'string', 'path', '$.output.results[0].url', 'is_critical', true, 'description', '生成的图像URL')
        )
    ),
    'https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis',
    JSON_OBJECT(
        'billing_mode', 'per_image',
        'cost_price', 0.12,
        'suggested_price', 0.80,
        'currency', 'CNY'
    ),
    JSON_OBJECT(
        'max_resolution', '1440*1440',
        'ipm_limit', 300,
        'timeout', 120
    ),
    'async',
    JSON_ARRAY('文生图', '万相Wan', '高清图像', '中文优化'),
    1,
    1,
    110,
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
-- 2. Wan2.5-i2i-preview - 万相2.5图生图预览版
-- ============================================================
INSERT INTO `ddwx_model_info` (
    `aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`,
    `description`, `input_schema`, `output_schema`, `endpoint_url`,
    `pricing_config`, `limits_config`, `task_type`, `capability_tags`,
    `is_system`, `is_active`, `sort`, `create_time`, `update_time`
) VALUES (
    0,
    @aliyun_provider_id,
    @image_type_id,
    'wan2.5-i2i-preview',
    '万相2.5图生图预览',
    'v2.5-preview',
    '阿里云万相Wan2.5系列图生图预览版模型，支持基于参考图像生成新图像，可用于图像风格迁移、图像编辑、图像扩展等功能。预览版提供更快速的反馈。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'image', 'label', '参考图像', 'type', 'string', 'required', true, 'description', '参考图像的URL地址'),
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', true, 'description', '对目标图像的描述'),
            JSON_OBJECT('name', 'negative_prompt', 'label', '负面提示词', 'type', 'string', 'required', false, 'description', '不希望在图像中出现的内容'),
            JSON_OBJECT('name', 'strength', 'label', '重绘幅度', 'type', 'number', 'required', false, 'min', 0.0, 'max', 1.0, 'default', 0.75, 'description', '控制与参考图像的相似度，越小越相似'),
            JSON_OBJECT('name', 'size', 'label', '图像尺寸', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('1024*1024', '1024*576', '576*1024'), 'default', '1024*1024', 'description', '输出图像尺寸'),
            JSON_OBJECT('name', 'n', 'label', '生成数量', 'type', 'integer', 'required', false, 'min', 1, 'max', 4, 'default', 1, 'description', '生成图像的数量'),
            JSON_OBJECT('name', 'seed', 'label', '随机种子', 'type', 'integer', 'required', false, 'description', '随机种子，用于复现结果')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'task_id', 'label', '任务ID', 'type', 'string', 'path', '$.output.task_id', 'is_critical', true, 'description', '异步任务ID'),
            JSON_OBJECT('field', 'task_status', 'label', '任务状态', 'type', 'string', 'path', '$.output.task_status', 'is_critical', true, 'description', '任务状态'),
            JSON_OBJECT('field', 'image_url', 'label', '图像URL', 'type', 'string', 'path', '$.output.results[0].url', 'is_critical', true, 'description', '生成的图像URL')
        )
    ),
    'https://dashscope.aliyuncs.com/api/v1/services/aigc/image-generation/image-edit',
    JSON_OBJECT(
        'billing_mode', 'per_image',
        'cost_price', 0.15,
        'suggested_price', 1.00,
        'currency', 'CNY'
    ),
    JSON_OBJECT(
        'max_resolution', '1024*1024',
        'ipm_limit', 300,
        'timeout', 120
    ),
    'async',
    JSON_ARRAY('图生图', '图像编辑', '风格迁移', '预览版'),
    1,
    1,
    105,
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
-- 3. Wanx2.1-imageedit - 万相2.1图像编辑
-- ============================================================
INSERT INTO `ddwx_model_info` (
    `aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`,
    `description`, `input_schema`, `output_schema`, `endpoint_url`,
    `pricing_config`, `limits_config`, `task_type`, `capability_tags`,
    `is_system`, `is_active`, `sort`, `create_time`, `update_time`
) VALUES (
    0,
    @aliyun_provider_id,
    @image_type_id,
    'wanx2.1-imageedit',
    '万相2.1图像编辑',
    'v2.1',
    '阿里云万相Wanx2.1系列图像编辑模型，支持对图像进行智能编辑，包括局部重绘、背景替换、风格转换等高级编辑功能。适用于电商修图、创意设计、内容制作等场景。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'image', 'label', '原图', 'type', 'string', 'required', true, 'description', '需要编辑的原图像URL'),
            JSON_OBJECT('name', 'mask_image', 'label', '遮罩图', 'type', 'string', 'required', false, 'description', '局部编辑时的遮罩图URL，白色区域表示需要编辑的部分'),
            JSON_OBJECT('name', 'prompt', 'label', '编辑指令', 'type', 'string', 'required', true, 'description', '编辑指令描述'),
            JSON_OBJECT('name', 'negative_prompt', 'label', '负面提示词', 'type', 'string', 'required', false, 'description', '不希望在结果中出现的内容'),
            JSON_OBJECT('name', 'size', 'label', '输出尺寸', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('1024*1024', '1024*576', '576*1024'), 'default', '1024*1024', 'description', '输出图像尺寸'),
            JSON_OBJECT('name', 'n', 'label', '生成数量', 'type', 'integer', 'required', false, 'min', 1, 'max', 4, 'default', 1, 'description', '生成图像的数量'),
            JSON_OBJECT('name', 'seed', 'label', '随机种子', 'type', 'integer', 'required', false, 'description', '随机种子，用于复现结果')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'task_id', 'label', '任务ID', 'type', 'string', 'path', '$.output.task_id', 'is_critical', true, 'description', '异步任务ID'),
            JSON_OBJECT('field', 'task_status', 'label', '任务状态', 'type', 'string', 'path', '$.output.task_status', 'is_critical', true, 'description', '任务状态'),
            JSON_OBJECT('field', 'image_url', 'label', '图像URL', 'type', 'string', 'path', '$.output.results[0].url', 'is_critical', true, 'description', '编辑后的图像URL')
        )
    ),
    'https://dashscope.aliyuncs.com/api/v1/services/aigc/image-generation/image-edit',
    JSON_OBJECT(
        'billing_mode', 'per_image',
        'cost_price', 0.18,
        'suggested_price', 1.20,
        'currency', 'CNY'
    ),
    JSON_OBJECT(
        'max_resolution', '1024*1024',
        'ipm_limit', 300,
        'timeout', 120,
        'mask_support', true
    ),
    'async',
    JSON_ARRAY('图像编辑', '局部重绘', '背景替换', '风格转换'),
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
-- 4. Wan2.6-i2v-flash - 万相2.6图生视频快速版
-- ============================================================
INSERT INTO `ddwx_model_info` (
    `aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`,
    `description`, `input_schema`, `output_schema`, `endpoint_url`,
    `pricing_config`, `limits_config`, `task_type`, `capability_tags`,
    `is_system`, `is_active`, `sort`, `create_time`, `update_time`
) VALUES (
    0,
    @aliyun_provider_id,
    @video_type_id,
    'wan2.6-i2v-flash',
    '万相2.6图生视频快速版',
    'v2.6-flash',
    '阿里云万相Wan2.6系列图生视频快速版模型，基于参考图像快速生成短视频片段，生成速度更快，适合快速预览和迭代。支持生成最长5秒的视频。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'image', 'label', '首帧图像', 'type', 'string', 'required', true, 'description', '视频首帧图像URL'),
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', false, 'description', '视频内容描述'),
            JSON_OBJECT('name', 'duration', 'label', '视频时长', 'type', 'integer', 'required', false, 'options', JSON_ARRAY(5), 'default', 5, 'description', '生成视频的秒数(固定5秒)'),
            JSON_OBJECT('name', 'resolution', 'label', '分辨率', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('480P', '720P'), 'default', '720P', 'description', '输出视频分辨率'),
            JSON_OBJECT('name', 'fps', 'label', '帧率', 'type', 'integer', 'required', false, 'options', JSON_ARRAY(16, 24), 'default', 16, 'description', '视频帧率'),
            JSON_OBJECT('name', 'seed', 'label', '随机种子', 'type', 'integer', 'required', false, 'description', '随机种子，用于复现结果')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'task_id', 'label', '任务ID', 'type', 'string', 'path', '$.output.task_id', 'is_critical', true, 'description', '异步任务ID'),
            JSON_OBJECT('field', 'task_status', 'label', '任务状态', 'type', 'string', 'path', '$.output.task_status', 'is_critical', true, 'description', '任务状态'),
            JSON_OBJECT('field', 'video_url', 'label', '视频URL', 'type', 'string', 'path', '$.output.video_url', 'is_critical', true, 'description', '生成的视频URL')
        )
    ),
    'https://dashscope.aliyuncs.com/api/v1/services/aigc/video-generation/video-synthesis',
    JSON_OBJECT(
        'billing_mode', 'per_second',
        'cost_price', 0.15,
        'suggested_price', 0.80,
        'currency', 'CNY'
    ),
    JSON_OBJECT(
        'max_duration', 5,
        'max_resolution', '1280x720',
        'concurrent_limit', 5,
        'timeout', 180
    ),
    'async',
    JSON_ARRAY('图生视频', '快速生成', '首帧图生成', '短视频'),
    1,
    1,
    95,
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
-- 5. Wan2.6-i2v - 万相2.6图生视频标准版
-- ============================================================
INSERT INTO `ddwx_model_info` (
    `aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`,
    `description`, `input_schema`, `output_schema`, `endpoint_url`,
    `pricing_config`, `limits_config`, `task_type`, `capability_tags`,
    `is_system`, `is_active`, `sort`, `create_time`, `update_time`
) VALUES (
    0,
    @aliyun_provider_id,
    @video_type_id,
    'wan2.6-i2v',
    '万相2.6图生视频',
    'v2.6',
    '阿里云万相Wan2.6系列图生视频标准版模型，基于参考图像生成高质量视频片段，支持首尾帧图生视频功能。可生成最长10秒、1080P分辨率的高质量视频，适用于短视频创作、电商展示、社交媒体内容等场景。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'image', 'label', '首帧图像', 'type', 'string', 'required', true, 'description', '视频首帧图像URL'),
            JSON_OBJECT('name', 'last_frame_image', 'label', '尾帧图像', 'type', 'string', 'required', false, 'description', '视频尾帧图像URL(首尾帧模式)'),
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', false, 'description', '视频内容描述'),
            JSON_OBJECT('name', 'duration', 'label', '视频时长', 'type', 'integer', 'required', false, 'min', 1, 'max', 10, 'default', 5, 'description', '生成视频的秒数(1-10)'),
            JSON_OBJECT('name', 'resolution', 'label', '分辨率', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('480P', '720P', '1080P'), 'default', '720P', 'description', '输出视频分辨率'),
            JSON_OBJECT('name', 'fps', 'label', '帧率', 'type', 'integer', 'required', false, 'options', JSON_ARRAY(16, 24, 30), 'default', 24, 'description', '视频帧率'),
            JSON_OBJECT('name', 'seed', 'label', '随机种子', 'type', 'integer', 'required', false, 'description', '随机种子，用于复现结果')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'task_id', 'label', '任务ID', 'type', 'string', 'path', '$.output.task_id', 'is_critical', true, 'description', '异步任务ID'),
            JSON_OBJECT('field', 'task_status', 'label', '任务状态', 'type', 'string', 'path', '$.output.task_status', 'is_critical', true, 'description', '任务状态'),
            JSON_OBJECT('field', 'video_url', 'label', '视频URL', 'type', 'string', 'path', '$.output.video_url', 'is_critical', true, 'description', '生成的视频URL')
        )
    ),
    'https://dashscope.aliyuncs.com/api/v1/services/aigc/video-generation/video-synthesis',
    JSON_OBJECT(
        'billing_mode', 'per_second',
        'cost_price', 0.30,
        'suggested_price', 1.50,
        'currency', 'CNY'
    ),
    JSON_OBJECT(
        'max_duration', 10,
        'max_resolution', '1920x1080',
        'concurrent_limit', 3,
        'timeout', 300
    ),
    'async',
    JSON_ARRAY('图生视频', '首尾帧图生视频', '高质量', '1080P'),
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
-- 6. Wan2.5-i2v-preview - 万相2.5图生视频预览版
-- ============================================================
INSERT INTO `ddwx_model_info` (
    `aid`, `provider_id`, `type_id`, `model_code`, `model_name`, `model_version`,
    `description`, `input_schema`, `output_schema`, `endpoint_url`,
    `pricing_config`, `limits_config`, `task_type`, `capability_tags`,
    `is_system`, `is_active`, `sort`, `create_time`, `update_time`
) VALUES (
    0,
    @aliyun_provider_id,
    @video_type_id,
    'wan2.5-i2v-preview',
    '万相2.5图生视频预览',
    'v2.5-preview',
    '阿里云万相Wan2.5系列图生视频预览版模型，支持基于参考图像生成视频片段。预览版提供更快的生成速度，适合快速验证创意。支持生成最长5秒的视频。',
    JSON_OBJECT(
        'parameters', JSON_ARRAY(
            JSON_OBJECT('name', 'image', 'label', '首帧图像', 'type', 'string', 'required', true, 'description', '视频首帧图像URL'),
            JSON_OBJECT('name', 'prompt', 'label', '提示词', 'type', 'string', 'required', false, 'description', '视频内容描述'),
            JSON_OBJECT('name', 'duration', 'label', '视频时长', 'type', 'integer', 'required', false, 'options', JSON_ARRAY(5), 'default', 5, 'description', '生成视频的秒数(固定5秒)'),
            JSON_OBJECT('name', 'resolution', 'label', '分辨率', 'type', 'enum', 'required', false, 'options', JSON_ARRAY('480P', '720P'), 'default', '720P', 'description', '输出视频分辨率'),
            JSON_OBJECT('name', 'fps', 'label', '帧率', 'type', 'integer', 'required', false, 'options', JSON_ARRAY(16, 24), 'default', 16, 'description', '视频帧率'),
            JSON_OBJECT('name', 'seed', 'label', '随机种子', 'type', 'integer', 'required', false, 'description', '随机种子，用于复现结果')
        )
    ),
    JSON_OBJECT(
        'fields', JSON_ARRAY(
            JSON_OBJECT('field', 'task_id', 'label', '任务ID', 'type', 'string', 'path', '$.output.task_id', 'is_critical', true, 'description', '异步任务ID'),
            JSON_OBJECT('field', 'task_status', 'label', '任务状态', 'type', 'string', 'path', '$.output.task_status', 'is_critical', true, 'description', '任务状态'),
            JSON_OBJECT('field', 'video_url', 'label', '视频URL', 'type', 'string', 'path', '$.output.video_url', 'is_critical', true, 'description', '生成的视频URL')
        )
    ),
    'https://dashscope.aliyuncs.com/api/v1/services/aigc/video-generation/video-synthesis',
    JSON_OBJECT(
        'billing_mode', 'per_second',
        'cost_price', 0.12,
        'suggested_price', 0.60,
        'currency', 'CNY'
    ),
    JSON_OBJECT(
        'max_duration', 5,
        'max_resolution', '1280x720',
        'concurrent_limit', 5,
        'timeout', 180
    ),
    'async',
    JSON_ARRAY('图生视频', '预览版', '首帧图生成', '快速预览'),
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

-- ============================================================
-- 提交事务
-- ============================================================
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
    'wan2.6-image',
    'wan2.5-i2i-preview',
    'wanx2.1-imageedit',
    'wan2.6-i2v-flash',
    'wan2.6-i2v',
    'wan2.5-i2v-preview'
)
ORDER BY m.sort ASC;

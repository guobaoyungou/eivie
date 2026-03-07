-- ================================================================
-- 模型能力调用示例数据
-- 创建时间：2026-02-27
-- 说明：为 doubao-seedream-5-0-260128 模型预置6种能力的调用示例
-- ================================================================

USE guobaoyungou_cn;

-- 获取模型ID（根据 model_code 查询）
SET @model_id = (SELECT id FROM ddwx_model_info WHERE model_code = 'doubao-seedream-5-0-260128' LIMIT 1);
SET @now = UNIX_TIMESTAMP();

-- 如果模型不存在，使用默认ID=0（示例仍可作为参考模板）
SET @model_id = IFNULL(@model_id, 0);

-- ================================================================
-- 能力1：文生图-生成单张图 (text2image_single)
-- ================================================================
INSERT INTO `ddwx_model_capability_example` (
    `aid`, `model_id`, `capability_type`, `example_name`, `description`,
    `request_params`, `response_example`, `notes`,
    `is_default`, `sort`, `status`, `create_time`, `update_time`
) VALUES (
    0, @model_id, 1,
    '橘猫图片生成',
    '生成一张可爱橘猫的高清图片，展示文生图-单张能力',
    JSON_OBJECT(
        'prompt', '一只可爱的橘猫坐在阳光下的窗台上，毛发蓬松，眼睛明亮，高清摄影风格，4K画质，柔和的自然光线',
        'size', '2K',
        'response_format', 'url',
        'watermark', false
    ),
    JSON_OBJECT(
        'created', 1709020800,
        'data', JSON_ARRAY(
            JSON_OBJECT('url', 'https://example.com/image.jpg')
        )
    ),
    '提示词越详细，生成效果越好。建议包含主体描述、风格、光线等要素。',
    1, 10, 1, @now, @now
);

-- ================================================================
-- 能力2：文生图-生成一组图 (text2image_batch)
-- ================================================================
INSERT INTO `ddwx_model_capability_example` (
    `aid`, `model_id`, `capability_type`, `example_name`, `description`,
    `request_params`, `response_example`, `notes`,
    `is_default`, `sort`, `status`, `create_time`, `update_time`
) VALUES (
    0, @model_id, 2,
    '橘猫系列图生成',
    '批量生成一组可爱橘猫的图片，展示文生图-组图能力',
    JSON_OBJECT(
        'prompt', '一只可爱的橘猫，不同姿态和表情，毛发蓬松，高清摄影风格，温馨氛围',
        'sequential_image_generation', 'auto',
        'sequential_image_generation_options', JSON_OBJECT('max_images', 4),
        'size', '2K',
        'stream', true
    ),
    JSON_OBJECT(
        'created', 1709020800,
        'data', JSON_ARRAY(
            JSON_OBJECT('url', 'https://example.com/image1.jpg'),
            JSON_OBJECT('url', 'https://example.com/image2.jpg'),
            JSON_OBJECT('url', 'https://example.com/image3.jpg'),
            JSON_OBJECT('url', 'https://example.com/image4.jpg')
        )
    ),
    '组图生成会产生多张风格相近但各有特色的图片，建议max_images设置为4-6张。',
    1, 20, 1, @now, @now
);

-- ================================================================
-- 能力3：图生图-单张图生成单张图 (image2image_single)
-- ================================================================
INSERT INTO `ddwx_model_capability_example` (
    `aid`, `model_id`, `capability_type`, `example_name`, `description`,
    `request_params`, `response_example`, `notes`,
    `is_default`, `sort`, `status`, `create_time`, `update_time`
) VALUES (
    0, @model_id, 3,
    '照片风格转换',
    '将照片转换为油画风格，展示图生图-单入单出能力',
    JSON_OBJECT(
        'image', 'https://example.com/reference.jpg',
        'prompt', '将照片转换为油画风格，保持人物主体特征，增添艺术质感，印象派风格',
        'sequential_image_generation', 'disabled',
        'size', '2K'
    ),
    JSON_OBJECT(
        'created', 1709020800,
        'data', JSON_ARRAY(
            JSON_OBJECT('url', 'https://example.com/result.jpg')
        )
    ),
    '请上传清晰的参考图片以获得更好效果。提示词中描述期望的变换方向。',
    1, 30, 1, @now, @now
);

-- ================================================================
-- 能力4：图生图-单张图生成一组图 (image2image_batch)
-- ================================================================
INSERT INTO `ddwx_model_capability_example` (
    `aid`, `model_id`, `capability_type`, `example_name`, `description`,
    `request_params`, `response_example`, `notes`,
    `is_default`, `sort`, `status`, `create_time`, `update_time`
) VALUES (
    0, @model_id, 4,
    '照片多风格生成',
    '基于一张照片生成多种艺术风格，展示图生图-单入多出能力',
    JSON_OBJECT(
        'image', 'https://example.com/reference.jpg',
        'prompt', '基于参考照片，生成多种艺术风格变化，包括油画、水彩、素描、漫画等风格',
        'sequential_image_generation', 'auto',
        'sequential_image_generation_options', JSON_OBJECT('max_images', 6),
        'size', '2K',
        'stream', true
    ),
    JSON_OBJECT(
        'created', 1709020800,
        'data', JSON_ARRAY(
            JSON_OBJECT('url', 'https://example.com/style1.jpg'),
            JSON_OBJECT('url', 'https://example.com/style2.jpg'),
            JSON_OBJECT('url', 'https://example.com/style3.jpg'),
            JSON_OBJECT('url', 'https://example.com/style4.jpg'),
            JSON_OBJECT('url', 'https://example.com/style5.jpg'),
            JSON_OBJECT('url', 'https://example.com/style6.jpg')
        )
    ),
    '每张生成图都会有不同的艺术风格，适合创意探索和风格选择。',
    1, 40, 1, @now, @now
);

-- ================================================================
-- 能力5：图生图-多张参考图生成单张图 (multi_image2image_single)
-- ================================================================
INSERT INTO `ddwx_model_capability_example` (
    `aid`, `model_id`, `capability_type`, `example_name`, `description`,
    `request_params`, `response_example`, `notes`,
    `is_default`, `sort`, `status`, `create_time`, `update_time`
) VALUES (
    0, @model_id, 5,
    '多图融合创作',
    '融合多张参考图生成新图，展示多图入-单出能力',
    JSON_OBJECT(
        'image', JSON_ARRAY(
            'https://example.com/ref1.jpg',
            'https://example.com/ref2.jpg',
            'https://example.com/ref3.jpg'
        ),
        'prompt', '融合多张参考图的元素，创作全新的艺术作品，保持和谐统一的风格',
        'sequential_image_generation', 'disabled',
        'size', '2K'
    ),
    JSON_OBJECT(
        'created', 1709020800,
        'data', JSON_ARRAY(
            JSON_OBJECT('url', 'https://example.com/merged.jpg')
        )
    ),
    '建议上传风格相近的参考图以获得更协调的效果。系统会自动融合多图特征。',
    1, 50, 1, @now, @now
);

-- ================================================================
-- 能力6：图生图-多张参考图生成一组图 (multi_image2image_batch)
-- ================================================================
INSERT INTO `ddwx_model_capability_example` (
    `aid`, `model_id`, `capability_type`, `example_name`, `description`,
    `request_params`, `response_example`, `notes`,
    `is_default`, `sort`, `status`, `create_time`, `update_time`
) VALUES (
    0, @model_id, 6,
    '多图融合系列创作',
    '基于多图融合生成系列作品，展示多图入-多出能力',
    JSON_OBJECT(
        'image', JSON_ARRAY(
            'https://example.com/ref1.jpg',
            'https://example.com/ref2.jpg',
            'https://example.com/ref3.jpg'
        ),
        'prompt', '融合多张参考图的元素，创作系列艺术作品，保持风格统一但各有特色',
        'sequential_image_generation', 'auto',
        'sequential_image_generation_options', JSON_OBJECT('max_images', 4),
        'size', '2K',
        'stream', true
    ),
    JSON_OBJECT(
        'created', 1709020800,
        'data', JSON_ARRAY(
            JSON_OBJECT('url', 'https://example.com/series1.jpg'),
            JSON_OBJECT('url', 'https://example.com/series2.jpg'),
            JSON_OBJECT('url', 'https://example.com/series3.jpg'),
            JSON_OBJECT('url', 'https://example.com/series4.jpg')
        )
    ),
    '适合批量创作系列主题作品，每张图都会融合参考图特征但各有变化。',
    1, 60, 1, @now, @now
);

-- ================================================================
-- 验证插入结果
-- ================================================================
-- SELECT id, model_id, capability_type, example_name, is_default 
-- FROM ddwx_model_capability_example 
-- ORDER BY capability_type;

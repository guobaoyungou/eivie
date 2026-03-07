-- ================================================================
-- 场景类型元数据表创建脚本
-- 创建时间：2026-02-06
-- 说明：存储6种场景类型的元数据配置
-- ================================================================

USE ddwx;

-- 创建场景类型元数据表
CREATE TABLE IF NOT EXISTS `ddwx_ai_travel_photo_scene_type` (
  `scene_type` int(11) NOT NULL COMMENT '场景类型编码（1-6）',
  `scene_name` varchar(100) NOT NULL COMMENT '场景名称',
  `scene_code` varchar(50) NOT NULL COMMENT '场景代码',
  `description` text COMMENT '场景描述',
  `input_requirements` json COMMENT '输入要求（参数列表）',
  `output_type` varchar(50) NOT NULL COMMENT '输出类型（single_image/multiple_images/video）',
  `form_template` json COMMENT '表单模板配置',
  `icon` varchar(200) DEFAULT NULL COMMENT '图标URL',
  `sort` int(11) NOT NULL DEFAULT '100' COMMENT '排序权重',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`scene_type`),
  UNIQUE KEY `idx_scene_code` (`scene_code`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='场景类型元数据表';

-- 插入6种场景类型的元数据
INSERT INTO `ddwx_ai_travel_photo_scene_type` (`scene_type`, `scene_name`, `scene_code`, `description`, `input_requirements`, `output_type`, `form_template`, `sort`, `is_active`, `create_time`, `update_time`)
VALUES
(1, '文生图-生成单张图', 'text2image_single', '根据文本提示词生成单张图片', 
 '["prompt"]', 
 'single_image', 
 '{"required_params": ["prompt"], "optional_params": ["negative_prompt", "size", "style", "watermark"]}', 
 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

(2, '文生图-生成一组图', 'text2image_batch', '根据文本提示词生成多张图片（1-6张）', 
 '["prompt", "n"]', 
 'multiple_images', 
 '{"required_params": ["prompt", "n"], "optional_params": ["negative_prompt", "size", "style", "watermark"], "n_range": [1, 6]}', 
 20, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

(3, '图生图-单张图生成单张图', 'image2image_single', '参考单张图片生成新图片', 
 '["image", "prompt"]', 
 'single_image', 
 '{"required_params": ["image", "prompt"], "optional_params": ["negative_prompt", "size", "watermark"]}', 
 30, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

(4, '图生图-单张图生成一组图', 'image2image_batch', '参考单张图片生成多张新图片（1-10张）', 
 '["image", "prompt", "sequential_image_generation_options"]', 
 'multiple_images', 
 '{"required_params": ["image", "prompt", "sequential_image_generation_options"], "optional_params": ["negative_prompt", "size", "watermark"], "max_images_range": [1, 10]}', 
 40, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

(5, '图生图-多张参考图生成单张图', 'multi_image2image_single', '融合多张参考图（1-10张）生成新图片', 
 '["image[]", "prompt"]', 
 'single_image', 
 '{"required_params": ["image[]", "prompt"], "optional_params": ["negative_prompt", "size", "watermark"], "image_count_range": [1, 10]}', 
 50, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

(6, '图生图-多张参考图生成一组图', 'multi_image2image_batch', '融合多张参考图（1-10张）生成多张新图片（1-10张）', 
 '["image[]", "prompt", "sequential_image_generation_options"]', 
 'multiple_images', 
 '{"required_params": ["image[]", "prompt", "sequential_image_generation_options"], "optional_params": ["negative_prompt", "size", "watermark"], "image_count_range": [1, 10], "max_images_range": [1, 10]}', 
 60, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 验证安装结果
SELECT '=== 场景类型元数据表安装完成 ===' AS status;
SELECT CONCAT('场景类型记录数: ', COUNT(*), ' 条') AS result FROM `ddwx_ai_travel_photo_scene_type`;

-- 显示场景类型列表
SELECT scene_type, scene_name, scene_code, output_type, is_active FROM `ddwx_ai_travel_photo_scene_type` ORDER BY sort;

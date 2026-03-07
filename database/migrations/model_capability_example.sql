-- ================================================================
-- 模型能力调用示例表
-- 创建时间：2026-02-27
-- 说明：为模型的每种能力类型存储标准调用示例
-- ================================================================

USE guobaoyungou_cn;

-- 创建模型能力调用示例表
CREATE TABLE IF NOT EXISTS `ddwx_model_capability_example` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '平台ID，0=系统级',
  `model_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联 ddwx_model_info.id',
  `capability_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '能力类型（1-6）',
  `example_name` varchar(100) NOT NULL DEFAULT '' COMMENT '示例名称，如"橘猫图片生成"',
  `description` text COMMENT '示例描述说明',
  `request_params` json DEFAULT NULL COMMENT '请求参数结构化示例',
  `response_example` json DEFAULT NULL COMMENT '响应结构示例',
  `notes` text COMMENT '注意事项',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为默认示例（1=是）',
  `sort` int(11) NOT NULL DEFAULT '100' COMMENT '排序权重',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态（1=启用，0=禁用）',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  KEY `idx_model_cap` (`model_id`, `capability_type`),
  KEY `idx_aid` (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='模型能力调用示例表';

-- 为 ddwx_generation_record 表新增 capability_type 字段
ALTER TABLE `ddwx_generation_record` 
ADD COLUMN `capability_type` tinyint(1) NOT NULL DEFAULT '0' 
COMMENT '能力类型（1-6）：1文生图单张/2文生图组图/3图生图单入单出/4图生图单入多出/5多图入单出/6多图入多出' 
AFTER `scene_id`;

-- 添加索引
ALTER TABLE `ddwx_generation_record` ADD INDEX `idx_capability_type` (`capability_type`);

-- ================================================================
-- 能力类型定义说明
-- ================================================================
-- 能力类型 1: 文生图-生成单张图 (text2image_single)
--   - 输入：提示词
--   - 输出：单张图片
--
-- 能力类型 2: 文生图-生成一组图 (text2image_batch)
--   - 输入：提示词 + 数量
--   - 输出：多张图片(1-6)
--
-- 能力类型 3: 图生图-单张图生成单张图 (image2image_single)
--   - 输入：单张参考图 + 提示词
--   - 输出：单张图片
--
-- 能力类型 4: 图生图-单张图生成一组图 (image2image_batch)
--   - 输入：单张参考图 + 提示词 + 数量
--   - 输出：多张图片(1-10)
--
-- 能力类型 5: 图生图-多张参考图生成单张图 (multi_image2image_single)
--   - 输入：多张参考图(1-10) + 提示词
--   - 输出：单张图片
--
-- 能力类型 6: 图生图-多张参考图生成一组图 (multi_image2image_batch)
--   - 输入：多张参考图(1-10) + 提示词 + 数量
--   - 输出：多张图片(1-10)
-- ================================================================

-- 验证查询
-- DESC ddwx_model_capability_example;
-- DESC ddwx_generation_record;

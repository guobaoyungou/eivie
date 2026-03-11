-- ======================================
-- 生成订单表字段补齐迁移脚本
-- 日期: 2026-03-09
-- 说明: 为 ddwx_generation_order 表新增 user_prompt, ref_images, template_snapshot 三个字段
--       修复首页场景弹窗创建订单时因字段缺失导致的 SQL 异常
-- ======================================

-- 新增 user_prompt 字段（用户自定义提示词）
ALTER TABLE `ddwx_generation_order`
ADD COLUMN `user_prompt` TEXT DEFAULT NULL COMMENT '用户自定义提示词' AFTER `remark`;

-- 新增 ref_images 字段（参考图URL列表，JSON数组格式）
ALTER TABLE `ddwx_generation_order`
ADD COLUMN `ref_images` TEXT DEFAULT NULL COMMENT '参考图URL列表（JSON数组）' AFTER `user_prompt`;

-- 新增 template_snapshot 字段（下单时场景模板快照，JSON格式）
ALTER TABLE `ddwx_generation_order`
ADD COLUMN `template_snapshot` TEXT DEFAULT NULL COMMENT '下单时场景模板快照（JSON）' AFTER `ref_images`;

-- 新增 model_id 字段（模型直选时的模型ID）
ALTER TABLE `ddwx_generation_order`
ADD COLUMN `model_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '模型ID（模型直选时使用）' AFTER `scene_id`;

-- ======================================
-- 验证字段是否添加成功
-- ======================================
-- SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_COMMENT
-- FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_NAME = 'ddwx_generation_order'
-- AND COLUMN_NAME IN ('user_prompt', 'ref_images', 'template_snapshot', 'model_id');

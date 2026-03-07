-- 为AI模型配置表添加模型名称简写字段
-- 执行时间：2026-02-04

ALTER TABLE `ddwx_ai_travel_photo_model`
ADD COLUMN `short_name` VARCHAR(50) DEFAULT NULL COMMENT '模型名称简写，便于后期调用和显示' AFTER `model_name`,
ADD INDEX `idx_short_name` (`short_name`);

COMMIT;

-- 为场景表添加 api_config_id 字段
-- 用于关联API配置，支持每个场景使用不同的API配置

ALTER TABLE `ddwx_ai_travel_photo_scene` 
ADD COLUMN `api_config_id` int(11) DEFAULT 0 COMMENT 'API配置ID，关联api_config表' AFTER `model_id`;

-- 添加索引以优化查询性能
ALTER TABLE `ddwx_ai_travel_photo_scene` 
ADD INDEX `idx_api_config_id` (`api_config_id`);

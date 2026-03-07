-- ================================================================
-- AI旅拍场景管理重构 - 数据库表结构调整
-- 创建时间：2026-02-04
-- 说明：为场景表添加API配置关联字段，整合模型配置和API配置
-- ================================================================

-- ----------------------------
-- 修改场景表：添加API配置关联字段
-- ----------------------------
ALTER TABLE `ddwx_ai_travel_photo_scene` 
ADD COLUMN `api_config_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联API配置ID(ddwx_api_config.id)' AFTER `model_id`;

-- 添加索引
ALTER TABLE `ddwx_ai_travel_photo_scene` 
ADD INDEX `idx_api_config_id` (`api_config_id`);

-- 确保 model_params 字段类型正确（如果已存在则跳过错误）
ALTER TABLE `ddwx_ai_travel_photo_scene` 
MODIFY COLUMN `model_params` text COMMENT '模型参数JSON(格式：{"prompt":"...","negative_prompt":"...","steps":50})';

-- ----------------------------
-- 验证查询：检查表结构
-- ----------------------------
-- 执行后请运行以下SQL验证：
-- DESC ddwx_ai_travel_photo_scene;
-- SHOW INDEX FROM ddwx_ai_travel_photo_scene;

-- ----------------------------
-- 数据迁移说明
-- ----------------------------
-- 1. 现有场景数据的 model_id 字段保持不变
-- 2. api_config_id 默认为0，后续编辑场景时需要选择API配置
-- 3. model_params 字段现有数据结构保持不变
-- 4. 建议后台提供批量设置API配置的功能

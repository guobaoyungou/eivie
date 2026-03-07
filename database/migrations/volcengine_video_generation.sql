-- ============================================================
-- 火山引擎方舟视频生成能力重构 - 数据库迁移脚本
-- 创建时间: 2026-02-28
-- 说明: 为ddwx_ai_travel_photo_generation表增加火山引擎视频生成相关字段
-- ============================================================

-- 1. 为生成记录表增加火山引擎视频相关字段
-- ============================================

-- 外部任务ID（火山方舟返回的任务ID）
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD COLUMN `external_task_id` VARCHAR(128) DEFAULT NULL 
COMMENT '火山方舟/外部平台返回的任务ID'
AFTER `task_id`;

-- 服务提供商
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD COLUMN `provider` VARCHAR(32) DEFAULT NULL 
COMMENT '服务提供商（volcengine/kling/aliyun）'
AFTER `external_task_id`;

-- 实际使用的模型代码
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD COLUMN `model_code` VARCHAR(64) DEFAULT NULL 
COMMENT '实际使用的模型代码（如doubao-seedance-1-5-pro）'
AFTER `provider`;

-- 视频生成模式
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD COLUMN `video_mode` VARCHAR(32) DEFAULT NULL 
COMMENT '视频生成模式（text_to_video/first_frame/first_last_frame/reference_images）'
AFTER `model_code`;

-- 是否有声视频
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD COLUMN `with_audio` TINYINT(1) DEFAULT 0 
COMMENT '是否有声视频：0否 1是'
AFTER `video_mode`;

-- 请求的视频时长
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD COLUMN `video_duration` INT DEFAULT NULL 
COMMENT '请求的视频时长（秒）'
AFTER `with_audio`;

-- 请求的分辨率
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD COLUMN `video_resolution` VARCHAR(16) DEFAULT NULL 
COMMENT '请求的分辨率（720p/1080p）'
AFTER `video_duration`;

-- API请求记录
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD COLUMN `api_request` TEXT DEFAULT NULL 
COMMENT '发送的完整API请求JSON'
AFTER `video_resolution`;

-- API响应记录
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD COLUMN `api_response` TEXT DEFAULT NULL 
COMMENT '收到的API响应JSON'
AFTER `api_request`;

-- 结果视频URL
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD COLUMN `result_video_url` VARCHAR(512) DEFAULT NULL 
COMMENT '生成结果视频URL'
AFTER `api_response`;

-- 结果音频URL（有声视频）
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD COLUMN `result_audio_url` VARCHAR(512) DEFAULT NULL 
COMMENT '有声视频的音频URL'
AFTER `result_video_url`;

-- 开始处理时间
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD COLUMN `start_time` INT(11) DEFAULT NULL 
COMMENT '开始处理时间戳'
AFTER `result_audio_url`;

-- 完成时间（兼容现有finish_time字段，如果不存在则添加）
-- 注意：如果finish_time字段已存在，此语句会报错可忽略
-- ALTER TABLE `ddwx_ai_travel_photo_generation`
-- ADD COLUMN `finish_time` INT(11) DEFAULT NULL 
-- COMMENT '完成时间戳'
-- AFTER `start_time`;

-- 2. 添加索引
-- ============================================

-- provider索引（按服务提供商查询）
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD INDEX `idx_provider` (`provider`);

-- model_code索引（按模型代码查询）
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD INDEX `idx_model_code` (`model_code`);

-- external_task_id索引（按外部任务ID查询）
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD INDEX `idx_external_task_id` (`external_task_id`);

-- video_mode索引
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD INDEX `idx_video_mode` (`video_mode`);

-- 复合索引：provider+status（查询某provider的待处理任务）
ALTER TABLE `ddwx_ai_travel_photo_generation`
ADD INDEX `idx_provider_status` (`provider`, `status`);

-- 3. 为aivideo_task表增加provider和external_task_id字段（如果表存在）
-- ============================================

-- 尝试添加provider字段
ALTER TABLE `ddwx_aivideo_task`
ADD COLUMN `provider` VARCHAR(32) DEFAULT NULL 
COMMENT '服务提供商（volcengine/kling）'
AFTER `task_status_msg`;

-- 尝试添加external_task_id字段
ALTER TABLE `ddwx_aivideo_task`
ADD COLUMN `external_task_id` VARCHAR(128) DEFAULT NULL 
COMMENT '外部平台任务ID'
AFTER `provider`;

-- 4. 模型信息和实例配置
-- ============================================
-- 注意：Seedance系列模型的完整ddwx_model_info和ddwx_ai_model_instance记录
-- 已统一在 preset_volcengine_models.sql 中维护，请执行该文件以初始化模型数据。
-- 
-- 包含的模型：
--   - doubao-seedance-1-5-pro    (豆包SeeDance 1.5 Pro - 专业版)
--   - doubao-seedance-1-0-pro    (豆包SeeDance 1.0 Pro)
--   - doubao-seedance-1-0-pro-fast (豆包SeeDance 1.0 Pro Fast)
--   - doubao-seedance-1-0-lite-t2v (豆包SeeDance 1.0 Lite 文生)
--   - doubao-seedance-1-0-lite-i2v (豆包SeeDance 1.0 Lite 图生)
--   - doubao-seedance-2-0        (豆包SeeDance 2.0 - 标准版)
-- 
-- 请确保执行顺序：
--   1. 先执行本文件（volcengine_video_generation.sql）添加表字段
--   2. 再执行 preset_volcengine_models.sql 初始化模型数据

-- ============================================================
-- AI旅拍系统设置清理 - 删除OSS配置、队列配置、监控告警相关字段
-- 执行时间: 2026-03-13
-- ============================================================

-- 删除OSS配置相关字段
ALTER TABLE `ddwx_business` 
DROP COLUMN IF EXISTS `ai_oss_access_key_id`,
DROP COLUMN IF EXISTS `ai_oss_access_key_secret`,
DROP COLUMN IF EXISTS `ai_oss_bucket`,
DROP COLUMN IF EXISTS `ai_oss_endpoint`,
DROP COLUMN IF EXISTS `ai_oss_domain`;

-- 删除队列配置相关字段
ALTER TABLE `ddwx_business` 
DROP COLUMN IF EXISTS `ai_queue_cutout_concurrent`,
DROP COLUMN IF EXISTS `ai_queue_image_concurrent`,
DROP COLUMN IF EXISTS `ai_queue_video_concurrent`,
DROP COLUMN IF EXISTS `ai_queue_cutout_timeout`,
DROP COLUMN IF EXISTS `ai_queue_image_timeout`,
DROP COLUMN IF EXISTS `ai_queue_video_timeout`;

-- 删除监控告警相关字段
ALTER TABLE `ddwx_business` 
DROP COLUMN IF EXISTS `ai_monitor_queue_threshold`,
DROP COLUMN IF EXISTS `ai_monitor_fail_rate`,
DROP COLUMN IF EXISTS `ai_monitor_response_time`,
DROP COLUMN IF EXISTS `ai_monitor_alert_emails`;

-- ============================================================
-- 说明：
-- 本SQL删除AI旅拍系统中不再需要的以下功能相关字段：
-- 1. OSS配置（阿里云OSS存储配置）
-- 2. 队列配置（抠图/图生图/图生视频的并发数和超时时间）
-- 3. 监控告警（队列积压/失败率/响应时间阈值和邮箱告警）
-- ============================================================

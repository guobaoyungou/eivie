-- ============================================
-- 后台系统设置功能扩展 - 数据库变更SQL
-- 创建时间：2026-01-22
-- 说明：为AI旅拍系统设置功能添加OSS、队列、监控等配置字段
-- ============================================

-- ============================================
-- 1. ddwx_business表新增字段
-- ============================================

-- OSS配置字段
ALTER TABLE `ddwx_business`
ADD COLUMN `ai_oss_access_key_id` varchar(100) DEFAULT NULL COMMENT '阿里云OSS AccessKey ID' AFTER `ai_max_scenes`,
ADD COLUMN `ai_oss_access_key_secret` varchar(100) DEFAULT NULL COMMENT '阿里云OSS AccessKey Secret' AFTER `ai_oss_access_key_id`,
ADD COLUMN `ai_oss_bucket` varchar(100) DEFAULT NULL COMMENT 'OSS Bucket名称' AFTER `ai_oss_access_key_secret`,
ADD COLUMN `ai_oss_endpoint` varchar(100) DEFAULT NULL COMMENT 'OSS Endpoint' AFTER `ai_oss_bucket`,
ADD COLUMN `ai_oss_domain` varchar(255) DEFAULT NULL COMMENT 'OSS CDN域名' AFTER `ai_oss_endpoint`;

-- 队列配置字段
ALTER TABLE `ddwx_business`
ADD COLUMN `ai_queue_cutout_concurrent` int(11) DEFAULT 10 COMMENT '抠图队列并发数' AFTER `ai_oss_domain`,
ADD COLUMN `ai_queue_image_concurrent` int(11) DEFAULT 5 COMMENT '图生图队列并发数' AFTER `ai_queue_cutout_concurrent`,
ADD COLUMN `ai_queue_video_concurrent` int(11) DEFAULT 3 COMMENT '图生视频队列并发数' AFTER `ai_queue_image_concurrent`,
ADD COLUMN `ai_queue_cutout_timeout` int(11) DEFAULT 120 COMMENT '抠图队列超时时间（秒）' AFTER `ai_queue_video_concurrent`,
ADD COLUMN `ai_queue_image_timeout` int(11) DEFAULT 180 COMMENT '图生图队列超时时间（秒）' AFTER `ai_queue_cutout_timeout`,
ADD COLUMN `ai_queue_video_timeout` int(11) DEFAULT 600 COMMENT '图生视频队列超时时间（秒）' AFTER `ai_queue_image_timeout`;

-- 监控告警配置字段
ALTER TABLE `ddwx_business`
ADD COLUMN `ai_monitor_queue_threshold` int(11) DEFAULT 1000 COMMENT '队列积压告警阈值' AFTER `ai_queue_video_timeout`,
ADD COLUMN `ai_monitor_fail_rate` int(11) DEFAULT 5 COMMENT '失败率告警阈值（%）' AFTER `ai_monitor_queue_threshold`,
ADD COLUMN `ai_monitor_response_time` int(11) DEFAULT 90 COMMENT '响应时间告警阈值（秒）' AFTER `ai_monitor_fail_rate`,
ADD COLUMN `ai_monitor_alert_emails` text DEFAULT NULL COMMENT '告警邮箱列表（JSON）' AFTER `ai_monitor_response_time`;

-- ============================================
-- 2. ddwx_ai_travel_photo_model表新增统计字段
-- ============================================

ALTER TABLE `ddwx_ai_travel_photo_model`
ADD COLUMN `current_concurrent` int(11) DEFAULT 0 COMMENT '当前并发数' AFTER `sort`,
ADD COLUMN `max_concurrent` int(11) DEFAULT 5 COMMENT '最大并发数' AFTER `current_concurrent`,
ADD COLUMN `total_calls` int(11) DEFAULT 0 COMMENT '总调用次数' AFTER `max_concurrent`,
ADD COLUMN `success_calls` int(11) DEFAULT 0 COMMENT '成功调用次数' AFTER `total_calls`,
ADD COLUMN `fail_calls` int(11) DEFAULT 0 COMMENT '失败调用次数' AFTER `success_calls`,
ADD COLUMN `total_cost` decimal(12,4) DEFAULT 0.0000 COMMENT '总消耗成本' AFTER `fail_calls`,
ADD COLUMN `last_call_time` int(11) DEFAULT 0 COMMENT '最后调用时间' AFTER `total_cost`;

-- ============================================
-- 3. 为ddwx_ai_travel_photo_model表添加组合索引
-- ============================================

ALTER TABLE `ddwx_ai_travel_photo_model`
ADD INDEX `idx_bid_type_status` (`bid`, `model_type`, `status`);

-- ============================================
-- 4. 为已启用AI旅拍的商家初始化默认Key配置（如果不存在）
-- ============================================

-- 插入通义万相默认配置
INSERT INTO `ddwx_ai_travel_photo_model` 
(aid, bid, model_type, model_name, category_id, api_base_url, max_concurrent, 
 cost_per_image, status, is_default, sort, create_time)
SELECT 
    aid,
    id as bid,
    'tongyi_wanxiang' as model_type,
    '通义万相-图生图' as model_name,
    1 as category_id,
    'https://dashscope.aliyuncs.com' as api_base_url,
    5 as max_concurrent,
    0.0500 as cost_per_image,
    0 as status,
    1 as is_default,
    100 as sort,
    UNIX_TIMESTAMP() as create_time
FROM ddwx_business 
WHERE ai_travel_photo_enabled = 1
AND NOT EXISTS (
    SELECT 1 FROM ddwx_ai_travel_photo_model 
    WHERE ddwx_ai_travel_photo_model.bid = ddwx_business.id 
    AND model_type = 'tongyi_wanxiang'
);

-- 插入可灵AI默认配置
INSERT INTO `ddwx_ai_travel_photo_model` 
(aid, bid, model_type, model_name, category_id, api_base_url, max_concurrent, 
 cost_per_video, status, is_default, sort, create_time)
SELECT 
    aid,
    id as bid,
    'kling_ai' as model_type,
    '可灵AI-图生视频' as model_name,
    2 as category_id,
    'https://api.klingai.com' as api_base_url,
    3 as max_concurrent,
    0.5000 as cost_per_video,
    0 as status,
    1 as is_default,
    200 as sort,
    UNIX_TIMESTAMP() as create_time
FROM ddwx_business 
WHERE ai_travel_photo_enabled = 1
AND NOT EXISTS (
    SELECT 1 FROM ddwx_ai_travel_photo_model 
    WHERE ddwx_ai_travel_photo_model.bid = ddwx_business.id 
    AND model_type = 'kling_ai'
);

-- ============================================
-- 执行说明
-- ============================================
-- 1. 请在执行前备份相关数据表
-- 2. 建议在非高峰期执行
-- 3. 执行完成后验证字段是否正确添加
-- 4. 检查默认配置数据是否正确插入
-- ============================================

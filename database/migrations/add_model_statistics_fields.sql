-- ============================================
-- 快速修复：添加 ddwx_ai_travel_photo_model 表的统计字段
-- 执行时间：2026-01-22
-- 说明：如果字段已存在，SQL会报错但不影响数据
-- ============================================

-- 添加统计相关字段
ALTER TABLE `ddwx_ai_travel_photo_model`
ADD COLUMN `current_concurrent` int(11) DEFAULT 0 COMMENT '当前并发数' AFTER `sort`,
ADD COLUMN `max_concurrent` int(11) DEFAULT 5 COMMENT '最大并发数' AFTER `current_concurrent`,
ADD COLUMN `total_calls` int(11) DEFAULT 0 COMMENT '总调用次数' AFTER `max_concurrent`,
ADD COLUMN `success_calls` int(11) DEFAULT 0 COMMENT '成功调用次数' AFTER `total_calls`,
ADD COLUMN `fail_calls` int(11) DEFAULT 0 COMMENT '失败调用次数' AFTER `success_calls`,
ADD COLUMN `total_cost` decimal(12,4) DEFAULT 0.0000 COMMENT '总消耗成本' AFTER `fail_calls`,
ADD COLUMN `last_call_time` int(11) DEFAULT 0 COMMENT '最后调用时间' AFTER `total_cost`;

-- 添加组合索引（如果不存在）
ALTER TABLE `ddwx_ai_travel_photo_model`
ADD INDEX `idx_bid_type_status` (`bid`, `model_type`, `status`);

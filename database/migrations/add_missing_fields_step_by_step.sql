-- 添加缺失的字段（逐个添加，避免重复字段错误）

-- 1. 添加 current_concurrent 字段
ALTER TABLE `ddwx_ai_travel_photo_model`
ADD COLUMN `current_concurrent` int(11) DEFAULT 0 COMMENT '当前并发数' AFTER `sort`;

-- 2. 添加 max_concurrent 字段
ALTER TABLE `ddwx_ai_travel_photo_model`
ADD COLUMN `max_concurrent` int(11) DEFAULT 5 COMMENT '最大并发数' AFTER `current_concurrent`;

-- 3. 添加 total_calls 字段
ALTER TABLE `ddwx_ai_travel_photo_model`
ADD COLUMN `total_calls` int(11) DEFAULT 0 COMMENT '总调用次数' AFTER `max_concurrent`;

-- 4. 添加 success_calls 字段
ALTER TABLE `ddwx_ai_travel_photo_model`
ADD COLUMN `success_calls` int(11) DEFAULT 0 COMMENT '成功调用次数' AFTER `total_calls`;

-- 5. 添加 fail_calls 字段
ALTER TABLE `ddwx_ai_travel_photo_model`
ADD COLUMN `fail_calls` int(11) DEFAULT 0 COMMENT '失败调用次数' AFTER `success_calls`;

-- 6. 添加 last_call_time 字段
ALTER TABLE `ddwx_ai_travel_photo_model`
ADD COLUMN `last_call_time` int(11) DEFAULT 0 COMMENT '最后调用时间' AFTER `fail_calls`;

-- 7. 修改 total_cost 字段类型（从 decimal(10,2) 改为 decimal(12,4)）
ALTER TABLE `ddwx_ai_travel_photo_model`
MODIFY COLUMN `total_cost` decimal(12,4) DEFAULT 0.0000 COMMENT '总消耗成本';

-- 8. 添加组合索引（如果不存在）
ALTER TABLE `ddwx_ai_travel_photo_model`
ADD INDEX `idx_bid_type_status` (`bid`, `model_type`, `status`);

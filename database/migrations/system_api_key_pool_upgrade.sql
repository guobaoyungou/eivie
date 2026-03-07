-- ============================================================
-- 系统API Key配置功能 - 多Key池重构升级脚本
-- 支持同一供应商添加多个API Key，实现负载均衡
-- ============================================================

-- 1. 添加新字段到system_api_key表
ALTER TABLE `ddwx_system_api_key`
    ADD COLUMN `max_concurrency` int(11) unsigned NOT NULL DEFAULT 5 COMMENT '单Key最大并发数，默认5' AFTER `extra_config`,
    ADD COLUMN `current_concurrency` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '当前并发占用数，默认0' AFTER `max_concurrency`,
    ADD COLUMN `weight` int(11) unsigned NOT NULL DEFAULT 100 COMMENT '负载均衡权重1-100，默认100' AFTER `current_concurrency`,
    ADD COLUMN `total_calls` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '累计调用次数' AFTER `weight`,
    ADD COLUMN `fail_calls` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '累计失败次数' AFTER `total_calls`,
    ADD COLUMN `last_used_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '最后使用时间戳' AFTER `fail_calls`,
    ADD COLUMN `last_error_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '最后出错时间戳' AFTER `last_used_time`,
    ADD COLUMN `last_error_msg` varchar(500) NOT NULL DEFAULT '' COMMENT '最后错误信息' AFTER `last_error_time`;

-- 2. 删除旧的单供应商唯一约束索引
ALTER TABLE `ddwx_system_api_key`
    DROP INDEX `uk_provider_id`;

-- 3. 添加新索引
-- 3.1 防止重复添加相同Key的唯一索引（取api_key前100个字符）
ALTER TABLE `ddwx_system_api_key`
    ADD UNIQUE KEY `uk_api_key` (`api_key`(100)) COMMENT '防止重复添加相同Key';

-- 3.2 按供应商查询启用Key的复合索引
ALTER TABLE `ddwx_system_api_key`
    ADD KEY `idx_provider_active` (`provider_code`, `is_active`) COMMENT '按供应商查询启用Key';

-- 查看表结构确认更新成功
DESCRIBE `ddwx_system_api_key`;

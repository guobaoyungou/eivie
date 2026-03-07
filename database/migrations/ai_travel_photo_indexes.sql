-- AI旅拍系统数据库索引优化
-- 用于提升查询性能的索引创建脚本

USE your_database_name;

-- ==========================================
-- 1. 人像表索引优化
-- ==========================================
ALTER TABLE `ddwx_ai_travel_photo_portrait` 
ADD INDEX `idx_bid_device` (`bid`, `device_id`),
ADD INDEX `idx_md5` (`md5`),
ADD INDEX `idx_cutout_status` (`cutout_status`),
ADD INDEX `idx_add_time` (`add_time`),
ADD INDEX `idx_status_time` (`status`, `add_time`);

-- ==========================================
-- 2. 场景表索引优化
-- ==========================================
ALTER TABLE `ddwx_ai_travel_photo_scene` 
ADD INDEX `idx_bid_status` (`bid`, `status`),
ADD INDEX `idx_category` (`category_name`),
ADD INDEX `idx_is_recommend` (`is_recommend`, `sort`),
ADD INDEX `idx_generation_count` (`generation_count` DESC);

-- ==========================================
-- 3. 生成记录表索引优化
-- ==========================================
ALTER TABLE `ddwx_ai_travel_photo_generation` 
ADD INDEX `idx_portrait` (`portrait_id`, `scene_id`),
ADD INDEX `idx_status_time` (`status`, `add_time`),
ADD INDEX `idx_bid_type` (`bid`, `generation_type`),
ADD INDEX `idx_process_time` (`process_time`);

-- ==========================================
-- 4. 结果表索引优化
-- ==========================================
ALTER TABLE `ddwx_ai_travel_photo_result` 
ADD INDEX `idx_portrait_scene` (`portrait_id`, `scene_id`),
ADD INDEX `idx_generation` (`generation_id`),
ADD INDEX `idx_bid_type` (`bid`, `content_type`),
ADD INDEX `idx_add_time` (`add_time`);

-- ==========================================
-- 5. 二维码表索引优化
-- ==========================================
ALTER TABLE `ddwx_ai_travel_photo_qrcode` 
ADD UNIQUE INDEX `uniq_qrcode` (`qrcode_str`),
ADD INDEX `idx_portrait` (`portrait_id`),
ADD INDEX `idx_expire` (`is_expired`, `expire_time`),
ADD INDEX `idx_scan_count` (`scan_count` DESC);

-- ==========================================
-- 6. 订单表索引优化
-- ==========================================
ALTER TABLE `ddwx_ai_travel_photo_order` 
ADD UNIQUE INDEX `uniq_order_no` (`order_no`),
ADD INDEX `idx_uid_status` (`uid`, `status`),
ADD INDEX `idx_bid_time` (`bid`, `add_time`),
ADD INDEX `idx_status_time` (`status`, `add_time`),
ADD INDEX `idx_pay_time` (`pay_time`);

-- ==========================================
-- 7. 订单商品表索引优化
-- ==========================================
ALTER TABLE `ddwx_ai_travel_photo_order_goods` 
ADD INDEX `idx_order` (`order_id`),
ADD INDEX `idx_goods` (`goods_type`, `goods_id`),
ADD INDEX `idx_result` (`result_id`);

-- ==========================================
-- 8. 套餐表索引优化
-- ==========================================
ALTER TABLE `ddwx_ai_travel_photo_package` 
ADD INDEX `idx_bid_status` (`bid`, `status`),
ADD INDEX `idx_is_recommend` (`is_recommend`, `sort`),
ADD INDEX `idx_sales` (`sales_count` DESC),
ADD INDEX `idx_stock` (`stock`);

-- ==========================================
-- 9. 设备表索引优化
-- ==========================================
ALTER TABLE `ddwx_ai_travel_photo_device` 
ADD UNIQUE INDEX `uniq_device_code` (`device_code`),
ADD INDEX `idx_bid_status` (`bid`, `status`),
ADD INDEX `idx_last_heartbeat` (`last_heartbeat_time`),
ADD INDEX `idx_upload_count` (`upload_count` DESC);

-- ==========================================
-- 10. 用户相册表索引优化
-- ==========================================
ALTER TABLE `ddwx_ai_travel_photo_user_album` 
ADD INDEX `idx_uid_status` (`uid`, `status`),
ADD INDEX `idx_result` (`result_id`),
ADD INDEX `idx_content_type` (`content_type`),
ADD INDEX `idx_is_favorite` (`is_favorite`, `add_time`),
ADD INDEX `idx_add_time` (`add_time` DESC);

-- ==========================================
-- 11. 统计表索引优化
-- ==========================================
ALTER TABLE `ddwx_ai_travel_photo_statistics` 
ADD INDEX `idx_bid_date` (`bid`, `date`),
ADD INDEX `idx_date` (`date` DESC);

-- ==========================================
-- 12. AI模型配置表索引优化
-- ==========================================
ALTER TABLE `ddwx_ai_travel_photo_model` 
ADD INDEX `idx_model_type` (`model_type`, `is_active`);

-- ==========================================
-- 索引创建完成
-- ==========================================
-- 执行以下语句验证索引创建情况：
-- SHOW INDEX FROM ddwx_ai_travel_photo_portrait;
-- SHOW INDEX FROM ddwx_ai_travel_photo_scene;
-- 等等...

-- ==========================================
-- 性能优化建议
-- ==========================================
-- 1. 定期执行 ANALYZE TABLE 更新表统计信息
-- 2. 定期执行 OPTIMIZE TABLE 整理表碎片
-- 3. 监控慢查询日志，针对性优化
-- 4. 合理使用覆盖索引减少回表查询
-- 5. 避免在大表上使用 SELECT *
-- 6. 合理设置 innodb_buffer_pool_size

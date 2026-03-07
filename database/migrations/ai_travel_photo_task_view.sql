-- AI旅拍任务列表功能 - 数据库视图和索引
-- 创建时间: 2026-02-03
-- 描述: 创建任务统计视图，优化任务列表查询性能

-- ========================================
-- 1. 创建任务统计视图
-- ========================================

-- 删除已存在的视图（新旧名称都删除）
DROP VIEW IF EXISTS `view_ai_travel_task_summary`;
DROP VIEW IF EXISTS `ddwx_view_ai_travel_task_summary`;

-- 创建任务统计视图（不带ddwx_前缀，因为ThinkPHP会自动添加）
-- 创建任务统计视图（不带ddwx_前缀，ThinkPHP会自动添加）
CREATE VIEW `view_ai_travel_task_summary` AS
SELECT 
    p.id AS portrait_id,
    p.aid,
    p.bid,
    p.mdid,
    p.uid,
    p.md5 AS portrait_md5,
    p.file_name,
    p.original_url,
    p.thumbnail_url,
    p.cutout_url,
    p.file_size,
    p.width,
    p.height,
    p.device_id,
    d.device_name,
    p.create_time,
    p.update_time,
    -- 抠图状态（从generation表获取generation_type=0的记录）
    COALESCE(
        (SELECT status FROM ddwx_ai_travel_photo_generation 
         WHERE portrait_id = p.id AND generation_type = 0 
         ORDER BY id DESC LIMIT 1), 
        0
    ) AS cutout_status,
    -- 任务统计（排除抠图任务generation_type=0）
    COUNT(DISTINCT CASE WHEN g.generation_type > 0 THEN g.id END) AS total_tasks,
    SUM(CASE WHEN g.status = 2 AND g.generation_type > 0 THEN 1 ELSE 0 END) AS success_tasks,
    SUM(CASE WHEN g.status = 3 AND g.generation_type > 0 THEN 1 ELSE 0 END) AS failed_tasks,
    SUM(CASE WHEN g.status = 1 AND g.generation_type > 0 THEN 1 ELSE 0 END) AS processing_tasks,
    SUM(CASE WHEN g.status = 0 AND g.generation_type > 0 THEN 1 ELSE 0 END) AS pending_tasks,
    SUM(CASE WHEN g.status = 4 AND g.generation_type > 0 THEN 1 ELSE 0 END) AS cancelled_tasks,
    -- 结果统计
    COUNT(DISTINCT CASE WHEN r.type != 19 THEN r.id END) AS image_result_count,
    COUNT(DISTINCT CASE WHEN r.type = 19 THEN r.id END) AS video_result_count,
    -- 时间统计
    MAX(g.update_time) AS latest_update_time,
    SUM(CASE WHEN g.status = 2 AND g.generation_type > 0 THEN g.cost_time ELSE 0 END) AS total_cost_time,
    -- 任务状态摘要（仅统计非抠图任务）
    CASE 
        WHEN SUM(CASE WHEN g.status IN (0,1) AND g.generation_type > 0 THEN 1 ELSE 0 END) > 0 THEN 'processing'
        WHEN SUM(CASE WHEN g.status = 3 AND g.generation_type > 0 THEN 1 ELSE 0 END) > 0 
             AND SUM(CASE WHEN g.status = 2 AND g.generation_type > 0 THEN 1 ELSE 0 END) = 0 THEN 'all_failed'
        WHEN SUM(CASE WHEN g.status = 3 AND g.generation_type > 0 THEN 1 ELSE 0 END) > 0 
             AND SUM(CASE WHEN g.status = 2 AND g.generation_type > 0 THEN 1 ELSE 0 END) > 0 THEN 'partial_failed'
        WHEN SUM(CASE WHEN g.status = 2 AND g.generation_type > 0 THEN 1 ELSE 0 END) > 0 THEN 'completed'
        ELSE 'pending'
    END AS task_status_summary
FROM ddwx_ai_travel_photo_portrait p
LEFT JOIN ddwx_ai_travel_photo_generation g ON p.id = g.portrait_id
LEFT JOIN ddwx_ai_travel_photo_result r ON g.id = r.generation_id AND r.status = 1
LEFT JOIN ddwx_ai_travel_photo_device d ON p.device_id = d.id
GROUP BY p.id;

-- ========================================
-- 2. 优化查询索引
-- ========================================

-- 检查并创建索引 (避免重复创建)

-- generation表索引
SET @exist_idx := (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ddwx_ai_travel_photo_generation' 
    AND index_name = 'idx_portrait_id_status');
SET @sql_text := IF(@exist_idx > 0, 
    'SELECT "Index idx_portrait_id_status already exists"',
    'ALTER TABLE ddwx_ai_travel_photo_generation ADD INDEX idx_portrait_id_status (portrait_id, status)');
PREPARE stmt FROM @sql_text;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_idx := (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ddwx_ai_travel_photo_generation' 
    AND index_name = 'idx_bid_create_time');
SET @sql_text := IF(@exist_idx > 0, 
    'SELECT "Index idx_bid_create_time already exists"',
    'ALTER TABLE ddwx_ai_travel_photo_generation ADD INDEX idx_bid_create_time (bid, create_time)');
PREPARE stmt FROM @sql_text;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_idx := (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ddwx_ai_travel_photo_generation' 
    AND index_name = 'idx_status_update_time');
SET @sql_text := IF(@exist_idx > 0, 
    'SELECT "Index idx_status_update_time already exists"',
    'ALTER TABLE ddwx_ai_travel_photo_generation ADD INDEX idx_status_update_time (status, update_time)');
PREPARE stmt FROM @sql_text;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- portrait表索引
SET @exist_idx := (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ddwx_ai_travel_photo_portrait' 
    AND index_name = 'idx_bid_create_time');
SET @sql_text := IF(@exist_idx > 0, 
    'SELECT "Index idx_bid_create_time already exists"',
    'ALTER TABLE ddwx_ai_travel_photo_portrait ADD INDEX idx_bid_create_time (bid, create_time)');
PREPARE stmt FROM @sql_text;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_idx := (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ddwx_ai_travel_photo_portrait' 
    AND index_name = 'idx_md5');
SET @sql_text := IF(@exist_idx > 0, 
    'SELECT "Index idx_md5 already exists"',
    'ALTER TABLE ddwx_ai_travel_photo_portrait ADD INDEX idx_md5 (md5)');
PREPARE stmt FROM @sql_text;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_idx := (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ddwx_ai_travel_photo_portrait' 
    AND index_name = 'idx_device_id');
SET @sql_text := IF(@exist_idx > 0, 
    'SELECT "Index idx_device_id already exists"',
    'ALTER TABLE ddwx_ai_travel_photo_portrait ADD INDEX idx_device_id (device_id)');
PREPARE stmt FROM @sql_text;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_idx := (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ddwx_ai_travel_photo_portrait' 
    AND index_name = 'idx_mdid');
SET @sql_text := IF(@exist_idx > 0, 
    'SELECT "Index idx_mdid already exists"',
    'ALTER TABLE ddwx_ai_travel_photo_portrait ADD INDEX idx_mdid (mdid)');
PREPARE stmt FROM @sql_text;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- result表索引
SET @exist_idx := (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ddwx_ai_travel_photo_result' 
    AND index_name = 'idx_generation_id_status');
SET @sql_text := IF(@exist_idx > 0, 
    'SELECT "Index idx_generation_id_status already exists"',
    'ALTER TABLE ddwx_ai_travel_photo_result ADD INDEX idx_generation_id_status (generation_id, status)');
PREPARE stmt FROM @sql_text;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ========================================
-- 完成
-- ========================================
SELECT 'AI旅拍任务列表视图和索引创建完成' AS message;

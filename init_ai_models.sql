-- AI旅拍系统 - AI模型配置初始化脚本
-- 执行时间：2026-01-22
-- 说明：此脚本会为所有启用了AI旅拍功能的商家初始化AI模型配置

-- ============================================
-- 1. 通义万相模型配置（图生图）
-- ============================================
INSERT INTO ddwx_ai_travel_photo_model 
(aid, bid, model_type, model_name, category_id, api_key, api_secret, api_base_url, api_version, 
timeout, max_retry, cost_per_image, cost_per_video, status, is_default, sort, 
create_time, update_time)
SELECT 
    aid,
    id as bid,
    'tongyi_wanxiang' as model_type,
    '通义万相-图生图' as model_name,
    1 as category_id,
    '' as api_key,
    '' as api_secret,
    'https://dashscope.aliyuncs.com' as api_base_url,
    'v1' as api_version,
    180 as timeout,
    3 as max_retry,
    0.0500 as cost_per_image,
    0.0000 as cost_per_video,
    1 as status,
    1 as is_default,
    100 as sort,
    UNIX_TIMESTAMP() as create_time,
    UNIX_TIMESTAMP() as update_time
FROM ddwx_business 
WHERE ai_travel_photo_enabled = 1
AND NOT EXISTS (
    SELECT 1 FROM ddwx_ai_travel_photo_model 
    WHERE ddwx_ai_travel_photo_model.bid = ddwx_business.id 
    AND model_type = 'tongyi_wanxiang'
);

-- ============================================
-- 2. 可灵AI模型配置（图生视频）
-- ============================================
INSERT INTO ddwx_ai_travel_photo_model 
(aid, bid, model_type, model_name, category_id, api_key, api_secret, api_base_url, api_version, 
timeout, max_retry, cost_per_image, cost_per_video, status, is_default, sort, 
create_time, update_time)
SELECT 
    aid,
    id as bid,
    'kling_ai' as model_type,
    '可灵AI-图生视频' as model_name,
    2 as category_id,
    '' as api_key,
    '' as api_secret,
    'https://api.klingai.com' as api_base_url,
    'v1-5' as api_version,
    600 as timeout,
    3 as max_retry,
    0.0000 as cost_per_image,
    0.5000 as cost_per_video,
    1 as status,
    1 as is_default,
    90 as sort,
    UNIX_TIMESTAMP() as create_time,
    UNIX_TIMESTAMP() as update_time
FROM ddwx_business 
WHERE ai_travel_photo_enabled = 1
AND NOT EXISTS (
    SELECT 1 FROM ddwx_ai_travel_photo_model 
    WHERE ddwx_ai_travel_photo_model.bid = ddwx_business.id 
    AND model_type = 'kling_ai'
);

-- ============================================
-- 3. 通义万相抠图模型配置
-- ============================================
INSERT INTO ddwx_ai_travel_photo_model 
(aid, bid, model_type, model_name, category_id, api_key, api_secret, api_base_url, api_version, 
timeout, max_retry, cost_per_image, cost_per_video, status, is_default, sort, 
create_time, update_time)
SELECT 
    aid,
    id as bid,
    'tongyi_cutout' as model_type,
    '通义万相-智能抠图' as model_name,
    3 as category_id,
    '' as api_key,
    '' as api_secret,
    'https://dashscope.aliyuncs.com' as api_base_url,
    'v1' as api_version,
    120 as timeout,
    3 as max_retry,
    0.0200 as cost_per_image,
    0.0000 as cost_per_video,
    1 as status,
    1 as is_default,
    110 as sort,
    UNIX_TIMESTAMP() as create_time,
    UNIX_TIMESTAMP() as update_time
FROM ddwx_business 
WHERE ai_travel_photo_enabled = 1
AND NOT EXISTS (
    SELECT 1 FROM ddwx_ai_travel_photo_model 
    WHERE ddwx_ai_travel_photo_model.bid = ddwx_business.id 
    AND model_type = 'tongyi_cutout'
);

-- ============================================
-- 查看初始化结果
-- ============================================
SELECT 
    '初始化结果' as 检查项,
    COUNT(*) as 总配置数,
    SUM(CASE WHEN model_type = 'tongyi_wanxiang' THEN 1 ELSE 0 END) as 图生图配置,
    SUM(CASE WHEN model_type = 'kling_ai' THEN 1 ELSE 0 END) as 图生视频配置,
    SUM(CASE WHEN model_type = 'tongyi_cutout' THEN 1 ELSE 0 END) as 抠图配置
FROM ddwx_ai_travel_photo_model;

-- 按商家查看配置
SELECT 
    b.id as 商家ID,
    b.name as 商家名称,
    COUNT(m.id) as 模型配置数
FROM ddwx_business b
LEFT JOIN ddwx_ai_travel_photo_model m ON b.id = m.bid
WHERE b.ai_travel_photo_enabled = 1
GROUP BY b.id, b.name
ORDER BY b.id
LIMIT 10;

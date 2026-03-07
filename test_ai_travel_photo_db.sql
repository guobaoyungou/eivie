-- AI旅拍系统数据库完整性检查脚本
-- 执行时间：2026-01-22

-- ============================================
-- 1. 检查表是否完整（应有12张表）
-- ============================================
SELECT 
    '表结构检查' as 检查项,
    COUNT(*) as 实际表数量,
    12 as 预期表数量,
    CASE WHEN COUNT(*) = 12 THEN '✓ 通过' ELSE '✗ 失败' END as 结果
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME LIKE 'ddwx_ai_travel_photo%';

-- 列出所有AI旅拍相关表
SELECT TABLE_NAME as 表名, TABLE_COMMENT as 备注
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME LIKE 'ddwx_ai_travel_photo%'
ORDER BY TABLE_NAME;

-- ============================================
-- 2. 检查商家表扩展字段
-- ============================================
SELECT 
    '商家表字段检查' as 检查项,
    COUNT(*) as AI相关字段数
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'ddwx_business'
AND COLUMN_NAME LIKE 'ai_%';

-- 列出商家表AI相关字段
SELECT 
    COLUMN_NAME as 字段名,
    COLUMN_TYPE as 字段类型,
    COLUMN_DEFAULT as 默认值,
    COLUMN_COMMENT as 备注
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'ddwx_business'
AND COLUMN_NAME LIKE 'ai_%';

-- ============================================
-- 3. 检查启用的商家
-- ============================================
SELECT 
    '启用商家检查' as 检查项,
    COUNT(*) as 启用商家数
FROM ddwx_business 
WHERE ai_travel_photo_enabled = 1;

-- 列出启用AI旅拍的商家
SELECT id, name, ai_travel_photo_enabled, aid
FROM ddwx_business 
WHERE ai_travel_photo_enabled = 1
LIMIT 10;

-- ============================================
-- 4. 检查AI模型配置
-- ============================================
SELECT 
    'AI模型配置检查' as 检查项,
    COUNT(*) as 配置数量,
    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as 启用数量
FROM ddwx_ai_travel_photo_model;

-- 列出AI模型配置
SELECT id, model_name, model_type, api_key, status
FROM ddwx_ai_travel_photo_model
LIMIT 10;

-- ============================================
-- 5. 检查场景数据
-- ============================================
SELECT 
    '场景数据检查' as 检查项,
    COUNT(*) as 场景总数,
    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as 启用场景数,
    COUNT(DISTINCT aid) as 平台数,
    COUNT(DISTINCT bid) as 商家数
FROM ddwx_ai_travel_photo_scene;

-- 按商家统计场景
SELECT aid, bid, COUNT(*) as 场景数, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as 启用数
FROM ddwx_ai_travel_photo_scene
GROUP BY aid, bid
LIMIT 10;

-- ============================================
-- 6. 检查套餐数据
-- ============================================
SELECT 
    '套餐数据检查' as 检查项,
    COUNT(*) as 套餐总数,
    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as 启用套餐数
FROM ddwx_ai_travel_photo_package;

-- 列出套餐配置
SELECT id, aid, bid, name, price, num as photo_count, video_num, status
FROM ddwx_ai_travel_photo_package
LIMIT 10;

-- ============================================
-- 7. 检查设备令牌
-- ============================================
SELECT 
    '设备令牌检查' as 检查项,
    COUNT(*) as 设备总数,
    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as 启用设备数
FROM ddwx_ai_travel_photo_device;

-- 列出设备
SELECT id, aid, bid, device_name, token, status, last_active_time
FROM ddwx_ai_travel_photo_device
LIMIT 10;

-- ============================================
-- 8. 检查人像数据
-- ============================================
SELECT 
    '人像数据检查' as 检查项,
    COUNT(*) as 人像总数,
    SUM(CASE WHEN cutout_status = 2 THEN 1 ELSE 0 END) as 抠图成功数,
    SUM(CASE WHEN cutout_status = 3 THEN 1 ELSE 0 END) as 抠图失败数
FROM ddwx_ai_travel_photo_portrait;

-- ============================================
-- 9. 检查生成任务数据
-- ============================================
SELECT 
    '生成任务检查' as 检查项,
    COUNT(*) as 任务总数,
    SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as 成功数,
    SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as 失败数,
    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as 处理中数
FROM ddwx_ai_travel_photo_generation;

-- ============================================
-- 10. 检查结果数据
-- ============================================
SELECT 
    '生成结果检查' as 检查项,
    COUNT(*) as 结果总数,
    SUM(CASE WHEN type = 1 THEN 1 ELSE 0 END) as 图片数,
    SUM(CASE WHEN type = 2 THEN 1 ELSE 0 END) as 视频数
FROM ddwx_ai_travel_photo_result;

-- ============================================
-- 11. 检查订单数据
-- ============================================
SELECT 
    '订单数据检查' as 检查项,
    COUNT(*) as 订单总数,
    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as 待支付数,
    SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as 已支付数,
    SUM(total_amount) as 总金额
FROM ddwx_ai_travel_photo_order;

-- ============================================
-- 12. 检查二维码数据
-- ============================================
SELECT 
    '二维码数据检查' as 检查项,
    COUNT(*) as 二维码总数,
    SUM(CASE WHEN expire_time > UNIX_TIMESTAMP() THEN 1 ELSE 0 END) as 有效数,
    SUM(CASE WHEN expire_time <= UNIX_TIMESTAMP() THEN 1 ELSE 0 END) as 过期数
FROM ddwx_ai_travel_photo_qrcode;

-- ============================================
-- 13. 数据一致性检查
-- ============================================
-- 检查孤立的生成记录（人像已删除但记录还在）
SELECT 
    '孤立生成记录检查' as 检查项,
    COUNT(*) as 孤立记录数
FROM ddwx_ai_travel_photo_generation g
LEFT JOIN ddwx_ai_travel_photo_portrait p ON g.portrait_id = p.id
WHERE p.id IS NULL;

-- 检查孤立的结果记录
SELECT 
    '孤立结果记录检查' as 检查项,
    COUNT(*) as 孤立记录数
FROM ddwx_ai_travel_photo_result r
LEFT JOIN ddwx_ai_travel_photo_generation g ON r.generation_id = g.id
WHERE g.id IS NULL;

-- 检查订单与商品的一致性
SELECT 
    '订单商品一致性检查' as 检查项,
    COUNT(*) as 不一致订单数
FROM ddwx_ai_travel_photo_order o
LEFT JOIN ddwx_ai_travel_photo_order_goods og ON o.id = og.order_id
WHERE o.status > 0 AND og.id IS NULL;

-- ============================================
-- 14. 索引检查
-- ============================================
SELECT 
    '索引检查' as 检查项,
    TABLE_NAME as 表名,
    INDEX_NAME as 索引名,
    COLUMN_NAME as 列名,
    NON_UNIQUE as 非唯一
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME LIKE 'ddwx_ai_travel_photo%'
ORDER BY TABLE_NAME, INDEX_NAME;

-- ============================================
-- 15. 字符集和引擎检查
-- ============================================
SELECT 
    TABLE_NAME as 表名,
    ENGINE as 引擎,
    TABLE_COLLATION as 字符集,
    CASE WHEN ENGINE = 'InnoDB' AND TABLE_COLLATION LIKE 'utf8mb4%' THEN '✓ 正常' ELSE '✗ 异常' END as 状态
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME LIKE 'ddwx_ai_travel_photo%'
ORDER BY TABLE_NAME;

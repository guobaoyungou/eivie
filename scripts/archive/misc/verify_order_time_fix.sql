-- ======================================
-- 订单创建时间修复验证脚本
-- 用于检查订单创建时间与记录创建时间的一致性
-- ======================================

-- 1. 查看订单创建时间 vs 记录创建时间的差异
SELECT 
    o.id AS order_id,
    o.ordernum,
    CASE o.generation_type 
        WHEN 1 THEN '照片生成' 
        WHEN 2 THEN '视频生成' 
    END AS type_name,
    FROM_UNIXTIME(o.createtime) AS order_create_time,
    FROM_UNIXTIME(r.create_time) AS record_create_time,
    CASE 
        WHEN r.create_time IS NULL THEN '无记录'
        ELSE CONCAT(TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(o.createtime), FROM_UNIXTIME(r.create_time)), '秒')
    END AS time_diff,
    CASE o.pay_status 
        WHEN 0 THEN '待支付' 
        WHEN 1 THEN '已支付' 
        WHEN 2 THEN '已取消' 
    END AS pay_status,
    CASE o.task_status 
        WHEN 0 THEN '待处理' 
        WHEN 1 THEN '处理中' 
        WHEN 2 THEN '成功' 
        WHEN 3 THEN '失败' 
        WHEN 4 THEN '已取消' 
    END AS task_status
FROM ddwx_generation_order o
LEFT JOIN ddwx_generation_record r ON o.record_id = r.id
WHERE o.status = 1
ORDER BY o.id DESC
LIMIT 20;

-- 2. 统计不同状态订单的时间差异
SELECT 
    CASE o.generation_type 
        WHEN 1 THEN '照片生成' 
        WHEN 2 THEN '视频生成' 
    END AS type_name,
    CASE o.pay_status 
        WHEN 0 THEN '待支付' 
        WHEN 1 THEN '已支付' 
        WHEN 2 THEN '已取消' 
    END AS pay_status,
    COUNT(*) AS order_count,
    COUNT(r.id) AS has_record_count,
    COUNT(*) - COUNT(r.id) AS no_record_count,
    ROUND(AVG(CASE WHEN r.create_time IS NOT NULL THEN TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(o.createtime), FROM_UNIXTIME(r.create_time)) END), 2) AS avg_time_diff_seconds
FROM ddwx_generation_order o
LEFT JOIN ddwx_generation_record r ON o.record_id = r.id
WHERE o.status = 1
GROUP BY o.generation_type, o.pay_status
ORDER BY o.generation_type, o.pay_status;

-- 3. 查看最近的照片生成订单（验证修复效果）
SELECT 
    o.id,
    o.ordernum,
    m.nickname,
    COALESCE(FROM_UNIXTIME(r.create_time), FROM_UNIXTIME(o.createtime)) AS display_time,
    FROM_UNIXTIME(o.createtime) AS original_order_time,
    FROM_UNIXTIME(r.create_time) AS record_time,
    CASE o.pay_status 
        WHEN 0 THEN '待支付' 
        WHEN 1 THEN '已支付' 
        WHEN 2 THEN '已取消' 
    END AS pay_status
FROM ddwx_generation_order o
LEFT JOIN ddwx_member m ON o.mid = m.id
LEFT JOIN ddwx_generation_record r ON o.record_id = r.id
WHERE o.status = 1 AND o.generation_type = 1
ORDER BY o.id DESC
LIMIT 10;

-- 4. 查看最近的视频生成订单（验证修复效果）
SELECT 
    o.id,
    o.ordernum,
    m.nickname,
    COALESCE(FROM_UNIXTIME(r.create_time), FROM_UNIXTIME(o.createtime)) AS display_time,
    FROM_UNIXTIME(o.createtime) AS original_order_time,
    FROM_UNIXTIME(r.create_time) AS record_time,
    CASE o.pay_status 
        WHEN 0 THEN '待支付' 
        WHEN 1 THEN '已支付' 
        WHEN 2 THEN '已取消' 
    END AS pay_status
FROM ddwx_generation_order o
LEFT JOIN ddwx_member m ON o.mid = m.id
LEFT JOIN ddwx_generation_record r ON o.record_id = r.id
WHERE o.status = 1 AND o.generation_type = 2
ORDER BY o.id DESC
LIMIT 10;

-- 5. 检查异常情况：有record_id但查不到记录的订单
SELECT 
    o.id,
    o.ordernum,
    o.record_id,
    FROM_UNIXTIME(o.createtime) AS order_time,
    CASE o.generation_type 
        WHEN 1 THEN '照片生成' 
        WHEN 2 THEN '视频生成' 
    END AS type_name
FROM ddwx_generation_order o
WHERE o.status = 1 
  AND o.record_id > 0 
  AND NOT EXISTS (
      SELECT 1 FROM ddwx_generation_record r WHERE r.id = o.record_id
  )
LIMIT 20;

-- 6. 检查：有生成记录但order_id不匹配的情况
SELECT 
    r.id AS record_id,
    r.order_id,
    o.id AS order_id_from_join,
    FROM_UNIXTIME(r.create_time) AS record_time,
    CASE r.generation_type 
        WHEN 1 THEN '照片生成' 
        WHEN 2 THEN '视频生成' 
    END AS type_name
FROM ddwx_generation_record r
LEFT JOIN ddwx_generation_order o ON r.order_id = o.id
WHERE r.order_id > 0 
  AND (o.id IS NULL OR o.record_id != r.id)
LIMIT 20;

-- ======================================
-- 使用说明
-- ======================================
-- 1. 运行查询1：查看最近20个订单的时间差异
-- 2. 运行查询2：统计不同状态订单的时间差异情况
-- 3. 运行查询3和4：验证照片和视频订单显示的时间（应该优先显示记录时间）
-- 4. 运行查询5和6：检查数据完整性（正常情况下应该没有结果）
-- ======================================

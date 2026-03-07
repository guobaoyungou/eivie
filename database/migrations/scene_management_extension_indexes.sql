-- ============================================
-- 场景管理功能扩展 - 数据库索引优化
-- 创建时间：2026-02-03
-- 功能说明：为场景管理新增的门店筛选和公共/私有属性添加索引
-- ============================================

-- 检查并添加 mdid 索引（如不存在）
-- 用途：支持按门店筛选场景
SET @exist_mdid_idx := (
    SELECT COUNT(*) 
    FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ddwx_ai_travel_photo_scene' 
    AND index_name = 'idx_mdid'
);

SET @sql_mdid = IF(
    @exist_mdid_idx = 0,
    'ALTER TABLE `ddwx_ai_travel_photo_scene` ADD INDEX `idx_mdid` (`mdid`)',
    'SELECT "索引 idx_mdid 已存在，跳过创建" as message'
);

PREPARE stmt_mdid FROM @sql_mdid;
EXECUTE stmt_mdid;
DEALLOCATE PREPARE stmt_mdid;

-- 检查并添加 is_public 索引（如不存在）
-- 用途：支持按公共/私有属性筛选场景
SET @exist_public_idx := (
    SELECT COUNT(*) 
    FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ddwx_ai_travel_photo_scene' 
    AND index_name = 'idx_is_public'
);

-- idx_is_public 在建表时已创建，这里做兼容性检查
SET @sql_public = IF(
    @exist_public_idx = 0,
    'ALTER TABLE `ddwx_ai_travel_photo_scene` ADD INDEX `idx_is_public` (`is_public`)',
    'SELECT "索引 idx_is_public 已存在，跳过创建" as message'
);

PREPARE stmt_public FROM @sql_public;
EXECUTE stmt_public;
DEALLOCATE PREPARE stmt_public;

-- 添加组合索引：bid + mdid（用于后台管理筛选）
-- 用途：商家查看某个门店的场景列表时性能优化
SET @exist_bid_mdid_idx := (
    SELECT COUNT(*) 
    FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ddwx_ai_travel_photo_scene' 
    AND index_name = 'idx_bid_mdid'
);

SET @sql_bid_mdid = IF(
    @exist_bid_mdid_idx = 0,
    'ALTER TABLE `ddwx_ai_travel_photo_scene` ADD INDEX `idx_bid_mdid` (`bid`, `mdid`)',
    'SELECT "索引 idx_bid_mdid 已存在，跳过创建" as message'
);

PREPARE stmt_bid_mdid FROM @sql_bid_mdid;
EXECUTE stmt_bid_mdid;
DEALLOCATE PREPARE stmt_bid_mdid;

-- 添加组合索引：is_public + status（用于C端查询）
-- 用途：C端用户查询公共启用场景时性能优化
SET @exist_public_status_idx := (
    SELECT COUNT(*) 
    FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ddwx_ai_travel_photo_scene' 
    AND index_name = 'idx_public_status'
);

SET @sql_public_status = IF(
    @exist_public_status_idx = 0,
    'ALTER TABLE `ddwx_ai_travel_photo_scene` ADD INDEX `idx_public_status` (`is_public`, `status`)',
    'SELECT "索引 idx_public_status 已存在，跳过创建" as message'
);

PREPARE stmt_public_status FROM @sql_public_status;
EXECUTE stmt_public_status;
DEALLOCATE PREPARE stmt_public_status;

-- 添加组合索引：is_public + status + mdid（用于C端门店场景查询）
-- 用途：C端用户查询指定门店的公共场景时性能优化
SET @exist_public_status_mdid_idx := (
    SELECT COUNT(*) 
    FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'ddwx_ai_travel_photo_scene' 
    AND index_name = 'idx_public_status_mdid'
);

SET @sql_public_status_mdid = IF(
    @exist_public_status_mdid_idx = 0,
    'ALTER TABLE `ddwx_ai_travel_photo_scene` ADD INDEX `idx_public_status_mdid` (`is_public`, `status`, `mdid`)',
    'SELECT "索引 idx_public_status_mdid 已存在，跳过创建" as message'
);

PREPARE stmt_public_status_mdid FROM @sql_public_status_mdid;
EXECUTE stmt_public_status_mdid;
DEALLOCATE PREPARE stmt_public_status_mdid;

-- ============================================
-- 索引说明
-- ============================================
-- 1. idx_mdid: 单列索引，用于按门店ID筛选
-- 2. idx_is_public: 单列索引，用于按公共/私有属性筛选（建表时已存在）
-- 3. idx_bid_mdid: 组合索引，用于商家后台按门店筛选
-- 4. idx_public_status: 组合索引，用于C端查询公共且启用的场景
-- 5. idx_public_status_mdid: 组合索引，用于C端查询指定门店的公共场景

-- ============================================
-- 典型查询示例
-- ============================================

-- 查询1：后台管理 - 查询商家某个门店的场景
-- SELECT * FROM ai_travel_photo_scene 
-- WHERE bid = 1 AND mdid = 5 
-- ORDER BY sort DESC, id DESC;
-- 使用索引：idx_bid_mdid

-- 查询2：C端 - 查询所有公共启用场景
-- SELECT * FROM ai_travel_photo_scene 
-- WHERE is_public = 1 AND status = 1 
-- ORDER BY sort DESC, id DESC;
-- 使用索引：idx_public_status

-- 查询3：C端 - 查询指定门店的公共场景（包含通用场景）
-- SELECT * FROM ai_travel_photo_scene 
-- WHERE is_public = 1 AND status = 1 AND (mdid = 0 OR mdid = 5)
-- ORDER BY sort DESC, id DESC;
-- 使用索引：idx_public_status_mdid

-- 查询4：后台管理 - 查询所有门店=0的场景
-- SELECT * FROM ai_travel_photo_scene 
-- WHERE bid = 1 AND mdid = 0;
-- 使用索引：idx_bid_mdid

-- ============================================
-- 执行结果检查
-- ============================================
SELECT 
    '场景表索引创建完成' as status,
    COUNT(*) as total_indexes
FROM information_schema.statistics 
WHERE table_schema = DATABASE() 
AND table_name = 'ddwx_ai_travel_photo_scene'
AND index_name IN ('idx_mdid', 'idx_is_public', 'idx_bid_mdid', 'idx_public_status', 'idx_public_status_mdid');

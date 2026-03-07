-- ================================================================
-- 更新场景配置表以支持新的场景类型系统
-- 创建时间：2026-02-06
-- 说明：确保场景表包含scene_type字段并添加相关索引
-- ================================================================

USE ddwx;

-- 检查并添加scene_type字段（如果不存在）
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = 'ddwx' 
    AND TABLE_NAME = 'ddwx_ai_travel_photo_scene' 
    AND COLUMN_NAME = 'scene_type');

SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `ddwx_ai_travel_photo_scene` ADD COLUMN `scene_type` int(11) NOT NULL DEFAULT ''1'' COMMENT ''场景类型（1-6）'' AFTER `category`',
  'SELECT ''scene_type字段已存在'' AS result');
  
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加scene_type索引（如果不存在）
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
  WHERE TABLE_SCHEMA = 'ddwx' 
    AND TABLE_NAME = 'ddwx_ai_travel_photo_scene' 
    AND INDEX_NAME = 'idx_scene_type');

SET @sql = IF(@idx_exists = 0,
  'ALTER TABLE `ddwx_ai_travel_photo_scene` ADD INDEX `idx_scene_type` (`scene_type`)',
  'SELECT ''idx_scene_type索引已存在'' AS result');
  
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加组合索引（model_id + scene_type）
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
  WHERE TABLE_SCHEMA = 'ddwx' 
    AND TABLE_NAME = 'ddwx_ai_travel_photo_scene' 
    AND INDEX_NAME = 'idx_model_scene_type');

SET @sql = IF(@idx_exists = 0,
  'ALTER TABLE `ddwx_ai_travel_photo_scene` ADD INDEX `idx_model_scene_type` (`model_id`, `scene_type`)',
  'SELECT ''idx_model_scene_type索引已存在'' AS result');
  
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加reference_image字段（参考图）
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = 'ddwx' 
    AND TABLE_NAME = 'ddwx_ai_travel_photo_scene' 
    AND COLUMN_NAME = 'reference_image');

SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `ddwx_ai_travel_photo_scene` ADD COLUMN `reference_image` varchar(500) DEFAULT NULL COMMENT ''参考图URL（场景3-6使用）'' AFTER `model_params`',
  'SELECT ''reference_image字段已存在'' AS result');
  
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加thumbnail字段（缩略图）
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = 'ddwx' 
    AND TABLE_NAME = 'ddwx_ai_travel_photo_scene' 
    AND COLUMN_NAME = 'thumbnail');

SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `ddwx_ai_travel_photo_scene` ADD COLUMN `thumbnail` varchar(500) DEFAULT NULL COMMENT ''缩略图URL'' AFTER `reference_image`',
  'SELECT ''thumbnail字段已存在'' AS result');
  
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 验证安装结果
SELECT '=== 场景表更新完成 ===' AS status;

SELECT CONCAT('scene_type字段: ', IF(COUNT(*) > 0, '✓ 已存在', '✗ 不存在')) AS result
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'ddwx' 
  AND TABLE_NAME = 'ddwx_ai_travel_photo_scene' 
  AND COLUMN_NAME = 'scene_type';

SELECT CONCAT('reference_image字段: ', IF(COUNT(*) > 0, '✓ 已存在', '✗ 不存在')) AS result
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'ddwx' 
  AND TABLE_NAME = 'ddwx_ai_travel_photo_scene' 
  AND COLUMN_NAME = 'reference_image';

SELECT CONCAT('thumbnail字段: ', IF(COUNT(*) > 0, '✓ 已存在', '✗ 不存在')) AS result
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'ddwx' 
  AND TABLE_NAME = 'ddwx_ai_travel_photo_scene' 
  AND COLUMN_NAME = 'thumbnail';

SELECT '字段更新完成，现在可以配置新的场景类型' AS notice;

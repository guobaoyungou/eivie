-- ============================================
-- 修复合成流程缺失的数据库字段
-- 日期: 2026-03-17
-- 说明: 
--   1. ai_travel_photo_generation 表新增 template_id 列（关联场景模板）
--   2. ai_travel_photo_result 表新增 bid 列（门店/商家ID）
-- ============================================

-- 1. generation表新增template_id字段
-- 用于关联 generation_scene_template 表的模板ID
SET @dbname = DATABASE();
SET @tablename = 'ddwx_ai_travel_photo_generation';
SET @columnname = 'template_id';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `template_id` int(11) NOT NULL DEFAULT 0 COMMENT ''关联场景模板ID'' AFTER `scene_id`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 为template_id添加索引
SET @indexname = 'idx_template_id';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD INDEX `', @indexname, '` (`template_id`)')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 2. result表新增bid字段
-- 用于记录门店/商家ID，便于按门店统计生成结果
SET @tablename = 'ddwx_ai_travel_photo_result';
SET @columnname = 'bid';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `bid` int(11) NOT NULL DEFAULT 0 COMMENT ''商家/门店ID'' AFTER `aid`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 为bid添加索引
SET @indexname = 'idx_bid';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD INDEX `', @indexname, '` (`bid`)')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ============================================
-- 验证
-- ============================================
-- DESC ddwx_ai_travel_photo_generation;
-- DESC ddwx_ai_travel_photo_result;

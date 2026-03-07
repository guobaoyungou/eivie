-- ============================================
-- 场景模板会员价格设置 - 数据库迁移SQL
-- 在 ddwx_generation_scene_template 表中新增以下字段
-- ============================================

-- 添加 base_price 字段：基础价格（游客价格）
ALTER TABLE `ddwx_generation_scene_template` 
ADD COLUMN IF NOT EXISTS `base_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '基础价格（游客价格）';

-- 添加 price_unit 字段：计价单位
ALTER TABLE `ddwx_generation_scene_template` 
ADD COLUMN IF NOT EXISTS `price_unit` varchar(20) NOT NULL DEFAULT 'per_image' COMMENT '计价单位：per_image=按张，per_second=按秒';

-- 添加 lvprice 字段：会员价开关
ALTER TABLE `ddwx_generation_scene_template` 
ADD COLUMN IF NOT EXISTS `lvprice` tinyint(1) NOT NULL DEFAULT '0' COMMENT '会员价开关：0=关闭，1=开启';

-- 添加 lvprice_data 字段：会员价格数据（JSON格式）
ALTER TABLE `ddwx_generation_scene_template` 
ADD COLUMN IF NOT EXISTS `lvprice_data` text NULL COMMENT '会员价格数据（JSON格式）';

-- 验证字段添加结果
-- SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT, COLUMN_COMMENT 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_NAME = 'ddwx_generation_scene_template' 
-- AND COLUMN_NAME IN ('base_price', 'price_unit', 'lvprice', 'lvprice_data');

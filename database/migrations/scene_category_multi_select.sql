-- 场景分类多选支持 - 数据库迁移SQL
-- 将 category_id (int) 改为 category_ids (varchar) 支持逗号分隔的多分类ID

-- 1. 添加新字段 category_ids
ALTER TABLE `ddwx_generation_scene_template` 
    ADD COLUMN `category_ids` varchar(500) NOT NULL DEFAULT '' COMMENT '关联分类ID列表，逗号分隔，最多10个' AFTER `category_id`;

-- 2. 将旧数据从 category_id 迁移到 category_ids
UPDATE `ddwx_generation_scene_template` 
    SET `category_ids` = CAST(`category_id` AS CHAR) 
    WHERE `category_id` > 0;

-- 3. 验证
SELECT id, category_id, category_ids FROM `ddwx_generation_scene_template` WHERE category_id > 0 LIMIT 10;

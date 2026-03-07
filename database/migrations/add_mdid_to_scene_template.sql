-- 场景模板表新增 mdid（关联门店ID）字段
-- 日期: 2026-03-05

-- 添加 mdid 字段
ALTER TABLE `ddwx_generation_scene_template`
ADD COLUMN `mdid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联门店ID，0表示不限门店/通用模板' AFTER `category_ids`;

-- 验证
SELECT id, template_name, category_ids, mdid FROM `ddwx_generation_scene_template` LIMIT 5;

-- ============================================
-- 自动标签系统：新增人种标签 (race_tag) 列
-- @date 2026-06-04
-- ============================================

-- 1. 人像表添加 race_tag 列
ALTER TABLE `ddwx_ai_travel_photo_portrait`
    ADD COLUMN `race_tag` varchar(30) NOT NULL DEFAULT '' COMMENT '人种标签（英文原始值，如 East Asian）' AFTER `age_tag`;

-- 2. 场景模板表添加 race_tag 列
ALTER TABLE `ddwx_generation_scene_template`
    ADD COLUMN `race_tag` varchar(30) NOT NULL DEFAULT '' COMMENT '人种标签（英文原始值，如 East Asian）' AFTER `age_tag`;

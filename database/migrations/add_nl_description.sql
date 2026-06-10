-- =====================================================
-- 自然语言描述人像功能 - 数据库迁移
-- 功能：新增豆包视觉模型生成的完整人物特征自然语言描述
-- 日期：2026-06-04
-- =====================================================

-- 1. 人像表新增自然语言描述相关列
ALTER TABLE ddwx_ai_travel_photo_portrait 
    ADD COLUMN nl_description TEXT NULL COMMENT '自然语言描述人像（豆包视觉模型生成）' AFTER has_accessory,
    ADD COLUMN nl_description_status TINYINT(1) NOT NULL DEFAULT 0 COMMENT '描述生成状态 0=未生成 1=生成中 2=已生成 3=失败' AFTER nl_description,
    ADD COLUMN nl_description_time INT NOT NULL DEFAULT 0 COMMENT '描述生成时间' AFTER nl_description_status;

-- 2. 合成设置表新增开关列（portrait_id=0 为全局设置，portrait_id>0 为人像级设置）
ALTER TABLE ddwx_ai_travel_photo_synthesis_setting 
    ADD COLUMN nl_description_enabled TINYINT(1) NOT NULL DEFAULT 1 COMMENT '自然语言描述人像开关 0=关闭 1=开启';

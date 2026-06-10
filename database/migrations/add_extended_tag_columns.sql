-- ============================================
-- 扩展人脸标签系统：新增16个扩展标签独立列
-- @date 2026-06-04
-- 表：ddwx_ai_travel_photo_portrait
-- ============================================

ALTER TABLE `ddwx_ai_travel_photo_portrait`
    ADD COLUMN `extended_tag_data` json COMMENT '扩展标签原始分析数据备份' AFTER `race_tag`,
    ADD COLUMN `emotion_primary` varchar(20) NOT NULL DEFAULT '' COMMENT '主表情标签',
    ADD COLUMN `emotion_scores` json COMMENT '6类表情概率分值',
    ADD COLUMN `glasses_type` varchar(20) NOT NULL DEFAULT '' COMMENT '眼镜类型',
    ADD COLUMN `eyelid_type` varchar(20) NOT NULL DEFAULT '' COMMENT '眼皮类型',
    ADD COLUMN `eyebrow_shape` varchar(20) NOT NULL DEFAULT '' COMMENT '眉形',
    ADD COLUMN `eyebrow_thickness` varchar(10) NOT NULL DEFAULT '' COMMENT '眉浓淡',
    ADD COLUMN `lip_type` varchar(10) NOT NULL DEFAULT '' COMMENT '唇厚薄',
    ADD COLUMN `has_beard` tinyint(1) NOT NULL DEFAULT 0 COMMENT '有无胡子',
    ADD COLUMN `skin_tone` varchar(10) NOT NULL DEFAULT '' COMMENT '肤色',
    ADD COLUMN `hair_length` varchar(10) NOT NULL DEFAULT '' COMMENT '发长',
    ADD COLUMN `has_bangs` tinyint(1) NOT NULL DEFAULT 0 COMMENT '有无刘海',
    ADD COLUMN `has_mask` tinyint(1) NOT NULL DEFAULT 0 COMMENT '有无口罩',
    ADD COLUMN `has_accessory` tinyint(1) NOT NULL DEFAULT 0 COMMENT '有无面部饰物',
    ADD COLUMN `extended_tag_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '扩展标签处理状态',
    ADD COLUMN `extended_tag_time` int(11) NOT NULL DEFAULT 0 COMMENT '扩展标签处理完成时间戳',
    ADD INDEX `idx_extended_tag_status` (`extended_tag_status`),
    ADD INDEX `idx_emotion_primary` (`emotion_primary`);

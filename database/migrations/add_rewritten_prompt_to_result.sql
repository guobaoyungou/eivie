-- 在 result 表新增 rewritten_prompt 字段，存储每张结果图对应的改写后提示词
ALTER TABLE `ddwx_ai_travel_photo_result`
    ADD COLUMN `rewritten_prompt` TEXT NULL COMMENT '改写后的提示词' AFTER `desc`;

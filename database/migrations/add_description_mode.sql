-- 合成设置表新增描述模式和NL提示词模板字段
ALTER TABLE `ddwx_ai_travel_photo_synthesis_setting`
ADD COLUMN `description_mode` VARCHAR(20) NOT NULL DEFAULT 'nl' COMMENT '改写模式：rewrite=标签改写，nl=NL描述改写' AFTER `nl_description_enabled`,
ADD COLUMN `nl_prompt_template` TEXT NULL COMMENT 'NL描述模式下的提示词改写模板' AFTER `description_mode`;

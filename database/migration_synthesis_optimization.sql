-- ============================================================
-- 人像管理合成设置优化 - 数据库变更
-- 日期: 2026-06-02
-- ============================================================

-- 1. 合成设置表新增 template_source 字段（模板来源）
ALTER TABLE `ddwx_ai_travel_photo_synthesis_setting`
ADD COLUMN `template_source` tinyint(1) NOT NULL DEFAULT '1' COMMENT '模板来源：1平台模板 2自建模板' AFTER `generate_mode`;

-- 2. 合成设置表新增 prompt_rewrite_template 字段（提示词改写模板）
-- 字段用途变更（2026-06-05）：原为标签改写模板，现改为提示词优化模板
-- 支持系统变量：{模板提示词} {人像描述} {模板绑定模型}
ALTER TABLE `ddwx_ai_travel_photo_synthesis_setting`
ADD COLUMN `prompt_rewrite_template` text COMMENT '提示词改写模板（支持{模板提示词}{人像描述}{模板绑定模型}等系统变量）' AFTER `prompt_rewrite_model`;

-- 3. 合成模板表新增 gender_tag 字段（性别标签，用于自动匹配人像性别）
ALTER TABLE `ddwx_ai_travel_photo_synthesis_template`
ADD COLUMN `gender_tag` varchar(20) NOT NULL DEFAULT '' COMMENT '性别标签：Male/Female/Both/空 用于自动匹配人像性别' AFTER `sort`;

-- 4. 系统变量记录
INSERT INTO `ddwx_sysset` (`name`, `value`)
SELECT 'system_variables', '{"自动标签性别":"人像自动识别的性别（Male→男性 / Female→女性）","自动标签年龄":"人像自动识别的年龄段（如：职场青年、中年主力等）","模板绑定模型":"当前合成模板绑定的AI模型名称（如：stable-diffusion-xl）","人像描述":"豆包视觉模型生成的人像自然语言描述（含性别年龄脸型五官服饰等11个维度）","模板提示词":"合成模板中的原始AI提示词文本（Prompt）"}'
WHERE NOT EXISTS (SELECT 1 FROM `ddwx_sysset` WHERE `name` = 'system_variables');

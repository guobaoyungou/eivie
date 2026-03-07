-- AI模型三级分类体系数据库迁移脚本
-- 一级分类：对话模型、图像生成、视频生成等
-- 二级分类：通义千问文生图、通义千问图像编辑等
-- 三级模型：qwen-image-max、qwen-image-max-2025-12-30等

-- 1. 修改ai_model_category表，添加分类层级支持
ALTER TABLE `ddwx_ai_model_category` 
ADD COLUMN `level` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '分类层级：1=一级分类，2=二级分类' AFTER `code`,
ADD COLUMN `parent_code` VARCHAR(50) DEFAULT NULL COMMENT '父级分类代码' AFTER `level`,
ADD INDEX `idx_parent_code` (`parent_code`),
ADD INDEX `idx_level` (`level`);

-- 2. 清空现有分类数据，重新插入三级体系
TRUNCATE TABLE `ddwx_ai_model_category`;

-- ========== 一级分类 ==========
INSERT INTO `ddwx_ai_model_category` (`code`, `level`, `parent_code`, `name`, `description`, `icon`, `sort`, `status`, `is_system`, `create_time`) VALUES
-- 对话模型
('dialogue', 1, NULL, '对话模型', '大语言模型、聊天机器人、智能问答', '💬', 100, 1, 1, UNIX_TIMESTAMP()),
-- 图像生成
('image_generation', 1, NULL, '图像生成', '文生图、图像编辑、图像增强', '🎨', 90, 1, 1, UNIX_TIMESTAMP()),
-- 视频生成
('video_generation', 1, NULL, '视频生成', '文生视频、视频编辑、视频合成', '🎬', 80, 1, 1, UNIX_TIMESTAMP()),
-- 专项模型
('specialized', 1, NULL, '专项模型', '特定领域专用模型', '🔧', 70, 1, 1, UNIX_TIMESTAMP()),
-- 实时多模态
('realtime_multimodal', 1, NULL, '实时多模态', '实时语音视频交互模型', '⚡', 60, 1, 1, UNIX_TIMESTAMP()),
-- 语音合成
('tts', 1, NULL, '语音合成', 'TTS文字转语音', '🔊', 50, 1, 1, UNIX_TIMESTAMP()),
-- 语音识别
('asr', 1, NULL, '语音识别', 'ASR语音转文字', '🎤', 40, 1, 1, UNIX_TIMESTAMP()),
-- 语言翻译
('translation', 1, NULL, '语言翻译', '机器翻译、多语言互译', '🌐', 30, 1, 1, UNIX_TIMESTAMP()),
-- 通用文本向量
('text_embedding', 1, NULL, '通用文本向量', '文本嵌入、语义检索', '📊', 20, 1, 1, UNIX_TIMESTAMP()),
-- 多模态向量
('multimodal_embedding', 1, NULL, '多模态向量', '图文向量、多模态检索', '🔮', 10, 1, 1, UNIX_TIMESTAMP());

-- ========== 二级分类：图像生成子分类 ==========
INSERT INTO `ddwx_ai_model_category` (`code`, `level`, `parent_code`, `name`, `description`, `icon`, `sort`, `status`, `is_system`, `create_time`) VALUES
-- 通义千问文生图
('qwen_text_to_image', 2, 'image_generation', '通义千问文生图', '阿里云通义千问文生图系列模型', '🖼️', 100, 1, 1, UNIX_TIMESTAMP()),
-- 通义千问图像编辑
('qwen_image_edit', 2, 'image_generation', '通义千问图像编辑', '阿里云通义千问图像编辑系列模型', '✏️', 90, 1, 1, UNIX_TIMESTAMP()),
-- DALL-E
('dalle', 2, 'image_generation', 'DALL-E系列', 'OpenAI DALL-E图像生成模型', '🎭', 80, 1, 1, UNIX_TIMESTAMP()),
-- Stable Diffusion
('stable_diffusion', 2, 'image_generation', 'Stable Diffusion', 'Stability AI开源图像生成模型', '🌈', 70, 1, 1, UNIX_TIMESTAMP()),
-- Midjourney
('midjourney', 2, 'image_generation', 'Midjourney', 'Midjourney艺术风格图像生成', '🎨', 60, 1, 1, UNIX_TIMESTAMP());

-- ========== 二级分类：对话模型子分类 ==========
INSERT INTO `ddwx_ai_model_category` (`code`, `level`, `parent_code`, `name`, `description`, `icon`, `sort`, `status`, `is_system`, `create_time`) VALUES
('qwen_turbo', 2, 'dialogue', '通义千问Turbo', '阿里云通义千问对话模型', '⚡', 100, 1, 1, UNIX_TIMESTAMP()),
('gpt', 2, 'dialogue', 'GPT系列', 'OpenAI GPT对话模型', '🤖', 90, 1, 1, UNIX_TIMESTAMP()),
('claude', 2, 'dialogue', 'Claude系列', 'Anthropic Claude对话模型', '🧠', 80, 1, 1, UNIX_TIMESTAMP()),
('gemini', 2, 'dialogue', 'Gemini系列', 'Google Gemini对话模型', '✨', 70, 1, 1, UNIX_TIMESTAMP());

-- ========== 二级分类：视频生成子分类 ==========
INSERT INTO `ddwx_ai_model_category` (`code`, `level`, `parent_code`, `name`, `description`, `icon`, `sort`, `status`, `is_system`, `create_time`) VALUES
('keling', 2, 'video_generation', '可灵视频', '快手可灵文生视频模型', '🎥', 100, 1, 1, UNIX_TIMESTAMP()),
('runway', 2, 'video_generation', 'Runway系列', 'Runway AI视频生成模型', '🎬', 90, 1, 1, UNIX_TIMESTAMP()),
('pika', 2, 'video_generation', 'Pika系列', 'Pika Labs视频生成模型', '📹', 80, 1, 1, UNIX_TIMESTAMP());

-- ========== 二级分类：语音合成子分类 ==========
INSERT INTO `ddwx_ai_model_category` (`code`, `level`, `parent_code`, `name`, `description`, `icon`, `sort`, `status`, `is_system`, `create_time`) VALUES
('cosyvoice', 2, 'tts', 'CosyVoice', '阿里云语音合成模型', '🔊', 100, 1, 1, UNIX_TIMESTAMP()),
('azure_tts', 2, 'tts', 'Azure TTS', '微软Azure语音合成', '🎙️', 90, 1, 1, UNIX_TIMESTAMP());

-- ========== 二级分类：语音识别子分类 ==========
INSERT INTO `ddwx_ai_model_category` (`code`, `level`, `parent_code`, `name`, `description`, `icon`, `sort`, `status`, `is_system`, `create_time`) VALUES
('paraformer', 2, 'asr', 'Paraformer', '阿里云语音识别模型', '🎤', 100, 1, 1, UNIX_TIMESTAMP()),
('whisper', 2, 'asr', 'Whisper系列', 'OpenAI Whisper语音识别', '🎧', 90, 1, 1, UNIX_TIMESTAMP());

-- 3. 修改ai_travel_photo_model表，添加二级分类支持
ALTER TABLE `ddwx_ai_travel_photo_model`
ADD COLUMN `category_level2_code` VARCHAR(50) DEFAULT NULL COMMENT '二级分类代码' AFTER `category_code`,
ADD COLUMN `model_version` VARCHAR(100) DEFAULT NULL COMMENT '模型版本号' AFTER `provider`,
ADD INDEX `idx_category_level2` (`category_level2_code`);

-- 4. 更新注释说明
ALTER TABLE `ddwx_ai_travel_photo_model` 
MODIFY COLUMN `category_code` VARCHAR(50) NOT NULL COMMENT '一级分类代码',
MODIFY COLUMN `model_type` VARCHAR(50) DEFAULT NULL COMMENT '【已废弃】旧字段，保留兼容';

COMMIT;

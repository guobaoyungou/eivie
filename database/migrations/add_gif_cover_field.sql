-- 为视频场景模板添加GIF封面字段
-- 用于存储视频前30帧自动转换的GIF动画URL
ALTER TABLE `ddwx_generation_scene_template` 
ADD COLUMN `gif_cover` varchar(500) NOT NULL DEFAULT '' COMMENT 'GIF封面URL(视频前30帧)' AFTER `cover_image`;

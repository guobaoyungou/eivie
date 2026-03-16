-- 添加人像表合成状态字段
-- 执行时间：2025年

ALTER TABLE `ddwx_ai_travel_photo_portrait`
MODIFY COLUMN `synthesis_status` tinyint(2) NOT NULL DEFAULT 0 COMMENT '合成状态：0未处理 1已提交 2处理中 3成功 4失败',
ADD COLUMN `synthesis_count` int(11) DEFAULT 0 COMMENT '合成生成数量',
ADD COLUMN `synthesis_time` int(11) DEFAULT 0 COMMENT '最后合成时间戳',
ADD COLUMN `synthesis_error` varchar(500) DEFAULT NULL COMMENT '合成错误信息',
ADD KEY `idx_synthesis_status` (`synthesis_status`);

-- 选片端：新增加载数量和刷新时间配置字段
-- 执行时间：2026-06-09
ALTER TABLE `ddwx_mendian` 
ADD COLUMN `xpd_load_count` INT DEFAULT 15 COMMENT '选片端每次加载头像数量，默认15' AFTER `xpd_face_detect`,
ADD COLUMN `xpd_refresh_interval` INT DEFAULT 10 COMMENT '选片端自动刷新间隔(秒)，默认10秒' AFTER `xpd_load_count`;

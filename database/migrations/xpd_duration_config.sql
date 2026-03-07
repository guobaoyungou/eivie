-- AI旅拍选片端 - 轮播时间配置字段
-- 日期: 2026-01-23

-- 为门店表添加轮播时间配置字段
ALTER TABLE `ddwx_mendian` 
ADD COLUMN `xpd_image_duration` INT UNSIGNED DEFAULT 1000 COMMENT '单张图片展示时长(毫秒)' AFTER `xpd_template`,
ADD COLUMN `xpd_group_duration` INT UNSIGNED DEFAULT 5000 COMMENT '单组展示时长(毫秒)' AFTER `xpd_image_duration`;

-- 字段说明：
-- xpd_image_duration: 单张图片展示时长，单位毫秒，默认1000ms(1秒)，建议范围500-3000ms
-- xpd_group_duration: 单组展示时长，单位毫秒，默认5000ms(5秒)，建议范围3000-10000ms

-- 使用场景：
-- 客流少时：增加展示时间，让游客有更多时间观看
-- 客流多时：减少展示时间，加快轮播速度，展示更多内容

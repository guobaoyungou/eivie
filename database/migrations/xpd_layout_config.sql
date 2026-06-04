-- AI旅拍选片端 - 自由布局系统配置字段
-- 日期: 2026-05-08

-- 为门店表添加布局配置、背景色和人脸识别开关字段
ALTER TABLE `ddwx_mendian` 
ADD COLUMN `xpd_layout` TEXT DEFAULT NULL COMMENT '选片端布局配置JSON，包含模块位置/尺寸/zIndex等' AFTER `xpd_group_duration`,
ADD COLUMN `xpd_bg_color` VARCHAR(7) DEFAULT '#000000' COMMENT '选片端页面背景色(hex值)' AFTER `xpd_layout`,
ADD COLUMN `xpd_face_detect` TINYINT(1) DEFAULT 1 COMMENT '人脸识别开关(1开启/0关闭)' AFTER `xpd_bg_color`;

-- 字段说明：
-- xpd_layout: 布局配置JSON。格式示例：
-- {
--   "bgColor": "#000000",
--   "faceDetectEnabled": true,
--   "modules": [
--     { "id":"swiper-main", "type":"swiper", "top":"0%", "left":"0%", "width":"100%", "height":"80%", "zIndex":1, "visible":true },
--     { "id":"avatar-bar",  "type":"avatar", "top":"80%", "left":"0%", "width":"100%", "height":"20%", "zIndex":2, "visible":true },
--     { "id":"qrcode-box",  "type":"qrcode", "top":"65%", "left":"85%", "width":"14%", "height":"14%", "zIndex":10, "visible":true }
--   ]
-- }
-- xpd_bg_color: 页面背景色，hex格式如 #000000，#ffffff，默认黑色
-- xpd_face_detect: 人脸识别开关，1=开启 0=关闭，默认开启

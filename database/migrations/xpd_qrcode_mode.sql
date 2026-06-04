-- AI旅拍选片端 - 二维码展示方式配置字段
-- 日期: 2026-06-02

-- 为门店表添加二维码展示方式字段
ALTER TABLE `ddwx_mendian` 
ADD COLUMN `xpd_qrcode_mode` VARCHAR(10) DEFAULT 'mp' COMMENT '二维码展示方式: mp=公众号二维码, h5=H5选片码' AFTER `xpd_group_duration`;

-- 字段说明：
-- xpd_qrcode_mode: 控制XPD大屏右下角二维码的展示类型
--   mp: 展示微信公众号带参数永久二维码（mp_qrcode_url），用户扫码后先关注公众号再进入选片中心
--   h5: 展示H5选片页预生成二维码（qrcode_url），用户扫码直接进入选片中心

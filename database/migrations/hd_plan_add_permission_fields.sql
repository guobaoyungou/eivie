-- 套餐表新增权限控制字段
-- 用于控制不同套餐级别下商户是否可使用公众号自定义和大屏显示自定义功能
-- 执行时间: 2026-04-07

ALTER TABLE `ddwx_hd_plan`
ADD COLUMN `allow_custom_wx` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否允许自定义公众号设置(0否1是)' AFTER `status`,
ADD COLUMN `allow_custom_display` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否允许自定义大屏显示设置(0否1是)' AFTER `allow_custom_wx`;

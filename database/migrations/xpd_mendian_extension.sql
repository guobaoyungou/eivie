-- AI旅拍选片端 - 门店管理扩展字段
-- 日期: 2026-01-22

-- 为门店表添加选片模板字段
ALTER TABLE `ddwx_mendian` 
ADD COLUMN `xpd_template` VARCHAR(50) DEFAULT 'template_1' COMMENT '选片端展示模板' AFTER `status`;

-- 说明：
-- xpd_template: 选片端展示模板ID，默认为template_1（经典上下布局）
-- 可选值: template_1（经典上下布局）, template_2（全屏沉浸式）, template_3（左右分屏）, template_4（栅格多屏）, template_5（轮播卡片）

-- ============================================================
-- 人像多用户关联改造
-- 将 ai_travel_photo_portrait.user_openid (1:1) 改为
-- ai_travel_photo_portrait_user 多对多关联表
-- 
-- 注意：实际表名前缀为 ddwx_ （项目配置文件 config.php 中定义）
-- 本脚本由 PHP 动态执行（自动读取 prefix），MySQL 原生执行时请手动加前缀
-- ============================================================

-- 1. 创建关联表
CREATE TABLE IF NOT EXISTS `ai_travel_photo_portrait_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `portrait_id` int(11) NOT NULL COMMENT '人像ID',
  `user_openid` varchar(64) NOT NULL DEFAULT '' COMMENT '微信OpenID',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '关联时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_portrait_openid` (`portrait_id`, `user_openid`),
  KEY `idx_user_openid` (`user_openid`),
  KEY `idx_portrait_id` (`portrait_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='人像-用户多对多关联表';

-- 2. 将现有 user_openid 非空数据迁移至关联表
INSERT IGNORE INTO `ai_travel_photo_portrait_user` (`portrait_id`, `user_openid`, `create_time`)
SELECT `id`, `user_openid`, COALESCE(`update_time`, `create_time`, UNIX_TIMESTAMP())
FROM `ai_travel_photo_portrait`
WHERE `user_openid` IS NOT NULL AND `user_openid` != '';

-- 3. 验证迁移结果
SELECT 
  (SELECT COUNT(*) FROM `ai_travel_photo_portrait` WHERE `user_openid` IS NOT NULL AND `user_openid` != '') AS `source_count`,
  (SELECT COUNT(*) FROM `ai_travel_photo_portrait_user`) AS `migrated_count`;

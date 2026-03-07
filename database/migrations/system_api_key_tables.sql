-- ============================================================
-- 系统API Key配置功能 - 数据库表创建脚本
-- ============================================================

-- 系统API Key配置表
CREATE TABLE IF NOT EXISTS `ddwx_system_api_key` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `provider_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联供应商ID（model_provider.id）',
  `provider_code` varchar(50) NOT NULL DEFAULT '' COMMENT '供应商标识（冗余字段，方便查询）',
  `api_key` varchar(500) NOT NULL DEFAULT '' COMMENT 'API密钥（AES-256-CBC加密存储）',
  `api_secret` varchar(500) NOT NULL DEFAULT '' COMMENT 'API密钥Secret（部分供应商需要）',
  `extra_config` json DEFAULT NULL COMMENT '扩展配置参数（如endpoint等）',
  `config_name` varchar(100) NOT NULL DEFAULT '' COMMENT '配置名称（便于识别）',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注说明',
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '启用状态：1-启用，0-停用',
  `sort` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '排序权重，默认0',
  `create_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间戳',
  `update_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provider_id` (`provider_id`) COMMENT '一个供应商只能配置一次',
  KEY `idx_provider_code` (`provider_code`) COMMENT '供应商查询优化',
  KEY `idx_is_active` (`is_active`) COMMENT '状态筛选优化'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统API Key配置表';

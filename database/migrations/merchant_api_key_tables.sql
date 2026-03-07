-- ============================================================
-- 商家API Key配置表
-- 用于商户级别的API Key管理，支持多Key池和门店范围配置
-- 创建时间: 2026-03-02
-- ============================================================

-- 商家API Key配置主表
CREATE TABLE IF NOT EXISTS `ddwx_merchant_api_key` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '平台ID',
  `bid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '商家ID',
  `provider_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联供应商ID（model_provider.id）',
  `provider_code` varchar(50) NOT NULL DEFAULT '' COMMENT '供应商标识码',
  `config_name` varchar(100) NOT NULL DEFAULT '' COMMENT '配置名称（如：Key-1、Key-旗舰店）',
  `api_key` varchar(500) NOT NULL DEFAULT '' COMMENT 'API Key（AES加密存储）',
  `api_secret` varchar(500) NOT NULL DEFAULT '' COMMENT 'API Secret（AES加密存储）',
  `extra_config` text COMMENT '扩展配置（JSON格式）',
  `max_concurrency` int(11) NOT NULL DEFAULT 5 COMMENT '该Key最大并发数限制',
  `current_concurrency` int(11) NOT NULL DEFAULT 0 COMMENT '当前正在使用的并发数',
  `weight` int(11) NOT NULL DEFAULT 100 COMMENT '负载均衡权重（1-100）',
  `total_calls` int(11) NOT NULL DEFAULT 0 COMMENT '累计调用次数',
  `fail_calls` int(11) NOT NULL DEFAULT 0 COMMENT '累计失败次数',
  `last_used_time` int(11) NOT NULL DEFAULT 0 COMMENT '最后使用时间',
  `last_error_time` int(11) NOT NULL DEFAULT 0 COMMENT '最后出错时间',
  `last_error_msg` varchar(500) NOT NULL DEFAULT '' COMMENT '最后错误信息',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用状态：0=禁用，1=启用',
  `scope_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '门店适用范围：0=全部门店，1=指定门店',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序值（优先级，数字越小越靠前）',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_aid_bid` (`aid`, `bid`),
  KEY `idx_bid_provider` (`bid`, `provider_code`),
  KEY `idx_provider` (`provider_code`),
  UNIQUE KEY `uk_bid_apikey` (`bid`, `api_key`(100)),
  KEY `idx_scope_type` (`bid`, `scope_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商家API Key配置表';

-- Key门店关联表（scope_type=1时使用）
CREATE TABLE IF NOT EXISTS `ddwx_merchant_api_key_mendian` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `key_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联merchant_api_key.id',
  `mdid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '门店ID（关联mendian.id）',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key_mdid` (`key_id`, `mdid`),
  KEY `idx_mdid` (`mdid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API Key门店关联表';

-- ============================================================
-- 索引说明：
-- idx_aid_bid: 按商家查询配置列表
-- idx_bid_provider: 按商家和供应商查询（支持多Key）
-- idx_provider: 按供应商查询
-- uk_bid_apikey: 防止同一商家重复添加相同Key
-- idx_scope_type: 门店范围查询
-- uk_key_mdid: 防止重复关联
-- idx_mdid: 按门店查询可用Key
-- ============================================================

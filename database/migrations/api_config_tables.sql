-- ================================================================
-- API配置功能数据库表结构
-- 创建时间：2026-02-04
-- 说明：包含API配置、计费规则、调用日志、使用授权四张核心表
-- ================================================================

-- ----------------------------
-- 表1：API配置表 (ddwx_api_config)
-- 用途：存储系统预置API和自定义API的配置信息
-- ----------------------------
CREATE TABLE IF NOT EXISTS `ddwx_api_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '平台ID，0表示超级管理员',
  `bid` int(11) NOT NULL DEFAULT '0' COMMENT '商家ID，0表示平台级',
  `mdid` int(11) NOT NULL DEFAULT '0' COMMENT '门店ID，0表示商家级',
  `model_id` int(11) DEFAULT '0' COMMENT '关联AI模型实例ID(ddwx_ai_model_instance.id)',
  `api_code` varchar(100) NOT NULL COMMENT 'API唯一标识码',
  `api_name` varchar(200) NOT NULL COMMENT 'API显示名称',
  `api_type` varchar(50) NOT NULL COMMENT 'API类型(如:image_generation,text_generation)',
  `provider` varchar(50) NOT NULL COMMENT '服务提供商(aliyun,baidu,openai)',
  `api_key` varchar(500) NOT NULL COMMENT 'API密钥(加密存储)',
  `api_secret` varchar(500) DEFAULT NULL COMMENT 'API密钥Secret(加密存储)',
  `endpoint_url` varchar(500) NOT NULL COMMENT 'API端点地址',
  `config_json` text COMMENT '其他配置参数(JSON格式)',
  `is_system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否系统预置(1=系统预置,0=自定义)',
  `owner_uid` int(11) DEFAULT '0' COMMENT '配置创建者UID',
  `scope_type` tinyint(1) NOT NULL DEFAULT '2' COMMENT '作用域(1=全局公开,2=仅自用,3=付费公开)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用(1=启用,0=禁用)',
  `description` text COMMENT 'API描述说明',
  `sort` int(11) NOT NULL DEFAULT '100' COMMENT '排序权重',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_api_code` (`api_code`),
  KEY `idx_aid` (`aid`),
  KEY `idx_bid` (`bid`),
  KEY `idx_mdid` (`mdid`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_is_system` (`is_system`),
  KEY `idx_scope_type` (`scope_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API配置表';

-- ----------------------------
-- 表2：API计费规则表 (ddwx_api_pricing)
-- 用途：存储API的计费模式和定价策略
-- ----------------------------
CREATE TABLE IF NOT EXISTS `ddwx_api_pricing` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `api_config_id` int(11) NOT NULL COMMENT '关联API配置ID',
  `billing_mode` varchar(20) NOT NULL DEFAULT 'fixed' COMMENT '计费模式(fixed/token/duration/image)',
  `cost_per_unit` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '单位成本价(元)',
  `price_per_unit` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '单位售价(元)',
  `unit_type` varchar(20) NOT NULL DEFAULT 'per_call' COMMENT '计费单位(per_call/per_token/per_minute/per_image)',
  `min_charge` decimal(10,4) DEFAULT '0.0000' COMMENT '最低收费金额',
  `free_quota` int(11) DEFAULT '0' COMMENT '免费额度(每日)',
  `tier_pricing_json` text COMMENT '阶梯定价规则(JSON格式)',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  KEY `idx_api_config_id` (`api_config_id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API计费规则表';

-- ----------------------------
-- 表3：API调用日志表 (ddwx_api_call_log)
-- 用途：记录所有API调用的详细日志和计费信息
-- ----------------------------
CREATE TABLE IF NOT EXISTS `ddwx_api_call_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `api_config_id` int(11) NOT NULL COMMENT '关联API配置ID',
  `caller_aid` int(11) NOT NULL DEFAULT '0' COMMENT '调用者平台ID',
  `caller_bid` int(11) NOT NULL DEFAULT '0' COMMENT '调用者商家ID',
  `caller_mdid` int(11) NOT NULL DEFAULT '0' COMMENT '调用者门店ID',
  `caller_uid` int(11) NOT NULL COMMENT '调用者用户ID',
  `request_id` varchar(100) NOT NULL COMMENT '请求唯一标识',
  `request_params` text COMMENT '请求参数(JSON格式)',
  `response_data` text COMMENT '响应数据(JSON格式)',
  `status_code` int(11) NOT NULL DEFAULT '0' COMMENT 'HTTP状态码',
  `is_success` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否成功(1=成功,0=失败)',
  `error_message` varchar(500) DEFAULT NULL COMMENT '错误信息',
  `consumed_units` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '消耗单位数',
  `charge_amount` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '计费金额',
  `balance_before` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '扣费前余额',
  `balance_after` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '扣费后余额',
  `response_time` int(11) NOT NULL DEFAULT '0' COMMENT '响应时间(毫秒)',
  `ip_address` varchar(50) DEFAULT NULL COMMENT '调用IP',
  `call_time` int(11) NOT NULL DEFAULT '0' COMMENT '调用时间戳',
  PRIMARY KEY (`id`),
  KEY `idx_api_config_id` (`api_config_id`),
  KEY `idx_caller_uid` (`caller_uid`),
  KEY `idx_request_id` (`request_id`),
  KEY `idx_call_time` (`call_time`),
  KEY `idx_is_success` (`is_success`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API调用日志表';

-- ----------------------------
-- 表4：API使用授权表 (ddwx_api_authorization)
-- 用途：管理API的使用授权和访问控制
-- ----------------------------
CREATE TABLE IF NOT EXISTS `ddwx_api_authorization` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `api_config_id` int(11) NOT NULL COMMENT '关联API配置ID',
  `grantee_aid` int(11) NOT NULL DEFAULT '0' COMMENT '被授权平台ID',
  `grantee_bid` int(11) NOT NULL DEFAULT '0' COMMENT '被授权商家ID',
  `grantee_mdid` int(11) NOT NULL DEFAULT '0' COMMENT '被授权门店ID',
  `auth_type` tinyint(1) NOT NULL DEFAULT '2' COMMENT '授权类型(1=免费授权,2=付费授权)',
  `quota_daily` int(11) DEFAULT '0' COMMENT '每日额度限制',
  `quota_monthly` int(11) DEFAULT '0' COMMENT '每月额度限制',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
  `expire_time` int(11) DEFAULT '0' COMMENT '过期时间',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique_auth` (`api_config_id`,`grantee_aid`,`grantee_bid`,`grantee_mdid`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API使用授权表';

-- ----------------------------
-- 初始化说明
-- ----------------------------
-- 1. 表结构创建后，系统预置API需要由超级管理员（admin账号）在后台配置
-- 2. API密钥存储前需使用AES-256加密
-- 3. 调用日志表建议定期归档，避免数据量过大影响性能
-- 4. 授权表的唯一索引确保同一对象不会重复授权
-- ----------------------------

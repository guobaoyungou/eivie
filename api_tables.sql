-- ================================================================
-- API管理功能数据库表结构
-- 创建时间：2026-02-02
-- ================================================================

-- 1. API接口表
CREATE TABLE IF NOT EXISTS `ddwx_api_interface` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '接口ID',
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '平台ID',
  `controller` varchar(100) NOT NULL DEFAULT '' COMMENT '控制器名称',
  `action` varchar(100) NOT NULL DEFAULT '' COMMENT '方法名称',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '接口名称',
  `category` varchar(50) NOT NULL DEFAULT '' COMMENT '接口分类',
  `method` varchar(20) NOT NULL DEFAULT 'POST' COMMENT '请求方式(GET/POST/PUT/DELETE)',
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT '接口路径',
  `description` text COMMENT '接口描述',
  `auth_required` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否需要认证(0=否,1=是)',
  `request_params` text COMMENT '请求参数(JSON格式)',
  `response_example` text COMMENT '响应示例(JSON格式)',
  `tags` varchar(255) DEFAULT '' COMMENT '标签(逗号分隔)',
  `remark` text COMMENT '备注',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态(0=禁用,1=启用)',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_aid` (`aid`),
  KEY `idx_controller` (`controller`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  UNIQUE KEY `unique_interface` (`aid`, `controller`, `action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API接口表';

-- 2. API测试日志表
CREATE TABLE IF NOT EXISTS `ddwx_api_test_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '平台ID',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '测试用户ID',
  `interface_id` int(11) NOT NULL DEFAULT '0' COMMENT '接口ID',
  `request_params` text COMMENT '请求参数(JSON)',
  `response_data` text COMMENT '响应数据(JSON)',
  `response_time` int(11) NOT NULL DEFAULT '0' COMMENT '响应时间(毫秒)',
  `status_code` int(11) NOT NULL DEFAULT '200' COMMENT 'HTTP状态码',
  `ip` varchar(50) DEFAULT '' COMMENT '请求IP',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_aid` (`aid`),
  KEY `idx_interface` (`interface_id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API测试日志表';

-- ================================================================
-- 使用说明
-- ================================================================
-- 1. 请将此SQL文件导入到你的数据库
-- 2. 确保数据库表前缀为 ddwx_（如不同请修改）
-- 3. 建表后即可使用API管理功能
-- 
-- 可以通过以下命令导入：
-- mysql -u用户名 -p密码 数据库名 < api_tables.sql
-- 
-- 或在phpMyAdmin中导入此SQL文件
-- ================================================================

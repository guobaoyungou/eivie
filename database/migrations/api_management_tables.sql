-- API管理功能数据库表
-- 创建时间: 2026-02-02

-- ========================================
-- API接口信息表
-- ========================================
CREATE TABLE IF NOT EXISTS `ddwx_api_interface` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '账户ID',
  `controller` varchar(100) NOT NULL DEFAULT '' COMMENT '控制器名称',
  `action` varchar(100) NOT NULL DEFAULT '' COMMENT '方法名称',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '接口名称',
  `category` varchar(50) NOT NULL DEFAULT '' COMMENT '接口分类',
  `method` varchar(20) NOT NULL DEFAULT 'POST' COMMENT '请求方式',
  `path` varchar(500) NOT NULL DEFAULT '' COMMENT '接口路径',
  `description` text COMMENT '接口描述',
  `request_params` text COMMENT '请求参数JSON',
  `response_example` text COMMENT '响应示例JSON',
  `auth_required` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否需要登录 0否1是',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 0停用1启用',
  `tags` varchar(500) DEFAULT '' COMMENT '标签(多个逗号分隔)',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `remark` text COMMENT '备注说明',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_aid` (`aid`),
  KEY `idx_controller` (`controller`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API接口信息表';

-- ========================================
-- API测试日志表
-- ========================================
CREATE TABLE IF NOT EXISTS `ddwx_api_test_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '账户ID',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '操作用户ID',
  `interface_id` int(11) NOT NULL DEFAULT '0' COMMENT '接口ID',
  `request_params` text COMMENT '请求参数',
  `response_data` text COMMENT '响应数据',
  `response_time` int(11) NOT NULL DEFAULT '0' COMMENT '响应耗时(毫秒)',
  `status_code` int(11) NOT NULL DEFAULT '0' COMMENT '状态码',
  `ip` varchar(50) DEFAULT '' COMMENT '请求IP',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_aid_uid` (`aid`,`uid`),
  KEY `idx_interface_id` (`interface_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API测试日志表';

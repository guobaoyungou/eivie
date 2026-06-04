-- ======================================
-- 算八字功能数据库迁移脚本
-- 日期: 2026-05-07
-- 说明: 创建八字配置表和订单记录表
-- ======================================

-- 1. 八字功能配置表
CREATE TABLE IF NOT EXISTS `ddwx_bazi_config` (
  `aid` int(11) NOT NULL COMMENT '平台ID（主键，一个平台一条配置）',
  `model` varchar(100) NOT NULL DEFAULT 'doubao-seed-2-0-pro-260215' COMMENT 'AI模型名称',
  `skill_prompt` mediumtext NOT NULL COMMENT 'Skill系统提示词（完整SKILL.md内容）',
  `pay_mode` varchar(20) NOT NULL DEFAULT 'free' COMMENT '付费模式: free=免费, pay_then_predict=先付费后预测, predict_then_pay=预测后付费看全文',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '价格（元）',
  `preview_percent` tinyint(3) NOT NULL DEFAULT '50' COMMENT '预览百分比(0-100)，predict_then_pay模式下控制摘要展示比例',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  PRIMARY KEY (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='算八字-功能配置表';

-- 2. 八字订单记录表（同时作为订单和记录）
CREATE TABLE IF NOT EXISTS `ddwx_bazi_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '平台ID',
  `mid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `ordernum` varchar(50) NOT NULL DEFAULT '' COMMENT '订单编号（唯一）',
  `payorderid` int(11) NOT NULL DEFAULT '0' COMMENT '关联 payorder 表ID',
  `pay_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '支付状态：0待支付，1已支付/已解锁',
  `pay_mode` varchar(20) NOT NULL DEFAULT 'free' COMMENT '付费模式：free/pay_then_predict/predict_then_pay',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实付金额',
  `pay_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '支付时间戳',
  `transaction_id` varchar(100) NOT NULL DEFAULT '' COMMENT '第三方支付订单号',
  `input_json` mediumtext COMMENT '用户输入参数（JSON：name/birth_date/birth_time/birth_place/gender）',
  `result_json` mediumtext COMMENT 'AI分析结果（JSON：result/reasoning/usage/latency_ms）',
  `preview_text` text COMMENT '预览摘要文本（predict_then_pay模式下按百分比截取的预览内容）',
  `latency_ms` int(11) NOT NULL DEFAULT '0' COMMENT 'AI请求耗时（毫秒）',
  `total_tokens` int(11) NOT NULL DEFAULT '0' COMMENT 'Token总用量',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间戳',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `ip` varchar(50) DEFAULT NULL COMMENT '请求IP地址',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ordernum` (`ordernum`),
  KEY `idx_aid` (`aid`),
  KEY `idx_mid` (`mid`),
  KEY `idx_pay_status` (`pay_status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='算八字-订单记录表';

-- ======================================
-- 3. 公众号模板消息通知
-- 八字分析结果通知复用已有的 tmpl_formsub（表单提交通知）模板字段，无需新增字段
-- ======================================

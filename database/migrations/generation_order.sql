-- ======================================
-- 生成订单与退款功能数据库迁移脚本
-- 日期: 2026-03-03
-- 说明: 为照片/视频生成模块增加订单管理和退款申请功能
-- ======================================

-- 1. 创建生成订单表 ddwx_generation_order
CREATE TABLE IF NOT EXISTS `ddwx_generation_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `aid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '平台ID',
  `bid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商家ID',
  `mid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `ordernum` varchar(50) NOT NULL DEFAULT '' COMMENT '订单编号（唯一）',
  `generation_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '生成类型：1=照片，2=视频',
  `scene_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '场景模板ID',
  `scene_name` varchar(200) NOT NULL DEFAULT '' COMMENT '场景名称（冗余）',
  `record_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联生成记录ID',
  `total_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单总金额',
  `pay_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实付金额',
  `pay_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '支付状态：0待支付，1已支付，2已取消',
  `pay_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '支付时间戳',
  `payorderid` int(11) NOT NULL DEFAULT '0' COMMENT '关联 payorder 表ID',
  `paytypeid` tinyint(1) NOT NULL DEFAULT '0' COMMENT '支付方式ID（1余额/2微信/3支付宝）',
  `paytype` varchar(50) NOT NULL DEFAULT '' COMMENT '支付方式描述',
  `paynum` varchar(100) NOT NULL DEFAULT '' COMMENT '支付流水号',
  `transaction_id` varchar(100) NOT NULL DEFAULT '' COMMENT '第三方支付订单号',
  `refund_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '退款状态：0无退款，1待审核，2已退款，3已驳回',
  `refund_reason` varchar(255) DEFAULT NULL COMMENT '退款原因',
  `refund_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '退款金额',
  `refund_time` int(11) NOT NULL DEFAULT '0' COMMENT '退款时间戳',
  `refund_checkremark` varchar(255) DEFAULT NULL COMMENT '退款审核备注',
  `task_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '生成任务状态（冗余）：0待处理/1处理中/2成功/3失败/4已取消',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '记录状态：1正常，0删除',
  `createtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ordernum` (`ordernum`),
  KEY `idx_aid_bid` (`aid`, `bid`),
  KEY `idx_mid` (`mid`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_pay_status` (`pay_status`),
  KEY `idx_refund_status` (`refund_status`),
  KEY `idx_generation_type` (`generation_type`),
  KEY `idx_createtime` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='生成订单表';

-- 2. 为现有 ddwx_generation_record 表增加 order_id 字段（反向关联）
ALTER TABLE `ddwx_generation_record` 
ADD COLUMN `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联生成订单ID' AFTER `id`;

-- 为 order_id 添加索引
ALTER TABLE `ddwx_generation_record` 
ADD INDEX `idx_order_id` (`order_id`);

-- ======================================
-- 数据迁移说明
-- ======================================
-- 1. 本次新增不影响现有数据
-- 2. 历史的生成记录 order_id = 0，表示非订单模式创建
-- 3. 新的订单模式：用户选择场景 → 创建订单 → 支付 → 自动创建生成记录
-- ======================================

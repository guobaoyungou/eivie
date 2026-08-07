<?php

if(!pdo_fieldexists2("ddwx_kanjia_product","helpgive_type")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD `helpgive_type` tinyint(1) DEFAULT '0';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD `helpgive_percent` float(11,2) DEFAULT '0.00';");
}

if(!pdo_fieldexists2("ddwx_kanjia_product","helpgive_type")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD `helpgive_type` tinyint(1) DEFAULT '0';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD `helpgive_percent` float(11,2) DEFAULT '0.00';");
}

 if(!pdo_fieldexists2("ddwx_yuyue_set","isautopd")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_set` ADD COLUMN `isautopd` tinyint(1) NOT NULL DEFAULT '0';");

	\think\facade\Db::execute("ALTER TABLE `ddwx_member_moneylog` MODIFY COLUMN `remark` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_scorelog` MODIFY COLUMN `remark` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_commissionlog` MODIFY COLUMN `remark` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
}

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_cycle_category` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `pid` int(11) DEFAULT '0',
	  `name` varchar(255) DEFAULT NULL,
	  `pic` varchar(255) DEFAULT NULL,
	  `status` int(1) DEFAULT '1',
	  `sort` int(11) DEFAULT '1',
	  `createtime` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_cycle_comment` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `mid` int(11) DEFAULT NULL,
	  `orderid` int(11) DEFAULT NULL,
	  `ogid` int(11) DEFAULT NULL,
	  `proid` int(11) DEFAULT NULL,
	  `proname` varchar(255) DEFAULT NULL,
	  `propic` varchar(255) DEFAULT NULL,
	  `ggid` int(11) DEFAULT NULL,
	  `ggname` varchar(255) DEFAULT NULL,
	  `ordernum` varchar(50) DEFAULT NULL,
	  `openid` varchar(255) DEFAULT NULL,
	  `nickname` varchar(255) DEFAULT NULL,
	  `headimg` varchar(255) DEFAULT NULL,
	  `score` int(11) DEFAULT NULL,
	  `content` varchar(255) DEFAULT NULL,
	  `content_pic` varchar(255) DEFAULT NULL,
	  `reply_content` varchar(255) DEFAULT NULL,
	  `reply_content_pic` varchar(255) DEFAULT NULL,
	  `append_content` varchar(255) DEFAULT NULL,
	  `append_content_pic` varchar(255) DEFAULT NULL,
	  `append_reply_content` varchar(255) DEFAULT NULL,
	  `append_reply_content_pic` varchar(255) DEFAULT NULL,
	  `createtime` int(11) DEFAULT NULL,
	  `appendtime` int(11) DEFAULT NULL,
	  `status` int(1) DEFAULT '1',
	  `reply_time` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE,
	  KEY `orderid` (`orderid`) USING BTREE,
	  KEY `ogid` (`ogid`) USING BTREE,
	  KEY `proid` (`proid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_cycle_guige` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `proid` int(11) DEFAULT NULL,
	  `name` varchar(255) DEFAULT NULL,
	  `pic` varchar(255) DEFAULT NULL,
	  `market_price` decimal(11,2) DEFAULT '0.00',
	  `cost_price` decimal(11,2) DEFAULT '0.00',
	  `sell_price` decimal(11,2) DEFAULT '0.00',
	  `weight` int(11) DEFAULT NULL,
	  `stock` int(11) unsigned DEFAULT '0',
	  `procode` varchar(255) DEFAULT NULL,
	  `sales` int(11) unsigned DEFAULT '0',
	  `ks` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `proid` (`proid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_cycle_order` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `mid` int(11) DEFAULT NULL,
	  `ordernum` varchar(255) DEFAULT NULL,
	  `buytype` tinyint(1) DEFAULT '1' COMMENT '1单买 2发团 3参团',
	  `title` text,
	  `proid` int(11) DEFAULT NULL,
	  `proname` varchar(255) DEFAULT NULL,
	  `propic` varchar(255) DEFAULT NULL,
	  `ggid` int(11) DEFAULT NULL,
	  `ggname` varchar(255) DEFAULT NULL,
	  `num` int(11) DEFAULT '1',
	  `cost_price` decimal(10,2) DEFAULT NULL,
	  `sell_price` decimal(10,2) DEFAULT NULL,
	  `totalprice` float(11,2) DEFAULT NULL,
	  `business_total_money` decimal(11,2) DEFAULT NULL,
	  `product_price` float(11,2) DEFAULT '0.00',
	  `freight_price` float(11,2) DEFAULT NULL,
	  `scoredk_money` float(11,2) DEFAULT NULL,
	  `leveldk_money` float(11,2) DEFAULT '0.00' COMMENT '会员等级优惠金额',
	  `leader_money` decimal(11,2) DEFAULT '0.00',
	  `coupon_money` decimal(11,2) DEFAULT '0.00' COMMENT '优惠券金额',
	  `coupon_rid` int(11) DEFAULT NULL COMMENT '优惠券coupon_record的id',
	  `scoredkscore` int(11) DEFAULT '0',
	  `givescore` int(11) DEFAULT '0',
	  `createtime` int(11) DEFAULT NULL,
	  `tr_roomId` int(11) NOT NULL DEFAULT '0' COMMENT '房间id',
	  `status` int(11) DEFAULT '0' COMMENT '0未支付;1已支付;2已发货,3已收货',
	  `linkman` varchar(255) DEFAULT NULL,
	  `tel` varchar(50) DEFAULT NULL,
	  `area` varchar(255) DEFAULT NULL,
	  `area2` varchar(255) DEFAULT NULL,
	  `address` varchar(255) DEFAULT NULL,
	  `longitude` varchar(100) DEFAULT NULL,
	  `latitude` varchar(100) DEFAULT NULL,
	  `message` varchar(255) DEFAULT NULL,
	  `remark` varchar(255) DEFAULT NULL,
	  `express_com` varchar(255) DEFAULT NULL,
	  `express_no` varchar(255) DEFAULT NULL,
	  `refund_reason` varchar(255) DEFAULT NULL,
	  `refund_money` decimal(11,2) DEFAULT '0.00',
	  `refund_total_money` decimal(11,2) DEFAULT '0.00' COMMENT '总申请退款金额',
	  `refund_status` int(1) DEFAULT '0' COMMENT '1申请退款审核中 2已同意退款 3已驳回',
	  `refund_time` int(11) DEFAULT NULL,
	  `refund_checkremark` varchar(255) DEFAULT NULL,
	  `payorderid` int(11) DEFAULT NULL,
	  `paytypeid` int(11) DEFAULT NULL,
	  `paytype` varchar(50) DEFAULT NULL,
	  `paynum` varchar(255) DEFAULT NULL,
	  `paytime` int(11) DEFAULT NULL,
	  `delete` int(1) DEFAULT '0',
	  `freight_id` int(11) DEFAULT NULL,
	  `freight_text` varchar(255) DEFAULT NULL,
	  `freight_type` tinyint(1) DEFAULT '0',
	  `mdid` int(11) DEFAULT NULL,
	  `freight_time` varchar(255) DEFAULT NULL,
	  `freight_content` text,
	  `send_time` bigint(20) DEFAULT NULL COMMENT '发货时间',
	  `collect_time` int(11) DEFAULT NULL COMMENT '收货时间',
	  `hexiao_code` varchar(100) DEFAULT NULL,
	  `hexiao_qr` varchar(255) DEFAULT NULL,
	  `platform` varchar(255) DEFAULT 'wx',
	  `field1` varchar(255) DEFAULT NULL,
	  `field2` varchar(255) DEFAULT NULL,
	  `field3` varchar(255) DEFAULT NULL,
	  `field4` varchar(255) DEFAULT NULL,
	  `field5` varchar(255) DEFAULT NULL,
	  `iscomment` tinyint(1) DEFAULT '0',
	  `parent1` int(11) DEFAULT NULL,
	  `parent2` int(11) DEFAULT NULL,
	  `parent3` int(11) DEFAULT NULL,
	  `parent1commission` decimal(11,2) DEFAULT '0.00',
	  `parent2commission` decimal(11,2) DEFAULT '0.00',
	  `parent3commission` decimal(11,2) DEFAULT '0.00',
	  `parent1score` int(11) DEFAULT '0',
	  `parent2score` int(11) DEFAULT '0',
	  `parent3score` int(11) DEFAULT '0',
	  `iscommission` tinyint(1) DEFAULT '0' COMMENT '佣金是否已发放',
	  `qsnum` int(11) DEFAULT '0' COMMENT '期数数量',
	  `fwtc` int(11) DEFAULT '0' COMMENT '每日配送下的配送频率1每天配送 2工作日配送 3周末配送 4隔天配送',
	  `ps_cycle` tinyint(1) DEFAULT '0' COMMENT '配送周期 1：每日一期 2：每周一期 3：每月一期',
	  `start_date` varchar(255) DEFAULT NULL COMMENT '开始日期',
	  PRIMARY KEY (`id`) USING BTREE,
	  UNIQUE KEY `hexiao_code` (`hexiao_code`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE,
	  KEY `status` (`status`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_cycle_order_stage` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT '0',
	  `bid` int(11) DEFAULT '0',
	  `mid` int(11) DEFAULT '0',
	  `ordernum` varchar(255) DEFAULT NULL COMMENT '订单号',
	  `cycle_date` varchar(50) DEFAULT NULL COMMENT '日期',
	  `cycle_strtotime` int(11) DEFAULT NULL COMMENT '日期时间戳',
	  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0:创建  1：已支付 2：已发货 3：已完成',
	  `orderid` int(11) DEFAULT '0' COMMENT '订单id',
	  `cycle_number` int(11) DEFAULT '0' COMMENT '期数',
	  `express_com` varchar(255) DEFAULT NULL COMMENT '物流公司、同城等',
	  `express_no` varchar(255) DEFAULT NULL COMMENT '单号，配送人id等',
	  `express_type` varchar(255) DEFAULT NULL COMMENT '物流类型',
	  `send_time` int(11) DEFAULT NULL COMMENT '配送、发货时间',
	  `longitude` varchar(100) DEFAULT NULL,
	  `latitude` varchar(100) DEFAULT NULL,
	  `proname` varchar(255) DEFAULT NULL,
	  `propic` varchar(255) DEFAULT NULL,
	  `ggname` varchar(255) DEFAULT NULL,
	  `num` int(11) DEFAULT '1',
	  `sell_price` decimal(10,2) DEFAULT NULL,
	  `collect_time` int(11) DEFAULT NULL COMMENT '确认收货时间',
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_cycle_product` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `cid` int(11) DEFAULT '0',
	  `name` varchar(255) DEFAULT NULL,
	  `procode` varchar(255) DEFAULT NULL,
	  `fuwupoint` varchar(255) DEFAULT NULL,
	  `sellpoint` varchar(255) DEFAULT NULL,
	  `pic` varchar(255) DEFAULT '',
	  `pics` varchar(5000) DEFAULT NULL,
	  `sales` int(11) unsigned DEFAULT '0',
	  `detail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
	  `market_price` float(11,2) DEFAULT NULL,
	  `sell_price` float(11,2) DEFAULT '0.00',
	  `cost_price` decimal(11,2) DEFAULT '0.00',
	  `weight` int(11) DEFAULT NULL,
	  `sort` int(11) DEFAULT '0',
	  `status` int(1) DEFAULT '1',
	  `stock` int(11) unsigned DEFAULT '100',
	  `createtime` int(11) DEFAULT NULL,
	  `commissionset` tinyint(1) DEFAULT '0',
	  `commission1` decimal(11,2) DEFAULT NULL,
	  `commission2` decimal(11,2) DEFAULT NULL,
	  `commission3` decimal(11,2) DEFAULT NULL,
	  `guigedata` text,
	  `comment_score` decimal(2,1) DEFAULT '5.0',
	  `comment_num` int(11) DEFAULT '0',
	  `comment_haopercent` int(11) DEFAULT '100',
	  `freighttype` tinyint(1) DEFAULT '1',
	  `freightdata` varchar(255) DEFAULT NULL,
	  `freightcontent` text,
	  `ischecked` tinyint(1) DEFAULT '1',
	  `check_reason` varchar(255) DEFAULT NULL,
	  `feepercent` decimal(5,2) DEFAULT NULL COMMENT '抽成费率',
	  `is_user_defined` tinyint(1) DEFAULT '0' COMMENT '用户自定义0：关闭 1：开启',
	  `fwtc` text COMMENT '服务套餐',
	  `ps_cycle` tinyint(1) DEFAULT NULL COMMENT '配送周期 1：每日一期 2：每周一期 3：每月一期',
	  `everyday_item` text COMMENT '每日一期 数据',
	  `advance_pay_days` int(11) DEFAULT '0' COMMENT '提前几天支付',
	  `advance_pay_time` int(11) DEFAULT '0' COMMENT '提前n天的n时支付',
	  `advance_extend_days` int(11) DEFAULT '0' COMMENT '提前n天延顺',
	  `min_num` int(11) DEFAULT '1' COMMENT '最少购买数量',
	  `min_qsnum` int(11) DEFAULT '1' COMMENT '最小购买期数',
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `cid` (`cid`) USING BTREE,
	  KEY `status` (`status`) USING BTREE,
	  KEY `ischecked` (`ischecked`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='周期购';");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_cycle_sysset` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `autoshdays` int(11) DEFAULT '7',
	  `comment` tinyint(1) DEFAULT '1',
	  `comment_check` tinyint(1) DEFAULT '1',
	  PRIMARY KEY (`id`) USING BTREE,
	  UNIQUE KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

if(!pdo_fieldexists2("ddwx_cycle_order_stage","hexiao_code")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_cycle_order_stage` ADD COLUMN `hexiao_code` varchar(100) DEFAULT '';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_cycle_order_stage` ADD COLUMN `hexiao_qr` varchar(255) DEFAULT '';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_cycle_order_stage` ADD COLUMN `remark` varchar(255) DEFAULT '';");
}
if(!pdo_fieldexists2("ddwx_membercard","custom_field_customize1_name")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_membercard` ADD COLUMN `custom_field_customize1_name` varchar(255) DEFAULT '自定义1';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_membercard` ADD COLUMN `custom_field_customize1_value` varchar(255) DEFAULT '查看';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_membercard` ADD COLUMN `custom_field_customize1_link` varchar(255) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_membercard` ADD COLUMN `custom_field_customize2_name` varchar(255) DEFAULT '自定义2';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_membercard` ADD COLUMN `custom_field_customize2_value` varchar(255) DEFAULT '查看';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_membercard` ADD COLUMN `custom_field_customize2_link` varchar(255) DEFAULT NULL;");
}

if(!pdo_fieldexists2("ddwx_coupon","showtj")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` ADD `showtj` varchar(255) DEFAULT '-1' COMMENT '显示条件 -1不限制' AFTER `tolist`;");
}
if(!pdo_fieldexists2("ddwx_coupon","usetj")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` ADD `usetj` varchar(255) DEFAULT '-1' COMMENT '使用条件 -1不限制' AFTER `showtj`;");
}

if(!pdo_fieldexists2("ddwx_yuyue_workerapply_order","refund_status")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_workerapply_order` ADD COLUMN `refund_status` int(2) DEFAULT '0';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_workerapply_order` ADD COLUMN `refund_money` float(11,2) DEFAULT '0.00';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_workerapply_order` ADD COLUMN `refund_time` int(11) DEFAULT '0';");
}

if(!pdo_fieldexists2("ddwx_admin_set","teamfenhong_differential_pj")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD `teamfenhong_differential_pj` tinyint(1) DEFAULT '0' AFTER `teamfenhong_differential`");
}
if(!pdo_fieldexists2("ddwx_membercard","remark")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_membercard` ADD `remark` varchar(255) DEFAULT NULL");
}

if(!pdo_fieldexists2("ddwx_shop_order","express_ogids")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD `express_ogids` varchar(255) DEFAULT NULL AFTER `express_no`");
}


if(!pdo_fieldexists2("ddwx_lipin","num_type")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_lipin`
MODIFY COLUMN `type` tinyint(1) NULL DEFAULT 0 COMMENT '0余额 1商品 2积分 3优惠券' AFTER `name`,
ADD COLUMN `num_type` tinyint(1) UNSIGNED NULL DEFAULT 0 AFTER `type`;");
}




if(!pdo_fieldexists2("ddwx_yuyue_product","isareaxz")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product`
		ADD COLUMN `isareaxz` tinyint(1) DEFAULT 0 COMMENT '0关闭 1开启',
		ADD COLUMN `areadata` varchar(1000) DEFAULT NULL ;");
}

if(!pdo_fieldexists2("ddwx_luntan_sysset","picurl")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_luntan_sysset` ADD `picurl` varchar(255) DEFAULT NULL AFTER `pic`");
}

if(!pdo_fieldexists2("ddwx_designer_share", "is_rootpath")) {
    \think\facade\Db::execute("ALTER TABLE ddwx_designer_share ADD `is_rootpath` tinyint(1) DEFAULT 0 COMMENT '是否过滤参数 1页面根路径忽略参数匹配';");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_mingpian` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `bgpic` varchar(255) DEFAULT NULL,
  `headimg` varchar(255) DEFAULT NULL,
  `realname` varchar(255) DEFAULT NULL,
  `touxian1` varchar(255) DEFAULT NULL,
  `touxian2` varchar(255) DEFAULT NULL,
  `touxian3` varchar(255) DEFAULT NULL,
  `tel` varchar(255) DEFAULT NULL,
  `weixin` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `douyin` varchar(255) DEFAULT NULL,
  `weibo` varchar(255) DEFAULT NULL,
  `toutiao` varchar(255) DEFAULT NULL,
  `field1` varchar(255) DEFAULT NULL,
  `field2` varchar(255) DEFAULT NULL,
  `field3` varchar(255) DEFAULT NULL,
  `field4` varchar(255) DEFAULT NULL,
  `field5` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `detail` longtext,
  `sharetitle` varchar(255) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `updatetime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `mid` (`mid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_mingpian_favorite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `mpid` int(11) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `mid` (`mid`),
  KEY `mpid` (`mpid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_mingpian_readlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `mpid` int(11) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `mid` (`mid`),
  KEY `mpid` (`mpid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_mingpian_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bgpics` text,
  `createtj` varchar(255) DEFAULT '-1',
  `field_list` varchar(2000) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

if(!pdo_fieldexists2("ddwx_mingpian", "longitude")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_mingpian ADD `longitude` varchar(255) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE ddwx_mingpian ADD `latitude` varchar(255) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_shop_order","express_isbufen")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD `express_isbufen` tinyint(1) DEFAULT '0' AFTER `express_ogids`");
}
if(!pdo_fieldexists2("ddwx_shop_category", "start_hours")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_category ADD start_hours varchar(100) DEFAULT NULL,
		ADD COLUMN `end_hours` varchar(100) DEFAULT NULL ;;");

}

if(!pdo_fieldexists2("ddwx_business_sysset", "show_product")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD COLUMN `show_product` tinyint(1) NOT NULL DEFAULT 1 COMMENT '首页是否显示本店商品0:否 1：是';");
	\think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD COLUMN `show_comment` tinyint(1) NOT NULL DEFAULT 1 COMMENT '首页是否显示店铺评价0:否 1：是';");
	\think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD COLUMN `show_detail` tinyint(1) NOT NULL DEFAULT 1 COMMENT '首页是否显示商家详情0:否 1：是';");
}

if(!pdo_fieldexists2("ddwx_business_sysset", "show_link")) {
    \think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD COLUMN `show_link` tinyint(1) NOT NULL DEFAULT 1 COMMENT '首页是否显示联系商家0:否 1：是';");
    \think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD COLUMN `show_linktext` varchar(30) NOT NULL DEFAULT '' COMMENT '联系商家文字';");
    \think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD COLUMN `show_producttext` varchar(30) NOT NULL DEFAULT '' COMMENT '本店商品文字';");
    \think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD COLUMN `show_commenttext` varchar(30) NOT NULL DEFAULT '' COMMENT '店铺评价文字';");
    \think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD COLUMN `show_detailtext` varchar(30) NOT NULL DEFAULT '' COMMENT '商家详情文字';");
}


\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_lipin_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `pid` int(11) DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `pic` varchar(255) DEFAULT NULL,
  `status` int(1) DEFAULT '1',
  `sort` int(11) DEFAULT '1',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `pid` (`pid`) USING BTREE,
  KEY `sort` (`sort`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");

if(!pdo_fieldexists2("ddwx_lipin", "cid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_lipin`
ADD COLUMN `cid` int(11) UNSIGNED NULL DEFAULT '0' AFTER `name`,
ADD INDEX(`name`),
ADD INDEX(`cid`);");
}

if(!pdo_fieldexists2("ddwx_admin_upload_group", "pid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_upload_group`
ADD COLUMN `pid` int(11) UNSIGNED NULL DEFAULT '0';");
}


if(!pdo_fieldexists2("ddwx_admin_set", "pay_transfer_check")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `pay_transfer_check` tinyint(1) NOT NULL DEFAULT 0 COMMENT '转账审核 0：关闭 1：开启';");
}


if(!pdo_fieldexists2("ddwx_kanjia_product", "helpgive_ff")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD COLUMN `helpgive_ff` tinyint(1) DEFAULT '0';");
}
if(!pdo_fieldexists2("ddwx_kanjia_order", "joinid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_order` ADD COLUMN `joinid` int(11) DEFAULT '0';");
}
if(!pdo_fieldexists2("ddwx_kanjia_product", "perhelpnum")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD COLUMN `perhelpnum` int(11) DEFAULT '5';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD COLUMN `perhelpnum_shareadd` int(11) DEFAULT '0';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD COLUMN `perhelpnum_buyadd` int(11) DEFAULT '0';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD COLUMN `payaftertourl` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD COLUMN `payafterbtntext` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD COLUMN `paygive_choujiangtimes` int(11) DEFAULT '0'");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD COLUMN `paygive_choujiangid` int(11) DEFAULT '0'");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD COLUMN `paygive_money` float(11,2) DEFAULT '0.00'");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD COLUMN `paygive_score` int(11) DEFAULT '0'");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD COLUMN `paygive_couponid` int(11) DEFAULT '0'");

	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD COLUMN `commissionset` int(2) DEFAULT '-1'");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD COLUMN `commissiondata1` text");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD COLUMN `commissiondata2` text");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD COLUMN `commissiondata3` text");

	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_order` ADD COLUMN `parent1` int(11) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_order` ADD COLUMN `parent2` int(11) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_order` ADD COLUMN `parent3` int(11) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_order` ADD COLUMN `parent1commission` decimal(11,2) DEFAULT '0.00'");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_order` ADD COLUMN `parent2commission` decimal(11,2) DEFAULT '0.00'");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_order` ADD COLUMN `parent3commission` decimal(11,2) DEFAULT '0.00'");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_order` ADD COLUMN `parent1score` int(11) DEFAULT '0'");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_order` ADD COLUMN `parent2score` int(11) DEFAULT '0'");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_order` ADD COLUMN `parent3score` int(11) DEFAULT '0'");
}
if(!pdo_fieldexists2("ddwx_choujiang_sharelog", "extratimes")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_choujiang_sharelog` ADD COLUMN `extratimes` int(11) DEFAULT '0'");
}
if(!pdo_fieldexists2("ddwx_kanjia_sysset", "mastpay")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_sysset` ADD COLUMN `mastpay` tinyint(1) DEFAULT '0'");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_kanjia_sharelog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `proid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `sharedaytimes` int(11) DEFAULT '0' COMMENT '当天分享次数',
  `sharecounttimes` int(11) DEFAULT '0' COMMENT '分享总次数',
  `updatetime` varchar(50) DEFAULT NULL COMMENT '更新时间',
  `addtimes` int(11) DEFAULT '0' COMMENT '增加的次数',
  `adddaytimes` int(11) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `proid` (`proid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `updatetime` (`updatetime`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");



if(!pdo_fieldexists2("ddwx_shop_product", "viewnum")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `viewnum` int(1) DEFAULT '0';");
}

if(!pdo_fieldexists2("ddwx_lipin_codelist", "cardno")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_lipin_codelist`
ADD COLUMN `cardno` varchar(30) NULL AFTER `hid`,
ADD COLUMN `saletime` int(11) DEFAULT NULL AFTER `createtime`,
ADD COLUMN `sale_mdid` int(11) NULL,
ADD COLUMN `hexiao_mdid` int(11) NULL,
ADD INDEX(`cardno`),
ADD INDEX(`sale_mdid`),
ADD INDEX(`hexiao_mdid`),
ADD UNIQUE INDEX(`aid`, `cardno`);");
}


if(!pdo_fieldexists2("ddwx_shop_category", "banner")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_category` ADD COLUMN `banner` varchar(255) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_shop_product", "show_recommend")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `show_recommend` tinyint(1) DEFAULT '0';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `recommend_productids` varchar(255) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_collage_product", "show_recommend")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_collage_product` ADD COLUMN `show_recommend` tinyint(1) DEFAULT '0';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_collage_product` ADD COLUMN `recommend_productids` varchar(255) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_admin_set", "ddbbtourl")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `ddbbtourl` varchar(100) DEFAULT NULL AFTER `ddbb`;");
}

if(!pdo_fieldexists2("ddwx_admin_set_sms","tmpl_recharge")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` ADD `tmpl_recharge` varchar(255) DEFAULT NULL COMMENT '充值成功';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` ADD `tmpl_recharge_st` tinyint(1) DEFAULT '1' COMMENT '充值短信是否开启';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` ADD `tmpl_sysmsg_notice` varchar(255) DEFAULT NULL COMMENT '充值成功';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` ADD `tmpl_sysmsg_notice_st` tinyint(1) DEFAULT '1' COMMENT '系统消息提醒是否开启';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` ADD `tmpl_checknotice` varchar(255) DEFAULT NULL COMMENT '待审核通知';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` ADD `tmpl_checknotice_st` tinyint(1) DEFAULT '1' COMMENT '待审核通知提醒是否开启';");
}

if(!pdo_fieldexists2("ddwx_kecheng_sysset","show_join_num")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_sysset` ADD COLUMN `show_join_num` tinyint(1) UNSIGNED NULL DEFAULT '1' COMMENT '是否显示学习人数';");
}

if(!pdo_fieldexists2("ddwx_article_set", "share_score")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_article_set` ADD COLUMN `share_score` int(11) DEFAULT '0'");
    \think\facade\Db::execute("ALTER TABLE `ddwx_article_set` ADD COLUMN `share_score_max_perday` int(11) DEFAULT '0'");
}

if(!pdo_fieldexists2("ddwx_kecheng_chapter", "jumpurl")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_chapter` ADD COLUMN `jumpurl` varchar(255) NULL COMMENT '外链';");
}

if(!pdo_fieldexists2("ddwx_admin_set", "teamfenhong_show")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
ADD COLUMN `teamfenhong_show` tinyint(1) UNSIGNED NULL DEFAULT '1' COMMENT '团队分红：1显示，0隐藏',
ADD COLUMN `commissionlog_show` tinyint(1) UNSIGNED NULL DEFAULT '1' COMMENT '佣金明细：1显示，0隐藏' AFTER `teamfenhong_show`,
ADD COLUMN `commissionrecord_show` tinyint(1) UNSIGNED NULL DEFAULT '1' COMMENT '佣金记录：1显示，0隐藏' AFTER `commissionlog_show`,
ADD COLUMN `fhorder_show` tinyint(1) UNSIGNED NULL DEFAULT '1' COMMENT '分红订单：1显示，0隐藏' AFTER `commissionrecord_show`,
ADD COLUMN `fhlog_show` tinyint(1) UNSIGNED NULL DEFAULT '1' COMMENT '分红记录：1显示，0隐藏' AFTER `fhorder_show`;");
}


if(!pdo_fieldexists2("ddwx_admin_set","google_client_id")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `google_client_id` varchar(255) DEFAULT NULL AFTER `logintype_app`;");
}
if(!pdo_fieldexists2("ddwx_kecheng_sysset","show_buyed_kecheng_price")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_sysset` ADD COLUMN `show_buyed_kecheng_price` tinyint(1) UNSIGNED NULL DEFAULT '1' COMMENT '已购课程价格显示：0已购买 1价格';");
}


if(!pdo_fieldexists2("ddwx_member","googleopenid")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `googleopenid` varchar(100) DEFAULT NULL AFTER `iosopenid`;");
}

if(!pdo_fieldexists2("ddwx_member_level","up_pro_extend_time")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_pro_extend_time` tinyint(1) UNSIGNED NULL DEFAULT '0' AFTER `up_pronum`;");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_designerpage_rwvideoad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `unitid` varchar(100) DEFAULT NULL,
  `givescore` int(11) DEFAULT NULL,
  `givemoney` decimal(11,2) DEFAULT NULL,
  `givetimes` int(11) DEFAULT '0',
  `createtime` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `unitid` (`unitid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_designerpage_rwvideoad_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `hid` int(11) DEFAULT NULL,
  `givescore` int(11) DEFAULT NULL,
  `givetimes` int(11) DEFAULT '0',
  `createdate` varchar(50) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `hid` (`hid`) USING BTREE,
  KEY `createdate` (`createdate`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

if(!pdo_fieldexists2("ddwx_signset","rewardedvideoad")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_signset` ADD COLUMN `rewardedvideoad` varchar(255) DEFAULT NULL;");
}

if(!pdo_fieldexists2("ddwx_restaurant_shop_sysset","banner_show")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_sysset` ADD COLUMN `banner_show` tinyint(1) DEFAULT 1 ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_sysset` ADD COLUMN `business_info_show` tinyint(1) DEFAULT 1 ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_sysset` ADD COLUMN `comment_show` tinyint(1) DEFAULT 1 ;");
}
if(!pdo_fieldexists2("ddwx_restaurant_takeaway_sysset","banner_show")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_sysset` ADD COLUMN `banner_show` tinyint(1) DEFAULT 1 ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_sysset` ADD COLUMN `business_info_show` tinyint(1) DEFAULT 1 ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_sysset` ADD COLUMN `comment_show` tinyint(1) DEFAULT 1 ;");
}
if(!pdo_fieldexists2("ddwx_member_level","maxnum")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `maxnum` int(11) DEFAULT '0' AFTER `discount`;");
}else{
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` MODIFY COLUMN `maxnum` int(11) NULL DEFAULT 0 AFTER `discount`;");
}
if(!pdo_fieldexists2("ddwx_payorder","issxpay")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_payorder` ADD COLUMN `issxpay` tinyint(1) DEFAULT '0';");
}

if(!pdo_fieldexists2("ddwx_shop_order","hexiao_code_member")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `hexiao_code_member` varchar(100) NULL AFTER `hexiao_qr`;");
}

if(!pdo_fieldexists2("ddwx_member_level","up_give_money")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_give_money` float(11, 2) NULL DEFAULT '0' COMMENT '升级赠送余额' AFTER `up_give_score`;");
}

if(!pdo_fieldexists2("ddwx_toupiao","gettj")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_toupiao` ADD COLUMN `gettj` varchar (255)  DEFAULT '-1' AFTER `per_allcount`;");
}

if(!pdo_fieldexists2("ddwx_admin_set","login_setnickname")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `login_setnickname` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `login_bind`;");
}

if(!pdo_fieldexists2("ddwx_shop_sysset","order_detail_toppic")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `order_detail_toppic` varchar(255) DEFAULT NULL COMMENT '订单详情顶部图片';");
}

if(!pdo_fieldexists2("ddwx_member_level","up_giveparent_levelid")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_giveparent_levelid` varchar(60) DEFAULT NULL;");
}else{
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` MODIFY COLUMN `up_giveparent_levelid` varchar(60) NULL DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_member_level","up_giveparent_levelid_p")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_giveparent_levelid_p` varchar(60) DEFAULT '0' AFTER `up_giveparent_levelid`;");
}else{
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` MODIFY COLUMN `up_giveparent_levelid_p` varchar(60) DEFAULT '0';");
}

if(!pdo_fieldexists2("ddwx_member_level","up_change_pid")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_change_pid` tinyint(1) NULL DEFAULT '0';");
}

if(!pdo_fieldexists2("ddwx_coupon","pack_coupon_ids")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` ADD COLUMN `pack_coupon_ids` varchar(255) NULL;");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_fifa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `matchId` varchar(255) DEFAULT NULL,
  `startDate` varchar(100) DEFAULT NULL,
  `startTime` varchar(100) DEFAULT NULL,
  `matchStage` varchar(100) DEFAULT NULL,
  `matchDesc` varchar(100) DEFAULT NULL,
  `matchStatus` varchar(100) DEFAULT NULL,
  `matchStatusText` varchar(100) DEFAULT NULL,
  `leftTeam_name` varchar(100) DEFAULT NULL,
  `leftTeam_logo` varchar(100) DEFAULT NULL,
  `leftTeam_score` varchar(100) DEFAULT NULL,
  `leftTeam_BigScore` varchar(100) DEFAULT NULL,
  `leftTeam_penaltyScore` varchar(100) DEFAULT NULL,
  `rightTeam_name` varchar(100) DEFAULT NULL,
  `rightTeam_logo` varchar(100) DEFAULT NULL,
  `rightTeam_score` varchar(100) DEFAULT NULL,
  `rightTeam_BigScore` varchar(100) DEFAULT NULL,
  `rightTeam_penaltyScore` varchar(100) DEFAULT NULL,
  `startTimestamp` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `startDate` (`startDate`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_fifa_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `hid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `headimg` varchar(255) DEFAULT NULL,
  `nickname` varchar(255) DEFAULT NULL,
  `guess1` varchar(100) DEFAULT NULL,
  `guess2` varchar(100) DEFAULT NULL,
  `guess1st` tinyint(1) DEFAULT '0' COMMENT '0未开奖 1正确 2错误',
  `guess2st` tinyint(1) DEFAULT '0' COMMENT '0未开奖 1正确 2错误',
  `givescore1` int(11) DEFAULT '0',
  `givescore2` int(11) DEFAULT '0',
  `createtime` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `mid` (`mid`),
  KEY `hid` (`hid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_fifa_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT '世界杯竞猜',
  `givescore1` int(11) DEFAULT '0',
  `givescore2` int(11) DEFAULT '0',
  `guess1set` text,
  `guess2set` text,
  `status` tinyint(1) DEFAULT '0',
  `guize` longtext,
  `rewardedvideoad` varchar(255) DEFAULT NULL,
  `starthour` int(11) DEFAULT '24',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `aid` (`aid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");


if(!pdo_fieldexists2("ddwx_admin_set","pid_origin")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
ADD COLUMN `pid_origin` text NULL,
ADD COLUMN `pid_new` text NULL AFTER `pid_origin`;");
}


if(!pdo_fieldexists2("ddwx_shop_product","commission_mid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `commission_mid` text NULL;");
}
if(!pdo_fieldexists2("ddwx_restaurant_takeaway_sysset","open_restaurant_detail_status")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_sysset` ADD COLUMN `open_restaurant_detail_status` tinyint(1) DEFAULT '1';");
}
if(!pdo_fieldexists2("ddwx_restaurant_shop_sysset","open_restaurant_detail_status")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_sysset`
ADD COLUMN `open_restaurant_detail_status` tinyint(1) DEFAULT '1',
ADD COLUMN `diancan_text` varchar(255) DEFAULT '点餐';");
}


if(!pdo_fieldexists2("ddwx_admin_set","pid_new_pos")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `pid_new_pos` varchar(30) NULL DEFAULT '1';");
}
if(!pdo_fieldexists2("ddwx_form","submit_tourl")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `submit_tourl` text NULL;");
}

if(!pdo_fieldexists2("ddwx_business_sysset","wxfw2_mchname")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `wxfw2_mchname` varchar(255) DEFAULT NULL AFTER `wxfw_apiclient_key`;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `wxfw2_mchid` varchar(100) DEFAULT NULL AFTER `wxfw2_mchname`;");
}

if(!pdo_fieldexists2("ddwx_wxpay_log","fenzhangmoney2")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_wxpay_log` ADD COLUMN `fenzhangmoney2` decimal(11,2) DEFAULT '0.00' AFTER `fenzhangmoney`;");
}

if(!pdo_fieldexists2("ddwx_freight","hxbids")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_freight` ADD COLUMN `hxbids` varchar(255) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_shop_product","isjici")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `isjici` tinyint(1) DEFAULT '0';");
}

if(!pdo_fieldexists2("ddwx_shop_order_goods","hexiao_code")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `hexiao_code` varchar(100) DEFAULT NULL COMMENT '唯一码 核销码';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `hexiao_qr` varchar(255) DEFAULT NULL COMMENT '核销码图片';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `hexiao_num` int(11) DEFAULT '0'");
}


if(!pdo_fieldexists2("ddwx_member_level","from_id")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `from_id` int(11) NULL;");
}
if(!pdo_fieldexists2("ddwx_shop_category","from_id")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_category` ADD COLUMN `from_id` int(11) NULL;");
}
if(!pdo_fieldexists2("ddwx_shop_fuwu","from_id")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_fuwu` ADD COLUMN `from_id` int(11) NULL;");
}
if(!pdo_fieldexists2("ddwx_shop_group","from_id")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_group` ADD COLUMN `from_id` int(11) NULL;");
}
if(!pdo_fieldexists2("ddwx_shop_param","from_id")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_param` ADD COLUMN `from_id` int(11) NULL;");
}
if(!pdo_fieldexists2("ddwx_shop_product","from_id")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `from_id` int(11) NULL;");
}
if(!pdo_fieldexists2("ddwx_shop_guige","from_id")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_guige` ADD COLUMN `from_id` int(11) NULL;");
}

if(!pdo_fieldexists2("ddwx_business_sysset","business_useplatmendian")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `business_useplatmendian` tinyint(1) DEFAULT '0'");
	\think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `business_useplatshopparam` tinyint(1) DEFAULT '0'");
}

if(!pdo_fieldexists2("ddwx_shop_sysset","cod_frontpercent")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `cod_frontpercent` decimal(10,2) DEFAULT '0.00' AFTER `codtxt`");
}
if(!pdo_fieldexists2("ddwx_cashier_order_goods","cost_price")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_cashier_order_goods` ADD COLUMN `cost_price` decimal(10,2) DEFAULT '0.00' AFTER `sell_price`");
	\think\facade\Db::execute("ALTER TABLE `ddwx_cashier_order_goods` ADD COLUMN `proid2` varchar(128) DEFAULT '' AFTER `proid`");
	\think\facade\Db::execute("update `ddwx_cashier_order_goods` set `proid2`=`proid`");
}
if(!pdo_fieldexists2("ddwx_lucky_collage_product","perlimitdan")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product` ADD COLUMN `perlimitdan` int(11) DEFAULT '0' COMMENT '每单限购';");
}
if(!pdo_fieldexists2("ddwx_member","pid_origin")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member`
ADD COLUMN `pid_origin` int(11) NULL DEFAULT NULL AFTER `pid`,
ADD INDEX(`pid_origin`);");
}

if(!pdo_fieldexists2("ddwx_business_sysset","business_selfscore")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `business_selfscore` tinyint(1) DEFAULT '0';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `score` int(11) DEFAULT '0' AFTER `money`;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `bscore` int(11) DEFAULT '0' AFTER `score`;");
}
if(!pdo_fieldexists2("ddwx_business_sysset","business_selfscore2")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `business_selfscore2` tinyint(1) DEFAULT '0';");
}
if(!pdo_fieldexists2("ddwx_business_sysset","business_selfscore_minus")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `business_selfscore_minus` tinyint(1) DEFAULT '1' COMMENT '商户积分是否可为负值';");
}

if(!pdo_fieldexists2("ddwx_business","business_selfscore_minus")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `business_selfscore_minus` tinyint(1) DEFAULT '-1';");
}
if(!pdo_fieldexists2("ddwx_admin","score")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `score` int(11) DEFAULT '0';");
}

if(!pdo_fieldexists2("ddwx_business","scoreset")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `scoreset` tinyint(1) DEFAULT '0';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `score2money` varchar(50) DEFAULT '0.01';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `scoredkmaxpercent` decimal(11,2) DEFAULT '100.00';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `scorebdkyf` tinyint(1) DEFAULT '0';");
}


\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_business_scorelog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT NULL,
  `score` int(11) DEFAULT '0',
  `after` int(11) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `remark` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_business_memberscore` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `score` int(11) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_business_member_scorelog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `score` int(11) DEFAULT '0',
  `after` int(11) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `remark` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8");


if(!pdo_fieldexists2("ddwx_lucky_collage_product","fenhongset")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product` ADD `fenhongset` int(11) DEFAULT '1' COMMENT '分红设置';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product` ADD `gdfenhongset` int(2) DEFAULT '0' COMMENT '0按会员等级 1价格比例  2固定金额 -1不参与分红';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product` ADD `gdfenhongdata1` text;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product` ADD `gdfenhongdata2` text;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product` ADD `teamfenhongset` int(2) DEFAULT '0';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product` ADD `teamfenhongdata1` text;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product` ADD `teamfenhongdata2` text;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product` ADD `areafenhongset` int(2) DEFAULT '0';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product` ADD `areafenhongdata1` text;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product` ADD `areafenhongdata2` text;");
}




if(!pdo_fieldexists2("ddwx_scoreshop_order_goods", "bid")){
	\think\facade\Db::execute("ALTER TABLE ddwx_scoreshop_order_goods ADD COLUMN `bid` int(11) DEFAULT '0' AFTER `aid`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_scoreshop_product ADD COLUMN `bid` int(11) DEFAULT '0' AFTER `aid`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_scoreshop_product ADD COLUMN `ischecked` tinyint(1) DEFAULT '1';");
	\think\facade\Db::execute("ALTER TABLE ddwx_scoreshop_product ADD COLUMN `check_reason` varchar(255) DEFAULT NULL;");
}

if(!pdo_fieldexists2("ddwx_admin_set", "fx_differential")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `fx_differential` tinyint(1) NULL DEFAULT '0' AFTER `fxjiesuantime_delaydays`;");
}


 \think\facade\Db::execute("ALTER TABLE `ddwx_shop_param` MODIFY COLUMN `cid` varchar(1000) DEFAULT '';");
if(!pdo_fieldexists2("ddwx_shop_product", "fx_differential")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `fx_differential` tinyint(1) NULL DEFAULT '-1';");
}
if(!pdo_fieldexists2("ddwx_designerpage_rwvideoad", "rad_url")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_designerpage_rwvideoad` ADD COLUMN `rad_url` varchar(255) DEFAULT NULL;");
}

if(!pdo_fieldexists2("ddwx_member","last_buytime")) {
    think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `last_buytime` int(11) NULL COMMENT '最后购买时间';");
}
if(!pdo_fieldexists2("ddwx_admin_set","partner_parent_only")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `partner_parent_only` tinyint(1) NULL DEFAULT '0';");
}


if(!pdo_fieldexists2("ddwx_manjian_set", "fwtype")){
	\think\facade\Db::execute("ALTER TABLE ddwx_manjian_set ADD COLUMN `fwtype` tinyint(1) NULL DEFAULT 0;");
}
if(!pdo_fieldexists2("ddwx_manjian_set", "categoryids")){
	\think\facade\Db::execute("ALTER TABLE ddwx_manjian_set ADD COLUMN `categoryids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_manjian_set", "productids")){
	\think\facade\Db::execute("ALTER TABLE ddwx_manjian_set ADD COLUMN `productids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;");
}
if(!pdo_fieldexists2("ddwx_manjian_set", "total_status")){
	\think\facade\Db::execute("ALTER TABLE ddwx_manjian_set ADD COLUMN `total_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '累计消费额满减 0：不开启 1：开启';");
}

\think\facade\Db::execute("ALTER TABLE `ddwx_manjian_set`
MODIFY COLUMN `categoryids` text NULL AFTER `fwtype`,
MODIFY COLUMN `productids` text NULL AFTER `categoryids`;");


if(!pdo_fieldexists2("ddwx_collage_sysset","team_refund")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_collage_sysset` ADD COLUMN `team_refund` tinyint(1) NOT NULL DEFAULT 0 COMMENT '团长发起订单退款 0:不解散团 1：解散团';");
}

if(!pdo_fieldexists2("ddwx_restaurant_takeaway_sysset","takeaway_show")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_sysset` ADD COLUMN `takeaway_show` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否显示点外卖 0:否 1：是';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_sysset` ADD COLUMN `takeaway_name` varchar(30) NOT NULL DEFAULT '点外卖' COMMENT '点外卖自定义名字';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_sysset` ADD COLUMN `business_info_name` varchar(30) NOT NULL DEFAULT '商家信息' COMMENT '商家信息自定义名字';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_sysset` ADD COLUMN `comment_name` varchar(30) NOT NULL DEFAULT '评价' COMMENT '评论自定义名字';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_sysset` ADD COLUMN `alone_hexiao_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '菜品单独核销 0:否 1：是';");
}


if(!pdo_fieldexists2("ddwx_admin_set","withdraw_desc")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `withdraw_desc` text NULL COMMENT '提现说明';");
}

 if(!pdo_fieldexists2("ddwx_admin_set","official_account_status")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `official_account_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '关注公众号组件状态';");
}
if(!pdo_fieldexists2("ddwx_cashier_order_goods","is_gj")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_cashier_order_goods` ADD COLUMN `is_gj` tinyint(1) DEFAULT '0' COMMENT '是否改过价0：未改价 1：改价';");
}
if(!pdo_fieldexists2("ddwx_lucky_collage_product","failtklx")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product` ADD COLUMN `failtklx` tinyint(1) DEFAULT '1' COMMENT '拼团失败退款路线 1原路退回 2 退回到余额';");
}

if(!pdo_fieldexists2("ddwx_member","change_pid_time")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member`
ADD COLUMN `change_pid_time` int(11) NULL DEFAULT NULL AFTER `pid_origin`,
ADD COLUMN `path_origin` text NULL AFTER `path`;");
}

if(!pdo_fieldexists2("ddwx_admin_setapp_app","androidurl")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_app` ADD COLUMN `androidurl` varchar(255) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_app` ADD COLUMN `iosurl` varchar(255) DEFAULT NULL;");
}


if(!pdo_fieldexists2("ddwx_mp_tmplset","tmpl_coupon_expire")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_mp_tmplset` ADD COLUMN `tmpl_coupon_expire` varchar(255) NULL;");
}


if(!pdo_fieldexists2("ddwx_restaurant_product","limit_takeaway")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product` ADD COLUMN `limit_takeaway` int(11) UNSIGNED NULL DEFAULT '0' COMMENT '外卖每人限购' AFTER `limit_per`;");
}


if(!pdo_fieldexists2("ddwx_business","endtime")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `endtime` bigint NULL AFTER `createtime`;");
}
if(!pdo_fieldexists2("ddwx_coupon","fwscene")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` ADD COLUMN `fwscene` tinyint(1) DEFAULT '0' COMMENT '适用场景0：所有 1:买单 2：收银台';");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_payaftergive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `gettj` varchar(255) DEFAULT '-1',
  `paygive_scene` varchar(500) DEFAULT NULL,
  `limittimes` int(11) DEFAULT '0',
  `pricestart` decimal(11,2) DEFAULT NULL,
  `priceend` decimal(11,2) DEFAULT NULL,
  `starttime` int(11) DEFAULT NULL,
  `endtime` int(11) DEFAULT NULL,
  `money` float(11,2) DEFAULT NULL,
  `score` int(11) DEFAULT '0',
  `choujiangtimes` int(11) DEFAULT NULL,
  `choujiangid` int(11) DEFAULT NULL,
  `give_coupon` tinyint(1) DEFAULT '0',
  `coupon_ids` varchar(255) DEFAULT NULL,
  `tourl` varchar(255) DEFAULT '',
  `btntext` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  `createtime` int(11) DEFAULT NULL,
  `sort` int(11) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `starttime` (`starttime`),
  KEY `endtime` (`endtime`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_payaftergive_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `hid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT '-1',
  `money` float(11,2) DEFAULT NULL,
  `score` int(11) DEFAULT '0',
  `choujiangtimes` int(11) DEFAULT NULL,
  `choujiangid` int(11) DEFAULT NULL,
  `give_coupon` tinyint(1) DEFAULT '0',
  `coupon_ids` varchar(255) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `hid` (`hid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

if(!pdo_fieldexists2("ddwx_admin_set","loading_icon")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
    ADD COLUMN `loading_style` tinyint(1) NULL DEFAULT 0 COMMENT '加载图标样式',
    ADD COLUMN `loading_icon` varchar(255) NULL DEFAULT '' COMMENT '当前加载图标';");
    //所有loading图标为空的用户
    $iconAdminSetList = \think\facade\Db::name('admin_set')->whereNull('loading_icon')->whereOr('loading_icon','=','')->field('id,aid,loading_icon')->select()->toArray();
    if($iconAdminSetList){
        $defaultIcon = ROOT_PATH."static/img/loading/1.png";
        foreach ($iconAdminSetList as $adminset){
            $setdata = [];
            $iconfile = "upload/loading/icon_".$adminset['aid'].'.png';
            $iconpath = ROOT_PATH.$iconfile;
            if(!file_exists($iconpath)){
                \app\common\File::all_copy($defaultIcon,$iconpath);
                $setdata['loading_icon'] = PRE_URL.'/'.$iconfile;
            }else{
                $setdata['loading_icon'] = PRE_URL.'/'.$iconfile;
            }
            $setdata['loading_style'] = 0;
            \think\facade\Db::name('admin_set')->where('id',$adminset['id'])->update($setdata);
        }
    }
}

if(!pdo_fieldexists2("ddwx_admin","group_id")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `group_id` int(11) UNSIGNED NULL DEFAULT '0';");
}

if(!pdo_fieldexists2("ddwx_yuyue_product","givescore")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD COLUMN `givescore` int(11) DEFAULT '0' AFTER `sell_price`;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_guige` ADD COLUMN `givescore` int(11) DEFAULT '0' AFTER `sell_price`;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` ADD COLUMN `givescore` int(11) DEFAULT '0' AFTER `product_price`;");
}


if(!pdo_fieldexists2("ddwx_scoreshop_product","guigeset")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` ADD COLUMN `guigeset` tinyint(1) DEFAULT '0' AFTER `lvprice_data`;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` ADD COLUMN `guigedata` text AFTER `guigeset`;");

	\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order_goods` ADD COLUMN `ggid` int(11) DEFAULT NULL AFTER `procode`;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order_goods` ADD COLUMN `ggname` varchar(255) DEFAULT NULL AFTER `ggid`;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_cart` ADD COLUMN `ggid` int(11) DEFAULT NULL AFTER `proid`;");
}

if(!pdo_fieldexists2("ddwx_scoreshop_cart","bid")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_cart` ADD COLUMN `bid` int(11) DEFAULT '0' AFTER `aid`;");
}


\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_scoreshop_guige` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL COMMENT '账户ID',
  `proid` int(11) DEFAULT NULL COMMENT '商品ID',
  `name` varchar(255) DEFAULT NULL COMMENT '规格名称',
  `pic` varchar(255) DEFAULT NULL COMMENT '规格图片',
  `market_price` decimal(11,2) DEFAULT '0.00' COMMENT '市场价',
  `cost_price` decimal(11,2) DEFAULT '0.00' COMMENT '成本价',
  `money_price` decimal(11,2) DEFAULT '0.00' COMMENT '销售价',
  `score_price` int(11) DEFAULT '0',
  `weight` int(11) DEFAULT NULL COMMENT '重量',
  `stock` int(11) unsigned DEFAULT '0' COMMENT '库存',
  `procode` varchar(255) DEFAULT NULL COMMENT '编码',
  `sales` int(11) DEFAULT '0' COMMENT '已售数量',
  `ks` varchar(255) DEFAULT NULL COMMENT '规格结构',
  `lvprice_data` text COMMENT '开启会员价时各个会员等级的价格数据',
  `limit_start` int(11) UNSIGNED NULL DEFAULT '0',
  `from_id` int(11) NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`) USING BTREE,
  KEY `proid` (`proid`) USING BTREE,
  KEY `from_id` (`from_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
if(!pdo_fieldexists2("ddwx_business","end_buy_status")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `end_buy_status` tinyint(1) UNSIGNED NULL DEFAULT '0' COMMENT '打烊后是否接单，1开启，0关闭' AFTER `end_hours3`;");
}

if(!pdo_fieldexists2("ddwx_plog","ip")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_plog`
ADD COLUMN `ip` varchar(60) NULL AFTER `remark`,
ADD COLUMN `province` varchar(60) NULL AFTER `ip`,
ADD COLUMN `city` varchar(60) NULL AFTER `province`,
ADD COLUMN `area` varchar(60) NULL AFTER `city`;");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_admin_setapp_cashdesk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `appid` varchar(255) DEFAULT NULL,
  `appsecret` varchar(255) DEFAULT NULL,
  `wxpay` tinyint(1) DEFAULT '1',
  `wxpay_type` tinyint(1) DEFAULT '0',
  `wxpay_sub_mchid` varchar(100) DEFAULT NULL,
  `wxpay_mchid` varchar(100) DEFAULT NULL,
  `wxpay_mchkey` varchar(100) DEFAULT NULL,
  `wxpay_apiclient_cert` varchar(100) DEFAULT NULL,
  `wxpay_apiclient_key` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");

if(!pdo_fieldexists2("ddwx_cashier","wxpay")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_cashier`
ADD COLUMN `wxpay` tinyint(1) DEFAULT '0' COMMENT '微信收款',
ADD COLUMN `cashpay` tinyint(1) DEFAULT '1' COMMENT '现金收款',
ADD COLUMN `moneypay` tinyint(1) DEFAULT '1' COMMENT '余额收款';");
}

if(!pdo_fieldexists2("ddwx_freight","minnumset")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_freight` ADD COLUMN `minnumset` tinyint(1) DEFAULT '0' AFTER `minprice`,ADD COLUMN `minnum` int(11) DEFAULT '0' AFTER `minnumset`;");
}

if(!pdo_fieldexists2("ddwx_admin_set","location_menu_list")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `location_menu_list` text NULL;");
}


if(!pdo_fieldexists2("ddwx_hexiao_order","mdid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_hexiao_order` ADD COLUMN `mdid` int(11) NULL DEFAULT 0;");
}

if(!pdo_fieldexists2("ddwx_mendian","province")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `province` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '省';");
}
if(!pdo_fieldexists2("ddwx_mendian","city")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `city` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '市';");
}

if(!pdo_fieldexists2("ddwx_mendian","district")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `district` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '区县';");
}

if(!pdo_fieldexists2("ddwx_business_sysset","scoredk_kouchu")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `scoredk_kouchu` tinyint(1) DEFAULT '0' AFTER `commission_kouchu`;");
}


if(!pdo_fieldexists2("ddwx_coupon","canused_bids")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` ADD COLUMN `canused_bids` text;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_coupon_record` ADD COLUMN `canused_bids` text;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `couponmoney` decimal(11,2) DEFAULT '0.00';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `couponwithdrawfee` decimal(11,2) DEFAULT '0.00';");
}

if(!pdo_fieldexists2("ddwx_coupon","canused_bcids")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` ADD COLUMN `canused_bcids` varchar(255) DEFAULT NULL;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_coupon_record` ADD COLUMN `canused_bcids` varchar(255) DEFAULT NULL;");
}


if(!pdo_fieldexists2("ddwx_admin_set","money_transfer_range")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
	ADD COLUMN `score_transfer_range` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `score_transfer`,
	ADD COLUMN `money_transfer_range` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `money_transfer`;");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_cashdesk","bid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_cashdesk` ADD COLUMN `bid` int(11) DEFAULT '0';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_cashdesk` DROP INDEX `aid`;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_cashdesk` ADD INDEX `aid`;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_cashdesk` ADD INDEX `bid`;");
}

if(!pdo_fieldexists2("ddwx_shop_product","commissiondata4")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `commissiondata4` text NULL AFTER `commissiondata3`;");
}

if(!pdo_fieldexists2("ddwx_member_level","fenhong_score_percent")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` 
ADD COLUMN `fenhong_score_percent` decimal(11, 2) NULL DEFAULT '0' AFTER `fenhong`,
ADD COLUMN `teamfenhong_score_percent` decimal(11, 2) NULL DEFAULT '0' AFTER `teamfenhong_money`,
ADD COLUMN `teamfenhong_pingji_score_percent` decimal(11, 2) NULL DEFAULT '0' AFTER `teamfenhong_pingji_money`,
ADD COLUMN `areafenhong_score_percent` decimal(11, 2) NULL DEFAULT '0' AFTER `areafenhong`;");
}

if(!pdo_fieldexists2("ddwx_shop_sysset","return_coupon")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `return_coupon` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1退款时自动退回优惠券，默认开启';");
}
if(!pdo_fieldexists2("ddwx_admin_set","money_transfer_pwd")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `money_transfer_pwd`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '1开启密码验证 0关闭密码验证' AFTER `money_transfer_range`;");
}


if(!pdo_fieldexists2("ddwx_coupon","rewardedvideoad")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` ADD COLUMN `rewardedvideoad` varchar(255) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_member_level","up_change_back")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level`
ADD COLUMN `up_change_back`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '升级后回归到以前的推荐人下面' AFTER `up_change_pid`;");
}

if(!pdo_fieldexists2("ddwx_admin_set","score_transfer_pwd")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
ADD COLUMN `score_transfer_pwd`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '1开启密码验证 0关闭密码验证' AFTER `score_transfer_range`;");
}


if(!pdo_fieldexists2("ddwx_member_level","commission_max")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `commission_max`  int(11) DEFAULT '0' COMMENT '会员等级奖励上限';");
}

if(!pdo_fieldexists2("ddwx_peisong_set","express_paidan")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_set` ADD COLUMN `express_paidan`  tinyint(1) DEFAULT '0' COMMENT '自动派单';");
}
if(!pdo_fieldexists2("ddwx_member","paypwd_rand")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `paypwd_rand`  varchar(255) NOT NULL DEFAULT '' COMMENT '支付密码MD5加密随机字符串';");
}

if(!pdo_fieldexists2("ddwx_business","viewnum")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `viewnum` int(11) DEFAULT '0' AFTER `sales`;");
}

if(!pdo_fieldexists2("ddwx_member","buynum")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member` 
	ADD COLUMN `buynum` int(11) DEFAULT 0,
	ADD COLUMN `buymoney` float(11,2) DEFAULT 0;");
}

if(!pdo_fieldexists2("ddwx_restaurant_product_guige","barcode")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product_guige`
        ADD COLUMN `barcode` varchar(255) DEFAULT NULL COMMENT '编码';");
}  


\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_designer_login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NULL DEFAULT NULL,
  `data` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
  `updatetime` int(11) NOT NULL DEFAULT 0,
  `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '类型',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `aid`(`aid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");

if(!pdo_fieldexists2("ddwx_cashier_order","scoredkscore")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_cashier_order` ADD COLUMN `scoredkscore` int(11) DEFAULT '0' COMMENT '积分抵扣用掉的积分';");
}
if(!pdo_fieldexists2("ddwx_shop_category2","fromid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_category2` ADD COLUMN `fromid` int(11) DEFAULT NULL;");
}

if(!pdo_fieldexists2("ddwx_admin_upload","bid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_upload` 
    ADD COLUMN `bid` int(11) NULL DEFAULT '0' AFTER `aid`,
    ADD COLUMN `hash` varchar(255) DEFAULT NULL,
    ADD INDEX(`bid`);");
}
if(!pdo_fieldexists2("ddwx_admin_set","file_image_limit")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` 
ADD COLUMN `file_image_limit` int(11) NULL DEFAULT 0,
ADD COLUMN `file_video_limit` int(11) NULL DEFAULT 0,
ADD COLUMN `file_other_limit` int(11) NULL DEFAULT 0;");
}

if(!pdo_fieldexists2("ddwx_admin","file_image_total")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin` 
ADD COLUMN `file_image_total` bigint(20) NULL DEFAULT 0,
ADD COLUMN `file_video_total` bigint(20) NULL DEFAULT 0,
ADD COLUMN `file_other_total` bigint(20) NULL DEFAULT 0,
ADD COLUMN `file_upload_total` bigint(20) NULL DEFAULT 0,
ADD COLUMN `file_upload_limit` bigint(20) NULL DEFAULT 0;");
}

if(!pdo_fieldexists2("ddwx_business","file_image_total")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business` 
ADD COLUMN `file_image_total` bigint(20) NULL DEFAULT 0,
ADD COLUMN `file_video_total` bigint(20) NULL DEFAULT 0,
ADD COLUMN `file_other_total` bigint(20) NULL DEFAULT 0,
ADD COLUMN `file_upload_total` bigint(20) NULL DEFAULT 0;");
}

if(pdo_fieldexists2("ddwx_admin","file_image_total")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin` MODIFY COLUMN `file_image_total` bigint(20) NULL DEFAULT 0;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin` MODIFY COLUMN `file_video_total` bigint(20) NULL DEFAULT 0;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin` MODIFY COLUMN `file_other_total` bigint(20) NULL DEFAULT 0;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin` MODIFY COLUMN `file_upload_total` bigint(20) NULL DEFAULT 0;");

    \think\facade\Db::execute("ALTER TABLE `ddwx_business` MODIFY COLUMN `file_image_total` bigint(20) NULL DEFAULT 0;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_business` MODIFY COLUMN `file_video_total` bigint(20) NULL DEFAULT 0;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_business` MODIFY COLUMN `file_other_total` bigint(20) NULL DEFAULT 0;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_business` MODIFY COLUMN `file_upload_total` bigint(20) NULL DEFAULT 0;");
}

if (!pdo_fieldexists2("ddwx_admin_setapp_h5", "alipay_type")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_h5` ADD COLUMN `alipay_type` tinyint(1) DEFAULT '0' COMMENT '支付宝支付模式0：普通模式 1:adapay';");
}


if(!pdo_fieldexists2("ddwx_business_sysset","business_cashdesk_alipay_type")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset`
        ADD COLUMN `business_cashdesk_alipay_type` tinyint(1) DEFAULT '2' COMMENT '多商户收银台 支付宝 0：关闭 2平台收款 3：独立收款 ',
        ADD COLUMN `business_cashdesk_wxpay_type` tinyint(1) DEFAULT '2' COMMENT '多商户收银台 微信  0：关闭 1：服务商 2平台收款 3：独立收款 ',
        ADD COLUMN `business_cashdesk_sxpay_type` tinyint(1) DEFAULT '2' COMMENT '多商户收银台 随行付  0：关闭 1：服务商 2平台收款 3：独立收款 ',
        ADD COLUMN `business_cashdesk_yue` tinyint(1) DEFAULT '1' COMMENT '多商户收银台 余额   0:关闭 1：开启',
        ADD COLUMN `business_cashdesk_cashpay` tinyint(1) DEFAULT '1' COMMENT '多商户收银台 现金   0:关闭 1：开启';");
}

if(!pdo_fieldexists2("ddwx_admin_setapp_cashdesk","sxpay_sub_mno")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_cashdesk`
        ADD COLUMN `sxpay_sub_mno` varchar(255) DEFAULT NULL COMMENT '随行付商户号 服务商',
        ADD COLUMN `sxpay_sub_mchkey` varchar(255) DEFAULT NULL COMMENT '随行付秘钥 服务商';");
}

if (!pdo_fieldexists2("ddwx_business_moneylog", "type")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_moneylog` ADD COLUMN `type` varchar(255) DEFAULT NULL;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_moneylog` ADD COLUMN `ordernum` varchar(255) DEFAULT NULL;");
}


if (!pdo_fieldexists2("ddwx_lipin", "prodata4")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_lipin` ADD COLUMN `prodata4` text AFTER `prodata`;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_lipin` ADD COLUMN `num_type4` tinyint(1) UNSIGNED NULL DEFAULT '0' AFTER `prodata4`;");
}

if (!pdo_fieldexists2("ddwx_scoreshop_product", "showtj")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` ADD COLUMN  `showtj` varchar(255) DEFAULT '-1';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` ADD COLUMN  `gettj` varchar(255) DEFAULT '-1';");
}

if (!pdo_fieldexists2("ddwx_headimg_upload", "bid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_headimg_upload` ADD COLUMN  `bid` int(11) DEFAULT NULL;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_headimg_upload` ADD COLUMN  `hash` varchar(255) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_designerpage_rwvideoad","givemoney")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_designerpage_rwvideoad` ADD COLUMN `givemoney` decimal(11,2) DEFAULT '0.00' AFTER `givescore`;");
}
if(!pdo_fieldexists2("ddwx_designerpage_rwvideoad_record","givemoney")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_designerpage_rwvideoad_record` ADD COLUMN `givemoney` decimal(11,2) DEFAULT '0.00' AFTER `givescore`;");
}
if(!pdo_fieldexists2("ddwx_sendredpack_log","platform")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_sendredpack_log` ADD COLUMN `platform` varchar(60) NULL;");
}

if(!pdo_fieldexists2("ddwx_member_scorelog", "bid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_scorelog` ADD COLUMN `bid` int(11) NULL AFTER `aid`;");
}

if(!pdo_fieldexists2("ddwx_designerpage_rwvideoad","givemoneyparent")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_designerpage_rwvideoad` ADD COLUMN `type` tinyint(1) DEFAULT '0';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_designerpage_rwvideoad` ADD COLUMN `givemoneyparent` decimal(11,2) DEFAULT '0.00' AFTER `givemoney`;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_designerpage_rwvideoad` ADD COLUMN `givetimestotal` int(11) DEFAULT '999' AFTER `givetimes`;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_designerpage_rwvideoad_record` ADD COLUMN `givemoneyparent` decimal(11,2) DEFAULT '0.00' AFTER `givemoney`;");
}
if(!pdo_fieldexists2("ddwx_wifiprint_set", "width")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_wifiprint_set` 
    ADD COLUMN `width` int(4) NULL DEFAULT '40',
    ADD COLUMN `height` int(4) NULL DEFAULT '30' AFTER `width`;");
}


if(!pdo_fieldexists2("ddwx_sxpay_income", "mid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_sxpay_income` ADD COLUMN `mid`  int(11) NULL DEFAULT 0 COMMENT '申请人';");
}


if(!pdo_fieldexists2("ddwx_business_sysset","leveldk_kouchu")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `leveldk_kouchu` tinyint(1) DEFAULT '0' AFTER `scoredk_kouchu`;");
}


if(!pdo_fieldexists2("ddwx_admin_set", "score_from_moneypay")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `score_from_moneypay` tinyint(1) NULL DEFAULT '0' COMMENT '余额支付送积分0不送，1送' AFTER `score_to_money_percent`;");
}
if(!pdo_fieldexists2("ddwx_payaftergive", "bid")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_payaftergive` ADD COLUMN `bid`  int(11) NULL DEFAULT 0;");
}

if (!pdo_fieldexists2("ddwx_member_scorelog", "expire_time")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_scorelog` 
    ADD COLUMN `used` int(11) NULL DEFAULT '0' COMMENT '使用积分' AFTER `after`,
    ADD COLUMN `expire_time` int(11) NULL AFTER `createtime`,
    ADD COLUMN `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0默认，-1过期，1全部已使用',
    ADD COLUMN `is_cancel` tinyint(1) NULL DEFAULT 0 AFTER `status`,
    ADD INDEX(`status`);");
}

if(!pdo_fieldexists2("ddwx_member_moneylog","from_mid")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_moneylog` ADD COLUMN `from_mid`  int(11) NULL DEFAULT 0;");
}
if(!pdo_fieldexists2("ddwx_member_scorelog","from_mid")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_scorelog` ADD COLUMN `from_mid`  int(11) NULL DEFAULT 0;");
}


if(!pdo_fieldexists2("ddwx_business", "tourl")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `tourl`  varchar(255) DEFAULT NULL;");
}

if(!pdo_fieldexists2("ddwx_wxpay_log", "is_upload_shipping_info")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_wxpay_log` ADD COLUMN `is_upload_shipping_info` tinyint(1) NULL DEFAULT '0' COMMENT '是否录入小程序发货信息';");
}

if(!pdo_fieldexists2("ddwx_shop_sysset", "refundpic")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `refundpic` tinyint(1) NOT NULL DEFAULT 0 COMMENT '退款图片 0：选填 1：必填 选择必填后无需退货必上传' ;");
}

if(!pdo_fieldexists2("ddwx_admin_set","pics")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`  ADD COLUMN `pics` text;");
}
if(!pdo_fieldexists2("ddwx_shop_order_goods", "gtype")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `gtype`  tinyint(4) NULL DEFAULT 0 COMMENT '1 赠送商品';");
}

if(!pdo_fieldexists2("ddwx_yuyue_guige", "cost_price")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_guige` ADD COLUMN `cost_price` decimal(11,2) DEFAULT '0.00'");
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` ADD COLUMN `cost_price` decimal(11,2) DEFAULT '0.00'");
}
if(!pdo_fieldexists2("ddwx_shop_sysset","fastbuy_toppic")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `fastbuy_toppic` varchar(255) DEFAULT NULL COMMENT '快速购买顶部图';");
}


if(!pdo_fieldexists2("ddwx_shop_product","product_type")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `product_type` tinyint(1) DEFAULT '0' COMMENT '商品类型 0 普通商品 1眼镜商品';");
}
if(!pdo_fieldexists2("ddwx_admin_set","maidan_login")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `maidan_login`  tinyint(1) NOT NULL DEFAULT 1 COMMENT '买单收款页是否强制登录 1是 0否';");
}
if(!pdo_fieldexists3('ddwx_business_sales')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_business_sales` (
        `id`  int(11) NOT NULL AUTO_INCREMENT ,
        `aid`  int(11) NOT NULL DEFAULT 0 ,
        `bid`  int(11) NOT NULL DEFAULT 0 ,
        `sales`  int(11) NOT NULL DEFAULT 0 COMMENT '虚拟销量' ,
        `shop_sales`  int(11) NOT NULL DEFAULT 0 COMMENT '商城产品销量' ,
        `collage_sales`  int(11) NOT NULL DEFAULT 0 COMMENT '拼团销量' ,
        `kanjia_sales`  int(11) NOT NULL DEFAULT 0 COMMENT '砍价销量' ,
        `seckill_sales`  int(11) NOT NULL DEFAULT 0 COMMENT '秒杀销量' ,
        `tuangou_sales`  int(11) NOT NULL DEFAULT 0 COMMENT '团购销量' ,
        `scoreshop_sales`  int(11) NOT NULL DEFAULT 0 COMMENT '积分商城销量' ,
        `lucky_collage_sales`  int(11) NOT NULL DEFAULT 0 COMMENT '幸运拼团销量' ,
        `total_sales`  int(11) NOT NULL DEFAULT 0 COMMENT '总销量' ,
        `yuyue_sales` int(11) NOT NULL DEFAULT '0' COMMENT '预约订单销量',
        `kecheng_sales` int(11) NOT NULL DEFAULT '0' COMMENT '课程订单销量',
        `cycle_sales` int(11) NOT NULL DEFAULT '0' COMMENT '周期购销量',
        `restaurant_takeaway_sales` int(11) NOT NULL DEFAULT '0' COMMENT '餐饮外卖销量',
        `restaurant_shop_sales` int(11) NOT NULL DEFAULT '0' COMMENT '餐饮点餐销量',
        `maidan_sales` int(11) NOT NULL DEFAULT '0' COMMENT '买单销量',
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB
        DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");

    //首次更新，统计商品销量
    curl_get(PRE_URL.'/?s=/ApiAuto/countSales');
}



if(!pdo_fieldexists2("ddwx_shop_order_goods","remark")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `remark` varchar(255) DEFAULT '';");
}
if(!pdo_fieldexists2("ddwx_member","alipayopenid_new")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `alipayopenid_new` varchar(100) DEFAULT NULL COMMENT '支付宝openid 新规则';");
}
//兼容富文本填写emoj表情
\think\facade\Db::execute("ALTER TABLE `ddwx_article` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_article` MODIFY COLUMN `content`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_designerpage` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_designerpage` MODIFY COLUMN `pageinfo`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_designerpage` MODIFY COLUMN `content`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_admin_notice` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_admin_notice` MODIFY COLUMN `content`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");

\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` MODIFY COLUMN `detail`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_collage_product` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_collage_product` MODIFY COLUMN `detail`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` MODIFY COLUMN `detail`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_seckill_product` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_seckill_product` MODIFY COLUMN `detail`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_tuangou_product` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_tuangou_product` MODIFY COLUMN `detail`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` MODIFY COLUMN `detail`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_luntan` MODIFY COLUMN `content`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");

\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product` MODIFY COLUMN `detail`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");


\think\facade\Db::execute("ALTER TABLE `ddwx_signset` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_signset` MODIFY COLUMN `guize`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` MODIFY COLUMN `detail`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_list` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_list` MODIFY COLUMN `detail`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_toupiao` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_toupiao` MODIFY COLUMN `guize`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");

\think\facade\Db::execute("ALTER TABLE `ddwx_cycle_product` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_cycle_product` MODIFY COLUMN `detail`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` MODIFY COLUMN `explain`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL AFTER `score3`;");

if(!pdo_fieldexists2("ddwx_business_sales","yuyue_sales")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_sales` ADD COLUMN `yuyue_sales`  int(11) NOT NULL DEFAULT 0 COMMENT '预约订单销量';");
}
if(!pdo_fieldexists2("ddwx_business_sales","kecheng_sales")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_sales` ADD COLUMN `kecheng_sales`  int(11) NOT NULL DEFAULT 0 COMMENT '课程订单销量';");
}
if(!pdo_fieldexists2("ddwx_business_sales","cycle_sales")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_sales` ADD COLUMN `cycle_sales`  int(11) NOT NULL DEFAULT 0 COMMENT '周期购销量';");
}
if(!pdo_fieldexists2("ddwx_business_sales","restaurant_takeaway_sales")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_sales` ADD COLUMN `restaurant_takeaway_sales`  int(11) NOT NULL DEFAULT 0 COMMENT '餐饮外卖销量';");
}
if(!pdo_fieldexists2("ddwx_business_sales","restaurant_shop_sales")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_sales` ADD COLUMN `restaurant_shop_sales`  int(11) NOT NULL DEFAULT 0 COMMENT '餐饮点餐销量';");
}


if(pdo_fieldexists2("ddwx_shop_product","pics")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` MODIFY COLUMN `pics` text CHARACTER SET utf8;");
}
if(pdo_fieldexists2("ddwx_shop_product","diypics")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` MODIFY COLUMN `diypics` text CHARACTER SET utf8;");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_mp_tmplset_new` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `tmpl_orderconfirm` varchar(64) DEFAULT NULL,
  `tmpl_orderpay` varchar(64) DEFAULT NULL,
  `tmpl_orderfahuo` varchar(64) DEFAULT NULL,
  `tmpl_ordershouhuo` varchar(64) DEFAULT NULL,
  `tmpl_ordertui` varchar(64) DEFAULT NULL,
  `tmpl_tuisuccess` varchar(64) DEFAULT NULL,
  `tmpl_tuierror` varchar(64) DEFAULT NULL,
  `tmpl_withdraw` varchar(64) DEFAULT NULL,
  `tmpl_tixiansuccess` varchar(64) DEFAULT NULL,
  `tmpl_tixianerror` varchar(64) DEFAULT NULL,
  `tmpl_collagesuccess` varchar(64) DEFAULT NULL,
  `tmpl_formsub` varchar(64) DEFAULT NULL,
  `tmpl_kehuzixun` varchar(64) DEFAULT NULL,
  `tmpl_fenxiaosuccess` varchar(64) DEFAULT NULL,
  `tmpl_fuwudaoqi` varchar(64) DEFAULT NULL,
  `tmpl_joinin` varchar(64) DEFAULT NULL,
  `tmpl_peisongorder` varchar(64) DEFAULT NULL,
  `tmpl_uplv` varchar(64) DEFAULT NULL COMMENT '会员升级通知',
  `tmpl_moneychange` varchar(64) DEFAULT NULL COMMENT '余额变动提示',
  `tmpl_restaurant_booking` varchar(64) DEFAULT NULL,
  `tmpl_shenhe` varchar(64) DEFAULT NULL COMMENT '审核结果通知',
  `tmpl_prize` varchar(64) DEFAULT NULL COMMENT '抽奖结果通知',
  `tmpl_fenhong` varchar(64) DEFAULT '',
  `tmpl_joinfree` varchar(64) NOT NULL DEFAULT '' COMMENT '加入免单通知',
  `tmpl_zhaopin_notice` varchar(64) DEFAULT '',
  `tmpl_register` varchar(64) DEFAULT NULL,
  `tmpl_coupon_expire` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `aid` (`aid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");

if(!pdo_fieldexists2("ddwx_admin_set","withdrawmax")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `withdrawmax` int(11) NOT NULL DEFAULT 0 COMMENT '余额提现最高金额';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `comwithdrawmax` int(11) NOT NULL DEFAULT 0 COMMENT '佣金提现最高金额';");
}


if(pdo_fieldexists2("ddwx_form_order", "form0")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form0` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form1` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form2` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form3` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form4` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form5` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form6` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form7` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form8` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form9` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form10` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form11` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form12` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form13` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form14` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form15` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form16` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form17` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form18` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form19` text ;");

	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form20` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form21` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form22` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form23` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form24` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form25` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form26` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form27` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form28` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form29` text ;");

	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form30` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form31` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form32` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form33` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form34` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form35` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form36` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form37` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form38` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form39` text ;");

	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form40` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form41` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form42` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form43` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form44` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form45` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form46` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form47` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form48` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form49` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form50` text ;");
}
if(pdo_fieldexists2("ddwx_form_order", "form51")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form51` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form52` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form53` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form54` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form55` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form56` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form57` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form58` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form59` text ;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` MODIFY COLUMN `form60` text ;");
}


\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_wx_upload_shipping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `wxpaylogid` int(11) DEFAULT NULL,
  `postdata` text DEFAULT NULL,
  `openid` varchar(255) DEFAULT NULL,
  `tablename` varchar(255) DEFAULT NULL,
  `ordernum` varchar(255) DEFAULT NULL,
  `mch_id` varchar(100) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `nexttime` int(11) DEFAULT NULL COMMENT '下次运行时间',
  `times_failed` tinyint(3) DEFAULT '0' COMMENT '失败次数',
  `status` tinyint(3) DEFAULT '0' COMMENT '0默认，1成功，-1失败 记录最后一次',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `nexttime` (`nexttime`) USING BTREE,
  KEY `times_failed` (`times_failed`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT '微信小程序录入发货信息';");


if(!pdo_fieldexists2("ddwx_choujiang","qrcode")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_choujiang` 
    ADD COLUMN `qrcode` varchar(255) NULL COMMENT '弹出二维码',
    ADD COLUMN `qrcode_tip` varchar(255) NULL COMMENT '弹出二维码文字';");
}

if(!pdo_fieldexists2("ddwx_member_moneylog","paytype")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_moneylog` ADD COLUMN `paytype` varchar(60) NULL;");
}

if(!pdo_fieldexists2("ddwx_wifiprint_set", "print_maidan")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_wifiprint_set` ADD COLUMN `print_maidan` tinyint(1) DEFAULT '1' COMMENT '买单打印';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_wifiprint_set` ADD COLUMN `print_cashdesk` tinyint(1) DEFAULT '1' COMMENT '收银台打印';");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_designer_mobile` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `data` text,
  `updatetime` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");


if(!pdo_fieldexists2("ddwx_sxpay_fenzhang", "account_ratio")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_sxpay_fenzhang`
ADD COLUMN `account_ratio` tinyint(3) NULL COMMENT '最大分账比例，1~100的整数',
ADD COLUMN `agreement_pic_str` text NULL COMMENT '分账情况说明函,多图,间隔',
ADD COLUMN `scenes_pic_str` text NULL COMMENT '分账场景图片，多图,间隔';");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_sxpay_special_apply` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `fenzhang_id` int(11) DEFAULT NULL,
  `business_code` varchar(100) DEFAULT NULL,
  `type` tinyint(3) DEFAULT '2' COMMENT '申请类型，枚举：1 分时结算申请,2 订单分账申请',
  `account_ratio` tinyint(3) DEFAULT NULL COMMENT '最大分账比例，1~100的整数',
  `split_accounts` text COMMENT '分账接收账户',
  `agreement_pic_str` text COMMENT '分账情况说明函,多图,间隔',
  `scenes_pic_str` text COMMENT '分账场景图片，多图,间隔',
  `other_pic_str` text COMMENT '其他图片，多图,间隔',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `mid` int(11) DEFAULT '0' COMMENT '申请人',
  `respid` varchar(100) DEFAULT NULL COMMENT '返回请求id',
  `status` tinyint(3) DEFAULT '0' COMMENT '00 申请审核中,01 申请通过,02 申请驳回,03 申请取消',
  `resp` text,
  `resp_explain` varchar(100) DEFAULT '0' COMMENT '处理说明',
  `resp_account_ratio` tinyint(3) DEFAULT NULL COMMENT '最大分账比例，1~100的整数',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`) USING BTREE,
  KEY `fenzhang_id` (`fenzhang_id`),
  KEY `business_code` (`business_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");


if(!pdo_fieldexists2("ddwx_member_levelup_order", "pid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_levelup_order` ADD COLUMN `pid` int(11) NULL;");
}

if(!pdo_fieldexists2("ddwx_member", "realname_status")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `realname_status` tinyint(1) NULL DEFAULT '0' AFTER `realname`;");
}
if(!pdo_fieldexists2("ddwx_member_commissionlog", "service_fee")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_commissionlog` ADD COLUMN `service_fee` decimal(17, 6) NULL DEFAULT '0';");
}

if(!pdo_fieldexists2("ddwx_member_commissionlog","fhtype")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_commissionlog`
        ADD COLUMN `fhtype`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分红类型';");
}
if (!pdo_fieldexists2("ddwx_peisong_order", "mdid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_order` ADD COLUMN `mdid` int(11) NULL COMMENT '门店id' AFTER `bid`;");
}

if (!pdo_fieldexists2("ddwx_shop_order", "discount_money_admin")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `discount_money_admin` decimal(11, 2) NULL DEFAULT '0' COMMENT '管理员优惠金额' AFTER `coupon_rid`;");
}


    if (!pdo_fieldexists2("ddwx_cashback", "receiver_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashback` ADD COLUMN `receiver_type`  tinyint(1) NOT NULL DEFAULT 1 COMMENT '购物返现 返现佣金受益人 （1自己（默认）、2参与活动的所有人';");
    }
    if (!pdo_fieldexists2("ddwx_cashback", "goods_multiple_max")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashback` ADD COLUMN `goods_multiple_max`   decimal(11, 2) NULL DEFAULT '1' COMMENT '返现额度倍数 商品售价的N倍（满额停止返现）0或空 不限制';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_cashback_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT '0',
  `mid` int(11) NOT NULL DEFAULT '0' COMMENT '会员id',
  `cashback_id` int(11) NOT NULL DEFAULT '0' COMMENT '购物返现id',
  `pro_id` int(11) DEFAULT NULL COMMENT '商品id',
  `pro_num` int(11) DEFAULT NULL COMMENT '商品数量',
  `cashback_money_max` decimal(11,2) DEFAULT NULL COMMENT '购物返现的限额',
  `cashback_money` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '购物返现额度',
  `score` int(11) DEFAULT '0' COMMENT '返现积分',
  `commission` decimal(11,2) DEFAULT '0.00' COMMENT '返现佣金',
  `back_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '购物返现类型',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0',
  `type` varchar(30) NOT NULL DEFAULT 'shop' COMMENT '订单类型',
  PRIMARY KEY (`id`),
  KEY `cashback_id` (`cashback_id`),
  KEY `mid` (`mid`),
  KEY `aid` (`aid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='购物返现活动参与人表';");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_cashback_member_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT '0',
  `mid` int(11) NOT NULL DEFAULT '0' COMMENT '会员id',
  `cashback_id` int(11) NOT NULL DEFAULT '0' COMMENT '购物返现id',
  `pro_id` int(11) DEFAULT NULL COMMENT '商品id',
  `cashback_money` decimal(11,2) DEFAULT '0.00' COMMENT '购物返现额度',
  `score` int(11) DEFAULT '0' COMMENT '返现积分',
  `commission` decimal(11,2) DEFAULT '0.00' COMMENT '返现佣金',
  `back_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '购物返现类型',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0',
  `type` varchar(30) NOT NULL DEFAULT 'shop' COMMENT '订单类型',
  PRIMARY KEY (`id`),
  KEY `cashback_id` (`cashback_id`),
  KEY `mid` (`mid`),
  KEY `aid` (`aid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='购物返现活动参与人表';");

if (!pdo_fieldexists2("ddwx_admin_user", "tmpl_maidanpay")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_user` ADD COLUMN `tmpl_maidanpay` tinyint(1) NULL DEFAULT '1' AFTER `tmpl_restaurant_booking`;");
}
if (!pdo_fieldexists2("ddwx_mp_tmplset_new", "tmpl_maidanpay")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_mp_tmplset_new` ADD COLUMN `tmpl_maidanpay` varchar(64) NULL COMMENT '买单付款通知';");
}

\think\facade\Db::execute("ALTER TABLE `ddwx_admin` MODIFY COLUMN `endtime` bigint(20) NULL DEFAULT NULL;");

if(!pdo_fieldexists2("ddwx_member_level","up_buygoods_condition")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `up_buygoods_condition` varchar(20) CHARACTER SET utf8 NOT NULL DEFAULT 'or' COMMENT '购买商品条件 or或，and且' AFTER `up_pronum`;;");
}


\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` MODIFY COLUMN `textset` text NULL;");


if(!pdo_fieldexists2("ddwx_admin_set","fxorder_show")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD  `fxorder_show` tinyint(1) NOT NULL DEFAULT 1 COMMENT '我的佣金页面是否显示分销订单';");
}
if(!pdo_fieldexists2("ddwx_admin_set","commissionrecord_withdrawlog_show")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD  `commissionrecord_withdrawlog_show` tinyint(1) NOT NULL DEFAULT 1 COMMENT '前端佣金提现页面是否显示佣金提现记录';");
}
if(!pdo_fieldexists2("ddwx_cashier","jiaoban_print_ids")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_cashier` ADD `jiaoban_print_ids` varchar(255) DEFAULT NULL COMMENT '交班打印机';");
}
if(!pdo_fieldexists2("ddwx_restaurant_shop_order","cuxiao_money")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order` ADD `cuxiao_money` decimal(10,2) DEFAULT '0.00';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order` ADD `cuxiao_ids` varchar(255) DEFAULT NULL;");
}

if(!pdo_fieldexists2("ddwx_shop_refund_order", "express_content")) {
  \think\facade\Db::execute("ALTER TABLE `ddwx_shop_refund_order` ADD COLUMN `express_content` text NULL COMMENT '多个快递单号时的快递单号数据';");
}


if(!pdo_fieldexists2("ddwx_admin_set","province2")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` 
    	ADD COLUMN `province2` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货省市',
		ADD COLUMN `city2` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货市',
		ADD COLUMN `district2` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货区域',
		ADD COLUMN `address2` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货详细地址';");
}

if(!pdo_fieldexists2("ddwx_shop_sysset","return_province")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` 
    	ADD COLUMN `return_province` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货省市',
		ADD COLUMN `return_city` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货市',
		ADD COLUMN `return_area` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货区域',
		ADD COLUMN `return_address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货详细地址',
		ADD COLUMN `return_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货联系人' AFTER `return_address`,
		ADD COLUMN `return_tel` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货联系电话' AFTER `return_name`;");
}

if(!pdo_fieldexists2("ddwx_business","return_province")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business` 
    	ADD COLUMN `return_province` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货省市',
		ADD COLUMN `return_city` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货市',
		ADD COLUMN `return_area` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货区域',
		ADD COLUMN `return_address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货详细地址',
		ADD COLUMN `return_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货联系人' AFTER `return_address`,
		ADD COLUMN `return_tel` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货联系电话' AFTER `return_name`;");
}

if(!pdo_fieldexists2("ddwx_shop_refund_order","return_province")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_refund_order` 
    	ADD COLUMN `return_province` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货省市',
		ADD COLUMN `return_city` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货市',
		ADD COLUMN `return_area` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货区域',
		ADD COLUMN `return_address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货详细地址',
		ADD COLUMN `return_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货联系人' AFTER `return_address`,
		ADD COLUMN `return_tel` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '退货联系电话' AFTER `return_name`;");
}

if(!pdo_fieldexists2("ddwx_shop_refund_order","isexpress")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_refund_order` 
    	ADD COLUMN `isexpress` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否发送快递 0： 否 1：是' ,
		ADD COLUMN `expresstime` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送时间';");
}

if(!pdo_fieldexists2("ddwx_member","usercard_begin_date")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member` 
ADD COLUMN `usercard_begin_date` date NULL DEFAULT NULL AFTER `usercard`,
ADD COLUMN `usercard_end_date` date NULL DEFAULT NULL AFTER `usercard_begin_date`,
ADD COLUMN `usercard_date_type` tinyint(1) NULL DEFAULT '0' COMMENT '0默认 1长期' AFTER `usercard_end_date`,
ADD COLUMN `bank_province` varchar(30) DEFAULT NULL AFTER `bankcarduser`,
ADD COLUMN `bank_province_code` varchar(10) DEFAULT NULL AFTER `bank_province`,
ADD COLUMN `bank_city` varchar(30) DEFAULT NULL AFTER `bank_province_code`,
ADD COLUMN `bank_city_code` varchar(10) DEFAULT NULL AFTER `bank_city`;");
}
if(pdo_fieldexists2("ddwx_admin","score")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin` MODIFY COLUMN `score` bigint(20) NULL DEFAULT 0;");
}
if(!pdo_fieldexists2("ddwx_member","totalscore")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `totalscore` int(11) NOT NULL DEFAULT 0 COMMENT '累计积分';");
}
if(!pdo_fieldexists2("ddwx_member","iscountscore")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `iscountscore` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否统计过累计积分 0 否 1 是';");
}


if(!pdo_fieldexists2("ddwx_open_app","status")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_open_app` ADD COLUMN `status` tinyint(1) NULL DEFAULT '0' COMMENT '0关闭，1开启';");
}
if(!pdo_fieldexists2("ddwx_admin_set","maidan_getlocation")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `maidan_getlocation`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '买单开启定位 0关闭 1开启';");
}

if(!pdo_fieldexists2("ddwx_member_level","up_pro_keep_time")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_pro_keep_time` tinyint(1) UNSIGNED NULL DEFAULT '0' AFTER `up_pro_extend_time`;");
}
//检查表是否存在
if(!pdo_fieldexists3("ddwx_designer_shopdetail") || (pdo_fieldexists3("ddwx_designer_shopdetail") && !pdo_fieldexists2("ddwx_designer_shopdetail","bid"))){
\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_designer_shopdetail` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `aid` int(11) DEFAULT NULL,
    `indexurl` varchar(255) DEFAULT '/pages/index/index',
    `menucount` int(11) DEFAULT NULL,
    `menudata` text,
    `navigationBarBackgroundColor` varchar(255) DEFAULT NULL,
    `navigationBarTextStyle` varchar(255) DEFAULT NULL,
    `updatetime` int(11) DEFAULT NULL,
    `platform` varchar(11) DEFAULT 'mp',
    `tongbu` tinyint(1) DEFAULT '1',
    PRIMARY KEY (`id`),
    KEY `aid` (`aid`) USING BTREE,
    KEY `platform` (`platform`) USING BTREE
  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='详情页导航栏调整';");

  \think\facade\Db::execute("ALTER TABLE `ddwx_designer_shopdetail` ADD COLUMN `bid` int(11)  DEFAULT '0' AFTER `aid` ;");
  if(pdo_fieldexists3("ddwx_designer_shopdetail")){
      $AdminList = \think\facade\Db::name('admin')->field('id')->select()->toArray();
      if($AdminList){
          foreach ($AdminList as $vd){
            $businessList = \think\facade\Db::name('business')->field('id,kfurl')->where('aid',$vd['id'])->select()->toArray();
            $businessList[]=[
                'id'=>0
            ];
            foreach($businessList as $vb){


            $designer_shopdetail = \think\facade\Db::name('designer_shopdetail')->where(['aid'=>$vd['id'],'bid'=>$vb['id']])->find();
             if(!$designer_shopdetail){
                if($vb['id'] == 0){
                    $AdminSet = \think\facade\Db::name('admin_set')->where('aid',$vd['id'])->field('id,aid,kfurl')->find();                
                              
                }else{
                    $AdminSet = $vb;                }
                
                
                $kfurl = '';
                $useSystem = 1;
                $gwc_showst = 1;
                $gwc_name = '购物车';
                $carturl = "/pages/shop/cart";
                if($AdminSet && !empty($AdminSet['kfurl'])){
                    $kfurl = $AdminSet['kfurl'];
                    $useSystem = 0;
                }
                if(pdo_fieldexists2("ddwx_shop_sysset","gwc_showst") && $vb['id'] == 0) {
                    $shopsysset = \think\facade\Db::name('shop_sysset')->where('aid',$vd['id'])->field('id,aid,gwc_showst,gwc_name')->find();
                    if($shopsysset){
                        if($shopsysset['gwc_showst'] == 2){
                            $gwc_showst = 0;
                        }
                        if(!empty($shopsysset['gwc_name'])){
                            $gwc_name = $shopsysset['gwc_name'];
                        }

                    }
                }
                if($vb['id'] > 0){
                    $carturl = "/pages/shop/cart?bid=".$vb['id']; 
                }
                $insertdata = [];
                $insertdata['aid'] = $vd['id'];
                $insertdata['bid'] = $vb['id'];
                $insertdata['menucount'] = 3;
                $insertdata['indexurl'] = '/pages/index/index';
                $insertdata['menudata'] = jsonEncode([
                    "color"=>"#BBBBBB",
                    "selectedColor"=>"#FD4A46",
                    "backgroundColor"=>"#ffffff",
                    "borderStyle"=>"black",
                    "position"=>"bottom",
                    "list"=>[
                        ["text"=>"客服","pagePath"=>$kfurl,"iconPath"=>PRE_URL.'/static/img/tabbar/kefu.png',"selectedIconPath"=>PRE_URL.'/static/img/tabbar/kefu.png',"pagePathname"=>"功能>客服","isShow"=>1,"menuType"=>1,"useSystem"=>$useSystem
                        ],
                        ["text"=>$gwc_name,"pagePath"=>$carturl,"iconPath"=>PRE_URL.'/static/img/tabbar/gwc.png',"selectedIconPath"=>PRE_URL.'/static/img/tabbar/gwc.png',"pagePathname"=>"基础功能>购物车","isShow"=>$gwc_showst,"menuType"=>2,"useSystem"=>0
                        ],
                        ["text"=>"收藏","pagePath"=>"addfavorite::","iconPath"=>PRE_URL.'/static/img/tabbar/shoucang.png',"selectedIconPath"=>PRE_URL.'/static/img/tabbar/shoucangselected.png',"pagePathname"=>"功能>商品收藏","isShow"=>1,"menuType"=>3,"useSystem"=>0,"selectedtext"=>"已收藏"
                        ],			
                    ]
                ]);
                $insertdata['navigationBarBackgroundColor'] = '#333333';
                $insertdata['navigationBarTextStyle'] = 'white';
                $insertdata['platform'] = 'all';
                \think\facade\Db::name('designer_shopdetail')->insert($insertdata);
                }

             }
          }
      }
    }
}


if(!pdo_fieldexists2("ddwx_admin_set","day_withdraw_num")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `day_withdraw_num` int NOT NULL DEFAULT 0 COMMENT '日提现次数';");
}
if(!pdo_fieldexists2("ddwx_business_sysset","show_shopdetail_menu")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `show_shopdetail_menu` tinyint(1) DEFAULT '0' COMMENT '自定义商品详情导航   0:关闭 1：开启';");
}

if(!pdo_fieldexists2("ddwx_toupiao","group_id")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_toupiao` ADD COLUMN `group_id`  int(11) NULL DEFAULT 0 COMMENT '分组id';");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_toupiao_group` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `name` varchar(255) DEFAULT NULL,
      `status` int(1) DEFAULT '1',
      `sort` int(11) DEFAULT '1',
      `createtime` int(11) DEFAULT NULL,
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE,
      KEY `bid` (`bid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='投票分组';");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_leveldown_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `levelid` int(11) DEFAULT NULL,
  `ordernum` varchar(100) DEFAULT NULL,
  `totalprice` decimal(11,2) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `beforelevelid` int(11) DEFAULT NULL,
  `platform` varchar(100) DEFAULT NULL,
  `from_mid` int(11) DEFAULT NULL,
  `pid` int(11) DEFAULT NULL,
  `remark` varchar(255) DEFAULT '' COMMENT '降级说明',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `levelid` (`levelid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");

if(!pdo_fieldexists2("ddwx_shortvideo_sysset","show_business_video")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_shortvideo_sysset` ADD COLUMN `show_business_video`  tinyint(1) NULL DEFAULT 0 COMMENT '多商户视频1显示0不显示';");
}

if(!pdo_fieldexists2("ddwx_admin_set_xieyi", "agree_type")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_xieyi` ADD COLUMN `agree_type` tinyint(1) UNSIGNED NULL DEFAULT '0' COMMENT '注册协议同意方式:0打勾，1阅读到最后';");
}

if(!pdo_fieldexists2("ddwx_restaurant_product","product_type")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product` ADD COLUMN `product_type` tinyint(1) DEFAULT '0' COMMENT '菜品类型 0：普通 1：称重 2：套餐';");
}

if (!pdo_fieldexists2("ddwx_maidan_order", "remark")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_maidan_order` ADD COLUMN `remark`  varchar(255) NULL DEFAULT '';");
}

if (!pdo_fieldexists2("ddwx_wxpay_log", "paysetjson")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_wxpay_log`	ADD COLUMN `paysetjson` text NULL;");
}

if(!pdo_fieldexists2("ddwx_admin_setapp_wx","sxpay_embedded")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_wx` ADD COLUMN `sxpay_embedded` tinyint(1) DEFAULT '0';");
}
if(!pdo_fieldexists2("ddwx_admin","remotearr")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `remotearr` varchar(20) NULL DEFAULT '0,2,3,4' COMMENT '可选附件存储类型' AFTER `remote`;");
}


if(!pdo_fieldexists2("ddwx_member_moneylog","rechargeid")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_moneylog` ADD COLUMN `rechargeid` int(11) DEFAULT NULL;");
}

if(!pdo_fieldexists2("ddwx_shop_order_goods","gg_group_title")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `gg_group_title` varchar(60) DEFAULT NULL COMMENT '规格分组名称';");
}

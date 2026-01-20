<?php

if(getcustom('freight_pstype11')){
    if(!pdo_fieldexists2("ddwx_freight", "type11pricedata")) {
        \think\facade\Db::execute("ALTER TABLE ddwx_freight ADD type11pricedata text;");
    }
}

if(getcustom('dc')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS  `ddwx_test` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `aid` (`aid`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS  `ddwx_restaurant_area` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `name` varchar(255) DEFAULT NULL COMMENT '餐厅区域名称',
	  `status` tinyint(1) DEFAULT '1',
	  `createtime` int(11) DEFAULT NULL,
	  `print_template_type` tinyint(1) unsigned DEFAULT '0' COMMENT '0普通打印，1一菜一单',
	  `print_ids` varchar(255) DEFAULT NULL COMMENT '关联打印机',
	  PRIMARY KEY (`id`),
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8;");

    if(!pdo_fieldexists2("ddwx_restaurant_product", "area_id")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product` ADD COLUMN `area_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '出餐区域id' AFTER `cid`;");
    }
}

if(getcustom('plug_businessqr')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_plug_businessqr_poster` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT NULL,
	  `content` text,
	  `posterurl` varchar(255) DEFAULT NULL,
	  `qrcode` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_plug_businessqr_pay` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `name` varchar(255) DEFAULT NULL,
	  `pic` varchar(255) DEFAULT NULL,
	  `market_price` float(10,2) DEFAULT NULL,
	  `sell_price` float(10,2) DEFAULT NULL,
	  `cost_price` float(10,2) DEFAULT NULL,
	  `status` int(1) DEFAULT '1',
	  `sort` int(11) DEFAULT '1',
	  `createtime` int(11) DEFAULT NULL,
	  `linkid` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_plug_businessqr_pay_order` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT NULL,
	  `mid` int(11) DEFAULT NULL,
	  `payid` int(11) DEFAULT NULL,
	  `ordernum` varchar(100) DEFAULT NULL,
	  `title` varchar(255) DEFAULT NULL,
	  `pic` varchar(255) DEFAULT NULL,
	  `market_price` float(10,2) DEFAULT NULL,
	  `sell_price` decimal(10,2) DEFAULT NULL,
	  `cost_price` decimal(10,2) DEFAULT NULL,
	  `totalprice` decimal(10,2) DEFAULT NULL,
	  `status` tinyint(1) DEFAULT NULL,
	  `createtime` int(11) DEFAULT NULL,
	  `payorderid` int(11) DEFAULT NULL,
	  `paytime` int(11) DEFAULT NULL,
	  `paytype` varchar(255) DEFAULT NULL,
	  `paytypeid` int(11) DEFAULT NULL,
	  `paynum` varchar(255) DEFAULT NULL,
	  `platform` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}
if(getcustom('express')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_express_address` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) DEFAULT NULL,
		  `mid` varchar(255) DEFAULT NULL,
		  `name` varchar(255) DEFAULT NULL,
		  `company` varchar(255) DEFAULT NULL,
		  `tel` varchar(255) DEFAULT NULL,
		  `province` varchar(255) DEFAULT NULL,
		  `city` varchar(255) DEFAULT NULL,
		  `district` varchar(255) DEFAULT NULL,
		  `area` varchar(255) DEFAULT NULL,
		  `address` varchar(255) DEFAULT NULL,
		  `latitude` varchar(255) DEFAULT NULL,
		  `longitude` varchar(255) DEFAULT NULL,
		  `isdefault` int(1) DEFAULT '0',
		  `createtime` int(11) DEFAULT NULL,
		  `mailtype` tinyint(2) DEFAULT '1' COMMENT '1 为寄件 2 为收件',
		  PRIMARY KEY (`id`) USING BTREE,
		  KEY `aid` (`aid`) USING BTREE,
		  KEY `mid` (`mid`) USING BTREE,
		  KEY `isdefault` (`isdefault`) USING BTREE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_express_cxlog` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) DEFAULT NULL,
		  `mid` varchar(255) DEFAULT NULL,
		  `company` varchar(255) DEFAULT NULL,
		  `text` varchar(255) DEFAULT NULL COMMENT '收件人信息',
		  `createtime` int(11) DEFAULT NULL,
		  `bid` int(11) DEFAULT '0',
		  `num` varchar(255) DEFAULT NULL,
		  `state` int(11) DEFAULT '0',
		  PRIMARY KEY (`id`) USING BTREE,
		  KEY `aid` (`aid`) USING BTREE,
		  KEY `mid` (`mid`) USING BTREE
	)ENGINE=InnoDB DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_express_order` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) DEFAULT NULL,
		  `mid` varchar(255) DEFAULT NULL,
		  `cargo` varchar(255) DEFAULT NULL,
		  `company` varchar(255) DEFAULT NULL,
		  `sm_time` varchar(255) DEFAULT NULL,
		  `recManName` varchar(255) DEFAULT NULL COMMENT '收件人信息',
		  `recManMobile` varchar(255) DEFAULT NULL,
		  `recManPrintAddr` varchar(255) DEFAULT NULL,
		  `sendManName` varchar(255) DEFAULT NULL,
		  `sendManMobile` varchar(255) DEFAULT NULL,
		  `sendManPrintAddr` varchar(255) DEFAULT NULL,
		  `weight` varchar(255) DEFAULT NULL,
		  `remark` int(1) DEFAULT '0',
		  `createtime` int(11) DEFAULT NULL,
		  `ordernum` varchar(255) DEFAULT NULL,
		  `recManPrintPro` varchar(255) DEFAULT NULL COMMENT '身份',
		  `recManPrintCity` varchar(255) DEFAULT NULL,
		  `sendManPrintPro` varchar(255) DEFAULT NULL,
		  `sendManPrintCity` varchar(255) DEFAULT NULL,
		  `orderId` varchar(255) DEFAULT '0' COMMENT '快递100返回给',
		  `platform` varchar(255) DEFAULT NULL,
		  `taskId` varchar(255) DEFAULT NULL,
		  `yundannum` varchar(255) DEFAULT NULL,
		  `status` int(11) DEFAULT '0',
		  `salt` varchar(255) DEFAULT NULL,
		  `courierName` varchar(255) DEFAULT NULL,
		  `courierMobile` varchar(255) DEFAULT NULL,
		  `kuaidinum` varchar(255) DEFAULT NULL,
		  PRIMARY KEY (`id`) USING BTREE,
		  KEY `aid` (`aid`) USING BTREE,
		  KEY `mid` (`mid`) USING BTREE,
		  KEY `isdefault` (`remark`) USING BTREE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_express_sysset` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) DEFAULT NULL,
		  `pstimedata` text,
		  `psprehour` int(11) DEFAULT '7',
		  `bid` int(11) DEFAULT '0',
		  `key` varchar(255) DEFAULT NULL COMMENT '企业 key',
		  `customer` varchar(255) DEFAULT NULL COMMENT '企业customer',
		  `secret_key` varchar(255) DEFAULT NULL,
		  `secret_secret` varchar(255) DEFAULT NULL,
		  `secret_code` varchar(255) DEFAULT NULL,
		  `secret` varchar(255) DEFAULT NULL,
		  PRIMARY KEY (`id`) USING BTREE,
		  KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    if(!pdo_fieldexists2("ddwx_express_sysset","secret_codep")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_express_sysset` ADD `secret_codep` varchar(255) DEFAULT NULL AFTER `secret`;");
    }
}
if(getcustom('everyday_hongbao')) {

    if(!pdo_fieldexists2("ddwx_shop_sysset","everyday_hongbao_open")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset`
ADD COLUMN `everyday_hongbao_open` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '红包开关' AFTER `canrefund`,
ADD COLUMN `everyday_hongbao_bl` decimal(5, 2) UNSIGNED  NULL DEFAULT '0' COMMENT '红包比例' AFTER `everyday_hongbao_open`,
ADD COLUMN `everyday_hongbao_bl_maidan` decimal(5, 2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '买单红包比例' AFTER `everyday_hongbao_bl`;");
    }

    if(!pdo_fieldexists2("ddwx_shop_product","everyday_hongbao_bl")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product`
ADD COLUMN `everyday_hongbao_bl` decimal(5, 2) NULL AFTER `douyin_status`;");
    }

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_hongbao_everyday` (
 `id` bigint(20) NOT NULL AUTO_INCREMENT,
 `aid` bigint(20) DEFAULT NULL,
 `type` varchar(255) DEFAULT NULL,
 `name` varchar(255) DEFAULT '',
 `banner` varchar(255) DEFAULT NULL,
 `bgpic` varchar(255) DEFAULT NULL,
 `starttime` int(11) DEFAULT NULL,
 `endtime` int(11) DEFAULT NULL,
 `guize` text COMMENT '活动规则',
 `num` int(11) NOT NULL DEFAULT '0' ,
 `shop_product_hongbao_bl` decimal(5, 2) NOT NULL DEFAULT '0' COMMENT '商品红包额度比例',
 `shop_order_money_type` varchar(30) NOT NULL DEFAULT 'pay' COMMENT 'pay:已支付订单，receive：收货订单',
 `maidan_hongbao_bl` decimal(5, 2) NOT NULL DEFAULT '0' COMMENT '买单红包额度比例',
 `hongbao_bl` decimal(5, 2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '红包比例',
 `hongbao_bl_business` decimal(5, 2) DEFAULT '0' COMMENT '多商户业绩红包比例',
 `hongbao_bl_maidan` decimal(5, 2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '买单红包比例',
 `createtime` varchar(50) DEFAULT NULL,
 `updatetime` varchar(50) DEFAULT NULL,
 `withdraw_weixin` tinyint(1) DEFAULT '0',
 `withdraw` tinyint(1) DEFAULT '1',
 `withdraw_autotransfer` tinyint(1) DEFAULT '0',
 `withdrawmin` decimal(11,2) DEFAULT '1.00',
 `withdrawfee` decimal(5,2) DEFAULT '0.00',
 `status` tinyint(1) DEFAULT '0',
 PRIMARY KEY (`id`) USING BTREE,
 KEY `aid` (`aid`) USING BTREE,
 KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_hbe_withdrawlog` (
`id` int(11) NOT NULL AUTO_INCREMENT,
 `aid` int(11) DEFAULT NULL,
 `mid` int(11) DEFAULT NULL,
 `money` decimal(11,2) DEFAULT NULL,
 `txmoney` decimal(11,2) DEFAULT NULL,
 `score` int(11) NOT NULL DEFAULT '0',
 `ordernum` varchar(255) DEFAULT NULL,
 `paytype` varchar(255) DEFAULT NULL,
 `status` tinyint(1) DEFAULT '0',
 `createtime` int(11) DEFAULT NULL,
 `paytime` int(11) DEFAULT NULL,
 `paynum` varchar(255) DEFAULT NULL,
 `platform` varchar(255) DEFAULT 'wx',
 `reason` varchar(255) DEFAULT NULL,
 PRIMARY KEY (`id`) USING BTREE,
 KEY `aid` (`aid`) USING BTREE,
 KEY `mid` (`mid`) USING BTREE,
 KEY `createtime` (`createtime`) USING BTREE,
 KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT = '每日红包提现记录';");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_hbe_edu_record` (
`id` int(11) NOT NULL AUTO_INCREMENT,
 `aid` int(11) DEFAULT NULL,
 `mid` int(11) DEFAULT NULL,
 `frommid` int(11) DEFAULT NULL,
 `orderid` int(11) DEFAULT NULL,
 `ogid` int(11) DEFAULT NULL,
 `type` varchar(100) DEFAULT 'shop' COMMENT 'shop 商城',
 `money` decimal(11,2) DEFAULT NULL,
 `remark` varchar(255) DEFAULT NULL,
 `createtime` int(11) DEFAULT NULL,
 `endtime` int(11) DEFAULT NULL,
 `status` tinyint(1) DEFAULT '0',
 PRIMARY KEY (`id`) USING BTREE,
 KEY `aid` (`aid`) USING BTREE,
 KEY `mid` (`mid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT = '每日红包额度记录';");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_hbe_record` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `money` decimal(11,2) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `createdate` date DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='每日红包记录';");

    if(!pdo_fieldexists2("ddwx_member","hongbao_everyday_edu")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member`
ADD COLUMN `hongbao_everyday_edu` decimal(10, 2) NOT NULL DEFAULT '0' AFTER `areafenhongbl`,
ADD COLUMN `hongbao_ereryday_total` decimal(10, 2) NOT NULL DEFAULT '0' AFTER `hongbao_everyday_edu`;");
    }

    if(!pdo_fieldexists2("ddwx_shop_order_goods","hongbaoEdu")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods`
ADD COLUMN `hongbaoEdu` decimal(11, 2) NOT NULL DEFAULT '0' AFTER `isteamfenhong`,
ADD COLUMN `ishongbao` tinyint(1) UNSIGNED NOT NULL DEFAULT '0';");
    }

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_hongbao_everyday_list` (
	`id` bigint(20) NOT NULL AUTO_INCREMENT,
  `aid` bigint(20) DEFAULT NULL,
  `mid` bigint(20) DEFAULT NULL,
  `num` int(11) NOT NULL DEFAULT '1',
  `createdate` date DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `money` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `left` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '剩余金额',
  `status` tinyint(1) DEFAULT '0',
  `updatetime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `createdate` (`createdate`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_hbe_log` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `mid` int(11) DEFAULT NULL,
	  `money` decimal(11,2) DEFAULT '0.00',
	  `after` decimal(11,2) DEFAULT '0.00',
	  `createtime` int(11) DEFAULT NULL,
	  `remark` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    if(!pdo_fieldexists2("ddwx_hongbao_everyday", "withdraw_score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_hongbao_everyday`
ADD COLUMN `withdraw_score` tinyint(1) NULL DEFAULT '0' AFTER `withdraw_weixin`,
ADD COLUMN `withdraw_score_bili` int(11) NULL DEFAULT '0' AFTER `withdrawfee`;");
    }
}

if(getcustom('design_group')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_designer_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `status` int(1) DEFAULT '1',
  `sort` int(11) DEFAULT '1',
  `createtime` int(11) DEFAULT NULL,
  `auth_uids` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}
if(getcustom('image_search')) {
    if (!pdo_fieldexists2("ddwx_admin", "image_search")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin`	ADD COLUMN `image_search` tinyint(1) NULL DEFAULT '0' AFTER `remark`;");
    }

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_baidu_set` (
	  `aid` int(11) NOT NULL,
	  `baidu_appid` varchar(200) NULL,
	  `baidu_apikey` varchar(200) NULL,
	  `baidu_secretkey` varchar(200) NULL,
	  `image_search` tinyint(1) NULL DEFAULT '0',
	  `image_search_num` tinyint(2) NULL DEFAULT '30',
	  `image_search_banner` varchar(255) NULL,
	  PRIMARY KEY (`aid`) USING BTREE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");

    if (!pdo_fieldexists2("ddwx_shop_product", "baidu_img_sync")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product`
	ADD COLUMN `baidu_img_sync` tinyint(1) NULL DEFAULT '0',
	ADD COLUMN `baidu_img_sync_time` int(11) NULL DEFAULT NULL,
	ADD COLUMN `baidu_img_cont_sign` varchar(200) NULL DEFAULT NULL,
	ADD INDEX `baidu_img_sync`(`baidu_img_sync`);");
    }
    if (!pdo_fieldexists2("ddwx_baidu_set", "image_search_pic")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_baidu_set` ADD COLUMN `image_search_pic` varchar(255) NULL AFTER `image_search_banner`;");
    }
}
if(getcustom('agent_card')){
    if(!pdo_fieldexists2("ddwx_admin","agent_card")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `agent_card` tinyint(1) NULL DEFAULT '0' AFTER `remark`;");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","agent_card")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`	ADD COLUMN `agent_card` tinyint(1) NULL DEFAULT '0';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_agent_card` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `mid` int(11) DEFAULT NULL,
	  `shopname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
	  `country` varchar(100) DEFAULT NULL,
	  `province` varchar(100) DEFAULT NULL,
	  `city` varchar(100) DEFAULT NULL,
	  `area` varchar(100) DEFAULT NULL,
	  `address` varchar(255) DEFAULT NULL,
	  `longitude` varchar(100) DEFAULT NULL,
	  `latitude` varchar(100) DEFAULT NULL,
	  `name` varchar(100) DEFAULT NULL,
	  `tel` varchar(100) DEFAULT NULL,
	  `pagecontent` longtext,
	  `createtime` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE,
	  KEY `createtime` (`createtime`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    if(!pdo_fieldexists2("ddwx_member_agent_card","logo")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_agent_card` ADD COLUMN `logo` varchar(255) DEFAULT NULL;");
    }
}
if(getcustom('up_give_coupon')){
    if(!pdo_fieldexists2("ddwx_member_level","up_give_coupon")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_give_coupon` varchar(255) NULL DEFAULT '' COMMENT '升级赠送优惠券-商城';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_give_restaurant_coupon")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_give_restaurant_coupon` varchar(255) NULL DEFAULT '' COMMENT '升级赠送优惠券-餐饮';");
    }
}

if(getcustom('usecoupon_give_score')){
    if(!pdo_fieldexists2("ddwx_coupon","usecoupon_give_score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon`
		ADD COLUMN `usecoupon_give_score` int(11) DEFAULT '0' COMMENT '转赠优惠券被使用后赠送转赠者积分',
		ADD COLUMN `usecoupon_give_type` tinyint(1) DEFAULT '1' COMMENT '1 支付后赠送 2确认收货后赠送'");
    }
}

if(getcustom('next_level_set')){
    if(!pdo_fieldexists2("ddwx_member_level","next_level_id")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `next_level_id` int(11) DEFAULT '0' COMMENT '下个等级id'");
    }
}

if(getcustom('fenhong_send_tmpl')){
    if(!pdo_fieldexists2("ddwx_mp_tmplset","tmpl_fenhong")){
        \think\facade\Db::execute("ALTER TABLE ddwx_mp_tmplset ADD tmpl_fenhong varchar(255) DEFAULT NULL COMMENT '分红到账通知';");
    }
}

if(getcustom('choujiang_time')) {
    if(!pdo_fieldexists2("ddwx_dscj", "opennum")) {
        \think\facade\Db::execute("ALTER TABLE ddwx_dscj
        ADD COLUMN `opennum`  int(11) NULL DEFAULT 0 COMMENT '开奖人数' AFTER `is_done`,
        ADD COLUMN `joinnum`  int(11) NULL DEFAULT 0 COMMENT '参与总人数' AFTER `opennum`,
        ADD COLUMN `set_opentime`  int(11) NULL DEFAULT NULL COMMENT '设置的开奖时间' AFTER `joinnum`,
        ADD COLUMN `opentype`  tinyint(1) NULL DEFAULT '1' COMMENT '开奖方式 1自动开奖 2手动开奖' AFTER `set_opentime`;");
    }
}
if(getcustom('invite_free')) {
    if(!pdo_fieldexists2("ddwx_shop_order","is_free")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `is_free` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否免费 0：否 1：是';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `free_time` int NOT NULL DEFAULT 0 COMMENT '免费时间' ;");

        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `free_use` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否免单使用过 0：未使用 1：已使用' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `join_free_time` int NOT NULL DEFAULT 0 COMMENT '加入免单时间';");

        \think\facade\Db::execute("ALTER TABLE `ddwx_mp_tmplset` ADD COLUMN `tmpl_joinfree` varchar(255) NOT NULL DEFAULT '' COMMENT '加入免单通知';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_wx_tmplset` ADD COLUMN `tmpl_joinfree` varchar(255) NOT NULL DEFAULT '' COMMENT '加入免单通知';");

        \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_invite_free` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`aid` int(11) NOT NULL DEFAULT 0,
			`pic` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '图片',
			`friend_num` int(11) NOT NULL DEFAULT 0 COMMENT '好友个数',
			`add_up_ratio` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '累积金额',
			`shuoming` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '说明',
			`gettj` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '参与人群',
			`status` tinyint(255) NOT NULL DEFAULT 0 COMMENT '状态0：不开启 1：开启',
			`createtime` int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY (`id`) USING BTREE,
			INDEX `aid`(`aid`) USING BTREE
			) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }
    if(!pdo_fieldexists2("ddwx_invite_free","start_time")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_invite_free` ADD COLUMN `start_time` int(11) NOT NULL DEFAULT 0 COMMENT '开始时间';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_invite_free` ADD COLUMN `end_time` int(11) NOT NULL DEFAULT 0 COMMENT '结束时间';");
    }
}

if(getcustom('yuyue_delayed')) {
    if(!pdo_fieldexists2("ddwx_yuyue_worker_category","delayedtime")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_worker_category` ADD COLUMN `delayedtime` int(11) NOT NULL DEFAULT 0 COMMENT '延时抢单时间';");
    }
}

if(getcustom('usecoupon_give_money')){
    if(!pdo_fieldexists2("ddwx_coupon","usecoupon_give_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` ADD COLUMN `usecoupon_give_money` decimal(11,2) DEFAULT '0';");
    }
}

if(getcustom('usecoupon_give_coupon')){
    if(!pdo_fieldexists2("ddwx_coupon","usecoupon_give_coupon")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` ADD COLUMN `usecoupon_give_coupon` varchar(255) NUll;");
    }
}

if(getcustom('commission_givedown')){
    if(!pdo_fieldexists2("ddwx_member_level","givedown_percent")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `givedown_percent` float(11,2) DEFAULT '0.00';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `givedown_txt` varchar(255) DEFAULT '';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","givedown_commission")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `givedown_commission` float(11,2) DEFAULT '0.00';");
    }
}

if(getcustom('up_downbuyprocount')){
    if(!pdo_fieldexists2("ddwx_member_level","up_downbuypronum")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `up_downbuypronum` int(11) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `up_downbuyproid` varchar(255) DEFAULT '';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `up_downbuyprolvnum` int(11) DEFAULT '0';");
    }
}
if(getcustom('fenhong_removefenxiao')){
    if(!pdo_fieldexists2("ddwx_member_level","teamfenhong_removefenxiao")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `teamfenhong_removefenxiao` tinyint(1) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `gdfenhong_removefenxiao` tinyint(1) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `areafenhong_removefenxiao` tinyint(1) DEFAULT '0';");
    }
}
if(getcustom('invite_free')){
    if(!pdo_fieldexists2("ddwx_wx_tmplset","tmpl_activity_notice")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_wx_tmplset` ADD COLUMN `tmpl_activity_notice` varchar(255) NOT NULL DEFAULT '' COMMENT '活动通知';");
    }
}

if(getcustom('product_moneypay')){
    if(!pdo_fieldexists2("ddwx_shop_product","product_moneypay")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `product_moneypay` tinyint(1) DEFAULT '1';");
    }
}
if(getcustom('express_wx')) {
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_express_wx_account` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) DEFAULT NULL,
		  `bid` int(11) DEFAULT '0',
		  `delivery_id` varchar(60) DEFAULT '',
		  `delivery_name` varchar(255) DEFAULT '',
		  `shopid` varchar(60) DEFAULT '',
		  `appsecret` varchar(255) NULL,
		  `audit_result` int(3) DEFAULT '0',
		  `createtime` int(11) DEFAULT NULL,
		  `remark` varchar(255) DEFAULT '',
		  `status` tinyint(1) UNSIGNED NULL DEFAULT '0',
		  PRIMARY KEY (`id`),
		  KEY `aid` (`aid`),
		  KEY `bid` (`bid`)
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_express_wx_order` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT NULL,
	  `mid` int(11) DEFAULT NULL,
	  `type` varchar(60) DEFAULT NULL,
	  `orderid` int(11) DEFAULT NULL,
	  `shop_order_id` varchar(255) DEFAULT NULL,
	  `goods_name` varchar(255) DEFAULT NULL,
	  `goods_count` int(11) DEFAULT NULL,
	  `img_url` varchar(255) DEFAULT NULL,
	  `totalprice` decimal(11,2) DEFAULT '0.00',
	  `shopid` varchar(255) DEFAULT NULL,
	  `shop_no` varchar(255) DEFAULT NULL,
	  `openid` varchar(255) DEFAULT NULL,
	  `delivery_id` varchar(255) DEFAULT NULL,
	  `delivery_sign` varchar(255) DEFAULT NULL,
	  `recManName` varchar(255) DEFAULT NULL COMMENT '收件人信息',
	  `recManMobile` varchar(255) DEFAULT NULL,
	  `recManPrintPro` varchar(255) DEFAULT NULL COMMENT '身份',
	  `recManPrintCity` varchar(255) DEFAULT NULL,
	  `receiver_json` text,
	  `sendManName` varchar(255) DEFAULT NULL,
	  `sendManMobile` varchar(255) DEFAULT NULL,
	  `sendManPrintPro` varchar(255) DEFAULT NULL,
	  `sendManPrintCity` varchar(255) DEFAULT NULL,
	  `sender_json` text,
	  `cargo_json` text,
	  `order_info_json` text,
	  `shop_json` text,
	  `weight` varchar(255) DEFAULT NULL,
	  `remark` int(1) DEFAULT '0',
	  `createtime` int(11) DEFAULT NULL,
	  `platform` varchar(255) DEFAULT NULL,
	  `waybill_id` varchar(255) DEFAULT '0' COMMENT '配送单号',
	  `order_status` int(11) DEFAULT '0' COMMENT '配送状态',
	  `fee` decimal(11,2) DEFAULT '0.00' COMMENT '实际运费',
	  `deliverfee` decimal(11,2) DEFAULT '0.00' COMMENT '运费 单位：元',
	  `couponfee` decimal(11,2) DEFAULT '0.00' COMMENT '优惠券',
	  `tips` decimal(11,2) DEFAULT '0.00' COMMENT '小费',
	  `insurancefee` decimal(11,2) DEFAULT '0.00' COMMENT '保价费',
	  `distance` int(11) DEFAULT '0' COMMENT '距离，单位：米',
	  `finish_code` varchar(255) DEFAULT NULL COMMENT '收货码',
	  `pickup_code` varchar(255) DEFAULT NULL COMMENT '取货码',
	  `dispatch_duration` varchar(255) DEFAULT '0' COMMENT '预计骑手接单时间，单位秒',
	  `errcode` varchar(255) DEFAULT NULL,
	  `errmsg` varchar(255) DEFAULT NULL,
	  `resultcode` varchar(255) DEFAULT NULL,
	  `resultmsg` varchar(255) DEFAULT NULL,
	  `orderinfo` text,
	  `prolist` longtext,
	  `binfo` text,
	  `deduct_fee` decimal(11,2) DEFAULT NULL COMMENT '取消违约金',
	  `rider_name` varchar(255) DEFAULT '' COMMENT '骑手姓名',
	  `rider_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '骑手电话',
	  `rider_lng` varchar(60) DEFAULT NULL COMMENT '骑手位置经度, 配送中时返回',
	  `rider_lat` varchar(60) DEFAULT NULL COMMENT '骑手位置纬度, 配送中时返回',
	  `reach_time` int(11) DEFAULT NULL COMMENT '预计送达时间戳',
	  `action_time` int(11) DEFAULT NULL COMMENT '状态更新时间',
	  `starttime` int(11) DEFAULT NULL,
	  `daodiantime` int(11) DEFAULT NULL,
	  `quhuotime` int(11) DEFAULT NULL,
	  `endtime` int(11) NOT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `type` (`type`) USING BTREE,
	  KEY `orderid` (`orderid`) USING BTREE,
	  KEY `shop_order_id` (`shop_order_id`) USING BTREE,
	  KEY `recManMobile` (`recManMobile`) USING BTREE,
	  KEY `sendManMobile` (`sendManMobile`) USING BTREE,
	  KEY `order_status` (`order_status`) USING BTREE,
	  KEY `waybill_id` (`waybill_id`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_express_wx_order_status_log` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) DEFAULT NULL,
		  `bid` int(11) DEFAULT NULL,
		  `mid` int(11) DEFAULT NULL,
		  `type` varchar(60) DEFAULT NULL,
		  `express_orderid` int(11) DEFAULT NULL,
		  `orderid` int(11) DEFAULT NULL,
		  `shop_order_id` varchar(255) DEFAULT NULL,
		  `waybill_id` varchar(255) DEFAULT '0' COMMENT '配送单号',
		  `order_status` int(11) DEFAULT '0' COMMENT '配送状态',
		  `order_action` varchar(255) DEFAULT '' COMMENT '动作描述',
		  `createtime` int(11) DEFAULT NULL COMMENT '状态更新时间',
		  PRIMARY KEY (`id`) USING BTREE,
		  KEY `aid` (`aid`) USING BTREE,
		  KEY `mid` (`mid`) USING BTREE,
		  KEY `bid` (`bid`) USING BTREE,
		  KEY `type` (`type`) USING BTREE,
		  KEY `express_orderid` (`express_orderid`) USING BTREE,
		  KEY `orderid` (`orderid`) USING BTREE,
		  KEY `shop_order_id` (`shop_order_id`) USING BTREE,
		  KEY `createtime` (`createtime`) USING BTREE,
		  KEY `order_status` (`order_status`) USING BTREE,
		  KEY `waybill_id` (`waybill_id`) USING BTREE
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}

if(getcustom('yx_kouling')) {
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_kouling` (
	  `id` bigint(20) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT NULL,
	  `type` varchar(255) DEFAULT NULL,
	  `name` varchar(255) DEFAULT '',
	  `starttime` int(11) DEFAULT NULL,
	  `endtime` int(11) DEFAULT NULL,
	  `pertotal` int(11) DEFAULT '1' COMMENT '每人参与总数',
	  `perday` int(11) DEFAULT '0' COMMENT '每天数量',
	  `num` int(11) DEFAULT '0' COMMENT '总次数',
	  `zjnum` int(11) DEFAULT '0' COMMENT '已中次数',
	  `money` float(11,2) DEFAULT '0.00',
	  `score` int(11) DEFAULT '0',
	  `give_coupon` tinyint(1) DEFAULT '0',
	  `coupon_ids` varchar(255) DEFAULT NULL,
	  `createtime` varchar(50) DEFAULT NULL,
	  `updatetime` varchar(50) DEFAULT NULL,
	  `gettj` varchar(255) DEFAULT '-1',
	  `status` tinyint(1) DEFAULT '1',
	  `scene_id` varchar(100) DEFAULT NULL,
	  `fanwei` tinyint(1) DEFAULT '0',
	  `fanwei_lng` varchar(100) DEFAULT NULL,
	  `fanwei_lat` varchar(100) DEFAULT NULL,
	  `fanwei_range` varchar(100) DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `name` (`name`) USING BTREE,
	  KEY `num` (`num`) USING BTREE,
	  KEY `status` (`status`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_kouling_record` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT NULL,
	  `objid` int(11) DEFAULT NULL COMMENT '口令id',
	  `name` varchar(255) DEFAULT NULL,
	  `mid` int(11) DEFAULT NULL,
	  `headimg` varchar(255) DEFAULT NULL,
	  `nickname` varchar(255) DEFAULT NULL,
	  `linkman` varchar(255) DEFAULT NULL,
	  `tel` char(11) DEFAULT NULL COMMENT '手机号',
	  `coupon_ids` varchar(255) DEFAULT NULL COMMENT '获得的优惠券',
	  `money` decimal(11,2) DEFAULT '0.00',
	  `score` int(11) DEFAULT NULL,
	  `formdata` text,
	  `createtime` int(11) DEFAULT NULL COMMENT '抽奖时间',
	  `createdate` date DEFAULT NULL,
	  `status` tinyint(1) DEFAULT '0' COMMENT '是否领奖',
	  `remark` varchar(1023) DEFAULT NULL,
	  `code` varchar(255) DEFAULT NULL,
	  `hexiaoqr` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `objid` (`objid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE,
	  KEY `createtime` (`createtime`) USING BTREE,
	  KEY `createdate` (`createdate`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_kouling_set` (
	  `id` bigint(20) NOT NULL AUTO_INCREMENT,
	  `aid` bigint(20) DEFAULT NULL,
	  `bid` bigint(20) DEFAULT NULL,
	  `name` varchar(255) DEFAULT '',
	  `banner` varchar(255) DEFAULT NULL,
	  `guize` text COMMENT '活动规则',
	  `createtime` int(10) DEFAULT NULL,
	  `status` tinyint(1) DEFAULT '0',
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `status` (`status`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
}

if(getcustom('guige_split')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shop_ggsplit` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) DEFAULT NULL,
		  `proid` int(11) DEFAULT NULL,
		  `ggid1` int(11) DEFAULT NULL,
		  `ggid2` int(11) DEFAULT NULL,
		  `multiple` int(11) DEFAULT NULL,
		  `createtime` int(11) DEFAULT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}

if(getcustom('yx_riddle')) {
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_riddle` (
	  `id` bigint(20) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT NULL,
	  `type` varchar(255) DEFAULT NULL,
	  `name` varchar(255) DEFAULT '',
	  `title` varchar(64) DEFAULT '',
	  `content` longtext,
	  `starttime` int(11) DEFAULT NULL,
	  `endtime` int(11) DEFAULT NULL,
	  `pertotal` int(11) DEFAULT '1' COMMENT '每人参与总数',
	  `perday` int(11) DEFAULT '0' COMMENT '每人每天数量',
	  `num` int(11) DEFAULT '0' COMMENT '总次数',
	  `zjnum` int(11) DEFAULT '0' COMMENT '已中次数',
	  `money` float(11,2) DEFAULT '0.00',
	  `score` int(11) DEFAULT '0',
	  `give_coupon` tinyint(1) DEFAULT '0',
	  `coupon_ids` varchar(255) DEFAULT NULL,
	  `createtime` varchar(50) DEFAULT NULL,
	  `updatetime` varchar(50) DEFAULT NULL,
	  `gettj` varchar(255) DEFAULT '-1',
	  `status` tinyint(1) DEFAULT '1',
	  `scene_id` varchar(100) DEFAULT NULL,
	  `fanwei` tinyint(1) DEFAULT '0',
	  `fanwei_lng` varchar(100) DEFAULT NULL,
	  `fanwei_lat` varchar(100) DEFAULT NULL,
	  `fanwei_range` varchar(100) DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `name` (`name`) USING BTREE,
	  KEY `num` (`num`) USING BTREE,
	  KEY `status` (`status`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_riddle_record` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT NULL,
	  `objid` int(11) DEFAULT NULL COMMENT '口令id',
	  `name` varchar(255) DEFAULT NULL,
	  `mid` int(11) DEFAULT NULL,
	  `headimg` varchar(255) DEFAULT NULL,
	  `nickname` varchar(255) DEFAULT NULL,
	  `linkman` varchar(255) DEFAULT NULL,
	  `tel` char(11) DEFAULT NULL COMMENT '手机号',
	  `coupon_ids` varchar(255) DEFAULT NULL COMMENT '获得的优惠券',
	  `money` decimal(11,2) DEFAULT '0.00',
	  `score` int(11) DEFAULT NULL,
	  `formdata` text,
	  `createtime` int(11) DEFAULT NULL COMMENT '抽奖时间',
	  `createdate` date DEFAULT NULL,
	  `status` tinyint(1) DEFAULT '0' COMMENT '是否领奖',
	  `remark` varchar(1023) DEFAULT NULL,
	  `code` varchar(255) DEFAULT NULL,
	  `hexiaoqr` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `objid` (`objid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE,
	  KEY `createtime` (`createtime`) USING BTREE,
	  KEY `createdate` (`createdate`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
}

if(getcustom('yx_jidian')) {
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_jidian_set` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT NULL,
	  `price_start` decimal(11,2) DEFAULT '0.00' COMMENT '最低支付金额',
	  `score` int(11) DEFAULT '0',
	  `set` text,
	  `name` varchar(255) DEFAULT NULL,
	  `status` tinyint(1) DEFAULT '0',
	  `guize` longtext,
	  `starttime` int(11) DEFAULT NULL,
	  `endtime` int(11) DEFAULT NULL,
	  `days` int(11) DEFAULT '0' COMMENT '消费时间周期(天)',
	  `give_coupon` tinyint(1) DEFAULT '0',
	  `coupon_ids` varchar(255) DEFAULT NULL,
	  `gettj` varchar(255) DEFAULT '-1',
	  `paygive_scene` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  UNIQUE KEY `bid` (`bid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_jidian_record` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT NULL,
	  `name` varchar(255) DEFAULT NULL,
	  `mid` int(11) DEFAULT NULL,
	  `headimg` varchar(255) DEFAULT NULL,
	  `nickname` varchar(255) DEFAULT NULL,
	  `linkman` varchar(255) DEFAULT NULL,
	  `tel` char(11) DEFAULT NULL COMMENT '手机号',
	  `jidian_num` int(11) DEFAULT '1',
	  `coupon_ids` tinyint(4) DEFAULT NULL COMMENT '获得的优惠券',
	  `money` decimal(11,2) DEFAULT '0.00',
	  `score` int(11) DEFAULT NULL,
	  `formdata` text,
	  `createtime` int(11) DEFAULT NULL COMMENT '抽奖时间',
	  `createdate` date DEFAULT NULL,
	  `status` tinyint(1) DEFAULT '0' COMMENT '是否领奖',
	  `remark` varchar(1023) DEFAULT NULL,
	  `code` varchar(255) DEFAULT NULL,
	  `hexiaoqr` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE,
	  KEY `createtime` (`createtime`) USING BTREE,
	  KEY `createdate` (`createdate`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
}
if(getcustom('choujiang_time')) {
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_dscj` (
      `id` bigint(20) NOT NULL AUTO_INCREMENT,
      `aid` bigint(20) DEFAULT NULL,
      `type` varchar(255) DEFAULT NULL,
      `name` varchar(255) DEFAULT NULL,
      `starttime` int(11) DEFAULT NULL,
      `endtime` int(11) DEFAULT NULL,
      `guize` text COMMENT '活动规则',
      `sharetitle` varchar(255) DEFAULT NULL,
      `sharelink` varchar(255) DEFAULT NULL COMMENT '分享链接',
      `sharepic` varchar(255) DEFAULT NULL,
      `sharedesc` varchar(255) DEFAULT NULL,
      `j0mc` varchar(255) NOT NULL DEFAULT '谢谢参与',
      `j0pic` varchar(255) DEFAULT NULL,
      `j0sl` int(11) NOT NULL DEFAULT '0',
      `j0yj` int(11) DEFAULT '0',
      `j1mc` varchar(255) DEFAULT '一等奖' COMMENT '奖项名称',
      `j1pic` varchar(255) DEFAULT NULL,
      `j1tp` tinyint(1) DEFAULT '1',
      `j1sl` int(11) DEFAULT '10' COMMENT '奖品数量',
      `j1yj` int(11) DEFAULT '0' COMMENT '已抽中数',
      `j2mc` varchar(255) DEFAULT '二等奖' COMMENT '奖项名称',
      `j2pic` varchar(255) DEFAULT NULL,
      `j2tp` tinyint(1) DEFAULT '1',
      `j2sl` int(11) DEFAULT '20' COMMENT '奖品数量',
      `j2yj` int(11) DEFAULT '0' COMMENT '已抽中数',
      `j3mc` varchar(255) DEFAULT '三等奖' COMMENT '奖项名称',
      `j3pic` varchar(255) DEFAULT NULL,
      `j3tp` tinyint(1) DEFAULT '1',
      `j3sl` int(11) DEFAULT '30' COMMENT '奖品数量',
      `j3yj` int(11) DEFAULT '0' COMMENT '已抽中数',
      `j4mc` varchar(255) DEFAULT NULL COMMENT '奖项名称',
      `j4pic` varchar(255) DEFAULT NULL,
      `j4tp` tinyint(1) DEFAULT '1',
      `j4sl` int(11) DEFAULT '40' COMMENT '奖品数量',
      `j4yj` int(11) DEFAULT '0' COMMENT '已抽中数',
      `j5mc` varchar(255) DEFAULT NULL COMMENT '奖项名称',
      `j5pic` varchar(255) DEFAULT NULL,
      `j5tp` tinyint(1) DEFAULT '1',
      `j5sl` int(11) DEFAULT NULL COMMENT '奖品数量',
      `j5yj` int(11) DEFAULT '0' COMMENT '已抽中数',
      `j6mc` varchar(255) DEFAULT NULL COMMENT '奖项名称',
      `j6pic` varchar(255) DEFAULT NULL,
      `j6tp` tinyint(1) DEFAULT '1',
      `j6sl` int(11) DEFAULT NULL COMMENT '奖品数量',
      `j6yj` int(11) DEFAULT '0' COMMENT '已抽中数',
      `j7mc` varchar(255) DEFAULT NULL COMMENT '奖项名称',
      `j7pic` varchar(255) DEFAULT NULL,
      `j7tp` tinyint(1) DEFAULT '1',
      `j7sl` int(11) DEFAULT NULL COMMENT '奖品数量',
      `j7yj` int(11) DEFAULT '0' COMMENT '已抽中数',
      `j8mc` varchar(255) DEFAULT NULL COMMENT '奖项名称',
      `j8pic` varchar(255) DEFAULT NULL,
      `j8tp` tinyint(1) DEFAULT '1',
      `j8sl` int(11) DEFAULT NULL COMMENT '奖品数量',
      `j8yj` int(11) DEFAULT '0' COMMENT '已抽中数',
      `j9mc` varchar(255) DEFAULT NULL COMMENT '奖项名称',
      `j9pic` varchar(255) DEFAULT NULL,
      `j9tp` tinyint(1) DEFAULT '1',
      `j9sl` int(11) DEFAULT NULL COMMENT '奖品数量',
      `j9yj` int(11) DEFAULT '0' COMMENT '已抽中数',
      `j10mc` varchar(255) DEFAULT NULL,
      `j10pic` varchar(255) DEFAULT NULL,
      `j10tp` tinyint(1) DEFAULT '1',
      `j10sl` int(11) DEFAULT NULL,
      `j10yj` int(11) DEFAULT '0',
      `j11mc` varchar(255) DEFAULT NULL,
      `j11pic` varchar(255) DEFAULT NULL,
      `j11tp` tinyint(1) DEFAULT '1',
      `j11sl` int(11) DEFAULT NULL,
      `j11yj` int(11) DEFAULT '0',
      `j12mc` varchar(255) DEFAULT NULL,
      `j12pic` varchar(255) DEFAULT NULL,
      `j12tp` tinyint(1) DEFAULT '1',
      `j12sl` int(11) DEFAULT NULL,
      `j12yj` int(11) DEFAULT '0',
      `formcontent` text,
      `createtime` varchar(50) DEFAULT NULL,
      `updatetime` varchar(50) DEFAULT NULL,
      `gettj` varchar(255) DEFAULT '-1',
      `status` tinyint(1) DEFAULT '1',
      `pics` text COMMENT '多轮播图',
      `need_fee` tinyint(1) DEFAULT '0',
      `fee` decimal(10,2) DEFAULT '0.00' COMMENT '付费金额',
      `bid` int(11) DEFAULT '0',
      `opentime` int(11) DEFAULT NULL,
      `content` text,
      `qrcode` varchar(255) DEFAULT '',
      `qrcode_tip` varchar(255) DEFAULT '',
      `is_done` tinyint(1) DEFAULT '0',
      PRIMARY KEY (`id`),
      KEY `aid` (`aid`) USING BTREE,
      KEY `status` (`status`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");


    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_dscj_order` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `mid` int(11) DEFAULT NULL,
      `hid` int(11) DEFAULT NULL COMMENT '大转盘id',
      `headimg` varchar(255) DEFAULT NULL,
      `nickname` varchar(255) DEFAULT NULL,
      `tel` char(11) DEFAULT NULL COMMENT '手机号',
      `createtime` int(11) DEFAULT NULL COMMENT '抽奖时间',
      `totalprice` decimal(10,2) DEFAULT '0.00' COMMENT '付费抽奖的金额',
      `status` tinyint(1) DEFAULT '0' COMMENT '是否支付',
      `payorderid` int(11) DEFAULT NULL,
      `paytypeid` int(11) DEFAULT '0',
      `paytype` varchar(50) DEFAULT '',
      `paynum` varchar(255) DEFAULT '',
      `paytime` int(11) DEFAULT NULL,
      `ordernum` varchar(255) DEFAULT '',
      `title` text,
      `coupon_rid` int(11) DEFAULT NULL,
      `coupon_money` decimal(11,2) DEFAULT '0.00' COMMENT '优惠券金额',
      `type` tinyint(1) DEFAULT '1' COMMENT '1真实用户 2虚拟用户',
      `platform` varchar(32) DEFAULT '',
      `is_done` tinyint(1) DEFAULT '0',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE,
      KEY `hid` (`hid`) USING BTREE,
      KEY `mid` (`mid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");


    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_dscj_record` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT 0,
      `hid` int(11) DEFAULT NULL COMMENT '活动id',
      `name` varchar(255) DEFAULT NULL,
      `mid` int(11) DEFAULT NULL,
      `headimg` varchar(255) DEFAULT NULL,
      `nickname` varchar(255) DEFAULT NULL,
      `linkman` varchar(255) DEFAULT NULL,
      `tel` char(11) DEFAULT NULL COMMENT '手机号',
      `jx` tinyint(4) DEFAULT NULL COMMENT '获得的奖项',
      `jxtp` tinyint(1) DEFAULT '1' COMMENT '类型 3优惠券 4积分 5余额',
      `jxmc` varchar(255) DEFAULT NULL COMMENT '奖品名称',
      `formdata` text,
      `createtime` int(11) DEFAULT NULL COMMENT '抽奖时间',
      `createdate` varchar(50) DEFAULT NULL,
      `status` tinyint(1) DEFAULT '0' COMMENT '是否领奖',
      `remark` varchar(1023) DEFAULT NULL,
      `code` varchar(255) DEFAULT NULL,
      `hexiaoqr` varchar(255) DEFAULT NULL,
      `orderid` int(11) DEFAULT '0',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE,
      KEY `hid` (`hid`) USING BTREE,
      KEY `mid` (`mid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    if(!pdo_fieldexists2("ddwx_dscj_record","bid")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_dscj_record` ADD COLUMN `bid` int (11) NOT NULL DEFAULT 0;");
    }
    if (!pdo_fieldexists2("ddwx_dscj_record", "updatetime")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_dscj_record`
    ADD COLUMN `updatetime` int(11) DEFAULT NULL,
    MODIFY COLUMN `jx`  int(4) NULL DEFAULT NULL COMMENT '获得的奖项';");
    }

}
if(getcustom('counsel_fee')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_counsel_fee` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NULL DEFAULT NULL,
		`name` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '民事案件' COMMENT '民事名称',
		`guigedata` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '民事内容',
		`status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '民事状态',
		`xz_guigedata` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '行政内容',
		`xs_guigedata` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '刑事内容',
		`xz_status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '行政状态',
		`xs_name` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '行政案件' COMMENT '刑事名称',
		`xs_status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '刑事状态',
		`xz_name` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '行政案件' COMMENT '行政名称',
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `aid`(`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}
if(getcustom('legal_fee')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_legal_fee` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NULL DEFAULT NULL,
		`name` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '诉讼费计算' COMMENT '名称',
		`law_name` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '法条',
		`law_content` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '法条内容',
		`guigedata` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '标的内容',
		`bq_guigedata` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '保全内容',
		`zx_guigedata` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '执行内容',
		`status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态',
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `aid`(`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}
if(getcustom('luntan_call')){
    if(!pdo_fieldexists2("ddwx_luntan","mobile")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_luntan` ADD COLUMN `mobile` varchar(15) NOT NULL DEFAULT '' COMMENT '电话';");
    }
}
if(getcustom('shop_other_infor')){
    if(!pdo_fieldexists2("ddwx_shop_product","xunjia_text")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `xunjia_text` varchar(50) NOT NULL DEFAULT '' COMMENT '询价提示';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","main_business")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `main_business` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '主营业务';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `main_business` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '主营业务';");
    }
}
if(getcustom('article_portion')){
    if(!pdo_fieldexists2("ddwx_article","pth_status")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_article` ADD COLUMN `pth_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '自定义3 0：不启用 1：启用';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_article` ADD COLUMN `pth_name` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '自定义3文字';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_article` ADD COLUMN `pth_content` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '自定义3内容';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_article` ADD COLUMN `pf_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '自定义4 0：不启用 1：启用';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_article` ADD COLUMN `pf_name` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '自定义4文字';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_article` ADD COLUMN `pf_content` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '自定义3内容';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_article` MODIFY COLUMN `pic` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL;");
    }
    if(!pdo_fieldexists2("ddwx_form","edit_status")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `edit_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否能编辑：0否 1是';");
    }

    if(!pdo_fieldexists2("ddwx_article","po_status")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_article` ADD COLUMN `po_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '自定义1 0：不启用 1：启用';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_article` ADD COLUMN `po_name` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '自定义1文字';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_article` ADD COLUMN `po_content` varchar(255) NOT NULL DEFAULT '' COMMENT '自定义1内容';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_article` ADD COLUMN `pt_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '自定义2 0：不启用 1：启用';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_article` ADD COLUMN `pt_name` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '自定义2文字';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_article` ADD COLUMN `pt_content` varchar(255) NOT NULL DEFAULT '' COMMENT '自定义2内容';");
    }
}
if(getcustom('register_fields')) {
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_register_form` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `name` varchar(255) DEFAULT NULL,
      `content` longtext,
      `sort` int(11) DEFAULT '0',
      `createtime` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_register_form_record` (
      `id` bigint(20) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `formid` bigint(20) DEFAULT NULL,
      `content` text COMMENT '自定义表单内容',
      `createtime` int(11) DEFAULT NULL,
      `form0` varchar(255) DEFAULT NULL,
      `form1` varchar(255) DEFAULT NULL,
      `form2` varchar(255) DEFAULT NULL,
      `form3` varchar(255) DEFAULT NULL,
      `form4` varchar(255) DEFAULT NULL,
      `form5` varchar(255) DEFAULT NULL,
      `form6` varchar(255) DEFAULT NULL,
      `form7` varchar(255) DEFAULT NULL,
      `form8` varchar(255) DEFAULT NULL,
      `form9` varchar(255) DEFAULT NULL,
      `form10` varchar(255) DEFAULT NULL,
      `form11` varchar(255) DEFAULT NULL,
      `form12` varchar(255) DEFAULT NULL,
      `form13` varchar(255) DEFAULT NULL,
      `form14` varchar(255) DEFAULT NULL,
      `form15` varchar(255) DEFAULT NULL,
      `form16` varchar(255) DEFAULT NULL,
      `form17` varchar(255) DEFAULT NULL,
      `form18` varchar(255) DEFAULT NULL,
      `form19` varchar(255) DEFAULT NULL,
      `form20` varchar(255) DEFAULT NULL,
      `form21` varchar(255) DEFAULT NULL,
      `form22` varchar(255) DEFAULT NULL,
      `form23` varchar(255) DEFAULT NULL,
      `form24` varchar(255) DEFAULT NULL,
      `form25` varchar(255) DEFAULT NULL,
      `form26` varchar(255) DEFAULT NULL,
      `form27` varchar(255) DEFAULT NULL,
      `form28` varchar(255) DEFAULT NULL,
      `form29` varchar(255) DEFAULT NULL,
      `form30` varchar(255) DEFAULT NULL,
      `form31` varchar(255) DEFAULT NULL,
      `form32` varchar(255) DEFAULT NULL,
      `form33` varchar(255) DEFAULT NULL,
      `form34` varchar(255) DEFAULT NULL,
      `form35` varchar(255) DEFAULT NULL,
      `form36` varchar(255) DEFAULT NULL,
      `form37` varchar(255) DEFAULT NULL,
      `form38` varchar(255) DEFAULT NULL,
      `form39` varchar(255) DEFAULT NULL,
      `form40` varchar(255) DEFAULT NULL,
      `form41` varchar(255) DEFAULT NULL,
      `form42` varchar(255) DEFAULT NULL,
      `form43` varchar(255) DEFAULT NULL,
      `form44` varchar(255) DEFAULT NULL,
      `form45` varchar(255) DEFAULT NULL,
      `form46` varchar(255) DEFAULT NULL,
      `form47` varchar(255) DEFAULT NULL,
      `form48` varchar(255) DEFAULT NULL,
      `form49` varchar(255) DEFAULT NULL,
      `form50` varchar(255) DEFAULT NULL,
      `form51` varchar(255) DEFAULT NULL,
      `form52` varchar(255) DEFAULT NULL,
      `form53` varchar(255) DEFAULT NULL,
      `form54` varchar(255) DEFAULT NULL,
      `form55` varchar(255) DEFAULT NULL,
      `form56` varchar(255) DEFAULT NULL,
      `form57` varchar(255) DEFAULT NULL,
      `form58` varchar(255) DEFAULT NULL,
      `form59` varchar(255) DEFAULT NULL,
      `form60` varchar(255) DEFAULT '',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE,
      KEY `bid` (`bid`) USING BTREE,
      KEY `formid` (`formid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    if(!pdo_fieldexists2("ddwx_member","form_record_id")){
        \think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `form_record_id` int(11) DEFAULT '0';");
    }
}
if(getcustom('diy_light')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_diylight_set` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `aid` int(11) NOT NULL,
          `mid` int(11) DEFAULT '0',
          `status` tinyint(1) DEFAULT '0',
          `bgimgs` text,
          PRIMARY KEY (`id`) USING BTREE,
          KEY `aid` (`aid`),
          KEY `mid` (`mid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
}

if(getcustom('up_downbuyprocount')){
    if(!pdo_fieldexists2("ddwx_member_level","up_downbuypronum")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `up_downbuypronum` int(11) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `up_downbuyproid` varchar(255) DEFAULT '';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `up_downbuyprolvnum` int(11) DEFAULT '0';");
    }
}
if(getcustom('fenhong_removefenxiao')){
    if(!pdo_fieldexists2("ddwx_member_level","teamfenhong_removefenxiao")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `teamfenhong_removefenxiao` tinyint(1) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `gdfenhong_removefenxiao` tinyint(1) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `areafenhong_removefenxiao` tinyint(1) DEFAULT '0';");
    }
}

if(getcustom('invite_free')){
    if(!pdo_fieldexists2("ddwx_wx_tmplset","tmpl_activity_notice")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_wx_tmplset` ADD COLUMN `tmpl_activity_notice` varchar(255) NOT NULL DEFAULT '' COMMENT '活动通知';");
    }
}

if(getcustom('xixie')){
    if(!pdo_fieldexists2("ddwx_mendian","withdrawfee")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `withdrawfee` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '手续费';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `peisong_rangetype` tinyint(1) NULL DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `peisong_rangepath` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `peisong_lng2` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `peisong_lat2` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `peisong_lng` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `peisong_lat` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `peisong_range` int(11) NULL DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `province` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '省';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `city` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '市';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `district` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '区县';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `peisong_juli1` float(11, 1) NULL DEFAULT 5.0 ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `peisong_fee1` float(11, 2) NULL DEFAULT 3.00 ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `peisong_juli2` float(11, 1) NULL DEFAULT NULL ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `peisong_fee2` float(11, 2) NULL DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `money` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '收入金额' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `withdrawmin` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '最低提现金额' ;");

        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `pstimeset` tinyint(1) NULL DEFAULT 0 COMMENT '是否开启配送时间选择' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `pstimedata` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '配送时间设置' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `psprehour` int(11) NULL DEFAULT 4 COMMENT '选择配送时间y要大于当前时间多少小时' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `zaohour` int(255) NULL DEFAULT 8 COMMENT '预约早几点' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `wanhour` int(255) NULL DEFAULT 21 COMMENT '预约晚几点' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `timejg` int(11) NULL DEFAULT 30 COMMENT '时间间隔 ' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `yyzhouqi` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '预约周期，周一-周日'  ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `datetype` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1时间段，2时间点' ;");

        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `timepoint` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `yybegintime` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '预约开始时间' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `yyendtime` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `rqtype` tinyint(3) NOT NULL DEFAULT 1 COMMENT '预约周期' ;");

        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `yytimeday` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '预约固定周期'  ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `pdprehour` int(11) NOT NULL DEFAULT 1 COMMENT '可选条件：\r\n下单时间大于可选时间' ;");

        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `yynum` int(11) NOT NULL DEFAULT 1 COMMENT '同一时间段预约人数限制';");

        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `mdid` int NOT NULL DEFAULT 0 COMMENT '门店id';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `is_vip` tinyint(255) NOT NULL DEFAULT 0 COMMENT '是否是vip 0: 否 1：是' ;");

        \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_mendian_member`  (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) NOT NULL DEFAULT 0,
		  `mdid` int(11) NOT NULL DEFAULT 0 COMMENT '门店id',
		  `realname` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '姓名',
		  `tel` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '手机号',
		  `account` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '账号',
		  `pwd` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '密码',
		  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态0：禁止 1：开启',
		  `mid` int(11) NOT NULL DEFAULT 0 COMMENT '用户id',
		  `createtime` int(11) NOT NULL DEFAULT 0,
		  `is_del` tinyint(1) NOT NULL DEFAULT 0 COMMENT '删除 0：否 1：是',
		  `ip` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
		  `logintime` int(11) NOT NULL DEFAULT 0,
		  PRIMARY KEY (`id`) USING BTREE
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

        \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_mendian_member_loginlog`  (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) NULL DEFAULT NULL,
		  `uid` int(11) NULL DEFAULT NULL,
		  `logintime` int(11) NULL DEFAULT NULL,
		  `loginip` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `logintype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  PRIMARY KEY (`id`) USING BTREE,
		  INDEX `aid`(`aid`) USING BTREE,
		  INDEX `uid`(`uid`) USING BTREE
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");


        \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_mendian_moneylog`  (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) NOT NULL DEFAULT 0,
		  `mdid` int(11) NOT NULL DEFAULT 0,
		  `orderid` int(11) NOT NULL DEFAULT 0,
		  `money` decimal(11, 2) NOT NULL DEFAULT 0.00,
		  `after` decimal(11, 2) NOT NULL DEFAULT 0.00,
		  `createtime` int(11) NOT NULL DEFAULT 0,
		  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `mid` int(11) NOT NULL DEFAULT 0,
		  PRIMARY KEY (`id`) USING BTREE,
		  INDEX `aid`(`aid`) USING BTREE,
		  INDEX `mid`(`mdid`) USING BTREE
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

        \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_mendian_withdrawlog`  (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) NULL DEFAULT NULL,
		  `bid` int(11) NULL DEFAULT NULL,
		  `mid` int(11) NOT NULL DEFAULT 0 COMMENT '会员id',
		  `mdid` int(11) NOT NULL DEFAULT 0 COMMENT '门店id',
		  `money` decimal(11, 2) NULL DEFAULT NULL,
		  `txmoney` decimal(11, 2) NULL DEFAULT NULL,
		  `weixin` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `aliaccount` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `ordernum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `paytype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `status` tinyint(1) NULL DEFAULT 0,
		  `createtime` int(11) NULL DEFAULT NULL,
		  `bankname` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `bankcarduser` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `bankcardnum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `paytime` int(11) NULL DEFAULT NULL,
		  `paynum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `reason` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `platform` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
		  `wx_package_info` text NULL COMMENT '微信商家转账确认页package信息',
		  `wx_transfer_bill_no` varchar(100) NULL DEFAULT '' COMMENT '微信转账单号，微信商家转账系统返回的唯一标识',
		  `wx_transfer_msg` varchar(255) NULL DEFAULT NULL COMMENT '微信转账错误信息',
		  `wx_state`  varchar(50) NULL DEFAULT NULL COMMENT '微信商家转账状态\r\nACCEPTED: 转账已受理\r\nPROCESSING: 转账处理中，转账结果尚未明确，如一直处于此状态，建议检查账户余额是否足够\r\nWAIT_USER_CONFIRM: 待收款用户确认，可拉起微信收款确认页面进行收款确认\r\nTRANSFERING: 转账结果尚未明确，可拉起微信收款确认页面再次重试确认收款\r\nSUCCESS: 转账成功\r\nFAIL: 转账失败\r\nCANCELING: 商户撤销请求受理成功，该笔转账正在撤销中\r\nCANCELLED: 转账撤销完成',
		  PRIMARY KEY (`id`) USING BTREE,
		  INDEX `aid`(`aid`) USING BTREE,
		  INDEX `bid`(`bid`) USING BTREE,
		  INDEX `status`(`status`) USING BTREE
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

        \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_xixie_cart`  (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) NULL DEFAULT NULL,
		  `bid` int(11) NULL DEFAULT 0,
		  `mid` int(11) NULL DEFAULT NULL,
		  `proid` int(11) NULL DEFAULT NULL,
		  `num` int(11) NULL DEFAULT NULL,
		  `createtime` int(11) NULL DEFAULT NULL,
		  PRIMARY KEY (`id`) USING BTREE,
		  INDEX `aid`(`aid`) USING BTREE,
		  INDEX `bid`(`bid`) USING BTREE,
		  INDEX `mid`(`mid`) USING BTREE,
		  INDEX `proid`(`proid`) USING BTREE
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
        \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_xixie_category`  (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) NULL DEFAULT NULL,
		  `pid` int(11) NULL DEFAULT 0,
		  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `pic` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `status` int(1) NULL DEFAULT 1,
		  `sort` int(11) NULL DEFAULT 1,
		  `createtime` int(11) NULL DEFAULT NULL,
		  PRIMARY KEY (`id`) USING BTREE,
		  INDEX `aid`(`aid`) USING BTREE
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
        \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_xixie_order`  (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) NULL DEFAULT NULL,
		  `bid` int(11) NULL DEFAULT 0,
		  `mid` int(11) NULL DEFAULT NULL,
		  `mdid` int(11) NOT NULL DEFAULT 0,
		  `ordernum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `title` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
		  `totalprice` float(11, 2) NULL DEFAULT NULL,
		  `product_price` float(11, 2) NULL DEFAULT 0.00,
		  `coupon_money` decimal(11, 2) NULL DEFAULT 0.00 COMMENT '优惠券金额',
		  `coupon_rid` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `createtime` int(11) NULL DEFAULT NULL,
		  `status` int(11) NULL DEFAULT 0 COMMENT '0未支付;1已支付、待取货，2、已取货、入库中 3已入库、清洗中 4、已清洗、送货中 5、已完成 ',
		  `linkman` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `company` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `tel` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `area` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `area2` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `longitude` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `latitude` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `message` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `payorderid` int(11) NULL DEFAULT NULL,
		  `paytypeid` int(11) NULL DEFAULT NULL,
		  `paytype` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `paynum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `paytime` int(11) NULL DEFAULT NULL,
		  `refund_reason` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
		  `refund_money` decimal(11, 2) NOT NULL DEFAULT 0.00,
		  `refund_status` int(1) NOT NULL DEFAULT 0 COMMENT '1申请退款审核中 2已同意退款 3已驳回',
		  `refund_time` int(11) NOT NULL DEFAULT 0,
		  `refund_checkremark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
		  `send_time` bigint(20) NULL DEFAULT NULL COMMENT '发货时间',
		  `collect_time` int(11) NULL DEFAULT NULL COMMENT '收货时间',
		  `platform` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'wx',
		  `iscomment` tinyint(1) NULL DEFAULT 0,
		  `delete` tinyint(1) NULL DEFAULT 0,
		  `scene` int(11) NULL DEFAULT 0 COMMENT '小程序场景',
		  `peisong_fee` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '配送费',
		  `qh_time` int(11) NOT NULL DEFAULT 0 COMMENT '取货完成时间',
		  `rk_time` int(11) NOT NULL DEFAULT 0 COMMENT '入库完成时间',
		  `qx_time` int(11) NOT NULL DEFAULT 0 COMMENT '清洗完成时间',
		  `end_time` int(11) NOT NULL DEFAULT 0 COMMENT '已完成',
		  `md_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '门店名称',
		  `md_address` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '门店地址',
		  `md_tel` varchar(12) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '门店电话',
		  `buy_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '购买类型 1：上门取件 2：送货到店',
		  `freight_time` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
		  `yy_time` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '预约时间',
		  `yytime` int(11) NOT NULL DEFAULT 0 COMMENT '预约时间戳',
		  `psid` int(11) NOT NULL DEFAULT 0 COMMENT '配送员id(mendian_member表)',
		  `psmid` int(11) NOT NULL DEFAULT 0 COMMENT '配送员用户表id',
		  `qd_time` int(11) NOT NULL DEFAULT 0 COMMENT '抢单时间',
		  `ps_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '配送员姓名',
		  `ps_tel` varchar(12) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '配送员手机号',
		  `update_time` int(11) NOT NULL DEFAULT 0,
		  `qh_pics` varchar(900) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '取货图片',
		  PRIMARY KEY (`id`) USING BTREE,
		  INDEX `aid`(`aid`) USING BTREE,
		  INDEX `bid`(`bid`) USING BTREE,
		  INDEX `mid`(`mid`) USING BTREE,
		  INDEX `status`(`status`) USING BTREE,
		  INDEX `createtime`(`createtime`) USING BTREE
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
        \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_xixie_order_goods`  (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) NULL DEFAULT NULL,
		  `bid` int(11) NULL DEFAULT 0,
		  `mid` int(11) NULL DEFAULT NULL,
		  `orderid` int(11) NULL DEFAULT NULL,
		  `ordernum` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `proid` int(11) NULL DEFAULT NULL,
		  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `pic` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `cid` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '0',
		  `num` int(11) UNSIGNED NOT NULL DEFAULT 0,
		  `refund_num` int(11) UNSIGNED NOT NULL DEFAULT 0,
		  `refund_money` decimal(11, 2) NULL DEFAULT 0.00,
		  `sell_price` decimal(11, 2) NULL DEFAULT NULL,
		  `totalprice` decimal(11, 2) NULL DEFAULT NULL,
		  `coupon_money` decimal(11, 2) NULL DEFAULT 0.00,
		  `real_totalprice` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '实际商品销售金额 减去了优惠券抵扣会员折扣满减积分抵扣的金额',
		  `status` int(1) NULL DEFAULT 0 COMMENT '0未支付;1已支付、待取货，2、已取货、入库中 3已入库、清洗中 4、已清洗、送货中 5、已送货 6、已完成 ',
		  `createtime` int(11) NULL DEFAULT NULL,
		  `endtime` int(11) NULL DEFAULT NULL,
		  `iscomment` tinyint(1) NULL DEFAULT 0,
		  PRIMARY KEY (`id`) USING BTREE,
		  INDEX `aid`(`aid`) USING BTREE,
		  INDEX `bid`(`bid`) USING BTREE,
		  INDEX `mid`(`mid`) USING BTREE,
		  INDEX `orderid`(`orderid`) USING BTREE,
		  INDEX `proid`(`proid`) USING BTREE
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
        \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_xixie_product`  (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) NULL DEFAULT NULL,
		  `bid` int(11) NULL DEFAULT 0,
		  `cid` int(11) NULL DEFAULT 0,
		  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `procode` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `fuwupoint` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `sellpoint` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `pic` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
		  `pics` varchar(5000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `sales` int(11) UNSIGNED NULL DEFAULT 0,
		  `detail` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
		  `sell_price` decimal(11, 2) NULL DEFAULT 0.00,
		  `vip_price` decimal(11, 2) NULL DEFAULT 0.00,
		  `weight` int(11) NULL DEFAULT NULL,
		  `sort` int(11) NULL DEFAULT 0,
		  `status` int(1) NULL DEFAULT 1,
		  `stock` int(11) UNSIGNED NULL DEFAULT 100,
		  `createtime` int(11) NULL DEFAULT NULL,
		  `commissionset` tinyint(1) NULL DEFAULT 0,
		  `commission1` decimal(11, 2) NULL DEFAULT NULL,
		  `commission2` decimal(11, 2) NULL DEFAULT NULL,
		  `commission3` decimal(11, 2) NULL DEFAULT NULL,
		  `comment_score` decimal(2, 1) NULL DEFAULT 5.0,
		  `comment_num` int(11) NULL DEFAULT 0,
		  `comment_haopercent` int(11) NULL DEFAULT 100,
		  `buymax` int(11) NOT NULL DEFAULT 0,
		  `freighttype` tinyint(1) NULL DEFAULT 1,
		  `freightdata` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `freightcontent` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
		  `ischecked` tinyint(1) NULL DEFAULT 1,
		  `check_reason` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `commissiondata1` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
		  `commissiondata2` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
		  `commissiondata3` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
		  `feepercent` decimal(5, 2) UNSIGNED NULL DEFAULT NULL,
		  PRIMARY KEY (`id`) USING BTREE,
		  INDEX `aid`(`aid`) USING BTREE,
		  INDEX `bid`(`bid`) USING BTREE,
		  INDEX `cid`(`cid`) USING BTREE,
		  INDEX `status`(`status`) USING BTREE,
		  INDEX `ischecked`(`ischecked`) USING BTREE
		)  ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
        \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_xixie_sysset`  (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) NULL DEFAULT NULL,
		  `autoshdays` int(11) NULL DEFAULT 7,
		  PRIMARY KEY (`id`) USING BTREE,
		  UNIQUE INDEX `aid`(`aid`) USING BTREE
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
        \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_xixie_vip`  (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) NOT NULL DEFAULT 0,
		  `fee` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '费用',
		  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
		  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否开启0：未开启 1：开启',
		  `createtime` int(11) NOT NULL DEFAULT 0,
		  `free_peisongfee` tinyint(1) NOT NULL DEFAULT 0 COMMENT '免除配送费0：否 1：是',
		  PRIMARY KEY (`id`) USING BTREE
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
        \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_xixie_vip_order`  (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) NULL DEFAULT NULL,
		  `mid` int(11) NULL DEFAULT NULL,
		  `money` decimal(11, 2) NULL DEFAULT 0.00,
		  `ordernum` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `createtime` int(11) NULL DEFAULT NULL,
		  `status` tinyint(1) NULL DEFAULT 0,
		  `payorderid` int(11) NULL DEFAULT NULL,
		  `paytypeid` int(11) NULL DEFAULT NULL,
		  `paytype` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `paynum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `paytime` int(11) NULL DEFAULT NULL,
		  `platform` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  PRIMARY KEY (`id`) USING BTREE,
		  INDEX `aid`(`aid`) USING BTREE,
		  INDEX `mid`(`mid`) USING BTREE
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
        \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_open_area`  (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) NOT NULL DEFAULT 0,
		  `area` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '区域',
		  `province` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '省',
		  `city` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '市',
		  `createtime` int(11) NOT NULL DEFAULT 0,
		  PRIMARY KEY (`id`) USING BTREE
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }

    if(!pdo_fieldexists2("ddwx_xixie_sysset","head_address")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_xixie_sysset` ADD COLUMN `head_address` tinyint(1) NOT NULL DEFAULT 0 COMMENT '首页头部地址0：为开启 1：已开启' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_xixie_sysset` ADD COLUMN `popup_address` tinyint(1) NOT NULL DEFAULT 0 COMMENT '首页地址弹窗0：为开启 1：已开启'  ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_xixie_sysset` ADD COLUMN `cart_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '首页头部地址0：为开启 1：已开启' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_xixie_sysset` ADD COLUMN `all_close` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态全部关闭0：否 1：是'  ;");
    }
    if(!pdo_fieldexists2("ddwx_mendian_moneylog","bid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian_moneylog` ADD COLUMN `bid` int(11) NOT NULL DEFAULT 0;");
    }
}

if(getcustom('yueke')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yueke_category` (
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
	  KEY `pid` (`pid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yueke_comment` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `mid` int(11) DEFAULT NULL,
	  `orderid` int(11) DEFAULT NULL,
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
	  `content_pic` varchar(500) DEFAULT NULL,
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
	  KEY `proid` (`proid`) USING BTREE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yueke_order` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `mid` int(11) DEFAULT NULL,
	  `ordernum` varchar(255) DEFAULT NULL,
	  `title` text,
	  `totalprice` float(11,2) DEFAULT NULL,
	  `product_price` float(11,2) DEFAULT '0.00',
	  `leveldk_money` float(11,2) DEFAULT '0.00',
	  `coupon_rid` int(11) DEFAULT NULL COMMENT '优惠券coupon_record的id',
	  `num` int(11) DEFAULT '1',
	  `createtime` int(11) DEFAULT NULL,
	  `status` int(11) DEFAULT '0' COMMENT '0未支付;1已支付;2已发货;3已收货;4关闭;',
	  `linkman` varchar(255) DEFAULT NULL,
	  `tel` varchar(50) DEFAULT NULL,
	  `area` varchar(255) DEFAULT NULL,
	  `area2` varchar(255) DEFAULT NULL,
	  `address` varchar(255) DEFAULT NULL,
	  `longitude` varchar(100) DEFAULT NULL,
	  `latitude` varchar(100) DEFAULT NULL,
	  `message` varchar(255) DEFAULT NULL,
	  `remark` varchar(255) DEFAULT NULL,
	  `payorderid` int(11) DEFAULT NULL,
	  `paytypeid` int(11) DEFAULT NULL COMMENT '16 次卡支付',
	  `paytype` varchar(50) DEFAULT NULL,
	  `paynum` varchar(255) DEFAULT NULL,
	  `paytime` int(11) DEFAULT NULL,
	  `send_time` bigint(20) DEFAULT NULL COMMENT '发货时间',
	  `collect_time` int(11) DEFAULT NULL COMMENT '收货时间',
	  `yy_date` varchar(100) DEFAULT NULL,
	  `yy_time` varchar(255) DEFAULT NULL,
	  `hexiao_code` varchar(100) DEFAULT NULL COMMENT '唯一码 核销码',
	  `hexiao_qr` varchar(255) DEFAULT NULL,
	  `platform` varchar(255) DEFAULT 'wx',
	  `iscomment` tinyint(1) DEFAULT '0',
	  `delete` tinyint(1) DEFAULT '0',
	  `coupon_money` float(11,2) DEFAULT '0.00',
	  `propic` varchar(255) DEFAULT NULL,
	  `proname` varchar(255) DEFAULT NULL,
	  `proid` int(11) DEFAULT '0',
	  `workerid` int(11) DEFAULT '0' COMMENT '服务人员id',
	  `begintime` int(11) DEFAULT '0',
	  `endtime` int(11) DEFAULT '0',
	  `refund_status` tinyint(1) DEFAULT '0',
	  `refund_time` int(11) DEFAULT NULL,
	  `refund_reason` varchar(255) DEFAULT NULL,
	  `refund_money` decimal(11,2) DEFAULT NULL,
	  `refund_checkremark` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  UNIQUE KEY `code` (`hexiao_code`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE,
	  KEY `status` (`status`) USING BTREE,
	  KEY `createtime` (`createtime`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");


    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yueke_product` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `name` varchar(255) DEFAULT NULL,
	  `procode` varchar(255) DEFAULT NULL,
	  `fuwupoint` varchar(255) DEFAULT NULL,
	  `sellpoint` varchar(255) DEFAULT NULL,
	  `workerid` int(11) DEFAULT '0',
	  `pic` varchar(255) DEFAULT '',
	  `pics` varchar(5000) DEFAULT NULL,
	  `sales` int(11) DEFAULT '0',
	  `detail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
	  `sell_price` float(11,2) DEFAULT '0.00',
	  `sort` int(11) DEFAULT '0',
	  `status` int(1) DEFAULT '1',
	  `stock` int(11) unsigned DEFAULT '100',
	  `createtime` int(11) DEFAULT NULL,
	  `comment_score` decimal(2,1) DEFAULT '5.0',
	  `comment_num` int(11) DEFAULT '0',
	  `comment_haopercent` int(11) DEFAULT '100',
	  `gettj` varchar(255) DEFAULT '-1',
	  `gettjurl` varchar(255) DEFAULT NULL,
	  `gettjtip` varchar(255) DEFAULT NULL,
	  `starttime` varchar(100) DEFAULT NULL,
	  `endtime` varchar(100) DEFAULT NULL,
	  `ischecked` tinyint(1) DEFAULT '1',
	  `check_reason` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
	  `cid` varchar(11) DEFAULT '0' COMMENT '分类id',
	  `yynum` int(11) DEFAULT '1' COMMENT '同一时间段预约人数限制',
	  `rqtype` tinyint(3) DEFAULT '1' COMMENT '预约周期',
	  `yyzhouqi` varchar(255) DEFAULT NULL COMMENT '预约周期，周一-周日',
	  `start_time` varchar(100) DEFAULT NULL,
	  `end_time` varchar(100) DEFAULT NULL,
	  `yybegintime` varchar(100) DEFAULT NULL COMMENT '预约开始时间',
	  `yyendtime` varchar(100) DEFAULT NULL,
	  `couponids` varchar(255) DEFAULT NULL COMMENT '预约次卡id',
	  `yytimeday` varchar(255) DEFAULT NULL COMMENT '预约固定周期',
	  `formdata` text,
	  `prehour` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `status` (`status`) USING BTREE,
	  KEY `stock` (`stock`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci COMMENT='商品表';");


    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yueke_set` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `status` tinyint(1) DEFAULT '0',
	  `autoshdays` int(11) DEFAULT '7',
	  `autoclose` int(255) DEFAULT '600',
	  `minminute` int(11) DEFAULT '3',
	  `discount` tinyint(1) DEFAULT '0',
	  `iscoupon` tinyint(1) DEFAULT '0',
	  `comment_check` tinyint(1) unsigned DEFAULT '1' COMMENT '评价审核，1开启，0关闭',
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yueke_worker` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `mid` int(11) DEFAULT NULL,
	  `un` varchar(255) DEFAULT NULL,
	  `pwd` varchar(255) DEFAULT NULL,
	  `realname` varchar(255) DEFAULT NULL,
	  `tel` varchar(255) DEFAULT NULL,
	  `headimg` varchar(255) DEFAULT 'https://v2d.diandashop.com/static/img/touxiang.png',
	  `status` tinyint(1) DEFAULT '1' COMMENT '0 未开启  1已开启',
	  `createtime` int(11) DEFAULT NULL,
	  `sort` int(11) DEFAULT '0',
	  `comment_score` decimal(2,1) DEFAULT '5.0',
	  `comment_num` int(11) DEFAULT '0',
	  `comment_haopercent` int(11) DEFAULT '100' COMMENT '好评率',
	  `money` decimal(11,2) DEFAULT '0.00',
	  `totalmoney` decimal(11,2) DEFAULT '0.00',
	  `totalnum` int(11) DEFAULT '0',
	  `weixin` varchar(60) NULL,
	  `aliaccount` varchar(60) DEFAULT NULL,
	  `bankname` varchar(60) DEFAULT NULL,
	  `bankcarduser` varchar(60) DEFAULT NULL,
	  `bankcardnum` varchar(100) DEFAULT NULL,
	  `dengji` varchar(255) DEFAULT NULL,
	  `desc` varchar(255) DEFAULT NULL,
	  `content` longtext COMMENT '介绍',
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yueke_worker_comment` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT NULL,
	  `mid` int(11) DEFAULT NULL,
	  `worker_id` int(11) DEFAULT NULL,
	  `orderid` int(11) DEFAULT NULL,
	  `ordernum` varchar(50) DEFAULT NULL,
	  `openid` varchar(255) DEFAULT NULL,
	  `nickname` varchar(255) DEFAULT NULL,
	  `headimg` varchar(255) DEFAULT NULL,
	  `score` int(11) DEFAULT NULL,
	  `content` varchar(255) DEFAULT NULL,
	  `content_pic` varchar(255) DEFAULT NULL,
	  `reply_content` varchar(255) DEFAULT NULL,
	  `reply_content_pic` varchar(255) DEFAULT NULL,
	  `status` int(1) DEFAULT '1',
	  `createtime` int(11) DEFAULT '0',
	  `reply_time` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `peisong_user_id` (`worker_id`) USING BTREE,
	  KEY `order_id` (`orderid`) USING BTREE,
	  KEY `order_no` (`ordernum`) USING BTREE,
	  KEY `status` (`status`) USING BTREE
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='配送单评价';");


}

if(getcustom('product_moneypay')){
    if(!pdo_fieldexists2("ddwx_shop_product","product_moneypay")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `product_moneypay` tinyint(1) DEFAULT '1';");
    }
}


if(getcustom('teamfenhong_pingji')){
    if(!pdo_fieldexists2("ddwx_shop_product","teamfenhongpjset")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `teamfenhongpjset` int(2) DEFAULT '0' COMMENT '团队分红平级奖设置-1不参与奖励0按照会员等级1单独设置奖励比例2单独设置奖励金额4单独设置奖励积分比例';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `teamfenhongpjdata1` text;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `teamfenhongpjdata2` text;");
    }
}


if(getcustom('collage_limit')){
    if(!pdo_fieldexists2("ddwx_collage_product", "is_many_times")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_collage_product` ADD COLUMN `is_many_times` tinyint(1) DEFAULT '0';");
    }
    if(!pdo_fieldexists2("ddwx_collage_product", "max_times")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_collage_product` ADD COLUMN `max_times` int(11) DEFAULT '0';");
    }
}
if(getcustom('register_fields')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_register_form_record` (
	  `id` bigint(20) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `formid` bigint(20) DEFAULT NULL,
      `content` text COMMENT '自定义表单内容',
      `createtime` int(11) DEFAULT NULL,
      `form0` varchar(255) DEFAULT NULL,
      `form1` varchar(255) DEFAULT NULL,
      `form2` varchar(255) DEFAULT NULL,
      `form3` varchar(255) DEFAULT NULL,
      `form4` varchar(255) DEFAULT NULL,
      `form5` varchar(255) DEFAULT NULL,
      `form6` varchar(255) DEFAULT NULL,
      `form7` varchar(255) DEFAULT NULL,
      `form8` varchar(255) DEFAULT NULL,
      `form9` varchar(255) DEFAULT NULL,
      `form10` varchar(255) DEFAULT NULL,
      `form11` varchar(255) DEFAULT NULL,
      `form12` varchar(255) DEFAULT NULL,
      `form13` varchar(255) DEFAULT NULL,
      `form14` varchar(255) DEFAULT NULL,
      `form15` varchar(255) DEFAULT NULL,
      `form16` varchar(255) DEFAULT NULL,
      `form17` varchar(255) DEFAULT NULL,
      `form18` varchar(255) DEFAULT NULL,
      `form19` varchar(255) DEFAULT NULL,
      `form20` varchar(255) DEFAULT NULL,
      `form21` varchar(255) DEFAULT NULL,
      `form22` varchar(255) DEFAULT NULL,
      `form23` varchar(255) DEFAULT NULL,
      `form24` varchar(255) DEFAULT NULL,
      `form25` varchar(255) DEFAULT NULL,
      `form26` varchar(255) DEFAULT NULL,
      `form27` varchar(255) DEFAULT NULL,
      `form28` varchar(255) DEFAULT NULL,
      `form29` varchar(255) DEFAULT NULL,
      `form30` varchar(255) DEFAULT NULL,
      `form31` varchar(255) DEFAULT NULL,
      `form32` varchar(255) DEFAULT NULL,
      `form33` varchar(255) DEFAULT NULL,
      `form34` varchar(255) DEFAULT NULL,
      `form35` varchar(255) DEFAULT NULL,
      `form36` varchar(255) DEFAULT NULL,
      `form37` varchar(255) DEFAULT NULL,
      `form38` varchar(255) DEFAULT NULL,
      `form39` varchar(255) DEFAULT NULL,
      `form40` varchar(255) DEFAULT NULL,
      `form41` varchar(255) DEFAULT NULL,
      `form42` varchar(255) DEFAULT NULL,
      `form43` varchar(255) DEFAULT NULL,
      `form44` varchar(255) DEFAULT NULL,
      `form45` varchar(255) DEFAULT NULL,
      `form46` varchar(255) DEFAULT NULL,
      `form47` varchar(255) DEFAULT NULL,
      `form48` varchar(255) DEFAULT NULL,
      `form49` varchar(255) DEFAULT NULL,
      `form50` varchar(255) DEFAULT NULL,
      `form51` varchar(255) DEFAULT NULL,
      `form52` varchar(255) DEFAULT NULL,
      `form53` varchar(255) DEFAULT NULL,
      `form54` varchar(255) DEFAULT NULL,
      `form55` varchar(255) DEFAULT NULL,
      `form56` varchar(255) DEFAULT NULL,
      `form57` varchar(255) DEFAULT NULL,
      `form58` varchar(255) DEFAULT NULL,
      `form59` varchar(255) DEFAULT NULL,
      `form60` varchar(255) DEFAULT '',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE,
      KEY `bid` (`bid`) USING BTREE,
      KEY `formid` (`formid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

}


if(getcustom('zhaopin')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_zhaopin` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `status` tinyint(1) DEFAULT '0' COMMENT '0 待审核 1已审核 2拒绝',
      `num` int(11) DEFAULT '0',
      `age` varchar(255) DEFAULT '',
      `sex` tinyint(1) DEFAULT '0',
      `experience` varchar(255) DEFAULT '',
      `welfare` varchar(255) DEFAULT '',
      `salary` varchar(255) DEFAULT '',
      `form_order_id` int(11) DEFAULT '0',
      `thumb` text,
      `title` varchar(255) DEFAULT '',
      `cid` int(11) DEFAULT '0',
      `cname` varchar(255) DEFAULT '',
      `desc` text,
      `createtime` int(11) DEFAULT NULL,
      `mid` int(11) DEFAULT '0',
      `education` varchar(255) DEFAULT '',
      `readnum` int(11) DEFAULT '0',
      `form_content` text,
      `area` varchar(255) DEFAULT '',
      `address` varchar(255) DEFAULT '',
      `latitude` varchar(128) DEFAULT '',
      `longitude` varchar(128) DEFAULT '',
      `endtime` int(11) DEFAULT NULL,
      `top_area` text COMMENT '置顶城市',
      `top_feetype` tinyint(2) DEFAULT '0' COMMENT '置顶方式 1全国 2区域',
      `top_endtime` int(11) DEFAULT NULL,
      `top_starttime` int(11) DEFAULT NULL,
      `apply_id` int(11) DEFAULT '0',
      `assurance_id` int(11) DEFAULT '0' COMMENT '担保id',
      `audit_time` int(11) DEFAULT NULL,
      `audit_remark` varchar(255) DEFAULT '',
      `vip_orderid` int(11) DEFAULT '0',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_zhaopin_apply` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `status` tinyint(1) DEFAULT '0' COMMENT '0 待审核 1已审核 2拒绝',
      `pics` text,
      `name` varchar(255) DEFAULT '',
      `tel` varchar(32) DEFAULT '',
      `createtime` int(11) DEFAULT NULL,
      `mid` int(11) DEFAULT '0',
      `remark` varchar(255) DEFAULT NULL,
      `audit_time` int(11) DEFAULT NULL,
      `audit_remark` varchar(255) DEFAULT '' COMMENT '审核备注',
      `company` varchar(255) DEFAULT '',
      `apply_fee` decimal(10,2) DEFAULT '0.00',
      `use_apply_fee` decimal(10,2) DEFAULT '0.00',
      `assurance_fee` decimal(10,2) DEFAULT '0.00' COMMENT '保证金',
      `pscore` int(11) DEFAULT '0',
      `cardno` varchar(32) DEFAULT '',
      `zhaopin_id` int(11) DEFAULT '0' COMMENT '发布哪个招聘认证的',
      `vip_orderid` int(11) DEFAULT '0',
      `assurance_num` int(11) DEFAULT '0' COMMENT '已担保人数',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_zhaopin_assurance` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `createtime` int(11) DEFAULT NULL,
      `mid` int(11) DEFAULT '0',
      `apply_id` int(11) DEFAULT '0',
      `zhaopin_id` int(11) DEFAULT '0',
      `fee` decimal(10,2) DEFAULT '0.00',
      `status` tinyint(2) DEFAULT '1' COMMENT '1担保中 2担保完成 3担保关闭',
      `remark` varchar(255) DEFAULT '' COMMENT '备注',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_zhaopin_assurancefee_order` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `mid` int(11) DEFAULT NULL,
      `createtime` int(11) DEFAULT NULL,
      `endtime` int(11) DEFAULT NULL,
      `ordernum` varchar(100) DEFAULT '',
      `status` tinyint(2) DEFAULT '0' COMMENT '0 待支付 1已支付 2已退款',
      `remark` varchar(255) DEFAULT '' COMMENT '备注',
      `payorderid` int(11) DEFAULT '0',
      `paytypeid` int(11) DEFAULT '0',
      `paytype` varchar(50) DEFAULT '',
      `paynum` varchar(255) DEFAULT '',
      `paytime` int(11) DEFAULT NULL,
      `totalprice` decimal(10,2) DEFAULT '0.00',
      `platform` varchar(64) DEFAULT '',
      `uid` int(11) DEFAULT '0' COMMENT '操作付款人',
      `message` varchar(255) DEFAULT '',
      `refund_reason` varchar(255) DEFAULT NULL,
      `refund_money` decimal(11,2) DEFAULT '0.00',
      `refund_status` int(1) DEFAULT '0' COMMENT '1申请退款审核中 2已同意退款 3已驳回',
      `refund_time` int(11) DEFAULT NULL,
      `refund_checkremark` varchar(255) DEFAULT NULL,
      `apply_id` int(11) DEFAULT '0' COMMENT '关联id',
      `title` varchar(255) DEFAULT '',
      `refund_com` varchar(255) DEFAULT NULL,
      `audit_remark` varchar(255) DEFAULT '',
      `audit_time` int(11) DEFAULT NULL,
      `top_orderid` int(11) DEFAULT '0',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE,
      KEY `mid` (`mid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_zhaopin_category` (
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
      KEY `pid` (`pid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_zhaopin_message` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `mid` int(11) DEFAULT NULL,
      `uid` int(11) DEFAULT '0',
      `nickname` varchar(255) DEFAULT NULL,
      `headimg` varchar(255) DEFAULT NULL,
      `unickname` varchar(255) DEFAULT NULL,
      `uheadimg` varchar(255) DEFAULT NULL,
      `tel` varchar(255) DEFAULT NULL,
      `msgtype` varchar(255) DEFAULT NULL,
      `content` text,
      `mediaid` varchar(255) DEFAULT NULL,
      `createtime` int(11) DEFAULT NULL,
      `isreply` tinyint(1) DEFAULT '0' COMMENT '0 用户发的 1商家回复的',
      `isread` tinyint(1) DEFAULT '0',
      `platform` varchar(100) DEFAULT 'mp',
      `iswx` tinyint(1) DEFAULT '0',
      `tablename` varchar(64) DEFAULT NULL,
      `tableid` int(11) DEFAULT '0',
      `bmid` int(11) DEFAULT '0',
      `tomid` int(11) DEFAULT '0',
      `tbtype` tinyint(2) DEFAULT '0',
      `zid` int(11) DEFAULT '0',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE,
      KEY `bid` (`bid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_zhaopin_qiuzhi` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `status` tinyint(1) DEFAULT '0' COMMENT '0 待审核 1已审核 2拒绝',
      `thumb` text,
      `title` varchar(255) DEFAULT '',
      `name` varchar(255) DEFAULT '',
      `age` varchar(255) DEFAULT '',
      `tel` varchar(32) DEFAULT '',
      `sex` tinyint(1) DEFAULT '0' COMMENT '1男 2女',
      `has_job` tinyint(1) DEFAULT '2' COMMENT '1 1在职 2离职',
      `experience` varchar(255) DEFAULT '',
      `birthday` varchar(255) DEFAULT '',
      `salary` varchar(255) DEFAULT '',
      `form_order_id` int(11) DEFAULT '0',
      `cids` varchar(255) DEFAULT '',
      `cnames` varchar(255) DEFAULT '',
      `desc` text,
      `createtime` int(11) DEFAULT NULL,
      `mid` int(11) DEFAULT '0',
      `education` varchar(255) DEFAULT '',
      `address` varchar(255) DEFAULT '',
      `area` varchar(255) DEFAULT '',
      `readnum` int(11) DEFAULT '0',
      `form_content` text,
      `secret_type` tinyint(2) DEFAULT '0' COMMENT '0 公开 1手机号加密 2手机号+图片',
      `tags` varchar(255) DEFAULT '',
      `endtime` int(11) DEFAULT NULL,
      `top_area` text COMMENT '置顶城市',
      `top_feetype` tinyint(2) DEFAULT '0' COMMENT '置顶方式 1全国 2区域',
      `top_endtime` int(11) DEFAULT NULL,
      `top_starttime` int(11) DEFAULT NULL,
      `audit_time` int(11) DEFAULT NULL,
      `audit_remark` varchar(255) DEFAULT '',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_zhaopin_qiuzhi_apply` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `status` tinyint(1) DEFAULT '0' COMMENT '0 待审核 1已审核 2拒绝 3下架',
      `pics` text,
      `name` varchar(255) DEFAULT '',
      `tel` varchar(32) DEFAULT '',
      `createtime` int(11) DEFAULT NULL,
      `mid` int(11) DEFAULT '0',
      `remark` varchar(255) DEFAULT NULL,
      `audit_time` int(11) DEFAULT NULL,
      `audit_remark` varchar(255) DEFAULT '' COMMENT '审核备注',
      `cardno` varchar(64) DEFAULT '',
      `qianyue_id` int(11) DEFAULT '0',
      `pscore` int(11) DEFAULT '0',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_zhaopin_qiuzhi_qianyue` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `status` tinyint(1) DEFAULT '0' COMMENT '0 待审核 1已审核 2拒绝',
      `bid` int(11) DEFAULT '0',
      `audit_time` int(11) DEFAULT NULL,
      `audit_remark` varchar(255) DEFAULT '',
      `apply_id` int(11) DEFAULT '0',
      `mid` int(11) DEFAULT '0',
      `formid` int(11) DEFAULT '0',
      `platform` varchar(32) DEFAULT '',
      `formcontent` text,
      `updatetime` int(11) DEFAULT NULL,
      `createtime` int(11) DEFAULT '0',
      `form0` varchar(255) DEFAULT NULL,
      `form1` varchar(255) DEFAULT NULL,
      `form2` varchar(255) DEFAULT NULL,
      `form3` varchar(255) DEFAULT NULL,
      `form4` varchar(255) DEFAULT NULL,
      `form5` varchar(255) DEFAULT NULL,
      `form6` varchar(255) DEFAULT NULL,
      `form7` varchar(255) DEFAULT NULL,
      `form8` varchar(255) DEFAULT NULL,
      `form9` varchar(255) DEFAULT NULL,
      `form10` varchar(255) DEFAULT NULL,
      `form11` varchar(255) DEFAULT NULL,
      `form12` varchar(255) DEFAULT NULL,
      `form13` varchar(255) DEFAULT NULL,
      `form14` varchar(255) DEFAULT NULL,
      `form15` varchar(255) DEFAULT NULL,
      `form16` varchar(255) DEFAULT NULL,
      `form17` varchar(255) DEFAULT NULL,
      `form18` varchar(255) DEFAULT NULL,
      `form19` varchar(255) DEFAULT NULL,
      `form20` varchar(255) DEFAULT NULL,
      `form21` varchar(255) DEFAULT NULL,
      `form22` varchar(255) DEFAULT NULL,
      `form23` varchar(255) DEFAULT NULL,
      `form24` varchar(255) DEFAULT NULL,
      `form25` varchar(255) DEFAULT NULL,
      `form26` varchar(255) DEFAULT NULL,
      `form27` varchar(255) DEFAULT NULL,
      `form28` varchar(255) DEFAULT NULL,
      `form29` varchar(255) DEFAULT NULL,
      `form30` varchar(255) DEFAULT NULL,
      `form31` varchar(255) DEFAULT NULL,
      `form32` varchar(255) DEFAULT NULL,
      `form33` varchar(255) DEFAULT NULL,
      `form34` varchar(255) DEFAULT NULL,
      `form35` varchar(255) DEFAULT NULL,
      `form36` varchar(255) DEFAULT NULL,
      `form37` varchar(255) DEFAULT NULL,
      `form38` varchar(255) DEFAULT NULL,
      `form39` varchar(255) DEFAULT NULL,
      `form40` varchar(255) DEFAULT NULL,
      `form41` varchar(255) DEFAULT NULL,
      `form42` varchar(255) DEFAULT NULL,
      `form43` varchar(255) DEFAULT NULL,
      `form44` varchar(255) DEFAULT NULL,
      `form45` varchar(255) DEFAULT NULL,
      `form46` varchar(255) DEFAULT NULL,
      `form47` varchar(255) DEFAULT NULL,
      `form48` varchar(255) DEFAULT NULL,
      `form49` varchar(255) DEFAULT NULL,
      `form50` varchar(255) DEFAULT NULL,
      `form51` varchar(255) DEFAULT NULL,
      `form52` varchar(255) DEFAULT NULL,
      `form53` varchar(255) DEFAULT NULL,
      `form54` varchar(255) DEFAULT NULL,
      `form55` varchar(255) DEFAULT NULL,
      `form56` varchar(255) DEFAULT NULL,
      `form57` varchar(255) DEFAULT NULL,
      `form58` varchar(255) DEFAULT NULL,
      `form59` varchar(255) DEFAULT NULL,
      `form60` varchar(255) DEFAULT NULL,
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_zhaopin_qiuzhi_top_order` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `mid` int(11) DEFAULT NULL,
      `createtime` int(11) DEFAULT NULL,
      `endtime` int(11) DEFAULT NULL,
      `ordernum` varchar(100) DEFAULT '',
      `status` tinyint(2) DEFAULT '0' COMMENT '0结算等待中 1已完成 2挂单 10已退款',
      `remark` varchar(255) DEFAULT '' COMMENT '备注',
      `payorderid` int(11) DEFAULT '0',
      `paytypeid` int(11) DEFAULT '0',
      `paytype` varchar(50) DEFAULT '',
      `paynum` varchar(255) DEFAULT '',
      `paytime` int(11) DEFAULT NULL,
      `totalprice` decimal(10,2) DEFAULT '0.00',
      `platform` varchar(64) DEFAULT '',
      `uid` int(11) DEFAULT '0' COMMENT '操作付款人',
      `message` varchar(255) DEFAULT '',
      `refund_reason` varchar(255) DEFAULT NULL,
      `refund_money` decimal(11,2) DEFAULT '0.00',
      `refund_status` int(1) DEFAULT '0' COMMENT '1申请退款审核中 2已同意退款 3已驳回',
      `refund_time` int(11) DEFAULT NULL,
      `refund_checkremark` varchar(255) DEFAULT NULL,
      `top_area` text COMMENT '置顶城市',
      `top_feetype` tinyint(2) DEFAULT '0' COMMENT '置顶方式 1全国 2区域',
      `top_duration` int(11) DEFAULT '0',
      `related_id` int(11) DEFAULT '0' COMMENT '关联id',
      `title` varchar(255) DEFAULT '',
      `assurance_total` decimal(11,2) DEFAULT '0.00',
      `is_assurance` tinyint(2) DEFAULT '0' COMMENT '是否同步担保',
      `assurance_num` int(11) DEFAULT '0',
      `assurance_per_fee` decimal(11,2) DEFAULT '0.00',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE,
      KEY `mid` (`mid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_zhaopin_record` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `status` tinyint(1) DEFAULT '0' COMMENT '0 待审核 1已审核 2拒绝',
      `mid` int(11) DEFAULT NULL,
      `zhaopin_id` int(11) DEFAULT NULL,
      `qiuzhi_id` int(11) DEFAULT '0',
      `createtime` int(11) DEFAULT NULL,
      `confirm_time` int(11) DEFAULT NULL,
      `mianshi_time` int(11) DEFAULT NULL,
      `ruzhi_time` int(11) DEFAULT NULL,
      `zhaopin_content` text,
      `qiuzhi_content` text,
      `contract_pics` longtext,
      `contract_time` int(11) DEFAULT NULL,
      `contract_status` tinyint(2) DEFAULT '0',
      `audit_time` int(11) DEFAULT NULL,
      `audit_remark` varchar(255) DEFAULT '',
      `desc` text,
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_zhaopin_set` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `content` longtext,
      `name` varchar(255) DEFAULT '',
      `tag` varchar(255) DEFAULT '',
      `createtime` int(11) DEFAULT NULL,
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_zhaopin_top_order` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `mid` int(11) DEFAULT NULL,
      `createtime` int(11) DEFAULT NULL,
      `endtime` int(11) DEFAULT NULL,
      `ordernum` varchar(100) DEFAULT '',
      `status` tinyint(2) DEFAULT '0' COMMENT '0结算等待中 1已完成 2挂单 10已退款',
      `remark` varchar(255) DEFAULT '' COMMENT '备注',
      `payorderid` int(11) DEFAULT '0',
      `paytypeid` int(11) DEFAULT '0',
      `paytype` varchar(50) DEFAULT '',
      `paynum` varchar(255) DEFAULT '',
      `paytime` int(11) DEFAULT NULL,
      `totalprice` decimal(10,2) DEFAULT '0.00',
      `platform` varchar(64) DEFAULT '',
      `uid` int(11) DEFAULT '0' COMMENT '操作付款人',
      `message` varchar(255) DEFAULT '',
      `refund_reason` varchar(255) DEFAULT NULL,
      `refund_money` decimal(11,2) DEFAULT '0.00',
      `refund_status` int(1) DEFAULT '0' COMMENT '1申请退款审核中 2已同意退款 3已驳回',
      `refund_time` int(11) DEFAULT NULL,
      `refund_checkremark` varchar(255) DEFAULT NULL,
      `top_area` text COMMENT '置顶城市',
      `top_feetype` tinyint(2) DEFAULT '0' COMMENT '置顶方式 1全国 2区域',
      `top_duration` int(11) DEFAULT '0',
      `related_id` int(11) DEFAULT '0' COMMENT '关联id',
      `title` varchar(255) DEFAULT '',
      `assurance_total` decimal(10,2) DEFAULT '0.00' COMMENT '担保费用',
      `is_assurance` tinyint(1) DEFAULT '0' COMMENT '是否同步担保',
      `assurance_num` int(11) DEFAULT '0',
      `assurance_per_fee` decimal(10,2) DEFAULT '0.00',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE,
      KEY `mid` (`mid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_zhaopin_vip_order` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `mid` int(11) DEFAULT NULL,
      `createtime` int(11) DEFAULT NULL,
      `endtime` int(11) DEFAULT NULL,
      `ordernum` varchar(100) DEFAULT '',
      `status` tinyint(2) DEFAULT '0' COMMENT '0结算等待中 1已完成 2挂单 10已退款',
      `remark` varchar(255) DEFAULT '' COMMENT '备注',
      `payorderid` int(11) DEFAULT '0',
      `paytypeid` int(11) DEFAULT '0',
      `paytype` varchar(50) DEFAULT '',
      `paynum` varchar(255) DEFAULT '',
      `paytime` int(11) DEFAULT NULL,
      `totalprice` decimal(10,2) DEFAULT '0.00',
      `platform` varchar(64) DEFAULT '',
      `uid` int(11) DEFAULT '0' COMMENT '操作付款人',
      `message` varchar(255) DEFAULT '',
      `refund_reason` varchar(255) DEFAULT NULL,
      `refund_money` decimal(11,2) DEFAULT '0.00',
      `refund_status` int(1) DEFAULT '0' COMMENT '1申请退款审核中 2已同意退款 3已驳回',
      `refund_time` int(11) DEFAULT NULL,
      `refund_checkremark` varchar(255) DEFAULT NULL,
      `zhaopin_id` int(11) DEFAULT '0' COMMENT '关联id',
      `title` varchar(255) DEFAULT '',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE,
      KEY `mid` (`mid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_zhaopin_send` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `mid` int(11) DEFAULT '0',
      `zhaopin_id` varchar(32) DEFAULT '0',
      `tomid` int(11) DEFAULT NULL,
      `pic` varchar(255) DEFAULT '',
      `title` varchar(255) DEFAULT '',
      `desc` varchar(255) DEFAULT '',
      `createtime` int(11) DEFAULT NULL,
      `qiuzhi_id` int(11) DEFAULT '0',
      `isread` tinyint(2) DEFAULT '0',
      `type` tinyint(1) DEFAULT '1' COMMENT '1求职 2招聘',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_zhaopin_agent` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `status` tinyint(1) DEFAULT '0' COMMENT '0 待审核 1已审核 2拒绝',
      `bid` int(11) DEFAULT '0',
      `audit_time` int(11) DEFAULT NULL,
      `audit_remark` varchar(255) DEFAULT '',
      `district` varchar(32) DEFAULT '',
      `city` varchar(32) DEFAULT '',
      `province` varchar(32) DEFAULT '',
      `level` tinyint(2) DEFAULT '1' COMMENT '代理层级',
      `name` varchar(64) DEFAULT '' COMMENT '代理姓名',
      `tel` varchar(32) DEFAULT '',
      `area` varchar(255) DEFAULT '' COMMENT '代理区域',
      `mid` int(11) DEFAULT '0',
      `formid` int(11) DEFAULT '0',
      `platform` varchar(32) DEFAULT '',
      `formcontent` text,
      `updatetime` int(11) DEFAULT NULL,
      `createtime` int(11) DEFAULT '0',
      `form0` varchar(255) DEFAULT NULL,
      `form1` varchar(255) DEFAULT NULL,
      `form2` varchar(255) DEFAULT NULL,
      `form3` varchar(255) DEFAULT NULL,
      `form4` varchar(255) DEFAULT NULL,
      `form5` varchar(255) DEFAULT NULL,
      `form6` varchar(255) DEFAULT NULL,
      `form7` varchar(255) DEFAULT NULL,
      `form8` varchar(255) DEFAULT NULL,
      `form9` varchar(255) DEFAULT NULL,
      `form10` varchar(255) DEFAULT NULL,
      `form11` varchar(255) DEFAULT NULL,
      `form12` varchar(255) DEFAULT NULL,
      `form13` varchar(255) DEFAULT NULL,
      `form14` varchar(255) DEFAULT NULL,
      `form15` varchar(255) DEFAULT NULL,
      `form16` varchar(255) DEFAULT NULL,
      `form17` varchar(255) DEFAULT NULL,
      `form18` varchar(255) DEFAULT NULL,
      `form19` varchar(255) DEFAULT NULL,
      `form20` varchar(255) DEFAULT NULL,
      `form21` varchar(255) DEFAULT NULL,
      `form22` varchar(255) DEFAULT NULL,
      `form23` varchar(255) DEFAULT NULL,
      `form24` varchar(255) DEFAULT NULL,
      `form25` varchar(255) DEFAULT NULL,
      `form26` varchar(255) DEFAULT NULL,
      `form27` varchar(255) DEFAULT NULL,
      `form28` varchar(255) DEFAULT NULL,
      `form29` varchar(255) DEFAULT NULL,
      `form30` varchar(255) DEFAULT NULL,
      `form31` varchar(255) DEFAULT NULL,
      `form32` varchar(255) DEFAULT NULL,
      `form33` varchar(255) DEFAULT NULL,
      `form34` varchar(255) DEFAULT NULL,
      `form35` varchar(255) DEFAULT NULL,
      `form36` varchar(255) DEFAULT NULL,
      `form37` varchar(255) DEFAULT NULL,
      `form38` varchar(255) DEFAULT NULL,
      `form39` varchar(255) DEFAULT NULL,
      `form40` varchar(255) DEFAULT NULL,
      `form41` varchar(255) DEFAULT NULL,
      `form42` varchar(255) DEFAULT NULL,
      `form43` varchar(255) DEFAULT NULL,
      `form44` varchar(255) DEFAULT NULL,
      `form45` varchar(255) DEFAULT NULL,
      `form46` varchar(255) DEFAULT NULL,
      `form47` varchar(255) DEFAULT NULL,
      `form48` varchar(255) DEFAULT NULL,
      `form49` varchar(255) DEFAULT NULL,
      `form50` varchar(255) DEFAULT NULL,
      `form51` varchar(255) DEFAULT NULL,
      `form52` varchar(255) DEFAULT NULL,
      `form53` varchar(255) DEFAULT NULL,
      `form54` varchar(255) DEFAULT NULL,
      `form55` varchar(255) DEFAULT NULL,
      `form56` varchar(255) DEFAULT NULL,
      `form57` varchar(255) DEFAULT NULL,
      `form58` varchar(255) DEFAULT NULL,
      `form59` varchar(255) DEFAULT NULL,
      `form60` varchar(255) DEFAULT NULL,
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    if(!pdo_fieldexists2("ddwx_zhaopin_top_order","assurance_total")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_zhaopin_top_order` ADD COLUMN `assurance_total` decimal(10, 2) DEFAULT 0 COMMENT '担保费';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_zhaopin_top_order` ADD COLUMN `is_assurance` tinyint(1) DEFAULT 0 COMMENT '是否同步担保';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_zhaopin_top_order` ADD COLUMN `assurance_num` int(11) DEFAULT 0 COMMENT '担保人数';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_zhaopin_top_order` ADD COLUMN `assurance_per_fee` decimal(10, 2) DEFAULT 0 COMMENT '担保单价';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_zhaopin_top_order` ADD COLUMN `apply_id` int(11) DEFAULT 0;");
    }

    if(!pdo_fieldexists2("ddwx_zhaopin_apply","assurance_num")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_zhaopin_apply` ADD COLUMN `assurance_num` int(11) DEFAULT 0 COMMENT '已担保的人数';");
    }

    if(!pdo_fieldexists2("ddwx_zhaopin_assurance","remark")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_zhaopin_assurance` ADD COLUMN `remark` varchar (255) DEFAULT '' COMMENT '备注';");
    }
    if(!pdo_fieldexists2("ddwx_mp_tmplset","tmpl_zhaopin_notice")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_mp_tmplset` ADD `tmpl_zhaopin_notice` varchar(255) DEFAULT NULL COMMENT '职位更新通知';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mp_tmplset` ADD `tmpl_register` varchar(255) DEFAULT NULL COMMENT '注册成功通知';");
    }
    if(!pdo_fieldexists2("ddwx_admin_user","tmpl_zhaopin_notice")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_user` ADD `tmpl_zhaopin_notice` tinyint(1) DEFAULT '1' COMMENT '招聘求职通知';");
    }
    if(!pdo_fieldexists2("ddwx_zhaopin_top_order","apply_id")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_zhaopin_top_order` ADD `apply_id` int(11) DEFAULT '0' COMMENT '认证id';");
    }
}
if(getcustom('mendian_hexiao_givemoney')){
    if(!pdo_fieldexists2("ddwx_mendian","hexiaogivepercent")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `hexiaogivepercent` decimal(10, 2) DEFAULT 0 COMMENT '核销提成比例';");
    }
    if(!pdo_fieldexists2("ddwx_mendian","hexiaogivemoney")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `hexiaogivemoney` decimal(10, 2) DEFAULT 0 COMMENT '核销提成金额';");
    }
    if(!pdo_fieldexists2("ddwx_mendian","withdrawfee")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `withdrawfee` decimal(10, 2) DEFAULT 0 COMMENT '手续费';");
    }
    if(!pdo_fieldexists2("ddwx_mendian","money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `money` decimal(10, 2) DEFAULT 0 COMMENT '收入金额' ;");
    }
    if(!pdo_fieldexists2("ddwx_mendian","withdrawmin")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `withdrawmin` decimal(10, 2) DEFAULT 0 COMMENT '最低提现金额' ;");
    }
    if(!pdo_fieldexists2("ddwx_mendian","business_canuse")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `business_canuse` tinyint(1) DEFAULT 0;");
    }

    if(!pdo_fieldexists2("ddwx_mendian","weixin")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD `weixin` varchar(255) DEFAULT NULL");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD `aliaccount` varchar(255) DEFAULT NULL");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD `aliaccountname` varchar(255) DEFAULT NULL");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD `bankname` varchar(255) DEFAULT NULL");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD `bankcarduser` varchar(255) DEFAULT NULL");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD `bankcardnum` varchar(100) DEFAULT NULL");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_mendian_moneylog`  (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) NOT NULL DEFAULT 0,
	  `mdid` int(11) NOT NULL DEFAULT 0,
	  `orderid` int(11) NOT NULL DEFAULT 0,
	  `money` decimal(11, 2) NOT NULL DEFAULT 0.00,
	  `after` decimal(11, 2) NOT NULL DEFAULT 0.00,
	  `createtime` int(11) NOT NULL DEFAULT 0,
	  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `mid` int(11) NOT NULL DEFAULT 0,
	  PRIMARY KEY (`id`) USING BTREE,
	  INDEX `aid`(`aid`) USING BTREE,
	  INDEX `mid`(`mdid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_mendian_withdrawlog`  (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) NULL DEFAULT NULL,
	  `bid` int(11) NULL DEFAULT NULL,
	  `mid` int(11) NOT NULL DEFAULT 0 COMMENT '会员id',
	  `mdid` int(11) NOT NULL DEFAULT 0 COMMENT '门店id',
	  `money` decimal(11, 2) NULL DEFAULT NULL,
	  `txmoney` decimal(11, 2) NULL DEFAULT NULL,
	  `weixin` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `aliaccount` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `ordernum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `paytype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `status` tinyint(1) NULL DEFAULT 0,
	  `createtime` int(11) NULL DEFAULT NULL,
	  `bankname` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `bankcarduser` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `bankcardnum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `paytime` int(11) NULL DEFAULT NULL,
	  `paynum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `reason` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `platform` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
	  `wx_package_info` text NULL COMMENT '微信商家转账确认页package信息',
	  `wx_transfer_bill_no` varchar(100) NULL DEFAULT '' COMMENT '微信转账单号，微信商家转账系统返回的唯一标识',
	  `wx_transfer_msg` varchar(255) NULL DEFAULT NULL COMMENT '微信转账错误信息',
	  `wx_state`  varchar(50) NULL DEFAULT NULL COMMENT '微信商家转账状态\r\nACCEPTED: 转账已受理\r\nPROCESSING: 转账处理中，转账结果尚未明确，如一直处于此状态，建议检查账户余额是否足够\r\nWAIT_USER_CONFIRM: 待收款用户确认，可拉起微信收款确认页面进行收款确认\r\nTRANSFERING: 转账结果尚未明确，可拉起微信收款确认页面再次重试确认收款\r\nSUCCESS: 转账成功\r\nFAIL: 转账失败\r\nCANCELING: 商户撤销请求受理成功，该笔转账正在撤销中\r\nCANCELLED: 转账撤销完成',
	  PRIMARY KEY (`id`) USING BTREE,
	  INDEX `aid`(`aid`) USING BTREE,
	  INDEX `bid`(`bid`) USING BTREE,
	  INDEX `status`(`status`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    if(!pdo_fieldexists2("ddwx_mendian_withdrawlog","aliaccountname")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian_withdrawlog` ADD COLUMN `aliaccountname` varchar(255) DEFAULT NULL AFTER `weixin`");
    }
    if(!pdo_fieldexists2("ddwx_mendian_moneylog","bid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian_moneylog` ADD COLUMN `bid` int(11) NOT NULL DEFAULT 0;");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","hexiaogivepercent")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` 
            ADD COLUMN `hexiaogivepercent` decimal(5, 2) NULL COMMENT '门店核销提成比例',
            ADD COLUMN `hexiaogivemoney` decimal(11, 2) NULL COMMENT '门店核销提成金额' AFTER `hexiaogivepercent`;");
    }
}
if(pdo_fieldexists3('ddwx_mendian_moneylog')){
    \think\facade\Db::execute("ALTER TABLE `ddwx_mendian_moneylog` MODIFY COLUMN `remark`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL AFTER `createtime`,DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
}

if(getcustom('article_reward')){
    if(!pdo_fieldexists2("ddwx_article_set","reward_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_article_set` ADD COLUMN `reward_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '打赏状态 0：关闭 1：开启';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_article_set` ADD COLUMN `money_data` varchar(255) NOT NULL DEFAULT '' COMMENT '打赏金额';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_article_set` ADD COLUMN `score_data` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '打赏积分';");
    }
    if(!pdo_fieldexists2("ddwx_article","mid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_article` ADD COLUMN `mid` int NOT NULL DEFAULT 0;");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_article_reward_order`  (
	  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	  `aid` int(11) NOT NULL DEFAULT 0,
	  `bid` int(11) NOT NULL DEFAULT 0,
	  `ordernum` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '订单号',
	  `mid` int(11) NOT NULL DEFAULT 0 COMMENT '用户id',
	  `send_mid` int(11) NOT NULL DEFAULT 0 COMMENT '打赏用户id',
	  `artid` int(11) NOT NULL DEFAULT 0 COMMENT '文章id',
	  `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '类型1：余额 2积分',
	  `num` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '打赏数量',
	  `payorderid` int(11) NOT NULL DEFAULT 0,
	  `paytypeid` int(11) NOT NULL DEFAULT 0,
	  `paytype` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
	  `paynum` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
	  `paytime` int(11) UNSIGNED NOT NULL DEFAULT 0,
	  `platform` varchar(50) NOT NULL DEFAULT '',
	  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态0：待支付 1：已支付',
	  `createtime` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
	  PRIMARY KEY (`id`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}
if(getcustom('maidan_qrcode')){
    if(!pdo_fieldexists2("ddwx_member_level","maidan_zt_ratio")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `maidan_zt_ratio` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '直推人扫码分成(%)';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `maidan_zt_payother_ratio` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '直推人间接消费分成(%)';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","maidan_nzt_payself_ratio")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `maidan_nzt_payself_ratio` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '非直推人收款分成(%)';");
    }
    if(!pdo_fieldexists2("ddwx_maidan_order","ymid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_maidan_order` ADD COLUMN `ymid` int NOT NULL DEFAULT 0 COMMENT '业务员id' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_maidan_order` ADD COLUMN `isfenhong` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否分红 0：否 1：是';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_maidan_qrcode`  (
	  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	  `aid` int(11) NOT NULL DEFAULT 0,
	  `bid` int(11) NOT NULL DEFAULT 0,
	  `mid` int(11) NOT NULL DEFAULT 0,
	  `qrcode` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT 'h5收款码',
	  `wxqrcode` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '小程序收款码',
	  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态0：关闭 1：开启',
	  `createtime` int(11) UNSIGNED NOT NULL DEFAULT 0,
	  `updatetime` int(11) UNSIGNED NOT NULL DEFAULT 0,
	  PRIMARY KEY (`id`) USING BTREE,
	  INDEX `mid`(`mid`) USING BTREE,
	  INDEX `bid`(`bid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    if(!pdo_fieldexists2("ddwx_maidan_qrcode","name")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_maidan_qrcode` ADD COLUMN `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '姓名';");
    }
}

if(getcustom('business_agent')){
    if(!pdo_fieldexists2("ddwx_member_level","business_zt_ratio")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `business_zt_ratio` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '直推商家分成(%)';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `business_jt_ratio` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '间推商家分成(%)';");
    }

    if(!pdo_fieldexists2("ddwx_admin_set", "tjbusiness_show")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `tjbusiness_show` tinyint(1) UNSIGNED NULL DEFAULT '0' COMMENT '推荐商家：1显示，0隐藏';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set", "tjbusiness_jiesuan_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `tjbusiness_jiesuan_type` tinyint(1) UNSIGNED NULL DEFAULT '0' COMMENT '推荐商家结算方式：0按结算金额,1按平台抽成金额';");
    }
}

if(getcustom('maidan_fenhong')){
    if(!pdo_fieldexists2("ddwx_maidan_order","isfenhong")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_maidan_order` ADD COLUMN `isfenhong` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否分红 0：否 1：是';");
    }
}

if(getcustom('pay_daifu')){
    if(!pdo_fieldexists2("ddwx_admin_set", "pay_daifu")) {
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD `pay_daifu` tinyint(1) DEFAULT 0 COMMENT '好友代付';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set", "pay_daifu_desc")) {
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD `pay_daifu_desc` text COMMENT '好友代付说明';");
    }
    if(!pdo_fieldexists2("ddwx_payorder", "paymid")) {
        \think\facade\Db::execute("ALTER TABLE ddwx_payorder ADD `paymid` int(11) DEFAULT 0 COMMENT '代付人mid';");
    }
    if(!pdo_fieldexists2("ddwx_payorder", "pid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_payorder` ADD COLUMN `pid`  int(11) NULL DEFAULT 0 COMMENT '代付的父级订单id';");
    }

}
if(getcustom('levelup_code') || getcustom('levelup_bg')){
    if(!pdo_fieldexists2("ddwx_member_level", "apply_code")) {
        \think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD COLUMN `apply_code` varchar(50) NOT NULL DEFAULT '' COMMENT '申请验证码';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_level_bgset`  (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NOT NULL,
		`title` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '标题',
		`level_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '等级名称',
		`bgcolor` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '背景颜色',
		`bgimg` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '背景图片',
		`create_time` int(11) NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `aid`(`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}

if(getcustom('paypal')){
    if(!pdo_fieldexists2("ddwx_admin_setapp_h5", "paypal")) {
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `paypal` tinyint(1) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `paypal_clientid` varchar(255) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `paypal_clientsecret` varchar(255) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_payorder ADD COLUMN `money_usd` float(11,2) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `paypal` tinyint(1) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `paypal_clientid` varchar(255) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `paypal_clientsecret` varchar(255) DEFAULT NULL;");
    }
}

if(getcustom('other_money')){
    if(!pdo_fieldexists2("ddwx_member", "money2")) {
        \think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `money2` decimal(11, 2) NOT NULL DEFAULT 0 COMMENT '余额2'");
        \think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `money3` decimal(11, 2) NOT NULL DEFAULT 0 COMMENT '余额3'");
        \think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `money4` decimal(11, 2) NOT NULL DEFAULT 0 COMMENT '余额4'");
        \think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `money5` decimal(11, 2) NOT NULL DEFAULT 0 COMMENT '余额5'");
        \think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `frozen_money` decimal(11, 2) NOT NULL DEFAULT 0 COMMENT '冻结金额'");
    }
    if(!pdo_fieldexists2("ddwx_admin", "othermoney_status")) {
        \think\facade\Db::execute("ALTER TABLE ddwx_admin ADD COLUMN `othermoney_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '多账户 0：关闭 1：开启'");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_othermoneylog`  (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NULL DEFAULT NULL,
		`mid` int(11) NULL DEFAULT NULL,
		`money` decimal(11, 2) NULL DEFAULT 0.00,
		`after` decimal(11, 2) NULL DEFAULT 0.00,
		`createtime` int(11) NULL DEFAULT NULL,
		`remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
		`type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '类型0、frozen_money  2、money2 3、money3 4、money4 5、money5',
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `aid`(`aid`) USING BTREE,
		INDEX `mid`(`mid`) USING BTREE,
		INDEX `type`(`type`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_otherwithdrawlog`  (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NULL DEFAULT NULL,
		`mid` int(11) NULL DEFAULT NULL,
		`money` decimal(11, 2) NULL DEFAULT NULL,
		`txmoney` decimal(11, 2) NULL DEFAULT NULL,
		`aliaccount` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`aliaccountname` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '支付宝姓名',
		`ordernum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`paytype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`status` tinyint(1) NULL DEFAULT 0 COMMENT '0审核中，1已审核，2已驳回，3已打款',
		`createtime` int(11) NULL DEFAULT NULL,
		`bankname` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`bankcarduser` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`bankcardnum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`paytime` int(11) NULL DEFAULT NULL,
		`paynum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`platform` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'wx',
		`reason` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '类型2、money2 3、money3 4、money4 5、money5',
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `aid`(`aid`) USING BTREE,
		INDEX `mid`(`mid`) USING BTREE,
		INDEX `createtime`(`createtime`) USING BTREE,
		INDEX `status`(`status`) USING BTREE,
		INDEX `type`(`type`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

}
if(getcustom('member_archives')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_archives` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `mid` int(11) DEFAULT '0',
	  `name` varchar(255) DEFAULT NULL,
	  `content` longtext,
	  `sort` int(11) DEFAULT '0',
	  `createtime` int(11) DEFAULT NULL,
	  `status` int(11) DEFAULT '1',
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE,
	  KEY `status` (`status`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}

if(getcustom('wxpay_member_level')){
    if(!pdo_fieldexists2("ddwx_admin_set", "wxpay_gettj")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `wxpay_gettj` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '-1' COMMENT '微信支付使用角色';");
    }
}
if(getcustom('lipinka_jihuo')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_lipin_code_jihuo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `hid` int(11) DEFAULT NULL,
  `codeid` int(11) DEFAULT NULL,
  `cardno` varchar(30) NULL,
  `code` varchar(100) DEFAULT NULL,
  `paytype` varchar(30) NULL,
  `mid` int(11) DEFAULT NULL,
  `headimg` varchar(255) DEFAULT NULL,
  `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `tel` varchar(255) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`) USING BTREE,
  KEY `hid` (`hid`) USING BTREE,
  KEY `codeid` (`codeid`) USING BTREE,
  KEY `cardno` (`cardno`) USING BTREE,
  KEY `code` (`code`) USING BTREE,
  KEY `tel` (`tel`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    if(!pdo_fieldexists2("ddwx_lipin_code_jihuo", "money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_lipin_code_jihuo`
ADD COLUMN `money` decimal(10, 2) NULL DEFAULT 0 AFTER `tel`,
ADD COLUMN `pic` varchar(255) NULL AFTER `money`;");
    }
}
if(getcustom('ngmm')){
    if(!pdo_fieldexists2("ddwx_shop_sysset", "detail_guangao1")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `detail_guangao1` varchar(255) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `detail_guangao1_t` varchar(255) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `detail_guangao2` varchar(255) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `detail_guangao2_t` varchar(255) DEFAULT NULL;");
    }
    if(!pdo_fieldexists2("ddwx_collage_sysset", "detail_guangao1")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_collage_sysset` ADD COLUMN `detail_guangao1` varchar(255) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_collage_sysset` ADD COLUMN `detail_guangao1_t` varchar(255) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_collage_sysset` ADD COLUMN `detail_guangao2` varchar(255) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_collage_sysset` ADD COLUMN `detail_guangao2_t` varchar(255) DEFAULT NULL;");
    }
}

if(getcustom('pay_transfer')){
    if(!pdo_fieldexists2("ddwx_admin_set","pay_transfer_gettj")){
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `pay_transfer_gettj` varchar(255) DEFAULT '-1';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","pay_transfer_qrcode")){
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `pay_transfer_qrcode` text NULL;");
    }
}
if(getcustom('product_glass')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_glass_set` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT '0',
      `bid` int(11) DEFAULT '0',
      `content` longtext,
      `name` varchar(255) DEFAULT '',
      `tag` varchar(255) DEFAULT '',
      `createtime` int(11) DEFAULT NULL,
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_glass_record` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT '0',
      `bid` int(11) DEFAULT '0',
      `name` varchar(255) DEFAULT NULL,
      `desc` text,
      `status` int(1) DEFAULT '1',
      `sort` int(11) DEFAULT '1',
      `createtime` int(11) DEFAULT NULL,
      `degress_left` int(6) DEFAULT NULL COMMENT '左眼度数',
      `degress_right` int(6) DEFAULT NULL COMMENT '右眼读书',
      `ipd` int(10) DEFAULT '0' COMMENT '瞳距',
      `correction_left` decimal(6,1) DEFAULT NULL COMMENT '左眼矫正视力',
      `correction_right` decimal(6,1) DEFAULT NULL COMMENT '右眼矫正视力',
      `is_ats` tinyint(1) DEFAULT NULL COMMENT '是否散光',
      `ats_left` int(6) DEFAULT NULL COMMENT '散光左眼',
      `ats_right` int(6) DEFAULT NULL COMMENT '散光右眼',
      `ats_zleft` int(6) DEFAULT NULL COMMENT '散光轴位左眼',
      `ats_zright` int(6) DEFAULT NULL COMMENT '散光轴位右眼',
      `mid` int(11) DEFAULT '0',
      `type` tinyint(1) DEFAULT '1' COMMENT '1近视 2远视',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_order_glass_record` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT '0',
      `bid` int(11) DEFAULT '0',
      `name` varchar(255) DEFAULT NULL,
      `desc` text,
      `createtime` int(11) DEFAULT NULL,
      `degress_left` int(6) DEFAULT NULL COMMENT '左眼度数',
      `degress_right` int(6) DEFAULT NULL COMMENT '右眼读书',
      `ipd` int(10) DEFAULT '0' COMMENT '瞳距',
      `correction_left` decimal(6,1) DEFAULT NULL COMMENT '左眼矫正视力',
      `correction_right` decimal(6,1) DEFAULT NULL COMMENT '右眼矫正视力',
      `is_ats` tinyint(1) DEFAULT NULL COMMENT '是否散光',
      `ats_left` int(6) DEFAULT NULL COMMENT '散光左眼',
      `ats_right` int(6) DEFAULT NULL COMMENT '散光右眼',
      `ats_zleft` int(6) DEFAULT NULL COMMENT '散光轴位左眼',
      `ats_zright` int(6) DEFAULT NULL COMMENT '散光轴位右眼',
      `mid` int(11) DEFAULT '0',
      `type` tinyint(1) DEFAULT '1' COMMENT '1近视 2远视',
      `glass_record_id` int(11) DEFAULT NULL,
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    if(!pdo_fieldexists2("ddwx_shop_cart","glass_record_id")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_cart` ADD COLUMN `glass_record_id` int(1) DEFAULT '0' COMMENT '视力档案id';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order_goods","glass_record_id")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `glass_record_id` int(1) DEFAULT '0' COMMENT '视力档案id';");
    }
    if(!pdo_fieldexists2("ddwx_glass_record","nickname")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_glass_record`
            MODIFY COLUMN `degress_left`  decimal(6,2) NULL DEFAULT NULL COMMENT '左眼度数',
            MODIFY COLUMN `degress_right`  decimal(6,2) NULL DEFAULT NULL COMMENT '右眼读书',
            MODIFY COLUMN `ipd`  decimal(6,2) NULL DEFAULT 0 COMMENT '瞳距',
            MODIFY COLUMN `ats_left`  decimal(6,2) NULL DEFAULT NULL COMMENT '散光左眼',
            MODIFY COLUMN `ats_right`  decimal(6,2) NULL DEFAULT NULL COMMENT '散光右眼',
            ADD COLUMN `add_right`  decimal(6,2) NULL DEFAULT NULL COMMENT '下加光右眼',
            ADD COLUMN `add_left`  decimal(6,2) NULL DEFAULT NULL COMMENT '下加光左眼',
            ADD COLUMN `nickname`  varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
            ADD COLUMN `age`  int(11) NULL DEFAULT NULL AFTER `nickname`,
            ADD COLUMN `sex`  tinyint(1) NULL DEFAULT 0 AFTER `age`,
            ADD COLUMN `tel`  varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' AFTER `sex`,
            ADD COLUMN `check_time`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `tel`,
            ADD COLUMN `remark`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' AFTER `check_time`;"
        );
    }
    if(!pdo_fieldexists2("ddwx_order_glass_record","nickname")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_order_glass_record`
            MODIFY COLUMN `degress_left`  decimal(6,2) NULL DEFAULT NULL COMMENT '左眼度数',
            MODIFY COLUMN `degress_right`  decimal(6,2) NULL DEFAULT NULL COMMENT '右眼读书',
            MODIFY COLUMN `ipd`  decimal(6,2) NULL DEFAULT 0 COMMENT '瞳距',
            MODIFY COLUMN `ats_left`  decimal(6,2) NULL DEFAULT NULL COMMENT '散光左眼',
            MODIFY COLUMN `ats_right`  decimal(6,2) NULL DEFAULT NULL COMMENT '散光右眼',
            ADD COLUMN `add_right`  decimal(6,2) NULL DEFAULT NULL COMMENT '下加光右眼',
            ADD COLUMN `add_left`  decimal(6,2) NULL DEFAULT NULL COMMENT '下加光左眼',
            ADD COLUMN `order_goods_id`  int(11) NULL DEFAULT '0' COMMENT '订单商品表',
            ADD COLUMN `nickname`  varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
            ADD COLUMN `age`  int(11) NULL DEFAULT NULL AFTER `nickname`,
            ADD COLUMN `sex`  tinyint(1) NULL DEFAULT 0 AFTER `age`,
            ADD COLUMN `tel`  varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' AFTER `sex`,
            ADD COLUMN `check_time`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `tel`,
            ADD COLUMN `remark`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' AFTER `check_time`;"
        );
    }
    if(!pdo_fieldexists2("ddwx_glass_record","edittime")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_glass_record`
            MODIFY COLUMN `degress_left`  varchar(32) NULL DEFAULT 0.00 COMMENT '左眼度数' AFTER `createtime`,
            MODIFY COLUMN `degress_right`  varchar(32) NULL DEFAULT 0.00 COMMENT '右眼读书' AFTER `degress_left`,
            MODIFY COLUMN `ipd` int(11) NULL DEFAULT 0 COMMENT '瞳距',
            MODIFY COLUMN `ats_left`  varchar(32) NULL DEFAULT 0.00 COMMENT '散光左眼' AFTER `is_ats`,
            MODIFY COLUMN `ats_right`  varchar(32) NULL DEFAULT 0.00 COMMENT '散光右眼' AFTER `ats_left`,
            ADD COLUMN `edittime`  int(11) NULL DEFAULT NULL COMMENT '更新时间';"
        );
    }
    if(!pdo_fieldexists2("ddwx_order_glass_record","edittime")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_order_glass_record`
            MODIFY COLUMN `degress_left`  varchar(32) NULL DEFAULT 0.00 COMMENT '左眼度数' AFTER `createtime`,
            MODIFY COLUMN `degress_right`  varchar(32) NULL DEFAULT 0.00 COMMENT '右眼读书' AFTER `degress_left`,
            MODIFY COLUMN `ipd` int(11) NULL DEFAULT 0 COMMENT '瞳距',
            MODIFY COLUMN `ats_left`  varchar(32) NULL DEFAULT 0.00 COMMENT '散光左眼' AFTER `is_ats`,
            MODIFY COLUMN `ats_right`  varchar(32) NULL DEFAULT 0.00 COMMENT '散光右眼' AFTER `ats_left`,
            ADD COLUMN `edittime`  int(11) NULL DEFAULT NULL COMMENT '更新时间';"
        );
    }


    if(!pdo_fieldexists2("ddwx_glass_record","ipd_right")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_glass_record`
            ADD COLUMN `ipd_right`  int(11) NULL DEFAULT 0 COMMENT '右眼瞳距',
            ADD COLUMN `ipd_left`  int(11) NULL DEFAULT 0 COMMENT '左眼瞳距',
            ADD COLUMN `double_ipd`  tinyint(1) NULL DEFAULT 0 COMMENT '是否双眼瞳距',
            MODIFY COLUMN `ats_zleft`  int(6) NULL DEFAULT 0 COMMENT '散光轴位左眼',
            MODIFY COLUMN `ats_zright`  int(6) NULL DEFAULT 0 COMMENT '散光轴位右眼',
            MODIFY COLUMN `degress_left`  decimal(6,2) NULL DEFAULT NULL COMMENT '左眼度数',
            MODIFY COLUMN `degress_right`  decimal(6,2) NULL DEFAULT 0 COMMENT '右眼读书',
            MODIFY COLUMN `correction_left` decimal(6,1) DEFAULT 0 COMMENT '左眼矫正视力',
            MODIFY COLUMN `correction_right` decimal(6,1) DEFAULT 0 COMMENT '右眼矫正视力',
            MODIFY COLUMN `add_right`  decimal(6,2) NULL DEFAULT 0 COMMENT '下加光右眼',
            MODIFY COLUMN `add_left`  decimal(6,2) NULL DEFAULT 0 COMMENT '下加光左眼',
            MODIFY COLUMN `ipd`  decimal(6,2) NULL DEFAULT 0 COMMENT '瞳距',
            MODIFY COLUMN `ats_left`  decimal(6,2) NULL DEFAULT 0 COMMENT '散光左眼',
            MODIFY COLUMN `ats_right`  decimal(6,2) NULL DEFAULT 0 COMMENT '散光右眼';"
        );
    }
    if(!pdo_fieldexists2("ddwx_order_glass_record","ipd_right")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_order_glass_record`
            ADD COLUMN `ipd_right`  int(11) NULL DEFAULT 0 COMMENT '右眼瞳距',
            ADD COLUMN `ipd_left`  int(11) NULL DEFAULT 0 COMMENT '左眼瞳距',
            ADD COLUMN `double_ipd`  tinyint(1) NULL DEFAULT 0 COMMENT '是否双眼瞳距',
            MODIFY COLUMN `ats_zleft`  int(6) NULL DEFAULT 0 COMMENT '散光轴位左眼',
            MODIFY COLUMN `ats_zright`  int(6) NULL DEFAULT 0 COMMENT '散光轴位右眼',
            MODIFY COLUMN `degress_left`  decimal(6,2) NULL DEFAULT NULL COMMENT '左眼度数',
            MODIFY COLUMN `degress_right`  decimal(6,2) NULL DEFAULT 0 COMMENT '右眼读书',
            MODIFY COLUMN `correction_left` decimal(6,1) DEFAULT 0 COMMENT '左眼矫正视力',
            MODIFY COLUMN `correction_right` decimal(6,1) DEFAULT 0 COMMENT '右眼矫正视力',
            MODIFY COLUMN `add_right`  decimal(6,2) NULL DEFAULT 0 COMMENT '下加光右眼',
            MODIFY COLUMN `add_left`  decimal(6,2) NULL DEFAULT 0 COMMENT '下加光左眼',
            MODIFY COLUMN `ipd`  decimal(6,2) NULL DEFAULT 0 COMMENT '瞳距',
            MODIFY COLUMN `ats_left`  decimal(6,2) NULL DEFAULT 0 COMMENT '散光左眼',
            MODIFY COLUMN `ats_right`  decimal(6,2) NULL DEFAULT 0 COMMENT '散光右眼';"
        );
    }

    if(!pdo_fieldexists2("ddwx_order_glass_record","isupdate1")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_order_glass_record`
MODIFY COLUMN `ipd`  decimal(10,2) NULL DEFAULT 0 COMMENT '瞳距',
MODIFY COLUMN `ipd_right`  decimal(10,2) NULL DEFAULT 0 COMMENT '右瞳距',
MODIFY COLUMN `ipd_left`  decimal(10,2) NULL DEFAULT 0 COMMENT '左瞳距',
ADD COLUMN `isupdate1`  int(11) NULL DEFAULT 0;"
        );
    }
    if(!pdo_fieldexists2("ddwx_glass_record","isupdate1")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_glass_record`
MODIFY COLUMN `ipd`  decimal(10,2) NULL DEFAULT 0 COMMENT '瞳距',
MODIFY COLUMN `ipd_right`  decimal(10,2) NULL DEFAULT 0 COMMENT '右瞳距',
MODIFY COLUMN `ipd_left`  decimal(10,2) NULL DEFAULT 0 COMMENT '左瞳距',
ADD COLUMN `isupdate1`  int(11) NULL DEFAULT 0;"
        );
    }

}
if(getcustom('product_jialiao')){

    if(!pdo_fieldexists2("ddwx_restaurant_shop_cart","jlprice")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_cart` ADD COLUMN `jlprice` decimal(10,2) DEFAULT 0.00 ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_cart` ADD COLUMN `jltitle` varchar(255) DEFAULT '' ;");
    }

    if(!pdo_fieldexists2("ddwx_restaurant_shop_order_goods","jlprice")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order_goods` ADD COLUMN `jlprice` decimal(10,2) DEFAULT 0.00 ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order_goods` ADD COLUMN `jltitle` varchar(255) DEFAULT '' ;");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_takeaway_cart","jlprice")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_cart` ADD COLUMN `jlprice` decimal(10,2) DEFAULT 0.00 ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_cart` ADD COLUMN `jltitle` varchar(255) DEFAULT '' ;");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_takeaway_order_goods","jlprice")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_order_goods` ADD COLUMN `jlprice` decimal(10,2) DEFAULT 0.00 ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_order_goods` ADD COLUMN `jltitle` varchar(255) DEFAULT '' ;");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_product","jialiaodata")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product` ADD COLUMN `jialiaodata` longtext  ;");
    }
}
if(getcustom('skip_levelup')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_level_bgset`  (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NOT NULL,
		`title` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '标题',
		`level_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '等级名称',
		`bgcolor` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '背景颜色',
		`bgimg` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '背景图片',
		`create_time` int(11) NOT NULL DEFAULT 0,
        `skip_level` tinyint(1) DEFAULT '0' COMMENT '是否按顺序升级 0 是 1可跨级',
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `aid`(`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    if(!pdo_fieldexists2("ddwx_member_level_bgset","skip_level")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level_bgset` ADD COLUMN `skip_level` tinyint(1) DEFAULT '0' COMMENT '是否按顺序升级 0 是 1可跨级';");
    }
}

if(getcustom('form_other_money')){
    if(!pdo_fieldexists2("ddwx_form","fee_items")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `fee_items`  text NULL COMMENT '费用明细';");
    }
    if(!pdo_fieldexists2("ddwx_form_order","fee_items")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD COLUMN `fee_items`  text NULL COMMENT '费用明细';");
    }
}
if(getcustom('ext_give_score')){
    if(!pdo_fieldexists2("ddwx_luntan_sysset","add_give_score")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_luntan_sysset`
        ADD COLUMN `add_give_score`  int(11) NULL DEFAULT 0,
        ADD COLUMN `day_give_score`  int(11) NULL DEFAULT 0 AFTER `add_give_score`;");
    }
    if(!pdo_fieldexists2("ddwx_article_set","read_give_score")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_article_set`
        ADD COLUMN `read_give_score`  int(11) NULL DEFAULT 0,
        ADD COLUMN `mid_give_score`  int(11) NULL DEFAULT 0 AFTER `read_give_score`,
        ADD COLUMN `day_give_score`  int(11) NULL DEFAULT 0 AFTER `mid_give_score`;");
    }
    if(!pdo_fieldexists2("ddwx_luntan_sysset","pinglun_give_score")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_luntan_sysset`
        ADD COLUMN `pinglun_give_score`  int(11) NULL DEFAULT 0;");
    }

    if(!pdo_fieldexists2("ddwx_article","read_give_score")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_article`
        ADD COLUMN `read_give_score`  varchar(32) NULL DEFAULT '';");
    }

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_ext_givescore_record`  (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) DEFAULT NULL,
        `mid` int(11) DEFAULT NULL,
        `from_table` varchar(32) DEFAULT '',
        `from_id` int(11) DEFAULT NULL,
        `score` int(11) DEFAULT NULL,
        `createtime` int(11) DEFAULT NULL,
        `type` varchar(32) DEFAULT '',
        PRIMARY KEY (`id`) USING BTREE,
        KEY `aid_mid` (`aid`,`mid`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

}
if(getcustom('with_system_param')){
    if(!pdo_fieldexists2("ddwx_admin","with_system_param")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `with_system_param`  tinyint(1) NULL DEFAULT 0 COMMENT '外链参数，外部链接是否携带系统aid和mid';");
    }
    if(!pdo_fieldexists2("ddwx_admin","system_param_key")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `system_param_key`  varchar (255) NULL DEFAULT '' COMMENT '外链参数加密key';");
    }
}
if(getcustom('form_attachment_alias')){
    if(!pdo_fieldexists2("ddwx_form","attachment_alias_type")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_form`
        ADD COLUMN `attachment_alias_type`  tinyint(1) NULL DEFAULT 0,
        ADD COLUMN `attachment_alias`  varchar(64) NULL DEFAULT '' COMMENT '自定义附件名称' AFTER `attachment_alias_type`;");
    }
}
if(getcustom('school_product')){
    if(!pdo_fieldexists2("ddwx_member_levelup_order","school_id")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_levelup_order` ADD COLUMN `school_id` int(11) NULL DEFAULT 0,
        ADD COLUMN `grade_id` int(11) NULL DEFAULT 0 AFTER `school_id`,
        ADD COLUMN `class_id` int(11) NULL DEFAULT 0 AFTER `grade_id`;");
    }
    if(!pdo_fieldexists2("ddwx_shop_order","school_id")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `school_id` int(11) NULL DEFAULT 0,
        ADD COLUMN `grade_id` int(11) NULL DEFAULT 0 AFTER `school_id`,
        ADD COLUMN `class_id` int(11) NULL DEFAULT 0 AFTER `grade_id`;");
    }
    if(!pdo_fieldexists2("ddwx_admin","need_school")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `need_school` tinyint(1) NULL DEFAULT 0;");
    }

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_school` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `aid` int(11) DEFAULT '0',
          `name` varchar(255) DEFAULT '',
          `number` varchar(32) DEFAULT '',
          `createtime` int(11) DEFAULT NULL,
          `sort` int(6) DEFAULT '0',
          PRIMARY KEY (`id`),
          KEY `aid` (`aid`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_school_class` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT '0',
      `name` varchar(255) DEFAULT '',
      `number` varchar(32) DEFAULT '',
      `createtime` int(11) DEFAULT NULL,
      `sort` int(6) DEFAULT '0',
      `pid` int(11) DEFAULT '0',
      `sid` int(11) DEFAULT '0' COMMENT '学校id',
      PRIMARY KEY (`id`),
      KEY `aid` (`aid`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    if(!pdo_fieldexists2("ddwx_shop_order","member_content")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `member_content`  text NULL COMMENT '当前会员等级资料';");
    }

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_school_member` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `aid` int(11) DEFAULT NULL,
          `mid` int(11) DEFAULT NULL,
          `school_id` int(11) DEFAULT '0',
          `grade_id` int(11) DEFAULT '0',
          `class_id` int(11) DEFAULT '0',
          `level_order_id` int(11) DEFAULT '0',
          `levelid` int(11) DEFAULT '0',
          `updatetime` int(11) DEFAULT NULL,
          PRIMARY KEY (`id`) USING BTREE,
          KEY `aid` (`aid`) USING BTREE,
          KEY `mid` (`mid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    if(!pdo_fieldexists2("ddwx_school","is_refund")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_school` ADD COLUMN `is_refund` tinyint(1) NULL DEFAULT 1 COMMENT '是否允许退款',ADD COLUMN `refund_tips` varchar (255) NULL DEFAULT '' COMMENT '退款提示';");
    }
}
if(getcustom('project')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_project` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT '',
  `content` longtext,
  `readcount` int(11) DEFAULT '0',
  `sort` int(11) DEFAULT '0',
  `createtime` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT '1',
  `reason` varchar(255) DEFAULT NULL,
  `mid` int(11) NOT NULL DEFAULT '0',
  `pic` varchar(255) DEFAULT '',
  `tel` varchar(20) DEFAULT '',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `sort` (`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_project_record` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT '',
  `content` longtext,
  `readcount` int(11) DEFAULT '0',
  `createtime` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT '1',
  `reason` varchar(255) DEFAULT NULL,
  `mid` int(11) NOT NULL DEFAULT '0',
  `pic` varchar(255) DEFAULT '',
  `tel` varchar(20) DEFAULT '',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `pid` (`pid`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");

    if(!pdo_fieldexists2("ddwx_project","tips_uncheck")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_project`
ADD COLUMN `tips_uncheck` varchar(255) NULL AFTER `tel`,
ADD COLUMN `tips_unupload` varchar(255) NULL AFTER `tips_uncheck`;");
    }
}
if(getcustom('commissionranking')){
    if(!pdo_fieldexists2("ddwx_admin_set","rank_status")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `rank_status` int(11) NULL DEFAULT 0,
		ADD COLUMN `rank_type` varchar(100) NULL DEFAULT NULL,
		ADD COLUMN `rank_date` int(11) NULL DEFAULT 1,
		ADD COLUMN `rank_people` int(11) NULL DEFAULT 0;");
    }
}

if(getcustom('member_tag')){
    if(!pdo_fieldexists2("ddwx_member","tags")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `tags` varchar(255) DEFAULT NULL;");
    }
	if(!pdo_fieldexists2("ddwx_member","buynum")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `buynum` int(11) DEFAULT 0;");
    }
	if(!pdo_fieldexists2("ddwx_member","buymoney")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `buymoney` float(11,2) DEFAULT 0;");
    }
    if(!pdo_fieldexists2("ddwx_lucky_collage_product","tags")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product` ADD COLUMN `tags` varchar(255) DEFAULT '' COMMENT '中奖标签',
			ADD COLUMN `istag` int(11) DEFAULT 0;");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_tag` (
	 `id` int(11) NOT NULL AUTO_INCREMENT,
	  `name` varchar(255) DEFAULT NULL COMMENT '标签名称',
	  `type` int(11) DEFAULT '1' COMMENT '1自动标签 2 是手动打标签',
	  `condition` varchar(255) DEFAULT '' COMMENT 'or 或 and',
	  `conditiontext` varchar(1000) DEFAULT '' COMMENT '详细条件设置',
	  `createtime` int(11) DEFAULT NULL,
	  `status` int(11) DEFAULT '1' COMMENT '0',
	  `aid` int(11) DEFAULT '1',
	  `sort` int(11) DEFAULT '0' COMMENT '排序',
	  `mindays` int(11) DEFAULT NULL COMMENT '注册时间',
	  `levelid` int(11) DEFAULT NULL COMMENT '会员等级',
	  `buynum` int(11) DEFAULT '0' COMMENT '购买次数',
	  `buymoney` float(11,2) DEFAULT '0.00' COMMENT '消费金额',
	  `productids` varchar(255) DEFAULT NULL COMMENT '指定商品',
	  `maxdays` int(11) DEFAULT '0',
	   `regdatestatus` tinyint(1) DEFAULT '0' COMMENT '注册时间状态，默认为0 为开启',
	  `levelstatus` tinyint(1) DEFAULT '0' COMMENT '等级状态，默认为0 未开启',
	  `buystatus` tinyint(1) DEFAULT '0' COMMENT '消费次数状态，默认为0 未开启',
	  `buymoneystatus` tinyint(1) DEFAULT '0' COMMENT '消费金额状态，默认为0 未开启',
	  `prostatus` tinyint(1) DEFAULT '0' COMMENT '消费指定产品的状态，默认为0 未开启',
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;");
}
if(getcustom('price_dollar')){
    if(!pdo_fieldexists2("ddwx_shop_sysset","usdrate")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `usdrate` decimal(11,2) DEFAULT '0.00';");
    }
}
if(getcustom('toupiao_pay_score')){
    if(!pdo_fieldexists2("ddwx_toupiao","pay_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_toupiao`
        ADD COLUMN `pay_score` int(11) DEFAULT '0',
        ADD COLUMN `pay_type`  tinyint(1) NULL DEFAULT 0 COMMENT '1 积分 2余额',
        ADD COLUMN `pay_money`  decimal(10,2) NULL DEFAULT 0 AFTER `pay_type`,
        ADD COLUMN `fanwei`  tinyint(1) NULL DEFAULT 0 AFTER `pay_money`,
        ADD COLUMN `fanwei_lng`  varchar(100) DEFAULT NULL AFTER `fanwei`,
        ADD COLUMN `fanwei_lat`  varchar(100) DEFAULT NULL AFTER `fanwei_lng`,
        ADD COLUMN `fanwei_range`  varchar(100) DEFAULT NULL AFTER `fanwei_lat`;");
    }
    if(!pdo_fieldexists2("ddwx_toupiao","toupiaotj")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_toupiao` ADD COLUMN `toupiaotj` varchar(255) DEFAULT '-1';");
    }
    if(!pdo_fieldexists2("ddwx_toupiao","pay_not_enough")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_toupiao`
        ADD COLUMN `pay_not_enough` varchar(255) DEFAULT '',
        ADD COLUMN `pay_not_enough_url` varchar(255) DEFAULT '';");
    }
}
if(getcustom('usd_sellprice')){
    if(!pdo_fieldexists2("ddwx_shop_order","usd_totalprice")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN  `usd_totalprice` decimal(10,2) DEFAULT '0.00' COMMENT '美元价格';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order_goods","usd_sellprice")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN  `usd_sellprice` float(11,2) DEFAULT '0.00' COMMENT '美元价格';");
    }
    if(!pdo_fieldexists2("ddwx_seckill_order","usd_sellprice")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_seckill_order` ADD COLUMN  `usd_sellprice` float(11,2) DEFAULT '0.00' COMMENT '美元价格',
		ADD COLUMN `usd_totalprice` float(11,2) DEFAULT '0.00';");
    }
    if(!pdo_fieldexists2("ddwx_tuangou_order","usd_productprice")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_tuangou_order` ADD COLUMN  `usd_productprice` float(11,2) DEFAULT '0.00' COMMENT '美元价格',
		ADD COLUMN `usd_totalprice` float(11,2) DEFAULT '0.00';");
    }
}
if(getcustom('return_component')){
    if(!pdo_fieldexists2("ddwx_shop_sysset","return_name")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset`
            ADD COLUMN `return_name`  varchar(32) NULL DEFAULT '',
            ADD COLUMN `return_tel`  varchar(32) NULL DEFAULT '' AFTER `return_name`,
            ADD COLUMN `return_province`  varchar(32) NULL DEFAULT '' AFTER `return_tel`,
            ADD COLUMN `return_city`  varchar(32) NULL DEFAULT '' AFTER `return_province`,
            ADD COLUMN `return_area`  varchar(32) NULL DEFAULT '' AFTER `return_city`,
            ADD COLUMN `return_address`  varchar(255) NULL DEFAULT '' AFTER `return_area`;"
        );
    }
    if(!pdo_fieldexists2("ddwx_business","return_name")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business`
            ADD COLUMN `return_name`  varchar(32) NULL DEFAULT '',
            ADD COLUMN `return_tel`  varchar(32) NULL DEFAULT '' AFTER `return_name`,
            ADD COLUMN `return_province`  varchar(32) NULL DEFAULT '' AFTER `return_tel`,
            ADD COLUMN `return_city`  varchar(32) NULL DEFAULT '' AFTER `return_province`,
            ADD COLUMN `return_area`  varchar(32) NULL DEFAULT '' AFTER `return_city`,
            ADD COLUMN `return_address`  varchar(255) NULL DEFAULT '' AFTER `return_area`;"
        );
    }
    if(!pdo_fieldexists2("ddwx_shop_refund_order","return_name")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_refund_order`
            ADD COLUMN `return_name`  varchar(32) NULL DEFAULT '',
            ADD COLUMN `return_tel`  varchar(32) NULL DEFAULT '' AFTER `return_name`,
            ADD COLUMN `return_province`  varchar(32) NULL DEFAULT '' AFTER `return_tel`,
            ADD COLUMN `return_city`  varchar(32) NULL DEFAULT '' AFTER `return_province`,
            ADD COLUMN `return_area`  varchar(32) NULL DEFAULT '' AFTER `return_city`,
            ADD COLUMN `return_address`  varchar(255) NULL DEFAULT '' AFTER `return_area`,
            ADD COLUMN `return_id`  varchar(32) NULL DEFAULT '' AFTER `return_address`;"
        );
    }

    if(!pdo_fieldexists2("ddwx_shop_sysset","return_component_open")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset`
            ADD COLUMN `return_component_open` tinyint(1) NULL DEFAULT '0';"
        );
    }
}

if(getcustom('other_money')){
    if(!pdo_fieldexists2("ddwx_admin_set","othermoney_withdraw")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `othermoney_withdraw` tinyint(1) NOT NULL DEFAULT 0 COMMENT '其他金额提现0：否 1：是';");
    }
}
if(getcustom('price_show_type')){
    if(!pdo_fieldexists2("ddwx_shop_sysset","price_show_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `price_show_type` tinyint(1) DEFAULT '0';");
    }
}
if(getcustom('task_banner')){
    if(!pdo_fieldexists2("ddwx_member","task_banner_total")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `task_banner_total` tinyint(1) DEFAULT '0';");
    }

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_task_banner_log` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT '0',
	  `mid` int(11) DEFAULT '0',
	  `complete_date` varchar(20) DEFAULT NULL,
	  `count` int(11) DEFAULT '0' COMMENT '今日总数量',
	  `times` int(11) DEFAULT '0' COMMENT '完成今日数量',
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_task_banner_set` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `everyday_complete_num` int(11) DEFAULT '0' COMMENT '每日完成数量',
	  `complete_count` int(11) DEFAULT '0' COMMENT '每日完成数量的次数',
	  `total_complete_num` int(11) DEFAULT '0',
	  `choujiang_id` int(11) DEFAULT '0' COMMENT '抽奖ID',
	  `aid` int(11) DEFAULT '0',
	  `rewardedvideoad` varchar(255) DEFAULT NULL,
	  `gettj` varchar(255) DEFAULT '',
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;");

    if(pdo_fieldexists2("ddwx_member","task_banner_total")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` MODIFY COLUMN `task_banner_total`  int(11) DEFAULT '0';");
    }
}
if(getcustom('commission_frozen') || getcustom('member_level_paymoney_commissionfrozenset')){

    if(!pdo_fieldexists2("ddwx_admin_set","fuchi_percent")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
ADD COLUMN `fuchi_percent` decimal(5, 2) NULL DEFAULT '20',
ADD COLUMN `fuchi_unfrozen` varchar(60) NULL AFTER `fuchi_percent`,
ADD COLUMN `fuchi_unfrozen1_num` int(11) NULL AFTER `fuchi_unfrozen`,
ADD COLUMN `fuchi_unfrozen1_levelid` varchar(60) NULL AFTER `fuchi_unfrozen1_num`,
ADD COLUMN `fuchi_unfrozen2_levelid` varchar(60) NULL AFTER `fuchi_unfrozen1_levelid`,
ADD COLUMN `fuchi_unfrozen3_levelid` varchar(60) NULL AFTER `fuchi_unfrozen2_levelid`,
ADD COLUMN `fuchi_unfrozen_type` tinyint(1) NULL DEFAULT '1' AFTER `fuchi_unfrozen3_levelid`;");
    }
    if(pdo_fieldexists2("ddwx_admin_set","fuchi_unfrozen1_levelid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` MODIFY COLUMN `fuchi_unfrozen1_levelid` varchar(60) NULL DEFAULT NULL;");
    }
    if(pdo_fieldexists2("ddwx_admin_set","fuchi_unfrozen2_levelid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` MODIFY COLUMN `fuchi_unfrozen2_levelid` varchar(60) NULL DEFAULT NULL;");
    }
    if(pdo_fieldexists2("ddwx_admin_set","fuchi_unfrozen3_levelid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` MODIFY COLUMN `fuchi_unfrozen3_levelid` varchar(60) NULL DEFAULT NULL;");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","fuchi_levelids")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `fuchi_levelids` varchar(255) NULL DEFAULT '-1' AFTER `fuchi_percent`;");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","fuchi_only_teamfenhong")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `fuchi_only_teamfenhong` tinyint(1) DEFAULT '0' COMMENT '仅团队分红参与扶持金 0否 1是';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","fuchi_unfrozen1_ceng")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `fuchi_unfrozen1_ceng` int(10) DEFAULT '0' COMMENT '扶持金解冻条件层级';");
    }

    if(!pdo_fieldexists2("ddwx_admin","commission_frozen")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `commission_frozen` tinyint(1) NULL DEFAULT '0';");
    }

    if(!pdo_fieldexists2("ddwx_member","fuchi_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member`
    ADD COLUMN `fuchi_money` decimal(11, 2) NULL DEFAULT '0',
    ADD COLUMN `commission_frozen_status` tinyint(1) NULL DEFAULT '0' COMMENT '0冻结，1解冻' AFTER `fuchi_money`;");
    }

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_fuchi_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `frommid` int(11) DEFAULT NULL,
  `commission` decimal(11,2) DEFAULT NULL,
  `after` decimal(11,2) DEFAULT '0.00',
  `createtime` int(11) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_fuchi_record` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `frommid` int(11) DEFAULT NULL,
  `orderid` int(11) DEFAULT NULL,
  `ogid` int(11) DEFAULT NULL,
  `type` varchar(100) DEFAULT 'shop' COMMENT 'shop 商城',
  `commission` decimal(11,2) DEFAULT NULL,
  `score` int(11) DEFAULT '0',
  `remark` varchar(255) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `endtime` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}
if(getcustom('business_selfscore')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_admin_scorelog` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `score` int(11) DEFAULT '0',
	  `after` int(11) DEFAULT NULL,
	  `createtime` int(11) DEFAULT NULL,
	  `remark` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8");
}
if(getcustom('shop_categroy_limit')){
    if(!pdo_fieldexists2("ddwx_shop_category","limit_num")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_category`
ADD COLUMN `limit_num` int(11) NULL AFTER `createtime`,
ADD COLUMN `limit_day` int(11) NULL AFTER `limit_num`;");
    }
}
if(getcustom('video_speed')){
    if(!pdo_fieldexists2("ddwx_kecheng_chapter","isspeed")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_chapter` ADD COLUMN `isspeed` int(11) DEFAULT 0;");
    }
}
if(getcustom('plug_more_alipay')){
    if(!pdo_fieldexists2("ddwx_admin_setapp_h5", "alipay4")){
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname2` varchar(100) DEFAULT '支付宝支付2';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname3` varchar(100) DEFAULT '支付宝支付3';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay4` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname4` varchar(100) DEFAULT '支付宝支付4';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid4` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey4` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey4` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay5` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname5` varchar(100) DEFAULT '支付宝支付5';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid5` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey5` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey5` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay6` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname6` varchar(100) DEFAULT '支付宝支付6';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid6` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey6` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey6` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay7` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname7` varchar(100) DEFAULT '支付宝支付7';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid7` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey7` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey7` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay8` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname8` varchar(100) DEFAULT '支付宝支付8';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid8` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey8` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey8` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay9` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname9` varchar(100) DEFAULT '支付宝支付9';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid9` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey9` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey9` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay10` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname10` varchar(100) DEFAULT '支付宝支付10';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid10` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey10` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey10` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay11` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname11` varchar(100) DEFAULT '支付宝支付11';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid11` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey11` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey11` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay12` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname12` varchar(100) DEFAULT '支付宝支付12';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid12` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey12` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey12` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay13` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname13` varchar(100) DEFAULT '支付宝支付13';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid13` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey13` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey13` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay14` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname14` varchar(100) DEFAULT '支付宝支付14';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid14` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey14` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey14` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay15` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname15` varchar(100) DEFAULT '支付宝支付15';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid15` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey15` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey15` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay16` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname16` varchar(100) DEFAULT '支付宝支付16';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid16` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey16` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey16` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay17` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname17` varchar(100) DEFAULT '支付宝支付17';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid17` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey17` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey17` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay18` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname18` varchar(100) DEFAULT '支付宝支付18';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid18` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey18` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey18` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay19` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname19` varchar(100) DEFAULT '支付宝支付19';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid19` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey19` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey19` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay20` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname20` varchar(100) DEFAULT '支付宝支付20';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid20` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey20` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey20` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay21` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname21` varchar(100) DEFAULT '支付宝支付21';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid21` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey21` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey21` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay22` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname22` varchar(100) DEFAULT '支付宝支付22';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid22` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey22` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey22` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay23` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname23` varchar(100) DEFAULT '支付宝支付23';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid23` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey23` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey23` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay24` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname24` varchar(100) DEFAULT '支付宝支付24';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid24` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey24` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey24` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay25` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname25` varchar(100) DEFAULT '支付宝支付25';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid25` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey25` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey25` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay26` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname26` varchar(100) DEFAULT '支付宝支付26';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid26` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey26` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey26` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay27` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname27` varchar(100) DEFAULT '支付宝支付27';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid27` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey27` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey27` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay28` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname28` varchar(100) DEFAULT '支付宝支付28';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid28` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey28` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey28` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay29` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname29` varchar(100) DEFAULT '支付宝支付29';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid29` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey29` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey29` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay30` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipayname30` varchar(100) DEFAULT '支付宝支付30';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid30` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey30` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey30` text;");
    }
}

if(getcustom('freight_selecthxbids') || getcustom('product_quanyi')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_hexiao_shopproduct` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT NULL,
	  `uid` int(11) DEFAULT NULL,
	  `mid` int(11) DEFAULT NULL,
	  `proid` int(11) DEFAULT NULL,
	  `name` varchar(255) DEFAULT NULL,
	  `pic` varchar(255) DEFAULT NULL,
	  `ggid` int(11) DEFAULT NULL,
	  `ggname` varchar(255) DEFAULT NULL,
	  `num` int(11) DEFAULT '0',
	  `orderid` int(11) DEFAULT NULL,
	  `ordernum` varchar(100) DEFAULT NULL,
	  `ogid` int(11) DEFAULT NULL,
	  `title` varchar(255) DEFAULT NULL,
	  `createtime` int(11) DEFAULT NULL,
	  `remark` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8");
}
if(getcustom('gdfenhong_score') || getcustom('teamfenhong_score_percent') || getcustom('fenhong_score_percent')){
    if(!pdo_fieldexists2("ddwx_member_fenhonglog", "score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_fenhonglog` ADD COLUMN `score` int(11) DEFAULT 0;");
    }
}
if(getcustom('jushuitan')){
    if(!pdo_fieldexists2("ddwx_admin","jushuitan_status")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `jushuitan_status` tinyint(1) NULL DEFAULT 0;");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","jushuitankey")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `jushuitankey` varchar(60) DEFAULT NULL,
		ADD COLUMN `jushuitansecret` varchar(60) DEFAULT NULL;");
    }

    if(!pdo_fieldexists2("ddwx_admin_set","shop_id")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `shop_id` int(11) DEFAULT NULL;");
    }

}

if(getcustom('forcerebuy')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_forcerebuy` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `name` varchar(255) DEFAULT NULL COMMENT '名称',
	  `type` int(11) DEFAULT '0' COMMENT '触发条件 0佣金 1时间',
	  `commission` decimal(10,2) DEFAULT NULL,
	  `daytype` int(11) DEFAULT '0' COMMENT '时间周期 0每月 1每季度 2每年',
	  `price` decimal(10,2) DEFAULT '0.00' COMMENT '复购金额',
	  `wfgtype` int(11) DEFAULT '0' COMMENT '未复购表现 0冻结佣金 1降低等级',
	  `wfgtxtips` varchar(255) DEFAULT NULL,
	  `wfglvid` int(11) DEFAULT NULL,
	  `gettj` varchar(255) DEFAULT NULL COMMENT '参与人群',
	  `fwtype` tinyint(1) DEFAULT '0',
	  `categoryids` varchar(255) DEFAULT NULL,
	  `productids` varchar(255) DEFAULT NULL,
	  `sort` int(11) DEFAULT '0',
	  `status` int(1) DEFAULT '1',
	  `createtime` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `status` (`status`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='强制复购';");
    if(!pdo_fieldexists2("ddwx_member","commission_isfreeze")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `commission_isfreeze` tinyint(1) DEFAULT '0' AFTER `commission`;");
    }
}


if(getcustom('member_gongxian')){
    if(!pdo_fieldexists2("ddwx_admin_set","gongxianin_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
ADD COLUMN `gongxianin_money` decimal(11, 2) NULL DEFAULT '1.00',
ADD COLUMN `gognxianin_value` int(11) NULL DEFAULT '1' AFTER `gongxianin_money`;");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","gongxian_days")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
ADD COLUMN `gongxian_days` int(11) NULL DEFAULT NULL,
ADD COLUMN `gongxian_percent` decimal(5, 2) NULL DEFAULT NULL;");
    }

    if(!pdo_fieldexists2("ddwx_admin","member_gongxian_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin`
ADD COLUMN `member_gongxian_status` tinyint(1) NULL DEFAULT '0' COMMENT '贡献开关';");
    }

    if(!pdo_fieldexists2("ddwx_member","gongxian")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member`
    ADD COLUMN `gongxian` int(11) NULL DEFAULT '0' COMMENT '贡献';");
    }
    if(!pdo_fieldexists2("ddwx_member","total_fenhong_gongxian")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member`
    ADD COLUMN `total_fenhong_gongxian` decimal(11,2) NOT NULL DEFAULT '0.00' COMMENT '总贡献值分红';");
    }

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_gongxianlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` varchar(100) DEFAULT NULL,
  `value` int(11) DEFAULT '0',
  `after` int(11) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `remark` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `channel` varchar(20) DEFAULT '' COMMENT '变动渠道',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    if(!pdo_fieldexists2("ddwx_member_gongxianlog","is_expire")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_gongxianlog` 
ADD COLUMN `is_expire` tinyint(1) NULL DEFAULT 0 COMMENT '0未过期，1过期',
ADD COLUMN `expire_time` int(11) NULL,
ADD INDEX(`is_expire`);");
    }
    if(!pdo_fieldexists2("ddwx_member_gongxianlog","orderid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_gongxianlog` ADD COLUMN `orderid` int(11) NULL;");
    }
    if(!pdo_fieldexists2("ddwx_member_level","gongxian_days")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level`  ADD COLUMN `gongxian_days` int(11) DEFAULT NULL COMMENT '贡献值过期天数';");
    }

    if(!pdo_fieldexists2("ddwx_business","fenhong_member_gongxian")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `fenhong_member_gongxian` tinyint(1) NULL DEFAULT '0' COMMENT '贡献值分红：0 关闭 1：开启';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","gongxian_bonus_disable")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `gongxian_bonus_disable`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '多商户商品不参与赠送贡献值 1不参与 0参与';");
    }
}
if(getcustom('shop_yuding')){
    if(!pdo_fieldexists2("ddwx_shop_order","yuding_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `yuding_type` tinyint(1) DEFAULT '0';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","yuding_stock")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `yuding_stock` int(11) DEFAULT '0';");
    }
}
if(getcustom('member_vip_edit')){
    if(!pdo_fieldexists2("ddwx_admin_set","member_vip_no_order_days")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `member_vip_no_order_days` int(11) DEFAULT '0';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","member_no_order_expire_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `member_no_order_expire_status` tinyint(1) DEFAULT '0';");
    }
}
if(getcustom('coupon_times_expire')){
    if(!pdo_fieldexists2("ddwx_coupon","use_gap")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon`
        ADD COLUMN `use_gap`  decimal(8,2) NULL DEFAULT 0 COMMENT '使用间隔',
        ADD COLUMN `is_expire_back` tinyint(1) NULL DEFAULT 0 AFTER `use_gap`,
        ADD COLUMN `expire_back_money` decimal(10,1) NULL DEFAULT 0 AFTER `is_expire_back`;");
    }
    if(!pdo_fieldexists2("ddwx_coupon_record","reback_count")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon_record`
        ADD COLUMN `reback_count`  int(11) NULL DEFAULT 0 COMMENT '过期返回的次数',
        ADD COLUMN `reback_money`  decimal(10,2) NULL COMMENT '过期返回的余额' AFTER `reback_count`;");
    }
}
if(getcustom('coupon_times_use_gap')){
    if(!pdo_fieldexists2("ddwx_coupon","use_gap")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon`
        ADD COLUMN `use_gap`  decimal(8,2) NULL DEFAULT 0 COMMENT '使用间隔';");
    }
}
if(getcustom('scoreshop_background')) {
    if (!pdo_fieldexists2("ddwx_scoreshop_sysset", "background_pic")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_sysset`
        ADD COLUMN `background_pic` varchar(255) NULL DEFAULT '';");
    }
}
if(getcustom('usecoupon_give_money')){
    if(!pdo_fieldexists2("ddwx_coupon","usecoupon_give_commission")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` ADD COLUMN `usecoupon_give_commission` decimal(11,2) DEFAULT '0';");
    }
}
if(getcustom('member_area_agent_multi')) {
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_area_agent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT '0',
  `bid` int(11) DEFAULT '0',
  `createtime` int(11) DEFAULT NULL,
  `areafenhong_province` varchar(255) DEFAULT NULL,
  `areafenhong_city` varchar(255) DEFAULT NULL,
  `areafenhong_area` varchar(255) DEFAULT NULL,
  `areafenhong` tinyint(1) DEFAULT '0',
  `areafenhongbl` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `areafenhong_province` (`areafenhong_province`) USING BTREE,
  KEY `areafenhong_city` (`areafenhong_city`) USING BTREE,
  KEY `areafenhong_area` (`areafenhong_area`) USING BTREE,
  KEY `areafenhong` (`areafenhong`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
}

if(getcustom('goods_hexiao')) {

    if(!pdo_fieldexists2("ddwx_restaurant_takeaway_order_goods","hexiao_code")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_order_goods` ADD COLUMN `hexiao_code` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '唯一码 核销码';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_order_goods` ADD COLUMN `hexiao_qr` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '核销码图片';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_order_goods` ADD COLUMN `hexiao_num` int(11) NOT NULL DEFAULT 0;");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_hexiao_restaurantproduct` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) NOT NULL DEFAULT 0,
        `bid` int(11) NOT NULL DEFAULT 0,
        `uid` int(11) NOT NULL DEFAULT 0,
        `mid` int(11) NOT NULL DEFAULT 0,
        `proid` int(11) NOT NULL DEFAULT 0,
        `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `pic` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `ggid` int(11) NOT NULL DEFAULT 0,
        `ggname` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '50',
        `num` int(11) NOT NULL DEFAULT 0,
        `orderid` int(11) NOT NULL DEFAULT 0,
        `ordernum` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0',
        `ogid` int(11) NOT NULL DEFAULT 0,
        `title` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1：外卖',
        `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
        `createtime` int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`) USING BTREE,
        INDEX `aid`(`aid`) USING BTREE,
        INDEX `bid`(`bid`) USING BTREE,
        INDEX `mid`(`mid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_restaurant_takeaway_adv` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) NOT NULL DEFAULT 0,
        `bid` int(11) NOT NULL DEFAULT 0,
        `title` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '标题',
        `pic` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '图片',
        `link_url` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `link_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `proid` int(11) NOT NULL DEFAULT 0 COMMENT '商品id',
        `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0：关闭 1：开启',
        `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
}

if(getcustom('member_set')) {
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_set` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) NOT NULL DEFAULT 0,
        `bid` int(11) NULL DEFAULT 0,
        `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '名称',
        `content` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
        `sort` int(11) NULL DEFAULT 0,
        `createtime` int(11) NULL DEFAULT NULL,
        PRIMARY KEY (`id`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_set_log` (
        `id` bigint(20) NOT NULL AUTO_INCREMENT,
        `aid` int(11) NOT NULL DEFAULT 0,
        `bid` int(11) NOT NULL DEFAULT 0,
        `mid` int(11) NOT NULL DEFAULT 0,
        `formid` int(11) NOT NULL DEFAULT 0,
        `form0` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form1` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form2` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form3` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form4` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form5` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form6` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form7` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form8` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form9` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form10` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form11` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form12` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form13` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form14` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form15` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form16` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form17` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form18` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form19` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form20` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form21` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form22` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form23` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form24` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form25` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form26` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form27` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form28` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form29` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form30` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form31` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form32` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form33` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form34` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form35` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form36` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form37` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form38` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form39` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form40` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form41` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form42` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form43` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form44` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form45` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form46` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form47` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form48` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form49` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form50` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form51` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form52` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form53` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form54` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form55` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form56` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form57` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form58` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form59` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `form60` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '自定义表单内容',
        `createtime` int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`) USING BTREE,
        INDEX `aid`(`aid`) USING BTREE,
        INDEX `bid`(`bid`) USING BTREE,
        INDEX `formid`(`formid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");

    \think\facade\Db::execute("ALTER TABLE `ddwx_member_set_log` MODIFY COLUMN `form3` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';");
}

if(getcustom('lipinka_no')) {
    if(!pdo_fieldexists2("ddwx_lipin_set","needno")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_lipin_set` ADD COLUMN `needno` tinyint(1) DEFAULT '0';");
    }
    if(!pdo_fieldexists2("ddwx_lipin_set","dhmtxt")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_lipin_set` ADD COLUMN `dhmtxt` varchar(255) DEFAULT '兑换码'");
    }
}
if(getcustom('business_member')) {
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_business_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT '0',
  `bid` int(11) DEFAULT '0',
  `mid` int(11) DEFAULT '0',
  `createtime` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}
if(getcustom('touzi_fenhong')) {
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shareholder_moneylog` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT '0',
	  `bid` int(11) DEFAULT '0',
	  `mid` int(11) DEFAULT '0',
	  `sid` int(11) DEFAULT '0' COMMENT '股东id',
	  `money` decimal(11,2) DEFAULT '0.00',
	  `before` decimal(11,2) DEFAULT '0.00',
	  `after` decimal(11,2) DEFAULT NULL,
	  `createtime` int(11) DEFAULT '0',
	  `remark` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shareholder` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT '0',
	  `bid` int(11) DEFAULT '0',
	  `realname` varchar(255) DEFAULT NULL COMMENT '姓名',
	  `idcard` varchar(255) DEFAULT NULL COMMENT '身份证',
	  `money` decimal(10,2) DEFAULT NULL COMMENT '投资金额',
	  `mid` int(11) DEFAULT '0',
	  `feepercent` decimal(10,2) DEFAULT '0.00' COMMENT '费率',
	  `sort` int(11) DEFAULT '0',
	  `status` tinyint(1) DEFAULT '1',
	  `createtime` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    if(!pdo_fieldexists2("ddwx_admin","shareholder_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `shareholder_status` tinyint(1) DEFAULT '0' COMMENT '股东投资分红状态';");
    }
    if(!pdo_fieldexists2("ddwx_business","shareholder_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `shareholder_status` tinyint(1) DEFAULT '0' COMMENT '股东投资分红状态';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `touzi_fh_type` tinyint(1) DEFAULT '0' COMMENT '投资分红分配方式';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `touzi_fh_percent` decimal(10,2) DEFAULT '0.00' COMMENT '投资分红分配比例';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","touzi_fh_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `touzi_fh_type` tinyint(1) DEFAULT '0' COMMENT '投资分红分配方式';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `touzi_fh_percent` decimal(10,2) DEFAULT '0.00' COMMENT '投资分红分配比例'");
    }
    if(!pdo_fieldexists2("ddwx_member","total_fenhong_touzi")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `total_fenhong_touzi` decimal(11,2) NOT NULL DEFAULT '0.00';");
    }
}

if(getcustom('buybutton_custom')) {
    if(!pdo_fieldexists2("ddwx_shop_product","buybtn_name")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `buybtn_name` varchar(50) NULL COMMENT '立即购买按钮名称';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `buybtn_link_url` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '立即购买按钮链接';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `buybtn_link_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '立即购买按钮链接名称';");
    }

    if(!pdo_fieldexists2("ddwx_admin","buybtn_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `buybtn_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '商城自定义购买按钮 0：关闭 1：开启';");
    }

    if(!pdo_fieldexists2("ddwx_business","buybtn_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `buybtn_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '商城自定义购买按钮 0：关闭 1：开启';");
    }

    if(pdo_fieldexists2("ddwx_shop_product","buybtn_name")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product`
MODIFY COLUMN `buybtn_name` varchar(50) NULL COMMENT '立即购买按钮名称' ,
MODIFY COLUMN `buybtn_link_url` varchar(255) NULL DEFAULT NULL COMMENT '立即购买按钮链接' AFTER `buybtn_name`,
MODIFY COLUMN `buybtn_link_name` varchar(100) NULL DEFAULT NULL COMMENT '立即购买按钮链接名称' AFTER `buybtn_link_url`;");
    }
}

if(getcustom('addcart_button_custom')) {
    if(!pdo_fieldexists2("ddwx_shop_product","addcart_name")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product`
ADD COLUMN `addcart_name` varchar(50) NULL COMMENT '加入购物车按钮名称' ,
ADD COLUMN `addcart_link_url` varchar(255) NULL DEFAULT NULL COMMENT '加入购物车按钮链接',
ADD COLUMN `addcart_link_name` varchar(100) NULL DEFAULT NULL COMMENT '加入购物车按钮链接名称';");
    }
}
if(getcustom('up_giveparent')) {
    if(!pdo_fieldexists2("ddwx_member_level","up_with_origin")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_with_origin` tinyint(1) NOT NULL DEFAULT 0 COMMENT '脱离后继续作为上级的升级条件 1是 0否';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_with_new")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_with_new` tinyint(1) NOT NULL DEFAULT 0 COMMENT '脱离后作为新上级的升级条件 1是 0否';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_pid_changelog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT '0',
  `mid` int(11) NOT NULL DEFAULT '0',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '',
  `path` text,
  `pid_origin` int(11) DEFAULT NULL,
  `path_origin` text,
  `createtime` int(11) NOT NULL DEFAULT '0',
  `updatetime` int(11) NOT NULL DEFAULT '0',
  `isback` tinyint(1) DEFAULT '0' COMMENT '是否回归',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `pid` (`pid`) USING BTREE,
  KEY `pid_origin` (`pid_origin`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
}
if(getcustom('restaurant_category_icon')) {
    if(!pdo_fieldexists2("ddwx_restaurant_product_category","tag")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product_category` ADD COLUMN `tag` varchar(255) DEFAULT NULL COMMENT '标签';");
    }
}
if(getcustom('business_product_isexamine')) {
    if(!pdo_fieldexists2("ddwx_business","is_open_examine")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `is_open_examine` tinyint(1) DEFAULT '1' COMMENT '商品是否打开审核1：开启 0：关闭';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `is_open_commission` tinyint(1) DEFAULT '1' COMMENT '商品是否打开分销';");
    }
}
if(getcustom('lipinka_no')) {
    if(!pdo_fieldexists2("ddwx_shop_order","duihuan_cardno")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `duihuan_cardno` varchar(255) DEFAULT NULL;");
    }
    if(!pdo_fieldexists2("ddwx_scoreshop_order","duihuan_cardno")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order` ADD COLUMN `duihuan_cardno` varchar(255) DEFAULT NULL;");
    }
}
if(getcustom('lipinka_jihuo2')) {
    if(!pdo_fieldexists2("ddwx_lipin_codelist","jhstatus")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_lipin_codelist` ADD COLUMN `jhstatus` tinyint(1) DEFAULT '0';");
    }
}
if(getcustom('restaurant_duli_buy')) {
    if(!pdo_fieldexists2("ddwx_restaurant_product","duli_buy")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product` ADD COLUMN `duli_buy` tinyint(1) DEFAULT '0' COMMENT '独立购买1:开启';");
    }
}
if(getcustom('form_print')) {
    if(!pdo_fieldexists2("ddwx_form","print_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `print_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '打印状态 0：关闭 1：开启' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `printid` int NOT NULL DEFAULT 0 COMMENT '打印机' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `print_num` int NOT NULL DEFAULT 0 COMMENT '打印份数' ;");
    }
    if(!pdo_fieldexists2("ddwx_form","print_auto")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `print_auto` tinyint(1) NOT NULL DEFAULT 0 COMMENT '自动打印：0 否 1 是';");
    }
}
if(getcustom('product_payaftergive')) {
    if(!pdo_fieldexists2("ddwx_shop_product","paygive_choujiangtimes")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `paygive_choujiangtimes` int(11) DEFAULT '0'");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `paygive_choujiangid` int(11) DEFAULT '0'");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `paygive_money` float(11,2) DEFAULT '0.00'");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `paygive_score` int(11) DEFAULT '0'");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `paygive_couponid` int(11) DEFAULT '0'");
    }
}

if(getcustom('coupon_expire_notice')  || getcustom('coupon_transfer')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_coupon_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `expire_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '过期提醒状态 0：关闭 1：开启',
  `expire_rules` varchar(255) NOT NULL DEFAULT '' COMMENT '提醒规则',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `bid` (`bid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}
if(getcustom('certificate_poster')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_certificate_poster` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `name` varchar(255) DEFAULT NULL,
	  `poster_bg` varchar(255) DEFAULT NULL,
	  `poster_data` text,
	  `createtime` int(11) DEFAULT NULL,
	  `sort` int(11) DEFAULT '0',
      `explain` longtext DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `aid` (`aid`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_certificate_poster_record` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `hid` int(11) DEFAULT NULL,
	  `realname` varchar(255) DEFAULT NULL,
	  `tel` varchar(255) DEFAULT NULL,
	  `posterurl` varchar(255) DEFAULT NULL,
	  `createtime` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `aid` (`aid`),
	  KEY `hid` (`hid`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_certificate_poster_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `certificate_text` varchar(60) NOT NULL DEFAULT '' COMMENT '成绩别名',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `bid` (`bid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}
if(getcustom('cashdesk_commission')){
    if(!pdo_fieldexists2("ddwx_cashier_order_goods","parent1")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashier_order_goods` ADD COLUMN `parent1` int(11) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashier_order_goods` ADD COLUMN `parent2` int(11) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashier_order_goods` ADD COLUMN `parent3` int(11) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashier_order_goods` ADD COLUMN `parent4` int(11) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashier_order_goods` ADD COLUMN `parent1commission` decimal(11,2) DEFAULT '0.00';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashier_order_goods` ADD COLUMN `parent2commission` decimal(11,2) DEFAULT '0.00';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashier_order_goods` ADD COLUMN `parent3commission` decimal(11,2) DEFAULT '0.00';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashier_order_goods` ADD COLUMN `parent4commission` decimal(11,2) DEFAULT '0.00';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","cashdeskfenxiao")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `cashdeskfenxiao` tinyint(1) DEFAULT '0' COMMENT '收银台分销开关';");
    }
    if(!pdo_fieldexists2("ddwx_cashier_order_goods","parent1score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashier_order_goods` 
                ADD COLUMN  `parent1score` int(11) DEFAULT '0' COMMENT '一级提成积分',
                ADD COLUMN  `parent2score` int(11) DEFAULT '0' COMMENT '二级提成积分',
                ADD COLUMN  `parent3score` int(11) DEFAULT '0' COMMENT '三级提成积分';");
    }
}
if(getcustom('product_bind_mendian')){
    if(!pdo_fieldexists2("ddwx_shop_product","bind_mendian_ids")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `bind_mendian_ids`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '销售门店';");
    }
}
if(getcustom('shopshd_shuixitie')){
    if(!pdo_fieldexists2("ddwx_shop_sysset","shd_style")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `shd_style` tinyint(1) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `shd_style1_no` int(11) DEFAULT '1';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `shd_style1_no` varchar(255) DEFAULT NULL;");
    }
}
if(getcustom('admin_user_group')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_admin_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sort` int(11) DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
}

if(getcustom('yx_hbtk')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_hbtk_activity` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `bid` int(11) DEFAULT NULL,
      `aid` bigint(20) DEFAULT NULL,
      `name` varchar(255) DEFAULT '',
      `bgpic` varchar(255) DEFAULT NULL COMMENT '背景图',
      `fmpic` varchar(255) DEFAULT NULL COMMENT '封面图',
      `bgcolor` varchar(50) DEFAULT NULL,
      `status` tinyint(1) DEFAULT '1',
      `starttime` int(11) DEFAULT NULL,
      `endtime` int(11) DEFAULT NULL,
      `guize` text COMMENT '规则',
      `sharetitle` varchar(255) DEFAULT NULL COMMENT '分享标题',
      `sharelink` varchar(255) DEFAULT NULL COMMENT '分享链接',
      `sharepic` varchar(255) DEFAULT NULL,
      `sharedesc` varchar(255) DEFAULT NULL,
      `gettj` varchar(255) DEFAULT '-1' COMMENT '参与等级 1:一级',
      `fanwei` tinyint(1) DEFAULT '0' COMMENT '参与范围 ',
      `fanwei_lng` varchar(100) DEFAULT NULL,
      `fanwei_lat` varchar(100) DEFAULT NULL,
      `fanwei_range` varchar(100) DEFAULT NULL,
      `content` text COMMENT '活动内容',
      `price` decimal(10,2) DEFAULT '0.00' COMMENT '购买支付金额',
      `xn_bgnum` int(11) DEFAULT NULL COMMENT '虚拟曝光人数',
      `xn_zfnum` int(11) DEFAULT NULL COMMENT '虚拟转发人数',
      `xn_buynum` int(11) DEFAULT NULL COMMENT '虚拟购买人数',
      `xn_joinnum` int(11) DEFAULT NULL COMMENT '虚拟参与人数',
      `show_ranking` tinyint(1) DEFAULT NULL COMMENT '展示排行榜',
      `show_buylog` tinyint(1) DEFAULT '1' COMMENT '展示购买记录 1：全展示 2：仅头像 3：不展示',
      `j1mc` varchar(255) DEFAULT '一等奖' COMMENT '奖项名称',
      `j1pic` varchar(255) DEFAULT NULL,
      `j1tp` tinyint(1) DEFAULT '1',
      `j1sl` int(11) DEFAULT '5' COMMENT '奖品数量',
      `j1yj` int(11) DEFAULT '0' COMMENT '已抽中数',
      `j2mc` varchar(255) DEFAULT '二等奖' COMMENT '奖项名称',
      `j2pic` varchar(255) DEFAULT NULL,
      `j2tp` tinyint(1) DEFAULT '1',
      `j2sl` int(11) DEFAULT '10' COMMENT '奖品数量',
      `j2yj` int(11) DEFAULT '0' COMMENT '已抽中数',
      `j3mc` varchar(255) DEFAULT '三等奖' COMMENT '奖项名称',
      `j3pic` varchar(255) DEFAULT NULL,
      `j3tp` tinyint(1) DEFAULT '1',
      `j3sl` int(11) DEFAULT '30' COMMENT '奖品数量',
      `j3yj` int(11) DEFAULT '0' COMMENT '已抽中数',
      `j4mc` varchar(255) DEFAULT NULL COMMENT '奖项名称',
      `j4pic` varchar(255) DEFAULT NULL,
      `j4tp` tinyint(1) DEFAULT '1',
      `j4sl` int(11) DEFAULT NULL COMMENT '奖品数量',
      `j4yj` int(11) DEFAULT '0' COMMENT '已抽中数',
      `j5mc` varchar(255) DEFAULT NULL COMMENT '奖项名称',
      `j5pic` varchar(255) DEFAULT NULL,
      `j5tp` tinyint(1) DEFAULT '1',
      `j5sl` int(11) DEFAULT NULL COMMENT '奖品数量',
      `j5yj` int(11) DEFAULT '0' COMMENT '已抽中数',
      `j6mc` varchar(255) DEFAULT NULL COMMENT '奖项名称',
      `j6pic` varchar(255) DEFAULT NULL,
      `j6tp` tinyint(1) DEFAULT '1',
      `j6sl` int(11) DEFAULT NULL COMMENT '奖品数量',
      `j6yj` int(11) DEFAULT '0' COMMENT '已抽中数',
      `j7mc` varchar(255) DEFAULT NULL COMMENT '奖项名称',
      `j7pic` varchar(255) DEFAULT NULL,
      `j7tp` tinyint(1) DEFAULT '1',
      `j7sl` int(11) DEFAULT NULL COMMENT '奖品数量',
      `j7yj` int(11) DEFAULT '0' COMMENT '已抽中数',
      `j8mc` varchar(255) DEFAULT NULL COMMENT '奖项名称',
      `j8pic` varchar(255) DEFAULT NULL,
      `j8tp` tinyint(1) DEFAULT '1',
      `j8sl` int(11) DEFAULT NULL COMMENT '奖品数量',
      `j8yj` int(11) DEFAULT '0' COMMENT '已抽中数',
      `j9mc` varchar(255) DEFAULT NULL COMMENT '奖项名称',
      `j9pic` varchar(255) DEFAULT NULL,
      `j9tp` tinyint(1) DEFAULT '1',
      `j9sl` int(11) DEFAULT NULL COMMENT '奖品数量',
      `j9yj` int(11) DEFAULT '0' COMMENT '已抽中数',
      `j10mc` varchar(255) DEFAULT NULL,
      `j10pic` varchar(255) DEFAULT NULL,
      `j10tp` tinyint(1) DEFAULT '1',
      `j10sl` int(11) DEFAULT NULL,
      `j10yj` int(11) DEFAULT '0',
      `j11mc` varchar(255) DEFAULT NULL,
      `j11pic` varchar(255) DEFAULT NULL,
      `j11tp` tinyint(1) DEFAULT '1',
      `j11sl` int(11) DEFAULT NULL,
      `j11yj` int(11) DEFAULT '0',
      `j12mc` varchar(255) DEFAULT NULL,
      `j12pic` varchar(255) DEFAULT NULL,
      `j12tp` tinyint(1) DEFAULT '1',
      `j12sl` int(11) DEFAULT NULL,
      `j12yj` int(11) DEFAULT '0',
      `scene_id` varchar(100) DEFAULT NULL COMMENT '微信红包场景',
      `formcontent` text,
      `createtime` int(11) DEFAULT '0',
      `updatetime` int(11) DEFAULT '0',
      `viewnum` int(11) DEFAULT '0' COMMENT '浏览数',
      `color1` varchar(255) DEFAULT NULL,
      `color2` varchar(255) DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_hbtk_sharelog` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `hid` int(11) DEFAULT NULL,
      `mid` int(11) DEFAULT NULL,
      `createtime` int(11) DEFAULT '0',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS  `ddwx_hbtk_order` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `hid` int(11) DEFAULT '0' COMMENT '红包ID',
      `ordernum` varchar(255) DEFAULT NULL,
      `name` varchar(255) DEFAULT NULL,
      `mid` int(11) DEFAULT '0',
      `pid` int(11) DEFAULT '0',
      `headimg` varchar(255) DEFAULT NULL,
      `nickname` varchar(255) DEFAULT NULL,
      `status` tinyint(1) DEFAULT '0' COMMENT '状态：0未支付 1：支付 2:已核销',
      `yq_num` int(11) DEFAULT '0' COMMENT '邀请人数',
      `createtime` int(11) DEFAULT '0',
      `hexiaoqr` varchar(255) DEFAULT NULL COMMENT '核销码',
      `hxtime` int(11) DEFAULT '0' COMMENT '核销时间',
      `formdata` text,
      `jx` tinyint(4) DEFAULT NULL COMMENT '获得的奖项',
      `jxtp` tinyint(1) DEFAULT NULL COMMENT '类型 1奖品 2红包',
      `jxmc` varchar(255) DEFAULT NULL COMMENT '奖品名称',
      `jxmoney` decimal(10,0) DEFAULT '0' COMMENT '红包金额',
      `price` decimal(10,2) DEFAULT '0.00',
      `code` varchar(255) DEFAULT NULL,
      `payorderid` int(11) DEFAULT NULL,
      `paytime` int(11) DEFAULT NULL,
      `paytypeid` int(11) DEFAULT NULL,
      `paytype` varchar(50) DEFAULT NULL,
      `paynum` int(11) DEFAULT NULL,
      `platform` varchar(255) DEFAULT NULL,
      `remark` varchar(255) DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    if(!pdo_fieldexists2("ddwx_hbtk_activity","bid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_hbtk_activity` ADD COLUMN `bid` int(11) DEFAULT NULL;");
    }
    if(!pdo_fieldexists2("ddwx_hbtk_activity","color1")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_hbtk_activity` ADD COLUMN `color1` varchar(255) DEFAULT NULL;");
    }
    if(!pdo_fieldexists2("ddwx_hbtk_activity","color2")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_hbtk_activity` ADD COLUMN `color2` varchar(255) DEFAULT NULL;");
    }
    if(!pdo_fieldexists2("ddwx_hbtk_order","bid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_hbtk_order` ADD COLUMN `bid` int(11) DEFAULT '0';");
    }
    if(pdo_fieldexists2("ddwx_hbtk_order","paynum")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_hbtk_order` modify column `paynum` varchar (100);");
    }
}

if(getcustom('teamfenhong_bole')){
    if(!pdo_fieldexists2("ddwx_member_level", "teamfenhong_bole_bl")){
        \think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD teamfenhong_bole_bl decimal(11,2) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD teamfenhong_bole_money decimal(11,2) DEFAULT NULL ;");
        \think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD teamfenhong_bole_type tinyint(1) DEFAULT '0' ;");
    }
    if(!pdo_fieldexists2("ddwx_member_level", "teamfenhong_bole_bl_tuoli")){
        \think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD teamfenhong_bole_bl_tuoli decimal(11,2) DEFAULT NULL;");
    }
    if(!pdo_fieldexists2("ddwx_shop_order_goods", "teamfenhong_bole_bl")){
        \think\facade\Db::execute("ALTER TABLE ddwx_shop_order_goods ADD teamfenhong_bole_bl decimal(11,2) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_shop_order_goods ADD teamfenhong_bole_money decimal(11,2) DEFAULT NULL ;");
    }
    if(!pdo_fieldexists2("ddwx_shop_order_goods", "teamfenhong_bole_bl_tuoli")){
        \think\facade\Db::execute("ALTER TABLE ddwx_shop_order_goods ADD teamfenhong_bole_bl_tuoli decimal(11,2) DEFAULT NULL;");
    }
    if(!pdo_fieldexists2("ddwx_member_level", "teamfenhong_bole_one")){
        \think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `teamfenhong_bole_one` tinyint(1) DEFAULT '0' AFTER `teamfenhong_bole_type`;");
    }
    if(!pdo_fieldexists2("ddwx_member_level", "teamfenhong_bole_origin")){
        \think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `teamfenhong_bole_origin` tinyint(1) DEFAULT '0' AFTER `teamfenhong_bole_type`;");
    }
    if (!pdo_fieldexists2("ddwx_shop_product", "teamfenhongblset")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `teamfenhongblset`  tinyint(1) DEFAULT '0' COMMENT '伯乐奖设置 0按会员等级 1单独设置奖励比例 2单独设置奖励金额 -1不参与奖励';");
    }
    if (!pdo_fieldexists2("ddwx_shop_product", "teamfenhongbldata1")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `teamfenhongbldata1` text NULL COMMENT '伯乐奖单独设置奖励比例数据';");
    }
    if (!pdo_fieldexists2("ddwx_shop_product", "teamfenhongbldata2")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `teamfenhongbldata2` text NULL COMMENT '伯乐奖单独设置奖励金额数据';");
    }
    if (!pdo_fieldexists2("ddwx_admin_set", "teamfenhong_bl_levelids")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `teamfenhong_bl_levelids` varchar(100) DEFAULT '-1' COMMENT '团队分红伯乐奖参与等级';");
    }
}
if(getcustom('wx_fws_liuliangzhu')){
    if(!pdo_fieldexists2("ddwx_admin","ad_ratio")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `ad_ratio` int(5) DEFAULT NULL COMMENT '服务商流量主分账比例';");
    }
}

if(getcustom('design_template')){
    if(!pdo_fieldexists2("ddwx_designerpage","type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_designerpage` ADD COLUMN `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '类型 0：普通 1:模板库';");
    }
}

if(getcustom('design_cat')){
    if(!pdo_fieldexists2("ddwx_designerpage","cid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_designerpage` ADD COLUMN `cid` int NOT NULL DEFAULT 0 COMMENT '分类id';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS  `ddwx_designerpage_category` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) NOT NULL DEFAULT 0,
        `bid` int(11) NOT NULL DEFAULT 0,
        `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
        `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 0：隐藏 1：启用',
        `sort` int(11) NOT NULL DEFAULT 1,
        `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '类型 0：普通 1:模板库',
        `is_del` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否删除 0：未删除 1：删除',
        `createtime` int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`) USING BTREE,
        INDEX `aid`(`aid`) USING BTREE,
        INDEX `bid`(`bid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}

if(getcustom('money_dec') || getcustom('cashier_money_dec') || getcustom('maidan_money_dec')){
    if(!pdo_fieldexists2("ddwx_admin_set","money_dec")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `money_dec` tinyint(1) NOT NULL DEFAULT 0 COMMENT '微信支付余额抵扣：0 关闭 1：开启';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `money_dec_rate` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '余额最高抵扣比例%';");

    }
    if(!pdo_fieldexists2("ddwx_business","money_dec")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `money_dec` tinyint(1) NOT NULL DEFAULT 0 COMMENT '微信支付余额抵扣：0 关闭 1：开启';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order","dec_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order`  ADD COLUMN `dec_money` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '余额抵扣金额' ;");
    }
    if(!pdo_fieldexists2("ddwx_shop_order_goods","dec_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods`  ADD COLUMN `dec_money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '余额抵扣金额';");
    }
    if(!pdo_fieldexists2("ddwx_business","money_dec_rate")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business`  ADD COLUMN `money_dec_rate` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '余额最高抵扣比例%';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order","money_dec_rate")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order`  ADD COLUMN `money_dec_rate` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '余额抵扣比例';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order","money_dec_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `money_dec_type` tinyint(1) NULL DEFAULT 0 COMMENT '余额抵扣类型 0：比例 1：金额';");
    }

    if(!pdo_fieldexists2("ddwx_shop_order_goods","add_dec_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods`  ADD COLUMN `add_dec_money` decimal(11, 2) NOT NULL DEFAULT 0.00 COMMENT '按商家抽成费率计算，商户独立支付模式，需要补发多少余额抵扣的部分';");
    }
}

if(getcustom('yx_cashback_time') || getcustom('yx_cashback_stage') || getcustom('yx_cashback_multiply') || getcustom('yx_cashback_yongjin') ){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS  `ddwx_shop_order_goods_cashback` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) NOT NULL DEFAULT 0,
        `bid` int(11) NOT NULL DEFAULT 0,
        `mid` int(11) NOT NULL DEFAULT 0,
        `sog_id` int(11) NOT NULL DEFAULT 0 COMMENT '商城订单商品id',
        `moneystatus` tinyint(1) NOT NULL DEFAULT 0 COMMENT '返现状态 0：未确认 1：返回中 2：返回完成',
        `allmoney` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '返现总余额',
        `money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '已返回余额',
        `moneyave` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '返现余额平均值',
        `moneyday` int(11) NOT NULL DEFAULT 0 COMMENT '返现余额天数',
        `money_sendtime` int(11) NOT NULL DEFAULT 0 COMMENT '返现余额发放时间',
        `money_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '余额返现名称',
        `money_sendnum` int(11) NOT NULL DEFAULT 0 COMMENT '余额发放次数',
        `commissionstatus` tinyint(1) NOT NULL DEFAULT 0 COMMENT '返现状态 0：未确认 1：返回中 2：返回完成',
        `allcommission` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '返现总佣金',
        `commission` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '已返回佣金',
        `commissionave` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '返现佣金平均值',
        `commissionday` int(11) NOT NULL DEFAULT 0 COMMENT '返现佣金天数',
        `commission_sendtime` int(11) NOT NULL DEFAULT 0 COMMENT '返现佣金发放时间',
        `commission_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '佣金返现名称',
        `commission_sendnum` int(11) NOT NULL DEFAULT 0 COMMENT '佣金发放次数',
        `scorestatus` tinyint(1) NOT NULL DEFAULT 0 COMMENT '返现状态 0：未确认 1：返回中 2：返回完成',
        `allscore` int(11) NOT NULL DEFAULT 0 COMMENT '返现总积分',
        `score` int(11) NOT NULL DEFAULT 0 COMMENT '已返现佣金',
        `scoreave` int(11) NOT NULL DEFAULT 0 COMMENT '返现积分平均值',
        `scoreday` int(11) NOT NULL DEFAULT 0 COMMENT '返现积分天数',
        `score_sendtime` int(11) NOT NULL DEFAULT 0 COMMENT '返现积分发放时间',
        `score_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '积分返现名称',
        `score_sendnum` int(11) NOT NULL DEFAULT 0 COMMENT '积分发放次数',
        `back_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '返回类型 1：余额 2：佣金 3：积分',
        `updatetime` int(11) NOT NULL DEFAULT 0,
        `createtime` int(11) NOT NULL DEFAULT 0 COMMENT '后加的创建时间',
        `return_type` int(11) NULL COMMENT '后加的返回类型 2024.12.10添加',
        PRIMARY KEY (`id`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    if (!pdo_fieldexists2("ddwx_shop_order_goods_cashback", "cashback_id")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods_cashback` ADD COLUMN `cashback_id` int(1) NULL DEFAULT '0' COMMENT '购物返现活动id';");
    }
    if (!pdo_fieldexists2("ddwx_shop_order_goods_cashback", "pro_id")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods_cashback` ADD COLUMN `pro_id` int(11) DEFAULT '0' COMMENT '商品id';");
    }
    if(!pdo_fieldexists2("ddwx_cashback","return_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashback` ADD COLUMN `return_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '返还类型 0:立即返回 1：自定义';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashback` ADD COLUMN `return_day` int(11) NOT NULL DEFAULT 0 COMMENT '返回天数';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order_goods_cashback", "canshtype")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods_cashback` ADD COLUMN `canshtype` varchar(30) NOT NULL DEFAULT 'shop' COMMENT '购物返回类型 如商城 shop';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order_goods_cashback", "cashback_yongjin")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods_cashback` ADD COLUMN `cashback_yongjin`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '返现抵扣佣金提现金额 0关闭 1开启 2已扣除';");
    }

    if(!pdo_fieldexists2('ddwx_shop_order_goods_cashback','return_type')){
    	//后加的返回类型，默认给-1无返回类型 2024.12.10添加
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods_cashback` ADD COLUMN `return_type` tinyint(1) NULL COMMENT '返还类型 -1无返回类型 0立即返还 1 自定义 2阶梯返还 3 倍增';");
    }
}

if(getcustom('express_maiyatian')){
    if(!pdo_fieldexists2("ddwx_peisong_set","myt_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_set` ADD COLUMN `myt_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '麦芽田是否开启 0：未开启 1：开启' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_set` ADD COLUMN `myt_appkey` varchar(100) NOT NULL DEFAULT '' COMMENT 'AppKey';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_set` ADD COLUMN `myt_appsecret` varchar(100) NOT NULL DEFAULT '' COMMENT 'AppSecret';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_set` ADD COLUMN `myt_dispatchmode` tinyint(1) NOT NULL DEFAULT 1 COMMENT '发单模式: 1.省钱 2.最快 3.指派 4.价格从低到高依次呼叫';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_set` ADD COLUMN `myt_issubscribe` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否预约单 0 否 1 是';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_set` ADD COLUMN `myt_callbackurl` varchar(255) NOT NULL DEFAULT '' COMMENT '回调地址';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_set` ADD COLUMN `myt_logistic` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '指派模式' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_set` ADD COLUMN `myt_balance` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '麦芽田余额';");

        \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_order` ADD COLUMN `myt_order_id` varchar(30) NOT NULL DEFAULT '' COMMENT '麦芽田订单';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_order` ADD COLUMN `myt_weight` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '订单重量';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_order` ADD COLUMN `myt_remark` varchar(255) NOT NULL DEFAULT '' COMMENT '麦芽田备注' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_order` ADD COLUMN `myt_shop_id` int NOT NULL DEFAULT 0 COMMENT '门店id' ;");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS  `ddwx_peisong_myt_shop` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) NOT NULL DEFAULT 0,
        `bid` int(11) NOT NULL DEFAULT 0,
        `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '门店名称',
        `province_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '省名称',
        `province` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '省编码',
        `city_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '城市名称',
        `city` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '城市编码（根据城市列表接口获取）',
        `district` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '我方区县编码（根据区县列表接口获取）',
        `phone` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '手机号码',
        `address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '门店地址',
        `longitude` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '门店经度',
        `latitude` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '门店纬度',
        `category` int(11) NOT NULL DEFAULT 0 COMMENT '物品类别，见附录-数据字典',
        `map_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '坐标类型 1、高德 ｜腾讯 2百度',
        `logistics` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '同步指定配送方:\r\n单个示例：mtps\r\n多个示例: dada,mtps(英文逗号拼接)\r\n详见?配送平台枚举值',
        `shop_id` int(11) NOT NULL DEFAULT 0 COMMENT '我方门店id',
        `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0：异常 1：正常',
        `is_del` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否删除 0：未删除 1：已删除',
        `createtime` int(11) NOT NULL DEFAULT 0,
        `updatetime` int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS  `ddwx_peisong_order_myt` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) NOT NULL DEFAULT 0,
        `bid` int(11) NOT NULL DEFAULT 0,
        `mid` int(11) NOT NULL DEFAULT 0,
        `poid` int(11) NOT NULL DEFAULT 0 COMMENT '对应的配送订单id',
        `logistic` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '配送方',
        `logistic_no` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '配送单号',
        `shop_latitude` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '门店纬度',
        `shop_longitude` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '门店经度',
        `rider_id` int(11) NOT NULL DEFAULT 0 COMMENT '配送员ID',
        `rider_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '配送员姓名',
        `rider_phone` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '配送员电话',
        `rider_latitude` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '配送员纬度',
        `rider_longitude` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '配送员经度',
        `content` varchar(700) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '详细描述',
        `distance` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '距离 单位（米）',
        `is_transfer` tinyint(1) NOT NULL DEFAULT 0 COMMENT '转单标识 0 否 1是',
        `delivery_amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '配送金额(单位：元)（只在20状态下返回）',
        `tip_amount` decimal(10, 2) NOT NULL DEFAULT 0.00,
        `amount` decimal(10, 2) NOT NULL DEFAULT 0.00,
        `cancel_amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '违约金额（单位：元）（只在60状态下返回）',
        `cancel_reason_code` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '取消原因码(只在60状态下返回)',
        `cancel_reason` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '取消原因描述(只在60状态下返回)',
        `reject_code` int(11) NOT NULL DEFAULT 0 COMMENT '拒单码(只在70状态下返回)',
        `reject_msg` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '拒单描述(只在70状态下返回)',
        `createtime` int(11) NOT NULL DEFAULT 0,
        `updatetime` int(11) NOT NULL DEFAULT 0,
        `order_id` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '麦芽田订单',
        `shop_id` int(11) NOT NULL DEFAULT 0 COMMENT '门店id',
        `weight` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '订单重量',
        `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '麦芽田备注',
        PRIMARY KEY (`id`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    if(pdo_fieldexists2("ddwx_peisong_myt_shop","city_name")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_myt_shop` MODIFY COLUMN `city_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '城市名称';");
    }
}

if(getcustom('paotui')){
    if(!pdo_fieldexists2("ddwx_peisong_order","tip_fee")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_order` ADD COLUMN `tip_fee` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '小费' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_order` ADD COLUMN `expect_take_time` int(11) NOT NULL DEFAULT 0 COMMENT '期望取件时间';");
    }

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS  `ddwx_paotui_order` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `aid` int(11) NOT NULL DEFAULT 0,
          `bid` int(11) NOT NULL DEFAULT 0,
          `mid` int(11) NOT NULL DEFAULT 0,
          `btntype` tinyint(1) NOT NULL DEFAULT 0 COMMENT '跑腿类型 1：帮我送 2：帮我取',
          `ordernum` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '订单号',
          `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '物品名称',
          `pic` varchar(255) NOT NULL DEFAULT '' COMMENT '图片',
          `take_addressid` int(11) NOT NULL DEFAULT 0 COMMENT '取货地址id',
          `take_area` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '取货位置',
          `take_address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '取货位置详情',
          `take_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '取货姓名',
          `take_tel` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '取货电话',
          `take_longitude` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
          `take_latitude` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
          `take_province` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
          `take_city` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
          `send_addressid` int(11) NOT NULL DEFAULT 0 COMMENT '收件地址id',
          `send_area` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '收件位置',
          `send_address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '收件位置详情',
          `send_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '收件姓名',
          `send_tel` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '收件电话',
          `send_longitude` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
          `send_latitude` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
          `send_province` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
          `send_city` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
          `weight` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '重量',
          `dayVal` varchar(12) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '取货天数',
          `hourVal` varchar(12) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '取货小时',
          `minuteVal` varchar(12) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '取货分钟',
          `take_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '取货时间戳',
          `distance_fee` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '距离费用',
          `distance` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '距离',
          `weight_fee` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '重量费用',
          `tip_fee` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '小费',
          `time_fee` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '时间费用',
          `dt_fee` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '动态溢价',
          `totalprice` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '总费用',
          `push_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '推送平台类型 1、系统配送 2、码科配送 3、即时配送',
          `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 -2: 退款失败 -1：已取消 0：待支付 1：已支付 2：已接单 3：已到店 4、已取货 5、已送达 ',
          `is_del` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否删除 0：否 1：是',
          `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '备注',
          `createtime` int(11) UNSIGNED NOT NULL DEFAULT 0,
          `updatetime` int(11) UNSIGNED NOT NULL DEFAULT 0,
          `payorderid` int(11) NULL DEFAULT NULL,
          `paytypeid` int(11) NULL DEFAULT NULL,
          `paytype` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
          `paynum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
          `paytime` int(11) UNSIGNED NOT NULL DEFAULT 0,
          `starttime` int(11) UNSIGNED NOT NULL DEFAULT 0,
          `daodiantime` int(11) UNSIGNED NOT NULL DEFAULT 0,
          `quhuotime` int(11) UNSIGNED NOT NULL DEFAULT 0,
          `endtime` int(11) UNSIGNED NOT NULL DEFAULT 0,
          `platform` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
          `pu_id` int(11) NOT NULL DEFAULT 0 COMMENT '配送员id',
          `pu_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '配送员姓名',
          `pu_tel` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '配送员电话',
          `cancel_fee` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '违约金额（单位：元）',
          `cancel_reason` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '取消原因',
          `cancel_fail_reason` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '取消失败原因',
          `refund_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '退款状态：-2：退款失败 -1驳回退款 1: 申请退款 2：退款成功',
          `refund_money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '退款金额',
          `refund_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '退款时间',
          `express_com` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '快递公司',
          `express_no` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '快递单号（配送端id）',
          `express_type` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '配送类型',
          `send_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '推送配送端时间',
          `is_assign` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否是系统派单，指定配送员模式 0：否 1：是 2：已配送',
          PRIMARY KEY (`id`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS  `ddwx_paotui_set` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NOT NULL,
		`bid` int(11) NOT NULL DEFAULT 0,
		`type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '配送端 0：系统配送 1：码科配送 2：即时配送',
		`area` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '区域',
		`province` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '省',
		`city` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '市',
		`max_distance` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '配送最远距离',
		`distance_one` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '基础公里数',
		`distance_fee_one` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '基础公里跑腿费',
		`distance_two` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '每超出公里数',
		`distance_fee_two` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '每超出公里多加跑腿费',
		`max_weight` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '配送最大公斤',
		`weight_one` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '基础公斤数',
		`weight_fee_one` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '基础公斤跑腿费',
		`weight_two` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '每超出公斤数',
		`weight_fee_two` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '每超出公斤多加跑腿费',
		`pic` varchar(255) NOT NULL DEFAULT '' COMMENT '图片',
		`content` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '详情',
		`status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0：关闭 1：开启',
		`createtime` int(11) NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    if(pdo_fieldexists2("ddwx_paotui_order","express_com")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_paotui_order`  MODIFY COLUMN `express_com` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '快递公司';");
    }
}

if(getcustom('cashdesk_alipay')){
    if(!pdo_fieldexists2("ddwx_cashier","alipay")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashier` ADD COLUMN `alipay` tinyint(1) DEFAULT '0' COMMENT '支付宝收款';");
    }
    if(!pdo_fieldexists2("ddwx_admin_setapp_cashdesk","ali_appid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_cashdesk`
        ADD COLUMN `alipay` tinyint(1) DEFAULT '1',
        ADD COLUMN `ali_appid` varchar(100) DEFAULT NULL,
        ADD COLUMN `ali_privatekey` text,
        ADD COLUMN `ali_publickey` text;");
    }
}
if(getcustom('cashdesk_sxpay')){
    if(!pdo_fieldexists2("ddwx_cashier","sxpay")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashier` ADD COLUMN `sxpay` tinyint(1) DEFAULT '0' COMMENT '随行付收款';");
    }
    if(!pdo_fieldexists2("ddwx_admin_setapp_cashdesk","sxpay_mno")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_cashdesk`
        ADD COLUMN `sxpay` tinyint(1) DEFAULT '1',
        ADD COLUMN `sxpay_mno` varchar(255) DEFAULT NULL,
        ADD COLUMN `sxpay_mchkey` varchar(255) DEFAULT NULL;");
    }
}
if(getcustom('show_location')){
    if(!pdo_fieldexists2("ddwx_admin_set","loc_area_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `loc_area_type`  tinyint(1) NULL DEFAULT 0 COMMENT '0 当前城市  1当前地址',
        ADD COLUMN `loc_range_type`  tinyint(1) NULL DEFAULT 0 COMMENT '0 同城 1自定义范围',
        ADD COLUMN `loc_range`  int(11) NULL DEFAULT 0 COMMENT '范围半径';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","updatetime")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
ADD COLUMN `updatetime`  int(11) NULL DEFAULT NULL");
        \app\model\SystemSet::initLocationPage();
    }
}
if(getcustom('article_files')){
    if(!pdo_fieldexists2("ddwx_article","fujian")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_article`  ADD COLUMN `fujian` text COMMENT '附件';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","is_look_resource")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN  `is_look_resource` tinyint(1) DEFAULT '0' COMMENT '是否可查看资源';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN  `is_download_resource` tinyint(1) DEFAULT '0' COMMENT '是否可下载资源';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_article_resource` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT NULL,
      `mid` int(11) DEFAULT NULL,
      `artid` int(11) DEFAULT '0',
      `createtime` int(11) DEFAULT '0',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}
if(getcustom('wx_fws_liuliangzhu')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_wx_advert` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(1) DEFAULT NULL,
	  `bid` int(11) DEFAULT NULL,
	  `name` varchar(255) DEFAULT NULL,
	  `type` varchar(255) DEFAULT NULL,
	  `ad_unit_id` varchar(255) DEFAULT NULL COMMENT '广告ID',
	  `code` varchar(255) DEFAULT NULL COMMENT '代码',
	  `tmpl_id` varchar(255) DEFAULT NULL COMMENT '模板广告的模板ID',
	  `createtime` int(11) DEFAULT NULL,
	  `status` tinyint(1) DEFAULT '1',
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    if(pdo_fieldexists2("ddwx_wx_advert","aid")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_wx_advert` modify column `aid` int(11);");
    }
}
if(getcustom('business_canuseplatcoupon')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_coupon_businessuserecord` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT NULL,
	  `mid` int(11) DEFAULT NULL,
	  `ordertype` varchar(255) DEFAULT NULL,
	  `orderid` int(11) DEFAULT NULL,
	  `couponid` int(11) DEFAULT NULL,
	  `couponrid` int(11) DEFAULT NULL,
	  `couponname` varchar(255) DEFAULT NULL,
	  `couponmoney` float(11,2) DEFAULT '0.00',
	  `decmoney` float(11,2) DEFAULT '0.00',
	  `status` tinyint(1) DEFAULT '0',
	  `createtime` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_coupon_businesswithdrawlog` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT NULL,
	  `money` decimal(11,2) DEFAULT NULL,
	  `txmoney` decimal(11,2) DEFAULT NULL,
	  `weixin` varchar(255) DEFAULT NULL,
	  `aliaccount` varchar(255) DEFAULT NULL,
	  `ordernum` varchar(255) DEFAULT NULL,
	  `paytype` varchar(255) DEFAULT NULL,
	  `status` tinyint(1) DEFAULT '0',
	  `createtime` int(11) DEFAULT NULL,
	  `bankname` varchar(255) DEFAULT NULL,
	  `bankcarduser` varchar(255) DEFAULT NULL,
	  `bankcardnum` varchar(255) DEFAULT NULL,
	  `paytime` int(11) DEFAULT NULL,
	  `paynum` varchar(255) DEFAULT NULL,
	  `reason` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `status` (`status`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

}
if(getcustom('sys_print_set')){
    if(!pdo_fieldexists2("ddwx_wifiprint_set","boot_custom")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_wifiprint_set` ADD COLUMN `boot_custom` tinyint(1) NOT NULL DEFAULT 0 COMMENT '底部自定义 0：关闭 1：开启';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_wifiprint_set` ADD COLUMN `boot_custom_content` text NULL COMMENT '底部自定义内容';");

        \think\facade\Db::execute("ALTER TABLE `ddwx_wifiprint_set` ADD COLUMN `day_ordernum` tinyint(1) NOT NULL DEFAULT 0 COMMENT '日单号 0: 关闭 1：开启';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_wifiprint_set` ADD COLUMN `print_num` int(5) NOT NULL DEFAULT 1 COMMENT '打印份数';");
    }

    if(!pdo_fieldexists2("ddwx_shop_order","printdaynum")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `printdaynum` int NOT NULL DEFAULT 0 COMMENT '日单号';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order` ADD COLUMN `printdaynum` int NOT NULL DEFAULT 0 COMMENT '日单号';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_order` ADD COLUMN `printdaynum` int NOT NULL DEFAULT 0 COMMENT '日单号';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashier_order` ADD COLUMN `printdaynum` int NOT NULL DEFAULT 0 COMMENT '日单号';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_maidan_order` ADD COLUMN `printdaynum` int NOT NULL DEFAULT 0 COMMENT '日单号';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order` ADD COLUMN `printdaynum` int NOT NULL DEFAULT 0 COMMENT '日单号';");
    }
}
if(getcustom('shoporder_cost_hide')){
    if(!pdo_fieldexists2("ddwx_admin","order_cost_hide")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `order_cost_hide` tinyint(1) NULL DEFAULT '0' COMMENT '隐藏销售统计的成本和利润';");
    }
}
if(getcustom('luntan_second_category')){
    if(!pdo_fieldexists2("ddwx_luntan_category","display_type")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_luntan_category` ADD COLUMN `display_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '显示类型 0 、横版：两横排图文显示 1、竖版：一排显示，一级分类加二级列表内容图文显示格式';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_luntan_category` ADD COLUMN `child_num` int(11) NOT NULL DEFAULT '0' COMMENT '竖版显示类型时二级分类显示的数量';");
    }
}

if(getcustom('yx_tuangou_vrnum')){
    if(!pdo_fieldexists2("ddwx_tuangou_product","vrnum")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_tuangou_product` ADD COLUMN `vrnum` int(11) NOT NULL DEFAULT '0' COMMENT '虚拟销售量';");
    }
}

if(getcustom('score_transfer')){
    if(!pdo_fieldexists2("ddwx_admin_set","score_transfer_gettj")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `score_transfer_gettj` varchar(120) NULL  DEFAULT '-1' COMMENT '有积分转赠权限使用的会员等级' AFTER `score_transfer_pwd`;");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","score_transfer_receivetj")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `score_transfer_receivetj` varchar(120) NULL  DEFAULT '-1' COMMENT '有积分转赠接收权限的会员等级' AFTER `score_transfer_gettj`;");
    }
}
if(getcustom('score_friend_transfer')){
    if(!pdo_fieldexists2("ddwx_admin_set","score_transfer_gettj")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `score_transfer_gettj` varchar(120) NULL  DEFAULT '-1' COMMENT '有权限使用的会员等级' AFTER `score_transfer_pwd`;");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","score_transfer_receivetj")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `score_transfer_receivetj` varchar(120) NULL  DEFAULT '-1' COMMENT '有积分转赠接收权限的会员等级' AFTER `score_transfer_gettj`;");
    }
}
if(getcustom('business_deduct_cost')){
    if(!pdo_fieldexists2("ddwx_business","deduct_cost")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `deduct_cost` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否扣除成本';");
    }
}
if(getcustom('teamfenhong_gouche')){
    if(!pdo_fieldexists2("ddwx_member_level","gouche_down_num")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `gouche_down_num`  int(11) NOT NULL DEFAULT 0 COMMENT '购车基金直推一级前几个人数条件';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","gouche_levelid")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `gouche_levelid`  int(11) NOT NULL DEFAULT 0 COMMENT '购车基金直推人等级条件';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","gouche_bonus_total")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `gouche_bonus_total`  decimal(12,2) NOT NULL DEFAULT 0 COMMENT '购车基金直推人收入条件';");
    }

    if(!pdo_fieldexists2("ddwx_shop_product","gouchebonusset")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `gouchebonusset`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '购车基金设置0不参与1按比例2按金额';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","gouchebonusdata1")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `gouchebonusdata1`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '购车基金按比例设置参数';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","gouchebonusdata2")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `gouchebonusdata2`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '购车基金按金额设置参数';");
    }

    if(!pdo_fieldexists2("ddwx_shop_order_goods","gouchebonusset")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `gouchebonusset`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '购车基金设置0不参与1按比例2按金额';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order_goods","gouchebonusdata1")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `gouchebonusdata1`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '购车基金按比例设置参数';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order_goods","gouchebonusdata2")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `gouchebonusdata2`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '购车基金按金额设置参数';");
    }
    if(!pdo_fieldexists2("ddwx_member","gouche_able")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `gouche_able`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否有购车基金资格 0无 1有';");
    }
}
if(getcustom('teamfenhong_lvyou')){
    if(!pdo_fieldexists2("ddwx_member_level","lvyou_down_num")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `lvyou_down_num`  int(11) NOT NULL DEFAULT 0 COMMENT '旅游基金直推二级前几个人数条件';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","lvyou_levelid")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `lvyou_levelid`  int(11) NOT NULL DEFAULT 0 COMMENT '旅游基金下二级直推人等级条件'");
    }
    if(!pdo_fieldexists2("ddwx_member_level","lvyou_bonus_total")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `lvyou_bonus_total`  decimal(12,2) NOT NULL DEFAULT 0 COMMENT '旅游基金下二级直推人收入条件'");
    }

    if(!pdo_fieldexists2("ddwx_shop_product","lvyoubonusset")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `lvyoubonusset`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '旅游基金设置0不参与1按比例2按金额';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","lvyoubonusdata1")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `lvyoubonusdata1`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '旅游基金按比例设置参数';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","lvyoubonusdata2")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `lvyoubonusdata2`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '旅游基金按金额设置参数'");
    }

    if(!pdo_fieldexists2("ddwx_shop_order_goods","lvyoubonusset")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `lvyoubonusset`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '旅游基金设置0不参与1按比例2按金额';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order_goods","lvyoubonusdata1")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `lvyoubonusdata1`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '旅游基金按比例设置参数';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order_goods","lvyoubonusdata2")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `lvyoubonusdata2`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '旅游基金按金额设置参数'");
    }
    if(!pdo_fieldexists2("ddwx_member","lvyou_able")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `lvyou_able`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否有旅游基金资格 0无 1有';");
    }
}
if(getcustom('blist_showviewnum')){
    if(!pdo_fieldexists2("ddwx_business_sysset","viewnum_defaultnum")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `viewnum_defaultnum` int(11) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `viewnum_addnum` int(11) DEFAULT '1';");
    }
}

if(getcustom('product_field_buy')){
    if(!pdo_fieldexists2("ddwx_shop_product","brand")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `brand` varchar(100) NOT NULL DEFAULT '' COMMENT '品牌名称';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `unit` varchar(30) NOT NULL DEFAULT '' COMMENT '单位' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `valid_time` varchar(50) NOT NULL DEFAULT '' COMMENT '有效时间';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","guige")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `guige` varchar(100) NOT NULL DEFAULT '' COMMENT '规格';");
    }
}

if(getcustom('product_collect_time')){
    if(!pdo_fieldexists2("ddwx_shop_sysset","ordercollect_time")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `ordercollect_time` int(11) NOT NULL DEFAULT 0 COMMENT '多少天后，用户可以点击确认收货';");
    }
}

if(getcustom('alipay_auto_transfer')){
    if(!pdo_fieldexists2("ddwx_admin_set","ali_withdraw_autotransfer")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `ali_withdraw_autotransfer` tinyint(1) NOT NULL DEFAULT 0 COMMENT '支付宝自动打款 0：否 1：是';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `ali_appid` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '支付宝APPID' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `ali_privatekey` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '应用私钥';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `ali_apppublickey` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '应用公钥';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `ali_publickey` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '支付宝公钥';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `ali_rootcert` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '支付宝根证书';");
    }
    if(!pdo_fieldexists2("ddwx_business","aliaccountname")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `aliaccountname` varchar(255) DEFAULT NULL COMMENT '支付宝户名';");
    }
}
if(getcustom('fenhong_ranking')){
    if(!pdo_fieldexists2("ddwx_admin_set","fenhong_rank_status")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`  ADD COLUMN `fenhong_rank_status` tinyint(1) DEFAULT NULL COMMENT '分红排行榜状态';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`  ADD COLUMN `fenhong_rank_date` int(11) DEFAULT '1' COMMENT '分红排行榜 日期';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`  ADD COLUMN `fenhong_rank_people` int(11) DEFAULT '0' COMMENT '分红排行榜 人数';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","fenhong_rank_title")){
      \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`  ADD COLUMN `fenhong_rank_title` varchar(50) DEFAULT NULL COMMENT '分红排行榜标题';");
      \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`  ADD COLUMN `fenhong_rank_desc` varchar(50) DEFAULT NULL COMMENT '分红排行榜标题下描述';");
      \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`  ADD COLUMN `fenhong_rank_type` varchar(100) DEFAULT '1' COMMENT '分红排行榜类型';");
    }
}
if(getcustom('commission_times_coupon')){
    if(!pdo_fieldexists2("ddwx_coupon","commissionset")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` ADD COLUMN `commissionset` int(2) DEFAULT '0' COMMENT '0按会员等级 1价格比例  2固定金额  -1不参与分销';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon`  ADD COLUMN `commissiondata1` text CHARACTER SET utf8mb4;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon`  ADD COLUMN `commissiondata2` text CHARACTER SET utf8mb4;");
    }
    if(!pdo_fieldexists2("ddwx_coupon_order","parent1")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon_order`  ADD COLUMN `parent1` int(11) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon_order`  ADD COLUMN `parent2` int(11) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon_order`  ADD COLUMN `parent3` int(11) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon_order`  ADD COLUMN  `parent1commission` decimal(11,2) DEFAULT '0.00';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon_order`  ADD COLUMN  `parent2commission` decimal(11,2) DEFAULT '0.00';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon_order`  ADD COLUMN  `parent3commission` decimal(11,2) DEFAULT '0.00';");
    }
}
if(getcustom('fenhong_times_coupon')){
    if(!pdo_fieldexists2("ddwx_coupon","fenhongset")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon`  ADD COLUMN `fenhongset` int(11) DEFAULT '1';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon`  ADD COLUMN `gdfenhongset` int(2) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon`  ADD COLUMN `gdfenhongdata1` text;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon`  ADD COLUMN `gdfenhongdata2` text;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon`  ADD COLUMN `teamfenhongset` int(2) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon`  ADD COLUMN `teamfenhongdata1` text;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon`  ADD COLUMN `teamfenhongdata2` text;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon`  ADD COLUMN `areafenhongset` int(2) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon`  ADD COLUMN `areafenhongdata1` text;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon`  ADD COLUMN `areafenhongdata2` text;");
    }
    if(!pdo_fieldexists2("ddwx_coupon_order","isfenhong")){
        think\facade\Db::execute("ALTER TABLE `ddwx_coupon_order`  ADD COLUMN  `isfenhong` tinyint(1) DEFAULT '0' COMMENT '是否已经分红' ;");
    }
}
if(getcustom('restaurant_shop_cashdesk')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_restaurant_cashdesk` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `name` varchar(255) DEFAULT NULL,
      `url` varchar(255) DEFAULT NULL,
      `createtime` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `remove_zero_length` tinyint(4) DEFAULT '0',
      `option_name` varchar(32) DEFAULT '',
      `updatetime` int(11) DEFAULT NULL,
      `color1` varchar(60) DEFAULT NULL,
      `wxpay` tinyint(1) DEFAULT '0' COMMENT '微信收款',
      `cashpay` tinyint(1) DEFAULT '1' COMMENT '现金收款',
      `moneypay` tinyint(1) DEFAULT '1' COMMENT '余额收款',
      `alipay` tinyint(1) DEFAULT '0' COMMENT '支付宝收款',
      `sxpay` tinyint(1) DEFAULT '0' COMMENT '随行付收款',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_admin_setapp_restaurant_cashdesk` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `appid` varchar(255) DEFAULT NULL,
      `appsecret` varchar(255) DEFAULT NULL,
      `wxpay` tinyint(1) DEFAULT '1',
      `wxpay_type` tinyint(1) DEFAULT '0',
      `wxpay_sub_mchid` varchar(100) DEFAULT NULL,
      `wxpay_mchid` varchar(100) DEFAULT NULL,
      `wxpay_mchkey` varchar(100) DEFAULT NULL,
      `wxpay_apiclient_cert` varchar(100) DEFAULT NULL,
      `wxpay_apiclient_key` varchar(100) DEFAULT NULL,
      `alipay` tinyint(1) DEFAULT '1',
      `ali_appid` varchar(100) DEFAULT NULL,
      `ali_privatekey` text,
      `ali_publickey` text,
      `sxpay` tinyint(1) DEFAULT '1',
      `sxpay_mno` varchar(255) DEFAULT NULL,
      `sxpay_mchkey` varchar(255) DEFAULT NULL,
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`),
      KEY `bid` (`bid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    if(!pdo_fieldexists2("ddwx_restaurant_shop_order","cashdesk_id")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order` 
        ADD COLUMN `cashdesk_id` int(11) DEFAULT '0',
        ADD COLUMN `remove_zero` tinyint(1) DEFAULT '0' COMMENT '抹零',
        ADD COLUMN `remove_zero_length` tinyint(4) DEFAULT '0' COMMENT '抹零位数',
        ADD COLUMN `moling_money` decimal(10,2) DEFAULT NULL COMMENT '抹零钱数',
        ADD COLUMN `uid` int(11) DEFAULT '0' COMMENT '收银员id';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_shop_order_goods","is_gj")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order_goods`
        ADD COLUMN `is_gj` tinyint(1) DEFAULT '0' COMMENT '0：未改价 1：改价',
        ADD COLUMN `protype` tinyint(2) DEFAULT '1' COMMENT '1商品 2直接收款（proid=-99)';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_admin_set","business_cashdesk_alipay_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_admin_set`
        ADD COLUMN `business_cashdesk_alipay_type` tinyint(1) DEFAULT '2' COMMENT '多商户收银台 支付宝 0：关闭 2平台收款 3：独立收款 ',
        ADD COLUMN `business_cashdesk_wxpay_type` tinyint(1) DEFAULT '2' COMMENT '多商户收银台 微信  0：关闭 1：服务商 2平台收款 3：独立收款 ',
        ADD COLUMN `business_cashdesk_sxpay_type` tinyint(1) DEFAULT '2' COMMENT '多商户收银台 随行付  0：关闭 1：服务商 2平台收款 3：独立收款 ',
        ADD COLUMN `business_cashdesk_yue` tinyint(1) DEFAULT '1' COMMENT '多商户收银台 余额   0:关闭 1：开启',
        ADD COLUMN `business_cashdesk_cashpay` tinyint(1) DEFAULT '1' COMMENT '多商户收银台 现金   0:关闭 1：开启';");
    }

    if(!pdo_fieldexists2("ddwx_admin_setapp_restaurant_cashdesk","sxpay_sub_mno")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_restaurant_cashdesk`
        ADD COLUMN `sxpay_sub_mno` varchar(255) DEFAULT NULL COMMENT '随行付商户号 服务商',
        ADD COLUMN `sxpay_sub_mchkey` varchar(255) DEFAULT NULL COMMENT '随行付秘钥 服务商';");
    }
}
if (getcustom('fenhong_money_weishu')){
    if(pdo_fieldexists2("ddwx_member","commission")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member`  MODIFY COLUMN `commission` decimal(17,6) DEFAULT '0.000000';");
    }
    if(pdo_fieldexists2("ddwx_member","totalcommission")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member`  MODIFY COLUMN `totalcommission` decimal(17,6) DEFAULT '0.000000';");
    }
    if(pdo_fieldexists2("ddwx_member","total_fenhong")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member`  MODIFY COLUMN `total_fenhong` decimal(17,6) NOT NULL DEFAULT '0.000000';");
    }
    if(pdo_fieldexists2("ddwx_member","total_fenhong_team")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member`  MODIFY COLUMN `total_fenhong_team` decimal(17,6) NOT NULL DEFAULT '0.000000';");
    }
    if(pdo_fieldexists2("ddwx_member","total_fenhong_partner")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member`  MODIFY COLUMN `total_fenhong_partner` decimal(17,6) NOT NULL DEFAULT '0.000000';");
    }
    if(pdo_fieldexists2("ddwx_member","total_fenhong_area")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member`  MODIFY COLUMN `total_fenhong_area` decimal(17,6) NOT NULL DEFAULT '0.000000';");
    }
    if(pdo_fieldexists2("ddwx_member","total_fenhong_touzi")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member`  MODIFY COLUMN `total_fenhong_touzi` decimal(17,6) NOT NULL DEFAULT '0.000000';");
    }
    if(pdo_fieldexists2("ddwx_member","total_fenhong_level_team")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member`  MODIFY COLUMN `total_fenhong_level_team` decimal(17,6) NOT NULL DEFAULT '0.000000';");
    }
    if(pdo_fieldexists2("ddwx_member","total_fenhong_gongxian")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member`  MODIFY COLUMN `total_fenhong_gongxian` decimal(17,6) NOT NULL DEFAULT '0.000000';");
    }

    if(pdo_fieldexists2("ddwx_member_fenhonglog","commission")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_fenhonglog`  MODIFY COLUMN `commission` decimal(17,6)  DEFAULT '0.000000';");
    }
    if(pdo_fieldexists2("ddwx_member_commissionlog","commission")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_commissionlog`  MODIFY COLUMN `commission` decimal(17,6)  DEFAULT '0.000000';");
    }
    if(pdo_fieldexists2("ddwx_member_commissionlog","after")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_commissionlog`  MODIFY COLUMN `after` decimal(17,6)  DEFAULT '0.000000';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","fenhong_money_weishu")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`  ADD COLUMN `fenhong_money_weishu` tinyint(1) DEFAULT '2' COMMENT '分红佣金 位数';");
    }
    if(pdo_fieldexists2("ddwx_member_commission_record","commission")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_commission_record`  MODIFY COLUMN `commission` decimal(17,6)  DEFAULT '0.000000';");
    }
    if(pdo_fieldexists2("ddwx_member_fenhonglog","send_commission")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_fenhonglog`  MODIFY COLUMN `send_commission` decimal(17,6)  DEFAULT '0.000000';");
    }
}
if(getcustom('image_ai')){

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_imgai_category` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) DEFAULT NULL,
        `bid` int(11) DEFAULT NULL,
        `pid` int(11) DEFAULT '0',
        `name` varchar(255) DEFAULT NULL,
        `pic` varchar(255) DEFAULT NULL,
        `status` int(1) DEFAULT '1',
        `sort` int(11) DEFAULT '1',
        `createtime` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`) USING BTREE,
        KEY `aid` (`aid`) USING BTREE,
        KEY `pid` (`pid`) USING BTREE,
        KEY `bid` (`bid`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='百度AI绘画——风格分类';");


    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_imgai_keyword` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) DEFAULT NULL,
        `bid` int(11) DEFAULT NULL,
        `pid` int(11) DEFAULT '0',
        `name` varchar(255) DEFAULT NULL,
        `keyword` varchar(255) DEFAULT NULL,
        `status` int(1) DEFAULT '1',
        `sort` int(11) DEFAULT '1',
        `createtime` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`) USING BTREE,
        KEY `aid` (`aid`) USING BTREE,
        KEY `pid` (`pid`) USING BTREE,
        KEY `bid` (`bid`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='百度AI绘画——关键词';");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_imgai_order` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) NOT NULL DEFAULT '0',
      `mid` int(11) NOT NULL DEFAULT '0' COMMENT '会员id',
      `ordernum` varchar(255) NOT NULL DEFAULT '' COMMENT '订单编号',
      `payorderid` int(11) NOT NULL DEFAULT '0' COMMENT '支付订单id',
      `ai_text` varchar(255) NOT NULL DEFAULT '' COMMENT '创作文本',
      `ai_style` varchar(255) NOT NULL DEFAULT '' COMMENT '绘画风格',
      `response` varchar(255) NOT NULL DEFAULT '' COMMENT '百度请求返回的内容',
      `taskId` varchar(255) NOT NULL DEFAULT '' COMMENT '任务id(用来查询图片地址)',
      `log_id` varchar(255) NOT NULL,
      `w_time` int(10) NOT NULL DEFAULT '0' COMMENT '请求时间',
      `pic` varchar(255) NOT NULL DEFAULT '' COMMENT '图片地址',
      `query_res` text COMMENT '查询图片的请求结果',
      `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0未支付 1已支付',
      `paytime` int(10) NOT NULL DEFAULT '0' COMMENT '支付时间',
      `paytype` varchar(255) NOT NULL DEFAULT '' COMMENT '支付类型',
      `paytypeid` int(11) NOT NULL DEFAULT '0' COMMENT '支付订单id',
      `paynum` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '实际支付金额',
      `platform` varchar(255) DEFAULT NULL,
      `pay_money` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '需支付余额数量',
      `pay_score` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '需支付积分数量',
      `able_time`  int(10) NOT NULL DEFAULT 0 COMMENT '有效时间',
      `pay_way`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '支付类型',
      PRIMARY KEY (`id`),
      KEY `aid` (`aid`),
      KEY `mid` (`mid`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='百度AI绘画——申请记录表';");

    if(!pdo_fieldexists2("ddwx_imgai_order","able_time")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_imgai_order` ADD COLUMN `able_time`  int(10) NOT NULL DEFAULT 0 COMMENT '有效时间';");
    }
    if(!pdo_fieldexists2("ddwx_imgai_order","pay_way")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_imgai_order` ADD COLUMN `pay_way`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '支付类型';");
    }

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_imgai_sysset` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `app_id` varchar(255) DEFAULT '' COMMENT '百度云appid',
      `api_key` varchar(255) DEFAULT '' COMMENT '百度云api_key',
      `secret_key` varchar(255) DEFAULT '' COMMENT '百度云secret',
      `pay_type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '支付方式0不需要支付 1余额支付 2积分支付',
      `pay_num` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '支付金额',
      `free_num`  int(11) NOT NULL DEFAULT 0 COMMENT '免费使用次数',
      `pay_num_month`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '包月费用 30天',
      `pay_num_ji`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '包季费用 90天',
      `pay_num_year`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '包年费用 365',
      `bgcolor`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      PRIMARY KEY (`id`) USING BTREE,
      UNIQUE KEY `aid` (`aid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='百度AI绘画——参数配置';");

    if(!pdo_fieldexists2("ddwx_imgai_sysset","free_num")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_imgai_sysset` ADD COLUMN `free_num`  int(11) NOT NULL DEFAULT 0 COMMENT '免费使用次数';");
    }
    if(!pdo_fieldexists2("ddwx_imgai_sysset","pay_num_month")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_imgai_sysset` ADD COLUMN `pay_num_month`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '包月费用 30天';");
    }
    if(!pdo_fieldexists2("ddwx_imgai_sysset","pay_num_ji")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_imgai_sysset` ADD COLUMN `pay_num_ji`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '包季费用 90天';");
    }
    if(!pdo_fieldexists2("ddwx_imgai_sysset","pay_num_year")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_imgai_sysset` ADD COLUMN `pay_num_year`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '包年费用 365';");
    }
    if(!pdo_fieldexists2("ddwx_imgai_sysset","bgcolor")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_imgai_sysset` ADD COLUMN `bgcolor`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';");
    }
    if(!pdo_fieldexists2("ddwx_member","imgai_time")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `imgai_time`  int(10) NOT NULL DEFAULT 0 COMMENT 'AI绘画有效期';");
    }
}
if(getcustom('map_mark')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_mapmark_category` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT NULL,
      `pid` int(11) DEFAULT '0',
      `name` varchar(255) DEFAULT NULL,
      `pic` varchar(255) DEFAULT NULL,
      `status` int(1) DEFAULT '1',
      `sort` int(11) DEFAULT '1',
      `createtime` int(11) DEFAULT NULL,
      `pcid` varchar(11) DEFAULT '0' COMMENT '分类id',
      `money` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '标注费用',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE,
      KEY `pid` (`pid`) USING BTREE,
      KEY `bid` (`bid`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='地图标注——地图分类';");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_mapmark_order` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) NOT NULL DEFAULT '0',
      `bid` int(11) DEFAULT NULL,
      `mid` int(11) NOT NULL DEFAULT '0' COMMENT '会员id',
      `ordernum` varchar(255) NOT NULL DEFAULT '' COMMENT '订单编号',
      `payorderid` int(11) NOT NULL DEFAULT '0' COMMENT '支付订单id',
      `name` varchar(255) NOT NULL DEFAULT '' COMMENT '标注名称',
      `shop_type` varchar(255) NOT NULL DEFAULT '' COMMENT '经营类型',
      `shop_tel` varchar(20) NOT NULL DEFAULT '' COMMENT '营业电话',
      `shop_time` varchar(255) NOT NULL DEFAULT '' COMMENT '营业时间',
      `address` varchar(255) NOT NULL DEFAULT '' COMMENT '经营地址',
      `mobile` varchar(11) NOT NULL DEFAULT '' COMMENT '手机号',
      `license_img` varchar(255) NOT NULL DEFAULT '' COMMENT '创作文本',
      `shop_img` varchar(255) NOT NULL DEFAULT '' COMMENT '绘画风格',
      `cids` varchar(255) NOT NULL DEFAULT '' COMMENT '百度请求返回的内容',
      `w_time` int(10) NOT NULL DEFAULT '0' COMMENT '请求时间',
      `pic` varchar(255) NOT NULL DEFAULT '' COMMENT '图片地址',
      `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0未支付 1已支付',
      `paytime` int(10) NOT NULL DEFAULT '0' COMMENT '支付时间',
      `paytype` varchar(255) NOT NULL DEFAULT '' COMMENT '支付类型',
      `paytypeid` int(11) NOT NULL DEFAULT '0' COMMENT '支付订单id',
      `paynum` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '实际支付金额',
      `platform` varchar(255) DEFAULT NULL,
      `pay_money` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '需支付余额数量',
      `mark_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0审核中 1审核通过 2审核拒绝',
      `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
      PRIMARY KEY (`id`),
      KEY `aid` (`aid`),
      KEY `bid` (`bid`),
      KEY `mid` (`mid`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='地图标注——申请记录表';");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_mapmark_sysset` (
        `id`  int(11) NOT NULL AUTO_INCREMENT ,
        `aid`  int(11) NULL DEFAULT NULL ,
        `bgcolor`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' ,
        PRIMARY KEY (`id`),
        UNIQUE INDEX `aid` (`aid`) USING BTREE 
        )
        ENGINE=InnoDB
        DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
        ROW_FORMAT=Dynamic;");
}

if(getcustom('video_spider')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_videospider_order` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) NOT NULL DEFAULT '0',
      `mid` int(11) NOT NULL DEFAULT '0' COMMENT '会员id',
      `ordernum` varchar(255) NOT NULL DEFAULT '' COMMENT '订单编号',
      `payorderid` int(11) NOT NULL DEFAULT '0' COMMENT '支付订单id',
      `url` text NOT NULL COMMENT '创作文本',
      `video_title` varchar(255) NOT NULL DEFAULT '' COMMENT '视频标题',
      `video_cover` varchar(255) NOT NULL DEFAULT '' COMMENT '视频封面图片',
      `video_url` varchar(255) NOT NULL DEFAULT '' COMMENT '去除水印的视频链接',
      `w_time` int(10) NOT NULL DEFAULT '0' COMMENT '请求时间',
      `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0未支付 1已支付',
      `paytime` int(10) NOT NULL DEFAULT '0' COMMENT '支付时间',
      `paytype` varchar(255) NOT NULL DEFAULT '' COMMENT '支付类型',
      `paytypeid` int(11) NOT NULL DEFAULT '0' COMMENT '支付订单id',
      `paynum` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '实际支付金额',
      `platform` varchar(255) DEFAULT NULL,
      `pay_money` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '需支付余额数量',
      `pay_score` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '需支付积分数量',
      `err_msg` varchar(255) NOT NULL DEFAULT '' COMMENT '错误信息',
      `able_time`  int(10) NOT NULL DEFAULT 0 COMMENT '有效时间',
      `pay_way`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '支付类型',
      PRIMARY KEY (`id`),
      KEY `aid` (`aid`),
      KEY `mid` (`mid`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='短视频去水印——订单表';");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_videospider_sysset` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `pay_type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '支付方式0不需要支付 1余额支付 2积分支付',
      `pay_num` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '支付金额',
      `free_num`  int(11) NOT NULL DEFAULT 0 COMMENT '免费使用次数',
      `pay_num_month`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '包月费用 30天',
      `pay_num_ji`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '包季费用 90天',
      `pay_num_year`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '包年费用 365',
      `bgcolor`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
      PRIMARY KEY (`id`) USING BTREE,
      UNIQUE KEY `aid` (`aid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='短视频去水印——参数配置';");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_videospider_category` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT NULL,
      `pid` int(11) DEFAULT '0',
      `name` varchar(255) DEFAULT NULL,
      `pic` varchar(255) DEFAULT NULL,
      `status` int(1) DEFAULT '1',
      `sort` int(11) DEFAULT '1',
      `createtime` int(11) DEFAULT NULL,
      `pcid` varchar(11) DEFAULT '0',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE,
      KEY `pid` (`pid`) USING BTREE,
      KEY `bid` (`bid`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='短视频去水印——支持平台';");

    if(!pdo_fieldexists2("ddwx_member","videospider_time")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `videospider_time`  int(10) NOT NULL DEFAULT 0 COMMENT '视频解析有效期';");
    }
    if(!pdo_fieldexists2("ddwx_videospider_order","able_time")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_videospider_order` ADD COLUMN `able_time`  int(10) NOT NULL DEFAULT 0 COMMENT '有效时间';");
    }
    if(!pdo_fieldexists2("ddwx_videospider_order","pay_way")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_videospider_order` ADD COLUMN `pay_way`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '支付类型';");
    }
    if(!pdo_fieldexists2("ddwx_videospider_sysset","free_num")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_videospider_sysset` ADD COLUMN `free_num`  int(11) NOT NULL DEFAULT 0 COMMENT '免费使用次数';");
    }
    if(!pdo_fieldexists2("ddwx_videospider_sysset","pay_num_month")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_videospider_sysset` ADD COLUMN `pay_num_month`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '包月费用 30天';");
    }
    if(!pdo_fieldexists2("ddwx_videospider_sysset","pay_num_ji")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_videospider_sysset` ADD COLUMN `pay_num_ji`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '包季费用 90天';");
    }
    if(!pdo_fieldexists2("ddwx_videospider_sysset","pay_num_year")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_videospider_sysset` ADD COLUMN `pay_num_year`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '包年费用 365';");
    }
    if(!pdo_fieldexists2("ddwx_videospider_sysset","bgcolor")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_videospider_sysset` ADD COLUMN `bgcolor`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '';");
    }

}
if(getcustom('shop_prodetailtitle')){
    if(!pdo_fieldexists2("ddwx_shop_sysset","prodetailtitle_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `prodetailtitle_type` tinyint(1) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `prodetailtitle_value` varchar(255) DEFAULT null;");
    }
}
if(getcustom('product_update_excel')) {
    if (!pdo_fieldexists2("ddwx_shop_product", "product_link")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `product_link` varchar(255) NOT NULL DEFAULT '' COMMENT '宝贝链接';");
    }
}
if(getcustom('copyright_link')){
    if(!pdo_fieldexists2("ddwx_admin","copyright_link")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `copyright_link` varchar(255) DEFAULT NULL AFTER `copyright`;");
    }
}
if(getcustom('pay_adapay')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_adapay_log` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `aid` int(11) DEFAULT NULL,
          `mid` int(11) DEFAULT NULL,
          `openid` varchar(255) DEFAULT NULL,
          `pay_channel` varchar(100) DEFAULT NULL,
          `tablename` varchar(255) DEFAULT NULL,
          `ordernum` varchar(255) DEFAULT NULL,
          `mch_id` varchar(100) DEFAULT NULL,
          `transaction_id` varchar(255) DEFAULT NULL,
          `total_fee` decimal(11,2) DEFAULT '0.00',
          `givescore` int(11) DEFAULT '0',
          `createtime` int(11) DEFAULT NULL,
          `fenzhangmoney` decimal(11,2) DEFAULT '0.00',
          `isfenzhang` tinyint(1) DEFAULT '0',
          `fz_ordernum` varchar(100) DEFAULT NULL,
          `fz_errmsg` varchar(255) DEFAULT NULL,
          PRIMARY KEY (`id`) USING BTREE,
          KEY `aid` (`aid`) USING BTREE,
          KEY `mid` (`mid`) USING BTREE,
          KEY `createtime` (`createtime`) USING BTREE
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");

    if (!pdo_fieldexists2("ddwx_admin_setapp_h5", "adapay_appid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_h5` ADD COLUMN `adapay_appid` varchar(100) DEFAULT NULL COMMENT 'adapay 的appid';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_h5` ADD COLUMN `adapay_api_key_live` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_h5` ADD COLUMN `adapay_rsa_private_key` text;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_h5` ADD COLUMN `adapay_union` tinyint(1) DEFAULT '0';");
    }
    if (!pdo_fieldexists2("ddwx_admin_set", "withdraw_adapay")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`  ADD COLUMN `withdraw_adapay` tinyint(1) DEFAULT '0' COMMENT '汇付天下银行卡提现';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_adapay_member` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `mid` int(11) DEFAULT '0',
      `member_id` varchar(100) DEFAULT NULL COMMENT 'ada用户id',
      `appid` varchar(100) DEFAULT NULL COMMENT '注册的appid',
      `bank_name` varchar(50) DEFAULT NULL COMMENT '银行',
      `realname` varchar(255) DEFAULT NULL COMMENT '姓名',
      `idcard` varchar(255) DEFAULT NULL COMMENT '身份证号',
      `card_id` varchar(32) DEFAULT NULL COMMENT '银行卡号',
      `tel_no` varchar(20) DEFAULT NULL COMMENT '预留手机号',
      `apply_id` varchar(64) DEFAULT NULL COMMENT '申请id',
      `settle_account_id` varchar(64) DEFAULT NULL COMMENT '由 Adapay 生成的结算账户对象 id',
      `account_info` text COMMENT '结算账户信息',
      `createtime` int(11) DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8; ");

    if (!pdo_fieldexists2("ddwx_business_sysset", "withdraw_adapay")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset`  ADD COLUMN `withdraw_adapay` tinyint(1) DEFAULT '1' COMMENT '汇付天下提现';");
    }
}
if(getcustom('team_auth')){
    if (!pdo_fieldexists2("ddwx_member_level", "team_month_data")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `team_month_data` tinyint(1) NOT NULL DEFAULT 0 COMMENT '查看团队月度数据权限 0无1有';");
    }
    if (!pdo_fieldexists2("ddwx_member_level", "team_down_total")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `team_down_total` tinyint(1) NOT NULL DEFAULT 0 COMMENT '查看团队下级人数权限 0无1有';");
    }
    if (!pdo_fieldexists2("ddwx_member_level", "team_yeji")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `team_yeji` tinyint(1) NOT NULL DEFAULT 0 COMMENT '查看团队业绩权限 0无 1有';");
    }
    if (!pdo_fieldexists2("ddwx_member_level", "team_self_yeji")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `team_self_yeji` tinyint(1) NOT NULL DEFAULT 0 COMMENT '查看个人业绩权限 0无 1有';");
    }
    if (!pdo_fieldexists2("ddwx_member_level", "team_score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `team_score` tinyint(1) NOT NULL DEFAULT 0 COMMENT '查看团队积分权限 0无1有';");
    }
}
if(getcustom('fenhong_max')){
    if (!pdo_fieldexists2("ddwx_member", "fenhong_max")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `fenhong_max`  decimal(12,2) NOT NULL DEFAULT 0 COMMENT '股东分红上限';");
    }
    if (!pdo_fieldexists2("ddwx_admin_set", "fenhong_max_add")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `fenhong_max_add`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '股东分红上限是否累加低级别 0否 1是';");
    }
}
if(getcustom('teamyeji_show')){
    if (!pdo_fieldexists2("ddwx_admin_set", "teamyeji_self")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `teamyeji_self` tinyint(1) NOT NULL DEFAULT 0 COMMENT '团队业绩是否包含自身 0不包含 1包含';");
    }
}
if(getcustom('mh_link')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_mohe_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `cid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `subname` varchar(255) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `content` longtext,
  `readcount` int(11) DEFAULT '0',
  `sort` int(11) DEFAULT '0',
  `createtime` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
}

if(getcustom('scoreshop_fenhong')){
    if(!pdo_fieldexists2("ddwx_scoreshop_product","fenhongset")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` ADD `fenhongset` int(11) DEFAULT '1' COMMENT '分红设置';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` ADD `gdfenhongset` int(2) DEFAULT '0' COMMENT '0按会员等级 1价格比例  2固定金额 -1不参与分红';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` ADD `gdfenhongdata1` text;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` ADD `gdfenhongdata2` text;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` ADD `teamfenhongset` int(2) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` ADD `teamfenhongdata1` text;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` ADD `teamfenhongdata2` text;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` ADD `areafenhongset` int(2) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` ADD `areafenhongdata1` text;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` ADD `areafenhongdata2` text;");
    }
    if(!pdo_fieldexists2("ddwx_scoreshop_order_goods","isfenhong")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order_goods` ADD COLUMN `isfenhong` tinyint(1) NULL DEFAULT '0';");
        \think\facade\Db::execute("UPDATE `ddwx_scoreshop_order_goods` SET `isfenhong` = '1';");
    }

}
if(getcustom('product_baodan')){
    if(!pdo_fieldexists2("ddwx_admin_set","baodan_beishu")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`  ADD COLUMN `baodan_beishu` int(11) DEFAULT '1' COMMENT '报单倍数';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","product_baodan")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product`  ADD COLUMN `product_baodan` tinyint(1) DEFAULT '0' COMMENT '是否是报单产品';");
    }
    if(!pdo_fieldexists2("ddwx_member","baodan_max")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member`  ADD COLUMN `baodan_max` decimal(12,2) DEFAULT '0.00' COMMENT '报单上限';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member`  ADD COLUMN `baodan_freeze` decimal(17,6) DEFAULT '0.000000' COMMENT '报单冻结佣金';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_baodan_freeze_log` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `mid` int(11) DEFAULT NULL,
      `commission` decimal(17,6) DEFAULT NULL,
      `after` decimal(17,6) DEFAULT NULL,
      `remark` varchar(255) DEFAULT NULL,
      `createtime` int(11) DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `aid` (`aid`) USING BTREE,
      KEY `mid` (`mid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}
if(getcustom('teamfenhong_max')){
    if(!pdo_fieldexists2("ddwx_admin_set","teamfenhong_max_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`  ADD COLUMN `teamfenhong_max_type` tinyint(1) DEFAULT '0' COMMENT '团队分红上限 类型0：默认 1：订单金额';");
    }
}
if(getcustom('payaftergive_bind_bids')){
    if(!pdo_fieldexists2("ddwx_payaftergive","bind_bids")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_payaftergive`
ADD COLUMN `bind_bids` longtext NULL;");
    }
}

if(getcustom('extend_tour')) {
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_tour_activity` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NOT NULL DEFAULT 0,
		`bid` int(11) NOT NULL DEFAULT 0,
		`name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '名称',
		`goods_sn` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '商品货号',
		`pic` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '背景图片',
		`pic_width` int(11) NOT NULL DEFAULT 0 COMMENT '图片长度',
		`pic_height` int(11) NOT NULL DEFAULT 0 COMMENT '图片宽度',
		`content` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
		`pic_local` tinyint(1) NOT NULL DEFAULT 0 COMMENT '生成相册偏向 0：大小一样 1：居中显示 2、靠上显示 3、靠上显示 ',
		`detail` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '详情',
		`sell_price` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '价格',
		`tour_price` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '导游价',
		`sales` int(11) NOT NULL DEFAULT 0 COMMENT '销量',
		`status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0：关闭 1：开启',
		`is_del` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否删除 0：否 1：是',
		`createtime` int(11) UNSIGNED NOT NULL DEFAULT 0,
		`updatetime` int(11) UNSIGNED NOT NULL DEFAULT 0,
		`freighttype` tinyint(1) NULL DEFAULT 1,
		`freightdata` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`freightcontent` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
		`img_upnum` int(11) NOT NULL DEFAULT 0 COMMENT '图片上传数量',
		`img_uptip` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '图片上传提示',
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `aid`(`aid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_tour_activity_order` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) NULL DEFAULT NULL,
	  `bid` int(11) NULL DEFAULT 0,
	  `mid` int(11) NULL DEFAULT NULL,
	  `ordernum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `title` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
	  `proid` int(11) NULL DEFAULT NULL,
	  `proname` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `goods_sn` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '商品货号',
	  `propic` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `num` int(11) NULL DEFAULT 1,
	  `cost_price` decimal(10, 2) NOT NULL DEFAULT 0.00,
	  `sell_price` decimal(10, 2) NULL DEFAULT NULL,
	  `totalprice` float(11, 2) NULL DEFAULT NULL,
	  `product_price` float(11, 2) NULL DEFAULT 0.00,
	  `tour_code` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '导游码',
	  `tmid` int(11) NOT NULL DEFAULT 0 COMMENT '导游id',
	  `tour_code_decprice` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '导游码优惠价格',
	  `travel_id` int(11) NOT NULL DEFAULT 0 COMMENT '旅行社id',
	  `freight_price` float(11, 2) NULL DEFAULT NULL,
	  `givescore` int(11) NOT NULL DEFAULT 0,
	  `createtime` int(11) NULL DEFAULT NULL,
	  `status` int(11) NULL DEFAULT 0 COMMENT '0未支付;1已支付;2已发货,3已收货',
	  `linkman` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `tel` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `email` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '邮箱',
	  `area` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `area2` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `longitude` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `latitude` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `message` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `express_com` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `express_no` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `refund_reason` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `refund_money` decimal(11, 2) NULL DEFAULT 0.00,
	  `refund_status` int(1) NULL DEFAULT 0 COMMENT '1申请退款审核中 2已同意退款 3已驳回',
	  `refund_time` int(11) NULL DEFAULT NULL,
	  `refund_checkremark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `payorderid` int(11) NULL DEFAULT NULL,
	  `paytypeid` int(11) NULL DEFAULT NULL,
	  `paytype` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `paynum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `paytime` int(11) NULL DEFAULT NULL,
	  `delete` int(1) NULL DEFAULT 0,
	  `freight_id` int(11) NULL DEFAULT NULL,
	  `freight_text` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `freight_type` tinyint(1) NULL DEFAULT 0,
	  `mdid` int(11) NULL DEFAULT NULL,
	  `freight_time` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `freight_content` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
	  `send_time` bigint(20) NULL DEFAULT NULL COMMENT '发货时间',
	  `collect_time` int(11) NULL DEFAULT NULL COMMENT '收货时间',
	  `hexiao_code` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `hexiao_qr` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `platform` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'wx',
	  `field1` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `field2` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `field3` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `field4` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `field5` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `iscomment` tinyint(1) NULL DEFAULT 0,
	  `is_tuisong` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否推送过 0：否 1：是',
	  `yf_order_sn` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
	  `yf_amount` decimal(10, 2) NOT NULL DEFAULT 0.00,
	  `yf_submit_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
	  `yf_shipping_name` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
	  `yf_shipping_fee` decimal(10, 2) NOT NULL DEFAULT 0.00,
	  `yf_goods_amount` decimal(10, 2) NOT NULL DEFAULT 0.00,
	  `yf_address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
	  `yf_consignee` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
	  `yf_mobile` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
	  `yf_custom_words` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
	  `yf_status` tinyint(1) NOT NULL DEFAULT 0,
	  PRIMARY KEY (`id`) USING BTREE,
	  UNIQUE INDEX `hexiao_code`(`hexiao_code`) USING BTREE,
	  INDEX `aid`(`aid`) USING BTREE,
	  INDEX `bid`(`bid`) USING BTREE,
	  INDEX `mid`(`mid`) USING BTREE,
	  INDEX `status`(`status`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_tour_activity_order_img` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NOT NULL DEFAULT 0,
		`bid` int(11) NOT NULL DEFAULT 0,
		`mid` int(11) NOT NULL DEFAULT 0,
		`orderid` int(11) NOT NULL DEFAULT 0,
		`pics` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '图片',
		`pdf` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '转换的pdf',
		`tpl_key` int(11) NOT NULL DEFAULT 0 COMMENT '使用到第几个模板了',
		`is_del` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否删除 0：否 1是',
		`createtime` int(11) UNSIGNED NOT NULL DEFAULT 0,
		`updatetime` int(11) UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_tour_activity_template` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NOT NULL DEFAULT 0,
		`bid` int(11) NOT NULL DEFAULT 0,
		`taid` int(11) NOT NULL DEFAULT 0,
		`name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '名称',
		`pic` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '背景图片',
		`pic_width` int(11) NOT NULL DEFAULT 0 COMMENT '图片长度',
		`pic_height` int(11) NOT NULL DEFAULT 0 COMMENT '图片宽度',
		`content` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
		`pic_local` tinyint(1) NOT NULL DEFAULT 0 COMMENT '生成相册偏向 0：大小一样 1：居中显示 2、靠上显示 3、靠上显示 ',
		`img_upnum` int(11) NOT NULL DEFAULT 0 COMMENT '图片上传数量',
		`img_uptip` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '图片上传提示',
		`sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
		`status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0：关闭 1：开启',
		`is_del` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否删除 0：否 1：是',
		`createtime` int(11) UNSIGNED NOT NULL DEFAULT 0,
		`updatetime` int(11) UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `aid`(`aid`) USING BTREE,
		INDEX `taid`(`taid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_tour_member` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NOT NULL DEFAULT 0,
		`bid` int(11) NOT NULL DEFAULT 0,
		`mid` int(11) NOT NULL DEFAULT 0,
		`travel_id` int(11) NOT NULL DEFAULT 0 COMMENT '旅行社',
		`name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '真实姓名',
		`tel` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '手机号',
		`code` varchar(7) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '导游码',
		`status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 -1：驳回 0：待审核 1：通过',
		`reason` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '驳回原因',
		`is_del` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否删除 0：否 1：是',
		`createtime` int(11) UNSIGNED NOT NULL DEFAULT 0,
		`updatetime` int(11) UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `aid`(`aid`) USING BTREE,
		INDEX `mid`(`mid`) USING BTREE,
		INDEX `travel_id`(`travel_id`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_tour_set` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NOT NULL DEFAULT 0,
		`bid` int(11) NOT NULL DEFAULT 0,
		`img_upnum` int(11) NOT NULL DEFAULT 0 COMMENT '图片上传数量',
		`img_uptip` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '图片上传提示',
		`freighttype` tinyint(1) NULL DEFAULT 1 COMMENT '配送方式',
		`freightdata` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`freightcontent` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
		`yf_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '有福网状态 0：关闭 1：开启',
		`yf_account` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '有福网账号',
		`yf_pwd` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '有福网密码',
		`createtime` int(11) UNSIGNED NOT NULL DEFAULT 0,
		`updatetime` int(11) UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_tour_travel` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NOT NULL DEFAULT 0,
		`bid` int(11) NOT NULL DEFAULT 0,
		`name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '名称',
		`ewm` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '二维码',
		`ewm_code` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '二维码code',
		`status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0：关闭 1：开启',
		`is_del` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否删除 0：否 1：是',
		`createtime` int(11) UNSIGNED NOT NULL DEFAULT 0,
		`updatetime` int(11) UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `aid`(`aid`) USING BTREE,
		INDEX `ewm_code`(`ewm_code`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    if(!pdo_fieldexists2("ddwx_tour_activity","pics")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_tour_activity` ADD COLUMN `pics` varchar(5000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_tour_set` ADD COLUMN `email_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '邮箱状态 0：关闭 1：开启';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_tour_set` ADD COLUMN `email` varchar(100) NULL DEFAULT '' COMMENT '邮箱';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_tour_activity_order` ADD COLUMN `check_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否审核 0：未审核 1：已审核';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_tour_activity_order_img` ADD COLUMN `album` text NULL COMMENT '相册';");
    }
    if(!pdo_fieldexists2("ddwx_tour_set","can_refund")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_tour_set` ADD COLUMN `can_refund` tinyint(1) NOT NULL DEFAULT 0 COMMENT '退款 0：关闭 1：开启';");
    }
    if(!pdo_fieldexists2("ddwx_tour_activity_order","yf_order_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_tour_activity_order` ADD COLUMN `yf_order_status` varchar(20) NOT NULL DEFAULT '' COMMENT '有福订单状态';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_tour_activity_order` ADD COLUMN `yf_pay_status` varchar(20) NOT NULL DEFAULT '' COMMENT '有福支付状态';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_tour_activity_order` ADD COLUMN `yf_shipping_status` varchar(20) NOT NULL DEFAULT '' COMMENT '有福发货状态';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_tour_set` ADD COLUMN `uporder_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新订单时间';");
    }
    if(!pdo_fieldexists2("ddwx_tour_member","pic")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_tour_member` ADD COLUMN `pic` varchar(255) NOT NULL DEFAULT '' COMMENT '图片';");
    }
    if(!pdo_fieldexists2("ddwx_tour_member","tour_id_number")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_tour_member` ADD COLUMN `tour_id_number` varchar(50) NOT NULL DEFAULT '' COMMENT '导游证件号码';");
    }
}

if(getcustom('hide_home_button')){
    if(!pdo_fieldexists2("ddwx_admin_set","hide_home_button")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`  ADD COLUMN `hide_home_button` tinyint(4) NOT NULL DEFAULT 0 COMMENT '是否显示小程序左上角主页按钮 0显示 1隐藏';");
    }
}
if(getcustom('business_poster')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_business_set_poster` (
        `id`  int(11) NOT NULL AUTO_INCREMENT ,
        `aid`  int(11) NULL DEFAULT NULL ,
        `bid`  int(1) NOT NULL DEFAULT 0 ,
        `type`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'index' COMMENT 'index,product,collage,collageteam,kanjia,kanjiajoin' ,
        `platform`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'mp' COMMENT 'mp,wx,alipay,baidu,toutiao,qq,app' ,
        `content`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
        `guize`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
        `createtime`  int(11) NULL DEFAULT NULL ,
        `poster`  text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
        PRIMARY KEY (`id`),
        INDEX `aid` (`aid`) USING BTREE 
        ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=Dynamic;");
}

if(getcustom('article_gather')){
    if(!pdo_fieldexists2("ddwx_article","is_gather")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_article` ADD COLUMN `is_gather` tinyint(1) NOT NULL DEFAULT 0 COMMENT '采集样式 0：未开启 1：开启';");
    }
}

if(getcustom('maidan_pay_ads')) {
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_maidan_ads` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `aid` int(11) NOT NULL DEFAULT '0',
        `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态0：关闭 1：开启',
        `createtime` int(11) unsigned NOT NULL DEFAULT '0',
        `pic` text COMMENT '广告位图片',
        `url` text COMMENT '图片链接',
        `name` varchar(255) DEFAULT '',
        `is_bind_bid` tinyint(1) DEFAULT '0',
        `bind_bids` text,
        `urlname` varchar(255) DEFAULT '' COMMENT '图片链接',
        `sort` tinyint(6) DEFAULT '0',
        PRIMARY KEY (`id`) USING BTREE,
        KEY `aid` (`aid`) USING BTREE
        ) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    if(!pdo_fieldexists2("ddwx_maidan_ads","scene")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_maidan_ads` ADD COLUMN `scene` varchar (255) NOT NULL DEFAULT '-1' COMMENT '适用模块';");
    }
}

if(getcustom('business_mendian_num_limit')) {
    if(!pdo_fieldexists2("ddwx_business","mendian_num_limit")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `mendian_num_limit` int(11) NULL DEFAULT 0 COMMENT '门店数量限制';");
    }
}
if(getcustom('yx_moneypay')) {
    if(!pdo_fieldexists2("ddwx_shop_order", "cuxiao_money")){
        \think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD COLUMN `cuxiao_money` decimal(11, 2)  NOT NULL  DEFAULT 0.00 COMMENT '促销金额';");
        \think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD COLUMN `cuxiao_id` int NOT NULL DEFAULT 0 COMMENT '促销id';");

        \think\facade\Db::execute("ALTER TABLE ddwx_collage_product ADD COLUMN `moneypay` tinyint(1) NOT NULL DEFAULT 1 COMMENT '余额支付 0：关闭 1：开启';");
        \think\facade\Db::execute("ALTER TABLE ddwx_cuxiao ADD COLUMN `moneypay` tinyint(1) NOT NULL DEFAULT 1 COMMENT '余额支付 0：关闭 1：开启';");
    }
}
if(getcustom('member_recharge_yj')) {
    if(!pdo_fieldexists2("ddwx_admin_set", "rechargeyj_withdraw")){
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `rechargeyj_withdraw` tinyint(1) NULL DEFAULT 0 COMMENT '充值业提现 0：关闭 1：开启' ;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `rechargeyj_withdrawmin` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '充值业提现最小金额';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `rechargeyj_withdrawfee` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '充值业提现手续费(%)';");

        \think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD COLUMN `open_yj` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否开启业绩 0：否 1：是';");
        \think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD COLUMN `recharge_yj_ratio` float NOT NULL DEFAULT 0 COMMENT '充值业绩比例';");
        \think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD COLUMN `yj_datas` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '提现比例';");
        \think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD COLUMN `yj_moneys_after` float NOT NULL DEFAULT 0 COMMENT '提现比例超出金额';");
        \think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD COLUMN `yj_ratios_after` float NOT NULL DEFAULT 0 COMMENT '提现比例超出金额提现比例';");

        \think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `rechargeyj_money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '业绩';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_recharge_yj_log` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NOT NULL DEFAULT 0,
		`mid` int(11) NOT NULL DEFAULT 0,
		`orderid` int(11) NOT NULL DEFAULT 0 COMMENT '充值记录id',
		`money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '充值钱数',
		`recharge_yj_ratio` float NOT NULL DEFAULT 0 COMMENT '充值业绩比例',
		`after` decimal(10, 2) NOT NULL DEFAULT 0.00,
		`get_yj` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '获得的业绩',
		`createtime` int(11) NOT NULL DEFAULT 0,
		`updatetime` int(11) NOT NULL DEFAULT 0,
		`remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '备注',
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `aid`(`aid`) USING BTREE,
		INDEX `mid`(`mid`) USING BTREE
    ) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_recharge_yj_withdrawlog` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NULL DEFAULT NULL,
		`mid` int(11) NULL DEFAULT NULL,
		`rechargeyj_money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '提现业绩',
		`money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '实际提现金额',
		`txmoney` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '转换金额',
		`ratio` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '业绩转换金额比例',
		`rechargeyj_withdrawfee` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '提现手续费比例',
		`aliaccount` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`aliaccountname` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '支付宝姓名',
		`ordernum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`paytype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`status` tinyint(1) NULL DEFAULT 0 COMMENT '0审核中，1已审核，2已驳回，3已打款',
		`createtime` int(11) NULL DEFAULT NULL,
		`bankname` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`bankcarduser` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`bankcardnum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`paytime` int(11) NULL DEFAULT NULL,
		`paynum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`platform` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'wx',
		`reason` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `aid`(`aid`) USING BTREE,
		INDEX `mid`(`mid`) USING BTREE,
		INDEX `createtime`(`createtime`) USING BTREE,
		INDEX `status`(`status`) USING BTREE
    ) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}

if(getcustom('scoreshop_wx_hongbao')){
    if(!pdo_fieldexists2("ddwx_scoreshop_product", "type")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product`  ADD COLUMN `type` tinyint(1) DEFAULT '0' COMMENT '0商城模式 1：兑换红包';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product`  ADD COLUMN `hongbao_money` decimal(11,2) DEFAULT NULL COMMENT '红包金额';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product`  ADD COLUMN `scene_id` varchar(100) DEFAULT NULL COMMENT '红包场景';");
    }
    if(!pdo_fieldexists2("ddwx_scoreshop_order_goods", "type")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order_goods`  ADD COLUMN `type` tinyint(1) DEFAULT '0' COMMENT '0:商城模式 1：兑换红包';");
    }
    if(!pdo_fieldexists2("ddwx_scoreshop_order", "send_remark")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order`  ADD COLUMN `send_remark` varchar(255) DEFAULT NULL COMMENT '发放日志';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order`  ADD COLUMN `type` tinyint(1) DEFAULT '0' COMMENT '0商城模式  1兑换红包';");
    }
    if(!pdo_fieldexists2("ddwx_scoreshop_sysset", "buymax")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_sysset`  ADD COLUMN `buymax` int(11) DEFAULT '0' COMMENT '每人每天限兑';");
    }
    if(!pdo_fieldexists2("ddwx_scoreshop_product", "everyday_buymax")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product`  ADD COLUMN `everyday_buymax` int(4) DEFAULT '0';");
    }
}
if(getcustom('business_num_limit')){
    if(!pdo_fieldexists2("ddwx_admin", "business_num_limit")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `business_num_limit` int(11) NULL DEFAULT 0;");
    }
}
if(getcustom('index_fav_tip')){
    if(!pdo_fieldexists2("ddwx_admin_set", "indexfavtip")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `indexfavtip` tinyint(1) NOT NULL DEFAULT 0 COMMENT '首次打开首页右上角提示添加到桌面并收藏 0：关闭 1：开启 ' ;");
    }
}
if(getcustom('workorder')){
    if(!pdo_fieldexists2("ddwx_workorder_liucheng", "cid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_liucheng` ADD COLUMN `cid` int(11) NOT NULL DEFAULT 0 COMMENT '工单类型id' ;");
    }
}
if(getcustom('member_code')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_code_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `no_start` varchar(60) DEFAULT '10000000' COMMENT '开始编号',
  `no_length` varchar(60) DEFAULT '8' COMMENT '编号长度',
  `no_type` int(3) NOT NULL DEFAULT '1' COMMENT '编号类型：1纯数字，6数字+大写字母',
  `no_before` varchar(255) DEFAULT NULL COMMENT '前缀',
  `status` tinyint(4) DEFAULT '0' COMMENT '0关闭，1开启',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    if(!pdo_fieldexists2("ddwx_member", "member_code")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` 
    ADD COLUMN `member_code` varchar(60) NULL COMMENT '会员码' AFTER `yqcode`,
    ADD COLUMN `member_code_img` varchar(255) NULL COMMENT '会员码' AFTER `member_code`,
    ADD INDEX `member_code`(`member_code`);");
    }
}
if(getcustom('shop_buy_worknum')){
    if(!pdo_fieldexists2("ddwx_freight","worknum_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_freight` ADD COLUMN `worknum_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '工号 0：开启 1：关闭' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `worknum` varchar(30) NOT NULL DEFAULT '' COMMENT '工号' ;");
    }
}
if(getcustom('commission_jinsuo')){
    if(!pdo_fieldexists2("ddwx_admin_set", "fx_jinsuo")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `fx_jinsuo`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '分销紧缩 0不开启 1开启';");
    }
}
if(getcustom('teamfenhong_yueji')){
    if(!pdo_fieldexists2("ddwx_admin_set", "teamfenhong_yueji")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `teamfenhong_yueji`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '团队分红不允许越级 0关1开启';");
    }
}

if(getcustom('extend_gift_bag')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_gift_bag` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) NOT NULL DEFAULT 0,
		  `bid` int(11) NOT NULL DEFAULT 0,
		  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '名称',
		  `shortdesc` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '简介',
		  `pic` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '主图片',
		  `pics` varchar(5000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
		  `detail` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '详情',
		  `sell_price` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '价格',
		  `sales` int(11) NOT NULL DEFAULT 0 COMMENT '销量',
		  `limit_num` int(11) NOT NULL DEFAULT 0 COMMENT '限领数量',
		  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0：关闭 1：开启',
		  `is_del` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否删除 0：否 1：是',
		  `createtime` int(11) UNSIGNED NOT NULL DEFAULT 0,
		  `updatetime` int(11) UNSIGNED NOT NULL DEFAULT 0,
		  PRIMARY KEY (`id`) USING BTREE,
		  INDEX `aid`(`aid`) USING BTREE
    ) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_gift_bag_list` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) NOT NULL DEFAULT 0,
		  `bid` int(11) NOT NULL DEFAULT 0,
		  `gbid` int(11) NOT NULL DEFAULT 0 COMMENT '礼包id',
		  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '名称',
		  `shortdesc` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '简介',
		  `pic` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '主图片',
		  `pics` varchar(5000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
		  `detail` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '详情',
		  `sell_price` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '价格',
		  `sales` int(11) NOT NULL DEFAULT 0 COMMENT '销量',
		  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0：关闭 1：开启',
		  `is_del` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否删除 0：否 1：是',
		  `createtime` int(11) UNSIGNED NOT NULL DEFAULT 0,
		  `updatetime` int(11) UNSIGNED NOT NULL DEFAULT 0,
		  PRIMARY KEY (`id`) USING BTREE,
		  INDEX `aid`(`aid`) USING BTREE
    ) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_gift_bag_order` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) NULL DEFAULT NULL,
		  `bid` int(11) NULL DEFAULT 0,
		  `mid` int(11) NULL DEFAULT NULL,
		  `ordernum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `title` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
		  `gbid` int(11) NULL DEFAULT NULL,
		  `proname` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
		  `propic` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `num` int(11) NULL DEFAULT 1,
		  `cost_price` decimal(10, 2) NOT NULL DEFAULT 0.00,
		  `sell_price` decimal(10, 2) NULL DEFAULT NULL,
		  `totalprice` float(11, 2) NULL DEFAULT NULL,
		  `product_price` float(11, 2) NULL DEFAULT 0.00,
		  `freight_price` float(11, 2) NULL DEFAULT NULL,
		  `givescore` int(11) NOT NULL DEFAULT 0,
		  `createtime` int(11) NULL DEFAULT NULL,
		  `status` int(11) NULL DEFAULT 0 COMMENT '0未支付;1已支付;2已发货,3已收货',
		  `linkman` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `tel` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `email` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '邮箱',
		  `area` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `area2` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `longitude` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `latitude` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `message` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `express_com` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `express_no` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `refund_reason` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `refund_money` decimal(11, 2) NULL DEFAULT 0.00,
		  `refund_status` int(1) NULL DEFAULT 0 COMMENT '1申请退款审核中 2已同意退款 3已驳回',
		  `refund_time` int(11) NULL DEFAULT NULL,
		  `refund_checkremark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `payorderid` int(11) NULL DEFAULT NULL,
		  `paytypeid` int(11) NULL DEFAULT NULL,
		  `paytype` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `paynum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `paytime` int(11) NULL DEFAULT NULL,
		  `delete` int(1) NULL DEFAULT 0,
		  `freight_id` int(11) NULL DEFAULT NULL,
		  `freight_text` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `freight_type` tinyint(1) NULL DEFAULT 0,
		  `mdid` int(11) NULL DEFAULT NULL,
		  `freight_time` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `freight_content` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
		  `send_time` bigint(20) NULL DEFAULT NULL COMMENT '发货时间',
		  `collect_time` int(11) NULL DEFAULT NULL COMMENT '收货时间',
		  `hexiao_code` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `hexiao_qr` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `platform` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'wx',
		  `field1` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `field2` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `field3` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `field4` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `field5` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `iscomment` tinyint(1) NOT NULL DEFAULT 0,
		  `business_total_money` decimal(11, 2) NOT NULL DEFAULT 0.00,
		  `hexiao_mdid` int(11) NOT NULL DEFAULT 0 COMMENT '核销门店id',
		  `hexiao_mid` int(11) NOT NULL DEFAULT 0 COMMENT '核销用户id',
		  `hexiao_uid` int(11) NOT NULL DEFAULT 0 COMMENT '核销账号id',
		  PRIMARY KEY (`id`) USING BTREE,
		  UNIQUE INDEX `hexiao_code`(`hexiao_code`) USING BTREE,
		  INDEX `aid`(`aid`) USING BTREE,
		  INDEX `bid`(`bid`) USING BTREE,
		  INDEX `mid`(`mid`) USING BTREE,
		  INDEX `status`(`status`) USING BTREE
    ) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_gift_bag_order_goods` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) NULL DEFAULT NULL,
		  `bid` int(11) NULL DEFAULT 0,
		  `mid` int(11) NULL DEFAULT NULL,
		  `orderid` int(11) NULL DEFAULT NULL,
		  `ordernum` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `gbid` int(11) NOT NULL DEFAULT 0 COMMENT '礼包id',
		  `proid` int(11) NULL DEFAULT NULL COMMENT '礼包活动id',
		  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `pic` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `procode` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `barcode` varchar(60) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `num` int(11) UNSIGNED NOT NULL DEFAULT 0,
		  `refund_num` int(11) UNSIGNED NOT NULL DEFAULT 0,
		  `refund_money` decimal(11, 2) NULL DEFAULT 0.00,
		  `cost_price` decimal(11, 2) NULL DEFAULT NULL,
		  `sell_price` decimal(11, 2) NULL DEFAULT NULL,
		  `totalprice` decimal(11, 2) NULL DEFAULT NULL,
		  `total_weight` decimal(11, 2) UNSIGNED NULL DEFAULT 0.00,
		  `scoredk_money` decimal(11, 2) NULL DEFAULT 0.00,
		  `leveldk_money` decimal(11, 2) NULL DEFAULT 0.00,
		  `manjian_money` decimal(11, 2) NULL DEFAULT 0.00,
		  `coupon_money` decimal(11, 2) NULL DEFAULT 0.00,
		  `real_totalprice` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '实际商品销售金额 减去了优惠券抵扣会员折扣满减积分抵扣的金额',
		  `business_total_money` decimal(11, 2) NOT NULL DEFAULT 0.00,
		  `status` int(1) NULL DEFAULT 0 COMMENT '0未付款1已付款2已发货3已收货4申请退款',
		  `createtime` int(11) NULL DEFAULT NULL,
		  `paytime` int(11) NOT NULL DEFAULT 0,
		  `endtime` int(11) NULL DEFAULT NULL,
		  `iscomment` tinyint(1) NULL DEFAULT 0,
		  `parent1` int(11) NULL DEFAULT NULL,
		  `parent2` int(11) NULL DEFAULT NULL,
		  `parent3` int(11) NULL DEFAULT NULL,
		  `parent4` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `parent1commission` decimal(11, 2) NULL DEFAULT 0.00,
		  `parent2commission` decimal(11, 2) NULL DEFAULT 0.00,
		  `parent3commission` decimal(11, 2) NULL DEFAULT 0.00,
		  `parent4commission` decimal(11, 2) NULL DEFAULT NULL,
		  `parent1score` int(11) NULL DEFAULT 0,
		  `parent2score` int(11) NULL DEFAULT 0,
		  `parent3score` int(11) NULL DEFAULT 0,
		  `iscommission` tinyint(1) NULL DEFAULT 0 COMMENT '佣金是否已发放',
		  `isfenhong` tinyint(1) NULL DEFAULT 0 COMMENT '分红是否已结算',
		  `isfg` tinyint(1) NULL DEFAULT 0,
		  `isteamfenhong` tinyint(1) NULL DEFAULT 0,
		  `hongbaoEdu` decimal(11, 2) NOT NULL DEFAULT 0.00,
		  `ishongbao` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
		  `isdan` int(11) NULL DEFAULT 0,
		  `usd_sellprice` float(11, 2) NULL DEFAULT 0.00 COMMENT '美元价格',
		  `hexiao_code` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '唯一码 核销码',
		  `hexiao_qr` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '核销码图片',
		  `hexiao_num` int(11) NULL DEFAULT 0,
		  `hexiao_mdid` int(11) NOT NULL DEFAULT 0 COMMENT '核销门店id',
		  `hexiao_mid` int(11) NOT NULL DEFAULT 0 COMMENT '核销用户id',
		  `hexiao_uid` int(11) NOT NULL DEFAULT 0 COMMENT '核销账号id',
		  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '备注',
		  PRIMARY KEY (`id`) USING BTREE,
		  INDEX `aid`(`aid`) USING BTREE,
		  INDEX `bid`(`bid`) USING BTREE,
		  INDEX `mid`(`mid`) USING BTREE,
		  INDEX `orderid`(`orderid`) USING BTREE,
		  INDEX `proid`(`proid`) USING BTREE
	) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_hexiao_giftbagproduct` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) NULL DEFAULT NULL,
		  `bid` int(11) NULL DEFAULT NULL,
		  `uid` int(11) NULL DEFAULT NULL,
		  `mid` int(11) NULL DEFAULT NULL,
		  `proid` int(11) NULL DEFAULT NULL,
		  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `pic` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `ggid` int(11) NULL DEFAULT NULL,
		  `ggname` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `num` int(11) NULL DEFAULT 0,
		  `orderid` int(11) NULL DEFAULT NULL,
		  `ordernum` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `ogid` int(11) NULL DEFAULT NULL,
		  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		  `createtime` int(11) NULL DEFAULT NULL,
		  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
		  PRIMARY KEY (`id`) USING BTREE,
		  INDEX `aid`(`aid`) USING BTREE,
		  INDEX `bid`(`bid`) USING BTREE,
		  INDEX `mid`(`mid`) USING BTREE
	) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}
if(getcustom('print_label_qrcode')){
    if(!pdo_fieldexists2("ddwx_wifiprint_set", "print_qrcode")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_wifiprint_set` ADD COLUMN `print_qrcode` tinyint(1) NULL DEFAULT 0 COMMENT '是否打印二维码';");
    }
}
if(getcustom('lipinka_bind_pid')){
    if(!pdo_fieldexists2("ddwx_lipin_codelist", "pid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_lipin_codelist` ADD COLUMN `pid` int(11) NULL;");
    }
}

if(getcustom('health_assessment')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_health_user` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT '0',
      `bids` text,
      `createtime` int(11) DEFAULT NULL,
      `uid` int(11) DEFAULT '0',
      `remark` varchar(255) DEFAULT '',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE
	) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_health_set` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT '0',
      `cids` varchar(255) DEFAULT '',
      `cnames` text,
      `createtime` int(11) DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `aid` (`aid`) USING BTREE
	) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_health_record_log` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `name` varchar(255) DEFAULT NULL,
      `status` int(1) DEFAULT '1',
      `createtime` int(11) DEFAULT NULL,
      `content` text,
      `mid` int(11) DEFAULT '0',
      `record_id` int(11) DEFAULT '0',
      `ha_child_name` varchar(255) DEFAULT '',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE,
      KEY `bid` (`bid`) USING BTREE
	) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_health_record` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `createtime` int(11) DEFAULT NULL,
      `mid` int(11) DEFAULT '0',
      `ha_id` int(11) DEFAULT '0',
      `fid` int(11) DEFAULT '0',
      `status` tinyint(2) DEFAULT '1',
      `score` int(11) DEFAULT '0' COMMENT '得分',
      `score_tag` varchar(255) DEFAULT NULL COMMENT '分值标签',
      `score_desc` text COMMENT '分值描述',
      `desc` text,
      `name` varchar(64) DEFAULT '',
      `tel` varchar(32) DEFAULT '',
      `sex` tinyint(1) DEFAULT '0',
      `age` int(4) DEFAULT NULL,
      `address` varchar(255) DEFAULT '',
      `child_result` longtext COMMENT '多维指标的得分情况',
      `ha_type` tinyint(2) DEFAULT '0',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE,
      KEY `bid` (`bid`) USING BTREE
	) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_health_question` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `name` varchar(255) DEFAULT NULL,
      `sort` int(11) DEFAULT '0',
      `status` int(1) DEFAULT '1',
      `createtime` int(11) DEFAULT NULL,
      `content` text,
      `ha_id` int(11) DEFAULT '0' COMMENT 'health_assessment表的主键',
      `ha_child_name` varchar(255) DEFAULT '',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE,
      KEY `bid` (`bid`) USING BTREE
	) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_health_form` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `createtime` int(11) DEFAULT NULL,
      `mid` int(11) DEFAULT '0',
      `ha_id` int(11) DEFAULT '0',
      `status` tinyint(2) DEFAULT '1',
      `name` varchar(64) DEFAULT '',
      `tel` varchar(32) DEFAULT '',
      `sex` tinyint(1) DEFAULT '0',
      `age` int(4) DEFAULT NULL,
      `address` varchar(255) DEFAULT '',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE,
      KEY `mid` (`mid`) USING BTREE
	) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_health_assessment` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT '0',
      `bid` int(11) DEFAULT '0',
      `name` varchar(255) DEFAULT '',
      `qrcode` text,
      `status` tinyint(2) DEFAULT '1',
      `createtime` int(11) DEFAULT NULL,
      `remark` text,
      `desc` text,
      `result` longtext,
      `pic` text,
      `content` text,
      `level_content` text,
      `level_desc` text,
      `type` tinyint(1) DEFAULT '0',
      `child_level_content` longtext,
      PRIMARY KEY (`id`),
      KEY `aid` (`aid`) USING BTREE
	) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    if(!pdo_fieldexists2("ddwx_health_record", "updatetime")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_health_record`
MODIFY COLUMN `score_desc`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '分值描述',
ADD COLUMN `updatetime`  int(11) NULL;");
    }
}

if(getcustom('yx_cashback_collage')){
    if(!pdo_fieldexists2("ddwx_cashback", "collageids")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashback` ADD COLUMN `collageids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '多人拼团' ;");
    }
}

if(getcustom('yx_shortvideo_jindubag')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shortvideo_looklog` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) NOT NULL DEFAULT 0,
	  `bid` int(11) NOT NULL DEFAULT 0,
	  `vid` int(11) NOT NULL DEFAULT 0,
	  `mid` int(11) NOT NULL DEFAULT 0,
	  `jindu` int(11) NOT NULL DEFAULT 0 COMMENT '进度',
	  `num` int(11) NOT NULL DEFAULT 0 COMMENT '观看次数',
	  `is_del` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否删除 0 否 1 是',
	  `createtime` int(11) NOT NULL DEFAULT 0,
	  `updatetime` int(11) NOT NULL DEFAULT 0,
	  PRIMARY KEY (`id`) USING BTREE,
	  INDEX `aid`(`aid`) USING BTREE,
	  INDEX `bid`(`bid`) USING BTREE,
	  INDEX `vid`(`vid`) USING BTREE,
	  INDEX `mid`(`mid`) USING BTREE
	) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    if(!pdo_fieldexists2("ddwx_shortvideo", "gbids")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shortvideo` ADD COLUMN `gbids` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '礼包ID';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shortvideo` ADD COLUMN `gbid` int NOT NULL DEFAULT 0 COMMENT '礼包ID';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shortvideo` ADD COLUMN `goodstype` tinyint(1) NOT NULL DEFAULT 0 COMMENT '商品类型 0：商城商品 1：礼包';");

        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `team_shortvideo` tinyint(1) NULL DEFAULT 0 COMMENT '查看下级短视频记录';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shortvideo_sysset` ADD COLUMN `showshare` tinyint(1) NOT NULL DEFAULT 0 COMMENT '底部分享信息' ;");
    }
}
if(getcustom('fenhong_kecheng')){
    if(!pdo_fieldexists2("ddwx_kecheng_list","fenhongset")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_list` ADD `fenhongset` int(11) DEFAULT '1' COMMENT '分红设置';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_list` ADD `gdfenhongset` int(2) DEFAULT '0' COMMENT '0按会员等级 1价格比例  2固定金额 -1不参与分红';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_list` ADD `gdfenhongdata1` text;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_list` ADD `gdfenhongdata2` text;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_list` ADD `teamfenhongset` int(2) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_list` ADD `teamfenhongdata1` text;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_list` ADD `teamfenhongdata2` text;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_list` ADD `areafenhongset` int(2) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_list` ADD `areafenhongdata1` text;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_list` ADD `areafenhongdata2` text;");
    }
    if(!pdo_fieldexists2("ddwx_kecheng_order","isfenhong")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_order` ADD COLUMN `isfenhong` tinyint(1) NULL DEFAULT '1';");
    }
}
if(getcustom('level_auto_down')){
    if(!pdo_fieldexists2("ddwx_member_level", "down_level_day")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `down_level_day`  int(11) NOT NULL DEFAULT 0 COMMENT '升级后自动降级考核天数';");
    }
    if(!pdo_fieldexists2("ddwx_member_level", "down_level_tjr")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `down_level_tjr`  int(11) NOT NULL DEFAULT 0 COMMENT '升级后自动降级考核直推人数';");
    }
    if(!pdo_fieldexists2("ddwx_member_level", "tjr_level_id")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `tjr_level_id`  int(11) NOT NULL DEFAULT 0 COMMENT '降级考核推荐人id';");
    }
    if(!pdo_fieldexists2("ddwx_member_level", "down_level_teamyeji")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `down_level_teamyeji`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '升级后考核自动降级团队业绩';");
    }
    if(!pdo_fieldexists2("ddwx_member_level", "down_level_id")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `down_level_id`  int(11) NOT NULL DEFAULT 0 COMMENT '考核自动降级指定级别id';");
    }
    if(!pdo_fieldexists2("ddwx_member_level", "check_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `check_type`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '降级考核方式 0不考核 1一次性考核 2长期考核';");
    }
    if(!pdo_fieldexists2("ddwx_member_levelup_order", "check_down")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_levelup_order` ADD COLUMN `check_down`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已检测自动降级';");
    }

}
if(getcustom('up_level_agree') || getcustom('up_level_agree2')){
    if(!pdo_fieldexists2("ddwx_member_level", "is_agree")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `is_agree`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否开启升级协议 0不开启 1开启';");
    }
    if(!pdo_fieldexists2("ddwx_member_level", "agree_content")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `agree_content`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '升级协议内容';");
    }
//    if(!pdo_fieldexists2("ddwx_member_level", "check_down")) {
//        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `check_down`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已检测自动降级';");
//    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_level_agree` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) NOT NULL DEFAULT '0',
      `mid` int(11) NOT NULL DEFAULT '0' COMMENT '会员id',
      `newlv_id` int(11) NOT NULL DEFAULT '0' COMMENT '新级别id',
      `sort` int(11) NOT NULL DEFAULT '0' COMMENT '级别排序',
      `cid` int(11) NOT NULL DEFAULT '0' COMMENT '级别到期时间',
      `w_time` int(11) NOT NULL DEFAULT '0' COMMENT '级别开始时间',
      `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否同意升级协议 0未同意 1已同意 2已拒绝',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='升级协议记录';");


    if(!pdo_fieldexists2("ddwx_member_level_agree", "signatureurl")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level_agree` ADD COLUMN `signatureurl` varchar(255) DEFAULT NULL;");
    }
    if(pdo_fieldexists2("ddwx_member_level", "agree_content")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` MODIFY COLUMN `agree_content`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '升级协议内容';");
    }
}
if(getcustom('up_level_agree3')){
    if(!pdo_fieldexists2("ddwx_member_level", "is_agree")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `is_agree`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否开启升级协议 0不开启 1开启';");
    }
    if(!pdo_fieldexists2("ddwx_member_level", "agree_content")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `agree_content`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '升级协议内容';");
    }
}
if(getcustom('yx_cashback_yongjin')){
    if(!pdo_fieldexists2("ddwx_cashback", "cashback_yongjin")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashback` ADD COLUMN `cashback_yongjin`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '返现抵扣抵扣佣金提现 0关闭 1开启';");
    }
    if(!pdo_fieldexists2("ddwx_member", "cash_yongji_total")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `cash_yongji_total`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '佣金提现累计';");
    }
    if(!pdo_fieldexists2("ddwx_member", "cashback_total")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `cashback_total`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '返现累计';");
    }
    if(!pdo_fieldexists2("ddwx_member_commission_withdrawlog", "cashback_id")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_commission_withdrawlog` ADD COLUMN `cashback_id`  int(11) NOT NULL DEFAULT 0 COMMENT '扣除的返现数据id';");
    }

}

if(getcustom('sxpay_h5')){
    if(!pdo_fieldexists2("ddwx_admin_setapp_h5","alisxpay_mno")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_h5`
	        ADD COLUMN `alisxpay_mno` varchar(255) DEFAULT NULL,
	        ADD COLUMN `alisxpay_mchkey` varchar(255) DEFAULT NULL;
        ");
    }
    if(!pdo_fieldexists2("ddwx_sxpay_income","alishiming_status")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_sxpay_income`
	        ADD COLUMN `alishiming_status` tinyint(1) NULL DEFAULT 0 COMMENT '支付宝0未实名 1审核中 2',
			ADD COLUMN `alishiming_qrurl` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
        ");
    }
}
if(getcustom('comwithdrawdate')){
    if(!pdo_fieldexists2("ddwx_admin_set", "comwithdrawdate_money")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `comwithdrawdate_money`  varchar(11) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '余额可提现日期';");
    }
}
if(getcustom('teamfenhong_jiandan')){
    if(!pdo_fieldexists2("ddwx_admin_set", "teamfenhong_jiandan_differential")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
            ADD COLUMN `teamfenhong_jiandan_differential`  tinyint(1) NULL DEFAULT 0 COMMENT '见单分红极差';"
        );
    }
    if(!pdo_fieldexists2("ddwx_member_level", "teamfenhong_jiandan_lv")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level`
            ADD COLUMN `teamfenhong_jiandan_lv`  int(11) NULL DEFAULT 0,
            ADD COLUMN `teamfenhong_jiandan_bl`  decimal(11,2) NULL DEFAULT 0.00,
            ADD COLUMN `teamfenhong_jiandan_money`  decimal(11,2) NOT NULL DEFAULT 0.00 COMMENT '团队见单分红每单奖励',
            ADD COLUMN `teamfenhong_jiandan_only`  tinyint(1) NULL DEFAULT 0,
            ADD COLUMN `teamfenhong_jiandan_self`  tinyint(1) NULL DEFAULT 0;"
        );
    }
    if(!pdo_fieldexists2("ddwx_shop_product", "teamfenhongjdset")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product`
            ADD COLUMN `teamfenhongjdset`  tinyint(1) NULL DEFAULT 0 COMMENT '见单分红设置 0按会员等级 1单独设置奖励比例 2单独设置奖励金额 -1不参与奖励',
            ADD COLUMN `teamfenhongjddata1`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '见单分红单独设置奖励比例数据',
            ADD COLUMN `teamfenhongjddata2`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '见单分红独设置奖励金额数据';"
        );
    }
}
if(getcustom('coupon_other_business')){
    if(!pdo_fieldexists2("ddwx_coupon_set", "show_other_bcoupon")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon_set` ADD COLUMN `show_other_bcoupon`  tinyint(1) NULL DEFAULT 1;");
    }
}

if(getcustom('score_expire')) {
    if (!pdo_fieldexists2("ddwx_admin_set", "score_expire_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` 
    ADD COLUMN `score_expire_status` tinyint(1) NULL DEFAULT '0' COMMENT '积分过期 0关闭，1开启',
    ADD COLUMN `score_expire_days` int(6) NULL DEFAULT '0' COMMENT '积分过期天数，0不过期';");
    }
}
if(getcustom('member_friend')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_friend_group` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) DEFAULT '0',
        `name` varchar(255) DEFAULT '' COMMENT '分组名称',
        `createtime` int(11) DEFAULT NULL,
        `status` tinyint(2) DEFAULT '1',
        `mid` int(11) DEFAULT '0' COMMENT '所属会员',
        PRIMARY KEY (`id`),
        KEY `index_aid` (`aid`,`mid`) USING BTREE
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='好友分组';");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_friend` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) DEFAULT '0',
        `createtime` int(11) DEFAULT NULL,
        `status` tinyint(2) DEFAULT '1',
        `mid` int(11) DEFAULT '0' COMMENT '所属会员',
        `fmid` int(11) DEFAULT '0' COMMENT '好友mid',
        `group_id` int(11) DEFAULT '0' COMMENT '好友分组id',
        `remark` varchar(255) DEFAULT '',
        `from` varchar(64) DEFAULT '',
        PRIMARY KEY (`id`),
        KEY `index_aid` (`aid`,`mid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='好友';");

    if(!pdo_fieldexists2("ddwx_member_level", "is_add_friend")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `is_add_friend`  tinyint(1) NULL DEFAULT 0 COMMENT '是否可加好友';");
    }

    if(!pdo_fieldexists2("ddwx_member", "friend_qrcode")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `friend_qrcode`  varchar(255) NULL DEFAULT '' COMMENT '好友二维码';");
    }

}
if(getcustom('score_friend_transfer')){
    if(!pdo_fieldexists2("ddwx_admin_set","score_transfer_range")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `score_transfer_range` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `score_transfer`;");
    }
}
if(getcustom('money_friend_transfer')){
    if(!pdo_fieldexists2("ddwx_admin_set","money_transfer_range")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `money_transfer_range` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `money_transfer`;");
    }
}
if(getcustom('extend_yuyue_car')){

    if(!pdo_fieldexists2("ddwx_yuyue_product", "type")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD COLUMN `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '类型 0：普通商品 1：洗车商品';");

        \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `yuyuecar_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '预约洗车 0：否 1：是 功能位置：扩展-预约服务-服务商品，类型选项';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_set`   ADD COLUMN `autopd_worker` tinyint(1) NOT NULL DEFAULT 0 COMMENT '派送最近服务人员 0：关闭 1：开启';");

        \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` ADD COLUMN `carid` int NOT NULL DEFAULT 0 COMMENT '车辆信息表id';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` ADD COLUMN `car_number` varchar(20) NOT NULL DEFAULT '' COMMENT '车牌号';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` ADD COLUMN `car_color` varchar(20) NOT NULL DEFAULT '' COMMENT '车辆颜色';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` ADD COLUMN `car_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '车辆类型 0：轿车 1：SUV 2:MPV';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` ADD COLUMN `protype` tinyint(1) NOT NULL DEFAULT 0 COMMENT '商品类型 0：普通 1：洗车';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` ADD COLUMN `startpic` varchar(700) NOT NULL DEFAULT '' COMMENT '洗车开始服务图片';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` ADD COLUMN `endpic` varchar(700) NOT NULL DEFAULT '' COMMENT '洗车完成服务图片';");

        \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_worker_order` ADD COLUMN `startpic` varchar(700) NOT NULL DEFAULT '' COMMENT '洗车开始服务图片' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_worker_order` ADD COLUMN `endpic` varchar(700) NOT NULL DEFAULT '' COMMENT '洗车完成服务图片' ;");
    }

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_car` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NOT NULL DEFAULT 0,
		`mid` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
		`name` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
		`tel` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
		`number` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '车牌',
		`type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '车型 0：轿车 1：SUV 2：MPV',
		`color` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '颜色',
		`isdefault` int(1) NOT NULL DEFAULT 0,
		`createtime` int(11) NOT NULL DEFAULT 0,
		`updatetime` int(11) NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `aid`(`aid`) USING BTREE,
		INDEX `mid`(`mid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='车辆信息';");
}

if(getcustom('form_map')){
    if(!pdo_fieldexists2("ddwx_form_order", "adr_lon")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD COLUMN `adr_lon`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';");
    }
    if(!pdo_fieldexists2("ddwx_form_order", "adr_lat")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD COLUMN `adr_lat`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';");
    }
}
if(getcustom('form_match')){
    if(!pdo_fieldexists2("ddwx_form", "form_match")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `form_match`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否开启搜索数据匹配 0关闭 1开启';");
    }
    if(!pdo_fieldexists2("ddwx_form", "match_limit")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `match_limit`  int(11) NOT NULL DEFAULT 0 COMMENT '数据匹配条数限制';");
    }
    if(!pdo_fieldexists2("ddwx_form", "show_title")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `show_title`  tinyint(1) NOT NULL DEFAULT 1 COMMENT '前端记录页面是否显示标题 1显示 0不显示';");
    }
    if(!pdo_fieldexists2("ddwx_form", "log_title")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `log_title`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '表单记录页面标题';");
    }
    if(!pdo_fieldexists2("ddwx_form", "show_name")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `show_name`  tinyint(1) NOT NULL DEFAULT 1 COMMENT '前端记录页面是否显示名称 1显示 0不显示';");
    }
    if(!pdo_fieldexists2("ddwx_form", "show_time")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `show_time`  tinyint(1) NOT NULL DEFAULT 1 COMMENT '前端记录页面是否显示提交时间 1显示 0不显示';");
    }
    if(!pdo_fieldexists2("ddwx_form", "show_audit")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `show_audit`  tinyint(1) NOT NULL DEFAULT 1 COMMENT '前端记录页面是否显示审核状态 1显示 0不显示';");
    }
    if(!pdo_fieldexists2("ddwx_form", "background_color_down")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `background_color_down`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '向上匹配数据背景色';");
    }
    if(!pdo_fieldexists2("ddwx_form", "match_limit_up")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `match_limit_up`  int(11) NOT NULL DEFAULT 0 COMMENT '向上匹配数据条数';");
    }
    if(!pdo_fieldexists2("ddwx_form", "background_color_up")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `background_color_up`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '向上匹配数据背景色';");
    }
    if(!pdo_fieldexists2("ddwx_form", "desc")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `desc` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '表单记录页说明';");
    }
    if(pdo_fieldexists2("ddwx_form", "desc")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` MODIFY COLUMN `desc` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '表单记录页说明';");
    }

    if(!pdo_fieldexists2("ddwx_form", "quanxian")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `quanxian`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '可查看表单记录的权限';");
    }
    if(!pdo_fieldexists2("ddwx_form", "search_title")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `search_title`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '表单记录页搜索提示语';");
    }
    if(!pdo_fieldexists2("ddwx_form", "noauth_text")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `noauth_text`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '无访问权限提示文字';");
    }
    if(!pdo_fieldexists2("ddwx_form", "noauth_url")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `noauth_url`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '无访问权限跳转链接';");
    }

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_form_match` (
        `id`  int(11) NOT NULL AUTO_INCREMENT ,
        `aid`  int(11) NOT NULL DEFAULT 0 ,
        `bid`  int(11) NOT NULL DEFAULT 0 ,
        `formid`  int(11) NOT NULL DEFAULT 0 COMMENT '表单数据id' ,
        `search_val`  int(11) NOT NULL DEFAULT 0 COMMENT '用户搜索值' ,
        `div_val`  int(11) NOT NULL DEFAULT 0 COMMENT '被除数' ,
        `total`  int(11) NOT NULL DEFAULT 0 COMMENT '总人数' ,
        `remark`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注' ,
        `w_time`  int(10) NOT NULL DEFAULT 0 COMMENT '添加时间' ,
        PRIMARY KEY (`id`),
        INDEX `search_val` (`search_val`) USING BTREE 
        ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='表单匹配数据' ROW_FORMAT=Dynamic;");
}
if(getcustom('sound')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_sound` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `mdid` varchar(255) NULL COMMENT '多个门店使用,间隔',
  `name` varchar(255) DEFAULT NULL COMMENT '音响名称',
  `client_id` varchar(255) DEFAULT NULL,
  `client_secret` varchar(255) DEFAULT NULL,
  `access_token` varchar(255) DEFAULT NULL,
  `device_brand` varchar(255) NULL COMMENT '设备厂商',
  `device_sn` varchar(255) DEFAULT NULL COMMENT '设备编号',
  `device_sign` varchar(255) NULL COMMENT '设备密钥',
  `device_type` tinyint(1) DEFAULT '0' COMMENT '',
  `custom_content` text COMMENT '自定义内容',
  `status` tinyint(1) DEFAULT '1',
  `play_content` varchar(255) DEFAULT 'maidan' COMMENT '播报内容:maidan',
  `voice` tinyint(1) DEFAULT '50' COMMENT '音量',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `client_id` (`client_id`) USING BTREE,
  KEY `device_sn` (`device_sn`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
}

if(getcustom('extend_qrcode')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_qrcode` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NOT NULL DEFAULT 0,
		`name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
		`formurl` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '链接地址',
		`status` tinyint(1) NOT NULL DEFAULT 1,
		`createtime` int(11) NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `aid`(`aid`) USING BTREE,
		INDEX `status`(`status`) USING BTREE,
		INDEX `name`(`name`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_qrcode_list` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NOT NULL DEFAULT 0,
		`qid` int(11) NOT NULL DEFAULT 0 COMMENT '活码表id',
		`pid` int(11) NOT NULL DEFAULT 0 COMMENT '分销者id',
		`qrtype` tinyint(1) NOT NULL DEFAULT 0 COMMENT '类型 1、二维码 2、条形码 3、H5二维码 4、小程序码',
		`qrcode` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '小程序码',
		`code` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT 'code',
		`bindstatus` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否绑定分销商 0：否 1：是',
		`bindtime` int(11) NOT NULL DEFAULT 0 COMMENT '绑定时间',
		`createtime` int(11) NOT NULL DEFAULT 0,
		`updatetime` int(11) NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `aid`(`aid`) USING BTREE,
		INDEX `qid`(`qid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
}
if(getcustom('lipinka_morefee') || getcustom('lipinka_commission') || getcustom('lipinka_freight_free')){
    if(!pdo_fieldexists2("ddwx_lipin", "fee_items")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_lipin` ADD COLUMN `fee_items` text COMMENT '附加费用';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_lipin_order` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NOT NULL DEFAULT 0,
		`bid` int(11) NOT NULL DEFAULT 0,
		`mid` int(11) NOT NULL DEFAULT 0,
		`lpid` int(11) NOT NULL DEFAULT 0 COMMENT '礼品id',
		`codeid` int(11) NOT NULL DEFAULT 0 COMMENT '礼品兑换码id',
		`title` varchar(120) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '兑换的商品标题',
		`ordernum` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '类型订单号（多个后面加_数字 如 2023xxx_1）',
		`type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '类型：0余额 1商品 2积分 3优惠券 4：积分商品（暂时只有商品类型的有）',
		`fee_items` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '费用',
		`totalprice` float(11, 2) NOT NULL DEFAULT 0.00 COMMENT '总费用',
		`status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0：未支付 1：已支付',
		`payorderid` int(11) NOT NULL DEFAULT 0,
		`paytypeid` int(11) NOT NULL DEFAULT 0,
		`paytype` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`paynum` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`paytime` int(11) NOT NULL DEFAULT 0,
		`platform` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
		`createtime` int(11) NOT NULL DEFAULT 0,
		`updatetime` int(11) NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `aid`(`aid`) USING BTREE,
		INDEX `mid`(`mid`) USING BTREE,
		INDEX `lpid`(`lpid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
}
if(getcustom('weight_template')){
    if(!pdo_fieldexists2("ddwx_shop_product", "weightdata")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `weightdata`  varchar(255) DEFAULT NULL,
		ADD COLUMN `weighttype` tinyint(1) DEFAULT '0' COMMENT '重量模板';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order", "weight_price")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `weight_price` decimal(11,2) DEFAULT '0.00' COMMENT '包装费价格';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order_goods", "weight_price")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `weight_price` decimal(11,2) DEFAULT '0.00' COMMENT '包装费价格',
		ADD COLUMN `weight_templateid` int(11) DEFAULT '0' COMMENT '重量模板id';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shop_weight_template` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `name` varchar(255) DEFAULT NULL,
	  `type` tinyint(1) DEFAULT '1' COMMENT '1按重量',
	  `status` int(1) DEFAULT '0',
	  `sort` int(11) DEFAULT '0',
	  `createtime` int(11) DEFAULT NULL,
	  `pricedata` varchar(1000) DEFAULT '',
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `type` (`type`) USING BTREE,
	  KEY `status` (`status`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
}
if(getcustom('freight_desc')){
    if(!pdo_fieldexists2("ddwx_freight", "desc")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_freight` ADD COLUMN `desc` varchar(255) DEFAULT NULL COMMENT '快递说明';");
    }
}

if(getcustom('business_fenhong_memberlevel')){
    if(!pdo_fieldexists2("ddwx_business", "fenhong_memberlevel")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `fenhong_memberlevel` varchar(255) DEFAULT NULL;");
    }
}

if(getcustom('member_levelup_businessnum')){
    if(!pdo_fieldexists2("ddwx_member_level", "up_businessnum")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_businessnum` int NOT NULL DEFAULT 0 COMMENT '增加推荐商家成功入驻数量';");
    }

    if(!pdo_fieldexists2("ddwx_member_level", "up_businessnum_condition")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_businessnum_condition` varchar(30) NULL DEFAULT 'or' COMMENT '推荐商家成功入驻数量条件';");
    }
}
if(getcustom('fenhong_maidan_percent')){
    if(!pdo_fieldexists2("ddwx_member_level", "fenhong_maidan_percent")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `fenhong_maidan_percent` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '分红买单比例';");
    }
}
if(getcustom('maidan_money_dec')){
    if(!pdo_fieldexists2("ddwx_maidan_order", "dec_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_maidan_order` ADD COLUMN `dec_money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '余额抵扣金额' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_maidan_order` ADD COLUMN `money_dec_rate` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '余额抵扣比例' ;");
    }
}
if(getcustom('fenhong_business_item_switch')){
    if(!pdo_fieldexists2("ddwx_business", "gdfenhong_status")) {
        \think\facade\Db::execute("
            ALTER TABLE `ddwx_business` 
            ADD COLUMN `gdfenhong_status` tinyint(1) NULL DEFAULT '0' COMMENT '股东分红：0 关闭 1：开启' ,
            ADD COLUMN `teamfenhong_status` tinyint(1) NULL DEFAULT '0' COMMENT '团队分红：0 关闭 1：开启' ,
            ADD COLUMN `areafenhong_status` tinyint(1) NULL DEFAULT '0' COMMENT '区域分红：0 关闭 1：开启' ;");
    }
}
if(getcustom('restaurant_book_custom')){
    if(!pdo_fieldexists2("ddwx_restaurant_booking_sysset", "level_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_booking_sysset` ADD COLUMN `level_money` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '级别价格';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_booking_sysset", "textset")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_booking_sysset` ADD COLUMN `textset` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '自定义文本';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_table_category", "level_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_table_category` ADD COLUMN `level_money` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '会员等级预定费用';");
    }
}

if(getcustom('member_level_price_rate')) {
    if (!pdo_fieldexists2("ddwx_member_level", "price_rate")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `price_rate` decimal(5, 2) NULL DEFAULT 1 AFTER `discount`;");
    }
    if (!pdo_fieldexists2("ddwx_member", "levelid_price_rate")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `levelid_price_rate` int(11) NULL;");
    }
}
if(getcustom('product_price_rate')) {
    if (!pdo_fieldexists2("ddwx_member", "price_rate")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` 
ADD COLUMN `price_rate` decimal(5, 1) NULL DEFAULT 1,
ADD COLUMN `price_rate_agent` decimal(5, 1) NULL DEFAULT 1 AFTER `price_rate`;");
    }
}

if(getcustom('business_deposit')) {
    if (!pdo_fieldexists2("ddwx_business", "deposit")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `deposit` decimal(11, 2) NULL DEFAULT '0' COMMENT '保证金' AFTER `money`;");
    }
    if (!pdo_fieldexists2("ddwx_business_sysset", "deposit")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `deposit` decimal(11, 2) NULL DEFAULT '0' COMMENT '保证金' AFTER `default_rate`;");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_business_depositlog` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT NULL,
  `money` decimal(11,2) DEFAULT '0.00',
  `after` decimal(11,2) DEFAULT '0.00',
  `type` varchar(255) DEFAULT NULL,
  `ordernum` varchar(255) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_business_deposit_order` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `money` decimal(11,2) DEFAULT '0.00',
  `ordernum` varchar(100) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  `payorderid` int(11) DEFAULT NULL,
  `paytypeid` int(11) DEFAULT NULL,
  `paytype` varchar(100) DEFAULT NULL,
  `paynum` varchar(255) DEFAULT NULL,
  `paytime` int(11) DEFAULT NULL,
  `platform` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `bid` (`mid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
}

if(getcustom('cashier_money_dec')){
    if(!pdo_fieldexists2("ddwx_cashier_order", "dec_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashier_order` ADD COLUMN `dec_money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '余额抵扣金额' ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashier_order` ADD COLUMN `money_dec_rate` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '余额抵扣比例' ;");
    }
}
if(getcustom('lot_cerberuse')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_cerberuse_order` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT NULL,
      `mid` int(11) DEFAULT NULL,
      `proid` int(11) DEFAULT NULL COMMENT '对应的设备id',
      `device_id` varchar(255) DEFAULT NULL,
      `title` varchar(255) DEFAULT NULL,
      `ordernum` varchar(255) DEFAULT NULL COMMENT '订单号',
      `leveldek_money` decimal(10,2) DEFAULT NULL,
      `price` decimal(10,2) DEFAULT NULL COMMENT '单价/小时',
      `product_price` decimal(10,2) DEFAULT '0.00',
      `totalprice` decimal(10,2) DEFAULT NULL,
      `couponrid` int(11) DEFAULT NULL,
      `starttime` int(11) DEFAULT NULL,
      `endtime` int(11) DEFAULT NULL,
      `time_length` decimal(10,2) DEFAULT NULL COMMENT '时长，小时',
      `paytime` int(11) DEFAULT NULL,
      `paytype` varchar(50) DEFAULT NULL,
      `paytypeid` int(11) DEFAULT NULL,
      `payorderid` int(11) DEFAULT NULL,
      `platform` varchar(255) DEFAULT 'wx',
      `linkman` varchar(255) DEFAULT NULL,
      `tel` varchar(50) DEFAULT NULL,
      `coupon_money` decimal(10,2) DEFAULT NULL,
      `qrcode_url` varchar(255) DEFAULT NULL,
      `refund_status` tinyint(1) DEFAULT '0',
      `status` tinyint(1) DEFAULT '0' COMMENT '0:未支付 1：支付 2:进行中 3完成',
      `createtime` int(11) DEFAULT '0',
      `usetime` int(11) DEFAULT NULL COMMENT '使用时间',
      `refund_time` int(11) DEFAULT NULL,
      `refund_reason` varchar(255) DEFAULT NULL,
      `refund_money` decimal(10,2) DEFAULT NULL,
      `refund_checkremark` varchar(255) DEFAULT NULL,
      `remark` varchar(255) DEFAULT NULL,
      `is_notice` tinyint(1) DEFAULT '0',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_cerberuse` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT NULL,
      `title` varchar(255) DEFAULT NULL,
      `pic` varchar(255) DEFAULT NULL,
      `pics` text,
      `sellpoint` varchar(255) DEFAULT NULL,
      `content` text,
      `rqtype` tinyint(1) DEFAULT '1',
      `starttime` varchar(30) DEFAULT NULL,
      `endtime` varchar(30) DEFAULT NULL,
      `imei` varchar(30) DEFAULT NULL COMMENT '4G插座imei',
      `server_ip` varchar(255) DEFAULT NULL COMMENT '服务器地址',
      `sn` varchar(50) DEFAULT NULL COMMENT '设备号',
      `device_id` varchar(50) DEFAULT NULL COMMENT '设备ID',
      `price` decimal(10,2) DEFAULT '0.00' COMMENT '价格 每小时',
      `view_id` varchar(255) DEFAULT NULL COMMENT '区域ID',
      `u_key` varchar(255) DEFAULT NULL COMMENT '授权Key',
      `timeout` int(11) DEFAULT NULL COMMENT '链接超时 毫秒',
      `relay1_time` int(11) DEFAULT NULL COMMENT '继电器开门时间',
      `relay1_type` tinyint(1) DEFAULT NULL COMMENT '继电器工作模式0为常规，1为常开，2为常闭',
      `net_mode` tinyint(1) DEFAULT NULL COMMENT '联网方式0:以太网 1:WIIF 2:自适应 3:4G',
      `dhcp` tinyint(1) DEFAULT NULL COMMENT '1:开启 0:关闭',
      `ip` varchar(30) DEFAULT NULL COMMENT '以太网IP设置',
      `mask` varchar(30) DEFAULT NULL COMMENT '以太网子网掩码',
      `gateway` varchar(30) DEFAULT NULL COMMENT '以太网网关',
      `dns` varchar(30) DEFAULT NULL COMMENT '以太网Dns',
      `wifi_ssid` varchar(255) DEFAULT NULL COMMENT 'WIFI名',
      `wifi_password` varchar(255) DEFAULT NULL COMMENT 'WIFI密码',
      `wifi_dhcp` tinyint(1) DEFAULT NULL COMMENT 'wifi的DHCP设置',
      `qrcode` varchar(255) DEFAULT NULL COMMENT '二维码',
      `sales` int(11) DEFAULT '0',
      `status` tinyint(1) DEFAULT '1',
      `createtime` int(11) DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_cerberuse_set` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `remind_minute` int(11) DEFAULT '10' COMMENT '分钟',
      `autoclose` int(11) DEFAULT '10' COMMENT '分钟',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    if(!pdo_fieldexists2("ddwx_wx_tmplset","tmpl_use_expire")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_wx_tmplset` ADD COLUMN  `tmpl_use_expire` varchar(255) DEFAULT NULL COMMENT '服务过期';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set_sms","tmpl_use_expire_st")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` ADD COLUMN  `tmpl_use_expire_st` tinyint(1) DEFAULT NULL COMMENT '消费时间到期';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` ADD COLUMN  `tmpl_use_expire` varchar(255) DEFAULT NULL COMMENT '消费时间到期';");
    }
}

if(getcustom('product_weight')){
    if(!pdo_fieldexists2("ddwx_shop_order","customer_id")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` 
        ADD COLUMN `customer_id`  int(11) NULL DEFAULT 0 COMMENT '客户id',
        ADD COLUMN `product_type` tinyint(1) DEFAULT '0';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_sh_weight_remark` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(255) DEFAULT '',
          `createtime` int(11) DEFAULT NULL,
          `sort` int(4) DEFAULT '0',
          `aid` int(11) DEFAULT '0',
          `bid` int(11) DEFAULT '0',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_customer_price` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `proid` int(11) DEFAULT '0',
          `ggid` int(11) DEFAULT '0',
          `aid` int(11) DEFAULT '0',
          `createtime` int(11) DEFAULT '0',
          `price` decimal(2,0) DEFAULT '0',
          `weight` decimal(2,0) DEFAULT '0',
          `customer_id` int(11) DEFAULT '0',
          `bid` int(11) DEFAULT '0',
          PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    if(!pdo_fieldexists2("ddwx_shop_order_goods","real_total_weight")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `real_total_weight` decimal(10, 2) NOT NULL DEFAULT 0.00;");
    }
    if(!pdo_fieldexists2("ddwx_customer_price","updatetime")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_customer_price`
            MODIFY COLUMN `price`  decimal(10,2) NULL DEFAULT 0,
            MODIFY COLUMN `weight`  decimal(10,2) NULL DEFAULT 0,
            ADD COLUMN `updatetime`  int(11) NULL;
        ");
    }
    if(!pdo_fieldexists2("ddwx_shop_order_goods","real_sell_price")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `real_sell_price` decimal(10, 2) NOT NULL DEFAULT 0.00;");
    }
    if(!pdo_fieldexists2("ddwx_shop_order","sync_bid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `sync_bid` int(11) NULL DEFAULT '-1';");
    }

    if(!pdo_fieldexists2("ddwx_shop_order_goods","remark_ext")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `remark_ext` varchar(255) NULL DEFAULT '';");
    }

    if(!pdo_fieldexists2("ddwx_sh_weight_remark","ext_bids")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_sh_weight_remark` 
        ADD COLUMN `ext_bids` varchar(255) NULL DEFAULT '' COMMENT '可用商户',
        ADD COLUMN `updatetime` int(11) NULL DEFAULT NULL;");
    }
}
if(getcustom('customer')) {
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_sh_customer` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(255) DEFAULT '',
          `tel` varchar(32) DEFAULT '',
          `address` varchar(255) DEFAULT '',
          `pid` int(11) DEFAULT '0',
          `createtime` int(11) DEFAULT NULL,
          `sort` int(4) DEFAULT '0',
          `aid` int(11) DEFAULT '0',
          `bid` int(11) DEFAULT '0',
          `number` varchar(32) DEFAULT '',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    if(!pdo_fieldexists2("ddwx_sh_customer","ext_bids")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_sh_customer` 
        ADD COLUMN `ext_bids` varchar(255) NULL DEFAULT '' COMMENT '可用商户',
        ADD COLUMN `mid` int(11) NULL DEFAULT 0,
        ADD COLUMN `updatetime` int(11) NULL DEFAULT NULL;");
    }
    if(!pdo_fieldexists2("ddwx_sh_customer","remark")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_sh_customer` 
        ADD COLUMN `remark` varchar(255) NULL DEFAULT '' COMMENT '商户备注',
        ADD COLUMN `bill` int(11) NULL DEFAULT 0 COMMENT '账单期';");
    }
}
if(getcustom('customer_peisonguser')){
    if(!pdo_fieldexists2("ddwx_sh_customer","peisong_uid")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_sh_customer` 
        ADD COLUMN `peisong_uid`  int(11) NULL DEFAULT 0 COMMENT '配送员id';");
    }
}
if(getcustom('loc_business')){
    if(!pdo_fieldexists2("ddwx_admin_set","loc_business_show_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `loc_business_show_type` tinyint(1) DEFAULT '0' COMMENT '商户门店模式--显示商户 0默认1推荐人';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","loc_business_show_address")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `loc_business_show_address` tinyint(1) DEFAULT '1' COMMENT '商户门店模式--默认商家地址和距离0隐藏1显示';");
    }
}
if(getcustom('choujiang_time')){
    \think\facade\Db::execute("ALTER TABLE `ddwx_dscj` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_dscj` MODIFY COLUMN `content`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
}
if(getcustom('yx_hbtk')) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_hbtk_activity` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_hbtk_activity` MODIFY COLUMN `guize`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '规则';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_hbtk_activity` MODIFY COLUMN `content`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '活动内容';");
}
if(getcustom('yueke')) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_yueke_product` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_yueke_product` MODIFY COLUMN `detail`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
}
if(getcustom('up_level_agree')) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` MODIFY COLUMN `agree_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '升级协议内容';");
}
if(getcustom('product_comment')){
    if(!pdo_fieldexists2("ddwx_shop_sysset","product_comment")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `product_comment`  tinyint(1) DEFAULT 0 COMMENT '商品评价',ADD COLUMN `product_comment_check`  tinyint(1) DEFAULT 1 COMMENT '评价是否审核';");
    }
}

if(getcustom('product_cost_show')){
    if(!pdo_fieldexists2("ddwx_shop_sysset","hide_cost")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset`
        ADD COLUMN `hide_cost`  tinyint(1) NULL DEFAULT 1 COMMENT '是否隐藏成本',
        ADD COLUMN `cost_name`  varchar(128) NULL DEFAULT '' AFTER `hide_cost`,
        ADD COLUMN `cost_color` varchar(32) NULL DEFAULT '' AFTER `cost_name`;");
    }
}
if(getcustom('product_sellprice_show')){
    if(!pdo_fieldexists2("ddwx_shop_sysset","hide_sellprice")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset`
        ADD COLUMN `hide_sellprice`  tinyint(1) NULL DEFAULT 0 COMMENT '是否隐藏销售价',
        ADD COLUMN `sellprice_name`  varchar(128) NULL DEFAULT '' AFTER `hide_sellprice`,
        ADD COLUMN `sellprice_color` varchar(32) NULL DEFAULT '' AFTER `sellprice_name`;");
    }
}

if(getcustom('product_detail_special')){
    if(!pdo_fieldexists2("ddwx_shop_sysset","show_product_name")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset`
        ADD COLUMN `show_product_name`  tinyint(1) NULL DEFAULT 1 COMMENT '是否显示商品名称行',
        ADD COLUMN `show_guige`  tinyint(1) NULL DEFAULT 1 COMMENT '是否显示规格行',
        ADD COLUMN `show_option_group`  tinyint(1) NULL DEFAULT 1 COMMENT '是否显示购买操作行';");
    }
    if(!pdo_fieldexists2("ddwx_shop_sysset","show_header_pic")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset`
        ADD COLUMN `show_header_pic`  tinyint(1) NULL DEFAULT 1 COMMENT '是否显示商品图片';");
    }
}

if(getcustom('product_xunjia_btn')){
    if(!pdo_fieldexists2("ddwx_shop_product","show_xunjia_btn")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product`
        ADD COLUMN `show_xunjia_btn`  tinyint(1) NULL DEFAULT 1 COMMENT '是否显示联系ta',
        ADD COLUMN `xunjia_btn_url`  varchar(255) NULL DEFAULT '',
        ADD COLUMN `xunjia_btn_bgcolor`  varchar(32) NULL DEFAULT '',
        ADD COLUMN `xunjia_btn_color`  varchar(32) NULL DEFAULT '';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","xunjia_text")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `xunjia_text` varchar(50) NOT NULL DEFAULT '' COMMENT '询价提示';");
    }
}
if(getcustom('score_to_money_auto')){
    if(!pdo_fieldexists2("ddwx_admin_set","score_to_money_auto")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
        ADD COLUMN `score_to_money_auto`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '积分每日自动转余额 0关闭 1开启',
        ADD COLUMN `score_to_money_auto_day`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '每日积分自动转余额比例',
        ADD COLUMN `score_to_money_auto_percent`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '每日积分自动转余额换算比例';");
    }

    if(!pdo_fieldexists3('ddwx_score_tomoney_log')) {
        \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_score_tomoney_log` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `w_day` varchar(255) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    if(!pdo_fieldexists2("ddwx_member","score_to_money_auto")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `score_to_money_auto`  tinyint(1) NULL DEFAULT 1 COMMENT '积分自动转余额 0关闭 1开启';");
    }
    if(!pdo_fieldexists2("ddwx_member","score_to_money_auto_day")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `score_to_money_auto_day`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '积分每日自动转余额比例';");
    }
}
if(getcustom('commission_xiaofei')){
    if(!pdo_fieldexists2("ddwx_admin_set","xiaofei_percent")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
        ADD COLUMN `xiaofei_percent`  tinyint(4) NOT NULL DEFAULT 0 COMMENT '冻结佣金比例',
        ADD COLUMN `xiaofei_levelids`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '-1' COMMENT '冻结佣金参与等级';");
    }
    if(!pdo_fieldexists2("ddwx_member","xiaofei_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member`
        ADD COLUMN `xiaofei_money`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '冻结佣金钱包余额';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","product_xiaofeipay")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product`
        ADD COLUMN `product_xiaofeipay`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '冻结佣金钱包支付 0不支持 1支持';");
    }
}
if(getcustom('teamfenhong_shouyi')){
    if(!pdo_fieldexists2("ddwx_member_commissionlog","fhtype")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_commissionlog`
        ADD COLUMN `fhtype`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '分红类型';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","team_shouyi_lv")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level`
        ADD COLUMN `team_shouyi_lv`  int(11) NOT NULL DEFAULT 0 COMMENT '团队收益层级 0不限制',
        ADD COLUMN `team_shouyi`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '团队收益比例',
        ADD COLUMN `team_shouyi_ordermoney`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '团队收益订单消费限制',
        ADD COLUMN `team_shouyi_min`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '团队收益最低金额';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_xiaofei_money_log` (
        `id`  int(11) NOT NULL AUTO_INCREMENT ,
        `aid`  int(11) NULL DEFAULT NULL ,
        `mid`  int(11) NULL DEFAULT NULL ,
        `frommid`  int(11) NULL DEFAULT NULL ,
        `commission`  decimal(11,2) NULL DEFAULT NULL ,
        `after`  decimal(11,2) NULL DEFAULT 0.00 ,
        `createtime`  int(11) NULL DEFAULT NULL ,
        `remark`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
        PRIMARY KEY (`id`),
        INDEX `aid` (`aid`) USING BTREE ,
        INDEX `mid` (`mid`) USING BTREE 
        )
        ENGINE=InnoDB
        DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
        ROW_FORMAT=Dynamic;");
}
if(getcustom('yx_invite_cashback')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_invite_cashback` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NULL DEFAULT NULL,
		`bid` int(11) NULL DEFAULT 0,
		`name` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '名称',
		`gettj` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '参与人群',
		`fwtype` tinyint(1) NULL DEFAULT 0,
		`categoryids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`productids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`minprice` decimal(10, 2) NULL DEFAULT 0.00,
		`starttime` int(11) NULL DEFAULT NULL,
		`endtime` int(11) NULL DEFAULT NULL,
		`sort` int(11) NULL DEFAULT 0,
		`status` int(1) NULL DEFAULT 1,
		`createtime` int(11) NULL DEFAULT NULL,
		`return_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '返还类型 0:立即返回 1：自定义',
		`return_day` int(11) NOT NULL DEFAULT 0 COMMENT '返回天数',
		`collageids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '多人拼团',
		`invite_cashbak_data` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '邀请返现数据',
		`isagain` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否复购 0：关闭 1：开启',
		`iscycle` tinyint(1)  NOT NULL DEFAULT 0 COMMENT '循环发放 0：关闭 1：开启',
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `aid`(`aid`) USING BTREE,
		INDEX `bid`(`bid`) USING BTREE,
		INDEX `status`(`status`) USING BTREE,
		INDEX `starttime`(`starttime`) USING BTREE,
		INDEX `endtime`(`endtime`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_invite_cashback_log` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NOT NULL DEFAULT 0,
		`mid` int(11) NOT NULL DEFAULT 0,
		`mid_order_gid` int(11) NOT NULL DEFAULT 0,
		`proid` int(11) NOT NULL DEFAULT 0 COMMENT '商品id',
		`num` int(11) NOT NULL DEFAULT 0 COMMENT '购买数量',
		`back_price` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '返回计算金额',
		`order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单id(暂只商城)',
		`order_mid` int(11) NOT NULL DEFAULT 0 COMMENT '订单用户id',
		`cashback_id` int(11) NOT NULL DEFAULT 0 COMMENT '购物返现id',
		`money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '固定余额返现',
		`money2` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '百分比余额返现',
		`allmoney` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '总共返余额',
		`score` int(11) NOT NULL DEFAULT 0 COMMENT '固定积分返现',
		`score2` int(11) NOT NULL DEFAULT 0 COMMENT '百分比积分返现',
		`allscore` int(11) NOT NULL DEFAULT 0 COMMENT '总共返积分',
		`commission` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '固定佣金返现',
		`commission2` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '百分比佣金返现',
		`allcommission` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '总共返佣金',
		`order_gid` int NOT NULL DEFAULT 0 COMMENT '订单商品表id',
		`reason` varchar(50) NOT NULL DEFAULT '' COMMENT '原因',
		`status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0：待发放 1：已发放  -1：已取消',
		`create_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
		`update_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
		`cancel_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
		`categoryids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
		`productids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `order_id`(`order_id`) USING BTREE,
		INDEX `order_mid`(`order_mid`) USING BTREE,
		INDEX `cashback_id`(`cashback_id`) USING BTREE,
		INDEX `mid`(`mid`) USING BTREE,
		INDEX `proid`(`proid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    if(!pdo_fieldexists2("ddwx_invite_cashback","isagain")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_invite_cashback` ADD COLUMN `isagain` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否复购 0：关闭 1：开启';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_invite_cashback` ADD COLUMN `iscycle` tinyint(1)  NOT NULL DEFAULT 0 COMMENT '循环发放 0：关闭 1：开启';");

        \think\facade\Db::execute("ALTER TABLE `ddwx_member_invite_cashback_log` ADD COLUMN `order_gid` int NOT NULL DEFAULT 0 COMMENT '订单商品表id';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_invite_cashback_log` ADD COLUMN `update_time` int(11) UNSIGNED NOT NULL DEFAULT 0 ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_invite_cashback_log` ADD COLUMN `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0：待发放 1：已发放  -1：已取消';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_invite_cashback_log` ADD COLUMN `reason` varchar(50) NOT NULL DEFAULT '' COMMENT '原因';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_invite_cashback_log` ADD INDEX `order_mid`(`order_mid`);");
    }
    if(!pdo_fieldexists2("ddwx_member_invite_cashback_log","cancel_time")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_invite_cashback_log` ADD COLUMN `cancel_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '取消时间';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_invite_cashback_log` ADD COLUMN `mid_order_gid` int NOT NULL DEFAULT 0 COMMENT '上级订单商品id';");
    }
    if(!pdo_fieldexists2("ddwx_member_invite_cashback_log","categoryids")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_invite_cashback_log` ADD COLUMN `categoryids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_invite_cashback_log` ADD COLUMN `productids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';");
    }
    if(!pdo_fieldexists2("ddwx_invite_cashback","cyclenum")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_invite_cashback` ADD COLUMN `cyclenum` int(11) NOT NULL DEFAULT 0 COMMENT '循环次数';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_invite_cashback` ADD COLUMN `cyclemoney` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '固定余额';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_invite_cashback` ADD COLUMN `cyclescore` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '固定积分';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_invite_cashback` ADD COLUMN `cyclecommission` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '固定佣金';");
    }
    if(!pdo_fieldexists2("ddwx_invite_cashback","cyclemoney2")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_invite_cashback` ADD COLUMN `cyclemoney2` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '百分比余额';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_invite_cashback` ADD COLUMN `cyclescore2` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '百分比积分';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_invite_cashback` ADD COLUMN `cyclecommission2` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '百分比佣金';");
    }
    if(!pdo_fieldexists2("ddwx_invite_cashback","needbuy")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_invite_cashback` ADD COLUMN `needbuy` tinyint(1) NOT NULL DEFAULT 1 COMMENT '需上级购买商品0：关闭 1：开启';");
    }

    if(!pdo_fieldexists2("ddwx_invite_cashback","tiptype")){
    	\think\facade\Db::execute("ALTER TABLE `ddwx_invite_cashback` 
			ADD COLUMN `tiptype` tinyint(1) NOT NULL DEFAULT 0 COMMENT '商品详情页提示文字 0：默认 1：自定义',
			ADD COLUMN `tiptext` varchar(255) NULL COMMENT '商品详情页提示文字';");
    }
}
if(getcustom('yuyue_product_lvprice')){
    if(!pdo_fieldexists2("ddwx_yuyue_product","lvprice")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD COLUMN `lvprice` tinyint(1) NULL DEFAULT 0 COMMENT '是否开启会员价 不同会员等级设置不同价格';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD COLUMN `lvprice_data` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD COLUMN `cost_price` decimal(11, 2) NULL DEFAULT 0.00;");

        \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_guige` ADD COLUMN `cost_price` decimal(11, 2) NULL DEFAULT 0.00 ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` ADD COLUMN `cost_price` decimal(11, 2) NULL DEFAULT 0.00;");
    }
}

if(getcustom('wifiprint_bind_user')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_wifiprint_user` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `uid` int(11) DEFAULT '0',
        `print_id` int(11) DEFAULT '0',
        `remark` varchar(255) DEFAULT '',
        `aid` int(11) DEFAULT '0',
        `bid` int(11) DEFAULT '0',
        `createtime` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}

if(getcustom('design_remark')){
    if(!pdo_fieldexists2("ddwx_designerpage","remark")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_designerpage` ADD COLUMN `remark` varchar (255) NULL DEFAULT '' COMMENT '页面备注';");
    }
}

if(getcustom('product_unit')) {
    if(!pdo_fieldexists2("ddwx_shop_product","product_unit")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `product_unit` varchar(60) NULL COMMENT '单位';");
    }
}
if(getcustom('product_keyword')) {
    if(!pdo_fieldexists2("ddwx_shop_product","keyword")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `keyword` varchar(255) NULL COMMENT '关键字';");
    }
}

if(getcustom('collage_givescore_time')) {
    if(!pdo_fieldexists2("ddwx_collage_product","givescore")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_collage_product` ADD COLUMN `givescore` int(11) NOT NULL DEFAULT 0 COMMENT '赠送积分';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_collage_product` ADD COLUMN `givescore_time` tinyint(1) NOT NULL DEFAULT 0 COMMENT '积分赠送时间 0: 确认收货后 1:付款后';");

        \think\facade\Db::execute("ALTER TABLE `ddwx_collage_order` ADD COLUMN `givescore1` int(11) NOT NULL DEFAULT 0 COMMENT '确认收货后赠送';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_collage_order` ADD COLUMN `givescore2` int(11) NOT NULL DEFAULT 0 COMMENT '付款后赠送';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_collage_guige` ADD COLUMN `givescore` int(11) NOT NULL DEFAULT 0 COMMENT '赠送积分';");
    }
}

if(getcustom('collage_teampay')) {
    if(!pdo_fieldexists2("ddwx_collage_product","teampay")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_collage_product` ADD COLUMN `teampay` tinyint(1) NOT NULL DEFAULT 0 COMMENT '团长余额支付0：跟随系统 1：开启 -1：关闭' ");
        \think\facade\Db::execute("ALTER TABLE `ddwx_collage_sysset` ADD COLUMN `teampay` tinyint(1) NOT NULL DEFAULT 1 COMMENT '团长余额支付 0：关闭 1：开启';");
    }
}

if(getcustom('product_bonus_pool')){
    if(!pdo_fieldexists2("ddwx_shop_product","bonus_pool_ratio")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product`  ADD COLUMN `bonus_pool_ratio` decimal(12,2) DEFAULT NULL COMMENT '奖金池 比例'; ");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product`  ADD COLUMN `bonus_pool_num` int(11) DEFAULT NULL COMMENT '奖金池分数'; ");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product`  ADD COLUMN `bonus_pool_releasetj` varchar(255) DEFAULT '-1' COMMENT '奖金池，可释放的等级';");
    }
    if(!pdo_fieldexists2("ddwx_member","bonus_pool_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `bonus_pool_money` decimal(11,2) DEFAULT '0.00' COMMENT '奖金池金额'; ");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `bonus_pool_tjnum` int(11) DEFAULT '0';");
    }
    if(!pdo_fieldexists2("ddwx_shop_sysset","bonus_pool_money_max")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `bonus_pool_money_max` decimal(11,2) DEFAULT NULL COMMENT '奖金池，用户上限';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `bonus_pool_tuijian_num` int(11) DEFAULT '0' COMMENT '奖金池 推荐人数';");
    }
    if(!pdo_fieldexists2("ddwx_shop_sysset","bonus_pool_already")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `bonus_pool_already` int(11) DEFAULT '0' COMMENT '已释放几天';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `bonus_pool_cx_days` int(11) DEFAULT '1' COMMENT '持续n天释放';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_bonus_pool_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `frommid` int(11) DEFAULT NULL,
  `commission` decimal(11,2) DEFAULT '0.00',
  `after` decimal(11,2) DEFAULT '0.00',
  `createtime` int(11) DEFAULT NULL,
  `remark` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_bonus_pool_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `orderid` int(11) DEFAULT NULL,
  `frommid` int(11) DEFAULT NULL,
  `ogid` int(11) DEFAULT NULL,
  `bpid` int(11) DEFAULT NULL COMMENT '奖金池ID',
  `type` varchar(100) DEFAULT 'shop' COMMENT 'shop 商城',
  `commission` decimal(11,2) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `endtime` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_bonus_pool_withdrawlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `money` decimal(11,2) DEFAULT NULL,
  `txmoney` decimal(11,2) DEFAULT NULL,
  `aliaccount` varchar(255) DEFAULT NULL,
  `aliaccountname` varchar(255) DEFAULT NULL COMMENT '支付宝姓名',
  `ordernum` varchar(255) DEFAULT NULL,
  `paytype` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  `createtime` int(11) DEFAULT NULL,
  `bankname` varchar(255) DEFAULT NULL,
  `bankcarduser` varchar(255) DEFAULT NULL,
  `bankcardnum` varchar(255) DEFAULT NULL,
  `paytime` int(11) DEFAULT NULL,
  `paynum` varchar(255) DEFAULT NULL,
  `platform` varchar(50) DEFAULT 'wx' COMMENT 'wx小程序 m公众号网页',
  `reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_bonus_pool` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `money` decimal(11,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0：未释放 1：释放',
  `ogid` int(11) DEFAULT '0' COMMENT '产生贡献的订单商品id',
  `mid` int(11) DEFAULT '0' COMMENT '释放用户',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='奖金池';");
    if(!pdo_fieldexists2("ddwx_shop_sysset","bonus_pool_noreleasetj")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `bonus_pool_noreleasetj` varchar(255) DEFAULT '0' COMMENT '奖金池不释放等级';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","bonus_pool_isrelease")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `bonus_pool_isrelease` tinyint(1) DEFAULT '1' COMMENT '奖金池 是否释放';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","bonus_pool_money_max")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `bonus_pool_money_max` int(11) DEFAULT '0' COMMENT '奖金池上限';");
    }
    if(!pdo_fieldexists2("ddwx_member_bonus_pool_record","proid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_bonus_pool_record` ADD COLUMN `proid` int(11) DEFAULT '0' COMMENT '产品ID';");
    }
    if(!pdo_fieldexists2("ddwx_bonus_pool","endtime")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_bonus_pool` ADD COLUMN `endtime` int(11) DEFAULT '0';");
    }
    if(!pdo_fieldexists2("ddwx_member","bonus_pool_max_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `bonus_pool_max_money` decimal(11,2) DEFAULT '0.00';");
    }

}

if(getcustom('article_news_pc')){
    if(!pdo_fieldexists2("ddwx_article","keywords")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_article` ADD COLUMN `keywords` varchar(255) DEFAULT NULL; ");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_web_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `keywords` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `pic` varchar(255) DEFAULT NULL,
  `content` longtext,
  `sort` int(11) DEFAULT '0',
  `readcount` int(11) DEFAULT '0',
  `createtime` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT '1',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
}

if(getcustom('choujiang_time_artificial')){
    if(!pdo_fieldexists2("ddwx_dscj","hj_content")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_dscj` 
    ADD COLUMN `hj_content` longtext COMMENT '内定获奖配置',
    ADD COLUMN `hj_num` int(11) DEFAULT '0' COMMENT '内定获奖人数'; ");
    }
}
if(getcustom('product_stock_warning')){
    if(!pdo_fieldexists2("ddwx_shop_guige","stock_warning")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_guige` ADD COLUMN `stock_warning` int(11) COMMENT '预警提醒值'");
    }
}

if(getcustom('form_data')){
    if(!pdo_fieldexists2("ddwx_form","list_pic")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `list_pic` int(11) NOT NULL DEFAULT 0 COMMENT '列表图片key'; ");
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `list_title` int(11) NOT NULL DEFAULT 0 COMMENT '列表标题key'; ");
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `list_address` int(11) NOT NULL DEFAULT 0 COMMENT '列表地址key'; ");
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `list_tel` int(11) NOT NULL DEFAULT 0 COMMENT '列表电话key'; ");

        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `detail_pic` int(11) NOT NULL DEFAULT 0 COMMENT '详情图片key'; ");
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `detail_title` int(11) NOT NULL DEFAULT 0 COMMENT '详情标题key'; ");
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `detail_word` int(11) NOT NULL DEFAULT 0 COMMENT '详情文字一key'; ");
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `detail_word2` int(11) NOT NULL DEFAULT 0 COMMENT '详情文字二key'; ");

        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `isopen` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否公开 0：否 1：是'; ");
    }

    if(!pdo_fieldexists2("ddwx_form_order","sort")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD COLUMN `sort` int(11) NOT NULL DEFAULT 0 ;");
    }
}
if(getcustom('workorder')){
    if(!pdo_fieldexists2("ddwx_workorder_order", "cid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_order` ADD COLUMN `cid` int(11) NOT NULL DEFAULT 0 COMMENT '分类id' ;");
    }
    if(!pdo_fieldexists2("ddwx_workorder_category", "isuserend")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_category` ADD COLUMN `isuserend` int(11) NOT NULL DEFAULT 0 COMMENT '用户结束按钮' ;");
    }
    if(!pdo_fieldexists2("ddwx_workorder_category", "cid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_category` ADD COLUMN `cid` int(11) NOT NULL DEFAULT 0 COMMENT '类型id' ;");
    }
}


if(getcustom('plug_yuebao')){
    if(pdo_fieldexists2("ddwx_member", "yuebao_money")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` MODIFY COLUMN `yuebao_money` decimal(11, 3) NULL DEFAULT 0.00 COMMENT '余额宝收益';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_yuebao_moneylog` MODIFY COLUMN `money` decimal(11, 3) NULL DEFAULT 0.00;");
    }
}
if(getcustom('form_edit')){
    if(!pdo_fieldexists2("ddwx_form","edit_status")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `edit_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否能编辑：0否 1是';");
    }
}

if(getcustom('yx_cashback_collage_moneyreturn')){
    if(!pdo_fieldexists2("ddwx_cashback","team_moneyreturn")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashback` ADD COLUMN `team_moneyreturn` tinyint(1) NOT NULL DEFAULT 1 COMMENT '拼团余额返还 0：关闭 1：全都返还 2：仅团长返还';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashback` ADD COLUMN `alone_moneyreturn` tinyint(1) NOT NULL DEFAULT 1 COMMENT '单独购买余额返还 0：关闭 1：开启';");
    }
}
if(getcustom('workorder')){
    if (!pdo_fieldexists3("ddwx_workorder_newcate")) {
        \think\facade\Db::execute("CREATE TABLE `ddwx_workorder_newcate` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) DEFAULT NULL,
		  `bid` int(11) DEFAULT '0',
		  `name` varchar(255) DEFAULT NULL,
		  `content` longtext,
		  `sort` int(11) DEFAULT '0',
		  `pid` int(11) DEFAULT '0',
		  `pic` varchar(255) DEFAULT '',
		  `status` tinyint(1) DEFAULT '1',
		  `createtime` int(11) DEFAULT NULL,
		  PRIMARY KEY (`id`) USING BTREE,
		  KEY `aid` (`aid`) USING BTREE,
		  KEY `bid` (`bid`) USING BTREE
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }
}
if(getcustom('product_stock_record')){
    if (!pdo_fieldexists3("ddwx_shop_product_stockrecord")) {
        \think\facade\Db::execute("CREATE TABLE `ddwx_shop_product_stockrecord` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) DEFAULT NULL,
		  `bid` int(11) DEFAULT NULL,
		  `proid` int(11) DEFAULT NULL,
		  `ggid` int(11) DEFAULT NULL,
		  `stock` int(11) DEFAULT '0' COMMENT '库存',
		  `afterstock` int(11) DEFAULT '0' COMMENT '改变后的库存',
		  `createtime` int(11) DEFAULT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    }
}

if(getcustom('print_label_barcode')){
    if(!pdo_fieldexists2("ddwx_wifiprint_set","print_barcode")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_wifiprint_set` ADD COLUMN `print_barcode` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否打印条形码 0：否 1：是';");
    }
}
if(getcustom('commission_parent_pj_stop')){
    if(!pdo_fieldexists2("ddwx_member_level","commission_parent_pj_status")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `commission_parent_pj_status`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '平级奖是否开启 0关闭 1开启';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","commission_parent_pj_lv")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `commission_parent_pj_lv`  int(11) NOT NULL DEFAULT 0 COMMENT '平级奖层级限制';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","commission_parent_pj_order")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `commission_parent_pj_order`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '平级奖根据订单金额计算比例';");
    }
}
if(getcustom('member_overdraft_money')){
    if(!pdo_fieldexists2("ddwx_admin","overdraft_init")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `overdraft_init`  int(11) NOT NULL DEFAULT 0 COMMENT '信用额度初始值';");
    }
    if(!pdo_fieldexists2("ddwx_member","overdraft_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `overdraft_money`  float (11,2) NOT NULL DEFAULT '0.00' COMMENT '信用额度';");
    }
    if(!pdo_fieldexists2("ddwx_member","limit_overdraft_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `limit_overdraft_money`  float (11,2) NOT NULL DEFAULT '0.00' COMMENT '信用额度限制';");
    }
    if(!pdo_fieldexists2("ddwx_member","open_overdraft_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `open_overdraft_money`  tinyint(1) NULL DEFAULT 0 COMMENT '开启无限信用额度1开启0关闭';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","overdraft_moneypay")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` 
        ADD COLUMN `overdraft_moneypay`  tinyint(1) NULL DEFAULT 0 COMMENT '信用额度支付',
        ADD COLUMN `overdraft_money_limit`  float (11,2) NOT NULL DEFAULT '0.00' COMMENT '最大信用额度';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_overdraft_moneylog` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
          `aid` int(11) DEFAULT NULL,
          `mid` int(11) DEFAULT NULL,
          `money` decimal(11,2) DEFAULT '0.00',
          `after` decimal(11,2) DEFAULT '0.00',
          `createtime` int(11) DEFAULT NULL,
          `remark` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
          `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '类型0、frozen_money  2、money2 3、money3 4、money4 5、money5',
          PRIMARY KEY (`id`) USING BTREE,
          KEY `aid` (`aid`) USING BTREE,
          KEY `mid` (`mid`) USING BTREE
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_overdraft_recharge_order` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
          `aid` int(11) DEFAULT NULL,
          `mid` int(11) DEFAULT NULL,
          `totalprice` decimal(11,2) DEFAULT '0.00',
          `ordernum` varchar(100) DEFAULT NULL,
          `createtime` int(11) DEFAULT NULL,
          `status` tinyint(1) DEFAULT '0',
          `payorderid` int(11) DEFAULT NULL,
          `paytypeid` int(11) DEFAULT NULL,
          `paytype` varchar(100) DEFAULT NULL,
          `paynum` varchar(255) DEFAULT NULL,
          `paytime` int(11) DEFAULT NULL,
          `platform` varchar(100) DEFAULT NULL,
          PRIMARY KEY (`id`) USING BTREE,
          KEY `aid` (`aid`) USING BTREE,
          KEY `mid` (`mid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}

if(getcustom('member_product_price')){

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS  `ddwx_member_product` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `mid` int(11) DEFAULT NULL,
	  `proid` int(11) DEFAULT NULL COMMENT '产品ID',
	  `proname` varchar(255) DEFAULT NULL,
	  `ggid` int(11) DEFAULT NULL COMMENT '规格ID',
	  `ggname` varchar(255) DEFAULT NULL,
	  `pic` varchar(255) DEFAULT NULL,
	  `market_price` decimal(11,2) DEFAULT '0.00',
	  `cost_price` decimal(11,2) DEFAULT NULL,
	  `sell_price` decimal(11,2) DEFAULT NULL COMMENT '专享价',
	  `givescore` int(11) DEFAULT '0' COMMENT '赠送积分',
	  `createtime` int(11) DEFAULT '0',
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_product_buylog` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `mid` int(11) DEFAULT '0',
	  `ordernum` varchar(255) DEFAULT NULL,
	  `type` varchar(100) DEFAULT NULL COMMENT '类型 cashier 收银台 shop商城',
	  `proid` int(11) DEFAULT '0',
	  `ggid` int(11) DEFAULT '0',
	  `orderid` int(11) DEFAULT '0',
	  `sell_price` decimal(10,2) DEFAULT '0.00',
	  `num` int(11) DEFAULT '0',
	  `createtime` int(11) DEFAULT '0',
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='买单记录';");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS  `ddwx_member_product_pricelog` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `proid` int(11) DEFAULT NULL COMMENT 'shop_member_product的id',
	  `before` decimal(11,2) DEFAULT '0.00',
	  `after` decimal(11,2) DEFAULT NULL,
	  `createtime` int(11) DEFAULT '0',
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='调价记录';");
}

if(getcustom('workorder')){
    if(!pdo_fieldexists2("ddwx_workorder_category", "contentuser")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_category` ADD COLUMN `contentuser` text;");
    }
    if(!pdo_fieldexists2("ddwx_workorder_huifu", "form0")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_huifu` ADD COLUMN `form0` text ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_huifu` ADD COLUMN `form1` text ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_huifu` ADD COLUMN `form2` text ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_huifu` ADD COLUMN `form3` text ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_huifu` ADD COLUMN `form4` text ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_huifu` ADD COLUMN `form5` text ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_huifu` ADD COLUMN `form6` text ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_huifu` ADD COLUMN `form7` text ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_huifu` ADD COLUMN `form8` text ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_huifu` ADD COLUMN `form9` text ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_huifu` ADD COLUMN `form10` text ;");
    }
    if(!pdo_fieldexists2("ddwx_workorder_chuli", "form0")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_chuli` ADD COLUMN `form0` text ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_chuli` ADD COLUMN `form1` text ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_chuli` ADD COLUMN `form2` text ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_chuli` ADD COLUMN `form3` text ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_chuli` ADD COLUMN `form4` text ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_chuli` ADD COLUMN `form5` text ;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_chuli` ADD COLUMN `form6` text ;");
    }
}
if(getcustom('user_disabled_auth_data')){
    if(!pdo_fieldexists2("ddwx_admin", "disabled_auth_data")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `disabled_auth_data` text;");
    }
    if(!pdo_fieldexists2("ddwx_admin_user", "disabled_auth_data")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_user` ADD COLUMN `disabled_auth_data` text;");
    }
}
if(getcustom('workorder')){
    if(!pdo_fieldexists2("ddwx_workorder_category", "desc")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_workorder_category` ADD COLUMN `desc` text ;");
    }
}
if(getcustom('cashdesk_member_recharge')){
    if(!pdo_fieldexists2("ddwx_cashier", "member_recharge_status")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashier` ADD COLUMN `member_recharge_status` tinyint(1) DEFAULT '0' COMMENT '用户充值状态';");
    }
}
if(getcustom('region_partner')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_region_partner_order` (
    `id`  int(11) NOT NULL AUTO_INCREMENT ,
    `aid`  int(11) NOT NULL ,
    `ordernum`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '订单编号' ,
    `mid`  int(11) NOT NULL DEFAULT 0 COMMENT '会员id' ,
    `name`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '姓名' ,
    `tel`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '手机号' ,
    `company`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '店铺名称' ,
    `province`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '省' ,
    `city`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '市' ,
    `district`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '区县' ,
    `apply_money`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '申请费用' ,
    `bonus`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '已发奖金' ,
    `remain`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '剩余未发奖金' ,
    `status`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0未审核 1审核通过 2审核拒绝' ,
    `bonus_status`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '奖金状态 0未完成 1已完成' ,
    `createtime`  int(10) NOT NULL DEFAULT 0 COMMENT '申请时间' ,
    `payorderid`  int(11) NOT NULL DEFAULT 0 COMMENT '支付订单id' ,
    `paytime`  int(10) NOT NULL DEFAULT 0 COMMENT '支付时间' ,
    `paytype`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL ,
    `paytypeid`  int(11) NOT NULL ,
    `paynum`  decimal(12,2) NOT NULL DEFAULT 0.00 ,
    `platform`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' ,
    `set_id`  int(11) NOT NULL DEFAULT 0 COMMENT '区域设置id' ,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_region_partner_set` (
    `id`  int(11) NOT NULL AUTO_INCREMENT ,
    `aid`  int(11) NOT NULL DEFAULT 1 ,
    `apply_money`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '申请预存配送费用' ,
    `fh_num`  int(11) NOT NULL DEFAULT 0 COMMENT '参与分红人数' ,
    `day_fh`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '每日分红金额' ,
    `province`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '省' ,
    `city`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '市' ,
    `district`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '区县' ,
    `createtime`  int(10) NOT NULL ,
    PRIMARY KEY (`id`)
    )ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");
}
if(getcustom('coupon_xianxia_buy')){
    if(!pdo_fieldexists2("ddwx_member_level", "up_get_couponnum")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level`  ADD COLUMN `up_get_couponnum` int(11) DEFAULT '0' COMMENT '转入优惠券数量';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level`  ADD COLUMN `xianxia_coupon_jl` text COMMENT '线下优惠券 每张奖励';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level`  ADD COLUMN `yeji_reward_data` text COMMENT '业绩奖励设置';");
    }
    if(!pdo_fieldexists2("ddwx_coupon", "is_xianxia_buy")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon`  ADD COLUMN `is_xianxia_buy` tinyint(1) DEFAULT '0' COMMENT '是否是线下购买';");
    }
    if(!pdo_fieldexists2("ddwx_coupon_record", "is_xianxia_buy")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon_record`  ADD COLUMN `is_xianxia_buy` tinyint(1) DEFAULT '0' COMMENT '是否是线下购买';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_coupon_send` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `aid` int(11) DEFAULT NULL,
          `rid` int(11) DEFAULT '0' COMMENT '优惠券记录',
          `tomid` int(11) DEFAULT '0',
          `from_mid` int(11) DEFAULT '0',
          `send_time` int(11) DEFAULT '0',
           `coupon_yeji` decimal(11,2) DEFAULT '0.00' COMMENT '被转入的销售额',
          `from_coupon_yeji` decimal(11,2) DEFAULT '0.00' COMMENT '转入的销售额',
          `shouyi` decimal(11,2) DEFAULT '0.00' COMMENT '收益',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    if(!pdo_fieldexists2("ddwx_coupon_send", "coupon_yeji")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon_send`  ADD COLUMN `coupon_yeji` decimal(11,2) DEFAULT '0.00' COMMENT '被转入的销售额';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon_send`  ADD COLUMN `from_coupon_yeji` decimal(11,2) DEFAULT '0.00' COMMENT '转入的销售额';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon_send`  ADD COLUMN `shouyi` decimal(11,2) DEFAULT '0.00' COMMENT '收益';");
    }
    if(!pdo_fieldexists2("ddwx_member_level", "xianxia_coupon_vip_tj")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level`  ADD COLUMN `xianxia_coupon_vip_tj` text COMMENT '线下优惠 会员推荐';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level`  ADD COLUMN `xianxia_full` text COMMENT '线下满x组 发放奖励';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_xianxia_commission_log` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `aid` int(11) DEFAULT NULL,
          `mid` int(11) DEFAULT '0' COMMENT '谁发',
          `tomid` int(11) DEFAULT '0' COMMENT '发给谁',
          `frommid` int(11) DEFAULT '0',
          `commission` decimal(11,2) DEFAULT '0.00',
          `num` int(11) DEFAULT NULL,
          `status` tinyint(1) DEFAULT '0' COMMENT '0:待打款 1待确定 2完成 3异议',
          `remark` varchar(255) DEFAULT NULL,
          `pics` text COMMENT '凭证图片',
          `objection_pics` text,
          `objection_content` varchar(255) DEFAULT NULL,
          `createtime` int(11) DEFAULT '0',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}
if(getcustom('alipay_plugin_trade')){
    if(!pdo_fieldexists2("ddwx_shop_order", "alipay_component_orderid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `alipay_component_orderid` varchar (255) NULL DEFAULT '' COMMENT '支付宝小程序交易组件订单id';");
    }
}

if(getcustom('member_realname_verify')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_realname_set` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `withdraw_status` tinyint(1) DEFAULT '1' COMMENT '未实名能否提现，0关闭，1开启',
  `view_poster` tinyint(1) DEFAULT '1' COMMENT '查看海报页面，0关闭，1开启',
  `idno_area_range` text DEFAULT NULL COMMENT '身份证范围（限制地区）',
  `bind_member_num` tinyint(4) DEFAULT '0' COMMENT '一个身份证可认证绑定的会员数量',
  `status` tinyint(4) DEFAULT '0' COMMENT '0关闭，1开启',
  `createtime` int(11) DEFAULT NULL,
  `tencent_secret_id` varchar(60) DEFAULT NULL,
  `tencent_secret_key` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_realname_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `name` varchar(60) DEFAULT NULL,
  `sex` varchar(60) DEFAULT NULL,
  `nation` varchar(60) DEFAULT NULL,
  `birth` varchar(60) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `id_num` varchar(60) DEFAULT NULL,
  `authority` varchar(60) DEFAULT NULL,
  `valid_date` varchar(60) DEFAULT NULL,
  `idcard` varchar(255) DEFAULT NULL,
  `idcard_back` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `mid` (`aid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    if(!pdo_fieldexists2("ddwx_member_level", "realname_commission1")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `realname_commission1` decimal(12, 2) NOT NULL DEFAULT '0' COMMENT '推荐实名佣金';");
    }
    if(!pdo_fieldexists2("ddwx_member_realname_set", "bind_member_num")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_realname_set` ADD COLUMN `bind_member_num` tinyint(4) NULL DEFAULT '0' COMMENT '一个身份证可认证绑定的会员数量';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product", "realname_buy_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `realname_buy_status` tinyint(1) NULL DEFAULT '0' COMMENT '实名购买 0关闭，1开启';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product", "limittj")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `limittj` varchar(30) NULL DEFAULT '0' COMMENT '限购条件 0会员账号，1实名身份证，2微信，3手机号';");
    }
}
if(getcustom('member_level_down_commission')){
    if(!pdo_fieldexists2("ddwx_member_level", "down_level_totalcommission")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level`
		ADD COLUMN `down_level_totalcommission` decimal(12, 2) NOT NULL DEFAULT '0' COMMENT '累计佣金达到',
		ADD COLUMN `down_level_id2` int(11) DEFAULT '0' COMMENT '降级后的等级id',
		ADD COLUMN `recovery_level_proid` int(11)  DEFAULT '0' COMMENT '恢复等级购买产品';");
    }
    if(!pdo_fieldexists2("ddwx_member", "isauto_down")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member`
		ADD COLUMN `isauto_down` int(11) DEFAULT '0' COMMENT '是否有自动降级',
		ADD COLUMN `up_levelid` int(11)  DEFAULT '0' COMMENT '降级前的等级id';");
    }
    if(!pdo_fieldexists2("ddwx_member", "down_commission")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member`
		ADD COLUMN `down_commission`  decimal(11, 2) DEFAULT '0' COMMENT '降级佣金';");
    }
}

if(getcustom('commission_withdraw_need_score')){
    if(!pdo_fieldexists2("ddwx_admin_set", "comwithdraw_need_score")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
        ADD COLUMN `comwithdraw_need_score`  tinyint(1) NULL DEFAULT 0,
        ADD COLUMN `commission_score_exchange_num`  int(11) NULL DEFAULT 1;");
    }
    if(!pdo_fieldexists2("ddwx_member_commission_withdrawlog", "need_score")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_commission_withdrawlog`
ADD COLUMN `need_score`  int(11) NULL DEFAULT 0;");
    }
}
if(getcustom('commission_tomoney_need_score')){
    if(!pdo_fieldexists2("ddwx_admin_set", "comtomoney_need_score")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
        ADD COLUMN `comtomoney_need_score`  tinyint(1) NULL DEFAULT 0,
        ADD COLUMN `commission_money_exchange_num`  int(11) NULL DEFAULT 1;");
    }
}
if(getcustom('commission_recursion')){
    if(!pdo_fieldexists2("ddwx_admin_set", "is_fugou_commission")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` 
    ADD COLUMN `is_fugou_commission` tinyint(1) NULL DEFAULT '0' COMMENT '是否开启复购',
    ADD COLUMN `fugou_recursion_percent` decimal(10, 2) NULL DEFAULT '0' COMMENT '递归比例',
    ADD COLUMN `fugou_commission_min` decimal(10, 2) NULL DEFAULT '0' COMMENT '奖励下限';");
    }
    if(!pdo_fieldexists2("ddwx_member_level", "is_fugou_commission")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level`
        ADD COLUMN `is_fugou_commission`  tinyint(1) NULL DEFAULT '0' COMMENT '开启复购奖励';");
    }
}

if(getcustom('member_level_salary_bonus')){
    if(!pdo_fieldexists2("ddwx_member_level", "salary_bonus_content")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `salary_bonus_content`  text NULL COMMENT '薪资补贴json格式';");
    }
}
if(getcustom('commission_platform_avg_bonus')){
    if(!pdo_fieldexists2("ddwx_member_level", "platform_avgbonus_percent")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `platform_avgbonus_percent`  decimal(6, 2) NULL DEFAULT '0'  COMMENT '平台加权奖励%';");
    }
}

if(getcustom('commission_service_fee')){
    if(!pdo_fieldexists2("ddwx_admin_set", "commission_service_fee")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `commission_service_fee` decimal(5, 2) NULL DEFAULT '0' COMMENT '佣金平台服务费百分比';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set", "commission_service_fee_show")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `commission_service_fee_show` tinyint(1) NULL DEFAULT '0' COMMENT '佣金平台服务费百分比显示';");
    }
}
if(getcustom('levelup_from_levelid')){
    if(!pdo_fieldexists2("ddwx_member_level","gettj")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `gettj` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '升级前置等级条件';");
    }
}
if(getcustom('extend_certificate')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_certificate_category` (
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
      KEY `pid` (`pid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_certificate_job` (
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
      KEY `pid` (`pid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_certificate_list` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT NULL,
      `mid` int(11) DEFAULT NULL,
      `name` varchar(255) DEFAULT NULL,
      `tel` varchar(255) DEFAULT NULL,
      `cid` varchar(255) DEFAULT NULL,
      `certificate_pic` text,
      `idcard_pic_front` varchar(255) DEFAULT NULL COMMENT '身份证正面',
      `idcard_pic_back` varchar(255) DEFAULT NULL COMMENT '身份证反面',
      `school` varchar(255) DEFAULT NULL COMMENT '毕业院校',
      `education` int(11) DEFAULT NULL COMMENT '学历',
      `job_id` varchar(255) DEFAULT NULL,
      `ischecked` tinyint(1) DEFAULT '0' COMMENT '0：审核中  1： 审核通过',
      `check_reason` varchar(255) DEFAULT NULL,
      `sort` int(11) DEFAULT '0',
      `createtime` int(11) DEFAULT '0',
      `gettj` varchar(255) DEFAULT NULL,
      `admin_user` int(11) DEFAULT '0',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_certificate_education` (
	   `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `name` varchar(255) DEFAULT NULL,
      `status` int(1) DEFAULT '1',
      `sort` int(11) DEFAULT '1',
      `createtime` int(11) DEFAULT NULL,
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_certificate_set` (
	   `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `gettj` varchar(255) DEFAULT '-1',
      `uplevel_text` varchar(255) DEFAULT NULL,
      `uplevel_url` varchar(255) DEFAULT NULL,
      `createtime` int(11) DEFAULT NULL,
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE,
      KEY `bid` (`bid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}
if(getcustom('member_sms_group_send')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_sms_groupsend_templ` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) DEFAULT NULL,
		  `bid` int(11) DEFAULT NULL,
		  `tmpl_smscode` varchar(255) DEFAULT NULL COMMENT '模板编号',
		  `content` varchar(255) DEFAULT '' COMMENT '模板内容',
		  `createtime` int(11) DEFAULT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");
}
if(getcustom('form_agree')){
    if(!pdo_fieldexists2("ddwx_form", "show_agree")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `show_agree` tinyint(1) NOT NULL DEFAULT 0 COMMENT '提交表单是否显示用户须知 0不显示 1显示';");
    }
    if(!pdo_fieldexists2("ddwx_form", "agree_title")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `agree_title` varchar(255) NOT NULL DEFAULT '' COMMENT '用户须知标题';");
    }
    if(!pdo_fieldexists2("ddwx_form", "agree_desc")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `agree_desc` text NULL COMMENT '用户须知内容';");
    }
    if(!pdo_fieldexists2("ddwx_form", "agree_button")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `agree_button` varchar(255) NOT NULL DEFAULT '' COMMENT '用户须知按钮';");
    }
    if(!pdo_fieldexists2("ddwx_form", "agree_title_pos")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `agree_title_pos` varchar(20) NOT NULL DEFAULT 'bottom' COMMENT '用户须知位置';");
    }
}

if(getcustom('form_submit_notice')){
    if(!pdo_fieldexists2("ddwx_admin_set_sms","tmpl_formsubmit")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` ADD COLUMN  `tmpl_formsubmit` varchar(255) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` ADD COLUMN  `tmpl_formsubmit_st` tinyint(1) DEFAULT 0;");
    }
}
if(getcustom('kecheng_give_score')){
    if(!pdo_fieldexists2("ddwx_kecheng_chapter", "give_score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_chapter` ADD COLUMN `give_score` int(11) DEFAULT '0' COMMENT '赠送积分';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_kecheng_give_scorelog` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT '0',
      `bid` int(11) DEFAULT '0',
      `mid` int(11) DEFAULT '0',
      `kccid` int(11) DEFAULT '0' COMMENT '章节ID',
      `score` int(11) DEFAULT '0',
      `createtime` int(11) DEFAULT '0',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}
if(getcustom('product_guige_showtype')){
    if(!pdo_fieldexists2("ddwx_shop_sysset","guige_name")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN  `guige_name` varchar(255) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN  `show_guigetype` tinyint(1) DEFAULT 1;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN  `gwc_showst` tinyint(1) DEFAULT 1;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN  `gwc_name` varchar(255) DEFAULT NULL;");
    }
}
if(getcustom('shop_gwc_name')){
    if(!pdo_fieldexists2("ddwx_shop_sysset","gwc_showst")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN  `gwc_showst` tinyint(1) DEFAULT 1;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN  `gwc_name` varchar(255) DEFAULT NULL;");
    }
}
if(getcustom('commission_to_score')){
    if(!pdo_fieldexists2("ddwx_admin_set","commission_to_score_time")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `commission_to_score_time`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '佣金转积分时间 0自动 1手动';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","commission_to_score_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `commission_to_score_type`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '佣金转积分计算方式 0利润百分比 1固定金额';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","commission_to_score_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `commission_to_score_money`  decimal(12,2) NOT NULL DEFAULT 0 COMMENT '佣金转积分补贴金额';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order_goods","paytime")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `paytime`  int(10) NOT NULL DEFAULT 0 COMMENT '支付时间';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_commission_toscore_log` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) NOT NULL DEFAULT '0',
      `mid` int(11) NOT NULL DEFAULT '0',
      `commission_to_score_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '补贴金额计算方式 0利润百分比 1固定金额',
      `butie_num` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '补贴金额',
      `commission` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '会员佣金',
      `commission_total` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '全网佣金',
      `num` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '转换数量',
      `w_day` int(11) NOT NULL DEFAULT '0' COMMENT '执行日期',
      `w_time` int(11) NOT NULL DEFAULT '0' COMMENT '执行时间',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;");
    if(!pdo_fieldexists2("ddwx_admin_set","commission_to_score_bili")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `commission_to_score_bili`  decimal(12,2) NULL DEFAULT 0 COMMENT '积分转佣金释放比例 默认100';");
    }
}
if(getcustom('product_commission_desc')){
    if(!pdo_fieldexists2("ddwx_shop_sysset","commission_desc")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN  `commission_desc` varchar(255) DEFAULT NULL;");
    }
}
if(getcustom('product_list_nocart')){
    if(!pdo_fieldexists2("ddwx_shop_sysset","list_nocart_platform")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN  `list_nocart_platform` varchar(255) DEFAULT '' COMMENT '商品列表不显示购物车的平台';");
    }
}
if(getcustom('admin_money')){
    if(!pdo_fieldexists2("ddwx_admin","money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `money` decimal(11, 2) NULL DEFAULT '0' COMMENT '余额' AFTER `score`;");
    }
    if(!pdo_fieldexists2("ddwx_admin","money_notice_value")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `money_notice_value` int(11) NULL DEFAULT '0' COMMENT '余额提醒阈值' AFTER `money`;");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_admin_moneylog` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `money` decimal(11,2) DEFAULT '0.00',
  `after` decimal(11,2) DEFAULT '0.00',
  `createtime` int(11) DEFAULT NULL,
  `remark` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `paytype` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
}
if(getcustom('sms_system')) {
    if (!pdo_fieldexists2("ddwx_admin", "sms_system_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin` 
ADD COLUMN `sms_system_status` tinyint(1) NULL DEFAULT '0',
ADD COLUMN `sms_system_price` decimal(5, 2) NULL DEFAULT '0.05' AFTER `sms_system_status`;");
    }
}
if(getcustom('yx_day_give')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_day_give` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `config_data` text,
  `status` tinyint(1) DEFAULT '0',
  `createtime` int(11) DEFAULT NULL,
  `gettj_children` varchar(255) DEFAULT '-1',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_day_give_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `score` int(11) DEFAULT '0',
  `commission` decimal(11,2) DEFAULT '0.00',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `mid` (`mid`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    if (!pdo_fieldexists2("ddwx_member", "day_give_score_total")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` 
ADD COLUMN `day_give_score_total` int(11) NULL DEFAULT '0',
ADD COLUMN `day_give_commission_total` decimal(11, 2) NULL DEFAULT '0' AFTER `day_give_score_total`;");
    }
}
if(getcustom('camera_hikvision')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_hikvision` (
    `id`  int(11) NOT NULL AUTO_INCREMENT ,
    `aid`  int(11) NOT NULL DEFAULT 0 ,
    `bid`  int(11) NOT NULL DEFAULT 0 ,
    `name`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称' ,
    `ip`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '摄像头IP' ,
    `port`  int(11) NOT NULL DEFAULT 0 COMMENT '摄像头端口' ,
    `channel`  int(11) NOT NULL DEFAULT 0 COMMENT '通道' ,
    `status`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '0未启用 1启用' ,
    `createtime`  int(10) NOT NULL DEFAULT 0 COMMENT '添加时间' ,
    `maliu`  int(11) NOT NULL DEFAULT 1 COMMENT '码流类型' ,
    `appkey`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL ,
    `secretkey`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL ,
    `productcode`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '产品标识' ,
    `projectid`  int(11) NULL DEFAULT NULL COMMENT '项目id' ,
    `deviceserial`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '设备序列号' ,
    `pic`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL ,
    `icon`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL ,
    `pwd` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`id`)
    )ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci CHECKSUM=0 ROW_FORMAT=Dynamic DELAY_KEY_WRITE=0;");
}
if(getcustom('yx_team_yeji')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_team_yeji_set` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `config_data` text,
      `jiesuan_type` tinyint(1) DEFAULT '1',
      `status` tinyint(1) DEFAULT '1',
      `createtime` int(11) DEFAULT '0',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_tem_yeji_xuni` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `yeji` decimal(11,2) DEFAULT '0.00',
      `mid` int(11) DEFAULT '0',
      `yeji_month` varchar(100) DEFAULT NULL,
      `createtime` int(11) DEFAULT '0',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}


if(getcustom('discount_code_zhongchuang')){
    if(!pdo_fieldexists2("ddwx_shop_product","price_discount_code_zc")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `price_discount_code_zc` decimal(11,2) DEFAULT NULL;");
    }
    if(!pdo_fieldexists2("ddwx_shop_order","discount_code_zc")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `discount_code_zc` varchar(60) DEFAULT NULL;");
    }
}

if(getcustom('areafenhong_region_ranking')){
    if (!pdo_fieldexists2("ddwx_admin_set", "region_ctime")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `region_ctime` varchar(100) DEFAULT NULL COMMENT '区域代理排行榜时间范围';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `region_show_type` varchar(10) DEFAULT '1,2' COMMENT '区域代理排行榜显示类型';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","region_rank_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `region_rank_status` tinyint(1) DEFAULT NULL COMMENT '区域代理排行榜状态';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `region_rank_levelids` varchar(255) DEFAULT NULL COMMENT '区域代理排行榜等级';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `region_rank_people` int(11) DEFAULT '0' COMMENT '区域代理排行榜显示人数';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_region_ranking` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) NOT NULL DEFAULT 0,
        `bid` int(11) NOT NULL DEFAULT 0,
        `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '名称',
        `show_type` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '统计类型',
        `levelids` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '显示范围',
        `levelids2` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '统计等级',
        `ctime` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '时间范围',
        `people` int(11) NOT NULL DEFAULT 0 COMMENT '显示人数',
        `levelnum` int(11) NOT NULL DEFAULT 0 COMMENT '团队统计级数',
        `qytype` tinyint(1) NOT NULL DEFAULT 0 COMMENT '区域不参与排名商品 0：不指定 2:指定商品',
        `productids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '区域指定商品',
        `tdtype` tinyint(1) NOT NULL DEFAULT 0 COMMENT '团队不参与排名商品 0：不指定 2:指定商品',
        `productids2` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '团队指定商品',
        `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态',
        `sort` int(11) NOT NULL DEFAULT 0,
        `createtime` int(10) UNSIGNED NOT NULL DEFAULT 0,
        `updatetime` int(10) UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`) USING BTREE,
        INDEX `aid`(`aid`) USING BTREE,
        INDEX `bid`(`bid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}
if(getcustom('maidan_fenhong_new')){
    if (!pdo_fieldexists2("ddwx_business", "maidan_area")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `maidan_area`  tinyint(1) NOT NULL DEFAULT 1 COMMENT '开启买单区域代理分红 1开启 0关闭';");
    }
    if (!pdo_fieldexists2("ddwx_business", "maidan_team")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `maidan_team`  tinyint(1) NOT NULL DEFAULT 1 COMMENT '开启买单团队分红 1开启 0关闭';");
    }
    if (!pdo_fieldexists2("ddwx_business", "maidan_team_jiandan")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `maidan_team_jiandan`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '团队分红见单 1开启 0关闭';");
    }
    if (!pdo_fieldexists2("ddwx_business", "maidan_touzi")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `maidan_touzi`  tinyint(1) NOT NULL DEFAULT 1 COMMENT '买单投资分红 1开启 0关闭';");
    }
    if (!pdo_fieldexists2("ddwx_business", "maidan_gongxian")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `maidan_gongxian`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '买单贡献值分红 1开启 0关闭';");
    }
    if (!pdo_fieldexists2("ddwx_business", "maidan_gudong")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `maidan_gudong`  tinyint(1) NOT NULL DEFAULT 1 COMMENT '开启买单股东分红 1开启  0关闭';");
    }
    if (!pdo_fieldexists2("ddwx_admin_set", "maidanfenhong")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `maidanfenhong`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '买单收款参与分红 0否 1是';");
    }
    if (!pdo_fieldexists2("ddwx_admin_set", "maidan_cost")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `maidan_cost`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '买单成本比例';");
    }
    if (!pdo_fieldexists2("ddwx_admin_set", "maidanfenhong_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `maidanfenhong_type`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '买单分红结算方式 0按成本 1按销售';");
    }
    if (!pdo_fieldexists2("ddwx_member_level", "teamfenhongbl_maidan")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `teamfenhongbl_maidan`  decimal(12,2) NULL DEFAULT NULL COMMENT '买单团队分红比例';");
    }
    if (!pdo_fieldexists2("ddwx_member_level", "teamfenhong_pingji_bl_maidan")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `teamfenhong_pingji_bl_maidan`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '买单团队分红平级奖奖金比例';");
    }
    if (!pdo_fieldexists2("ddwx_member_level", "teamfenhong_bole_bl_maidan")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `teamfenhong_bole_bl_maidan`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '买单团队分红伯乐奖奖金比例';");
    }
    if (!pdo_fieldexists2("ddwx_member_level", "teamfenhong_jiandan_bl_maidan")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `teamfenhong_jiandan_bl_maidan`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '买单团队见单分红比例';");
    }
    if (!pdo_fieldexists2("ddwx_member_level", "level_teamfenhongbl_maidan")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `level_teamfenhongbl_maidan`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '分红比例';");
    }
    if (!pdo_fieldexists2("ddwx_member_level", "fenhong_maidan_percent")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `fenhong_maidan_percent`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '买单股东分红';");
    }
    if (!pdo_fieldexists2("ddwx_member_level", "areafenhongbl_maidan")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `areafenhongbl_maidan`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '买单区域代理分红比例';");
    }
    if (!pdo_fieldexists2("ddwx_business", "cost_bili")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `cost_bili`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '成本价比例';");
    }
    if (!pdo_fieldexists2("ddwx_business", "cost_bili_with_edit")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `cost_bili_with_edit` tinyint(1) NOT NULL DEFAULT '1' COMMENT '设置成本比例后，是否允许商户修改商品成本，关闭后商家不可修改';");
    }
    if (!pdo_fieldexists2("ddwx_admin_set", "maidanfenxiao_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `maidanfenxiao_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '买单分销结算方式 0按销售额 1按利润';");
    }
    if (!pdo_fieldexists2("ddwx_member", "areafenhongbl_maidan")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `areafenhongbl_maidan` decimal(12,2) NULL DEFAULT 0.00 COMMENT '买单收款分红比例';");
    }
    if (!pdo_fieldexists2("ddwx_business", "maidan_cost")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `maidan_cost` decimal(12,2) NULL DEFAULT 0.00 COMMENT '买单收款成本比例';");
    }
    if (!pdo_fieldexists2("ddwx_maidan_order", "cost_price")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_maidan_order` ADD COLUMN `cost_price` decimal(12,2) NULL DEFAULT 0.00 COMMENT '买单收款成本';");
    }
    if (!pdo_fieldexists2("ddwx_shop_sysset", "cost_bili")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `cost_bili` decimal(12,2) NULL DEFAULT 0.00 COMMENT '成本价比例';");
    }
}
if(getcustom('restaurant_order_payafter_autoclose')){
    if (!pdo_fieldexists2("ddwx_restaurant_shop_sysset", "pay_after_autoclose")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_sysset` ADD COLUMN `pay_after_autoclose` tinyint(1) NOT NULL DEFAULT 0 COMMENT '未支付订单关闭 0：关闭 1：开启';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_sysset` ADD COLUMN `pay_after_autoclosetime` int(11) NOT NULL DEFAULT 15 COMMENT '自动关闭时间';");
    }
}
if(getcustom('restaurant_table_name')){
    if (!pdo_fieldexists2("ddwx_restaurant_shop_sysset", "table_text")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_sysset` ADD COLUMN `table_text` varchar(50) NOT NULL DEFAULT '桌号' COMMENT '桌号别名';");
    }
}

if(getcustom('business_fenxiao')){
    if (!pdo_fieldexists2("ddwx_admin_set", "business_fenxiao_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `business_fenxiao_type`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '店铺营业额 0支付完成统计 1收货完成统计';");
    }
    if (!pdo_fieldexists2("ddwx_business", "promoter")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `promoter`  text COMMENT '发起人id';");
    }else{
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` MODIFY COLUMN `promoter` text COMMENT '发起人id';");
    }
    if (!pdo_fieldexists2("ddwx_business", "promoter_mids")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `promoter_mids` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '发起人会员id';");
        $b_list = \think\facade\Db::name('business')->field('id,promoter')->select()->toArray();
        foreach($b_list as $b){
            $promoter = $b['promoter']?json_decode($b['promoter'],true):[];
            if($promoter){
                $promoter_mids = array_keys($promoter);
                $promoter_mids = implode(',',$promoter_mids);
                \think\facade\Db::name('business')->where('id',$b['id'])->update(['promoter_mids'=>$promoter_mids]);
            }
        }
    }
    if (!pdo_fieldexists2("ddwx_business", "partner")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `partner`  text COMMENT '合伙人id';");
    }else{
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` MODIFY COLUMN `partner`  text COMMENT '合伙人id';");
    }
    if (!pdo_fieldexists2("ddwx_business", "partner_mids")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `partner_mids`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '合伙人会员id';");
    }
    if (!pdo_fieldexists2("ddwx_business", "protect_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `protect_status`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '开启保护期 1开启 0关闭';");
    }
    if (!pdo_fieldexists2("ddwx_business", "protect_yeji")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `protect_yeji`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '保护期每日业绩';");
    }
    if (!pdo_fieldexists2("ddwx_business", "protect_day")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `protect_day`  int(11) NOT NULL DEFAULT 0 COMMENT '保护期天数';");
    }
    if (!pdo_fieldexists2("ddwx_business", "protect_cost_bili")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `protect_cost_bili`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '保护期成本比例';");
    }
    if (!pdo_fieldexists2("ddwx_business", "protect_plate_bili")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `protect_plate_bili`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '保护期平台比例';");
    }
    if (!pdo_fieldexists2("ddwx_business", "protect_business_bili")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `protect_business_bili`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '保护期店铺比例';");
    }
    if (!pdo_fieldexists2("ddwx_business", "protect_business_send_bili")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `protect_business_send_bili`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '保护期店铺发放比例';");
    }
    if (!pdo_fieldexists2("ddwx_business", "mature_yeji")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `mature_yeji`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '成熟期业绩';");
    }
    if (!pdo_fieldexists2("ddwx_business", "mature_cost_bili")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `mature_cost_bili`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '成熟期成本比例';");
    }
    if (!pdo_fieldexists2("ddwx_business", "mature_plate_bili")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `mature_plate_bili`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '成熟期平台比例';");
    }
    if (!pdo_fieldexists2("ddwx_business", "mature_business_bili")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `mature_business_bili`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '成熟期店铺比例';");
    }
    if (!pdo_fieldexists2("ddwx_business", "mature_business_send_bili")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `mature_business_send_bili`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '成熟期店铺发放比例';");
    }
    if (!pdo_fieldexists2("ddwx_business", "promoter_bili")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `promoter_bili`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '发起人奖金比例';");
    }
    if (!pdo_fieldexists2("ddwx_business", "promoter_tj_bili")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `promoter_tj_bili`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '发起人推荐人奖金比例';");
    }
    if (!pdo_fieldexists2("ddwx_business", "partner_bili")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `partner_bili`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '合伙人奖金比例';");
    }
    if (!pdo_fieldexists2("ddwx_business", "partner_tj_bili")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `partner_tj_bili`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '合伙人推荐人奖金比例';");
    }
    if (!pdo_fieldexists2("ddwx_business", "count_yeji")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `count_yeji`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '营业数据统计 0自动 1手动';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_business_fenxiao` (
        `id`  int(11) NOT NULL AUTO_INCREMENT ,
        `aid`  int(11) NOT NULL ,
        `bid`  int(11) NOT NULL DEFAULT 0 COMMENT '商户id' ,
        `yeji`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '营销业绩' ,
        `butie_yeji`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '保护期内系统补贴的业绩' ,
        `yeji_total`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '补贴后的总业绩' ,
        `type`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '统计方式 0自动 1手动录入' ,
        `cost`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '成本' ,
        `business`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '店铺金额' ,
        `plate`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '平台金额' ,
        `business_send`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '发放金额' ,
        `promoter_mid`  int(11) NOT NULL DEFAULT 0 COMMENT '发起人id' ,
        `promoter`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '发起人金额' ,
        `promoter_tj_mid`  int(11) NOT NULL DEFAULT 0 COMMENT '发起人推荐人id' ,
        `promoter_tj`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '发起人推荐人金额' ,
        `partner_mids`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '合伙人id' ,
        `partner`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '合伙人金额' ,
        `partner_tj_mids`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '合伙人推荐人id' ,
        `partner_tj`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '合伙人推荐人金额' ,
        `jiesuan_time`  int(11) NOT NULL DEFAULT 0 COMMENT '结算日期' ,
        `jiesuan_day`  int(11) NOT NULL DEFAULT 0 COMMENT '结算日期' ,
        `createtime`  int(11) NOT NULL DEFAULT 0 COMMENT '统计时间' ,
        `sendtime`  int(11) NOT NULL DEFAULT 0 COMMENT '发放时间' ,
        `status`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '0未结算 1已结算' ,
        `lirun` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '净利润',
        `stage` tinyint(1) NOT NULL DEFAULT '0' COMMENT '店铺阶段 1保护期 2成熟期',
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");
    if (!pdo_fieldexists2("ddwx_payorder", "business_fenxiao")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_payorder` ADD COLUMN `business_fenxiao`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已统计店铺分销营业额 0未统计 1已统计';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_business_fenxiao_bonus` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) NOT NULL DEFAULT '0',
      `mid` int(11) DEFAULT '0' COMMENT '会员id',
      `bonus` decimal(12,2) DEFAULT '0.00' COMMENT '奖金数量',
      `bid` int(11) DEFAULT '0' COMMENT '来源商户id',
      `type` varchar(255) DEFAULT '' COMMENT '奖金类型',
      `createtime` int(10) DEFAULT '0' COMMENT '奖金结算时间',
      `yeji` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '店铺业绩',
      `butie_yeji` decimal(11,0) NOT NULL DEFAULT '0' COMMENT '补贴业绩',
      `jiesuan_time` int(11) DEFAULT '0' COMMENT '结算日期',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='店铺分销——奖金明细';");
    if (!pdo_fieldexists2("ddwx_business_fenxiao_bonus", "yeji")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business_fenxiao_bonus` ADD COLUMN `yeji` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '店铺业绩';");
    }
    if (!pdo_fieldexists2("ddwx_business_fenxiao_bonus", "butie_yeji")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business_fenxiao_bonus` ADD COLUMN `butie_yeji` decimal(11,0) NOT NULL DEFAULT '0' COMMENT '补贴业绩';");
    }
    if (!pdo_fieldexists2("ddwx_business_fenxiao_bonus", "jiesuan_time")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business_fenxiao_bonus` ADD COLUMN `jiesuan_time` int(11) DEFAULT '0' COMMENT '结算日期';");
    }
    if (!pdo_fieldexists2("ddwx_member_commission_withdrawlog", "bid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_commission_withdrawlog` ADD COLUMN `bid`  int(11) NOT NULL DEFAULT 0 COMMENT '店铺id';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_business_fenxiao_bonus_total` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) NOT NULL DEFAULT '0',
      `mid` int(11) NOT NULL DEFAULT '0',
      `bid` int(11) NOT NULL DEFAULT '0',
      `bonus_total` decimal(12,2) NOT NULL DEFAULT '0.00',
      `withdraw` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '已提现金额',
      `remain` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '剩余奖励',
      PRIMARY KEY (`id`),
      KEY `mid` (`mid`,`bid`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='店铺分销奖励来源汇总';");
    if (!pdo_fieldexists2("ddwx_member", "disable_withdraw")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `disable_withdraw`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '冻结提现功能 0否 1是';");
    }
}
if(getcustom('fenhong_gudong_huiben')){
    if (!pdo_fieldexists2("ddwx_member", "total_fenhong_huiben")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `total_fenhong_huiben`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '回本股东分红奖金';");
    }
    if (!pdo_fieldexists2("ddwx_member_level", "fenhong_huiben")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `fenhong_huiben`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '回本股东分红比例';");
    }
    if (!pdo_fieldexists2("ddwx_member_level", "fenhong_max_money_huiben")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `fenhong_max_money_huiben`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '回本股东分红上限';");
    }
    if (!pdo_fieldexists2("ddwx_shop_product", "gdfenhongdata1_huiben")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `gdfenhongdata1_huiben`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '回本股东分红设置';");
    }
    if (!pdo_fieldexists2("ddwx_shop_product", "gdfenhongdata2_huiben")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `gdfenhongdata2_huiben`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '回本股东分红设置';");
    }
    if (!pdo_fieldexists2("ddwx_shop_product", "gdfenhongset_huiben")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `gdfenhongset_huiben`  tinyint(1) NULL DEFAULT 0 COMMENT '回本股东分红设置 0按等级 1单独设置比例 -1不参与';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_fenhong_huiben` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) NOT NULL DEFAULT '0',
      `mid` int(11) NOT NULL DEFAULT '0' COMMENT '会员id',
      `levelid` int(11) NOT NULL DEFAULT '0' COMMENT '级别id',
      `level_sort` int(11) NOT NULL DEFAULT '0',
      `fenhong` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '分红数量',
      `max` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '分红最大值',
      `update_time` int(10) NOT NULL DEFAULT '0' COMMENT '更新时间',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='回本股东分红记录';");
    if (!pdo_fieldexists2("ddwx_shop_order_goods", "isfenhong_huiben")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `isfenhong_huiben`  tinyint(1) NULL DEFAULT 0 COMMENT '回本股东分红 0未结算 1已结算';");
    }
    if (pdo_fieldexists3("ddwx_yuyue_order") && !pdo_fieldexists2("ddwx_yuyue_order", "isfenhong_huiben")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` ADD COLUMN `isfenhong_huiben`  tinyint(1) NULL DEFAULT 0 COMMENT '回本股东分红 0未结算 1已结算';");
    }
    if (pdo_fieldexists3("ddwx_scoreshop_order_goods") && !pdo_fieldexists2("ddwx_scoreshop_order_goods", "isfenhong_huiben")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order_goods` ADD COLUMN `isfenhong_huiben`  tinyint(1) NULL DEFAULT 0 COMMENT '回本股东分红 0未结算 1已结算';");
    }
    if (pdo_fieldexists3("ddwx_lucky_collage_order") && !pdo_fieldexists2("ddwx_lucky_collage_order", "isfenhong_huiben")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_order` ADD COLUMN `isfenhong_huiben`  tinyint(1) NULL DEFAULT 0 COMMENT '回本股东分红 0未结算 1已结算';");
    }
    if (pdo_fieldexists3("ddwx_maidan_order") && !pdo_fieldexists2("ddwx_maidan_order", "isfenhong_huiben")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_maidan_order` ADD COLUMN `isfenhong_huiben`  tinyint(1) NULL DEFAULT 0 COMMENT '回本股东分红 0未结算 1已结算';");
    }
    if (pdo_fieldexists3("ddwx_restaurant_shop_order_goods") && !pdo_fieldexists2("ddwx_restaurant_shop_order_goods", "isfenhong_huiben")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order_goods` ADD COLUMN `isfenhong_huiben`  tinyint(1) NULL DEFAULT 0 COMMENT '回本股东分红 0未结算 1已结算';");
    }
    if (pdo_fieldexists3("ddwx_restaurant_takeaway_order_goods") && !pdo_fieldexists2("ddwx_restaurant_takeaway_order_goods", "isfenhong_huiben")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_order_goods` ADD COLUMN `isfenhong_huiben`  tinyint(1) NULL DEFAULT 0 COMMENT '回本股东分红 0未结算 1已结算';");
    }
    if (pdo_fieldexists3("ddwx_coupon_order") && !pdo_fieldexists2("ddwx_coupon_order", "isfenhong_huiben")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon_order` ADD COLUMN `isfenhong_huiben`  tinyint(1) NULL DEFAULT 0 COMMENT '回本股东分红 0未结算 1已结算';");
    }
    if (pdo_fieldexists3("ddwx_kecheng_order") && !pdo_fieldexists2("ddwx_kecheng_order", "isfenhong_huiben")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_order` ADD COLUMN `isfenhong_huiben`  tinyint(1) NULL DEFAULT 0 COMMENT '回本股东分红 0未结算 1已结算';");
    }
    if (!pdo_fieldexists2("ddwx_admin_set", "fhjiesuantime_type_huiben")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `fhjiesuantime_type_huiben`  tinyint(1) NULL DEFAULT 0 COMMENT '结算时间 0确认收货后结算 1付款后结算';");
    }
    if (!pdo_fieldexists2("ddwx_admin_set", "fhjiesuantime_huiben")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `fhjiesuantime_huiben`  tinyint(1) NULL DEFAULT 0 COMMENT '结算时间 0每天结算 1月初结算 2每小时结算 3每分钟结算 4月底结算 5年底结算';");
    }
    if (!pdo_fieldexists2("ddwx_business", "maidan_fenhong_huiben")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `maidan_fenhong_huiben`  tinyint(1) NULL DEFAULT 0 COMMENT '买单回本分红 0关闭 1开启';");
    }
    if (!pdo_fieldexists2("ddwx_admin_set", "fenhong_huiben_max_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `fenhong_huiben_max_status`  tinyint(1) NULL DEFAULT 0 COMMENT '回本股东分红额度限制 0关闭 1开启';");
    }
    if (!pdo_fieldexists2("ddwx_member", "huiben_maximum")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `huiben_maximum`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '回本分红额度';");
    }
    if (!pdo_fieldexists2("ddwx_member", "total_fenhong_huiben2")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `total_fenhong_huiben2`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '累计获得回本分红';");
    }
    if (!pdo_fieldexists2("ddwx_member_level", "fenhong_huiben_max_bili")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `fenhong_huiben_max_bili`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '回本股东分红额度比例';");
    }
    if (!pdo_fieldexists2("ddwx_shop_order", "huiben_maximum")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `huiben_maximum`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '发放分红额度数量';");
    }
    if (!pdo_fieldexists2("ddwx_fenhong_huiben", "maximum")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_fenhong_huiben` ADD COLUMN `maximum`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '红额度数量';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_huibenmaximum_log` (
    `id`  int(11) NOT NULL AUTO_INCREMENT ,
    `aid`  int(11) NULL DEFAULT NULL ,
    `mid`  int(11) NULL DEFAULT NULL ,
    `value`  decimal(12,2) NULL DEFAULT NULL ,
    `after`  decimal(12,2) NULL DEFAULT NULL ,
    `createtime`  int(10) NULL DEFAULT NULL ,
    `remark`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL ,
    `channel`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL ,
    `orderid`  int(11) NULL DEFAULT NULL ,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");
    if (!pdo_fieldexists2("ddwx_member_level", "fenhong_huiben_jiaquan_ids")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `fenhong_huiben_jiaquan_ids`  varchar (255) NULL DEFAULT '' COMMENT '回本股东分红加权级别ID';");
    }
}
if(getcustom('yx_team_yeji_manage')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_team_yeji_manage_set` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `config_data` text,
  `status` tinyint(1) DEFAULT '0',
  `createtime` int(11) DEFAULT NULL,
  `gettj_children` varchar(255) DEFAULT '-1',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_team_yeji_manage` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `from_mid` int(11) DEFAULT NULL,
  `score` int(11) DEFAULT '0',
  `commission` decimal(11,2) DEFAULT '0.00',
  `money` decimal(11,2) DEFAULT '0.00',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `mid` (`mid`),
  KEY `from_mid` (`from_mid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    if (!pdo_fieldexists2("ddwx_member", "team_yeji_manage_commission_total")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `team_yeji_manage_commission_total` decimal(11, 2) NULL DEFAULT '0';");
    }
}
if(getcustom('teamfenhong_pingji_yueji')){
    if (!pdo_fieldexists2("ddwx_admin_set", "teamfenhong_pingji_yueji")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`	ADD COLUMN `teamfenhong_pingji_yueji` tinyint(1) NULL DEFAULT '1' COMMENT '平级奖允许越级,1开，0关，关闭后不允许越级';");
    }
}
if(getcustom('alipay_auto_transfer')){
    if(pdo_fieldexists2("ddwx_admin_set","ali_appid")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` MODIFY COLUMN `ali_appid` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '支付宝APPID' ;");
    }
}

if(getcustom('yx_queue_free')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_queue_free_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  `rate` decimal(11,2) DEFAULT '0.00' COMMENT '比例',
  `rate_min` decimal(11,2) DEFAULT '0.00' COMMENT '最小比例',
  `rate_max` decimal(11,2) DEFAULT '100.00' COMMENT '最大比例',
  `rate_status_business` tinyint(1) DEFAULT '0' COMMENT '多商户是否有权限编辑比例:0无，1有，多商户配置为-1时跟随系统设置',
  `money_max` decimal(11,2) DEFAULT '0.00' COMMENT '单笔金额上限',
  `createtime` int(11) DEFAULT NULL,
  `gettj_children` varchar(255) DEFAULT '-1',
  `order_types` varchar(255) DEFAULT 'all' COMMENT '参与订单范围',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_queue_free` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL COMMENT 'shop maidan',
  `orderid` int(11) DEFAULT NULL,
  `ordernum` varchar(100) DEFAULT NULL,
  `ordermoney` decimal(11,2) DEFAULT '0.00',
  `title` varchar(255) DEFAULT NULL,
  `money` decimal(11,2) DEFAULT '0.00' COMMENT '应返金额',
  `money_give` decimal(11,2) DEFAULT '0.00' COMMENT '已返金额',
  `score` int(11) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `endtime` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0' COMMENT '0排队中，1已完成',
  `queue_no` int(11) DEFAULT NULL COMMENT '排名',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `bid` (`bid`),
  KEY `mid` (`mid`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_queue_free_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `queueid` int(11) DEFAULT NULL,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL COMMENT 'shop maidan',
  `orderid` int(11) DEFAULT NULL,
  `ordernum` varchar(100) DEFAULT NULL,
  `ordermoney` decimal(11,2) DEFAULT '0.00',
  `title` varchar(255) DEFAULT NULL,
  `from_queueid` int(11) DEFAULT NULL,
  `from_mid` int(11) DEFAULT NULL,
  `money_give` decimal(11,2) DEFAULT '0.00' COMMENT '已返金额',
  `score` int(11) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `queueid` (`queueid`),
  KEY `from_queueid` (`from_queueid`),
  KEY `aid` (`aid`),
  KEY `bid` (`bid`),
  KEY `mid` (`mid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    if(!pdo_fieldexists2("ddwx_shop_product","queue_free_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `queue_free_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '排队免单状态：0关闭，1开启';");
    }
    if(!pdo_fieldexists2("ddwx_queue_free_set","rate_back")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_queue_free_set` ADD COLUMN `rate_back` decimal(11,2) NOT NULL DEFAULT '0' COMMENT '返利比例';");
    }
    if(!pdo_fieldexists2("ddwx_queue_free_set","queue_type_business")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_queue_free_set` ADD COLUMN `queue_type_business` tinyint(1) NOT NULL DEFAULT '0' COMMENT '多商户排队 0独立，1参与平台';");
    }
    if(!pdo_fieldexists2("ddwx_queue_free_set","time_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_queue_free_set` ADD COLUMN `time_type` tinyint(1) UNSIGNED NULL DEFAULT 0 COMMENT '排队时间:0确认收货，1支付';");
    }
    if(!pdo_fieldexists2("ddwx_queue_free_set","receive_account")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_queue_free_set` 
    ADD COLUMN `receive_account` varchar(30) NULL DEFAULT 'money' COMMENT '返现账户:money余额',
    ADD COLUMN `fenzhang_wxpay_rate` decimal(5,2) DEFAULT '0.00',
    ADD COLUMN `parent_fast` tinyint(1) UNSIGNED NULL DEFAULT '0' COMMENT '分享速返：0关闭 1开启',
    ADD COLUMN `quit_wxhb` tinyint(1) UNSIGNED NULL DEFAULT '0' COMMENT '退出返红包:0关闭 1开启',
    ADD COLUMN `quit_wxhb_min` decimal(5,2) DEFAULT '0.00',
    ADD COLUMN `quit_wxhb_max` decimal(5,2) DEFAULT '100.00';");
    }
    if(!pdo_fieldexists2("ddwx_queue_free_set","parent_fast_rate")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_queue_free_set` ADD COLUMN `parent_fast_rate` decimal(5,2) DEFAULT '100.00' AFTER `parent_fast`;");
    }

    if(!pdo_fieldexists2("ddwx_queue_free","money_quit_hb")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_queue_free` 
    ADD COLUMN `money_quit_hb` decimal(11, 2) NULL DEFAULT NULL,
    ADD COLUMN `isquit` tinyint(1) UNSIGNED NULL DEFAULT '0' COMMENT '退出';");
    }
    if(!pdo_fieldexists2("ddwx_queue_free_log","receive_account")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_queue_free_log` 
    ADD COLUMN `receive_account` varchar(30) NULL DEFAULT 'money' COMMENT '返现账户:money余额',
    ADD COLUMN `isfenzhang` tinyint(1) NULL DEFAULT '0',
    ADD COLUMN `fz_errmsg` varchar(200) NULL,
    ADD COLUMN `fz_ordernum` varchar(60) NULL,
    ADD COLUMN `wxpay_status` tinyint(1) NULL DEFAULT '0',
    ADD COLUMN `wxpay_errmsg` varchar(200) NULL;");
    }
    if(!pdo_fieldexists2("ddwx_queue_free_log","payorderjson")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_queue_free_log` ADD COLUMN `payorderjson` text NULL;");
    }
    if(!pdo_fieldexists2("ddwx_queue_free_log","payordertype")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_queue_free_log` ADD COLUMN `payordertype` varchar(100) NULL,
     ADD COLUMN `payordernum` varchar(100) NULL;");
    }
    if(!pdo_fieldexists2("ddwx_queue_free_log","fenzhang_wxpay_rate")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_queue_free_log` ADD COLUMN `fenzhang_wxpay_rate` decimal(5, 2) NULL DEFAULT '0';");
    }
}
if(getcustom('transfer_farsion')){
    if (!pdo_fieldexists2("ddwx_admin_set", "withdraw_aliaccount_xiaoetong")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`  ADD COLUMN `withdraw_aliaccount_xiaoetong` tinyint(1) DEFAULT '0' COMMENT '小额通支付宝';");
    }
    if (!pdo_fieldexists2("ddwx_admin_set", "withdraw_bankcard_xiaoetong")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`  ADD COLUMN `withdraw_bankcard_xiaoetong` tinyint(1) DEFAULT '0' COMMENT '小额通银行卡';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_withdrawlog_xiaoetong` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `withdrawlog_id` int(11) DEFAULT NULL COMMENT '提现表id',
  `money` decimal(11,2) DEFAULT NULL,
  `txmoney` decimal(11,2) DEFAULT NULL,
  `aliaccount` varchar(255) DEFAULT NULL,
  `aliaccountname` varchar(255) DEFAULT NULL COMMENT '支付宝姓名',
  `ordernum` varchar(255) DEFAULT NULL,
  `paytype` varchar(255) DEFAULT NULL,
  `status` tinyint(3) DEFAULT '0' COMMENT '状态 0:''待提交'',1:''''已提交'',2:'' 提交失败'',3:''待支付'',4:''已确认'',5:''出款中'',6:''已取消'',7:''结算成功'',8:''结算失败'',9:''部分成功'',10:''已退票'',11:''已关闭''',
  `createtime` int(11) DEFAULT NULL,
  `bankname` varchar(255) DEFAULT NULL,
  `bankcarduser` varchar(255) DEFAULT NULL,
  `bankcardnum` varchar(255) DEFAULT NULL,
  `paytime` int(11) DEFAULT NULL,
  `platform` varchar(255) DEFAULT NULL,
  `order_no` varchar(255) DEFAULT NULL COMMENT '小额通订单号',
  `batch_no` varchar(255) DEFAULT NULL COMMENT '小额通批次号',
  `task_id` int(11) DEFAULT NULL COMMENT '小额通任务id',
  `withdraw_type` varchar(255) DEFAULT NULL COMMENT '推送提现的类型余额提现佣金提现',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='小额通提现推送记录';");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_transfer_farsion_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `appid` varchar(255) NOT NULL DEFAULT '' COMMENT '企业平台',
  `private_secret` text COMMENT '用户私钥',
  `public_secret_system` text COMMENT '系统公钥',
  `task_id` int(11) DEFAULT NULL COMMENT '任务id',
  `domain_url` varchar(255) DEFAULT NULL COMMENT '域名地址',
  `model` tinyint(3) DEFAULT '2' COMMENT '结算模式',
  `pay_type` tinyint(3) DEFAULT '1' COMMENT '结算通道',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `bid` (`bid`),
  KEY `aid` (`aid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='小额通平台配置信息';");
}

if(getcustom('gdfenhong_add')){
    if(!pdo_fieldexists2("ddwx_admin_set","gdfenhong_add")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`	ADD COLUMN `gdfenhong_add` tinyint(1) NULL DEFAULT '0' COMMENT '股东分红叠加 1开启 0关闭';");
    }
}

if(getcustom('sms_temp_money_recharge')){
    if(!pdo_fieldexists2("ddwx_admin_set_sms","tmpl_money_recharge_st")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms`	ADD COLUMN `tmpl_money_recharge_st` tinyint(1) DEFAULT '1' COMMENT '充值余额';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms`	ADD COLUMN `tmpl_money_recharge` varchar(255) DEFAULT NULL;");
    }
}
if(getcustom('sms_temp_money_use')){
    if(!pdo_fieldexists2("ddwx_admin_set_sms","tmpl_money_use_st")){

        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms`	ADD COLUMN `tmpl_money_use_st` tinyint(1) DEFAULT '1' COMMENT '消费余额';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms`	ADD COLUMN `tmpl_money_use` varchar(255) DEFAULT NULL;");
    }
}
if(getcustom('sms_temp_coupon_get')){
    if(!pdo_fieldexists2("ddwx_admin_set_sms","tmpl_coupon_get_st")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms`	ADD COLUMN `tmpl_coupon_get_st` tinyint(1) DEFAULT '1';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms`	ADD COLUMN `tmpl_coupon_get` varchar(255) DEFAULT NULL COMMENT '发券通知';");
    }
}

if(getcustom('fenhong_jiaquan_bylevel')){
    if(!pdo_fieldexists2("ddwx_member_level","fenhong_copies")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` 
        ADD COLUMN `fenhong_copies`  int(11) NULL DEFAULT 0 COMMENT '等级加权分红份数',
        ADD COLUMN `fenhong_zt_copies`  int(11) NULL DEFAULT 0 COMMENT '直推加权分红份数' AFTER `fenhong_copies`;");
    }

    if(!pdo_fieldexists2("ddwx_member","fhcopies")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` 
        ADD COLUMN `fhcopies`  int(11) NULL DEFAULT 0 COMMENT '加权分红份数';");
    }

    if(!pdo_fieldexists2("ddwx_shop_product","fenhong_jq_status")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` 
        ADD COLUMN `fenhong_jq_status`  tinyint(1) NULL DEFAULT 0 COMMENT '是否参与加权分红';");
    }

    if(!pdo_fieldexists2("ddwx_shop_order_goods","fenhong_jq_status")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` 
        ADD COLUMN `fenhong_jq_status`  tinyint(1) NULL DEFAULT 0 COMMENT '是否参与加权分红';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","fenhong_jqjs_time")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
	ADD COLUMN `fenhong_jqjs_rate` decimal(6,2) NULL DEFAULT '0',
    ADD COLUMN `fenhong_jqjs_time` varchar (16) NULL DEFAULT '';");
    }
    if(!pdo_fieldexists2("ddwx_member_fenhonglog","copies")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_fenhonglog` ADD `copies` int(11) DEFAULT '0' COMMENT '分红份数';");
    }

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_fenhong_jiaquan` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) DEFAULT '0',
        `mid` int(11) DEFAULT '0',
        `frommid` int(11) DEFAULT '0',
        `orderid` int(11) DEFAULT '0',
        `ogid` int(11) DEFAULT '0',
        `type` varchar(100) DEFAULT 'shop' COMMENT 'shop 商城',
        `copies` int(11) DEFAULT '0' COMMENT '分红加权份数',
        `remark` varchar(255) DEFAULT NULL,
        `createtime` int(11) DEFAULT NULL,
        `status` tinyint(1) DEFAULT '0' COMMENT '0 创建未生生效 1已生效待结算 2已结算',
        `bid` int(11) DEFAULT '0',
        `effect_time` int(11) DEFAULT NULL COMMENT '生效时间',
        `jiesuan_time` int(11) DEFAULT NULL COMMENT '结算时间',
        PRIMARY KEY (`id`) USING BTREE,
        KEY `aid` (`aid`) USING BTREE,
        KEY `mid` (`mid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='加权分红获得记录';");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_mendian_shop_order` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `totalprice` decimal(10,2) DEFAULT NULL,
      `createtime` int(11) DEFAULT NULL,
      `title` varchar(255) DEFAULT '',
      `remark` varchar(255) DEFAULT '',
      `addid` int(11) DEFAULT '0',
      `date` varchar(255) DEFAULT '',
      `status` tinyint(1) DEFAULT '0' COMMENT '0 未结算 1已结算',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='门店收款';");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_fenhong_jiaquan_log` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT '0',
      `createtime` int(11) DEFAULT NULL,
      `date` varchar(32) DEFAULT '',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='分红执行记录';");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_fhcopies_log` (
       `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT NULL,
      `copies` int(11) DEFAULT '0',
      `after` int(11) DEFAULT NULL,
      `createtime` int(11) DEFAULT NULL,
      `remark` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
      `mid` int(11) DEFAULT '0',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE,
      KEY `mid` (`mid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='分红份数记录';");
}

if(getcustom('extend_qrcode_variable')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_qrcode_variable` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT '0',
  `bid` int(11) DEFAULT NULL,
  `name` varchar(50) NOT NULL DEFAULT '',
  `formurl` varchar(255) DEFAULT '' COMMENT '链接地址',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `createtime` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='活码表';");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_qrcode_list_variable` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT '0',
  `qid` int(11) NOT NULL DEFAULT '0' COMMENT '活码表id',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '分销者id',
  `qrtype` tinyint(1) NOT NULL DEFAULT '0' COMMENT '类型 1、二维码 2、条形码 3、H5二维码 4、小程序码',
  `qrcode` varchar(255) NOT NULL DEFAULT '' COMMENT '小程序码',
  `code` varchar(30) NOT NULL DEFAULT '' COMMENT 'code',
  `path` varchar(255) DEFAULT NULL,
  `tourl` varchar(255) DEFAULT NULL,
  `bindstatus` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否绑定分销商 0：否 1：是',
  `bindtime` int(11) NOT NULL DEFAULT '0' COMMENT '绑定时间',
  `createtime` int(11) NOT NULL DEFAULT '0',
  `updatetime` int(11) NOT NULL DEFAULT '0',
  `shownum` bigint(11) DEFAULT '0',
  `bindstatus_business` tinyint(1) NULL DEFAULT '0' COMMENT '是否绑定多商户 0：否 1：是',
  `param_bid` int(11) NULL DEFAULT '0' COMMENT '参数',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `aid_2` (`aid`,`code`),
  KEY `aid` (`aid`) USING BTREE,
  KEY `qid` (`qid`) USING BTREE,
  KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='活码二维码列表';");
    if(!pdo_fieldexists2("ddwx_qrcode_list_variable", "bindstatus_business")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_qrcode_list_variable` 
ADD COLUMN `bindstatus_business` tinyint(1) NULL DEFAULT 0 COMMENT '是否绑定多商户 0：否 1：是',
ADD COLUMN `param_bid` int(11) NULL DEFAULT 0 COMMENT '参数' AFTER `bindstatus_business`;");
    }
}

if(getcustom('coupon_not_used_discount')){
    if(!pdo_fieldexists2("ddwx_shop_sysset","coupon_not_used_discount")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD   `coupon_not_used_discount` tinyint(1) DEFAULT '0' COMMENT '优惠不同享';");
    }
    if(!pdo_fieldexists2("ddwx_coupon","not_used_discount")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` ADD  `not_used_discount` tinyint(1) DEFAULT '0' COMMENT '优惠不同享';");
    }
}
if(getcustom('fenxiao_manage')){
    if(!pdo_fieldexists2("ddwx_admin_set","fenxiao_manage_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD   `fenxiao_manage_status` tinyint(1) DEFAULT '0' COMMENT '分销推广级差奖 0关闭 1开启';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_fenxiao_manage` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) NOT NULL DEFAULT '1',
      `key` varchar(255) NOT NULL DEFAULT '' COMMENT '唯一标识',
      `levelid` int(11) NOT NULL DEFAULT '0' COMMENT '会员级别id',
      `down_levelid` int(11) NOT NULL DEFAULT '0' COMMENT '下级会员级别id',
      `commission1` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '一级佣金比例',
      `commission2` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '二级佣金比例',
      `commission3` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '三级佣金比例',
      `createtime` int(10) NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `key` (`key`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;");
}

if(getcustom('scoreshop_to_money')){
    if(!pdo_fieldexists2("ddwx_scoreshop_product","give_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` ADD `give_money` decimal(11,2) DEFAULT '0.00';");
    }
    if(!pdo_fieldexists2("ddwx_scoreshop_order_goods", "type")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order_goods`  ADD COLUMN `type` tinyint(1) DEFAULT '0' COMMENT '0:商城模式 2：兑换余额';");
    }
    if(!pdo_fieldexists2("ddwx_scoreshop_product", "type")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product`  ADD COLUMN `type` tinyint(1) DEFAULT '0' COMMENT '0商城模式 2：兑换余额';");
    }
    if(!pdo_fieldexists2("ddwx_scoreshop_order", "type")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order`  ADD COLUMN `send_remark` varchar(255) DEFAULT NULL COMMENT '发放日志';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order`  ADD COLUMN `type` tinyint(1) DEFAULT '0' COMMENT '0商城模式  1兑换红包';");
    }
    if(!pdo_fieldexists2("ddwx_scoreshop_order", "send_remark")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order`  ADD COLUMN `send_remark` varchar(255) DEFAULT NULL COMMENT '发放日志';");
    }
}
if(getcustom('form_give_money')){
    if(!pdo_fieldexists2("ddwx_form","give_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD `give_money` decimal(11,2) DEFAULT '0.00';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD `give_score` int(11) DEFAULT '0';");
    }
}

if(getcustom('mendian_list')){
    if(!pdo_fieldexists2("ddwx_mendian","cid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD `cid` int(11) DEFAULT '0';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_mendian_category` (
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
      KEY `pid` (`pid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
}

if(getcustom('restaurant_tag_wifiprint')){
    if(!pdo_fieldexists2("ddwx_restaurant_area","tag_data")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_area` ADD COLUMN  `tag_data` text COMMENT '标签模板数据';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_area` ADD COLUMN  `tag_width` int(11) DEFAULT '0' COMMENT '标签宽';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_area` ADD COLUMN  `tag_height` int(11) DEFAULT '0' COMMENT '标签高';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_area","tag_print_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_area` ADD COLUMN  `tag_print_status` tinyint(1) DEFAULT '1' COMMENT '开启标签打印';");
    }
}
if(getcustom('business_teamfenhong')){
    if(!pdo_fieldexists2("ddwx_member_level","business_teamfenhonglv")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `business_teamfenhonglv` decimal(11,2) DEFAULT '0.00' COMMENT '商家团队分红级数';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","business_teamfenhongbl")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `business_teamfenhongbl` decimal(11,2) DEFAULT '0.00' COMMENT '商家团队分红比例';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","business_teamfenhong_show")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD `business_teamfenhong_show` tinyint(1) DEFAULT '0' COMMENT '佣金页面是否显示商家团队分红 0不显示1显示';");
    }
    if(!pdo_fieldexists2("ddwx_maidan_order","isfenhong")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_maidan_order` ADD COLUMN `isfenhong` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否分红 0：否 1：是';");
    }
}
if(getcustom('admin_login_page')){
    if(!pdo_fieldexists2("ddwx_admin","login_page_code")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD `login_page_code` varchar(60) DEFAULT NULL COMMENT '子用户单独登录编码参数',ADD INDEX `login_page_code`(`login_page_code`);");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","webinfo")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD  `webinfo` text COMMENT '子用户系统信息';");
    }
}

if(getcustom('one_buy_not_send')){
    if(!pdo_fieldexists2("ddwx_restaurant_product","one_buy_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product` ADD `one_buy_status` tinyint(1) DEFAULT '0' COMMENT '单点不送';");
    }
}
if(getcustom('restaurant_table_after_pay_clean')){
    if(!pdo_fieldexists2("ddwx_restaurant_table","auto_clean")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_table` ADD `auto_clean` tinyint(1) DEFAULT '0' COMMENT '付款后清理桌台';");
    }
}

if(getcustom('baoming_xcx')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_baoming_xcx_set` (
       `id` int(11) NOT NULL AUTO_INCREMENT,
	   `aid` int(11) DEFAULT NULL,
	   `bid` int(11) DEFAULT '0',
	   `content` text,
	   `price` decimal(10,2) DEFAULT NULL,
	   `name` varchar(255) DEFAULT NULL,
	   `poster_bg` varchar(255) DEFAULT NULL,
	   `poster_data` text,
	   `createtime` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  UNIQUE KEY `aid` (`aid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_baoming_xcx_order` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) DEFAULT NULL,
		  `bid` int(11) DEFAULT '0',
		  `mid` int(11) DEFAULT '0',
		  `realname` varchar(255) DEFAULT NULL,
		  `icode` varchar(255) DEFAULT NULL,
		  `sex` int(1) DEFAULT NULL,
		  `birthday` varchar(255) DEFAULT NULL,
		  `minzu` tinyint(1) DEFAULT NULL COMMENT '民族',
		  `hunyin` tinyint(1) DEFAULT NULL COMMENT '婚姻状况',
		  `zhengzhimianmao` tinyint(1) DEFAULT '0' COMMENT '政治面貌',
		  `jiguan` varchar(255) DEFAULT NULL COMMENT '籍贯',
		  `hujidi` varchar(255) DEFAULT NULL COMMENT '户籍地',
		  `sydi` varchar(255) DEFAULT NULL COMMENT '出生地',
		  `zhengjian` varchar(255) DEFAULT NULL,
		  `tel` varchar(255) DEFAULT NULL,
		  `jjtel` varchar(255) DEFAULT NULL,
		  `email` varchar(255) DEFAULT NULL,
		  `txaddress` varchar(255) DEFAULT NULL,
		  `qq` varchar(255) DEFAULT NULL,
		  `weixin` varchar(255) DEFAULT NULL,
		  `xueli` tinyint(1) DEFAULT NULL,
		  `xuewei` tinyint(4) DEFAULT NULL,
		  `biyeschool` varchar(255) DEFAULT NULL,
		  `biyedate` varchar(100) DEFAULT NULL,
		  `zhuanye` varchar(255) DEFAULT NULL,
		  `jyxingshi` tinyint(4) DEFAULT NULL,
		  `en_level` varchar(255) DEFAULT NULL,
		  `pc_level` varchar(255) DEFAULT NULL,
		  `shengao` varchar(255) DEFAULT NULL,
		  `weight` varchar(255) DEFAULT NULL,
		  `left_vision` varchar(255) DEFAULT NULL,
		  `right_vision` varchar(255) DEFAULT NULL,
		  `left_jzvision` varchar(255) DEFAULT NULL,
		  `right_jzvision` varchar(255) DEFAULT NULL,
		  `left_hearing` varchar(255) DEFAULT NULL,
		  `right_hearing` varchar(255) DEFAULT NULL,
		  `bianseli` int(11) DEFAULT '0',
		  `benrenshenfen` varchar(255) DEFAULT NULL,
		  `addtiaojian` varchar(255) DEFAULT NULL,
		  `zhunyejishu` varchar(255) DEFAULT NULL,
		  `zhiyezige` varchar(255) NOT NULL,
		  `tctuchu` varchar(255) DEFAULT NULL,
		  `jiangchengqk` varchar(255) DEFAULT NULL,
		  `jiatinglist` text,
		  `xuexilist` text,
		  `worklist` text,
		  `hukoubo` varchar(255) DEFAULT NULL,
		  `shenfenzheng` varchar(255) DEFAULT NULL,
		  `biyezheng` varchar(255) DEFAULT NULL,
		  `tuiwuzheng` varchar(255) DEFAULT NULL,
		  `xuejibaogao` varchar(255) DEFAULT NULL,
		  `other` varchar(255) DEFAULT NULL,
		  `createtime` int(11) DEFAULT NULL,
		  `status` tinyint(1) DEFAULT '0' COMMENT '0 待审核 1 已通过 2未通过',
		  `paystatus` tinyint(1) DEFAULT '0' COMMENT '0 未支付 1已支付',
		  `paytime` int(11) DEFAULT NULL,
		  `payordernum` varchar(255) DEFAULT NULL,
		  `ordernum` varchar(255) DEFAULT NULL,
		  `kaoqu` varchar(255) DEFAULT NULL COMMENT '考区',
		  `bmxuhao` int(11) DEFAULT NULL COMMENT '包名序号',
		  `bmzhuanye` varchar(255) DEFAULT NULL,
		  `kaoshiaddress` varchar(255) DEFAULT NULL COMMENT '考试地点',
		  `kaoshikemu` varchar(255) DEFAULT NULL COMMENT '考试科目',
		  `kaoshidate` datetime DEFAULT NULL,
		  `kaoshitime` varchar(255) DEFAULT NULL,
		  `kaochangnum` varchar(255) DEFAULT NULL,
		  `zuoweihao` int(11) DEFAULT NULL,
		  `zhunkaozhengnum` varchar(255) DEFAULT NULL COMMENT '准考证 号',
		  `bmid` int(11) DEFAULT '0' COMMENT '哪个活动的id',
		  `money` float(11,2) DEFAULT '0.00',
		  `title` varchar(255) DEFAULT NULL,
		  `payorderid` int(11) DEFAULT '0',
		  `checkreason` varchar(255) DEFAULT NULL,
		  `paytype` varchar(255) DEFAULT '',
		  `poster` varchar(255) DEFAULT NULL,
		  `paytypeid` int(11) DEFAULT NULL,
		  `paynum` varchar(255) DEFAULT NULL,
		  `platform` varchar(255) DEFAULT NULL,
		  `dengjipic` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_baoming_xcx_dengjiset` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) DEFAULT NULL,
		  `bid` int(11) DEFAULT '0',
		  `name` varchar(255) DEFAULT NULL,
		  `poster_bg` varchar(255) DEFAULT NULL,
		  `poster_data` text,
		  `createtime` int(11) DEFAULT NULL,
		  `bmid` int(11) DEFAULT '0',
		  `content` text,
	  PRIMARY KEY (`id`) USING BTREE,
	  UNIQUE KEY `aid` (`aid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
}

if(getcustom('restaurant_queue_print')){
    if(!pdo_fieldexists2("ddwx_restaurant_queue_sysset","print_ids")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_queue_sysset` ADD `print_ids` varchar(255) DEFAULT NULL COMMENT '排队打印机';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_queue_category","is_show_screen")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_queue_category` ADD `is_show_screen` tinyint(1) DEFAULT '0' COMMENT '大屏幕显示';");
    }
}
if(getcustom('product_handwork')){
    if(!pdo_fieldexists2("ddwx_shop_sysset","hwname")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `hwname` varchar(50) NOT NULL DEFAULT '' COMMENT '手工活协议名称';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `hwcontent` text NULL COMMENT '手工活协议内容';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `autoreturn_hwmoney` int(11) NOT NULL DEFAULT 0 COMMENT '手工活自动返款';");

        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_guige` ADD COLUMN `hand_fee` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '手工费';");

        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `protype` tinyint(1) NOT NULL DEFAULT 0 COMMENT '商品类型';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `hand_fee` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '手工费';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `hand_num` int NOT NULL DEFAULT 0 COMMENT '手工活返回数量';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `hand_allmoney` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '已回寄返款金额';");

        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `ishand` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否是手工活 0：否 1：是';");
    }
    if(!pdo_fieldexists2("ddwx_shop_sysset","autoreturn_hwtime")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `autoreturn_hwtime` int(11) NOT NULL DEFAULT 7 COMMENT '手工活回寄时间限制';");
    }
    if(!pdo_fieldexists2("ddwx_shop_sysset","hand_fee_to_score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `hand_fee_to_score` int(11) NOT NULL DEFAULT 0 COMMENT '手工费金额百分比到积分';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shop_hand_order` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NULL DEFAULT NULL,
		`bid` int(11) NULL DEFAULT 0,
		`mdid` int(11) NULL DEFAULT NULL,
		`mid` int(11) NULL DEFAULT NULL,
		`hand_ordernum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`orderid` int(11) NULL DEFAULT 0,
		`ordernum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`title` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
		`createtime` int(11) NULL DEFAULT NULL,
		`status` int(11) NULL DEFAULT 0 COMMENT '0：待验货 1：已验货',
		`checktime` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '验货时间',
		`express_com` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '快递公司',
		`express_no` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '快递单号',
		`express_pic` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '快件图片',
		`hand_checkremark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`hand_pics` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
		`platform` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'wx',
		`delete` tinyint(1) NULL DEFAULT 0,
		`aftersale_id` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`hand_name` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
		`hand_tel` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
		`hand_province` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
		`hand_city` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
		`hand_area` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
		`hand_address` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
		`hand_id` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
		`bname` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '商家名称',
		`btel` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '商家电话',
		`bprovince` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`bcity` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`bdistrict` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`baddress` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`issend` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否发放返款',
		`sendtime` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '发放时间',
		`totalmoney` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '返款金额',
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `aid`(`aid`) USING BTREE,
		INDEX `bid`(`bid`) USING BTREE,
		INDEX `mid`(`mid`) USING BTREE,
		INDEX `status`(`status`) USING BTREE,
		INDEX `createtime`(`createtime`) USING BTREE,
		INDEX `orderid`(`orderid`) USING BTREE,
		INDEX `hand_ordernum`(`hand_ordernum`) USING BTREE,
		INDEX `ordernum`(`ordernum`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shop_hand_order_goods` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`aid` int(11) NULL DEFAULT NULL,
		`bid` int(11) NULL DEFAULT 0,
		`mid` int(11) NULL DEFAULT NULL,
		`orderid` int(11) NULL DEFAULT NULL,
		`ordernum` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`ogid` int(11) NOT NULL,
		`proid` int(11) NULL DEFAULT NULL,
		`name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`pic` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`procode` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`ggid` int(11) NULL DEFAULT NULL,
		`ggname` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`cid` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '0',
		`cost_price` decimal(11, 2) NULL DEFAULT NULL,
		`sell_price` decimal(11, 2) NULL DEFAULT NULL,
		`createtime` int(11) NULL DEFAULT NULL,
		`hand_orderid` int(11) NULL DEFAULT NULL,
		`hand_ordernum` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`hand_num` int(11) UNSIGNED NOT NULL DEFAULT 0,
		`hand_fee` decimal(10, 2) NOT NULL DEFAULT 0.00,
		`hand_money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '返款金额',
		`fbpics` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '反馈图片',
		`fbremark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '反馈信息',
		`ispassnum` int(11) NOT NULL DEFAULT 0 COMMENT '合格数量',
		`nopassnum` int(11) NOT NULL DEFAULT 0 COMMENT '不合格数量',
		PRIMARY KEY (`id`) USING BTREE,
		INDEX `aid`(`aid`) USING BTREE,
		INDEX `bid`(`bid`) USING BTREE,
		INDEX `mid`(`mid`) USING BTREE,
		INDEX `hand_orderid`(`hand_orderid`) USING BTREE,
		INDEX `hand_ordernum`(`hand_ordernum`) USING BTREE,
		INDEX `orderid`(`orderid`) USING BTREE,
		INDEX `ordernum`(`ordernum`) USING BTREE,
		INDEX `proid`(`proid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    if(!pdo_fieldexists2("ddwx_shop_product","hand_fee")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `hand_fee` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '手工费';");
    }
    if(!pdo_fieldexists2("ddwx_shop_hand_order","express_content")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_hand_order` ADD COLUMN `express_content` text NULL COMMENT '快递数据' AFTER `express_pic`;");
    }
    if(!pdo_fieldexists2("ddwx_shop_hand_order","issign")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_hand_order` 
			ADD COLUMN `issign` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否签收 0 否 1 是' AFTER `totalmoney`,
			ADD COLUMN `signtime` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '签收时间' AFTER `issign`;");
    }
}
if(getcustom('up_fxorder_condition_new')){
    if(!pdo_fieldexists2("ddwx_member_level","up_fxdowncount_new")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_fxdowncount_new`  int(11) NULL DEFAULT 0 COMMENT '下级总人数';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_fxdownlevelnum_new")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_fxdownlevelnum_new`  int(11) NULL DEFAULT 0 COMMENT '下级级数';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_fxdownlevelid_new")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_fxdownlevelid_new`  int(11) NULL DEFAULT 0 COMMENT '下级等级ID';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_fxorder_condition_new")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_fxorder_condition_new`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '条件逻辑关系 or and';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_fxdowncount2_new")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_fxdowncount2_new`  int(11) NULL DEFAULT 0 COMMENT '下级总人数';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_fxdownlevelnum2_new")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_fxdownlevelnum2_new`  int(11) NULL DEFAULT 0 COMMENT '下级级数';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_fxdownlevelid2_new")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_fxdownlevelid2_new`  int(11) NULL DEFAULT 0 COMMENT '下级等级ID';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_fxorder_condition2_new")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_fxorder_condition2_new`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '条件逻辑关系 or and';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_fxdowncount3_new")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_fxdowncount3_new`  int(11) NULL DEFAULT 0 COMMENT '下级总人数';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_fxdownlevelnum3_new")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_fxdownlevelnum3_new`  int(11) NULL DEFAULT 0 COMMENT '下级级数';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_fxdownlevelid3_new")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_fxdownlevelid3_new`  int(11) NULL DEFAULT 0 COMMENT '下级等级ID';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_fxorder_condition3_new")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_fxorder_condition3_new`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '条件逻辑关系 or and';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_fxdowncount4_new")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_fxdowncount4_new`  int(11) NULL DEFAULT 0 COMMENT '下级总人数';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_fxdownlevelnum4_new")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_fxdownlevelnum4_new`  int(11) NULL DEFAULT 0 COMMENT '下级级数';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_fxdownlevelid4_new")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_fxdownlevelid4_new`  int(11) NULL DEFAULT 0 COMMENT '下级等级ID';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_fxorder_condition4_new")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_fxorder_condition4_new`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '条件逻辑关系 or and';");
    }
}
if(getcustom('commission_parent_pj_stop_product')){
    if(!pdo_fieldexists2("ddwx_shop_product","commission_parent_pj_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `commission_parent_pj_status`  tinyint(1) NULL DEFAULT 0 COMMENT '平级奖开关 0按会员等级 1单独设置 -1关闭平级奖';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","commission_parent_pj_lv")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `commission_parent_pj_lv`  int(11) NULL DEFAULT 0 COMMENT '平级级数';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","commission_parent_pj")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `commission_parent_pj`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '平级奖固定金额';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","commission_parent_pj_order")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `commission_parent_pj_order`  int(11) NULL DEFAULT 0 COMMENT '平级奖支付金额比例';");
    }
}
if(getcustom('member_levelup_parentcommission')){
    if(!pdo_fieldexists2("ddwx_member_level","levelup_parentcommission")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `levelup_parentcommission` varchar(255) DEFAULT NULL COMMENT '会员升级奖励不同会员等级的推荐佣金';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","levelup_parent_jicha")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `levelup_parent_jicha` tinyint(1) NOT NULL DEFAULT 0 COMMENT '升级奖励上级开启级差';");
    }
}
if(getcustom('member_levelup_auth')){
    if(!pdo_fieldexists2("ddwx_member_level","give_level_totalmoney")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `give_level_totalmoney` decimal(10,2) DEFAULT 0 COMMENT '转赠会员等级的总额度';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","saletj")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `saletj` varchar(100) DEFAULT NULL COMMENT '可转增或售卖的等级';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_salelevel_order` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) DEFAULT NULL,
		  `mid` int(11) DEFAULT NULL,
		  `levelid` int(11) DEFAULT '0' COMMENT '升级后的levelid',
		  `ordernum` varchar(100) DEFAULT NULL,
		  `totalprice` decimal(11,2) DEFAULT NULL,
		  `title` varchar(255) DEFAULT NULL,
		  `status` int(1) DEFAULT '0' COMMENT '0未支付，1领取成功',
		  `createtime` int(11) DEFAULT NULL,
		  `levelup_time` int(11) DEFAULT NULL,
		  `payorderid` int(11) DEFAULT NULL,
		  `paytypeid` int(11) DEFAULT NULL,
		  `paynum` varchar(100) DEFAULT NULL,
		  `paytype` varchar(100) DEFAULT NULL,
		  `paytime` int(11) DEFAULT NULL,
		  `beforelevelid` int(11) DEFAULT NULL,
		  `areafenhong_province` varchar(255) DEFAULT NULL,
		  `areafenhong_city` varchar(255) DEFAULT NULL,
		  `areafenhong_area` varchar(255) DEFAULT NULL,
		  `areafenhong_largearea` varchar(255) DEFAULT NULL,
		  `platform` varchar(100) DEFAULT NULL,
		  `from_mid` int(11) DEFAULT NULL,
		  `ordertype` int(11) DEFAULT '0' COMMENT '1为转赠 2为售卖',
		  `levelprice` decimal(10,2) DEFAULT '0.00' COMMENT '等级价格',
		  PRIMARY KEY (`id`) USING BTREE,
		  KEY `aid` (`aid`) USING BTREE,
		  KEY `mid` (`mid`) USING BTREE,
		  KEY `levelid` (`levelid`) USING BTREE,
		  KEY `levelup_time` (`levelup_time`),
		  KEY `status` (`status`)
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    if(!pdo_fieldexists2("ddwx_member","salelevel_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `salelevel_money` decimal(10,2) DEFAULT 0 COMMENT '转赠或售卖会员等级的额度';");
    }
}
if(getcustom('up_level_teamorder')){
    if(!pdo_fieldexists2("ddwx_member_level","up_teamorder_condition")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_teamorder_condition`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '团队订单升级条件逻辑关系';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_teamorder_num")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_teamorder_num`  int(11) NULL DEFAULT 0 COMMENT '团队订单数量';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_teamorder_lv")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_teamorder_lv`  int(11) NULL DEFAULT 0 COMMENT '团队订单统计级数';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_teamorder_levelid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_teamorder_levelid`  int(11) NULL DEFAULT 0 COMMENT '团队订单统计会员级别';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_teamorder_small_condition")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_teamorder_small_condition`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '团队订单升级条件逻辑关系';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_teamorder_small_num")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_teamorder_small_num`  int(11) NULL DEFAULT 0 COMMENT '团队小区订单数量';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_teamorder_small_lv")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_teamorder_small_lv`  int(11) NULL DEFAULT 0 COMMENT '团队小区订单统计级数';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","up_teamorder_small_levelid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_teamorder_small_levelid`  int(11) NULL DEFAULT 0 COMMENT '团队小区订单统计级别';");
    }
}

if(getcustom('restaurant_shop_cashdesk')){
    if(!pdo_fieldexists2("ddwx_restaurant_cashdesk","jiaoban_print_ids")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_cashdesk` ADD `jiaoban_print_ids` varchar(255) DEFAULT NULL COMMENT '交班打印机';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_cashdesk","member_login_alert")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_cashdesk` ADD `member_login_alert` tinyint(1) DEFAULT '1' COMMENT '登陆会员弹窗';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_cashdesk","default_select_pay")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_cashdesk` ADD COLUMN `default_select_pay` tinyint(1) DEFAULT '1' COMMENT '默认选中收款方式';");
    }
}
if(getcustom('member_levelup_givecoupon')){
    if(!pdo_fieldexists2("ddwx_member_level","givecoupondata")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `givecoupondata` varchar(500) DEFAULT NULL COMMENT '会员升级奖励周期优惠券';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_give_coupon_log` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `mid` int(11) DEFAULT NULL,
	  `couponid` int(11) DEFAULT '0' COMMENT '升级后的levelid',
	  `beginzstime` int(11) DEFAULT NULL COMMENT '开始赠送时间',
	  `coupon_num` int(11) DEFAULT NULL,
	  `cycle_type` int(11) DEFAULT NULL,
	  `status` int(1) DEFAULT '0' COMMENT '0未赠送 1 已赠送',
	  `createtime` int(11) DEFAULT NULL,
	  `zstime` int(11) DEFAULT NULL COMMENT '赠送时间',
	  `levelid` int(11) DEFAULT '0',
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE,
	  KEY `levelid` (`levelid`) USING BTREE,
	  KEY `status` (`status`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
}
if(getcustom('extend_chongzhi')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_livepay_item` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) DEFAULT NULL,
        `type` varchar(60) DEFAULT NULL COMMENT '充值项类型',
        `type_name` varchar(255) DEFAULT NULL COMMENT '充值项名称',
        `fixed_amount` varchar(600) DEFAULT NULL COMMENT '固定金额',
        `is_other_amount` tinyint(1) DEFAULT '1' COMMENT '其它金额',
        `min_amount` decimal(11,2) DEFAULT NULL COMMENT '最低金额',
        `max_amount` decimal(11,2) DEFAULT NULL COMMENT '最高金额',
        `sort` tinyint(3) DEFAULT NULL COMMENT '排序',
        `stock` decimal(11,2) DEFAULT NULL COMMENT '库存金额',
        `icon` varchar(255) DEFAULT NULL COMMENT '图标地址',
        `commission_ratio` decimal(11,2) DEFAULT '0.00' COMMENT '提成比例',
        `discount_ratio` decimal(11,2) DEFAULT '100.00' COMMENT '折扣比例',
        `pay_des` text COMMENT '充值说明',
        `pay_agreement` text COMMENT '充值协议',
        `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
        `status` tinyint(1) DEFAULT '1' COMMENT '是否开启预留1开启',
        `is_auto_pay` tinyint(1) DEFAULT '1' COMMENT '是否自动充值1手动2自动',
        `reduce_stock` tinyint(255) DEFAULT '1' COMMENT '1充值金额减库存',
        PRIMARY KEY (`id`),
        KEY `aid` (`aid`),
        KEY `type` (`type`)
      ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='生活缴费的项目';");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_livepay_company` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) DEFAULT NULL,
        `type` varchar(60) DEFAULT NULL COMMENT '所属项目类型',
        `name` varchar(255) DEFAULT NULL COMMENT '公司名称',
        `province` varchar(60) DEFAULT NULL COMMENT '省份',
        `city` varchar(60) DEFAULT NULL COMMENT '市',
        `district` varchar(60) DEFAULT NULL COMMENT '区',
        `channel` varchar(255) DEFAULT NULL COMMENT '充值渠道支付宝，微信，京东，抖音，其他 固定5个',
        `min_discount` varchar(255) DEFAULT NULL COMMENT '最低折扣',
        `sort` int(11) DEFAULT '10' COMMENT '排序越小越在前',
        `status` tinyint(1) DEFAULT '1' COMMENT '1开启0关闭',
        `createtime` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `aid` (`aid`)
      ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='生活缴费的公司';");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_livepay_order` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) DEFAULT NULL,
        `mid` int(11) DEFAULT NULL COMMENT '会员id',
        `admin_uid` int(11) DEFAULT '0' COMMENT '后台客服id',
        `ordernum` varchar(60) DEFAULT NULL COMMENT '订单号',
        `province` varchar(60) DEFAULT NULL COMMENT '省份',
        `city` varchar(60) DEFAULT NULL COMMENT '城市',
        `recharge_name` varchar(60) DEFAULT NULL COMMENT '姓名',
        `recharge_number` varchar(60) DEFAULT NULL COMMENT '充值户号/手机号',
        `recharge_img` varchar(255) DEFAULT NULL COMMENT '充值截图',
        `recharge_remarks` varchar(255) DEFAULT NULL COMMENT '充值备注',
        `recharge_time` int(11) DEFAULT NULL COMMENT '充值时间',
        `type` varchar(255) DEFAULT NULL COMMENT '充值项目',
        `type_name` varchar(255) DEFAULT NULL COMMENT '充值项目名称',
        `company_id` int(11) DEFAULT NULL COMMENT '充值公司id手机充值是公司名称',
        `company` varchar(255) DEFAULT NULL COMMENT '充值公司',
        `channel` varchar(255) DEFAULT NULL COMMENT '可用充值渠道',
        `pay_money` decimal(11,2) DEFAULT NULL COMMENT '充值金额',
        `totalprice` decimal(11,2) DEFAULT NULL COMMENT '实际支付金额',
        `status` tinyint(255) DEFAULT '0' COMMENT '0未支付;1已支付;2已接单待充值;3已完成已充值;4已关闭;5已退款;',
        `mid_from` tinyint(3) DEFAULT '0' COMMENT '0直客1分销客户',
        `payorderid` int(11) DEFAULT NULL COMMENT '支付订单id',
        `paytypeid` int(11) DEFAULT NULL COMMENT '支付类型id',
        `paytype` varchar(50) DEFAULT NULL COMMENT '支付类型',
        `paynum` varchar(255) DEFAULT NULL COMMENT '支付单号',
        `paytime` int(11) DEFAULT NULL COMMENT '支付时间',
        `parent1` int(11) DEFAULT NULL,
        `parent2` int(11) DEFAULT NULL,
        `parent3` int(11) DEFAULT NULL,
        `parent1commission` decimal(11,2) DEFAULT '0.00',
        `parent2commission` decimal(11,2) DEFAULT '0.00',
        `parent3commission` decimal(11,2) DEFAULT '0.00',
        `uid_commission_ratio` decimal(11,2) DEFAULT NULL COMMENT '提成比例',
        `uid_commission` decimal(11,2) DEFAULT NULL COMMENT '提成金额',
        `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
        `platform` varchar(255) DEFAULT NULL COMMENT '订单来源',
        `refund_checkremarks` varchar(255) DEFAULT NULL COMMENT '退款备注',
        PRIMARY KEY (`id`),
        KEY `aid` (`aid`),
        KEY `mid` (`mid`),
        KEY `admin_uid` (`admin_uid`),
        KEY `ordernum` (`ordernum`),
        KEY `type` (`type`),
        KEY `recharge_number` (`recharge_number`)
      ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='生活缴费订单';");
}

if(getcustom('pay_huifu')){
    if(!pdo_fieldexists2("ddwx_admin_setapp_h5","huifu_sys_id")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_h5` 
            ADD COLUMN `huifu_sys_id` varchar(60) NULL,
            ADD COLUMN `huifu_product_id` varchar(60) NULL,
            ADD COLUMN `huifu_public_key` text NULL,
            ADD COLUMN `huifu_merch_public_key` text NULL,
            ADD COLUMN `huifu_merch_private_key` text NULL;");
    }
    if(!pdo_fieldexists2("ddwx_admin_setapp_wx","huifu_sys_id")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_wx` 
            ADD COLUMN `huifu_sys_id` varchar(60) NULL,
            ADD COLUMN `huifu_product_id` varchar(60) NULL,
            ADD COLUMN `huifu_public_key` text NULL,
            ADD COLUMN `huifu_merch_public_key` text NULL,
            ADD COLUMN `huifu_merch_private_key` text NULL;");
    }
    if(!pdo_fieldexists2("ddwx_admin_setapp_mp","huifu_sys_id")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_mp` 
            ADD COLUMN `huifu_sys_id` varchar(60) NULL,
            ADD COLUMN `huifu_product_id` varchar(60) NULL,
            ADD COLUMN `huifu_public_key` text NULL,
            ADD COLUMN `huifu_merch_public_key` text NULL,
            ADD COLUMN `huifu_merch_private_key` text NULL;");
    }
    if(!pdo_fieldexists2("ddwx_admin_setapp_alipay","huifu_sys_id")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_alipay` 
            ADD COLUMN `huifu` tinyint(1) DEFAULT '0' COMMENT '汇付天下斗拱',
            ADD COLUMN `huifu_sys_id` varchar(60) NULL,
            ADD COLUMN `huifu_product_id` varchar(60) NULL,
            ADD COLUMN `huifu_public_key` text NULL,
            ADD COLUMN `huifu_merch_public_key` text NULL,
            ADD COLUMN `huifu_merch_private_key` text NULL;");
    }

    if(!pdo_fieldexists2("ddwx_member","huifu_id")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` 
            ADD COLUMN `huifu_id` varchar(30) DEFAULT NULL,
            ADD COLUMN `huifu_token_no` varchar(30) DEFAULT NULL;");
    }
    if(!pdo_fieldexists2("ddwx_member_withdrawlog","huifu_id")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_withdrawlog` 
            ADD COLUMN `huifu_id` varchar(30) DEFAULT NULL;");
    }
    if(!pdo_fieldexists2("ddwx_member_commission_withdrawlog","huifu_id")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_commission_withdrawlog` 
            ADD COLUMN `huifu_id` varchar(30) DEFAULT NULL;");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","withdraw_huifu")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `withdraw_huifu` tinyint(1) DEFAULT '0' AFTER `withdraw`;");
    }

    if(!pdo_fieldexists2("ddwx_admin_set","withdraw_huifu_cash_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `withdraw_huifu_cash_type` varchar(10) DEFAULT 'T1' COMMENT '取现配置-业务类型 T1 D1' AFTER `withdraw_huifu`;");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","withdraw_huifu_fee_rate")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `withdraw_huifu_fee_rate` decimal(5,2) DEFAULT '0.03' COMMENT '取现配置-提现手续费率' AFTER `withdraw_huifu_cash_type`;");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_huifu_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `mid` int(11) DEFAULT NULL,
  `tablename` varchar(100) DEFAULT NULL,
  `ordernum` varchar(100) DEFAULT NULL,
  `hf_seq_id` varchar(100) DEFAULT NULL,
  `huifu_id` varchar(100) DEFAULT NULL,
  `party_order_id` varchar(100) DEFAULT NULL,
  `req_seq_id` varchar(100) DEFAULT NULL,
  `remark` varchar(100) DEFAULT NULL,
  `req_date` varchar(100) DEFAULT NULL,
  `resp_code` varchar(100) DEFAULT NULL,
  `resp_desc` varchar(100) DEFAULT NULL,
  `trade_type` varchar(100) DEFAULT NULL,
  `pay_info` text CHARACTER SET utf8,
  `trans_amt` decimal(11,2) DEFAULT '0.00',
  `trans_stat` varchar(100) DEFAULT NULL,
  `givescore` int(11) DEFAULT '0',
  `createtime` int(11) DEFAULT NULL,
  `fenzhangmoney` decimal(11,2) DEFAULT '0.00',
  `fenzhangmoney2` decimal(11,2) DEFAULT '0.00',
  `isfenzhang` tinyint(1) DEFAULT '0' COMMENT '0待分账，1已分账，2分账失败，3退款退回，4取消分账',
  `fz_ordernum` varchar(100) DEFAULT NULL,
  `fz_errmsg` varchar(255) DEFAULT NULL,
  `platform` varchar(100) DEFAULT NULL,
  `refund_money` decimal(11,2) DEFAULT '0.00',
  `is_div` varchar(10) DEFAULT NULL COMMENT '是否分账交易,Y: 分账交易， N: 非分账交易',
  `acct_split_bunch` text COMMENT '分账对象',
  `notify_data` text,
  `pay_status` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `ordernum` (`ordernum`) USING BTREE,
  KEY `tablename` (`tablename`) USING BTREE,
  KEY `hf_seq_id` (`hf_seq_id`) USING BTREE,
  KEY `huifu_id` (`huifu_id`) USING BTREE,
  KEY `req_seq_id` (`req_seq_id`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_huifu_bank_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `logid` int(11) DEFAULT NULL COMMENT '提现表id',
  `tablename` varchar(64) DEFAULT NULL,
  `huifu_id` varchar(100) DEFAULT NULL,
  `hf_seq_id` varchar(100) DEFAULT NULL COMMENT '原交易返回的全局流水号',
  `req_date` varchar(100) DEFAULT NULL COMMENT '原交易请求日期',
  `req_seq_id` varchar(100) DEFAULT NULL COMMENT '原交易请求流水号',
  `trans_status` varchar(10) DEFAULT NULL COMMENT '交易状态 S：成功；F：失败；P：处理中',
  `trans_desc` varchar(100) DEFAULT NULL,
  `cash_amt` decimal(11,2) DEFAULT '0.00' COMMENT '金额',
  `fee_amt` decimal(11,2) DEFAULT '0.00' COMMENT '手续费金额',
  `resp_code` varchar(100) DEFAULT NULL,
  `resp_desc` varchar(100) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `logid` (`logid`) USING BTREE,
  KEY `tablename` (`tablename`) USING BTREE,
  KEY `hf_seq_id` (`hf_seq_id`) USING BTREE,
  KEY `req_seq_id` (`req_seq_id`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `trans_status` (`trans_status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_huifu_refund_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `logid` int(11) DEFAULT NULL COMMENT 'huifu_log.id',
  `huifu_id` varchar(100) DEFAULT NULL,
  `req_seq_id` varchar(100) DEFAULT NULL COMMENT '请求流水号',
  `req_date` varchar(100) DEFAULT NULL COMMENT '原交易请求日期',
  `org_req_date` varchar(100) DEFAULT NULL COMMENT '原交易请求日期',
  `org_hf_seq_id` varchar(100) DEFAULT NULL COMMENT '原交易全局流水号',
  `ord_amt` decimal(11,2) DEFAULT '0.00' COMMENT '金额',
  `trans_stat` varchar(10) DEFAULT NULL COMMENT '交易状态 S：成功；F：失败；P：处理中',
  `resp_code` varchar(100) DEFAULT NULL,
  `resp_desc` varchar(100) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
	`req_data` text,
	`resp_data` text,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `logid` (`logid`) USING BTREE,
  KEY `huifu_id` (`huifu_id`) USING BTREE,
  KEY `req_seq_id` (`req_seq_id`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `trans_stat` (`trans_stat`) USING BTREE,
  KEY `org_hf_seq_id` (`org_hf_seq_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_huifu_fenzhang` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `huifu_id` varchar(100) DEFAULT NULL,
  `fenzhangdata` text COMMENT '分账接收方',
  `apply_ratio` decimal(5,2) DEFAULT '0.00' COMMENT '最大分账比例，0-100 的数值，支持两位小数',
  `online_busi_type` varchar(100) DEFAULT NULL COMMENT '线上业务类型编码',
  `file_list` text COMMENT '分账材料,间隔',
  `createtime` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  `check_status` varchar(10) DEFAULT NULL COMMENT '审核状态',
  `sort` int(11) DEFAULT '0',    
  `apply_no` varchar(100) DEFAULT NULL COMMENT '申请单编号',
  `respdata` text COMMENT '异步数据',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `huifu_id` (`huifu_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_huifu_moneypay_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `logid` int(11) DEFAULT NULL COMMENT '提现表id',
  `tablename` varchar(64) DEFAULT NULL,
  `huifu_id` varchar(100) DEFAULT NULL,
  `huifu_id_member` varchar(100) DEFAULT NULL,
  `hf_seq_id` varchar(100) DEFAULT NULL COMMENT '原交易返回的全局流水号',
  `req_date` varchar(100) DEFAULT NULL COMMENT '原交易请求日期',
  `req_seq_id` varchar(100) DEFAULT NULL COMMENT '原交易请求流水号',
  `trans_status` varchar(10) DEFAULT NULL COMMENT '交易状态 S：成功；F：失败；P：处理中',
  `ord_amt` decimal(11,2) DEFAULT '0.00' COMMENT '金额',
  `resp_code` varchar(100) DEFAULT NULL,
  `resp_desc` varchar(100) DEFAULT NULL,
  `req_data_json` text,
  `resp_data_json` text,
  `createtime` int(11) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `logid` (`logid`) USING BTREE,
  KEY `tablename` (`tablename`) USING BTREE,
  KEY `hf_seq_id` (`hf_seq_id`) USING BTREE,
  KEY `req_seq_id` (`req_seq_id`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `trans_status` (`trans_status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");

    if(!pdo_fieldexists2("ddwx_cashier","huifupay")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashier` ADD COLUMN `huifupay` tinyint(1) DEFAULT '0' COMMENT '汇付收款';");
    }
    if(!pdo_fieldexists2("ddwx_business_sysset","business_cashdesk_huifupay")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `business_cashdesk_huifupay` tinyint(1) DEFAULT '0' COMMENT '多商户收银台 汇付   0:关闭 1：开启';");
    }
    if(!pdo_fieldexists2('ddwx_huifu_log','is_upload_shipping_info')){
        \think\facade\Db::execute("ALTER TABLE `ddwx_huifu_log` ADD COLUMN `is_upload_shipping_info` tinyint(1) DEFAULT '0' COMMENT '是否录入小程序发货信息';");
    }
}
if(getcustom('restaurant_shop_cashdesk') && getcustom('pay_huifu')){
    if(!pdo_fieldexists2("ddwx_restaurant_admin_set","business_cashdesk_huifupay")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_admin_set` ADD COLUMN `business_cashdesk_huifupay` tinyint(1) DEFAULT '1' COMMENT '多商户收银台 汇付支付   0:关闭 1：开启';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_cashdesk","huifupay")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_cashdesk` ADD COLUMN `huifupay` tinyint(1) DEFAULT '0' COMMENT '汇付付款';");
    }
}
if(getcustom('restaurant_cashdesk_cuxiao')){
    if(!pdo_fieldexists2("ddwx_restaurant_cuxiao","restaurant_cashdesk_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_cuxiao` ADD `restaurant_cashdesk_status` tinyint(1) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_cuxiao` ADD `is_not_share` tinyint(1) DEFAULT '1' COMMENT '不共享，默认不共享';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_shop_order","cuxiao_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order` ADD `cuxiao_money` decimal(10,2) DEFAULT '0.00';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order` ADD `cuxiao_ids` varchar(255) DEFAULT NULL;");
    }
}
if(getcustom('product_memberlevel_limit')){
    if(!pdo_fieldexists2("ddwx_shop_product","levellimitdata")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `levellimitdata` varchar(500) DEFAULT NULL COMMENT '会员等级限购';");
    }
}
if(getcustom('restaurant_scan_qrcode_coupon')){
    if(!pdo_fieldexists2("ddwx_coupon_record","is_scan")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon_record` ADD `is_scan` tinyint(1) DEFAULT '0';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_shop_order","coupon_code")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order` ADD `coupon_code` varchar(255) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order` ADD `qrcode_coupon_money` decimal(10,2) DEFAULT '0.00';");
    }
}
if(getcustom('restaurant_cashdesk_auth_enter')){
    if(!pdo_fieldexists2("ddwx_restaurant_shop_order","direct_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order` ADD `direct_money` decimal(10,2) DEFAULT NULL COMMENT '直接优惠';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order` ADD `direct_auth_uid` int(11) DEFAULT '0' COMMENT '操作优惠打折退款的店长ID';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order` ADD `refund_auth_uid` int(11) DEFAULT '0' COMMENT '确认退款权限的店长ID';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order` ADD `cancel_auth_uid` int(11) DEFAULT '0' COMMENT '确认取消权限的店长ID';");
    }
}

if(getcustom('system_copy')){
    if(!pdo_fieldexists2("ddwx_admin_set", "copyinfo")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `copyinfo` varchar(700) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '剪贴板内容';");
    }
}
if(getcustom('teamfenhong_peiyujiang')){
    if(!pdo_fieldexists2("ddwx_member_level","teamfenhong_peiyujiang_bl")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `teamfenhong_peiyujiang_bl` decimal(10,2) DEFAULT '0.00' COMMENT '培育奖比例';");
    }
}

if(getcustom('restaurant_product_jialiao')){
    if(!pdo_fieldexists2("ddwx_restaurant_product","jl_is_selected")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product` ADD `jl_is_selected` tinyint(1) DEFAULT '0' COMMENT '加料是否必选0不必选 1：必选';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product` ADD `jl_is_discount` tinyint(1) DEFAULT '0' COMMENT '加料是否打折 0：不打折 1：打折';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product` ADD `jl_is_cuxiao` tinyint(1) DEFAULT '0' COMMENT '加料是否参与促销 0：不参与 1：参与';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_restaurant_product_jialiao` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `proid` int(11) DEFAULT '0',
      `title` varchar(255) DEFAULT NULL,
      `price` decimal(10,2) DEFAULT NULL,
      `limit_num` int(11) DEFAULT NULL,
      `createtime` int(11) DEFAULT '0',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    if(!pdo_fieldexists2("ddwx_restaurant_shop_cart","jldata")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_cart` ADD `jldata` text;");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_shop_order_goods","njlprice")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order_goods` ADD `njlprice` decimal(10,2) DEFAULT '0.00';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_shop_order_goods","njltitle")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order_goods` ADD `njltitle` varchar(255) DEFAULT NULL;");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_takeaway_cart","jldata")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_cart` ADD `jldata` text;");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_takeaway_order_goods","njlprice")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_order_goods` ADD `njlprice` decimal(10,2) DEFAULT '0.00';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_takeaway_order_goods","njltitle")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_order_goods` ADD `njltitle` varchar(255) DEFAULT NULL;");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_product","jl_total_limit")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product` ADD `jl_total_limit` int(11) DEFAULT '0' COMMENT '加料总数限制';");
    }

}
if(getcustom('commission_butie')){
    if(!pdo_fieldexists2("ddwx_admin_set","fx_butie_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `fx_butie_type`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '分销补贴类型 0按月 1按周';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","fx_butie_circle")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `fx_butie_circle`  int(11) NOT NULL DEFAULT 0 COMMENT '分销补贴周期';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","fx_butie_send_week")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `fx_butie_send_week`  int(11) NOT NULL DEFAULT 0 COMMENT '每周几发放补贴';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","fx_butie_send_day")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `fx_butie_send_day`  int(11) NOT NULL DEFAULT 0 COMMENT '每月几号发放补贴';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_commission_butie` (
        `id`  int(11) NOT NULL AUTO_INCREMENT ,
        `aid`  int(11) NOT NULL DEFAULT 1 ,
        `mid`  int(11) NOT NULL DEFAULT 0 COMMENT '会员id' ,
        `frommid`  int(11) NULL DEFAULT 0 COMMENT '来源会id' ,
        `orderid`  int(11) NULL DEFAULT 0 COMMENT '订单id' ,
        `ogid`  int(11) NULL DEFAULT 0 COMMENT '订单商品id' ,
        `commission`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '补贴金额' ,
        `have_send`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '已发放补贴' ,
        `remain`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '剩余发放补贴' ,
        `send_circle`  int(11) NULL DEFAULT 0 COMMENT '已发期数' ,
        `fx_butie_type`  int(11) NULL DEFAULT 0 COMMENT '分销补贴类型 0按月 1按周' ,
        `fx_butie_circle`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '0' COMMENT '补贴周期' ,
        `createtime`  int(11) NULL DEFAULT 0 COMMENT '创建时间' ,
        `status`  tinyint(1) NULL DEFAULT 0 COMMENT '1发放完成' ,
        `type`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '订单类型' ,
        `last_send_time`  int(10) NULL DEFAULT 0 COMMENT '上一次发放时间' ,
        `fx_butie_send_week`  int(11) NULL DEFAULT 0 COMMENT '每周发放日期' ,
        `fx_butie_send_day`  int(11) NULL DEFAULT 0 COMMENT '每月发放日期' ,
        `next_send_time`  int(11) NOT NULL DEFAULT 0 COMMENT '下期发放时间' ,
        `record_id`  int(11) NULL DEFAULT 0 COMMENT '佣金记录id' ,
        `remark`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '备注' ,
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_commission_butie_log` (
        `id`  int(11) NOT NULL AUTO_INCREMENT ,
        `aid`  int(11) NOT NULL DEFAULT 0 ,
        `mid`  int(11) NOT NULL DEFAULT 0 ,
        `pid`  int(11) NOT NULL DEFAULT 0 COMMENT '补贴数据id' ,
        `send_num`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '发放数量' ,
        `send_circle`  int(11) NOT NULL DEFAULT 0 COMMENT '发放期数' ,
        `send_time`  int(11) NOT NULL DEFAULT 0 COMMENT '发放时间' ,
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");
    if(!pdo_fieldexists2("ddwx_member_commission_record","butie")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_commission_record` ADD COLUMN `butie`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '分配到补贴的数量';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","commissionbutie")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `commissionbutie`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '分销补贴固定金额';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","commissionbutie2")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `commissionbutie2`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '分销补贴比例';");
    }
}
if(getcustom('zhitui_pj')){
    if(!pdo_fieldexists2("ddwx_shop_product","zhitui_pj")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `zhitui_pj`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '直推平级奖';");
    }
}
if(getcustom('collage_show_mingpian')){
    if(!pdo_fieldexists2("ddwx_collage_sysset","show_mingpian")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_collage_sysset` ADD COLUMN `show_mingpian`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否展示参团成员名片 1开启 0关闭';");
    }
}
if(getcustom('restaurant_the_second_discount')){
    if(!pdo_fieldexists2("ddwx_restaurant_cuxiao","is_one_product")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_cuxiao` ADD `is_one_product` tinyint(1) DEFAULT '0' COMMENT '第二件是否同一产品';");
    }
}
if(getcustom('score_weishu')){
    if(!pdo_fieldexists2("ddwx_admin_set","score_weishu")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `score_weishu`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '积分保留小数位数';");
    }
    if(pdo_fieldexists2("ddwx_member","score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` MODIFY COLUMN `score`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_member","score_withdraw")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` MODIFY COLUMN `score_withdraw` decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_member_scorelog","score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_scorelog` MODIFY COLUMN `score`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_member_scorelog","after")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_scorelog` MODIFY COLUMN `after`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_member_scorelog","used")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_scorelog` MODIFY COLUMN `used`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_scoreshop_product","score_price")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` MODIFY COLUMN `score_price`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_scoreshop_order","totalscore")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order` MODIFY COLUMN `totalscore`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_scoreshop_order_goods","score_price")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order_goods` MODIFY COLUMN `score_price`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_scoreshop_order_goods","totalscore")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order_goods` MODIFY COLUMN `totalscore`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_signset","score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_signset` MODIFY COLUMN `score`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_sign_record","score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_sign_record` MODIFY COLUMN `score`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_payorder","score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_payorder` MODIFY COLUMN `score`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists3('ddwx_commission_toscore_log')){
        if(pdo_fieldexists2("ddwx_commission_toscore_log","commission")) {
            \think\facade\Db::execute("ALTER TABLE `ddwx_commission_toscore_log` MODIFY COLUMN `commission`  decimal(12,3) DEFAULT '0.000';");
        }
        if(pdo_fieldexists2("ddwx_commission_toscore_log","commission_total")) {
            \think\facade\Db::execute("ALTER TABLE `ddwx_commission_toscore_log` MODIFY COLUMN `commission_total`  decimal(12,3) DEFAULT '0.000';");
        }
        if(pdo_fieldexists2("ddwx_commission_toscore_log","num")) {
            \think\facade\Db::execute("ALTER TABLE `ddwx_commission_toscore_log` MODIFY COLUMN `num`  decimal(12,3) DEFAULT '0.000';");
        }
    }
    if(pdo_fieldexists2("ddwx_member_fenhonglog","score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_fenhonglog` MODIFY COLUMN `score`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_shop_product","givescore")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` MODIFY COLUMN `givescore`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_shop_guige","givescore")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_guige` MODIFY COLUMN `givescore`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_shop_order","givescore")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` MODIFY COLUMN `givescore`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_shop_order","scoredkscore")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` MODIFY COLUMN `scoredkscore`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_shop_order","givescore2")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` MODIFY COLUMN `givescore2`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_member_commission_record","score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_commission_record` MODIFY COLUMN `score`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_member_level","up_give_score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` MODIFY COLUMN `up_give_score`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_cashier_order","scoredkscore")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_cashier_order` MODIFY COLUMN `scoredkscore`  decimal(12,3) DEFAULT '0.000';");
    }

    if(pdo_fieldexists2("ddwx_register_giveset","score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_register_giveset` MODIFY COLUMN `score`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_register_giveset","wanshan_score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_register_giveset` MODIFY COLUMN `wanshan_score`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_payaftergive","score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_payaftergive` MODIFY COLUMN `score`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_payaftergive_record","score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_payaftergive_record` MODIFY COLUMN `score`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_collage_product","givescore")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_collage_product` MODIFY COLUMN `givescore`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_collage_product","leaderscore")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_collage_product` MODIFY COLUMN `leaderscore`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_collage_guige","givescore")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_collage_guige` MODIFY COLUMN `givescore`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_collage_order","givescore1")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_collage_order` MODIFY COLUMN `givescore1`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_collage_order","givescore2")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_collage_order` MODIFY COLUMN `givescore2`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_seckill_product","givescore")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_seckill_product` MODIFY COLUMN `givescore`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_seckill_guige","givescore")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_seckill_guige` MODIFY COLUMN `givescore`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_seckill_order","givescore")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_seckill_order` MODIFY COLUMN `givescore`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_choujiang","usescore")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_choujiang` MODIFY COLUMN `usescore`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_lucky_collage_product","leaderscore")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product` MODIFY COLUMN `leaderscore`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_shop_order_goods","parent1score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` MODIFY COLUMN `parent1score`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_shop_order_goods","parent2score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` MODIFY COLUMN `parent2score`  decimal(12,3) DEFAULT '0.000';");
    }
    if(pdo_fieldexists2("ddwx_shop_order_goods","parent3score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` MODIFY COLUMN `parent3score`  decimal(12,3) DEFAULT '0.000';");
    }
	if(pdo_fieldexists3("ddwx_business_score_withdrawlog")){
		\think\facade\Db::execute("ALTER TABLE ddwx_business_score_withdrawlog MODIFY COLUMN `score`  decimal(12,3) DEFAULT '0.000';");
	}
	if(pdo_fieldexists3("ddwx_business_scorelog")){
		\think\facade\Db::execute("ALTER TABLE ddwx_business_scorelog MODIFY COLUMN `score` decimal(12,3) DEFAULT '0.000';");
		\think\facade\Db::execute("ALTER TABLE ddwx_business_scorelog MODIFY COLUMN `after` decimal(12,3) DEFAULT '0.000';");
	}
    if(pdo_fieldexists2("ddwx_business","score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` MODIFY COLUMN `score`  decimal(12,3) DEFAULT '0.000';");
    }
}
if(getcustom('teamfenhong_jicha')){
    if(!pdo_fieldexists2("ddwx_member_level","teamjichabl")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `teamjichabl`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '团队级差分红比例';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","teamjicha_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `teamjicha_money`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '团队级差分红金额';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","teamjicha_pingji_bl")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `teamjicha_pingji_bl`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '团队级差平级比例';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","teamjicha_pingji_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `teamjicha_pingji_money`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '团队级差平级金额';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","teamjicha_pingji_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `teamjicha_pingji_type`  tinyint(1) NULL DEFAULT 0 COMMENT '团队级差平级计算 0按奖励金额 1按订单金额';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","teamjichaset")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `teamjichaset`  tinyint(1) NULL DEFAULT 0 COMMENT '团队级差分红 0按会员等级 1分红比例 2分红金额 -1不参与分红';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","teamjichadata1")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `teamjichadata1`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '团队级差分红比例';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","teamjichadata2")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `teamjichadata2`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '团队级差分红金额';");
    }
    if(pdo_fieldexists2("ddwx_shop_product","teamjichadata1")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` MODIFY COLUMN `teamjichadata1`  text ;");
    }
    if(pdo_fieldexists2("ddwx_shop_product","teamjichadata2")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` MODIFY COLUMN `teamjichadata2`  text ;");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","teamjichapjset")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `teamjichapjset`  tinyint(1) NULL DEFAULT 0 COMMENT '团队级差分红 0按会员等级 1分红比例 2分红金额 -1不参与分红';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","teamjichapjdata1")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `teamjichapjdata1`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '团队级差平级比例';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","teamjichapjdata2")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `teamjichapjdata2`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '团队级差分红金额';");
    }

    if(pdo_fieldexists2("ddwx_shop_product","teamjichapjdata1")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` MODIFY COLUMN `teamjichapjdata1`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '团队级差平级比例';");
    }
    if(pdo_fieldexists2("ddwx_shop_product","teamjichapjdata2")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` MODIFY COLUMN `teamjichapjdata2`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '团队级差分红金额';");
    }
}
if(getcustom('luntan_category_give_coupon')){
    if(!pdo_fieldexists2("ddwx_luntan_category","give_coupon")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_luntan_category` 
		ADD COLUMN `give_coupon` tinyint(1) DEFAULT '0' COMMENT '是否开启赠送优惠券',
		ADD COLUMN `coupon_ids` varchar(255) DEFAULT NULL COMMENT '赠送优惠券id',
		ADD COLUMN `starttime` int(11) DEFAULT NULL COMMENT '活动开始时间',
		ADD COLUMN `endtime` int(11) DEFAULT '0' COMMENT '活动结束时间',
		ADD COLUMN `isphone` tinyint(1) DEFAULT '0' COMMENT '是否填写姓名手机号',
		ADD COLUMN `isshowphone` tinyint(1) DEFAULT '0' COMMENT '是否显示姓名手机号',
		ADD COLUMN `limitnum` int(11) DEFAULT '0' COMMENT '领取次数限制 0 为不限制';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_luntan_give_couponlog` (
       `id` int(11) NOT NULL AUTO_INCREMENT,
	   `aid` int(11) DEFAULT NULL,
	   `mid` int(11) DEFAULT NULL,
	   `createtime` int(11) DEFAULT NULL,
	   `cateid` int(11) DEFAULT '0',
	   `couponid` varchar(100) DEFAULT NULL,
	    PRIMARY KEY (`id`) USING BTREE,
	    KEY `aid` (`aid`) USING BTREE,
	    KEY `mid` (`mid`) USING BTREE
        ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");
}
if(getcustom('luntan_category_phone')){
    if(!pdo_fieldexists2("ddwx_luntan","mobile")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_luntan` ADD COLUMN `mobile` varchar(15) NOT NULL DEFAULT '' COMMENT '电话';");
    }
    if(!pdo_fieldexists2("ddwx_luntan","name")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_luntan` ADD COLUMN `name` varchar(20) NOT NULL DEFAULT '' COMMENT '姓名';");
    }
}
if(getcustom('gdfenhong_level')){
    if(!pdo_fieldexists2("ddwx_member_level","apply_paygudong")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `apply_paygudong`  tinyint(1) NULL DEFAULT 0 COMMENT '升级费用用于股东分红 0否 1是';");
    }
    if(!pdo_fieldexists2("ddwx_member_levelup_order","isfenhong")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_levelup_order` ADD COLUMN `isfenhong`  tinyint(1) NULL DEFAULT 0 COMMENT '是否已分红 0未分红 1已分红';");
    }
}
if(getcustom('business_teamfenhong_pj')){
    if(!pdo_fieldexists2("ddwx_member_level","business_teamfenhonglv_pj")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `business_teamfenhonglv_pj`  int(11) NULL DEFAULT 0 COMMENT '平级奖分红级数';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","business_teamfenhongbl_pj")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `business_teamfenhongbl_pj`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '平级奖分红比例';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","business_teamfenhong_pingji_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `business_teamfenhong_pingji_type`  tinyint(1) NULL DEFAULT 0 COMMENT '计算方式 0按奖励金额 1按分红金额';");
    }
}
if(getcustom('choujiang_hexiao_reward')){
    if(!pdo_fieldexists2("ddwx_choujiang","hexiao_reward_st")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_choujiang` 
		ADD COLUMN `hexiao_reward_st` tinyint(1) DEFAULT '0' COMMENT '0 为关闭1 为开启',
		ADD COLUMN `hexiaogivemoney` float(11,2) DEFAULT '0.00' COMMENT '奖励余额',
		ADD COLUMN `hexiaogivescore` float(11,2) DEFAULT '0.00' COMMENT '奖励积分';");
    }
}

if(getcustom('mendian_maidan_ticheng')){
    if(!pdo_fieldexists2("ddwx_mendian","maidangivepercent")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `maidangivepercent` decimal(10, 2) DEFAULT 0 COMMENT '买单提成比例';");
    }
    if(!pdo_fieldexists2("ddwx_mendian","maidangivemoney")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `maidangivemoney` decimal(10, 2) DEFAULT 0 COMMENT '买单提成金额';");
    }
}
if(getcustom('paycode_bg')){
    if(!pdo_fieldexists2("ddwx_admin_set","paycode_bgpic")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `paycode_bgpic`  varchar(255) NOT NULL DEFAULT '' COMMENT '买单收款码背景图';");
    }
    if(!pdo_fieldexists2("ddwx_mendian","paycodepic")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `paycodepic`  varchar(255) NOT NULL DEFAULT '' COMMENT '买单收款码';");
    }
}
if(getcustom('choujiang_before_info')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_choujiang_memberinfo` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `hid` int(11) DEFAULT NULL COMMENT '大转盘id',
	  `mid` int(11) DEFAULT NULL,
	  `formdata` text,
	  `createtime` int(11) DEFAULT NULL COMMENT '填写时间',
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `hid` (`hid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE
      ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");
}
if(getcustom('yx_share_give')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_share_give` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `name` varchar(255) DEFAULT NULL,
	  `gettj` varchar(255) DEFAULT '-1' COMMENT '适应范围',
	  `limittimes` int(11) DEFAULT '0' COMMENT '每人限制次数',
	  `starttime` int(11) DEFAULT NULL,
	  `endtime` int(11) DEFAULT NULL,
	  `money` float(11,2) DEFAULT NULL,
	  `score` int(11) DEFAULT '0',
	  `choujiangtimes` int(11) DEFAULT NULL,
	  `choujiangid` int(11) DEFAULT NULL,
	  `give_coupon` tinyint(1) DEFAULT '0',
	  `coupon_ids` varchar(255) DEFAULT NULL,
	  `btntext` varchar(255) DEFAULT NULL,
	  `status` tinyint(1) DEFAULT '0',
	  `createtime` int(11) DEFAULT NULL,
	  `sort` int(11) DEFAULT '0',
	  `bid` int(11) DEFAULT '0',
	  `indexurl` varchar(255) DEFAULT NULL,
	  `indexurlname` varchar(255) DEFAULT NULL,
	  `readtimes` int(11) DEFAULT '10' COMMENT '阅读时间限制单位秒',
	  `isanswer` tinyint(1) DEFAULT '0' COMMENT '是否开启答题',
	  `pageid` int(11) DEFAULT NULL,
	  `tiku_ids` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `status` (`status`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `starttime` (`starttime`),
	  KEY `endtime` (`endtime`)
      ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_share_give_answer_record` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `mid` int(11) DEFAULT NULL,
	  `status` int(1) DEFAULT '0' COMMENT '0 未答完  1已答完',
	  `time` int(11) DEFAULT NULL,
	  `timu` text,
	  `endtime` int(11) DEFAULT NULL COMMENT '结束时间',
	  `giveid` int(11) DEFAULT '0',
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `status` (`status`) USING BTREE
      ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_share_give_answer_recordlog` (
       `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `mid` int(11) DEFAULT NULL,
	  `status` int(1) DEFAULT '0' COMMENT '0 未答  1答对了 2 答错了',
	  `time` int(11) DEFAULT NULL,
	  `tmid` int(11) DEFAULT '0' COMMENT '题目id',
	  `answer` varchar(255) DEFAULT NULL,
	  `recordid` int(11) DEFAULT '0' COMMENT 'record id',
	  `sort` int(11) DEFAULT '0' COMMENT '题目序号',
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `status` (`status`) USING BTREE
      ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_share_give_read_log` (
       `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `mid` int(11) DEFAULT NULL,
	  `status` int(1) DEFAULT '0' COMMENT '1已完成',
	  `time` int(11) DEFAULT NULL,
	  `endtime` int(11) DEFAULT NULL COMMENT '时间',
	  `giveid` int(11) DEFAULT '0',
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `status` (`status`) USING BTREE
      ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_share_give_record` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `giveid` int(11) DEFAULT NULL,
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
	  KEY `giveid` (`giveid`)
      ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_share_give_tiku` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `title` varchar(255) DEFAULT NULL,
	  `sort` int(11) DEFAULT '0',
	  `status` int(1) DEFAULT '1',
	  `createtime` int(11) DEFAULT NULL,
	  `type` int(255) DEFAULT '1' COMMENT '1选择题',
	  `right_option` varchar(255) DEFAULT NULL COMMENT '答案',
	  `option_group` text,
	  `jiexi` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `status` (`status`) USING BTREE
      ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");
}
if(getcustom('product_mendian_hexiao_givemoney')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shop_product_mendian_hexiaoset` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `proid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT NULL,
	  `mdid` int(11) DEFAULT NULL,
	  `hexiaogivepercent` decimal(11,2) DEFAULT '0.00',
	  `hexiaogivemoney` decimal(11,2) DEFAULT '0.00',
	  `createtime` int(11) DEFAULT '0',
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `proid` (`proid`) USING BTREE
      ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");
}
if(getcustom('team_leader_fh')){
    if(!pdo_fieldexists2("ddwx_member_level","teamleader_fenhonglv")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `teamleader_fenhonglv`  int(11) NULL DEFAULT 0 COMMENT '团队长分红级数';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","teamleader_fenhongbl")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `teamleader_fenhongbl`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '团队长分红比例';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","teamleader_fenhong_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `teamleader_fenhong_money`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '团队长分红金额';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","teamleader_fenhong_self")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `teamleader_fenhong_self`  tinyint(1) NULL DEFAULT 0 COMMENT '团队长分红包含自己 0否 1是';");
    }

    if(!pdo_fieldexists2("ddwx_shop_product","teamleader_fenhongset")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `teamleader_fenhongset`  tinyint(1) NULL DEFAULT 0 COMMENT '团队长分红 0按等级 1单独比例 2单独金额 -1不参与';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","teamleader_fenhongdata1")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `teamleader_fenhongdata1`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '团队长分红比例';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","teamleader_fenhongdata2")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `teamleader_fenhongdata2`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '团队长分红金额';");
    }
}
if(getcustom('show_price_unlogin')){
    if(!pdo_fieldexists2("ddwx_shop_sysset","is_show_price_unlogin")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `is_show_price_unlogin` tinyint(1) NULL DEFAULT 1 COMMENT '未登录查看价格 0关闭 1开启';");
    }
    if(!pdo_fieldexists2("ddwx_shop_sysset","show_price_unlogin_txt")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `show_price_unlogin_txt` varchar(60) NOT NULL DEFAULT '请先登录' COMMENT '未登录查看价格提示文字';");
    }
}
if(getcustom('show_price_uncheck')){
    if(!pdo_fieldexists2("ddwx_shop_sysset","is_show_price_uncheck")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `is_show_price_uncheck` tinyint(1) NULL DEFAULT 1  COMMENT '未审核查看价格 0关闭 1开启';");
    }
    if(!pdo_fieldexists2("ddwx_shop_sysset","show_price_uncheck_txt")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `show_price_uncheck_txt` varchar(60) NOT NULL DEFAULT '请先认证' COMMENT '未审核查看价格提示文字';");
    }
}

if(getcustom('restaurant_bar_table_order')){
    if(!pdo_fieldexists2("ddwx_restaurant_shop_sysset","bar_table_order")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_sysset` ADD COLUMN `bar_table_order` tinyint(1) DEFAULT '0' COMMENT '吧台点餐';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_shop_sysset","start_pickup_number")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_sysset` ADD COLUMN `start_pickup_number` int(11) DEFAULT '0' COMMENT '取餐号开始号';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_shop_order","is_bar_table_order")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order` ADD COLUMN `is_bar_table_order` tinyint(1) DEFAULT '0' COMMENT '是否吧台订单';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_shop_order","pickup_number")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order` ADD COLUMN `pickup_number` varchar(255) DEFAULT NULL COMMENT '取餐号';");
    }
    if(!pdo_fieldexists2("ddwx_admin_user","cashdesk_mdid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_user` ADD COLUMN `cashdesk_mdid` int(11) DEFAULT '0';");
    }
}
if(getcustom('restaurant_shop_jingmo_auth')){
    if(!pdo_fieldexists2("ddwx_restaurant_admin_set","shop_is_jingmo")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_admin_set` ADD COLUMN `shop_is_jingmo` tinyint(1) DEFAULT '0' COMMENT '是否静默登陆';");
    }
}
if(getcustom('restaurant_shop_pindan')){
    if(!pdo_fieldexists2("ddwx_restaurant_table","pindan_status")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_table` ADD COLUMN `pindan_status` tinyint(1) DEFAULT '0' COMMENT '拼单模式';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_shop_order","mids")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order` ADD COLUMN `mids` varchar(255) DEFAULT NULL;");
    }
}
if(getcustom('restaurant_weigh')){
    if(!pdo_fieldexists2("ddwx_restaurant_product","product_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product` ADD COLUMN `product_type` tinyint(1) DEFAULT '0' COMMENT '菜品类型 0：普通 1：称重';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_shop_order_goods","product_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order_goods` ADD COLUMN `product_type` tinyint(1) DEFAULT '0' COMMENT '菜品类型 0：普通 1：称重';");
    }
    if(pdo_fieldexists2("ddwx_restaurant_shop_order_goods","num")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order_goods` MODIFY COLUMN `num` decimal(11,1) DEFAULT NULL;");
    }
}
if(getcustom('business_platform_auth')){
    if(!pdo_fieldexists2("ddwx_business","isplatform_auth")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `isplatform_auth` tinyint(1) DEFAULT '0';");
    }
}

if(getcustom('form_option_adminuser')){
    if(!pdo_fieldexists2("ddwx_form_order","uid")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD COLUMN `uid` int NOT NULL DEFAULT 0 COMMENT '管理员id';");
    }
    if(!pdo_fieldexists2("ddwx_form","uk")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `uk` int(11) NOT NULL DEFAULT 0 COMMENT '下拉选项设置为管理员的选项';");
    }
}
if(getcustom('form_option_givescore')){
    if(!pdo_fieldexists2("ddwx_form_order","issend_opscore")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_form_order` 
			ADD COLUMN `issend_opscore` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否发放选项积分0 ：是 1：否',
			ADD COLUMN `send_opscoretime` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '发放选项积分时间';");
    }
}
if(getcustom('score_ranking')){
    if(!pdo_fieldexists2("ddwx_admin_set","score_rank_status")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `score_rank_status` tinyint(1) DEFAULT NULL COMMENT '积分排行榜状态';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `score_rank_date` int(11) DEFAULT '1' COMMENT '积分排行榜 日期';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `score_rank_people` int(11) DEFAULT '0' COMMENT '积分排行榜 人数';");
    }
}
if(getcustom('member_richinfo')){
    if(!pdo_fieldexists2("ddwx_member","richinfo")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `richinfo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    }
}
if(getcustom('reward_business_score')){
    if(!pdo_fieldexists2("ddwx_shop_order","reward_business_score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `reward_business_score`  decimal(12,3) NULL DEFAULT 0.000 COMMENT '店长奖励积分';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","reward_business_score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `reward_business_score`  decimal(12,3) NULL DEFAULT 0.000 COMMENT '奖励店长积分数量';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","reward_business_score_bili")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `reward_business_score_bili`  decimal(12,2) NULL DEFAULT 0.000 COMMENT '奖励店长积分比例';");
    }
}
if(getcustom('product_givetongzheng')){
    if(!pdo_fieldexists2("ddwx_admin_set","tongzheng_release_bili")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `tongzheng_release_bili`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '通证释放比例';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","tongzheng_transfer")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `tongzheng_transfer`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '通证转账 0关闭 1开启';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","tongzheng2money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `tongzheng2money`  decimal(12,2) NOT NULL DEFAULT 0 COMMENT '每通证积分抵扣多少元';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","tongzhengdkmaxpercent")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `tongzhengdkmaxpercent`  decimal(12,2) NOT NULL DEFAULT 0 COMMENT '通证最多抵扣百分比';");
    }

    if(!pdo_fieldexists2("ddwx_admin_set","tongzheng_transfer_pwd")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `tongzheng_transfer_pwd`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '转账支付密码 0关闭 1开启';");
    }
    if(!pdo_fieldexists2("ddwx_member","tongzheng")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `tongzheng`  decimal(12,3) NULL DEFAULT 0.000 COMMENT '通证';");
    }
    if(!pdo_fieldexists2("ddwx_member","release_tongzheng")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `release_tongzheng`  decimal(12,3) NULL DEFAULT 0.000 COMMENT '已释放通证';");
    }
    if(!pdo_fieldexists2("ddwx_coupon","tongzheng")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` ADD COLUMN `tongzheng` decimal(12,2) DEFAULT '0.00' COMMENT '需要消费多少通证兑换';");
    }
    if(!pdo_fieldexists2("ddwx_coupon","use_tongzheng")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` ADD COLUMN `use_tongzheng` tinyint(1) DEFAULT '0' COMMENT '是否使用通证兑换 0否 1是';");
    }

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_tongzhenglog` (
    `id`  int(11) NOT NULL AUTO_INCREMENT ,
    `aid`  int(11) NULL DEFAULT NULL ,
    `mid`  int(11) NULL DEFAULT NULL ,
    `money`  decimal(12,3) NULL DEFAULT 0.000 ,
    `after`  decimal(12,3) NULL DEFAULT 0.000 ,
    `createtime`  int(11) NULL DEFAULT NULL ,
    `remark`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL ,
    `from_mid`  int(11) NULL DEFAULT 0 ,
    `paytype`  varchar(60) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    PRIMARY KEY (`id`),
    INDEX `aid` (`aid`) USING BTREE ,
    INDEX `mid` (`mid`) USING BTREE 
    )ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=Dynamic;");

    if(!pdo_fieldexists2("ddwx_shop_guige","givetongzheng")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_guige` ADD COLUMN `givetongzheng`  decimal(12,3) NULL DEFAULT 0.000 COMMENT '赠送通证';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order","givetongzheng")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `givetongzheng`  decimal(12,3) NULL DEFAULT 0.000 COMMENT '收货后赠送通证';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","givetongzheng")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `givetongzheng`  decimal(12,3) NULL DEFAULT 0.000 COMMENT '赠送通证';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","tongzhengdkmaxset")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `tongzhengdkmaxset`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '通证积分抵扣设置';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","tongzhengdkmaxval")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `tongzhengdkmaxval`  decimal(12,3) NULL DEFAULT 0.000 COMMENT '通证积分抵扣数量';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order","tongzhengdk_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `tongzhengdk_money`  decimal(12,2) NULL DEFAULT 0 COMMENT '通证抵扣金额';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order","tongzhengdktongzheng")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `tongzhengdktongzheng`  decimal(12,3) NULL DEFAULT 0 COMMENT '通证抵扣数量';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order_goods","tongzhengdk_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `tongzhengdk_money`  decimal(12,2) NULL DEFAULT 0 COMMENT '通证抵扣比例';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_tongzheng_release_log` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) NOT NULL DEFAULT '0',
      `pid` int(11) NOT NULL DEFAULT '0' COMMENT '通证订单记录id',
      `mid` int(11) NOT NULL DEFAULT '0' COMMENT '会员id',
      `tongzheng` decimal(12,3) NOT NULL DEFAULT '0.000' COMMENT '通证数量',
      `release_bili` decimal(12,3) NOT NULL DEFAULT '0.000' COMMENT '释放比例',
      `release_num` decimal(12,3) NOT NULL DEFAULT '0.000' COMMENT '释放数量',
      `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
      `createtime` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_tongzheng_order_log` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) NOT NULL,
      `mid` int(11) NOT NULL DEFAULT '0' COMMENT '会员id',
      `orderid` int(11) DEFAULT '0' COMMENT '订单id',
      `tongzheng` decimal(12,3) DEFAULT '0.000' COMMENT '通证数量',
      `release_bili` decimal(12,2) DEFAULT '0.00' COMMENT '释放比例',
      `release_num` decimal(12,3) DEFAULT '0.000' COMMENT '释放数量',
      `remain` decimal(12,3) DEFAULT '0.000' COMMENT '剩余未释放数量',
      `createtime` int(10) DEFAULT '0' COMMENT '创建时间',
      `release_time` int(10) DEFAULT '0' COMMENT '最后一次释放时间',
      `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '释放状态 0未释放完成 1释放完成 2订单删除',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;");

    if(!pdo_fieldexists2("ddwx_admin_set","withdraw2tongzheng")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `withdraw2tongzheng` decimal(12,2) DEFAULT '0.00' COMMENT '余额提现到通证比例';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","commissionwithdraw2tongzheng")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `commissionwithdraw2tongzheng` decimal(12,2) DEFAULT '0.00' COMMENT '佣金提现到通证比例';");
    }
    if(!pdo_fieldexists2("ddwx_member_commission_withdrawlog","tongzheng")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_commission_withdrawlog` ADD COLUMN `tongzheng` decimal(12,3) DEFAULT '0.000' COMMENT '通证数量';");
    }
    if(!pdo_fieldexists2("ddwx_member_withdrawlog","tongzheng")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_withdrawlog` ADD COLUMN `tongzheng` decimal(12,3) DEFAULT '0.000' COMMENT '通证数量';");
    }

}
if(getcustom('shop_order_add_shipping_status')) {
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shop_order_shipping_log` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`aid` int(11) DEFAULT NULL,
`bid` int(11) DEFAULT '0',
`orderid` int(11) DEFAULT NULL,
`ordernum` varchar(255) DEFAULT NULL,
`packid` int(11) DEFAULT NULL,
`status` int(11) DEFAULT '0' COMMENT '',
`freight_message` varchar(255) DEFAULT NULL COMMENT '',
`freight_time` int(11) DEFAULT '0' COMMENT '',
`remark` varchar(255) DEFAULT NULL COMMENT '后台备注',
`express_com` varchar(60) DEFAULT NULL COMMENT '快递公司',
`express_code` varchar(30) DEFAULT NULL COMMENT '快递公司编码',
`express_no` varchar(60) DEFAULT NULL COMMENT '快递单号',
`express_ogids` varchar(255) DEFAULT NULL,
`express_type` varchar(255) DEFAULT NULL COMMENT '物流类型',
`area` varchar(255) DEFAULT NULL,
`area2` varchar(255) DEFAULT NULL,
`address` varchar(255) DEFAULT NULL,
`longitude` varchar(100) DEFAULT NULL,
`latitude` varchar(100) DEFAULT NULL,
`mdid` int(11) DEFAULT NULL,
`createtime` int(11) DEFAULT NULL,
PRIMARY KEY (`id`) USING BTREE,
KEY `aid` (`aid`) USING BTREE,
KEY `bid` (`bid`) USING BTREE,
KEY `orderid` (`orderid`) USING BTREE,
KEY `status` (`status`) USING BTREE,
KEY `createtime` (`createtime`) USING BTREE,
KEY `freight_time` (`freight_time`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_collage_order_shipping_log` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`aid` int(11) DEFAULT NULL,
`bid` int(11) DEFAULT '0',
`orderid` int(11) DEFAULT NULL,
`ordernum` varchar(255) DEFAULT NULL,
`packid` int(11) DEFAULT NULL,
`status` int(11) DEFAULT '0' COMMENT '',
`freight_message` varchar(255) DEFAULT NULL COMMENT '',
`freight_time` int(11) DEFAULT '0' COMMENT '',
`remark` varchar(255) DEFAULT NULL COMMENT '后台备注',
`express_com` varchar(60) DEFAULT NULL COMMENT '快递公司',
`express_code` varchar(30) DEFAULT NULL COMMENT '快递公司编码',
`express_no` varchar(60) DEFAULT NULL COMMENT '快递单号',
`express_ogids` varchar(255) DEFAULT NULL,
`express_type` varchar(255) DEFAULT NULL COMMENT '物流类型',
`area` varchar(255) DEFAULT NULL,
`area2` varchar(255) DEFAULT NULL,
`address` varchar(255) DEFAULT NULL,
`longitude` varchar(100) DEFAULT NULL,
`latitude` varchar(100) DEFAULT NULL,
`mdid` int(11) DEFAULT NULL,
`createtime` int(11) DEFAULT NULL,
PRIMARY KEY (`id`) USING BTREE,
KEY `aid` (`aid`) USING BTREE,
KEY `bid` (`bid`) USING BTREE,
KEY `orderid` (`orderid`) USING BTREE,
KEY `status` (`status`) USING BTREE,
KEY `createtime` (`createtime`) USING BTREE,
KEY `freight_time` (`freight_time`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_kanjia_order_shipping_log` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`aid` int(11) DEFAULT NULL,
`bid` int(11) DEFAULT '0',
`orderid` int(11) DEFAULT NULL,
`ordernum` varchar(255) DEFAULT NULL,
`packid` int(11) DEFAULT NULL,
`status` int(11) DEFAULT '0' COMMENT '',
`freight_message` varchar(255) DEFAULT NULL COMMENT '',
`freight_time` int(11) DEFAULT '0' COMMENT '',
`remark` varchar(255) DEFAULT NULL COMMENT '后台备注',
`express_com` varchar(60) DEFAULT NULL COMMENT '快递公司',
`express_code` varchar(30) DEFAULT NULL COMMENT '快递公司编码',
`express_no` varchar(60) DEFAULT NULL COMMENT '快递单号',
`express_ogids` varchar(255) DEFAULT NULL,
`express_type` varchar(255) DEFAULT NULL COMMENT '物流类型',
`area` varchar(255) DEFAULT NULL,
`area2` varchar(255) DEFAULT NULL,
`address` varchar(255) DEFAULT NULL,
`longitude` varchar(100) DEFAULT NULL,
`latitude` varchar(100) DEFAULT NULL,
`mdid` int(11) DEFAULT NULL,
`createtime` int(11) DEFAULT NULL,
PRIMARY KEY (`id`) USING BTREE,
KEY `aid` (`aid`) USING BTREE,
KEY `bid` (`bid`) USING BTREE,
KEY `orderid` (`orderid`) USING BTREE,
KEY `status` (`status`) USING BTREE,
KEY `createtime` (`createtime`) USING BTREE,
KEY `freight_time` (`freight_time`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_seckill_order_shipping_log` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`aid` int(11) DEFAULT NULL,
`bid` int(11) DEFAULT '0',
`orderid` int(11) DEFAULT NULL,
`ordernum` varchar(255) DEFAULT NULL,
`packid` int(11) DEFAULT NULL,
`status` int(11) DEFAULT '0' COMMENT '',
`freight_message` varchar(255) DEFAULT NULL COMMENT '',
`freight_time` int(11) DEFAULT '0' COMMENT '',
`remark` varchar(255) DEFAULT NULL COMMENT '后台备注',
`express_com` varchar(60) DEFAULT NULL COMMENT '快递公司',
`express_code` varchar(30) DEFAULT NULL COMMENT '快递公司编码',
`express_no` varchar(60) DEFAULT NULL COMMENT '快递单号',
`express_ogids` varchar(255) DEFAULT NULL,
`express_type` varchar(255) DEFAULT NULL COMMENT '物流类型',
`area` varchar(255) DEFAULT NULL,
`area2` varchar(255) DEFAULT NULL,
`address` varchar(255) DEFAULT NULL,
`longitude` varchar(100) DEFAULT NULL,
`latitude` varchar(100) DEFAULT NULL,
`mdid` int(11) DEFAULT NULL,
`createtime` int(11) DEFAULT NULL,
PRIMARY KEY (`id`) USING BTREE,
KEY `aid` (`aid`) USING BTREE,
KEY `bid` (`bid`) USING BTREE,
KEY `orderid` (`orderid`) USING BTREE,
KEY `status` (`status`) USING BTREE,
KEY `createtime` (`createtime`) USING BTREE,
KEY `freight_time` (`freight_time`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_tuangou_order_shipping_log` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`aid` int(11) DEFAULT NULL,
`bid` int(11) DEFAULT '0',
`orderid` int(11) DEFAULT NULL,
`ordernum` varchar(255) DEFAULT NULL,
`packid` int(11) DEFAULT NULL,
`status` int(11) DEFAULT '0' COMMENT '',
`freight_message` varchar(255) DEFAULT NULL COMMENT '',
`freight_time` int(11) DEFAULT '0' COMMENT '',
`remark` varchar(255) DEFAULT NULL COMMENT '后台备注',
`express_com` varchar(60) DEFAULT NULL COMMENT '快递公司',
`express_code` varchar(30) DEFAULT NULL COMMENT '快递公司编码',
`express_no` varchar(60) DEFAULT NULL COMMENT '快递单号',
`express_ogids` varchar(255) DEFAULT NULL,
`express_type` varchar(255) DEFAULT NULL COMMENT '物流类型',
`area` varchar(255) DEFAULT NULL,
`area2` varchar(255) DEFAULT NULL,
`address` varchar(255) DEFAULT NULL,
`longitude` varchar(100) DEFAULT NULL,
`latitude` varchar(100) DEFAULT NULL,
`mdid` int(11) DEFAULT NULL,
`createtime` int(11) DEFAULT NULL,
PRIMARY KEY (`id`) USING BTREE,
KEY `aid` (`aid`) USING BTREE,
KEY `bid` (`bid`) USING BTREE,
KEY `orderid` (`orderid`) USING BTREE,
KEY `status` (`status`) USING BTREE,
KEY `createtime` (`createtime`) USING BTREE,
KEY `freight_time` (`freight_time`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_scoreshop_order_shipping_log` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`aid` int(11) DEFAULT NULL,
`bid` int(11) DEFAULT '0',
`orderid` int(11) DEFAULT NULL,
`ordernum` varchar(255) DEFAULT NULL,
`packid` int(11) DEFAULT NULL,
`status` int(11) DEFAULT '0' COMMENT '',
`freight_message` varchar(255) DEFAULT NULL COMMENT '',
`freight_time` int(11) DEFAULT '0' COMMENT '',
`remark` varchar(255) DEFAULT NULL COMMENT '后台备注',
`express_com` varchar(60) DEFAULT NULL COMMENT '快递公司',
`express_code` varchar(30) DEFAULT NULL COMMENT '快递公司编码',
`express_no` varchar(60) DEFAULT NULL COMMENT '快递单号',
`express_ogids` varchar(255) DEFAULT NULL,
`express_type` varchar(255) DEFAULT NULL COMMENT '物流类型',
`area` varchar(255) DEFAULT NULL,
`area2` varchar(255) DEFAULT NULL,
`address` varchar(255) DEFAULT NULL,
`longitude` varchar(100) DEFAULT NULL,
`latitude` varchar(100) DEFAULT NULL,
`mdid` int(11) DEFAULT NULL,
`createtime` int(11) DEFAULT NULL,
PRIMARY KEY (`id`) USING BTREE,
KEY `aid` (`aid`) USING BTREE,
KEY `bid` (`bid`) USING BTREE,
KEY `orderid` (`orderid`) USING BTREE,
KEY `status` (`status`) USING BTREE,
KEY `createtime` (`createtime`) USING BTREE,
KEY `freight_time` (`freight_time`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
}
if(getcustom('restaurant_product_showtj')) {
    if(!pdo_fieldexists2("ddwx_restaurant_product","showtj")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product` ADD COLUMN `showtj` varchar(255) DEFAULT '-1' COMMENT '显示';");
    }
}
if(getcustom('restaurant_wifiprint_yingmeiyun')) {
    if(!pdo_fieldexists2("ddwx_wifiprint_set","template_id")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_wifiprint_set` ADD COLUMN `template_id` varchar(255) DEFAULT NULL;");
    }
    if(!pdo_fieldexists2("ddwx_wifiprint_set","module")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_wifiprint_set` ADD COLUMN `module` varchar(255) DEFAULT 'shop' COMMENT '适用模块';");
    }
}
if(getcustom('order_add_mobile')) {
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shop_cartlr` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) DEFAULT NULL,
        `bid` int(11) DEFAULT '0',
        `mid` int(11) DEFAULT NULL,
        `proid` int(11) DEFAULT NULL,
        `ggid` int(11) DEFAULT NULL,
        `num` int(11) DEFAULT NULL,
        `sell_price` decimal(11,2) DEFAULT NULL,
        `createtime` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`) USING BTREE,
        KEY `aid` (`aid`) USING BTREE,
        KEY `bid` (`bid`) USING BTREE,
        KEY `mid` (`mid`) USING BTREE,
        KEY `proid` (`proid`) USING BTREE
      ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='录入订单记录选择商品';");
}
if(getcustom('product_wholesale')){
    if(!pdo_fieldexists2("ddwx_shop_product","jieti_discount_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `jieti_discount_type` tinyint(4) DEFAULT NULL COMMENT '数量阶梯类型0整套1单套';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","jieti_discount_data")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `jieti_discount_data`  text COMMENT '数量阶梯折扣';");
    }
}

if(getcustom('shopproduct_rewardedvideoad')){
    if(!pdo_fieldexists2("ddwx_shop_product","rewardedvideoad")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `rewardedvideoad` varchar(255) DEFAULT NULL;");
    }
}

if(getcustom('yx_choujiang_manren')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_choujiang_manren` (
    `id`  bigint(20) NOT NULL AUTO_INCREMENT ,
    `aid`  bigint(20) NULL DEFAULT NULL ,
    `type`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `name`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '幸运大转盘活动开始啦' ,
    `pic`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `pro_id`  int(11) NULL DEFAULT NULL ,
    `market_price`  decimal(10,0) NULL DEFAULT NULL ,
    `starttime`  int(11) NULL DEFAULT NULL ,
    `endtime`  int(11) NULL DEFAULT NULL ,
    `formcontent`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
    `createtime`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `updatetime`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `gettj`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '-1' ,
    `use_type`  tinyint(1) NULL DEFAULT 1 COMMENT '消耗类型 1：消耗积分 2：消耗余额' ,
    `usescore`  int(11) NULL DEFAULT 0 COMMENT '消耗积分' ,
    `limit_score`  int(11) NOT NULL DEFAULT 0 COMMENT '封顶积分' ,
    `usemoney`  decimal(10,2) NULL DEFAULT 0.00 COMMENT '消耗余额' ,
    `status`  tinyint(1) NULL DEFAULT 1 ,
    `qrcode`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '弹出二维码' ,
    `qrcode_tip`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `score_total`  int(11) NULL DEFAULT NULL COMMENT '总积分数量' ,
    `have_score`  int(11) NULL DEFAULT 0 COMMENT '已参与积分' ,
    `join_max_score`  int(11) NULL DEFAULT 0 COMMENT '每人每次可参与最大积分' ,
    `join_max_num`  int(11) NULL DEFAULT 0 COMMENT '每人每轮可参与最大次数' ,
    `cycles`  int(11) NULL DEFAULT 0 COMMENT '轮数' ,
    `cjnum`  int(11) NULL DEFAULT 0 COMMENT '参加人数' ,
    `zjnum`  int(11) NULL DEFAULT 0 COMMENT '中奖人数' ,
    PRIMARY KEY (`id`),
    INDEX `aid` (`aid`) USING BTREE ,
    INDEX `status` (`status`) USING BTREE 
    ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=Dynamic COMMENT='满人开奖——抽奖活动';");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_choujiang_manren_cycles` (
    `id`  int(11) NOT NULL AUTO_INCREMENT ,
    `aid`  int(11) NOT NULL ,
    `hid`  int(11) NOT NULL DEFAULT 0 COMMENT '活动id' ,
    `cycles`  int(11) NOT NULL DEFAULT 0 COMMENT '轮数' ,
    `cjnum`  int(11) NULL DEFAULT 0 COMMENT '已参与次数' ,
    `score_total`  int(11) NULL DEFAULT 0 COMMENT '总积分' ,
    `have_score`  int(11) NULL DEFAULT 0 COMMENT '已参与积分' ,
    `status`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '-1积分未满 0未开奖 1已开奖' ,
    `code`  varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0' COMMENT '中奖号码' ,
    `rand_num`  int(11) NULL DEFAULT NULL ,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic COMMENT='满人开奖——抽奖循环轮数';");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_choujiang_manren_record` (
    `id`  int(11) NOT NULL AUTO_INCREMENT ,
    `aid`  int(11) NULL DEFAULT NULL ,
    `hid`  int(11) NULL DEFAULT NULL COMMENT '大转盘id' ,
    `name`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `mid`  int(11) NULL DEFAULT NULL ,
    `headimg`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `nickname`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `linkman`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `tel`  char(11) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '手机号' ,
    `region`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '收货区域' ,
    `address`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '收货详细地址' ,
    `jxmc`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '奖品名称' ,
    `formdata`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
    `createtime`  int(11) NULL DEFAULT NULL COMMENT '抽奖时间' ,
    `score`  int(11) NOT NULL DEFAULT 0 COMMENT '积分' ,
    `createdate`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `status`  tinyint(1) NULL DEFAULT 0 COMMENT '0未开奖 -1未中奖 1未领取 2已领取' ,
    `remark`  varchar(1023) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `code`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '编号' ,
    `hexiaoqr`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `cycles`  int(11) NOT NULL DEFAULT 0 COMMENT '参与轮数' ,
    `formcontent`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `is_neiding`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '1内定为中奖人' ,
    `ordernum`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    PRIMARY KEY (`id`),
    INDEX `aid` (`aid`) USING BTREE ,
    INDEX `hid` (`hid`) USING BTREE ,
    INDEX `mid` (`mid`) USING BTREE 
    ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=Dynamic COMMENT='满人开奖——抽奖记录';");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_choujiang_manren_set` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) NOT NULL DEFAULT '0',
      `banner` varchar(255) DEFAULT NULL,
      `bgpic` varchar(255) DEFAULT NULL,
      `bgcolor` varchar(255) DEFAULT NULL,
      `guize` text,
      `createtime` int(10) DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='满人开奖——抽奖设置';");
}
if(getcustom('product_givetongzheng') && getcustom('yx_choujiang_manren')){
    if(!pdo_fieldexists2("ddwx_choujiang_manren_record","use_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_choujiang_manren_record` ADD COLUMN `use_type` tinyint(1) DEFAULT '1' COMMENT '1使用积分 2使用通证';");
    }
}
if(getcustom('business_fee_type')){
    if(!pdo_fieldexists2("ddwx_business_sysset", "business_fee_type")) {
        \think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD COLUMN `business_fee_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '商家费率结算类型  0:销售价 1：结算价2：成本价';");
    }
}
if(getcustom('member_auto_addlogin')){
    if(!pdo_fieldexists2("ddwx_admin_set", "is_member_auto_addlogin")) {
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `is_member_auto_addlogin` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1开始0关闭 开启后可自动注册登录系统购买商城商品，支付后填写收货地址';");
    }
}
if(getcustom('yx_invite_cashback_ordertj')){
    if(!pdo_fieldexists2("ddwx_invite_cashback", "ordertj")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_invite_cashback` ADD COLUMN `ordertj` tinyint(1) NOT NULL DEFAULT 0 COMMENT '订单返现条件 0：确认收货后 1：付款后';");
    }
}
if(getcustom('restaurant_douyin_qrcode_hexiao')){
    if(!pdo_fieldexists2("ddwx_restaurant_cashdesk", "dy_client_key")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_cashdesk` ADD COLUMN `dy_client_key` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '抖音key';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_cashdesk` ADD COLUMN `dy_client_secret` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '抖音secret';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_cashdesk` ADD COLUMN `poi_id` varchar(255) DEFAULT NULL COMMENT '核销的抖音门店id';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_cashdesk", "douyinhx")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_cashdesk` ADD COLUMN `douyinhx` tinyint(1) DEFAULT '1' COMMENT '抖音团购券核销';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_admin_set", "business_cashdesk_douyinhx")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_admin_set` ADD COLUMN `business_cashdesk_douyinhx` tinyint(1) DEFAULT '1' COMMENT '抖音团购券核销';");
    }

}
if(getcustom('teamfenhong_share')) {
    if (!pdo_fieldexists2("ddwx_member_level", "teamfenhong_share_pid_bl")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` 
    ADD COLUMN `teamfenhong_share_pid_bl` decimal(5,2) UNSIGNED NULL DEFAULT '100',
    ADD COLUMN `teamfenhong_share_pid_origin_bl` decimal(5,2) UNSIGNED NULL DEFAULT '0',
    ADD COLUMN `teamfenhong_share_pid_origin_levelid` varchar(60) NULL DEFAULT '0',
    ADD COLUMN `teamfenhong_share_buy_levelid` varchar(60) NULL DEFAULT '0',
    ADD COLUMN `teamfenhong_share_down_levelid` varchar(60) NULL DEFAULT '0';");
    }
}

if(getcustom('yx_gift_pack')) {
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_gift_pack` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `type` tinyint(1) DEFAULT '1' COMMENT '1',
	  `name` varchar(255) DEFAULT NULL,
	  `sell_price` float(11,2) DEFAULT '0.00',
	  `zsscore` int(11) DEFAULT '0',
	  `sort` int(11) DEFAULT '0',
	  `createtime` int(11) DEFAULT NULL,
	  `pic` varchar(255) DEFAULT '',
	  `content` text,
	  `coupon_ids` varchar(255) DEFAULT NULL,
	  `sales` int(11) DEFAULT '0',
	  `stock` int(11) DEFAULT '100',
	  `status` int(11) DEFAULT '0',
	  `downbuy_nums` int(11) DEFAULT '3' COMMENT '下级购买分数',
	  `commission` float(11,2) DEFAULT '0.00' COMMENT '奖励佣金',
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE
  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='礼包';");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_gift_pack_order` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `mid` int(11) DEFAULT NULL,
	  `ordernum` varchar(255) DEFAULT NULL,
	  `title` text,
	  `giftid` int(11) DEFAULT NULL,
	  `proname` varchar(255) NOT NULL DEFAULT '',
	  `propic` varchar(255) DEFAULT NULL,
	  `num` int(11) DEFAULT '1',
	  `sell_price` decimal(10,2) DEFAULT NULL,
	  `givescore` int(11) NOT NULL DEFAULT '0',
	  `createtime` int(11) DEFAULT NULL,
	  `status` int(11) DEFAULT '0' COMMENT '0未支付;1已支付',
	  `payorderid` int(11) DEFAULT NULL,
	  `paytypeid` int(11) DEFAULT NULL,
	  `paytype` varchar(50) DEFAULT NULL,
	  `paynum` varchar(255) DEFAULT NULL,
	  `paytime` int(11) DEFAULT NULL,
	  `hexiao_code` varchar(100) DEFAULT NULL,
	  `hexiao_qr` varchar(255) DEFAULT NULL,
	  `platform` varchar(255) DEFAULT 'wx',
	  `couponids` varchar(255) DEFAULT NULL,
	  `type` int(11) DEFAULT '0' COMMENT '礼包类型',
	  `parent1` int(11) DEFAULT '0',
	  `parent1commission` decimal(11,2) DEFAULT '0.00' COMMENT '推荐人佣金',
	  PRIMARY KEY (`id`) USING BTREE,
	  UNIQUE KEY `hexiao_code` (`hexiao_code`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE,
	  KEY `status` (`status`) USING BTREE
  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='礼包订单';");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_bank` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) DEFAULT NULL,
		  `mid` varchar(255) DEFAULT NULL,
		  `bankcardnum` varchar(255) DEFAULT NULL,
		  `bankname` varchar(255) DEFAULT NULL,
		  `bankaddress` varchar(255) DEFAULT NULL,
		  `bankcarduser` varchar(255) DEFAULT NULL,
		  `bank_province` varchar(255) DEFAULT NULL,
		  `isdefault` int(1) DEFAULT '0',
		  `createtime` int(11) DEFAULT NULL,
		  PRIMARY KEY (`id`) USING BTREE,
		  KEY `aid` (`aid`) USING BTREE,
		  KEY `mid` (`mid`) USING BTREE,
		  KEY `isdefault` (`isdefault`) USING BTREE
	  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='银行卡';");
}
if(getcustom('commission_withdraw_freeze')){
    if (!pdo_fieldexists2("ddwx_admin_set", "comwithdraw_freeze")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` 
		ADD COLUMN `comwithdraw_freeze` varchar(255) DEFAULT '' COMMENT '提现冻结',
		ADD COLUMN `jiedong_condtion` varchar(255) DEFAULT '' COMMENT '解冻条件',
		ADD COLUMN `comwithdraw_totalmoney` float(11,2) DEFAULT '0.00' COMMENT '提现金额',
		ADD COLUMN `buy_proid` varchar(100) DEFAULT '' COMMENT '购买商品id',
		ADD COLUMN `buypro_num` varchar(100) DEFAULT '0' COMMENT '购买数量';");
    }
    if(!pdo_fieldexists2("ddwx_member","iscomwithdraw_freeze")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `iscomwithdraw_freeze` tinyint(1) DEFAULT '0' COMMENT '佣金提现冻结';");
    }
    if (!pdo_fieldexists2("ddwx_admin_set", "comwithdraw_isjiedong")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `comwithdraw_isjiedong` tinyint(1) DEFAULT '0';");
    }
}
if(getcustom('toupiao_day_free_times')){
    if(!pdo_fieldexists2("ddwx_toupiao","day_free_times")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_toupiao` ADD COLUMN `day_free_times`  int(11) NULL DEFAULT 0 COMMENT '免费次数';");
    }
}
if(getcustom('toupiao_day_tomember_times')){
    if(!pdo_fieldexists2("ddwx_toupiao","day_tomember_times")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_toupiao` ADD COLUMN `day_tomember_times`  int(11) NULL DEFAULT 0 COMMENT '每天给单个会员投票的次数';");
    }
}
if(getcustom('commission_withdraw_level_sxf')){
    if(!pdo_fieldexists2("ddwx_admin_set","comwithdrawfee_level")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `comwithdrawfee_level` text NULL COMMENT '会员等级手续费';");
    }
}
if(getcustom('money_withdraw_level_sxf')){
    if(!pdo_fieldexists2("ddwx_admin_set","withdrawfee_level")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `withdrawfee_level` text NULL COMMENT '会员等级手续费';");
    }
}

if(getcustom('member_lock')){
    if(!pdo_fieldexists2("ddwx_member","lock_withdraw_givemoney")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `lock_withdraw_givemoney`  tinyint(1) DEFAULT '0' COMMENT '锁定提现转账'");
    }
}
if(getcustom('member_disabled')){
    if(!pdo_fieldexists2("ddwx_member","disabled_login")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `disabled_login`  tinyint(1) DEFAULT '0' COMMENT '禁用禁止登录'");
    }
}
if(getcustom('freight_add_district')){
    if(!pdo_fieldexists2("ddwx_admin_set", "open_freight_district")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `open_freight_district` tinyint(1) NOT NULL DEFAULT 0 COMMENT '配送开启县区限制 0：关闭 1：开启 ' ;");
    }
}
if(getcustom('commission_perc_to_score')){
    if(!pdo_fieldexists2("ddwx_admin_set", "commission_perc_to_score")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `commission_perc_to_score` decimal(5, 2) NOT NULL DEFAULT 0 COMMENT '获取佣金时百分比到积分';");
    }
}
if(getcustom('data_screen')){
    if(!pdo_fieldexists2("ddwx_admin_set", "data_screen_title")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `data_screen_title` varchar(60) DEFAULT '智能可视化数据' COMMENT '数据展示标题';");
    }
}
if(getcustom('restaurant_table_timing')){
    if(!pdo_fieldexists2("ddwx_restaurant_table", "timing_fee_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_table` ADD COLUMN `timing_fee_type` tinyint(1) DEFAULT '0' COMMENT '计时类型 0不计时 1阶梯计费  2时段计费';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_table` ADD COLUMN `timing_data1` text COMMENT '阶梯计时';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_table` ADD COLUMN `timing_data2` text COMMENT '时段计费';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_shop_order", "timeing_start")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order` ADD COLUMN `timeing_start` int(11) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order` ADD COLUMN `timing_money` decimal(11,2) DEFAULT '0.00' COMMENT '计时费用';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order` ADD COLUMN `timing_can_pay` tinyint(1) DEFAULT '0' COMMENT '计时订单是否可进行支付';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_shop_sysset", "timing_fee_text")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_sysset` ADD COLUMN `timing_fee_text` varchar(50) DEFAULT '计时费用' COMMENT '计时费用 别名';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_restaurant_timing_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT NULL,
  `tableid` int(11) DEFAULT NULL,
  `orderid` int(11) DEFAULT NULL,
  `start_time` varchar(100) DEFAULT NULL COMMENT '开始时间',
  `end_time` varchar(100) DEFAULT NULL COMMENT '结束时间',
  `starttime` int(11) DEFAULT NULL COMMENT '时间戳',
  `endtime` int(11) DEFAULT NULL COMMENT '时间戳',
  `num` int(11) DEFAULT '0',
  `createtime` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `id` (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
}
if(getcustom('restaurant_table_minprice')){
    if(!pdo_fieldexists2("ddwx_restaurant_table", "minprice")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_table` ADD COLUMN `minprice` decimal(11,2) DEFAULT '0.00' COMMENT '最低消费';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_table` ADD COLUMN `service_fee_type` tinyint(1) DEFAULT '0' COMMENT '服务费类型 0固定金额 1：应付金额百分比';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_table` ADD COLUMN `service_fee` decimal(10,2) DEFAULT '0.00' COMMENT '服务费或比例';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_shop_sysset", "table_service_fee_type")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_sysset` ADD COLUMN `table_service_fee_type` tinyint(1) DEFAULT '0' COMMENT '服务费提示选项0';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_shop_order", "service_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order` ADD COLUMN `service_money` decimal(11,2) DEFAULT '0.00' COMMENT '不满金额服务费';");
    }
}

if(getcustom('yx_order_discount_rand')) {
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_order_discount_rand` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `status` tinyint(1) DEFAULT '0',
  `discount_type` tinyint(1) DEFAULT '0' COMMENT '立减类型 0按金额，1按比例',
  `rate_min` decimal(11,2) DEFAULT '0.00' COMMENT '最小比例',
  `rate_max` decimal(11,2) DEFAULT '100.00' COMMENT '最大比例',
  `money_min` decimal(11,2) DEFAULT '0.00' COMMENT '最小金额',
  `money_max` decimal(11,2) DEFAULT '0.00' COMMENT '最大金额',
  `order_price_min` decimal(11,2) DEFAULT '0' COMMENT '达到最小金额的订单才会触发随机立减',
  `wait_time` int(11) DEFAULT '0' COMMENT '间隔等待时间，单位分钟',
  `createtime` int(11) DEFAULT NULL,
  `gettj` varchar(255) DEFAULT '-1',
  `order_types` varchar(255) DEFAULT 'all' COMMENT '参与订单范围',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `bid` (`bid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;");
    if(!pdo_fieldexists2("ddwx_shop_order", "discount_rand_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `discount_rand_money` decimal(11, 2) NULL AFTER `discount_money_admin`;");
    }
}
if(getcustom('restaurant_wifiprint_tmpl_custom')) {
    if(!pdo_fieldexists2("ddwx_wifiprint_set","restaurant_tmpltype")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_wifiprint_set` ADD COLUMN `restaurant_tmpltype` tinyint(1) DEFAULT '0' COMMENT '餐饮模板类型 0默认 1自定义';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_wifiprint_set` ADD COLUMN `restaurant_tmplcontent` text COMMENT '餐饮模板内容';");
    }
}

if(getcustom('user_area_agent')){
    if(!pdo_fieldexists2("ddwx_admin_user","agent_level")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_user` MODIFY COLUMN `isadmin`  tinyint(1) NULL DEFAULT 0 COMMENT '0普通管理员，1多商户或子平台主管理员，2平台主管理员 3区域代理';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_user` 
        ADD COLUMN `agent_level`  tinyint(2) NULL default 0,
        ADD COLUMN `agent_province`  varchar(30) NULL default '' AFTER `agent_level`,
        ADD COLUMN `agent_city`  varchar(30) NULL default '' AFTER `agent_province`,
        ADD COLUMN `agent_area`  varchar(30) NULL default '' AFTER `agent_city`;");
    }
    if(!pdo_fieldexists2('ddwx_designerpage','uid')){
        \think\facade\Db::execute("ALTER TABLE `ddwx_designerpage` ADD COLUMN `uid`  int(11) NULL DEFAULT 0 COMMENT '创建人';");
    }
}

if(getcustom('wurl_reward')){
    if(!pdo_fieldexists2('ddwx_member','wurl_reward_set')){
        \think\facade\Db::execute("ALTER TABLE `ddwx_member` 
            ADD COLUMN `wurl_reward_set` tinyint(1) NOT NULL DEFAULT 0 COMMENT '外部链接奖励 0：跟随系统 1：独立设置',
            ADD COLUMN `wr_money` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '外链点击奖励余额',
            ADD COLUMN `wr_score` int(11) NOT NULL DEFAULT 0 COMMENT '外链点击奖励积分',
            ADD COLUMN `wr_commission` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '外链点击奖励佣金',
            ADD COLUMN `wr_money2` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '外链每日上限余额',
            ADD COLUMN `wr_score2` int(11) NOT NULL DEFAULT 0 COMMENT '外链每日上限积分',
            ADD COLUMN `wr_commission2` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '外链每日上限佣金',
            ADD COLUMN `wr_money3` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '外链最大上限余额',
            ADD COLUMN `wr_score3` int(11) NOT NULL DEFAULT 0 COMMENT '外链最大上限积分',
            ADD COLUMN `wr_commission3` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '外链最大上限佣金';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_wurl_rewardlog` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) NOT NULL DEFAULT 0,
      `mid` int(11) NOT NULL DEFAULT 0,
      `tourl` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '请求的原url',
      `pre` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT 'url前缀',
      `url` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'url',
      `money` decimal(10, 2) NOT NULL DEFAULT 0.00,
      `score` decimal(10, 2) NOT NULL DEFAULT 0.00,
      `commission` decimal(10, 2) NOT NULL DEFAULT 0.00,
      `createtime` int(11) UNSIGNED NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`) USING BTREE,
      INDEX `aid`(`aid`) USING BTREE,
      INDEX `mid`(`mid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_wurl_reward_set` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) NOT NULL DEFAULT 0,
	  `rewardurls` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
	  `commissiondata` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
	  `status` tinyint(1) NOT NULL DEFAULT 0,
	  `createtime` int(11) UNSIGNED NOT NULL DEFAULT 0,
	  `updatetime` int(11) UNSIGNED NOT NULL DEFAULT 0,
	  PRIMARY KEY (`id`) USING BTREE,
	  INDEX `aid`(`aid`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    if(!pdo_fieldexists2('ddwx_wurl_reward_set','rewardurls')){
        \think\facade\Db::execute("ALTER TABLE `ddwx_wurl_reward_set` ADD COLUMN `rewardurls` text NULL;");
    }
}
if(getcustom('commission_orderrefund_deduct')){
    if (!pdo_fieldexists2("ddwx_admin_set", "open_commission_orderrefund_deduct")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`	ADD COLUMN `open_commission_orderrefund_deduct` tinyint(1) NULL DEFAULT '0' COMMENT '开启后订单退款，已发放的佣金追回扣除1开，0关';");
    }
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS  `ddwx_member_fenhong_record` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) DEFAULT NULL,
        `mid` int(11) DEFAULT NULL,
        `commission` decimal(17,6) DEFAULT '0.000000',
        `createtime` int(11) DEFAULT NULL,
        `remark` varchar(255) DEFAULT NULL,
        `ogid` int(11) DEFAULT NULL,
        `type` varchar(255) DEFAULT '',
        `module` varchar(255) DEFAULT 'shop',
        `score` decimal(12,3) DEFAULT '0.000',
        `status` tinyint(1) DEFAULT '1' COMMENT '1已发放2已退回',
        PRIMARY KEY (`id`) USING BTREE,
        KEY `aid` (`aid`) USING BTREE,
        KEY `mid` (`mid`) USING BTREE,
        KEY `ogid` (`ogid`)
      ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='分红单个ogid记录表';");
if(getcustom('shop_product_fenqi_pay')){
    if(!pdo_fieldexists2("ddwx_shop_product","fenqigive_couponid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `fenqigive_couponid` int(11) DEFAULT NULL COMMENT '分期赠送卡券id';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","fenqigive_couponnum")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `fenqigive_couponnum` int(4) DEFAULT NULL COMMENT '分期赠送卡券数量';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","fenqigive_fx_couponid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `fenqigive_fx_couponid` int(4) DEFAULT NULL COMMENT '分期赠送卡券数量';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","fenqigive_couponnum_fenxiao")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `fenqigive_couponnum_fenxiao` int(4) DEFAULT NULL COMMENT '分期分销奖励卡券数量';");
    }
    if(!pdo_fieldexists2("ddwx_shop_product","fenqi_data")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `fenqi_data`  text COMMENT '分期配置信息';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order","fenqi_data")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `fenqi_data`  text COMMENT '分期配置信息';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order","is_fenqi")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `is_fenqi`  tinyint(1) DEFAULT 0 COMMENT '分期订单';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order","now_fenqi_num")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `fenqi_num` tinyint(3) DEFAULT NULL COMMENT '分期数量';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `now_fenqi_num` varchar(30) DEFAULT NULL COMMENT '当前期数多个期数逗号分隔';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `fenqi_one_paydate` varchar(20) DEFAULT NULL COMMENT '第一个支付日期';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `fenqigive_couponid` int(11) DEFAULT NULL COMMENT '卡券id';");
    }
    if(!pdo_fieldexists2("ddwx_shop_order","fenqigive_fx_couponid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `fenqigive_fx_couponid` int(11) DEFAULT NULL COMMENT '分期分销奖励卡券id';");
    }
}

if(getcustom('user_hexiao_num')){
    if(!pdo_fieldexists2("ddwx_admin_user","hexiao_num")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_user` ADD COLUMN `hexiao_num` int(11) DEFAULT '-1';");
    }
}

if(getcustom('maidan_refund')){
    if(!pdo_fieldexists2("ddwx_maidan_order","refund_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_maidan_order` ADD COLUMN `refund_money`  float(10,2) NULL DEFAULT 0 AFTER `remark`;");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS  `ddwx_maidan_refund_order` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `mdid` int(11) DEFAULT NULL,
      `mid` int(11) DEFAULT NULL,
      `refund_ordernum` varchar(255) DEFAULT NULL,
      `orderid` int(11) DEFAULT '0',
      `ordernum` varchar(255) DEFAULT NULL,
      `title` text,
      `product_price` float(11,2) DEFAULT '0.00',
      `createtime` int(11) DEFAULT NULL,
      `status` int(11) DEFAULT '0' COMMENT '0未支付;1已支付;2已发货,3已收货',
      `message` varchar(255) DEFAULT NULL,
      `remark` varchar(255) DEFAULT NULL,
      `payorderid` int(11) DEFAULT NULL,
      `paytypeid` int(11) DEFAULT NULL,
      `paytype` varchar(50) DEFAULT NULL,
      `paynum` varchar(255) DEFAULT NULL,
      `paytime` int(11) DEFAULT NULL,
      `refund_type` varchar(20) DEFAULT NULL COMMENT 'refund退款，return退货退款',
      `refund_reason` varchar(255) DEFAULT NULL,
      `refund_money` decimal(11,2) DEFAULT '0.00',
      `refund_status` int(1) DEFAULT '1' COMMENT '0取消 1申请退款审核中 2已同意退款 4同意待退货 3已驳回',
      `refund_time` int(11) DEFAULT NULL,
      `refund_checkremark` varchar(255) DEFAULT NULL,
      `refund_pics` text,
      `platform` varchar(255) DEFAULT 'wx',
      `uid` int(11) DEFAULT '0',
      PRIMARY KEY (`id`) USING BTREE,
      KEY `aid` (`aid`) USING BTREE,
      KEY `bid` (`bid`) USING BTREE,
      KEY `mid` (`mid`) USING BTREE,
      KEY `createtime` (`createtime`) USING BTREE,
      KEY `orderid` (`orderid`) USING BTREE,
      KEY `refund_ordernum` (`refund_ordernum`) USING BTREE,
      KEY `ordernum` (`ordernum`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='分红单个ogid记录表';");
}

if(getcustom('teamfenhong_pingji_single_bl')){
    if(!pdo_fieldexists2("ddwx_member_level","teamfenhong_pingji_single_bl")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `teamfenhong_pingji_single_bl` varchar(100) DEFAULT NULL COMMENT '团队分红平级奖单独比例';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","teamfenhong_pingji_single_money")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `teamfenhong_pingji_single_money` varchar(100) DEFAULT NULL COMMENT '团队分红平级奖单独金额';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set","teamfenhong_pingji_parent_limit")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `teamfenhong_pingji_parent_limit` tinyint(1) DEFAULT '0' COMMENT '平级奖受限于上级团队分红 0无限制 1上级无团队分红时不发放平级奖';");
    }
}
if(getcustom('fenhong_gudong_yeji')){
    if(!pdo_fieldexists2("ddwx_member_level","fenhong_yeji_lv")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `fenhong_yeji_lv` int(11) DEFAULT 0 COMMENT '股东分红条件：级数';");
    }
    if(pdo_fieldexists2("ddwx_member_level","fenhong_yeji_lv")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` MODIFY COLUMN `fenhong_yeji_lv`  int(11) NULL DEFAULT 0 COMMENT '股东分红条件：级数';");
    }
    if(!pdo_fieldexists2("ddwx_member_level","fenhong_yeji_num")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `fenhong_yeji_num` decimal(12,2) DEFAULT 0 COMMENT '团队分红平级奖单独金额';");
    }
}
if(getcustom('restaurant_product_category_cashdesk')){
    if(!pdo_fieldexists2("ddwx_restaurant_product_category","cashdesk_show")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product_category` ADD COLUMN `cashdesk_show` tinyint(1) DEFAULT '1';");
    }
}
if(getcustom('plug_more_alipay')){
    if(!pdo_fieldexists2("ddwx_admin_setapp_app", "alipay2")){
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay2` tinyint(1) NULL DEFAULT 0 AFTER `ali_publickey`;");
    }
    if(!pdo_fieldexists2("ddwx_admin_setapp_app", "ali_appid2")){
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid2` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `alipay2`;");
    }
    if(!pdo_fieldexists2("ddwx_admin_setapp_app", "ali_privatekey2")){
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey2` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `ali_appid2`;");
    }
    if(!pdo_fieldexists2("ddwx_admin_setapp_app", "ali_publickey2")){
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey2` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `ali_privatekey2`;");
    }

    if(!pdo_fieldexists2("ddwx_admin_setapp_app", "alipay3")){
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay3` tinyint(1) NULL DEFAULT 0 AFTER `ali_publickey2`;");
    }
    if(!pdo_fieldexists2("ddwx_admin_setapp_app", "ali_appid3")){
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid3` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `alipay3`;");
    }
    if(!pdo_fieldexists2("ddwx_admin_setapp_app", "ali_privatekey3")){
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey3` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `ali_appid3`;");
    }
    if(!pdo_fieldexists2("ddwx_admin_setapp_app", "ali_publickey3")){
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey3` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `ali_privatekey3`;");
    }
}
if(getcustom('plug_more_alipay')){
    if(!pdo_fieldexists2("ddwx_admin_setapp_app", "alipay4")){
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname2` varchar(100) DEFAULT '支付宝支付2';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname3` varchar(100) DEFAULT '支付宝支付3';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay4` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname4` varchar(100) DEFAULT '支付宝支付4';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid4` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey4` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey4` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay5` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname5` varchar(100) DEFAULT '支付宝支付5';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid5` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey5` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey5` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay6` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname6` varchar(100) DEFAULT '支付宝支付6';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid6` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey6` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey6` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay7` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname7` varchar(100) DEFAULT '支付宝支付7';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid7` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey7` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey7` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay8` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname8` varchar(100) DEFAULT '支付宝支付8';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid8` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey8` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey8` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay9` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname9` varchar(100) DEFAULT '支付宝支付9';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid9` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey9` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey9` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay10` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname10` varchar(100) DEFAULT '支付宝支付10';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid10` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey10` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey10` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay11` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname11` varchar(100) DEFAULT '支付宝支付11';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid11` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey11` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey11` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay12` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname12` varchar(100) DEFAULT '支付宝支付12';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid12` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey12` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey12` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay13` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname13` varchar(100) DEFAULT '支付宝支付13';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid13` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey13` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey13` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay14` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname14` varchar(100) DEFAULT '支付宝支付14';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid14` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey14` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey14` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay15` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname15` varchar(100) DEFAULT '支付宝支付15';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid15` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey15` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey15` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay16` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname16` varchar(100) DEFAULT '支付宝支付16';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid16` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey16` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey16` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay17` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname17` varchar(100) DEFAULT '支付宝支付17';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid17` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey17` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey17` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay18` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname18` varchar(100) DEFAULT '支付宝支付18';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid18` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey18` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey18` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay19` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname19` varchar(100) DEFAULT '支付宝支付19';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid19` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey19` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey19` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay20` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname20` varchar(100) DEFAULT '支付宝支付20';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid20` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey20` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey20` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay21` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname21` varchar(100) DEFAULT '支付宝支付21';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid21` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey21` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey21` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay22` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname22` varchar(100) DEFAULT '支付宝支付22';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid22` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey22` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey22` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay23` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname23` varchar(100) DEFAULT '支付宝支付23';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid23` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey23` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey23` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay24` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname24` varchar(100) DEFAULT '支付宝支付24';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid24` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey24` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey24` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay25` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname25` varchar(100) DEFAULT '支付宝支付25';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid25` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey25` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey25` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay26` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname26` varchar(100) DEFAULT '支付宝支付26';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid26` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey26` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey26` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay27` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname27` varchar(100) DEFAULT '支付宝支付27';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid27` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey27` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey27` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay28` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname28` varchar(100) DEFAULT '支付宝支付28';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid28` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey28` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey28` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay29` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname29` varchar(100) DEFAULT '支付宝支付29';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid29` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey29` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey29` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipay30` tinyint(1) DEFAULT 0;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `alipayname30` varchar(100) DEFAULT '支付宝支付30';");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_appid30` varchar(100) DEFAULT NULL;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_privatekey30` text;");
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_app ADD COLUMN `ali_publickey30` text;");
    }
}
if(getcustom('form_custom_number')){
    if(!pdo_fieldexists2("ddwx_form","custom_number")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `custom_number` int(11) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD COLUMN `custom_number_text` varchar(50) DEFAULT NULL COMMENT '自定义序号名称';");
    }
    if(!pdo_fieldexists2("ddwx_form_order","custom_number")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD COLUMN `custom_number` int(11) DEFAULT '0';");
    }
}
if(getcustom('custom_help')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_helpnew` (
    `id`  int(11) NOT NULL AUTO_INCREMENT ,
    `aid`  int(11) NULL DEFAULT NULL ,
    `bid`  int(11) NULL DEFAULT 0 ,
    `cid`  int(11) NULL DEFAULT NULL ,
    `name`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `subname`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' ,
    `pic`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
    `content`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL ,
    `sort`  int(11) NULL DEFAULT 0 ,
    `createtime`  int(11) NULL DEFAULT NULL ,
    `status`  int(11) NULL DEFAULT 1 ,
    `pcid`  int(11) NULL DEFAULT 0 ,
    `mid`  int(11) NOT NULL DEFAULT 0 ,
    `keywords`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL ,
    `is_hot`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否热门 0否 1是' ,
    `readcount`  int(11) NOT NULL DEFAULT 0 COMMENT '阅读量' ,
    PRIMARY KEY (`id`),
    INDEX `aid` (`aid`) USING BTREE ,
    INDEX `bid` (`bid`) USING BTREE ,
    INDEX `cid` (`cid`) USING BTREE ,
    INDEX `status` (`status`) USING BTREE 
    ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_helpnew_category` (
    `id`  int(11) NOT NULL AUTO_INCREMENT ,
    `aid`  int(11) NULL DEFAULT NULL ,
    `bid`  int(11) NULL DEFAULT 0 ,
    `pid`  int(11) NULL DEFAULT 0 ,
    `name`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `pic`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `status`  int(1) NULL DEFAULT 1 ,
    `sort`  int(11) NULL DEFAULT 1 ,
    `createtime`  int(11) NULL DEFAULT NULL ,
    PRIMARY KEY (`id`),
    INDEX `aid` (`aid`) USING BTREE ,
    INDEX `bid` (`bid`) USING BTREE ,
    INDEX `pid` (`pid`) USING BTREE 
    )
    ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=Dynamic;");
    if(!pdo_fieldexists2("ddwx_admin_set", "helpnew_set")){
        \think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `helpnew_set` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '帮助中心页背景图';");
    }
}
if(getcustom('taocan_product')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_taocan_comment` (
    `id`  int(11) NOT NULL AUTO_INCREMENT ,
    `aid`  int(11) NULL DEFAULT NULL ,
    `bid`  int(11) NULL DEFAULT 0 ,
    `mid`  int(11) NULL DEFAULT NULL ,
    `orderid`  int(11) NULL DEFAULT NULL ,
    `ogid`  int(11) NULL DEFAULT NULL ,
    `proid`  int(11) NULL DEFAULT NULL ,
    `proname`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `propic`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `ggid`  int(11) NULL DEFAULT NULL ,
    `ggname`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `ordernum`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `openid`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `nickname`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `headimg`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `score`  int(11) NULL DEFAULT NULL ,
    `content`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `content_pic`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `reply_content`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `reply_content_pic`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `append_content`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `append_content_pic`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `append_reply_content`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `append_reply_content_pic`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `createtime`  int(11) NULL DEFAULT NULL ,
    `appendtime`  int(11) NULL DEFAULT NULL ,
    `status`  int(1) NULL DEFAULT 1 ,
    `reply_time`  int(11) NULL DEFAULT NULL ,
    PRIMARY KEY (`id`),
    INDEX `aid` (`aid`) USING BTREE ,
    INDEX `bid` (`bid`) USING BTREE ,
    INDEX `mid` (`mid`) USING BTREE ,
    INDEX `orderid` (`orderid`) USING BTREE ,
    INDEX `ogid` (`ogid`) USING BTREE ,
    INDEX `proid` (`proid`) USING BTREE 
    ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=Dynamic;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_taocan_order` (
    `id`  int(11) NOT NULL AUTO_INCREMENT ,
    `aid`  int(11) NULL DEFAULT NULL ,
    `bid`  int(11) NULL DEFAULT 0 ,
    `mid`  int(11) NULL DEFAULT NULL ,
    `ordernum`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `proid`  int(11) NULL DEFAULT NULL ,
    `proname`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
    `propic`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `title`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
    `totalprice`  float(11,2) NULL DEFAULT NULL ,
    `cost_price`  decimal(10,0) NULL DEFAULT NULL ,
    `sell_price`  decimal(10,0) NULL DEFAULT NULL ,
    `num`  int(11) NULL DEFAULT NULL ,
    `product_price`  float(11,2) NULL DEFAULT 0.00 ,
    `freight_price`  float(11,2) NULL DEFAULT NULL ,
    `invoice_money`  float(11,2) NULL DEFAULT 0.00 ,
    `scoredk_money`  float(11,2) NULL DEFAULT NULL COMMENT '积分抵扣金额' ,
    `leveldk_money`  float(11,2) NULL DEFAULT 0.00 COMMENT '会员等级优惠金额' ,
    `manjian_money`  decimal(11,2) NULL DEFAULT 0.00 COMMENT '满减优惠金额' ,
    `coupon_money`  decimal(11,2) NULL DEFAULT 0.00 COMMENT '优惠券金额' ,
    `coupon_rid`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `discount_money_admin`  decimal(11,2) NULL DEFAULT 0.00 COMMENT '管理员优惠金额' ,
    `scoredkscore`  int(11) NULL DEFAULT 0 COMMENT '积分抵扣用掉的积分' ,
    `givescore`  decimal(12,3) NULL DEFAULT 0.000 ,
    `givescore2`  decimal(12,3) NULL DEFAULT 0.000 ,
    `createtime`  int(11) NULL DEFAULT NULL ,
    `status`  int(11) NULL DEFAULT 0 COMMENT '0未支付;1已支付;2已发货;3已收货;4关闭;' ,
    `linkman`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `company`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `tel`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `area`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `area2`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `address`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `longitude`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `latitude`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `message`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '用户留言废弃，改为配送方式自定义表单' ,
    `remark`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '后台备注' ,
    `payorderid`  int(11) NULL DEFAULT NULL ,
    `paytypeid`  int(11) NULL DEFAULT NULL ,
    `paytype`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `paynum`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `paytime`  int(11) NULL DEFAULT NULL ,
    `express_com`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '快递公司' ,
    `express_no`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '快递单号' ,
    `express_ogids`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `express_isbufen`  tinyint(1) NULL DEFAULT 0 ,
    `express_type`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '物流类型' ,
    `express_content`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '多个快递单号时的快递单号数据' ,
    `refund_reason`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `refund_money`  decimal(11,2) NULL DEFAULT 0.00 ,
    `refund_status`  int(1) NULL DEFAULT 0 COMMENT '1申请退款审核中 2已同意退款 3已驳回' ,
    `refund_time`  int(11) NULL DEFAULT NULL ,
    `refund_checkremark`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `send_time`  bigint(20) NULL DEFAULT NULL COMMENT '发货时间' ,
    `collect_time`  int(11) NULL DEFAULT NULL COMMENT '收货时间' ,
    `freight_id`  int(11) NULL DEFAULT NULL ,
    `freight_text`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `freight_type`  tinyint(1) NULL DEFAULT 0 COMMENT '1到店自提 2同城 3自动发货 4在线卡密 5门店配送 0,10 快递' ,
    `mdid`  int(11) NULL DEFAULT NULL ,
    `freight_time`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `freight_content`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '自动发货信息 卡密' ,
    `hexiao_code`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '唯一码 核销码' ,
    `hexiao_qr`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `hexiao_code_member`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `platform`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'wx' ,
    `iscomment`  tinyint(1) NULL DEFAULT 0 ,
    `field1`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `field2`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `field3`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `field4`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `field5`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `delete`  tinyint(1) NULL DEFAULT 0 ,
    `isfenhong`  tinyint(1) NULL DEFAULT 0 COMMENT '是否已经分红' ,
    `checkmemid`  int(11) NULL DEFAULT NULL COMMENT '指定返佣用户ID' ,
    `balance_pay_status`  tinyint(1) NULL DEFAULT 0 COMMENT '0未支付，1已支付' ,
    `balance_pay_orderid`  int(11) NULL DEFAULT NULL COMMENT '尾款支付订单ID' ,
    `balance_price`  float(11,2) NULL DEFAULT 0.00 ,
    `fromwxvideo`  tinyint(1) NULL DEFAULT 0 COMMENT '是否是视频号过来的订单' ,
    `scene`  int(11) NULL DEFAULT 0 COMMENT '小程序场景' ,
    `wxvideo_order_id`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `sysOrderNo`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '定制使用对接的订单号' ,
    `transfer_check`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '转账审核 -1 驳回 0：待审核 1：通过' ,
    `dec_money`  decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '余额抵扣金额' ,
    `money_dec_rate`  decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '余额抵扣比例' ,
    `sync_bid`  int(11) NULL DEFAULT '-1' ,
    `order_type`  tinyint(4) NULL DEFAULT 0 COMMENT '0默认 1录入订单' ,
    `alipay_component_orderid`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '支付宝小程序交易组件订单id' ,
    `beishu`  int(11) NULL DEFAULT 0 ,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `code` (`hexiao_code`) USING BTREE ,
    INDEX `aid` (`aid`) USING BTREE ,
    INDEX `bid` (`bid`) USING BTREE ,
    INDEX `mid` (`mid`) USING BTREE ,
    INDEX `status` (`status`) USING BTREE ,
    INDEX `createtime` (`createtime`) USING BTREE 
    ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=Dynamic;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_taocan_order_goods` (
    `id`  int(11) NOT NULL AUTO_INCREMENT ,
    `aid`  int(11) NULL DEFAULT NULL ,
    `bid`  int(11) NULL DEFAULT 0 ,
    `mid`  int(11) NULL DEFAULT NULL ,
    `orderid`  int(11) NULL DEFAULT NULL ,
    `ordernum`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `proid`  int(11) NULL DEFAULT NULL ,
    `name`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `pic`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `procode`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `barcode`  varchar(60) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `ggid`  int(11) NULL DEFAULT NULL ,
    `ggname`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `cid`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '0' ,
    `num`  int(11) UNSIGNED NOT NULL DEFAULT 0 ,
    `refund_num`  int(11) UNSIGNED NOT NULL DEFAULT 0 ,
    `refund_money`  decimal(11,2) NULL DEFAULT 0.00 ,
    `cost_price`  decimal(11,2) NULL DEFAULT NULL ,
    `sell_price`  decimal(11,2) NULL DEFAULT NULL ,
    `totalprice`  decimal(11,2) NULL DEFAULT NULL ,
    `total_weight`  decimal(11,2) UNSIGNED NULL DEFAULT 0.00 ,
    `scoredk_money`  decimal(11,2) NULL DEFAULT 0.00 ,
    `leveldk_money`  decimal(11,2) NULL DEFAULT 0.00 ,
    `manjian_money`  decimal(11,2) NULL DEFAULT 0.00 ,
    `coupon_money`  decimal(11,2) NULL DEFAULT 0.00 ,
    `real_totalprice`  decimal(10,2) NULL DEFAULT 0.00 COMMENT '实际商品销售金额 减去了优惠券抵扣会员折扣满减积分抵扣的金额' ,
    `business_total_money`  decimal(11,2) NULL DEFAULT NULL ,
    `status`  int(1) NULL DEFAULT 0 COMMENT '0未付款1已付款2已发货3已收货4申请退款' ,
    `createtime`  int(11) NULL DEFAULT NULL ,
    `tr_roomId`  int(11) NOT NULL DEFAULT 0 COMMENT '房间id' ,
    `endtime`  int(11) NULL DEFAULT NULL ,
    `iscomment`  tinyint(1) NULL DEFAULT 0 ,
    `parent1`  int(11) NULL DEFAULT NULL ,
    `parent2`  int(11) NULL DEFAULT NULL ,
    `parent3`  int(11) NULL DEFAULT NULL ,
    `parent4`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `parent1commission`  decimal(11,2) NULL DEFAULT 0.00 ,
    `parent2commission`  decimal(11,2) NULL DEFAULT 0.00 ,
    `parent3commission`  decimal(11,2) NULL DEFAULT 0.00 ,
    `parent4commission`  decimal(11,2) NULL DEFAULT NULL ,
    `parent1score`  decimal(12,3) NULL DEFAULT 0.000 ,
    `parent2score`  decimal(12,3) NULL DEFAULT 0.000 ,
    `parent3score`  decimal(12,3) NULL DEFAULT 0.000 ,
    `iscommission`  tinyint(1) NULL DEFAULT 0 COMMENT '佣金是否已发放' ,
    `isfenhong`  tinyint(1) NULL DEFAULT 0 COMMENT '分红是否已结算' ,
    `isfg`  tinyint(1) NULL DEFAULT 0 ,
    `isteamfenhong`  tinyint(1) NULL DEFAULT 0 ,
    `ishongbao`  tinyint(1) UNSIGNED NOT NULL DEFAULT 0 ,
    `isdan`  int(11) NULL DEFAULT 0 ,
    `to86yk_tid`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `to86yk_successnum`  int(11) NULL DEFAULT 0 ,
    `hexiao_code`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '唯一码 核销码' ,
    `hexiao_qr`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '核销码图片' ,
    `hexiao_num`  int(11) NULL DEFAULT 0 ,
    `shd_style1_no`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `dec_money`  decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '余额抵扣金额' ,
    `isteamfenhong_jiandan`  tinyint(1) NULL DEFAULT 0 ,
    `gtype`  tinyint(4) NULL DEFAULT 0 COMMENT '1 赠送商品' ,
    `remark`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' ,
    `remark_ext`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' ,
    `paytime`  int(10) NOT NULL DEFAULT 0 COMMENT '支付时间' ,
    `protype`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '商品类型' ,
    `gg_proid`  int(11) NULL DEFAULT NULL ,
    `gg_proname`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    PRIMARY KEY (`id`),
    INDEX `aid` (`aid`) USING BTREE ,
    INDEX `bid` (`bid`) USING BTREE ,
    INDEX `mid` (`mid`) USING BTREE ,
    INDEX `orderid` (`orderid`) USING BTREE ,
    INDEX `proid` (`proid`) USING BTREE 
    ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=Dynamic;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_taocan_product` (
    `id`  int(11) NOT NULL AUTO_INCREMENT ,
    `aid`  int(11) NULL DEFAULT NULL ,
    `bid`  int(11) NULL DEFAULT 0 ,
    `cid`  int(11) NULL DEFAULT 0 ,
    `name`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `procode`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `sellpoint`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `pic`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' ,
    `pics`  varchar(5000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `sales`  int(11) UNSIGNED NULL DEFAULT 0 ,
    `detail`  longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL ,
    `market_price`  float(11,2) NULL DEFAULT NULL ,
    `sell_price`  float(11,2) NULL DEFAULT 0.00 ,
    `cost_price`  decimal(11,2) NULL DEFAULT 0.00 ,
    `weight`  int(11) NULL DEFAULT NULL ,
    `sort`  int(11) NULL DEFAULT 0 ,
    `status`  int(1) NULL DEFAULT 1 ,
    `stock`  int(11) UNSIGNED NULL DEFAULT 100 ,
    `createtime`  int(11) NULL DEFAULT NULL ,
    `commissionset`  tinyint(1) NULL DEFAULT 0 ,
    `commission1`  decimal(11,2) NULL DEFAULT NULL ,
    `commission2`  decimal(11,2) NULL DEFAULT NULL ,
    `commission3`  decimal(11,2) NULL DEFAULT NULL ,
    `guigedata`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
    `comment_score`  decimal(2,1) NULL DEFAULT 5.0 ,
    `comment_num`  int(11) NULL DEFAULT 0 ,
    `comment_haopercent`  int(11) NULL DEFAULT 100 ,
    `perlimit`  int(11) NULL DEFAULT 0 ,
    `freighttype`  tinyint(1) NULL DEFAULT 1 ,
    `freightdata`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `freightcontent`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
    `ischecked`  tinyint(1) NULL DEFAULT 1 ,
    `check_reason`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `pricedata`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
    `starttime`  int(11) NULL DEFAULT NULL ,
    `endtime`  int(11) NULL DEFAULT NULL ,
    `showtj`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `gettj`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
    `commissiondata1`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
    `commissiondata2`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
    `commissiondata3`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
    `scoredkmaxset`  tinyint(1) NULL DEFAULT 0 ,
    `scoredkmaxval`  decimal(11,2) NULL DEFAULT 0.00 ,
    `group_status`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '用户分组 0：未开启 1：开启' ,
    `vrnum`  int(11) NOT NULL DEFAULT 0 COMMENT '虚拟销售量' ,
    `product_ids`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '礼包产品id' ,
    `product_guige`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '礼包产品规格' ,
    `product_nums`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
    `buy_limit` int(11) NOT NULL DEFAULT '0' COMMENT '每人限购数量 0表示不限购',
    `level_price` varchar(255) DEFAULT NULL COMMENT '会员价格',
    PRIMARY KEY (`id`),
    INDEX `aid` (`aid`) USING BTREE ,
    INDEX `bid` (`bid`) USING BTREE ,
    INDEX `cid` (`cid`) USING BTREE ,
    INDEX `status` (`status`) USING BTREE ,
    INDEX `ischecked` (`ischecked`) USING BTREE 
    ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_taocan_sysset` (
    `id`  int(11) NOT NULL AUTO_INCREMENT ,
    `aid`  int(11) NULL DEFAULT NULL ,
    `pics`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
    `autoshdays`  int(11) NULL DEFAULT 7 ,
    `comment`  tinyint(1) NULL DEFAULT 1 ,
    `comment_check`  tinyint(1) NULL DEFAULT 1 ,
    `showjd`  tinyint(1) NULL DEFAULT 1 ,
    `refund`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否可退款 0否 1是' ,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `aid` (`aid`) USING BTREE 
    ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=Dynamic;");
}
if(getcustom('restaurant_product_package')){
    if(!pdo_fieldexists2("ddwx_restaurant_product","packagedata")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product` ADD COLUMN `packagedata` text COMMENT '套餐数据';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product` ADD COLUMN `package_is_discount` tinyint(1) DEFAULT '0' COMMENT '套餐 0：不打折 1：打折';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product` ADD COLUMN `package_is_coupon` tinyint(1) DEFAULT '0' COMMENT '套餐可使用优惠券 0：关闭 1：开启';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product` ADD COLUMN `package_is_cuxiao` tinyint(1) DEFAULT '0' COMMENT '套餐可使用促销 0：关闭 1：开启';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product` ADD COLUMN `package_price` decimal(10,2) DEFAULT '0.00' COMMENT '套餐价格';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_shop_cart","package_data")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_cart` ADD COLUMN `package_data` text COMMENT '套餐数据';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_cart` ADD COLUMN `package_price` decimal(10,2) DEFAULT '0.00' COMMENT '套餐价格';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_shop_order_goods","package_data")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order_goods` ADD COLUMN `package_data` text COMMENT '套餐数据';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order_goods` ADD COLUMN `is_package` tinyint(1) DEFAULT '0';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order_goods` ADD COLUMN `product_type` tinyint(1) DEFAULT '0' COMMENT '菜品类型 0：普通 1：称重 2套餐';");
    }
    if(!pdo_fieldexists2("ddwx_restaurant_product","product_type")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product` ADD COLUMN `product_type` tinyint(1) DEFAULT '0' COMMENT '菜品类型 0：普通 1：称重';");
    }
}
if(getcustom('mendian_apply')){
    if (!pdo_fieldexists2("ddwx_mendian", "mid")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian` 
		ADD COLUMN  `mid` int(11) DEFAULT '0',
		ADD COLUMN  `check_status` tinyint(1) DEFAULT '0' COMMENT '审核状态',
		ADD COLUMN  `reason` varchar(255) DEFAULT NULL;");
    }
}
if(getcustom('car_hailing')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_car_hailing_product` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `name` varchar(255) DEFAULT NULL,
	  `procode` varchar(255) DEFAULT NULL,
	  `fuwupoint` varchar(255) DEFAULT NULL,
	  `sellpoint` varchar(255) DEFAULT NULL,
	  `workerid` int(11) DEFAULT '0',
	  `pic` varchar(255) DEFAULT '',
	  `pics` varchar(5000) DEFAULT NULL,
	  `sales` int(11) DEFAULT '0',
	  `detail` longtext,
	  `sell_price` float(11,2) DEFAULT '0.00',
	  `sort` int(11) DEFAULT '0',
	  `status` int(1) DEFAULT '1',
	  `stock` int(11) unsigned DEFAULT '100',
	  `createtime` int(11) DEFAULT NULL,
	  `comment_score` decimal(2,1) DEFAULT '5.0',
	  `comment_num` int(11) DEFAULT '0',
	  `comment_haopercent` int(11) DEFAULT '100',
	  `gettj` varchar(255) DEFAULT '-1',
	  `gettjurl` varchar(255) DEFAULT NULL,
	  `gettjtip` varchar(255) DEFAULT NULL,
	  `starttime` varchar(100) DEFAULT NULL,
	  `endtime` varchar(100) DEFAULT NULL,
	  `ischecked` tinyint(1) DEFAULT '1',
	  `check_reason` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
	  `cid` varchar(11) DEFAULT '0' COMMENT '分类id',
	  `yynum` int(11) DEFAULT '1' COMMENT '同一时间段预约人数限制',
	  `rqtype` tinyint(3) DEFAULT '1' COMMENT '预约周期',
	  `yyzhouqi` varchar(255) DEFAULT NULL COMMENT '预约周期，周一-周日',
	  `start_time` varchar(100) DEFAULT NULL,
	  `end_time` varchar(100) DEFAULT NULL,
	  `yybegintime` varchar(100) DEFAULT NULL COMMENT '预约开始时间',
	  `yyendtime` varchar(100) DEFAULT NULL,
	  `couponids` varchar(255) DEFAULT NULL COMMENT '预约次卡id',
	  `yytimeday` varchar(255) DEFAULT NULL COMMENT '预约固定周期',
	  `formdata` text,
	  `prehour` varchar(255) DEFAULT NULL,
	  `pid` int(11) DEFAULT '0',
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `status` (`status`) USING BTREE,
	  KEY `stock` (`stock`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='商品表';");

    if(!pdo_fieldexists2("ddwx_car_hailing_product","pid")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_car_hailing_product` ADD COLUMN `pid` int(11) DEFAULT '0';");
    }
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_car_hailing_order` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `mid` int(11) DEFAULT NULL,
	  `ordernum` varchar(255) DEFAULT NULL,
	  `title` text,
	  `totalprice` float(11,2) DEFAULT NULL,
	  `product_price` float(11,2) DEFAULT '0.00',
	  `leveldk_money` float(11,2) DEFAULT '0.00',
	  `coupon_rid` int(11) DEFAULT NULL COMMENT '优惠券coupon_record的id',
	  `num` int(11) DEFAULT '1',
	  `createtime` int(11) DEFAULT NULL,
	  `status` int(11) DEFAULT '0' COMMENT '0未支付;1已支付;2已发货;3已收货;4关闭;',
	  `linkman` varchar(255) DEFAULT NULL,
	  `tel` varchar(50) DEFAULT NULL,
	  `area` varchar(255) DEFAULT NULL,
	  `area2` varchar(255) DEFAULT NULL,
	  `address` varchar(255) DEFAULT NULL,
	  `longitude` varchar(100) DEFAULT NULL,
	  `latitude` varchar(100) DEFAULT NULL,
	  `message` varchar(255) DEFAULT NULL,
	  `remark` varchar(255) DEFAULT NULL,
	  `payorderid` int(11) DEFAULT NULL,
	  `paytypeid` int(11) DEFAULT NULL COMMENT '16 次卡支付',
	  `paytype` varchar(50) DEFAULT NULL,
	  `paynum` varchar(255) DEFAULT NULL,
	  `paytime` int(11) DEFAULT NULL,
	  `send_time` bigint(20) DEFAULT NULL COMMENT '发货时间',
	  `collect_time` int(11) DEFAULT NULL COMMENT '收货时间',
	  `yy_date` varchar(100) DEFAULT NULL,
	  `yy_time` varchar(255) DEFAULT NULL,
	  `hexiao_code` varchar(100) DEFAULT NULL COMMENT '唯一码 核销码',
	  `hexiao_qr` varchar(255) DEFAULT NULL,
	  `platform` varchar(255) DEFAULT 'wx',
	  `iscomment` tinyint(1) DEFAULT '0',
	  `delete` tinyint(1) DEFAULT '0',
	  `coupon_money` float(11,2) DEFAULT '0.00',
	  `propic` varchar(255) DEFAULT NULL,
	  `proname` varchar(255) DEFAULT NULL,
	  `proid` int(11) DEFAULT '0',
	  `workerid` int(11) DEFAULT '0' COMMENT '服务人员id',
	  `begintime` int(11) DEFAULT '0',
	  `endtime` int(11) DEFAULT '0',
	  `refund_status` tinyint(1) DEFAULT '0',
	  `refund_time` int(11) DEFAULT NULL,
	  `refund_reason` varchar(255) DEFAULT NULL,
	  `refund_money` decimal(11,2) DEFAULT NULL,
	  `refund_checkremark` varchar(255) DEFAULT NULL,
	  `start_time` varchar(20) DEFAULT NULL,
	  `end_time` varchar(20) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  UNIQUE KEY `code` (`hexiao_code`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE,
	  KEY `status` (`status`) USING BTREE,
	  KEY `createtime` (`createtime`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_car_hailing_set` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `status` tinyint(1) DEFAULT '0',
	  `autoshdays` int(11) DEFAULT '7',
	  `autoclose` int(255) DEFAULT '600',
	  `minminute` int(11) DEFAULT '3',
	  `discount` tinyint(1) DEFAULT '0',
	  `iscoupon` tinyint(1) DEFAULT '0',
	  `comment_check` tinyint(1) unsigned DEFAULT '1' COMMENT '评价审核，1开启，0关闭',
	  `start_time_ratio` text,
	  `end_time_ratio` text,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_car_hailing_category` (
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
	  KEY `pid` (`pid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

    if(!pdo_fieldexists2("ddwx_car_hailing_order","zc_start_time")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_car_hailing_order` ADD COLUMN `zc_start_time` varchar(30)  DEFAULT '';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_car_hailing_order` ADD COLUMN `zc_end_time` varchar(30)  DEFAULT '';");
    }
    if(!pdo_fieldexists2("ddwx_car_hailing_set","zc_select_months")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_car_hailing_set` ADD COLUMN `zc_select_months` int(11)  DEFAULT '6';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_car_hailing_set` ADD COLUMN `zc_hour_day` int(11)  DEFAULT '4';");
    }

    \think\facade\Db::execute("ALTER TABLE `ddwx_car_hailing_order` MODIFY COLUMN `num`  float(11,1) DEFAULT '1.0';");

    if(!pdo_fieldexists2("ddwx_car_hailing_set","zc_max_day")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_car_hailing_set` ADD COLUMN `zc_max_day` int(11) DEFAULT '0' COMMENT '租车最多天数';");
    }
    if(!pdo_fieldexists2("ddwx_car_hailing_product","is_coupon")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_car_hailing_product` ADD COLUMN `is_coupon` tinyint(1) DEFAULT '1';");
    }
    if(!pdo_fieldexists2("ddwx_car_hailing_set","zc_desc_status")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_car_hailing_set` ADD COLUMN `zc_desc_status` tinyint(1) DEFAULT '1' COMMENT '租车说明状态';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_car_hailing_set` ADD COLUMN `zc_desc` text COMMENT '租车说明';");

        \think\facade\Db::execute("ALTER TABLE `ddwx_car_hailing_set` ADD COLUMN `bc_desc_status` tinyint(1) DEFAULT '1' COMMENT '包车说明状态';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_car_hailing_set` ADD COLUMN `bc_desc` text COMMENT '包车说明';");

        \think\facade\Db::execute("ALTER TABLE `ddwx_car_hailing_set` ADD COLUMN `pc_desc_status` tinyint(1) DEFAULT '1' COMMENT '拼车说明状态';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_car_hailing_set` ADD COLUMN `pc_desc` text COMMENT '拼车说明';");

        \think\facade\Db::execute("ALTER TABLE `ddwx_car_hailing_set` ADD COLUMN `refund_desc_status` tinyint(1) DEFAULT '1' COMMENT '退款提示状态';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_car_hailing_set` ADD COLUMN `refund_desc` text COMMENT '退款说明';");
    }
    if(!pdo_fieldexists2("ddwx_car_hailing_order","scoredk_money")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_car_hailing_order` ADD COLUMN `scoredk_money` decimal(10,2) DEFAULT '0.00' COMMENT '积分抵扣金额';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_car_hailing_order` ADD COLUMN `scoredkscore` int(11) DEFAULT '0';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set_sms","tmpl_carhailing_sucess_st")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` ADD COLUMN `tmpl_carhailing_sucess_st` tinyint(1) DEFAULT '1';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` ADD COLUMN `tmpl_carhailing_sucess` varchar(255) DEFAULT NULL;");
    }

}
if(getcustom('region_partner')){
    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_region_partner_order` (
    `id`  int(11) NOT NULL AUTO_INCREMENT ,
    `aid`  int(11) NOT NULL ,
    `ordernum`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '订单编号' ,
    `mid`  int(11) NOT NULL DEFAULT 0 COMMENT '会员id' ,
    `name`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '姓名' ,
    `tel`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '手机号' ,
    `company`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '店铺名称' ,
    `province`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '省' ,
    `city`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '市' ,
    `district`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '区县' ,
    `apply_money`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '申请费用' ,
    `bonus`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '已发奖金' ,
    `remain`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '剩余未发奖金' ,
    `status`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0未审核 1审核通过 2审核拒绝' ,
    `bonus_status`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '奖金状态 0未完成 1已完成' ,
    `createtime`  int(10) NOT NULL DEFAULT 0 COMMENT '申请时间' ,
    `payorderid`  int(11) NOT NULL DEFAULT 0 COMMENT '支付订单id' ,
    `paytime`  int(10) NOT NULL DEFAULT 0 COMMENT '支付时间' ,
    `paytype`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL ,
    `paytypeid`  int(11) NOT NULL ,
    `paynum`  decimal(12,2) NOT NULL DEFAULT 0.00 ,
    `platform`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' ,
    `set_id`  int(11) NOT NULL DEFAULT 0 COMMENT '区域设置id' ,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");

    \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_region_partner_set` (
    `id`  int(11) NOT NULL AUTO_INCREMENT ,
    `aid`  int(11) NOT NULL DEFAULT 1 ,
    `apply_money`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '申请预存配送费用' ,
    `fh_num`  int(11) NOT NULL DEFAULT 0 COMMENT '参与分红人数' ,
    `day_fh`  decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '每日分红金额' ,
    `province`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '省' ,
    `city`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '市' ,
    `district`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '区县' ,
    `createtime`  int(10) NOT NULL ,
    PRIMARY KEY (`id`)
    )ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=Dynamic;");
}
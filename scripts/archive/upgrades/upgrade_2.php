<?php

if(!pdo_fieldexists2("ddwx_member_levelup_order", "areafenhong_largearea")){

	if(!pdo_fieldexists2("ddwx_member", "aliaccountname")){
		\think\facade\Db::execute("ALTER TABLE ddwx_member ADD aliaccountname varchar(255) DEFAULT NULL COMMENT '支付宝姓名' AFTER `aliaccount`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_member_withdrawlog` ADD aliaccountname varchar(255) DEFAULT NULL COMMENT '支付宝姓名' AFTER `aliaccount`;");
		\think\facade\Db::execute("ALTER TABLE ddwx_member_commission_withdrawlog ADD aliaccountname varchar(255) DEFAULT NULL COMMENT '支付宝姓名' AFTER `aliaccount`;");
	}

	\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_toutiaopay_log` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `mid` int(11) DEFAULT NULL,
	  `openid` varchar(255) DEFAULT NULL,
	  `tablename` varchar(255) DEFAULT NULL,
	  `ordernum` varchar(255) DEFAULT NULL,
	  `mch_id` int(11) DEFAULT NULL,
	  `transaction_id` varchar(255) DEFAULT NULL,
	  `total_fee` decimal(11,2) DEFAULT '0.00',
	  `givescore` int(11) DEFAULT '0',
	  `createtime` int(11) DEFAULT NULL,
	  `fenzhangmoney` decimal(11,2) DEFAULT '0.00',
	  `isfenzhang` tinyint(1) DEFAULT '0',
	  `fz_ordernum` varchar(100) DEFAULT NULL,
	  `fz_errmsg` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE,
	  KEY `createtime` (`createtime`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");


	if(!pdo_fieldexists2("ddwx_payorder", "issettle")){
		\think\facade\Db::execute("ALTER TABLE ddwx_payorder ADD issettle tinyint(1) DEFAULT '0' COMMENT '头条小程序是否已分账';");
	}

	if(pdo_fieldexists2("ddwx_toupiao_join", "pics")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_toupiao_join` CHANGE `pics` `pics` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");
	}
	if(pdo_fieldexists2("ddwx_toupiao_help", "nickname")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_toupiao_help` CHANGE `nickname` `nickname` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;");
	}
	if(!pdo_fieldexists2("ddwx_restaurant_booking_order", "collect_time")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_booking_order` ADD `collect_time` INT(11) DEFAULT NULL AFTER `refund_checkremark`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_booking_order_goods` ADD `endtime` INT(11) DEFAULT NULL AFTER `create_time`;");
	}
	if(!pdo_fieldexists2("ddwx_shop_product", "feepercent")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `feepercent` decimal(5, 2) UNSIGNED NULL;");
	}

	if(!pdo_fieldexists2("ddwx_shop_order_goods", "scoredk_money")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods`
	ADD COLUMN `scoredk_money` decimal(11, 2) NULL DEFAULT '0' AFTER `totalprice`,
	ADD COLUMN `leveldk_money` decimal(11, 2) NULL DEFAULT '0' AFTER `scoredk_money`,
	ADD COLUMN `manjian_money` decimal(11, 2) NULL DEFAULT '0' AFTER `leveldk_money`,
	ADD COLUMN `coupon_money` decimal(11, 2) NULL DEFAULT '0' AFTER `manjian_money`,
	ADD COLUMN `business_total_money` decimal(11, 2) NULL DEFAULT NULL AFTER `real_totalprice`;");
	}


	if(!pdo_fieldexists2("ddwx_shop_product", "fengdanjiangli")){
		\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD fengdanjiangli varchar(255) DEFAULT ''");
		\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD `comwithdrawbl` decimal(11,2) DEFAULT '100.00' AFTER `comwithdrawfee`");
		\think\facade\Db::execute("ALTER TABLE ddwx_shop_order_goods ADD isdan int(11) DEFAULT '0'");
	}
	if(!pdo_fieldexists2("ddwx_restaurant_shop_sysset", "banner")){
		\think\facade\Db::execute("ALTER TABLE ddwx_restaurant_shop_sysset ADD `banner` varchar(255) DEFAULT NULL AFTER `bid`");
		\think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_sysset` ADD `banner` varchar(255) DEFAULT NULL AFTER `bid`");
	}
	if(!pdo_fieldexists2("ddwx_admin_user", "bids")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_admin_user` ADD `bids` varchar(255) DEFAULT '0'");
	}

	if(!pdo_fieldexists2("ddwx_collage_product", "feepercent")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_collage_product` ADD COLUMN `feepercent` decimal(5, 2) UNSIGNED NULL;");
	}
	if(!pdo_fieldexists2("ddwx_collage_order", "business_total_money")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_collage_order`
	ADD COLUMN `business_total_money` decimal(11, 2) NULL DEFAULT NULL AFTER `totalprice`;");
	}
	if(!pdo_fieldexists2("ddwx_kanjia_product", "feepercent")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` ADD COLUMN `feepercent` decimal(5, 2) UNSIGNED NULL;");
	}
	if(!pdo_fieldexists2("ddwx_kanjia_order", "business_total_money")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_order`
	ADD COLUMN `business_total_money` decimal(11, 2) NULL DEFAULT NULL AFTER `totalprice`;");
	}
	if(!pdo_fieldexists2("ddwx_seckill_product", "feepercent")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_seckill_product` ADD COLUMN `feepercent` decimal(5, 2) UNSIGNED NULL;");
	}
	if(!pdo_fieldexists2("ddwx_seckill_order", "business_total_money")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_seckill_order`
	ADD COLUMN `business_total_money` decimal(11, 2) NULL DEFAULT NULL AFTER `totalprice`;");
	}

	if(!pdo_fieldexists2("ddwx_lucky_collage_order", "isfenhong")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_order` ADD COLUMN `isfenhong` tinyint(1) DEFAULT '0';");
	}

	if(!pdo_fieldexists2("ddwx_admin_set", "fhjiesuanhb")){
		\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `fhjiesuanhb` tinyint(1) DEFAULT '0' AFTER fhjiesuantime;");
	}
	if(!pdo_fieldexists2("ddwx_admin_set", "commission2scorepercent")){
		\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `commission2scorepercent` decimal(11, 2) DEFAULT '0.00' AFTER comwithdrawbl;");
		\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `up_fxordermoney_removemax` tinyint(1) DEFAULT '0' AFTER `up_fxorderlevelid`;");
	}

	if(!pdo_fieldexists2("ddwx_admin_user","workcate_id")){
		\think\facade\Db::execute("ALTER TABLE ddwx_admin_user ADD `workcate_id` int(11) DEFAULT '0';");
	}


	if(!pdo_fieldexists2("ddwx_shortvideo", "linkurl")){
		\think\facade\Db::execute("ALTER TABLE ddwx_shortvideo ADD `linkurl` varchar(255) DEFAULT NULL AFTER productids;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_wxvideo_catelist` MODIFY COLUMN `qualification` text;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_wxvideo_catelist` MODIFY COLUMN `product_qualification` text;");
	}

	if(!pdo_fieldexists2("ddwx_admin_user","workorder_type")){
		\think\facade\Db::execute("ALTER TABLE ddwx_admin_user ADD `workorder_type` int(11) DEFAULT '0';");
	}
	\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_workorder_category` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `name` varchar(255) DEFAULT NULL,
	  `content` longtext,
	  `sort` int(11) DEFAULT '0',
	  `createtime` int(11) DEFAULT NULL,
	  `status` int(11) DEFAULT '1',
	  `payset` tinyint(1) DEFAULT NULL,
	  `price` decimal(10,2) DEFAULT '0.00',
	  `priceedit` tinyint(1) DEFAULT '0',
	  `maxlimit` int(11) DEFAULT '0' COMMENT '总收集数量限制',
	  `perlimit` int(11) DEFAULT '1' COMMENT '每人提交次数限制',
	  `usertype` tinyint(255) DEFAULT '1' COMMENT '1为用户   2为商户',
	  `cltype` tinyint(2) DEFAULT '2' COMMENT '默认为2',
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");


	\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_workorder_chuli` (
	 `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `logid` int(11) DEFAULT '0',
	  `desc` varchar(255) DEFAULT NULL,
	  `remark` varchar(255) DEFAULT NULL,
	  `lcid` int(1) DEFAULT '1',
	  `createtime` int(11) DEFAULT NULL,
	  `userid` int(11) DEFAULT '0' COMMENT '谁处理得',
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `pid` (`logid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");


	\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_workorder_order` (
	  `id` bigint(20) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `formid` bigint(20) DEFAULT NULL,
	  `title` varchar(255) DEFAULT NULL,
	  `mid` int(11) DEFAULT NULL,
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
	  `status` tinyint(1) DEFAULT '0' COMMENT '0待处理 1处理中  2已处理   -1 驳回',
	  `createtime` int(11) DEFAULT NULL,
	  `isudel` tinyint(1) DEFAULT '0',
	  `paystatus` int(1) DEFAULT '0',
	  `paynum` varchar(255) DEFAULT NULL,
	  `money` float(11,2) DEFAULT '0.00',
	  `ordernum` varchar(255) DEFAULT NULL,
	  `payorderid` int(11) DEFAULT NULL,
	  `paytypeid` int(11) DEFAULT NULL,
	  `paytype` varchar(255) DEFAULT NULL,
	  `paytime` int(11) DEFAULT NULL,
	  `platform` varchar(100) DEFAULT NULL,
	  `reason` varchar(255) DEFAULT NULL COMMENT '驳回原因',
	  `isrefund` tinyint(1) DEFAULT '0',
	  `fromurl` varchar(255) DEFAULT NULL,
	  `userid` int(11) DEFAULT '0' COMMENT '员工id',
	  `type` int(11) DEFAULT '1' COMMENT '1为用户  2 为商户',
	  `cltype` int(11) DEFAULT '2' COMMENT '1 所有人 2按分类 3 后台指派',
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE,
	  KEY `formid` (`formid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");


	\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_workorder_liucheng` (
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


	if(!pdo_fieldexists2("ddwx_workorder_liucheng","lcstatus")){
		\think\facade\Db::execute("ALTER TABLE ddwx_workorder_liucheng ADD `lcstatus` tinyint(2) DEFAULT '1';");
	}


	if(!pdo_fieldexists2("ddwx_shortvideo", "linkname")){
		\think\facade\Db::execute("ALTER TABLE ddwx_shortvideo ADD `linkname` varchar(255) DEFAULT NULL AFTER `linkurl`;");
		\think\facade\Db::execute("ALTER TABLE ddwx_shortvideo ADD `mid` int(11) DEFAULT '0' AFTER `bid`;");
		\think\facade\Db::execute("ALTER TABLE ddwx_shortvideo ADD `reason` varchar(255) DEFAULT NULL;");
	}


	\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shortvideo_sysset` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `list_type` tinyint(1) DEFAULT '0',
	  `can_upload` tinyint(1) DEFAULT '0',
	  `upload_maxsize` int(11) DEFAULT '0',
	  `upload_maxduration` int(11) DEFAULT '0',
	  `upload_check` int(11) DEFAULT '1',
	  PRIMARY KEY (`id`) USING BTREE,
	  UNIQUE KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");


	if(!pdo_fieldexists2("ddwx_business_sysset", "article_check")){
		\think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD `article_check` tinyint(1) DEFAULT '0' AFTER `product_check`;");
		\think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD `shortvideo_check` tinyint(1) DEFAULT '0' AFTER `article_check`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_article` ADD `reason` varchar(255) DEFAULT NULL AFTER `status`;");
	}


	if(!pdo_fieldexists2("ddwx_member_level", "areafenhongmaxnum")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `areafenhongmaxnum` int(11) DEFAULT '0' AFTER `areafenhongbl`;");
	}

	if(!pdo_fieldexists2("ddwx_admin_set", "maidanfenxiao")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD `maidanfenxiao` tinyint(1) DEFAULT '0' AFTER `fhjiesuanbusiness`;");
	}

	if(!pdo_fieldexists2("ddwx_admin_set", "commission2moneypercent1")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD `commission2moneypercent1` decimal(11,2) DEFAULT '0.00' AFTER `commission2scorepercent`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD `commission2moneypercent2` decimal(11,2) DEFAULT '0.00' AFTER `commission2moneypercent1`;");
	}


	if(!pdo_fieldexists2("ddwx_business_sysset", "wxfw_status")){
		\think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD `wxfw_status` tinyint(1) DEFAULT '0'");
		\think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD `wxfw_mchname` varchar(255) DEFAULT NULL");
		\think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD `wxfw_appid` varchar(100) DEFAULT NULL");
		\think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD `wxfw_mchid` varchar(100) DEFAULT NULL");
		\think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD `wxfw_mchkey` varchar(100) DEFAULT NULL");
		\think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD `wxfw_apiclient_cert` varchar(255) DEFAULT NULL");
		\think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD `wxfw_apiclient_key` varchar(255) DEFAULT NULL");
	}
	if(!pdo_fieldexists2("ddwx_business", "wxpayst")){
		\think\facade\Db::execute("ALTER TABLE ddwx_business ADD `wxpayst` tinyint(1) DEFAULT '0'");
		\think\facade\Db::execute("ALTER TABLE ddwx_business ADD `wxpay_submchid` varchar(100) DEFAULT NULL");

	}

	if(!pdo_fieldexists2("ddwx_payorder", "isbusinesspay")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_payorder` ADD `isbusinesspay` tinyint(1) DEFAULT '0'");
	}
	if(!pdo_fieldexists2("ddwx_wxpay_log", "bid")){
		\think\facade\Db::execute("ALTER TABLE ddwx_wxpay_log ADD `bid` int(11) DEFAULT '0'");
	}

	if(!pdo_fieldexists2("ddwx_workorder_category", "isglorder")){
		\think\facade\Db::execute("ALTER TABLE ddwx_workorder_category ADD `isglorder` tinyint(1) DEFAULT '0'");
	}

	if(!pdo_fieldexists2("ddwx_workorder_order", "ordertype")){
		\think\facade\Db::execute("ALTER TABLE ddwx_workorder_order ADD `ordertype` varchar(100) DEFAULT NULL");
		\think\facade\Db::execute("ALTER TABLE ddwx_workorder_order ADD `orderid` int(11) DEFAULT 0");
		\think\facade\Db::execute("ALTER TABLE ddwx_workorder_order ADD `glordernum` varchar(255) DEFAULT NULL");
	}

	if(!pdo_fieldexists2("ddwx_member_level", "teamfenhong_pingji_bl")){
		\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD teamfenhong_pingji_bl decimal(11,2) DEFAULT NULL AFTER `teamfenhong_self`");
		\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD teamfenhong_pingji_money decimal(11,2) DEFAULT NULL AFTER teamfenhong_pingji_bl");
	}
	if(!pdo_fieldexists2("ddwx_admin_set", "partner_jiaquan")){
		\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD partner_jiaquan tinyint(1) DEFAULT '0' AFTER fhjiesuanbusiness");
		\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD partner_gongxian tinyint(1) DEFAULT '0' AFTER partner_jiaquan");
	}
	if(!pdo_fieldexists2("ddwx_admin_set", "teamyeji_show")){
		\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD teamyeji_show tinyint(1) DEFAULT '0' AFTER partner_gongxian");
		\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD teamnum_show tinyint(1) DEFAULT '0' AFTER teamyeji_show");
		\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD gongxianfenhong_show tinyint(1) DEFAULT '0' AFTER teamnum_show");
		\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD gongxianfenhong_txt varchar(30) DEFAULT NULL AFTER gongxianfenhong_show");
	}
	if(!pdo_fieldexists2("ddwx_member_level", "fenhong_gongxian_minyeji")){
		\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD fenhong_gongxian_minyeji decimal(11,2) DEFAULT '0'");
		\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD fenhong_gongxian_percent decimal(11,2) DEFAULT '0'");
	}
	if(!pdo_fieldexists2("ddwx_yuyue_set", "discount")){
		\think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD discount tinyint(1) DEFAULT '0' ");
		\think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD iscoupon tinyint(1) DEFAULT '0' ");
	}
	if(!pdo_fieldexists2("ddwx_yuyue_order", "leveldk_money")){
		\think\facade\Db::execute("ALTER TABLE ddwx_yuyue_order ADD leveldk_money float(11,2) DEFAULT '0.00' ");
	}


	\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_business_recharge_order` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT NULL,
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
	  KEY `mid` (`bid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8");

	\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yuyue_fuwu` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `name` varchar(255) DEFAULT NULL,
	  `desc` text,
	  `status` int(1) DEFAULT '1',
	  `sort` int(11) DEFAULT '1',
	  `createtime` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");


	if(!pdo_fieldexists2("ddwx_yuyue_product", "fwid")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD `fwid` varchar(255) DEFAULT NULL AFTER `sellpoint`");
	}

	if(!pdo_fieldexists2("ddwx_article", "showtj")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_article` ADD COLUMN `showtj` varchar(255) NULL DEFAULT '-1' AFTER `zan`,
	ADD INDEX(`showtj`);");
	}

	if(!pdo_fieldexists2("ddwx_shop_guige", "limit_start")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_shop_guige` ADD COLUMN `limit_start` int(11) UNSIGNED NULL DEFAULT '0' AFTER `givescore`;");
	}

	if(!pdo_fieldexists2("ddwx_shop_category", "showtj")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_shop_category` ADD COLUMN `showtj` varchar(255) NULL DEFAULT '-1' AFTER `sort`,
	ADD INDEX(`showtj`);");
	}

	if(!pdo_fieldexists2("ddwx_choujiang", "use_type")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_choujiang` ADD COLUMN `use_type`  tinyint(1) DEFAULT '1' COMMENT '消耗类型 1：消耗积分 2：消耗余额' AFTER `gettj`;");
	}

	if(!pdo_fieldexists2("ddwx_choujiang", "usemoney")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_choujiang` ADD COLUMN `usemoney` decimal(10, 2) DEFAULT 0 COMMENT '消耗余额' AFTER `usescore`;");
	}

	if(!pdo_fieldexists2("ddwx_member", "bankaddress")){
		\think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `bankaddress` varchar(255) DEFAULT NULL COMMENT '所属分支行' AFTER `bankname`;");
	}
	if(!pdo_fieldexists2("ddwx_shop_product", "perlimitdan")){
		\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD COLUMN `perlimitdan` int(11) DEFAULT '0' COMMENT '每单限购多少件' AFTER `perlimit`;");
	}

	if(!pdo_fieldexists2("ddwx_shop_sysset", "show_lvupsavemoney")){
		\think\facade\Db::execute("ALTER TABLE ddwx_shop_sysset ADD COLUMN `show_lvupsavemoney` tinyint(1) DEFAULT '0' COMMENT '是否显示升级优惠';");
	}

	if(getcustom('plug_yuebao')){
		if(!pdo_fieldexists2("ddwx_admin_set", "open_yuebao")){
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `open_yuebao` tinyint(1) NULL DEFAULT 0 COMMENT '余额宝功能 0：关闭 1：开启' AFTER `reg_check`;");
		}
		if(!pdo_fieldexists2("ddwx_admin_set", "yuebao_rate")){
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `yuebao_rate` decimal(10, 2) NULL DEFAULT 0 COMMENT '余额宝利率(%)' AFTER `open_yuebao`;");
		}
		if(!pdo_fieldexists2("ddwx_admin_set", "yuebao_withdraw_time")){
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `yuebao_withdraw_time` int(11) NULL DEFAULT 0 COMMENT '余额宝收益提现天数' AFTER `yuebao_rate`;");
		}
		if(!pdo_fieldexists2("ddwx_admin_set", "yuebao_turn_yue")){
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `yuebao_turn_yue` tinyint(1) NULL DEFAULT 0 COMMENT '余额宝收益转余额 0：关闭 1：开启' AFTER `yuebao_withdraw_time`;");
		}
		if(!pdo_fieldexists2("ddwx_admin_set", "yuebao_withdraw")){
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `yuebao_withdraw` tinyint(1) NULL DEFAULT 0 COMMENT '余额宝收益提现 0：关闭 1：开启' AFTER `yuebao_turn_yue`;");
		}
		if(!pdo_fieldexists2("ddwx_admin_set", "yuebao_withdrawmin")){
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `yuebao_withdrawmin` decimal(10, 2) NULL DEFAULT 0 COMMENT '余额宝提现最小金额' AFTER `yuebao_withdraw`;");
		}
		if(!pdo_fieldexists2("ddwx_admin_set", "yuebao_withdrawfee")){
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `yuebao_withdrawfee` decimal(10, 2) NULL DEFAULT 0 COMMENT '余额宝提现手续费(%)' AFTER `yuebao_withdrawmin`;");
		}
		if(!pdo_fieldexists2("ddwx_member", "yuebao_money")){
			\think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `yuebao_money` decimal(10, 2) NULL DEFAULT 0 COMMENT '余额宝收益' AFTER `checkreason`;");
		}
		if(!pdo_fieldexists2("ddwx_member", "yuebao_withdraw_time")){
			\think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `yuebao_withdraw_time` int(11) NULL DEFAULT -1 COMMENT '余额宝收益提现天数' AFTER `yuebao_money`;");
		}
		if(!pdo_fieldexists2("ddwx_member", "yuebao_rate")){
			\think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `yuebao_rate` decimal(10, 2) NULL DEFAULT -1 COMMENT '余额宝收益率' AFTER `yuebao_withdraw_time`;");
		}

		\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_yuebao_moneylog` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) DEFAULT NULL,
		  `mid` int(11) DEFAULT NULL,
		  `money` decimal(11,2) DEFAULT '0.00',
		  `after` decimal(11,2) DEFAULT '0.00',
		  `type` tinyint(1) DEFAULT '0' COMMENT '类型：1、收益增加 2、提现减少 3、转余额减少 4、退款返回',
		  `createtime` int(11) DEFAULT NULL,
		  `remark` varchar(255) DEFAULT NULL,
		  PRIMARY KEY (`id`),
		  KEY `aid` (`aid`) USING BTREE,
		  KEY `mid` (`mid`) USING BTREE
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

		\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_yuebao_withdrawlog` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `aid` int(11) DEFAULT NULL,
		  `mid` int(11) DEFAULT NULL,
		  `money` decimal(11,2) DEFAULT NULL,
		  `txmoney` decimal(11,2) DEFAULT NULL,
		  `aliaccount` varchar(255) DEFAULT NULL,
		  `aliaccountname` varchar(255) DEFAULT NULL COMMENT '支付宝姓名',
		  `ordernum` varchar(255) DEFAULT NULL,
		  `paytype` varchar(255) DEFAULT NULL,
		  `status` tinyint(1) DEFAULT '0' COMMENT '0审核中，1已审核，2已驳回，3已打款',
		  `createtime` int(11) DEFAULT NULL,
		  `bankname` varchar(255) DEFAULT NULL,
		  `bankcarduser` varchar(255) DEFAULT NULL,
		  `bankcardnum` varchar(255) DEFAULT NULL,
		  `paytime` int(11) DEFAULT NULL,
		  `paynum` varchar(255) DEFAULT NULL,
		  `platform` varchar(255) DEFAULT 'wx',
		  `reason` varchar(255) DEFAULT NULL,
		  PRIMARY KEY (`id`),
		  KEY `aid` (`aid`) USING BTREE,
		  KEY `mid` (`mid`) USING BTREE,
		  KEY `createtime` (`createtime`) USING BTREE,
		  KEY `status` (`status`) USING BTREE
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
	}
	if(getcustom('plug_more_alipay')){
		if(!pdo_fieldexists2("ddwx_admin_setapp_h5", "alipay2")){
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay2` tinyint(1) NULL DEFAULT 0 AFTER `ali_publickey`;");
		}
		if(!pdo_fieldexists2("ddwx_admin_setapp_h5", "ali_appid2")){
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid2` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `alipay2`;");
		}
		if(!pdo_fieldexists2("ddwx_admin_setapp_h5", "ali_privatekey2")){
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey2` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `ali_appid2`;");
		}
		if(!pdo_fieldexists2("ddwx_admin_setapp_h5", "ali_publickey2")){
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey2` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `ali_privatekey2`;");
		}

		if(!pdo_fieldexists2("ddwx_admin_setapp_h5", "alipay3")){
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `alipay3` tinyint(1) NULL DEFAULT 0 AFTER `ali_publickey2`;");
		}
		if(!pdo_fieldexists2("ddwx_admin_setapp_h5", "ali_appid3")){
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_appid3` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `alipay3`;");
		}
		if(!pdo_fieldexists2("ddwx_admin_setapp_h5", "ali_privatekey3")){
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_privatekey3` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `ali_appid3`;");
		}
		if(!pdo_fieldexists2("ddwx_admin_setapp_h5", "ali_publickey3")){
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_h5 ADD COLUMN `ali_publickey3` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `ali_privatekey3`;");
		}
	}

	\think\facade\Db::execute("ALTER TABLE `ddwx_alipay_log` MODIFY COLUMN `mch_id` varchar(100) DEFAULT NULL;");

	\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_article_set` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `listtype` tinyint(1) DEFAULT '0',
	  PRIMARY KEY (`id`),
	  KEY `aid` (`aid`),
	  KEY `bid` (`bid`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

	if(!pdo_fieldexists2("ddwx_peisong_set", "express_wx_status")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_peisong_set` 
	ADD COLUMN `express_wx_status` tinyint(1) UNSIGNED NULL DEFAULT '0' AFTER `make_expire_time`,
	ADD COLUMN `express_wx_shopkoufei` tinyint(1) UNSIGNED NULL DEFAULT '0' AFTER `express_wx_status`,
	ADD COLUMN `express_wx_paidan` tinyint(1) UNSIGNED NULL DEFAULT '0' COMMENT '派单方式，0手动，1自动' AFTER `express_wx_shopkoufei`;");
	}

	if(!pdo_fieldexists2("ddwx_shop_order_goods", "total_weight")) {
		\think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` 
	MODIFY COLUMN `cid` varchar(255) NULL DEFAULT '0' AFTER `ggname`,
	ADD COLUMN `total_weight` decimal(11, 2) UNSIGNED NULL DEFAULT '0' AFTER `totalprice`;");
	}

	if(!pdo_fieldexists2("ddwx_member", "iosopenid")) {
		\think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD `iosopenid` varchar(100) DEFAULT NULL AFTER `toutiaoopenid`");

		\think\facade\Db::execute("ALTER TABLE `ddwx_alipay_log` MODIFY COLUMN `mch_id` varchar(100) DEFAULT NULL;");
		\think\facade\Db::execute("ALTER TABLE ddwx_shop_product MODIFY COLUMN wxvideo_reject_reason text COMMENT '视频号商品驳回原因';");

	}
	if(!pdo_fieldexists2("ddwx_tuangou_product","commissiondata1")){
		\think\facade\Db::execute("ALTER TABLE ddwx_tuangou_product
		  ADD COLUMN `commissiondata1` text,
		  ADD COLUMN `commissiondata2` text,
		  ADD COLUMN `commissiondata3` text,
		  ADD COLUMN `scoredkmaxset` tinyint(1) DEFAULT '0',
		  ADD COLUMN `scoredkmaxval` decimal(11,2) DEFAULT '0.00';");
		\think\facade\Db::execute("ALTER TABLE `ddwx_tuangou_order`
		  ADD COLUMN `parent1` int(11) DEFAULT NULL,
		  ADD COLUMN `parent2` int(11) DEFAULT NULL,
		  ADD COLUMN `parent3` int(11) DEFAULT NULL,
		  ADD COLUMN `parent1commission` decimal(11,2) DEFAULT '0.00',
		  ADD COLUMN `parent2commission` decimal(11,2) DEFAULT '0.00',
		  ADD COLUMN `parent3commission` decimal(11,2) DEFAULT '0.00',
		  ADD COLUMN `parent1score` int(11) DEFAULT '0',
		  ADD COLUMN `parent2score` int(11) DEFAULT '0',
		  ADD COLUMN `parent3score` int(11) DEFAULT '0',
		  ADD COLUMN `iscommission` tinyint(1) DEFAULT '0' COMMENT '佣金是否已发放';");
	}

	\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_cashback` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) NULL DEFAULT 0,
	  `bid` int(11) NULL DEFAULT 0,
	  `name` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '名称',
	  `back_ratio` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '返还比率',
	  `back_type` tinyint(1) NULL DEFAULT 0 COMMENT '返还类型 1、余额 2、佣金 3、积分',
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
	  PRIMARY KEY (`id`) USING BTREE,
	  INDEX `aid`(`aid`) USING BTREE,
	  INDEX `bid`(`bid`) USING BTREE,
	  INDEX `status`(`status`) USING BTREE,
	  INDEX `starttime`(`starttime`) USING BTREE,
	  INDEX `endtime`(`endtime`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

	if(!pdo_fieldexists2("ddwx_admin_set", "mode")) {
		\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` 
	ADD COLUMN `mode` int(5) NULL DEFAULT '0' AFTER `logo`;");
	}
	if(!pdo_fieldexists2("ddwx_yuyue_order", "mdid")) {
		\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` ADD COLUMN `mdid` int(11) NULL DEFAULT '0';");
	}

	\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_restaurant_deposit_sysset` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `time` varchar(100) DEFAULT '',
	  `status` tinyint(2) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

	if(!pdo_fieldexists2("ddwx_sign_record","lxqd_coupon_id")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_sign_record` ADD COLUMN `lxqd_coupon_id` int(10) DEFAULT '0' COMMENT '连续签到优惠券id' AFTER `score`;");
	}

	if(!pdo_fieldexists2("ddwx_sign_record","lxzs_coupon_id")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_sign_record` ADD COLUMN `lxzs_coupon_id` int(10) DEFAULT '0' COMMENT '连续赠送优惠券id' AFTER `lxqd_coupon_id`;");
	}

	if(!pdo_fieldexists2("ddwx_shop_product","price_type")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `price_type` tinyint(1) UNSIGNED NULL DEFAULT '0'  COMMENT '价格模式：0默认，1询价' AFTER `cost_price`;");
	}


	if(!pdo_fieldexists2("ddwx_signset","display")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_signset` ADD COLUMN `display` tinyint(1) UNSIGNED NULL DEFAULT '0'  COMMENT '是否展示排名' AFTER `status`;");
	}

	if(!pdo_fieldexists2("ddwx_shop_order","express_type")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `express_type` varchar(255) NULL COMMENT '物流类型' AFTER `express_no`;");
	}
	if(!pdo_fieldexists2("ddwx_restaurant_takeaway_order","express_type")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_order` ADD COLUMN `express_type` varchar(255) NULL COMMENT '物流类型' AFTER `express_no`;");
	}

	if(!pdo_fieldexists2("ddwx_member_level","teamfenhong_pingji_lv")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `teamfenhong_pingji_lv` int(11) DEFAULT '1' AFTER `teamfenhong_self`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_admin_notice` MODIFY COLUMN `content` longtext;");
	}

	if(!pdo_fieldexists2("ddwx_restaurant_product","linkid")) {
		\think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product` 
	ADD COLUMN `linkid` int(11) NULL DEFAULT '0' AFTER `ischecked`;");
	}

	if(!pdo_fieldexists2("ddwx_admin_setapp_mp","sxpay_mno")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_mp` ADD COLUMN `sxpay_mno` varchar(100) DEFAULT NULL;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_wx` ADD COLUMN `sxpay_mno` varchar(100) DEFAULT NULL;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` MODIFY COLUMN `balance_price` decimal(10,2);");
	}
	if(!pdo_fieldexists2("ddwx_admin_setapp_mp","sxpay_mchkey")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_mp` ADD COLUMN `sxpay_mchkey` varchar(100) DEFAULT NULL;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_wx` ADD COLUMN `sxpay_mchkey` varchar(100) DEFAULT NULL;");
	}

	if(pdo_fieldexists2("ddwx_restaurant_product","pack_fee")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_product`
	MODIFY COLUMN `pack_fee` decimal(10, 1) NULL DEFAULT '0.0' COMMENT '打包费' AFTER `cost_price`;");
	}

	if(!pdo_fieldexists2("ddwx_wxpay_log","refund_money")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_wxpay_log` ADD `refund_money` decimal(11,2) DEFAULT '0.00';");
		\think\facade\Db::execute("ALTER TABLE `ddwx_wxpay_log` MODIFY COLUMN `mch_id` varchar(100) DEFAULT NULL;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_wxrefund_log` MODIFY COLUMN `mch_id` varchar(100) DEFAULT NULL;");
	}
	if(!pdo_fieldexists2("ddwx_kefu_message","iswx")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_kefu_message` ADD `iswx` tinyint(1) DEFAULT '0';");
	}

	if(!pdo_fieldexists2("ddwx_shop_sysset","receiving_address_name")){
		\think\facade\Db::execute("ALTER TABLE ddwx_shop_sysset ADD `receiving_address_name` varchar(255) DEFAULT NULL;");
		\think\facade\Db::execute("ALTER TABLE ddwx_shop_sysset ADD `receiving_address_tel` varchar(255) DEFAULT NULL;");
		\think\facade\Db::execute("ALTER TABLE ddwx_shop_sysset ADD `receiving_address_province` varchar(255) DEFAULT NULL;");
		\think\facade\Db::execute("ALTER TABLE ddwx_shop_sysset ADD `receiving_address_city` varchar(255) DEFAULT NULL;");
		\think\facade\Db::execute("ALTER TABLE ddwx_shop_sysset ADD `receiving_address_area` varchar(255) DEFAULT NULL;");
		\think\facade\Db::execute("ALTER TABLE ddwx_shop_sysset ADD `receiving_address_address` varchar(255) DEFAULT NULL;");
	}
	if(!pdo_fieldexists2("ddwx_shop_refund_order","aftersale_id")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_shop_refund_order` ADD `aftersale_id` varchar(100) DEFAULT NULL;");
	}

	if(!pdo_fieldexists2("ddwx_wx_tmplset","tmpl_orderfahuo_new")){
		\think\facade\Db::execute("ALTER TABLE ddwx_wx_tmplset ADD `tmpl_orderfahuo_new` varchar(255) DEFAULT NULL;");
		\think\facade\Db::execute("ALTER TABLE ddwx_wx_tmplset ADD `tmpl_tuisuccess_new` varchar(255) DEFAULT NULL;");
		\think\facade\Db::execute("ALTER TABLE ddwx_wx_tmplset ADD `tmpl_tuierror_new` varchar(255) DEFAULT NULL;");
		\think\facade\Db::execute("ALTER TABLE ddwx_wx_tmplset ADD `tmpl_tixiansuccess_new` varchar(255) DEFAULT NULL;");
		\think\facade\Db::execute("ALTER TABLE ddwx_wx_tmplset ADD `tmpl_tixianerror_new` varchar(255) DEFAULT NULL;");
		\think\facade\Db::execute("ALTER TABLE ddwx_wx_tmplset ADD `tmpl_collagesuccess_new` varchar(255) DEFAULT NULL;");
		\think\facade\Db::execute("ALTER TABLE ddwx_wx_tmplset ADD `tmpl_shenhe_new` varchar(255) DEFAULT NULL;");
		\think\facade\Db::execute("update ddwx_shop_order_goods set iscommission=1 where `status`=3 and iscommission=0 and (parent1commission>0 or parent2commission>0 or parent3commission>0);");
	}

	\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_wxpic_cache` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `pic` varchar(255) DEFAULT NULL,
	  `media_id` varchar(255) DEFAULT NULL,
	  `img_url` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;");

	\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_sxpay_income` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `business_code` varchar(100) DEFAULT NULL,
	  `applicationId` varchar(100) DEFAULT NULL,
	  `applicationId_edit` varchar(255) DEFAULT NULL,
	  `subject_type` varchar(100) DEFAULT 'SUBJECT_TYPE_MICRO',
	  `merchant_shortname` varchar(100) DEFAULT NULL,
	  `service_phone` varchar(100) DEFAULT NULL,
	  `mccCd` varchar(255) DEFAULT NULL,
	  `store_name` varchar(100) DEFAULT NULL,
	  `store_province` varchar(100) DEFAULT NULL,
	  `store_city` varchar(100) DEFAULT NULL,
	  `store_area` varchar(100) DEFAULT NULL,
	  `store_street` varchar(100) DEFAULT NULL,
	  `store_entrance_pic` varchar(255) DEFAULT NULL,
	  `indoor_pic` varchar(255) DEFAULT NULL,
	  `store_other_pics` varchar(600) DEFAULT NULL,
	  `business_license_copy` varchar(255) DEFAULT NULL,
	  `business_license_number` varchar(100) DEFAULT NULL,
	  `business_merchant_name` varchar(100) DEFAULT NULL,
	  `business_company_address` varchar(100) DEFAULT NULL,
	  `business_legal_person` varchar(100) DEFAULT NULL,
	  `identity_id_card_copy` varchar(255) DEFAULT NULL,
	  `identity_id_card_national` varchar(255) DEFAULT NULL,
	  `identity_id_card_name` varchar(100) DEFAULT NULL,
	  `identity_id_card_number` varchar(100) DEFAULT NULL,
	  `identity_id_card_valid_time1` varchar(100) DEFAULT NULL,
	  `identity_id_card_valid_time2` varchar(100) DEFAULT NULL,
	  `identity_id_card_valid_time_cq` varchar(100) DEFAULT NULL,
	  `contact_mobile` varchar(100) DEFAULT NULL,
	  `contact_email` varchar(100) DEFAULT NULL,
	  `jiesuan_bank_account_type` varchar(255) DEFAULT NULL,
	  `jiesuan_account_name` varchar(255) DEFAULT NULL,
	  `jiesuan_account_bank` varchar(100) DEFAULT NULL,
	  `jiesuan_bank_name` varchar(100) DEFAULT NULL,
	  `jiesuan_bank_province` varchar(100) DEFAULT NULL,
	  `jiesuan_bank_city` varchar(100) DEFAULT NULL,
	  `jiesuan_bank_area` varchar(100) DEFAULT NULL,
	  `jiesuan_account_number` varchar(100) DEFAULT NULL,
	  `account_license_pic` varchar(255) DEFAULT NULL,
	  `bank_card_pic` varchar(255) DEFAULT NULL,
	  `business_addition_msg` varchar(255) DEFAULT NULL,
	  `business_addition_pics2` varchar(255) DEFAULT NULL,
	  `createtime` int(11) DEFAULT NULL,
	  `delete` tinyint(1) DEFAULT '0',
	  `taskStatus` varchar(10) DEFAULT '-1' COMMENT '-1 未提交 0审核中 1入驻通过 2入驻驳回 5商户修改通过 6 商户修改驳回',
	  `suggestion` varchar(255) DEFAULT NULL,
	  `taskStatus_edit` varchar(10) DEFAULT '-1',
	  `suggestion_edit` varchar(255) DEFAULT NULL,
	  `isEspecial` varchar(255) DEFAULT NULL,
	  `suggestion2` varchar(255) DEFAULT NULL,
	  `specialMerFlagEndTime` varchar(255) DEFAULT NULL,
	  `submchid` varchar(255) DEFAULT NULL,
	  `zfbmchid` varchar(255) DEFAULT NULL,
	  `shiming_status` tinyint(1) DEFAULT '0' COMMENT '0未实名 1审核中 2',
	  `shiming_qrurl` varchar(255) DEFAULT NULL,
	  `mchkey` varchar(100) DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

	if(!pdo_fieldexists2("ddwx_business","province")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_business` 
	ADD COLUMN `province` varchar(100) NULL AFTER `content`,
	ADD COLUMN `city` varchar(100) NULL AFTER `province`,
	ADD COLUMN `district` varchar(100) NULL AFTER `city`;");
	}

	if(!pdo_fieldexists2("ddwx_admin_set","province")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` 
	ADD COLUMN `province` varchar(100) NULL AFTER `tel`,
	ADD COLUMN `city` varchar(100) NULL AFTER `province`,
	ADD COLUMN `district` varchar(100) NULL AFTER `city`;");
	}

	if(!pdo_fieldexists2("ddwx_peisong_set","express_wx_shop_no")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_peisong_set` 
	ADD COLUMN `express_wx_shop_no` varchar(60) NULL COMMENT '商家门店编号' AFTER `express_wx_paidan`;");
	}

	if(!pdo_fieldexists2("ddwx_business","express_wx_shop_no")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_business` 
	ADD COLUMN `express_wx_shop_no` varchar(60) NULL COMMENT '商家门店编号';");
	}

	if(!pdo_fieldexists2("ddwx_member_level","kecheng_discount")){
		\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `kecheng_discount` decimal(11,2) DEFAULT '10.00';");
		\think\facade\Db::execute("ALTER TABLE ddwx_kecheng_list ADD `pcid` varchar(11) DEFAULT '0' COMMENT '商户的课程 所属平台的分类id';");
	}

	if(!pdo_fieldexists2("ddwx_restaurant_table","print_ids")){
		\think\facade\Db::execute("ALTER TABLE  `ddwx_restaurant_table` 
	ADD COLUMN `print_ids` varchar(255) NULL COMMENT '关联打印机' AFTER `orderid`;");
	}

	if(getcustom('to86yk')){
		if(!pdo_fieldexists2("ddwx_shop_sysset","to86yk_user")){
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_sysset ADD `to86yk_user` varchar(100) DEFAULT NULL;");
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_sysset ADD `to86yk_pwd` varchar(100) DEFAULT NULL;");
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD `to86yk_tid` varchar(100) DEFAULT NULL;");
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_order_goods ADD `to86yk_tid` varchar(100) DEFAULT NULL;");
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_order_goods ADD `to86yk_successnum` int(11) DEFAULT 0;");
		}
	}
	if(!pdo_fieldexists2("ddwx_article","pcid")){
		\think\facade\Db::execute("ALTER TABLE ddwx_article ADD `pcid` int(11) DEFAULT '0';");
	}
	\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_restaurant_deposit_order_log` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `order_id` int(11) DEFAULT NULL,
	  `mid` int(11) DEFAULT NULL,
	  `num` varchar(255) DEFAULT NULL,
	  `message` varchar(255) DEFAULT NULL COMMENT '客户备注',
	  `createtime` int(11) DEFAULT NULL,
	  `type` int(11) DEFAULT '0' COMMENT '0存入，1取出',
	  `status` int(3) DEFAULT '1' COMMENT '',
	  `remark` varchar(255) DEFAULT NULL,
	  `platform` varchar(60) DEFAULT 'wx',
	  `waiter_id` int(11) DEFAULT '0' COMMENT '服务员',
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `order_id` (`order_id`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE,
	  KEY `type` (`type`) USING BTREE,
	  KEY `status` (`status`) USING BTREE,
	  KEY `create_time` (`createtime`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='寄存订单记录表';");

	if(!pdo_fieldexists2("ddwx_sxpay_income","store_entrance_pic")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_sxpay_income` 
	MODIFY COLUMN `store_entrance_pic` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `store_street`,
	MODIFY COLUMN `indoor_pic` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `store_entrance_pic`,
	MODIFY COLUMN `business_license_copy` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `store_other_pics`,
	MODIFY COLUMN `identity_id_card_copy` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `business_legal_person`,
	MODIFY COLUMN `identity_id_card_national` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `identity_id_card_copy`;");
	}

	\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_designerpage_tab` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `tabid` varchar(100) DEFAULT '0',
	  `tabindexid` varchar(100) DEFAULT NULL,
	  `content` longtext,
	  `createtime` int(11) DEFAULT NULL,
	  `updatetime` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `tabid` (`tabid`) USING BTREE,
	  KEY `tabindexid` (`tabindexid`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

	if(!pdo_fieldexists2("ddwx_shop_order","wxvideo_order_id")){
		\think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD `wxvideo_order_id` varchar(100) DEFAULT NULL AFTER `scene`;");
	}

	if(!pdo_fieldexists2("ddwx_kecheng_category","pcid")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_category` ADD `pcid` varchar(11) DEFAULT '0' COMMENT '商户的课程分类 所属平台的分类id';");
	}

	if(!pdo_fieldexists2("ddwx_form","fanwei")) {
		\think\facade\Db::execute("ALTER TABLE `ddwx_form`
	ADD COLUMN `fanwei` tinyint(1) DEFAULT '0' AFTER `commissiondata2`,
	ADD COLUMN `fanwei_lng` varchar(100) DEFAULT NULL AFTER `fanwei`,
	ADD COLUMN `fanwei_lat` varchar(100) DEFAULT NULL AFTER `fanwei_lng`,
	ADD COLUMN `fanwei_range` varchar(100) DEFAULT NULL AFTER `fanwei_lat`;");
	}

	if(!pdo_fieldexists2("ddwx_admin_set","fxjiesuantime_delaydays")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD `fxjiesuantime_delaydays` float(10,2) DEFAULT '0' AFTER `fxjiesuantime`;");
	}

	if(!pdo_fieldexists2("ddwx_workorder_chuli","hfremark")) {
		\think\facade\Db::execute("ALTER TABLE `ddwx_workorder_chuli`
	ADD COLUMN `hfremark` varchar(255) DEFAULT NULL AFTER `userid`,
	ADD COLUMN `hftime` int(11) DEFAULT 0 AFTER `hfremark`;");
	}

	if(!pdo_fieldexists2("ddwx_workorder_order","iscomment")) {
		\think\facade\Db::execute("ALTER TABLE `ddwx_workorder_order`
	ADD COLUMN `iscomment` int(2) DEFAULT 0 AFTER `glordernum`,
	ADD COLUMN `comment_status` int(2) DEFAULT 0 AFTER `iscomment`,
	ADD COLUMN `enddate` datetime DEFAULT NULL AFTER `comment_status`;");
	}


	if(!pdo_fieldexists2("ddwx_yuyue_set","formurl")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_set` ADD `formurl` varchar(255) DEFAULT NULL;");
	}

	if(!pdo_fieldexists2("ddwx_workorder_chuli","content_pic")) {
		\think\facade\Db::execute("ALTER TABLE `ddwx_workorder_chuli`
	ADD COLUMN `content_pic` varchar(1000) DEFAULT NULL AFTER `hftime`,
	ADD COLUMN `hfcontent_pic` varchar(1000) DEFAULT NULL AFTER `content_pic`;");
	}

	\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_workorder_huifu` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `logid` int(11) DEFAULT '0',
	  `createtime` int(11) DEFAULT NULL,
	  `mid` int(11) DEFAULT '0' COMMENT '谁处理得',
	  `hfremark` varchar(255) DEFAULT NULL,
	  `hfcontent_pic` varchar(1000) DEFAULT NULL,
	  `clid` int(11) DEFAULT '0' COMMENT '处理的id 针对工单处理的回复记录',
	  `hfuserid` int(11) DEFAULT '0' COMMENT '回复的是哪个用户',
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `pid` (`logid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

	if(!pdo_fieldexists2("ddwx_form_order","form31")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form31` varchar(255) DEFAULT NULL AFTER `form30`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form32` varchar(255) DEFAULT NULL AFTER `form31`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form33` varchar(255) DEFAULT NULL AFTER `form32`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form34` varchar(255) DEFAULT NULL AFTER `form33`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form35` varchar(255) DEFAULT NULL AFTER `form34`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form36` varchar(255) DEFAULT NULL AFTER `form35`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form37` varchar(255) DEFAULT NULL AFTER `form36`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form38` varchar(255) DEFAULT NULL AFTER `form37`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form39` varchar(255) DEFAULT NULL AFTER `form38`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form40` varchar(255) DEFAULT NULL AFTER `form39`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form41` varchar(255) DEFAULT NULL AFTER `form40`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form42` varchar(255) DEFAULT NULL AFTER `form41`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form43` varchar(255) DEFAULT NULL AFTER `form42`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form44` varchar(255) DEFAULT NULL AFTER `form43`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form45` varchar(255) DEFAULT NULL AFTER `form44`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form46` varchar(255) DEFAULT NULL AFTER `form45`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form47` varchar(255) DEFAULT NULL AFTER `form46`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form48` varchar(255) DEFAULT NULL AFTER `form47`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form49` varchar(255) DEFAULT NULL AFTER `form48`;");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form50` varchar(255) DEFAULT NULL AFTER `form49`;");
	}
	if(getcustom('businessindex_showfw')){
		if(!pdo_fieldexists2("ddwx_form_order","form51")){
			\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form51` varchar(255) DEFAULT NULL AFTER `form50`;");
			\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form52` varchar(255) DEFAULT NULL AFTER `form51`;");
			\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form53` varchar(255) DEFAULT NULL AFTER `form52`;");
			\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form54` varchar(255) DEFAULT NULL AFTER `form53`;");
			\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form55` varchar(255) DEFAULT NULL AFTER `form54`;");
			\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form56` varchar(255) DEFAULT NULL AFTER `form55`;");
			\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form57` varchar(255) DEFAULT NULL AFTER `form56`;");
			\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form58` varchar(255) DEFAULT NULL AFTER `form57`;");
			\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form59` varchar(255) DEFAULT NULL AFTER `form58`;");
			\think\facade\Db::execute("ALTER TABLE `ddwx_form_order` ADD `form60` varchar(255) DEFAULT NULL AFTER `form59`;");
		}
	}
	if(!pdo_fieldexists2("ddwx_admin_set","commission_autowithdraw")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD `commission_autowithdraw` tinyint(1) DEFAULT '0' AFTER `commission2money`;");
	}

	\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_kecheng_sysset` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `details_rec` tinyint(1) DEFAULT '1' COMMENT '是否支持详情推荐',
	  `showcommission` tinyint(1) DEFAULT NULL COMMENT '是否显示佣金',
	  `show_lvupsavemoney` tinyint(1) DEFAULT NULL COMMENT '是否显示升级优惠',
	  `upgrade_text` varchar(255) DEFAULT NULL COMMENT '升级优惠文字',
	  PRIMARY KEY (`id`) USING BTREE,
	  UNIQUE KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

	if(!pdo_fieldexists2("ddwx_kecheng_list","kctype")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_list` ADD `kctype` tinyint(2) DEFAULT NULL COMMENT '课程类型 1图文 2音频 3视频 4综合' AFTER `pcid`;");
	}

	if(!pdo_fieldexists2("ddwx_kecheng_list","market_price")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_list` ADD `market_price` decimal(10,2) DEFAULT NULL COMMENT '划线价' AFTER `kctype`;");
	}

	if(!pdo_fieldexists2("ddwx_kecheng_list","lvprice")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_list` ADD `lvprice` tinyint(1) DEFAULT '0' COMMENT '是否开启会员价 不同会员等级设置不同价格' AFTER `market_price`;");
	}

	if(!pdo_fieldexists2("ddwx_kecheng_list","lvprice_data")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_list` ADD `lvprice_data` text AFTER `lvprice`;");
	}
	if(!pdo_fieldexists2("ddwx_business","kfurl")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD `kfurl` varchar(255) DEFAULT NULL;");
	}

	if(!pdo_fieldexists2("ddwx_member_level","commission_parent_pj")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `commission_parent_pj` decimal(11, 2) NOT NULL DEFAULT '0' AFTER `commission_parent`;");
	}


	if(!pdo_fieldexists2("ddwx_shop_product","gdfenhongset")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD `gdfenhongset` int(2) DEFAULT '0' AFTER `fenhongset`");
		\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD `gdfenhongdata1` text AFTER `gdfenhongset`");
		\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD `gdfenhongdata2` text AFTER `gdfenhongdata1`");
		\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD `teamfenhongset` int(2) DEFAULT '0' AFTER `gdfenhongdata2`");
		\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD `teamfenhongdata1` text AFTER `teamfenhongset`");
		\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD `teamfenhongdata2` text AFTER `teamfenhongdata1`");
	}

	\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shop_param` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT '0',
	  `name` varchar(255) DEFAULT NULL,
	  `type` tinyint(1) DEFAULT '0',
	  `params` text,
	  `tips` varchar(255) DEFAULT NULL,
	  `is_required` tinyint(1) DEFAULT '0',
	  `sort` int(11) DEFAULT NULL,
	  `status` tinyint(1) DEFAULT '1',
	  `createtime` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `aid` (`aid`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

	if(!pdo_fieldexists2("ddwx_shop_product","paramdata")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD `paramdata` text AFTER `sales`");
	}

	if(!pdo_fieldexists2("ddwx_luntan_sysset","cansave")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_luntan_sysset` ADD `cansave` tinyint(1) DEFAULT '0' AFTER `pingluncheck`");
	}

	if(!pdo_fieldexists2("ddwx_admin_set", "areafenhong_jiaquan")){
		\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD areafenhong_jiaquan tinyint(1) DEFAULT '0' AFTER `partner_gongxian`");
	}

	if(!pdo_fieldexists2("ddwx_admin_set", "areafenhong_checktype")){
		\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD `areafenhong_checktype` tinyint(1) DEFAULT '0' AFTER areafenhong_jiaquan");
	}

	if(!pdo_fieldexists2("ddwx_form", "form_query")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD `form_query` tinyint(1) DEFAULT '0'");
		\think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD `form_query_type` tinyint(1) DEFAULT '0'");
	}

	\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_largearea` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `name` varchar(255) DEFAULT NULL,
	  `province` text,
	  `status` int(1) DEFAULT '1',
	  `sort` int(11) DEFAULT '1',
	  `createtime` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

	if(!pdo_fieldexists2("ddwx_member", "areafenhong_largearea")){
		\think\facade\Db::execute("ALTER TABLE ddwx_member ADD areafenhong_largearea varchar(255) DEFAULT NULL AFTER areafenhong_area");
	}

	if(!pdo_fieldexists2("ddwx_shop_product", "diypics")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `diypics` varchar(5000) NULL AFTER `pics`;");
	}

	if(!pdo_fieldexists2("ddwx_member_levelup_order", "areafenhong_largearea")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_member_levelup_order` ADD `areafenhong_largearea` varchar(255) DEFAULT NULL AFTER areafenhong_area");
	}

	if(!pdo_fieldexists2("ddwx_member_levelup_order", "areafenhong_largearea")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_member_levelup_order` ADD `areafenhong_largearea` varchar(255) DEFAULT NULL AFTER areafenhong_area");
	}

}



if(!pdo_fieldexists2("ddwx_shop_sysset", "shipping_pagetitle")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` 
ADD COLUMN `shipping_pagetitle` varchar(255) NULL DEFAULT '送货单';");
}
if(!pdo_fieldexists2("ddwx_admin_set", "invoice_rate")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` 
ADD COLUMN `invoice_rate` decimal(5, 2) NULL DEFAULT '0' AFTER `invoice_type`;");
}
if(!pdo_fieldexists2("ddwx_shop_sysset", "pay_month")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` 
ADD COLUMN `pay_month` tinyint(1) DEFAULT '0' AFTER `codtxt`,
ADD COLUMN `pay_month_txt` varchar(60) NULL DEFAULT '月结账户' AFTER `pay_month`;");
}
if(!pdo_fieldexists2("ddwx_shop_order", "invoice_money")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` 
ADD COLUMN `invoice_money` float(11, 2) NULL DEFAULT '0' AFTER `freight_price`;");
}

if(!pdo_fieldexists2("ddwx_admin_setapp_mp", "wxpay_serial_no")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_mp` ADD `wxpay_serial_no` varchar(100) DEFAULT NULL AFTER `wxpay_apiclient_key`");
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_wx` ADD `wxpay_serial_no` varchar(100) DEFAULT NULL AFTER `wxpay_apiclient_key`");

	\think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_record` MODIFY COLUMN `timu` text;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_sxpay_income` MODIFY COLUMN `store_entrance_pic` varchar(255) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_sxpay_income` MODIFY COLUMN `indoor_pic` varchar(255) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_sxpay_income` MODIFY COLUMN `store_other_pics` varchar(600) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_sxpay_income` MODIFY COLUMN `business_license_copy` varchar(255) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_sxpay_income` MODIFY COLUMN `identity_id_card_copy` varchar(255) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_sxpay_income` MODIFY COLUMN `identity_id_card_national` varchar(255) DEFAULT NULL;");
}


if(!pdo_fieldexists2("ddwx_form", "form_query_bgcolor")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD `form_query_bgcolor` varchar(100) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE `ddwx_form` ADD `form_query_txtcolor` varchar(100) DEFAULT NULL");
}
if(!pdo_fieldexists2("ddwx_member_level", "teamfenhong_pingji_type")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `teamfenhong_pingji_type` tinyint(1) NULL DEFAULT '0' AFTER `teamfenhong_pingji_money`");
}

if(!pdo_fieldexists2("ddwx_wx_tmplset", "tmpl_orderconfirm")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_wx_tmplset` ADD `tmpl_orderconfirm` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE `ddwx_wx_tmplset` ADD `tmpl_ordershouhuo` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE `ddwx_wx_tmplset` ADD `tmpl_ordertui` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE `ddwx_wx_tmplset` ADD `tmpl_withdraw` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE `ddwx_wx_tmplset` ADD `tmpl_kehuzixun` varchar(255) DEFAULT NULL");
}



\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_wx_url` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `endtime` int(11) DEFAULT NULL,
  `days` int(11) DEFAULT '30',
  `sort` int(11) DEFAULT '0',
  `code` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

if(!pdo_fieldexists2("ddwx_member_level", "teamfenhong_removemax")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `teamfenhong_removemax` tinyint(1) DEFAULT '0' AFTER `teamfenhong_self`");
	if(pdo_fieldexists2("ddwx_shop_refund_order_goods", "cid")){
		\think\facade\Db::execute("ALTER TABLE `ddwx_shop_refund_order_goods` CHANGE `cid` `cid` VARCHAR(255) NULL DEFAULT '0';");
	}
}

if(!pdo_fieldexists2("ddwx_member", "isfreeze")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD `isfreeze` tinyint(1) DEFAULT '0';");
}
if(!pdo_fieldexists2("ddwx_yuyue_product", "couponids")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD `couponids` varchar(255) DEFAULT NULL COMMENT '预约次卡id';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD `yytimeday` varchar(255) DEFAULT NULL");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_sxpay_fenzhang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `business_code` varchar(100) DEFAULT NULL,
  `fenzhangdata` text,
  `createtime` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  `sort` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");


if(!pdo_fieldexists2("ddwx_shop_product","areafenhongset")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD `areafenhongset` int(2) DEFAULT '0' AFTER `teamfenhongdata2`");
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD `areafenhongdata1` text AFTER `areafenhongset`");
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD `areafenhongdata2` text AFTER `areafenhongdata1`");
}

if(!pdo_fieldexists2("ddwx_admin_set","areafenhong_differential")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD `areafenhong_differential` tinyint(1) DEFAULT '0' AFTER `teamfenhong_differential`");
}

if(!pdo_fieldexists2("ddwx_admin_setapp_alipay","sxpay")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_alipay` ADD `sxpay` tinyint(1) DEFAULT '0'");
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_alipay` ADD `sxpay_mno` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_alipay` ADD `sxpay_mchkey` varchar(255) DEFAULT NULL");
}

if(!pdo_fieldexists2("ddwx_shop_param","cid")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_param` ADD COLUMN `cid` varchar(255) DEFAULT '' AFTER `name`;");
}

if(!pdo_fieldexists2("ddwx_admin_set","wxkftransfer")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `wxkftransfer` tinyint(1) DEFAULT '0' AFTER `corpid`;");
}

if(!pdo_fieldexists2("ddwx_admin","order_show_onlychildren")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin` 
	ADD COLUMN `order_show_onlychildren` tinyint(1) NULL DEFAULT '0';");
}

if(!pdo_fieldexists2("ddwx_admin_set_xieyi","name2")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_xieyi` ADD COLUMN `name2` varchar(255) DEFAULT '' AFTER `content`;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_xieyi` ADD COLUMN `content2` longtext AFTER `name2`;");
}

if(!pdo_fieldexists2("ddwx_designerpage","gid")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_designerpage` ADD COLUMN `gid` int(11) NULL DEFAULT '0' AFTER `ishome`;");
}

if(!pdo_fieldexists2("ddwx_shop_sysset","hide_stock")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `hide_stock` tinyint(1) DEFAULT '0' COMMENT '是否隐藏库存' AFTER `hide_sales`;");
}
if(!pdo_fieldexists2("ddwx_business","sxpay_mno")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `sxpay_mno` varchar(100) DEFAULT NULL;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `sxpay_mchkey` varchar(100) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_yuyue_product","minbuynum")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD COLUMN `minbuynum` int(11) DEFAULT 1;");
}

if (!pdo_fieldexists2("ddwx_yuyue_worker","apply_paymoney")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_worker` 
	ADD COLUMN `apply_paymoney` decimal(11,2) DEFAULT '0.00' COMMENT '申请时需要支付的费用',
	ADD COLUMN `sex` tinyint(2) DEFAULT '0' COMMENT '性别',
	ADD COLUMN `age` int(11) DEFAULT '0' COMMENT '年龄',
	ADD COLUMN `citys` varchar(255) DEFAULT '' COMMENT '服务城市',
	ADD COLUMN `fuwu_juli` int(11) DEFAULT '5' COMMENT '服务公里数',
	ADD COLUMN `codepic` varchar(255) DEFAULT '',
	ADD COLUMN `otherpic` varchar(255) DEFAULT '',
	ADD COLUMN `reason` varchar(255) DEFAULT NULL COMMENT '驳回原因',
	ADD COLUMN `ordernum` varchar(255) DEFAULT '',
	ADD COLUMN `shstatus` tinyint(2) DEFAULT '1' COMMENT '0 未支付 1已通过  2 已驳回';");
}


if (!pdo_fieldexists2("ddwx_yuyue_set","apply_paymoney")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_set` 
	ADD COLUMN `apply_paymoney` decimal(11,2) DEFAULT '0.00' COMMENT '申请时需要支付的费用',
	ADD COLUMN `xieyi_show` tinyint(2) DEFAULT '0' COMMENT '开启入驻协议 1 开启',
	ADD COLUMN `xieyi` text,
	ADD COLUMN `sign_juli` decimal(10) DEFAULT '0' COMMENT '师傅签到距离';");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yuyue_workerapply_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) unsigned DEFAULT '0',
  `mid` int(11) DEFAULT NULL,
  `worker_id` int(11) DEFAULT NULL COMMENT '师傅id',
  `ordernum` varchar(100) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0' COMMENT '1已支付',
  `payorderid` int(11) DEFAULT NULL,
  `paytypeid` int(11) DEFAULT NULL,
  `paytype` varchar(50) DEFAULT NULL,
  `paynum` varchar(100) DEFAULT NULL,
  `paytime` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `platform` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `psid` (`worker_id`) USING BTREE,
  KEY `ordernum` (`ordernum`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");


if(!pdo_fieldexists2("ddwx_member_level","up_give_parent_coupon_ids")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_give_parent_coupon_ids` varchar(255) DEFAULT NULL AFTER `up_give_parent_money`;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_give_parent_coupon_nums` varchar(255) DEFAULT NULL AFTER `up_give_parent_coupon_ids`;");
}

if(!pdo_fieldexists2("ddwx_yuyue_order","addmoney")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` ADD COLUMN `addmoney` decimal(10,2) DEFAULT 0");
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` ADD COLUMN `addmoneyPaycode` varchar(255) DEFAULT NUll");
}


if(!pdo_fieldexists2("ddwx_yuyue_order","addmoneyStatus")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` ADD COLUMN `addmoneyStatus` int(11) DEFAULT 0");
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` ADD COLUMN `addmoneyPayorderid` int(11) DEFAULT 0");
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin` MODIFY COLUMN `linkman` varchar(60) NULL DEFAULT '';");
}

if(!pdo_fieldexists2("ddwx_member_level", "maidan_commission_score1")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` 
	        ADD `maidan_commission_score1` int(11) DEFAULT 0,
            ADD `maidan_commission_score2` int(11) DEFAULT 0,
            ADD `maidan_commission_score3` int(11) DEFAULT 0;");
}

if(getcustom('pay_yuanbao')){
		if(!pdo_fieldexists2("ddwx_admin_set", "yuanbao_transfer")){
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `yuanbao_transfer` tinyint(1) NOT NULL DEFAULT 0 COMMENT '元宝转账是否开启0：未开启 1：开启';");
		}
		if(!pdo_fieldexists2("ddwx_admin_set", "yuanbao_money_ratio")){
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `yuanbao_money_ratio` decimal(10) NOT NULL DEFAULT 0 COMMENT '元宝与现金兑换比例' AFTER `yuanbao_transfer`;");
		}
		if(!pdo_fieldexists2("ddwx_admin_set", "yuanbao_pay")){
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `yuanbao_pay` tinyint(1) NOT NULL DEFAULT 0 COMMENT '元宝支付0：未开启 1：开启' AFTER `yuanbao_money_ratio`;");
		}
		if(!pdo_fieldexists2("ddwx_member", "yuanbao")){
			\think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `yuanbao` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '元宝' AFTER `isfreeze`;");
		}
		if(!pdo_fieldexists2("ddwx_shop_product", "yuanbao")){
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD COLUMN `yuanbao` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '元宝';");
		}
		if(!pdo_fieldexists2("ddwx_shop_order", "total_yuanbao")){
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD COLUMN `total_yuanbao` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '元宝价' AFTER `sysOrderNo`;");
		}
		if(!pdo_fieldexists2("ddwx_shop_order", "is_yuanbao_pay")){
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD COLUMN `is_yuanbao_pay` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否是元宝支付0：否1：是' AFTER `total_yuanbao`;");
		}
		if(!pdo_fieldexists2("ddwx_shop_order", "yuanbao_money")){
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD COLUMN `yuanbao_money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '元宝现金金额' AFTER `is_yuanbao_pay`;");
		}
		if(!pdo_fieldexists2("ddwx_shop_order", "have_no_yuanbao")){
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD COLUMN `have_no_yuanbao` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否有非元宝商品' AFTER `yuanbao_money`;");
		}
		if(!pdo_fieldexists2("ddwx_shop_order_goods", "yuanbao")){
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_order_goods ADD COLUMN `yuanbao` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '元宝价';");
		}
		if(!pdo_fieldexists2("ddwx_shop_order_goods", "total_yuanbao")){
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_order_goods ADD COLUMN `total_yuanbao` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '总元宝价' AFTER `yuanbao`;");
		}
		if(!pdo_fieldexists2("ddwx_shop_order_goods", "yuanbao_money")){
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_order_goods ADD COLUMN `yuanbao_money` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '元宝现金金额' AFTER `total_yuanbao`;");
		}

		if(!pdo_fieldexists2("ddwx_payorder", "is_yuanbao_pay")){
			\think\facade\Db::execute("ALTER TABLE ddwx_payorder ADD COLUMN `is_yuanbao_pay` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否是元宝支付0：否1：是' AFTER `isbusinesspay`;");
		}
		if(!pdo_fieldexists2("ddwx_payorder", "yuanbao_money")){
			\think\facade\Db::execute("ALTER TABLE ddwx_payorder ADD COLUMN `yuanbao_money` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '元宝现金金额' AFTER `is_yuanbao_pay`;");
		}

		\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_yuanbaolog`  (
		    `id` int NOT NULL AUTO_INCREMENT,
		    `aid` int(11) NOT NULL DEFAULT 0,
		    `mid` int(11) NOT NULL DEFAULT 0 COMMENT '转账人id',
		    `yuanbao` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '元宝',
		    `after` decimal(10, 2) NOT NULL DEFAULT 0,
		    `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
		    `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1普通',
		    `createtime` int(11) UNSIGNED NOT NULL DEFAULT 0,
		    PRIMARY KEY (`id`)
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

		\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_yuanbao_transfer_order` (
		  `id` int NOT NULL AUTO_INCREMENT,
		    `aid` int(11) NOT NULL DEFAULT 0,
		    `mid` int(11) NOT NULL DEFAULT 0 COMMENT '转账人id',
		    `to_mid` int NOT NULL DEFAULT 0 COMMENT '接收人id',
		    `ordernum` varchar(30) NOT NULL DEFAULT '' COMMENT '订单',
		    `money` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '金额',
		    `yuanbao` decimal(10, 2) NOT NULL DEFAULT 0 COMMENT '元宝',
		    `payorderid` int(11) NOT NULL DEFAULT 0,
		    `paytypeid` int(11) NOT NULL DEFAULT 0,
		    `paytype` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
		    `paynum` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
		    `paytime` int(11) UNSIGNED NOT NULL DEFAULT 0,
		    `platform` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'wx',
		    `parent1` int(11) NULL DEFAULT NULL,
		    `parent2` int(11) NULL DEFAULT NULL,
		    `parent3` int(11) NULL DEFAULT NULL,
		    `parent1commission` decimal(11, 2) NULL DEFAULT 0.00,
		    `parent2commission` decimal(11, 2) NULL DEFAULT 0.00,
		    `parent3commission` decimal(11, 2) NULL DEFAULT 0.00,
		    `iscommission` tinyint(1) NULL DEFAULT 0 COMMENT '佣金是否已发放',
		    `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态0：未支付 1：已支付',
		    `create_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
		    PRIMARY KEY (`id`)
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");
	}

if (!pdo_fieldexists2("ddwx_yuyue_order","parent1")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` 
	ADD COLUMN `parent1` int(11) DEFAULT NULL,
	ADD COLUMN `parent2` int(11) DEFAULT NULL,
	ADD COLUMN `parent3` int(11) DEFAULT NULL,
	ADD COLUMN `parent1commission` decimal(11,2) DEFAULT '0.00',
	ADD COLUMN `parent2commission` decimal(11,2) DEFAULT '0.00',
	ADD COLUMN `parent3commission` decimal(11,2) DEFAULT '0.00',
	ADD COLUMN `parent1score` int(11) DEFAULT '0',
	ADD COLUMN `parent2score` int(11) DEFAULT '0',
	ADD COLUMN `parent3score` int(11) DEFAULT '0',
	ADD COLUMN `iscommission` tinyint(1) DEFAULT '0' COMMENT '佣金是否已发放';");
}
if (!pdo_fieldexists2("ddwx_yuyue_worker_order","sign_status")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_worker_order` 
	ADD COLUMN `sign_status` tinyint(11) DEFAULT 0,
	ADD COLUMN `sign_time` int(11) DEFAULT '0';");
}

if (!pdo_fieldexists2("ddwx_yuyue_product","commissionset")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` 
	ADD COLUMN `commissionset` tinyint(1) DEFAULT '0',
	ADD COLUMN `commissiondata1` text,
	ADD COLUMN `commissiondata2` text,
	ADD COLUMN `commissiondata3` text;");
}
if (!pdo_fieldexists2("ddwx_admin_setapp_wx","wxpay_sub_mchid2")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_wx` ADD COLUMN `wxpay_sub_mchid2` varchar(100) DEFAULT NULL AFTER `wxpay_sub_mchid`;");
}
if (!pdo_fieldexists2("ddwx_shop_product","givescore_time")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `givescore_time` tinyint(1) DEFAULT '0';");
}
if (!pdo_fieldexists2("ddwx_shop_order","givescore2")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `givescore2` int(11) DEFAULT '0' COMMENT '赠送积分2' AFTER `givescore`;");
}
if (!pdo_fieldexists2("ddwx_business_sysset","moneypay")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `moneypay` tinyint(1) DEFAULT '0';");
}

if (!pdo_fieldexists2("ddwx_business_sysset","scorein_money")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `scorein_money` decimal(11,2) DEFAULT '1.00';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `scorein_score` int(11) DEFAULT '0';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `parentcommission` decimal(11,2) DEFAULT '0';");
}

if (!pdo_fieldexists2("ddwx_yuyue_worker","fwcids")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_worker` ADD COLUMN `fwcids` varchar(255) DEFAULT NULL;");
}

if(!pdo_fieldexists2("ddwx_yuyue_set","apply_url")){
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `apply_url` varchar(255) DEFAULT NULL");
}

if(!pdo_fieldexists2("ddwx_yuyue_set","ad_status")){
    \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `ad_status` tinyint(1) DEFAULT '0';");
    \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `ad_pic` varchar(255) DEFAULT NULL;");
    \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `ad_link` varchar(255) DEFAULT NULL;");
    \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `video_status` tinyint(1) DEFAULT '0';");
}
if(!pdo_fieldexists2("ddwx_yuyue_set","video_tag")){
    \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `video_tag` varchar(255) DEFAULT NULL;");
    \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `video_title` varchar(255) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_shop_sysset","coupon_peruselimit")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD `coupon_peruselimit` int(11) DEFAULT '1'");
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` MODIFY COLUMN `coupon_rid` varchar(255) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_coupon","yuyue_productids")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` 
MODIFY COLUMN `fwtype` tinyint(1) NULL DEFAULT '0' COMMENT '0全场通用,1指定类目,2指定商品,3指定菜品,4指定服务商品' AFTER `paygive_maxprice`,
ADD COLUMN `yuyue_productids` varchar(255) DEFAULT NULL COMMENT '指定服务商品ids' AFTER `restaurant_productids`;");
}
if(!pdo_fieldexists2("ddwx_lipin_codelist","qr")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_lipin_codelist` ADD `qr` varchar(255) DEFAULT NULL AFTER `code`");

	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_comment` MODIFY COLUMN `content_pic` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `content`;");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_lipin_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `scanshow` tinyint(1) DEFAULT '0',
  `guize` longtext,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

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
	\think\facade\Db::execute("ALTER TABLE ddwx_manjian_set ADD COLUMN `total_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '总消费额满减 0：不开启 1：开启';");
}

if(!pdo_fieldexists2("ddwx_choujiang", "add_num_score")){
	\think\facade\Db::execute("ALTER TABLE ddwx_choujiang ADD COLUMN `add_num_score` int(11) NOT NULL DEFAULT 0 COMMENT '增加次数消耗积分';");
}
if(!pdo_fieldexists2("ddwx_choujiang", "limit_score")){
	\think\facade\Db::execute("ALTER TABLE ddwx_choujiang ADD COLUMN `limit_score` int(11) NOT NULL DEFAULT 0 COMMENT '封顶积分';");
}
if(!pdo_fieldexists2("ddwx_choujiang_record", "score")){
	\think\facade\Db::execute("ALTER TABLE ddwx_choujiang_record ADD COLUMN `score` int(11) NOT NULL DEFAULT 0 COMMENT '积分';");
}

if(getcustom('plug_tengrui')){

		if(!pdo_fieldexists2("ddwx_member", "tr_is_rzh")){
			\think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `tr_is_rzh` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否认证0：否 1：是' ;");
		}
		if(!pdo_fieldexists2("ddwx_member", "tr_openId")){
			\think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `tr_openId` varchar(30) NOT NULL DEFAULT '' COMMENT '公众号openId' ;");
		}
		if(!pdo_fieldexists2("ddwx_member", "tr_name")){
			\think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `tr_name` varchar(30) NOT NULL DEFAULT '' COMMENT '客户名称' ;");
		}
		if(!pdo_fieldexists2("ddwx_member", "tr_communityName")){
			\think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `tr_communityName` varchar(30) NOT NULL DEFAULT '' COMMENT '小区名称' ;");
		}
		if(!pdo_fieldexists2("ddwx_member", "tr_phoneNum")){
			\think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `tr_phoneNum` varchar(11) NOT NULL DEFAULT '' COMMENT '手机号';");
		}
		if(!pdo_fieldexists2("ddwx_member", "tr_id")){
			\think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `tr_id` int NOT NULL DEFAULT 0 COMMENT 'id';");
		}
		if(!pdo_fieldexists2("ddwx_member", "tr_communityId")){
			\think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `tr_communityId` varchar(255) NOT NULL DEFAULT '' COMMENT '小区id，多个用逗号拼接';");
		}
		if(!pdo_fieldexists2("ddwx_member", "tr_roomId")){
			\think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `tr_roomId` varchar(255) NOT NULL DEFAULT '' COMMENT '房间id，多个用逗号拼接';");
		}
		if(!pdo_fieldexists2("ddwx_member", "tr_relationType")){
			\think\facade\Db::execute("ALTER TABLE ddwx_member ADD COLUMN `tr_relationType` varchar(255) NOT NULL DEFAULT '' COMMENT '业务关系 0：业主 1、家属 2、租户 3买断 4、租用，多个用逗号拼接';");
		}

		if(!pdo_fieldexists2("ddwx_coupon", "house_status")){
			\think\facade\Db::execute("ALTER TABLE ddwx_coupon ADD COLUMN `house_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否一户领取一次';");
		}
		if(!pdo_fieldexists2("ddwx_coupon", "group_status")){
			\think\facade\Db::execute("ALTER TABLE ddwx_coupon ADD COLUMN `group_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '用户分组 0：未开启 1：开启';");
		}
		if(!pdo_fieldexists2("ddwx_coupon", "group_ids")){
			\think\facade\Db::execute("ALTER TABLE ddwx_coupon ADD COLUMN `group_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '小区id，逗号拼接';");
		}
		if(!pdo_fieldexists2("ddwx_coupon", "is_rzh")){
			\think\facade\Db::execute("ALTER TABLE ddwx_coupon ADD COLUMN `is_rzh` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否认证0：否 1：是';");
		}
		if(!pdo_fieldexists2("ddwx_coupon", "relation_type")){
			\think\facade\Db::execute("ALTER TABLE ddwx_coupon ADD COLUMN `relation_type` tinyint(1) NOT NULL DEFAULT -1 COMMENT '用户身份-1：所有 0：业主 1、家属 2、租户 3买断 4、租用';");
		}

		if(!pdo_fieldexists2("ddwx_choujiang_record", "tr_roomId")){
			\think\facade\Db::execute("ALTER TABLE ddwx_choujiang_record ADD COLUMN `tr_roomId` int(11) NOT NULL DEFAULT 0 COMMENT '房间id';");
		}

		if(!pdo_fieldexists2("ddwx_coupon_record", "tr_roomId")){
			\think\facade\Db::execute("ALTER TABLE ddwx_coupon_record ADD COLUMN `tr_roomId` int(11) NOT NULL DEFAULT 0 COMMENT '房间id';");
		}

		if(!pdo_fieldexists2("ddwx_coupon_order", "tr_roomId")){
			\think\facade\Db::execute("ALTER TABLE ddwx_coupon_order ADD COLUMN `tr_roomId` int(11) NOT NULL DEFAULT 0 COMMENT '房间id';");
		}

		if(!pdo_fieldexists2("ddwx_shop_product", "house_status")){
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD COLUMN `house_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否一户仅限一次' ;");
		}
		if(!pdo_fieldexists2("ddwx_shop_product", "group_status")){
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD COLUMN `group_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '用户分组 0：未开启 1：开启';");
		}
		if(!pdo_fieldexists2("ddwx_shop_product", "group_ids")){
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD COLUMN `group_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '小区id，逗号拼接';");
		}
		if(!pdo_fieldexists2("ddwx_shop_product", "is_rzh")){
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD COLUMN `is_rzh` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否认证0：否 1：是';");
		}
		if(!pdo_fieldexists2("ddwx_shop_product", "relation_type")){
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD COLUMN `relation_type` tinyint(1) NOT NULL DEFAULT -1 COMMENT '用户身份-1：所有 0：业主 1、家属 2、租户 3买断 4、租用';");
		}

		if(!pdo_fieldexists2("ddwx_shop_order_goods", "tr_roomId")){
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_order_goods ADD COLUMN `tr_roomId` int(11) NOT NULL DEFAULT 0 COMMENT '房间id';");
		}

		if(!pdo_fieldexists2("ddwx_tuangou_product", "house_status")){
			\think\facade\Db::execute("ALTER TABLE ddwx_tuangou_product ADD COLUMN `house_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否一户仅限一次';");
		}
		if(!pdo_fieldexists2("ddwx_tuangou_product", "is_rzh")){
			\think\facade\Db::execute("ALTER TABLE ddwx_tuangou_product ADD COLUMN `is_rzh` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否认证0：否 1：是';");
		}
		if(!pdo_fieldexists2("ddwx_tuangou_product", "relation_type")){
			\think\facade\Db::execute("ALTER TABLE ddwx_tuangou_product ADD COLUMN `relation_type` tinyint(1) NOT NULL DEFAULT -1 COMMENT '用户身份-1：所有 0：业主 1、家属 2、租户 3买断 4、租用';");
		}

		if(!pdo_fieldexists2("ddwx_tuangou_order", "tr_roomId")){
			\think\facade\Db::execute("ALTER TABLE ddwx_tuangou_order ADD COLUMN `tr_roomId` int(11) NOT NULL DEFAULT 0 COMMENT '房间id';");
		}

		if(!pdo_fieldexists2("ddwx_seckill_product", "house_status")){
			\think\facade\Db::execute("ALTER TABLE ddwx_seckill_product ADD COLUMN `house_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否一户仅限一次';");
		}
		if(!pdo_fieldexists2("ddwx_seckill_product", "is_rzh")){
			\think\facade\Db::execute("ALTER TABLE ddwx_seckill_product ADD COLUMN `is_rzh` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否认证0：否 1：是';");
		}
		if(!pdo_fieldexists2("ddwx_seckill_product", "relation_type")){
			\think\facade\Db::execute("ALTER TABLE ddwx_seckill_product ADD COLUMN `relation_type` tinyint(1) NOT NULL DEFAULT -1 COMMENT '用户身份-1：所有 0：业主 1、家属 2、租户 3买断 4、租用';");
		}

		if(!pdo_fieldexists2("ddwx_seckill_order", "tr_roomId")){
			\think\facade\Db::execute("ALTER TABLE ddwx_seckill_order ADD COLUMN `tr_roomId` int(11) NOT NULL DEFAULT 0 COMMENT '房间id';");
		}

		if(!pdo_fieldexists2("ddwx_manjian_set", "house_status")){
			\think\facade\Db::execute("ALTER TABLE ddwx_manjian_set ADD COLUMN `house_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否一户仅限一次';");
		}
		if(!pdo_fieldexists2("ddwx_manjian_set", "is_rzh")){
			\think\facade\Db::execute("ALTER TABLE ddwx_manjian_set ADD COLUMN `is_rzh` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否认证0：否 1：是';");
		}
		if(!pdo_fieldexists2("ddwx_manjian_set", "relation_type")){
			\think\facade\Db::execute("ALTER TABLE ddwx_manjian_set ADD COLUMN `relation_type` tinyint(1) NOT NULL DEFAULT -1 COMMENT '用户身份-1：所有 0：业主 1、家属 2、租户 3买断 4、租用';");
		}

		if(!pdo_fieldexists2("ddwx_cuxiao", "house_status")){
			\think\facade\Db::execute("ALTER TABLE ddwx_cuxiao ADD COLUMN `house_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否一户仅限一次';");
		}
		if(!pdo_fieldexists2("ddwx_cuxiao", "is_rzh")){
			\think\facade\Db::execute("ALTER TABLE ddwx_cuxiao ADD COLUMN `is_rzh` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否认证0：否 1：是';");
		}
		if(!pdo_fieldexists2("ddwx_cuxiao", "relation_type")){
			\think\facade\Db::execute("ALTER TABLE ddwx_cuxiao ADD COLUMN `relation_type` tinyint(1) NOT NULL DEFAULT -1 COMMENT '用户身份-1：所有 0：业主 1、家属 2、租户 3买断 4、租用';");
		}

		if(!pdo_fieldexists2("ddwx_shop_order", "cuxiao_money")){
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD COLUMN `cuxiao_money` decimal(11, 2)  NOT NULL  DEFAULT 0.00 COMMENT '促销金额';");
		}
		if(!pdo_fieldexists2("ddwx_shop_order", "cuxiao_tr_roomId")){
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD COLUMN `cuxiao_tr_roomId` int(11) NOT NULL DEFAULT 0 COMMENT '促销房间id';");
		}
		if(!pdo_fieldexists2("ddwx_shop_order", "manjian_tr_roomId")){
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD COLUMN `manjian_tr_roomId` int(11) NOT NULL DEFAULT 0 COMMENT '满减房间id';");
		}
		if(!pdo_fieldexists2("ddwx_shop_order", "cuxiao_id")){
			\think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD COLUMN `cuxiao_id` int NOT NULL DEFAULT 0 COMMENT '促销id';");
		}
	

		if(!pdo_fieldexists2("ddwx_cashback", "house_status")){
			\think\facade\Db::execute("ALTER TABLE ddwx_cashback ADD COLUMN `house_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否一户仅限一次';");
		}
		if(!pdo_fieldexists2("ddwx_cashback", "is_rzh")){
			\think\facade\Db::execute("ALTER TABLE ddwx_cashback ADD COLUMN `is_rzh` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否认证0：否 1：是';");
		}
		if(!pdo_fieldexists2("ddwx_cashback", "relation_type")){
			\think\facade\Db::execute("ALTER TABLE ddwx_cashback ADD COLUMN `relation_type` tinyint(1) NOT NULL DEFAULT -1 COMMENT '用户身份-1：所有 0：业主 1、家属 2、租户 3买断 4、租用';");
		}

		if(!pdo_fieldexists2("ddwx_choujiang", "house_status")){
			\think\facade\Db::execute("ALTER TABLE ddwx_choujiang ADD COLUMN `house_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否一户仅限一次';");
		}
		if(!pdo_fieldexists2("ddwx_choujiang", "is_rzh")){
			\think\facade\Db::execute("ALTER TABLE ddwx_choujiang ADD COLUMN `is_rzh` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否认证0：否 1：是';");
		}
		if(!pdo_fieldexists2("ddwx_choujiang", "relation_type")){
			\think\facade\Db::execute("ALTER TABLE ddwx_choujiang ADD COLUMN `relation_type` tinyint(1) NOT NULL DEFAULT -1 COMMENT '用户身份-1：所有 0：业主 1、家属 2、租户 3买断 4、租用';");
		}

		if(!pdo_fieldexists2("ddwx_collage_product", "house_status")){
			\think\facade\Db::execute("ALTER TABLE ddwx_collage_product ADD COLUMN `house_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否一户仅限一次';");
		}
		if(!pdo_fieldexists2("ddwx_collage_product", "is_rzh")){
			\think\facade\Db::execute("ALTER TABLE ddwx_collage_product ADD COLUMN `is_rzh` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否认证0：否 1：是';");
		}
		if(!pdo_fieldexists2("ddwx_collage_product", "relation_type")){
			\think\facade\Db::execute("ALTER TABLE ddwx_collage_product ADD COLUMN `relation_type` tinyint(1) NOT NULL DEFAULT -1 COMMENT '用户身份-1：所有 0：业主 1、家属 2、租户 3买断 4、租用';");
		}

		if(!pdo_fieldexists2("ddwx_collage_order", "tr_roomId")){
			\think\facade\Db::execute("ALTER TABLE ddwx_collage_order ADD COLUMN `tr_roomId` int(11) NOT NULL DEFAULT 0 COMMENT '房间id';");
		}

		if(!pdo_fieldexists2("ddwx_kanjia_product", "house_status")){
			\think\facade\Db::execute("ALTER TABLE ddwx_kanjia_product ADD COLUMN `house_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否一户仅限一次';");
		}
		if(!pdo_fieldexists2("ddwx_kanjia_product", "is_rzh")){
			\think\facade\Db::execute("ALTER TABLE ddwx_kanjia_product ADD COLUMN `is_rzh` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否认证0：否 1：是';");
		}
		if(!pdo_fieldexists2("ddwx_kanjia_product", "relation_type")){
			\think\facade\Db::execute("ALTER TABLE ddwx_kanjia_product ADD COLUMN `relation_type` tinyint(1) NOT NULL DEFAULT -1 COMMENT '用户身份-1：所有 0：业主 1、家属 2、租户 3买断 4、租用';");
		}

		if(!pdo_fieldexists2("ddwx_kanjia_order", "tr_roomId")){
			\think\facade\Db::execute("ALTER TABLE ddwx_kanjia_order ADD COLUMN `tr_roomId` int(11) NOT NULL DEFAULT 0 COMMENT '房间id';");
		}

		if(!pdo_fieldexists2("ddwx_kanjia_join", "tr_roomId")){
			\think\facade\Db::execute("ALTER TABLE ddwx_kanjia_join ADD COLUMN `tr_roomId` int(11) NOT NULL DEFAULT 0 COMMENT '房间id';");
		}

		if(!pdo_fieldexists2("ddwx_scoreshop_product", "house_status")){
			\think\facade\Db::execute("ALTER TABLE ddwx_scoreshop_product ADD COLUMN `house_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否一户仅限一次';");
		}
		if(!pdo_fieldexists2("ddwx_scoreshop_product", "is_rzh")){
			\think\facade\Db::execute("ALTER TABLE ddwx_scoreshop_product ADD COLUMN `is_rzh` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否认证0：否 1：是';");
		}
		if(!pdo_fieldexists2("ddwx_scoreshop_product", "relation_type")){
			\think\facade\Db::execute("ALTER TABLE ddwx_scoreshop_product ADD COLUMN `relation_type` tinyint(1) NOT NULL DEFAULT -1 COMMENT '用户身份-1：所有 0：业主 1、家属 2、租户 3买断 4、租用';");
		}

		if(!pdo_fieldexists2("ddwx_scoreshop_order_goods", "tr_roomId")){
			\think\facade\Db::execute("ALTER TABLE ddwx_scoreshop_order_goods ADD COLUMN `tr_roomId` int(11) NOT NULL DEFAULT 0 COMMENT '房间id';");
		}

		if(!pdo_fieldexists2("ddwx_lucky_collage_product", "house_status")){
			\think\facade\Db::execute("ALTER TABLE ddwx_lucky_collage_product ADD COLUMN `house_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否一户仅限一次';");
		}
		if(!pdo_fieldexists2("ddwx_lucky_collage_product", "is_rzh")){
			\think\facade\Db::execute("ALTER TABLE ddwx_lucky_collage_product ADD COLUMN `is_rzh` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否认证0：否 1：是';");
		}
		if(!pdo_fieldexists2("ddwx_lucky_collage_product", "relation_type")){
			\think\facade\Db::execute("ALTER TABLE ddwx_lucky_collage_product ADD COLUMN `relation_type` tinyint(1) NOT NULL DEFAULT -1 COMMENT '用户身份-1：所有 0：业主 1、家属 2、租户 3买断 4、租用';");
		}

		if(!pdo_fieldexists2("ddwx_lucky_collage_order", "tr_roomId")){
			\think\facade\Db::execute("ALTER TABLE ddwx_lucky_collage_order ADD COLUMN `tr_roomId` int(11) NOT NULL DEFAULT 0 COMMENT '房间id';");
		}

		if(!pdo_fieldexists2("ddwx_admin_setapp_mp", "e_appid")){
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_mp ADD COLUMN `e_appid` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';");
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_mp ADD COLUMN `e_appsecret` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';");
			\think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_mp ADD COLUMN `e_url` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';");
		}

		if(!pdo_fieldexists2("ddwx_manjian_set", "group_status")){
			\think\facade\Db::execute("ALTER TABLE ddwx_manjian_set ADD COLUMN `group_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '用户分组 0：未开启 1：开启';");
			\think\facade\Db::execute("ALTER TABLE ddwx_manjian_set ADD COLUMN `group_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '小区id，逗号拼接';");

			\think\facade\Db::execute("ALTER TABLE ddwx_cuxiao ADD COLUMN `group_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '用户分组 0：未开启 1：开启';");
			\think\facade\Db::execute("ALTER TABLE ddwx_cuxiao ADD COLUMN `group_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '小区id，逗号拼接';");

			\think\facade\Db::execute("ALTER TABLE ddwx_cashback ADD COLUMN `group_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '用户分组 0：未开启 1：开启';");
			\think\facade\Db::execute("ALTER TABLE ddwx_cashback ADD COLUMN `group_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '小区id，逗号拼接';");

			\think\facade\Db::execute("ALTER TABLE ddwx_collage_product ADD COLUMN `group_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '用户分组 0：未开启 1：开启';");
			\think\facade\Db::execute("ALTER TABLE ddwx_collage_product ADD COLUMN `group_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '小区id，逗号拼接';");

			\think\facade\Db::execute("ALTER TABLE ddwx_kanjia_product ADD COLUMN `group_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '用户分组 0：未开启 1：开启';");
			\think\facade\Db::execute("ALTER TABLE ddwx_kanjia_product ADD COLUMN `group_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '小区id，逗号拼接';");

			\think\facade\Db::execute("ALTER TABLE ddwx_seckill_product ADD COLUMN `group_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '用户分组 0：未开启 1：开启';");
			\think\facade\Db::execute("ALTER TABLE ddwx_seckill_product ADD COLUMN `group_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '小区id，逗号拼接';");

			\think\facade\Db::execute("ALTER TABLE ddwx_tuangou_product ADD COLUMN `group_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '用户分组 0：未开启 1：开启';");
			\think\facade\Db::execute("ALTER TABLE ddwx_tuangou_product ADD COLUMN `group_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '小区id，逗号拼接';");

			\think\facade\Db::execute("ALTER TABLE ddwx_scoreshop_product ADD COLUMN `group_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '用户分组 0：未开启 1：开启';");
			\think\facade\Db::execute("ALTER TABLE ddwx_scoreshop_product ADD COLUMN `group_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '小区id，逗号拼接';");

			\think\facade\Db::execute("ALTER TABLE ddwx_choujiang ADD COLUMN `group_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '用户分组 0：未开启 1：开启';");
			\think\facade\Db::execute("ALTER TABLE ddwx_choujiang ADD COLUMN `group_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '小区id，逗号拼接';");

			\think\facade\Db::execute("ALTER TABLE ddwx_lucky_collage_product ADD COLUMN `group_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '用户分组 0：未开启 1：开启';");
			\think\facade\Db::execute("ALTER TABLE ddwx_lucky_collage_product ADD COLUMN `group_ids` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '小区id，逗号拼接';");
		}

		\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_tr_group`  (
		    `id` int NOT NULL AUTO_INCREMENT,
			`aid` int NOT NULL DEFAULT 0,
			`capitalId` int NOT NULL DEFAULT 0 COMMENT '⼩区的编号id',
			`fullName` varchar(50) NOT NULL DEFAULT '' COMMENT '⼩区的名字全称',
			`areaName` varchar(100) NOT NULL DEFAULT '' COMMENT '⼩区所属的地区（xx省xx市xx区）',
			`areaInstId` int NOT NULL DEFAULT 0 COMMENT '所属地区在⼩区存放的编号id',
			`create_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
			`update_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (`id`),
			INDEX `id`(`id`)
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

		\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_community_room`  (
		     `id` int NOT NULL AUTO_INCREMENT,
			`aid` int NOT NULL DEFAULT 0,
			`mid` int NOT NULL DEFAULT 0 COMMENT '会员id',
			`tr_communityId` int(11) NOT NULL DEFAULT 0 COMMENT '小区id',
			`tr_communityName` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '小区名称',
			`tr_roomId` int(11) NOT NULL DEFAULT 0 COMMENT '房间id',
			`tr_roomName` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '房间名称',
			`tr_relationType` tinyint(1) NOT NULL DEFAULT 0 COMMENT '业务关系 0：业主 1、家属 2、租户 3买断 4、租用',
			`tr_region` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '省市区',
			`is_del` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否删除 0：否 1：是',
			`create_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
			PRIMARY KEY (`id`),
			INDEX `tr_communityId`(`tr_communityId`),
			INDEX `tr_roomId`(`tr_roomId`),
			INDEX `mid`(`mid`)
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

		\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_cashback_log`  (
		    `id` int NOT NULL AUTO_INCREMENT,
			`aid` int NOT NULL DEFAULT 0,
			`mid` int NOT NULL DEFAULT 0,
			`order_id` int NOT NULL DEFAULT 0 COMMENT '订单id(暂只商城)',
			`cashback_id` int NOT NULL DEFAULT 0 COMMENT '购物返现id',
			`cashback_tr_roomId` int(11) NOT NULL DEFAULT 0 COMMENT '购物返现房间id',
			`cashback_money` decimal(11, 2) NOT NULL DEFAULT 0.00 COMMENT '购物返现额度',
			`back_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '购物返现类型',
			`create_time` int(11) UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (`id`),
			INDEX `order_id`(`order_id`),
			INDEX `cashback_id`(`cashback_id`),
			INDEX `mid`(`mid`)
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS  `ddwx_renovation_calculator` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT '装修计算器',
  `status` tinyint(1) DEFAULT '1',
  `type1_param` float(10,2) DEFAULT '1.00',
  `type2_param` float(10,2) DEFAULT '1.50',
  `type3_param` float(10,2) DEFAULT '2.00',
  `areadata` text,
  `guigedata` text,
  `bgcolor` varchar(255) DEFAULT '#FD4A46',
  `banner` varchar(255) DEFAULT NULL,
  `xystatus` tinyint(1) DEFAULT '1',
  `xieyi` longtext,
  `description` longtext,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_membercard_sendscorelog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `card_id` varchar(255) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `remark` text,
  `createtime` int(11) DEFAULT NULL,
  `sendcount` int(11) DEFAULT NULL,
  `successcount` int(11) DEFAULT NULL,
  `errorcount` int(11) DEFAULT NULL,
  `endtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `card_id` (`card_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");


if(!pdo_fieldexists2("ddwx_yuyue_set","yuyue_numtext")){
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `yuyue_numtext` varchar(255) DEFAULT '购买数量'");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_headimg_upload` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `dir` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `size` varchar(255) DEFAULT NULL,
  `bsize` int(11) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `platform` varchar(11) DEFAULT 'ht',
  `isdel` tinyint(1) DEFAULT '0',
  `gid` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`) USING BTREE,
  KEY `uid` (`uid`) USING BTREE,
  KEY `dir` (`dir`) USING BTREE,
  KEY `platform` (`platform`) USING BTREE,
  KEY `isdel` (`isdel`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

if(!pdo_fieldexists2("ddwx_mp_tmplset","tmpl_prize")){
    \think\facade\Db::execute("ALTER TABLE ddwx_mp_tmplset ADD tmpl_prize varchar(255) DEFAULT NULL COMMENT '抽奖结果通知';");
}
if(!pdo_fieldexists2("ddwx_wx_tmplset","tmpl_choujiang")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_wx_tmplset` ADD `tmpl_choujiang` varchar(255) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_choujiang","bgcolor")){
    \think\facade\Db::execute("ALTER TABLE ddwx_choujiang ADD `bgcolor` varchar(50) DEFAULT '' AFTER `bgpic`;");
}

if(!pdo_fieldexists2("ddwx_admin_set","locking_pwd")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD `locking_pwd` varchar(60) DEFAULT '';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD `islock` tinyint(1) DEFAULT '0';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD `islock` tinyint(1) DEFAULT '0';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD `islock` tinyint(1) DEFAULT '0';");
}

if(!pdo_fieldexists2("ddwx_coupon","discount")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` ADD `discount` float(11,2) DEFAULT NULL COMMENT '折扣券折扣比例' AFTER `money`;");
    \think\facade\Db::execute("ALTER TABLE `ddwx_coupon_record` ADD `discount` float(11,2) DEFAULT '0.00' COMMENT '折扣券折扣比例' AFTER `money`;");
}

if(!pdo_fieldexists2("ddwx_admin_set","appurl")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD `appurl` varchar(255) DEFAULT '';");
}
if(!pdo_fieldexists2("ddwx_article_set","title_size")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_article_set` ADD COLUMN `title_size` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '标题大小';");
}
if(!pdo_fieldexists2("ddwx_shop_guige","barcode")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_guige` ADD COLUMN `barcode` varchar(60) NULL AFTER `limit_start`;");
}
\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_open_app` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL,
  `appid` varchar(255) NOT NULL,
  `appsecret` varchar(255) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `refresh_token` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
	`channel` varchar(30) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `request_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  UNIQUE KEY `appid` (`appid`) USING BTREE,
  KEY `name` (`name`) USING BTREE,
  KEY `channel` (`channel`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");

if(!pdo_fieldexists2("ddwx_member","huidong_sync")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member` 
ADD COLUMN `huidong_sync` tinyint(1) NULL DEFAULT '0' COMMENT '0未处理，1已处理，-1无需处理',
ADD COLUMN `huidong_mid` varchar(60) NULL AFTER `huidong_sync`,
ADD INDEX(`huidong_sync`),
ADD INDEX(`huidong_mid`);");
}
if(!pdo_fieldexists2("ddwx_admin_set","huidong_status")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` 
ADD COLUMN `huidong_status` tinyint(1) UNSIGNED NULL DEFAULT '0',
ADD COLUMN `huidong_url` varchar(255) NULL;");
}

if(!pdo_fieldexists2("ddwx_member_scorelog","channel")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_scorelog` 
ADD COLUMN `channel` varchar(20) NULL DEFAULT '' COMMENT '变动渠道';");
}


\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_cashier` (
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
	PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_cashier_order` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`aid` int(11) DEFAULT '0',
	`createtime` int(11) DEFAULT NULL,
	`endtime` int(11) DEFAULT NULL,
	`ordernum` varchar(100) DEFAULT '',
	`bid` int(11) DEFAULT '0',
	`status` tinyint(2) DEFAULT '0' COMMENT '0结算等待中 1已完成 2挂单',
	`remark` varchar(255) DEFAULT '' COMMENT '备注',
	`cashier_id` int(11) DEFAULT '0',
	`mid` int(11) DEFAULT NULL,
	`payorderid` int(11) DEFAULT '0',
	`paytypeid` int(11) DEFAULT '0',
	`paytype` varchar(50) DEFAULT '',
	`paynum` varchar(255) DEFAULT '',
	`paytime` int(11) DEFAULT NULL,
	`totalprice` decimal(10,2) DEFAULT '0.00',
	`coupon_money` decimal(11,2) DEFAULT '0.00' COMMENT '优惠券金额',
	`coupon_rid` int(11) DEFAULT '0',
	`hangup_time` int(11) DEFAULT NULL COMMENT '挂单时间',
	`platform` varchar(64) DEFAULT '',
	`scoredk_money` float(11,2) DEFAULT NULL COMMENT '积分抵扣金额',
	`leveldk_money` float(11,2) DEFAULT '0.00' COMMENT '会员等级优惠金额',
	`moling_money` decimal(10,2) DEFAULT '0.00',
	`remove_zero` tinyint(1) DEFAULT '0',
	`remove_zero_length` tinyint(4) DEFAULT '0',
	`uid` int(11) DEFAULT '0' COMMENT '操作付款人',
	`pre_totalprice` decimal(10,2) DEFAULT '0.00',
	`message` varchar(255) DEFAULT '',
	`refund_reason` varchar(255) DEFAULT NULL,
	`refund_money` decimal(11,2) DEFAULT '0.00',
	`refund_status` int(1) DEFAULT '0' COMMENT '1申请退款审核中 2已同意退款 3已驳回',
	`refund_time` int(11) DEFAULT NULL,
	`refund_checkremark` varchar(255) DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `aid` (`aid`) USING BTREE,
	KEY `cashier_id` (`cashier_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_cashier_order_goods` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`aid` int(11) DEFAULT '0',
	`bid` int(11) DEFAULT '0',
	`mid` int(11) DEFAULT '0',
	`orderid` int(11) DEFAULT '0',
	`ordernum` varchar(50) DEFAULT '',
	`proid` varchar(128) DEFAULT '',
	`proname` varchar(255) DEFAULT '',
	`propic` varchar(255) DEFAULT '',
	`protype` tinyint(2) DEFAULT '1' COMMENT '1商品 2直接收款（proid=-99)',
	`sell_price` decimal(10,2) DEFAULT '0.00',
	`num` int(11) DEFAULT '0',
	`totalprice` decimal(10,2) DEFAULT '0.00',
	`pre_price` decimal(10,2) DEFAULT '0.00',
	`createtime` int(11) DEFAULT NULL,
	`ggid` int(11) DEFAULT '0',
	`ggname` varchar(255) DEFAULT '',
	`barcode` varchar(255) DEFAULT '',
	PRIMARY KEY (`id`),
	KEY `aid` (`aid`) USING BTREE,
	KEY `orderid` (`orderid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

if(!pdo_fieldexists2("ddwx_admin_set_sms","tmpl_yysucess")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` 
		ADD COLUMN `tmpl_yysucess` varchar(255) DEFAULT '',
		ADD COLUMN `tmpl_yysucess_st` tinyint(1) DEFAULT 1;");
}

if(!pdo_fieldexists2("ddwx_admin","choucheng_receivertype")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin` 
		ADD `choucheng_receivertype` tinyint(1) DEFAULT '0',
		ADD `choucheng_receivertype1_account` varchar(100) DEFAULT '',
		ADD `choucheng_receivertype1_name` varchar(255) DEFAULT '',
		ADD `choucheng_receivertype2_openidtype` tinyint(1) DEFAULT '0',
		ADD `choucheng_receivertype2_account` varchar(100) DEFAULT '',
		ADD `choucheng_receivertype2_accountwx` varchar(100) DEFAULT '',
		ADD `choucheng_receivertype2_name` varchar(255) DEFAULT ''");
}

if(!pdo_fieldexists2("ddwx_yuyue_set","yuyue_success")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_set` 
		ADD COLUMN `yuyue_success` varchar(255) DEFAULT ''");
}

if(!pdo_fieldexists2("ddwx_shop_product","commissionpingjiset")){
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD `commissionpingjiset` int(2) DEFAULT '0' AFTER `areafenhongdata2`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD `commissionpingjidata1` text AFTER `commissionpingjiset`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD `commissionpingjidata2` text AFTER `commissionpingjidata1`;");
}

if(!pdo_fieldexists2("ddwx_member_level","commission_appointlevelid")){
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD commission_appointlevelid varchar(255) DEFAULT '' AFTER `commission_parent_pj`;");
}

if(!pdo_fieldexists2("ddwx_admin_set","parent_show")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` 
ADD COLUMN `parent_show` tinyint(1) UNSIGNED NULL DEFAULT '1' COMMENT '是否显示推荐人：1显示，0隐藏';");
}

if(!pdo_fieldexists2("ddwx_member_level","up_giveparent_num")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_giveparent_num` int(11) DEFAULT '0';");
}

if(!pdo_fieldexists2("ddwx_admin_set","comwithdrawdate")){
    \think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD COLUMN `comwithdrawdate` varchar(100) DEFAULT '0' AFTER `comwithdrawfee`;");
}

if(!pdo_fieldexists2("ddwx_signset","style")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_signset` 
ADD COLUMN `style` tinyint(1) UNSIGNED NULL DEFAULT '1',
ADD COLUMN `bgpic` varchar(255) NULL COMMENT '背景图';");
}

if(!pdo_fieldexists2("ddwx_member","last_visittime")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member`
ADD COLUMN `last_visittime` int(11) NULL AFTER `createtime`,
ADD INDEX(`last_visittime`);");
}

if(!pdo_fieldexists2("ddwx_yuyue_set","comment_check")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_set` 
ADD COLUMN `comment_check` tinyint(1) UNSIGNED NULL DEFAULT '1' COMMENT '评价审核，1开启，0关闭';");
}
if(!pdo_fieldexists2("ddwx_yuyue_product","fenhongset")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD `fenhongset` int(11) DEFAULT '1' COMMENT '分红设置';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD `gdfenhongset` int(2) DEFAULT '0' COMMENT '0按会员等级 1价格比例  2固定金额 -1不参与分红';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD `gdfenhongdata1` text;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD `gdfenhongdata2` text;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD `teamfenhongset` int(2) DEFAULT '0';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD `teamfenhongdata1` text;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD `teamfenhongdata2` text;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD `areafenhongset` int(2) DEFAULT '0';");
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD `areafenhongdata1` text;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD `areafenhongdata2` text;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` ADD `isfenhong` int(2) DEFAULT '0';");
}
if(!pdo_fieldexists2("ddwx_member_fenhonglog","module")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_fenhonglog` ADD `module` varchar(255) DEFAULT 'shop' AFTER `type`;");
}

if(!pdo_fieldexists2("ddwx_member_level","up_catid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` 
ADD COLUMN `up_catid` varchar(255) NULL,
ADD COLUMN `up_cat_ordermoney` decimal(11, 2) NULL DEFAULT '0' AFTER `up_catid`;");
}

\think\facade\Db::execute("ALTER TABLE `ddwx_member_moneylog` MODIFY COLUMN `remark` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_member_scorelog` MODIFY COLUMN `remark` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_member_commissionlog` MODIFY COLUMN `remark` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");


 if(!pdo_fieldexists2("ddwx_yuyue_set","isautopd")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_set` ADD COLUMN `isautopd` tinyint(1) NOT NULL DEFAULT '0';");
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
		  `wx_package_info`  text NULL COMMENT '微信商家转账确认页package信息',
		  `wx_transfer_bill_no`  varchar(100) NULL DEFAULT '' COMMENT '微信转账单号，微信商家转账系统返回的唯一标识',
		  `wx_transfer_msg`  varchar(255) NULL DEFAULT NULL COMMENT '微信转账错误信息',
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
	  `refund_status` tinyint(11) DEFAULT '0',
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
	  PRIMARY KEY (`id`) USING BTREE,
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `status` (`status`) USING BTREE,
	  KEY `stock` (`stock`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='商品表';");


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
	  `status` tinyint(11) DEFAULT '1' COMMENT '0 未开启  1已开启',
	  `createtime` int(11) DEFAULT NULL,
	  `sort` int(11) DEFAULT '0',
	  `comment_score` decimal(2,1) DEFAULT '5.0',
	  `comment_num` int(11) DEFAULT '0',
	  `comment_haopercent` int(11) DEFAULT '100' COMMENT '好评率',
	  `money` decimal(11,2) DEFAULT '0.00',
	  `totalmoney` decimal(11,2) DEFAULT '0.00',
	  `totalnum` int(11) DEFAULT '0',
	  `weixin` varchar(255) NULL,
	  `aliaccount` varchar(255) DEFAULT NULL,
	  `bankname` varchar(255) DEFAULT NULL,
	  `bankcarduser` varchar(255) DEFAULT NULL,
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
	  `detail` longtext,
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

if(getcustom('zhaopin')){
    if(!pdo_fieldexists2("ddwx_mp_tmplset","tmpl_zhaopin_notice")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_mp_tmplset` ADD `tmpl_zhaopin_notice` varchar(255) DEFAULT NULL COMMENT '职位更新通知';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_mp_tmplset` ADD `tmpl_register` varchar(255) DEFAULT NULL COMMENT '注册成功通知';");
    }
    if(!pdo_fieldexists2("ddwx_admin_set_sms","tmpl_recharge")){
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` ADD `tmpl_recharge` varchar(255) DEFAULT NULL COMMENT '充值成功';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` ADD `tmpl_recharge_st` tinyint(1) DEFAULT '1' COMMENT '充值短信是否开启';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` ADD `tmpl_sysmsg_notice` varchar(255) DEFAULT NULL COMMENT '充值成功';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` ADD `tmpl_sysmsg_notice_st` tinyint(1) DEFAULT '1' COMMENT '系统消息提醒是否开启';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` ADD `tmpl_checknotice` varchar(255) DEFAULT NULL COMMENT '待审核通知';");
        \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` ADD `tmpl_checknotice_st` tinyint(1) DEFAULT '1' COMMENT '待审核通知提醒是否开启';");
    }
}
 

<?php

if(!pdo_fieldexists2("ddwx_admin_set","wxkf")){
	\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD `wxkf` tinyint(1) DEFAULT '0' AFTER `kfurl`");
}

if(!pdo_fieldexists2("ddwx_freight","formdata")){
	\think\facade\Db::execute("ALTER TABLE ddwx_freight ADD `formdata` text");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_freight_formdata` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `orderid` int(11) DEFAULT NULL,
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
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`) USING BTREE,
  KEY `type` (`type`) USING BTREE,
  KEY `orderid` (`orderid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;");

if(!pdo_fieldexists2("ddwx_member_level","can_buyselect")){
    \think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `can_buyselect` tinyint(1) DEFAULT '0'");
    \think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD `balance` float(11,2) DEFAULT '0.00'");
    \think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD `checkmemid` int(11) DEFAULT NULL");
    \think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD `balance_price` float(11,2) DEFAULT '0.00'");
    \think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD `balance_pay_status` tinyint(1) DEFAULT '0'");
    \think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD `balance_pay_orderid` int(11) DEFAULT NULL");
}
if(!pdo_fieldexists2("ddwx_member_level","buyselect_commission")){
    \think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `buyselect_commission` float(11,2) DEFAULT '0.00'");
}

if(!pdo_fieldexists2("ddwx_member_level","apply_formdata")){
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `apply_formdata` text");
}
if(!pdo_fieldexists2("ddwx_member_levelup_order", "form1")){
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form0` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form1` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form2` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form3` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form4` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form5` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form6` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form7` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form8` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form9` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form10` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form11` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form12` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form13` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form14` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form15` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form16` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form17` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form18` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form19` varchar(255) DEFAULT NULL");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `form20` varchar(255) DEFAULT NULL");
}

if(!pdo_fieldexists2("ddwx_member", "random_str")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_member ADD `random_str` varchar(255) DEFAULT ''");
	$memberlist = \think\facade\Db::name('member')->where([])->select()->toArray();
	foreach($memberlist as $member){
		\think\facade\Db::execute("update ddwx_member set `random_str`='".random(16)."' where id=".$member['id']);
	}
}
if(!pdo_fieldexists2("ddwx_admin_user", "random_str")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_admin_user ADD `random_str` varchar(255) DEFAULT ''");
	$adminuserlist = \think\facade\Db::name('admin_user')->where([])->select()->toArray();
	foreach($adminuserlist as $adminuser){
		\think\facade\Db::execute("update ddwx_admin_user set `random_str`='".random(16)."' where id=".$adminuser['id']);
	}
}

if(!pdo_fieldexists2("ddwx_member_level", "scoremax")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `scoremax` int(11) DEFAULT '0' COMMENT '推荐最高奖励积分'");
}

if(!pdo_fieldexists2("ddwx_business", "comment_haopercent")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_business ADD `comment_haopercent` int(11) DEFAULT '100' COMMENT '好评率'");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS  `ddwx_freight_type10_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `pic` varchar(255) DEFAULT NULL,
  `fhname` varchar(255) DEFAULT NULL,
  `fhaddress` varchar(255) DEFAULT NULL,
  `shname` varchar(255) DEFAULT NULL,
  `shaddress` varchar(255) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

//\think\facade\Db::execute("ALTER TABLE `ddwx_freight_type10_record` MODIFY COLUMN `pic` varchar(255) DEFAULT NULL;");

if(!pdo_fieldexists2("ddwx_member", "remark")) {
  \think\facade\Db::execute("ALTER TABLE ddwx_member ADD `remark` varchar(255) DEFAULT '' COMMENT '备注'");
}
if(!pdo_fieldexists2("ddwx_cuxiao", "prozk")) {
  \think\facade\Db::execute("ALTER TABLE ddwx_cuxiao ADD `prozk` varchar(255) DEFAULT '' COMMENT '单独设置的商品折扣'");
}

if(!pdo_fieldexists2("ddwx_scoreshop_sysset", "gettj")) {
  \think\facade\Db::execute("ALTER TABLE ddwx_scoreshop_sysset ADD `gettj` varchar(255) DEFAULT '-1' COMMENT '进入条件'");
  \think\facade\Db::execute("ALTER TABLE ddwx_scoreshop_sysset ADD `gettjtip` varchar(255) DEFAULT '您没有权限进入'");
  \think\facade\Db::execute("ALTER TABLE ddwx_scoreshop_sysset ADD `gettjurl` varchar(255) DEFAULT ''");
}
if(!pdo_fieldexists2("ddwx_cuxiao", "minnum")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_cuxiao ADD `minnum` int(11) DEFAULT '1' COMMENT '最低购买件数'");
	\think\facade\Db::execute("ALTER TABLE ddwx_cuxiao ADD `zhekou` float(11,2) DEFAULT NULL COMMENT '折扣0.01~9.99'");
}
if(!pdo_fieldexists2("ddwx_admin_set", "gettj")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD `gettj` varchar(255) DEFAULT '-1' COMMENT '进入条件';");
	\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD `gettjtip` varchar(255) DEFAULT '您没有权限进入';");
}

if(!pdo_fieldexists2("ddwx_business", "start_hours")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_business ADD `start_hours` varchar(100) DEFAULT '00:00';");
}
if(!pdo_fieldexists2("ddwx_business", "end_hours")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_business ADD `end_hours` varchar(100) DEFAULT '00:00';");
}


if(!pdo_fieldexists2("ddwx_shop_product", "start_hours")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD `start_hours` varchar(100) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_shop_product", "end_hours")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD `end_hours` varchar(100) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_shop_product", "start_time")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD start_time varchar(100) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_shop_product", "end_time")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD end_time varchar(100) DEFAULT NULL;");
}




if(!pdo_fieldexists2("ddwx_admin", "domain")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_admin ADD `domain` varchar(100) DEFAULT NULL;");
}


\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS  `ddwx_admin_wxreglog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `code_type` int(1) DEFAULT NULL,
  `legal_persona_wechat` varchar(255) DEFAULT NULL,
  `legal_persona_name` varchar(255) DEFAULT NULL,
  `component_phone` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0' COMMENT '0审核中 1通过 2不通过',
  `reason` varchar(255) DEFAULT NULL COMMENT '失败原因',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

if(!pdo_fieldexists2("ddwx_admin_setapp_wx", "signature")) {
  \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_wx ADD `signature` varchar(255) DEFAULT NULL");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_wx", "createtype")) {
  \think\facade\Db::execute("ALTER TABLE ddwx_admin_setapp_wx ADD `createtype` tinyint(1) DEFAULT '0' COMMENT '0自己注册 1通过开放平台复用公众号资质快速注册  2通过开放平台快速创建'");
}

if(!pdo_fieldexists2("ddwx_peisong_set", "make_shopkoufei")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_peisong_set ADD `make_shopkoufei` tinyint(1) DEFAULT '0';");
}
if(!pdo_fieldexists2("ddwx_shop_order", "longitude")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD `latitude` varchar(100) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD `longitude` varchar(100) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE ddwx_scoreshop_order ADD `latitude` varchar(100) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE ddwx_scoreshop_order ADD `longitude` varchar(100) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE ddwx_kanjia_order ADD `latitude` varchar(100) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE ddwx_kanjia_order ADD `longitude` varchar(100) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE ddwx_seckill_order ADD `latitude` varchar(100) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE ddwx_seckill_order ADD `longitude` varchar(100) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE ddwx_collage_order ADD `latitude` varchar(100) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE ddwx_collage_order ADD `longitude` varchar(100) DEFAULT NULL;");
}



if(!pdo_fieldexists2("ddwx_admin_set", "address")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD `address` varchar(255) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD `longitude` varchar(255) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD `latitude` varchar(255) DEFAULT NULL;");
}

if(!pdo_fieldexists2("ddwx_peisong_order", "make_ordernum")){

	\think\facade\Db::execute("ALTER TABLE ddwx_payorder MODIFY COLUMN `paynum` varchar(100) DEFAULT NULL;");

	//同城配送模块
	\think\facade\Db::execute("DROP TABLE IF EXISTS `ddwx_peisong_moneylog`;");
	\think\facade\Db::execute("CREATE TABLE `ddwx_peisong_moneylog` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `uid` int(11) DEFAULT NULL,
	  `money` decimal(11,2) DEFAULT '0.00',
	  `after` decimal(11,2) DEFAULT '0.00',
	  `createtime` int(11) DEFAULT NULL,
	  `remark` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `uid` (`uid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

	\think\facade\Db::execute("DROP TABLE IF EXISTS `ddwx_peisong_order`;");
	\think\facade\Db::execute("CREATE TABLE `ddwx_peisong_order` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) unsigned DEFAULT '0',
	  `mid` int(11) DEFAULT NULL,
	  `psid` int(11) DEFAULT NULL COMMENT '配送员id',
	  `orderid` int(11) DEFAULT NULL,
	  `ordernum` varchar(100) DEFAULT NULL,
	  `createtime` int(11) DEFAULT NULL,
	  `starttime` int(11) DEFAULT NULL,
	  `daodiantime` int(11) DEFAULT NULL,
	  `quhuotime` int(11) DEFAULT NULL,
	  `endtime` int(11) DEFAULT NULL,
	  `status` tinyint(1) DEFAULT '0' COMMENT '0待接单 1已接单正在赶往商家 2已到店 3已取货配送中 4已送达',
	  `type` varchar(255) DEFAULT NULL,
	  `ticheng` decimal(11,2) DEFAULT '0.00',
	  `psfee` decimal(10,2) DEFAULT '0.00' COMMENT '配送费 需要扣除商家的钱',
	  `iscomment` tinyint(1) unsigned DEFAULT '0' COMMENT '是否评价',
	  `yujitime` int(11) DEFAULT NULL COMMENT '预计送达时间',
	  `juli` int(11) DEFAULT NULL COMMENT '商家到用户的距离 米',
	  `longitude` varchar(100) DEFAULT NULL COMMENT '商家坐标',
	  `latitude` varchar(100) DEFAULT NULL,
	  `longitude2` varchar(100) DEFAULT NULL COMMENT '用户坐标',
	  `latitude2` varchar(100) DEFAULT NULL,
	  `orderinfo` text COMMENT '订单信息',
	  `prolist` text COMMENT '商品信息',
	  `binfo` text COMMENT '商家信息',
	  `make_ordernum` varchar(100) DEFAULT NULL COMMENT '码科跑腿订单号',
	  `make_rider_name` varchar(255) DEFAULT NULL COMMENT '码科配送员姓名',
	  `make_rider_mobile` varchar(255) DEFAULT NULL COMMENT '码科配送员手机号',
	  PRIMARY KEY (`id`),
	  KEY `aid` (`aid`),
	  KEY `psid` (`psid`),
	  KEY `orderid` (`orderid`),
	  KEY `ordernum` (`ordernum`),
	  KEY `status` (`status`),
	  KEY `type` (`type`),
	  KEY `iscomment` (`iscomment`),
	  KEY `bid` (`bid`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

	\think\facade\Db::execute("DROP TABLE IF EXISTS `ddwx_peisong_order_comment`;");
	\think\facade\Db::execute("CREATE TABLE `ddwx_peisong_order_comment` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `bid` int(11) DEFAULT NULL,
	  `mid` int(11) DEFAULT NULL,
	  `psid` int(11) DEFAULT NULL,
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
	  PRIMARY KEY (`id`),
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE,
	  KEY `bid` (`bid`) USING BTREE,
	  KEY `peisong_user_id` (`psid`) USING BTREE,
	  KEY `order_id` (`orderid`) USING BTREE,
	  KEY `order_no` (`ordernum`) USING BTREE,
	  KEY `status` (`status`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='配送单评价';");

	\think\facade\Db::execute("DROP TABLE IF EXISTS `ddwx_peisong_set`;");
	\think\facade\Db::execute("CREATE TABLE `ddwx_peisong_set` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `status` tinyint(1) DEFAULT '0',
	  `paidantype` tinyint(1) DEFAULT '0',
	  `yuji_sendminute` int(11) DEFAULT '10',
	  `yuji_psjuli1` int(11) DEFAULT '5',
	  `yuji_psminute1` int(11) DEFAULT '20',
	  `yuji_psjuli2` int(11) DEFAULT '1',
	  `yuji_psminute2` int(11) DEFAULT '2',
	  `jiesuantype` tinyint(1) DEFAULT '0',
	  `tcmoney` decimal(11,2) DEFAULT '0.00',
	  `peisong_juli1` varchar(100) DEFAULT '5',
	  `peisong_tcmoney1` decimal(11,2) DEFAULT '5.00',
	  `peisong_juli2` varchar(100) DEFAULT '1',
	  `peisong_tcmoney2` decimal(11,2) DEFAULT '2.00',
	  `peisong_tcmoneymax` decimal(11,2) DEFAULT '20.00' COMMENT '封顶提成金额',
	  `withdraw_weixin` tinyint(1) DEFAULT '1',
	  `withdraw_aliaccount` tinyint(1) DEFAULT '1',
	  `withdraw_bankcard` tinyint(1) DEFAULT '1',
	  `withdrawmin` varchar(255) DEFAULT '10',
	  `withdrawfee` varchar(255) DEFAULT '0',
	  `businessst` tinyint(1) DEFAULT '0',
	  `businessfee` decimal(11,2) DEFAULT '0.00',
	  `make_status` tinyint(1) DEFAULT '0',
	  `make_domain` varchar(100) DEFAULT NULL,
	  `make_appid` varchar(100) DEFAULT NULL,
	  `make_token` varchar(255) DEFAULT NULL,
	  `make_access_token` varchar(255) DEFAULT NULL,
	  `make_expire_time` int(11) DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `aid` (`aid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

	\think\facade\Db::execute("DROP TABLE IF EXISTS `ddwx_peisong_user`;");
	\think\facade\Db::execute("CREATE TABLE `ddwx_peisong_user` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `mid` int(11) DEFAULT NULL,
	  `realname` varchar(255) DEFAULT NULL,
	  `tel` varchar(255) DEFAULT NULL,
	  `status` tinyint(11) DEFAULT '1',
	  `createtime` int(11) DEFAULT NULL,
	  `sort` int(11) DEFAULT '0',
	  `comment_score` decimal(2,1) DEFAULT '5.0',
	  `comment_num` int(11) DEFAULT '0',
	  `comment_haopercent` int(11) DEFAULT '100' COMMENT '好评率',
	  `money` decimal(11,2) DEFAULT '0.00',
	  `totalmoney` decimal(11,2) DEFAULT NULL,
	  `totalnum` int(11) DEFAULT '0',
	  `weixin` varchar(255) DEFAULT NULL,
	  `aliaccount` varchar(255) DEFAULT NULL,
	  `bankname` varchar(255) DEFAULT NULL,
	  `bankcarduser` varchar(255) DEFAULT NULL,
	  `bankcardnum` varchar(100) DEFAULT NULL,
	  `longitude` varchar(100) DEFAULT NULL,
	  `latitude` varchar(100) DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `mid` (`mid`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

	\think\facade\Db::execute("DROP TABLE IF EXISTS `ddwx_peisong_withdrawlog`;");
	\think\facade\Db::execute("CREATE TABLE `ddwx_peisong_withdrawlog` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `aid` int(11) DEFAULT NULL,
	  `uid` int(11) DEFAULT NULL,
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
	  `platform` varchar(255) DEFAULT 'wx',
	  `reason` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `aid` (`aid`) USING BTREE,
	  KEY `uid` (`uid`) USING BTREE,
	  KEY `createtime` (`createtime`) USING BTREE,
	  KEY `status` (`status`) USING BTREE
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

}

if(!pdo_fieldexists2("ddwx_scoreshop_product", "commissionset")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product`
   ADD COLUMN `commissionset` int(2) DEFAULT '0' COMMENT '0按会员等级 1价格比例  2固定金额 3送积分 -1不参与分销',
   ADD COLUMN `commissiondata1` text,
   ADD COLUMN `commissiondata2` text,
   ADD COLUMN `commissiondata3` text,
   ADD COLUMN `commission1` decimal(11,2) DEFAULT NULL,
   ADD COLUMN `commission2` decimal(11,2) DEFAULT NULL,
   ADD COLUMN `commission3` decimal(11,2) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order_goods`
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
	\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product`
  ADD COLUMN `cost_price` decimal(11,2) DEFAULT '0.00' AFTER `sell_price`;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order_goods`
  ADD COLUMN `cost_price` decimal(11,2) DEFAULT '0.00' AFTER `sell_price`;");
}


if(!pdo_fieldexists2("ddwx_cuxiao", "pronum")) {
  \think\facade\Db::execute("ALTER TABLE ddwx_cuxiao ADD `pronum` varchar(255) DEFAULT '' COMMENT '单独设置的商品最低购买数量'");
}


\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_iconsvg_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL,
  `iconid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `show_svg` text NOT NULL,
  `pngurl` varchar(255) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`) USING BTREE,
  KEY `uid` (`uid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shop_refund_order` (
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
  `express_com` varchar(255) DEFAULT NULL COMMENT '快递公司',
  `express_no` varchar(255) DEFAULT NULL COMMENT '快递单号',
  `refund_type` varchar(20) DEFAULT NULL COMMENT 'refund退款，return退货退款',
  `refund_reason` varchar(255) DEFAULT NULL,
  `refund_money` decimal(11,2) DEFAULT '0.00',
  `refund_status` int(1) DEFAULT '1' COMMENT '0取消 1申请退款审核中 2已同意退款 4同意待退货 3已驳回',
  `refund_time` int(11) DEFAULT NULL,
  `refund_checkremark` varchar(255) DEFAULT NULL,
  `refund_pics` text,
  `freight_time` varchar(255) DEFAULT NULL,
  `freight_content` text COMMENT '自动发货信息 卡密',
  `platform` varchar(255) DEFAULT 'wx',
  `delete` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `refund_type` (`refund_type`) USING BTREE,
  KEY `orderid` (`orderid`) USING BTREE,
  KEY `refund_ordernum` (`refund_ordernum`) USING BTREE,
  KEY `ordernum` (`ordernum`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shop_refund_order_goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `mid` int(11) DEFAULT NULL,
  `refund_orderid` int(11) DEFAULT NULL,
  `refund_ordernum` varchar(50) DEFAULT NULL,
  `refund_num` int(11) unsigned NOT NULL DEFAULT '0',
  `refund_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00',
  `orderid` int(11) DEFAULT NULL,
  `ordernum` varchar(50) DEFAULT NULL,
  `ogid` int(11) NOT NULL,
  `proid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `pic` varchar(255) DEFAULT NULL,
  `procode` varchar(255) DEFAULT NULL,
  `ggid` int(11) DEFAULT NULL,
  `ggname` varchar(255) DEFAULT NULL,
  `cid` int(11) DEFAULT '0',
  `cost_price` decimal(11,2) DEFAULT NULL,
  `sell_price` decimal(11,2) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `refund_orderid` (`refund_orderid`) USING BTREE,
  KEY `refund_ordernum` (`refund_ordernum`) USING BTREE,
  KEY `orderid` (`orderid`) USING BTREE,
  KEY `ordernum` (`ordernum`) USING BTREE,
  KEY `proid` (`proid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");


if(!pdo_fieldexists2("ddwx_guanggao_showlog", "platform")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_guanggao_showlog ADD `platform` varchar(255) DEFAULT ''");
	\think\facade\Db::execute("CREATE UNIQUE INDEX mpopenid_tel ON ddwx_member (mpopenid,tel);");
	\think\facade\Db::execute("alter table ddwx_member alter tel set default '';");
}

if(!pdo_fieldexists2("ddwx_shop_order_goods", "refund_num")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods`
MODIFY COLUMN `num` int(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `cid`,
ADD COLUMN `refund_num` int(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `num`;");
}

if(!pdo_fieldexists2("ddwx_collage_order","iscomment")){
	\think\facade\Db::execute("ALTER TABLE ddwx_collage_order ADD `iscomment` tinyint(1) DEFAULT '0'");
}


if(!pdo_fieldexists2("ddwx_member","bid")){
	\think\facade\Db::execute("ALTER TABLE ddwx_member ADD `bid` int(11) DEFAULT '0';");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_commissionlog MODIFY COLUMN `after` decimal(11,2) DEFAULT '0.00';");
}
if(!pdo_fieldexists2("ddwx_shop_product","linkid")){
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD linkid int(11) DEFAULT '0';");
}


if(!pdo_fieldexists2("ddwx_business_sysset","commission_autotransfer")){
	\think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD commission_autotransfer tinyint(1) DEFAULT '0';");
}

if(!pdo_fieldexists2("ddwx_shop_sysset","hide_sales")){
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_sysset ADD hide_sales tinyint(1) DEFAULT '0' COMMENT '是否隐藏销量';");
}
if(!pdo_fieldexists2("ddwx_member_level","team_showtel")){
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD team_showtel tinyint(1) DEFAULT '0' COMMENT '是否显示下级手机号';");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD team_givemoney tinyint(1) DEFAULT '0' COMMENT '是否可以给下级转余额';");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD team_givescore tinyint(1) DEFAULT '0' COMMENT '是否可以给下级转积分';");
}
if(!pdo_fieldexists2("ddwx_payorder","paypics")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_payorder`
	MODIFY COLUMN `paytypeid` tinyint(1) NULL DEFAULT '0' COMMENT '1余额支付 2微信支付 3支付宝支付 4货到付款 5转账汇款 11百度小程序 12头条小程序' AFTER `paynum`,
	ADD COLUMN `paypics` text NULL AFTER `paytime`,
	ADD COLUMN `check_status` tinyint(1) DEFAULT NULL COMMENT '0待审核，1审核通过，2驳回' AFTER `paypics`,
	ADD COLUMN `check_remark` varchar(255) NULL AFTER `check_status`;");
}
if(!pdo_fieldexists2("ddwx_admin_set","pay_transfer")){
	\think\facade\Db::execute("ALTER TABLE ddwx_admin_set
		ADD COLUMN `pay_transfer` tinyint(1) unsigned DEFAULT '0' COMMENT '转账汇款',
		ADD COLUMN `pay_transfer_account_name` varchar(60) DEFAULT '',
		ADD COLUMN `pay_transfer_account` varchar(60) DEFAULT '',
		ADD COLUMN `pay_transfer_bank` varchar(60) DEFAULT '',
		ADD COLUMN `pay_transfer_desc` varchar(60) DEFAULT '';");
}

if(!pdo_fieldexists2("ddwx_shop_product","wxvideo_third_cat_id")){
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD wxvideo_edit_status int(11) DEFAULT '0' COMMENT '视频号商品草稿状态 0未同步 1未审核 2审核中 3审核失败 4审核成功';");
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD wxvideo_status int(11) DEFAULT '0' COMMENT '视频号商品线上状态 0初始值 5已上架 11已下架 13违规/风控';");
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD wxvideo_third_cat_id varchar(100) DEFAULT '' COMMENT '视频号类目id';");
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD wxvideo_brand_id varchar(100) DEFAULT '' COMMENT '视频号品牌id';");
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD wxvideo_product_id varchar(100) DEFAULT '' COMMENT '视频号商品id';");
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD wxvideo_qualification_pics varchar(255) DEFAULT '0' COMMENT '视频号商品资质';");
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD wxvideo_reject_reason varchar(255) DEFAULT '' COMMENT '视频号商品驳回原因';");
}
if(!pdo_fieldexists2("ddwx_shop_order","fromwxvideo")){
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD fromwxvideo tinyint(1) DEFAULT '0' COMMENT '是否是视频号过来的订单';");
}
if(!pdo_fieldexists2("ddwx_shop_order","scene")){
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD `scene` int(11) DEFAULT '0' COMMENT '小程序场景'");
}

if(!pdo_fieldexists2("ddwx_mp_tmplset","tmpl_uplv")){
	\think\facade\Db::execute("ALTER TABLE ddwx_mp_tmplset ADD tmpl_uplv varchar(255) DEFAULT NULL COMMENT '会员升级通知';");
}

if(!pdo_fieldexists2("ddwx_admin_user","tmpl_uplv")){
	\think\facade\Db::execute("ALTER TABLE ddwx_admin_user ADD `tmpl_uplv` tinyint(1) DEFAULT '1';");
}

if(!pdo_fieldexists2("ddwx_admin_set","invoice")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
ADD COLUMN `invoice` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '发票 0关 1开' ,
ADD COLUMN `invoice_type` varchar(20) NULL DEFAULT '1' COMMENT '发票类型 1普通 2专票' AFTER `invoice`;");
}
if(!pdo_fieldexists2("ddwx_business","invoice")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_business`
ADD COLUMN `invoice` tinyint(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '发票 0关 1开' ,
ADD COLUMN `invoice_type` varchar(20) NULL DEFAULT '1' COMMENT '发票类型 1普通 2专票' AFTER `invoice`;");
}
\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_invoice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(10) unsigned NOT NULL DEFAULT '0',
  `bid` int(10) unsigned DEFAULT '0',
  `order_type` varchar(100) NOT NULL DEFAULT 'shop' COMMENT '订单类型',
  `orderid` int(11) NOT NULL DEFAULT '0',
  `ordernum` varchar(100) DEFAULT NULL,
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '类型 1普票 2专票',
  `invoice_name` varchar(200) NOT NULL COMMENT '抬头',
  `name_type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '抬头类型 1个人 2公司',
  `tax_no` varchar(100) DEFAULT NULL COMMENT '税号',
  `address` varchar(255) DEFAULT NULL,
  `tel` varchar(30) DEFAULT NULL,
  `bank_name` varchar(200) DEFAULT NULL,
  `bank_account` varchar(100) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态 0待审核 1通过 2驳回',
  `check_remark` varchar(255) DEFAULT NULL COMMENT '审核备注',
  `create_time` int(11) DEFAULT NULL,
  `check_time` int(11) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `type` (`type`),
  KEY `name_type` (`name_type`),
  KEY `invoice_name` (`invoice_name`),
  KEY `tax_no` (`tax_no`),
  KEY `orderid` (`orderid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

if(!pdo_fieldexists2("ddwx_member","laxin_time")){
	\think\facade\Db::execute("ALTER TABLE ddwx_member ADD `laxin_time` int(11) DEFAULT '0' COMMENT '最后拉新时间'");
}


\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_wxvideo_brand_apply` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `license` varchar(255) DEFAULT NULL,
  `brand_audit_type` varchar(255) DEFAULT NULL,
  `trademark_type` varchar(255) DEFAULT NULL,
  `brand_management_type` varchar(255) DEFAULT NULL,
  `commodity_origin_type` varchar(255) DEFAULT NULL,
  `brand_wording` varchar(255) DEFAULT NULL,
  `sale_authorization` varchar(255) DEFAULT NULL,
  `trademark_registration_certificate` varchar(255) DEFAULT NULL,
  `trademark_change_certificate` varchar(11) DEFAULT NULL,
  `trademark_registrant` varchar(255) DEFAULT NULL,
  `trademark_registrant_nu` varchar(255) DEFAULT NULL,
  `trademark_authorization_period` varchar(255) DEFAULT NULL,
  `trademark_registration_application` varchar(255) DEFAULT NULL,
  `trademark_applicant` varchar(255) DEFAULT NULL,
  `trademark_application_time` datetime DEFAULT NULL,
  `imported_goods_form` varchar(255) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `audit_id` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0' COMMENT '0审核中 1已通过 2驳回',
  `reject_reason` varchar(255) DEFAULT NULL COMMENT '驳回原因',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_wxvideo_category_apply` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `license` varchar(255) DEFAULT NULL,
  `first_cat_id` varchar(255) DEFAULT NULL,
  `first_cat_name` varchar(255) DEFAULT NULL,
  `second_cat_id` varchar(255) DEFAULT NULL,
  `second_cat_name` varchar(255) DEFAULT NULL,
  `third_cat_id` varchar(255) DEFAULT NULL,
  `third_cat_name` varchar(255) DEFAULT NULL,
  `certificate` varchar(255) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `audit_id` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0' COMMENT '0审核中 1已通过 2驳回',
  `reject_reason` varchar(255) DEFAULT NULL COMMENT '驳回原因',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_wxvideo_catelist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `third_cat_id` int(11) DEFAULT NULL,
  `third_cat_name` varchar(255) DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `qualification_type` tinyint(1) DEFAULT '0' COMMENT '类目资质类型,0:不需要,1:必填,2:选填',
  `product_qualification` varchar(255) DEFAULT NULL,
  `product_qualification_type` tinyint(1) DEFAULT '0' COMMENT '商品资质类型,0:不需要,1:必填,2:选填',
  `second_cat_id` int(11) DEFAULT NULL,
  `second_cat_name` varchar(255) DEFAULT NULL,
  `first_cat_id` int(11) DEFAULT NULL,
  `first_cat_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_designer_menu_business` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `indexurl` varchar(255) DEFAULT '/pages/business/index',
  `menucount` int(11) DEFAULT NULL,
  `menudata` text,
  `updatetime` int(11) DEFAULT NULL,
  `platform` varchar(11) DEFAULT 'mp',
  `tongbu` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `platform` (`platform`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

if(!pdo_fieldexists2("ddwx_mendian","tel")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_mendian` ADD COLUMN `tel` varchar(20) NULL AFTER `content`;");
}


\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shop_fuwu` (
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

if(!pdo_fieldexists2("ddwx_shop_product","fwid")){
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD COLUMN `fwid` varchar(255) NULL AFTER `procode`;");
}
if(!pdo_fieldexists2("ddwx_member_level","teamfenhongonly")){
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `teamfenhongonly` tinyint(1) DEFAULT '0' AFTER `teamfenhongbl`;");
}
if(!pdo_fieldexists2("ddwx_business_sysset","product_showset")){
	\think\facade\Db::execute("ALTER TABLE ddwx_business_sysset ADD `product_showset` tinyint(1) DEFAULT '0' AFTER `commission_kouchu`;");
}
if(!pdo_fieldexists2("ddwx_recharge_giveset","caninput")){
	\think\facade\Db::execute("ALTER TABLE ddwx_recharge_giveset ADD `caninput` tinyint(1) DEFAULT '1';");
}

if(!pdo_fieldexists2("ddwx_shop_order","express_content")){
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_order ADD `express_content` text COMMENT '多个快递单号时的快递单号数据' AFTER `express_no`;");
}

if(!pdo_fieldexists2("ddwx_mp_tmplset","tmpl_moneychange")){
	\think\facade\Db::execute("ALTER TABLE ddwx_mp_tmplset ADD `tmpl_moneychange` varchar(255) DEFAULT NULL COMMENT '余额变动提示';");
}

if(!pdo_fieldexists2("ddwx_admin_set","login_bind")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `login_bind` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `logintype_app`;");
}
if(!pdo_fieldexists2("ddwx_wxpay_log","sub_mchid")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_wxpay_log` ADD `sub_mchid` varchar(100) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_wxpay_log","platform")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_wxpay_log` ADD `platform` varchar(100) DEFAULT NULL;");
}

if(!pdo_fieldexists2("ddwx_member_level","show_business")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `show_business` tinyint(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '是否显示在商家';");
}

if(!pdo_fieldexists2("ddwx_admin_set","login_mast")){
	\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD `login_mast` varchar(100) DEFAULT NULL COMMENT '强制登录' AFTER `login_bind`;");
}

if(!pdo_fieldexists2("ddwx_mp_tmplset","tmpl_restaurant_booking")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_mp_tmplset` ADD COLUMN `tmpl_restaurant_booking` varchar(255) NULL AFTER `tmpl_moneychange`;");
}
if(!pdo_fieldexists2("ddwx_admin_set_sms","tmpl_restaurant_booking")){
	\think\facade\Db::execute("
	ALTER TABLE `ddwx_admin_set_sms`
ADD COLUMN `tmpl_restaurant_booking` varchar(255) NULL AFTER `tmpl_fenxiaosuccess_st`,
ADD COLUMN `tmpl_restaurant_booking_st` tinyint(1) NULL DEFAULT '1' AFTER `tmpl_restaurant_booking`,
ADD COLUMN `tmpl_restaurant_booking_fail` varchar(255) NULL AFTER `tmpl_restaurant_booking_st`,
ADD COLUMN `tmpl_restaurant_booking_fail_st` tinyint(1) NULL DEFAULT '1' AFTER `tmpl_restaurant_booking_fail`;
	");
}


if(!pdo_fieldexists2("ddwx_admin_user","groupid")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_user` ADD `groupid` int(11) DEFAULT '0';");
}
if(!pdo_fieldexists2("ddwx_admin_user","addid")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_user` ADD `addid` int(11) DEFAULT '0' COMMENT '哪个管理员添加的';");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_admin_user_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `sort` int(11) DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `createtime` int(11) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `auth_data` text,
  `wxauth_data` text,
  `notice_auth_data` text,
  `hexiao_auth_data` text,
  `mdid` int(11) DEFAULT '0',
  `showtj` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

if(!pdo_fieldexists2("ddwx_admin_set","fhjiesuanbusiness")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD `fhjiesuanbusiness` tinyint(1) DEFAULT '1' AFTER `fhjiesuantime`;");
}

if(!pdo_fieldexists2("ddwx_member_level","areafenhong")){
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `areafenhong` tinyint(1) DEFAULT '0' AFTER `teamfenhongonly`;");
}
if(!pdo_fieldexists2("ddwx_member_level","areafenhongbl")){
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `areafenhongbl` decimal(10,2) DEFAULT '0' AFTER `areafenhong`;");
}
if(!pdo_fieldexists2("ddwx_member_levelup_order","areafenhong_province")){
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `areafenhong_area` varchar(255) DEFAULT NULL AFTER `beforelevelid`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `areafenhong_city` varchar(255) DEFAULT NULL AFTER `beforelevelid`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_levelup_order ADD `areafenhong_province` varchar(255) DEFAULT NULL AFTER `beforelevelid`;");
}
if(!pdo_fieldexists2("ddwx_member","areafenhong_province")){
	\think\facade\Db::execute("ALTER TABLE ddwx_member ADD `areafenhong_province` varchar(255) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE ddwx_member ADD `areafenhong_city` varchar(255) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE ddwx_member ADD `areafenhong_area` varchar(255) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_member_fenhonglog","ogids")){
	\think\facade\Db::execute("ALTER TABLE ddwx_member_fenhonglog ADD `ogids` text;");
}

if(!pdo_fieldexists2("ddwx_mendian","area")){
	\think\facade\Db::execute("ALTER TABLE ddwx_mendian ADD `area` varchar(255) DEFAULT NULL AFTER `tel`;");
}
if(!pdo_fieldexists2("ddwx_admin_set","teamfenhong_differential")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD `teamfenhong_differential` tinyint(1) DEFAULT '0';");
}
if(!pdo_fieldexists2("ddwx_shop_product","payaftertourl")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD `payaftertourl` varchar(255) DEFAULT null;");
}
if(!pdo_fieldexists2("ddwx_shop_product","payafterbtntext")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD `payafterbtntext` varchar(255) DEFAULT null;");
}
if(!pdo_fieldexists2("ddwx_member","areafenhong")){
	\think\facade\Db::execute("ALTER TABLE ddwx_member ADD `areafenhong` tinyint(1) DEFAULT '0';");
	\think\facade\Db::execute("ALTER TABLE ddwx_member ADD `areafenhongbl` decimal(10,2) DEFAULT '0';");
}

if(!pdo_fieldexists2("ddwx_admin_set_xieyi","name")){
	\think\facade\Db::execute("ALTER TABLE ddwx_admin_set_xieyi ADD `name` varchar(255) DEFAULT '《用户注册协议》';");
}

if(!pdo_fieldexists2("ddwx_member_level","up_fxorderlevelnum")){
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `up_fxorderlevelnum` varchar(255) DEFAULT '0' after `up_fxordermoney`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `up_fxorderlevelid` varchar(255) DEFAULT '0' after `up_fxorderlevelnum`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `up_fxdownlevelnum` varchar(255) DEFAULT '0' after `up_fxdowncount`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `up_fxdownlevelid` varchar(255) DEFAULT '0' after `up_fxdownlevelnum`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `up_fxdowncount2` int(11) DEFAULT '0' after `up_fxdownlevelid`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `up_fxdownlevelnum2` varchar(255) DEFAULT '0' after `up_fxdowncount2`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `up_fxdownlevelid2` varchar(255) DEFAULT '0' after `up_fxdownlevelnum2`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level MODIFY COLUMN `up_proid` varchar(255) DEFAULT '';");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `up_pronum` varchar(255) DEFAULT '1' after `up_proid`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `comwithdraw` tinyint(1) DEFAULT '1';");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `fenhong_num` int(11) DEFAULT '0' after `fenhong`;");
}

if(!pdo_fieldexists2("ddwx_admin_set","withdraw_autotransfer")){
	\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD `withdraw_autotransfer` tinyint(1) DEFAULT '0' after `withdraw`");
}
if(!pdo_fieldexists2("ddwx_member","levelstarttime")){
	\think\facade\Db::execute("ALTER TABLE ddwx_member ADD `levelstarttime` int(11) DEFAULT '0' after `subscribe_time`");
}

if(!pdo_fieldexists2("ddwx_shop_sysset","canrefund")){
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_sysset ADD `canrefund` tinyint(1) DEFAULT '1'");
}

//短视频数据表
\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shortvideo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT NULL COMMENT '店铺ID',
  `cid` int(11) DEFAULT NULL COMMENT '视频分类ID',
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '短视频名称',
  `url` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '视频地址',
  `video_duration` decimal(10,2) DEFAULT NULL COMMENT '视频长度',
  `coverimg` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '封面图片',
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '视频文案',
  `productids` text COMMENT '商品ID',
  `comment` tinyint(1) DEFAULT '1' COMMENT '是否开启评论，1是，0否',
  `comment_check` tinyint(1) DEFAULT '0',
  `view_num` int(11) DEFAULT '0' COMMENT '播放量',
  `zan_num` int(11) DEFAULT '0' COMMENT '点赞数量',
  `share_num` int(11) DEFAULT '0' COMMENT '分享次数',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `createtime` int(11) DEFAULT NULL COMMENT '添加时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '1显示，0隐藏',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`aid`) USING BTREE,
  KEY `cid` (`aid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='短视频表';");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shortvideo_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL COMMENT '分类名称',
  `status` tinyint(1) DEFAULT NULL COMMENT '状态，1正常，0禁用',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  `sort` int(11) DEFAULT '0' COMMENT '序号',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`aid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='短视频类型表';");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shortvideo_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `vid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `headimg` varchar(255) DEFAULT NULL,
  `nickname` varchar(255) DEFAULT NULL,
  `content` varchar(255) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  `zan` int(11) DEFAULT '0',
  `score` int(11) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `sid` (`vid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");


\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shortvideo_comment_reply` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `vid` int(11) DEFAULT NULL,
  `pid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `headimg` varchar(255) DEFAULT NULL,
  `nickname` varchar(255) DEFAULT NULL,
  `content` varchar(255) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  `zan` int(11) DEFAULT '0',
  `score` int(11) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `sid` (`vid`) USING BTREE,
  KEY `pid` (`pid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");


\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shortvideo_comment_zanlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `pid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `pid` (`pid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shortvideo_zanlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `vid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `stid` (`vid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");



\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yuyue_category` (
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

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yuyue_comment` (
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
  KEY `proid` (`proid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yuyue_guige` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `proid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `pic` varchar(255) DEFAULT NULL,
  `sell_price` decimal(11,2) DEFAULT '0.00',
  `danwei` varchar(11) DEFAULT NULL,
  `stock` int(11) unsigned DEFAULT '0',
  `procode` varchar(255) DEFAULT NULL,
  `sales` int(11) DEFAULT '0',
  `ks` varchar(255) DEFAULT NULL,
  `lvprice_data` text,
  `bid` int(11) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `proid` (`proid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yuyue_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `mid` int(11) DEFAULT NULL,
  `ordernum` varchar(255) DEFAULT NULL,
  `title` text,
  `totalprice` float(11,2) DEFAULT NULL,
  `product_price` float(11,2) DEFAULT '0.00',
  `coupon_rid` int(11) DEFAULT NULL COMMENT '优惠券coupon_record的id',
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
  `paytypeid` int(11) DEFAULT NULL,
  `paytype` varchar(50) DEFAULT NULL,
  `paynum` varchar(255) DEFAULT NULL,
  `paytime` int(11) DEFAULT NULL,
  `send_time` bigint(20) DEFAULT NULL COMMENT '发货时间',
  `collect_time` int(11) DEFAULT NULL COMMENT '收货时间',
  `yy_time` varchar(255) DEFAULT NULL,
  `hexiao_code` varchar(100) DEFAULT NULL COMMENT '唯一码 核销码',
  `hexiao_qr` varchar(255) DEFAULT NULL,
  `platform` varchar(255) DEFAULT 'wx',
  `iscomment` tinyint(1) DEFAULT '0',
  `field1` varchar(255) DEFAULT NULL,
  `field2` varchar(255) DEFAULT NULL,
  `field3` varchar(255) DEFAULT NULL,
  `field4` varchar(255) DEFAULT NULL,
  `field5` varchar(255) DEFAULT NULL,
  `delete` tinyint(1) DEFAULT '0',
  `checkmemid` int(11) DEFAULT NULL COMMENT '指定返佣用户ID',
  `fromwxvideo` tinyint(1) DEFAULT '0' COMMENT '是否是视频号过来的订单',
  `scene` int(11) DEFAULT '0' COMMENT '小程序场景',
  `coupon_money` float(11,2) DEFAULT '0.00',
  `propic` varchar(255) DEFAULT NULL,
  `proname` varchar(255) DEFAULT NULL,
  `paidan_money` float(11,2) DEFAULT '0.00',
  `paidan_type` tinyint(255) DEFAULT '0' COMMENT '派单方式',
  `worker_orderid` int(11) DEFAULT '0' COMMENT '派单后的订单id',
  `balance_price` decimal(10,0) DEFAULT '0' COMMENT '尾款金额',
  `num` int(11) DEFAULT NULL,
  `ggname` varchar(255) DEFAULT NULL,
  `balance_pay_orderid` int(11) DEFAULT '0' COMMENT '尾款支付订单ID',
  `balance_pay_status` tinyint(11) DEFAULT '0' COMMENT '尾款支付状态 1已支付  0 未支付',
  `proid` int(11) DEFAULT '0',
  `ggid` int(11) DEFAULT '0',
  `sm_time` int(11) DEFAULT NULL COMMENT '上门时间',
  `worker_id` int(11) DEFAULT '0' COMMENT '服务人员id',
  `fwtype` int(11) DEFAULT '1' COMMENT '1 为到店服务，2 为上门服务',
  `begintime` int(11) DEFAULT '0',
  `endtime` int(11) unsigned zerofill DEFAULT '00000000000',
  `refund_status` tinyint(11) DEFAULT '0',
  `refund_time` int(11) DEFAULT NULL,
  `refund_reason` varchar(255) DEFAULT NULL,
  `refund_money` decimal(11,2) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `code` (`hexiao_code`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yuyue_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `procode` varchar(255) DEFAULT NULL,
  `fuwupoint` varchar(255) DEFAULT NULL,
  `sellpoint` varchar(255) DEFAULT NULL,
  `pic` varchar(255) DEFAULT '',
  `pics` varchar(5000) DEFAULT NULL,
  `sales` int(11) DEFAULT '0',
  `detail` longtext,
  `sell_price` float(11,2) DEFAULT '0.00',
  `sort` int(11) DEFAULT '0',
  `status` int(1) DEFAULT '1',
  `stock` int(11) unsigned DEFAULT '100',
  `createtime` int(11) DEFAULT NULL,
  `guigedata` text,
  `comment_score` decimal(2,1) DEFAULT '5.0',
  `comment_num` int(11) DEFAULT '0',
  `comment_haopercent` int(11) DEFAULT '100',
  `perlimit` int(11) DEFAULT '0',
  `detail_text` text,
  `detail_pics` text,
  `gettj` varchar(255) DEFAULT '-1',
  `gettjurl` varchar(255) DEFAULT NULL,
  `gettjtip` varchar(255) DEFAULT NULL,
  `starttime` int(11) DEFAULT NULL,
  `ischecked` tinyint(1) DEFAULT '1',
  `check_reason` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `balance` decimal(11,2) DEFAULT '0.00',
  `danwei` varchar(10) DEFAULT NULL,
  `cid` varchar(11) DEFAULT '0' COMMENT '分类id',
  `zaohour` int(255) DEFAULT '8' COMMENT '预约早几点',
  `wanhour` int(255) DEFAULT '21' COMMENT '预约晚几点',
  `fwtype` varchar(11) DEFAULT '1' COMMENT '服务方式 1 到店服务',
  `fwpeople` int(11) DEFAULT '0' COMMENT '0 为后台分配，1为用户选择',
  `yynum` int(11) DEFAULT '1' COMMENT '同一时间段预约人数限制',
  `fwpeoid` varchar(255) DEFAULT NULL,
  `fwlong` int(11) DEFAULT '30' COMMENT '服务时长',
  `timejg` int(11) DEFAULT '30' COMMENT '时间间隔 ',
  `jiesuantype` int(11) DEFAULT '0' COMMENT '结算方式 1，按单 ， 2按比例',
  `tcmoney` float(11,2) DEFAULT '0.00',
  `tc_bfb` float(11,2) DEFAULT '0.00',
  `pdprehour` int(11) DEFAULT '1',
  `formdata` text,
  `yyzhouqi` varchar(255) DEFAULT NULL COMMENT '预约周期，周一-周日',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `stock` (`stock`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='商品表';");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yuyue_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `status` tinyint(1) DEFAULT '0',
  `paidantype` tinyint(1) DEFAULT '0',
  `withdraw_weixin` tinyint(1) DEFAULT '1',
  `withdraw_aliaccount` tinyint(1) DEFAULT '1',
  `withdraw_bankcard` tinyint(1) DEFAULT '1',
  `withdrawmin` varchar(255) DEFAULT '10',
  `withdrawfee` varchar(255) DEFAULT '0',
  `businessst` tinyint(1) DEFAULT '0',
  `businessfee` decimal(11,2) DEFAULT '0.00',
  `make_status` tinyint(1) DEFAULT '0',
  `make_domain` varchar(100) DEFAULT NULL,
  `make_appid` varchar(100) DEFAULT NULL,
  `make_token` varchar(255) DEFAULT NULL,
  `make_shopkoufei` tinyint(1) DEFAULT '0',
  `make_access_token` varchar(255) DEFAULT NULL,
  `make_expire_time` int(11) DEFAULT NULL,
  `diyname` varchar(255) DEFAULT NULL COMMENT '自定义人员名称',
  `autoshdays` int(11) DEFAULT '7',
  `autoclose` int(255) DEFAULT '600',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yuyue_worker` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `mid` int(11) DEFAULT NULL,
  `un` varchar(255) DEFAULT NULL,
  `pwd` varchar(255) DEFAULT NULL,
  `realname` varchar(255) DEFAULT NULL,
  `tel` varchar(255) DEFAULT NULL,
  `status` tinyint(11) DEFAULT '1',
  `createtime` int(11) DEFAULT NULL,
  `sort` int(11) DEFAULT '0',
  `comment_score` decimal(2,1) DEFAULT '5.0',
  `comment_num` int(11) DEFAULT '0',
  `comment_haopercent` int(11) DEFAULT '100' COMMENT '好评率',
  `money` decimal(11,2) DEFAULT '0.00',
  `totalmoney` decimal(11,2) DEFAULT '0.00',
  `totalnum` int(11) DEFAULT '0',
  `weixin` varchar(255) DEFAULT NULL,
  `aliaccount` varchar(255) DEFAULT NULL,
  `bankname` varchar(255) DEFAULT NULL,
  `bankcarduser` varchar(255) DEFAULT NULL,
  `bankcardnum` varchar(100) DEFAULT NULL,
  `longitude` varchar(100) DEFAULT NULL,
  `latitude` varchar(100) DEFAULT NULL,
  `headimg` varchar(255) DEFAULT 'https://v2d.diandashop.com/static/img/touxiang.png',
  `jineng` varchar(255) DEFAULT NULL,
  `cid` int(11) DEFAULT '0',
  `desc` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yuyue_worker_category` (
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

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yuyue_worker_comment` (
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COMMENT='配送单评价';");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yuyue_worker_moneylog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `uid` int(11) DEFAULT NULL,
  `money` decimal(11,2) DEFAULT '0.00',
  `after` decimal(11,2) DEFAULT '0.00',
  `createtime` int(11) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `uid` (`uid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yuyue_worker_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) unsigned DEFAULT '0',
  `mid` int(11) DEFAULT NULL,
  `worker_id` int(11) DEFAULT NULL COMMENT '配送员id',
  `orderid` int(11) DEFAULT NULL,
  `ordernum` varchar(100) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `starttime` int(11) DEFAULT NULL,
  `daodiantime` int(11) DEFAULT NULL,
  `quhuotime` int(11) DEFAULT NULL,
  `endtime` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0' COMMENT '0待接单 1已接单正在赶往商家 2已到店 3已取货配送中 4已送达',
  `ticheng` decimal(11,2) DEFAULT '0.00',
  `psfee` decimal(10,2) DEFAULT '0.00' COMMENT '配送费 需要扣除商家的钱',
  `iscomment` tinyint(1) unsigned DEFAULT '0' COMMENT '是否评价',
  `yujitime` int(11) DEFAULT NULL COMMENT '预计送达时间',
  `juli` int(11) DEFAULT NULL COMMENT '商家到用户的距离 米',
  `longitude` varchar(100) DEFAULT NULL COMMENT '商家坐标',
  `latitude` varchar(100) DEFAULT NULL,
  `longitude2` varchar(100) DEFAULT NULL COMMENT '用户坐标',
  `latitude2` varchar(100) DEFAULT NULL,
  `orderinfo` text COMMENT '订单信息',
  `prolist` text COMMENT '商品信息',
  `binfo` text COMMENT '商家信息',
  `make_ordernum` varchar(100) DEFAULT NULL COMMENT '码科跑腿订单号',
  `make_rider_name` varchar(255) DEFAULT NULL COMMENT '码科配送员姓名',
  `make_rider_mobile` varchar(255) DEFAULT NULL COMMENT '码科配送员手机号',
  `comment_num` int(255) DEFAULT '0',
  `comment_score` varchar(255) DEFAULT NULL,
  `comment_haopercent` varchar(255) DEFAULT NULL,
  `fwtype` int(11) DEFAULT '0' COMMENT '1 为到店服务，2 为上门服务',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `psid` (`worker_id`) USING BTREE,
  KEY `orderid` (`orderid`) USING BTREE,
  KEY `ordernum` (`ordernum`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `iscomment` (`iscomment`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_yuyue_worker_withdrawlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `uid` int(11) DEFAULT NULL,
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
  `platform` varchar(255) DEFAULT 'wx',
  `reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `uid` (`uid`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_jobs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增主键',
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '队列名称',
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '有效负载',
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '重试次数',
  `reserved` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '订阅次数',
  `reserve_time` int(10) unsigned DEFAULT '0' COMMENT '订阅时间',
  `available_time` int(10) unsigned DEFAULT '0' COMMENT '有效时间',
  `create_time` int(10) unsigned DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='消息队列';");

if(!pdo_fieldexists2("ddwx_admin_user","tmpl_restaurant_booking")){
	\think\facade\Db::execute("ALTER TABLE  `ddwx_admin_user`
ADD COLUMN `tmpl_restaurant_booking` tinyint(1) DEFAULT 1 AFTER `tmpl_kehuzixun`;");
}

if(!pdo_fieldexists2("ddwx_member_level","up_fxdowncount3")){
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `up_fxdowncount3` int(11) DEFAULT '0' after `up_fxdownlevelid2`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `up_fxdownlevelnum3` varchar(255) DEFAULT '0' after `up_fxdowncount3`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `up_fxdownlevelid3` varchar(255) DEFAULT '0' after `up_fxdownlevelnum3`;");
}
if(!pdo_fieldexists2("ddwx_shop_product","limit_start")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD `limit_start` int(11) DEFAULT '0' after `perlimit`;");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_register_giveset` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `money` float(11,2) DEFAULT NULL,
  `score` int(11) DEFAULT '0',
  `give_coupon` tinyint(1) DEFAULT '0',
  `coupon_ids` varchar(255) DEFAULT NULL,
  `starttime` datetime DEFAULT NULL,
  `endtime` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `aid` (`aid`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

if(!pdo_fieldexists2("ddwx_scoreshop_product","commissiondata5")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product`
MODIFY COLUMN `commissionset` int(2) NULL DEFAULT '0' COMMENT '0按会员等级 1价格比例  2固定金额 3送积分 4极差 5比例和积分 -1不参与分销' AFTER `freightcontent`,
ADD COLUMN `commissiondata5` text NULL AFTER `commissiondata3`,
ADD COLUMN `lvprice` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '是否开启会员价 不同会员等级设置不同价格' AFTER `freightcontent`,
ADD COLUMN `lvprice_data` text NULL AFTER `lvprice`;");
}


if(!pdo_fieldexists2("ddwx_scoreshop_sysset","showcommission")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_sysset`
ADD COLUMN `showcommission` tinyint(1) UNSIGNED NOT NULL DEFAULT '1' AFTER `gettjurl`;");
}

if(!pdo_fieldexists2("ddwx_member_level","team_levelup")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level`
ADD COLUMN `team_levelup` tinyint(1) DEFAULT '0' COMMENT '是否可以给下级升级',
ADD COLUMN `team_levelup_num` int(11) DEFAULT NULL COMMENT '给下级升级数量';");
}

if(!pdo_fieldexists2("ddwx_member_levelup_order","from_mid")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_levelup_order` ADD COLUMN `from_mid` int(11) NULL DEFAULT NULL;");
}

if(!pdo_fieldexists2("ddwx_shop_product","sharetitle")){
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD `sharetitle` varchar(255) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_shop_product","sharepic")){
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD `sharepic` varchar(255) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_shop_product","sharedesc")){
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD `sharedesc` varchar(255) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_shop_product","sharelink")){
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD `sharelink` varchar(255) DEFAULT NULL;");
}

if(!pdo_fieldexists2("ddwx_mendian","commission_money_type")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_mendian`
ADD COLUMN `commission_money_type` tinyint(1) DEFAULT '0' COMMENT '分成类型：0比例 1固定' AFTER `latitude`,
ADD COLUMN `commission_money_percent` decimal(5, 2) NULL AFTER `commission_money_type`,
ADD COLUMN `commission_money` decimal(11, 2) NULL AFTER `commission_money_percent`,
ADD COLUMN `commission_score_type` tinyint(1) DEFAULT '0' COMMENT '积分分成类型：0比例 1固定' AFTER `commission_money`,
ADD COLUMN `commission_score_percent` decimal(5, 2) NULL AFTER `commission_score_type`,
ADD COLUMN `commission_score` int(11) NULL AFTER `commission_score_percent`;");
}

if(!pdo_fieldexists2("ddwx_shop_product","no_discount")){
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD `no_discount` tinyint(1) DEFAULT '0';");
}

if(!pdo_fieldexists2("ddwx_shop_product","barcode")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` ADD COLUMN `barcode` varchar(60) NULL AFTER `procode`;");
}

if(!pdo_fieldexists2("ddwx_shop_order_goods","barcode")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `barcode` varchar(60) NULL AFTER `procode`;");
}

if(!pdo_fieldexists2("ddwx_member_address","company")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_address` ADD COLUMN `company` varchar(255) NULL AFTER `name`;");
}

if(!pdo_fieldexists2("ddwx_shop_order","company")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `company` varchar(255) NULL AFTER `linkman`;");
}


\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_kecheng_category` (
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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='课程分类';");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_kecheng_chapter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `pic` varchar(255) DEFAULT '',
  `detail` longtext,
  `voice_url` varchar(255) DEFAULT '0.00',
  `sort` int(11) DEFAULT '0',
  `status` int(1) DEFAULT '1',
  `createtime` int(11) DEFAULT NULL,
  `video_url` text,
  `kcid` int(11) DEFAULT '0' COMMENT '课程id',
  `ismianfei` int(11) DEFAULT '2',
  `video_duration` varchar(255) DEFAULT NULL,
  `kctype` int(255) DEFAULT NULL COMMENT '1图文 2 音频 3 视频',
  `readnum` int(11) DEFAULT '0',
  `isjinzhi` tinyint(255) DEFAULT '0' COMMENT '是否禁止快进 1为禁止  ',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='课程章节';");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_kecheng_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `pic` varchar(255) DEFAULT '',
  `pics` varchar(5000) DEFAULT NULL,
  `detail` longtext,
  `price` float(11,2) DEFAULT '0.00',
  `sort` int(11) DEFAULT '0',
  `status` int(1) DEFAULT '1',
  `createtime` int(11) DEFAULT NULL,
  `detail_text` text,
  `detail_pics` text,
  `gettj` varchar(255) DEFAULT '-1',
  `gettjurl` varchar(255) DEFAULT NULL,
  `gettjtip` varchar(255) DEFAULT NULL,
  `cid` varchar(11) DEFAULT '0' COMMENT '分类id',
  `readnum` int(11) DEFAULT '0',
  `sxdate` int(11) DEFAULT '0' COMMENT '答题所需时间',
  `isdt` tinyint(2) DEFAULT '1' COMMENT '是否开启答题',
  `dtnum` int(11) DEFAULT '5' COMMENT '每次随机出题数量',
  `hgscore` int(11) DEFAULT '60' COMMENT '多少分合格',
  `join_num` int(11) DEFAULT '0' COMMENT '已有多少人加入学习',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='课程列表';");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_kecheng_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `mid` int(11) DEFAULT NULL,
  `ordernum` varchar(255) DEFAULT NULL,
  `title` text,
  `totalprice` float(11,2) DEFAULT NULL,
  `price` float(11,2) DEFAULT '0.00',
  `createtime` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT '0' COMMENT '0未支付;1已支付;2已发货;3已收货;4关闭;',
  `payorderid` int(11) DEFAULT NULL,
  `paytypeid` int(11) DEFAULT NULL,
  `paytype` varchar(50) DEFAULT NULL,
  `paynum` varchar(255) DEFAULT NULL,
  `paytime` int(11) DEFAULT NULL,
  `platform` varchar(255) DEFAULT 'wx',
  `iscomment` tinyint(1) DEFAULT '0',
  `kcid` int(11) DEFAULT '0',
  `pic` varchar(255) DEFAULT NULL,
  `study_status` float(11,2) DEFAULT '0.00' COMMENT '0未学习  1 已学习',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='课程订单';");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_kecheng_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `mid` int(11) DEFAULT NULL,
  `status` int(1) DEFAULT '0' COMMENT '0 未答完  1已答完',
  `time` int(11) DEFAULT NULL,
  `kcid` int(11) DEFAULT NULL COMMENT '所属课程',
  `timu` varchar(255) DEFAULT NULL,
  `ishg` tinyint(2) DEFAULT '0' COMMENT '是否合格',
  `endtime` int(11) DEFAULT NULL COMMENT '交卷时间',
  `score` int(11) DEFAULT '0' COMMENT '分数',
  `isend` int(255) DEFAULT '0' COMMENT '1 为时间 结束',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='课程考试表';");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_kecheng_recordlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `mid` int(11) DEFAULT NULL,
  `status` int(1) DEFAULT '0' COMMENT '0 未答  1答对了 2 答错了',
  `time` int(11) DEFAULT NULL,
  `kcid` int(11) DEFAULT NULL COMMENT '所属课程',
  `tmid` int(11) DEFAULT '0' COMMENT '题目id',
  `answer` varchar(255) DEFAULT NULL,
  `score` int(11) DEFAULT '0',
  `recordid` int(11) DEFAULT '0' COMMENT 'record id',
  `sort` int(11) DEFAULT '0' COMMENT '题目序号',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='课程答题详情表';");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_kecheng_studylog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `mid` int(11) DEFAULT NULL,
  `mlid` int(11) DEFAULT NULL,
  `title` text,
  `createtime` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT '0' COMMENT '0未支付;1已支付;2已发货;3已收货;4关闭;',
  `kcid` int(11) DEFAULT '0',
  `pic` varchar(255) DEFAULT NULL,
  `currentTime` float(11,2) DEFAULT '0.00' COMMENT '当前播放时间',
  `platform` varchar(255) DEFAULT NULL,
  `jindu` varchar(255) DEFAULT NULL COMMENT '学习进度',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='学习log';");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_kecheng_tiku` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `title` varchar(255) DEFAULT NULL,
  `sort` int(11) DEFAULT '0',
  `status` int(1) DEFAULT '1',
  `createtime` int(11) DEFAULT NULL,
  `score` float(11,2) DEFAULT '0.00' COMMENT '题目分值',
  `type` int(255) DEFAULT NULL COMMENT '1选择题  2填空题',
  `right_option` varchar(255) DEFAULT NULL COMMENT '答案',
  `option_group` text,
  `kcid` int(11) DEFAULT NULL COMMENT '所属课程',
  `jiexi` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='课程题库';");

if(!pdo_fieldexists2("ddwx_scoreshop_product","commission_money_type")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product`
ADD COLUMN `commission_money_type` tinyint(1) DEFAULT '0' COMMENT '分成类型：0比例 1固定' AFTER `commission3`,
ADD COLUMN `commission_money_percent` decimal(5, 2) NULL AFTER `commission_money_type`,
ADD COLUMN `commission_money` decimal(11, 2) NULL AFTER `commission_money_percent`,
ADD COLUMN `commission_score_type` tinyint(1) DEFAULT '0' COMMENT '积分分成类型：0比例 1固定' AFTER `commission_money`,
ADD COLUMN `commission_score_percent` decimal(5, 2) NULL AFTER `commission_score_type`,
ADD COLUMN `commission_score` int(11) NULL AFTER `commission_score_percent`;");
}

if(!pdo_fieldexists2("ddwx_scoreshop_order_goods","mendian_commission")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order_goods`
ADD COLUMN `mendian_commission` decimal(11, 2) NULL DEFAULT '0.00' COMMENT '门店分成' AFTER `iscommission`,
ADD COLUMN `mendian_score` int(11) NULL DEFAULT '0' COMMENT '门店分成积分' AFTER `mendian_commission`,
ADD COLUMN `mendian_iscommission` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '门店佣金是否已发放' AFTER `mendian_score`;");
}

if(!pdo_fieldexists2("ddwx_member_level","team_levelup_id")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `team_levelup_id` varchar(255) DEFAULT NULL COMMENT '给下级升级id';");
}

if(!pdo_fieldexists2("ddwx_admin","remark")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `remark` varchar(255) NULL COMMENT '备注' AFTER `domain`;");
}


\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shop_category2` (
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
  KEY `bid` (`aid`) USING BTREE,
  KEY `pid` (`pid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

if(!pdo_fieldexists2("ddwx_shop_product","cid2")){
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD `cid2` varchar(255) DEFAULT '0' COMMENT '商家的商品分类' AFTER `cid`;");
}

if(!pdo_fieldexists2("ddwx_lipin","coupon_ids")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_lipin` ADD COLUMN `coupon_ids` varchar(255) NULL AFTER `score`;");
}

if(!pdo_fieldexists2("ddwx_member_level","up_give_score")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_give_score` int(11) NULL COMMENT '升级赠送积分' AFTER `team_levelup_id`;");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_tuangou_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `pid` int(11) DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `pic` varchar(255) DEFAULT NULL,
  `status` int(1) DEFAULT '1',
  `sort` int(11) DEFAULT '1',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_tuangou_codelist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `proid` int(11) DEFAULT NULL,
  `content` text,
  `ordernum` varchar(100) DEFAULT NULL,
  `orderid` int(11) DEFAULT NULL,
  `headimg` varchar(255) DEFAULT NULL,
  `nickname` varchar(255) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `buytime` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `proid` (`proid`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_tuangou_comment` (
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
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `orderid` (`orderid`) USING BTREE,
  KEY `ogid` (`ogid`) USING BTREE,
  KEY `proid` (`proid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_tuangou_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `mid` int(11) DEFAULT NULL,
  `ordernum` varchar(255) DEFAULT NULL,
  `buytype` tinyint(1) DEFAULT '1' COMMENT '1单买 2发团 3参团',
  `teamid` int(11) DEFAULT NULL,
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
  `iscomment` tinyint(1) DEFAULT '0',
  `tuimoney` decimal(11,2) DEFAULT '0.00',
  `zongbu_fahuo` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hexiao_code` (`hexiao_code`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_tuangou_product` (
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
  `perlimit` int(11) DEFAULT '0',
  `freighttype` tinyint(1) DEFAULT '1',
  `freightdata` varchar(255) DEFAULT NULL,
  `freightcontent` text,
  `ischecked` tinyint(1) DEFAULT '1',
  `check_reason` varchar(255) DEFAULT NULL,
  `pricedata` text,
  `starttime` int(11) DEFAULT NULL,
  `endtime` int(11) DEFAULT NULL,
  `showtj` varchar(255) DEFAULT NULL,
  `gettj` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `ischecked` (`ischecked`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_tuangou_sysset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `pics` text,
  `autoshdays` int(11) DEFAULT '7',
  `comment` tinyint(1) DEFAULT '1',
  `comment_check` tinyint(1) DEFAULT '1',
  `showjd` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `aid` (`aid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_level_record` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT '0',
  `bid` int(11) DEFAULT '0',
  `levelid` int(11) DEFAULT '0',
  `cid` int(11) DEFAULT '0',
  `qrcode` varchar(255) DEFAULT NULL,
  `sharepic` varchar(255) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `levelstarttime` int(11) DEFAULT '0',
  `levelendtime` int(11) DEFAULT '0',
  `areafenhong_province` varchar(255) DEFAULT NULL,
  `areafenhong_city` varchar(255) DEFAULT NULL,
  `areafenhong_area` varchar(255) DEFAULT NULL,
  `areafenhong` tinyint(1) DEFAULT '0',
  `areafenhongbl` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `levelid` (`levelid`) USING BTREE,
  KEY `levelendtime` (`levelendtime`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `areafenhong_province` (`areafenhong_province`) USING BTREE,
  KEY `areafenhong_city` (`areafenhong_city`) USING BTREE,
  KEY `areafenhong_area` (`areafenhong_area`) USING BTREE,
  KEY `areafenhong` (`areafenhong`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_level_category` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `pid` int(11) DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `pic` varchar(255) DEFAULT NULL,
  `status` int(1) DEFAULT '1',
  `sort` int(11) DEFAULT '1',
  `createtime` int(11) DEFAULT NULL,
  `isdefault` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `pid` (`pid`) USING BTREE,
  KEY `isdefault` (`isdefault`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

if(!pdo_fieldexists2("ddwx_member_level","cid")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `cid` int(11) NOT NULL DEFAULT '0' AFTER `aid`,
ADD INDEX `cid`(`cid`);");
}

if(!pdo_fieldexists2("ddwx_member_level","fenhong_max_money")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `fenhong_max_money` decimal(11, 2) NOT NULL DEFAULT '0' AFTER `fenhong_num`;");
}

if(!pdo_fieldexists2("ddwx_member","total_fenhong")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member`
	ADD COLUMN `total_fenhong` decimal(11, 2) NOT NULL DEFAULT '0' COMMENT '总分红' AFTER `commission`,
	ADD COLUMN `total_fenhong_team` decimal(11, 2) NOT NULL DEFAULT '0' AFTER `total_fenhong`,
	ADD COLUMN `total_fenhong_partner` decimal(11, 2) NOT NULL DEFAULT '0' COMMENT '股东分红' AFTER `total_fenhong_team`,
	ADD COLUMN `total_fenhong_area` decimal(11, 2) NOT NULL DEFAULT '0' AFTER `total_fenhong_partner`;");
}

if(!pdo_fieldexists2("ddwx_member_level","commission4")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD commission4 decimal(11,2) DEFAULT '0' AFTER commission3;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD commission5 decimal(11,2) DEFAULT '0' AFTER commission4;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD commission6 decimal(11,2) DEFAULT '0' AFTER commission5;");
}
if(!pdo_fieldexists2("ddwx_shop_order_goods","isfg")){
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_order_goods ADD isfg tinyint(1) DEFAULT '0';");
}
if(!pdo_fieldexists2("ddwx_member_level","up_fxordermoney_xiao")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD up_fxordermoney_xiao decimal(11,2) DEFAULT '0' AFTER `up_fxorderlevelid`;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD up_fxorderlevelnum_xiao decimal(11,2) DEFAULT '0' AFTER up_fxordermoney_xiao;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD up_fxorderlevelid_xiao decimal(11,2) DEFAULT '0' AFTER up_fxorderlevelnum_xiao;");
}
if(!pdo_fieldexists2("ddwx_shop_order_goods","isteamfenhong")){
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_order_goods ADD isteamfenhong tinyint(1) DEFAULT '0';");
	\think\facade\Db::execute("update ddwx_shop_order_goods set isteamfenhong=1 where isfenhong=1");
}
if(!pdo_fieldexists2("ddwx_member_fenhonglog","type")){
	\think\facade\Db::execute("ALTER TABLE ddwx_member_fenhonglog ADD `type` varchar(255) DEFAULT '';");
	\think\facade\Db::execute("update ddwx_member_fenhonglog set `type`='fenhong' where remark='股东分红'");
	\think\facade\Db::execute("update ddwx_member_fenhonglog set `type`='areafenhong' where remark='区域代理分红'");
	\think\facade\Db::execute("update ddwx_member_fenhonglog set `type`='teamfenhong' where remark='团队分红'");
}



\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_lucky_collage_category` (
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

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_lucky_collage_comment` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_lucky_collage_guige` (
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

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_lucky_collage_jiqilist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `nickname` varchar(255) DEFAULT NULL,
  `headimg` varchar(255) DEFAULT NULL,
  `status` int(1) DEFAULT '1',
  `sort` int(11) DEFAULT '1',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_lucky_collage_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `mid` int(11) DEFAULT NULL,
  `ordernum` varchar(255) DEFAULT NULL,
  `buytype` tinyint(1) DEFAULT '1' COMMENT '1单买 2发团 3参团',
  `teamid` int(11) DEFAULT NULL,
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
  `iszj` tinyint(2) DEFAULT '0' COMMENT '是否中奖  1 为中奖',
  `money` decimal(11,2) DEFAULT '0.00' COMMENT '奖励得红包金额',
  `isjiqiren` tinyint(2) DEFAULT '0' COMMENT '1 为机器人',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `hexiao_code` (`hexiao_code`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_lucky_collage_order_team` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` varchar(100) DEFAULT NULL,
  `proid` int(11) DEFAULT NULL,
  `teamhour` int(11) DEFAULT NULL,
  `teamnum` int(11) DEFAULT NULL,
  `status` int(1) DEFAULT '0' COMMENT '0未支付 1进行中 2成功 3失败',
  `num` int(11) DEFAULT '0',
  `createtime` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `proid` (`proid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_lucky_collage_product` (
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
  `teamnum` int(11) DEFAULT '3',
  `buymax` int(11) DEFAULT '0',
  `teamhour` int(11) DEFAULT NULL,
  `leadermoney` decimal(11,2) DEFAULT '0.00',
  `leaderscore` int(11) DEFAULT '0',
  `freighttype` tinyint(1) DEFAULT '1',
  `freightdata` varchar(255) DEFAULT NULL,
  `freightcontent` text,
  `ischecked` tinyint(1) DEFAULT '1',
  `check_reason` varchar(255) DEFAULT NULL,
  `commissiondata1` text,
  `commissiondata2` text,
  `commissiondata3` text,
  `fy_type` tinyint(1) DEFAULT '1' COMMENT '1 按比例 2 红包',
  `fy_money` decimal(11,2) DEFAULT '0.00' COMMENT '红包比例',
  `fy_money_val` decimal(11,2) DEFAULT '0.00' COMMENT '红包金额',
  `gua_num` int(11) DEFAULT '1' COMMENT '不中奖人数',
  `red_give_mode` tinyint(11) DEFAULT '1' COMMENT '1 返到余额， 2 放到零钱',
  `tklx` int(2) DEFAULT '1' COMMENT '退款路线默认原路退回',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `ischecked` (`ischecked`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_lucky_collage_sysset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `pics` text,
  `autoshdays` int(11) DEFAULT '7',
  `comment` tinyint(1) DEFAULT '1',
  `comment_check` tinyint(1) DEFAULT '1',
  `showjd` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `aid` (`aid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");



if(!pdo_fieldexists2("ddwx_yuyue_order","refund_checkremark")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` ADD COLUMN `refund_checkremark` varchar(255) DEFAULT NULL AFTER `refund_money`;");
}

if(!pdo_fieldexists2("ddwx_admin_set","score_transfer")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
	ADD COLUMN `score_transfer` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `scorebdkyf`,
	ADD COLUMN `money_transfer` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `recharge`;");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_lucky_collage_codelist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `proid` int(11) DEFAULT NULL,
  `content` text,
  `ordernum` varchar(100) DEFAULT NULL,
  `orderid` int(11) DEFAULT NULL,
  `headimg` varchar(255) DEFAULT NULL,
  `nickname` varchar(255) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `buytime` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `proid` (`proid`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");


if(!pdo_fieldexists2("ddwx_member_level","up_give_parent_money")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_give_parent_money` decimal(11, 2) NOT NULL DEFAULT '0' COMMENT '升级给上级赠送余额' AFTER `up_give_score`;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD UNIQUE KEY `wxopenid_tel` (`wxopenid`,`tel`);");
	$adminuserlist = \think\facade\Db::name('admin_user')->where("random_str='' or random_str is null")->select()->toArray();
	foreach($adminuserlist as $adminuser){
		\think\facade\Db::execute("update ddwx_admin_user set `random_str`='".random(16)."' where id=".$adminuser['id']);
	}
}

if(!pdo_fieldexists2("ddwx_member","score_withdraw")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `score_withdraw` int(11) NOT NULL DEFAULT '0' AFTER `score`;");
}

if(!pdo_fieldexists2("ddwx_admin_set","score_withdraw")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
ADD COLUMN `score_withdraw` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `score_transfer`,
ADD COLUMN `score_withdraw_percent_day` decimal(5, 2) UNSIGNED NOT NULL DEFAULT '0' AFTER `score_withdraw`,
ADD COLUMN `score_to_money_percent` decimal(6, 3) UNSIGNED NOT NULL DEFAULT '0' AFTER `score_withdraw_percent_day`;");
}

if(!pdo_fieldexists2("ddwx_member_scorelog","type")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_scorelog` ADD COLUMN `type` tinyint(3) NOT NULL DEFAULT '1' COMMENT '1普通，2允提' AFTER `remark`;");
}

if(!pdo_fieldexists2("ddwx_yuyue_product","datetype")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product`
	ADD COLUMN `datetype` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1时间段，2时间点' AFTER `yyzhouqi`,
	ADD COLUMN `timepoint` varchar(255) DEFAULT '' AFTER `datetype`;");
}


if(!pdo_fieldexists2("ddwx_member_level","up_give_commission")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_give_commission` decimal(11, 2) NOT NULL DEFAULT '0' COMMENT '升级赠送佣金' AFTER `up_give_score`;");
}

if(!pdo_fieldexists2("ddwx_member_level","teamfenhong_money")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `teamfenhong_money` decimal(11, 2) NOT NULL DEFAULT '0' COMMENT '团队分红每单奖励' AFTER `teamfenhongbl`;");
}

if(!pdo_fieldexists2("ddwx_member_level","level_teamfenhong_ids")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level`
ADD COLUMN `level_teamfenhong_ids` varchar(255)  NOT NULL DEFAULT '' AFTER `teamfenhongonly`,
ADD COLUMN `level_teamfenhonglv` int(11) NULL AFTER `level_teamfenhong_ids`,
ADD COLUMN `level_teamfenhongbl` decimal(11, 2) NULL AFTER `level_teamfenhonglv`,
ADD COLUMN `level_teamfenhong_money` decimal(11, 2) NOT NULL DEFAULT '0' AFTER `level_teamfenhongbl`,
ADD COLUMN `level_teamfenhongonly` tinyint(1) NULL DEFAULT '0' AFTER `level_teamfenhong_money`;");
}

if(!pdo_fieldexists2("ddwx_yuyue_product", "start_time")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_yuyue_product ADD start_time varchar(100) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_yuyue_product", "end_time")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_yuyue_product ADD end_time varchar(100) DEFAULT NULL;");
}

if(!pdo_fieldexists2("ddwx_yuyue_product", "yybegintime")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_yuyue_product ADD yybegintime varchar(100) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_yuyue_product", "yyendtime")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_yuyue_product ADD yyendtime varchar(100) DEFAULT NULL;");
}
if(!pdo_fieldexists2("ddwx_yuyue_product", "rqtype")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_yuyue_product ADD rqtype tinyint(3) DEFAULT '1';");
}

if(!pdo_fieldexists2("ddwx_luntan","is_top")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_luntan`
	ADD COLUMN `is_top` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `zan`,
	ADD INDEX(`is_top`);");
}

if(!pdo_fieldexists2("ddwx_collage_product","commissiondata1")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_collage_product`
	  ADD COLUMN `commissiondata1` text,
	  ADD COLUMN `commissiondata2` text,
	  ADD COLUMN `commissiondata3` text;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_collage_order`
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

if(!pdo_fieldexists2("ddwx_member", "total_fenhong_level_team")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_member` ADD COLUMN `total_fenhong_level_team` decimal(11, 2) NOT NULL DEFAULT '0' AFTER `total_fenhong_team`;");
}

if(!pdo_fieldexists2("ddwx_member_level", "commission_parent")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `commission_parent` decimal(11, 2) NOT NULL DEFAULT '0' COMMENT '持续推荐奖励' AFTER `commission6`;");
}

if(!pdo_fieldexists2("ddwx_shop_order_goods", "parent4")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods`
ADD COLUMN `parent4` varchar(255) NULL AFTER `parent3`,
ADD COLUMN `parent4commission` decimal(11, 2) NULL AFTER `parent3commission`;");
}

if(!pdo_fieldexists2("ddwx_member_level", "up_fxorder_condition")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_fxorder_condition` varchar(20) NOT NULL DEFAULT 'or' COMMENT 'or或，and且' AFTER `up_rechargemoney`;");
}

if(!pdo_fieldexists2("ddwx_member_level","up_fxdowncount_and")){
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `up_fxdowncount_and` varchar(255) DEFAULT '0' after `up_fxdownlevelid3`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `up_fxdownlevelnum_and` varchar(255) DEFAULT '0' after `up_fxdowncount_and`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `up_fxdownlevelid_and` varchar(255) DEFAULT '0' after `up_fxdownlevelnum_and`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `up_fxdowncount2_and` int(11) DEFAULT '0' after `up_fxdownlevelid_and`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `up_fxdownlevelnum2_and` varchar(255) DEFAULT '0' after `up_fxdowncount2_and`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_member_level ADD `up_fxdownlevelid2_and` varchar(255) DEFAULT '0' after `up_fxdownlevelnum2_and`;");
}


if(!pdo_fieldexists2("ddwx_member_level","product_teamfenhong_ids")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level`
ADD COLUMN `product_teamfenhong_ids` varchar(255)  NOT NULL DEFAULT '0' AFTER `teamfenhongonly`,
ADD COLUMN `product_teamfenhonglv` int(11) NULL AFTER `product_teamfenhong_ids`,
ADD COLUMN `product_teamfenhong_money` decimal(11, 2) NOT NULL DEFAULT '0' AFTER `product_teamfenhonglv`,
ADD COLUMN `product_teamfenhongonly` tinyint(1) NULL DEFAULT '0' AFTER `product_teamfenhong_money`,
ADD COLUMN `product_teamfenhong_self` tinyint(1) NULL DEFAULT '0' AFTER `product_teamfenhongonly`;");
}

if(!pdo_fieldexists2("ddwx_member_level","teamfenhong_self")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level`
ADD COLUMN `teamfenhong_self` tinyint(1) NULL DEFAULT '0' COMMENT '分红包含自己' AFTER `teamfenhongonly`;");
}

if(!pdo_fieldexists2("ddwx_admin_set","fhjiesuantime_type")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
ADD COLUMN `fhjiesuantime_type` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '分红结算时间类型 0收货后，1付款后' AFTER `fxjiesuantype`;");
}

if(!pdo_fieldexists2("ddwx_admin_set","reg_invite_code")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
ADD COLUMN `reg_invite_code` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0关闭，1开启' AFTER `login_mast`,
ADD COLUMN `reg_invite_code_type` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0手机号' AFTER `reg_invite_code`;");
}


if(!pdo_fieldexists2("ddwx_shop_product","douyin_product_id")){
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD `douyin_product_id` varchar(255) DEFAULT '';");
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD `douyin_check_status` tinyint(2) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD `douyin_status` tinyint(1) DEFAULT NULL;");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_douyin_sysset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `app_id` varchar(255) DEFAULT NULL,
  `app_secret` varchar(255) DEFAULT NULL,
  `shop_id` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  `access_token` varchar(255) DEFAULT NULL,
  `expires_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aid` (`aid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");


if(!pdo_fieldexists2("ddwx_admin_set","wxkfurl")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD `wxkfurl` varchar(255) DEFAULT NULL AFTER `wxkf`;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD `corpid` varchar(255) DEFAULT NULL AFTER `wxkfurl`;");
	\think\facade\Db::execute("update `ddwx_admin_set` set wxkfurl=kfurl");
}


if(!pdo_fieldexists2("ddwx_kecheng_list","commissionset")){
	\think\facade\Db::execute("ALTER TABLE ddwx_kecheng_list ADD `commissiondata1` text;");
	\think\facade\Db::execute("ALTER TABLE ddwx_kecheng_list ADD `commissiondata2` text;");
	\think\facade\Db::execute("ALTER TABLE ddwx_kecheng_list ADD `commissiondata3` text;");
	\think\facade\Db::execute("ALTER TABLE ddwx_kecheng_list ADD `commissionset` tinyint(1) DEFAULT '0';");
}

if(!pdo_fieldexists2("ddwx_business","feepercent_freight")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` MODIFY COLUMN `textset` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `reg_invite_code_type`;");
	\think\facade\Db::execute("ALTER TABLE ddwx_business MODIFY COLUMN `cid` varchar(255) DEFAULT '0';");
	\think\facade\Db::execute("ALTER TABLE ddwx_business ADD `feepercent_freight` float(11,2) DEFAULT '0.00' COMMENT '配送费费率';");
}

if(!pdo_fieldexists2("ddwx_business","is_open")){
  \think\facade\Db::execute("ALTER TABLE ddwx_business ADD `is_open` tinyint(1) DEFAULT '1'");
  \think\facade\Db::execute("ALTER TABLE ddwx_business ADD `autocollecthour` int(11) DEFAULT '168'");
  \think\facade\Db::execute("ALTER TABLE ddwx_business ADD `start_hours2` varchar(100) DEFAULT '00:00'");
  \think\facade\Db::execute("ALTER TABLE ddwx_business ADD `end_hours2` varchar(100) DEFAULT '00:00'");
  \think\facade\Db::execute("ALTER TABLE ddwx_business ADD `start_hours3` varchar(100) DEFAULT '00:00'");
  \think\facade\Db::execute("ALTER TABLE ddwx_business ADD `end_hours3` varchar(100) DEFAULT '00:00'");
}

if(!pdo_fieldexists2("ddwx_wifiprint_set","tmpltype")){
  \think\facade\Db::execute("ALTER TABLE ddwx_wifiprint_set ADD `tmpltype` tinyint(1) DEFAULT '0'");
  \think\facade\Db::execute("ALTER TABLE ddwx_wifiprint_set ADD `tmplcontent` text");
}

if(!pdo_fieldexists2("ddwx_yuyue_category","appid")){
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_category ADD `appid` int(11) DEFAULT '0'");
}

if(!pdo_fieldexists2("ddwx_yuyue_set","isapi")){
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `isapi` int(11) DEFAULT '1' COMMENT '是否接入跑腿';");
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `minminute` int(11) DEFAULT '3'");
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `pic` varchar(255) DEFAULT NULL");
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `desc` varchar(100) DEFAULT NULL");
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `datetype` tinyint(2) DEFAULT '0' COMMENT '开启接入跑腿后选择';");
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `zaohour` int(11) DEFAULT '8' COMMENT '预约早几点';");
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `wanhour` int(11) DEFAULT '21' COMMENT '预约晚几点';");
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `timejg` int(11) DEFAULT '30' COMMENT '时间间隔 默认30分钟';");
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `timepoint` varchar(100) DEFAULT NULL");
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `rqtype` int(11) DEFAULT '1' COMMENT '预约周期';");
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `yybegintime` varchar(100) DEFAULT NUll");
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `yyendtime` varchar(100) DEFAULT NUll");
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `yyzhouqi` varchar(100) DEFAULT NUll COMMENT '周几到周几';");
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `pdprehour` int(11) DEFAULT '1' COMMENT '提前几小时预约';");
}


if(!pdo_fieldexists2("ddwx_yuyue_order","sysOrderNo")){
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_order ADD `sysOrderNo` varchar(255) DEFAULT '' COMMENT '定制返回的订单号';");
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_order ADD `masterName` varchar(255) DEFAULT NULL COMMENT '师傅姓名';");
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_order ADD `errandDistance` float(11,2) DEFAULT '0.00' COMMENT '距离';");
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_order ADD `platformIncome` float(11,2) DEFAULT '0.00' COMMENT '平台收入';");
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_order ADD `firstCategory` int(11) DEFAULT '0'");
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_order ADD `secondCategory` int(11) DEFAULT '0'");
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_order ADD `unit` varchar(255) DEFAULT NULL COMMENT '单位'");
}

if(!pdo_fieldexists2("ddwx_yuyue_set","detailpic")){
  \think\facade\Db::execute("ALTER TABLE ddwx_yuyue_set ADD `detailpic` varchar(255) DEFAULT NULL");
}

if(!pdo_fieldexists2("ddwx_member_levelup_order","levelup_time")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_levelup_order`
	ADD COLUMN `levelup_time` int(11) AFTER `createtime`,
	ADD INDEX `levelup_time`(`levelup_time`),
	ADD INDEX(`beforelevelid`),
	ADD INDEX(`status`);");
}
if(!pdo_fieldexists2("ddwx_shop_order","sysOrderNo")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `sysOrderNo` varchar(255) DEFAULT NULL;");
}

if(!pdo_fieldexists2("ddwx_member_level","up_condition_show")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `up_condition_show` tinyint(1) UNSIGNED NOT NULL DEFAULT '1' AFTER `can_up`;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_mp_menu` MODIFY COLUMN `menudata` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci AFTER `aid`,CHARACTER SET = utf8mb4, COLLATE = utf8mb4_general_ci;");
	try{
		\think\facade\Db::execute("ALTER TABLE `ddwx_member` DROP INDEX `mpopenid_tel` , ADD UNIQUE INDEX `mpopenid_tel` (`mpopenid`, `tel`, `aid`) USING BTREE ,DROP INDEX `wxopenid_tel` ,ADD UNIQUE INDEX `wxopenid_tel` (`wxopenid`, `tel`, `aid`) USING BTREE;");
	}catch(Exception $e) {

	}
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_toupiao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `color1` varchar(255) DEFAULT NULL,
  `color2` varchar(255) DEFAULT NULL,
  `helptext` varchar(255) DEFAULT NULL,
  `starttime` int(11) DEFAULT NULL,
  `endtime` int(11) DEFAULT NULL,
  `canapply` tinyint(1) DEFAULT NULL,
  `apply_check` tinyint(1) DEFAULT NULL,
  `help_check` tinyint(1) DEFAULT NULL,
  `sort` int(11) DEFAULT '0',
  `guize` longtext,
  `sharetitle` varchar(255) DEFAULT NULL,
  `sharepic` varchar(255) DEFAULT NULL,
  `sharedesc` varchar(255) DEFAULT NULL,
  `sharelink` varchar(255) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT '1',
  `readcount` int(11) DEFAULT '0',
  `per_daycount` int(11) DEFAULT '1' COMMENT '每天可投票数',
  `per_allcount` int(11) DEFAULT '0' COMMENT '每人最多总共可投票数',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_toupiao_help` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `headimg` varchar(255) DEFAULT NULL,
  `nickname` varchar(255) DEFAULT NULL,
  `tel` varchar(255) DEFAULT NULL,
  `hid` int(11) DEFAULT NULL,
  `joinid` int(11) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `hid` (`hid`) USING BTREE,
  KEY `joinid` (`joinid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_toupiao_join` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `hid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `weixin` varchar(255) DEFAULT NULL,
  `pic` varchar(255) DEFAULT NULL,
  `pics` varchar(255) DEFAULT NULL,
  `detail_txt` text,
  `detail` longtext,
  `helpnum` int(11) DEFAULT '0',
  `readcount` int(11) DEFAULT '0',
  `createtime` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0' COMMENT '0进行中 1通过 2驳回',
  `reason` varchar(255) DEFAULT NULL,
  `number` varchar(255) DEFAULT NULL,
  `sort` int(11) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `hid` (`hid`) USING BTREE,
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

if(!pdo_fieldexists2("ddwx_member_level","tongji_yeji")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_member_level`
ADD COLUMN `tongji_yeji` tinyint(1) NOT NULL DEFAULT '0' AFTER `up_give_parent_money`,
ADD COLUMN `tongji_yeji_proids` varchar(255) NULL AFTER `tongji_yeji`;");
}
if(!pdo_fieldexists2("ddwx_kecheng_order","parent1")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_order`
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

if(!pdo_fieldexists2("ddwx_coupon","buypro_give_num")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_coupon`
ADD COLUMN `buypro_give_num` varchar(255) DEFAULT '1' AFTER `buyprogive`;");
}


\think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_admin_set`
MODIFY COLUMN `qrcode` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci AFTER `aid`;");

if(!pdo_fieldexists2("ddwx_toupiao","listtype")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_toupiao ADD `listtype` int(11) DEFAULT '0' AFTER color2;");
}


if(!pdo_fieldexists2("ddwx_lucky_collage_order","givetz_money")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_order`
 ADD COLUMN `givetz_money` float(11,2) DEFAULT '0.00' COMMENT '赠送余额',
 ADD COLUMN `givetz_commission` float(11,2) DEFAULT '0.00' COMMENT '赠送团长佣金',
 ADD COLUMN `sharemoney` float(11,2) DEFAULT '0.00' COMMENT '分享奖励余额',
 ADD COLUMN `sharecommission` float(11,2) DEFAULT '0.00' COMMENT '分享奖励佣金',
 ADD COLUMN `sharescore` float(11,2) DEFAULT '0.00' COMMENT '分享奖励积分',
 ADD COLUMN `shareid` int(11) DEFAULT '0' COMMENT '分享者id',
 ADD COLUMN `share_yhqids` varchar(255) DEFAULT NULL COMMENT '分享赠送优惠券';");
}

if(!pdo_fieldexists2("ddwx_lucky_collage_product","member_money")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product`
 ADD COLUMN `member_money` decimal(11,2) DEFAULT '0.00' COMMENT '团长奖励余额',
 ADD COLUMN `zstzcommission` decimal(11,2) DEFAULT '0.00' COMMENT '团长奖励佣金',
 ADD COLUMN `sharescore` int(11) DEFAULT '0' COMMENT '分享奖励积分',
 ADD COLUMN `sharecommission` decimal(11,2) DEFAULT '0.00' COMMENT '分享奖励佣金',
 ADD COLUMN `zj_money` decimal(11,2) DEFAULT '0.00' COMMENT '中奖人奖励金额',
 ADD COLUMN `zjscore` decimal(11,2) DEFAULT '0.00' COMMENT '中奖奖励积分',
 ADD COLUMN `zjcommission` decimal(11,2) DEFAULT '0.00' COMMENT '中奖奖励佣金',
 ADD COLUMN `ktxianzhi` text COMMENT '根据会员等级设置开团限制',
 ADD COLUMN `bzids` varchar(255) DEFAULT NULL,
 ADD COLUMN `zjids` varchar(255) DEFAULT NULL,
 ADD COLUMN `tzjl_type` int(2) DEFAULT '1' COMMENT '1奖励余额 2 奖励积分 3 奖励佣金  4 奖励优惠券',
 ADD COLUMN `tz_yhqids` varchar(255) DEFAULT NULL COMMENT '团长赠送优惠券id',
 ADD COLUMN `sharejltype` int(2) DEFAULT '1' COMMENT '分享拼团奖励 1奖励余额 2 奖励积分 3 奖励佣金  4 奖励优惠券',
 ADD COLUMN `zjjl_type` int(2) DEFAULT '1' COMMENT '中奖奖励 1奖励余额 2 奖励积分 3 奖励佣金  4 奖励优惠券',
 ADD COLUMN `sharemoney` float(11,2) DEFAULT '0.00' COMMENT '分享奖励余额1',
 ADD COLUMN `kaituan_time` varchar(255) DEFAULT NULL,
 ADD COLUMN `kaituan_date` varchar(255) DEFAULT NULL,
 ADD COLUMN `starttime` varchar(255) DEFAULT NULL,
 ADD COLUMN `tsktnum` int(11) DEFAULT '0' COMMENT '同时开团数量限制',
 ADD COLUMN `zstz_yhqids` varchar(255) DEFAULT NULL COMMENT '赠送团长优惠券ids',
 ADD COLUMN `share_yhqids` varchar(255) DEFAULT NULL COMMENT '分享奖励优惠券',
 ADD COLUMN `zj_yhqids` varchar(255) DEFAULT NULL COMMENT '中奖奖励优惠券ids';");
}

if(!pdo_fieldexists2("ddwx_lucky_collage_sysset","timeset")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_sysset`
 ADD COLUMN `timeset` varchar(255) DEFAULT NULL COMMENT '赠送余额',
 ADD COLUMN `duration` varchar(255) DEFAULT NULL COMMENT '赠送团长佣金';");
}

if(!pdo_fieldexists2("ddwx_shop_product","fenhongset")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD `fenhongset` int(11) DEFAULT '1' AFTER commission3");
}

if(!pdo_fieldexists2("ddwx_mp_tmplset","tmpl_shenhe")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_mp_tmplset` ADD `tmpl_shenhe` varchar(255) NULL COMMENT '审核结果通知'");
	\think\facade\Db::execute("ALTER TABLE ddwx_form_order ADD `reason` varchar(255) NULL COMMENT '驳回原因'");
	\think\facade\Db::execute("ALTER TABLE `ddwx_wx_tmplset` ADD `tmpl_shenhe` varchar(255) DEFAULT NULL");
}


if(!pdo_fieldexists2("ddwx_scoreshop_order","freight_content")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_scoreshop_order ADD freight_content text AFTER `freight_time`");
}

if(!pdo_fieldexists2("ddwx_lucky_collage_product","bzjl_type")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product`
	 ADD COLUMN `bzjl_type` tinyint(11) DEFAULT '1' COMMENT '不中奖励类型',
	 ADD COLUMN `bzj_score` int(255) DEFAULT '0',
	 ADD COLUMN `bzj_commission` varchar(255) DEFAULT NULL COMMENT '不中奖佣金',
	 ADD COLUMN `bzj_yhqids` varchar(255) DEFAULT NULL COMMENT '不中奖优惠券ids',
	 ADD COLUMN `isktdate` tinyint(1) DEFAULT '0' COMMENT '默认0 不开启时间 1 为开启时间';");
}


if(!pdo_fieldexists2("ddwx_member", "ktnum")) {
  \think\facade\Db::execute("ALTER TABLE ddwx_member ADD `ktnum` tinyint(1) DEFAULT '0' COMMENT '开团次数限制'");
}

if(!pdo_fieldexists2("ddwx_baidupay_log", "userId")) {
  \think\facade\Db::execute("ALTER TABLE ddwx_baidupay_log ADD `userId` varchar(255) DEFAULT NULL");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_session` (
  `session_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_key` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Http User Agent',
  `login_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '登录时间',
  `login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '登录IP地址',
  `login_ip_location` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP地址转换成的地理位置',
  `platform` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  KEY (`session_id`),
  KEY `aid` (`aid`),
  KEY `mid` (`mid`),
  KEY `login_time` (`login_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");


if(!pdo_fieldexists2("ddwx_freight", "peisong_lng2")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_choujiang` MODIFY COLUMN `gettj` varchar(255) DEFAULT '-1';");
	\think\facade\Db::execute("ALTER TABLE ddwx_freight ADD `peisong_lng2` varchar(255) DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE ddwx_freight ADD `peisong_lat2` varchar(255) DEFAULT NULL;");
}

if(pdo_fieldexists2("ddwx_session", "user_type")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_session` DROP COLUMN `user_type`;");
}

if(!pdo_fieldexists2("ddwx_form_order", "isrefund")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_form_order ADD `isrefund` tinyint(1) DEFAULT '0';");
}


if(!pdo_fieldexists2("ddwx_form_order", "fromurl")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_form_order ADD `fromurl` varchar(255) DEFAULT NULL;");
	if(pdo_fieldexists2("ddwx_session", "user_agent")) {
		\think\facade\Db::execute("ALTER TABLE `ddwx_session` MODIFY COLUMN `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Http User Agent' AFTER `mid`;");
	}
}

if(!pdo_fieldexists2("ddwx_shop_product", "commissionset4")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_shop_product ADD `commissionset4` tinyint(1) DEFAULT '0' COMMENT '是否开启极差分销' AFTER `commission3`");
	\think\facade\Db::execute("update ddwx_shop_product set `commissionset4`=1 where commissionset=4");
	\think\facade\Db::execute("update ddwx_shop_product set `commissionset`=-1 where commissionset=4");

	try{
		\think\facade\Db::execute("ALTER TABLE `ddwx_session` DROP PRIMARY KEY,ADD INDEX `session_id` (`session_id`);");
	}catch(Exception $e){

	}
}

if(!pdo_fieldexists2("ddwx_register_giveset", "wanshan_score")){
	\think\facade\Db::execute("ALTER TABLE ddwx_register_giveset ADD `wanshan_score` int(11) DEFAULT '0'");
	\think\facade\Db::execute("ALTER TABLE ddwx_member ADD is_wanshan_score tinyint(1) DEFAULT '0'");
}

if(!pdo_fieldexists2("ddwx_coupon", "isgive")){
	\think\facade\Db::execute("ALTER TABLE  `ddwx_coupon`
ADD COLUMN `isgive` tinyint(1) NULL DEFAULT '0' COMMENT '是否可赠送' AFTER `tolist`;");
}


if(!pdo_fieldexists2("ddwx_coupon_record", "from_mid")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_coupon_record`
ADD COLUMN `from_mid` int(11) NULL COMMENT '赠送人' AFTER `remark`,
ADD COLUMN `receive_time` int(11) NULL AFTER `from_mid`;");
}

if(!pdo_fieldexists2("ddwx_member", "yqcode")){
	\think\facade\Db::execute("ALTER TABLE ddwx_member ADD `yqcode` varchar(20) DEFAULT NULL COMMENT '邀请码';");
	\think\facade\Db::execute("ALTER TABLE ddwx_admin_set ADD `reg_check` tinyint(1) DEFAULT '0' COMMENT '注册审核';");
	\think\facade\Db::execute("ALTER TABLE ddwx_member ADD `checkst` tinyint(1) DEFAULT '1' COMMENT '是否已审核';");
	\think\facade\Db::execute("ALTER TABLE ddwx_member ADD `checkreason` varchar(255) DEFAULT NULL COMMENT '审核备注';");
	\think\facade\Db::execute("ALTER TABLE ddwx_admin_set_sms ADD `tmpl_checksuccess` varchar(255) DEFAULT NULL COMMENT '审核通过通知';");
	\think\facade\Db::execute("ALTER TABLE ddwx_admin_set_sms ADD `tmpl_checkerror` varchar(255) DEFAULT NULL COMMENT '审核驳回通知';");
	\think\facade\Db::execute("ALTER TABLE ddwx_admin_set_sms ADD `tmpl_checksuccess_st` tinyint(1) DEFAULT '1';");
	\think\facade\Db::execute("ALTER TABLE ddwx_admin_set_sms ADD `tmpl_checkerror_st` tinyint(1) DEFAULT '1';");


	\think\facade\Db::execute("ALTER TABLE `ddwx_article_pinglun` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_article_pinglun_reply` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_business_comment` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_choujiang_record` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_collage_codelist` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_collage_comment` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_fans` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_codelist` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kefu_message` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_kefu_message` MODIFY COLUMN `unickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_lipin_codelist` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_luntan` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_luntan_pinglun` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_luntan_pinglun_reply` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_membercard_record` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_peisong_order_comment` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_codelist` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_seckill_codelist` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_seckill_comment` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_codelist` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_shop_comment` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_sign_record` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_shortvideo_comment` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_shortvideo_comment_reply` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_comment` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_worker_comment` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_tuangou_codelist` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_tuangou_comment` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_comment` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_jiqilist` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
	\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_codelist` MODIFY COLUMN `nickname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL;");
}
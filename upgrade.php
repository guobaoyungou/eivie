<?php

function pdo_fieldexists2($tablename, $fieldname){
	$fields = \think\facade\Db::query("SHOW COLUMNS FROM " . $tablename);
	if(empty($fields)){
		return false;
	}
	foreach ($fields as $field) {
		if ($fieldname == $field['Field']){
			return true;
		}
	}
	return false;
}

//检查表是否存在
 function pdo_fieldexists3($tablename){
	$table = \think\facade\Db::query("SHOW TABLES LIKE '". $tablename."'");
	if(empty($table)){
		return false;
	}else{
		return true;
	}
}

//检查索引是否存在
function pdo_indexExists($tablename, $indexname){
    $table = \think\facade\Db::query("SHOW INDEX FROM " . $tablename. " WHERE key_name = '" .$indexname. "'");
    if(empty($table)){
        return false;
    }else{
        return true;
    }
}

/*
if(!pdo_fieldexists2("ddwx_wifiprint_set", "printauto3")) {
	\think\facade\Db::execute("ALTER TABLE ddwx_wifiprint_set ADD `printauto3` tinyint(1) DEFAULT '1';");
}
\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_test` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `value` longtext,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
*/

if(file_exists('upgrade_restaurant.php')){
	require('upgrade_restaurant.php');
}

if(!pdo_fieldexists2("ddwx_member", "yqcode")){
	require('upgrade_1.php');
}

if(!pdo_fieldexists2("ddwx_member_level","up_catid")) {
	require('upgrade_2.php');
}

if(getcustom('extend_tour')) {
    if (!pdo_fieldexists3("ddwx_tour_ecs_region")) {
    	require('upgrade_3.php');
    }
}

if(!pdo_fieldexists2("ddwx_shop_order_goods","gg_group_title")){
	require('upgrade_4.php');
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_admin_set_custom` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='系统设置定制字段表';");

if(file_exists(ROOT_PATH.'custom.php')){
    $custom = include(ROOT_PATH.'custom.php');
    if($custom){
        require('upgrade_c1.php');
        require('upgrade_c2.php');
    }
    if(getcustom('wx_channels')){
        require('upgrade_wxchannels.php');
    }
}


if(!pdo_fieldexists2("ddwx_admin","ali_appcode_choose")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin` ADD COLUMN `ali_appcode_choose` tinyint(1) DEFAULT '1' COMMENT '1跟随系统0独立设置';");
}
if(!pdo_fieldexists2("ddwx_admin_set","ali_appcode")) {
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `ali_appcode` varchar(60) DEFAULT NULL COMMENT '快递查询code';");
}
\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_ali_wuliu` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`aid` int(11) DEFAULT NULL,
`bid` int(11) DEFAULT '0',
`type` varchar(50) DEFAULT NULL COMMENT '物流类型',
`no` varchar(50) DEFAULT NULL COMMENT '物流单号',
`content` text COMMENT '内容',
`createtime` int(11) DEFAULT NULL COMMENT '创建时间',
PRIMARY KEY (`id`),
KEY `aid` (`aid`),
KEY `bid` (`bid`),
KEY `type` (`type`),
KEY `no` (`no`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='物流查询记录';");
\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_ordernum_change_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `mid` int(11) DEFAULT NULL,
  `tablename` varchar(60) DEFAULT NULL,
  `orderid` int(11) DEFAULT NULL,
  `ordernum` varchar(100) DEFAULT NULL,
  `ordernum_new` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `tablename` (`tablename`) USING BTREE,
  KEY `orderid` (`orderid`) USING BTREE,
  KEY `ordernum` (`ordernum`) USING BTREE,
  KEY `ordernum_new` (`ordernum_new`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT '订单号修改记录';");

if(!pdo_fieldexists2("ddwx_business","bottomImg")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business` ADD COLUMN `bottomImg` varchar(255) DEFAULT '' COMMENT '商品详情公共底部图片';");
}

if(!pdo_fieldexists2("ddwx_shop_sysset","classify_show_stock")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `classify_show_stock` tinyint(1) DEFAULT '0';");
}

if(!pdo_fieldexists2("ddwx_shop_sysset","shipping_pagenum")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` 
    	ADD COLUMN `shipping_pagenum` int(3) NOT NULL DEFAULT '18' COMMENT '送货单每页行数',
    	ADD COLUMN `shipping_linenum` int(3) NOT NULL DEFAULT '2' COMMENT '送货单品名及规格行数';");
}
\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_express_data` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`aid` int(11) NOT NULL DEFAULT 0,
	`bid` int(11) NOT NULL DEFAULT 0,
	`express_data` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '快递数据',
	`createtime` int(11) UNSIGNED NOT NULL DEFAULT 0,
	`updatetime` int(11) UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY (`id`) USING BTREE,
	INDEX `aid`(`aid`) USING BTREE,
	INDEX `bid`(`bid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT '自定义快递数据';");
if(!pdo_fieldexists2("ddwx_business","shipping_pagenum")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business` 
    	ADD COLUMN `shipping_pagetitle` varchar(100) NOT NULL DEFAULT '送货单' COMMENT '送货单名称',
    	ADD COLUMN `shipping_pagenum` int(3) NOT NULL DEFAULT '18' COMMENT '送货单每页行数',
    	ADD COLUMN `shipping_linenum` int(3) NOT NULL DEFAULT '2' COMMENT '送货单品名及规格行数';");
}
if(!pdo_fieldexists2("ddwx_shop_product","contact_require")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` 
    	ADD COLUMN `contact_require` tinyint(1) NULL DEFAULT 0 COMMENT '自动发货|在线卡密是否必填联系人信息 0 否 1 是' AFTER `freighttype`;");
}
if(!pdo_fieldexists2("ddwx_collage_product","contact_require")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_collage_product` 
    	ADD COLUMN `contact_require` tinyint(1) NULL DEFAULT 0 COMMENT '自动发货|在线卡密是否必填联系人信息 0 否 1 是' AFTER `freighttype`;");
}
if(!pdo_fieldexists2("ddwx_kanjia_product","contact_require")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` 
    	ADD COLUMN `contact_require` tinyint(1) NULL DEFAULT 0 COMMENT '自动发货|在线卡密是否必填联系人信息 0 否 1 是' AFTER `freighttype`;");
}
if(!pdo_fieldexists2("ddwx_seckill_product","contact_require")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_seckill_product` 
    	ADD COLUMN `contact_require` tinyint(1) NULL DEFAULT 0 COMMENT '自动发货|在线卡密是否必填联系人信息 0 否 1 是' AFTER `freighttype`;");
}
if(!pdo_fieldexists2("ddwx_tuangou_product","contact_require")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_tuangou_product` 
    	ADD COLUMN `contact_require` tinyint(1) NULL DEFAULT 0 COMMENT '自动发货|在线卡密是否必填联系人信息 0 否 1 是' AFTER `freighttype`;");
}
if(!pdo_fieldexists2("ddwx_scoreshop_product","contact_require")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` 
    	ADD COLUMN `contact_require` tinyint(1) NULL DEFAULT 0 COMMENT '自动发货|在线卡密是否必填联系人信息 0 否 1 是' AFTER `freighttype`;");
}
if(!pdo_fieldexists2("ddwx_lucky_collage_product","contact_require")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product` 
    	ADD COLUMN `contact_require` tinyint(1) NULL DEFAULT 0 COMMENT '自动发货|在线卡密是否必填联系人信息 0 否 1 是' AFTER `freighttype`;");
}
if(!pdo_fieldexists2("ddwx_admin_set","miandan_wx")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` 
    	ADD COLUMN `miandan_wx` tinyint(1)  NOT NULL DEFAULT 0 COMMENT '是否优先使用微信物流助手查询物流轨迹 0 否 1 是';");
}

//商品名称兼容emoj表情
\think\facade\Db::execute("ALTER TABLE `ddwx_collage_product` MODIFY COLUMN `name`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_collage_product` MODIFY COLUMN `sellpoint`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_collage_order` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_collage_order` MODIFY COLUMN `title`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_product` MODIFY COLUMN `name`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_order` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_kanjia_order` MODIFY COLUMN `title`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_seckill_product` MODIFY COLUMN `name`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_seckill_order` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_seckill_order` MODIFY COLUMN `title`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_tuangou_product` MODIFY COLUMN `name`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_tuangou_product` MODIFY COLUMN `sellpoint`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_tuangou_order` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_tuangou_order` MODIFY COLUMN `title`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` MODIFY COLUMN `name`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` MODIFY COLUMN `sellpoint`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_order` MODIFY COLUMN `title`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_choujiang` MODIFY COLUMN `name`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '幸运大转盘活动开始啦';");
\think\facade\Db::execute("ALTER TABLE `ddwx_choujiang` MODIFY COLUMN `guize`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '活动规则';");
\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product` MODIFY COLUMN `name`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_product` MODIFY COLUMN `sellpoint`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;");
\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_order` DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci;");
\think\facade\Db::execute("ALTER TABLE `ddwx_lucky_collage_order` MODIFY COLUMN `title`  varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;");
if(!pdo_fieldexists2("ddwx_shop_sysset","price_show_type")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_sysset` ADD COLUMN `price_show_type` tinyint(1) DEFAULT '0' COMMENT '价格显示方式';");
}
if(!pdo_fieldexists2("ddwx_yuyue_product","tichengtype")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD COLUMN `tichengtype` tinyint(1) DEFAULT '0' COMMENT '提成金额结算';");
}
//修复会员表path以逗号开头的数据（Db::raw( field(id,’.$member['path'.')')会报错）
$lists =  \think\facade\Db::name('member')->where('path like ",%"')->field('id,path')->select();
if($lists){
    foreach($lists as $v){
        $path = ltrim($v['path'],',');
        \think\facade\Db::name('member')->where('id',$v['id'])->update(['path'=>$path]);
    }
}
$lists =  \think\facade\Db::name('member')->where('path_origin like ",%"')->field('id,path_origin')->select();
if($lists){
    foreach($lists as $v){
        $path_origin = ltrim($v['path_origin'],',');
        \think\facade\Db::name('member')->where('id',$v['id'])->update(['path_origin'=>$path_origin]);
    }
}
if(!pdo_fieldexists2("ddwx_member_levelup_order","type")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_levelup_order` ADD COLUMN `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '记录类型 0升级 1降级';");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_admin_wxicp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL,
  `appid` varchar(30) DEFAULT '' COMMENT '关联小程序id',
  `face_verify_task_id` varchar(100) DEFAULT NULL COMMENT '人脸核身任务id',
  `face_is_finish` tinyint(1) DEFAULT '0' COMMENT '核身任务是否已经完成 0 否 1 是',
  `face_status` tinyint(1) DEFAULT '0' COMMENT '任务状态 0. 未开始；1. 等待中；2. 失败；3. 成功',
  `face_finish_time` int(11) DEFAULT NULL COMMENT '核验通知时间',
  `face_send_time` int(11) DEFAULT NULL COMMENT '核验通知发起时间，用于验证通知有效期，避免重复发起',
  `beian_status` varchar(100) DEFAULT NULL COMMENT '备案状态值',
  `beian_reason` text COMMENT '备案返回内容',
  `subject_province` varchar(100) DEFAULT NULL,
  `subject_city` varchar(100) DEFAULT NULL,
  `subject_district` varchar(100) DEFAULT NULL,
  `subject_address` varchar(100) DEFAULT NULL,
  `subject_comment` varchar(200) DEFAULT NULL,
  `subject_type` varchar(100) DEFAULT NULL,
  `subject_name` varchar(100) DEFAULT NULL,
  `subject_certificate_type` varchar(100) DEFAULT NULL,
  `subject_certificate_photo` varchar(255) DEFAULT NULL,
  `subject_certificate_photo_media_id` varchar(100) DEFAULT '',
  `subject_certificate_address` varchar(255) DEFAULT NULL,
  `subject_certificate_number` varchar(100) DEFAULT NULL,
  `subject_residence_permit` varchar(255) DEFAULT NULL,
  `subject_residence_permit_media_id` varchar(100) DEFAULT '',
  `principal_name` varchar(100) DEFAULT NULL,
  `principal_mobile` varchar(20) DEFAULT NULL,
  `principal_emergency_contact` varchar(20) DEFAULT NULL,
  `principal_email` varchar(50) DEFAULT NULL,
  `principal_certificate_type` varchar(100) DEFAULT NULL,
  `principal_certificate_photo_front` varchar(255) DEFAULT NULL,
  `principal_certificate_photo_front_media_id` varchar(100) DEFAULT '',
  `principal_certificate_photo_back` varchar(255) DEFAULT NULL,
  `principal_certificate_photo_back_media_id` varchar(100) DEFAULT '',
  `principal_certificate_number` varchar(100) DEFAULT NULL,
  `principal_certificate_validity_date_start` varchar(50) DEFAULT NULL,
  `principal_certificate_validity_date_end` varchar(50) DEFAULT NULL,
  `principal_certificate_validity_date_cq` tinyint(1) DEFAULT '0',
  `principal_authorization_letter_media_id` varchar(100) DEFAULT '',
  `principal_authorization_letter` varchar(255) DEFAULT NULL,
  `legal_person_name` varchar(50) DEFAULT NULL,
  `legal_person_certificate_number` varchar(20) DEFAULT NULL,
  `service_content_types` varchar(255) DEFAULT NULL,
  `nrlx_details` text COMMENT '[{\"type\":\"\",\"code\":\"\",\"media\":\"\",\"media_id\":\"\"}]',
  `app_comment` varchar(200) DEFAULT NULL,
  `manager_name` varchar(100) DEFAULT NULL,
  `manager_mobile` varchar(20) DEFAULT NULL,
  `manager_email` varchar(50) DEFAULT NULL,
  `manager_emergency_contact` varchar(20) DEFAULT NULL,
  `manager_certificate_type` varchar(100) DEFAULT NULL,
  `manager_certificate_photo_front` varchar(255) DEFAULT NULL,
  `manager_certificate_photo_front_media_id` varchar(100) DEFAULT '',
  `manager_certificate_photo_back` varchar(255) DEFAULT NULL,
  `manager_certificate_photo_back_media_id` varchar(100) DEFAULT '',
  `manager_certificate_number` varchar(50) DEFAULT NULL,
  `manager_certificate_validity_date_start` varchar(20) DEFAULT NULL,
  `manager_certificate_validity_date_end` varchar(20) DEFAULT NULL,
  `manager_certificate_validity_date_cq` tinyint(1) DEFAULT '0' COMMENT '是否长期',
  `manager_authorization_letter` varchar(255) DEFAULT NULL,
  `manager_authorization_letter_media_id` varchar(100) DEFAULT '',
  `commitment_letter` varchar(255) DEFAULT NULL,
  `commitment_letter_media_id` varchar(100) DEFAULT '',
  `business_name_change_letter` varchar(255) DEFAULT NULL,
  `business_name_change_letter_media_id` varchar(100) DEFAULT '',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `aid` (`aid`,`appid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='小程序备案记录';");

if (!pdo_fieldexists2("ddwx_admin_wxicp", "beian_reason")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_wxicp` ADD COLUMN `beian_reason` text COMMENT '备案返回内容';");
}

\think\facade\Db::execute('update ddwx_admin_user set auth_data=replace(auth_data,\'getproduct,Member\\\/index\"\',\'getproduct,Member\\\/choosemember\"\')');//录入订单
\think\facade\Db::execute('update ddwx_admin_user set auth_data=replace(auth_data,\'setst,Member\\\/choosemember\"\',\'setst,Member\\\/choosemember,Member\\\/check\"\')');
\think\facade\Db::execute('update ddwx_admin_user set auth_data=replace(auth_data,\'getproduct,Member\\\/choosemember,Member\\\/check\"\',\'getproduct,Member\\\/choosemember\"\')');//错误替换恢复 2.6.0 移除

if (!pdo_fieldexists2("ddwx_mp_tmplset", "tmpl_message_link")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_mp_tmplset` ADD COLUMN `tmpl_message_link` varchar(11) DEFAULT 'mp' COMMENT '模板消息链接 mp公众号 wx小程序';");
}
if (!pdo_fieldexists2("ddwx_mp_tmplset_new", "tmpl_message_link")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_mp_tmplset_new` ADD COLUMN `tmpl_message_link` varchar(11) DEFAULT 'mp' COMMENT '模板消息链接 mp公众号 wx小程序';");
}
if (!pdo_fieldexists2("ddwx_coupon", "bg_color")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` 
    ADD COLUMN `font_color` varchar(10) DEFAULT '#2B2B2B' COMMENT '字体颜色',
    ADD COLUMN `title_color` varchar(10) DEFAULT '#2B2B2B' COMMENT '标题颜色',
    ADD COLUMN `bg_color` varchar(10) DEFAULT '#FFFFFF' COMMENT '背景颜色';");
}
if (!pdo_fieldexists2("ddwx_shop_product", "print_name")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_product` 
    ADD COLUMN `print_name` varchar(50) DEFAULT NULL COMMENT '小票打印机标题';");
}
\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_video_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `pic` varchar(255) DEFAULT NULL,
  `status` int(1) DEFAULT '1',
  `sort` int(11) DEFAULT '1',
  `createtime` int(11) DEFAULT NULL,
  `type` tinyint(4) DEFAULT '0' COMMENT '0本地视频 1微信视频号视频',
  `video_url` varchar(255) DEFAULT '',
  `video_duration` decimal(10,2) DEFAULT '0.00',
  `ext_param` text COMMENT '扩展数据，json格式',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `bid` (`bid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='视频管理';");
//原单独表的降级记录合并到升级记录表中
$lists = \think\facade\Db::name('member_leveldown_record')->where('1=1')->select()->toArray();
if($lists){
    $data_all = [];
    foreach($lists as $k=>$v){
        $data = $v;
        unset($data['id']);
        unset($data['remark']);
        $data['levelup_time'] = $v['createtime'];
        $data['type'] = 1;
        $data['form0'] = $v['remark'];
        $data_all[] = $data;
    }
    \think\facade\Db::name('member_levelup_order')->insertAll($data_all);
    \think\facade\Db::execute('TRUNCATE TABLE ddwx_member_leveldown_record');
}
if (!pdo_fieldexists2("ddwx_admin_setapp_alipay", "openid_set")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_alipay` ADD COLUMN `openid_set` varchar(10) NULL DEFAULT 'userid' COMMENT 'openid配置管理，userid、openid';");
}
if (!pdo_fieldexists2("ddwx_payorder", "refund_money")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_payorder` 
        ADD COLUMN `refund_money` decimal(11,2) DEFAULT '0.00' COMMENT '退款金额',
        ADD COLUMN `refund_time` int(11) DEFAULT '0' COMMENT '退款时间';");
}

if(!pdo_fieldexists2("ddwx_collage_sysset","team_refund")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_collage_sysset` ADD COLUMN `team_refund` tinyint(1) NOT NULL DEFAULT 0 COMMENT '团长发起订单退款 0:不解散团 1：解散团';");
}

if (!pdo_fieldexists2("ddwx_coupon", "buyyuyueprogive")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` 
        ADD COLUMN `buyyuyueprogive` tinyint(1) NULL DEFAULT 0 COMMENT '购买服务商品赠送 0:关闭 1：开启',
        ADD COLUMN `buyyuyueproids` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '购买服务商品集合',
        ADD COLUMN `buyyuyuepro_give_num` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '购买服务商品数量';");
}

if (!pdo_fieldexists2("ddwx_toupiao", "jump_url")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_toupiao` 
    ADD COLUMN `jump_url` varchar(255) DEFAULT '' COMMENT '投票成功后跳转链接';");
}
if (!pdo_fieldexists2("ddwx_toupiao", "group_id")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_toupiao` 
    ADD COLUMN `group_id` int(11) DEFAULT '0' COMMENT '分组id';");
}

if (!pdo_fieldexists2("ddwx_restaurant_takeaway_sysset", "auto_send_order")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_sysset` 
    ADD COLUMN `auto_send_order` tinyint(1) DEFAULT 0 COMMENT '自动发单 1:开启 0:关闭';");
}

if (pdo_indexExists("ddwx_restaurant_deposit_order", "order_no")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_deposit_order` 
    DROP INDEX `order_no`,
    ADD INDEX `name`(`name`) USING BTREE;");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_score_scoreinlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL,
  `bid` int(11) NOT NULL DEFAULT 0,
  `mid` int(1) NOT NULL DEFAULT 0,
  `type` varchar(20) NOT NULL DEFAULT '' COMMENT '类型',
  `orderid` int(11) NOT NULL DEFAULT 0 COMMENT '订单id',
  `ordernum` varchar(50) NOT NULL DEFAULT '' COMMENT '订单号',
  `score` decimal(12, 3) NOT NULL DEFAULT 0.000,
  `residue` decimal(12, 3) NOT NULL DEFAULT 0.00 COMMENT '剩余积分',
  `createtime` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `updatetime` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `aid`(`aid`) USING BTREE,
  INDEX `mid`(`mid`) USING BTREE,
  INDEX `orderid`(`orderid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='会员消费赠送积分记录';");

if (!pdo_fieldexists2("ddwx_admin_set", "maidan_payaftertourl")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`  ADD COLUMN `maidan_payaftertourl` text DEFAULT NULL;");
}
if (!pdo_fieldexists2("ddwx_restaurant_takeaway_freight", "peisong_lng2")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_takeaway_freight`  
        ADD COLUMN `peisong_lng2` varchar(50) DEFAULT NULL,
        ADD COLUMN `peisong_lat2` varchar(50) DEFAULT NULL;");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_wxpay_fzlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `mid` int(11) DEFAULT NULL,
  `logid` int(11) DEFAULT NULL,
  `openid` varchar(100) DEFAULT NULL,
  `tablename` varchar(60) DEFAULT NULL COMMENT '字段为ordertype,非表名',
  `ordernum` varchar(60) DEFAULT NULL,
  `mch_id` varchar(100) DEFAULT NULL,
  `sub_mchid` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `out_order_no` varchar(100) DEFAULT NULL,
  `receiversjson` text,
  `platform` varchar(255) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `fenzhangmoney` decimal(11,2) DEFAULT '0.00',
  `fenzhangmoney2` decimal(11,2) DEFAULT '0.00',
  `isfenzhang` tinyint(1) DEFAULT '0' COMMENT '0待分账，1已分账，2分账失败，3退款退回，4取消分账',
  `fz_ordernum` varchar(100) DEFAULT NULL,
  `fz_errmsg` varchar(255) DEFAULT NULL,
  `isfinish` tinyint(1) DEFAULT '0' COMMENT '0未结束，1已结束',
  `finish_error_times` int(5) DEFAULT '0',
  `finish_error_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aid` (`aid`) USING BTREE,
  KEY `mid` (`mid`) USING BTREE,
  KEY `logid` (`logid`) USING BTREE,
  KEY `transaction_id` (`transaction_id`) USING BTREE,
  KEY `createtime` (`createtime`) USING BTREE,
  KEY `isfenzhang` (`isfenzhang`) USING BTREE,
  KEY `isfinish` (`isfinish`) USING BTREE,
  KEY `finish_error_times` (`finish_error_times`),
  KEY `finish_error_time` (`finish_error_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;");
if (!pdo_fieldexists2("ddwx_business", "maidan_payaftertourl")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business`  ADD COLUMN `maidan_payaftertourl` text DEFAULT NULL;");
}
if (!pdo_fieldexists2("ddwx_cashback_member", "type")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_cashback_member` ADD COLUMN `type` varchar(30) NOT NULL DEFAULT 'shop' COMMENT '订单类型';");
    \think\facade\Db::execute("ALTER TABLE `ddwx_cashback_member_log` ADD COLUMN `type` varchar(30) NOT NULL DEFAULT 'shop' COMMENT '订单类型';");
}
if (!pdo_fieldexists2("ddwx_payaftergive", "bid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_payaftergive` ADD COLUMN `bid` int(11) NULL DEFAULT '0' AFTER `aid`;");
}
if (!pdo_fieldexists2("ddwx_yuyue_product", "is_open")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` 
        ADD COLUMN `is_open` tinyint(1) NOT NULL DEFAULT 1 COMMENT '显示状态  0：停业 1：营业',
        ADD COLUMN `noopentip` varchar(100) NOT NULL DEFAULT '' COMMENT '停业提示' ;");
}
if (!pdo_fieldexists2("ddwx_yuyue_product", "opentip")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` ADD COLUMN `opentip` varchar(100) NOT NULL DEFAULT '' COMMENT '营业提示';");
}

if(!pdo_fieldexists2("ddwx_business_sales","maidan_sales")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_sales` ADD COLUMN `maidan_sales`  int(11) NOT NULL DEFAULT '0' COMMENT '买单销量';");
}

if (!pdo_fieldexists2("ddwx_kecheng_sysset", "ios_canbuy")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_sysset` ADD COLUMN `ios_canbuy` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'ios端购买 0 ：关闭 1：开启';");
}
if (!pdo_fieldexists2("ddwx_scoreshop_product", "everyday_buymax")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_scoreshop_product` ADD COLUMN `everyday_buymax` int(4) DEFAULT '0';");
}
if (!pdo_fieldexists2("ddwx_admin_wxicp", "applets_other_materials")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_wxicp` 
ADD COLUMN `applets_other_materials` varchar(255) NULL COMMENT '小程序其他附件' AFTER `business_name_change_letter_media_id`,
ADD COLUMN `applets_other_materials_media_id` varchar(100) NULL DEFAULT '' AFTER `applets_other_materials`;");
}
if (!pdo_fieldexists2("ddwx_coupon", "categoryids2")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` ADD COLUMN `categoryids2` varchar(255) NULL COMMENT '指定商家类目ids';");
}
if(!pdo_fieldexists2("ddwx_cashier_order_goods","real_totalprice")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_cashier_order_goods` ADD COLUMN `real_totalprice` decimal(10,2) DEFAULT '0.00' COMMENT '实际商品销售金额 减去了优惠券抵扣会员折扣满减积分抵扣的金额';");
}
if(!pdo_fieldexists2("ddwx_wifiprint_set","rsa_publickey")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_wifiprint_set` 
        ADD COLUMN `rsa_publickey` text COMMENT 'k8打印机平台公钥 验签',
        ADD COLUMN `aes_publickey` text COMMENT 'k8打印机平台公钥 解密公钥';");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_member_wximage_log` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL,
  `mid` int(11) NOT NULL,
  `headimg` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
  `createtime` int(11) NULL DEFAULT NULL,
  `updatetime` int(11) NULL DEFAULT NULL,
  `trace_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `status` tinyint(2) NULL DEFAULT 0 COMMENT '1通过2检测不通过',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `aid`(`aid`) USING BTREE,
  INDEX `mid`(`mid`) USING BTREE,
  INDEX `trace_id`(`trace_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '小程序音视频安全检测' ROW_FORMAT = Dynamic;");
if(!pdo_fieldexists2("ddwx_shop_order_goods","real_totalmoney")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order_goods` ADD COLUMN `real_totalmoney` decimal(11,2) DEFAULT '0.00' COMMENT '实际支付金额（减去优惠券抵扣、会员折扣、满减积分抵扣），区别于real_totalprice)';");
}

if(!pdo_fieldexists2("ddwx_shop_order", "transfer_check")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `transfer_check` tinyint(1) NOT NULL DEFAULT 0 COMMENT '转账审核 -1 驳回 0：待审核 1：通过';");
}
if(!pdo_fieldexists2("ddwx_admin_set", "score_from_xianxiapay")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `score_from_xianxiapay` tinyint(1) DEFAULT '0' COMMENT '货到付款是否赠送积分 0否 1是';");
}
if(!pdo_fieldexists2("ddwx_member_fenhonglog", "send_commission")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_fenhonglog` ADD COLUMN `send_commission`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '发放到佣金的数量';");
}
if(!pdo_fieldexists2("ddwx_member_fenhonglog", "send_money")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_fenhonglog` ADD COLUMN `send_money`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '发放到余额的数量';");
}
if(!pdo_fieldexists2("ddwx_member_fenhonglog", "send_score")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_fenhonglog` ADD COLUMN `send_score`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '发放到积分的数量';");
}
if(!pdo_fieldexists2("ddwx_member_fenhonglog", "send_fuchi")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_fenhonglog` ADD COLUMN `send_fuchi`  decimal(12,2) NULL DEFAULT 0.00 COMMENT '发放到扶持金的数量' ;");
}
if(!pdo_fieldexists2("ddwx_member_fenhonglog", "frommid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_fenhonglog` ADD COLUMN `frommid`  int(11) NULL DEFAULT 0 COMMENT '来源会员id';");
}
if(!pdo_fieldexists2("ddwx_member_fenhonglog", "levelid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_fenhonglog` ADD COLUMN `levelid`  int(11) NULL DEFAULT 0;");
}

if(!pdo_fieldexists2("ddwx_member_moneylog", "uid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_moneylog` ADD COLUMN `uid` int(11) NULL DEFAULT 0;");
}
if(!pdo_fieldexists2("ddwx_member_commissionlog", "uid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_commissionlog` ADD COLUMN `uid` int(11) NULL DEFAULT 0;");
}
if(!pdo_fieldexists2("ddwx_member_scorelog", "uid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_scorelog` ADD COLUMN `uid` int(11) NULL DEFAULT 0;");
}
if(!pdo_fieldexists2("ddwx_member_fenhonglog", "send_remark")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_fenhonglog` ADD COLUMN `send_remark`  varchar (100) NULL DEFAULT '' COMMENT '发放备注';");
}
if(!pdo_fieldexists2("ddwx_member_fenhonglog", "status")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_fenhonglog` ADD COLUMN `status`  tinyint(1) NULL DEFAULT 1 COMMENT '是否发放 0未发放 1已发放 2已退回';");
    //结算老订单分红
//    \app\common\Fenhong::jiesuanAll();
}
if(!pdo_fieldexists2("ddwx_cashier", "is_use_mid_search")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_cashier` ADD COLUMN `is_use_mid_search`  tinyint(1) DEFAULT '1' COMMENT '会员ID搜索';");
}
if(!pdo_fieldexists2("ddwx_member_commissionlog", "fhid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_commissionlog` ADD COLUMN `fhid`  int(11) DEFAULT 0 COMMENT '来源分红数据id';");
}
if(!pdo_fieldexists2("ddwx_admin_set_sms","code_length")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` 
    ADD COLUMN `tmpl_orderpay_txy` varchar(64) DEFAULT NULL,
    ADD COLUMN `tmpl_orderfahuo_txy` varchar(64) DEFAULT NULL,
    ADD COLUMN `tmpl_collagesuccess_txy` varchar(64) DEFAULT NULL,
    ADD COLUMN `tmpl_tixianerror_txy` varchar(64) DEFAULT NULL,
    ADD COLUMN `tmpl_checkerror_txy` varchar(64) DEFAULT NULL,
    ADD COLUMN `tmpl_tuierror_txy` varchar(64) DEFAULT NULL,
    ADD COLUMN `tmpl_tuisuccess_txy` varchar(64) DEFAULT NULL,
    ADD COLUMN `tmpl_tixiansuccess_txy` varchar(64) DEFAULT NULL,
    ADD COLUMN `tmpl_fenxiaosuccess_txy` varchar(64) DEFAULT NULL,
    ADD COLUMN `tmpl_restaurant_booking_txy` varchar(64) DEFAULT NULL,
    ADD COLUMN `code_length` tinyint(1) DEFAULT '0' COMMENT '变量长度 0:不限制 1:6个字符';");
}

if(pdo_fieldexists2("ddwx_yuyue_product","cid")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product` MODIFY COLUMN cid VARCHAR(64);");
}
if(!pdo_fieldexists2("ddwx_shop_order", "lipin_dhcode")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_order` ADD COLUMN `lipin_dhcode` varchar(60) NOT NULL DEFAULT '' COMMENT '礼品卡兑换码';");
}
if(!pdo_fieldexists2("ddwx_mingpian_set", "bgbtncolor")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_mingpian_set` ADD COLUMN `bgbtncolor` tinyint(1) NULL DEFAULT '0' COMMENT '背景|按钮颜色 0默认 1跟随系统';");
}
if(!pdo_fieldexists2("ddwx_coupon", "give_num_type")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_coupon` ADD COLUMN `give_num_type` tinyint(1) NULL DEFAULT '0' COMMENT '赠送数量类型,0按设置数量,1按设置数量*购买数量';");
}
if(!pdo_fieldexists2('ddwx_cashier','cashdesk_give_score_set')){
    \think\facade\Db::execute("ALTER TABLE `ddwx_cashier`
        ADD COLUMN `cashdesk_give_score_set` tinyint(1) DEFAULT '1' COMMENT '积分赠送  1按系统设置消费送积分 2按商品独立设置送积分';");
}
if(!pdo_fieldexists2('ddwx_business_sysset','weixin_withdraw_max')){
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` 
        ADD COLUMN	`weixin_withdraw_max` decimal(11,2) DEFAULT '0.00' COMMENT '商户提现自动打款-微信限额',
        ADD COLUMN	`alipay_withdraw_max` decimal(11,2) DEFAULT '0.00' COMMENT '商户提现自动打款-支付宝限额';");
}
if(!pdo_fieldexists2("ddwx_maidan_order","refund_money")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_maidan_order` ADD COLUMN `refund_money`  float(10,2) NULL DEFAULT 0 AFTER `remark`;");
}
\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS  `ddwx_maidan_refund_order` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `aid` int(11) DEFAULT NULL,
      `bid` int(11) DEFAULT '0',
      `mdid` int(11) DEFAULT NULL,
      `mid` int(11) DEFAULT NULL,
      `refund_ordernum` varchar(60) DEFAULT NULL,
      `orderid` int(11) DEFAULT '0',
      `ordernum` varchar(60) DEFAULT NULL,
      `title` text,
      `product_price` float(11,2) DEFAULT '0.00',
      `createtime` int(11) DEFAULT NULL,
      `status` int(11) DEFAULT '0' COMMENT '0未支付;1已支付;2已发货,3已收货',
      `message` varchar(255) DEFAULT NULL,
      `remark` varchar(255) DEFAULT NULL,
      `payorderid` int(11) DEFAULT NULL,
      `paytypeid` int(11) DEFAULT NULL,
      `paytype` varchar(50) DEFAULT NULL,
      `paynum` varchar(60) DEFAULT NULL,
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
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='买单退款记录表';");

if(!pdo_fieldexists2("ddwx_member_fenhonglog","sendtime_yj")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_fenhonglog` ADD COLUMN `sendtime_yj` int(10) DEFAULT '0' COMMENT '预计发放时间';");
}
if(!pdo_fieldexists2("ddwx_admin_set","reg_invite_code_show")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set`
ADD COLUMN `reg_invite_code_show` tinyint(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '显示邀请人邀请码 0隐藏，1显示' AFTER `reg_invite_code_type`;");
}
\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_pay_transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT NULL,
  `transaction_num` varchar(100) DEFAULT NULL COMMENT '支付流水号',
  `payorderid` int(11) DEFAULT NULL,
  `orderid` int(11) DEFAULT NULL,
  `ordernum` varchar(100) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `money` decimal(11,2) DEFAULT '0.00',
  `score` int(11) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL COMMENT 'shop collage scoreshop kanjia seckill recharge maidan designerpage form',
  `createtime` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0' COMMENT '0未支付 1已支付 2已取消',
  `paynum` varchar(100) DEFAULT NULL,
  `paytypeid` tinyint(1) DEFAULT '0' COMMENT '1余额支付 2微信支付 3支付宝支付 4货到付款 5转账汇款 11百度小程序 12头条小程序',
  `paytype` varchar(200) DEFAULT NULL,
  `paytime` int(11) DEFAULT NULL,
  `paypics` text,
  `check_status` tinyint(1) DEFAULT NULL COMMENT '0待审核，1审核通过，2驳回',
  `check_remark` varchar(200) DEFAULT NULL,
  `platform` varchar(100) DEFAULT NULL,
  `issettle` tinyint(1) DEFAULT '0',
  `isbusinesspay` tinyint(1) DEFAULT '0',
  `issxpay` tinyint(1) DEFAULT '0',
  `refund_money` decimal(11,2) DEFAULT '0.00' COMMENT '退款金额',
  `refund_time` int(11) DEFAULT '0' COMMENT '退款时间',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `bid` (`bid`),
  KEY `mid` (`mid`),
  KEY `orderid` (`orderid`),
  KEY `ordernum` (`ordernum`),
  KEY `payorderid` (`payorderid`),
  KEY `transaction_num` (`transaction_num`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='支付流水';");

if(!pdo_fieldexists2("ddwx_coupon_record","is_api_get")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_coupon_record` ADD COLUMN `is_api_get` tinyint(1) NULL DEFAULT '0' COMMENT '是否api领取';");
}
if(!pdo_fieldexists2("ddwx_coupon_record","api_params")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_coupon_record` ADD COLUMN `api_params` varchar(120) NULL DEFAULT '' COMMENT 'api关键参数，json格式';");
}
if(!pdo_fieldexists2("ddwx_yuyue_set","show_free")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_set` ADD COLUMN `show_free` tinyint(1) NOT NULL DEFAULT '0' COMMENT '显示免费 1显示 0关闭';");
}
/*************新版商家转账功能 start********************/
if(!pdo_fieldexists2("ddwx_admin_set","wx_transfer_type")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set` ADD COLUMN `wx_transfer_type`  tinyint(1) NULL DEFAULT 0 COMMENT '使用微信新版商家转账功能 0关闭 1开启';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_mp","wxpay_wechatpay_pem")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_mp` ADD COLUMN `wxpay_wechatpay_pem`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信支付平台证书v3使用';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_mp","transfer_scene_id")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_mp` ADD COLUMN `transfer_scene_id`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '商家转账场景ID(目前仅支持1005)';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_mp","transfer_scene_type")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_mp` ADD COLUMN `transfer_scene_type`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '转账场景报备信息-岗位类型';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_mp","transfer_scene_content")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_mp` ADD COLUMN `transfer_scene_content`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '转账场景报备信息-报酬说明';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_mp","wxpay_mchkey_v3")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_mp` ADD COLUMN `wxpay_mchkey_v3`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信支付APIv3密钥密钥';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_wx","transfer_scene_id")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_wx` ADD COLUMN `transfer_scene_id`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '商家转账场景ID(目前仅支持1005)';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_wx","transfer_scene_type")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_wx` ADD COLUMN `transfer_scene_type`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '转账场景报备信息-岗位类型';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_wx","transfer_scene_content")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_wx` ADD COLUMN `transfer_scene_content`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '转账场景报备信息-报酬说明';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_wx","wxpay_mchkey_v3")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_wx` ADD COLUMN `wxpay_mchkey_v3`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信支付APIv3密钥密钥';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_wx","wxpay_wechatpay_pem")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_wx` ADD COLUMN `wxpay_wechatpay_pem`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信支付平台证书v3使用';");
}
if(!pdo_fieldexists2("ddwx_member_withdrawlog","wx_package_info")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_withdrawlog` ADD COLUMN `wx_package_info`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '微信商家转账确认页package信息';");
}
if(!pdo_fieldexists2("ddwx_member_withdrawlog","wx_state")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_withdrawlog` ADD COLUMN `wx_state`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信商家转账状态\r\nACCEPTED: 转账已受理\r\nPROCESSING: 转账处理中，转账结果尚未明确，如一直处于此状态，建议检查账户余额是否足够\r\nWAIT_USER_CONFIRM: 待收款用户确认，可拉起微信收款确认页面进行收款确认\r\nTRANSFERING: 转账结果尚未明确，可拉起微信收款确认页面再次重试确认收款\r\nSUCCESS: 转账成功\r\nFAIL: 转账失败\r\nCANCELING: 商户撤销请求受理成功，该笔转账正在撤销中\r\nCANCELLED: 转账撤销完成';");
}
if(!pdo_fieldexists2("ddwx_member_withdrawlog","wx_transfer_bill_no")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_withdrawlog` ADD COLUMN `wx_transfer_bill_no`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '微信转账单号，微信商家转账系统返回的唯一标识';");
}
if(!pdo_fieldexists2("ddwx_member_withdrawlog","wx_transfer_msg")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_withdrawlog` ADD COLUMN `wx_transfer_msg`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信转账错误信息';");
}

if(!pdo_fieldexists2("ddwx_member_commission_withdrawlog","wx_package_info")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_commission_withdrawlog` ADD COLUMN `wx_package_info`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '微信商家转账确认页package信息';");
}
if(!pdo_fieldexists2("ddwx_member_commission_withdrawlog","wx_state")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_commission_withdrawlog` ADD COLUMN `wx_state`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信商家转账状态\r\nACCEPTED: 转账已受理\r\nPROCESSING: 转账处理中，转账结果尚未明确，如一直处于此状态，建议检查账户余额是否足够\r\nWAIT_USER_CONFIRM: 待收款用户确认，可拉起微信收款确认页面进行收款确认\r\nTRANSFERING: 转账结果尚未明确，可拉起微信收款确认页面再次重试确认收款\r\nSUCCESS: 转账成功\r\nFAIL: 转账失败\r\nCANCELING: 商户撤销请求受理成功，该笔转账正在撤销中\r\nCANCELLED: 转账撤销完成';");
}
if(!pdo_fieldexists2("ddwx_member_commission_withdrawlog","wx_transfer_bill_no")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_commission_withdrawlog` ADD COLUMN `wx_transfer_bill_no`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '微信转账单号，微信商家转账系统返回的唯一标识';");
}
if(!pdo_fieldexists2("ddwx_member_commission_withdrawlog","wx_transfer_msg")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_commission_withdrawlog` ADD COLUMN `wx_transfer_msg`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信转账错误信息';");
}

if(!pdo_fieldexists2("ddwx_business_withdrawlog","wx_package_info")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_withdrawlog` ADD COLUMN `wx_package_info`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '微信商家转账确认页package信息';");
}
if(!pdo_fieldexists2("ddwx_business_withdrawlog","wx_state")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_withdrawlog` ADD COLUMN `wx_state`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信商家转账状态\r\nACCEPTED: 转账已受理\r\nPROCESSING: 转账处理中，转账结果尚未明确，如一直处于此状态，建议检查账户余额是否足够\r\nWAIT_USER_CONFIRM: 待收款用户确认，可拉起微信收款确认页面进行收款确认\r\nTRANSFERING: 转账结果尚未明确，可拉起微信收款确认页面再次重试确认收款\r\nSUCCESS: 转账成功\r\nFAIL: 转账失败\r\nCANCELING: 商户撤销请求受理成功，该笔转账正在撤销中\r\nCANCELLED: 转账撤销完成';");
}
if(!pdo_fieldexists2("ddwx_business_withdrawlog","wx_transfer_bill_no")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_withdrawlog` ADD COLUMN `wx_transfer_bill_no`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '微信转账单号，微信商家转账系统返回的唯一标识';");
}
if(!pdo_fieldexists2("ddwx_business_withdrawlog","wx_transfer_msg")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_withdrawlog` ADD COLUMN `wx_transfer_msg`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信转账错误信息';");
}
if(!pdo_fieldexists2("ddwx_business_withdrawlog","platform")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_withdrawlog` ADD COLUMN `platform`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '操作平台';");
}
if(pdo_fieldexists3('ddwx_wx_transfer_log')){
    \think\facade\Db::execute("ALTER TABLE `ddwx_wx_transfer_log` MODIFY COLUMN `status_code`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '微信支付 API 返回的状态码，用于表示转账操作的状态';");
}
\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_wx_transfer_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `mid` int(11) DEFAULT '0' COMMENT '会员id',
  `ordernum` varchar(255) DEFAULT NULL COMMENT '订单号',
  `money` decimal(12,2) DEFAULT NULL COMMENT '转账的金额',
  `realname` varchar(255) DEFAULT NULL COMMENT '收款人的真实姓名',
  `desc` varchar(255) DEFAULT NULL COMMENT '对此次转账操作的描述信息',
  `data_tbl` varchar(255) DEFAULT NULL COMMENT '与该转账操作相关的数据表名称',
  `data_id` int(11) DEFAULT NULL COMMENT '与该转账操作相关的数据表的行的唯一标识符',
  `status_code` varchar(50) DEFAULT NULL COMMENT '微信支付 API 返回的状态码，用于表示转账操作的状态',
  `createtime` int(11) DEFAULT NULL COMMENT '转账操作的时间戳',
  `package_info` text COMMENT '用户确认页面的信息',
  `state` varchar(255) DEFAULT NULL COMMENT '转账状态',
  `transfer_bill_no` varchar(255) DEFAULT NULL COMMENT '微信支付返回的转账单号',
  `message` text COMMENT '转账失败的原因',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='新版微信转账记录';");

if(!pdo_fieldexists2("ddwx_admin_setapp_wx","sign_type")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_wx` ADD COLUMN `sign_type`  tinyint(1) NULL DEFAULT 0 COMMENT '微信支付加密验签方式 0平台证书 1支付公钥';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_wx","public_key_id")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_wx` ADD COLUMN `public_key_id`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '支付公钥ID';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_wx","public_key_pem")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_wx` ADD COLUMN `public_key_pem`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '支付公钥文件路径';");
}

if(!pdo_fieldexists2("ddwx_admin_setapp_mp","sign_type")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_mp` ADD COLUMN `sign_type`  tinyint(1) NULL DEFAULT 0 COMMENT '微信支付加密验签方式 0平台证书 1支付公钥';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_mp","public_key_id")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_mp` ADD COLUMN `public_key_id`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '支付公钥ID';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_mp","public_key_pem")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_mp` ADD COLUMN `public_key_pem`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '支付公钥文件路径';");
}
if(!pdo_fieldexists2('ddwx_payorder','wxpay_typeid')){
    \think\facade\Db::execute("ALTER TABLE `ddwx_payorder` ADD COLUMN `wxpay_typeid` int(11) NOT NULL DEFAULT '0' COMMENT '微信支付模式 0 普通模式1  服务商模式2  二级商户3  随行付4  汇付天下斗拱5  银盛支付6 通联支付 7 微信收款码（定制pay_wx_native_h5）8 B2b模式';");
}
//预约服务人员
if(!pdo_fieldexists2("ddwx_yuyue_worker_withdrawlog","wx_package_info")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_worker_withdrawlog` ADD COLUMN `wx_package_info`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '微信商家转账确认页package信息';");
}
if(!pdo_fieldexists2("ddwx_yuyue_worker_withdrawlog","wx_state")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_worker_withdrawlog` ADD COLUMN `wx_state`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信商家转账状态\r\nACCEPTED: 转账已受理\r\nPROCESSING: 转账处理中，转账结果尚未明确，如一直处于此状态，建议检查账户余额是否足够\r\nWAIT_USER_CONFIRM: 待收款用户确认，可拉起微信收款确认页面进行收款确认\r\nTRANSFERING: 转账结果尚未明确，可拉起微信收款确认页面再次重试确认收款\r\nSUCCESS: 转账成功\r\nFAIL: 转账失败\r\nCANCELING: 商户撤销请求受理成功，该笔转账正在撤销中\r\nCANCELLED: 转账撤销完成';");
}
if(!pdo_fieldexists2("ddwx_yuyue_worker_withdrawlog","wx_transfer_bill_no")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_worker_withdrawlog` ADD COLUMN `wx_transfer_bill_no`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '微信转账单号，微信商家转账系统返回的唯一标识';");
}
if(!pdo_fieldexists2("ddwx_yuyue_worker_withdrawlog","wx_transfer_msg")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_worker_withdrawlog` ADD COLUMN `wx_transfer_msg`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信转账错误信息';");
}
//配送员
if(!pdo_fieldexists2("ddwx_peisong_withdrawlog","wx_package_info")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_withdrawlog` ADD COLUMN `wx_package_info`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '微信商家转账确认页package信息';");
}
if(!pdo_fieldexists2("ddwx_peisong_withdrawlog","wx_state")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_withdrawlog` ADD COLUMN `wx_state`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信商家转账状态\r\nACCEPTED: 转账已受理\r\nPROCESSING: 转账处理中，转账结果尚未明确，如一直处于此状态，建议检查账户余额是否足够\r\nWAIT_USER_CONFIRM: 待收款用户确认，可拉起微信收款确认页面进行收款确认\r\nTRANSFERING: 转账结果尚未明确，可拉起微信收款确认页面再次重试确认收款\r\nSUCCESS: 转账成功\r\nFAIL: 转账失败\r\nCANCELING: 商户撤销请求受理成功，该笔转账正在撤销中\r\nCANCELLED: 转账撤销完成';");
}
if(!pdo_fieldexists2("ddwx_peisong_withdrawlog","wx_transfer_bill_no")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_withdrawlog` ADD COLUMN `wx_transfer_bill_no`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '微信转账单号，微信商家转账系统返回的唯一标识';");
}
if(!pdo_fieldexists2("ddwx_peisong_withdrawlog","wx_transfer_msg")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_peisong_withdrawlog` ADD COLUMN `wx_transfer_msg`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信转账错误信息';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_wx","wxpay_plate_serialno")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_wx` ADD COLUMN `wxpay_plate_serialno`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '平台证书序列号';");
}

if(!pdo_fieldexists2("ddwx_admin_setapp_mp","wxpay_plate_serialno")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_mp` ADD COLUMN `wxpay_plate_serialno`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '平台证书序列号';");
}
//app配置
if(!pdo_fieldexists2("ddwx_admin_setapp_app","transfer_scene_id")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_app` ADD COLUMN `transfer_scene_id`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '商家转账场景ID(目前仅支持1005)';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_app","transfer_scene_type")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_app` ADD COLUMN `transfer_scene_type`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '转账场景报备信息-岗位类型';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_app","transfer_scene_content")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_app` ADD COLUMN `transfer_scene_content`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '转账场景报备信息-报酬说明';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_app","wxpay_mchkey_v3")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_app` ADD COLUMN `wxpay_mchkey_v3`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信支付APIv3密钥密钥';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_app","wxpay_wechatpay_pem")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_app` ADD COLUMN `wxpay_wechatpay_pem`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信支付平台证书v3使用';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_app","sign_type")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_app` ADD COLUMN `sign_type`  tinyint(1) NULL DEFAULT 0 COMMENT '微信支付加密验签方式 0平台证书 1支付公钥';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_app","public_key_id")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_app` ADD COLUMN `public_key_id`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '支付公钥ID';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_app","public_key_pem")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_app` ADD COLUMN `public_key_pem`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '支付公钥文件路径';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_app","wxpay_plate_serialno")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_app` ADD COLUMN `wxpay_plate_serialno`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '平台证书序列号';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_app","wxpay_serial_no")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_app` ADD COLUMN `wxpay_serial_no`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '商户证书序列号';");
}
/*************新版商家转账功能 end********************/

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_wechat_send_tmpl_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT '0',
  `bid` int(11) NOT NULL DEFAULT '0',
  `mid` int(11) NOT NULL DEFAULT '0',
  `tmpl_name` varchar(30) NOT NULL DEFAULT '' COMMENT '模版名称',
  `tmpl_content` text COMMENT '发送内容 存json',
  `m_url` varchar(60) NOT NULL DEFAULT '' COMMENT '跳转链接',
  `mdid` int(11) NOT NULL DEFAULT '0',
  `tmpl_content_new` text,
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0未发送 1已发送',
  `type` varchar(20) DEFAULT NULL COMMENT '类型：sendhttmpl，sendtmpl，sendhtwxtmpl 等..',
  `createtime` int(11) DEFAULT NULL,
  `updatetime` int(11) DEFAULT NULL COMMENT '发送时间',
  PRIMARY KEY (`id`),
  KEY `status` (`status`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='微信模版消息统一发放数据表';");

if(pdo_fieldexists2("ddwx_wechat_send_tmpl_data","tmpl_content_a")) {
    \think\facade\Db::execute("ALTER TABLE ddwx_wechat_send_tmpl_data CHANGE COLUMN tmpl_content_a tmpl_content text;");
}
if(pdo_fieldexists2("ddwx_wechat_send_tmpl_data","tmpl_content_b")) {
    \think\facade\Db::execute("ALTER TABLE ddwx_wechat_send_tmpl_data CHANGE COLUMN tmpl_content_b tmpl_content_new text;");
}
if(!pdo_fieldexists2("ddwx_sxpay_fenzhang","ledger_type")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_sxpay_fenzhang` ADD COLUMN `ledger_type` tinyint(1) UNSIGNED NULL DEFAULT 1 COMMENT '分账方式：1同步，2异步';");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_sxpay_paylog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `ordernum` varchar(60) DEFAULT NULL COMMENT '订单号',
  `tablename` varchar(60) DEFAULT NULL,
  `uuid` varchar(60) DEFAULT NULL COMMENT '天阙平台订单号',
  `mno` varchar(100) DEFAULT NULL COMMENT '商户编号',
  `ordNo` varchar(100) DEFAULT NULL COMMENT '商户订单号',
  `amt` varchar(100) DEFAULT NULL COMMENT '订单总金额(元)',
  `payType` varchar(30) DEFAULT NULL COMMENT '支付渠道',
  `payWay` varchar(30) DEFAULT NULL COMMENT '支付方式',
  `fenzhang_jsondata` text,
  `transaction_id` varchar(60) DEFAULT NULL COMMENT '微信交易号',
  `notify_data` text,
  `createtime` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  `fz_uuid` varchar(60) DEFAULT NULL COMMENT '分账订单号',
  `fz_status` varchar(20) NULL DEFAULT '' COMMENT '分账结果：00 分账成功,01 分账失败,05 分账中',
  `fz_msg` varchar(60),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");

if(pdo_fieldexists2("ddwx_sxpay_paylog","fz_status")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_sxpay_paylog` MODIFY COLUMN `fz_status` varchar(20) NULL DEFAULT '' COMMENT '分账结果：00 分账成功,01 分账失败,05 分账中';");
}
if(!pdo_fieldexists2("ddwx_sxpay_paylog","fz_uuid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_sxpay_paylog` ADD COLUMN `fz_uuid` varchar(60) DEFAULT NULL COMMENT '分账订单号';");
}
if(!pdo_fieldexists2("ddwx_restaurant_shop_order_goods","remark")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_restaurant_shop_order_goods` 
            ADD COLUMN `remark` varchar(100) DEFAULT NULL COMMENT '备注';");
}
if(!pdo_fieldexists2("ddwx_admin_setapp_alipay","pay_mode")){
	\think\facade\Db::execute("ALTER TABLE `ddwx_admin_setapp_alipay` 
		ADD `pay_mode` tinyint(1) DEFAULT 1 COMMENT '支付模式 1:普通支付 2:直付通支付',
		ADD `msid` varchar(20) DEFAULT NULL COMMENT '直付通二级商户id';");
}
if(!pdo_fieldexists2("ddwx_miandan_order","shop_detail_list")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_miandan_order` ADD COLUMN `shop_detail_list` text NULL AFTER `shop_goods_count`,
 ADD COLUMN `createtime` int(11) NULL;");
}
if(!pdo_fieldexists2("ddwx_member_commission_record","levelid")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_commission_record` ADD COLUMN `levelid` int(11) DEFAULT NULL COMMENT '等级ID';");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_miandan_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT '0',
  `status` tinyint(1) DEFAULT NULL COMMENT '物流助手面单开关，1开启，0关闭，null使用admin_set.miandanst设置',
  `query_status` tinyint(1) DEFAULT NULL COMMENT '优先使用物流助手查询物流，1开启，0关闭，null使用admin_set.miandan_wx设置',
  `print_type` tinyint(1) DEFAULT '0' COMMENT '打印面单类型，1：一联单，0：二联单',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `bid` (`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='物流助手电子面单设置';");

if(!pdo_fieldexists2("ddwx_yuyue_product","gettj")){
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_product`
         ADD COLUMN `gettj` varchar(100) DEFAULT '-1' COMMENT '购买等级限制',
         ADD COLUMN `gettjurl` varchar(100) DEFAULT NULL COMMENT '无权限跳转链接',
	     ADD COLUMN `gettjtip` varchar(100) DEFAULT NULL COMMENT '无权限提示';");
}
if (!pdo_fieldexists2("ddwx_freight", "select_address_status")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_freight` ADD COLUMN  `select_address_status` tinyint(1) DEFAULT '0' COMMENT '选择收货地址1开启0关闭';");
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_payorder_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ordernum` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ordernum` (`ordernum`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='支付回调记录';");

if (pdo_fieldexists2("ddwx_luntan_pinglun", "content")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_luntan_pinglun` MODIFY COLUMN `content` TEXT NULL;");
}
if (pdo_fieldexists2("ddwx_luntan_pinglun_reply", "content")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_luntan_pinglun_reply` MODIFY COLUMN `content` TEXT NULL;");
}
if (!pdo_fieldexists2("ddwx_business_sysset", "platform_fee_type")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_sysset` ADD COLUMN `platform_fee_type` tinyint(1) DEFAULT '0' COMMENT '商家返款平台费率计算 0后计算1先计算';");
}
if (!pdo_fieldexists2("ddwx_kecheng_list", "showtj")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_kecheng_list` ADD COLUMN `showtj` varchar(100) DEFAULT '-1' COMMENT '显示等级';");
}

if (!pdo_fieldexists2("ddwx_admin_upload", "remote_type")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_upload` ADD COLUMN `remote_type` varchar(20) NULL COMMENT '附件类型，aliyun,qiniu,tencent,local';");
}
if (!pdo_fieldexists2("ddwx_wx_tmplset", "tmpl_orderpay")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_wx_tmplset` ADD COLUMN `tmpl_orderpay` varchar(200) DEFAULT NULL COMMENT '支付成功通知';");
}
if (!pdo_fieldexists2("ddwx_member_level", "apply_level_range")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level` ADD COLUMN `apply_level_range` tinyint(1) NULL DEFAULT '1' COMMENT '申请等级：1所有等级，2小于等于推荐人等级，3小于推荐人等级' AFTER `apply_paymoney`;");
}
if (pdo_fieldexists2("ddwx_shop_comment", "content_pic")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_shop_comment` MODIFY COLUMN `content_pic` text NULL;");
}
if (pdo_fieldexists2("ddwx_business_comment", "content_pic")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_business_comment` MODIFY COLUMN `content_pic` text NULL;");
}
if (!pdo_fieldexists2("ddwx_form_order", "parent1")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_form_order` 
        ADD COLUMN `parent1` int(11) DEFAULT NULL,
        ADD COLUMN `parent2` int(11) DEFAULT NULL,
        ADD COLUMN `parent3` int(11) DEFAULT NULL,
        ADD COLUMN `parent1commission` decimal(11,2) DEFAULT '0.00',
        ADD COLUMN `parent2commission` decimal(11,2) DEFAULT '0.00',
        ADD COLUMN `parent3commission` decimal(11,2) DEFAULT '0.00',
        ADD COLUMN `is_send_commission` tinyint(1) DEFAULT '0' COMMENT '是否发放分销佣金';");
}
if(pdo_fieldexists2("ddwx_member_level","up_proid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_member_level`
        MODIFY COLUMN `up_proid` text COMMENT '';");
}
if(pdo_fieldexists3('ddwx_mendian_withdrawlog')) {
    if (!pdo_fieldexists2("ddwx_mendian_withdrawlog", "wx_package_info")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian_withdrawlog` ADD COLUMN `wx_package_info`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '微信商家转账确认页package信息';");
    }
    if (!pdo_fieldexists2("ddwx_mendian_withdrawlog", "wx_state")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian_withdrawlog` ADD COLUMN `wx_state`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信商家转账状态\r\nACCEPTED: 转账已受理\r\nPROCESSING: 转账处理中，转账结果尚未明确，如一直处于此状态，建议检查账户余额是否足够\r\nWAIT_USER_CONFIRM: 待收款用户确认，可拉起微信收款确认页面进行收款确认\r\nTRANSFERING: 转账结果尚未明确，可拉起微信收款确认页面再次重试确认收款\r\nSUCCESS: 转账成功\r\nFAIL: 转账失败\r\nCANCELING: 商户撤销请求受理成功，该笔转账正在撤销中\r\nCANCELLED: 转账撤销完成';");
    }
    if (!pdo_fieldexists2("ddwx_mendian_withdrawlog", "wx_transfer_bill_no")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian_withdrawlog` ADD COLUMN `wx_transfer_bill_no`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '微信转账单号，微信商家转账系统返回的唯一标识';");
    }
    if (!pdo_fieldexists2("ddwx_mendian_withdrawlog", "wx_transfer_msg")) {
        \think\facade\Db::execute("ALTER TABLE `ddwx_mendian_withdrawlog` ADD COLUMN `wx_transfer_msg`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信转账错误信息';");
    }
}

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_shortcut_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL,
  `bid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `menus` text NULL,
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `bid` (`bid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='快捷菜单表';");

if (!pdo_fieldexists2("ddwx_admin_wxicp", "subject_other_materials")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_wxicp` 
        ADD COLUMN `subject_other_materials` varchar(255) NULL COMMENT '主体其他附件' AFTER `applets_other_materials_media_id`,
        ADD COLUMN `subject_other_materials_media_id` varchar(100) NULL AFTER `subject_other_materials`;");
}
if (!pdo_fieldexists2("ddwx_mp_tmplset_new", "tmpl_yuyue_jiedan")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_mp_tmplset_new` 
        ADD COLUMN `tmpl_yuyue_jiedan` varchar(64) DEFAULT NULL COMMENT '预约接单提醒';");
}
if (!pdo_fieldexists2("ddwx_yuyue_set", "finish_confirm")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_set` 
        ADD COLUMN `finish_confirm` tinyint(1) DEFAULT '0' COMMENT '服务完成确认：1开启，0关闭',
        ADD COLUMN `confirm_type` tinyint(1) DEFAULT '1' COMMENT '确认方式：1会员确认，2商家确认',
        ADD COLUMN `autoconfirm` int(11) DEFAULT '1' COMMENT '自动确认天数';");
}
if(!pdo_fieldexists2("ddwx_yuyue_order", "isticheng")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_yuyue_order` 
        ADD COLUMN `isticheng` tinyint(1) DEFAULT '0' COMMENT '是否服务提成：1是，0否';");
}
if(!pdo_fieldexists2("ddwx_cashback_member", "ogid")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_cashback_member` 
        ADD COLUMN `ogid` int(11) DEFAULT '0';");
}
if(!pdo_fieldexists2("ddwx_cashback", "shuoming")) {
    \think\facade\Db::execute("ALTER TABLE `ddwx_cashback` 
        ADD COLUMN `shuoming` text NULL COMMENT '说明';");
}
\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_order_collect_reward` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT 0,
  `start_time` int(11) DEFAULT 0 COMMENT '开始时间',
  `end_time` int(11) DEFAULT 0 COMMENT '结束时间',
  `reward_type` tinyint(1) DEFAULT 0 COMMENT '奖励类型：1 积分 2优惠券 3余额 4佣金',
  `score` int(11) DEFAULT 0 COMMENT '奖励积分',
  `coupon_id` int(11) DEFAULT 0 COMMENT '优惠券id',
  `money` decimal(11,2) DEFAULT '0.00' COMMENT '奖励余额',
  `commission` decimal(11,2) DEFAULT '0.00' COMMENT '奖励佣金',
  `order_type` varchar(100) DEFAULT '' COMMENT '订单范围',
  `platform`  varchar(50) DEFAULT '' COMMENT '使用平台',
  `gettj` varchar(255) DEFAULT '-1' COMMENT '参与条件 -1不限制',
  `min_order_amount` decimal(11,2) DEFAULT '0.00' COMMENT '单笔订单满额门槛',
  `max_daily_return` int(11) DEFAULT 0 COMMENT '每账号每日最多返单数',
  `max_month_return` int(11) DEFAULT 0 COMMENT '每账号每月最多返单数',
  `prompt` varchar(255) DEFAULT '' COMMENT '引导话术',
  `bgcolor` varchar(50) DEFAULT '#EF9B39' COMMENT '背景颜色',
  `fontcolor` varchar(50) DEFAULT '' COMMENT '字体颜色',
  `status` tinyint(1) DEFAULT 0 COMMENT '状态：0关闭 1开启',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='订单确认收货奖励';");

\think\facade\Db::execute("CREATE TABLE IF NOT EXISTS `ddwx_order_collect_reward_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) DEFAULT NULL,
  `bid` int(11) DEFAULT 0,
  `mid` int(11) DEFAULT NULL,
  `orderid` int(11) DEFAULT 0 COMMENT '订单id',
  `ordernum` varchar(60) DEFAULT NULL COMMENT '订单号',
  `ordertype` varchar(20) DEFAULT '' COMMENT '订单类型',
  `reward_type` tinyint(1) DEFAULT 0 COMMENT '奖励类型：1 积分 2优惠券 3余额 4佣金',
  `reward` decimal(11,2) DEFAULT '0.00' COMMENT '奖励',
  `ordertime` int(11) DEFAULT NULL COMMENT '订单时间',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `mid` (`mid`),
  KEY `orderid` (`orderid`),
  UNIQUE KEY `ordertype` (`ordertype`, `orderid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='订单确认收货奖励记录';");
//定制短信通道
if(!pdo_fieldexists2('ddwx_admin_set_sms','tmpl_send_coupon_st')){
    \think\facade\Db::execute("ALTER TABLE `ddwx_admin_set_sms` 
        ADD COLUMN `tmpl_send_coupon_st` tinyint(1) DEFAULT '1' COMMENT '发放优惠券通知开关',
        ADD COLUMN `tmpl_send_coupon`  varchar(64) DEFAULT NULL COMMENT '发放优惠券通知', 
        ADD COLUMN `tmpl_coupon_expired_st` tinyint(1) DEFAULT '1' COMMENT '优惠券快过期通知开关',
        ADD COLUMN `tmpl_coupon_expired`  varchar(64) DEFAULT NULL COMMENT '优惠券快过期通知通知',
        ADD COLUMN `tmpl_admin_operate_money_st` tinyint(1) DEFAULT '1' COMMENT '后台操作余额变更开关',
        ADD COLUMN `tmpl_admin_operate_money`  varchar(64) DEFAULT NULL COMMENT '后台操作余额变更',
        ADD COLUMN `tmpl_admin_recharge_money_st` tinyint(1) DEFAULT '1' COMMENT '余额充值通知开关',
        ADD COLUMN `tmpl_admin_recharge_money`  varchar(64) DEFAULT NULL COMMENT '余额充值通知',
        ADD COLUMN `tmpl_member_uplevel_st` tinyint(1) DEFAULT '1' COMMENT '后台操作余额变更开关',
        ADD COLUMN `tmpl_member_uplevel`  varchar(64) DEFAULT NULL COMMENT '后台操作余额变更',   
        ADD COLUMN `tmpl_member_uplevel_expired_st` tinyint(1) DEFAULT '1' COMMENT '后台操作余额变更开关',
        ADD COLUMN `tmpl_member_uplevel_expired`  varchar(64) DEFAULT NULL COMMENT '后台操作余额变更';");
}
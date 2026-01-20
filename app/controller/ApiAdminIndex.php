<?php
/**
 * 点大商城（www.diandashop.com） - 微信公众号小程序商城系统!
 * Copyright © 2020 山东点大网络科技有限公司 保留所有权利
 * =========================================================
 * 版本：V2
 * 授权主体：shop.guobaoyungou.cn
 * 授权域名：guobaoyungou.cn
 * 授权码：TZJcxBSGGdtDBIxFerKVJo
 * ----------------------------------------------
 * 您只能在商业授权范围内使用，不可二次转售、分发、分享、传播
 * 任何企业和个人不得对代码以任何目的任何形式的再发布
 * =========================================================
 */

//管理员中心
namespace app\controller;
use think\facade\Db;
class ApiAdminIndex extends ApiAdmin
{
	public function index(){
		$where = ['aid'=>aid,'uid'=>uid];
		if(bid == 0){
			$set = Db::name('admin_set')->field('name,logo,desc,tel')->where('aid',aid)->find();
		}else{
			$set = Db::name('business')->field('name,logo,desc,tel,money,is_open')->where('id',bid)->find();
		}
        $uinfo = array();
		$uinfo['bid'] = $this->user['bid'];
		$uinfo['un'] = $this->user['un'];
		$uinfo['tmpl_orderconfirm'] = $this->user['tmpl_orderconfirm'];
		$uinfo['tmpl_orderpay'] = $this->user['tmpl_orderpay'];
		$uinfo['tmpl_ordershouhuo'] = $this->user['tmpl_ordershouhuo'];
		$uinfo['tmpl_ordertui'] = $this->user['tmpl_ordertui'];
		$uinfo['tmpl_withdraw'] = $this->user['tmpl_withdraw'];
		$uinfo['tmpl_formsub'] = $this->user['tmpl_formsub'];
		$uinfo['tmpl_kehuzixun'] = $this->user['tmpl_kehuzixun'];
		$uinfo['tmpl_recharge'] = $this->user['tmpl_recharge'];
        $uinfo['tmpl_maidanpay'] = $this->user['tmpl_maidanpay'];
		$uinfo['tmpl_hotelbooking_success'] = 0;
		$uinfo['shownotice'] = false;
		$noticeauthdata = json_decode($this->user['notice_auth_data'],true);
		foreach($noticeauthdata as $k=>$notice){
			if(in_array($notice,$noticeauthdata)){
				$uinfo['shownotice'] = true;
			}
		}

        $uinfo['tmpl_orderconfirm_show'] = 0;
        $uinfo['tmpl_orderpay_show'] = 0;
        $uinfo['tmpl_ordershouhuo_show'] = 0;
        $uinfo['tmpl_ordertui_show'] = 0;
        $uinfo['tmpl_withdraw_show'] = 0;
        $uinfo['tmpl_formsub_show'] = 0;
        $uinfo['tmpl_kehuzixun_show'] = 0;
        $uinfo['tmpl_recharge_show'] = 0;
        $uinfo['tmpl_maidanpay_show'] = 0;
		$notice_auth_data = json_decode($this->user['notice_auth_data'],true);
        foreach($notice_auth_data as $v){
            $uinfo[$v.'_show'] = 1;
        }
        $where = [];
		$where['aid'] = aid;
		$where['bid'] = bid;
        $whereMd = '';
        if($this->user['mdid']){
            $whereMd = 'mdid='.$this->user['mdid'];
            }
        $where_fuwu = '';
        $where_province = [];
        $where_supplier = '';
        $where_shop = [];
		$count0 = 0 + Db::name('shop_order')->where($where)->where('status',0)->where($whereMd)->where($where_fuwu)->where($where_province)->where($where_supplier)->where($where_shop)->count();
		$count1 = 0 + Db::name('shop_order')->where($where)->where('status',1)->where($whereMd)->where($where_fuwu)->where($where_province)->where($where_supplier)->where($where_shop)->count();
		$count2 = 0 + Db::name('shop_order')->where($where)->where('status',2)->where($whereMd)->where($where_fuwu)->where($where_province)->where($where_supplier)->where($where_shop)->count();
		$count3 = 0 + Db::name('shop_order')->where($where)->where('status',3)->where($whereMd)->where($where_fuwu)->where($where_province)->where($where_supplier)->where($where_shop)->count();

		$count4 = 0 + Db::name('shop_refund_order')->where($where)->where('refund_status','in', [1,4])->count();
		$collageCount = 0 + Db::name('collage_order')->where($where)->count();
		$luckycollageCount = 0 + Db::name('lucky_collage_order')->where($where)->count();
		$kanjiaCount = 0 + Db::name('kanjia_order')->where($where)->count();
		$seckillCount = 0 + Db::name('seckill_order')->where($where)->count();
		$scoreshopCount = 0 + Db::name('scoreshop_order')->where($where)->where($where_province)->count();
		$yuyueorderCount = 0 + Db::name('yuyue_order')->where(['aid'=>aid,'bid'=>bid])->count();
		$tuangouCount = 0 + Db::name('tuangou_order')->where($where)->count();
        $cycleCount = 0 + Db::name('cycle_order')->where($where)->count();
		$todayMoney = Db::name('payorder')->where('aid',aid)->where('bid',bid)->where('status',1)->where('paytime','>',strtotime(date("Y-m-d")))->sum('money');

		$productCount = Db::name('shop_product')->where(['aid'=>aid,'bid'=>bid])->count();
		$formlogCount = Db::name('form_order')->where(['aid'=>aid,'bid'=>bid])->count();


		$hxwehere = [];
		$hxwehere['order.aid'] = aid;
		$hxwehere['order.bid'] = bid;
		if($this->user['mdid']){
			$hxwehere['order.uid'] = $this->user['id'];
		}
		$hexiaoCount = Db::name('hexiao_order')->alias('order')->field('member.nickname,member.headimg,order.*')->join('member member','member.id=order.mid')->where($hxwehere)->count();

		//$hexiaoCount = Db::name('hexiao_order')->where($hxwehere)->count();
		$mdwhere = [];
		$mdwhere[] = ['maidan_order.aid','=',aid];
		$mdwhere[] = ['maidan_order.bid','=',bid];
		$mdwhere[] = ['maidan_order.status','=',1];
		if($this->user['mdid']){
			$mdwhere[] = ['maidan_order.mdid','=',$this->user['mdid']];
		}
		$maidanCount = Db::name('maidan_order')->alias('maidan_order')->field('member.nickname,member.headimg,maidan_order.*')->join('member member','member.id=maidan_order.mid','left')->where($mdwhere)->count();
	
		if($this->user['auth_type']==0){
			$auth_data = json_decode($this->user['auth_data'],true);
			$auth_path = [];
			foreach($auth_data as $v){
				$auth_path = array_merge($auth_path,explode(',',$v));
			}
			$auth_data = $auth_path;
		}else{
			$auth_data = 'all';
		}
        $custom = ['showOrder'=>true];
		$showcollageorder = false;
		$showkanjiaorder = false;
		$showseckillorder = false;
		$showscoreshoporder = false;
		$showluckycollageorder = false;
		$showyuyueorder = false;
		$showtuangouorder = false;
		$showformlog = false;
		$showshoporder=false;
		$showweightorder=false;
		$showyuekeorder=false;
        $showcycleorder = false;
        $showrestaurantproduct = false;
        $showrestauranttable = false;
        $show_auth = [];
		if($auth_data=='all' || in_array('ShopOrder/*',$auth_data)){
			$showshoporder = true;
		}
        if($auth_data=='all' || in_array('WeightOrder/*',$auth_data)){
            $showweightorder = true;
        }
		if($auth_data=='all' || in_array('CollageOrder/*',$auth_data)){
			$showcollageorder = true;
		}
		if($auth_data=='all' || in_array('KanjiaOrder/*',$auth_data)){
			$showkanjiaorder = true;
		}
		if($auth_data=='all' || in_array('SeckillOrder/*',$auth_data)){
			$showseckillorder = true;
		}
		if($auth_data=='all' || in_array('ScoreshopOrder/*',$auth_data)){
			$showscoreshoporder = true;
		}
		if($auth_data=='all' || in_array('YuyueOrder/*',$auth_data)){
			$showyuyueorder = true;
		}
		if($auth_data=='all' || in_array('LuckyCollageOrder/*',$auth_data)){
			$showluckycollageorder = true;
		}
		if($auth_data=='all' || in_array('TuangouOrder/*',$auth_data)){
			$showtuangouorder = true;
		}
		if($auth_data=='all' || in_array('Form/*',$auth_data)){
			$showformlog = true;
		}
		if($auth_data=='all' || in_array('Maidan/*',$auth_data) || in_array('BusinessMaidan/*',$auth_data)){
			$showmaidanlog = true;
		}
		if($auth_data=='all' || in_array('CycleOrder/*',$auth_data)){
            $showcycleorder = true;
        }
        

		$showshortvideo = false;
		$showrecharge = false;
		if(bid>0 && getcustom('business_withdraw')){
			$showrecharge = true;
		}
		$showworkorder = false;
        $showworkadd = false;
		$workordercount = 0;
		if($auth_data=='all' || in_array('ScoreshopProduct/*,ScoreshopCode/*',$auth_data)){
            }
        if($auth_data=='all' || in_array('ScoreshopOrder/*',$auth_data)){
            $show_auth[] = 'ScoreshopOrder';
        }
        if($auth_data=='all' || in_array('KanjiaOrder/*',$auth_data)){
            $show_auth[] = 'KanjiaOrder';
        }
        if($auth_data=='all' || in_array('CollageOrder/*',$auth_data)){
            $show_auth[] = 'CollageOrder';
        }
        if($auth_data=='all' || in_array('SeckillOrder/*',$auth_data)){
            $show_auth[] = 'SeckillOrder';
        }
        if($auth_data=='all' || in_array('TuangouOrder/*',$auth_data)){
            $show_auth[] = 'TuangouOrder';
        }
        if($auth_data=='all' || in_array('LuckyCollageOrder/*',$auth_data)){
            $show_auth[] = 'LuckyCollageOrder';
        }
        if($auth_data=='all' || in_array('YuyueOrder/*',$auth_data)){
            $show_auth[] = 'YuyueOrder';
        }
        if($auth_data=='all' || in_array('CycleOrder/*',$auth_data)){
            $show_auth[] = 'CycleOrder';
        }
		$wxtmplset = Db::name('wx_tmplset')->where('aid',aid)->find();
		$tmpl_orderconfirmId = $wxtmplset['tmpl_orderconfirm'];
		$uinfo['tmpl_orderconfirmNum'] = Db::name('member_tmplnum')->where('mid',mid)->where('tmplid',$wxtmplset['tmpl_orderconfirm'])->value('num');
		$uinfo['tmpl_orderpayNum'] = Db::name('member_tmplnum')->where('mid',mid)->where('tmplid',$wxtmplset['tmpl_orderpay'])->value('num');
		$uinfo['tmpl_ordershouhuoNum'] = Db::name('member_tmplnum')->where('mid',mid)->where('tmplid',$wxtmplset['tmpl_ordershouhuo'])->value('num');
		$uinfo['tmpl_ordertuiNum'] = Db::name('member_tmplnum')->where('mid',mid)->where('tmplid',$wxtmplset['tmpl_ordertui'])->value('num');
		$uinfo['tmpl_withdrawNum'] = Db::name('member_tmplnum')->where('mid',mid)->where('tmplid',$wxtmplset['tmpl_withdraw'])->value('num');
		$uinfo['tmpl_kehuzixunNum'] = Db::name('member_tmplnum')->where('mid',mid)->where('tmplid',$wxtmplset['tmpl_kehuzixun'])->value('num');
		if(!$uinfo['tmpl_orderconfirmNum']) $uinfo['tmpl_orderconfirmNum'] = 0;
		if(!$uinfo['tmpl_orderpayNum']) $uinfo['tmpl_orderpayNum'] = 0;
		if(!$uinfo['tmpl_ordershouhuoNum']) $uinfo['tmpl_ordershouhuoNum'] = 0;
		if(!$uinfo['tmpl_ordertuiNum']) $uinfo['tmpl_ordertuiNum'] = 0;
		if(!$uinfo['tmpl_withdrawNum']) $uinfo['tmpl_withdrawNum'] = 0;
		if(!$uinfo['tmpl_kehuzixunNum']) $uinfo['tmpl_kehuzixunNum'] = 0;		
		$today_start = strtotime(date('Y-m-d').' 00:00:01');
        $today_end = strtotime(date('Y-m-d').' 23:59:59');
        
        //今日收入  统计payorder
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',bid];
        $where[] = ['paytypeid', 'not in', [1,4]];//今日收款不统计余额支付订单和货到付款订单
        $where[] = ['createtime', 'between', [$today_start, $today_end]];
        //有门店的按模块读取（有些模块没有门店数据）
        if($this->user['mdid']) {
            $where[] = ['status', 'in', '1,2,3,4'];
            $where[] = ['mdid', '=', $this->user['mdid']];
             $today_shopmoney = Db::name('shop_order')->where($where)->sum('totalprice');
			
            $today_collagemoney = Db::name('collage_order')->where($where)->sum('totalprice');
            $today_seckillmoney = Db::name('seckill_order')->where($where)->sum('totalprice');
            $today_maidanmoney = Db::name('maidan_order')->where($where)->sum('paymoney');
            $today_tuangoumoney = Db::name('tuangou_order')->where($where)->sum('totalprice');
            $today_kanjiamoney = Db::name('kanjia_order')->where($where)->sum('totalprice');
            $yuyue_totalmoney = Db::name('yuyue_order')->where($where)->sum('totalprice');
            $restaurant_takeaway_totalmoney = Db::name('restaurant_takeaway_order')->where($where)->sum('totalprice');
            $restaurant_shop_totalmoney = Db::name('restaurant_shop_order')->where($where)->sum('totalprice');
			//        $restaurant_booking_totalmoney = Db::name('restaurant_booking_order')->where($where)->sum('totalprice');
            $cycle_order_totalmoney = Db::name('cycle_order')->where($where)->sum('totalprice');
            $lucky_totalmoney = Db::name('lucky_collage_order')->where($where)->sum('totalprice');
            $scoreshop_totalmoney = Db::name('scoreshop_order')->where($where)->sum('totalprice');
            $today_maidanmoney_new = 0;
            $today_money = round($today_shopmoney + $today_collagemoney + $today_seckillmoney + $today_maidanmoney + $today_tuangoumoney + $today_kanjiamoney + $yuyue_totalmoney + $restaurant_takeaway_totalmoney + $restaurant_shop_totalmoney + $cycle_order_totalmoney + $lucky_totalmoney + $today_maidanmoney_new + $scoreshop_totalmoney, 2);
            //今日订单数
            $today_shopcount = Db::name('shop_order')->where($where)->count();
            $today_collagecount = Db::name('collage_order')->where($where)->count();
            $today_seckillcount = Db::name('seckill_order')->where($where)->count();
            $today_maidancount = Db::name('maidan_order')->where($where)->count();
            $today_tuangoucount = Db::name('tuangou_order')->where($where)->count(); 
            $today_kanjiacount = Db::name('kanjia_order')->where($where)->count();
            $today_yuyuecount = Db::name('yuyue_order')->where($where)->count();
            $today_takeaway_count = Db::name('restaurant_takeaway_order')->where($where)->count();
            $today_restshop_count = Db::name('restaurant_shop_order')->where($where)->count();
            $today_cycle_count = Db::name('cycle_order')->where($where)->count();
            $today_lucky_collage_count = Db::name('lucky_collage_order')->where($where)->count();
            $scoreshop_count = Db::name('scoreshop_order')->where($where)->count();
            $today_maidancount_new = 0;
            $today_order_count = intval($today_shopcount+$today_collagecount+$today_seckillcount+$today_maidancount+$today_tuangoucount+$today_kanjiacount+$today_yuyuecount+$today_takeaway_count+$today_restshop_count+$today_cycle_count+$today_lucky_collage_count+$today_maidancount_new+$scoreshop_count);
        }else{
            $where[] = ['status', '=', 1];
            $where[] = ['type', '<>', 'daifu'];
            //不是门店管理员，从payorder读取全部
            //$today_money = round(Db::name('payorder')->where($where)->field('sum(money - refund_money) as money')->find()['money'],2);
            $today_money = round(Db::name('payorder')->where($where)->field('sum(money) as money')->find()['money'],2);
            $today_order_count = intval(Db::name('payorder')->where($where)->count());
        }


        //外卖订单
        $where_r = [];
        $where_r['aid'] = aid;
        $where_r['bid'] = bid;
        if($this->user['mdid']){
            $where_r['mdid'] = $this->user['mdid'];
        }
        $restaurant_takeaway_count = Db::name('restaurant_takeaway_order') ->where($where_r)->count();
        $restaurant_shop_count = Db::name('restaurant_shop_order') ->where('aid',aid)->where('bid',bid)->count();
        $restaurant_booking_count = Db::name('restaurant_booking_order') ->where('aid',aid)->where('bid',bid)->count(); //预定订单
        $restaurant_queue = Db::name('restaurant_queue')->where('aid',aid)->where('bid',bid)->where('status',0)->count(); //排队
        $restaurant_deposit =  Db::name('restaurant_queue')->where('aid',aid)->where('bid',bid)->where('status',1)->count(); //寄存
        $restaurant_product_count = Db::name('restaurant_product')->where('aid',aid)->where('bid',bid)->where('status',1)->count(); //菜品数量
        $restaurant_table_count = Db::name('restaurant_table')->where('aid',aid)->where('bid',bid)->count();  //餐桌数量

        //手机端设置
        $designer_mobile = Db::name('designer_mobile')->where('aid',aid)->find();
        $designer_mobile_data = json_decode($designer_mobile['data'],true);
        $set['bgimg'] = $designer_mobile_data['bgimg'] ? $designer_mobile_data['bgimg'] : '';


		$custom['setnewscore'] = 0;
        //$rdata不要再加显示和权限字段了，控制显示隐藏的默认通过权限auth_data，权限不够用的通过show_auth数组 优先级：手机端权限>后台权限>独立设置
        $rdata = [];
		$rdata['status'] = 1;
		$rdata['set'] = $set;
		$rdata['wxtmplset'] = $wxtmplset;
		$rdata['uinfo'] = $uinfo;
		$rdata['count0'] = $count0;
		$rdata['count1'] = $count1;
		$rdata['count2'] = $count2;
        $rdata['count3'] = $count3;
		$rdata['count4'] = $count4;
		$rdata['today_order_count'] = $today_order_count;
		$rdata['today_money'] = $today_money;
		
		$rdata['restaurant_takeaway_count'] = $restaurant_takeaway_count;
		$rdata['restaurant_shop_count'] = $restaurant_shop_count;
		$rdata['restaurant_booking_count'] = $restaurant_booking_count;
		$rdata['restaurant_queue'] = $restaurant_queue;
		$rdata['restaurant_deposit'] = $restaurant_deposit;
		$rdata['restaurant_product_count'] = $restaurant_product_count;
		$rdata['restaurant_table_count'] = $restaurant_table_count;
		
		$rdata['custom'] = $custom;
		$rdata['collageCount'] = $collageCount;
		$rdata['luckycollageCount'] = $luckycollageCount;
		$rdata['kanjiaCount'] = $kanjiaCount;
		$rdata['seckillCount'] = $seckillCount;
		$rdata['tuangouCount'] = $tuangouCount;
		$rdata['scoreshopCount'] = $scoreshopCount;
		$rdata['maidanCount'] = $maidanCount;
		$rdata['productCount'] = $productCount;
		$rdata['yuyueorderCount'] = $yuyueorderCount;
		$rdata['hexiaoCount'] = $hexiaoCount;
		$rdata['cycleCount'] = $cycleCount;
		
		$rdata['formlogCount'] = $formlogCount;
		$rdata['todayMoney'] = $todayMoney;
		$rdata['workordercount'] = $workordercount;
		$rdata['auth_data'] = $this->auth_data;
        $rdata['auth_data']['hexiao_auth_data'] = json_decode($this->user['hexiao_auth_data'],true);
        $rdata['auth_data']['wxauth_data'] = json_decode($this->user['wxauth_data'],true);
		$rdata['showbusinessqr'] = (getcustom('plug_businessqr') && bid > 0 ? true : false);
		$rdata['showzhaopin'] = (getcustom('zhaopin') ? true : false);
		$rdata['showshoporder'] = $showshoporder;
		$rdata['showweightorder'] = $showweightorder;
		$rdata['showcollageorder'] = $showcollageorder;
		$rdata['showCycleorder'] = $showcycleorder;
		
		$rdata['showkanjiaorder'] = $showkanjiaorder;
		$rdata['showseckillorder'] = $showseckillorder;
		$rdata['showscoreshoporder'] = $showscoreshoporder;
		$rdata['showluckycollageorder'] = $showluckycollageorder;
		$rdata['showtuangouorder'] = $showtuangouorder;
		$rdata['showmaidanlog'] = $showmaidanlog;
		$rdata['showformlog'] = $showformlog;
		$rdata['showworkadd'] = $showworkadd;
		$rdata['showworkorder'] = $showworkorder;
		$rdata['showshortvideo'] = $showshortvideo;
		$rdata['showyuyueorder'] = $showyuyueorder;
		$rdata['showyuekeorder'] = $showyuekeorder;
		//商家充值
		$rdata['showrecharge'] = $showrecharge;
		$rdata['showrestaurantproduct'] = $showrestaurantproduct;//2.5.1废弃
		$rdata['showrestauranttable'] = $showrestauranttable;//2.5.1废弃

		
		$searchmember = false;
		$rdata['searchmember'] = $searchmember;
        //$rdata不要再加显示和权限字段了，控制显示隐藏的默认通过权限auth_data，权限不够用的通过show_auth数组 优先级：手机端权限>后台权限>独立设置

		$scoreshop_product = false;
		$scoreproductCount = 0;
		$rdata['scoreshop_product'] = $scoreshop_product;
		$rdata['scoreproductCount'] = $scoreproductCount;
		$rdata['show_categroy_business'] = (getcustom('shop_categroy_business_mobile') && bid > 0 ? true : false);
        //$rdata不要再加显示和权限字段了，控制显示隐藏的默认通过权限auth_data，权限不够用的通过show_auth数组 优先级：手机端权限>后台权限>独立设置
        $rdata['show_auth'] = $show_auth;
        $add_product = 1;//允许添加商品
        $rdata['add_product'] = $add_product;
		$hotel = [];
		$text=['酒店'=>'酒店'];
		$hotel['text'] = $text;
		$rdata['hotel'] = $hotel;
	 	$rdata['showmdmoney'] = 0;
		//$rdata不要再加显示和权限字段了，控制显示隐藏的默认通过权限auth_data，权限不够用的通过show_auth数组 优先级：手机端权限>后台权限>独立设置
		return $this->json($rdata);
	}
	//管理员登录
	public function login(){
		if(request()->isPost()){
			$username = trim(input('post.username'));
			$password = trim(input('post.password'));
			$captcha = trim(input('post.captcha'));
            $login_type = trim(input('post.login_type',0)); //登录方式 1:账号密码 2:短信验证码

            if($login_type != 2 && ($username=='' || $password=='')){
                return $this->json(['status'=>0,'msg'=>'用户名和密码不能为空']);
            }elseif($login_type == 2 && $username == ''){
                return json(['status'=>0,'msg'=>'手机号不能为空']);
            }elseif($captcha == ''){
                return $this->json(['status'=>0,'msg'=>'验证码不能为空']);
            }elseif(strtolower($captcha) != strtolower(cache($this->sessionid.'_captcha'))){
				 return $this->json(['status'=>0,'msg'=>'验证码错误']);
			}

            if($login_type == 0 || $login_type == 1){
                $rs = db('admin_user')->where(['un'=>$username,'pwd'=>md5($password),'aid' => aid])->find();
            }
			if($rs){
				$aid = $rs['aid'];
				if($rs['status']!=1) return  $this->json(['status'=>0,'msg'=>'账号未启用']);
				cookie("AM_AID",$aid,86400*300);
				cookie("AM_UID_{$aid}",$rs['id'],86400*300);
				cookie("AM_TOKEN_{$aid}",md5($username.'wxx1$%^'.$password.'_@#$x'),86400*300);
				session("AM_UID_{$aid}",$rs['id']);
				
				$update = [];
				$update['ip'] = request()->ip();
				$update['logintime'] = time();
				if(!$rs['mid']){ //账号没有绑定会员ID
					$rs2 = db('admin_user')->where('mid',mid)->find();
					if(!$rs2){ //该会员没有绑定其他账号
						$update['mid'] = mid;
						if($rs['bid'] > 0 && in_array($rs['isadmin'],[1,2])){
							$business = db('business')->where('aid',aid)->where('id',$rs['bid'])->find();
							if(!$business['mid']){
								Db::name('business')->where('aid',aid)->where('id',$rs['bid'])->update(['mid'=>mid]);
							}							
						}
					}
				}
				db('admin_user')->where(['id'=>$rs['id']])->update($update);
				db('admin_loginlog')->insert(['aid'=>$aid,'uid'=>$rs['id'],'logintime'=>time(),'loginip'=>request()->ip(),'logintype'=>'手机端登录']);
				cache($this->sessionid.'_uid',$rs['id']);

                define('uid',$rs['id']);
                define('bid',$rs['bid']);
                $tourl = '';
                \app\common\System::plog('手机端登录');            
				return $this->json(['status'=>1,'msg'=>'登录成功','tourl' => $tourl]);
			}else{
				cache($this->sessionid.'_captcha',null);
				return $this->json(['status'=>2,'msg'=>'账号或密码错误']);
			}
		}else{
			cache($this->sessionid.'_uid',null);
            $smslogin = 0;
            $forgetpwd = false;
            return $this->json(['status'=>1,'smslogin' => $smslogin,'forgetpwd' => $forgetpwd]);
		}
	}
	//消息通知开关
	public function setusertmpl(){
		$post = input('post.');
		if(in_array($post['field'],['tmpl_orderconfirm','tmpl_orderpay','tmpl_ordershouhuo','tmpl_withdraw','tmpl_formsub','tmpl_kehuzixun','tmpl_recharge','tmpl_ordertui','tmpl_maidanpay','tmpl_hotelbooking_success'])){
			Db::name('admin_user')->where('id',uid)->update([$post['field']=>$post['value']]);
		}
        \app\common\System::plog('手机端消息通知开关');
		return $this->json(['status'=>1,'msg'=>'设置成功']);
	}
	//修改密码
	public function setpwd(){
		if(request()->isPost()){
			$user = Db::name('admin_user')->where('id',uid)->find();
			$oldpwd = input('post.oldpwd');
			$pwd = input('post.pwd');
			if(md5($oldpwd)!=$user['pwd']){
				return $this->json(['status'=>0,'msg'=>'原密码输入错误']);
			}
			Db::name('admin_user')->where('id',$user['id'])->update(['pwd'=>md5($pwd)]);
            \app\common\System::plog('手机端修改密码');
			return $this->json(['status'=>1,'msg'=>'修改成功']);
		}
		$user = Db::name('admin_user')->field('id,un')->where('id',uid)->find();
		$rdata = [];
		$rdata['status'] = 1;
		$rdata['user'] = $user;
		return $this->json($rdata);
	}
	//发送验证码
	public function sendsms(){
		}
	//重置密码
	public function editpwd(){
		}
	//找回密码
	public function getpwd(){
		}
	//商家推广码
	public function getbusinessqr(){
		$posterset = Db::name('plug_businessqr_poster')->where('aid',aid)->where('bid',bid)->order('id')->find();
		if(!$posterset){
			return json(['status'=>-4,'msg'=>'请先在电脑端后台设置推广码']);
		}
		$rdata = [];
		$rdata['posterurl'] = $posterset['posterurl'];
		return $this->json($rdata);
	}
    //设置店铺信息
    public function setField(){
        $field = input('post.field');
        $value = input('post.value');
        db('business')->where(['aid'=>aid,'id'=>bid])->update([$field=>$value]);
        \app\common\System::plog('手机端系统设置');
        return json(['status'=>1,'msg'=>'设置成功','url'=>true]);
    }
	//设置店铺信息
	public function setinfo(){
        $field = 'xieyi_show,xieyi,default_rate';

        $bset = Db::name('business_sysset')->where('aid',aid)->field($field)->find();
        $new_score = 0;
        $showMemberNewscoreRatio = 0;
        $showBusinessNewscoreRatio = 0;
        $oldinfo = Db::name('business')->where('id',bid)->where('aid',aid)->find();
		if(request()->isPost()){
			$postinfo = input('post.info/a');
			$info = [];
			$info['tel'] = $postinfo['tel'];
			$info['logo'] = $postinfo['logo'];
			$info['desc'] = $postinfo['desc'];
			$info['pics'] = $postinfo['pics'];
			if(isset($postinfo['content'])) $info['content'] = $postinfo['content'];
            if(isset($postinfo['end_buy_status'])) $info['end_buy_status'] = $postinfo['end_buy_status'];
			$info['address'] = $postinfo['address'];
			$info['longitude'] = $postinfo['longitude'];
			$info['latitude'] = $postinfo['latitude'];
			$info['weixin'] = $postinfo['weixin'];
			$info['aliaccount'] = $postinfo['aliaccount'];
			$info['bankname'] = $postinfo['bankname'];
			$info['bankcarduser'] = $postinfo['bankcarduser'];
			$info['bankcardnum'] = $postinfo['bankcardnum'];
			$info['start_hours'] = $postinfo['start_hours'];
			$info['end_hours'] = $postinfo['end_hours'];
//			$info['invoice'] = $postinfo['invoice'];
//			$info['invoice_type'] = implode(',',$postinfo['invoice_type']);
			$info['is_open'] = $postinfo['is_open'];
			$info['autocollecthour'] = $postinfo['autocollecthour'];
			$info['start_hours2'] = $postinfo['start_hours2'];
			$info['end_hours2'] = $postinfo['end_hours2'];
			$info['start_hours3'] = $postinfo['start_hours3'];
			$info['end_hours3'] = $postinfo['end_hours3'];
			db('business')->where(['aid'=>aid,'id'=>bid])->update($info);
			\app\common\System::plog('手机端系统设置');
			return json(['status'=>1,'msg'=>'设置成功','url'=>true]);
		}
		
		$new_score_log_str = '';
        $rdata = [];
		$rdata['info'] = $oldinfo;
        //新积分参数
        $rdata['new_score'] = $new_score;
        $rdata['showMemberNewscoreRatio'] = $showMemberNewscoreRatio;
        $rdata['showBusinessNewscoreRatio'] = $showBusinessNewscoreRatio;
        $rdata['new_score_log_str'] = $new_score_log_str;
        //是否展示消费补贴比例
        $rdata['showSubsidyRatio'] = $bset['maidan_rate_set']??0;
		return $this->json($rdata);
	}
	
	//获取商家余额
	public function getBusiness(){
		$where = ['aid'=>aid,'uid'=>uid];
		if(bid == 0){
			$set = Db::name('admin_set')->field('name,logo,desc,tel')->where('aid',aid)->find();
		}else{
			$set = Db::name('business')->field('name,logo,desc,tel,money')->where('id',bid)->find();
		}
		return json(['status'=>1,'data'=>$set]);
	}
	//充值
	public function recharge(){
		$bid = bid;
		$money = floatval(input('post.money'));
		if($money == 0){
			return json(['status'=>0,'msg'=>'请输入充值金额']);
		}
		if(request()->isPost()){
			if($money>0){
				$ordernum = date('ymdHis').aid.rand(1000,9999);
				//增加消费记录
				$orderdata = [];
				$orderdata['aid'] = aid;
				$orderdata['bid'] = bid;
				$orderdata['createtime']= time();
				$orderdata['money'] = $money;
				$orderdata['ordernum'] = $ordernum;
				$orderid = Db::name('business_recharge_order')->insertGetId($orderdata);
				$payorderid = \app\model\Payorder::createorder(aid,$orderdata['bid'],'0','business_recharge',$orderid,$ordernum,t('余额').'充值',$money);

                \app\common\System::plog('手机端充值：'.$money);
				return $this->json(['status'=>1,'msg'=>'提交成功','orderid'=>$orderid,'payorderid'=>$payorderid]);
			}else{
				return $this->json(['status'=>0,'msg'=>'充值金额必须大于0']);
			}
		}
	}

    public function bindqrcodevar()
    {
        }

    public function bindqrcodevarlist()
    {
        }

    public function qrcodevarbindsound()
    {
        }

    public function unbindqrcodevar()
    {
        }

    //商家二维码
    public function qrcodeShop(){
        $platform = Db::name('admin')->where('id',aid)->value('platform');
        $platform = explode(',',$platform);

        $rdata = ['status'=>1];
        if(bid > 0){
            $path = '/pagesExt/business/index'.'?id='.bid;
            }else{
            $path = '';
        }

		if(in_array('h5',$platform) || in_array('mp',$platform)) {
            $rdata['url_h5'] = m_url($path);
            $rdata['qrcode_h5'] = createqrcode($rdata['url_h5'],'');
        }
        if(in_array('wx',$platform)) {
            $rs = \app\common\Wechat::getQRCode(aid,'wx',$path,[],bid);
            $rdata['qrcode_wx'] = $rs['url'];
        }
        if(in_array('alipay',$platform)) {
            $rs = \app\common\Alipay::getQRCode(aid,$path);
            $rdata['qrcode_alipay'] = $rs['url'];
        }

        return $this->json($rdata);
    }

    //设置商户视频号ID
    public function setBusinessChannels(){
        }

	//商户进件
	public function qlszJinjian(){
		}

    //门店二维码
    public function mendianQrCode() {
        }
     //获取营业数据
    public function getYingyeData(){
	    }

    public function setnewscore(){
        }
}
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

// +----------------------------------------------------------------------
// | 首页
// +----------------------------------------------------------------------
namespace app\controller;
use app\common\File;
use app\common\Pic;
use PhpOffice\PhpSpreadsheet\Worksheet\AutoFilter\Column;
use think\facade\View;
use think\facade\Db;

class Backstage extends Common
{

	//首页框架
    public function index(){
		$menudata = \app\common\Menu::getdata(aid,uid,true);

        if(bid>0){
            //判断商户是否有编辑新积分活动的权限
            if(getcustom('yx_new_score_active')) {
                $bcids = Db::name('business')->where('id',bid)->value('cid');
                $edit_newscore = 0;
                if($bcids){
                    $edit_newscore = Db::name('business_category')->where('id','in',$bcids)->where('edit_newscore',1)->find();
                }
                if($edit_newscore){
                    $newscore_child = [
                        'name'=>t('新积分').'设置',
                        'path'=>'NewScore/edit_active_business',
                        'authdata'=>'NewScore/edit_active_business'
                    ];
                    $menudata['newscore'] = $newscore_child;
                }
            }
        }
		$set = Db::name('admin_set')->where('aid',aid)->find();
		if(!$set){
			\app\common\System::initaccount(aid);
			$set = Db::name('admin_set')->where('aid',aid)->find();
		}
		$webname = \app\common\System::webname();

		$adminname = $this->user['un'];
		if(getcustom('backstage_welcome1')){
			if(bid == 0){
				$webname = $set['name'];
			}else{
				$webname = Db::name('business')->where('id',bid)->value('name');
			}
		}else{
			if(bid > 0){
				$bname = Db::name('business')->where('id',bid)->value('name');
				$adminname = $bname . '('.$adminname.')';
			}else{
				$adminname = $set['name'] . '('.$adminname.')';
                if(getcustom('user_area_agent') && $this->user['isadmin']==3 && $this->user['agent_level']>0){
                    $areaname = $this->user['agent_province'];
                    if($this->user['agent_level']>1){
                        $areaname .= $this->user['agent_city'];
                    }
                    if($this->user['agent_level']>2){
                        $areaname .= $this->user['agent_area'];
                    }
                    $adminname = '区域代理('.$this->user['un'].')<font style="color:#009688">['.$areaname.']</font>';
                }
			}
		}
		$noticecount = 0 + Db::name('admin_notice')->where('aid',aid)->where('uid',uid)->where('isread',0)->count();
		View::assign('isadmin',$this->user['isadmin']);
		View::assign('adminname',$adminname);
		View::assign('menudata',$menudata);
		View::assign('noticecount',$noticecount);
		View::assign('webname',$webname);
        View::assign('set',$set);
		$webinfo = Db::name('sysset')->where('name','webinfo')->value('value');
		$webinfo = json_decode($webinfo,true);
		if(getcustom('admin_login_page')){
			if($webinfo['open_login_page'] == 1){					
				$ainfo = Db::name('admin')->where('id',aid)->find();
				$admin_user = Db::name('admin_user')->where('isadmin',2)->find();
				$admin_set = Db::name('admin_set')->where('aid',$ainfo['id'])->find();
				if($admin_set['webinfo'] && $admin_user['aid'] != aid ){
					$admin_webinfo = json_decode($admin_set['webinfo'],true);
					if(!empty($admin_webinfo['webname'])){
						$webinfo = array_merge($webinfo,$admin_webinfo);
						View::assign('webname',$webinfo['webname']);
					}					
				}
			}
		}
        elseif(getcustom('pctitle_follow_shopname')){
            if($set['name']) View::assign('webname',$set['name']);
        }
		$auth_data = $this->auth_data;

		if(getcustom('member_huoyuedu_notice')  && ($auth_data=='all' || in_array('MemberHuoyuedu/index',$auth_data))){
			$huoyuedu_set = Db::name('member_huoyuedu_set')->where('aid',aid)->find();
			View::assign('huoyuedu_set',$huoyuedu_set);
		}

		$socket_uid = '';
		$socket_mid = '';
		$socket_token = '';
		if($this->user['mid']){
			$socket_uid = $this->user['id'];
			$socket_mid = $this->user['mid'];
			$socket_token = Db::name('member')->where('id',$this->user['mid'])->value('random_str');
		}
		$showlinks = true;
        if(getcustom('kecheng_lecturer')){
            //课程讲师前端链接需要验证设计权限
            if($this->user['lecturerid']){
                if($this->auth_data != 'all'){
                    $auth_data = json_decode($this->user['auth_data'],true);
                    $auth_path = [];
                    foreach($auth_data as $v){
                        $auth_path[] = explode(',',$v);
                    }
                    if(!in_array('DesignerPage/*',$auth_path) && !in_array('DesignerPage/chooseurl',$auth_path)){
                        $showlinks = false;
                        $socket_token = '';
                    }
                } 
            }
        }
        View::assign('showlinks',$showlinks);
		View::assign('webinfo',$webinfo);
		View::assign('socket_mid',$socket_mid);
		View::assign('socket_token',$socket_token);
		View::assign('socket_uid',$socket_uid);
        // 获取快捷菜单
        $shortMenu = Db::name("shortcut_menu")
            ->where('aid', aid)
            ->where('bid', bid)
            ->where('uid', uid)
            ->find();
        $shortMenu['menus'] = json_decode($shortMenu['menus'], true);
        // 根据权限筛选
        if ($this->auth_data != 'all') {
            $shortMenu['menus'] = array_filter($shortMenu['menus'], function($menu) {
                return in_array($menu['value'], $this->auth_data);
            });
        }
        View::assign('shortcutMenus', $shortMenu['menus']);
		return View::fetch();
    }
	//欢迎页面 数据统计
	public function welcomeOld(){
		if(session('IS_ADMIN')==0 && $this->user['showtj']==0){
			return View::fetch('welcome2');
		}else{
			if(getcustom('backstage_welcome1')){
				return $this->welcome1();
			}

			$monthEnd = strtotime(date('Y-m-d',time()-86400));
			$monthStart = $monthEnd - 86400 * 29;
			//订单限制门店
			if($this->mdid){
				$where1 = [];
				$where1[] = ['mdid','=',$this->mdid];
			}else{
				$where1 = '1=1';
			}
			if(input('post.op') == 'getdata'){
				$dataArr = array();
				$dateArr = array();
				for($i=0;$i<30;$i++){
					$thisDayStart = $monthStart + $i * 86400;
					$thisDayEnd = $monthStart + ($i+1) * 86400;
					$dateArr[] = date('m-d',$thisDayStart);
					if($_POST['type']==1){//客户数
						$dataArr[] = 0 + Db::name('member')->where('aid',aid)->where('createtime','<',$thisDayEnd)->count();
					}elseif($_POST['type']==2){//新增客户数
						$dataArr[] = 0 + Db::name('member')->where('aid',aid)->where('createtime','>=',$thisDayStart)->where('createtime','<',$thisDayEnd)->count();
					}elseif($_POST['type']==3){//收款金额
						$dataArr[] = 0 + Db::name('payorder')->where('aid',aid)->where('createtime','>=',$thisDayStart)->where('createtime','<',$thisDayEnd)->where('paytypeid','not in','1,4')->where('type','<>','daifu')->where('status',1)->sum('money');
					}elseif($_POST['type']==4){//订单金额
						$dataArr[] = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('paytime','>=',$thisDayStart)->where('paytime','<',$thisDayEnd)->where('status','in','1,2,3')->where($where1)->sum('totalprice');
					}elseif($_POST['type']==5){//订单数
						$dataArr[] = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('paytime','>=',$thisDayStart)->where('paytime','<',$thisDayEnd)->where('status','in','1,2,3')->where($where1)->count();
					}
				}
				return json(['dateArr'=>$dateArr,'dataArr'=>$dataArr]);
			}
			$dataArr = array();
			$dateArr = array();
			for($i=0;$i<30;$i++){
				$thisDayStart = $monthStart + $i * 86400;
				$thisDayEnd = $monthStart + ($i+1) * 86400;
				$dateArr[] = date('m-d',$thisDayStart);
				if(bid == 0){
					$dataArr[] = 0 + Db::name('member')->where('aid',aid)->where('createtime','<',$thisDayEnd)->count();
				}else{
					$dataArr[] = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('paytime','>=',$thisDayStart)->where('paytime','<',$thisDayEnd)->where('status','in','1,2,3')->where($where1)->sum('totalprice');
				}
			}

			$lastDayStart = strtotime(date('Y-m-d',time()-86400));
			$lastDayEnd = $lastDayStart + 86400;
			$thisMonthStart = strtotime(date('Y-m-1'));
			$nowtime = time();
			if(bid == 0){
				//客户数
				$memberCount = 0 + Db::name('member')->where('aid',aid)->count();
				$memberThisDayCount = 0 + Db::name('member')->where('aid',aid)->where('createtime','>=',$lastDayEnd)->count();
				$memberLastDayCount = 0 + Db::name('member')->where('aid',aid)->where('createtime','>=',$lastDayStart)->where('createtime','<',$lastDayEnd)->count();
				$memberThisMonthCount = 0 + Db::name('member')->where('aid',aid)->where('createtime','>=',$thisMonthStart)->where('createtime','<',$nowtime)->count();

				//收款金额
				$payCount = Db::name('payorder')->where('aid',aid)->where('paytypeid','not in','1,4')->where('type','<>','daifu')->where('status',1)->sum('money');
				$payThisDayCount = 0 + Db::name('payorder')->where('aid',aid)->where('paytypeid','not in','1,4')->where('type','<>','daifu')->where('status',1)->where('paytime','>=',$lastDayEnd)->sum('money');
				$payLastDayCount = 0 + Db::name('payorder')->where('aid',aid)->where('paytypeid','not in','1,4')->where('type','<>','daifu')->where('status',1)->where('paytime','>=',$lastDayStart)->where('paytime','<',$lastDayEnd)->sum('money');
				$payThisMonthCount = 0 + Db::name('payorder')->where('aid',aid)->where('paytypeid','not in','1,4')->where('type','<>','daifu')->where('status',1)->where('paytime','>=',$thisMonthStart)->where('paytime','<',$nowtime)->sum('money');

				//提现金额
				$withdrawCount = 0 + Db::name('member_withdrawlog')->where('aid',aid)->where('status',3)->sum('money') + Db::name('member_commission_withdrawlog')->where('aid',aid)->where('status',3)->sum('money');
				$withdrawThisDayCount = 0 + Db::name('member_withdrawlog')->where('aid',aid)->where('status',3)->where('createtime','>=',$lastDayEnd)->sum('money') + Db::name('member_commission_withdrawlog')->where('aid',aid)->where('status',3)->where('createtime','>=',$lastDayEnd)->sum('money');
				$withdrawLastDayCount = 0 + Db::name('member_withdrawlog')->where('aid',aid)->where('status',3)->where('createtime','>=',$lastDayStart)->where('createtime','<',$lastDayEnd)->sum('money') + Db::name('member_commission_withdrawlog')->where('aid',aid)->where('status',3)->where('createtime','>=',$lastDayStart)->where('createtime','<',$lastDayEnd)->sum('money');
				$withdrawThisMonthCount = 0 + Db::name('member_withdrawlog')->where('aid',aid)->where('status',3)->where('createtime','>=',$thisMonthStart)->where('createtime','<',$nowtime)->sum('money') + Db::name('member_commission_withdrawlog')->where('aid',aid)->where('status',3)->where('createtime','>=',$thisMonthStart)->where('createtime','<',$nowtime)->sum('money');
			}
			//商品数量
			$productCount = 0 + Db::name('shop_product')->where('aid',aid)->where('bid',bid)->count();

            $nowtime = time();
            $nowhm = date('H:i');
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = Db::raw("`status`=0 or (`status`=2 and (unix_timestamp(start_time)>$nowtime or unix_timestamp(end_time)<$nowtime)) or (`status`=3 and ((start_hours<end_hours and (start_hours>'$nowhm' or end_hours<'$nowhm')) or (start_hours>=end_hours and (start_hours>'$nowhm' and end_hours<'$nowhm'))) )");
            $product0Count = 0 + Db::name('shop_product')->where($where)->count();
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = Db::raw("`status`=1 or (`status`=2 and unix_timestamp(start_time)<=$nowtime and unix_timestamp(end_time)>=$nowtime) or (`status`=3 and ((start_hours<end_hours and start_hours<='$nowhm' and end_hours>='$nowhm') or (start_hours>=end_hours and (start_hours<='$nowhm' or end_hours>='$nowhm'))) )");
            $product1Count = 0 + Db::name('shop_product')->where($where)->count();

			//订单数
			$orderallCount = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('status','in','1,2,3')->where($where1)->count();
			$orderallThisDayCount = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('status','in','1,2,3')->where('paytime','>=',$lastDayEnd)->where($where1)->count();
			$orderallLastDayCount = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('status','in','1,2,3')->where('paytime','>=',$lastDayStart)->where('paytime','<',$lastDayEnd)->where($where1)->count();
			$orderallThisMonthCount = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('status','in','1,2,3')->where('paytime','>=',$thisMonthStart)->where('paytime','<',$nowtime)->where($where1)->count();

			//订单金额
			$orderMoneyCount = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('status','in','1,2,3')->where($where1)->sum('totalprice');
			$orderMoneyThisDayCount = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('paytime','>=',$lastDayEnd)->where('status','in','1,2,3')->where($where1)->sum('totalprice');
			$orderMoneyLastDayCount = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('paytime','>=',$lastDayStart)->where('paytime','<',$lastDayEnd)->where('status','in','1,2,3')->where($where1)->sum('totalprice');
			$orderMoneyThisMonthCount = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('paytime','>=',$thisMonthStart)->where('paytime','<',$nowtime)->where('status','in','1,2,3')->where($where1)->sum('totalprice');

			//退款金额
			$refundCount = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('refund_status',2)->where($where1)->sum('refund_money');
			$refundThisDayCount = 0 + Db::name('shop_refund_order')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$lastDayEnd)->where('refund_status',2)->where($where1)->sum('refund_money');
			$refundLastDayCount = 0 + Db::name('shop_refund_order')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$lastDayStart)->where('createtime','<',$lastDayEnd)->where('refund_status',2)->where($where1)->sum('refund_money');
			$refundThisMonthCount = 0 + Db::name('shop_refund_order')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$thisMonthStart)->where('createtime','<',$nowtime)->where('refund_status',2)->where($where1)->sum('refund_money');

			if(getcustom('business_canuseplatcoupon') && bid > 0){
				$platcouponCount = 0 + Db::name('coupon_businessuserecord')->where('aid',aid)->where('bid',bid)->where('status',1)->sum('decmoney');
				$platcouponThisDayCount = 0 + Db::name('coupon_businessuserecord')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$lastDayEnd)->where('status',1)->sum('decmoney');
				$platcouponLastDayCount = 0 + Db::name('coupon_businessuserecord')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$lastDayStart)->where('createtime','<',$lastDayEnd)->where('status',1)->sum('decmoney');
				$platcouponThisMonthCount = 0 + Db::name('coupon_businessuserecord')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$thisMonthStart)->where('createtime','<',$nowtime)->where('status',1)->sum('decmoney');

				View::assign('platcouponCount',$platcouponCount);
				View::assign('platcouponThisDayCount',$platcouponThisDayCount);
				View::assign('platcouponLastDayCount',$platcouponLastDayCount);
				View::assign('platcouponThisMonthCount',$platcouponThisMonthCount);
			}


			if(getcustom('business_sales_quota') && bid > 0){
				$totalSalesquota = 0 + Db::name('business_salesquota_log')->where('aid',aid)->where('bid',bid)->where('status',1)->sum('money');
				$ThisDaySalesquota = 0 + Db::name('business_salesquota_log')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$lastDayEnd)->where('status',1)->sum('money');
				$LastDaySalesquota = 0 + Db::name('business_salesquota_log')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$lastDayStart)->where('createtime','<',$lastDayEnd)->where('status',1)->sum('money');
				$ThisMonthSalesquota = 0 + Db::name('business_salesquota_log')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$thisMonthStart)->where('createtime','<',$nowtime)->where('status',1)->sum('money');

				View::assign('totalSalesquota',$totalSalesquota);
				View::assign('ThisDaySalesquota',$ThisDaySalesquota);
				View::assign('LastDaySalesquota',$LastDaySalesquota);
				View::assign('ThisMonthSalesquota',$ThisMonthSalesquota);
			}

			$mpinfo = [];
			$wxapp = [];
			if(in_array('wx',$this->platform)){//小程序
				$wxapp = \app\common\System::appinfo(aid,'wx');
			}
			if(in_array('mp',$this->platform)){//公众号
				$mpinfo = \app\common\System::appinfo(aid,'mp');
			}
			View::assign('mpinfo',$mpinfo);
			View::assign('wxapp',$wxapp);

			$set = Db::name('admin_set')->where('aid',aid)->find();
			View::assign('set',$set);

            if(bid == 0){
                $admin = Db::name('admin')->where('id',aid)->find();
                $endtime = $admin['endtime'];
                if(getcustom('admin_money')){
                    $adminMoney['money'] = $admin['money'];
                    $adminMoney['yesterday'] = Db::name('admin_moneylog')->where('aid',aid)->where('createtime','>=',$lastDayStart)->where('createtime','<',$lastDayEnd)->sum('money');
                    $adminMoney['month'] = Db::name('admin_moneylog')->where('aid',aid)->where('createtime','>=',$thisMonthStart)->where('createtime','<',$nowtime)->sum('money');
                    View::assign('adminMoney',$adminMoney);
                    //余额提醒
                    $money_notice_value = 0;
                    if($adminMoney['money'] < $admin['money_notice_value'] && $admin['money_notice_value'] > 0 && $adminMoney['money'] > 0){
                        $money_notice_value = $admin['money_notice_value']?$admin['money_notice_value']:0;
                    }
                    View::assign('money_notice_value',$money_notice_value);
                }
            }
            if(bid > 0){
                $businessEndtime = Db::name('business')->where('aid',aid)->where('id',bid)->value('endtime');
                View::assign('businessStatus',time() > $businessEndtime ? 'expire' : 'normal');
                View::assign('businessEndtime',$businessEndtime);
            }

			View::assign('bid',bid);
			View::assign('endtime',$endtime);
			View::assign('memberCount',$memberCount);
			View::assign('memberThisDayCount',$memberThisDayCount);
			View::assign('memberLastDayCount',$memberLastDayCount);
			View::assign('memberThisMonthCount',$memberThisMonthCount);
//			View::assign('order3Count',$order3Count);
//			View::assign('order3LastDayCount',$order3LastDayCount);
//			View::assign('order3ThisMonthCount',$order3ThisMonthCount);
			View::assign('orderallCount',$orderallCount);
			View::assign('orderallThisDayCount',$orderallThisDayCount);
			View::assign('orderallLastDayCount',$orderallLastDayCount);
			View::assign('orderallThisMonthCount',$orderallThisMonthCount);
			View::assign('orderMoneyCount',$orderMoneyCount);
			View::assign('orderMoneyThisDayCount',$orderMoneyThisDayCount);
			View::assign('orderMoneyLastDayCount',$orderMoneyLastDayCount);
			View::assign('orderMoneyThisMonthCount',$orderMoneyThisMonthCount);
			View::assign('productCount',$productCount);
			View::assign('product0Count',$product0Count);
			View::assign('product1Count',$product1Count);
			View::assign('payCount',$payCount);
			View::assign('payThisDayCount',$payThisDayCount);
			View::assign('payLastDayCount',$payLastDayCount);
			View::assign('payThisMonthCount',$payThisMonthCount);
			View::assign('refundCount',$refundCount);
			View::assign('refundThisDayCount',$refundThisDayCount);
			View::assign('refundLastDayCount',$refundLastDayCount);
			View::assign('refundThisMonthCount',$refundThisMonthCount);
			View::assign('withdrawCount',$withdrawCount);
			View::assign('withdrawThisDayCount',$withdrawThisDayCount);
			View::assign('withdrawLastDayCount',$withdrawLastDayCount);
			View::assign('withdrawThisMonthCount',$withdrawThisMonthCount);
			View::assign('dateArr',$dateArr);
			View::assign('dataArr',$dataArr);

            //默认全部显示
            $pc_index_data = ['all'];
            if(getcustom('plug_siming')){
                $pc_index_data = Db::name("admin")->where('id', aid)->value('pc_index_data');
                $pc_index_data = explode(',', $pc_index_data);
            }
            View::assign('pc_index_data', $pc_index_data);
			return View::fetch();
        }
	}
	public function welcome(){
		if(session('IS_ADMIN')==0 && $this->user['showtj']==0){
			return View::fetch('welcome2');
		}
		if(getcustom('backstage_welcome1')){
			return $this->welcome1();
		}
		$admin = Db::name('admin')->where('id',aid)->find();
		$platform = explode(',',$admin['platform']);

        $monthStart = strtotime(date('Y-m-1',time()));
        $monthEnd = time();
        $lastDayStart = strtotime(date('Y-m-d',time()-86400));
        $lastDayEnd = $lastDayStart + 86400;
		//订单限制门店
		if($this->mdid){
			$where1 = [];
			$where1[] = ['mdid','=',$this->mdid];
		}else{
			$where1 = '1=1';
		}
		//运营数据概览
		if(input('post.op') == 'getOperateData'){
			$day = input('post.day');
			if(!$day){
				$day = 1;
			}
			$dayEnd = strtotime(date('Y-m-d 23:59:59',time()));
			$dayStart = $dayEnd - 86400 * $day;
			if($day == 2){
				$dayEnd = $dayEnd - 86400;
			}
			//收款金额			
			$payMoneyDayCount = 0 + Db::name('payorder')->where('aid',aid)->where('paytypeid','not in','1,4')->where('type','<>','daifu')->where('status',1)->where('paytime','>=',$dayStart)->where('paytime','<=',$dayEnd)->sum('money');
			$payMoneyDayCount = round($payMoneyDayCount,2);
			//订单金额
			$where = [];
			$where[] = ['p.aid','=',aid];
			$where[] = ['p.bid','=',bid];
			$where[] = ['p.type','=','shop'];
	        $where[] = ['p.createtime', 'between', [$dayStart, $dayEnd]];
	        $where[] = ['p.status', '=', 1];
	        if($this->mdid){
	        	$where[] = ['o.mdid','=',$this->mdid];
	        }
            $orderMoneyDayCount = round(Db::name('payorder')->alias('p')->join('shop_order o','o.id=p.orderid')->where($where)->field('sum(p.money - p.refund_money) as money')->find()['money'],2); 
            // 后台录入
            $orderMoneyDayCountAdmin = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('platform','admin')->where('createtime','>=',$dayStart)->where('createtime','<',$dayEnd)->where('status','in','1,2,3,4')->where($where1)->sum('totalprice');
            $refundMoneyDayCountAdmin = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('platform','admin')->where('createtime','>=',$dayStart)->where('createtime','<',$dayEnd)->where('refund_status',2)->where($where1)->sum('refund_money');
            $orderMoneyDayCount = round($orderMoneyDayCount+$orderMoneyDayCountAdmin-$refundMoneyDayCountAdmin,2);

			//订单数
			$orderDayCount = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$dayStart)->where('createtime','<',$dayEnd)->where('status','in','1,2,3,4')->where($where1)->count();

			//退款金额
			//$refundMoneyDayCount = 0 + Db::name('shop_refund_order')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$dayStart)->where('createtime','<',$dayEnd)->where('refund_status',2)->where($where1)->sum('refund_money');
			$refundMoneyDayCount = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$dayStart)->where('createtime','<',$dayEnd)->where('refund_status',2)->where($where1)->sum('refund_money');
			$refundMoneyDayCount = round($refundMoneyDayCount,2);
			//退款数量
			$refundDayCount = 0 + Db::name('shop_refund_order')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$dayStart)->where('createtime','<',$dayEnd)->where('refund_status',2)->where($where1)->count();
			//订单待发货数量
			$orderNoFahuoDayCount = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$dayStart)->where('createtime','<',$dayEnd)->where('status','=','1')->where($where1)->count();
			//订单待售后（退款）
			$orderShouhouDayCount = 0 + Db::name('shop_refund_order')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$dayStart)->where('createtime','<',$dayEnd)->where('refund_status','in','1,4')->where($where1)->count();
			//订单待支付
			$orderNoPayDayCount = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$dayStart)->where('createtime','<',$dayEnd)->where('status','=','0')->where($where1)->count();
			$rdata = [];
			$rdata = [
				'payMoneyDayCount'=>$payMoneyDayCount,
				'orderMoneyDayCount'=>($orderMoneyDayCount < 0) ? 0 : $orderMoneyDayCount,
				'orderDayCount'=>$orderDayCount,
				'refundMoneyDayCount'=>$refundMoneyDayCount,
				'refundDayCount'=>$refundDayCount,
				'orderNoFahuoDayCount'=>$orderNoFahuoDayCount,
				'orderShouhouDayCount'=>$orderShouhouDayCount,
				'orderNoPayDayCount'=>$orderNoPayDayCount,

			];
			return json($rdata);
		}

		//热卖商品
		if(input('post.op') == 'getgoodssalesmoney'){
			$day = input('post.day');
			if(!$day){
				$day = 1;
			}
			$dayEnd = strtotime(date('Y-m-d 23:59:59',time()));
			$dayStart = $dayEnd - 86400 * $day;
			if($day == 365){
				$dayStart = strtotime(date('Y-01-01',time()));
			}
			if(input('param.cid') && input('param.cid')!==''){
				//取出cid 在的商品
				$cid = input('param.cid');
				if(bid == 0){
					$shop_product_cid = "shop_product.cid";
				}else{
					$shop_product_cid = "shop_product.cid2";
				}
				//子分类
				$where_cid = '';
				$clist = Db::name('shop_category')->where('aid',aid)->where('pid',$cid)->column('id');
				if($clist){
					$clist2 = Db::name('shop_category')->where('aid',aid)->where('pid','in',$clist)->column('id');
					$cCate = array_merge($clist, $clist2, [$cid]);
					if($cCate){
						$whereCid = [];
						foreach($cCate as $k => $c2){
							$whereCid[] = "find_in_set({$c2},{$shop_product_cid})";
						}
						$where_cid = Db::raw(implode(' or ',$whereCid));
					}
				} else {
					$where_cid = Db::raw("find_in_set(".$cid.",{$shop_product_cid})");
				}
			}
			$where = [];
			$where[] = ['og.aid','=',aid];
			$where[] = ['og.bid','=',bid];
			$where[] = ['og.status','in','1,2,3'];
			$fields = 'og.proid,og.name,og.pic,sum(og.num) num,sum(og.totalprice) totalprice,og.procode';
			$where[] = ['shop_order.createtime','>=',$dayStart];
			$where[] = ['shop_order.createtime','<',$dayEnd];
			if($where_cid !=''){
				$where[] = $where_cid;
			}
			$shop_goods_order = Db::name('shop_order_goods')->alias('og')->leftjoin('shop_order','shop_order.id=og.orderid')->leftjoin('shop_product','shop_product.id=og.proid')->fieldRaw($fields)->where($where)->group('proid')->limit(10)->order('num desc')->select()->toArray();
			//echo Db::getLastSql();exit;
			return json(['goodsdata'=>$shop_goods_order]);
		}
		//数据趋势图 day=1 本月 2上月 3前月
		if(input('post.op') == 'getDataChart'){
			$day = input('post.day');
			$days = 30;
			if(!$day){
				$day = 1;
			}
			$monthEnd = strtotime(date('Y-m-01 00:00:00',time()));
			$days = date('t',$monthEnd-86400*2);//上个月多少天
			$monthStart = $monthEnd - 86400*$days;
			//本月
			if($day == 1){
				$days = date('t',time());
				$monthEnd = time();
            	$monthStart = strtotime(date('Y-m-01 00:00:00',time()));
			}
			//前月
			if($day == 3){
				$monthEnd = $monthStart;
				$days = date('t',$monthEnd-86400*2);//上个月多少天
				$monthStart = $monthEnd - 86400*$days;
			}
			//优化查询数据
			if(bid == 0){
				$payorder_group = Db::name('payorder')->field("id,FROM_UNIXTIME(createtime,'%Y-%m-%d') as day,sum(money) AS totalmoney")->where('aid',aid)->where('createtime','>=',$monthStart)->where('createtime','<',$monthEnd)->where('paytypeid','not in','1,4')->where('type','<>','daifu')->where('status',1)->group('day')->select()->toArray();
				$payorder_group = array_column($payorder_group,'totalmoney','day');
				$membernum_group = Db::name('member')->field("id,FROM_UNIXTIME(createtime,'%Y-%m-%d') as day,count(id) AS totalnum")->where('aid',aid)->where('createtime','>=',$monthStart)->where('createtime','<',$monthEnd)->group('day')->select()->toArray();
				$membernum_group = array_column($membernum_group,'totalnum','day');

				$memebr_total_start = 0 + Db::name('member')->where('aid',aid)->where('createtime','<',$monthStart)->count();
			}
			$ordermoney_group = Db::name('shop_order')->field("id,FROM_UNIXTIME(createtime,'%Y-%m-%d') as day,sum(totalprice) AS totalmoney")->where('aid',aid)->where('bid',bid)->where('createtime','>=',$monthStart)->where('createtime','<',$monthEnd)->where('status','in','1,2,3')->where($where1)->group('day')->select()->toArray();
			$ordermoney_group = array_column($ordermoney_group,'totalmoney','day');

			$ordernum_group = Db::name('shop_order')->field("id,FROM_UNIXTIME(createtime,'%Y-%m-%d') as day,count(id) AS totalnum")->where('aid',aid)->where('bid',bid)->where('createtime','>=',$monthStart)->where('createtime','<',$monthEnd)->where('status','in','1,2,3')->where($where1)->group('day')->select()->toArray();
			$ordernum_group = array_column($ordernum_group,'totalnum','day');

			$dateArr = array();
			for($i=0;$i<$days;$i++){
				$thisDayStart = $monthStart + $i * 86400;					
				$thisDayEnd = $monthStart + ($i+1) * 86400;
				$dateArr[] = date('m-d',$thisDayStart);
				$thisday = date('Y-m-d',$thisDayStart);
				
				if(bid == 0){
					//$memberChartNum[] = 0 + Db::name('member')->where('aid',aid)->where('createtime','<',$thisDayEnd)->count();

					$day_member_num = $membernum_group[$thisday]??0;
					$memebr_total_start += $day_member_num;
					if($thisday > date('Y-m-d')) $memebr_total_start = 0;
					$memberChartNum[] = $memebr_total_start;
					//$memberChartAddNum[] = 0 + Db::name('member')->where('aid',aid)->where('createtime','>=',$thisDayStart)->where('createtime','<',$thisDayEnd)->count();
					$memberChartAddNum[] = 0 + $day_member_num;

					//$payChartMoney[] = round(0 + Db::name('payorder')->where('aid',aid)->where('createtime','>=',$thisDayStart)->where('createtime','<',$thisDayEnd)->where('paytypeid','not in','1,4')->where('type','<>','daifu')->where('status',1)->sum('money'),2);
					$payChartMoney[] = round(0 + $payorder_group[$thisday]??0,2);
				}
					//$orderChartMoney[] = round(0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('paytime','>=',$thisDayStart)->where('paytime','<',$thisDayEnd)->where('status','in','1,2,3')->where($where1)->sum('totalprice'),2);
					$orderChartMoney[] = round(0 + $ordermoney_group[$thisday]??0);

					// $orderChartNum[] = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('paytime','>=',$thisDayStart)->where('paytime','<',$thisDayEnd)->where('status','in','1,2,3')->where($where1)->count();
					$orderChartNum[] = 0 + $ordernum_group[$thisday]??0;
				
			}
			$title=[];
			$series = [];
			if(bid == 0){
				$title[] = '收款金额';
				$series[] = [
					'name'=>'收款金额',
					'type'=>'line',
					'smooth'=>'true',
					'itemStyle'=>['color'=>'#fac858'],
					'data'=>$payChartMoney,
				];
			}
			$title[] = '订单金额';
			$series[] = [
				'name'=>'订单金额',
				'type'=>'line',
				'smooth'=>'true',
				'itemStyle'=>['color'=>'#ee6666'],
				'data'=>$orderChartMoney,
			];
			$title[] = '订单数量';
			$series[] = [
				'name'=>'订单数量',
				'type'=>'line',
				'smooth'=>'true',
				'itemStyle'=>['color'=>'#66ccff'],
				'data'=>$orderChartNum,
			];
			if(bid == 0){
				$title[] = '会员数量';
				$series[] = [
					'name'=>'会员数量',
					'type'=>'line',
					'smooth'=>'true',
					'itemStyle'=>['color'=>'#5470c6'],
					'data'=>$memberChartNum,
				];
				$title[] = '新增会员数量';
				$series[] = [
					'name'=>'新增会员数量',
					'type'=>'line',
					'smooth'=>'true',
					'itemStyle'=>['color'=>'#99ff66'],
					'data'=>$memberChartAddNum,
				];
			}
	 
			return json(['dateArr'=>$dateArr,'series'=>$series,'title'=>$title]);


		}
		$mpinfo = [];
		$wxapp = [];
		if(in_array('wx',$this->platform)){//小程序
			$wxapp = \app\common\System::appinfo(aid,'wx');
		}
		if(in_array('mp',$this->platform)){//公众号
			$mpinfo = \app\common\System::appinfo(aid,'mp');
		}
		View::assign('mpinfo',$mpinfo);
		View::assign('wxapp',$wxapp);
		$set = Db::name('admin_set')->where('aid',aid)->find();
		View::assign('set',$set);
		if(bid == 0){
			$admin = Db::name('admin')->where('id',aid)->find();
			$endtime = $admin['endtime'];
			if(getcustom('admin_money')){
				$adminMoney['money'] = $admin['money'];
				$adminMoney['yesterday'] = Db::name('admin_moneylog')->where('aid',aid)->where('createtime','>=',$lastDayStart)->where('createtime','<',$lastDayEnd)->sum('money');
				$adminMoney['month'] = Db::name('admin_moneylog')->where('aid',aid)->where('createtime','>=',$monthStart)->where('createtime','<',$monthEnd)->sum('money');
				View::assign('adminMoney',$adminMoney);
				//余额提醒
				$money_notice_value = 0;
				if($adminMoney['money'] < $admin['money_notice_value'] && $admin['money_notice_value'] > 0 && $adminMoney['money'] > 0){
					$money_notice_value = $admin['money_notice_value']?$admin['money_notice_value']:0;
				}
				View::assign('money_notice_value',$money_notice_value);
			}
		}
		if(bid > 0){
			$business = Db::name('business')->where('aid',aid)->where('id',bid)->find();
			View::assign('business',$business);
			$endtime = $business['endtime'];
		}
		View::assign('bid',bid);
		View::assign('endtime',$endtime);

		//本月订单统计
		//总订单数
		$orderMonthCountAll = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$monthStart)->where('createtime','<',$monthEnd)->where($where1)->count();
		//本月待支付
		$orderNoZhifuMonthCount = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$monthStart)->where('createtime','<',$monthEnd)->where('status','=','0')->where($where1)->count();
		//本月待发货
		$orderNoFahuoMonthCount = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$monthStart)->where('createtime','<',$monthEnd)->where('status','=','1')->where($where1)->count();
		//发货中运输中
		$orderFahuoMonthCount = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$monthStart)->where('createtime','<',$monthEnd)->where('status','=','2')->where($where1)->count();
		//已完成
		$orderFinishMonthCount = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$monthStart)->where('createtime','<',$monthEnd)->where('status','=','3')->where($where1)->count();
		//已关闭
		$orderClosehMonthCount = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$monthStart)->where('createtime','<',$monthEnd)->where('status','=','4')->where($where1)->count();
		//已退款
		$orderRefundMonthCount = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$monthStart)->where('createtime','<',$monthEnd)->where('refund_status','=','2')->where($where1)->count();

		$orderNoZhifuMonthCountBili = round($orderNoZhifuMonthCount * 100 / $orderMonthCountAll ,2);
		$orderNoFahuoMonthCountBili = round($orderNoFahuoMonthCount * 100 / $orderMonthCountAll ,2);
		$orderFahuoMonthCountBili = round($orderFahuoMonthCount * 100 / $orderMonthCountAll ,2);
		$orderFinishMonthCountBili = round($orderFinishMonthCount * 100 / $orderMonthCountAll ,2);
		$orderClosehMonthCountBili = round($orderClosehMonthCount * 100 / $orderMonthCountAll ,2);
		$orderRefundMonthCountBili = round($orderRefundMonthCount * 100 / $orderMonthCountAll ,2);

		$channel_order_data[] = [
			'name'=>$orderNoZhifuMonthCountBili.'%',
			'itemStyle'=>['color'=>'#ed7330'],
			'value'=>$orderNoZhifuMonthCount
		];
		$channel_order_data[] = [
			'name'=>$orderNoFahuoMonthCountBili.'%',
			'itemStyle'=>['color'=>'#FFB723'],
			'value'=>$orderNoFahuoMonthCount
		];
		$channel_order_data[] = [
			'name'=>$orderFahuoMonthCountBili.'%',
			'itemStyle'=>['color'=>'#1ECF8F'],
			'value'=>$orderFahuoMonthCount
		];
		$channel_order_data[] = [
			'name'=>$orderFinishMonthCountBili.'%',
			'itemStyle'=>['color'=>'#5FB8FC'],
			'value'=>$orderFinishMonthCount
		];
		$channel_order_data[] = [
			'name'=>$orderClosehMonthCountBili.'%',
			'itemStyle'=>['color'=>'#6161F9'],
			'value'=>$orderClosehMonthCount
		];
		$channel_order_data[] = [
			'name'=>$orderRefundMonthCountBili.'%',
			'itemStyle'=>['color'=>'#333E6A'],
			'value'=>$orderRefundMonthCount
		];
		View::assign('channel_order_data',$channel_order_data);
		View::assign('orderMonthCountAll',$orderMonthCountAll);
		View::assign('orderNoZhifuMonthCount',$orderNoZhifuMonthCount);
		View::assign('orderNoFahuoMonthCount',$orderNoFahuoMonthCount);
		View::assign('orderFahuoMonthCount',$orderFahuoMonthCount);
		View::assign('orderFinishMonthCount',$orderFinishMonthCount);
		View::assign('orderClosehMonthCount',$orderClosehMonthCount);
		View::assign('orderRefundMonthCount',$orderRefundMonthCount);

		//会员概览
		$monthEnd30 = strtotime(date('Y-m-d',time()-86400*30));
		//会员总数
		$memberCount = 0 + Db::name('member')->where('aid',aid)->count();
		//活跃会员
		$memberHuoyueCount = 0 + Db::name('member')->where('aid',aid)->where('last_visittime','>',$monthEnd30)->count();
		//下单会员总数
		$orderMemberCount = 0 + Db::name('shop_order')->where('aid',aid)->where('status','in','1,2,3')->group('mid')->count();
		$orderMemberCountBili = floor($orderMemberCount * 100 / $memberCount);
		//复购会员数
		$orderMemberFugouCount = 0 + Db::name('shop_order')->where('aid',aid)->where('status','in','1,2,3')->group('mid')->having('count(id)>=2')->count();
		$orderMemberFugouCountBili = floor($orderMemberFugouCount * 100 / $memberCount);
		//余额会员数
		$memberMoneyCount = 0 + Db::name('member')->where('aid',aid)->where('money','>',0)->count();
		$memberMoneyCountBili = floor($memberMoneyCount * 100 / $memberCount);
		//佣金会员数
		$memberCommissionCount = 0 + Db::name('member')->where('aid',aid)->where('commission','>',0)->count();
		$memberCommissionCountBili = floor($memberCommissionCount * 100 / $memberCount);

		$memberChartData = [];
		if($memberCount == 0){
			$memberCommissionCountBili = 0;
			$memberMoneyCountBili = 0;
			$orderMemberFugouCountBili = 0;
			$orderMemberCountBili = 0;
		}
		$memberChartData[] = [
			'name'=>'佣金会员数占比',
			'itemStyle'=>['color'=>'rgba(30, 159, 255, 0.2)'],
			'num'=>$memberCommissionCount,
			'value'=>$memberCommissionCountBili
		];
		$memberChartData[] = [
			'name'=>'余额会员数占比',
			'itemStyle'=>['color'=>'rgba(30, 159, 255, 0.4)'],
			'num'=>$memberMoneyCount,
			'value'=>$memberMoneyCountBili
		];
		$memberChartData[] = [
			'name'=>'复购会员数占比',
			'itemStyle'=>['color'=>'rgba(30, 159, 255, 0.6)'],
			'num'=>$orderMemberFugouCount,
			'value'=>$orderMemberFugouCountBili
		];
		$memberChartData[] = [
			'name'=>'下单会员数占比',
			'itemStyle'=>['color'=>'#1E9FFF'],
			'num'=>$orderMemberCount,
			'value'=>$orderMemberCountBili
		];
		$memberChartDataname = [$memberCommissionCountBili,$memberMoneyCountBili,$orderMemberFugouCountBili,$orderMemberCountBili];
		
		//$memberChartDataname = ['a2','b3','c4','d5'];
		View::assign('memberChartDataname',$memberChartDataname);
		View::assign('memberChartData',$memberChartData);
		View::assign('memberCount',$memberCount);
		View::assign('memberHuoyueCount',$memberHuoyueCount);
		View::assign('orderMemberCount',$orderMemberCount);
		View::assign('orderMemberCountBili',$orderMemberCountBili);
		View::assign('orderMemberFugouCount',$orderMemberFugouCount);
		View::assign('orderMemberFugouCountBili',$orderMemberFugouCountBili);
		View::assign('memberMoneyCount',$memberMoneyCount);
		View::assign('memberMoneyCountBili',$memberMoneyCountBili);
		View::assign('memberCommissionCount',$memberCommissionCount);
		View::assign('memberCommissionCountBili',$memberCommissionCountBili);



		//会员金额概览
		//储值余额
		$memberMoney = Db::name('member')->where('aid',aid)->sum('money');
		$memberMoney = round($memberMoney,2);
		//储值总额
		$memberMoneySum = Db::name('recharge_order')->where('aid',aid)->where('status','=',1)->sum('money');
		$memberMoneySum = round($memberMoneySum,2);
		//佣金余额
		$memberCommission = Db::name('member')->where('aid',aid)->sum('commission');		
		$memberCommission = round($memberCommission,2);
		//佣金总额
		$memberCommissionSum = Db::name('member')->where('aid',aid)->sum('totalcommission');
		$memberCommissionSum = round($memberCommissionSum,2);
		//积分余额
		$memberScore = Db::name('member')->where('aid',aid)->sum('score');
		$memberScore = round($memberScore,0);
		//积分总额
		$memberScoreSum = Db::name('member')->where('aid',aid)->sum('totalscore');
		$memberScoreSum = round($memberScoreSum,0);


		View::assign('memberMoney',$memberMoney);
		View::assign('memberMoneySum',$memberMoneySum);
		View::assign('memberCommission',$memberCommission);
		View::assign('memberCommissionSum',$memberCommissionSum);
		View::assign('memberScore',$memberScore);
		View::assign('memberScoreSum',$memberScoreSum);
		$hide_wallet_total = 0;
		$able_withdraw_commission_total = $memberCommission;
		if(getcustom('hide_wallet_total')){
		    //恭喜发财定制，隐藏佣金总额、积分总额，显示可提现佣金
            $hide_wallet_total = 1;
            if(getcustom('withdraw_mul')){
                $set_withdraw_mul = Db::name('admin_set')->where('aid',aid)->value('comwithdrawmul');
                if($set_withdraw_mul>0){
                    $able_withdraw_commission_total = 0;
                    $member_lists = Db::name('member')->where('aid',aid)->where('commission','>',$set_withdraw_mul)->column('commission','id');
                    foreach($member_lists as $mid=>$commission){
                        $beishu = floor(bcdiv($commission,$set_withdraw_mul,2));
                        $able_withdraw_commission = bcmul($set_withdraw_mul,$beishu,2);
                        $able_withdraw_commission_total = bcadd($able_withdraw_commission_total,$able_withdraw_commission,2);
                    }
                }
            }
        }
		View::assign('hide_wallet_total',$hide_wallet_total);
		View::assign('able_withdraw_commission_total',$able_withdraw_commission_total);

		//通知列表
		$noticedata = Db::name('admin_notice')->where('aid',aid)->where('uid',uid)->limit(10)->order('id desc')->select()->toArray();
		View::assign('noticedata',$noticedata);

		//分类
		if(bid == 0){
			$clist = Db::name('shop_category')->Field('id,name')->where('aid',aid)->where('pid',0)->order('sort desc,id')->select()->toArray(); 
			foreach($clist as $k=>$v){
				$child = Db::name('shop_category')->Field('id,name')->where('aid',aid)->where('pid',$v['id'])->order('sort desc,id')->select()->toArray();
				foreach($child as $k2=>$v2){
					$child2 = Db::name('shop_category')->Field('id,name')->where('aid',aid)->where('pid',$v2['id'])->order('sort desc,id')->select()->toArray();
					$child[$k2]['child'] = $child2;
				}
				$clist[$k]['child'] = $child;
			}
			View::assign('clist',$clist);
		}else{
			$clist = Db::name('shop_category2')->Field('id,name')->where('aid',aid)->where('pid',0)->order('sort desc,id')->select()->toArray(); 
			foreach($clist as $k=>$v){
				$child = Db::name('shop_category2')->Field('id,name')->where('aid',aid)->where('pid',$v['id'])->order('sort desc,id')->select()->toArray();
				foreach($child as $k2=>$v2){
					$child2 = Db::name('shop_category2')->Field('id,name')->where('aid',aid)->where('pid',$v2['id'])->order('sort desc,id')->select()->toArray();
					$child[$k2]['child'] = $child2;
				}
				$clist[$k]['child'] = $child;
			}
			View::assign('clist',$clist);
		}

		return View::fetch();
	}

	public function welcome1(){
		
		$monthEnd = strtotime(date('Y-m-d',time()-86400));
		$monthStart = $monthEnd - 86400 * 29;
		
		$lastDayStart = strtotime(date('Y-m-d',time()-86400));
		$lastDayEnd = $lastDayStart + 86400;
		$thisMonthStart = strtotime(date('Y-m-1'));
		$nowtime = time();

		$last7DayStart = $lastDayEnd - 86400 * 6;


		if(input('post.op') == 'getdata'){
			$dataArr = array();
			$dateArr = array();
			for($i=0;$i<30;$i++){
				$thisDayStart = $monthStart + $i * 86400;
				$thisDayEnd = $monthStart + ($i+1) * 86400;
				$dateArr[] = date('m-d',$thisDayStart);
				if($_POST['type']==1){//客户数
					$dataArr[] = 0 + Db::name('member')->where('aid',aid)->where('createtime','<',$thisDayEnd)->count();
				}elseif($_POST['type']==2){//新增客户数
					$dataArr[] = 0 + Db::name('member')->where('aid',aid)->where('createtime','>=',$thisDayStart)->where('createtime','<',$thisDayEnd)->count();
				}elseif($_POST['type']==3){//收款金额
					$dataArr[] = 0 + Db::name('payorder')->where('aid',aid)->where('createtime','>=',$thisDayStart)->where('createtime','<',$thisDayEnd)->where('paytypeid','not in','1,4')->where('type','<>','daifu')->where('status',1)->sum('money');
				}elseif($_POST['type']==4){//订单金额
					$dataArr[] = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('paytime','>=',$thisDayStart)->where('paytime','<',$thisDayEnd)->where('status','in','1,2,3')->sum('totalprice');
				}elseif($_POST['type']==5){//订单数
					$dataArr[] = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('paytime','>=',$thisDayStart)->where('paytime','<',$thisDayEnd)->where('status','in','1,2,3')->count();
				}
			}
			return json(['dateArr'=>$dateArr,'dataArr'=>$dataArr]);
		}
		$dataArr = array();
		$dateArr = array();
		for($i=0;$i<30;$i++){
			$thisDayStart = $monthStart + $i * 86400;
			$thisDayEnd = $monthStart + ($i+1) * 86400;
			$dateArr[] = date('m-d',$thisDayStart);
			if(bid == 0){
				$dataArr[] = 0 + Db::name('member')->where('aid',aid)->where('createtime','<',$thisDayEnd)->count();
			}else{
				$dataArr[] = 0 + Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('paytime','>=',$thisDayStart)->where('paytime','<',$thisDayEnd)->where('status','in','1,2,3')->sum('totalprice');
			}
		}

		
		
		if(input('post.datetype') == 0 || !input('?post.datetype')){ //今日
			$starttime = $lastDayEnd;
			$endtime = time();
		}elseif(input('post.datetype') == 1){ //昨日
			$starttime = $lastDayStart;
			$endtime = $lastDayEnd;
		}elseif(input('post.datetype') == 7){ //七日
			$starttime = $last7DayStart;
			$endtime = time();
		}elseif(input('post.datetype') == 10){ //汇总
			$starttime = 0;
			$endtime = time();
		}elseif(input('post.datetype') == 11){ //选择时间
			$starttime = strtotime(input('post.starttime'));
			$endtime = strtotime(input('post.endtime')) + 86400;
		}

		if(input('post.op') == 'getordercount' || !input('?post.op')){
			$orderallCount = Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('status','in','0,1,2,3')->where('createtime','>=',$starttime)->where('createtime','<',$endtime)->count();
			$order0Count = Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('status','0')->where('createtime','>=',$starttime)->where('createtime','<',$endtime)->count();
			$order1Count = Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('status','1')->where('createtime','>=',$starttime)->where('createtime','<',$endtime)->count();
			$order3Count = Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('status','3')->where('createtime','>=',$starttime)->where('createtime','<',$endtime)->count();

			$orderallCount = number_format($orderallCount);
			$order0Count = number_format($order0Count);
			$order1Count = number_format($order1Count);
			$order3Count = number_format($order3Count);
			if(input('?post.op')){
				return json([
					'orderallCount'=>$orderallCount,
					'order0Count'=>$order0Count,
					'order1Count'=>$order1Count,
					'order3Count'=>$order3Count,
					'orderallCountName'=> (input('post.datetype') == 0) ? '今日订单数' : '订单数'
				]);
			}
		}

		if(input('post.op') == 'getpaynum' || !input('?post.op')){
			$paynumCount = Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('status','in','1,2,3')->where('paytime','>=',$starttime)->where('paytime','<',$endtime)->count();
			$paypersonCount = Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('status','in','1,2,3')->where('paytime','>=',$starttime)->where('paytime','<',$endtime)->group('mid')->count();
			$payproCount = Db::name('shop_order_goods')->where('aid',aid)->where('bid',bid)->where('status','in','1,2,3')->where('createtime','>=',$starttime)->where('createtime','<',$endtime)->sum('num');

			$paysumMoney = Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('status','in','1,2,3')->where('paytime','>=',$starttime)->where('paytime','<',$endtime)->where('paytypeid','1')->sum('totalprice');
			
			$paysumWeixin = Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('status','in','1,2,3')->where('paytime','>=',$starttime)->where('paytime','<',$endtime)->where('paytypeid','in','2,60')->sum('totalprice');
			$paysum = Db::name('shop_order')->where('aid',aid)->where('bid',bid)->where('status','in','1,2,3')->where('paytime','>=',$starttime)->where('paytime','<',$endtime)->sum('totalprice');

			$paynumCount = number_format($paynumCount);
			$paypersonCount = number_format($paypersonCount);
			$payproCount = number_format($payproCount);
			$paysumMoney = number_format($paysumMoney,2);
			$paysumWeixin = number_format($paysumWeixin,2);
			$paysum = number_format($paysum,2);
			if(input('?post.op')){
				return json([
					'paynumCount'=>$paynumCount,
					'paypersonCount'=>$paypersonCount,
					'payproCount'=>$payproCount,
					'paysumMoney'=>$paysumMoney,
					'paysumWeixin'=>$paysumWeixin,
					'paysum'=>$paysum,
				]);
			}
		}

		
		if(input('post.op') == 'getwithdrawsum' || !input('?post.op')){
			$withdrawSum = Db::name('member_withdrawlog')->where('aid',aid)->where('status',3)->where('createtime','>=',$starttime)->where('createtime','<',$endtime)->sum('money');
			$withdraw2Sum = Db::name('member_commission_withdrawlog')->where('aid',aid)->where('status',3)->where('createtime','>=',$starttime)->where('createtime','<',$endtime)->sum('money');
			$refundSum = Db::name('shop_refund_order')->where('aid',aid)->where('bid',bid)->where('createtime','>=',$starttime)->where('createtime','<',$endtime)->where('refund_status',2)->sum('refund_money');
			
			$withdrawSum = number_format($withdrawSum,2);
			$withdraw2Sum = number_format($withdraw2Sum,2);
			$refundSum = number_format($refundSum,2);
			if(input('?post.op')){
				return json([
					'withdrawSum'=>$withdrawSum,
					'withdraw2Sum'=>$withdraw2Sum,
					'refundSum'=>$refundSum,
				]);
			}
		}
		
		$productCount = 0 + Db::name('shop_product')->where('aid',aid)->where('bid',bid)->count();
		$productCount = number_format($productCount);

		View::assign('orderallCount',$orderallCount);
		View::assign('order0Count',$order0Count);
		View::assign('order1Count',$order1Count);
		View::assign('order3Count',$order3Count);
		View::assign('productCount',$productCount);
		View::assign('paynumCount',$paynumCount);
		View::assign('paypersonCount',$paypersonCount);
		View::assign('payproCount',$payproCount);
		View::assign('paysumMoney',$paysumMoney);
		View::assign('paysumWeixin',$paysumWeixin);
		View::assign('paysum',$paysum);
		
		View::assign('withdrawSum',$withdrawSum);
		View::assign('withdraw2Sum',$withdraw2Sum);
		View::assign('refundSum',$refundSum);

		View::assign('bid',bid);
		View::assign('endtime',$endtime);
		View::assign('dateArr',$dateArr);
		View::assign('dataArr',$dataArr);

		return View::fetch('welcome1');
	}
	//修改密码
	public function setpwd(){
		if(request()->isPost()){
			$rs = Db::name('admin_user')->where('id',$this->uid)->find();
			if($rs['pwd'] != md5(input('post.oldPassword'))){
				return json(['status'=>0,'msg'=>'当前密码输入错误']);
			}
			Db::name('admin_user')->where('id',$this->uid)->update(['pwd'=>md5(input('post.password'))]);
			\app\common\System::plog('修改密码');
			return json(['status'=>1,'msg'=>'修改成功']);
		}
		return View::fetch();
	}
	//系统设置
	public function sysset(){
        $admin = Db::name('admin')->where('id',aid)->find();
        $iconurl = "upload/loading/icon_".aid.'.png';
		$auth_data = $this->auth_data;
		if(bid == 0){
            if(getcustom('sysset_scoredkmaxpercent_memberset')){
                //会员独立设置最大积分抵扣比例
                $hasmemberset = false;
                $user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
                if($user){
                    if($user['auth_type'] !=1){
                        $admin_auth = !empty($user['auth_data'])?json_decode($user['auth_data'],true):[];
                        if($user['groupid']){
                            $admin_auth = Db::name('admin_user_group')->where('id',$user['groupid'])->value('auth_data');
                        }
                        if($admin_auth && in_array('ScoredkmaxpercentMemberset,ScoredkmaxpercentMemberset',$admin_auth)){
                            $hasmemberset = true;
                        }
                    }else{
                        $hasmemberset = true;
                    }
                }
            }
            if(getcustom('member_shopscore')){
            	$membershopscoreauth = false;
            	if(!$user){
            		//查询权限组
	            	$user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
            	}
	            //如果开启了产品积分权限
	            if($user['auth_type'] == 1){
	                $membershopscoreauth = true;
	            }else{
	                $admin_auth = json_decode($user['auth_data'],true);
	                if(in_array('MemberShopscoreAuth,MemberShopscoreAuth',$admin_auth)){
	                    $membershopscoreauth = true;
	                }
	            }
	        }
			if(request()->isPost()){
				$rs = Db::name('admin_set')->where('aid',aid)->find();
				$rs_custom = Db::name('admin_set_custom')->where('aid',aid)->find();
				$info = input('post.info/a');
                $info_custom = input('post.info_custom/a');//定制功能字段
				if(!empty($info['ali_apppublickey']) && substr($info['ali_apppublickey'], -4) != '.crt'){
					return json(['status'=>0,'msg'=>'应用公钥格式错误']);
				}
				if(!empty($info['ali_publickey']) && substr($info['ali_publickey'], -4) != '.crt'){
					return json(['status'=>0,'msg'=>'支付宝公钥格式错误']);
				}
				if(!empty($info['ali_publickey']) && substr($info['ali_rootcert'], -4) != '.crt'){
					return json(['status'=>0,'msg'=>'支付宝根证书格式错误']);
				}
				$gzts = input('post.gzts/a');
				$gzts = implode(',',$gzts);
				$info['gzts'] = $gzts;
				$ddbb = input('post.ddbb/a');
				$ddbb = implode(',',$ddbb);
				$info['ddbb'] = $ddbb;
				$info['login_mast'] = implode(',',input('post.login_mast/a'));
                $info['location_menu_list'] = input('?param.location_menu_list') ? jsonEncode(input('param.location_menu_list/a')) : '';

				foreach($this->platform as $pl){
					$info['logintype_'.$pl] = implode(',',$info['logintype_'.$pl]);
				}
				$info['textset'] = jsonEncode(input('post.textset/a'));
				$info['gettj'] = implode(',',$info['gettj']);
                $info['invoice_type'] = implode(',',$info['invoice_type']);
                //loading图标
                if(empty($info['loading_icon'])){
                    $info['loading_icon'] = PRE_URL.'/static/img/loading/1.png';
                }
				if(getcustom('admin_login_page')){
					$webinfo = input('post.webinfo/a');
					$info['webinfo'] = json_encode($webinfo,JSON_UNESCAPED_UNICODE);
				}

                if(getcustom('withdraw_mul')){
                  $info['withdrawmul'] = $info['withdrawmul']??0;
                  $info['comwithdrawmul'] = $info['comwithdrawmul']??0;
                }

                if($rs['loading_icon']!=$info['loading_icon']){
                    @unlink(ROOT_PATH.$iconurl);
                    file_put_contents(ROOT_PATH.$iconurl,request_get($info['loading_icon']));
                    $info['loading_icon'] = PRE_URL.'/'.$iconurl;
                }

                if($info['latitude'] && $info['longitude']){
                    //通过坐标获取省市区
                    $mapqq = new \app\common\MapQQ();
                    $address_component = $mapqq->locationToAddress($info['latitude'],$info['longitude']);
                    if($address_component && $address_component['status']==1){
                        $info['province'] = $address_component['province'];
                        $info['city'] = $address_component['city'];
                        $info['district'] = $address_component['district'];
                    }
                }
                if(getcustom('wxpay_member_level')){
	            	if($info['wxpay_gettj']){
	            		$info['wxpay_gettj'] = implode(',',$info['wxpay_gettj']);
	            	}
	            }
                if(getcustom('pay_transfer')){
                    if($info['pay_transfer_gettj']){
                        $info['pay_transfer_gettj'] = implode(',',$info['pay_transfer_gettj']);
                    }
                }

			    if(getcustom('commissionranking')){
                    if($info['rank_type']){
                        $info['rank_type'] = implode(',',$info['rank_type']);
                    }
                }
                if(getcustom('fenhong_ranking')){
                    $info['fenhong_rank_type'] = implode(',',$info['fenhong_rank_type']);
                }
                if(getcustom('commission_frozen')){
                    if($info['fuchi_levelids']){
                        $info['fuchi_levelids'] = implode(',',$info['fuchi_levelids']);
                    }
                    if($info['fuchi_unfrozen']){
                        $info['fuchi_unfrozen'] = implode(',',$info['fuchi_unfrozen']);
                    }
                    if(!empty( $info['fuchi_only_teamfenhong'])){
                        $info['fuchi_only_teamfenhong'] = 1;
                    }else{
                        $info['fuchi_only_teamfenhong'] = 0;
                    }

                }
                if(getcustom('fenhong_money_weishu')){
                    if($info['fenhong_money_weishu'] < 2){
                        $info['fenhong_money_weishu'] = 2;
                    }
                    if($info['fenhong_money_weishu'] > 6){
                        $info['fenhong_money_weishu'] = 6;
                    }
                }
                if(getcustom('member_money_weishu')){
                   // if($info['member_money_weishu'] < 2){
                   //     $info['member_money_weishu'] = 2;
                  //  }
                    if($info['member_money_weishu'] > 6){
                        $info['member_money_weishu'] = 6;
                    }
                }

                if(getcustom('pay_adapay')){
                    if(!$info['withdraw_adapay']){
                        $info['withdraw_adapay'] = 0;
                    }
                }
                if(getcustom('score_transfer')){
                    $info['score_transfer_gettj'] = implode(',',$info['score_transfer_gettj']);
                    $info['score_transfer_receivetj'] = implode(',',$info['score_transfer_receivetj']);
                }
                if(getcustom('areafenhong_region_ranking')){
                    $info['region_show_type'] = implode(',',$info['region_show_type']);
                    $info['region_rank_levelids'] = implode(',',$info['region_rank_levelids']);
                }
                if(getcustom('pay_adapay')){
                    if(!$info['withdraw_adapay']) $info['withdraw_adapay'] = 0;
                }
                if(getcustom('pay_huifu')){
                    if(!$info['withdraw_huifu']) $info['withdraw_huifu'] = 0;
                }
				if(getcustom('transfer_farsion')){
                    if(!$info['withdraw_aliaccount_xiaoetong']){
                        $info['withdraw_aliaccount_xiaoetong'] = 0;
                    }
                    if(!$info['withdraw_bankcard_xiaoetong']){
                        $info['withdraw_bankcard_xiaoetong'] = 0;
                    }
                }
                if(getcustom('extend_linghuoxin')){
                    if(!$info['withdraw_aliaccount_linghuoxin']){
                        $info['withdraw_aliaccount_linghuoxin'] = 0;
                    }
                    if(!$info['withdraw_bankcard_linghuoxin']){
                        $info['withdraw_bankcard_linghuoxin'] = 0;
                    }
                }
                if(getcustom('pay_allinpay')){
                    if(!$info['withdraw_bankcard_allinpayYunst']){
                        $info['withdraw_bankcard_allinpayYunst'] = 0;
                    }
                }
                if(getcustom('withdraw_paycode')){
                    if(!$info['withdraw_paycode']){
                        $info['withdraw_paycode'] = 0;
                    }
                }

                if(getcustom('score_weishu')){
                    if($info['score_weishu'] < 0){
                        $info['score_weishu'] = 0;
                    }
                    if($info['score_weishu'] > 3){
                        $info['score_weishu'] = 3;
                    }
                   // $score_decimal = $info['score_weishu'];
                   // if($score_decimal>0){
                   //     Db::execute("ALTER TABLE `ddwx_member` MODIFY COLUMN `score`  decimal(12,".$score_decimal.");");
                   //     Db::execute("ALTER TABLE `ddwx_member_scorelog` MODIFY COLUMN `score`  decimal(12,".$score_decimal.");");
                   //     Db::execute("ALTER TABLE `ddwx_member_scorelog` MODIFY COLUMN `after`  decimal(12,".$score_decimal.");");
                   //     Db::execute("ALTER TABLE `ddwx_member_scorelog` MODIFY COLUMN `used`  decimal(12,".$score_decimal.");");
                   //     Db::execute("ALTER TABLE `ddwx_scoreshop_product` MODIFY COLUMN `score_price`  decimal(12,".$score_decimal.");");
                   //     Db::execute("ALTER TABLE `ddwx_scoreshop_order` MODIFY COLUMN `totalscore`  decimal(12,".$score_decimal.");");
                   //     Db::execute("ALTER TABLE `ddwx_scoreshop_order_goods` MODIFY COLUMN `score_price`  decimal(12,".$score_decimal.");");
                   //     Db::execute("ALTER TABLE `ddwx_scoreshop_order_goods` MODIFY COLUMN `totalscore`  decimal(12,".$score_decimal.");");
                   //     Db::execute("ALTER TABLE `ddwx_signset` MODIFY COLUMN `score`  decimal(12,".$score_decimal.");");
                   //     Db::execute("ALTER TABLE `ddwx_sign_record` MODIFY COLUMN `score`  decimal(12,".$score_decimal.");");
                   //     Db::execute("ALTER TABLE `ddwx_commission_toscore_log` MODIFY COLUMN `commission`  decimal(12,".$score_decimal.");");
                   //     Db::execute("ALTER TABLE `ddwx_commission_toscore_log` MODIFY COLUMN `commission_total`  decimal(12,".$score_decimal.");");
                   //     Db::execute("ALTER TABLE `ddwx_commission_toscore_log` MODIFY COLUMN `num`  decimal(12,".$score_decimal.");");
                   // }
                }
				if(getcustom('commission_withdraw_freeze')){
                    if($info['comwithdraw_freeze']){
                        $info['comwithdraw_freeze'] = implode(',',$info['comwithdraw_freeze']);
                    }else{
						$info['comwithdraw_freeze'] = 0;
					}
					if($info['jiedong_condtion']){
                        $info['jiedong_condtion'] = implode(',',$info['jiedong_condtion']);
                    }else{
						$info['jiedong_condtion'] = 0;
					}
					$member_freezes =  Db::name('member')->where('iscomwithdraw_freeze',1)->select()->toArray();
					foreach($member_freezes as $freeze){
						$comwithdrawmoney = Db::name('member_commission_withdrawlog')->where('aid',aid)->where('mid',$freeze['id'])->where('status',3)->sum('txmoney');
						if(in_array('1',explode(',',$info['comwithdraw_freeze'])) && $comwithdrawmoney<$info['comwithdraw_totalmoney']){
							Db::name('member')->where('id',$freeze['id'])->update(['iscomwithdraw_freeze'=>0]);
						}
					}
                }
				if(getcustom('commission_withdraw_level_sxf')){
					$info['comwithdrawfee_level'] = jsonEncode(input('post.comwithdrawfee_level/a'));
				}
				if(getcustom('money_withdraw_level_sxf')){
					$info['withdrawfee_level'] = jsonEncode(input('post.withdrawfee_level/a'));
				}
				if(getcustom('teamfenhong_jicha_add_pj')){
				    $info['teamfenhong_jicha_add_pj'] = $info['teamfenhong_jicha_add_pj']??0;
                }
                if(getcustom('money_transfer')){
                    $info['money_transfer_type'] = $info['money_transfer_type'] ? implode(',',$info['money_transfer_type']) : '';
                }
                if(getcustom('money_recharge_transfer')){
                    $info['money_recharge_pay_type'] = $info['money_recharge_pay_type'] ? implode(',',$info['money_recharge_pay_type']) : '';
                }
                if(getcustom('commission_xianjin_percent')){
                	$info['xianjin_recharge_pay_type'] = $info['xianjin_recharge_pay_type'] ? implode(',',$info['xianjin_recharge_pay_type']) : '';
                }
                if(getcustom('sysset_scoredkmaxpercent_memberset')){
                    if($hasmemberset){
                        $info['scoredkmaxpercent_memberset'] = $info['scoredkmaxpercent_memberset']?$info['scoredkmaxpercent_memberset']:0;
                        if($info['scoredkmaxpercent_memberset'] == 1){
                            $info['scoredkmaxpercent_memberdata'] = input('post.memberscoredks/a')?json_encode(input('post.memberscoredks/a')):'';
                        }
                    }
                }
                if(getcustom('money_commission_withdraw_fenxiao')){
                    $info['money_withdrawfee_commissiondata'] = jsonEncode(input('post.mwcommissiondata/a'));
                    $info['commission_withdrawfee_commissiondata'] = jsonEncode(input('post.cwcommissiondata/a'));
                }
                if(getcustom('commission_to_money_rate')){
                    $info['commission_to_money_rate'] = $info['commission_to_money_rate']??0;
                }
                if(getcustom('commission_withdraw_score_conversion')){
                    $info['commission_conversion'] = $info['commission_conversion']??0;
                    $info['score_conversion'] = $info['score_conversion']??0;
                    $addRes = $info['commission_conversion'] + $info['score_conversion'];
                    if($addRes != 100){
                        return json(['status'=>0,'msg'=>'佣金提现到账比例必须为100']);
                    }
                }
                if(getcustom('member_dedamount')){
                    $info['dedamount_fullmoney'] = $info['dedamount_fullmoney'];
                    $info['dedamount_givemoney'] = $info['dedamount_givemoney'];
                    $info['dedamount_dkpercent'] = $info['dedamount_dkpercent'];
                }
                if(getcustom('gdfenhong_jiesuantype')){
                    $info['gdfh_levelids'] = implode(',',$info['gdfh_levelids']);
                }
                if(getcustom('member_shopscore') && $membershopscoreauth){
                	if($info['shopscoredkmaxpercent']<0 || $info['shopscoredkmaxpercent']>100){
                		return json(['status'=>0,'msg'=>t('产品积分').'最多抵扣百分比范围为0~100']);
                	}
                }
                if(getcustom('teamfenhong_bole')){
                    $info['teamfenhong_bl_levelids'] = implode(',',$info['teamfenhong_bl_levelids']);
                }
                if(getcustom('commission_transfer')){
                    $info['commission_transfer_gettj'] = implode(',',$info['commission_transfer_gettj']);
                }
                if(getcustom('product_chinaums_subsidy')){
                    $info['company_name'] = $info['company_name'];
                    $info['uniscid'] = $info['uniscid'];
                }

                if(getcustom('commission_withdraw_limit')){
                    $givedata = [];
                    foreach($info['shop_consume_money'] as $k=>$money){
                        if($money>0 || $info['shop_give_commission_withdraw'][$k]>0){
                            $givedata[] = array(
                                'money'=>$money,
                                'give'=>$info['shop_give_commission_withdraw'][$k]
                            );
                        }
                    }
                    unset($info['shop_consume_money'],$info['shop_give_commission_withdraw']);
                    $info['shop_consume_commission_withdraw_limit'] = $givedata ? json_encode($givedata,JSON_UNESCAPED_UNICODE) : '';
                }
                if(getcustom('commission_withdraw_upload_invoice')){
                    if($auth_data=='all' || in_array('CommissionWithdrawUploadInvoice',$auth_data)){
                        $info['commission_withdraw_upload_invoice'] = $info['commission_withdraw_upload_invoice']??0;
                    }else{
                        $info['commission_withdraw_upload_invoice'] = 0;
                    }
                }
                if(getcustom('system_moneypayscene')){
                    if($info['payscene'] && !empty($info['payscene'])){
                        if(in_array('all',$info['payscene'])){
                            $info['payscene'] = 'all';
                        }else{
                            $info['payscene'] = implode(',',$info['payscene']);
                        }
                    }else{
                        $info['payscene'] = 'none';
                    }
                }
                if(getcustom('pay_limit_paytype')){
                    $info['limit_money_score_pay'] = $info['limit_money_score_pay'];
                }
                if(getcustom('commission_send_wallet')){
                    $info['commission_send_wallet_levelids'] = implode(',',$info['commission_send_wallet_levelids']);
                }
                if(getcustom('commission_jicha_ordermember')){
                    $info['commission_jicha_ordermember'] = $info['commission_jicha_ordermember']?:0;
                }
                if(getcustom('maidan_fenhong_new')){
                    if($info['maidanfenhong_start_time']){
                        $info['maidanfenhong_start_time'] = strtotime($info['maidanfenhong_start_time']);
                    }else{
                        $info['maidanfenhong_start_time'] = 0;
                    }
                }
				if($rs){
					Db::name('admin_set')->where('aid',aid)->update($info);
				}else{
					$info['aid'] = aid;
					Db::name('admin_set')->insert($info);
				}

                if(getcustom('money_transfer') && getcustom('money_transfer_gettj')){
                    if($info_custom['money_transfer_gettj']){
                        $info_custom['money_transfer_gettj'] = implode(',',$info_custom['money_transfer_gettj']);
                    }else{
                        $info_custom['money_transfer_gettj'] = '';
                    }
                }
				if($info_custom){
                    if($rs_custom){
                        Db::name('admin_set_custom')->where('aid',aid)->update($info_custom);
                    }else{
                        $info_custom['aid'] = aid;
                        Db::name('admin_set_custom')->insert($info_custom);
                    }
                }
				$xyinfo = input('post.xyinfo/a');
				$rs = Db::name('admin_set_xieyi')->where('aid',aid)->find();
				if($rs){
					Db::name('admin_set_xieyi')->where('aid',aid)->update($xyinfo);
				}else{
					$xyinfo['aid'] = aid;
					Db::name('admin_set_xieyi')->insert($xyinfo);
				}

				$remote = jsonEncode(input('post.rinfo/a'));
				Db::name('admin')->where('id',aid)->update(['remote'=>$remote]);

				if($info['reg_invite_code'] != 0 && $info['reg_invite_code_type']==1){
					$memberlist = Db::name('member')->where('aid',aid)->where("yqcode='' or yqcode is null")->select()->toArray();
					foreach($memberlist as $member){
						$yqcode = \app\model\Member::getyqcode(aid);
						Db::name('member')->where('id',$member['id'])->update(['yqcode'=>$yqcode]);
					}
				}

				$delposter = false;//是否需要删除海报
				if($rs && $rs['name'] != $info['name']){
					$delposter = true;
				}
				if(getcustom('reg_invite_code')){
					//邀请码切换需要删除，重新生成
		            if($rs && $rs['reg_invite_code']!= $info['reg_invite_code']){
						$delposter = true;
					}
		        }
		        if($delposter){
					Db::name('member_poster')->where('aid',aid)->delete();
				}

				if(getcustom('system_replacedbstr')){
					$strinfo = input('?param.strinfo')?input('param.strinfo/a'):[];
					if($strinfo){
						$rinfo = input('post.rinfo/a');
						if($rinfo && $rinfo['type'] && $rinfo['type'] == 1 && $strinfo['updomainurl']){
							$strinfo['updomainurl'] = trim($strinfo['updomainurl']);
							//查询域名是否有小数点
		                	$pos = strpos($strinfo['updomainurl'],'.');
		                	if(!$pos)  return json(['status'=>0,'msg'=>'附件设置-本地上传域名URL格式不正确']);
		                	$domainurlArr = explode('.',$strinfo['updomainurl']);
		                	if(empty($domainurlArr[0]) || empty($domainurlArr[1])) return json(['status'=>0,'msg'=>'附件设置-本地上传域名URL格式不正确']);

		                	\app\common\System::plog('附件本地上传域名：'.$strinfo['updomainurl']);
						}
						$resstrinfo = Db::name('admin_set_replacedbstr')->where('aid',aid)->find();
						if($resstrinfo){
							Db::name('admin_set_replacedbstr')->where('aid',aid)->update($strinfo);
						}else{
							$strinfo['aid'] = aid;
							Db::name('admin_set_replacedbstr')->insert($strinfo);
						}
					}
				}

				if(getcustom('commission_xianjin_percent')){
	            	$xianjininfo = input('?post.xianjininfo')?input('post.xianjininfo/a'):[];
	            	if($xianjininfo){
	            		$xianjin = Db::name('admin_set_xianjin')->where('aid',aid)->find();
		            	if($xianjin){
		            		Db::name('admin_set_xianjin')->where('id',$xianjin['id'])->update($xianjininfo);
		            	}else{
		            		$xianjininfo['aid'] = aid;
		            		Db::name('admin_set_xianjin')->where('id',$xianjin['id'])->insert($xianjininfo);
		            	}
	            	}
	            }
				\app\common\System::plog('系统设置');
				return json(['status'=>1,'msg'=>'设置成功','url'=>true]);
			}
			$info = Db::name('admin_set')->where('aid',aid)->find();
			if(!$info){
				\app\common\System::initaccount(aid);
				$info = Db::name('admin_set')->where('aid',aid)->find();
			}
            $info_custom = Db::name('admin_set_custom')->where('aid',aid)->find();
            if(getcustom('money_transfer') && getcustom('money_transfer_gettj')){
                $info_custom['money_transfer_gettj'] = explode(',',$info_custom['money_transfer_gettj']);
            }
            //loading图标处理
            if(empty($info['loading_icon'])){
                //追加loading图标
                $defaultIcon = ROOT_PATH."static/img/loading/1.png";
                $iconpath = ROOT_PATH.$iconurl;
                if(!file_exists($iconpath)){
                    File::all_copy($defaultIcon,$iconpath);
                    $info['loading_icon'] = PRE_URL.'/'.$iconurl;
                }else{
                    $info['loading_icon'] = PRE_URL.'/'.$iconurl;
                }
            }
            $info['loading_pics'] = [
                PRE_URL.'/static/img/loading/1.png',
                PRE_URL.'/static/img/loading/2.png',
                PRE_URL.'/static/img/loading/3.png',
                PRE_URL.'/static/img/loading/4.png',
                PRE_URL.'/static/img/loading/5.png',
            ];
            if(empty($info['location_menu_list'])){
                $location_menu_list = [
                    ["isshow"=>"1","url"=>"/pages/shop/cart","icon"=>PRE_URL.'/static/img/cart_64.png'],
                    ["isshow"=>"1","url"=>"/pages/kefu/index","icon"=>PRE_URL.'/static/img/message_64.png'],
                ];
                Db::name('admin_set')->where('aid',aid)->update(['location_menu_list'=>jsonEncode($location_menu_list)]);
            }else{
                $location_menu_list = json_decode($info['location_menu_list'],true);
            }
            $info['location_menu_list'] = $location_menu_list;

			foreach($this->platform as $pl){
				$info['logintype_'.$pl] = explode(',',$info['logintype_'.$pl]);
			}
			$textset = json_decode($info['textset'], true);
			$oldtextset = ['余额'=>'余额','积分'=>'积分','佣金'=>'佣金','优惠券'=>'优惠券','会员'=>'会员'];
            $newtextset = ['团队分红'=>'团队分红','股东分红'=>'股东分红','区域代理分红'=>'区域代理分红','团队业绩'=>'团队业绩'];
            if(getcustom('level_teamfenhong')) {$newtextset['等级团队分红']='等级团队分红';}
            if(getcustom('product_teamfenhong')) {$newtextset['商品团队分红']='商品团队分红';}
            if(getcustom('plug_yuebao')) {$newtextset['余额宝']='余额宝';}
            if(getcustom('pay_yuanbao')) {$newtextset['元宝']='元宝';$newtextset['现金']='现金';}
            if(getcustom('member_gongxian')) {$newtextset['贡献']='贡献';}
            if(getcustom('pay_daifu')){
                $newtextset['好友代付'] = '好友代付';
            }
            if(getcustom('pay_transfer')) {$newtextset['转账汇款']='转账汇款';}
            if(getcustom('commission_frozen')) {$newtextset['扶持金']='扶持金';}
            if(getcustom('touzi_fenhong')) {$newtextset['投资分红']='投资分红';}
			if(getcustom('weight_template')) {$newtextset['包装费']='包装费';}
            if(getcustom('commission_xiaofei')) {$newtextset['冻结佣金']='冻结佣金';}
            if(getcustom('teamfenhong_shouyi')) {$newtextset['团队收益']='团队收益';}
            if(getcustom('business_teamfenhong')) {$newtextset['商家团队分红']='商家团队分红';}
            if(getcustom('commission_butie')){
                $newtextset['分销补贴']='分销补贴';
            }
            if(getcustom('teamfenhong_jicha')){
                $newtextset['团队级差分红']='团队级差分红';
            }
            if(getcustom('business_teamfenhong_pj')) {$newtextset['商家团队分红平级奖']='商家团队分红平级奖';}
            if(getcustom('product_givetongzheng')){
                $newtextset['通证']='通证';
            }
            if(getcustom('teamfenhong_share')){
                $newtextset['团队分红共享奖']='团队分红共享奖';
            }
            if(getcustom('teamfenhong_bole')){
                $newtextset['团队分红伯乐奖']='团队分红伯乐奖';
            }
            if(getcustom('fenhong_jiaquan_copies')){
                $newtextset['分红份数'] = '分红份数';
            }
			if(getcustom('mendian_upgrade')){
				//$admin =  Db::name('admin')->field('mendian_upgrade_status')->where('id',aid)->find();
				if($admin['mendian_upgrade_status']==1){
					 $newtextset['门店'] = '门店';
				}
			}
            if(getcustom('up_giveparent_prize')) {
                $newtextset['见点奖']='见点奖';
            }
            if(getcustom('member_commission_max')) {
                $newtextset['佣金上限']='佣金上限';
            }
            if(getcustom('yx_mangfan')){
                $newtextset['消费盲返'] = '消费盲返';
                $newtextset['可享消费盲返'] = '可享消费盲返';
            }
            if(getcustom('yx_queue_free_freeze_account')){
                if(!$textset['冻结账户']) $textset['冻结账户']='冻结账户';
            }
            if(getcustom('yx_queue_free_fenzhang_wxpay')){
                if(!$textset['排队奖励分账']) $textset['排队奖励分账']='排队奖励分账';
            }
            if(getcustom('consumer_value_add')){
                if(!$textset['绿色积分']) $textset['绿色积分']='绿色积分';
                if(!$textset['奖金池']) $textset['奖金池']='奖金池';
            }
            if(getcustom('consumer_value_add') && getcustom('greenscore_max')){
                if(!$textset['封顶额度']) $textset['封顶额度']='封顶额度';
            }
            if(getcustom('active_coin')){
                if(!$textset['激活币']) $textset['激活币']='激活币';
            }
            if(getcustom('member_shop_favorite')){
                if(!$textset['商城商品收藏']) $textset['商城商品收藏']='商城商品收藏';
                if(!$textset['优惠价']) $textset['优惠价']='优惠价';
            }
            if(getcustom('business_deposit')){
                if(!$textset['入驻保证金']) $textset['入驻保证金']='入驻保证金';
            }
            if(getcustom('yx_hongbao_queue_free')){
                if(!$textset['红包排队返利']) $textset['红包排队返利']='红包排队返利';
            }
            if(getcustom('yx_team_yeji')){
                if(!$textset['团队业绩阶梯奖']) $textset['团队业绩阶梯奖']='团队业绩阶梯奖';
            }
            if(getcustom('yx_queue_free')){
                if($auth_data=='all' || in_array('QueueFree/*',$auth_data)){
                    if(!$textset['排队免单']) $textset['排队免单']='排队免单';
                    if(!$textset['免单设置']) $textset['免单设置']='免单设置';
                    if(!$textset['排队奖励返现']) $textset['排队奖励返现']='排队奖励返现';
                    if(!$textset['消费返利']) $textset['消费返利']='消费返利';
                    if(!$textset['返利比例']) $textset['返利比例']='返利比例';
                    if(!$textset['排队返利']) $textset['排队返利']='排队返利';
                    if(!$textset['排队返现']) $textset['排队返现']='排队返现';
                    if(!$textset['已返金额']) $textset['已返金额']='已返金额';
                }else{
                    unset($textset['排队免单']);
                    unset($textset['免单设置']);
                    unset($textset['排队奖励返现']);
                    unset($textset['消费返利']);
                    unset($textset['返利比例']);
                    unset($textset['排队返利']);
                    unset($textset['排队返现']);
                    unset($textset['已返金额']);
                }
            }
            if(getcustom('gold_bean_shop')){
                if($auth_data=='all' || in_array('GoldBeanShopProduct/*',$auth_data)){
                    if(!$textset['金豆']) $textset['金豆']='金豆';
                }else{
                    unset($textset['金豆']);
                }
            }
            if(getcustom('commission_two_level')){
                if(!$textset['二级分销']) $textset['二级分销']='二级分销';
            }
            if(getcustom('mingpian_customtext')){
                if(!$textset['名片']) $textset['名片']='名片';
                if(!$textset['联系信息']) $textset['联系信息']='联系信息';
            }
            if(getcustom('member_shopscore') && $membershopscoreauth){
                if(!$textset['产品积分']) $textset['产品积分']='产品积分';
            }
            if(getcustom('fenhong_jiaquan_bylevel')){
                if(!$textset['加权分红']) $textset['加权分红']='加权分红';
            }
            if(getcustom('commission_withdrawfee_fundpool')){
                if(!$textset['基金池']) $textset['基金池']=t('基金池');
            }
            if(getcustom('commission_withdraw_limit')){
                if(!$textset['佣金提现额度']) $textset['佣金提现额度']=t('佣金提现额度');
            }
            if(getcustom('teamfenhong_jichamoney')){
                if(!$textset['级差奖励']) $textset['级差奖励']=t('级差奖励');
            }
            if(getcustom('extend_business_shareholder')){
                if(!$textset['发起人']) $textset['发起人']=t('发起人');
                if(!$textset['入股人']) $textset['入股人']=t('入股人');
            }
			if(!$textset) {
                $textset = array_merge($oldtextset,$newtextset);
            } else {
			    if(array_keys($textset) == array_keys($oldtextset)) {
                    $textset = array_merge($textset, $newtextset);
                }
                if(getcustom('plug_yuebao') && !$textset['余额宝']){
                	$textset['余额宝']='余额宝';
                }
                if(getcustom('pay_yuanbao')){
                	if(!$textset['元宝']) $textset['元宝']='元宝';
                	if(!$textset['现金']) $textset['现金']='现金';
                }
                if(getcustom('pay_daifu')){
                    if(!$textset['好友代付']) $textset['好友代付']='好友代付';
                }
                if(getcustom('pay_transfer')){
                    if(!$textset['转账汇款']) $textset['转账汇款']='转账汇款';
                }
                if(getcustom('commission_frozen')){
                    if(!$textset['扶持金']) $textset['扶持金']='扶持金';
                }
                if(getcustom('member_gongxian')) {
                    if(!$textset['贡献']) $textset['贡献']='贡献';
                }
                if(getcustom('touzi_fenhong')) {
                    if(!$textset['投资分红']) $textset['投资分红']='投资分红';
                }
				if(getcustom('weight_template')) {
                    if(!$textset['包装费']) $textset['包装费']='包装费';
                }
                if(getcustom('commission_xiaofei')){
                    if(!$textset['冻结佣金']) $textset['冻结佣金']='冻结佣金';
                }
                if(getcustom('teamfenhong_shouyi')) {
                    if(!$textset['团队收益']) $textset['团队收益']='团队收益';
                }
                if(getcustom('business_teamfenhong')) {
                    if(!$textset['商家团队分红']) $textset['商家团队分红']='商家团队分红';
                }
                if(getcustom('business_teamfenhong_pj')) {
                    if(!$textset['商家团队分红平级奖']) $textset['商家团队分红平级奖']='商家团队分红平级奖';
                }
                if(getcustom('product_givetongzheng')){
                    if(!$textset['通证']) $textset['通证']='通证';
                }
                if(getcustom('teamfenhong_share')){
                    if(!$textset['团队分红共享奖']) $textset['团队分红共享奖']='团队分红共享奖';
                }
                if(getcustom('teamfenhong_bole')){
                    if(!$textset['团队分红伯乐奖']) $textset['团队分红伯乐奖']='团队分红伯乐奖';
                }
                if(getcustom('fenhong_jiaquan_copies') && !$textset['分红份数']){
                    $textset['分红份数'] = '分红份数';
                }
				
				if(getcustom('mendian_upgrade') && !$textset['门店']){
					//$admin =  Db::name('admin')->field('mendian_upgrade_status')->where('id',aid)->find();
					if($admin['mendian_upgrade_status']==1){
						 $textset['门店'] = '门店';
					}
				}
                if(getcustom('up_giveparent_prize') && !$textset['见点奖']){
                    $textset['见点奖'] = '见点奖';
                }
                if(getcustom('level_teamfenhong')){
                	if(!$textset['等级团队分红']) $textset['等级团队分红']='等级团队分红';
                }
                if(getcustom('member_commission_max')) {
                    if(!$textset['佣金上限']) $textset['佣金上限']='佣金上限';
                }
                if(getcustom('yx_mangfan')){
                    if(!$textset['消费盲返']) $textset['消费盲返']='消费盲返';
                    if(!$textset['可享消费盲返']) $textset['可享消费盲返']='可享消费盲返';
                }
                if(getcustom('yx_queue_free_freeze_account')){
                    if(!$textset['冻结账户']) $textset['冻结账户']='冻结账户';
                }
                if(getcustom('yx_queue_free_fenzhang_wxpay')){
                    if(!$textset['排队奖励分账']) $textset['排队奖励分账']='排队奖励分账';
                }
                if(getcustom('fenhong_area_zhitui_pingji')){
                    if(!$textset['区域代理分红直推平级奖']) $textset['区域代理分红直推平级奖']='区域代理分红直推平级奖';
                }
                if(getcustom('fenhong_jiaquan_area')){
                    if(!$textset['区域代理加权分红']) $textset['区域代理加权分红']='区域代理加权分红';
                }
                if(getcustom('fenhong_jiaquan_gudong')){
                    if(!$textset['股东加权分红']) $textset['股东加权分红']='股东加权分红';
                }
                if(getcustom('commission_withdrawfee_fundpool')){
                    if(!$textset['基金池']) $textset['基金池']=t('基金池');
                }
                if(getcustom('commission_withdraw_limit')){
                    if(!$textset['佣金提现额度']) $textset['佣金提现额度']=t('佣金提现额度');
                }
                if(getcustom('teamfenhong_jichamoney')){
	                if(!$textset['级差奖励']) $textset['级差奖励']=t('级差奖励');
	            }
	            if(getcustom('extend_business_shareholder')){
	                if(!$textset['发起人']) $textset['发起人']=t('发起人');
	                if(!$textset['入股人']) $textset['入股人']=t('入股人');
	            }
            }
            if(!$textset['团队业绩']){
				$textset['团队业绩']='团队业绩';
			}
			if(!$textset['我的团队']){
				$textset['我的团队']='我的团队';
			}
			if(!$textset['分销订单']){
				$textset['分销订单']='分销订单'; 
			}
			if(!$textset['自定义表单']){
				$textset['自定义表单']='自定义表单';
			}
			if(!$textset['推荐人']){
				$textset['推荐人']='推荐人';
			}
			if(!$textset['团队分红']){
                $textset['团队分红']='团队分红';
            }
            if(!$textset['股东分红']){
                $textset['股东分红']='股东分红';
            }
            if(!$textset['区域代理分红']){
                $textset['区域代理分红']='区域代理分红';
            }
            $textset['团队'] = $textset['团队']??'团队';
            $textset['一级'] = $textset['一级']??'一级';
            $textset['二级'] = $textset['二级']??'二级';
            $textset['三级'] = $textset['三级']??'三级';
            $textset['下级'] = $textset['下级']??'下级';
            $textset['下二级'] = $textset['下二级']??'下二级';
            $textset['下三级'] = $textset['下三级']??'下三级';
            $textset['后台修改'] = $textset['后台修改']??'后台修改';
            $textset['等级价格极差分销'] = $textset['等级价格极差分销']??'等级价格极差分销';
			if(getcustom('other_money')){
				$othermoney_status = $admin['othermoney_status'];
				if($othermoney_status==1){
					if(!$textset['余额2']){
						$textset['余额2']='余额2';
					}
					if(!$textset['余额3']){
						$textset['余额3']='余额3';
					}
					if(!$textset['余额3']){
						$textset['余额3']='余额3';
					}
					if(!$textset['余额4']){
						$textset['余额4']='余额4';
					}
					if(!$textset['余额5']){
						$textset['余额5']='余额5';
					}
					if(!$textset['冻结金额']){
						$textset['冻结金额']='冻结金额';
					}
				}
			}
            if(getcustom('teamfenhong_gouche')) {
                if(!$textset['购车基金']) $textset['购车基金']='购车基金';
            }
            if(getcustom('teamfenhong_lvyou')) {
                if(!$textset['旅游基金']) $textset['旅游基金']='旅游基金';
            }
            if(getcustom('product_bonus_pool')) {
                if(!$textset['贡献值']) $textset['贡献值']='贡献值';
            }
            if(getcustom('fenhong_gudong_huiben')) {
                if(!$textset['回本股东分红']) $textset['回本股东分红']='回本股东分红';
                if(!$textset['回本股东分红额度']) $textset['回本股东分红额度']='回本股东分红额度';
            }
            if(getcustom('commission_butie')){
                if(!$textset['分销补贴']) $textset['分销补贴']='分销补贴';
            }
            if(getcustom('teamfenhong_jicha')){
                if(!$textset['团队级差分红']) $textset['团队级差分红']='团队级差分红';
            }
            if(getcustom('member_overdraft_money')){
                if(!$textset['信用额度']) $textset['信用额度']='信用额度';
            }
            if(getcustom('shop_paiming_fenhong') && ($auth_data=='all' || in_array('PaimingFenhong/*',$auth_data))){
                if(!$textset['排名分红']) $textset['排名分红']='排名分红';
            }
            if(getcustom('textset_yue_commission_unit')){
                if(!$textset['佣金单位']) $textset['佣金单位']='元';
                if(!$textset['余额单位']) $textset['余额单位']='元';
            }
            if(getcustom('product_service_fee')) {
                if(!$textset['服务费']) $textset['服务费']='服务费';
            } 
			if(getcustom('textset_money_unit')){
                if(!$textset['余额单位']) $textset['余额单位']='元';
            }
            if(getcustom('team_jiandian')){
                if(!$textset['团队见点奖']) $textset['团队见点奖']='团队见点奖';
            }
            if(getcustom('team_fuchijin')){
                if(!$textset['团队扶持金']) $textset['团队扶持金']='团队扶持金';
            }
            if(getcustom('teamfenhong_pingji')){
                if(!$textset['团队分红平级奖']) $textset['团队分红平级奖']='团队分红平级奖';
            }
            if(getcustom('yueke_extend')){
                if(!$textset['教练']) $textset['教练']='教练';
                if(!$textset['课时']) $textset['课时']='课时';
            }
            if(getcustom('product_quanyi')){
                if(!$textset['权益商品']) $textset['权益商品']='权益商品';
            }
            if(getcustom('yx_team_yeji_weight')){
                if(!$textset['业绩加权奖']) $textset['业绩加权奖']='业绩加权奖';
            }
			$info['gettj'] = explode(',',$info['gettj']);
            $info['invoice_type'] = explode(',',$info['invoice_type']);
            if(getcustom('wxpay_member_level')){
            	if($info['wxpay_gettj']){
            		$info['wxpay_gettj'] = explode(',',$info['wxpay_gettj']);
            	}
            }
            if(getcustom('pay_transfer')){
                $info['pay_transfer_gettj'] = $info['pay_transfer_gettj'] ? explode(',',$info['pay_transfer_gettj']) : [];
            }
            if(getcustom('member_goldmoney_silvermoney')){
            	$showgoldmoney   = true;$showsilvermoney = true;
            	//平台权限
                if($this->auth_data != 'all' && !in_array('Member/addGoldmoney',$this->auth_data)){
                    $showgoldmoney = false;
                }
                if($this->auth_data != 'all' && !in_array('Member/addSilvermoney',$this->auth_data)){
                    $showsilvermoney = false;
                }
				if($showgoldmoney){
					if(!$textset['金值']){
						$textset['金值']='金值';
					}
				}
				if($showsilvermoney){
					if(!$textset['银值']){
						$textset['银值']='银值';
					}
				}
			}
            if(getcustom('commission_two_level')){
                if(!$textset['二级分销']) $textset['二级分销']='二级分销';
            }
            if(getcustom('bonus_pool_gold')){
                if(!$textset['奖金池']) $textset['奖金池']='奖金池';
                if(!$textset['金币']) $textset['金币']='金币';
            }
            if(getcustom('yx_cashback_log')){
                if(!$textset['释放积分']) $textset['释放积分']='释放积分';
            }
            if(getcustom('yx_cashback_multiply')){
                if(!$textset['增值释放积分']) $textset['增值释放积分']='增值释放积分';
            }
            if(getcustom('green_score_reserves')){
                if(!$textset['预备金']) $textset['预备金']='预备金';
            }
            if(getcustom('member_shopscore') && $membershopscoreauth){
                if(!$textset['产品积分']) $textset['产品积分']='产品积分';
            }
            if(getcustom('tuanzhang_fenhong') && ($auth_data=='all' || in_array('Commission/tuanzhang_fenhonglog',$auth_data))){
                if(!$textset['团长分红']) $textset['团长分红']='团长分红';
            }
            if(getcustom('yx_team_yeji_fenhong')){
                if(!$textset['团队业绩阶梯分红']) $textset['团队业绩阶梯分红']='团队业绩阶梯分红';
            }
            if(getcustom('yuyue_worker_upload_service_pics')){
                if(!$textset['人员']) $textset['人员']='人员';
            }
			if(getcustom('luckycollage_text_custom')){
				if(!$textset['幸运拼团']) $textset['幸运拼团']='幸运拼团';
			}
			if(getcustom('product_promotion_tag')){
				if(!$textset['升级多赚']) $textset['升级多赚']='升级多赚';
			}
			if(getcustom('freeze_money')){
                if(!$textset['冻结资金']) $textset['冻结资金']='冻结资金';
            }
			if(getcustom('fuwu_usercenter')){
                $textset['服务中心'] = $textset['服务中心']??'服务中心';
            }
			if(getcustom('commission_send_wallet')){
                $info['commission_send_wallet_levelids'] = explode(',',$info['commission_send_wallet_levelids']);
            }
            if(getcustom('yx_network_help')){
                $textset['互助积分'] = $textset['互助积分']??'互助积分';
                $textset['补贴积分'] = $textset['补贴积分']??'补贴积分';
            }
            if(getcustom('commission_xianjin_percent')){
                $textset['现金'] = $textset['现金']??'现金';
            }
            if(getcustom('yx_cashback_decmoney_lock')){
                $textset['冻结余额'] = $textset['冻结余额']??'冻结余额';
            }
            if(getcustom('yx_buyer_subsidy')){
                $textset['返现积分'] = $textset['返现积分']??'返现积分';
                $textset['让利'] = $textset['让利']??'让利';
            }
            if(getcustom('yx_new_score')){
                $textset['新积分'] = $textset['新积分']??'新积分';
                $textset['待返现额度'] = $textset['待返现额度']??'待返现额度';
                $textset['冻结额度'] = $textset['冻结额度']??'冻结额度';
                $textset['商户'] = $textset['商户']??'商户';
                if(getcustom('yx_new_score_speed_pack')){
                    $textset['加速包'] = $textset['加速包']??'加速包';
                }
            }
            if(getcustom('deposit')){
                if($auth_data=='all' || in_array('Deposit/*',$auth_data)){
                    $textset['押金']= $textset['押金']??'押金';
                    $textset['电子水票']= $textset['电子水票']??'电子水票';
                }else{
                    unset($textset['押金']);
                    unset($textset['电子水票']);
                }
            }
            if(getcustom('yx_digital_consum')){
                $textset['数字消费'] = $textset['数字消费']??'数字消费';
                $textset['数字权益'] = $textset['数字权益']??'数字权益';
                $textset['数字价格'] = $textset['数字价格']??'数字价格';
                $textset['数字奖金池'] = $textset['数字奖金池']??'数字奖金池';
            }
            if(getcustom('yx_cashback_decay')){
                $textset['购物返现'] = $textset['购物返现']??'购物返现';
            }
            if(getcustom('yx_commission_to_lingqiantong')){
                $textset['零钱通'] = $textset['零钱通']??'零钱通';
            }
            $default_cid = Db::name('member_level_category')->where('aid',aid)->where('isdefault', 1)->value('id');
            $default_cid = $default_cid ? $default_cid : 0;
            $levellist = Db::name('member_level')->where('aid',aid)->where('cid', $default_cid)->order('sort,id')->select()->toArray();
			View::assign('levellist',$levellist);

            if(getcustom('sysset_scoredkmaxpercent_memberset')){
                $memberscoredks = [];
                if($hasmemberset){
                    $scoredkmaxpercent_memberdata = [];
                    if(!empty($info['scoredkmaxpercent_memberdata'])){
                        $scoredkmaxpercent_memberdata = json_decode($info['scoredkmaxpercent_memberdata'],true);
                    }
                    foreach($levellist as $lv){
                        $memberscoredk = [];
                        $memberscoredk['id']   = $lv['id'];
                        $memberscoredk['name'] = $lv['name'];
                        $memberscoredk['scoredkmaxpercent'] = '';
                        if($scoredkmaxpercent_memberdata){
                            foreach($scoredkmaxpercent_memberdata as $smk=>$smv){
                                if($smk == $lv['id']){
                                    $memberscoredk['scoredkmaxpercent'] = $smv['scoredkmaxpercent'];
                                }
                            }
                            unset($smv);
                        }
                        $memberscoredks[] = $memberscoredk;
                    }
                    unset($lv);
                }
                View::assign('memberscoredks',$memberscoredks);
                View::assign('hasmemberset',$hasmemberset);
            }
			$ainfo = Db::name('admin')->where('id',aid)->find();

			$xyinfo = Db::name('admin_set_xieyi')->where('aid',aid)->find();
            if(getcustom('file_size_limit')){
                $remote = Db::name('sysset')->where('name','remote')->value('value');
                $remote = json_decode($remote,true);
                View::assign('remote',$remote);
            }

			if($this->auth_data == 'all' || in_array('partner_jiaquan',$this->auth_data)){
				$partner_jiaquan = true;
			}else{
				$partner_jiaquan = false;
			}
			if($this->auth_data == 'all' || in_array('partner_gongxian',$this->auth_data)){
				$partner_gongxian = true;
			}else{
				$partner_gongxian = false;
			}
			if(getcustom('team_yeji_ranking')){
			    $team_yeji_show = false;
                if($this->auth_data == 'all' || in_array('TeamYejiRanking',$this->auth_data)){
                    $team_yeji_show = true;
                }
                View::assign('team_yeji_show',$team_yeji_show);
            }
			if(getcustom('admin_login_page')){
                $webinfo = $info['webinfo'] ? json_decode($info['webinfo'],true) : [];
				View::assign('webinfo',$webinfo);
				
				$admin_user = Db::name('admin_user')->where('isadmin',2)->find();
				if($admin_user['aid'] != aid ){
					$sysset_webinfo = Db::name('sysset')->where('name','webinfo')->value('value');
					$sysset_webinfo = json_decode($sysset_webinfo,true);
                    $open_login_page = $sysset_webinfo['open_login_page'];
				}else{
                    $open_login_page = 0;
				}
				View::assign('open_login_page',$open_login_page);
            }
			
			if(getcustom('commission_withdraw_level_sxf')){
				$levels = Db::name('member_level')->where('aid',aid)->order('sort asc')->select()->toArray();
			    View::assign('levels',$levels);
			}

			View::assign('isadmin',$this->user['isadmin']);
			View::assign('info',$info);
            View::assign('admin',$admin);
            View::assign('ainfo',$ainfo);
			View::assign('textset',$textset);
			View::assign('xyinfo',$xyinfo);
			View::assign('rinfo',json_decode($ainfo['remote'],true));
			View::assign('partner_jiaquan',$partner_jiaquan);
			View::assign('partner_gongxian',$partner_gongxian);
            if(getcustom('money_commission_withdraw_fenxiao')){
                $default_cid = Db::name('member_level_category')->where('aid',aid)->where('isdefault', 1)->value('id');
                $default_cid = $default_cid ? $default_cid : 0;
                $aglevellist = Db::name('member_level')->where('aid',aid)->where('cid', $default_cid)->where('can_agent','<>',0)->order('sort,id')->select()->toArray();
                View::assign('aglevellist',$aglevellist);
            }
            if(getcustom('member_shopscore')){
	            View::assign('membershopscoreauth',$membershopscoreauth);
	        }
            if(getcustom('system_replacedbstr')){
            	//附加域名替换设置
                $systemReplacedbstrauth = false;
                //查询权限组
                if(!$user){
                    $user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
                }
                //如果开启了权限
                if($user['auth_type'] == 1){
                    $systemReplacedbstrauth = true;
                }else{
                    $admin_auth = json_decode($user['auth_data'],true);
                    if(in_array('SystemReplacedbstrAuth,SystemReplacedbstrAuth',$admin_auth)){
                        $systemReplacedbstrauth = true;
                    }
                }
                if($systemReplacedbstrauth){
                    $strinfo = Db::name('admin_set_replacedbstr')->where('aid',aid)->find();
                    View::assign('strinfo',$strinfo??[]);
                }
                View::assign('systemReplacedbstrauth',$systemReplacedbstrauth);
            }
            View::assign('info_custom',$info_custom);
            //是否显示分销平级奖的设置
            $show_pj_set = 0;
            if(getcustom('commission_parent_pj') && ($auth_data=='all' || in_array('commission_parent_pj',$auth_data))){
                if( getcustom('commission_parent_pj_jinsuo')){
                    $show_pj_set = 1;
                }
                if( getcustom('commission_parent_pj_once')){
                    $show_pj_set = 1;
                }
                if( getcustom('commission_parent_pj_member')){
                    $show_pj_set = 1;
                }
            }
            View::assign('show_pj_set',$show_pj_set);

            if(getcustom('commission_xianjin_percent')){
            	$xianjininfo = Db::name('admin_set_xianjin')->where('aid',aid)->find();
            	View::assign('xianjininfo',$xianjininfo);
            }
			return View::fetch();
		}
		else{
			$bset    = Db::name('business_sysset')->where('aid',aid)->find();
			$oldinfo = Db::name('business')->where('aid',aid)->where('id',bid)->find();

			$BaseSet=$RefundSet=$OpenSet=$PaySet=true;//基础、退货、营业、支付权限设置
            if(getcustom('business_sysset_auth')){
                //是否需要验证权限
                $checkSet=false;
                //平台权限
                $user = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->field('auth_type,auth_data,groupid')->find();
                if($user){
                    if($user['auth_type'] !=1){
                        if($user['groupid']){
                            $user['auth_data'] = Db::name('admin_user_group')->where('id',$user['groupid'])->value('auth_data');
                        }
                        $admin_auth = !empty($user['auth_data'])?json_decode($user['auth_data'],true):[];
                        if(in_array('BusinessSysSet,BusinessSysSet',$admin_auth)){
                            $checkSet=true;
                        }
                    }else{
                        $checkSet=true;
                    }
                }
                //是否开启了验证
                if($checkSet){
                    //查询商户权限
                    $user2 = Db::name('admin_user')->where('aid',aid)->where('bid',bid)->where('isadmin','>',0)->field('auth_type,auth_data,groupid')->find();
                    if($user2 && $user2['auth_type'] !=1){
                        if($user2['groupid']){
                            $user2['auth_data'] = Db::name('admin_user_group')->where('id',$user2['groupid'])->value('auth_data');
                        }
                        $admin_auth2 = !empty($user2['auth_data'])?json_decode($user2['auth_data'],true):[];
                        if(!in_array('BusinessSysSet,BusinessSysSet',$admin_auth2)){
                            $checkSet=false;
                        }
                        if($checkSet){
                            if(!in_array('BusinessBaseSet,BusinessBaseSet',$admin_auth2)){
                                $BaseSet = false;
                            }
                            if(!in_array('BusinessRefundSet,BusinessRefundSet',$admin_auth2)){
                                $RefundSet = false;
                            }
                            if(!in_array('BusinessOpenSet,BusinessOpenSet',$admin_auth2)){
                                $OpenSet = false;
                            }
                            if(!in_array('BusinessPaySet,BusinessPaySet',$admin_auth2)){
                                $PaySet = false;
                            }
                        }
                    }
                }
            }
			if(request()->isPost()){
				$postinfo = input('post.info/a');
				$info = [];
				//基础部分
				if($BaseSet){
					$info['tel'] = $postinfo['tel'];
					$info['kfurl'] = $postinfo['kfurl'];
					$info['logo'] = $postinfo['logo'];
					$info['pics'] = $postinfo['pics'];
					$info['content'] = $postinfo['content'];
					$info['address'] = $postinfo['address'];
					$info['longitude'] = $postinfo['longitude'];
					$info['latitude'] = $postinfo['latitude'];
	                if($postinfo['latitude'] && $postinfo['longitude']){
	                    //通过坐标获取省市区
	                    $mapqq = new \app\common\MapQQ();
	                    $address_component = $mapqq->locationToAddress($postinfo['latitude'],$postinfo['longitude']);
	                    if($address_component && $address_component['status']==1){
	                        $info['province'] = $address_component['province'];
	                        $info['city'] = $address_component['city'];
	                        $info['district'] = $address_component['district'];
	                    }
	                }
	                if(getcustom('shop_code_exchangepage')){
	                	$info['exchange_page'] = $postinfo['exchange_page']??0;
	                	$info['exchange_page_bgpic'] = $postinfo['exchange_page_bgpic'];
	                	$info['exchange_page_tourl'] = $postinfo['exchange_page_tourl'];
	                }
                }

                //退款部分
                if($RefundSet){
                	$info['return_name']     = $postinfo['return_name']?$postinfo['return_name']:'';
					$info['return_tel']      = $postinfo['return_tel']?$postinfo['return_tel']:'';
					$info['return_province'] = $postinfo['return_province']?$postinfo['return_province']:'';
					$info['return_city']     = $postinfo['return_city']?$postinfo['return_city']:'';
					$info['return_area']     = $postinfo['return_area']?$postinfo['return_area']:'';
					$info['return_address']  = $postinfo['return_address']?$postinfo['return_address']:'';
                }
				
				//营业部分
				if($OpenSet){
					$info['start_hours2'] = $postinfo['start_hours2'];
					$info['end_hours2'] = $postinfo['end_hours2'];
					$info['start_hours3'] = $postinfo['start_hours3'];
					$info['end_hours3'] = $postinfo['end_hours3'];
					$info['start_hours'] = $postinfo['start_hours'];
					$info['end_hours'] = $postinfo['end_hours'];
               		$info['end_buy_status'] = $postinfo['end_buy_status'];
               		$info['is_open'] = $postinfo['is_open'];
				}

				//支付相关设置
				if($PaySet){
					$info['weixin'] = $postinfo['weixin'];
					$info['aliaccount'] = $postinfo['aliaccount'];
					$info['bankname'] = $postinfo['bankname'];
					$info['bankcarduser'] = $postinfo['bankcarduser'];
					$info['bankcardnum'] = $postinfo['bankcardnum'];
					$info['invoice'] = $postinfo['invoice'];
                	$info['invoice_type'] = implode(',',$postinfo['invoice_type']);
                	$info['autocollecthour'] = $postinfo['autocollecthour'];
                	if(getcustom('business_selfscore') && $bset['business_selfscore'] == 1 && $bset['business_selfscore2'] == 1){
						$info['scoreset'] = $postinfo['scoreset'];
						$info['score2money'] = $postinfo['score2money'];
						$info['scoredkmaxpercent'] = $postinfo['scoredkmaxpercent'];
						$info['scorebdkyf'] = $postinfo['scorebdkyf'];
					}
					if(getcustom('touzi_fenhong')){
	                    $info['touzi_fh_type'] = $postinfo['touzi_fh_type'];
	                    $info['touzi_fh_percent'] = $postinfo['touzi_fh_percent'];
	                }
	                if(getcustom('money_dec') || getcustom('cashier_money_dec')){
	                    $info['money_dec']      = $postinfo['money_dec'];
	                    $info['money_dec_rate'] = $postinfo['money_dec_rate'];
	                }
	                if(getcustom('cashier_overdraft_money_dec')){
	                    $info['overdraft_money_dec']      = $postinfo['overdraft_money_dec'];
	                    $info['overdraft_money_dec_rate'] = $postinfo['overdraft_money_dec_rate'];
	                }
	                if(getcustom('alipay_auto_transfer') || getcustom('shangfutong_daifu')){
                        $info['aliaccountname']      = $postinfo['aliaccountname'];
                    }
                    if(getcustom('active_score')){
                    	$shopactivescore_data = [];
                        $shopactivescore_data['shopactivescore_ratio']          = $postinfo['shopactivescore_ratio'];
                        $shopactivescore_data['member_shopactivescore_ratio']   = $postinfo['member_shopactivescore_ratio'];
                        $shopactivescore_data['business_shopactivescore_ratio'] = $postinfo['business_shopactivescore_ratio'];
                        $info['shopactivescore_data'] = json_encode($shopactivescore_data);
                    	$maidanactivescore_data = [];
                        $maidanactivescore_data['maidanactivescore_ratio']          = $postinfo['maidanactivescore_ratio'];
                        $maidanactivescore_data['member_maidanactivescore_ratio']   = $postinfo['member_maidanactivescore_ratio'];
                        $maidanactivescore_data['business_maidanactivescore_ratio'] = $postinfo['business_maidanactivescore_ratio'];
                        $info['maidanactivescore_data'] = json_encode($maidanactivescore_data);
			        }
			        if(getcustom('member_dedamount')){
			        	$paymoney_givepercent = $postinfo['paymoney_givepercent']??0;
			        	//修改让利比例需要平台审核
			        	if($paymoney_givepercent != $oldinfo['paymoney_givepercent']){
			        		$info['paymoney_givepercent2'] = $paymoney_givepercent;
			        	}
                    }
				}

				if(getcustom('erp_nod')){
                    $info['erp_nod_api'] = $postinfo['erp_nod_api'];
                    $info['erp_nod_key'] = $postinfo['erp_nod_key'];
                }
                if(getcustom('yx_buyer_subsidy')){
                    if($bset['maidan_rate_set']==1 && $postinfo['feepercent']!=$oldinfo['feepercent']){
                        if($postinfo['feepercent']<$bset['maidan_rate_min'] || $postinfo['feepercent']>$bset['maidan_rate_max']){
                            return json(['status'=>0, 'msg'=>'抽成比例范围为'.$bset['maidan_rate_min'].' - '.$bset['maidan_rate_max']]);
                        }
                        $info['feepercent'] = $oldinfo['feepercent'];
                        $info['feepercent_audit'] = $postinfo['feepercent'];
                    }
                }
                if(getcustom('yx_new_score')){
                    //记录让利比例修改，需要后台审核后生效
                    $is_change = 0;
                    if($postinfo['newscore_ratio']!=$oldinfo['newscore_ratio'] ){
                        $is_change = 1;
                    }
                    if($bset['showMemberNewscoreRatio'] && $postinfo['member_newscore_ratio']!=$oldinfo['member_newscore_ratio'] ){
                        $is_change = 1;
                    }
                    if($bset['showBusinessNewscoreRatio'] && $postinfo['business_newscore_ratio']!=$oldinfo['business_newscore_ratio'] ){
                        $is_change = 1;
                    }
                    if($is_change==1){
                        $change_log = [
                            'aid' => aid,
                            'bid' => bid,
                            'newscore_ratio' => $postinfo['newscore_ratio'],
                            'member_newscore_ratio' => $postinfo['member_newscore_ratio']??$oldinfo['member_newscore_ratio'],
                            'business_newscore_ratio' => $postinfo['business_newscore_ratio']??$oldinfo['business_newscore_ratio'],
                            'old_newscore_ratio' => $oldinfo['newscore_ratio'],
                            'old_member_newscore_ratio' => $oldinfo['member_newscore_ratio'],
                            'old_business_newscore_ratio' => $oldinfo['business_newscore_ratio'],
                            'status' => 0,
                            'createtime' => time(),
                        ];
                        Db::name('newscore_business_log')->insert($change_log);
                    }
                }
                if(getcustom('shopbuy_sign')){
                    if($auth_data=='all' || in_array('ShopbuySign',$auth_data)){
                        $info['sign_contract'] = $postinfo['sign_contract'] ?? 0;
                        $info['sign_contract_content'] = $postinfo['sign_contract_content'] ?? '';
                        $info['sign_contract_template'] = $postinfo['sign_contract_template'] ?? '';
                        $info['sign_contract_name'] = $postinfo['sign_contract_name'] ?? '';
                        if($info['sign_contract'] == 1 && !$info['sign_contract_template']){
                            return json(['status'=>0, 'msg'=>'请上传合同模版']);
                        }
                    }
                }
				db('business')->where(['aid'=>aid,'id'=>bid])->update($info);
				\app\common\System::plog('系统设置');
				return json(['status'=>1,'msg'=>'设置成功','url'=>true]);
			}
            if(getcustom('active_score')){
                if($oldinfo && $oldinfo['shopactivescore_data']){
                    $shopactivescore_data = json_decode($oldinfo['shopactivescore_data'],true);
                    $html = '';
                    $color1 = $color2 = $color3 = '';
                    if($shopactivescore_data['shopactivescore_ratio'] != $oldinfo['shopactivescore_ratio']) $color1 = 'color:red';
                    if($shopactivescore_data['member_shopactivescore_ratio'] != $oldinfo['member_shopactivescore_ratio']) $color2 = 'color:red';
                    if($shopactivescore_data['business_shopactivescore_ratio'] != $oldinfo['business_shopactivescore_ratio']) $color3 = 'color:red';
                    $html .= '<div class="layui-input-inline layui-module-itemL"><div>总让利(%)</div><input disabled="true" class="layui-input" value="'.$shopactivescore_data['shopactivescore_ratio'].'" style="'.$color1.'"></div>';
                    $html .= '<div class="layui-input-inline layui-module-itemL"><div>消费者(%)</div><input disabled="true" class="layui-input" value="'.$shopactivescore_data['member_shopactivescore_ratio'].'" style="'.$color2.'"></div>';
                    $html .= '<div class="layui-input-inline layui-module-itemL"><div>商家(%)</div><input disabled="true" class="layui-input" value="'.$shopactivescore_data['business_shopactivescore_ratio'].'" style="'.$color3.'"></div>';
                    $oldinfo['shopactivescore_data_html'] = $html;
                }

                if($oldinfo && $oldinfo['maidanactivescore_data']){
                    $maidanactivescore_data = json_decode($oldinfo['maidanactivescore_data'],true);
                    $html = '';
                    $color1 = $color2 = $color3 = '';
                    if($maidanactivescore_data['maidanactivescore_ratio'] != $oldinfo['maidanactivescore_ratio']) $color1 = 'color:red';
                    if($maidanactivescore_data['member_maidanactivescore_ratio'] != $oldinfo['member_maidanactivescore_ratio']) $color2 = 'color:red';
                    if($maidanactivescore_data['business_maidanactivescore_ratio'] != $oldinfo['business_maidanactivescore_ratio']) $color3 = 'color:red';
                    $html .= '<div class="layui-input-inline layui-module-itemL"><div>总让利(%)</div><input disabled="true" class="layui-input" value="'.$maidanactivescore_data['maidanactivescore_ratio'].'" style="'.$color1.'"></div>';
                    $html .= '<div class="layui-input-inline layui-module-itemL"><div>消费者(%)</div><input disabled="true" class="layui-input" value="'.$maidanactivescore_data['member_maidanactivescore_ratio'].'" style="'.$color2.'"></div>';
                    $html .= '<div class="layui-input-inline layui-module-itemL"><div>商家(%)</div><input disabled="true" class="layui-input" value="'.$maidanactivescore_data['business_maidanactivescore_ratio'].'" style="'.$color3.'"></div>';
                    $oldinfo['maidanactivescore_data_html'] = $html;
                }
            }
            if(getcustom('member_dedamount')){
            	$oldinfo['paymoney_givepercent2_html'] = '';
            	if($oldinfo['paymoney_givepercent2'] >=0){
	                $html = '<div class="layui-input-inline" style="display:flex;align-items:center;width:230px"><div>让利比例：</div><input type="number" disabled="true" class="layui-input" value="'.$oldinfo['paymoney_givepercent2'].'" style="color:red;width:120px;margin:0 10px">%</div>';
	                $oldinfo['paymoney_givepercent2_html'] = $html;
            	}
            }
            //是否显示买单抽佣设置
            $show_maidan_rate = 0;
            if(getcustom('yx_buyer_subsidy')){
                //if($this->auth_data=='all' || in_array('SubsidyBusiness',$this->auth_data)){
                    $show_maidan_rate = $bset['maidan_rate_set'];
                //}
            }
            View::assign('show_maidan_rate',$show_maidan_rate);

            //是否显示新积分让利设置
            $showMemberNewscoreRatio  = $bset['showMemberNewscoreRatio']?:0;
            $showBusinessNewscoreRatio= $bset['showBusinessNewscoreRatio']?:0;
            View::assign('showMemberNewscoreRatio',$showMemberNewscoreRatio);
            View::assign('showBusinessNewscoreRatio',$showBusinessNewscoreRatio);
            //待审核的让利修改数据
            if(getcustom('yx_new_score')){
                $edit_log_last = db('newscore_business_log')->where('aid',aid)->where('bid',bid)->order('id desc')->find();
                View::assign('edit_log_last',$edit_log_last);
            }

			$info = $oldinfo;
            $info['invoice_type'] = explode(',',$info['invoice_type']);
			View::assign('info',$info);
			View::assign('bset',$bset);
            View::assign('admin',$admin);
            
            View::assign('BaseSet',$BaseSet);
            View::assign('RefundSet',$RefundSet);
            View::assign('OpenSet',$OpenSet);
            View::assign('PaySet',$PaySet);
			return View::fetch('syssetb');
		}
	}
	//操作日志
    public function plog(){
        if(input('param.op') == 'del'){
			$ids = input('post.ids/a');
			Db::name('plog')->where('aid',aid)->where('bid',bid)->where('id','in',$ids)->delete();
            \app\common\System::plog('删除操作日志');
			return json(['status'=>1,'msg'=>'删除成功']);
		}
		$userArr = db('admin_user')->where('aid',aid)->where('bid',bid)->column('un','id');
		//dump($userArr);
		if(request()->isAjax()){
			$page = input('get.page');
			$limit = input('get.limit');
			if(input('get.field') && input('get.order')){
				$order = input('get.field').' '.input('get.order');
			}else{
				$order = 'id desc';
			}
			$where = [];
			$where[] = ['aid','=',aid];
			$where[] = ['bid','=',bid];
			if($this->user['isadmin'] > 0){
				if(input('get.uid')) $where[] = ['uid','=',input('get.uid')];
			}else{
				$where[] = ['uid','=',uid];
			}
			if(input('get.remark')) $where[] = ['remark','like','%'.input('get.remark').'%'];
			$count = 0 + Db::name('plog')->where($where)->count();
			$data = Db::name('plog')->where($where)->page($page,$limit)->order($order)->select()->toArray();
			foreach($data as $k=>$v){
				$data[$k]['un'] = $userArr[$v['uid']];
			}
			return ['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data];
		}
		if($this->user['isadmin'] > 0){
			View::assign('userArr',$userArr);
		}
		return View::fetch();
    }
    public function plogexcel(){
        if(getcustom('plog_excel')){
            $page = input('param.page')?:1;
            $limit = input('param.limit')?:10;
            if(input('get.field') && input('get.order')){
                $order = input('get.field').' '.input('get.order');
            }else{
                $order = 'id desc';
            }
            $userArr = db('admin_user')->where('aid',aid)->where('bid',bid)->column('un','id');
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            if($this->user['isadmin'] > 0){
                if(input('get.uid')) $where[] = ['uid','=',input('get.uid')];
            }else{
                $where[] = ['uid','=',uid];
            }
            if(input('get.remark')) $where[] = ['remark','like','%'.input('get.remark').'%'];
            $count = 0 + Db::name('plog')->where($where)->count();
            $datalist = Db::name('plog')->where($where)->order($order)->page($page,$limit)->select()->toArray();
            $title = array('ID','账号','操作内容','操作时间','ip');
            $data = [];
            foreach($datalist as $k=>$v){
                $un = $userArr[$v['uid']];
                $data[] = [
                    $v['id'],
                    $un,
                    $v['remark'],
                    date('Y-m-d H:i:s',$v['createtime']),
                    $v['ip']
                ];
            }
            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data,'title'=>$title]);
            $this->export_excel($title,$data);
        }
    }
	//保持连接
	public function linked(){
		return json(['status'=>1]);
	}

	public function imgsearch(){
        if(getcustom('image_search')){
        	$where = [];
            $where[] = ['aid','=',aid];
            $image_search_business_switch = Db::name('baidu_set')->where('aid',aid)->where('bid',0)->value('image_search_business_switch');
            if($image_search_business_switch){
                $where[] = ['bid','=',bid];
            }else{
            	$where[] = ['bid','=',0];
            }
            if(request()->isPost()){
            	
	            $rs = Db::name('baidu_set')->where($where)->find();
                $info = input('post.info/a');
                if(bid > 0){
                	$info['image_search_business'] = 0;
                }
                if($rs){
                    Db::name('baidu_set')->where('aid',aid)->where('bid',bid)->update($info);
                }else{
                    $info['aid'] = aid;
                    $info['bid'] = bid;
                    Db::name('baidu_set')->insert($info);
                }

                \app\common\System::plog('图搜设置');
                return json(['status'=>1,'msg'=>'设置成功','url'=>true]);
            }

            $info = Db::name('baidu_set')->where($where)->find();
            $ainfo = Db::name('admin')->where('id',aid)->find();

            View::assign('info',$info);
            View::assign('ainfo',$ainfo);
            View::assign('bid',bid);
            return View::fetch();
        }
    }
    public function diylight(){
        if(getcustom('diy_light')){
            if(request()->isPost()){
                $rs = Db::name('diylight_set')->where('aid',aid)->where('mid',0)->find();
                $info = input('post.info/a');

                if($rs){
                    Db::name('diylight_set')->where('aid',aid)->where('mid',0)->update($info);
                }else{
                    $info['aid'] = aid;
                    Db::name('diylight_set')->insert($info);
                }

                \app\common\System::plog('配灯设置');
                return json(['status'=>1,'msg'=>'设置成功','url'=>true]);
            }
            $info = Db::name('diylight_set')->where('aid',aid)->where('mid',0)->find();

            View::assign('info',$info);
            return View::fetch();
        }
    }

    public function huidong()
    {
        $channel = 'huidong';
        if(request()->isPost()){
            $set = input('post.set/a');
            $post = input('post.');

            if($post['op'] == 'reset'){
                $update['appsecret'] = md5(rand(1,9999).uniqid());
                Db::name('open_app')->where('aid',aid)->where('channel',$channel)->update($update);
                return json(['status'=>1,'msg'=>'重置成功','url'=>true]);
            }

            $set = Db::name('admin_set')->where('aid',aid)->update(['huidong_status'=>$set['huidong_status'],'huidong_url'=>$set['huidong_url']]);

            \app\common\System::plog('企微设置');
            return json(['status'=>1,'msg'=>'设置成功','url'=>true]);
        }
        $info = Db::name('open_app')->where('aid',aid)->where('channel',$channel)->find();
        if(empty($info)){
            $appid = 'dianda'.rand(11,99).make_rand_code(5,10);
            if(Db::name('open_app')->where('appid',$appid)->count())
                $appid = 'dianda'.rand(11,99).make_rand_code(5,10);
            $info = [
                'aid'=>aid,
                'channel' => $channel,
                'appid' => $appid,
                'appsecret' => md5(rand(1,9999).uniqid()),
                'createtime'=>time()
            ];
            Db::name('open_app')->insert($info);
        }
        View::assign('domain',PRE_URL);
        View::assign('info',$info);
        $set = Db::name('admin_set')->where('aid',aid)->find();
        View::assign('set',$set);
        return View::fetch();
    }

    //随行付服务商
    public function sxpayset(){
        if(getcustom('admin_user_sxpay_merchant')){
            $payset = Db::name('sxpay_merchant_set')->where('aid',aid)->find();
            if(request()->isPost()){
                $postinfo = input('post.info/a');
                $postinfo['publicKey'] = preg_replace('/\s*/','',$postinfo['publicKey']);
                $postinfo['privateKey'] = preg_replace('/\s*/','',$postinfo['privateKey']);
                $info = [];
                $info['mchname'] = $postinfo['mchname'];
                $info['orgId'] = $postinfo['orgId'];
                $info['orgIdOne'] = $postinfo['orgIdOne'];
                $info['signType'] = $postinfo['signType'];
                $info['publicKey'] = $postinfo['publicKey'];
                $info['privateKey'] = $postinfo['privateKey'];
                $info['feepercent'] = $postinfo['feepercent'];
                $info['status'] = $postinfo['status'];
                $info['specifyWechatChannel'] = $postinfo['specifyWechatChannel'];
                if(!$payset){
                    $info['aid'] = aid;
                    $info['createtime'] = time();
                    Db::name('sxpay_merchant_set')->insert($info);
                }else{
                    Db::name('sxpay_merchant_set')->where('aid',aid)->update($info);
                }
                \app\common\System::plog('随行付服务商配置');
                return json(['status'=>1,'msg'=>'设置成功','url'=>true]);
            }
            if(empty($payset)){
                $payset = [
                    'status'=>0,
                ];
            }
            View::assign('info',$payset);
            return View::fetch();
        }
    }

    public function huifuset(){
        if(getcustom('pay_huifu_fenzhang') && getcustom('pay_huifu_fenzhang_backstage')){
            $aid = input('param.id/d');
            //汇付服务商
            $wxpayset = Db::name('admin_set')->where('aid',$aid)->field('huifuset')->find();
            if(request()->isPost()){
                $postinfo = input('post.info/a');
                Db::name('admin_set')->where('aid',$aid)->update(['huifuset'=>json_encode($postinfo)]);
                \app\common\System::plog('汇付斗拱服务商配置',1);
                return json(['status'=>1,'msg'=>'设置成功','url'=>true]);
            }
            View::assign('info',json_decode($wxpayset['huifuset'],true));
            View::assign('id',$aid);
            return View::fetch();
        }
    }

    public function replacedbstr(){
        if(getcustom('system_replacedbstr')){
            //批量替换数据库域名字符串
            if(request()->isPost()){
                set_time_limit(0);
                ini_set('memory_limit','-1');
                $startime = time();
                $tostr = input('post.tostr')?trim(input('post.tostr')):'';//新域名
                $fromstr = input('post.fromstr')?trim(input('post.fromstr')):'';//旧域名

                if($fromstr == '' || $tostr == '') return json(['status'=>0,'msg'=>'请填写替换域名']);
                //查询域名是否有小数点
                $pos = strpos($tostr,'.');
                if(!$pos)  return json(['status'=>0,'msg'=>'新域名格式不正确']);
                $tostrArr = explode('.',$tostr);
                if(empty($tostrArr[0]) || empty($tostrArr[1])) return json(['status'=>0,'msg'=>'新域名格式不正确']);

                $pos2 = strpos($fromstr,'.');
                if(!$pos2)  return json(['status'=>0,'msg'=>'旧域名格式不正确']);
                $fromstrArr = explode('.',$fromstr);
                if(empty($fromstrArr[0]) || empty($fromstrArr[1])) return json(['status'=>0,'msg'=>'旧域名格式不正确']);


                $resstrinfo = Db::name('admin_set_replacedbstr')->where('aid',aid)->find();
                if($resstrinfo){
                    $res = Db::name('admin_set_replacedbstr')->where('aid',aid)->update(['tostr'=>$tostr,'fromstr'=>$fromstr]);
                }else{
                    Db::name('admin_set_replacedbstr')->insert(['aid'=>aid,'tostr'=>$tostr,'fromstr'=>$fromstr]);
                }
                \app\common\System::plog('新域名：'.$tostr.'，替换旧域名：'.$fromstr);

                $tables = Db::query("show tables from `".\think\facade\Config::get('database.connections.mysql.database')."`");
                foreach($tables as $k=>$v){
                    $table = array_values($v)[0];//数据库名称
                    //不替换ddwx_admin、ddwx_access_token、ddwx_plog、admin_set_replacedbstr表，此表无aid，或不可替换
                    if($table != 'ddwx_admin' && $table != 'ddwx_access_token' && $table != 'ddwx_plog' && $table != 'ddwx_admin_set_replacedbstr'){
                        $fields = Db::query("SHOW COLUMNS FROM `{$table}`");//字段名称
                        $upsqls = [];//此数据表要替换的数据表字段数据集
                        $hasaid = false;//查询是否存在aid字段，及是否存在varchar、text 、longtext字段，不存在不替换
                        foreach($fields as $field){
                            //查询是否存在aid字段
                            if($field['Field'] == 'aid'){
                                $hasaid = true;
                                //是否存在varchar、text 、longtext字段
	                            if(strpos($field['Type'],'varchar') !== false || strpos($field['Type'],'text') !== false || strpos($field['Type'],'longtext') !== false){
	                                $fieldname = $field['Field'];
	                                $upsqls[] = "update `{$table}` set `{$fieldname}`=replace(`$fieldname`,'{$fromstr}','{$tostr}') where aid = ".aid." and `{$fieldname}` like '%{$fromstr}%'";
	                            }
                            }
                        }
                        if($hasaid){
                            foreach($upsqls as $upsql){
                                Db::execute($upsql);
                            }
                        }
                    }
                }
                $usetime = time() - $startime;
                return json(['status'=>1,'msg'=>'替换完成 用时'.$usetime.'秒','url'=>true]);
            }
        }
    }
}

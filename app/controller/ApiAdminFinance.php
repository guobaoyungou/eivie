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

//管理员中心 - 财务管理
namespace app\controller;
use pay\wechatpay\WxPayV3;
use think\facade\Db;
class ApiAdminFinance extends ApiAdmin
{
	//财务管理
	function index(){
		$aid = aid;
		$lastDayStart = strtotime(date('Y-m-d',time()-86400));
		$lastDayEnd = $lastDayStart + 86400;
		$thisMonthStart = strtotime(date('Y-m-1'));
		$nowtime = time();
		$info = [];

		//退款金额
		$where   = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',bid];
        if($this->user['mdid']){
        	$where[] = ['mdid','=',$this->user['mdid']];
        }
        $where[] = ['refund_status','=',2];
		$info['refundCount'] = Db::name('shop_order')->where($where)->sum('refund_money');

		$where   = [];
        $where[] = ['ro.aid','=',aid];
        $where[] = ['ro.bid','=',bid];
        if($this->user['mdid']){
        	$where[] = ['o.mdid','=',$this->user['mdid']];
        }
        $where[] = ['ro.refund_status','=',2];
		$info['refundLastDayCount']   = Db::name('shop_refund_order')->alias('ro')->join('shop_order o','o.id = ro.orderid')->where($where)->where('ro.refund_time','>=',$lastDayStart)->where('ro.refund_time','<',$lastDayEnd)->sum('ro.refund_money');
		$info['refundThisMonthCount'] = Db::name('shop_refund_order')->alias('ro')->join('shop_order o','o.id = ro.orderid')->where($where)->where('ro.refund_time','>=',$thisMonthStart)->where('ro.refund_time','<',$nowtime)->sum('ro.refund_money');
		$info['show_tixian'] = 0;
		if(bid == 0){
			//收款金额
			if($this->user['mdid']){
				// 普通订单
				$where   = [];
		        $where[] = ['aid','=',aid];
		        $where[] = ['bid','=',bid];
		        $where[] = ['mdid','=',$this->user['mdid']];
		        //$where[] = ['status','in',[1,2,3]];
		        $where[] = ['status','in',[1,2,3,4]];
		        $where[] = ['paytypeid','not in',[1,4]];
				$shop_order_money = Db::name('shop_order')->where($where)->sum('totalprice');
		        $where[] = ['paytime','between',[$lastDayStart,$lastDayEnd]];
				$shop_order_money_day = Db::name('shop_order')->where($where)->sum('totalprice');
				unset($where[5]);
		        $where[] = ['paytime','between',[$thisMonthStart,$nowtime]];
				$shop_order_money_month = Db::name('shop_order')->where($where)->sum('totalprice');
				// 快速买单
				$where   = [];
		        $where[] = ['aid','=',aid];
		        $where[] = ['bid','=',bid];
		        $where[] = ['mdid','=',$this->user['mdid']];
		        //$where[] = ['status','=',1];
		        $where[] = ['status','in',[1,2,3,4]];
		        $where[] = ['paytypeid','not in',[1,4]];
				$maidan_order_money = Db::name('maidan_order')->where($where)->sum('paymoney');
		        $where[] = ['paytime','between',[$lastDayStart,$lastDayEnd]];
				$maidan_order_money_day = Db::name('maidan_order')->where($where)->sum('paymoney');
				unset($where[5]);
		        $where[] = ['paytime','between',[$thisMonthStart,$nowtime]];
				$maidan_order_money_month = Db::name('maidan_order')->where($where)->sum('paymoney');

				$info['wxpayCount'] = $shop_order_money + $maidan_order_money;
				$info['wxpayCount'] = round($info['wxpayCount'],2);

				$info['wxpayLastDayCount'] = $shop_order_money_day + $maidan_order_money_day;
				$info['wxpayLastDayCount'] = round($info['wxpayLastDayCount'],2);

				$info['wxpayThisMonthCount'] = $shop_order_money_month + $maidan_order_money_month;
				$info['wxpayThisMonthCount'] = round($info['wxpayThisMonthCount'],2);

				// 门店提现
				if(getcustom('mendian_hexiao_givemoney')){
					$info['show_tixian'] = 1;
					$where = [];
					$where[] = ['aid','=',aid];
					$where[] = ['bid','=',bid];
					$where[] = ['mdid','=',$this->user['mdid']];
					$where[] = ['status','=',3];
					$mendian_withdraw_money = Db::name('mendian_withdrawlog')->where($where)->sum('money');
		        	$where[] = ['createtime','between',[$lastDayStart,$lastDayEnd]];
		        	$mendian_withdraw_money_day = Db::name('mendian_withdrawlog')->where($where)->sum('money');
		        	unset($where[4]);
		        	$where[] = ['createtime','between',[$thisMonthStart,$nowtime]];
		        	$mendian_withdraw_money_month = Db::name('mendian_withdrawlog')->where($where)->sum('money');

		        	$info['withdrawCount'] = $mendian_withdraw_money;
		        	$info['withdrawCount'] = round($info['withdrawCount'],2);
		        	$info['withdrawLastDayCount'] = $mendian_withdraw_money_day;
		        	$info['withdrawLastDayCount'] = round($info['withdrawLastDayCount'],2);
		        	$info['withdrawThisMonthCount'] = $mendian_withdraw_money_month;
		        	$info['withdrawThisMonthCount'] = round($info['withdrawThisMonthCount'],2);
				}

			}else{
				$info['wxpayCount'] = Db::name('payorder')->where('aid',aid)->where('bid',bid)->where('status',1)->where('paytypeid','not in','1,4')->where('type','<>','daifu')->sum('money');
				$info['wxpayCount'] = round($info['wxpayCount'],2);
				$info['wxpayLastDayCount'] = Db::name('payorder')->where('aid',aid)->where('bid',bid)->where('status',1)->where('paytypeid','not in','1,4')->where('type','<>','daifu')->where('paytime','>=',$lastDayStart)->where('paytime','<',$lastDayEnd)->sum('money');
				$info['wxpayLastDayCount'] = round($info['wxpayLastDayCount'],2);
				$info['wxpayThisMonthCount'] = 0 + Db::name('payorder')->where('aid',aid)->where('bid',bid)->where('status',1)->where('paytypeid','not in','1,4')->where('paytime','>=',$thisMonthStart)->where('paytime','<',$nowtime)->sum('money');
				$info['wxpayThisMonthCount'] = round($info['wxpayThisMonthCount'],2);

				//提现金额
				$info['show_tixian'] = 1;
				$info['withdrawCount'] = Db::name('member_withdrawlog')->where('aid',aid)->where('status',3)->sum('money') + Db::name('member_commission_withdrawlog')->where('aid',aid)->where('status',3)->sum('money');
				$info['withdrawCount'] = round($info['withdrawCount'],2);
				$info['withdrawLastDayCount'] = Db::name('member_withdrawlog')->where('aid',aid)->where('status',3)->where('createtime','>=',$lastDayStart)->where('createtime','<',$lastDayEnd)->sum('money') + Db::name('member_commission_withdrawlog')->where('aid',aid)->where('status',3)->where('createtime','>=',$lastDayStart)->where('createtime','<',$lastDayEnd)->sum('money');
				$info['withdrawLastDayCount'] = round($info['withdrawLastDayCount'],2);
				$info['withdrawThisMonthCount'] = Db::name('member_withdrawlog')->where('aid',aid)->where('status',3)->where('createtime','>=',$thisMonthStart)->where('createtime','<',$nowtime)->sum('money') + Db::name('member_commission_withdrawlog')->where('aid',aid)->where('status',3)->where('createtime','>=',$thisMonthStart)->where('createtime','<',$nowtime)->sum('money');
				$info['withdrawThisMonthCount'] = round($info['withdrawThisMonthCount'],2);
			}
			
		}else{
			//收款金额
			if($this->user['mdid']){
				// 普通订单
				$where   = [];
		        $where[] = ['aid','=',aid];
		        $where[] = ['bid','=',bid];
		        $where[] = ['mdid','=',$this->user['mdid']];
//		        $where[] = ['status','in',[1,2,3]];
//		        $where[] = ['paytypeid','<>',4];
                $where[] = ['status','in',[1,2,3,4]];
                $where[] = ['paytypeid','not in',[1,4]];
				$shop_order_money = Db::name('shop_order')->where($where)->sum('totalprice');
		        $where[] = ['paytime','between',[$lastDayStart,$lastDayEnd]];
				$shop_order_money_day = Db::name('shop_order')->where($where)->sum('totalprice');
				unset($where[5]);
		        $where[] = ['paytime','between',[$thisMonthStart,$nowtime]];
				$shop_order_money_month = Db::name('shop_order')->where($where)->sum('totalprice');
				// 快速买单
				$where   = [];
		        $where[] = ['aid','=',aid];
		        $where[] = ['bid','=',bid];
		        $where[] = ['mdid','=',$this->user['mdid']];
//		        $where[] = ['status','=',1];
//		        $where[] = ['paytypeid','<>',4];
                $where[] = ['status','in',[1,2,3,4]];
                $where[] = ['paytypeid','not in',[1,4]];
				$maidan_order_money = Db::name('maidan_order')->where($where)->sum('paymoney');
		        $where[] = ['paytime','between',[$lastDayStart,$lastDayEnd]];
				$maidan_order_money_day = Db::name('maidan_order')->where($where)->sum('paymoney');
				unset($where[5]);
		        $where[] = ['paytime','between',[$thisMonthStart,$nowtime]];
				$maidan_order_money_month = Db::name('maidan_order')->where($where)->sum('paymoney');

				$info['wxpayCount'] = $shop_order_money + $maidan_order_money;
				$info['wxpayCount'] = round($info['wxpayCount'],2);

				$info['wxpayLastDayCount'] = $shop_order_money_day + $maidan_order_money_day;
				$info['wxpayLastDayCount'] = round($info['wxpayLastDayCount'],2);

				$info['wxpayThisMonthCount'] = $shop_order_money_month + $maidan_order_money_month;
				$info['wxpayThisMonthCount'] = round($info['wxpayThisMonthCount'],2);
			}else{
                //平台收款不统计余额支付，商家收款要统计余额支付
				//$info['wxpayCount'] = Db::name('payorder')->where('aid',aid)->where('bid',bid)->where('status',1)->where('paytypeid','<>','4')->sum('money');
                $info['wxpayCount'] = Db::name('payorder')->where('aid',aid)->where('bid',bid)->where('status',1)->where('paytypeid','<>','4')->where('type','<>','daifu')->sum('money');
				$info['wxpayCount'] = round($info['wxpayCount'],2);
				$info['wxpayLastDayCount'] = Db::name('payorder')->where('aid',aid)->where('bid',bid)->where('status',1)->where('paytypeid','<>','4')->where('paytime','>=',$lastDayStart)->where('paytime','<',$lastDayEnd)->sum('money');
				$info['wxpayLastDayCount'] = round($info['wxpayLastDayCount'],2);
				$info['wxpayThisMonthCount'] = 0 + Db::name('payorder')->where('aid',aid)->where('bid',bid)->where('status',1)->where('paytypeid','<>','4')->where('paytime','>=',$thisMonthStart)->where('paytime','<',$nowtime)->sum('money');
				$info['wxpayThisMonthCount'] = round($info['wxpayThisMonthCount'],2);
			}
			
		}
        $moeny_weishu = 2;
        if(getcustom('fenhong_money_weishu')){
            $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
            $moeny_weishu = $moeny_weishu?$moeny_weishu:2;
        }
		$commissiontotal =    Db::name('member')->where('aid',aid)->sum('totalcommission');
		$info['commissiontotal'] =  dd_money_format($commissiontotal,$moeny_weishu);
		$commission =   Db::name('member')->where('aid',aid)->sum('commission');
        $info['commission'] = dd_money_format($commission,$moeny_weishu) ;
		$info['commissionwithdraw'] = Db::name('member_commission_withdrawlog')->where('aid',aid)->where('status',3)->sum('txmoney');

		$rdata = [];
		$rdata['status'] = 1;

		//余额宝收益
		$rdata['showyuebao_moneylog'] = false;
		//余额宝提现
		$rdata['showyuebao_withdrawlog'] = false;
		if(getcustom('plug_yuebao')){
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
			if($auth_data=='all'){
				//余额宝收益
				$rdata['showyuebao_moneylog'] = true;
				//余额宝提现
				$rdata['showyuebao_withdrawlog'] = true;
			}else{
				if(in_array('Yuebao/*',$auth_data)){
					//余额宝收益
					$rdata['showyuebao_moneylog'] = true;
					//余额宝提现
					$rdata['showyuebao_withdrawlog'] = true;
				}else{
					if(in_array('Yuebao/moneylog',$auth_data)){
						//余额宝收益
						$rdata['showyuebao_moneylog'] = true;
					}
					if(in_array('Yuebao/withdrawlog',$auth_data)){
						//余额宝提现
						$rdata['showyuebao_withdrawlog'] = true;
					}
				}
			}
			$info['yuebaowithdrawCount'] = Db::name('member_yuebao_withdrawlog')->where('aid',aid)->where('status',3)->sum('money');
			$info['withdrawCount'] += round($info['yuebaowithdrawCount'],2);

			$info['yuebaowithdrawLastDayCount'] = Db::name('member_yuebao_withdrawlog')->where('aid',aid)->where('status',3)->where('createtime','>=',$lastDayStart)->where('createtime','<',$lastDayEnd)->sum('money');
			$info['withdrawLastDayCount'] += round($info['yuebaowithdrawLastDayCount'],2);

			$info['yuebaowithdrawThisMonthCount'] = Db::name('member_yuebao_withdrawlog')->where('aid',aid)->where('status',3)->where('createtime','>=',$thisMonthStart)->where('createtime','<',$nowtime)->sum('money');
			$info['withdrawThisMonthCount'] += round($info['yuebaowithdrawThisMonthCount'],2);
		}

		$rdata['bid'] = bid;
		$rdata['mdid'] = $this->user['mdid'];
		$rdata['showmdmoney'] = 0;
		if(getcustom('mendian_hexiao_givemoney') && $this->user['mdid'] > 0){
			$rdata['showmdmoney'] = 1;
		}
		$rdata['showbscore'] = false;
		if((getcustom('business_selfscore') || getcustom('business_score_withdraw') || getcustom('business_score_jiesuan')) && bid > 0){
			$bset = Db::name('business_sysset')->where('aid',aid)->find();
			if($bset['business_selfscore'] == 1){
				$rdata['showbscore'] = true;
				$info['score'] = Db::name('business')->where('id',bid)->value('score');
			}
		}
		$rdata['showcouponmoney'] = false;
		if(getcustom('business_canuseplatcoupon')){
			$rdata['showcouponmoney'] = true;
		}
        $show = [];
        if(getcustom('admin_m_show_scorelog')){
            $show['scorelog'] = true;
        }
		$show['finance'] = true;
		if(getcustom('mendian_apply')){
			if($this->user['mdid'] > 0){
			    $show['finance'] = false;
			}
		}
        if(getcustom('mendian_usercenter')){
            if($this->user['mdid'] > 0){
                $show['finance'] = true;
            }
        }
        $show['showdepositlog'] = false;
        if(getcustom('business_deposit')){
            $show['showdepositlog'] = true;
        }
        //默认全部展示
        $mobile_index_data = ['all'];
        if (getcustom('plug_siming')) {
          $mobile_index_data = Db::name("admin")->where('id', aid)->value('mobile_index_data');
          $mobile_index_data = explode(',', $mobile_index_data);
        }
        if(getcustom('mendian_hexiao_givemoney')){
            $show['finance'] = true;
            $mobile_index_data = ['total_receive','total_refund'];
        }
        $rdata['index_data'] = $mobile_index_data;

		$show['show_salesquota'] = false;
		if(getcustom('business_sales_quota')){
			if($this->user['bid'] > 0){
				$business =  Db::name('business')->field('sales_quota,total_sales_quota')->where('id',$this->user['bid'])->find();
			    $show['show_salesquota'] = true;
				$info['sales_quota'] = $business['sales_quota'];
				$info['total_sales_quota'] = $business['total_sales_quota'];
			}
		}

        $show['bonus_pool_gold'] = false;
        if(getcustom('bonus_pool_gold') && bid>0){
            //奖金池
            $set = Db::name('bonuspool_gold_set')->where('aid',aid)->find();
            if($set['status']==1){
                $show['bonus_pool_gold'] = true;
                $info['gold'] = Db::name('business')->where('id',bid)->value('gold');
                $info['gold_price'] = Db::name('bonuspool_gold_set')->where('aid',aid)->value('gold_price');
            }
        }

        $show['subsidy_score'] = false;
        if(getcustom('yx_buyer_subsidy') && bid>0){
            $ptuser = Db::name('admin_user')->where('aid',aid)->where('isadmin','>',0)->where('bid','=',0)->field('auth_type,auth_data')->find();
            if($ptuser['auth_type']==1 || strpos($ptuser['auth_data'],'Subsidy')!==false){
                $show['subsidy_score'] = true;
                $info['subsidy_score'] = Db::name('business')->where('id',bid)->value('subsidy_score');
            }
        }
        // 菜单权限
        if($this->user['auth_type']==0){
            $auth_data = json_decode($this->user['auth_data'],true);
            $auth_path = [];
            foreach($auth_data as $v){
                $auth_path = array_merge($auth_path,explode(',',$v));
            }
            $auth_data = $auth_path;
        }else{
            $auth_data = ['all'];
        }
        $show['huifu_settllement'] = false;
      
        if(getcustom('mobile_admin_huifu_settllement') && getcustom('pay_huifu') && getcustom('pay_huifu_fenzhang')){
            if(bid >0){
                $huifu_business = Db::name('business')->where('aid',aid)->where('id',bid)->field('huifu_business_status,huifu_id,money')->find();
                $huifu_business_status = $huifu_business['huifu_business_status'];
                $business_huifu_id = $huifu_business['huifu_id'];
                if($huifu_business_status){
                    $show['huifu_settllement'] = true;

                    $huifuset = Db::name('sysset')->where('name','huifuset')->find();
                    $appinfo = [];
                    if($huifuset){
                        $huifu_appinfo = json_decode($huifuset['value'],true);
                        $appinfo['huifu_sys_id'] = $huifu_appinfo['huifu_sys_id'];//渠道商的huifu_id
                        $appinfo['huifu_id'] = $business_huifu_id;//商户的huifu_id
                        $appinfo['huifu_product_id'] = $huifu_appinfo['huifu_product_id'];
                        $appinfo['huifu_merch_private_key'] = $huifu_appinfo['huifu_merch_private_key'];
                        $appinfo['huifu_public_key'] =$huifu_appinfo['huifu_public_key'];
                    }
                    $huifu = new \app\custom\Huifu($appinfo, aid, bid);
                    $balance_rs = $huifu ->getAcctpaymentBalance($business_huifu_id);
                    $balance_money = 0;
                    if($balance_rs['status'] ==1){
                        $balance_money = $balance_rs['balance_money'];
                    }
                    $info['balance_money'] = $balance_money;
                    $info['money'] = $huifu_business['money'];
                }
                $show['show_txset'] = true;
            }
        }

        if(getcustom('business_withdraw_cash_mobile') && bid>0){
            //提现现金支付记录
            $show['business_withdraw_cash_mobile'] = true;
        }

        if(getcustom('business_withdraw_invoice_mobile') && bid>0){
            //提现发票
            $show['business_withdraw_invoice_mobile'] = true;
        }
        
        $rdata['show'] = $show;
		$rdata['info'] = $info;
       
        if(getcustom('finance_trade_report')){
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
            if($auth_data =='all' || in_array('Payorder/tradereport',$auth_data)){
                $this->auth_data['tradereport'] = true;
            }
        }

        $rdata['auth_data'] = $this->auth_data;
        $rdata['wxauth_data'] = $this->user['wxauth_data'] ? json_decode($this->user['wxauth_data'],true) : [];

   
        $rdata['auth_data_menu'] = $auth_data;

		return $this->json($rdata);
	}
	//余额充值记录
	function rechargelog(){
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$order = 'id desc';
		$where = [];
		$where[] = ['recharge_order.aid','=',aid];
        if(!getcustom('money_recharge_transfer')){
            $where[] = ['recharge_order.status','=',1];
        }else{
            $where[] = ['recharge_order.paytype','<>','null'];
        }
        if(getcustom('recharge_use_mendian')){
            if($this->user['mdid']){
                $where[] = ['recharge_order.mdid','=',$this->user['mdid']];
            }
        }
		if(input('param.keyword')) $where[] = ['member.nickname','like','%'.trim(input('param.keyword')).'%'];
		$datalist = Db::name('recharge_order')->alias('recharge_order')->field('member.nickname,member.headimg,recharge_order.*')->join('member member','member.id=recharge_order.mid')->where($where)->page($pagenum,$pernum)->order($order)->select()->toArray();
		if($pagenum==1){
			$count = 0 + Db::name('recharge_order')->alias('recharge_order')->field('member.nickname,member.headimg,recharge_order.*')->join('member member','member.id=recharge_order.mid')->where($where)->count();
		}
        foreach ($datalist as &$v){
            if(getcustom('money_recharge_transfer')){
                $v['money_recharge_transfer'] = true;
                $v['payorder_check_status'] = Db::name('payorder')->where('aid',aid)->where('type','recharge')->where('orderid',$v['id'])->value('check_status');
            }

            if($v['status']==1){
                $v['status_name'] = '充值成功';
            }else{
                if(getcustom('money_recharge_transfer') && $v['paytypeid'] == 5 && $v['paytype'] != '随行付支付'){
                    if($v['transfer_check'] == 1){
                        if($v['payorder_check_status'] == 2){
                            $v['status_name'] = '凭证被驳回';
                        }else if($v['payorder_check_status'] == 1){
                            $v['status_name'] = '审核通过';
                        }else{
                            $v['status_name'] = '凭证待审核';
                        }
                    }else if($v['transfer_check'] == -1){
                        $v['status_name'] = '已驳回';
                    }else {
                        $v['status_name'] = '待审核';
                    }
                }else{
                    $v['status_name'] = '充值失败';
                }
            }
            if(getcustom('maidan_use_mendian')){
                $mendian_name = Db::name('mendian')->where('aid',aid)->where('id',$v['mdid'])->value('name');
                $v['mendian_name'] =  $mendian_name??'';
            }
        }
        $today_rechargemoney = 0;
        if(getcustom('recharge_use_mendian')){
            $twhere = [];
            $twhere[] = ['aid','=',aid];
            $twhere[] = ['status','=',1];
            $start_time = strtotime(date('Y-m-d 00:00:01'));
            $end_time = strtotime(date('Y-m-d 23:59:59'));
            if($this->user['mdid']){
                $twhere[] = ['mdid','=',$this->user['mdid']];
            }
            $twhere[] = ['createtime','between',[$start_time,$end_time]];
            $today_rechargemoney = Db::name('recharge_order')->where($twhere)->sum('money'); 
        }
		return $this->json(['status'=>1,'count'=>$count,'today_rechargemoney' =>$today_rechargemoney ,'data'=>$datalist]);
	}
    public function getrechargeorderdetail()
    {
        $orderid = input('param.orderid');
        $order = Db::name('recharge_order')->where('aid',aid)->where('id',$orderid)->find();
        $payorder = Db::name('payorder')->where('id',$order['payorderid'])->where('type','recharge')->where('aid',aid)->find();
        if($order['paytypeid'] == 5) {
            if($payorder) {
                if($payorder['check_status'] === 0) {
                    $payorder['check_status_label'] = '待审核';
                }elseif($payorder['check_status'] == 1) {
                    $payorder['check_status_label'] = '通过';
                }elseif($payorder['check_status'] == 2) {
                    $payorder['check_status_label'] = '驳回';
                }else{
                    $payorder['check_status_label'] = '未上传';
                }
                if($payorder['paypics']) {
                    $payorder['paypics'] = explode(',', $payorder['paypics']);
                    foreach ($payorder['paypics'] as $item) {
                        $payorder['paypics_html'] .= '<img src="'.$item.'" width="200" onclick="preview(this)"/>';
                    }
                }
            }
        }
        return $this->json(['status'=>1,'order'=>$order,'payorder' => $payorder]);
    }
    //转账审核
    public function transferCheck(){
        if(getcustom('money_recharge_transfer')){
            $orderid = input('post.orderid/d');
            $st = input('post.st/d');

            $order = Db::name('recharge_order')->where('id',$orderid)->where('aid',aid)->field('id,status')->find();
            if($order['status']!=0){
                return $this->json(['status'=>0,'msg'=>'该订单状态不允许审核']);
            }

            if($st==1){
                $up = Db::name('recharge_order')->where('id',$orderid)->where('aid',aid)->update(['transfer_check'=>1]);
                if($up){
                    \app\common\System::plog('余额充值订单转账审核驳回'.$orderid);
                    return $this->json(['status'=>1,'msg'=>'审核通过']);
                }else{
                    return $this->json(['status'=>0,'msg'=>'操作失败']);
                }
            }else{
                $up = Db::name('recharge_order')->where('id',$orderid)->where('aid',aid)->update(['transfer_check'=>-1]);
                if($up){
                    \app\common\System::plog('余额充值订单转账审核通过'.$orderid);
                    return $this->json(['status'=>1,'msg'=>'转账已驳回']);
                }else{
                    return $this->json(['status'=>0,'msg'=>'操作失败']);
                }
            }
        }
    }
    //付款审核
    public function payCheck(){
        if(getcustom('money_recharge_transfer')){
            $orderid = input('post.orderid/d');
            $st = input('post.st/d');
            $remark = input('post.remark');
            $order = Db::name('recharge_order')->where('id',$orderid)->where('aid',aid)->find();

            if($order['status']!=0){
                return $this->json(['status'=>0,'msg'=>'该订单状态不允许审核付款']);
            }

            if($st==2){
                $check_data = [];
                $check_data['check_status'] = 2;
                if($remark){
                    $check_data['check_remark'] = $remark;
                }
                Db::name('payorder')->where('id',$order['payorderid'])->where('aid',aid)->where('type','recharge')->update($check_data);
                \app\common\System::plog('余额充值订单付款审核驳回'.$orderid);
                return $this->json(['status'=>1,'msg'=>'付款已驳回']);
            }elseif($st == 1){
                $check_data = [];
                $check_data['check_status'] = 1;
                if($remark){
                    $check_data['check_remark'] = $remark;
                }
                \app\model\Payorder::payorder($order['payorderid'],t('转账汇款'),5,'');
                Db::name('payorder')->where('id',$order['payorderid'])->where('type','recharge')->where('aid',aid)->update($check_data);

                Db::name('recharge_order')->where('id',$orderid)->where('aid',aid)->update(['status'=>1,'paytime' => time()]);

                \app\common\System::plog('余额充值订单付款审核通过'.$orderid);
                return $this->json(['status'=>1,'msg'=>'审核通过']);
            }
        }
    }
	//余额明细
	function moneylog(){
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$order = 'id desc';
		$pernum = 20;
		$where = [];
		$where[] = ['member_moneylog.aid','=',aid];
		if(input('param.keyword')) $where[] = ['member.nickname','like','%'.trim(input('param.keyword')).'%'];
		if(input('?param.status') && input('param.status')!=='') $where[] = ['member_moneylog.status','=',input('param.status')];
		$datalist = Db::name('member_moneylog')->alias('member_moneylog')->field('member.nickname,member.headimg,member_moneylog.*')->join('member member','member.id=member_moneylog.mid')->where($where)->page($pagenum,$pernum)->order($order)->select()->toArray();
		if($pagenum==1){
			$count = 0 + Db::name('member_moneylog')->alias('member_moneylog')->field('member.nickname,member.headimg,member_moneylog.*')->join('member member','member.id=member_moneylog.mid')->where($where)->count();
		}
		return $this->json(['status'=>1,'count'=>$count,'data'=>$datalist]);
	}
	//佣金明细
	function commissionlog(){
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$order = 'id desc';
		$pernum = 20;
		$where = [];
		$where[] = ['member_commissionlog.aid','=',aid];
		if(input('param.keyword')) $where[] = ['member.nickname','like','%'.trim(input('param.keyword')).'%'];
		if(input('?param.status') && input('param.status')!=='') $where[] = ['member_commissionlog.status','=',input('param.status')];
		$datalist = Db::name('member_commissionlog')->alias('member_commissionlog')->field('member.nickname,member.headimg,member_commissionlog.*')->join('member member','member.id=member_commissionlog.mid')->where($where)->page($pagenum,$pernum)->order($order)->select()->toArray();
		if($pagenum==1){
			$count = 0 + Db::name('member_commissionlog')->alias('member_commissionlog')->field('member.nickname,member.headimg,member_commissionlog.*')->join('member member','member.id=member_commissionlog.mid')->where($where)->count();
		}
		return $this->json(['status'=>1,'count'=>$count,'data'=>$datalist]);
	}

    //明细
    function scoreloglist(){
        $pagenum = input('post.pagenum');
        if(!$pagenum) $pagenum = 1;
        $order = 'id desc';
        $pernum = 20;
        $where = [];
        $where[] = ['member_scorelog.aid','=',aid];
        if(bid > 0){
            $where[] = ['member_scorelog.bid','=',bid];
        }
        if(input('param.keyword')) $where[] = ['member.nickname','like','%'.trim(input('param.keyword')).'%'];
        if(input('?param.status') && input('param.status')!=='') $where[] = ['member_scorelog.status','=',input('param.status')];
        $datalist = Db::name('member_scorelog')->alias('member_scorelog')->field('member.nickname,member.headimg,member_scorelog.*')->join('member member','member.id=member_scorelog.mid')->where($where)->page($pagenum,$pernum)->order($order)->select()->toArray();
        if($pagenum==1){
            $count = 0 + Db::name('member_scorelog')->alias('member_scorelog')->field('member.nickname,member.headimg,member_scorelog.*')->join('member member','member.id=member_scorelog.mid')->where($where)->count();
        }
        return $this->json(['status'=>1,'count'=>$count,'data'=>$datalist]);
    }
	//余额提现记录
	function withdrawlog(){
		$pagenum = input('post.pagenum');
		$st = input('post.st');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['withdrawlog.aid','=',aid];
		if($st == 'all'){

		}elseif($st == '0'){
			$where[] = ['status','=',0];
		}elseif($st == '1'){
			$where[] = ['status','=',1];
		}elseif($st == '2'){
			$where[] = ['status','=',2];
		}elseif($st == '3'){
			$where[] = ['status','=',3];
		}

		if(input('param.keyword')) $where[] = ['member.nickname','like','%'.trim(input('param.keyword')).'%'];
		if(input('?param.status') && input('param.status')!=='') $where[] = ['withdrawlog.status','=',input('param.status')];
		$datalist = Db::name('member_withdrawlog')->alias('withdrawlog')->field('member.nickname,member.headimg,withdrawlog.*')->join('member member','member.id=withdrawlog.mid')->where($where)->page($pagenum,$pernum)->order('withdrawlog.id desc')->select()->toArray();
		if($pagenum==1){
			$count = 0 + Db::name('member_withdrawlog')->alias('withdrawlog')->field('member.nickname,member.headimg,withdrawlog.*')->join('member member','member.id=withdrawlog.mid')->where($where)->count();
		}
		return $this->json(['status'=>1,'count'=>$count,'data'=>$datalist]);
	}
	//余额提现明细
	function withdrawdetail(){
		$id = input('param.id/d');
		$info = Db::name('member_withdrawlog')->where(['aid'=>aid,'id'=>$id])->find();
		$member = Db::name('member')->where(['id'=>$info['mid']])->find();
		$info['nickname'] = $member['nickname'];
		$info['headimg'] = $member['headimg'];
		return $this->json(['status'=>1,'info'=>$info]);
	}
	//余额提现审核通过
	function widthdrawpass(){
		$id = input('post.id/d');
		$info = Db::name('member_withdrawlog')->where(['aid'=>aid,'id'=>$id])->update(['status'=>1,'reason'=>'']);
		return $this->json(['status'=>1,'msg'=>'操作成功']);
	}
	//余额提现审核不通过
	function widthdrawnopass(){
		$id = input('post.id/d');
		$reason = input('post.reason');
		Db::name('member_withdrawlog')->where(['aid'=>aid,'id'=>$id])->update(['status'=>2,'reason'=>$reason]);
		$info = Db::name('member_withdrawlog')->where(['aid'=>aid,'id'=>$id])->find();
		\app\common\Member::addmoney(aid,$info['mid'],$info['txmoney'],t('余额').'提现返还');
		//提现失败通知
		$tmplcontent = [];
		$tmplcontent['first'] = '您的提现申请被商家驳回，可与商家协商沟通。';
		$tmplcontent['remark'] = $reason.'，请点击查看详情~';
		$tmplcontent['money'] = (string) $info['txmoney'];
		$tmplcontent['time'] = date('Y-m-d H:i',$info['createtime']);
		\app\common\Wechat::sendtmpl(aid,$info['mid'],'tmpl_tixianerror',$tmplcontent,m_url('/pages/my/usercenter'));
		//订阅消息
		$tmplcontent = [];
		$tmplcontent['amount1'] = $info['txmoney'];
		$tmplcontent['time3'] = date('Y-m-d H:i',$info['createtime']);
		$tmplcontent['thing4'] = $reason;
		
		$tmplcontentnew = [];
		$tmplcontentnew['thing1'] = '提现失败';
		$tmplcontentnew['amount2'] = $info['txmoney'];
		$tmplcontentnew['date4'] = date('Y-m-d H:i',$info['createtime']);
		$tmplcontentnew['thing12'] = $reason;
		\app\common\Wechat::sendwxtmpl(aid,$info['mid'],'tmpl_tixianerror',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
		//短信通知
		$member = Db::name('member')->where(['id'=>$info['mid']])->find();
		if($member['tel']){
			\app\common\Sms::send(aid,$member['tel'],'tmpl_tixianerror',['reason'=>$reason]);
		}
		return $this->json(['status'=>1,'msg'=>'操作成功']);
	}
	//余额提现改为打款
	function widthdsetydk(){
		$id = input('post.id/d');
		Db::name('member_withdrawlog')->where(['aid'=>aid,'id'=>$id])->update(['status'=>3,'reason'=>'']);
		$info = Db::name('member_withdrawlog')->where(['aid'=>aid,'id'=>$id])->find();
        $info['money'] = dd_money_format($info['money']);
		//提现成功通知
		$tmplcontent = [];
		$tmplcontent['first'] = '您的提现申请已打款，请留意查收';
		$tmplcontent['remark'] = '请点击查看详情~';
		$tmplcontent['money'] = (string) $info['money'];
		$tmplcontent['timet'] = date('Y-m-d H:i',$info['createtime']);
        $tempconNew = [];
        $tempconNew['amount2'] = (string) $info['money'];//提现金额
        $tempconNew['time3'] = date('Y-m-d H:i',$info['createtime']);//提现时间
		\app\common\Wechat::sendtmpl(aid,$info['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'),$tempconNew);
		//订阅消息
		$tmplcontent = [];
		$tmplcontent['amount1'] = $info['money'];
		$tmplcontent['thing3'] = $info['paytype'];
		$tmplcontent['time5'] = date('Y-m-d H:i');
		
		$tmplcontentnew = [];
		$tmplcontentnew['amount3'] = $info['money'];
		$tmplcontentnew['phrase9'] = $info['paytype'];
		$tmplcontentnew['date8'] = date('Y-m-d H:i');
		\app\common\Wechat::sendwxtmpl(aid,$info['mid'],'tmpl_tixiansuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
		//短信通知
		$member = Db::name('member')->where(['id'=>$info['mid']])->find();
		if($member['tel']){
			\app\common\Sms::send(aid,$member['tel'],'tmpl_tixiansuccess',['money'=>$info['money']]);
		}
		return $this->json(['status'=>1,'msg'=>'操作成功']);
	}
	//余额提现 微信打款
	function widthdwxdakuan(){
		$id = input('post.id/d');
		$info = Db::name('member_withdrawlog')->where(['aid'=>aid,'id'=>$id])->find();
		if($info['status']!=1) return $this->json(['status'=>0,'msg'=>'已审核状态才能打款']);
        $info['money'] =  dd_money_format($info['money']);
        $field = 'wx_transfer_type';
        $admin_set = Db::name('admin_set')->where('aid',aid)->field($field)->find();
        if($admin_set['wx_transfer_type']==1){
            //使用了新版的商家转账功能
            $paysdk = new WxPayV3(aid,$info['mid'],$info['platform']);
            $rs = $paysdk->transfer($info['ordernum'],$info['money'],'',t('余额').'提现','member_withdrawlog',$info['id']);
            if($rs['status']==1){
                $data = [
                    'status' => '4',//状态改为处理中，用户确认收货后再改为已打款
                    'wx_package_info' => $rs['data']['package_info'],//用户确认页面的信息
                    'wx_state' => $rs['data']['state'],//转账状态
                    'wx_transfer_bill_no' => $rs['data']['transfer_bill_no'],//微信单号
                ];
                Db::name('member_withdrawlog')->where('id',$info['id'])->update($data);
            }else{
                $data = [
                    'wx_transfer_msg' => $rs['msg'],
                ];
                Db::name('member_withdrawlog')->where('id',$info['id'])->update($data);
            }
        }else{
            $rs = \app\common\Wxpay::transfers(aid,$info['mid'],$info['money'],$info['ordernum'],$info['platform'],t('余额').'提现');
            Db::name('member_withdrawlog')->where(['aid'=>aid,'id'=>$id])->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['resp']['payment_no']]);
        }
		if($rs['status']==0){
			return $this->json(['status'=>0,'msg'=>$rs['msg']]);
		}else{
			//提现成功通知
			$tmplcontent = [];
			$tmplcontent['first'] = '您的提现申请已打款，请留意查收';
			$tmplcontent['remark'] = '请点击查看详情~';
			$tmplcontent['money'] = (string) $info['money'];
			$tmplcontent['timet'] = date('Y-m-d H:i',$info['createtime']);
            $tempconNew = [];
            $tempconNew['amount2'] = (string) $info['money'];//提现金额
            $tempconNew['time3'] = date('Y-m-d H:i',$info['createtime']);//提现时间
			\app\common\Wechat::sendtmpl(aid,$info['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'),$tempconNew);
			//订阅消息
			$tmplcontent = [];
			$tmplcontent['amount1'] = $info['money'];
			$tmplcontent['thing3'] = $info['paytype'];
			$tmplcontent['time5'] = date('Y-m-d H:i');
			
			$tmplcontentnew = [];
			$tmplcontentnew['amount3'] = $info['money'];
			$tmplcontentnew['phrase9'] = $info['paytype'];
			$tmplcontentnew['date8'] = date('Y-m-d H:i');
			\app\common\Wechat::sendwxtmpl(aid,$info['mid'],'tmpl_tixiansuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
			//短信通知
			$member = Db::name('member')->where(['id'=>$info['mid']])->find();
			if($member['tel']){
				$tel = $member['tel'];
				\app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$info['money']]);
			}
			return $this->json(['status'=>1,'msg'=>$rs['msg']]);
		}
	}
	//佣金提现记录
	function comwithdrawlog(){
		$pagenum = input('post.pagenum');
		$st = input('post.st');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['withdrawlog.aid','=',aid];
		if($st == 'all'){

		}elseif($st == '0'){
			$where[] = ['status','=',0];
		}elseif($st == '1'){
			$where[] = ['status','=',1];
		}elseif($st == '2'){
			$where[] = ['status','=',2];
		}elseif($st == '3'){
			$where[] = ['status','=',3];
		}

		if(input('param.keyword')) $where[] = ['member.nickname','like','%'.trim(input('param.keyword')).'%'];
		if(input('?param.status') && input('param.status')!=='') $where[] = ['withdrawlog.status','=',input('param.status')];
		$datalist = Db::name('member_commission_withdrawlog')->alias('withdrawlog')->field('member.nickname,member.headimg,withdrawlog.*')->join('member member','member.id=withdrawlog.mid')->where($where)->page($pagenum,$pernum)->order('withdrawlog.id desc')->select()->toArray();
		if($pagenum==1){
			$count = 0 + Db::name('member_commission_withdrawlog')->alias('withdrawlog')->field('member.nickname,member.headimg,withdrawlog.*')->join('member member','member.id=withdrawlog.mid')->where($where)->count();
		}
		return $this->json(['status'=>1,'count'=>$count,'data'=>$datalist]);
	}
	function comwithdrawdetail(){
		$id = input('param.id/d');
		$info = Db::name('member_commission_withdrawlog')->where(['aid'=>aid,'id'=>$id])->find();

		$comwithdrawbl = Db::name('admin_set')->where('aid',aid)->value('comwithdrawbl');
		if($comwithdrawbl > 0 && $comwithdrawbl < 100){
			$money = $info['money'];
			$info['money'] = round($money * $comwithdrawbl * 0.01,2);
			$info['tomoney'] = round($money - $info['money'],2);
		}else{
			$info['tomoney'] = 0;
		}

		$member = Db::name('member')->where(['id'=>$info['mid']])->find();
		$info['nickname'] = $member['nickname'];
		$info['headimg'] = $member['headimg'];
		return $this->json(['status'=>1,'info'=>$info]);
	}
	function comwidthdrawpass(){
		$id = input('post.id/d');
		$info = Db::name('member_commission_withdrawlog')->where(['aid'=>aid,'id'=>$id])->update(['status'=>1]);
		return $this->json(['status'=>1,'msg'=>'操作成功']);
	}
	function comwidthdrawnopass(){
		$id = input('post.id/d');
		$reason = input('post.reason');
		Db::name('member_commission_withdrawlog')->where(['aid'=>aid,'id'=>$id])->update(['status'=>2,'reason'=>$reason]);
		$info = Db::name('member_commission_withdrawlog')->where(['aid'=>aid,'id'=>$id])->find();
		\app\common\Member::addcommission(aid,$info['mid'],0,$info['txmoney'],t('佣金').'提现返还',0,'withdraw_back');
		//提现失败通知
		$tmplcontent = [];
		$tmplcontent['first'] = '您的提现申请被商家驳回，可与商家协商沟通。';
		$tmplcontent['remark'] = $reason.'，请点击查看详情~';
		$tmplcontent['money'] = (string) $info['txmoney'];
		$tmplcontent['time'] = date('Y-m-d H:i',$info['createtime']);
		\app\common\Wechat::sendtmpl(aid,$info['mid'],'tmpl_tixianerror',$tmplcontent,m_url('activity/commission/commissionlog?st=1'));
		//订阅消息
		$tmplcontent = [];
		$tmplcontent['amount1'] = $info['txmoney'];
		$tmplcontent['time3'] = date('Y-m-d H:i',$info['createtime']);
		$tmplcontent['thing4'] = $reason;

		$tmplcontentnew = [];
		$tmplcontentnew['thing1'] = '提现失败';
		$tmplcontentnew['amount2'] = $info['txmoney'];
		$tmplcontentnew['date4'] = date('Y-m-d H:i',$info['createtime']);
		$tmplcontentnew['thing12'] = $reason;
		\app\common\Wechat::sendwxtmpl(aid,$info['mid'],'tmpl_tixianerror',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
		//短信通知
		$member = Db::name('member')->where(['id'=>$info['mid']])->find();
		if($member['tel']){
			$tel = $member['tel'];
			\app\common\Sms::send(aid,$tel,'tmpl_tixianerror',['reason'=>$reason]);
		}
		return $this->json(['status'=>1,'msg'=>'操作成功']);
	}
	function comwidthdsetydk(){
		$id = input('post.id/d');

		$info = Db::name('member_commission_withdrawlog')->where(['aid'=>aid,'id'=>$id])->find();

		Db::name('member_commission_withdrawlog')->where(['aid'=>aid,'id'=>$id])->update(['status'=>3]);

		if(getcustom('fengdanjiangli')){
			$tomoney = $info['tomoney']??0;
			// 提现时计算$tomoney
			if($tomoney > 0){
				\app\common\Member::addmoney(aid,$info['mid'],$tomoney,t('佣金').'提现');
			}
		}

        $info['money'] =dd_money_format($info['money']);
		//提现成功通知
		$tmplcontent = [];
		$tmplcontent['first'] = '您的提现申请已打款，请留意查收';
		$tmplcontent['remark'] = '请点击查看详情~';
		$tmplcontent['money'] = (string) $info['money'];
		$tmplcontent['timet'] = date('Y-m-d H:i',$info['createtime']);
        $tempconNew = [];
        $tempconNew['amount2'] = (string) $info['money'];//提现金额
        $tempconNew['time3'] = date('Y-m-d H:i',$info['createtime']);//提现时间
		\app\common\Wechat::sendtmpl(aid,$info['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'),$tempconNew);
		//订阅消息
		$tmplcontent = [];
		$tmplcontent['amount1'] = $info['money'];
		$tmplcontent['thing3'] = $info['paytype'];
		$tmplcontent['time5'] = date('Y-m-d H:i');
		
		$tmplcontentnew = [];
		$tmplcontentnew['amount3'] = $info['money'];
		$tmplcontentnew['phrase9'] = $info['paytype'];
		$tmplcontentnew['date8'] = date('Y-m-d H:i');
		\app\common\Wechat::sendwxtmpl(aid,$info['mid'],'tmpl_tixiansuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
		//短信通知
		$member = Db::name('member')->where(['id'=>$info['mid']])->find();
		if($member['tel']){
			$tel = $member['tel'];
			\app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$info['money']]);
		}
		return $this->json(['status'=>1,'msg'=>'操作成功']);
	}
	function comwidthdwxdakuan(){
		$id = input('post.id/d');
		$info = db('member_commission_withdrawlog')->where(['aid'=>aid,'id'=>$id])->find();
		if($info['status']!=1) return ['status'=>0,'msg'=>'已审核状态才能打款'];
        $info['money'] =dd_money_format($info['money']);
		$comwithdrawbl = Db::name('admin_set')->where('aid',aid)->value('comwithdrawbl');
		if($comwithdrawbl > 0 && $comwithdrawbl < 100){
			$paymoney = round($info['money'] * $comwithdrawbl * 0.01,2);
			$tomoney = round($info['money'] - $paymoney,2);
		}else{
			$paymoney = $info['money'];
			$tomoney = 0;
		}
        $field = 'wx_transfer_type';
        $admin_set = Db::name('admin_set')->where('aid',aid)->field($field)->find();
        if($admin_set['wx_transfer_type']==1){
            //使用了新版的商家转账功能
            $paysdk = new WxPayV3(aid,$info['mid'],$info['platform']);
            $rs = $paysdk->transfer($info['ordernum'],$paymoney,'',t('佣金').'提现','member_commission_withdrawlog',$info['id']);
            if($rs['status']==1){
                $data = [
                    'status' => '4',//状态改为处理中，用户确认收货后再改为已打款
                    'wx_package_info' => $rs['data']['package_info'],//用户确认页面的信息
                    'wx_state' => $rs['data']['state'],//转账状态
                    'wx_transfer_bill_no' => $rs['data']['transfer_bill_no'],//微信单号
                ];
                Db::name('member_commission_withdrawlog')->where('id',$info['id'])->update($data);
            }else{
                $data = [
                    'wx_transfer_msg' => $rs['msg'],
                ];
                Db::name('member_commission_withdrawlog')->where('id',$info['id'])->update($data);
            }
            $rs['msg'] = $rs['msg']??'操作成功';
        }else{
            $rs = \app\common\Wxpay::transfers(aid,$info['mid'],$paymoney,$info['ordernum'],$info['platform'],t('佣金').'提现');
            Db::name('member_commission_withdrawlog')->where(['aid'=>aid,'id'=>$id])->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['resp']['payment_no']]);
        }

		if($rs['status']==0){
			return $this->json(['status'=>0,'msg'=>$rs['msg']]);
		}else{
			if($tomoney > 0){
				\app\common\Member::addmoney(aid,$info['mid'],$tomoney,t('佣金').'提现');
			}
			//提现成功通知
			$tmplcontent = [];
			$tmplcontent['first'] = '您的提现申请已打款，请留意查收';
			$tmplcontent['remark'] = '请点击查看详情~';
			$tmplcontent['money'] = (string) $info['money'];
			$tmplcontent['timet'] = date('Y-m-d H:i',$info['createtime']);
            $tempconNew = [];
            $tempconNew['amount2'] = (string) $info['money'];//提现金额
            $tempconNew['time3'] = date('Y-m-d H:i',$info['createtime']);//提现时间
			\app\common\Wechat::sendtmpl(aid,$info['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'),$tempconNew);
			//订阅消息
			$tmplcontent = [];
			$tmplcontent['amount1'] = $info['money'];
			$tmplcontent['thing3'] = $info['paytype'];
			$tmplcontent['time5'] = date('Y-m-d H:i');
			
			$tmplcontentnew = [];
			$tmplcontentnew['amount3'] = $info['money'];
			$tmplcontentnew['phrase9'] = $info['paytype'];
			$tmplcontentnew['date8'] = date('Y-m-d H:i');
			\app\common\Wechat::sendwxtmpl(aid,$info['mid'],'tmpl_tixiansuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
			//短信通知
			$member = Db::name('member')->where(['id'=>$info['mid']])->find();
			if($member['tel']){
				$tel = $member['tel'];
				\app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$info['money']]);
			}

			return $this->json(['status'=>1,'msg'=>$rs['msg']]);
		}
	}

	//商家余额明细
	public function bmoneylog(){
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',bid];
		$datalist = Db::name('business_moneylog')->field("id,money,`after`,createtime,remark")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = [];
		return $this->json(['status'=>1,'data'=>$datalist]);
	}
	//商家余额提现记录
	public function bwithdrawlog(){
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
        $set = [];
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',bid];
        if(getcustom('business_withdraw_cash_mobile')){
            $where[] = ['status','<>',20];
        }

        if (getcustom('business_withdraw_invoice_mobile') ) {
            if(input('date_start') && input('date_end')){
                $date_start = strtotime(input('date_start'));
                $date_end = strtotime(input('date_end')) + 86399;
                $where[] = ['createtime', 'between', [$date_start, $date_end]];
            }
            $set['business_withdraw_invoice_mobile'] = true;

            $st = input('param.st');
            if($st){
                $where[] = ['status', '=', $st];
                if($st == 3){
                    $where[] = ['withdrawlog_invoice_id','=',0];
                }
            }
        }
		$datalist = Db::name('business_withdrawlog')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = [];
		return $this->json(['status'=>1,'data'=>$datalist,'set' => $set]);
	}
	//提现信息设置
	public function txset(){
		if(request()->isPost()){
			$postinfo = input('post.');
			$data = [];
			$data['weixin'] = $postinfo['weixin'];
			if( getcustom('alipay_auto_transfer')){
                $data['aliaccountname'] = $postinfo['aliaccountname'];
            }
			$data['aliaccount'] = $postinfo['aliaccount'];
			$data['bankname'] = $postinfo['bankname'];
			$data['bankcarduser'] = $postinfo['bankcarduser'];
			$data['bankcardnum'] = $postinfo['bankcardnum'];
			Db::name('business')->where('id',bid)->update($data);
			return $this->json(['status'=>1,'msg'=>'保存成功']);
		}
		$field = 'id,weixin,aliaccount,bankname,bankcarduser,bankcardnum';
		if(getcustom('alipay_auto_transfer')){
		    $field.=',aliaccountname';
        }
		$info = Db::name('business')->field($field)->where(['id'=>bid])->find();
        if(getcustom('alipay_auto_transfer')){
            $info['show_aliaccountname']  =1;
        }
		return $this->json(['status'=>1,'info'=>$info]);
	}
	public function bwithdraw(){
	    $field = 'withdrawmin,withdrawfee,withdraw_weixin,withdraw_aliaccount,withdraw_bankcard,commission_autotransfer,weixin_withdraw_max,alipay_withdraw_max';
	    if(getcustom('business_withdraw_otherset')){
            $field .=',withdrawmax,day_withdraw_num';
        }
        if(getcustom('pay_allinpay')){
            $field .= ',withdraw_bankcard_allinpayYunst,yunstwithdrawfeetype,yunstwithdrawfee';
        }
        if(getcustom('pay_huifu_business_withdraw')){
            $field .= ',withdraw_huifu';
        }
        if(getcustom('pay_huifu_dianzhang_withdraw')){
            $field .= ',withdraw_huifu_dianzhang';
        }
        if(getcustom('pay_huifu_business_withdraw') || getcustom('pay_huifu_dianzhang_withdraw')){
            $field .= ',huifu_withdraw_max';
        }
        if(getcustom('business_withdraw_max_limit_text')){
            $field .= ',withdraw_desc,max_withdraw_limit_text';
        }
        if(getcustom('business_withdraw_cash_mobile')){
            $field .= ',withdrawfee_cash_status,withdrawfee_cash_rate,withdraw_business_admin_money';
        }
		$set = Db::name('business_sysset')->where(['aid'=>aid])->field($field)->find();
        $wx_transfer_type = Db::name('admin_set')->where('aid',aid)->value('wx_transfer_type');
		if(request()->isPost()){
			$post = input('post.');
			//if($set['withdraw'] == 0){
			//	return ['status'=>0,'msg'=>'余额提现功能未开启'];
			//}
            $withdrawmaxText = '';
            if(getcustom('business_withdraw_max_limit_text') && $set['max_withdraw_limit_text']){
                $withdrawmaxText = $set['max_withdraw_limit_text'];
            }
            if(!$post['paytype']){
                return $this->json(['status'=>0,'msg'=>'请选择提现方式']);
            }
            if($post['paytype']=='支付宝'){
                if($post['money'] > $set['alipay_withdraw_max'] && $set['alipay_withdraw_max'] > 0){
                    return $this->json(['status'=>0,'msg'=>'该方式提现限额为'.$set['alipay_withdraw_max'].'元']);
                }
            }
            if($post['paytype']=='微信' || $post['paytype']=='微信钱包'){
                if($post['money'] > $set['weixin_withdraw_max'] && $set['weixin_withdraw_max'] > 0){
                    if($withdrawmaxText){
                        return $this->json(['status'=>0,'msg'=> $withdrawmaxText]);
                    }
                    return $this->json(['status'=>0,'msg'=>'该方式提现限额为'.$set['weixin_withdraw_max'].'元']);
                }
            }
            if($post['paytype']=='汇付斗拱' || $post['paytype']=='店长汇付打款'){
                if($post['money'] > $set['huifu_withdraw_max'] && $set['huifu_withdraw_max'] > 0){
                    return $this->json(['status'=>0,'msg'=>'该方式提现限额为'.$set['huifu_withdraw_max'].'元']);
                }
            }
            Db::startTrans();
            if(getcustom('admin_login_sms_verify')){
                $checkTel = Db::name('admin_user')->where('aid',aid)->where('bid',bid)->where('tel',$post['tel'])->find();
                if(empty($checkTel)){
                    return $this->json(['status'=>0,'msg'=>'绑定手机号不正确']);
                }
                if($post['smscode'] == ''){
                    return $this->json(['status'=>0,'msg'=>'短信验证码不能为空']);
                }elseif(md5($post['tel'].'-'.$post['smscode']) != cache($this->sessionid.'_smscode') || cache($this->sessionid.'_smscodetimes')>5){
                    cache($this->sessionid.'_smscodetimes',cache($this->sessionid.'_smscodetimes')+1);
                    return $this->json(['status'=>0,'msg'=>'短信验证码错误']);
                }
                cache($this->sessionid.'_smscode',null);
                cache($this->sessionid.'_smscodetimes',null);
            }
			$binfo = Db::name('business')->where('id',bid)->lock(true)->find();
			if($post['paytype']=='支付宝' && $binfo['aliaccount']==''){
				return $this->json(['status'=>0,'msg'=>'请先设置支付宝账号']);
			}
            if($post['paytype']=='支付宝'){
                if(getcustom('alipay_auto_transfer') && $binfo['aliaccountname']==''){
                    return $this->json(['status'=>0,'msg'=>'请设置支付宝户名']);
                }
            }

			if($post['paytype']=='银行卡' && ($binfo['bankname']==''||$binfo['bankcarduser']==''||$binfo['bankcardnum']=='')){
				return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
			}
			// 汇付斗拱
			if($post['paytype']=='汇付斗拱' && getcustom('pay_huifu_business_withdraw') && $set['withdraw_huifu'] == 1 && getcustom('pay_huifu_fenzhang') && ($binfo['huifu_business_status'] == 0 || !$binfo['huifu_id'])){
				return $this->json(['status'=>0,'msg'=>'请先设置汇付商户号']);
			}
			// 店长汇付打款
			if($post['paytype']=='店长汇付打款' && getcustom('pay_huifu_dianzhang_withdraw') && $set['withdraw_huifu_dianzhang'] == 1){
				if(!$binfo['mid']){
					return $this->json(['status'=>0,'msg'=>'请先在管理员列表进行会员绑定']);
				}
				$memberinfo = Db::name('member')->where('aid',aid)->where('id',$binfo['mid'])->find();
				if(empty($memberinfo['huifu_id']) || empty($memberinfo['huifu_token_no'])){
					return $this->json(['status'=>0,'msg'=>'请先对商家绑定的会员进行汇付进件操作']);
				}
			}
			if(getcustom('pay_allinpay')){
                if($post['paytype']=='通联支付银行卡'){
                    if($set['withdraw_bankcard_allinpayYunst'] != 1){
                        return $this->json(['status'=>0,'msg'=>'通联支付银行卡提现功能未开启']);
                    }
                    // if(empty($this->member['bankname']) || empty($this->member['bankcarduser'])|| empty($this->member['bankcardnum'])){
                    //     return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
                    // }
                    //通联支付 通联企业会员
                    $companyuser = Db::name('member_allinpay_yunst_companyuser')->where('mid',mid)->where('aid',aid)->find();
                    if(!$companyuser){
                        return $this->json(['status'=>-4,'msg'=>'未创建通联企业会员，请前去创建','url'=>'/pagesC/allinpay/yunstMemberCompany?bid='.bid]);
                    }
                    if($companyuser['status'] == 1){
                    	return $this->json(['status'=>0,'msg'=>'通联企业会员申请中','url'=>'/pagesC/allinpay/yunstMemberCompany?bid='.bid]);
                    }
                    if($companyuser['status'] != 2){
                    	return $this->json(['status'=>-4,'msg'=>'通联企业会员申请失败','url'=>'/pagesC/allinpay/yunstMemberCompany?bid='.bid]);
                    }
                    if($companyuser && $companyuser['status'] == 2 && ($companyuser['ocrIdcardComparisonResult'] != 1 || $companyuser['ocrRegnumComparisonResult'] != 1)){
	            		return $this->json(['status'=>0,'msg'=>'OCR识别失败，请联系人工客服']);
	            	}
                }
            }

			$money = $post['money'];
			if($money<=0 || $money < $set['withdrawmin']){
				return $this->json(['status'=>0,'msg'=>'提现金额必须大于'.($set['withdrawmin']?$set['withdrawmin']:0)]);
			}
            $withdrawmaxText = '';
            if(getcustom('business_withdraw_max_limit_text') && $set['max_withdraw_limit_text']){
                $withdrawmaxText = $set['max_withdraw_limit_text'];
            }

            $business_withdraw_cash_mobile = getcustom('business_withdraw_cash_mobile');
            if(getcustom('business_withdraw_otherset')){
                if($set['withdrawmax']>0 && $money > $set['withdrawmax']){
                    if($withdrawmaxText){
                        return $this->json(['status'=>0,'msg'=> $withdrawmaxText]);
                    }
                    return $this->json(['status'=>0,'msg'=> '提现金额过大，单笔'.t('余额').'提现最高金额为'.$set['withdrawmax'].'元']);
                }
                if($set['day_withdraw_num']<0){
                    return $this->json(['status'=>0,'msg'=>'暂时不可提现']);
                }else if($set['day_withdraw_num']>0){
                    $start_time = strtotime(date('Y-m-d 00:00:01'));
                    $end_time = strtotime(date('Y-m-d 23:59:59'));
                    $log_where = [];
                    if($business_withdraw_cash_mobile){
                        $log_where[] = ['status','<>','20'];
                    }
                    $day_withdraw_num = 0 + Db::name('business_withdrawlog')->where('aid',aid)->where('bid',bid)->where($log_where)->where('createtime','between',[$start_time,$end_time])->count();
                    $daynum = $day_withdraw_num+1;
                    if($daynum>$set['day_withdraw_num']){
                        return $this->json(['status'=>0,'msg'=>'今日申请提现次数已满，请明天继续申请提现']);
                    }
                }
            }
			if($money > $binfo['money']){
				return $this->json(['status'=>0,'msg'=>'可提现余额不足']);
			}

			$ordernum = date('ymdHis').aid.rand(1000,9999);
			$record['aid'] = aid;
			$record['bid'] = bid;
			$record['createtime']= time();

			$record['txmoney']= $money;
			$fee = $set['withdrawfee']>0?$money*$set['withdrawfee']*0.01:0;
			if(getcustom('pay_allinpay')){
				if($post['paytype']=='通联支付银行卡'){
					if($set['yunstwithdrawfeetype'] == 1){
						$fee = $set['yunstwithdrawfee']>0?$set['yunstwithdrawfee']:0;
					}
				}
			}
			$money2 = $money-$fee;
			$record['money']  = dd_money_format($money2);

			$record['ordernum'] = $ordernum;
			$record['paytype'] = $post['paytype'];
			if($post['paytype']=='微信' || $post['paytype']=='微信钱包'){
				if($binfo['weixin']==''){
					return $this->json(['status'=>0,'msg'=>'请填写完整提现信息','url'=>'txset']);
				}
				$record['weixin'] = $binfo['weixin'];
				if($set['commission_autotransfer']==1 && $wx_transfer_type==0){
					$mid = Db::name('admin_user')->where('aid',aid)->where('bid',bid)->where('isadmin',1)->value('mid');
					if(!$mid) return $this->json(['status'=>0,'msg'=>'商户主管理员未绑定微信']);
				}
			}else if($post['paytype']=='支付宝'){
				$record['aliaccount'] = $binfo['aliaccount'];
				if($record['aliaccount']==''){
                    return $this->json(['status'=>0,'msg'=>'请填写完整提现信息']);
                }
			}else if($post['paytype']=='银行卡'){
				$record['bankname'] = $binfo['bankname'];
				$record['bankcarduser'] = $binfo['bankcarduser'];
				$record['bankcardnum'] = $binfo['bankcardnum'];
				if($record['bankname']=='' || $record['bankcarduser']=='' || $record['bankcardnum']==''){
                    return $this->json(['status'=>0,'msg'=>'请填写完整提现信息']);
                }
			}
            $record['platform'] = platform;
            if(getcustom('pay_allinpay')){
                if($post['paytype']=='通联支付银行卡'){
                    $record['bankname']    = '';
                    $record['bankcarduser']= $companyuser['parentBankName'];
                    $record['bankcardnum'] = $companyuser['accountNo'];
                }
            }

            $withdrawfee_cash_money = 0;
            $business_withdraw_cash_mobile_duliset = getcustom('business_withdraw_cash_mobile_duliset');
            if(getcustom('business_withdraw_cash_mobile')){

                if($business_withdraw_cash_mobile_duliset){
                    $business = Db::name('business')->where('aid', aid)->where('id', bid)->field('id,withdrawfee_cash_status_type,withdrawfee_cash_status,withdrawfee_cash_rate')->find();
                    //独立设置
                    if($business['withdrawfee_cash_status_type'] == 1){
                        $set['withdrawfee_cash_status'] = $business['withdrawfee_cash_status'];
                        $set['withdrawfee_cash_rate'] = $business['withdrawfee_cash_rate'];
                    }
                }

                if($set['withdrawfee_cash_status'] == 1 && $set['withdrawfee_cash_rate'] > 0){
                    //提现手续费现金额
                    $withdrawfee_cash_money = bcmul($money,($set['withdrawfee_cash_rate'] * 0.01),2);
                    if($withdrawfee_cash_money > 0){
                        $record['status'] = 20;
                        $record['payorderid'] = input('post.payorderid') ?? 0;
                    }
                }

                if ($post['paytype'] == '商家管理员余额'){
                    if(!$this->user){
                        return $this->json(['status' => 0, 'msg' => '暂无管理员信息']);
                    }
                    if(!$this->user['mid']){
                        return $this->json(['status' => 0, 'msg' => '当前登录管理员暂未绑定用户信息']);
                    }

                    $record['tx_admin_user_id'] = $this->user['id'];
                    $record['weixin'] = '用户ID:'.$this->user['mid'].'账户'.t('余额');
                }
            }

            if($withdrawfee_cash_money == 0){
                $res = \app\common\Business::addmoney(aid,bid,-$money,'余额提现',false,'withdraw');
                if(!$res || ($res && $res['status'] !=1)){
	                \think\facade\Log::write('Businesswithdrawfail_'.bid.'_'.$money);
	                return json(['status'=>0,'msg'=>'提现失败']);
	            }
            }

            if($res && $res['status'] == 0) return $this->json(['status'=>0,'msg'=>$res['msg']]);

			$recordid = db('business_withdrawlog')->insertGetId($record);
			if(!$recordid) return $this->json(['status'=>0,'msg'=>'提现失败']);
			Db::commit();

			\app\common\System::plog('手机端商家提现'.$recordid);

            if(getcustom('business_withdraw_cash_mobile')){
                if($record['status'] == 20){
                    return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
                }
            }

            if($post['paytype']=='微信' || $post['paytype']=='微信钱包'){
				if($set['commission_autotransfer']==1 && $wx_transfer_type==0){

                    $rs = \app\common\Wxpay::transfers(aid,$mid,$record['money'],$record['ordernum'],'','余额提现');
					if($rs['status']==0){
						$record = [];
                        $record['status'] = 1;
                        $record['reason'] = $rs['msg']??'微信提现失败';
                        Db::name('business_withdrawlog')->where('id',$recordid)->update($record);
						return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
					}else{
						$record = [];
						$record['weixin'] = t('会员').'ID：'.$mid;
						$record['status'] = 3;
						$record['paytime'] = time();
						$record['paynum'] = $rs['resp']['payment_no'];
						Db::name('business_withdrawlog')->where('id',$recordid)->update($record);

						//提现成功通知
						$tmplcontent = [];
						$tmplcontent['first'] = '您的提现申请已打款，请留意查收';
						$tmplcontent['remark'] = '请点击查看详情~';
						$tmplcontent['money'] = (string) $record['money'];
						$tmplcontent['timet'] = date('Y-m-d H:i',$record['createtime']);
                        $tempconNew = [];
                        $tempconNew['amount2'] = (string) $record['money'];//提现金额
                        $tempconNew['time3'] = date('Y-m-d H:i',$record['createtime']);//提现时间
						\app\common\Wechat::sendtmpl(aid,$mid,'tmpl_tixiansuccess',$tmplcontent,m_url('admin/index/index'),$tempconNew);
						//短信通知
						$member = Db::name('member')->where('id',$mid)->find();
						if($member['tel']){
							$tel = $member['tel'];
							\app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$record['money']]); 
						}
						return $this->json(['status'=>1,'msg'=>$rs['msg']]);
					}
				}
			}else if($post['paytype']=='支付宝'){
                if($set['commission_autotransfer']==1){
                    $rs = \app\common\Alipay::transfers(aid,$record['ordernum'],$money,t('余额').'提现',$binfo['aliaccount'],$binfo['aliaccountname'],t('余额').'提现');
                    if($rs && $rs['status']==1){
                        $record = [];
                        $record['aliaccount'] =$binfo['aliaccount'] ;
                        $record['status'] = 3;
                        $record['paytime'] = time();
                        $record['paynum'] = $rs['resp']['payment_no'];
                        Db::name('business_withdrawlog')->where('id',$recordid)->update($record);
                        \app\common\System::plog('商家提现支付宝打款'.$recordid);
                        return $this->json(['status'=>1,'msg'=>$rs['msg'],'url'=>(string)url('withdrawlog')]);
                    }else{
                        $record = [];
                        $record['status'] = 1;
                        $record['reason'] = $rs['sub_msg']??'支付宝提现失败';
                        Db::name('business_withdrawlog')->where('id',$recordid)->update($record);
                        return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
                    }
                }
			}else if($post['paytype']=='汇付斗拱'){
				if(getcustom('pay_huifu_business_withdraw') && getcustom('pay_huifu_fenzhang') && $set['commission_autotransfer']==1){
                    $is_huifu_withdraw_max = 0;
                    if($money > $set['huifu_withdraw_max'] && $set['huifu_withdraw_max'] > 0){
                        $is_huifu_withdraw_max = 1;
                    }
                    if(!$is_huifu_withdraw_max){
                        $huifu = new \app\custom\Huifu([],aid,bid,0,t('余额').'提现',$record['ordernum'],$record['money']);
                        $record['id'] = $recordid;
                        $rs = $huifu->moneypayTradeAcctpaymentPay($binfo['huifu_id'],array_merge($record,['tablename'=>'member_withdrawlog']));
                        if($rs['status']==0){
                            Db::name('business_withdrawlog')->where('aid',aid)->where('id',$recordid)->update(['reason'=>$rs['msg']]);
                            return json(['status'=>0,'msg'=>$rs['msg']?:'审核中']);
                        }elseif($rs['status']==2){//处理中
                            Db::name('business_withdrawlog')->where('aid',aid)->where('id',$recordid)->update(['status'=>4,'paynum'=>$rs['data']['hf_seq_id']]);
                            \app\common\System::plog('商家余额提现汇付斗拱余额打款'.$recordid);
                            return json(['status'=>1,'msg'=>'支付处理中，'.$rs['msg']]);
                        }else{
                            $huifu->tradeSettlementEnchashmentRequest();
                            Db::name('business_withdrawlog')->where('aid',aid)->where('id',$recordid)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['data']['hf_seq_id'],'reason'=>'']);
                            \app\common\System::plog('商家余额提现汇付斗拱余额打款'.$recordid);
                            return json(['status'=>1,'msg'=>$rs['msg'],'url'=>(string)url('withdrawlog')]);

                        }
                    }
                }
			}else if($post['paytype']=='店长汇付打款'){
				if(getcustom('pay_huifu_dianzhang_withdraw') && $set['commission_autotransfer']==1){
                    $is_huifu_withdraw_max = 0;
                    if($money > $set['huifu_withdraw_max'] && $set['huifu_withdraw_max'] > 0){
                        $is_huifu_withdraw_max = 1;
                    }
                    if(!$is_huifu_withdraw_max) {
                        $huifu = new \app\custom\Huifu([], aid, bid, $binfo['mid'], t('余额') . '提现', $record['ordernum'], $record['money']);
                        $record['id'] = $recordid;
                        $rs = $huifu->moneypayTradeAcctpaymentPay($binfo['huifu_id'], array_merge($record, ['tablename' => 'member_withdrawlog']));
                        if ($rs['status'] == 0) {
                            Db::name('business_withdrawlog')->where('aid', aid)->where('id', $recordid)->update(['reason' => $rs['msg']]);
                            return json(['status' => 0, 'msg' => $rs['msg'] ?: '审核中']);
                        } elseif ($rs['status'] == 2) {//处理中
                            Db::name('business_withdrawlog')->where('aid', aid)->where('id', $recordid)->update(['status' => 4, 'paynum' => $rs['data']['hf_seq_id']]);
                            \app\common\System::plog('商家余额提现汇付斗拱余额打款' . $recordid);
                            return json(['status' => 1, 'msg' => '支付处理中，' . $rs['msg']]);
                        } else {
                            $huifu->tradeSettlementEnchashmentRequest();
                            Db::name('business_withdrawlog')->where('aid', aid)->where('id', $recordid)->update(['status' => 3, 'paytime' => time(), 'paynum' => $rs['data']['hf_seq_id'], 'reason' => '']);
                            \app\common\System::plog('商家余额提现汇付斗拱余额打款' . $recordid);
                            return json(['status' => 1, 'msg' => $rs['msg'], 'url' => (string)url('withdrawlog')]);

                        }
                    }
                }
			}else if($post['paytype']=='商家管理员余额'){
                if(getcustom('business_withdraw_cash_mobile')){
                    if($set['commission_autotransfer']==1){
                        $rs = \app\common\Member::addmoney(aid,$this->user['mid'], $record['money'],"商户余额提现");
                        if($rs && $rs['status']==1){
                            $record = [];
                            $record['status'] = 3;
                            $record['paytime'] = time();
                            Db::name('business_withdrawlog')->where('id',$recordid)->update($record);
                            \app\common\System::plog('商家提现到管理员余额'.$recordid);
                            return $this->json(['status'=>1,'msg'=>'提现成功','url'=>(string)url('withdrawlog')]);
                        }else{
                            return $this->json(['status'=>1,'msg'=>'提现失败']);
                        }
                    }
                }
            }
            
            $need_confirm = 0;

            if(getcustom('pay_allinpay')){
                //通联支付 云商通
                if($set['commission_autotransfer'] && $post['paytype'] == '通联支付银行卡'){
                    //先查询余额，余额不足去转账，然后提现，充足直接提现
                    $queryBalance = \app\custom\AllinpayYunst::queryBalance(aid,$companyuser['bizUserId']);
                    if(!$queryBalance || $queryBalance['status'] != 1){
                        $msg = $queryBalance && $queryBalance['msg']?$queryBalance['msg']:'';
                        Db::name('business_withdrawlog')->where('id',$recordid)->update(['reason'=>'通联查询余额失败'.$msg]);
                        return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
                    }
                    $balance = ($queryBalance['data']['allAmount'] - $queryBalance['data']['freezenAmount'])/100;
                    if($balance>=$record['txmoney']){
                        //提现
                        $withdrawApply = \app\custom\AllinpayYunst::withdrawApply(aid,$this->member,$recordid,$record,3,2);
                        if($withdrawApply && $withdrawApply['status'] == 1){
                            $updata = [];
                            $updata['status']   = 1;
                            $updata['allinpayorderNo'] = $withdrawApply['data']['orderNo'];
                            Db::name('business_withdrawlog')->where('id',$recordid)->update($updata);
                            return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款','data'=>[]]);
                        }else{
                            $msg = $withdrawApply && $withdrawApply['msg']?$withdrawApply['msg']:'';
                            Db::name('business_withdrawlog')->where('id',$recordid)->update(['reason'=>'通联提现失败'.$msg]);
                            return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
                        }
                    }else{
                        //转账
                        $applicationTransfer = \app\custom\AllinpayYunst::applicationTransfer(aid,$this->member,$recordid,$record,3,2);
                        if($applicationTransfer && $applicationTransfer['status'] == 1){
                            //提现
                            $withdrawApply = \app\custom\AllinpayYunst::withdrawApply(aid,$this->member,$recordid,$record,3,2);
                            if($withdrawApply && $withdrawApply['status'] == 1){
                                $updata = [];
                                $updata['status']   = 1;
                                $updata['allinpayorderNo'] = $withdrawApply['data']['orderNo'];
                                Db::name('business_withdrawlog')->where('id',$recordid)->update($updata);
                                return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款','data'=>[]]);
                            }else{
                                $msg = $withdrawApply && $withdrawApply['msg']?$withdrawApply['msg']:'';
                                Db::name('business_withdrawlog')->where('id',$recordid)->update(['reason'=>'通联提现失败'.$msg]);
                                return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
                            }
                        }else{
                            $msg = $applicationTransfer && $applicationTransfer['msg']?$applicationTransfer['msg']:'';
                            Db::name('business_withdrawlog')->where('id',$recordid)->update(['reason'=>'通联转账失败'.$msg]);
                            return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
                        }
                    }
                }
            }

            if($set['commission_autotransfer']==1 && $wx_transfer_type==1 ){
                if($post['paytype']=='微信' || $post['paytype']=='微信钱包') {
                    //使用了新版的商家转账功能
                    $mid = Db::name('admin_user')->where('aid',aid)->where('bid',bid)->where('isadmin',1)->value('mid');
                    if(!$mid) return $this->json(['status'=>0,'msg'=>'商户主管理员未绑定微信']);
                    $paysdk = new WxPayV3(aid, $mid, platform);
                    $rs = $paysdk->transfer($record['ordernum'], $record['money'], '', '余额提现', 'business_withdrawlog', $recordid);
                    if ($rs['status'] == 1) {
                        $data = [
                            'status' => '4',//状态改为处理中，用户确认收货后再改为已打款
                            'wx_package_info' => $rs['data']['package_info'],//用户确认页面的信息
                            'wx_state' => $rs['data']['state'],//转账状态
                            'wx_transfer_bill_no' => $rs['data']['transfer_bill_no'],//微信单号
                        ];
                        Db::name('business_withdrawlog')->where('id', $recordid)->update($data);
                        $need_confirm = 1;
                    } else {
                        $data = [
                        	'status' => 1,
                            'wx_transfer_msg' => $rs['msg'],
                        ];
                        Db::name('business_withdrawlog')->where('id', $recordid)->update($data);
                    }
                }
            }
			return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款','need_confirm'=>$need_confirm,'id'=>$recordid]);
		}
        if(getcustom('pay_allinpay')){
        	//检查企业会员信息，获取更新会员信息
            if($set['withdraw_bankcard_allinpayYunst'] == 1){
                $companyuser = Db::name('member_allinpay_yunst_companyuser')->where('mid',mid)->where('aid',aid)->find();
                if($companyuser){
                    //获取会员信息
                    \app\custom\AllinpayYunst::getMemberInfo($companyuser['aid'],$companyuser['bizUserId'],2,$companyuser);
                }
            }
        }
		$field  ='id,money,weixin,aliaccount,bankname,bankcarduser,bankcardnum';
        if(getcustom('alipay_auto_transfer')){
            $field.=',aliaccountname';
        }
        if(getcustom('business_withdraw_cash_mobile_duliset')){
            $field.=',withdrawfee_cash_status_type,withdrawfee_cash_status,withdrawfee_cash_rate';
        }
		$userinfo = db('business')->where(['id'=>bid])->field($field)->find();

		$rdata = [];
		$rdata['userinfo'] = $userinfo;

		if(getcustom('alipay_auto_transfer')){
			//提现说明
			$withdraw_desc = Db::name('admin_set')->where('aid',aid)->value('withdraw_desc');
			$set['withdraw_desc'] = $withdraw_desc?$withdraw_desc:'';
		}
        if(getcustom('admin_login_sms_verify')){
            $set['smscode'] = 1;
        }

        $business_withdraw_cash_mobile_duliset = getcustom('business_withdraw_cash_mobile_duliset');
        if(getcustom('business_withdraw_cash_mobile')){
            //提现手续费现金支付
            //独立设置
            if($business_withdraw_cash_mobile_duliset && $userinfo['withdrawfee_cash_status_type'] == 1){
                $set['withdrawfee_cash_status'] = $userinfo['withdrawfee_cash_status'];
                $set['withdrawfee_cash_rate'] = $userinfo['withdrawfee_cash_rate'];
            }
            $set['withdrawfee_cash_rate'] = sprintf('%g',$set['withdrawfee_cash_rate']);
        }
		$rdata['sysset'] = $set;
        $rdata['can_edit_account'] = 0;
        if(getcustom('business_withdraw_account')){
            $rdata['can_edit_account'] = 1;
        }
		return $this->json($rdata);
	}
	//余额宝明细
	function yuebaolog(){
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$order = 'id desc';
		$pernum = 20;
		$where = [];
		$where[] = ['member_moneylog.aid','=',aid];
		if(input('param.keyword')) $where[] = ['member.nickname','like','%'.trim(input('param.keyword')).'%'];
		if(input('?param.status') && input('param.status')!=='') $where[] = ['member_moneylog.status','=',input('param.status')];
		$datalist = Db::name('member_yuebao_moneylog')->alias('member_moneylog')->field('member.nickname,member.headimg,member_moneylog.*')->join('member member','member.id=member_moneylog.mid')->where($where)->page($pagenum,$pernum)->order($order)->select()->toArray();
		if($pagenum==1){
			$count = 0 + Db::name('member_yuebao_moneylog')->alias('member_moneylog')->field('member.nickname,member.headimg,member_moneylog.*')->join('member member','member.id=member_moneylog.mid')->where($where)->count();
		}
		return $this->json(['status'=>1,'count'=>$count,'data'=>$datalist]);
	}
	//余额宝提现记录
	function yuebaowithdrawlog(){
		$pagenum = input('post.pagenum');
		$st = input('post.st');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['withdrawlog.aid','=',aid];
		if($st == 'all'){

		}elseif($st == '0'){
			$where[] = ['status','=',0];
		}elseif($st == '1'){
			$where[] = ['status','=',1];
		}elseif($st == '2'){
			$where[] = ['status','=',2];
		}elseif($st == '3'){
			$where[] = ['status','=',3];
		}

		if(input('param.keyword')) $where[] = ['member.nickname','like','%'.trim(input('param.keyword')).'%'];
		if(input('?param.status') && input('param.status')!=='') $where[] = ['withdrawlog.status','=',input('param.status')];
		$datalist = Db::name('member_yuebao_withdrawlog')->alias('withdrawlog')->field('member.nickname,member.headimg,withdrawlog.*')->join('member member','member.id=withdrawlog.mid')->where($where)->page($pagenum,$pernum)->order('withdrawlog.id desc')->select()->toArray();
		if($pagenum==1){
			$count = 0 + Db::name('member_yuebao_withdrawlog')->alias('withdrawlog')->field('member.nickname,member.headimg,withdrawlog.*')->join('member member','member.id=withdrawlog.mid')->where($where)->count();
		}
		return $this->json(['status'=>1,'count'=>$count,'data'=>$datalist]);
	}
	//余额宝提现明细
	function yuebaowithdrawdetail(){
		$id = input('param.id/d');
		$info = Db::name('member_yuebao_withdrawlog')->where(['aid'=>aid,'id'=>$id])->find();
		$member = Db::name('member')->where(['id'=>$info['mid']])->find();
		$info['nickname'] = $member['nickname'];
		$info['headimg'] = $member['headimg'];
		return $this->json(['status'=>1,'info'=>$info]);
	}
	//余额宝提现审核通过
	function yuebaowithdrawpass(){
		$id = input('post.id/d');
		$info = Db::name('member_yuebao_withdrawlog')->where(['aid'=>aid,'id'=>$id])->update(['status'=>1,'reason'=>'']);
		return $this->json(['status'=>1,'msg'=>'操作成功']);
	}
	//余额宝提现审核不通过
	function yuebaowithdrawnopass(){
		$id = input('post.id/d');
		$reason = input('post.reason');
		Db::name('member_yuebao_withdrawlog')->where(['aid'=>aid,'id'=>$id])->update(['status'=>2,'reason'=>$reason]);
		$info = Db::name('member_yuebao_withdrawlog')->where(['aid'=>aid,'id'=>$id])->find();
		\app\common\Member::addyuebaomoney(aid,$info['mid'],$info['txmoney'],t('余额宝').'收益提现返还',4);
		//提现失败通知
		$tmplcontent = [];
		$tmplcontent['first'] = '您的提现申请被商家驳回，可与商家协商沟通。';
		$tmplcontent['remark'] = $reason.'，请点击查看详情~';
		$tmplcontent['money'] = (string) $info['txmoney'];
		$tmplcontent['time'] = date('Y-m-d H:i',$info['createtime']);
		\app\common\Wechat::sendtmpl(aid,$info['mid'],'tmpl_tixianerror',$tmplcontent,m_url('/pages/my/usercenter'));
		//订阅消息
		$tmplcontent = [];
		$tmplcontent['amount1'] = $info['txmoney'];
		$tmplcontent['time3'] = date('Y-m-d H:i',$info['createtime']);
		$tmplcontent['thing4'] = $reason;

		$tmplcontentnew = [];
		$tmplcontentnew['thing1'] = '提现失败';
		$tmplcontentnew['amount2'] = $info['txmoney'];
		$tmplcontentnew['date4'] = date('Y-m-d H:i',$info['createtime']);
		$tmplcontentnew['thing12'] = $reason;
		\app\common\Wechat::sendwxtmpl(aid,$info['mid'],'tmpl_tixianerror',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
		//短信通知
		$member = Db::name('member')->where(['id'=>$info['mid']])->find();
		if($member['tel']){
			\app\common\Sms::send(aid,$member['tel'],'tmpl_tixianerror',['reason'=>$reason]);
		}
		return $this->json(['status'=>1,'msg'=>'操作成功']);
	}
	//余额宝提现改为打款
	function yuebaowidthdsetydk(){
		$id = input('post.id/d');
		Db::name('member_yuebao_withdrawlog')->where(['aid'=>aid,'id'=>$id])->update(['status'=>3,'reason'=>'']);
		$info = Db::name('member_yuebao_withdrawlog')->where(['aid'=>aid,'id'=>$id])->find();
        $info['money'] = dd_money_format($info['money']);
		//提现成功通知
		$tmplcontent = [];
		$tmplcontent['first'] = '您的提现申请已打款，请留意查收';
		$tmplcontent['remark'] = '请点击查看详情~';
		$tmplcontent['money'] = (string) $info['money'];
		$tmplcontent['timet'] = date('Y-m-d H:i',$info['createtime']);
        $tempconNew = [];
        $tempconNew['amount2'] = (string) $info['money'];//提现金额
        $tempconNew['time3'] = date('Y-m-d H:i',$info['createtime']);//提现时间
		\app\common\Wechat::sendtmpl(aid,$info['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'),$tempconNew);
		//订阅消息
		$tmplcontent = [];
		$tmplcontent['amount1'] = $info['money'];
		$tmplcontent['thing3'] = $info['paytype'];
		$tmplcontent['time5'] = date('Y-m-d H:i');
		
		$tmplcontentnew = [];
		$tmplcontentnew['amount3'] = $info['money'];
		$tmplcontentnew['phrase9'] = $info['paytype'];
		$tmplcontentnew['date8'] = date('Y-m-d H:i');
		\app\common\Wechat::sendwxtmpl(aid,$info['mid'],'tmpl_tixiansuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
		//短信通知
		$member = Db::name('member')->where(['id'=>$info['mid']])->find();
		if($member['tel']){
			\app\common\Sms::send(aid,$member['tel'],'tmpl_tixiansuccess',['money'=>$info['money']]);
		}
		return $this->json(['status'=>1,'msg'=>'操作成功']);
	}
	//余额宝提现 微信打款
	function yuebaowidthdwxdakuan(){
		$id = input('post.id/d');
		$info = Db::name('member_yuebao_withdrawlog')->where(['aid'=>aid,'id'=>$id])->find();
		if($info['status']!=1) return $this->json(['status'=>0,'msg'=>'已审核状态才能打款']);
		$info['money'] = dd_money_format($info['money']);
		$rs = \app\common\Wxpay::transfers(aid,$info['mid'],$info['money'],$info['ordernum'],$info['platform'],t('余额宝').'提现');
		if($rs['status']==0){
			return $this->json(['status'=>0,'msg'=>$rs['msg']]);
		}else{
			Db::name('member_yuebao_withdrawlog')->where(['aid'=>aid,'id'=>$id])->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['resp']['payment_no']]);
			//提现成功通知
			$tmplcontent = [];
			$tmplcontent['first'] = '您的提现申请已打款，请留意查收';
			$tmplcontent['remark'] = '请点击查看详情~';
			$tmplcontent['money'] = (string) $info['money'];
			$tmplcontent['timet'] = date('Y-m-d H:i',$info['createtime']);
            $tempconNew = [];
            $tempconNew['amount2'] = (string) $info['money'];//提现金额
            $tempconNew['time3'] = date('Y-m-d H:i',$info['createtime']);//提现时间
			\app\common\Wechat::sendtmpl(aid,$info['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'),$tempconNew);
			//订阅消息
			$tmplcontent = [];
			$tmplcontent['amount1'] = $info['money'];
			$tmplcontent['thing3'] = $info['paytype'];
			$tmplcontent['time5'] = date('Y-m-d H:i');
			
			$tmplcontentnew = [];
			$tmplcontentnew['amount3'] = $info['money'];
			$tmplcontentnew['phrase9'] = $info['paytype'];
			$tmplcontentnew['date8'] = date('Y-m-d H:i');
			\app\common\Wechat::sendwxtmpl(aid,$info['mid'],'tmpl_tixiansuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
			//短信通知
			$member = Db::name('member')->where(['id'=>$info['mid']])->find();
			if($member['tel']){
				$tel = $member['tel'];
				\app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$info['money']]);
			}
			return $this->json(['status'=>1,'msg'=>$rs['msg']]);
		}
	}
	
	public function scorelog(){
		$pagenum = input('post.pagenum');
        $st = input('post.st');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['bid','=',bid];
		$datalist = Db::name('business_scorelog')->field('id,score,after,remark,from_unixtime(createtime)createtime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = [];
        $businessTransfer = false;
        $businessScoreWithdraw = false;
        $business = [];
        if($pagenum == 1){
            $business= Db::name('business')->where('id',bid)->find();
            if(getcustom('score_business_to_member')){
                $businessTransfer = true;
            }
            //是否可提现
            if(getcustom('business_score_withdraw')){
                $bset = Db::name('business_sysset')->where('aid',aid)->find();
                if($bset['business_score_withdraw']==1){
                    $businessScoreWithdraw = true;
                }
            }
        }
		return $this->json(['status'=>1,'data'=>$datalist,'myscore'=>$business['score'],'businessTransfer'=>$businessTransfer,'scoreWithdraw'=>$businessScoreWithdraw]);
	}


	//门店余额明细
	public function mdmoneylog(){
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['mdid','=',$this->user['mdid']];
		$datalist = Db::name('mendian_moneylog')->field("id,money,`after`,createtime,remark")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = [];
		return $this->json(['status'=>1,'data'=>$datalist]);
	}
	//门店余额提现记录
	public function mdwithdrawlog(){
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['mdid','=',$this->user['mdid']];
		$datalist = Db::name('mendian_withdrawlog')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = [];
		return $this->json(['status'=>1,'data'=>$datalist]);
	}
	//提现信息设置
	public function mdtxset(){
		if(request()->isPost()){
			$postinfo = input('post.');
			$data = [];
			$data['weixin'] = $postinfo['weixin'];
			$data['aliaccountname'] = $postinfo['aliaccountname'];
			$data['aliaccount'] = $postinfo['aliaccount'];
			$data['bankname'] = $postinfo['bankname'];
			$data['bankcarduser'] = $postinfo['bankcarduser'];
			$data['bankcardnum'] = $postinfo['bankcardnum'];
			Db::name('mendian')->where('id',$this->user['mdid'])->update($data);
			return $this->json(['status'=>1,'msg'=>'保存成功']);
		}
		$info = Db::name('mendian')->field('id,weixin,aliaccountname,aliaccount,bankname,bankcarduser,bankcardnum')->where(['id'=>$this->user['mdid']])->find();
		return $this->json(['status'=>1,'info'=>$info]);
	}
	public function mdwithdraw(){
        $field = 'withdrawmin,withdraw_weixin,withdraw_aliaccount,withdraw_bankcard,withdraw_autotransfer';
        if(getcustom('mendian_money_transfer')){
            $field .= ',mendian_money_transfer';
        }
		$set = Db::name('admin_set')->where(['aid'=>aid])->field($field)->find();
		$mendian = Db::name('mendian')->where('id',$this->user['mdid'])->find();
		if(getcustom('mendian_usercenter')){
		    $withdrawfee = Db::name('mendian_set')->where('aid',aid)->value('withdrawfee');
            $mendian['withdrawfee'] = $mendian['withdrawfee']>0?$mendian['withdrawfee']:$withdrawfee;
        }
		$set['withdrawfee'] = $mendian['withdrawfee'];
		if(request()->isPost()){
			$post = input('post.');
			if($post['paytype']=='支付宝' && $mendian['aliaccount']==''){
				return $this->json(['status'=>0,'msg'=>'请先设置支付宝账号']);
			}
			if($post['paytype']=='银行卡' && ($mendian['bankname']==''||$mendian['bankcarduser']==''||$mendian['bankcardnum']=='')){
				return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
			}

			$money = $post['money'];
			if($money<=0 || $money < $set['withdrawmin']){
				return $this->json(['status'=>0,'msg'=>'提现金额必须大于'.($set['withdrawmin']?$set['withdrawmin']:0)]);
			}
			if($money > $mendian['money']){
				return $this->json(['status'=>0,'msg'=>'可提现余额不足']);
			}
			if(empty($this->user['mid'])){
				return $this->json(['status'=>0,'msg'=>'此账号未绑定用户，请绑定']);
			}
			$ordernum = date('ymdHis').aid.rand(1000,9999);
			$record['aid'] = aid;
			$record['bid'] = $mendian['bid'];
			$record['mid'] = $this->user['mid'];
			$record['mdid'] = $mendian['id'];
			$record['createtime']= time();
			$record['money'] =dd_money_format( $money*(1-$set['withdrawfee']*0.01));
			$record['txmoney'] = $money;
			$record['ordernum'] = $ordernum;
			$record['paytype'] = $post['paytype'];
			$record['platform'] = platform;
			if(empty($mendian['bid']) && ($post['paytype']=='微信' || $post['paytype']=='微信钱包')){
				if($set['commission_autotransfer']==1){
					if($record['bid']>0){
		                //查询多商户的金额
		                $business = Db::name('business')->where('id',$record['bid'])->where('aid',aid)->field('money')->find();
		                if($business['money']<$record['money']){
		                    return $this->json(['status'=>0,'msg'=>'提现失败，商户余额不足']);
		                }
		            }

					$res = \app\common\Mendian::addmoney(aid,$mendian['id'],-$money,'余额提现');
					if(!$res || ($res && $res['status'] !=1)){
		                \think\facade\Log::write('Mendianwithdrawfail_'.$mendian['id'].'_'.$money);
		                return json(['status'=>0,'msg'=>'提现失败']);
		            }

					$mid = $this->user['mid'];
					if(!$mid) return $this->json(['status'=>0,'msg'=>'未绑定微信']);
					$rs = \app\common\Wxpay::transfers(aid,$mid,$record['money'],$record['ordernum'],'','余额提现');
					if($rs['status']==0){
						\app\common\Mendian::addmoney(aid,$mendian['id'],$money,'余额提现失败返还');
						return $this->json(['status'=>0,'msg'=>$rs['msg']]);
					}else{
						$record['weixin'] = t('会员').'ID：'.$mid;
						$record['status'] = 3;
						$record['paytime'] = time();
						$record['paynum'] = $rs['resp']['payment_no'];
						$id = db('mendian_withdrawlog')->insertGetId($record);

						//提现成功通知
						$tmplcontent = [];
						$tmplcontent['first'] = '您的提现申请已打款，请留意查收';
						$tmplcontent['remark'] = '请点击查看详情~';
						$tmplcontent['money'] = (string) $record['money'];
						$tmplcontent['timet'] = date('Y-m-d H:i',$record['createtime']);
                        $tempconNew = [];
                        $tempconNew['amount2'] = (string) $record['money'];//提现金额
                        $tempconNew['time3'] = date('Y-m-d H:i',$record['createtime']);//提现时间
						\app\common\Wechat::sendtmpl(aid,$mid,'tmpl_tixiansuccess',$tmplcontent,m_url('admin/index/index'),$tempconNew);
						//短信通知
						$member = Db::name('member')->where('id',$mid)->find();
						if($member['tel']){
							$tel = $member['tel'];
							\app\common\Sms::send(aid,$tel,'tmpl_tixiansuccess',['money'=>$record['money']]);
						}
						return $this->json(['status'=>1,'msg'=>$rs['msg']]);
					}
				}
				if($mendian['weixin']==''){
					return $this->json(['status'=>0,'msg'=>'请填写完整提现信息','url'=>'mdtxset']);
				}
				$record['weixin'] = $mendian['weixin'];
			}else{
				if($post['paytype']=='微信' || $post['paytype']=='微信钱包'){
					if($mendian['weixin']==''){
						return $this->json(['status'=>0,'msg'=>'请填写完整提现信息','url'=>'mdtxset']);
					}
					$record['weixin'] = $mendian['weixin'];
				}
			}
			if($post['paytype']=='支付宝'){
				$record['aliaccountname'] = $mendian['aliaccountname'];
				$record['aliaccount'] = $mendian['aliaccount'];
			}
			if($post['paytype']=='银行卡'){
				$record['bankname'] = $mendian['bankname'];
				$record['bankcarduser'] = $mendian['bankcarduser'];
				$record['bankcardnum'] = $mendian['bankcardnum'];
			}

			$res = \app\common\Mendian::addmoney(aid,$mendian['id'],-$money,'余额提现');
			if(!$res || ($res && $res['status'] !=1)){
                \think\facade\Log::write('Mendianwithdrawfail_'.$mendian['id'].'_'.$money);
                return json(['status'=>0,'msg'=>'提现失败']);
            }
			$recordid = db('mendian_withdrawlog')->insertGetId($record);

			return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
		}
		$userinfo = db('mendian')->where(['id'=>$mendian['id']])->field('id,money,weixin,aliaccount,aliaccountname,bankname,bankcarduser,bankcardnum')->find();
		$rdata = [];
		$rdata['userinfo'] = $userinfo;
		$rdata['sysset'] = $set;
		return $this->json($rdata);
	}
    public function bscorewithdraw()
    {
        if(getcustom('business_score_withdraw')) {
            $adminset = Db::name('admin_set')->where('aid', aid)->field('aid,score2money')->find();
            $set = Db::name('business_sysset')->where(['aid' => aid])->field('business_score_withdraw,business_score_withdrawfee,withdraw_weixin,withdraw_aliaccount,withdraw_bankcard,commission_autotransfer')->find();
            $set['score2money'] = $exchangeRate = $adminset['score2money'] ?? 0.01;
            if (request()->isPost()) {
                $post = input('post.');
                if ($set['business_score_withdraw'] == 0) {
                    return ['status' => 0, 'msg' => t('积分') . '提现功能未开启'];
                }
                $binfo = Db::name('business')->where('id', bid)->find();
                if ($post['paytype'] == '支付宝' && $binfo['aliaccount'] == '') {
                    return $this->json(['status' => 0, 'msg' => '请先设置支付宝账号']);
                }
                if ($post['paytype'] == '银行卡' && ($binfo['bankname'] == '' || $binfo['bankcarduser'] == '' || $binfo['bankcardnum'] == '')) {
                    return $this->json(['status' => 0, 'msg' => '请先设置完整银行卡信息']);
                }

                $score = $post['score'];
                if ($score > $binfo['score']) {
                    return $this->json(['status' => 0, 'msg' => '可提现' . t('积分') . '不足']);
                }
                if($binfo['score_withdrawfee_type'] == 1){
                    //积分提现手续费 读取独立设置
                    $set['business_score_withdrawfee'] = $binfo['score_withdrawfee'];
                }
                $money = round($score * $exchangeRate, 2);
                $ordernum = date('ymdHis') . aid . rand(1000, 9999);
                $record['aid'] = aid;
                $record['bid'] = bid;
                $record['createtime'] = time();
                $record['score'] = $score;
                $record['money'] = dd_money_format($money * (1 - $set['business_score_withdrawfee'] * 0.01));
                $record['txmoney'] = $money;
                $record['ordernum'] = $ordernum;
                $record['paytype'] = $post['paytype'];
                if ($post['paytype'] == '微信' || $post['paytype'] == '微信钱包') {
                    if ($set['commission_autotransfer'] == 1) {
                        \app\common\Business::addscore(aid, bid, -$score, t('积分') . '提现');
                        $mid = Db::name('admin_user')->where('aid', aid)->where('bid', bid)->where('isadmin', 1)->value('mid');
                        if (!$mid) return $this->json(['status' => 0, 'msg' => '商户主管理员未绑定微信']);
                        $rs = \app\common\Wxpay::transfers(aid, $mid, $record['money'], $record['ordernum'], '', '余额提现');
                        if ($rs['status'] == 0) {
                            \app\common\Business::addscore(aid, bid, $score, t('积分') . '提现失败返还');
                            return $this->json(['status' => 0, 'msg' => $rs['msg']]);
                        } else {
                            $record['weixin'] = t('会员') . 'ID：' . $mid;
                            $record['status'] = 3;
                            $record['paytime'] = time();
                            $record['paynum'] = $rs['resp']['payment_no'];
                            $id = db('business_score_withdrawlog')->insertGetId($record);

                            //提现成功通知
                            $tmplcontent = [];
                            $tmplcontent['first'] = '您的提现申请已打款，请留意查收';
                            $tmplcontent['remark'] = '请点击查看详情~';
                            $tmplcontent['money'] = (string)$record['money'];
                            $tmplcontent['timet'] = date('Y-m-d H:i', $record['createtime']);
                            $tempconNew = [];
                            $tempconNew['amount2'] = (string)$record['money'];//提现金额
                            $tempconNew['time3'] = date('Y-m-d H:i', $record['createtime']);//提现时间
                            \app\common\Wechat::sendtmpl(aid, $mid, 'tmpl_tixiansuccess', $tmplcontent, m_url('admin/index/index'), $tempconNew);
                            //短信通知
                            $member = Db::name('member')->where('id', $mid)->find();
                            if ($member['tel']) {
                                $tel = $member['tel'];
                                \app\common\Sms::send(aid, $tel, 'tmpl_tixiansuccess', ['money' => $record['money']]);
                            }
                            return $this->json(['status' => 1, 'msg' => $rs['msg']]);
                        }
                    }
    //                if($binfo['weixin']==''){
    //                    return $this->json(['status'=>0,'msg'=>'请填写完整提现信息','url'=>'txset']);
    //                }
    //                $record['weixin'] = $binfo['weixin'];
                }
                if ($post['paytype'] == '支付宝') {
                    $record['aliaccount'] = $binfo['aliaccount'];
                }
                if ($post['paytype'] == '银行卡') {
                    $record['bankname'] = $binfo['bankname'];
                    $record['bankcarduser'] = $binfo['bankcarduser'];
                    $record['bankcardnum'] = $binfo['bankcardnum'];
                }
                $recordid = db('business_score_withdrawlog')->insertGetId($record);

                \app\common\Business::addscore(aid, bid, -$score, t('积分') . '提现', false, 'business_score_withdrawlog', $ordernum);

                return $this->json(['status' => 1, 'msg' => '提交成功,请等待打款']);
            }
            $userinfo = db('business')->where(['id' => bid])->field('id,score,weixin,aliaccount,bankname,bankcarduser,bankcardnum')->find();

            $rdata = [];
            $rdata['userinfo'] = $userinfo;
            $rdata['sysset'] = $set;
            return $this->json($rdata);
        }
    }
    //商家积分提现记录
    public function bscorewithdrawlog(){
        if(getcustom('business_score_withdraw')) {
            $pagenum = input('post.pagenum');
            if (!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['aid', '=', aid];
            $where[] = ['bid', '=', bid];
            if(is_numeric(input('param.st'))) $where[] = ['status', '=', input('param.st/d')];
            $datalist = Db::name('business_score_withdrawlog')->where($where)->page($pagenum, $pernum)->order('id desc')->select()->toArray();
            foreach ($datalist as $k=>$v){
                $datalist[$k]['showtime'] = date('Y-m-d H:i:s',$v['createtime']);
                $datalist[$k]['reason'] = $v['reason']??'';
            }
            if (!$datalist) $datalist = [];
            return $this->json(['status' => 1, 'data' => $datalist]);
        }
    }
    //所有的支付方式
	public function getpaytypelist(){
         $list = [
             ['id' => '1','title' => '余额支付'],
             ['id' => '2','title' => '微信支付'],
             ['id' => '3','title' => '支付宝支付'],
         ];
         if(getcustom('restaurant_shop_cashdesk')){
             $list[] = ['id' => '0','title' => '现金收款-餐饮收银台'];
         }
        if(getcustom('cashdesk_sxpay')){
            $list[] = ['id' => '81','title' => '随行付收款-餐饮收银台'];
        }
        if(getcustom('restaurant_douyin_qrcode_hexiao')){
            $list[] = ['id' => '121','title' => '抖音团购券-餐饮收银台'];
        }
        if(getcustom('restaurant_cashdesk_custom_pay')){
            $custom_paylist = Db::name('restaurant_cashdesk_custom_pay')->where('aid',aid)->where('bid',bid)->where('status',1)->order('sort desc,id desc')->select()->toArray();
            foreach($custom_paylist as $ck=>$cv){
                $list[] = ['id' => 10000+ $cv['id'],'title' => $cv['title'].'-餐饮收银台'];
            }
        }
        return $this->json(['status'=>1,'data'=>$list]);
    }
    public function gettradereport(){
	    if(getcustom('finance_trade_report')){
            $other['datetype'] ='today';
            $paytypeid = input('param.paytypeid');
            if($paytypeid !='')$other['search_paytype'] =  $paytypeid;
            $ctime = input('param.ctime');
            if($ctime){
                $other['starttime'] =$ctime[0];
                $other['endtime'] =$ctime[1];
                $other['datetype'] ='custom';
            }
            $data = \app\model\Payorder::tradeReport(aid,bid,0,2,$other);
            $isprint = input('param.isprint');
            if($isprint){
                \app\common\Wifiprint::jiaobanPrint($data);
                return $this->json(['status'=>1,'msg'=>'打印成功']);
            }
            return $this->json(['status'=>1,'data'=>$data]);
        }
    }
   
    public function depositloglist(){
	    if(getcustom('business_deposit')){
            $pagenum = input('post.pagenum');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $datalist = Db::name('business_depositlog')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            $count =  Db::name('business_depositlog')->field('id,after,remark,from_unixtime(createtime)createtime')->where($where)->count();
            $business = Db::name('business')->where('aid',aid)->where('id', bid)->find();
            $deposit_refund =Db::name('business_sysset')->where('aid',aid)->value('business_deposit_refund');
            $business_deposit_refund = 0;
            if($deposit_refund && $business['deposit'] > 0){
                $business_deposit_refund = 1;
            }
            $refund_status = 0;
            //申请状态
            $order = Db::name('business_deposit_order')->where('aid',aid)->where('bid',bid)->find();
            if($order){
                $refund_status = $order['refund_status'];
            }
            return $this->json(['status'=>1,'data'=>$datalist,'deposit' => $business['deposit'],'count' => $count,'business_deposit_refund' => $business_deposit_refund,'refund_status' => $refund_status]);
        }
    }
    public function depositrefund(){
	    if(getcustom('business_deposit_refund')){
            $business = Db::name('business')->where('aid',aid)->where('id', bid)->find();
            if(!$business['deposit']){
                return $this->json(['status'=>1,'msg'=>'保证金不足']);
            }
            $order = Db::name('business_deposit_order')->where('aid',aid)->where('bid',bid)->find();
            $money = $business['deposit'];
            if(!$order){
                $ordernum = date('ymdHis').aid.rand(1000,9999);
                $orderdata = [];
                $orderdata['aid'] = aid;
                $orderdata['mid'] = mid;
                $orderdata['bid'] = bid;
                $orderdata['createtime']= time();
                $orderdata['money'] = $money;
                $orderdata['ordernum'] = $ordernum;
                $orderdata['refund_status'] = 1;
                $orderdata['refund_time'] = time();
                $orderid = Db::name('business_deposit_order')->insertGetId($orderdata);
            }else{
                if($order['refund_status'] ==1){
                    return $this->json(['status'=>1,'msg'=>'申请成功，等待审核']);
                }
                $order['refund_status'] = 1;
                $order['refund_time'] = time();
                Db::name('business_deposit_order')->where('id',$order['id'])->update($order);
            }
            return $this->json(['status'=>1,'msg'=>'申请成功，等待审核']);
        }
    }

    /**
     * 门店余额转账
     * 定制功能 开发文档：https://doc.weixin.qq.com/doc/w3_AT4AYwbFACw48hyU006QESmU9gvsS?scode=AHMAHgcfAA0Lo2NuezAeYAOQYKALU
     * @author: liud
     * @time: 2024/11/11 上午11:23
     */
    public function transferMendianMoney()
    {
        if(getcustom('mendian_money_transfer')) {
            $mid = input('post.mid/d');
            $mendian = Db::name('mendian')->where('id',$this->user['mdid'])->find();
            $set = Db::name('admin_set')->where('aid',aid)->find();
            if(!$set['mendian_money_transfer']){
                return $this->json(['status'=>0,'msg'=>'功能未开启']);
            }
            if(request()->isPost()){
                $mobile = input('post.mobile');
                $mid = input('post.mid/d');
                $money = input('post.money/f');
                if ($money < 0.01){
                    return $this->json(['status'=>0,'msg'=>'请输入正确的金额，最小金额为：0.01']);
                }

                if (input('?post.mid') && $mid > 0) {
                    $member = Db::name('member')->where('aid', aid)->where('id', $mid)->find();
                }
                if(!$member) return $this->json(['status'=>0,'msg'=>'未找到该'.t('会员')]);
                $user_id = $member['id'];

//                if ($user_id == $business['mid']) {
//                    return $this->json(['status'=>0,'msg'=>'不能转账给自己']);
//                }
                if ($money > $mendian['money']){
                    return $this->json(['status'=>0,'msg'=>'您的'.t('余额').'不足']);
                }

                $midMsg = sprintf("转给：%s",$member['nickname']);
                $toMidMsg = sprintf("来自门店%s的转账", $mendian["name"]);
                //减去门店余额
                $rs = \app\common\Mendian::addmoney(aid,$mendian['id'],$money * -1, $midMsg);
                if ($rs['status'] == 1) {
                    \app\common\Member::addmoney(aid,$user_id,$money,$toMidMsg,$mendian["id"]);
                }else{
                    return $this->json(['status'=>0, 'msg' => '转账失败']);
                }
                \app\common\System::plog('移动端后台'.$toMidMsg.$midMsg);
                return $this->json(['status'=>1, 'msg' => '转账成功', 'url'=>'/admin/finance/mdwithdraw']);
            }
            $tomember = [];
            if($mid){
                $tomember = Db::name('member')->where('aid',aid)->where('id',$mid)->field('id,money,nickname,headimg')->find();
            }
            $rdata['status'] = 1;
            $rdata['mymoney'] = $mendian['money'];
            $rdata['moneyList'] = [];//可选金额列表
            $rdata['tomember'] = $tomember?$tomember:['nickname'=>''];//转给谁
            return $this->json($rdata);
        }
    }

    //金币明细
    function goldlog(){
        if(getcustom('bonus_pool_gold')){
            $pagenum = input('post.pagenum');
            if(!$pagenum) $pagenum = 1;
            $order = 'id desc';
            $pernum = 20;
            $where = [];
            $where[] = ['l.aid','=',aid];
            $where[] = ['l.mid','=',bid];
            $where[] = ['l.is_business','=',1];
            $datalist = Db::name('member_gold_log')->alias('l')
                ->field('l.*')
                ->where($where)->page($pagenum,$pernum)->order($order)->select()->toArray();
            if($pagenum==1){
                $count = 0 + Db::name('member_gold_log')->alias('l')->field('l.*')->where($where)->count();
            }
            return $this->json(['status'=>1,'count'=>$count,'data'=>$datalist]);
        }
    }
    //金币兑换
    public function goldwithdraw(){
        if(getcustom('bonus_pool_gold')) {
            $field = '*';
            $set = Db::name('bonuspool_gold_set')->where(['aid' => aid])->field($field)->find();
            if (request()->isPost()) {
                $params = input('');
                $withdraw_num = $params['money'];
                $paytype = 'money';
                $set = Db::name('bonuspool_gold_set')->where('aid',aid)->find();
                $gold_price = $set['gold_price'];
                $gold_value = bcmul($withdraw_num,$gold_price,2);
                $cash_fee = bcmul($gold_value, $set['cash_fee'] / 100, 2);
                $real_gold_value = bcsub($gold_value, $cash_fee, 2);
                Db::startTrans();
                if($paytype=='money'){
                    $money = $real_gold_value;
                    //兑换余额
                    $res = \app\common\Business::addmoney(aid,bid,$money,t('金币').'兑换');
                }

                //插入提取记录表
                $log = [];
                $log['aid'] = aid;
                $log['mid'] = bid;
                $log['is_business'] = 1;
                $log['money'] = $gold_value?:0;
                $log['fee'] = $cash_fee;
                $log['gold_num'] = $withdraw_num;
                $log['gold_price'] = $gold_price;
                $log['to_commission'] = 0;
                $log['to_money'] = $money?:0;
                $log['to_score'] = 0;
                $log['remark'] = '兑换';
                $log['createtime'] = time();
                $log_id = Db::name('gold_withdraw_log')->insertGetId($log);
                //扣除金币
                $res = \app\custom\BonusPoolGold::addbusinessgold(aid,bid,-$withdraw_num,t('金币').'兑换','gold_withdraw_log',$log_id);
                if(!$res['status']){
                    return $this->json(['status'=>0,'msg'=>'兑换失败'.$res['msg']]);
                }
                //手续费回流奖金池
                $res = \app\custom\BonusPoolGold::addbonuspool(aid,0,$cash_fee,'商户'.bid.t('金币').'兑换手续费回流','gold_withdraw_log',$log_id,0,0,bid);
                Db::commit();
                return $this->json(['status'=>1,'msg'=>'兑换成功']);
            }
            $field = 'gold';
            $userinfo = db('business')->where(['id' => bid])->field($field)->find();

            $rdata = [];
            $rdata['userinfo'] = $userinfo;

            $rdata['sysset'] = $set;
            return $this->json($rdata);
        }
    }

    //余额转账
    public function btransfer(){
        $business = db('business')->where(array('id'=>bid))->find();
        $bset = db('business_sysset')->where(['aid'=>aid])->find();

        $info = input('post.');
        $money = floatval($info['money']);
        $tomid = trim($info['mid']);
        $type = $info['type'];

        $tomember = Db::name('member')->where('aid',aid)->where('id',$tomid)->find();
        if(!$tomember){
            return $this->json(['status'=>0,'msg'=>'转入会员不存在']);
        }
        
        if($business['money'] < $money) return $this->json(['status'=>0,'msg'=>'可转账余额不足']);
        // 实际到账 去掉手续费
        $tomoney = dd_money_format($money * (1-$bset['withdrawfee']*0.01));

        $res = \app\common\Business::addmoney(aid,bid,-$money,'余额转账');
        if ($res['status'] == 1 && $tomoney>0) {
            if($type == 'money'){
                \app\common\Member::addmoney(aid,$tomid,$tomoney,'商家'.$business['name'].'转账');
            }
            if($type == 'commission'){
                \app\common\Member::addcommission(aid,$tomid,0,$tomoney,'商家'.$business['name'].'转账');
            }
        }else{
            return $this->json(['status'=>0, 'msg' => '转账失败']);
        }
        return $this->json(['status'=>1,'msg'=>'转账成功']);

    }

    public function yunstCompanyuser()
    {
        if(getcustom('pay_allinpay')){
            $set = Db::name('allinpay_yunst_set')->where('aid',aid)->find();
            if(!$set || !$set['apiurl']){
                return $this->json(['status'=>0,'msg'=>'系统设置不存在']);
            }
            //直接创建通联会员
            $companyuser = Db::name('member_allinpay_yunst_companyuser')->where('mid',mid)->where('aid',aid)->find();
            // if($companyuser && $companyuser['status'] == 2 && ($companyuser['ocrIdcardComparisonResult'] != 1 || $companyuser['ocrRegnumComparisonResult'] != 1)){
            //     return $this->json(['status'=>0,'msg'=>'已创建成功','goback'=>true]);
            // }
            //通联会员
            if(request()->isPost()){
                $info = input('?param.info')?input('param.info/a'):[];
                if(!$info){
                    return $this->json(['status'=>0,'msg'=>'请填写会员信息']);
                }
                if(!$info['bizUserId']){
                    return $this->json(['status'=>0,'msg'=>'请输入会员名称']);
                }
                //查询是否添加过
		        $old = Db::name('member_allinpay_yunst_companyuser')->where('bizUserId',$info['bizUserId'])->field('id')->find();
		        if($old && $old['id'] !=$companyuser['id']){
		            return $this->json(['status'=>0,'msg'=>'此会员已有用户使用']);
		        }

                if(!$info['companyName']){
                    return $this->json(['status'=>0,'msg'=>'请输入企业名称']);
                }
                // if(!$info['comproperty'] || !in_array($info['comproperty'],[1,2,3])){
                //     return $this->json(['status'=>0,'msg'=>'请选择企业性质']);
                // }
                // if(!$info['authType'] || !in_array($info['authType'],[1,2])){
                //     return $this->json(['status'=>0,'msg'=>'请选择认证类型']);
                // }
                // if($info['authType'] == 2 && !$info['uniCredit']){
                //     return $this->json(['status'=>0,'msg'=>'请填写统一社会信用']);
                // }
                // if($info['authType'] == 1){
                //     if(!$info['businessLicense']){
                //         return $this->json(['status'=>0,'msg'=>'请填写营业执照号']);
                //     }
                //     if(!$info['organizationCode']){
                //         return $this->json(['status'=>0,'msg'=>'请填写组织机构代码']);
                //     }
                //     if(!$info['taxRegister']){
                //         return $this->json(['status'=>0,'msg'=>'请填写税务登记证']);
                //     }
                // }

                $info['jumpPageType'] = 1; //跳转页面类型  1-H5页面 2-小程序页面 兼容存量模式，不上送默认跳转H5页面
                if(platform == 'wx'){
                    $info['jumpPageType'] = 2;
                }

                $checkset = \app\custom\AllinpayYunst::checkset(aid);
	            if($checkset['status'] == 0){
	                return $this->json($checkset);
	            }
	            $set = $checkset['set'];

                //记录
                $data = [];
                $data = $info;
                if($companyuser){
                	if($info['id']) unset($info['id']);
                    $data['status'] = 0;
                    $data['updatetime'] = time();
                    $sql = Db::name('member_allinpay_yunst_companyuser')->where('id',$companyuser['id'])->update($data);
                    $dataid = $info['id'];
                }else{
                    $data['aid'] = aid;
                    $data['mid'] = mid;
                    $data['bid'] = bid;
                    $data['appId'] = $set['app_id'];
                    $data['createtime'] = time();
                    $dataid = $sql = Db::name('member_allinpay_yunst_companyuser')->insertGetId($data);
                }

                $createCompanyuser = \app\custom\AllinpayYunst::createCompanyuser(aid,$info,$set);
                if(!$createCompanyuser || $createCompanyuser['status'] != 1){
                    $msg = $createCompanyuser && $createCompanyuser['msg']?$createCompanyuser['msg']:'操作失败';
                    return $this->json(['status'=>0,'msg'=>$msg]);
                }
                if(!$createCompanyuser['data']){
                    return $this->json(['status'=>0,'msg'=>'数据异常']);
                }

                //记录
                $updata = [];
                $updata['status'] = 1;
                $updata['userId'] = $createCompanyuser['data']['userId']??'';
                $updata['regInviteLink'] = $createCompanyuser['data']['regInviteLink']??'';
                $updata['regInviteLinkEndTime'] = $createCompanyuser['data']['regInviteLinkEndTime']??'';
                if($updata['regInviteLinkEndTime']) $updata['regInviteLinkEndTime2'] = strtotime($updata['regInviteLinkEndTime']);
                $updata['regInviteparam'] = $createCompanyuser['param'] && !empty($createCompanyuser['param'])?json_encode($createCompanyuser['param'],JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT|JSON_UNESCAPED_SLASHES):'';
                $updata['createtime'] = time();
                Db::name('member_allinpay_yunst_companyuser')->where('id',$companyuser['id'])->update($updata);
                //查询是否添加过
                $count = Db::name('member_allinpay_yunst_user')->where('companyuserid',$dataid)->count('id');
               	if(!$count && !empty($updata['userId'])){
               		$data2 = [];
               		$data2['aid'] = aid;
                    $data2['mid'] = mid;
                    $data2['appId']     = $set['app_id'];
                    $data2['bizUserId'] = $info['bizUserId'];
                    $data2['userId']    = $updata['userId']??'';
                    $data2['companyuserid'] = $dataid;
                    $data2['memberType'] = 2;
                    $data2['createtime'] = time();
                    Db::name('member_allinpay_yunst_user')->insertGetId($data2);
               	}

                $regInviteLink = $updata['regInviteLink'];
                /*//处理小程序拼接参数字符串
                if($info['jumpPageType'] == 2 && $createCompanyuser['param']){
                    $reg = [];
                    $reg['method'] = "signContract";
                    $reg['param']  = $createCompanyuser['param'];
                    $reg['service']= "MemberService";
                    $regInviteLink .= "&reg=".json_encode($reg,JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT|JSON_UNESCAPED_SLASHES)."&";
                }*/
                return $this->json(['status'=>1,'msg'=>'创建成功','signstatus'=>2,'regInviteLink'=>$regInviteLink,'regInviteAppid'=>$set['allipayappid']]);
            }else{
                $rdata = [];
                $rdata['status'] = 1;

                $signstatus = 1;//提交注册
                if($companyuser && $companyuser['status'] == 1 && $companyuser['regInviteLinkEndTime2']>time()){
                    $signstatus = 2;//跳转认证页面
                }
                $rdata['info']       = $companyuser?$companyuser:'';
                $rdata['signstatus'] = $signstatus;
                if($signstatus == 2){
                    $regInviteLink = $companyuser['regInviteLink'];
                    /*//处理小程序拼接参数字符串
                    if(platform == 'wx' && $createCompanyuser['param']){
                        $info['jumpPageType'] = 2;
                        $reg = [];
                        $reg['method'] = "signContract";
                        $reg['param']  = $createCompanyuser['param'];
                        $reg['service']= "MemberService";
                        $regInviteLink .= "&reg=".json_encode($reg,JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT|JSON_UNESCAPED_SLASHES)."&";
                    }*/
                    $rdata['regInviteLink']  = $regInviteLink;
                    $rdata['regInviteAppid'] = $set['allipayappid'];
                }
                $rdata['cardCheck']  = 8;//8 银行卡四要素验证 无需调用【确认绑定银行卡】;
                $rdata['showAnew'] = true;
                return $this->json($rdata);
            }
        }
    }

    public function yunstCompanyuserAnew()
    {
        if(getcustom('pay_allinpay')){
            $set = Db::name('allinpay_yunst_set')->where('aid',aid)->find();
            if(!$set || !$set['apiurl']){
                return $this->json(['status'=>0,'msg'=>'系统设置不存在']);
            }
            //直接创建通联会员
            $companyuser = Db::name('member_allinpay_yunst_companyuser')->where('mid',mid)->where('aid',aid)->find();
            if(!$companyuser){
                return $this->json(['status'=>0,'msg'=>'企业会员信息不存在']);
            }
            // if($companyuser['status'] == 2){
            // 	return $this->json(['status'=>0,'msg'=>'已审核成功']);
            // }

            $updata = [];
            $updata['regInviteLink'] = '';
            $updata['regInviteLinkEndTime']  = '';
            $updata['regInviteLinkEndTime2'] = 0;
            $updata['updatetime'] = time();
            $sql = Db::name('member_allinpay_yunst_companyuser')->where('id',$companyuser['id'])->update($updata);
            if(!$sql) return $this->json(['status'=>0,'msg'=>'操作失败']);

            return $this->json(['status'=>1,'msg'=>'操作成功']);
        }
    }

    public function xianjinrechargelog(){
        if(getcustom('commission_xianjin_percent')){
            $pagenum = input('post.pagenum');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $order = 'id desc';
            $where = [];
            $where[] = ['xianjin_recharge_order.aid','=',aid];
            //$where[] = ['xianjin_recharge_order.status','=',1];
            $where[] = ['xianjin_recharge_order.paytype','<>','null'];
            if(input('param.keyword')) $where[] = ['member.nickname','like','%'.trim(input('param.keyword')).'%'];
            $datalist = Db::name('xianjin_recharge_order')->alias('xianjin_recharge_order')->field('member.nickname,member.headimg,xianjin_recharge_order.*')->join('member member','member.id=xianjin_recharge_order.mid')->where($where)->page($pagenum,$pernum)->order($order)->select()->toArray();
            if($pagenum==1){
                $count = 0 + Db::name('xianjin_recharge_order')->alias('xianjin_recharge_order')->field('member.nickname,member.headimg,xianjin_recharge_order.*')->join('member member','member.id=xianjin_recharge_order.mid')->where($where)->count();
            }
            foreach ($datalist as &$v){
            	$v['money_recharge_transfer'] = true;
                $v['payorder_check_status'] = Db::name('payorder')->where('aid',aid)->where('type','xianjin_recharge')->where('orderid',$v['id'])->value('check_status');
                if($v['status']==1){
                    $v['status_name'] = '充值成功';
                }else{
                    if($v['paytypeid'] == 5 && $v['paytype'] != '随行付支付'){
	                    if($v['transfer_check'] == 1){
	                        if($v['payorder_check_status'] == 2){
	                            $v['status_name'] = '凭证被驳回';
	                        }else if($v['payorder_check_status'] == 1){
	                            $v['status_name'] = '审核通过';
	                        }else{
	                            $v['status_name'] = '凭证待审核';
	                        }
	                    }else if($v['transfer_check'] == -1){
	                        $v['status_name'] = '已驳回';
	                    }else {
	                        $v['status_name'] = '待审核';
	                    }
	                }else{
	                    $v['status_name'] = '充值失败';
	                }
                }
            }
            $today_rechargemoney = 0;
            return $this->json(['status'=>1,'count'=>$count,'today_rechargemoney' =>$today_rechargemoney ,'data'=>$datalist]);
        }
    }
    //转账审核
    public function xianjinTransferCheck(){
        if(getcustom('commission_xianjin_percent')){
            $orderid = input('post.orderid/d');
            $st = input('post.st/d');

            $order = Db::name('xianjin_recharge_order')->where('id',$orderid)->where('aid',aid)->field('id,status')->find();
            if($order['status']!=0){
                return $this->json(['status'=>0,'msg'=>'该订单状态不允许审核']);
            }

            if($st==1){
                $up = Db::name('xianjin_recharge_order')->where('id',$orderid)->where('aid',aid)->update(['transfer_check'=>1]);
                if($up){
                    \app\common\System::plog('现金充值订单转账审核驳回'.$orderid);
                    return $this->json(['status'=>1,'msg'=>'审核通过']);
                }else{
                    return $this->json(['status'=>0,'msg'=>'操作失败']);
                }
            }else{
                $up = Db::name('xianjin_recharge_order')->where('id',$orderid)->where('aid',aid)->update(['transfer_check'=>-1]);
                if($up){
                    \app\common\System::plog('现金充值订单转账审核通过'.$orderid);
                    return $this->json(['status'=>1,'msg'=>'转账已驳回']);
                }else{
                    return $this->json(['status'=>0,'msg'=>'操作失败']);
                }
            }
        }
    }
    //付款审核
    public function xianjinPayCheck(){
        if(getcustom('commission_xianjin_percent')){
            $orderid = input('post.orderid/d');
            $st = input('post.st/d');
            $remark = input('post.remark');
            $order = Db::name('xianjin_recharge_order')->where('id',$orderid)->where('aid',aid)->find();

            if($order['status']!=0){
                return $this->json(['status'=>0,'msg'=>'该订单状态不允许审核付款']);
            }

            if($st==2){
                $check_data = [];
                $check_data['check_status'] = 2;
                if($remark){
                    $check_data['check_remark'] = $remark;
                }
                Db::name('payorder')->where('id',$order['payorderid'])->where('aid',aid)->where('type','xianjin_recharge')->update($check_data);
                \app\common\System::plog('现金充值订单付款审核驳回'.$orderid);
                return $this->json(['status'=>1,'msg'=>'付款已驳回']);
            }elseif($st == 1){
                $check_data = [];
                $check_data['check_status'] = 1;
                if($remark){
                    $check_data['check_remark'] = $remark;
                }
                \app\model\Payorder::payorder($order['payorderid'],t('转账汇款'),5,'');
                Db::name('payorder')->where('id',$order['payorderid'])->where('type','xianjin_recharge')->where('aid',aid)->update($check_data);

                Db::name('xianjin_recharge_order')->where('id',$orderid)->where('aid',aid)->update(['status'=>1,'paytime' => time()]);

                \app\common\System::plog('现金充值订单付款审核通过'.$orderid);
                return $this->json(['status'=>1,'msg'=>'审核通过']);
            }
        }
    }
    public function getxianjinrechargeorderdetail()
    {
        if(getcustom('commission_xianjin_percent')){
            $orderid = input('param.orderid');
            $order = Db::name('xianjin_recharge_order')->where('aid',aid)->where('id',$orderid)->find();
            $payorder = Db::name('payorder')->where('id',$order['payorderid'])->where('type','xianjin_recharge')->where('aid',aid)->find();
            if($order['paytypeid'] == 5) {
                if($payorder) {
                    if($payorder['check_status'] === 0) {
                        $payorder['check_status_label'] = '待审核';
                    }elseif($payorder['check_status'] == 1) {
                        $payorder['check_status_label'] = '通过';
                    }elseif($payorder['check_status'] == 2) {
                        $payorder['check_status_label'] = '驳回';
                    }else{
                        $payorder['check_status_label'] = '未上传';
                    }
                    if($payorder['paypics']) {
                        $payorder['paypics'] = explode(',', $payorder['paypics']);
                        foreach ($payorder['paypics'] as $item) {
                            $payorder['paypics_html'] .= '<img src="'.$item.'" width="200" onclick="preview(this)"/>';
                        }
                    }
                }
            }
            return $this->json(['status'=>1,'order'=>$order,'payorder' => $payorder]);
        }
    }

    public function xianjinlog(){
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$order = 'id desc';
		$pernum = 20;
		$where = [];
		$where[] = ['member_xianjinlog.aid','=',aid];
		if(input('param.keyword')) $where[] = ['member.nickname','like','%'.trim(input('param.keyword')).'%'];
		if(input('?param.status') && input('param.status')!=='') $where[] = ['member_xianjinlog.status','=',input('param.status')];
		$datalist = Db::name('member_xianjinlog')->alias('member_xianjinlog')->field('member.nickname,member.headimg,member_xianjinlog.*')->join('member member','member.id=member_xianjinlog.mid')->where($where)->page($pagenum,$pernum)->order($order)->select()->toArray();
		if($pagenum==1){
			$count = 0 + Db::name('member_xianjinlog')->alias('member_xianjinlog')->field('member.nickname,member.headimg,member_xianjinlog.*')->join('member member','member.id=member_xianjinlog.mid')->where($where)->count();
		}
		return $this->json(['status'=>1,'count'=>$count,'data'=>$datalist]);
    }

    //返现积分日汇总
    public function subsidyScoreDay(){
        if(getcustom('yx_buyer_subsidy')){
            $st = input('param.st');
            $pagenum = input('post.pagenum');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['is_business','=',1];
            $datalist = Db::name('member_subsidy_scoreday')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            foreach($datalist as $k=>$v){
                $moeny_weishu = 6;
                $datalist[$k]['score'] = dd_money_format($v['score'],$moeny_weishu);
//            $datalist[$k]['have_release'] = bcsub($v['score_total'],$v['score'],6);
                $have_release = Db::name('subsidy_bonus_log')->where('aid',aid)
                    ->where('bid', bid)
                    ->where('is_business', 1)
                    ->where('day_id',$v['id'])
                    ->sum('bonus');
                $datalist[$k]['have_release'] = $have_release?:0;
                $datalist[$k]['createday'] = date('Y-m-d',$v['createday']);
            }
            if($pagenum==1){
                $total = [];
                //我的返现积分
                $total['subsidy_score'] = Db::name('business')->where('id',bid)->value('subsidy_score');
                //累计释放
                $commission = Db::name('subsidy_bonus_log')->where($where)->sum('bonus');
                $total['commission'] = $commission;
                //今日释放
                $commission_today = Db::name('subsidy_bonus_log')->where($where)->whereTime('createtime', '>=',strtotime(date('Y-m-d')))
                    ->sum('bonus');
                $total['commission_today'] = $commission_today;
                //今日新增返现积分
                $today_add = Db::name('member_subsidy_scoreday')->where($where)->whereTime('createday', '>=',strtotime(date('Y-m-d')));
                $total['today_add'] = $today_add->sum('score_total');

                //累计让利
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['order_bid','=',bid];
                $total_rangli = Db::name('subsidy_order')->where($where)->sum('rangli');
                $total['total_rangli'] = dd_money_format($total_rangli,2);
            }

            return $this->json(['status'=>1,'datalist'=>$datalist,'total'=>$total]);
        }
    }

    //返现积分明细
    public function subsidyScoreLog(){
        if(getcustom('yx_buyer_subsidy')) {
            $st = input('param.st');
            $pagenum = input('post.pagenum');
            if (!$pagenum) $pagenum = 1;
            $pernum = 20;
            $day_id = input('day_id');
            $where = [];
            $where[] = ['aid', '=', aid];
            $where[] = ['bid', '=', bid];
            $where[] = ['is_business', '=', 1];
            if ($day_id) {
                $where[] = ['day_id', '=', $day_id];
            }
            $release_index = input('release_index');
            if ($release_index) {
                $where[] = ['release_num', '=', $release_index];
            }
            if ($st == 2) {//释放记录
                $field = '*';
                $datalist = Db::name('subsidy_score_release_log')->field($field)->where($where)->page($pagenum, $pernum)->order('id desc')->select()->toArray();
                if (!$datalist) $datalist = [];
                foreach ($datalist as $k => $v) {
                    $datalist[$k]['createtime'] = date('Y-m-d H:i:s', $v['createtime']);
                }
            } elseif ($st == 1) { //余额明细
                $datalist = Db::name('member_subsidy_scorelog')->field("id,score,`after`,from_unixtime(createtime) createtime,remark")->where($where)->page($pagenum, $pernum)->order('id desc')->select()->toArray();
                if (!$datalist) $datalist = [];
                foreach ($datalist as $k => $v) {
                    $moeny_weishu = 6;
                    $datalist[$k]['score'] = dd_money_format($v['score'], $moeny_weishu);
                    $datalist[$k]['after'] = dd_money_format($v['after'], $moeny_weishu);
                }
            }
            $score_commission = Db::name('subsidy_score_release_log')->where($where)->sum('member_score_bonus');
            if ($day_id) {
                $subsidy_score = Db::name('member_subsidy_scoreday')->where('id', $day_id)->value('score_total');
                $info = Db::name('member_subsidy_scoreday')->where('id', $day_id)->find();
                $remain_score = $info['score'];
            } else {
                $subsidy_score = Db::name('business')->where('id', bid)->value('subsidy_score');
                $remain_score = $subsidy_score;
            }
            //释放期数
            $set = Db::name('subsidy_set')->where('aid', aid)->find();
            $release_item = [];
            $release_max_day = $set['release_max_day'] > $set['release_max_day_b'] ? $set['release_max_day'] : $set['release_max_day_b'];
            $release_item[] = '全部';
            for ($i = 1; $i <= $release_max_day; $i++) {
                $release_item[] = '第' . $i . '期';
            }
            if($pagenum==1){
                $total = [];
                //我的返现积分
                $total['subsidy_score'] = Db::name('business')->where('id',bid)->value('subsidy_score');
                //累计释放
                $commission = Db::name('subsidy_bonus_log')->where($where)->sum('bonus');
                $total['commission'] = $commission;
                //今日释放
                $commission_today = Db::name('subsidy_bonus_log')->where($where)->whereTime('createtime', '>=',strtotime(date('Y-m-d')))
                    ->sum('bonus');
                $total['commission_today'] = $commission_today;
                //今日新增返现积分
                $today_add = Db::name('member_subsidy_scoreday')->where($where)->whereTime('createday', '>=',strtotime(date('Y-m-d')));
                $total['today_add'] = $today_add->sum('score_total');

                //累计让利
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['order_bid','=',bid];
                $total_rangli = Db::name('subsidy_order')->where($where)->sum('rangli');
                $total['total_rangli'] = dd_money_format($total_rangli,2);
            }
            return $this->json(['status' => 1, 'data' => $datalist, 'myscore' => $subsidy_score, 'score_commission' => $score_commission, 'remain_score' => $remain_score, 'release_item' => $release_item,'total'=>$total]);
        }
    }

    //汇付结算记录
    public function getHuifuIncomeLog(){
        if(getcustom('mobile_admin_huifu_settllement')){
            $pagenum = input('post.pagenum');
            if (!$pagenum) $pagenum = 1;
            $pernum = 20;
            $begin_date = input('param.begin_date');
            $end_date = input('param.end_date');
            $begin_date_time = strtotime($begin_date);
            $end_date_time =  strtotime($end_date);
            $diff_time =$end_date_time - $begin_date_time;
            $diff_day =  floor($diff_time/86400);
            if($diff_day >100){
                return $this->json(['status'=>0,'msg'=>'查询时间范围100天']);
            }
            $begin_date = $begin_date?date('Ymd',$begin_date_time):'';
            $end_date = $end_date?date('Ymd',$end_date_time):'';
            $business_huifu_id = Db::name('business')->where('aid',aid)->where('id',bid)->value('huifu_id');
            if(!$business_huifu_id)return $this->json(['status'=>0,'msg'=>'未开启该功能']);
            $huifuset = Db::name('sysset')->where('name','huifuset')->find();
            $appinfo = [];
            if($huifuset){
                $huifu_appinfo = json_decode($huifuset['value'],true);
                $appinfo['huifu_sys_id'] = $huifu_appinfo['huifu_sys_id'];//渠道商的huifu_id
                $appinfo['huifu_id'] = $business_huifu_id;//商户的huifu_id
                $appinfo['huifu_product_id'] = $huifu_appinfo['huifu_product_id'];
                $appinfo['huifu_merch_private_key'] = $huifu_appinfo['huifu_merch_private_key'];
                $appinfo['huifu_public_key'] =$huifu_appinfo['huifu_public_key'];
            }
            $huifu = new \app\custom\Huifu($appinfo, aid, bid);
            $rs = $huifu ->getSettlementQuery($business_huifu_id,$pagenum,$pernum,'S',$begin_date,$end_date);
            $datalist = [];
            if($rs['status'] ==1){
                $datalist = $rs['data'];
            }
            return $this->json(['status' => 1, 'data' => $datalist]);
        }
    }
    //汇付结算记录
    public function getHuifuSettementLog(){
        if(getcustom('mobile_admin_huifu_settllement')){
            $pagenum = input('post.pagenum');
            if (!$pagenum) $pagenum = 1;
            $pernum = 20;
            $begin_date = input('param.begin_date');
            $end_date = input('param.end_date');
            $begin_date_time = strtotime($begin_date);
            $end_date_time =  strtotime($end_date);
            $diff_time =$end_date_time - $begin_date_time; 
            $diff_day =  floor($diff_time/86400);
            if($diff_day >100){
                return $this->json(['status'=>0,'msg'=>'查询时间范围100天']);
            }
            $begin_date = $begin_date?date('Ymd',$begin_date_time):'';
            $end_date = $end_date?date('Ymd',$end_date_time):'';
            $st = input('param.st');
            $st = $st??'S';
            $business_huifu_id = Db::name('business')->where('aid',aid)->where('id',bid)->value('huifu_id');
            if(!$business_huifu_id)return $this->json(['status'=>0,'msg'=>'未开启该功能']);
            $huifuset = Db::name('sysset')->where('name','huifuset')->find();
            $appinfo = [];
            if($huifuset){
                $huifu_appinfo = json_decode($huifuset['value'],true);
                $appinfo['huifu_sys_id'] = $huifu_appinfo['huifu_sys_id'];//渠道商的huifu_id
                $appinfo['huifu_id'] = $business_huifu_id;//商户的huifu_id
                $appinfo['huifu_product_id'] = $huifu_appinfo['huifu_product_id'];
                $appinfo['huifu_merch_private_key'] = $huifu_appinfo['huifu_merch_private_key'];
                $appinfo['huifu_public_key'] =$huifu_appinfo['huifu_public_key'];
            }
            $huifu = new \app\custom\Huifu($appinfo, aid, bid);
            $rs = $huifu ->getSettlementQuery($business_huifu_id,$pagenum,$pernum,$st,$begin_date,$end_date);
            $datalist = [];
            if($rs['status'] ==1){
                $datalist = $rs['data'];
            }
            return $this->json(['status' => 1, 'data' => $datalist]);
        }
    }
    //汇付待结算金额 
    public function getHuifuAcctpaymentBalance(){
        if(getcustom('mobile_admin_huifu_settllement')){
            $business_huifu_id = Db::name('business')->where('aid',aid)->where('id',bid)->value('huifu_id');
            if(!$business_huifu_id)return $this->json(['status'=>0,'msg'=>'记录不存在']);
            $huifuset = Db::name('sysset')->where('name','huifuset')->find();
            $appinfo = [];
            if($huifuset){
                $huifu_appinfo = json_decode($huifuset['value'],true);
                $appinfo['huifu_sys_id'] = $huifu_appinfo['huifu_sys_id'];//渠道商的huifu_id
                $appinfo['huifu_id'] = $business_huifu_id;//商户的huifu_id
                $appinfo['huifu_product_id'] = $huifu_appinfo['huifu_product_id'];
                $appinfo['huifu_merch_private_key'] = $huifu_appinfo['huifu_merch_private_key'];
                $appinfo['huifu_public_key'] =$huifu_appinfo['huifu_public_key'];
            }
            $huifu = new \app\custom\Huifu($appinfo, aid, bid);
            $rs = $huifu ->getAcctpaymentBalance($business_huifu_id);
            return $this->json(['status' => 1, 'data' => $rs]);
        }
    }

    /**
     * 提现支付现金
     * https://doc.weixin.qq.com/doc/w3_AV4AYwbFACwCN1ZD3PXaSQiK0DP0F?scode=AHMAHgcfAA0A6OKFY5AeYAOQYKALU
     * @author: liud
     * @time: 2025/9/3 16:51
     */
    public function withdrawfeeCashOrder(){
        $business_withdraw_cash_mobile_duliset = getcustom('business_withdraw_cash_mobile_duliset');
        if(getcustom('business_withdraw_cash_mobile')){
            $money = input('post.money');
            $opt = input('post.opt');
            if($money <= 0){
                return $this->json(['status' => 0, 'msg' => '提现金额不能小于0']);
            }

            $set = Db::name('business_sysset')->where('aid', aid)->field('id,withdrawfee_cash_status,withdrawfee_cash_rate')->find();
            if($business_withdraw_cash_mobile_duliset){
                $business = Db::name('business')->where('aid', aid)->where('id', bid)->field('id,withdrawfee_cash_status_type,withdrawfee_cash_status,withdrawfee_cash_rate')->find();
            }

            if (!$opt['paytype']){
                return $this->json(['status' => 0, 'msg' => '请选择提现方式']);
            }

            if ($opt['paytype'] == '商家管理员余额'){
                if(!$this->user){
                    return $this->json(['status' => 0, 'msg' => '暂无管理员信息']);
                }
                if(!$this->user['mid']){
                    return $this->json(['status' => 0, 'msg' => '当前登录管理员暂未绑定用户信息']);
                }
            }

            //独立设置
            if($business_withdraw_cash_mobile_duliset && $business['withdrawfee_cash_status_type'] == 1){
                $withdrawfee_cash_status = $business['withdrawfee_cash_status'];
                $withdrawfee_cash_rate_yz = $business['withdrawfee_cash_rate'];
            }else{
                //跟随系统
                $withdrawfee_cash_status = $set['withdrawfee_cash_status'];
                $withdrawfee_cash_rate_yz = $set['withdrawfee_cash_rate'];
            }

            $withdrawfee_cash_rate = $withdrawfee_cash_rate_yz / 100;

            //功能关闭
            if($withdrawfee_cash_status == 0 || $withdrawfee_cash_rate <= 0){
                return $this->json(['status' => 2]);
            }

            //计算应支付金额
            $totalprice = bcmul($withdrawfee_cash_rate,$money,2);

            if($totalprice <= 0){
                return $this->json(['status' => 2]);
            }

//            var_dump($opt);
//            exit;

            //创建订单
            Db::startTrans();

            $ordernum = \app\common\Common::generateOrderNo(aid,'business_withdrawfee_cash_order');
            $orderdata = [
                'mid'=>mid,
                'aid'=>aid,
                'bid'=>bid,
                'title'=>'商户余额提现支付现金',
                'createtime'=>time(),
                'ordernum'=> $ordernum,
                'totalprice'=>$totalprice,
                'money'=> $money,
                'withdrawfee_cash_rate'=> $withdrawfee_cash_rate_yz,
                'platform'=> platform,
                'opt'=> jsonEncode($opt),
            ];

            $orderid = Db::name('business_withdrawfee_cash_order')->insertGetId($orderdata);

            if($orderid){
                $payorderid = \app\model\Payorder::createorder(aid,$orderdata['bid'],$orderdata['mid'],'business_withdrawfee_cash',$orderid,$orderdata['ordernum'],$orderdata['title'],$orderdata['totalprice']);
                Db::name('business_withdrawfee_cash_order')->where('id',$orderid)->update(['payorderid'=>$payorderid]);
                \app\common\System::plog('移动端后台创建提现支付现金订单'.$orderid);
                Db::commit();
                return $this->json(['status'=>1,'msg'=>'订单创建成功','payorderid'=>$payorderid]);
            }else{
                Db::rollback();
                return $this->json(['status'=>0,'msg'=>'订单创建失败']);
            }
        }
    }

    public function withdrawfeeCashLog(){
        if(getcustom('business_withdraw_cash_mobile')) {
            $pagenum = input('post.pagenum');
            $st = input('post.st');
            if (!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['cash_order.aid', '=', aid];
            $where[] = ['cash_order.bid', '=', bid];
            $where[] = ['cash_order.status', '=', 1];

            if (input('param.keyword')) {
                $where[] = ['member.nickname|cash_order.ordernum', 'like', '%' . input('param.keyword') . '%'];
            }
            if ($pagenum == 1) {
                $count = 0 + Db::name('business_withdrawfee_cash_order')->alias('cash_order')->field('member.nickname,member.headimg,cash_order.*')->join('member member', 'member.id=cash_order.mid', 'left')->where($where)->count();
            } else {
                $count = 0;
            }
            $count = 0 + Db::name('business_withdrawfee_cash_order')->alias('cash_order')->field('member.nickname,member.headimg,cash_order.*')->join('member member', 'member.id=cash_order.mid', 'left')->where($where)->count();
            $datalist = Db::name('business_withdrawfee_cash_order')->alias('cash_order')->field('member.nickname,member.headimg,cash_order.*')->join('member member', 'member.id=cash_order.mid', 'left')->where($where)->page($pagenum, $pernum)->order('cash_order.id desc')->select()->toArray();
            if (!$datalist) $datalist = [];

            foreach ($datalist as &$v) {
                $datekey = date('Ymd', $v['paytime']);
                if (empty($v['paynum'])) {
                    $v['paynum'] = '';
                }
                if (empty($v['paytype'])) {
                    $v['paytype'] = '';
                }
                $v['paytime'] = $v['paytime'] ? date('Y-m-d H:i:s', $v['paytime']) : '';
            }

            $rdata = [];
            $rdata['count'] = $count;
            $rdata['data'] = $datalist;
            return $this->json($rdata);
        }
    }

    public function payorderlog(){
        if(getcustom('business_payorder_log')){
            $pagenum = input('post.pagenum');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;
            $where = [];
            $where[] = ['member.aid','=',aid];
            $where[] = ['payorder.bid','=',bid];
            $where[] = ['payorder.status','=',1];
            $where[] = ['payorder.money','>',0];
            if(input('param.keyword')){
                $where[] = ['member.nickname|payorder.ordernum','like','%'.input('param.keyword').'%'];
            }
            $count = 0;
            if($pagenum == 1){
                $count = 0 + Db::name('payorder')->alias('payorder')->field('member.nickname,member.headimg,payorder.*')->join('member member', 'member.id=payorder.mid', 'left')->where($where)->count();
            }
            $datalist = Db::name('payorder')->alias('payorder')->field('member.nickname,member.headimg,payorder.*')->join('member member', 'member.id=payorder.mid', 'left')->where($where)->page($pagenum, $pernum)->order('payorder.id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            foreach ($datalist as $key=>$val){
                $datalist[$key]['paytime'] = $val['paytime']?date('Y-m-d H:i:s',$val['paytime']):'';
            }
            $rdata = [];
            $rdata['count'] = $count;
            $rdata['data'] = $datalist;
            return $this->json($rdata);
        }
    }

    public function payorderdetail(){
        if(getcustom('business_payorder_log')){
            $id = input('param.id/d');
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['id','=',$id];
            $detail = Db::name('payorder')->where($where)->find();
            $detail['paytime'] = $detail['paytime']?date('Y-m-d H:i:s',$detail['paytime']):'';
            $detail['createtime'] = $detail['createtime']?date('Y-m-d H:i:s',$detail['createtime']):'';
            $detail['refund_time'] = $detail['refund_time']?date('Y-m-d H:i:s',$detail['refund_time']):'';
            $detail['paynum'] = $detail['paynum']?$detail['paynum']:'-';
            $member = Db::name('member')->where(['id'=>$detail['mid']])->find();
            if(!$member) $member = [];
            $rdata = [];
            $rdata['detail'] = $detail;
            $rdata['member'] = $member;
            return $this->json($rdata);
        }
    }

    public function withdrawInvoice(){
        if (getcustom('business_withdraw_invoice_mobile')){
            $pagenum = input('post.pagenum');
            $st = input('post.st');
            if(!$pagenum) $pagenum = 1;
            $pernum = 20;

            if(input('param.keyword')) $where[] = ['member.nickname','like','%'.trim(input('param.keyword')).'%'];

            // 设置起始日期为三年前
            $startDate = new \DateTime('-3 years');

            if($pagenum == 1){
                $sstime = $startDate->format('Y-m-01');
                //上个月结束时间
                $lastDayLastMonth = strtotime('last day of last month 23:59:59');
                $swhere = [];
                $swhere[] = ['aid','=',aid];
                $swhere[] = ['bid','=',bid];
                $swhere[] = ['status','=',3];
                $swhere[] = ['createtime','between',[$sstime,$lastDayLastMonth]];
                $dkp_amount = Db::name('business_withdrawlog')->where($swhere)->where('withdrawlog_invoice_status','in',[0,1])->sum('txmoney');
                $ykp_amount = Db::name('business_withdrawlog')->where($swhere)->where('withdrawlog_invoice_status',2)->sum('txmoney');
            }

            if($st == 0){

                $datalist =  [];

                // 设置结束日期为今天
                $endDate = new \DateTime('now');

                // 创建一个DatePeriod，从$startDate到$endDate，每个月迭代一次
                $period = new \DatePeriod($startDate, new \DateInterval('P1M'), $endDate);
                //$period = new \DatePeriod($endDate, new \DateInterval('P1M'), $startDate);

                foreach ($period as $k => $dt) {
                    // 获取当前月份的第一天
                    $startOfMonth = new \DateTime($dt->format('Y-m-01'));
//                    var_dump($startOfMonth);
                    // 获取当前月份的最后一天
                    $endOfMonth = (clone $startOfMonth)->modify('last day of this month');

                    $stime = strtotime($startOfMonth->format('Y-m-d'." 0:0:0"));
                    $etime = strtotime($endOfMonth->format('Y-m-d'." 23:59:59"));

                    $where = [];
                    $where[] = ['aid','=',aid];
                    $where[] = ['bid','=',bid];
                    $where[] = ['status','=',3];
                    $where[] = ['withdrawlog_invoice_id','=',0];
                    $where[] = ['createtime','between',[$stime,$etime]];

                    $data = Db::name('business_withdrawlog')->where($where)->field('count(id) as zdnum,sum(txmoney) as zdmoney')->find();
//var_dump(Db::name('business_withdrawlog')->getLastSql());
                    if($data['zdmoney'] > 0){
                        $datalist[$k]['id'] = 0;
                        $datalist[$k]['zdnum'] = $data['zdnum'];
                        $datalist[$k]['zdmoney'] = $data['zdmoney'] ?? 0;
                        $datalist[$k]['stime'] = date('Y-m-d',$stime);
                        $datalist[$k]['etime'] = date('Y-m-d',$etime);
                        $datalist[$k]['zdzq'] = date('Y/m/d',$stime).'-'. date('Y/m/d',$etime);
                    }

                }

                $datalist = array_reverse($datalist);

            }else{
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['bid','=',bid];

                if($st == 1){
                    //待提交和已驳回
                    $where[] = ['status','in',[1,4]];
                }elseif($st == 2){
                    //已开票和待审核
                    $where[] = ['status','in',[2,3]];
                }

                $datalist = Db::name('business_withdrawlog_invoice')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();

                if($datalist){
                    foreach ($datalist as &$val){
                        $val['zdmoney'] = $val['money'];
                        $val['zdnum'] = $val['num'];
                        $val['zdzq'] = date('Y/m/d',$val['start_time']).'-'. date('Y/m/d',$val['end_time']);
                        $val['stime'] = date('Y-m-d',$val['start_time']);
                        $val['etime'] = date('Y-m-d',$val['end_time']);
                    }
                    unset($val);
                }
            }


            if(!$datalist) $datalist = [];
            return $this->json(['status'=>1,'data'=>$datalist,'dkp_amount' => $dkp_amount,'ykp_amount'=>$ykp_amount]);
        }
    }

    public function withdrawInvoiceInfo(){
        if (getcustom('business_withdraw_invoice_mobile')){
            $st = input('param.st');
            $stime = input('param.stime');
            $etime = input('param.etime');
            $id = input('param.id');

            $set = Db::name('business_sysset')->where('aid',aid)->field('invoice_account,invoice_taxpayer_num,invoice_address,invoice_tel,invoice_bankname,invoice_bankcardnum')->find();

            if($st == 0){

                $datalist =  [];
                $stime = strtotime($stime." 0:0:0");
                $etime = strtotime($etime." 23:59:59");

                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['bid','=',bid];
                $where[] = ['status','=',3];
                $where[] = ['withdrawlog_invoice_id','=',0];
                $where[] = ['createtime','between',[$stime,$etime]];

                $datalist = Db::name('business_withdrawlog')->where($where)->field('count(id) as zdnum,sum(txmoney) as zdmoney')->find();


            }else{
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['bid','=',bid];
                $where[] = ['id','=',$id];

                $datalist = Db::name('business_withdrawlog_invoice')->field('*,num as zdnum,money as zdmoney')->where($where)->find();

            }

            $datalist['set'] = $set;

            if(!$datalist) $datalist = [];
            return $this->json(['status'=>1,'data'=>$datalist]);
        }
    }

    public function setWithdrawInvoice(){
        if (getcustom('business_withdraw_invoice_mobile')){
            $st = input('param.st');
            $stime = input('param.stime');
            $etime = input('param.etime');

            $stime = strtotime($stime." 0:0:0");
            $etime = strtotime($etime." 23:59:59");

            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['status','=',3];
            $where[] = ['withdrawlog_invoice_id','=',0];
            $where[] = ['createtime','between',[$stime,$etime]];

            $datalist = Db::name('business_withdrawlog')->where($where)->select()->toArray();

            $info = Db::name('business_withdrawlog')->where($where)->field('count(id) as zdnum,sum(txmoney) as zdmoney')->find();

            if($datalist){
                $oid = Db::name('business_withdrawlog_invoice')->insertGetId([
                    'aid' => aid,
                    'bid' => bid,
                    'status' => 1,
                    'ordernum' => date('ymdHis').aid.rand(1000,9999),
                    'createtime' => time(),
                    'confirmtime' => time(),
                    'start_time' => $stime,
                    'end_time' => $etime,
                    'money' => $info['zdmoney'],
                    'num' => $info['zdnum'],
                ]);
                foreach ($datalist as &$val){

                    Db::name('business_withdrawlog')->where('aid',aid)->where('id',$val['id'])->update([
                        'withdrawlog_invoice_id' => $oid,
                        'withdrawlog_invoice_status' => 1,
                    ]);
                }
                \app\common\System::plog('移动端后台确认提现账单信息'.$oid);
            }

            if(!$datalist) $datalist = [];
            return $this->json(['status'=>1,'data'=>$datalist]);
        }
    }

    public function getWithdrawInvoice(){
        if (getcustom('business_withdraw_invoice_mobile')){
            $id = input('param.id');
            $invoice_pics = input('param.invoice_pics');
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['id','=',$id];

            if(request()->isPost()){

                if(!$info = Db::name('business_withdrawlog_invoice')->where($where)->find()){
                    return $this->json(['status'=>0,'msg'=>'数据不存在']);
                }

                if(!$invoice_pics){
                    return $this->json(['status'=>0,'msg'=>'请上传发票']);
                }

                $set = Db::name('business_sysset')->where('aid',aid)->field('invoice_account,invoice_taxpayer_num,invoice_address,invoice_tel,invoice_bankname,invoice_bankcardnum')->find();

                $datalist = Db::name('business_withdrawlog_invoice')->where($where)->update([
                    'invoice_pics' => implode(',',$invoice_pics),
                    'status' => 3,
                    'uploadtime' => time(),
                    'invoice_info' => jsonEncode($set),
                ]);

                \app\common\System::plog('移动端后台上传发票信息'.$id);
                return $this->json(['status'=>1,'msg'=> '提交成功']);
            }

            $datalist = Db::name('business_withdrawlog_invoice')->where($where)->find();

            if($datalist){
                $datalist['invoice_pics'] = $datalist['invoice_pics'] ? explode(',',$datalist['invoice_pics']) : [];
            }

            if(!$datalist) $datalist = [];
            return $this->json(['status'=>1,'data'=>$datalist]);
        }
    }
}
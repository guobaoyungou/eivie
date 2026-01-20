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
// | 自动执行  每分钟执行一次 crontab -e 加入 */1 * * * * curl https://域名/?s=/ApiAuto/index/key/配置文件中的authtoken
// +----------------------------------------------------------------------
namespace app\controller;
use app\BaseController;
use pay\wechatpay\WxPayV3;
use think\facade\Db;
use think\facade\Log;
class ApiAuto extends BaseController
{
    public function initialize(){

	}
	public function index(){
		$config = include(ROOT_PATH.'config.php');
		set_time_limit(0);
		ini_set('memory_limit', -1);
		if(input('param.key')!=$config['authtoken']) die('error');
		$this->perminute();
		//执行了多少次了
		$lastautotimes = cache('autotimes');
		if(!$lastautotimes) $lastautotimes = 0;
		cache('autotimes',$lastautotimes+1);

		//Log::write('perminute');
		$lastautohour = cache('autotimehour');
		if(!$lastautohour){
			$lastautohour = strtotime(date("Ymd H:00:00")); //整点执行
			cache('autotimehour',$lastautohour);
		}
		if($lastautohour <= time() - 3600){
			cache('autotimehour',time());
			$this->perhour();
			//Log::write('perhour');
		}
		$lastautoday = cache('autotimeday');
		if(!$lastautoday){
			$lastautoday = strtotime(date("Ymd 06:00:00")); //6点执行
			cache('autotimeday',$lastautoday);
		}
//		if($lastautoday <= time() - 86400){
//			cache('autotimeday',time());
//			$this->perday();
//			\think\facade\Log::write('perday');
//		}
        if(date('H:i')=='02:00'){
            cache('autotimeday',time());
			$this->perday();
			\think\facade\Log::write('perday');
        }

		if(getcustom('plug_yuebao')){

			//定时0点执行
			$time = (int)date("H",time());
			if($time == 0){

				//确保一天执行一次
				$yuebaotime = cache('yuebaotime');

				$n_time = strtotime(date("Y-m-d",time()));

				$can = true;
				if(!$yuebaotime){
					cache('yuebaotime',$n_time);
				}else{
					if($yuebaotime == $n_time){
						$can = false;
					}else{
						cache('yuebaotime',$n_time);
					}
				}

				if($can){
					//计算余额宝收益
					$this->yuebao();
				}
			}
		}
        //会员未购买 过期
        if(getcustom('member_vip_edit')){
            //定时0点执行
            $time = (int)date("H",time());
            if($time == 0){
                $this->member_vip_edit();
            }
        }
        if(getcustom('business_fenxiao')){
            //店铺分销
            if(date('H:i')=='00:01'){
                $this->businessfenxiao();
            }
        }
        if(getcustom('commission_butie')){
            //分销补贴，每天0点10分执行一次
            if(date('H:i')=='00:10'){
                $this->commission_butie();
            }
        }
        if(getcustom('product_givetongzheng')){
            //通证释放，每天0点10分执行一次
            if(date('H:i')=='00:10'){
                $this->release_tongzheng();
            }
        }
        if(getcustom('yx_choujiang_manren')){
            //满人开奖活动，每分钟执行一次开奖
            $this->manren_choujiang();
        }
        if(getcustom('shoporder_ranking')){
            //定时每月初1点执行上个月数据
            $time = (int)date("H",time());
            if($time == 1){
                $can = true;
                //确保一个月执行一次
                $shoporder_ranking_time = cache('shoporder_ranking_time');
                $n_time = strtotime(date("Y-m",time()));
                if(!$shoporder_ranking_time){
                    cache('shoporder_ranking_time',$n_time);
                }else{
                    if($shoporder_ranking_time == $n_time){
                        $can = false;
                    }else{
                        cache('shoporder_ranking_time',$n_time);
                    }
                }
                if($can){
                    $admin = Db::name('admin')->where('status',1)->field('id')->select()->toArray();
                    if($admin){
                        foreach($admin as $v){
                            \app\custom\AgentCustom::allshoporderranking($v['id'],2);
                        }
                        unset($v);
                    }
                }
            }
        }
        if(getcustom('shop_paiming_fenhong')){
            //商城消费排名分红，每天0点45分执行一次
            if(date('H:i')=='00:45'){
                $yuebaotime = cache('yuebaotime');
				$n_time = strtotime(date("Y-m-d",time()));
				$can = true;
				if(!$yuebaotime){
					cache('yuebaotime',$n_time);
				}else{
					if($yuebaotime == $n_time){
						$can = false;
					}else{
						cache('yuebaotime',$n_time);
					}
				}
                if($can){
                    $this->paimingFenhong();
                }                
            }
        }
		if(getcustom('sign_pay_bonus')){
            //签到开奖，每天10点执行一次
            if(date('H:i')=='10:00'){
                $this->sign_kaijiang();
            }
        }
        if(getcustom('ciruikang_fenxiao')){
            //定时每月初0点执行上个月业绩加权合作分红数据
            $time = (int)date("H",time());
            if($time == 0){
            	$can = true;
                //确保一个月执行一次
                $ciruikang_fenxiao_time = cache('ciruikang_fenxiao_time');
                $n_time = strtotime(date("Y-m",time()));
                if(!$ciruikang_fenxiao_time){
                    cache('ciruikang_fenxiao_time',$n_time);
                }else{
                    if($ciruikang_fenxiao_time == $n_time){
                        $can = false;
                    }else{
                        cache('ciruikang_fenxiao_time',$n_time);
                    }
                }
                if($can){
	                \app\custom\CiruikangCustom::deal_fenhong_areabt();
	            }
            }
        }
        if(getcustom('yx_money_monthsend')){
            //定时每月初0点执行充值每个月返还
            $time = (int)date("H",time());
            if($time == 0){
            	$can = true;
                //确保一个月执行一次
                $yx_money_monthsend_time = cache('yx_money_monthsend_time');
                $n_time = strtotime(date("Y-m",time()));
                if(!$yx_money_monthsend_time){
                    cache('yx_money_monthsend_time',$n_time);
                }else{
                    if($yx_money_monthsend_time == $n_time){
                        $can = false;
                    }else{
                        cache('yx_money_monthsend_time',$n_time);
                    }
                }
                if($can){
	                \app\custom\yingxiao\MoneyMonthsendCustom::deal_monthsend();
	            }
            }
        }
        if(getcustom('yx_butie_activity')){

			//定时2点执行
			$time = (int)date("H",time());
			if($time == 2){
				$yx_butie_activity_time = cache('yx_butie_activity_time');

				$n_time = strtotime(date("Y-m-d",time()));

				$can = true;
				if(!$yx_butie_activity_time){
					cache('yx_butie_activity_time',$n_time);
				}else{
					if($yx_butie_activity_time == $n_time){
						$can = false;
					}else{
						cache('yx_butie_activity_time',$n_time);
					}
				}

				if($can){
					$this->butieActivityCashbackCommissionDay();
				}
			}
		}
        if(getcustom('yx_commission_to_lingqiantong')){
			//定时1点执行
			$time = (int)date("H",time());
			if($time == 1){
				$yx_commission_to_lingqiantong_time = cache('yx_commission_to_lingqiantong_time');

				$n_time = strtotime(date("Y-m-d",time()));

				$can = true;
				if(!$yx_commission_to_lingqiantong_time){
					cache('yx_commission_to_lingqiantong_time',$n_time);
				}else{
					if($yx_commission_to_lingqiantong_time == $n_time){
						$can = false;
					}else{
						cache('yx_commission_to_lingqiantong_time',$n_time);
					}
				}
				if($can){
					$this->commissionToLingqiantongDay();
				}
			}
		}
        if(getcustom('yx_cashback_time') || getcustom('yx_cashback_stage')){
            //定时0点执行 购物返现
            $time = (int)date("H",time());
            $n_time = strtotime(date("Y-m-d",time()));
            if($time == 0){
                $can = true;
                //确保每天
                $yx_cashback_time_time = cache('yx_cashback_time_time');
                if(!$yx_cashback_time_time){
                    cache('yx_cashback_time_time',$n_time);
                }else{
                    if($yx_cashback_time_time == $n_time){
                        $can = false;
                    }else{
                        cache('yx_cashback_time_time',$n_time);
                    }
                }
                if($can){
                    \app\custom\OrderCustom::deal_autocashback();
                }
            }

            //执行20分钟后再执行
            $yx_cashback_time_time2 = cache('yx_cashback_time_time');
            if($yx_cashback_time_time2 && $yx_cashback_time_time2 == $n_time && $yx_cashback_time_time2 <= time() - 1200){
                //执行10分钟后再执行
                $yx_cashback_time_time3 = cache('yx_cashback_time_time3');
                if(!$yx_cashback_time_time3 || ($yx_cashback_time_time3 && $yx_cashback_time_time3 <= time() - 600)){
                    cache('yx_cashback_time_time3',time());
                    \app\custom\OrderCustom::deal_autocashback(0,1);
                }
            }
        }

        if(getcustom('car_management')){
            //到期提醒，每天11点00分执行一次
            if(date('H:i')=='11:00'){
                $this->carManagement();
            }
        }
        if(getcustom('product_lvxin_replace_remind')){
            //滤芯可用天数倒计时，每天执行一次
            if(date('H:i')=='00:13'){
                $daytime = strtotime(date('Y-m-d',time()). ' 23:59:59');
                $time = time();
                Db::name('product_lvxin_replace')->where('day','>',0)->where('daytime','<',$time)->update(['day' => Db::raw("day-1"),'daytime' =>$daytime]);
            }

            //过期提醒
            if(date('H:i')=='08:16'){
                $this->send_lvxin_replace_remind();
            }
        }

        if(getcustom('fenhong_jiaquan_area')){
            //区域代理加权分红
            //每季度开始执行一次
            $jiduarr = ['04-01 01:10','07-01 01:10','10-01 01:10','01-01 01:10'];
            $dqtime = date('m-d H:i');
            if(in_array($dqtime,$jiduarr)){
                $admins = Db::name('admin')->where('status',1)->field('id')->select()->toArray();
                if($admins){
                    foreach($admins as $admin){
                        \app\common\Fenhong::fenhong_jiaquan_area($admin['id']);
                    }
                }
            }
        }

        if(getcustom('fenhong_jiaquan_gudong')){
            //股东加权分红
            //每季度开始执行一次
            $jiduarr = ['04-01 01:25','07-01 01:25','10-01 01:25','01-01 01:25'];
            $dqtime = date('m-d H:i');
            if(in_array($dqtime,$jiduarr)){
                $admins = Db::name('admin')->where('status',1)->field('id')->select()->toArray();
                if($admins){
                    foreach($admins as $admin){
                        \app\common\Fenhong::fenhong_jiaquan_gudong($admin['id']);
                    }
                }
            }
        }

        if(getcustom('fenhong_area_zhitui_pingji')){
            //区域代理分红直推平级奖
            //每季度开始执行一次
            $jiduarr = ['04-01 01:45','07-01 01:45','10-01 01:45','01-01 01:45'];
            $dqtime = date('m-d H:i');
            if(in_array($dqtime,$jiduarr)){
                $admins = Db::name('admin')->where('status',1)->field('id')->select()->toArray();
                if($admins){
                    foreach($admins as $admin){
                        \app\common\Fenhong::fenhong_area_zhitui_pingji($admin['id']);
                    }
                }
            }
        }
        if(getcustom('shop_order_excel_countpay')){
            //定时0点执行 商城订单数据统计
            $time = (int)date("H",time());
            if($time == 0){
                $can = true;
                //确保每天
                $shop_order_excel_countpay_time = cache('shop_order_excel_countpay_time');
                $n_time = strtotime(date("Y-m-d",time()));
                if(!$shop_order_excel_countpay_time){
                    cache('shop_order_excel_countpay_time',$n_time);
                }else{
                    if($shop_order_excel_countpay_time == $n_time){
                        $can = false;
                    }else{
                        cache('shop_order_excel_countpay_time',$n_time);
                    }
                }
                if($can){
                    $this->shopOrderExcelCountpay();
                }
            }
        }
        if(getcustom('score_to_fenhongdian')){
            //定时0点执行 积分分红点分红
            $time = (int)date("H",time());
            if($time == 0){
                $can = true;
                //确保每天
                $score_to_fenhongdian_time = cache('score_to_fenhongdian_time');
                $n_time = strtotime(date("Y-m-d",time()));
                if(!$score_to_fenhongdian_time){
                    cache('score_to_fenhongdian_time',$n_time);
                }else{
                    if($score_to_fenhongdian_time == $n_time){
                        $can = false;
                    }else{
                        cache('score_to_fenhongdian_time',$n_time);
                    }
                }
                if($can){
                    $this->scoreToFenhongdian();
                }
            }
        }

        if(getcustom('member_tag_age')){
            //定时每月初1点执行-会员年龄标签每月发放积分
//            $time = (int)date("H",time());
//            if($time == 1){
//                $can = true;
//                //确保一个月执行一次
//                $member_tag_age_time = cache('member_tag_age');
//                $n_time = strtotime(date("Y-m",time()));
//                if(!$member_tag_age_time){
//                    cache('member_tag_age',$n_time);
//                }else{
//                    if($member_tag_age_time == $n_time){
//                        $can = false;
//                    }else{
//                        cache('member_tag_age',$n_time);
//                    }
//                }
//                if($can){
//                    $admin = Db::name('admin')->where('status',1)->field('id')->select()->toArray();
//                    if($admin){
//                        foreach($admin as $v){
//                            $this->memberTagAgeGiveScore($v['id']);
//                        }
//                        unset($v);
//                    }
//                }
//            }

            //每天2点执行-根据年龄自动升级年龄标签
            $time1 = (int)date("H",time());
            if($time1 == 2){
                $can1 = true;
                //确保每天
                $day_member_tag_age = cache('day_member_tag_age');
                $n_time1 = strtotime(date("Y-m-d",time()));
                if(!$day_member_tag_age){
                    cache('day_member_tag_age',$n_time1);
                }else{
                    if($day_member_tag_age == $n_time1){
                        $can1 = false;
                    }else{
                        cache('day_member_tag_age',$n_time1);
                    }
                }
                if($can1){
                    $admin = Db::name('admin')->where('status',1)->field('id')->select()->toArray();
                    if($admin){
                        foreach($admin as $v){
                            $this->everyDayCheckTagAge($v['id']);
                        }
                        unset($v);
                    }
                }
            }
        }
        if(getcustom('member_pid_reset')){
            //每天02点00分执行一次
            if(date('H:i')=='02:00'){
                $this->memberPidReset();
            }
        }

        //新版微信商家转账查询
        $this->auto_check_transfer();

        if(getcustom('yx_invite_cashback_time')){
            //定时1点执行 邀请购物返现
            $time = (int)date("H",time());
            $n_time = strtotime(date("Y-m-d",time()));
            if($time == 1){
                $can = true;
                //确保每天
                $yx_invite_cashback_time = cache('yx_invite_cashback_time');
                if(!$yx_invite_cashback_time){
                    cache('yx_invite_cashback_time',$n_time);
                }else{
                    if($yx_invite_cashback_time == $n_time){
                        $can = false;
                    }else{
                        cache('yx_invite_cashback_time',$n_time);
                    }
                }
                if($can){
                    \app\custom\OrderCustom::deal_autoinvitecashback();
                }
            }

            //执行20分钟后再执行
            $yx_invite_cashback_time2 = cache('yx_invite_cashback_time');
            if($yx_invite_cashback_time2 && $yx_invite_cashback_time2 == $n_time && $yx_invite_cashback_time2 <= time() - 1200){
                //执行10分钟后再执行
                $yx_invite_cashback_time3 = cache('yx_invite_cashback_time3');
                if(!$yx_invite_cashback_time3 || ($yx_invite_cashback_time3 && $yx_invite_cashback_time3 <= time() - 600)){
                    cache('yx_invite_cashback_time3',time());
                    \app\custom\OrderCustom::deal_autoinvitecashback(0,1);
                }
            }
        }

        if(getcustom('bonus_pool_gold')){
            //结算买单订单金币
            if(date('H:i')=='01:00'){
                $this->caclMaidanOrderBonusPoolGold();
            }
        }
        if(getcustom('freeze_money')){
            //每分钟检测一次冻结资金有效期
            $this->check_freeze_money();
        }
        if(getcustom('sign_pay_bonus')){
            //每月1号定时1点执行 每月签到次数奖金
            $day = (int)date("d",time());
            $time = (int)date("H",time());
            if($day == 1 && $time == 1){
                $can = true;
                //确保每月执行一次
                $sign_pay_bonus_time = cache('sign_pay_bonus_time');
                $n_time = strtotime(date("Y-m",time()));
                if(!$sign_pay_bonus_time){
                    cache('sign_pay_bonus_time',$n_time);
                }else{
                    if($sign_pay_bonus_time == $n_time){
                        $can = false;
                    }else{
                        cache('sign_pay_bonus_time',$n_time);
                    }
                }
                if($can){
                    \app\common\SignBonus::everymonthBonus();
                }
            }
        }
        if(getcustom('yx_offline_subsidies')){
            //每月1号定时2点执行 计算上月的线下补贴的人数奖励
            $day = (int)date("m",time());
            $time = (int)date("H",time());
            if($day == 1 && $time == 2){
                $can = true;
                //确保每月执行一次
                $yx_offline_subsidies = cache('yx_offline_subsidies');
                $n_time = strtotime(date("Y-m",time()));
                if(!$yx_offline_subsidies){
                    cache('yx_offline_subsidies',$n_time);
                }else{
                    if($yx_offline_subsidies == $n_time){
                        $can = false;
                    }else{
                        cache('yx_offline_subsidies',$n_time);
                    }
                }
                if($can){
                    self::renshureward();
                }
            }
        }
        if(getcustom('auto_del_member')){
            $this->memberAutoDel();
        }
        if(getcustom('yx_network_help')){
            $this->helpscoreToMoney();
        }
        if(getcustom('extend_tencent_qian')){
            //定时同步腾讯电子签
            \app\custom\TencentQian::autotask();
        }
        //统一发送微信模版消息
        $this->sendWechatTmpl();

		die;
	}

	//每分钟执行一次
	private function perminute(){
		$time = time();

        if(getcustom('h5zb')){
            $config = include(ROOT_PATH.'config.php');
            $urlroom = PRE_URL.'/?s=/ApiAuto/roomProductAutoOnline/key/'.$config['authtoken'];
            syncRequest($urlroom);
            \app\custom\H5zb::autoCloseZb();//10分钟未推流，自动关闭直播
        }
		//60分钟自动关闭订单 释放库存
		$orderlist = Db::name('shop_order')->where('status',0)->select()->toArray();
		$autocloseArr = [];
		foreach($orderlist as $order){
			if(!$autocloseArr[$order['aid']]){
				$autocloseArr[$order['aid']] = Db::name('shop_sysset')->where('aid',$order['aid'])->value('autoclose');
			}
			if($order['createtime'] + $autocloseArr[$order['aid']]*60 > time()) continue;
			$aid = $order['aid'];
			$mid = $order['mid'];
			$orderid = intval($order['id']);
			$order = Db::name('shop_order')->where('id',$orderid)->find();
			if(!$order || $order['status']!=0){
				//return $this->json(['status'=>0,'msg'=>'关闭失败,订单状态错误']);
			}else{
				//加库存
				$oglist = Db::name('shop_order_goods')->where('aid',$aid)->where('orderid',$orderid)->select()->toArray();
				foreach($oglist as $og){
					Db::name('shop_guige')->where('aid',$aid)->where('id',$og['ggid'])->update(['stock'=>Db::raw("stock+".$og['num']),'sales'=>Db::raw("IF(sales>=".$og['num'].",sales-".$og['num'].",0)")]);
					Db::name('shop_product')->where('aid',$aid)->where('id',$og['proid'])->update(['stock'=>Db::raw("stock+".$og['num']),'sales'=>Db::raw("IF(sales>=".$og['num'].",sales-".$og['num'].",0)")]);
					if(getcustom('guige_split')){
						\app\model\ShopProduct::addlinkstock($og['proid'],$og['ggid'],$og['num']);
					}
					if(getcustom('ciruikang_fenxiao')){
                        //是否开启了商城商品需上级购买足量
                        $deal_ogstock2 = \app\custom\CiruikangCustom::deal_ogstock2($order,$og,$og['num'],'下级订单关闭');
                    }
                    if(getcustom('product_chinaums_subsidy') && $og['subsidy_id'] && $og['cred_frozen_no']){
                        //国补资格解锁
                        $subsidyRecord = Db::name('chinaums_subsidy_apply')->where('id',$og['subsidy_id'])->find();
                        $subsidyRecord['cred_frozen_no'] = $og['cred_frozen_no'];
                        $subsidy = new \app\custom\ChinaumsSubsidy($aid);
                        $cancelRes = $subsidy->authCodeCancel($subsidyRecord);
                        if($cancelRes['respCode'] == '000000'){
                            Db::name('chinaums_subsidy_apply')->where('id',$og['subsidy_id'])->update(['status'=>1]);
                        }
                    }
                    if(getcustom('deposit')) {
                        if ($og['deposit_hexiao_num'] > 0) {
                            \app\common\Member::addDeposit($og['aid'], $og['bid'], $og['mid'], $og['deposit_hexiao_num'], $og['deposit_id'], '取消订单解冻' . t('押金'), ['orderid' => $order['id']]);
                        }
                    }
				}
				//优惠券抵扣的返还
				if($order['coupon_rid']){
                    \app\common\Coupon::refundCoupon2($order['aid'],$order['mid'],$order['coupon_rid'],$order,2);
				}
				if(getcustom('money_dec')){
		            //返回余额抵扣
		            if($order['dec_money']>0){
		                \app\common\Member::addmoney($aid,$mid,$order['dec_money'],t('余额').'抵扣返回，订单号: '.$order['ordernum']);
		            }
		        }
                if(getcustom('pay_money_combine')){
                    //返回余额组合支付
                    if($order['combine_money']>0){
                    	$up =  Db::name('shop_order')->where('id',$orderid)->where('combine_money',$order['combine_money'])->update(['combine_money'=>0]);
                    	if($up){
                    		$res = \app\common\Member::addmoney($aid,$mid,$order['combine_money'],t('余额').'组合支付返回，订单号: '.$order['ordernum']);
	                        if(!$res || $res['status'] !=1){
	                            Db::name('shop_order')->where('id',$orderid)->update(['combine_money'=>$order['combine_money']]);
	                        }
                    	}
                    }
                }
                if(getcustom('member_goldmoney_silvermoney')){
                    //返回银值抵扣
                    if($order['silvermoneydec']>0){
                        $res = \app\common\Member::addsilvermoney($aid,$order['mid'],$order['silvermoneydec'],t('银值').'抵扣返回，订单号: '.$order['ordernum'],$order['ordernum']);
                    }
                    //返回金值抵扣
                    if($order['goldmoneydec']>0){
                        $res = \app\common\Member::addgoldmoney($aid,$order['mid'],$order['goldmoneydec'],t('金值').'抵扣返回，订单号: '.$order['ordernum'],$order['ordernum']);
                    }
                }
                if(getcustom('member_dedamount')){
                    //返回抵抗金抵扣
                    if($order['dedamount_dkmoney']>0){
                        $params=['orderid'=>$order['id'],'ordernum'=>$order['ordernum'],'paytype'=>'shop','opttype'=>'return'];
                        \app\common\Member::adddedamount($aid,$order['bid'],$order['mid'],$order['dedamount_dkmoney'],'抵扣金抵扣返回，订单号: '.$order['ordernum'],$params);
                    }
                }
                if(getcustom('member_shopscore')){
		            //返回产品积分抵扣
		            if($order['shopscore']>0 && $order['shopscore_status'] == 1){
		                $params=['orderid'=>$order['id'],'ordernum'=>$order['ordernum'],'paytype'=>'shop'];
		                \app\common\Member::addshopscore($aid,$order['mid'],$order['shopscore'],t('产品积分').'抵扣返回，订单号: '.$order['ordernum'],$params);
		            }
		        }
				$rs = Db::name('shop_order')->where('id',$orderid)->where('aid',$aid)->where('mid',$mid)->update(['status'=>4]);
				Db::name('shop_order_goods')->where('orderid',$orderid)->where('aid',$aid)->where('mid',$mid)->update(['status'=>4]);

				if($order['platform'] == 'toutiao'){
					\app\common\Ttpay::pushorder($aid,$order['ordernum'],2);
				}
                if(getcustom('transfer_order_parent_check')){
                     Db::name('transfer_order_parent_check_log')->where('orderid',$orderid)->where('aid',$aid)->where('status',0)->where('hide',0)->update(['status'=>2,'examinetime'=>time()]);

                    //关闭订单减分销数据统计的单量
                    \app\common\Fenxiao::decTransferOrderCommissionTongji($aid, $mid, $orderid, 1);
                }
                if(getcustom('erp_hupun')){
                    //万里牛erp
                    $wln = new \app\custom\Hupun($order['aid']);
                    $wln->orderPush($order['id']);
                }
				//return $this->json(['status'=>1,'msg'=>'操作成功']);
				//$rs = \app\common\Wxpay::closeorder($order['aid'],$order['ordernum'],$order['platform']);
			}
            \app\common\Order::order_close_done($order['aid'],$order['id'],'shop');
		}
		//秒杀
		$orderlist = Db::name('seckill_order')->where('status',0)->select()->toArray();
		$autocloseArr = [];
		foreach($orderlist as $order){
			if(!$autocloseArr[$order['aid']]){
				$autocloseArr[$order['aid']] = Db::name('seckill_sysset')->where('aid',$order['aid'])->value('autoclose');
			}
			if($order['createtime'] + $autocloseArr[$order['aid']]*60 > time()) continue;
			$aid = $order['aid'];
			$mid = $order['mid'];
			$orderid = intval($order['id']);
			$order = Db::name('seckill_order')->where('id',$orderid)->find();
			if(!$order || $order['status']!=0){
				//return $this->json(['status'=>0,'msg'=>'关闭失败,订单状态错误']);
			}else{
				//加库存
				Db::name('seckill_product')->where('aid',$aid)->where('id',$order['proid'])->update(['stock'=>Db::raw("stock+".$order['num']),'sales'=>Db::raw("IF(sales>=".$order['num'].",sales-".$order['num'].",0)")]);
                if($order['ggid']) Db::name('seckill_guige')->where('aid',$aid)->where('id',$order['ggid'])->update(['stock'=>Db::raw("stock+".$order['num']),'sales'=>Db::raw("IF(sales>=".$order['num'].",sales-".$order['num'].",0)")]);
				//优惠券抵扣的返还
				if($order['coupon_rid'] > 0){
                    \app\common\Coupon::refundCoupon2($order['aid'],$order['mid'],$order['coupon_rid'],$order);
				}
				$rs = Db::name('seckill_order')->where('id',$orderid)->where('aid',$aid)->where('mid',$mid)->update(['status'=>4]);
			}
		}

		//积分兑换
		$orderlist = Db::name('scoreshop_order')->where('status',0)->select()->toArray();
		$autocloseArr = [];
		foreach($orderlist as $order){
			if(!$autocloseArr[$order['aid']]){
				$autocloseArr[$order['aid']] = Db::name('scoreshop_sysset')->where('aid',$order['aid'])->value('autoclose');
			}
			if($order['createtime'] + $autocloseArr[$order['aid']]*60 > time()) continue;
			$aid = $order['aid'];
			$mid = $order['mid'];
			$orderid = intval($order['id']);
			$order = Db::name('scoreshop_order')->where('id',$orderid)->find();
			if(!$order || $order['status']!=0){
				//return $this->json(['status'=>0,'msg'=>'关闭失败,订单状态错误']);
			}else{
				//加库存
				$oglist = Db::name('scoreshop_order_goods')->where('aid',$aid)->where('orderid',$orderid)->select()->toArray();
				foreach($oglist as $og){
					Db::name('scoreshop_product')->where('aid',$aid)->where('id',$og['proid'])->update(['stock'=>Db::raw("stock+".$og['num']),'sales'=>Db::raw("IF(sales>=".$og['num'].",sales-".$og['num'].",0)")]);
                    if($og['ggid']) Db::name('scoreshop_guige')->where('aid',$aid)->where('id',$og['ggid'])->update(['stock'=>Db::raw("stock+".$og['num']),'sales'=>Db::raw("IF(sales>=".$og['num'].",sales-".$og['num'].",0)")]);
				}
				//优惠券抵扣的返还
				if($order['coupon_rid'] > 0){
                    \app\common\Coupon::refundCoupon2($order['aid'],$order['mid'],$order['coupon_rid'],$order);
				}
				$rs = Db::name('scoreshop_order')->where('id',$orderid)->where('aid',$aid)->where('mid',$mid)->update(['status'=>4]);
				Db::name('scoreshop_order_goods')->where('orderid',$orderid)->where('aid',$aid)->where('mid',$mid)->update(['status'=>4]);
			}
            \app\common\Order::order_close_done($order['aid'],$order['id'],'scoreshop');
		}

		//预约服务
		$orderlist = Db::name('yuyue_order')->where('status',0)->select()->toArray();
		$autocloseArr = [];
		foreach($orderlist as $order){
			if(!$autocloseArr[$order['aid'].'_'.$order['bid']]){
				$autocloseArr[$order['aid'].'_'.$order['bid']] = Db::name('yuyue_set')->where('aid',$order['aid'])->where('bid',$order['bid'])->value('autoclose');
			}
			if($order['createtime'] + $autocloseArr[$order['aid'].'_'.$order['bid']]*60 > time()) continue;
			$aid = $order['aid'];
			$mid = $order['mid'];
			$orderid = intval($order['id']);

			//加库存
			Db::name('yuyue_product')->where('aid',$aid)->where('id',$order['proid'])->update(['stock'=>Db::raw("stock+".$order['num']),'sales'=>Db::raw("IF(sales>=".$order['num'].",sales-".$order['num'].",0)")]);
			Db::name('yuyue_guige')->where('aid',$aid)->where('id',$order['ggid'])->update(['stock'=>Db::raw("stock+".$order['num']),'sales'=>Db::raw("IF(sales>=".$order['num'].",sales-".$order['num'].",0)")]);
			//优惠券抵扣的返还
			if($order['coupon_rid'] > 0){
                \app\common\Coupon::refundCoupon2($order['aid'],$order['mid'],$order['coupon_rid'],$order);
			}
			$rs = Db::name('yuyue_order')->where('id',$orderid)->where('aid',$aid)->where('mid',$mid)->update(['status'=>4]);
			//积分抵扣的返还
			if($order['scoredkscore'] > 0){
				\app\common\Member::addscore($aid,$order['mid'],$order['scoredkscore'],'订单退款返还');
			}
            if(getcustom('yuyue_money_dec')){
                if($order['dec_money']>0 && $order['dec_money_status'] == 1){
                    Db::name('yuyue_order')->where('id',$order['id'])->update(['dec_money_status'=>0]);
                    \app\common\Member::addmoney($order['aid'],$order['mid'],$order['dec_money'],t('余额').'抵扣返还,订单号: '.$order['ordernum']);
                }
            }
			//退款成功通知
			$tmplcontent = [];
			$tmplcontent['first'] = '您的订单已经完成退款，¥'.$order['refund_money'].'已经退回您的付款账户，请留意查收。';
			$tmplcontent['remark'] = '请点击查看详情~';
			$tmplcontent['orderProductPrice'] = (string) $order['refund_money'];
			$tmplcontent['orderProductName'] = $order['title'];
			$tmplcontent['orderName'] = $order['ordernum'];
            $tmplcontentNew = [];
            $tmplcontentNew['character_string1'] = $order['ordernum'];//订单编号
            $tmplcontentNew['thing2'] = $order['title'];//商品名称
            $tmplcontentNew['amount3'] = $order['refund_money'];//退款金额
			\app\common\Wechat::sendtmpl(aid,$order['mid'],'tmpl_tuisuccess',$tmplcontent,m_url('pages/my/usercenter'),$tmplcontentNew);
			//订阅消息
			$tmplcontent = [];
			$tmplcontent['amount6'] = $order['refund_money'];
			$tmplcontent['thing3'] = $order['title'];
			$tmplcontent['character_string2'] = $order['ordernum'];
			
			$tmplcontentnew = [];
			$tmplcontentnew['amount3'] = $order['refund_money'];
			$tmplcontentnew['thing6'] = $order['title'];
			$tmplcontentnew['character_string4'] = $order['ordernum'];
			\app\common\Wechat::sendwxtmpl($aid,$order['mid'],'tmpl_tuisuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);

			//短信通知
			$member = Db::name('member')->where('id',$order['mid'])->find();
			if($member['tel']){
				$tel = $member['tel'];
			}else{
				$tel = $order['tel'];
			}
			$rs = \app\common\Sms::send($aid,$tel,'tmpl_tuisuccess',['ordernum'=>$order['ordernum'],'money'=>$order['refund_money']]);
            \app\common\Order::order_close_done($order['aid'],$order['id'],'yuyue');
		}
        if(getcustom('h5zb')){
            \app\custom\H5zb::roomProductAutoOnline();
        }

        if(getcustom('water_happy_ti')){
            //扫码打水
            $this->waterHappytiAutoOrder();
        }
        if(getcustom('gold_bean_shop')){
            $this->goldBeanShopAutoOrder();
        }
		//预约服务 几分钟内未接单自动退款
		if(getcustom('hmy_yuyue')){
			$orderlist = Db::name('yuyue_order')->where('status',1)->where('worker_orderid',0)->select()->toArray();
			$autocloseArr = [];
			foreach($orderlist as $order){
				if(!$autocloseArr[$order['aid'].'_'.$order['bid']]){
					$autocloseArr[$order['aid'].'_'.$order['bid']] = Db::name('yuyue_set')->where('aid',$order['aid'])->where('bid',$order['bid'])->value('minminute');
				}
				if($order['paytime'] + $autocloseArr[$order['aid'].'_'.$order['bid']]*60 > time()) continue;
				$aid = $order['aid'];
				$mid = $order['mid'];
				$bid = $order['bid'];
				$orderid = intval($order['id']);
				//	$rs = Db::name('yuyue_order')->where('id',$orderid)->where('aid',$aid)->where('mid',$mid)->update(['status'=>4]);
				//退款
				Db::name('yuyue_order')->where('id',$orderid)->where('aid',$aid)->where('bid',$bid)->update(['status'=>4,'refund_status'=>2,'refund_money'=>$order['totalprice'],'refund_reason'=>'超时未接单退款','refund_time'=>time()]);
				$rs = \app\common\Order::refund($order,$order['totalprice'],'超时未接单退款');

				$config = include(ROOT_PATH.'config.php');
				$appId=$config['hmyyuyue']['appId'];
				$appSecret=$config['hmyyuyue']['appSecret'];
				$headrs = array('content-type: application/json;charset=UTF-8','appid:'.$appId,'appSecret:'.$appSecret);
				$url = 'https://shifu.api.kkgj123.cn/api/1/order/cancel';
				$data = [];
				$data['sysOrderNo'] = $order['sysOrderNo'];
				$data['cancelParty'] = 3;
				$data['cancelReason'] = '超时取消';
				$data = json_encode($data,JSON_UNESCAPED_UNICODE);
				$res = curl_post($url,$data,'',$headrs);
				$res = json_decode($res,true);
			}
		}
		//超时的团
		$time = time();
		$collagewhere = [];
		if(getcustom('yx_collage_jieti')){
            $collagewhere[] = ['collage_type','=',0];
        }
		$tlist = Db::name('collage_order_team')->where($collagewhere)->where("`status`=1 and createtime+teamhour*3600<{$time}")->select()->toArray();
		Db::name('collage_order_team')->where($collagewhere)->where("`status`=1 and createtime+teamhour*3600<{$time}")->update(['status'=>3]);//改成失败状态
		if($tlist){//退款
			foreach($tlist as $t){
				$sysset = Db::name('admin')->where('id',$t['aid'])->find();
				$orderlist = Db::name('collage_order')->where('status',1)->where('teamid',$t['id'])->where('buytype','<>',1)->select()->toArray();
				foreach($orderlist as $orderinfo){

                    Db::name('collage_order')->where('id',$orderinfo['id'])->update(['status'=>4]);

					if($orderinfo['paytype']=='微信支付'){
						$rs = \app\common\Wxpay::refund($orderinfo['aid'],$orderinfo['platform'],$orderinfo['ordernum'],$orderinfo['totalprice'],$orderinfo['totalprice'],'拼团失败');
					}else{
						\app\common\Member::addmoney($orderinfo['aid'],$orderinfo['mid'],$orderinfo['totalprice'],'拼团失败退款');
					}
					//积分抵扣的返还
					if($orderinfo['scoredkscore'] > 0){
						\app\common\Member::addscore($orderinfo['aid'],$orderinfo['mid'],$orderinfo['scoredkscore'],'拼团失败退款返还');
					}
					//扣除消费赠送积分
        			\app\common\Member::decscorein($orderinfo['aid'],'collage',$orderinfo['id'],$orderinfo['ordernum'],'拼团失败退款扣除消费赠送');
					//优惠券抵扣的返还
					if($orderinfo['coupon_rid'] > 0){
                        \app\common\Coupon::refundCoupon2($orderinfo['aid'],$orderinfo['mid'],$orderinfo['coupon_rid'],$orderinfo);
					}
                    if(getcustom('yx_collage_team_in_team')){
                        //扣除团中团赠送佣金
                        \app\custom\CollageTeamInTeamCustom::deccommission(aid,$orderinfo['id'],$orderinfo['ordernum'],'订单退款扣除');
                    }
					
                    //非机器人订单，进行通知信息
                    if(!$orderinfo['isjiqiren']){
    					//退款成功通知
    					$tmplcontent = [];
    					$tmplcontent['first'] = '拼团失败退款，¥'.$orderinfo['totalprice'].'已经退回您的付款账户，请留意查收。';
    					$tmplcontent['remark'] = '请点击查看详情~';
    					$tmplcontent['orderProductPrice'] = (string) $orderinfo['totalprice'];
    					$tmplcontent['orderProductName'] = $orderinfo['title'];
    					$tmplcontent['orderName'] = $orderinfo['ordernum'];
                        $tmplcontentNew = [];
                        $tmplcontentNew['character_string1'] = $orderinfo['ordernum'];//订单编号
                        $tmplcontentNew['thing2'] = $orderinfo['title'];//商品名称
                        $tmplcontentNew['amount3'] = $orderinfo['totalprice'];//退款金额
    					\app\common\Wechat::sendtmpl($orderinfo['aid'],$orderinfo['mid'],'tmpl_tuisuccess',$tmplcontent,m_url('activity/collage/orderlist',$orderinfo['aid']),$tmplcontentNew);
    					//订阅消息
    					$tmplcontent = [];
    					$tmplcontent['amount6'] = $orderinfo['totalprice'];
    					$tmplcontent['thing3'] = $orderinfo['title'];
    					$tmplcontent['character_string2'] = $orderinfo['ordernum'];
    					
    					$tmplcontentnew = [];
    					$tmplcontentnew['amount3'] = $orderinfo['totalprice'];
    					$tmplcontentnew['thing6'] = $orderinfo['title'];
    					$tmplcontentnew['character_string4'] = $orderinfo['ordernum'];
    					\app\common\Wechat::sendwxtmpl($orderinfo['aid'],$orderinfo['mid'],'tmpl_tuisuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);

    					//短信通知
    					$member = Db::name('member')->where('id',$orderinfo['mid'])->find();
    					if($member['tel']){
    						$tel = $member['tel'];
    					}else{
    						$tel = $orderinfo['tel'];
    					}
    					$rs = \app\common\Sms::send($orderinfo['aid'],$tel,'tmpl_tuisuccess',['ordernum'=>$orderinfo['ordernum'],'money'=>$orderinfo['totalprice']]);
                    }
				}
			}
		}
		
        if(getcustom('yx_collage_jieti')){
            //拼团，根据设置的结束时间进行结束操作
            $time = time();
            $jt_collage_list_team = Db::name('collage_order_team')->where('collage_type',1)->where("`status`=1 and endtime<{$time}")->select()->toArray();
            foreach($jt_collage_list_team as $jtckey=>$jtcval){
                $jtc_count = Db::name('collage_order')->where('aid',$jtcval['aid'])->where('bid',$jtcval['bid'])->where('teamid',$jtcval['id'])->where('status',1)->count();
                //更改团的状态
                Db::name('collage_order_team')->where('aid',$jtcval['aid'])->where('bid',$jtcval['bid']) ->where('id',$jtcval['id'])->update(['status' => 2,'num' => $jtc_count]);
                //修改团对应订单的数量,根据总数量 对应设置中的商品数量
                $jtdata = Db::name('collage_product')->where('aid',$jtcval['aid'])->where('bid',$jtcval['bid'])->where('id',$jtcval['proid'])->value('jieti_data');
                $jtdata = json_decode($jtdata,true);
                $give_num =1;
                foreach($jtdata as $key=>$val){
                    if($jtc_count >= $val['teamnum'] && $val['goodsnum'] > $give_num ){
                        $give_num = $val['goodsnum'];
                    }
                }
                Db::name('collage_order')->where('aid',$jtcval['aid'])->where('bid',$jtcval['bid'])->where('teamid',$jtcval['id'])->where('status',1)->update(['num'=>$give_num,'status'=>3,'collect_time'=>time()]);
            }
        }

		//超时的幸运拼团
		//超时的团
		$time = time();
		$tlist = Db::name('lucky_collage_order_team')->where("`status`=1 and createtime+teamhour*3600<{$time}")->select()->toArray();
		Db::name('lucky_collage_order_team')->where("`status`=1 and createtime+teamhour*3600<{$time}")->update(['status'=>3]);//改成失败状态
		if($tlist){//退款
			foreach($tlist as $t){
				$sysset = Db::name('admin')->where('id',$t['aid'])->find();
				$orderlist = Db::name('lucky_collage_order')->where('status',1)->where('isjiqiren',0)->where('teamid',$t['id'])->where('buytype','<>',1)->select()->toArray();
				foreach($orderlist as $orderinfo){
					$product = Db::name('lucky_collage_product')->where(['id'=>$orderinfo['proid']])->find();
                    if(getcustom('luckycollage_fail_norefund',$t['aid'])){
                        if($product['failtklx']=='3'){
                            //不退款
                            \app\common\Order::order_close_done($orderinfo['aid'],$orderinfo['id'],'lucky_collage');
                            continue;
                        }
                    }
                    if($product['failtklx']=='1'){
                        if(getcustom('luckycollage_score_pay')){
                            if($orderinfo['is_score_pay'] == 1){
                                \app\common\Member::addscore($orderinfo['aid'],$orderinfo['mid'],$orderinfo['totalscore'],'拼团失败订单返还');
                            }
                        }
                        \app\common\Order::refund($orderinfo,$orderinfo['totalprice'],'拼团失败订单退款');
                    }else{
                        \app\common\Member::addmoney($orderinfo['aid'],$orderinfo['mid'],$orderinfo['totalprice'],'拼团失败退款');
                    }
                    /*if($orderinfo['paytype']=='微信支付'){
                        $rs = \app\common\Wxpay::refund($orderinfo['aid'],$orderinfo['platform'],$orderinfo['ordernum'],$orderinfo['totalprice'],$orderinfo['totalprice'],'拼团失败');
                    }else{
                        \app\common\Member::addmoney($orderinfo['aid'],$orderinfo['mid'],$orderinfo['totalprice'],'拼团失败退款');
                    }*/
                    //积分抵扣的返还
                    if($orderinfo['scoredkscore'] > 0){
                        \app\common\Member::addscore($orderinfo['aid'],$orderinfo['mid'],$orderinfo['scoredkscore'],'拼团失败退款返还');
                    }
                    //扣除消费赠送积分
                    \app\common\Member::decscorein($orderinfo['aid'],'lucky_collage',$orderinfo['id'],$orderinfo['ordernum'],'拼团失败退款扣除消费赠送');
                    //优惠券抵扣的返还
                    if($orderinfo['coupon_rid'] > 0){
                        \app\common\Coupon::refundCoupon2($orderinfo['aid'],$orderinfo['mid'],$orderinfo['coupon_rid'],$orderinfo);
                    }
                    Db::name('lucky_collage_order')->where('id',$orderinfo['id'])->update(['status'=>4]);
                    //退款成功通知
                    $tmplcontent = [];
                    if($product['failtklx']=='1'){
                        $tmplcontent['first'] = '拼团失败退款，¥'.$orderinfo['totalprice'].'已经退回您的付款账户，请留意查收。';
                    }else{
                        $tmplcontent['first'] = '拼团失败退款，¥'.$orderinfo['totalprice'].'已经退回您的余额账户，请留意查收。';
                    }
                    $tmplcontent['remark'] = '请点击查看详情~';
                    $tmplcontent['orderProductPrice'] = (string) $orderinfo['totalprice'];
                    $tmplcontent['orderProductName'] = $orderinfo['title'];
                    $tmplcontent['orderName'] = $orderinfo['ordernum'];
                    $tmplcontentNew = [];
                    $tmplcontentNew['character_string1'] = $orderinfo['ordernum'];//订单编号
                    $tmplcontentNew['thing2'] = $orderinfo['title'];//商品名称
                    $tmplcontentNew['amount3'] = $orderinfo['totalprice'];//退款金额
                    \app\common\Wechat::sendtmpl($orderinfo['aid'],$orderinfo['mid'],'tmpl_tuisuccess',$tmplcontent,m_url('activity/luckycollage/orderlist',$orderinfo['aid']),$tmplcontentNew);
                    //订阅消息
                    $tmplcontent = [];
                    $tmplcontent['amount6'] = $orderinfo['totalprice'];
                    $tmplcontent['thing3'] = $orderinfo['title'];
                    $tmplcontent['character_string2'] = $orderinfo['ordernum'];
                    
                    $tmplcontentnew = [];
                    $tmplcontentnew['amount3'] = $orderinfo['totalprice'];
                    $tmplcontentnew['thing6'] = $orderinfo['title'];
                    $tmplcontentnew['character_string4'] = $orderinfo['ordernum'];
                    \app\common\Wechat::sendwxtmpl($orderinfo['aid'],$orderinfo['mid'],'tmpl_tuisuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);

                    //短信通知
                    $member = Db::name('member')->where('id',$orderinfo['mid'])->find();
                    if($member['tel']){
                        $tel = $member['tel'];
                    }else{
                        $tel = $order['tel'];
                    }
                    $rs = \app\common\Sms::send($orderinfo['aid'],$tel,'tmpl_tuisuccess',['ordernum'=>$orderinfo['ordernum'],'money'=>$orderinfo['totalprice']]);
                    \app\common\Order::order_close_done($orderinfo['aid'],$orderinfo['id'],'lucky_collage');
				}
			}
		}
		
		if(getcustom('yueke')){
			$orderlist = Db::name('yueke_order')->where('status',0)->select()->toArray();
			$autocloseArr = [];
			foreach($orderlist as $order){
				if(!$autocloseArr[$order['aid']]){
					$autocloseArr[$order['aid']] = Db::name('yueke_set')->where('aid',$order['aid'])->value('autoclose');
				}
				if($order['createtime'] + $autocloseArr[$order['aid']]*60 > time()) continue;
				$aid = $order['aid'];
				$mid = $order['mid'];
				$orderid = intval($order['id']);
				$order = Db::name('yueke_order')->where('id',$orderid)->find();
				if(!$order || $order['status']!=0){
					//return $this->json(['status'=>0,'msg'=>'关闭失败,订单状态错误']);
				}else{
					//优惠券抵扣的返还
					if($order['coupon_rid'] > 0){
                        \app\common\Coupon::refundCoupon2($order['aid'],$order['mid'],$order['coupon_rid'],$order);
					}
					$rs = Db::name('yueke_order')->where('id',$orderid)->where('aid',$aid)->where('mid',$mid)->update(['status'=>4]);
				}
			}
		}
		if(getcustom('huodong_baoming')){
			$orderlist = Db::name('huodong_baoming_order')->where('status',0)->select()->toArray();
			$autocloseArr = [];
			foreach($orderlist as $order){
				if(!$autocloseArr[$order['aid']]){
					$autocloseArr[$order['aid']] = Db::name('huodong_baoming_set')->where('aid',$order['aid'])->value('autoclose');
				}
				if($order['createtime'] + $autocloseArr[$order['aid']]*60 > time()) continue;
				$aid = $order['aid'];
				$mid = $order['mid'];
				$orderid = intval($order['id']);
				$order = Db::name('huodong_baoming_order')->where('id',$orderid)->find();
				if(!$order || $order['status']!=0){
					//return $this->json(['status'=>0,'msg'=>'关闭失败,订单状态错误']);
				}else{
					//优惠券抵扣的返还
					if($order['coupon_rid'] > 0){
                        \app\common\Coupon::refundCoupon2($order['aid'],$order['mid'],$order['coupon_rid'],$order);
					}
					$rs = Db::name('huodong_baoming_order')->where('id',$orderid)->where('aid',$aid)->where('mid',$mid)->update(['status'=>4]);
				}
			}
		}

        if(getcustom('express_wx')){
            //判断是否自动派单
            $peisong_set = \db('peisong_set')->where('express_wx_status',1)->where('express_wx_paidan',1)->select()->toArray();
            if($peisong_set){
                foreach ($peisong_set as $set){
                    $orderlist = Db::name('shop_order')->where('aid',$set['aid'])->where('status',1)->where('freight_type',2)->whereNull('express_type')->limit(50)->select()->toArray();
                    foreach ($orderlist as $item){
                        Db::name('shop_order')->where('id',$item['id'])->update(['express_type'=>'express_wx']);
                        \app\custom\ExpressWx::addOrder('shop_order',$item);
                    }
                }
            }
        }

		if(getcustom('plug_mantouxia')){
			Db::name('form_order')->where('money','>',0)->where('paystatus',0)->where('createtime','<',time() - 3600)->delete();
			Db::name('payorder')->where('type','form')->where('status',0)->where('createtime','<',time() - 3600)->delete();
		}

		$wxpaylog = Db::name('wxpay_log')->whereRaw('fenzhangmoney>0 or fenzhangmoney2>0')->where('isfenzhang',0)->where('createtime','<',time()-60)->select()->toArray();
		if($wxpaylog){
			$dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
			$dbwxpayset = json_decode($dbwxpayset,true);

			foreach($wxpaylog as $v){
				$amount = intval(bcmul($v['fenzhangmoney'],100));
				$amount2 = intval(bcmul($v['fenzhangmoney2'],100));
				$sub_appid = '';
				$sub_mchid = $v['sub_mchid'];
				$appinfo = \app\common\System::appinfo($v['aid'],$v['platform']);
				if(!$sub_mchid) $sub_mchid = $appinfo['wxpay_sub_mchid'];
				if($v['bid'] > 0){
					$bset = Db::name('business_sysset')->where('aid',$v['aid'])->find();
					if($bset['wxfw_status'] == 1){
						$dbwxpayset = [
							'mchname'=>$bset['wxfw_mchname'],
							'appid'=>$bset['wxfw_appid'],
							'mchid'=>$bset['wxfw_mchid'],
							'mchkey'=>$bset['wxfw_mchkey'],
							'apiclient_cert'=>$bset['wxfw_apiclient_cert'],
							'apiclient_key'=>$bset['wxfw_apiclient_key'],
						];
						$receivers = [];
						$addreceivers = [];
						if(getcustom('business_more_account')){
							$wxpays =  json_decode($v['wxpay_submchid_text'],true);	
							foreach($wxpays as $sub){
								if($sub['amount']>0){
									$receivers[] = ['type'=>'MERCHANT_ID','account'=>$sub['submchid'],'amount'=>$sub['amount'],'description'=>$sub_mchid.'分账'];
									$addreceivers[] = ['type'=>'MERCHANT_ID','name'=>$sub['subname'],'account'=>$sub['submchid'],'relation_type'=>'SERVICE_PROVIDER'];
								}
							}
						}
						$receivers[] = ['type'=>'MERCHANT_ID','account'=>$dbwxpayset['mchid'],'amount'=>$amount,'description'=>$sub_mchid.'分账'];
						$addreceivers[] = ['type'=>'MERCHANT_ID','name'=>$dbwxpayset['mchname'],'account'=>$dbwxpayset['mchid'],'relation_type'=>'SERVICE_PROVIDER'];
						
					}elseif($bset['wxfw_status'] == 2){
						$receivers = [];
						$addreceivers = [];
						if($amount > 0){
							$receivers[] = ['type'=>'MERCHANT_ID','account'=>$dbwxpayset['mchid'],'amount'=>$amount,'description'=>$sub_mchid.'分账'];
							$addreceivers[] = ['type'=>'MERCHANT_ID','name'=>$dbwxpayset['mchname'],'account'=>$dbwxpayset['mchid'],'relation_type'=>'SERVICE_PROVIDER'];
						}
						if($amount2 > 0){
							$receivers[] = ['type'=>'MERCHANT_ID','account'=>$bset['wxfw2_mchid'],'amount'=>$amount2,'description'=>$sub_mchid.'分账'];
							$addreceivers[] = ['type'=>'MERCHANT_ID','name'=>$bset['wxfw2_mchname'],'account'=>$bset['wxfw2_mchid'],'relation_type'=>'PARTNER'];
						}
					}
				}
                else
                {
					$admin = Db::name('admin')->where('id',$v['aid'])->find();
					if($admin['choucheng_receivertype'] == 0){
						$receivers = [['type'=>'MERCHANT_ID','account'=>$dbwxpayset['mchid'],'amount'=>$amount,'description'=>$sub_mchid.'分账']];
						$addreceivers = [['type'=>'MERCHANT_ID','name'=>$dbwxpayset['mchname'],'account'=>$dbwxpayset['mchid'],'relation_type'=>'SERVICE_PROVIDER']];
					}elseif($admin['choucheng_receivertype'] == 1){
						$receivers = [['type'=>'MERCHANT_ID','account'=>$admin['choucheng_receivertype1_account'],'amount'=>$amount,'description'=>$sub_mchid.'分账']];
						$addreceivers = [['type'=>'MERCHANT_ID','name'=>$admin['choucheng_receivertype1_name'],'account'=>$admin['choucheng_receivertype1_account'],'relation_type'=>'PARTNER']];
					}elseif($admin['choucheng_receivertype'] == 2){
						if($admin['choucheng_receivertype2_openidtype'] == 0){
							if($admin['choucheng_receivertype2_name']){
								$receivers = [['type'=>'PERSONAL_OPENID','name'=>$admin['choucheng_receivertype2_name'],'account'=>$admin['choucheng_receivertype2_account'],'amount'=>$amount,'description'=>$sub_mchid.'分账']];
								$addreceivers = [['type'=>'PERSONAL_OPENID','name'=>$admin['choucheng_receivertype2_name'],'account'=>$admin['choucheng_receivertype2_account'],'relation_type'=>'PARTNER']];
							}else{
								$receivers = [['type'=>'PERSONAL_OPENID','account'=>$admin['choucheng_receivertype2_account'],'amount'=>$amount,'description'=>$sub_mchid.'分账']];
								$addreceivers = [['type'=>'PERSONAL_OPENID','account'=>$admin['choucheng_receivertype2_account'],'relation_type'=>'PARTNER']];
							}
						}else{
							$sub_appid = $appinfo['appid'];
							if($v['platform'] == 'wx'){
								$account = $admin['choucheng_receivertype2_accountwx'];
							}else{
								$account = $admin['choucheng_receivertype2_account'];
							}
							if($admin['choucheng_receivertype2_name']){
								$receivers = [['type'=>'PERSONAL_SUB_OPENID','name'=>$admin['choucheng_receivertype2_name'],'account'=>$account,'amount'=>$amount,'description'=>$sub_mchid.'分账']];
								$addreceivers = [['type'=>'PERSONAL_SUB_OPENID','name'=>$admin['choucheng_receivertype2_name'],'account'=>$account,'relation_type'=>'PARTNER']];
							}else{
								$receivers = [['type'=>'PERSONAL_SUB_OPENID','account'=>$account,'amount'=>$amount,'description'=>$sub_mchid.'分账']];
								$addreceivers = [['type'=>'PERSONAL_SUB_OPENID','account'=>$account,'relation_type'=>'PARTNER']];
							}
						}
					}
				}
                $multi=false;
                if(getcustom('yx_queue_free_fenzhang_wxpay')) {
                    $set = Db::name('queue_free_set')->where('aid',$v['aid'])->where('bid',0)->find();
                    if($set['receive_account'] == 'fenzhang_wxpay'){
                        $multi = true;
                    }
                }
				$rs = $this->profitsharing($v,$receivers,$addreceivers,$sub_mchid,$dbwxpayset,$v['transaction_id'],$sub_appid,0,$multi);
				if($rs['status'] == 0){
                    \think\facade\Log::write(__FILE__.__LINE__);
					\think\facade\Log::write($rs);
					Db::name('wxpay_log')->where('id',$v['id'])->update(['isfenzhang'=>2,'fz_errmsg'=>$rs['msg']]);
				}else{
					Db::name('wxpay_log')->where('id',$v['id'])->update(['isfenzhang'=>1,'fz_errmsg'=>$rs['msg'],'fz_ordernum'=>$rs['ordernum']]);
				}
			}
		}

        if(getcustom('member_gongxian')){
            //贡献值过期
            $adminlist = Db::name('admin')->where('member_gongxian_status',1)->select()->toArray();
            foreach($adminlist as $admin) {
                $sysset = Db::name('admin_set')->where('aid',$admin['id'])->find();
                //每笔记录超过过期时间贡献值即过期
                $level_with_expire = Db::name('member_level')->where('aid',$admin['id'])->where('gongxian_days','>',0)->column('id,gongxian_days','id');
                $loglist = [];
                if($level_with_expire){
                    $levelids_with_expire = array_keys($level_with_expire);
                    foreach ($level_with_expire as $levelids_item){
                        $log1 = Db::name('member_gongxianlog')->alias('ml')->leftJoin('member m', 'm.id = ml.mid')
                            ->where('ml.aid',$admin['id'])->where('ml.value','>',0)->where('ml.is_expire',0)->where('m.levelid','=',$levelids_item['id'])->where("ml.createtime + '".($levelids_item['gongxian_days']*86400)."' < ".$time)
                            ->field('ml.*,m.levelid')->select()->toArray();
                        if($log1)
                            $loglist = array_merge((array)$log1, (array)$loglist);
                    }
                    $log2 = Db::name('member_gongxianlog')->alias('ml')->leftJoin('member m', 'm.id = ml.mid')
                        ->where('ml.aid',$admin['id'])->where('ml.value','>',0)->where('ml.is_expire',0)->where('m.levelid','not in',$levelids_with_expire)->where("ml.createtime + '".($sysset['gongxian_days']*86400)."' < ".$time)
                        ->field('ml.*,m.levelid')->select()->toArray();
                    if($log2)
                        $loglist = array_merge((array)$log2, (array)$loglist);
                }else{
                    $loglist = Db::name('member_gongxianlog')->alias('ml')->leftJoin('member m', 'm.id = ml.mid')
                        ->where('ml.aid',$admin['id'])->where('ml.value','>',0)->where('ml.is_expire',0)->where("ml.createtime + '".($sysset['gongxian_days']*86400)."' < ".$time)
                        ->field('ml.*,m.levelid')->select()->toArray();
                }
                if($loglist){
                    foreach ($loglist as $logitem){
                        Db::name('member_gongxianlog')->where('id',$logitem['id'])->update(['is_expire' => 1, 'expire_time' => $time]);
                        \app\common\Member::addgongxian($logitem['aid'],$logitem['mid'],$logitem['value']*-1,'过期',$logitem['channel'],$logitem['orderid']);
                    }
                }
            }
        }

		if(getcustom('member_levelup_auth')){
			$orderlist = Db::name('member_salelevel_order')->where('status',0)->select()->toArray();
			foreach($orderlist as $order){
				if($order['createtime'] + 30*60 > time()) continue;
				$aid = $order['aid'];
				$mid = $order['mid'];
				$orderid = intval($order['id']);
				$order = Db::name('member_salelevel_order')->where('id',$orderid)->find();
				if(!$order || $order['status']!=0){
					//return $this->json(['status'=>0,'msg'=>'关闭失败,订单状态错误']);
				}else{
					$rs = Db::name('member_salelevel_order')->where('id',$orderid)->where('aid',$aid)->where('mid',$mid)->update(['status'=>-1]);
				}
			}
		}


		$this->fenhong('perminute');

		if(getcustom('restaurant')){
			\app\custom\Restaurant::auto_perminute();
		}

        if(getcustom('everyday_hongbao')) {
            if(date('G') == 8) {
                $this->hbcalculate();
            }
        }

        if(getcustom('image_search')){
            $this->product_img_baidu_sync();
        }
        if(getcustom('choujiang_time')){
            $this->run_dscj();
        }
        if(getcustom('invite_free')){
        	//结束已完成的免单未发放的订单
        	\app\custom\InviteFree::deal_finishfree();
		}
        //计次优惠券过期结算
        if(getcustom('coupon_times_expire')){
            \app\model\Counpon::couponExpire();
        }
//            $this->huidong();

		if(getcustom('extend_tour')){
			//导游相册订单
			$admin = Db::name('admin')
	            ->where('status',1)
	            ->field('id')
	            ->select()
	            ->toArray();
	        if($admin){
	            foreach($admin as $v){
					$tour_custom  = new \app\custom\TourCustom();
		        	$deal_tpl = $tour_custom->get_updated_order($v['id']);
		        }
		        unset($v);
	        }
		}

		if(getcustom('extend_yuyue_car')){
			//预约洗车订单
//	        \app\custom\YuyueCustom::deal_order();
            \app\custom\YuyueCustom::dispatch_order();
		}

        if(getcustom('score_to_money_auto')){
            //积分每日自动转入余额,每天0点1分执行
            if(date('G')=='0'){
                $this->scoreToMoney();
            }
        }

		$this->sxpayquery();

		$this->fifaauto();
		if(getcustom('lot_cerberuse')){
            $this->cerberuseExpire();
        }

        //再次同步小程序发货
        \app\common\Order::retryUploadShipping();

		if(getcustom('commission_to_score')){
		    //佣金自动转积分，每日执行一次
		    if(date('H:i')=='00:01'){
		        $this->commission_to_score();
            }
        }
        if(date('H:i')=='00:01'){
            if(getcustom('yx_day_give')){
                $this->day_give();
            }
        }

        if(getcustom('product_handwork')){
        	//手工活
            \app\custom\HandWork::sendmoney();
        }

        //统计计算累计积分
        $this->count_totalscore();

        if(getcustom('yx_queue_free_fenzhang_wxpay')){
            $log = Db::name('queue_free_log')->where('receive_account','fenzhang_wxpay')->where('isfenzhang',0)->where('createtime','<',time()-60)->select()->toArray();
            if($log){
                $i = 1;
                foreach ($log as $v){
                    $order = json_decode($v['payorderjson'],true);
                    $money = round($v['money_give']*(100-$v['fenzhang_wxpay_rate'])/100,2);
                    if($order && $money > 0){
                        $rs = \app\custom\QueueFree::wxFenzhang($v['aid'],$v['bid'],$order['mid'],$order,$money,$v['mid'],$v['id'],$order['orderType']);
                        if($rs['status'] != 1)
                            \app\common\Member::addmoney($v['aid'],$v['mid'],$v['money_give'],t('排队奖励返现',$v['aid']));
                        $remainder = $i % 30;
                        if($remainder == 0){
                            sleep(1);
                        }
                        $i++;
                    }
                }
            }
            //10分钟 结束分账
            $fzlog = Db::name('wxpay_fzlog')->distinct(true)->field('transaction_id')->where('isfinish',0)->where('isfenzhang','=',1)
                ->where('createtime','<',time()-600)->where('finish_error_times','<',3)->select()->toArray();
            if($fzlog){
                $i = 1;
                foreach ($fzlog as $v){
                    $info = Db::name('wxpay_fzlog')->where('transaction_id',$v['transaction_id'])->find();
                    \app\custom\QueueFree::profitsharingfinish($info['aid'],$info['transaction_id'],$info);
                    $remainder = $i % 60;
                    if($remainder == 0){
                        sleep(1);
                    }
                    $i++;
                }
            }
        }

        if(getcustom('douyin_groupbuy')){
        	//抖音券关闭
            \app\custom\DouyinGroupbuyCustom::autoclose();
        }
        if(getcustom('ganer_fenxiao')){
            $this->ganer_prize_pool();
        }

        //ERP旺店物流：每5分钟调用查询物流同步接口获取待物流同步数据，每次取数据100条，获取后将数据同步至商城平台，处理完成后调用物流同步回写接口将处理状态（成功 or 失败）回写旺店通ERP
        if(getcustom('erp_wangdiantong')){
            $checkTime = cache('wdt_logistics_check_time');
            if(!$checkTime || $checkTime<time()-5*60){
                $adminlist = Db::name('admin')->where('wdt_status',1)->field('id,wdt_status')->select()->toArray();
                foreach ($adminlist as $k=>$v){
                    $c = new \app\custom\Wdt($v['id'],0);
                    $c->logisticsQuery();
                }
                cache('wdt_logistics_check_time',time());
            }
        }
        if(getcustom('consumer_value_add')){
            //自动释放平台绿色积分
            $this->release_green_score();
        }
        if(getcustom('product_pickup_device')){
            $this->pickupDeviceAddstockRemind();
        }

        //积分转赠 未支付自动取消
        if(getcustom('score_transfer_sxf')){
            $this->closeScoreTransferSxfOrder();
        }

        if(getcustom('yuyue_before_starting')){
            //预约开始前通知
            self::sendnotice_time();
        }
        if(getcustom('yuyue_datetype1_autoendorder')){
            //时间段自动完成
            self::datetype1_autoendorder();
        }
		//酒店未支付自动取消
		if(getcustom('hotel')){
			$this->closeHotelorder();
		}

        //鱼塘 到达时间
        if(getcustom('extend_fish_pond')){
            $this->fishpond();
        }

        //约课订单 核销超过24小时的未完成的订单
        if(getcustom('yueke_extend')){
            $this->hexiaoyuekeorder();
        }

        if(getcustom('supply_zhenxin')){
        	//甄新汇选计划任务
            \app\custom\SupplyZhenxinCustom::autotask();
        }
        if(getcustom('supply_yongsheng')){
            //永盛计划任务
            \app\custom\SupplyYongsheng::autotask();
        }
        if(getcustom('car_hailing')){
            $orderlist = Db::name('car_hailing_order')->where('status',0)->select()->toArray();
            $autocloseArr = [];
            foreach($orderlist as $order){
                if(!$autocloseArr[$order['aid']]){
                    $autocloseArr[$order['aid']] = Db::name('car_hailing_set')->where('aid',$order['aid'])->value('autoclose');
                }
                if($order['createtime'] + $autocloseArr[$order['aid']]*60 > time()) continue;
                $aid = $order['aid'];
                $mid = $order['mid'];
                $orderid = intval($order['id']);
                $order = Db::name('car_hailing_order')->where('id',$orderid)->find();
                if(!$order || $order['status']!=0){
                    //return $this->json(['status'=>0,'msg'=>'关闭失败,订单状态错误']);
                }else{
                    //优惠券抵扣的返还
                    if($order['coupon_rid'] > 0){
                        \app\common\Coupon::refundCoupon2($order['aid'],$order['mid'],$order['coupon_rid'],$order);
                    }
                    $rs = Db::name('car_hailing_order')->where('id',$orderid)->where('aid',$aid)->where('mid',$mid)->update(['status'=>4]);
                }
            }
        }
        if(getcustom('extend_invite_redpacket')){
        	//邀请分红包
            \app\custom\InviteRedpacketCustom::endRedpacketLog();
        }
        if(getcustom('alipay_fenzhang')){
            \app\common\Alipay::DoFenzhang();
        }
        if(getcustom('yx_queue_free_other_mode')){
            $queue_free_time = cache('yx_queue_free_average_time');
            if(!$queue_free_time || $queue_free_time<time()-3*60) {
                $this->queuefreeAverage();
                cache('yx_queue_free_average_time',time());
            }
            //金额分配异步发放
            $queue_free_moneyback_time = cache('yx_queue_free_moneyback_time');
            if(!$queue_free_moneyback_time || $queue_free_moneyback_time < time()-3*60) {
                $this->queuefreeMoneyBack();
                cache('yx_queue_free_moneyback_time',time());
            }
        }
        if(getcustom('commission_mendian_hexiao_coupon')){
            $this->commissionFenrun();
        }

        if(getcustom('wx_channels_sharer_apply')){
        	//分享员绑定查询定时任务
            \app\common\WxChannels::deal_applybind();
        }
        if(getcustom('pay_huifu_fenzhang')){
            $this->huifuFenzheng();
        }
        if(getcustom('yx_queue_free_quit_give_coupon')){
            $queue_free_quit_time = cache('yx_queue_free_quit_queue');
            if(!$queue_free_quit_time || $queue_free_quit_time<time()-3*60) {
                $this->quitSendCoupon();
                cache('yx_queue_free_quit_queue',time());
            }
        }

        if(getcustom('shop_giveorder')){
        	//处理赠送订单
        	self::dealgiveorder();
        }
        if(getcustom('member_forzengxcommission')){
            //处理佣金解冻
            self::dealforzengxcommission();
        }

        if(getcustom('yx_invite_cashback_commission_day')){
            //推三返一佣金每天发放
            $this->inviteCashbackCommissionDay();
        }

        if(getcustom('commission_withdrawfee_fundpool')){
            //基金池结算
            $this->commissionWithdrawfeeFundpool();
        }

        if(getcustom('money_commission_withdraw_fenxiao')){
            //定时查询余额、佣金提现分销
            $this->moneyCommissionWithdrawFenxiao();
        }

        if(getcustom('product_chinaums_subsidy')){
            //国补回退资格
            $this->backQualification();
        }

        if(getcustom('extend_hanglvfeike')){
            //航空旅客机票定时任务
            \app\custom\Hanglvfeike::autotask();
        }
		if(getcustom('meituan_xinyoujie')){
			//美团订单
			$this->meituanOrder();
		}
        if(getcustom('extend_zhiyoubao_theater')){
            //票务剧院定时任务
            \app\custom\Zhiyoubao::autotask();
        }
        if(getcustom('yx_money_send_hongbao')){
            $this->moneySendHongbaoExpire();
        }
        if(getcustom('fenhong_max') && getcustom('fenhong_max_buymoney')){
            $this->fenhongMaxAddBuymoney();
        }
        if(getcustom('luntan_pay_top')){
            $this->luntanTopExpire();
        }
        if(getcustom('yx_daily_lirun_choujiang')){
            $this->lirunKaijiang();
        }
        if(getcustom('wx_express_intracity')){
            //$this->autoWxtcOrder();
        }
        if(getcustom('yx_queue_free_multi_team_business')){
            $queue_free_multi_business_time = cache('queue_free_multi_business_time');
            if(!$queue_free_multi_business_time || time() - $queue_free_multi_business_time > 180 ){
                $this-> queueFreeMultiBusiness();
                cache('queue_free_multi_business_time',time());
            }
            $this->queueFreeTeamJoin();
        }
        if(getcustom('yx_farm')){
            $this->checkFarmTree();
        }
	}

	private function sxpayquery(){
		$payorderList = Db::name('payorder')->where('issxpay',1)->where('status',0)->where('createtime','>',time()-10*60)->where('createtime','<',time()-2*60)->select()->toArray();
		foreach($payorderList as $payorder){
			$aid = $payorder['aid'];
			$mid = $payorder['mid'];
			$ordernum = $payorder['ordernum'];
			$rs = \app\custom\Sxpay::tradeQuery($payorder);
//			\think\facade\Log::write([
//                'file'=>__FILE__.__FUNCTION__,
//                $rs
//            ]);
			if($rs['status'] == 1 && ($rs['data']['tranSts'] == 'CLOSED' || $rs['data']['tranSts'] == 'FAIL' || $rs['data']['tranSts'] == 'CANCELED')){
				Db::name('payorder')->where('id',$payorder['id'])->update(['issxpay'=>0]);
			}
			if($rs['status'] == 1 && $rs['data']['tranSts'] == 'SUCCESS'){
				$attach = explode(':',$rs['data']['extend']);
				$aid = intval($attach[0]);
				$tablename = $attach[1];
				$platform = $attach[2];
				if($platform == 'sxpaymp') $platform = 'mp';
				if($platform == 'sxpaywx') $platform = 'wx';
				if($platform == 'sxpayalipay') $platform = 'alipay';
				$transaction_id = $rs['data']['transactionId'];
				$isbusinesspay = 0;
				if($attach[4]){
					$isbusinesspay = 1;
				}
				Db::name('payorder')->where('id',$payorder['id'])->update(['platform'=>$platform,'isbusinesspay'=>$isbusinesspay]);

//                if($payorder['money'] != $total_fee*0.01){
                    //金额不一致 退款
//                    continue;
//                    return ['status'=>2,'msg'=>'支付金额和订单金额不一致','payorder'=>$payorder];
//                }
//                if($payorder['status'] == 2){
                    //支付单取消
//                    continue;
//                    return ['status'=>2,'msg'=>'订单已修改，请重新发起支付','payorder'=>$payorder];
//                }

				if($payorder['score'] > 0){
					$rs = \app\common\Member::addscore($aid,$mid,-$payorder['score'],'支付订单，订单号：'.$ordernum);
					if($rs['status'] == 0){
						$order = $payorder;
						$order['totalprice'] = $order['money'];
						$order['paytypeid'] = 2;
						\app\common\Order::refund($order,$order['money'],'积分扣除失败退款');
						continue;
					}
				}
				$rs = \app\model\Payorder::payorder($payorder['id'],'微信支付',2,$transaction_id);
				if($rs['status']==0) continue;

				$total_fee = intval($payorder['money']*100);

				$set = Db::name('admin_set')->where('aid',$aid)->find();
                if(getcustom('business_score_duli_set')){//如果商户单独设置了赠送积分规则
                    $business_duli = Db::name('business')->where('aid',$aid)->where('id',$payorder['bid'])->field('scorein_money,scorein_score,scorecz_money,scorecz_score')->find();
                    if(!is_null($business_duli['scorein_money']) && !is_null($business_duli['scorein_score'])){
                        $set['scorein_money'] = $business_duli['scorein_money'];
                        $set['scorein_score'] = $business_duli['scorein_score'];
                    }
                    if(!is_null($business_duli['scorecz_money']) && !is_null($business_duli['scorecz_score'])){
                        $set['scorecz_money'] = $business_duli['scorecz_money'];
                        $set['scorecz_score'] = $business_duli['scorecz_score'];
                    }
                }
                $iszs = true;
                if(getcustom('score_stacking_give_set') && $set['score_stacking_give_set'] == 2){
                    $iszs = false;
                }
				//消费送积分
				if($tablename != 'recharge' && $set['scorein_money']>0 && $set['scorein_score']>0 && $iszs){
					$givescore = floor($total_fee*0.01 / $set['scorein_money']) * $set['scorein_score'];
					$res = \app\common\Member::addscore($aid,$mid,$givescore,'消费送'.t('积分'));
					if($res && $res['status'] == 1){
						//记录消费赠送积分记录
						\app\common\Member::scoreinlog($aid,0,$mid,$payorder['type'],$payorder['orderid'],$payorder['ordernum'],$givescore,$total_fee);
					}
				}
				if(getcustom('business_moneypay')){ //多商户设置的消费送积分
					if($payorder['bid'] > 0 && $tablename != 'shop'){
						$bset = Db::name('business_sysset')->where('aid',$aid)->find();
						$givescore = floor($total_fee*0.01 / $bset['scorein_money']) * $bset['scorein_score'];
						$res = \app\common\Member::addscore($aid,$mid,$givescore,'消费送'.t('积分'));
						if($res && $res['status'] == 1){
							//记录消费赠送积分记录
							\app\common\Member::scoreinlog($aid,$payorder['bid'],$mid,$payorder['type'],$payorder['orderid'],$payorder['ordernum'],$givescore,$total_fee);
						}
					}
				}
				//充值送积分
				if($tablename == 'recharge' && $set['scorecz_money']>0 && $set['scorecz_score']>0){
					$givescore = floor($total_fee*0.01 / $set['scorecz_money']) * $set['scorecz_score'];
					\app\common\Member::addscore($aid,$mid,$givescore,'充值送'.t('积分'));
				}

				if($rs['status'] == 1){
					//记录
					$data = array();
					$data['aid'] = $aid;
					$data['mid'] = $payorder['mid'];
					$data['openid'] = $rs['data']['uuid'];
					$data['tablename'] = $tablename;
					$data['givescore'] = $givescore;
					$data['ordernum'] = $rs['data']['ordNo'];
					$data['mch_id'] = $rs['data']['mno'];
					$data['transaction_id'] = $rs['data']['transactionId'];
					$data['total_fee'] = $rs['data']['oriTranAmt'];
					$data['createtime'] = time();
					Db::name('wxpay_log')->insert($data);
					\app\common\Member::uplv($aid,$mid);
				}
			}
		}
	}
	private function hbcalculate()
    {
        if(getcustom('everyday_hongbao')) {
            $date = date('Y-m-d');
            $todayStart = strtotime($date);
            $yestdayStart = $todayStart - 86400;
            $yestdayEnd = $yestdayStart + 86399;

            $yestdayDate = date('Y-m-d',$yestdayStart);

            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset) {
                $aid = $sysset['aid'];
                $hd = Db::name('hongbao_everyday')->where('aid',$aid)->find();
                if($hd['status']!=1 || $hd['num'] < 1) continue;
                if($hd['starttime'] > time() && $hd['endtime'] < time()) continue;
                $haveHongbao = Db::name('hongbao_everyday_list')->where('aid',$aid)->where('createdate','=',$date)->count();
                if($haveHongbao > 0) continue;
                //前一天业绩
                if($hd['shop_order_money_type'] == 'pay') {
                    $where[] = ['status', 'in', [1,2,3]];
                    $where[] = ['paytime', 'between', [$yestdayStart,$yestdayEnd]];
                }else if($hd['shop_order_money_type'] == 'receive') {
                    $where[] = ['status', '=', 3];
                    $where[] = ['collect_time', 'between', [$yestdayStart,$yestdayEnd]];
                } else {
                    $where[] = ['status', 'in', [1,2,3]];
                    $where[] = ['paytime', 'between', [$yestdayStart,$yestdayEnd]];
                }
                $totalOrder = Db::name('shop_order')->where('aid',$aid)->where('bid', 0)->where($where)->sum('totalprice');
                $totalOrder = round($totalOrder * $hd['hongbao_bl'] / 100,2);
                $orderBusiness = Db::name('shop_order')->where('aid',$aid)->where('bid', '>',0)->where($where)->field('id,aid,bid,totalprice')->select()->toArray();
                $business = Db::name('business')->where('aid',$aid)->column('feepercent','id');
                $totalOrderBusiness = 0;
                foreach ($orderBusiness as $item) {
                    $totalOrderBusiness += $item['totalprice'] * $business[$item['bid']] / 100;
                }
                $totalOrderBusiness = round($totalOrderBusiness * $hd['hongbao_bl_business'] / 100,2);
                //买单业绩
                $maidanOrder = Db::name('maidan_order')->where('aid',$aid)->where('createtime','between',[$yestdayStart,$yestdayEnd])->where('status',1)->select()->toArray();
                $totalMaidan = 0;
                foreach ($maidanOrder as $item) {
                    $totalMaidan += $item['paymoney'] * $business[$item['bid']] / 100;
                }
                $totalMaidan = round($totalMaidan * $hd['hongbao_bl_maidan'] / 100,2);

                $yestdayLeft = Db::name('hongbao_everyday_list')->where('aid',$aid)->where('createdate','=',$yestdayDate)->sum('left');
                $total = $totalOrder + $totalOrderBusiness + $totalMaidan + $yestdayLeft;
                //生成随机红包(改为平均)
                $dataHongbao = [];
                $time = time();
//            $total = $total * 100;
                $minMoney = 1;
                $avgMoney = $total/$hd['num'];
                $avgMoney = substr(sprintf("%.3f", $avgMoney), 0, -1);
//            dd($total);
                if($avgMoney < 0.01) continue;
                for($i=0;$i<$hd['num'];$i++) {
                    if($i == $hd['num'] - 1)
                        $money = $total;
                    else
//                    $money = rand($minMoney,($total - ($hd['num']-$i) * $minMoney));
                        $money = $avgMoney;
                    $dataHongbao[] = [
                        'aid' => $aid,
                        'createdate' => $date,
                        'createtime' => $time,
                        'money' => $money,
                        'left' => $money,
                    ];
                    $total -= $money;
                    if($total <= 0) {
                        break;
                    }
                }
                Db::name('hongbao_everyday_list')->limit(100)
                    ->insertAll($dataHongbao);
            }
        }
    }
	//每小时执行一次
	private function perhour(){
		$time = time();
		//商城自动收货
		$shopsetlist = Db::name('shop_sysset')->select()->toArray();
		foreach($shopsetlist as $sysset){
			$aid = $sysset['aid'];
			if($aid){

                if(getcustom('member_tag')){
                    \app\model\Member::member_tag($aid);
                }

				if(getcustom('plug_yang',$aid)){
					$list = Db::name('shop_order')->where("aid={$aid} and bid=0 and status=2 and ".time().">`send_time` + 86400*".$sysset['autoshdays'])->select()->toArray();
				}else{
                    $owhere = [];
                    $owhere[] = ['aid','=',$aid];
                    if(getcustom('product_weight',$aid)){
                        //信用额度付款的，不自动发货
                        $owhere[] = ['paytypeid','<>',38];
                    }
					$list = Db::name('shop_order')->where($owhere)->where("status=2 and ".time().">`send_time` + 86400*".$sysset['autoshdays'])->select()->toArray();
				}
				foreach($list as $order){
                    $refundOrder = Db::name('shop_refund_order')->where('refund_status','in',[1,4])->where('aid',$aid)->where('orderid',$order['id'])->count();
                    if($refundOrder){
                        continue;
                    }
                    //部分发货 不进行收货
                    if($order['express_isbufen'] ==1){
                        continue;
                    }
					$rs = \app\common\Order::collect($order,'shop');
					if($rs['status'] == 0) continue;
					Db::name('shop_order')->where('id',$order['id'])->update(['status'=>3,'collect_time'=>time()]);
					Db::name('shop_order_goods')->where('orderid',$order['id'])->update(['status'=>3,'endtime'=>time()]);
					if(getcustom('ciruikang_fenxiao',$aid)){
                        //一次购买升级
                        \app\common\Member::uplv($aid,$order['mid'],'shop',['onebuy'=>1,'onebuy_orderid'=>$order['id']]);
                    }else{
                        \app\common\Member::uplv($aid,$order['mid']);
                    }

                    if(getcustom('member_shougou_parentreward',$aid)){
                        //首购解锁
                        Db::name('member_commission_record')->where('orderid',$order['id'])->where('type','shop')->where('status',0)->where('islock',1)->where('aid',$order['aid'])->where('remark','like','%首购奖励')->update(['islock'=>0]);
                    }

                    if(getcustom('transfer_order_parent_check')) {
                        //确认收货增加有效金额
                        \app\common\Fenxiao::addTotalOrderNum($aid, $order['mid'], $order['id'],3);
                    }

                    //即拼
                    if(getcustom('yx_collage_jipin')) {
                        \app\common\Order::collageJipinOrder($aid,$order['id']);
                    }
                    //即拼7人成团
                    if(getcustom('yx_collage_jipin2')) {
                        \app\common\Order::jipin($aid,$order,3);
                    }

                    //消费赠送佣金提现额度
                    if(getcustom('commission_withdraw_limit')) {
                        $sysset = Db::name('admin_set')->where('aid',$aid)->find();
                        $user_info = Db::name('member')->where('aid',$aid)->where('id',$order['mid'])->field('shop_commission_withdraw_limit,commission_withdraw_limit_infinite')->find();
                        if($sysset['commission_withdraw_limit_set'] == 1){
                            //商城消费赠送佣金提现额度
                            $consume_withdraw_limit_arr = json_decode($sysset['shop_consume_commission_withdraw_limit'],true);
                            if($consume_withdraw_limit_arr){
                                //把数组按照奖励最大的排序
                                usort($consume_withdraw_limit_arr, function($a, $b) {
                                    if ($a['money'] == $b['money']) return 0;
                                    return ($a['money'] > $b['money']) ? -1 : 1;
                                });
                                foreach ($consume_withdraw_limit_arr as $vg){
                                    if($vg['money'] && $vg['give'] && $order['totalprice'] >= $vg['money']){
                                        //增加佣金提现额度
                                        Db::name('member')->where('aid', $aid)->where('id', $order['mid'])->update(['commission_withdraw_limit' => Db::raw("commission_withdraw_limit+" . $vg['give'])]);
                                        break;
                                    }
                                }
                            }

                            //商城消费赠送佣金提现额度-无限制
                            if($user_info['commission_withdraw_limit_infinite'] == 0 && $sysset['shop_consume_money_give_infinite'] > 0){
                                //统计确认收货的订单金额
                                $collectOrdrMoney = Db::name('shop_order')->where('aid',$aid)->where('mid',$order['mid'])->where('status',3)->sum('totalprice');
                                if($collectOrdrMoney >= $sysset['shop_consume_money_give_infinite']){
                                    //增加佣金提现额度-无限制
                                    Db::name('member')->where('aid', $aid)->where('id', $order['mid'])->update(['commission_withdraw_limit_infinite' =>1]);
                                }
                            }
                        }
                    }
				}

				if(getcustom('plug_yang',$aid)){
					$list = Db::name('shop_order')->where("aid={$aid} and bid>0 and status=2 and ".time().">`send_time` + 3600*(select autocollecthour from ddwx_business where id=ddwx_shop_order.bid)")->select();
					foreach($list as $order){
						$refundOrder = Db::name('shop_refund_order')->where('refund_status','in',[1,4])->where('aid',$aid)->where('orderid',$order['id'])->count();
						if($refundOrder){
							continue;
						}
						$rs = \app\common\Order::collect($order,'shop');
						if($rs['status'] == 0) continue;
						Db::name('shop_order')->where('id',$order['id'])->update(['status'=>3,'collect_time'=>time()]);
						Db::name('shop_order_goods')->where('orderid',$order['id'])->update(['status'=>3,'endtime'=>time()]);
						\app\common\Member::uplv($aid,$order['mid']);
					}
				}
			}
		}
		//秒杀自动收货
        $seckillsetlist = Db::name('seckill_sysset')->select()->toArray();
        foreach($seckillsetlist as $sysset){
            $aid = $sysset['aid'];
            if($aid){
                $list = Db::name('seckill_order')->where("aid={$aid} and status=2 and ".time().">`send_time` + 86400*".$sysset['autoshdays'])->select()->toArray();
                foreach($list as $order){
                    $rs = \app\common\Order::collect($order,'seckill');
                    if($rs['status'] == 0) continue;
                    Db::name('seckill_order')->where('id',$order['id'])->update(['status'=>3,'collect_time'=>time()]);
                }
            }
        }
		//拼团自动收货
		$collagesetlist = Db::name('collage_sysset')->select()->toArray();
		foreach($collagesetlist as $sysset){
			$aid = $sysset['aid'];
			if($aid){
				$list = Db::name('collage_order')->where("aid={$aid} and status=2 and ".time().">`send_time` + 86400*".$sysset['autoshdays'])->select()->toArray();
				foreach($list as $order){
					$rs = \app\common\Order::collect($order,'collage');
					if($rs['status'] == 0) continue;
					Db::name('collage_order')->where('id',$order['id'])->update(['status'=>3,'collect_time'=>time()]);
				}
			}
		}
		//团购自动收货
        $tuangousetlist = Db::name('tuangou_sysset')->select()->toArray();
        foreach($tuangousetlist as $sysset){
            $aid = $sysset['aid'];
            if($aid){
                $list = Db::name('tuangou_order')->where("aid={$aid} and status=2 and ".time().">`send_time` + 86400*".$sysset['autoshdays'])->select()->toArray();
                foreach($list as $order){
                    $rs = \app\common\Order::collect($order,'tuangou');
                    if($rs['status'] == 0) continue;
                    Db::name('tuangou_order')->where('id',$order['id'])->update(['status'=>3,'collect_time'=>time()]);
                }
            }
        }
		//幸运拼团自动收货
		$collagesetlist = Db::name('lucky_collage_sysset')->select()->toArray();
		foreach($collagesetlist as $sysset){
			$aid = $sysset['aid'];
			if($aid){
				$list = Db::name('lucky_collage_order')->where("aid={$aid} and status=2 and ".time().">`send_time` + 86400*".$sysset['autoshdays'])->select()->toArray();
				foreach($list as $order){
					$rs = \app\common\Order::collect($order,'lucky_collage');
					if($rs['status'] == 0) continue;
					Db::name('lucky_collage_order')->where('id',$order['id'])->update(['status'=>3,'collect_time'=>time()]);
				}
			}
		}

		//砍价自动收货
		$kanjiasetlist = Db::name('kanjia_sysset')->select()->toArray();
		foreach($kanjiasetlist as $sysset){
			$aid = $sysset['aid'];
			if($aid){
				$list = Db::name('kanjia_order')->where("aid={$aid} and status=2 and ".time().">`send_time` + 86400*".$sysset['autoshdays'])->select()->toArray();
				foreach($list as $order){
					$orderid = $order['id'];
					$mid = $order['mid'];
					$rs = \app\common\Order::collect($order,'kanjia');
					if($rs['status'] == 0) continue;
					Db::name('kanjia_order')->where('aid',$aid)->where('mid',$mid)->where('id',$orderid)->update(['status'=>3,'collect_time'=>time()]);
				}
			}
		}
		//积分商城自动收货
		$scoreshopsetlist = Db::name('scoreshop_sysset')->select()->toArray();
		foreach($scoreshopsetlist as $sysset){
			$aid = $sysset['aid'];
			if($aid){
				$list = Db::name('scoreshop_order')->where("aid={$aid} and status=2 and ".time().">`send_time` + 86400*".$sysset['autoshdays'])->select()->toArray();
				foreach($list as $order){
                    $rs = \app\common\Order::collect($order,'scoreshop');
                    if($rs['status'] == 0) continue;
					Db::name('scoreshop_order')->where('id',$order['id'])->update(['status'=>3,'collect_time'=>time()]);
                    Db::name('scoreshop_order_goods')->where('orderid',$order['id'])->update(['status'=>3,'endtime'=>time()]);
				}
			}
		}

        foreach($scoreshopsetlist as $sysset){
            $aid = $sysset['aid'];
            if($aid){
                //积分分销补发
                $commission_record_list = Db::name('member_commission_record')->alias('r')
                    ->leftJoin('scoreshop_order o', "r.orderid = o.id and r.type = 'scoreshop'")
                    ->leftJoin('scoreshop_order_goods og', "r.ogid = og.id")
                    ->where('r.aid',$aid)->where('r.status',0)->where('o.status',3)
                    ->where('og.iscommission',0)
                    ->field('r.*,o.ordernum,o.title,og.iscommission')
                    ->select()->toArray();
                if($commission_record_list){
                    //佣金
                    foreach($commission_record_list as $commission_record){
                        Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                        Db::name('scoreshop_order_goods')->where('id',$commission_record['ogid'])->update(['iscommission'=>1]);
                        if($commission_record['commission'] > 0){
                            \app\common\Member::addcommission($aid,$commission_record['mid'],$commission_record['frommid'],$commission_record['commission'],$commission_record['remark']);
                        }
                        if($commission_record['score'] > 0){
                            \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                        }
                    }
                }
            }
        }
        //预约自动确认
        $this->yuyueAutoConfirm();

		//等级到期自动降级
        $this->autoDownLevel();

		//延时结算分销佣金
		$syssetlist = Db::name('admin_set')->where('fxjiesuantime_delaydays','<>',0)->select()->toArray();
		foreach($syssetlist as $sysset){
			\app\common\Order::jiesuanCommission($sysset['aid'],$sysset);
			// 店铺补贴
            if(getcustom('yx_shop_order_team_yeji_bonus')){
				\app\common\Order::jiesuanShopBonus($sysset['aid'],$sysset);

			}
		}
        //未延时未结算的补充结算
        $syssetlist = Db::name('admin_set')->where('fxjiesuantime_delaydays','=',0)->select()->toArray();
        foreach($syssetlist as $sysset){
            \app\common\Order::jiesuanCommission($sysset['aid'],$sysset);
            // 店铺补贴
            if(getcustom('yx_shop_order_team_yeji_bonus')){
				\app\common\Order::jiesuanShopBonus($sysset['aid'],$sysset);
			}
        }

		if(getcustom('xixie')){
            \app\custom\Xixie::auto_endorder();
        }
        
		$this->fenhong('perhour');

        if(getcustom('restaurant')){
            \app\custom\Restaurant::auto_perhour();
        }

        //可接收消息时段
        if(date('H') >= 8 && date('H') <= 20){

        }
        if(getcustom('fenhong_jiaquan_bylevel')){
            \app\common\Fenhong::JiesuanJiaquanFenhongByDay();
        }
        //过期商家
        \app\common\Business::update_expire_status();

        //积分过期
        \app\common\Member::scoreExpire();
        if(getcustom('yx_team_yeji')){
            $this->teamyejiJiangli();
        }
		//周期优惠券发放
		if(getcustom('member_levelup_givecoupon')){
			$setlist = Db::name('admin_set')->select()->toArray();
			foreach($setlist as $set){
				$aid = $set['aid'];
				//周期优惠券发放
				$list = Db::name('member_give_coupon_log')->where('aid',$aid)->where('status',0)->where('beginzstime','<=',time())->select()->toArray();
				foreach($list as $l){
					$days=0;
					if($l['cycle_type']==2) $days=7;
					if($l['cycle_type']==3) $days=30;
					for($i=1;$i<=$l['coupon_num'];$i++){
						\app\common\Coupon::send($aid,$l['mid'],$l['couponid'],false,0,$days);
					}
					Db::name('member_give_coupon_log')->where('id',$l['id'])->where('status',0)->update(['status'=>1,'zstime'=>time()]);
				}
			}
		}

        if(getcustom('yx_queue_free_multi_team')){
            $this->queueMultiTeamOut();
        }
        if(getcustom('yx_buy_fenhong')){
            if(date('H') == 1){
                \app\custom\BuyFenhong::sendBuyFenhong();
            }
        }
        if(getcustom('yx_team_yeji_weight')){
            //团队业绩加权分红
            $this->teamyejiWeight();
        }
        if(getcustom('mobile_business_jinjian')){
            $this->saveJinjianStatus();
        }

        //买单 返还那些待支付的抵扣金
        \app\common\Order::deal_decreturnAll();

        if(getcustom('extend_zhiyoubao_theater')){
            //自动同步演出信息
           \app\custom\Zhiyoubao::dealtongbu();
        }
		if(getcustom('member_recommend_apply_business')){
			//推荐商家奖励
			$this->recommendApplyBusiness();
		}
        if(getcustom('yx_team_yeji_activity')){
            $this->sendTeamYeajiJiangliActivity();
        }
        if(date('H') ==10){
            $this->couponExpiredSendSms();
        }
    }
	//每天执行
	private function perday(){
		$this->fenhong('perday');

		if(getcustom('score_withdraw')){
            $this->scoreToWithdraw();
        }

		$this->depositOrderExpire();
		
		if(getcustom('fxjiesuantime_perweek')){
			//每周结算佣金
			$syssetlist = Db::name('admin_set')->where('fxjiesuantime_delaydays','<>',0)->select()->toArray();
			foreach($syssetlist as $sysset){
				\app\common\Order::jiesuanCommissionWeek($sysset['aid'],$sysset);
			}
		}

		if(getcustom('forcerebuy') && date('d') == '01'){
			$forcerebuyList = Db::name('forcerebuy')->where('type',1)->where('status',1)->select()->toArray();
			foreach($forcerebuyList as $forcerebuy){
				$aid = $forcerebuy['aid'];
				if($forcerebuy['daytype'] == 0){
					$starttime = strtotime(date('Y-m-01',strtotime(date('Y-m-01')) - 86400));
					$endtime = strtotime(date('Y-m-01')) - 1;
				}elseif($forcerebuy['daytype'] == 1){
					if(date('m') != '01' && date('m') != '04' && date('m') != '07' && date('m') != '10') continue;
					if(date('m') == '01'){
						$starttime = strtotime((date('Y')-1).'-09-01');
						$endtime = strtotime(date('Y-01-01')) - 1;
					}
					if(date('m') == '04'){
						$starttime = strtotime(date('Y').'-01-01');
						$endtime = strtotime(date('Y-04-01')) - 1;
					}
					if(date('m') == '07'){
						$starttime = strtotime(date('Y').'-04-01');
						$endtime = strtotime(date('Y-07-01')) - 1;
					}
					if(date('m') == '10'){
						$starttime = strtotime(date('Y').'-07-01');
						$endtime = strtotime(date('Y-10-01')) - 1;
					}
				}elseif($forcerebuy['daytype'] == 2){
					if(date('m') != '01') continue;
					$starttime = strtotime((date('Y')-1).'-01-01');
					$endtime = strtotime(date('Y-01-01')) - 1;
				}
				//本周期复购是否达标
				$mwhere = [];
				$mwhere[] = ['aid','=',$aid];
				if($forcerebuy['wfgtype'] == 0){
					$mwhere[] = ['commission_isfreeze','=',0];
				}
				$gettj = explode(',',$forcerebuy['gettj']);
				if(!in_array('-1',$gettj)){
					$mwhere[] = ['levelid','in',$gettj];
				}
				$memberList = Db::name('member')->where($mwhere)->select()->toArray();
				foreach($memberList as $member){
					$mid = $member['id'];
					$orderwhere = [];
					$orderwhere[] = ['aid','=',$aid];
					$orderwhere[] = ['mid','=',$mid];
					$orderwhere[] = ['createtime','>=',$starttime];
					$orderwhere[] = ['createtime','<=',$endtime];
					$orderwhere[] = ['status','in','1,2,3'];
					if($forcerebuy['fwtype'] == 1){
						$orderwhere[] = ['cid','in',$forcerebuy['categoryids']];
					}elseif($forcerebuy['fwtype'] == 2){
						$orderwhere[] = ['proid','in',$forcerebuy['productids']];
					}
					$totalprice = Db::name('shop_order_goods')->where($orderwhere)->sum('totalprice');
					if($totalprice < $forcerebuy['price']){
						if($forcerebuy['wfgtype'] == 0){
							Db::name('member')->where('id', $mid)->update(['commission_isfreeze' => 1]);
						}else{
							Db::name('member')->where('id', $mid)->update(['levelid' => $forcerebuy['wfglvid'], 'levelendtime' => 0]);
						}
					}
				}
			}
		}

        if(getcustom('coupon_expire_notice')){
            \app\common\Coupon::auto_expire_notice();
        }
        if(getcustom('region_partner')){
            //每天发放区域分红
            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset){
                \app\common\Fenhong::regionPartnerBonus($sysset['aid'],$sysset);
            }
        }
        if(getcustom('coupon_xianxia_buy')){
            $this->xianixaCouponYeji(); 
        }
        //平台加权奖励【每月1号发放】
        if(getcustom('commission_platform_avg_bonus')){
            $this->platformAvgBonus();
        }
        //薪资奖励【每月1号发放】
        if(getcustom('member_level_salary_bonus')){
            $this->levelSalaryBonus();
        }
        //每日清除临时文件
        $dirTemp = ROOT_PATH . 'upload/temp';
        \app\common\File::clear_dir($dirTemp);
        if(getcustom('team_minyeji_count')){
            $this->teamyeji_count();
        }

		if(getcustom('hotel')){
			$this->addroomdays();
		}
        if(getcustom('yx_score_freeze')){
            $this->releaseScoreFreeze();
        }
        if(getcustom('yx_cashback_addup_return')){
            $this->cashbackEverydayRelease();
        }
        if(getcustom('business_expert')){
            //处理商户达人发奖及到期问题
            $this->dealBusinessExpert();
        }

        if(getcustom('supply_yongsheng')){
            //永盛计划任务
            \app\custom\SupplyYongsheng::uptongbu();
        }

        if(getcustom('yx_network_help')){
            $this->butieToCommission();
        }
        if(getcustom('yx_new_score_speed_pack')){
            Db::name('newscore_speed_pack_member')->where('status','in',[0,1])->where('endtime','<',time())->update(['status'=>2]);
        }
        if(getcustom('yx_digital_consum')){
            $this->release_digital();
        }
        if(getcustom('yx_cashback_decay')){
            $this->dailyDecayCashback();
        }
        if(getcustom('yx_farm')){
            $this->dayFarmBonus();
        }
        $this->memberExpiredSendSms();
	}
	//普通客户被设置成vip后，当天必须下单，不下单，第二天自动变成普通用户，下单后，后面15天没订单也变成普通用户
    public function member_vip_edit(){
        if(getcustom('member_vip_edit')) {
            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
            foreach ($syssetlist as $key => $sysset) {
                //查找刚 昨天 从普通会员升级为会员的 
                $defaultlevel = Db::name('member_level')->where('aid', $sysset['aid'])->where('isdefault', 1)->find();
                $zt_start_time = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day')));
                $zt_end_time = strtotime(date('Y-m-d 23:59:59', strtotime('-1 day')));
                if ($sysset['member_no_order_expire_status']) {
                    $uplvlist = Db::name('member_levelup_order')->alias('ml')
                        ->join('member m', 'm.id = ml.mid')
                        ->where('ml.beforelevelid', $defaultlevel['id'])
                        ->where('ml.status', 2)
                        ->where('ml.levelup_time', 'between', [$zt_start_time, $zt_end_time])
                        ->where('m.levelid', '<>', $defaultlevel['id'])
                        ->where('ml.aid', $sysset['aid'])
                        ->select()->toArray();
                    foreach ($uplvlist as $key => $val) {
                        $count = Db::name('shop_order')->where('aid', $sysset['aid'])->where('mid', $val['mid'])->where('status', 'in', [1, 2, 3])
                            ->where('paytime', 'between', [$zt_start_time, $zt_end_time])->count();
                        //数量是0的 回退到普通会员
                        if ($count <= 0) {
                            Db::name('member')->where('aid', $sysset['aid'])->where('id', $val['mid'])->update(['levelid' => $defaultlevel['id'], 'levelendtime' => 0]);
                        }
                    }
                }

                //查找  15天以前升级为会员的普通会员 就是 第16天（今天执行不算 算昨天的第15天）
                $days = $sysset['member_vip_no_order_days'];
                $days_15 = $days + 1;
                $zt_start_time_15 = strtotime(date('Y-m-d 00:00:00', strtotime('-' . $days_15 . ' day')));//（昨天的）15天前的一天  24号执行 23号算16天 就是8号
                $zt_end_time_15 = strtotime(date('Y-m-d 23:59:59', strtotime('-' . $days_15 . ' day')));  //（昨天的）15天前的一天
                $uplvlist_15 = Db::name('member_levelup_order')->alias('ml')
                    ->join('member m', 'm.id = ml.mid')
                    ->where('ml.beforelevelid', $defaultlevel['id'])
                    ->where('ml.status', 2)
                    ->where('ml.levelup_time', 'between', [$zt_start_time_15, $zt_end_time_15])
                    ->where('m.levelid', '<>', $defaultlevel['id'])
                    ->field('ml.id,ml.mid,ml.status,ml.levelup_time,ml.levelid')
                    ->where('ml.aid', $sysset['aid'])
                    ->select()->toArray();

                $_after_15 = strtotime(date('Y-m-d 00:00:00', strtotime('-' . $days . ' day')));//订单是 后面15天 24号执行 23号算15天 就是9号 到昨天结束的订单数量
                foreach ($uplvlist_15 as $key => $val15) {
                    $count = Db::name('shop_order')->where('aid', $sysset['aid'])->where('mid', $val15['mid'])->where('status', 'in', [1, 2, 3])
                        ->where('paytime', 'between', [$_after_15, $zt_end_time])->count();
                    //数量是0的 回退到普通会员
                    if ($count <= 0) {
                        Db::name('member')->where('aid', $sysset['aid'])->where('id', $val15['mid'])->update(['levelid' => $defaultlevel['id'], 'levelendtime' => 0]);
                    }
                }
            }
        }
    }

	//分红
	private function fenhong($pertime){
		$midCommissionList = [];
		$syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
		foreach($syssetlist as $sysset){
			$aid = $sysset['aid'];
			$fhjiesuantype = $sysset['fhjiesuantype'];
            $fhjiesuantime_type = $sysset['fhjiesuantime_type'];//分红结算时间类型 0收货后，1付款后
			$fhjiesuantime = $sysset['fhjiesuantime'];
			if($fhjiesuantime_type == 1) {
                if($pertime == 'perday') continue;
                if($pertime == 'perhour') continue;
                $starttime = 1;
                $endtime = time();
            } else {
				if($fhjiesuantime == 10) continue;  //手动结算
                if($fhjiesuantime == 0){ //按天结算
                    if($pertime == 'perhour') continue;
                    if($pertime == 'perminute') continue;
                    //$starttime = strtotime(date('Y-m-d'))-86400;
					$starttime = 1;
                    $endtime = strtotime(date('Y-m-d'));
                }elseif($fhjiesuantime == 1){ //月初结算
                    if($pertime == 'perhour') continue;
                    if($pertime == 'perminute') continue;
                    //$starttime = strtotime(date('Y-m-01').' -1 month');
                    if(date('d')!=1 && date('d')!='01'){
                        continue;
                    }
					$starttime = 1;
                    $endtime = strtotime(date('Y-m-01'));
                }elseif($fhjiesuantime == 2){ //按小时结算
                    if($pertime == 'perday') continue;
                    if($pertime == 'perminute') continue;
                    $starttime = 1;
                    $endtime = time();
                }elseif($fhjiesuantime == 3){ //每分钟结算
                    if($pertime == 'perday') continue;
                    if($pertime == 'perhour') continue;
                    $starttime = 1;
                    $endtime = time();
                }elseif($fhjiesuantime == 4){ //月底结算
                    if($pertime == 'perhour') continue;
                    if($pertime == 'perminute') continue;
                    if(date("t") != date("j")) continue;
                    $starttime = 1;
                    $endtime = time();
                }elseif($fhjiesuantime == 5){ //年底结算
                    if($pertime == 'perhour') continue;
                    if($pertime == 'perminute') continue;
                    if(date("t") != date("j") || date("m")!=12) continue;
                    $starttime = 1;
                    $endtime = time();
                }elseif($fhjiesuantime == 7){ //周一凌晨1点结算
                    if($pertime == 'perminute') continue;
                    if($pertime == 'perday') continue;
                    if(date("w") != 1) continue;
                    $starttime = 1;
                    //本周
                    $thisweek_start = mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y"));
                    $endtime = $thisweek_start;
                }
            }
//			\app\common\Fenhong::jiesuan($aid,$starttime,$endtime);
            $is_send = 1;
			if(getcustom('fenhong_fafang_type')){
			    if($sysset['fenhong_fafang_type'] ==1){ //1审核发放
                    $is_send = 0;
                }
            }
			if($is_send){
                \app\common\Fenhong::send($aid,$starttime,$endtime);
            }
            if(getcustom('car_hailing')){
                \app\common\CarhailingFenhong::jiesuan($aid,$starttime,$endtime);
            }
		}
		if(getcustom('fenhong_gudong_huiben')){
            foreach($syssetlist as $sysset){
                $aid = $sysset['aid'];
                $fhjiesuantime_type = $sysset['fhjiesuantime_type_huiben'];//分红结算时间类型 0收货后，1付款后
                $fhjiesuantime = $sysset['fhjiesuantime_huiben'];
                if($fhjiesuantime_type == 1) {
                    if($pertime == 'perday') continue;
                    if($pertime == 'perhour') continue;
                    $starttime = 1;
                    $endtime = time();
                } else {
                    if($fhjiesuantime == 10) continue;  //手动结算
                    if($fhjiesuantime == 0){ //按天结算
                        if($pertime == 'perhour') continue;
                        if($pertime == 'perminute') continue;
                        //$starttime = strtotime(date('Y-m-d'))-86400;
                        $starttime = 1;
                        $endtime = strtotime(date('Y-m-d'));
                    }elseif($fhjiesuantime == 1){ //月初结算
                        if($pertime == 'perhour') continue;
                        if($pertime == 'perminute') continue;
                        //$starttime = strtotime(date('Y-m-01').' -1 month');
                        if(date('d')!=1 && date('d')!='01'){
                            continue;
                        }
                        $starttime = 1;
                        $endtime = strtotime(date('Y-m-01'));
                    }elseif($fhjiesuantime == 2){ //按小时结算
                        if($pertime == 'perday') continue;
                        if($pertime == 'perminute') continue;
                        $starttime = 1;
                        $endtime = time();
                    }elseif($fhjiesuantime == 3){ //每分钟结算
                        if($pertime == 'perday') continue;
                        if($pertime == 'perhour') continue;
                        $starttime = 1;
                        $endtime = time();
                    }elseif($fhjiesuantime == 4){ //月底结算
                        if($pertime == 'perhour') continue;
                        if($pertime == 'perminute') continue;
                        if(date("t") != date("j")) continue;
                        $starttime = 1;
                        $endtime = time();
                    }elseif($fhjiesuantime == 5){ //年底结算
                        if($pertime == 'perhour') continue;
                        if($pertime == 'perminute') continue;
                        if(date("t") != date("j") || date("m")!=12) continue;
                        $starttime = 1;
                        $endtime = time();
                    }elseif($fhjiesuantime == 7){ //周一凌晨1点结算
                        if($pertime == 'perminute') continue;
                        if($pertime == 'perday') continue;
                        if(date("w") != 1) continue;
                        $starttime = 1;
                        //本周
                        $thisweek_start = mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y"));
                        $endtime = $thisweek_start;
                    }
                }
                \app\common\Fenhong::jiesuan_gdfenhong_huiben($aid,$starttime,$endtime);
            }
        }
        if(getcustom('gdfenhong_jiesuantype')){
            //股东分红独立结算
            foreach($syssetlist as $sysset){
                if($sysset['gdfenhong_jiesuantype']!=1){
                    continue;
                }
                $aid = $sysset['aid'];
                $fhjiesuantime_type = $sysset['gdfhjiesuantime_type'];//分红结算时间类型 0收货后，1付款后
                $fhjiesuantime = $sysset['gd_fhjiesuantime'];
                if($fhjiesuantime_type == 1) {
                    if($pertime == 'perday') continue;
                    if($pertime == 'perhour') continue;
                    $starttime = 1;
                    $endtime = time();
                } else {
                    if($fhjiesuantime == 10) continue;  //手动结算
                    if($fhjiesuantime == 0){ //按天结算
                        if($pertime == 'perhour') continue;
                        if($pertime == 'perminute') continue;
                        //$starttime = strtotime(date('Y-m-d'))-86400;
                        $starttime = 1;
                        $endtime = strtotime(date('Y-m-d'));
                    }elseif($fhjiesuantime == 1){ //月初结算
                        if($pertime == 'perhour') continue;
                        if($pertime == 'perminute') continue;
                        //$starttime = strtotime(date('Y-m-01').' -1 month');
                        if(date('d')!=1 && date('d')!='01'){
                            continue;
                        }
                        $starttime = 1;
                        $endtime = strtotime(date('Y-m-01'));
                    }elseif($fhjiesuantime == 2){ //按小时结算
                        if($pertime == 'perday') continue;
                        if($pertime == 'perminute') continue;
                        $starttime = 1;
                        $endtime = time();
                    }elseif($fhjiesuantime == 3){ //每分钟结算
                        if($pertime == 'perday') continue;
                        if($pertime == 'perhour') continue;
                        $starttime = 1;
                        $endtime = time();
                    }elseif($fhjiesuantime == 4){ //月底结算
                        if($pertime == 'perhour') continue;
                        if($pertime == 'perminute') continue;
                        if(date("t") != date("j")) continue;
                        $starttime = 1;
                        $endtime = time();
                    }elseif($fhjiesuantime == 5){ //年底结算
                        if($pertime == 'perhour') continue;
                        if($pertime == 'perminute') continue;
                        if(date("t") != date("j") || date("m")!=12) continue;
                        $starttime = 1;
                        $endtime = time();
                    }elseif($fhjiesuantime == 7){ //周一凌晨1点结算
                        if($pertime == 'perminute') continue;
                        if($pertime == 'perday') continue;
                        if(date("w") != 1) continue;
                        $starttime = 1;
                        //本周
                        $thisweek_start = mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y"));
                        $endtime = $thisweek_start;
                    }
                }
                \app\common\Fenhong::jiesuan_gdfenhong($aid,$starttime,$endtime);
            }
        }
        if(getcustom('yx_team_yeji_fenhong')){
            //团队业绩阶梯分红独立处理
            self::jieti_fenhong($pertime);
        }
        if(getcustom('area_fenhong_time')){
            foreach($syssetlist as $sysset){
                $aid = $sysset['aid'];
                $fhjiesuantype = $sysset['fhjiesuantype'];
                $fhjiesuantime_type = $sysset['fhjiesuantime_type_area'];//分红结算时间类型 0收货后，1付款后
                $fhjiesuantime = $sysset['fhjiesuantime_area'];
                if($fhjiesuantime_type == 1) {
                    if($pertime == 'perday') continue;
                    if($pertime == 'perhour') continue;
                    $starttime = 1;
                    $endtime = time();
                } else {
                    if($fhjiesuantime == 10) continue;  //手动结算
                    if($fhjiesuantime == 0){ //按天结算
                        if($pertime == 'perhour') continue;
                        if($pertime == 'perminute') continue;
                        //$starttime = strtotime(date('Y-m-d'))-86400;
                        $starttime = 1;
                        $endtime = strtotime(date('Y-m-d'));
                    }elseif($fhjiesuantime == 1){ //月初结算
                        if($pertime == 'perhour') continue;
                        if($pertime == 'perminute') continue;
                        //$starttime = strtotime(date('Y-m-01').' -1 month');
                        if($sysset['fhjiesuantime_area_monthday']>0){
                            if(date('d')!=$sysset['fhjiesuantime_area_monthday'] && date('d')!='0'.$sysset['fhjiesuantime_area_monthday']){
                                continue;
                            }
                        }else{
                            if(date('d')!=1 && date('d')!='01'){
                                continue;
                            }
                        }
                        $starttime = 1;
                        $endtime = strtotime(date('Y-m-01'));
                    }elseif($fhjiesuantime == 2){ //按小时结算
                        if($pertime == 'perday') continue;
                        if($pertime == 'perminute') continue;
                        $starttime = 1;
                        $endtime = time();
                    }elseif($fhjiesuantime == 3){ //每分钟结算
                        if($pertime == 'perday') continue;
                        if($pertime == 'perhour') continue;
                        $starttime = 1;
                        $endtime = time();
                    }elseif($fhjiesuantime == 4){ //月底结算
                        if($pertime == 'perhour') continue;
                        if($pertime == 'perminute') continue;
                        if(date("t") != date("j")) continue;
                        $starttime = 1;
                        $endtime = time();
                    }elseif($fhjiesuantime == 5){ //年底结算
                        if($pertime == 'perhour') continue;
                        if($pertime == 'perminute') continue;
                        if(date("t") != date("j") || date("m")!=12) continue;
                        $starttime = 1;
                        $endtime = time();
                    }elseif($fhjiesuantime == 7){ //周一凌晨1点结算
                        if($pertime == 'perminute') continue;
                        if($pertime == 'perday') continue;
                        if(date("w") != 1) continue;
                        $starttime = 1;
                        //本周
                        $thisweek_start = mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y"));
                        $endtime = $thisweek_start;
                    }
                }
                $is_send = 1;
                if(getcustom('fenhong_fafang_type')){
                    if($sysset['fenhong_fafang_type'] ==1){ //1审核发放
                        $is_send = 0;
                    }
                }
                if($is_send){
                    \app\common\Fenhong::send($aid,$starttime,$endtime,[],'areafenhong');
                }
            }
        }

	}
    //分红
    private function jieti_fenhong($pertime){
        if(getcustom('yx_team_yeji_fenhong')){
            //阶梯分红独立发放时间
            $yejisetlist = Db::name('team_yeji_fenhong_set')->where('status=1')->select()->toArray();
            foreach($yejisetlist as $yejiset){
                $aid = $yejiset['aid'];
                $yeji_fhjiesuantime = $yejiset['yejijiesuantime'];
                /*****按周期统计业绩 start******/
                if($yeji_fhjiesuantime == 0){ //按天结算
                    if($pertime == 'perhour') continue;
                    if($pertime == 'perminute') continue;
                    //$starttime = strtotime(date('Y-m-d'))-86400;
                    $starttime = 1;
                    $endtime = strtotime(date('Y-m-d'));
                }elseif($yeji_fhjiesuantime == 1){ //月初结算
                    if($pertime == 'perhour') continue;
                    if($pertime == 'perminute') continue;
                    //$starttime = strtotime(date('Y-m-01').' -1 month');
                    if(date('d')!=1 && date('d')!='01'){
                        continue;
                    }
                    $starttime = 1;
                    $endtime = strtotime(date('Y-m-01'));
                }elseif($yeji_fhjiesuantime == 2){ //按小时结算
                    if($pertime == 'perday') continue;
                    if($pertime == 'perminute') continue;
                    $starttime = 1;
                    $endtime = time();
                }elseif($yeji_fhjiesuantime == 3){ //每分钟结算
                    if($pertime == 'perday') continue;
                    if($pertime == 'perhour') continue;
                    $starttime = 1;
                    $endtime = time();
                }
                \app\common\Order::jietiYeji($aid,$starttime,$endtime);
                /*****按周期统计业绩 end******/
            }
            foreach($yejisetlist as $yejiset){
                $aid = $yejiset['aid'];
                $yeji_fhjiesuantime = $yejiset['yejijiesuantime'];
                /*****按周期发放分红 start******/
                $fenhong_fhjiesuantime = $yejiset['fhjiesuantime'];
                if($fenhong_fhjiesuantime == 0){ //按天结算
                    if($pertime == 'perhour') continue;
                    if($pertime == 'perminute') continue;
                    //$starttime = strtotime(date('Y-m-d'))-86400;
                    $send_starttime = 1;
                    $send_endtime = strtotime(date('Y-m-d'));
                }elseif($fenhong_fhjiesuantime == 1){ //月初结算
                    if($pertime == 'perhour') continue;
                    if($pertime == 'perminute') continue;
                    //$starttime = strtotime(date('Y-m-01').' -1 month');
                    if(date('d')!=1 && date('d')!='01'){
                        continue;
                    }
                    $send_starttime = 1;
                    $send_endtime = strtotime(date('Y-m-01'));
                }elseif($fenhong_fhjiesuantime == 2){ //按小时结算
                    if($pertime == 'perday') continue;
                    if($pertime == 'perminute') continue;
                    $send_starttime = 1;
                    $send_endtime = time();
                }elseif($fenhong_fhjiesuantime == 3){ //每分钟结算
                    if($pertime == 'perday') continue;
                    if($pertime == 'perhour') continue;
                    $send_starttime = 1;
                    $send_endtime = time();
                }

                \app\common\Fenhong::send($aid,$send_starttime,$send_endtime,[],'team_yeji_fenhong');
                /*****按周期发放分红 end******/
            }
        }
    }
	//分账 https://pay.weixin.qq.com/wiki/doc/api/allocation.php?chapter=27_1&index=1
	private function profitsharing($wxpaylog,$receivers,$addreceivers,$sub_mchid,$dbwxpayset,$transaction_id,$sub_appid,$times=0,$multi=false){

		$mchkey = $dbwxpayset['mchkey'];
		$sslcert = ROOT_PATH.str_replace(PRE_URL.'/','',$dbwxpayset['apiclient_cert']);
		$sslkey = ROOT_PATH.str_replace(PRE_URL.'/','',$dbwxpayset['apiclient_key']);

		$pars = array();
		$pars['mch_id'] = $dbwxpayset['mchid'];
		$pars['sub_mch_id'] = $sub_mchid;
		$pars['appid'] = $dbwxpayset['appid'];
		$pars['nonce_str'] = random(32);
		$pars['transaction_id'] = $transaction_id;
		$pars['out_order_no'] = 'P'.date('YmdHis').rand(1000,9999);
		$pars['receivers'] = jsonEncode($receivers);
		if($sub_appid){
			$pars['sub_appid'] = $sub_appid;
		}
		//$pars['sign_type'] = 'MD5';
		ksort($pars, SORT_STRING);
		$string1 = '';
		foreach ($pars as $k => $v) {
			$string1 .= "{$k}={$v}&";
		}
		$string1 .= "key=" . $mchkey;
		//$pars['sign'] = strtoupper(md5($string1));
		$pars['sign'] = strtoupper(hash_hmac("sha256",$string1 ,$mchkey));
		$xml = array2xml($pars);
        \think\facade\Log::write(__FILE__.__LINE__.__FUNCTION__);
		Log::write($pars);
		Log::write($xml);
		//Log::write($sslcert);

        $exist = Db::name('wxpay_fzlog')->where('transaction_id',$wxpaylog['transaction_id'])->where('receiversjson',$pars['receivers'])->find();
        if(!$exist){
            $insert = [
                'aid'=>$wxpaylog['aid'],
                'bid'=>$wxpaylog['bid'],
                'mid'=>$wxpaylog['mid'],
                'logid'=>$wxpaylog['id'],
                'openid'=>$wxpaylog['openid'],
                'tablename'=>$wxpaylog['tablename'],
                'ordernum'=>$wxpaylog['ordernum'],
                'mch_id'=>$wxpaylog['mch_id'],
                'sub_mchid'=>$wxpaylog['sub_mchid'],
                'transaction_id'=>$wxpaylog['transaction_id'],
                'out_order_no'=>$pars['out_order_no'],
                'receiversjson'=>$pars['receivers'],
                'createtime'=>time(),
                'fz_ordernum'=>$pars['out_order_no'],
                'platform'=>$wxpaylog['platform']
            ];
            $fzlogid = Db::name('wxpay_fzlog')->insertGetId($insert);
        }else{
            Db::name('wxpay_fzlog')->where('transaction_id',$wxpaylog['transaction_id'])->where('id',$exist['id'])
                ->update(['out_order_no'=>$pars['out_order_no'],'fz_ordernum'=>$pars['out_order_no'],'createtime'=>time()]);
            $fzlogid = $exist['id'];
        }

        //查询订单待分账金额
        $query = \app\common\Wxpay::fenzhangQuery($dbwxpayset['mchid'],$mchkey,$wxpaylog['transaction_id']);
        if($query['status'] == 1 && $query['resp']['unsplit_amount'] == 0){
            //待分金额为0，返回成功
            Db::name('wxpay_fzlog')->where('transaction_id',$wxpaylog['transaction_id'])->where('id',$fzlogid)
                ->update(['isfenzhang'=>1,'fz_errmsg'=>'']);
            return ['status'=>1,'msg'=>'分账成功','resp'=>$query['resp'],'ordernum'=>$pars['out_order_no']];
        }

		$ch = curl_init ();
        if($multi){
            curl_setopt ( $ch, CURLOPT_URL, "https://api.mch.weixin.qq.com/secapi/pay/multiprofitsharing" );
        }else{
            curl_setopt ( $ch, CURLOPT_URL, "https://api.mch.weixin.qq.com/secapi/pay/profitsharing" );
        }

		curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		curl_setopt ( $ch, CURLOPT_SSLCERT,$sslcert);
		curl_setopt ( $ch, CURLOPT_SSLKEY,$sslkey);
		curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt ( $ch, CURLOPT_AUTOREFERER, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $xml );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );

		$info = curl_exec ( $ch );
		curl_close ( $ch );
		//Log::write($info);
		$resp = (array)(simplexml_load_string($info,'SimpleXMLElement', LIBXML_NOCDATA));
		Log::write($resp);
		if($resp['return_code'] == 'SUCCESS' && $resp['result_code']=='SUCCESS'){
            Db::name('wxpay_fzlog')->where('transaction_id',$wxpaylog['transaction_id'])->where('id',$fzlogid)
                ->update(['isfenzhang'=>1,'fz_errmsg'=>'']);
			return ['status'=>1,'msg'=>'分账成功','resp'=>$resp,'ordernum'=>$pars['out_order_no']];
		}else{
			//Log::write('profitsharing');
			//Log::write($resp);
			if($times == 0 && ($resp['err_code'] == 'PARAM_ERROR' || $resp['err_code'] == 'RECEIVER_INVALID')){
			//if($times == 0 && $resp['err_code'] == 'RECEIVER_INVALID'){
				foreach($addreceivers as $addreceiver){
					$pars = array();
					$pars['mch_id'] = $dbwxpayset['mchid'];
					$pars['sub_mch_id'] = $sub_mchid;
					$pars['appid'] = $dbwxpayset['appid'];
					$pars['nonce_str'] = random(32);
					$pars['receiver'] = jsonEncode($addreceiver);
					if($sub_appid){
						$pars['sub_appid'] = $sub_appid;
					}
					//$pars['sign_type'] = 'MD5';
					ksort($pars, SORT_STRING);
					$string1 = '';
					foreach ($pars as $k => $v) {
						$string1 .= "{$k}={$v}&";
					}
					$string1 .= "key=" . $mchkey;
					//$pars['sign'] = strtoupper(md5($string1));
					$pars['sign'] = strtoupper(hash_hmac("sha256",$string1 ,$mchkey));
					$xml = array2xml($pars);
					$ch = curl_init ();
					curl_setopt ( $ch, CURLOPT_URL, "https://api.mch.weixin.qq.com/secapi/pay/profitsharingaddreceiver" );
					curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
					curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
					curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
					curl_setopt ( $ch, CURLOPT_SSLCERT,$sslcert);
					curl_setopt ( $ch, CURLOPT_SSLKEY,$sslkey);
					curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
					curl_setopt ( $ch, CURLOPT_AUTOREFERER, 1 );
					curl_setopt ( $ch, CURLOPT_POSTFIELDS, $xml );
					curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
					$info = curl_exec ( $ch );
					curl_close ( $ch );
					Log::write('profitsharingaddreceiver');
					Log::write($info);
					sleep(2);
				}
				return $this->profitsharing($wxpaylog,$receivers,$addreceivers,$sub_mchid,$dbwxpayset,$transaction_id,$sub_appid,1);
			}
			$msg = '未知错误';
			if ($resp['return_code'] == 'FAIL') {
				$msg = $resp['return_msg'];
			}
			if ($resp['result_code'] == 'FAIL') {
				$msg = $resp['err_code_des'];
			}
            Db::name('wxpay_fzlog')->where('transaction_id',$wxpaylog['transaction_id'])->where('id',$fzlogid)
                ->update(['isfenzhang'=>2,'fz_errmsg'=>$msg]);
			return ['status'=>0,'msg'=>$msg,'resp'=>$resp];
		}
	}

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 1、小于1积分时，按1积分允许释放。
     * 2、大于1积分时，对小数位四舍五入取整数。
     */
	public function scoreToWithdraw()
    {
        if(getcustom('score_withdraw')){
            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset){
                if($sysset['score_withdraw'] == 1 && $sysset['score_withdraw_percent_day'] > 0) {
//                $oneStand = (100 / $sysset['score_withdraw_percent_day']);
                    $oneStand = 0;
                    $mlist = Db::name('member')->where('aid', $sysset['aid'])->where('score', '>', $oneStand)->select()->toArray();
                    if($mlist) {
                        foreach ($mlist as $member) {
                            $score_withdraw = 0;
                            $score_withdraw = round($member['score'] * $sysset['score_withdraw_percent_day'] / 100);
                            if($score_withdraw < 1 && $member['score'] > 1) {
                                $score_withdraw = 1;
                            }
                            if($score_withdraw > 0) {
                                \app\common\Member::addscore($sysset['aid'],$member['id'], $score_withdraw * -1, '转为允提'.t('积分',$sysset['aid']));
                                \app\common\Member::addscore_withdraw($sysset['aid'],$member['id'], $score_withdraw, '转入允提'.t('积分',$sysset['aid']));
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 积分自动转余额
     * 1、小于1积分时，按1积分允许释放。
     * 2、大于1积分时，对小数位四舍五入取整数。
     */
    private function scoreToMoney()
    {
        $score_weishu = 0;
        $score_weishu_set = getcustom('score_weishu')?:0;
        if(getcustom('score_to_money_auto')){
            //判断今日是否已执行
            $exit = Db::name('score_tomoney_log')->where('w_day',date('Ymd'))->find();
            if($exit){
                return true;
            }
            //添加自动转余额记录，防止重复执行
            Db::name('score_tomoney_log')->insert(['w_day'=>date('Ymd')]);
            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset){
                if($score_weishu_set){
                    $score_weishu = $sysset['score_weishu']??0;
                }
                if($sysset['score_to_money_auto'] == 1 && $sysset['score_to_money_auto_day'] > 0) {
                    $mlist = Db::name('member')->where('aid', $sysset['aid'])->where('score', '>', 0)
                        ->where('score_to_money_auto',1)
                        ->select()->toArray();
                    if($mlist) {
                        foreach ($mlist as $member) {
                            $score_to_money_auto_day = $sysset['score_to_money_auto_day'];
                            if($member['score_to_money_auto_day']>0){
                                $score_to_money_auto_day = $member['score_to_money_auto_day'];
                            }
                            $score_num = bcmul($member['score'] , $score_to_money_auto_day / 100,$score_weishu);
                            if($score_num <= 0) {
                                continue;
                            }
                            $score_to_money_auto_percent = $sysset['score_to_money_auto_percent']?:1;
                            $money = bcmul($score_num,$score_to_money_auto_percent,2);
                            if($score_num > 0) {
                                \app\common\Member::addscore($sysset['aid'],$member['id'], $score_num * -1, t('积分',$sysset['aid']).'每日'.$score_to_money_auto_day.'%释放到'.t('余额',$sysset['aid']));
                                \app\common\Member::addmoney($sysset['aid'],$member['id'], $money, t('积分',$sysset['aid']).'每日'.$score_to_money_auto_day.'%释放到'.t('余额',$sysset['aid']));
                            }
                        }
                    }
                }
            }

        }
    }

    private function yuebao()
    {
      if(getcustom('plug_yuebao')){
        //余额宝
          //读取配置
          $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
          if($syssetlist){
              foreach($syssetlist as $sv){
                  //读取大于0的用户余额
                  $sel_member = Db::name('member')
                      ->where('aid',$sv['aid'])
                      ->where('money','<>',0)
                      ->field('id,money,yuebao_rate')
                      ->select()
                      ->toArray();
                  //如果余额宝开启、余额收益比例大于0、且用户存在
                  if($sv['open_yuebao'] ==1 && $sv['yuebao_rate'] >0 && $sel_member){

                      foreach($sel_member as $mv){
                          //查询是收益率是否单独设置
                          if($mv['yuebao_rate']>=0){
                              $yuebao_rate = $mv['yuebao_rate']/100;
                          }else{
                              $yuebao_rate = $sv['yuebao_rate']/100;
                          }

                          //计算用户收益
                          $m_money = $mv['money']*$yuebao_rate;
                          if($m_money!=0){
                              $m_money = round($m_money,3);
                              \app\common\Member::addyuebaomoney($sv['aid'],$mv['id'],$m_money,t('余额宝').'收益',1);
                          }
                      }
                  }
              }
          }
      }
    }

    //寄存订单过期
    private function depositOrderExpire()
    {
        $syssetlist = Db::name('restaurant_deposit_sysset')->where('1=1')->select()->toArray();
        if($syssetlist) {
            $time = time();
            foreach ($syssetlist as $set) {
                if($set['time'] > 0) {
                    Db::name('restaurant_deposit_order')->where('aid',$set['aid'])->where('bid',$set['bid'])
                        ->where('status',1)->where('createtime','<',$time-intval($set['time'])*86400)->update(['status' => 4]);
                }
            }
        }
    }

    public function product_img_baidu_sync()
    {
        if(getcustom('image_search')){
            $limit = 10;
            $aids = Db::name('admin')->where('image_search',1)->where('status',1)->column('id');
            if(empty($aids)) return;
            $syssetlist = Db::name('baidu_set')->whereIn('aid',$aids)->where('image_search',1)->where('baidu_apikey','<>','')->where('baidu_secretkey','<>','')->select()->toArray();
            foreach($syssetlist as $sysset) {
                $aid = $sysset['aid'];
                $bid = $sysset['bid'];
                $baidu = new \app\custom\Baidu($aid,$bid);
                $baidu->sync($limit);
            }
        }
    }

	public function fifaauto(){
		if(time() > 1671422400) return;

		$fifaauto_randnum = cache('fifaauto_randnum');
		if(!$fifaauto_randnum){
			$fifaauto_randnum = ''.rand(0,4);
			cache('fifaauto_randnum',$fifaauto_randnum);
		}

		if(!in_array(date('i'),['0'.$fifaauto_randnum,'1'.$fifaauto_randnum,'2'.$fifaauto_randnum,'3'.$fifaauto_randnum,'4'.$fifaauto_randnum,'5'.$fifaauto_randnum])) return;

		sleep(rand(0,20));
		
		\think\facade\Log::write('---fifaauto----'.date('Y-m-d H:i:s'));

		\app\custom\Fifa::initdata();

		$fifadata = Db::name('fifa')->where('matchStatus',2)->select()->toArray();
		foreach($fifadata as $fifa){
			$leftTeam_score = intval($fifa['leftTeam_score']);
			$rightTeam_score = intval($fifa['rightTeam_score']);
			$successguess2 = $leftTeam_score.':'.$rightTeam_score;
			if($leftTeam_score > $rightTeam_score){
				$successguess1 = '1';
				if($leftTeam_score > 5) $successguess2 = '胜其他';
			}elseif($leftTeam_score == $rightTeam_score){
				$successguess1 = '2';
				if($leftTeam_score > 3) $successguess2 = '平其他';
			}else{
				$successguess1 = '3';
				if($rightTeam_score > 5) $successguess2 = '其他';
			}
			$syssetlist = Db::name('fifa_set')->where('status',1)->select()->toArray();
            foreach($syssetlist as $sysset) {
				$aid = $sysset['aid'];
				$recordList = Db::name('fifa_record')->where('aid',$aid)->where('hid',$fifa['id'])->where('status',0)->select()->toArray();
				foreach($recordList as $record){
					$update = [];
					$update['status'] = 1;
					if($record['guess1'] && $record['guess1'] == $successguess1){
						$update['guess1st'] = 1;
						$update['givescore1'] = intval($sysset['givescore1']);
						
						$successnum = 1 + Db::name('fifa_record')->where('aid',$aid)->where('mid',$record['mid'])->whereRaw('guess1st=1')->count();
						$guess1set = json_decode($sysset['guess1set'],true);
						if($guess1set){
							foreach($guess1set as $k=>$v){
								if($v['score']!=='' && $v['score']!==null && $successnum == $v['times']){
									$update['givescore1'] += intval($v['score']);
								}
								//赠送优惠券
								if($v['coupon_id']!=='' && $v['coupon_id']!==null && $successnum == $v['times']){
									\app\common\Coupon::send($aid,$record['mid'],$v['coupon_id']);
								}
							}
						}
						\app\common\Member::addscore($aid,$record['mid'],$update['givescore1'],'世界杯竞猜成功奖励');

					}else{
						$update['guess1st'] = 2;
					}
					if($record['guess2'] && $record['guess2'] == $successguess2){
						$update['guess2st'] = 1;
						$update['givescore2'] = intval($sysset['givescore2']);
						
						$successnum = 1 + Db::name('fifa_record')->where('aid',$aid)->where('mid',$record['mid'])->whereRaw('guess2st=1')->count();
						$guess2set = json_decode($sysset['guess2set'],true);
						if($guess2set){
							foreach($guess2set as $k=>$v){
								if($v['score']!=='' && $v['score']!==null && $successnum == $v['times']){
									$update['givescore2'] += intval($v['score']);
								}
								//赠送优惠券
								if($v['coupon_id']!=='' && $v['coupon_id']!==null && $successnum == $v['times']){
									\app\common\Coupon::send($aid,$record['mid'],$v['coupon_id']);
								}
							}
						}
						\app\common\Member::addscore($aid,$record['mid'],$update['givescore2'],'世界杯竞猜成功奖励');
					}else{
						$update['guess2st'] = 2;
					}

					Db::name('fifa_record')->where('id',$record['id'])->update($update);
				}
			}
		}
	}

    //定时抽奖 1分钟执行一次
    public function run_dscj()
    {
        if (getcustom('choujiang_time')) {
            \app\model\Dscj::kaijiang();
        }
    }

    public function huidong()
    {
        \app\custom\HuiDong::syncMember();
    }

    /**
     * 自动降级
     */
    private function autoDownLevel(){

        //会员等级到期
//        $check_time = time()+35*86400;
        $check_time = time();
        $memberlist = Db::name('member')->where("levelendtime>0 and levelendtime<".$check_time)->select()->toArray();
        //dump($memberlist);
        foreach($memberlist as $member){

            if(getcustom('level_auto_down')){
                //检测推荐人和团队业绩
                $level_data = Db::name('member_level')->where('id',$member['levelid'])->find();
                if($level_data['check_type']>0){
                    $check_result = \app\common\Member::checkDownLevelCon($member,$level_data);
                    //dump($check_result);
                    if(!$check_result['is_down']){
                        //考核通过，更新有效期
                        $data_u = [];
                        if($level_data['check_type']==1){
                            $data_u['levelstarttime'] = $member['levelendtime'];
                            if($level_data['yxqdate']>0){
                                $data_u['levelendtime'] = $member['levelendtime']+86400 * $level_data['yxqdate'];
                            }else{
                                $data_u['levelendtime'] = 0;
                            }
                        }else{
                            $data_u['levelendtime'] = 0;
                        }
                        Db::name('member')->where('id', $member['id'])->update($data_u);
                        continue;
                    }
                }
            }
            $is_default = 1;
            $defaultlevel = Db::name('member_level')->where('aid', $member['aid'])->where('isdefault', 1)->find();
            if(getcustom('next_level_set') || getcustom('level_auto_down')){
                //是不是有设置的下个等级
                $curlevel = Db::name('member_level')->where('aid',$member['aid'])->where('id',$member['levelid'])->find();
                if ($curlevel && $curlevel['next_level_id'] > 0 && $curlevel['next_level_id']!=$defaultlevel['id']) {
                    $nextlevel = Db::name('member_level')->where('id',$curlevel['next_level_id'])->find();
                    if($nextlevel){
                        $is_default = 0;
                        $newlv['levelid'] = $nextlevel['id'];
                        $newlv['levelendtime'] = strtotime(date('Y-m-d')) + 86400 + 86400 * $nextlevel['yxqdate'];
                        Db::name('member')->where('id', $member['id'])->update($newlv);
                    }
                }
            }

            if($is_default==1) {
                $newlv['levelid'] = $defaultlevel['id'];
                Db::name('member')->where('id', $member['id'])->update(['levelid' => $defaultlevel['id'], 'levelendtime' => 0]);
            }
            //if(getcustom('level_auto_down')) {
                //降级记录
                $order = [
                    'aid' => $member['aid'],
                    'mid' => $member['id'],
                    'from_mid' => $member['id'],
                    'pid'=>$member['pid'],
                    'levelid' => $newlv['levelid'],
                    'title' => '自动降级',
                    'totalprice' => 0,
                    'createtime' => time(),
                    'levelup_time' => time(),
                    'beforelevelid' => $member['levelid'],
                    'form0' => '类型^_^' . $check_result['desc']??'自动降级',
                    'platform' => '',
                    'status' => 2,
                    'type' => 1
                ];
                Db::name('member_levelup_order')->insert($order);
                //Db::name('member_leveldown_record')->insert($order);
            //}
        }

        //其他分组等级到期后失效
        Db::name('member_level_record')->where("levelendtime>0 and levelendtime<".time())->delete();

    }
    
    //智能开门设备过期和 10分钟提醒
    private function cerberuseExpire(){
        if (getcustom('lot_cerberuse')){
            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset) {
                $cerberuse_set = Db::name('cerberuse_set')->where('aid',$sysset['aid'])->find();
                //已支付的过期关闭
                Db::name('cerberuse_order')->where('aid',$sysset['aid'])->where('endtime','<=',time())->where('status','in',[1,2])->update(['status'=>3]);
                
                //未支付的 过期关闭
                $autoclose =  $cerberuse_set['autoclose']?$cerberuse_set['autoclose']:10;
                $expirse_time = time()- $autoclose * 60;
                Db::name('cerberuse_order')->where('aid',$sysset['aid'])->where('createtime','<=',$expirse_time)->where('status',0)->update(['status' => 4]);
                
                //查询距离结束还有10分钟结束的 进行提醒
                $minute = $cerberuse_set['remind_minute'];
                $remind_minute = $minute?$minute:10;
                $entime = time() + $remind_minute*60;
                $list = Db::name('cerberuse_order')->where('aid',$sysset['aid'])->where('endtime','<=',$entime)->where('status',2)->where('is_notice',0)->select()->toArray();
                if(!$list){
                    continue;
                }
                foreach($list as $key=>$val){
                    $cerberuse = Db::name('cerberuse')->where('aid',$val['aid'])->where('id',$val['proid'])->find();
                    $content =[
                        'method' => 'playTts',
                        'content' => '温馨提醒！您消费的时间还有'.$remind_minute.'分钟！即将到期！',
                        'vol' => 4
                    ];
                    $content_json = json_encode($content,JSON_UNESCAPED_UNICODE);
                    $topic= $val['aid'].'/'.$cerberuse['imei'];
                    $mqtt = new \app\custom\Mqtt();
                    $mqtt -> publish($topic,$content_json);
                    Db::name('cerberuse_order')->where('id',$val['id'])->update(['is_notice' => 1 ]);
                    //短信通知
                    $member = Db::name('member')->where('id',$val['mid'])->find();
                    if($member['tel']){
                        $tel = $member['tel'];
                    }else{
                        $tel = $val['tel'];
                    }
                    $rs = \app\common\Sms::send($val['aid'],$tel,'tmpl_use_expire',[]);
                    //模板小时到期通知
                    $wx_tmplset = Db::name('wx_tmplset')->where('aid',aid)->field('tmpl_use_expire')->find();
                    if($wx_tmplset && $wx_tmplset['tmpl_use_expire']){
                        $tmplcontent = [];
                        $tmplcontent['thing1']  = $cerberuse['title'];
                        $tmplcontent['time2'] = date('Y-m-d H:i',$val['endtime']);
                        $tmplcontent['thing3'] = '您消费的时间即将到期';
                        \app\common\Wechat::sendwxtmpl($val['aid'],$val['mid'],'tmpl_use_expire',$tmplcontent,m_url('pagesZ/cerberuse/index'),0);
                    }
                    
                }
                
            }
        }
    }

    //统计商户销量 business/sales页面中直接调用
    public function countSales(){
        ini_set('memory_limit','1024M');
        set_time_limit(0);
        $sales_type = [
            'sales' => 'sales',
            'shop' => 'shop_sales',
            'collage' => 'collage_sales',
            'kanjia' => 'kanjia_sales',
            'seckill' => 'seckill_sales',
            'tuangou' => 'tuangou_sales',
            'scoreshop' => 'scoreshop_sales',
            'lucky_collage' => 'lucky_collage_sales',
            'yuyue' => 'yuyue_sales',//预约服务
            'kecheng' => 'kecheng_sales',//课程
            'cycle' => 'cycle_sales',//周期购
            'restaurant_takeaway' => 'restaurant_takeaway_sales',//餐饮外卖
            'restaurant_shop' => 'restaurant_shop_sales',//餐饮点餐
            'maidan' => 'maidan_sales'//买单
        ];
        Db::startTrans();
        Db::execute('truncate table ddwx_business_sales');
        foreach($sales_type as $type=>$sales_field){
            switch ($type){
                case 'shop':
                    $orders = Db::name('shop_order_goods')->whereIn('status',[1,2,3])->field('aid,bid,num')->select()->toArray();
                    $products = Db::name('shop_product')->where('1=1')->field('aid,bid,sales')->select()->toArray();
                    break;
                case 'collage':
                    $orders = Db::name('collage_order')->whereIn('status',[1,2,3])->field('aid,bid,num')->select()->toArray();
                    $products = Db::name('collage_product')->where('1=1')->field('aid,bid,sales')->select()->toArray();
                    break;
                case 'kanjia':
                    $orders = Db::name('kanjia_order')->whereIn('status',[1,2,3])->field('aid,bid,num')->select()->toArray();
                    $products = Db::name('kanjia_product')->where('1=1')->field('aid,bid,sales')->select()->toArray();
                    break;
                case 'seckill':
                    $orders = Db::name('seckill_order')->whereIn('status',[1,2,3])->field('aid,bid,num')->select()->toArray();
                    $products = Db::name('seckill_product')->where('1=1')->field('aid,bid,sales')->select()->toArray();
                    break;
                case 'tuangou':
                    $orders = Db::name('tuangou_order')->whereIn('status',[1,2,3])->field('aid,bid,num')->select()->toArray();
                    $field = 'aid,bid,sales';
                    if(getcustom('yx_tuangou_vrnum')){
                        $field = 'aid,bid,(sales+vrnum) sales';
                    }
                    $products = Db::name('tuangou_product')->where('1=1')->field($field)->select()->toArray();
                    break;
                case 'scoreshop':
                    $orders = Db::name('scoreshop_order_goods')->whereIn('status',[1,2,3])->field('aid,bid,num')->select()->toArray();
                    $products = Db::name('scoreshop_product')->where('1=1')->field('aid,bid,sales')->select()->toArray();
                    break;
                case 'lucky_collage':
                    $orders = Db::name('lucky_collage_order')->whereIn('status',[1,2,3])->field('aid,bid,num')->select()->toArray();
                    $products = Db::name('lucky_collage_product')->where('1=1')->field('aid,bid,sales')->select()->toArray();
                    break;
                case 'yuyue':
                    $orders = Db::name('yuyue_order')->whereIn('status',[1,2,3])->field('aid,bid,num')->select()->toArray();
                    $products = Db::name('yuyue_product')->where('1=1')->field('aid,bid,sales')->select()->toArray();
                    break;
                case 'kecheng':
                    $orders = Db::name('kecheng_order')->whereIn('status',[1,2,3])->field('aid,bid')->select()->toArray();
                    $products = Db::name('kecheng_list')->where('1=1')->field('aid,bid')->select()->toArray();
                    break;
                case 'cycle':
                    $orders = Db::name('cycle_order')->whereIn('status',[1,2,3])->field('aid,bid,num')->select()->toArray();
                    $products = Db::name('cycle_product')->where('1=1')->field('aid,bid,sales')->select()->toArray();
                    break;
                case 'restaurant_takeaway':
                    $orders = Db::name('restaurant_takeaway_order_goods')->whereIn('status',[1,2,3])->field('aid,bid,num')->select()->toArray();
                    $products = Db::name('restaurant_product')->where('1=1')->field('aid,bid,sales')->select()->toArray();
                    break;
                case 'restaurant_shop':
                    $orders = Db::name('restaurant_shop_order_goods')->whereIn('status',[1,2,3])->field('aid,bid,num')->select()->toArray();
                    break;
                case 'maidan':
                    $orders = Db::name('maidan_order')->whereIn('status',[1])->field('aid,bid,1 as num')->select()->toArray();
                    $products = [];
                    break;
            }


            //更新商户虚拟销量
            foreach($products as $product){
                if($product && empty($product['sales'])){
                    $product['sales'] = 0;
                }
                $aid = $product['aid']?:1;
                $bid = $product['bid'];
                $business = Db::name('business')->where('id',$bid)->find();
                if($bid && !$business){
                    continue;
                }
                $sale_num = $product['sales'];
                $business_sales = Db::name('business_sales')
                    ->where('aid',$aid)
                    ->where('bid',$bid)
                    ->find();

                if(!$business_sales && $sale_num>0){
                    $data_sales = [];
                    $data_sales['aid'] = $aid;
                    $data_sales['bid'] = $bid;
                    $data_sales['sales'] = $sale_num;
                    $data_sales['total_sales'] = $sale_num;
                    Db::name('business_sales')->insert($data_sales);
                }else{
                    $data_sales = [];
                    $data_sales['sales'] = $business_sales['sales']+$sale_num;
                    $data_sales['total_sales'] = $business_sales['total_sales']+$sale_num;
                    Db::name('business_sales')->where('id',$business_sales['id'])->update($data_sales);
                }
            }

            //更新商户订单销量
            foreach($orders as $order){
                if($order && empty($order['num'])){
                    $order['num'] = 1;
                }
                $aid = $order['aid']?:1;
                $bid = $order['bid'];
                $business = Db::name('business')->where('id',$bid)->find();
                if($bid && !$business){
                    continue;
                }
                $sale_num = $order['num'];
                $sales_field = $sales_type[$type];
                $business_sales = Db::name('business_sales')
                    ->where('aid',$aid)
                    ->where('bid',$bid)
                    ->find();

                if(!$business_sales && $sale_num>0){
                    $data_sales = [];
                    $data_sales['aid'] = $aid;
                    $data_sales['bid'] = $bid;
                    $data_sales[$sales_field] = $sale_num;
                    $data_sales['total_sales'] = $sale_num;
                    Db::name('business_sales')->insert($data_sales);
                }else{
                    $data_sales = [];
                    $data_sales[$sales_field] = $business_sales[$sales_field]+$sale_num;
                    $data_sales['total_sales'] = $business_sales['total_sales']+$sale_num;
                    Db::name('business_sales')->where('id',$business_sales['id'])->update($data_sales);
                }
            }
        }
        Db::commit();

        die('更新完成');

    }

    //奖金池
    private function bonusPoolDaily(){
        if(getcustom('product_bonus_pool')){
            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset) {
                $bonus_pool_status = Db::name('admin')->where('id',$sysset['aid'])->value('bonus_pool_status');
                if(!$bonus_pool_status){
                    continue;
                }
                $yesterday_start = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day')));
                $yesterday_end = strtotime(date('Y-m-d 23:59:59', strtotime('-1 day')));
                $order = Db::name('shop_order')->where('paytime','between',[$yesterday_start,$yesterday_end])->where('status','in',[1])->where('aid',$sysset['aid'])->find();
                $poolshopset = Db::name('shop_sysset')->where('aid',$sysset['aid'])->field('bonus_pool_money_max,bonus_pool_cx_days,bonus_pool_already,bonus_pool_noreleasetj')->find();
                
                //如果昨天没有业绩，进行释放
                if(!$order){
                    //连续释放次数 小于 设置的次数才能进行释放
                    
                    if($poolshopset['bonus_pool_already'] >= $poolshopset['bonus_pool_cx_days']){
                        continue;
                    }
                    //发放奖励
                    $release_list = Db::name('shop_order')->alias('so')
                        ->join('member m','m.id = so.mid')
                        ->where('so.status','in',[1,2,3])
                        ->where('so.aid',$sysset['aid'])
                        ->order('so.paytime asc')
                        ->field('so.id,so.aid,so.status,so.createtime,so.mid,m.bonus_pool_money,m.levelid')
                        ->group('so.mid')
                        ->select()->toArray(); 
                  
                    //释放奖金池
                    if($release_list){
                        foreach ($release_list as $mk=>$mv){
                            $pool = Db::name('bonus_pool')->where('aid',$mv['aid'])->where('status',0)->order('id asc')->find();
                            
                            if(!$pool){
                                continue;
                            }
                            //用户达到上限，不释放
//                            if($mv['bonus_pool_money']+$pool['money'] >= $poolshopset['bonus_pool_money_max']){
//                                \think\facade\Log::write($mv['mid'].'达到上限');
//                                continue;
//                            }
                            $bonus_pool_money = dd_money_format($mv['bonus_pool_money'] + $pool['money']);
                            //增加log
                            $log = [
                                'aid' =>$mv['aid'],
                                'mid' =>$mv['mid'],
                                'frommid' => 0,
                                'commission' => $pool['money'],
                                'after' => $bonus_pool_money,
                                'createtime' => time(),
                                'remark' => '奖金发放'
                            ];
                            Db::name('member_bonus_pool_log') ->insert($log);
                            //修改奖金池状态
                            Db::name('member')->where('id',$mv['mid'])->update(['bonus_pool_money' => $bonus_pool_money]);
                            Db::name('bonus_pool')->where('aid',$mv['aid'])->where('id',$pool['id'])->update(['status' => 1,'mid' => $mv['mid'],'endtime' => time()]);
                        }
                        //持续每天的次数增加1
                        Db::name('shop_sysset')->where('aid',$sysset['aid'])->inc('bonus_pool_already',1)->update();
                    }
                }else{
                    //有订单 持续每天 设置为0 从头开始
                    Db::name('shop_sysset')->where('aid',$sysset['aid'])->update(['bonus_pool_already' => 0]);
                }
            }
        }
       
    }
    //线下优惠券
    private function xianixaCouponYeji(){
        if(getcustom('coupon_xianxia_buy')){
            $month_last = date( "Y-m-t");
            $now_date = date('Y-m-d');
            //判断当前天 是不是当月最后一天 
            if($month_last !=$now_date){
                return;
            }
            $month_start = strtotime(date('Y-m-01 00:00:00'));
            $month_end=strtotime(date('Y-m-t 23:59:59')); 
            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset) {
                $levellist = Db::name('member_level')->where('aid',$sysset['aid'])->select()->toArray();
                
                foreach ($levellist as $level){
                    //如果不设置业绩，不进行操作
                    if(!$level['yeji_reward_data']){
                         continue;
                    }
                    $yeji_reward_data = json_decode($level['yeji_reward_data'],true);
                   
                    $memberlist = Db::name('member')->where('aid',$sysset['aid'])->where('levelid',$level['id'])->select()->toArray();
                    foreach($memberlist as $member){
                      $res = \app\common\Member::xianxiaYeji($sysset['aid'],$member,$yeji_reward_data,$month_start,$month_end);
                        if($res['commission'] > 0){
                            \app\common\Member::addcommission($sysset['aid'], $member['id'],  $member['id'], $res['commission'], '业绩奖');
                        }
                    }
                }
            }
        }
    }
    //平台加权奖励【每月1号发放】
    public function platformAvgBonus($type=0){
        if(getcustom('commission_platform_avg_bonus')){
            $day = intval(date('d'));
            if($day!=1 && $type==0){
                return;
            }
            $monthEnd = strtotime(date('Y-m-01 00:00:00',time()));
            $month = date('m',$monthEnd-86400*2);
            //等级下面设置平台奖励的会员
            $mwhere = [];
            $mwhere[] = ['m.levelid','>',0];
            $mwhere[] = ['l.isdefault','=',0];
            $mwhere[] = ['l.platform_avgbonus_percent','>',0];
            $list = Db::name('member')->alias('m')->join('member_level l','m.levelid=l.id')->where($mwhere)->field('m.aid,m.id,m.levelid,l.platform_avgbonus_percent')->select()->toArray();
            //按平台分组
            $data = [];//有要发放的记录 按aid发放
            foreach ($list as $k=>$v){
                $data[$v['aid']][] = $v;
            }
            foreach ($data as $aid=>$members){
                //平台总业绩
//                $orderMoneyCount = 0 + Db::name('shop_order')->where('aid',$aid)->where('status',3)->sum('totalprice');
                $sumResult = Db::name('shop_order')->where('aid',$aid)->where('status',3)->field("sum(`totalprice`-`refund_money`) as totalamount")->find();
                $orderMoneyCount = $sumResult['totalamount'];
                //奖励多少
                foreach ($members as $k=>$member){
                    $bonusPercent = $member['platform_avgbonus_percent'];
                    //同等级的会员平均佣金
                    $levelCount = Db::name('member')->where('aid',$aid)->where('levelid',$member['levelid'])->count('id');
                    $commission = $orderMoneyCount * $bonusPercent * 0.01 / $levelCount;//平均值
                    if($commission){
                        \app\common\Member::addcommission($aid,$member['id'],0,$commission,$month.'月份等级业绩达标平台奖励');
                        Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$member['id'],'type'=>'platform','commission'=>$commission,'score'=>0,'remark'=>$month.'月份等级业绩达标平台奖励','createtime'=>time(),'endtime'=>time(),'status'=>1]);
                    }
                }
            }
        }
    }
    //薪资奖励【每月1号发放】type=0脚本执行
    public function levelSalaryBonus($type=0){
        if(getcustom('member_level_salary_bonus')){
            //每个月1号结算
            $day = intval(date('d'));
            if($day!=1 && $type==0){
                return;
            }
            //上个月的业绩
            $monthEnd = strtotime(date('Y-m-01 00:00:00',time()));
            $days = date('t',$monthEnd-86400*2);//上个月多少天
            $month = date('m',$monthEnd-86400*2);
            $monthStart = $monthEnd - 86400*$days;
            //等级下面设置平台奖励的会员
            $mwhere = [];
            $mwhere[] = ['m.levelid','>',0];
            $mwhere[] = ['l.isdefault','=',0];
            $mwhere[] = ['l.salary_bonus_content','<>',''];
            $mwhere[] = Db::raw('l.salary_bonus_content IS NOT NULL');
            $members = Db::name('member')->alias('m')->join('member_level l','m.levelid=l.id')->where($mwhere)->field('m.aid,m.id,m.levelid,l.salary_bonus_content')->select()->toArray();
            foreach ($members as $k=>$member){
                $aid = $member['aid'];
                //直推会员
                $yejiAmount = \app\model\Commission::getMiniTeamCommission($aid,$member['mid'],$monthStart,$monthEnd);
                if($yejiAmount<=0){
                    continue;
                }
                $memberNum = Db::name('member')->where('aid', $aid)->where('pid', $member['mid'])->count('id');
                $bonuslist = json_decode($member['salary_bonus_content'],true);
                //倒叙找符合的第一个
                $newbonuslist = array_reverse($bonuslist);
                foreach ($newbonuslist as $bk=>$bonus){
                    if($memberNum>=$bonus['member_num'] && $yejiAmount>=$bonus['yj_amount'] && $bonus['bonus']>0){
                        //发放达标奖励
                        $commission = $bonus['bonus'];
                        \app\common\Member::addcommission($aid,$member['id'],0,$commission,$month.'月份业绩达标工资补贴');
                        Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$member['id'],'type'=>'salary','commission'=>$commission,'score'=>0,'remark'=>$month.'月份业绩达标工资补贴','createtime'=>time(),'endtime'=>time(),'status'=>1]);
                        break;
                    }
                }
            }
        }
    }

    //佣金自动转积分，每日0点执行一次
    private function commission_to_score(){
        if(getcustom('commission_to_score')){
            Db::startTrans();
            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset){
                //查询今日是否有转换记录
                $today = date('Ymd',time());
                $exit = Db::name('commission_toscore_log')->where('aid',$sysset['aid'])->where('w_day',$today)->find();
                if($exit){
                    continue;
                }
                if($sysset['commission_to_score_time']==1){
                    //设置手动发放的跳过
                    continue;
                }
                $where = [];
                $where[] = ['aid','=',$sysset['aid']];
                $where[] = ['score','>',0];
                //计算全网总佣金
                $commission_total = Db::name('member')->where($where)->field('id,commission,score')->sum('score');
                $res = \app\common\Member::commission_to_score($sysset,0,0,0,$commission_total);
                $res = \app\common\Member::commission_to_score2($sysset,0,0,0,$commission_total);
                dump($res);
            }
            Db::commit();
        }
        dump('完成');
    }

    public function day_give(){
        if(getcustom('yx_day_give')){
            //每日赠送
            Db::startTrans();
            $aids = Db::name('admin')->where('status',1)->column('id');
//        $aids = [6];
            if(empty($aids)) return;
            $setlist = Db::name('day_give')->whereIn('aid',$aids)->where('status',1)->select()->toArray();
            foreach($setlist as $set){
                //查询今日是否有记录
                $today = date('Y-m-d');
                $yesterdaytime = strtotime($today);
                $exit = Db::name('day_give_log')->where('aid',$set['aid'])->where('date',$today)->find();
                if($exit){
                    \think\facade\Log::write([
                        'file'=>__FILE__,
                        'fun'=>__FUNCTION__,
                        'line'=>__LINE__,
                        'msg'=>'今日分红数据已存在'
                    ]);
                    continue;
                }
                $config = json_decode($set['config_data'],true);
                if(empty($config)) continue;
                $levelids = array_keys($config);
                $memberList = Db::name('member')
                    ->field('id,aid,pid,path,levelid,createtime,day_give_score_total,day_give_commission_total')
                    ->where('aid',$set['aid'])->whereIn('levelid',$levelids)->where('createtime','<',$yesterdaytime)->select()->toArray();
                foreach ($memberList as $item) {
                    $configLevel = $config[$item['levelid']];
                    //上限
                    if(($item['day_give_score_total'] >= $configLevel['scoreMax'] && $configLevel['scoreMax'] > 0) && ($item['day_give_commission_total'] >= $configLevel['commissionMax'] && $configLevel['commissionMax'] > 0)){
                        \think\facade\Log::write([
                            'file'=>__FILE__,
                            'fun'=>__FUNCTION__,
                            'line'=>__LINE__,
                            'member'=>$item,
                            'msg'=>'已达上限'
                        ]);
                        continue;
                    }
                    //查询昨日之前注册的下级
                    $whereM = [];
                    $whereM[] = ['aid', '=', $set['aid']];
                    $whereM[] = ['createtime','<',$yesterdaytime];
                    $levelidsChild = explode(',',$set['gettj_children']);
                    if(!in_array('-1',$levelidsChild)){
                        $whereM[] = ['levelid','in',$levelidsChild];
                    }
                    if(empty($levelidsChild)){
                        \think\facade\Log::write([
                            'file'=>__FILE__,
                            'fun'=>__FUNCTION__,
                            'line'=>__LINE__,
                            'levelidsChild'=>$levelidsChild,
                            'msg'=>'下级条件为空'
                        ]);
                        continue;
                    }
                    $children1 = Db::name('member')->where($whereM)->where('pid',$item['id'])->column('id');
                    $children2 = [];
                    $children3 = [];
                    if($children1){
                        $children2 = Db::name('member')->where($whereM)->whereIn('pid',$children1)->column('id');
                        if($children2){
                            $children3 = Db::name('member')->where($whereM)->whereIn('pid',$children2)->column('id');
                        }
                    }
                    $score = 0;
                    //scoreMax 0或空无上限
                    if($item['day_give_score_total'] < $configLevel['scoreMax'] || empty($configLevel['scoreMax'])){
                        $score = $configLevel['score'];
                        if($children1) $score += $configLevel['score1'] * count($children1);
                        if($children2) $score += $configLevel['score2'] * count($children2);
                        if($children3) $score += $configLevel['score3'] * count($children3);
                        if($configLevel['scoreMax'] > 0 && $score + $item['day_give_score_total'] > $configLevel['scoreMax']){
                            $score = $configLevel['scoreMax'] - $item['day_give_score_total'];
                        }
                    }
                    $commission = 0;
                    if($item['day_give_commission_total'] < $configLevel['commissionMax'] || empty($configLevel['commissionMax'])){
                        $commission = $configLevel['commission'];
                        if($children1) $commission += $configLevel['commission1'] * count($children1);
                        if($children2) $commission += $configLevel['commission2'] * count($children2);
                        if($children3) $commission += $configLevel['commission3'] * count($children3);
                        if($configLevel['commissionMax'] > 0 && $commission + $item['day_give_commission_total'] > $configLevel['commissionMax']){
                            $commission = $configLevel['commissionMax'] - $item['day_give_commission_total'];
                        }
                    }
                    if($score > 0){
                        \app\common\Member::addscore($item['aid'],$item['id'],$score,'系统每日奖励');
                        Db::name('member')->where('aid',$set['aid'])->where('id',$item['id'])->inc('day_give_score_total',$score)->update();
                    }
                    if($commission > 0){
                        \app\common\Member::addcommission($item['aid'],$item['id'],0,$commission,'系统每日奖励');
                        Db::name('member')->where('aid',$set['aid'])->where('id',$item['id'])->inc('day_give_commission_total',$commission)->update();
                    }
                    if($score > 0 || $commission > 0)
                        Db::name('day_give_log')->insert(['aid'=>$item['aid'],'mid'=>$item['id'],'date'=>$today,'score'=>$score,'commission'=>$commission,'createtime'=>time()]);
                }
            }
            Db::commit();
        }
    }
    
    //团队分红奖励
    private function teamyejiJiangli(){
        $is_include_self = getcustom('yx_team_yeji_include_self');
        $is_jicha_custom = getcustom('yx_team_yeji_jicha');
        $pingji_yueji_custom = getcustom('yx_team_yeji_pingji_jinsuo');
        $is_team_yeji_jc = getcustom('yx_team_yeji_jc');
        if(getcustom('yx_team_yeji')){
            $syssetlist = Db::name('admin_set')->select()->toArray();
            foreach($syssetlist as $sysset) {
                $yeji_set = Db::name('team_yeji_set')->where('aid',$sysset['aid'])->find();
                if(!$yeji_set || $yeji_set['status'] == 0){//未开启
                    continue;
                }
                if($yeji_set['jiesuan_type'] == 1){//按月
                    if(date('d') !='01' ||  date('H') !='01'){
                        continue;
                    }
                }elseif($yeji_set['jiesuan_type'] == 3){//按季度 1-1 4-1 7-1 10-1
                  if(!in_array(date('m-d'), ['01-01', '04-01', '07-01', '10-01']) || date('H') != '01'){
                    continue;
                  }
                }elseif($yeji_set['jiesuan_type'] == 2){//按年
                  if(date('m-d') != '01-01' || date('H') != '01'){
                    continue;
                  }
                }elseif($yeji_set['jiesuan_type'] == 4){//按天
                    if(date('H') != '01'){
                        continue;
                    }
                }
                if(!getcustom('yx_team_yeji_jicha_new')){
                    $yeji_set['jicha_fenhong'] = 0;
                }
               
                $config = json_decode($yeji_set['config_data'],true);
                if(!$config || empty($config)) continue;
                Db::name('member')->where('aid', $sysset['aid'])
                    ->order('id desc')
                    ->chunk(10, function ($member_list) use ($config, $yeji_set, $sysset,$is_include_self,$is_jicha_custom,$pingji_yueji_custom,$is_team_yeji_jc){

                        foreach($member_list as $key => $member){
                            $mid = $member['id'];
                            $fenhong = 0;
                            if($is_jicha_custom && $yeji_set['is_jicha']){
                                $fenhong= \app\common\Order::getDownTeamyejiJiangli($member,$yeji_set,$sysset,$config,0);
                            }elseif($is_team_yeji_jc && $yeji_set['jc_fenhong']){
                                \app\custom\TeamYejiJc::teamYejiJc($member, $yeji_set, $sysset, $config);
                            } else{
                                $now_month = date('Y-m',strtotime('-1 month'));
                                $xuni_yeji = 0;  //虚拟业绩
                                $yejiwhere = [];
                                $startdate = 0;
                                $enddate = 0;
                                if($yeji_set['jiesuan_type'] == 1){//按月
                                    $startdate = strtotime(date('Y-m-01 00:00:00',strtotime('-1 month')));
                                    $enddate  = strtotime(date('Y-m-t 23:59:59',strtotime('-1 month')));

                                    //虚拟业绩 yeji_type业绩类型 0 统计价格（默认） 1:统计数量 不统计虚拟业绩
                                    if(!$yeji_set['yeji_type']) $xuni_yeji = 0 +Db::name('tem_yeji_xuni')->where('aid',$sysset['aid'])->where('mid',$mid)->where('yeji_month',$now_month)->value('yeji');
                                }elseif($yeji_set['jiesuan_type'] == 2){//按年
                                    $startdate=strtotime((date('Y')-1) . '-01-01 00:00:00');
                                    $enddate=strtotime((date('Y')-1) . '-12-31 23:59:59');
                                }elseif($yeji_set['jiesuan_type'] == 3){//按季度
                                    $startdate=strtotime(date('Y-m-01 00:00:00',strtotime('-3 month')));
                                    $enddate=strtotime(date('Y-m-t 23:59:59',strtotime('-1 month')));
                                }elseif($yeji_set['jiesuan_type'] == 4){//按天
                                    if(getcustom('yx_team_yeji_day')){
                                        $enddate = strtotime(date('Y-m-d 00:00:00'));
                                        $startdate  = $enddate-86400;
                                        //虚拟业绩 yeji_type业绩类型 0 统计价格（默认） 1:统计数量 不统计虚拟业绩
                                        //if(!$yeji_set['yeji_type']) $xuni_yeji = 0 +Db::name('tem_yeji_xuni')->where('aid',$sysset['aid'])->where('mid',$mid)->where('yeji_month',$now_month)->value('yeji');
                                    }
                                }
                                $yejiwhere[] = ['createtime','between',[$startdate,$enddate]];
                                
                                $yejiwhere[] = ['status','in','1,2,3'];
                                $deep = 999;
                                if($config[$member['levelid']]['levelnum'] > 0) $deep = intval($config[$member['levelid']]['levelnum']);
                                $levelids = [];
                                if($pingji_yueji_custom){
                                    //下级统计或越级不算上级业绩
                                    if($yeji_set['yueji_pingji_status']){
                                        $nowlevelsort = Db::name('member_level')->where('aid',$member['aid'])->where('id',$member['levelid'])->value('sort');
                                        //查找等级排序小于当前等级的会员
                                        $levelids= Db::name('member_level')->where('aid',$member['aid'])->where('sort','<',$nowlevelsort)->column('id');
                                    }
                                }
                                $downmids = \app\common\Member::getteammids($sysset['aid'],$mid,$deep,$levelids);
                                if($is_include_self){
                                    if($yeji_set['include_self']) $downmids[] = $member['id'];
                                }
                                if(!$downmids){
                                    \think\facade\Log::write($member['id'].'团队为空');
                                    continue;
                                }

                                $shopwhere = '1=1';
                                if(getcustom('yx_team_yeji_type')){
                                    //是否有商品限制
                                    if($yeji_set['fwtype']==1){
                                        if(empty($yeji_set['productids'])){
                                            $shopwhere = 'id = 0';
                                        }else{
                                            $shopwhere = 'proid in ('.$yeji_set['productids'].')';
                                        }
                                    }
                                }

                                //业绩类型 0 按价格（默认） 1:按数量
                                $shopsumField = 'real_totalprice';
                                if($yeji_set['yeji_type']) $shopsumField = 'num';
                                $tongji_shop = true;$tongji_maidan = false;//统计商城订单、统计商城订单
                                if(getcustom('yx_team_yeji_tongji_fw')){
                                    $tongji_fw = explode(',',$yeji_set['tongji_fw']);
                                    if(!in_array('shop',$tongji_fw)) $tongji_shop = false;
                                    if(in_array('maidan',$tongji_fw)){
                                        $tongji_maidan = true;
                                        //统计数量或指定商品，则不统计买单
                                        if(($yeji_set['yeji_type'] && $yeji_set['yeji_type'] ==1) || $yeji_set['fwtype'] == 1){
                                            $tongji_maidan = false;
                                        }
                                    }
                                }

                                //下级人数
                                $teamyeji = 0;
                                $leiji_total_yeji = 0;
                                //统计商城订单
                                if($tongji_shop){
                                    $teamyeji += Db::name('shop_order_goods')->where('aid',$sysset['aid'])->where('mid','in',$downmids)->where($yejiwhere)->where($shopwhere)->sum($shopsumField);
                                    if(getcustom('yx_team_yeji_leiji')) {
                                        if ($yeji_set['yeji_leiji']) {
                                            $leiji_total_yeji += Db::name('shop_order_goods')->where('aid',$sysset['aid'])->where('mid','in',$downmids)->where('status','in',['1','2','3'])->where('createtime','<',$enddate)->where($shopwhere)->sum($shopsumField);
                                        }
                                    }
                                }
                                //统计买单订单
                                if($tongji_maidan){
                                    $teamyeji += Db::name('maidan_order')->where('aid',$sysset['aid'])->where('mid','in',$downmids)->where($yejiwhere)->sum('paymoney');//paymoney
                                    if(getcustom('yx_team_yeji_leiji')) {
                                        if ($yeji_set['yeji_leiji']) {
                                            $leiji_total_yeji += Db::name('maidan_order')->where('aid',$sysset['aid'])->where('mid','in',$downmids)->where('createtime','<',$enddate)->sum('paymoney');//paymoney
                                        }
                                    }
                                }

                                //业绩类型 0 按价格（默认）增加虚拟销量 1:按数量 不增加虚拟销量
                                if(!$yeji_set['yeji_type']){
                                    $totalyeji = $teamyeji + $xuni_yeji;
                                }else{
                                    $totalyeji = $teamyeji;
                                }

                                //阶梯设置
                                $jt_range = $config[$member['levelid']]['range'];
                                if(!$jt_range){
                                    \think\facade\Log::write($member['id'].'_'.$member['levelid'].'无设置');
                                    continue;
                                }
                                $ratio = $price = $fenhong = 0;//百分比、固定金额
                                $ratio_totalyeji = $totalyeji;
                                if(getcustom('yx_team_yeji_leiji')) {
                                    if ($yeji_set['yeji_leiji']) {
                                        $ratio_totalyeji = $leiji_total_yeji ;
                                    }
                                }

                                $countfenhong = true;//是否计算分红
                                foreach($jt_range as $rk=> $range){
                                    //获取最高段奖励设置
                                    if( $range['start'] <= $ratio_totalyeji && $ratio_totalyeji < $range['end']){
                                        if(!$yeji_set['yeji_type']){
                                            $ratio = $range['ratio'];
                                            $price = $range['price']??0;
                                        }else{
                                            $ratio = 0;
                                            $price = $range['price']??0;
                                        }

                                        if(getcustom('yx_team_yeji_leiji')) {
                                            //定制yx_team_yeji_leiji 累计发放类型 0：按最高奖励发放 1：分段发放
                                            if($yeji_set['yeji_leiji_sendtype'] == 1 && $totalyeji>0){
                                                $countfenhong = false;
                                                $fenhong = \app\custom\AgentCustom::dealSendtype($yeji_set['yeji_leiji_sendtype'],$ratio_totalyeji,$totalyeji,$jt_range,0,$range);
                                            }
                                        }
                                    }
                                }
                                if($countfenhong){
                                    if($ratio > 0) $fenhong += $ratio / 100 * $totalyeji;
                                    if($price>0) $fenhong += $price * $totalyeji;
                                }

                                if($yeji_set['jicha_fenhong'] == 1 && $fenhong>0){
                                    //按级差计算时先记录下来每个会员的原分红数量，发放时再计算级差
                                    $log_data = [
                                        'aid' => $sysset['aid'],
                                        'mid' => $mid,
                                        'path' => $member['path'],
                                        'fenhong' => $fenhong,
                                        'totalyeji' => $totalyeji,
                                        'ratio' => $ratio,
                                        'status' => 0,
                                        'createtime' => time(),
                                    ];

                                    Db::name('yx_team_yeji_fenhong')->insert($log_data);
                                }
                            }
                            if($fenhong > 0 && $yeji_set['jicha_fenhong'] == 0){
                                \app\common\Member::addcommission($sysset['aid'],$mid,0,$fenhong,'团队业绩阶梯分红奖',1,'teamyejifenhong');
                            }
                            //平级
                            if($pingji_yueji_custom && $member['path']){
                                $pingji_yueji_data = json_decode($yeji_set['yueji_pingji_data'],true);
                                //查找path
                                $parentList = Db::name('member')->where('id','in',$member['path'])->order(Db::raw('field(id,'.$member['path'].')'))->select()->toArray();
                                if($parentList){
                                    $parentList = array_reverse($parentList);
                                    $level_lists = Db::name('member_level')->where('aid',$member['aid'])->column('*','id');
                                    //当前设置
                                    $this_pingjidata = $pingji_yueji_data[$member['levelid']];
                                    $parent_arr = [];
                                    $is_jinsuo =  $this_pingjidata['jinsuo'];
                                    $dai = 1;
                                    foreach($parentList as $k=>$parent){
                                        //没级别 紧缩掉
                                        $level_data = $level_lists[$parent['levelid']]??[];
                                        if(!$level_data){
                                            continue;
                                        }
                                        //开启紧缩后，往上查找平级
                                        if($is_jinsuo){
                                            //如果 平级，且不到2级
                                            if($level_data['id'] != $member['levelid'] || count($parent_arr) >= 2){
                                                continue;
                                            }
                                            $parent_arr[$dai] =$parent;
                                            $dai += 1;
                                        }else{
                                            if($dai <= 2 &&  $level_data['id'] == $member['levelid']){
                                                $parent_arr[$dai] =$parent;
                                            }
                                            $dai +=1;
                                        }
                                    }
                                    //根据设置的级数 发放奖励
                                    foreach($parent_arr as $dai=>$pv){
                                        $commission1_ratio = $this_pingjidata['commission1'];
                                        $commission2_ratio = $this_pingjidata['commission2'];
                                        if($dai ==1){
                                            $commission1  = dd_money_format($totalyeji * $commission1_ratio/100);
                                            if($commission1 > 0){
                                                \app\common\Member::addcommission($sysset['aid'],$pv['id'],$member['id'],$commission1,'团队业绩阶梯分红一级平级奖',1,'teamyejifenhong');
                                            }
                                        }
                                        if($dai ==2){
                                            $commission2  = dd_money_format($totalyeji * $commission2_ratio/100);
                                            if($commission2 > 0){
                                                \app\common\Member::addcommission($sysset['aid'],$pv['id'],$member['id'],$commission2,'团队业绩阶梯分红二级平级奖',1,'teamyejifenhong');
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    });
                if($yeji_set['jicha_fenhong'] == 1){
                    //按级差发放分红
                    Db::startTrans();
                    \app\common\Order::team_yeji_jicha_new($sysset['aid']);
                    Db::commit();
                }
            }
        }
    }

    //团队业绩奖励 活动
    private function sendTeamYeajiJiangliActivity(){
         if(getcustom('yx_team_yeji_activity')){

             $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
             foreach($syssetlist as $sysset) {
                 $aid = $sysset['aid'];
                 $activity_list = Db::name('team_yeji_activity')->where('aid',$aid)->where('status',1)->select()->toArray();
                 if(!$activity_list)continue;
                 foreach($activity_list as $key => $yeji_set){
                     if($yeji_set['jiesuan_type'] == 1){//按月
                         if(date('d') !='01' ||  date('H') !='01'){
                             continue;
                         }
                     }elseif($yeji_set['jiesuan_type'] == 3){//按季度 1-1 4-1 7-1 10-1
                         if(!in_array(date('m-d'), ['01-01', '04-01', '07-01', '10-01']) || date('H') != '01'){
                             continue;
                         }
                     }elseif($yeji_set['jiesuan_type'] == 2){//按年
                         if(date('m-d') != '01-01' || date('H') != '01'){
                             continue;
                         }
                     }
                     if($yeji_set['fwtype'] ==1){//0全部商品 1指定商品
                         if(!$yeji_set['proids'])continue;
                     }
                     if(time() < $yeji_set['starttime'] ){
                         continue;
                     }
                     if(time() > $yeji_set['endtime'] ){
                         continue;
                     }
                     $config = json_decode($yeji_set['config_data'],true);
                     if(!$config || empty($config)) continue;
                     Db::name('member')->where('aid', $sysset['aid'])
                         ->field('id,aid,pid,levelid')
                         ->order('id desc')
                         ->chunk(10, function ($member_list) use ($config, $yeji_set, $sysset){
                             foreach($member_list as $key => $member){
                                 $fenhong = \app\common\Order::getTeamYejiJiangiActivity($sysset['aid'],$member,$yeji_set,0);
                                 $mid = $member['id'];
                                 if($fenhong > 0 ){
                                     \app\common\Member::addcommission($sysset['aid'],$mid,0,$fenhong,'团队业绩阶梯分红奖，ID:'.$yeji_set['id'],1,'teamyejifenhong');
                                     $log = [
                                         'aid' => $sysset['aid'],
                                         'mid' => $mid,
                                         'activity_id' => $yeji_set['id'],
                                         'money' => $fenhong,
                                         'createtime' =>time() 
                                     ];
                                     Db::name('tem_yeji_activity_log')->insert($log);
                                 }
                             }
                         });
                 }
             }
         }
    }
    //店铺分销
    private function businessfenxiao(){
        if(getcustom('business_fenxiao')){
            //->where('1=1')
            Db::startTrans();
            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset) {
                \app\common\Business::business_fenxiao($sysset,0);
            }
            Db::commit();
        }
    }
    //分销补贴发放
    private function commission_butie(){
        if(getcustom('commission_butie')){
            //->where('1=1')
            Db::startTrans();
            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset) {
                \app\common\Member::commission_butie($sysset['aid']);
            }
            Db::commit();
        }
    }

    //滤芯到期提醒
    private function send_lvxin_replace_remind(){
        if(getcustom('product_lvxin_replace_remind')){
            $data = Db::name('product_lvxin_replace')->alias('l')
                ->leftJoin('member m', 'm.id = l.mid')
                ->leftJoin('shop_product p', 'p.id = l.proid')
                ->leftJoin('shop_sysset s', 's.aid = l.aid')
                ->field('l.*,m.tel,p.name,s.product_lvxin_replace_remind,s.product_lvxin_expireday_remind,s.product_lvxin_remind_type')
                ->where('l.day','>',0)
                ->where('s.product_lvxin_replace_remind','=',1)
                ->select()->toArray();
            if($data){
                foreach($data as $key => $v) {
                    if(($v['product_lvxin_replace_remind'] != 1) || ($v['day'] > $v['product_lvxin_expireday_remind'])){
                        continue;
                    }

                    $remind_type = explode(',',$v['product_lvxin_remind_type']);

                    //发送消息模板
                    if(in_array('wx',$remind_type)){
                        $tmplcontent_new = [];
                        $tmplcontent_new['thing5'] = trim($v['name']);
                        $tmplcontent_new['thing3'] = '您的滤芯设备将于'.$v['day'].'天后过期，请及时更换';
                        $tmplcontent_new['time2'] = date("Y年m月d日 H:i",time());

                        \app\common\Wechat::sendtmpl($v['aid'],$v['mid'],'tmpl_product_lvxin_replace_remind',[],m_url('pagesC/my/productlvxinreplace',$v['aid']),$tmplcontent_new);
                    }
                    //发送短信
                    if(in_array('sms',$remind_type)){
                        //短信通知
                        $rs = \app\common\Sms::send($v['aid'],$v['tel'],'tmpl_product_lvxin_replace_remind',['name'=>$v['name'],'day'=>$v['day']]);
                    }
                }
            }
        }
    }

    //统计计算累计积分
    private function count_totalscore(){
        $admins = Db::name('admin')->where('status',1)->field('id')->select()->toArray();
        if($admins){
            foreach($admins as $admin){
                self::deal_countscore($admin['id']);
            }
        }
    }
    private static function deal_countscore($aid){
        //查询未统计的累计积分的会员
        $mids = Db::name('member')->where('aid',$aid)->where('iscountscore',0)->where('totalscore',0)->limit(50)->column('id');
        if($mids && !empty($mids)){
            foreach($mids as $mid){
                //统计计算下他累计积分
                $totalscore = Db::name('member_scorelog')->where('mid',$mid)->where('score','>',0)->where('aid',$aid)->sum('score');
                $totalscore += 0;
                //再次验证是否统计过
                $count = Db::name('member')->where('id',$mid)->where('iscountscore',1)->count('id');
                $count += 0;
                if(!$count){
                    Db::name('member')->where('id',$mid)->update(['totalscore'=>$totalscore,'iscountscore'=>1]);
                }
            }
            self::deal_countscore($aid);
        }
    }
    //商城消费排名分红
    private function paimingFenhong(){
        if(getcustom('shop_paiming_fenhong')){
            Db::startTrans();
            $syssetlist = Db::name('paiming_fenhong_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset) {
				$aid = $sysset['aid'];
				$diff_not_in_id = [];
				//查询昨天的销售额
				$yesterday_start = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day')));
                $yesterday_end = strtotime(date('Y-m-d 23:59:59', strtotime('-1 day')));
				// $yesterday_start = strtotime(date('Y-m-d 00:00:00'));
                // $yesterday_end = strtotime(date('Y-m-d 23:59:59'));
                $totalprice = Db::name('shop_order')->where('collect_time','between',[$yesterday_start,$yesterday_end])->where('status','in',[3])->where('aid',$aid)->sum('totalprice');
                //$over_point_amount = $sysset['over_point_amount'];     
                //$totalprice = 100;
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                $check_sale = Db::name('paiming_fenhong_sale')->where('day',$yesterday)->where('aid',$aid)->find();
                if($check_sale){
                    continue;
                }
                //商家每月结算货款        
                if($totalprice <=0){
                    $data_sale = [];
                    $data_sale['aid'] = $aid;
                    $data_sale['totalprice'] = $totalprice;
                    $data_sale['fenhong_amount'] = 0;
                    $data_sale['three_fenhong_amount'] = 0;
                    $data_sale['other_fenhong_amount'] = 0;
                    $data_sale['day'] = $yesterday;
                    $data_sale['createtime'] = time();
                    $id = Db::name('paiming_fenhong_sale')->insertGetId($data_sale);
                    continue;
                }
                //分红金额
                $sale_point_money = round($totalprice * $sysset['sale_ratio'] *0.01,2);
                $three_point_money = round($sale_point_money * $sysset['three_point_ratio'] *0.01,2);
                $other_point_money = $sale_point_money - $three_point_money;
                $point_amount = $sysset['point_amount'];
                writeLog('商城消费分红aid:'.$aid,'paiming_fenhong');  
                writeLog('商城消费分红上期结余:'.$sysset['last_amount'],'paiming_fenhong');  
                $all_amount = $three_point_money + $sysset['last_amount']; 
                $last_amount = 0;//本次剩余金额
                if($sale_point_money > 0){
                    $data_sale = [];
                    $data_sale['aid'] = $aid;
                    $data_sale['totalprice'] = $totalprice;
                    $data_sale['fenhong_amount'] = $sale_point_money;
                    $data_sale['three_fenhong_amount'] = $three_point_money;
                    $data_sale['other_fenhong_amount'] = $other_point_money;
                    $data_sale['day'] = $yesterday;
                    $data_sale['createtime'] = time();
                    $id = Db::name('paiming_fenhong_sale')->insertGetId($data_sale);
                } 
                $yifa_record_ids=[];           
                if($all_amount > 0){             
                    
                    //补欠的分红
                    $diff_arr = Db::name('paiming_fenhong_record_diff')->where('aid',$aid)->where(['status'=>0])->order('createtime asc')->select()->toArray();
                    if($diff_arr && $all_amount >0){
                        foreach($diff_arr as $k=>$v){
                            $record_ids = explode(',',$v['record_ids']);                        
                            $record_diff = Db::name('paiming_fenhong_record')->where('aid',$aid)->where(['status'=>0])->where('id','in',$record_ids)->order('createtime asc')->select()->toArray();
                            if($all_amount <=0){
                                continue;
                            }
                            //业绩充足
                            if($all_amount >= $v['amount']){                            
                                foreach($record_diff as $krd=>$vrd){
                                    $diff_not_in_id[] = $vrd['id'];
                                    $up_data = [];
                                    $diff_bu = $vrd['max_amount'] - $vrd['already_amount'];
                                    $all_amount = $all_amount - $diff_bu;
                                    $up_data['already_amount'] = $vrd['max_amount'];
                                    $up_data['status'] = 1;   
                                    //修改分红排位点
                                    Db::name('paiming_fenhong_record')->where('aid',$aid)->where('id',$vrd['id'])->update($up_data);                                
                                    //分红记录
                                    \app\common\Member::addpaimingfenhong($aid,$vrd['mid'],$diff_bu,'商城消费分红',0,$vrd['id']);                            
                                }
                                Db::name('paiming_fenhong_record_diff')->where('aid',$aid)->where('id',$v['id'])->update(['status'=>1]);
                            }else{                            
                                $diff_point_num = count($record_ids);
                                $diff_point_amount = floor($all_amount*100/$diff_point_num)*0.01;
                                $diff_money_bu = 0;
                                foreach($record_diff as $krd=>$vrd){
                                    $diff_not_in_id[] = $vrd['id'];
                                    $up_data = [];
                                    $diff_bu = $vrd['max_amount'] - $vrd['already_amount'];
                                    if($diff_bu > $diff_point_amount){
                                        $add_money = $diff_point_amount;
                                        $up_data['already_amount'] = $vrd['already_amount']+$add_money;
                                        $diff_money_record = $vrd['max_amount'] - $vrd['already_amount']-$add_money;
                                        $diff_id[] = $vrd['id'];
                                        $diff_money_bu +=$diff_money_record;//累计差值金额
                                        $all_amount = $all_amount - $add_money;
                                    }else{
                                        //足额的直接扣除
                                        $add_money = $diff_bu;
                                        $use_money = $diff_point_amount - $diff_bu;
                                        $all_amount = $all_amount - $add_money;
                                        $up_data['already_amount'] = $vrd['already_amount']+$add_money;
                                        $up_data['status'] = 1;                        
                                    }
                                    //修改分红排位点
                                    Db::name('paiming_fenhong_record')->where('aid',$aid)->where('id',$vrd['id'])->update($up_data);
                                    //分红记录
                                    \app\common\Member::addpaimingfenhong($aid,$vrd['mid'],$add_money,'商城消费分红',0,$vrd['id']);                           
                                }
                                if($diff_money_bu > 0){
                                    Db::name('paiming_fenhong_record_diff')->where('aid',$aid)->where('id',$v['id'])->update(['amount'=>$diff_money_bu,'record_ids' => implode(',',$diff_id)]);
                                }else{
                                    Db::name('paiming_fenhong_record_diff')->where('aid',$aid)->where('id',$v['id'])->update(['status'=>1]);
                                }
                                
                            }
                        }
                    }   
                    $where_r[] = ['aid','=',$aid];
                    if($diff_not_in_id){
                        $diff_not_in_id = array_unique($diff_not_in_id);
                        $where_r[]=['id','not in',$diff_not_in_id];
                        $yifa_record_ids = array_merge($yifa_record_ids,$diff_not_in_id);
                        writeLog('商城消费分红第一批补差额发放人员:'.implode(',',$diff_not_in_id),'paiming_fenhong');  
                    }  
                    
                                            
                    $point_num = floor($all_amount/$point_amount);
                    //获取真实名额 前面3个不足3个算3个，超出3个以后5个算3个，8个算6个，10个算9个，…16个算15个…32个算30个，以此类推
                    if($point_num>3){
                        $real_point_num = floor($point_num/3) * 3;
                    }else{
                        $real_point_num = 3;
                        $point_amount = floor($all_amount*100/3)*0.01;
                    }                        
                    //查询换算当前分红人数
                    $record_count = Db::name('paiming_fenhong_record')->where($where_r)->where(['status'=>0])->count();                    
                    //名额不足跳过 业绩不足跳过
                    if($record_count >3 && $all_amount >0.1){
                        writeLog('商城消费分红第一批正常发放人员数量:'.$real_point_num,'paiming_fenhong');  
                        $record = Db::name('paiming_fenhong_record')->where($where_r)->where(['status'=>0])->order('createtime asc')->limit($real_point_num)->select()->toArray();
                        $shengyu_use_money = 0;
                        
                        $diff_id = [];
                        $diff_money = 0;
                        $fafang_money = 0;
                        foreach($record as $k=>$v){
                            $up_data = [];
                            $diff = $v['max_amount'] - $v['already_amount'];
                            $yifa_record_ids[] = $v['id'];
                            if($diff > $point_amount){
                                $add_money = $point_amount;
                                $up_data['already_amount'] = $v['already_amount']+$add_money;
                                $diff_money_record = $v['max_amount'] - $v['already_amount']-$point_amount;
                                $diff_id[] = $v['id'];                        
                                $diff_money +=$diff_money_record;
                            }else{
                                $add_money = $diff;
                                $use_money = $point_amount - $diff;
                                $shengyu_use_money +=$use_money;
                                $up_data['already_amount'] = $v['already_amount']+$add_money;
                                $up_data['status'] = 1;                        
                            }
                            $fafang_money +=$add_money;
                            //修改分红排位点
                            Db::name('paiming_fenhong_record')->where('aid',$aid)->where('id',$v['id'])->update($up_data);
                            //分红记录
                            \app\common\Member::addpaimingfenhong($aid,$v['mid'],$add_money,'商城消费分红',0,$v['id']);
                        }
                        //今日不足金额累计到第二天补齐
                        if(count($diff_id) > 0){
                            $data_record = [];
                            $data_record['aid'] = $aid;
                            $data_record['record_ids'] = implode(',',$diff_id);
                            $data_record['amount'] = $diff_money;
                            $data_record['date'] = $yesterday;
                            $data_record['status'] = 0;
                            $data_record['createtime'] = time();
                            $id = Db::name('paiming_fenhong_record_diff')->insertGetId($data_record);
                        }
                        //剩余分红累计
                        $last_amount = round($all_amount - $fafang_money,2);
                        //剩余资金充足继续发放
                        $point_num_yu = floor($last_amount/$point_amount);
                        if($point_num_yu >= 3){
                            $real_point_num = floor($point_num/3) * 3;
                            $where_yu[] = ['aid','=',$aid];
                            if($yifa_record_ids){
                                $where_yu[]=['id','not in',$yifa_record_ids];                               
                            }
                            $record_yu = Db::name('paiming_fenhong_record')->where($where_yu)->where(['status'=>0])->order('createtime asc')->limit($real_point_num)->select()->toArray();
                            $diff_id = [];
                            $diff_money = 0;
                            $fafang_money = 0;
                            foreach($record_yu as $k=>$v){                                          
                                $up_data = [];
                                $diff = $v['max_amount'] - $v['already_amount'];                                
                                if($diff < $point_amount){
                                    $yifa_record_ids[] = $v['id'];
                                    $add_money = $diff;
                                    $use_money = $point_amount - $diff;
                                    $shengyu_use_money +=$use_money;
                                    $up_data['already_amount'] = $v['already_amount']+$add_money;
                                    $up_data['status'] = 1;  
                                    $fafang_money +=$add_money;
                                    //修改分红排位点
                                    Db::name('paiming_fenhong_record')->where('aid',$aid)->where('id',$v['id'])->update($up_data);
                                    //分红记录
                                    \app\common\Member::addpaimingfenhong($aid,$v['mid'],$add_money,'商城消费分红',0,$v['id']);                     
                                }                
                                
                            }
                            writeLog('商城消费分红剩余金额 再次发放:'.$fafang_money,'paiming_fenhong'); 
                            $last_amount = round($last_amount - $fafang_money,2);
                        }                 
                    }else{
                        //累计当前分红
                        $last_amount = $all_amount;               
                    }   
                        
                    writeLog('商城消费分红剩余第一批金额:'.$last_amount,'paiming_fenhong'); 
                    writeLog('商城消费分红第一批已发总列表:'.implode(',',$yifa_record_ids),'paiming_fenhong');         
                }
                      
                //其它金额平均分配给当天剩余未分配的人。
                if($other_point_money){
                    //查询换算当前分红人数
                    $where_other[] = ['aid','=',$aid];
                    if($yifa_record_ids){
                        $yifa_record_ids = array_unique($yifa_record_ids);
                        $where_other[]=['id','not in',$yifa_record_ids];
                    }                
                    $other_record_count = Db::name('paiming_fenhong_record')->where($where_other)->where(['status'=>0])->count();
                    $fafang_money = 0;
                    if($other_record_count>0){
                        $other_point_amount = floor($other_point_money*100/$other_record_count)*0.01;
                        $record = Db::name('paiming_fenhong_record')->where($where_other)->where(['status'=>0])->order('createtime asc')->select()->toArray();
                        
                        foreach($record as $k=>$v){
                            $up_data = [];
                            $diff = $v['max_amount'] - $v['already_amount'];
                            $yifa_record_ids[] = $v['id'];
                            if($diff > $other_point_amount){
                                $add_money = $other_point_amount;
                                $up_data['already_amount'] = $v['already_amount']+$add_money;
                            }else{
                                $add_money = $diff;
                                $use_money = $other_point_amount - $diff;
                                //$shengyu_use_money +=$use_money;
                                $up_data['already_amount'] = $v['already_amount']+$add_money;
                                $up_data['status'] = 1;                        
                            }
                            $fafang_money +=$add_money;
                            //修改分红排位点
                            Db::name('paiming_fenhong_record')->where('aid',$aid)->where('id',$v['id'])->update($up_data);
                            //分红记录
                            \app\common\Member::addpaimingfenhong($aid,$v['mid'],$add_money,'商城消费分红',0,$v['id']);
                        }
                        
                    }
                    //剩余分红累计
                    $other_last_amount = round($other_point_money - $fafang_money,2);
                    writeLog('商城消费分红第二批剩余金额:'.$other_last_amount,'paiming_fenhong'); 
                    $last_amount +=$other_last_amount;
                
                }
                if($last_amount){
                    writeLog('商城消费分红剩余金额:'.$last_amount,'paiming_fenhong'); 
                    Db::name('paiming_fenhong_set')->where('aid',$aid)->update(['last_amount'=>$last_amount]);
                }
            }
            Db::commit();
        }
    }

    //释放通证
    private function release_tongzheng(){
        if(getcustom('product_givetongzheng')){
            //->where('1=1')
            Db::startTrans();
            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset) {
                \app\common\Member::release_tongzheng($sysset);
            }
            Db::commit();
        }
    }

    //满人抽奖活动开奖
    private function manren_choujiang(){
        if(getcustom('yx_choujiang_manren')){
            Db::startTrans();
            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset) {
                \app\common\Choujiang::kaijiang($sysset);
            }
            Db::commit();
        }
    }
    //甘尔定制奖金池
    private function ganer_prize_pool(){
        if(getcustom('ganer_fenxiao')){
            Db::startTrans();
            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset) {
                $set = Db::name('prize_pool_set')->where('aid',$sysset['aid'])->find();
                if(!$set){
                    //未设置奖金池
                    continue;
                }
                if($set['send_time']==0){
                    //手动发放
                    continue;
                }
                if($set['send_time']==1){
                    //每月发放一次
                    if(date('d')!='01'){
                        continue;
                    }
                    $send_time = strtotime(date('Y-m-01 00:00:00'));
                    $exit = Db::name('prize_pool_send_log')
                        ->where('aid','=',$sysset['aid'])
                        ->where('createtime','>=',$send_time)
                        ->where('send_type','=',1)
                        ->find();
                    if($exit){
                        //已经发放过了
                        continue;
                    }
                }
                if($set['send_time']==2){
                    //每年发放一次
                    if(date('m-d')!='01-01'){
                        continue;
                    }
                    $send_time = strtotime(date('Y-01-01 00:00:00'));
                    $exit = Db::name('prize_pool_send_log')
                        ->where('aid','=',$sysset['aid'])
                        ->where('createtime','>=',$send_time)
                        ->where('send_type','=',1)
                        ->find();
                    if($exit){
                        //已经发放过了
                        continue;
                    }
                }
                $levelids = json_decode($set['levelids'],true);
                \app\common\Fenxiao::send_prize_pool($levelids,$set['send_bili'],$sysset['aid'],1);
            }
            Db::commit();
        }
    }
	
    //签到奖金池开奖
    protected function sign_kaijiang(){
        $date = input('param.date','');
        if(getcustom('sign_pay_bonus')){
            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
			$now_time = time();
            foreach($syssetlist as $sysset) {
				Db::startTrans();
				$aid = $sysset['aid'];
				//查询昨天的奖金池
                if($date){
                    $yesterday = $date;
                }else{
                    $yesterday = date('Ymd',$now_time-86400);
                }
				$info = Db::name('sign_bonus')->where('aid',$aid)->where('date',$yesterday)->where('status',0)->find();
				\app\common\SignBonus::kaijiang($info);
				Db::commit();
            }
        }
    }

    //自动释放绿色积分
    private function release_green_score(){
        if(getcustom('consumer_value_add')){
            Db::startTrans();
            $syssetlist = Db::name('consumer_set')->where('1=1')->select()->toArray();
            $now_time = time();
            foreach($syssetlist as $sysset) {
                if($sysset['green_score_price']>=$sysset['max_price']){
                    \app\common\Member::release_green_score($sysset);
                }
            }
            Db::commit();
        }
    }
    
    //商品柜 补货提醒
    private function pickupDeviceAddstockRemind(){
        if(getcustom('product_pickup_device')){
            $syssetlist= Db::name('product_pickup_device_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $key=>$sysset ){
                $aid = $sysset['aid'];
                $remind_type = explode(',',$sysset['remind_type']);
                $remind_pinlv = explode(',',$sysset['remind_pinlv']);
                //状态未开启， 未设置补货方式， 未开启该方式，未设置时间
                if(!$sysset['add_stock_remind'] || !$remind_type || !in_array(3,$remind_pinlv) ||!$sysset['remind_time']){
                    continue;
                }
                if(date('H:i')==$sysset['remind_time']){
                    $send_list = Db::name('product_pickup_device_goods')
                        ->where('aid',$aid)
                        ->whereColumn('stock','>','real_stock')
                        ->group('device_id')
                        ->field('device_id')
                        ->select()->toArray();
                    foreach($send_list as $key=>$val){
                        $device =Db::name('product_pickup_device')->where('id',$val['device_id'])->field('name,address,uid')->find();
                        //发送消息模板
                        if(in_array('tmpl',$remind_type)){
                            $tmplcontent = [];
                            $tempconNew = [];
                            $tempconNew['thing11'] = $device['name'];//设备名称
                            $tempconNew['thing12'] = $device['address'];//地点
                            $send_uid = explode(',',$device['uid']);
                            \app\common\Wechat::sendhttmplByUids($aid,$send_uid,'tmpl_device_addstock_remind',$tempconNew,m_url('/pagesB/admin/pickupdeviceaddstock',$aid),0);
                        }
                        //发送短信
                        if(in_array('sms',$remind_type)){
                            $tel_list = Db::name('admin_user')->alias('au')
                                ->join('member m','m.id = au.mid')
                                ->where('au.aid',$aid)->where('au.bid',$sysset['bid'])
                                ->where('au.id','in',$device['uid'])
                                ->column('tel');
                            foreach($tel_list as $tel){
                                \app\common\Sms::send($aid,$tel,'tmpl_device_addstock_remind',['address'=>$device['address'],'name' => $device['name']]);
                            }
                        }
                    }
                }
            }
        }
    }
    
    private function queueMultiTeamOut(){
        if(getcustom('yx_queue_free_multi_team')){
            //一小时执行一次 不是1点不执行
            $hour =  intval(date('H'));
            if($hour !=1 && $hour !=9){
                return;
            }
            $syssetlist= Db::name('queue_free_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $key=>$sysset ){
                $aid = $sysset['aid'];
                $bid = $sysset['bid'];
                $set = Db::name('queue_free_set')->where('aid',$aid)->where('bid',0)->find();
                if(!$set)continue;
                $days = $set['queue_multi_team_remind_days']??1;
                if ($set['queue_multi_team_repeat_datetype'] == 2) {//月底
                    $month_start = strtotime(date('Y-m-01 00:00:01'));
                    $month_end = strtotime(date('Y-m-t 23:59:59'));
                    if($hour ==1) {//1点执行 月初或者月底 退出操作
                        //当前天 不等于当月最后一天
                        if (date('t') != date('d')) continue;
                    }
                    if($hour ==9) { //9点执行  发送通知，提前n天
                        $remind_date = strtotime (date ('Y-m-t') . ' -'.$days.' day');
                        if (date('t') != date('d',$remind_date)) continue;
                    }
                } else { //月初 统计上个月
                    $month_start = strtotime(date('Y-m-01 00:00:01') . ' -1 month');
                    $month_end = strtotime(date('Y-m-01 23:59:59') . ' -1 day');
                    if($hour ==1) {//1点执行 月初或者月底 退出操作
                        $day = intval(date('d'));
                        if ($day != 1) continue;
                    }
                    if($hour ==9) { //9点执行  发送通知，提前n天
                        $days = $days-1;
                        $remind_date =strtotime(date('Y-m-t') . ' -'.$days.' day');
                        if (date('d') != date('d',$remind_date)) continue;
                    }
                }
                //正在排队的用户
                $where = [];
                $where[] = ['qf.aid','=',$aid];
                if($set['queue_type_business'] != 1){
                    $where[] = ['qf.bid','=',$bid];
                }
                $where[] = ['qf.status','=',0];
                $where[] = ['qf.quit_queue','=',0];
                $queue_member = Db::name('queue_free')->alias('qf')
                    ->join('member m','m.id = qf.mid')
                    ->where($where)
                    ->group('qf.mid')
                    ->field('qf.mid,m.tel')
                    ->select()->toArray();
                foreach($queue_member as $key=>$member){
                    //统计买单+商城的金额 月份之内
                    $member_total_money  = 0;
                    $ogwhere = [];
                    $ogwhere[] = ['aid','=',$aid];
                    if($set['queue_type_business'] != 1){
                        $ogwhere[] = ['bid','=',$bid];
                    }
                    $ogwhere[] = ['status','in',[1,2,3]];
                    $ogwhere[] = ['mid','=',$member['mid']];
                    $ogwhere[] = ['paytime','between',[$month_start,$month_end]];

                    $oglist = Db::name('shop_order_goods')->where($ogwhere)->select()->toArray();
                    foreach ($oglist as $og){
                        $product = Db::name('shop_product')->where('id',$og['proid'])->where('aid',$aid)->where('bid',$bid)->find();
                        if($product['queue_free_status'] == 1){
                            $member_total_money += $og['real_totalprice'];
                        }
                    }

                    //买单
                    $maidanwhere = [];
                    $maidanwhere[] = ['aid','=',$aid];
                    $maidanwhere[] = ['mid','=',$member['mid']];;
                    if($set['queue_type_business'] != 1){
                        $maidanwhere[] = ['bid','=',$bid];
                    }
                    $maidan_money = 0+Db::name('maidan_order')->where($maidanwhere)->where('paytime','between',[$month_start,$month_end])->sum('paymoney');
                    $member_total_money +=$maidan_money;

                    //复购金额 小于 设置金额，该会员对应排队取消
                    if($member_total_money < $set['queue_multi_team_repeat_money'] && $set['queue_multi_team_repeat_money'] > 0){
                        if($hour ==1) {//1点执行 月初或者月底 退出操作 
                            $q_whehre = [];
                            $q_whehre[] = ['aid', '=', $aid];
                            $q_whehre[] = ['status', '=', 0];
                            $q_whehre[] = ['quit_queue', '=', 0];
                            if ($set['queue_type_business'] != 1) {
                                $q_whehre[] = ['bid', '=', $bid];
                            }
                            $q_whehre[] = ['mid', '=', $member['mid']];
                            Db::name('queue_free')->where($q_whehre)->update(['quit_queue' => 1]);
                        }
                        if($hour ==9){//发送通知
                            if ($set['queue_multi_team_repeat_datetype'] == 2) {//月底
                                $quit_date = date('Y-m-t');
                            }else{
                                $quit_date =date('Y-m-d',strtotime(date('Y-m-t') . ' +1 day'));
                            }
                            //短信 
                            $tel = $member['tel'];
                            \app\common\Sms::send($aid,$tel,'tmpl_queue_free_before_quit',['date' => $quit_date]);
                            //模板消息  
                            $tmplcontent = [];
                            $tmplcontentnew = [];
                            $tmplcontentnew['time5'] = $quit_date;
                            $tmplcontentnew['thing3'] = '消费返红包';
                            \app\common\Wechat::sendwxtmpl($aid,$member['mid'],'tmpl_queue_free_before_quit',$tmplcontentnew,'',$tmplcontent);
                        }
                    }
                }

            }
        }
    }

    //自动关闭积分转赠订单
    private function closeScoreTransferSxfOrder(){
        if(getcustom('score_transfer_sxf')) {
            $data = Db::name('score_transfer_order')->where('status', 0)->select()->toArray();
            foreach ($data as $key => $value) {
                $closeTime = Db::name('admin_set')->where('aid', $value['aid'])->value('autoclose_score_transfer');
                if ($closeTime) {
                    if ($value['createtime'] + $closeTime * 60 > time()) {
                        continue;
                    }
                    //关闭订单
                    $res = Db::name('score_transfer_order')->where('id', $value['id'])->where('aid', $value['aid'])->where('mid', $value['mid'])->update(['status' => 4]);
                    if ($res) {
                        //返还积分
                        \app\common\Member::addscore($value['aid'], $value['mid'], $value['score_num'], '积分转赠返还');
                    }
                }
            }
        }
    }

    //根据价格倍数自动释放绿色积分 独立执行的计划任务
    public function green_score_withdraw(){
        if(getcustom('consumer_value_add')){
            Db::startTrans();
            $syssetlist = Db::name('consumer_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset) {
                $res = \app\custom\GreenScore::autoWithdraw($sysset['aid'],$sysset);
//                dump($res);
            }
//            die('stop');
            Db::commit();
        }
    }

    //鱼塘 到达时间
    private function fishpond(){
        if(getcustom('extend_fish_pond')){
            Db::startTrans();
            //关闭订单 0：未支付 1：已支付(使用中)
            $orderList = Db::name('fishpond_order')->where('status','in',[0,1])->select()->toArray();
            $autocloseArr = [];
            foreach($orderList as $order){
                $aid = $order['aid'];
                $mid = $order['mid'];
                $orderid = intval($order['id']);

                //未支付
                if($order['status'] == 0){
                    if(!$autocloseArr[$order['aid']]){
                        $autocloseArr[$order['aid']] = Db::name('fishpond_sysset')->where('aid',$order['aid'])->value('autoclose');
                    }

                    if($order['createtime'] + $autocloseArr[$order['aid']]*60 > time()) continue;

                    //关闭订单
                    Db::name('fishpond_order')
                        ->where('id',$orderid)
                        ->where('aid',$aid)
                        ->where('mid',$mid)
                        ->update([
                            'status' => 4
                        ]);

                    //释放钓点
                    Db::name('fishpond_basan')
                        ->where('aid',$aid)
                        ->where('orderid',$orderid)
                        ->update([
                            'orderid' => '',
                            'ordernum' => '',
                            'starttime'  => '',
                            'endtime' => '',
                            'status' => 0 //未使用
                        ]);
                }elseif($order['status'] == 1){ //已支付(使用中)

                    //判断是否到达使用时间
                    if($order['endtime'] < time()) {

                        Db::name('fishpond_order')
                            ->where('id',$orderid)
                            ->where('aid',$aid)
                            ->update([
                                'status' => 3 //已完成
                            ]);

                        //释放钓点
                        Db::name('fishpond_basan')
                            ->where('aid',$aid)
                            ->where('orderid',$orderid)
                            ->update([
                                'orderid' => '',
                                'ordernum' => '',
                                'starttime'  => '',
                                'endtime' => '',
                                'status' => 0 //未使用
                            ]);

                        //发放佣金
                        $rs = \app\common\Order::collect($order,'fishpond');
                        if($rs['status'] == 0) continue;

                        //发送模板消息
                        $member = Db::name('member')->field('nickname,wxopenid')->where('id',$order['mid'])->find();
                        if($member['wxopenid']){
                            //模板消息
                            $tmplcontent = [];
                            $tmplcontentnew = [];
                            $tmplcontentnew['thing1'] = $order['proname'];
                            $tmplcontentnew['character_string2'] = $order['ordernum'];
                            $tmplcontentnew['thing6'] = $member['nickname']?:$order['linkman'];
                            $tmplcontentnew['time7'] = date('Y-m-d H:i',$order['endtime']);
                            $tourl = 'pagesB/fishpond/orderdetail?id='.$order['id'];
                            \app\common\Wechat::sendwxtmpl($order['aid'],$order['mid'],'tmpl_fishpond_expire',$tmplcontentnew,$tourl,$tmplcontent);
                        }
                    }
                }
            }
            Db::commit();
        }
    }

    //预约开始前通知
    private static function sendnotice_time(){
        if (getcustom('yuyue_before_starting')) {
            $admin = Db::name('admin')->where('status',1)->field('id')->select()->toArray();
            if($admin){
                $time  = time();
                $stime = strtotime(date('Y-m-d H:i',$time));
                foreach($admin as $av){
                    $aid = $av['id'];
                    $orderlist = Db::name('yuyue_order')->where('aid',$av['id'])->where('status',1)->where('begintime','>=',$time)->whereRaw('sendnotice_time = 0 or (sendnotice_time>0 && sendnotice_time < '.$stime.')')->field('id,aid,bid,mid,ordernum,title,begintime,worker_id,sendnotice_time,yy_time,linkman,fwtype,title,linkman,tel,fwbid,area,area2,address')->select()->toArray();
                    if($orderlist){
                        foreach($orderlist as $order){
                            //现在与开始的时间差
                            $timecha = $order['begintime']-time();
                            //查询通知设置
                            $yyset = Db::name('yuyue_set')->where('aid',$order['aid'])->where('bid',$order['bid'])->field('serverbefore_notice,noticedata')->find();
                            if($yyset && $yyset['serverbefore_notice']>0 && !empty($yyset['noticedata'])){
                                $sendmember = $sendworker = false;
                                if($yyset['serverbefore_notice'] == 3){
                                    $sendmember = $sendworker = true;
                                }else if($yyset['serverbefore_notice'] == 1){
                                    if(!$order['mid']) continue;
                                    $sendmember = true;
                                }else if($yyset['serverbefore_notice'] == 2){
                                    if(!$order['worker_id']) continue;
                                    $sendworker = true;
                                }

                                $sendnotice = false;
                                $noticedata = json_decode($yyset['noticedata']);
                                foreach($noticedata as $nv){
                                    //时间差在一分钟内发送消息
                                    $cha = $timecha-$nv*60;
                                    if($cha<=60 && $cha >=-10){
                                        $sendnotice = true;
                                        break;
                                    }
                                }
                                unset($nv);

                                //发送消息
                                if($sendnotice && ($sendmember || $sendworker)){
                                    //预约地址
                                    $yyaddress = '';
                                    //到店
                                    if($order['fwtype'] == 1){
                                        if($order['bid']!=0){
                                            $business = Db::name('business')->where('id',$order['bid'])->field('id,aid,cid,name,logo,tel,address,sales,longitude,latitude,start_hours,end_hours,start_hours2,end_hours2,start_hours3,end_hours3,end_buy_status,invoice,invoice_type,province,city,district')->find();
                                            if($business){
                                                $yyaddress .= $business['province'].$business['city'].$business['district'].$business['address'];
                                            }
                                            
                                        }else{
                                            $set = Db::name('admin_set')->where('aid',$order['aid'])->field('id,name,logo,desc,tel,province,city,district,address')->find();
                                            if($set){
                                                $yyaddress .= $set['province'].$set['city'].$set['district'].$set['address'];
                                            }
                                        }
                                    //上门
                                    }else if($order['fwtype'] == 2){
                                        $yyaddress .= $order['area'].$order['address'];
                                    //到商家
                                    }else if($order['fwtype'] == 3){
                                        $fwbusines = Db::name('business')->where('id',$order['fwbid'])->where('status',1)->where('aid',aid)->field('id,aid,name,logo,tel,linkman,linktel,province,city,district,address,latitude,longitude')->find();
                                        if($fwbusines){
                                            $yyaddress .= $fwbusines['province'].$fwbusines['city'].$fwbusines['district'].$fwbusines['address'];
                                        }
                                    }
                                    $begintime = $order['begintime']?date("Y年m月d日 H:i",$order['begintime']):'无';
                                    if($sendmember){
                                        //公众号
                                        $tmplcontentNew = [];
                                        $tmplcontentNew['thing16'] = $order['title']?$order['title']:'无';//预约项目
                                        $tmplcontentNew['time20']  = $begintime;//预约时间
                                        $tmplcontentNew['thing24'] = $yyaddress?$yyaddress:'无';//预约地址
                                        $tmplcontentNew['thing19'] = $order['linkman']?$order['linkman']:'无';//预约人
                                        $tmplcontentNew['time23']  = $order['begintime']?date("Y-m-d H:i",$order['begintime']):'无';//预约时间
                                        $rs = \app\common\Wechat::sendtmpl($aid,$order['mid'],'tmpl_yuyue_before_starting',[],m_url('yuyue/yuyue/orderlist', $aid),$tmplcontentNew);

                                        //小程序
                                        $tmplcontent = [];
                                        $tmplcontent['thing1']   = $order['title']?$order['title']:'无';//预约项目
                                        $tmplcontent['time3']    = $begintime;//预约时间
                                        $tmplcontent['thing4']   = $yyaddress?$yyaddress:'无';//预约地址
                                        $tmplcontent['thing11']  = $order['linkman']?$order['linkman']:'无';//预约人
                                        $tmplcontent['time12']   = $order['begintime']?date("Y-m-d H:i",$order['begintime']):'无';//预约时间
                                        \app\common\Wechat::sendwxtmpl($aid,$order['mid'],'tmpl_yuyue_before_starting','','yuyue/yuyue/orderlist',$tmplcontent);
                                        //短信通知
                                        $member = Db::name('member')->where('id',$order['mid'])->field('id,tel,wxopenid,mpopenid')->find();
                                        $rs = \app\common\Sms::send($aid,$member['tel']?$member['tel']:$order['tel'],'tmpl_yuyue_before_starting',['title'=>$order['title'],'ordernum'=>$order['ordernum'],'begintime'=>$begintime]);
                                    }
                                    if($sendworker){
                                        //查询服务人员绑定的用户
                                        $worker = Db::name('yuyue_worker')->where('id',$order['worker_id'])->where('aid',$order['aid'])->where('bid',$order['bid'])->field('id,aid,mid,tel')->find();
                                        if($worker && $worker['mid']){
                                            $wmember = Db::name('member')->where('id',$worker['mid'])->field('id,tel,wxopenid,mpopenid')->find();
                                            if($wmember){
                                                //公众号
		                                        $tmplcontentNew = [];
		                                        $tmplcontentNew['thing16'] = $order['title']?$order['title']:'无';//预约项目
		                                        $tmplcontentNew['time20']  = $begintime;//预约时间
		                                        $tmplcontentNew['thing24'] = $yyaddress?$yyaddress:'无';//预约地址
		                                        $tmplcontentNew['thing19'] = $order['linkman']?$order['linkman']:'无';//预约人
		                                        $tmplcontentNew['time23']  = $order['begintime']?date("Y-m-d H:i",$order['begintime']):'无';//预约时间
		                                        $rs = \app\common\Wechat::sendtmpl($aid,$worker['mid'],'tmpl_yuyue_before_starting',[],m_url('yuyue/yuyue/jdorderlist', $aid),$tmplcontentNew);

                                                //小程序
                                                $tmplcontent = [];
                                                $tmplcontent['thing1']   = $order['title']?$order['title']:'无';//预约项目
                                                $tmplcontent['time3']    = $begintime;//预约时间
                                                $tmplcontent['thing4']   = $yyaddress?$yyaddress:'无';//预约地址
                                                $tmplcontent['thing11']  = $order['linkman']?$order['linkman']:'无';//预约人
                                                $tmplcontent['time12']   = $order['begintime']?date("Y-m-d H:i",$order['begintime']):'无';//预约时间
                                                \app\common\Wechat::sendwxtmpl($aid,$worker['mid'],'tmpl_yuyue_before_starting','','yuyue/yuyue/jdorderlist',$tmplcontent);
                                                //短信通知
                                                $rs = \app\common\Sms::send($aid,$worker['tel']?$worker['tel']:$wmember['tel'],'tmpl_yuyue_before_starting',['title'=>$order['title'],'ordernum'=>$order['ordernum'],'begintime'=>$begintime]);
                                            }
                                        }
                                    }
                                    //更新发送时间
                                    Db::name('yuyue_order')->where('id',$order['id'])->update(['sendnotice_time'=>$stime]);
                                }

                            }
                        }
                        unset($order);
                    }
                    
                }
                unset($av);
            }
        }
    }

    //时间段自动完成
    private static function datetype1_autoendorder(){
        if (getcustom('yuyue_datetype1_autoendorder')) {
            $admin = Db::name('admin')->where('status',1)->field('id')->select()->toArray();
            if($admin){
                foreach($admin as $av){
                    //筛选是时间段类型，且是自动完成类型，且预约完成时间大于0 且预约完成时间小等于现在时间
                    $orderlist = Db::name('yuyue_order')->where('aid',$av['id'])->where('status',2)->where('datetype',1)->where('datetype1_autoendorder',1)->where('yyendtime','>',0)->where('yyendtime','<=',time())->select()->toArray();
                    if($orderlist){
                        foreach($orderlist as $order){
                            if( $order['paytypeid']==4){
                                continue;
                            }
                            if($order['balance_price'] > 0 && $order['balance_pay_status']!=1){
                                continue;
                            }
                            if(getcustom('yuyue_apply') && $order['addmoney']>0 && $order['addmoneyStatus']!=1){
                                continue;
                            }

                            $psorder = Db::name('yuyue_worker_order')->where('id',$order['worker_orderid'])->where('worker_id',$order['worker_id'])->field('id,bid,worker_id,aid,status,ticheng')->find();
                            if(!$psorder || $psorder['status']!=2){
                                continue;
                            }

                            $rs = \app\common\Order::collect($order,'yuyue');
                            if($rs['status'] == 0) continue;

                            Db::name('yuyue_worker')->where('id',$order['worker_id'])->inc('totalnum')->update();
                            Db::name('yuyue_order')->where('id',$order['id'])->update(['status'=>3,'collect_time'=>time()]);
                            Db::name('yuyue_worker_order')->where('id',$psorder['id'])->update(['status'=>3,'endtime'=>time()]);
                            \app\common\YuyueWorker::addmoney($av['id'],$psorder['bid'],$order['worker_id'],$psorder['ticheng'],'服务提成');
                        }
                        unset($order);
                    }
                    
                }
                unset($av);
            }
        }
    }
	//自动关闭酒店订单
    private function closeHotelorder(){
        if(getcustom('hotel')) {
            $data = Db::name('hotel_order')->where('status',0)->select()->toArray();
            foreach ($data as $key => $value) {
                $closeTime = Db::name('hotel_set')->where('aid', $value['aid'])->value('autoclose');
                $text = \app\model\Hotel::gettext($value['aid']);
                if ($closeTime) {
                    if ($value['createtime'] + $closeTime * 60 > time()) {
                        continue;
                    }
					if($value['paytypeid']!=1 && $value['use_money']>0){
						/*$text = \app\model\Hotel::gettext($value['aid']);
						\app\common\Member::addmoney($value['aid'],$value['mid'],$value['use_money'],$text['酒店'].'订单超时未付款返还');*/
					}
                    //关闭订单 -1关闭
                    $res = Db::name('hotel_order')->where('id', $value['id'])->where('aid', $value['aid'])->where('mid', $value['mid'])->update(['status' => -1]);
                    if($res){
                        if(getcustom('member_upgradescore')){
                            //返回升级积分抵扣
                            if($value['upgradescore']>0 && $value['upgradescore_status'] == 1){
                                $params=['orderid'=>$value['id'],'ordernum'=>$value['ordernum'],'paytype'=>'hotel'];
                                \app\common\Member::addupgradescore($value['aid'],$value['mid'],$value['upgradescore'],$text['升级积分'].'抵扣返回，订单号: '.$value['ordernum'],$params);
                            }
                        }
                        //返回余额抵扣
                        if($value['use_money']>0 && $value['use_money_paystaus'] == 1){
                            \app\common\Member::addmoney($value['aid'],$value['mid'],$value['use_money'],t('余额').'抵扣返回，订单号: '.$value['ordernum']);
                        }
                    }
                }
            }
        }
    }
    //根据封顶额度自动扣除绿色积分 独立执行的计划任务
    public function dec_green_score(){
        if(getcustom('consumer_value_add') && getcustom('greenscore_max')){
            Db::startTrans();
            $syssetlist = Db::name('consumer_set')->where('1=1')->select()->toArray();
            $now_time = time();
            foreach($syssetlist as $sysset) {
                $res = \app\custom\GreenScore::autoDec($sysset['aid'],$sysset);
            }
            Db::commit();
            dump('完成');
        }
    }

    //约课订单 核销超过X分钟未完成的订单
    private function hexiaoyuekeorder(){
        if(getcustom('yueke_extend')){
            Db::startTrans();
            $data = Db::name('yueke_study_record')->where('status',1)->select()->toArray();

            $currentTime = time();
            foreach ($data as $key => $record) {
                $hexiaotime = Db::name('yueke_set')->where('aid', $record['aid'])->value('autohexiao');
                $hexiaotime = $hexiaotime * 60;

                //核销超过X分钟未核销的订单
                $timeDiff = $currentTime - $record['start_study_time'];
                if ($timeDiff >= $hexiaotime) {

                    $order = Db::name('yueke_order')->where('id',$record['orderid'])->find();
                    //ogid: 学习记录id
                    $order['ogid'] = $record['id'];

                    //发放佣金
                    \app\common\Order::giveCommission($order,'yueke');

                    \app\common\YuekeWorker::addmoney($record['aid'],0,$record['workerid'],$record['workercommission'],'老师上课佣金');

                    // 更新状态为已核销
                    Db::name('yueke_study_record')->where('id', $record['id'])->update([
                        'status' => 2,
                        'iscommission' => 1,
                        'hx_time' => $currentTime
                    ]);

                    //修改订单状态
                    $update = [];

                    //判断本次是否是订单中最后一节课程
                    $usedKechengNum = $order['used_kecheng_num'] + 1;
                    $kechengNum = $order['total_kecheng_num'] - $order['refund_kecheng_num'] - $usedKechengNum;

                    if($kechengNum == 0){
                        $update['status'] = 3;
                    }
                    $update['used_kecheng_num'] = $usedKechengNum;
                    Db::name('yueke_order')->where('id',$record['orderid'])->update($update);
                }
            }
            Db::commit();
        }
    }
    public function roomProductAutoOnline(){
        if(getcustom('h5zb')) {
            $config = include(ROOT_PATH . 'config.php');
            set_time_limit(0);
            ini_set('memory_limit', -1);
            if (input('param.key') != $config['authtoken']) die('error');
            for ($i = 0; $i < 6; $i++) {
                \app\custom\H5zb::roomProductAutoOnline();
                sleep(10);
            }
        }
    }


    //团队业绩加权分红
    private function teamyejiWeight(){
        if(getcustom('yx_team_yeji_weight')){
            Db::startTrans();
            $syssetlist = Db::name('team_yeji_weight_set')->where('status',1)->select()->toArray();

            foreach($syssetlist as $yeji_set) {
                if(!$yeji_set || $yeji_set['status'] == 0){//未开启
                    continue;
                }
                if($yeji_set['jiesuan_type'] == 1){//按月
                    if(date('d') !='01' ||  date('H') !='01'){
                        continue;
                    }
                }elseif($yeji_set['jiesuan_type'] == 2){//按季度 1-1 4-1 7-1 10-1
                    if(!in_array(date('m-d'), ['01-01', '04-01', '07-01', '10-01']) || date('H') != '01'){
                        continue;
                    }
                }elseif($yeji_set['jiesuan_type'] == 3){//按年
                    if(date('m-d') != '01-01' || date('H') != '01'){
                        continue;
                    }
                }
                \app\common\Fenhong::teamyejiweight($yeji_set['aid'],$yeji_set);

            }
            Db::commit();
        }
        
    }
    //团队小区业绩统计
    private function teamyeji_count()
    {
        if (getcustom('team_minyeji_count')) {
            Db::startTrans();
            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();

            foreach ($syssetlist as $set) {
                $mids = Db::name('member')->where('aid', $set['aid'])->column('id');
                foreach($mids as $mid){
                    $team_yeji = \app\model\Commission::getTeamYeji($set['aid'],$mid);
                    $team_miniyeji = $team_yeji['min_yeji']?:0;
                    Db::name('member')->where('id',$mid)->update(['team_minyeji' => $team_miniyeji]);
                }
            }
            Db::commit();
        }
    }

	//酒店自动新增房态房价
	private function addroomdays(){
		$hotellist = Db::name('hotel')->field('id,aid,yddatedays')->where('status',1)->select()->toArray();
		foreach($hotellist as $hotel){
			$maxenddate = time()+($hotel['yddatedays']-1)*86400;
			$roomlist =  Db::name('hotel_room')->where('aid',$hotel['aid'])->where('hotelid',$hotel['id'])->where('status',1)->select()->toArray();
			foreach($roomlist as $room){
				$roomprice = Db::name('hotel_room_prices')->where('aid',$hotel['aid'])->where('hotelid',$hotel['id'])->where('roomid',$room['id'])->where("unix_timestamp(datetime)>=".$maxenddate)->order('id desc')->find();
				if(!$roomprice){  //没有大于的新增
					$roompicenew = Db::name('hotel_room_prices')->where('aid',$hotel['aid'])->where('roomid',$room['id'])->order('id desc')->find();
					$maxenddate = time()+$hotel['yddatedays']*86400;
					if($roompicenew['datetime']){
						$enddate = strtotime($roompicenew['datetime']);
					}
					$days = round(($maxenddate-$enddate)/86400,0);
					\think\facade\Log::write('days:'.$days.'-----------------'.$room['id']);
					//自动创建 房态数据
					\app\model\Hotel::addroomdays($hotel['aid'],$hotel['id'],$room,$room['id'],$days,$enddate);
				}
			}
		}
	}
	//释放冻结
	public function releaseScoreFreeze(){
        $score_weishu_custom = getcustom('score_weishu');
        if(getcustom('yx_score_freeze')){
            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
            foreach ($syssetlist as $set) {
                $aid = $set['aid'];
                $freeze_set = Db::name('score_freeze_set')->where('aid',$aid)->where('bid',0)->find();
                if(!$freeze_set['status'])continue;
                $releasedata = json_decode($freeze_set['releasedata'],true);
                $score_weishu = 0;
                if($score_weishu_custom){
                    $score_weishu = Db::name('admin_set')->where('aid',$aid)->value('score_weishu');
                    $score_weishu = $score_weishu?$score_weishu:0;
                }
                Db::name('member')
                    ->where('aid', $aid)
                    ->where('score_freeze','>',0)
                    ->order('id desc')
                    ->chunk(10, function ($member_list) use ($freeze_set,$releasedata,$score_weishu){
                        foreach($member_list as $key => $member){
                            //会员冻结积分 小于 最小释放积分 不进行释放
                            $min_release_score = dd_money_format($freeze_set['min_release_score'],$score_weishu);
                            $member_score_freeze =  dd_money_format($member['score_freeze'],$score_weishu);
                            if($min_release_score > $member_score_freeze && $min_release_score > 0)continue;
                            $ratio = $releasedata[$member['levelid']];
                            $release_score = dd_money_format($member_score_freeze * $ratio *0.01,$score_weishu);
                            if($release_score > 0){                                                     
                                \app\common\Member::addscore($member['aid'],$member['id'],$release_score,'冻结每日释放','',0,'','',['is_release' =>1]);
                                \app\common\Member::addscorefree($member['aid'],$member['id'],$release_score*-1,'冻结每日释放','',0);
                                //增加释放记录
                                Db::name('score_freeze_release_log')->insertGetId([
                                    'aid' => $member['aid'],
                                    'bid' => 0,
                                    'mid' => $member['id'],
                                    'before' =>$member_score_freeze,
                                    'ratio' => $ratio,
                                    'score' => $release_score,
                                    'after' => dd_money_format($member_score_freeze - $release_score,$score_weishu),
                                    'createtime' => time(),
                                    'remark' => '冻结释放'
                                ]);
                            }
                        }
                    });
            }
        }
    }
    
    //------------排队免单[平均分配]执行发放异步发放操作 start---------
    private function queuefreeAverage(){
        if(getcustom('yx_queue_free_other_mode')){
            //文件锁，防止并发执行
            $file_name = ROOT_PATH.'runtime/queue_free_average_lock.log';
            $is_do = true;
            if(file_exists($file_name)){
                file_put_contents($file_name,date('Y-m-d H:i:s').'任务重复'."\r\n",FILE_APPEND);
                $is_do = false;
            }else{
                file_put_contents($file_name,date('Y-m-d H:i:s').'任务开始'."\r\n",FILE_APPEND);
            }
            if($is_do){
                try {
                    $average_list = Db::name('queue_free_average')
                        ->where('status',0)
                        ->limit(0,200)
                        ->order('id asc')->select()->toArray();
                    foreach($average_list as $key=>$average){
                        $order = json_decode($average['order'],true);
                        $newlog = json_decode($average['newlog'],true);
                        $where = json_decode($average['where'],true);
                        $set = json_decode($average['set'],true);
                        \app\custom\QueueFree::doOrder($average['aid'], $average['bid'], $average['mid'], $order, $average['pj_money'], $newlog, $where, $set, $average['rate_back'],1,$average['mode'],$average['remark']);
                        $average_id[] = $average['id'];
                        Db::name('queue_free_average')->where('id',$average['id'])->update(['status' => 2,'endtime' =>time()]);
                    }
                }catch (\Exception $e) {
                    // 请求失败
                    writeLog($e, 'queue_free_average');
                    unlink($file_name);
                }
                //执行完成删除锁文件
                unlink($file_name);
            }
        }
    }
    //------------排队免单异步发放操作 end-----------

    //-------------------今日平均发放任务 start------------ 
    // 排队免单[今日平均分配]产生奖励操作,凌晨2点执行 ApiAuto/todayAverageFafang/key/配置文件key
    public function todayAverageFafang(){
        $config = include(ROOT_PATH.'config.php');
        set_time_limit(0);
        ini_set('memory_limit', -1);
        if(input('param.key')!=$config['authtoken']) die('error');
        
        if(getcustom('yx_queue_free_today_average')){
            //文件锁，防止并发执行
            $file_name = ROOT_PATH.'runtime/queue_free_today_fafang_lock.log';
            $is_do = true;
            if(file_exists($file_name)){
                file_put_contents($file_name,date('Y-m-d H:i:s').'任务重复'."\r\n",FILE_APPEND);
                $is_do = false;
            }else{
                file_put_contents($file_name,date('Y-m-d H:i:s').'任务开始'."\r\n",FILE_APPEND);
            }
            if($is_do){
                try {
                    \app\custom\QueueFree::todayAverageOtherFafang();
                }  catch (\Exception $e) {
                    // 请求失败
                    writeLog($e, 'queue_free_today_average_fafang');
                    unlink($file_name);
                }
                //执行完成删除锁文件
                unlink($file_name);
            }
        }
    }
    //排队免单[今日平均分配]奖励发放操作 调用queuefreeTodayAverage 凌晨4点执行，ApiAuto/todayAvergeDo/key/配置文件key
    public function todayAvergeDo(){
        $config = include(ROOT_PATH.'config.php');
        set_time_limit(0);
        ini_set('memory_limit', -1);
        if(input('param.key')!=$config['authtoken']) die('error');
        if(getcustom('yx_queue_free_today_average')){
            $queue_free_today_time = cache('yx_queue_free_today_average_time');
            if(!$queue_free_today_time || $queue_free_today_time<time()-3*60) {
                $this->queuefreeTodayAverage();
                cache('yx_queue_free_today_average_time',time());
            }
        }
    }
    //排队免单执行[今日平均分配]奖励发放方法，上面方法调用
    private function queuefreeTodayAverage(){
        if(getcustom('yx_queue_free_today_average')){
            //文件锁，防止并发执行
            $file_name = ROOT_PATH.'runtime/queue_free_today_lock.log';      
          
            if(file_exists($file_name)){
                file_put_contents($file_name,date('Y-m-d H:i:s').'任务重复'."\r\n",FILE_APPEND);
                die();
            }else{
                file_put_contents($file_name,date('Y-m-d H:i:s').'任务开始'."\r\n",FILE_APPEND);
            }
            try {
                $average_list = Db::name('queue_free_today_average')
                ->where('status',0)
                 ->limit(0,200)   
                ->order('id asc')->select()->toArray();
                //防止重复发 先更改数据状态
                foreach($average_list as $key=>$average){
                    $order = json_decode($average['order'],true);
                    $newlog = json_decode($average['newlog'],true);
                    $where = json_decode($average['where'],true);
                    $set = json_decode($average['set'],true);
                    \app\custom\QueueFree::doOrder($average['aid'], $average['bid'], $average['mid'], $order, $average['pj_money'], $newlog, $where, $set, $average['rate_back'],1,$average['mode'],$average['remark']);
                    $average_id[] = $average['id'];
                    Db::name('queue_free_today_average')->where('id',$average['id'])->update(['status' => 1,'endtime' =>time()]);
                }
            } catch (\Exception $e) {
                // 请求失败
                writeLog($e, 'queue_free_today_average');
                unlink($file_name);
                die();
            }
            //执行完成删除锁文件
            unlink($file_name);
            die();
        }
    }
    //-------------------今日平均发放操作 end--------------

    //------------排队免单[金额分配]执行发放异步发放操作，3分钟执行一次 start---------
    private function queuefreeMoneyBack(){
        if(getcustom('yx_queue_free_other_mode')){
            //文件锁，防止并发执行
            $file_name = ROOT_PATH.'runtime/queue_free_moneyback_lock.log';
            $is_do = true;
            if(file_exists($file_name)){
                file_put_contents($file_name,date('Y-m-d H:i:s').'任务重复'."\r\n",FILE_APPEND);
                $is_do = false;
            }else{
                file_put_contents($file_name,date('Y-m-d H:i:s').'任务开始'."\r\n",FILE_APPEND);
            }
            if($is_do){
                try {
                    $moneyback_list = Db::name('queue_free_moneyback')
                        ->where('status',0)
                        ->limit(0,200)
                        ->order('id asc')->select()->toArray();
                    foreach($moneyback_list as $key=>$moneyback){
                        $order = json_decode($moneyback['order'],true);
                        $newlog = json_decode($moneyback['newlog'],true);
                        $where = json_decode($moneyback['where'],true);
                        $set = json_decode($moneyback['set'],true);
                        \app\custom\QueueFree::doOrder($moneyback['aid'], $moneyback['bid'], $moneyback['mid'], $order, $moneyback['money'], $newlog, $where, $set, $moneyback['rate_back'],1,$moneyback['mode'],$moneyback['remark']);
                        $average_id[] = $moneyback['id'];
                        Db::name('queue_free_moneyback')->where('id',$moneyback['id'])->update(['status' => 2,'endtime' =>time()]);
                    }
                }catch (\Exception $e) {
                    // 请求失败
                    writeLog($e, 'queue_free_moneyback');
                    unlink($file_name);
                }
                //执行完成删除锁文件
                unlink($file_name);
            }
        }
    }
    //------------排队免单异步发放操作 end-----------
    
    
    //结算佣金 门店核销分润
    public function commissionFenrun(){
    	$aids = Db::name('admin')->where('status',1)->column('id');
    	foreach ($aids as $aid) {
    		$recordList = Db::name('mendian_coupon_commission_log')->where('aid',$aid)->where('status',0)->select()->toArray();
	        foreach($recordList as $k=>$record){
	            // 发放
	            Db::name('mendian_coupon_commission_log')->where('aid',$aid)->where('id',$record['id'])->update(['status'=>1,'endtime'=>time()]);
	            if($record['commission'] > 0){
	                \app\common\Member::addcommission($aid,$record['mid'],$record['frommid'],$record['commission'],'核销奖金分润');
	            }
	        }
    	}  
    }
    // 车辆到期提醒
    public function carManagement(){
    	$time = strtotime(date("Y-m-d"));
    	$car_set = Db::name('car_set')->where('1=1')->select()->toArray();
    	foreach ($car_set as $set) {
    		$aid = $set['aid'];
    		$tel = Db::name('admin_set')->where('aid',$aid)->value('tel');
    		// 提前通知
    		$tixingtime = $time + $set['day']*86400;

    		// 客户预约车险通知
    		$cars = Db::name('car')->where("aid=".$aid." and (baoxian_time=".$tixingtime." or baoxian_time=".$time.")")->order('id desc')->select()->toArray();
    		if($cars){
    			foreach ($cars as $key => $value) {
    				// 通知
					$tmplcontent_new = [];
					$tmplcontent_new['car_number6'] = trim($value['car_num']);
					$tmplcontent_new['phrase8'] = $value['truename'];
					$tmplcontent_new['time9'] = date("Y年m月d日",$value['baoxian_time']);
					$tmplcontent_new['phone_number4'] = $tel;
					\app\common\Wechat::sendtmpl($aid,$value['mid'],'tmpl_car_baoxian',[],m_url('pagesC/registervehicle/vehicleList',$aid),$tmplcontent_new);
    			}
			}

			// 车辆保养申请提醒
    		$cars = Db::name('car')->where("aid=".$aid." and (baoyang_time=".$tixingtime." or baoyang_time=".$time.")")->select()->toArray();
    		if($cars){
    			foreach ($cars as $key => $value) {
    				// 通知
					$tmplcontent_new = [];
					$tmplcontent_new['car_number1'] = trim($value['car_num']);
					$tmplcontent_new['time4'] = date("Y-m-d",$value['baoyang_time']);
					
					\app\common\Wechat::sendtmpl($aid,$value['mid'],'tmpl_car_baoyang',[],m_url('pagesC/registervehicle/vehicleList',$aid),$tmplcontent_new);
    			}
			}

			// 车辆年检申请提醒
    		$cars = Db::name('car')->where("aid=".$aid." and (nianjian_time=".$tixingtime." or nianjian_time=".$time.")")->select()->toArray();
    		if($cars){
    			foreach ($cars as $key => $value) {
    				// 通知
					$tmplcontent_new = [];
					$tmplcontent_new['thing4'] = $value['truename'];
					$tmplcontent_new['car_number1'] = trim($value['car_num']);
					$tmplcontent_new['time2'] = date("Y-m-d",$value['nianjian_time']);
					$tmplcontent_new['phone_number7'] = $tel;

					\app\common\Wechat::sendtmpl($aid,$value['mid'],'tmpl_car_nianjian',[],m_url('pagesC/registervehicle/vehicleList',$aid),$tmplcontent_new);
    			}
			}
    	}
    }

    //自动兑换超出倍数的金币 独立执行的计划任务
    public function releaseBonusPoolGold(){
        if(getcustom('bonus_pool_gold')){
            Db::startTrans();
            $set_arr = Db::name('bonuspool_gold_set')->where('1=1')->select()->toArray();
            foreach($set_arr as $set){
                $res = \app\custom\BonusPoolGold::autoWithdraw($set['aid'],$set);
            }
            Db::commit();
            return json(['status'=>1,'msg'=>'释放成功！']);
        }

    }
    //每天一次自动结算买单订单
    private function caclMaidanOrderBonusPoolGold(){
        if(getcustom('bonus_pool_gold')){
            Db::startTrans();
            $set_arr = Db::name('bonuspool_gold_set')->where('1=1')->select()->toArray();
            foreach($set_arr as $set){
                $res = \app\custom\BonusPoolGold::cacl_maidanorder($set['aid'],$set);
            }
            Db::commit();
            return json(['status'=>1,'msg'=>'释放成功！']);
        }

    }
    //每分钟检测一次冻结资金有效期
    private function check_freeze_money(){
        if(getcustom('freeze_money')){
            Db::startTrans();
            $aids = Db::name('admin')->where('status',1)->column('id');
            foreach ($aids as $aid) {
                $res = \app\custom\MemberCustom::check_freeze_money($aid);
            }
            Db::commit();
        }

    }

    //绿色积分根据设置的天数每日释放 独立执行的计划任务
    public function day_release_greenscore(){
        if(getcustom('green_score_new')){
            Db::startTrans();
            $syssetlist = Db::name('consumer_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset) {
                $res = \app\custom\GreenScore::auto_day_release($sysset['aid'],$sysset);
//                dump($res);
            }
//            die('stop');
            Db::commit();
            return json(['status'=>1,'msg'=>'释放成功！']);
        }

    }
    private function huifuFenzheng(){
        if(getcustom('pay_huifu_fenzhang')){
            //回调时huifu_log的paystatus =1在确认收货后，所以确认收货执行时，，查询不到不能执行分账，在此执行
            $huifuset = Db::name('sysset')->where('name','huifuset')->find();
            $appinfo = json_decode($huifuset['value'],true);
            $loglist = Db::name('huifu_log')->whereNotNull('fenzhangdata')->where('isfenzhang',0)->where('pay_status',1)->where('fenzhangdata','NOT NULL',null)->where('tablename','maidan')->select()->toArray();
            if($loglist){
                foreach($loglist as $key => $log){
                    $aid = $log['aid'];
                    $bid = $log['bid'];
                    $mid = $log['mid'];
                    if(getcustom('pay_huifu_fenzhang_backstage')){
                        //后台独立设置服务商
                        $huifuset_backstage = Db::name('admin_set')->where('aid',$aid)->value('huifuset');
                        if($huifuset_backstage){
                            $appinfo = json_decode($huifuset_backstage,true);
                        }
                    }
                    $huifu = new \app\custom\Huifu($appinfo,$aid,$bid,$mid,'分账',$log['ordernum']);
                    $huifu -> comfirmTrade($log);
                }
            }
        }
    }

    public function up_giveparent_help(){
        if(getcustom('up_giveparent_help')) {
            Db::startTrans();
            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset) {
                \app\custom\MemberCustom::help_check($sysset['aid'],$sysset);
            }
            Db::commit();
            return json(['code' => 1, 'msg' => '检测完成']);
        }
    }
   
    public function  quitSendCoupon(){
        if(getcustom('yx_queue_free_quit_give_coupon')){
            $syssetlist= Db::name('queue_free_set')->where('bid',0)->select()->toArray();
            foreach($syssetlist as $sysset){
                \app\custom\QueueFree::quitSendCoupon($sysset['aid']);
            }
        }
        
    }

    public static function dealgiveorder(){
        if(getcustom('shop_giveorder')){
            $admin = Db::name('admin')->where('status',1)->field('id')->select()->toArray();
            if($admin){
                foreach($admin as $av){
                    //查询有效时间
                    $validtime = Db::name('shop_sysset')->where('aid',$av['id'])->value('giveorder_validtime');
                    $endtime = time()-$validtime*3600;
                    $orders = Db::name('shop_order')
                        ->where('aid',$av['id'])->where('status',1)->where('usegiveorder',1)->where('giveordermid',0)->where('paytime','<=',$endtime)
                        ->select()->toArray();
                    foreach($orders as $order){
                        \app\common\Order::dealShopOrderRefund($order,'赠送好友失败退还');
                    }
                }
                unset($av);
            }
        }
    }

    //新版商家转账自主查询
    private function auto_check_transfer(){
        try {
            //检测商家转账订单
            $wx_state_arr = [
                'ACCEPTED',//'转账已受理',
                'PROCESSING' ,// ' 转账处理中，转账结果尚未明确，如一直处于此状态，建议检查账户余额是否足够',
                'WAIT_USER_CONFIRM' ,// '待收款用户确认，可拉起微信收款确认页面进行收款确认',
                'TRANSFERING' ,// '转账结果尚未明确，可拉起微信收款确认页面再次重试确认收款',
                'CANCELING',//商户撤销请求受理成功，该笔转账正在撤销中
            ];
            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset) {
                $aid = $sysset['aid'];
                if($sysset['wx_transfer_type']!=1){
                    continue;
                }
                //余额提现查询
                $money_lists = Db::name('member_withdrawlog')->where('aid',$aid)->where('status', 4)->where('wx_state', 'in', $wx_state_arr)->select()->toArray();
                foreach ($money_lists as $log) {
                    $paysdk = new WxPayV3($aid, $log['mid'], $log['platform'],1);
                    $rs = $paysdk->transfer_query($log['ordernum'],'member_withdrawlog',$log['id']);

                }
                //佣金提现查询
                $money_lists = Db::name('member_commission_withdrawlog')->where('aid',$aid)->where('status', 4)->where('wx_state', 'in', $wx_state_arr)->select()->toArray();
                foreach ($money_lists as $log) {
                    $paysdk = new WxPayV3($aid, $log['mid'], $log['platform'],1);
                    $rs = $paysdk->transfer_query($log['ordernum'],'member_commission_withdrawlog',$log['id']);
                }
                //商家提现查询
                $money_lists = Db::name('business_withdrawlog')->where('aid', $aid)->where('status', 4)->where('wx_state', 'in', $wx_state_arr)->select()->toArray();
                foreach ($money_lists as $log) {
                    $mid = Db::name('admin_user')->where('aid', $aid)->where('bid', $log['bid'])->where('isadmin', 1)->value('mid');
                    $paysdk = new WxPayV3($aid, $mid, $log['platform'],1);
                    $rs = $paysdk->transfer_query($log['ordernum'],'business_withdrawlog',$log['id']);
                }
                //服务提成提现查询
                $money_lists = Db::name('yuyue_worker_withdrawlog')->where('aid', $aid)->where('status', 4)->where('wx_state', 'in', $wx_state_arr)->select()->toArray();
                foreach ($money_lists as $log) {
                    $mid = Db::name('yuyue_worker')->where('aid',$aid)->where('bid',$log['bid'])->where('id',$log['uid'])->value('mid');
                    $paysdk = new WxPayV3($aid, $mid, $log['platform'],1);
                    $rs = $paysdk->transfer_query($log['ordernum'],'yuyue_worker_withdrawlog',$log['id']);
                }
                //配送员提现查询
                $money_lists = Db::name('peisong_withdrawlog')->where('aid', $aid)->where('status', 4)->where('wx_state', 'in', $wx_state_arr)->select()->toArray();
                foreach ($money_lists as $log) {
                    $mid = Db::name('peisong_user')->where('aid',$aid)->where('id',$log['uid'])->value('mid');
                    $paysdk = new WxPayV3($aid, $mid, $log['platform'],1);
                    $rs = $paysdk->transfer_query($log['ordernum'],'peisong_withdrawlog',$log['id']);
                }
                //门店提现查询
                $mendian_lists = Db::name('mendian_withdrawlog')->where('aid', $aid)->where('status', 4)->where('wx_state', 'in', $wx_state_arr)->select()->toArray();
                foreach ($mendian_lists as $log) {
                    $paysdk = new WxPayV3($aid, $log['mid'], $log['platform'],1);
                    $rs = $paysdk->transfer_query($log['ordernum'],'mendian_withdrawlog',$log['id']);
                }
            }
        }catch ( \Exception $e){
            writeLog('新版商家转账自主查询异常1：'.$e->getMessage(),'wx_pay_query');
        }catch (\ParseError $e) {
            // 专门处理语法错误
            writeLog('新版商家转账自主查询异常2：'.$e->getMessage(),'wx_pay_query');
        } catch (\Error $e) {
            // 处理其他错误
            writeLog('新版商家转账自主查询异常3：'.$e->getMessage(),'wx_pay_query');
        }
    }

    public static function dealforzengxcommission(){
        if(getcustom('member_forzengxcommission')){
            $admin = Db::name('admin')->where('status',1)->field('id')->select()->toArray();
            if($admin){
                foreach($admin as $av){
                    $sendmonthtime = strtotime(date("Y-m"));
                    $logs = Db::name('member_forzengxcommissionlog')->where('aid',$av['id'])->where('status',0)->where('sendmonthtime','<',$sendmonthtime)->where('commission2','>',0)->where('sendmonth','>',0)->select()->toArray();
                    foreach($logs as $log){
                        $avgcommission = $log['avgcommission'];//每个月应发佣金

                        $updata = [];
                        if($log['sendmonth2']<$log['sendmonth']){
                            $sendmonth2 = $log['sendmonth2']+1;
                            if($sendmonth2>=$log['sendmonth']){
                                $avgcommission = $log['commission2'];
                                $updata['status'] = 1;
                            }else{
                                //如果平均每月发放佣金大于等于剩余佣金，则发放全部佣金，并结束佣金发放
                                if($avgcommission>=$log['commission2']){
                                    $avgcommission = $log['commission2'];
                                    $updata['status'] = 1;
                                }
                            }
                        }else{
                            if($avgcommission>=$log['commission2']){
                                $avgcommission = $log['commission2'];
                            }
                            $updata['status'] = 1;
                        }
                        $updata['sendmonthtime'] = $sendmonthtime;
                        $updata['updatetime'] = time();
                        $upsql = Db::name('member_forzengxcommissionlog')->where('id',$log['id'])->where('sendmonthtime','<',$sendmonthtime)->dec('commission2',$avgcommission)->inc('sendmonth2',1)->update($updata);
                        if($upsql && $avgcommission>0){
                            \app\common\Member::addcommission($log['aid'],$log['mid'],0,$avgcommission,t('佣金').'解冻增加',1,'member_forzengxcommissionlog',0,0,$log['id']);
                        }
                    }
                }
                unset($av);
            }
        }
    }

    /**
     * 统一发送微信模版消息
     * @author: liud
     * @time: 2025/2/22 下午2:41
     */
    private function sendWechatTmpl(){
        $data = Db::name('wechat_send_tmpl_data')->where('status',0)->select()->toArray();
        if($data){
            foreach($data as $v){
                if(!$v['updatetime']){
                    $tmpl_content = $v['tmpl_content'] ? json_decode($v['tmpl_content'],true) : '';
                    $tmpl_content_new = $v['tmpl_content_new'] ? json_decode($v['tmpl_content_new'],true) : '';
                    $m_url = $v['m_url'] ? m_url($v['m_url'],$v['aid']) : '';

                    if($v['type'] == 'sendhttmpl'){
                        \app\common\Wechat::sendhttmpl($v['aid'],$v['bid'],$v['tmpl_name'],$tmpl_content,$m_url,$v['mdid'],$tmpl_content_new);
                    }
                    else if($v['type'] == 'sendhtwxtmpl'){
                        \app\common\Wechat::sendhtwxtmpl($v['aid'],$v['bid'],$v['tmpl_name'],$tmpl_content,$v['m_url'],$v['mdid']);
                    }
                    else if($v['type'] == 'sendtmpl'){
                        \app\common\Wechat::sendtmpl($v['aid'],$v['mid'],$v['tmpl_name'],$tmpl_content,$m_url,$tmpl_content_new);
                    }
                    else if($v['type'] == 'sendwxtmpl'){
                        \app\common\Wechat::sendwxtmpl($v['aid'],$v['mid'],$v['tmpl_name'],$tmpl_content,$v['m_url'],$tmpl_content_new);
                    }

                    Db::name('wechat_send_tmpl_data')->where('id',$v['id'])->update(['status' => 1,'updatetime' => time()]);
                }
            }
        }
    }

    /**
     * 推三返一循环后产生的佣金每天发放
     * @author: liud
     * @time: 2025/2/25 上午9:23
     */
    private function inviteCashbackCommissionDay()
    {
        if(getcustom('yx_invite_cashback_commission_day')){
            $startOfDay = strtotime(date('Y-m-d'));

            $data = Db::name('member_invite_cashback_log')->where('status',0)->where('back_commission_day','>',0)->where('allcommission','>',0)->where('back_commission_lasttime','<',$startOfDay)->select()->toArray();

            if($data){
                foreach($data as $v){
                    if($v['back_commission'] < $v['allcommission']){
                        //计算每天要发放多少
                        $mtff = round(bcdiv($v['allcommission'],$v['back_commission_day'],3),2);

                        //计算还有多钱没发
                        $mfdeq = bcsub($v['allcommission'],$v['back_commission'],2);

                        if($mfdeq < $mtff){
                            $mtff = $mfdeq;
                        }

                        $zz = bcadd($v['back_commission'],$mtff,2);
                        if($zz <= $v['allcommission']){
                            $gname = Db::name('shop_product')->where('aid',$v['aid'])->where('id',$v['proid'])->value('name') ?? '';
                            //发放
                            $remark =  '商品'.$gname.'邀请返还，每日返还进度：'.$zz.'/'.$v['allcommission'];
                            \app\common\Member::addcommission($v['aid'],$v['mid'],$v['order_mid'],$mtff,$remark);

                            $update = [];
                            $update['back_commission_lasttime'] = time();
                            $update['back_commission'] = $zz;

                            if($zz == $v['allcommission']){
                                //已发放完修改状态
                                $update['status'] = 1;
                            }

                            Db::name('member_invite_cashback_log')->where('aid',$v['aid'])->where('id',$v['id'])->where('status',0)->update($update);
                        }
                    }
                }
            }
        }
    }

    //消费补贴活动根据返还周期每日发放
    private function butieActivityCashbackCommissionDay($time=0,$doaid=0)
    {
        if(getcustom('yx_butie_activity',$doaid)){
            Db::startTrans();
            try {
                \app\custom\ButieActivity::butieActivityCashbackCommissionDay($time);
                Db::commit();    
            } catch (\Exception $e) {
                Db::rollback();
                Log::write("消费补贴活动: ".$e->getMessage());
            }
        }
    }
    //佣金转零钱通
    private function commissionToLingqiantongDay($time=0,$doaid=0)
    {
        if(getcustom('yx_commission_to_lingqiantong',$doaid)){
            Db::startTrans();
            try {
                \app\custom\CommissionLingqiantong::commissionToLingqiantongDay($time);
                Db::commit();    
            } catch (\Exception $e) {
                Db::rollback();
                Log::write("消费补贴活动: ".$e->getMessage());
            }
        }
    }

    private function shopOrderExcelCountpay()
    {
        if(getcustom('shop_order_excel_countpay')){
            //读取配置
            $admins = Db::name('admin')->where('status',1)->field('id')->select()->toArray();
            if($admins){
                foreach($admins as $av){
                    //统计平台订单
                    $countpays = $this->shopOrderExcelCountpay2($av['id'],0);

                    //统计商户订单
                    $blist = Db::name('business')->where('aid',$av['id'])->field('id')->select()->toArray();
                    if($blist){
                        foreach($blist as $bv){
                            $this->shopOrderExcelCountpay2($av['id'],$bv['id']);
                        }
                    }
                }
            }
        }
    }

    private function shopOrderExcelCountpay2($aid,$bid=0){
        if(getcustom('shop_order_excel_countpay')){
            //统计前一天已支付的订单
            $where = [];
            $where[] = ['order.aid','=',$aid];
            $where[] = ['order.bid','=',$bid];
            $where[] = ['order.status','>=',1];
            $where[] = ['order.status','<=',3];

            $endtime = strtotime(date("Y-m-d"));
            $where[] = ['order.paytime','<',$endtime];
            $orders = Db::name('shop_order')->alias('order')
                ->leftJoin('member member','member.id=order.mid')
                ->where($where)->field('order.*')->select()->toArray();

            $countpays = ['countxianjin'=>0,'countscore'=>0,'countmoney'=>0,'countcostPrice'=>0];
            if($orders){
                foreach($orders as $k=>$vo){
                    $totalprice = $vo['totalprice'];
                    //统计实际支付（如微信、支付宝）
                    if($vo['paytypeid'] == 1 || empty($vo['paytypeid'])){
                        $totalprice = 0;
                    }else{
                        if($vo['combine_money'] && $vo['combine_money']>0){
                            $totalprice -= $vo['combine_money'];
                        }
                    }
                    $countpays['countxianjin'] += round($totalprice,2);

                    $countpays['countscore'] += $vo['scoredkscore'];

                    $money = 0;
                    if($vo['combine_money'] && $vo['combine_money']>0){
                        $money += $vo['combine_money'];
                    }else{
                        if($vo['paytypeid'] == 1){
                            $money += $vo['totalprice'];
                        }
                    }
                    if($vo['dec_money'] && $vo['dec_money']>0){
                        $money += $vo['dec_money'];
                    }
                    $countpays['countmoney'] += $money;

                    $cost_price = 0;
                    $oglist = Db::name('shop_order_goods')->where('orderid',$vo['id'])->field('id,cost_price')->select()->toArray();
                    foreach($oglist as $og){
                        if(!empty($og['cost_price'])){
                            $cost_price += $og['cost_price'];
                        }
                    }
                    unset($og);
                    $countpays['countcostPrice'] +=  $cost_price;
                }
            }

            //查询记录
            $countpay = Db::name('shop_order_countpay')->where('aid',$aid)->where('bid',$bid)->find();
            if($countpay){
                $countpays['updatetime'] = time();
                Db::name('shop_order_countpay')->where('id',$countpay['id'])->update($countpays);
            }else{
                $countpays['aid'] = $aid;
                $countpays['bid'] = $bid;
                $countpays['createtime'] = time();
                Db::name('shop_order_countpay')->insert($countpays);
            }
        }
    }

    private function scoreToFenhongdian()
    {
        if(getcustom('score_to_fenhongdian')){
            //积分分红点，分红
            $admins = Db::name('admin')->where('status',1)->field('id')->select()->toArray();
            if($admins){
                foreach($admins as $av){
                    $aid = $av['id'];
                    //读取配置
                    $set = Db::name('score_to_fenhongdian_set')->where('aid',$aid)->find();
                    if($set && $set['score_num']>0 && $set['yeji_ratio']>0){
                        //查询满足积分的用户
                        $members = Db::name('member')->where('aid',$aid)->where('score','>=',$set['score_num'])->field('id,score,levelid')->select()->toArray();
                        if($members){
                            $allfenhongdian = 0;
                            foreach($members as &$member){
                                //计算分红点
                                $fenhongdian = $member['score']/$set['score_num'];
                                $member['fenhongdian'] = floor($fenhongdian);
                                $allfenhongdian += $member['fenhongdian'];
                            }
                            unset($member);
                            if($allfenhongdian<=0) continue;

                            //计算系统昨天新增业绩
                            $endtime   = strtotime(date("Y-m-d"));
                            $starttime = strtotime('-1 day',$endtime);

                            $where = [];
                            $where[] = ['og.aid','=',$aid];
                            if($set['yeji_type'] == 1){
                                $where[] = ['o.paytime','>=',$starttime];
                                $where[] = ['o.paytime','<',$endtime];
                                $where[] = ['o.status','in',[1,2,3]];
                            }else{
                                $where[] = ['o.collect_time','>=',$starttime];
                                $where[] = ['o.collect_time','<',$endtime];
                                $where[] = ['o.status','=',3];
                            }
                            $where[] = ['og.isfenhong','<',2];
                            //统计团队业绩--按订单金额统计
                            $real_totalprice = Db::name('shop_order_goods')->alias('og')
                                ->join('shop_order o','o.id = og.orderid')
                                ->where($where)->sum('og.real_totalprice');
                            $real_totalprice = $real_totalprice ?? 0;
                            if($real_totalprice>0){
                                //计算加权平均分红，这里就是平均分红
                                $avgfenhong = ($real_totalprice * $set['yeji_ratio'] *0.01)/$allfenhongdian;
                                $avgfenhong = round($avgfenhong,2);
                                if($avgfenhong>0){
                                    //分红
                                    foreach($members as $member){
                                        //分红点
                                        $fenhongdian = $member['fenhongdian'];
                                        if($fenhongdian>=1){
                                            $fenhong = $avgfenhong*$fenhongdian;
                                            $fenhong = round($fenhong,2);
                                            if($fenhong>0){
                                                $data = [];
                                                $data['aid'] = $aid;
                                                $data['mid'] = $member['id'];
                                                $data['levelid']= $member['levelid'];
                                                $data['commission'] = $fenhong;
                                                $data['send_commission']= $fenhong;
                                                $data['remark'] = t('积分').'分红点分红';
                                                $data['type']   = 'score_to_fenhongdian';
                                                $data['frommid']    = 0;
                                                $data['send_money'] = 0;
                                                $data['send_score'] = 0;
                                                $data['send_fuchi'] = 0;
                                                $data['status']     = 1;
                                                $data['createtime'] = time();
                                                $logid = Db::name('member_fenhonglog')->insertGetId($data);
                                                if($logid){
                                                    \app\common\Member::addcommission($aid,$data['mid'],$data['frommid'],$data['send_commission'],$data['remark'],1,$data['type'],$data['levelid'],'',$logid);
                                                    if($set['consume_score']>0){
                                                        $consume_score = $fenhong*$set['consume_score'] * 0.01;
                                                        $consume_score = intval($consume_score);
                                                        if($consume_score>0){
                                                            \app\common\Member::addscore($aid,$data['mid'],-$consume_score,$data['remark'].'扣除减少');
                                                        }
                                                        
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    unset($member);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private function commissionWithdrawfeeFundpool()
    {
        if(getcustom('commission_withdrawfee_fundpool')){
            //基金池结算
            $admins = Db::name('admin')->where('status',1)->field('id')->select()->toArray();
            if($admins){
                foreach($admins as $av){
                    $aid = $av['id'];
                    //读取待结算基金池
                    $logs = Db::name('admin_fundpool_log')->where('aid',$aid)->where('status',0)->select()->toArray();
                    if($logs){
                        foreach($logs as $log){
                            //暂时只佣金提现手续费增加
                            if($log['type'] == 'commission_withdrawfee'){
                                //查询佣金提现记录
                                $withdrawlog = Db::name('member_commission_withdrawlog')->where('id',$log['logid'])->find();
                                //若已打款，则标记待结算基金池记录已结算
                                if($withdrawlog && $withdrawlog['status'] == 3){
                                    $params = ['aid'=>$aid,'status'=>1,'logid'=>$withdrawlog['id'],'type'=>'commission_withdrawfee'];
                                    \app\common\Admin::addfundpool($params);
                                }else{
                                    //若记录不存在、或已驳回，则标记待结算基金池记录失效
                                    if(!$withdrawlog || $withdrawlog['status'] == 2){
                                        $params = ['aid'=>$aid,'status'=>-1,'logid'=>$withdrawlog['id'],'type'=>'commission_withdrawfee'];
                                        \app\common\Admin::addfundpool($params);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private function moneyCommissionWithdrawFenxiao(){
        if(getcustom('money_commission_withdraw_fenxiao')){
            //基金池结算
            $admins = Db::name('admin')->where('status',1)->field('id')->select()->toArray();
            if($admins){
                foreach($admins as $av){
                    $aid = $av['id'];
                    $where = [];
                    $where[] = ['aid', '=', $aid];
                    $where[] = ['status', '=', 0];
                    //读取余额提现待结算分销记录
                    $moneyrecords = Db::name('member_commission_record')->where($where)->where('type','money_withdraw')->select()->toArray();
                    if($moneyrecords){
                        foreach($moneyrecords as $record){
                            //查询余额提现记录
                            $withdrawlog = Db::name('member_withdrawlog')->where('id',$record['orderid'])->where('aid',$aid)->find();
                            //若已打款，发放佣金
                            if($withdrawlog && $withdrawlog['status'] == 3){
                                \app\common\Order::giveCommission($withdrawlog,'money_withdraw');
                            }else{
                                //若记录不存在、或已驳回，则标记待结算记录失效
                                if(!$withdrawlog || $withdrawlog['status'] == 2){
                                    Db::name('member_commission_record')->where('id',$record['id'])->update(['status'=>2]);
                                }
                            }
                        }
                        unset($record);
                    }

                    //读取余额提现待结算分销记录
                    $commissionrecords = Db::name('member_commission_record')->where($where)->where('type','commission_withdraw')->select()->toArray();
                    if($commissionrecords){
                        foreach($commissionrecords as $record){
                            //查询佣金提现记录
                            $withdrawlog2 = Db::name('member_commission_withdrawlog')->where('id',$record['orderid'])->where('aid',$aid)->find();
                            //若已打款，发放佣金
                            if($withdrawlog2 && $withdrawlog2['status'] == 3){
                                \app\common\Order::giveCommission($withdrawlog2,'commission_withdraw');
                            }else{
                                //若记录不存在、或已驳回，则标记待结算记录失效
                                if(!$withdrawlog2 || $withdrawlog2['status'] == 2){
                                    Db::name('member_commission_record')->where('id',$record['id'])->update(['status'=>2]);
                                }
                            }
                        }
                        unset($record);
                    }
                }
            }
        }
    }

    private function memberTagAgeGiveScore($aid){
        if(getcustom('member_tag_age')){
            return 1;
            //查询所有有标签的会员
            if($m_arr = Db::name('member')->where('aid',$aid)->whereNotNull('tags')->field('id,score,tags,tag_age_score')->select()->toArray()){
                foreach ($m_arr as $v){
                    if($v['tags']){
                        $tags = explode(',',$v['tags']);
                        if($tags){

                            //排序大的先处理
                            $sort_tags = Db::name('member_tag')->where('aid',$aid)->where('id','in',$tags)->order('sort desc')->select()->toArray();

                            foreach ($sort_tags as $tag_info){
                                //获取每个月赠送的积分
                                //$tag_info = Db::name('member_tag')->where('aid',$aid)->where('id',$vv)->where('give_score','>',0)->field('id,name,give_score,give_score_cover')->find();
                                if($tag_info){
                                    if($tag_info['give_score_cover'] == 0){//每月积分不覆盖

                                        //如果赠送的积分大于0
                                        if($tag_info['give_score'] > 0){
                                            \app\common\Member::addscore($aid,$v['id'],$tag_info['give_score'],'会员标签['.$tag_info['name'].']每月赠送','',0,$tag_info['id']);

                                            //增加年龄标签赠送的积分字段
                                            $tag_age_score = $v['tag_age_score'] + $tag_info['give_score'];
                                            Db::name('member')->where('aid',$aid)->where('id',$v['id'])->update(['tag_age_score'=>$tag_age_score]);
                                        }

                                    }else{//每月积分覆盖
                                        //先减剩余积分
                                        if($v['score'] > 0){
                                            \app\common\Member::addscore($aid,$v['id'],-$v['score'],'会员标签['.$tag_info['name'].']每月赠送前清空上月积分余量','',0,$tag_info['id']);
                                        }

                                        if($tag_info['give_score'] > 0){
                                            //增加积分记录
                                            \app\common\Member::addscore($aid,$v['id'],$tag_info['give_score'],'会员标签['.$tag_info['name'].']每月赠送','',0,$tag_info['id']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private function everyDayCheckTagAge($aid){
        if(getcustom('member_tag_age')){
            //查询所有有标签的会员
            if($m_arr = Db::name('member')->where('aid',$aid)->whereNotNull('tags')->field('id,tags,tag_age_score')->select()->toArray()){
                foreach ($m_arr as $v){
                    if($v['tags']){
                        $tags = explode(',',$v['tags']);
                        if($tags){
                            foreach ($tags as $kk => $vv){
                                //获取年龄标签
                                $tag_info = Db::name('member_tag')->where('aid',$aid)->where('id',$vv)->where('tag_age_type',1)->field('id,name,pid')->find();
                                if($tag_info){
                                    $pid = null;
                                    if($tag_info['pid'] > 0){
                                        $pid = $tag_info['pid'];
                                    }elseif ($tag_info['pid'] == 0){
                                        $pid = $tag_info['id'];
                                    }

                                    if($pid){
                                        //获取这个年龄标签组的所有子标签
                                        if($zi_tag = Db::name('member_tag')->where('aid',$aid)->where('pid',$pid)->where('status',1)->order('sort desc')->select()->toArray()){
                                            //获取申请单里最新的一条记录获取出生日期
                                            $birthday = Db::name('member_tag_age_apply_order')->where('aid',$aid)->where('mid',$v['id'])->where('tag_id',$pid)->where('status',1)->order('id desc')->value('birthday');
                                            if($birthday){
                                                // 获取年龄(岁)计算两个时间戳之间的差异（秒）
                                                $ageInSeconds = strtotime(date('Y-m-d')) - strtotime($birthday);

                                                // 将秒转换为年（考虑到不是每一年都是365天，这里使用更精确的方法）
                                                $ageInYears = floor($ageInSeconds / (60 * 60 * 24 * 365.25)); // 使用365.25考虑到闰年

                                                if($ageInYears){
                                                    foreach ($zi_tag as $vt) {
                                                        if ($ageInYears >= $vt['age_type_start'] && $ageInYears <= $vt['age_type_end']) {
                                                            if($vt['id'] != $vv) {
                                                                unset($tags[$kk]);
                                                                $tags[] = $vt['id'];
                                                                break;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            //更新标签
                            Db::name('member')->where('aid',$aid)->where('id',$v['id'])->update(['tags' => implode(',',$tags)]);
                        }
                    }
                }
            }
        }
    }

    //国补回退资格
    private function backQualification(){
        if(getcustom('product_chinaums_subsidy')){
            $list = Db::name('chinaums_subsidy_apply')->where('status',1)->select()->toArray();
            $closeSet = [];
            foreach ($list as $key => $val){
                if(!$closeSet[$val['aid']]){
                    $closeSet[$val['aid']] = Db::name('chinaums_subsidy_set')->where('aid',$val['aid'])->value('back_expire_duration');
                }
                if($val['createtime'] + $closeSet[$val['aid']]*60 > time()) continue;

                $subsidy = new \app\custom\ChinaumsSubsidy($val['aid']);
                $res = $subsidy->qualificationAlter($val);
                if($res['respCode'] != '000000'){
                    Log::write('资格回退失败：【id:'.$val['id'].'】');
                    continue;
                }
                Db::name('chinaums_subsidy_apply')->where('id',$val['id'])->update(['status'=>0,'auth_code' => '']);
            }
        }
    }

    //查询商户进件状态
    private function saveJinjianStatus(){
        if(getcustom('mobile_business_jinjian')){
            try {
                $pageSize = 100;
                $page = 1;
                $notStatus = ['99','APPLYMENT_STATE_FINISHED'];
                do {
                    $list = Db::name('business_apply_jinjian')->where('status','not in',$notStatus)->page($page, $pageSize)->select()->toarray();
                    foreach ($list as $record) {
                        Db::name('business_apply_jinjian')->lock(true)->find($record['id']);
                        if(empty($record['applyment_id'])) continue;
                        try {
                            if($record['type'] == 1){
                                // 微信进件
                                $wxApplyment = new \app\custom\WxpayJinjian();
                                $resAppinfo = $wxApplyment->getappinfo($record['aid']);
                                if(!isset($resAppinfo['status'])){
                                    $wxRes = $wxApplyment->queryApplyment($record['applyment_id']);
                                    if(isset($wxRes['applyment_id'])){
                                        $statusMap = [
                                            'APPLYMENT_STATE_EDITTING' => '编辑中',
                                            'APPLYMENT_STATE_AUDITING' => '审核中',
                                            'APPLYMENT_STATE_REJECTED' => '已驳回',
                                            'APPLYMENT_STATE_TO_BE_CONFIRMED' => '待账户验证',
                                            'APPLYMENT_STATE_TO_BE_SIGNED' => '待签约',
                                            'APPLYMENT_STATE_SIGNING' => '开通权限中',
                                            'APPLYMENT_STATE_FINISHED' => '已完成',
                                            'APPLYMENT_STATE_CANCELED' => '已作废',
                                        ];

                                        $update = [
                                            'mchid_id' => $wxRes['sub_mchid'] ?? '',
                                            'status' => $wxRes['applyment_state'] ?? '',
                                            'status_msg' => $statusMap[$wxRes['applyment_state']] ?? '',
                                            'reason' => isset($wxRes['audit_detail']) ? implode(';', array_column($wxRes['audit_detail'], 'reject_reason')) : ''
                                        ];

                                        if(empty($record['sign_url_qrcode']) && !empty($wxRes['sign_url'])){
                                            $update['sign_url_qrcode'] = createqrcode($wxRes['sign_url'], '', $record['aid']);
                                            $update['sign_url'] = $wxRes['sign_url'];
                                        }

                                        //绑定商户号
                                        if($wxRes['applyment_state'] == 'APPLYMENT_STATE_FINISHED' && $record['bid'] > 0){
                                            Db::name('business')->where('id', $record['bid'])->update([
                                                'wxpayst' => 1,
                                                'wxpay_submchid' => $wxRes['sub_mchid']
                                            ]);
                                        }

                                        Db::name('business_apply_jinjian')->where('id',$record['id'])->update($update);
                                    }
                                }
                            }elseif($record['type'] == 2){
                                // 支付宝进件
                                $aliRes = \app\custom\AlipayJinjian::queryApplyment($record['aid'], $record['applyment_id']);
                                if($aliRes['status'] == 0) continue;

                                $obj = $aliRes['data'] ?? [];
                                $statusMap = [
                                    'CREATE' => '已发起二级商户确认',
                                    'SKIP' => '无需确认',
                                    'FAIL' => '签约失败',
                                    'NOT_CONFIRM' => '商户未确认',
                                    'FINISH' => '签约完成'
                                ];

                                $update = [
                                    'mchid_id' => $obj->smid ?? '',
                                    'status' => $obj->status ?? '',
                                    'status_msg' => $statusMap[$obj->sub_confirm] ?? '',
                                    'reason' => implode(';', array_filter([
                                        $obj->kf_audit_memo ?? '',
                                        $obj->kz_audit_memo ?? '',
                                        $obj->reason ?? ''
                                    ]))
                                ];

                                if(empty($record['sign_url_qrcode']) && isset($obj->sub_sign_qr_code_url)){
                                    $update['sign_url_qrcode'] = createqrcode($obj->sub_sign_qr_code_url, '', $record['aid']);
                                    $update['sign_url'] = $obj->sub_sign_qr_code_url;
                                }

                                Db::name('business_apply_jinjian')->where('id',$record['id'])->update($update);
                            }
                        } catch (\Exception $e) {
                            Log::write("进件处理异常[ID:{$record['id']}]: ".$e->getMessage());
                            continue;
                        }
                    }
                    $page++;
                } while(!empty($list));
            } catch (\Exception $e) {
                Log::write("进件状态查询全局异常: ".$e->getMessage());
            }
        }
    }

    //释放订单预备金到奖金池 独立执行的计划任务
    public function release_reserves(){
        //释放订单预备金到奖金池
        if(getcustom('green_score_reserves')){
            $syssetlist = Db::name('consumer_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset) {
                if($sysset['reserves_to_pool']>0 && $sysset['reserves_total']>0){
                    $res = \app\custom\GreenScore::reserves_to_bonus($sysset['aid'], $sysset);
                }
            }
            dump('释放完成');
        }
    }

    //关闭后台短信登录
    public function closeSmsLogin(){
        if(getcustom('admin_login_sms_verify')){
            $config = include(ROOT_PATH.'config.php');
            set_time_limit(0);
            ini_set('memory_limit', -1);
            if(input('param.key')!=$config['authtoken']) die('error');
            try {
                $webinfo = Db::name('sysset')->where(['name'=>'webinfo'])->value('value');
                $webinfo = json_decode($webinfo,true);
                if(!isset($webinfo['sms_login'])){
                    echo'error';
                    die();
                }
                $webinfo['sms_login'] = 0; //关闭
                $jsonWebinfo = json_encode($webinfo);
                $res = Db::name('sysset')->where(['name'=>'webinfo'])->update(['value'=>$jsonWebinfo]);
                if($res === false){
                    throw new Exception('数据库更新失败');
                }
                echo 'success';
            } catch (\Exception $e) {
                // 发生异常时输出错误信息
                echo 'Error: '. $e->getMessage();
            }
        }
    }
    //会员关系解绑
    private function memberPidReset(){
        if(getcustom('member_pid_reset')){
            $aids = Db::name('admin_set')->where('member_pid_reset',1)->column('aid');
            foreach ($aids as $aid) {
                Db::name('member')->where('aid',$aid)->update(['pid'=>0,'pid_origin'=>'']);
            }
        }
    }
    //购物返现 每天释放
    private function cashbackEverydayRelease(){
        if(getcustom('yx_cashback_addup_return')){
            $syssetlist = Db::name('admin_set')->where('1=1')->field('id,aid')->select()->toArray();
//           $syssetlist = Db::name('admin_set')->where('aid',32)->field('id,aid')->select()->toArray();
            foreach($syssetlist as $sysset) {
                $aid = $sysset['aid'];
                //每日释放待返金额
                $cashback_sysset = Db::name('cashback_sysset')->where('aid',$aid)->find();
                $cashback_start_money =  $cashback_sysset['start_money'];
                $cashback_everyday_ratio = $cashback_sysset['everyday_ratio'];
				$cashback_everyday_ratio_data = [];
				$recycleType = 1; //1 先发放后回收 2 先回收后发放
				if(getcustom('yx_cashback_category_addup_return')){
					if($cashback_sysset['send_condition']){
						$cashback_everyday_ratio_data = $cashback_sysset['everyday_ratio_data'] ? json_decode($cashback_sysset['everyday_ratio_data'], true) : [];
					}
					$recycleType = $cashback_sysset['back_send_sort'];
				}
				if($recycleType == 2){
					$this->cashbackUnclaimedRecycle($aid);
				}
                if($cashback_everyday_ratio > 0 || $cashback_everyday_ratio_data){
                    $addup_list = Db::name('cashback_addup')->where('aid',$aid)->where('back_price','>',$cashback_start_money)->select()->toArray();
                    if($addup_list){
                        foreach ($addup_list as $key=>$addup){
							if(getcustom('yx_cashback_category_addup_return')){
								if($cashback_everyday_ratio_data){
									$levelid = Db::name('member')->where('id',$addup['mid'])->value('levelid');
									$levelRatio = $cashback_everyday_ratio_data[$levelid] ?? 0;
									if ($levelRatio > 0) {
										$cashback_everyday_ratio = $levelRatio;
									} elseif ($cashback_everyday_ratio <= 0) {
										continue;
									}
								}
							}
                            $weishu = 2;
                            if(getcustom('yx_cashback_everyday_weishu',$aid)){
                                $weishu = 5;
                            }
                            $back_money =  dd_money_format($addup['back_price'] *  $cashback_everyday_ratio * 0.01,$weishu);
                            //到停止线的数值
                            $diff_money = dd_money_format($addup['back_price']-$back_money,$weishu);//100-20 80
                            if($diff_money < $cashback_start_money){
                                $back_money =  dd_money_format( $addup['back_price']-  $cashback_start_money,$weishu);
                            }
                            $money_weishu = 2;
                            if($cashback_sysset['back_type'] ==1){
                                if(getcustom('member_money_weishu',$aid)){
                                    $money_weishu = Db::name('admin_set')->where('aid',$aid)->value('member_money_weishu');
                                }
                            } else if($cashback_sysset['back_type'] == 2){
                                if(getcustom('fenhong_money_weishu',$aid)){
                                    $money_weishu = Db::name('admin_set')->where('aid',$aid)->value('fenhong_money_weishu');
                                }
                            } else if($cashback_sysset['back_type'] == 3){
                                $money_weishu = 0;
                                if(getcustom('score_weishu',$aid)){
                                    $money_weishu = Db::name('admin_set')->where('aid',$aid)->value('score_weishu');
                                }
                            }
                            $back_money = dd_money_format($back_money,$money_weishu);

                            if($back_money > 0){
                                $insert = [
                                    'aid' => $aid,
                                    'mid' => $addup['mid'],
                                    'back_price' => $addup['back_price'],
                                    'ratio' => $cashback_everyday_ratio,
                                    'money' => $back_money,
                                    'status' => 0,
                                    'createtime' => time()
                                ];
                                Db::name('cashback_addup_record')->insert($insert);
                                //待返金额变动记录
                                \app\custom\OrderCustom::cashbackAddup($aid,$addup['mid'],0,$back_money * -1,'每日释放',$weishu);
                            }
                        }
                    }
                }
				if($recycleType == 1){
					$this->cashbackUnclaimedRecycle($aid);
				}
            }
        }
    }
    //叠加记录 过期
	private function cashbackUnclaimedRecycle($aid){
		if(getcustom('yx_cashback_addup_return')){
			//每日过期未领取的，一天一执行，今天之前的不领取的过期
			$todaytime = strtotime(date('Y-m-d 00:00:01',time()));
			$record_list = Db::name('cashback_addup_record')->where('aid',$aid)->where('status',0)->where('createtime','<',$todaytime)->where('type',0)->select()->toArray();
			if($record_list){
				foreach ($record_list as $rkey=>$record){
					//设置过期
					Db::name('cashback_addup_record')->where('id',$record['id'])->update(['expiretime' => time(),'status' => 2]);
					//返还待返金额
					\app\custom\OrderCustom::cashbackAddup($aid,$record['mid'],0,$record['money'],'过期返还');
				}
			}
			//类型是自定义时间返回时
            $record_list2 =  Db::name('cashback_addup_record')->where('aid',$aid)->where('status',0)->where('createtime','<',$todaytime)->where('type',1)->select()->toArray();
            if($record_list2){
                foreach ($record_list2 as $rkey2=>$record2){
                    //设置过期
                    Db::name('cashback_addup_record')->where('id',$record2['id'])->update(['expiretime' => time(),'status' => 2]);
                    //设置过期，然后再新增一条新的 2025-12-23客户修改
                    $new_record = [
                        'aid' => $record2['aid'],
                        'mid' => $record2['mid'],
                        'back_price' =>$record2['back_price'],
                        'ratio' => $record2['ratio'],
                        'money' => $record2['money'],
                        'type' => $record2['type'],
                        'back_type' => $record2['back_type'],
                        'status' => 0,
                        'createtime' => time()
                    ];
                    Db::name('cashback_addup_record')->insert($new_record);
                }
            }
		}
	}

    public function dealBusinessExpert(){
        if(getcustom('business_expert')){
            $admin = Db::name('admin')->where('status',1)->field('id')->select()->toArray();
            if($admin){
                foreach($admin as $v){
                    $aid = $v['id'];
                    //查询商家设置
                    $bsysset = Db::name('business_expert_sysset')->where('aid',$aid)->find();
                    //不存在，或未开启则跳过
                    if(!$bsysset || $bsysset['status'] == 0) continue;
                    //查询有效的达人
                    $experts = Db::name('business_expert')->where('aid',$aid)->where('bid','>',0)->where('status',1)->select()->toArray();

                    if($experts){
                        //当前月份时间
                        $nowmonth = strtotime(date("Y-m"));
                        //上个月份时间
                        $beforemonth = strtotime('-1 month',$nowmonth);
                        //当前月份几号
                        $nowmonthday = date('d');
                        //当前时间
                        $nowdaytime = strtotime(date("Y-m-d"));
                        //昨天时间
                        $yesterdaytime = strtotime('-1 day',$nowdaytime);
                        foreach($experts as $ev){
                            //查询商户及商户抽成比例
                            $business = Db::name('business')->where('id',$ev['bid'])->field('id,name,status,feepercent')->find();
                            if(!$business || $business['status'] !=1) continue;

                            //计算奖励
                            if($business['feepercent']>0){
                                //统计商家昨天商城确认收货订单数量
                                $totalprice = Db::name('shop_order')->where('bid',$ev['bid'])->where('collect_time','>=',$yesterdaytime)->where('collect_time','<',$nowdaytime)->where('status',3)->where('aid',$aid)->sum('totalprice');
                                $totalprice = round($totalprice,2);
                                //统计商家昨天买单订单金额数量
                                $paymoney = Db::name('maidan_order')->where('bid',$ev['bid'])->where('paytime','>=',$yesterdaytime)->where('paytime','<',$nowdaytime)->where('status',1)->where('aid',$aid)->sum('paymoney');
                                $paymoney = round($paymoney,2);
                                $orderprice = $totalprice + $paymoney;
                                if($orderprice>0){

                                    //买单商户费率金额
                                    $feepercentmoney = $orderprice * $business['feepercent'] * 0.01;
                                    if($feepercentmoney>0){
                                        $rewardradio = 0;//奖励比例
                                        //若订单金额小于等于底于最低金额比例，则直接使用此比例
                                        if($orderprice<=$bsysset['minfullmoney']){
                                            $rewardradio = $bsysset['minreward'];
                                        //若订单金额大于底于最高金额比例，则直接使用此比例
                                        }else if($orderprice>$bsysset['maxfullmoney']){
                                            $rewardradio = $bsysset['maxreward'];
                                        }else{
                                            //查看中间比例设置
                                            $rewarddata= $bsysset['rewarddata']?json_decode($bsysset['rewarddata'],true):'';
                                            if($rewarddata){
                                                foreach($rewarddata as $rv){
                                                    if($orderprice>$rv['minmoney']){
                                                        $rewardradio = $rv['reward'];
                                                    }
                                                }
                                                unset($rv);
                                            }
                                        }
                                        if($rewardradio>0){
                                            $commission = $feepercentmoney * $rewardradio * 0.01;
                                            if($commission>0){
                                                //增加到他的佣金中
                                                \app\common\Member::addcommission($aid,$ev['mid'],0,$commission,'来自'.$business['name'].'商户达人奖励',1,'business_expert',0,0,$ev['id']);
                                            }
                                        }
                                    }
                                }
                            }

                            //统计当月营业额和当月成交数量
                            if($nowmonthday == '01'){
                                $premonth = $beforemonth;
                                $where = [];
                                $where[] = ['collect_time','>=',$beforemonth];
                                $where[] = ['collect_time','<',$nowmonth];
                                $where2 = [];
                                $where2[] = ['paytime','>=',$beforemonth];
                                $where2[] = ['paytime','<',$nowmonth];
                            }else{
                                $premonth = $nowmonth;
                                $where = [];
                                $where[] = ['collect_time','>=',$nowmonth];
                                $where2 = [];
                                $where2[] = ['paytime','>=',$nowmonth];
                            }
                            //统计当月营业额
                            $month_totalprice = Db::name('shop_order')->where('bid',$ev['bid'])->where($where)->where('status',3)->where('aid',$aid)->sum('totalprice');
                            $month_totalprice = round($month_totalprice,2);
                            $month_paymoney = Db::name('maidan_order')->where('bid',$ev['bid'])->where($where2)->where('status',1)->where('aid',$aid)->sum('paymoney');
                            $month_paymoney = round($month_paymoney,2);
                            $premonth_totalprice = $month_totalprice + $month_paymoney;

                            //统计当月成交数量
                            $month_countshop = Db::name('shop_order')->where('bid',$ev['bid'])->where($where)->where('status',3)->where('aid',$aid)->count('id');
                            $month_countmaidan = Db::name('maidan_order')->where('bid',$ev['bid'])->where($where2)->where('status',1)->where('aid',$aid)->count('id');
                            $premonth_num = $month_countshop + $month_countmaidan;
                            Db::name('business_expert')->where('id',$ev['id'])->where('status','>',0)->update(['premonth_totalprice'=>$premonth_totalprice,'premonth_num'=>$premonth_num,'premonth'=>$premonth]);

                            //若是每月1号，则统计商户达人绑定的商户订单是否满足有效期设置
                            if($nowmonthday == '01' && $bsysset['ordernum']>0){
                                //商户达人审核成功时间月份，统计上个月之前审核的达人
                                $checkmonth = strtotime(date("Y-m",$ev['checktime']));
                                if($checkmonth>= $beforemonth) continue;

                                //统计商家商城确认收货订单数量
                                $countshop = Db::name('shop_order')->where('bid',$ev['bid'])->where('collect_time','>=',$beforemonth)->where('collect_time','<',$nowmonth)->where('status',3)->where('aid',$aid)->count('id');
                                //统计买单订单金额数量
                                $countmaidan = Db::name('maidan_order')->where('bid',$ev['bid'])->where('paytime','>=',$beforemonth)->where('paytime','<',$nowmonth)->where('status',1)->where('aid',$aid)->count('id');
                                $ordernum = $countshop + $countmaidan;
                                //若不满足订单数量要求，则解绑
                                if($ordernum<$bsysset['ordernum']){
                                    $remark = '该商户上月完成'.$ordernum.'单订单，小于每月完成'.$bsysset['ordernum'].'单订单的条件要求';
                                    $rs = Db::name('business_expert')->where('id',$ev['id'])->where('status','>',0)->update(['status'=>-2,'expiredtime'=>time(),'expiredremark'=>$remark]);
                                    if($rs){
                                        Db::name('business')->where('id',$ev['bid'])->update(['expertid'=>0]);
                                    }
                                }
                            }
                        }
                        unset($ev);
                    }
                }
            }
        }
    }

    //倍增返现独立计划任务入口
    public function cashbackMultiply(){
        if(getcustom('yx_cashback_multiply')){
            //文件锁，防止并发执行
            $file_name = ROOT_PATH.'runtime/plan_cashback_multiply.log';
            if(file_exists($file_name)){
                file_put_contents($file_name,date('Y-m-d H:i:s').'任务重复'."\r\n",FILE_APPEND);
                return json_encode(['status'=>1,'msg'=>'重复执行']);
            }else{
                file_put_contents($file_name,date('Y-m-d H:i:s').'任务开始'."\r\n",FILE_APPEND);
            }
            //倍增返现，每分钟执行一次
            try {
                \app\custom\OrderCustom::deal_autocashback_multiply();
            }catch (\Exception $e) {
                // 请求失败
                unlink($file_name);
            }
            //执行完成删除锁文件
            unlink($file_name);
            return json_encode(['status'=>1,'msg'=>'执行成功']);
        }
    }

	//美团定时订单
    private function meituanOrder(){
		if(getcustom('meituan_xinyoujie')) {
			$now    = date('Y-m-d H:i:s');
			$orders = Db::name('meituan_order')->where('pay_end_date', '<', $now)->where('status', 1)->select()->toArray();
			foreach ($orders as $order) {
				$mt  = new \app\custom\Meituan($order['aid']);
				$res = $mt->cancelOrder($order['order_info_id']);
				if ($res['status'] == 1) {
					Db::name('meituan_order')->where('id', $order['id'])->update(['status' => -1]);
				} else {
					Log::write([
						'title'   => '美团订单取消失败',
						'message' => $res['msg'],
						'order'   => $order['id']
					]);
				}
			}
		}
	}

	//推荐商家奖励 是否达到奖励条件
    private function recommendApplyBusiness(){
		if(getcustom('member_recommend_apply_business')){
            $currentTime = time();
            $activity = Db::name('recommend_apply_business')->where('status',1)->where('start_time', '<=', $currentTime)->where('end_time', '>=', $currentTime)->select()->toArray();
            $independent = [];
			foreach ($activity as $key => $val){
                if (empty($val['start_time']) && empty($val['open_days'])) {
                    writeLog($val['aid'].'：未设置活动时间','recommend_apply_business');
                    continue;
                }
				if(getcustom('business_agent_referrer_independent')){
					if(!$independent[$val['aid']]){
						$independent[$val['aid']] = Db::name('business_sysset')->where('aid',$val['aid'])->value('referrer_independent');
					}
				}
                if(empty($val['levelids'])) {
                    writeLog($val['aid'].'：未设置参与等级','recommend_apply_business');
                    continue;
                }
                $levelData = explode(',',$val['levelids']);

				//查询符合条件的商户
                $businessList = Db::name('business')->where('aid',$val['aid'])->where('createtime', '>=', $val['start_time'])->where('createtime','<=',$val['end_time'])->where('status',1)->select()->toArray();
                foreach ($businessList as $business) {
					//查看是否发放
					$log = Db::name('recommend_apply_business_log')->where('aid',$business['aid'])->where('invite_bid',$business['id'])->where('condition',2)->find();
					if($log) {
                        writeLog($val['aid'].'：商家'.$business['id'].'已发放奖励,id：'.$log['id'],'recommend_apply_business');
                        continue;
                    }
                    //判断上级是否符合条件
                    $record = Db::name('recommend_apply_business_log')->where('aid',$business['aid'])->where('invite_bid',$business['id'])->where('condition','in',[0,1])->find();
                    if(empty($record)){
                        writeLog($val['aid'].'：商家'.$business['id'].'未找记录','recommend_apply_business');
                        continue;
                    }
                    if(!in_array($record['pid_levelid'],$levelData)) {
                        writeLog($val['aid'].'：商家'.$business['id'].'推荐商家等级'.$record['pid_levelid'].'不在参与等级中','recommend_apply_business');
                        continue;
                    }

                    //统计商户收款金
                    $where   = [];
                    $where[] = ['aid', '=', $business['aid']];
                    $where[] = ['bid', '=', $business['id']];
                    $where[] = ['paytime', '>=', $val['start_time']];
                    if ($val['open_days'] > 0) {
                        //如果设置的天数 超过结束日期 则以结束日期为准
                        if($val['start_time'] + ($val['open_days'] * 86400) > $val['end_time']){
                            $where[] = ['paytime', '<=', $val['end_time']];
                        }else{
                            $where[] = ['paytime', '<=', $val['start_time'] + ($val['open_days'] * 86400)];
                        }
                    }
                    $shop_order_money   = Db::name('shop_order')->where($where)->where('status', 'in', [1, 2, 3])->sum('totalprice');
                    $maidan_order_money = Db::name('maidan_order')->where($where)->where('status', 1)->sum('paymoney');
                    $totalMoney         = $shop_order_money + $maidan_order_money;
                    if ($totalMoney >= $val['receive_pay']) {
                        //达到收款额度发放奖励
                        \app\common\Member::addcommission($business['aid'], $record['mid'],$business['mid'], $val['reward_money'], '推荐商家（' . $business['id'] . '）奖励');
                        Db::name('recommend_apply_business_log')->insert([
                            'aid'         => $business['aid'],
                            'mid'         => $record['mid'],
                            'invite_bid'  => $business['id'],
                            'invite_mid'  => $business['mid'],
                            'money'       => $val['reward_money'],
                            'condition'   => 2,//邀请成为商家
                            'pid_levelid' => $record['pid_levelid'],
                            'createtime'  => time(),
                        ]);
                    }
				}
			}
		}
	}
	
	private function moneySendHongbaoExpire(){
        if(getcustom('yx_money_send_hongbao')){
            $hongbaolist = Db::name('money_send_hongbao')->where('status',0)->where('expire_time','<',time())->limit(100)->select()->toArray();
      
            foreach ($hongbaolist as $key=>$hongbao){
                $sy_money = Db::name('money_send_hongbao_log')->where('aid',$hongbao['aid'])->where('mid',$hongbao['mid'])->where('hbid',$hongbao['id'])->where('status',0)->sum('money');
                //返还金额
                \app\common\Member::addmoney($hongbao['aid'],$hongbao['mid'],$sy_money ,t('余额').'发红包过期返还');
                //修改红包状态
                Db::name('money_send_hongbao')->where('aid',$hongbao['aid'])->where('mid',$hongbao['mid'])->where('id',$hongbao['id'])->update(['cancel_money' => $sy_money,'cancel_time' => time(),'status' => 2]);
                //修改红包记录状态
                Db::name('money_send_hongbao_log')->where('aid',$hongbao['aid'])->where('mid',$hongbao['mid'])->where('hbid',$hongbao['id'])->update(['status' => 2]);
            }
        }
    }

    //消费补贴释放 独立执行的计划任务
    public function dealSubsidy(){
        if(getcustom('yx_buyer_subsidy')){
            set_time_limit(0);
            ini_set('memory_limit','1024M');
            $admin = Db::name('subsidy_set')->where('status',1)->field('aid')->select()->toArray();
            if($admin){
                foreach($admin as $v){
                    $aid = $v['aid'];
                    Db::startTrans();
                    $res = \app\custom\Subsidy::caclBonus($aid,0);
                    Db::commit();
                }
            }
        }
    }

    public function fenhongMaxAddBuymoney(){
        $money_dec = getcustom('money_dec');
        if(getcustom('fenhong_max') && getcustom('fenhong_max_buymoney')){
            $aids = Db::name('admin')->where('status',1)->column('id');
            $field = 'id,mid,totalprice';
            if($money_dec){
                $field .= ',dec_money';
            }
            foreach ($aids as $aid) {
                $sysset_custom = Db::name('admin_set_custom')->where('aid',$aid)->field('fenhong_max_buymoney')->find();
                $orderlist = Db::name('shop_order')->where('aid',$aid)->where('fenhong_max_add_buymoney',0)->where('status','in',[1,2,3])->field($field)->select()->toArray();
                if($orderlist && $sysset_custom['fenhong_max_buymoney'] == 1){
                    foreach ($orderlist as $order) {
                        $user = Db::name('member')->where('aid',$aid)->where('id',$order['mid'])->field('id,fenhong_max')->find();
                        if($user){
                            $fenhong_max = bcadd($user['fenhong_max'],$order['totalprice'],2);
                            if($money_dec && $order['dec_money'] > 0){
                                $fenhong_max = bcadd($fenhong_max,$order['dec_money'],2);
                            }
                            Db::name('member')->where('aid',$aid)->where('id',$user['id'])->update(['fenhong_max' => $fenhong_max]);
                            Db::name('shop_order')->where('aid',$aid)->where('id',$order['id'])->update(['fenhong_max_add_buymoney' => 1]);
                        }
                    }
                }
            }
        }
    }

    private function renshureward($aid=0,$s_time='',$e_time=''){
        if(getcustom('yx_offline_subsidies')){
            if($aid){
                $aids = Db::name('admin')->where('id',$aid)->where('status',1)->column('id');
            }else{
                $aids = Db::name('admin')->where('status',1)->column('id');
            }

            if($s_time){
                //获取上个月开始和结束时间戳
                $firstDayLastMonth = strtotime($s_time);
                $lastDayLastMonth = strtotime($e_time);
            }else{
                //获取上个月开始和结束时间戳
                $firstDayLastMonth = strtotime('first day of last month 00:00:00');
                $lastDayLastMonth = strtotime('last day of last month 23:59:59');
            }

//            var_dump($aid);
//            var_dump($s_time);
//            var_dump($e_time);

            foreach ($aids as $aid) {
                $set = Db::name('offline_subsidies_set')->where('aid',$aid)->find();
                if(!$set || !$set['status'] || ($set['effective_ratio'] <= 0)){
                    continue;
                }

                $productids_arr = explode(',',$set['productids']);
                //有效比例
                $effective_ratio = $set['effective_ratio'] / 100;

                $scene_arr = explode(',',$set['scene']);

                //先查出所有有下级的用户
                $p_memer_arr = Db::name('member')->where('aid',$aid)->where('pid','>',0)->group('pid')->column('pid');
                if($p_memer_arr){
                    //循环每一个下级
                    foreach ($p_memer_arr as $p_memer_id){

                        //下级订单总数
                        $p_xia_order_num = 0;

                        $ordermoney = 0;

                        //查询每一个下级
                        $xiaj_member_arr = Db::name('member')->where('aid',$aid)->where('pid',$p_memer_id)->column('id');
                        if($xiaj_member_arr){

                            foreach ($xiaj_member_arr as $xiaj_member_id){

                                //每个用户是否下单
                                $mgyhxiad = 0;

                                //每一个用户上月累计消费数量
                                if(in_array('shop',$scene_arr)){
                                    //计算商城消费
                                    $og_order = Db::name('shop_order_goods')->where('aid',$aid)->where('mid',$xiaj_member_id)->where('createtime','between',[$firstDayLastMonth,$lastDayLastMonth])->where('status','in',[1,2,3])->select()->toArray();
                                    if($og_order){
                                        foreach ($og_order as $og){
                                            if($set['fwtype'] == 0 || in_array($og['proid'],$productids_arr)){
                                                $mgyhxiad = 1;
                                                if($set['base'] == 0){
                                                    //成交金额
                                                    $ordermoney += $og['real_totalmoney'];
                                                }else{
                                                    //成交利润
                                                    $ordermoney += $og['real_totalmoney'] - $og['cost_price'] * $og['num'];
                                                }
                                            }
                                        }
                                    }

                                }
                                if(in_array('maidan',$scene_arr)){
                                    //计算买单消费
                                    $maidan_order = Db::name('maidan_order')->where('aid',$aid)->where('mid',$xiaj_member_id)->where('createtime','between',[$firstDayLastMonth,$lastDayLastMonth])->where('status','in',[1,2,3])->select()->toArray();
                                    if($maidan_order) {
                                        $mgyhxiad = 1;
                                        foreach ($maidan_order as $order) {
                                            if($set['base'] == 0){
                                                //成交金额
                                                $ordermoney += $order['paymoney'];
                                            }else{
                                                //成交利润
                                                $ordermoney += $order['paymoney'] - $order['cost_price'];
                                            }
                                        }
                                    }
                                }

                                $p_xia_order_num += $mgyhxiad;
                            }
                        }

                        $renshureward_lj_ratio = 0;
                        //取出符合累计业绩的阶梯配置
                        $renshureward_limit_arr = json_decode($set['renshureward_limit'],true);
                        if($renshureward_limit_arr){
                            //把数组按照奖励最大的排序
                            usort($renshureward_limit_arr, function($a, $b) {
                                if ($a['renshureward_lj'] == $b['renshureward_lj']) return 0;
                                return ($a['renshureward_lj'] > $b['renshureward_lj']) ? -1 : 1;
                            });
                            foreach ($renshureward_limit_arr as $vg){
                                if($vg['renshureward_lj'] && $vg['renshureward_lj_ratio'] && $p_xia_order_num >= $vg['renshureward_lj']){
                                    $renshureward_lj_ratio = $vg['renshureward_lj_ratio'];
                                    break;
                                }
                            }
                        }

                        $gs_firstDayLastMonth = date('Y-m-d H:i:s', $firstDayLastMonth);
                        $gs_lastDayLastMonth = date('Y-m-d H:i:s', $lastDayLastMonth);

                        //如果在阶梯配置中取到比例 才发奖励
                        if($renshureward_lj_ratio > 0) {
                            $renshureward_lj_ratio_gs = $renshureward_lj_ratio / 100;

                            //计算业绩奖励
                            $money = round(bcmul(bcmul(bcmul($ordermoney, $effective_ratio, 3), $set['renshureward_ratio'] / 100, 3), $renshureward_lj_ratio_gs, 3), 2);

                            $remark = $gs_firstDayLastMonth.'至'.$gs_lastDayLastMonth.'下级有'.$p_xia_order_num.'人共消费：'.$ordermoney.' x '.$set['renshureward_text'].'比例：'.$set['renshureward_ratio'].'% x阶梯比例：'.$renshureward_lj_ratio.'% x 有效比例：'.$set['effective_ratio'].'%的'.$set['renshureward_text'].'：'.$money;

                            //判断是否发放过
                            $ifff = Db::name('member_offline_subsidies_log')->where('aid',$aid)->where('mid',$p_memer_id)->where('type',7)->where('scene',$set['scene'])->where('ordernum',$gs_firstDayLastMonth)->find();

                            if($money > 0 && !$ifff){
                            //if($money > 0 ){

                                \app\common\Member::addcommission($aid,$p_memer_id,0,$money,$remark,1,'offline_subsidies');

                                $data = array(
                                    'aid' => $aid,
                                    'mid' => $p_memer_id,
                                    'frommid' => 0,
                                    'orderid' => 0,
                                    'ordernum' => $gs_firstDayLastMonth,
                                    'scene' => $set['scene'],
                                    'type' => 7,
                                    'commission' => $money,
                                    'remark' => $remark,
                                    'createtime' => time(),
                                    'status' => 1,
                                    'order_money' => $ordermoney,
                                    'offline_subsidies_set' => jsonEncode($set)
                                );
                                Db::name('member_offline_subsidies_log')->insertGetId($data);
                            }
                        }
                    }
                }
            }
        }
    }

    private function waterHappytiAutoOrder(){
        if(getcustom('water_happy_ti')){
            $aids = Db::name('admin')->where('status',1)->column('id');

            foreach ($aids as $aid){

                //处理退款订单退消费赠积分
                //获取5分钟之前的时间
                $old3_time = time() - 5 * 60;

//                $order1 = Db::name('water_happyti_order')->where('aid',$aid)->where('status',4)->where('scoreinlog',0)->where('paytime','<',$old3_time)->select()->toArray();
//                //var_dump(Db::name('water_happyti_order')->getLastSql());
//                if($order1) {
//
//                    foreach ($order1 as $v) {
//                        //扣除消费赠送积分
//                        \app\common\Member::decscorein($aid,'water_happyti',$v['id'],$v['ordernum'],'打水订单退款扣除消费赠送');
//                            //订单处理
//                            Db::name('water_happyti_order')->where('aid',$aid)->where('id',$v['id'])->update([
//                                'scoreinlog' => 1,
//                            ]);
//                    }
//                }


                //处理未支付订单退优惠券
                $order2 = Db::name('water_happyti_order')->where('aid',$aid)->where('status',0)->where('coupon_rid','>',0)->where('createtime','<',$old3_time)->select()->toArray();
                //var_dump(Db::name('water_happyti_order')->getLastSql());
                if($order2) {
                    //查询后台是否开启退还已使用的优惠券
                    $return_coupon = Db::name('shop_sysset')->where('aid',$aid)->value('return_coupon');
                    foreach ($order2 as $v) {

                        //优惠券退还
                        if($return_coupon && $v['coupon_rid'] > 0){
                            \app\common\Coupon::refundCoupon($aid,$v['mid'],$v['coupon_rid'],$v);
                            //订单处理
                            Db::name('water_happyti_order')->where('aid',$aid)->where('id',$v['id'])->update([
                                'coupon_rid' => 0,
                            ]);
                        }
                    }
                }

                //获取五分钟之前的时间
                //$old_time = time() - 5 * 60;

                //查询待处理订单
                $order = Db::name('water_happyti_order')->where('aid',$aid)->where('iscd',0)->where('status',1)->where('paytime','>',0)->where('paytime','<',$old3_time)->select()->toArray();
                //var_dump(Db::name('water_happyti_order')->getLastSql());
                if($order){

                    foreach ($order as $v){

                        try {
                            $waterHappyti_new = new \app\custom\WaterHappyti($v['aid'],$v['bid']);
                        }catch (\Exception $e){
                            //var_dump($e->getMessage());
                            continue;
                        }

                        //查询订单
                        $res = $waterHappyti_new->queryorder([
                            'ordernum'=>$v['ordernum'],
                            'deviceId'=>$v['deviceId'],
                        ]);

                        if($res['status'] == 1){
                            $detail = $res['data'];

                            //判断是否要退款
                            if($detail['balance'] && $detail['balance'] > 0){

                                //已使用金额 = 打水金额 - 未消费金额
                                $shiy = bcsub($v['product_price'],$detail['balance'],2);

                                //退款 = 实付金额 - 已使用金额
                                $shengy = bcsub($v['totalprice'],$shiy,2);

                                //退款
                                if($shengy > 0){
                                    //退款
                                    $rs = \app\common\Order::refund($v,$shengy,'打水订单未使用金额退款');
                                    if($rs['status']==1){
                                        Db::name('water_happyti_order')->where('id',$v['id'])->where('aid',$v['aid'])->where('bid',$v['bid'])->update(['refund_money' => $shengy,'refund_time' => time()]);

//                                        //积分抵扣的返还
//                                        if($order['scoredkscore'] > 0){
//                                            \app\common\Member::addscore($aid,$order['mid'],$order['scoredkscore'],'打水订单退款返还');
//                                        }
//                                        //扣除消费赠送积分
//                                        \app\common\Member::decscorein($aid,'water_happyti',$order['id'],$order['ordernum'],'打水订单退款扣除消费赠送');
//
//                                         //查询后台是否开启退还已使用的优惠券
//                                         $return_coupon = Db::name('shop_sysset')->where('aid',$aid)->value('return_coupon');
//
//                                        //优惠券退还
//                                        if($order['coupon_rid'] > 0){
//                                            \app\common\Coupon::refundCoupon($aid,$order['mid'],$order['coupon_rid'],$order);
//                                        }
                                    }
                                }
                            }

                            //发货信息录入 微信小程序+微信支付
                            if($v['platform'] == 'wx' && $v['paytypeid'] == 2){
                                \app\common\Order::wxShipping($v['aid'],$v,'water_happyti');
                            }

                            //确认收货
                            $rs = \app\common\Order::collect($v,'water_happyti');

                            if($shengy > 0 && $shengy < $v['totalprice']){

                                //从新计算分销佣金金额
                                $new_totalprice = bcsub($v['totalprice'],$shengy,2);
                                $this->newWaterCommission($v,$new_totalprice);
                            }

                            //进行分佣
                            \app\common\Order::giveCommission($v,'water_happyti');

                            //订单处理
                            Db::name('water_happyti_order')->where('aid',$aid)->where('id',$v['id'])->update([
                                'iscd' => 1,
                                'status' => 3,
                                'alipay_number' => $detail['alipay_number'] ?? '',
                            ]);
                        }
                    }
                }
            }
        }
    }

    public function newWaterCommission($order,$new_totalprice)
    {
        if(getcustom('water_happy_ti')){

            if($new_totalprice <= 0){
                return;
            }

            $info['commissionset'] = 0;
            $aid = $order['aid'];
            $mid = $order['mid'];
            $orderid = $order['id'];
            $num = 1;
            $totalprice = $new_totalprice;
            $orderdata = [];
            $sysset = Db::name('admin_set')->where('aid',$aid)->find();
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->find();

            $agleveldata = Db::name('member_level')->where('aid',$aid)->where('id',$member['levelid'])->find();
            if($agleveldata['can_agent'] > 0 && $agleveldata['commission1own']==1){
                $member['pid'] = $mid;
            }
            //$ppath = array_reverse(explode(',',$this->member['path']));

            if($info['commissionset']!=-1){
                //return $this->json(['status'=>0,'msg'=>'11','data'=>$this->member]);
                if($member['pid']){
                    $parent1 = Db::name('member')->where('aid',$aid)->where('id',$member['pid'])->find();

                    if($parent1){
                        $agleveldata1 = Db::name('member_level')->where('aid',$aid)->where('id',$parent1['levelid'])->find();
                        if($agleveldata1['can_agent']!=0){
                            $orderdata['parent1'] = $parent1['id'];
                        }
                    }
                    //return $this->json(['status'=>0,'msg'=>'11','data'=>$parent1,'data2'=>$agleveldata1]);
                }
                if($parent1['pid']){
                    $parent2 = Db::name('member')->where('aid',$aid)->where('id',$parent1['pid'])->find();
                    if($parent2){
                        $agleveldata2 = Db::name('member_level')->where('aid',$aid)->where('id',$parent2['levelid'])->find();
                        if($agleveldata2['can_agent']>1){
                            $orderdata['parent2'] = $parent2['id'];
                        }
                    }
                }
                if($parent2['pid']){
                    $parent3 = Db::name('member')->where('aid',$aid)->where('id',$parent2['pid'])->find();
                    if($parent3){
                        $agleveldata3 = Db::name('member_level')->where('aid',$aid)->where('id',$parent3['levelid'])->find();
                        if($agleveldata3['can_agent']>2){
                            $orderdata['parent3'] = $parent3['id'];
                        }
                    }
                }
                if($sysset['fxjiesuantype']==2){ //按利润提成
                    //$totalprice = $totalprice - $guige['cost_price'] * $num;
                    $totalprice = $totalprice;
                    if($totalprice < 0) $totalprice = 0;
                }
                if($info['commissionset']==1){//按比例
                    $commissiondata = json_decode($info['commissiondata1'],true);
                    if($commissiondata){
                        $orderdata['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * $totalprice * 0.01;
                        $orderdata['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * $totalprice * 0.01;
                        $orderdata['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * $totalprice * 0.01;
                    }
                }elseif($info['commissionset']==2){//按固定金额
                    $commissiondata = json_decode($info['commissiondata2'],true);
                    if($commissiondata){
                        $orderdata['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * $num;
                        $orderdata['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * $num;
                        $orderdata['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * $num;
                    }
                }elseif($info['commissionset']==3){//提成是积分
                    $commissiondata = json_decode($info['commissiondata3'],true);
                    if($commissiondata){
                        $orderdata['parent1score'] = $commissiondata[$agleveldata1['id']]['commission1'] * $num;
                        $orderdata['parent2score'] = $commissiondata[$agleveldata2['id']]['commission2'] * $num;
                        $orderdata['parent3score'] = $commissiondata[$agleveldata3['id']]['commission3'] * $num;
                    }
                }else{
                    if($agleveldata1){
                        if($agleveldata1['commissiontype']==1){ //固定金额按单
                            $orderdata['parent1commission'] = $agleveldata1['commission1'];
                        }else{
                            $orderdata['parent1commission'] = $agleveldata1['commission1'] * $totalprice * 0.01;
                        }
                    }
                    if($agleveldata2){
                        if($agleveldata2['commissiontype']==1){
                            $orderdata['parent2commission'] = $agleveldata2['commission2'];
                        }else{
                            $orderdata['parent2commission'] = $agleveldata2['commission2'] * $totalprice * 0.01;
                        }
                    }
                    if($agleveldata3){
                        if($agleveldata3['commissiontype']==1){
                            $orderdata['parent3commission'] = $agleveldata3['commission3'];
                        }else{
                            $orderdata['parent3commission'] = $agleveldata3['commission3'] * $totalprice * 0.01;
                        }
                    }
                }

                Db::name('water_happyti_order')->where('aid',$aid)->where('id',$order['id'])->update($orderdata);

                if($orderdata['parent1'] && ($orderdata['parent1commission'] || $orderdata['parent1score'])){
                    Db::name('member_commission_record')->where('aid',$aid)->where('orderid',$orderid)->update(['mid'=>$orderdata['parent1'],'frommid'=>$mid,'ogid'=>0,'type'=>'water_happyti','commission'=>$orderdata['parent1commission'],'score'=>$orderdata['parent1score'],'remark'=>'下级打水奖励']);
                }
                if($orderdata['parent2'] && ($orderdata['parent2commission'] || $orderdata['parent2score'])){
                    Db::name('member_commission_record')->where('aid',$aid)->where('orderid',$orderid)->update(['mid'=>$orderdata['parent2'],'frommid'=>$mid,'ogid'=>0,'type'=>'water_happyti','commission'=>$orderdata['parent2commission'],'score'=>$orderdata['parent2score'],'remark'=>'下二级打水奖励']);
                }
                if($orderdata['parent3'] && ($orderdata['parent3commission'] || $orderdata['parent3score'])){
                    Db::name('member_commission_record')->where('aid',$aid)->where('orderid',$orderid)->update(['mid'=>$orderdata['parent3'],'frommid'=>$mid,'ogid'=>0,'type'=>'water_happyti','commission'=>$orderdata['parent3commission'],'score'=>$orderdata['parent3score'],'remark'=>'下三级打水奖励']);
                }
            }
        }
    }

    //自动删除会员
    private function memberAutoDel(){
        if(getcustom('auto_del_member')){
            $syssetlist = Db::name('admin_set')->where('1=1')->field('aid,del_member_hour')->select()->toArray();
            foreach($syssetlist as $set){
                //未设置自动删除时间的表示不删除
                if($set['del_member_hour']<=0){
                    continue;
                }
                $default_levelid = Db::name('member_level')->where('aid',$set['aid'])->where('isdefault',1)->value('id');
                $list = Db::name('member')
                    ->where('aid',$set['aid'])
                    ->where('levelid',$default_levelid)
                    ->where('createtime','<',time()-$set['del_member_hour']*3600)
                    ->where('money',0)
                    ->where('score',0)
                    ->where('commission',0)
                    ->limit(1)
                    ->select()
                    ->toArray();
                foreach($list as $v){
                    if($v['last_buytime']>0){
                        //下过单的会员不删除
                        continue;
                    }
                    if($v['money']>0 || $v['score']>0 || $v['commission']>0){
                        //有余额的不删除
                        continue;
                    }
                    $res = \app\model\Member::del($set['aid'],(string)$v['id'],0);
                    writeLog('自动删除结果：'.json_encode($res),'memberAutoDel');
                    \app\common\System::plog('自动删除会员'.$v['id'],$set['aid']);
                    //写个日志，误删了可以再从日志中查数据恢复
                    writeLog('自动删除会员：'.json_encode($v),'memberAutoDel');
                }
            }
            dump('自动删除会员');
        }
    }

    //补贴积分自动转佣金
    private function butieToCommission(){
        if(getcustom('yx_network_help')) {
            $syssetlist = Db::name('admin_set')->where('1=1')->field('aid')->select()->toArray();
            foreach ($syssetlist as $set) {
                \app\custom\NetworkHelp::butie_to_commission($set['aid']);
            }
            dump('补贴积分自动转佣金');
        }
    }
    //互助积分自动转余额
    private function helpscoreToMoney(){
        if(getcustom('yx_network_help')) {
            Db::startTrans();
            $syssetlist = Db::name('admin_set')->where('1=1')->field('aid')->select()->toArray();
            foreach ($syssetlist as $set) {
                \app\custom\NetworkHelp::scoreToMoney($set['aid']);
            }
            Db::commit();
        }
    }
    private function luntanTopExpire(){
        if(getcustom('luntan_pay_top')){
            $now = time();
            //查询过期的限时置顶帖子
            $expiredPosts = Db::name('luntan')
                ->where('top_type', 2)
                ->where('top_expire_time', '<=', $now)
                ->where('top_expire_time', '>', 0)
                ->column('id, top_expire_time');

            if (empty($expiredPosts)) return;
            $ids = array_column($expiredPosts, 'id');

            //取消置顶
            Db::name('luntan')->where('id', 'in', $ids)->update(['is_top'=>0,'top_type' => 0,'top_expire_time' => 0]);
        }
    }

    private function lirunKaijiang(){
        //利润抽奖
        if(getcustom('yx_daily_lirun_choujiang')){
            $currentTime = date('H:i');
            $setlist = Db::name('lirun_choujiang_set')->where('drawing_type', 0)->where('status', 1)->where('draw_time', 'like', $currentTime . ':%')->select()->toArray();
            if (empty($setlist)) return;
            foreach ($setlist as $set){
                $cacheKey = 'lirun_kaijiang_' . $set['id'] . '_' . $currentTime;
                if (cache($cacheKey)) {
                    continue;
                }
                \app\custom\yingxiao\LirunChoujiang::drawWinner($set);
                cache($cacheKey, 1, 60);
            }
        }
    }
    private  function goldBeanShopAutoOrder(){
        if(getcustom('gold_bean_shop')){
            //金豆兑换
            $orderlist = Db::name('gold_bean_shop_order')->where('status',0)->select()->toArray();
            $autocloseArr = [];
            foreach($orderlist as $order){
                if(!$autocloseArr[$order['aid']]){
                    $autocloseArr[$order['aid']] = Db::name('gold_bean_shop_sysset')->where('aid',$order['aid'])->value('autoclose');
                }
                if($order['createtime'] + $autocloseArr[$order['aid']]*60 > time()) continue;
                $aid = $order['aid'];
                $mid = $order['mid'];
                $orderid = intval($order['id']);
                $order = Db::name('gold_bean_shop_order')->where('id',$orderid)->find();
                if(!$order || $order['status']!=0){
                    //return $this->json(['status'=>0,'msg'=>'关闭失败,订单状态错误']);
                }else{
                    //加库存
                    $oglist = Db::name('gold_bean_shop_order_goods')->where('aid',$aid)->where('orderid',$orderid)->select()->toArray();
                    foreach($oglist as $og){
                        Db::name('gold_bean_shop_product')->where('aid',$aid)->where('id',$og['proid'])->update(['stock'=>Db::raw("stock+".$og['num']),'sales'=>Db::raw("IF(sales>=".$og['num'].",sales-".$og['num'].",0)")]);
                        if($og['ggid']) Db::name('gold_bean_shop_guige')->where('aid',$aid)->where('id',$og['ggid'])->update(['stock'=>Db::raw("stock+".$og['num']),'sales'=>Db::raw("IF(sales>=".$og['num'].",sales-".$og['num'].",0)")]);
                    }
                    //优惠券抵扣的返还
                    if($order['coupon_rid'] > 0){
                        \app\common\Coupon::refundCoupon2($order['aid'],$order['mid'],$order['coupon_rid'],$order);
                    }
                    $rs = Db::name('gold_bean_shop_order')->where('id',$orderid)->where('aid',$aid)->where('mid',$mid)->update(['status'=>4]);
                    Db::name('gold_bean_shop_order_goods')->where('orderid',$orderid)->where('aid',$aid)->where('mid',$mid)->update(['status'=>4]);
                }
                \app\common\Order::order_close_done($order['aid'],$order['id'],'gold_bean_shop');
            }
        }
    }

    /**
     * 微信同城根据配送时间自动派单
     * @author: liud
     * @time: 2025/11/7 08:54
     */
    private function autoWxtcOrder(){
        if(getcustom('wx_express_intracity')){
            $syssetlist = Db::name('admin_set')->where('1=1')->field('aid')->select()->toArray();
            foreach ($syssetlist as $set) {
                $peisong = Db::name('peisong_set')->where('aid', $set['aid'])->field('wxtc_status')->find();
                if($peisong['wxtc_status'] != 1){
                    continue;
                }

                //查询待派单的订单
                $orderlist = Db::name('shop_order')->where('aid',$set['aid'])->where('status',1)->where('freight_type',2)->whereNotNull('freight_time')->field('id,aid,bid,freight_time')->select()->toArray();

                if($orderlist){
                    foreach ($orderlist as $order){
                        $freight_time = $order['freight_time'];
                        $sj_arr = explode('~',$freight_time);
                        if(time() >= strtotime($sj_arr[0])){
                            \app\common\Order::autoWxtcPeisong($order['aid'],$order['id'],'shop_order');
                        }
                    }
                }
            }
        }
    }
    
    //排队免单 -多商户队伍 重新排队
    private function queueFreeMultiBusiness(){
        if(getcustom('yx_queue_free_multi_team_business')){
            //文件锁，防止并发执行
            $file_name = ROOT_PATH.'runtime/queue_free_multi_business_lock.log';
            $is_do = true;
            if(file_exists($file_name)){
                file_put_contents($file_name,date('Y-m-d H:i:s').'任务重复'."\r\n",FILE_APPEND);
                $is_do = false;
            }else{
                file_put_contents($file_name,date('Y-m-d H:i:s').'任务开始'."\r\n",FILE_APPEND);
            }
            if($is_do){
                try {
                    $queue_set_list = Db::name('queue_free_set')->where('bid',0)->where('status',1)->select()->toArray();
                    foreach($queue_set_list as $key=>$set){
                        $aid = $set['aid'];
                        //所有的排队的商户
                        $team_bids = Db::name('queue_free_business_team')->where('aid',$aid)->column('bids');
                        $all_team_bids = [];
                        foreach ($team_bids as $tk=>$bids){
                            $this_team = explode(',',$bids);
                            $all_team_bids = array_merge($all_team_bids,$this_team);
                        }

                        //修改记录
                        $loglist = Db::name('queue_free_business_team_log')->where('aid',$aid)->where('status',0)->select()->toArray();
                        if(!$loglist)continue;
                        foreach($loglist as $lk=>$log){
                            //更改的排队
                            if($log['bids']){
                                $bids = explode(',',$log['bids']);
                                $order = 'createtime asc,id asc';
                                $queue_list = Db::name('queue_free')->where('aid',$aid)->where('status',0)->where('quit_queue',0)->where('bid','in',$bids)->order($order)->select()->toArray();
                                $no = 1;
                                foreach ($queue_list as $queue){
                                    Db::name('queue_free')->where('id',$queue['id'])->update(['queue_no' => $no]);
                                    $no +=1;
                                }
                            }
                           
                            Db::name('queue_free_business_team_log')->where('aid',$aid)->where('id',$log['id'])->update(['status' => 1,'endtime' => time()]);
                        }

                        //平台或者商户的将按照原方式更新排队
                        $where = [];
                        $where[] = ['aid','=',$aid];
                        $where[] = ['status','=',0];
                        $where[] = ['quit_queue','=',0];
                        $order = 'createtime asc,id asc';
                        if($set['queue_type_business'] != 1){
                           $bset_list =  Db::name('queue_free_set')->where('bid','not in',$all_team_bids)->where('status',1)->field('id,bid')->select()->toArray();
                            foreach($bset_list as $bset){
                                $b_queue_list = Db::name('queue_free')->where($where)->where('bid',$bset['bid'])->order($order)->select()->toArray();
                                $duli_no =1;
                                foreach ($b_queue_list as $queue){
                                    Db::name('queue_free')->where('id',$queue['id'])->update(['queue_no' => $duli_no]);
                                    $duli_no +=1;
                                }
                            }         
                        }else{
                            $where[] = ['bid','not in',$all_team_bids];
                            $duli_queue_list = Db::name('queue_free')->where($where)->order($order)->select()->toArray();
                            $duli_no =1;
                            foreach ($duli_queue_list as $queue){
                                Db::name('queue_free')->where('id',$queue['id'])->update(['queue_no' => $duli_no]);
                                $duli_no +=1;
                            }
                        }
                        
                    }
                }catch (\Exception $e) {
                    // 请求失败
                    writeLog($e, 'queue_free_multi_business');
                    unlink($file_name);
                }
                //执行完成删除锁文件
                unlink($file_name);
            }
        }
    }
    //重新排队的商户中，等待加入排队的订单
    private function queueFreeTeamJoin(){
        if(getcustom('yx_queue_free_multi_team_business')){
            $queue_set_list = Db::name('queue_free_set')->where('bid',0)->where('status',1)->select()->toArray();
            foreach($queue_set_list as $key=>$set){
                $temp_list = Db::name('queue_free_business_team_temp')->where('aid',$set['aid'])->select()->toArray();
                foreach($temp_list as $temp){
                    $aid = $temp['aid'];
                    $bid = $temp['bid'];
                    $log_raw[] = Db::raw("find_in_set({$bid},bids) or find_in_set({$bid},duli_bids)");
                    $queue_free_team_log = Db::name('queue_free_business_team_log')->where('aid',$aid)->where($log_raw)->where('status',0)->find();
                    //如果还有没执行的重新排队记录 就不能加入排队
                    if($queue_free_team_log){
                        writeLog('queueFreeTeamJoin - have queue_free_team_log', 'queue_free');  continue;
                    }
                    //存在重排中的队伍，加入临时表，
                    $order = Db::name($temp['type'].'_order')->where('aid',$aid)->where('id',$temp['orderid'])->find();
                    if(!$order){
                        writeLog('queueFreeTeamJoin - not have order', 'queue_free');  continue;
                    }
                    \app\custom\QueueFree::join($order,$temp['type'],$temp['action']);
                    Db::name('queue_free_business_team_temp')->where('id',$temp['id'])->update(['status' => 1,'endtime' => time()]);
                }
            } 
        }
    }
    //预约确认服务自动完成
    public function yuyueAutoConfirm(){
        $sysset = Db::name('yuyue_set')->where('finish_confirm',1)->where('autoconfirm','>',0)->select()->toArray();
        foreach($sysset as $set){
            $autoconfirm = $set['autoconfirm']*24*60*60;
            $orderlist = Db::name('yuyue_order')
                ->alias('o')
                ->field('o.*,w.status as worker_status')
                ->join('yuyue_worker_order w','o.id = w.orderid')
                ->where('o.aid',$set['aid'])
                ->where('w.status',3) //服务订单完成
                ->where('o.status',2) //预约订单服务中
                ->where('o.isticheng',0)
                ->where('w.endtime','<',time()-$autoconfirm) //服务订单完成时间 小于 自动确认时间
                ->select()
                ->toArray();
            foreach ($orderlist as $key => $order){
                //订单完成
                Db::name('yuyue_order')->where('id',$order['id'])->update(['status'=>3,'collect_time'=>time(),'isticheng'=>1]);
                $rs = \app\common\Order::collect($order,'yuyue'); //订单确认
                if($rs['status'] == 0) return $this->json($rs);
                $psorder = Db::name('yuyue_worker_order')->where('aid',$order['aid'])->where('orderid',$order['id'])->where('orderid',$order['id'])->find();
                \app\common\YuyueWorker::addmoney($psorder['aid'],$psorder['bid'],$psorder['worker_id'],$psorder['ticheng'],'服务提成'); //发放提成
            }

        }
    }

    //数字消费每日自动按倍数释放数组权益
    private function release_digital(){
        if(getcustom('yx_digital_consum')) {
            Db::startTrans();
            $set_arr = Db::name('digital_set')->where('status', 1)->select()->toArray();
            foreach ($set_arr as $set) {
                //切割底池1进底池2
                $res = \app\custom\yingxiao\DigitalConsum::cut_reservepool1($set['aid'], $set);
                //切割底池2进主池
                $res = \app\custom\yingxiao\DigitalConsum::cut_reservepool2($set['aid'], $set);
                //按倍数释放
                $res = \app\custom\yingxiao\DigitalConsum::autoWithdraw($set['aid'], $set);
            }
            Db::commit();
        }

    }

    //每日递减返现定时任务
    public static function dailyDecayCashback() {
        if (getcustom('yx_cashback_decay')){
            $cashbacklist = Db::name('cashback')->where('endtime','>',time())->where('return_type',5)->select()->toArray();
            foreach ($cashbacklist as $cashback){
                $cashback_member_list = Db::name('cashback_member')->where('aid',$cashback['aid'])->where('cashback_id',$cashback['id'])->where('decay_back_status','in',[1,3])->select()->toArray();
                foreach ($cashback_member_list as $cashback_member){
                    $check_status = \app\custom\OrderCustom::check_decay_cashback($cashback,$cashback_member);
                    if($check_status){
                        //开启暂停返现
                        Db::name('cashback_member')->where('id',$cashback_member['id'])->where('mid',$cashback_member['mid'])->where('cashback_id',$cashback['id'])->where('decay_back_status',3)->update(['decay_back_status' => 1]);
                    }else{
                        //暂停返现
                        Db::name('cashback_member')->where('id',$cashback_member['id'])->where('decay_back_status',1)->update(['decay_back_status' => 3]);
                    }

                    if($cashback_member['decay_back_status'] == 2) continue;
                    \app\custom\OrderCustom::deal_decay_cashback($cashback,$cashback_member['id']);
                }
            }
        }
    }

    //每日结算农场奖金
    private function dayFarmBonus() {
        if(getcustom('yx_farm')) {
            Db::startTrans();
            $set_arr = Db::name('farm_set')->where('status', 1)->select()->toArray();
            foreach ($set_arr as $set) {
                $farmCustom = new \app\custom\yingxiao\FarmCustom($set['aid']);
                $res = $farmCustom->dayBonus();
            }
            Db::commit();
        }
    }
    //每分钟检测一次摇钱树是否成熟
    private function checkFarmTree() {
        if(getcustom('yx_farm')) {
            Db::startTrans();
            $set_arr = Db::name('farm_set')->where('status', 1)->select()->toArray();
            foreach ($set_arr as $set) {
                $farmCustom = new \app\custom\yingxiao\FarmCustom($set['aid']);
                $res = $farmCustom->checkTreeEnd($set['aid']);
            }
            Db::commit();
        }
    }
    //会员等级即将到期短信通知 7天
    private function memberExpiredSendSms(){
        $syssetlist = Db::name('admin_set')->where('1=1')->field('aid')->select()->toArray();
        $expired_time = strtotime(date('Y-m-d',time())) + 86400 * 7; 
        foreach($syssetlist as $set){
             $aid = $set['aid'];
             $member_list = Db::name('member')->where('aid',$aid)->where('levelendtime','=',$expired_time)->field('id,tel,levelid')->select()->toArray(); 
             foreach($member_list as $member){
                 $tel = $member['tel']?$member['tel']:'';
                 if($tel){
                     //短信通知
                     $levelname = Db::name('member_level')->where('aid',$aid)->where('id',$member['levelid'])->value('name');
                      \app\common\Sms::send($aid,$tel,'tmpl_member_uplevel_expired',['level' => $levelname]);
                 }
             }
        }
    }
    //优惠券快过期短信通知 3天
    private function couponExpiredSendSms(){
        $syssetlist = Db::name('admin_set')->where('1=1')->field('aid')->select()->toArray();
        $start_time = strtotime(date('Y-m-d ',time())) + 86400 * 2;
        $end_time = $start_time+86399;
        foreach($syssetlist as $set){
            $aid = $set['aid'];
            $coupon_record=Db::name('coupon_record')->alias('cr')
                ->join('member m','m.id = cr.mid')
                ->where('cr.aid',$aid)->where('cr.endtime','between',[$start_time,$end_time])
                ->field('cr.id,cr.mid,cr.endtime,cr.couponname,m.tel')->select()->toArray();
           foreach ($coupon_record as $record){
               //马到成功新增短信通道，余额变更通知
               $tel = $record['tel'];
               if($tel){
                   $admin_set =  Db::name('admin_set')->where('aid',$aid)->field('name,tel')->find();
                   $bname = $admin_set['name'];
                    \app\common\Sms::send($aid,$tel,'tmpl_coupon_expired',['bname'=>$bname,'coupon_name'=>$record['couponname'],'days' => '3']);
               }
           } 
        }
    }
}

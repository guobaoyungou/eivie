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

namespace app\common;

use think\facade\Db;
use think\facade\Log;

class Order
{
    //订单退款  1余额支付 2微信支付 3支付宝支付 4货到付款 11百度小程序 12头条小程序 122云闪付小程序
    //$params 其他数据 如['refund_combine'=>true,'refund_order'=>$refund_order,'mid'=>0]
    public static function refund($order,$refund_money,$reason='退款',$params=[]){
        $params['mid'] = $order['mid']??0;//下单用户id
        if(!$reason) $reason = '退款';
        $paytype = $order['paytypeid'];
        if(is_null($paytype)) return ['status'=>1,'msg'=>''];
        if($refund_money == 0) return ['status'=>1,'msg'=>''];
        //代付订单退款，推给实际支付人
        //如果是代付则退回代付账号,不可以用orderid[]
        $payorder = Db::name('payorder')->where('aid',$order['aid'])->where('ordernum',$order['ordernum'])->find();
        if($payorder) $order['totalprice'] = $payorder['money'];
        else {
            \think\facade\Log::error($order['ordernum'].' not find payorder');
        }
        if(strpos($order['ordernum'],'_')){ //合并支付
            $ordernum = explode('_',$order['ordernum'])[0];
            $payorder = Db::name('payorder')->where('aid',$order['aid'])->where('ordernum',$ordernum)->find();
            if($payorder['status']==1){ //是合并支付的
                $order['totalprice'] = $payorder['money'];
                $order['ordernum'] = $ordernum;
            }
        }
        if(getcustom('restaurant_cashdesk_table_merge_pay')){
            if($payorder['merge_ordernum']){  //替换为 支付的订单号，用于退款
                $order['ordernum'] = $payorder['merge_ordernum'];
                $order['totalprice'] = $payorder['merge_totalprice'];
            }
        }
        if($payorder['paymid']>0){
            $payorderP = Db::name('payorder')->where('status',1)->where('pid',$payorder['id'])->find();
            $mid = $payorderP['mid'];
//            $order['platform'] = $payorderP['platform'];
            $order['ordernum'] = $payorderP['ordernum'];
            $order['totalprice'] = $payorderP['money'];
            $remark = '代付订单退款';
        }else{
            $mid = $order['mid'];
            $remark = '订单退款';
        }
        if($params && $params['remark']){
            $remark .= $params['remark'];
        }
        if(getcustom('member_create_child_order')){
           if($payorder['pmid']) $mid = $payorder['pmid'];
        }
        if($paytype == 0){
            $rs = ['status' =>1,'msg'=>'现金退款成功'];
        }
        elseif($paytype == 1){
            //aid和mid重新定义
            $aid2 = $order['aid'];$mid2 = $mid;
            $addmoney_params = [];//其他参数
            if(getcustom('scoreshop_otheradmin_buy')){
                //如果扣除的是其他平台用户积分
                if($order['othermid']){
                    $aid2 = $order['otheraid'];$mid2 = $order['othermid'];
                    $appinfo = Db::name('admin_setapp_wx')->where('aid',$order['aid'])->field('id,nickname')->find();
                    if($appinfo && !empty($appinfo['nickname'])){
                        $remark = $appinfo['nickname'].'订单'.$order['ordernum'].'退款返还';
                    }else{
                        $set = Db::name('admin_set')->where('aid',$order['aid'])->field('name')->find();
                        if($set && !empty($set['name'])){
                            $remark = $set['name'].'订单'.$order['ordernum'].'退款返还';
                        }
                    }
                    $addmoney_params['optaid'] = $order['aid'];
                }
            }

            //退还余额支付手续费
            if(getcustom('money_pay_fee_rate',$order['aid']) ) {
                if($payorder['money_pay_fee'] > 0){
                    $return_money_pay_fee = Db::name('member_moneylog')->where('aid',$payorder['aid'])->where('ordernum',$payorder['ordernum'])->where('type','money_pay_fee')->where('money','>',0)->sum('money');

                    if($return_money_pay_fee < $payorder['money_pay_fee']){
                        //计算退款及金额比例
                        $return_money_pay_fee_rate = $refund_money/$payorder['money'];
                        //根据比例计算应退款手续费
                        $dc_money_pay_fee = round($payorder['money_pay_fee'] * $return_money_pay_fee_rate,2);

                        $tksxf = $return_money_pay_fee + $dc_money_pay_fee;
                        if($tksxf > $payorder['money_pay_fee']){
                            $dc_money_pay_fee = $payorder['money_pay_fee'] - $return_money_pay_fee;
                        }

                        \app\common\Member::addmoney($payorder['aid'],$payorder['mid'],$dc_money_pay_fee,'订单退款退还余额支付手续费，订单号: ' . $payorder['ordernum'],0,'','',['type' => 'money_pay_fee','ordernum'=>$payorder['ordernum']]);
                    }
                }
            }

            $rs = \app\common\Member::addmoney($aid2,$mid2,$refund_money,$remark.' '.$reason,0,'','',$addmoney_params);
            //马到成功新增短信通道，余额变更通知
            $tel = Db::name('member')->where('aid',$aid2)->where('id',$mid2)->value('tel');
            if($tel){
                $admin_set =  Db::name('admin_set')->where('aid',$aid2)->field('name,tel')->find();
                $bname = $admin_set['name'];
                $btel = $admin_set['tel'];
                \app\common\Sms::send($aid2,$tel,'tmpl_admin_operate_money',['bname'=>$bname,'remark'=>'退款','operate' => '增加','money' => $refund_money,'btel' => $btel]);
            }
        }
        elseif($paytype == 2){
            if(getcustom('pay_money_combine',$order['aid'])){
                //处理余额组合支付退款
                $refund_money = \app\custom\OrderCustom::deal_refund_combine2($refund_money,$order,$payorder,$params,'wxpay');
            }
            if($refund_money>0){
                if(getcustom('wxpay_native_h5') && $payorder['wxpay_typeid'] == 7){
                    //如果是扫码付款，则退款调用h5配置，因为拉起付款码的时候用的是h5配置
                    $order['platform'] = 'h5';
                }
                if(getcustom('pay_allinpay')){
                    $params['mid'] = $payorder['mid'];
                    $params['tablename'] = $payorder['type'];
                }
                $rs = \app\common\Wxpay::refund($order['aid'],$order['platform'],$order['ordernum'],$order['totalprice'],$refund_money,$reason,$order['bid'],$payorder,$params['refund_order'],$params);
            }else{
                $rs = ['status'=>1,'msg'=>''];
            }
            if(getcustom('pay_money_combine',$order['aid'])){
                //处理余额组合支付退款
                $res3 = \app\custom\OrderCustom::deal_refund_combine3($refund_money,$order,$payorder,$params,'wxpay',$mid,$rs,$paytype,$remark,$reason);
                if($res3['status'] != 1){
                    if(!$res3 || $res3['status'] !=1){
                        $msg = $res3 && $res3['msg']?$res3['msg']:'退款错误';
                        return ['status'=>0,'msg'=>$msg];
                    }
                }
            }
        }
        elseif($paytype == 3 || ($paytype>=302 && $paytype<=330)){
            if(getcustom('pay_money_combine',$order['aid'])){
                //处理余额组合支付退款
                $refund_money = \app\custom\OrderCustom::deal_refund_combine2($refund_money,$order,$payorder,$params,'alipay');
            }
            if($refund_money>0){
                $rs = \app\common\Alipay::refund($order['aid'],$order['platform'],$order['ordernum'],$order['totalprice'],$refund_money,$reason,$order['bid'],$payorder,$params['refund_order'],$params);
            }else{
                $rs = ['status'=>1,'msg'=>''];
            }
            if(getcustom('pay_money_combine',$order['aid'])){
                //处理余额组合支付退款
                $res3 = \app\custom\OrderCustom::deal_refund_combine3($refund_money,$order,$payorder,$params,'alipay',$mid,$rs,$paytype,$remark,$reason);
                if($res3['status'] != 1){
                    if(!$res3 || $res3['status'] !=1){
                        $msg = $res3 && $res3['msg']?$res3['msg']:'退款错误';
                        return ['status'=>0,'msg'=>$msg];
                    }
                }
            }
        }
        elseif($paytype == 4){
            $rs = ['status'=>1,'msg'=>''];
        }
        //转账汇款
        elseif($paytype == 5){
            $rs = ['status'=>1,'msg'=>''];
        }
        elseif($paytype == 11){ //百度小程序
            $rs = \app\common\Baidupay::refund($order['aid'],$order['mid'],$order['ordernum'],$order['paynum'],$order['totalprice'],$refund_money,$reason);
        }
        elseif($paytype == 12){ //头条小程序
            $rs = \app\common\Ttpay::refund($order['aid'],$order['ordernum'],$order['totalprice'],$refund_money,$reason);
        }
        elseif($paytype == 22){ //云收银
            $rs = \app\common\Yunpay::refund($order['aid'],$order['platform'],$order['ordernum'],$order['totalprice'],$refund_money,$reason);
        }
        elseif($paytype == 23){
            $rs = \app\common\Qmpay::refund($order['aid'],$order['platform'],$order['ordernum'],$order['totalprice'],$refund_money,$reason);
        }
        elseif($paytype == 24){
            $rs = \app\common\Qmpay::refund2($order['aid'],$order['platform'],$order['ordernum'],$order['totalprice'],$refund_money,$reason);
        }
        elseif($paytype == 51){  //paypal
            $rs = \app\custom\PayPal::refund($order['aid'],$order['platform'],$order['ordernum'],$order['totalprice'],$refund_money,$reason);
        }
        elseif($paytype == 62){  //汇付     ($order['aid'],$order['platform'],$order['ordernum'],$order['totalprice'],$refund_money,$reason
            $appinfo = [];
            if(in_array($payorder['platform'],['cashier','cashdesk'])){
                $admin_setapp_type = '';
                if($payorder['platform'] =='cashier'){
                    $admin_setapp_type = 'cashdesk';
                }elseif($payorder['platform'] =='cashdesk'){
                    $admin_setapp_type = 'restaurant_cashdesk';
                }
                $appinfo = Db::name('admin_setapp_'.$admin_setapp_type)->where('aid',$order['aid'])->where('bid',0)->find();
                if($order['bid']>0){
                    if($payorder['platform'] =='cashier'){
                        $business_sysset = Db::name('business_sysset')->where('aid',$order['aid'])->field('business_cashdesk_huifupay')->find();
                    } else{
                        $business_sysset = Db::name('restaurant_admin_set')->where('aid',$order['aid'])->field('business_cashdesk_huifupay')->find();
                    }
                    if($business_sysset['business_cashdesk_huifupay'] ==3){//商户独立收款
                        $bappinfo  =  Db::name('admin_setapp_'.$admin_setapp_type)->where('aid',$order['aid'])->where('bid',$order['bid'])->find();
                        $appinfo =  $bappinfo;
                    }
                }
            }
            
            $huifu = new \app\custom\Huifu($appinfo,$order['aid'],$order['bid'],$mid,$reason,$order['ordernum'],$order['totalprice']);
            $rs = $huifu->refund($refund_money,$payorder);
        }
        elseif($paytype == 63){
            if (getcustom('pay_huifu_quickpay')){
                $appinfo = \app\common\System::appinfo($order['aid'],$order['platform']);
                $huifu = new \app\custom\Huifu($appinfo,$order['aid'],$order['bid']);
                $rs = $huifu->onlinepaymentRefund($refund_money,$order);
            }
        }
        //信用额度支付
        elseif($paytype == 38){
            $rs = \app\common\Member::addOverdraftMoney($order['aid'],$mid,$refund_money,$remark.' '.$reason);
        }
        elseif($paytype == 60){ //视频号
            $rs = ['status'=>1,'msg'=>''];
        }
        elseif($paytype == 60){ //视频号
            $rs = ['status'=>1,'msg'=>''];
        }
        elseif($paytype == 122){ //云闪付小程序
			if(getcustom('pay_chinaums')){
				$yunshanfu = new \app\custom\YunshanfuWxPay($order['aid']);
				$yunshanfu->refund($order,$refund_money,$params['refund_order']);
				$rs = ['status'=>1,'msg'=>'退款成功'];
			}
        }
		elseif($paytype == 123){
			if(getcustom('pay_qilinshuzi')){
				$appinfo = \app\common\System::appinfo($order['aid'],$order['platform']);
				//查询原订单号
				$payOrderNum = Db::name('qilinshuzi_log')->where('ordernum',$order['ordernum'])->where('pay_status',1)->value('ordernum');
				$refundInfo = [
					'refund_ordernum' => $ordernum .'T'.mt_rand(10000, 99999),
					'ordernum' => $payOrderNum, //原订单号
					'refund_reason' => $reason,
					'refund_money' => $refund_money
				];
				$rs = \app\custom\QilinshuziPay::refund($appinfo,$refundInfo);
			}
		}elseif($paytype == 124){
            if (getcustom('sxpay_native')){
                $ordernum = $order['ordernum'];
                //检查支付流水表是否存在当前已支付的订单
                $pay_transaction = Db::name('pay_transaction')->where(['aid'=>$order['aid'],'ordernum'=>$order['ordernum'],'type'=>$payorder['type'],'status'=>1])->order('id desc')->find();
                if($pay_transaction){
                    //如果有数据取流水单号发起退款
                    $ordernum = $pay_transaction['transaction_num'];
                }
                $rs = \app\custom\Sxpay::refund($order['aid'],$order['platform'],$ordernum,$order['totalprice'],$refund_money,$reason,$order['bid'],$paytype);
            }
        }
        //新增退款金额和时间，用于统计
        $after_refund_money =    $payorder['refund_money'] + $refund_money;
        if($after_refund_money > $payorder['money']){
            $after_refund_money =  $payorder['money'];
        }
        $refund_update  =[
            'refund_money' => $after_refund_money,
            'refund_time' => time()
        ];
        Db::name('payorder')->where('aid',$order['aid'])->where('id',$payorder['id'])->update($refund_update);
        if(getcustom('yx_buy_fenhong')){
            if($paytype !=1){
                \app\custom\BuyFenhong::refundSubScoreWeight($payorder);
            }
        }
        // 店铺补贴
        if(getcustom('yx_shop_order_team_yeji_bonus',$order['aid'])) {
            self::rebackShopBonus($payorder,$refund_money);
        }
        // 分红
        if(getcustom('yx_yeji_fenhong',$order['aid'])) {
            self::rebackYejiFenhong($payorder,$refund_money);
        }
        // 订单合并
        if(getcustom('shoporder_merge',$order['aid'])){
           $shop_order_merge = Db::name('shop_order_merge')->where('aid',$order['aid'])->where("find_in_set('".$order['id']."',orderids)")->find();
           if($shop_order_merge){
                $update_merge = [];
                $update_merge['refund_money'] = $shop_order_merge['refund_money']+$refund_money;
                if($update_merge['refund_money'] >= $shop_order_merge['totalprice']){
                    $update_merge['status'] = 3;
                }
                Db::name('shop_order_merge')->where('aid',$order['aid'])->where('id',$shop_order_merge['id'])->update($update_merge);
           }
           
        }

        if(getcustom('freeze_money')){
            $order = $params['refund_order'];
            //退回冻结资金
            if($order['refund_freezemoney']>0){
                \app\common\Member::addfreezemoney($order['aid'],$order['mid'],$order['refund_freezemoney'],'订单退款'.$order['refund_reason'],$order['ordernum']);
            }
        }

        //修改未发放的分红状态
        if($payorder['type']=='maidan'){
            self::order_close_done($payorder['aid'],$payorder['orderid'],$payorder['type']);
        }

        return $rs;
    }
    //订单收货
    public static function collect($order,$type='shop',$commission_mid = 0){
        $aid = $order['aid'];
        $mid = $order['mid'];
        $platformMoney = 0;
        $business_lirun = 0;
        if(getcustom('yx_jidian',$aid) && $order['bid']) {
            $jidian_set = Db::name('jidian_set')->where('aid', $aid)->where('bid', $order['bid'])->find();
            $paygive_scene = explode(',',$jidian_set['paygive_scene']);
        }
        $businessDkScore = $businessDkMoney = 0;

        //查询支付订单，处理是否是商家支付问题
        $isbusinesspay = self::dealIsbusinesspay($aid,$order);

        if($type == 'shop'){
            if(getcustom('supply_zhenxin',$aid)){
                //如果是甄新汇选商品，需要先请求接口完成订单
                if($order['issource'] && $order['source'] == 'supply_zhenxin'){
                    if($order['status'] != 2){
                        return ['status'=>0,'msg'=>'订单状态不符'];
                    }
                    $orderconfirm = \app\custom\SupplyZhenxinCustom::orderconfirm($aid,$order['bid'],$order['sordernum']);
                    if(!$orderconfirm ||  $orderconfirm['status'] != 1){
                        $msg = $orderconfirm && $orderconfirm['msg']?$orderconfirm['msg']:'确认收货失败';
                        if($msg != '当前订单[已完结]，该订单不能确认收货') return ['status'=>0,'msg'=>$msg];
                    }
                }
            }
            if($order['fromwxvideo'] == 1){
                \app\common\Wxvideo::deliveryrecieve($order['id']);
            }

            if(getcustom('cefang',$aid) && $aid==2){ //定制1 订单对接 同步到策方
                $order2 = $order;
                $order2['status'] = 3;
                \app\custom\Cefang::api($order2);
            }

            if(getcustom('active_coin',$aid)){
                //先发放激活币
                self::giveActiveCoin($aid,$order,'shop',1);
            }
            if(getcustom('member_commission_max',$aid) && getcustom('add_commission_max',$aid)){
                //先发放佣金上限
                //20250327新增 绿色积分上限要跟着佣金上限走，必须先发佣金上限
                $res_commission_max = \app\common\Order::giveCommissionMax($aid,$order);
                if($res_commission_max['status']==1){
                    if(getcustom('greenscore_max')){
                        $consumer_set = Db::name('consumer_set')->where('aid',$aid)->find();
                        if($consumer_set['maximum_set_type']==0){
                            $order['give_maximum'] = $res_commission_max['member_commission_max'];
                        }
                    }
                }
            }
            if(getcustom('active_score',$aid)){
                //先发放让利积分
                self::giveActiveScore($aid,$order);
            }


            $oglist = Db::name('shop_order_goods')->where('aid',$aid)->where('orderid',$order['id'])->select()->toArray();
            
             if(getcustom('pay_huifu_fenzhang')){
                 $huifulog = Db::name('huifu_log')->where('aid',$order['aid'])->where('bid',$order['bid'])->where('ordernum',$order['ordernum'])->where('tablename','shop')->where('pay_status',1)->where('fenzhangdata','NOT NULL',null)->find();
                  if($huifulog){
                      //存在汇付支付且有分账，不再加入驻贷款
                      $is_business_daikuai = 0;
                  }
             }             
             $is_mendian_usercenter = 0;
             if(getcustom('mendian_usercenter',$aid)){
                 if($order['mdid']>0){
                     //门店中心给门店结算返款
                     $is_mendian_usercenter = 1;
                     \app\custom\MendianUsercenter::orderCollect($aid,$order,$oglist);
                 }
             }
             $is_business_daikuai = 1;
            if($order['bid']!=0 && $order['paytypeid'] !=4 && $is_business_daikuai && $is_mendian_usercenter==0){//入驻商家的货款
                $totalnum = 0;
                foreach($oglist as $og){
                    $totalnum += $og['num'];
                }

                $totalcommission = 0;
                $og_business_money = false;
                $totalmoney = 0;
                $all_cost_price = 0;
                $lirun_cost_price = 0;
                $total_cost_price = 0;
                $total_activecoin = 0;
                //扣除返现比例
                $queue_feepercent_type = 0;
                $queue_feepercent_allmoney = 0;
                $platformMoney = 0;
                if(getcustom('yx_queue_free',$aid)){
                    $queue_free_set = Db::name('queue_free_set')->where('aid',$order['aid'])->where('bid',0)->find();
                    $b_queue_free_set = Db::name('queue_free_set')->where('aid',$order['aid'])->where('bid',$order['bid'])->find();
                    $queue_free_set['order_types'] = explode(',',$queue_free_set['order_types']);
                    if($queue_free_set && $queue_free_set['status']==1 && $b_queue_free_set['status']==1 && in_array('all',$queue_free_set['order_types']) || in_array('shop',$queue_free_set['order_types'])){
                        if($queue_free_set['feepercent_type'] == 1){
                            $queue_feepercent_type = 1;
                        }
                    }
                }

                if(getcustom('money_dec',$aid)){
                    $add_dec_money = 0;//按商家抽成费率计算，商户独立支付模式，需要补发多少余额抵扣的部分，
                }

                $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                foreach($oglist as $og){
                    $og_commission = 0;
                    //if($og['iscommission']) continue;
                    if($og['parent1'] && $og['parent1commission'] > 0){
                        $totalcommission += $og['parent1commission'];
                        $og_commission += $og['parent1commission'];
                    }
                    if($og['parent2'] && $og['parent2commission'] > 0){
                        $totalcommission += $og['parent2commission'];
                        $og_commission += $og['parent2commission'];
                    }
                    if($og['parent3'] && $og['parent3commission'] > 0){
                        $totalcommission += $og['parent3commission'];
                        $og_commission += $og['parent3commission'];
                    }
                    if($og['parent4'] && $og['parent4commission'] > 0){
                        $totalcommission += $og['parent4commission'];
                        $og_commission += $og['parent4commission'];
                    }
                    //等级价格极差分销
                    $commissionJicha = Db::name('member_commission_record')->where(['aid' => $order['aid'], 'frommid' => $order['mid'], 'orderid' => $order['id'], 'ogid' => $og['id'], 'type' => 'shop'])
                        ->whereIn('status', [0,1])//佣金可能发了，可能还没发
                        ->whereLike('remark',"%购买商品差价")->sum('commission');
                    if($commissionJicha > 0) {
                        $totalcommission += $commissionJicha;
                        $og_commission += $commissionJicha;
                    }

                    if(!is_null($og['business_total_money'])) {
                        $og_business_money = true;
                        if($bset['platform_fee_type'] == 1){
                            $totalmoney += $og['business_total_money'];
                        }
                    }
                    if(getcustom('business_deduct_cost',$aid)){
                        if(!empty($og['cost_price']) && $og['cost_price']>0){
                            if($og['cost_price']<=$og['sell_price']){
                                $all_cost_price += $og['cost_price'];
                            }else{
                                $all_cost_price += $og['sell_price'];
                            }
                        }
                    }
                    if(getcustom('business_agent',$aid)){
                        if(!empty($og['cost_price']) && $og['cost_price']>0){
                            $lirun_cost_price += ($og['cost_price']*$og['num']);
                        }
                    }
                    if(getcustom('business_fee_type',$aid)){
                        if(!empty($og['cost_price']) && $og['cost_price']>0){
                            $total_cost_price += ($og['cost_price']*$og['num']);
                        }
                    }
                    if(getcustom('active_coin',$aid)){
                        $total_activecoin = bcadd($total_activecoin,$og['activecoin'],2);
                    }
                    if(getcustom('yx_queue_free',$aid)){
                        $product = Db::name('shop_product')->where('id',$og['proid'])->where('aid',$order['aid'])->where('bid',$order['bid'])->find();

                        if($product['queue_free_status'] == 1){
                            $queue_feepercent_allmoney += $og['real_totalprice'];
                        }
                    }
                    if(getcustom('money_dec',$aid)){
                        if($og['add_dec_money']>0) $add_dec_money += $og['add_dec_money'];//按商家抽成费率计算，商户独立支付模式，需要补发多少余额抵扣的部分，
                    }
                    if($bset['platform_fee_type'] == 0 && $og_business_money){
                        // 费率重新计算 平台费率 =（订单实付金额-退款金额）* 费率 
                        $where = [];
                        $where[] = ['rog.aid','=',$og['aid']];
                        $where[] = ['rog.mid','=',$og['mid']];
                        $where[] = ['rog.orderid','=',$og['orderid']];
                        $where[] = ['rog.ogid','=',$og['id']];
                        $where[] = ['ro.refund_status','=',2];
                        $refund_money = Db::name('shop_refund_order_goods')->alias('rog')
                            ->join('shop_refund_order ro','ro.id=rog.refund_orderid')
                            ->where($where)
                            ->sum('rog.refund_money');
                        if($bset['commission_kouchu'] == 0){ //不扣除佣金
                            $og_commission = 0;
                        }
                        $og_scoredkmoney = 0;
                        if($bset['scoredk_kouchu'] == 0){
                            $og_scoredkmoney = $og['scoredk_money'] ?? 0;
                        }
                        $og_leveldkmoney = 0;
                        if($bset['leveldk_kouchu'] == 0){ //扣除会员折扣
                            $og_leveldkmoney = $og['leveldk_money'] ?? 0;
                        }

                        // 计算商家货款
                        $remainMoney = $og['real_totalmoney'] - $refund_money - $og_commission + $og_scoredkmoney + $og_leveldkmoney;

                        $ogPlatformMoney = 0;
                        if($remainMoney > 0){
                            $product = Db::name('shop_product')->where('id', $og['proid'])->find();
                            //商品独立费率
                            if($product['feepercent'] != '' && $product['feepercent'] != null && $product['feepercent'] >= 0) {
                                $ogPlatformMoney = $remainMoney * $product['feepercent'] * 0.01;
                            } else {
                                //商户费率
                                if($binfo['feepercent']) $ogPlatformMoney = $remainMoney * $binfo['feepercent'] * 0.01;
                            }
                            $remainMoney -= $ogPlatformMoney;
                        }
                        $totalmoney += $remainMoney;
                        $platformMoney += $ogPlatformMoney;
                    }
                    
                }
                
                if($bset['commission_kouchu'] == 0){ //不扣除佣金
                    $totalcommission = 0;
                }
                if(getcustom('business_agent',$aid)){
                    $business_lirun = $order['totalprice']-$order['refund_money']-$lirun_cost_price;
                }
                $scoredkmoney = 0;
                if($bset['scoredk_kouchu'] == 0){
                    $scoredkmoney = 0;
                }elseif($bset['scoredk_kouchu'] == 1){ //扣除积分抵扣
                    $scoredkmoney = $order['scoredk_money'] ?? 0;
                }elseif($bset['scoredk_kouchu'] == 2){ //到商户余额
                    $businessDkMoney = $order['scoredk_money'];
                }elseif($bset['scoredk_kouchu'] == 3){ //到商户积分
                    $scoredkmoney = $order['scoredk_money'] ?? 0;
                    $businessDkScore = $order['scoredkscore'];
                }
                if(getcustom('business_toaccount_type',$aid)){
                    $hastoaccount = true;//实际到账单独设置权限
                    $addDecmoney = 0;//抵扣货款
                    //查询权限组
                    $admin_user = db('admin_user')->where('aid',$aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
                    //如果开启了实际到账单独设置权限
                    if($admin_user['auth_type'] != 1){
                        if($admin_user['groupid']){
                            $admin_user['auth_data'] = Db::name('admin_user_group')->where('id',$admin_user['groupid'])->value('auth_data');
                        }
                        $admin_auth = json_decode($admin_user['auth_data'],true);
                        if(!in_array('BusinessToaccountType,BusinessToaccountType',$admin_auth)){
                            $hastoaccount = false;
                        }
                    }
                    //计算实际到账 0：默认 1、按销售价 2、按市场价 3、按成本价
                    if($binfo['toaccount_type']<1 || $binfo['toaccount_type']>3) $hastoaccount = false;
                    $toaccountmoney = 0;//实际到账
                    if($hastoaccount){
                        foreach($oglist as $og){
                            if($binfo['toaccount_type'] == 1){
                                $toaccountmoney += $og['sell_price']*$og['num'];
                            }else if($binfo['toaccount_type'] == 2){
                                $toaccountmoney += $og['market_price']*$og['num'];
                            }else if($binfo['toaccount_type'] == 3){
                                $toaccountmoney += $og['cost_price']*$og['num'];
                            }
                        }
                        //商品独立费率
                        if($og_business_money) {
                            $toaccountmoney  = $toaccountmoney*(100- $binfo['feepercent']) * 0.01;
                            $toaccountmoney2 = $totalmoney;//原到账金额
                        }else{
                            $toaccountmoney2 = $order['product_price'];//原到账金额
                        }
                        //如果实际到账金额小于等于原到账金额，则重置原到账金额等于实际到账金额
                        if($toaccountmoney<=$toaccountmoney2){
                            $toaccountmoney2 = $toaccountmoney>=0?$toaccountmoney:0;
                        }else{
                            if(getcustom('member_goldmoney_silvermoney',$aid)){
                                $tocha = $toaccountmoney-$toaccountmoney2;//计算实际差额
                                $goldsilvermoneydec = $order['goldmoneydec']+$order['silvermoneydec'];//计算金银值抵扣部分
                                if($tocha>$goldsilvermoneydec){
                                    $addDecmoney = $goldsilvermoneydec;//抵扣货款
                                }else{
                                    $addDecmoney = $tocha;//抵扣货款
                                }
                                //原到账金额加上抵扣货款
                                $toaccountmoney2 += $addDecmoney;
                                $toaccountmoney2 = $toaccountmoney2>=0?$toaccountmoney2:0;
                            }
                        }
                        //商品独立费率
                        if($og_business_money) {
                            $totalmoney = $toaccountmoney2;
                        }else{
                            $order['product_price'] = $toaccountmoney2;
                        }
                    }
                }
                if($bset['platform_fee_type'] == 0 && $og_business_money){
                    $totalmoney +=  $order['freight_price'];
                }
                if(getcustom('deposit') && getcustom('deposit_business')){
                    if($order['up_floor_fee'] > 0){
                        $totalmoney +=  $order['up_floor_fee'];
                    }
                }
                /*********先计算平台费率**********/
                if($bset['platform_fee_type'] == 1){
                    //商品独立费率
                    if($og_business_money) {
//                        $totalmoney = $totalmoney + $order['freight_price'] - $totalcommission - $order['refund_money'] - $scoredkmoney;
                        //20251222发现 -$scoredkmoney已经在创建订单时减过了
                        $totalmoney = $totalmoney + $order['freight_price'] - $totalcommission - $order['refund_money'];
                        $platformMoney = $order['totalprice']-$totalmoney - $order['refund_money'];
                    } else {
                        /*********全部走的商品独立费率，这里的代码执行不到**********/
                        $leveldkmoney = $order['leveldk_money'] ?? 0;
                        if($bset['leveldk_kouchu'] == 0){ //扣除积分抵扣
                            $leveldkmoney = 0;
                        }

                        $totalmoney = $order['product_price'] + $order['freight_price'] - $order['coupon_money'] - $order['manjian_money'] - $order['discount_money_admin'] - $order['refund_money'] - $totalcommission - $scoredkmoney - $leveldkmoney;
                        if($totalmoney > 0){
                            if(getcustom('business_deduct_cost',$aid)){
                                if($binfo && $binfo['deduct_cost'] == 1){
                                    //扣除成本
                                    $platformMoney = ($totalmoney-$all_cost_price)*$binfo['feepercent']/100;
                                }else{
                                    $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;
                                }
                            }else{
                                $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;
                            }
                            if(getcustom('business_fee_type',$aid)){
                                if($bset['business_fee_type'] == 1){
                                    //多商户结算按销售价
                                    $platformMoney = ($order['totalprice']-$order['freight_price']) * $binfo['feepercent'] * 0.01;
                                }elseif($bset['business_fee_type'] == 2){
                                    //多商户结算按销售价
                                    $platformMoney = $total_cost_price * $binfo['feepercent'] * 0.01;
                                }
                            }
                            $totalmoney = $totalmoney - $platformMoney;
                        }
                    }
                }
                if(getcustom('active_coin',$aid)){
                    //$totalmoney = bcsub($totalmoney,$total_activecoin,2);
                }

                if($order['paytypeid']==4){
                    $totalmoney = $totalmoney - $order['totalprice'];
                }
                if(getcustom('yx_queue_free',$aid)){
                    if($queue_feepercent_type == 1 && $queue_feepercent_allmoney > 0 && $b_queue_free_set['rate_back'] > 0){
                        $totalmoney = $totalmoney - $queue_feepercent_allmoney * $b_queue_free_set['rate_back'] * 0.01;
                    }
                }

                if(!$isbusinesspay){
                    if(getcustom('member_dedamount',$aid)){
                        //是否开启了抽佣依赖抵扣金，开启了需要有抵扣金金额才抽成，需要重新计算商家到账货款
                        $dedamountset = Db::name('admin_set')->where('aid',$order['aid'])->field('dedamount_dkpercent,dedamount_choucheng')->find();
                        if($dedamountset && $dedamountset['dedamount_choucheng'] && $dedamountset['dedamount_choucheng']  == 1){
                            //下单让利比例大于且抵扣金额大于，则需要计算，否则直接为实付金额，不进行抽成
                            if($dedamountset['dedamount_dkpercent']>0 && $order['dedamount_dkmoney']>0){
                                //计算商品价格+快递费的总未折扣、未抵扣的金额
                                $allmoney   = $order['product_price']+$order['freight_price'];
                                //总让利
                                $allrlmoney = $order['dedamount_dkmoney']/($dedamountset['dedamount_dkpercent'] * 0.01);
                                //商家到账
                                $totalmoney = $allmoney - $allrlmoney;
                                if($totalmoney<0) $totalmoney = 0;
                            }else{
                                $totalmoney = $order['totalprice'];//不抽成，直接是实付金额
                            }
                        }
                    }
                    if($totalmoney < 0){
                        $bmoney = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->value('money');
                        if($bmoney + $totalmoney < 0){
                            return ['status'=>0,'msg'=>'操作失败,商家余额不足'];
                        }
                    }
                    //商家货款
                    \app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'货款，订单号：'.$order['ordernum'],true,$type,$order['ordernum'],['platformMoney'=>$platformMoney,'business_lirun'=>$business_lirun]);
                }else{
                    //商家推荐分成
                    if(getcustom('business_agent',$aid)){
                        \app\common\Business::addparentcommission2($aid,$order['bid'],$totalmoney,$platformMoney,$business_lirun);
                    }else{
                        if($totalmoney > 0){
                            \app\common\Business::addparentcommission($aid,$order['bid'],$totalmoney);
                        }
                    }

                    if(getcustom('business_toaccount_type',$aid)){
                        $payorder = Db::name('payorder')->where('aid',$aid)->where('ordernum',$order['ordernum'])->find();
                        //不是随行付付款，且有实际到账权限，且单独设置，且抵扣货款大于0
                        if($payorder && !$payorder['issxpay'] && $hastoaccount && $addDecmoney>0){
                            //抵扣货款乘以抽成
                            $addDecmoney = $addDecmoney*$binfo['feepercent'] * 0.01;
                            $addDecmoney = round($addDecmoney,2);
                            if($addDecmoney>0){
                                //补发抵扣货款
                                \app\common\Business::addmoney($aid,$order['bid'],$addDecmoney,'补发货款，订单号：'.$order['ordernum'],false,$type,$order['ordernum']);
                            }
                        }
                    }
                    if(getcustom('money_dec',$aid)){
                        //按商家抽成费率计算，商户独立支付模式，需要补发多少余额抵扣的部分，
                        $add_dec_money = round($add_dec_money,2);
                        if($add_dec_money>0){
                            //补发抵扣货款
                            \app\common\Business::addmoney($aid,$order['bid'],$add_dec_money,'补发'.t('余额').'抵扣部分货款，订单号：'.$order['ordernum'],false,$type,$order['ordernum']);
                        }
                    }
                }
                //店铺加销量
                Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->inc('sales',$totalnum)->update();
                //Db::name('shop_order_goods')->where('aid',$aid)->where('orderid',$order['id'])->update(['iscommission' => 1]);
                if(getcustom('yx_jidian',$aid)){
                    //集点
                    if($jidian_set && in_array('shop',$paygive_scene) && $jidian_set['status'] == 1 && time() >= $jidian_set['starttime'] && time() <= $jidian_set['endtime']){
                        //执行时此笔订单还没收货
                        \app\common\System::getOrderNumFromJidian($aid,$order['bid'],$jidian_set,$order['mid'],1,true);
                    }
                }

                if(getcustom('pay_yuanbao',$aid)){
                    //元宝支付
                    //查询商家
                    $business = Db::name('business')->where('id',$order['bid'])->field('mid')->find();
                    if($business && $business['mid']>0){
                        //查询用户是否存在
                        $count_member = Db::name('member')
                            ->where('id',$business['mid'])
                            ->count();
                        if($count_member){
                            //给商家用户发元宝
                            \app\common\Member::addyuanbao($order['aid'],$business['mid'],$order['total_yuanbao'],'订单:'.$order['ordernum'].'完成发放');
                        }
                    }
                }
                if(getcustom('business_moneypay',$aid) && in_array($order['paytypeid'],[2,3,12,13])){ //多商户设置的消费送积分
                    $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                    $givescore = floor($order['totalprice'] / $bset['scorein_money']) * $bset['scorein_score'];
                    if($givescore > 0){
                        $res = \app\common\Member::addscore($aid,$order['mid'],$givescore,'消费送'.t('积分'));
                        if($res && $res['status'] == 1){
                            //记录消费赠送积分记录
                            \app\common\Member::scoreinlog($aid,$order['bid'],$order['mid'],'shop',$order['id'],$order['ordernum'],$givescore,$order['totalprice']);
                        }
                    }
                }

                if(getcustom('business_canuseplatcoupon',$aid) && $order['coupon_money'] > 0 && $order['coupon_rid'] > 0){
                    $couponrecord = Db::name('coupon_record')->where('id',$order['coupon_rid'])->find();
                    if($couponrecord && $couponrecord['bid'] == 0){
                        $businessuserecord = [];
                        $businessuserecord['aid'] = $order['aid'];
                        $businessuserecord['bid'] = $order['bid'];
                        $businessuserecord['mid'] = $order['mid'];
                        $businessuserecord['ordertype'] = $type;
                        $businessuserecord['orderid'] = $order['id'];
                        $businessuserecord['couponrid'] = $order['coupon_rid'];
                        $businessuserecord['couponid'] = $couponrecord['couponid'];
                        $businessuserecord['couponname'] = $couponrecord['couponname'];
                        $businessuserecord['couponmoney'] = $couponrecord['money'];
                        $businessuserecord['decmoney'] = $order['coupon_money'];
                        $businessuserecord['status'] = 1;
                        $businessuserecord['createtime'] = time();
                        Db::name('coupon_businessuserecord')->insert($businessuserecord);
                        Db::name('business')->where('id',$order['bid'])->inc('couponmoney',$order['coupon_money'])->update();
                    }
                }
            }
            //赠积分
            if($order['givescore'] > 0){
                if(getcustom('shop_alone_give_score')){
                    $alone_give_score = Db::name('admin_set')->where('aid',$order['aid'])->value('alone_give_score');
                    if($alone_give_score == 2){
                        $payShop = Db::name('shop_order_goods')
                            ->alias('og')
                            ->where('og.orderid',$order['id'])
                            ->join('shop_guige gg','og.ggid = gg.id')
                            ->field('og.totalprice,og.scoredk_money,gg.givescore,gg.sell_price')
                            ->select()
                            ->toArray();

                        $shopGiveScore = 0;
                        foreach ($payShop as $k => $v){
                            if($v['totalprice'] == $v['scoredk_money']){
                                continue;
                            }
                            $payMoney = $v['totalprice']  - $v['scoredk_money'];
                            $shopGiveScore += $v['givescore'] * ( $payMoney / $v['sell_price'] );
                        }
                        
                        $order['givescore'] = $shopGiveScore;
                        Db::name('shop_order')->where('id',$order['id'])->update(['givescore' => $order['givescore']]);
                    }
                }
                if($order['bid'] > 0){
                    $decbscore = 0; //由平台发放积分
                    if(getcustom('business_score')){
                        //是否开启商户使用独立积分
                        $decbscore = Db::name('business_sysset')->where('aid',$aid)->value('business_selfscore');
                    }
                    \app\common\Business::addmemberscore($aid,$order['bid'],$order['mid'],$order['givescore'],'购买产品赠送'.t('积分'),$decbscore);
                }else{
                    \app\common\Member::addscore($aid,$order['mid'],$order['givescore'],'购买产品赠送'.t('积分'));
                }
            }
            if(getcustom('member_commission_max',$aid)){
                //送佣金上限
                if($order['give_commission_max'] > 0){
                    \app\common\Member::addcommissionmax($aid,$order['mid'],$order['give_commission_max'],'购买商品赠送'.t('佣金上限'),'shop',$order['id']);
                }
            }
            //赠提现积分
            if(getcustom('commission_duipeng_score_withdraw',$aid)){
                if($order['give_withdraw_score'] > 0){
                    \app\common\Member::add_commission_withdraw_score($aid,$order['mid'],$order['give_withdraw_score'],'购买产品赠送提现积分');
                }
                if($order['give_parent1_withdraw_score'] > 0 && $order['give_parent1'] >0){
                    \app\common\Member::add_commission_withdraw_score($aid,$order['give_parent1'],$order['give_parent1_withdraw_score'],'推荐下级购买产品赠送提现积分',$order['mid']);
                }
            }
            if(getcustom('product_givetongzheng',$aid)){
                if($order['givetongzheng']>0){
                    $release_bili = Db::name('admin_set')->where('aid',$order['aid'])->value('tongzheng_release_bili');
                    $tz_log = [];
                    $tz_log['aid'] = $order['aid'];
                    $tz_log['mid'] = $order['mid'];
                    $tz_log['orderid'] = $order['id'];
                    $tz_log['tongzheng'] = $order['givetongzheng'];
                    $tz_log['release_bili'] = $release_bili;
                    $tz_log['remain'] = $order['givetongzheng'];
                    $tz_log['createtime'] = time();
                    Db::name('tongzheng_order_log')->insert($tz_log);
                }
            }

            if(getcustom('everyday_hongbao',$aid)) {
                $totalHongbao = 0;
                foreach($oglist as $og){
                    if($og['ishongbao'] || $og['hongbaoEdu'] <= 0) continue;
                    \app\common\Member::addHongbaoEverydayEdu($aid,$order['mid'],$og['hongbaoEdu'], '购买增加红包额度', $og['id']);
                }
                Db::name('shop_order_goods')->where('aid',$aid)->where('orderid',$order['id'])->update(['ishongbao' => 1]);
            }

            if(getcustom('discount_code_zhongchuang',$aid)){
                if($order['discount_code_zc']){
                    //中创推送订单数据
                    $postzcog = [];
                    foreach($oglist as $og){
                        $postzcog[] = [
                            'proid'=>$og['proid'],
                            'name'=>$og['name'],
                            'pic'=>$og['pic'],
                            'procode'=>$og['procode'],
                            'ggid'=>$og['ggid'],
                            'ggname'=>$og['ggname'],
                            'num'=>$og['num'],
                            'sell_price'=>$og['sell_price'],
                            'totalprice'=>$og['totalprice'],
                        ];
                    }
                    $postzc =[
                        'invitecode' => $order['discount_code_zc'],
                        'order'=> [
                            'ordernum' => $order['ordernum'],
                            'totalprice' => $order['totalprice'],
                            'product_price' => $order['product_price'],
                            'freight_price' => $order['freight_price'],
                            'scoredk_money' => $order['scoredk_money'],
                            'leveldk_money' => $order['leveldk_money'],
                            'manjian_money' => $order['manjian_money'],
                            'coupon_money' => $order['coupon_money'],
                            'discount_money_admin' => $order['discount_money_admin'],
                            'cuxiao_money' => $order['cuxiao_money'],
                            'createtime' => $order['createtime'],
                            'linkman' => $order['linkman'],
                            'tel' => $order['tel'],
                            'area' => $order['area'],
                            'address' => $order['address'],
                            'paytime' => $order['paytime'],
                            'collect_time' => $order['collect_time'],
                        ],
                        'orderGoods'=> $postzcog
                    ];
                    $url ='https://zckl.zhoming.top/imcore/api/mall/getUserInfo';
                    $res = curl_post($url,jsonEncode($postzc),0,array('Content-Type: application/json'));
                    $res = json_decode($res,true);
                    \think\facade\Log::write('中创rs:'.jsonEncode($res));
                }
            }

            if(getcustom('member_levelup_givechild',$aid)){
                foreach($oglist as $og){
                $product = Db::name('shop_product')->where('id',$og['proid'])->field('id,give_team_levelup,team_levelup_data')->find();
                    if($product['give_team_levelup'] == 1){
                        \app\common\Member::addMemberLevelupNum($aid,$mid,$product['team_levelup_data']);
                    }
                }
            }
            if(getcustom('consumer_value_add',$aid)){
                //送绿色积分
                if($order['give_green_score'] > 0){
                    \app\common\Member::addgreenscore($aid,$order['mid'],$order['give_green_score'],'购买商品赠送'.t('绿色积分'),'shop_order',$order['id'],0,$order['give_maximum']);
                }
                //放入奖金池
                if($order['give_bonus_pool'] > 0){
                    \app\common\Member::addbonuspool($aid,$order['mid'],$order['give_bonus_pool'],'购买商品赠送'.t('奖金池'),'shop_order',$order['id'],0,$order['give_green_score']);
                }
                if(getcustom('green_score_reserves',$aid)){
                    //订单进入预备金
                    if($order['give_green_score_reserves']>0){
                        \app\custom\GreenScore::addgreenscorereserves($aid,$order['mid'],$order['give_green_score_reserves'],'购买商品赠送'.t('预备金'),'shop_order',$order['id']);
                    }
                }
            }

            if(getcustom('yx_farm')){
                if($order['give_farmseed'] > 0){
                    $remark = '订单ID：'.$order['id'].'赠送';
                    \app\custom\yingxiao\FarmCustom::addFert($aid,$order['mid'],'seed',$order['give_farmseed'],$remark);
                }
            }
            //查询购买用户
            $member = Db::name('member')->where('id',$order['mid'])->find();
            if($oglist && $member){
                //购物返现
                self::dealcashback($aid,$order,$oglist,$member,'collect');
            }
            if(getcustom('yx_butie_activity',$aid)){
                //消费补贴活动
                \app\custom\ButieActivity::confirmIssue($aid,$order['id']);
                if($member && !empty($member['pid'])){
                    //$butieorder = Db::name('shop_order')->where('aid',$aid)->where('mid',$member['id'])->where('status',3)->find();
                    //if(!$butieorder && $member['buynum'] < 1){
                        //推广人员购买了商品，增加对应数据
                        //\app\custom\ButieActivity::upparentButie($aid,$member['pid']);
                    //}
                    //推广人员购买了商品，增加对应数据
                    \app\custom\ButieActivity::upparentButiePro($aid,$member['pid'],$order['id'],$member['id']);
                }
            }

            //排名分红根据购买金额获取分红位置
            if(getcustom('shop_paiming_fenhong',$aid)){
                self::PaimingFenhongPoint($order);
            }
            if(getcustom('product_bonus_pool')){
                self::prodcutBonusPoolCollect($aid,$oglist,$member);
            }
            if(getcustom('invite_free',$aid)){
                if($order['is_free'] ||$member['pid']>0){
                    //处理上级邀请免单及退回自己免单余额
                    \app\custom\InviteFree::deal_free($member,$order);
                }
            }

            if(getcustom('yx_invite_cashback',$aid)){
                //邀请返现
                if($order && $oglist && $member){
                    \app\custom\OrderCustom::deal_invitecashback2($aid,$order,$oglist,$member);
                }
            }

            //恢复会员等级
            if(getcustom('member_level_down_commission',$aid) && $member['isauto_down']==1){
                if($member['up_levelid']>0){
                    $level =   Db::name('member_level')->field('id,recovery_level_proid')->where('aid',$aid)->where('id',$member['up_levelid'])->find();
                    $proids = [];
                    $isrecovery=false;
                    $recovery_level_proid = explode(',',$level['recovery_level_proid']);
                    foreach ($oglist as $og){
                        if(in_array($og['proid'],$recovery_level_proid)){
                            $isrecovery = true;
                            break;
                        }
                    }
                    if($isrecovery){
                        \app\Common\member::recovery_level($aid,$member);
                    }
                }
            }

            //增加购买次数 购买金额if(getcustom('member_tag'))
            Db::name('member')->where('aid',$aid)->where('id',$mid)->inc('buynum',1)->update();
            Db::name('member')->where('aid',$aid)->where('id',$mid)->inc('buymoney',$order['totalprice'])->update();
            //支付宝小程序交易组件订单状态同步
            if($order['platform']=='alipay' && $order['paytypeid'] == 3){
                $ordernum = $order['ordernum'];
                if(strpos($ordernum, '_')!==false){
                    $ordernum = explode('-',$ordernum)[0];
                }
                if(getcustom('alipay_plugin_trade',$aid) && $order['alipay_component_orderid']){
                    $pluginResult = \app\common\Alipay::pluginOrderConfirm($aid,$mid,$ordernum);
                }
            }
            if($order['platform'] == 'toutiao'){
                \app\common\Ttpay::pushorder($aid,$order['ordernum'],4);
            }
            if(getcustom('yx_team_yeji_manage',$aid)){
                self::teamYejiManage($aid,$member);
            }
            if(getcustom('yx_queue_free',$aid)){
                \app\custom\QueueFree::join($order,$type,'collect');
            }
            if(getcustom('yx_hongbao_queue_free',$aid)){
                \app\custom\HongbaoQueueFree::join($order,'shop');
            }
            if(getcustom('fenhong_jiaquan_bylevel',$aid)){
                //份数累加到会员
                \app\common\Fenhong::updateJiaquanCopies2member($order['id']);
            }
            $score_weishu = 0;
            if(getcustom('score_weishu',$aid)){
                $score_weishu = Db::name('admin_set')->where('aid',$aid)->value('score_weishu');
                $score_weishu = $score_weishu?$score_weishu:0;
            }
            if(getcustom('reward_business_score',$aid)){
                $reward_score = dd_money_format($order['reward_business_score'],$score_weishu);
                foreach($oglist as $og){
                    $reward_business_score_bili = Db::name('shop_product')->where('id',$og['proid'])->value('reward_business_score_bili');
                    $reward_score2 = bcmul($og['real_totalprice'],$reward_business_score_bili/100,2);
                    $reward_score = bcadd($reward_score,$reward_score2,2);
                }
                $uinfo = Db::name('admin_user')->where('aid',$aid)->where('bid',$og['bid'])->where('isadmin',1)->find();
                if($reward_score>0 && $uinfo['mid']){
                    \app\common\Member::addscore($aid,$uinfo['mid'],$reward_score,'店长奖励');
                }
            }

            if(getcustom('commission_withdraw_freeze',$aid)){
                $set = Db::name('admin_set')->field('jiedong_condtion,buy_proid,buypro_num')->where('aid',$aid)->find();
                if(in_array('1',explode(',',$set['jiedong_condtion']))){
                    $isjiedong = false;
                    $buy_proids = explode(',',str_replace('，',',',$set['buy_proid']));
                    $buypro_nums = explode(',',str_replace('，',',',$set['buypro_num']));
                    foreach ($oglist as $og){
                        if(count($buypro_nums) > 1) {
                            foreach($buy_proids as $k=>$proid){
                                $pronum = $buypro_nums[$k];
                                if(!$pronum) $pronum = 1;
                                $buynum = Db::name('shop_order_goods')->where('aid',$aid)->where('mid',$mid)->where('proid',$proid)->where('status','in','1,2,3')->sum('num');
                                if($buynum >= $pronum){
                                    $isjiedong = true;
                                }
                            }
                        }else {
                            $pronum = $buypro_nums[0];
                            if(!$pronum) $pronum = 1;
                            $buynum = 0;
                            foreach($buy_proids as $k=>$proid){
                                $buynum += Db::name('shop_order_goods')->where('aid',$aid)->where('mid',$mid)->where('proid',$proid)->where('status','in','1,2,3')->sum('num');
                                if($buynum >= $pronum){
                                    $isjiedong = true;
                                }
                            }
                        }
                    }
                    if($isjiedong){
                        Db::name('member')->where('id',$order['mid'])->update(['iscomwithdraw_freeze'=>0]);
                    }
                }
            }
            if(getcustom('mendian_upgrade',$aid)){
                $mendian_upgrade_status = Db::name('admin')->where('id',$order['aid'])->value('mendian_upgrade_status');
                //核销提成
                //$rs = \app\custom\Mendian::mendian_hexiao_ticheng($order, $this->mendian);
                if($mendian_upgrade_status){
                    \app\custom\Mendian::givecommission($order);
                    $mendian = Db::name('mendian')->where('id',$order['mdid'])->find();
                    //门店升级
                    \app\custom\Mendian::uplv($order['aid'],$mendian);

                }
            }
            if(getcustom('erp_wangdiantong',$aid)){
                //订单存在erp同步商品
                if($order['wdt_status']==1){
                    $c = new \app\custom\Wdt($order['aid'],$order['bid']);
                    $c->orderCollect($order['id']);
                }
            }

            if(getcustom('ciruikang_fenxiao',$aid)){
                if($order['crk_givenum']>0){
                    //增加赠送发放数量
                    Db::name('member')->where('id', $order['mid'])->inc('crk_up_send_pronum',$order['crk_givenum'])->update();
                }
                //增加商品库存
                \app\custom\CiruikangCustom::deal_ogstock3($order['aid'],$order['mid'],$order['id']);
                //处理推荐商家补贴
                \app\custom\CiruikangCustom::deal_recom_btmoney($order);
            }

            if(getcustom('yx_mangfan',$aid)){
                \app\custom\Mangfan::sendBonus($order['aid'],$order['mid'],$order['id']);
            }
            if(getcustom('member_goldmoney_silvermoney',$aid)){
                if($order['givesilvermoney']>0 || $order['givegoldmoney']>0){
                    $ShopSendSilvermoney = $ShopSendGoldmoney = true;//赠送金值银值权限
                    //平台权限
                    $admin_user = Db::name('admin_user')->where('aid',$order['aid'])->where('isadmin','>',0)->field('auth_type,auth_data')->find();
                    if($admin_user['auth_type'] !=1 ){
                        $admin_auth = !empty($admin_user['auth_data'])?json_decode($admin_user['auth_data'],true):[];
                        if(!in_array('ShopSendSilvermoney,ShopSendSilvermoney',$admin_auth)){
                            $ShopSendSilvermoney = false;
                        }
                        if(!in_array('ShopSendGoldmoney,ShopSendGoldmoney',$admin_auth)){
                            $ShopSendGoldmoney   = false;
                        }
                    }
                    if($ShopSendSilvermoney && $order['givesilvermoney'] > 0) {
                        \app\common\Member::addsilvermoney($order['aid'],$order['mid'],$order['givesilvermoney'],'购买商品赠送'.$order['ordernum'],$order['ordernum']);
                    }
                    if($ShopSendGoldmoney && $order['givegoldmoney'] > 0) {
                        \app\common\Member::addgoldmoney($order['aid'],$order['mid'],$order['givegoldmoney'],'购买商品赠送'.$order['ordernum'],$order['ordernum']);
                    }
                }
            }
            if(getcustom('extend_invite_redpacket',$aid)){
                //发放邀新红包
                \app\custom\InviteRedpacketCustom::sendredpacket($member,$order,$oglist);
            }

            if(getcustom('bonus_pool_gold')){
                //思明定制奖金池
                Db::name('shop_order')->where('id',$order['id'])->update(['status'=>3]);
                \app\custom\BonusPoolGold::orderBonusPool($order['aid'],$order['mid'],$order['id'],'shop');
            }
            if(getcustom('member_dedamount',$aid)){
                if($order['product_price']>0){
                    //消费送抵扣金
                    $dedamountset = Db::name('admin_set')->where('aid',$order['aid'])->field('dedamount_fullmoney,dedamount_givemoney,dedamount_type,dedamount_type2')->find();
                    if($dedamountset['dedamount_fullmoney']>0 && $dedamountset['dedamount_givemoney']>0){
                        $canadd = true;
                         //判断消费赠送类型二 0 全部 1：仅商城 2：仅买单
                        if($dedamountset['dedamount_type2'] == 2){
                            $canadd = false;
                        }else{
                            //判断消费赠送类型 0 全部 1：仅平台 2：仅商户
                            if($order['bid']>0 && $dedamountset['dedamount_type'] == 1){
                                $canadd = false;
                            }else if($order['bid']<=0 && $dedamountset['dedamount_type'] == 2){
                                $canadd = false;
                            }
                        }
                        if($canadd){
                            $givededamount = $order['product_price'] / $dedamountset['dedamount_fullmoney'] * $dedamountset['dedamount_givemoney'];
                            $givededamount = round($givededamount,2);
                            if($givededamount>0){
                                $params=['orderid'=>$order['id'],'ordernum'=>$order['ordernum'],'paytype'=>'shop'];
                                \app\common\Member::adddedamount($order['aid'],$order['bid'],$order['mid'],$givededamount,'消费赠送抵扣金',$params);
                            }
                        }
                    }
                }
            }
            if(getcustom('yx_buy_product_manren_choujiang')){
                \app\custom\ManrenChoujiang::choujiang($oglist,2);
            }
            if(getcustom('business_shopbuycashback',$aid)){
                if($order['bid']>0){
                    //查询商家返现比例设置
                    $shopbuycashback_ratio = Db::name('business')->where('id',$order['bid'])->value('shopbuycashback_ratio');
                    if($shopbuycashback_ratio && $shopbuycashback_ratio>0){
                        $backmoeny = $order['totalprice'] * $shopbuycashback_ratio/100;
                        $backmoeny = round($backmoeny,2);
                        if($backmoeny>0){
                            $res = \app\common\Member::addmoney($order['aid'],$order['mid'],$backmoeny,'商家商城商品购物返现');
                        }
                    }
                }
            }
            if(getcustom('level_business_shopbuyfenhong',$aid)){
                //发放店铺分红
                \app\common\Fenhong::business_shopbuyfenhong($aid,$order['id'],$oglist);
            }
            if(getcustom('member_forzengxcommission',$aid)){
                //佣金商品发放到冻结佣金记录中
                if($order['product_type'] && $order['product_type'] == 10){
                    if($oglist){
                        $commission = 0;
                        foreach($oglist as $og){
                            $commission += $og['sell_price'] * $og['num'];
                        }
                        unset($og);
                        if($commission>0){
                            $sendmonth = Db::name('admin_set')->where('aid',$aid)->value('gxcommission_sendmonth');
                            if($sendmonth>0){
                                \app\common\Member::addforzengxcommission($aid,$order['mid'],$commission,'shop_order',$order['id'],$sendmonth);
                            }
                        }
                    }
                }
            }


            if(getcustom('member_level_ztorder_extrareward',$aid)){
                //直推前三单额外奖励
                if($oglist && $member && !empty($member['pid'])){
                    self::ztorder_extrareward($aid,$order['id'],$oglist,$member);
                }
            }
            if(getcustom('fenhong_gudong_huiben')){
                //赠送回本股东分红额度
                \app\common\Order::giveHuibenMaximum($aid,$order['id']);
            }
			if(getcustom('product_luckyfree')){
				//幸运免单
				\app\custom\yingxiao\ProductLuckyfree::addLuckyfree($order,$oglist);
			}
            if(getcustom('yx_liandong',$aid)){
                \app\custom\Liandong::join_active($aid,$order['mid'],$order['id'],1);
            }
            if(getcustom('yx_network_help',$aid)){
                \app\custom\NetworkHelp::join_active($aid,$order['mid'],$order['id'],1);
            }
            if(getcustom('yx_buyer_subsidy',$aid)){
                $res = \app\custom\Subsidy::caclOrder($aid,$order['id'],1,'shop');
            }
            if(getcustom('yx_new_score',$aid)){
                $res = \app\custom\NewScore::caclOrder($aid,$order['id'],1,'shop');
            }
          
            if(getcustom('yx_offline_subsidies',$aid)){
                //线下补助
                \app\common\Member::offlineSubsidiesLog($aid,$order,'shop',1);
            }

            if(getcustom('yx_digital_consum',$aid)){
                $res = \app\custom\yingxiao\DigitalConsum::caclOrder($aid,$order['id'],1,'shop');
            }
            if(getcustom('yx_team_yeji_jc')){
                \app\custom\TeamYejiJc::addTeamYejiLog($order['id']);
        }
        }
        elseif($type=='collage'){
            if($order['bid']!=0){//入驻商家的货款

                $totalmoney      = 0;
                $totalcommission = 0;
                //if($order['iscommission']){
                if($order['parent1'] && $order['parent1commission'] > 0){
                    $totalcommission += $order['parent1commission'];
                }
                if($order['parent2'] && $order['parent2commission'] > 0){
                    $totalcommission += $order['parent2commission'];
                }
                if($order['parent3'] && $order['parent3commission'] > 0){
                    $totalcommission += $order['parent3commission'];
                }
                //}

                $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                if($bset['commission_kouchu'] == 0){ //不扣除佣金
                    $totalcommission = 0;
                }

                if(getcustom('yx_collage_team_in_team',$aid)){
                    $commission_teaminteam_kouchu = Db::name('business_sysset')->where('aid',$order['aid'])->value('commission_teaminteam_kouchu');
                    if($commission_teaminteam_kouchu && $commission_teaminteam_kouchu == 1){
                        //团中团奖励
                        $teaminteamlogs = Db::name('member_commission_record')->where('orderid',$order['id'])->where('isteaminteam',1)->where('type','collage')->where('commission','>',0)->where('status','>=',0)->where('status','<=',1)->where('aid',$order['aid'])->select()->toArray();
                        foreach($teaminteamlogs as $tv){
                            //$totalcommission += $tv['residue'];
                            $totalcommission += $tv['commission'];
                        }
                        unset($tv);
                    }
                }

                if(getcustom('business_agent',$aid)){
                    $lirun_cost_price = 0;
                    if($order['cost_price']>0){
                        $lirun_cost_price = $order['cost_price']*$order['num'];
                    }
                    $business_lirun = $order['totalprice']-$lirun_cost_price;
                }
                $queue_feepercent_type = 0;
                $has_yx_queue_free = 0;
                if(getcustom('yx_queue_free',$aid)){
                    $has_yx_queue_free = 1;
                }

                if(getcustom('yx_queue_free_collage',$aid) && $has_yx_queue_free){
                    $queue_free_set = Db::name('queue_free_set')->where('aid',$order['aid'])->where('bid',0)->find();
                    $b_queue_free_set = Db::name('queue_free_set')->where('aid',$order['aid'])->where('bid',$order['bid'])->find();
                    $queue_free_set['order_types'] = explode(',',$queue_free_set['order_types']);
                    if($queue_free_set && $queue_free_set['status']==1 && $b_queue_free_set['status']==1 && in_array('all',$queue_free_set['order_types']) || in_array('collage',$queue_free_set['order_types'])){
                        if($queue_free_set['feepercent_type'] == 1){
                            $queue_feepercent_type = 1;
                        }
                    }

                }

                $scoredkmoney = 0;
                if($bset['scoredk_kouchu'] == 0){
                    $scoredkmoney = 0;
                }elseif($bset['scoredk_kouchu'] == 1){ //扣除积分抵扣
                    $scoredkmoney = $order['scoredk_money'] ?? 0;
                }elseif($bset['scoredk_kouchu'] == 2){ //到商户余额
                    $businessDkMoney = $order['scoredk_money'];
                }elseif($bset['scoredk_kouchu'] == 3){ //到商户积分
                    $scoredkmoney = $order['scoredk_money'] ?? 0;
                    $businessDkScore = $order['scoredkscore'];
                }
                if(!is_null($order['business_total_money'])) {
                    $leveldkmoney = $order['leveldk_money'] ?? 0;
                    if($bset['leveldk_kouchu'] == 0){ //扣除会员抵扣
                        $leveldkmoney = 0;
                    }
                    $totalmoney = $order['business_total_money'] + $order['freight_price'] - $leveldkmoney - $scoredkmoney;
                    $platformMoney = $order['totalprice']-$totalmoney - $order['refund_money'];
                    if(getcustom('yx_queue_free_collage',$aid)){
                        $product = Db::name('collage_product')->where('id',$order['proid'])->field('queue_free_status,queue_free_rate_back')->find();
                            if($product['queue_free_status'] == 1 && $product['queue_free_rate_back']>=0){
                                $b_queue_free_set['rate_back'] =  $product['queue_free_rate_back'];
                            }
                        if($queue_feepercent_type == 1 && $totalmoney > 0 && $b_queue_free_set['rate_back'] > 0){
                            $totalmoney = $totalmoney - $totalmoney * $b_queue_free_set['rate_back'] * 0.01;
                        }
                    }
                } else {
                    /*********全部走的商品独立费率，这里的代码执行不到**********/
                    $leveldkmoney = $order['leveldk_money'] ?? 0;
                    if($bset['leveldk_kouchu'] == 0){ //扣除积分抵扣
                        $leveldkmoney = 0;
                    }

                    $oldtotalmoney = $totalmoney = $order['product_price'] + $order['freight_price'] - $order['coupon_money'] - $order['leader_money'] - $scoredkmoney - $leveldkmoney;

                    if($totalmoney > 0){
                        if(getcustom('business_deduct_cost',$aid)){
                            if($binfo && $binfo['deduct_cost'] == 1 && $order['cost_price']>0){
                                if($order['cost_price']<=$order['sell_price']){
                                    $all_cost_price = $order['cost_price'];
                                }else{
                                    $all_cost_price = $order['sell_price'];
                                }
                                //扣除成本
                                $platformMoney = ($totalmoney-$all_cost_price)*$binfo['feepercent']/100;
                            }else{
                                $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;
                            }
                        }else{
                            $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;
                        }

                        if(getcustom('business_fee_type',$aid)){
                            if($bset['business_fee_type'] == 1){
                                $platformMoney = ($order['totalprice']-$order['freight_price']) * $binfo['feepercent'] * 0.01;
                            }elseif($bset['business_fee_type'] == 2){
                                $platformMoney = $order['cost_price'] * $binfo['feepercent'] * 0.01;
                            }
                        }
                        $totalmoney = $totalmoney - $platformMoney;
                        //扣掉返利
                        if(getcustom('yx_queue_free_collage',$aid)){
                            $product = Db::name('collage_product')->where('id',$order['proid'])->field('queue_free_status,queue_free_rate_back')->find();
                            if($product['queue_free_status'] == 1 && $product['queue_free_rate_back']>=0){
                                $b_queue_free_set['rate_back'] =  $product['queue_free_rate_back'];
                            }
                            if($queue_feepercent_type == 1 && $totalmoney > 0 && $b_queue_free_set['rate_back'] > 0){
                                $totalmoney = $totalmoney - $oldtotalmoney * $b_queue_free_set['rate_back'] * 0.01;
                            }
                        }
                    }
                }

                if($order['paytypeid']==4){
                    $totalmoney = $totalmoney - $order['totalprice'];
                }
                $totalmoney -= $totalcommission;//扣除佣金
                if(!$isbusinesspay){
                    if($totalmoney < 0){
                        $bmoney = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->value('money');
                        if($bmoney + $totalmoney < 0){
                            return ['status'=>0,'msg'=>'操作失败,商家余额不足'];
                        }
                    }
                    \app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'货款，拼团订单号：'.$order['ordernum'],true,$type,$order['ordernum'],['platformMoney'=>$platformMoney,'business_lirun'=>$business_lirun]);
                }else{
                    //商家推荐分成
                    if(getcustom('business_agent',$aid)){
                        \app\common\Business::addparentcommission2($aid,$order['bid'],$totalmoney,$platformMoney,$business_lirun);
                    }else{
                        if($totalmoney > 0){
                            \app\common\Business::addparentcommission($aid,$order['bid'],$totalmoney);
                        }
                    }
                }
                //店铺加销量
                Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->inc('sales',$order['num'])->update();

                if(getcustom('business_canuseplatcoupon',$aid) && $order['coupon_money'] > 0 && $order['coupon_rid'] > 0){
                    $couponrecord = Db::name('coupon_record')->where('id',$order['coupon_rid'])->find();
                    if($couponrecord && $couponrecord['bid'] == 0){
                        $businessuserecord = [];
                        $businessuserecord['aid'] = $order['aid'];
                        $businessuserecord['bid'] = $order['bid'];
                        $businessuserecord['mid'] = $order['mid'];
                        $businessuserecord['ordertype'] = $type;
                        $businessuserecord['orderid'] = $order['id'];
                        $businessuserecord['couponrid'] = $order['coupon_rid'];
                        $businessuserecord['couponid'] = $couponrecord['couponid'];
                        $businessuserecord['couponname'] = $couponrecord['couponname'];
                        $businessuserecord['couponmoney'] = $couponrecord['money'];
                        $businessuserecord['decmoney'] = $order['coupon_money'];
                        $businessuserecord['status'] = 1;
                        $businessuserecord['createtime'] = time();
                        Db::name('coupon_businessuserecord')->insert($businessuserecord);
                        Db::name('business')->where('id',$order['bid'])->inc('couponmoney',$order['coupon_money'])->update();
                    }
                }
            }

            if(getcustom('yx_cashback_collage',$aid)){
                //处理返现
                \app\custom\OrderCustom::deal_collagecashback($aid,$order);
            }

            if(getcustom('collage_givescore_time',$aid)){
                //赠积分
                if($order['givescore1'] > 0){
                    \app\common\Member::addscore($aid,$order['mid'],$order['givescore1'],'购买拼团商品赠送'.t('积分'));
                }
            }

            if(getcustom('yx_mangfan_collage',$aid)){
                \app\custom\Mangfan::sendBonus($order['aid'],$order['mid'],$order['id'],'collage');
            }

            if(getcustom('yx_queue_free_collage',$aid)){
                \app\custom\QueueFree::join($order,$type,'collect');
            }
            if(getcustom('bonus_pool_gold')){
                //思明定制奖金池
                Db::name('collage_order')->where('id',$order['id'])->update(['status'=>3]);
                \app\custom\BonusPoolGold::orderBonusPool($order['aid'],$order['mid'],$order['id'],'collage');
            }
        }
        elseif($type=='cycle'){
            if($order['bid']!=0){//入驻商家的货款

                $totalmoney = 0;
                $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                $bset = Db::name('business_sysset')->where('aid',$aid)->find();

                if(getcustom('business_agent',$aid)){
                    $lirun_cost_price = 0;
                    if($order['cost_price']>0){
                        $lirun_cost_price = $order['cost_price']*$order['num'];
                    }
                    $business_lirun = $order['totalprice']-$lirun_cost_price;
                }
                $scoredkmoney = 0;
                if($bset['scoredk_kouchu'] == 0){
                    $scoredkmoney = 0;
                }elseif($bset['scoredk_kouchu'] == 1){ //扣除积分抵扣
                    $scoredkmoney = $order['scoredk_money'] ?? 0;
                }elseif($bset['scoredk_kouchu'] == 2){ //到商户余额
                    $businessDkMoney = $order['scoredk_money'];
                }elseif($bset['scoredk_kouchu'] == 3){ //到商户积分
                    $scoredkmoney = $order['scoredk_money'] ?? 0;
                    $businessDkScore = $order['scoredkscore'];
                }
                if(!is_null($order['business_total_money'])) {
                    $leveldkmoney = $order['leveldk_money'] ?? 0;
                    if($bset['leveldk_kouchu'] == 0){ //扣除会员抵扣
                        $leveldkmoney = 0;
                    }
                    $totalmoney = $order['business_total_money'] + $order['freight_price'] - $leveldkmoney - $scoredkmoney;
                    $platformMoney = $order['totalprice']-$totalmoney - $order['refund_money'];
                } else {
                    /*********全部走的商品独立费率，这里的代码执行不到**********/
                    $leveldkmoney = $order['leveldk_money'] ?? 0;
                    if($bset['leveldk_kouchu'] == 0){ //扣除积分抵扣
                        $leveldkmoney = 0;
                    }
                    $totalmoney = $order['product_price'] + $order['freight_price'] - $order['coupon_money'] - $order['leader_money'] - $order['discount_money_admin'] - $scoredkmoney - $leveldkmoney;
                    if($totalmoney > 0){
                        if(getcustom('business_deduct_cost',$aid)){
                            if($binfo && $binfo['deduct_cost'] == 1 && $order['cost_price']>0){
                                if($order['cost_price']<=$order['sell_price']){
                                    $all_cost_price = $order['cost_price'];
                                }else{
                                    $all_cost_price = $order['sell_price'];
                                }
                                //扣除成本
                                $platformMoney = ($totalmoney-$all_cost_price)*$binfo['feepercent']/100;
                            }else{
                                $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;
                            }
                        }else {
                            $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;
                        }
                        if(getcustom('business_fee_type',$aid)){
                            if($bset['business_fee_type'] == 1){
                                $platformMoney = ($order['totalprice']-$order['freight_price']) * $binfo['feepercent'] * 0.01;
                            }elseif($bset['business_fee_type'] == 2){
                                $platformMoney = $order['cost_price'] * $binfo['feepercent'] * 0.01;
                            }
                        }
                        $totalmoney = $totalmoney - $platformMoney;
                    }
                }

                if($order['paytypeid']==4){
                    $totalmoney = $totalmoney - $order['totalprice'];
                }
                if(!$isbusinesspay){
                    if($totalmoney < 0){
                        $bmoney = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->value('money');
                        if($bmoney + $totalmoney < 0){
                            return ['status'=>0,'msg'=>'操作失败,商家余额不足'];
                        }
                    }
                    \app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'货款，周期购订单号：'.$order['ordernum'],true,$type,$order['ordernum'],['platformMoney'=>$platformMoney,'business_lirun'=>$business_lirun]);
                }else{
                    //商家推荐分成
                    if(getcustom('business_agent',$aid)){
                        \app\common\Business::addparentcommission2($aid,$order['bid'],$totalmoney,$platformMoney,$business_lirun);
                    }else{
                        if($totalmoney > 0){
                            \app\common\Business::addparentcommission($aid,$order['bid'],$totalmoney);
                        }
                    }
                }
                //店铺加销量
                Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->inc('sales',$order['num'])->update();

                if(getcustom('business_canuseplatcoupon',$aid) && $order['coupon_money'] > 0 && $order['coupon_rid'] > 0){
                    $couponrecord = Db::name('coupon_record')->where('id',$order['coupon_rid'])->find();
                    if($couponrecord && $couponrecord['bid'] == 0){
                        $businessuserecord = [];
                        $businessuserecord['aid'] = $order['aid'];
                        $businessuserecord['bid'] = $order['bid'];
                        $businessuserecord['mid'] = $order['mid'];
                        $businessuserecord['ordertype'] = $type;
                        $businessuserecord['orderid'] = $order['id'];
                        $businessuserecord['couponrid'] = $order['coupon_rid'];
                        $businessuserecord['couponid'] = $couponrecord['couponid'];
                        $businessuserecord['couponname'] = $couponrecord['couponname'];
                        $businessuserecord['couponmoney'] = $couponrecord['money'];
                        $businessuserecord['decmoney'] = $order['coupon_money'];
                        $businessuserecord['status'] = 1;
                        $businessuserecord['createtime'] = time();
                        Db::name('coupon_businessuserecord')->insert($businessuserecord);
                        Db::name('business')->where('id',$order['bid'])->inc('couponmoney',$order['coupon_money'])->update();
                    }
                }
            }
        }
        elseif($type=='yuyue'){
            if($order['bid']!=0){//入驻商家的货款
                $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                if(getcustom('hmy_yuyue',$aid)){
                    $totalmoney = $order['totalprice']+$order['balance_price']-$order['paidan_money'];
                }else{
                    //$totalmoney = $order['product_price'];
                    //按实付金额计算
                    $totalmoney = $order['totalprice'];
                }
                if(getcustom('yuyue_money_dec') && $order['dec_money'] > 0){
                    $totalmoney = $totalmoney + $order['dec_money'];
                }
                $paidanMoney = $platformMoney = 0;
                if($binfo['feepercent'] > 0){
                    $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;
                }
                $totalmoney = max(round($totalmoney-$paidanMoney-$platformMoney,2),0);

                $totalcommission = 0;
                if($bset['commission_kouchu']==1){
                    $totalcommission = round($order['parent1commission'] + $order['parent2commission'] + $order['parent3commission'],2);
                }

                if($totalcommission>0) $totalmoney = $totalmoney - $totalcommission;

                if(!$isbusinesspay){
                    if($totalmoney < 0){
                        $bmoney = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->value('money');
                        if($bmoney + $totalmoney < 0){
                            return ['status'=>0,'msg'=>'操作失败,商家余额不足'];
                        }
                    }
                    $worker_order = Db::name('yuyue_worker_order')->where('id',$order['worker_orderid'])->find();
                    if(getcustom('hmy_yuyue',$aid)){
                        //获取师傅信息
                        $rs = \app\custom\Yuyue::getMaster($worker_order['worker_id']);
                        $worker = [];
                        $worker['realname'] =$rs['data']['name'];
                        $worker['tel'] = $rs['data']['phone']?$rs['data']['phone']:'';
                    }else{
                        $worker = Db::name('yuyue_worker')->where('id',$worker_order['worker_id'])->find();
                    }
                    \app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'服务订单/('.$worker['realname'].')'.$worker['tel'].' /:'.$order['ordernum'],true,$type,$order['ordernum'],['platformMoney'=>$platformMoney,'business_lirun'=>$business_lirun]);
                }else{
                    //商家推荐分成
                    if(getcustom('business_agent',$aid)){
                        \app\common\Business::addparentcommission2($aid,$order['bid'],$totalmoney,$platformMoney);
                    }else{
                        if($totalmoney > 0){
                            \app\common\Business::addparentcommission($aid,$order['bid'],$totalmoney);
                        }
                    }
                }
                //店铺加销量
                Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->inc('sales',$order['num'])->update();

                if(getcustom('business_canuseplatcoupon',$aid) && $order['coupon_money'] > 0 && $order['coupon_rid'] > 0){
                    $couponrecord = Db::name('coupon_record')->where('id',$order['coupon_rid'])->find();
                    if($couponrecord && $couponrecord['bid'] == 0){
                        $businessuserecord = [];
                        $businessuserecord['aid'] = $order['aid'];
                        $businessuserecord['bid'] = $order['bid'];
                        $businessuserecord['mid'] = $order['mid'];
                        $businessuserecord['ordertype'] = $type;
                        $businessuserecord['orderid'] = $order['id'];
                        $businessuserecord['couponrid'] = $order['coupon_rid'];
                        $businessuserecord['couponid'] = $couponrecord['couponid'];
                        $businessuserecord['couponname'] = $couponrecord['couponname'];
                        $businessuserecord['couponmoney'] = $couponrecord['money'];
                        $businessuserecord['decmoney'] = $order['coupon_money'];
                        $businessuserecord['status'] = 1;
                        $businessuserecord['createtime'] = time();
                        Db::name('coupon_businessuserecord')->insert($businessuserecord);
                        Db::name('business')->where('id',$order['bid'])->inc('couponmoney',$order['coupon_money'])->update();
                    }
                }
            }
            //赠积分
            if($order['givescore'] > 0){
                if($order['bid'] > 0){
                    \app\common\Business::addmemberscore($aid,$order['bid'],$order['mid'],$order['givescore'],'购买产品赠送'.t('积分'));
                }else{
                    \app\common\Member::addscore($aid,$order['mid'],$order['givescore'],'购买产品赠送'.t('积分'));
                }
            }

            //发货信息录入 微信小程序+微信支付,预约订单尾款支付同步小程序发货 尾款可通过余额支付
            if($order['platform'] == 'wx' && ($order['paytypeid'] == 2 || $order['paytypeid'] == 1)){
                \app\common\Order::wxShipping($aid,$order,'yuyue');
            }
        }
        elseif($type=='lucky_collage'){
            if($order['bid']!=0){//入驻商家的货款

                $totalcommission = 0;
                //if($order['iscommission']){
                if($order['parent1'] && $order['parent1commission'] > 0){
                    $totalcommission += $order['parent1commission'];
                }
                if($order['parent2'] && $order['parent2commission'] > 0){
                    $totalcommission += $order['parent2commission'];
                }
                if($order['parent3'] && $order['parent3commission'] > 0){
                    $totalcommission += $order['parent3commission'];
                }
                //}
                $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                if($bset['commission_kouchu'] == 0){ //不扣除佣金
                    $totalcommission = 0;
                }

                $scoredkmoney = 0;
                if($bset['scoredk_kouchu'] == 0){
                    $scoredkmoney = 0;
                }elseif($bset['scoredk_kouchu'] == 1){ //扣除积分抵扣
                    $scoredkmoney = $order['scoredk_money'] ?? 0;
                }elseif($bset['scoredk_kouchu'] == 2){ //到商户余额
                    $businessDkMoney = $order['scoredk_money'];
                }elseif($bset['scoredk_kouchu'] == 3){ //到商户积分
                    $scoredkmoney = $order['scoredk_money'] ?? 0;
                    $businessDkScore = $order['scoredkscore'];
                }

                $leveldkmoney = $order['leveldk_money'] ?? 0;
                if($bset['leveldk_kouchu'] == 0){ //扣除积分抵扣
                    $leveldkmoney = 0;
                }

                $totalmoney = $order['product_price'] - $order['coupon_money'] - $order['leader_money'] - $totalcommission - $scoredkmoney - $leveldkmoney;
                if($totalmoney > 0){
                    $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01 ;
                    $totalmoney = $totalmoney - $platformMoney + $order['freight_price'];
                }

                if($order['paytypeid']==4){
                    $totalmoney = $totalmoney - $order['totalprice'];
                }
                if(!$isbusinesspay){
                    if($totalmoney < 0){
                        $bmoney = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->value('money');
                        if($bmoney + $totalmoney < 0){
                            return ['status'=>0,'msg'=>'操作失败,商家余额不足'];
                        }
                    }
                    \app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'货款，'.t('幸运拼团').'订单号：'.$order['ordernum'],true,$type,$order['ordernum'],['platformMoney'=>$platformMoney]);
                }else{
                    //商家推荐分成
                    if(getcustom('business_agent',$aid)){
                        \app\common\Business::addparentcommission2($aid,$order['bid'],$totalmoney,$platformMoney);
                    }else{
                        if($totalmoney > 0){
                            \app\common\Business::addparentcommission($aid,$order['bid'],$totalmoney);
                        }
                    }
                }
                //店铺加销量
                Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->inc('sales',$order['num'])->update();

                if(getcustom('business_canuseplatcoupon',$aid) && $order['coupon_money'] > 0 && $order['coupon_rid'] > 0){
                    $couponrecord = Db::name('coupon_record')->where('id',$order['coupon_rid'])->find();
                    if($couponrecord && $couponrecord['bid'] == 0){
                        $businessuserecord = [];
                        $businessuserecord['aid'] = $order['aid'];
                        $businessuserecord['bid'] = $order['bid'];
                        $businessuserecord['mid'] = $order['mid'];
                        $businessuserecord['ordertype'] = $type;
                        $businessuserecord['orderid'] = $order['id'];
                        $businessuserecord['couponrid'] = $order['coupon_rid'];
                        $businessuserecord['couponid'] = $couponrecord['couponid'];
                        $businessuserecord['couponname'] = $couponrecord['couponname'];
                        $businessuserecord['couponmoney'] = $couponrecord['money'];
                        $businessuserecord['decmoney'] = $order['coupon_money'];
                        $businessuserecord['status'] = 1;
                        $businessuserecord['createtime'] = time();
                        Db::name('coupon_businessuserecord')->insert($businessuserecord);
                        Db::name('business')->where('id',$order['bid'])->inc('couponmoney',$order['coupon_money'])->update();
                    }
                }
            }
        }
        elseif($type=='kanjia'){
            if($order['bid']!=0){//入驻商家的货款
                $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                $scoredkmoney = 0;
                if($bset['scoredk_kouchu'] == 0){
                    $scoredkmoney = 0;
                }elseif($bset['scoredk_kouchu'] == 1){ //扣除积分抵扣
                    $scoredkmoney = $order['scoredk_money'] ?? 0;
                }elseif($bset['scoredk_kouchu'] == 2){ //到商户余额
                    $businessDkMoney = $order['scoredk_money'];
                }elseif($bset['scoredk_kouchu'] == 3){ //到商户积分
                    $scoredkmoney = $order['scoredk_money'] ?? 0;
                    $businessDkScore = $order['scoredkscore'];
                }
                if(!is_null($order['business_total_money'])) {
//                    $totalmoney = $order['business_total_money'] - $scoredkmoney + $order['freight_price'];
                    //202522发现 -$scoredkmoney已经在创建订单时减过了
                    $totalmoney = $order['business_total_money'] + $order['freight_price'];
                    $platformMoney = $order['totalprice']-$totalmoney - $order['refund_money'];
                } else {
                    /*********全部走的商品独立费率，这里的代码执行不到**********/
                    $totalmoney = $order['product_price'] + $order['freight_price'] - $scoredkmoney;
                    if($totalmoney > 0){
                        $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;
                        $totalmoney = $totalmoney - $platformMoney;
                    }
                }

                if($order['paytypeid']==4){
                    $totalmoney = $totalmoney - $order['totalprice'];
                }
                if(!$isbusinesspay){
                    if($totalmoney < 0){
                        $bmoney = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->value('money');
                        if($bmoney + $totalmoney < 0){
                            return ['status'=>0,'msg'=>'操作失败,商家余额不足'];
                        }
                    }
                    \app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'货款，砍价订单号：'.$order['ordernum'],true,$type,$order['ordernum'],['platformMoney'=>$platformMoney]);
                }else{
                    //商家推荐分成
                    if(getcustom('business_agent',$aid)){
                        \app\common\Business::addparentcommission2($aid,$order['bid'],$totalmoney,$platformMoney);
                    }else{
                        if($totalmoney > 0){
                            \app\common\Business::addparentcommission($aid,$order['bid'],$totalmoney);
                        }
                    }
                }
                //店铺加销量
                Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->inc('sales',$order['num'])->update();

                if(getcustom('business_canuseplatcoupon',$aid) && $order['coupon_money'] > 0 && $order['coupon_rid'] > 0){
                    $couponrecord = Db::name('coupon_record')->where('id',$order['coupon_rid'])->find();
                    if($couponrecord && $couponrecord['bid'] == 0){
                        $businessuserecord = [];
                        $businessuserecord['aid'] = $order['aid'];
                        $businessuserecord['bid'] = $order['bid'];
                        $businessuserecord['mid'] = $order['mid'];
                        $businessuserecord['ordertype'] = $type;
                        $businessuserecord['orderid'] = $order['id'];
                        $businessuserecord['couponrid'] = $order['coupon_rid'];
                        $businessuserecord['couponid'] = $couponrecord['couponid'];
                        $businessuserecord['couponname'] = $couponrecord['couponname'];
                        $businessuserecord['couponmoney'] = $couponrecord['money'];
                        $businessuserecord['decmoney'] = $order['coupon_money'];
                        $businessuserecord['status'] = 1;
                        $businessuserecord['createtime'] = time();
                        Db::name('coupon_businessuserecord')->insert($businessuserecord);
                        Db::name('business')->where('id',$order['bid'])->inc('couponmoney',$order['coupon_money'])->update();
                    }
                }
            }
        }
        elseif($type=='seckill'){
            if($order['bid']!=0){//入驻商家的货款

                $totalcommission = 0;
                if($order['parent1'] && $order['parent1commission'] > 0){
                    $totalcommission += $order['parent1commission'];
                }
                if($order['parent2'] && $order['parent2commission'] > 0){
                    $totalcommission += $order['parent2commission'];
                }
                if($order['parent3'] && $order['parent3commission'] > 0){
                    $totalcommission += $order['parent3commission'];
                }
                $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                if($bset['commission_kouchu'] == 0){ //不扣除佣金
                    $totalcommission = 0;
                }

                if(getcustom('business_agent',$aid)){
                    $lirun_cost_price = 0;
                    if($order['cost_price']>0){
                        $lirun_cost_price = $order['cost_price']*$order['num'];
                    }
                    $business_lirun = $order['totalprice'] - $order['refund_money'] - $lirun_cost_price;
                }
                $scoredkmoney = 0;
                if($bset['scoredk_kouchu'] == 0){
                    $scoredkmoney = 0;
                }elseif($bset['scoredk_kouchu'] == 1){ //扣除积分抵扣
                    $scoredkmoney = $order['scoredk_money'] ?? 0;
                }elseif($bset['scoredk_kouchu'] == 2){ //到商户余额
                    $businessDkMoney = $order['scoredk_money'];
                }elseif($bset['scoredk_kouchu'] == 3){ //到商户积分
                    $scoredkmoney = $order['scoredk_money'] ?? 0;
                    $businessDkScore = $order['scoredkscore'];
                }
                if(!is_null($order['business_total_money'])) {
                    $leveldkmoney = $order['leveldk_money'] ?? 0;
                    if($bset['leveldk_kouchu'] == 0){ //扣除会员抵扣
                        $leveldkmoney = 0;
                    }
                    //20251222发现 -$scoredkmoney已经在创建订单时减过了
//                    $totalmoney = $order['business_total_money'] - $totalcommission - $leveldkmoney - $scoredkmoney + $order['freight_price'];
                    $totalmoney = $order['business_total_money'] - $totalcommission - $leveldkmoney  + $order['freight_price'];
                    $platformMoney = $order['totalprice']-$totalmoney - $order['refund_money'];
                } else {
                    /*********全部走的商品独立费率，这里的代码执行不到**********/
                    $leveldkmoney = $order['leveldk_money'] ?? 0;
                    if($bset['leveldk_kouchu'] == 0){ //扣除积分抵扣
                        $leveldkmoney = 0;
                    }
                    $totalmoney = $order['product_price'] + $order['freight_price'] - $order['coupon_money'] - $order['manjia_money'] - $totalcommission - $scoredkmoney - $leveldkmoney;
                    if($totalmoney > 0){
                        if(getcustom('business_deduct_cost',$aid)){
                            if($binfo && $binfo['deduct_cost'] == 1 && $order['cost_price']>0){
                                if($order['cost_price']<=$order['sell_price']){
                                    $all_cost_price = $order['cost_price'];
                                }else{
                                    $all_cost_price = $order['sell_price'];
                                }
                                //扣除成本
                                $platformMoney = ($totalmoney-$all_cost_price)*$binfo['feepercent']/100;
                            }else{
                                $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;
                            }

                        }else{
                            $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;

                        }
                        if(getcustom('business_fee_type',$aid)){
                            if($bset['business_fee_type'] == 1){
                                $platformMoney = ($order['totalprice']-$order['freight_price']) * $binfo['feepercent'] * 0.01;
                            }elseif($bset['business_fee_type'] == 2){
                                $platformMoney = $order['cost_price'] * $binfo['feepercent'] * 0.01;
                            }
                        }
                        $totalmoney = $totalmoney - $platformMoney;
                    }
                }

                if($order['paytypeid']==4){
                    $totalmoney = $totalmoney - $order['totalprice'];
                }
                if(!$isbusinesspay){
                    if($totalmoney < 0){
                        $bmoney = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->value('money');
                        if($bmoney + $totalmoney < 0){
                            return ['status'=>0,'msg'=>'操作失败,商家余额不足'];
                        }
                    }
                    \app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'货款，秒杀订单号：'.$order['ordernum'],true,$type,$order['ordernum'],['platformMoney'=>$platformMoney,'business_lirun'=>$business_lirun]);
                }else{
                    //商家推荐分成
                    if(getcustom('business_agent',$aid)){
                        \app\common\Business::addparentcommission2($aid,$order['bid'],$totalmoney,$platformMoney,$business_lirun);
                    }else{
                        if($totalmoney > 0){
                            \app\common\Business::addparentcommission($aid,$order['bid'],$totalmoney);
                        }
                    }
                }
                //店铺加销量
                Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->inc('sales',$order['num'])->update();

                if(getcustom('business_canuseplatcoupon',$aid) && $order['coupon_money'] > 0 && $order['coupon_rid'] > 0){
                    $couponrecord = Db::name('coupon_record')->where('id',$order['coupon_rid'])->find();
                    if($couponrecord && $couponrecord['bid'] == 0){
                        $businessuserecord = [];
                        $businessuserecord['aid'] = $order['aid'];
                        $businessuserecord['bid'] = $order['bid'];
                        $businessuserecord['mid'] = $order['mid'];
                        $businessuserecord['ordertype'] = $type;
                        $businessuserecord['orderid'] = $order['id'];
                        $businessuserecord['couponrid'] = $order['coupon_rid'];
                        $businessuserecord['couponid'] = $couponrecord['couponid'];
                        $businessuserecord['couponname'] = $couponrecord['couponname'];
                        $businessuserecord['couponmoney'] = $couponrecord['money'];
                        $businessuserecord['decmoney'] = $order['coupon_money'];
                        $businessuserecord['status'] = 1;
                        $businessuserecord['createtime'] = time();
                        Db::name('coupon_businessuserecord')->insert($businessuserecord);
                        Db::name('business')->where('id',$order['bid'])->inc('couponmoney',$order['coupon_money'])->update();
                    }
                }
            }
            //赠积分
            if($order['givescore'] > 0){
                \app\common\Member::addscore($aid,$order['mid'],$order['givescore'],'购买产品赠送'.t('积分'));
            }
            if(getcustom('yx_queue_free_seckill',$aid)){
                \app\custom\QueueFree::join($order,$type,'collect');
            }
            if(getcustom('yx_cashback_seckill')){
                $join_cashback_status = Db::name('seckill_product')->where('aid',$aid)->where('bid',$order['bid'])->where('id',$order['proid'])->value('join_cashback_status');
                if($join_cashback_status){
                    \app\custom\OrderCustom::deal_seckillcashback($aid,$order);
                }
            }
        }
        elseif($type=='coupon'){
            if($order['bid']!=0){//入驻商家的货款
                $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                $totalmoney = $order['price'];
                if($totalmoney > 0){
                    $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;
                    $totalmoney = $totalmoney - $platformMoney;
                }
                if(!$isbusinesspay){
                    \app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'销售'.t('优惠券').' 订单号：'.$order['ordernum'],true,$type,$order['ordernum'],['platformMoney'=>$platformMoney]);
                }else{
                    //商家推荐分成
                    if(getcustom('business_agent',$aid)){
                        \app\common\Business::addparentcommission2($aid,$order['bid'],$totalmoney,$platformMoney);
                    }else{
                        if($totalmoney > 0){
                            \app\common\Business::addparentcommission($aid,$order['bid'],$totalmoney);
                        }
                    }
                }
            }
        }
        elseif($type=='maidan'){
			$totalcommission = 0;
            $maidanfenxiao = Db::name('admin_set')->where('aid',$aid)->value('maidanfenxiao');
            if(getcustom('maidan_qrcode',$aid)) {
                if($order['ymid'] && $order['ymid']>0){
                    $maidanfenxiao = 0;
                }
            }

            if(getcustom('business_shopbuycashback',$aid)){
                if($order['bid']>0){
                    //查询商家返现比例设置
                    $shopbuycashback_ratio = Db::name('business')->where('id',$order['bid'])->value('shopbuycashback_ratio');
                    if($shopbuycashback_ratio && $shopbuycashback_ratio>0){
                        $backmoeny = $order['paymoney'] * $shopbuycashback_ratio/100;
                        $backmoeny = round($backmoeny,2);
                        if($backmoeny>0){
                            $res = \app\common\Member::addmoney($order['aid'],$order['mid'],$backmoeny,'商家商城购物返现');
                        }
                    }
                }
            }
            if(getcustom('level_business_shopbuyfenhong',$aid)){
                //发放店铺分红
                $ogdata = $order;
                $ogdata['real_totalprice'] = $ogdata['paymoney'];
                $ogdata['module'] = 'maidan';
                $ogdata['cost_price'] = 0;
                $ogdata['num']    = 0;
                $ogdatas = [$ogdata];
                \app\common\Fenhong::business_shopbuyfenhong($aid,$order['id'],$ogdatas,'maidan');
            }
            if(getcustom('maidan_item')){
                $maidanItem = Db::name('maidan_item')->where('id',$order['item_id'])->find();
                //判断买单项目是否开启分销
                if($maidanItem['commissionset'] == -1){
                    $maidanfenxiao = 0; //关闭系统设置分销
                }
            }
            if($maidanfenxiao == 1){ //参与分销 买单分销
                $fenxiao_paymoney = $order['paymoney'];
                $money_dec_status = 1;
                if(getcustom('money_dec_commission_fenhong',$aid)){
                    //余额抵扣部分不参与任何分销分红
                    $money_dec_status = Db::name('admin_set')->where('aid',$aid)->value('money_dec_fenxiao_fenhong');
                }
                if(getcustom('maidan_money_dec',$aid)){
                    // 加上余额抵扣部分 2025.5.28
                    if($money_dec_status){
                        if($order['dec_money'] && $order['dec_money']>0){
                            $fenxiao_paymoney += $order['dec_money'];
                        }
                    }
                }
                $member = Db::name('member')->where('aid',$aid)->where('id',$order['mid'])->find();
                $agleveldata = Db::name('member_level')->where('aid',$aid)->where('id',$member['levelid'])->find();
                if($agleveldata['can_agent'] > 0 && $agleveldata['commission1own']==1){
                    $member['pid'] = $member['id'];
                }
                $isCommissionScore = 0;
                if(getcustom('maidan_commission_score',$aid)){
                    $isCommissionScore = 1;
                }
                if(getcustom('maidan_fenhong_new',$aid)){
                    //买单分销结算方式
                    $sysset = Db::name('admin_set')->where('aid',$aid)->field('maidanfenxiao_type,maidan_cost')->find();
                    $maidanfenhong_type = $sysset['maidanfenxiao_type'];
                    if($maidanfenhong_type == 1){
                        //按利润结算时直接把销售额改成利润
                        if($order['bid']>0){
                            $maidan_cost = Db::name('business')->where('id',$order['bid'])->value('maidan_cost');
                        }else{
                            $maidan_cost = $sysset['maidan_cost'];
                        }
                        $cost_price = bcmul($fenxiao_paymoney,$maidan_cost/100,2);
                        $fenxiao_paymoney = $fenxiao_paymoney - $cost_price;
                    }
                    if(getcustom('yx_buyer_subsidy',$aid)){
                        if($maidanfenhong_type==2){
                            //按抽佣结算
                            $fenxiao_paymoney = $order['order_fee'];
                        }
                    }
                }
                if(getcustom('member_dedamount',$aid)){
                    //若开启了分销依赖抵扣金，或者商家买单使用了抵扣金抵扣，则重置分销佣金金额为让利部分的会员的抵扣金额
                    $dedamount_fenxiao = Db::name('admin_set')->where('aid',$aid)->value('dedamount_fenxiao');
                    if(($dedamount_fenxiao && $dedamount_fenxiao == 1) || ($order['bid']>0 && $order['dedamount_dkmoney']>0 )){
                        if($order['bid']>0 && $order['dedamount_dkmoney']>0 ){
                            $fenxiao_paymoney = $order['dedamount_dkmoney'];
                        }else{
                            $fenxiao_paymoney = 0;
                        }
                    }
                }
                //买单分销 -1关闭 0:跟随系统更加（默认） 1:单独设置
                $commissionset = 0;
                $commissiondata1 = [];//1:单独设置时，金额提成比例
                $commissiondata2 = [];//2:单独设置时，固定金额
                if(getcustom('business_maidan_commission',$aid)){
                    if($order['bid'] > 0){
                        //查询商户信息
                        $business = Db::name('business')
                            ->field('id,maidan_commissionset,maidan_commissiondata1')
                            ->where('aid',$aid)
                            ->where('id',$order['bid'])
                            ->where('status',1)
                            ->find();
                        $commissionset  = $business['maidan_commissionset'];
                        $commissiondata1 = !empty($business['maidan_commissiondata1'])?json_decode($business['maidan_commissiondata1'],true):'';
                    }
                }
                if(getcustom('maidan_item') && isset($maidanItem)){
                    $commissionset = $maidanItem['commissionset'];
                    if($commissionset == 1 && $maidanItem['commissiondata1']){
                        $commissiondata1 = json_decode($maidanItem['commissiondata1'],true);
                    }
                    if($commissionset == 2 && $maidanItem['commissiondata2']){
                        $commissiondata2 = json_decode($maidanItem['commissiondata2'],true);
                    }
                }
                $ogdata = [];
                //是否积分提成
                $ogdata['isparent1score'] = 0;
                $ogdata['isparent2score'] = 0;
                $ogdata['isparent3score'] = 0;
                if($member['pid']){
                    $parent1 = Db::name('member')->where('aid',$aid)->where('id',$member['pid'])->find();
                    if($parent1){
                        $agleveldata1 = Db::name('member_level')->where('aid',$aid)->where('id',$parent1['levelid'])->find();
                        if($agleveldata1['can_agent']!=0){
                            $ogdata['parent1'] = $parent1['id'];
                            if($isCommissionScore && $agleveldata1['maidan_commission_score1']>0){
                                $ogdata['isparent1score'] = 1;
                                $ogdata['parent1score'] = round($agleveldata1['maidan_commission_score1'] * $fenxiao_paymoney * 0.01);
                            }
                        }
                    }
                }
                if($parent1['pid']){
                    $parent2 = Db::name('member')->where('aid',$aid)->where('id',$parent1['pid'])->find();
                    if($parent2){
                        $agleveldata2 = Db::name('member_level')->where('aid',$aid)->where('id',$parent2['levelid'])->find();
                        if($agleveldata2['can_agent']>1){
                            $ogdata['parent2'] = $parent2['id'];
                            if($isCommissionScore && $agleveldata2['maidan_commission_score2']>0){
                                $ogdata['isparent2score'] = 1;
                                $ogdata['parent2score'] = round($agleveldata2['maidan_commission_score2'] * $fenxiao_paymoney * 0.01);
                            }
                        }
                    }
                }
                if($parent2['pid']){
                    $parent3 = Db::name('member')->where('aid',$aid)->where('id',$parent2['pid'])->find();
                    if($parent3){
                        $agleveldata3 = Db::name('member_level')->where('aid',$aid)->where('id',$parent3['levelid'])->find();
                        if($agleveldata3['can_agent']>2){
                            $ogdata['parent3'] = $parent3['id'];
                            if($isCommissionScore &&  $agleveldata3['maidan_commission_score3']>0){
                                $ogdata['isparent3score'] = 1;
                                $ogdata['parent3score'] = round($agleveldata3['maidan_commission_score3'] * $fenxiao_paymoney * 0.01);
                            }
                        }
                    }
                }
                //买单分销
                if($commissionset != -1){
                    //单独设置
                    if($commissionset == 1){
                        //提成比例
                        if($commissiondata1){
                            if($agleveldata1) $ogdata['parent1commission'] = $commissiondata1[$agleveldata1['id']]['commission1'] * $fenxiao_paymoney * 0.01;
                            if($agleveldata2) $ogdata['parent2commission'] = $commissiondata1[$agleveldata2['id']]['commission2'] * $fenxiao_paymoney * 0.01;
                            if($agleveldata3) $ogdata['parent3commission'] = $commissiondata1[$agleveldata3['id']]['commission3'] * $fenxiao_paymoney * 0.01;
                        }
                    }else if($commissionset == 2){
                        //固定金额
                        if($commissiondata2){
                            if($agleveldata1) $ogdata['parent1commission'] = $commissiondata2[$agleveldata1['id']]['commission1'];
                            if($agleveldata2) $ogdata['parent2commission'] = $commissiondata2[$agleveldata2['id']]['commission2'];
                            if($agleveldata3) $ogdata['parent3commission'] = $commissiondata2[$agleveldata3['id']]['commission3'];
                        }
                    }else if($commissionset == 0){
                        //会员等级 分销设置
                        if($agleveldata1['commissiontype']==1){ //固定金额按单
                            $ogdata['parent1commission'] = $agleveldata1['commission1'];
                        }else{
                            $ogdata['parent1commission'] = $agleveldata1['commission1'] * $fenxiao_paymoney * 0.01;
                        }
                        if($agleveldata2['commissiontype']==1){ //固定金额按单
                            $ogdata['parent2commission'] = $agleveldata2['commission2'];
                        }else{
                            $ogdata['parent2commission'] = $agleveldata2['commission2'] * $fenxiao_paymoney * 0.01;
                        }
                        if($agleveldata3['commissiontype']==1){ //固定金额按单
                            $ogdata['parent3commission'] = $agleveldata3['commission3'];
                        }else{
                            $ogdata['parent3commission'] = $agleveldata3['commission3'] * $fenxiao_paymoney * 0.01;
                        }
                    }
                }
                //分销分红发放钱包控制
                $fxfh_send_wallet = 0;
                $fxfh_send_wallet_levelids = [];
                if(getcustom('commission_send_wallet',$aid)){
                    //定制分销分红发放钱包 0到佣金 1到余额
                    $admin_set = Db::name('admin_set')->where('aid',$aid)->field('commission_send_wallet,commission_send_wallet_levelids')->find();
                    $fxfh_send_wallet = $admin_set['commission_send_wallet'];
                    $fxfh_send_wallet_levelids = explode(',',$admin_set['commission_send_wallet_levelids']);
                    $fxfh_send_wallet_levelids = array_filter($fxfh_send_wallet_levelids);
                    if(empty($fxfh_send_wallet_levelids)){
                        $fxfh_send_wallet_levelids = ['-1'];
                    }
                }

                if(getcustom('commission_log_remark_custom',$aid)){
                    if($order['bid'] > 0){
                        $bname = Db::name('business')->where('id',$order['bid'])->value('name');
                    }else{
                        $bname = Db::name('admin_set')->where('aid',$order['aid'])->value('name');
                    }
                    $nickname = Db::name('member')->where('aid',$order['aid'])->where('id',$mid)->value('nickname');
                }
                if(getcustom('pay_huifu_fenzhang')){
                    $huifu_business_status = 0;
                    if($order['bid'] > 0){
                        $businessdata = Db::name('business')->where('aid',$order['aid'])->where('id',$order['bid'])->field('huifu_business_status')->find();
                        $huifu_business_status = $businessdata['huifu_business_status'];//汇付独立收款
                        $huifu_send_commission = $businessdata['huifu_send_commission'];//汇付分账发放佣金
                        $delay_acct_flag = $businessdata['delay_acct_flag']; //分账类型 0实时
                        //上部分上级满足条件发放，其他这里触发佣金的 分账
                        $huifu_log = Db::name('huifu_log')->where('aid',$order['aid'])->where('bid',$order['bid'])->whereNotNull('fenzhangdata')->where('isfenzhang',0)->where('pay_status',1)->where('fenzhangdata','NOT NULL',null)->where('tablename','maidan')->where('ordernum',$order['ordernum'])->find();
                        if($huifu_log){
                            $huifuset = Db::name('sysset')->where('name','huifuset')->find();
                            if(getcustom('pay_huifu_fenzhang_backstage')){
                                //后台独立设置服务商
                                $huifuset_backstage = Db::name('admin_set')->where('aid',$order['aid'])->value('huifuset');
                                if($huifuset_backstage){
                                    $huifuset = ['value'=>$huifuset_backstage];
                                }
                            }
                            $appinfo = json_decode($huifuset['value'],true);
                            $aid = $huifu_log['aid'];
                            $bid = $huifu_log['bid'];
                            $mid = $huifu_log['mid'];
                            $huifu = new \app\custom\Huifu($appinfo,$aid,$bid,$mid,'分账',$huifu_log['ordernum']);
                            $huifu -> comfirmTrade($huifu_log);
                        }
                    }
                }
                if($ogdata['parent1'] && $ogdata['isparent1score']==1){
                    $ogdata['parent1score']>0 && \app\common\Member::addscore($aid,$ogdata['parent1'],$ogdata['parent1score'],'下级买单'.t('积分').'奖励');
                }elseif($ogdata['parent1'] && $ogdata['isparent1score']==0 && $ogdata['parent1commission'] > 0){
                    $remark1 =  '下级买单收款奖励';
                    if(getcustom('commission_log_remark_custom',$aid)){
                        $remark1 = '下级'.$nickname.'在'.$bname.'消费'.$order['money'].'元';
                    }
                    $totalcommission+=$ogdata['parent1commission'];
                    $is_send_commission1 = 1; //是否增加佣金，为了汇付的分账
                    if(getcustom('pay_huifu_fenzhang')){
                        if($huifu_business_status && $huifu_log && $huifu_send_commission && $delay_acct_flag ==1){
                            $parent1_huifu_id = Db::name('member')->where('aid',$order['aid'])->where('id',$ogdata['parent1'])->value('huifu_id');
                            if($parent1_huifu_id ) $is_send_commission1 = 0;
                        }
                    }
                    if($is_send_commission1){
                        //\app\common\Member::addcommission($aid,$ogdata['parent1'],$mid,$ogdata['parent1commission'],$remark1);

                        $fxfh_send_money = 0;
                        if($fxfh_send_wallet==1){
                            //分销分红发放钱包等级限制
                            $member_levelid = Db::name('member')->where('id',$ogdata['parent1'])->value('levelid');
                            if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                                $fxfh_send_money = 1;
                            }
                        }
                        if($fxfh_send_money==1){
                            //发放到余额
                            \app\common\Member::addmoney($aid,$ogdata['parent1'],$ogdata['parent1commission'],$remark1);
                        }else{
                            \app\common\Member::addcommission($aid,$ogdata['parent1'],$mid,$ogdata['parent1commission'],$remark1,1,'fenxiao');
                        }
                    }
                    Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$ogdata['parent1'],'frommid'=>$order['mid'],'orderid'=>$order['id'],'type'=>'maidan','commission'=>$ogdata['parent1commission'],'remark'=>$remark1,'createtime'=>time(),'status'=>1,'endtime'=>time()]);
                }
                if($ogdata['parent2'] && $ogdata['isparent2score']==1){
                    $ogdata['parent2score']>0 && \app\common\Member::addscore($aid,$ogdata['parent2'],$ogdata['parent2score'],'下二级买单'.t('积分').'奖励');
                }elseif($ogdata['parent2'] && $ogdata['isparent2score']==0 && $ogdata['parent2commission'] > 0){
                    $remark2 = '下二级买单收款奖励';
                    if(getcustom('commission_log_remark_custom',$aid)){
                        $remark2 = '下二级'.$nickname.'在'.$bname.'消费'.$order['money'].'元';
                    }
                    $totalcommission+=$ogdata['parent2commission'];
                    $is_send_commission2 = 1; //是否增加佣金，为了汇付的分账
                    if(getcustom('pay_huifu_fenzhang')){
                        if($huifu_business_status && $huifu_log && $huifu_send_commission && $delay_acct_flag ==1){
                            $parent2_huifu_id = Db::name('member')->where('aid',$order['aid'])->where('id',$ogdata['parent2'])->value('huifu_id');
                            if($parent2_huifu_id ) $is_send_commission2 = 0;
                        }
                    }
                    if($is_send_commission2){
                        //\app\common\Member::addcommission($aid,$ogdata['parent2'],$mid,$ogdata['parent2commission'],$remark2);

                        $fxfh_send_money = 0;
                        if($fxfh_send_wallet==1){
                            //分销分红发放钱包等级限制
                            $member_levelid = Db::name('member')->where('id',$ogdata['parent2'])->value('levelid');
                            if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                                $fxfh_send_money = 1;
                            }
                        }
                        if($fxfh_send_money==1){
                            //发放到余额
                            \app\common\Member::addmoney($aid,$ogdata['parent2'],$ogdata['parent2commission'],$remark2);
                        }else{
                            \app\common\Member::addcommission($aid,$ogdata['parent2'],$mid,$ogdata['parent2commission'],$remark2,1,'fenxiao');
                        }
                    }
                    Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$ogdata['parent2'],'frommid'=>$order['mid'],'orderid'=>$order['id'],'type'=>$type,'commission'=>$ogdata['parent2commission'],'remark'=>$remark2,'createtime'=>time(),'status'=>1,'endtime'=>time()]);
                }
                if($ogdata['parent3'] && $ogdata['isparent3score']==1){
                    $ogdata['parent3score']>0 && \app\common\Member::addscore($aid,$ogdata['parent3'],$ogdata['parent3score'],'下三级买单'.t('积分').'奖励');
                }elseif($ogdata['parent3'] && $ogdata['isparent3score']==0 && $ogdata['parent3commission'] > 0){
                    $totalcommission+=$ogdata['parent3commission'];
                    $remark3 = '下三级买单收款奖励';
                    if(getcustom('commission_log_remark_custom',$aid)){
                        $remark3 = '下三级'.$nickname.'在'.$bname.'消费'.$order['money'].'元';
                    }
                    $is_send_commission3 = 1; //是否增加佣金，为了汇付的分账
                    if(getcustom('pay_huifu_fenzhang')){
                         if($huifu_business_status && $huifu_log && $huifu_send_commission && $delay_acct_flag ==1){
                             $parent3_huifu_id = Db::name('member')->where('aid',$order['aid'])->where('id',$ogdata['parent3'])->value('huifu_id');
                             if($parent3_huifu_id ) $is_send_commission3 = 0;
                         }
                    }
                    if($is_send_commission3){
                       // \app\common\Member::addcommission($aid,$ogdata['parent3'],$mid,$ogdata['parent3commission'],$remark3);

                        $fxfh_send_money = 0;
                        if($fxfh_send_wallet==1){
                            //分销分红发放钱包等级限制
                            $member_levelid = Db::name('member')->where('id',$ogdata['parent3'])->value('levelid');
                            if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                                $fxfh_send_money = 1;
                            }
                        }
                        if($fxfh_send_money==1){
                            //发放到余额
                            \app\common\Member::addmoney($aid,$ogdata['parent3'],$ogdata['parent3commission'],$remark2);
                        }else{
                            \app\common\Member::addcommission($aid,$ogdata['parent3'],$mid,$ogdata['parent3commission'],$remark2,1,'fenxiao');
                        }
                    }
                    Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$ogdata['parent3'],'frommid'=>$order['mid'],'orderid'=>$order['id'],'type'=>$type,'commission'=>$ogdata['parent3commission'],'remark'=>$remark3,'createtime'=>time(),'status'=>1,'endtime'=>time()]);
                }
                if($ogdata['parent1']){
                    \app\common\Member::uplv($aid,$ogdata['parent1']);
                }
                $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                if($bset['commission_kouchu'] == 0){ //不扣除佣金
                    $totalcommission = 0;
                }
            }
            if(getcustom('active_coin',$aid)){
                //先发放激活币
                self::giveActiveCoin($aid,$order,'maidan');
            }
            if(getcustom('active_score',$aid)){
                //让利积分
                self::giveActiveScore($aid,$order,'maidan');
            }
            if(getcustom('member_commission_max',$aid) && getcustom('add_commission_max',$aid)){
                //先发放佣金上限
                \app\common\Order::giveCommissionMax($aid,$order,'maidan');
            }
            $is_business_daikuai = 1;
            if(getcustom('pay_huifu_fenzhang')){
                $huifulog = Db::name('huifu_log')->where('aid',$order['aid'])->where('bid',$order['bid'])->where('ordernum',$order['ordernum'])->where('tablename','maidan')->where('pay_status',1)->where('fenzhangdata','NOT NULL',null)->find();
                if($huifulog){
                    //存在汇付支付且有分账,是独立收款，不再加入驻贷款
                    $is_business_daikuai = 0;
                }
            }
            if($order['bid']!=0 && $is_business_daikuai){//入驻商家的货款
                $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                $oldtotalmoney = $totalmoney = $order['money'] - $order['couponmoney'];
                if(getcustom('member_dedamount',$aid)){
                    $totalmoney -= $order['dedamount_dkmoney'];
                }
                if($totalmoney > 0){
                    $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;
                    $totalmoney = $totalmoney - $platformMoney - $totalcommission;//先算抽成，然后扣除佣金
                }
                if(!isset($bset)) $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                $scoredkmoney = 0;
                if($bset['scoredk_kouchu'] == 0){
                    $scoredkmoney = 0;
                }elseif($bset['scoredk_kouchu'] == 1){ //扣除积分抵扣
                    $scoredkmoney = $order['scoredk'] ?? 0;
                }elseif($bset['scoredk_kouchu'] == 2){ //到商户余额
                    $businessDkMoney = $order['scoredk'];
                }elseif($bset['scoredk_kouchu'] == 3){ //到商户积分
                    $scoredkmoney = $order['scoredk'] ?? 0;
                    $businessDkScore = $order['decscore'];
                }
                $disprice = $order['disprice']??0;//会员折扣金额
                if($bset['leveldk_kouchu'] == 0){ //扣除积分抵扣
                    $disprice = 0;
                }
                $totalmoney = $totalmoney - $scoredkmoney- $disprice;
                if(getcustom('member_shopscore')){
                    if($binfo['shopscore_kouchu'] == 1){ //扣除产品积分
                        $totalmoney -= $order['shopscoredk_money'];
                    }
                }
                if(getcustom('active_coin',$aid) && getcustom('maidan_fenhong_new',$aid)){
                    //$total_activecoin = Db::name('maidan_order')->where('id',$order['id'])->value('activecoin');
                    //$totalmoney = bcsub($totalmoney,bcmul($totalmoney,$binfo['maidan_cost']/100,2),2);
                }
                //扣除返现比例
                $queue_feepercent_type = 0;
                $queue_feepercent_allmoney = 0;
                if(getcustom('yx_queue_free',$aid)){
                    $queue_free_set = Db::name('queue_free_set')->where('aid',$order['aid'])->where('bid',0)->find();
                    $b_queue_free_set = Db::name('queue_free_set')->where('aid',$order['aid'])->where('bid',$order['bid'])->find();
                    $queue_free_set['order_types'] = explode(',',$queue_free_set['order_types']);
                    if($queue_free_set && $queue_free_set['status']==1 && $b_queue_free_set['status']==1 && in_array('all',$queue_free_set['order_types']) || in_array('maidan',$queue_free_set['order_types'])){
                        if($queue_free_set['feepercent_type'] == 1){
                            $queue_feepercent_type = 1;
                        }
                    }

                    if($queue_feepercent_type == 1  && $b_queue_free_set['rate_back'] > 0){
                        $totalmoney = $totalmoney - $oldtotalmoney * $b_queue_free_set['rate_back'] * 0.01;
                    }
                }

                if(!$isbusinesspay){
                    if(getcustom('member_dedamount',$aid)){
                        //是否开启了抽佣依赖抵扣金，开启了需要有抵扣金金额才抽成，需要重新计算商家到账货款
                        $dedamountset = Db::name('admin_set')->where('aid',$order['aid'])->field('dedamount_dkpercent,dedamount_choucheng')->find();
                        if($dedamountset && $dedamountset['dedamount_choucheng'] && $dedamountset['dedamount_choucheng']  == 1){
                            //下单让利比例大于且抵扣金额大于，则需要计算，否则直接为实付金额，不进行抽成
                            if($dedamountset['dedamount_dkpercent']>0 && $order['dedamount_dkmoney']>0){
                                //计算总未折扣、未抵扣的金额
                                $allmoney   = $order['money'];
                                //总让利
                                $allrlmoney = $order['dedamount_dkmoney']/($dedamountset['dedamount_dkpercent'] * 0.01);
                                //商家到账
                                $totalmoney = $allmoney - $allrlmoney;
                                if($totalmoney<0) $totalmoney = 0;
                            }else{
                                $totalmoney = $order['paymoney'];
                            }
                        }
                    }
                    $business_lirun = $totalmoney;
                    if(getcustom('maidan_fenhong_new',$aid)){
                        $business_lirun = $oldtotalmoney * (100 - $binfo['maidan_cost'])*0.01;
                    }

                    $isfamoney = 1;
                    if(getcustom('maidan_orderadd_mobile_paytransfer',$aid) && $order['payment_voucher_pic']){
                        $isfamoney = 0;
                    }

                    if($isfamoney == 1){
                        \app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'买单 订单号：'.$order['ordernum'],true,$type,$order['ordernum'],['platformMoney'=>$platformMoney,'business_lirun'=>$business_lirun]);
                    }
                }else{
                    //商家推荐分成
                    $business_lirun = $totalmoney;
                    if(getcustom('maidan_fenhong_new',$aid)){
                        $business_lirun = $oldtotalmoney * (100 - $binfo['maidan_cost'])*0.01;
                    }
                    if(getcustom('business_agent',$aid)){
                        \app\common\Business::addparentcommission2($aid,$order['bid'],$totalmoney,$platformMoney,$business_lirun);
                    }else{
                        if($totalmoney > 0){
                            \app\common\Business::addparentcommission($aid,$order['bid'],$totalmoney);
                        }
                    }

                    if($totalmoney > 0){

                        //重新赋值用于计算补发货款
                        $totalmoney2 = $totalmoney - $order['paymoney'] * (100-$binfo['feepercent']) * 0.01;
                        //不扣除积分抵扣，则需要给补发此积分抵扣
                        if($totalmoney2 >0 && $bset['scoredk_kouchu'] == 0){
                            $scoredkmoney = $order['scoredk'] ?? 0;
                            if($scoredkmoney>0){
                                if($scoredkmoney >= $totalmoney2){
                                    $scoredkmoney = round($totalmoney2,2);
                                    $totalmoney2 = 0;
                                }else{
                                    $scoredkmoney = round($scoredkmoney,2);
                                    $totalmoney2 -= $scoredkmoney;
                                }
                                if($scoredkmoney>0){
                                    \app\common\Business::addmoney($aid,$order['bid'],$scoredkmoney,'补发'.t('积分').'抵扣部分货款，订单号：'.$order['ordernum'],false,$type,$order['ordernum']);
                                }
                            }
                        }
                        //不扣除会员折扣金额，则需要给补发此会员折扣金额
                        if($totalmoney2 >0 && $bset['leveldk_kouchu'] == 0){
                            $disprice = $order['disprice']??0;
                            if($disprice>0){
                                if($disprice >= $totalmoney2){
                                    $disprice = round($totalmoney2,2);
                                    $totalmoney2 = 0;
                                }else{
                                    $disprice = round($disprice,2);
                                    $totalmoney2 -= $disprice;
                                }
                                if($disprice>0){
                                    \app\common\Business::addmoney($aid,$order['bid'],$disprice,'补发'.t('会员').'折扣金额部分货款，订单号：'.$order['ordernum'],false,$type,$order['ordernum']);
                                }
                            }
                        }
                    }
                    
                    if(getcustom('maidan_money_dec')){
                        if($order['dec_money']> 0) {
                            //按商家抽成费率计算，商户独立支付模式，需要补发多少余额抵扣的部分
                            $add_dec_money = $order['dec_money'] * (100 - $binfo['feepercent']) * 0.01;
                            $add_dec_money = round($add_dec_money,2);
                            if($add_dec_money>0){
                                //补发抵扣货款
                                \app\common\Business::addmoney($aid,$order['bid'],$add_dec_money,'补发'.t('余额').'抵扣部分货款，订单号：'.$order['ordernum'],false,$type,$order['ordernum']);
                            }
                        }
                    }
                }
                if(getcustom('business_canuseplatcoupon',$aid) && $order['couponmoney'] > 0 && $order['couponrid'] > 0){
                    $couponrecord = Db::name('coupon_record')->where('id',$order['couponrid'])->find();
                    if($couponrecord && $couponrecord['bid'] == 0){
                        $businessuserecord = [];
                        $businessuserecord['aid'] = $order['aid'];
                        $businessuserecord['bid'] = $order['bid'];
                        $businessuserecord['mid'] = $order['mid'];
                        $businessuserecord['ordertype'] = $type;
                        $businessuserecord['orderid'] = $order['id'];
                        $businessuserecord['couponrid'] = $order['couponrid'];
                        $businessuserecord['couponid'] = $couponrecord['couponid'];
                        $businessuserecord['couponname'] = $couponrecord['couponname'];
                        $businessuserecord['couponmoney'] = $couponrecord['money'];
                        $businessuserecord['decmoney'] = $order['couponmoney'];
                        $businessuserecord['status'] = 1;
                        $businessuserecord['createtime'] = time();
                        Db::name('coupon_businessuserecord')->insert($businessuserecord);
                        Db::name('business')->where('id',$order['bid'])->inc('couponmoney',$order['couponmoney'])->update();
                    }
                }
            }

            if(getcustom('yx_queue_free',$aid)){
                \app\custom\QueueFree::join($order,$type,'collect');
            }

            if(getcustom('mendian_maidan_ticheng',$aid)){
                if($order['mdid']>0){
                    $mendian =  Db::name('mendian')->field('id,maidangivepercent,maidangivemoney')->where('id',$order['mdid'])->find();
                    $givemoney = 0;
                    if($mendian['maidangivepercent']>0 || $mendian['maidangivemoney']>0){
                        $givemoney += $order['paymoney'] * 0.01 * $mendian['maidangivepercent'] + $mendian['maidangivemoney'];
                        if($givemoney > 0){
                            \app\common\Mendian::addmoney($order['aid'],$mendian['id'],$givemoney,'买单提成'.$order['ordernum']);
                        }
                    }
                }

            }
            if(getcustom('business_maidan_team_fenhong')){
                self::maidanFenhongJl($order);
            }
            if(getcustom('member_dedamount',$aid)){
                //抵扣金直推奖
                self::dealDedamountZtreward($aid,$order['id'],$order,'maidan');
            }

            if(getcustom('yx_offline_subsidies',$aid)){
                //线下补助
                \app\common\Member::offlineSubsidiesLog($aid,$order,'maidan',1);
            }
            if(getcustom('gold_bean',$aid) && getcustom('maidan_money_dec',$aid)){
                //余额支付送积分
                if($order['bid'] > 0){
                    $business_moneypay_goldbean = Db::name('business')->where('aid',$order['aid'])->where('id',$order['bid'])->field('maidan_give_gold_bean_money,maidan_give_gold_bean_givenum,maidan_give_gold_bean_status,moneypay_give_gold_bean_status')->find();
                    if($business_moneypay_goldbean['moneypay_give_gold_bean_status'] && $business_moneypay_goldbean['maidan_give_gold_bean_givenum']>0 && $business_moneypay_goldbean['maidan_give_gold_bean_money']>0 && $order['dec_money'] >0){
                        $give_gold_bean = floor($order['dec_money']/$business_moneypay_goldbean['maidan_give_gold_bean_money'] )*  $business_moneypay_goldbean['maidan_give_gold_bean_givenum'];
                        if($give_gold_bean > 0){
                            //$aid,$mid,$gold_bean,$remark,$channel='',$bid=0,$frommid=0,$addtotal=1,$params = []
                            $res = \app\common\Member::addgoldbean($order['aid'],$order['mid'],$give_gold_bean,t('余额').'支付送'.t('金豆').'，订单号：'.$order['ordernum']);
                            if($res && $res['status'] == 1){
                                //记录消费赠送金豆记录
                                \app\common\Member::goldbeaninlog($order['aid'],0,$order['mid'],'maidan',$order['id'],$order['ordernum'],$give_gold_bean);
                            }
                        }
                    }
                }
            }
           if(getcustom('maidan_give_score',$aid) && getcustom('maidan_money_dec',$aid)){
               if($order['bid'] > 0){
                   $give_score_set =  Db::name('business')->where('aid',$order['aid'])->where('id',$order['bid'])->field('moneypay_give_score_status,maidan_give_score_money,maidan_give_score_givenum')->find();
                   $moneypay_give_score_status = $give_score_set['moneypay_give_score_status'];
                   
                    if($moneypay_give_score_status ==-1){
                        $give_score_set = Db::name('admin_set')->where('aid',$order['aid'])->field('scorein_money,scorein_score,score_from_moneypay,score_from_moneypay')->find();
                        $moneypay_give_score_status = $give_score_set['score_from_moneypay'];
                        
                    }elseif($moneypay_give_score_status ==1){
                        $give_score_set['scorein_money'] = $give_score_set['maidan_give_score_money'];
                        $give_score_set['scorein_score'] = $give_score_set['maidan_give_score_givenum'];
                    }
                    if($moneypay_give_score_status){
                        if($give_score_set['scorein_money']>0 && $give_score_set['scorein_score']>0 ){
                            $givescore = floor($order['dec_money'] / $give_score_set['scorein_money']) * $give_score_set['scorein_score'];
                            $res = \app\common\Member::addscore($aid,$order['mid'],$givescore,'消费送'.t('积分'));
                            if($res && $res['status'] == 1){
                                //记录消费赠送积分记录
                                \app\common\Member::scoreinlog($aid,0,$order['mid'],'maidan',$order['id'],$order['ordernum'],$givescore,$order['money']);
                            }
                        }
                    }
               } 
           }
           if(getcustom('yx_cashback_decay')){
               //查询购买用户
               $member = Db::name('member')->where('id',$order['mid'])->find();
               \app\custom\OrderCustom::maidandealcashback($aid,$order,$member,'collect');
        }
        }
        elseif($type=='maidan_new'){
            $totalcommission = 0;
            $maidanfenxiao = Db::name('admin_set')->where('aid',$aid)->value('maidanfenxiao');
            if(getcustom('maidan_qrcode',$aid)) {
                if($order['ymid'] && $order['ymid']>0){
                    $maidanfenxiao = 0;
                }
            }

            if(getcustom('business_shopbuycashback',$aid)){
                if($order['bid']>0){
                    //查询商家返现比例设置
                    $shopbuycashback_ratio = Db::name('business')->where('id',$order['bid'])->value('shopbuycashback_ratio');
                    if($shopbuycashback_ratio && $shopbuycashback_ratio>0){
                        $backmoeny = $order['paymoney'] * $shopbuycashback_ratio/100;
                        $backmoeny = round($backmoeny,2);
                        if($backmoeny>0){
                            $res = \app\common\Member::addmoney($order['aid'],$order['mid'],$backmoeny,'商家商城购物返现');
                        }
                    }
                }
            }
            if(getcustom('level_business_shopbuyfenhong',$aid)){
                //发放店铺分红
                $ogdata = $order;
                $ogdata['real_totalprice'] = $ogdata['paymoney'];
                $ogdata['module'] = 'maidan_new';
                $ogdata['cost_price'] = 0;
                $ogdata['num']    = 0;
                $ogdatas = [$ogdata];
                \app\common\Fenhong::business_shopbuyfenhong($aid,$order['id'],$ogdatas,'maidan');
            }

            if($maidanfenxiao == 1){ //参与分销 买单分销
                $fenxiao_paymoney = $order['paymoney'];
                if(getcustom('maidan_money_dec',$aid)){
                    // 加上余额抵扣部分 2025.5.28
                    if($order['dec_money'] && $order['dec_money']>0){
                        $fenxiao_paymoney += $order['dec_money'];
                    }
                }
                $member = Db::name('member')->where('aid',$aid)->where('id',$order['mid'])->find();
                $agleveldata = Db::name('member_level')->where('aid',$aid)->where('id',$member['levelid'])->find();
                if($agleveldata['can_agent'] > 0 && $agleveldata['commission1own']==1){
                    $member['pid'] = $member['id'];
                }
                $isCommissionScore = 0;
                if(getcustom('maidan_commission_score',$aid)){
                    $isCommissionScore = 1;
                }
                if(getcustom('maidan_fenhong_new',$aid)){
                    //买单分销结算方式
                    $sysset = Db::name('admin_set')->where('aid',$aid)->field('maidanfenxiao_type,maidan_cost')->find();
                    $maidanfenhong_type = $sysset['maidanfenxiao_type'];
                    if($maidanfenhong_type == 1){
                        //按利润结算时直接把销售额改成利润
                        if($order['bid']>0){
                            $maidan_cost = Db::name('business')->where('id',$order['bid'])->value('maidan_cost');
                        }else{
                            $maidan_cost = $sysset['maidan_cost'];
                        }
                        $cost_price = bcmul($fenxiao_paymoney,$maidan_cost/100,2);
                        $fenxiao_paymoney = $fenxiao_paymoney - $cost_price;
                    }
                }

                if(getcustom('member_dedamount',$aid)){
                    //若开启了分销依赖抵扣金，或者商家买单使用了抵扣金抵扣，则重置分销佣金金额为让利部分的会员的抵扣金额
                    $dedamount_fenxiao = Db::name('admin_set')->where('aid',$aid)->value('dedamount_fenxiao');
                    if(($dedamount_fenxiao && $dedamount_fenxiao == 1) || ($order['bid']>0 && $order['dedamount_dkmoney']>0 )){
                        if($order['bid']>0 && $order['dedamount_dkmoney']>0 ){
                            $fenxiao_paymoney = $order['dedamount_dkmoney'];
                        }else{
                            $fenxiao_paymoney = 0;
                        }
                    }
                }

                //买单分销 -1关闭 0:跟随系统更加（默认） 1:单独设置
                $commissionset = 0;
                $commissiondata1 = [];//1:单独设置时，金额提成比例
                if(getcustom('business_maidan_commission')){
                    if($order['bid'] > 0){
                        //查询商户信息
                        $business = Db::name('business')
                            ->field('id,maidan_commissionset,maidan_commissiondata1')
                            ->where('aid',$aid)
                            ->where('id',$order['bid'])
                            ->where('status',1)
                            ->find();
                        $commissionset  = $business['maidan_commissionset'];
                        $commissiondata1 = !empty($business['maidan_commissiondata1'])?json_decode($business['maidan_commissiondata1'],true):'';
                    }
                }
                $ogdata = [];
                //是否积分提成
                $ogdata['isparent1score'] = 0;
                $ogdata['isparent2score'] = 0;
                $ogdata['isparent3score'] = 0;
                if($member['pid']){
                    $parent1 = Db::name('member')->where('aid',$aid)->where('id',$member['pid'])->find();
                    if($parent1){
                        $agleveldata1 = Db::name('member_level')->where('aid',$aid)->where('id',$parent1['levelid'])->find();
                        if($agleveldata1['can_agent']!=0){
                            $ogdata['parent1'] = $parent1['id'];
                            if($isCommissionScore && $agleveldata1['maidan_commission_score1']>0){
                                $ogdata['isparent1score'] = 1;
                                $ogdata['parent1score'] = round($agleveldata1['maidan_commission_score1'] * $fenxiao_paymoney * 0.01);
                            }
                        }
                    }
                }
                if($parent1['pid']){
                    $parent2 = Db::name('member')->where('aid',$aid)->where('id',$parent1['pid'])->find();
                    if($parent2){
                        $agleveldata2 = Db::name('member_level')->where('aid',$aid)->where('id',$parent2['levelid'])->find();
                        if($agleveldata2['can_agent']>1){
                            $ogdata['parent2'] = $parent2['id'];
                            if($isCommissionScore && $agleveldata2['maidan_commission_score2']>0){
                                $ogdata['isparent2score'] = 1;
                                $ogdata['parent2score'] = round($agleveldata2['maidan_commission_score2'] * $fenxiao_paymoney * 0.01);
                            }
                        }
                    }
                }
                if($parent2['pid']){
                    $parent3 = Db::name('member')->where('aid',$aid)->where('id',$parent2['pid'])->find();
                    if($parent3){
                        $agleveldata3 = Db::name('member_level')->where('aid',$aid)->where('id',$parent3['levelid'])->find();
                        if($agleveldata3['can_agent']>2){
                            $ogdata['parent3'] = $parent3['id'];
                            if($isCommissionScore &&  $agleveldata3['maidan_commission_score3']>0){
                                $ogdata['isparent3score'] = 1;
                                $ogdata['parent3score'] = round($agleveldata3['maidan_commission_score3'] * $fenxiao_paymoney * 0.01);
                            }
                        }
                    }
                }

                //买单分销
                if($commissionset != -1){
                    //单独设置
                    if($commissionset == 1){
                        //提成比例
                        if($commissiondata1){
                            if($agleveldata1) $ogdata['parent1commission'] = $commissiondata1[$agleveldata1['id']]['commission1'] * $fenxiao_paymoney * 0.01;
                            if($agleveldata2) $ogdata['parent2commission'] = $commissiondata1[$agleveldata2['id']]['commission2'] * $fenxiao_paymoney * 0.01;
                            if($agleveldata3) $ogdata['parent3commission'] = $commissiondata1[$agleveldata3['id']]['commission3'] * $fenxiao_paymoney * 0.01;
                        }
                    }else if($commissionset == 0){
                        //会员等级 分销设置
                        if($agleveldata1['commissiontype']==1){ //固定金额按单
                            $ogdata['parent1commission'] = $agleveldata1['commission1'];
                        }else{
                            $ogdata['parent1commission'] = $agleveldata1['commission1'] * $fenxiao_paymoney * 0.01;
                        }
                        if($agleveldata2['commissiontype']==1){ //固定金额按单
                            $ogdata['parent2commission'] = $agleveldata2['commission2'];
                        }else{
                            $ogdata['parent2commission'] = $agleveldata2['commission2'] * $fenxiao_paymoney * 0.01;
                        }
                        if($agleveldata3['commissiontype']==1){ //固定金额按单
                            $ogdata['parent3commission'] = $agleveldata3['commission3'];
                        }else{
                            $ogdata['parent3commission'] = $agleveldata3['commission3'] * $fenxiao_paymoney * 0.01;
                        }
                    }
                }

                if(getcustom('commission_log_remark_custom',$aid)){
                    if($order['bid'] > 0){
                        $bname = Db::name('business')->where('id',$order['bid'])->value('name');
                    }else{
                        $bname = Db::name('admin_set')->where('aid',$order['aid'])->value('name');
                    }
                    $nickname = Db::name('member')->where('aid',$order['aid'])->where('id',$mid)->value('nickname');
                }
                if(getcustom('pay_huifu_fenzhang')){
                    $huifu_business_status = 0;
                    if($order['bid'] > 0){
                        $businessdata = Db::name('business')->where('aid',$order['aid'])->where('id',$order['bid'])->field('huifu_business_status')->find();
                        $huifu_business_status = $businessdata['huifu_business_status'];//汇付独立收款
                        $huifu_send_commission = $businessdata['huifu_send_commission'];//汇付分账发放佣金
                        $delay_acct_flag = $businessdata['delay_acct_flag']; //分账类型 0实时
                        //上部分上级满足条件发放，其他这里触发佣金的 分账
                        $huifu_log = Db::name('huifu_log')->where('aid',$order['aid'])->where('bid',$order['bid'])->whereNotNull('fenzhangdata')->where('isfenzhang',0)->where('pay_status',1)->where('fenzhangdata','NOT NULL',null)->where('tablename','maidan')->where('ordernum',$order['ordernum'])->find();
                        if($huifu_log){
                            $huifuset = Db::name('sysset')->where('name','huifuset')->find();
                            if(getcustom('pay_huifu_fenzhang_backstage')){
                                //后台独立设置服务商
                                $huifuset_backstage = Db::name('admin_set')->where('aid',$order['aid'])->value('huifuset');
                                if($huifuset_backstage){
                                    $huifuset = ['value'=>$huifuset_backstage];
                                }
                            }
                            $appinfo = json_decode($huifuset['value'],true);
                            $aid = $huifu_log['aid'];
                            $bid = $huifu_log['bid'];
                            $mid = $huifu_log['mid'];
                            $huifu = new \app\custom\Huifu($appinfo,$aid,$bid,$mid,'分账',$huifu_log['ordernum']);
                            $huifu -> comfirmTrade($huifu_log);
                        }
                    }
                }
                if($ogdata['parent1'] && $ogdata['isparent1score']==1){
                    $ogdata['parent1score']>0 && \app\common\Member::addscore($aid,$ogdata['parent1'],$ogdata['parent1score'],'下级买单'.t('积分').'奖励');
                }elseif($ogdata['parent1'] && $ogdata['isparent1score']==0 && $ogdata['parent1commission'] > 0){
                    $remark1 =  '下级买单收款奖励';
                    if(getcustom('commission_log_remark_custom',$aid)){
                        $remark1 = '下级'.$nickname.'在'.$bname.'消费'.$order['money'].'元';
                    }
                    $totalcommission+=$ogdata['parent1commission'];
                    $is_send_commission1 = 1; //是否增加佣金，为了汇付的分账
                    if(getcustom('pay_huifu_fenzhang')){
                        if($huifu_business_status && $huifu_log && $huifu_send_commission==1 && $delay_acct_flag==1){
                            $parent1_huifu_id = Db::name('member')->where('aid',$order['aid'])->where('id',$ogdata['parent1'])->value('huifu_id');
                            if($parent1_huifu_id ) $is_send_commission1 = 0;
                        }
                    }
                    if($is_send_commission1){
                        \app\common\Member::addcommission($aid,$ogdata['parent1'],$mid,$ogdata['parent1commission'],$remark1);
                    }
                    Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$ogdata['parent1'],'frommid'=>$order['mid'],'orderid'=>$order['id'],'type'=>'maidan_new','commission'=>$ogdata['parent1commission'],'remark'=>$remark1,'createtime'=>time(),'status'=>1,'endtime'=>time()]);
                }
                if($ogdata['parent2'] && $ogdata['isparent2score']==1){
                    $ogdata['parent2score']>0 && \app\common\Member::addscore($aid,$ogdata['parent2'],$ogdata['parent2score'],'下二级买单'.t('积分').'奖励');
                }elseif($ogdata['parent2'] && $ogdata['isparent2score']==0 && $ogdata['parent2commission'] > 0){
                    $remark2 = '下二级买单收款奖励';
                    if(getcustom('commission_log_remark_custom',$aid)){
                        $remark2 = '下二级'.$nickname.'在'.$bname.'消费'.$order['money'].'元';
                    }
                    $totalcommission+=$ogdata['parent2commission'];
                    $is_send_commission2 = 1; //是否增加佣金，为了汇付的分账
                    if(getcustom('pay_huifu_fenzhang')){
                        if($huifu_business_status && $huifu_log && $huifu_send_commission==1 && $delay_acct_flag==1){
                            $parent2_huifu_id = Db::name('member')->where('aid',$order['aid'])->where('id',$ogdata['parent2'])->value('huifu_id');
                            if($parent2_huifu_id ) $is_send_commission2 = 0;
                        }
                    }
                    if($is_send_commission2){
                        \app\common\Member::addcommission($aid,$ogdata['parent2'],$mid,$ogdata['parent2commission'],$remark2);
                    }
                    Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$ogdata['parent2'],'frommid'=>$order['mid'],'orderid'=>$order['id'],'type'=>'maidan_new','commission'=>$ogdata['parent2commission'],'remark'=>$remark2,'createtime'=>time(),'status'=>1,'endtime'=>time()]);
                }
                if($ogdata['parent3'] && $ogdata['isparent3score']==1){
                    $ogdata['parent3score']>0 && \app\common\Member::addscore($aid,$ogdata['parent3'],$ogdata['parent3score'],'下三级买单'.t('积分').'奖励');
                }elseif($ogdata['parent3'] && $ogdata['isparent3score']==0 && $ogdata['parent3commission'] > 0){
                    $totalcommission+=$ogdata['parent3commission'];
                    $remark3 = '下三级买单收款奖励';
                    if(getcustom('commission_log_remark_custom',$aid)){
                        $remark3 = '下三级'.$nickname.'在'.$bname.'消费'.$order['money'].'元';
                    }
                    $is_send_commission3 = 1; //是否增加佣金，为了汇付的分账
                    if(getcustom('pay_huifu_fenzhang')){
                        if($huifu_business_status && $huifu_log && $huifu_send_commission==1 && $delay_acct_flag==1){
                            $parent3_huifu_id = Db::name('member')->where('aid',$order['aid'])->where('id',$ogdata['parent3'])->value('huifu_id');
                            if($parent3_huifu_id ) $is_send_commission3 = 0;
                        }
                    }
                    if($is_send_commission3){
                        \app\common\Member::addcommission($aid,$ogdata['parent3'],$mid,$ogdata['parent3commission'],$remark3);
                    }
                    Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$ogdata['parent3'],'frommid'=>$order['mid'],'orderid'=>$order['id'],'type'=>'maidan_new','commission'=>$ogdata['parent3commission'],'remark'=>$remark3,'createtime'=>time(),'status'=>1,'endtime'=>time()]);
                }
                if($ogdata['parent1']){
                    \app\common\Member::uplv($aid,$ogdata['parent1']);
                }
                $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                if($bset['commission_kouchu'] == 0){ //不扣除佣金
                    $totalcommission = 0;
                }
            }
            if(getcustom('active_coin',$aid)){
                //先发放激活币
                self::giveActiveCoin($aid,$order,'maidan_new');
            }
            if(getcustom('active_score',$aid)){
                //让利积分
                self::giveActiveScore($aid,$order,'maidan_new');
            }
            if(getcustom('member_commission_max',$aid) && getcustom('add_commission_max',$aid)){
                //先发放佣金上限
                \app\common\Order::giveCommissionMax($aid,$order,'maidan_new');
            }

            if($order['bid']!=0){//入驻商家的货款
                $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                $oldtotalmoney = $totalmoney = $order['money'] - $order['couponmoney'];
                if(getcustom('member_dedamount',$aid)){
                    $totalmoney -= $order['dedamount_dkmoney'];
                }
                if($totalmoney > 0){
                    $platformMoney = $totalmoney * $binfo['maidan_new_feepercent'] * 0.01;
                    $totalmoney = $totalmoney - $platformMoney - $totalcommission;//先算抽成，然后扣除佣金
                }
                if(!isset($bset)) $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                $scoredkmoney = 0;
                if($bset['scoredk_kouchu'] == 0){
                    $scoredkmoney = 0;
                }elseif($bset['scoredk_kouchu'] == 1){ //扣除积分抵扣
                    $scoredkmoney = $order['scoredk'] ?? 0;
                }elseif($bset['scoredk_kouchu'] == 2){ //到商户余额
                    $businessDkMoney = $order['scoredk'];
                }elseif($bset['scoredk_kouchu'] == 3){ //到商户积分
                    $scoredkmoney = $order['scoredk'] ?? 0;
                    $businessDkScore = $order['decscore'];
                }
                $disprice = $order['disprice']??0;//会员折扣金额
                if($bset['leveldk_kouchu'] == 0){ //扣除积分抵扣
                    $disprice = 0;
                }
                $totalmoney = $totalmoney - $scoredkmoney- $disprice;
                if(getcustom('active_coin',$aid) && getcustom('maidan_fenhong_new',$aid)){
                    //$total_activecoin = Db::name('maidan_order')->where('id',$order['id'])->value('activecoin');
                    //$totalmoney = bcsub($totalmoney,bcmul($totalmoney,$binfo['maidan_cost']/100,2),2);
                }
                //扣除返现比例
                $queue_feepercent_type = 0;
                $queue_feepercent_allmoney = 0;
                if(getcustom('yx_queue_free',$aid)){
                    $queue_free_set = Db::name('queue_free_set')->where('aid',$order['aid'])->where('bid',0)->find();
                    $b_queue_free_set = Db::name('queue_free_set')->where('aid',$order['aid'])->where('bid',$order['bid'])->find();
                    $queue_free_set['order_types'] = explode(',',$queue_free_set['order_types']);
                    if($queue_free_set && $queue_free_set['status']==1 && $b_queue_free_set['status']==1 && in_array('all',$queue_free_set['order_types']) || in_array('maidan_new',$queue_free_set['order_types'])){
                        if($queue_free_set['feepercent_type'] == 1){
                            $queue_feepercent_type = 1;
                        }
                    }

                    if($queue_feepercent_type == 1  && $b_queue_free_set['rate_back'] > 0){
                        $totalmoney = $totalmoney - $oldtotalmoney * $b_queue_free_set['rate_back'] * 0.01;
                    }
                }
                if(!$isbusinesspay){
                    $business_lirun = $totalmoney;
                    if(getcustom('maidan_fenhong_new',$aid)){
                        $business_lirun = $oldtotalmoney * (100 - $binfo['maidan_cost'])*0.01;
                    }

                    $isfamoney = 1;
                    if(getcustom('maidan_orderadd_mobile_paytransfer',$aid) && $order['payment_voucher_pic']){
                        $isfamoney = 0;
                    }
                    if($isfamoney == 1){
                        \app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'买单 订单号：'.$order['ordernum'],true,$type,$order['ordernum'],['platformMoney'=>$platformMoney,'business_lirun'=>$business_lirun]);
                    }
                }else{

                    //商家推荐分成
                    $business_lirun = $totalmoney;
                    if(getcustom('maidan_fenhong_new',$aid)){
                        $business_lirun = $oldtotalmoney * (100 - $binfo['maidan_cost'])*0.01;
                    }
                    if(getcustom('business_agent',$aid)){
                        \app\common\Business::addparentcommission2($aid,$order['bid'],$totalmoney,$platformMoney,$business_lirun);
                    }else{
                        if($totalmoney > 0){
                            \app\common\Business::addparentcommission($aid,$order['bid'],$totalmoney);
                        }
                    }

                    if($totalmoney > 0){

                        //重新赋值用于计算补发货款
                        $totalmoney2 = $totalmoney - $order['paymoney'] * (100-$binfo['feepercent']) * 0.01;
                        //不扣除积分抵扣，则需要给补发此积分抵扣
                        if($totalmoney2 >0 && $bset['scoredk_kouchu'] == 0){
                            $scoredkmoney = $order['scoredk'] ?? 0;
                            if($scoredkmoney>0){
                                if($scoredkmoney >= $totalmoney2){
                                    $scoredkmoney = round($totalmoney2,2);
                                    $totalmoney2 = 0;
                                }else{
                                    $scoredkmoney = round($scoredkmoney,2);
                                    $totalmoney2 -= $scoredkmoney;
                                }
                                if($scoredkmoney>0){
                                    \app\common\Business::addmoney($aid,$order['bid'],$scoredkmoney,'补发'.t('积分').'抵扣部分货款，订单号：'.$order['ordernum'],false,$type,$order['ordernum']);
                                }
                            }
                        }
                        //不扣除会员折扣金额，则需要给补发此会员折扣金额
                        if($totalmoney2 >0 && $bset['leveldk_kouchu'] == 0){
                            $disprice = $order['disprice']??0;
                            if($disprice>0){
                                if($disprice >= $totalmoney2){
                                    $disprice = round($totalmoney2,2);
                                    $totalmoney2 = 0;
                                }else{
                                    $disprice = round($disprice,2);
                                    $totalmoney2 -= $disprice;
                                }
                                if($disprice>0){
                                    \app\common\Business::addmoney($aid,$order['bid'],$disprice,'补发'.t('会员').'折扣金额部分货款，订单号：'.$order['ordernum'],false,$type,$order['ordernum']);
                                }
                            }
                        }
                    }

                    if(getcustom('maidan_money_dec',$aid)){
                        if($order['dec_money']> 0) {
                            //按商家抽成费率计算，商户独立支付模式，需要补发多少余额抵扣的部分
                            $add_dec_money = $order['dec_money'] * (100 - $binfo['feepercent']) * 0.01;
                            $add_dec_money = round($add_dec_money,2);
                            if($add_dec_money>0){
                                //补发抵扣货款
                                \app\common\Business::addmoney($aid,$order['bid'],$add_dec_money,'补发'.t('余额').'抵扣部分货款，订单号：'.$order['ordernum'],false,$type,$order['ordernum']);
                            }
                        }
                    }
                }
                if(getcustom('business_canuseplatcoupon',$aid) && $order['couponmoney'] > 0 && $order['couponrid'] > 0){
                    $couponrecord = Db::name('coupon_record')->where('id',$order['couponrid'])->find();
                    if($couponrecord && $couponrecord['bid'] == 0){
                        $businessuserecord = [];
                        $businessuserecord['aid'] = $order['aid'];
                        $businessuserecord['bid'] = $order['bid'];
                        $businessuserecord['mid'] = $order['mid'];
                        $businessuserecord['ordertype'] = $type;
                        $businessuserecord['orderid'] = $order['id'];
                        $businessuserecord['couponrid'] = $order['couponrid'];
                        $businessuserecord['couponid'] = $couponrecord['couponid'];
                        $businessuserecord['couponname'] = $couponrecord['couponname'];
                        $businessuserecord['couponmoney'] = $couponrecord['money'];
                        $businessuserecord['decmoney'] = $order['couponmoney'];
                        $businessuserecord['status'] = 1;
                        $businessuserecord['createtime'] = time();
                        Db::name('coupon_businessuserecord')->insert($businessuserecord);
                        Db::name('business')->where('id',$order['bid'])->inc('couponmoney',$order['couponmoney'])->update();
                    }
                }
            }

            if(getcustom('yx_queue_free',$aid)){
                \app\custom\QueueFree::join($order,$type,'collect');
            }

            if(getcustom('mendian_maidan_ticheng',$aid)){
                if($order['mdid']>0){
                    $mendian =  Db::name('mendian')->field('id,maidangivepercent,maidangivemoney')->where('id',$order['mdid'])->find();
                    $givemoney = 0;
                    if($mendian['maidangivepercent']>0 || $mendian['maidangivemoney']>0){
                        $givemoney += $order['paymoney'] * 0.01 * $mendian['maidangivepercent'] + $mendian['maidangivemoney'];
                        if($givemoney > 0){
                            \app\common\Mendian::addmoney($order['aid'],$mendian['id'],$givemoney,'买单提成'.$order['ordernum']);
                        }
                    }
                }

            }
            if(getcustom('business_maidan_team_fenhong')){
                self::maidanFenhongJl($order,'maidan_new');
            }
        }
        elseif($type=='designerpage'){
            if($order['bid']!=0){//入驻商家的货款
                $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                $totalmoney = $order['price'];
                if($totalmoney > 0){
                    $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;
                    $totalmoney = $totalmoney - $platformMoney;
                }
                if(!$isbusinesspay){
                    \app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'付费查看页面 订单号：'.$order['ordernum'],true,$type,$order['ordernum'],['platformMoney'=>$platformMoney]);
                }else{
                    //商家推荐分成
                    if(getcustom('business_agent',$aid)){
                        \app\common\Business::addparentcommission2($aid,$order['bid'],$totalmoney,$platformMoney);
                    }else{
                        if($totalmoney > 0){
                            \app\common\Business::addparentcommission($aid,$order['bid'],$totalmoney);
                        }
                    }
                }
            }
        }
        elseif($type == 'scoreshop') {
            //门店分成
            if($order['mdid'] && $commission_mid) {
                $orderGoods = Db::name($type.'_order_goods')->where('orderid',$order['id'])->select()->toArray();
                foreach ($orderGoods as $og) {
                    if($og['mendian_iscommission'] == 0 && $og['mendian_commission'] > 0 && $og['mendian_score'] > 0){
                        if($og['mendian_commission'] > 0) {
                            \app\common\Member::addcommission($aid, $commission_mid, $order['mid'], $og['mendian_commission'], '门店核销：'.$order['ordernum']);
                        }
                        if($og['mendian_score'] > 0) {
                            \app\common\Member::addscore($aid, $commission_mid, $og['mendian_score'],'门店核销：'.$order['ordernum']);
                        }
                        Db::name($type.'_order_goods')->where('id',$og['id'])->update(['mendian_iscommission' => 1]);
                    }
                }
            }
            if(getcustom('score_product_membergive')){
                self::scoreProductMembergive($aid,$order,1);
            }
            if($order['bid']!=0){//入驻商家的货款

                $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                if(getcustom('business_agent',$aid)){
                    $lirun_cost_price = 0;
                    if($order['cost_price']>0){
                        $lirun_cost_price = $order['cost_price']*$order['num'];
                    }
                    $business_lirun = $order['totalprice'] - $order['refund_money'] - $lirun_cost_price;
                }

                if(!is_null($order['business_total_money'])) {
                    $totalmoney = $order['business_total_money'];
                    $platformMoney = $order['totalprice']-$totalmoney - $order['refund_money'];
                } else {
                    $totalmoney = $order['totalmoney'] - $order['freight_price'];
                    if($totalmoney > 0){
                        if(getcustom('business_deduct_cost',$aid)){
                            //获取商品成
                            $orderGoods = Db::name($type.'_order_goods')->where('orderid',$order['id'])->select()->toArray();
                            $all_cost_price = 0;
                            if($orderGoods){
                                foreach ($orderGoods as $og) {
                                    if(!empty($og['cost_price']) && $og['cost_price']>0){
                                        if($og['cost_price']<=$og['sell_price']){
                                            $all_cost_price += $og['cost_price'];
                                        }else{
                                            $all_cost_price += $og['sell_price'];
                                        }
                                    }
                                }
                            }

                            if($binfo && $binfo['deduct_cost'] == 1){
                                //扣除成本
                                $platformMoney = ($totalmoney-$all_cost_price)*$binfo['feepercent']/100;
                            }else{
                                $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;
                            }

                        }else{
                            $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;

                        }
                        if(getcustom('business_fee_type',$aid)){
                            if($bset['business_fee_type'] == 0){
                                $platformMoney = $order['totalprice'] * $binfo['feepercent'] * 0.01;
                            }if($bset['business_fee_type'] == 1){
                                $platformMoney = ($order['totalprice']-$order['freight_price']) * $binfo['feepercent'] * 0.01;
                            }elseif($bset['business_fee_type'] == 2){
                                //获取商品成
                                $orderGoods = Db::name($type.'_order_goods')->where('orderid',$order['id'])->select()->toArray();
                                $total_cost_price = 0;
                                if($orderGoods){
                                    foreach ($orderGoods as $og) {
                                        if(!empty($og['cost_price']) && $og['cost_price']>0){
                                                $total_cost_price += $og['cost_price']*$og['num'];
                                        }
                                    }
                                }
                                $platformMoney = $total_cost_price * $binfo['feepercent'] * 0.01;
                            }
                        }
                        $totalmoney = $totalmoney - $platformMoney + $order['freight_price'];

                    }
                }

                if($order['paytypeid']==4){
                    $totalmoney = $totalmoney - $order['totalprice'];
                }
                if(!$isbusinesspay){
                    if($totalmoney < 0){
                        $bmoney = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->value('money');
                        if($bmoney + $totalmoney < 0){
                            return ['status'=>0,'msg'=>'操作失败,商家余额不足'];
                        }
                    }
                    \app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'货款，'.t('积分').'兑换订单号：'.$order['ordernum'],true,$type,$order['ordernum'],['platformMoney'=>$platformMoney,'business_lirun'=>$business_lirun]);
                }else{
                    //商家推荐分成
                    if(getcustom('business_agent',$aid)){
                        \app\common\Business::addparentcommission2($aid,$order['bid'],$totalmoney,$platformMoney,$business_lirun);
                    }else{
                        if($totalmoney > 0){
                            \app\common\Business::addparentcommission($aid,$order['bid'],$totalmoney);
                        }
                    }
                }
                $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                if($bset['business_selfscore'] == 1){
                    $totalscore = Db::name('scoreshop_order_goods')->where('orderid',$order['id'])->sum('totalscore');
                    if($totalscore > 0 && $order['totalscore'] == 0){
                        \app\common\Business::addscore($aid,$order['bid'],$totalscore,'用户兑换商品，订单号：'.$order['ordernum'],1);
                    }
                }
                //店铺加销量
                $totalnum = Db::name('scoreshop_order_goods')->where('orderid',$order['id'])->sum('num');
                Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->inc('sales',$totalnum)->update();
            }
            if(getcustom('consumer_value_add',$aid) && getcustom('consumer_value_add_scoreshop',$aid)){
                //送绿色积分
                if($order['give_green_score'] > 0){
                    \app\common\Member::addgreenscore($aid,$order['mid'],$order['give_green_score'],'购买商品赠送'.t('绿色积分'),'shop_order',$order['id'],0,$order['give_maximum']);
                }
                //放入奖金池
                if($order['give_bonus_pool'] > 0){
                    \app\common\Member::addbonuspool($aid,$order['mid'],$order['give_bonus_pool'],'购买商品赠送'.t('奖金池'),'shop_order',$order['id'],0,$order['give_green_score']);
                }
                if(getcustom('green_score_reserves',$aid)){
                    //订单进入预备金
                    if($order['give_green_score_reserves']>0){
                        \app\custom\GreenScore::addgreenscorereserves($aid,$order['mid'],$order['give_green_score_reserves'],'购买商品赠送'.t('预备金'),'shop_order',$order['id']);
                    }
                }
            }
            if(getcustom('scoreshop_givescore',$aid)){
                //赠积分
                if($order['givescore'] && $order['givescore'] > 0){
                    if($order['bid'] > 0){
                        $decbscore = 0; //由平台发放积分
                        \app\common\Business::addmemberscore($aid,$order['bid'],$order['mid'],$order['givescore'],'购买'.t('积分').'产品赠送'.t('积分'),$decbscore);
                    }else{
                        \app\common\Member::addscore($aid,$order['mid'],$order['givescore'],'购买'.t('积分').'产品赠送'.t('积分'));
                    }
                }
            }
            if(getcustom('yx_queue_free_scoreshop')){
                //加入排队免单
                \app\custom\QueueFree::join($order,'scoreshop','collect');
            }
        }
        elseif($type=='tuangou'){
            if($order['bid']!=0){//入驻商家的货款

                $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();

                if(getcustom('business_agent',$aid)){
                    $lirun_cost_price = 0;
                    if($order['cost_price']>0){
                        $lirun_cost_price = $order['cost_price']*$order['num'];
                    }
                    $business_lirun = $order['totalprice'] - $order['refund_money'] - $lirun_cost_price;
                }

                $totalmoney = $order['product_price']   - $order['coupon_money'] - $order['manjia_money'];
                if($order['paytypeid']==4){
                    $totalmoney = $totalmoney - $order['totalprice'];
                }
                $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                $scoredkmoney = 0;
                if($bset['scoredk_kouchu'] == 0){
                    $scoredkmoney = 0;
                }elseif($bset['scoredk_kouchu'] == 1){ //扣除积分抵扣
                    $scoredkmoney = $order['scoredk_money'] ?? 0;
                }elseif($bset['scoredk_kouchu'] == 2){ //到商户余额
                    $businessDkMoney = $order['scoredk_money'];
                }elseif($bset['scoredk_kouchu'] == 3){ //到商户积分
                    $scoredkmoney = $order['scoredk_money'] ?? 0;
                    $businessDkScore = $order['scoredkscore'];
                }

                $leveldkmoney = $order['leveldk_money'] ?? 0;
                if($bset['leveldk_kouchu'] == 0){ //扣除积分抵扣
                    $leveldkmoney = 0;
                }

                $totalmoney = $totalmoney - $scoredkmoney - $leveldkmoney;
                if($totalmoney > 0){
                    $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;
                    $totalmoney = $totalmoney - $platformMoney + $order['freight_price']* (100-$binfo['feepercent_freight']) * 0.01;
                }
                if(!$isbusinesspay){
                    if($totalmoney < 0){
                        $bmoney = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->value('money');
                        if($bmoney + $totalmoney < 0){
                            return ['status'=>0,'msg'=>'操作失败,商家余额不足'];
                        }
                    }
                    \app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'货款，团购订单号：'.$order['ordernum'],true,$type,$order['ordernum'],['platformMoney'=>$platformMoney,'business_lirun'=>$business_lirun]);
                }else{
                    //商家推荐分成
                    if(getcustom('business_agent',$aid)){
                        \app\common\Business::addparentcommission2($aid,$order['bid'],$totalmoney,$platformMoney,$business_lirun);
                    }else{
                        if($totalmoney > 0){
                            \app\common\Business::addparentcommission($aid,$order['bid'],$totalmoney);
                        }
                    }
                }
                //店铺加销量
                Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->inc('sales',$order['num'])->update();

                if(getcustom('business_canuseplatcoupon',$aid) && $order['coupon_money'] > 0 && $order['coupon_rid'] > 0){
                    $couponrecord = Db::name('coupon_record')->where('id',$order['coupon_rid'])->find();
                    if($couponrecord && $couponrecord['bid'] == 0){
                        $businessuserecord = [];
                        $businessuserecord['aid'] = $order['aid'];
                        $businessuserecord['bid'] = $order['bid'];
                        $businessuserecord['mid'] = $order['mid'];
                        $businessuserecord['ordertype'] = $type;
                        $businessuserecord['orderid'] = $order['id'];
                        $businessuserecord['couponrid'] = $order['coupon_rid'];
                        $businessuserecord['couponid'] = $couponrecord['couponid'];
                        $businessuserecord['couponname'] = $couponrecord['couponname'];
                        $businessuserecord['couponmoney'] = $couponrecord['money'];
                        $businessuserecord['decmoney'] = $order['coupon_money'];
                        $businessuserecord['status'] = 1;
                        $businessuserecord['createtime'] = time();
                        Db::name('coupon_businessuserecord')->insert($businessuserecord);
                        Db::name('business')->where('id',$order['bid'])->inc('couponmoney',$order['coupon_money'])->update();
                    }
                }
            }
        }
        elseif($type=='kecheng'){
            if($order['bid']!=0){//入驻商家的货款

                $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                $totalcommission = 0;
                //if($order['iscommission']){
                if($order['parent1'] && $order['parent1commission'] > 0){
                    $totalcommission += $order['parent1commission'];
                }
                if($order['parent2'] && $order['parent2commission'] > 0){
                    $totalcommission += $order['parent2commission'];
                }
                if($order['parent3'] && $order['parent3commission'] > 0){
                    $totalcommission += $order['parent3commission'];
                }
                //}
                $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                if($bset['commission_kouchu'] == 0){ //不扣除佣金
                    $totalcommission = 0;
                }

                $totalmoney = $order['totalprice'] - $totalcommission;
                if($totalmoney > 0){
                    $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;
                    $totalmoney = $totalmoney - $platformMoney;
                }
                if(!$isbusinesspay){
                    if($totalmoney < 0){
                        $bmoney = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->value('money');
                        if($bmoney + $totalmoney < 0){
                            return ['status'=>0,'msg'=>'操作失败,商家余额不足'];
                        }
                    }
                    \app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'课程订单号：'.$order['ordernum'],true,$type,$order['ordernum'],['platformMoney'=>$platformMoney]);
                }else{
                    //商家推荐分成
                    if(getcustom('business_agent',$aid)){
                        \app\common\Business::addparentcommission2($aid,$order['bid'],$totalmoney,$platformMoney);
                    }else{
                        if($totalmoney > 0){
                            \app\common\Business::addparentcommission($aid,$order['bid'],$totalmoney);
                        }
                    }
                }
                //店铺加销量
                if(empty($order['num'])){
                    $order['num'] = 1;
                }
                Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->inc('sales',$order['num'])->update();
            }
        }
        elseif($type=='yueke'){
            if($order['bid']!=0){//入驻商家的货款

                $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                $totalmoney = $order['product_price'];
                if($totalmoney > 0){
                    $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;
                    $totalmoney = $totalmoney - $platformMoney;
                }
                if(!$isbusinesspay){
                    if($totalmoney < 0){
                        $bmoney = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->value('money');
                        if($bmoney + $totalmoney < 0){
                            return ['status'=>0,'msg'=>'操作失败,商家余额不足'];
                        }
                    }
                    $worker = Db::name('yuyue_worker')->where('id',$order['workerid'])->find();
                    \app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'约课订单/('.$worker['realname'].')'.$worker['tel'].' /:'.$order['ordernum'],true,$type,$order['ordernum'],['platformMoney'=>$platformMoney]);
                }else{
                    //商家推荐分成
                    if(getcustom('business_agent',$aid)){
                        \app\common\Business::addparentcommission2($aid,$order['bid'],$totalmoney,$platformMoney);
                    }else{
                        if($totalmoney > 0){
                            \app\common\Business::addparentcommission($aid,$order['bid'],$totalmoney);
                        }
                    }
                }
                //店铺加销量
                Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->inc('sales',$order['num'])->update();
            }
        }
        elseif($type=='gift_bag'){
            if(getcustom('extend_gift_bag',$aid)){
                if($order['bid']!=0){//入驻商家的货款
                    $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                    $totalmoney = $order['totalprice'];
                    if($totalmoney > 0){
                        $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;
                        $totalmoney = $totalmoney - $platformMoney;
                    }

                    if(!$isbusinesspay){
                        \app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'礼包订单号：'.$order['ordernum'],true,$type,$order['ordernum'],['platformMoney'=>$platformMoney]);
                    }else{
                        //商家推荐分成
                        if(getcustom('business_agent',$aid)){
                            \app\common\Business::addparentcommission2($aid,$order['bid'],$totalmoney,$platformMoney);
                        }else{
                            if($totalmoney > 0){
                                \app\common\Business::addparentcommission($aid,$order['bid'],$totalmoney);
                            }
                        }
                    }
                }
            }
        }
        elseif($type=='hotel'){
            if($order['bid']!=0){//入驻商家的货款
                $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                //需要结算的金额为 实付金额减去押金  减去已退的服务费 加余额抵扣
                $totalmoney = $order['totalprice'] - $order['yajin_money'] - $order['fuwu_refund_money'] + $order['use_money'];
                $totalmoney = $totalmoney* (100-$binfo['feepercent_freight']) * 0.01;
                $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                $scoredkmoney = 0;
                if($bset['scoredk_kouchu'] == 0){
                    $scoredkmoney = 0;
                }elseif($bset['scoredk_kouchu'] == 1){ //扣除积分抵扣
                    $scoredkmoney = $order['scoredk_money'] ?? 0;
                }elseif($bset['scoredk_kouchu'] == 2){ //到商户余额
                    $businessDkMoney = $order['scoredk_money'];
                }elseif($bset['scoredk_kouchu'] == 3){ //到商户积分
                    $scoredkmoney = $order['scoredk_money'] ?? 0;
                    $businessDkScore = $order['scoredkscore'];
                }
                $leveldkmoney = $order['leveldk_money'] ?? 0;
                if($bset['leveldk_kouchu'] == 0){ //扣除积分抵扣
                    $leveldkmoney = 0;
                }

                $totalmoney = $totalmoney - $scoredkmoney - $leveldkmoney;

                if($totalmoney > 0){
                    $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;
                    $totalmoney = $totalmoney - $platformMoney;
                }
                if(!$isbusinesspay){
                    if($totalmoney < 0){
                        $bmoney = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->value('money');
                        if($bmoney + $totalmoney < 0){
                            return ['status'=>0,'msg'=>'操作失败,商家余额不足'];
                        }
                    }
                    $text =  \app\model\Hotel::gettext($aid);
                    \app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'货款，'.$text['酒店'].'订单号：'.$order['ordernum'],true,$type,$order['ordernum']);
                }else{
                    if($order['use_money']> 0) {
                        //按商家抽成费率计算，商户独立支付模式，需要补发多少余额抵扣的部分
                        $add_dec_money = $order['use_money'] * (100 - $binfo['feepercent']) * 0.01;
                        $add_dec_money = round($add_dec_money,2);
                        if($add_dec_money>0){
                            //补发抵扣货款
                            \app\common\Business::addmoney($aid,$order['bid'],$add_dec_money,'补发'.t('余额').'抵扣部分货款，订单号：'.$order['ordernum'],false,$type,$order['ordernum']);
                        }
                    }
                }
                //店铺加销量
                Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->inc('sales',$order['totalnum'])->update();
            }
            if(getcustom('yx_queue_free_hotel',$aid)){
                \app\custom\QueueFree::join($order,$type,'collect');
            }
            if(getcustom('yx_buyer_subsidy',$aid)){
                $res = \app\custom\Subsidy::caclOrder($aid,$order['id'],1,'hotle');
            }
            if(getcustom('yx_mangfan_hotel')) {
                \app\custom\Mangfan::sendBonus($order['aid'],$order['mid'],$order['id'],'hotel');
            }
        }
        elseif($type=='car_hailing'){
            if(getcustom('yx_buyer_subsidy',$aid)){
                $res = \app\custom\Subsidy::caclOrder($aid,$order['id'],1,'car_hailing');
            }
            if(getcustom('yx_mangfan_car_hailing')) {
                \app\custom\Mangfan::sendBonus($order['aid'],$order['mid'],$order['id'],'car_hailing');
            }
        }
        elseif($type=='water_happyti'){
            if(getcustom('water_happy_ti',$order['aid']) && $order['bid']!=0){//入驻商家的货款

                $order = Db::name('water_happyti_order')->where('aid',$order['aid'])->where('id',$order['id'])->find();

                $totalcommission = 0;
                if($order['parent1'] && $order['parent1commission'] > 0){
                    $totalcommission += $order['parent1commission'];
                }
                if($order['parent2'] && $order['parent2commission'] > 0){
                    $totalcommission += $order['parent2commission'];
                }
                if($order['parent3'] && $order['parent3commission'] > 0){
                    $totalcommission += $order['parent3commission'];
                }
                $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                //$water_set = Db::name('water_happyti_set')->where('aid',$aid)->where('bid',$order['bid'])->find();
                if($bset['commission_kouchu'] == 0){ //不扣除佣金
                    $totalcommission = 0;
                }

                //打水订单平台抽成比例
                $water_happyti_platform_rate = 0;
                if($binfo['water_happyti_platform_rate_type'] == 1){
                    //如果是商户自己设置
                    $water_happyti_platform_rate = $binfo['water_happyti_platform_rate'];
                }else{
                    //跟随系统
                    $water_happyti_platform_rate = $bset['water_happyti_platform_rate'];
                }

                $scoredkmoney = 0;
                if($bset['scoredk_kouchu'] == 0){
                    $scoredkmoney = 0;
                }elseif($bset['scoredk_kouchu'] == 1){ //扣除积分抵扣
                    $scoredkmoney = $order['scoredk_money'] ?? 0;
                }elseif($bset['scoredk_kouchu'] == 2){ //到商户余额
                    $businessDkMoney = $order['scoredk_money'];
                }elseif($bset['scoredk_kouchu'] == 3){ //到商户积分
                    $scoredkmoney = $order['scoredk_money'] ?? 0;
                    $businessDkScore = $order['scoredkscore'];
                }
                if(!is_null($order['business_total_money'])) {
                    $leveldkmoney = $order['leveldk_money'] ?? 0;
                    if($bset['leveldk_kouchu'] == 0){ //扣除会员抵扣
                        $leveldkmoney = 0;
                    }
                    $totalmoney = $order['business_total_money'] - $totalcommission - $leveldkmoney - $scoredkmoney;
                    $platformMoney = $order['totalprice']-$totalmoney - $order['refund_money'];
                } else {
                    /*********全部走的商品独立费率，这里的代码执行不到**********/
                    $leveldkmoney = $order['leveldk_money'] ?? 0;
                    if($bset['leveldk_kouchu'] == 0){ //商家返款扣除会员折扣
                        $leveldkmoney = 0;
                    }
                    $totalmoney = $order['product_price'] - $order['coupon_money'] - $order['scoredk_money'] - $totalcommission - $leveldkmoney - $order['refund_money'];
                    if($totalmoney > 0){
                        $platformMoney = $totalmoney * $water_happyti_platform_rate * 0.01;
                        $totalmoney = $totalmoney - $platformMoney;
                    }
                }

                if($order['paytypeid']==4){
                    $totalmoney = $totalmoney - $order['totalprice'];
                }
                if(!$isbusinesspay){
                    if($totalmoney < 0){
                        $bmoney = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->value('money');
                        if($bmoney + $totalmoney < 0){
                            return ['status'=>0,'msg'=>'操作失败,商家余额不足'];
                        }
                    }
                    \app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'货款，打水订单号：'.$order['ordernum'],true,$type,$order['ordernum'],['platformMoney'=>$platformMoney,'business_lirun'=>$business_lirun]);
                }else{
                    //商家推荐分成
                    if($totalmoney > 0){
                        \app\common\Business::addparentcommission($aid,$order['bid'],$totalmoney);
                    }
                }
            }
        }  
        elseif($type == 'gold_bean_shop') {
            if(getcustom('gold_bean_shop')){
                //门店分成
                if($order['mdid'] && $commission_mid) {
                    $orderGoods = Db::name($type.'_order_goods')->where('orderid',$order['id'])->select()->toArray();
                    foreach ($orderGoods as $og) {
                        if($og['mendian_iscommission'] == 0 && $og['mendian_commission'] > 0 && $og['mendian_score'] > 0){
                            if($og['mendian_commission'] > 0) {
                                \app\common\Member::addcommission($aid, $commission_mid, $order['mid'], $og['mendian_commission'], '门店核销：'.$order['ordernum']);
                            }
                            if($og['mendian_score'] > 0) {
                                \app\common\Member::addscore($aid, $commission_mid, $og['mendian_score'],'门店核销：'.$order['ordernum']);
                            }
                            Db::name($type.'_order_goods')->where('id',$og['id'])->update(['mendian_iscommission' => 1]);
                        }
                    }
                }
                if($order['bid']!=0){//入驻商家的货款

                    $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                    $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                    if(getcustom('business_agent',$aid)){
                        $lirun_cost_price = 0;
                        if($order['cost_price']>0){
                            $lirun_cost_price = $order['cost_price']*$order['num'];
                        }
                        $business_lirun = $order['totalprice'] - $order['refund_money'] - $lirun_cost_price;
                    }

                    if(!is_null($order['business_total_money'])) {
                        $totalmoney = $order['business_total_money'];
                        $platformMoney = $order['totalprice']-$totalmoney - $order['refund_money'];
                    } else {
                        $totalmoney = $order['totalmoney'] - $order['freight_price'];
                        if($totalmoney > 0){
                            if(getcustom('business_deduct_cost',$aid)){
                                //获取商品成
                                $orderGoods = Db::name($type.'_order_goods')->where('orderid',$order['id'])->select()->toArray();
                                $all_cost_price = 0;
                                if($orderGoods){
                                    foreach ($orderGoods as $og) {
                                        if(!empty($og['cost_price']) && $og['cost_price']>0){
                                            if($og['cost_price']<=$og['sell_price']){
                                                $all_cost_price += $og['cost_price'];
                                            }else{
                                                $all_cost_price += $og['sell_price'];
                                            }
                                        }
                                    }
                                }

                                if($binfo && $binfo['deduct_cost'] == 1){
                                    //扣除成本
                                    $platformMoney = ($totalmoney-$all_cost_price)*$binfo['feepercent']/100;
                                }else{
                                    $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;
                                }

                            }else{
                                $platformMoney = $totalmoney * $binfo['feepercent'] * 0.01;

                            }
                            if(getcustom('business_fee_type',$aid)){
                                if($bset['business_fee_type'] == 0){
                                    $platformMoney = $order['totalprice'] * $binfo['feepercent'] * 0.01;
                                }if($bset['business_fee_type'] == 1){
                                    $platformMoney = ($order['totalprice']-$order['freight_price']) * $binfo['feepercent'] * 0.01;
                                }elseif($bset['business_fee_type'] == 2){
                                    //获取商品成
                                    $orderGoods = Db::name($type.'_order_goods')->where('orderid',$order['id'])->select()->toArray();
                                    $total_cost_price = 0;
                                    if($orderGoods){
                                        foreach ($orderGoods as $og) {
                                            if(!empty($og['cost_price']) && $og['cost_price']>0){
                                                $total_cost_price += $og['cost_price']*$og['num'];
                                            }
                                        }
                                    }
                                    $platformMoney = $total_cost_price * $binfo['feepercent'] * 0.01;
                                }
                            }
                            $totalmoney = $totalmoney - $platformMoney + $order['freight_price'];

                        }
                    }

                    if($order['paytypeid']==4){
                        $totalmoney = $totalmoney - $order['totalprice'];
                    }
                    if(!$isbusinesspay){
                        if($totalmoney < 0){
                            $bmoney = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->value('money');
                            if($bmoney + $totalmoney < 0){
                                return ['status'=>0,'msg'=>'操作失败,商家余额不足'];
                            }
                        }
                        \app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'货款，'.t('金豆').'兑换订单号：'.$order['ordernum'],true,$type,$order['ordernum'],['platformMoney'=>$platformMoney,'business_lirun'=>$business_lirun]);
                    }else{
                        //商家推荐分成
                        if(getcustom('business_agent',$aid)){
                            \app\common\Business::addparentcommission2($aid,$order['bid'],$totalmoney,$platformMoney,$business_lirun);
                        }else{
                            if($totalmoney > 0){
                                \app\common\Business::addparentcommission($aid,$order['bid'],$totalmoney);
                            }
                        }
                    }
                    $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                    if($bset['business_selfscore'] == 1){
                        $totalscore = Db::name('gold_bean_shop_order_goods')->where('orderid',$order['id'])->sum('totalscore');
                        if($totalscore > 0 && $order['totalscore'] == 0){
                            \app\common\Business::addscore($aid,$order['bid'],$totalscore,'用户兑换商品，订单号：'.$order['ordernum'],1);
                        }
                    }
                    //店铺加销量
                    $totalnum = Db::name('gold_bean_shop_order_goods')->where('orderid',$order['id'])->sum('num');
                    Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->inc('sales',$totalnum)->update();
                }
                if(getcustom('yx_queue_free_gold_bean_shop',$aid)){
                    \app\custom\QueueFree::join($order,$type,'collect');
                }
            }
        }

        $set = Db::name('admin_set')->where('aid',$aid)->find();
        //确认收货奖励
        self::orderCollectReward($order,$type);
        if($set['fxjiesuantime_delaydays'] == '0'){ //确认收货后发佣金 0天结算
            self::giveCommission($order,$type);
            if(getcustom('yx_shop_order_team_yeji_bonus',$aid)) {
                self::giveShopBonus($order,$aid);
            }
        }
        if(getcustom('business_fenxiao',$aid)){
            $fenxiao_type = $set['business_fenxiao_type'];
            //收货完成，店铺分销统计日营业额
            if($fenxiao_type==1){
                $payorder = Db::name('payorder')
                    ->where('orderid',$order['id'])
                    ->where('mid',$order['mid'])
                    ->where('status',1)
                    ->where('type',$type)
                    ->find();
                \app\common\Business::countBusinessYeji($payorder);
            }
        }
        \app\model\Payorder::afterusecoupon($order['id'],$type,2,$order['ordernum']);
        if(getcustom('ganer_fenxiao',$aid)){
            //订单业绩进入奖金池
            $set = Db::name('prize_pool_set')->where('aid',$aid)->find();
            //订单业绩进入奖金池
            if($set['pool_time']==1){
                \app\common\Fenxiao::bonus_poul($order['id'],$type);
            }
        }
        //会员抵扣积分兑换到余额
        if(getcustom('business_score_jiesuan',$aid) && $order['bid']>0){
            if($businessDkMoney>0){
                \app\common\Business::addmoney($aid,$order['bid'],$businessDkMoney,t('积分').'抵扣转'.t('余额').'，订单号：'.$order['ordernum'],false,$type,$order['ordernum']);
            }
            if($businessDkScore>0){
                \app\common\Business::addscore($aid,$order['bid'],$businessDkScore,t('积分').'抵扣到商户'.t('积分').'，订单号：'.$order['ordernum']);
            }
        }
        if(getcustom('pay_qilinshuzi',$aid)){
            if($type == 'shop') {
                //确认收货后分账
                $payorder = Db::name('payorder')->where('orderid', $order['id'])->where('mid', $order['mid'])->where('status', 1)->where('type', $type)->find();
                //查询流水号
                $payorder['ordernum'] = Db::name('pay_transaction')->where('payorderid',$payorder['id'])->value('transaction_num');
                \app\custom\QilinshuziPay::orderFenzhang($payorder);
            }
        }
        return ['status'=>1,'msg'=>'操作成功'];
    }

    //排名分红追加点位
    public static function PaimingFenhongPoint($order){
        $aid = $order['aid'];
        $mid = $order['mid'];
        $set = Db::name('paiming_fenhong_set')->where('aid',$aid)->find();
        if($set['is_open'] == 1){
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->find();
            $order_amount = $order['totalprice'];

            $over_point_amount = $set['over_point_amount'];
            $all_amount = $order_amount + $member['paiming_fenhong_buy_money'];
            $point_num = floor($all_amount/$over_point_amount);
            $buy_money = round($all_amount - ($point_num*$over_point_amount),2);
            //剩余金额继续保留
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['paiming_fenhong_buy_money'=>$buy_money]);
            //追加点位
            if($point_num>0){
                for($i=0;$i<$point_num;$i++){
                    $data_record = [];
                    $data_record['aid'] = $aid;
                    $data_record['mid'] = $mid;
                    $data_record['max_amount'] = $set['max_point_amount'];
                    $data_record['status'] = 0;
                    $data_record['createtime'] = time();
                    $id = Db::name('paiming_fenhong_record')->insertGetId($data_record);
                }
            }
        }
    }
    //退款 分红
    public static function rebackYejiFenhong($payorder,$refund_money){

        $aid = $payorder['aid'];

        $mid = $payorder['mid'];
        $orderid = $payorder['orderid'];
        $logs = Db::name('yeji_fenhong_log')->where('aid',$aid)->where('orderid',$orderid)->select()->toArray();

        if(empty($logs)){
            return;
        }
        foreach ($logs as $key => $log) {
            if(!$log || $log['status'] != 1){
                return;
            }
            if($log['refund_commission'] >= $log['commission']){
                return;
            }
            $refund_money_l = dd_money_format($refund_money*$log['bili']/100);

            if($refund_money_l+$log['refund_commission'] > $log['commission']){
                $refund_money_l = $log['commission'] - $log['refund_commission'];
            }
            if($refund_money_l > 0){
                Db::name('yeji_fenhong_log')->where('aid',$aid)->where('id',$log['id'])->inc('refund_commission',$refund_money_l)->update();
                \app\common\Member::addcommission($aid,$log['mid'],$mid,-$refund_money_l,'订单退款，分红退回，订单ID:'.$orderid);
            }
        }
    }

    //退款 店铺补贴
    public static function rebackShopBonus($payorder,$refund_money){

        $aid = $payorder['aid'];
        $mid = $payorder['mid'];
        $orderid = $payorder['orderid'];
        $logs = Db::name('shop_bonus_log')->where('aid',$aid)->where('orderid',$orderid)->select()->toArray();

        if(empty($logs)){
            return;
        }
        foreach ($logs as $key => $log) {
            if(!$log || $log['status'] != 1){
                return;
            }
            if($log['refund_commission'] >= $log['commission']){
                return;
            }
            $refund_money_l = dd_money_format($refund_money*$log['bili']/100);

            if($refund_money_l+$log['refund_commission'] > $log['commission']){
                $refund_money_l = $log['commission'] - $log['refund_commission'];
            }
            if($refund_money_l > 0){
                Db::name('shop_bonus_log')->where('aid',$aid)->where('id',$log['id'])->inc('refund_commission',$refund_money_l)->update();
                \app\common\Member::addcommission($aid,$log['mid'],$mid,-$refund_money_l,'订单退款，店铺补贴退回');
            }
        }
    }

    //结算佣金 店铺补贴
    public static function giveShopBonus($order,$aid){

        //分销结算时间 fxjiesuantime,0确认收货后,1付款后；延迟几天fxjiesuantime_delaydays

        $recordList = Db::name('shop_bonus_log')->where('aid',$aid)->where('orderid',$order['id'])->where('status',0)->select()->toArray();
        if(empty($recordList)){
            return;
        }
        $commission_arr = [];
        foreach($recordList as $k=>$record){
            $order = Db::name('shop_order')->where('id',$record['orderid'])->find();
            if(!$order || $order['status'] == 4){
                Db::name('shop_bonus_log')->where('aid',$aid)->where('id',$record['id'])->update(['status'=>2]);
                continue;
            }
            $status = $order['status'];

            // 发放
            Db::name('shop_bonus_log')->where('aid',$aid)->where('id',$record['id'])->update(['status'=>1,'endtime'=>time()]);

            if($record['commission'] > 0){
                if(!empty($commission_arr[$record['mid']])){
                    $commission_arr[$record['mid']] += $record['commission'];
                }else{
                    $commission_arr[$record['mid']] = $record['commission'];
                }
            }
        }
        // dump($commission_arr);die;
        if(!empty($commission_arr)){
            foreach ($commission_arr as $mid => $commission) {
                \app\common\Member::addcommission($aid,$mid,$record['frommid'],$commission,'店铺补贴');
            }
        }

        // Db::name('shop_bonus_log')->where('aid',$aid)->where('id',$record['id'])->update(['status'=>1,'endtime'=>time()]);

        // if($record['commission'] > 0){
        //     $commission = $record['commission'];
        //     \app\common\Member::addcommission($aid,$record['mid'],$record['frommid'],$commission,'店铺补贴',1,'',0,'',$record['id']);
        // }  
    }
    //结算佣金 设置了延时结算时每小时执行 店铺补贴
    public static function jiesuanShopBonus($aid,$admin_set=[]){

        //分销结算时间 fxjiesuantime,0确认收货后,1付款后；延迟几天fxjiesuantime_delaydays
        if(empty($admin_set))
            $admin_set = Db::name('admin_set')->where('aid',$aid)->find();
        if(empty($admin_set))
            return;
        $where = [];
        $where[] = ['aid', '=', $aid];
        $where[] = ['status', '=', 0];
        $dtime = time();
        if($admin_set['fxjiesuantime_delaydays'] > 0){
            $delaytime = floatval($admin_set['fxjiesuantime_delaydays']) * 86400;

            $dtime -= $delaytime;
            $where[] = ['createtime', '<', $dtime];
//            $where[] = ['createtime', '>', $dtime-300*86400];//部分客户很长时间未收货，改为300天
        }else{
            $where[] = ['createtime', '<', time()];
        }

        $recordList = Db::name('shop_bonus_log')->where($where)->select()->toArray();
        
        if(empty($recordList)){
            return;
        }

        $commission_arr = [];
        foreach($recordList as $k=>$record){
            $order = Db::name('shop_order')->where('id',$record['orderid'])->find();
            if(!$order || $order['status'] == 4){
                Db::name('shop_bonus_log')->where('aid',$aid)->where('id',$record['id'])->update(['status'=>2]);
                continue;
            }

            $status = $order['status'];

            if(($admin_set['fxjiesuantime'] == 0 && $status==3 && $order['collect_time'] < $dtime) || ($admin_set['fxjiesuantime'] == 1 && in_array($status,[1,2,3]) && $order['paytime'] < $dtime)){
 
                // 发放
                Db::name('shop_bonus_log')->where('aid',$aid)->where('id',$record['id'])->update(['status'=>1,'endtime'=>time()]);

                if($record['commission'] > 0){
                    $commission = $record['commission'];
                    if(!empty($commission_arr[$record['mid']])){
                        $commission_arr[$record['mid']] += $record['commission'];
                    }else{
                        $commission_arr[$record['mid']] = $record['commission'];
                    }
                }
            }
        }
        if(!empty($commission_arr)){
            foreach ($commission_arr as $mid => $commission) {
                \app\common\Member::addcommission($aid,$mid,0,$commission,'店铺补贴');
            }
            
        }
        
    }
    //结算佣金 设置了延时结算时每小时执行
    public static function jiesuanCommission($aid,$admin_set=[]){
        //分销结算时间 fxjiesuantime,0确认收货后,1付款后；延迟几天fxjiesuantime_delaydays
        if(getcustom('fxjiesuantime_perweek',$aid)) return;
        if(empty($admin_set))
            $admin_set = Db::name('admin_set')->where('aid',$aid)->find();
        if(empty($admin_set))
            return;
        $where = [];
        $where[] = ['aid', '=', $aid];
        $where[] = ['status', '=', 0];
        $where[] = ['type', 'in', ['shop','seckill','scoreshop','collage','lucky_collage','kecheng','tuangou','fishpond','hotel','zhiyoubao']];
        $dtime = time();
        if($admin_set['fxjiesuantime_delaydays'] > 0){
            $delaytime = floatval($admin_set['fxjiesuantime_delaydays']) * 86400;
            $dtime -= $delaytime;
            $where[] = ['createtime', '<', $dtime];
//            $where[] = ['createtime', '>', $dtime-300*86400];//部分客户很长时间未收货，改为300天
        }else{
            $where[] = ['createtime', '<', time()];
        }
        $recordList = Db::name('member_commission_record')->where($where)->select()->toArray();
        foreach($recordList as $k=>$record){
            $order = Db::name($record['type'].'_order')->where('id',$record['orderid'])->find();
            if(!$order || $order['status'] == 4){
                Db::name('member_commission_record')->where('id',$record['id'])->update(['status'=>2]);
                continue;
            }
            $status = $order['status'];
            if($record['type'] == 'kecheng' && $status == 1) $status = 3;
            if($record['type'] == 'hotel' && $status == 1) $status = 0; //已支付未确认
            if($record['type'] == 'hotel' && $status == 4) $status = 3; //已离店  0未支付;1已支付;2已确认 ,3已到店 4已离店 -1已关闭
            if(($admin_set['fxjiesuantime'] == 0 && $status==3 && $order['paytime'] < $dtime) || ($admin_set['fxjiesuantime'] == 1 && in_array($status,[1,2,3]) && $order['paytime'] < $dtime)){
                self::giveCommission($order,$record['type']);
            }
        }
    }
    //结算佣金 每周几结算
    public static function jiesuanCommissionWeek($aid,$admin_set=[]){
        if(!getcustom('fxjiesuantime_perweek',$aid)) return;
        if(empty($admin_set))
            $admin_set = Db::name('admin_set')->where('aid',$aid)->find();
        $week = $admin_set['fxjiesuantime_delaydays'];
        if($week == 0) return;
        if($week == 7) $week = 0;
        if($week != date('w')) return;
        $dtime = strtotime(date('Y-m-d',time()-86400));
        $recordList = Db::name('member_commission_record')->where('aid',$aid)->where('status',0)
            ->where('type','in',['shop','seckill','scoreshop','collage','lucky_collage','kecheng','tuangou','fishpond','zhiyoubao'])->where('createtime','<',$dtime)
            /*->where('createtime','>',$dtime-30*86400)*/->select()->toArray();
        foreach($recordList as $k=>$record){
            $order = Db::name($record['type'].'_order')->where('id',$record['orderid'])->find();
            if(!$order || $order['status'] == 4){
                Db::name('member_commission_record')->where('id',$record['id'])->update(['status'=>2]);
                continue;
            }
            $status = $order['status'];
            if($record['type'] == 'kecheng' && $status == 1) $status = 3;
            if(getcustom('commission_times_coupon',$aid)){
                if($record['type'] == 'coupon' && $status == 1) $status = 3;
            }
            if(($admin_set['fxjiesuantime'] == 0 && $status==3 && $order['paytime'] < $dtime) || ($admin_set['fxjiesuantime'] == 1 && in_array($status,[1,2,3]) && $order['paytime'] < $dtime)){
                self::giveCommission($order,$record['type']);
            }
        }
    }

    //发放佣金
    public static function giveCommission($order,$type='shop'){
        $aid = $order['aid'];
        $fxfh_send_wallet = 0;
        $fxfh_send_wallet_levelids = [];
        if(getcustom('commission_send_wallet',$aid)){
            //定制分销分红发放钱包 0到佣金 1到余额
            $admin_set = Db::name('admin_set')->where('aid',$aid)->field('commission_send_wallet,commission_send_wallet_levelids')->find();
            $fxfh_send_wallet = $admin_set['commission_send_wallet'];
            $fxfh_send_wallet_levelids = explode(',',$admin_set['commission_send_wallet_levelids']);
            $fxfh_send_wallet_levelids = array_filter($fxfh_send_wallet_levelids);
            if(empty($fxfh_send_wallet_levelids)){
                $fxfh_send_wallet_levelids = ['-1'];
            }
        }
        if($type == 'shop'){
            $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type','shop')->where('orderid',$order['id'])->where('status',0)->select();
            foreach($commission_record_list as $commission_record){
                if(getcustom('member_shougou_parentreward')){
                    //商城订单是否锁住
                    if($commission_record['islock'] == 1){
                        continue;
                    }
                }
                Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                if(getcustom('pay_huifu_fenzhang')){
                    if($commission_record['to_fenzhang'])continue;
                }
                $og = Db::name('shop_order_goods')->where('id',$commission_record['ogid'])->find();
                Db::name('shop_order_goods')->where('id',$commission_record['ogid'])->update(['iscommission'=>1]);
                if($commission_record['commission'] > 0){
                    $commission = $commission_record['commission'];
                    if(getcustom('commission2moneypercent')){
                        $sysset = Db::name('admin_set')->where('aid',$aid)->find();
                        if($sysset['commission2moneypercent1'] > 0){
                            //是否是首单
                            $beforeorder = Db::name('shop_order')->where('aid',$aid)->where('mid',$order['mid'])->where('status','in','1,2,3')->where('paytime','<',$order['paytime'])->find();
                            if(!$beforeorder){
                                $commission = (100 - $sysset['commission2moneypercent1']) * $commission_record['commission'] * 0.01;
                                $money = $sysset['commission2moneypercent1'] * $commission_record['commission'] * 0.01;
                            }else{
                                $commission = (100 - $sysset['commission2moneypercent2']) * $commission_record['commission'] * 0.01;
                                $money = $sysset['commission2moneypercent2'] * $commission_record['commission'] * 0.01;
                            }
                            $commission = round($commission,2);
                            $money = round($money,2);
                            if($money > 0){
                                \app\common\Member::addmoney($aid,$commission_record['mid'],$money,$commission_record['remark']);
                            }
                        }
                    }
                    $levelid = 0;
                    if(getcustom('commission_frozen_level')){
                        $member = Db::name('member')->where('id','=',$commission_record['mid'])->find();
                        if($member['levelstarttime'] >= $order['createtime']) {
                            $levelup_order_levelid = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $commission_record['mid'])->where('status', 2)
                                ->where('levelup_time', '<', $order['createtime'])->order('levelup_time', 'desc')->value('levelid');
                            if($levelup_order_levelid) {
                                $levelid = $levelup_order_levelid;
                            }
                        }
                    }

                    $addcommission = true;
                    if(getcustom('member_forzengxcommission')){
                        //佣金商品发放到冻结佣金记录中
                        if($commission_record['product_type'] && $commission_record['product_type'] == 10){
                            $addcommission = false;
                            if($commission>0){
                                $sendmonth = Db::name('admin_set')->where('aid',$aid)->value('gxcommission_sendmonth');
                                if($sendmonth>0){
                                    \app\common\Member::addforzengxcommission($aid,$commission_record['mid'],$commission,'member_commission_record',$commission_record['id'],$sendmonth);
                                }
                            }
                        }
                    }

                    if($addcommission){
                        $fxfh_send_money = 0;
                        if($fxfh_send_wallet==1){
                            //分销分红发放钱包等级限制
                            $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                            if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                                $fxfh_send_money = 1;
                            }
                        }
                        if($fxfh_send_money==1){
                            //发放到余额
                            \app\common\Member::addmoney($aid,$commission_record['mid'],$commission,$commission_record['remark']);
                        }else{
                            \app\common\Member::addcommission($aid,$commission_record['mid'],$commission_record['frommid'],$commission,$commission_record['remark'],1,'fenxiao',$levelid,'',$commission_record['id']);
                        }
                        
                        // 团队分红极差式加速 
                        if(getcustom('yx_cashback_time_fenxiao_speed',$aid) && $commission_record['cashback_speed'] == 1){
                            \app\custom\OrderCustom::dealFxspeed($commission_record);

                        }

                        $tmplcontent = [];
                        $tmplcontent['first'] = '恭喜您，成功分销商品获得'.t('佣金').'：￥'.$commission_record['commission'];
                        $tmplcontent['remark'] = '点击进入查看~';
                        $tmplcontent['keyword1'] = $og['name']; //商品信息
                        $tmplcontent['keyword2'] = (string) $og['sell_price'];//商品单价
                        $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                        $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                        $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                        //短信通知
                        $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                        $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    }

                    //发放团队收益
                    //根据分红奖团队收益
                    if(getcustom('teamfenhong_shouyi')){
                        $sysset = Db::name('admin_set')->where('aid',$aid)->find();
                        $midfhArr = [
                            $commission_record['mid']=>[
                                'commission' => $commission,
                                'ogids' =>$commission_record['ogid'],
                                'module' => 'shop'
                            ]
                        ];
                       \app\common\Fenhong::teamshouyi($aid,$sysset,$midfhArr,[],0,0,1,0);
                    }
                }
                if(getcustom('commission_money_percent')){
                    if($commission_record['money'] > 0){
                        \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['money'],$commission_record['remark']);
                    }
                }
                if($commission_record['score'] > 0){
                    \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得：'.$commission_record['score'].t('积分');
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['name']; //商品信息
                    $tmplcontent['keyword2'] = (string) $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                }
                if(getcustom('yx_network_help',$aid)){
                    if($commission_record['money'] > 0 && !getcustom('commission_money_percent',$aid)){
                        \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['money'],$commission_record['remark']);
                    }
                    if($commission_record['help_score'] > 0){
                        \app\custom\NetworkHelp::addhelpscore($aid,$commission_record['mid'],$commission_record['help_score'],$commission_record['remark'],0,$commission_record['orderid'],$commission_record['ogid']);
                    }
                }
                if(getcustom('commission_xianjin_percent',$aid)){
                    if($commission_record['xianjin'] > 0){
                        $res = \app\custom\MemberCustom::addXianjin($aid,$commission_record['mid'],$commission_record['xianjin'],$commission_record['remark']);
                    }
                }
                if(getcustom('yx_buyer_subsidy',$aid)){
                    if($commission_record['subsidyscore'] > 0){
                        $res = \app\custom\Subsidy::addSubcidyScore($aid,$commission_record['mid'],$commission_record['subsidyscore'],$commission_record['remark']);
                    }
                }
                if(getcustom('yx_farm',$aid)){
                    if($commission_record['farmseed'] > 0){
                        $farmCustom = new \app\custom\yingxiao\FarmCustom($aid);
                        $res = $farmCustom::addFert($aid,$commission_record['mid'],'seed',$commission_record['farmseed'],$commission_record['remark']);
                    }
                }
            }
            if(getcustom('pay_huifu_fenzhang')){
                $huifu_log = Db::name('huifu_log')->where('aid',$order['aid'])->where('bid',$order['bid'])->whereNotNull('fenzhangdata')->where('isfenzhang',0)->where('pay_status',1)->where('fenzhangdata','NOT NULL',null)->where('tablename','shop')->where('ordernum',$order['ordernum'])->find();
                if($huifu_log){
                    $huifuset = Db::name('sysset')->where('name','huifuset')->find();
                    if(getcustom('pay_huifu_fenzhang_backstage')){
                        //后台独立设置服务商
                        $huifuset_backstage = Db::name('admin_set')->where('aid',$order['aid'])->value('huifuset');
                        if($huifuset_backstage){
                            $huifuset = ['value'=>$huifuset_backstage];
                        }
                    }
                    $appinfo = json_decode($huifuset['value'],true);
                    $aid = $huifu_log['aid'];
                    $bid = $huifu_log['bid'];
                    $mid = $huifu_log['mid'];
                    $huifu = new \app\custom\Huifu($appinfo,$aid,$bid,$mid,'分账',$huifu_log['ordernum']);
                    $huifu -> comfirmTrade($huifu_log);
                }
            }
            if(getcustom('yx_liandong',$aid)){
                \app\custom\Liandong::send_bonus($aid,$order['id']);
            }
            if(getcustom('yx_network_help',$aid)){
                \app\custom\NetworkHelp::send_bonus($aid,$order['id']);
            }
        }elseif($type=='seckill'){
            $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type','seckill')->where('orderid',$order['id'])->where('status',0)->select();
            foreach($commission_record_list as $commission_record){
                Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                if($commission_record['commission'] > 0){
                    $fxfh_send_money = 0;
                    if($fxfh_send_wallet==1){
                        //分销分红发放钱包等级限制
                        $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                        if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                            $fxfh_send_money = 1;
                        }
                    }
                    if($fxfh_send_money==1){
                        //发放到余额
                        \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                    }else {
                        \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                    }
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得'.t('佣金').'：￥'.$commission_record['commission'];
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $order['proname']; //商品信息
                    $tmplcontent['keyword2'] = (string) $order['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    //根据分红奖团队收益
                    if(getcustom('teamfenhong_shouyi')){
                        $sysset = Db::name('admin_set')->where('aid',$aid)->find();
                        $midfhArr = [
                            $commission_record['mid']=>[
                                'commission' => $commission_record['commission'],
                                'ogids' =>$commission_record['ogid'],
                                'module' => $commission_record['type']
                            ]
                        ];
                       \app\common\Fenhong::teamshouyi($aid,$sysset,$midfhArr,[],0,0,1,0);
                    }
                }
                if($commission_record['score'] > 0){
                    \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得：'.$commission_record['score'].t('积分');
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $order['proname']; //商品信息
                    $tmplcontent['keyword2'] = (string) $order['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                }
            }
        }elseif($type == 'scoreshop'){
            //佣金
            $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type',$type)->where('orderid',$order['id'])->where('status',0)->select();
            foreach($commission_record_list as $commission_record){
                Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                $og = Db::name($type.'_order_goods')->where('id',$commission_record['ogid'])->find();
                Db::name($type.'_order_goods')->where('id',$commission_record['ogid'])->update(['iscommission'=>1]);
                if($commission_record['commission'] > 0){
                    $fxfh_send_money = 0;
                    if($fxfh_send_wallet==1){
                        //分销分红发放钱包等级限制
                        $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                        if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                            $fxfh_send_money = 1;
                        }
                    }
                    if($fxfh_send_money==1){
                        //发放到余额
                        \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                    }else {
                        \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                    }
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得'.t('佣金').'：￥'.$commission_record['commission'];
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['name']; //商品信息
                    $tmplcontent['keyword2'] = (string) $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    //根据分红奖团队收益
                    if(getcustom('teamfenhong_shouyi')){
                        $sysset = Db::name('admin_set')->where('aid',$aid)->find();
                        $midfhArr = [
                            $commission_record['mid']=>[
                                'commission' => $commission_record['commission'],
                                'ogids' =>$commission_record['ogid'],
                                'module' => $commission_record['type']
                            ]
                        ];
                       \app\common\Fenhong::teamshouyi($aid,$sysset,$midfhArr,[],0,0,1,0);
                    }
                }
                if($commission_record['score'] > 0){
                    \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得：'.$commission_record['score'].t('积分');
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['name']; //商品信息
                    $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                }
            }
        }elseif($type == 'collage'){
            //佣金
            $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type',$type)->where('orderid',$order['id'])->where('status',0)->select();
            foreach($commission_record_list as $commission_record){
                Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                $og = Db::name($type.'_order')->where('id',$commission_record['orderid'])->find();
                if($commission_record['commission'] > 0){
                    $fxfh_send_money = 0;
                    if($fxfh_send_wallet==1){
                        //分销分红发放钱包等级限制
                        $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                        if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                            $fxfh_send_money = 1;
                        }
                    }
                    if($fxfh_send_money==1){
                        //发放到余额
                        \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                    }else {
                        \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                    }
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得'.t('佣金').'：￥'.$commission_record['commission'];
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    //根据分红奖团队收益
                    if(getcustom('teamfenhong_shouyi')){
                        $sysset = Db::name('admin_set')->where('aid',$aid)->find();
                        $midfhArr = [
                            $commission_record['mid']=>[
                                'commission' => $commission_record['commission'],
                                'ogids' =>$commission_record['ogid'],
                                'module' => $commission_record['type']
                            ]
                        ];
                       \app\common\Fenhong::teamshouyi($aid,$sysset,$midfhArr,[],0,0,1,0);
                    }
                }
                if($commission_record['score'] > 0){
                    \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得：'.$commission_record['score'].t('积分');
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                }
            }
        }elseif($type == 'kanjia'){
            //佣金
            $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type',$type)->where('orderid',$order['id'])->where('status',0)->select();
            foreach($commission_record_list as $commission_record){
                Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                $og = Db::name($type.'_order')->where('id',$commission_record['orderid'])->find();
                if($commission_record['commission'] > 0){
                    $fxfh_send_money = 0;
                    if($fxfh_send_wallet==1){
                        //分销分红发放钱包等级限制
                        $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                        if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                            $fxfh_send_money = 1;
                        }
                    }
                    if($fxfh_send_money==1){
                        //发放到余额
                        \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                    }else {
                        \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                    }
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得'.t('佣金').'：￥'.$commission_record['commission'];
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['product_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    //根据分红奖团队收益
                    if(getcustom('teamfenhong_shouyi')){
                        $sysset = Db::name('admin_set')->where('aid',$aid)->find();
                        $midfhArr = [
                            $commission_record['mid']=>[
                                'commission' => $commission_record['commission'],
                                'ogids' =>$commission_record['ogid'],
                                'module' => $commission_record['type']
                            ]
                        ];
                       \app\common\Fenhong::teamshouyi($aid,$sysset,$midfhArr,[],0,0,1,0);
                    }
                }
                if($commission_record['score'] > 0){
                    \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得：'.$commission_record['score'].t('积分');
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['product_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                }
            }
        }elseif($type == 'lucky_collage'){
            //佣金
            $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type',$type)->where('orderid',$order['id'])->where('status',0)->select();
            foreach($commission_record_list as $commission_record){
                Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                $og = Db::name($type.'_order')->where('id',$commission_record['orderid'])->find();
                if($commission_record['commission'] > 0){
                    $fxfh_send_money = 0;
                    if($fxfh_send_wallet==1){
                        //分销分红发放钱包等级限制
                        $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                        if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                            $fxfh_send_money = 1;
                        }
                    }
                    if($fxfh_send_money==1){
                        //发放到余额
                        \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                    }else {
                        \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                    }
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得'.t('佣金').'：￥'.$commission_record['commission'];
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    //根据分红奖团队收益
                    if(getcustom('teamfenhong_shouyi')){
                        $sysset = Db::name('admin_set')->where('aid',$aid)->find();
                        $midfhArr = [
                            $commission_record['mid']=>[
                                'commission' => $commission_record['commission'],
                                'ogids' =>$commission_record['ogid'],
                                'module' => $commission_record['type']
                            ]
                        ];
                       \app\common\Fenhong::teamshouyi($aid,$sysset,$midfhArr,[],0,0,1,0);
                    }
                }
                if($commission_record['score'] > 0){
                    \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得：'.$commission_record['score'].t('积分');
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                }

            }
        }elseif($type == 'kecheng'){
            //佣金
            $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type',$type)->where('orderid',$order['id'])->where('status',0)->select();
            foreach($commission_record_list as $commission_record){
                Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                $og = Db::name($type.'_order')->where('id',$commission_record['orderid'])->find();
                if($commission_record['commission'] > 0){
                    $fxfh_send_money = 0;
                    if($fxfh_send_wallet==1){
                        //分销分红发放钱包等级限制
                        $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                        if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                            $fxfh_send_money = 1;
                        }
                    }
                    if($fxfh_send_money==1){
                        //发放到余额
                        \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                    }else {
                        \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                    }
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得'.t('佣金').'：￥'.$commission_record['commission'];
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    //根据分红奖团队收益
                    if(getcustom('teamfenhong_shouyi')){
                        $sysset = Db::name('admin_set')->where('aid',$aid)->find();
                        $midfhArr = [
                            $commission_record['mid']=>[
                                'commission' => $commission_record['commission'],
                                'ogids' =>$commission_record['ogid'],
                                'module' => $commission_record['type']
                            ]
                        ];
                       \app\common\Fenhong::teamshouyi($aid,$sysset,$midfhArr,[],0,0,1,0);
                    }
                }
                if($commission_record['score'] > 0){
                    \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得：'.$commission_record['score'].t('积分');
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                }
            }
        }elseif($type=='tuangou'){
            $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type',$type)->where('orderid',$order['id'])->where('status',0)->select();
            foreach($commission_record_list as $commission_record){
                Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                if($commission_record['commission'] > 0){
                    $fxfh_send_money = 0;
                    if($fxfh_send_wallet==1){
                        //分销分红发放钱包等级限制
                        $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                        if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                            $fxfh_send_money = 1;
                        }
                    }
                    if($fxfh_send_money==1){
                        //发放到余额
                        \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                    }else {
                        \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                    }
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得'.t('佣金').'：￥'.$commission_record['commission'];
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $order['proname']; //商品信息
                    $tmplcontent['keyword2'] = (string) $order['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    //根据分红奖团队收益
                    if(getcustom('teamfenhong_shouyi')){
                        $sysset = Db::name('admin_set')->where('aid',$aid)->find();
                        $midfhArr = [
                            $commission_record['mid']=>[
                                'commission' => $commission_record['commission'],
                                'ogids' =>$commission_record['ogid'],
                                'module' => $commission_record['type']
                            ]
                        ];
                       \app\common\Fenhong::teamshouyi($aid,$sysset,$midfhArr,[],0,0,1,0);
                    }
                }
                if($commission_record['score'] > 0){
                    \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得：'.$commission_record['score'].t('积分');
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $order['proname']; //商品信息
                    $tmplcontent['keyword2'] = (string) $order['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                }
            }
        }elseif($type == 'yuyue'){
            //佣金
            $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type',$type)->where('orderid',$order['id'])->where('status',0)->select();
            foreach($commission_record_list as $commission_record){
                Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                $og = Db::name($type.'_order')->where('id',$commission_record['orderid'])->find();
                if($commission_record['commission'] > 0){
                    $fxfh_send_money = 0;
                    if($fxfh_send_wallet==1){
                        //分销分红发放钱包等级限制
                        $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                        if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                            $fxfh_send_money = 1;
                        }
                    }
                    if($fxfh_send_money==1){
                        //发放到余额
                        \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                    }else {
                        \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                    }
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销服务获得'.t('佣金').'：￥'.$commission_record['commission'];
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    //根据分红奖团队收益
                    if(getcustom('teamfenhong_shouyi')){
                        $sysset = Db::name('admin_set')->where('aid',$aid)->find();
                        $midfhArr = [
                            $commission_record['mid']=>[
                                'commission' => $commission_record['commission'],
                                'ogids' =>$commission_record['ogid'],
                                'module' => $commission_record['type']
                            ]
                        ];
                        \app\common\Fenhong::teamshouyi($aid,$sysset,$midfhArr,[],0,0,1,0);
                    }
                }
                if($commission_record['score'] > 0){
                    \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销服务获得：'.$commission_record['score'].t('积分');
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                }

            }
        }elseif($type=='cashier'){
            //佣金
            $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type',$type)->where('orderid',$order['id'])->where('status',0)->select();
            foreach($commission_record_list as $commission_record){
                Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                $og = Db::name($type.'_order')->where('id',$commission_record['orderid'])->find();
                if($commission_record['commission'] > 0){
                    $fxfh_send_money = 0;
                    if($fxfh_send_wallet==1){
                        //分销分红发放钱包等级限制
                        $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                        if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                            $fxfh_send_money = 1;
                        }
                    }
                    if($fxfh_send_money==1){
                        //发放到余额
                        \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                    }else {
                        \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                    }
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销服务获得'.t('佣金').'：￥'.$commission_record['commission'];
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    //根据分红奖团队收益
                    if(getcustom('teamfenhong_shouyi')){
                        $sysset = Db::name('admin_set')->where('aid',$aid)->find();
                        $midfhArr = [
                            $commission_record['mid']=>[
                                'commission' => $commission_record['commission'],
                                'ogids' =>$commission_record['ogid'],
                                'module' => $commission_record['type']
                            ]
                        ];
                       \app\common\Fenhong::teamshouyi($aid,$sysset,$midfhArr,[],0,0,1,0);
                    }
                }
                if($commission_record['score'] > 0){
                    \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销服务获得：'.$commission_record['score'].t('积分');
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                }

            }
        }elseif($type=='restaurant_shop'){
            //佣金
            $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type',$type)->where('orderid',$order['id'])->where('status',0)->select();
            foreach($commission_record_list as $commission_record){
                Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                $og = Db::name($type.'_order')->where('id',$commission_record['orderid'])->find();
                if($commission_record['commission'] > 0){
                    $fxfh_send_money = 0;
                    if($fxfh_send_wallet==1){
                        //分销分红发放钱包等级限制
                        $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                        if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                            $fxfh_send_money = 1;
                        }
                    }
                    if($fxfh_send_money==1){
                        //发放到余额
                        \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                    }else {
                        \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                    }
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销服务获得'.t('佣金').'：￥'.$commission_record['commission'];
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    //根据分红奖团队收益
                    if(getcustom('teamfenhong_shouyi')){
                        $sysset = Db::name('admin_set')->where('aid',$aid)->find();
                        $midfhArr = [
                            $commission_record['mid']=>[
                                'commission' => $commission_record['commission'],
                                'ogids' =>$commission_record['ogid'],
                                'module' => $commission_record['type']
                            ]
                        ];
                       \app\common\Fenhong::teamshouyi($aid,$sysset,$midfhArr,[],0,0,1,0);
                    }
                }
                if($commission_record['score'] > 0){
                    \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销服务获得：'.$commission_record['score'].t('积分');
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                }
            }
        }elseif($type=='coupon'){
            if(getcustom('commission_times_coupon')){
                //佣金
                $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type',$type)->where('orderid',$order['id'])->where('status',0)->select();
                foreach($commission_record_list as $commission_record){
                    Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                    $og = Db::name($type.'_order')->where('id',$commission_record['orderid'])->find();
                    if($commission_record['commission'] > 0){
                        $fxfh_send_money = 0;
                        if($fxfh_send_wallet==1){
                            //分销分红发放钱包等级限制
                            $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                            if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                                $fxfh_send_money = 1;
                            }
                        }
                        if($fxfh_send_money==1){
                            //发放到余额
                            \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                        }else {
                            \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                        }
                        $tmplcontent = [];
                        $tmplcontent['first'] = '恭喜您，成功分销服务获得'.t('佣金').'：￥'.$commission_record['commission'];
                        $tmplcontent['remark'] = '点击进入查看~';
                        $tmplcontent['keyword1'] = $og['proname']; //商品信息
                        $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                        $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                        $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                        $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                        //短信通知
                        $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                        $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    }
                    if($commission_record['score'] > 0){
                        \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                        $tmplcontent = [];
                        $tmplcontent['first'] = '恭喜您，成功分销服务获得：'.$commission_record['score'].t('积分');
                        $tmplcontent['remark'] = '点击进入查看~';
                        $tmplcontent['keyword1'] = $og['proname']; //商品信息
                        $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                        $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                        $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                        $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                        //短信通知
                        //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                        //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                    }

                }
            }

        }elseif($type=='livepay'){
            if(getcustom('extend_chongzhi')){
            $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type','livepay')->where('orderid',$order['id'])->where('status',0)->select();
            foreach($commission_record_list as $commission_record){
                Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                if($commission_record['commission'] > 0){
                    $fxfh_send_money = 0;
                    if($fxfh_send_wallet==1){
                        //分销分红发放钱包等级限制
                        $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                        if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                            $fxfh_send_money = 1;
                        }
                    }
                    if($fxfh_send_money==1){
                        //发放到余额
                        \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                    }else {
                        \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                    }
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功生活缴费分销获得'.t('佣金').'：￥'.$commission_record['commission'];
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $order['type_name']; //商品信息
                    $tmplcontent['keyword2'] = (string) $order['pay_money'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                }
            }
            }
        }elseif($type=='gift_pack'){
            if(getcustom('yx_gift_pack')){
            $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type','gift_pack')->where('orderid',$order['id'])->where('status',0)->select();
                foreach($commission_record_list as $commission_record){
                    Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                    if($commission_record['commission'] > 0){
                        $fxfh_send_money = 0;
                        if($fxfh_send_wallet==1){
                            //分销分红发放钱包等级限制
                            $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                            if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                                $fxfh_send_money = 1;
                            }
                        }
                        if($fxfh_send_money==1){
                            //发放到余额
                            \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                        }else {
                            \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                        }
                        $tmplcontent = [];
                        $tmplcontent['first'] = '恭喜您，分销获得'.t('佣金').'：￥'.$commission_record['commission'];
                        $tmplcontent['remark'] = '点击进入查看~';
                        $tmplcontent['keyword1'] = $order['title'];
                        $tmplcontent['keyword2'] = (string) $order['sell_price'];
                        $tmplcontent['keyword3'] = $commission_record['commission'].'元';
                        $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                        $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                        //短信通知
                        $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                        $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    }
                }
            }
        }elseif($type=='business_reward'){
            if(getcustom('business_reward_member')){
                $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type','business_reward')->where('orderid',$order['id'])->where('status',0)->select();
                foreach($commission_record_list as $commission_record){
                    Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                    if($commission_record['commission'] > 0){
                        $fxfh_send_money = 0;
                        if($fxfh_send_wallet==1){
                            //分销分红发放钱包等级限制
                            $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                            if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                                $fxfh_send_money = 1;
                            }
                        }
                        if($fxfh_send_money==1){
                            //发放到余额
                            \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                        }else {
                            \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                        }
                        $tmplcontent = [];
                        $tmplcontent['first'] = '恭喜您，分销获得'.t('佣金').'：￥'.$commission_record['commission'];
                        $tmplcontent['remark'] = '点击进入查看~';
                        $tmplcontent['keyword1'] = $order['title'];
                        $tmplcontent['keyword2'] = (string) $order['sell_price'];
                        $tmplcontent['keyword3'] = $commission_record['commission'].'元';
                        $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                        $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                        //短信通知
                        $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                        $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    }
                }
            }
        }else if($type == 'channels'){
            if(getcustom('wx_channels')){
                //多商户订单
                if($order['bid'] && !empty($order['bid'])){
                    $commission_record_list = Db::name('channels_sharer_commission_record')->where('orderid',$order['id'])->where('status',0)->where('bid',$order['bid'])->where('aid',$order['aid'])->where('type','channels')->select();
                }else{
                    $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type','channels')->where('orderid',$order['id'])->where('status',0)->select();
                }
                foreach($commission_record_list as $commission_record){
                    if(getcustom('member_shougou_parentreward')){
                        //商城订单是否锁住
                        if($commission_record['islock'] == 1){
                            continue;
                        }
                    }
                    if($order['bid'] && !empty($order['bid'])){
                        Db::name('channels_sharer_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                    }else{
                        Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                    }
                    $og = Db::name('channels_order_goods')->where('id',$commission_record['ogid'])->find();
                    Db::name('channels_order_goods')->where('id',$commission_record['ogid'])->update(['iscommission'=>1]);
                    if($commission_record['commission'] > 0){
                        $fxfh_send_money = 0;
                        if($fxfh_send_wallet==1){
                            //分销分红发放钱包等级限制
                            $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                            if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                                $fxfh_send_money = 1;
                            }
                        }
                        $commission = $commission_record['commission'];
                        $levelid = 0;
                        if(getcustom('commission_frozen_level')){
                            $member = Db::name('member')->where('id','=',$commission_record['mid'])->find();
                            if($member['levelstarttime'] >= $order['createtime']) {
                                $levelup_order_levelid = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $commission_record['mid'])->where('status', 2)
                                    ->where('levelup_time', '<', $order['createtime'])->order('levelup_time', 'desc')->value('levelid');
                                if($levelup_order_levelid) {
                                    $levelid = $levelup_order_levelid;
                                }
                            }
                        }
                        if($order['bid'] && !empty($order['bid'])){
                            //加分享员佣金
                            $params = ['aid'=>$aid,'bid'=>$order['bid'],'mid'=>$commission_record['mid'],'frommid'=>$commission_record['frommid'],'commission'=>$commission,'remark'=>$commission_record['remark'],'addtotal'=>1,'fhtype'=>'fenxiao','fhid'=>$commission_record['id'],'commissionid'=>0,'sharerid'=>$commission_record['sharerid']];
                            \app\common\WxChannels::addsharercommission($params);
                        }else{
                            if($fxfh_send_money==1){
                                //发放到余额
                                \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                            }else {
                                \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission, $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                            }
                        }
                        $tmplcontent = [];
                        $tmplcontent['first'] = '恭喜您，成功分销商品获得'.t('佣金').'：￥'.$commission_record['commission'];
                        $tmplcontent['remark'] = '点击进入查看~';
                        $tmplcontent['keyword1'] = $og['name']; //商品信息
                        $tmplcontent['keyword2'] = (string) $og['sell_price'];//商品单价
                        $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                        $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                        $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                        //短信通知
                        $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                        $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                        //发放团队收益
                        //根据分红奖团队收益
                        if(getcustom('teamfenhong_shouyi')){
                            $sysset = Db::name('admin_set')->where('aid',$aid)->find();
                            $midfhArr = [
                                $commission_record['mid']=>[
                                    'commission' => $commission,
                                    'ogids' =>$commission_record['ogid'],
                                    'module' => $commission_record['type']
                                ]
                            ];
                            \app\common\Fenhong::teamshouyi($aid,$sysset,$midfhArr,[],0,0,1,0);
                        }
                    }
                    if($commission_record['score'] > 0){
                        \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                        $tmplcontent = [];
                        $tmplcontent['first'] = '恭喜您，成功分销商品获得：'.$commission_record['score'].t('积分');
                        $tmplcontent['remark'] = '点击进入查看~';
                        $tmplcontent['keyword1'] = $og['name']; //商品信息
                        $tmplcontent['keyword2'] = (string) $og['sell_price'];//商品单价
                        $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                        $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                        $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                        //短信通知
                        //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                        //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                    }
                }
            }
        }else if($type == 'car_hailing'){
            //佣金
            $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type',$type)->where('orderid',$order['id'])->where('status',0)->select();
            foreach($commission_record_list as $commission_record){
                Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                $og = Db::name($type.'_order')->where('id',$commission_record['orderid'])->find();
                if($commission_record['commission'] > 0){
                    $fxfh_send_money = 0;
                    if($fxfh_send_wallet==1){
                        //分销分红发放钱包等级限制
                        $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                        if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                            $fxfh_send_money = 1;
                        }
                    }
                    if($fxfh_send_money==1){
                        //发放到余额
                        \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                    }else {
                        \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                    }
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得'.t('佣金').'：￥'.$commission_record['commission'];
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    //根据分红奖团队收益
                    if(getcustom('teamfenhong_shouyi')){
                        $sysset = Db::name('admin_set')->where('aid',$aid)->find();
                        $midfhArr = [
                            $commission_record['mid']=>[
                                'commission' => $commission_record['commission'],
                                'ogids' =>$commission_record['ogid'],
                                'module' => $commission_record['type']
                            ]
                        ];
                        \app\common\Fenhong::teamshouyi($aid,$sysset,$midfhArr,[],0,0,1,0);
                    }
                }
                if($commission_record['score'] > 0){
                    \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得：'.$commission_record['score'].t('积分');
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                }
            }
        }elseif($type == 'hotel'){
            //佣金
            $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type',$type)->where('orderid',$order['id'])->where('status',0)->select();
            foreach($commission_record_list as $commission_record){
                Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                $og = Db::name($type.'_order')->where('id',$commission_record['orderid'])->find();
                if($commission_record['commission'] > 0){
                    $fxfh_send_money = 0;
                    if($fxfh_send_wallet==1){
                        //分销分红发放钱包等级限制
                        $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                        if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                            $fxfh_send_money = 1;
                        }
                    }
                    if($fxfh_send_money==1){
                        //发放到余额
                        \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                    }else {
                        \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                    }
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销服务获得'.t('佣金').'：￥'.$commission_record['commission'];
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    //根据分红奖团队收益
                    if(getcustom('teamfenhong_shouyi')){
                        $sysset = Db::name('admin_set')->where('aid',$aid)->find();
                        $midfhArr = [
                            $commission_record['mid']=>[
                                'commission' => $commission_record['commission'],
                                'ogids' =>$commission_record['ogid'],
                                'module' => $commission_record['type']
                            ]
                        ];
                        \app\common\Fenhong::teamshouyi($aid,$sysset,$midfhArr,[],0,0,1,0);
                    }
                }
                if($commission_record['score'] > 0){
                    \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销服务获得：'.$commission_record['score'].t('积分');
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                }
            }
        }elseif($type=='fishpond'){
            //佣金
            $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type',$type)->where('orderid',$order['id'])->where('status',0)->select();
            foreach($commission_record_list as $commission_record){
                Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                $og = Db::name($type.'_order')->where('id',$commission_record['orderid'])->find();
                if($commission_record['commission'] > 0){
                    $fxfh_send_money = 0;
                    if($fxfh_send_wallet==1){
                        //分销分红发放钱包等级限制
                        $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                        if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                            $fxfh_send_money = 1;
                        }
                    }
                    if($fxfh_send_money==1){
                        //发放到余额
                        \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                    }else {
                        \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                    }
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得'.t('佣金').'：￥'.$commission_record['commission'];
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                }
                if($commission_record['score'] > 0){
                    \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得：'.$commission_record['score'].t('积分');
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['proname']; //商品信息
                    $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                }
            }
        }elseif($type=='money_withdraw' || $type=='commission_withdraw'){
            //佣金       
            $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type',$type)->where('orderid',$order['id'])->where('status',0)->select();
            foreach($commission_record_list as $commission_record){
                Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                $table = $type=='money_withdraw'?'member_withdrawlog':'member_commission_withdrawlog';
                $og = Db::name($table)->where('id',$commission_record['orderid'])->find();
                $type_text = $type=='money_withdraw'?'余额提现':'佣金提现';
                if($commission_record['commission'] > 0){
                    $fxfh_send_money = 0;
                    if($fxfh_send_wallet==1){
                        //分销分红发放钱包等级限制
                        $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                        if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                            $fxfh_send_money = 1;
                        }
                    }
                    if($fxfh_send_money==1){
                        //发放到余额
                        \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                    }else {
                        \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                    }
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销'.t('会员').'提现获得'.t('佣金').'：￥'.$commission_record['commission'];
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = t('会员').$type_text; //商品信息
                    $tmplcontent['keyword2'] = $og['txmoney'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                }
                if($commission_record['score'] > 0){
                    \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销'.t('会员').'提现获得'.t('积分').'：￥'.$commission_record['commission'];;
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = t('会员').$type_text; //商品信息
                    $tmplcontent['keyword2'] = $og['txmoney'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                }
            }
        }elseif($type=='yueke'){
            if(getcustom('yueke_extend')){
                //佣金
                $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type',$type)->where('orderid',$order['id'])->where('ogid',$order['ogid'])->where('status',0)->select();
                foreach($commission_record_list as $commission_record){
                    //ogid: 学习记录id
                    Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                    $og = Db::name($type.'_order')->where('id',$commission_record['orderid'])->find();
                    if($commission_record['commission'] > 0){
                        $fxfh_send_money = 0;
                        if($fxfh_send_wallet==1){
                            //分销分红发放钱包等级限制
                            $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                            if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                                $fxfh_send_money = 1;
                            }
                        }
                        if($fxfh_send_money==1){
                            //发放到余额
                            \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                        }else {
                            \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                        }
                        $tmplcontent = [];
                        $tmplcontent['first'] = '恭喜您，成功分销商品获得'.t('佣金').'：￥'.$commission_record['commission'];
                        $tmplcontent['remark'] = '点击进入查看~';
                        $tmplcontent['keyword1'] = $og['proname']; //商品信息
                        $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                        $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                        $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                        $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                        //短信通知
                        $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                        $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    }
                }
            }
        }
        elseif($type=='hanglvfeike'){
            if(getcustom('extend_hanglvfeike')){
                //再次验证出发时间
                $nowdaytime = strtotime(date("Y-m-d"));
                if($order['fromDatetime']>=$nowdaytime){
                    return;
                }
                $aid = $order['aid'];
                $mid = $order['mid'];

                //查询佣金记录
                $commission_record_list = Db::name('member_commission_record')->where('orderid',$order['id'])->where('type',$type)->where('status',0)->where('aid',$aid)->select()->toArray();
                if(!$commission_record_list){
                    Db::name('hanglvfeike_order')->where('id',$order['id'])->where('iscommission',1)->update(['iscommission'=>2]);
                    return;
                } 
                foreach($commission_record_list as $commission_record){
                    //查询相关的订单用户表
                    $goods = Db::name('hanglvfeike_order_goods')->where('id',$commission_record['ogid'])->where('orderid',$commission_record['orderid'])->where('iscommission','>=',1)->where('iscommission','<=',2)->find();
                    if(!$goods){
                        Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>2]);
                        continue;
                    }

                    //有退款申请及退款成功的不发放
                    if($goods['refund_status'] > 0 && $goods['refund_status'] < 4){
                        //已退款则取消佣金发放
                        if($goods['refund_status'] == 3){
                            Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>2]);
                        }
                        continue;
                    } 

                    //有改签申请的不发放
                    if($goods['change_status'] == 1 ) continue;
                    //改签已审核或改签成功 查询最新的改签订单信息
                    if($goods['change_status'] >= 2){
                        //查询最新的改签订单用户表
                        $changeordergoods = Db::name('hanglvfeike_order_goods')->where('change_original_ogid',$goods['id'])->where('change_status','>=',0)->where('ischange',1)->order('id desc')->find();
                        if($changeordergoods){

                            //有退款申请及退款成功的不发放
                            if($changeordergoods['refund_status'] > 0 && $changeordergoods['refund_status'] < 4){
                                //已退款则取消佣金发放
                                if($changeordergoods['refund_status'] == 3){
                                    Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>2]);
                                }
                                continue;
                            }

                            //有改签申请和改签成功的的不发放
                            if($changeordergoods['change_status'] >=1 ) continue;

                            //查询改签订单是否存在，不存在的跳过
                            $changeorder = Db::name('hanglvfeike_order')->where('id',$changeordergoods['orderid'])->field('id,aid,fromDatetime')->find();
                            if(!$changeorder) continue;
                        }
                    }

                    //标记
                    //Db::name('hanglvfeike_order')->where('id',$order['id'])->where('status',2)->where('iscommission',0)->update(['iscommission'=>2]);
                    Db::name('hanglvfeike_order_goods')->where('id',$goods['id'])->where('iscommission','>=',1)->where('iscommission','<=',2)->update(['iscommission'=>2]);

                    Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                    if($commission_record['commission'] > 0){
                        $commission = $commission_record['commission'];
                        $levelid = 0;
                        $fxfh_send_money = 0;
                        if($fxfh_send_wallet==1){
                            //分销分红发放钱包等级限制
                            $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                            if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                                $fxfh_send_money = 1;
                            }
                        }
                        if($fxfh_send_money==1){
                            //发放到余额
                            \app\common\Member::addmoney($aid,$commission_record['mid'],$commission,$commission_record['remark'],$commission_record['frommid']);
                        }else{
                            \app\common\Member::addcommission($aid,$commission_record['mid'],$commission_record['frommid'],$commission,$commission_record['remark'],1,'fenxiao',$levelid,'',$commission_record['id']);
                        }
                        $tmplcontent = [];
                        $tmplcontent['first'] = '恭喜您，成功分销商品获得'.t('佣金').'：￥'.$commission_record['commission'];
                        $tmplcontent['remark'] = '点击进入查看~';
                        $tmplcontent['keyword1'] = '机票商品'; //商品信息
                        $tmplcontent['keyword2'] = (string) $goods['totalprice'];//商品单价
                        $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                        $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                        $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                        //短信通知
                        $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                        $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    }
                    if($commission_record['score'] > 0){
                        \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                        $tmplcontent = [];
                        $tmplcontent['first'] = '恭喜您，成功分销商品获得：'.$commission_record['score'].t('积分');
                        $tmplcontent['remark'] = '点击进入查看~';
                        $tmplcontent['keyword1'] = $goods['name']; //商品信息
                        $tmplcontent['keyword2'] = (string) $goods['totalprice'];//商品单价
                        $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                        $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                        $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                        //短信通知
                        //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                        //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                    }

                }
            }
        }elseif($type=='zhiyoubao'){
            if(getcustom('extend_zhiyoubao_theater')){
                $aid = $order['aid'];
                $mid = $order['mid'];

                //查询佣金记录
                $commission_record_list = Db::name('member_commission_record')->where('orderid',$order['id'])->where('type',$type)->where('status',0)->where('aid',$aid)->select()->toArray();
                if(!$commission_record_list){
                    Db::name('zhiyoubao_order')->where('id',$order['id'])->where('iscommission',1)->update(['iscommission'=>2]);
                    return;
                } 
                foreach($commission_record_list as $commission_record){
                    //查询相关的订单用户表
                    $cert = Db::name('zhiyoubao_order_goods_certs')->where('id',$commission_record['ogid'])->where('orderid',$commission_record['orderid'])->where('iscommission','>=',1)->where('iscommission','<=',2)->find();
                    if(!$cert){
                        Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>2]);
                        continue;
                    }

                    //有退款申请及退款成功的不发放
                    if($cert['refund_status'] > 0 && $cert['refund_status'] < 4){
                        //已退款则取消佣金发放
                        if($cert['refund_status'] == 3){
                            Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>2]);
                        }
                        continue;
                    } 

                    //标记
                    Db::name('zhiyoubao_order_goods_certs')->where('id',$cert['id'])->where('iscommission','>=',1)->where('iscommission','<=',2)->update(['iscommission'=>2]);
                    Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                    if($commission_record['commission'] > 0){
                        $commission = $commission_record['commission'];
                        $levelid = 0;
                        $fxfh_send_money = 0;
                        if($fxfh_send_wallet==1){
                            //分销分红发放钱包等级限制
                            $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                            if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                                $fxfh_send_money = 1;
                            }
                        }
                        if($fxfh_send_money==1){
                            //发放到余额
                            \app\common\Member::addmoney($aid,$commission_record['mid'],$commission,$commission_record['remark'],$commission_record['frommid']);
                        }else{
                            \app\common\Member::addcommission($aid,$commission_record['mid'],$commission_record['frommid'],$commission,$commission_record['remark'],1,'fenxiao',$levelid,'',$commission_record['id']);
                        }
                        $tmplcontent = [];
                        $tmplcontent['first'] = '恭喜您，成功分销商品获得'.t('佣金').'：￥'.$commission_record['commission'];
                        $tmplcontent['remark'] = '点击进入查看~';
                        $tmplcontent['keyword1'] = '票务商品'; //商品信息
                        $tmplcontent['keyword2'] = (string) $cert['totalprice'];//商品单价
                        $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                        $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                        $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                        //短信通知
                        $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                        $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    }
                    if($commission_record['score'] > 0){
                        \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                        $tmplcontent = [];
                        $tmplcontent['first'] = '恭喜您，成功分销商品获得：'.$commission_record['score'].t('积分');
                        $tmplcontent['remark'] = '点击进入查看~';
                        $tmplcontent['keyword1'] = $cert['name']; //商品信息
                        $tmplcontent['keyword2'] = (string) $cert['totalprice'];//商品单价
                        $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                        $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                        $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                        //短信通知
                        //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                        //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                    }

                }
            }
        }elseif($type=='water_happyti'){
            $teamfenhong_shouyi = getcustom('teamfenhong_shouyi',$aid);
            if(getcustom('water_happy_ti',$aid)){
                $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type','water_happyti')->where('orderid',$order['id'])->where('status',0)->select();
                foreach($commission_record_list as $commission_record){
                    Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                    if($commission_record['commission'] > 0){
                        $fxfh_send_money = 0;
                        if($fxfh_send_wallet==1){
                            //分销分红发放钱包等级限制
                            $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                            if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                                $fxfh_send_money = 1;
                            }
                        }
                        if($fxfh_send_money==1){
                            //发放到余额
                            \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                        }else {
                            \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                        }
                        $tmplcontent = [];
                        $tmplcontent['first'] = '恭喜您，成功分销商品获得'.t('佣金').'：￥'.$commission_record['commission'];
                        $tmplcontent['remark'] = '点击进入查看~';
                        $tmplcontent['keyword1'] = $order['title']; //商品信息
                        $tmplcontent['keyword2'] = (string) $order['product_price'];//商品单价
                        $tmplcontent['keyword3'] = sprintf('%g',$commission_record['commission']).'元';//商品佣金
                        $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                        $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                        //短信通知
                        $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                        $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                        //根据分红奖团队收益
                        if($teamfenhong_shouyi){
                            $sysset = Db::name('admin_set')->where('aid',$aid)->find();
                            $midfhArr = [
                                $commission_record['mid']=>[
                                    'commission' => $commission_record['commission'],
                                    'ogids' =>$commission_record['ogid'],
                                    'module' => $commission_record['type']
                                ]
                            ];
                            \app\common\Fenhong::teamshouyi($aid,$sysset,$midfhArr,[],0,0,1,0);
                        }
                    }
                    if($commission_record['score'] > 0){
                        \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                        $tmplcontent = [];
                        $tmplcontent['first'] = '恭喜您，成功分销商品获得：'.$commission_record['score'].t('积分');
                        $tmplcontent['remark'] = '点击进入查看~';
                        $tmplcontent['keyword1'] = $order['title']; //商品信息
                        $tmplcontent['keyword2'] = (string) $order['product_price'];//商品单价
                        $tmplcontent['keyword3'] = sprintf('%g',$commission_record['score']).t('积分');//商品佣金
                        $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                        $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                        //短信通知
                        //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                        //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                    }
                }
            }
        } elseif($type == 'gold_bean_shop'){
            //佣金
            $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('type',$type)->where('orderid',$order['id'])->where('status',0)->select();
            foreach($commission_record_list as $commission_record){
                Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>1,'endtime'=>time()]);
                $og = Db::name($type.'_order_goods')->where('id',$commission_record['ogid'])->find();
                Db::name($type.'_order_goods')->where('id',$commission_record['ogid'])->update(['iscommission'=>1]);
                if($commission_record['commission'] > 0){
                    $fxfh_send_money = 0;
                    if($fxfh_send_wallet==1){
                        //分销分红发放钱包等级限制
                        $member_levelid = Db::name('member')->where('id',$commission_record['mid'])->value('levelid');
                        if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                            $fxfh_send_money = 1;
                        }
                    }
                    if($fxfh_send_money==1){
                        //发放到余额
                        \app\common\Member::addmoney($aid,$commission_record['mid'],$commission_record['commission'],$commission_record['remark'],$commission_record['frommid']);
                    }else {
                        \app\common\Member::addcommission($aid, $commission_record['mid'], $commission_record['frommid'], $commission_record['commission'], $commission_record['remark'], 1, 'fenxiao', '', '', $commission_record['id']);
                    }
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得'.t('佣金').'：￥'.$commission_record['commission'];
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['name']; //商品信息
                    $tmplcontent['keyword2'] = (string) $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    $tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    $rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess',['money'=>$commission_record['commission']]);
                    //根据分红奖团队收益
                    if(getcustom('teamfenhong_shouyi')){
                        $sysset = Db::name('admin_set')->where('aid',$aid)->find();
                        $midfhArr = [
                            $commission_record['mid']=>[
                                'commission' => $commission_record['commission'],
                                'ogids' =>$commission_record['ogid'],
                                'module' => $commission_record['type']
                            ]
                        ];
                        \app\common\Fenhong::teamshouyi($aid,$sysset,$midfhArr,[],0,0,1,0);
                    }
                }
                if($commission_record['score'] > 0){
                    \app\common\Member::addscore($aid,$commission_record['mid'],$commission_record['score'],$commission_record['remark']);
                    $tmplcontent = [];
                    $tmplcontent['first'] = '恭喜您，成功分销商品获得：'.$commission_record['score'].t('积分');
                    $tmplcontent['remark'] = '点击进入查看~';
                    $tmplcontent['keyword1'] = $og['name']; //商品信息
                    $tmplcontent['keyword2'] = $og['sell_price'];//商品单价
                    $tmplcontent['keyword3'] = $commission_record['score'].t('积分');//商品佣金
                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    //短信通知
                    //$tel = Db::name('member')->where('id',$commission_record['mid'])->value('tel');
                    //$rs = \app\common\Sms::send($aid,$tel,'tmpl_fenxiaosuccess');
                }
            }
        }

        if(getcustom('commission_butie')){
            $sysset = Db::name('admin_set')->where('aid',$aid)->find();
            $fx_butie_type = $sysset['fx_butie_type'];
            $fx_butie_circle = $sysset['fx_butie_circle'];
            //记录分销补贴
            $butie_ids = [];
            foreach($commission_record_list as $record){
                if($record['butie']<=0){
                    continue;
                }
                //记录补贴数据
                $data_butie = [];
                $data_butie['aid'] = $record['aid'];
                $data_butie['mid'] = $record['mid'];
                $data_butie['frommid'] = $record['frommid'];
                $data_butie['orderid'] = $record['orderid'];
                $data_butie['ogid'] = $record['ogid'];
                $data_butie['type'] = $record['type'];
                $data_butie['commission'] = $record['butie'];
                $data_butie['fx_butie_type'] = $fx_butie_type;
                $data_butie['fx_butie_circle'] = $fx_butie_circle;
                $data_butie['fx_butie_send_week'] = $sysset['fx_butie_send_week'];
                $data_butie['fx_butie_send_day'] = $sysset['fx_butie_send_day'];;
                $data_butie['createtime'] = time();
                $data_butie['record_id'] = $record['id'];
                $data_butie['remark'] = $record['remark'];
                $butie_id = Db::name('member_commission_butie')->insertGetId($data_butie);
                $butie_ids[] = $butie_id;
            }
            if($butie_ids){
                \app\common\Member::commission_butie($record['aid'],$butie_ids);
            }
        }

        return ['status'=>1,'msg'=>'操作成功'];
    }

    //重新计算分成
    public static function updateCommission($orderGoods, $refundOrderGoods)
    {
        $newkey = 'ogid';
        $refundOrderGoods = collect($refundOrderGoods)->dictionary(null,$newkey);
        $ogids = array_keys($refundOrderGoods);
        foreach ($orderGoods as $og) {
            if(in_array($og['id'], $ogids)) {
                $new = [];
                if($og['parent1'] && ($og['parent1commission'] || $og['parent1score'])) {
                    $record = [];
                    if($og['parent1commission']) {
                        $new['parent1commission'] = $og['parent1commission'] / $og['num'] * ($og['num'] - $og['refund_num']);
                        $record['commission'] = $new['parent1commission'];
                    }
                    if($og['parent1score']) {
                        $new['parent1score'] = $og['parent1score'] / $og['num'] * ($og['num'] - $og['refund_num']);
                        $record['score'] = $new['parent1score'];
                    }
                    if($record) {
                        Db::name('member_commission_record')->where('mid',$og['parent1'])->where('aid',$og['aid'])->where('orderid',$og['orderid'])
                            ->where('ogid', $og['id'])->where('type', 'shop')->update($record);
                    }
                }
                if($og['parent2'] && ($og['parent2commission'] || $og['parent2score'])) {
                    $record = [];
                    if($og['parent2commission']) {
                        $new['parent2commission'] = $og['parent2commission'] / $og['num'] * ($og['num'] - $og['refund_num']);
                        $record['commission'] = $new['parent2commission'];
                    }
                    if($og['parent2score']) {
                        $new['parent2score'] = $og['parent2score'] / $og['num'] * ($og['num'] - $og['refund_num']);
                        $record['score'] = $new['parent2score'];
                    }
                    if($record) {
                        Db::name('member_commission_record')->where('mid',$og['parent2'])->where('aid',$og['aid'])->where('orderid',$og['orderid'])
                            ->where('ogid', $og['id'])->where('type', 'shop')->update($record);
                    }
                }
                if($og['parent3'] && ($og['parent3commission'] || $og['parent3score'])) {
                    $record = [];
                    if($og['parent3commission']) {
                        $new['parent3commission'] = $og['parent3commission'] / $og['num'] * ($og['num'] - $og['refund_num']);
                        $record['commission'] = $new['parent3commission'];
                    }
                    if($og['parent3score']) {
                        $new['parent3score'] = $og['parent3score'] / $og['num'] * ($og['num'] - $og['refund_num']);
                        $record['score'] = $new['parent3score'];
                    }
                    if($record) {
                        Db::name('member_commission_record')->where('mid',$og['parent3'])->where('aid',$og['aid'])->where('orderid',$og['orderid'])
                            ->where('ogid', $og['id'])->where('type', 'shop')->update($record);
                    }
                }
                if($new)
                    Db::name('shop_order_goods')->where('id', $og['id'])->update($new);
            }
        }
    }

    //递归分销:按订单金额的50%[变量]一直向下分销，直到金额小于1[变量]元截止；
    public static function recursionCommission($aid,$mid=0,$totalprice=0,$orderid='',$ogid='',$type='shop'){
        if(getcustom('commission_recursion')) {
            if (empty($mid)) return;
            //所有的父级
            $adminset = Db::name('admin_set')->where('aid', $aid)->field('is_fugou_commission,fugou_recursion_percent,fugou_commission_min')->find();
            if (empty($adminset['is_fugou_commission']) || empty($adminset['fugou_recursion_percent'])) {
                return;
            }
            $commission_min = $adminset['fugou_commission_min'] ?? 0;
            $recursionPercent = $adminset['fugou_recursion_percent'];
            $rate = round(100 / $recursionPercent, 2);
            self::recursionCommissionAllParent($aid, $mid, $totalprice, $commission_min, $recursionPercent, $rate, $mid, $orderid, $ogid);
        }
    }
    public static function shougouReward($aid,$mid=0,$totalprice=0,$orderid='',$ogid='',$type='shop'){
        if(getcustom('member_shougou_parentreward')) {
            if (empty($mid)) return;
            //所有的父级
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->field('id,levelid,pid')->find();
            $memberPreLevel = Db::name('member_level')->where('aid',$aid)->where('id',$member['levelid'])->field('commissionsg1,commissionsg2,commissionsg3,can_agent')->find();
            if(!$memberPreLevel['can_agent']){
                return;
            }
            $canAgent = $memberPreLevel['can_agent'];
            //查询上级
            $hasParent1 = $hasParent2 = $hasParent3 = false;
            $onebuy_commission1 = $onebuy_commission2 = $onebuy_commission3 = 0;
            $onebuy_commissionjs = 1;//按当前购买者等级配置发放
            //是否按会员当前购买等级算奖励
            $parent = Db::name('member')->where('id', $member['pid'])->field('id,pid,levelid')->find();
            //$onebuy_commissionjs = $memberPreLevel['onebuy_commissionjs']??0;
            if($memberPreLevel && $onebuy_commissionjs==1){
                $onebuy_commission1 = $memberPreLevel['commissionsg1'];
                $onebuy_commission2 = $memberPreLevel['commissionsg2'];
                $onebuy_commission3 = $memberPreLevel['commissionsg3'];
            }
            //首购奖励和一次性升级不重复发
            $member_commission_table = 'member_commission_record';
            if(getcustom('member_shougou_parentreward_wait')){
                $member_commission_table = 'member_commission_record_wait';
            }
            if ($parent) {
                $hasParent1 = true;
                if ($onebuy_commissionjs==0 && $parent['levelid']) {
                    $level = Db::name('member_level')->where('id', $parent['levelid'])->field('commissionsg1')->find();
                    if ($level && $level['commissionsg1'] > 0) {
                        $onebuy_commission1 = $level['commissionsg1'];
                    }
                }
                if ($canAgent>1 && $parent['pid']) {
                    //查询上级
                    $parent2 = Db::name('member')->where('id', $parent['pid'])->field('id,pid,levelid')->find();
                    if ($parent2) {
                        $hasParent2 = true;
                        if ($onebuy_commissionjs==0 && $parent2['levelid']) {
                            $level = Db::name('member_level')->where('id', $parent2['levelid'])->field('commissionsg2')->find();
                            if ($level && $level['commissionsg2'] > 0) {
                                $onebuy_commission2 = $level['commissionsg2'];
                            }
                        }
                        if ($canAgent>2 && $parent2['pid']) {
                            //查询上级
                            $parent3 = Db::name('member')->where('id', $parent2['pid'])->field('id,pid,levelid')->find();
                            if ($parent3) {
                                $hasParent3 = true;
                                if ($onebuy_commissionjs==0 && $parent3['levelid']) {
                                    $level = Db::name('member_level')->where('id', $parent3['levelid'])->field('commissionsg3')->find();
                                    if ($level && $level['commissionsg3'] > 0) {
                                        $onebuy_commission3 = $level['commissionsg3'];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $updata = ['aid' => $aid, 'mid' => 0, 'frommid' => $mid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => 0, 'score' => 0, 'remark' => '', 'createtime' => time(),'islock'=>0];
            if(getcustom('ciruikang_fenxiao')){
                $updata['islock'] = 1;
            }
            if($onebuy_commission1>0 && $hasParent1){
                $money = $totalprice * $onebuy_commission1 / 100;
                $money = round($money, 2);
                if ($money > 0) {
                    $updata['mid']        = $parent['id'];
                    $updata['commission'] = $money;
                    $updata['remark']     = '下级首购奖励';
                    Db::name($member_commission_table)->insert($updata);
                }
            }
            if($onebuy_commission2>0 && $hasParent2){
                $money2 = $totalprice * $onebuy_commission2 / 100;
                $money2 = round($money2, 2);
                if ($money2 > 0) {
                    $updata['mid']        = $parent2['id'];
                    $updata['commission'] = $money2;
                    $updata['remark']     = '下下级首购奖励';
                    //首购奖励和一次性升级不重复发
                    Db::name($member_commission_table)->insert($updata);
                }
            }
            if($onebuy_commission3>0 && $hasParent3){
                $money3 = $totalprice * $onebuy_commission3 / 100;
                $money3 = round($money3, 2);
                if($money3 > 0) {
                    $updata['mid']        = $parent3['id'];
                    $updata['commission'] = $money3;
                    $updata['remark']     = '下三级首购奖励';
                    Db::name($member_commission_table)->insert($updata);
                }
            }
        }
    }

    //递归发佣金
    public static function recursionCommissionAllParent($aid,$mid,$totalprice,$commission_min,$recursionPercent,$rate=2,$frommid='',$orderid='',$ogid=''){
        if(getcustom('commission_recursion')) {
            if ($totalprice <= $commission_min) {
                return;
            }
            $commission = $totalprice * $recursionPercent * 0.01;
            if ($commission <= $commission_min) {
                return;
            }
            $member = Db::name('member')->where('aid', $aid)->where('id', $mid)->field('id,levelid,pid,path,path_origin')->find();
            if (empty($member['pid'])) return;
            $parent = Db::name('member')->alias('m')->join('member_level l', 'm.levelid=l.id')
                ->where('m.aid', $aid)
                ->where('m.id', $member['pid'])->field('m.id,m.levelid,m.pid,l.is_fugou_commission')->find();
            if ($parent['is_fugou_commission']) {
                Db::name('member_commission_record')->insert(['aid' => $aid, 'mid' => $parent['id'], 'frommid' => $frommid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $commission, 'score' => 0, 'remark' => '下级购物奖励[递归]', 'createtime' => time()]);
            }
            //如果有上级 继续发奖励
            if ($parent['pid'] > 0) {
                $recursionPercent = $recursionPercent / $rate;
                return self::recursionCommissionAllParent($aid, $parent['id'], $totalprice, $commission_min, $recursionPercent, $rate, $frommid, $orderid, $ogid);
            }
            return;
        }
    }

    //小程序 发货信息录入接口 app/controller/ApiWechat.php异步通知收货
    public static function wxShipping($aid,$order,$orderType = 'shop',$shippingInfo=[]){
        \think\facade\Log::write('wxShipping发货信息录入接口orderType:'.$orderType);
        \think\facade\Log::write('wxShipping发货信息录入接口:'.jsonEncode(['ordernum'=>$order['ordernum'],'paynum'=>$order['paynum'],'aid'=>$order['aid'],'bid'=>$order['bid'],'mid'=>$order['mid'],'freight_type'=>$order['freight_type']]));
        $isTradeManaged = \app\common\Wechat::isTradeManaged($aid);
        if($isTradeManaged['status'] == 1 && $isTradeManaged['is_trade_managed']){
            \think\facade\Log::write('is_trade_managed true');
//            \think\facade\Log::write('wxShipping发货信息录入接口shippingInfo:'.jsonEncode($shippingInfo));
            //发货信息录入接口 https://developers.weixin.qq.com/miniprogram/dev/platform-capabilities/business-capabilities/order-shipping/order-shipping.html#%E4%B8%80%E3%80%81%E5%8F%91%E8%B4%A7%E4%BF%A1%E6%81%AF%E5%BD%95%E5%85%A5%E6%8E%A5%E5%8F%A3
            $orderType = str_replace('_order','',$orderType);
            if($orderType == 'yuyue' && $order['balance_pay_orderid']){
                //预约订单尾款支付
                $orderBal = $order;
                $orderBal['ordernum'] = \db('payorder')->where('aid',$aid)->where('id',$order['balance_pay_orderid'])->value('ordernum');
                self::wxShipping($aid,$orderBal,'yuyue_balance',$shippingInfo);
            }

            $wxpaylog = [];
            //先查询流水号
            $pay_transaction = Db::name('pay_transaction')->where(['aid'=>$aid,'ordernum'=>$order['ordernum'],'type'=>$orderType,'status'=>1])->order('id desc')->find();
            if($pay_transaction){
                $wxpaylog = Db::name('wxpay_log')->where('aid',$aid)->where('ordernum',$pay_transaction['transaction_num'])->where('tablename',$orderType)->find();
            }
            if(!$wxpaylog){
                $wxpaylog = Db::name('wxpay_log')->where('aid',$aid)->where('ordernum',$order['ordernum'])->where('tablename',$orderType)->find();
            }
            $wxpaylog_type = 'wx';//默认是 微信支付的记录，如果查询不到可能是汇付支付的记录
            if(empty($wxpaylog)){
                $wxpaylog = Db::name('wxpay_log')->where('aid',$aid)->where('transaction_id',$order['paynum'])->where('tablename','daifu')->find();
                if(getcustom('pay_huifu')){
                    if(empty($wxpaylog)){
                        //可能是汇付，汇付支付走 huifu_log，需要查询
                        $wxpaylog = Db::name('huifu_log')->where('aid',$aid)->where('ordernum',$order['ordernum'])->where('tablename',$orderType)->find();
                        if($wxpaylog){
                            //取出汇付返回值中的 原微信的交易单号
                            $notifydata = json_decode($wxpaylog['notify_data'],true);
                            if($notifydata){
                                $wxpaylog['transaction_id'] = $notifydata['out_trans_id'];
                                $wxpaylog['openid'] = $notifydata['wx_response']['openid'];
                                $wxpaylog['mch_id'] = '';
                            }
                            $wxpaylog_type = 'huifu';
                        }
                    }
                }
                if(!$wxpaylog){
                    \think\facade\Log::write('wxShipping发货信息录入接口:未找到微信支付记录');
                    return ['status'=>0,'msg'=>'未找到微信支付记录'];
                }
            }
            if(getcustom('product_quanyi',$aid)){
                //权益商品会多次核销，防止重复发送
                if($wxpaylog['is_upload_shipping_info']){
                    return ['status'=>1,'msg'=>'已发送'];
                }
            }
            if($wxpaylog['platform'] != 'wx'){
                \think\facade\Log::write('wxShipping发货信息录入接口:非微信小程序支付订单');
                return ['status'=>0,'msg'=>'非微信小程序支付订单'];
            }
            //支付不足20秒不请求微信接口
            if($wxpaylog['createtime'] && time() - $wxpaylog['createtime'] < 20){
                $data = [
                    'aid'=>$aid,
                    'mid'=>$order['mid'],
                    'wxpaylogid'=>$wxpaylog['id'],
                    'openid'=>$wxpaylog['openid'],
                    'tablename'=>$wxpaylog['tablename'],
                    'ordernum'=>$wxpaylog['ordernum'],
                    'mch_id'=>$wxpaylog['mch_id'],
                    'createtime'=>time(),
                    'nexttime'=>time()+20,
                    'times_failed'=>0,
                    'status'=>0
                ];
                Db::name('wx_upload_shipping')->insert($data);
                \think\facade\Log::write('wxShipping发货信息录入接口:支付不足20秒不请求微信接口 稍后再次发起');
                return ['status'=>0,'msg'=>'稍后再试'];
            }
            $shipping_list = ['item_desc'=> $order['title']?$order['title']:self::getOrderTypeName($orderType)];
            //0普通快递 1到店自提 2同城配送 3自动发货 4在线卡密
            if(in_array($orderType,['restaurant_shop','huodong_baoming','lipin'])){
                //无需配送 订单
                $postdata['logistics_type'] = self::getWxLogisticsType();
            }else{
                if($order['freight_type'] === 0){
                    if(empty($shippingInfo)){
                        if($order['express_content'] && !empty($order['express_content'])){
                            $express_content = json_decode($order['express_content'],true);
                            if($express_content){
                                $shippingInfo['express_no'] = $express_content[0]['express_no'];
                                $shippingInfo['express_com'] = $express_content[0]['express_com'];
                            }
                        }else{
                            $shippingInfo['express_no'] = $order['express_no'];
                            $shippingInfo['express_com'] = $order['express_com'];
                        }

                    }
                    $shipping_list['tracking_no'] = $shippingInfo['express_no'];   //tracking_no 物流单号，物流快递发货时必填
                    $shipping_list['express_company'] = \app\common\Wxvideo::get_delivery_id($shippingInfo['express_com']);
                    $shipping_list['contact']['receiver_contact'] = substr($order['tel'], 0,3).'****'.substr($order['tel'],-4);
                    //contact 联系方式，当发货的物流公司为顺丰时，联系方式为必填，收件人或寄件人联系方式二选一
                }
                $postdata['logistics_type'] = self::getWxLogisticsType($order['freight_type'],$orderType);//物流模式，发货方式枚举值：1、实体物流配送采用快递公司进行实体物流配送形式 2、同城配送 3、虚拟商品，虚拟商品，例如话费充值，点卡等，无实体配送形式 4、用户自提
            }
            $postdata['shipping_list'] = [$shipping_list];
            $postdata['order_key'] = [
                'order_number_type' => 2,
                'transaction_id'=>$wxpaylog['transaction_id']
//                'mchid' => $appinfo['wxpay_type'] == 0 ? $appinfo['wxpay_mchid'] : $appinfo['wxpay_sub_mchid'],
//                'out_trade_no'=>$order['ordernum']
            ];
            $postdata['delivery_mode'] = 1;
            $postdata['upload_time'] = date(DATE_RFC3339,time());
            $member = Db::name('member')->where('id',$wxpaylog['mid'])->find();//可能存在代付
            $postdata['payer'] = [
                'openid' => $member['wxopenid']
            ];
            $rs = \app\common\Wechat::uploadShippingInfo($aid,'wx',$postdata);
            if($rs['status'] == 1){
                if($wxpaylog_type =='wx'){
                    Db::name('wxpay_log')->where('aid',$aid)->where('id',$wxpaylog['id'])->update(['is_upload_shipping_info'=>1]);
                }else{
                    Db::name('huifu_log')->where('aid',$aid)->where('id',$wxpaylog['id'])->update(['is_upload_shipping_info'=>1]);
                }
            }
            return $rs;
        }
    }

    //再次录入发货信息
    public static function retryUploadShipping()
    {
        $time = time();
        $list = Db::name('wx_upload_shipping')->where('status',0)->where('nexttime','<=',$time)->where('times_failed','<',3)->where('tablename','<>','daifu')
            ->select()->toArray();
        if($list){
            foreach ($list as $item){

                $trade_no = explode('D', $item['ordernum']);
                $ordernum = $trade_no[0];

                if($item['tablename'] == 'yuyue_balance'){
                    $payorderid = \db('payorder')->where('aid',$item['aid'])->where('type',$item['tablename'])->where('ordernum',$ordernum)->value('id');
                    $order = Db::name('yuyue_order')->where('aid',$item['aid'])->where('balance_pay_orderid',$payorderid)->find();
                    $order['ordernum'] = $ordernum;
                }else{
                    $order = Db::name($item['tablename'].'_order')->where('aid',$item['aid'])->where('ordernum',$ordernum)->find();
                }

                if($order['paytypeid'] == 2 || $order['paytypeid'] == 1){
                    $rs = \app\common\Order::wxShipping($order['aid'],$order,$item['tablename']);
                    if($rs['status'] == 1){
                        Db::name('wx_upload_shipping')->where('id',$item['id'])->update(['status'=>1]);
                    }else{
                        Db::name('wx_upload_shipping')->where('id',$item['id'])->update(['nexttime'=>$time+60,'times_failed'=>$item['times_failed']+1]);
                    }
                }else{
                    Db::name('wx_upload_shipping')->where('id',$item['id'])->update(['status'=>-1]);
                }
            }
        }
    }

    /**
     * 微信物流模式
     * @param $freight_type  0普通快递 1到店自提 2同城配送 3自动发货 4在线卡密 5门店配送
     * @return void
     */
    public static function getWxLogisticsType($freight_type='',$orderType = ''){
        //物流模式，发货方式枚举值：1、实体物流配送采用快递公司进行实体物流配送形式 2、同城配送 3、虚拟商品，虚拟商品，例如话费充值，点卡等，无实体配送形式 4、用户自提
        if($orderType == 'maidan'){
            return 4;
        }
        if($freight_type === 0){
            return 1;
        }else if($freight_type == 1){
            return 4;
        }else if($freight_type == 2){
            return 2;
        }else if($freight_type == 3){
            return 3;
        }else if($freight_type == 4){
            return 3;
        }
        return 3;
    }


    /**
     * 通过订单类型获得订单类型名称
     * @param $orderType
     * @return string
     */
    public static function getOrderTypeName($orderType)
    {
        $typeName = '订单';
        $orderType = str_replace('_order','',$orderType);
        if($orderType == 'shop'){
            $typeName = '商城订单';
        }elseif($orderType == 'recharge'){
            $typeName = '充值订单';
        }elseif($orderType == 'miandan'){
            $typeName = '买单订单';
        }elseif($orderType == 'scoreshop'){
            $typeName = t('积分').'兑换订单';
        }elseif($orderType == 'seckill'){
            $typeName = '秒杀订单';
        }elseif($orderType == 'collage'){
            $typeName = '拼团订单';
        }elseif($orderType == 'lucky_collage'){
            $typeName = t('幸运拼团').'订单';
        }elseif($orderType == 'coupon'){
            $typeName = t('优惠券').'订单';
        }elseif($orderType == 'cycle'){
            $typeName = '周期购订单';
        }elseif($orderType == 'hbtk'){
            $typeName = '拓客订单';
        }elseif($orderType == 'kanjia'){
            $typeName = '砍价订单';
        }elseif($orderType == 'kecheng'){
            $typeName = '知识付费订单';
        }elseif($orderType == 'paotui'){
            $typeName = '跑腿订单';
        }elseif($orderType == 'restaurant_booking'){
            $typeName = '预定订单';
        }elseif($orderType == 'restaurant_shop'){
            $typeName = '点餐订单';
        }elseif($orderType == 'restaurant_takeaway'){
            $typeName = '外卖订单';
        }elseif($orderType == 'yuyue'){
            $typeName = '预约服务订单';
        }elseif($orderType == 'form'){
            $typeName = '表单订单';
        }elseif($orderType == 'member_levelup'){
            $typeName = t('会员').'升级';
        }elseif($orderType == 'cashier'){
            $typeName = '收银台';
        }elseif($orderType == 'designerpage'){
            $typeName = '页面';
        }elseif($orderType == 'hanglvfeike'){
            $typeName = '机票';
        }elseif($orderType == 'xianjin_recharge'){
            $typeName = t('现金').'充值订单';
        }elseif($orderType == 'zhiyoubao'){
            $typeName = '票务订单';
        }

        return $typeName;
    }


    /**
     * 判断订单类型是否有orderGoods表
     * @param $orderType 订单类型
     * @return void
     */
    public static function hasOrderGoodsTable($orderType)
    {
        if(empty($orderType)){
            return false;
        }
        $hasOrderGoodsArr = [
            'shop',
            'scoreshop',
            'restaurant_shop',
            'restaurant_booking',
            'restaurant_takeaway',
            'cashier',
            'gift_bag',
            'hanglvfeike',
            'zhiyoubao'
        ];
        if(in_array($orderType,$hasOrderGoodsArr)){
            return true;
        }
        return table_exists($orderType.'_order_goods');
    }

    /**
     * 通过快递名字查询微信物流公司编码
     * @param $aid
     * @param $express_com
     * @return void ‘STO’
     */
    public static function getWxExpressCompany($aid,$express_com){
        $rs = \app\common\Wechat::get_delivery_list($aid);
        if($rs['status'] == 1) {
            $srs = collect($rs['delivery_list'])->where('delivery_name','=',$express_com)->first();
            return $srs['delivery_id'];
        }
    }

    /**
     * @param $aid
     * @param $orderid
     * @param $order
     * @param $mid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function collectCheck($aid,$orderid=0,$order=[],$mid=0)
    {
        if(empty($order))
            $order = Db::name('shop_order')->where('aid',$aid)->where('mid',$mid)->where('id',$orderid)->find();
        else
            $orderid = $order['id'];

        if(!$order || ($order['status']!=2) || $order['paytypeid']==4){
            if(getcustom('mendian_upgrade') ){
                $mendian_upgrade_status = Db::name('admin')->where('id',$order['aid'])->value('mendian_upgrade_status');
                if($mendian_upgrade_status && $order['status'] == 8){
                    //社区团购
                }else{
                    return ['status'=>0,'msg'=>'订单状态不符合收货要求'];
                }
            }else{
                return ['status'=>0,'msg'=>'订单状态不符合收货要求'];
            }
        }

        if(getcustom('product_collect_time')){
            //查询可确认收货时间
            $shopset = Db::name('shop_sysset')->where('aid',$aid)->field('ordercollect_time')->find();
            $start_time = time()-$shopset['ordercollect_time']*86400;
            if($order['send_time']>$start_time){
                return ['status'=>0,'msg'=>'订单发货后'.$shopset['ordercollect_time'].'天之后才可点击确认收货'];
            }
        }

        $refundOrder = Db::name('shop_refund_order')->where('refund_status','in',[1,4])->where('aid',$aid)->where('mid',$mid)->where('orderid',$orderid)->count();
        if($refundOrder){
            return ['status'=>0,'msg'=>'有正在进行的退款，无法确认收货'];
        }
        if($order['balance_price'] > 0 && $order['balance_pay_status']==0)
            return ['status'=>0,'msg'=>'请先支付尾款'];

        return ['status'=>1,'msg'=>'ok'];
    }

    public static function teamYejiManage($aid,$member){
        if(getcustom('yx_team_yeji_manage')){
            //团队管理奖
//            Db::startTrans();
//            dump($member);
            if(empty($member['path'])) return false;
            $set = Db::name('team_yeji_manage_set')->where('aid',$aid)->where('status',1)->find();
//            dump(Db::getLastSql());
//            dump($set);
            if($set['status'] != 1) return false;
            $config = json_decode($set['config_data'],true);
            if(empty($config)) return false;
            $levelids = array_keys($config);

            //查询所有父级
            $parentList = \app\common\Member::getParentsByPath($aid,$member['path'],[['levelid','in',$levelids]]);
            if(empty($parentList)) return false;
//            dump($parentList);

            foreach ($parentList as $parent){
                $configLevel = $config[$parent['levelid']];
//                dump($parent['id'].'-'.$parent['nickname']);
//                dump($configLevel);
                if(empty($configLevel)) continue;
                //直推团队
                $children1 = Db::name('member')->where('aid',$aid)->where('pid',$parent['id'])->column('id');
                if(empty($children1)) continue;
                //排除已发放
                $logmids = Db::name('team_yeji_manage')->where('aid',$aid)->where('mid',$parent['id'])->column('from_mid');
                if($logmids){
                    foreach ($children1 as $k => $mid){
                        if(in_array($mid,$logmids)){
                            unset($children1[$k]);
                        }
                    }
                }

                if(count($children1) < $configLevel['teamNum']){
                    continue;
                }
                $commission = 0;
                $score = 0;
                $teamNum = 0;//满足条件的团队数量
                $from_mids = [];
                //任意直推N条线业绩满足条件即为成功
                foreach ($children1 as $k => $mid){
                    if($configLevel['levelNum'] > 1){
                        $childrenmids = \app\common\Member::getdownmids($aid,$mid,$configLevel['levelNum']-1);
                        if(empty($childrenmids)) continue;
                        $childrenmids = array_merge([$mid],$childrenmids);
                    }else{
                        $childrenmids = [$mid];
                    }
//                    dump($childrenmids);

                    $yejiwhere = [];
                    $yejiwhere[] = ['status','in','1,2,3'];
                    $yeji = Db::name('shop_order_goods')->where('aid',$aid)->where('mid','in',$childrenmids)->where($yejiwhere)->sum('real_totalprice');
//                    dump('$yeji:'.$yeji);
                    if($yeji >= $configLevel['yeji']){
                        //满足一个团队
                        $from_mids[] = $mid;
                        $teamNum++;
                        if($teamNum >= $configLevel['teamNum']) break;
                    }
                }
//                dump('$teamNum:'.$teamNum);

                //发奖
                if($teamNum >= $configLevel['teamNum']){
//                if($score > 0){
//                    \app\common\Member::addscore($aid,$parent['id'],$score,'团队管理奖励');
//                    Db::name('member')->where('aid',$set['aid'])->where('id',$parent['id'])->inc('day_give_score_total',$score)->update();
//                }
                    $commission += $configLevel['commission'];
                    if($commission > 0){
                        $commission_member = $commission/$configLevel['teamNum'];
                        foreach ($from_mids as $from_mid){
                            \app\common\Member::addcommission($aid,$parent['id'],$from_mid,$commission_member,'团队管理奖励');
                            Db::name('team_yeji_manage')->insert(['aid'=>$aid,'mid'=>$parent['id'],'from_mid'=>$from_mid,'score'=>$score,'commission'=>$commission_member,'createtime'=>time()]);
                        }
                        Db::name('member')->where('aid',$aid)->where('id',$parent['id'])->inc('team_yeji_manage_commission_total',$commission)->update();
                    }
                }
            }

//            Db::commit();
        }
    }
    /**
     * 所有参与活动的用户平均发放返现
     * aid 商家id
     * mid 会员id
     * cashback 购物返现活动
     * og 商品id
     * back_price_total 返现金额
     */

    public static function cashbackMemerDo($aid,$mid,$cashback,$og,$back_price_total,$cash_type = 0){
        $pro_id = $og['proid'];
        $cashback_member = Db::name('cashback_member')->where('aid',$aid)->where(['cashback_id'=>$cashback['id'],'pro_id'=>$pro_id])->whereRaw('cashback_money_max > cashback_money or cashback_money_max <= 0')->select()->toArray();
        //平均返现
        //$member_num = count($cashback_member);
        $member_num = array_sum(array_column($cashback_member,'pro_num'));
        $av_back_price_one = round($back_price_total/$member_num,2);
        $over_back_price = 0;
        foreach($cashback_member as $k=>$v){
            $av_back_price = $v['pro_num'] * $av_back_price_one;
            //自定义发放
            if($cash_type >= 1){
                \app\custom\OrderCustom::deal_first_cashback($aid,$v['mid'],$av_back_price,$og,$cashback,$cash_type);
            }else{
                if($v['back_type'] == 1 ){
                    $cashback_num = $v['cashback_money'];
                }else if($v['back_type'] == 2 ){
                    $cashback_num = $v['commission'];
                }else if($v['back_type'] == 3 ){
                    $cashback_num = $v['score'];
                }
                if($v['cashback_money_max'] > 0 ){
                    if( $v['cashback_money_max'] > $cashback_num){
                        //最大可追加金额
                        $cashback_money_max = $v['cashback_money_max'] - $cashback_num;
                        if($cashback_money_max < $av_back_price){
                            $over_price = $av_back_price - $cashback_money_max;
                            $av_back_price_tem = $av_back_price;
                            $av_back_price = $cashback_money_max;
                            $over_back_price += $over_price;
                        }
                    }else{
                        continue;
                    }
                    
                }
                if($v['back_type'] == 1 ){
                    \app\common\Member::addmoney($aid,$v['mid'],$av_back_price,$cashback['name']);
                    Db::name('cashback_member')->where('id',$v['id'])->inc('cashback_money',$av_back_price)->update();
                }
                if($v['back_type'] == 2){
                    \app\common\Member::addcommission($aid,$v['mid'],$mid,$av_back_price,$cashback['name']);
                    Db::name('cashback_member')->where('id',$v['id'])->inc('commission',$av_back_price)->update();
                }
                if($v['back_type'] == 3){
                    $av_back_price = round($av_back_price);
                    \app\common\Member::addscore($aid,$v['mid'],$av_back_price,$cashback['name']);
                    Db::name('cashback_member')->where('id',$v['id'])->inc('score',$av_back_price)->update();
                }

                //写入发放日志
                \app\custom\OrderCustom::cashbackMemerDoLog($aid,$v['mid'],$cashback,$og,$av_back_price);
                if($av_back_price_tem){
                    $av_back_price = $av_back_price_tem;
                }
            }
        }
        if($over_back_price >0 ){
            $res = self::cashbackMemerDo($aid,$mid,$cashback,$og,$over_back_price,$cash_type);
        }
        return true;
    }
    /**
     * 所有参与活动的用户平均发放返现
     * aid 商家id
     * mid 会员id
     * cashback 购物返现活动
     * og 商品
     * back_price_total 返现数量
     */

    public static function cashbackMemerDoLog($aid,$mid,$cashback,$og,$back_price_total){
        $cashback_member = [];
        $cashback_member['aid'] = $aid;
        $cashback_member['mid'] = $mid;
        $cashback_member['cashback_id'] = $cashback['id'];
        $cashback_member['pro_id'] = $og['proid'];
        if($cashback['back_type'] == 1 ){
            $cashback_member['cashback_money'] = $back_price_total;
        }else if($cashback['back_type'] == 2 ){
            $cashback_member['commission'] = $back_price_total;
        }else if($cashback['back_type'] == 3 ){
            $cashback_member['score'] = $back_price_total;
        }
        $cashback_member['back_type']   = $cashback['back_type'];
        $cashback_member['type']        = $og['ordertype']??'shop';
        $cashback_member['create_time'] = time();
        $insert = Db::name('cashback_member_log')->insert($cashback_member);
        return $insert;
    }
    //订单退还分红
    public static function refundFenhongDeduct($order,$type='shop'){
        if(getcustom('commission_orderrefund_deduct')){
        $aid = $order['aid'];

        $open_commission_orderrefund_deduct = Db::name('admin_set')->where('aid',$aid)->value('open_commission_orderrefund_deduct');
        if($open_commission_orderrefund_deduct !=1){
            return;
        }
        writeLog('订单退款扣除分红佣金orderid:'.$order['id'].'type:'.$type,'commissionrefund');
        $og_record_arr = [];
        if($type == 'shop'){
            $og_list = Db::name('shop_order_goods')->where('orderid',$order['id'])->select();
            foreach($og_list as $k=>$v){
                $og_record = Db::name('member_fenhong_record')->where('aid',$aid)->where('ogid',$v['id'])->where('module',$type)->where('status',1)->select();
                foreach($og_record as $record){
                    $og_record_arr[] = $record;
                }

            }
        }elseif($type=='seckill'){

                $og_record = Db::name('member_fenhong_record')->where('aid',$aid)->where('ogid',$order['id'])->where('module',$type)->where('status',1)->select();
                foreach($og_record as $record){
                    $og_record_arr[] = $record;
                }

        }elseif($type == 'scoreshop'){
            $og_list = Db::name('scoreshop_order_goods')->where('orderid',$order['id'])->select();
            foreach($og_list as $k=>$v){
                $og_record = Db::name('member_fenhong_record')->where('aid',$aid)->where('ogid',$v['id'])->where('module',$type)->where('status',1)->select();
                foreach($og_record as $record){
                    $og_record_arr[] = $record;
                }

            }
        }elseif($type == 'collage'){
            $og_record = Db::name('member_fenhong_record')->where('aid',$aid)->where('ogid',$order['id'])->where('module',$type)->where('status',1)->select();
            foreach($og_record as $record){
                $og_record_arr[] = $record;
            }
        }elseif($type == 'kanjia'){
            $og_record = Db::name('member_fenhong_record')->where('aid',$aid)->where('ogid',$order['id'])->where('module',$type)->where('status',1)->select();
            foreach($og_record as $record){
                $og_record_arr[] = $record;
            }
        }elseif($type == 'lucky_collage'){
            $type = 'luckycollage';
            $og_record = Db::name('member_fenhong_record')->where('aid',$aid)->where('ogid',$order['id'])->where('module',$type)->where('status',1)->select();
            foreach($og_record as $record){
                $og_record_arr[] = $record;
            }
        }elseif($type == 'kecheng'){
            $og_record = Db::name('member_fenhong_record')->where('aid',$aid)->where('ogid',$order['id'])->where('module',$type)->where('status',1)->select();
            foreach($og_record as $record){
                $og_record_arr[] = $record;
            }
        }elseif($type=='tuangou'){
            $og_record = Db::name('member_fenhong_record')->where('aid',$aid)->where('ogid',$order['id'])->where('module',$type)->where('status',1)->select();
            foreach($og_record as $record){
                $og_record_arr[] = $record;
            }
        }elseif($type == 'yuyue'){
            $og_record = Db::name('member_fenhong_record')->where('aid',$aid)->where('ogid',$order['id'])->where('module',$type)->where('status',1)->select();
            foreach($og_record as $record){
                $og_record_arr[] = $record;
            }
        }elseif($type=='cashier'){
            $og_list = Db::name('cashier_order_goods')->where('orderid',$order['id'])->select();
            foreach($og_list as $k=>$v){
                $og_record = Db::name('member_fenhong_record')->where('aid',$aid)->where('ogid',$v['id'])->where('module',$type)->where('status',1)->select();
                foreach($og_record as $record){
                    $og_record_arr[] = $record;
                }

            }
        }elseif($type=='restaurant_shop'){
            $og_list = Db::name('restaurant_shop_order_goods')->where('orderid',$order['id'])->select();
            foreach($og_list as $k=>$v){
                $og_record = Db::name('member_fenhong_record')->where('aid',$aid)->where('ogid',$v['id'])->where('module',$type)->where('status',1)->select();
                foreach($og_record as $record){
                    $og_record_arr[] = $record;
                }

            }
        }elseif($type=='restaurant_takeaway'){
            $og_list = Db::name('restaurant_takeaway_order_goods')->where('orderid',$order['id'])->select();
            foreach($og_list as $k=>$v){
                $og_record = Db::name('member_fenhong_record')->where('aid',$aid)->where('ogid',$v['id'])->where('module',$type)->where('status',1)->select();
                foreach($og_record as $record){
                    $og_record_arr[] = $record;
                }

            }
        }elseif($type=='coupon'){
            if(getcustom('commission_times_coupon')){
                $og_record = Db::name('member_fenhong_record')->where('aid',$aid)->where('ogid',$order['id'])->where('module',$type)->where('status',1)->select();
                foreach($og_record as $record){
                    $og_record_arr[] = $record;
                }
            }

        }elseif($type=='gift_pack'){
            if(getcustom('yx_gift_pack')){
                $og_record = Db::name('member_fenhong_record')->where('aid',$aid)->where('ogid',$order['id'])->where('module',$type)->where('status',1)->select();
                foreach($og_record as $record){
                    $og_record_arr[] = $record;
                }
            }
        }
        if($og_record_arr){
            foreach($og_record_arr as $record){
                $ogid = $record['ogid'];
                $fenhonglog = Db::name('member_fenhonglog')->where('aid',$aid)->where('mid',$record['mid'])
                    ->where('module',$type)
                    ->where('type',$record['type'])
                    ->where("find_in_set('{$ogid}',ogids)")->find();
                if($fenhonglog){
                    if($fenhonglog['status']==1){
                        //分红已发放,退款
                        \app\common\Member::addcommission($aid,$record['mid'],0,-1*$record['commission'],'订单退款扣除'.$record['remark'],1,$record['type']);
                    }
                    Db::name('member_fenhonglog')->where('id',$fenhonglog['id'])->update(['status'=>2]);
                    Db::name('member_fenhong_record')->where('id', $record['id'])->update(['status' => 2]);
                }
            }
        }
    }

        return true;
    }
    /**
     * 分期退款处理
     */

    public static function fenqi_refund($order,$refund_money,$reason=''){
        //$refund_money = $data['refund_money'];
        if(getcustom('shop_product_fenqi_pay')){
            $fenqi_data = json_decode($order['fenqi_data'],true);
            $fenqi_order = $order;
            $member = Db::name('member')->where('id',$order['mid'])->find();
            foreach($fenqi_data as $fqkey=>$fq){
                if($fq['status'] == 1){
                    if($refund_money<$fq['fenqi_money']){
                        $fq['fenqi_money'] = $refund_money;
                    }
                    if($fq['fenqi_money'] <=0 ||$refund_money <= 0){
                        break;
                    }
                    $where_counpon[] = ['status','=',0];
                    $where_counpon[] = ['endtime','>',time()];
                    if($order['fenqigive_couponid']){
                        Db::name('coupon_record')->where('aid',$order['aid'])->where('mid',$order['mid'])->where('couponid','=',$order['fenqigive_couponid'])->where($where_counpon)->limit($fq['fenqi_give_num'])->order('id asc')->update(['endtime'=>time()]);
                    }
                    //分销奖励上级
                    if($member['pid'] > 0 && $order['fenqigive_fx_couponid']>0){
                        Db::name('coupon_record')->where('aid',$order['aid'])->where('mid',$member['pid'])->where('couponid','=',$order['fenqigive_fx_couponid'])->where($where_counpon)->limit($fq['fenqi_fx_num'])->order('id asc')->update(['endtime'=>time()]);

                    }
                    $payorder = Db::name('payorder')->where('aid',$order['aid'])->where('ordernum',$fq['ordernum'])->find();
                    $fenqi_order['ordernum']=$fq['ordernum'];
                    $fenqi_order['totalprice']=$payorder['money'];
                    $rs = \app\common\Order::refund($fenqi_order, $fq['fenqi_money'], $reason);
                    if($rs['status']==0){
                        return $rs;
                    }
                    $refund_money = round($refund_money - $fq['fenqi_money'],2);
                }
            }
            return $rs;
        }
    }

    //商品柜 付款后处理
    public static function pickupDeviceGoodsPayafter($order){
        if(getcustom('product_pickup_device')) {
            if ($order['dgid']) {//商品柜更改库存和开门
                $aid = $order['aid'];
                $bid = $order['bid'];
                $device_goods = Db::name('product_pickup_device_goods')->where('aid', $aid)->where('id', $order['dgid'])->find();
                $device =Db::name('product_pickup_device')->where('id',$device_goods['device_id'])->field('name,address,uid,guangeiot')->find();
                if ($device_goods) {
                    $num = Db::name('shop_order_goods')->where('aid', $aid)->where('orderid', $order['id'])->sum('num');
                    //更新库存
                    $real_stock = $device_goods['real_stock'] - $num;
                    //增加销量
                    $real_stock = $real_stock <= 0 ? 0 : $real_stock;
                    $dgsales = $device_goods['sales'] + $num;
                    //更改库存
                    Db::name('product_pickup_device_goods')->where('aid', $order['aid'])->where('id', $order['dgid'])->update(['real_stock' => $real_stock, 'sales' => $dgsales]);

                    //开门 只有自提的开门
                    if($order['freight_type'] ==1){
                        if(getcustom('product_pickup_device_guangeiot')) {
                            if ($device['guangeiot'] == 'xinjierui') {
                                $senddata = [
                                    'device_id' => $device_goods['device_no'],
                                    'operation' => "openBox",
                                    'userId' => 0,
                                    'msgId' => rand(1, 100),
                                    'boxCh' => $device_goods['goods_lane'],
                                    'halfway' => 0,
                                    'certType' => 6,
                                    'dateTime' => time()
                                ];
                                $senddata = json_encode($senddata, JSON_UNESCAPED_UNICODE);
                                $rs = \app\custom\ProductPickupDevice::publishData($aid, $bid, $device_goods['device_no'], $senddata);
                            }
                            self::collect($order,'shop');
                            Db::name('shop_order')->where('aid',$aid)->where('id',$order['id'])->update(['status' => 3]);
                        }
                    }
                    //发送通知
                    $set = Db::name('product_pickup_device_set')->where('aid',$aid)->where('bid',$bid)->find();
                    if($set['add_stock_remind']){
                        $remind_type = explode(',',$set['remind_type']);
                        $remind_pinlv = explode(',',$set['remind_pinlv']);
                        //查找管理员信息
                        //模板通知
                        if(in_array('tmpl',$remind_type)){
                            if(in_array(1,$remind_pinlv) || (in_array(2,$remind_pinlv) && $real_stock <=$set['remind_limit_stock'])){ //每件通知开启  ,库存达x件
                                $tmplcontent = [];
                                $tempconNew = [];
                                $tempconNew['thing11'] = $device['name'];//设备名称
                                $tempconNew['thing12'] = $device['address'];//地点
                                $send_uid = explode(',',$device['uid']);
                                \app\common\Wechat::sendhttmplByUids($order['aid'],$send_uid,'tmpl_device_addstock_remind',$tempconNew,m_url('/pagesB/admin/pickupdeviceaddstock'),0);
                            }
                        }
                        if(in_array('sms',$remind_type)){
                            if(in_array(1,$remind_pinlv) || (in_array(2,$remind_pinlv) && $real_stock <=$set['remind_limit_stock'])){ //每件通知开启   库存达
                                $tel_list = Db::name('admin_user')->alias('au')
                                    ->join('member m','m.id = au.mid')
                                    ->where('au.aid',$aid)->where('au.bid',$bid)
                                    ->where('au.id',$device['uid'])
                                    ->column('tel');
                                foreach($tel_list as $tel) {
                                    \app\common\Sms::send($aid, $tel, 'tmpl_device_addstock_remind', ['address' => $device['address'], 'name' => $device['name']]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public static function prodcutBonusPool($aid,$order,$oglist,$member){
        if(getcustom('product_bonus_pool')){
            $bonus_pool_status = Db::name('admin')->where('id',$aid)->value('bonus_pool_status');
            if($bonus_pool_status ==1){
                $bonus_pool_sysset = Db::name('shop_sysset')->where('aid',$order['aid'])->field('bonus_pool_member_count,bonus_pool_total_mcount')->find();
                $level_member=[];
                $total_member = [];
                //查询每个等级，已释放的会员人数
                $default_cid = Db::name('member_level_category')->where('aid',$order['aid'])->where('isdefault', 1)->value('id');
                $default_cid = $default_cid ? $default_cid : 0;
                $levellist = Db::name('member_level')->where('aid',$order['aid'])->where('cid', $default_cid)->field('id')->order('sort,id')->select()->toArray();
                foreach($levellist as $key=>$level){
                    $mids = Db::name('member_bonus_pool_record')->alias('bpr')
                        ->join('member m','m.id = bpr.mid')
                        ->join('member_level ml','ml.id = m.levelid')
                        ->where('bpr.aid',$order['aid'])
                        ->where('ml.id',$level['id'])
                        ->group('bpr.mid')
                        ->column('mid');
                    $level_member[$level['id']] =  $mids?$mids:[];
                    $total_member = array_merge($total_member,$mids);
                }
                //如果购买人不在 已购买的等级中且 人数已超限制
                $is_add_max_money = true;
                if($bonus_pool_sysset['bonus_pool_total_mcount'] > 0){
                    $buy_member_limitnum =$bonus_pool_sysset['bonus_pool_total_mcount']; //设置中等级对应的会员数量
                    $buy_member_limitnum = $buy_member_limitnum?$buy_member_limitnum:0;
                    if(count($total_member)+1 >= $buy_member_limitnum  && $buy_member_limitnum >0){
                        $is_add_max_money = false;
                    }
                }

                //平台首单不释放
                $order_number = Db::name('shop_order')->where('status','in',[1,2,3])->where('aid',$order['aid'])-> count();
                if($order_number > 1){

                    $bonus_pool_member_count = json_decode($bonus_pool_sysset['bonus_pool_member_count'],true);
                    foreach ($oglist as $ok=>$og){
                        $release_list =  Db::name('shop_order_goods')->alias('sog')
                            ->join('shop_product sp','sp.id = sog.proid')
                            ->join('member m','m.id = sog.mid')
                            ->where('sog.status','in',[1,2,3])
                            ->where('sog.aid',$order['aid'])
                            ->field('sog.id,sog.aid,sog.proid,sog.mid,sog.num,m.bonus_pool_money,m.levelid,m.pid,sp.bonus_pool_isrelease,sp.bonus_pool_releasetj,sp.bonus_pool_money_max,m.bonus_pool_max_money,m.bonus_pool_money')->select()->toArray();
                        //释放奖金池
                        if($release_list){
                            foreach ($release_list as $mk=>$mv){
                                //自己的订单 不拿奖励
                                if($order['mid'] == $mv['mid']){
                                    continue;
                                }

                                //其他用户购买的产品 是释放的 就给释放  不是释放的就不释放 释放数量是 购买的数量
                                if(!$mv['bonus_pool_isrelease']){
                                    continue;
                                }
                                $pool_releasetj = explode(',',$mv['bonus_pool_releasetj']);
                                //判断是否存在不发放的问题

                                if(!in_array($mv['levelid'],$pool_releasetj) && !in_array(-1,$pool_releasetj)){
                                    continue;
                                }
                                //如果设置总数量，等级的不再生效
                                if($bonus_pool_sysset['bonus_pool_total_mcount'] <=0){
                                    //判断会员的数量
                                    $this_level_mids = $level_member[$mv['levelid']];
                                    $bonus_pool_member_limitnum = $bonus_pool_member_count[$mv['levelid']];
                                    $bonus_pool_member_limitnum = $bonus_pool_member_limitnum?$bonus_pool_member_limitnum:0;
                                    //获得奖金的会员 不在已释放的列表中且发放的会员数量大4于设置的数量
                                    if(!in_array($mv['mid'],$this_level_mids) && count($this_level_mids) >= $bonus_pool_member_limitnum && $bonus_pool_member_limitnum >0){
                                        continue;
                                    }
                                }else{
                                    $total_member_count = count($total_member);
                                    if(!in_array($mv['mid'],$total_member) && count($total_member_count) >= $bonus_pool_sysset['bonus_pool_total_mcount']){
                                        
                                        continue;
                                    }
                                }
                                for($i=1;$i<=$mv['num'];$i++){
                                    //新增 等级对应的奖金池分类
                                    $pc_where = [];
                                    $pc_where[] = ['aid','=',$order['aid']];
                                    $pc_where[] = ['bid','=',$order['bid']];
                                    $pc_where[] = ['status','=',1];
                                    $pc_where[] = Db::raw('find_in_set(-1,gettj) or find_in_set('.$mv['levelid'].',gettj)');
                                    $p_category = Db::name('bonus_pool_category')->where($pc_where)->column('id');
                                    
                                    $pool = Db::name('bonus_pool')->where('aid',$mv['aid'])->where('status',0) ->where('cid','in',$p_category)->order('id asc')->find();
                                   
                                    if(!$pool){
                                        continue ;
                                    }
                                    $wait_money = Db::name('member_bonus_pool_record')->where('aid',$mv['aid'])->where('status',0)->where('mid',$mv['mid'])->sum('commission');

                                    //判断商品 发放上限
                                    $total_product_pool_money =0+Db::name('member_bonus_pool_record')->where('aid',$og['aid'])->where('mid',$mv['mid'])->sum('commission');

                                    if($total_product_pool_money +$pool['money'] +$wait_money >  $mv['bonus_pool_max_money'] ){
                                        \think\facade\Log::write($mv['bonus_pool_money_max'].'超过商品设置上限'.$og['id'].'---total'.$total_product_pool_money.'---'.$pool['money'].'---'.$wait_money.'---mid:'.$mv['mid']);
                                        continue;
                                    }
                                    if($mv['bonus_pool_money'] +$pool['money'] +$wait_money >  $mv['bonus_pool_max_money'] ){
                                        \think\facade\Log::write($mv['bonus_pool_money_max'].'超过商品设置上限2'.$og['id'].'---total'.$total_product_pool_money.'---'.$pool['money'].'---'.$wait_money.'---mid:'.$mv['mid']);
                                        continue;
                                    }
                                    //插入记录
                                    $record = [
                                        'aid' => $og['aid'],
                                        'mid' => $mv['mid'],
                                        'orderid' => $og['orderid'],
                                        'frommid' => $og['mid'],
                                        'ogid' => $og['id'],
                                        'proid' =>$og['proid'],
                                        'bpid' => $pool['id'],
                                        'type' => 'shop',
                                        'commission' => $pool['money'],
                                        'remark' => '新进订单释放',
                                        'createtime' => time(),
                                        'status' => 0
                                    ];
                                    Db::name('member_bonus_pool_record')->insert($record);
                                    //修改奖金池状态
                                    Db::name('bonus_pool')->where('aid',$mv['aid'])->where('id',$pool['id'])->update(['status' => 1,'mid' => $mv['mid'],'endtime' => time()]);
                                }
                            }
                            //推荐人发放
                            foreach($release_list as $mk=>$mv){
                                if($mv['pid'] ==0){
                                    continue;
                                }
                                $parent = Db::name('member')->where('id',$mv['pid'])->find();
                                if($parent['bonus_pool_max_money'] <=0){
                                    continue;
                                }
                                $tpooldata = Db::name('bonus_pool')->where('aid',$mv['aid'])->where('status',0)->order('id asc')->find();
                                if(!$tpooldata){
                                    continue;
                                }
                                if($bonus_pool_sysset['bonus_pool_total_mcount'] <=0) {
                                    //判断会员的数量
                                    $this_level_mids = $level_member[$parent['levelid']];
                                    $bonus_pool_member_limitnum = $bonus_pool_member_count[$parent['levelid']];
                                    $bonus_pool_member_limitnum = $bonus_pool_member_limitnum ? $bonus_pool_member_limitnum : 0;
                                    //获得奖金的会员 不在已释放的列表中且发放的会员数量大于设置的数量
                                    if (!in_array($parent['id'], $this_level_mids) && count($this_level_mids) >= $bonus_pool_member_limitnum && $bonus_pool_member_limitnum > 0) {
                                        continue;
                                    }
                                }else{
                                    $total_member_count = count($total_member);
                                    if(!in_array($parent['id'],$total_member) && count($total_member_count) >= $bonus_pool_sysset['bonus_pool_total_mcount']){
   
                                        continue;
                                    }
                                }
                                //判断是否存在不发放的问题
                                for($i=1;$i<=$mv['num'];$i++){
                                    //用户达到上限，不释放
                                    //未发放的
                                    $tpc_where = [];
                                    $tpc_where[] = ['aid','=',$order['aid']];
                                    $tpc_where[] = ['bid','=',$order['bid']];
                                    $tpc_where[] = ['status','=',1];
                                    $tpc_where[] = Db::raw('(find_in_set(-1,gettj) or find_in_set('.$parent['levelid'].',gettj))');
                                    $tp_category = Db::name('bonus_pool_category')->where($tpc_where)->column('id');
                                    $tpool = Db::name('bonus_pool')->where('aid',$mv['aid'])->where('status',0)->where('cid','in',$tp_category)->order('id asc')->find();
      
                                    if(!$tpool){
                                        continue ;
                                    }
                                    $wait_money = Db::name('member_bonus_pool_record')->where('aid',$mv['aid'])->where('status',0)->where('mid',$parent['id'])->sum('commission');
                                    // if($parent['bonus_pool_money']+$pool['money'] +$wait_money > $poolshopset['bonus_pool_money_max']){
                                    //     continue;
                                    // }
                                    //判断商品 发放上限
                                    $total_product_pool_money =0+Db::name('member_bonus_pool_record')->where('aid',$og['aid'])->where('mid',$parent['id'])->sum('commission');
                                    if($total_product_pool_money +$tpool['money'] +$wait_money >  $parent['bonus_pool_max_money']){
                                        \think\facade\Log::write($mv['bonus_pool_money_max'].'推荐人超过商品设置上限'.$og['id'].'---total'.$total_product_pool_money.'---'.$tpool['money'].'---'.$wait_money);
                                        continue;
                                    }
                                    if($parent['bonus_pool_money'] +$tpool['money'] +$wait_money >  $parent['bonus_pool_max_money'] ){
                                        \think\facade\Log::write($mv['bonus_pool_money_max'].'推荐人超过商品设置上限2'.$og['id'].'---total'.$total_product_pool_money.'---'.$tpool['money'].'---'.$wait_money);
                                        continue;
                                    }
                                    //插入记录
                                    $record = [
                                        'aid' => $og['aid'],
                                        'mid' => $parent['id'],
                                        'orderid' => $og['orderid'],
                                        'frommid' => $mv['mid'],
                                        'ogid' => $og['id'],
                                        'proid' =>$og['proid'],
                                        'bpid' => $tpool['id'],
                                        'type' => 'shop',
                                        'commission' => $tpool['money'],
                                        'remark' => '推荐人-新进订单释放',
                                        'createtime' => time(),
                                        'status' => 0
                                    ];
                                    Db::name('member_bonus_pool_record')->insert($record);
                                    //修改奖金池状态
                                    Db::name('bonus_pool')->where('aid',$mv['aid'])->where('id',$tpool['id'])->update(['status' => 1,'mid' => $parent['id'],'endtime' => time()]);
                                }

                            }
                        }
                    }
                }
                foreach($levellist as $key=>$level){
                    $mids = Db::name('member_bonus_pool_record')->alias('bpr')
                        ->join('member m','m.id = bpr.mid')
                        ->join('member_level ml','ml.id = m.levelid')
                        ->where('bpr.aid',$order['aid'])
                        ->where('ml.id',$level['id'])
                        ->group('bpr.mid')
                        ->column('mid');

                    $level_member[$level['id']] =  $mids?$mids:[];
                    $total_member = array_merge($total_member,$mids);
                }
                //不设置总数，设置每个等级时
                if($bonus_pool_sysset['bonus_pool_total_mcount'] <= 0){
                    $bonus_pool_member_count = json_decode($bonus_pool_sysset['bonus_pool_member_count'],true);

                    $buy_level_mids = $level_member[$member['levelid']];

                    $buy_bonus_pool_member_limitnum = $bonus_pool_member_count[$member['levelid']];

                    $mids_count = count($buy_level_mids);

//                    $order_number2 = Db::name('shop_order')->where('status','in',[1,2,3])->where('aid',$order['aid'])-> count();
                    //查找第一单的会员的等级，根据这个等级知道是不是首单的等级
                    $first_order_levelid = Db::name('shop_order')->alias('so')
                        ->join('member m','m.id = so.mid')
                        ->where('so.status','in',[1,2,3])
                        ->where('so.aid',$aid)
                        ->order('so.paytime asc')
                        ->limit(1)
                        ->value('m.levelid');
                    if($first_order_levelid == $member['levelid']){
                        $mids_count = $mids_count+2;
                    }else{
                        $mids_count = $mids_count+1;
                    }
//                    if($order_number2 ==2 && ){
//                        $mids_count = $mids_count+1;
//                    }
                  
                    if(!in_array($order['mid'],$buy_level_mids) && $mids_count > $buy_bonus_pool_member_limitnum && $buy_bonus_pool_member_limitnum >0){// 0 > 1? true    1>1?
                        $is_add_max_money = false;
                    }
                }

                if($is_add_max_money){
                    foreach ($oglist as $ok=>$og){
                        //更新用户的最大值
                        $member =Db::name('member')->where('aid',$og['aid'])->where('id',$og['mid'])->find();
                        $product_max_money = Db::name('shop_product')->where('aid',$og['aid'])->where('id',$og['proid'])->value('bonus_pool_money_max');
                        if($product_max_money>0){
                            $m_max_money = $product_max_money * $og['num'] + $member['bonus_pool_max_money'];
                            Db::name('member')->where('aid',$og['aid'])->where('id',$og['mid'])->update(['bonus_pool_max_money' => $m_max_money]);
                        }
                    }
                }
            }
        }
    }
    public static function prodcutBonusPoolCollect($aid,$oglist,$member){
        if(getcustom('product_bonus_pool')){
            $bonus_pool_status = Db::name('admin')->where('id',$aid)->value('bonus_pool_status');
            if($oglist && $member && $bonus_pool_status){
                foreach($oglist as $og){
                    $field='id,cid';
                    $field .=',bonus_pool_ratio,bonus_pool_num,bonus_pool_releasetj,bonus_pool_cid';//新增bonus_pool_cid分类
                    $product = Db::name('shop_product')
                        ->where('id',$og['proid'])
                        ->field($field)
                        ->find();
                    if($product && $product['bonus_pool_ratio']){
                        //加入奖金池
                        for($i=0;$i < $product['bonus_pool_num'];$i++){
                            $pool_money = $product['bonus_pool_ratio']/100 *$og['totalprice'];
                            $money = dd_money_format($pool_money / $product['bonus_pool_num']);
                            if($money > 0){
                                $pool_data = [
                                    'aid' => $og['aid'],
                                    'bid' => $og['bid'],
                                    'cid' =>$product['bonus_pool_cid'],//新增bonus_pool_cid分类
                                    'money' => $money,
                                    'ogid' => $og['id'],
                                    'createtime' => time()
                                ];
                                Db::name('bonus_pool')->insert($pool_data);
                            }

                        }
                    }

                }
            }
            if($oglist){
                //奖金池 记录 发放
                foreach ($oglist as $og){
                    $recordlist = Db::name('member_bonus_pool_record')->where('aid',$og['aid'])->where('ogid',$og['id'])->select()->toArray();

                    foreach ($recordlist as $rk=>$rv){
                        $member = Db::name('member')->where('id',$rv['mid'])->find();
                        $bonus_pool_money = dd_money_format($member['bonus_pool_money'] + $rv['commission']);
                        //增加log
                        $log = [
                            'aid' =>$rv['aid'],
                            'mid' =>$rv['mid'],
                            'frommid' => $og['mid'],
                            'commission' => $rv['commission'],
                            'after' => $bonus_pool_money,
                            'createtime' => time(),
                            'remark' => $rv['remark']
                        ];

                        Db::name('member_bonus_pool_log') ->insert($log);

                        Db::name('member')->where('id',$rv['mid'])->update(['bonus_pool_money' => $bonus_pool_money]);
                        Db::name('member_bonus_pool_record')->where('id',$rv['id'])->update(['status' => 1,'endtime' => time()]);
                    }
                }

            }
        }
    }
    //赠送激活币
    public static function giveActiveCoin($aid,$order,$type='shop',$is_collect=0){
        if(getcustom('active_coin',$aid)) {
            if($order['paytype']==0 && strpos($order['remark'],'自动下单')!==false){
                //自动下单的不赠送激活币
                return true;
            }
            //送激活币
            $coin_set = Db::name('active_coin_set')->where('aid', $aid)->find();
            //余额抵扣是否参与激活币计算
            $active_coin_money_dec = 1;
            if(getcustom('active_coin_money_dec',$aid)){
                $active_coin_money_dec = $coin_set['active_coin_money_dec'];
            }
            $can_handle = 1;
            if($type=='shop'){
                if ($order['status'] == 1 && $coin_set['reward_time'] == 0  && $is_collect!= 1) {
                    //设置的收货后赠送
                    $can_handle = 0;
                }
                if ($order['status'] == 3 && $coin_set['reward_time'] == 1) {
                    //设置的支付后赠送
                    $can_handle = 0;
                }
            }
            if ($order['is_coin'] == 1) {
                //已经赠送完了
                $can_handle = 0;
            }
            if (!$can_handle) {
                return true;
            }
            $activecoin_ratio = $coin_set['activecoin_ratio'];
            $member_activecoin_ratio = 100;
            $business_activecoin_ratio = 0;
            $business_tj_activecoin_ratio = 0;
            //查找会员id和商家id
            $mid = $order['mid'];
            $business_mid = 0;
            $business_pid = 0;
            if ($order['bid'] > 0) {
                $binfo = Db::name('business')->where('aid', $aid)->where('id', $order['bid'])->find();
                $business_mid = $binfo['mid'];
                $activecoin_ratio = $binfo['activecoin_ratio'];
                $member_activecoin_ratio = $binfo['member_activecoin_ratio'];
                $business_activecoin_ratio = $binfo['business_activecoin_ratio'];
                if(getcustom('active_coin_business_tj',$aid)){
                    $business_tj_activecoin_ratio = $binfo['business_tj_activecoin_ratio'];
                    if($business_mid>0){
                        $business_pid = Db::name('member')->where('id',$business_mid)->value('pid');
                    }
                }
            }
            if($type=='shop'){
                $oglist = Db::name('shop_order_goods')->where('aid', $aid)->where('orderid', $order['id'])->select()->toArray();
                foreach ($oglist as $k => $og) {
                    $order_money = $og['real_totalprice'];
                    if(!$active_coin_money_dec){
                        $order_money = $order_money-$og['dec_money'];
                    }
                    if ($coin_set['reward_type'] == 0 && $og['bid']==0) {
                        //按订单利润 20250606新增商家商品不按订单利润
                        $order_money = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                    }
                    if($og['bid']==0){
                        $product = Db::name('shop_product')->where('id', $og['proid'])->find();
                        $product['sell_price'] = $order_money;
                        $product['cost_price'] = 0;
                        $member_activecoin = self::getProductActiveCoin($aid,$product,$og['num'],0);
                        $activecoin_total = $member_activecoin;
                        $business_activecoin = 0;
                        $business_tj_activecoin = 0;
                    }else{
                        $activecoin_total = bcmul($order_money, $activecoin_ratio / 100, 2);
                        $member_activecoin = bcmul($activecoin_total, $member_activecoin_ratio / 100, 2);
                        $business_activecoin = bcmul($activecoin_total, $business_activecoin_ratio / 100, 2);
                        $business_tj_activecoin = bcmul($activecoin_total, $business_tj_activecoin_ratio / 100, 2);
                    }

                    if ($member_activecoin > 0 && $mid > 0) {
                        \app\common\Member::addactivecoin($aid, $mid, $member_activecoin, '购买商品' . $og['name'] . '赠送', '', $order['id']);
                    }
                    if ($business_activecoin > 0 && $business_mid > 0) {
                        \app\common\Member::addactivecoin($aid, $business_mid, $business_activecoin, '购买商品' . $og['name'] . '赠送', '', $order['id']);
                    }
                    if($business_tj_activecoin>0 && $business_pid>0){
                        \app\common\Member::addactivecoin($aid, $business_pid, $business_tj_activecoin, '购买商品' . $og['name'] . '赠送', '', $order['id']);
                    }
                    Db::name('shop_order_goods')->where('id', $og['id'])->update(['activecoin' => $activecoin_total]);
                }
                Db::name('shop_order')->where('id', $order['id'])->update(['is_coin' => 1]);
            }else if($type=='maidan'){
                $order_money = $order['money'];
                if(!$active_coin_money_dec){
                    $order_money = $order_money-$order['dec_money'];
                }
                $activecoin_total = bcmul($order_money, $activecoin_ratio / 100, 2);
                $member_activecoin = bcmul($activecoin_total, $member_activecoin_ratio / 100, 2);
                if ($member_activecoin > 0 && $mid > 0) {
                    \app\common\Member::addactivecoin($aid, $mid, $member_activecoin, '买单赠送', '', $order['id']);
                }
                $business_activecoin = bcmul($activecoin_total, $business_activecoin_ratio / 100, 2);
                if ($business_activecoin > 0 && $business_mid > 0) {
                    \app\common\Member::addactivecoin($aid, $business_mid, $business_activecoin, '买单赠送', '', $order['id']);
                }
                $business_tj_activecoin = bcmul($activecoin_total, $business_tj_activecoin_ratio / 100, 2);
                if($business_tj_activecoin>0 && $business_pid>0){
                    \app\common\Member::addactivecoin($aid, $business_pid, $business_tj_activecoin, '买单赠送', '', $order['id']);
                }
                Db::name('maidan_order')->where('id', $order['id'])->update(['is_coin' => 1,'activecoin' => $activecoin_total]);
            }

            return true;
        }

    }
    //获取产品预计赠送激活币数量
    public static function getProductActiveCoin($aid,$product,$num=1,$ggid=0){
        if(getcustom('active_coin')) {
            $active_coin_product = getcustom('active_coin_product');//20250327新增商品独立设置激活币
            if($ggid>0){
                $shop_guige = Db::name('shop_guige')->where('proid',$product['id'])->where('id', $ggid)->find();
                $product['sell_price'] = $shop_guige['sell_price'];
                $product['cost_price'] = $shop_guige['cost_price'];
                if($active_coin_product){
                    $product['give_active_coin'] = $shop_guige['give_active_coin'];
                }
            }
            //送激活币
            $coin_set = Db::name('active_coin_set')->where('aid', $aid)->find();
            $activecoin_ratio = $coin_set['activecoin_ratio'];
            $member_activecoin_ratio = 100;
            $business_activecoin_ratio = 0;
            if($product['bid']==0 && $active_coin_product){
                //平台商品有指定活动商品限制
                if($coin_set['fwtype']==2){//指定商品可用
                    $productids = explode(',',$coin_set['productids']);
                    if(!in_array($product['id'],$productids)){
                        return 0;
                    }
                }
                if($coin_set['fwtype']==1){//指定类目可用
                    $categoryids = explode(',',$coin_set['categoryids']);
                    $cids = explode(',',$product['cid']);
                    $clist = Db::name('shop_category')->where('pid','in',$categoryids)->select()->toArray();
                    foreach($clist as $vc){
                        $categoryids[] = $vc['id'];
                        $cate2 = Db::name('shop_category')->where('pid',$vc['id'])->find();
                        $categoryids[] = $cate2['id'];
                    }
                    if(!array_intersect($cids,$categoryids)){
                        return 0;
                    }
                }
                if($product['give_active_coin']>0){
                    //平台商品可以独立设置赠送数量
                    return bcmul($product['give_active_coin'],$num,2);
                }
            }

            if ($product['bid'] <= 0) {
                if (getcustom('active_coin_category', $aid)) {
                    //平台商品分类独立设置让利比例
                    if($product['cid']){
                        $cids = explode(',',$product['cid']);
                        $categoty1 = Db::name('shop_category')->where('id','in',$product['cid'])->order('pid asc')->find();
                        if(count($cids)==3){
                            $categoty2 = Db::name('shop_category')->where('id','in',$product['cid'])->where('pid',$categoty1['id'])->find();
                            $categoty3 = Db::name('shop_category')->where('id','in',$product['cid'])->where('pid',$categoty2['id'])->find();
                        }else{
                            $categoty2 = Db::name('shop_category')->where('id','in',$product['cid'])->where('pid',$categoty1['id'])->find();
                        }
                        if($categoty3['activecoin_ratio']>0){
                            $activecoin_ratio = $categoty3['activecoin_ratio'];
                        }else if($categoty2['activecoin_ratio']>0){
                            $activecoin_ratio = $categoty2['activecoin_ratio'];
                        }else if($categoty1['activecoin_ratio']>0){
                            $activecoin_ratio = $categoty1['activecoin_ratio'];
                        }
                    }
                }
            }



            //查找会员id和商家id
            $business_mid = 0;
            if ($product['bid'] > 0) {
                $binfo = Db::name('business')->where('aid', $aid)->where('id', $product['bid'])->find();
                $business_mid = $binfo['mid'];
                $activecoin_ratio = $binfo['activecoin_ratio'];
                $member_activecoin_ratio = $binfo['member_activecoin_ratio'];
                $business_activecoin_ratio = $binfo['business_activecoin_ratio'];
            }
            $order_money = $product['sell_price'];
            if ($coin_set['reward_type'] == 0) {
                //按订单利润
                $order_money = $product['sell_price'] - $product['cost_price'] ;
            }
            $activecoin_total = bcmul($order_money, $activecoin_ratio / 100, 2);
            $member_activecoin = bcmul($activecoin_total, $member_activecoin_ratio / 100, 2);
            $member_activecoin = bcmul($member_activecoin, $num, 2);
            return $member_activecoin?:0;
        }
    }

    //配送
    public static function peisong($aid,$bid,$orderid,$type,$psid,$order,$params=[]){
        if(!$order) return ['status'=>0,'msg'=>'订单不存在'];
        if($order['status']!=1 && $order['status']!=12) return ['status'=>0,'msg'=>'订单状态不符合'];

        $set = Db::name('peisong_set')->where('aid',$aid)->find();
        if(getcustom('express_maiyatian')) {
            if($set['myt_status'] == 1){
                $psid = -2;//  -1、码科  -2、麦芽田配送
            }
        }

        $other = [];
        if(getcustom('express_maiyatian')){
            $other['myt_shop_id'] = $params['myt_shop_id']?$params['myt_shop_id']:0;
            $other['myt_weight']  = $params['myt_weight']?$params['myt_weight']:1;
            if(!is_numeric($other['myt_weight'])){
                return ['status'=>0,'msg'=>'重量必须为纯数字'];
            }
            $other['myt_remark']  = $params['myt_remark']?$params['myt_remark']:'';
        }

        $rs = \app\model\PeisongOrder::create($type,$order,$psid,$other);
        if($rs['status']==0) return $rs;
        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping($aid,$order,$type);
        }
        \app\common\System::plog('订单配送'.$orderid);
        return ['status'=>1,'msg'=>'操作成功'];
    }

    //团队业绩统计 $config:阶梯配置  $yuji:待结算
    public static function teamyejiJiangli($member,$yeji_set,$sysset,$config){
        $is_include_self = getcustom('yx_team_yeji_include_self');
        $pingji_yueji_custom = getcustom('yx_team_yeji_pingji_jinsuo');
        $is_jicha_custom = getcustom('yx_team_yeji_jicha');
        $product_is_join_custom = getcustom('teamyeji_product_is_join');
        if(getcustom('yx_team_yeji')){
            $mid = $member['id'];
            $now_month = date('Y-m',strtotime('-1 month'));
            $fenhong = 0;
            $xuni_yeji = 0;  //虚拟业绩
            $yejiwhere = [];
            $levelup_time = 0;
            $not_join_where = [];//不参与的商品
            if($is_jicha_custom){
                $show_levelid = array_keys($config);
                if(in_array($member['levelid'],$show_levelid)){
                    $levelup_order = Db::name('member_levelup_order')
                        ->where('aid',$sysset['aid'])
                        ->where('mid',$mid)
                        ->where('levelid',$member['levelid'])
                        ->where('status',2)
                        ->order('levelup_time desc')
                        ->find();
                    $levelup_time = $levelup_order['levelup_time'];
                }
            }
            if($yeji_set['jiesuan_type'] == 1){//按月
                $month_start = strtotime(date('Y-m-01 00:00:00'));
                $enddate = $month_end  = strtotime(date('Y-m-t 23:59:59'));
                if($is_jicha_custom){
                    if($levelup_time && $levelup_time > $month_start )$month_start =   $levelup_time;
                }
                $yejiwhere[] = ['createtime','between',[$month_start,$month_end]];
                $not_join_where[] = ['og.createtime','between',[$month_start,$month_end]];
                //虚拟业绩 yeji_type业绩类型 0 统计价格（默认） 1:统计数量 不统计虚拟业绩
                if(!$yeji_set['yeji_type']) $xuni_yeji = 0 +Db::name('tem_yeji_xuni')->where('aid',$sysset['aid'])->where('mid',$mid)->where('yeji_month',$now_month)->value('yeji');
            }elseif($yeji_set['jiesuan_type'] == 2){//按年
                $year_start=strtotime(date('Y') . '-01-01 00:00:00');
                $enddate = $year_end=strtotime(date('Y') . '-12-31 23:59:59');
                if($is_jicha_custom){
                    if($levelup_time && $levelup_time > $year_start )$year_start =   $levelup_time;
                }
                $yejiwhere[] = ['createtime','between',[$year_start,$year_end]];
                $not_join_where[] = ['og.createtime','between',[$year_start,$year_end]];
            }elseif($yeji_set['jiesuan_type'] == 3){//按季度
                $season_start=strtotime(date('Y-m-01 00:00:00'));
                $enddate = $season_end=strtotime(date('Y-m-t 23:59:59',strtotime('+3 month')));
                if($is_jicha_custom){
                    if($levelup_time && $levelup_time > $season_start )$season_start =   $levelup_time;
                }
                $yejiwhere[] = ['createtime','between',[$season_start,$season_end]];
                $not_join_where[] = ['og.createtime','between',[$season_start,$season_end]];
            }elseif($yeji_set['jiesuan_type'] == 4){//按天
                $season_start=strtotime(date('Y-m-d 00:00:00'));
                $enddate = $season_end=time();
                if($is_jicha_custom){
                    if($levelup_time && $levelup_time > $season_start )$season_start =   $levelup_time;
                }
                $yejiwhere[] = ['createtime','between',[$season_start,$season_end]];
                $not_join_where[] = ['og.createtime','between',[$season_start,$season_end]];
            }
            $yejiwhere[] = ['status','in','1,2,3'];
            $not_join_where[] = ['og.status','in','1,2,3'];
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

            $down_where = [];
            $down_where[] = ['aid','=',$member['aid']];
            $down_where[] = ['id','<>',$member['id']];
            if($levelids){
                $down_where[] = ['levelid','in',$levelids];
            }
            $down_where[] = Db::raw('find_in_set('.$member['id'].',path)');
            $downmids = Db::name('member')->where($down_where)->column('id');
            if($is_include_self){
                if($yeji_set['include_self']) $downmids[] = $member['id'];
            }

            //参与的商品
            $join_proid = [];
            $shopwhere = '1=1';
            if($product_is_join_custom){
                //查询参与的商品id
                $join_proid = Db::name('shop_order_goods')->alias('og')
                    ->join('shop_product p','p.id = og.proid')
                    ->where('og.aid',$sysset['aid'])
                    ->where('og.mid','in',$downmids)
                    ->where('p.teamyeji_join_st',1)
                    ->where($not_join_where)
                    ->column('og.proid');
            }
            if(getcustom('yx_team_yeji_type')){
                //是否有商品限制
                if($yeji_set['fwtype']==1){
                    if(empty($yeji_set['productids'])){
                        if(!$join_proid) $shopwhere = 'id = 0';
                    }else{
                        $productids = explode(',',$yeji_set['productids']);
                        $join_proid = array_merge($join_proid,$productids);
                    }
                }
            }
            if($join_proid){
                $shopwhere = 'proid in ('.implode(',',$join_proid).')';
            }
            if(getcustom('product_yeji_level')){
                //有效业绩
                $yejiwhere[] = ['yeji','>',0];
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
            $teamyeji = $leiji_total_yeji = 0;
            //可统计商城订单
            if($tongji_shop){
                $teamyeji = Db::name('shop_order_goods')->where('aid',$sysset['aid'])->where('mid','in',$downmids)->where($yejiwhere)->where($shopwhere)->sum($shopsumField);
                if(getcustom('yx_team_yeji_leiji')) {
                    if ($yeji_set['yeji_leiji']) {
                        $leiji_total_yeji += Db::name('shop_order_goods')->where('aid',$sysset['aid'])->where('mid','in',$downmids)->where('status','in',['1','2','3'])->where('createtime','<',$enddate)->where($shopwhere)->sum($shopsumField);
                    }
                }
            }
            //可统计买单订单
            if($tongji_maidan){
                $teamyeji += Db::name('maidan_order')->where('aid',$sysset['aid'])->where('mid','in',$downmids)->where($yejiwhere)->sum('paymoney');//paymoney
                if(getcustom('yx_team_yeji_leiji')) {
                    if ($yeji_set['yeji_leiji']) {
                        $leiji_total_yeji += Db::name('maidan_order')->where('aid',$sysset['aid'])->where('mid','in',$downmids)->where('createtime','<',$enddate)->sum('paymoney');//paymoney
                    }
                }
            }

            //业绩类型 0 按价格（默认）增加虚拟业绩 1:按数量 不增加虚拟业绩
            if(!$yeji_set['yeji_type']){
                $totalyeji = $teamyeji + $xuni_yeji;
            }else{
                $totalyeji = $teamyeji;
            }
            $ratio_totalyeji = $totalyeji;
            if(getcustom('yx_team_yeji_leiji')) {
                if ($yeji_set['yeji_leiji']) {
                    $ratio_totalyeji = $leiji_total_yeji ;
                }
            }

            //阶梯设置
            $jt_range = $config[$member['levelid']]['range'];
            $countfenhong = true;//是否计算分红
            $ratio = $price = $fenhong = 0;//百分比、固定金额
            foreach($jt_range as $rk=> $range){
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
            if($is_jicha_custom && $yeji_set['is_jicha']){
                $fenhong = self::getDownTeamyejiJiangli($member,$yeji_set,$sysset,$config);
            } else{
                if($countfenhong){
                    if($ratio > 0) $fenhong = $ratio / 100 * $totalyeji;
                    if($price>0) $fenhong += $price * $totalyeji;
                }
            }

            //平级
            if($pingji_yueji_custom){
                $pingji_yueji_data = json_decode($yeji_set['yueji_pingji_data'],true);
                $path_where = [];
                if($yeji_set['yueji_pingji_status']){
                    $member_level_sort = Db::name('member_level')->where('aid',$sysset['aid']) ->where('id',$member['levelid'])->value('sort');
                    $path_where[] = ['ml.sort','<=',$member_level_sort];
                }
                $pathlist = Db::name('member')->alias('m')
                    ->join('member_level ml','ml.id = m.levelid')
                    ->where('m.aid',$sysset['aid'])
                    ->where('find_in_set('.$member['id'].',m.path)')
                    ->where($path_where)
                    ->field('m.*,ml.sort')
                    ->select()->toArray();
                $this_fenhong = 0;
                foreach($pathlist as $pk=>$pval){
                    
                    //下级人数
                    $new_yejiwhere = [];
                    foreach($yejiwhere as $yw=> $yejiwhereval){
                        if(is_array($yejiwhereval)){
                            $yejiwhereval[0] = 'og.'.$yejiwhereval[0];
                            $new_yejiwhere[$yw] = $yejiwhereval;
                        }
                    }
                    $thistotalyeji = Db::name('shop_order_goods')->alias('og')
                        ->join('member m','m.id = og.mid')
                        ->where('og.aid',$member['aid'])
                        ->where('m.id','<>',$pval['id']);
                    if($join_proid){
                        $thistotalyeji = $thistotalyeji ->where('og.proid','in ',$join_proid);
                    }
                    $thistotalyeji = $thistotalyeji ->whereRaw(Db::raw('find_in_set('.$pval['id'].',m.path)'))
                        ->where($new_yejiwhere)->sum($shopsumField);
                    if($is_include_self && $yeji_set['include_self']){
                        $myself_yeji =0 + Db::name('shop_order_goods')->where('aid',$sysset['aid'])->where('mid','=', $pval['id'])->where($yejiwhere)->where($shopwhere)->sum($shopsumField);
                        $thistotalyeji = $thistotalyeji +  $myself_yeji;
                    }

                    //旧查找path
                    $parentList = [];
                    if($pval['path']){
                        $pval_path_arr = array_reverse(explode(',',$pval['path']));
                        $pval_str = implode(',',$pval_path_arr);
                        $parentList = Db::name('member')->where('id','in',$pval['path'])->where('levelid','>',0)->order(Db::raw('field(id,'.$pval_str.')'))->select()->toArray();
                    }
                    //旧方式
                    if($parentList){
                        //当前设置
                        $this_pingjidata = $pingji_yueji_data[$pval['levelid']];
                        $parent_arr = [];
                        $is_jinsuo =  $this_pingjidata['jinsuo'];
                        $dai = 1;
                        foreach($parentList as $k=>$parent){
                            //开启紧缩后，往上查找平级
                            if($is_jinsuo){
                                //如果 平级，且不到2级
                                if($parent['levelid'] != $pval['levelid'] || count($parent_arr) >= 2){
                                    continue;
                                }
                                if($parent['id'] == $member['id']){
                                    $parent_arr[$dai] =$parent;
                                }
                                $dai += 1;
                            }else{
                                if($dai <= 2 &&  $parent['levelid'] == $pval['levelid']){
                                    if($parent['id'] == $member['id']) {
                                        $parent_arr[$dai] = $parent;
                                    }
                                }
                                $dai +=1;
                            }
                        }
                        foreach($parent_arr as $dai=>$pv){
                            //发放奖励的会员 和 当前会员不是一个
                            if($pv['id'] != $member['id']){
                                continue;
                            }
                            $commission1_ratio = $this_pingjidata['commission1'];
                            $commission2_ratio = $this_pingjidata['commission2'];
                            if($dai ==1){
                                $this_fenhong  += dd_money_format($thistotalyeji * $commission1_ratio/100);
                            }
                            if($dai ==2){
                                $this_fenhong  += dd_money_format($thistotalyeji * $commission2_ratio/100);
                            }
                        }
                    }
                }
                $fenhong +=$this_fenhong;
            }

            return dd_money_format($fenhong);
        }
    }

    public static function getDownTeamyejiJiangli($member,$yeji_set,$sysset,$config,$yj=1){
        $is_include_self = getcustom('yx_team_yeji_include_self');
        $is_jicha_custom = getcustom('yx_team_yeji_jicha');
        if(getcustom('yx_team_yeji') && $is_jicha_custom){
            $mid = $member['id'];
            $deep = 999;
            if($config[$member['levelid']]['levelnum'] > 0) $deep = intval($config[$member['levelid']]['levelnum']);
            
            $show_levelid = array_keys($config);
            if(!in_array($member['levelid'],$show_levelid)) return 0;
            
            //当前会员所有下级，查出对应业绩
            $downmids = \app\common\Member::getteammidsByStoplevelid($sysset['aid'],$mid,$deep,[],$member['levelid'],0);
            $new_downmids = [];
            foreach($downmids as $key=>$downmid){
                $this_levelid = Db::name('member')->where('aid',$sysset['aid'])->where('id',$downmid)->value('levelid');
                if(in_array($this_levelid,$show_levelid)){
                    $new_downmids[] =$downmid;
                }
            }
            $new_downmids[] = $mid;
            $yejidata = [];
            foreach($new_downmids as $key => $thismid){
                $this_levelup_time = 0;
                $this_levelid = Db::name('member')->where('aid',$sysset['aid'])->where('id',$thismid)->value('levelid');
                $this_show_levelid = array_keys($config);
                if(in_array($this_levelid,$this_show_levelid)){
                        $levelup_order = Db::name('member_levelup_order')
                            ->where('aid',$sysset['aid'])
                            ->where('mid',$thismid)
                            ->where('levelid',$this_levelid)
                            ->where('status',2)
                            ->order('levelup_time desc')
                            ->find();
                        $this_levelup_time = $levelup_order['levelup_time'];
                    }
                $this_downmids = \app\common\Member::getteammids($sysset['aid'],$thismid);
                if($is_include_self){
                    if($yeji_set['include_self']) $this_downmids[] = $thismid;
                }
                $after_sj_yeji = 0;
                $leiji_total_yeji = 0;
                $leiji_yeji_where = [];
                if($this_levelup_time){
                    $after_sj_yeji_where = [];
                    if($yj ==1){ //预计是当月获得当
                        if($yeji_set['jiesuan_type'] == 1) {//按月
                            $after_month_start = strtotime(date('Y-m-01 00:00:00'));
                            $after_month_end  = strtotime(date('Y-m-t 23:59:59'));
                            if($this_levelup_time > $after_month_start ){
                                $after_month_start =   $this_levelup_time;
                            }
                            $after_sj_yeji_where[] = ['createtime','between',[$after_month_start,$after_month_end]];
                        }
                        elseif($yeji_set['jiesuan_type'] == 2) {//按年
                            $after_year_start=strtotime(date('Y') . '-01-01 00:00:00');
                            $after_year_end=strtotime(date('Y') . '-12-31 23:59:59');
                            if($this_levelup_time > $after_year_start ) {
                                $after_year_start  = $this_levelup_time;
                            }
                            $after_sj_yeji_where[] = ['createtime','between',[$after_year_start,$after_year_end]];
                        }
                        elseif($yeji_set['jiesuan_type'] == 3) {//按季度
                            $this_season_start=strtotime(date('Y-m-01 00:00:00'));
                            if($this_levelup_time > $this_season_start ) {
                                $this_season_start =  $this_levelup_time;
                            }
                            $after_sj_yeji_where[] = ['createtime','between',[$this_season_start,$this_levelup_time]];
                        }elseif($yeji_set['jiesuan_type'] == 4){//按天
                            $start = strtotime(date('Y-m-d 00:00:00'));
                            $end  = time();
                            $after_sj_yeji_where[] = ['createtime','between',[$start,$end]];
                        }
                    }else{// //非预计 是上月
                        if($yeji_set['jiesuan_type'] == 1){//按月
                            $start = strtotime(date('Y-m-01 00:00:00',strtotime('-1 month')));
                            $end  = strtotime(date('Y-m-t 23:59:59',strtotime('-1 month')));
                            //升级时间大于结束时间，无业绩
                        }elseif($yeji_set['jiesuan_type'] == 2){//按年
                            $start=strtotime((date('Y')-1) . '-01-01 00:00:00');
                            $end=strtotime((date('Y')-1) . '-12-31 23:59:59');
                        }elseif($yeji_set['jiesuan_type'] == 3){//按季度
                            $start=strtotime(date('Y-m-01 00:00:00',strtotime('-3 month')));
                            $end=strtotime(date('Y-m-t 23:59:59',strtotime('-1 month')));
                        }elseif($yeji_set['jiesuan_type'] == 4){//按天
                            $end = strtotime(date('Y-m-d 00:00:00'));
                            $start  = $end-86400;
                        }
                        if(getcustom('yx_team_yeji_leiji')){
                            if($yeji_set['yeji_leiji']){
                                //升级时间 < 统计时间
                                if($this_levelup_time < $end){
                                    $leiji_yeji_where[] = ['createtime','between',[$this_levelup_time,$end]];
                                }else{
                                    $leiji_yeji_where[] = ['createtime','between',[0,0]];
                                }
                            }
                        }
                        if($this_levelup_time > $end){
                            $start = 0;
                            $end = 0;
                        }elseif ($this_levelup_time > $start && $this_levelup_time < $end){
                            $start =  $this_levelup_time;
                        }
                        $after_sj_yeji_where[] = ['createtime','between',[$start,$end]];
                    }

                    $shopwhere = '1=1';
                    if(getcustom('yx_team_yeji_type')){
                        //是否有商品限制
                        if($yeji_set['fwtype']==1){
                            if(empty($yeji_set['productids'])){
                                $shopwhere = 'id=0';
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

                    //可以统计商城订单
                    if($tongji_shop){
                        $after_sj_yeji += Db::name('shop_order_goods')->where('aid',$sysset['aid'])->where('mid','in',$this_downmids)->where('status','in',['1','2','3'])->where($after_sj_yeji_where)->where($shopwhere)->sum($shopsumField);
                        if(getcustom('yx_team_yeji_leiji')) {
                            if ($yeji_set['yeji_leiji']) {
                                if($leiji_yeji_where){
                                    //计算累计，从升级时间开始算，到当月或者当季等时间结束的所有业绩
                                    //例如：按月，11.01-11.30，升级时间是6-15，则总业绩是从06-15 -11.30，计算所有业绩，再计算在哪个区间，取比例
                                    $leiji_total_yeji += Db::name('shop_order_goods')->where('aid',$sysset['aid'])->where('mid','in',$this_downmids)->where('status','in',['1','2','3'])->where($leiji_yeji_where)->where($shopwhere)->sum($shopsumField);
                                }
                            }
                        }
                    }

                    //可以统计买单订单
                    if($tongji_maidan){
                        $after_sj_yeji += Db::name('maidan_order')->where('aid',$sysset['aid'])->where('mid','in',$this_downmids)->where($after_sj_yeji_where)->sum('paymoney');//paymoney
                        if(getcustom('yx_team_yeji_leiji')) {
                            if ($yeji_set['yeji_leiji']) {
                                if($leiji_yeji_where){
                                    $leiji_total_yeji += Db::name('maidan_order')->where('aid',$sysset['aid'])->where('mid','in',$this_downmids)->where($leiji_yeji_where)->sum('paymoney');//paymoney
                                }
                            }
                        }
                    }

                }
                $jt_range = $config[$this_levelid]['range'];
                $this_ratio = $this_price = 0;//百分比、固定金额
                $ratio_sj_yeji = $after_sj_yeji;//查找比例的业绩
                if(getcustom('yx_team_yeji_leiji')){
                    if($yeji_set['yeji_leiji']){
                        $ratio_sj_yeji = $leiji_total_yeji;
                    }
                }
                $fenhong=0;
                $countfenhong = true;//是否计算分红
                if($jt_range){
                    foreach($jt_range as $rk=> $range){
                        if( $range['start'] <= $ratio_sj_yeji && $ratio_sj_yeji < $range['end']){
                            if(!$yeji_set['yeji_type']){
                                $this_ratio = $range['ratio'];
                                $this_price = $range['price']??0;
                            }else{
                                $this_ratio = 0;
                                $this_price = $range['price']??0;
                            }
                            if(getcustom('yx_team_yeji_leiji')) {
                                //定制yx_team_yeji_leiji 累计发放类型 0：按最高奖励发放 1：分段发放
                                if($yeji_set['yeji_leiji_sendtype'] == 1 && $after_sj_yeji>0){
                                    $countfenhong = false;
                                    $fenhong = \app\custom\AgentCustom::dealSendtype($yeji_set['yeji_leiji_sendtype'],$ratio_sj_yeji,$after_sj_yeji,$jt_range,0,$range);
                                }
                            }
                        }
                    }
                }
                if($countfenhong){
                    if($this_ratio > 0) $fenhong += $after_sj_yeji * $this_ratio * 0.01;
                    if($this_price > 0) $fenhong += $after_sj_yeji * $this_price;
                }
                
                $yejidata[$thismid] = ['after_yeji'=> $after_sj_yeji,'ratio' => $this_ratio,'levelid' =>$this_levelid,'leveluptime' =>$this_levelup_time,'fenhong' => $fenhong ];
            }
            //找到当前会员的业绩，并删除，下面循环 进行和当前会员进行相减
            $this_member_yeji =$yejidata[$mid];
            $fenhong = $this_member_yeji['fenhong'];
            unset($yejidata[$mid]);
            foreach($yejidata as $key=>$yeji){
                $fenhong -= $yeji['fenhong'];
            }
            $fenhong = $fenhong<=0?0:$fenhong;
            return  $fenhong;
        }
    }
    
    public static function giveActiveScore($aid,$order,$type = 'shop',$returntype = 0){
        if(getcustom('active_score')) {
            $score_weishu = 0;
            if(getcustom('score_weishu',$aid)){
                $score_weishu = Db::name('admin_set')->where('aid',$aid)->value('score_weishu');
            }
            $member_activescore = $business_activescore = 0;
            //让利积分
            $active_score_set = Db::name('active_score_set')->where('aid', $aid)->find();
            $can_handle = 1;
            if($type=='shop'){
                if ($order['status'] == 1 && $active_score_set['reward_time'] == 0) {
                    //设置的收货后赠送
                    $can_handle = 0;
                }
                if ($order['status'] == 3 && $active_score_set['reward_time'] == 1) {
                    //设置的支付后赠送
                    $can_handle = 0;
                }
            }
            if ($order['is_activescore'] == 1) {
                //已经赠送完了
                $can_handle = 0;
            }
            if (!$can_handle) {
                return ['member_activescore'=>0,'business_activescore'=>0];
            }
            
            if($type == 'shop'){
                $shopactivescore_ratio = $active_score_set['shopactivescore_ratio'];
                $member_shopactivescore_ratio = 100;
                $business_shopactivescore_ratio = 0;
                //查找会员id和商家id
                $mid = $order['mid'];
                $business_mid = 0;
                if ($order['bid'] > 0) {
                    $binfo = Db::name('business')->where('aid', $aid)->where('id', $order['bid'])->find();
                    $business_mid = $binfo['mid'];
                    $shopactivescore_ratio = $binfo['shopactivescore_ratio'];
                    $member_shopactivescore_ratio = $binfo['member_shopactivescore_ratio'];
                    $business_shopactivescore_ratio = $binfo['business_shopactivescore_ratio'];
                }
                $oglist = Db::name('shop_order_goods')->where('aid', $aid)->where('orderid', $order['id'])->select()->toArray();
                foreach ($oglist as $k => $og) {
                    $order_money = $og['real_totalprice'];
                    if ($active_score_set['reward_type'] == 0) {
                        //按订单利润
                        $order_money = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                    }
                    $shopactivescore_total = bcmul($order_money, $shopactivescore_ratio / 100, 2);
                    $member_shopactivescore = bcmul($shopactivescore_total, $member_shopactivescore_ratio / 100, 2);
                    if ($member_shopactivescore > 0 && $mid > 0) {
                        if($score_weishu==0){
                            $member_activescore += intval($member_shopactivescore);//总获取让利积分
                        }else{
                            $member_activescore += dd_money_format($member_shopactivescore,$score_weishu);
                        }

                        if(!$returntype) \app\common\Member::addscore($aid, $mid, $member_shopactivescore, '购买商品' . $og['name'] . '赠送');
                    }
                    $business_shopactivescore = bcmul($shopactivescore_total, $business_shopactivescore_ratio / 100, 2);
                    if ($business_shopactivescore > 0 && $business_mid > 0) {
                        if($score_weishu==0){
                            $business_activescore += intval($business_shopactivescore);//总获取让利积分
                        }else{
                            $business_activescore += dd_money_format($business_shopactivescore,$score_weishu);
                        }

                        if(!$returntype) \app\common\Member::addscore($aid, $business_mid, $business_shopactivescore, '购买商品' . $og['name'] . '赠送');
                    }
                    if(!$returntype) Db::name('shop_order_goods')->where('id', $og['id'])->update(['activescore' => $shopactivescore_total]);
                }
                if(!$returntype) Db::name('shop_order')->where('id', $order['id'])->update(['is_activescore' => 1]);
            }else if($type == 'maidan'){
                $maidanactivescore_ratio = $active_score_set['maidanactivescore_ratio'];
                $member_maidanactivescore_ratio = 100;
                $business_maidanactivescore_ratio = 0;
                //查找会员id和商家id
                $mid = $order['mid'];
                $business_mid = 0;
                if ($order['bid'] > 0) {
                    $binfo = Db::name('business')->where('aid', $aid)->where('id', $order['bid'])->find();
                    $business_mid = $binfo['mid'];
                    $maidanactivescore_ratio = $binfo['maidanactivescore_ratio'];
                    $member_maidanactivescore_ratio = $binfo['member_maidanactivescore_ratio'];
                    $business_maidanactivescore_ratio = $binfo['business_maidanactivescore_ratio'];
                }
                $order_money = $order['paymoney'];
                $maidanactivescore_total = bcmul($order_money, $maidanactivescore_ratio / 100, 2);
                $member_maidanactivescore = bcmul($maidanactivescore_total, $member_maidanactivescore_ratio / 100, 2);
                if ($member_maidanactivescore > 0 && $mid > 0) {
                    if($score_weishu==0){
                        $member_activescore += intval($member_maidanactivescore);//总获取让利积分
                    }else{
                        $member_activescore += dd_money_format($member_maidanactivescore,$score_weishu);
                    }

                    if(!$returntype) \app\common\Member::addscore($aid, $mid, $member_maidanactivescore, '买单赠送');
                }
                $business_maidanactivescore = bcmul($maidanactivescore_total, $business_maidanactivescore_ratio / 100, 2);
                if ($business_maidanactivescore > 0 && $business_mid > 0) {
                    if($score_weishu==0){
                        $business_activescore += intval($business_maidanactivescore);//总获取让利积分
                    }else{
                        $business_activescore += dd_money_format($business_maidanactivescore,$score_weishu);
                    }

                    if(!$returntype) \app\common\Member::addscore($aid, $business_mid, $business_maidanactivescore, '买单赠送');
                }
                if(!$returntype) Db::name('maidan_order')->where('id', $order['id'])->update(['is_activescore' => 1,'activescore' => $maidanactivescore_total]);
            }
            return ['member_activescore'=>$member_activescore,'business_activescore'=>$business_activescore];
        }
    }

    //赠送佣金上限
    public static function giveCommissionMax($aid,$order,$type='shop'){
        if(getcustom('member_commission_max',$aid) && getcustom('add_commission_max',$aid)) {
            //送佣金上限
            $set = Db::name('admin_set')->where('aid', $aid)->find();
            if($set['member_commission_max']==0){
                //未开启佣金上限功能
                return true;
            }
            $can_handle = 1;
            if($type=='shop'){
                if ($order['status'] == 1 && $set['commission_max_time'] == 0) {
                    //设置的收货后赠送
                    $can_handle = 0;
                }
                if ($order['status'] == 3 && $set['commission_max_time'] == 1) {
                    //设置的支付后赠送
                    $can_handle = 0;
                }
            }
            if ($order['is_commission_max'] == 1) {
                //已经赠送完了
                $can_handle = 0;
            }
            if (!$can_handle) {
                return true;
            }
            if($order['give_commission_max2']>0 || $order['give_commission_max']>0){
                //产品单独设置了赠送佣金上限
                return true;
            }
            $commission_max_ratio = 0;
            $member_commission_max_ratio = 0;
            $business_commission_max_ratio = 0;
            //查找会员id和商家id
            $mid = $order['mid'];
            $business_mid = 0;
            if ($order['bid'] > 0) {
                $binfo = Db::name('business')->where('aid', $aid)->where('id', $order['bid'])->find();
                $business_mid = $binfo['mid'];
                $commission_max_ratio = $binfo['commission_max_ratio'];
                $member_commission_max_ratio = $binfo['member_commission_max_ratio'];
                $business_commission_max_ratio = $binfo['business_commission_max_ratio'];
            }
            /********************1、按商户独立设置的让利比例赠送**************************/
            if($commission_max_ratio>0){
                //商户独立设置让利比例
                if($type=='shop'){
                    $oglist = Db::name('shop_order_goods')->where('aid', $aid)->where('orderid', $order['id'])->select()->toArray();
                    foreach ($oglist as $k => $og) {
                        $pro_givecommax_time = Db::name('shop_product')->where('id',$og['proid'])->value('givecommax_time');
                        if($pro_givecommax_time==-1){
                            //产品单独设置了不参与赠送
                            continue;
                        }
                        $order_money = $og['real_totalprice'];
                        if ($set['commission_max_type'] == 0) {
                            //按订单利润
                            $order_money = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                        }
                        $commission_max_total = bcmul($order_money, $commission_max_ratio / 100, 2);
                        $member_commission_max = bcmul($commission_max_total, $member_commission_max_ratio / 100, 2);
                        if ($member_commission_max > 0 && $mid > 0) {
                            \app\common\Member::addcommissionmax($aid, $mid, $member_commission_max, '购买商品' . $og['name'] . '赠送', $type, $order['id']);
                        }
                        $business_commission_max = bcmul($commission_max_total, $business_commission_max_ratio / 100, 2);
                        if ($business_commission_max > 0 && $business_mid > 0) {
                            \app\common\Member::addcommissionmax($aid, $business_mid, $business_commission_max, '购买商品' . $og['name'] . '赠送', $type, $order['id']);
                        }
                        Db::name('shop_order_goods')->where('id', $og['id'])->update(['commission_max_total' => $commission_max_total]);
                    }
                    Db::name('shop_order')->where('id', $order['id'])->update(['is_commission_max' => 1]);
                }else if($type=='maidan'){
                    $order_money = $order['money'];

                    $commission_max_total = bcmul($order_money, $commission_max_ratio / 100, 2);
                    $member_commission_max = bcmul($commission_max_total, $member_commission_max_ratio / 100, 2);
                    if ($member_commission_max > 0 && $mid > 0) {
                        \app\common\Member::addcommissionmax($aid, $mid, $member_commission_max, '买单赠送', $type, $order['id']);
                    }
                    $business_commission_max = bcmul($commission_max_total, $business_commission_max_ratio / 100, 2);
                    if ($business_commission_max > 0 && $business_mid > 0) {
                        \app\common\Member::addcommissionmax($aid, $business_mid, $business_commission_max, '买单赠送', $type, $order['id']);
                    }
                    Db::name('maidan_order')->where('id', $order['id'])->update(['is_commission_max' => 1,'commission_max_total' => $commission_max_total]);
                }
            }
            /********************2、按平台设置的消费额赠送**************************/
            if($commission_max_ratio<=0){
                //产品未设置，商户未设置，按消费额赠送
                $commission_max_xf = json_decode($set['commission_max_xf'],true);
                if($type=='shop'){
                    $order_money = $order['totalprice'];
                    //按订单利润
                    $cacl_order_money = 0;
                    $oglist = Db::name('shop_order_goods')->where('aid', $aid)->where('orderid', $order['id'])->select()->toArray();
                    foreach ($oglist as $k => $og) {
                        $pro_givecommax_time = Db::name('shop_product')->where('id',$og['proid'])->value('givecommax_time');
                        if($pro_givecommax_time==-1){
                            //产品单独设置了不参与赠送
                            continue;
                        }
                        if($set['commission_max_type'] == 0){
                            //按利润
                            $lirun = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                        }else{
                            //按订单金额
                            $lirun = $og['real_totalprice'];
                        }
                        $cacl_order_money = bcadd($cacl_order_money, $lirun, 2);
                    }
                }
                if($type=='maidan'){
                    $order_money = $order['money'];
                    $cacl_order_money = $order_money;
                }
                $count = count($commission_max_xf);
                $bili = 0;//赠送比例
                for($i=$count-1;$i>=0;$i--){
                    $xf_num = $commission_max_xf[$i]['xf_num']??0;
                    if($order_money>=$xf_num){
                        $bili = $commission_max_xf[$i]['bili']??0;
                        break;
                    }
                }
                $member_commission_max = bcmul($cacl_order_money, $bili / 100, 2);
                if($member_commission_max>0){
                    \app\common\Member::addcommissionmax($aid, $mid, $member_commission_max, '购买订单ID'.$order['id'].'满' . $xf_num . '赠送', $type, $order['id']);
                }
                Db::name('shop_order')->where('id', $order['id'])->update(['is_commission_max' => 1]);
            }
            return ['status'=>1,'member_commission_max'=>$member_commission_max];
        }

    }

    //买单分红奖励 团队
    public static function maidanFenhongJl($order,$type='maidan'){
        $maidan_new_custom = getcustom('maidan_new');
        if(getcustom('business_maidan_team_fenhong')){
            if($order['bid'] > 0 && $order['mid'] > 0){//付款是多商户的
                $aid = $order['aid'];
                $bid = $order['bid'];
                $field = 'maidan_fenhong_jl_status,maidan_fenhong_jl_minprice,maidan_fenhong_jl_data,maidan_fenhong_jl_lv';
                if($maidan_new_custom){
                    $field .= ',maidan_new_fenhong_jl_status,maidan_new_fenhong_jl_minprice,maidan_new_fenhong_jl_data,maidan_new_fenhong_jl_lv';
                }
                $business = Db::name('business')->where('aid',$aid)->where('id',$bid)->field($field)->find();
                if($maidan_new_custom && $type =='maidan_new'){
                    //如果是新买单，且定制已开，重置配置
                    $business['maidan_fenhong_jl_status'] =  $business['maidan_new_fenhong_jl_status'];
                    $business['maidan_fenhong_jl_minprice'] =  $business['maidan_new_fenhong_jl_minprice'];
                    $business['maidan_fenhong_jl_data'] =  $business['maidan_new_fenhong_jl_data'];
                    $business['maidan_fenhong_jl_lv'] =  $business['maidan_new_fenhong_jl_lv'];
                }
                $maidan_fenhong_jicha = Db::name('business_sysset')->where('aid',$aid)->value('maidan_fenhong_jicha');
                //未开启
                if($business['maidan_fenhong_jl_status'] != 1){
                    return false;
                }
                $maidan_member = Db::name('member')->where('id',$order['mid'])->field('id,path')->find();
                if(empty($maidan_member) || empty($maidan_member['path'])){
                    //无推荐人
                    return false;
                }
                $maidan_member_path = [];
                $maidan_member_path = explode(',',$maidan_member['path']);
                $maidan_member_path = array_reverse($maidan_member_path);
                //统计商户的买单金额
                $business_maidan_money = Db::name('maidan_order')->where('aid',$order['aid'])->where('bid',$order['bid'])->where('status',1)->sum('paymoney');
                if($maidan_new_custom){
                    //买单和新买单都计入考核
                    $business_maidan_new_money =0+ Db::name('maidan_new_order')->where('aid',$aid)->where('bid',$bid)->where('status',1)->sum('paymoney');
                        $business_maidan_money += $business_maidan_new_money;
                }
                //如果买单的会员的 path中包含 商户会员，且 设置的最低金额 >= 商户买单金额
                if($business_maidan_money >= $business['maidan_fenhong_jl_minprice'] && $business['maidan_fenhong_jl_minprice'] > 0){
                    $last_ratio = 0;
                    $maidan_fenhong_jl_data = json_decode($business['maidan_fenhong_jl_data'],true);
                    $level_i = 0;
                    foreach($maidan_member_path as $pid){
                        $level_i++;
                        //层级 超过 设置的层级，不发放
                        if($level_i >$business['maidan_fenhong_jl_lv']  && $business['maidan_fenhong_jl_lv'] > 0){
                            break;
                        }
                        $parent = Db::name('member')->where('id',$pid)->field('id,levelid')->find();
                        $thisratio = $maidan_fenhong_jl_data[$parent['levelid']]['ratio'];
                        if(!$thisratio)continue;
                        //如果开启级差
                        if($maidan_fenhong_jicha){
                            $ratio = $thisratio - $last_ratio;
                        }else{
                            $ratio = $thisratio;
                        }
                        if($ratio > 0){
                            $last_ratio = $thisratio;
                            $commission = dd_money_format($order['paymoney'] * $ratio * 0.01,2);
                            if($commission > 0){
                                $fhdata = [];
                                $fhdata['aid'] = $aid;
                                $fhdata['mid'] = $pid;
                                $fhdata['commission'] = $commission;
                                $fhdata['remark'] = '买单分红奖励';
                                $fhdata['type'] = 'business_maidan_team_fenhong';
                                $fhdata['module'] = 'maidan';
                                $fhdata['createtime'] = time();
                                $fhdata['ogids'] = $order['id'];
                                $fhdata['frommid'] = $order['mid'];
                                $fhdata['status'] = 1;
                                $fhid = Db::name('member_fenhonglog')->insertGetId($fhdata);
                                \app\common\Member::addcommission($aid,$pid,$maidan_member['id'],$commission,'买单分红奖励',1,'',0,0,$fhid);
                            }
                        }
                    }
                }
            }
        }
    }

    public static function quanyihexiao($ogid,$is_check=0,$hexiao_num=0,$mdid=0){
        if(getcustom('product_quanyi')){
            if($hexiao_num<=0){
                return ['status' => 0, 'msg' => '请填写正确的核销次数'];
            }
            //核销周期检测
            $og = Db::name('shop_order_goods')->where('id',$ogid)->find();
            $aid = $og['aid'];
            $orderid = $og['orderid'];
            $order = Db::name('shop_order')->where('id',$orderid)->find();
            $proid = $og['proid'];
            $product = Db::name('shop_product')->where('id',$proid)->find();
            if($product['quanyi_hexiao_circle']==1){
                //每周
                $start_date = date('Y-m-d H:i:s',mktime(0,0,0,date('m'),date('d')-date('w')+1,date('Y')));
                $end_date = date('Y-m-d H:i:s',mktime(23,59,59,date('m'),date('d')-date('w')+7,date('Y')));
                $s_time = strtotime($start_date);
                $e_time = strtotime($end_date);
                $err_msg = '每月';
            }elseif($product['quanyi_hexiao_circle']==2){
                //每天
                $s_time = strtotime(date('Y-m-d', time()));
                $e_time = $s_time + 86400;
                $err_msg = '每周';
            }else{
                //每月
                $s_time = strtotime(date('Y-m-1', time()));
                $e_time = strtotime(date('Y-m-t', time())) + 86400;
                $err_msg = '每日';
            }

            $where = [];
            $where[] = ['aid', '=', $aid];
            $where[] = ['orderid', '=', $ogid];
            $where[] = ['createtime', 'between', [$s_time, $e_time]];
            $have_hexiao_count = Db::name('hexiao_shopproduct')->where($where)->order('id desc')->sum('num');
            if (($have_hexiao_count+$hexiao_num) > $product['circle_hexiao_num']) {
                return ['status' => 0, 'msg' => $err_msg.'可核销'.$product['circle_hexiao_num'].'次,当前已核销'.$have_hexiao_count.'次'];
            }
            $where = [];
            $where[] = ['aid', '=', $aid];
            $where[] = ['orderid', '=', $ogid];
            $hexiao_used =  Db::name('hexiao_shopproduct')->where($where)->order('id desc')->sum('num');
            if (($hexiao_used+$hexiao_num) > $og['hexiao_num_total']) {
                return ['status' => 0, 'msg' => '总核销次数为'.$og['hexiao_num_total'].'次,当前已核销'.$have_hexiao_count.'次'];
            }
            if($is_check){
                //仅仅是检测是否可以核销
                return ['status' => 1, 'msg' => $err_msg.'可核销'.$product['circle_hexiao_num'].'次,当前已核销'.$have_hexiao_count.'次'];
            }

            $is_collect = 0;
            Db::name('shop_order')->where('aid',$aid)->where('id',$orderid)->inc('hexiao_num_used',$hexiao_num)->update();
            Db::name('shop_order_goods')->where('aid',$aid)->where('orderid',$orderid)->inc('hexiao_num_used',$hexiao_num)->update();
            $order_info = Db::name('shop_order')->where('id',$orderid)->find();
            if($order_info['hexiao_num_used']>=$order_info['hexiao_num_total']){
                if($product['quanyi_hexiao_return'] == 1){
                    //核销完成，退还费用
                    $remark = '订单'.$order_info['id'].'核销完成退款';
                    \app\common\Member::addmoney($order_info['aid'],$order_info['mid'],$order_info['totalprice'],$remark);
                }
                $is_collect = 1;
            }

            if(getcustom('mendian_hexiao_givemoney') && $mdid){
                $mendian = Db::name('mendian')->where('aid',$aid)->where('id',$mdid)->find();
                if($mendian){
                    $givemoney = 0;
                    if(getcustom('product_mendian_hexiao_givemoney')){
                        $pro = Db::name('shop_product')->where('aid',$aid)->where('id',$og['proid'])->find();
                        $hexiao_set = Db::name('shop_product_mendian_hexiaoset')->where('aid',$aid)->where('mdid',$order['mdid'])->where('proid',$og['proid'])->find();
                        if($hexiao_set['hexiaogivepercent']>0 || $hexiao_set['hexiaogivemoney']>0){
                            $givemoney += $hexiao_set['hexiaogivepercent'] * 0.01 * $og['real_totalprice'] + $hexiao_set['hexiaogivemoney'];
                        }
                        elseif(!is_null($pro['hexiaogivepercent']) || !is_null($pro['hexiaogivemoney'])){

                            $givemoney += $pro['hexiaogivepercent'] * 0.01 * $og['real_totalprice'] + $pro['hexiaogivemoney']*$og['num'];
                        }else{
                            $givemoney += $mendian['hexiaogivepercent'] * 0.01 * $og['real_totalprice'] + $mendian['hexiaogivemoney'];
                        }
                    }else{
                        $pro = Db::name('shop_product')->where('aid',$aid)->where('id',$og['proid'])->find();
                        if(!is_null($pro['hexiaogivepercent']) || !is_null($pro['hexiaogivemoney'])){
                            $givemoney += $pro['hexiaogivepercent'] * 0.01 * $og['real_totalprice'] + $pro['hexiaogivemoney']*$og['num'];
                        }else{
                            $givemoney += $mendian['hexiaogivepercent'] * 0.01 * $og['real_totalprice'] + $mendian['hexiaogivemoney'];
                        }
                    }
                    $avg_money = bcdiv($givemoney , $og['hexiao_num_total'],2);
                    $givemoney_real = bcmul($avg_money,$hexiao_num,2);
                    if($givemoney_real > 0){
                        \app\common\Mendian::addmoney($aid,$mendian['id'],$givemoney_real,'核销订单'.$order['ordernum']);
                    }
                }
            }
            return ['status'=>1,'is_collect'=>$is_collect];
        }
    }
    //购买积分商城商品向指定会员赠送佣金余额积分
    public static function scoreProductMembergive($aid,$order,$type){
        if(getcustom('score_product_membergive')){
            $info = Db::name('scoreshop_sysset')->where('aid',$aid)->find();            
            if($info['membergive_fafangtime'] == $type && $info['membergive_isfafang'] == 1){
                $scoreorderlist = Db::name('scoreshop_order_goods')->where('orderid',$order['id'])->where('membergive_member_status',0)->select();         
                foreach($scoreorderlist as $k=>$v){
                    if($v['membergive_member_id'] > 0){
                        if($v['membergive_commission'] > 0){
                            \app\common\Member::addcommission($aid,$v['membergive_member_id'],$order['id'],$v['membergive_commission']*$v['num'],'购买['.$v['name'].']赠送');
                        }
                        if($v['membergive_score'] > 0){
                            \app\common\Member::addscore($aid,$v['membergive_member_id'],$v['membergive_score']*$v['num'],'购买['.$v['name'].']赠送');
                        }
                        if($v['membergive_money'] > 0){
                            \app\common\Member::addmoney($aid,$v['membergive_member_id'],$v['membergive_money']*$v['num'],'购买['.$v['name'].']赠送');
                        }
                    }
                }
                Db::name('scoreshop_order_goods')->where('orderid',$order['id'])->update(['membergive_member_status'=>1]);
            }
        }
    }

    /**
     * 订单创建完成操作
     * 相关订单API中的createorder中触发
     * 买单、收银台、优惠券、点餐是支付完成触发\app\model\Payorder::payorder
     * 幸运拼团订单开奖后触发\app\model\LuckyCollage:kaijiang
     * @param $aid
     * @param $orderid
     * @param string $type
     */
    public static function order_create_done($aid,$orderid,$type='shop'){
        //结算分红
        $type_arr = [
            'yuyue',//预约订单,创建完成触发
            'scoreshop',//积分商城订单,创建完成触发
            'lucky_collage',//幸运拼团订单,创建完成触发
            'maidan',//扫码买单 ，支付完成触发
            'cashier',//收银台 ，支付完成触发
            'restaurant_shop',//餐饮外卖,创建完成触发
            'restaurant_takeaway',//餐饮外卖,创建完成触发
            'coupon',//优惠券，支付完成触发
            'kecheng',//课程,创建完成触发
            'business_reward',// 商家打赏，支付完成触发
            'hotel',//酒店,创建完成触发
            'shop',//商城,创建完成触发
        ];
        $info = Db::name('sysset')->where('name','webinfo')->find();
        $webinfo = json_decode($info['value'],true);
        if(!$webinfo['jiesuan_fenhong_type']) {
            if (in_array($type, $type_arr)) {
                \app\common\Fenhong::jiesuan_single($aid, $orderid, $type);
            }
        }
        if(getcustom('yx_team_yeji_fenhong',$aid)) {
            if ($type == 'shop') {
                //团队业绩阶梯分红单独结算(需要统计支付前的业绩，所以只用同步执行)
                \app\common\Fenhong::jiesuan_yeji_fenhong($aid, $orderid, 'shop');
            }
        }
    }
    //关闭订单操作
    public static function order_close_done($aid,$orderid,$type){
        $map = [];
        $map[] = ['aid','=',$aid];
        $map[] = ['status','=',0];
        switch ($type){
            case 'yuyue'://预约订单
                Db::name('member_fenhonglog')->where($map)->where('module', 'yuyue')->where('ogids', $orderid)->update(['status' => 2]);
                break;
            case 'scoreshop'://积分商城订单
                $ogids = Db::name('scoreshop_order_goods')->where('aid',$aid)->where('orderid',$orderid)->column('id');
                if($ogids) {
                    Db::name('member_fenhonglog')->where($map)->where('module', 'scoreshop')->where('ogids','in', $ogids)->update(['status' => 2]);
                }
                break;
            case 'lucky_collage':
                Db::name('member_fenhonglog')->where($map)->where('module','lucky_collage')->where('ogids',$orderid)->update(['status'=>2]);
                Db::name('member_fenhonglog')->where($map)->where('module','luckycollage')->where('ogids',$orderid)->update(['status'=>2]);//兼容之前的错误
                break;
            case 'maidan'://买单
                Db::name('member_fenhonglog')->where($map)->where('module','maidan')->where('ogids',$orderid)->update(['status'=>2]);
                break;
            case 'cashier'://收银台订单
                //多商户收银订单，按商户地址结算区域分红
                $ogids = Db::name('cashier_order')->where('aid',$aid)->where('id',$orderid)->column('id');
                if($ogids){
                    Db::name('member_fenhonglog')->where($map)->where('module','cashier')->where('ogids','in',$ogids)->update(['status'=>2]);
                }

                break;
            case 'restaurant_shop'://点餐订单
                $ogids = Db::name('restaurant_shop_order_goods')->where('aid',$aid)->where('orderid',$orderid)->column('id');
                if($ogids) {
                    Db::name('member_fenhonglog')->where($map)->where('module', 'restaurant_shop')->where('ogids','in', $ogids)->update(['status' => 2]);
                }
                break;
            case 'restaurant_takeaway'://点餐订单
                $ogids = Db::name('restaurant_takeaway_order_goods')->where('aid',$aid)->where('orderid',$orderid)->column('id');
                if($ogids) {
                    Db::name('member_fenhonglog')->where($map)->where('module', 'restaurant_takeaway')->where('ogids','in', $ogids)->update(['status' => 2]);
                }
                break;
            case 'coupon'://优惠券订单
                Db::name('member_fenhonglog')->where($map)->where('module','coupon')->where('ogids',$orderid)->update(['status'=>2]);
                break;
            case 'kecheng'://课程订单
                Db::name('member_fenhonglog')->where($map)->where('module','kecheng')->where('ogids',$orderid)->update(['status'=>2]);
                break;
            case 'business_reward'://
                if(getcustom('business_reward_member',$aid)){
                    Db::name('member_fenhonglog')->where($map)->where('module','business_reward')->where('ogids',$orderid)->update(['status'=>2]);
                }
                break;
            case 'hotel'://点餐订单
                if(getcustom('hotel',$aid)){
                    Db::name('member_fenhonglog')->where($map)->where('module','hotel')->where('ogids',$orderid)->update(['status'=>2]);
                }
                break;
            case 'gold_bean_shop'://金豆商城订单
                if(getcustom('gold_bean_shop')){
                    $ogids = Db::name('gold_bean_shop_order_goods')->where('aid',$aid)->where('orderid',$orderid)->column('id');
                    if($ogids) {
                        Db::name('member_fenhonglog')->where($map)->where('module', 'gold_bean_shop')->where('ogids','in', $ogids)->update(['status' => 2]);
                    }
                }
                break;
            default:
                //商城订单
                $ogids = Db::name('shop_order_goods')->where('aid',$aid)->where('orderid',$orderid)->column('id');
                if($ogids){
                    Db::name('member_fenhonglog')->where($map)->where('module','shop')->where('ogids','in',$ogids)->update(['status'=>2]);
                }
                break;
        }
    }

    // 分销商开启门店核销后，对应核销的商品金额自动换算成对应积分赠送到核销管理员
    public static function mendian_hexiao_money_to_score($aid,$umid,$totalprice=0){
        if(getcustom('mendian_hexiao_money_to_score')){
            $admin_set = Db::name('admin_set')->where('aid',$aid)->field('money_to_score,money_to_score_bili,score2money')->find();
            if($admin_set['score2money'] <= 0){
                $admin_set['score2money'] = 1;
            }
            if($admin_set['money_to_score'] && $admin_set['money_to_score_bili'] > 0 && $totalprice > 0 && $umid){

                $tscore = $totalprice/$admin_set['score2money'];
                $send_score = floor($admin_set['money_to_score_bili'] * $tscore / 100);
                if($send_score > 0){
                    \app\common\Member::addscore($aid,$umid,$send_score,'核销奖励');
                }
            }
        }
    }
    
    public static function deal_paymoney_commissionfrozenset($aid,$order,$member='',$leveldata=''){
        if(getcustom('member_level_paymoney_commissionfrozenset')){
            if(!$member){
                $member = Db::name('member')->where('id',$order['mid'])->find();
                if(!$member) return;
            }

            if(!$leveldata){
                $leveldata = Db::name('member_level')->where('id',$order['levelid'])->where('aid',$aid)->find();
                if(!$leveldata) return;
            }

            //升级费用 单独设置直推分销
            if($leveldata['apply_payfenxiao'] == 2 ){
                $nowid = 0;//现在增加的冻结记录id
                //处理上级发佣金
                if($leveldata['apply_paymoney_commission1']>0 && $order['totalprice']>0){
                    //直推佣金
                    $paymoney_commission1 = $leveldata['apply_paymoney_commission1'] * $order['totalprice'] * 0.01;
                    if($paymoney_commission1 > 0 && $member['pid']>0){
                        $parent1 = Db::name('member')->where('aid',$aid)->where('id',$member['pid'])->find();
                        if($parent1 && $parent1['levelid']>0){
                            $agleveldata1 = Db::name('member_level')->where('id',$parent1['levelid'])->where('aid',$aid)->find();
                            if($agleveldata1 && $agleveldata1['can_agent']!=0){
                                //冻结部分
                                if($leveldata['apply_paymoney_commission1_frozenpercent']>0){
                                    $fuchi_money = round($paymoney_commission1 * $leveldata['apply_paymoney_commission1_frozenpercent'] / 100,2);
                                    if($fuchi_money>0){
                                        $paymoney_commission1 -= $fuchi_money;//减少只发佣金
                                        //增加冻结佣金
                                        $addFuchi = \app\common\Member::addFuchi($aid,$member['pid'],$member['id'],$fuchi_money,t('下级').t('会员').'升级奖励');
                                        if($addFuchi && $addFuchi['status'] == 1){
                                            $nowid = Db::name('member_fuchi_record')->insertGetId(['aid'=>$aid,'mid'=>$member['pid'],'frommid'=>$order['mid'],'orderid'=>$order['id'],'ogid'=>0,'type'=>'apply_paymoney_commission','commission'=>$fuchi_money,'score'=>0,'remark'=>t('下级').t('会员').'升级奖励冻结','createtime'=>time()]);
                                        }
                                    }
                                }
                                $paymoney_commission1 = round($paymoney_commission1,2);
                                if($paymoney_commission1>0){
                                    \app\common\Member::addcommission($aid,$member['pid'],$order['mid'],$paymoney_commission1,t('下级').t('会员').'升级奖励');
                                    Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$member['pid'],'frommid'=>$order['mid'],'orderid'=>$order['id'],'ogid'=>0,'type'=>'levelup','commission'=>$paymoney_commission1,'score'=>0,'remark'=>t('下级').t('会员').'升级奖励','createtime'=>time(),'status'=>1,'endtime'=>time()]);

                                    //公众号通知 分销成功提醒
                                    $tmplcontent = [];
                                    $tmplcontent['first'] = '恭喜您，成功分销获得'.t('佣金').'：￥'.$paymoney_commission1;
                                    $tmplcontent['remark'] = '点击进入查看~';
                                    $tmplcontent['keyword1'] = $order['title']; //商品信息
                                    $tmplcontent['keyword2'] = $order['totalprice'];//商品单价
                                    $tmplcontent['keyword3'] = $paymoney_commission1.'元';//商品佣金
                                    $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$order['createtime']);//分销时间
                                    $rs = \app\common\Wechat::sendtmpl($aid,$parent1['id'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                                    //短信通知
                                    $rs = \app\common\Sms::send($aid,$parent1['tel'],'tmpl_fenxiaosuccess',['money'=>$paymoney_commission1]);
                                }
                            }
                        }
                    }
                }
                //解冻之前的所有的冻结佣金
                $where = [];
                $where[] = ['mid','=',$member['pid']];
                if($nowid){
                    $where[] = ['id','<',$nowid];
                }
                $where[] = ['aid','=',$aid];
                $where[] = ['status','=',0];
                $frozen_record = Db::name('member_fuchi_record')->where($where)->select()->toArray();
                if($frozen_record){
                    foreach ($frozen_record as $fv){
                        \app\common\Member::addFuchi($aid,$fv['mid'],$fv['frommid'],$fv['commission']*-1,'升级奖励解冻');
                        \app\common\Member::addcommission($aid, $fv['mid'], $fv['frommid'], $fv['commission'], '升级奖励解冻',1,'unfrozen');
                        Db::name('member_fuchi_record')->where('id',$fv['id'])->where('status',0)->update(['status'=>1,'endtime'=>time()]);
                    }
                    unset($fv);
                }
            }
        }
    }

    /**
     * 商品押金
     * 功能1：https://doc.weixin.qq.com/sheet/e3_AV4AYwbFACwhK9lmw4HTpWYpjlp8K?scode=AHMAHgcfAA0s91tNOVAeYAOQYKALU&tab=lom7cg
     * @author: liud
     * @time: 2025/1/4 上午10:42
     */
    public static function order_product_deposit($aid,$orderid,$paymoney){
        if(getcustom('product_deposit_mode')){
            $shopset = Db::name('shop_sysset')->where('aid',$aid)->find();

            if($shopset['product_deposit_mode'] == 0){
                return;
            }

            //商城订单
            if(!$order = Db::name('shop_order')->where('aid',$aid)->where('status','in',[1,2,3])->where('id',$orderid)->find()){
                return;
            }

            $ogsarr = Db::name('shop_order_goods')->where('aid',$aid)->where('orderid',$orderid)->select()->toArray();

            $money = 0;
            foreach ($ogsarr as $kk => $vv){
                if($vv['deposit_mode'] == 1){
                    $money += $vv['real_totalmoney'];
                }
            }

            if($money <= 0 ) {
                return;
            }

            Db::startTrans();
            try {
                $member = Db::name('member')->where('id',$order['mid'])->where('aid',$aid)->lock(true)->find();
                if(!$member) {
                    Db::rollback();
                    return;
                }

                $updata = [];
                $after = $member['product_deposit'] + $money;
                $updata['product_deposit'] = $after;
                $up = Db::name('member')->where('id',$order['mid'])->where('aid',$aid)->update($updata);

                $logdata = [
                    'aid' => $aid,
                    'mid' => $order['mid'],
                    'money' => $money,
                    'createtime' => time(),
                    'orderid' => $order['id'],
                    'withdrawaltime' => time() + 86400 * $shopset['product_deposit_mode_withdrawalday'],
                ];

                Db::name('product_deposit_log')->insertGetId($logdata);

                Db::commit();
            }catch (\Exception $e) {
                Db::rollback();
                Log::write('order_product_deposit error line:'.__LINE__.' msg:'.$e->getMessage(),'error');
            }
        }
    }

    //处理商城订单退款退还积分、优惠券、抵扣值等操作
    public static function dealShoprefundReturn($aid,$order){
        //积分抵扣的返还
        if ($order['scoredkscore'] > 0) {
            \app\common\Member::addscore($aid, $order['mid'], $order['scoredkscore'], '订单退款返还');
        }
        if ($order['givescore2'] > 0) {
            \app\common\Member::addscore($aid, $order['mid'], -$order['givescore2'], '订单退款扣除');
        }
        //扣除消费赠送积分
        \app\common\Member::decscorein($aid,'shop',$order['id'],$order['ordernum'],'订单退款扣除消费赠送');
        //查询后台是否开启退还已使用的优惠券
        $return_coupon = Db::name('shop_sysset')->where('aid',$aid)->value('return_coupon');
        //优惠券抵扣的返还
        if ($return_coupon && $order['coupon_rid']) {
            \app\common\Coupon::refundCoupon($aid,$order['mid'], $order['coupon_rid'],$order);
            //Db::name('coupon_record')->where('aid', $aid)->where('mid', $order['mid'])->where('id', 'in', $order['coupon_rid'])->update(['status' => 0, 'usetime' => '']);
        }
        //元宝返回
        if (getcustom('pay_yuanbao',$aid) && $order['is_yuanbao_pay'] == 1 && $order['total_yuanbao'] > 0) {
            \app\common\Member::addyuanbao($aid, $order['mid'], $order['total_yuanbao'], '订单退款返还');
        }
        if ($order['givescore2'] > 0) {
            \app\common\Member::addscore($aid, $order['mid'], -$order['givescore2'], '订单退款扣除');
        }
        if(getcustom('money_dec',$aid)){
            if($order['dec_money']>0){
                \app\common\Member::addmoney($aid,$order['mid'],$order['dec_money'],t('余额').'抵扣返回，订单号: '.$order['ordernum']);
            }
        }
        if(getcustom('yx_invite_cashback',$aid)){
            //取消邀请返现
            \app\custom\OrderCustom::cancel_invitecashbacklog($aid,$order,'订单退款');
        }
        //退款退还佣金
        if(getcustom('commission_orderrefund_deduct')){
            \app\common\Fenxiao::refundFenxiao($aid,$order['id'],'shop');
            \app\common\Order::refundFenhongDeduct($order,'shop');
        }
        if(getcustom('member_goldmoney_silvermoney',$aid)){
            //返回银值抵扣
            if($order['silvermoneydec']>0){
                $res = \app\common\Member::addsilvermoney($aid,$order['mid'],$order['silvermoneydec'],t('银值').'抵扣返回，订单号: '.$order['ordernum'],$order['ordernum']);
            }
            //返回金值抵扣
            if($order['goldmoneydec']>0){
                $res = \app\common\Member::addgoldmoney($aid,$order['mid'],$order['goldmoneydec'],t('金值').'抵扣返回，订单号: '.$order['ordernum'],$order['ordernum']);
            }
        }
        if(getcustom('member_dedamount',$aid)){
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
                \app\common\Member::addshopscore($aid,$order['mid'],$order['shopscore'],t('产品积分').'扣返回，订单号: '.$order['ordernum'],$params);
            }
        }
        if(getcustom('deposit')){
            //计次券 应该返回次数
            if($order['water_coupon_rid']){
                $couponrecord_list = Db::name('coupon_record')->where('aid',$aid)->where('mid',$order['mid'])->where('id','in',$order['water_coupon_rid'])->select()->toArray();
                $water_coupon_num_array = explode(',',$order['water_coupon_num']);
                foreach($couponrecord_list as $key=>$couponrecord){
                    $water_coupon_num = $water_coupon_num_array[$key];//对应的数量
                    $used_count = $couponrecord['used_count'] - $water_coupon_num;
                    $update =[
                        'status' => 0,
                        'used_count'=> $used_count <=0?0:$used_count
                    ];
                    Db::name('coupon_record')->where('aid',$aid)->where(['mid'=>$order['mid'],'id'=>$couponrecord['id']])->update($update);
                    //核销记录删除
                    Db::name('hexiao_order')->where('aid',$aid)->where('ordernum',$order['ordernum'])->where('orderid',$couponrecord['id'])->limit($water_coupon_num)->delete();
                }
            }
        }
    }

    //统一处理商城订单退款后操作
    public static function dealShoprefundAfter($aid,$bid,$orderid,$order,$refund_id,$prolist){
        if(getcustom('yx_mangfan')){
            \app\custom\Mangfan::delAndCreate($aid, $order['mid'], $order['id'], $order['ordernum']);
        }

        //      //积分抵扣的返还
        //      if($order['scoredkscore'] > 0){
        //          \app\common\Member::addscore($aid,$order['mid'],$order['scoredkscore'],'订单退款返还');
        //      }
        //      //优惠券抵扣的返还
        //      if($order['coupon_rid'] > 0){
        //          Db::name('coupon_record')->where('aid',$aid)->where(['mid'=>$order['mid'],'id'=>$order['coupon_rid']])->update(['status'=>0,'usetime'=>'']);
        //      }

        if (getcustom('cefang') && $aid == 2) { //定制1 订单对接 同步到策方
            $order['status'] = 4;
            \app\custom\Cefang::api($order);
        }
        if(getcustom('member_gongxian')){
            //扣除贡献值
            $admin = Db::name('admin')->where('id',$aid)->find();
            $set = Db::name('admin_set')->where('aid',$aid)->find();
            $gongxian_days = $set['gongxian_days'];
            //等级设置优先
            $level_gongxian_days = Db::name('member_level')->where('aid',$aid)->where('id',$order['mid'])->value('gongxian_days');
            if($level_gongxian_days > 0){
                $gongxian_days = $level_gongxian_days;
            }
            if($admin['member_gongxian_status'] == 1 && $set['gongxianin_money'] > 0 && $set['gognxianin_value'] > 0 && ((time()-$order['paytime'])/86400 <= $gongxian_days)){
                $log = Db::name('member_gongxianlog')->where('aid',$aid)->where('mid',$order['mid'])->where('channel','shop')->where('orderid',$order['id'])->find();
                if($log){
                    $givevalue = $log['value']*-1;
                    Db::name('member_gongxianlog')->where('aid',$aid)->where('id',$log['id'])->update(['is_expire'=>2]);
                    \app\common\Member::addgongxian($aid,$order['mid'],$givevalue,'退款扣除'.t('贡献'),'shop_refund',$order['id']);
                }
            }
        }
        if(getcustom('product_bonus_pool')){
            foreach ($prolist as $key=>$val){
                $recordlist = Db::name('member_bonus_pool_record')->where('ogid',$val['id'])->select()->toArray();
                foreach ($recordlist as $rkey=>$rval){
                    //修改记录
                    Db::name('member_bonus_pool_record')->where('id',$rval['id'])->update(['status' => 2]);
                    //修改奖金池
                    Db::name('bonus_pool')->where('id',$rval['bpid'])->update(['status' => 0,'mid' => 0]);
                }
            }
        }
        if(getcustom('erp_wangdiantong')){
            $reog = Db::name('shop_refund_order_goods')->where('refund_orderid', $refund_id)->where('wdt_status',1)->select()->toArray();
            if($reog) {
                $c = new \app\custom\Wdt($aid, $bid);
                $data['id'] = $refund_id;
                $c->orderRefund($order, $data, $reog);
            }
        }
        if(getcustom('transfer_order_parent_check')) {
            //订单退款减分销数据统计的单量
            \app\common\Fenxiao::decTransferOrderCommissionTongji($aid, $order['mid'], $order['id'], 2);
        }
        \app\common\Order::order_close_done($aid,$orderid,'shop');
    }

    //处理商城订单已支付状态下直接退款
    public static function dealShopOrderRefund($order,$reason = ''){
        if(getcustom('douyin_groupbuy') || getcustom('supply_zhenxin') || getcustom('shop_giveorder') || getcustom('supply_yongsheng')){
            if(!$order || $order['status']!=1) return ['status'=>0,'msg'=>'状态不符'];
            $countrefund = 0+Db::name('shop_refund_order')->where('orderid',$order['id'])->where('aid',$order['aid'])->whereIn('refund_status',[1,4])->count('id');
            if($countrefund) return ['status'=>0,'msg'=>'有退款申请'];

            $aid = $order['aid'];
            $bid = $order['bid'];
            $orderid = $order['id'];
            $refund_money = $order['totalprice'] - $order['refund_money'];

            try {
                Db::startTrans();
                $data = [
                    'aid' => $order['aid'],
                    'bid' => $order['bid'],
                    'mdid'=> $order['mdid']??0,
                    'mid' => $order['mid'],
                    'orderid'  => $order['id'],
                    'ordernum' => $order['ordernum'],
                    'refund_type' => 'refund',
                    'refund_ordernum' => '' . date('ymdHis') . rand(100000, 999999),
                    'refund_money' => $refund_money,
                    'refund_reason'=> $reason ,
                    'refund_pics' => '',
                    'createtime'  => time(),
                    'refund_time' => time(),
                    'refund_status' => 2,
                    'platform' => '',
                ];
                if(getcustom('pay_money_combine')){
                    //处理余额组合支付退款
                    $res = \app\custom\OrderCustom::deal_refund_combine($order,$refund_money);
                    if($res['status'] == 1){
                        $data['refund_combine_money']  = $res['refund_combine_money'];//退余额部分
                        $data['refund_combine_wxpay']  = $res['refund_combine_wxpay'];//退微信部分
                        $data['refund_combine_alipay'] = $res['refund_combine_alipay'];//退支付宝部分
                    }else{
                        return $res;
                    }
                }
                if(getcustom('erp_wangdiantong')) {
                    $data['wdt_status'] = $order['wdt_status'];
                }
                $refund_id = Db::name('shop_refund_order')->insertGetId($data);
                if ($order['fromwxvideo'] == 1) {
                    \app\common\Wxvideo::aftersaleadd($order['id'], $refund_id);
                }
                if($data['refund_money'] > 0) {
                    $is_refund = 1;
                    if(getcustom('shop_product_fenqi_pay')){
                        if($order['is_fenqi'] == 1){
                            $rs = \app\common\Order::fenqi_refund($order,$data['refund_money'], $reason);
                            if($rs['status']==0){
                                return ['status'=>0,'msg'=>$rs['msg']];
                            }
                            $is_refund = 0;
                        }
                    }
                    if($is_refund){
                        $params = [];
                        $params['refund_order'] = $data;
                        if(getcustom('pay_money_combine')){
                            //如果是组合支付，退款需要判断余额、微信、支付宝退款部分
                            if($order['combine_money']>0 && ($order['combine_wxpay']>0 || $order['combine_alipay']>0)){
                                //refund_combine 1 走shop_refund_order 退款;2 走shop_order 退款
                                $params['refund_combine'] = 1;
                            }
                        }
                        $rs = \app\common\Order::refund($order, $data['refund_money'], $reason,$params);
                        if ($rs['status'] == 0) {
                            if($order['balance_price'] > 0){
                                $order2 = $order;
                                $order2['totalprice']= $order2['totalprice'] - $order2['balance_price'];
                                $order2['ordernum']  = $order2['ordernum'].'_0';
                                $rs = \app\common\Order::refund($order2,$data['refund_money'],$reason);
                                if($rs['status']==0){
                                    Db::name('shop_refund_order')->where('id', $refund_id)->delete();
                                    return ['status'=>0,'msg'=>$rs['msg']];
                                }
                            }else{
                                Db::name('shop_refund_order')->where('id', $refund_id)->delete();
                                return ['status'=>0,'msg'=>$rs['msg']];
                            }
                        }
                    }
                    
                }
                $prolist = Db::name('shop_order_goods')->where('orderid', $orderid)->select()->toArray();
                foreach ($prolist as $item) {
                    $refund_num2   = $item['num']- $item['refund_num'];
                    $refund_money2 = $refund_num2 * $item['real_totalprice'] / $item['num'];
                    $od = [
                        'aid' => $order['aid'],
                        'bid' => $order['bid'],
                        'mid' => $order['mid'],
                        'orderid'  => $order['id'],
                        'ordernum' => $order['ordernum'],
                        'refund_orderid'  => $refund_id,
                        'refund_ordernum' => $data['refund_ordernum'],
                        'refund_num'   => $refund_num2,
                        'refund_money' => $refund_money2 ,
                        'ogid'  => $item['id'],
                        'proid' => $item['proid'],
                        'name'  => $item['name'],
                        'pic'   => $item['pic'],
                        'procode'=> $item['procode'],
                        'ggid'   => $item['ggid'],
                        'ggname' => $item['ggname'],
                        'cid'    => $item['cid'],
                        'cost_price' => $item['cost_price'],
                        'sell_price' => $item['sell_price'],
                        'createtime' => time()
                    ];
                    if(getcustom('erp_wangdiantong')) {
                        $od['wdt_status'] = $item['wdt_status'];
                    }
                    Db::name('shop_refund_order_goods')->insertGetId($od);
                    Db::name('shop_order_goods')->where('aid', $aid)->where('id', $item['id'])->inc('refund_num', $refund_num2)->update();


                    Db::name('shop_guige')->where('aid', $aid)->where('id', $item['ggid'])->update(['stock' => Db::raw("stock+" . $refund_num2), 'sales' => Db::raw("sales-" . $refund_num2)]);
                    Db::name('shop_product')->where('aid', $aid)->where('id', $item['proid'])->update(['stock' => Db::raw("stock+" . $refund_num2), 'sales' => Db::raw("sales-" . $refund_num2)]);

                    Db::name('shop_order')->where('id', $order['id'])->where('aid', $aid)->update(['status' => 4, 'refund_status' => 2, 'refund_money' =>$order['totalprice']]);
                    Db::name('shop_order_goods')->where('orderid', $order['id'])->where('aid', $aid)->update(['status' => 4]);

                    if (getcustom('guige_split')) {
                        \app\model\ShopProduct::addlinkstock($item['proid'], $item['ggid'], $refund_num2);
                    }
                    if(getcustom('ciruikang_fenxiao')){
                        //是否开启了商城商品需上级购买足量
                        $og = $item;
                        if($og){
                            $deal_ogstock2 = \app\custom\CiruikangCustom::deal_ogstock2($order,$og,$refund_num2,'下级订单退款');
                        }
                    }

                    if(getcustom('consumer_value_add',$aid)){
                        $goods_info =  Db::name('shop_order_goods')->where('aid', $aid)->where('id', $item['id'])->find();
                        //扣除绿色积分
                        if($goods_info['give_green_score2'] > 0){
                            \app\common\Member::addgreenscore($aid,$order['mid'],-bcmul($goods_info['give_green_score2'],$refund_num2,2),'订单退款扣除'.t('绿色积分'),'shop_order',$orderid);
                            \app\common\Member::addmaximum($aid,$order['mid'],-$goods_info['give_maximum'],'订单退款扣除','shop_order',$orderid);
                        }
                        //扣除奖金池
                        if($goods_info['give_bonus_pool2'] > 0){
                            \app\common\Member::addbonuspool($aid,$order['mid'],-bcmul($goods_info['give_bonus_pool2'],$refund_num2,2),'订单退款扣除'.t('奖金池'),'shop_order',$orderid,0,-bcmul($goods_info['give_green_score2'],$refund_num2,2));
                        }
                        if(getcustom('green_score_reserves',$aid)){
                            //订单进入预备金
                            if($order['green_score_reserves2']>0){
                                \app\custom\GreenScore::addgreenscorereserves($aid,$order['mid'],-$goods_info['green_score_reserves2'],'订单退款扣除'.t('预备金'),'shop_order',$orderid);
                            }
                        }
                    }

                    //统一处理退还佣金、积分、优惠券、抵扣值等返还操作
                    self::dealShoprefundReturn($aid,$order);
                }

                //统一处理处理商城订单退款后操作
                self::dealShoprefundAfter($aid,$bid,$orderid,$order,$refund_id,$prolist);

                Db::commit();
            } catch (\Exception $e) {
                Log::write([
                    'file' => __FILE__ . ' L' . __LINE__,
                    'function' => __FUNCTION__,
                    'error' => $e->getMessage(),
                ]);
                Db::rollback();
                return ['status'=>0,'msg'=>'提交失败,请重试'];
            }

            $refund_money = $data['refund_money'];
            //退款成功通知
            $tmplcontent = [];
            $tmplcontent['first'] = '您的订单已经完成退款，¥'.$refund_money.'已经退回您的付款账户，请留意查收。';
            $tmplcontent['remark'] = $reason.'，请点击查看详情~';
            $tmplcontent['orderProductPrice'] = $refund_money.'元';
            $tmplcontent['orderProductName'] = $order['title'];
            $tmplcontent['orderName'] = $order['ordernum'];
            $tmplcontentNew = [];
            $tmplcontentNew['character_string1'] = $order['ordernum'];//订单编号
            $tmplcontentNew['thing2'] = $order['title'];//商品名称
            $tmplcontentNew['amount3'] = $refund_money;//退款金额
            \app\common\Wechat::sendtmpl($aid,$order['mid'],'tmpl_tuisuccess',$tmplcontent,m_url('pages/my/usercenter',$aid),$tmplcontentNew);
            //订阅消息
            $tmplcontent = [];
            $tmplcontent['amount6'] = $refund_money;
            $tmplcontent['thing3'] = $order['title'];
            $tmplcontent['character_string2'] = $order['ordernum'];

            $tmplcontentnew = [];
            $tmplcontentnew['amount3'] = $refund_money;
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
            \app\common\Sms::send($aid,$tel,'tmpl_tuisuccess',['ordernum'=>$order['ordernum'],'money'=>$refund_money]);
            return ['status'=>1,'msg'=>'已退款成功'];
        }
    }

    // 即拼超级卖货
    public static function jipin($aid,$order,$status){
        
        $time = time();
        $shop_order_goods = Db::name('shop_order_goods')->where('aid',$aid)->where('orderid',$order['id'])->select();
        // dump($shop_order_goods);
        if($status == 1){
            $tuanstatus = 0;
        }elseif($status == 3){
            $tuanstatus = 1;
        }else{
            return;
        }

        foreach ($shop_order_goods as $shop_order_good) {
            $proid = $shop_order_good['proid'];
            $where = [];
            $where[] = ['aid', '=', $aid];
            $where[] = ['status', '=', 1];
            $where[] = ['tuanstatus', '=', $tuanstatus];
            $where[] = ['starttime', '<', $time];
            $where[] = ['endtime', '>', $time];
            $where[] = Db::raw("find_in_set('".$proid."',productids)");
            $jipin = Db::name('jipin')->where($where)->order('id desc')->find();
            if(!$jipin){
                continue;
            }
            // 最新团号
            $newtunnum = Db::name('jipin_log')->where('aid',$aid)->where('jipinid',$jipin['id'])->max('tuannum')?:0;
            // dump($jipin);
            // 参团
            // 推荐网找
            $ztmid = 0;
            $path = Db::name('member')->where('aid',$aid)->where('id',$order['mid'])->value('path');
            $tuanid = 0;
            if($path){
                $path = explode(',', $path);
                $path = array_reverse($path);
                foreach ($path as $key => $pmid) {
                    $log = Db::name('jipin_log')->where('aid',$aid)->where('jipinid',$jipin['id'])->where('status','0')->where('find_in_set('.$pmid.',mids)')->find();
                    if($log){
                        $tuanid = $log['id'];
                        if( $key == 0){//直推奖
                            $ztmid = $pmid;
                            // 增加一个贡献值
                        }
                        break;
                    }
                }
            }
            if(!$tuanid){
                // 最早开始的团
                $log = Db::name('jipin_log')->where('aid',$aid)->where('jipinid',$jipin['id'])->where('status','0')->order('id asc')->find();
                $tuanid = $log['id'];
            }
            if(!$tuanid){
                $newtunnum++;
                // mid->(mid num createtime orderid)
                $info = [
                    [
                        'mid'=>$order['mid'],
                        'createtime'=>$time,
                        'orderid'=>$order['id'],
                        'num'=>0
                    ]
                ];
                // 建团 第一个团
                $log = [];
                $log['aid'] = $aid;
                $log['bid'] = $order['bid'];
                $log['jipinid'] = $jipin['id'];
                $log['tuannum'] = $newtunnum;
                $log['mids'] = $order['mid'];
                $log['score'] = $jipin['score'];
                $log['commission'] = $jipin['commission'];
                $log['upcommission'] = $jipin['upcommission'];
                $log['info'] = json_encode($info);
                // $log['orderids'] = $order['id'];
                $log['createtime'] = $time;
                $tuanid = Db::name('jipin_log')->insertGetId($log);
            }else{
                //进团
                if($tuanid){
                    $update = [];
                    $update['mids'] = $log['mids'].','.$order['mid'];
                    
                    // 成团
                    $mids = explode(',', $log['mids']);
                    $mnum = count($mids);
                    if($mnum >= 6){
                        $update['status'] = 1;
                        $update['endtime'] = $time;

                    }
                    $next = [
                        'mid'=>$order['mid'],
                        'createtime'=>$time,
                        'orderid'=>$order['id'],
                        'num'=>0,
                    ];
                    $loginfo = json_decode($log['info'],true);

                    //直推奖
                    if($ztmid){
                        // 增加贡献值
                        foreach ($loginfo as $k => $v) {
                            if($v['mid'] == $ztmid && $v['num'] < 2){
                                $loginfo[$k]['num']++;
                            }
                        }
                        \app\common\Member::addcommission($aid, $ztmid, $order['mid'], $log['upcommission'], '超级卖货-直推奖，活动ID'.$jipin['id'].'，订单'.$order['ordernum']);
                    }

                    array_push($loginfo,$next);
                    $update['info'] = json_encode($loginfo);
                    Db::name('jipin_log')->where('aid',$aid)->where('status','0')->where('id',$tuanid)->update($update);

                    //成团
                    if($mnum >= 6){
                        $is_futou = 0;
                         // 发成团奖
                        //贡献值跟时间排序
                        usort($loginfo, function($a, $b) {  
                            // 先比较 num 从大到小  
                            if ($a['num'] === $b['num']) {  
                                // 如果 num 相同，再比较 createtime 从大到小  
                                return $a['createtime'] <=> $b['createtime'];  
                            }  
                            return $b['num'] <=> $a['num'];  
                        });  
                        // dump($loginfo);die;
                        $tmid = $loginfo[0]['mid'];
                        if($tmid){
                            Db::name('jipin_log')->where('aid',$aid)->where('id',$tuanid)->update(['tmid'=>$tmid]);
                            // 奖励
                            //自动复投开启
                            if($jipin['isfutou'] == 1){
                                //复投完成之后剩下的佣金发放到佣金账户

                                // 查找复投商品
                                $defaultproid = Db::name('jipin_product_log')->where('aid',$aid)->where('jipinid',$jipin['id'])->where('mid',$tmid)->order('id desc')->value('defaultproid');
                                if(!$defaultproid){
                                    $defaultproid = $jipin['defaultproid'];
                                }

                                $shop_product = Db::name('shop_product')->where('aid',$aid)->where('id',$defaultproid)->find();
                                // dump($shop_product);die;
                                if($shop_product){
                                    $totalprice = $shop_product['sell_price'];
                                }else{
                                    $defaultproid = $shop_order_good['proid'];
                                    $totalprice = $shop_order_good['totalprice'];
                                }

                                if($log['commission'] - $totalprice > 0){
                                    $sendcommission =  $log['commission'] - $totalprice;
                                    \app\common\Member::addcommission($aid, $tmid, 0, $sendcommission,'超级卖货-成团奖,活动ID:'.$jipin['id'].',团ID:'.$tuanid);

                                    // 复投
                                    // 创建订单 defaultproid
                                    $is_futou = 1;

                                }else{
                                    //复投不足
                                    if($log['commission'] > 0){
                                        \app\common\Member::addcommission($aid, $tmid, 0, $log['commission'],'超级卖货-成团奖,活动ID:'.$jipin['id'].',团ID:'.$tuanid);
                                    }
                                }
                            }else{
                                // 不复投
                                if($log['commission'] > 0){
                                    \app\common\Member::addcommission($aid, $tmid, 0, $log['commission'],'超级卖货-成团奖,活动ID:'.$jipin['id'].',团ID:'.$tuanid);
                                }
                            }
                            if($log['score'] > 0){
                                \app\common\Member::addscore($aid,$tmid,$log['score'],'超级卖货-成团奖,活动ID:'.$jipin['id'].',团ID:'.$tuanid);
                            }
                        }

                        // 拆团 除了$tmid 最先来的拆成新团
                        $loginfo = array_slice($loginfo, 1);
                        usort($loginfo, function($a, $b) {  
                            // 只按 createtime 从小到到排序  
                            return $a['createtime'] <=> $b['createtime'];  
                        });  
                        // 选人 一号团长
                        $tmid1 = $loginfo[0]['mid'];
                        // 二号团长
                        $tmid2 = $loginfo[1]['mid'];

                        // 剩下强弱排序
                        $tuan1info = $loginfo[0];
                        $tuan2info = $loginfo[1];
                        unset($loginfo[0]);
                        unset($loginfo[1]);
                        //贡献值跟时间排序
                        usort($loginfo, function($a, $b) {  
                            // 先比较 num 从大到小  
                            if ($a['num'] === $b['num']) {  
                                // 如果 num 相同，再比较 createtime 从大到小  
                                return $a['createtime'] <=> $b['createtime'];  
                            }  
                            return $b['num'] <=> $a['num'];  
                        }); 

                        $tuanmids1 = $tmid1.','.$loginfo[0]['mid'].','.$loginfo[3]['mid'];//新团1
                        $tuanmids2 = $tmid2.','.$loginfo[1]['mid'].','.$loginfo[2]['mid'];//新团2

                        $new1info = [
                            $tuan1info,
                            $loginfo[0],
                            $loginfo[3]
                        ];
                        $info1 = self::gongxiannum($aid,$tuanmids1,$new1info,$jipin);
                        // dump($info1);die;
                        $logarr = [];
                        // 建团 第一个团
                        $log = [];
                        $log['aid'] = $aid;
                        $log['bid'] = $order['bid'];
                        $log['jipinid'] = $jipin['id'];
                        $log['tuannum'] = $newtunnum+1;
                        $log['mids'] = $tuanmids1;
                        $log['score'] = $jipin['score'];
                        $log['commission'] = $jipin['commission'];
                        $log['upcommission'] = $jipin['upcommission'];
                        $log['info'] = json_encode($info1);
                        $log['createtime'] = $time;
                        $logarr[] = $log;
                       
                        $new2info = [
                            $tuan2info,
                            $loginfo[1],
                            $loginfo[2]
                        ];
                        $info2 = self::gongxiannum($aid,$tuanmids2,$new2info,$jipin);
                         // 建团 第二个团
                        $log = [];
                        $log['aid'] = $aid;
                        $log['bid'] = $order['bid'];
                        $log['jipinid'] = $jipin['id'];
                        $log['tuannum'] = $newtunnum+2;
                        $log['mids'] = $tuanmids2;
                        $log['score'] = $jipin['score'];
                        $log['commission'] = $jipin['commission'];
                        $log['upcommission'] = $jipin['upcommission'];
                        $log['info'] = json_encode($info2);
                        $log['createtime'] = $time;
                        $logarr[] = $log;
                        Db::name('jipin_log')->insertAll($logarr);
                       
                        if($is_futou){
                            $rs = self::autoOrder($aid,$tmid,$defaultproid,1,$status,$tuanid);
                            if($rs['status']){
                                self::jipin($aid,$rs['order'],$status);
                            }
                        }

                    }
                }
            }
        }
    }
    // 计算贡献值
    public static function gongxiannum($aid,$mids,$info,$jipin=[]){
        // 重新计算新团贡献值 团1
        $mids = explode(',', $mids);
        $mids = array_reverse($mids);

        $num1 = 0;
        $num2 = 0;

        // 拆团新团贡献值清零
        $keep_gongxian = 0;
        if(getcustom('yx_collage_jipin2_gongxian',$aid)){
            $keep_gongxian = $jipin['keep_gongxian']??0;
        }
        if($keep_gongxian){
            foreach ($mids as $k => $mid) {
                $pid = Db::name('member')->where('aid',$aid)->where('id',$mid)->value('pid');
                if($k == 0){
                    if($pid == $mids[2]){
                        $num1++;
                    }
                    if($pid == $mids[1]){
                        $num2++;
                    }
                    
                }elseif($k == 1){
                    if($pid == $mids[2]){
                        $num1++;
                    }
                }
            }
        }
        
        $info[0]['num'] = $num1;
        $info[1]['num'] = $num2;
        return $info;
    }
    // 自动复投
    public static function autoOrder($aid,$mid,$pro_id,$num=1,$status = 1,$tuanid=0){
        // 收货地址 收货方式 按照上一个团的订单的收货地址
        $info = Db::name('jipin_log')->where('aid',$aid)->where('id',$tuanid)->value('info');
        $info = json_decode($info,true);
        $orderid = 0;
        foreach ($info as $k => $v) {
            if($v['mid'] = $mid){
                $orderid = $v['orderid'];
            }
        }
        $shop_order = Db::name('shop_order')->where('aid',$aid)->where('id',$orderid)->find();


        $product = Db::name('shop_product')->where('id', $pro_id)->find();
        $guige = Db::name('shop_guige')->where('proid', $pro_id)->find();
        $sysset = Db::name('admin_set')->where('aid', $aid)->find();

        $ordernum = \app\common\Common::generateOrderNo($aid);
        $orderdata = [];
        $orderdata['aid'] = $aid;
        $orderdata['mid'] = $mid;
        $orderdata['bid'] = $product['bid'] ?: 0;
        $orderdata['ordernum'] = $ordernum;
        $orderdata['title'] = $product['name'];

        // $address = Db::name('member_address')->where('mid', $mid)->order('isdefault desc')->find();
        $orderdata['linkman'] = $shop_order['linkman']??'';
        $orderdata['tel'] = $shop_order['tel']??'';
        $orderdata['area'] = $shop_order['area']??'';
        $orderdata['area2'] = $shop_order['area2']??'';
        $orderdata['address'] = $shop_order['address']??'';
        $orderdata['totalprice'] = $product['sell_price'] * $num;
        $orderdata['product_price'] = $product['sell_price'];
        $orderdata['leveldk_money'] = 0;  //会员折扣
        $orderdata['scoredk_money'] = 0;    //积分抵扣
        $orderdata['scoredkscore'] = 0;    //抵扣掉的积分
        $orderdata['freight_price'] = 0; //运费
        $orderdata['message'] = '';
        $orderdata['freight_text'] = $shop_order['freight_text']??'';
        $orderdata['freight_id'] = $shop_order['freight_id']??'';
        $orderdata['freight_type'] = $product['freighttype']?:1;
        $orderdata['mdid'] = 0;
        $orderdata['platform'] = $shop_order['platform']??'';
        $orderdata['hexiao_code'] = random(16);
        $orderdata['hexiao_qr'] = createqrcode(m_url('admin/hexiao/hexiao?type=shop&co=' . $orderdata['hexiao_code']));
        $orderdata['status'] = $status;
        $orderdata['paytype'] = t('佣金').'支付';
        $orderdata['createtime'] = time();
        $orderdata['paytime'] = time();
        $remark = '超级卖货自动复投';

        $orderdata['remark'] = $remark;
        $orderdata['givescore'] = 0;
        $orderdata['givescore2'] = 0;
        $orderid = Db::name('shop_order')->insertGetId($orderdata);
        $ogdata = [];
        $ogdata['aid'] = $aid;
        $ogdata['bid'] = $product['bid'];
        $ogdata['mid'] = $mid;
        $ogdata['orderid'] = $orderid;
        $ogdata['ordernum'] = $orderdata['ordernum'];
        $ogdata['proid'] = $product['id'];
        $ogdata['name'] = $product['name'];
        $ogdata['pic'] = $guige['pic'] ? $guige['pic'] : $product['pic'];
        $ogdata['procode'] = $product['procode'];
        $ogdata['barcode'] = $product['barcode'];
        $ogdata['ggid'] = $guige['id'];
        $ogdata['ggname'] = $guige['name'];
        $ogdata['cid'] = $product['cid'];
        $ogdata['num'] = $num;
        $ogdata['cost_price'] = $guige['cost_price'];
        $ogdata['sell_price'] = $guige['sell_price'];
        $ogdata['totalprice'] = $num * $guige['sell_price'];
        $ogdata['real_totalprice'] = $ogdata['totalprice'];
        $ogdata['status'] = $status;
        $ogdata['createtime'] = time();
        if ($product['fenhongset'] == 0) { //不参与分红
            $ogdata['isfenhong'] = 2;
        }
        $ogid = Db::name('shop_order_goods')->insertGetId($ogdata);

        //分销数据
        //计算佣金的商品金额
        $commission_totalprice = $ogdata['totalprice'];
        if($sysset['fxjiesuantype']==1){ //按成交价格
            $commission_totalprice = $ogdata['totalprice'];
        }
        if($sysset['fxjiesuantype']==2){ //按销售利润
            $commission_totalprice = $ogdata['totalprice'] - $guige['cost_price'] * $num;
        }
        if($commission_totalprice < 0) $commission_totalprice = 0;
        $istc1 = 0; //设置了按单固定提成时 只将佣金计算到第一个商品里
        $istc2 = 0;
        $istc3 = 0;
        $isfg  = 0;
        $member = Db::name('member')->where('id',$mid)->find();
        if(!getcustom('fenxiao_manage',$aid)){
            $sysset['fenxiao_manage_status'] = 0;
        }
        if($sysset['fenxiao_manage_status']){
            $commission_data = \app\common\Fenxiao::fenxiao_jicha($sysset,$member,$product,$num,$commission_totalprice);
        }else{
            $commission_data = \app\common\Fenxiao::fenxiao($sysset,$member,$product,$num,$commission_totalprice,$isfg,$istc1,$istc2,$istc3);
        }
        $ogupdate = [];
        $ogupdate['parent1'] = $commission_data['parent1']??0;
        $ogupdate['parent2'] = $commission_data['parent2']??0;
        $ogupdate['parent3'] = $commission_data['parent3']??0;
        $ogupdate['parent4'] = $commission_data['parent4']??0;
        $ogupdate['parent1commission'] = $commission_data['parent1commission']??0;
        $ogupdate['parent2commission'] = $commission_data['parent2commission']??0;
        $ogupdate['parent3commission'] = $commission_data['parent3commission']??0;
        $ogupdate['parent4commission'] = $commission_data['parent4commission']??0;
        $ogupdate['parent1score'] = $commission_data['parent1score']??0;
        $ogupdate['parent2score'] = $commission_data['parent2score']??0;
        $ogupdate['parent3score'] = $commission_data['parent3score']??0;

        //20250626新增 平级奖独立记录
        if(getcustom('commission_parent_pj')) {
            $ogupdate['parent_pj1'] = $commission_data['parent_pj1'] ?? 0;
            $ogupdate['parent_pj2'] = $commission_data['parent_pj2'] ?? 0;
            $ogupdate['parent_pj3'] = $commission_data['parent_pj3'] ?? 0;
            $ogupdate['parent1commission_pj'] = $commission_data['parent1commission_pj'] ?? 0;
            $ogupdate['parent2commission_pj'] = $commission_data['parent2commission_pj'] ?? 0;
            $ogupdate['parent3commission_pj'] = $commission_data['parent3commission_pj'] ?? 0;
        }
        $istc1 = $commission_data['istc1']??0;
        $istc2 = $commission_data['istc2']??0;
        $istc3 = $commission_data['istc3']??0;
        if($ogupdate){
            Db::name('shop_order_goods')->where('id',$ogid)->update($ogupdate);
        }

        if($product['commissionset']!=4){
            if($ogupdate['parent1'] && ($ogupdate['parent1commission']>0 || $ogupdate['parent1score']>0)){
                $data_c = ['aid'=>$aid,'mid'=>$ogupdate['parent1'],'frommid'=>$mid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent1commission'],'score'=>$ogupdate['parent1score'],'remark'=>t('下级').'购买商品奖励','createtime'=>time()];
                Db::name('member_commission_record')->insert($data_c);
            }
            if($ogupdate['parent2'] && ($ogupdate['parent2commission']>0 || $ogupdate['parent2score']>0)){
                $data_c = ['aid'=>$aid,'mid'=>$ogupdate['parent2'],'frommid'=>$mid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent2commission'],'score'=>$ogupdate['parent2score'],'remark'=>t('下二级').'购买商品奖励','createtime'=>time()];
                Db::name('member_commission_record')->insert($data_c);
            }
            if($ogupdate['parent3'] && ($ogupdate['parent3commission']>0 || $ogupdate['parent3score']>0)){
                $data_c = ['aid'=>$aid,'mid'=>$ogupdate['parent3'],'frommid'=>$mid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent3commission'],'score'=>$ogupdate['parent3score'],'remark'=>t('下三级').'购买商品奖励','createtime'=>time()];
                Db::name('member_commission_record')->insert($data_c);
            }
            if($ogupdate['parent4'] && ($ogupdate['parent4commission']>0)){
                $remark = '持续推荐奖励';
                if(getcustom('commission_parent_pj_stop',$aid)){
                    $remark = '平级奖';
                }
                Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$ogupdate['parent4'],'frommid'=>$mid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent4commission'],'score'=>0,'remark'=>$remark,'createtime'=>time()]);
            }
            if(getcustom('commission_parent_pj')) {
                if ($ogupdate['parent_pj1'] && ($ogupdate['parent1commission_pj'] > 0)) {
                    $remark = '平级一级奖励';
                    $data_c = ['aid' => $aid, 'mid' => $ogupdate['parent_pj1'], 'frommid' => $mid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent1commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                    Db::name('member_commission_record')->insert($data_c);
                }
                if ($ogupdate['parent_pj2'] && ($ogupdate['parent2commission_pj'] > 0)) {
                    $remark = '平级二级奖励';
                    $data_c = ['aid' => $aid, 'mid' => $ogupdate['parent_pj2'], 'frommid' => $mid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent2commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                    Db::name('member_commission_record')->insert($data_c);
                }
                if ($ogupdate['parent_pj3'] && ($ogupdate['parent3commission_pj'] > 0)) {
                    $remark = '平级三级奖励';
                    $data_c = ['aid' => $aid, 'mid' => $ogupdate['parent_pj3'], 'frommid' => $mid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent3commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                    Db::name('member_commission_record')->insert($data_c);
                }
            }
        }

        Db::name('shop_guige')->where('aid', $aid)->where('id', $guige['id'])->update(['stock' => Db::raw("stock-$num"), 'sales' => Db::raw("sales+$num")]);
        Db::name('shop_product')->where('aid', $aid)->where('id', $product['id'])->update(['stock' => Db::raw("stock-$num"), 'sales' => Db::raw("sales+$num")]);
        $orderdata['id'] = $orderid;
        return ['status' => true, 'oid' => $orderid,'order'=>$orderdata];
    
    }

    /**
     * 即拼
     * 需求文档：https://doc.weixin.qq.com/doc/w3_AT4AYwbFACwFKMCUPXlRYOs2zqn0t?scode=AHMAHgcfAA0ERjsaqiAeYAOQYKALU
     * @author: liud
     * @time: 2025/2/10 下午5:16
     */
    public static function collageJipinOrder($aid,$orderid,$hdid=0){
        $up_change_pid = getcustom('up_change_pid',$aid);
        if(getcustom('yx_collage_jipin',$aid)){
            if(!$orderinfo = db('shop_order')->where('aid',$aid)->where('id',$orderid)->where('status','in',[1,3])->find()){
                return;
            }

            if((($orderinfo['is_jipin_auto'] == 1) || (strpos($orderinfo['remark'], '即拼出局后自动生成新订单') !== false)) && !$hdid){
                return;
            }

            if(!$order_goods = db('shop_order_goods')->where('aid',$aid)->where('orderid',$orderid)->select()->toArray()){
                return;
            }

            $ogarr = [];
            foreach ($order_goods as $ak => $av){
                for ($i = 1; $i <= $av['num'];$i++){
                    $ogarr[] = $av;
                }
            }

            $out_num = $dsk = 0;
            foreach ($ogarr as $ok => $order){

                //订单信息
                $mid = $order['mid'];
                $bid = $order['bid'];
                $proid = $order['proid'];
                $cid_arr = explode(',', $order['cid']);

                $clustering_time = [];
                if($order['status'] == 1){
                    $clustering_time[] = ['clustering_time','=',1];
                }else if($order['status'] == 3){
                    $clustering_time[] = ['clustering_time','=',0];
                }else{
                    continue;
                }

                //用户信息
                $minfo = db('member')->where('aid',$aid)->where('bid',$bid)->where('id',$mid)->field('pid,pid_origin')->find();

                if($hdid){
                    $hd_info = db('collage_jipin_set')->where('aid',$aid)->where('bid',$bid)->where('id',$hdid)->where('status',1)->select()->toArray();
                }else{
                    //查询商品所属活动
                    $hd_info = db('collage_jipin_set')->where('aid',$aid)->where('bid',$bid)->where('fwtype',0)->where('status',1)->where($clustering_time)->where('find_in_set('.$proid.',productids)')->select()->toArray();
                    if($cid_arr){
                        //先循环这个商品的每个分类
                        foreach ($cid_arr as $ck => $cv){
                            //查询所有分类活动
                            if($hd_category = db('collage_jipin_set')->where('aid',$aid)->where('bid',$bid)->where('fwtype',1)->where('status',1)->where($clustering_time)->select()->toArray()){
                                foreach ($hd_category as $vk => $vv){
                                    $categoryids = explode(',', $vv['categoryids']);
                                    $clist = Db::name('shop_category')->where('pid', 'in', $categoryids)->select()->toArray();
                                    foreach ($clist as $kc => $vc) {
                                        $categoryids[] = $vc['id'];
                                        $cate2 = Db::name('shop_category')->where('pid', $vc['id'])->find();
                                        $categoryids[] = $cate2['id'];
                                    }
                                    if(in_array($cv,$categoryids)){
                                        $hd_info[] = $vv;
                                    }
                                }
                            }
                        }
                    }
                }

                if(empty($hd_info)){
                    return;
                }

                //多维数组去重
                $hd_info = array_unique_map($hd_info);

                //循环活动
                foreach ($hd_info as $k => $v) {

                    //如果是第一个直接写入
                    if(!$max_num = Db::name('collage_jipin_order')->where('aid', $aid)->where('bid',$bid)->where('collage_jipin_id',$v['id'])->order('num desc')->value('num')){
                        $data = [
                            'aid' => $aid,
                            'bid' => $bid,
                            'mid' => $mid,
                            'collage_jipin_id' => $v['id'],
                            'collage_jipin_name' => $v['name'],
                            'orderid' => $order['orderid'],
                            'order_goods_id' => $order['id'],
                            'ordernum' => $order['ordernum'],
                            'proid' => $order['proid'],
                            'num' => 1,
                            'createtime' => time(),
                        ];

                        Db::name('collage_jipin_order')->insertGetId($data);

                        continue;
                    }

                    //推荐人
                    $pid = 0;

                    if(!$up_change_pid){
                        $v['num_recommend'] = 0;
                    }

                    if($v['num_recommend'] == 1){
                        //优先原上级
                        if($minfo['pid_origin'] > 0){
                            $pid = $minfo['pid_origin'];
                        }elseif ($minfo['pid'] > 0){
                            $pid = $minfo['pid'];
                        }
                    }elseif ($v['num_recommend'] == 0){
                        //优先现上级
                        if($minfo['pid'] > 0){
                            $pid = $minfo['pid'];
                        }elseif ($minfo['pid_origin'] > 0){
                            $pid = $minfo['pid_origin'];
                        }
                    }

                    if($out_num){
                        //如果有出局开启的新团,则剩下的序号放到新团下面
                        $p_num = Db::name('collage_jipin_order')->where('aid', $aid)->where('bid',$bid)->where('collage_jipin_id',$v['id'])->where('num',$out_num)->find();

                        if($p_num){
                            if( $p_num['down_left'] > 0 && $p_num['down_right'] > 0){
                                if(!$p_num = Db::name('collage_jipin_order')->where('aid', $aid)->where('bid',$bid)->where('collage_jipin_id',$v['id'])->where('find_in_set('.$out_num.',num_path)')->where('down_left|down_right',0)->where('orderid',$order['orderid'])->order('num asc')->find()){
                                    $p_num = Db::name('collage_jipin_order')->where('aid', $aid)->where('bid',$bid)->where('collage_jipin_id',$v['id'])->where('down_left|down_right',0)->order('num asc')->find();
                                }
                            }
                        }else{
                            $p_num = Db::name('collage_jipin_order')->where('aid', $aid)->where('bid',$bid)->where('collage_jipin_id',$v['id'])->where('down_left|down_right',0)->order('num asc')->find();
                        }
                    }else{
                        if($dsk == 0){
                            //如果有推荐人
                            if($pid > 0){
                                if($p_num = Db::name('collage_jipin_order')->where('aid', $aid)->where('bid',$bid)->where('collage_jipin_id',$v['id'])->where('mid',$pid)->where('status',0)->order('num asc')->find()){
                                    $down_left = $p_num['down_left'];
                                    $down_right = $p_num['down_right'];
                                   if($down_left > 0 && $down_right > 0){
                                       if(!$p_num = Db::name('collage_jipin_order')->where('aid', $aid)->where('bid',$bid)->where('collage_jipin_id',$v['id'])->where('num',$down_left)->where('down_left|down_right',0)->order('num asc')->find()){
                                           if(!$p_num = Db::name('collage_jipin_order')->where('aid', $aid)->where('bid',$bid)->where('collage_jipin_id',$v['id'])->where('num',$down_right)->where('down_left|down_right',0)->order('num asc')->find()){
                                               $p_num = Db::name('collage_jipin_order')->where('aid', $aid)->where('bid',$bid)->where('collage_jipin_id',$v['id'])->where('down_left|down_right',0)->order('num asc')->find();
                                           }
                                       }
                                   }
                                }else{
                                    $p_num = Db::name('collage_jipin_order')->where('aid', $aid)->where('bid',$bid)->where('collage_jipin_id',$v['id'])->where('down_left|down_right',0)->order('num asc')->find();
                                }
                            }else{
                                $p_num = Db::name('collage_jipin_order')->where('aid', $aid)->where('bid',$bid)->where('collage_jipin_id',$v['id'])->where('down_left|down_right',0)->order('num asc')->find();
                            }
                        }else{
                            if(!$p_num = Db::name('collage_jipin_order')->where('aid', $aid)->where('bid',$bid)->where('collage_jipin_id',$v['id'])->where('down_left|down_right',0)->where('orderid',$order['orderid'])->order('num asc')->find()){
                                $p_num = Db::name('collage_jipin_order')->where('aid', $aid)->where('bid',$bid)->where('collage_jipin_id',$v['id'])->where('down_left|down_right',0)->order('num asc')->find();
                            }
                        }
                    }
//                    var_dump(11111);
//                    var_dump('hd：'.$v['id']);
//                    var_dump('订单id：'.$order['id']);
//                    var_dump($p_num);
                    if($p_num){
                        $data = [
                            'aid' => $aid,
                            'bid' => $bid,
                            'mid' => $mid,
                            'collage_jipin_id' => $v['id'],
                            'collage_jipin_name' => $v['name'],
                            'orderid' => $order['orderid'],
                            'order_goods_id' => $order['id'],
                            'ordernum' => $order['ordernum'],
                            'proid' => $order['proid'],
                            'num' => $max_num + 1,
                            'pnum' => $p_num['num'],
                            'pnum_mid' => $p_num['mid'],
                            'num_path' => ($p_num['num_path']) ? $p_num['num_path'].','.$p_num['num'] : $p_num['num'],
                            'createtime' => time(),
                        ];

                        Db::name('collage_jipin_order')->insertGetId($data);

                        //更改父级左右序号(放到父级的左还是右)
                        $down_name = '';
                        if($p_num['down_left'] == 0){
                            $down_name = 'down_left';
                        }else if($p_num['down_right'] == 0){
                            $down_name = 'down_right';
                        }
                        if($down_name){
                            Db::name('collage_jipin_order')->where('aid', $aid)->where('id', $p_num['id'])->update([$down_name => $data['num']]);
                        }
                        if(Db::name('collage_jipin_order')->where('aid', $aid)->where('id', $p_num['id'])->where('down_left','>',0)->where('down_right','>',0)->find()){
                            $dsk = 1;
                        }

                        //查看团长是否符合成团,团长其实就是上级的上级
                        $p_p_num = Db::name('collage_jipin_order')->where('aid', $aid)->where('bid',$bid)->where('collage_jipin_id',$v['id'])->where('num',$p_num['pnum'])->find();
                        if($p_p_num && $p_p_num['down_left'] > 0 && $p_p_num['down_right'] > 0){
                            $pl = Db::name('collage_jipin_order')->where('aid', $aid)->where('bid',$bid)->where('collage_jipin_id',$v['id'])->where('num',$p_p_num['down_left'])->where('down_left','>',0)->where('down_right','>',0)->find();
                            $pr = Db::name('collage_jipin_order')->where('aid', $aid)->where('bid',$bid)->where('collage_jipin_id',$v['id'])->where('num',$p_p_num['down_right'])->where('down_left','>',0)->where('down_right','>',0)->find();

                            if($pl && $pr && $p_p_num['status'] == 0 && $p_p_num['mid'] > 0){
                                //已成团出局操作
                                $out_num = self::collageJipinOut($aid,$bid,$p_p_num,$v['id']);
                            }
                        }
                    }
                }
            }
            return $data['num'] ?? 0;
        }
    }

    /**
     * 成团出局操作
     * @author: liud
     * @time: 2025/2/10 下午5:20
     */
    public static function collageJipinOut($aid,$bid,$p_p_num,$hdid){
        $yx_collage_jipin_optimize = getcustom('yx_collage_jipin_optimize',$aid);
        $yx_collage_jipin_referrer_limit = getcustom('yx_collage_jipin_referrer_limit',$aid);
        if(getcustom('yx_collage_jipin',$aid)){
            //获取活动详情
            if(!$hd_info = db('collage_jipin_set')->where('aid',$aid)->where('bid',$bid)->where('id',$hdid)->where('status',1)->find()){
                return;
            }

            //团长会员信息
            $p_info= Db::name('member')->where('aid',$aid)->where('id',$p_p_num['mid'])->find();

            $isff = true;
            if($yx_collage_jipin_referrer_limit){
                $referrer_limit = true;
                //权限
                $admin_user = Db::name('admin_user')->where('aid',$aid)->where('bid',$bid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
                if($admin_user){
                    if($admin_user['auth_type'] !=1 ){
                        $admin_auth = !empty($admin_user['auth_data'])?json_decode($admin_user['auth_data'],true):[];
                        if(!in_array('ReferrerlimitNum,ReferrerlimitNum',$admin_auth)){
                            $referrer_limit = false;
                        }
                    }
                }

                $referrerlimit_num_levelids_arr = explode(',',$hd_info['referrerlimit_num_levelids']);

                if($referrer_limit && $hd_info['referrerlimit_num'] > 0 && (in_array('-1',$referrerlimit_num_levelids_arr) || in_array($p_info['levelid'],$referrerlimit_num_levelids_arr))){
                    //查询团长推荐人数
                    $ptj_num = Db::name('member')->where('aid',$aid)->where('pid',$p_p_num['mid'])->count();
                    if($ptj_num < $hd_info['referrerlimit_num']){
                        $isff = false;
                    }
                }
            }

            //团长会员信息不存在
            if(!$p_info){
                $isff = false;
            }

            $remark = '即拼成团奖励(活动ID：'.$p_p_num['collage_jipin_id'].';序号：'.$p_p_num['num'].")";
            //奖励佣金
            if($hd_info['commission'] > 0 && $isff){
                \app\common\Member::addcommission($aid,$p_p_num['mid'],0,$hd_info['commission'],$remark,1,'jipin','','',$hd_info['id']);
            }

            //奖励余额
            if($hd_info['money'] > 0 && $isff){
                \app\common\Member::addmoney($aid,$p_p_num['mid'],$hd_info['money'],$remark,'','',$hd_info['id']);
            }

            //奖励积分
            if($hd_info['score'] > 0 && $isff){
                \app\common\Member::addscore($aid,$p_p_num['mid'],$hd_info['score'],$remark,'',$bid);
            }

            //修改状态为已初局
            db('collage_jipin_order')->where('aid',$aid)->where('bid',$bid)->where('collage_jipin_id',$hdid)->where('id',$p_p_num['id'])->update(['status' => 1,'outtime' => time()]);

            //判断成团后是否开新团
            if($hd_info['auto_new'] == 1 && $isff){

                //开新团的活动ID
                if($hd_info['auto_new_hdid'] > 0){
                    $hdid = $hd_info['auto_new_hdid'];
                    //获取新活动信息
                    $hd_info = db('collage_jipin_set')->where('aid',$aid)->where('bid',$bid)->where('id',$hdid)->where('status',1)->find();
                }

                if($hd_info){
                    //最近一次参与活动下单的商品
                    $o_proid = db('collage_jipin_order')->where('aid',$aid)->where('bid',$bid)->where('collage_jipin_id',$hdid)->where('mid',$p_p_num['mid'])->order('id desc')->value('proid');

                    //查询参与此活动的商品
                    if($hd_info['fwtype'] == 0){
                        $proidarr = explode(',',$hd_info['productids']);
                        $isz_o_proid = Db::name('shop_product')->where('aid', $aid)->where('id', $o_proid)->find();
                        if(!in_array($o_proid,$proidarr) || !$isz_o_proid){
                            //如果不在当前活动商品里
                            $o_proid = 0;
                            foreach ($proidarr as $v_proid){
                                $isz_o_proid = Db::name('shop_product')->where('aid', $aid)->where('id', $v_proid)->find();
                                if($isz_o_proid){
                                    $o_proid = $v_proid;
                                    break;
                                }
                            }
                        }
                    }else{
                        //获取分类下的商品
                        $categoryids = explode(',', $hd_info['categoryids']);
                        $clist = Db::name('shop_category')->where('pid', 'in', $categoryids)->select()->toArray();
                        foreach ($clist as $kc => $vc) {
                            $categoryids[] = $vc['id'];
                            $cate2 = Db::name('shop_category')->where('pid', $vc['id'])->find();
                            $categoryids[] = $cate2['id'];
                        }

                        //最近一次参与活动下单的商品
                        $cid = Db::name('shop_product')->where('aid',$aid)->where('bid',$bid)->where('id',$o_proid)->value('cid');
                        $cids = explode(',',$cid);
                        if(!array_intersect($cids,$categoryids)){
                            //如果不在当前活动商品里
                            $sjcid = $categoryids[array_rand($categoryids)];
                            $o_proid = Db::name('shop_product')->where('aid',$aid)->where('bid',$bid)->where('find_in_set('.$sjcid.',cid)')->value('id');
                        }
                    }

                    //复购商品
                    if($yx_collage_jipin_optimize){
                        if($p_p_num['fugou_proid'] > 0){
                            $o_proid = $p_p_num['fugou_proid'];
                            $isz_o_proid = Db::name('shop_product')->where('aid', $aid)->where('id', $o_proid)->find();
                            if(!$isz_o_proid){
                                $o_proid = $hd_info['mrfg_proid'];
                            }
                        }elseif ($hd_info['mrfg_proid'] > 0){
                            $o_proid = $hd_info['mrfg_proid'];
                        }
                    }

                    //生成新订单
                    $orderid = \app\common\Member::addCollageJipinOrder($aid,$p_p_num['mid'],$o_proid,1,$hd_info);

                    //生成新团
                    return self::collageJipinOrder($aid,$orderid,$hdid);
                }
            }
        }
    }
    // 门店店长核销返佣
    public static function dianzhangCommission($aid=0,$order=[]){
        $mendian = Db::name('mendian')->where('aid',$aid)->where('id',$order['mdid'])->find();
        if($mendian && $mendian['dianzhang'] && $mendian['dianzhang_bili'] > 0){
            $oglist = Db::name('shop_order_goods')->where(['aid'=>$aid,'orderid'=>$order['id']])->select()->toArray();
            $ogids = [];
            if($oglist){
                foreach ($oglist as $og){
                    $ogids[] = $og['id'];
                }
            }
            $givecommission = dd_money_format($mendian['dianzhang_bili'] * 0.01 * $order['totalprice']);
            if($givecommission > 0){
                $fhdata = [];
                $fhdata['aid'] = $aid;
                $fhdata['mid'] = $mendian['dianzhang'];
                $fhdata['commission'] = $givecommission;
                $fhdata['send_commission'] = $givecommission;
                $fhdata['remark'] = '店长分红奖励';
                $fhdata['type'] = 'mendian_dianzhan_commission';
                $fhdata['module'] = 'shop';
                $fhdata['createtime'] = time();
                $fhdata['ogids'] = implode(',', $ogids);
                $fhdata['frommid'] = $order['mid'];
                $fhdata['status'] = 0;
                Db::name('member_fenhonglog')->insert($fhdata);
            }
        }
    }

    //
    public static function ztorder_extrareward($aid,$orderid,$oglist,$member,$type='shop'){
        if(getcustom('member_level_ztorder_extrareward')){
            $ztorder_extrareward = true;
            //平台权限
            $admin_user = Db::name('admin_user')->where('aid',$aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
            if($admin_user){
                if($admin_user['auth_type'] !=1 ){
                    $admin_auth = !empty($admin_user['auth_data'])?json_decode($admin_user['auth_data'],true):[];
                    if(!in_array('ZtorderExtrareward,ZtorderExtrareward',$admin_auth)){
                        $ztorder_extrareward = false;
                    }
                }
            }else{
                $ztorder_extrareward = false;
            }
            if(!$ztorder_extrareward) return;

            //查询此订单是否已发放过
            $countorder = Db::name('member_commission_record')->where('mid',$member['pid'])->where('orderid',$orderid)->where('frommid',$member['id'])->where('othertype','ztorder_extrareward')->where('status','>=',0)->count('id');
            //直推前三单额外奖励
            if(!$countorder && $oglist && $member && !empty($member['pid'])){
                $plevel = Db::name('member_level')->alias('ml')
                    ->join('member m','ml.id = m.levelid')
                    ->where('m.id',$member['pid'])->where('ml.can_agent','>=',1)->field('ml.id,ml.ztorder_extrareward1,ml.ztorder_extrareward2,ml.ztorder_extrareward3')->find();
                if($plevel && ($plevel['ztorder_extrareward1']>0 || $plevel['ztorder_extrareward2']>0 || $plevel['ztorder_extrareward3']>0)){
                    //查询已确认收货的商城订单几单
                    $countorder = Db::name('shop_order')->where('mid',$member['id'])->where('status',3)->count('id');
                    if($countorder>=3) return;
                    $countnum =$countorder+1;

                    //查询是否有已发放直推额外奖励的佣金记录
                    $countrecord = Db::name('member_commission_record')->where('mid',$member['pid'])->where('frommid',$member['id'])->where('othertype','ztorder_extrareward')->where('status',1)->count('id');
                    if($countrecord>=$countnum) $countnum = $countrecord+1;
                    if($countnum>3) return;

                    $ztorder_extrareward = 0;
                    if($countnum <= 1){
                        $ztorder_extrareward = $plevel['ztorder_extrareward1'];
                    }else if($countnum == 2){
                        $ztorder_extrareward = $plevel['ztorder_extrareward2'];
                    }else{
                        $ztorder_extrareward = $plevel['ztorder_extrareward3'];
                    }
                    $commission = 0;
                    foreach($oglist as $og){
                        if($og['commission_totalprice']>0){
                            $commission += $og['commission_totalprice'] * $ztorder_extrareward/100;
                        }
                    }
                    unset($og);
                    $commission = round($commission,2);
                    if($commission>0){
                        $remark = '直推下级购买商品，发直推第'.$countnum.'单额外奖励';
                        $data = [
                            'aid' => $aid,'mid' => $member['pid'],'frommid' => $member['id'],'commission' => $commission,'orderid' => $orderid,'ogid' => 0,'type'=>$type,
                            'othertype'=>'ztorder_extrareward','status'=>1,'remark' => $remark,
                            'createtime' => time(),'endtime'=>time()
                        ];
                        $sql = Db::name('member_commission_record')->insert($data);
                        if($sql){
                            \app\common\Member::addcommission($aid,$member['pid'],$member['id'],$commission,$remark,1,'ztorder_extrareward');
                        }
                    }
                }
            }
        }
    }
    // 同一会员订单合并
    public static function orderMerge($aid,$order,$status){
        // 普通订单
        $where = [];
        $where['aid'] = $aid;
        $where['bid'] = $order['bid'];
        $where['mid'] = $order['mid'];
        $where['status'] = 0;
        $where['freight_type'] = $order['freight_type'];//配送方式
        $where['freight_id'] = $order['freight_id'];
        $where['mdid'] = $order['mdid'];
        $where['linkman'] = $order['linkman'];
        // 同一收货地址
        $where['area'] = $order['area'];
        $where['address'] = $order['address'];
        $info = Db::name('shop_order_merge')->where($where)->find();

        $is_new = 0;
        if($info){
            // 判断订单状态，是否有发货收货的
            $shop_order = Db::name('shop_order')->where('aid',$aid)->where('id','in',$info['orderids'])->where('status','in',[2,3])->find();
            if($shop_order){
                Db::name('shop_order_merge')->where('aid',$aid)->where('id',$info['id'])->update(['status'=>1]);  
                $is_new = 1;
            }else{
                $update = [];
                $update['orderids'] = $info['orderids'].','.$order['id'];
                $update['ordernums'] = $info['ordernums'].','.$order['ordernum'];
                $update['totalprice'] = dd_money_format($info['totalprice']+$order['totalprice']);
                $update['product_price'] = dd_money_format($info['product_price']+$order['product_price']);
                Db::name('shop_order_merge')->where('aid',$aid)->where('id',$info['id'])->update($update); 
            }
        }else{
            $is_new = 1;
        }

        if($is_new == 1){
            $data = [];
            $data['aid'] = $aid;
            $data['bid'] = $order['bid'];
            $data['mid'] = $order['mid'];
            $data['orderids'] = $order['id'];
            $data['ordernums'] = $order['ordernum'];
            $data['status'] = 0;
            $data['freight_type'] = $order['freight_type'];//配送方式
            $data['freight_id'] = $order['freight_id'];
            $data['linkman'] = $order['linkman'];
            $data['area'] = $order['area'];
            $data['address'] = $order['address'];
            $data['totalprice'] = $order['totalprice'];
            $data['product_price'] = $order['product_price'];
            $data['mdid'] = $order['mdid'];
            $data['tel'] = $order['tel'];
            $data['freight_text'] = $order['freight_text'];
            $data['createtime'] = time();
            Db::name('shop_order_merge')->insert($data);
        }
    }

    public static function jietiYeji($aid,$starttime,$endtime){
        if(getcustom('yx_team_yeji_fenhong',$aid)) {
            $sysset = Db::name('team_yeji_fenhong_set')->where('aid', $aid)->find();
            $where = [];
            $where[] = ['og.aid', '=', $aid];
            $where[] = ['og.is_yeji', '=', 0];
            if ($sysset['fhjiesuanbusiness'] == 0) { //多商户的商品是否参与分红
                $where[] = ['og.bid', '=', '0'];
            }
            if ($sysset['fhjiesuantime_type'] == 1) { //分红结算时间类型 0收货后，1付款后
                $where[] = ['og.status', 'in', [1, 2, 3]];
                $where[] = ['og.createtime', '>=', $starttime];
                $where[] = ['og.createtime', '<', $endtime];
            } else {
                $where[] = ['og.status', '=', '3'];
                $where[] = ['og.endtime', '>=', $starttime];
                $where[] = ['og.endtime', '<', $endtime];
            }
            $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('shop_order o', 'o.id=og.orderid')
                ->where($where)->where('refund_num', 0)->select()->toArray();
            foreach ($oglist as $og) {
                $yeji = $og['real_totalprice'];
                self::updateTeamYeji($aid, $og['mid'], $yeji);
                Db::name('shop_order_goods')->where('id', $og['id'])->update(['is_yeji' => 1]);
            }
        }
    }
    //订单支付完成或退款，更新团队业绩
    public static function updateTeamYeji($aid,$mid,$yeji){
        if(getcustom('yx_team_yeji_fenhong',$aid)) {
            if (getcustom('yx_team_yeji_fenhong', $aid)) {
                $yeji_set = Db::name('team_yeji_fenhong_set')->where('aid', $aid)->find();
                $levelids = explode(',', $yeji_set['levelids']);
                $member = Db::name('member')->where('aid', $aid)->where('id', $mid)->find();

                $path = $member['path'];
                if ($path) {
                    Db::name('member')->where('aid', $aid)->where('id', 'in', $path)->where('levelid', 'in', $levelids)->inc('team_yeji', $yeji)->update();
                }
                Db::name('member')->where('aid', $aid)->where('id', $mid)->inc('self_yeji', $yeji)->update();
                return true;
            }
        }
    }

    //购物返现
    public static function dealcashback($aid,$order,$oglist,$member,$type="collect"){
        if($oglist && $member){
            //购物返现
            $bid = $order['bid'];
            if(getcustom('yx_cashback_yongjin',$aid) || getcustom('yx_cashback_business_product',$aid)){
                //定制总后台添加多商户商品
                $bid = 0;
            }
            $where = [];
            $where[] = ['aid','=',$aid];
            $where[] = ['bid','=',$bid];
            $where[] = ['starttime','<',$order['paytime']];
            $where[] = ['endtime','>',$order['paytime']];
            if(getcustom('yx_cashback_stop')){
                $where[] = ['status','=',1];
            }
            if(getcustom('yx_cashback_sendtype')){
                //付款后还是确认收货时发放
                if($type=="collect"){
                    $where[] = ['sendtype','=',2];
                }else if($type=="pay"){
                    $where[] = ['sendtype','=',1];
                }
            }
            $cashbacklist = Db::name('cashback')->where($where)->order('sort desc')->select()->toArray();
            $allreal_totalprice = 0;//实际消费

            //返现类型 1、余额 2、佣金 3、积分 小数位数
            $money_weishu = 2;$commission_weishu = 2;$score_weishu = 0;
            if(getcustom('member_money_weishu',$aid)){
                $money_weishu = Db::name('admin_set')->where('aid',$aid)->value('member_money_weishu');
            }
            if(getcustom('fenhong_money_weishu',$aid)){
                $commission_weishu = Db::name('admin_set')->where('aid',$aid)->value('fenhong_money_weishu');
            }
            if(getcustom('score_weishu',$aid)){
                $score_weishu = Db::name('admin_set')->where('aid',$aid)->value('score_weishu');
            }
            if(getcustom('product_chinaums_subsidy')){
                $adminSet = Db::name('admin_set')->where('aid',$order['aid'])->find();
            }
            foreach($oglist as $og){
                $real_totalprice = $og['real_totalprice'];
                $allreal_totalprice += $real_totalprice;
                $proField = 'id,cid';
                if(getcustom('product_chinaums_subsidy')){
                    $proField .= ',name,brand_id,category_code,barcode';
                }
                $product = Db::name('shop_product')->where('id',$og['proid'])->field($proField)->find();
                if($product && $cashbacklist){
                    foreach($cashbacklist as $v){

                        $gettj = explode(',',$v['gettj']);
                        if(!in_array('-1',$gettj) && !in_array($member['levelid'],$gettj)){ //不是所有人
                            continue;
                        }

                        if($v['fwtype']>=3){
                            //其他类型不适应
                            continue;
                        }

                        if($v['fwtype']==2){//指定商品可用
                            $productids = explode(',',$v['productids']);
                            if(!in_array($product['id'],$productids)){
                                continue;
                            }
                        }

                        if($v['fwtype']==1){//指定类目可用
                            $categoryids = explode(',',$v['categoryids']);
                            $cids = explode(',',$product['cid']);
                            $clist = Db::name('shop_category')->where('pid','in',$categoryids)->select()->toArray();
                            foreach($clist as $vc){
                                $categoryids[] = $vc['id'];
                                $cate2 = Db::name('shop_category')->where('pid',$vc['id'])->find();
                                $categoryids[] = $cate2['id'];
                            }
                            if(!array_intersect($cids,$categoryids)){
                                continue;
                            }
                        }

                        //如果返现利率大于0
                        if($v['back_ratio']>0){
                            //计算返现
                            $back_price = $v['back_ratio']*$real_totalprice/100;

                            //返现类型 1、余额 2、佣金 3、积分
                            if($v['back_type'] == 1 ){
                                $back_price = dd_money_format($back_price,$money_weishu);
                            }else if($v['back_type']== 2){
                                $back_price = dd_money_format($back_price,$commission_weishu);
                            }else if($v['back_type'] == 3){
                                $back_price = dd_money_format($back_price,$score_weishu);
                            }

                            $return_type = 0;//发放类型 0：立即发放 1、自定义 2、阶梯 5、递减
                            if(getcustom('yx_cashback_time',$aid) || getcustom('yx_cashback_stage',$aid)){
                                $return_type = $v['return_type'];
                            }
                            if(getcustom('yx_cashback_multiply',$aid) || getcustom('yx_cashback_category_addup_return',$aid)){
                                $return_type = $v['return_type'];
                            }
                            $og['ordertype'] = 'shop';
                            $cashback_mid = $order['mid'];
                            if(getcustom('yx_cashback_pid',$aid)){
                                if($v['cashback_pid']==1){
                                    //返现给下单人的直推上级
                                    $cashback_mid = $member['pid']?:0;
                                    if(!$cashback_mid){
                                        //没有上级不发放了
                                        continue;
                                    }
                                }
                            }
							if(getcustom('yx_cashback_pid_jiantui',$aid)){
								if($v['cashback_pid'] == 2){
									//上级
									$cashback_mid = $member['pid']?:0;
									if(!$cashback_mid){
										continue;
									}
									//上上级
									$cashback_mid = Db::name('member')->where('id',$cashback_mid)->value('pid');
									if(!$cashback_mid){
										continue;
									}
								}
							}
                            //记录参与的会员
                            $map = ['aid'=>$order['aid'],'mid'=>$cashback_mid,'cashback_id'=>$v['id'],'pro_id'=>$og['proid'],'type'=>'shop'];
                            if(getcustom('yx_cashback_decay') && $v['return_type'] == 5){
                                $map['decay_back_status'] = [1, 3];
                            }
                            $cashback_member_check = Db::name('cashback_member')->where($map)->find();
                            //返现额度倍数
                            $goods_multiple_max = 0;
                            if(getcustom('cashback_max',$aid)){
                                $goods_multiple_max = $v['goods_multiple_max'];
                            }
                            //计算最高返现数量，有额度倍数按额度倍数处理，没有倍数按返现比例处理
                            if($goods_multiple_max>0){
                                $cashback_money_max = $og['sell_price'] * $goods_multiple_max * $og['num'];
                            }else{
                                $cashback_money_max = $og['sell_price'] * $v['back_ratio']/100 * $og['num'];;
                            }
                            if(getcustom('yx_cashback_decay')  && $v['return_type'] == 5){
                                $cashback_money_max = $real_totalprice;
                            }
                            if(!$cashback_member_check){
                                $cashback_member = [];
                                $cashback_member['aid'] = $order['aid'];
                                $cashback_member['mid'] = $cashback_mid;
                                $cashback_member['cashback_id'] = $v['id'];
                                $cashback_member['pro_id'] = $og['proid'];
                                $cashback_member['pro_num'] = $og['num'];

                                $cashback_member['cashback_money_max'] = $cashback_money_max;
                                //$cashback_member['cashback_money']   = $back_price;
                                $cashback_member['back_type']          = $v['back_type'];
                                $cashback_member['type']               = 'shop';
                                $cashback_member['create_time']        = time();
                                if(getcustom('yx_cashback_pid',$aid)) {
                                    $cashback_member['order_mid'] = $order['mid'];
                                }
                                $cashback_member['ogid'] = $og['id'];
                                if(getcustom('yx_cashback_decay') && $return_type == 5) {
                                    $cashback_member['orderid'] = $order['id'];
                                    $cashback_member['back_after'] = $cashback_money_max;
                                    $cashback_member['decay_back_status'] = 1;
                                }
                                $insert = Db::name('cashback_member')->insert($cashback_member);
                                $cashback_member_check = Db::name('cashback_member')->where('aid',$order['aid'])->where('mid',$order['mid'])->where(['cashback_id'=>$v['id'],'pro_id'=>$og['proid'],'type'=>'shop'])->find();
                            }else{
                                if(getcustom('yx_cashback_decay') && $return_type == 5) {
                                    $cashback_member = [];
                                    $cashback_member['back_after'] = $cashback_member_check['back_after'] + $cashback_money_max;
                                }
                                Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('pro_num',$og['num'])->update();
                                $cashback_member['cashback_money_max']  = $cashback_member_check['cashback_money_max'] + $cashback_money_max;

                                Db::name('cashback_member')->where('id',$cashback_member_check['id'])->update($cashback_member);
                            }

                            //开启限额
                            $cashback_max = 0;
                            if(getcustom('cashback_max',$aid)){
                                $cashback_max = 1;
                            }
                            //开启选择受益人
                            $cashback_receiver = 0;
                            if(getcustom('cashback_receiver',$aid)){
                                $cashback_receiver = 1;
                            }

                            if(!$return_type){
                                //受益人限额仅限单个商品可用
                                if($cashback_receiver || $cashback_max){
                                    if($back_price){
                                        //判定受益人的方式1
                                        if($v['receiver_type'] == 1){
                                            //判定是否限额
                                            if($v['back_type'] == 1 ){
                                                $cashback_num = $cashback_member_check['cashback_money'];
                                            }else if($v['back_type'] == 2){
                                                $cashback_num = $cashback_member_check['commission'];
                                            }else if($v['back_type'] == 3){
                                                $cashback_num = $cashback_member_check['score'];
                                            }

                                            if(getcustom('cashback_max',$aid)){
                                                if($v['goods_multiple_max'] > 0){
                                                    if($cashback_member_check['cashback_money_max'] > $cashback_num){
                                                        //最大可追加金额
                                                        $cashback_money_max = $cashback_member_check['cashback_money_max'] - $cashback_num;
                                                        if($cashback_money_max < $back_price){
                                                            $back_price = $cashback_money_max;
                                                        }
                                                    }else{
                                                        $back_price = 0;
                                                    }
                                                }
                                            }
                                        }elseif($v['receiver_type'] ==2){//参与活动的人
                                            //查询参与活动的所有人发放佣金
                                            $res_code = self::cashbackMemerDo($order['aid'],$order['mid'],$v,$og,$back_price);
                                            $back_price = 0;
                                        }
                                    }
                                }

                                if($back_price>0){
                                    if($v['back_type'] == 1 ){
                                        \app\common\Member::addmoney($aid,$cashback_mid,$back_price,$v['name']);
                                        //累计到参与人统计表
                                        Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('cashback_money',$back_price)->update();
                                    }else if($v['back_type'] == 2){
                                        \app\common\Member::addcommission($aid,$cashback_mid,$order['mid'],$back_price,$v['name']);
                                        //累计到参与人统计表
                                        Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('commission',$back_price)->update();
                                    }else if($v['back_type'] == 3){
                                        \app\common\Member::addscore($aid,$cashback_mid,$back_price,$v['name']);
                                        //累计到参与人统计表
                                        Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('score',$back_price)->update();
                                    }
                                    if(getcustom('yx_cashback_time',$aid) || getcustom('yx_cashback_stage',$aid)){
                                        //直接发放
                                        \app\custom\OrderCustom::deal_first_cashback($aid,$cashback_mid,$back_price,$og,$v,0,'shop',$order['mid']);
                                    }
                                    //写入发放日志
                                    \app\custom\OrderCustom::cashbackMemerDoLog($order['aid'],$cashback_mid,$v,$og,$back_price,$order['mid']);
                                }
                            }elseif($return_type==3){
                                if(getcustom('yx_cashback_multiply',$aid)){
                                    \app\custom\OrderCustom::deal_multiply_cashback($aid,$cashback_mid,$back_price,$og,$v,$return_type,'shop',$order['mid']);
                                }
                            }elseif($return_type==4){//叠加递减
                                if(getcustom('yx_cashback_addup_return')){
                                    if($back_price > 0){
                                        \app\custom\OrderCustom::cashbackAddup($aid,$cashback_mid,$v['id'],$back_price,'购物返现'); 
                                    }
                                }
                            }elseif($return_type==5){//递减返现
                                if(getcustom('yx_cashback_decay',$aid)){
                                    \app\custom\OrderCustom::deal_decay_cashback($v,$cashback_member_check['id']);
                                }
                            }else{
                                if($back_price>0){
                                    if(getcustom('yx_cashback_time',$aid) || getcustom('yx_cashback_stage',$aid)){
                                        //处理自定义第一次发放
                                        //处理受益人是参与人
                                        if($cashback_receiver == 1 && $v['receiver_type'] ==2){
                                            //查询参与活动的所有人发放佣金
                                            $res_code = self::cashbackMemerDo($order['aid'],$order['mid'],$v,$og,$back_price,$return_type);
                                        }else{
                                            \app\custom\OrderCustom::deal_first_cashback($aid,$cashback_mid,$back_price,$og,$v,$return_type,'shop',$order['mid']);
                                        }
                                    }
                                }
                            }
                        }

                        if(getcustom('yx_cashback_time_tjspeed',$aid)){
                            //给上级、上上级加速
                            if($member['pid']>0 && $real_totalprice>0){
                                \app\custom\OrderCustom::deal_cashbackspeed($member,$real_totalprice,$v);
                            }
                        }
                    }
                }

                if(getcustom('product_chinaums_subsidy') && $og['subsidy_id'] && $og['cred_frozen_no']){
                    //国补订单核销
                    $product['brand_name'] = Db::name('shop_brand')->where('id',$product['brand_id'])->value('name');
                    $subsidy = new \app\custom\ChinaumsSubsidy($og['aid']);
                    $subsidyRecord = Db::name('chinaums_subsidy_apply')->where('aid',$og['aid'])->where('id',$og['subsidy_id'])->find();
                    $confirmRes = $subsidy->qualificationConfirm($order,$product,$og,$subsidyRecord,$adminSet);
                    if($confirmRes['respCode'] == '000000'){
                        Db::name('chinaums_subsidy_apply')->where('id',$og['subsidy_id'])->update(['status'=>5]); //已核销
                    }
                }
            }
            if(getcustom('yx_cashback_time_teamspeed',$aid)){
                //团队业绩达标加速
                if($member['pid']>0 && $allreal_totalprice>0){
                    \app\custom\OrderCustom::deal_cashbackteamspeed($member,$allreal_totalprice);
                }
            }
        }
    }

    //赠送回本分红额度
    public static function giveHuibenMaximum($aid,$orderid){
        if(getcustom('fenhong_gudong_huiben')){
            $order = Db::name('shop_order')->where('id',$orderid)->find();
            $sysset = Db::name('admin_set')->where('aid',$aid)->find();
            if($sysset['fenhong_huiben_max_status']==0){
                //未开启分红额度
                return true;
            }
            if($sysset['fhjiesuantime_type_huiben']==0 && $order['status']<=1){
                //设置的收货后发放
                return true;
            }
            if($order['huiben_maximum']>0){
                //已经发放过了
                return true;
            }
            $og_lists = Db::name('shop_order_goods')->where('aid',$aid)->where('orderid',$orderid)->select()->toArray();
            $member = Db::name('member')->where('id',$order['mid'])->find();
            $member_level = Db::name('member_level')->where('id',$member['levelid'])->find();
            $huiben_maximum = 0;
            foreach($og_lists as $og){
                $product = Db::name('shop_product')->where('id',$og['proid'])->find();
                if($product['gdfenhongset_huiben']==-1){
                    continue;
                }
                $huiben_maximum = bcadd($huiben_maximum,bcmul($og['real_totalprice'],$member_level['fenhong_huiben_max_bili']/100,2),2);
            }
            if($huiben_maximum<=0){
                return ;
            }
            $remark = '订单'.$order['id'].'赠送回本分红额度'.$huiben_maximum;
            \app\common\Member::addhuibenmaximum($aid,$order['mid'],$huiben_maximum,$remark,'shop_order',$orderid);
            Db::name('shop_order')->where('id',$orderid)->update(['huiben_maximum'=>$huiben_maximum]);
            return true;
        }
    }

    //定时返回所有
    public static function deal_decreturnAll(){
        if(getcustom('maidan_money_dec') || getcustom('member_dedamount') || getcustom('pay_money_combine_maidan') || getcustom('member_shopscore')){
            $admin = Db::name('admin')->where('status',1)->field('id')->select()->toArray();
            if($admin){
                foreach($admin as $v){
                    $aid = $v['id'];
                    self::deal_decreturn($aid,0,0,1);
                    if(getcustom('maidan_new')){
                        self::deal_decreturn($aid,0,0,1,'maidan_new');
                    }
                }
            }
        }
    }

    //进入买单页面时，返还那些待支付的抵扣金
    public static function deal_decreturn($aid,$bid=0,$mid=0,$opttype=0,$ordertype='maidan'){
        if(getcustom('maidan_money_dec') || getcustom('pay_money_combine_maidan') || getcustom('member_shopscore')){
            self::deal_maidanstaydec($aid,'',$mid,['opttype'=>$opttype,'ordertype'=>$ordertype]);
        }
        if(getcustom('member_dedamount')){
            $params=['paytype'=>$ordertype,'opttype'=>$opttype,'ordertype'=>$ordertype,'opttype'=>'return'];
            \app\common\Member::deal_staydecdedamount($aid,'',$mid,'买单未支付抵扣金抵扣返回',$params);
        }
    }

    //处理买单待返回抵扣
    private static function deal_maidanstaydec($aid,$bid=0,$mid=0,$params=['ordernum'=>'','opttype'=>0,'ordertype'=>'maidan']){
        $ordertype = $params['ordertype']??'maidan';

        //处理返回的抵扣记录
        $where = [];
        $where[] = ['aid','=',$aid];
        if($bid ===0 || $bid>0){
            $where[] = ['bid','=',$bid];
        }
        if($mid){
            $where[] = ['mid','=',$mid];
        }
        $ordernum = $params['ordernum']??0;
        if($ordernum){
            $where[] = ['ordernum','=',$ordernum];
        }
        $where[] = ['status','=',0];

        $opttype = $params['opttype']??0;
        if($opttype == 1){
            //一个小时前
            $endtime = time()-3600;
            $where[] = ['createtime','<=',$endtime];
        }

        $where2 = '';
        if(getcustom('maidan_money_dec')){
            $where2 = ' dec_money>0 ';//余额抵扣
        }

        if(getcustom('pay_money_combine_maidan')){
            if($where2){
                $where2 .= ' or combine_money>0 ';
            }else{
                $where2 = ' combine_money>0 ';
            }
        }

        if(getcustom('member_shopscore')){
            if($ordertype == 'maidan'){
                if($where2){
                    $where2 .= ' or (shopscore>0 and shopscore_status = 1) ';
                }else{
                    $where2 = ' (shopscore>0 and shopscore_status = 1) ';
                }
            }
        }
        if(!$where2) return;//若无查询条件，则停止

        $orders = Db::name($ordertype.'_order')->where($where)->where($where2)->order('id asc')->select()->toArray();

        if($orders){
            foreach($orders as $lv){
                $up = Db::name('maidan_order')->where('id',$lv['id'])->where('status',0)->update(['status'=>-1]);
                if($up){
                    if(getcustom('maidan_money_dec')){
                        if($lv['dec_money'] && $lv['dec_money']>0){
                            //余额抵扣
                            $remark = '买单未支付'.t('余额').'抵扣返回';
                            $res = \app\common\Member::addmoney($aid,$lv['mid'],$lv['dec_money'],$remark.',订单号：'.$lv['ordernum']);
                            if($res && $res['status'] == 1){
                                Db::name('maidan_order')->where('id',$lv['id'])->update(['dec_money'=>0]);
                            }
                        }
                    }

                    if(getcustom('pay_money_combine_maidan')){
                        //余额组合支付
                        if($lv['combine_money'] && $lv['combine_money']>0){
                            $remark = '买单未支付'.t('余额').'付款返回';
                            $res2 = \app\common\Member::addmoney($aid,$lv['mid'],$lv['combine_money'],$remark.',订单号：'.$lv['ordernum']);
                            if($res2 && $res2['status'] == 1){
                                Db::name('maidan_order')->where('id',$lv['id'])->update(['combine_money'=>0]);
                            }
                        }
                    }

                    if(getcustom('member_shopscore')){
                        //产品积分抵扣
                        if($lv['shopscore'] && $lv['shopscore']>0  && $lv['shopscore_status'] == 1){
                            $params=['orderid'=>$lv['id'],'ordernum'=>$lv['ordernum'],'paytype'=>$ordertype];
                            $remark = '买单未支付抵扣返回';
                            $res3 = \app\common\Member::addshopscore($aid,$lv['mid'],$lv['shopscore'],$remark.',订单号：'.$lv['ordernum'],$params);
                            if($res3 && $res3['status'] == 1){
                                Db::name('maidan_order')->where('id',$lv['id'])->update(['shopscore'=>0]);
                            }
                        }
                    }
                }
            }
            unset($lv);
        }
    }

    //处理预约时间
    public static function dealyytime($yy_time,$begintime){
        $yybegintime = '';//开始时间
        $yyendtime = '';//结束时间
        //判断是否有年月字段
        if(mb_strpos($yy_time,'年') || mb_strpos($yy_time,'月')){
            //是否是时间段范围
            if(strpos($yy_time,'-') || strpos($yy_time,'~')){
                if(strpos($yy_time,'-')){
                    $yytimeArr = explode('-',$yy_time);
                }else{
                    $yytimeArr = explode('~',$yy_time);
                }

                //判断是否有年字段
                $yeartime = '';
                if(!mb_strpos($yy_time,'年')){
                    $yeartime = date('Y年',$begintime);
                }
                $yybegintime = $yeartime.$yytimeArr[0];
                $yyendtime   = $yeartime.$yytimeArr[1];

                //判断结束时间是否有年字段
                if(!mb_strpos($yyendtime,'年')){
                    //获取开始时间年字段
                    $yearpos = mb_strpos($yybegintime,'年');
                    $yyendtime1 = mb_substr($yybegintime,0,$yearpos+1);

                    //判断开始时间是否有月字段
                    if(!mb_strpos($yyendtime,'月')){
                        //获取开始时间年月字段
                        $yybegintimeArr = explode(' ',$yybegintime);
                        $yyendtime2 = $yybegintimeArr[0];
                        $yyendtime = $yyendtime2.' '.$yyendtime;
                    }else{
                        $yyendtime = $yyendtime1.$yyendtime;
                    }
                }
            }else{
                //判断是否有年字段
                if(!mb_strpos($yy_time,'年')){
                    //判断是否有月份字段
                    if(!mb_strpos($yy_time,'月')){
                        $yybegintime = $yyendtime = date('Y年m月',$begintime).$yy_time;
                    }else{
                        $yybegintime = $yyendtime = date('Y年',$begintime).$yy_time;
                    }
                }else{
                    $yybegintime = $yyendtime = $yy_time;
                }
            }
        }else{
            $yybegintime = $yyendtime = $yy_time;
        }
        
        if(mb_strpos($yybegintime,'年') || mb_strpos($yybegintime,'月')){
            $yybegintime = preg_replace(['/年|月/','/日/'],['-',''],$yybegintime);
        }
        $yybegintime = strtotime($yybegintime);
        if(mb_strpos($yyendtime,'年') || mb_strpos($yyendtime,'月')){
            $yyendtime = preg_replace(['/年|月/','/日/'],['-',''],$yyendtime);
        }
        $yyendtime = strtotime($yyendtime);

        return ['yybegintime'=>$yybegintime,'yyendtime'=>$yyendtime];
    }

    public static function dealDedamountZtreward($aid,$orderid,$order,$type,$sysset=[]){
        if(getcustom('member_dedamount',$aid)){
            if(!$sysset){
                $field = 'dedamount_fenxiao_ztreward,dedamount_fenxiao_ztreward_ratio';
                if(getcustom('fenhong_money_weishu',$aid)){
                    $field .= ',fenhong_money_weishu';
                }
                $sysset = Db::name('admin_set')->where('aid',$aid)->field($field)->find();
            }
            //抵扣金直推奖
            if($sysset && $sysset['dedamount_fenxiao_ztreward'] && $sysset['dedamount_fenxiao_ztreward_ratio']>0){
                //根据抵扣金直推奖比例计算奖励，查询所有此订单减少的抵扣金
                $dedamountlogs = Db::name('member_dedamountlog')->where('ordernum',$order['ordernum'])->where('mid',$order['mid'])->where('paytype',$type)->where('dedamount','<',0)->where('orderid',0)->where('type',2)->where('aid',$aid)->select()->toArray();
                if($dedamountlogs){
                    foreach($dedamountlogs as $dedamountlog){
                        if($dedamountlog['pid']<=0) continue;//无上级跳过

                        //查询减少来源的记录
                        $plog = Db::name('member_dedamountlog')->where('id',$dedamountlog['pid'])->find();
                        if(!$plog) continue;//无上级记录跳过

                        //查询是商家，还是分享者
                        $pmid = 0;
                        if($plog['bid']){
                            $pmid = Db::name('admin_user')->where('bid',$plog['bid'])->where('isadmin','>',0)->where('aid',$aid)->value('mid');
                            $remark1 = '用户购买商品使用抵扣金奖励';
                            if($type == 'maidan'){
                                $remark1 = '用户买单付款使用抵扣金奖励';
                            }
                        }else if($plog['from_mid']){
                            $pmid = $plog['from_mid'];
                            $remark1 = t('下级').'购买商品使用抵扣金奖励';
                            if($type == 'maidan'){
                                $remark1 = t('下级').'买单付款使用抵扣金奖励';
                            }
                        }
                        if($pmid){
                            $countmember = Db::name('member')->where('id',$pmid)->where('aid',$aid)->count('id');
                            if($countmember){
                                //计算让利佣金
                                $commission = -$dedamountlog['dedamount'] * $sysset['dedamount_fenxiao_ztreward_ratio']/100;
                                $commission_weishu = 2;
                                if(getcustom('fenhong_money_weishu',$aid)){
                                    $commission_weishu = $sysset['fenhong_money_weishu'];
                                }
                                $commission = dd_money_format($commission,$commission_weishu);
                                if($commission>0){
                                    $data_c = ['aid'=>$aid,'mid'=>$pmid,'frommid'=>$order['mid'],'orderid'=>$orderid,'ogid'=>0,'type'=>$type,'commission'=>$commission,'score'=>0,'remark'=>$remark1,'createtime'=>time()];
                                    if($type == 'maidan'){
                                        $data_c['status'] = 1;
                                        $data_c['endtime']= time();
                                    }
                                    $id = Db::name('member_commission_record')->insertGetId($data_c);
                                    if($type == 'maidan' && $id){
                                        \app\common\Member::addcommission($aid,$pmid,$order['mid'],$commission,$remark1);
                                    }
                                }
                            }
                        }
                    }
                    unset($dedamountlog);
                }
            }
        }
    }

    //团队业绩奖级差奖励处理 yx_team_yeji_jicha_new
    public static function team_yeji_jicha_new($aid){
        if(getcustom('yx_team_yeji_jicha_new')){
            set_time_limit(0);
            ini_set('memory_limit', '1024M');
            $data = Db::name('yx_team_yeji_fenhong')->where('aid',$aid)->where('status',0)->select()->toArray();
            // 按网体深度排序，最深的先处理
            usort($data, function ($a, $b) {
                $aDepth = empty($a['path']) ? 0 : count(explode(',', $a['path']));
                $bDepth = empty($b['path']) ? 0 : count(explode(',', $b['path']));
                return $bDepth <=> $aDepth;
            });

            // 创建一个以会员ID为键的数组方便查找
            $members = [];
            foreach ($data as $item) {
                $members[$item['mid']] = [
                    'fenhong' => $item['fenhong'],
                    'path' => empty($item['path']) ? [] : explode(',', $item['path']),
                    'jicha' => $item['fenhong'] // 初始化为原始分红
                ];
            }
            // 从最底层开始处理
            foreach ($members as $id => &$member) {
                // 如果有上级，则从上级的分红中扣除当前会员的分红
                if (!empty($member['path'])) {
                    $superiors = array_reverse($member['path']);
                    foreach ($superiors as $superiorId) {
                        if (isset($members[$superiorId])) {
                            $members[$superiorId]['jicha'] -= $member['fenhong'];
                            break; // 找到第一个有效上级后停止
                        }
                    }
                }
            }
            // 构建结果数组
            $result = [];
            foreach ($data as $item) {
                $real_fenhong = $members[$item['mid']]['jicha'];
                Db::name('yx_team_yeji_fenhong')->where('id',$item['id'])->update(['status'=>1,'real_fenhong'=>$real_fenhong]);
                if($real_fenhong>0){
                    \app\common\Member::addcommission($aid,$item['mid'],0,$real_fenhong,'团队业绩阶梯分红奖',1,'teamyejifenhong');
                }
            }
            return $result;
        }
    }

    //查询支付订单，处理是否是商家支付问题
    public static function dealIsbusinesspay($aid,$order,$type=''){
        $isbusinesspay = false;//是否是商家支付
        if(!$order) return $isbusinesspay;
        $bid = $order['bid']??0;
        if($bid && $bid>0){
            $where = [];
            $where[] = ['ordernum','=',$order['ordernum']];
            if($type){
                $where[] = ['type','=',$type];
            }
            $where[] = ['aid','=',$aid];
            $field = 'id,bid,ordernum,isbusinesspay';
            if(getcustom('pay_daifu')) $field .= ',paymid,pid';
            $payorder = Db::name('payorder')->where($where)->field($field)->find();
            if($payorder){
                if($payorder['isbusinesspay']) $isbusinesspay = true;
                if(getcustom('pay_daifu')){
                    if(!$isbusinesspay){
                        //查询是否是代付
                        $paymid = $payorder['paymid']??0;
                        if($paymid && $paymid>0){
                            $daifuPayorder = Db::name('payorder')->where('pid',$payorder['id'])->where('mid',$paymid)->where('status',1)->where('aid',$aid)->order('id desc')->field('id,bid,ordernum,isbusinesspay')->find();
                            if($daifuPayorder && $daifuPayorder['isbusinesspay']) $isbusinesspay = true;
                        }
                    }
                }
            }
        }
        return $isbusinesspay;
    }

    //改价后重新计算分销、分红
    public static function editShopOrderCommission($aid,$orderid){
        /****************************1、重新计算分销***********************************/
        $sysset = Db::name('admin_set')->where('aid',$aid)->find();
        $order = Db::name('shop_order')->where('id',$orderid)->find();
        $ordermid = $order['mid'];
        $member = Db::name('member')->where('id',$order['mid'])->find();
        //是否是复购
        $hasordergoods = Db::name('shop_order_goods')->where('aid',$aid)->where('mid',$order['mid'])->where('status','in','1,2,3')->find();
        if($hasordergoods){
            $isfg = 1;
        }else{
            $isfg = 0;
        }
        $istc1 = 0; //设置了按单固定提成时 只将佣金计算到第一个商品里
        $istc2 = 0;
        $istc3 = 0;
        //先退回之前计算的提成
        Db::name('member_commission_record')
            ->where('aid',$aid)
            ->where('type','shop')
            ->where('orderid',$order['id'])
            ->where('status',0)
            ->update(['status'=>2]);
        $levelList = Db::name('member_level')->where('aid', $aid)->column('*','id');
        $agleveldata = $levelList[$member['levelid']];
        $og_list = Db::name('shop_order_goods')->where('orderid',$order['id'])->select()->toArray();
        foreach($og_list as $ogdata){
            $ogid = $ogdata['id'];
            $product = Db::name('shop_product')->where('id',$ogdata['pid'])->find();
            $guige = Db::name('shop_guige')->where('id',$ogdata['ggid'])->find();
            $num = $ogdata['num'];
            //计算佣金的商品金额
            $commission_totalprice = $ogdata['totalprice'];
            if($sysset['fxjiesuantype']==1){ //按成交价格
                $commission_totalprice = $ogdata['real_totalprice'];
            }
            $commission_totalpriceCache = $commission_totalprice;
            if($sysset['fxjiesuantype']==2){ //按销售利润
                $commission_totalprice = $ogdata['real_totalprice'] - $ogdata['cost_price'] * $num;
            }
            if(getcustom('yx_buyer_subsidy')) {
                if ($sysset['fxjiesuantype'] == 3) { //按抽佣
                    $commission_totalprice = $ogdata['order_fee'];
                }
            }

            if($commission_totalprice < 0) $commission_totalprice = 0;
            //平级奖计算金额
            $commission_totalprice_pj = 0;//
            if(getcustom('commission_parent_pj_jiesuantype')){
                if($sysset['fxjiesuantype_pj']==1){ //按商品价格
                    $commission_totalprice_pj = $ogdata['totalprice'];
                }
                if($sysset['fxjiesuantype_pj']==2){ //按成交价格
                    $commission_totalprice_pj = $ogdata['real_totalprice'];
                }
                if($sysset['fxjiesuantype_pj']==3){ //按销售利润
                    $commission_totalprice_pj = $ogdata['real_totalprice'] - $ogdata['cost_price'] * $num;
                }
                if($commission_totalprice_pj<0){
                    $commission_totalprice_pj = 0;
                }
            }

            $ogupdate = [];
            if(getcustom('member_level_ztorder_extrareward')){
                $ogupdate['commission_totalprice'] = $commission_totalprice;
            }
            if($product['commissionset']!=-1){
                $params = [];
                if(!getcustom('fenxiao_manage')){
                    $sysset['fenxiao_manage_status'] = 0;
                }
                if(getcustom('extend_planorder')){
                    //如果是排队系统里的店铺下单，分销仅给店铺人员，其他人员不发奖
                    $poshopid = $order['poshopid'];
                    if($poshopid && $poshopid>0){
                        $params['poshopid']  = $poshopid;
                        $params['poshopmid'] = Db::name('planorder_shop')->where('id',$poshopid)->value('mid');
                    }
                }
                if(getcustom('shop_product_commission_memberset')){
                    //商品分销员ID，若有分销员ID，给分销员及分销员上级发奖
                    $params['procommissionmid']  = $product['procommissionmid']??0;
                }
                if($sysset['fenxiao_manage_status']){
                    $commission_data = \app\common\Fenxiao::fenxiao_jicha($sysset,$member,$product,$num,$commission_totalprice,$commission_totalprice_pj,$params);
                }else{
                    $commission_data = \app\common\Fenxiao::fenxiao($sysset,$member,$product,$num,$commission_totalprice,$isfg,$istc1,$istc2,$istc3,$commission_totalprice_pj,$params);
                }

                if(getcustom('commission_parent_bcy_send_once') && $sysset['commission_parent_bcy_send_once']==1){
                    //被超越奖只给最近的发一次，无限往上找直至找到一个
                    $path_arr = array_reverse(explode(',',$member['path']));
                    if($path_arr){
                        $lastid = 0;
                        foreach ($path_arr as $pv) {
                            if($lastid > 0 && $pv > 0){
                                $last_member = Db::name('member')->where('aid',$aid)->where('id',$lastid)->field('id,levelid')->find();
                                $last_member_level = $levelList[$last_member['levelid']];

                                $last_p_member = Db::name('member')->where('aid',$aid)->where('id',$pv)->field('id,levelid')->find();
                                $last_p_member_level = $levelList[$last_p_member['levelid']];

                                //判断当前人级别是否大于上级
                                if($last_member_level['id'] > $last_p_member_level['id']){
                                    $last_p_member_level['commissionbcytype'] = $last_p_member_level['commissiontype'];
                                    if($product['commissionbcyset'] != 0){
                                        if($product['commissionbcyset'] == 1){
                                            $commissionbcydata1 = json_decode($product['commissionbcydata1'],true);
                                            $last_p_member_level['commission_parent_bcy'] = $commissionbcydata1[$last_p_member_level['id']]['commission'];
                                        }elseif($product['commissionbcyset'] == 2){
                                            $commissionbcydata2 = json_decode($product['commissionbcydata2'],true);
                                            $last_p_member_level['commission_parent_bcy'] = $commissionbcydata2[$last_p_member_level['id']]['commission'];
                                            $last_p_member_level['commissionbcytype'] = 1;
                                        }else{
                                            $last_p_member_level['commission_parent_bcy'] = 0;
                                        }
                                    }
                                    if($last_p_member_level['commission_parent_bcy'] > 0){
                                        if($last_p_member_level['commissionbcytype'] == 0){
                                            $commission_bcy = $commission_totalprice * $last_p_member_level['commission_parent_bcy'] * 0.01;
                                        }else{
                                            $commission_bcy = $last_p_member_level['commission_parent_bcy'] * $num;
                                        }

                                        if($commission_bcy > 0){
                                            Db::name('member_commission_record')->insert([
                                                'aid' => $aid,
                                                'mid' => $last_p_member['id'],
                                                'frommid' => $last_member['id'],
                                                'commission' => $commission_bcy,
                                                'remark' => '被用户(ID:'.$last_member['id'].'，级别：'.$last_member_level['name'].')超越，发被超越奖',
                                                'createtime' => time(),
                                                'orderid' => $orderid,
                                                'ogid' => $ogid,
                                            ]);
                                        }
                                        break;
                                    }
                                }
                            }

                            $lastid = $pv;
                        }
                    }
                }

                if(getcustom('commission_parent_pj_send_once') && $sysset['commission_parent_pj_send_once']==1) {
                    //平级奖只给最近的发一次，无限往上找直至找到一个
                    $path_arr = array_reverse(explode(',', $member['path']));
                    if ($path_arr) {
                        $lastid = 0;
                        foreach ($path_arr as $pv) {
                            if ($lastid > 0 && $pv > 0) {
                                $last_member = Db::name('member')->where('aid', $aid)->where('id', $lastid)->field('id,levelid')->find();
                                $last_member_level = $levelList[$last_member['levelid']];

                                $last_p_member = Db::name('member')->where('aid', $aid)->where('id', $pv)->field('id,levelid')->find();
                                $last_p_member_level = $levelList[$last_p_member['levelid']];

                                //判断当前人级别是否等于上级
                                if ($last_member_level['id'] == $last_p_member_level['id']) {
                                    $last_p_member_level['commissionpingjitype'] = $last_p_member_level['commissiontype'];
                                    if ($product['commissionpingjiset'] != 0) {
                                        if ($product['commissionpingjiset'] == 1) {
                                            $commissionbcydata1 = json_decode($product['commissionpingjidata1'], true);
                                            $last_p_member_level['commission_parent_pj'] = $commissionbcydata1[$last_p_member_level['id']]['commission'];
                                        } elseif ($product['commissionpingjiset'] == 2) {
                                            $commissionbcydata2 = json_decode($product['commissionpingjidata2'], true);
                                            $last_p_member_level['commission_parent_pj'] = $commissionbcydata2[$last_p_member_level['id']]['commission'];
                                            $last_p_member_level['commissionpingjitype'] = 1;
                                        } else {
                                            $last_p_member_level['commission_parent_pj'] = 0;
                                        }
                                    }
                                    if ($last_p_member_level['commission_parent_pj'] > 0) {
                                        if ($last_p_member_level['commissionpingjitype'] == 0) {
                                            $commission_pj = $commission_totalprice * $last_p_member_level['commission_parent_pj'] * 0.01;
                                        } else {
                                            $commission_pj = $last_p_member_level['commission_parent_pj'] * $num;
                                        }
                                        if ($commission_pj > 0) {
                                            Db::name('member_commission_record')->insert([
                                                'aid' => $aid,
                                                'mid' => $last_p_member['id'],
                                                'frommid' => $last_member['id'],
                                                'commission' => $commission_pj,
                                                'remark' => '团队平级奖',
                                                'createtime' => time(),
                                                'orderid' => $orderid,
                                                'ogid' => $ogid,
                                            ]);
                                        }
                                        break;
                                    }
                                }
                            }

                            $lastid = $pv;
                        }
                    }
                }

                //直推特殊奖--用户购买首单时，直推上级获得额外百分比的奖励，仅限于第一个订单
                if(getcustom('commission_zhitui_special_first_order') && $sysset['commission_zhitui_special_first_order']==1){
                    //直推人
                    if($pid = Db::name('member')->where('aid', $aid)->where('id', $member['id'])->value('pid')){
                        $pinfo = Db::name('member')->where('aid', $aid)->where('id', $pid)->find();
                        //获取直推人级别信息
                        $p_level = $levelList[$pinfo['levelid']];
                        //查询已支付的订单包括已支付的
                        $yyforder = Db::name('shop_order')->where('aid',$aid)->where('mid',$member['id'])->whereRaw('status in(1,2,3) or refund_status in(1,2)')->count();
                        //检测是否是第一个已支付的订单
                        if($yyforder <= 0 && $p_level['commission_zhitui_special_ratio'] > 0){
                            //直推特殊奖比例
                            $commission_zhitui_special_ratio = $p_level['commission_zhitui_special_ratio'] / 100;
                            $commission_zhitui_special = round(bcmul($commission_totalprice, $commission_zhitui_special_ratio, 3),2);
                            if ($commission_zhitui_special > 0) {
                                $id = Db::name('member_commission_record')->insert([
                                    'aid' => $aid,
                                    'mid' => $pinfo['id'],
                                    'frommid' => $member['id'],
                                    'commission' => $commission_zhitui_special,
                                    'remark' => '直推特殊奖',
                                    'createtime' => time(),
                                    'orderid' => $orderid,
                                    'ogid' => $ogid,
                                ]);
                            }
                        }
                    }
                }

                if(getcustom('member_level_parent_not_commission')){
                    //定制，购买人上级无任何分销奖励
                    if($product['parent_not_commission_json']){
                        $parent_not_commission = json_decode($product['parent_not_commission_json'],true);
                        if(isset($parent_not_commission[$agleveldata['id']])){
                            if($parent_not_commission[$agleveldata['id']] == -1){//跟随会员等级
                                if($agleveldata['parent_not_commission'] == 1) {
                                    $commission_data = [];
                                }
                            }elseif($parent_not_commission[$agleveldata['id']] == 1){//开启
                                $commission_data = [];
                            }
                        }else{
                            //商品未设置，使用会员等级设置
                            if($agleveldata['parent_not_commission'] == 1) {
                                $commission_data = [];
                            }
                        }
                    }else{
                        //商品未设置，使用会员等级设置
                        if($agleveldata['parent_not_commission'] == 1) {
                            $commission_data = [];
                        }
                    }
                }

                $ogupdate['parent1'] = $commission_data['parent1']??0;
                $ogupdate['parent2'] = $commission_data['parent2']??0;
                $ogupdate['parent3'] = $commission_data['parent3']??0;
                $ogupdate['parent4'] = $commission_data['parent4']??0;
                $ogupdate['parent1commission'] = $commission_data['parent1commission']??0;
                $ogupdate['parent2commission'] = $commission_data['parent2commission']??0;
                $ogupdate['parent3commission'] = $commission_data['parent3commission']??0;
                $ogupdate['parent4commission'] = $commission_data['parent4commission']??0;
                $ogupdate['parent1score'] = $commission_data['parent1score']??0;
                $ogupdate['parent2score'] = $commission_data['parent2score']??0;
                $ogupdate['parent3score'] = $commission_data['parent3score']??0;
                if(getcustom('commission_money_percent')){
                    $ogupdate['parent1money'] = $commission_data['parent1money']??0;
                    $ogupdate['parent2money'] = $commission_data['parent2money']??0;
                    $ogupdate['parent3money'] = $commission_data['parent3money']??0;
                }
                if(getcustom('commission_xianjin_percent')){
                    $ogupdate['parent1xianjin'] = $commission_data['parent1xianjin']??0;
                    $ogupdate['parent2xianjin'] = $commission_data['parent2xianjin']??0;
                    $ogupdate['parent3xianjin'] = $commission_data['parent3xianjin']??0;
                }
                //20250626新增 平级奖独立记录
                if(getcustom('commission_parent_pj')){
                    $ogupdate['parent_pj1'] = $commission_data['parent_pj1']??0;
                    $ogupdate['parent_pj2'] = $commission_data['parent_pj2']??0;
                    $ogupdate['parent_pj3'] = $commission_data['parent_pj3']??0;
                    $ogupdate['parent1commission_pj'] = $commission_data['parent1commission_pj']??0;
                    $ogupdate['parent2commission_pj'] = $commission_data['parent2commission_pj']??0;
                    $ogupdate['parent3commission_pj'] = $commission_data['parent3commission_pj']??0;
                }

                $istc1 = $commission_data['istc1']??0;
                $istc2 = $commission_data['istc2']??0;
                $istc3 = $commission_data['istc3']??0;
                if(getcustom('commission_butie')){
                    $butie_data = [];
                    $butie_data['parent1commission_butie'] = $commission_data['parent1commission_butie']??0;
                    $butie_data['parent2commission_butie'] = $commission_data['parent2commission_butie']??0;
                    $butie_data['parent3commission_butie'] = $commission_data['parent3commission_butie']??0;
                }
                if(getcustom('mendian_usercenter')){
                    $ogupdate['totalcommission_pj'] = $commission_data['total_pj'];
                }
                if(getcustom('commission_product_self_buy')){
                    //自购佣金加入order_goods表
                    $ogupdate['selfbuy_commission'] = $commission_data['selfbuy_commission']??0;
                }
            }
            if($ogupdate){
                Db::name('shop_order_goods')->where('id',$ogid)->update($ogupdate);
            }
            $totalcommission = 0;
            if($product['commissionset']!=4){
                if(getcustom('commission_fugou') && $isfg == 1){
                    if($ogupdate['parent1'] && ($ogupdate['parent1commission'] || $ogupdate['parent1score'])){
                        Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$ogupdate['parent1'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent1commission'],'score'=>$ogupdate['parent1score'],'remark'=>t('下级').'复购奖励','createtime'=>time()]);
                        $totalcommission += $ogupdate['parent1commission'];
                    }
                    if($ogupdate['parent2'] && ($ogupdate['parent2commission'] || $ogupdate['parent2score'])){
                        Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$ogupdate['parent2'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent2commission'],'score'=>$ogupdate['parent2score'],'remark'=>t('下二级').'复购奖励','createtime'=>time()]);
                        $totalcommission += $ogupdate['parent2commission'];
                    }
                    if($ogupdate['parent3'] && ($ogupdate['parent3commission'] || $ogupdate['parent3score'])){
                        Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$ogupdate['parent3'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent3commission'],'score'=>$ogupdate['parent3score'],'remark'=>t('下三级').'复购奖励','createtime'=>time()]);
                        $totalcommission += $ogupdate['parent3commission'];
                    }
                }else{
                    // 岗位提成
                    $gangwei_ceng = 0;
                    $gangwei_commission = 0;
                    $gangwei_arr = [];
                    if(getcustom('commission_log_remark_custom')){
                        $nickname = Db::name('member')->where('aid',$aid)->where('id',$ordermid)->value('nickname');
                        $nickname = removeEmoj($nickname);
                        if($product['bid'] > 0){
                            $bname = Db::name('business')->where('aid',$aid)->where('id',$product['bid'])->value('name');
                        }else{
                            $bname = Db::name('admin_set')->where('aid',$aid)->value('name');
                        }
                    }
                    if($ogupdate['parent1'] && ($ogupdate['parent1commission']>0 || $ogupdate['parent1score']>0 || $ogupdate['parent1money']>0 || $ogupdate['parent1xianjin']>0)){
                        $remrak1 = t('下级').'购买商品奖励';
                        if(getcustom('commission_log_remark_custom')){
                            $remrak1 = t('下级').$nickname.'在'.$bname.'购买'.$product['name'].'消费'.$ogdata['real_totalmoney'].'元';
                        }
                        $parent1_levelid = $commission_data['parent1_levelid']??0;
                        $data_c = ['aid'=>$aid,'mid'=>$ogupdate['parent1'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent1commission'],'score'=>$ogupdate['parent1score'],'remark'=>$remrak1,'createtime'=>time(),'levelid'=>$parent1_levelid];

                        if(getcustom('commission_butie')){
                            $data_c['butie'] = $butie_data['parent1commission_butie'];
                            $data_c['commission'] = bcsub($ogupdate['parent1commission'],$butie_data['parent1commission_butie'],2);
                        }
                        if(getcustom('commission_max_times')){
                            //分销份数限制
                            $data_c['proid'] = $product['id'];
                            $data_c['level'] = 1;
                        }
                        if(getcustom('pay_huifu_fenzhang')){
                            //如果该分销商已进件 且是商户独立收款，进行分佣使用分账
                            if($product['bid'] > 0){
                                $businessdata = Db::name('business')->where('aid',$aid)->where('id',$product['bid'])->field('huifu_business_status')->find();
                                $huifu_business_status = $businessdata['huifu_business_status'];//汇付独立收款
                                $huifu_send_commission = $businessdata['huifu_send_commission'];//汇付分账发放佣金
                                $delay_acct_flag = $businessdata['delay_acct_flag']; //分账类型 0实时
                                $parent1_huifu_id = Db::name('member')->where('aid',$data_c['aid'])->where('id',$ogupdate['parent1'])->value('huifu_id');
                                if($parent1_huifu_id && $huifu_business_status && $huifu_send_commission==1 && $delay_acct_flag==1)$data_c['to_fenzhang'] = 1;
                            }
                        }
                        if(getcustom('member_forzengxcommission')){
                            $protypes = [$product['product_type']];
                            if($protypes && $protypes[0] == 10){
                                $data_c['product_type'] = 10;//佣金类商品
                            }
                        }
                        if(getcustom('yx_collage_jipin_optimize')){
                            $data_c['collage_jipin_id'] = $commission_data['collage_jipin_id'] ?? 0;//即拼活动ID
                        }
                        if(getcustom('commission_money_percent')){
                            $data_c['money'] = $ogupdate['parent1money']??0;
                        }
                        if(getcustom('up_giveparent_userdata')){
                            //记录佣金类型，方便后续数据统计
                            $data_c['commission_type'] = \app\model\CommissionType::commission_type['parent1'];
                        }
                        if(getcustom('commission_xianjin_percent')){
                            //记录佣金类型，方便后续数据统计
                            $data_c['xianjin'] = $ogupdate['parent1xianjin']??0;;
                        }
                        if(getcustom('yx_cashback_time_fenxiao_speed')){
                            $data_c['cashback_speed'] = 1;
                        }

                        Db::name('member_commission_record')->insert($data_c);
                        $totalcommission += $ogupdate['parent1commission'];

                        // 岗位提成 gangwei_give_origin_status发放上级选择：0现上级，1原上级（购买人的原上级一个团队，现上级一个团队，没有原上级给现上级这个团队），2每层都发给原上级（没有原上级发给现上级）
                        if(getcustom('commission_gangwei') && $sysset['gangwei_give_origin_status'] == 0){
                            $last_pid = $ordermid;
                            $gangwei_ceng  = 1;
                            $parent1_info = Db::name('member')->where('aid',$aid)->where('id',$ogupdate['parent1'])->find();

                            // 计算直推人上一级（实为上二级）
                            if($parent1_info && $ogupdate['parent1commission']>0 && $parent1_info['pid']){
                                $parent1_info_last = Db::name('member')
                                    ->alias('m')
                                    ->join('member_level l','l.id=m.levelid')
                                    ->where('m.aid',$aid)
                                    ->where('m.id',$parent1_info['pid'])
                                    ->field('m.id,m.nickname,l.can_agent,l.gangwei1,l.gangwei2,l.gangwei_only_big,l.sort')
                                    ->find();
                                //只发等级高于或者等于下单直推人 gangwei_only_big=1
                                if($parent1_info_last && $parent1_info_last['gangwei1'] > 0 && $parent1_info_last['can_agent'] != 0
                                    && ($parent1_info_last['gangwei_only_big'] == 0 || ($parent1_info_last['gangwei_only_big'] == 1 && $parent1_info_last['sort'] >= $levelList[$parent1_info['levelid']]['sort']))){
                                    $commission_p = dd_money_format($ogupdate['parent1commission']*$parent1_info_last['gangwei1']/100,2);
                                    if($commission_p >= 1){
                                        $gangwei_commission = $commission_p;
                                    }
                                    if($gangwei_commission > 0){
                                        // 条件
                                        $zt_num = Db::name('member')->where('aid',$aid)->where('pid',$parent1_info['pid'])->count('id');
                                        $is_send = 0;
                                        if($sysset['gangwei_tndn_status'] == 1 ){//推N得N
                                            if($zt_num >= 10 || $zt_num > $gangwei_ceng){
                                                $is_send = 1;
                                            }
                                        }else{
                                            $is_send = 1;
                                        }
                                        if($is_send == 1){
                                            $data_p = ['aid'=>$aid,'mid'=>$parent1_info['pid'],'frommid'=>$parent1_info['id'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$gangwei_commission,'remark'=>'岗位提成','createtime'=>time()];
                                            $gangwei_arr[] = $data_p;
                                            $totalcommission += $gangwei_commission;
                                            $last_pid = $parent1_info['pid'];
                                        }else{
                                            // 紧缩关闭
                                            if($sysset['gangwei_jinsuo_status'] == 0 ){
                                                $gangwei_commission = 0;
                                            }
                                        }
                                    }

                                }
                                else{
                                    //直推人上一级（实为上二级）无岗位提成时需要标记，后面无限层从上三级开始重新计算
                                    $oneLevelNoGangwei = $ogupdate['parent1commission'];
                                    $last_pid = $parent1_info['pid'];
                                }
                            }

                        }
                    }
                    if($ogupdate['parent2'] && ($ogupdate['parent2commission']>0 || $ogupdate['parent2score']>0 || $ogupdate['parent2money']>0 || $ogupdate['parent2xianjin']>0)){
                        $remrak2 = t('下二级').'购买商品奖励';
                        if(getcustom('commission_log_remark_custom')){
                            $remrak2 = t('下二级').$nickname.'在'.$bname.'购买'.$product['name'].'消费'.$ogdata['real_totalmoney'].'元';
                        }
                        $parent2_levelid = $commission_data['parent2_levelid']??0;
                        $data_c = ['aid'=>$aid,'mid'=>$ogupdate['parent2'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent2commission'],'score'=>$ogupdate['parent2score'],'remark'=>$remrak2,'createtime'=>time(),'levelid'=>$parent2_levelid];
                        if(getcustom('commission_butie')){
                            $data_c['butie'] = $butie_data['parent2commission_butie'];
                            $data_c['commission'] =  bcsub($ogupdate['parent2commission'],$butie_data['parent2commission_butie'],2);
                        }
                        if(getcustom('commission_max_times')){
                            //分销份数限制
                            $data_c['proid'] = $product['id'];
                            $data_c['level'] = 2;
                        }
                        if(getcustom('pay_huifu_fenzhang')){
                            //如果该分销商已进件 且是商户独立收款，进行分佣使用分账
                            if($product['bid'] > 0){
                                $businessdata = Db::name('business')->where('aid',$aid)->where('id',$product['bid'])->field('huifu_business_status')->find();
                                $huifu_business_status = $businessdata['huifu_business_status'];//汇付独立收款
                                $huifu_send_commission = $businessdata['huifu_send_commission'];//汇付分账发放佣金
                                $delay_acct_flag = $businessdata['delay_acct_flag']; //分账类型 0实时
                                $parent2_huifu_id = Db::name('member')->where('aid',$data_c['aid'])->where('id',$ogupdate['parent2'])->value('huifu_id');
                                if($parent2_huifu_id && $huifu_business_status && $huifu_send_commission==1 && $delay_acct_flag==1)$data_c['to_fenzhang'] = 1;
                            }
                        }
                        if(getcustom('member_forzengxcommission')){
                            if($protypes && $protypes[0] == 10){
                                $data_c['product_type'] = 10;//佣金类商品
                            }
                        }
                        if(getcustom('commission_money_percent')){
                            $data_c['money'] = $ogupdate['parent2money']??0;
                        }
                        if(getcustom('up_giveparent_userdata')){
                            //记录佣金类型，方便后续数据统计
                            $data_c['commission_type'] = \app\model\CommissionType::commission_type['parent2'];
                        }
                        if(getcustom('commission_xianjin_percent')){
                            //记录佣金类型，方便后续数据统计
                            $data_c['xianjin'] = $ogupdate['parent2xianjin']??0;;
                        }
                        Db::name('member_commission_record')->insert($data_c);
                        $totalcommission += $ogupdate['parent2commission'];

                        // 岗位提成
                        if(getcustom('commission_gangwei') && $sysset['gangwei_give_origin_status'] == 0){
                            $gangwei_ceng  = 2;
                            $parent2_info = Db::name('member')->where('aid',$aid)->where('id',$ogupdate['parent2'])->find();

                            // 计算直推人上二级（实为上三级）
                            if($parent2_info && $parent2_info['pid']){
                                $parent2_info_last = Db::name('member')
                                    ->alias('m')
                                    ->join('member_level l','l.id=m.levelid')
                                    ->where('m.aid',$aid)
                                    ->where('m.id',$parent2_info['pid'])
                                    ->field('l.can_agent,l.gangwei1,l.gangwei2,l.gangwei_only_big,l.sort')
                                    ->find();
                                //只发等级高于或者等于下单直推人 gangwei_only_big=1
                                if($parent2_info_last && ($parent2_info_last['gangwei1'] > 0 || $parent2_info_last['gangwei2'] > 0) && $parent2_info_last['can_agent'] != 0
                                    && ($parent2_info_last['gangwei_only_big'] == 0 || ($parent2_info_last['gangwei_only_big'] == 1 && $parent2_info_last['sort'] >= $levelList[$parent1_info['levelid']]['sort']))){
                                    if($gangwei_commission > 0){
                                        $gangwei_commission = dd_money_format($gangwei_commission*$parent2_info_last['gangwei2']/100,2);
                                    }
                                    if($ogupdate['parent2commission'] > 0){
                                        $gangwei_commission_fenxiao = dd_money_format($ogupdate['parent2commission']*$parent2_info_last['gangwei1']/100,2);
                                        if($gangwei_commission_fenxiao > 0){
                                            $gangwei_commission += $gangwei_commission_fenxiao;
                                        }
                                    }

                                    if($gangwei_commission < 1){
                                        $gangwei_commission = 0;
                                    }
                                    if($gangwei_commission > 0){
                                        // 直推人数
                                        $zt_num = Db::name('member')->where('aid',$aid)->where('pid',$parent2_info['pid'])->count('id');

                                        $is_send = 0;
                                        if($sysset['gangwei_tndn_status'] == 1 ){
                                            if($zt_num >= 10 || $zt_num > $gangwei_ceng){
                                                $is_send = 1;
                                            }
                                        }else{
                                            $is_send = 1;
                                        }

                                        if($is_send == 1){
                                            $data_p = ['aid'=>$aid,'mid'=>$parent2_info['pid'],'frommid'=>$parent2_info['id'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$gangwei_commission,'remark'=>'岗位提成','createtime'=>time()];
                                            $gangwei_arr[] = $data_p;
                                            $totalcommission += $gangwei_commission;
                                            $last_pid = $parent2_info['pid'];
                                        }else{
                                            // 紧缩关闭
                                            if($sysset['gangwei_jinsuo_status'] == 0 ){
                                                $gangwei_commission = 0;
                                            }
                                        }
                                    }
                                }else{
                                    // 紧缩关闭
                                    if($sysset['gangwei_jinsuo_status'] == 0 ){
                                        $gangwei_commission = 0;
                                    }
                                }
                            }else{
                                // 紧缩关闭
                                if($sysset['gangwei_jinsuo_status'] == 0 ){
                                    $gangwei_commission = 0;
                                }
                            }
                        }
                    }
                    if($ogupdate['parent3'] && ($ogupdate['parent3commission']>0 || $ogupdate['parent3score']>0 || $ogupdate['parent3money']>0 || $ogupdate['parent3xianjin']>0)){
                        $remrak3 = t('下三级').'购买商品奖励';
                        if(getcustom('commission_log_remark_custom')){
                            $remrak3 = t('下三级').$nickname.'在'.$bname.'购买'.$product['name'].'消费'.$ogdata['real_totalmoney'].'元';
                        }
                        $parent3_levelid = $commission_data['parent3_levelid']??0;
                        $data_c = ['aid'=>$aid,'mid'=>$ogupdate['parent3'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent3commission'],'score'=>$ogupdate['parent3score'],'remark'=>$remrak3,'createtime'=>time(),'levelid'=>$parent3_levelid];
                        if(getcustom('commission_butie')){
                            $data_c['butie'] = $butie_data['parent3commission_butie'];
                            $data_c['commission'] =  bcsub($ogupdate['parent3commission'],$butie_data['parent3commission_butie'],2);
                        }
                        if(getcustom('commission_max_times')){
                            //分销份数限制
                            $data_c['proid'] = $product['id'];
                            $data_c['level'] = 3;
                        }
                        if(getcustom('pay_huifu_fenzhang')){
                            //如果该分销商已进件 且是商户独立收款，进行分佣使用分账
                            if($product['bid'] > 0){
                                $businessdata = Db::name('business')->where('aid',$aid)->where('id',$product['bid'])->field('huifu_business_status')->find();
                                $huifu_business_status = $businessdata['huifu_business_status'];//汇付独立收款
                                $huifu_send_commission = $businessdata['huifu_send_commission'];//汇付分账发放佣金
                                $delay_acct_flag = $businessdata['delay_acct_flag']; //分账类型 0实时
                                $parent3_huifu_id = Db::name('member')->where('aid',$data_c['aid'])->where('id',$ogupdate['parent3'])->value('huifu_id');
                                if($parent3_huifu_id && $huifu_business_status && $huifu_send_commission==1 && $delay_acct_flag==1)$data_c['to_fenzhang'] = 1;
                            }
                        }
                        if(getcustom('member_forzengxcommission')){
                            if($protypes && $protypes[0] == 10){
                                $data_c['product_type'] = 10;//佣金类商品
                            }
                        }
                        if(getcustom('commission_money_percent')){
                            $data_c['money'] = $ogupdate['parent3money']??0;
                        }
                        if(getcustom('up_giveparent_userdata')){
                            //记录佣金类型，方便后续数据统计
                            $data_c['commission_type'] = \app\model\CommissionType::commission_type['parent3'];
                        }
                        if(getcustom('commission_xianjin_percent')){
                            //记录佣金类型，方便后续数据统计
                            $data_c['xianjin'] = $ogupdate['parent3xianjin']??0;;
                        }
                        Db::name('member_commission_record')->insert($data_c);
                        $totalcommission += $ogupdate['parent3commission'];

                        // 岗位提成
                        if(getcustom('commission_gangwei') && $sysset['gangwei_give_origin_status'] == 0){
                            $gangwei_ceng  = 3;
                            $parent3_info = Db::name('member')->where('aid',$aid)->where('id',$ogupdate['parent3'])->find();
                            // 计算直推人上三级（实为上四级）
                            if($parent3_info && $parent3_info['pid']){
                                $parent3_info_last = Db::name('member')
                                    ->alias('m')
                                    ->join('member_level l','l.id=m.levelid')
                                    ->where('m.aid',$aid)
                                    ->where('m.id',$parent3_info['pid'])
                                    ->field('l.can_agent,l.gangwei1,l.gangwei2,l.gangwei_only_big,l.sort')
                                    ->find();
                                if($parent3_info_last && ($parent3_info_last['gangwei1'] > 0 || $parent3_info_last['gangwei2'] > 0) && $parent3_info_last['can_agent'] != 0
                                    && ($parent3_info_last['gangwei_only_big'] == 0 || ($parent3_info_last['gangwei_only_big'] == 1 && $parent3_info_last['sort'] >= $levelList[$parent1_info['levelid']]['sort']))){
                                    if($gangwei_commission > 0){
                                        $gangwei_commission = dd_money_format($gangwei_commission*$parent3_info_last['gangwei2']/100,2);
                                    }
                                    if($ogupdate['parent3commission'] > 0){
                                        $gangwei_commission_fenxiao = dd_money_format($ogupdate['parent3commission']*$parent3_info_last['gangwei1']/100,2);
                                        if($gangwei_commission_fenxiao > 0){
                                            $gangwei_commission += $gangwei_commission_fenxiao;
                                        }
                                    }

                                    if($gangwei_commission < 1){
                                        $gangwei_commission = 0;
                                    }
                                    if($gangwei_commission > 0){
                                        // 直推人数
                                        $zt_num = Db::name('member')->where('aid',$aid)->where('pid',$parent3_info['pid'])->count('id');
                                        $is_send = 0;
                                        if($sysset['gangwei_tndn_status'] == 1 ){
                                            if($zt_num >= 10 || $zt_num > $gangwei_ceng){
                                                $is_send = 1;
                                            }
                                        }else{
                                            $is_send = 1;
                                        }

                                        if($is_send == 1){
                                            $data_p = ['aid'=>$aid,'mid'=>$parent3_info['pid'],'frommid'=>$parent3_info['id'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$gangwei_commission,'remark'=>'岗位提成','createtime'=>time()];
                                            $gangwei_arr[] = $data_p;
                                            $totalcommission += $gangwei_commission;
                                            $last_pid = $parent3_info['pid'];
                                        }else{
                                            // 紧缩关闭
                                            if($sysset['gangwei_jinsuo_status'] == 0 ){
                                                $gangwei_commission = 0;
                                            }
                                        }
                                    }
                                }else{
                                    // 紧缩关闭
                                    if($sysset['gangwei_jinsuo_status'] == 0 ){
                                        $gangwei_commission = 0;
                                    }
                                }
                            }else{
                                // 紧缩关闭
                                if($sysset['gangwei_jinsuo_status'] == 0 ){
                                    $gangwei_commission = 0;
                                }
                            }
                        }
                    }
                    if($ogupdate['parent4'] && ($ogupdate['parent4commission']>0)){
                        $remark = '持续推荐奖励';
                        if(getcustom('commission_parent_pj_stop') || getcustom('commission_parent_pj_by_buyermid')){
                            $remark = '平级奖';
                        }
                        $data_c = ['aid'=>$aid,'mid'=>$ogupdate['parent4'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent4commission'],'score'=>0,'remark'=>$remark,'createtime'=>time()];
                        if(getcustom('member_forzengxcommission')){
                            if($protypes && $protypes[0] == 10){
                                $data_c['product_type'] = 10;//佣金类商品
                            }
                        }
                        if(getcustom('up_giveparent_userdata')){
                            //记录佣金类型，方便后续数据统计
                            $data_c['commission_type'] = \app\model\CommissionType::commission_type['parent4'];
                        }
                        Db::name('member_commission_record')->insert($data_c);
                        $totalcommission += $ogupdate['parent4commission'];
                    }
                    if(getcustom('commission_parent_pj')) {
                        if ($ogupdate['parent_pj1'] && ($ogupdate['parent1commission_pj'] > 0)) {
                            $remark = '平级一级奖励';
                            $data_c = ['aid' => $aid, 'mid' => $ogupdate['parent_pj1'], 'frommid' => $ordermid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent1commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                            Db::name('member_commission_record')->insert($data_c);
                            $totalcommission += $ogupdate['parent1commission_pj'];
                        }
                        if ($ogupdate['parent_pj2'] && ($ogupdate['parent2commission_pj'] > 0)) {
                            $remark = '平级二级奖励';
                            $data_c = ['aid' => $aid, 'mid' => $ogupdate['parent_pj2'], 'frommid' => $ordermid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent2commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                            Db::name('member_commission_record')->insert($data_c);
                            $totalcommission += $ogupdate['parent2commission_pj'];
                        }
                        if ($ogupdate['parent_pj3'] && ($ogupdate['parent3commission_pj'] > 0)) {
                            $remark = '平级三级奖励';
                            $data_c = ['aid' => $aid, 'mid' => $ogupdate['parent_pj3'], 'frommid' => $ordermid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent3commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                            Db::name('member_commission_record')->insert($data_c);
                            $totalcommission += $ogupdate['parent1commission_pj'];
                        }
                    }

                    // 岗位提成 发给原上级
                    if(getcustom('commission_gangwei') && in_array($sysset['gangwei_give_origin_status'], [1,2])){
                        $usermember = $member;
                        if($usermember['pid_origin']){
                            $usermember['pid'] = $usermember['pid_origin'];
                            $usermember['path'] = $usermember['path_origin'];
                        }

                        if($sysset['fenxiao_manage_status']){
                            $commission_data_l = \app\common\Fenxiao::fenxiao_jicha($sysset,$usermember,$product,$num,$commission_totalprice);
                        }else{
                            $commission_data_l = \app\common\Fenxiao::fenxiao($sysset,$usermember,$product,$num,$commission_totalprice,$isfg,$istc1,$istc2,$istc3);
                        }
                        if($commission_data_l['parent1'] && $commission_data_l['parent1commission']>0){

                            $last_pid = $ordermid;
                            $gangwei_ceng  = 1;
                            $parent1_info = Db::name('member')->where('aid',$aid)->where('id',$commission_data_l['parent1'])->find();
                            if($sysset['gangwei_give_origin_status'] == 2){
                                if($parent1_info['pid_origin']){
                                    $parent1_info['pid'] = $parent1_info['pid_origin'];
                                }
                            }

                            // 计算上一级
                            if($parent1_info && $commission_data_l['parent1commission']>0 && $parent1_info['pid']){
                                $parent1_info_last = Db::name('member')
                                    ->alias('m')
                                    ->join('member_level l','l.id=m.levelid')
                                    ->where('m.aid',$aid)
                                    ->where('m.id',$parent1_info['pid'])
                                    ->field('l.can_agent,l.gangwei1,l.gangwei2,l.gangwei_only_big,l.sort')
                                    ->find();
                                if($parent1_info_last && $parent1_info_last['gangwei1'] > 0 && $parent1_info_last['can_agent'] != 0){

                                    $commission_p = dd_money_format($commission_data_l['parent1commission']*$parent1_info_last['gangwei1']/100,2);
                                    if($commission_p >= 1){
                                        $gangwei_commission = $commission_p;
                                    }
                                    if($gangwei_commission > 0){
                                        // 条件
                                        $zt_num = Db::name('member')->where('aid',$aid)->where('pid',$parent1_info['pid'])->count('id');
                                        $is_send = 0;
                                        if($sysset['gangwei_tndn_status'] == 1 ){
                                            if($zt_num >= 10 || $zt_num > $gangwei_ceng){
                                                $is_send = 1;
                                            }
                                        }else{
                                            $is_send = 1;
                                        }
                                        if($is_send == 1){
                                            $data_p = ['aid'=>$aid,'mid'=>$parent1_info['pid'],'frommid'=>$parent1_info['id'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$gangwei_commission,'remark'=>'岗位提成','createtime'=>time()];
                                            $gangwei_arr[] = $data_p;
                                            $totalcommission += $gangwei_commission;
                                            $last_pid = $parent1_info['pid'];
                                        }else{
                                            // 紧缩关闭
                                            if($sysset['gangwei_jinsuo_status'] == 0 ){
                                                $gangwei_commission = 0;
                                            }
                                        }
                                    }

                                }
                            }

                        }
                        if($commission_data_l['parent2']){

                            $gangwei_ceng  = 2;
                            $parent2_info = Db::name('member')->where('aid',$aid)->where('id',$commission_data_l['parent2'])->find();
                            if($sysset['gangwei_give_origin_status'] == 2){
                                if($parent2_info['pid_origin']){
                                    $parent2_info['pid'] = $parent2_info['pid_origin'];
                                }
                            }
                            // 计算上二级
                            if($parent2_info &&  $parent2_info['pid']){
                                $parent2_info_last = Db::name('member')
                                    ->alias('m')
                                    ->join('member_level l','l.id=m.levelid')
                                    ->where('m.aid',$aid)
                                    ->where('m.id',$parent2_info['pid'])
                                    ->field('l.can_agent,l.gangwei1,l.gangwei2,l.gangwei_only_big,l.sort')
                                    ->find();
                                //只发等级高于或者等于下单直推人 gangwei_only_big=1
                                if($parent2_info_last && ($parent2_info_last['gangwei1'] > 0 || $parent2_info_last['gangwei2'] > 0) && $parent2_info_last['can_agent'] != 0
                                    && ($parent2_info_last['gangwei_only_big'] == 0 || ($parent2_info_last['gangwei_only_big'] == 1 && $parent2_info_last['sort'] >= $levelList[$parent1_info['levelid']]['sort']))){
                                    if($gangwei_commission > 0){
                                        $gangwei_commission = dd_money_format($gangwei_commission*$parent2_info_last['gangwei2']/100,2);
                                    }
                                    if($commission_data_l['parent2commission'] > 0){
                                        $gangwei_commission_fenxiao = dd_money_format($commission_data_l['parent2commission']*$parent2_info_last['gangwei1']/100,2);
                                        if($gangwei_commission_fenxiao > 0){
                                            $gangwei_commission += $gangwei_commission_fenxiao;
                                        }
                                    }

                                    if($gangwei_commission < 1){
                                        $gangwei_commission = 0;
                                    }
                                    if($gangwei_commission > 0){
                                        // 直推人数
                                        $zt_num = Db::name('member')->where('aid',$aid)->where('pid',$parent2_info['pid'])->count('id');

                                        $is_send = 0;
                                        if($sysset['gangwei_tndn_status'] == 1 ){
                                            if($zt_num >= 10 || $zt_num > $gangwei_ceng){
                                                $is_send = 1;
                                            }
                                        }else{
                                            $is_send = 1;
                                        }

                                        if($is_send == 1){
                                            $data_p = ['aid'=>$aid,'mid'=>$parent2_info['pid'],'frommid'=>$parent2_info['id'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$gangwei_commission,'remark'=>'岗位提成','createtime'=>time()];
                                            $gangwei_arr[] = $data_p;
                                            $totalcommission += $gangwei_commission;
                                            $last_pid = $parent2_info['pid'];
                                        }else{
                                            // 紧缩关闭
                                            if($sysset['gangwei_jinsuo_status'] == 0 ){
                                                $gangwei_commission = 0;
                                            }
                                        }
                                    }
                                }else{
                                    // 紧缩关闭
                                    if($sysset['gangwei_jinsuo_status'] == 0 ){
                                        $gangwei_commission = 0;
                                    }
                                }
                            }else{
                                // 紧缩关闭
                                if($sysset['gangwei_jinsuo_status'] == 0 ){
                                    $gangwei_commission = 0;
                                }
                            }

                        }
                        if($commission_data_l['parent3']){

                            $gangwei_ceng  = 3;
                            $parent3_info = Db::name('member')->where('aid',$aid)->where('id',$commission_data_l['parent3'])->find();
                            if($sysset['gangwei_give_origin_status'] == 2){
                                if($parent3_info['pid_origin']){
                                    $parent3_info['pid'] = $parent3_info['pid_origin'];
                                }
                            }

                            if($parent3_info && $parent3_info['pid']){
                                $parent3_info_last = Db::name('member')
                                    ->alias('m')
                                    ->join('member_level l','l.id=m.levelid')
                                    ->where('m.aid',$aid)
                                    ->where('m.id',$parent3_info['pid'])
                                    ->field('l.can_agent,l.gangwei1,l.gangwei2,l.gangwei_only_big,l.sort')
                                    ->find();
                                //只发等级高于或者等于下单直推人 gangwei_only_big=1
                                if($parent3_info_last && ($parent3_info_last['gangwei1'] > 0 || $parent3_info_last['gangwei2'] > 0) && $parent3_info_last['can_agent'] != 0
                                    && ($parent3_info_last['gangwei_only_big'] == 0 || ($parent3_info_last['gangwei_only_big'] == 1 && $parent3_info_last['sort'] >= $levelList[$parent1_info['levelid']]['sort']))){
                                    if($gangwei_commission > 0){
                                        $gangwei_commission = dd_money_format($gangwei_commission*$parent3_info_last['gangwei2']/100,2);
                                    }
                                    if($commission_data_l['parent3commission'] > 0){
                                        $gangwei_commission_fenxiao = dd_money_format($commission_data_l['parent3commission']*$parent3_info_last['gangwei1']/100,2);
                                        if($gangwei_commission_fenxiao > 0){
                                            $gangwei_commission += $gangwei_commission_fenxiao;
                                        }
                                    }

                                    if($gangwei_commission < 1){
                                        $gangwei_commission = 0;
                                    }
                                    if($gangwei_commission > 0){
                                        // 直推人数
                                        $zt_num = Db::name('member')->where('aid',$aid)->where('pid',$parent3_info['pid'])->count('id');
                                        $is_send = 0;
                                        if($sysset['gangwei_tndn_status'] == 1 ){
                                            if($zt_num >= 10 || $zt_num > $gangwei_ceng){
                                                $is_send = 1;
                                            }
                                        }else{
                                            $is_send = 1;
                                        }

                                        if($is_send == 1){
                                            $data_p = ['aid'=>$aid,'mid'=>$parent3_info['pid'],'frommid'=>$parent3_info['id'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$gangwei_commission,'remark'=>'岗位提成','createtime'=>time()];
                                            $gangwei_arr[] = $data_p;
                                            $totalcommission += $gangwei_commission;
                                            $last_pid = $parent3_info['pid'];
                                        }else{
                                            // 紧缩关闭
                                            if($sysset['gangwei_jinsuo_status'] == 0 ){
                                                $gangwei_commission = 0;
                                            }
                                        }
                                    }
                                }else{
                                    // 紧缩关闭
                                    if($sysset['gangwei_jinsuo_status'] == 0 ){
                                        $gangwei_commission = 0;
                                    }
                                }
                            }else{
                                // 紧缩关闭
                                if($sysset['gangwei_jinsuo_status'] == 0 ){
                                    $gangwei_commission = 0;
                                }
                            }

                        }
                    }
                    // 岗位提成 无限层
                    if($gangwei_commission == 0 && $oneLevelNoGangwei) $gangwei_commission = $oneLevelNoGangwei;//上3级无岗位提成 只有一级分销的情况
                    if(getcustom('commission_gangwei') && $gangwei_commission > 0 && $last_pid){
                        $new_path = \app\common\Member::getPids($aid,$sysset,$last_pid);
                        $pids = explode(',', $new_path);
                        $pids = array_reverse($pids);
                        foreach ($pids as $pid_v) {

                            // 直推人数
                            $zt_num = Db::name('member')->where('aid',$aid)->where('pid',$pid_v)->count('id');

                            $is_send = 0;
                            if($sysset['gangwei_tndn_status'] == 1 ){
                                if($zt_num >= 10 || $zt_num > $gangwei_ceng){
                                    $is_send = 1;
                                }
                            }else{
                                $is_send = 1;
                            }
                            if($is_send == 1){
                                $member_info = Db::name('member')->alias('m')
                                    ->join('member_level l','l.id=m.levelid')
                                    ->where('m.aid',$aid)
                                    ->where('m.id',$pid_v)
                                    ->field('l.can_agent,l.gangwei1,l.gangwei2,l.gangwei_only_big,l.sort')
                                    ->find();
                                if($member_info && $member_info['can_agent']!=0 && $member_info['gangwei2'] > 0 && ($member_info['gangwei_only_big'] == 0 || ($member_info['gangwei_only_big'] == 1 && $member_info['sort'] >= $levelList[$parent1_info['levelid']]['sort']))){
                                    // 金额
                                    $gangwei_commission = dd_money_format($gangwei_commission*$member_info['gangwei2']/100,2);
                                    if($gangwei_commission < 1){
                                        break;
                                    }

                                    $data_p = ['aid'=>$aid,'mid'=>$pid_v,'frommid'=>$last_pid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$gangwei_commission,'remark'=>'岗位提成','createtime'=>time()];
                                    $gangwei_arr[] = $data_p;
                                    $totalcommission += $gangwei_commission;
                                    $last_pid = $pid_v;
                                }else{
                                    // 紧缩关闭
                                    if($sysset['gangwei_jinsuo_status'] == 0 ){
                                        $gangwei_commission = 0;
                                    }
                                }
                            }else{
                                // 紧缩关闭
                                if($sysset['gangwei_jinsuo_status'] == 0 ){
                                    $gangwei_commission = 0;
                                }
                            }
                            $gangwei_ceng ++;
                        }
                    }
                    // dump($gangwei_arr);die;
                    if(getcustom('commission_gangwei') && !empty($gangwei_arr)){
                        Db::name('member_commission_record')->insertAll($gangwei_arr);
                    }


                    if(getcustom('commission_bole')){
                        //分销伯乐奖
                        if($commission_data['parent2_bole'] && $commission_data['parent2commission_bole']>0){
                            $data_c = ['aid'=>$aid,'mid'=>$commission_data['parent2_bole'],'frommid'=>$ogupdate['parent1'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$commission_data['parent2commission_bole'],'remark'=>'分销伯乐奖','createtime'=>time()];
                            Db::name('member_commission_record')->insert($data_c);
                        }
                        if($commission_data['parent3_bole'] && $commission_data['parent3commission_bole']>0){
                            $data_c = ['aid'=>$aid,'mid'=>$commission_data['parent3_bole'],'frommid'=>$ogupdate['parent2'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$commission_data['parent3commission_bole'],'remark'=>'分销伯乐奖','createtime'=>time()];
                            Db::name('member_commission_record')->insert($data_c);
                        }
                        if($commission_data['parent4_bole'] && $commission_data['parent4commission_bole']>0){
                            $data_c = ['aid'=>$aid,'mid'=>$commission_data['parent4_bole'],'frommid'=>$ogupdate['parent3'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$commission_data['parent4commission_bole'],'remark'=>'分销伯乐奖','createtime'=>time()];
                            Db::name('member_commission_record')->insert($data_c);
                        }
                    }

                    if(getcustom('commission_product_self_buy')){
                        //自购佣金加入佣金记录
                        if($commission_data['selfbuy_commission']>0 && $ordermid){
                            $data_selfbuy = ['aid'=>$aid,'mid'=>$ordermid,'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$commission_data['selfbuy_commission'],'remark'=>'自购佣金','createtime'=>time()];
                            Db::name('member_commission_record')->insert($data_selfbuy);
                        }
                    }
                }
                if(getcustom('buy_selectmember')){
                    $checkmemid = $order['checkmemid'];
                }
                if($checkmemid && $commission_totalprice > 0){
                    $checkmember = Db::name('member')->where('aid',$aid)->where('id',$checkmemid)->find();
                    if($checkmember){
                        $buyselect_commission = $levelList[$checkmember['levelid']]['buyselect_commission'];
                        $checkmemcommission = $buyselect_commission * $commission_totalprice * 0.01;
                        Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$checkmember['id'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$checkmemcommission,'score'=>0,'remark'=>'购买商品时指定奖励','createtime'=>time()]);
                    }
                }
            }
            if(getcustom('zhitui_pj')){
                //直推平级奖
                $zhitui_pj = json_decode($product['zhitui_pj'],true);
                $parent = Db::name('member')->where('id',$member['pid'])->find();
                $member_levelid = $member['levelid'];
                $zhitui_pj_commission = $zhitui_pj[$member_levelid]??0;
                $zhitui_pj_commission = bcmul($zhitui_pj_commission,$num,2);
                if($parent && $parent['levelid']==$member_levelid && $zhitui_pj_commission>0){
                    Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$parent['id'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$zhitui_pj_commission,'score'=>0,'remark'=>'直推平级奖','createtime'=>time()]);
                }
            }


            if($product['commissionset4']==1 && $product['lvprice']==1){ //极差分销
                if(getcustom('jicha_removecommission')){ //算极差时先减去分销的钱
                    $commission_totalpriceCache = $commission_totalpriceCache - $totalcommission;
                }
                if($member['path']){
                    $send_origin = 0;
                    if(getcustom('lvprice_jicha_lv')){
                        //是否发放给原上级
                        $send_origin = $product['lvprice_jicha_origin'];
                    }
                    if($send_origin){
                        //$parentList = Db::name('member')->where('id','in',$this->member['path_origin'])->order(Db::raw('field(id,'.$this->member['path_origin'].')'))->select()->toArray();
                        $parentList = \app\common\Member::queryOriginPath($aid,mid,$product['lvprice_jicha_lv']?:50);
                    }else{
                        $parentList = Db::name('member')->where('id','in',$member['path'])->order(Db::raw('field(id,'.$member['path'].')'))->select()->toArray();
                        $parentList = array_reverse($parentList);
                    }

                    if($parentList) {
                        $lvprice_data = json_decode($guige['lvprice_data'], true);
                        $nowprice = $commission_totalpriceCache;
                        $giveidx = 0;
                        $isclose_jicha = 0;
                        if (getcustom('member_level_close_jicha')){
                            if($agleveldata['isclose_jicha']==1) $isclose_jicha = 1;
                        }

                        foreach($parentList as $k=>$parent){
                            if($parent['levelid'] && $lvprice_data[$parent['levelid']]){
                                $thisprice = floatval($lvprice_data[$parent['levelid']]) * $num;
                                if($nowprice > $thisprice){
                                    $commission = $nowprice - $thisprice;
                                    $nowprice = $thisprice;
                                    $giveidx++;

                                    if(!$isclose_jicha) {
                                        $remark = t('下级') . '购买商品差价';
                                        if(t('等级价格极差分销')!='等级价格极差分销'){
                                            $remark = t('下级') . '购买商品'.t('等级价格极差分销');
                                        }
                                        $data_c = ['aid' => $aid, 'mid' => $parent['id'], 'frommid' => $ordermid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $commission, 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                                        if(getcustom('up_giveparent_userdata')){
                                            //记录佣金类型，方便后续数据统计
                                            $data_c['commission_type'] = \app\model\CommissionType::commission_type['parent_jicha'];
                                        }
                                        Db::name('member_commission_record')->insert($data_c);
                                    }

                                    //平级奖
                                    if(getcustom('commission_parent_pj') && getcustom('commission_parent_pj_jicha')){
                                        if($parentList[$k+1] && $parentList[$k+1]['levelid'] == $parent['levelid']){
                                            $parent2 = $parentList[$k+1];
                                            $parent2lv = $levelList[$parent2['levelid']];
                                            $parent2lv['commissionpingjitype'] = $parent2lv['commissiontype'];
                                            if($product['commissionpingjiset'] != 0){
                                                if($product['commissionpingjiset'] == 1){
                                                    $commissionpingjidata1 = json_decode($product['commissionpingjidata1'],true);
                                                    $parent2lv['commission_parent_pj'] = $commissionpingjidata1[$parent2lv['id']]['commission'];
                                                }elseif($product['commissionpingjiset'] == 2){
                                                    $commissionpingjidata2 = json_decode($product['commissionpingjidata2'],true);
                                                    $parent2lv['commission_parent_pj'] = $commissionpingjidata2[$parent2lv['id']]['commission'];
                                                    $parent2lv['commissionpingjitype'] = 1;
                                                }else{
                                                    $parent2lv['commission_parent_pj'] = 0;
                                                }
                                            }
                                            if($parent2lv['commission_parent_pj'] > 0) {
                                                if($parent2lv['commissionpingjitype']==0){
                                                    $pingjicommission = $commission * $parent2lv['commission_parent_pj'] * 0.01;
                                                } else {
                                                    $pingjicommission = $parent2lv['commission_parent_pj'];
                                                }
                                                if($pingjicommission > 0){
                                                    Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$parent2['id'],'frommid'=>$parent['id'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$pingjicommission,'score'=>0,'remark'=>'平级奖励','createtime'=>time()]);
                                                }
                                            }
                                        }
                                    }
                                    if(getcustom('lvprice_jicha_lv')){
                                        //发放代数限制
                                        if($product['lvprice_jicha_lv']>0 && $giveidx>=$product['lvprice_jicha_lv']){
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if(getcustom('product_commission_mid')){
                if($product['commission_mid']){ //商品指定会员佣金奖励
                    $commission_mid = json_decode($product['commission_mid'],true);
                    $commission_mid = array_filter($commission_mid);
                    foreach($commission_mid as $kcm=>$vcm){
                        $money=0;
                        if($vcm['mid'] && ($vcm['percent']>0 || $vcm['money']>0)){
                            if($vcm['percent']>0) $money += $commission_totalprice*$vcm['percent']/100;
                            $money += $vcm['money'] * $num;
                            $money = round($money,2);
                            if($money > 0){
                                Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$vcm['mid'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$money,'score'=>0,'remark'=>$vcm['name'],'createtime'=>time()]);
                            }
                        }
                    }
                }
            }

            if(getcustom('commission_givedown')){
                $commission_recordlist = Db::name('member_commission_record')->field("mid,sum(commission) totalcommission")->where('aid',$aid)->where('orderid',$orderid)->where('ogid',$ogid)->where('type','shop')->where('commission','>',0)->group('mid')->select()->toArray();
                foreach($commission_recordlist as $record){
                    $thismember = Db::name('member')->where('id',$record['mid'])->find();
                    $memberlevel = $levelList[$thismember['levelid']];
                    if($memberlevel && ($memberlevel['givedown_percent'] > 0 || $memberlevel['givedown_commission'] > 0)){
                        $downmemberlist = Db::name('member')->where('aid',$aid)->where('pid',$record['mid'])->select()->toArray();
                        if(!$downmemberlist) continue;
                        $downcommission = $memberlevel['givedown_commission'] / count($downmemberlist) + $record['totalcommission'] * $memberlevel['givedown_percent'] * 0.01 / count($downmemberlist);
                        foreach($downmemberlist as $downmember){
                            Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$downmember['id'],'frommid'=>$thismember['id'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$downcommission,'score'=>0,'remark'=>$memberlevel['givedown_txt'],'createtime'=>time()]);
                        }
                    }
                }
            }

            /****************************2、重新计算分红***********************************/
            $info = Db::name('sysset')->where('name','webinfo')->find();
            $webinfo = json_decode($info['value'],true);
            if(!$webinfo['jiesuan_fenhong_type']) {
                Db::name('shop_order_goods')->where('orderid',$order['id'])->update(['isfenhong'=>0,'isteamfenhong'=>0]);
                //退回之前计算的分红
                Db::name('member_fenhonglog')
                    ->where('aid',$aid)
                    ->where('module','shop')
                    ->where('ogids',$ogid)
                    ->where('status',0)
                    ->update(['status'=>2]);
                \app\common\Fenhong::jiesuan_single($aid, $orderid, 'shop');
            }
        }

    }


    // $yj:1 //预计  0：发放，当月之前
    public static function getTeamYejiJiangiActivity($aid,$member,$yeji_set,$yj=1){
        $is_include_self = getcustom('yx_team_yeji_activity_include_self');
        $is_jicha_custom = getcustom('yx_team_yeji_activity_jicha');
        if(getcustom('yx_team_yeji_activity')){
            $mid = $member['id'];
            $fenhong = 0;
            $xuni_yeji = 0;  //虚拟业绩
            $config = json_decode($yeji_set['config_data'],true);
            if($is_jicha_custom && $yeji_set['is_jicha']){
                //查询是pid是当前会员的
                $sub_list = Db::name('member')->where('aid',$aid)->where('pid',$mid)->field('id,levelid,nickname')->select()->toArray();
              
                $sub_yeji = [];
                $this_total_yeji = 0;
                $this_member_levelup_time = 0;
                // $this_member_levelup_time = Db::name('member_levelup_order')
                //             ->where('aid',$aid)
                //             ->where('mid',$member['id'])
                //             ->where('levelid',$member['levelid'])
                //             ->where('status',2)
                //             ->field('levelup_time')
                //             ->order('levelup_time desc')
                //             ->value('levelup_time');

                foreach($sub_list as $pk=>$sub){
//                    $this_show_levelid = array_keys($config);
//                    if(!in_array($sub['levelid'],$this_show_levelid)){
//                           continue;
//                    }
                  
                    //分别求下级的业绩
                    $levelids = [];
                    if($config[$sub['levelid']]['levelnum'] > 0) {
                        //设置层级，使用层级查询，
                        $deep = intval($config[$member['levelid']]['levelnum']);
                        $sub_downmids = \app\common\Member::getteammids($aid,$sub['id'],$deep,$levelids);
                    } else{
                        //不设置层级，只用快速查询，因为上面方法非常慢
                        $down_where = [];
                        $down_where[] = ['aid','=',$member['aid']];
//                        $down_where[] = ['id','<>',$sub['id']];
                        $down_where[] = Db::raw('find_in_set('.$sub['id'].',path)');
                        $sub_downmids = Db::name('member')->where($down_where)->column('id');
                    }
                    // $sub_downmids[] = $sub['id'];
                    //升级时间
                    // $levelup_order = Db::name('member_levelup_order')
                    //     ->where('aid',$aid)
                    //     ->where('mid',$sub['id'])
                    //     ->where('levelid',$sub['levelid'])
                    //     ->where('status',2)
                    //     ->field('levelup_time')
                    //     ->order('levelup_time desc')
                    //     ->find();
                    $this_levelup_time = $this_member_levelup_time;
//                    if(!$this_levelup_time){
//                        continue;
//                    }
                    $after_sj_yeji_where = [];
                    if($yj ==1){ //预计是当月获得当
                        if($yeji_set['jiesuan_type'] == 1) {//按月
                            $after_month_start = strtotime(date('Y-m-01 00:00:00'));
                            $after_month_end  = strtotime(date('Y-m-t 23:59:59'));
                            if($this_levelup_time > $after_month_start ){
                                $after_month_start =   $this_levelup_time;
                            }
                            $after_sj_yeji_where[] = ['createtime','between',[$after_month_start,$after_month_end]];
                        }
                        elseif($yeji_set['jiesuan_type'] == 2) {//按年
                            $after_year_start=strtotime(date('Y') . '-01-01 00:00:00');
                            $after_year_end=strtotime(date('Y') . '-12-31 23:59:59');
                            if($this_levelup_time > $after_year_start ) {
                                $after_year_start  = $this_levelup_time;
                            }
                            $after_sj_yeji_where[] = ['createtime','between',[$after_year_start,$after_year_end]];
                        }
                        elseif($yeji_set['jiesuan_type'] == 3) {//按季度
                            $this_season_start=strtotime(date('Y-m-01 00:00:00'));
                            $this_season_end=strtotime(date('Y-m-t 23:59:59',strtotime('+3 month')));
                            if($this_levelup_time > $this_season_start ) {
                                $this_season_start =  $this_levelup_time;
                            }
                            $after_sj_yeji_where[] = ['createtime','between',[$this_season_start,$this_season_end]];
                        }
                    }
                    else{// //非预计 是上月
                        if($yeji_set['jiesuan_type'] == 1){//按月
                            $start = strtotime(date('Y-m-01 00:00:00',strtotime('-1 month')));
                            $end  = strtotime(date('Y-m-t 23:59:59',strtotime('-1 month')));
                            //升级时间大于结束时间，无业绩
                        }elseif($yeji_set['jiesuan_type'] == 2){//按年
                            $start=strtotime((date('Y')-1) . '-01-01 00:00:00');
                            $end=strtotime((date('Y')-1) . '-12-31 23:59:59');
                        }elseif($yeji_set['jiesuan_type'] == 3){//按季度
                            $start=strtotime(date('Y-m-01 00:00:00',strtotime('-3 month')));
                            $end=strtotime(date('Y-m-t 23:59:59',strtotime('-1 month')));
                        }
                        if($this_levelup_time > $end){
                            $start = 0;
                            $end = 0;
                        }elseif ($this_levelup_time > $start && $this_levelup_time < $end){
                            $start =  $this_levelup_time;
                        }
                        $after_sj_yeji_where[] = ['createtime','between',[$start,$end]];
                    }
                    if($yeji_set['fwtype'] ==1){//0全部商品 1指定商品
                        $after_sj_yeji_where[] = ['proid','in',$yeji_set['proids']];
                    }
                    //团队自己的业绩，不计算包含自己
                    $after_sj_yeji= Db::name('shop_order_goods')->where('aid',$aid)->where('mid','in',$sub_downmids)->where('status','in',['1','2','3'])->where($after_sj_yeji_where)->sum('real_totalprice');
                    $sub_downmids[] = $sub['id'];
                    //这里是登录会员的，需要包含下级自己的
                    $after_total_yeji = Db::name('shop_order_goods')->where('aid',$aid)->where('mid','in',$sub_downmids)->where('status','in',['1','2','3'])->where($after_sj_yeji_where)->sum('real_totalprice');
                    $this_total_yeji += $after_total_yeji;//下一级的总业绩

                    $jt_range = $config[$sub['levelid']]['range'];
                    $this_ratio = 0;
                    if($jt_range){
                        foreach($jt_range as $rk=> $range){
                            if( $range['start'] <= $after_sj_yeji && $after_sj_yeji < $range['end']){
                                $this_ratio = $range['ratio'];
                            }
                        }
                    }
                    $fenhong=0;
                    if($this_ratio > 0)$fenhong = $after_sj_yeji * $this_ratio * 0.01;
                    
                    $sub_yeji[$sub['id']] = ['yeji' => $after_sj_yeji,'fenhong' => $fenhong,'ratio' => $this_ratio];
                }
                //自己购买的业绩
                if(false){
                    if($yj ==1){ //预计是当月获得当
                        if($yeji_set['jiesuan_type'] == 1) {//按月
                            $after_month_start = strtotime(date('Y-m-01 00:00:00'));
                            $after_month_end  = strtotime(date('Y-m-t 23:59:59'));
                            if($this_member_levelup_time > $after_month_start ){
                                $after_month_start =   $this_levelup_time;
                            }
                            $this_member_yeji_where[] = ['createtime','between',[$after_month_start,$after_month_end]];
                        }
                        elseif($yeji_set['jiesuan_type'] == 2) {//按年
                            $after_year_start=strtotime(date('Y') . '-01-01 00:00:00');
                            $after_year_end=strtotime(date('Y') . '-12-31 23:59:59');
                            if($this_member_levelup_time > $after_year_start ) {
                                $after_year_start  = $this_levelup_time;
                            }
                            $this_member_yeji_where[] = ['createtime','between',[$after_year_start,$after_year_end]];
                        }
                        elseif($yeji_set['jiesuan_type'] == 3) {//按季度
                            $this_season_start=strtotime(date('Y-m-01 00:00:00'));
                            $this_season_end=strtotime(date('Y-m-t 23:59:59',strtotime('+3 month')));
                            if($this_member_levelup_time > $this_season_start ) {
                                $this_season_start =  $this_levelup_time;
                            }
                            $this_member_yeji_where[] = ['createtime','between',[$this_season_start,$this_season_end]];
                        }
                    }
                    else{// //非预计 是上月
                        if($yeji_set['jiesuan_type'] == 1){//按月
                            $start = strtotime(date('Y-m-01 00:00:00',strtotime('-1 month')));
                            $end  = strtotime(date('Y-m-t 23:59:59',strtotime('-1 month')));
                            //升级时间大于结束时间，无业绩
                        }elseif($yeji_set['jiesuan_type'] == 2){//按年
                            $start=strtotime((date('Y')-1) . '-01-01 00:00:00');
                            $end=strtotime((date('Y')-1) . '-12-31 23:59:59');
                        }elseif($yeji_set['jiesuan_type'] == 3){//按季度
                            $start=strtotime(date('Y-m-01 00:00:00',strtotime('-3 month')));
                            $end=strtotime(date('Y-m-t 23:59:59',strtotime('-1 month')));
                        }
                        if($this_member_levelup_time > $end){
                            $start = 0;
                            $end = 0;
                        }elseif ($this_member_levelup_time > $start && $this_member_levelup_time < $end){
                            $start =  $this_member_levelup_time;
                        }
                        $this_member_yeji_where[] = ['createtime','between',[$start,$end]];
                    }
                    $this_member_yeji =   Db::name('shop_order_goods')->where('aid',$aid)->where('mid','=',$mid)->where('status','in',['1','2','3'])->where($this_member_yeji_where)->sum('real_totalprice');
                    $this_total_yeji += $this_member_yeji;
                }
               
                $jt_range = $config[$member['levelid']]['range'];
                $this_ratio = 0;
                if($jt_range){
                    foreach($jt_range as $rk=> $range){
                        if( $range['start'] <= $this_total_yeji && $this_total_yeji < $range['end']){
                            $this_ratio = $range['ratio'];
                        }
                    }
                }
                $this_member_fenhong = 0;
                if($this_ratio > 0)$this_member_fenhong = $this_total_yeji * $this_ratio * 0.01;
                foreach($sub_yeji as $key=>$yeji){
                    $this_member_fenhong -= $yeji['fenhong'];
                }
                //分红/比例 = 实际业绩
                $real_yeji = 0;
                if($this_ratio > 0){
                    $real_yeji= dd_money_format($this_member_fenhong/($this_ratio*0.01)); 
                }
                if($yj ==1){
                    return ['fenhong' => $this_member_fenhong,'yeji' => $real_yeji];
                } else{
                    return $this_member_fenhong;
                }
            }
            else{ //非级差
                $yejiwhere = [];
                if($yj==1){
                    if($yeji_set['jiesuan_type'] == 1){//按月
                        $month_start = strtotime(date('Y-m-01 00:00:00'));
                        $month_end  = strtotime(date('Y-m-t 23:59:59'));
                     
                        $yejiwhere[] = ['createtime','between',[$month_start,$month_end]];
                        //虚拟业绩
                        $now_month = date('Y-m',strtotime('-1 month'));
                        $xuni_yeji = 0 +Db::name('tem_yeji_activity_xuni')->where('aid',$aid)->where('mid',$mid)->where('yeji_month',$now_month)->value('yeji');
                    }elseif($yeji_set['jiesuan_type'] == 2){//按年
                        $year_start=strtotime(date('Y') . '-01-01 00:00:00');
                        $year_end=strtotime(date('Y') . '-12-31 23:59:59');
                        
                        $yejiwhere[] = ['createtime','between',[$year_start,$year_end]];
                    }elseif($yeji_set['jiesuan_type'] == 3){//按季度
                        $season_start=strtotime(date('Y-m-01 00:00:00'));
                        $season_end=strtotime(date('Y-m-t 23:59:59',strtotime('+3 month')));
                        
                        $yejiwhere[] = ['createtime','between',[$season_start,$season_end]];
                    }
                }else{
                  
                    if($yeji_set['jiesuan_type'] == 1){//按月
                        $month_start = strtotime(date('Y-m-01 00:00:00',strtotime('-1 month')));
                        $month_end  = strtotime(date('Y-m-t 23:59:59',strtotime('-1 month')));
                        $yejiwhere[] = ['createtime','between',[$month_start,$month_end]];

                    }elseif($yeji_set['jiesuan_type'] == 2){//按年
                        $year_start=strtotime((date('Y')-1) . '-01-01 00:00:00');
                        $year_end=strtotime((date('Y')-1) . '-12-31 23:59:59');
                        $yejiwhere[] = ['createtime','between',[$year_start,$year_end]];
                    }elseif($yeji_set['jiesuan_type'] == 3){//按季度
                        $season_start=strtotime(date('Y-m-01 00:00:00',strtotime('-3 month')));
                        $season_end=strtotime(date('Y-m-t 23:59:59',strtotime('-1 month')));
                        $yejiwhere[] = ['createtime','between',[$season_start,$season_end]];
                    }
                }
                $yejiwhere[] = ['status','in','1,2,3'];
                $levelids = [];
                if($config[$member['levelid']]['levelnum'] > 0) {
                    //设置层级，使用层级查询，
                    $deep = intval($config[$member['levelid']]['levelnum']);
                    $downmids = \app\common\Member::getteammids($aid,$mid,$deep,$levelids);
                } else{
                    //不设置层级，只用快速查询，因为上面方法非常慢
                    $down_where = [];
                    $down_where[] = ['aid','=',$member['aid']];
                    $down_where[] = ['id','<>',$member['id']];
                    if($levelids){
                        $down_where[] = ['levelid','in',$levelids];
                    }
                    $down_where[] = Db::raw('find_in_set('.$member['id'].',path)');
                    $downmids = Db::name('member')->where($down_where)->column('id');
                }
              
                if($is_include_self){
                    if($yeji_set['include_self']) $downmids[] = $member['id'];
                }
                if(!$downmids){
                    return $fenhong;
                }
                if($yeji_set['fwtype'] ==1){//0全部商品 1指定商品
                    $yejiwhere[] = ['proid','in',$yeji_set['proids']];
                }
                $teamyeji = Db::name('shop_order_goods')->where('aid',$aid)->where('mid','in',$downmids)->where($yejiwhere)->sum('real_totalprice');//real_totalprice totalprice
                $totalyeji = $teamyeji + $xuni_yeji;
                $jt_range = $config[$member['levelid']]['range'];
                if(!$jt_range){
                    \think\facade\Log::write($member['id'].'_'.$member['levelid'].'无设置activity');
                    return $fenhong;
                }
                $ratio = 0;
                foreach($jt_range as $rk=> $range){
                    if( $range['start'] <= $totalyeji && $totalyeji < $range['end']){
                        $ratio = $range['ratio'];
                    }
                }
                if($ratio > 0){
                    $fenhong = $ratio / 100 * $totalyeji;
                }
                if($yj ==1){                             
                    //反向计算实际业绩
                    if($ratio > 0){
                        $totalyeji= dd_money_format($fenhong/($ratio*0.01));
                    }
                    return ['fenhong' => $fenhong,'yeji' => $totalyeji];
                } else{
                    return $fenhong;
                }
            }
        }
    }

    public static function formatOrderMoney($amount, $mode = 1){
        if(!is_array($amount)){
            return formatMoney($amount,$mode);
        }
        else{
            foreach ($amount as $k => $item){
                if(!is_array($item)){
                    //一维数组
                    $moneyArr = ['totalprice','product_price','freight_price','invoice_money','scoredk_money','leveldk_money','manjian_money','coupon_money','discount_money_admin',
                        'cuxiao_money','discount_rand_money','up_floor_fee','deposit_totalprice','water_coupon_money'];
                    if(in_array($k,$moneyArr)){
                        $amount[$k] = formatMoney($item,$mode);
                    }
                }
                else{
                    //二维数组
                    if(isset($item['totalprice'])) $amount[$k]['totalprice'] = formatMoney($item['totalprice'],$mode);
                    if(isset($item['product_price'])) $amount[$k]['product_price'] = formatMoney($item['product_price'],$mode);
                    if(isset($item['freight_price'])) $amount[$k]['freight_price'] = formatMoney($item['freight_price'],$mode);
                    if(isset($item['invoice_money'])) $amount[$k]['invoice_money'] = formatMoney($item['invoice_money'],$mode);
                    if(isset($item['scoredk_money'])) $amount[$k]['scoredk_money'] = formatMoney($item['scoredk_money'],$mode);
                    if(isset($item['leveldk_money'])) $amount[$k]['leveldk_money'] = formatMoney($item['leveldk_money'],$mode);
                    if(isset($item['manjian_money'])) $amount[$k]['manjian_money'] = formatMoney($item['manjian_money'],$mode);
                    if(isset($item['coupon_money'])) $amount[$k]['coupon_money'] = formatMoney($item['coupon_money'],$mode);
                    if(isset($item['discount_money_admin'])) $amount[$k]['discount_money_admin'] = formatMoney($item['discount_money_admin'],$mode);
                    if(isset($item['cuxiao_money'])) $amount[$k]['cuxiao_money'] = formatMoney($item['cuxiao_money'],$mode);
                    if(isset($item['discount_rand_money'])) $amount[$k]['discount_rand_money'] = formatMoney($item['discount_rand_money'],$mode);
                    if(isset($item['up_floor_fee'])) $amount[$k]['up_floor_fee'] = formatMoney($item['up_floor_fee'],$mode);
                    if(isset($item['deposit_totalprice'])) $amount[$k]['deposit_totalprice'] = formatMoney($item['deposit_totalprice'],$mode);
                    if(isset($item['water_coupon_money'])) $amount[$k]['water_coupon_money'] = formatMoney($item['water_coupon_money'],$mode);
                }
            }
            return $amount;
        }
    }

    //送货单每页行数
    public static function shdLinenum($order_goods,$pagenum = 0,$extraRows = []){
        $order_goods2 = $order_goods;
        $beinum = 1;
        $count = count($order_goods);
        $num = $count + 1;
        if ($pagenum > 0) {
            $beinum = ceil($num / $pagenum);
            $cha = $pagenum - $num;
            if ($beinum > 1) {
                $yunum = $num % $pagenum;
                $cha = $pagenum - ($yunum + 3);
                if ($cha <= 0) {
                    $len = $cha + $pagenum;
                    if ($len > 0) {
                        for ($i = 0; $i < $len; $i++) {
                            $order_goods2[] = [];
                        }
                    }
                } else {
                    for ($i = 0; $i < $cha; $i++) {
                        $order_goods2[] = [];
                    }
                }
            } else {
                if ($cha > 0) {
                    for ($i = 0; $i < $cha; $i++) {
                        $order_goods2[] = [];
                    }
                }
            }
        }

        //固定行
        foreach ($extraRows as $row) {
            $order_goods2[] = $row;
        }

        //分页
        if ($pagenum > 0) {
            if ($beinum > 1) {
                $order_goods3 = array_chunk($order_goods2, $pagenum);
            } else {
                $chunk = $pagenum + 4;
                $order_goods3 = array_chunk($order_goods2, $chunk);
            }
        } else {
            $order_goods3 = [$order_goods2];
        }

        return $order_goods3;
    }

    /**
     * 微信同城自动派单
     * @author: liud
     * @time: 2025/11/5 14:41
     */
    public function autoWxtcPeisong($aid,$orderid,$type='shop_order'){
        if(getcustom('wx_express_intracity')){
            $cargo_type = 99;//物品类型 99 其他

            $psset_where = [];
            $psset_where[] = ['aid','=',$aid];

            $peisong = Db::name('peisong_set')->where($psset_where)->find();

            $order = Db::name($type)->where('id',$orderid)->where('aid',$aid)->find();

            if(!$order) return ['status'=>0,'msg'=>'订单不存在'];
            if($order['status']!=1 && $order['status']!=12) return ['status'=>0,'msg'=>'订单状态不符合'];

            if($peisong['wxtc_status'] != 1){
                return ['status'=>0,'msg'=>'微信同城配送功能未开启'];
            }

            if($order['bid'] == 0){
                if($peisong['wxtc_store_id'] <= 0){
                    return ['status'=>0,'msg'=>'平台未绑定微信配送门店'];
                }

                $wxstore = Db::name('wx_express_intracity_store')->where('aid',$aid)->where('id',$peisong['wxtc_store_id'])->find();
            }else{
                if($peisong['wxtc_status_business'] != 1){
                    return ['status'=>0,'msg'=>'平台未开启商户微信同城配送功能'];
                }

                $binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();

                if($binfo['wxtc_status'] != 1){
                    return ['status'=>0,'msg'=>'商户未开启微信同城配送功能'];
                }
                if($binfo['wxtc_store_id'] <= 0){
                    return ['status'=>0,'msg'=>'商户未绑定微信配送门店'];
                }

                $wxstore = Db::name('wx_express_intracity_store')->where('aid',$aid)->where('id',$binfo['wxtc_store_id'])->find();
            }

            if($wxstore['status'] != 1){
                return ['status'=>0,'msg'=>'微信配送门店(ID:'.$wxstore['id'].')已关闭'];
            }

            if($order['freight_type'] != 2){
                return ['status'=>0,'msg'=>'不是同城配送订单'];
            }

            //是否自动派单
            if($wxstore['auto_dispatch'] != 1){
                return ['status'=>0,'msg'=>'店铺未开启自动派单功能'];
            }

            //查询商品
            $order['item_list'] = Db::name($type.'_goods')->where('orderid',$order['id'])->select()->toArray();

            //查询重量
            $weight_arr = \app\custom\WxExpressIntracity::getweight($aid,$order,$type);//默认一千克

            $order['cargo_weight'] = $weight_arr ? $weight_arr['weight'] : 1000;
            $order['cargo_type'] = $cargo_type ? intval($cargo_type) : 99;
            $order['wx_store_id'] = $wxstore['wx_store_id'] ?? '';

            $order['user_openid'] = Db::name('member')->where('aid',$aid)->where('id',$order['mid'])->value('wxopenid') ?? '';

            $res = \app\custom\WxExpressIntracity::addorder($aid,$order);
            if($res['status'] != 1){
                return ['status'=>0,'msg'=>'操作失败：'.$res['msg']];
            }

            if($order['bid']>0){
                $business = Db::name('business')->field('name,address,tel,logo,longitude,latitude,money')->where('id',$order['bid'])->find();
            }elseif($order['mdid']>0){
                $business = Db::name('mendian')->field('name,address,tel,pic,longitude,latitude,money')->where('id',$order['mdid'])->find();
            }else{
                $business = Db::name('admin_set')->field('name,address,tel,logo,longitude,latitude')->where('aid',$order['aid'])->find();
            }

            $wxtc_data = $res['data']['data'];

            $service_trans = \app\custom\WxExpressIntracity::service_trans;

            $express_com = '微信同城配送-'.$service_trans[$wxtc_data['service_trans_id']];

            $order['procount'] = Db::name($type.'_goods')->where('orderid',$order['id'])->sum('num');

            $psorderdata = [];
            $psorderdata['aid'] = $order['aid'];
            $psorderdata['bid'] = $order['bid'];
            $psorderdata['mid'] = $order['mid'];
            $psorderdata['orderid'] = $order['id'];
            $psorderdata['ordernum'] = $order['ordernum'];
            $psorderdata['mdid'] = $order['mdid'];
            $psorderdata['createtime'] = time();
            $psorderdata['status'] = 0;
            $psorderdata['type'] = $type;
            $psorderdata['wxtc_wx_store_id'] = $wxtc_data['wx_store_id'];
            $psorderdata['wxtc_wx_order_id'] = $wxtc_data['wx_order_id'];
            $psorderdata['wxtc_service_trans_id'] = $wxtc_data['service_trans_id'];
            $psorderdata['wxtc_fee'] = $wxtc_data['fee'] / 100;
            $psorderdata['wxtc_trans_order_id'] = $wxtc_data['trans_order_id'];;
            $psorderdata['wxtc_addorder_notify'] =jsonEncode($wxtc_data);
            $psorderdata['orderinfo'] =  jsonEncode($order);
            $psorderdata['prolist'] = jsonEncode($order['item_list']);
            $psorderdata['binfo'] = jsonEncode($business);

            $psorderid = Db::name('peisong_order')->insertGetId($psorderdata);

            $wxtc_insertdata = [];
            $wxtc_insertdata['aid'] = $order['aid'];
            $wxtc_insertdata['bid'] = $order['bid'];
            $wxtc_insertdata['mid'] = $order['mid'];
            $wxtc_insertdata['poid'] = $psorderid;
            $wxtc_insertdata['createtime'] = time();
            $wxtc_insertdata['orderid'] = $order['id'];
            $wxtc_insertdata['ordernum'] = $order['ordernum'];
            $wxtc_insertdata['cargo_weight'] = $order['cargo_weight'];
            $wxtc_insertdata['cargo_type'] = $order['cargo_type'];
            $wxtc_insertdata['addorder_notify'] = jsonEncode($wxtc_data);
            $wxtc_insertdata['wx_store_id'] = $wxtc_data['wx_store_id'];
            $wxtc_insertdata['wx_order_id'] = $wxtc_data['wx_order_id'];
            $wxtc_insertdata['service_trans_id'] = $wxtc_data['service_trans_id'];
            $wxtc_insertdata['logistic_no'] = $wxtc_insertdata['trans_order_id'] = $wxtc_data['trans_order_id'];
            $wxtc_insertdata['order_status'] = 10000;

            Db::name('peisong_order_wx_express_intracity')->insertGetId($wxtc_insertdata);

            Db::name($type)->where('aid',$aid)->where('id',$orderid)->update(['express_com'=>$express_com,'express_no'=>$wxtc_data['trans_order_id'],'send_time'=>time(),'status'=>2,'wxtc_wx_order_id' => $wxtc_data['wx_order_id']]);
            Db::name($type.'_goods')->where('orderid',$orderid)->where('aid',$aid)->update(['status'=>2]);

            //发货信息录入 微信小程序+微信支付
            if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                \app\common\Order::wxShipping($aid,$order,$type);
            }
            //\app\common\System::plog('微信同城配送自动派单'.$orderid);
            return ['status'=>1,'msg'=>'操作成功'];
        }
    }

    //确认收货奖励
    public static function orderCollectReward($order,$type='shop'){
        if(empty($order) || empty($type)){
            return false;
        }

        $aid = $order['aid'];
        $mid = $order['mid'];
        $now = time();
        $set = Db::name('order_collect_reward')->where('aid', $aid)->where('start_time', '<=', $now)->where('end_time', '>=', $now)->where('status', 1)->find();
        if(!$set){
            return false;
        }

        $member = Db::name('member')->where('id', $mid)->find();
        if(!$member){
            return false;
        }

        //订单时间
        if($order['createtime'] < $set['start_time'] || $order['createtime'] > $set['end_time']) {
            return false;
        }

        //多商户只支持商城订单;
        if($order['bid'] > 0){
            if($type != 'shop'){
                return false;
            }
            $type = 'businessshop';
        }

        //订单类型
        $orderTypes = explode(',', $set['order_type']);
        if(!in_array($type, $orderTypes)){
            return false;
        }

        //使用平台
        $platform = explode(',', $set['platform']);
        if(!in_array($order['collect_reward_platform'] ?? '', $platform)){
            return false;
        }

        //参与条件
        $gettj = explode(',', $set['gettj']);
        if(!in_array('-1', $gettj) && !in_array($member['levelid'], $gettj)){
            return false;
        }

        //单笔订单金额限制
        $totalprice = bcsub($order['totalprice'], $order['freight_price'], 2); //减去运费
        if($set['min_order_amount'] > 0 && $totalprice < $set['min_order_amount']){
            return false;
        }

        //每日奖励次数限制
        if($set['max_daily_return'] > 0){
            $dailyCount = Db::name('order_collect_reward_record')
                ->where('aid', $aid)
                ->where('mid', $mid)
                ->whereTime('createtime', 'today')
                ->count();
            if($dailyCount >= $set['max_daily_return']){
                return false;
            }
        }

        //每月奖励次数限制
        if($set['max_month_return'] > 0){
            $monthlyCount = Db::name('order_collect_reward_record')
                ->where('aid', $aid)
                ->where('mid', $mid)
                ->whereTime('createtime', 'month')
                ->count();
            if($monthlyCount >= $set['max_month_return']){
                return false;
            }
        }

        //否已存在记录
        $recordExists = Db::name('order_collect_reward_record')->where('orderid', $order['id'])->where('aid', $aid)->find();
        if ($recordExists) return false;

        $reward = 0;
        $reward_type = $set['reward_type'];
        switch ($reward_type) {
            case 1: if ($set['score'] > 0) $reward = $set['score']; break;
            case 2: if (!empty($set['coupon_id'])) $reward = $set['coupon_id']; break;
            case 3: if ($set['money'] > 0) $reward = $set['money']; break;
            case 4: if ($set['commission'] > 0) $reward = $set['commission']; break;
            default: return false;
        }
        if ($reward <= 0) return false;

        try {
            Db::name('order_collect_reward_record')->insert([
                'aid'         => $aid,
                'bid'         => $order['bid'],
                'mid'         => $mid,
                'orderid'     => $order['id'],
                'ordernum'    => $order['ordernum'] ?? '',
                'ordertype'   => $type,
                'reward_type' => $reward_type,
                'reward'      => $reward,
                'ordertime'   => $order['createtime'] ?? 0,
                'createtime'  => $now,
            ]);

            switch ($reward_type) {
                case 1: \app\common\Member::addscore($aid, $mid, $set['score'], '确认收货奖励'); break;
                case 2: \app\common\Coupon::send($aid, $mid, $set['coupon_id']); break;
                case 3: \app\common\Member::addmoney($aid, $mid, $set['money'], '确认收货奖励'); break;
                case 4: \app\common\Member::addcommission($aid, $mid, 0, $set['commission'], '确认收货奖励'); break;
            }
            return true;
        } catch (\Exception $e) {
            \think\facade\Log::error("确认收货奖励异常 | order_id:{$order['id']} | aid:{$aid} | error: " . $e->getMessage());
            return false;
        }
    }
}

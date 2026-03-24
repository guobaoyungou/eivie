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

namespace app\model;
use think\facade\Db;
use think\facade\Log;
use function Qiniu\waterImg;

class Payorder
{
	//创建支付订单
	public static function createorder($aid,$bid,$mid,$type,$orderid,$ordernum,$title,$money,$score=0,$service_fee=0,$params = [],$total_freezemoney_price=0){
		$data = [];
		$data['aid'] = $aid;
		$data['bid'] = $bid;
		$data['mid'] = $mid;
		$data['orderid'] = $orderid;
		$data['ordernum'] = $ordernum;
		$data['title'] = $title;
		$data['money'] = $money;
		$data['score'] = $score;
        if(getcustom('product_service_fee')){
		    $data['service_fee_money'] = $service_fee;
        }
		$data['type'] = $type; //shop collage scoreshop kanjia seckill recharge designerpage form servicefee_recharge
		$data['createtime'] = time();

        if(getcustom('member_level_moneypay_price')){
            //所有商品普通价格总价
            if($params && isset($params['putongprice'])){
                $data['moneyprice']  = $money;//会员购买总价
                $data['putongprice'] = $params['putongprice'];//普通价格总价
                $data['usemoneypay'] = 0;//是否使用会员价仅余额支付 -1 之前支付默认 0：未使用 1：使用
            }
        }
        if(getcustom('freeze_money',$aid)){
            $data['total_freezemoney_price'] = $total_freezemoney_price;
        }
        if(getcustom('payorder_feepercent')){
            //记录商家抽成费率
            if($bid>0){
                $data['feepercent'] = Db::name('business')->where('id',$bid)->value('feepercent');
            }
        }
        if(getcustom('gold_bean_shop')){
            if($params['gold_bean']){
                $data['gold_bean'] = $params['gold_bean'];
            }
        }
		$id = Db::name('payorder')->insertGetId($data);
		if($type == 'shop_hb' || $type == 'scoreshop_hb' || $type == 'shop_fenqi'){
			//Db::name('shop_order')->where('ordernum','like',$ordernum.'%')->update(['payorderid'=>$id]);
		}elseif(in_array($type, ['score_buy','creative_member'])){
			// 积分购买和创作会员无独立order表，跳过关联更新
		}else{
			if($orderid > 0){
				Db::name($type.'_order')->where('id',$orderid)->update(['payorderid'=>$id]);
			}
		}
        //创建新的支付单，关闭旧的支付单
        Db::name('payorder')->where('id','<>',$id)->where('aid',$data['aid'])->where('bid',$data['bid'])->where('orderid',$data['orderid'])->where('type',$data['type'])->where('status',0)->update(['status'=>2]);
		return $id;
	}
	//修改订单
	public static function updateorder($id,$newOrdernum,$newprice,$updateOrderId=0){

//        // 查询订单原价
//        $order_info = Db::name('shop_order')->where('aid',aid)->where('id', $orderid)->find();
//        // 查询订单原来应该支付金额
//        $pay_money = Db::name('payorder')->where('aid',aid)->where('id', $order_info['payorderid'])->value('money');
//        // 计算修改差价
//        $order_num = explode('_', $order_info['ordernum'])[0] ?? $order_info['ordernum'] ; // 原支付订单号
//        // 查询同笔订单信息
//        $shop_order_info = Db::name('shop_order')->where('aid',aid)->where('ordernum', 'like',  $order_num . '%' )->select()->toArray();
//
//        // print_r($shop_order_info);die;
//
//        foreach ($shop_order_info as $k=>$v){
//            // 修改订单号
//            $new_order_num = str_replace($order_num, $newordernum, $v['ordernum']) ;
//            $update_arr = [ 'ordernum'=>$new_order_num  ] ;
//            // 只有当前修改的订单改价 其它子订单只修改单号 解决支付出现重复单号-金额不同问题
//            if($v['id'] == $orderid){
//                $update_arr['totalprice'] = $newprice ; // 后台改动后金额
//            }
//            Db::name('shop_order')->where('aid',aid)->where('id',$v['id'])->update($update_arr);
//            Db::name('shop_order_goods')->where('aid',aid)->where('orderid',$v['id'])->update(['ordernum'=>$new_order_num]);
//            // 修改子支付订单 订单号
//            Db::name('payorder')->where('ordernum', $v['ordernum'])->update(['ordernum'=>$new_order_num]);
//        }
//
//        // 计算差价 修改 总支付订单差价
//        if($order_info['totalprice'] > $newprice){
//            $newprice = bcsub($pay_money, bcsub($order_info['totalprice'], $newprice, 2), 2) ;
//        }else{
//            $newprice = bcadd($pay_money, bcsub($newprice, $order_info['totalprice'], 2), 2) ;
//        }

        $payorder = Db::name('payorder')->where('id',$id)->find();
        $ordernumArr = explode('_',$payorder['ordernum']);
        //合并订单
        if($ordernumArr[1]){
//        if(in_array($payorder['type'],['shop_hb','restaurant_takeaway_hb','scoreshop_hb'])){
            //合并支付订单重新计算
            // 查询同笔订单信息
            $child_order = Db::name('payorder')->where('aid',$payorder['aid'])->where('ordernum', 'like',  $ordernumArr[0] . '_%' )->select()->toArray();
            //修改子价格，修改订单号
            if($child_order){
                $totalprice = 0;
                $newOrdernumArr = explode('_',$newOrdernum);
                $newOrdernum = $newOrdernumArr[0];
                foreach ($child_order as $v){
                    // 修改订单号
                    $order_num = explode('_',$v['ordernum']);
                    $new_order_num = str_replace($order_num[0], $newOrdernum, $v['ordernum']) ;
                    $update_arr = ['ordernum'=>$new_order_num] ;
                    if($v['orderid'] == $updateOrderId){
                        $update_arr['money'] = $newprice ; //改动后金额
                        $totalprice += $newprice;
                    }else{
                        $totalprice += $v['money'];
                        $where=[];
                        $where[]=['aid','=',$v['aid']];
                        $where[]=['id','=',$v['orderid']];
                        Db::name($v['type'].'_order')->where($where)->where('id',$v['orderid'])->update($update_arr);
                        if(\app\common\Order::hasOrderGoodsTable($v['type'])){
                            Db::name($v['type'].'_order_goods')->where('aid',$v['aid'])->where('orderid',$v['orderid'])->update($update_arr);
                        }
                    }
                    Db::name('payorder')->where('id',$v['id'])->update($update_arr);
                }

                //修改主价格，修改订单号
                Db::name('payorder')->where('ordernum',$ordernumArr[0])->update(['ordernum'=>$newOrdernum,'money'=>$totalprice]);
            }
        }else{
            Db::name('payorder')->where('id',$id)->update(['ordernum'=>$newOrdernum,'money'=>$newprice]);
        }
	}
	//支付完成后操作
	public static function payorder($orderid,$paytype,$paytypeid,$paynum='',$wxpay_typeid='',$out_trade_no=''){
		if(!$orderid) return;
		$payorder = Db::name('payorder')->where('id',$orderid)->find();
       
		//dump($payorder.'看看支付后的操作');
		if(!$payorder || $payorder['status']==1) return ['status'=>0,'msg'=>'该订单已支付'];
		if(getcustom('pay_yuanbao') && $payorder['type'] == 'shop'){
            if($payorder['is_yuanbao_pay'] == 1){
                $paytype = t('元宝').'支付+'.$paytype;
            }
            //元宝 更新shop_order和payorder
            self::yuanbao_up($payorder['type'],$payorder);
        }
		//成为多商户会员
        if(getcustom('business_member')){
            if($payorder['bid'] >0){
                $business_member = Db::name('business_member')->where('aid',$payorder['aid'])->where('bid',$payorder['bid'])->where('mid',$payorder['mid'])->find();
                if(empty($business_member)){
                    $insert=[
                        'aid' => $payorder['aid'],
                        'bid' => $payorder['bid'],
                        'mid' => $payorder['mid'],
                        'createtime' => time()
                    ];
                    Db::name('business_member')->insert($insert);
                }
            }
        }

        if(getcustom('freeze_money')) {
            if ($payorder['total_freezemoney_price'] > 0) {
                $remark = '支付订单,订单号: ' . $payorder['ordernum'];
                $res_freezemoney = \app\common\Member::addfreezemoney($payorder['aid'], $payorder['mid'], -$payorder['total_freezemoney_price'], $remark, $payorder['ordernum']);
            }
        }
		if($payorder['type'] == 'restaurant_shop' && $paytypeid == 4) {
            Db::name('payorder')->where('id',$orderid)->update(['paytype'=>$paytype,'paytypeid'=>$paytypeid,'paynum'=>$paynum]);
            //更新流水表记录
            Db::name('pay_transaction')->where('transaction_num',$out_trade_no)->update(['paytype'=>$paytype,'paytypeid'=>$paytypeid,'paynum'=>$paynum]);
        } else {
            Db::name('payorder')->where('id',$orderid)->update(['status'=>1,'paytime'=>time(),'paytype'=>$paytype,'paytypeid'=>$paytypeid,'paynum'=>$paynum]);
            //更新流水表记录
            Db::name('pay_transaction')->where('transaction_num',$out_trade_no)->update(['status'=>1,'paytime'=>time(),'paytype'=>$paytype,'paytypeid'=>$paytypeid,'paynum'=>$paynum]);
        }
		$type = $payorder['type'];

		if($payorder['mid']){
		    Db::name('member')->where('id',$payorder['mid'])->update(['last_buytime'=>time()]);
        }

        if(getcustom('member_gongxian')){
            if(!in_array($type,['recharge'])){
                $aid = $payorder['aid'];
                $admin = Db::name('admin')->where('id',$aid)->find();
                $set = Db::name('admin_set')->where('aid',$aid)->find();
                if(empty($set['gongxian_bonus_disable']) || $payorder['bid']==0){//多商户商品是否参与赠送贡献值
                    if($admin['member_gongxian_status'] == 1 && $set['gongxianin_money'] > 0 && $set['gognxianin_value'] > 0){
                        $givevalue = floor($payorder['money'] / $set['gongxianin_money']) * $set['gognxianin_value'];
                        \app\common\Member::addgongxian($aid,$payorder['mid'],$givevalue,'消费送'.t('贡献'),$type,$payorder['orderid']);
                    }
                }
            }
        }
        if($type=='daifu'){
            Db::name('payorder')->where('id',$payorder['pid'])->update([
                'paynum'=>$paynum,
                'status'=>1,
                'paytypeid'=>$paytypeid,
                'paytype'=>$paytype,
                'paytime'=>time(),
                'paymid'=>$payorder['mid'],
            ]);
            $payorder = Db::name('payorder')->where('id',$payorder['pid'])->find();
            $type = $payorder['type'];
        }
        if(getcustom('wxpay_native_h5') && ($paytypeid == 2) && $wxpay_typeid){
            //微信收款码
            Db::name('payorder')->where('id',$orderid)->update(['wxpay_typeid'=>$wxpay_typeid]);
        }
		if($type == 'shop_hb'){
			Db::name('shop_order')->where('ordernum','like',$payorder['ordernum'].'%')->update(['status'=>1,'paytime'=>time(),'paytype'=>$paytype,'paytypeid'=>$paytypeid,'paynum'=>$paynum,'platform'=>$payorder['platform']]);
		}elseif($type == 'scoreshop_hb'){
			Db::name('scoreshop_order')->where('ordernum','like',$payorder['ordernum'].'%')->update(['status'=>1,'paytime'=>time(),'paytype'=>$paytype,'paytypeid'=>$paytypeid,'paynum'=>$paynum,'platform'=>$payorder['platform']]);
		}elseif($type == 'balance'){
            $update = ['balance_pay_status'=>1,'balance_pay_orderid'=>$orderid];
            if(getcustom('shop_cod_onlinepay')) {
                //判断是否是货到付款支付
                $order = Db::name('shop_order')->where('id',$payorder['orderid'])->find();
                if($order && $order['balance_pay_orderid2'] == $payorder['id']){
                    //修改订单支付参数
                    $update = ['paytime'=>time(),'paytype'=>$paytype,'paytypeid'=>$paytypeid,'paynum'=>$paynum,'platform'=>$payorder['platform'],'balance_pay_status2'=>1];
                    if($order['payorderid'] != $payorder['id']){
                        $payorder2 = Db::name('payorder')->where('id',$order['payorderid'])->where('orderid',$order['id'])->find();
                        if($payorder2 ){
                            //修改订单原payorder支付参数
                            $updata2 = $payorder;
                            $updata2['type'] = 'shop';
                            $updata2['status'] = 1;
                            unset($updata2['id']);
                            unset($updata2['createtime']);
                            Db::name('payorder')->where('id',$order['payorderid'])->update($updata2);
                        }else{
                            //重置为现在payorder
                            Db::name('payorder')->where('id',$payorder['id'])->update(['paytype'=>'shop']);
                            $update['payorderid'] = $payorder['id'];
                        }
                    }
                }
            }
            if($update){
                Db::name('shop_order')->where('id',$payorder['orderid'])->update($update);
            }
			return ['status'=>1,'msg'=>''];
		}elseif($type == 'shopfront'){
			$order = Db::name('shop_order')->where('id',$payorder['orderid'])->find();
			Db::name('shop_order')->where('id',$payorder['orderid'])->update(['status'=>1,'paytime'=>time(),'paytype'=>$paytype,'paytypeid'=>$paytypeid,'paynum'=>$paynum,'platform'=>$payorder['platform'],'balance_price'=>$order['totalprice'] - $payorder['money']]);
			self::shop_pay($payorder['orderid']);
			return ['status'=>1,'msg'=>''];
		}elseif($type == 'yuyue_balance'){
			$yuyueset = Db::name('yuyue_set')->where('aid',$payorder['aid'])->where('bid',$payorder['bid'])->find();
            $yuyueOrderUpdate = [];
            $yuyueOrderUpdate['balance_pay_status'] = 1;
            $yuyueOrderUpdate['balance_pay_orderid'] = $orderid;
            //开启服务完成确认 需确认后 订单状态改为已完成
            if($yuyueset['finish_confirm'] == 0){
                $yuyueOrderUpdate['status'] = 3;
            }
            Db::name('yuyue_order')->where('id',$payorder['orderid'])->update($yuyueOrderUpdate);
		}elseif($type == 'yuyue_addmoney'){
			Db::name('yuyue_order')->where('id',$payorder['orderid'])->update(['addmoneyStatus'=>1]);
		}elseif($type == 'seckill2'){
			Db::name('seckill2_order')->where('id',$payorder['orderid'])->update(['status'=>1,'paytime'=>time(),'paytype'=>$paytype,'paytypeid'=>$paytypeid,'paynum'=>$paynum,'platform'=>$payorder['platform']]);
			$form_sale = Db::name('seckill2_order')->alias('or')->join('seckill2_product pro','or.proid = pro.id')->field('pro.saleid')->where('or.ordernum',$payorder['ordernum'])->find();
			$sale = Db::name('seckill2_sale')->where('id',$form_sale['saleid'])->find();
			Db::name('seckill2_order')->where('ordernum',$sale['form_ordernum'])->update(['status'=>10]);
		}elseif($type == 'form'){
			Db::name($type.'_order')->where('id',$payorder['orderid'])->update(['paystatus'=>1,'paytime'=>time(),'paytype'=>$paytype,'paytypeid'=>$paytypeid,'paynum'=>$paynum,'platform'=>$payorder['platform']]);
		}elseif($type == 'plug_business_pay'){
			Db::name($type.'_order')->where('id',$payorder['orderid'])->update(['status'=>1,'paytime'=>time(),'paytype'=>$paytype,'paytypeid'=>$paytypeid,'paynum'=>$paynum,'platform'=>$payorder['platform']]);
        }elseif($type == 'restaurant_shop' && $paytypeid == 4){
            Db::name($type.'_order')->where('id',$payorder['orderid'])->update(['paytype'=>$paytype,'paytypeid'=>$paytypeid,'paynum'=>$paynum,'platform'=>$payorder['platform']]);
        }elseif($type == 'workorder'){  //工单订单
            Db::name($type.'_order')->where('id',$payorder['orderid'])->update(['paystatus'=>1,'paytype'=>$paytype,'paytypeid'=>$paytypeid,'paynum'=>$paynum,'platform'=>$payorder['platform']]);
        }elseif($type == 'hbtk'){  //拓客活动
		    if(getcustom('yx_hbtk')){
                Db::name($type.'_order')->where('id',$payorder['orderid'])->update(['status'=>1,'paytype'=>$paytype,'paytypeid'=>$paytypeid,'paynum'=>$paynum,'platform'=>$payorder['platform']]);
            }
        }elseif($type=='imgai'){
            Db::name($type.'_order')->where('id',$payorder['orderid'])->update(['status'=>1,'paytime'=>time(),'paytype'=>$paytype,'paytypeid'=>$paytypeid,'paynum'=>$paynum,'platform'=>$payorder['platform']]);
        }elseif($type=='mapmark'){
            Db::name($type.'_order')->where('id',$payorder['orderid'])->update(['status'=>1,'paytime'=>time(),'paytype'=>$paytype,'paytypeid'=>$paytypeid,'paynum'=>$paynum,'platform'=>$payorder['platform']]);
        }elseif($type=='videospider'){
            Db::name($type.'_order')->where('id',$payorder['orderid'])->update(['status'=>1,'paytime'=>time(),'paytype'=>$paytype,'paytypeid'=>$paytypeid,'paynum'=>$paynum,'platform'=>$payorder['platform']]);
        }elseif($type=='cerberuse'){
		    if(getcustom('lot_cerberuse')){
                Db::name($type.'_order')->where('id',$payorder['orderid'])->update(['status'=>1,'paytime'=>time(),'paytype'=>$paytype,'paytypeid'=>$paytypeid,'platform'=>$payorder['platform']]);
            }
        }elseif($type=='shop_fenqi'){
            $fenqi_order = Db::name('shop_order')->where('id',$payorder['orderid'])->find();
            $fenqi_money = round($payorder['money'] + $fenqi_order['totalprice'],2);
            Db::name('shop_order')->where('id',$payorder['orderid'])->update(['status'=>1,'paytime'=>time(),'paytype'=>$paytype,'paytypeid'=>$paytypeid,'platform'=>$payorder['platform'],'totalprice'=>$fenqi_money]);
            self::shop_fenqi_pay_change($payorder);
        }elseif($type=='livepay') {
            Db::name('livepay_order')->where('id', $payorder['orderid'])->update(['status' => 1, 'paytime' => time(), 'paytype' => $paytype, 'paytypeid' => $paytypeid, 'platform' => $payorder['platform']]);
            $order = Db::name('livepay_order')->where('id', $payorder['orderid'])->find();
            $set = Db::name('admin_set')->where('aid', $aid)->find();
            if ($set['fxjiesuantime'] == 1 && $set['fxjiesuantime_delaydays'] == '0') {
                \app\common\Order::giveCommission($order, 'livepay');
            }
            return ['status' => 1, 'msg' => ''];
        }elseif($type=='huodong_baoming') {
            $aid = $payorder['aid'];
            Db::name('huodong_baoming_order')->where('id', $payorder['orderid'])->update(['status' => 1, 'paytime' => time(), 'paytype' => $paytype, 'paytypeid' => $paytypeid, 'platform' => $payorder['platform']]);
            $order = Db::name('huodong_baoming_order')->where('id', $payorder['orderid'])->find();
            Db::name('huodong_baoming_product')->where('aid',$aid)->where('id',$order['proid'])->update(['sales'=>Db::raw("sales+".$order['num'])]);
            if ($order['givescore'] > 0) {
                \app\common\Member::addscore($aid,$order['mid'],$order['givescore'],'参与活动赠送'.t('积分'));
            }
            return ['status' => 1, 'msg' => ''];
        }elseif($type=='servicefee_recharge'){
            if(getcustom('product_service_fee')){
                Db::name($type.'_order')->where('id',$payorder['orderid'])->update(['status'=>1,'paytime'=>time(),'paytype'=>$paytype,'paytypeid'=>$paytypeid,'platform'=>$payorder['platform']]);
                \app\common\Member::addServiceFee($payorder['aid'],$payorder['mid'],$payorder['money'],$paytype);
                return ['status'=>1,'msg'=>''];
            }
        }elseif($type=='score_buy'){
            // H5积分购买 - 无独立order表，直接通过payorder记录处理
            $aid = $payorder['aid'];
            $mid = $payorder['mid'];
            $total_score = intval($payorder['score']); // score字段存储的是含赠送的总积分
            if($total_score > 0){
                \app\common\Member::addscore($aid, $mid, $total_score, '积分购买');
            }
            // 升级等级
            \app\common\Member::uplv($aid, $mid);
            return ['status'=>1,'msg'=>''];
        }elseif($type=='creative_member'){
            // H5创作会员购买 - 通过payorder记录激活订阅
            $aid = $payorder['aid'];
            $mid = $payorder['mid'];
            try {
                $service = new \app\service\CreativeMemberService();
                $plan = null;
                // 优先通过orderid存储的plan_id查找套餐
                if(!empty($payorder['orderid'])){
                    $plan = Db::name('creative_member_plan')
                        ->where('id', intval($payorder['orderid']))
                        ->where('status', 1)
                        ->find();
                }
                // 兑容旧订单：通过价格匹配套餐
                if(!$plan){
                    $plan = Db::name('creative_member_plan')
                        ->where('aid', $aid)
                        ->where('price', $payorder['money'])
                        ->where('status', 1)
                        ->find();
                }
                if(!$plan){
                    $plan = Db::name('creative_member_plan')
                        ->where('price', $payorder['money'])
                        ->where('status', 1)
                        ->find();
                }
                if($plan){
                    $subId = $service->activateSubscription($aid, $mid, $plan, $payorder['id']);
                    Db::name('payorder')->where('id', $payorder['id'])->update(['orderid' => $subId]);
                } else {
                    Log::error('创作会员支付回调：找不到匹配套餐, payorder_id='.$payorder['id']);
                }
            } catch(\Exception $e) {
                Log::error('创作会员激活失败: '.$e->getMessage());
            }
            return ['status'=>1,'msg'=>''];
        }elseif($type=='ai_travel_photo'){
            // 笑脸抓拍选片订单支付回调
            try {
                $pickService = new \app\service\AiTravelPhotoPickService();
                $order = \app\model\AiTravelPhotoOrder::find($payorder['orderid']);
                if ($order) {
                    $pickService->paySuccessfulfilment($order->order_no, [
                        'transaction_id' => $paynum ?: '',
                        'pay_type' => $paytype,
                    ]);
                }
            } catch(\Exception $e) {
                Log::error('AI旅拍选片支付回调失败: '.$e->getMessage());
            }
            return ['status'=>1,'msg'=>''];
        }elseif($type=='ai_travel_photo_pay'){
            // AI旅拍套餐购买支付回调（云空间扩容/余额充值/续费）
            try {
                \app\controller\AiTravelPhoto::handlePaymentSuccess(
                    intval($payorder['orderid']),
                    $payorder['aid']
                );
            } catch(\Exception $e) {
                Log::error('AI旅拍套餐支付回调失败: '.$e->getMessage());
            }
            return ['status'=>1,'msg'=>''];
        }else{
			Db::name($type.'_order')->where('id',$payorder['orderid'])->update(['status'=>1,'paytime'=>time(),'paytype'=>$paytype,'paytypeid'=>$paytypeid,'paynum'=>$paynum,'platform'=>$payorder['platform']]);
		}
		$fun = $type.'_pay';
		self::$fun($payorder['orderid'],$payorder['ordernum']);
        self::afterusecoupon($payorder['orderid'],$type,1,$payorder['ordernum']);
		self::payaftergive($payorder);

        if(getcustom('invite_free')){
            if($payorder['type'] == 'shop'){
                //发送订单通知
                self::send_free_notice($payorder);
            }
        }

        //余额支付手续费
        if(getcustom('money_pay_fee_rate')) {
            $money_pay_fee_rate = Db::name('admin_set_custom')->where('aid',$payorder['aid'])->value('money_pay_fee_rate');
            if($money_pay_fee_rate > 0  && $paytypeid == 1){
                $money_pay_fee = round($payorder['money'] * $money_pay_fee_rate / 100,2);

                if($money_pay_fee > 0){
                    Db::name('payorder')->where('aid',$payorder['aid'])->where('id',$payorder['id'])->update(['money_pay_fee'=>$money_pay_fee]);

                    \app\common\Member::addmoney($payorder['aid'],$payorder['mid'],-$money_pay_fee,'余额支付扣除手续费，订单号: ' . $payorder['ordernum'],0,'','',['type' => 'money_pay_fee','ordernum'=>$payorder['ordernum']]);
                }
            }
        }

        if(getcustom('task_banner')){
            self::taskbanner($payorder['aid'],$payorder['mid']);
        }
        if(getcustom('ganer_fenxiao')){
            $set = Db::name('prize_pool_set')->where('aid',$payorder['aid'])->find();
            //订单业绩进入奖金池
            if($set['pool_time']==0){
                \app\common\Fenxiao::bonus_poul($payorder['orderid'],$type);
            }
        }

        //支付完成增加商户销量,幸运拼团在开奖时触发
        if($type!='lucky_collage'){
            self::addSales($payorder['orderid'],$type,$payorder['aid'],$payorder['bid']);
        }

        if(getcustom('business_fenxiao')){
            $payorder = Db::name('payorder')->where('id',$orderid)->find();
            $fenxiao_type = Db::name('admin_set')->where('aid',$payorder['aid'])->value('business_fenxiao_type');
            //支付完成，店铺分销统计日营业额
            if($fenxiao_type==0){
                \app\common\Business::countBusinessYeji($payorder);
            }
        }
        if(getcustom('yx_buy_fenhong')){
            if($payorder['type'] !='recharge' && $payorder['mid'] > 0){
                //余额支付和充值重复，保留余额
                \app\custom\BuyFenhong::getScoreWeight($payorder);
            }
        }
        //支付后增加分销数据统计
        if(getcustom('transfer_order_parent_check')) {
            //销售金额
            \app\common\Fenxiao::addTotalOrderNum($payorder['aid'], $payorder['mid'], $payorder['orderid'],2);
        }
        //支付后增加押金数据
        if(getcustom('product_deposit_mode')) {
            \app\common\Order::order_product_deposit($payorder['aid'],$payorder['orderid'],$payorder['money']);
        }
        //计算分红数据
        //订单支付完成，计算分红数据
        if(in_array($type,['maidan','coupon','business_reward','cashier','restaurant_shop'])){
            \app\common\Order::order_create_done($payorder['aid'],$payorder['orderid'],$type);
            //\app\common\Fenhong::jiesuan_single($payorder['aid'],$payorder['orderid'],$type);
        }
        //即拼
        if(getcustom('yx_collage_jipin')) {
            \app\common\Order::collageJipinOrder($payorder['aid'],$payorder['orderid']);
        }
        //末消店铺
        if(getcustom('business_last_buy_bind_show_page')){
            $payorder = Db::name('payorder')->where('id',$orderid)->find();
            if($payorder['bid'] > 0){
                //记录最后购买的店铺
                Db::name('member')->where('id',$payorder['mid'])->where('aid',$payorder['aid'])->update(['lastbuy_business'=>$payorder['bid']]);
            }
        }
        if(getcustom('yx_daily_lirun_choujiang')){
            //参与抽奖
            if($payorder['type'] == 'shop' || $payorder['type'] == 'maidan'){
                \app\custom\yingxiao\LirunChoujiang::addChoujiang($payorder);
            }
        }

        if(getcustom('wx_express_intracity')){
            //微信同城自动派单
            if($payorder['type'] == 'shop' ){
                $shop_order = Db::name('shop_order')->where('id',$payorder['orderid'])->field('id,aid,status,freight_type,freight_time')->find();
                if($shop_order['freight_type'] == 2 && !$shop_order['freight_time'] && $shop_order['status'] == 1){
                    \app\common\Order::autoWxtcPeisong($shop_order['aid'],$shop_order['id'],'shop_order');
                }
            }
        }
		return ['status'=>1,'msg'=>''];
	}
    

	//更新商户销量
	public static function addSales($orderid,$type,$aid,$bid=0,$sale_num=0){
        $sales_type = [
            'sales' => 'sales',//虚拟销量
            'shop' => 'shop_sales',//普通商铺
            'collage' => 'collage_sales',//多人拼团
            'kanjia' => 'kanjia_sales',//砍价
            'seckill' => 'seckill_sales',//秒杀
            'tuangou' => 'tuangou_sales',//团购
            'scoreshop' => 'scoreshop_sales',//积分商城
            'lucky_collage' => 'lucky_collage_sales',//幸运拼团
            'yuyue' => 'yuyue_sales',//预约服务
            'kecheng' => 'kecheng_sales',//课程
            'cycle' => 'cycle_sales',//周期购
            'restaurant_takeaway' => 'restaurant_takeaway_sales',//餐饮外卖
            'restaurant_shop' => 'restaurant_shop_sales',//餐饮点餐
            'maidan' => 'maidan_sales'//买单
        ];
        if(!empty($sales_type[$type])){
            if($sale_num==0 && $orderid){
                switch ($type){
                    case 'shop':
                        $sale_num = Db::name('shop_order_goods')->where('orderid',$orderid)->sum('num');
                        break;
                    case 'collage':
                        $sale_num = Db::name('collage_order')->where('id',$orderid)->sum('num');
                        break;
                    case 'kanjia':
                        $sale_num = Db::name('kanjia_order')->where('id',$orderid)->sum('num');
                        break;
                    case 'seckill':
                        $sale_num = Db::name('seckill_order')->where('id',$orderid)->sum('num');
                        break;
                    case 'tuangou':
                        $sale_num = Db::name('tuangou_order')->where('id',$orderid)->sum('num');
                        break;
                    case 'scoreshop':
                        $sale_num = Db::name('scoreshop_order_goods')->where('orderid',$orderid)->sum('num');
                        break;
                    case 'lucky_collage':
                        $sale_num = Db::name('lucky_collage_order')->where('id',$orderid)->sum('num');
                        break;
                    case 'yuyue':
                        $sale_num = Db::name('yuyue_order')->where('id',$orderid)->sum('num');
                        break;
                    case 'kecheng':
                        $sale_num = 1;
                        break;
                    case 'cycle':
                        $sale_num = Db::name('cycle_order')->where('id',$orderid)->sum('num');
                        break;
                    case 'restaurant_takeaway':
                        $sale_num = Db::name('restaurant_takeaway_order_goods')->where('orderid',$orderid)->sum('num');
                        break;
                    case 'restaurant_shop':
                        $sale_num = Db::name('restaurant_shop_order_goods')->where('orderid',$orderid)->sum('num');
                        break;
                    case 'maidan':
                        $sale_num = 1;
                        break;
                }
            }

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
	public static function cerberuse_pay($orderid,$ordernum){
         $order = Db::name('cerberuse_order')->where('id', $orderid)->find();
         $cerberuse = Db::name('cerberuse')->where('id',$order['proid'])->find();
         $member = Db::name('member')->where('aid',$order['aid'])->where('id',$order['mid'])->find();
          //短信通知
         $rs = \app\common\Sms::send(aid,$order['tel'],'tmpl_yysucess',['name'=>$cerberuse['title'],'time'=>date('Y-m-d H:i',$order['starttime'])]);
         //通知管理员
         $tmplcontent = [];
         $tmplcontent['first']    = '有新订单提交成功';
         $tmplcontent['remark']   = '点击进入查看~';
         $tmplcontent['keyword1'] = ''; //店铺
         $tmplcontent['keyword2'] = date('Y-m-d H:i:s',$order['createtime']);//下单时间
         $tmplcontent['keyword3'] = $order['title']?$order['title']:'';//商品
         $tmplcontent['keyword4'] = $order['totalprice']?$order['totalprice']:'';//金额
         $tempconNew = [];
         $tempconNew['character_string2'] = $order['ordernum'];//订单号
         $tempconNew['thing8'] = '';//门店名称
         $tempconNew['thing3'] = $order['title']?$order['title']:'';//商品
         $tempconNew['amount7'] = $order['totalprice'];//金额
         $tempconNew['time4'] = date('Y-m-d H:i:s',$order['createtime']);//下单时间
         // \app\common\Wechat::sendhttmpl(aid,$order['bid'],'tmpl_orderconfirm',$tmplcontent,m_url('pages/index/index'),$order['mdid'],$tempconNew);
         \app\common\Wechat::createSendTmplData($order['aid'],$order['bid'],0,'tmpl_orderconfirm',$tmplcontent,'pages/index/index',$order['mdid'],$tempconNew,'sendhttmpl');

         $tmplcontent = [];
         $tmplcontent['thing11'] = $order['title'];
         $tmplcontent['character_string2'] = $order['ordernum'];
         $tmplcontent['phrase10'] = '已预约成功';
         $tmplcontent['amount13'] =$order['totalprice'];
         $tmplcontent['thing27'] =$order['linkman'];
         //\app\common\Wechat::sendhtwxtmpl(aid,$order['bid'],'tmpl_orderconfirm',$tmplcontent,'pages/index/index',$order['mdid']);
         \app\common\Wechat::createSendTmplData($order['aid'],$order['bid'],0,'tmpl_orderconfirm',$tmplcontent,'pages/index/index',$order['mdid'],'','sendhtwxtmpl');
         
     }
	public static function hbtk_pay($orderid,$ordernum){
	    if(getcustom('yx_hbtk')){
            $hid = Db::name('hbtk_order')->where('id',$orderid)->value('hid');
            $hd = Db::name('hbtk_activity')->where('id',$hid)->find();
            //发红包 和产生佣金
            if($hd['j1yj'] > $hd['j1sl']) $hd['j1yj'] = $hd['j1sl'];
            if($hd['j2yj'] > $hd['j2sl']) $hd['j2yj'] = $hd['j2sl'];
            if($hd['j3yj'] > $hd['j3sl']) $hd['j3yj'] = $hd['j3sl'];
            if($hd['j4yj'] > $hd['j4sl']) $hd['j4yj'] = $hd['j4sl'];
            if($hd['j5yj'] > $hd['j5sl']) $hd['j5yj'] = $hd['j5sl'];
            if($hd['j6yj'] > $hd['j6sl']) $hd['j6yj'] = $hd['j6sl'];
            if($hd['j7yj'] > $hd['j7sl']) $hd['j7yj'] = $hd['j7sl'];
            if($hd['j8yj'] > $hd['j8sl']) $hd['j8yj'] = $hd['j8sl'];
            if($hd['j9yj'] > $hd['j9sl']) $hd['j9yj'] = $hd['j9sl'];
            if($hd['j10yj'] > $hd['j10sl']) $hd['j10yj'] = $hd['j10sl'];
            if($hd['j11yj'] > $hd['j11sl']) $hd['j11yj'] = $hd['j11sl'];
            if($hd['j12yj'] > $hd['j12sl']) $hd['j12yj'] = $hd['j12sl'];
            $count =  ($hd['j1sl'] - $hd['j1yj']) + ($hd['j2sl'] - $hd['j2yj']) + ($hd['j3sl'] - $hd['j3yj']) + ($hd['j4sl'] - $hd['j4yj']) + ($hd['j5sl'] - $hd['j5yj']) + ($hd['j6sl'] - $hd['j6yj']) + ($hd['j7sl'] - $hd['j7yj']) + ($hd['j8sl'] - $hd['j8yj']) + ($hd['j9sl'] - $hd['j9yj']) + ($hd['j10sl'] - $hd['j10yj']) + ($hd['j11sl'] - $hd['j11yj']) + ($hd['j12sl'] - $hd['j12yj']);
	
            if($count>0){
                $jparr = [
                    ($hd['j1sl'] - $hd['j1yj']),
                    ($hd['j2sl'] - $hd['j2yj']),
                    ($hd['j3sl'] - $hd['j3yj']),
                    ($hd['j4sl'] - $hd['j4yj']),
                    ($hd['j5sl'] - $hd['j5yj']),
                    ($hd['j6sl'] - $hd['j6yj']),
                    ($hd['j7sl'] - $hd['j7yj']),
                    ($hd['j8sl'] - $hd['j8yj']),
                    ($hd['j9sl'] - $hd['j9yj']),
                    ($hd['j10sl'] - $hd['j10yj']),
                    ($hd['j11sl'] - $hd['j11yj']),
                    ($hd['j12sl'] - $hd['j12yj']),
                ];
                $rands = rand(1,$count);
                $qian = 0;
                foreach ($jparr as $k=>$v) {
                    if($rands > $qian && $rands <= $qian + $v){
                        $jx = $k+1;
                        $jxmc = $hd["j{$jx}mc"];
                        $jxtp = $hd["j{$jx}tp"];
                        break;
                    }
                    $qian += $v;
                }
            }
            $data = [];
            $data['jx'] = $jx;
            $data['jxtp'] = $jxtp;
            $data['jxmc'] = $jxmc;
            Db::name('hbtk_order')->where('id', $orderid)->update($data);
        }
	    
     }
	public static function car_hailing_pay($orderid){
        if(getcustom('car_hailing')) {
            $order = Db::name('car_hailing_order')->where('id', $orderid)->find();
            if (!$order) return json(['status' => 0, 'msg' => '订单不存在']);
            if ($order['status'] != 1 && $order['status'] != 12) return json(['status' => 0, 'msg' => '订单状态不符合']);
            //短信通知
            $member = Db::name('member')->where('id',$order['mid'])->find();
            if($member['tel']){
                $tel = $member['tel'];
            }else{
                $tel = $order['tel'];
            }       
            $aid = $order['aid'];
            $mid = $order['aid'];
            $rs = \app\common\Sms::send($order['aid'],$tel,'tmpl_carhailing_sucess',['ordernum'=>$order['ordernum']]);
            //支付后送券
            $couponlist = \app\common\Coupon::getpaygive($aid,$order['mid'],'car_hailing',$order['totalprice'],$order['id']);
            if($couponlist){
                foreach($couponlist as $coupon){
                    if($coupon['paygive']==1 && in_array('car_hailing',explode(',',$coupon['paygive_scene']))){
                        \app\common\Coupon::send($aid,$order['mid'],$coupon['id']);
                    }
                    if($coupon['buycarhailingprogive'] == 1){
                        $coupon['buycarhailingproids'] = explode(',',$coupon['buycarhailingproids']);
                        $coupon['carhailing_give_num'] = explode(',',$coupon['carhailing_give_num']);
                        foreach($coupon['buycarhailingproids'] as $k => $proid) {
                            if($proid == $order['proid'] && $coupon['carhailing_give_num'][$k] > 0) {
                                for($i=0;$i<$coupon['carhailing_give_num'][$k];$i++) {
                                    \app\common\Coupon::send($aid,$order['mid'],$coupon['id']);
                                }
                            }
                        }
                    }
                }
            }
            if(getcustom('yx_buyer_subsidy',$aid)){
                $res = \app\custom\Subsidy::caclOrder($aid,$orderid,0,'car_hailing');
            }
            if(getcustom('yx_queue_free_car_hailing')){
                \app\custom\QueueFree::join($order,'car_hailing');
            }
            if(getcustom('yx_mangfan_car_hailing')) {
                //支付成功计算盲返列表
                if($order['is_mangfan']){
                    $order['ogid'] = $order['id'];
                    $order['real_totalprice'] = $order['totalprice'];
                    $order['type'] = 'car_hailing';
                    $order_goods[] = $order;
                    if($order_goods){
                        \app\custom\Mangfan::createRecord($aid, $order['mid'], $order['id'], $order['ordernum'], $order['paytime'], $order_goods,false,0,'car_hailing');
                    }
                }
                //模式是支付时发放使用
                \app\custom\Mangfan::sendBonus($order['aid'],$order['mid'],$order['id'],'car_hailing');
            }
        }
    }
	public static  function taskbanner($aid,$mid){
        if(getcustom('task_banner')){
            $member = Db::name('member')->where('id',$mid)->find();
            $sysset = Db::name('task_banner_set')->where('aid',$aid)->find();
            if($member['task_banner_total']>= $sysset['total_complete_num']){
                Db::name('member')->where('id',$mid)->update(['task_banner_total' => 0]);
            }
        }
    }
	//商城订单合并支付
	public static function shop_hb_pay($orderid,$ordernum){
		$orderlist = Db::name('shop_order')->where('ordernum','like',$ordernum.'%')->select()->toArray();
		foreach($orderlist as $order){
			self::shop_pay($order['id']);
		}
	}
	//分期商品支付
	public static function shop_fenqi_pay($orderid){
        self::shop_pay($orderid);
    }
	public static function shop_fenqi_pay_change($payorder){
        $orderid = $payorder['orderid'];
        $order = Db::name('shop_order')->where('id',$orderid)->find();
		$member = Db::name('member')->where('id',$order['mid'])->find();
		$aid = $order['aid'];
		$mid = $order['mid'];
		$oglist = Db::name('shop_order_goods')->where('orderid',$orderid)->select()->toArray();

        if(getcustom('shop_product_fenqi_pay')){
            if($order['is_fenqi'] == 1){
                $now_fenqi_num = explode(',',$order['now_fenqi_num']);
                $fenqi_data = json_decode($order['fenqi_data'],true);
                foreach($fenqi_data as $fqkey=>$fq){
                    if(in_array($fq['fenqi_num'],$now_fenqi_num)){

                        if($order['fenqigive_couponid']){
                            for($i=0;$i<$fq['fenqi_give_num'];$i++) {
                                \app\common\Coupon::send($aid,$mid,$order['fenqigive_couponid']);
                            }
                        }                        
                        //分销奖励上级
                        if($member['pid'] > 0 && $order['fenqigive_fx_couponid']>0){                            
                            for($i=0;$i<$fq['fenqi_fx_num'];$i++) {
                                \app\common\Coupon::send($aid,$member['pid'],$order['fenqigive_fx_couponid']);
                            } 
                        }
                        $fenqi_data[$fqkey]['status'] = 1;
                        $fenqi_data[$fqkey]['payorderid'] = $order['payorderid'];
                        $fenqi_data[$fqkey]['paytime'] = $order['paytime'];
                        $fenqi_data[$fqkey]['ordernum'] = $payorder['ordernum'];
                        
                    }
                }
                $up = Db::name('shop_order')->where('id',$orderid)->update(['fenqi_data'=>json_encode($fenqi_data,JSON_UNESCAPED_UNICODE)]);
            }
            
        }		
	}
	//商城订单
	public static function shop_pay($orderid){
        
		$order = Db::name('shop_order')->where('id',$orderid)->find();
        $aid = $order['aid'];
        if(getcustom('sound',$aid)){
            \app\common\Sound::play($aid,'shop',$order);
        }
		$member = Db::name('member')->where('id',$order['mid'])->find();
		$mid = $order['mid'];
		$oglist = Db::name('shop_order_goods')->where('orderid',$orderid)->select()->toArray();
        Db::name('shop_order_goods')->where('orderid',$orderid)->update(['status'=>1]);

        //超级卖货
        if(getcustom('yx_collage_jipin2',$aid)) {
            \app\common\Order::jipin($aid,$order,1);
        }
        // 订单合并
        if(getcustom('shoporder_merge',$aid)){
            \app\common\Order::orderMerge($aid,$order,1);
        }

        if(getcustom('member_level_moneypay_price',$aid)){
            //第一时间修改变动的价格
            self::changeShopOrderPirce($order,$oglist,$member);
        }

        if(getcustom('douyin_groupbuy',$aid)){
            //抖音团购核销
            if($order['dydatas'] && $order['dypoi_id']){
               $res = \app\custom\DouyinGroupbuyCustom::dealpayafter($aid,$order,$oglist);
               if($res && $res['status'] == 2){
                    //已退款，停止执行下方事件
                    return;
               }
            }
        }


        if(getcustom('commission_to_score')){
            Db::name('shop_order_goods')->where('orderid',$orderid)->update(['paytime'=>time()]);
        }

        if(getcustom('yx_offline_subsidies',$aid)){
            //线下补助
            \app\common\Member::offlineSubsidiesLog($aid,$order,'shop',0);
        }

        //逢单奖励时更新奖金数据
		if(getcustom('fengdanjiangli')){
			foreach($oglist as $og){
				$product = Db::name('shop_product')->where('id',$og['proid'])->find();
				if($product['fengdanjiangli'] && $product['commissionset']==2){
					$commissiondata = json_decode($product['commissiondata2'],true);
					$fengdanjiangliArr = explode(',',$product['fengdanjiangli']);
					$num = $og['num'];
					$ogupdate = [];
					/*
					$memberlevel = Db::name('member_level')->where('aid',$aid)->where('id',$member['levelid'])->find();
					if($memberlevel['sort'] > 1){
						Db::name('shop_order_goods')->where('id',$og['id'])->update(['isdan'=>1]);
						$dannum = Db::name('shop_order_goods')->where('aid',$aid)->where('proid',$product['id'])->where('status','in','1,2,3')->where("(mid=".$og['parent1']." and isdan=1) or parent1=".$og['parent1'])->sum('num');
						for($i=0;$i<$num;$i++){
							$thisdannum = ($dannum+1+$i)%10;
							if(in_array($thisdannum.'',$fengdanjiangliArr)){
								Db::name('shop_order_goods')->where('id',$og['id'])->update(['isfenhong'=>2]);
								Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$mid,'frommid'=>$mid,'orderid'=>$orderid,'ogid'=>$og['id'],'type'=>'shop','commission'=>$commissiondata[$memberlevel['id']]['commission1'],'remark'=>'复购奖励','createtime'=>time()]);
							}
						}
					}
					*/

					if($og['parent1']){
						$parent1 = Db::name('member')->where('id',$og['parent1'])->find();
						$agleveldata1 = Db::name('member_level')->where('aid',$aid)->where('id',$parent1['levelid'])->find();
						$dannum = Db::name('shop_order_goods')->where('aid',$aid)->where('proid',$product['id'])->where('status','in','1,2,3')->where("parent1",$og['parent1'])->sum('num');

						$ogupdate['parent1commission'] = 0;
						for($i=0;$i<$num;$i++){
							$thisdannum = ($dannum+1+$i)%10;
							if(in_array($thisdannum.'',$fengdanjiangliArr)){
								$ogupdate['parent1commission'] += $commissiondata[$agleveldata1['id']]['commission1'];
								$ogupdate['isfenhong'] = 2;
							}
						}
					}
					if($og['parent2']){
						$parent2 = Db::name('member')->where('id',$og['parent2'])->find();
						$agleveldata2 = Db::name('member_level')->where('aid',$aid)->where('id',$parent2['levelid'])->find();
						$dannum = Db::name('shop_order_goods')->where('aid',$aid)->where('proid',$product['id'])->where('status','in','1,2,3')->where("parent2",$og['parent2'])->sum('num');
						$ogupdate['parent2commission'] = 0;
						for($i=0;$i<$num;$i++){
							$thisdannum = ($dannum+1+$i)%10;
							if(in_array($thisdannum.'',$fengdanjiangliArr)){
								$ogupdate['parent2commission'] += $commissiondata[$agleveldata2['id']]['commission2'];
								$ogupdate['isfenhong'] = 2;
							}
						}
					}
					if($og['parent3']){
						$parent3 = Db::name('member')->where('id',$og['parent3'])->find();
						$agleveldata3 = Db::name('member_level')->where('aid',$aid)->where('id',$parent3['levelid'])->find();
						$dannum = Db::name('shop_order_goods')->where('aid',$aid)->where('proid',$product['id'])->where('status','in','1,2,3')->where("parent3",$og['parent3'])->sum('num');
						$ogupdate['parent3commission'] = 0;
						for($i=0;$i<$num;$i++){
							$thisdannum = ($dannum+1+$i)%10;
							if(in_array($thisdannum.'',$fengdanjiangliArr)){
								$ogupdate['parent3commission'] += $commissiondata[$agleveldata3['id']]['commission3'];
								$ogupdate['isfenhong'] = 2;
							}
						}
					}
					if($ogupdate){
						Db::name('shop_order_goods')->where('id',$og['id'])->update($ogupdate);
						if($og['parent1'] && $ogupdate['parent1commission']){
							Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$og['parent1'],'frommid'=>$og['mid'],'orderid'=>$orderid,'ogid'=>$og['id'],'type'=>'shop','commission'=>$ogupdate['parent1commission'],'remark'=>t('下级').'购买商品奖励','createtime'=>time()]);
						}
						if($og['parent2'] && $ogupdate['parent2commission']){
							Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$og['parent2'],'frommid'=>$og['mid'],'orderid'=>$orderid,'ogid'=>$og['id'],'type'=>'shop','commission'=>$ogupdate['parent2commission'],'remark'=>t('下二级').'购买商品奖励','createtime'=>time()]);
						}
						if($og['parent3'] && $ogupdate['parent3commission']){
							Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$og['parent3'],'frommid'=>$og['mid'],'orderid'=>$orderid,'ogid'=>$og['id'],'type'=>'shop','commission'=>$ogupdate['parent3commission'],'remark'=>t('下三级').'购买商品奖励','createtime'=>time()]);
						}
					}
				}
			}
		}
		if(getcustom('to86yk')){
			foreach($oglist as $og){
				if($og['to86yk_tid']){
					$shopset = Db::name('shop_sysset')->where('aid',$aid)->find();
					$formdata = \app\model\Freight::getformdata($orderid,'shop_order');
					$successnum = 0;
					for($i=0;$i<$og['num'];$i++){
						$url = 'http://api.yukawl.cn/api.php?act=pay&tid='.$og['to86yk_tid'].'&input1='.$formdata[0][1].'&user='.$shopset['to86yk_user'].'&pass='.$shopset['to86yk_pwd'];
						$rs = request_get($url);
						//\think\facade\Log::write($url);
						\think\facade\Log::write($rs);
						$rs = json_decode($rs,true);
						\think\facade\Log::write($rs);
						if($rs && $rs['code'] == 0) $successnum++;
					}
					Db::name('shop_order_goods')->where('id',$og['id'])->update(['to86yk_successnum'=>$successnum]);
				}
			}
		}

		if(getcustom('product_baodan')){
		   
           foreach($oglist as $og){
               $product = Db::name('shop_product')->where('id',$og['proid'])->find();
               if($product && $product['product_baodan'] ==1){
                   $field = 'baodan_beishu';
                   if(getcustom('fenhong_money_weishu')){
                       $field.=',fenhong_money_weishu';
                   }
                   $sysset = Db::name('admin_set')->where('aid',$og['aid'])->field($field)->find();
                   $max_money = $og['sell_price']* $og['num'] * $sysset['baodan_beishu']; 
                   //先更新上限值
                   //最终用户的上限值
                   $member_baodan_max = $member['baodan_max'] + $max_money;
                   Db::name('member')->where('id',$og['mid'])->update(['baodan_max' => $member_baodan_max]);
                    //判断是否有冻结,有冻结才操作解冻
                    if($member['baodan_freeze'] >0){
                         $member_total_commisiion = $member['totalcommission'];
                         //总佣金+冻结的  < 上限值，能使用的解冻范围就是上限值-用户的总佣金 
                         $max_nofreeze = 0;
                         if($member['totalcommission'] <= $member_baodan_max){
                             $max_nofreeze =  $member_baodan_max - $member['totalcommission'];
                         }
                         if($max_nofreeze >=$member['baodan_freeze'] ){
                             $nofreeze = $member['baodan_freeze'];
                         }else{
                             $nofreeze  = $max_nofreeze;
                         }
                         if($nofreeze > 0){
                             //加佣金
                             $commission = $member['commission'] + $nofreeze;
                             $totalcommission = $member['totalcommission'] + $nofreeze;
                              $mupdate = [
                                    'commission' => $commission,
                                    'totalcommission' => $totalcommission
                              ];
                             //减冻结
                             $weishu = $sysset['fenhong_money_weishu']??2;
                             $sy_freeze =$member['baodan_freeze']-$nofreeze;
                             $sy_freeze = dd_money_format( $sy_freeze,$weishu);
                             $mupdate['baodan_freeze'] = $sy_freeze;
                             Db::name('member')->where('id',$og['mid'])->update($mupdate);
                             //加记录
                             $baodan_data = [];
                             $baodan_data['aid'] = $aid;
                             $baodan_data['mid'] = $mid;
                             $baodan_data['commission'] = $nofreeze;
                             $baodan_data['after'] = $sy_freeze;
                             $baodan_data['createtime'] = time();
                             $baodan_data['remark'] = '报单产品佣金解冻，产品ID:'.$og['proid'];
                             Db::name('member_baodan_freeze_log')->insert($baodan_data);
                         }
                        
                    }
               }
           }
        }
        if(getcustom('product_bonus_pool')){
            \app\common\Order::prodcutBonusPool($aid,$order,$oglist,$member);
        }
		if(getcustom('coupon_xianxia_buy')){
		    //购买一单升级,如果当前用户的等级 = 默认等级，进行升级
            $default_level = Db::name('member_level')->where('aid',$aid)->where('isdefault',1)->find();
            if($member['levelid'] ==$default_level['id']){
                $next_level = Db::name('member_level')->where('aid',$aid)->where('sort','>',$default_level['sort'])->order('sort asc')->find();
                if($next_level){
                    Db::name('member')->where('id',$member['id'])->update(['levelid' => $next_level['id']]);
                }
            }
        }
        
        
		if($order['fromwxvideo'] == 1){
			if($order['paytypeid'] !=60){ //不是视频号微信支付的
				Db::name('shop_order')->where('id',$orderid)->update(['fromwxvideo'=>0]);
				$order['fromwxvideo'] = 0;
				//\app\common\Wxvideo::createorder($orderid);
			}else{
				\app\common\Wxvideo::orderpay($orderid);
			}
		}

        //在线卡密
        if($order['freight_type']==4){
            $og = Db::name('shop_order_goods')->where('orderid',$order['id'])->find();
            $codelist = Db::name('shop_codelist')->where('proid',$og['proid'])->where('status',0)->order('id')->limit($og['num'])->select()->toArray();
            if($codelist && count($codelist) >= $og['num']){
                $pscontent = [];
                foreach($codelist as $codeinfo){
                    $pscontent[] = $codeinfo['content'];
                    Db::name('shop_codelist')->where('id',$codeinfo['id'])->update(['orderid'=>$order['id'],'ordernum'=>$order['ordernum'],'headimg'=>$member['headimg'],'nickname'=>$member['nickname'],'buytime'=>time(),'status'=>1]);
                }
                $pscontent = implode("\r\n",$pscontent);
                Db::name('shop_order')->where('id',$order['id'])->update(['freight_content'=>$pscontent,'status'=>2,'send_time'=>time()]);
                Db::name('shop_order_goods')->where('orderid',$order['id'])->update(['status'=>2]);
                $express_com = '卡密订单';
                $express_no = $order['ordernum'];
                //订单发货通知
                $tmplcontent = [];
                $tmplcontent['first'] = '您的订单已发货';
                $tmplcontent['remark'] = '请点击查看详情~';
                $tmplcontent['keyword1'] = $order['title'];
                $tmplcontent['keyword2'] = $express_com;
                $tmplcontent['keyword3'] = $express_no;
                $tmplcontent['keyword4'] = $order['linkman'].' '.$order['tel'];
                $tmplcontentNew = [];
                $tmplcontentNew['thing4'] = $order['title'];//商品名称
                $tmplcontentNew['thing13'] = $express_com;//快递公司
                $tmplcontentNew['character_string14'] = $express_no;//快递单号
                $tmplcontentNew['thing16'] = $order['linkman'].' '.$order['tel'];//收货人
                //\app\common\Wechat::sendtmpl(aid,$order['mid'],'tmpl_orderfahuo',$tmplcontent,m_url('pages/my/usercenter'),$tmplcontentNew);
                \app\common\Wechat::createSendTmplData($order['aid'],0,$order['mid'],'tmpl_orderfahuo',$tmplcontent,'pages/my/usercenter',$order['mdid'],$tmplcontentNew,'sendtmpl');
                //订阅消息
                $tmplcontent = [];
                $tmplcontent['thing2'] = $order['title'];
                $tmplcontent['thing7'] = $express_com;
                $tmplcontent['character_string4'] = $express_no;
                $tmplcontent['thing11'] = $order['address']?:"卡密订单";

                $tmplcontentnew = [];
                $tmplcontentnew['thing29'] = $order['title'];
                $tmplcontentnew['thing1'] = $express_com;
                $tmplcontentnew['character_string2'] = $express_no;
                $tmplcontentnew['thing9'] = $order['address']?:"卡密订单";
                //\app\common\Wechat::sendwxtmpl(aid,$order['mid'],'tmpl_orderfahuo',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
                \app\common\Wechat::createSendTmplData($order['aid'],0,$order['mid'],'tmpl_orderfahuo',$tmplcontentnew,'pages/my/usercenter',0,$tmplcontent,'sendwxtmpl');

                //短信通知
                $member = Db::name('member')->where('id',$order['mid'])->find();
                if($member['tel']){
                    $tel = $member['tel'];
                }else{
                    $tel = $order['tel'];
                }
                $rs = \app\common\Sms::send(aid,$tel,'tmpl_orderfahuo',['ordernum'=>$order['ordernum'],'express_com'=>$express_com,'express_no'=>$express_no]);
            }
            if($order['fromwxvideo'] == 1){
                \app\common\Wxvideo::deliverysend($orderid);
            }

            //发货信息录入 微信小程序+微信支付
            if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                \app\common\Order::wxShipping($order['aid'],$order);
            }
            if(getcustom('plug_zhiming')){
                \app\common\Order::collect($order);
                Db::name('shop_order')->where('id',$orderid)->update(['status'=>3,'collect_time'=>time()]);
                Db::name('shop_order_goods')->where('orderid',$orderid)->update(['status'=>3,'endtime'=>time()]);
            }
        }

        if(getcustom('h5zb') && $order['roomid']>0){
            Db::name('h5zb_room_record')->insert([
                'aid'=>$order['aid'],
                'bid'=>$order['bid'],
                'createtime'=>time(),
                'remark'=>'直播间下单',
                'nickname'=>$member['nickname'],
                'headimg'=>$member['headimg'],
                'roomid'=>$order['roomid'],
                'eventid'=>0,
                'eventdata'=>'',
                'type'=>'buy'
            ]);
        }

        //加权分红份数记录
        if(getcustom('fenhong_jiaquan_bylevel')){
            //需要发放加权分红份数的会员【直推和自购】
            $jqMemberArr = [];
            //自购
            if($member['levelid']){
                $memberLevel = Db::name('member_level')->where('aid',$order['aid'])->where('id',$member['levelid'])->field('id,fenhong_zt_copies, 1 isZg')->find();
                $memberLevel['mid'] = $member['id'];
                $jqMemberArr[] = $memberLevel;
            }
            //直推
            if($member['pid']){
                $memberParent = Db::name('member')->where('aid',$aid)->where('id',$member['pid'])->find();
                if($memberParent && $memberParent['levelid']) {
                    $memberParentLevel = Db::name('member_level')->where('aid', $aid)->where('id', $memberParent['levelid'])->field('id,fenhong_zt_copies, 0 isZg')->find();
                    $memberParentLevel['mid'] = $memberParent['id'];
                    $jqMemberArr[] = $memberParentLevel;
                }
            }
            $allJiaQuan = [];
            foreach ($jqMemberArr as $mk => $jqMember) {
                if(empty($jqMember['fenhong_zt_copies'])){
                    continue;
                }
                //加权分红记录
                foreach ($oglist as $k => $goods) {
                    if ($goods['fenhong_jq_status'] != 1) {
                        continue;
                    }
                    $allJiaQuan[] = [
                        'aid' => $goods['aid'],
                        'bid' => $goods['bid'],
                        'orderid' => $goods['orderid'],
                        'ogid' => $goods['id'],
                        'mid' => $jqMember['mid'],
                        'frommid' => $goods['mid'],
                        'type' => 'shop',
                        'remark' => $jqMember['isZg']?'购物加权分红奖励':'直推购物加权分红奖励',
                        'createtime' => time(),
                        'status' => 0,//确认收货后算有效份数
                        'copies' => $jqMember['fenhong_zt_copies'],
                    ];
                }
            }
            if($allJiaQuan){
                Db::name('member_fenhong_jiaquan')->insertAll($allJiaQuan);
            }
        }

		//支付后送券
		$couponlist = \app\common\Coupon::getpaygive($aid,$mid,'shop',$order['totalprice'],$order['id']);
		if($couponlist){
            $proids = db('shop_order_goods')->where('orderid','=',$order['id'])->column('proid');
			foreach($couponlist as $coupon){
				if($coupon['paygive']==1 && $coupon['paygive_minprice'] <= $order['totalprice'] && $coupon['paygive_maxprice'] >= $order['totalprice'] && in_array('shop',explode(',',$coupon['paygive_scene']))){
					\app\common\Coupon::send($aid,$mid,$coupon['id']);
				}
				if($coupon['buyprogive'] == 1){
					$coupon['buyproids'] = explode(',',$coupon['buyproids']);
					$coupon['buypro_give_num'] = explode(',',$coupon['buypro_give_num']);
					foreach($coupon['buyproids'] as $k => $proid) {
						if(in_array($proid, $proids) && $coupon['buypro_give_num'][$k] > 0) {
                            foreach($oglist as $og){
                                if($og['proid'] == $proid){
                                    //give_num_type 赠送数量类型,0按设置数量,1按设置数量*购买数量
                                    $couponGiveNum = $coupon['buypro_give_num'][$k]??0;
                                    if($coupon['give_num_type'] == 1){
                                        $couponGiveNum = $couponGiveNum * $og['num'];
                                    }
                                    for($i=0; $i<$couponGiveNum; $i++) {
                                        $params = [];
                                        if(getcustom('shoporder_refund_sendcoupon')){
                                            $params = ['source'=>'shop','orderid'=>$orderid,'proid'=>$proid,'ogid'=>$og['id']];
                                        }
                                        \app\common\Coupon::send($aid,$mid,$coupon['id'],false,0,0,0,$params);
                                    }
                                }
                            }
						}
					}
				}
			}
		}
		//送积分
		if($order['givescore2'] > 0){
			if($order['bid'] > 0){
				\app\common\Business::addmemberscore($aid,$order['bid'],$order['mid'],$order['givescore2'],'购买产品赠送'.t('积分'));
			}else{
				\app\common\Member::addscore($aid,$order['mid'],$order['givescore2'],'购买产品赠送'.t('积分'));
			}
		}
		if(getcustom('member_commission_max',$aid)){
            //送佣金上限
            if($order['give_commission_max2'] > 0){
                \app\common\Member::addcommissionmax($aid,$order['mid'],$order['give_commission_max2'],'购买商品赠送'.t('佣金上限'),'shop',$orderid);
            }
        }

		if(getcustom('product_payaftergive')){
			foreach($oglist as $og){
				$product = Db::name('shop_product')->where('id',$og['proid'])->find();
				if($product['paygive_choujiangtimes'] > 0 && $product['paygive_choujiangid'] > 0){
					$sharelog = Db::name('choujiang_sharelog')->where('aid',$aid)->where('hid',$product['paygive_choujiangid'])->where('mid',$mid)->find();
					if($sharelog){
						Db::name('choujiang_sharelog')->where('id',$sharelog['id'])->inc('extratimes',$product['paygive_choujiangtimes'])->update();
					}else{
						$data = [];
						$data['aid'] = $aid;
						$data['hid'] = $product['paygive_choujiangid'];
						$data['mid'] = $mid;
						$data['extratimes'] = $product['paygive_choujiangtimes'];
						Db::name('choujiang_sharelog')->insert($data);
					}
				}
				if($product['paygive_money'] > 0){
					\app\common\Member::addmoney($aid,$mid,$product['paygive_money'],'购买商品赠送');
				}
				if($product['paygive_score'] > 0){
					\app\common\Member::addscore($aid,$mid,$product['paygive_score'],'购买商品赠送');
				}
				if($product['paygive_couponid'] > 0){
					\app\common\Coupon::send($aid,$mid,$product['paygive_couponid']);
				}
			}
		}

        if(getcustom('member_commission_max',$aid) && getcustom('add_commission_max',$aid)){
            //送佣金上限
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
        if(getcustom('consumer_value_add')){
            //送绿色积分
            if($order['give_green_score2'] > 0){
                \app\common\Member::addgreenscore($aid,$order['mid'],$order['give_green_score2'],'购买商品赠送'.t('绿色积分'),'shop_order',$orderid,0,$order['give_maximum'],0);
            }
            //放入奖金池
            if($order['give_bonus_pool2'] > 0){
                \app\common\Member::addbonuspool($aid,$order['mid'],$order['give_bonus_pool2'],'购买商品赠送'.t('奖金池'),'shop_order',$orderid,0,$order['give_green_score2']);
            }
            if(getcustom('green_score_reserves')){
                //订单进入预备金
                if($order['give_green_score_reserves2']>0){
                    \app\custom\GreenScore::addgreenscorereserves($aid,$order['mid'],$order['give_green_score_reserves2'],'购买商品赠送'.t('预备金'),'shop_order',$orderid);
                }
            }
        }
        if(getcustom('active_coin',$aid)){
            //送激活币
            \app\common\Order::giveActiveCoin($aid,$order);
        }

        if(getcustom('active_score')){
            //送积分
            \app\common\Order::giveActiveScore($aid,$order);
        }

		if(getcustom('shopshd_shuixitie')){
			$shd_style1_no = Db::name('shop_sysset')->where('aid',$aid)->value('shd_style1_no');
			$shd_style1_no = intval($shd_style1_no);
			$shd_style1_no = $shd_style1_no + 1;
			foreach($oglist as $k=>$og){
				//if(count($oglist) > 1){
				//	$shd_style1_no_this = $shd_style1_no.'-'.($k+1 > 9 ? '' : '0') . ($k+1);
				//}else{
					$shd_style1_no_this = $shd_style1_no;
				//}
				Db::name('shop_order_goods')->where('id',$og['id'])->update(['shd_style1_no'=>$shd_style1_no_this]);
			}
			Db::name('shop_sysset')->where('aid',$aid)->update(['shd_style1_no'=>$shd_style1_no]);
		}
        
        if(getcustom('ciruikang_fenxiao')){
           //会员当前等级及应该赠送的数量
           \app\custom\CiruikangCustom::deal_givenum($order);
            //一次购买升级
            \app\common\Member::uplv($aid,$mid,'shop',['onebuy'=>1,'onebuy_orderid'=>$order['id']]);
        }else{
            \app\common\Member::uplv($aid,$mid);
        }
        if(getcustom('yx_butie_activity',$aid)){
            //消费补贴活动
            \app\custom\ButieActivity::createOrder($aid,$order['bid'],$member,$order,$oglist);
        }

        if(getcustom('member_shougou_parentreward')){
            $changelock = true;
            if(getcustom('ciruikang_fenxiao')){
                //查询当前用户，进行首购解锁
                $oldlevel = Db::name('member_level')->where('id',$member['levelid'])->field('id,up_pro_orderstatus2')->find();
                //如果统计的是确认收货后，则进行不解锁
                if($oldlevel && $oldlevel['up_pro_orderstatus2']){
                    $changelock = false;
                }
            }
            if($changelock){
                Db::name('member_commission_record')->where('orderid',$order['id'])->where('type','shop')->where('status',0)->where('islock',1)->where('aid',$order['aid'])->where('remark','like','%首购奖励')->update(['islock'=>0]);
            }
        }
        
        $shopOrderType = 1;//商城订单
        if(getcustom('product_weight') && $order['product_type']==2){
            $shopOrderType = 0;
            //称重商品不打印
           // \app\common\Wifiprint::print($aid,'shop',$order['id'],1,0);
        }
        if($shopOrderType==1){
            \app\common\Wifiprint::print($aid,'shop',$order['id']);
        }
        if(getcustom('erp_wangdiantong')){
            $c = new \app\custom\Wdt($aid,$order['bid']);
            $c->orderCreate($order['id']);
        }
        if(getcustom('product_pingce')){
            if($order['is_pingce'] == 1){
                $pingce = json_decode($order['pingce'],true);
                $pingce['user_id'] = $order['id'];
                $pingce['aid'] = $aid;
                $pingceinfo = (new \app\custom\Haneo($aid))->createUserNoSend($pingce);
                Db::name('shop_order')->where('id',$order['id'])->update(['pingce_return'=>$pingceinfo]);    
            }                
        }

        if(getcustom('bonus_pool_gold')){
            //思明定制奖金池
            \app\custom\BonusPoolGold::orderBonusPool($order['aid'],$order['mid'],$order['id'],'shop');
        }
        if(getcustom('yx_buy_product_manren_choujiang')){
            \app\custom\ManrenChoujiang::choujiang($oglist);
        }
        if(getcustom('wanyue10086') && $order['coupon_rid']) {
            $couponrids = explode(',',$order['coupon_rid']);
            foreach($couponrids as $couponrid){
                $couponrecord = Db::name('coupon_record')->where('aid',$order['aid'])->where('id',$couponrid)->find();
                if($couponrecord && $couponrecord['is_api_get']) (new \app\custom\Wanyue10086($order['aid']))->hexiaoNotice($couponrecord,$order['ordernum']);
            }
        }
        if(getcustom('erp_hupun')){
            $wln = new \app\custom\Hupun($aid);
            $wln->orderPush($order['id']);
        }
        // 释放推荐人冻结积分
        if(getcustom('yx_score_freeze') && getcustom('yx_score_freeze_release')){
            $freeze_set = Db::name('score_freeze_set')->where('aid',$aid)->where('bid',$order['bid'])->find();
            $pid = Db::name('member')->where('aid', $aid)->where('id', $mid)->value('pid');
            if($freeze_set && $freeze_set['status'] && $freeze_set['down_release_beishu']>0 && $pid){
                // 积分位数
                $score_weishu_custom = getcustom('score_weishu');
                $score_weishu = 0;
                if($score_weishu_custom){
                    $score_weishu = Db::name('admin_set')->where('aid',$aid)->value('score_weishu');
                    $score_weishu = $score_weishu?$score_weishu:0;
                }
                // 冻结积分剩余
                $score_freeze = Db::name('member')->where('aid', $aid)->where('id', $pid)->value('score_freeze');
                // 消费赠送
                $release_score = dd_money_format($order['totalprice']*$freeze_set['down_release_beishu'],$score_weishu);
                if($score_freeze>0 && $release_score > 0){   
                    if($release_score > $score_freeze){
                        $release_score = $score_freeze;
                    }                                            
                    \app\common\Member::addscore($aid,$pid,$release_score,'下级消费释放,订单号:'.$order['ordernum'],'',0,'','',['is_release' =>1]);
                    \app\common\Member::addscorefree($aid,$pid,-$release_score,'下级消费释放,订单号:'.$order['ordernum'],'',0);
                    //增加释放记录
                    Db::name('score_freeze_release_log')->insertGetId([
                        'aid' => $aid,
                        'bid' => $order['bid'],
                        'mid' => $pid,
                        'before' =>$score_freeze,
                        'ratio' => '',
                        'score' => $release_score,
                        'after' => dd_money_format($score_freeze - $release_score,$score_weishu),
                        'createtime' => time(),
                        'remark' => '下级消费释放,订单号:'.$order['ordernum']
                    ]);
                }

            }
        }

        if(getcustom('fenhong_gudong_huiben')){
            //赠送回本股东分红额度
            \app\common\Order::giveHuibenMaximum($aid,$order['id']);
        }

        // 赠送优惠券
        if(getcustom('shopbuy_give_coupon')){
            $pid = Db::name('member')->where('aid', $aid)->where('id', $mid)->value('pid');
            $plevelid = Db::name('member')->where('aid', $aid)->where('id', $pid)->value('levelid');

            foreach($oglist as $og){
                 
                 $shop_product_coupon = Db::name('shop_product')->where('id',$og['proid'])->field('shopbuy_give_coupon,coupon_gettj')->find();
                 $shopbuy_give_coupon = $shop_product_coupon['shopbuy_give_coupon'];
                 $coupon_gettj = $shop_product_coupon['coupon_gettj'];
                 $tjr_send = 0;
                 if($coupon_gettj){
                     $coupon_gettj = explode(',', $coupon_gettj);
                     if(in_array('-1', $coupon_gettj) || in_array($plevelid, $coupon_gettj)){
                        $tjr_send = 1;
                     }
                 }
                

                 if(!empty($shopbuy_give_coupon) && $pid && $tjr_send == 1){
                    $shopbuy_give_coupon = json_decode($shopbuy_give_coupon,true);
                    foreach ($shopbuy_give_coupon as $k => $v) {
                        if(empty($v['coupon_id']) || empty($v['coupon_num'])) {
                            continue; // 跳过无效的优惠券配置
                        }
                        // 自己+上级
                        $sendnum = $v['coupon_num']*$og['num'];
                        for ($i=0; $i < $sendnum; $i++) { 
                            \app\common\Coupon::send($aid,$mid,$v['coupon_id']);
                            \app\common\Coupon::send($aid,$pid,$v['coupon_id']);
                        }
                    }
                 }
             }
        }

        //绑定门店
        if(getcustom('mendian_usercenter')){
            //门店中心下单之后锁定门店为该会员的门店
            if($order['mdid']){
                \app\custom\MendianUsercenter::editMemberMdid($order['aid'],$order['mid'],$order['mdid']);
            }
        }
        if(getcustom('shop_product_certificate')){
            //生成商品证书
            \app\model\ShopProduct::createProductCertificateByForm($aid,$order['bid'],$oglist);
        }
        // die('stop');
        
        if(getcustom('deposit')){
            //1、跟随订单购买的押金订单，更新订单状态，并更新会员的押金账户
            $deposit_order_list = Db::name('deposit_order')->where('aid',$aid)->where('orderid',$orderid)->where('from_type','shop')->select()->toArray();
            foreach($deposit_order_list as $dk=>$deposit_order){
                Db::name('deposit_order')->where('aid',$aid)->where('id',$deposit_order['id'])->update(['status' => 1,'paytime' => time()]);
                //增加押金
                \app\common\Member::addDeposit($aid,$deposit_order['bid'],$deposit_order['mid'],$deposit_order['num'],$deposit_order['deposit_id'],'订单下单购买'.t('押金').'，订单号：'.$order['ordernum'],['orderid' => $deposit_order['id']]);
            }
            //2、购买完成后，冻结的应该给解冻进行返还
//            foreach($oglist as $k=>$og){
//                   //下单时，被冻结了，这里应该解冻
//                  if($og['deposit_hexiao_num'] > 0){
//                      \app\common\Member::addDeposit($aid,$og['bid'],$og['mid'],$og['deposit_hexiao_num'],$og['deposit_id'],'订单下单解冻'.t('押金'),['orderid' => $order['id']]);
//                  }
//            }
        }
        if(getcustom('deposit') &&  getcustom('deposit_business')){
            if($order['bid'] > 0){
                foreach($oglist as $og){
                    if($og['deposit_num'] > 0 && $og['deposit_totalprice'] >0){
                        \app\common\Business::addDeposit($order['aid'],$order['bid'], $order['deposit_totalprice'],$og['deposit_id'],'商城订单，订单号：'.$order['ordernum'],'shop');
                    }
                }
            }
        }

        if(getcustom('commission_ab')){
            //ab单循环分销奖
            \app\custom\CommissionAb::caclShopOrder($aid,$orderid);
        }
		//公众号通知 订单支付成功
		$tmplcontent = [];
        if($order['paytypeid'] != 4) {
            $tmplcontent['first'] = '有新订单支付成功';
        } else {
            $tmplcontent['first'] = '有新订单下单成功（'.$order['paytype'].'）';
        }
		$tmplcontent['remark'] = '点击进入查看~';
		$tmplcontent['keyword1'] = $member['nickname']; //用户名
		$tmplcontent['keyword2'] = $order['ordernum'];//订单号
		$tmplcontent['keyword3'] = $order['totalprice'].'元';//订单金额
		$tmplcontent['keyword4'] = $order['title'];//商品信息
        $tmplcontentNew = [];
        $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
        $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
        $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
        $tmplcontentNew['amount5'] = $order['totalprice']==0?'0.00':$order['totalprice'];//订单金额
        $tmplcontentNew['thing3'] = $order['title'];//商品信息
		//\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('admin/order/shoporder', $aid),$order['mdid'],$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/shoporder',$order['mdid'],$tmplcontentNew,'sendhttmpl');


        if($order['paytypeid'] != 4) {
            $tmplcontent['first'] = '恭喜您的订单已支付成功';
        } else {
            $tmplcontent['first'] = '恭喜您的订单已下单成功';
        }
		//$rs = \app\common\Wechat::sendtmpl($aid,$mid,'tmpl_orderpay',$tmplcontent,m_url('pages/my/usercenter', $aid),$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,0,$mid,'tmpl_orderpay',$tmplcontent,'pages/my/usercenter',0,$tmplcontentNew,'sendtmpl');

//		$tmplcontent = [];
//		$tmplcontent['thing11'] = $order['title'];
//		$tmplcontent['character_string2'] = $order['ordernum'];
//		if($order['paytypeid'] != 4) {
//			$tmplcontent['phrase10'] = '已支付';
//		}else{
//			$tmplcontent['phrase10'] = $order['paytype'];
//		}
//		$tmplcontent['amount13'] = $order['totalprice'].'元';
//		$tmplcontent['thing27'] = $member['nickname'];

        //小程序订阅消息通知管理员 支付成功
        $tmplcontent = [];
        $tmplcontent['character_string3'] = $order['ordernum'];//订单编号
        $tmplcontent['time4'] = $order['paytime'] ? date('Y-m-d H:i:s',$order['paytime']) : '';//支付时间
        $tmplcontent['amount9'] = $order['totalprice'] ? $order['totalprice'].'元' : '';//订单金额
        $tmplcontent['thing12'] = $order['title'] ?? '';//商品名称
        $tmplcontent['thing14'] = $member['nickname'] ?? '';//客户信息

		//\app\common\Wechat::sendhtwxtmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,'admin/order/shoporder',$order['mdid']);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/shoporder',$order['mdid'],'','sendhtwxtmpl');

		//短信通知
		$rs = \app\common\Sms::send($aid,$member['tel']?$member['tel']:$order['tel'],'tmpl_orderpay',['ordernum'=>$order['ordernum']]);

		$set = Db::name('admin_set')->where('aid',$aid)->find();
		if($set['fxjiesuantime'] == 1 && $set['fxjiesuantime_delaydays'] == '0'){
			\app\common\Order::giveCommission($order,'shop');
            if(getcustom('yx_shop_order_team_yeji_bonus',$aid)) {
                \app\common\Order::giveShopBonus($order,$aid);
            }
		}

		if(getcustom('forcerebuy') && $member['commission_isfreeze'] == 1){
			$forcerebuy = Db::name('forcerebuy')->where('aid',$aid)->where('wfgtype',0)->where('status',1)->where("find_in_set('-1',gettj) or find_in_set('".$member['levelid']."',gettj)")->find();
			if($forcerebuy){
				$orderwhere = [];
				$orderwhere[] = ['aid','=',$aid];
				$orderwhere[] = ['mid','=',$mid];
				$orderwhere[] = ['status','in','1,2,3'];
				if($forcerebuy['type'] == 0){
					$orderwhere[] = ['isfg','=',1];
				}else{
					if($forcerebuy['daytype'] == 0){
						$starttime = strtotime(date('Y-m-01'));
						$endtime = time();
					}elseif($forcerebuy['daytype'] == 1){
						if(date('m') == '01' || date('m') == '02' || date('m') == '03'){
							$starttime = strtotime(date('Y-01-01'));
						}
						if(date('m') == '04' || date('m') == '05' || date('m') == '06'){
							$starttime = strtotime(date('Y-04-01'));
						}
						if(date('m') == '07' || date('m') == '08' || date('m') == '09'){
							$starttime = strtotime(date('Y-07-01'));
						}
						if(date('m') == '10' || date('m') == '11' || date('m') == '12'){
							$starttime = strtotime(date('Y-10-01'));
						}
						$endtime = time();
					}elseif($forcerebuy['daytype'] == 2){
						$starttime = strtotime(date('Y-01-01'));
						$endtime = time();
					}else{
						$starttime = 0;
						$endtime = time();
					}
					$orderwhere[] = ['createtime','>=',$starttime];
				}
				if($forcerebuy['fwtype'] == 1){
					$orderwhere[] = ['cid','in',$forcerebuy['categoryids']];
				}elseif($forcerebuy['fwtype'] == 2){
					$orderwhere[] = ['proid','in',$forcerebuy['productids']];
				}
				$totalprice = Db::name('shop_order_goods')->where($orderwhere)->sum('totalprice');
				if($totalprice >= $forcerebuy['price']){
					Db::name('member')->where('id', $member['id'])->update(['commission_isfreeze' => 0]);
				}
			}
		}

        if(getcustom('yx_invite_cashback')){
            //邀请返现
            if($order && $oglist && $member){
                \app\custom\OrderCustom::deal_invitecashback($aid,$order,$oglist,$member);
            }
        }

        if(getcustom('yx_queue_free')){
            \app\custom\QueueFree::join($order,'shop');
        }

		if(getcustom('mendian_upgrade')){
			$admin = Db::name('admin')->where('id',$aid)->field('mendian_upgrade_status')->find();
			if($admin['mendian_upgrade_status']==1 && $order['mdid']>0){
				 \app\custom\Mendian::createCommission($order);
				 $mendian_sysset = Db::name('mendian_sysset')->where('aid',$aid)->find();
				//发送消息通知
				if($mendian_sysset['notice_status']==1){
					$mendian =  Db::name('mendian')->field('mid')->where('id',$order['mdid'])->find();
					$member =  Db::name('mendian')->where('id',$mendian['mid'])->find();
					if($member['wxopenid']){
					   	$tmplcontent = [];
						$tmplcontent['thing11']  = $order['title']?$order['title']:'';//商品
						$tmplcontent['character_string2'] = $order['ordernum']?$order['ordernum']:'';
						$tmplcontent['phrase10'] = '新订单下单成功';
						$tmplcontent['amount13'] =  $order['totalprice']?$order['totalprice']:'';//金额
						$tmplcontent['thing27']  = '';
						//\app\common\Wechat::sendwxtmpl($aid,$member['id'],'tmpl_orderconfirm',$tmplcontent,m_url('pagesA/mendiancenter/orderlist'),$order['mdid']);
                        \app\common\Wechat::createSendTmplData($aid,0,$member['id'],'tmpl_orderconfirm',$tmplcontent,'pagesA/mendiancenter/orderlist',$order['mdid'],'','sendwxtmpl');
					}
					if($member['mpopenid']){
						//$rs = \app\common\Wechat::sendtmpl($aid,$member['id'],'tmpl_orderpay',$tmplcontent,m_url('pagesA/mendiancenter/orderlist', $aid),$tmplcontentNew);
                        \app\common\Wechat::createSendTmplData($aid,0,$member['id'],'tmpl_orderpay',$tmplcontent,'pagesA/mendiancenter/orderlist',0,$tmplcontentNew,'sendtmpl');
					}
				}
				
			}
		}

		if(getcustom('business_sales_quota')){
			if($order['bid']>0){
				$business = Db::name('business')->where(['aid'=>$aid,'id'=>$order['bid']])->field('kctime,kctype')->find();
				if($business['kctime']==0){
					$remark = '订单号：'.$order['ordernum'];
					$sales_price = $order['product_price'];
					if($business['kctype']==1){
						$sales_price = $order['totalprice'];
					}
					\app\common\Business::addsalesquota($aid,$order['bid'],$sales_price,$remark,$order['id']);
				}
			}
		}
        if(getcustom('zhongkang_sync')){
            $zksysset = Db::name('admin_set')->where('aid',$aid)->find();
            foreach($oglist  as $key=>$val){
                $shop_product =  Db::name('shop_product')->where('aid',$aid)->where('id',$val['proid'])->field('zhongkang_appid,zhongkang_levelid')->find();
                 if($shop_product['zhongkang_appid'] && $shop_product['zhongkang_levelid'] && $zksysset['zhongkang_secret'] && $member['tel']){
                     \app\custom\Zhongkang::createMember($shop_product['zhongkang_appid'],$member['tel'],$shop_product['zhongkang_levelid'],$zksysset['zhongkang_secret']);
                 }
            }
        }

        
        if(getcustom('yx_mangfan')) {
			//支付成功计算盲返列表
            $field = "id ogid, aid, mid, num, real_totalprice, totalprice, is_mangfan, mangfan_rate, mangfan_commission_type";
            if(getcustom('yx_buyer_subsidy',$aid)){
                $field .= ",order_fee";
            }
            $order_goods = Db::name("shop_order_goods")
                ->where("orderid", $orderid)
                ->where("is_mangfan", 1)
                ->field($field)
                ->select();
            if($order_goods){
                \app\custom\Mangfan::createRecord($aid, $order['mid'], $order['id'], $order['ordernum'], $order['paytime'], $order_goods);
            }
        }

        if(getcustom('product_pickup_device')){
             \app\common\Order::pickupDeviceGoodsPayafter($order);
        }

        //是否延后处理 默认不延后处理
        //（用于一些事件延后处理，如:订单未正式生效：不能同城配送、不能发货、不能推送到第三方等）
        $isdelayed = false;//默认不延后处理
        if(getcustom('shop_giveorder')){
            if($order['usegiveorder']) $isdelayed = true;
        }
        if(!$isdelayed){
            //处理订单延后事件
            self::dealshoppayDelayed($aid,$orderid,$order,$oglist,$member);
        }
        if(getcustom('sms_temp_orderpay_adminuser')){                                     
            $admin_tel = Db::name('admin_user')->where('aid',$aid)->where('bid',$order['bid'])->where('tel','NOT NULL',null)->column('tel');
            if($admin_tel){
                 foreach($admin_tel as $key=>$tel){
                     $rs = \app\common\Sms::send($aid,$tel,'tmpl_orderplay_adminuser',['time'=>date('Y-m-d H:i')]);
                 }
            }
        }
        if(getcustom('yx_liandong',$aid)){
            \app\custom\Liandong::join_active($aid,$order['mid'],$orderid);
            if($set['fxjiesuantime'] == 1 && $set['fxjiesuantime_delaydays'] == '0'){
                \app\common\Order::giveCommission($order,'shop');
                //\app\custom\Liandong::send_bonus($aid,$order['id']);
            }
        }
        if(getcustom('yx_network_help',$aid)){
            \app\custom\NetworkHelp::join_active($aid,$order['mid'],$orderid);
            if($set['fxjiesuantime'] == 1 && $set['fxjiesuantime_delaydays'] == '0'){
                \app\common\Order::giveCommission($order,'shop');
            }
        }
        if(getcustom('erp_nod',$aid)){
            $res = \app\custom\ErpNod::erp_order($aid,$orderid,'shop');
        }
        if(getcustom('yx_buyer_subsidy',$aid)){
            $res = \app\custom\Subsidy::caclOrder($aid,$orderid,0);
        }
        if(getcustom('yx_new_score',$aid)){
            $res = \app\custom\NewScore::caclOrder($aid,$orderid,0);
        }
        $money_dec = getcustom('money_dec',$aid);
        if(getcustom('fenhong_max',$aid) && getcustom('fenhong_max_buymoney',$aid)){
            //支付后增加股东分红上限额度
            $user = Db::name('member')->where('aid',$aid)->where('id',$order['mid'])->field('id,fenhong_max')->find();
            $sysset_custom = Db::name('admin_set_custom')->where('aid',$aid)->field('fenhong_max_buymoney')->find();
            if($user && $sysset_custom['fenhong_max_buymoney'] == 1){
                $fenhong_max = bcadd($user['fenhong_max'],$order['totalprice'],2);
                if($money_dec && $order['dec_money'] > 0){
                    $fenhong_max = bcadd($fenhong_max,$order['dec_money'],2);
                }
                Db::name('member')->where('aid',$aid)->where('id',$user['id'])->update(['fenhong_max' => $fenhong_max]);
                Db::name('shop_order')->where('aid',$aid)->where('id',$order['id'])->update(['fenhong_max_add_buymoney' => 1]);
            }
        }
        if(getcustom('yx_digital_consum',$aid)){
            $res = \app\custom\yingxiao\DigitalConsum::caclOrder($aid,$orderid,0,'shop');
        }
        if(getcustom('yx_farm')){
            if($order['give_farmseed2'] > 0){
                $remark = '订单ID：'.$order['id'].'赠送';
                \app\custom\yingxiao\FarmCustom::addFert($aid,$order['mid'],'seed',$order['give_farmseed2'],$remark);
            }
        }
	}

    //商城订单支付延后事件
    public static function dealshoppayDelayed($aid,$orderid,$order,$oglist,$member=''){
        //同城配送
        if($order['freight_type'] == 2){
            if(getcustom('express_maiyatian_autopush')) {
                //麦芽田同城配送自动推送
                \app\custom\MaiYaTianCustom::auto_push($aid,$orderid,$order,'shop_order');
            }
        }
        //自动发货
        elseif($order['freight_type']==3){
            $og = Db::name('shop_order_goods')->where('orderid',$order['id'])->find();
            $freight_content = Db::name('shop_product')->where('id',$og['proid'])->value('freightcontent');
            Db::name('shop_order')->where('id',$order['id'])->update(['freight_content'=>$freight_content,'status'=>2,'send_time'=>time()]);
            Db::name('shop_order_goods')->where('orderid',$order['id'])->update(['status'=>2]);

            if($order['fromwxvideo'] == 1){
                \app\common\Wxvideo::deliverysend($orderid);
            }
            //发货信息录入 微信小程序+微信支付
            if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                \app\common\Order::wxShipping($order['aid'],$order);
            }
            if(getcustom('plug_zhiming')){
                \app\common\Order::collect($order);
                Db::name('shop_order')->where('id',$orderid)->update(['status'=>3,'collect_time'=>time()]);
                Db::name('shop_order_goods')->where('orderid',$orderid)->update(['status'=>3,'endtime'=>time()]);
            }
        }
        if(getcustom('huawa') && $aid==1){ //定制1 订单对接 同步到花娃
            \app\custom\Huawa::api($order);
        }
        if(getcustom('cefang') && $aid==2){ //定制1 订单对接 同步到策方
            \app\custom\Cefang::api($order);
        }
        if(getcustom('hmy_yuyue')){  // 红蚂蚁定制同步到跑腿订单
            \app\custom\Yuyue::api($order);
        }

        $jushuitanadd = true;//创建聚水潭订单
        if(getcustom('supply_zhenxin')){
            //甄新汇选下单
            if($order['issource'] && $order['source'] == 'supply_zhenxin'){
                $jushuitanadd = false;
            }
        }
        if(getcustom('supply_yongsheng')){
            //永盛下单
            if($order['issource'] && $order['source'] == 'supply_yongsheng'){
                $jushuitanadd = false;
            }
        }
        if(getcustom('jushuitan')){
            if($jushuitanadd){
                //创建聚水潭订单
                $rs = \app\custom\Jushuitan::createOrder($order,'WAIT_SELLER_SEND_GOODS');
                if($rs['code']==0){  //此o_id 用于取消订单使用
                    $o_id = $rs['data']['datas'][0]['o_id'];
                    \think\facade\Log::write($o_id);
                    Db::name('shop_order')->where('id', $orderid)->update(['jsto_id' => $o_id]);
                }
            }
        }

        if($order['platform'] == 'toutiao'){
            \app\common\Ttpay::pushorder($aid,$order['ordernum'],1);
        }

        if(getcustom('product_supply_chain')){
            if($order['product_type']==7 && $order['supplier_status']==0){
                //同步到供货平台：海带网
                \app\custom\Chain::orderPay($aid,$order['id']);
            }
        }

        //自动派单到大厅
        $canpaidan = true;
        if(getcustom('supply_zhenxin')){
            //甄新汇选下单
            if($order['issource'] && $order['source'] == 'supply_zhenxin'){
                $canpaidan = false;
            }
        }
        if(getcustom('supply_yongsheng')){
            //永盛下单
            if($order['issource'] && $order['source'] == 'supply_yongsheng'){
                $canpaidan = false;
            }
        }
        if(getcustom('express_paidan')){
            if($order['freight_type'] == 2 && $canpaidan){
                $set = Db::name('peisong_set')->where('aid',$aid)->find();
                if($set['paidantype'] == 0){
                    if($set['express_paidan'] == 1){
                        $rs = \app\model\PeisongOrder::create('shop_order',$order,0,[]);
                    }
                }
            }
        }

        if(getcustom('supply_zhenxin')){
            //甄新汇选下单
            if($order['issource'] && $order['source'] == 'supply_zhenxin'){
                \app\custom\SupplyZhenxinCustom::dealpayafter($aid,$order,$oglist);
            }
        }
        if(getcustom('supply_yongsheng')){
            //永盛下单
            if($order['issource'] && $order['source'] == 'supply_yongsheng'){
                \app\custom\SupplyYongsheng::createOrder($aid,$order,$oglist);
            }
        }

        if(getcustom('yx_cashback_sendtype')){
            if($oglist && $member){
                //购物返现
                \app\common\Order::dealcashback($aid,$order,$oglist,$member,'pay');
            }
        }
        return ['status'=>1];
    }

	public static function scoreshop_hb_pay($orderid,$ordernum){
		$orderlist = Db::name('scoreshop_order')->where('ordernum','like',$ordernum.'%')->select()->toArray();
		foreach($orderlist as $order){
			self::scoreshop_pay($order['id']);
		}
	}
	//积分商城订单
	public static function scoreshop_pay($orderid){
		$order = Db::name('scoreshop_order')->where('id',$orderid)->find();
		$member = Db::name('member')->where('id',$order['mid'])->find();
		$aid = $order['aid'];
		$mid = $order['mid'];

        Db::name('scoreshop_order_goods')->where('orderid',$order['id'])->update(['status'=>1]);
        //同城配送
        if($order['freight_type'] == 2){
            if(getcustom('express_maiyatian_autopush')) {
                //麦芽田同城配送自动推送
                \app\custom\MaiYaTianCustom::auto_push($aid,$orderid,$order,'scoreshop_order');
            }
        }
		//自动发货
		if($order['freight_type']==3){
			$og = Db::name('scoreshop_order_goods')->where('orderid',$order['id'])->find();
			$freight_content = Db::name('scoreshop_product')->where('id',$og['proid'])->value('freightcontent');
			Db::name('scoreshop_order')->where('id',$order['id'])->update(['freight_content'=>$freight_content,'status'=>2,'send_time'=>time()]);
			Db::name('scoreshop_order_goods')->where('orderid',$order['id'])->update(['status'=>2]);
			if(getcustom('scoreshop_wx_hongbao')){
			    //如果是兑换红包
                if($og['type'] ==1){
                    $hb_scoreshop_product = Db::name('scoreshop_product')->where('id',$og['proid'])->field('hongbao_money,scene_id')->find();

                    if($hb_scoreshop_product['hongbao_money'] > 0){
                        $money =  dd_money_format($hb_scoreshop_product['hongbao_money'],2);
                        $rs = \app\common\Wxpay::sendredpackage($order['aid'],$order['mid'],$order['platform'],$money,mb_substr($order['title'],0,10),'微信红包','恭喜发财','微信红包',$hb_scoreshop_product['scene_id']);
                        if($rs['status']==0){ //发放失败
                            Db::name('scoreshop_order')->where('id',$order['id'])->update(['send_remark'=>$rs['msg']]);
                        }else{
                            //修改订单状态
                            Db::name('scoreshop_order')->where('id',$order['id'])->update(['status'=>3,'send_time'=>time(),'send_remark'=>'红包发放成功']);
                            Db::name('scoreshop_order_goods')->where('orderid',$order['id'])->update(['status'=>3]);
                        }
                    }
                }
            }
            if(getcustom('scoreshop_to_money')){
                if($order['type'] ==2 && $order['bid'] == 0){
                    $give_money = Db::name('scoreshop_product')->where('id',$og['proid'])->value('give_money');
                     if($give_money > 0){
                         $give_money =  dd_money_format($give_money,2);
                         //增加余额
                         $rs = \app\common\Member::addmoney($order['aid'],$mid,$give_money,'积分兑换,订单号:'.$order['ordernum']);
                         if($rs['status'] ==1){
                             //修改订单状态
                             Db::name('scoreshop_order')->where('id',$order['id'])->update(['status'=>3,'send_time'=>time(),'send_remark'=>'余额发放成功']);
                             Db::name('scoreshop_order_goods')->where('orderid',$order['id'])->update(['status'=>3]);
                         }else{
                             Db::name('scoreshop_order')->where('id',$order['id'])->update(['send_remark'=>'余额发放失败']);
                         }

                     }
                }
            }
            //发货信息录入 微信小程序+微信支付
            if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                \app\common\Order::wxShipping($order['aid'],$order,'scoreshop');
            }
		}
		//在线卡密
		if($order['freight_type']==4){
			$og = Db::name('scoreshop_order_goods')->where('orderid',$order['id'])->find();
			$codelist = Db::name('scoreshop_codelist')->where('proid',$og['proid'])->where('status',0)->order('id')->limit($og['num'])->select()->toArray();
			if($codelist && count($codelist) >= $og['num']){
				$pscontent = [];
				foreach($codelist as $codeinfo){
					$pscontent[] = $codeinfo['content'];
					Db::name('scoreshop_codelist')->where('id',$codeinfo['id'])->update(['orderid'=>$order['id'],'ordernum'=>$order['ordernum'],'headimg'=>$member['headimg'],'nickname'=>$member['nickname'],'buytime'=>time(),'status'=>1]);
				}
				$pscontent = implode("\r\n",$pscontent);
				Db::name('scoreshop_order')->where('id',$order['id'])->update(['freight_content'=>$pscontent,'status'=>2,'send_time'=>time()]);
				Db::name('scoreshop_order_goods')->where('orderid',$order['id'])->update(['status'=>2]);
			}

            //发货信息录入 微信小程序+微信支付
            if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                \app\common\Order::wxShipping($order['aid'],$order,'scoreshop');
            }
		}
        if(getcustom('consumer_value_add') && getcustom('consumer_value_add_scoreshop')){
            //送绿色积分
            if($order['give_green_score2'] > 0){
                \app\common\Member::addgreenscore($aid,$order['mid'],$order['give_green_score2'],'购买商品赠送'.t('绿色积分'),'shop_order',$orderid,0,$order['give_maximum'],0);
            }
            //放入奖金池
            if($order['give_bonus_pool2'] > 0){
                \app\common\Member::addbonuspool($aid,$order['mid'],$order['give_bonus_pool2'],'购买商品赠送'.t('奖金池'),'shop_order',$orderid,0,$order['give_green_score2']);
            }
            if(getcustom('green_score_reserves')){
                //订单进入预备金
                if($order['give_green_score_reserves2']>0){
                    \app\custom\GreenScore::addgreenscorereserves($aid,$order['mid'],$order['give_green_score_reserves2'],'购买商品赠送'.t('预备金'),'shop_order',$orderid);
                }
            }
        }

        if(getcustom('supply_yongsheng')){
            //永盛下单
            if($order['issource'] && $order['source'] == 'supply_yongsheng'){
                $oglist = $oglist??[];
                if(!$oglist){
                    $oglist = Db::name('scoreshop_order_goods')->where('orderid',$order['id'])->select()->toArray();
                }   
                \app\custom\SupplyYongsheng::createOrder($aid,$order,$oglist,'scoreshop');
            }
        }
		//支付后送券
		$couponlist = \app\common\Coupon::getpaygive($aid,$mid,'scoreshop',$order['totalprice']);
		if($couponlist){
			foreach($couponlist as $coupon){
				\app\common\Coupon::send($aid,$mid,$coupon['id']);
			}
		}
		\app\common\Wifiprint::print($aid,'scoreshop',$order['id']);
        if(getcustom('yx_queue_free_scoreshop')){
            //加入排队免单
            \app\custom\QueueFree::join($order,'scoreshop');
        }
		//公众号通知 订单支付成功
		$tmplcontent = [];
		$tmplcontent['first'] = '有新'.t('积分').'商城订单支付成功';
		$tmplcontent['remark'] = '点击进入查看~';
		$tmplcontent['keyword1'] = $member['nickname']; //用户名
		$tmplcontent['keyword2'] = $order['ordernum'];//订单号
		$tmplcontent['keyword3'] = $order['totalscore'].t('积分').($order['totalprice']>0?' + '.$order['totalprice'].'元':'');//订单金额
		$tmplcontent['keyword4'] = $order['title'];//商品信息
        $tmplcontentNew = [];
        $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
        $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
        $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
        $tmplcontentNew['amount5'] = $order['totalscore'].t('积分').($order['totalprice']>0?' + '.$order['totalprice'].'元':'');//订单金额
        $tmplcontentNew['thing3'] = $order['title'];//商品信息
		//\app\common\Wechat::sendhttmpl($aid,0,'tmpl_orderpay',$tmplcontent,m_url('admin/order/scoreshoporder', $aid),$order['mdid'],$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,0,0,'tmpl_orderpay',$tmplcontent,'admin/order/scoreshoporder',$order['mdid'],$tmplcontentNew,'sendhttmpl');

		$tmplcontent['first'] = '恭喜您的订单已支付成功';
		//$rs = \app\common\Wechat::sendtmpl($aid,$mid,'tmpl_orderpay',$tmplcontent,m_url('activity/scoreshop/orderlist', $aid),$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,0,$mid,'tmpl_orderpay',$tmplcontent,'activity/scoreshop/orderlist',0,$tmplcontentNew,'sendtmpl');

		$tmplcontent = [];
		$tmplcontent['thing11'] = $order['title'];
		$tmplcontent['character_string2'] = $order['ordernum'];
		$tmplcontent['phrase10'] = '已支付';
		$tmplcontent['amount13'] = $order['totalprice'].'元';
		$tmplcontent['thing27'] = $member['nickname'];
		//\app\common\Wechat::sendhtwxtmpl($aid,0,'tmpl_orderpay',$tmplcontent,'admin/order/scoreshoporder',$order['mdid']);
        \app\common\Wechat::createSendTmplData($aid,0,0,'tmpl_orderpay',$tmplcontent,'admin/order/scoreshoporder',$order['mdid'],'','sendhtwxtmpl');
		//短信通知
		$rs = \app\common\Sms::send($aid,$member['tel']?$member['tel']:$order['tel'],'tmpl_orderpay',['ordernum'=>$order['ordernum']]);

		$set = Db::name('admin_set')->where('aid',$aid)->find();
		if($set['fxjiesuantime'] == 1 && $set['fxjiesuantime_delaydays'] == '0'){
			\app\common\Order::giveCommission($order,'scoreshop');
		}
        if(getcustom('score_product_membergive')){
            \app\common\Order::scoreProductMembergive($aid,$order,2);
        }
	}
    //金豆商城订单
    public static function gold_bean_shop_pay($orderid){
	    if(getcustom('gold_bean_shop')) {
            $order = Db::name('gold_bean_shop_order')->where('id', $orderid)->find();
            $member = Db::name('member')->where('id', $order['mid'])->find();
            $aid = $order['aid'];
            $mid = $order['mid'];

            Db::name('gold_bean_shop_order_goods')->where('orderid', $order['id'])->update(['status' => 1]);
           
            //自动发货
            if ($order['freight_type'] == 3) {
                $og = Db::name('gold_bean_shop_order_goods')->where('orderid', $order['id'])->find();
                $freight_content = Db::name('gold_bean_shop_product')->where('id', $og['proid'])->value('freightcontent');
                Db::name('gold_bean_shop_order')->where('id', $order['id'])->update(['freight_content' => $freight_content, 'status' => 2, 'send_time' => time()]);
                Db::name('gold_bean_shop_order_goods')->where('orderid', $order['id'])->update(['status' => 2]);
                //发货信息录入 微信小程序+微信支付
                if ($order['platform'] == 'wx' && $order['paytypeid'] == 2) {
                    \app\common\Order::wxShipping($order['aid'], $order, 'gold_bean_shop');
                }
            }
            //在线卡密
            if ($order['freight_type'] == 4) {
                $og = Db::name('gold_bean_shop_order_goods')->where('orderid', $order['id'])->find();
                $codelist = Db::name('gold_bean_shop_codelist')->where('proid', $og['proid'])->where('status', 0)->order('id')->limit($og['num'])->select()->toArray();
                if ($codelist && count($codelist) >= $og['num']) {
                    $pscontent = [];
                    foreach ($codelist as $codeinfo) {
                        $pscontent[] = $codeinfo['content'];
                        Db::name('gold_bean_shop_codelist')->where('id', $codeinfo['id'])->update(['orderid' => $order['id'], 'ordernum' => $order['ordernum'], 'headimg' => $member['headimg'], 'nickname' => $member['nickname'], 'buytime' => time(), 'status' => 1]);
                    }
                    $pscontent = implode("\r\n", $pscontent);
                    Db::name('gold_bean_shop_order')->where('id', $order['id'])->update(['freight_content' => $pscontent, 'status' => 2, 'send_time' => time()]);
                    Db::name('gold_bean_shop_order_goods')->where('orderid', $order['id'])->update(['status' => 2]);
                }

                //发货信息录入 微信小程序+微信支付
                if ($order['platform'] == 'wx' && $order['paytypeid'] == 2) {
                    \app\common\Order::wxShipping($order['aid'], $order, 'gold_bean_shop');
                }
            }

            //支付后送券
            $couponlist = \app\common\Coupon::getpaygive($aid, $mid, 'gold_bean_shop', $order['totalprice']);
            if ($couponlist) {
                foreach ($couponlist as $coupon) {
                    \app\common\Coupon::send($aid, $mid, $coupon['id']);
                }
            }
            \app\common\Wifiprint::print($aid, 'gold_bean_shop', $order['id']);
            if(getcustom('yx_queue_free_gold_bean_shop')){
                //加入排队免单
                \app\custom\QueueFree::join($order,'gold_bean_shop');
            }
            //公众号通知 订单支付成功
            $tmplcontent = [];
            $tmplcontent['first'] = '有新' . t('金豆') . '商城订单支付成功';
            $tmplcontent['remark'] = '点击进入查看~';
            $tmplcontent['keyword1'] = $member['nickname']; //用户名
            $tmplcontent['keyword2'] = $order['ordernum'];//订单号
            $tmplcontent['keyword3'] = $order['totalscore'] . t('金豆') . ($order['totalprice'] > 0 ? ' + ' . $order['totalprice'] . '元' : '');//订单金额
            $tmplcontent['keyword4'] = $order['title'];//商品信息
            $tmplcontentNew = [];
            $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
            $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
            $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
            $tmplcontentNew['amount5'] = $order['totalscore'] . t('金豆') . ($order['totalprice'] > 0 ? ' + ' . $order['totalprice'] . '元' : '');//订单金额
            $tmplcontentNew['thing3'] = $order['title'];//商品信息
            //\app\common\Wechat::sendhttmpl($aid,0,'tmpl_orderpay',$tmplcontent,m_url('admin/order/scoreshoporder', $aid),$order['mdid'],$tmplcontentNew);
            \app\common\Wechat::createSendTmplData($aid, 0, 0, 'tmpl_orderpay', $tmplcontent, 'adminExt/order/goldbeanshoporder', $order['mdid'], $tmplcontentNew, 'sendhttmpl');

            $tmplcontent['first'] = '恭喜您的订单已支付成功';
            //$rs = \app\common\Wechat::sendtmpl($aid,$mid,'tmpl_orderpay',$tmplcontent,m_url('activity/scoreshop/orderlist', $aid),$tmplcontentNew);
            \app\common\Wechat::createSendTmplData($aid, 0, $mid, 'tmpl_orderpay', $tmplcontent, 'pagesD/goldbeanshop/orderlist', 0, $tmplcontentNew, 'sendtmpl');

            $tmplcontent = [];
            $tmplcontent['thing11'] = $order['title'];
            $tmplcontent['character_string2'] = $order['ordernum'];
            $tmplcontent['phrase10'] = '已支付';
            $tmplcontent['amount13'] = $order['totalprice'] . '元';
            $tmplcontent['thing27'] = $member['nickname'];
            //\app\common\Wechat::sendhtwxtmpl($aid,0,'tmpl_orderpay',$tmplcontent,'admin/order/scoreshoporder',$order['mdid']);
            \app\common\Wechat::createSendTmplData($aid, 0, 0, 'tmpl_orderpay', $tmplcontent, 'adminExt/order/goldbeanshoporder', $order['mdid'], '', 'sendhtwxtmpl');
            //短信通知
            $rs = \app\common\Sms::send($aid, $member['tel'] ? $member['tel'] : $order['tel'], 'tmpl_orderpay', ['ordernum' => $order['ordernum']]);

            $set = Db::name('admin_set')->where('aid', $aid)->find();
            if ($set['fxjiesuantime'] == 1 && $set['fxjiesuantime_delaydays'] == '0') {
                \app\common\Order::giveCommission($order, 'scoreshop');
            }
        }
    }
	//拼团订单
	public static function collage_pay($orderid){
		$order = Db::name('collage_order')->where('id',$orderid)->find();
		$member = Db::name('member')->where('id',$order['mid'])->find();
		$aid = $order['aid'];
		$mid = $order['mid'];

        if(getcustom('sound',$aid)){
            \app\common\Sound::play(aid,'collage',$order);
        }

		if($order['buytype']!=1){
			$team = Db::name('collage_order_team')->where('id',$order['teamid'])->find();
			$tdata = [];

            $addnum = true;//是否增加参团数量
            if(getcustom('yx_collage_teambuy_type')){
                //是开启团长拼团模式一 不占参团人数
                if($order['buytype'] == 2 && $order['teambuy_type'] == 1){
                    $addnum = false;
                }
            }
            if($addnum){
                $tdata['num'] = $team['num'] + 1;
            }else{
                $tdata['num'] = $team['num'];
            }
            $checknum = true;
            if(getcustom('yx_collage_jieti')){
                if($team['collage_type'] ==1){
                    $checknum = false;
                }
            }

            if(getcustom('yx_collage_team_in_team')){
                //参与拼团支付完成，查询是否标记此团上级团为自己所参与团
                if($order['buytype'] == 3){
                    $teampid = 0;
                    $has_teaminteam = \app\custom\CollageTeamInTeamCustom::has_teaminteam($order['aid']);//团中团权限
                    if($has_teaminteam){
                        //商品信息
                        $product = Db::name('collage_product')->where('id',$order['proid'])->field('id,teaminteam_splitnum,teaminteam_status')->find();
                        if($product && $product['teaminteam_status'] == 1 && $product['teaminteam_splitnum']>0){
                            //标记此订单的上级团为自己所参与团
                            $teampid = $order['teamid'];
                        }
                    }
                    //更新上级团ID
                    $up = Db::name('collage_order')->where('id',$order['id'])->update(['teampid'=>$teampid]);
                    if($up){
                        $order['teampid'] = $teampid;
                    }
                }
            }

			if($tdata['num'] >= $team['teamnum'] && $checknum){
                $tdata['status'] = 2;
                //统一处理拼团成功
				self::collageEnd($aid,$order,$team,$member);
			}else{
				$tdata['status'] = 1;
			}
			Db::name('collage_order_team')->where('aid',$aid)->where('id',$order['teamid'])->update($tdata);
		}else{
            if(getcustom('collage_givescore_time')){
                //付款后送积分
                if($order['givescore2'] > 0){
                    \app\common\Member::addscore($aid,$order['mid'],$order['givescore2'],'购买拼团商品赠送'.t('积分'));
                }
            }
            //同城配送
            if($order['freight_type'] == 2){
                if(getcustom('express_maiyatian_autopush')) {
                    //麦芽田同城配送自动推送
                    \app\custom\MaiYaTianCustom::auto_push($aid,$orderid,$order,'collage_order');
                }
            }
			//自动发货
			if($order['freight_type']==3){
				$freight_content = Db::name('collage_product')->where('id',$order['proid'])->value('freightcontent');
				Db::name('collage_order')->where('id',$order['id'])->update(['freight_content'=>$freight_content,'status'=>2,'send_time'=>time()]);
                //发货信息录入 微信小程序+微信支付
                if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                    \app\common\Order::wxShipping($order['aid'],$order,'collage');
                }
			}
			//在线卡密
			if($order['freight_type']==4){
				$codelist = Db::name('collage_codelist')->where('proid',$order['proid'])->where('status',0)->order('id')->limit($order['num'])->select()->toArray();
				if($codelist && count($codelist) >= $order['num']){
					$pscontent = [];
					foreach($codelist as $codeinfo){
						$pscontent[] = $codeinfo['content'];
						Db::name('collage_codelist')->where('id',$codeinfo['id'])->update(['orderid'=>$order['id'],'ordernum'=>$order['ordernum'],'headimg'=>$member['headimg'],'nickname'=>$member['nickname'],'buytime'=>time(),'status'=>1]);
					}
					$pscontent = implode("\r\n",$pscontent);
					Db::name('collage_order')->where('id',$order['id'])->update(['freight_content'=>$pscontent,'status'=>2,'send_time'=>time()]);
				}
                //发货信息录入 微信小程序+微信支付
                if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                    \app\common\Order::wxShipping($order['aid'],$order,'collage');
                }
			}
            if(getcustom('express_paidan')){
                if($order['freight_type'] == 2 ){
                    $peisongset = Db::name('peisong_set')->where('aid',$aid)->find();
                    if($peisongset['paidantype'] == 0){
                        if($peisongset['express_paidan'] == 1){
                            $rs = \app\model\PeisongOrder::create('collage_order',$order,0,[]);
                        }
                    }
                }
            }
            \app\common\Wifiprint::print($aid,'collage',$order['id']);//成功后打印
		}

        if(getcustom('yx_mangfan_collage')) {
            //支付成功计算盲返列表
            $order_good = $order;
            $order_good['ogid'] = $order['id'];
            $order_good['real_totalprice'] = $order['totalprice'];
            $order_good['type'] = 'collage';
            if($order_good){
                $order_goods = [$order_good];
                \app\custom\Mangfan::createRecord($aid, $order['mid'], $order['id'], $order['ordernum'], $order['paytime'], $order_goods,false,0,'collage');
            }
        }

        if(getcustom('yx_collage_team_in_team')){
            if($order['buytype'] == 1 || $order['buytype'] == 2){
                //处理团中团分裂新团
                \app\custom\CollageTeamInTeamCustom::deal_splitteam($order);
            }
        }

        if(getcustom('yx_queue_free_collage')){
            //多人拼团排队免单
            \app\custom\QueueFree::join($order,'collage');
        }

		//支付后送券
		$couponlist = \app\common\Coupon::getpaygive($aid,$mid,'collage',$order['totalprice']);
		if($couponlist){
			foreach($couponlist as $coupon){
				\app\common\Coupon::send($aid,$mid,$coupon['id']);
			}
		}

        if(getcustom('bonus_pool_gold')){
            //思明定制奖金池
            \app\custom\BonusPoolGold::orderBonusPool($order['aid'],$order['mid'],$order['id'],'collage');
        }

		//公众号通知 订单支付成功
		$tmplcontent = [];
		$tmplcontent['first'] = '有新拼团订单支付成功';
		$tmplcontent['remark'] = '点击进入查看~';
		$tmplcontent['keyword1'] = $member['nickname']; //用户名
		$tmplcontent['keyword2'] = $order['ordernum'];//订单号
		$tmplcontent['keyword3'] = $order['totalprice'].'元';//订单金额
		$tmplcontent['keyword4'] = $order['title'];//商品信息
        $tmplcontentNew = [];
        $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
        $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
        $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
        $tmplcontentNew['amount5'] = $order['totalprice']==0?'0.00':$order['totalprice'];//订单金额
        $tmplcontentNew['thing3'] = $order['title'];//商品信息
		//\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('admin/order/collageorder', $aid),$order['mdid'],$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/collageorder',$order['mdid'],$tmplcontentNew,'sendhttmpl');

		$tmplcontent['first'] = '恭喜您的订单已支付成功';
		//$rs = \app\common\Wechat::sendtmpl($aid,$mid,'tmpl_orderpay',$tmplcontent,m_url('activity/collage/orderlist', $aid),$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,0,$mid,'tmpl_orderpay',$tmplcontent,'activity/collage/orderlist',0,$tmplcontentNew,'sendtmpl');

		$tmplcontent = [];
		$tmplcontent['thing11'] = $order['title'];
		$tmplcontent['character_string2'] = $order['ordernum'];
		$tmplcontent['phrase10'] = '已支付';
		$tmplcontent['amount13'] = $order['totalprice'].'元';
		$tmplcontent['thing27'] = $member['nickname'];
		//\app\common\Wechat::sendhtwxtmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,'admin/order/collageorder',$order['mdid']);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/collageorder',$order['mdid'],'','sendhtwxtmpl');

		//短信通知
		if($order['buytype']==1){ //直接购买
			$rs = \app\common\Sms::send($aid,$member['tel'] ? $member['tel'] : $order['tel'],'tmpl_orderpay',['ordernum'=>$order['ordernum']]);
		}
	}

    public static function collageEnd($aid,$order,$team,$member=[]){
        //团长奖励积分
        $orderlist = Db::name('collage_order')->where(['teamid'=>$team['id'],'status'=>1])->select()->toArray();
        $leader = Db::name('member')->where('id',$team['mid'])->find();
        foreach($orderlist as $v){
            //是否是机器人 0：不是 1：是
            if(!$v['isjiqiren']){
                if($v['givescore'] > 0){
                    \app\common\Member::addscore($aid,$v['mid'],$v['givescore'],'购买拼团产品奖励'.t('积分'));
                }
                if(getcustom('collage_givescore_time')){
                    //付款后送积分
                    if($v['givescore2'] > 0){
                        \app\common\Member::addscore($aid,$v['mid'],$v['givescore2'],'购买拼团商品赠送'.t('积分'));
                    }
                }
            }

            //是否直接完成
            $endorder = false;
            if(getcustom('yx_collage_teambuy_type')){
                //是开启团长拼团模式一 不发货直接完成
                if($v['buytype'] == 2 && $v['teambuy_type'] == 1){
                    $endorder = true;
                }
            }
            if(getcustom('yx_collage_team_in_team')){
                //处理拼团成功，团中团的队长订单直接完成
                if($order['buytype'] == 2 && $order['teampid']){
                    $endorder = true;
                }
            }
            if(!$endorder){
                //是否是机器人 0：不是 1：是
                if(!$v['isjiqiren']){
                    //同城配送
                    if($order['freight_type'] == 2){
                        if(getcustom('express_maiyatian_autopush')) {
                            //麦芽田同城配送自动推送
                            \app\custom\MaiYaTianCustom::auto_push($aid,$v['id'],$v,'collage_order');
                        }
                    //自动发货
                    }else if($v['freight_type']==3){
                        $freight_content = Db::name('collage_product')->where('id',$v['proid'])->value('freightcontent');
                        Db::name('collage_order')->where('id',$v['id'])->update(['freight_content'=>$freight_content,'status'=>2,'send_time'=>time()]);

                        //发货信息录入 微信小程序+微信支付
                        if($v['platform'] == 'wx' && $v['paytypeid'] == 2){
                            \app\common\Order::wxShipping($v['aid'],$v,'collage');
                        }
                    }

                    if(getcustom('express_paidan')){
                        if($order['freight_type'] == 2 ){
                            $peisongset = Db::name('peisong_set')->where('aid',$aid)->find();
                            if($peisongset['paidantype'] == 0){
                                if($peisongset['express_paidan'] == 1){
                                    $rs = \app\model\PeisongOrder::create('collage_order',$v,0,[]);
                                }
                            }
                        }
                    }
                    //在线卡密
                    if($v['freight_type']==4){
                        $codelist = Db::name('collage_codelist')->where('proid',$v['proid'])->where('status',0)->order('id')->limit($v['num'])->select()->toArray();
                        if($codelist && count($codelist) >= $v['num']){
                            $pscontent = [];
                                foreach($codelist as $codeinfo){
                                    $pscontent[] = $codeinfo['content'];
                                    Db::name('collage_codelist')->where('id',$codeinfo['id'])->update(['orderid'=>$v['id'],'ordernum'=>$v['ordernum'],'headimg'=>$member['headimg'],'nickname'=>$member['nickname'],'buytime'=>time(),'status'=>1]);
                                }
                                $pscontent = implode("\r\n",$pscontent);
                            Db::name('collage_order')->where('id',$v['id'])->update(['freight_content'=>$pscontent,'status'=>2,'send_time'=>time()]);
                        }
                        //发货信息录入 微信小程序+微信支付
                        if($v['platform'] == 'wx' && $v['paytypeid'] == 2){
                            \app\common\Order::wxShipping($v['aid'],$v,'collage');
                        }
                    }
                    \app\common\Wifiprint::print($aid,'collage',$v['id']);//拼团成功后打印
                }else{
                    //机器人完成订单
                    Db::name('collage_order')->where('id',$v['id'])->update(['status'=>3,'collect_time'=>time()]);
                }
            }else{
                //完成订单
                Db::name('collage_order')->where('id',$v['id'])->update(['status'=>3,'collect_time'=>time()]);
            }

            //是否是机器人 0：不是 1：是
            if(!$v['isjiqiren']){
                if(getcustom('yx_collage_team_in_team')){
                    if($v['buytype'] == 3){
                        //处理团中团分裂新团
                        \app\custom\CollageTeamInTeamCustom::deal_splitteam($v);
                    }
                }
                //公众号通知 拼团成功通知
                $tmplcontent = [];
                $tmplcontent['first'] = '有新拼团订单拼团成功';
                $tmplcontent['remark'] = '点击进入查看~';
                $tmplcontent['keyword1'] = $v['title']; //商品名称
                $tmplcontent['keyword2'] = $leader['nickname'];//团长
                $tmplcontent['keyword3'] = $team['teamnum'];//成团人数
                //\app\common\Wechat::sendhttmpl(aid,$v['bid'],'tmpl_collagesuccess',$tmplcontent,m_url('admin/order/collageorder'));
                $tmplcontent['first'] = '恭喜您拼团成功';
                //$rs = \app\common\Wechat::sendtmpl($aid,$v['mid'],'tmpl_collagesuccess',$tmplcontent,m_url('activity/collage/orderlist', $aid));
                \app\common\Wechat::createSendTmplData($aid,0,$v['mid'],'tmpl_collagesuccess',$tmplcontent,'activity/collage/orderlist',0,'','sendtmpl');
                //订阅消息
                $tmplcontent = [];
                $tmplcontent['thing1'] = $v['title'];
                $tmplcontent['thing10'] = $leader['nickname'];
                $tmplcontent['number12'] = $team['teamnum'];

                $tmplcontentnew = [];
                $tmplcontentnew['thing7'] = $v['title'];
                $tmplcontentnew['thing12'] = $leader['nickname'];
                $tmplcontentnew['number2'] = $team['teamnum'];
                //\app\common\Wechat::sendwxtmpl($aid,$v['mid'],'tmpl_collagesuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
                \app\common\Wechat::createSendTmplData($aid,0,$v['mid'],'tmpl_collagesuccess',$tmplcontentnew,'pages/my/usercenter',0,$tmplcontent,'sendwxtmpl');

                $mtel = Db::name('member')->where('id',$v['mid'])->value('tel');
                $tel = $mtel?$mtel:$v['tel'];
                //短信通知
                $rs = \app\common\Sms::send($aid,$tel,'tmpl_collagesuccess',['ordernum'=>$v['ordernum']]);
            }
        }
        if(getcustom('yx_collage_team_in_team')){
            //处理拼团成功，分发团中团团长分销奖励
            if($order['buytype'] == 3 && $order['teampid']){
                \app\custom\CollageTeamInTeamCustom::deal_reward($order,$team);
            }
        }
    }
    //周期购
    public static function cycle_pay($orderid){
        $order = Db::name('cycle_order')->where('id',$orderid)->find();

        $member = Db::name('member')->where('id',$order['mid'])->find();
        $aid = $order['aid'];
        $mid = $order['mid'];
        Db::name('cycle_order_stage')->where('aid',$aid)->where('orderid',$order['id'])->update(['status' =>1]);
        //同城配送
        if($order['freight_type'] == 2){
            if(getcustom('express_maiyatian_autopush')) {
                //麦芽田同城配送自动推送
                \app\custom\MaiYaTianCustom::auto_push($aid,$orderid,$order,'cycle_order_stage');
            }
        }
        //支付后送券
        $couponlist = \app\common\Coupon::getpaygive($aid,$mid,'cycle',$order['totalprice']);
        if($couponlist){
            foreach($couponlist as $coupon){
                \app\common\Coupon::send($aid,$mid,$coupon['id']);
            }
        }
        \app\common\Wifiprint::print($aid,'cycle',$order['id']);

        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping($order['aid'],$order,'cycle');
        }

        //公众号通知 订单支付成功
        $tmplcontent = [];
        $tmplcontent['first'] = '有新周期购订单支付成功';
        $tmplcontent['remark'] = '点击进入查看~';
        $tmplcontent['keyword1'] = $member['nickname']; //用户名
        $tmplcontent['keyword2'] = $order['ordernum'];//订单号
        $tmplcontent['keyword3'] = $order['totalprice'].'元';//订单金额
        $tmplcontent['keyword4'] = $order['title'];//商品信息
        $tmplcontentNew = [];
        $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
        $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
        $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
        $tmplcontentNew['amount5'] = $order['totalprice']==0?'0.00':$order['totalprice'];//订单金额
        $tmplcontentNew['thing3'] = $order['title'];//商品信息
        //\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('admin/order/cycleorder', $aid),$order['mdid'],$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/cycleorder',$order['mdid'],$tmplcontentNew,'sendhttmpl');

        $tmplcontent['first'] = '恭喜您的订单已支付成功';
        //$rs = \app\common\Wechat::sendtmpl($aid,$mid,'tmpl_orderpay',$tmplcontent,m_url('pagesExt/cycle/orderList', $aid),$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,0,$mid,'tmpl_orderpay',$tmplcontent,'pagesExt/cycle/orderList',0,$tmplcontentNew,'sendtmpl');

        $tmplcontent = [];
        $tmplcontent['thing11'] = $order['title'];
        $tmplcontent['character_string2'] = $order['ordernum'];
        $tmplcontent['phrase10'] = '已支付';
        $tmplcontent['amount13'] = $order['totalprice'].'元';
        $tmplcontent['thing27'] = $member['nickname'];
        //\app\common\Wechat::sendhtwxtmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,'admin/order/cycleorder',$order['mdid']);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/cycleorder',$order['mdid'],'','sendhtwxtmpl');

        $rs = \app\common\Sms::send($aid,$member['tel'] ? $member['tel'] : $order['tel'],'tmpl_orderpay',['ordernum'=>$order['ordernum']]);

    }
	//幸运拼团订单
	public static function lucky_collage_pay($orderid){

		$order = Db::name('lucky_collage_order')->where('id',$orderid)->find();
		$member = Db::name('member')->where('id',$order['mid'])->find();
		$aid = $order['aid'];
		$mid = $order['mid'];
		if($order['buytype']!=1){
			//支付成功后奖励分享者余额，积分，佣金，优惠券
			if(getcustom('plug_luckycollage')){
				if($order['shareid']>0){
					//取出分享
					$pmember = Db::name('member')->where('id',$order['shareid'])->find();
					if($pmember && $order['sharemoney'] > 0){  //1 奖励余额
						\app\common\Member::addmoney($aid,$order['shareid'],$order['sharemoney'],'推荐奖励'.t('余额'));
					}else if($order['sharescore'] > 0){  //2 奖励积分
						\app\common\Member::addscore($aid,$order['shareid'],$order['sharescore'],'推荐奖励'.t('积分'));
					}else if($order['sharecommission'] > 0){  //3 奖励佣金
						\app\common\Member::addcommission($aid,$order['shareid'],$order['mid'],$order['sharecommission'],'推荐奖励'.t('佣金'));
					}elseif($order['share_yhqids']){ //奖励优惠券
						foreach($order['share_yhqids'] as $yhqid){
							\app\common\Coupon::send($aid,$order['shareid'],$yhqid);
						}
					}
				}
				if($order['buytype']==2){
					//减掉开团次数
					if($order['isjiqiren']!=1 && $member['ktnum']>0){
						$ktnum = $member['ktnum']-1;
						 Db::name('member')->where(['aid'=>$aid,'id'=>$order['mid']])->update(['ktnum'=>$ktnum]);
					}
				}
			}
			Db::startTrans();	
			$team = Db::name('lucky_collage_order_team')->where('id',$order['teamid'])->lock(true)->find();
			$tdata = [];
			$tdata['num'] = $team['num'] + 1;
			$iscanjia = 1;
			if(getcustom('member_tag')){
				$product = Db::name('lucky_collage_product')->where('aid',$aid)->where('status',1)->where('ischecked',1)->where('id',$order['proid'])->lock(true)->find();
				if($product['istag']==1){
					$rs = \app\model\LuckyCollage::membertag_collage($order['mid'],$order['teamid'],$product);
					if($rs && $rs['status']==0){
						$iscanjia = 0;
						\app\common\Order::refund($order,$order['totalprice'],'不符合参加条件订单退款');
						Db::name('lucky_collage_order')->where('id',$order['id'])->update(['teamid'=>0,'status'=>4,'refund_status'=>2,'refund_time'=>time(),'refund_money'=>$order['totalprice'],'refund_reason'=>'不符合参加条件订单退款','iszj'=>0]);
					}
				}
			}
			if($iscanjia==1){
				if($tdata['num']  == $team['teamnum']){
					$tdata['status'] = 2;
					Db::name('lucky_collage_order_team')->where('aid',$aid)->where('id',$order['teamid'])->update($tdata);
					\app\model\LuckyCollage::kaijiang($order);
				}elseif($tdata['num'] >$team['teamnum']){
					//已拼团成功退款
					\app\common\Order::refund($order,$order['totalprice'],'拼团参加失败订单退款');
					Db::name('lucky_collage_order')->where('id',$order['id'])->update(['teamid'=>0,'status'=>4,'refund_status'=>2,'refund_time'=>time(),'refund_money'=>$order['totalprice'],'refund_reason'=>'拼团参加失败订单退款','iszj'=>0]);
				}else{
					$tdata['status'] = 1;
					Db::name('lucky_collage_order_team')->where('aid',$aid)->where('id',$order['teamid'])->update($tdata);
				}
				//关闭其他参与未支付得订单
				Db::name('lucky_collage_order')->where('aid',$aid)->where('teamid',$order['teamid'])->where('mid',$order['mid'])->where('id','<>',$order['id'])->where('isjiqiren',0)->update(['status'=>4,'teamid'=>0]);
			}
			Db::commit();
		}else{
            //同城配送
            if($order['freight_type'] == 2){
                if(getcustom('express_maiyatian_autopush')) {
                    //麦芽田同城配送自动推送
                    \app\custom\MaiYaTianCustom::auto_push($aid,$orderid,$order,'lucky_collage_order');
                }
            }
        }

		//支付后送券
		$couponlist = \app\common\Coupon::getpaygive($aid,$mid,'lucky_collage',$order['totalprice']);
		if($couponlist){
			foreach($couponlist as $coupon){
				\app\common\Coupon::send($aid,$mid,$coupon['id']);
			}
		}

		//公众号通知 订单支付成功
		$tmplcontent = [];
		$tmplcontent['first'] = '有新拼团订单支付成功';
		$tmplcontent['remark'] = '点击进入查看~';
		$tmplcontent['keyword1'] = $member['nickname']; //用户名
		$tmplcontent['keyword2'] = $order['ordernum'];//订单号
		$tmplcontent['keyword3'] = $order['totalprice'].'元';//订单金额
		$tmplcontent['keyword4'] = $order['title'];//商品信息
        $tmplcontentNew = [];
        $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
        $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
        $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
        $tmplcontentNew['amount5'] = $order['totalprice'];//订单金额
        $tmplcontentNew['thing3'] = $order['title'];//商品信息
		//\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('admin/order/luckycollageorder', $aid),$order['mdid'],$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/luckycollageorder',$order['mdid'],$tmplcontentNew,'sendhttmpl');

		$tmplcontent['first'] = '恭喜您的订单已支付成功';
		//$rs = \app\common\Wechat::sendtmpl($aid,$order['mid'],'tmpl_orderpay',$tmplcontent,m_url('activity/luckycollage/orderlist', $aid),$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,0,$order['mid'],'tmpl_orderpay',$tmplcontent,'activity/luckycollage/orderlist',0,$tmplcontentNew,'sendtmpl');

		$tmplcontent = [];
		$tmplcontent['thing11'] = $order['title'];
		$tmplcontent['character_string2'] = $order['ordernum'];
		$tmplcontent['phrase10'] = '已支付';
		$tmplcontent['amount13'] = $order['totalprice'].'元';
		$tmplcontent['thing27'] = $member['nickname'];
		//\app\common\Wechat::sendhtwxtmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,'admin/order/luckycollageorder',$order['mdid']);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/luckycollageorder',$order['mdid'],'','sendhtwxtmpl');

		\app\common\Wifiprint::print($aid,'collage',$order['id']);
		//短信通知
		if($order['buytype']==1){ //直接购买
			$rs = \app\common\Sms::send($aid,$member['tel'] ? $member['tel'] : $order['tel'],'tmpl_orderpay',['ordernum'=>$order['ordernum']]);
		}
	}

	//砍价订单
	public static function kanjia_pay($orderid){
		$order = Db::name('kanjia_order')->where('id',$orderid)->find();
		$member = Db::name('member')->where('id',$order['mid'])->find();
		$product = Db::name('kanjia_product')->where('id',$order['proid'])->find();
		$aid = $order['aid'];
		$mid = $order['mid'];
        //同城配送
        if($order['freight_type'] == 2){
            if(getcustom('express_maiyatian_autopush')) {
                //麦芽田同城配送自动推送
                \app\custom\MaiYaTianCustom::auto_push($aid,$orderid,$order,'kanjia_order');
            }
        }
		//自动发货
		if($order['freight_type']==3){
			$freight_content = $product['freightcontent'];
			Db::name('kanjia_order')->where('id',$order['id'])->update(['freight_content'=>$freight_content,'status'=>2,'send_time'=>time()]);
            //发货信息录入 微信小程序+微信支付
            if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                \app\common\Order::wxShipping($order['aid'],$order,'kanjia');
            }
		}
		//在线卡密
		if($order['freight_type']==4){
			$codelist = Db::name('kanjia_codelist')->where('proid',$order['proid'])->where('status',0)->order('id')->limit($order['num'])->select()->toArray();
			if($codelist && count($codelist) >= $order['num']){
				$pscontent = [];
				foreach($codelist as $codeinfo){
					$pscontent[] = $codeinfo['content'];
					Db::name('kanjia_codelist')->where('id',$codeinfo['id'])->update(['orderid'=>$order['id'],'ordernum'=>$order['ordernum'],'headimg'=>$member['headimg'],'nickname'=>$member['nickname'],'buytime'=>time(),'status'=>1]);
				}
				$pscontent = implode("\r\n",$pscontent);
				Db::name('kanjia_order')->where('id',$order['id'])->update(['freight_content'=>$pscontent,'status'=>2,'send_time'=>time()]);
			}
            //发货信息录入 微信小程序+微信支付
            if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                \app\common\Order::wxShipping($order['aid'],$order,'kanjia');
            }
		}

		//买单后发放帮砍得积分或余额
		if($product['helpgive_ff'] == 1 && $product['helpgive_percent'] > 0 && $order['joinid']){
			$helplist = Db::name('kanjia_help')->where('aid',$aid)->where('joinid',$order['joinid'])->where('mid','<>',$mid)->select()->toArray();
			foreach($helplist as $help){
				if($product['helpgive_type'] == 0){ //积分
					$givescore = intval($help['cut_price'] * $product['helpgive_percent'] * 0.01);
					if($givescore > 0){
						\app\common\Member::addscore($aid,$help['mid'],$givescore,'帮好友砍价奖励');
					}
				}
				if($product['helpgive_type'] == 1){ //余额
					$givemoney = round($help['cut_price'] * $product['helpgive_percent'] * 0.01,2);
					if($givemoney > 0){
						\app\common\Member::addmoney($aid,$help['mid'],$givemoney,'帮好友砍价奖励');
					}
				}
			}
		}
		//下单增加帮砍次数
		if($product['perhelpnum_buyadd'] > 0){
			$sharelog = Db::name('kanjia_sharelog')->where('aid',$aid)->where('proid',$product['id'])->where('mid',$mid)->find();
			if($sharelog){
				Db::name('kanjia_sharelog')->where('id',$sharelog['id'])->inc('addtimes',$product['perhelpnum_buyadd'])->update();
			}else{
				$data = [];
				$data['aid'] = $aid;
				$data['proid'] = $product['id'];
				$data['mid'] = $mid;
				$data['addtimes'] = $product['perhelpnum_buyadd'];
				Db::name('kanjia_sharelog')->insert($data);
			}
		}
		//下单送抽奖/余额/积分/优惠券
		if($product['paygive_choujiangtimes'] > 0 && $product['paygive_choujiangid'] > 0){
			$sharelog = Db::name('choujiang_sharelog')->where('aid',$aid)->where('hid',$product['paygive_choujiangid'])->where('mid',$mid)->find();
			if($sharelog){
				Db::name('choujiang_sharelog')->where('id',$sharelog['id'])->inc('extratimes',$product['paygive_choujiangtimes'])->update();
			}else{
				$data = [];
				$data['aid'] = $aid;
				$data['hid'] = $product['paygive_choujiangid'];
				$data['mid'] = $mid;
				$data['extratimes'] = $product['paygive_choujiangtimes'];
				Db::name('choujiang_sharelog')->insert($data);
			}
		}
		if($product['paygive_money'] > 0){
			\app\common\Member::addmoney($aid,$mid,$product['paygive_money'],'砍价活动下单赠送');
		}
		if($product['paygive_score'] > 0){
			\app\common\Member::addscore($aid,$mid,$product['paygive_score'],'砍价活动下单赠送');
		}
		if($product['paygive_couponid'] > 0){
			\app\common\Coupon::send($aid,$mid,$product['paygive_couponid']);
		}

		//支付后送券
		$couponlist = \app\common\Coupon::getpaygive($aid,$mid,'kanjia',$order['totalprice']);
		if($couponlist){
			foreach($couponlist as $coupon){
				\app\common\Coupon::send($aid,$mid,$coupon['id']);
			}
		}
		\app\common\Wifiprint::print($aid,'kanjia',$order['id']);

		//公众号通知 订单支付成功
		$tmplcontent = [];
		$tmplcontent['first'] = '有新砍价订单支付成功';
		$tmplcontent['remark'] = '点击进入查看~';
		$tmplcontent['keyword1'] = $member['nickname']; //用户名
		$tmplcontent['keyword2'] = $order['ordernum'];//订单号
		$tmplcontent['keyword3'] = $order['totalprice'].'元';//订单金额
		$tmplcontent['keyword4'] = $order['title'];//商品信息
        $tmplcontentNew = [];
        $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
        $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
        $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
        $tmplcontentNew['amount5'] = $order['totalprice']==0?'0.00':$order['totalprice'];//订单金额
        $tmplcontentNew['thing3'] = $order['title'];//商品信息
		//\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('admin/order/kanjiaorder', $aid),$order['mdid'],$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/kanjiaorder',$order['mdid'],$tmplcontentNew,'sendhttmpl');

		$tmplcontent['first'] = '恭喜您的订单已支付成功';
		//$rs = \app\common\Wechat::sendtmpl($aid,$mid,'tmpl_orderpay',$tmplcontent,m_url('activity/kanjia/orderlist', $aid),$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,0,$mid,'tmpl_orderpay',$tmplcontent,'activity/kanjia/orderlist',0,$tmplcontentNew,'sendtmpl');

		$tmplcontent = [];
		$tmplcontent['thing11'] = $order['title'];
		$tmplcontent['character_string2'] = $order['ordernum'];
		$tmplcontent['phrase10'] = '已支付';
		$tmplcontent['amount13'] = $order['totalprice'].'元';
		$tmplcontent['thing27'] = $member['nickname'];
		//\app\common\Wechat::sendhtwxtmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,'admin/order/kanjiaorder',$order['mdid']);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/kanjiaorder',$order['mdid'],'','sendhtwxtmpl');

		//短信通知
		$rs = \app\common\Sms::send($aid,$member['tel'] ? $member['tel'] : $order['tel'],'tmpl_orderpay',['ordernum'=>$order['ordernum']]);
	}
	//秒杀订单
	public static function seckill_pay($orderid){
		$order = Db::name('seckill_order')->where('id',$orderid)->find();
		$member = Db::name('member')->where('id',$order['mid'])->find();
		$aid = $order['aid'];
		$mid = $order['mid'];
        //同城配送
        if($order['freight_type'] == 2){
            if(getcustom('express_maiyatian_autopush')) {
                //麦芽田同城配送自动推送
                \app\custom\MaiYaTianCustom::auto_push($aid,$orderid,$order,'seckill_order');
            }
        }
		//自动发货
		if($order['freight_type']==3){
			$freight_content = Db::name('seckill_product')->where('id',$order['proid'])->value('freightcontent');
			Db::name('seckill_order')->where('id',$order['id'])->update(['freight_content'=>$freight_content,'status'=>2,'send_time'=>time()]);
            //发货信息录入 微信小程序+微信支付
            if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                \app\common\Order::wxShipping($order['aid'],$order,'seckill');
            }
		}
		//在线卡密
		if($order['freight_type']==4){
			$codelist = Db::name('seckill_codelist')->where('proid',$order['proid'])->where('status',0)->order('id')->limit($order['num'])->select()->toArray();
			if($codelist && count($codelist) >= $order['num']){
				$pscontent = [];
				foreach($codelist as $codeinfo){
					$pscontent[] = $codeinfo['content'];
					Db::name('seckill_codelist')->where('id',$codeinfo['id'])->update(['orderid'=>$order['id'],'ordernum'=>$order['ordernum'],'headimg'=>$member['headimg'],'nickname'=>$member['nickname'],'buytime'=>time(),'status'=>1]);
				}
				$pscontent = implode("\r\n",$pscontent);
				Db::name('seckill_order')->where('id',$order['id'])->update(['freight_content'=>$pscontent,'status'=>2,'send_time'=>time()]);
			}
            //发货信息录入 微信小程序+微信支付
            if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                \app\common\Order::wxShipping($order['aid'],$order,'seckill');
            }
		}

		//支付后送券
		$couponlist = \app\common\Coupon::getpaygive($aid,$mid,'seckill',$order['totalprice']);
		if($couponlist){
			foreach($couponlist as $coupon){
				\app\common\Coupon::send($aid,$mid,$coupon['id']);
			}
		}
		\app\common\Wifiprint::print($aid,'seckill',$order['id']);
        if(getcustom('yx_queue_free_seckill')){
            \app\custom\QueueFree::join($order,'seckill');
        }
		//公众号通知 订单支付成功
		$tmplcontent = [];
		$tmplcontent['first'] = '有新秒杀订单支付成功';
		$tmplcontent['remark'] = '点击进入查看~';
		$tmplcontent['keyword1'] = $member['nickname']; //用户名
		$tmplcontent['keyword2'] = $order['ordernum'];//订单号
		$tmplcontent['keyword3'] = $order['totalprice'].'元';//订单金额
		$tmplcontent['keyword4'] = $order['title'];//商品信息
        $tmplcontentNew = [];
        $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
        $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
        $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
        $tmplcontentNew['amount5'] = $order['totalprice']==0?'0.00':$order['totalprice'];//订单金额
        $tmplcontentNew['thing3'] = $order['title'];//商品信息
		//\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('admin/order/seckillorder', $aid),$order['mdid'],$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/seckillorder',$order['mdid'],$tmplcontentNew,'sendhttmpl');

		$tmplcontent['first'] = '恭喜您的订单已支付成功';
		//$rs = \app\common\Wechat::sendtmpl($aid,$mid,'tmpl_orderpay',$tmplcontent,m_url('activity/seckill/orderlist', $aid),$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,0,$mid,'tmpl_orderpay',$tmplcontent,'activity/seckill/orderlist',0,$tmplcontentNew,'sendtmpl');

		$tmplcontent = [];
		$tmplcontent['thing11'] = $order['title'];
		$tmplcontent['character_string2'] = $order['ordernum'];
		$tmplcontent['phrase10'] = '已支付';
		$tmplcontent['amount13'] = $order['totalprice'].'元';
		$tmplcontent['thing27'] = $member['nickname'];
		//\app\common\Wechat::sendhtwxtmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,'admin/order/seckillorder',$order['mdid']);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/seckillorder',$order['mdid'],'','sendhtwxtmpl');

		//短信通知
		$rs = \app\common\Sms::send($aid,$member['tel'] ? $member['tel'] : $order['tel'],'tmpl_orderpay',['ordernum'=>$order['ordernum']]);

		$set = Db::name('admin_set')->where('aid',$aid)->find();
		if($set['fxjiesuantime'] == 1 && $set['fxjiesuantime_delaydays'] == '0'){
			\app\common\Order::giveCommission($order,'seckill');
		}
	}

	//充值订单
	public static function recharge_pay($orderid){
		$order = Db::name('recharge_order')->where('id',$orderid)->find();
		$member = Db::name('member')->where('id',$order['mid'])->find();
		$aid = $order['aid'];
		$mid = $order['mid'];

		//充值赠送
		$giveset = Db::name('recharge_giveset')->where('aid',$aid)->find();
		if($giveset && $giveset['status']==1){
			$givedata = json_decode($giveset['givedata'],true);
		}else{
			$givedata = array();
		}
		$givemoney = 0;
		$givescore = 0;
		$moneyduan = 0;

        if(getcustom('yx_money_monthsend')){
            //处理充值每月赠送
            $monthdatas = [];
        }
		if($givedata){
			foreach($givedata as $give){
				if($order['money']*1 >= $give['money']*1 && $give['money']*1 > $moneyduan){
					$moneyduan = $give['money']*1;
					$givemoney = $give['give']*1;
                    $givescore = $give['give_score']*1;
                    if(getcustom('yx_money_monthsend')){
                        //总共返多少
                        $monthdatas['allsendmoney'] = $order['money'];
                        $monthdatas['allsendscore'] = 0;

                        //立即赠送
                        $monthdatas['month_sendmoney']  = $give['month_sendmoney'];
                        $monthdatas['month_sendscore']  = $give['month_sendscore'];
                        //每个月返还
                        $monthdatas['month_sendmoney2'] = $give['month_sendmoney2'];
                        $monthdatas['month_sendscore2'] = $give['month_sendscore2'];
                        $monthdatas['month_sendnum']    = $give['month_sendnum'];
                    }
				}
			}
		}

        //是否直接增加充值
        $addmoney = true;

        if(getcustom('yx_money_monthsend')){
            //处理充值每月赠送
            if($monthdatas){
                //查询是否有立即到账设置和按月返还设置
                if(!empty($monthdatas['month_sendmoney']) || $monthdatas['month_sendmoney'] === '0'){
                    $addmoney = false;
                    if($monthdatas['month_sendmoney'] > 0){
                        \app\common\Member::addmoney($aid,$mid,$monthdatas['month_sendmoney'],'充值立即到账');
                    }
                }
                if(!empty($monthdatas['month_sendscore']) || $monthdatas['month_sendscore'] === '0'){
                    $addmoney = false;
                    if($monthdatas['month_sendscore'] > 0){
                        \app\common\Member::addscore($aid,$mid,$monthdatas['month_sendscore'],'充值立即到账');
                    }
                }
                //是否有每月到账设置
                if((!empty($monthdatas['month_sendmoney']) || $monthdatas['month_sendmoney'] === '0') || (!empty($monthdatas['month_sendmoney']) || $monthdatas['month_sendmoney'] === '0')){
                    $addmoney = false;
                }
                //是否有按月返还设置
                if((!empty($monthdatas['month_sendnum']) || $monthdatas['month_sendnum'] === '0')){
                    $addmoney = false;
                }
                \app\custom\yingxiao\MoneyMonthsendCustom::deal_monthlog($order,$monthdatas);
            }
        }
        if($addmoney){
            $params = [];
            if(getcustom('moneylog_detail')){
                $params['type'] = 'recharge';
                $params['ordernum'] = $order['ordernum'];
            }
            if(getcustom('recharge_use_mendian')){
                $params['mdid'] = $order['mdid']; 
            }
            \app\common\Member::addmoney($aid,$mid,$order['money'],t('余额').'充值','','',$orderid,$params);
        }

        if($givemoney > 0){
            $params = [];
            if(getcustom('recharge_use_mendian')){
                $params = ['mdid' => $order['mdid']];
            }
            \app\common\Member::addmoney($aid,$mid,$givemoney,'充值赠送',0,'','',$params);
            if(getcustom('member_recharge_detail_refund')){
                Db::name('recharge_order')->where('id',$orderid)->update(['give_money' => $givemoney]);
            }
        }
        if($givescore > 0){
            \app\common\Member::addscore($aid,$mid,$givescore,'充值赠送');
        }

		//支付后送券
		$couponlist = \app\common\Coupon::getpaygive($aid,$mid,'recharge',$order['money']);
		if($couponlist){
			foreach($couponlist as $coupon){
				\app\common\Coupon::send($aid,$mid,$coupon['id']);
			}
		}

		//升级
		\app\common\Member::uplv($aid,$mid);

        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping($order['aid'],$order,'recharge');
        }
        if(getcustom('recharge_order_wifiprint')){
            $rs = \app\common\Wifiprint::print($aid,'recharge',$order['id'],0);
        }
		$tmplcontent = array();
		$tmplcontent['first'] = '有新充值订单支付成功';
		$tmplcontent['remark'] = '点击进入查看~';
		$tmplcontent['accountType'] = t('会员').'昵称';
		$tmplcontent['account'] = $member['nickname'];
		$tmplcontent['amount'] = $order['money'].'元' . ($givemoney>0?'+赠送'.$givemoney.'元':'');
		$tmplcontent['result'] = '充值成功';
		//\app\common\Wechat::sendhttmpl($aid,0,'tmpl_recharge',$tmplcontent,m_url('admin/finance/rechargelog', $aid),$order['mdid']);
        \app\common\Wechat::createSendTmplData($aid,0,0,'tmpl_recharge',$tmplcontent,'admin/finance/rechargelog',$order['mdid'],'','sendhttmpl');

		//充值通知
        if(getcustom('zhaopin')){
            \app\model\Zhaopin::sendhtsms('tmpl_recharge',[],$order['aid'],$order['bid'],$order['mdid']);
        }
        //充值余额
        if(getcustom('sms_temp_money_recharge')){
            if($member['tel']){
                $rs = \app\common\Sms::send($aid,$member['tel'],'tmpl_money_recharge',['money'=>$order['money'],'givemoney'=>$givemoney]);
            }
        }
        if(getcustom('member_recharge_yj')){
            //充值业绩
            \app\custom\RechargeYj::addyj($order);
        }
		//余额充值通知
        $tel = $member['tel']?$member['tel']:'';
        if($tel){
            //短信通知  
            $admin_set =  Db::name('admin_set')->where('aid',$aid)->field('name,tel')->find();
            $bname = $admin_set['name'];
            $btel = $admin_set['tel'];
             \app\common\Sms::send($aid,$tel,'tmpl_admin_recharge_money',['bname' => $bname,'money' => $order['money'],'btel' => $btel]);
        }
	}
    //充值订单
    public static function overdraft_recharge_pay($orderid){
        $order = Db::name('overdraft_recharge_order')->where('id',$orderid)->find();
        $aid = $order['aid'];
		$mid = $order['mid'];
		$money = $order['totalprice'];
        $remark = '用户还款';
        \app\common\Member::addOverdraftMoney($aid,$mid,$money,$remark);
        return true;
    }


	//会员升级订单
	public static function member_levelup_pay($orderid){
		$order = Db::name('member_levelup_order')->where('id',$orderid)->find();
		$member = Db::name('member')->where('id',$order['mid'])->find();
		$aid = $order['aid'];
		$mid = $order['mid'];

		//成为分销商
		$leveldata = Db::name('member_level')->where('aid',$aid)->where('id',$order['levelid'])->find();
		if(getcustom('gdfenhong_level')){
            if(!$leveldata['apply_paygudong']){
                //当时参与股东分红的直接改状态，防止后期设置了可以参与分红又触发分红
                Db::name('member_levelup_order')->where('id',$orderid)->update(['isfenhong'=>2]);
            }
        }

        if(getcustom('mendian_member_levelup_fenhong')){
            //扫门店码升级分红 详细见文档功能3、4 https://doc.weixin.qq.com/sheet/e3_AV4AYwbFACwhK9lmw4HTpWYpjlp8K?scode=AHMAHgcfAA0dXW7gYuAeYAOQYKALU&tab=BB08J2
            $u_info = Db::name('member')->where('aid',$order['aid'])->where('id',$order['mid'])->field('id,mdid,pid')->find();
            $sysset = Db::name('admin_set')->where('aid',$order['aid'])->find();
            //判断会员是有上级-没有的话绑定上级
            if($u_info && $u_info['pid'] == 0 && $order['mdid'] > 0 && $sysset['mendian_member_levelup_fenhong'] == 1){
                $mend = Db::name('mendian')->where('aid',$order['aid'])->where('id',$order['mdid'])->field('id,member_levelup_fenhong,member_levelup_fenhong_mid')->find();
                if($mend['member_levelup_fenhong'] == 1 && $mend['member_levelup_fenhong_mid']){
                    if($puser = Db::name('member')->where('aid',$order['aid'])->where('id',$mend['member_levelup_fenhong_mid'])->field('id,path,pid')->find()){
                        $ppath = $puser['path'] ? $puser['path'].','.$puser['id'] : '';
                        Db::name('member')->where('aid',$order['aid'])->where('id',$order['mid'])->update(['pid'=>$puser['id'],'path'=>$ppath]);
                    }
                }
            }
        }

		if($leveldata['apply_check']){
			//$return = array('status'=>2,'msg'=>'付款成功请等待审核');

			$tmplcontent = [];
			$tmplcontent['first'] = '有新用户申请升级,请及时处理';
			$tmplcontent['remark'] = '请进入电脑端后台进行审核~';
			$tmplcontent['keyword1'] = $leveldata['name']; //会员等级
			$tmplcontent['keyword2'] = '待审核';//审核状态
			//\app\common\Wechat::sendhttmpl($aid,0,'tmpl_uplv',$tmplcontent);
            \app\common\Wechat::createSendTmplData($aid,0,0,'tmpl_uplv',$tmplcontent,'',0,'','sendhttmpl');

		}else{
			if($leveldata['yxqdate'] > 0){
				$levelendtime = strtotime(date('Y-m-d')) + 86400 + 86400 * $leveldata['yxqdate'];
			}else{
				$levelendtime = 0;
			}
			Db::name('member_levelup_order')->where('id',$orderid)->update(['status'=>2,'paytime'=>time(),'levelup_time' =>time()]);
            //判断是否默认分组
			if($leveldata['cid'] > 0)
            $is_default = Db::name('member_level_category')->where('id', $leveldata['cid'])->value('isdefault');
            if($is_default || $leveldata['cid'] == 0) {
                Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['levelid'=>$leveldata['id'],'levelendtime'=>$levelendtime,'levelstarttime'=>time()]);

                //更新代理区域
                if($leveldata['areafenhong']==1){
                    Db::name('member')->where('aid',$aid)->where('id',$order['mid'])->update(['areafenhong_province'=>$order['areafenhong_province'],'areafenhong_city'=>'','areafenhong_area'=>'']);
                }elseif($leveldata['areafenhong']==2){
                    Db::name('member')->where('aid',$aid)->where('id',$order['mid'])->update(['areafenhong_province'=>$order['areafenhong_province'],'areafenhong_city'=>$order['areafenhong_city'],'areafenhong_area'=>'']);
                }elseif($leveldata['areafenhong']==3){
                    Db::name('member')->where('aid',$aid)->where('id',$order['mid'])->update(['areafenhong_province'=>$order['areafenhong_province'],'areafenhong_city'=>$order['areafenhong_city'],'areafenhong_area'=>$order['areafenhong_area']]);
                }elseif($leveldata['areafenhong']==10){
                    Db::name('member')->where('aid',$aid)->where('id',$order['mid'])->update(['areafenhong_largearea'=>$order['areafenhong_largearea']]);
                }
            } else {
                if(getcustom('plug_sanyang')) {
                    $count = Db::name('member_level_record')->where('aid', $aid)->where('mid', $mid)->where('cid', $leveldata['cid'])->count();
                    if($count) Db::name('member_level_record')->where('aid', $aid)->where('mid', $mid)->where('cid', $leveldata['cid'])->update(['levelid' => $leveldata['id'], 'levelendtime' => $levelendtime]);
                    else {
                        $record_data = ['levelid' => $leveldata['id'], 'levelendtime' => $levelendtime];
                        $record_data['aid'] = $aid;
                        $record_data['mid'] = $mid;
                        $record_data['createtime'] = time();
                        $record_data['cid'] = $leveldata['cid'];
                        Db::name('member_level_record')->insertGetId($record_data);
                    }
                    Db::name('member_level_record')->where('aid', $aid)->where('mid', $mid)->where('cid', $leveldata['cid'])->update(['levelstarttime'=>time()]);

                    //更新代理区域
                    if($leveldata['areafenhong']==1){
                        Db::name('member_level_record')->where('aid', $aid)->where('mid', $mid)->where('cid', $leveldata['cid'])->update(['areafenhong_province'=>$order['areafenhong_province']]);
                    }elseif($leveldata['areafenhong']==2){
                        Db::name('member_level_record')->where('aid', $aid)->where('mid', $mid)->where('cid', $leveldata['cid'])->update(['areafenhong_province'=>$order['areafenhong_province'],'areafenhong_city'=>$order['areafenhong_city']]);
                    }elseif($leveldata['areafenhong']==3){
                        Db::name('member_level_record')->where('aid', $aid)->where('mid', $mid)->where('cid', $leveldata['cid'])->update(['areafenhong_province'=>$order['areafenhong_province'],'areafenhong_city'=>$order['areafenhong_city'],'areafenhong_area'=>$order['areafenhong_area']]);
                    }
                }
            }

            //0702给上级更新拉新时间
            if(getcustom('seckill2') && in_array($order['levelid'],explode(',',Db::name('seckill2_sysset')->where('aid',$order['aid'])->value('seckill_level')))){
                Db::name('member')->where(['aid'=>$aid,'id'=>$member['pid']])->update(['laxin_time'=>time()]);
            }

            if($leveldata['apply_payfenxiao'] == 1){ //升级费用参与分销及分红
                \app\common\Common::applypayfenxiao($aid,$order['id']);
            }else{
                if(getcustom('member_level_paymoney_commissionfrozenset')){
                    //升级费用 单独设置直推分销
                    if($leveldata['apply_payfenxiao'] == 2){
                        \app\common\Order::deal_paymoney_commissionfrozenset($aid,$order,$member,$leveldata);
                    }
                }
            }

            //赠送积分
            if($leveldata['up_give_score'] > 0) {
                \app\common\Member::addscore($aid,$mid,$leveldata['up_give_score'],'升级奖励');
            }

            //奖励佣金
            if($leveldata['up_give_commission'] > 0) {
                \app\common\Member::addcommission($aid,$mid,0,$leveldata['up_give_commission'],'升级奖励');
            }

            //奖励余额
            if($leveldata['up_give_money'] > 0) {
                \app\common\Member::addmoney($aid,$mid,$leveldata['up_give_money'],'升级奖励');
            }

            //赠送上级佣金
            if ($leveldata['up_give_parent_money'] > 0) {
                $pid = Db::name('member')->where('aid', $aid)->where('id', $mid)->value('pid');
                if($pid > 0) \app\common\Member::addcommission($aid, $pid, $mid, $leveldata['up_give_parent_money'], '直推奖');
            }

            if(getcustom('coupon_pack')){
                if($leveldata['up_give_couponpack']) {
                    \app\common\Coupon::send($aid,$mid,$leveldata['up_give_couponpack'],true);
                }
            }

			//升级赠送优惠券
            if(getcustom('up_give_coupon')){
                //商城优惠券赠送
                $shop_coupon = $leveldata['up_give_coupon']?json_decode($leveldata['up_give_coupon'],true):[];
                foreach($shop_coupon as $k=>$v){
                    if($v['num']<1){
                        continue;
                    }
                    for($i=0;$i<$v['num'];$i++){
                        \app\common\Coupon::send($aid,$mid,$v['id'],true);
                    }
                }
                //餐饮优惠券赠送
                $restaurant_coupon = $leveldata['up_give_restaurant_coupon']?json_decode($leveldata['up_give_restaurant_coupon'],true):[];
                foreach($restaurant_coupon as $k=>$v){
                    if($v['num']<1){
                        continue;
                    }
                    for($i=0;$i<$v['num'];$i++){
                        \app\common\Coupon::send($aid,$mid,$v['id'],true);
                    }
                }
            }
            if(getcustom('school_product')) {
                \app\model\School::updateMemberClass($aid, $mid, $orderid, $order['school_id'], $order['grade_id'], $order['class_id'], $order['levelid']);
            }
            if(getcustom('up_fxorder_condition_new')){
                //升级
                \app\common\Member::uplv($aid,$mid);
            }
            if(getcustom('ganer_fenxiao')){
                //升级
                \app\common\Member::uplv($aid,$mid);
                \app\common\Fenxiao::tuiguang_bonus($orderid);
            }
            //die('stop');
            $member_levelup_parentcommission_jicha = 0;
            if(getcustom('member_levelup_parentcommission_jicha')){
                //上级佣金按团队级差发放
                $member_levelup_parentcommission_jicha = 1;
            }
			if(getcustom('member_levelup_parentcommission')){
				if($leveldata['levelup_parentcommission']){
					$levelup_parentcommission = json_decode($leveldata['levelup_parentcommission'],true);
					$parent = Db::name('member')->where('aid',$aid)->where('id',$member['pid'])->find();
					$commission_money =dd_money_format($levelup_parentcommission[$parent['levelid']]);
					if($commission_money > 0){ 
						\app\common\Member::addcommission($aid, $member['pid'], $member['id'], $commission_money, '直推-会员升级奖励');
					}
                    if($member_levelup_parentcommission_jicha && $leveldata['levelup_parent_jicha']){
                        \app\common\Member::parent_commission($leveldata,$parent,$member['id'],$aid);
                    }
				}		
			}
			//[{"coupon_id":"485","cycletype":"2","cyclenum":"2","coupon_num":"1"}]
			if(getcustom('member_levelup_givecoupon')){  //赠送周期优惠券
				if($leveldata['givecoupondata']){
					$givecoupondata = json_decode($leveldata['givecoupondata'],true);
					foreach($givecoupondata as $k=>$g){
						if($g['cycletype']>=1 && $g['cyclenum']>=1){
							$log = Db::name('member_give_coupon_log')->where('aid',$aid)->where('mid',$mid)->where('levelid',$leveldata['id'])->find();
							if($log)  break;
							for($i=1;$i<=$g['cyclenum'];$i++){
								$data = [];
								$days=0;
								if($g['cycletype']==2) $days=7;
								if($g['cycletype']==3) $days=30;
								if($i==1){
									$data['beginzstime'] = time();
									$data['status'] = 1;
									$data['zstime'] = time();
									//赠送优惠券	
									for($j=1;$j<=$g['coupon_num'];$j++){
										\app\common\Coupon::send($aid,$mid,$g['coupon_id'],false,0,$days);
									}
								}elseif($i>1){
									$BeginDate=date('Y-m-01',strtotime(date('Y-m-d')));
									$data['beginzstime'] = strtotime("$BeginDate +$i month");;
									$data['status'] = 0;
								}
								$data['aid'] = $aid;
								$data['mid'] = $mid;
								$data['couponid'] = $g['coupon_id'];
								$data['coupon_num'] = $g['coupon_num'];
								$data['cycle_type'] = $g['cycletype'];
								$data['createtime'] = time();
								$data['levelid'] = $leveldata['id'];
								Db::name('member_give_coupon_log')->insert($data);
							}
						}
					}
				}		
			}
			if(getcustom('member_levelup_auth')){
				if($leveldata['give_level_totalmoney']>0){
					//查看已经赠送了的额度
					$yzsed =  Db::name('member_salelevel_order')->where('aid',$aid)->where('from_mid',$order['mid'])->where('status',1)->sum('levelprice');
					$salelevel_money =  round($leveldata['give_level_totalmoney']-$yzsed,2);
					if($salelevel_money>0){
						Db::name('member')->where('id',$order['mid'])->update(['salelevel_money'=>$salelevel_money]);
					}
				}
			}

             // 推荐人有效期
            if(getcustom('levelup_add_parent_time')){
                \app\common\Member::addParentTime($aid,$member['pid'],$leveldata['id']);
            }

            //会员升级
            \app\common\Member::uplv($aid,$mid);
            

            if(getcustom('network_slide')){
                //公排网滑落
                $res = \app\common\Member::net_slide($member['pid'],$mid,$leveldata['id']);
                //dump($res);
            }

            if(getcustom('mendian_member_levelup_fenhong')){
                //扫门店码升级分红 详细见文档功能3、4 https://doc.weixin.qq.com/sheet/e3_AV4AYwbFACwhK9lmw4HTpWYpjlp8K?scode=AHMAHgcfAA0dXW7gYuAeYAOQYKALU&tab=BB08J2

                //判断会员是否绑定过门店
                if(Db::name('member')->where('aid',$order['aid'])->where('id',$order['mid'])->where('mdid',0)->find() && $order['mdid'] > 0){
                    Db::name('member')->where('aid',$order['aid'])->where('id',$order['mid'])->update(['mdid'=>$order['mdid'],'add_mendian_time' => time()]);
                }

                //会员扫描门店二维支付升级后给门店绑定人分红
                \app\common\Fenhong::mendian_member_levelup_fenhong($order['aid'],$order['id']);
            }

            //发货信息录入 微信小程序+微信支付
            if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                \app\common\Order::wxShipping($order['aid'],$order,'member_levelup');
            }

            // 门店审核
            if(getcustom('member_level_add_apply_mendian') && $order['up_mdid']){
                Db::name('mendian')->where('aid',$aid)->where('mid',$mid)->where('id',$order['up_mdid'])->where('check_status',0)->update(['check_status'=>1,'status'=>1]);
            }

			$tmplcontent = [];
			$tmplcontent['first'] = '恭喜您成功升级为'.$leveldata['name'];
			$tmplcontent['remark'] = '点击进入查看~';
			$tmplcontent['keyword1'] = $leveldata['name']; //会员等级
			$tmplcontent['keyword2'] = '已生效';//审核状态
			//$rs = \app\common\Wechat::sendtmpl($aid,$mid,'tmpl_uplv',$tmplcontent,m_url('pages/my/usercenter', $aid));
            \app\common\Wechat::createSendTmplData($aid,0,$mid,'tmpl_uplv',$tmplcontent,'pages/my/usercenter',0,'','sendtmpl');
            //发短信
            $member = Db::name('member')->where('aid',$aid)->where('id',$order['mid'])->field('id,tel')->find();
            $tel = $member['tel']?$member['tel']:'';
            if($tel){
                //短信通知
                $oldlevelname = Db::name('member_level')->where('aid',$aid)->where('id',$order['beforelevelid'])->value('name');
                $expiration_days = '永久';
                if($levelendtime > 0){
                    $expiration_days= getTimeDiff(time(),$levelendtime);
                }
                \app\common\Sms::send($aid,$tel,'tmpl_member_uplevel',['old_level' => $oldlevelname,'new_level' => $leveldata['name'],'expiration_days' => $expiration_days]);
            }
		}
	}
	//表单支付
	public static function form_pay($orderid){
		$order = Db::name('form_order')->where('id',$orderid)->find();
		$member = Db::name('member')->where('id',$order['mid'])->find();
		$aid = $order['aid'];
		$mid = $order['mid'];
		//分销提成
		$form = Db::name('form')->where('id',$order['formid'])->find();
		$totalcommission = 0;
		if($form && $form['commissionset']!=-1){
			$ogdata = [];
			if($member['pid']){
				$parent1 = Db::name('member')->where('aid',$aid)->where('id',$member['pid'])->find();
				if($parent1){
					$agleveldata1 = Db::name('member_level')->where('aid',$aid)->where('id',$parent1['levelid'])->find();
					if($agleveldata1['can_agent']!=0){
						$ogdata['parent1'] = $parent1['id'];
					}
				}
			}
			if($parent1['pid']){
				$parent2 = Db::name('member')->where('aid',$aid)->where('id',$parent1['pid'])->find();
				if($parent2){
					$agleveldata2 = Db::name('member_level')->where('aid',$aid)->where('id',$parent2['levelid'])->find();
					if($agleveldata2['can_agent']>1){
						$ogdata['parent2'] = $parent2['id'];
					}
				}
			}
			if($parent2['pid']){
				$parent3 = Db::name('member')->where('aid',$aid)->where('id',$parent2['pid'])->find();
				if($parent3){
					$agleveldata3 = Db::name('member_level')->where('aid',$aid)->where('id',$parent3['levelid'])->find();
					if($agleveldata3['can_agent']>2){
						$ogdata['parent3'] = $parent3['id'];
					}
				}
			}
			if($form['commissionset']==1){//按比例
				$commissiondata = json_decode($form['commissiondata1'],true);
				if($commissiondata){
					$ogdata['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * $order['money'] * 0.01;
					$ogdata['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * $order['money'] * 0.01;
					$ogdata['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * $order['money'] * 0.01;
				}
			}elseif($form['commissionset']==2){//按固定金额
				$commissiondata = json_decode($form['commissiondata2'],true);
				if($commissiondata){
					$ogdata['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * 1;
					$ogdata['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * 1;
					$ogdata['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * 1;
				}
			}else{
				$ogdata['parent1commission'] = $agleveldata1['commission1'] * $order['money'] * 0.01;
				$ogdata['parent2commission'] = $agleveldata2['commission2'] * $order['money'] * 0.01;
				$ogdata['parent3commission'] = $agleveldata3['commission3'] * $order['money'] * 0.01;
			}

			if($ogdata['parent1'] && $ogdata['parent1commission'] > 0){
				$totalcommission+=$ogdata['parent1commission'];
			}
			if($ogdata['parent2'] && $ogdata['parent2commission'] > 0){
				$totalcommission+=$ogdata['parent2commission'];
			}
			if($ogdata['parent3'] && $ogdata['parent3commission'] > 0){
				$totalcommission+=$ogdata['parent3commission'];
			}
			if($ogdata['parent1']){
				\app\common\Member::uplv($aid,$ogdata['parent1']);
			}
            if($ogdata){
                Db::name('form_order')->where('id',$orderid)->update($ogdata);
            }
		}

		if($order['bid']!=0){//入驻商家的货款
			$binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
			$totalmoney = $order['money'] - $totalcommission;
			if($totalmoney > 0){
				$totalmoney = $totalmoney * (100-$binfo['feepercent']) * 0.01;
			}
            if(getcustom('yx_order_discount_rand') && $order['discount_rand_money'] > 0){
                //加上随机立减金额
                $totalmoney = bcadd($totalmoney,$order['discount_rand_money'],2);
            }
			\app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'表单支付 订单号：'.$order['ordernum'],false,'form',$order['ordernum']);
		}
        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping($order['aid'],$order,'form');
        }

        if(getcustom('form_option_givescore')){
            //赠送选项积分（暂单选选择）
            if($form && $form['content']){
                //查询表设置的参数
                $formcontent = json_decode($form['content'],true);
                if($formcontent){
                    //$givescore = 0;//赠送积分
                    //查询选择的选项
                    foreach($formcontent as $k=>$v){
                        if($v['key']=='radio' || $v['key']=='selector'){
                            if(isset($order['form'.$k])){
                                $val = $order['form'.$k];//获取选项值;
                                $i = -1;//对应的序号
                                if($v['val2']){
                                    foreach($v['val2'] as $k2=>$v2){
                                        if($v2 == $val){
                                            $i = $k2;
                                        }
                                    }
                                    unset($v2);
                                }
                                if($i>=0 && $v['val16']){
                                    $givescore = $v['val16'][$i]?$v['val16'][$i]:0;
                                    if($givescore >0){
                                        $res = \app\common\Member::addscore($aid,$order['mid'],$givescore,$val.'赠送'.t('积分'));
                                        if($res && $res['status'] == 1){
                                            Db::name('form_order')->where('aid',$aid)->where('id',$order['id'])->update(['issend_opscore'=>1,'send_opscoretime'=>time()]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    unset($v);
                }
            }
        }

		$tmplcontent = [];
		$tmplcontent['first'] = '有客户提交表单成功';
		$tmplcontent['remark'] = '点击查看详情~';
		$tmplcontent['keyword1'] = $order['title'];
		$tmplcontent['keyword2'] = date('Y-m-d H:i');
        $tempconNew = [];
        $tempconNew['thing3'] = $order['title'];//报名名称
        $tempconNew['time5'] = date('Y-m-d H:i');//申请时间
		//\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_formsub',$tmplcontent,m_url('admin/form/formdetail?id='.$orderid,$aid),0,$tempconNew);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_formsub',$tmplcontent,'admin/form/formdetail?id='.$orderid,0,$tempconNew,'sendhttmpl');
		$tel = $member['tel']?$member['tel']:'';
		if($tel){
			//短信通知
			$rs = \app\common\Sms::send($aid,$tel,'tmpl_formsubmit');
		}
	}

	//工单支付
	public static function workorder_pay($orderid){
		$order = Db::name('workorder_order')->where('id',$orderid)->find();
		$aid = $order['aid'];

        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping($order['aid'],$order,'workorder');
        }

		$tmplcontent = [];
		$tmplcontent['first'] = '有客户提交工单成功';
		$tmplcontent['remark'] = '请进入电脑端后台进行查看~';
		$tmplcontent['keyword1'] = $order['title'];
		$tmplcontent['keyword2'] = date('Y-m-d H:i');
        $tempconNew = [];
        $tempconNew['thing3'] = $order['title'];//报名名称
        $tempconNew['time5'] = date('Y-m-d H:i');//申请时间
		//\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_formsub',$tmplcontent,'',0,$tempconNew);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_formsub',$tmplcontent,'',0,$tempconNew,'sendhttmpl');
	}

	//付费查看页面
	public static function designerpage_pay($orderid){
		$order = Db::name('designerpage_order')->where('id',$orderid)->find();
		\app\common\Order::collect($order,'designerpage');
        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping($order['aid'],$order,'designerpage');
        }
	}
	//购买优惠券
	public static function coupon_pay($orderid){
		$order = Db::name('coupon_order')->where('id',$orderid)->find();
		$rs = \app\common\Coupon::send($order['aid'],$order['mid'],$order['cpid']);
		if($rs && $rs['status'] == 0){
			$order['totalprice'] = $order['price'];
			$rs = \app\common\Order::refund($order,$order['totalprice'],$rs['msg']);
			//\think\facade\Log::write($rs);
		}else{
			\app\common\Order::collect($order,'coupon');
            //发货信息录入 微信小程序+微信支付
            if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                \app\common\Order::wxShipping($order['aid'],$order,'coupon');
            }
		}
		if(getcustom('coupon_buy_give_nolimit',$order['aid'])){
            //赠送限制区分获得优惠券是活动赠送还是购买获得(get_way=1是购买获得)
            if($rs['status']==1 && !empty($rs['coupon_record_ids'])){
                Db::name('coupon_record')->where('id','in',$rs['coupon_record_ids'])->update(['get_way'=>1]);
            }
        }
	}
	//买单
	public static function maidan_pay($orderid){
		$order = Db::name('maidan_order')->where('id',$orderid)->find();
		$member = Db::name('member')->where('id',$order['mid'])->find();
		$aid = $order['aid'];
		$mid = $order['mid'];

        if(getcustom('sound',$aid)){
            \app\common\Sound::play($aid,'maidan',$order);
        }

        //新增打印机打印
        \app\common\Wifiprint::print($aid,'maidan',$order['id']);

        if(getcustom('member_dedamount')){
            //商家支付，需要完成抵扣金抵扣记录
            if($order['bid']){
                $params=['ordernum'=>$order['ordernum'],'paytype'=>'maidan','type'=>'pay'];
                \app\common\Member::deal_staydecdedamount($order['aid'],$order['bid'],$order['mid'],'完成买单支付',$params);
            }
            if($order['money']>0){
                //消费送抵扣金
                $dedamountset = Db::name('admin_set')->where('aid',$order['aid'])->field('dedamount_fullmoney,dedamount_givemoney,dedamount_type,dedamount_type2')->find();
                if($dedamountset['dedamount_fullmoney']>0 && $dedamountset['dedamount_givemoney']>0){
                    $canadd = true;
                    //判断消费赠送类型二 0 全部 1：仅商城 2：仅买单
                    if($dedamountset['dedamount_type2'] == 1){
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
                        $givededamount = $order['money'] / $dedamountset['dedamount_fullmoney'] * $dedamountset['dedamount_givemoney'];
                        $givededamount = round($givededamount,2);
                        if($givededamount>0){
                            $params=['orderid'=>$order['id'],'ordernum'=>$order['ordernum'],'paytype'=>'maidan'];
                            \app\common\Member::adddedamount($order['aid'],$order['bid'],$order['mid'],$givededamount,'消费赠送抵扣金',$params);
                        }
                    }
                }
            }
        }
		if($order['couponrid']){
            $couponrecord = Db::name('coupon_record')->where('id',$order['coupon_rid'])->where('mid',$order['mid'])->where('aid',$aid)->find();
            $couponrecord_update = ['status'=>1,'usetime'=>time()];
            if(getcustom('coupon_pack')){
                //张数
                if($couponrecord && $couponrecord['packrid'] && $couponrecord['num'] && $couponrecord['num']>0){
                    $usenum = $couponrecord['usenum']+1;
                    if($usenum<$couponrecord['num']){
                        $couponrecord_update = ['status'=>0,'usenum'=>$usenum];
                    }else{
                        $couponrecord_update = ['status'=>1,'usenum'=>$couponrecord['num'],'usetime'=>time()];
                    }
                }
            }
			Db::name('coupon_record')->where('id',$order['couponrid'])->update($couponrecord_update);
		}
        if(getcustom('maidan_item')) {
            //买单项目 赠送积分
            if($order['item_id']){
                $orderItem = Db::name('maidan_item')->where('aid',$order['aid'])->where('bid',$order['bid'])->where('id',$order['item_id'])->find();
                if($orderItem['score_ratio'] > 0){
                    $giftScore = $order['paymoney'] * $orderItem['score_ratio'] / 100;
                    $giftScore > 0 && \app\common\Member::addscore($aid,$order['mid'],$giftScore,'买单项目赠送'.t('积分'));
                }
            }
        }

        if(getcustom('yx_offline_subsidies',$aid)){
            //线下补助
            \app\common\Member::offlineSubsidiesLog($aid,$order,'maidan',0);
        }

        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping($order['aid'],$order,'maidan');
        }

		\app\common\Order::collect($order,'maidan');
        if(getcustom('levelup_perpaymoney',$aid)){
            \app\common\Member::uplv($aid,$mid,'maidan');
        }
        //支付后送券
		$couponlist = \app\common\Coupon::getpaygive($aid,$mid,'maidan',$order['money'],$order['id']);
		if($couponlist){
			foreach($couponlist as $coupon){
				\app\common\Coupon::send($aid,$mid,$coupon['id']);
			}
		}
        if(getcustom('everyday_hongbao')) {
            $hd = Db::name('hongbao_everyday')->where('aid',$aid)->find();
            if($hd['status'] == 1 && $hd['hongbao_bl_maidan'] > 0) {
                $hongbaoEdu = round($order['paymoney'] * $hd['maidan_hongbao_bl'] / 100,2);
                if($hongbaoEdu > 0)
                \app\common\Member::addHongbaoEverydayEdu($aid,$order['mid'],$hongbaoEdu, '买单增加红包额度');
            }
        }
        //买单增加商家销量
        Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->inc('sales',1)->update();

        if(getcustom('maidan_qrcode')) {
            \app\custom\MaidanQrcode::deal_ycommission($order,$member);
        }

        if(getcustom('yx_cashback_maidan')){
            //处理返现
            \app\custom\OrderCustom::deal_maidancashback($aid,$order);
        }
        if(getcustom('maidan_invite')){
            //商家买单拉新
            if($order['bid'] > 0){
                $pid = 0;$invitedata = [];
                //查询系统设置
                $inviteset = Db::name('maidan_invite')->where('aid',aid)->where('bid',0)->where('status',1)->find();
                if($inviteset && $inviteset['validday']>0){
                    $business = Db::name('business')->where('id',$order['bid'])->where('aid',aid)->field('maidaninviteset,province,city,district')->find();
                    //判断商家区域
                    $is_in_area = 1; //区域限制
                    $area_participate = json_decode($inviteset['area_participate'],true);
                    if($area_participate && !in_array($business['province'],$area_participate) && !in_array($business['province'].'-'.$business['city'],$area_participate) && !in_array($business['province'].'-'.$business['city'].'-'.$business['district'],$area_participate)){
                        $is_in_area = 0;
                    }
                    $invitedata = $inviteset['invitedata']?json_decode($inviteset['invitedata'],true):[];
                    if($business && $business['maidaninviteset']>=0 && $is_in_area){
                        //查询买单商家绑定的mid
                        $mid = Db::name('admin_user')->where('aid',aid)->where('bid',$order['bid'])->where('isadmin','>',0)->value('mid');
                        $pid = $mid && $mid >0?$mid:0;
                        if($business['maidaninviteset'] == 1){
                            $invitedata = [];
                            //查询商家单独设置
                            $inviteset2 = Db::name('maidan_invite')->where('bid',$order['bid'])->where('aid',aid)->where('status',1)->find();
                            if($inviteset2){
                                $invitedata = $inviteset2['invitedata']?json_decode($inviteset2['invitedata'],true):[];
                            }
                        }
                        //如果用户邀请上级是商家绑定的mid
                        if($pid>0 && $member['pid'] == $pid && $invitedata){
                            //查询用户注册天数
                            $regday = time()-$member['createtime'];
                            $validday = $inviteset['validday']*86400;
                            if($regday<=$validday){
                                //查询用户是否提前消费其他项目
                                $payorderid = Db::name('payorder')->where('orderid',$orderid)->where('type','maidan')->where('aid',aid)->value('id');
                                if($payorderid){
                                    $count = Db::name('payorder')->where('id','<>',$payorderid)->where('mid',$order['mid'])->where('status',1)->where('aid',aid)->count('id');
                                    if(!$count){
                                        //拉新奖励
                                        $invitemoney = 0;
                                        foreach($invitedata as $invite){
                                            if($order['paymoney'] >= $invite['min'] && $invite['paymoney']<= $invite['max']){
                                                $invitemoney = 0;
                                                //返还比例
                                                if($invite['back'] && $invite['back']>0){
                                                    $invitemoney += $order['paymoney']*$invite['back']*0.01;
                                                }
                                                //返还固定金额
                                                if($invite['back2'] && $invite['back2']>0){
                                                    $invitemoney += $invite['back2'];
                                                }
                                            }
                                        }
                                        $invitemoney = $invitemoney?round($invitemoney,2):0;
                                        if($invitemoney>0){
                                            $up = Db::name('maidan_order')->where('id',$order['id'])->update(['invitemoney'=>$invitemoney]);
                                            if($up){
                                                //给商家增加拉新奖励
                                                \app\common\Business::addmoney(aid,$order['bid'],$invitemoney,'买单拉新奖励，订单号：'.$order['ordernum'],false,'maidan',$order['ordernum']);
                                                //20250618 增加拉新奖励记录
                                                \app\common\Business::maidan_invite_log(aid,$order,$invitemoney);
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

        if(getcustom('yx_mangfan_maidan')) {
            if($order['is_mangfan']){
                $order['ogid'] = $order['id'];
                $order['real_totalprice'] = $order['paymoney'];
                $order['type'] = 'maidan';
                $order_goods[] = $order;
                if($order_goods){
                    \app\custom\Mangfan::createRecord($aid, $order['mid'], $order['id'], $order['ordernum'], $order['paytime'], $order_goods,false,0,'maidan');
                }
                //发放盲返
                \app\custom\Mangfan::sendBonus($order['aid'],$order['mid'],$order['id'],'maidan');
            }
        }

        if(getcustom('bonus_pool_gold')){
            //思明定制奖金池
            \app\custom\BonusPoolGold::orderBonusPool($order['aid'],$order['mid'],$order['id'],'maidan');
        }
        if(getcustom('business_maidan_give_manager_score')){
            if($order['bid'] > 0){
                 $business_sysset = Db::name('business_sysset')->where('aid',$aid)->field('maidan_money_give_score_status,maidan_money_give_score_ratio')->find();
                 if($business_sysset['maidan_money_give_score_status'] ==1){
                     $user_mid = Db::name('admin_user')->where('aid',$aid)->where('bid',$order['bid'])->where('isadmin',1)->value('mid');
                     if($user_mid){
                         $score_weishu = 0;
                         if(getcustom('score_weishu')){
                             $score_weishu = Db::name('admin_set')->where('aid',aid)->value('score_weishu');
                             $score_weishu = $score_weishu?$score_weishu:0;
                         }
                         $give_score = dd_money_format($business_sysset['maidan_money_give_score_ratio'] * $order['paymoney'] ,$score_weishu);
                         if($give_score > 0){
                             \app\common\Member::addscore($aid,$user_mid,$give_score,'商家收款赠送');
                             Db::name('maidan_order')->where('aid',$aid)->where('id',$order['id'])->update(['manager_score_mid' => $user_mid,'manager_score' => $give_score]);
                         }
                     }
                 }
            }
        }
        if(getcustom('maidan_reward_score')){
            //买单赠送
            if($order['bid'] > 0){
                $business = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                $reward_member = bcmul($order['paymoney'],$business['maidan_reward_score_member']/100,2);
                if($reward_member>0){
                    //赠送会员
                    \app\common\Member::addscore($aid,$mid,$reward_member,'买单ID'.$orderid.'赠送','maidan_order');
                }
                $business_mid = $business['mid'];
                if($order['mdid']>0){
                    $mendian_mid = Db::name('admin_user')->where('mdid',$order['mdid'])->value('mid');
                    if($mendian_mid){
                        $business_mid = $mendian_mid;
                    }
                }
                $reward_business = bcmul($order['paymoney'],$business['maidan_reward_score_business']/100,2);
                if($reward_business>0 && $business_mid){
                    //赠送商家
                    \app\common\Member::addscore($aid,$business_mid,$reward_business,'买单ID'.$orderid.'赠送','maidan_order');
                }
            }
        }
        if(getcustom('maidan_binding_parent')){
            $maidan_binding_parent_type = Db::name('admin_set')->where('aid',$aid)->value('maidan_binding_parent_type');
            $maidan_binding_parent_type = $maidan_binding_parent_type??0;
            $bid = $order['bid'];
            if($maidan_binding_parent_type && $bid>0){
                $umid = Db::name('admin_user')->where('aid',$aid)->where('bid',$bid)->where('isadmin',1) ->value('mid');
                //我没绑定推荐人，且管理员有绑定会员  且 付款用户不能是管理员，且 父级推荐链中不能有我
                $umpath = Db::name('member')->where('aid',$aid)->where('id',$umid)->value('path');
                $umpath = explode(',',$umpath);
                $is_in = 0;
                if($umpath && in_array($mid,$umpath))$is_in = 1;
                if(!$member['pid'] && $umid && $member['id'] != $umid && !$is_in){
                    \app\model\Member::edit($aid,['id'=>$mid,'pid'=>$umid]);
                }
            }
        }
        if(getcustom('maidan_reward_commission',$aid)){
            //买单赠送佣金
            if($order['bid'] > 0){
                $business = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
                $reward_member_commission = bcmul($order['paymoney'],$business['maidan_reward_commission_member']/100,2);
                if($reward_member_commission>0){
                    //赠送会员
                    \app\common\Member::addcommission($aid,$mid,0,$reward_member_commission,'买单奖励消费者佣金(订单ID:'.$orderid.')','maidan_order');
                }
            }
        }

		//公众号通知 订单支付成功
        $tmplcontent = [];
        $tmplcontent['first'] = '有新买单订单支付成功';
        $tmplcontent['remark'] = '点击进入查看~';
        $tmplcontent['keyword1'] = $member['nickname']; //用户名
        $tmplcontent['keyword2'] = $order['ordernum'];//订单号
        $tmplcontent['keyword3'] = $order['money'].'元';//订单金额
        $tmplcontent['keyword4'] = $order['title'];//商品信息
        $tmplnew = Db::name('mp_tmplset_new')->where('aid',$aid)->find();
        if($tmplnew['tmpl_maidanpay']){
            $tmplcontentNew = [];
            $tmplcontentNew['thing13'] = \app\common\Mendian::getNameWithBusines($order);//门店
            $tmplcontentNew['thing12'] = $member['nickname']; //用户名
            $tmplcontentNew['character_string4'] = $order['ordernum'];//订单号
            $tmplcontentNew['amount15'] = $order['money'];//订单金额
            $tmplcontentNew['thing11'] = $order['title'];//商品信息
            //\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_maidanpay',$tmplcontent,m_url('adminExt/order/maidanlog', $aid),$order['mdid'],$tmplcontentNew);
            \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_maidanpay',$tmplcontent,'adminExt/order/maidanlog',$order['mdid'],$tmplcontentNew,'sendhttmpl');
        }elseif($tmplnew['tmpl_orderpay']){
            $tmplcontentNew = [];
            $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
            $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
            $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
            $tmplcontentNew['amount5'] = $order['money']==0?'0.00': $order['money'];//订单金额
            $tmplcontentNew['thing3'] = $order['title'];//商品信息
            //\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('adminExt/order/maidanlog', $aid),$order['mdid'],$tmplcontentNew);
            \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'adminExt/order/maidanlog',$order['mdid'],$tmplcontentNew,'sendhttmpl');
        }else{
           // \app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('adminExt/order/maidanlog', $aid),$order['mdid']);
            \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'adminExt/order/maidanlog',$order['mdid'],'','sendhttmpl');
        }
        if(getcustom('yx_hongbao_queue_free')){
            \app\custom\HongbaoQueueFree::join($order,'maidan');
        }
		$tmplcontent = [];
		$tmplcontent['thing11'] = $order['title'];
		$tmplcontent['character_string2'] = $order['ordernum'];
		$tmplcontent['phrase10'] = '已支付';
		$tmplcontent['amount13'] = $order['money'].'元';
		$tmplcontent['thing27'] = $member['nickname'];
		//\app\common\Wechat::sendhtwxtmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,'adminExt/order/maidanlog',$order['mdid']);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'adminExt/order/maidanlog',$order['mdid'],'','sendhtwxtmpl');
        if(getcustom('yx_buyer_subsidy',$aid)){
            $res = \app\custom\Subsidy::caclOrder($aid,$orderid,1,'maidan');
        }
        if(getcustom('yx_new_score',$aid)){
            $res = \app\custom\NewScore::caclOrder($aid,$orderid,1,'maidan');
        }
        if(getcustom('yx_digital_consum',$aid)){
            $res = \app\custom\yingxiao\DigitalConsum::caclOrder($aid,$orderid,1,'maidan');
        }
	}
    public static function maidan_new_pay($orderid){
	    if(getcustom('maidan_new')) {
            $order = Db::name('maidan_new_order')->where('id', $orderid)->find();
            $member = Db::name('member')->where('id', $order['mid'])->find();
            $aid = $order['aid'];
            $mid = $order['mid'];

            if (getcustom('sound',$aid)) {
                \app\common\Sound::play($aid, 'maidan_new', $order);
            }
            //新增打印机打印
            \app\common\Wifiprint::print($aid, 'maidan_new', $order['id']);

            if (getcustom('member_dedamount')) {
                //商家支付，需要完成抵扣金抵扣记录
                if ($order['bid']) {
                    $params = ['ordernum' => $order['ordernum'], 'paytype' => 'maidan', 'type' => 'pay'];
                    \app\common\Member::deal_staydecdedamount($order['aid'], $order['bid'], $order['mid'], '完成买单支付', $params);
                }
                if ($order['money'] > 0) {
                    //消费送抵扣金
                    $dedamountset = Db::name('admin_set')->where('aid', $order['aid'])->field('dedamount_fullmoney,dedamount_givemoney,dedamount_type,dedamount_type2')->find();
                    if ($dedamountset['dedamount_fullmoney'] > 0 && $dedamountset['dedamount_givemoney'] > 0) {
                        $canadd = true;
                        //判断消费赠送类型二 0 全部 1：仅商城 2：仅买单
                        if ($dedamountset['dedamount_type2'] == 1) {
                            $canadd = false;
                        } else {
                            //判断消费赠送类型 0 全部 1：仅平台 2：仅商户
                            if ($order['bid'] > 0 && $dedamountset['dedamount_type'] == 1) {
                                $canadd = false;
                            } else if ($order['bid'] <= 0 && $dedamountset['dedamount_type'] == 2) {
                                $canadd = false;
                            }
                        }
                        if ($canadd) {
                            $givededamount = $order['money'] / $dedamountset['dedamount_fullmoney'] * $dedamountset['dedamount_givemoney'];
                            $givededamount = round($givededamount, 2);
                            if ($givededamount > 0) {
                                $params = ['orderid' => $order['id'], 'ordernum' => $order['ordernum'], 'paytype' => 'maidan'];
                                \app\common\Member::adddedamount($order['aid'], $order['bid'], $order['mid'], $givededamount, '消费赠送抵扣金', $params);
                            }
                        }
                    }
                }
            }
            if ($order['couponrid']) {
                $couponrecord = Db::name('coupon_record')->where('id', $order['coupon_rid'])->where('mid', $order['mid'])->where('aid', $aid)->find();
                $couponrecord_update = ['status' => 1, 'usetime' => time()];
                if (getcustom('coupon_pack')) {
                    //张数
                    if ($couponrecord && $couponrecord['packrid'] && $couponrecord['num'] && $couponrecord['num'] > 0) {
                        $usenum = $couponrecord['usenum'] + 1;
                        if ($usenum < $couponrecord['num']) {
                            $couponrecord_update = ['status' => 0, 'usenum' => $usenum];
                        } else {
                            $couponrecord_update = ['status' => 1, 'usenum' => $couponrecord['num'], 'usetime' => time()];
                        }
                    }
                }
                Db::name('coupon_record')->where('id', $order['couponrid'])->update($couponrecord_update);
            }
            \app\common\Order::collect($order, 'maidan_new');

            //支付后送券
            $couponlist = \app\common\Coupon::getpaygive($aid, $mid, 'maidan', $order['money'], $order['id']);
            if ($couponlist) {
                foreach ($couponlist as $coupon) {
                    \app\common\Coupon::send($aid, $mid, $coupon['id']);
                }
            }
            if (getcustom('everyday_hongbao')) {
                $hd = Db::name('hongbao_everyday')->where('aid', $aid)->find();
                if ($hd['status'] == 1 && $hd['hongbao_bl_maidan'] > 0) {
                    $hongbaoEdu = round($order['paymoney'] * $hd['maidan_hongbao_bl'] / 100, 2);
                    if ($hongbaoEdu > 0)
                        \app\common\Member::addHongbaoEverydayEdu($aid, $order['mid'], $hongbaoEdu, '买单增加红包额度');
                }
            }
            //买单增加商家销量
            Db::name('business')->where('aid', $aid)->where('id', $order['bid'])->inc('sales', 1)->update();

            if (getcustom('maidan_qrcode')) {
                \app\custom\MaidanQrcode::deal_ycommission($order, $member);
            }

            if (getcustom('yx_cashback_maidan')) {
                //处理返现
                \app\custom\OrderCustom::deal_maidancashback($aid, $order);
            }
            if (getcustom('maidan_invite')) {
                //商家买单拉新
                if ($order['bid'] > 0) {
                    $pid = 0;
                    $invitedata = [];
                    //查询系统设置
                    $inviteset = Db::name('maidan_invite')->where('aid', aid)->where('bid', 0)->where('status', 1)->find();
                    if ($inviteset && $inviteset['validday'] > 0) {
                        $invitedata = $inviteset['invitedata'] ? json_decode($inviteset['invitedata'], true) : [];
                        $business = Db::name('business')->where('id', $order['bid'])->where('aid', aid)->field('maidaninviteset')->find();
                        if ($business && $business['maidaninviteset'] >= 0) {
                            //查询买单商家绑定的mid
                            $mid = Db::name('admin_user')->where('aid', aid)->where('bid', $order['bid'])->where('isadmin', '>', 0)->value('mid');
                            $pid = $mid && $mid > 0 ? $mid : 0;
                            if ($business['maidaninviteset'] == 1) {
                                $invitedata = [];
                                //查询商家单独设置
                                $inviteset2 = Db::name('maidan_invite')->where('bid', $order['bid'])->where('aid', aid)->where('status', 1)->find();
                                if ($inviteset2) {
                                    $invitedata = $inviteset2['invitedata'] ? json_decode($inviteset2['invitedata'], true) : [];
                                }
                            }
                            //如果用户邀请上级是商家绑定的mid
                            if ($pid > 0 && $member['pid'] == $pid && $invitedata) {
                                //查询用户注册天数
                                $regday = time() - $member['createtime'];
                                $validday = $inviteset['validday'] * 86400;
                                if ($regday <= $validday) {
                                    //查询用户是否提前消费其他项目
                                    $payorderid = Db::name('payorder')->where('orderid', $orderid)->where('type', 'maidan')->where('aid', aid)->value('id');
                                    if ($payorderid) {
                                        $count = Db::name('payorder')->where('id', '<>', $payorderid)->where('mid', $order['mid'])->where('status', 1)->where('aid', aid)->count('id');
                                        if (!$count) {
                                            //拉新奖励
                                            $invitemoney = 0;
                                            foreach ($invitedata as $invite) {
                                                if ($order['paymoney'] >= $invite['min'] && $invite['paymoney'] <= $invite['max']) {
                                                    $invitemoney = 0;
                                                    //返还比例
                                                    if ($invite['back'] && $invite['back'] > 0) {
                                                        $invitemoney += $order['paymoney'] * $invite['back'] * 0.01;
                                                    }
                                                    //返还固定金额
                                                    if ($invite['back2'] && $invite['back2'] > 0) {
                                                        $invitemoney += $invite['back2'];
                                                    }
                                                }
                                            }
                                            $invitemoney = $invitemoney ? round($invitemoney, 2) : 0;
                                            if ($invitemoney > 0) {
                                                $up = Db::name('maidan_order')->where('id', $order['id'])->update(['invitemoney' => $invitemoney]);
                                                if ($up) {
                                                    //给商家增加拉新奖励
                                                    \app\common\Business::addmoney(aid, $order['bid'], $invitemoney, '买单拉新奖励，订单号：' . $order['ordernum'], false, 'maidan', $order['ordernum']);
                                                    //20250618 增加拉新奖励记录
                                                    \app\common\Business::maidan_invite_log(aid,$order,$invitemoney);
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

            if (getcustom('yx_mangfan_maidan')) {
                //支付成功计算盲返列表
                if ($order['is_mangfan']) {
                    $order['ogid'] = $order['id'];
                    $order['real_totalprice'] = $order['paymoney'];
                    $order['type'] = 'maidan';
                    $order_goods[] = $order;
                    if ($order_goods) {
                        \app\custom\Mangfan::createRecord($aid, $order['mid'], $order['id'], $order['ordernum'], $order['paytime'], $order_goods, false, 0, 'maidan');
                    }
                    //发放盲返
                    \app\custom\Mangfan::sendBonus($order['aid'], $order['mid'], $order['id'], 'maidan');
                }
            }
            //发货信息录入 微信小程序+微信支付
            if ($order['platform'] == 'wx' && $order['paytypeid'] == 2) {
                \app\common\Order::wxShipping($order['aid'], $order, 'maidan');
            }

            if (getcustom('bonus_pool_gold')) {
                //思明定制奖金池
                \app\custom\BonusPoolGold::orderBonusPool($order['aid'], $order['mid'], $order['id'], 'maidan');
            }
            //公众号通知 订单支付成功
            $tmplcontent = [];
            $tmplcontent['first'] = '有新买单订单支付成功';
            $tmplcontent['remark'] = '点击进入查看~';
            $tmplcontent['keyword1'] = $member['nickname']; //用户名
            $tmplcontent['keyword2'] = $order['ordernum'];//订单号
            $tmplcontent['keyword3'] = $order['money'] . '元';//订单金额
            $tmplcontent['keyword4'] = $order['title'];//商品信息
            $tmplnew = Db::name('mp_tmplset_new')->where('aid', $aid)->find();
            if ($tmplnew['tmpl_maidanpay']) {
                $tmplcontentNew = [];
                $tmplcontentNew['thing13'] = \app\common\Mendian::getNameWithBusines($order);//门店
                $tmplcontentNew['thing12'] = $member['nickname']; //用户名
                $tmplcontentNew['character_string4'] = $order['ordernum'];//订单号
                $tmplcontentNew['amount15'] = $order['money'];//订单金额
                $tmplcontentNew['thing11'] = $order['title'];//商品信息
                //\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_maidanpay',$tmplcontent,m_url('adminExt/order/maidanlog', $aid),$order['mdid'],$tmplcontentNew);
                \app\common\Wechat::createSendTmplData($aid, $order['bid'], 0, 'tmpl_maidanpay', $tmplcontent, 'adminExt/order/maidanlog', $order['mdid'], $tmplcontentNew, 'sendhttmpl');
            } elseif ($tmplnew['tmpl_orderpay']) {
                $tmplcontentNew = [];
                $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
                $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
                $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
                $tmplcontentNew['amount5'] = $order['money'] == 0 ? '0.00' : $order['money'];//订单金额
                $tmplcontentNew['thing3'] = $order['title'];//商品信息
                //\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('adminExt/order/maidanlog', $aid),$order['mdid'],$tmplcontentNew);
                \app\common\Wechat::createSendTmplData($aid, $order['bid'], 0, 'tmpl_orderpay', $tmplcontent, 'adminExt/order/maidanlog', $order['mdid'], $tmplcontentNew, 'sendhttmpl');
            } else {
                // \app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('adminExt/order/maidanlog', $aid),$order['mdid']);
                \app\common\Wechat::createSendTmplData($aid, $order['bid'], 0, 'tmpl_orderpay', $tmplcontent, 'adminExt/order/maidanlog', $order['mdid'], '', 'sendhttmpl');
            }
            if (getcustom('yx_hongbao_queue_free')) {
                \app\custom\HongbaoQueueFree::join($order, 'maidan');
            }
            $tmplcontent = [];
            $tmplcontent['thing11'] = $order['title'];
            $tmplcontent['character_string2'] = $order['ordernum'];
            $tmplcontent['phrase10'] = '已支付';
            $tmplcontent['amount13'] = $order['money'] . '元';
            $tmplcontent['thing27'] = $member['nickname'];
            //\app\common\Wechat::sendhtwxtmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,'adminExt/order/maidanlog',$order['mdid']);
            \app\common\Wechat::createSendTmplData($aid, $order['bid'], 0, 'tmpl_orderpay', $tmplcontent, 'adminExt/order/maidanlog', $order['mdid'], '', 'sendhtwxtmpl');
        }
    }
	//餐饮订单合并支付
	public static function restaurant_takeaway_hb_pay($orderid,$ordernum){
		$orderlist = Db::name('restaurant_takeaway_order')->where('ordernum','like',$ordernum.'%')->select()->toArray();
		foreach($orderlist as $order){
			self::restaurant_takeaway_pay($order['id']);
		}
	}
	//餐饮订单
	public static function restaurant_takeaway_pay($orderid){
		$order = Db::name('restaurant_takeaway_order')->where('id',$orderid)->find();
		$member = Db::name('member')->where('id',$order['mid'])->find();
		$aid = $order['aid'];
		$mid = $order['mid'];
		Db::name('restaurant_takeaway_order_goods')->where('orderid',$orderid)->update(['status'=>1]);
		//真实销量和实际销量不含退款的 更新
        $goodslist =Db::name('restaurant_takeaway_order_goods')->where('aid',$aid)->where('orderid', $orderid)->select()->toArray();
        foreach($goodslist as $key=>$val){
            $num = $val['num'];
            Db::name('restaurant_product')->where('aid',$aid)->where('id',$val['proid'])->update(['real_sales'=>Db::raw("real_sales+$num"),'real_sales2'=>Db::raw("real_sales2+$num")]);
        }

		if(getcustom('member_level_moneypay_price')){
            //第一时间修改变动的价格
            self::changeRestaurantOrderPirce('restaurant_takeaway',$order,$goodslist,$member);
        }

		$takeaway_set = Db::name('restaurant_takeaway_sysset')->where('aid',$aid)->where('bid',$order['bid'])->find();
		if($takeaway_set['confirm_auto']==1){ //自动接单
			Db::name('restaurant_takeaway_order')->where('id',$orderid)->update(['status'=>12]);
			Db::name('restaurant_takeaway_order_goods')->where('orderid',$orderid)->update(['status'=>12]);
		}

		//支付后送券
		$couponlist = \app\common\Coupon::getpaygive($aid,$mid,'restaurant',$order['totalprice'],$order['id']);
		if($couponlist){
			foreach($couponlist as $coupon){
				\app\common\Coupon::send($aid,$mid,$coupon['id']);
			}
		}

		\app\common\Member::uplv($aid,$mid);
        \app\custom\Restaurant::print('restaurant_takeaway', $order,'','',1);//限制能自动打印的打印机

        if($takeaway_set['confirm_auto']==1){ //自动接单
            //判断是否自动派单
            if($order['freight_type'] == 2){
                $peisong_set = \db('peisong_set')->where('aid',$aid)->find();
                if($peisong_set['express_wx_status'] == 1 && $peisong_set['express_wx_paidan'] == 1){
                    Db::name('restaurant_takeaway_order')->where('id',$orderid)->update(['express_type'=>'express_wx']);
                    \app\custom\ExpressWx::addOrder('restaurant_takeaway_order',$order);
                }else{
                    //0 配送员抢单 1 指定配送员
                    if($peisong_set['paidantype'] == 0){
                        //外卖设置-自动发单 1 开启 0 关闭
                        if($takeaway_set['auto_send_order'] == 1){
                            \app\model\PeisongOrder::create('restaurant_takeaway_order', $order);
                        }
                    }

                }
            }
        }
        if(getcustom('yx_queue_free_restaurant_takeaway')){
            \app\custom\QueueFree::join($order,'restaurant_takeaway');
        }
        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping($order['aid'],$order,'restaurant_takeaway');
        }
        if(getcustom('yx_mangfan_restaurant_takeaway')) {
            //支付成功计算盲返列表
            $field = "id ogid, aid, mid, num, real_totalprice, totalprice, is_mangfan, mangfan_rate, mangfan_commission_type,mangfan_money";
            $order_goods = Db::name("restaurant_takeaway_order_goods")
                ->where("orderid", $orderid)
                ->where("is_mangfan", 1)
                ->field($field)
                ->select();
            foreach($order_goods as $key=>$og){
                $order_goods[$key]['type'] = 'restaurant_takeaway';
            }
            if($order_goods){
                \app\custom\Mangfan::createRecord($aid, $order['mid'], $order['id'], $order['ordernum'], $order['paytime'], $order_goods,false,0,'restaurant_takeaway');
            }
            //支付成功计算盲返列表，模式使用支付后发放时
            \app\custom\Mangfan::sendBonus($order['aid'],$order['mid'],$order['id'],'restaurant_takeaway');
        }
		//公众号通知 订单支付成功
		$tmplcontent = [];
		$tmplcontent['first'] = '有新订单支付成功';
		$tmplcontent['remark'] = '点击进入查看~';
		$tmplcontent['keyword1'] = $member['nickname']; //用户名
		$tmplcontent['keyword2'] = $order['ordernum'];//订单号
		$tmplcontent['keyword3'] = $order['totalprice'].'元';//订单金额
		$tmplcontent['keyword4'] = $order['title'];//商品信息
        $tmplcontentNew = [];
        $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
        $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
        $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
        $tmplcontentNew['amount5'] = $order['totalprice']==0?'0.00':$order['totalprice'];//订单金额
        $tmplcontentNew['thing3'] = $order['title'];//商品信息
		//\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('admin/restaurant/takeawayorder', $aid),$order['mdid'],$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/restaurant/takeawayorder',$order['mdid'],$tmplcontentNew,'sendhttmpl');
		$tmplcontent['first'] = '恭喜您的订单已支付成功';
		//$rs = \app\common\Wechat::sendtmpl($aid,$mid,'tmpl_orderpay',$tmplcontent,m_url('restaurant/takeaway/orderlist', $aid),$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,0,$mid,'tmpl_orderpay',$tmplcontent,'restaurant/takeaway/orderlist',0,$tmplcontentNew,'sendtmpl');

		$tmplcontent = [];
		$tmplcontent['thing11'] = $order['title'];
		$tmplcontent['character_string2'] = $order['ordernum'];
		$tmplcontent['phrase10'] = '已支付';
		$tmplcontent['amount13'] = $order['totalprice'].'元';
		$tmplcontent['thing27'] = $member['nickname'];
		//\app\common\Wechat::sendhtwxtmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,'admin/restaurant/takeawayorder',$order['mdid']);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/restaurant/takeawayorder',$order['mdid'],'','sendhtwxtmpl');

		//短信通知
		$rs = \app\common\Sms::send($aid,$member['tel']?$member['tel']:$order['tel'],'tmpl_orderpay',['ordernum'=>$order['ordernum']]);
        if(getcustom('yx_buyer_subsidy',$aid)){
            $res = \app\custom\Subsidy::caclOrder($aid,$orderid,0,'restaurant_takeaway');
        }
        if(getcustom('yx_new_score',$aid) && getcustom('yx_new_score_restaurant',$aid)){
            $res = \app\custom\NewScore::caclOrder($aid,$orderid,0,'restaurant_takeaway');
        }
	}
    //餐饮订单
    public static function restaurant_booking_pay($orderid){
        $order = Db::name('restaurant_booking_order')->where('id',$orderid)->find();
        $member = Db::name('member')->where('id',$order['mid'])->find();
        $aid = $order['aid'];
        $mid = $order['mid'];
        Db::name('restaurant_booking_order_goods')->where('orderid',$orderid)->update(['status'=>1]);

        //支付后送券
        $couponlist = \app\common\Coupon::getpaygive($aid,$mid,'restaurant',$order['totalprice'],$order['id']);
        if($couponlist){
            foreach($couponlist as $coupon){
                \app\common\Coupon::send($aid,$mid,$coupon['id']);
            }
        }

        \app\common\Member::uplv($aid,$mid);
        \app\custom\Restaurant::print('restaurant_booking', $order,'','',1);

        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping($order['aid'],$order,'restaurant_booking');
        }

        //公众号通知 订单支付成功
        $tmplcontent = [];
        $tmplcontent['first'] = '有新预定支付成功';
        $tmplcontent['remark'] = '点击进入查看~';
        $tmplcontent['keyword1'] = $member['nickname']; //用户名
        $tmplcontent['keyword2'] = $order['ordernum'];//订单号
        $tmplcontent['keyword3'] = $order['totalprice'].'元';//订单金额
        $tmplcontent['keyword4'] = $order['title'];//商品信息
        $tmplcontentNew = [];
        $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
        $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
        $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
        $tmplcontentNew['amount5'] = $order['totalprice']==0?'0.00':$order['totalprice'];//订单金额
        $tmplcontentNew['thing3'] = $order['title'];//商品信息
        //\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('admin/restaurant/bookingorder', $aid),$order['mdid'],$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/restaurant/bookingorder',$order['mdid'],$tmplcontentNew,'sendhttmpl');
        $tmplcontent['first'] = '恭喜您的预定订单已支付成功';
        //$rs = \app\common\Wechat::sendtmpl($aid,$mid,'tmpl_orderpay',$tmplcontent,m_url('restaurant/booking/orderlist', $aid),$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,0,$mid,'tmpl_orderpay',$tmplcontent,'restaurant/booking/orderlist',0,$tmplcontentNew,'sendtmpl');

		$tmplcontent = [];
		$tmplcontent['thing11'] = $order['title'];
		$tmplcontent['character_string2'] = $order['ordernum'];
		$tmplcontent['phrase10'] = '已支付';
		$tmplcontent['amount13'] = $order['totalprice'].'元';
		$tmplcontent['thing27'] = $member['nickname'];
		//\app\common\Wechat::sendhtwxtmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,'admin/restaurant/bookingorder',$order['mdid']);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/restaurant/bookingorder',$order['mdid'],'','sendhtwxtmpl');

        //短信通知
        $rs = \app\common\Sms::send($aid,$member['tel']?$member['tel']:$order['tel'],'tmpl_orderpay',['ordernum'=>$order['ordernum']]);
    }

    //餐饮订单
    public static function restaurant_shop_pay($orderid){
        $order = Db::name('restaurant_shop_order')->where('id',$orderid)->find();
        $member = Db::name('member')->where('id',$order['mid'])->find();
        $aid = $order['aid'];
        $bid = $order['bid'];
        $mid = $order['mid'];
        Db::name('restaurant_shop_order_goods')->where('orderid',$orderid)->update(['status'=>1]);
        //更新销量（包含退款） 和真实销量 不含退款
        $goodslist =Db::name('restaurant_shop_order_goods')->where('aid',$aid)->where('orderid', $orderid)->select()->toArray();
         foreach($goodslist as $key=>$val){
             $num = $val['num'];
             Db::name('restaurant_product')->where('aid',$aid)->where('id',$val['proid'])->update(['real_sales'=>Db::raw("real_sales+$num"),'real_sales2'=>Db::raw("real_sales2+$num")]);
             if(getcustom('restaurant_product_package')){
                 if($val['package_data']){
                     $package_data = json_decode($val['package_data'],true);
                     foreach($package_data as $pdk=>$pd){
                         $pdnum = $pd['num'];
                         Db::name('restaurant_product')->where('aid',$aid)->where('id',$pd['proid'])->update(['real_sales'=>Db::raw("real_sales+$pdnum"),'real_sales2'=>Db::raw("real_sales2+$pdnum")]);
                     }
                 }
             }
         }
        if(getcustom('member_level_moneypay_price')){
            //第一时间修改变动的价格
            self::changeRestaurantOrderPirce('restaurant_shop',$order,$goodslist,$member);
        }
       
         //判断该桌台
        //支付后送券
        $couponlist = \app\common\Coupon::getpaygive($aid,$mid,'restaurant',$order['totalprice'],$order['id']);
        if($couponlist){
            foreach($couponlist as $coupon){
                \app\common\Coupon::send($aid,$mid,$coupon['id']);
            }
        }

        \app\common\Member::uplv($aid,$mid);
        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping($order['aid'],$order,'restaurant_shop');
        }

        //根据餐后付款设置，开启时下单后打印小票，关闭时付款后打印小票
        $restaurant_shop_sysset = Db::name('restaurant_shop_sysset')->where('aid', $aid)->where('bid', $bid)->find();
        if($order['eattype'] == 0){
            \app\custom\Restaurant::print('restaurant_shop',$order,'','',1);
            
            //先付后吃的 清台后变为空闲
            if ($order['tableid'] && $order['eattype'] == 0 && $restaurant_shop_sysset['clean_table_status']) {
                Db::name('restaurant_table')->where('id', $order['tableid'])->where('aid', $aid)->where('bid', $bid)->update(['status' => 0, 'orderid' => 0]);
                //修改订单为已完成
//                Db::name('restaurant_shop_order')->where('aid', $aid)->where('id', $order['id'])->update(['status' => 3]);
                $rs = \app\custom\Restaurant::shop_orderconfirm($orderid); //执行确认收货发放佣金等
            }
        }
        
            
        if($order['eattype'] == 1){
            if(getcustom('restaurnt_table_afterpay_clean')){
                if($restaurant_shop_sysset['afterpay_clean']){
                    if($order['tableid']) {
                        Db::name('restaurant_table')->where('id',$order['tableid'])->where('aid',$aid)->where('bid',$bid)->update(['status' => 0, 'orderid' => 0]);
                        //修改订单为已完成
                        Db::name('restaurant_shop_order')->where('aid',$aid)->where('id',$order['id'])->update(['status' => 3]);
                    }
                }
            }
            if(getcustom('restaurnt_table_afterpay_print')){
                if($restaurant_shop_sysset['afterpay_print']){
                    \app\custom\Restaurant::print('restaurant_shop',$order,'',0,1);
                }
            }
            Db::name('restaurant_shop_order')->where('aid',$aid)->where('bid',$bid)->where('id',$orderid)->update(['settle_status' => 2]);
        }
      
        $extend_qrcode_variable_fenzhang =  getcustom('extend_qrcode_variable_fenzhang');
        if(getcustom('restaurant_table_after_pay_clean')){
            //每个桌台设置自动清理，付款后自动清理
            if($order['tableid']){
                $where  = [];
                $where[] = ['id','=',$order['tableid']];
                $where[] = ['aid','=',$aid];
                if(!$extend_qrcode_variable_fenzhang){
                    $where[] = ['bid','=',$bid];
                }
                $table = Db::name('restaurant_table')->where($where)->find();
                if($table['auto_clean'] ==1){
                    Db::name('restaurant_table')->where($where)->update(['status' => 0, 'orderid' => 0]);
                }
            }
        }
        if(getcustom('restaurant_take_food')){
            //发送取餐通知
            if($order['pickup_number']){
                \app\custom\Restaurant::addTakeFoodNumber($order);
//                if($order['bid'] == 0){
//                    $bname = Db::name('admin_set')->where('aid',$order['aid'])->value('name');
//                }else{
//                    $bname = Db::name('business')->where('id',$order['bid'])->value('name');
//                }
//                if(platform=='wx'){
//                    //订阅消息
//                    $tmplcontent = [];
//                    $tmplcontent['thing13'] = $bname;
//                    $tmplcontent['thing1'] = $order['pickup_number'];//取单号
//                    $tmplcontent['phrase8'] = '请取餐';//订单状态
//                    $tmplcontent['character_string12'] = $order['ordernum'];//订单编号
//                    $tmplcontentnew = [];
//                    \app\common\Wechat::sendwxtmpl($aid,$order['mid'],'tmpl_take_food',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
//                }
//                if(platform=='alipay'){
//                    $templatecontent= [$bname,$order['pickup_number'],$order['ordernum'],'待取餐'];
//                    \app\common\Alipay::sendTemplateMessage($order['aid'],$order['mid'],$templatecontent);
//                }
            }
        }
        if(getcustom('yx_queue_free_restaurant_shop')){
            \app\custom\QueueFree::join($order,'restaurant_shop');
        }
        if(getcustom('yx_mangfan_restaurant_shop')) {
            //支付成功计算盲返列表
            $field = "id ogid, aid, mid, num, real_totalprice, totalprice, is_mangfan, mangfan_rate, mangfan_commission_type,mangfan_money";
            $order_goods = Db::name("restaurant_shop_order_goods")
                ->where("orderid", $orderid)
                ->where("is_mangfan", 1)
                ->field($field)
                ->select();
            foreach($order_goods as $key=>$og){
                $order_goods[$key]['type'] = 'restaurant_shop';
            }
            if($order_goods){
                \app\custom\Mangfan::createRecord($aid, $order['mid'], $order['id'], $order['ordernum'], $order['paytime'], $order_goods,false,0,'restaurant_shop');
            }
            //支付成功计算盲返列表，模式使用支付后发放时
            \app\custom\Mangfan::sendBonus($order['aid'],$order['mid'],$order['id'],'restaurant_shop');
        }
        //公众号通知 订单支付成功
        $tmplcontent = [];
        if($order['paytypeid'] != 4) {
            $tmplcontent['first'] = '有新点餐订单支付成功';
        } else {
            $tmplcontent['first'] = '有新点餐订单下单成功（线下支付），';
        }
        $tmplcontent['remark'] = '点击进入查看~';
        $tmplcontent['keyword1'] = $member['nickname']; //用户名
        $tmplcontent['keyword2'] = $order['ordernum'];//订单号
        $tmplcontent['keyword3'] = $order['totalprice'].'元';//订单金额
        $tmplcontent['keyword4'] = $order['title'];//商品信息
        $tmplcontentNew = [];
        $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
        $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
        $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
        $tmplcontentNew['amount5'] = $order['totalprice']==0?'0.00':$order['totalprice'];//订单金额
        $tmplcontentNew['thing3'] = $order['title'];//商品信息
        //\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('admin/restaurant/shoporder', $aid),$order['mdid'],$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/restaurant/shoporder',$order['mdid'],$tmplcontentNew,'sendhttmpl');
        if($order['paytypeid'] != 4) {
            $tmplcontent['first'] = '恭喜您的点餐订单已支付成功';
        } else {
            $tmplcontent['first'] = '恭喜您的点餐订单下单成功';
        }
        //$rs = \app\common\Wechat::sendtmpl($aid,$mid,'tmpl_orderpay',$tmplcontent,m_url('restaurant/shop/orderlist', $aid),$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,0,$mid,'tmpl_orderpay',$tmplcontent,'restaurant/shop/orderlist',0,$tmplcontentNew,'sendtmpl');

		$tmplcontent = [];
		$tmplcontent['thing11'] = $order['title'];
		$tmplcontent['character_string2'] = $order['ordernum'];
		$tmplcontent['phrase10'] = '已支付';
		$tmplcontent['amount13'] = $order['totalprice'].'元';
		$tmplcontent['thing27'] = $member['nickname'];
		//\app\common\Wechat::sendhtwxtmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,'admin/restaurant/shoporder',$order['mdid']);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/restaurant/shoporder',$order['mdid'],'','sendhtwxtmpl');

        //短信通知
        $rs = \app\common\Sms::send($aid,$member['tel']?$member['tel']:$order['tel'],'tmpl_orderpay',['ordernum'=>$order['ordernum']]);
        if(getcustom('yx_buyer_subsidy',$aid)){
            $res = \app\custom\Subsidy::caclOrder($aid,$orderid,0,'restaurant_shop');
        }
        if(getcustom('yx_new_score',$aid) && getcustom('yx_new_score_restaurant',$aid)){
            $res = \app\custom\NewScore::caclOrder($aid,$orderid,0,'restaurant_shop');
        }
    }
	public static function plug_businessqr_pay_pay($orderid){
        $order = Db::name('plug_businessqr_pay_order')->where('id',$orderid)->find();
        $member = Db::name('member')->where('id',$order['mid'])->find();
        $aid = $order['aid'];
		if($order['bid']!=0){//入驻商家的货款
			$binfo = Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->find();
			$totalmoney = $order['cost_price'];
			\app\common\Business::addmoney($aid,$order['bid'],$totalmoney,'会员支付，订单号：'.$order['ordernum']);
			//店铺加销量
			Db::name('business')->where('aid',$aid)->where('id',$order['bid'])->inc('sales',1)->update();

			//公众号通知 订单支付成功
			$tmplcontent = [];
			$tmplcontent['first'] = '有新会员支付订单支付成功';
			$tmplcontent['remark'] = '点击进入查看~';
			$tmplcontent['keyword1'] = $member['nickname']; //用户名
			$tmplcontent['keyword2'] = $order['ordernum'];//订单号
			$tmplcontent['keyword3'] = $order['totalprice'].'元';//订单金额
			$tmplcontent['keyword4'] = $order['title'];//商品信息
            $tmplcontentNew = [];
            $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
            $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
            $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
            $tmplcontentNew['amount5'] = $order['totalprice']==0?'0.00':$order['totalprice'];//订单金额
            $tmplcontentNew['thing3'] = $order['title'];//商品信息
			//\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('admin/finance/bmoneylog', $aid),$tmplcontentNew);
            \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/finance/bmoneylog',0,$tmplcontentNew,'sendhttmpl');
			$tmplcontent['first'] = '恭喜您已支付成功';
			//$rs = \app\common\Wechat::sendtmpl($aid,$order['mid'],'tmpl_orderpay',$tmplcontent,m_url('pagesExt/money/moneylog?st=1', $aid),$tmplcontentNew);
            \app\common\Wechat::createSendTmplData($aid,0,$order['mid'],'tmpl_orderpay',$tmplcontent,'pagesExt/money/moneylog?st=1',0,$tmplcontentNew,'sendtmpl');

			$tmplcontent = [];
			$tmplcontent['thing11'] = $order['title'];
			$tmplcontent['character_string2'] = $order['ordernum'];
			$tmplcontent['phrase10'] = '已支付';
			$tmplcontent['amount13'] = $order['totalprice'].'元';
			$tmplcontent['thing27'] = $member['nickname'];
			//\app\common\Wechat::sendhtwxtmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,'admin/finance/bmoneylog');
            \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/finance/bmoneylog',0,'','sendhtwxtmpl');
		}
	}
	//预约服务支付
	public static function yuyue_pay($orderid){
        $order = Db::name('yuyue_order')->where('id',$orderid)->find();
        $product = Db::name('yuyue_product')->field('pdprehour,yynum')->where('id',$order['proid'])->find();
        //查看是否已经存在
        $yycount= Db::name('yuyue_order')->where('aid',$order['aid'])->where('yy_time',$order['yy_time'])->where('proid',$order['proid'])->where('mid','<>',$order['mid'])->where('status','in','1,2')->count();
        if($yycount>=$product['yynum']){
            Db::name('yuyue_order')->where('id',$orderid)->where('aid',$order['aid'])->where('bid',$order['bid'])->update(['status'=>4,'refund_money'=>$order['totalprice'],'refund_status'=>2]);
            //如果已经存在走退款
            $rs = \app\common\Order::refund($order,$order['totalprice'],'时间重复退款');
            //取消配送订单
            Db::name('yuyue_worker_order')->where('orderid',$orderid)->where('aid',$order['aid'])->where('bid',$order['bid'])->update(['status'=>-1]);
            //退款成功通知
            $tmplcontent = [];
            $tmplcontent['first'] = '您的订单已经退款，¥'.$order['totalprice'].'已经退回您的付款账户，请留意查收。';
            $tmplcontent['remark'] = '请点击查看详情~';
            $tmplcontent['orderProductPrice'] = (string) $order['totalprice'];
            $tmplcontent['orderProductName'] = $order['title'];
            $tmplcontent['orderName'] = $order['ordernum'];
            $tmplcontentNew = [];
            $tmplcontentNew['character_string1'] = $order['ordernum'];//订单编号
            $tmplcontentNew['thing2'] = $order['title'];//商品名称
            $tmplcontentNew['amount3'] = $order['totalprice'];//退款金额
            //\app\common\Wechat::sendtmpl(aid,$order['mid'],'tmpl_tuisuccess',$tmplcontent,m_url('pages/my/usercenter'),$tmplcontentNew);
            \app\common\Wechat::createSendTmplData($order['aid'],0,$order['mid'],'tmpl_tuisuccess',$tmplcontent,'pages/my/usercenter',0,$tmplcontentNew,'sendtmpl');
        }

        $aid = $order['aid'];
        $member = Db::name('member')->where('id',$order['mid'])->find();
        if(!$order) return json(['status'=>0,'msg'=>'订单不存在']);
        if($order['status']!=1 && $order['status']!=12) return json(['status'=>0,'msg'=>'订单状态不符合']);
        if(getcustom('hmy_yuyue') && in_array($order['paidan_type'],[2,3])){ //定制 将订单同步到师傅app端
            \app\custom\Yuyue::apiyuyue($order);
        }else{
            if($order['worker_id']){
                //如果用户已经选择服务人员则支付后直接进行派单
                if(empty($order['worker_orderid'])) {
                    $rs = \app\model\YuyueWorkerOrder::create($order, $order['worker_id'], '');
                    if ($rs['status'] == 0) return json($rs);
                    //\app\common\System::plog('预约派单'.$orderid);
                }else{
                    //更新提成
                    if(getcustom('yuyue_level_givemoney')){
                        $ticheng = \app\model\YuyueWorkerOrder::tichenglevel([],$order);
                    }else{
                        $ticheng = \app\model\YuyueWorkerOrder::ticheng([],$order);
                    }
                    

                    Db::name('yuyue_worker_order')->where('aid',$order['aid'])->where('id',$order['worker_orderid'])->update(['ticheng'=>$ticheng]);
                }
            }else{
                $yyset = Db::name('yuyue_set')->where('aid',$aid)->where('bid',$order['bid'])->find();

                $autopd_worker = false;
                if(getcustom('extend_yuyue_car')){
                    //如果是洗车订单
                    if($order['protype'] == 1){
                        $admin = Db::name('admin')->where('id',$aid)->field('yuyuecar_status')->find();
                        //如果有洗车权限且开启洗车订单推送最近师傅功能
                        if($admin && $admin['yuyuecar_status'] == 1 && $yyset['autopd_worker']== 1){
                            $autopd_worker = true;
                        }
                    }
                }

                if(!$autopd_worker){
                    //自动派单到大厅
                    if($yyset['paidantype']==0 && $yyset['isautopd']==1){
                        $rs = \app\model\YuyueWorkerOrder::create($order,0,'');
                    }
                }else{
                    if(getcustom('extend_yuyue_car')){
                        //下一个小时内的结束时间
                        $next_endtime = strtotime(date("Y-m-d H",$order['paytime']).':00:00')+2*60*60;

                        //转换预约时间
                        $yydate = explode('-',$order['yy_time']);
                        //开始时间
                        $begindate = $yydate[0];
                        if(strpos($begindate,'年') === false){
                            $begindate = date('Y').'年'.$begindate;
                        }
                        $begindate = preg_replace(['/年|月/','/日/'],['-',''],$begindate);
                        $begintime = strtotime($begindate);

                        //如果等于或超出结束时间
                        if($begintime>=$next_endtime){
                        //进入抢单大厅
                            $rs = \app\model\YuyueWorkerOrder::create($order,0,'');
                        }else{
                            //继续派单
                            $worker_id = \app\custom\YuyueCustom::get_worker($order);
                            if($worker_id){
                                \app\model\YuyueWorkerOrder::create($order,$worker_id,'');
                            }
                        }

                        //洗车订单派送给最近的服务人员
                        // $worker_id = \app\custom\YuyueCustom::get_worker($order);
                        // if($worker_id){
                        //     $rs = \app\model\YuyueWorkerOrder::create($order,$worker_id,'');
                        //     if($rs['status']==0) return json($rs);
                        // }
                    }
                }
            }
        }
        if(getcustom('yuyue_order_wifiprint')){
            $rs = \app\common\Wifiprint::print($aid,'yuyue',$orderid);
        }
        
        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping($order['aid'],$order,'yuyue');
        }

        //支付后送券
        $couponlist = \app\common\Coupon::getpaygive($aid,$order['mid'],'yuyue',$order['totalprice'],$order['id']);
        if($couponlist){
            foreach($couponlist as $coupon){
                if($coupon['buyyuyueprogive'] == 1){
                    $coupon['buyyuyueproids'] = explode(',',$coupon['buyyuyueproids']);
                    $coupon['buyyuyuepro_give_num'] = explode(',',$coupon['buyyuyuepro_give_num']);
                    foreach($coupon['buyyuyueproids'] as $k => $proid) {
                        if($proid == $order['proid'] && $coupon['buyyuyuepro_give_num'][$k] > 0) {
                            //give_num_type 赠送数量类型,0按设置数量,1按设置数量*购买数量
                            $couponGiveNum = $coupon['buyyuyuepro_give_num'][$k];
                            if($coupon['give_num_type'] == 1){
                                $buynum = $order['num'];
                                $couponGiveNum = $couponGiveNum * $buynum;
                            }
                            for($i=0;$i<$couponGiveNum;$i++) {
                                \app\common\Coupon::send($aid,$order['mid'],$coupon['id']);
                            }
                        }
                    }
                }
            }
        }

        //公众号通知 订单支付成功
        $tmplcontent = [];
        $tmplcontent['first'] = '有新预约订单支付成功';
        $tmplcontent['remark'] = '点击进入查看~';
        $tmplcontent['keyword1'] = $member['nickname']; //用户名
        $tmplcontent['keyword2'] = $order['ordernum'];//订单号
        $tmplcontent['keyword3'] = $order['totalprice'].'元';//订单金额
        $tmplcontent['keyword4'] = $order['title'];//商品信息
        $tmplcontentNew = [];
        $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
        $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
        $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
        $tmplcontentNew['amount5'] = $order['totalprice']==0?'0.00':$order['totalprice'];//订单金额
        $tmplcontentNew['thing3'] = $order['title'];//商品信息
        //\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('admin/order/yuyueorder', $aid),$order['mdid'],$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/yuyueorder',$order['mdid'],$tmplcontentNew,'sendhttmpl');
        $tmplcontent['first'] = '恭喜您的订单已支付成功';
        //$rs = \app\common\Wechat::sendtmpl($aid,$order['mid'],'tmpl_orderpay',$tmplcontent,m_url('yuyue/yuyue/orderlist', $aid),$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,0,$order['mid'],'tmpl_orderpay',$tmplcontent,'yuyue/yuyue/orderlist',0,$tmplcontentNew,'sendtmpl');

        $tmplcontent = [];
        $tmplcontent['thing11'] = $order['title'];
        $tmplcontent['character_string2'] = $order['ordernum'];
        $tmplcontent['phrase10'] = '已支付';
        $tmplcontent['amount13'] = $order['totalprice'].'元';
        $tmplcontent['thing27'] = $member['nickname'];
        //\app\common\Wechat::sendhtwxtmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,'admin/order/yuyueorder',$order['mdid']);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/yuyueorder',$order['mdid'],'','sendhtwxtmpl');

        //短信通知
        $rs = \app\common\Sms::send($aid,$member['tel']?$member['tel']:$order['tel'],'tmpl_orderpay',['ordernum'=>$order['ordernum']]);
	}


	//预约尾款支付
	public static function yuyue_balance_pay($orderid){
		//var_dump($orderid);
	    $order = Db::name('yuyue_order')->where('id',$orderid)->find();
		$aid = $order['aid'];
        $member = Db::name('member')->where('id',$order['mid'])->find();
		if(!$order) return json(['status'=>0,'msg'=>'订单不存在']);
		if($order['balance_pay_status']!=1) return json(['status'=>0,'msg'=>'订单状态不符合']);
		if(getcustom('hmy_yuyue') && $order['sysOrderNo']){ //将订单同步到师傅app端
			//直接完成订单
			db('yuyue_order')->where(['aid'=>$order['aid'],'id'=>$orderid])->update(['status'=>3,'collect_time'=>time()]);
			$rs = \app\common\Order::collect($order,'yuyue');
			Db::name('yuyue_worker_order')->where('id',$order['worker_orderid'])->update(['status'=>3,'endtime'=>time()]);
			\app\custom\Yuyue::payoff($order);
		}

        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping($order['aid'],$order,'yuyue');
        }
		//公众号通知 订单支付成功
		$tmplcontent = [];
		$tmplcontent['first'] = '尾款订单支付成功';
		$tmplcontent['remark'] = '点击进入查看~';
		$tmplcontent['keyword1'] = $member['nickname']; //用户名
		$tmplcontent['keyword2'] = $order['ordernum'];//订单号
		$tmplcontent['keyword3'] = $order['totalprice'].'元';//订单金额
		$tmplcontent['keyword4'] = $order['title'];//商品信息
        $tmplcontentNew = [];
        $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
        $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
        $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
        $tmplcontentNew['amount5'] = $order['totalprice']==0?'0.00':$order['totalprice'];//订单金额
        $tmplcontentNew['thing3'] = $order['title'];//商品信息
		//\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('admin/order/yuyueorder', $aid),$order['mdid'],$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/yuyueorder',$order['mdid'],$tmplcontentNew,'sendhttmpl');
		$tmplcontent['first'] = '恭喜您的订单已支付成功';
		//$rs = \app\common\Wechat::sendtmpl($aid,$order['mid'],'tmpl_orderpay',$tmplcontent,m_url('yuyue/yuyue/orderlist', $aid),$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,0,$order['mid'],'tmpl_orderpay',$tmplcontent,'yuyue/yuyue/orderlist',0,$tmplcontentNew,'sendtmpl');

		$tmplcontent = [];
		$tmplcontent['thing11'] = $order['title'];
		$tmplcontent['character_string2'] = $order['ordernum'];
		$tmplcontent['phrase10'] = '已支付';
		$tmplcontent['amount13'] = $order['totalprice'].'元';
		$tmplcontent['thing27'] = $member['nickname'];
		//\app\common\Wechat::sendhtwxtmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,'admin/order/yuyueorder',$order['mdid']);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/yuyueorder',$order['mdid'],'','sendhtwxtmpl');

		//短信通知
		$rs = \app\common\Sms::send($aid,$member['tel']?$member['tel']:$order['tel'],'tmpl_orderpay',['ordernum'=>$order['ordernum']]);
	}
    public function yuyue_workerapply_pay($orderid){
        $order = Db::name('yuyue_workerapply_order')->where('id',$orderid)->find();
        $info = Db::name('yuyue_worker')->where('id',$order['bid'])->find();
        //入驻成功给管理员发通知
        $tmplcontent = [];
        $tmplcontent['first'] = '有师傅申请成功';
        $tmplcontent['remark'] = '请登录后台，查看申请详情~';
        $tmplcontent['keyword1'] = '预约师傅申请';
        $tmplcontent['keyword2'] = date('Y-m-d H:i');
        $tempconNew = [];
        $tempconNew['thing3'] = '预约师傅申请';//报名名称
        $tempconNew['time5'] = date('Y-m-d H:i');//申请时间
        //\app\common\Wechat::sendhttmpl(aid,$info['bid'],'tmpl_formsub',$tmplcontent,'',0,$tempconNew);
        \app\common\Wechat::createSendTmplData($info['aid'],$info['bid'],0,'tmpl_formsub',$tmplcontent,'',0,$tempconNew,'sendhttmpl');
    }

    //预约补余款支付
    public static function yuyue_addmoney_pay($orderid){
        //var_dump($orderid);
        $order = Db::name('yuyue_order')->where('id',$orderid)->find();
        $aid = $order['aid'];
        $member = Db::name('member')->where('id',$order['mid'])->find();
        if(!$order) return json(['status'=>0,'msg'=>'订单不存在']);
        if($order['addmoneyStatus']!=1) return json(['status'=>0,'msg'=>'订单状态不符合']);

        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping($order['aid'],$order,'yuyue');
        }
        //公众号通知 订单支付成功
        /*$tmplcontent = [];
        $tmplcontent['first'] = '补余款订单支付成功';
        $tmplcontent['remark'] = '点击进入查看~';
        $tmplcontent['keyword1'] = $member['nickname']; //用户名
        $tmplcontent['keyword2'] = $order['ordernum'];//订单号
        $tmplcontent['keyword3'] = $order['addmoney'].'元';//订单金额
        $tmplcontent['keyword4'] = $order['title'];//商品信息
        \app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('admin/order/yuyueorder', $aid),$order['mdid']);
        $tmplcontent['first'] = '恭喜您的订单已支付成功';
        $rs = \app\common\Wechat::sendtmpl($aid,$order['mid'],'tmpl_orderpay',$tmplcontent,m_url('yuyue/yuyue/orderlist', $aid));

        $tmplcontent = [];
        $tmplcontent['thing11'] = $order['title'];
        $tmplcontent['character_string2'] = $order['ordernum'];
        $tmplcontent['phrase10'] = '已支付';
        $tmplcontent['amount13'] = $order['addmoney'].'元';
        $tmplcontent['thing27'] = $member['nickname'];
        \app\common\Wechat::sendhtwxtmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,'admin/order/yuyueorder',$order['mdid']);
        */
        //短信通知
        $rs = \app\common\Sms::send($aid,$member['tel']?$member['tel']:$order['tel'],'tmpl_orderpay',['ordernum'=>$order['ordernum']]);
    }

	//课程支付
	public static function kecheng_pay($orderid){
		//var_dump($orderid);
	    $order = Db::name('kecheng_order')->where('id',$orderid)->find();
        $aid = $order['aid'];
		//增加学习人数
		Db::name('kecheng_list')->where('aid',$order['aid'])->where('id',$order['kcid'])->inc('join_num')->update();
		if(!$order) return json(['status'=>0,'msg'=>'订单不存在']);
		if($order['status']!=1 && $order['status']!=12) return json(['status'=>0,'msg'=>'订单状态不符合']);
        $member = Db::name('member')->where('aid',$order['aid'])->where('id',$order['mid'])->find();

        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping($order['aid'],$order,'kecheng');
        }

        if(getcustom('kecheng_lecturer')){
            if($order['lecturerid']>0){
                //平台权限给讲师发放分佣
                $admin_user = Db::name('admin_user')->where('aid',$aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
                if($admin_user){
                    $cansend = true;
                    if($admin_user['auth_type'] !=1){
                        $admin_auth = !empty($admin_user['auth_data'])?json_decode($admin_user['auth_data'],true):[];
                        if($admin_user['groupid']){
                            $admin_auth = Db::name('admin_user_group')->where('id',$admin_user['groupid'])->value('auth_data');
                        }
                        if(!$admin_auth || !in_array('KechengLecturerList/index,KechengLecturerList/*',$admin_auth)){
                            $cansend = false;
                        }
                    }
                    if($cansend){
                        //查询关联的课程讲师
                        $lmember = Db::name('member')
                            ->alias('m')
                            ->join('kecheng_lecturer lecturer','lecturer.mid = m.id')
                            ->where('lecturer.id',$order['lecturerid'])
                            ->where('lecturer.checkstatus',1)
                            ->where('lecturer.commissionratio','>',0)
                            ->where('lecturer.mid','>',0)
                            ->field('m.id,lecturer.commissionratio')->find();
                        if($lmember){
                            $sendmoney = $order['totalprice'] * $lmember['commissionratio'] * 0.01;
                            $sendmoney = round($sendmoney,2);
                            $updata = [];
                            $updata['lecturer_mid'] = $lmember['id'];
                            $updata['lecturer_commissionratio'] = $lmember['commissionratio'];
                            if($sendmoney){
                                \app\common\Member::addmoney($order['aid'],$lmember['id'],$sendmoney,'用户购买课程：'.$order['title'].'，订单号'.$order['ordernum'].'增加');
                                $updata['lecturer_sendmoney'] = $sendmoney;
                            }
                            //更新发放分佣
                            Db::name('kecheng_order')->where('id',$orderid)->update($updata);
                        }
                    }
                }
            }
        }
		//公众号通知 订单支付成功
		$tmplcontent = [];
		$tmplcontent['first'] = '有新课程订单支付成功';
		$tmplcontent['remark'] = '点击进入查看~';
		$tmplcontent['keyword1'] = $member['nickname']; //用户名
		$tmplcontent['keyword2'] = $order['ordernum'];//订单号
		$tmplcontent['keyword3'] = $order['price'].'元';//订单金额
		$tmplcontent['keyword4'] = $order['title'];//商品信息
        $tmplcontentNew = [];
        $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
        $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
        $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
        $tmplcontentNew['amount5'] = $order['price']==0?'0.00':$order['price'];//订单金额
        $tmplcontentNew['thing3'] = $order['title'];//商品信息
		//\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('admin/order/kechengorder', $aid),$order['mdid'],$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/kechengorder',$order['mdid'],'','sendhttmpl');
		$tmplcontent['first'] = '恭喜您的订单已支付成功';
		//$rs = \app\common\Wechat::sendtmpl($aid,$order['mid'],'tmpl_orderpay',$tmplcontent,m_url('activity/kecheng/orderlist?bid='.$order['bid'], $aid),$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,0,$order['mid'],'tmpl_orderpay',$tmplcontent,'activity/kecheng/orderlist?bid='.$order['bid'],0,$tmplcontentNew,'sendtmpl');

		$tmplcontent = [];
		$tmplcontent['thing11'] = $order['title'];
		$tmplcontent['character_string2'] = $order['ordernum'];
		$tmplcontent['phrase10'] = '已支付';
		$tmplcontent['amount13'] = $order['totalprice'].'元';
		$tmplcontent['thing27'] = $member['nickname'];
		//\app\common\Wechat::sendhtwxtmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,'admin/order/kechengorder',$order['mdid']);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/kechengorder',$order['mdid'],'','sendhtwxtmpl');

		//短信通知
		$rs = \app\common\Sms::send($aid,$member['tel']?$member['tel']:$order['tel'],'tmpl_orderpay',['ordernum'=>$order['ordernum']]);
	    \app\common\Order::collect($order,'kecheng');
	}


	//团购
	public static function tuangou_pay($orderid){
		$order = Db::name('tuangou_order')->where('id',$orderid)->find();
		$member = Db::name('member')->where('id',$order['mid'])->find();
		$aid = $order['aid'];
		$mid = $order['mid'];
        //同城配送
        if($order['freight_type'] == 2){
            if(getcustom('express_maiyatian_autopush')) {
                //麦芽田同城配送自动推送
                \app\custom\MaiYaTianCustom::auto_push($aid,$orderid,$order,'tuangou_order');
            }
        }
		//自动发货
		if($order['freight_type']==3){
			Db::name('tuangou_order')->where('id',$order['id'])->update(['status'=>2,'send_time'=>time()]);

            //发货信息录入 微信小程序+微信支付
            if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                \app\common\Order::wxShipping($order['aid'],$order,'tuangou');
            }
		}
		//在线卡密
		if($order['freight_type']==4){
			$codelist = Db::name('tuangou_codelist')->where('proid',$order['proid'])->where('status',0)->order('id')->limit($order['num'])->select()->toArray();
			if($codelist && count($codelist) >= $order['num']){
				$pscontent = [];
				foreach($codelist as $codeinfo){
					$pscontent[] = $codeinfo['content'];
					Db::name('tuangou_codelist')->where('id',$codeinfo['id'])->update(['orderid'=>$order['id'],'ordernum'=>$order['ordernum'],'headimg'=>$member['headimg'],'nickname'=>$member['nickname'],'buytime'=>time(),'status'=>1]);
				}
				$pscontent = implode("\r\n",$pscontent);
				Db::name('tuangou_order')->where('id',$order['id'])->update(['freight_content'=>$pscontent,'status'=>2,'send_time'=>time()]);
			}

            //发货信息录入 微信小程序+微信支付
            if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                \app\common\Order::wxShipping($order['aid'],$order,'tuangou');
            }
		}
		//支付后送券
		$couponlist = \app\common\Coupon::getpaygive($aid,$mid,'tuangou',$order['totalprice']);
		if($couponlist){
			foreach($couponlist as $coupon){
				\app\common\Coupon::send($aid,$mid,$coupon['id']);
			}
		}
		\app\common\Wifiprint::print($aid,'tuangou',$order['id']);
		//公众号通知 订单支付成功
		$tmplcontent = [];
		$tmplcontent['first'] = '有新团购订单支付成功';
		$tmplcontent['remark'] = '点击进入查看~';
		$tmplcontent['keyword1'] = $member['nickname']; //用户名
		$tmplcontent['keyword2'] = $order['ordernum'];//订单号
		$tmplcontent['keyword3'] = $order['totalprice'].'元';//订单金额
		$tmplcontent['keyword4'] = $order['title'];//商品信息
        $tmplcontentNew = [];
        $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
        $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
        $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
        $tmplcontentNew['amount5'] = $order['totalprice']==0?'0.00':$order['totalprice'];//订单金额
        $tmplcontentNew['thing3'] = $order['title'];//商品信息
		//\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('admin/order/tuangouorder', $aid),$order['mdid'],$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/tuangouorder',$order['mdid'],'','sendhttmpl');
		$tmplcontent['first'] = '恭喜您的订单已支付成功';
		//$rs = \app\common\Wechat::sendtmpl($aid,$mid,'tmpl_orderpay',$tmplcontent,m_url('activity/tuangou/orderlist', $aid),$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,0,$mid,'tmpl_orderpay',$tmplcontent,'activity/tuangou/orderlist',0,$tmplcontentNew,'sendtmpl');

		$tmplcontent = [];
		$tmplcontent['thing11'] = $order['title'];
		$tmplcontent['character_string2'] = $order['ordernum'];
		$tmplcontent['phrase10'] = '已支付';
		$tmplcontent['amount13'] = $order['totalprice'].'元';
		$tmplcontent['thing27'] = $member['nickname'];
		//\app\common\Wechat::sendhtwxtmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,'admin/order/tuangouorder',$order['mdid']);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/tuangouorder',$order['mdid'],'','sendhtwxtmpl');

		//短信通知
		$rs = \app\common\Sms::send($aid,$member['tel']?$member['tel']:$order['tel'],'tmpl_orderpay',['ordernum'=>$order['ordernum']]);

		$set = Db::name('admin_set')->where('aid',$aid)->find();
		if($set['fxjiesuantime'] == 1 && $set['fxjiesuantime_delaydays'] == '0'){
			\app\common\Order::giveCommission($order,'tuangou');
		}
	}

	//约课服务支付
	public static function yueke_pay($orderid){
        $order = Db::name('yueke_order')->where('id',$orderid)->find();
        $aid = $order['aid'];
        $member = Db::name('member')->where('id',$order['mid'])->find();
        if(!$order) return json(['status'=>0,'msg'=>'订单不存在']);
        if($order['status']!=1 && $order['status']!=12) return json(['status'=>0,'msg'=>'订单状态不符合']);
        $workerinfo = Db::name('yueke_worker')->where('aid',$aid)->where('id',$order['workerid'])->find();

        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping($order['aid'],$order,'yueke');
        }

        //创建学习记录
        if(getcustom('yueke_extend') && $order['total_kecheng_num'] > 0){
            $workermid = Db::name('member')->where('id',$order['workerid'])->value('id')?:0;
            for ($count = 0;$count < $order['total_kecheng_num']; $count++) {
                $studyId = Db::name('yueke_study_record')->insertGetId([
                    'aid' => $order['aid'],
                    'mid' => $order['mid'],
                    'orderid' => $order['id'],
                    'ordernum' => $order['ordernum'],
                    'proid' => $order['proid'],
                    'name' => $order['proname'],
                    'workerid' => $order['workerid'],
                    'start_study_time' => '',
                    'parent1' => $order['parent1'],
                    'parent2' => $order['parent2'],
                    'parent3' => $order['parent3'],
                    'workermid' => $workermid,
                    'parent1commission' => $order['parent1commission'],
                    'parent2commission' => $order['parent2commission'],
                    'parent3commission' => $order['parent3commission'],
                    'workercommission' => $order['workercommission'],
                    'status' => 0,
                    'createtime' => time()
                ]);

                //增加佣金记录
                if($order['parent1'] && $order['parent1commission'] > 0){
                    //ogid: 学习记录id
                    Db::name('member_commission_record')->insert(['aid'=>aid,'mid'=>$order['parent1'],'frommid'=>$order['mid'],'orderid'=>$orderid,'ogid'=>$studyId,'type'=>'yueke','commission'=>$order['parent1commission'],'score'=>0,'remark'=>t('下级').'购买课程奖励','createtime'=>time()]);
                }

                if($order['parent2'] && $order['parent2commission'] > 0){
                    //ogid: 学习记录id
                    Db::name('member_commission_record')->insert(['aid'=>aid,'mid'=>$order['parent2'],'frommid'=>$order['mid'],'orderid'=>$orderid,'ogid'=>$studyId,'type'=>'yueke','commission'=>$order['parent2commission'],'score'=>0,'remark'=>t('下二级').'购买课程奖励','createtime'=>time()]);
                }

                if($order['parent3'] && $order['parent3commission'] > 0){
                    //ogid: 学习记录id
                    Db::name('member_commission_record')->insert(['aid'=>aid,'mid'=>$order['parent3'],'frommid'=>$order['mid'],'orderid'=>$orderid,'ogid'=>$studyId,'type'=>'yueke','commission'=>$order['parent3commission'],'score'=>0,'remark'=>t('下三级').'购买课程奖励','createtime'=>time()]);
                }
            }
        }

        //公众号通知 订单支付成功
        $tmplcontent = [];
        $tmplcontent['first'] = '有新预约订单支付成功';
        $tmplcontent['remark'] = '点击进入查看~';
        $tmplcontent['keyword1'] = $member['nickname']; //用户名
        $tmplcontent['keyword2'] = $order['ordernum'];//订单号
        $tmplcontent['keyword3'] = $order['totalprice'].'元';//订单金额
        $tmplcontent['keyword4'] = $order['title'];//商品信息
        $tmplcontentNew = [];
        $tmplcontentNew['thing8'] = \app\common\Mendian::getNameWithBusines($order);//门店
        $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
        $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
        $tmplcontentNew['amount5'] = $order['totalprice']==0?'0.00':$order['totalprice'];//订单金额
        $tmplcontentNew['thing3'] = $order['title'];//商品信息
        //\app\common\Wechat::sendhttmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,m_url('admin/order/yuekeorder', $aid),$order['mdid'],$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/yuekeorder',$order['mdid'],$tmplcontentNew,'sendhttmpl');
        if($workerinfo && $workerinfo['mid']){
            //$rs = \app\common\Wechat::sendtmpl($aid,$workerinfo['mid'],'tmpl_orderpay',$tmplcontent,m_url('pagesExt/yueke/workerorderlist', $aid),$tmplcontentNew);
            \app\common\Wechat::createSendTmplData($aid,0,$workerinfo['mid'],'tmpl_orderpay',$tmplcontent,'pagesExt/yueke/workerorderlist',0,$tmplcontentNew,'sendtmpl');
        }
        $tmplcontent['first'] = '恭喜您的订单已支付成功';
        //$rs = \app\common\Wechat::sendtmpl($aid,$order['mid'],'tmpl_orderpay',$tmplcontent,m_url('pagesExt/yueke/orderlist', $aid),$tmplcontentNew);
        \app\common\Wechat::createSendTmplData($aid,0,$order['mid'],'tmpl_orderpay',$tmplcontent,'pagesExt/yueke/orderlist',0,$tmplcontentNew,'sendtmpl');

        $tmplcontent = [];
        $tmplcontent['thing11'] = $order['title'];
        $tmplcontent['character_string2'] = $order['ordernum'];
        $tmplcontent['phrase10'] = '已支付';
        $tmplcontent['amount13'] = $order['totalprice'].'元';
        $tmplcontent['thing27'] = $member['nickname'];
        //\app\common\Wechat::sendhtwxtmpl($aid,$order['bid'],'tmpl_orderpay',$tmplcontent,'admin/order/yuekeorder',$order['mdid']);
        \app\common\Wechat::createSendTmplData($aid,$order['bid'],0,'tmpl_orderpay',$tmplcontent,'admin/order/yuekeorder',$order['mdid'],'','sendhtwxtmpl');

        //短信通知
        $rs = \app\common\Sms::send($aid,$member['tel']?$member['tel']:$order['tel'],'tmpl_orderpay',['ordernum'=>$order['ordernum']]);
	}

	public function business_recharge_pay($orderid){
		$order = Db::name('business_recharge_order')->where('id',$orderid)->find();
		$info = Db::name('business')->where('id',$order['bid'])->find();
		\app\common\Business::addmoney($order['aid'],$order['bid'],$order['money'],t('余额').'充值');

        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping($order['aid'],$order,'business_recharge');
        }
		//\app\common\System::plog('给商户充值'.$order['bid']);
	}
	//元宝 更新shop_order和payorder
    private static function yuanbao_up($type,$payorder){
        //元宝支付
        if(getcustom('pay_yuanbao') && $type == 'shop'){
            //如果是元宝支付，则需要重置支付金额
            if($payorder['is_yuanbao_pay'] == 1){
                //更新pay_order
                $up_pay = Db::name('payorder')->where('id',$payorder['id'])->update(['money'=>$payorder['yuanbao_money']]);

                //查询订单
                $order = Db::name('shop_order')->where('id',$payorder['orderid'])->field('aid,ordernum,mid,total_yuanbao')->find();
                if($order){
                    //更新shop_order
                    $up_order = Db::name('shop_order')->where('id',$payorder['orderid'])->update(['is_yuanbao_pay'=>1,'yuanbao_money'=>$payorder['yuanbao_money'],'totalprice'=>$payorder['yuanbao_money']]);
                    //更新商品id
                   	$sel_goods = Db::name('shop_order_goods')
                   		->where('orderid',$payorder['orderid'])
                   		->select()
                   		->toArray();
                   	if($sel_goods){
                   		//查询系统设置
                   		$sysset = Db::name('admin_set')->where('aid',$order['aid'])->find();
                   		//查询商城设置
                   		$shopset = Db::name('shop_sysset')->where('aid',$order['aid'])->field('showjd,comment,showcommission,hide_sales,hide_stock,show_lvupsavemoney')->find();
                   		//查询下单者
                   		$member  = Db::name('member')->where('id',$order['mid'])->find();
				        self::deal_commission($order['aid'],$order,$sel_goods,$sysset,$shopset,$member);
                   	}
                }
                //扣除元宝
                  \app\common\Member::addyuanbao($order['aid'],$order['mid'],-$order['total_yuanbao'],'支付订单：'.$order['ordernum']);
            }
        }
    }
    //元宝转账
    private static function member_yuanbao_transfer_pay($id){
        //元宝支付
        if(getcustom('pay_yuanbao')){
            //查询订单
            $order = Db::name('member_yuanbao_transfer_order')->where('id',$id)->field('aid,mid,to_mid,money,yuanbao,parent1,parent2,parent3,parent1commission,parent2commission,parent3commission,iscommission')->find();
            $aid = $order['aid'];
            if($order){
                $member     = Db::name('member')->where('id',$order['mid'])->field('nickname')->find();
                $to_member  = Db::name('member')->where('id',$order['to_mid'])->field('nickname')->find();
                if($member){
                    //直接转账
                    $rs = \app\common\Member::addyuanbao($aid,$order['to_mid'],$order['yuanbao'],sprintf("来自%s的".t('元宝')."转赠", $member["nickname"]));
                    if ($rs['status'] == 1) {
                        \app\common\Member::addyuanbao($aid,$order['mid'],$order['yuanbao'] * -1, sprintf(t('元宝')."转赠给：%s",$to_member['nickname']));
                    }
                }

                if($order['iscommission'] != 1){
                    $totalcommission = 0;
                    //发奖
                    if($order['parent1'] && $order['parent1commission'] > 0){
                        $totalcommission+=$order['parent1commission'];
                        \app\common\Member::addcommission($aid,$order['parent1'],$order['mid'],$order['parent1commission'],t('下级').''.t('元宝').'转账奖励');
                    }
                    if($order['parent2'] && $order['parent2commission'] > 0){
                        $totalcommission+=$order['parent2commission'];
                        \app\common\Member::addcommission($aid,$order['parent2'],$order['mid'],$order['parent2commission'],t('下二级').''.t('元宝').'转账奖励');
                    }
                    if($order['parent3'] && $order['parent3commission'] > 0){
                        $totalcommission+=$order['parent3commission'];
                        \app\common\Member::addcommission($aid,$order['parent3'],$order['mid'],$order['parent3commission'],t('下三级').''.t('元宝').'转账奖励');
                    }

                    //更新发佣金状态
                    $up = Db::name('member_yuanbao_transfer_order')->where('id',$id)->update(['iscommission'=>1]);
                }
            }
        }
    }
    //重新计算佣金
    private static function deal_commission($aid,$order,$sel_goods,$sysset,$shopset,$member){
        //元宝支付
        if(getcustom('pay_yuanbao')){
            if($sysset){
                $yuanbao_money_ratio = $sysset['yuanbao_money_ratio']/100;
            }else{
                $yuanbao_money_ratio  = 0;
            }
            $istc1 = $istc2 = $istc3 = 0;
            foreach($sel_goods as $ogdata){

                //计算商品元宝现金价格
                $yuanbao_money = $ogdata['total_yuanbao']*$yuanbao_money_ratio;
                $yuanbao_money = round($yuanbao_money,2);
                //更新商品金额
                $up_goods = Db::name('shop_order_goods')->where('id',$ogdata['id'])->update(['yuanbao_money'=>$yuanbao_money,'totalprice'=>$yuanbao_money,'real_totalprice'=>$yuanbao_money]);


                //删除之前的会员佣金记录
                Db::name('member_commission_record')->where('orderid',$ogdata['orderid'])->where('ogid',$ogdata['id'])->delete();

                //查询规格
                $guige = Db::name('shop_guige')->where('aid',$aid)->where('id',$ogdata['ggid'])->find();

                //实际支付价格
                $og_totalprice = $yuanbao_money;

                //数量
                $num = $ogdata['num'];
                //佣金总价格
                $commission_totalprice   = 0;

                if($sysset['fxjiesuantype']==1){ //按成交价格
					$commission_totalprice = $yuanbao_money;
					if($commission_totalprice < 0){
						$commission_totalprice = 0;
					}
				}
				if($sysset['fxjiesuantype']==2){ //按销售利润
					$commission_totalprice = $yuanbao_money - $guige['cost_price'] * $num;
					if($commission_totalprice < 0) {
						$commission_totalprice = 0;
					}
				}
                $commission_totalpriceCache = $commission_totalprice;

                $ogupdate = [];
                $ogupdate['parent1'] = 0;
            	$ogupdate['parent2'] = 0;
            	$ogupdate['parent3'] = 0;
            	$ogupdate['parent4'] = 0;

            	$ogupdate['parent1commission'] = 0;
            	$ogupdate['parent2commission'] = 0;
            	$ogupdate['parent3commission'] = 0;
            	$ogupdate['parent4commission'] = 0;

            	$ogupdate['parent1score'] = 0;
            	$ogupdate['parent2score'] = 0;
            	$ogupdate['parent3score'] = 0;

            	$ogupdate['hongbaoEdu'] = 0;
            	$ogupdate['business_total_money'] = 0;
            	//自己是否拿一级分成
                $agleveldata = Db::name('member_level')->where('aid',$aid)->where('id',$member['levelid'])->find();
                if($agleveldata['can_agent'] > 0 && $agleveldata['commission1own']==1){
                    $member['pid'] = $ogdata['mid'];
                }

                //查询商品
                $product = Db::name('shop_product')->where('id',$ogdata['proid'])->find();

                if($product['bid'] > 0) {

                	$store_info = Db::name('business')->where('aid',$aid)->where('id',$product['bid'])->find();

                    $totalprice_business = $og_totalprice;
                    //商品独立费率
                    if($product['feepercent'] != '' && $product['feepercent'] != null && $product['feepercent'] >= 0) {
                        $ogupdate['business_total_money'] = $totalprice_business * (100-$product['feepercent']) * 0.01;
                        if(getcustom('business_deduct_cost')){
                            if($store_info && $store_info['deduct_cost'] == 1){
                                if($ogdata['cost_price']<=$ogdata['sell_price']){
                                    $all_cost_price = $ogdata['cost_price'];
                                }else{
                                    $all_cost_price = $ogdata['sell_price'];
                                }
                                //扣除成本
                                $ogupdate['business_total_money'] = $totalprice_business - ($totalprice_business-$all_cost_price)*$product['feepercent']/100;
                            }
                        }
                    } else {
                        //商户费率
                        $ogupdate['business_total_money'] = $totalprice_business * (100-$store_info['feepercent']) * 0.01;
                        if(getcustom('business_deduct_cost')){
                            if($store_info && $store_info['deduct_cost'] == 1){
                                if($ogdata['cost_price']<=$ogdata['sell_price']){
                                    $all_cost_price = $ogdata['cost_price'];
                                }else{
                                    $all_cost_price = $ogdata['sell_price'];
                                }
                                //扣除成本
                                $ogupdate['business_total_money'] = $totalprice_business - ($totalprice_business-$all_cost_price)*$store_info['feepercent']/100;
                            }
                        }
                    }

                }

                if($product['commissionset']!=-1){

                    if($member['pid']){
                        $parent1 = Db::name('member')->where('aid',$aid)->where('id',$member['pid'])->find();
                        if($parent1){
                            $agleveldata1 = Db::name('member_level')->where('aid',$aid)->where('id',$parent1['levelid'])->find();
                            if($agleveldata1['can_agent']!=0){
                                $ogupdate['parent1'] = $parent1['id'];
                            }
                        }
                    }
                    if($parent1['pid']){
                        $parent2 = Db::name('member')->where('aid',$aid)->where('id',$parent1['pid'])->find();
                        if($parent2){
                            $agleveldata2 = Db::name('member_level')->where('aid',$aid)->where('id',$parent2['levelid'])->find();
                            if($agleveldata2['can_agent']>1){
                                $ogupdate['parent2'] = $parent2['id'];
                            }
                        }
                    }
                    if($parent2['pid']){
                        $parent3 = Db::name('member')->where('aid',$aid)->where('id',$parent2['pid'])->find();
                        if($parent3){
                            $agleveldata3 = Db::name('member_level')->where('aid',$aid)->where('id',$parent3['levelid'])->find();
                            if($agleveldata3['can_agent']>2){
                                $ogupdate['parent3'] = $parent3['id'];
                            }
                        }
                    }
                    if($parent3['pid']){
                        $parent4 = Db::name('member')->where('aid',$aid)->where('id',$parent3['pid'])->find();
                        if($parent4){
                            $agleveldata4 = Db::name('member_level')->where('aid',$aid)->where('id',$parent4['levelid'])->find();
                            //持续推荐奖励
                            if($agleveldata4['can_agent'] > 0 && ($agleveldata4['commission_parent'] > 0 || ($parent4['levelid']==$parent3['levelid'] && $agleveldata4['commission_parent_pj'] > 0))){
                                $ogupdate['parent4'] = $parent4['id'];
                            }
                        }
                    }
                    if($product['commissionset']==1){//按商品设置的分销比例
                        $commissiondata = json_decode($product['commissiondata1'],true);
                        if($commissiondata){
                            if($agleveldata1) $ogupdate['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * $commission_totalprice * 0.01;
                            if($agleveldata2) $ogupdate['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * $commission_totalprice * 0.01;
                            if($agleveldata3) $ogupdate['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * $commission_totalprice * 0.01;
                        }
                    }elseif($product['commissionset']==2){//按固定金额
                        $commissiondata = json_decode($product['commissiondata2'],true);
                        if($commissiondata){
                            if(getcustom('fengdanjiangli') && $product['fengdanjiangli']){

                            }else{
                                if($agleveldata1) $ogupdate['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * $num;
                                if($agleveldata2) $ogupdate['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * $num;
                                if($agleveldata3) $ogupdate['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * $num;
                            }
                        }
                    }elseif($product['commissionset']==3){//提成是积分
                        $commissiondata = json_decode($product['commissiondata3'],true);
                        if($commissiondata){
                            if($agleveldata1) $ogupdate['parent1score'] = $commissiondata[$agleveldata1['id']]['commission1'] * $num;
                            if($agleveldata2) $ogupdate['parent2score'] = $commissiondata[$agleveldata2['id']]['commission2'] * $num;
                            if($agleveldata3) $ogupdate['parent3score'] = $commissiondata[$agleveldata3['id']]['commission3'] * $num;
                        }
                    }else{ //按会员等级设置的分销比例
                        if($agleveldata1){
                            if(getcustom('commission_fugou') && $ogdata['isfg'] == 1){
                                $agleveldata1['commission1'] = $agleveldata1['commission4'];
                            }
                            if($agleveldata1['commissiontype']==1){ //固定金额按单
                                if($istc1==0){
                                    $ogupdate['parent1commission'] = $agleveldata1['commission1'];
                                    $istc1 = 1;
                                }
                            }else{
                                $ogupdate['parent1commission'] = $agleveldata1['commission1'] * $commission_totalprice * 0.01;
                            }
                        }
                        if($agleveldata2){
                            if(getcustom('commission_fugou') && $ogdata['isfg'] == 1){
                                $agleveldata2['commission2'] = $agleveldata2['commission5'];
                            }
                            if($agleveldata2['commissiontype']==1){
                                if($istc2==0){
                                    $ogupdate['parent2commission'] = $agleveldata2['commission2'];
                                    $istc2 = 1;
                                    //持续推荐奖励
                                    if($agleveldata2['commission_parent'] > 0) {
                                        $ogupdate['parent2commission'] = $ogupdate['parent2commission'] + $agleveldata2['commission_parent'];
                                    }
                                    if($agleveldata1['id'] == $agleveldata2['id'] && $agleveldata2['commission_parent_pj'] > 0) {
                                        $ogupdate['parent2commission'] = $ogupdate['parent2commission'] + $agleveldata2['commission_parent_pj'];
                                    }
                                }
                            }else{
                                $ogupdate['parent2commission'] = $agleveldata2['commission2'] * $commission_totalprice * 0.01;
                                //持续推荐奖励
                                if($agleveldata2['commission_parent'] > 0 && $ogupdate['parent1commission'] > 0) {
                                    $ogupdate['parent2commission'] = $ogupdate['parent2commission'] + $ogupdate['parent1commission'] * $agleveldata2['commission_parent'] * 0.01;
                                }
                                if($agleveldata1['id'] == $agleveldata2['id'] && $agleveldata2['commission_parent_pj'] > 0 && $ogupdate['parent1commission'] > 0) {
                                    $ogupdate['parent2commission'] = $ogupdate['parent2commission'] + $ogupdate['parent1commission'] * $agleveldata2['commission_parent_pj'] * 0.01;
                                }
                            }
                        }
                        if($agleveldata3){
                            if(getcustom('commission_fugou') && $ogdata['isfg'] == 1){
                                $agleveldata3['commission3'] = $agleveldata3['commission6'];
                            }
                            if($agleveldata3['commissiontype']==1){
                                if($istc3==0){
                                    $ogupdate['parent3commission'] = $agleveldata3['commission3'];
                                    $istc3 = 1;
                                    //持续推荐奖励
                                    if($agleveldata3['commission_parent'] > 0) {
                                        $ogupdate['parent3commission'] = $ogupdate['parent3commission'] + $agleveldata3['commission_parent'];
                                    }
                                    if($agleveldata2['id'] == $agleveldata3['id'] && $agleveldata3['commission_parent_pj'] > 0) {
                                        $ogupdate['parent3commission'] = $ogupdate['parent3commission'] + $agleveldata3['commission_parent_pj'];
                                    }
                                }
                            }else{
                                $ogupdate['parent3commission'] = $agleveldata3['commission3'] * $commission_totalprice * 0.01;
                                //持续推荐奖励
                                if($agleveldata3['commission_parent'] > 0 && $ogupdate['parent2commission'] > 0) {
                                    $ogupdate['parent3commission'] = $ogupdate['parent3commission'] + $ogupdate['parent2commission'] * $agleveldata3['commission_parent'] * 0.01;
                                }
                                if($agleveldata2['id'] == $agleveldata3['id'] && $agleveldata3['commission_parent_pj'] > 0 && $ogupdate['parent2commission'] > 0) {
                                    $ogupdate['parent3commission'] = $ogupdate['parent3commission'] + $ogupdate['parent2commission'] * $agleveldata3['commission_parent_pj'] * 0.01;
                                }
                            }
                        }
                        //持续推荐奖励
                        if($agleveldata4['commission_parent'] > 0) {
                            if($agleveldata3['commissiontype']==1){
                                $ogupdate['parent4commission'] = $agleveldata4['commission_parent'];
                            } else {
                                $ogupdate['parent4commission'] = $ogupdate['parent3commission'] * $agleveldata4['commission_parent'] * 0.01;
                            }
                        }
                        if($agleveldata3['id'] == $agleveldata4['id'] && $agleveldata4['commission_parent_pj'] > 0) {
                            if($agleveldata3['commissiontype']==1){
                                $ogupdate['parent4commission'] = $agleveldata4['commission_parent_pj'];
                            } else {
                                $ogupdate['parent4commission'] = $ogupdate['parent3commission'] * $agleveldata4['commission_parent_pj'] * 0.01;
                            }
                        }
                    }
                }
                if($ogupdate){
                    Db::name('shop_order_goods')->where('id',$ogdata['id'])->update($ogupdate);
                }

                if($product['commissionset4']==1 && $product['lvprice']==1){ //极差分销
                    if($member['path']){
                        $parentList = Db::name('member')->where('id','in',$member['path'])->order(Db::raw('field(id,'.$member['path'].')'))->select()->toArray();
                        if($parentList){
                            $parentList   = array_reverse($parentList);
                            $lvprice_data = json_decode($guige['lvprice_data'],true);
                            $nowprice     = $commission_totalpriceCache;
                            $giveidx      = 0;
                            foreach($parentList as $k=>$parent){
                                if($parent['levelid'] && $lvprice_data[$parent['levelid']]){
                                    $thisprice = floatval($lvprice_data[$parent['levelid']]) * $num;
                                    if($nowprice > $thisprice){
                                        $commission = $nowprice - $thisprice;
                                        $nowprice = $thisprice;
                                        $giveidx++;
                                        //添加新的
                                        Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$parent['id'],'frommid'=>$ogdata['mid'],'orderid'=>$ogdata['orderid'],'ogid'=>$ogdata['id'],'type'=>'shop','commission'=>$commission,'score'=>0,'remark'=>t('下级').'购买商品差价','createtime'=>time()]);
                                    }
                                }
                            }
                        }
                    }
                }

                if($product['commissionset']!=4){
                    if(getcustom('commission_fugou') && $ogdata['isfg'] == 1){
                        if($ogupdate['parent1'] && ($ogupdate['parent1commission'] || $ogupdate['parent1score'])){
                            Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$ogupdate['parent1'],'frommid'=>$ogdata['mid'],'orderid'=>$ogdata['orderid'],'ogid'=>$ogdata['id'],'type'=>'shop','commission'=>$ogupdate['parent1commission'],'score'=>$ogupdate['parent1score'],'remark'=>t('下级').'复购奖励','createtime'=>time()]);
                        }
                        if($ogupdate['parent2'] && ($ogupdate['parent2commission'] || $ogupdate['parent2score'])){
                            Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$ogupdate['parent2'],'frommid'=>$ogdata['mid'],'orderid'=>$ogdata['orderid'],'ogid'=>$ogdata['id'],'type'=>'shop','commission'=>$ogupdate['parent2commission'],'score'=>$ogupdate['parent2score'],'remark'=>t('下二级').'复购奖励','createtime'=>time()]);
                        }
                        if($ogupdate['parent3'] && ($ogupdate['parent3commission'] || $ogupdate['parent3score'])){
                            Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$ogupdate['parent3'],'frommid'=>$ogdata['mid'],'orderid'=>$ogdata['orderid'],'ogid'=>$ogdata['id'],'type'=>'shop','commission'=>$ogupdate['parent3commission'],'score'=>$ogupdate['parent3score'],'remark'=>t('下三级').'复购奖励','createtime'=>time()]);
                        }
                    }else{
                        if($ogupdate['parent1'] && ($ogupdate['parent1commission'] || $ogupdate['parent1score'])){
                            Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$ogupdate['parent1'],'frommid'=>$ogdata['mid'],'orderid'=>$ogdata['orderid'],'ogid'=>$ogdata['id'],'type'=>'shop','commission'=>$ogupdate['parent1commission'],'score'=>$ogupdate['parent1score'],'remark'=>t('下级').'购买商品奖励','createtime'=>time()]);
                        }
                        if($ogupdate['parent2'] && ($ogupdate['parent2commission'] || $ogupdate['parent2score'])){
                            Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$ogupdate['parent2'],'frommid'=>$ogdata['mid'],'orderid'=>$ogdata['orderid'],'ogid'=>$ogdata['id'],'type'=>'shop','commission'=>$ogupdate['parent2commission'],'score'=>$ogupdate['parent2score'],'remark'=>t('下二级').'购买商品奖励','createtime'=>time()]);
                        }
                        if($ogupdate['parent3'] && ($ogupdate['parent3commission'] || $ogupdate['parent3score'])){
                            Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$ogupdate['parent3'],'frommid'=>$ogdata['mid'],'orderid'=>$ogdata['orderid'],'ogid'=>$ogdata['id'],'type'=>'shop','commission'=>$ogupdate['parent3commission'],'score'=>$ogupdate['parent3score'],'remark'=>t('下三级').'购买商品奖励','createtime'=>time()]);
                        }
                        if($ogupdate['parent4'] && ($ogupdate['parent4commission'])){
                            Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$ogupdate['parent4'],'frommid'=>$ogdata['mid'],'orderid'=>$ogdata['orderid'],'ogid'=>$ogdata['id'],'type'=>'shop','commission'=>$ogupdate['parent4commission'],'score'=>0,'remark'=>'持续推荐奖励','createtime'=>time()]);
                        }
                    }
                    if($order['checkmemid'] && $commission_totalprice > 0){
                        $checkmember = Db::name('member')->where('aid',$aid)->where('id',$order['checkmemid'])->find();
                        if($checkmember){
                            $buyselect_commission = Db::name('member_level')->where('id',$checkmember['levelid'])->value('buyselect_commission');
                            $checkmemcommission = $buyselect_commission * $commission_totalprice * 0.01;
                            Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$checkmember['id'],'frommid'=>$ogdata['mid'],'orderid'=>$ogdata['orderid'],'ogid'=>$ogdata['id'],'type'=>'shop','commission'=>$checkmemcommission,'score'=>0,'remark'=>'购买商品时指定奖励','createtime'=>time()]);
                        }
                    }
                }

                if(getcustom('everyday_hongbao')) {

                    $hd = Db::name('hongbao_everyday')->where('aid', $aid)->find();
                    $hongbaoEdu = 0;
                    if($product['everyday_hongbao_bl'] === null) {
                        $hongbaoEdu = $og_totalprice * $hd['shop_product_hongbao_bl'] / 100;
                    } elseif($product['everyday_hongbao_bl'] > 0 ) {
                        $hongbaoEdu = $og_totalprice * $product['everyday_hongbao_bl'] / 100;
                    }
                    $hongbaoEdu = round($hongbaoEdu,2);
                    if($hongbaoEdu > 0){
                        Db::name('shop_order_goods')->where('id',$ogupdate['id'])->update(['hongbaoEdu' => $hongbaoEdu]);
                    }
                }
            }
            unset($sg_v);
        }

    }
    public static function cashier_pay($orderid){
//	    //如果是多商家且余额支付，则结算商家费用
//        $order = Db::name('cashier_order')->where('id',$orderid)->find();
//        $totalmoney = $order['totalprice'];
//        if($order['paytypeid']==1 && $order['bid']>0 && $totalmoney>0){
//            //商家费率
//            $feepercent = Db::name('business')->where('aid',$order['aid'])->where('id',$order['bid'])->value('feepercent');
//            if($feepercent){
//                $totalmoney = $totalmoney * (100-$feepercent) * 0.01;
//            }
//            //结算给商家
//            $res = \app\common\Business::addmoney($order['aid'],$order['bid'],$totalmoney,'收银台收款');
//            if(!$res || $res['status']!=1){
//                \think\facade\Log::write('--------------商家结算失败--------------');
//                \think\facade\Log::write(json_encode($res));
//                \think\facade\Log::write('--------------商家结算失败--------------');
//            }
//        }
	    return true;
    }
    public static function dscj_pay($orderid){
        $order = Db::name('dscj_order')->where('id',$orderid)->find();
        //其他订单关闭
        $where = [];
        $where[] = ['status','=',0];
        $where[] = ['bid','=',$order['bid']];
        $where[] = ['aid','=',$order['aid']];
        $where[] = ['hid','=',$order['hid']];
        $where[] = ['mid','=',$order['mid']];
        Db::name('dscj_order')->where($where)->update(['status'=>4]);
        Db::name('dscj')->where('id',$order['hid'])->inc('joinnum',1)->update();
        return true;
    }
    //招聘置顶
    public static function zhaopin_top_pay($orderid){
	    if(getcustom('zhaopin')){
            $order = Db::name('zhaopin_top_order')->where('id',$orderid)->find();
            $zhaopinupdate  = [];
            if($order['status']==1 && $order['related_id']){
                $starttime = $order['paytime'];
                $endtimeS = $order['paytime'];
                $duration = $order['top_duration'];
                $durationTotal = 86400 * $duration;
                $relateinfo = Db::name('zhaopin')->where('aid',$order['aid'])->where('id',$order['related_id'])->find();
                if($relateinfo){
                    if($relateinfo['top_endtime'] && $relateinfo['top_endtime']>time()){
                        $starttime = $relateinfo['top_starttime'];
                        $endtimeS = $relateinfo['top_endtime'];
                    }
                    $zhaopinupdate['top_starttime'] = $starttime;
                    $zhaopinupdate['top_endtime'] = $endtimeS + $durationTotal;
                    $zhaopinupdate['top_feetype'] = $order['top_feetype'];
                    $zhaopinupdate['top_area'] = $order['top_area'];
//                    Db::name('zhaopin')->where('id',$order['related_id'])->update($zhaopinupdate);
                }
            }
            if($order['assurance_total']>0){
                //同步保证金订单
                $ordera = [];
                $ordera['ordernum'] = $order['ordernum'];
                $ordera['top_orderid'] = $order['id'];
                $ordera['totalprice'] = $order['assurance_total'];
                $ordera['createtime'] = time();
                $ordera['title'] = '担保招聘保证金';
                $ordera['aid'] = $order['aid'];
                $ordera['bid'] = $order['bid'];
                $ordera['mid'] = $order['mid'];
                $ordera['apply_id'] = $order['apply_id'];
                $ordera['status'] = 1;
                $ordera['paynum'] = $order['paynum'];
                $ordera['paytype'] = $order['paytype'];
                $ordera['paytime'] = $order['paytime'];
                Db::name('zhaopin_assurancefee_order')->insertGetId($ordera);

                //保证金累加
                Db::name('zhaopin_apply')->where('aid',$order['aid'])->where('id',$order['apply_id'])->update(['assurance_fee'=>Db::raw("assurance_fee+{$order['assurance_total']}")]);

                $data = [];
                $data['createtime'] = time();
                $data['aid'] = $order['aid'];
                $data['bid'] = $order['bid'];
                $data['mid'] = $order['mid'];
                $data['apply_id'] = $order['apply_id'];
                $data['zhaopin_id'] = $order['related_id'];
                $data['status'] = 1;//担保中
                $data['fee'] =  $order['assurance_total'];
                $data['remark'] =  '置顶担保招聘';
                $assurance_id = Db::name('zhaopin_assurance')->insertGetId($data);
                $zhaopinupdate['assurance_id'] = $assurance_id;
            }
            if($order['totalprice']>0){
                //发放奖励
                $givescore = \app\model\Zhaopin::getSetValue($order['aid'],'zhaopin','top_give_score',0);
                if($givescore>0){
                    \app\common\Member::addscore($order['aid'],$order['mid'],$givescore,'招聘置顶奖励');
                }
            }
            if($zhaopinupdate){
                Db::name('zhaopin')->where('id',$order['related_id'])->update($zhaopinupdate);
            }
        }
        return true;
    }

    //vip
    public static function zhaopin_vip_pay($orderid){
        if(getcustom('zhaopin')){
            //其他未支付的订单删除
            $order = Db::name('zhaopin_vip_order')->where('id',$orderid)->find();
            Db::name('zhaopin_vip_order')->where('status',0)->where('mid',$order['mid'])->where('zhaopin_id',$order['zhaopin_id'])->delete();
            Db::name('zhaopin')->where('aid',$order['aid'])->where('id',$order['zhaopin_id'])->update(['vip_orderid'=>$orderid]);
            //vip_order
            Db::name('zhaopin_apply')->where('aid',$order['aid'])->where('mid',$order['mid'])->update(['vip_orderid'=>$orderid,'zhaopin_id'=>$order['zhaopin_id']]);
            return;
        }
        return true;
    }

    //求职置顶
    public static function zhaopin_qiuzhi_top_pay($orderid){
        if(getcustom('zhaopin')) {
            $order = Db::name('zhaopin_qiuzhi_top_order')->where('id', $orderid)->find();
            if ($order['status'] == 1 && $order['related_id']) {
                $starttime = $order['paytime'];
                $endtimeS = $order['paytime'];
                $duration = $order['top_duration'];
                $durationTotal = 86400 * ($duration + 1);
                $relateinfo = Db::name('zhaopin_qiuzhi')->where('aid', $order['aid'])->where('id', $order['related_id'])->find();
                if ($relateinfo) {
                    if ($relateinfo['top_endtime'] && $relateinfo['top_endtime']>time()) {
                        $starttime = $relateinfo['top_starttime'];
                        $endtimeS = $relateinfo['top_endtime'];
                    }
                    $update['top_starttime'] = $starttime;
                    $update['top_endtime'] = $endtimeS + $durationTotal;
                    $update['top_feetype'] = $order['top_feetype'];
                    $update['top_area'] = $order['top_area'];
                    Db::name('zhaopin_qiuzhi')->where('id', $order['related_id'])->update($update);
                }
            }
            //发放奖励
            $givescore = \app\model\Zhaopin::getSetValue($order['aid'],'qiuzhi','top_give_score',0);
            if($givescore>0){
                \app\common\Member::addscore($order['aid'],$order['mid'],$givescore,'求职置顶奖励');
            }
        }
        return true;
    }
    //招聘置顶
    public static function zhaopin_assurancefee_pay($orderid){
        if(getcustom('zhaopin')){
            $order = Db::name('zhaopin_assurancefee_order')->where('id',$orderid)->find();
            if($order['apply_id']){
                //更新商家缴纳保证金的费用
                Db::name('zhaopin_apply')->where('aid',$order['aid'])->where('id',$order['apply_id'])->inc('assurance_fee',$order['totalprice'])->update();
            }
        }
        return true;
    }

    /**
     * @param $orderid
     * @param $type
     * @param $usecoupon_type 1付款后，2确认收货
     * @param $ordernum
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function afterusecoupon($orderid,$type,$usecoupon_type=1,$ordernum='')
    {
        //所有使用优惠券的订单类型maidan = couponrid
        if(getcustom('usecoupon_give_score') || getcustom('usecoupon_give_coupon') || getcustom('usecoupon_give_money')){
            if(in_array($type,[
                'shop','shop_hb','yuyue','tuangou','seckill2','seckill',
                'restaurant_takeaway','restaurant_shop','restaurant_booking',
                'maidan','lucky_collage','dscj','collage','cashier'
            ])){
                $couponOrders = [];//优惠券字段 全部转为coupon_rid
                //特殊处理的表
                if($type=='shop_hb'){
                    $couponOrders = Db::name('shop_order')->where('ordernum','like',$ordernum.'%')->select()->toArray();
                }elseif($type=='maidan'){
                    $couponOrders[] = Db::name('maidan_order')->where('id',$orderid)->field('*,couponrid coupon_rid')->find();
                }else{
                    $couponOrders[] = Db::name($type.'_order')->where('id',$orderid)->find();
                }
                if(empty($couponOrders)){
                    return true;
                }
                foreach ($couponOrders as $key=>$order){
                    if(!isset($order['coupon_rid']) || empty($order['coupon_rid'])){
                        continue;
                    }
                    \app\common\Coupon::useCoupon($order['aid'],$order['coupon_rid'],$usecoupon_type);
                }
            }
        }

        return true;
    }

    public static function send_free_notice($payorder){
    	if(getcustom('invite_free')){
    		//查询免单设置
            $set = Db::name('invite_free')->where('aid',$payorder['aid'])->find();
    		if($set && $set['status'] ==1 && $set['start_time']<=time() && $set['end_time']>=time() && $payorder['aid'] && $payorder['aid'] == 10){
    			//查询订单
		        $order = Db::name('shop_order')->where('id',$payorder['orderid'])->field('id,ordernum,mid,title,totalprice,mdid')->find();
		        if($order){
		        	//查询他下单次数
		        	$count_num = Db::name('shop_order')
		        		->where('mid',$order['mid'])
		        		->where('status','>=',1)
		        		->where('status','<=',3)
		        		->count();
		        	if($count_num == 1){
		        		//查询他上级
		        		$member = Db::name('member')
		        			->where('id',$order['mid'])
		        			->field('pid')
		        			->find();
		        		if($member && $member['pid']>0){

							// $tmplcontent['keyword1'] = Db::name('admin_set')->where('aid',aid)->value('name'); //店铺
							// $tmplcontent['thing27'] = $this->member['nickname'];

		        			//查询通知设置
				        	$mp_tmplset = Db::name('mp_tmplset')->where('aid',$payorder['aid'])->field('tmpl_orderconfirm')->find();
				        	if($mp_tmplset && $mp_tmplset['tmpl_orderconfirm']){
				        		//公众号通知 发送有新伙伴下单
								$tmplcontent = [];
								$tmplcontent['first']    = '有新伙伴下单';
								$tmplcontent['remark']   = '点击进入查看~';
								$tmplcontent['keyword1'] = ''; //店铺
								$tmplcontent['keyword2'] = date('Y-m-d H:i:s',$payorder['paytime']);//下单时间
								$tmplcontent['keyword3'] = $order['title']?$order['title']:'';//商品
								$tmplcontent['keyword4'] = $order['totalprice']?$order['totalprice']:'';//金额
                                $tempconNew = [];
                                $tempconNew['character_string2'] = $order['ordernum'];//订单号
                                $tempconNew['thing8'] = '';//门店名称
                                $tempconNew['thing3'] = $order['title']?$order['title']:'';//商品
                                $tempconNew['amount7'] = $order['totalprice']?$order['totalprice']:'';//金额
                                $tempconNew['time4'] = date('Y-m-d H:i:s',$payorder['paytime']);//下单时间
								//\app\common\Wechat::sendtmpl($payorder['aid'],$member['pid'],'tmpl_orderconfirm',$tmplcontent,m_url('pagesExt/invite_free/index'),$tempconNew);
                                \app\common\Wechat::createSendTmplData($payorder['aid'],0,$member['pid'],'tmpl_orderconfirm',$tmplcontent,'pagesExt/invite_free/index',0,$tempconNew,'sendtmpl');
				        	}

				        	//查询通知设置
				        	$wx_tmplset = Db::name('wx_tmplset')->where('aid',$payorder['aid'])->field('tmpl_orderconfirm')->find();
				        	if($wx_tmplset && $wx_tmplset['tmpl_orderconfirm']){
								$tmplcontent = [];
								$tmplcontent['thing11']  = $order['title']?$order['title']:'';//商品
								$tmplcontent['character_string2'] = $order['ordernum']?$order['ordernum']:'';
								$tmplcontent['phrase10'] = '新伙伴下单';
								$tmplcontent['amount13'] =  $order['totalprice']?$order['totalprice']:'';//金额
								$tmplcontent['thing27']  = '';
								//\app\common\Wechat::sendwxtmpl($payorder['aid'],$member['pid'],'tmpl_orderconfirm',$tmplcontent,m_url('pagesExt/invite_free/index'),$order['mdid']);
                                \app\common\Wechat::createSendTmplData($payorder['aid'],0,$member['pid'],'tmpl_orderconfirm',$tmplcontent,'pagesExt/invite_free/index',$order['mdid'],'','sendwxtmpl');
							}
		        		}
		        	}
		        }
    		}

	    }
    }

    public static function xixie_pay($orderid){
        if(getcustom('xixie')){
            $order = Db::name('shop_order')->where('id',$orderid)->find();
            $member = Db::name('member')->where('id',$order['mid'])->find();
            $aid = $order['aid'];
            $mid = $order['mid'];
            Db::name('shop_order_goods')->where('orderid',$orderid)->update(['status'=>1]);
        }
    }

    //充值订单
    public static function xixie_vip_pay($orderid){
        if(getcustom('xixie')){
            $order = Db::name('xixie_vip_order')->where('id',$orderid)->find();
            $member = Db::name('member')->where('id',$order['mid'])->update(['is_vip'=>1]);
        }
    }

    public static function article_reward_pay($orderid){
        if(getcustom('article_reward')){
            $order = Db::name('article_reward_order')->where('id',$orderid)->find();
            if($order){
                //增加打赏
                \app\common\Member::addmoney($order['aid'],$order['send_mid'],$order['num'],'文章打赏');
            }
        }
    }
	//支付后赠送活动
	public static function payaftergive($payorder){
		$aid = $payorder['aid'];
		$mid = $payorder['mid'];
		$bid = $payorder['bid'];
		$member = Db::name('member')->where('id',$payorder['mid'])->find();
		$payordertype = $payorder['type'];
		if($payordertype == 'shop_hb') $payordertype = 'shop';
		if($payordertype == 'restaurant_shop' || $payordertype == 'restaurant_takeaway' || $payordertype == 'restaurant_booking') $payordertype = 'restaurant';
		$where = [];
        $where[] = ['aid','=',$payorder['aid']];

        $wherebid = 'bid = '.$payorder['bid'];
        if(getcustom('coupon_other_business')){
            $couponset = Db::name('coupon_set')->where('aid',$aid)->where('bid',$bid)->find();
            $showOtherBusinessCoupon = $couponset['show_other_bcoupon']??1;
            if($showOtherBusinessCoupon==0){
                //只展示该商家的赠送优惠券
                $wherebid = 'bid = '.$bid;
            }
        }

        $where[] = ['pricestart','<=',$payorder['money']];
        $where[] = ['priceend','>=',$payorder['money']];
        $where[] = ['starttime','<',time()];
        $where[] = ['endtime','>',time()];
        $where[] = Db::raw("find_in_set('".$payordertype."',paygive_scene)");
        $where[] = Db::raw("find_in_set('-1',gettj) or find_in_set('".$member['levelid']."',gettj)");
        $payaftergivelist = Db::name('payaftergive')->where($where)->where($wherebid);
        if(getcustom('payaftergive_bind_bids')){
            //查询平台设置为所有商户
            if($bid == 0){
                $payaftergivelist = $payaftergivelist->whereRaw("ISNULL(bind_bids)");
            }
        }
        $payaftergivelist = $payaftergivelist->select()->toArray();
        
        if(getcustom('payaftergive_bind_bids')){
            //查询平台设置为适用商户的活动
            if($bid != 0){
                $payaftergivelist2 = Db::name('payaftergive')->where($where)->where('bid',0)->whereRaw("find_in_set('".$payorder['bid']."',`bind_bids`) OR ISNULL(bind_bids)")->select()->toArray();
                if($payaftergivelist2){
                    $payaftergivelist = array_merge($payaftergivelist,$payaftergivelist2);
                }
            }
        }

        if(getcustom('payaftergive_select_product')){
            $proids =[];
            if($payordertype =='shop'){
               $proids= Db::name('shop_order_goods')->where('aid',$aid)->where('bid',$bid)->where('orderid',$payorder['orderid'])->column('proid');
            }
        }
		foreach($payaftergivelist as $payaftergive){
            if(getcustom('payaftergive_select_product')){
                if($payaftergive['productids'] && $payordertype =='shop'){
                    $productids = explode(',',$payaftergive['productids']);
                    $select_product= array_intersect($productids,$proids);
                    if(!$select_product)continue;  //无相同的porid 跳出
                }
            }
			if($payaftergive['limittimes'] != 0){
				$hastimes = Db::name('payaftergive_record')->where('aid',$aid)->where('mid',$mid)->where('hid',$payaftergive['id'])->count();
				if($hastimes >= $payaftergive['limittimes']) continue;
			}
			
			$record = [];
			$record['aid'] = $aid;
			$record['mid'] = $mid;
			$record['hid'] = $payaftergive['id'];
			$record['name'] = $payaftergive['name'];
			$record['money'] = $payaftergive['money'];
			$record['score'] = $payaftergive['score'];
			$record['choujiangtimes'] = $payaftergive['choujiangtimes'];
			$record['choujiangid'] = $payaftergive['choujiangid'];
			$record['give_coupon'] = $payaftergive['give_coupon'];
			$record['coupon_ids'] = $payaftergive['coupon_ids'];
			$record['createtime'] = time();
			Db::name('payaftergive_record')->insert($record);

			if($payaftergive['money'] > 0){
				\app\common\Member::addmoney($aid,$mid,$payaftergive['money'],$payaftergive['name']);
			}
			if($payaftergive['score'] > 0){
				\app\common\Member::addscore($aid,$mid,$payaftergive['score'],$payaftergive['name']);
			}
			if($payaftergive['choujiangtimes'] > 0 && $payaftergive['choujiangid'] > 0){
				$sharelog = Db::name('choujiang_sharelog')->where('aid',$aid)->where('hid',$payaftergive['choujiangid'])->where('mid',$mid)->find();
				if($sharelog){
					Db::name('choujiang_sharelog')->where('id',$sharelog['id'])->inc('extratimes',$payaftergive['choujiangtimes'])->update();
				}else{
					$data = [];
					$data['aid'] = $aid;
					$data['hid'] = $payaftergive['choujiangid'];
					$data['mid'] = $mid;
                    if(getcustom('business_choujiang')){
                        $data['bid'] = $bid;
                    }
					$data['extratimes'] = $payaftergive['choujiangtimes'];
					Db::name('choujiang_sharelog')->insert($data);
				}
			}
			if($payaftergive['give_coupon']==1 && $payaftergive['coupon_ids']){
                $coupon_ids = explode(',',$payaftergive['coupon_ids']);
                foreach ($coupon_ids as $couponid){
                    \app\common\Coupon::send($aid,$mid,$couponid);
                }
            }
		}
	}

    public static function paotui_pay($orderid){
        if(getcustom('paotui')){
            //处理推送到哪个端
            $res = \app\custom\PaotuiCustom::deal_push($orderid);
            if($res['status']!=1){
                return json(['status'=>0,'msg'=>$res['msg']]);
            } 
        }
    }

    //百度AI绘画支付完成
    public static function imgai_pay($orderid){
        //放在 app\custom\BaiduAi 文件中处理了
        $order = Db::name('imgai_order')->where('id',$orderid)->find();
        if($order['able_time']>0){
            Db::name('member')->where('id',$order['mid'])->update(['imgai_time'=>$order['able_time']]);
        }
        //百度AI绘画处理
        $baidu_ai = new \app\custom\BaiduAi($order['aid']);
        $res = $baidu_ai->afterPay($orderid);
        return true;
    }
    //地图标注支付完成
    public static function mapmark_pay(){
        return true;
    }
    //短视频去水印支付完成
    public static function videospider_pay($orderid){
        $order = Db::name('videospider_order')->where('id',$orderid)->find();
        if($order['able_time']>0){
            Db::name('member')->where('id',$order['mid'])->update(['videospider_time'=>$order['able_time']]);
        }
       return true;
    }

    public static function tour_activity_pay($orderid){
        if(getcustom('extend_tour')){
            $order = Db::name('tour_activity_order')->where('id',$orderid)->find();
            $member = Db::name('member')->where('id',$order['mid'])->field('id,nickname')->find();
            $aid = $order['aid'];
            $mid = $order['mid'];

            //公众号通知 订单支付成功
            $tmplcontent = [];
            $tmplcontent['first'] = '恭喜您的订单已支付成功';
            $tmplcontent['remark'] = '点击进入查看~';
            $tmplcontent['keyword1'] = $member['nickname']; //用户名
            $tmplcontent['keyword2'] = $order['ordernum'];//订单号
            $tmplcontent['keyword3'] = $order['totalprice'].'元';//订单金额
            $tmplcontent['keyword4'] = $order['title'];//商品信息
            $tmplcontentNew = [];
            $tmplcontentNew['phrase18'] = $member['nickname']; //用户名
            $tmplcontentNew['character_string2'] = $order['ordernum'];//订单号
            $tmplcontentNew['amount5'] = $order['totalprice']==0?'0.00':$order['totalprice'];//订单金额
            $tmplcontentNew['thing3'] = $order['title'];//商品信息
            //$rs = \app\common\Wechat::sendtmpl($aid,$mid,'tmpl_orderpay',$tmplcontent,m_url('pagesA/tour/orderlist', $aid),$tmplcontentNew);
            \app\common\Wechat::createSendTmplData($aid,0,$mid,'tmpl_orderpay',$tmplcontent,'pagesA/tour/orderlist',0,$tmplcontentNew,'sendtmpl');

            $rs = \app\common\Sms::send($aid,$member['tel'] ? $member['tel'] : $order['tel'],'tmpl_orderpay',['ordernum'=>$order['ordernum']]);
        }
    }

    public static function gift_bag_pay($orderid){
        if(getcustom('extend_gift_bag')){
           Db::name('gift_bag_order_goods')->where('orderid',$orderid)->update(['status'=>1,'paytime'=>time()]);
           return true;
       }
    }

    public static function lipin_pay($orderid){
        if(getcustom('lipinka_morefee') || getcustom('lipinka_freight_free')){
           $order = Db::name('lipin_order')->where('id',$orderid)->find();
           if($order){
                $member = Db::name('member')->where('id',$order['mid'])->find();
                $updata = [
                    'status'=>1,
                    'usetime'=>time(),
                    'mid'=>$order['mid'],
                    'headimg'=>$member['headimg'],
                    'nickname'=>$member['nickname'],
                    'remark'=>'兑换商品:'.$order['title']
                ];
                Db::name('lipin_codelist')->where('id',$order['codeid'])->update($updata);
                if($order['type'] == 1){
                    //修改商品为已支付
                    Db::name('shop_order')->where('ordernum','like',$order['ordernum'].'%')->update(['status'=>1,'paytime'=>$order['paytime']]);
                }
                if($order['type'] == 4){
                    //修改积分商品为已支付
                     Db::name('scoreshop_order')->where('ordernum','like',$order['ordernum'].'%')->update(['status'=>1,'paytime'=>$order['paytime']]);
                }
               //发货信息录入 微信小程序+微信支付
               if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                    $order['freight_type'] = 3;
                    \app\common\Order::wxShipping($order['aid'],$order,'lipin');
               }
           }
       }
    }

    public static function business_deposit_pay($orderid){
        if(getcustom('business_deposit')){
            $order = Db::name('business_deposit_order')->where('id',$orderid)->find();
            $aid = $order['aid'];
            $bid = $order['bid'];

            \app\common\Business::updateDeposit($aid,$bid,$order['money'],'保证金充值','recharge',$order['ordernum']);
        }
    }

	public function baoming_xcx_pay($orderid){
	    if(getcustom('baoming_xcx')){
            $order = Db::name('baoming_xcx_order')->where('id',$orderid)->find();
            $aid = $order['aid'];
            $bid = $order['bid'];

           	Db::name('baoming_xcx_order')->where('id',$orderid)->update(['paystatus'=>1]);
        }
		
	}
	public function member_salelevel_pay($orderid){
	    if(getcustom('member_levelup_auth')){
            $order = Db::name('member_salelevel_order')->where('id',$orderid)->find();
            $aid = $order['aid'];
            $bid = $order['bid'];
			$zsuserlevel = Db::name('member_level')->where('aid',$aid)->where('id',$order['levelid'])->find();
			$member = Db::name('member')->where('id',$order['mid'])->find();

			\app\common\Member::addmoney($aid,$order['from_mid'],$order['totalprice'],'推荐'.$member['nickname'].'购买'.$zsuserlevel['name']);

			//减掉赠送人的额度
            Db::name('member')->where('aid',aid)->where('id',$order['from_mid'])->dec('salelevel_money',$zsuserlevel['apply_paymoney'])->update();
			//增加领取人的额度
			//查看已经赠送了的额度
			$yzsed =  Db::name('member_salelevel_order')->where('aid',$aid)->where('from_mid',$order['mid'])->where('status',1)->sum('levelprice');
			$salelevel_money =  round($zsuserlevel['give_level_totalmoney']-$yzsed,2);
			if($salelevel_money>0){
				Db::name('member')->where('id',$order['mid'])->update(['salelevel_money'=>$salelevel_money]);
			}
			$rs = \app\common\Member::handleUpLevel($aid,$order['mid'],$zsuserlevel,$member,$member,$zsuserlevel['cid']);
           
        }
		
	}
    //申请区域合伙人订单
    public static function region_partner_pay($orderid){
        if(getcustom('region_partner')){
            return true;
        }
    }
	public static function gift_pack_pay($orderid){
		 if(getcustom('yx_gift_pack')){
            $order = Db::name('gift_pack_order')->where('id',$orderid)->find();
            $aid = $order['aid'];
            $bid = $order['bid'];
			$member = Db::name('member')->where('id',$order['mid'])->find();
			if($order['givescore']>0){
				\app\common\Member::addscore($aid,$order['mid'],$order['givescore'],'购买礼包赠送'.t('积分'));				
			}
			//优惠券赠送
			$coupon = explode(',',$order['couponids']);
			foreach($coupon as $k=>$v){
				\app\common\Coupon::send($aid,$order['mid'],$v,true);
			}
            \app\common\Order::giveCommission($order,'gift_pack');
        }
	}

    //商城订单
    public static function taocan_pay($orderid){
        if(getcustom('taocan_product')){
            $order = Db::name('taocan_order')->where('id',$orderid)->find();
            $member = Db::name('member')->where('id',$order['mid'])->find();
            $aid = $order['aid'];
            $mid = $order['mid'];
            $oglist = Db::name('taocan_order_goods')->where('orderid',$orderid)->select()->toArray();


            Db::name('taocan_order_goods')->where('orderid',$orderid)->update(['status'=>1]);

            //自动发货
            if($order['freight_type']==3){
                $og = Db::name('taocan_order_goods')->where('orderid',$order['id'])->find();
                $freight_content = Db::name('taocan_product')->where('id',$og['proid'])->value('freightcontent');
                Db::name('taocan_order')->where('id',$order['id'])->update(['freight_content'=>$freight_content,'status'=>2,'send_time'=>time()]);
                Db::name('taocan_order_goods')->where('orderid',$order['id'])->update(['status'=>2]);
            }
            if($order['platform'] == 'toutiao'){
                \app\common\Ttpay::pushorder($aid,$order['ordernum'],1);
            }

            \app\common\Member::uplv($aid,$mid);

        }
    }
	//签到订单
	public static function sign_pay($orderid){
		$order = Db::name('sign_order')->where('id',$orderid)->find();   
            $rs = \app\common\SignBonus::signin($order['aid'],$order);      
	 	}

	//商家打赏
    public static function business_reward_pay($orderid){
        if(getcustom('business_reward_member')){
            $order = Db::name('business_reward_order')->where('id',$orderid)->find();
            Db::name('business_reward_order')->where('id',$orderid)->update(['status'=>1]);
            $set = Db::name('business_reward_set')->where('aid',$order['aid'])->find();
            $sysset = Db::name('admin_set')->where('aid',$order['aid'])->find();
            $member = Db::name('member')->where('id',$order['to_mid'])->find();
            //增加被打赏会员积分
            \app\common\Member::addscore($order['aid'],$order['to_mid'],$order['to_money'],'商家会员ID'.$order['mid'].'打赏');
            //增加商家会员积分
            \app\common\Member::addscore($order['aid'],$order['mid'],$order['to_business_money'],'打赏会员ID'.$order['to_mid'].'奖励');
            if($order['to_commission']>0){
                //增加会员佣金
                \app\common\Member::addcommission($order['aid'],$order['to_mid'],$order['mid'],$order['to_commission'],'商家会员ID'.$order['mid'].'打赏',1,'business_reward');
            }
            //发放分销佣金
            $product = [
                'commissionset' => $set['commissionset'],
                'commissiondata1' => $set['commissiondata'],
                'fx_differential' => -1
            ];
            $fenxiao_money = bcsub($order['pay_money'],$order['to_commission'],2);
            $commission_data = \app\common\Fenxiao::fenxiao($sysset,$member,$product,1,$fenxiao_money);
            if($commission_data['parent1'] && $commission_data['parent1commission']>0){
                $data_c = [
                    'aid'=>$order['aid'],
                    'mid'=>$commission_data['parent1'],
                    'frommid'=>$order['to_mid'],
                    'orderid'=>$orderid,
                    'ogid'=>0,
                    'type'=>'business_reward',
                    'commission'=>$commission_data['parent1commission'],
                    'score'=>0,
                    'remark'=>'打赏一级奖励',
                    'createtime'=>time()
                ];

                Db::name('member_commission_record')->insert($data_c);
            }
            if($commission_data['parent2'] && $commission_data['parent2commission']>0){
                $data_c = [
                    'aid'=>$order['aid'],
                    'mid'=>$commission_data['parent2'],
                    'frommid'=>$order['to_mid'],
                    'orderid'=>$orderid,
                    'ogid'=>0,
                    'type'=>'business_reward',
                    'commission'=>$commission_data['parent2commission'],
                    'score'=>0,
                    'remark'=>'打赏二级奖励',
                    'createtime'=>time()
                ];

                Db::name('member_commission_record')->insert($data_c);
            }
            if($commission_data['parent3'] && $commission_data['parent3commission']>0){
                $data_c = [
                    'aid'=>$order['aid'],
                    'mid'=>$commission_data['parent3'],
                    'frommid'=>$order['to_mid'],
                    'orderid'=>$orderid,
                    'ogid'=>0,
                    'type'=>'business_reward',
                    'commission'=>$commission_data['parent3commission'],
                    'score'=>0,
                    'remark'=>'打赏三级奖励',
                    'createtime'=>time()
                ];

                Db::name('member_commission_record')->insert($data_c);
            }
            \app\common\Order::giveCommission($order,'business_reward');
        }
    }

    //积分赠送手续费
    public static function score_transfer_pay($orderid){
        if(getcustom('score_transfer_sxf')){
            $order = Db::name('score_transfer_order')->where('id',$orderid)->find();
            if($order){
                // 1 已支付 0 待支付
                Db::name('score_transfer_order')->where('id',$orderid)->update(['status' => 1]);

                $nickname = Db::name('member')->where('id',$order['mid'])->value('nickname');

                \app\common\Member::addscore($order['aid'],$order['receive_mid'],$order['score_num'],sprintf("来自%s的".t('积分')."转赠",$nickname), '', 0, $order['mid']);
            }
        }
    }
    //找到修改过单号但支付为非最新单号的订单（支付了老的单号，或者支付了以后又去触发了支付更新了单号），更新为支付的ordernum
    public static function changeOrdernumToPayOrdernum($payorder=[])
    {
        $change_log = Db::name('ordernum_change_log')->where('ordernum',$payorder['ordernum'])->where('orderid',$payorder['orderid'])->find();
        if($change_log){
            $neworder = Db::name($change_log['tablename'])->where('ordernum',$change_log['ordernum_new'])->where('orderid',$change_log['orderid'])->find();
            if($neworder){
                //只改了一次单号
                Db::name($change_log['tablename'])->where('ordernum',$change_log['ordernum_new'])->where('orderid',$change_log['orderid'])
                    ->update(['ordernum'=>$payorder['ordernum']]);
                $newlog = $change_log;
                unset($newlog['id']);
                $newlog['ordernum'] = $change_log['ordernum_new'];
                $newlog['ordernum_new'] = $payorder['ordernum'];
                Db::name($change_log['tablename'])->insert($newlog);
            }else{
                //改了多次单号
                $change_loglist = Db::name('ordernum_change_log')->where('tablename',$change_log['tablename'])->where('orderid',$change_log['orderid'])->select()->toArray();
                foreach ($change_loglist as $item){
                    $neworder = Db::name($item['tablename'])->where('ordernum',$item['ordernum_new'])->where('orderid',$item['orderid'])->find();
                    if($neworder){
                        Db::name($item['tablename'])->where('ordernum',$item['ordernum_new'])->where('orderid',$item['orderid'])
                            ->update(['ordernum'=>$payorder['ordernum']]);
                        $newlog = $item;
                        unset($newlog['id']);
                        $newlog['ordernum'] = $item['ordernum_new'];
                        $newlog['ordernum_new'] = $payorder['ordernum'];
                        Db::name($item['tablename'])->insert($newlog);
                        break;
                    }
                }
            }
        }
    }

    //$type =1//交班   2：报表
    /**
     * 餐饮收银台交班报表等统计营业额信息
     *  $datetype 报表的日期类型 today：今天   yesterday昨天  custom自定义
     *  $type=1 =1//交班   2：报表
     * $other 其他字段   datetype日期类型  starttime开始  endtime结束时间   paytype 支付方式限制，算总的时候 只显示限的支付方式
     */
    public static function tradeReport($aid,$bid,$uid,$type=1,$other=[]){
        $rdata = [];
        $jiaobantime = time();
        $logintime = Db::name('admin_loginlog')->where('aid',$aid)->where('uid',$uid)->order('id desc')->where('logintype','餐饮收银台账号登录')->value('logintime');

        $cashier = [];
        if(getcustom('restaurant_shop_cashdesk')){
            //收银员账号
            $cwhere []= ['aid' ,'=',$aid];
            if($bid > 0){
                $cwhere []= ['bid' ,'=',$bid];
            }else{
                $cwhere []= ['bid' ,'=',0];
            }
            $cashier = Db::name('restaurant_cashdesk')->where($cwhere)->find();
        }
        $search_paytype = $other['search_paytype']??'';
        $rdata['search_paytype'] =$search_paytype;//用于打印的判断
        //默认
        $today_start_time = strtotime(date('Y-m-d 00:00:01'));
        if($logintime > $today_start_time){
            $today_start_time =$logintime;
        }

        //$datetype today:今日 yesterday昨日 custom自定义 
        if($other['datetype'] =='today'){
            $logintime =$today_start_time =strtotime(date('Y-m-d 00:00:01'));
            $jiaobantime = time();
        }
        if($other['datetype'] =='yesterday'){
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $logintime =$today_start_time =strtotime($yesterday.' 00:00:01');
            $jiaobantime = strtotime($yesterday.' 23:59:59');
        }
        if($other['datetype'] =='custom'){
            $logintime =$today_start_time =strtotime($other['starttime']);
            $jiaobantime = $other['endtime']?strtotime($other['endtime']):$jiaobantime;
        }

        $today_total_money = 0;
        $today_cash_money  = 0;//现金
        $today_yue_money   = 0;//余额
        $today_sxf_money   = 0;//随行付
        $today_wx_money    = 0;//微信
        $today_alipay_money= 0;//支付宝

        if(getcustom('restaurant_shop_cashdesk')){
            $where = [];
            $where[] =['aid','=',$aid];
            $where[] =['bid','=',$bid];
            $where[] = Db::raw("status in (1,3) or (status =4 and refund_status =2)");
            $where[] = ['paytime','between',[$today_start_time,$jiaobantime]];
            if($type ==1){
                $where[] = ['uid','=',$uid];
            }
//            $where[] = ['platform','=','restaurant_cashdesk'];
            $where[] = ['cashdesk_id','=',$cashier['id']];
            
            //------------------收银台营业额----------------
            //现金 0
            $today_cash_money =  0+ Db::name('restaurant_shop_order')->where($where)->where('paytypeid','=',0)->sum('totalprice');
            if(getcustom('restaurant_cashdesk_mix_pay')){
                $today_mix_money0 = Db::name('restaurant_shop_order')->where($where)->where('paytypeid','=',0)->sum('mix_money');
                $today_cash_money-=$today_mix_money0;
            }
            //余额 1
            $today_yue_money =  0+ Db::name('restaurant_shop_order')->where($where)->where('paytypeid','=',1)->sum('totalprice');
            if(getcustom('restaurant_cashdesk_mix_pay')){
                $today_mix_money1 = Db::name('restaurant_shop_order')->where($where)->where('paytypeid','=',1)->sum('mix_money');
                $today_yue_money-=$today_mix_money1;
            }
            //随行付 0
            $today_sxf_money = 0;
            if(getcustom('cashdesk_sxpay')){
                $today_sxf_money =  0+ Db::name('restaurant_shop_order')->where($where)->where('paytypeid','in',[5,81])->sum('totalprice');
            }
            //微信 2
            $today_wx_money =  0+ Db::name('restaurant_shop_order')->where($where)->where('paytypeid','=',2)->sum('totalprice');
            //支付宝 3
            $today_alipay_money =  0+ Db::name('restaurant_shop_order')->where($where)->where('paytypeid','=',3)->sum('totalprice');
        }

        $today_douyin_hexaio_money = 0;
        if(getcustom('restaurant_douyin_qrcode_hexiao')){
            $today_douyin_hexaio_money =0+ Db::name('restaurant_shop_order')->where($where)->where('paytypeid','=',121)->sum('totalprice');
        }
        $today_custom_money = 0;
        if(getcustom('restaurant_cashdesk_custom_pay')){
            //自定义支付
            $today_custom_pay_list  =[];
            $custom_pay_list = Db::name('restaurant_cashdesk_custom_pay')->where('aid',$aid)->where('bid',$bid)->where('status',1)->select()->toArray();
            foreach($custom_pay_list as $key=>$val){
                $custom_paytypeid = 10000 + $val['id'];
                $custom_money =0 +Db::name('restaurant_shop_order')->where($where)->where('paytypeid','=',$custom_paytypeid)->where('child_paytypeid',$val['id'])->sum('totalprice');
                //用于支付方式的搜索
                if($search_paytype==$custom_paytypeid)$today_total_money +=  $custom_money;
                if($custom_money > 0){
                    $today_custom_pay_list[$val['id']] =[
                        'money' => dd_money_format( $custom_money),
                        'title' =>   $val['title'] ,
                        'paytypeid' => $custom_paytypeid
                    ]  ;
                    $today_custom_money+=  $custom_money;
                }
            }
        }
        //用于支付方式搜索
        if($search_paytype==0){
            $today_total_money +=  $today_cash_money;
        }
        if($search_paytype==1){
            $today_total_money +=  $today_yue_money;
        }
        if($search_paytype==81){
            $today_total_money +=  $today_sxf_money;
        }
        if($search_paytype==2){
            $today_total_money +=  $today_wx_money;
        }
        if($search_paytype==3){
            $today_total_money +=  $today_alipay_money;
        }
        if($search_paytype==121){
            $today_total_money +=  $today_douyin_hexaio_money;
        }
        if($search_paytype ==''){
            $today_total_money = $today_cash_money+$today_yue_money+$today_sxf_money+ $today_wx_money +$today_alipay_money+$today_custom_money+$today_douyin_hexaio_money;
        }
        if(getcustom('restaurant_cashdesk_mix_pay')){
            //开启 混合支付
            $mix_wx_pay =  0+ Db::name('restaurant_shop_order')->where($where)->where('mix_paytypeid','=',2)->sum('mix_money');
            $today_total_money+= $mix_wx_pay;
            $mix_alipay_pay =  0+ Db::name('restaurant_shop_order')->where($where)->where('mix_paytypeid','=',3)->sum('mix_money');
            $today_total_money+=$mix_alipay_pay;
            $mix_sxf_pay =  0+ Db::name('restaurant_shop_order')->where($where)->where('mix_paytypeid','=',81)->sum('mix_money');
            $today_total_money+= $mix_sxf_pay;
            
            $rdata['mix_sxf_pay'] =dd_money_format($mix_sxf_pay); //随行付
            $rdata['mix_alipay_pay'] =dd_money_format($mix_alipay_pay); //支付宝
            $rdata['mix_wx_pay'] =dd_money_format($mix_wx_pay); //微信
        }
        
        $rdata['today_total_money'] =dd_money_format($today_total_money);//总
        $rdata['today_cash_money'] =dd_money_format($today_cash_money); //现金
        $rdata['today_yue_money'] =dd_money_format($today_yue_money); //余额
        $rdata['today_sxf_money'] =dd_money_format($today_sxf_money); //随行付
        $rdata['today_wx_money'] =dd_money_format($today_wx_money); //微信
        $rdata['today_alipay_money'] =dd_money_format($today_alipay_money); //支付宝
        $rdata['today_douyin_hexaio_money'] =dd_money_format($today_douyin_hexaio_money); //抖音核销
        if(getcustom('restaurant_cashdesk_custom_pay')){
            $rdata['today_custom_pay_list'] = $today_custom_pay_list;
        }
//------------------收银台订单统计----------------
        $where = [];
        $where[] = ['aid','=',$aid];
        $where[] = ['bid','=',$bid];
        $where[] = ['status','=',1];
        $where[] = ['paytime','between',[$today_start_time,$jiaobantime]];
        if($type ==1){
            $where[] = ['uid','=',$uid];
        }
        $cashier_total_money = 0;
        //现金 0
        $cashier_cash_money =  0+ Db::name('cashier_order')->where($where)->where('paytypeid','=',0)->sum('totalprice');
        //余额 1
        $cashier_yue_money =  0+ Db::name('cashier_order')->where($where)->where('paytypeid','=',1)->sum('totalprice');
        //随行付 0
        $cashier_sxf_money = 0;
        if(getcustom('cashdesk_sxpay')){
            $cashier_sxf_money =  0+ Db::name('cashier_order')->where($where)->where('paytypeid','in',[5,81])->sum('totalprice');
        }
        //微信 2
        $cashier_wx_money =  0+ Db::name('cashier_order')->where($where)->where('paytypeid','=',2)->sum('totalprice');
        //支付宝 3
        $cashier_alipay_money =  0+ Db::name('cashier_order')->where($where)->where('paytypeid','=',3)->sum('totalprice');
        //用于支付方式搜索
        if($search_paytype==0){
            $cashier_total_money +=  $cashier_cash_money;
        }
        if($search_paytype==1){
            $cashier_total_money +=  $cashier_yue_money;
        }
        if($search_paytype==81){
            $cashier_total_money +=  $cashier_sxf_money;
        }
        if($search_paytype==2){
            $cashier_total_money +=  $cashier_wx_money;
        }
        if($search_paytype==3){
            $cashier_total_money +=  $cashier_alipay_money;
        }
        if($search_paytype ==''){
            $cashier_total_money = $cashier_cash_money+$cashier_yue_money+$cashier_sxf_money+ $cashier_wx_money +$cashier_alipay_money;
        }
        $rdata['cashier_total_money']  = dd_money_format($cashier_total_money);//总
        $rdata['cashier_cash_money']   = dd_money_format($cashier_cash_money); //现金
        $rdata['cashier_yue_money']    = dd_money_format($cashier_yue_money); //余额
        $rdata['cashier_sxf_money']    = dd_money_format($cashier_sxf_money); //随行付
        $rdata['cashier_wx_money']     = dd_money_format($cashier_wx_money); //微信
        $rdata['cashier_alipay_money'] = dd_money_format($cashier_alipay_money); //支付宝

        $rdata['today_cash_money']   += $rdata['cashier_cash_money']; //现金
        $rdata['today_yue_money']    += $rdata['cashier_yue_money']; //余额
        $rdata['today_sxf_money']    += $rdata['cashier_sxf_money']; //随行付
        $rdata['today_wx_money']     += $rdata['cashier_wx_money']; //微信
        $rdata['today_alipay_money'] += $rdata['cashier_alipay_money']; //支付宝
//------------------线上营业额营业额（包括线上商城和扫码点餐）----------------
        $online_total_money = 0;
        $online_where = [];
        $online_where[] = ['platform','<>','cashdesk'];
        $online_where[] = ['platform','<>','cashier'];
        $online_where[]= ['aid' ,'=',$aid];
        $online_where[]= ['bid' ,'=',$bid];
        $online_where[]= ['status' ,'=',1];
        $online_where[]= ['type' ,'<>','recharge'];
        $online_where[] = ['paytime','between',[$today_start_time,$jiaobantime]];
        $online_yue_money = 0+Db::name('payorder')->where($online_where)->where('paytypeid',1)->sum('money');
        $online_wx_money = 0+Db::name('payorder')->where($online_where)->where('paytypeid',2)->sum('money');
        $online_alipay_money = 0+Db::name('payorder')->where($online_where)->where('paytypeid',3)->sum('money');
        $online_admin_money = 0+Db::name('payorder')->where($online_where)->where('paytypeid',0)->sum('money');
        if($search_paytype==1){
            $online_total_money +=  $today_douyin_hexaio_money;
        }
        if($search_paytype==2){
            $online_total_money +=  $online_wx_money;
        }
        if($search_paytype==3){
            $online_total_money +=  $online_alipay_money;
        }
        if($search_paytype==0){
            $online_total_money +=  $online_admin_money;
        }
        if($search_paytype==''){
            $online_total_money =$online_yue_money + $online_wx_money+ $online_alipay_money+$online_admin_money;
        }

        $rdata['online_yue_money'] =dd_money_format($online_yue_money); //余额
        $rdata['online_wx_money'] =dd_money_format($online_wx_money); //微信
        $rdata['online_alipay_money'] =dd_money_format($online_alipay_money); //支付宝
        $rdata['online_admin_money'] =dd_money_format($online_admin_money); //后台补录
        $rdata['online_total_money'] =dd_money_format($online_total_money); //总
//------------------优惠金额（商城的优惠劵、餐饮的优惠劵、餐饮的促销、收银台优惠券）----------------
        $youhui_where =[];
        $youhui_where[]= ['aid' ,'=',$aid];
        $youhui_where[] =['bid','=',$bid];
        $youhui_where[] = ['paytime','between',[$today_start_time,$jiaobantime]];
        //商城
        $shop_coupon = Db::name('shop_order')->where($youhui_where)->where('status','in',[1,2,3])->sum('coupon_money');
        //餐饮
        $restaurant_youhui_where[] =  $youhui_where;
        $restaurant_youhui_where[] = Db::raw("((status in (1,3)) or (status =4 and refund_status =2))");
        $restaurant_coupon = Db::name('restaurant_shop_order')->where($restaurant_youhui_where)->sum('coupon_money');
        $restaurant_cuxiao = Db::name('restaurant_shop_order')->where($restaurant_youhui_where)->sum('cuxiao_money');
        //收银台
        $cashier_coupon = Db::name('cashier_order')->where($youhui_where)->where('status',1)->sum('coupon_money');
        $rdata['shop_coupon']       = $shop_coupon;
        $rdata['restaurant_coupon'] = $restaurant_coupon;
        $rdata['restaurant_cuxiao'] = $restaurant_cuxiao;
        $rdata['cashier_coupon']    = $cashier_coupon;
        $rdata['youhui_total'] =dd_money_format($shop_coupon+$restaurant_coupon+$restaurant_cuxiao+$cashier_coupon); //总
//------------------会员充值（所有）----------------
        $recharge_total_money = 0;
        if($bid ==0){
            $recharge_where = [];
            $recharge_where[]= ['aid' ,'=',$aid];
            
            $recharge_where[]= ['type' ,'=','recharge'];
            $recharge_where[] = ['paytime','between',[$today_start_time,$jiaobantime]];
            $recharge_sxf_money  = 0;
            if(getcustom('cashdesk_sxpay')) {
                $recharge_sxf_money = 0 + Db::name('payorder')->where($recharge_where)->where('paytypeid', 81)->sum('money');//随行付
            }
            $recharge_wx_money =  0+Db::name('payorder')->where($recharge_where)->where('paytypeid',2)->sum('money');//微信
            $recharge_alipay_money =  0+Db::name('payorder')->where($recharge_where)->where('paytypeid',3)->sum('money');//支付宝
            $recharge_cash_money  =  0+Db::name('payorder')->where($recharge_where)->where('paytypeid',0)->where('platform','cashdesk')->sum('money');//现金
            $recharge_cash_money +=  0+Db::name('payorder')->where($recharge_where)->where('paytypeid',0)->where('platform','cashier')->sum('money');//现金
    //        $recharge_admin_money =  0+Db::name('payorder')->where($recharge_where)->where('paytypeid',0)->where('platform','<>','cashdesk')->sum('money');//后台充值
            if($search_paytype==81){
                $recharge_total_money +=  $recharge_sxf_money;
            }
            if($search_paytype==2){
                $recharge_total_money +=  $recharge_wx_money;
            }
            if($search_paytype==3){
                $recharge_total_money +=  $recharge_alipay_money;
            }
            if($search_paytype==0){
                $recharge_total_money +=  $recharge_cash_money;
            }
            
            if($search_paytype=='') {
                $recharge_total_money = $recharge_sxf_money + $recharge_wx_money + $recharge_alipay_money + $recharge_cash_money;
            }
            $rdata['recharge_sxf_money'] =dd_money_format($recharge_sxf_money); //随行付
            $rdata['recharge_wx_money'] =dd_money_format($recharge_wx_money); //微信
            $rdata['recharge_alipay_money'] =dd_money_format($recharge_alipay_money); //支付宝
            $rdata['recharge_cash_money'] =dd_money_format($recharge_cash_money); //现金
            //$rdata['recharge_admin_money'] =dd_money_format($recharge_admin_money); //现金
            $rdata['recharge_total_money'] =dd_money_format($recharge_total_money); //总
        }
//------------------退款总额（所有）----------------
        $refund_total_money = 0;
        $refund_where = [];
        $refund_where[] = ['aid' ,'=',$aid];
        $refund_where[] = ['bid' ,'=',$bid];
        $refund_where[] = ['refund_time' ,'>',0];
        $refund_where[] = ['status' ,'=',1];
        $refund_where[] = ['refund_time','between',[$today_start_time,$jiaobantime]];
        //退款 现金
        $refund_cash_money =  0+Db::name('payorder')->where($refund_where)->where('paytypeid',0)->sum('refund_money');//现金
        //退款 余额
        $refund_yue_money =  0+Db::name('payorder')->where($refund_where)->where('paytypeid',1)->sum('refund_money');//现金
        //退款 随行付
        $refund_sxf_money =0;
        if(getcustom('cashdesk_sxpay')){
            $refund_sxf_money = 0+Db::name('payorder')->where($refund_where)->where('paytypeid',81)->where('platform','cashdesk')->sum('refund_money');//随行付
            $refund_sxf_money += 0+Db::name('payorder')->where($refund_where)->where('paytypeid',81)->where('platform','cashier')->sum('refund_money');//随行付
        }
        //微信线上退款    商城 + 点餐退款金额
        $refund_wx_money = 0+Db::name('payorder')->where($refund_where)->where('paytypeid',2)->sum('refund_money');;//现金;
        //支付宝线上退款     
        $refund_alipay_money = 0+Db::name('payorder')->where($refund_where)->where('paytypeid',3)->sum('refund_money');
        $refund_douyin_hexaio_money = 0;
        if(getcustom('restaurant_douyin_qrcode_hexiao')){
            //抖音核销券     
            $refund_douyin_hexaio_money = 0+Db::name('payorder')->where($refund_where)->where('paytypeid',121)->sum('refund_money');
        }
        //支出
        if(getcustom('expend')){
            $expend_where = [];
            $expend_where[] = ['aid' ,'=',$aid];
            $expend_where[] = ['bid' ,'=',$bid];
            $expend_where[] = ['createtime','between',[$today_start_time,$jiaobantime]];
            //
            $rdata['expend_total_money'] = 0+Db::name('expend')->where($expend_where)->sum('money');
            //分类
            $expendCategory = Db::name('expend_category')->where('aid',$aid)->where('bid',$bid)->column('name','id');
            $expendCids = [];
            if($expendCategory){
                foreach ($expendCategory as $cid => $cname){
                    $expendCids[] = $cid;
                    $rdata['expend_money_cat'][] = [
                        'cid'=>$cid,
                        'cname'=>$cname,
                        'money'=> 0+Db::name('expend')->where($expend_where)->where('cid',$cid)->sum('money')
                    ];
                }
            }
            $rdata['expend_money_cat'][] = [
                'cid'=>0,
                'cname'=>'未分类',
                'money'=> 0+Db::name('expend')->where($expend_where)->where('cid','not in',$expendCids)->sum('money')
            ];
        }
        //自定义支付计算
        $refund_custom_money = 0;
        $refund_custom_list = [];
        if(getcustom('restaurant_cashdesk_custom_pay')){
            //自定义支付
            $custom_pay_list = Db::name('restaurant_cashdesk_custom_pay')->where('aid',$aid)->where('bid',$bid)->where('status',1)->select()->toArray();
            foreach($custom_pay_list as $key=>$val){
                $custom_paytypeid = 10000 + $val['id'];
                $custom_refund_money =0 +Db::name('payorder')->where($refund_where)->where('paytypeid','=',$custom_paytypeid)->sum('refund_money');
                //用于支付方式的搜索
                if($search_paytype==$custom_paytypeid)$refund_total_money +=  $custom_refund_money;
                if($custom_refund_money > 0){
                    $refund_custom_money+=  $custom_refund_money;
                    $refund_custom_list[$val['id']] =[
                        'refund_money' => dd_money_format( $custom_refund_money),
                        'title' =>   $val['title'],
                        'paytypeid' => $custom_paytypeid
                    ]  ;
                }
            }
        }
        if($search_paytype==0){
            $refund_total_money+=$refund_cash_money;
        }
        if($search_paytype==1){
            $refund_total_money+=$refund_yue_money;
        }
        if($search_paytype==81){
            $refund_total_money+=$refund_sxf_money;
        }
        if($search_paytype==2){
            $refund_total_money+=$refund_wx_money;
        }
        if($search_paytype==3){
            $refund_total_money+=$refund_alipay_money;
        }
        if($search_paytype==121){
            $refund_total_money+=$refund_douyin_hexaio_money;
        }
        if($search_paytype=='') {
            $refund_total_money = $refund_cash_money + $refund_yue_money + $refund_sxf_money + $refund_wx_money + $refund_alipay_money + $refund_douyin_hexaio_money + $refund_custom_money;
        }
        $rdata['refund_total_money'] =dd_money_format($refund_total_money); //总退款
        $rdata['refund_cash_money'] =dd_money_format($refund_cash_money); //现金退款
        $rdata['refund_yue_money'] =dd_money_format($refund_yue_money); //余额退款
        $rdata['refund_sxf_money'] =dd_money_format($refund_sxf_money); //随行付退款
        $rdata['refund_wx_money'] =dd_money_format($refund_wx_money); //微信退款
        $rdata['refund_alipay_money'] =dd_money_format($refund_alipay_money); //支付宝退款
        $rdata['refund_douyin_hexaio_money'] =dd_money_format($refund_douyin_hexaio_money); //抖音核销
        $rdata['refund_custom_money'] =dd_money_format($refund_custom_money); //自定义支付退款
        $rdata['refund_custom_list'] =$refund_custom_list; //自定义支付列表
        if(getcustom('restaurant_cashdesk_mix_pay')){
            //开启 混合支付
            $mix_refund_where = [];
            $mix_refund_where[] = ['refund_status','=',2];
            $mix_refund_where[] = ['paytime','between',[$today_start_time,$jiaobantime]];

            $mix_refund_wx_pay =  0+ Db::name('restaurant_shop_order')->where($mix_refund_where)->where('mix_paytypeid','=',2)->sum('mix_money');
            $mix_refund_alipay_pay =  0+ Db::name('restaurant_shop_order')->where($mix_refund_where)->where('mix_paytypeid','=',3)->sum('mix_money');
            $mix_refund_sxf_pay =  0+ Db::name('restaurant_shop_order')->where($mix_refund_where)->where('mix_paytypeid','=',81)->sum('mix_money');

            $rdata['mix_refund_wx_pay'] =dd_money_format($mix_refund_wx_pay); //微信
            $rdata['mix_refund_alipay_pay'] =dd_money_format($mix_refund_alipay_pay); //支付宝
            $rdata['mix_refund_sxf_pay'] =dd_money_format($mix_refund_sxf_pay); //随行付
        }
//------------------订单数量（收银机-已完成，线上（商城-已支付 +扫码点餐-已完成））----------------
        $cashdesk_ordercount = 0;
        if(getcustom('restaurant_shop_cashdesk')){
            $cashdesk_ordercount_where[] = ['aid' ,'=',$aid];
            $cashdesk_ordercount_where[] = ['bid' ,'=',$bid];
            $cashdesk_ordercount_where[] = ['status','in',[1,3]];
            $cashdesk_ordercount_where[] = ['cashdesk_id','>',0];
            $cashdesk_ordercount_where[] = ['paytime','between',[$today_start_time,$jiaobantime]];
            if($search_paytype !=''){
                $cashdesk_ordercount_where[] = ['paytypeid','=',$search_paytype];
            }
            $cashdesk_ordercount = Db::name('restaurant_shop_order')->where($cashdesk_ordercount_where)->count();
        }
        //收银台订单
        $cashier_ordercount_where   = [];
        $cashier_ordercount_where[] = ['aid' ,'=',$aid];
        $cashier_ordercount_where[] = ['bid' ,'=',$bid];
        $cashier_ordercount_where[] = ['status','=',1];
        $cashier_ordercount_where[] = ['paytime','between',[$today_start_time,$jiaobantime]];
        if($search_paytype !=''){
            $cashier_ordercount_where[] = ['paytypeid','=',$search_paytype];
        }
        $cashier_ordercount = Db::name('cashier_order')->where($cashier_ordercount_where)->count();
        //扫码点餐+商城
        $shop_ordercount_where = [];
        $shop_ordercount_where[] = ['status','in',[1,2,3]];
        $shop_ordercount_where[] = ['aid','=',$aid];
        $shop_ordercount_where[] = ['bid','=',$bid];
        if($search_paytype !=''){
            $shop_ordercount_where[] = ['paytypeid','=',$search_paytype];
        }
        $shop_ordercount_where[] = ['paytime','between',[$today_start_time,$jiaobantime]];
        $shop_ordercount = 0+Db::name('shop_order')->where($shop_ordercount_where)->count();

        //扫码点餐
        $scan_ordercount = 0;
        if(getcustom('restaurant_shop_cashdesk')){
            $scan_ordercount_where =[];
            $scan_ordercount_where[] = ['status','in',[1,3]];
            $scan_ordercount_where[] = ['aid','=',$aid];
            $scan_ordercount_where[] = ['bid','=',$bid];
            $scan_ordercount_where[] = ['cashdesk_id','=',0];
            $scan_ordercount_where[] = ['paytime','between',[$today_start_time,$jiaobantime]];
            if($search_paytype !=''){
                $scan_ordercount_where[] = ['paytypeid','=',$search_paytype];
            }
            $scan_ordercount =0+ Db::name('restaurant_shop_order')->where($scan_ordercount_where)->count();
        }
        $rdata['cashdesk_ordercount'] = $cashdesk_ordercount+$cashier_ordercount;//收银台订单数
        $rdata['online_ordercount']   = $shop_ordercount +$scan_ordercount ;//商城+扫码点餐
        $rdata['total_ordercount']    = $cashdesk_ordercount + $shop_ordercount + $scan_ordercount+$cashier_ordercount;
//------------------汇总数据----------------
        $rdata['recharge_show'] =true;
        if($bid>0){
            //如果是商户 不统计 充值金额
            $recharge_total_money = 0;
            $rdata['recharge_show'] =false;
        }
        //营业额汇总  收银机营业额+线上营业额-退款总额
        $yingyee_money =  $today_total_money + $cashier_total_money +  $online_total_money -  $refund_total_money;
        
        $rdata['yingyee_money'] =dd_money_format($yingyee_money); //营业额汇总
        $rdata['all_yingyee_money'] =dd_money_format($yingyee_money+$recharge_total_money - $online_yue_money -$cashier_yue_money); //营业额汇总 （减去线上和线下余额支付）
        //总收款    收银机营业额+线上营业额-会员余额消费+会员储值预付款收款小计-退款总额
        if($search_paytype !=''){
            $online_yue_money = 0;
        }
        $total_in_money =  $today_total_money + $cashier_total_money + $online_total_money - $online_yue_money  + $recharge_total_money - $refund_total_money;
        $rdata['total_in_money'] = dd_money_format($total_in_money - $cashier_yue_money); //减去线下收音机的余额支付
        $rdata['all_total_in_money'] = dd_money_format($total_in_money+$online_yue_money);//包含会员余额消费
        $rdata['all_yue_money'] = dd_money_format($online_yue_money + $cashier_yue_money - $refund_yue_money+$today_yue_money);//总余额消费

        $cashdesk_user = Db::name('admin_user')->where('id',$uid)->value('un');
        //根据设置显示不同的支付信息
        $wxpay_show = true;
        $sxfpay_show = true;
        $alipay_show = true;
        $cashpay_show = true;
        $yuepay_show =  true;
        $douyinhx_show =  true;
        if($cashier['bid'] ==0){
            if(!$cashier['wxpay']){
                $wxpay_show = false;
            }
            if(!$cashier['sxpay']){
                $sxfpay_show = false;
            }
            if(!getcustom('cashdesk_alipay') || !$cashier['alipay']){
                $alipay_show = false;
            }
            if(!$cashier['cashpay']){
                $cashpay_show = false;
            }
            if(!$cashier['cashpay']){
                $yuepay_show = false;
            }
            if(!$cashier['douyinhx']){
                $douyinhx_show = false;
            }
        }else{//bid>0
            $sysset = Db::name('restaurant_admin_set')->where('aid',$aid)->find();
            if(!$sysset['business_cashdesk_wxpay_type']){
                $wxpay_show = false;
            }
            if(!$sysset['business_cashdesk_sxpay_type']){
                $sxfpay_show = false;
            }
            if(!$sysset['business_cashdesk_alipay_type'] ||!getcustom('cashdesk_alipay')){
                $alipay_show = false;
            }
            if(!$sysset['business_cashdesk_cashpay']){
                $cashpay_show = false;
            }
            if(!$sysset['business_cashdesk_yue']){
                $yuepay_show = false;
            }
            if(!$sysset['business_cashdesk_douyinhx']){
                $douyinhx_show = false;
            }
        }
        $rdata['logintime'] = date('Y-m-d H:i:s',$logintime);
        $rdata['jiaobantime'] = date('Y-m-d H:i:s',$jiaobantime);
        $rdata['cashier_info'] =$cashier;
        $rdata['cashdesk_user'] =$cashdesk_user;
        $rdata['wxpay_show'] =$wxpay_show;
        $rdata['sxfpay_show'] =$sxfpay_show;
        $rdata['alipay_show'] =$alipay_show;
        $rdata['cashpay_show'] =$cashpay_show;
        $rdata['yuepay_show'] =$yuepay_show;
        $rdata['douyinhx_show'] =$douyinhx_show;
       
        return  $rdata;
    }

	//酒店订单
    public static function hotel_pay($orderid,$ordernum,$type=0){
        $order = Db::name('hotel_order')->where('id',$orderid)->find();
        $member = Db::name('member')->where('id',$order['mid'])->find();
        $aid = $order['aid'];
        $mid = $order['mid'];
        if($order['use_money']>0 && !$order['use_money_paystaus'] && !$type){
            \app\common\Member::addmoney($aid,$mid,-$order['use_money'],t('余额').'抵扣，订单号: '.$order['ordernum']);
        }
        $room = Db::name('hotel_room')->where('id',$order['roomid'])->find();
        //增加押金记录
        if($order['yajin_money']>0){
            $yjdata = [];
            $yjdata['aid'] = $order['aid'];
            $yjdata['bid'] = $order['bid'];
            $yjdata['hotelid'] = $order['hotelid'];
            $yjdata['roomid'] = $order['roomid'];
            $yjdata['mid'] = $order['mid'];
            $yjdata['orderid'] = $order['id'];
            $yjdata['ordernum'] = $order['ordernum'];
            $yjdata['yajin_money'] = $order['yajin_money'];
            $yjdata['yajin_type'] = $order['yajin_type'];
            $yjdata['refund_money'] = $order['yajin_money'];
            $yjdata['refund_status'] = 0;
            $yjdata['refund_ordernum'] = '' . date('ymdHis') . rand(100000, 999999);
            //$yjdata['apply_time'] = time();
            $yjdata['yd_num'] = $order['totalnum']; //预定人数
            $yajinid = Db::name('hotel_order_yajin')->insertGetId($yjdata);
            //修改关联的押金订单
            Db::name('hotel_order')->where('id',$orderid)->update(['yajin_orderid'=>$yajinid]);
        }
        $couponlist = \app\common\Coupon::getpaygive($aid,$mid,'hotel',$order['totalprice']);
       
        if($couponlist){
            foreach($couponlist as $coupon){
                \app\common\Coupon::send($aid,$mid,$coupon['id']);
            }
        }
        //发货信息录入 微信小程序+微信支付
        if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
            \app\common\Order::wxShipping($order['aid'],$order,'hotel');
        }
        //是否为即时确认
        if($room['qrtype']==1){
            Db::name('hotel_order')->where('id',$orderid)->update(['status'=>2,'confirm_time'=>time()]);
            //发送消息通知
            \app\model\Hotel::sendNotice($aid,$order);
        }
        //加销量
        \app\model\Hotel::addroomsales($order,$order['totalnum']);
        if(getcustom('yx_queue_free_hotel',$aid)){
            \app\custom\QueueFree::join($order,'hotel');
        }
        //短信通知
        $rs = \app\common\Sms::send($aid,$member['tel']?$member['tel']:$order['tel'],'tmpl_orderpay',['ordernum'=>$order['ordernum']]);

        if(getcustom('yx_buyer_subsidy',$aid)){
            $res = \app\custom\Subsidy::caclOrder($aid,$orderid,0,'hotel');
        }
        if(getcustom('yx_mangfan_hotel')) {
            //支付成功计算盲返列表
            if($order['is_mangfan']){
                $order['ogid'] = $order['id'];
                $order['real_totalprice'] = $order['totalprice'];
                $order['type'] = 'hotel';
                $order_goods[] = $order;
                if($order_goods){
                    \app\custom\Mangfan::createRecord($aid, $order['mid'], $order['id'], $order['ordernum'], $order['paytime'], $order_goods,false,0,'hotel');
                }
            }
            \app\custom\Mangfan::sendBonus($order['aid'],$order['mid'],$order['id'],'hotel');
        }
    }



    //鱼塘
    public static function fishpond_pay($orderid){
        $order = Db::name('fishpond_order')->where('id',$orderid)->find();
        $hours = $order['hours'];
        $hours_float = floatval($hours);
        $minutes = (int) ($hours_float * 60);
        $start_time = time();
        $end_time = strtotime("+$minutes minutes", $start_time);
        Db::name('fishpond_order')
            ->where('id',$orderid)
            ->update([
               'starttime' => $start_time,
               'endtime' => $end_time
            ]);

        //修改钓点状态
        Db::name('fishpond_basan')
            ->where('aid',aid)
            ->where('id','in',$order['basanid'])
            ->update([
                'starttime' => $start_time,
                'endtime' => $end_time,
                'status' => 2 //已售出
            ]);

        $set = Db::name('admin_set')->where('aid',$order['aid'])->find();
        if($set['fxjiesuantime'] == 1 && $set['fxjiesuantime_delaydays'] == '0'){
            \app\common\Order::giveCommission($order,'fishpond');
        }
    }

    //修改商城商品订单价格
    public static function changeShopOrderPirce($order,$oglist,$member){
        if(getcustom('member_level_moneypay_price')){
            $orderid = $order['id'];

            //第一时间修改价格
            if(strpos($order['ordernum'],'_')){ //合并支付
                $ordernum = explode('_',$order['ordernum'])[0];
                $payorder = Db::name('payorder')->where('aid',$order['aid'])->where('ordernum',$ordernum)->where('type','like','shop%')->find();
            }else{
                $payorder = Db::name('payorder')->where('aid',$order['aid'])->where('ordernum',$order['ordernum'])->where('type','like','shop%')->find();
            }

            //查询会员价仅限余额支付方式
            if($payorder && $payorder['usemoneypay']>0 && $payorder['moneypaytypeid']>0){
                //如果不是余额支付，则使用普通价格支付
                if($payorder['moneypaytypeid'] != 1 && $order['totalprice'] != $order['totalputongprice']){
                    //更新价格为普通支付价格
                    Db::name('shop_order')->where('id',$order['id'])->update(['totalprice'=>$order['totalputongprice'],'product_price'=>$order['product_putongprice']]);

                    $product_price = $order['product_putongprice'];
                    $leveldk_money = $order['leveldk_money'] && !empty($order['leveldk_money'])?$order['leveldk_money']:0;
                    $coupon_money  = $order['coupon_money'] && !empty($order['coupon_money'])?$order['coupon_money']:0;
                    $scoredk_money = $order['scoredk_money'] && !empty($order['scoredk_money'])?$order['scoredk_money']:0;
                    $manjian_money = $order['manjian_money'] && !empty($order['manjian_money'])?$order['manjian_money']:0;
                    if(getcustom('money_dec')){
                        $dec_money = $order['dec_money'] && !empty($order['dec_money'])?$order['dec_money']:0;
                    }
                    if(getcustom('product_givetongzheng')){
                        $tongzhengdk_money = $order['tongzhengdk_money'] && !empty($order['tongzhengdk_money'])?$order['tongzhengdk_money']:0;
                    }

                    $sysset  = Db::name('admin_set')->where('aid',$order['aid'])->find();
                    $shopset = Db::name('shop_sysset')->where('aid', $order['aid'])->find();
                    if($order['bid']){
                        $bset = Db::name('business_sysset')->where('aid',$order['aid'])->find();
                        $store_info = Db::name('business')->where('aid',$order['aid'])->where('id',$order['bid'])->find();
                    }else{
                        $store_info = Db::name('admin_set')->where('aid',$order['aid'])->find();
                        $bset = [];
                    }
                    if($order['couponrid'] && !empty($order['couponrid'])){
                        $couponrecord = Db::name('coupon_record')->where('id',$order['couponrid'])->find();
                    }else{
                        $couponrecord = [];
                    }

                    $scoremaxtype = 0;//0按系统设置 1商品独立设置
                    $needzkproduct_price = 0;//无会员价、无折扣
                    foreach($oglist as &$og){
                        if($og['totalprice'] == $og['totalputongprice']){
                            continue;
                        }

                        $product = Db::name('shop_product')->where('id',$og['proid'])->find();
                        $guige   = Db::name('shop_guige')->where('id',$og['ggid'])->find();
                        $num     = $og['num'];

                        $product['og_scoredk_money'] = 0;//商品占的积分抵扣数值，默认为0
                        if($product['scoredkmaxset']==0){
                            $is_sysset_scoredk = true;
                            if(getcustom('scoredk_percent_category')){
                                //查找第一个分类
                                $cid_arr = explode(',',$product['cid']);
                                $first_cid = $cid_arr[0];
                                if ($first_cid){
                                    $category_set= Db::name('shop_category')->where('aid',$product['aid'])->where('id',$first_cid)->field('scoredkmaxval,scoredkmaxset')->find();
                                    if($category_set['scoredkmaxset'] ==1) {
                                        $category_scoreval =$category_set['scoredkmaxval'];
                                        if ($category_scoreval > 0 && $category_scoreval < 100) {
                                            $is_sysset_scoredk = false;
                                            $product['og_scoredk_money'] = $category_scoreval * 0.01 * $guige['sell_price'] * $num;
                                        }
                                    }
                                }
                            }
                            if($is_sysset_scoredk) {
                                if ($sysset['scoredkmaxpercent'] == 0) {
                                    $product['og_scoredk_money'] = 0;
                                } else {
                                    if ($sysset['scoredkmaxpercent'] > 0 && $sysset['scoredkmaxpercent'] <= 100) {
                                        $product['og_scoredk_money'] = $sysset['scoredkmaxpercent'] * 0.01 * $guige['sell_price'] * $num;
                                    } else {
                                        $product['og_scoredk_money'] = $guige['sell_price'] * $num;
                                    }
                                }
                            }
                        }elseif($product['scoredkmaxset']==1){
                            $scoremaxtype = 1;
                            $product['og_scoredk_money'] = $product['scoredkmaxval'] * 0.01 * $guige['sell_price'] * $num;
                        }elseif($product['scoredkmaxset']==2){
                            $scoremaxtype = 1;
                            $product['og_scoredk_money'] = $product['scoredkmaxval'] * $num;
                        }else{
                            $scoremaxtype = 1;
                            $product['og_scoredk_money'] = 0;
                        }

                        if($product['lvprice']==0 && $product['no_discount'] == 0){ //未开启会员价
                            $needzkproduct_price += $og['sell_putongprice'] * $og['num'];
                        }

                        $og['product'] = $product;
                        $og['guige']   = $guige;
                    }
                    unset($og);

                    $istc1 = 0; //设置了按单固定提成时 只将佣金计算到第一个商品里
                    $istc2 = 0;
                    $istc3 = 0;
                    foreach($oglist as $og){
                        if($og['totalprice'] == $og['totalputongprice']){
                            continue;
                        }

                        $product = $og['product'];
                        $guige   = $og['guige'];;
                        $num     = $og['num'];

                        $ogdata = [];
                        //$productNums = $productNums+$num;
                        $ogdata['sell_price'] = $og['sell_price'] = $og['sell_putongprice'];
                        $ogdata['totalprice'] = $og['totalputongprice'];

                        if(getcustom('product_weight') && $product['product_type']==2){
                            //称重商品，单价 重量 总价 计算(单位g)
                            $gprice  = $og['sell_price'];
                            $gweight = $og['weight'];
                            $gtotalweight = $og['weight']*$num/500;//总重量 斤
                            if($gweight>0){
                                $gpriceNew = $gprice / $gweight * 500;//化成每斤的价格
                            }else{
                                $gpriceNew = $gprice;
                            }
                            $gtotalprice = round($gpriceNew * $gtotalweight,2);
                            $ogdata['sell_price']      = $gpriceNew;
                            $ogdata['real_sell_price'] = $gpriceNew;
                            $ogdata['totalprice']      = $gtotalprice;
                            $ogdata['real_totalprice'] = $gtotalprice;
                        }

                        $og_totalprice = $ogdata['totalprice'];
                        if($product['balance'] > 0){
                            $og_totalprice = $og_totalprice * (1 - $product['balance']*0.01);
                        }

                        $allproduct_price = $product_price;
                        $og_leveldk_money = 0;
                        $og_coupon_money  = 0;
                        $og_scoredk_money = 0;
                        $og_manjian_money = 0;
                        if(getcustom('money_dec')){
                            $og_dec_money  = 0 ;//余额抵扣比例
                        }
                        if($allproduct_price > 0 && $og_totalprice > 0){
                            if($leveldk_money){
                                //更新order_goods的leveldk_money时，产品不开启会员价的 才更新
                                if($product['lvprice']==0 && $product['no_discount'] == 0){ //未开启会员价
                                    $og_leveldk_money = $og_totalprice / $needzkproduct_price * $leveldk_money;
                                }
                            }
                            if($coupon_money){
                                $og_coupon_money = $og_totalprice / $allproduct_price * $coupon_money;
                            }
                            if($scoredk_money && $scoredk_money>0){
                                //如果积分抵扣都是跟随系统设置
                                if($scoremaxtype == 0){
                                    $og_scoredk_money = $og_totalprice / $allproduct_price * $scoredk_money;
                                //存在单独设置
                                }else{
                                    $og_scoredk_money = $product['og_scoredk_money']??0;
                                    if($og_scoredk_money>=$scoredk_money){
                                        $og_scoredk_money = $scoredk_money;
                                        $scoredk_money    = 0;
                                    }else{
                                        $scoredk_money -= $og_scoredk_money;
                                    }
                                }
                            }
                            if($manjian_money){
                                $og_manjian_money = $og_totalprice / $allproduct_price * $manjian_money;
                            }
                            if(getcustom('money_dec')){
                                if($dec_money){
                                    $og_dec_money = $og_totalprice / $allproduct_price * $dec_money;
                                }
                            }
                            if(getcustom('product_givetongzheng')){
                                $og_tongzhengdk_money = $og_totalprice / $allproduct_price * $tongzhengdk_money;
                                $ogdata['tongzhengdk_money'] = $og_tongzhengdk_money;
                            }
                        }
                        $ogdata['scoredk_money'] = $og_scoredk_money;
                        $ogdata['leveldk_money'] = $og_leveldk_money;
                        $ogdata['manjian_money'] = $og_manjian_money;
                        $ogdata['coupon_money']  = $og_coupon_money;
                        if(getcustom('money_dec')){
                            $ogdata['dec_money'] = $og_dec_money;//余额抵扣比例
                        }

                        if($product['bid'] > 0) {
                            $scoredkmoney = $og_scoredk_money ?? 0;
                            if($bset['scoredk_kouchu'] == 0){ //扣除积分抵扣
                                $scoredkmoney = 0;
                            }
                            $leveldkmoney = $og_leveldk_money;
                            if($bset['leveldk_kouchu'] == 0){ //扣除会员折扣
                                $leveldkmoney = 0;
                            }
                            $totalprice_business = $og_totalprice - $og_manjian_money - $og_coupon_money - $og_leveldk_money;
                            if($bset['scoredk_kouchu']==1){
                                $totalprice_business = $totalprice_business - $og_scoredk_money;
                            }
                            //商品独立费率
                            if($product['feepercent'] != '' && $product['feepercent'] != null && $product['feepercent'] >= 0) {
                                $ogdata['business_total_money'] = $totalprice_business * (100-$product['feepercent']) * 0.01;
                                if(getcustom('business_deduct_cost')){
                                    if($store_info && $store_info['deduct_cost'] == 1 && $og['cost_price']>0){
                                        if($og['cost_price']<=$og['sell_price']){
                                            $all_cost_price = $og['cost_price'];
                                        }else{
                                            $all_cost_price = $og['sell_price'];
                                        }
                                        //扣除成本
                                        $ogdata['business_total_money'] = $totalprice_business - ($totalprice_business-$all_cost_price)*$product['feepercent']/100;
                                    }
                                }
                                if(getcustom('business_fee_type')){
                                    if($bset['business_fee_type'] == 0){
                                        $platformMoney = ($totalprice_business+$order['freight_price']) * $product['feepercent'] * 0.01;
                                        $ogdata['business_total_money'] = $totalprice_business - $platformMoney;
                                    }elseif($bset['business_fee_type'] == 1){
                                        $platformMoney = $totalprice_business * $product['feepercent'] * 0.01;
                                        $ogdata['business_total_money'] = $totalprice_business - $platformMoney;
                                    }elseif($bset['business_fee_type'] == 2){
                                        $platformMoney = $og['cost_price'] * $product['feepercent'] * 0.01;
                                        $ogdata['business_total_money'] = $totalprice_business - $platformMoney;
                                    }
                                }
                            } else {
                                $useStorFee = true;
                                if(getcustom('business_deduct_cost')){
                                    if($store_info && $store_info['deduct_cost'] == 1 && $og['cost_price']>0){
                                        if($og['cost_price']<=$og['sell_price']){
                                            $all_cost_price = $og['cost_price'];
                                        }else{
                                            $all_cost_price = $og['sell_price'];
                                        }
                                        //扣除成本
                                        $ogdata['business_total_money'] = $totalprice_business - ($totalprice_business-$all_cost_price)*$store_info['feepercent']/100;
                                        $useStorFee = false;
                                    }
                                }
                                if(getcustom('business_fee_type')){
                                    if($bset['business_fee_type'] == 0){
                                        $totalprice_business = $totalprice_business + $order['freight_price'];
                                        $platformMoney = $totalprice_business * $store_info['feepercent'] * 0.01;
                                        $ogdata['business_total_money'] = $totalprice_business - $platformMoney;
                                    }if($bset['business_fee_type'] == 1){
                                        $platformMoney = $totalprice_business * $store_info['feepercent'] * 0.01;
                                        $ogdata['business_total_money'] = $totalprice_business - $platformMoney;
                                    }elseif($bset['business_fee_type'] == 2){
                                        $platformMoney = $og['cost_price'] * $store_info['feepercent'] * 0.01;
                                        $ogdata['business_total_money'] = $totalprice_business - $platformMoney;
                                    }
                                    $useStorFee = false;
                                }
                                if($useStorFee){
                                    $ogdata['business_total_money'] = $totalprice_business * (100-$store_info['feepercent']) * 0.01;
                                }
                            }
                        }
                        $og_totalprice = round($og_totalprice,2);
                        if($og_totalprice < 0) $og_totalprice = 0;
                        
                        //新增real_totalmoney,实际支付金额，和分销无关，都更新
                        $og_totalmoney = $og_totalprice - $og_leveldk_money - $og_scoredk_money - $og_manjian_money;
                        if($couponrecord && $couponrecord['type']!=4) {//运费抵扣券
                            $og_totalmoney -= $og_coupon_money;
                        }
                        if($og_totalmoney < 0) $og_totalmoney = 0;
                        //计算商品实际金额  商品金额 - 会员折扣 - 积分抵扣 - 满减抵扣 - 优惠券抵扣
                        //分销结算方式fxjiesuantype，0按商品价格，1按成交价格，2按销售利润
                        if($sysset['fxjiesuantype'] == 1 || $sysset['fxjiesuantype'] == 2){
                            $og_totalprice = $og_totalprice - $og_leveldk_money - $og_scoredk_money - $og_manjian_money;
                            if($couponrecord && $couponrecord['type']!=4) {//运费抵扣券
                                $og_totalprice -= $og_coupon_money;
                            }
                            $og_totalprice = round($og_totalprice,2);
                            if($og_totalprice < 0) $og_totalprice = 0;
                        }
                        if(getcustom('money_dec')){
                            $og_totalprice -= $og_dec_money;//余额抵扣比例
                            $og_totalmoney -= $og_dec_money;//余额抵扣比例
                        }
                        if(getcustom('member_goldmoney_silvermoney')){
                            //抵扣金银值
                            $og_totalprice -= ($ogdata['silvermoneydec']+$ogdata['goldmoneydec']);
                        }
                        $ogdata['real_totalprice'] = $og_totalprice; //实际商品销售金额,和分销结算方式相关
                        $ogdata['real_totalmoney'] = dd_money_format($og_totalmoney); //实际商品销售金额
                        
                        //计算佣金的商品金额
                        $commission_totalprice = $ogdata['totalprice'];
                        if($sysset['fxjiesuantype']==1){ //按成交价格
                            $commission_totalprice = $ogdata['real_totalprice'];
                        }
                        $commission_totalpriceCache = $commission_totalprice;
                        if($sysset['fxjiesuantype']==2){ //按销售利润
                            $commission_totalprice = $ogdata['real_totalprice'] - $og['cost_price'] * $num;
                        }
                        if($commission_totalprice < 0) $commission_totalprice = 0;

                        if(getcustom('price_dollar')){
                            if($shopset['usdrate']>0) $ogdata['usd_sellprice'] = round($og['sell_price']/$shopset['usdrate'],2);
                        }

                        Db::name('shop_order_goods')->where('id',$og['id'])->update($ogdata);
                        $ogid = $og['id'];

                        $parent1 = [];$parent2 = [];$parent3 = [];$parent4 = [];
                        $agleveldata = Db::name('member_level')->where('aid',$order['aid'])->where('id',$member['levelid'])->find();
                        if($agleveldata['can_agent'] > 0 && $agleveldata['commission1own']==1){
                            $member['pid'] = $order['mid'];
                        }
                        if(getcustom('commission_recursion') && $og['isfg']){
                            //首单才发分销奖励
                            $product['commissionset'] = -1;
                        }

                        $totalcommission = 0;
                        if($product['commissionset']!=-1){
                            $ogupdate = [];
                            if(!getcustom('fenxiao_manage')){
                                $sysset['fenxiao_manage_status'] = 0;
                            }
                            if($sysset['fenxiao_manage_status']){
                                $commission_data = \app\common\Fenxiao::fenxiao_jicha($sysset,$member,$product,$num,$commission_totalprice);
                            }else{
                                $commission_data = \app\common\Fenxiao::fenxiao($sysset,$member,$product,$num,$commission_totalprice,$og['isfg'],$istc1,$istc2,$istc3);
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
                            $istc1 = $commission_data['istc1']??0;
                            $istc2 = $commission_data['istc2']??0;
                            $istc3 = $commission_data['istc3']??0;
                            if(getcustom('commission_butie')){
                                $butie_data = [];
                                $butie_data['parent1commission_butie'] = $commission_data['parent1commission_butie']??0;
                                $butie_data['parent2commission_butie'] = $commission_data['parent2commission_butie']??0;
                                $butie_data['parent3commission_butie'] = $commission_data['parent3commission_butie']??0;
                            }
                            Db::name('shop_order_goods')->where('id',$ogid)->update($ogupdate);

                            if($product['commissionset']!=4){
                                if(getcustom('commission_fugou') && $og['isfg'] == 1){

                                    if($ogupdate['parent1'] && ($ogupdate['parent1commission'] || $ogupdate['parent1score'])){
                                        //查询下级复购是否存在，存在更新，不存在添加
                                        $record = Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>t('下级').'复购奖励','status'=>0,'aid'=>$order['aid']])->field('id')->find();
                                        if($record){
                                            Db::name('member_commission_record')->where('id',$record['id'])->update(['mid'=>$ogupdate['parent1'],'commission'=>$ogupdate['parent1commission'],'score'=>$ogupdate['parent1score']]);
                                        }else{
                                            Db::name('member_commission_record')->insert(['aid'=>$order['aid'],'mid'=>$ogupdate['parent1'],'frommid'=>$order['mid'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent1commission'],'score'=>$ogupdate['parent1score'],'remark'=>t('下级').'复购奖励','createtime'=>time()]);
                                        }
                                        $totalcommission += $ogupdate['parent1commission'];
                                    }else{
                                        Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>t('下级').'复购奖励','status'=>0,'aid'=>$order['aid']])->delete();
                                    }

                                    if($ogupdate['parent2'] && ($ogupdate['parent2commission'] || $ogupdate['parent2score'])){
                                        //查询下二级复购是否存在，存在更新，不存在添加
                                        $record2 = Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>t('下二级').'复购奖励','status'=>0,'aid'=>$order['aid']])->field('id')->find();
                                        if($record2){
                                            Db::name('member_commission_record')->where('id',$record2['id'])->update(['mid'=>$ogupdate['parent2'],'commission'=>$ogupdate['parent2commission'],'score'=>$ogupdate['parent2score']]);
                                        }else{
                                            Db::name('member_commission_record')->insert(['aid'=>$order['aid'],'mid'=>$ogupdate['parent2'],'frommid'=>$order['mid'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent2commission'],'score'=>$ogupdate['parent2score'],'remark'=>t('下二级').'复购奖励','createtime'=>time()]);
                                        }
                                        $totalcommission += $ogupdate['parent2commission'];
                                    }else{
                                        Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>t('下二级').'复购奖励','status'=>0,'aid'=>$order['aid']])->delete();
                                    }

                                    if($ogupdate['parent3'] && ($ogupdate['parent3commission'] || $ogupdate['parent3score'])){
                                        //查询下三级复购是否存在，存在更新，不存在添加
                                        $record3 = Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>t('下三级').'复购奖励','status'=>0,'aid'=>$order['aid']])->field('id')->find();
                                        if($record3){
                                            Db::name('member_commission_record')->where('id',$record3['id'])->update(['mid'=>$ogupdate['parent3'],'commission'=>$ogupdate['parent3commission'],'score'=>$ogupdate['parent3score']]);
                                        }else{
                                            Db::name('member_commission_record')->insert(['aid'=>$order['aid'],'mid'=>$ogupdate['parent3'],'frommid'=>$order['mid'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent3commission'],'score'=>$ogupdate['parent3score'],'remark'=>t('下三级').'复购奖励','createtime'=>time()]);
                                        }
                                        $totalcommission += $ogupdate['parent3commission'];
                                    }else{
                                        Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>t('下三级').'复购奖励','status'=>0,'aid'=>$order['aid']])->delete();
                                    }

                                }else{
                                    if($ogupdate['parent1'] && ($ogupdate['parent1commission']>0 || $ogupdate['parent1score']>0)){
                                        $data_c = ['mid'=>$ogupdate['parent1'],'commission'=>$ogupdate['parent1commission'],'score'=>$ogupdate['parent1score']];
                                        if(getcustom('commission_butie')){
                                            $data_c['butie'] = $butie_data['parent1commission_butie'];
                                            $data_c['commission'] =  bcsub($ogupdate['parent1commission'],$butie_data['parent1commission_butie'],2);
                                        }
                                        //查询下级购买商品奖励是否存在，存在更新，不存在添加
                                        $record = Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>t('下级').'购买商品奖励','status'=>0,'aid'=>$order['aid']])->field('id')->find();
                                        if($record){
                                            Db::name('member_commission_record')->where('id',$record['id'])->update($data_c);
                                        }else{
                                            $data_c2 = ['aid'=>$order['aid'],'frommid'=>$order['mid'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>t('下级').'购买商品奖励','createtime'=>time()];
                                            $data_c = array_merge($data_c,$data_c2);
                                            Db::name('member_commission_record')->insert($data_c);
                                        }
                                        $totalcommission += $ogupdate['parent1commission'];
                                    }else{
                                        Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>t('下级').'购买商品奖励','status'=>0,'aid'=>$order['aid']])->delete();
                                    }

                                    if($ogupdate['parent2'] && ($ogupdate['parent2commission']>0 || $ogupdate['parent2score']>0)){
                                        $data_c = ['mid'=>$ogupdate['parent2'],'commission'=>$ogupdate['parent2commission'],'score'=>$ogupdate['parent2score']];
                                        if(getcustom('commission_butie')){
                                            $data_c['butie'] = $butie_data['parent2commission_butie'];
                                            $data_c['commission'] =  bcsub($ogupdate['parent2commission'],$butie_data['parent2commission_butie'],2);
                                        }
                                        //查询下二级购买商品奖励是否存在，存在更新，不存在添加
                                        $record2 = Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>t('下二级').'购买商品奖励','status'=>0,'aid'=>$order['aid']])->field('id')->find();
                                        if($record2){
                                            Db::name('member_commission_record')->where('id',$record2['id'])->update($data_c);
                                        }else{
                                            $data_c2 = ['aid'=>$order['aid'],'frommid'=>$order['mid'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>t('下二级').'购买商品奖励','createtime'=>time()];
                                            $data_c = array_merge($data_c,$data_c2);
                                            Db::name('member_commission_record')->insert($data_c);
                                        }
                                        $totalcommission += $ogupdate['parent2commission'];
                                    }else{
                                        Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>t('下二级').'购买商品奖励','status'=>0,'aid'=>$order['aid']])->delete();
                                    }

                                    if($ogupdate['parent3'] && ($ogupdate['parent3commission']>0 || $ogupdate['parent3score']>0)){
                                        $data_c = ['mid'=>$ogupdate['parent3'],'commission'=>$ogupdate['parent3commission'],'score'=>$ogupdate['parent3score']];
                                        if(getcustom('commission_butie')){
                                            $data_c['butie'] = $butie_data['parent3commission_butie'];
                                            $data_c['commission'] =  bcsub($ogupdate['parent3commission'],$butie_data['parent3commission_butie'],2);
                                        }
                                        //查询下三级购买商品奖励是否存在，存在更新，不存在添加
                                        $record3 = Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>t('下三级').'购买商品奖励','status'=>0,'aid'=>$order['aid']])->field('id')->find();
                                        if($record3){
                                            Db::name('member_commission_record')->where('id',$record3['id'])->update($data_c);
                                        }else{
                                            $data_c2 = ['aid'=>$order['aid'],'frommid'=>$order['mid'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>t('下三级').'购买商品奖励','createtime'=>time()];
                                            $data_c = array_merge($data_c,$data_c2);
                                            Db::name('member_commission_record')->insert($data_c);
                                        }
                                        $totalcommission += $ogupdate['parent3commission'];
                                    }else{
                                        Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>t('下三级').'购买商品奖励','status'=>0,'aid'=>$order['aid']])->delete();
                                    }

                                    if($ogupdate['parent4'] && ($ogupdate['parent4commission']>0)){
                                        $remark = '持续推荐奖励';
                                        if(getcustom('commission_parent_pj_stop') || getcustom('commission_parent_pj_by_buyermid')){
                                            $remark = '平级奖';
                                        }
                                        $record4 = Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>$remark,'status'=>0,'aid'=>$order['aid']])->field('id')->find();
                                        //查询奖励是否存在，存在更新，不存在添加
                                        if($record4){
                                            Db::name('member_commission_record')->where('id',$record4['id'])->update(['mid'=>$ogupdate['parent4'],'commission'=>$ogupdate['parent4commission'],'score'=>0]);
                                        }else{
                                            $data_c = ['aid'=>$order['aid'],'mid'=>$ogupdate['parent4'],'frommid'=>$order['mid'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent4commission'],'score'=>0,'remark'=>$remark,'createtime'=>time()];
                                            Db::name('member_commission_record')->insert($data_c);
                                        }
                                        $totalcommission += $ogupdate['parent4commission'];
                                    }else{
                                        Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>'持续推荐奖励','status'=>0,'aid'=>$order['aid']])->delete();
                                    }
                                }

                                if($order['checkmemid']){
                                    if($commission_totalprice > 0){
                                        $checkmember = Db::name('member')->where('aid',$order['aid'])->where('id',$order['checkmemid'])->find();
                                        if($checkmember){
                                            $buyselect_commission = Db::name('member_level')->where('id',$checkmember['levelid'])->value('buyselect_commission');
                                            $checkmemcommission = $buyselect_commission * $commission_totalprice * 0.01;
                                            Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>'购买商品时指定奖励','mid'=>$checkmember['id'],'status'=>0,'aid'=>$order['aid']])->update(['commission'=>$checkmemcommission,'score'=>0]);
                                        }else{
                                            Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>'购买商品时指定奖励','mid'=>$checkmember['id'],'status'=>0,'aid'=>$order['aid']])->delete();
                                        }
                                    }else{
                                        Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>'购买商品时指定奖励','mid'=>$order['checkmemid'],'status'=>0,'aid'=>$order['aid']])->delete();
                                    }
                                }
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
                                    $parentList = \app\common\Member::queryOriginPath($order['aid'],$order['mid'],$product['lvprice_jicha_lv']?:50);
                                }else{
                                    $parentList = Db::name('member')->where('id','in',$member['path'])->order(Db::raw('field(id,'.$member['path'].')'))->select()->toArray();
                                    $parentList = array_reverse($parentList);
                                }
                                if($parentList) {
                                    $lvprice_data  = json_decode($guige['lvprice_data'], true);
                                    $nowprice      = $commission_totalpriceCache;
                                    $giveidx       = 0;
                                    $isclose_jicha = 0;
                                    if (getcustom('member_level_close_jicha')){
                                        if($agleveldata['isclose_jicha']==1) $isclose_jicha = 1;
                                    }
                                    foreach($parentList as $k=>$parent){
                                        $deljicha = true;//是否删除极差
                                        $delpj    = true;//是否删除平级
                                        $cansend = true;//是否可以发放
                                        if(getcustom('lvprice_jicha_lv')){
                                            //发放代数限制
                                            if($product['lvprice_jicha_lv']>0 && $giveidx>=$product['lvprice_jicha_lv']){
                                                $cansend = false;
                                            }
                                        }

                                        if($cansend && $parent['levelid'] && $lvprice_data[$parent['levelid']]){
                                            $thisprice = floatval($lvprice_data[$parent['levelid']]) * $num;
                                            if($nowprice > $thisprice){
                                                $commission = $nowprice - $thisprice;
                                                $nowprice   = $thisprice;
                                                $giveidx++;
                                                if(!$isclose_jicha) {
                                                    //查询极差奖励是否存在，存在更新，不存在添加
                                                    $record_jicha = Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>t('下级') . '购买商品差价','mid'=>$parent['id'],'status'=>0,'aid'=>$order['aid']])->field('id')->find();
                                                    if($record_jicha){
                                                        Db::name('member_commission_record')->where('id',$record_jicha['id'])->update(['commission' => $commission,'score' => 0]);
                                                    }else{
                                                        Db::name('member_commission_record')->insert(['aid' => $order['aid'], 'mid' => $parent['id'], 'frommid' => $order['mid'], 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $commission, 'score' => 0, 'remark' => t('下级') . '购买商品差价', 'createtime' => time()]);
                                                    }
                                                    $deljicha = false;
                                                }
                                                //平级奖
                                                if(getcustom('commission_parent_pj') && getcustom('commission_parent_pj_jicha')){
                                                    if($parentList[$k+1] && $parentList[$k+1]['levelid'] == $parent['levelid']){
                                                        $parent2   = $parentList[$k+1];
                                                        $parent2lv = Db::name('member_level')->where('id',$parent2['levelid'])->find();
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
                                                                //查询平级是否存在，存在更新，不存在添加
                                                                $record_pingji = Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>'平级奖励','mid'=>$parent2['id'],'status'=>0,'aid'=>$order['aid']])->field('id')->find();
                                                                if($record_pingji){
                                                                    Db::name('member_commission_record')->where('id',$record_pingji['id'])->update(['commission'=>$pingjicommission,'score'=>0]);
                                                                }else{
                                                                    Db::name('member_commission_record')->insert(['aid'=>$order['aid'],'mid'=>$parent2['id'],'frommid'=>$parent['id'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$pingjicommission,'score'=>0,'remark'=>'平级奖励','createtime'=>time()]);
                                                                }
                                                                $delpj = false;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        if($deljicha){
                                            //删除极差
                                            Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>t('下级') . '购买商品差价','mid'=>$parent['id'],'status'=>0,'aid'=>$order['aid']])->delete();
                                        }
                                        if($delpj){
                                            //删除平级
                                            if(getcustom('commission_parent_pj') && getcustom('commission_parent_pj_jicha')){
                                                $parent2   = $parentList[$k+1];
                                                Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','remark'=>'平级奖励','mid'=>$parent2['id'],'status'=>0,'aid'=>$order['aid']])->delete();
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
    }

    //修改餐饮订单价格
    public static function changeRestaurantOrderPirce($type,$order,$oglist,$member){
        if(getcustom('member_level_moneypay_price')){
            $orderid = $order['id'];

            //第一时间修改价格
            if(strpos($order['ordernum'],'_')){ //合并支付
                $ordernum = explode('_',$order['ordernum'])[0];
                $payorder = Db::name('payorder')->where('aid',$order['aid'])->where('ordernum',$ordernum)->where('type','like',$type.'%')->find();
            }else{
                $payorder = Db::name('payorder')->where('aid',$order['aid'])->where('ordernum',$order['ordernum'])->where('type','like',$type.'%')->find();
            }

            //查询会员价仅限余额支付方式
            if($payorder && $payorder['usemoneypay']>0 &&  $payorder['moneypaytypeid']>0){
                //如果不是余额支付，则使用普通价格支付
                if($payorder['moneypaytypeid'] != 1 && $order['totalprice'] != $order['totalputongprice']){
                    //更新价格为普通支付价格
                    Db::name($type.'_order')->where('id',$order['id'])->update(['totalprice'=>$order['totalputongprice'],'product_price'=>$order['product_putongprice']]);

                    $product_price = $order['product_putongprice'];
                    $leveldk_money = $order['leveldk_money'] && !empty($order['leveldk_money'])?$order['leveldk_money']:0;
                    $coupon_money  = $order['coupon_money'] && !empty($order['coupon_money'])?$order['coupon_money']:0;
                    $scoredk_money = $order['scoredk_money'] && !empty($order['scoredk_money'])?$order['scoredk_money']:0;
                    $manjian_money = $order['manjian_money'] && !empty($order['manjian_money'])?$order['manjian_money']:0;

                    $sysset  = Db::name('admin_set')->where('aid',$order['aid'])->find();
                    $shopset = Db::name('shop_sysset')->where('aid',$order['aid'])->find();
                    if($order['bid']){
                        $bset = Db::name('business_sysset')->where('aid',$order['aid'])->find();
                    }else{
                        $bset = [];
                    }
                    if(!empty($order['couponrid'])){
                        $couponrecord = Db::name('coupon_record')->where('id',$order['couponrid'])->find();
                    }else{
                        $couponrecord = [];
                    }

                    //点餐
                    if($type == 'restaurant_shop' || $type == 'restaurant_takeaway'){
                        $istc1 = 0; //设置了按单固定提成时 只将佣金计算到第一个菜品里
                        $istc2 = 0;
                        $istc3 = 0;
                        foreach($oglist as $og){
                            $product = Db::name('restaurant_product')->where('id',$og['proid'])->find();
                            //$guige   = Db::name('restaurant_product_guige')->where('id',$og['ggid'])->find();
                            $num     = $og['num'];

                            $ogdata = [];
                            $ogdata['sell_price'] = $og['sell_price'] = $og['sell_putongprice'];
                            $ogdata['totalprice'] = $og['totalputongprice'];

                            $og_totalprice = $ogdata['totalprice'];
                            //计算菜品实际金额  菜品金额 - 会员折扣 - 积分抵扣 - 满减抵扣 - 优惠券抵扣
                            if($sysset['fxjiesuantype'] == 1 || $sysset['fxjiesuantype'] == 2){
                                $allproduct_price = $product_price;
                                $og_leveldk_money = 0;
                                $og_coupon_money  = 0;
                                $og_scoredk_money = 0;
                                $og_manjian_money = 0;
                                if($allproduct_price > 0 && $og_totalprice > 0){
                                    if($leveldk_money){
                                        $og_leveldk_money = $og_totalprice / $allproduct_price * $leveldk_money;
                                    }
                                    if($coupon_money){
                                        $og_coupon_money = $og_totalprice / $allproduct_price * $coupon_money;
                                    }
                                    if($scoredk_money){
                                        $og_scoredk_money = $og_totalprice / $allproduct_price * $scoredk_money;
                                    }
                                    if($manjian_money){
                                        $og_manjian_money = $og_totalprice / $allproduct_price * $manjian_money;
                                    }
                                }
                                $og_totalprice = $og_totalprice - $og_leveldk_money - $og_coupon_money - $og_scoredk_money - $og_manjian_money;
                                if($type == 'restaurant_takeaway' && $couponrecord['type']!=4) {//运费抵扣券
                                    $og_totalprice -= $og_coupon_money;
                                }
                                $og_totalprice = round($og_totalprice,2);
                                if($og_totalprice < 0) $og_totalprice = 0;
                            }
                            $ogdata['real_totalprice'] = $og_totalprice; //实际菜品销售金额

                            //计算佣金的菜品金额
                            $commission_totalprice = $ogdata['totalprice']; 
                            if($sysset['fxjiesuantype'] == 1){ //按成交价格
                                $commission_totalprice = $ogdata['real_totalprice'];
                                if($commission_totalprice < 0) $commission_totalprice = 0;
                            }
                            if($sysset['fxjiesuantype']==2){ //按利润提成
                                $commission_totalprice = $ogdata['real_totalprice'] - $og['cost_price'] * $num;
                                if($commission_totalprice < 0) $commission_totalprice = 0;
                            }
                            if($commission_totalprice < 0) $commission_totalprice = 0;

                            $agleveldata = Db::name('member_level')->where('aid',$order['aid'])->where('id',$member['levelid'])->find();
                            if($agleveldata['can_agent'] > 0 && $agleveldata['commission1own']==1){
                                $member['pid'] = $member['id'];
                            }
                            if($product['commissionset']!=-1){
                                if($member['pid']){
                                    $parent1 = \app\custom\Restaurant::getParentWithLevel($order['aid'], $member['pid']);
                                    if($parent1 && $parent1['levelData']['can_agent'] != 0){
                                        $ogdata['parent1'] = $parent1['id'];
                                    }
                                }
                                if($parent1['pid']){
                                    $parent2 = \app\custom\Restaurant::getParentWithLevel($order['aid'], $parent1['pid']);
                                    if($parent2 && $parent2['levelData']['can_agent'] > 1){
                                        $ogdata['parent2'] = $parent2['id'];
                                    }
                                }
                                if($parent2['pid']){
                                    $parent3 = \app\custom\Restaurant::getParentWithLevel($order['aid'], $parent2['pid']);
                                    if($parent3 && $parent3['levelData']['can_agent'] > 2){
                                        $ogdata['parent3'] = $parent3['id'];
                                    }
                                }
                                if($product['commissionset']==1){//按菜品设置的分销比例
                                    $commissiondata = json_decode($product['commissiondata1'],true);
                                    if($commissiondata){
                                        if($ogdata['parent1']) $ogdata['parent1commission'] = $commissiondata[$parent1['levelData']['id']]['commission1'] * $commission_totalprice * 0.01;
                                        if($ogdata['parent2']) $ogdata['parent2commission'] = $commissiondata[$parent2['levelData']['id']]['commission2'] * $commission_totalprice * 0.01;
                                        if($ogdata['parent3']) $ogdata['parent3commission'] = $commissiondata[$parent3['levelData']['id']]['commission3'] * $commission_totalprice * 0.01;
                                    }
                                }elseif($product['commissionset']==2){//按固定金额
                                    $commissiondata = json_decode($product['commissiondata2'],true);
                                    if($commissiondata){
                                        if($ogdata['parent1']) $ogdata['parent1commission'] = $commissiondata[$parent1['levelData']['id']]['commission1'] * $num;
                                        if($ogdata['parent2']) $ogdata['parent2commission'] = $commissiondata[$parent2['levelData']['id']]['commission2'] * $num;
                                        if($ogdata['parent3']) $ogdata['parent3commission'] = $commissiondata[$parent3['levelData']['id']]['commission3'] * $num;
                                    }
                                }elseif($product['commissionset']==3){//提成是积分
                                    $commissiondata = json_decode($product['commissiondata3'],true);
                                    if($commissiondata){
                                        if($ogdata['parent1']) $ogdata['parent1score'] = $commissiondata[$parent1['levelData']['id']]['commission1'] * $num;
                                        if($ogdata['parent2']) $ogdata['parent2score'] = $commissiondata[$parent2['levelData']['id']]['commission2'] * $num;
                                        if($ogdata['parent3']) $ogdata['parent3score'] = $commissiondata[$parent3['levelData']['id']]['commission3'] * $num;
                                    }
                                }else{ //按会员等级设置的分销比例
                                    if($ogdata['parent1']){
                                        if($parent1['levelData']['commissiontype']==1){ //固定金额按单
                                            if($istc1==0){
                                                $ogdata['parent1commission'] = $parent1['levelData']['commission1'];
                                                $istc1 = 1;
                                            }
                                        }else{
                                            $ogdata['parent1commission'] = $parent1['levelData']['commission1'] * $commission_totalprice * 0.01;
                                        }
                                    }
                                    if($ogdata['parent2']){
                                        if($parent2['levelData']['commissiontype']==1){
                                            if($istc2==0){
                                                $ogdata['parent2commission'] = $parent2['levelData']['commission2'];
                                                $istc2 = 1;
                                            }
                                        }else{
                                            $ogdata['parent2commission'] = $parent2['levelData']['commission2'] * $commission_totalprice * 0.01;
                                        }
                                    }
                                    if($ogdata['parent3']){
                                        if($parent3['levelData']['commissiontype']==1){
                                            if($istc3==0){
                                                $ogdata['parent3commission'] = $parent3['levelData']['commission3'];
                                                $istc3 = 1;
                                            }
                                        }else{
                                            $ogdata['parent3commission'] = $parent3['levelData']['commission3'] * $commission_totalprice * 0.01;
                                        }
                                    }
                                }
                            }

                            Db::name($type.'_order_goods')->where('id',$og['id'])->update($ogdata);
                            $ogid = $og['id'];

                            $ogids[] = $ogid;
                            if($ogdata['parent1'] && ($ogdata['parent1commission'] > 0 || $ogdata['parent1score'] > 0)){
                                //查询下级购买菜品奖励是否存在，存在更新，不存在添加
                                $record = Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>$type,'remark'=>'下级购买菜品奖励','status'=>0,'aid'=>$order['aid']])->field('id')->find();
                                if($record){
                                    Db::name('member_commission_record')->where('id',$record['id'])->update(['mid'=>$ogdata['parent1'],'commission'=>$ogdata['parent1commission'],'score'=>$ogdata['parent1score']]);
                                }else{
                                    Db::name('member_commission_record')->insert(['aid'=>$order['aid'],'mid'=>$ogdata['parent1'],'frommid'=>$order['mid'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'restaurant_shop','commission'=>$ogdata['parent1commission'],'score'=>$ogdata['parent1score'],'remark'=>'下级购买菜品奖励','createtime'=>time()]);
                                }
                            }else{
                                Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>$type,'remark'=>'下级购买菜品奖励','status'=>0,'aid'=>$order['aid']])->delete();
                            }

                            if($ogdata['parent2'] && ($ogdata['parent2commission'] || $ogdata['parent2score'])){
                                //查询下二级购买菜品奖励是否存在，存在更新，不存在添加
                                $record2 = Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>$type,'remark'=>'下二级购买菜品奖励','status'=>0,'aid'=>$order['aid']])->field('id')->find();
                                if($record2){
                                    Db::name('member_commission_record')->where('id',$record2['id'])->update(['mid'=>$ogdata['parent2'],'commission'=>$ogdata['parent2commission'],'score'=>$ogdata['parent2score']]);
                                }else{
                                    Db::name('member_commission_record')->insert(['aid'=>$order['aid'],'mid'=>$ogdata['parent2'],'frommid'=>$order['mid'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'restaurant_shop','commission'=>$ogdata['parent2commission'],'score'=>$ogdata['parent2score'],'remark'=>'下二级购买菜品奖励','createtime'=>time()]);
                                }
                            }else{
                                Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>$type,'remark'=>'下二级购买菜品奖励','status'=>0,'aid'=>$order['aid']])->delete();
                            }

                            if($ogdata['parent3'] && ($ogdata['parent3commission'] || $ogdata['parent3score'])){
                                //查询下三级购买菜品奖励是否存在，存在更新，不存在添加
                                $record3 = Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>$type,'remark'=>'下三级购买菜品奖励','status'=>0,'aid'=>$order['aid']])->field('id')->find();
                                if($record3){
                                    Db::name('member_commission_record')->where('id',$record3['id'])->update(['mid'=>$ogdata['parent3'],'commission'=>$ogdata['parent3commission'],'score'=>$ogdata['parent3score']]);
                                }else{
                                    Db::name('member_commission_record')->insert(['aid'=>$order['aid'],'mid'=>$ogdata['parent3'],'frommid'=>$order['mid'],'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'restaurant_shop','commission'=>$ogdata['parent3commission'],'score'=>$ogdata['parent3score'],'remark'=>'下三级购买菜品奖励','createtime'=>time()]);
                                }
                            }else{
                                Db::name('member_commission_record')->where(['orderid'=>$orderid,'ogid'=>$ogid,'type'=>$type,'remark'=>'下三级购买菜品奖励','status'=>0,'aid'=>$order['aid']])->delete();
                            }
                        }
                    }
                }
            }
        }
    }
    public static function product_thali_pay($orderid){
        if(getcustom('product_thali')){
            $order = Db::name('product_thali_order')->where('id',$orderid)->find();
            $aid = $order['aid'];
            $mid = $order['mid'];
            Db::name('product_thali_order_goods')->where('orderid',$orderid)->update(['status'=>1]);
            //自动发货
            if($order['freight_type']==3){
                Db::name('product_thali_order')->where('id',$order['id'])->update(['status'=>2,'send_time'=>time()]);
                Db::name('product_thali_order_goods')->where('orderid',$order['id'])->update(['status'=>2]);
            }
            if($order['platform'] == 'toutiao'){
                \app\common\Ttpay::pushorder($aid,$order['ordernum'],1);
            }
            \app\common\Member::uplv($aid,$mid);
        }
    }
    //车辆订单
    public static function car_pay($orderid){
        $car_order = Db::name('car_order')->where('id',$orderid)->find();
        $newinfo = json_decode($car_order['newinfo'],ture);
        if($car_order['type'] == 0){// 编辑
            Db::name('car')->where('aid',$car_order['aid'])->where('id',$car_order['car_id'])->update($newinfo);
        }else{//添加
            Db::name('car')->insert($newinfo);
        }   
        
    }

    //购买金币订单
    public static function buy_gold_pay($orderid){
        $order = Db::name('buy_gold_order')->where('id',$orderid)->find();
        $aid = $order['aid'];
        $mid = $order['mid'];
        //增加奖金池
        $res = \app\custom\BonusPoolGold::addbonuspool($aid,$mid,$order['real_buy_num'],'在线购买','buy_gold_order',$orderid,0,$order['gold_num']);
        //增加金币
        $res = \app\custom\BonusPoolGold::addgold($aid,$mid,$order['gold_num'],'在线购买','buy_gold_order',$orderid,1);
//        //计算金币价格
//        \app\custom\BonusPoolGold::addgold($aid,$mid,$order['gold_num'],$order['buy_num'],'在线购买','buy_gold_order',$orderid);
    }

    //现金支付比例分销
    public function cashPayCommission($order, $oglist) {
        if (getcustom('shop_cash_commission')) {
            $notPayType = [1,4,38,71,91];
            //判断非现金支付订单
            if (in_array($order['paytypeid'], $notPayType)) {
                foreach ($oglist as $key => $og) {
                    if (empty($og['parent1commission']) && empty($og['parent2commission']) && empty($og['parent3commission'])) {
                        continue;
                    }

                    $product = Db::name('shop_product')->field('commissionset, cash_commission_status')->where('id', $og['proid'])->find();
                    //商品优先级高于会员等级 关闭后直接清空跳过
                    if ($product['cash_commission_status'] == 0) {
                        continue;
                    }
                    if ($product['commissionset'] == 0) {
                        $parent = [
                            $og['parent1'],
                            $og['parent2'],
                            $og['parent3']
                        ];

                        $parent = array_filter($parent, function($value) {
                            return !empty($value);
                        });

                        if (!empty($parent)) {
                            $levelid = Db::name('member')->where('id', 'in', $parent)->column('levelid', 'id');

                            if (!empty($levelid)) {
                                $levelData = Db::name('member_level')->where('id', 'in', array_values($levelid))->column('cash_commission_status, commissiontype', 'id');
                                $level = [];
                                foreach ($levelid as $memberId => $levelId) {
                                    if (isset($levelData[$levelId])) {
                                        $level[$memberId] = $levelData[$levelId];
                                    }
                                }
                                //按会员等级判断是否开启现金支付比例分销 commissiontype:提成方式百分比
                                if ($og['parent1'] && $level[$og['parent1']]['cash_commission_status'] == 1 && $level[$og['parent1']]['commissiontype'] == 0) {
                                    Db::name('shop_order_goods')->where('id', $og['id'])->update(['parent1' => 0, 'parent1commission' => 0]);
                                    Db::name('member_commission_record')->where('ogid',$og['id'])->where('orderid',$og['orderid'])->where('mid',$og['parent1'])->delete();
                                }
                                if ($og['parent2'] && $level[$og['parent2']]['cash_commission_status'] == 1 && $level[$og['parent2']]['commissiontype'] == 0) {
                                    Db::name('shop_order_goods')->where('id', $og['id'])->update(['parent2' => 0, 'parent2commission' => 0]);
                                    Db::name('member_commission_record')->where('ogid',$og['id'])->where('orderid',$og['orderid'])->where('mid',$og['parent2'])->delete();
                                }
                                if ($og['parent3'] && $level[$og['parent3']]['cash_commission_status'] == 1 && $level[$og['parent3']]['commissiontype'] == 0) {
                                    Db::name('shop_order_goods')->where('id', $og['id'])->update(['parent3' => 0, 'parent3commission' => 0]);
                                    Db::name('member_commission_record')->where('ogid',$og['id'])->where('orderid',$og['orderid'])->where('mid',$og['parent3'])->delete();
                                }
                            }
                        }
                    }else{
                        Db::name('shop_order_goods')->where('id', $og['id'])->update([
                            'parent1' => 0,
                            'parent2' => 0,
                            'parent3' => 0,
                            'parent1commission' => 0,
                            'parent2commission' => 0,
                            'parent3commission' => 0,
                        ]);
                        Db::name('member_commission_record')->where('ogid',$og['id'])->where('orderid',$og['orderid'])->delete();
                    }
                }
            }
        }
    }

    public function hanglvfeike_pay($orderid){
        if(getcustom('extend_hanglvfeike')){

            $order = Db::name('hanglvfeike_order')->where('id',$orderid)->find();
            Db::name('hanglvfeike_order_goods')->where('orderid',$orderid)->where('status',0)->update(['status'=>1,'paytime'=>$order['paytime']]);
            
            //航旅飞客下单进行订单支付
            $goodsType = $order['ischange'] == 1?1:0;//支付商品类型：0 机票；1改签
            if($goodsType == 1){
                $transationOrderNo = $order['changeOrderNo'];
            }else{
                $transationOrderNo = $order['transationOrderNo'];
            }
            $params=['orderid'=>$orderid,'ordernum'=>$order['ordernum'],'order'=>$order,'amount'=>$order['amount'],'transationOrderNo'=>$transationOrderNo,'goodsType'=>$goodsType];
            \app\custom\Hanglvfeike::pay($order['aid'],$params);
        }
    }

    public function zhiyoubao_pay($orderid){
        if(getcustom('extend_zhiyoubao_theater')){

            $order = Db::name('zhiyoubao_order')->where('id',$orderid)->find();
            Db::name('zhiyoubao_order_goods')->where('orderid',$orderid)->where('status',0)->update(['status'=>1,'paytime'=>$order['paytime']]);
            Db::name('zhiyoubao_order_goods_certs')->where('orderid',$orderid)->where('status',0)->update(['status'=>1,'paytime'=>$order['paytime']]);
            $params=['orderid'=>$orderid,'ordernum'=>$order['ordernum'],'order'=>$order];
            \app\custom\Zhiyoubao::createPayOrder($order['aid'],$params);
        }
    }

	public function meituan_pay($orderid){
		if(getcustom('meituan_xinyoujie')){
			$order = Db::name('meituan_order')->where('id',$orderid)->find();
			Db::name('meituan_order')->where('id',$orderid)->where('status',0)->update(['status'=>1,'paytime'=>time()]);
			$mt = new \app\custom\Meituan($order['aid']);
			$mt->orderPayment($order);
		}
	}

    public static function xianjin_recharge_pay($orderid){
        if(getcustom('commission_xianjin_percent')){
            //现金充值订单
            $order = Db::name('xianjin_recharge_order')->where('id',$orderid)->find();
            $member = Db::name('member')->where('id',$order['mid'])->find();
            $aid = $order['aid'];
            $mid = $order['mid'];

            //充值赠送
            $giveset = Db::name('xianjin_recharge_giveset')->where('aid',$aid)->find();
            if($giveset && $giveset['status']==1){
                $givedata = json_decode($giveset['givedata'],true);
            }else{
                $givedata = array();
            }
            $givemoney = 0;
            $givescore = 0;
            $moneyduan = 0;

            if($givedata){
                foreach($givedata as $give){
                    if($order['money']*1 >= $give['money']*1 && $give['money']*1 > $moneyduan){
                        $moneyduan = $give['money']*1;
                        $givemoney = $give['give']*1;
                        $givescore = $give['give_score']*1;
                    }
                }
            }

            //是否直接增加充值
            $addmoney = true;
            if($addmoney){
                \app\custom\MemberCustom::addXianjin($aid,$mid,$order['money'],t('现金').'充值');
            }

            if($givemoney > 0){
                \app\custom\MemberCustom::addXianjin($aid,$mid,$givemoney,'充值赠送');
            }

            if($givescore > 0){
                \app\common\Member::addscore($aid,$mid,$givescore,'充值赠送');
            }

            //支付后送券
            $couponlist = \app\common\Coupon::getpaygive($aid,$mid,'xianjin_recharge',$order['money']);
            if($couponlist){
                foreach($couponlist as $coupon){
                    \app\common\Coupon::send($aid,$mid,$coupon['id']);
                }
            }

            //升级
            \app\common\Member::uplv($aid,$mid);

            //发货信息录入 微信小程序+微信支付
            if($order['platform'] == 'wx' && $order['paytypeid'] == 2){
                \app\common\Order::wxShipping($order['aid'],$order,'xianjin_recharge');
            }

            $tmplcontent = array();
            $tmplcontent['first'] = '有新'.t('现金').'充值订单支付成功';
            $tmplcontent['remark'] = '点击进入查看~';
            $tmplcontent['accountType'] = t('会员').'昵称';
            $tmplcontent['account'] = $member['nickname'];
            $tmplcontent['amount'] = $order['money'].'元' . ($givemoney>0?'+赠送'.$givemoney.'元':'');
            $tmplcontent['result'] = '充值成功';
            $mdid = $order['mdid']??0;
            \app\common\Wechat::createSendTmplData($aid,0,0,'tmpl_recharge',$tmplcontent,'adminExt/finance/xianjinrechargelog',$mdid,'','sendhttmpl');
        }
    }
    
    public static function deposit_pay($orderid){
        if(getcustom('deposit')){
            $order = Db::name('deposit_order')->where('id',$orderid)->find();
            //更改押金
            \app\common\Member::addDeposit($order['aid'],$order['bid'],$order['mid'],$order['num'],$order['deposit_id'],'购买'.t('押金').'，订单号：'.$order['ordernum'],['orderid' => $orderid]);
            if(getcustom('deposit_business')){
                if($order['bid'] > 0){
                    \app\common\Business::addDeposit($order['aid'],$order['bid'], $order['totalprice'],$order['deposit_id'],'购买'.t('押金').'订单，订单号：'.$order['ordernum'],'deposit');
                }
            }
        }
    }

    public static function water_happyti_pay($orderid){
        if(getcustom('water_happy_ti')){
            $order = Db::name('water_happyti_order')->where('id',$orderid)->find();
            $aid = $order['aid'];
            $bid = $order['bid'];
            $mid = $order['mid'];

            try {
                $waterHappyti_new = new \app\custom\WaterHappyti($aid,$bid);
            }catch (\Exception $e){
                return $e->getMessage();
            }

            //推送打水订单
            $res = $waterHappyti_new->addorder([
                'money'=>$order['product_price'],
                'mid'=>$mid,
                'outlet_num'=>$order['outlet_num'],
                'ordernum'=>$order['ordernum'],
                'deviceId'=>$order['deviceId'],
                'location'=>$order['location'],
            ]);

            Db::name('water_happyti_order')->where('id',$orderid)->update(['notify'=>jsonEncode($res)]);

            if(!$res || $res['status'] != 1){
                //第三方订单推送失败

                //退款
                $rs = \app\common\Order::refund($order,$order['totalprice'],'打水订单推送失败，自动退款');
                if($rs['status']==1){
                    Db::name('water_happyti_order')->where('id',$orderid)->where('aid',$aid)->where('bid',$order['bid'])->update(['status'=>4,'refund_money' => $order['totalprice'],'refund_time' => time()]);

                    //积分抵扣的返还
                    if($order['scoredkscore'] > 0){
                        \app\common\Member::addscore($aid,$order['mid'],$order['scoredkscore'],'打水订单退款返还');
                    }
                    //扣除消费赠送积分
                    \app\common\Member::decscorein($aid,'water_happyti',$order['id'],$order['ordernum'],'打水订单退款扣除消费赠送');

                    //查询后台是否开启退还已使用的优惠券
                    $return_coupon = Db::name('shop_sysset')->where('aid',$aid)->value('return_coupon');

                    //优惠券退还
                    if($return_coupon && $order['coupon_rid'] > 0){
                        \app\common\Coupon::refundCoupon($aid,$order['mid'],$order['coupon_rid'],$order);
                    }
                }

            }

        }
    }

    public static function business_withdrawfee_cash_pay($orderid){
        if(getcustom('business_withdraw_cash_mobile')){
            $order = Db::name('business_withdrawfee_cash_order')->where('id',$orderid)->find();
            $aid = $order['aid'];
            $bid = $order['bid'];
            $mid = $order['mid'];

            $business_withdrawlog = Db::name('business_withdrawlog')->where('aid',$aid)->where('bid',$bid)->where('payorderid',$order['payorderid'])->find();
            if($business_withdrawlog['status'] == 20){
                $b_res = \app\common\Business::addmoney($aid,$bid,-$business_withdrawlog['money'],'余额提现',false,'withdraw');
                Db::name('business_withdrawfee_cash_order')->where('aid',$aid)->where('id',$orderid)->update(['withdrawlog_id'=>$business_withdrawlog['id']]);

                Db::name('business_withdrawlog')->where('aid',$aid)->where('id',$business_withdrawlog['id'])->update([
                    'status'=>0
                ]);

                $binfo = Db::name('business')->where('aid',$aid)->where('id',$bid)->find();
                $set = Db::name('business_sysset')->where(['aid'=>$aid])->find();
                $wx_transfer_type = Db::name('admin_set')->where('aid',$aid)->value('wx_transfer_type');

                $recordid = $business_withdrawlog['id'];

                //商户提现余额打到商户管理员余额账号
                if($business_withdrawlog['paytype']=='商家管理员余额'){
                    $admin_user = Db::name('admin_user')->where('aid',$aid)->where('bid',$bid)->where('id',$business_withdrawlog['tx_admin_user_id'])->find();
                    if($set['commission_autotransfer']==1){
                        if($b_res['status']==1 && $admin_user['mid']){
                            $res = \app\common\Member::addmoney($aid,$admin_user['mid'],$business_withdrawlog['money'],"商户余额提现");
                            if($res['status']!=1){
                                $rs1 = \app\common\Order::refund($order,$order['totalprice'],'商户余额提现失败，自动退款~');
                                if($rs1['status']==1){
                                    Db::name('business_withdrawfee_cash_order')->where('id',$order['id'])->where('aid',$order['aid'])->where('bid',$order['bid'])->update(['status'=>4,'refund_money' => $order['totalprice'],'refund_time' => time()]);
                                }
                            }else{
                                Db::name('business_withdrawlog')->where('aid',$aid)->where('id',$business_withdrawlog['id'])->update([
                                    'status'=>3,
                                    'weixin'=>'用户ID:'.$admin_user['mid'].'账户'.t('余额')
                                ]);
                            }
                        }else{
                            $rs1 = \app\common\Order::refund($order,$order['totalprice'],'商户余额提现失败，自动退款');
                            if($rs1['status']==1){
                                Db::name('business_withdrawfee_cash_order')->where('id',$order['id'])->where('aid',$order['aid'])->where('bid',$order['bid'])->update(['status'=>4,'refund_money' => $order['totalprice'],'refund_time' => time()]);
                            }
                        }
                    }else{
                        Db::name('business_withdrawlog')->where('aid',$aid)->where('id',$business_withdrawlog['id'])->update([
                            'status'=>0,
                            'weixin'=>'用户ID:'.$admin_user['mid'].'账户'.t('余额')
                        ]);
                    }
                }
                else if($business_withdrawlog['paytype']=='微信' || $business_withdrawlog['paytype']=='微信钱包'){
                    if($set['commission_autotransfer']==1 && $wx_transfer_type==0){

                        $rs = \app\common\Wxpay::transfers($aid,$mid,$business_withdrawlog['money'],$business_withdrawlog['ordernum'],'','余额提现');
                        if($rs['status']==0){
                            $record = [];
                            $record['status'] = 1;
                            $record['reason'] = $rs['msg']??'微信提现失败';
                            Db::name('business_withdrawlog')->where('id',$recordid)->update($record);

//                            $rs1 = \app\common\Order::refund($order,$order['totalprice'],'商户余额提现失败，自动退款');
//                            if($rs1['status']==1){
//                                Db::name('business_withdrawfee_cash_order')->where('id',$order['id'])->where('aid',$order['aid'])->where('bid',$order['bid'])->update(['status'=>4,'refund_money' => $order['totalprice'],'refund_time' => time()]);
//                            }
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
                            \app\common\Wechat::sendtmpl($aid,$mid,'tmpl_tixiansuccess',$tmplcontent,m_url('admin/index/index'),$tempconNew);
                            //短信通知
                            $member = Db::name('member')->where('id',$mid)->find();
                            if($member['tel']){
                                $tel = $member['tel'];
                                \app\common\Sms::send($aid,$tel,'tmpl_tixiansuccess',['money'=>$record['money']]);
                            }
                        }
                    }
                }else if($business_withdrawlog['paytype']=='支付宝'){
                    if($set['commission_autotransfer']==1){
                        $rs = \app\common\Alipay::transfers($aid,$business_withdrawlog['ordernum'],$business_withdrawlog['txmoney'],t('余额').'提现',$binfo['aliaccount'],$binfo['aliaccountname'],t('余额').'提现');
                        if($rs && $rs['status']==1){
                            $record = [];
                            $record['aliaccount'] =$binfo['aliaccount'] ;
                            $record['status'] = 3;
                            $record['paytime'] = time();
                            $record['paynum'] = $rs['resp']['payment_no'];
                            Db::name('business_withdrawlog')->where('id',$recordid)->update($record);
                            \app\common\System::plog('商家提现支付宝打款'.$recordid);
                        }else{
                            $record = [];
                            $record['status'] = 1;
                            $record['reason'] = $rs['sub_msg']??'支付宝提现失败';
                            Db::name('business_withdrawlog')->where('id',$recordid)->update($record);

//                            $rs1 = \app\common\Order::refund($order,$order['totalprice'],'商户余额提现失败，自动退款');
//                            if($rs1['status']==1){
//                                Db::name('business_withdrawfee_cash_order')->where('id',$order['id'])->where('aid',$order['aid'])->where('bid',$order['bid'])->update(['status'=>4,'refund_money' => $order['totalprice'],'refund_time' => time()]);
//                            }
                        }
                    }
                }

                $need_confirm = 0;

                $record = $business_withdrawlog;
                if($set['commission_autotransfer']==1 && $wx_transfer_type==1 ){
                    if($business_withdrawlog['paytype']=='微信' || $business_withdrawlog['paytype']=='微信钱包') {
                        //使用了新版的商家转账功能
                        $mid = Db::name('admin_user')->where('aid',$aid)->where('bid',$bid)->where('isadmin',1)->value('mid');
                        if(!$mid) return ['status'=>0,'msg'=>'商户主管理员未绑定微信'];
                        $paysdk = new \pay\wechatpay\WxPayV3($aid, $mid, $business_withdrawlog['platform']);
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

//                            $rs1 = \app\common\Order::refund($order,$order['totalprice'],'商户余额提现失败，自动退款');
//                            if($rs1['status']==1){
//                                Db::name('business_withdrawfee_cash_order')->where('id',$order['id'])->where('aid',$order['aid'])->where('bid',$order['bid'])->update(['status'=>4,'refund_money' => $order['totalprice'],'refund_time' => time()]);
//                            }
                        }
                    }
                }
            }
        }
    }

    public static function luntan_pay($orderid){
        if(getcustom('luntan_pay_top')){
            $order = Db::name('luntan_order')->where('id',$orderid)->find();
            //判断是否需要审核
            $sysset = Db::name('luntan_sysset')->where('aid',aid)->find();
            $data = [];
            $data['status'] = 1;
            $data['is_top'] = 1;
            $data['top_type'] = 2; //付费置顶
            $data['top_expire_time'] = $order['top_expire_time'];
            if($sysset['sendcheck'] == 1){
                $data['status'] = 0;
            }
            Db::name('luntan')->where('id',$order['luntan_id'])->where('aid',$order['aid'])->update($data);
        }
    }
}

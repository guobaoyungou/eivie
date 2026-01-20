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
class ShopOrder
{
    //从自定义字段中 同步订单的备注
	static function checkOrderMessage($orderid,$orderinfo=[]){
	    if($orderinfo){
	        $orderid = $orderinfo['id'];
        }else if($orderid){
	        $orderinfo = Db::name('shop_order')->where('aid',aid)->where('id',$orderid)->find();
        }else{
	        return '';
        }
	    $message = $orderinfo['message'];
        if(empty($orderinfo['message'])){
            $formdata = Db::name('freight_formdata')->where('aid',aid)->where('orderid',$orderinfo['id'])->order('id desc')->where('type','shop_order')->find();
            if($formdata){
                for ($i=0;$i<=30;$i++){
                    $field = $formdata['form'.$i];
                    if(!$field){
                        continue;
                    }
                    $fieldArr = explode('^_^',$field);
                    if(!$fieldArr || $fieldArr[2]=='upload'){
                        continue;
                    }
                    if(strpos($fieldArr[0],'备注')!==false){
                        $message = $orderinfo['message'] = $fieldArr[1]??'';
                        break;
                    }
                }
                //更新到订单，下次不再查询
                if($message){
                    Db::name('shop_order')->where('aid',aid)->where('id',$orderid)->update(['message'=>$message]);
                }
            }
        }
        return empty($message)?'':$message;
    }

    //视力档案
    static function getGlassRecordRow($ordergoods = []){
	    return '';
    }

    static function checkReturnComponent($aid,$bid=0){
        $status = false;
        return $status;
    }

    //订单创建完成进入回调逻辑处理
    static function after_create($aid,$orderid){
	    $orderids = [$orderid];
        if(getcustom('product_supplier_admin',$aid)){
            $res = self::split_order($aid,$orderid);
            if($res['status']==1 && $res['orderids']){
                $orderids = $res['orderids'];
            }
        }
        //订单创建完成，触发订单完成事件
        \app\common\Order::order_create_done($aid,$orderid,'shop');
        return $orderids;
    }


    //订单退款完成回调逻辑处理
    static function after_refund($aid,$orderid,$oglist,$type='shop'){
	    if(getcustom('yx_team_yeji_fenhong',$aid)){
	        if(!$type || $type=='shop'){
                //退款扣除团队业绩
                $order = Db::name('shop_order')->where('id',$orderid)->find();
                if($order['is_yeji']==1){
                    //已经结算过业绩的才给扣除
                    $yeji = 0;
                    foreach($oglist as $og){
                        $yeji = bcadd($yeji,$og['real_totalprice'],2);
                    }
                    $yeji = $yeji*-1;
                    \app\common\Order::updateTeamYeji($aid,$order['mid'],$yeji);
                }

            }
        }
        }


    //拆分订单
    public static function split_order($aid,$orderid){
        if(getcustom('product_supplier_admin',$aid)){
            $orderids = [];
            $split_order = Db::name('shop_sysset')->where('aid',$aid)->value('split_order');
            if($split_order==1){
                /*****************订单拆分 start************************/
                $order = Db::name('shop_order')->where('id',$orderid)->find();
                $og_list = Db::name('shop_order_goods')->where('aid',$aid)->where('orderid',$orderid)->select()->toArray();
                $supplier_ids = array_unique(array_column($og_list,'supplier_id'));
                if(count($supplier_ids)>1){
                    $supplier_og = [];
                    foreach($og_list as $og){
                        if (!isset($supplier_og[$og['supplier_id']])) {
                            $supplier_og[$og['supplier_id']] = [];
                        }
                        $supplier_og[$og['supplier_id']][] = $og;
                    }
                    $total_scoredkscore = 0;
                    $total_balance_price = 0;
                    $total_freight_price = 0;
                    $total_invoice_money = 0;
                    $total_givescore = 0;
                    $total_givescore2 = 0;
                    $total_give_commission_max = 0;
                    $total_give_commission_max2 = 0;
                    $total_givetongzheng = 0;
                    $total_give_withdraw_score = 0;
                    $total_give_parent1_withdraw_score = 0;
                    $i = 0;
                    foreach($supplier_og as $supplier_id=>$og_list){
                        $i++;
                        $ordernum = $order['ordernum'].'_'.$i;
                        $new_order = $order;
                        unset($new_order['id']);
                        $new_order['supplier_id'] = $supplier_id;
                        $new_order['ordernum'] = $ordernum;
                        $title = $og_list[0]['name'];
                        $new_order['title'] = $title.(count($og_list)>1?'等':'');
                        $product_price = array_sum(array_column($og_list,'totalprice'));
                        $totalprice = array_sum(array_column($og_list,'totalprice'));
                        $price_bili = bcdiv($product_price,$order['product_price'],2);
                        $leveldk_money =array_sum(array_column($og_list,'leveldk_money'));
                        $manjian_money = array_sum(array_column($og_list,'manjian_money'));
                        $scoredk_money = array_sum(array_column($og_list,'scoredk_money'));
                        $coupon_money = array_sum(array_column($og_list,'coupon_money'));

                        $scoredkscore = bcmul($price_bili,$order['scoredkscore'], 2);
                        $tongzhengdk_money = array_sum(array_column($og_list,'tongzhengdk_money'));
                        $tongzhengdktongzheng = array_sum(array_column($og_list,'tongzhengdktongzheng'));
                        $balance_price = bcmul($price_bili,$order['balance_price'], 2);
                        $freight_price = bcmul($price_bili,$order['freight_price'], 2);
                        $invoice_money = bcmul($price_bili,$order['invoice_money'], 2);
                        $givescore = bcmul($price_bili,$order['givescore'], 2);
                        $givescore2 = bcmul($price_bili,$order['givescore2'], 2);
                        $give_commission_max = bcmul($price_bili,$order['give_commission_max'], 2);
                        $give_commission_max2 = bcmul($price_bili,$order['give_commission_max2'], 2);
                        if($i==count($supplier_og)){
                            $scoredkscore = bcsub($order['scoredkscore'],$total_scoredkscore,2);
                            $balance_price = bcsub($order['scoredkscore'],$total_balance_price,2);
                            $freight_price = bcsub($order['freight_price'],$total_freight_price,2);
                            $invoice_money = bcsub($order['invoice_money'],$total_invoice_money,2);
                            $givescore = bcsub($order['givescore'],$total_givescore,2);
                            $givescore2 = bcsub($order['givescore2'],$total_givescore2,2);
                            $give_commission_max = bcsub($order['give_commission_max'],$total_give_commission_max,2);
                            $give_commission_max2 = bcsub($order['give_commission_max2'],$total_give_commission_max2,2);
                        }
                        $new_order['totalprice'] = $totalprice;
                        $new_order['product_price'] = $product_price;
                        $new_order['leveldk_money'] = $leveldk_money;	//会员折扣
                        $new_order['manjian_money'] = $manjian_money;	//满减活动
                        $new_order['scoredk_money'] = $scoredk_money;	//积分抵扣
                        $new_order['coupon_money'] = $coupon_money;	//优惠券抵扣
                        $new_order['scoredkscore'] = $scoredkscore;		//抵扣掉的积分
                        $new_order['balance_price'] = $balance_price;	//尾款金额 定制的
                        $new_order['freight_price'] = $freight_price; //运费
                        $new_order['invoice_money'] = $invoice_money; //发票
                        $new_order['givescore'] = $givescore;
                        $new_order['givescore2'] = $givescore2;
                        $new_order['hexiao_code'] = random(16);
                        if(false){}else{
                            $new_order['hexiao_qr'] = createqrcode(m_url('admin/hexiao/hexiao?type=shop&co='.$new_order['hexiao_code']));
                        }
                        $total_scoredkscore = bcadd($total_scoredkscore,$new_order['scoredkscore'],2);
                        $total_balance_price = bcadd($total_balance_price,$new_order['balance_price'],2);
                        $total_freight_price = bcadd($total_freight_price,$new_order['freight_price'],2);
                        $total_invoice_money = bcadd($total_invoice_money,$new_order['invoice_money'],2);
                        $total_givescore = bcadd($total_givescore,$new_order['givescore'],2);
                        $total_givescore2 = bcadd($total_givescore2,$new_order['givescore2'],2);
                        $total_give_commission_max = bcadd($total_give_commission_max,$new_order['give_commission_max'],2);
                        $total_give_commission_max2 = bcadd($total_give_commission_max2,$new_order['give_commission_max2'],2);
                        $total_givetongzheng = bcadd($total_givetongzheng,$new_order['givetongzheng'],2);
                        $total_give_withdraw_score = bcadd($total_give_withdraw_score,$new_order['give_withdraw_score'],2);
                        $total_give_parent1_withdraw_score = bcadd($total_give_parent1_withdraw_score,$new_order['give_parent1_withdraw_score'],2);
                        $new_orderid = Db::name('shop_order')->insertGetId($new_order);
                        $orderids[] = $new_orderid;
                        $og_ids = array_column($og_list,'id');
                        Db::name('shop_order_goods')->where('id','in',$og_ids)->update(['orderid'=>$new_orderid]);
                        Db::name('shop_order')->where('id',$orderid)->update(['delete'=>1]);

                    }
                }
            }
            return ['status'=>1,'msg'=>'订单拆分成功','orderids'=>$orderids];
        }
    }
}
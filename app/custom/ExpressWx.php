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



namespace app\custom;

use app\common\Wechat;
use think\facade\Db;
use think\facade\Log;

class ExpressWx
{
//order_status 枚举值
//值	说明
//101	配送公司接单阶段——等待分配骑手，即初始状态
//102	配送公司接单阶段——分配骑手成功
//103	配送公司接单阶段——商家取消订单， 订单结束
//201	骑手取货阶段——骑手到店开始取货
//202	骑手取货阶段——取货成功
//203	骑手取货阶段——取货失败，商家取消订单， 订单结束
//204	骑手取货阶段——取货失败，骑手因自身原因取消订单， 订单结束
//205	骑手取货阶段——取货失败，骑手因商家原因取消订单， 订单结束
//301	骑手配送阶段——配送中
//302	骑手配送阶段——配送成功， 订单结束
//303	骑手配送阶段——商家取消订单，配送物品开始返还商家
//304	骑手配送阶段——无法联系收货人，配送物品开始返还商家
//305	骑手配送阶段——收货人拒收，配送物品开始返还商家
//401	骑手返回配送货品阶段——货品返还商户成功， 订单结束
//501	因运力系统原因取消， 订单结束
    public static $orderStatusEnd = [
        '103','203','204','205','302','401','501','502'
    ];

    public static function getBindAccount($aid,$refresh = false)
    {
        $list = \db('express_wx_account')->where('aid',$aid)->select()->toArray();
        if(empty($list) || $refresh){
            $access_token = Wechat::access_token($aid,'wx');
            $url = 'https://api.weixin.qq.com/cgi-bin/express/local/business/shop/get?access_token='.$access_token;
            $rs = request_post($url);
            $rs = json_decode($rs,true);
            if($rs['errcode']!=0){
                return ['status'=>0,'msg'=>Wechat::geterror($rs)];
            }else{
                $list = [];
                foreach ($rs['shop_list'] as $k => $item){
                    $data = [
                        'aid' => $aid,
                        'delivery_id' => $item['delivery_id'],
                        'delivery_name' => $item['delivery_name'],
                        'shopid' => $item['shopid'],
                        'audit_result' => $item['audit_result'],
                        'createtime' => $item['create_time'],
                        'remark' => $item['remark'],
                        'status'=> $k === 0 ? 1 : 0
                    ];
                    if($refresh){
                        $info = \db('express_wx_account')->where('aid',$aid)->find();
                        if($info){
                            unset($data['status']);
                            \db('express_wx_account')->where('aid',$aid)->update($data);
                        }else{
                            \db('express_wx_account')->insert($data);
                        }
                    } else {
                        \db('express_wx_account')->insert($data);
                    }
                }
            }
        }

        return ['status'=>1,'msg'=>'成功','shop_list' => $list];
    }

    public static function accountSetStatus($aid,$id)
    {
        \db('express_wx_account')->where('aid',$aid)->update(['status'=>0]);
        \db('express_wx_account')->where('aid',$aid)->where('id',$id)->update(['status'=>1]);
        return ['status'=>1,'msg'=>'成功'];
    }

    public static function getAccount($aid,$id)
    {
        $info = \db('express_wx_account')->where('aid',$aid)->where('id',$id)->find();
        return $info;
    }

    public static function updateAccount($aid,$id,$info=[])
    {
        $info = \db('express_wx_account')->where('aid',$aid)->where('id',$id)->update($info);
        return $info;
    }

    public static function addOrder($type,$order,$psid=0)
    {
        Log::write([
            'file'=>__FILE__.__LINE__,
            'type'=>$type,
            'addOrder-rs'=>$order
        ]);
        $aid = $order['aid'];
        $set = Db::name('peisong_set')->where('aid',$aid)->find();
        if($order['bid']>0){
            $business = Db::name('business')->field('name,city,address,tel,logo,longitude,latitude,express_wx_shop_no')->where('id',$order['bid'])->find();
            if($set['express_wx_shopkoufei']==1){
                $businessMoney = $business['money'];
                if($businessMoney < $order['freight_price']){
                    return ['status'=>0,'msg'=>'商家余额不足'];
                }
                \app\common\Business::addmoney($aid,$order['bid'],-$order['freight_price'],'同城配送费');
            }
            if(empty($business['latitude']) || empty($business['longitude'])){
                return ['status'=>0,'msg'=>'请编辑商家坐标'];
            }
            if(empty($business['city'])) {
                $business['city'] = self::updateBusinessCity($business);
            }
        }else{
            $business = Db::name('admin_set')->field('name,city,address,tel,logo,longitude,latitude')->where('aid',$aid)->find();
            if(empty($business['latitude']) || empty($business['longitude'])){
                return ['status'=>0,'msg'=>'请编辑系统坐标'];
            }
            if(empty($business['city'])) {
                $business['city'] = self::updateAdminCity($business);
            }
            $business['express_wx_shop_no'] = $set['express_wx_shop_no'];
        }

        $account = \db('express_wx_account')->where('aid',$aid)->where('status',1)->find();
        $member = \db('member')->where('id',$order['mid'])->find();
        $oglist = \db($type.'_goods')->where('aid',$aid)->where('orderid',$order['id'])->select()->toArray();

        if(empty($account['appsecret'])){
            Log::write([
                'file'=>__FILE__.__LINE__,
                'error'=>'appsecret不能为空'
            ]);
            return ['status'=>0,'msg'=>'请填写appsecret'];
        }
        //请求数据
        Db::startTrans();
        $data = [];
        $data['shopid'] = $account['shopid'];
        $data['shop_order_id'] = $order['ordernum'];
        $data['shop_no'] = $business['express_wx_shop_no'];//'12345678';//商家门店编号，在配送公司登记，如果只有一个门店，美团闪送必填, 值为店铺id
//        $data['add_source'] = $order['platform'] == 'wx' ? 0 : 2;//订单来源，0为小程序订单，2为App或H5订单，填2则不发送物流服务通知
        $data['openid'] = $member['wxopenid'];
        $data['delivery_id'] = $account['delivery_id'];
        $data['delivery_sign'] =sha1($data['shopid'].$data['shop_order_id'].$account['appsecret']);//delivery_sign=SHA1(shopid + shop_order_id + AppSecret)

        $sender = [
            'name' => $business['name'],
            'city' => $business['city'],
            'address' => $business['address'],
            'address_detail' => $business['address'],//$business['address'],
            'phone' => $business['tel'],
            'lng' => sprintf("%.6f", $business['longitude']),
            'lat' => sprintf("%.6f", $business['latitude']),
            'coordinate_type' => 0
        ];
        $data['sender'] = $sender;
        $recAddress = explode(',',$order['area2']);
        $receiver = [
            'name' => $order['linkman'],
            'city' => $recAddress[1],
            'address' => $order['address'],
            'address_detail' => $order['address'],//$order['linkman'],
            'phone' => $order['tel'],
            'lng' => sprintf("%.6f", $order['longitude']),
            'lat' => sprintf("%.6f", $order['latitude']),
            'coordinate_type' => 0
        ];
        $data['receiver'] = $receiver;
        $goodslist = [];
        $totalNum = 0;
        $totalWeight = 0;
        foreach ($oglist as $og){
            $goodslist[] = [
                'good_count' => $og['num'],
                'good_name' => $og['name'].'['.$og['ggname'].']',
                'good_price' => $og['sell_price'],
                'good_unit' => '元'
            ];
            $totalNum += $og['num'];
            $totalWeight =+ $og['total_weight'];
        }
        $cargo = [
            'goods_value' => $order['totalprice'],//货物价格，单位为元，精确到小数点后两位
//            'goods_weight' => $totalWeight <= 0 ? 1 : round($totalWeight/1000,2),//单位为kg
            'goods_weight' => 1,
//            'goods_detail'=>['goods'=>$goodslist],
            'cargo_first_class'=>'电商',//品类一级类目
            'cargo_second_class'=>'线上商城'//品类2级类目
        ];
        $data['cargo'] = $cargo;
        $order_info = [
            'note' => '123',//$order['remark']
            'order_time' => $order['paytime']
        ];
        $data['order_info'] = $order_info;
        $shop = [
            'wxa_path' => '/pages/my/usercenter',
            'img_url' => $oglist[0]['pic'],
            'goods_name' => $order['title'],
            'goods_count' => $totalNum
        ];
        $data['shop'] = $shop;
        $order['procount'] = Db::name($type.'_goods')->field('name,ggname,pic,sell_price,num')->where('orderid',$order['id'])->sum('num');

        //插入数据
        $insData=$data;
        $insData['aid'] = $order['aid'];
        $insData['bid'] = $order['bid'];
        $insData['mid'] = $order['mid'];
        $insData['type'] = $type;
        $insData['orderid'] = $order['id'];
        $insData['goods_name'] = $order['title'];
        $insData['goods_count'] = $totalNum;
        $insData['img_url'] = $shop['img_url'];
        $insData['totalprice'] = $order['totalprice'];
        $insData['recManName'] = $receiver['name'];
        $insData['recManMobile'] = $receiver['phone'];
        $insData['receiver_json'] = json_encode($receiver);unset($insData['receiver']);
        $insData['sendManName'] = $sender['name'];
        $insData['sendManMobile'] = $sender['phone'];
        $insData['sender_json'] = json_encode($sender);unset($insData['sender']);
        $insData['cargo_json'] = json_encode($cargo);unset($insData['cargo']);
        $insData['order_info_json'] = json_encode($order_info);unset($insData['order_info']);
        $insData['shop_json'] = json_encode($shop);unset($insData['shop']);
        $insData['weight'] = $totalWeight;
        $insData['remark']='';
        $insData['createtime']=time();
        $insData['platform']=$order['platform'];
        $insData['orderinfo'] = jsonEncode($order);
        $insData['prolist'] = jsonEncode($oglist);
        $insData['binfo'] = jsonEncode($business);
        $express_wx_order_id = \db('express_wx_order')->insertGetId($insData);

        //api请求
        $access_token = Wechat::access_token($order['aid'],'wx');

        $url = 'https://api.weixin.qq.com/cgi-bin/express/local/business/order/add?access_token='.$access_token;

        $rs = request_post($url,jsonEncode($data));

        $rs = json_decode($rs,true);

        Log::write([
            'file'=>__FILE__.__LINE__,
            '$data'=>$data,
            'addOrder-rs'=>$rs
        ]);
        if($rs['errcode']!=0 || (!isset($rs['errcode']) && $rs['resultcode'] > 0)){
//            \db('express_wx_order')->where('id',$express_wx_order_id)->delete();
            // 回滚事务
            Db::rollback();
            if($rs['errcode'])
            $msg = Wechat::geterror($rs);
            $msg = $msg ? $msg : $rs['resultmsg'];
            return ['status'=>0,'msg'=>$msg];
        }else{
            $update = [
                'waybill_id' => $rs['waybill_id'],
                'order_status' => $rs['order_status'],
                'fee' => $rs['fee'],
                'deliverfee' => $rs['deliverfee'],
                'couponfee' => $rs['couponfee'],
                'tips' => $rs['tips'],
                'insurancefee' => $rs['insurancefee'],
                'distance' => $rs['distance'],
                'finish_code' => $rs['finish_code'],
                'pickup_code' => $rs['pickup_code'],
                'dispatch_duration' => $rs['dispatch_duration'],
                'errcode' => $rs['errcode'],
                'errmsg' => $rs['errmsg'],
                'resultcode' => $rs['resultcode'],
                'resultmsg' => $rs['resultmsg']
            ];
            \db('express_wx_order')->where('id',$express_wx_order_id)->update($update);

            Db::name($type)->where('aid',$aid)->where('id',$order['id'])->update(['express_com'=>'同城配送','express_no'=>$express_wx_order_id,'express_type' => 'express_wx','send_time'=>time(),'status'=>2]);

            if($type == 'shop_order'){
                Db::name('shop_order_goods')->where('orderid',$order['id'])->where('aid',$aid)->update(['status'=>2]);
            }
            if($type == 'scoreshop_order'){
                Db::name('scoreshop_order_goods')->where('orderid',$order['id'])->where('aid',$aid)->update(['status'=>2]);
            }
            if($type == 'restaurant_takeaway_order'){
                Db::name('restaurant_takeaway_order_goods')->where('orderid',$order['id'])->where('aid',$aid)->update(['status'=>2]);
            }

            Db::commit();

            //订单发货通知
            $tmplcontent = [];
            $tmplcontent['first'] = '您的订单系统已派单';
            $tmplcontent['remark'] = '请点击查看详情~';
            $tmplcontent['keyword1'] = $order['title'];
            $tmplcontent['keyword2'] = '同城配送';
            $tmplcontent['keyword3'] = '';
            $tmplcontent['keyword4'] = $order['linkman'].' '.$order['tel'];
            \app\common\Wechat::sendtmpl($aid,$order['mid'],'tmpl_orderfahuo',$tmplcontent,m_url('pages/my/usercenter', $aid));
            //订阅消息
            $tmplcontent = [];
            $tmplcontent['thing2'] = $order['title'];
            $tmplcontent['thing7'] = '同城配送';
            $tmplcontent['character_string4'] = '';
            $tmplcontent['thing11'] = $order['address'];
			
			$tmplcontentnew = [];
			$tmplcontentnew['thing29'] = $order['title'];
			$tmplcontentnew['thing1'] = '同城配送';
			$tmplcontentnew['character_string2'] = '';
			$tmplcontentnew['thing9'] = $order['address'];
            \app\common\Wechat::sendwxtmpl($aid,$order['mid'],'tmpl_orderfahuo',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);

            //短信通知
            if($member['tel']){
                $tel = $member['tel'];
            }else{
                $tel = $order['tel'];
            }
            \app\common\Sms::send($aid,$tel,'tmpl_orderfahuo',['ordernum'=>$order['ordernum'],'express_com'=>'同城配送','express_no'=>'']);

            $rs['status']=1;
            return $rs;
        }
    }

    public static function getOrder($order){
        $aid = $order['aid'];
        $type = $order['type'];

        $data=[
            'shopid' => $order['shopid'],
            'shop_order_id' => $order['shop_order_id'],
            'shop_no' => $order['shop_no'],
            'delivery_sign' => $order['delivery_sign'],
        ];

        //api请求
        $access_token = Wechat::access_token($order['aid'],'wx');

        $url = 'https://api.weixin.qq.com/cgi-bin/express/local/business/order/get?access_token='.$access_token;

        $rs = request_post($url,jsonEncode($data));

        $rs = json_decode($rs,true);

        Log::write([
            'file'=>__FILE__.__LINE__,
            '$data'=>$data,
            'getOrder-rs'=>$rs
        ]);
        if($rs['errcode']!=0) {
            return ['status' => 0, 'msg' => $rs['errmsg'] . '-' . $rs['resultmsg']];
        }
        if($rs['resultcode'] == 0){

            $update = [
                'order_status' => $rs['order_status'],
                'waybill_id' => $rs['waybill_id'],
                'rider_name' => $rs['rider_name'],
                'rider_phone' => $rs['rider_phone'],
                'rider_lng' => $rs['rider_lng'],
                'rider_lat' => $rs['rider_lat'],
                'reach_time' => $rs['reach_time'],
            ];
            Db::name('express_wx_order')->where('id',$order['id'])->update($update);

            self::updateOrderByStatus($rs['order_status'], $order);

            return ['status'=>1,'msg'=>'', 'order'=>array_merge($order,$update)];
        }
    }

    //取消配送单
    public static function cancelOrder($order,$cancel_reason_id){
        $aid = $order['aid'];
        $type = $order['type'];

        $data=[
            'shopid' => $order['shopid'],
            'shop_order_id' => $order['shop_order_id'],
            'shop_no' => $order['shop_no'],
            'delivery_sign' => $order['delivery_sign'],
            'delivery_id' => $order['delivery_id'],
            'waybill_id' => $order['waybill_id'],
            'cancel_reason_id' => $cancel_reason_id,
        ];
        $cancel_reason_id == 6 ?? $data['cancel_reason']='其他';

        //api请求
        $access_token = Wechat::access_token($order['aid'],'wx');

        $url = 'https://api.weixin.qq.com/cgi-bin/express/local/business/order/cancel?access_token='.$access_token;

        $rs = request_post($url,jsonEncode($data));

        $rs = json_decode($rs,true);

        Log::write([
            'file'=>__FILE__.__LINE__,
            '$data'=>$data,
            'cancelOrder-rs'=>$rs
        ]);
        if($rs['errcode']!=0) {
            return ['status' => 0, 'msg' => $rs['errmsg'] . '-' . $rs['resultmsg']];
        }
        if($rs['resultcode'] == 0){
            $set = Db::name('peisong_set')->where('aid',$aid)->find();
            $orderOrign = json_decode($order['orderinfo'],true);
            if($order['bid'] > 0 && $orderOrign['freight_price'] > 0){
                if($set['express_wx_shopkoufei']==1){
                    \app\common\Business::addmoney($order['aid'],$order['bid'],$orderOrign['freight_price'],'取消配送返还配送费');
                }
                //扣除违约金
                if($rs['deduct_fee'] > 0)
                    \app\common\Business::addmoney($order['aid'],$order['bid'],$rs['deduct_fee']*-1,'取消配送扣除违约金');
            }

            self::updateOrderByStatus(103, $order);
            $msg = '取消成功';
            if($rs['deduct_fee'] > 0)
                $msg .='，扣除违约金：'.$rs['deduct_fee'];
            return ['status'=>1,'msg'=>$msg];
        }
    }

    //更新状态
    public static function updateOrderStatus($postObj)
    {
        Log::write([
            'file'=>__FILE__.__LINE__,
            'updateOrderStatus'=>$postObj
        ]);
        $order = Db::name('express_wx_order')->where('shop_order_id',$postObj->shop_order_id)->where('waybill_id',$postObj->waybill_id)->order('id','desc')->find();
        $update = [
            'action_time' => $postObj->action_time,
            'order_status' => $postObj->order_status,
            'rider_name' =>  $postObj->agent->name,
            'rider_phone' =>  $postObj->agent->phone,
        ];
        if($postObj->order_status == '102'){
            $update['starttime'] = $postObj->action_time;
        }
        if($postObj->order_status == '201'){
            $update['daodiantime'] = $postObj->action_time;
        }
        if($postObj->order_status == '202' || $postObj->order_status == '301'){
            $update['quhuotime'] = $postObj->action_time;
        }
        if($postObj->order_status == '301' && empty($order['quhuotime'])){
            $update['quhuotime'] = $postObj->action_time;
        }
        if($postObj->order_status == '302'){
            $update['endtime'] = $postObj->action_time;
        }

        Db::name('express_wx_order')->where('id',$order['id'])
            ->update($update);

        \db('express_wx_order_status_log')->insert([
            'aid'=>$order['aid'],
            'bid'=>$order['bid'],
            'mid'=>$order['mid'],
            'type'=>$order['type'],
            'express_orderid'=>$order['id'],
            'orderid'=>$order['orderid'],
            'shop_order_id'=>$order['shop_order_id'],
            'waybill_id'=>$order['waybill_id'],
            'order_status'=>$postObj->order_status,
            'order_action'=>$order[''],
            'createtime'=>$postObj->action_time,
        ]);
    }

    public static function updateOrderByStatus($order_status, $order)
    {
        $aid = $order['aid'];
        $type = $order['type'];
        if($order_status == 103){
            //取消

            $status = 1;
            Db::name($type)->where('aid',$aid)->where('id',$order['id'])->update(['express_com'=>'同城配送','express_no'=>'','send_time'=>0,'status'=>1]);

            if($type == 'shop_order'){
                Db::name('shop_order_goods')->where('orderid',$order['id'])->where('aid',$aid)->update(['status'=>$status]);
            }
            if($type == 'scoreshop_order'){
                Db::name('scoreshop_order_goods')->where('orderid',$order['id'])->where('aid',$aid)->update(['status'=>$status]);
            }
            if($type == 'restaurant_takeaway_order'){
                Db::name('restaurant_takeaway_order_goods')->where('orderid',$order['id'])->where('aid',$aid)->update(['status'=>$status]);
            }
        }elseif($order_status == 302){
            //配送成功
//            $status = 3;
//            Db::name($type)->where('aid',$aid)->where('id',$order['id'])->update(['status'=>$status]);
//
//            if($type == 'shop_order'){
//                Db::name('shop_order_goods')->where('orderid',$order['id'])->where('aid',$aid)->update(['status'=>$status]);
//            }
//            if($type == 'scoreshop_order'){
//                Db::name('scoreshop_order_goods')->where('orderid',$order['id'])->where('aid',$aid)->update(['status'=>$status]);
//            }
//            if($type == 'restaurant_takeaway_order'){
//                Db::name('restaurant_takeaway_order_goods')->where('orderid',$order['id'])->where('aid',$aid)->update(['status'=>$status]);
//            }
        }
        Db::name('express_wx_order')->where('id',$order['id'])->update(['order_status'=>$order_status]);
        if($order_status != $order['order_status']){
            \db('express_wx_order_status_log')->insert([
                'aid'=>$order['aid'],
                'bid'=>$order['bid'],
                'mid'=>$order['mid'],
                'type'=>$order['type'],
                'express_orderid'=>$order['id'],
                'orderid'=>$order['orderid'],
                'shop_order_id'=>$order['shop_order_id'],
                'waybill_id'=>$order['waybill_id'],
                'order_status'=>$order_status,
                'order_action'=>$order[''],
                'createtime'=>time(),
            ]);
        }

    }

    public static function getActionName($order_status)
    {
        switch ($order_status) {
            case 101:
                $name = '等待分配骑手';
                break;
            case 102:
                $name = '分配骑手成功';
                break;
            case 103:
                $name = '商家取消订单';
                break;
            case 201:
                $name = '骑手到店';
                break;
            case 202:
                $name = '取货成功';
                break;
            case 203:
            case 204:
            case 205:
                $name = '取货失败';
                break;
            case 301:
                $name = '开始配送';
                break;
            case 302:
                $name = '配送成功';
                break;
            case 303:
            case 304:
            case 305:
                $name = '返还商家';
                break;
            case 401:
            case 501:
            case 502:
                $name = '订单结束';
                break;
            default:
                $name = '未知';
        }
        return $name;
    }

    public static function updateBusinessCity($business)
    {
        $info = [];
        if($business['latitude'] && $business['longitude']){
            //通过坐标获取省市区
            $address_component = \app\common\Common::getAreaByLocation($business['longitude'],$business['latitude']);
            if($address_component && $address_component['status']==1){
                $info['province'] = $address_component['province'];
                $info['city'] = $address_component['city'];
                $info['district'] = $address_component['district'];
                \db('business')->where('id',$business['id'])->update($info);
            }
        }
        return $info['city'] ? $info['city'] : '';
    }

    public static function updateAdminCity($admin)
    {
        $info = [];
        if($admin['latitude'] && $admin['longitude']){
            //通过坐标获取省市区
            $address_component = \app\common\Common::getAreaByLocation($admin['longitude'],$admin['latitude']);
            if($address_component && $address_component['status']==1){
                $info['province'] = $address_component['province'];
                $info['city'] = $address_component['city'];
                $info['district'] = $address_component['district'];
                \db('admin_set')->where('id',$admin['id'])->update($info);
            }
        }
        return $info['city'] ? $info['city'] : '';
    }

    /*
     * https://developers.weixin.qq.com/miniprogram/dev/platform-capabilities/industry/immediate-delivery/order_status.html
     * order_status 枚举值
值	说明
101	配送公司接单阶段——等待分配骑手，即初始状态
102	配送公司接单阶段——分配骑手成功
103	配送公司接单阶段——商家取消订单， 订单结束
201	骑手取货阶段——骑手到店开始取货
202	骑手取货阶段——取货成功
203	骑手取货阶段——取货失败，商家取消订单， 订单结束
204	骑手取货阶段——取货失败，骑手因自身原因取消订单， 订单结束
205	骑手取货阶段——取货失败，骑手因商家原因取消订单， 订单结束
301	骑手配送阶段——配送中
302	骑手配送阶段——配送成功， 订单结束
303	骑手配送阶段——商家取消订单，配送物品开始返还商家
304	骑手配送阶段——无法联系收货人，配送物品开始返还商家
305	骑手配送阶段——收货人拒收，配送物品开始返还商家
401	骑手返回配送货品阶段——货品返还商户成功， 订单结束
501	因运力系统原因取消， 订单结束
502	因不可抗拒因素（天气，道路管制等原因）取消，订单结束
说明
最终状态包括成功状态302，失败状态: 103,203,204,205,401,501,502。
当状态更新时，我们会在关键节点给收件用户推送服务通知，告知配送状态，同一配送单常态下会收到三条通知，即【骑手已接单】、【骑手已取货，配送中】、【配送已完成】，
    配送异常时会下发【配送异常】服务通知。
不同服务通知对应的 order_status 枚举值为
服务通知	对应的order_status值
骑手已接单	102
骑手已取货，配送中	202或301
配送已完成	302
配送异常	203、204、205、303、304、305、501、502*/
}
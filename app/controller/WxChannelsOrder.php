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

//custom_file(wx_channels)
//视频号小店
namespace app\controller;

use app\common\System;
use think\facade\View;
use think\facade\Db;

class WxChannelsOrder extends Common
{
    public function initialize()
    {
        parent::initialize();
        if (!getcustom('wx_channels_business') && bid > 0) showmsg('无访问权限');
        $this->appid = \app\common\WxChannels::defaultApp(aid,bid);
    }
    public function index()
    {
        $order_status = \app\common\WxChannels::order_status;
        $sharer_type_arr = \app\common\WxChannels::sharer_type;
        $share_scene_arr = \app\common\WxChannels::share_scene;
        if (request()->isAjax()) {
            $page = [
                "list_rows" => input('limit', 20),
                "page" => input('page', 1),
            ];
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['appid','=',$this->appid];
            if(input('?param.status') && input('param.status')!==''){
                $where[] = ['status','=',input('status')];
            }
            if(input('openid')){
                $where[] = ['openid','like','%'.input('openid').'%'];
            }
            //订单号
            if(input('order_id')){
                $where[] = ['order_id','like','%'.input('order_id').'%'];
            }
            if(input('orderid')){
                $where[] = ['id','=',input('orderid')];
            }
            //商家备注
            if(input('merchant_notes')){
                $where[] = ['merchant_notes','like','%'.input('merchant_notes').'%'];
            }
            //商品名称
            if(input('product_name')){
                $orderids = Db::name('channels_order_goods')->where('title','like','%'.input('product_name').'%')->column('order_id');
                $where[] = ['order_id','in',$orderids];
            }
            //商品编码
            if(input('product_id')){
                $orderids = Db::name('channels_order_goods')->where('product_id','like','%'.input('product_id').'%')->column('order_id');
                $where[] = ['order_id','in',$orderids];
            }
            //买家/收件人
            if(input('user_name')){
                $unionid = Db::name('member')->where('nickname','like','%'.input('user_name').'%')->order('unionid desc')->value('unionid');
                if($unionid){
                    $where[] = ['unionid','=',$unionid];
                }
            }
            //手机号
            if(input('tel_number')){
                $tel = input('tel_number');
                $where[] = Db::raw("JSON_EXTRACT(address_info, '$.tel_number') LIKE '%$tel%' OR JSON_EXTRACT(address_info, '$.virtual_order_tel_number') LIKE '%$tel%'");
            }
            //物流单号
            if(input('waybill_id')){
                $waybill_id = input('waybill_id');
//                $where[] = Db::raw("JSON_EXTRACT(delivery_product_info, '$[0].waybill_id') LIKE '%$waybill_id%'");
                $where[] = Db::raw("JSON_UNQUOTE(JSON_EXTRACT(delivery_product_info, '$[*].waybill_id')) LIKE '%$waybill_id%'");
            }
            //下单时间
            if(input('create_time')){
                $timeData = explode(' ~ ',input('create_time'));
                $where[] = ['create_time','>=',strtotime($timeData[0])];
                $where[] = ['create_time','<',strtotime($timeData[1])];
            }
            $list = Db::name("channels_order")
                ->where($where)
                ->order('create_time desc')
                ->paginate($page)
                ->toArray();
            $data = $list['data'];
            $after_sales_status = [
                "MERCHANT_PROCESSING",// => "商家受理中",
                "USER_WAIT_RETURN",// => "待买家退货",
                "MERCHANT_WAIT_RECEIPT",// => "待商家收货",
                "PLATFORM_REFUNDING",// => "平台退款中",
                "USER_WAIT_CONFIRM",// => "待用户确认",
                "USER_WAIT_CONFIRM_UPDATE",// => "待用户处理商家协商",
                "USER_WAIT_HANDLE_MERCHANT_AFTER_SALE",// => "待用户处理商家代发起的售后申请",
            ];
            foreach($data as $k=>$v){
                
                $data[$k]['member_name'] = '';
                $member = [];
                if($v['mid']){
                    $member =  Db::name('member')->where('id',$v['mid'])->where('aid',aid)->field('id,nickname')->find();
                }
                if(!$member && !empty($v['unionid'])){
                    $member =  Db::name('member')->where('unionid',$v['unionid'])->where('aid',aid)->field('id,nickname')->find();
                }
                if(!$member && !empty($v['openid'])){
                    $member =  Db::name('member')->where('channels_openid',$v['openid'])->where('aid',aid)->field('id,nickname')->find();
                }
                if(!$member){
                    if(!empty($v['unionid'])){
                        $sharer = Db::name('channels_sharer')->where('appid',$v['appid'])->where('unionid',$v['unionid'])->where('bid',bid)->where('isbind',1)->where('aid',aid)->find();
                    }
                    if(!$sharer &&!empty($v['openid'])){
                        $sharer = Db::name('channels_sharer')->where('appid',$v['appid'])->where('openid',$v['openid'])->where('bid',bid)->where('isbind',1)->where('aid',aid)->find();
                    }
                    if($sharer && $sharer['mid']){
                        $member = Db::name('member')->where('id',$sharer['mid'])->where('aid',$v['aid'])->find();
                    }
                }
                if($member){
                    $data[$k]['member_name'] = $member['nickname'].'(ID:'.$member['id'].')';
                }

                $data[$k]['sharer_name'] = '';
                if($v['sharerid']){
                    $share_info = Db::name('channels_sharer')->where('id',$v['sharerid'])->where('bid',bid)->where('aid',aid)->field('id,nickname')->find();
                }
                if($share_info && !empty($v['sharer_unionid'])){
                    $share_info = Db::name('channels_sharer')->where('unionid',$v['sharer_unionid'])->where('bid',bid)->where('aid',aid)->field('id,nickname')->find();
                }
                if(!$share_info && !empty($v['sharer_openid'])){
                    $share_info = Db::name('channels_sharer')->where('openid',$v['sharer_openid'])->where('bid',bid)->where('aid',aid)->field('id,nickname')->find();
                }
                if($share_info){
                    $data[$k]['sharer_name'] = $share_info['nickname'].'(ID:'.$share_info['id'].')';
                }else{
                    $data[$k]['sharer_name'] = $v['sharer_openid'];
                }

                $data[$k]['status_name'] = $order_status[$v['status']];
                $data[$k]['sharer_type_str'] = $sharer_type_arr[$v['sharer_type']];
                $data[$k]['share_scene_str'] = $share_scene_arr[$v['share_scene']];

                //商品信息
                $oglist = Db::name('channels_order_goods')->where('order_id',$v['order_id'])->select()->toArray();
                $goodsdata=array();
                foreach($oglist as $og){
                    $ggname = Db::name('channels_product_guige')->where('product_id',$og['product_id'])->where('sku_id',$og['sku_id'])->value('name');
                    $goodshtml = '<div style="font-size:12px;float:left;clear:both;margin:1px 0">'.
                        '<div class="table-imgbox"><img src="'.$og['thumb_img'].'" ></div>'.
                        '<div style="float: left;width:180px;margin-left: 10px;white-space:normal;line-height:16px;">'.
                        '<div style="width:100%;min-height:25px;max-height:32px;overflow:hidden">'.$og['title'].'</div>'.
                        '<div style="padding-top:0px;color:#f60"><span style="color:#888">'.$ggname.'</span></div>';
                        $goodshtml.='<div style="padding-top:0px;color:#f60;">￥'.$og['sale_price'].' × '.$og['sku_cnt'].'</div>';
                    $goodshtml.='</div>';
                    $goodshtml.='</div>';

                    $goodsdata[] = $goodshtml;
                }
                // $member = [];
                // if($v['unionid']){
                //     $member = Db::name('member')->field('headimg,nickname')->where('unionid',$v['unionid'])->find();
                // }
                $data[$k]['goodsdata'] = implode('',$goodsdata);
                //地址信息
                $address_info = json_decode($v['address_info'],true);
                $address_html = '';
                if($member){
                    $address_html.='<div><img src="'.$member['headimg'].'" style="height:50px"></div>';
                    $address_html.='<div style="margin: 10px 0;">'.$member['nickname'].'</div>';
                }
                if($v['deliver_method']==0){
                    $address_html .= '<div><div style="font-weight:bold">' .
                        $address_info['user_name'] .$address_info['tel_number'].
                        '</div><div style="line-height:20px;font-size:12px">' .$address_info['province_name'].$address_info['city_name'].$address_info['county_name'].
                        '</div></div>';
                }else{
                    $address_html .= '<div><div style="font-weight:bold">' .
                        $address_info['user_name'] .$address_info['virtual_order_tel_number'].
                        '</div></div>';
                }


                $data[$k]['address_html'] = $address_html;
                //查询订单退款信息
                $after_sales_count = Db::name('channels_after_sales')
                    ->where('order_id',$v['order_id'])->where('status','in',$after_sales_status)->count();
                $refund_money = Db::name('channels_after_sales')
                    ->alias('a')
                    ->where('order_id',$v['order_id'])->where('status','in',['MERCHANT_REFUND_SUCCESS','MERCHANT_RETURN_SUCCESS'])->sum('amount');
                $data[$k]['refund_money'] = $refund_money?:0;
                $data[$k]['after_sales_count'] = $after_sales_count?:0;


            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $list['total'], 'data' => $data]);
        }
        View::assign('order_status',$order_status);
        return View::fetch();
    }
    //同步商品列表
    public function asyncAllOrder()
    {
        Db::startTrans();
        try {
            $input = input();
            if(!empty($input['create_time_range'])){
                $ctime = explode(' ~ ',$input['create_time_range']);
                $start_time = strtotime($ctime[0]);
                $end_time = strtotime($ctime[1]);
            }else{
                $start_time = time()-7*86400;
                $end_time = time();
            }
            $next_key = input('next_key');
            $params = [];
            $params['create_time_range'] = [
                'start_time' => $start_time,
                'end_time' => $end_time,
            ];
            if(!empty($input['status'])){
                $params['status'] = (int)$input['status'];
            }
            if($next_key){
                $params['next_key'] = $next_key;
            }
            $params['page_size'] = 20;

            //获取订单id列表
            $res = \app\common\WxChannels::asyncOrderAll(aid,bid, $this->appid, $params);
            if($res['status'] == 0){
                return json($res);
            }
            $order_ids = $res['data'];
//            $order_ids = ['37423523451235145'];

            foreach ($order_ids as $order_id) {
                $res = \app\common\WxChannels::updateOrder(aid,bid, $this->appid, $order_id);
            }

            Db::commit();
            return json(['status' => 1, 'msg' => '同步成功','next_key'=>$res['next_key'],'continue_flag'=>$res['continue_flag']]);
        } catch (\Throwable $t) {
            Db::rollback();
            return json(['status' => 0, 'msg' => $t->getMessage()]);
        }
    }
    //修改价格
    public function edit_price(){
        $id = input('id');
        $info = Db::name('channels_order')->where('id',$id)->find();
        if(request()->isPost()){
            $data = [];
            $data['order_id'] = $info['order_id'];
            $data['change_express'] = input('change_express')==1?true:false;
            $data['express_fee'] = $info['express_fee']*100;
            $product_ids = input('product_id');
            $sku_ids = input('sku_id');
            $change_prices = input('change_price');
            $change_order_infos = [];
            foreach($product_ids as $k=>$product_id){
                $arr = [
                    'product_id' => $product_id,
                    'sku_id' => $sku_ids[$k],
                    'change_price' => $change_prices[$k]*100,
                ];
                $change_order_infos[] = $arr;
            }
            $data['change_order_infos'] = $change_order_infos;
            $res = \app\common\WxChannels::updataOrderPrice(aid,bid, $this->appid, $data);
            if(!$res['status']){
                return json($res);
            }
            $res = \app\common\WxChannels::updateOrder(aid,bid,$this->appid,$info['order_id']);
            \app\common\System::plog("修改视频号小店订单价格：".$info['order_id']);
            return json(['status'=>1,'msg'=>'修改成功']);
        }else{
            $order_goods = Db::name('channels_order_goods')->where('order_id',$info['order_id'])->select()->toArray();
            foreach($order_goods as $k=>$product){
                $product_info = Db::name('channels_product')->where('product_id',$product['product_id'])->find();
                $sku_info = Db::name('channels_product_guige')
                    ->where('product_id',$product['product_id'])
                    ->where('sku_id',$product['sku_id'])
                    ->find();
                $order_goods[$k]['product_name'] = $product_info['name'];
                $order_goods[$k]['guige_name'] = $sku_info['name'];
            }
            View::assign('info',$info);
            View::assign('order_goods',$order_goods);
        }

        return View::fetch();
    }
    //修改备注
    public function edit_notice(){
        $id = input('id');
        $notice = input('notice');
        $info = Db::name('channels_order')->where('id',$id)->find();
        $data = [];
        $data['order_id'] = $info['order_id'];
        $data['merchant_notes'] = $notice;
        $res = \app\common\WxChannels::updataOrderNotice(aid,bid, $this->appid, $data);
        if(!$res['status']){
            return json($res);
        }
        Db::name('channels_order')->where('id',$id)->update(['merchant_notes'=>$notice]);
        \app\common\System::plog("修改视频号小店订单备注：".$info['order_id']);
        return json(['status'=>1,'msg'=>'修改成功']);
    }
    //修改订单收货地址（未发货的订单可以修改，次数限制为5次）
    public function edit_adr(){
        $id = input('id');
        $info = Db::name('channels_order')->where('id',$id)->find();
        if(request()->isPost()){
            $addressinfo = input('addressinfo');
            $data = [];
            $data['order_id'] = $info['order_id'];
//            $data['user_address'] = $addressinfo;
            $data['user_address'] = [
                'user_name' => $addressinfo['user_name']??"",
                'postal_code' => $addressinfo['postal_code']??'',
                'province_name' => $addressinfo['province_name']??'',
                'city_name' => $addressinfo['city_name']??'',
                'county_name' => $addressinfo['county_name']??'',
                'detail_info' => $addressinfo['detail_info']??'',
                'national_code' => $addressinfo['national_code']??'',
                'tel_number' => $addressinfo['tel_number']??'',
                'house_number' => $addressinfo['house_number']??'',
                'virtual_order_tel_number' => $addressinfo['virtual_order_tel_number']??'',
            ];
            $res = \app\common\WxChannels::updataOrderAdr(aid,bid, $this->appid, $data);
            if(!$res['status']){
                return json($res);
            }
            \app\common\System::plog("修改视频号小店订单收货地址：".$info['order_id']);
            return json(['status'=>1,'msg'=>'修改成功']);
        }else{
            $order_goods = Db::name('channels_order_goods')->where('order_id',$info['order_id'])->select()->toArray();
            foreach($order_goods as $k=>$product){
                $product_info = Db::name('channels_product')->where('product_id',$product['product_id'])->find();
                $sku_info = Db::name('channels_product_guige')
                    ->where('product_id',$product['product_id'])
                    ->where('sku_id',$product['sku_id'])
                    ->find();
                $order_goods[$k]['product_name'] = $product_info['name'];
                $order_goods[$k]['guige_name'] = $sku_info['name'];
            }
            View::assign('info',$info);
            View::assign('order_goods',$order_goods);

            $addressinfo = json_decode($info['address_info'],true);
            View::assign('addressinfo',$addressinfo);
        }

        return View::fetch();
    }
    /**
     * 修改订单物流信息（未发货的订单可以修改，次数限制为5次）
     *发货完成的订单可以修改，最多修改1次；
     *拆包发货的订单暂不允许修改物流；
     *虚拟商品订单暂不允许修改物流。
     **/
    public function edit_delivery(){
        $id = input('id');
        $info = Db::name('channels_order')->where('id',$id)->find();
        if(request()->isPost()){
            $delivery_num = input('delivery_num');
            $product_infos = [];
            foreach($delivery_num as $goods_id=>$num){
                if($num<=0){
                    continue;
                }
                $order_good_info = Db::name('channels_order_goods')->where('order_id',$info['order_id'])->where('id','=',$goods_id)->find();
                $product_infos[] = [
                    'product_cnt' => (int)$num,
                    'product_id' => $order_good_info['product_id'],
                    'sku_id' => $order_good_info['sku_id'],
                ];
            }
            if(empty($product_infos)){
                return json(['status'=>0,'msg'=>'请选择发货数量']);
            }

            $delivery_list[] = [
                'delivery_id' => input('delivery_id'),
                'waybill_id' => input('waybill_id'),
                'deliver_type' => $info['deliver_method']==0?1:3,
                'product_infos' => $product_infos
            ];
            $data = [];
            $data['order_id'] = $info['order_id'];
            $data['delivery_list'] = $delivery_list;
            if($info['status']==20 || $info['status']==21){
                //待发货的调用发货接口
                $res = \app\common\WxChannels::sendOrder(aid,bid, $this->appid, $data);
            }else{
                //已发货的调用修改物流接口
                $res = \app\common\WxChannels::updataDeliveryinfo(aid,bid, $this->appid, $data);
            }
            if(!$res['status']){
                return json($res);
            }
            \app\common\WxChannels::updateOrder(aid,bid, $this->appid, $info['order_id']);
            \app\common\System::plog("修改视频号小店订单物流：".$info['order_id']);
            return json(['status'=>1,'msg'=>'修改成功']);
        }else{
            $order_goods = Db::name('channels_order_goods')->where('order_id',$info['order_id'])->select()->toArray();
            foreach($order_goods as $k=>$product){
                $product_info = Db::name('channels_product')->where('product_id',$product['product_id'])->find();
                $sku_info = Db::name('channels_product_guige')
                    ->where('product_id',$product['product_id'])
                    ->where('sku_id',$product['sku_id'])
                    ->find();
                $order_goods[$k]['product_name'] = $product_info['name'];
                $order_goods[$k]['guige_name'] = $sku_info['name'];
            }
            View::assign('info',$info);
            View::assign('order_goods',$order_goods);
            $addressinfo = json_decode($info['address_info'],true);
            View::assign('addressinfo',$addressinfo);
            //物流公司
            $delivery_lists = Db::name('channels_delivery')->where('aid',aid)->where('appid',$this->appid)->where('bid',bid)->select()->toArray();
            if(empty($delivery_lists)){
                $res = \app\common\WxChannels::getDeliveryLists(aid,bid, $this->appid);
                $delivery_lists = $res['data'];
                foreach($delivery_lists as $k=>$v){
                    $data_d = [];
                    $data_d['aid'] = aid;
                    $data_d['bid'] = bid;
                    $data_d['appid'] = $this->appid;
                    $data_d['delivery_id'] = $v['delivery_id'];
                    $data_d['delivery_name'] = $v['delivery_name'];
                    Db::name('channels_delivery')->insert($data_d);
                }
            }
            View::assign('delivery_lists',$delivery_lists);
        }

        return View::fetch();
    }
    public function getordergoods(){
        $id = input('id');
        $info = Db::name('channels_order')->where('id',$id)->find();
        $order_goods = Db::name('channels_order_goods')->where('order_id',$info['order_id'])->select()->toArray();
        foreach($order_goods as $k=>$product){
            $product_info = Db::name('channels_product')->where('product_id',$product['product_id'])->find();
            $sku_info = Db::name('channels_product_guige')
                ->where('product_id',$product['product_id'])
                ->where('sku_id',$product['sku_id'])
                ->find();
            $order_goods[$k]['product_name'] = $product_info['name'];
            $order_goods[$k]['guige_name'] = $sku_info['name'];
        }
        return json(['code' => 0, 'msg' => '查询成功', 'count' => count($order_goods), 'data' => $order_goods]);
    }

    //电子面单打印
    public function ewaybill(){
        $id = input('id');
        $info = Db::name('channels_order')->where('id',$id)->find();
        if(request()->isPost()){
            $input = input();
            $acct_id = input('acct_id');
            $account_info = Db::name('channels_ewaybill_account')->where('acct_id',$acct_id)->find();
            $delivery_id = $account_info['delivery_id'];
            //站点信息
            $site_info = json_decode($account_info['site_info'],true);
            //发货地址
            $address_id = input('address_id');
            $address_info = Db::name('channels_address')->where('id',$address_id)->find();
            //收货地址
            $order_address_info = json_decode($info['address_ifno'],true);
            $params = [];
            $params['delivery_id'] = $delivery_id;
            $params['site_code'] = $site_info['site_code'];
            $params['ewaybill_acct_id'] = $account_info['acct_id'];
            $params['sender'] = [
               'name' => $address_info['user_name'],
               'mobile' => $address_info['user_name'],
               'province' => $address_info['province_name'],
               'city' => $address_info['city_name'],
               'county' => $address_info['county_name'],
               'street' => '',
               'address' => $address_info['detail_info'],
           ];
            $params['receiver'] = [
               'name' => $order_address_info['user_name']?:'测试',
               'mobile' => $order_address_info['tel_number']?:'12345678',
               'province' => $order_address_info['province_name']?:'山东省',
               'city' => $order_address_info['city_name']?:'临沂市',
               'county' => $order_address_info['county_name']?:'兰山区',
               'street' => '',
               'address' => $order_address_info['detail_info']?:'iec1402',
           ];
            $goods_lists = Db::name('channels_order_goods')
               ->alias('g')->join('channels_order o','g.order_id=o.order_id')
               ->where('o.id','=',$id)
               ->field('title as good_name,product_id,sku_id,sku_cnt good_count')
               ->select()->toArray();
            $order_list = [
               'ec_order_id' => (int)$info['order_id'],
               'goods_list' => $goods_lists
           ];
            $params['ec_order_list'][] = $order_list;
            $params['remark'] = $input['remark'];
            $params['shop_id'] = $account_info['shop_id'];

            $res = \app\common\WxChannels::ewaybillPrecreateOrder(aid,bid,$this->appid,$params);
            if(!$res['status']){
                return json($res);
            }else{
                $ewaybill_order_id = $res['data'];
            }
            $params['ewaybill_order_id'] = $ewaybill_order_id;
            $params['order_id'] = $info['order_id'];
            $params['aid'] = aid;
            $params['bid'] = bid;
            $params['appid'] = $this->appid;
            $inset_id = Db::name('channels_ewaybill_order')->insert($params);
            $order_params = $params;
            unset($order_params['order_id']);
            $res = \app\common\WxChannels::ewaybillCreateOrder(aid,bid,$this->appid,$order_params);
            if(!$res['status']){
                return json($res);
            }else{
                $res_data = $res['data'];
            }
            $data_u = [
                'waybill_id' => $res_data['waybill_id'],
                'delivery_error_msg' => $res_data['delivery_error_msg'],
                'print_info' => json_encode($res_data['print_info'])
            ];
            Db::name('channels_ewaybill_order')
                ->where('id',$inset_id)
                ->update($data_u);
            //更新订单电子面单号
            Db::name('channels_order')->where('id',$id)->update(['ewaybill_order_id'=>$ewaybill_order_id]);
            return json(['status'=>1,'msg'=>'取号成功']);
        }else{
            $order_goods = Db::name('channels_order_goods')->where('order_id',$info['order_id'])->select()->toArray();
            foreach($order_goods as $k=>$product){
                $product_info = Db::name('channels_product')->where('product_id',$product['product_id'])->find();
                $sku_info = Db::name('channels_product_guige')
                    ->where('product_id',$product['product_id'])
                    ->where('sku_id',$product['sku_id'])
                    ->find();
                $order_goods[$k]['product_name'] = $product_info['name'];
                $order_goods[$k]['guige_name'] = $sku_info['name'];
            }
            View::assign('info',$info);
            View::assign('order_goods',$order_goods);
            $delivery_arr = Db::name('channels_ewaybill_account')
                ->where('aid',aid)
                ->where('appid',$this->appid)
                ->where('bid',bid)
                ->select()->toArray();
            foreach($delivery_arr as $k=>$v){
                $delivery_name = Db::name('channels_ewaybill_delivery')->where('delivery_id',$v['delivery_id'])->value('delivery_name');
                $delivery_arr[$k]['site_name'] = $delivery_name.'-'.$v['site_name'];
            }
            View::assign('delivery_arr',$delivery_arr);
        }

        return View::fetch();
    }
    //打印页面
    public function ewaybill_print(){

        $id = input('id');
        $info = Db::name('channels_order')->where('id',$id)->find();
        $order_goods = Db::name('channels_order_goods')->where('order_id',$info['order_id'])->select()->toArray();
        foreach($order_goods as $k=>$product){
            $product_info = Db::name('channels_product')->where('product_id',$product['product_id'])->find();
            $sku_info = Db::name('channels_product_guige')
                ->where('product_id',$product['product_id'])
                ->where('sku_id',$product['sku_id'])
                ->find();
            $order_goods[$k]['product_name'] = $product_info['name'];
            $order_goods[$k]['guige_name'] = $sku_info['name'];
        }
        View::assign('info',$info);
        View::assign('order_goods',$order_goods);
        View::assign('order_goods_json',json_encode($order_goods));
        $delivery_arr = Db::name('channels_ewaybill_delivery')
            ->where('aid',aid)
            ->where('appid',$this->appid)
            ->where('bid',bid)
            ->select()->toArray();
        View::assign('delivery_arr',$delivery_arr);

        $ewaybill_order =  Db::name('channels_ewaybill_order')
            ->where('ewaybill_order_id',$info['ewaybill_order_id'])->find();
        $ewaybill_order['delivery_name'] = Db::name('channels_ewaybill_delivery')->where('delivery_id',$ewaybill_order['delivery_id'])->value('delivery_name');
        View::assign('ewaybill_order',$ewaybill_order);
        //电子面单标准模板
        $template_config = Db::name('channels_ewaybill_template_config')->where('delivery_id',$ewaybill_order['delivery_id'])->value('url');
        View::assign('template_config',$template_config);
        //小店信息
        $app_info = Db::name('admin_setapp_channels')->where('appid',$this->appid)->find();
        View::assign('app_info',$app_info);
        return View::fetch();
    }
    //获取打印报文
    public function getPrintInfo(){
        $id = input('id');
        $info = Db::name('channels_ewaybill_order')->where('id',$id)->find();
        $ewaybill_order_id = $info['ewaybill_order_id'];
        $template_id = input('template_id');
        $data = [
            'ewaybill_order_id' => $ewaybill_order_id,
            'template_id' => $template_id
        ];
        $res = \app\common\WxChannels::ewaybillPrintInfo(aid,bid,$this->appid,$data);
        if(!$res['status']){
            return json($res);
        }else{
            $res_data = $res['data'];
        }
    }

    //订单详情
    public function getdetail(){
        $id = input('id');
        $order = Db::name('channels_order')->where('aid',aid)->where('appid',$this->appid)->where('id',$id)->where('bid',bid)->find();
        $payment_method_arr = \app\common\WxChannels::payment_method;
        $order['payment_method_str'] = $payment_method_arr[$order['payment_method']]??'';
        $address_info = json_decode($order['address_info'],true);

        $ogwhere = [];
        $ogwhere[] = ['order_id','=',$order['order_id']];
        $oglist = Db::name('channels_order_goods')->where($ogwhere)->select()->toArray();
        foreach($oglist as $k=>$v){
            $guige_name = Db::name('channels_product_guige')->where('product_id',$v['product_id'])->where('sku_id',$v['sku_id'])->value('name');
            $oglist[$k]['guige_name'] = $guige_name;
        }
        $member = [];
        if($order['mid']){
            $member =  Db::name('member')->where('id',$order['mid'])->where('aid',aid)->field('id,nickname')->find();
        }
        if(!$membe && $order['unionid']){
            $member = Db::name('member')->field('id,nickname,headimg,realname,tel,wxopenid,unionid')->where('unionid',$order['unionid'])->find();
        }
        if(!$member && $order['openid']){
            $member = Db::name('member')->field('id,nickname,headimg,realname,tel,wxopenid,unionid')->where('channels_openid',$order['openid'])->find();
        }
        if(!$member) $member = ['id'=>$order['openid'],'nickname'=>$order['openid'],'headimg'=>''];
        $comdata = array();
        $comdata['parent1'] = ['mid'=>'','nickname'=>'','headimg'=>'','money'=>0,'score'=>0];
        $comdata['parent2'] = ['mid'=>'','nickname'=>'','headimg'=>'','money'=>0,'score'=>0];
        $comdata['parent3'] = ['mid'=>'','nickname'=>'','headimg'=>'','money'=>0,'score'=>0];
        $ogids = [];
        foreach($oglist as $gk=>$v){
            $commission_data = json_decode($v['commission_data'],true);
            $ogids[] = $v['id'];
            if($commission_data['parent1']){
                $parent1 = Db::name('member')->where('id',$commission_data['parent1'])->find();
                $comdata['parent1']['mid'] = $commission_data['parent1'];
                $comdata['parent1']['nickname'] = $parent1['nickname'];
                $comdata['parent1']['headimg'] = $parent1['headimg'];
                $comdata['parent1']['money'] += $commission_data['parent1commission']??0;
                $comdata['parent1']['score'] += $commission_data['parent1score']??0;
            }
            if($commission_data['parent2']){
                $parent2 = Db::name('member')->where('id',$commission_data['parent2'])->find();
                $comdata['parent2']['mid'] = $commission_data['parent2'];
                $comdata['parent2']['nickname'] = $parent2['nickname'];
                $comdata['parent2']['headimg'] = $parent2['headimg'];
                $comdata['parent2']['money'] += $commission_data['parent2commission']??0;
                $comdata['parent2']['score'] += $commission_data['parent2score']??0;
            }
            if($commission_data['parent3']){
                $parent3 = Db::name('member')->where('id',$commission_data['parent3'])->find();
                $comdata['parent3']['mid'] = $commission_data['parent3'];
                $comdata['parent3']['nickname'] = $parent3['nickname'];
                $comdata['parent3']['headimg'] = $parent3['headimg'];
                $comdata['parent3']['money'] += $commission_data['parent3commission']??0;
                $comdata['parent3']['score'] += $commission_data['parent3score']??0;
            }
        }
        $comdata['parent1']['money'] = round($comdata['parent1']['money'],2);
        $comdata['parent2']['money'] = round($comdata['parent2']['money'],2);
        $comdata['parent3']['money'] = round($comdata['parent3']['money'],2);


        $payorder = [];

        if($order['express_content']) $order['express_content'] = json_decode($order['express_content'],true);
        if($order['status'] == 1){
            $order['express_ogids'] = implode(',',$ogids);
        }
        if($order['express_ogids']){
            $order['express_ogids'] = explode(',',$order['express_ogids']);
        }else{
            $order['express_ogids'] = [];
        }
        foreach($order['express_content'] as $k=>$v){
            if(!$v['express_ogids']){
                $v['express_ogids'] = [];
            }else{
                $v['express_ogids'] = explode(',',$v['express_ogids']);
            }
            $order['express_content'][$k] = $v;
        }
        $delivery_product_info = json_decode($order['delivery_product_info'],true);
        $delivery_product_info = $delivery_product_info?:'';
        $data = ['order'=>$order,'oglist'=>$oglist,'member'=>$member,'comdata'=>$comdata,'payorder' => $payorder,'address_info'=>$address_info,'delivery_product_info'=>$delivery_product_info];
        return json($data);
    }

    public function excel()
    {
        $order_status = \app\common\WxChannels::order_status;
        $sharer_type_arr = \app\common\WxChannels::sharer_type;
        $share_scene_arr = \app\common\WxChannels::share_scene;
        $page = [
            "list_rows" => input('limit', 20),
            "page" => input('page', 1),
        ];
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',bid];
        $where[] = ['appid','=',$this->appid];
        if(input('?param.status') && input('param.status')!==''){
            $where[] = ['status','=',input('status')];
        }
        if(input('openid')){
            $where[] = ['openid','like','%'.input('openid').'%'];
        }
        //订单号
        if(input('order_id')){
            $where[] = ['order_id','like','%'.input('order_id').'%'];
        }
        if(input('orderid')){
            $where[] = ['id','=',input('orderid')];
        }
        //商家备注
        if(input('merchant_notes')){
            $where[] = ['merchant_notes','like','%'.input('merchant_notes').'%'];
        }
        //商品名称
        if(input('product_name')){
            $orderids = Db::name('channels_order_goods')->where('title','like','%'.input('product_name').'%')->column('order_id');
            $where[] = ['order_id','in',$orderids];
        }
        //商品编码
        if(input('product_id')){
            $orderids = Db::name('channels_order_goods')->where('product_id','like','%'.input('product_id').'%')->column('order_id');
            $where[] = ['order_id','in',$orderids];
        }
        //买家/收件人
        if(input('user_name')){
            $unionid = Db::name('member')->where('nickname','like','%'.input('user_name').'%')->order('unionid desc')->value('unionid');
            if($unionid){
                $where[] = ['unionid','=',$unionid];
            }
        }
        //手机号
        if(input('tel_number')){
            $tel = input('tel_number');
            $where[] = Db::raw("JSON_EXTRACT(address_info, '$.tel_number') LIKE '%$tel%' OR JSON_EXTRACT(address_info, '$.virtual_order_tel_number') LIKE '%$tel%'");
        }
        //物流单号
        if(input('waybill_id')){
            $waybill_id = input('waybill_id');
            //  $where[] = Db::raw("JSON_EXTRACT(delivery_product_info, '$[0].waybill_id') LIKE '%$waybill_id%'");
            $where[] = Db::raw("JSON_UNQUOTE(JSON_EXTRACT(delivery_product_info, '$[*].waybill_id')) LIKE '%$waybill_id%'");
        }
        //下单时间
        if(input('create_time')){
            $timeData = explode(' ~ ',input('create_time'));
            $where[] = ['create_time','>=',strtotime($timeData[0])];
            $where[] = ['create_time','<',strtotime($timeData[1])];
        }
        $list = Db::name("channels_order")
            ->where($where)
            ->order('create_time desc')
            ->paginate($page)
            ->toArray();
        $data = $list['data'];
        $excel_data = [];

        foreach($data as $k=>$v){
            $v['member_name'] = '';
            $member = [];
            if($v['mid']){
                $member =  Db::name('member')->where('id',$v['mid'])->where('aid',aid)->field('id,nickname')->find();
            }
            if(!empty($v['unionid'])){
                $member =  Db::name('member')->where('aid',aid)->where('unionid',$v['unionid'])->field('id,nickname')->find();
            }
            if(!$member && !empty($v['openid'])){
                $member =  Db::name('member')->where('aid',aid)->where('channels_openid',$v['openid'])->field('id,nickname')->find();
            }
            if($member){
                $v['member_name'] = $member['nickname'].'(ID:'.$member['id'].')';
            }

            $v['sharer_name'] = '';
            if(!empty($v['sharer_openid'])){
                $sharer = Db::name('channels_sharer')->where('bid',bid)->where('openid',$v['sharer_openid'])->where('aid',aid)->field('id,nickname,openid')->find();
                if($sharer){
                    $v['sharer_name'] = $sharer['nickname'].'(ID:'.$sharer['id'].')';
                }else{
                    $v['sharer_name'] = $v['sharer_openid'];
                }
            }

            //地址信息
            $address_info = json_decode($v['address_info'],true);
            if($v['deliver_method']==0){
                $address_html = $address_info['user_name'] .$address_info['tel_number'].
                    $address_info['province_name'].$address_info['city_name'].$address_info['county_name'];
            }else{
                $address_html = $address_info['user_name'] .$address_info['virtual_order_tel_number'];
            }
            //商品信息
            $oglist = Db::name('channels_order_goods')->where('order_id',$v['order_id'])->select()->toArray();
            foreach($oglist as $og){
                $ggname = Db::name('channels_product_guige')->where('product_id',$og['product_id'])->where('sku_id',$og['sku_id'])->value('name');


                $parent1commission = $og['parent1'] ? $og['parent1commission'] : 0;
                $parent2commission = $og['parent2'] ? $og['parent2commission'] : 0;
                $parent3commission = $og['parent3'] ? $og['parent3commission'] : 0;
                $totalcommission = $parent1commission+$parent2commission+$parent3commission;
                if($og['parent1']){
                    $parent1 = Db::name('member')->where('id',$og['parent1'])->find();
                    $parent1str = $parent1['nickname'].'(会员ID:'.$parent1['id'].')';
                }else{
                    $parent1str = '';
                }
                if($og['parent2']){
                    $parent2 = Db::name('member')->where('id',$og['parent2'])->find();
                    $parent2str = $parent2['nickname'].'(会员ID:'.$parent2['id'].')';
                }else{
                    $parent2str = '';
                }
                if($og['parent3']){
                    $parent3 = Db::name('member')->where('id',$og['parent3'])->find();
                    $parent3str = $parent3['nickname'].'(会员ID:'.$parent3['id'].')';
                }else{
                    $parent3str = '';
                }

                $excel_data[] = [
                    $v['order_id'],
                    $og['title'],
                    $ggname,
                    $og['sale_price'],
                    $og['sku_cnt'],
                    $og['real_price'],
                    $order_status[$v['status']],
                    date('Y-m-d H:i:s',$v['create_time']),
                    $v['transaction_id'],
                    $address_html,
                    $v['settle_time']?date('Y-m-d H:i:s',$v['settle_time']):'',
                    $v['member_name'],
                    $v['sharer_name'],
                    $sharer_type_arr[$v['sharer_type']],
                    $share_scene_arr[$v['share_scene']],
                    $parent1commission,
                    $parent1str,
                    $parent2commission,
                    $parent2str,
                    $parent3commission,
                    $parent3str,
                ];
            }
        }
        $title = ['订单编号','商品名称','商品规格','商品价格','商品数量','支付价格','状态','下单时间','支付单号','收货地址','结算时间','下单'.t('会员'),'分享员','分享员类型','分享场景','一级佣金','会员信息','二级佣金','会员信息','三级佣金','会员信息'];
        return json(['code' => 0, 'msg' => '查询成功', 'count' => $list['total'], 'data' => $excel_data,'title'=>$title]);
    }

    //查物流
    public function getExpress(){
        $id = input('post.id/d');
        $order = Db::name('channels_order')->where('id',$id)->where('aid',aid)->where('bid',bid)->find();
        $delivery_product_info = json_decode($order['delivery_product_info'],true);
        $list = [];
        if($delivery_product_info){
            foreach($delivery_product_info as $delivery){
                if($delivery['delivery_id'] == 'SF'){
                    $address_info = json_decode($delivery['address_info'],true);
                    $totel = $address_info['tel_number'];
                    $delivery['waybill_id'] = $delivery['waybill_id'].":".substr($totel,-4);
                }
                $oglist = [];
                foreach($delivery['product_infos'] as $product){
                    $og = Db::name('channels_product')
                        ->alias('p')
                        ->join('channels_product_guige g','g.product_id=p.product_id')
                        ->where('p.product_id',$product['product_id'])
                        ->where('g.sku_id',$product['sku_id'])
                        ->field('p.name,p.pic,g.name guigename,g.sell_price')->find();
                    $og['product_cnt'] = $product['product_cnt'];
                    $oglist[] = $og;
                }
                $list[] = [
                    'express_no' => $delivery['waybill_id'],
                    'express_com' => $delivery['delivery_name'],
                    'express_data' => \app\common\Common::getwuliu($delivery['waybill_id'],'','', aid),
                    'oglist'=>$oglist,
                ];
            }
        }
        return json(['status'=>1,'data'=>$list]);
    }
}
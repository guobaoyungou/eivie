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
//视频号小店 优惠券
namespace app\controller;

use app\common\System;
use think\facade\View;
use think\facade\Db;

class WxChannelsCoupon extends Common
{
    public function initialize()
    {
        parent::initialize();
        if (!getcustom('wx_channels_business') && bid > 0) showmsg('无访问权限');
        $this->appid = \app\common\WxChannels::defaultApp(aid,bid);
        $childmenu = [
            [
                'path' => 'WxChannelsCoupon/index',
                'name' => '平台优惠券'
            ],
            [
                'path' => 'WxChannelsUserCoupon/index',
                'name' => '用户优惠券'
            ],
        ];
        View::assign('childmenu',$childmenu);
        $thispath = request()->controller().'/'.request()->action();
        View::assign('thispath',$thispath);
    }
    public function index()
    {
        $coupon_type_arr = \app\common\WxChannels::coupon_type;
        $coupon_status_arr = \app\common\WxChannels::coupon_status;
        $valid_type_arr = \app\common\WxChannels::coupon_valid_type;
        $promote_type_arr = \app\common\WxChannels::coupon_promote_type;
//        $coupon_coupon_status_arr = \app\common\WxChannels::coupon_coupon_status;
        if (request()->isAjax()) {
            $page = [
                "list_rows" => input('limit', 20),
                "page" => input('page', 1),
            ];
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['appid','=',$this->appid];
            if(input('coupon_id')){
                $where[] = ['coupon_id','=',input('coupon_id')];
            }
            if(input('?param.status') && input('param.status')!==''){
                $where[] = ['status','=',input('status')];
            }
            if(input('param.field') && input('param.order')){
                $order = input('param.field').' '.input('param.order');
            }else{
                $order = 'id desc';
            }
            if(input('name')){
                $where[] = ['name','like','%'.input('name').'%'];
            }
            $list = Db::name("channels_coupon")
                ->where($where)
                ->order($order)
                ->paginate($page)
                ->toArray();
            $data = $list['data'];
            foreach($data as $k=>$v){
//                $data[$k]['discount_num'] = $v['discount_num']/1000;//折扣
                $data[$k]['type_str'] = $coupon_type_arr[$v['type']];
                $data[$k]['money_str'] = \app\common\WxChannels::getCouponMoneyStr($v,$v['type']);//金额 满100-1
                $data[$k]['status_str'] = $coupon_status_arr[$v['status']];
//                $data[$k]['coupon_status_str'] = $coupon_coupon_status_arr[$v['coupon_status']];
                $data[$k]['valid_type_str'] = \app\common\WxChannels::getCouponValidStr($v,$v['valid_type']);//有效期
                $data[$k]['total_receive'] = $v['receive_num']+$v['used_num'];//领取总数=优惠券领用但未使用量+优惠券已用量

                //优惠券推广类型
                $data[$k]['promote_type_str'] = $promote_type_arr[$v['promote_type']];
            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $list['total'], 'data' => $data]);
        }
        View::assign('coupon_type_arr',$coupon_type_arr);

//        View::assign('coupon_coupon_status_arr',$coupon_coupon_status_arr);
        View::assign('coupon_status_arr',$coupon_status_arr);
        return View::fetch();
    }
    //同步商品列表
    public function asyncAllCoupon()
    {
        Db::startTrans();
        try {
            $input = input();
            $next_key = input('next_key');
            $params = [];
            if($next_key){
                $params['page_ctx'] = $next_key;
            }
            $params['page'] = (int)input('pagenum');
            $params['page_size'] = 20;

            //获取订单id列表
            $res = \app\common\WxChannels::asyncCouponAll(aid,bid, $this->appid, $params);
            if($res['status'] == 0){
                return json($res);
            }
            $coupon_ids = $res['data'];
//            $order_ids = ['37423523451235145'];

            foreach ($coupon_ids as $coupon_id) {
                $res = \app\common\WxChannels::updateCoupon(aid,bid, $this->appid, $coupon_id['coupon_id']);
            }

            Db::commit();
            return json(['status' => 1, 'msg' => '同步成功','next_key'=>$res['page_ctx']]);
        } catch (\Throwable $t) {
            Db::rollback();
            return json(['status' => 0, 'msg' => $t->getMessage()]);
        }
    }
    //添加优惠券
    public function edit(){
        $id = input('id');
        $info = Db::name('channels_coupon')->where('id',$id)->where('aid',aid)->where('bid',bid)->find();
        if(request()->isPost()){
            $data = input('info');
            $recieve_time = explode(' ~ ',$data['receive_info_time']);
            $data['start_time'] = strtotime($recieve_time[0]);
            $data['end_time'] = strtotime($recieve_time[1]);
            unset($data['recieve_time']);
            $valid_time =  explode(' ~ ',$data['valid_time']);
            $data['valid_start_time'] = strtotime($valid_time[0]);
            $data['valid_end_time'] = strtotime($valid_time[1]);
            unset($data['valid_time']);
            unset($data['receive_info_time']);
            $params = [];
            $params['type'] = (int)$data['type'];
            $params['name'] = $data['name'];
            $discount_info = [
                'discount_condition' => [
                    'product_cnt' => (int)$data['product_cnt'],
                    'product_ids' => $data['product_ids'],
                    'product_price' => (int)bcmul($data['product_price'],100,0),
                ],
                'discount_fee' => (int)bcmul($data['discount_fee'],100,0),
                'discount_num' => (int)$data['discount_num']
            ];
            if(!in_array($params['type'],[2,4,102,104])){
                unset($discount_info['discount_fee']);
            }
            if(!in_array($params['type'],[2,102])){
                unset($discount_info['discount_condition']['product_price']);
            }
            $params['discount_info'] = $discount_info;
            $ext_info = [
                'jump_product_id' => $data['jump_product_id'],
                'notes' => $data['notes'],
            ];
            $params['ext_info'] = $ext_info;
            $promote_info = [
                'promote_type' => (int)$data['promote_type'],
            ];
            $params['promote_info'] = $promote_info;
            $receive_info = [
                'limit_num_one_person' => (int)$data['limit_num_one_person'],
                'start_time' => (int)$data['start_time'],
                'end_time' => (int)$data['end_time'],
                'total_num' => (int)$data['total_num'],
            ];
            $params['receive_info'] = $receive_info;
            if($data['valid_type']==1){
                $valid_info = [
                    'end_time' => $data['valid_end_time'],
                    'start_time' => $data['valid_start_time'],
                    'valid_type' => (int)$data['valid_type'],
                ];
            }else{
                $valid_info = [
                    'valid_day_num' => (int)$data['valid_day_num'],
                    'valid_type' => (int)$data['valid_type'],
                ];
            }

            $params['valid_info'] = $valid_info;
            $params['auto_valid_info'] = [
                'auto_valid_type' => (int)$data['auto_valid_type']
            ];
            if($info && $info['coupon_id']){
                $params['coupon_id'] = $info['coupon_id'];
                $res = \app\common\WxChannels::editCoupon(aid,bid,$this->appid,$params);
                if(!$res['status']) {
                    return json($res);
                }
                $coupon_id = $info['coupon_id'];
            }else{
                $res = \app\common\WxChannels::addCoupon(aid,bid,$this->appid,$params);
                if(!$res['status']){
                    return json($res);
                }else{
                    $coupon_id = $res['data'];
                }
            }
            $data['coupon_id'] = $coupon_id;
            if($id){
                Db::name('channels_coupon')->where('id',$id)->update($data);
            }else{
                $data['aid'] = aid;
                $data['bid'] = bid;
                $data['appid'] = $this->appid;
                Db::name('channels_coupon')->insert($data);
            }
            \app\common\System::plog("修改视频号小店优惠券：".$coupon_id);
            return json(['status'=>1,'msg'=>'操作成功']);
        }else{
            //优惠券类型
            $coupon_type_arr = \app\common\WxChannels::coupon_type;
            View::assign('coupon_type_arr',$coupon_type_arr);
            //优惠券推广类型
            $promote_type_arr = \app\common\WxChannels::coupon_promote_type;
            View::assign('promote_type_arr',$promote_type_arr);
            //优惠券有效期类型
            $auto_valid_type_arr = \app\common\WxChannels::auto_valid_type;
            View::assign('auto_valid_type_arr',$auto_valid_type_arr);
            if($info){
                $info['receive_info_time'] = date('Y-m-d H:i:s',$info['start_time']).' ~ '.date('Y-m-d H:i:s',$info['end_time']);
                $info['valid_time'] = date('Y-m-d H:i:s',$info['valid_start_time']).' ~ '.date('Y-m-d H:i:s',$info['valid_end_time']);
            }
            View::assign('info',$info);
            $product_list = [];
            if($info && $info['product_ids']){
                $product_list = Db::name('channels_product')->where('product_id','in',$info['product_ids'])->select()->toArray();
            }
            View::assign('product_list',$product_list);
            return View::fetch();
        }
    }

    public function edit_status(){
        $coupon_id = input('coupon_id');
        $status = input('status/d');
        $info = Db::name('channels_coupon')->where('coupon_id',$coupon_id)->where('aid',aid)->where('bid',bid)->find();
        $params = [
            'coupon_id' => $info['coupon_id'],
            'status' => $status
        ];
        $res = \app\common\WxChannels::editCouponStaus(aid,bid,$this->appid,$params);
        if(!$res['status']){
            return json($res);
        }
        Db::name('channels_coupon')->where('id',$info['id'])->update(['status'=>$status]);
        \app\common\System::plog("修改视频号小店优惠券状态：".$info['coupon_id']);
        return json(['status'=>1,'msg'=>'修改成功']);
    }

    public function getdetail(){
        $id = input('id');
        $data = Db::name('channels_coupon')->where('id',$id)->where('aid',aid)->where('bid',bid)->find();
        $coupon_type_arr = \app\common\WxChannels::coupon_type;
        $coupon_status_arr = \app\common\WxChannels::coupon_status;
        $promote_type_arr = \app\common\WxChannels::coupon_promote_type;
        $data['type_str'] = $coupon_type_arr[$data['type']];
        $data['money_str'] = \app\common\WxChannels::getCouponMoneyStr($data,$data['type']);//金额 满100-1
        $data['status_str'] = $coupon_status_arr[$data['status']];
        $data['valid_type_str'] = \app\common\WxChannels::getCouponValidStr($data,$data['valid_type']);//有效期
        $data['total_receive'] = $data['receive_num']+$data['used_num'];//领取总数=优惠券领用但未使用量+优惠券已用量

        //优惠券推广类型
        $data['promote_type_str'] = $promote_type_arr[$data['promote_type']];

        if($data && $data['product_ids']){
            $data['product_list'] = Db::name('channels_product')->where('product_id','in',$data['product_ids'])->select()->toArray();
        }
        return json($data);
    }
}
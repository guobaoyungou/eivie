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

class WxChannelsUserCoupon extends Common
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
        $coupon_status_arr = \app\common\WxChannels::user_coupon_status;
        $coupon_type_arr = \app\common\WxChannels::coupon_type;
        if (request()->isAjax()) {
            $page = [
                "list_rows" => input('limit', 20),
                "page" => input('page', 1),
            ];
            if(input('param.field') && input('param.order')){
                $order = 'uc.'.input('param.field').' '.input('param.order');
            }else{
                $order = 'uc.id desc';
            }
            $where = [];
            $where[] = ['uc.aid','=',aid];
            $where[] = ['uc.bid','=',bid];
            $where[] = ['uc.appid','=',$this->appid];
            if(input('coupon_id')){
                $where[] = ['uc.coupon_id','=',input('coupon_id')];
            }
            if(input('?param.status') && input('param.status')!==''){
                $where[] = ['uc.status','=',input('status')];
            }
            $field = 'uc.*,c.name,c.type,c.discount_num,c.product_price,c.discount_fee,c.product_cnt';
            $list = Db::name("channels_user_coupon")
                ->alias('uc')
                ->join('channels_coupon c','uc.coupon_id=c.coupon_id')
                ->where($where)
                ->field($field)
                ->order('id desc')
                ->paginate($page)
                ->toArray();
            $data = $list['data'];
            foreach($data as $k=>$v){
                $data[$k]['status_str'] = $coupon_status_arr[$v['status']];
                $data[$k]['type_str'] = $coupon_type_arr[$v['type']];
                $data[$k]['money_str'] = \app\common\WxChannels::getCouponMoneyStr($v,$v['type']);//金额 满100-1
            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $list['total'], 'data' => $data]);
        }
        View::assign('coupon_type_arr',$coupon_status_arr);
        View::assign('coupon_status_arr',$coupon_status_arr);
        return View::fetch();
    }
    //同步商品列表
    public function asyncAllCoupon()
    {
        Db::startTrans();
        try {
            $openid = input('openid');
            $next_key = input('next_key');
            $params = [];
            if($next_key){
                $params['page_ctx'] = $next_key;
            }
            $params['page'] = (int)input('pagenum');
            $params['page_size'] = 10;
            $params['openid'] = $openid;

            //获取订单id列表
            $res = \app\common\WxChannels::asyncUserCouponAll(aid,bid, $this->appid, $params);
            if($res['status'] == 0){
                return json($res);
            }
            $coupon_ids = $res['data'];
            foreach ($coupon_ids as $coupon_id) {
                $res = \app\common\WxChannels::updateUserCoupon(aid,bid, $this->appid, $coupon_id['user_coupon_id'],$openid);
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
            $params = [];
            $params['type'] = (int)$data['type'];
            $params['name'] = $data['name'];
            $discount_info = [
                'discount_condition' => [
                    'product_cnt' => (int)$data['product_cnt'],
                    'product_ids' => $data['product_ids'],
                    'product_price' => $data['product_price'],
                ],
                'discount_fee' => $data['discount_fee'],
                'discount_num' => $data['discount_num']
            ];
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
                'limit_num_one_person' => $data['limit_num_one_person'],
                'start_time' => $data['start_time'],
                'total_num' => $data['total_num'],
            ];
            $params['receive_info'] = $receive_info;
            $valid_info = [
                'end_time' => $data['valid_end_time'],
                'start_time' => $data['valid_start_time'],
                'valid_day_num' => $data['valid_day_num'],
                'valid_type' => (int)$data['valid_type'],
            ];
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
                $coupon_id = $res['coupon_id'];
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
        }else{
            $promote_type_arr = \app\common\WxChannels::coupon_promote_type;
            View::assign('promote_type_arr',$promote_type_arr);

            $auto_valid_type_arr = \app\common\WxChannels::auto_valid_type;
            View::assign('auto_valid_type_arr',$auto_valid_type_arr);
            return View::fetch();
        }
    }

}
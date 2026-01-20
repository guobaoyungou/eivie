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

class WxChannelsAfterSales extends Common
{
    public function initialize()
    {
        parent::initialize();
        if (!getcustom('wx_channels_business') && bid > 0) showmsg('无访问权限');
        $this->appid = \app\common\WxChannels::defaultApp(aid,bid);
    }
    public function index()
    {
        $after_sale_status = \app\common\WxChannels::after_sale_status;
        $after_refund_reason = \app\common\WxChannels::after_refund_reason;
        $after_refund_reason2 = \app\common\WxChannels::after_sale_reason;
        $after_sale_type = \app\common\WxChannels::after_sale_type;
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
            //售后单号
            if(input('after_sale_order_id')){
                $where[] = ['after_sale_order_id','like','%'.input('after_sale_order_id').'%'];
            }
            //订单号
            if(input('order_id')){
                $where[] = ['order_id','like','%'.input('order_id').'%'];
            }
            //退款原因
            if(input('reason_text')){
                $where[] = ['reason_text','=',input('reason_text')];
            }
            //退款类型
            if(input('type')){
                $where[] = ['type','=',input('type')];
            }
            //手机号
            if(input('tel_number')){
                $where[] = ['tel_number','=',input('tel_number')];
            }
            //申请时间
            if(input('create_time')){
                $timeData = explode(' ~ ',input('create_time'));
                $where[] = ['create_time','>=',strtotime($timeData[0])];
                $where[] = ['create_time','<',strtotime($timeData[1])];
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
            //物流单号
            if(input('waybill_id')){
                $where[] = ['waybill_id','like','%'.input('waybill_id').'%'];
            }
            if(input('openid')){
                $where[] = ['openid','like','%'.input('openid').'%'];
            }
            if(input('param.field') && input('param.order')){
                $order = input('param.field').' '.input('param.order');
            }else{
                $order = 'id desc';
            }
            $list = Db::name("channels_after_sales")
                ->where($where)
                ->order($order)
                ->paginate($page)
                ->toArray();
            $data = $list['data'];
            foreach($data as $k=>$v){
                $data[$k]['status_name'] = $after_sale_status[$v['status']];
                $data[$k]['refund_reason_str'] = $after_refund_reason[$v['refund_reason']];
                $data[$k]['reason_str'] = $after_refund_reason2[$v['reason']];
                $data[$k]['type_str'] = $after_sale_type[$v['type']];

                $member = Db::name('member')->field('headimg,nickname')->where('unionid',$v['unionid'])->find();
                if($member){
                    $openidHtml ='<div><img src="'.$member['headimg'].'" style="height:50px"></div>';
                    $openidHtml.='<div style="margin: 10px 0;">'.$member['nickname'].'</div>';
                    $data[$k]['openid'] = $openidHtml;
                }

                //商品信息
                $map = [];
                $map[] = ['g.product_id','=',$v['product_id']];
                $map[] = ['g.sku_id','=',$v['sku_id']];
                $field = 'p.name,p.pic,g.name guige_name,g.sell_price';
                $product_info = Db::name('channels_product')
                    ->alias('p')
                    ->join('channels_product_guige g','p.product_id=g.product_id')
                    ->where($map)
                    ->field($field)
                    ->find();
                    $goodshtml = '<div style="font-size:12px;float:left;clear:both;margin:1px 0">'.
                        '<div class="table-imgbox"><img style="max-width: 60px;max-height: 60px;" src="'.$product_info['pic'].'" ></div>'.
                        '<div style="float: left;width:180px;margin-left: 10px;white-space:normal;line-height:16px;">'.
                        '<div style="width:100%;min-height:25px;max-height:32px;overflow:hidden">'.$product_info['name'].'</div>'.
                        '<div style="padding-top:0px;color:#f60"><span style="color:#888">'.$product_info['guige_name'].'</span></div>';
                    $goodshtml.='<div style="padding-top:0px;color:#f60;">￥'.$product_info['sell_price'].' × '.$v['count'].'</div>';
                    $goodshtml.='</div>';
                    $goodshtml.='</div>';


                $data[$k]['goodsdata'] = $goodshtml;
            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $list['total'], 'data' => $data]);
        }
        View::assign('after_sale_status',$after_sale_status);
        //拒绝售后原因
        $reject_list_res = \app\common\WxChannels::rejectReasonList(aid,bid,$this->appid);
        View::assign('reject_reason_type_arr',$reject_list_res['data']);
        View::assign('after_sale_status',$after_sale_status);
        View::assign('after_refund_reason2',$after_refund_reason2);
        View::assign('after_sale_type',$after_sale_type);
        return View::fetch();
    }
    //同步售后订单
    public function asyncAllOrder()
    {
        Db::startTrans();
        try {
            $input = input();
            $ctime = explode(' ~ ',$input['create_time_range']);
            $start_time = strtotime($ctime[0]);
            $end_time = strtotime($ctime[1]);
            $next_key = input('next_key');
            $params = [];
            $params['begin_create_time'] = $start_time;
            $params['end_create_time'] = $end_time;
            if(!empty($input['status'])){
                $params['status'] = (int)$input['status'];
            }
            if($next_key){
                $params['next_key'] = $next_key;
            }
            $params['page_size'] = 20;

            //售后的单列表
            $res = \app\common\WxChannels::asyncAfterSaleAll(aid,bid, $this->appid, $params);
            if($res['status'] == 0 || !$res['has_more']){
                if($res['status']==0){
                    return json($res);
                }
            }
            $order_ids = $res['data'];
//            $order_ids = ['123456'];

            foreach ($order_ids as $order_id) {
                //售后单详情
                $res = \app\common\WxChannels::updateAfterSale(aid,bid, $this->appid, $order_id);
            }

            Db::commit();
            return json(['status' => 1, 'msg' => '同步成功','next_key'=>$res['next_key'],'continue_flag'=>$res['continue_flag']]);
        } catch (\Throwable $t) {
            Db::rollback();
            return json(['status' => 0, 'msg' => $t->getMessage()]);
        }
    }
    //查看售后单详情
    public function view(){
        $id = input('id');
        $info = Db::name('channels_after_sales')->where('id',$id)->find();
        $after_refund_reason = \app\common\WxChannels::after_sale_reason;
        $after_sale_status = \app\common\WxChannels::after_sale_status;
        $info['refund_reason_str'] = $after_refund_reason[$info['refund_reason']];
        if($info['media_id_list'] ){
            if(!$info['media_url_list']){
                $media_arr = explode(',',$info['media_id_list']);
                $media_url_arr = [];
                foreach($media_arr as $media_id){
                    $res = \app\common\WxChannels::getmedia(aid,bid,$this->appid,$media_id);
                    if($res['status']){
                        $media_url_arr[] = binaryDEcodeImage(aid,$res['data']);
                    }
                }
                if($media_url_arr){
                    Db::name('channels_after_sales')->where('id',$id)->update(['media_url_list'=>implode(',',$media_url_arr)]);
                }
            }else{
                $media_url_arr = explode(',',$info['media_url_list']);
            }
        }
        //是否需要审核
        if($info['status']=='MERCHANT_PROCESSING' || $info['status']=='MERCHANT_WAIT_RECEIPT'){
            $info['can_audit'] = 1;
        }else{
            $info['can_audit'] = 0;
        }
        $info['status_str'] = $after_sale_status[$info['status']];

        //产品信息
        $goods_info = Db::name('channels_product')
            ->alias('p')
            ->join('channels_product_guige g','p.product_id=g.product_id')
            ->where('g.product_id',$info['product_id'])
            ->where('g.sku_id',$info['sku_id'])
            ->field('p.name,g.name guigename,g.product_id,g.sku_id')
            ->find();

        View::assign('info',$info);
        View::assign('goods_info',$goods_info);
        View::assign('media_url_arr',$media_url_arr);

        //拒绝售后原因
        $reject_list_res = \app\common\WxChannels::rejectReasonList(aid,bid,$this->appid);
        View::assign('reject_reason_type_arr',$reject_list_res['data']);
        return View::fetch();
    }
    public function getdetail(){
        $id = input('id');
        $info = Db::name('channels_after_sales')->where('id',$id)->find();
        $after_refund_reason = \app\common\WxChannels::after_sale_reason;
        $after_sale_status = \app\common\WxChannels::after_sale_status;
        $info['refund_reason_str'] = $after_refund_reason[$info['refund_reason']];
        if($info['media_id_list'] ){
            if(!$info['media_url_list']){
                $media_arr = explode(',',$info['media_id_list']);
                $media_url_arr = [];
                foreach($media_arr as $media_id){
                    $res = \app\common\WxChannels::getmedia(aid,bid,$this->appid,$media_id);
                    if($res['status']){
                        $media_url_arr[] = binaryDEcodeImage(aid,$res['data']);
                    }
                }
                if($media_url_arr){
                    Db::name('channels_after_sales')->where('id',$id)->update(['media_url_list'=>implode(',',$media_url_arr)]);
                }
            }else{
                $media_url_arr = explode(',',$info['media_url_list']);
            }
        }
        if($info['refund_certificates_mediaid'] ){
            if(!$info['refund_certificates']){
                $refund_certificates_arr = explode(',',$info['refund_certificates_mediaid']);
                $refund_certificates_url_arr = [];
                foreach($refund_certificates_arr as $refund_certificates_id){
                    $res = \app\common\WxChannels::getmedia(aid,bid,$this->appid,$refund_certificates_id);
                    if($res['status']){
                        $refund_certificates_url_arr[] = binaryDEcodeImage(aid,$res['data']);
                    }
                }
                if($refund_certificates_url_arr){
                    Db::name('channels_after_sales')->where('id',$id)->update(['refund_certificates'=>implode(',',$refund_certificates_url_arr)]);
                }
            }else{
                $refund_certificates_url_arr = explode(',',$info['refund_certificates']);
            }
        }
        //是否需要审核
        if($info['status']=='MERCHANT_PROCESSING' || $info['status']=='MERCHANT_WAIT_RECEIPT'){
            $info['can_audit'] = 1;
        }else{
            $info['can_audit'] = 0;
        }
        $info['status_str'] = $after_sale_status[$info['status']];

        //拒绝售后原因
        $reject_list_res = \app\common\WxChannels::rejectReasonList(aid,bid,$this->appid);
        $info['reject_reason_type_str'] = $reject_list_res['data'][$info['reject_reason_type']]??'';

        //产品信息
        $goods_info = Db::name('channels_product')
            ->alias('p')
            ->join('channels_product_guige g','p.product_id=g.product_id')
            ->where('g.product_id',$info['product_id'])
            ->where('g.sku_id',$info['sku_id'])
            ->field('p.name,p.pic,g.name guigename,g.product_id,g.sku_id')
            ->select()->toArray();
        //拒绝售后原因
        $reject_list_res = \app\common\WxChannels::rejectReasonList(aid,bid,$this->appid);
        foreach($reject_list_res['data'] as $k=>$v){
            if($v['reject_reason_type']==$info['reject_reason_type']){
                $info['reject_reason_type_str'] = $v['reject_reason_type_text'];
            }
        }

        $order = Db::name('channels_order')
            ->where('aid',aid)->where('appid',$this->appid)
            ->where('order_id',$info['order_id'])
            ->where('bid',bid)
            ->find();
        $ogwhere = [];
        $ogwhere[] = ['order_id','=',$order['order_id']];
        $ogwhere[] = ['product_id','=',$info['product_id']];
        $ogwhere[] = ['sku_id','=',$info['sku_id']];
        $oglist = Db::name('channels_order_goods')->where($ogwhere)->select()->toArray();
        foreach($oglist as $k=>$v){
            $guige_name = Db::name('channels_product_guige')->where('product_id',$v['product_id'])->where('sku_id',$v['sku_id'])->value('name');
            $oglist[$k]['guige_name'] = $guige_name;
        }
        $member = Db::name('member')->field('id,nickname,headimg,realname,tel,wxopenid,unionid')->where('unionid',$order['unionid'])->find();
        if(!$member) $member = ['id'=>$order['openid'],'nickname'=>$order['openid'],'headimg'=>''];

        $refund_certificates = '';
        if($info['refund_certificates']){
            $refund_certificates = explode(',',$info['refund_certificates']);
        }
        $data = [
            'info' => $info,
            'goods_info' => $goods_info,
            'media_url_arr' => $media_url_arr,
            'refund_certificates_url_arr' => $refund_certificates_url_arr,
            'reject_reason_type_arr' => $reject_list_res['data'],
            'order' => $order,
            'member' => $member,
            'oglist' => $oglist,
            'refund_certificates' => $refund_certificates
        ];
        return json($data);
    }
    public function audit(){
        $id = input('id');
        $info = Db::name('channels_after_sales')->where('id',$id)->find();
        $params = input();
        if($params['status']==1){
            //同意售后
            $info = Db::name('channels_after_sales')->where('id',$id)->find();
            $data = [];
            $data['after_sale_order_id'] = $info['after_sale_order_id'];
            $accept_type = input('accept_type/d');
            if($info['type']=='RETURN'){
                $address_id = input('address_id');
                if(!$address_id){
                    return json(['status'=>0,'msg'=>'请选择退货地址']);
                }
                $data['address_id'] = input('address_id');
                if($accept_type){
                    //1. 同意退货退款，并通知用户退货;
                    //2. 确认收到货并退款给用户
                    $data['accept_type'] = $accept_type;
                }
            }
            $res = \app\common\WxChannels::acceptAfterSale(aid,bid, $this->appid, $data);
            if(!$res['status']){
                return json($res);
            }
            if($info['type']=='RETURN') {
                if($accept_type==1){
                    //更改状态为 待用户退货
                    Db::name('channels_after_sales')->where('id', $id)->update(['status' => 'USER_WAIT_RETURN']);
                }else{
                    //更改状态为退款完成
                    Db::name('channels_after_sales')->where('id', $id)->update(['status' => 'MERCHANT_REFUND_SUCCESS']);
                }
            }else{
                //更改状态为退款完成
                Db::name('channels_after_sales')->where('id', $id)->update(['status' => 'MERCHANT_REFUND_SUCCESS']);
            }
            \app\common\System::plog("同意视频号小店订单售后：".$info['after_sale_order_id']);
            return json(['status'=>1,'msg'=>'操作成功']);
        }else{
            $id = input('id');
            $info = Db::name('channels_after_sales')->where('id',$id)->find();
            $data = [];
            $data['after_sale_order_id'] = $info['after_sale_order_id'];
            $data['reject_reason'] = input('reject_reason');
            $data['reject_reason_type'] = input('reject_reason_type/d');
            $res = \app\common\WxChannels::rejectAfterSale(aid,bid, $this->appid, $data);
            if(!$res['status']){
                return json($res);
            }
            if($info['type']=='RETURN') {
                $status = 'MERCHANT_REJECT_REFUND';
            }else{
                $status = 'MERCHANT_REJECT_RETURN';
            }
            $data_u = [
                'reject_reason' => input('reject_reason'),
                'reject_reason_type' => input('reject_reason_type/d'),
                'status' => $status
            ];
            Db::name('channels_after_sales')->where('id', $id)->update($data_u);
            \app\common\System::plog("拒绝视频号小店订单售后：".$info['after_sale_order_id']);
            return json(['status'=>1,'msg'=>'修改成功']);
        }
    }
    //上传退款凭证
    public function upload_refund(){
        $id = input('id');
        $info = Db::name('channels_after_sales')->where('id',$id)->find();
        $refund_certificates = input('refund_certificates');
        if($refund_certificates){
            $file_ids = Db::name('admin_upload')->where('url','in',$refund_certificates)
                ->where('other_param','channels_wx_mediaid')
                ->column('channels_file_id');
        }
        $desc = input('desc');
        $data = [];
        $data['after_sale_order_id'] = $info['after_sale_order_id'];
        $data['refund_certificates'] = $file_ids?:[];
        $data['desc'] = $desc;

        $res = \app\common\WxChannels::uploadRefund(aid,bid, $this->appid, $data);
        if(!$res['status']){
            return json($res);
        }
        $field_ids = implode(',',$file_ids);
        Db::name('channels_after_sales')->where('id',$id)
            ->update(['refund_certificates'=>$refund_certificates,'refund_desc'=>$desc,'refund_certificates_mediaid'=>$field_ids,'status'=>'USER_WAIT_CONFIRM']);
        \app\common\System::plog("上传视频号小店订单退款凭证：".$info['after_sale_order_id']);
        return json(['status'=>1,'msg'=>'上传成功']);
    }
}
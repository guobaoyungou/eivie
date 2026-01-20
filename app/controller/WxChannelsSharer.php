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
//视频号小店 分享员
namespace app\controller;

use app\common\System;
use think\facade\View;
use think\facade\Db;

class WxChannelsSharer extends Common
{
    public function initialize()
    {
        parent::initialize();
        if (!getcustom('wx_channels_business') && bid > 0) showmsg('无访问权限');
        $this->appid = \app\common\WxChannels::defaultApp(aid,bid);
        $childmenu = [
            [
                'path' => 'WxChannelsSharer/index',
                'name' => '分享员管理'
            ],
            [
                'path' => 'WxChannelsSharer/orders',
                'name' => '分享员订单'
            ],
        ];
        View::assign('childmenu',$childmenu);
        $thispath = request()->controller().'/'.request()->action();
        View::assign('thispath',$thispath);
    }
    public function index()
    {
        $sharer_type_arr = \app\common\WxChannels::sharer_type;
        if (request()->isAjax()) {
            $page = [
                "list_rows" => input('limit', 20),
                "page" => input('page', 1),
            ];
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['appid','=',$this->appid];
            if(input('?param.sharer_type') && input('param.sharer_type')!==''){
                $where[] = ['sharer_type','=',input('sharer_type')];
            }
            $list = Db::name("channels_sharer")
                ->where($where)
                ->order('id desc')
                ->paginate($page)
                ->toArray();
            $data = $list['data'];
            foreach($data as $k=>$v){
                $data[$k]['sharer_type_str'] = $sharer_type_arr[$v['sharer_type']];
                $commission = 0;
                if($v['mid']){
                    if(bid){
                        $commission = Db::name('channels_sharer_commission')->where('sharerid',$v['id'])->where('mid',$v['mid'])->where('bid',bid)->where('aid',aid)->value('commission');
                    }else{
                        $commission = Db::name('member')->where('id',$v['mid'])->value('commission');
                    }
                }
                $data[$k]['commission'] = $commission;
            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $list['total'], 'data' => $data]);
        }

        View::assign('sharer_type_arr',$sharer_type_arr);
        View::assign('bid',bid);
        return View::fetch();
    }
    //同步分享员
    public function asyncAllSharer()
    {
        Db::startTrans();
        try {
            $input = input();
            $params = [];
            $params['page'] = (int)input('pagenum');
            $params['page_size'] = 100;
            $params['sharer_type'] = input('sharer_type/d');

            //获取订单id列表
            $res = \app\common\WxChannels::asyncSharerAll(aid,bid, $this->appid, $params);
            if($res['status'] == 0){
                return json($res);
            }
            $sharer_lists = $res['data'];
            $sharer_openids = Db::name('channels_sharer')->where('aid',aid)->where('bid',bid)->column('openid');
            foreach ($sharer_lists as $sharer) {
                $data = [];
                $data['openid'] = $sharer['openid'];
                $data['nickname'] = $sharer['nickname'];
                $data['bind_time'] = $sharer['bind_time'];
                $data['sharer_type'] = $sharer['sharer_type'];
                $data['unionid'] = $sharer['unionid'];
                if($sharer_openids && in_array($sharer['openid'],$sharer_openids)){
                    if(bid && bid>0){
                        $data['isbind'] = 1;
                    }
                    Db::name('channels_sharer')->where('aid',aid)->where('openid',$sharer['openid'])->where('bid',bid)->update($data);
                    if(bid && bid>0){
                        $sharers = Db::name('channels_sharer')->where('aid',aid)->where('openid',$sharer['openid'])->where('bid',bid)->select()->toArray();
                        if($sharers){
                            foreach($sharers as $sv){
                                if(empty($sv['mid'])) continue;

                                //查询是否有佣金账号表
                                $sharer_commission = Db::name('channels_sharer_commission')->where('sharerid',$sv['id'])->where('mid',$sv['mid'])->where('bid',bid)->where('aid',aid)->field('id')->find();
                                $data2 = [];
                                $data2['appid']   = $sv['appid'];
                                $data2['openid']  = $sv['openid'];
                                $data2['unionid'] = $sv['unionid'];
                                if(!$sharer_commission){
                                    //增加多商户佣金账号表
                                    $data2['aid']     = aid;
                                    $data2['bid']     = bid;
                                    $data2['mid']     = $sv['mid'];
                                    $data2['sharerid']= $sv['id'];
                                    $data2['commission'] = 0;
                                    $data2['createtime'] = time();
                                    $sharerid = Db::name('channels_sharer_commission')->insertGetId($data2);
                                }else{
                                    $data2['updatetime'] = time();
                                    Db::name('channels_sharer_commission')->where('id',$sharer_commission['id'])->update($data2);
                                }
                            }
                            unset($sv);
                        }
                    }
                }else{
                    $data['aid'] = aid;
                    $data['bid'] = bid;
                    $data['appid'] = $this->appid;
                    $sharerid = Db::name('channels_sharer')->insertGetId($data);
                }
                if($sharer['unionid']){
                    $member = Db::name('member')->where('unionid',$sharer['unionid'])->where('aid',aid)->find();
                    if(!$member){
                        //注册会员 创建的此会员可能为空会员，在wx_channels_sharer_apply定制里会有删除操作，请谨慎增加此会员事件
                        $data = [];
                        $data['aid'] = aid;
                        $data['unionid'] = $sharer['unionid'];
                        $data['createtime'] = time();
                        $data['last_visittime'] = time();
                        $data['nickname'] = $sharer['nickname'];
                        $data['channels_openid'] = $sharer['openid']??'';
                        $data['platform'] = 'wx_channels';
                        $mid = \app\model\Member::add(aid,$data);
                    }else{
                        $mid = $member['id'];
                    }
                    $data = [];
                    $data['mid'] = $mid;
                    if(bid && bid>0){
                        $data['isbind'] = 1;
                    }
                    Db::name('channels_sharer')->where('aid',aid)->where('unionid',$sharer['unionid'])->where('bid',bid)->update($data);
                    if(bid && bid>0){
                        $sharers = Db::name('channels_sharer')->where('aid',aid)->where('unionid',$sharer['unionid'])->where('bid',bid)->where('mid',$mid)->select()->toArray();
                        if($sharers){
                            foreach($sharers as $sv){
                                if(empty($sv['mid'])) continue;

                                //查询是否有佣金账号表
                                $sharer_commission = Db::name('channels_sharer_commission')->where('sharerid',$sv['id'])->where('mid',$sv['mid'])->where('bid',bid)->where('aid',aid)->field('id')->find();
                                $data2 = [];
                                $data2['appid']   = $sv['appid'];
                                $data2['openid']  = $sv['openid'];
                                $data2['unionid'] = $sv['unionid'];
                                if(!$sharer_commission){
                                    //增加多商户佣金账号表
                                    $data2['aid']     = aid;
                                    $data2['bid']     = bid;
                                    $data2['mid']     = $sv['mid'];
                                    $data2['sharerid']= $sv['id'];
                                    $data2['commission'] = 0;
                                    $data2['createtime'] = time();
                                    $sharerid = Db::name('channels_sharer_commission')->insertGetId($data2);
                                }else{
                                    $data2['updatetime'] = time();
                                    Db::name('channels_sharer_commission')->where('id',$sharer_commission['id'])->update($data2);
                                }
                            }
                            unset($sv);
                        }
                    }
                }
            }

            Db::commit();
            return json(['status' => 1, 'msg' => '同步成功','next_key'=>$res['page_ctx']]);
        } catch (\Throwable $t) {
            Db::rollback();
            return json(['status' => 0, 'msg' => $t->getMessage()]);
        }
    }

    //解绑分享员
    public function unbind(){
        $ids = input('ids');
        $openids = [];
        $sharers = Db::name('channels_sharer')->where('id','in',$ids)->where('aid',aid)->where('bid',bid)->select()->toArray();
        if(!$sharers){
            return json(['status' => 0,'msg' => '分享员不存在']);
        }

        $type = input('?param.type')?input('param.type/d'):0;//0；默认解绑删除 1：删除软解绑的分销员
        if($type == 1){
            $msg = '删除';
        }else{
            $msg = '解绑';
        }
        foreach($sharers as $sharer){
            $openids[] = $sharer['openid'];
            if(bid){
                //查询此分享员佣金账号、及佣金提现是否有佣金未结算完，未结算完，则只能软删除
                $commission1 = 0+Db::name('channels_sharer_commission')->where('sharerid',$sharer['id'])->sum('commission');
                if($commission1>0){
                    return json(['status' => 0,'msg' => '分享员ID:'.$sharer['id'].'佣金未结算完，不能'.$msg]);
                }
                $commission2 = 0+Db::name('channels_sharer_commission_withdrawlog')->where('sharerid',$sharer['id'])->where('status','in','0,1')->sum('txmoney');
                if($commission2>0){
                    return json(['status' => 0,'msg' => '分享员ID:'.$sharer['id'].'佣金未提现完，不能'.$msg]);
                }
            }
        }

        $res = \app\common\WxChannels::unbindSharer(aid,bid,$this->appid,$openids);
        if(!$res['status']){
            if($type == 1){
                Db::name('channels_sharer')->where('id','in',$ids)->where('aid',aid)->where('bid',bid)->where('isbind',0)->delete();
                \app\common\System::plog("删除视频号小店分享员：".implode(',',$ids));
                return json(['status'=>1,'msg'=>'删除成功']);
            }else{
                return json($res);
            }
        }else{
            if($type == 1){
                Db::name('channels_sharer')->where('id','in',$ids)->where('aid',aid)->where('bid',bid)->where('isbind',0)->delete();
                \app\common\System::plog("删除视频号小店分享员：".implode(',',$ids));
                return json(['status'=>1,'msg'=>'删除成功']);
            }else{
                $success_openid = $res['data']['success_openid']??'';
                $fail_openid = $res['data']['fail_openid']??'';
                $refuse_openid = $res['data']['refuse_openid']??'';
                if($success_openid){
                    Db::name('channels_sharer')->where('id','in',$ids)->where('aid',aid)->where('openid','in',$success_openid)->where('bid',bid)->delete();
                }
                \app\common\System::plog("解绑视频号小店分享员：".implode(',',$openids));
                return json(['status'=>1,'msg'=>'解绑成功'.count($success_openid).',解绑失败'.count($fail_openid).',解绑拒绝'.count($refuse_openid)]);
            }
        }
    }
    //获取商品分享连接
    public function get_shareurl(){
        $id = input('id');
        $openid = Db::name('channels_sharer')->where('id',$id)->where('aid',aid)->where('bid',bid)->value('openid');
        $product_id = input('product_id');
        $sharer_product_type = input('sharer_product_type');
        if($sharer_product_type==1){
            $res = \app\common\WxChannels::getSharerH5url(aid,bid,$this->appid,$product_id,$openid);
        }
        if($sharer_product_type==2){
            $res = \app\common\WxChannels::getSharerTaglink(aid,bid,$this->appid,$product_id,$openid);
        }
        if($sharer_product_type==3){
            $res = \app\common\WxChannels::getSharerQrcode(aid,bid,$this->appid,$product_id,$openid);
        }

        if(!$res['status']){
            return json($res);
        }else{
            return json(['status'=>1,'msg'=>'创建成功','share_url'=>$res['data']]);
        }
    }

    //邀请分享员
    public function get_bindurl(){
        $id = input('id');
        $username = input('username');
        $res = \app\common\WxChannels::getBindUrl(aid,bid,$this->appid,$username);
        if(!$res['status']){
            return json($res);
        }else{
            return json(['status'=>1,'msg'=>'创建成功','url'=>'data:image/jpg;base64,'.$res['qrcode_img_base64']]);
        }
    }

    //分享员订单
    public function orders()
    {
        $sharer_type_arr = \app\common\WxChannels::sharer_type;
        if (request()->isAjax()) {
            $page = [
                "list_rows" => input('limit', 20),
                "page" => input('page', 1),
            ];
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['appid','=',$this->appid];
            if(input('order_id')){
                $where[] = ['order_id','=',input('order_id')];
            }
            $list = Db::name("channels_share_orders")
                ->where($where)
                ->order('id desc')
                ->paginate($page)
                ->toArray();
            $data = $list['data'];
            foreach($data as $k=>$v){
                $data[$k]['sharer_type_str'] = $sharer_type_arr[$v['sharer_type']];
            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $list['total'], 'data' => $data]);
        }

        View::assign('sharer_type_arr',$sharer_type_arr);
        return View::fetch();
    }

    //同步分享订单
    public function asyncAllOrders()
    {
        Db::startTrans();
        try {
            $input = input();
            $params = [];
            $params['page'] = (int)input('pagenum');
            $params['page_size'] = 100;
            if(input('sharer_openid')){
                $params['openid'] = input('sharer_openid');
            }
            //获取订单id列表
            $res = \app\common\WxChannels::asyncSharerOrders(aid,bid, $this->appid, $params);
            if($res['status'] == 0){
                return json($res);
            }
            $sharer_lists = $res['data'];
            foreach ($sharer_lists as $order) {
                $data = [];
                $data['order_id'] = $order['order_id'];
                $data['share_scene'] = $order['share_scene'];
                $data['sharer_openid'] = $order['sharer_openid'];
                $data['sharer_type'] = $order['sharer_type'];
                $data['sku_id'] = $order['sku_id'];
                $data['product_id'] = $order['product_id'];
                $data['from_wecom'] = $order['from_wecom'];
                $data['promoter_id'] = $order['promoter_id'];
                $data['finder_nickname'] = $order['finder_nickname'];
                $data['live_export_id'] = $order['live_export_id'];
                $data['video_export_id'] = $order['video_export_id'];
                $data['video_title'] = $order['video_title'];
                $exit = Db::name('channels_share_orders')->where('aid',aid)->where('order_id',$order['order_id'])->where('sharer_openid',$order['sharer_openid'])->where('bid',bid)->find();
                if($exit){
                    Db::name('channels_share_orders')->where('id',$exit['id'])->update($data);
                }else{
                    $data['aid'] = aid;
                    $data['bid'] = bid;
                    $data['appid'] = $this->appid;
                    Db::name('channels_share_orders')->insert($data);
                }
            }

            Db::commit();
            return json(['status' => 1, 'msg' => '同步成功','next_key'=>$res['page_ctx']]);
        } catch (\Throwable $t) {
            Db::rollback();
            return json(['status' => 0, 'msg' => $t->getMessage()]);
        }
    }

    public function tongbumember(){
        }

    public function editbind(){
        $id = input('?param.id')?input('param.id/d'):0;
        $mid = input('?param.mid')?input('param.mid/d'):0;
        $sharer = Db::name('channels_sharer')->where('id',$id)->where('bid',bid)->where('aid',aid)->find();
        if(!$sharer){
            return json(['status' =>0, 'msg' => '分享员不存在']);
        }
        Db::name('channels_sharer')->where('id',$id)->update(['mid'=>$mid]);
        \app\common\System::plog("修改视频号小店分享员".t('会员')."ID： ID".$id.' mid:'.$mid);
        return json(['status' =>1, 'msg' => '操作成功']);
    }
}
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

class WxChannels extends Common
{
    public function initialize()
    {
        parent::initialize();
        if (!getcustom('wx_channels_business') && bid > 0) showmsg('无访问权限');
    }

    public function bind()
    {
        $list = Db::name("admin_setapp_channels")
            ->where('aid', aid)
            ->where('bid', bid)
            ->select()->toArray();
        View::assign('list', $list);
        return View::fetch();
    }


    //添加小店
    public function detail()
    {
        if (request()->isPost()) {
            Db::startTrans();
            $postinfo = input('post.info/a');
            $data = [
                'aid' => aid,
                'bid' => bid,
            ];

            $data['appid'] = trim($postinfo['appid']);
            $checkbind = Db::name('admin_setapp_channels')->where('appid',$data['appid'])->find();
            if($checkbind){
                if($checkbind['aid'] != aid || $checkbind['bid'] != bid){
                    return json(['status'=>0,'msg'=>'其他站点已绑定此小店，不可重复绑定']);
                }
            }

            $data['appsecret'] = trim($postinfo['appsecret']);
            $data['key'] = trim($postinfo['key']);
            $data['token'] = trim($postinfo['token']);
            $data['sph_id'] = trim($postinfo['sph_id']);
            $data['appsecret'] = trim($postinfo['appsecret']);
            $data['key'] = trim($postinfo['key']);
            $data['token'] = trim($postinfo['token']);
            $data['headimg'] = trim($postinfo['headimg']);
            $data['qrcode'] = trim($postinfo['qrcode']);
            $isbind = Db::name('admin_setapp_channels')
                ->where('id', '=', $postinfo['id'])
                ->find();
            if (!$isbind) {
                $store_id = Db::name('admin_setapp_channels')->insertGetId($data);
            }else{
                Db::name('admin_setapp_channels')->where('id',$postinfo['id'])->update($data);
                $store_id = $isbind['id'];
            }
            \app\common\WxChannels::getAccessToken(aid,bid, $postinfo['appid'], ['appsecret'=> $postinfo['appsecret'],'iscache' => false, 'ischeck' => true]);
            if ($store_id) {
                //获取视频号小店基础信息并保存
                $store_info = \app\common\WxChannels::baseInfo(aid,bid, $postinfo['appid']);
                if ($store_info['status'] == 0) {
                    return json(['status' => 0, 'msg' => $store_info['msg']]);
                }
                $base_info['nickname'] = trim($store_info['data']['nickname']);
//                $base_info['headimg'] = trim($store_info['data']['headimg_url']);//不让外部引用
                $base_info['subject_type'] = trim($store_info['data']['subject_type']);
                $base_info['status'] = trim(\app\common\WxChannels::statusText($store_info['data']['status']));
                $base_info['username'] = trim($store_info['data']['username']);
                Db::name('admin_setapp_channels')->where("id", $store_id)->save($base_info);
            }
            Db::commit();
            \app\common\System::plog("绑定视频号小店：【{$data['appid']}】【{$base_info['nickname']}】");
            return json(['status' => 1, 'msg' => '保存成功']);
        }
        $id = input('id', '0');
        $info = Db::name("admin_setapp_channels")
            ->where('aid', aid)
            ->where('id', $id)
            ->where('bid', bid)
            ->find();
        if (!$info) {
            $info = [
                "token" => random(16),
                "key" => random(43),
            ];
        }
        View::assign('info', $info);
        return View::fetch();
    }

    //解绑小店
    public function removeBind()
    {
        $id = input('id');
        $info = Db::name("admin_setapp_channels")
            ->where('id', $id)
            ->where('aid', aid)
            ->where('bid', bid)
            ->find();
        if (!$info) {
            return json([
                'status' => 0,
                'msg' => '小店不存在，刷新后重试'
            ]);
        }

        if(bid){
            //查询此分享员佣金账号、及佣金提现是否有佣金未结算完，未结算完，则不能解绑
            $commission1 = 0+Db::name('channels_sharer_commission')->where('bid',bid)->where('aid',aid)->sum('commission');
            if($commission1>0){
                return json(['status' => 0,'msg' => '有分享员佣金未结算完，不能解绑']);
            }
            $commission2 = 0+Db::name('channels_sharer_commission_withdrawlog')->where('bid',bid)->where('aid',aid)->where('status','in','0,1')->sum('txmoney');
            if($commission2>0){
                return json(['status' => 0,'msg' => '有分享员佣金未提现完，不能解绑']);
            }
        }

        $res = Db::name("admin_setapp_channels")
            ->where('id', $id)
            ->where('aid', aid)
            ->where('bid', bid)
            ->delete();
        if ($res) {
            System::plog("解绑视频号小店：【{$info['appid']}】【{$info['nickname']}】");
            return json([
                'status' => 0,
                'msg' => '解绑成功'
            ]);
        } else {
            return json([
                'status' => 0,
                'msg' => '小店不存在，刷新后重试'
            ]);
        }
    }
    //刷新token
    public function refreshToken()
    {
        $id = input('id');
        $info = Db::name("admin_setapp_channels")
            ->where('id', $id)
            ->where('aid', aid)
            ->where('bid', bid)
            ->find();
        if (!$info) {
            return json([
                'status' => 0,
                'msg' => '小店不存在，刷新后重试'
            ]);
        }
        $res = \app\common\WxChannels::getAccessToken(aid,bid, $info['appid'], ['appsecret'=>$info['appsecret'],'iscache' => false, 'ischeck' => true]);
        if(!$res['status']){
            return json($res);
        }else{
            return json(['status'=>1,'msg'=>'刷新成功！']);
        }
    }
}
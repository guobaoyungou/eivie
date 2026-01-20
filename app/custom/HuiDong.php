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

use think\facade\Db;
use think\facade\Log;

class HuiDong
{

    public function __construct()
    {

    }

    //同步会员
    public static function syncMember()
    {
        $limit = 100;
        $syssetlist = Db::name('admin_set')->field('id,aid,huidong_status,huidong_url')->where('huidong_status',1)->select()->toArray();
        if($syssetlist) {
            foreach ($syssetlist as $v) {
                $appinfo = \app\common\System::appinfo($v['aid'],'wx');
                if(!$appinfo){
                    continue;
                }
                $mlist = Db::name('member')->field('id,aid,wxopenid,unionid,huidong_sync')->where('aid',$v['aid'])->whereNotNull('unionid')->where('unionid','<>','')
                    ->whereNotNull('wxopenid')->where('wxopenid','<>','')->where('huidong_sync',0)->limit($limit)->select()->toArray();

                foreach ($mlist as $item){
                    Log::write([
                        'file'=>__FILE__,
                        '$item'=>$item
                    ]);
                    $rs = self::getHuidongMid($v['huidong_url'],$item['wxopenid'],$item['unionid']);
                    Log::write([
                        'file'=>__FILE__,
                        '$rs'=>$rs
                    ]);
                    if($rs['status'] == 1){
                        Db::name('member')->where('id',$item['id'])->update(['huidong_mid'=>$rs['mid'],'huidong_sync'=>1]);
                    }else{
//                        Db::name('member')->where('id',$item['id'])->update(['huidong_sync'=>-1]);
                    }
                }
            }
        }
    }

    //同步单个会员
    public static function syncMemberSingle($aid,$mid)
    {
//        Log::write([
//            'file'=>__FILE__.__LINE__,
//            'syncMemberSingle'=>'syncMemberSingle'
//        ]);
        $sysset = Db::name('admin_set')->field('id,aid,huidong_status,huidong_url')->where('aid',$aid)->where('huidong_status',1)->find();
//        Log::write([
//            'file'=>__FILE__.__LINE__,
//            '$sysset'=>$sysset
//        ]);
        if($sysset) {
            $appinfo = \app\common\System::appinfo($sysset['aid'],'wx');
//            Log::write([
//                'file'=>__FILE__.__LINE__,
//                '$appinfo'=>$appinfo
//            ]);
            if(!$appinfo){
                return ['status' => 0, 'msg'=>'未绑定小程序'];
            }
            $item = Db::name('member')->field('id,aid,wxopenid,unionid,huidong_sync')->where('id',$mid)->where('aid',$sysset['aid'])->whereNotNull('unionid')->where('unionid','<>','')
                ->whereNotNull('wxopenid')->where('wxopenid','<>','')/*->where('huidong_sync',0)*/->find();
//            Log::write([
//                'file'=>__FILE__.__LINE__,
//                '$item'=>$item
//            ]);
            if(empty($item))
                return ['status' => 0, 'msg'=>'会员不符合条件'];
            $rs = self::getHuidongMid($sysset['huidong_url'],$item['wxopenid'],$item['unionid']);
//            Log::write([
//                'file'=>__FILE__.__LINE__,
//                '$rs'=>$rs
//            ]);
            if($rs['status'] == 1){
                Db::name('member')->where('id',$item['id'])->update(['huidong_mid'=>$rs['mid'],'huidong_sync'=>1]);
            }else{
//                        Db::name('member')->where('id',$item['id'])->update(['huidong_sync'=>-1]);
            }
        }

        return ['status' => 0, 'msg'=>'未开启功能'];
    }



    public static function getHuidongMid($url,$openid,$unionid)
    {
        $param = ['openid'=>$openid,'unionid'=>$unionid];
        $result = request_post($url,$param);
        $res = json_decode($result);
        if($res->errcode == 0 && $res->external_userid) {
            return ['status' => 1, 'mid'=>$res->external_userid];
        }
        //dump($param);
       // dd($res);
        Log::write([
            'file'=>__FILE__.__LINE__,
            'param'=>$param,
            'rs' => $res
        ]);
        return ['status' => 0, 'errcode'=>$res->errcode];
    }
}
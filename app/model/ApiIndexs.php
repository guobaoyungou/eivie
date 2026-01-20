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
class ApiIndexs
{

	public static function loginset($aid,$sysset){
        $loginset_type = 0;
        $loginset_data = '';

        //读取登录设置信息
        $loginset = Db::name('designer_login')->where('aid',$aid)->field('id,data,type')->find();
        if($loginset){
            if(!empty($loginset['data'])){
                $loginset_data = json_decode($loginset['data'],true);
            }
            if(!empty($loginset['type'])){
                $loginset_type = $loginset['type'];
            }
        }else{

            $logo    = PRE_URL.'/static/imgsrc/logo.jpg';
            $sysname = '';
            $bgcolor = '';
            if($sysset){
                if(!empty($sysset['logo'])){
                    $logo = $sysset['logo'];
                }
                $sysname = !empty($sysset['name'])?$sysset['name']:'';
                $bgcolor = !empty($sysset['color1'])?$sysset['color1']:'';
            }

            $loginset = array(
                'aid'=>$aid,
                'type'=>1,
                'updatetime'=>time(),
                'data'=>jsonEncode([
                    "logo"      => $logo,
                    "bgtype"    => 1,
                    "bgcolor"   => $bgcolor,
                    "bgimg"     => PRE_URL.'/static/admin/img/login/bg1.png',
                    "cardcolor" => '#FFFFFF',
                    "titletype" => 'center',
                    "title"     => '欢迎使用'.$sysname,
                    "titlecolor"=> '#000000',
                    "subhead"   => '',
                    "subheadcolor" => '#A8B5D3',
                    "btntype"   => 1,
                    "btncolor"   => '#0256FF',
                    "btnwordcolor" => '#FFFFFF',
                    "codecolor" => '#0256FF',
                    "regtipcolor"  => '#666666',
                    "regpwdbtncolor"  => '#666666',
                    "xytipword"    => '我已阅读并同意',
                    "xytipcolor"   => '#D8D8D8',
                    "xycolor"  => '#0256FF'
                ])
            );
            Db::name('designer_login')->insertGetId($loginset);

            $loginset_type = 1;
            $loginset_data = $loginset_data = json_decode($loginset['data'],true);
        }

        return ['loginset_type'=>$loginset_type,'loginset_data'=>$loginset_data];
    }

    public static function dealadvertising($advertisingset=[],$admin=[],$location=0,$platform=''){
        $advertising = [];
        if(!$advertisingset) $advertisingset = Db::name('advertising_set')->where('aid',1)->where('status',1)->find();

        //查询此时间段的
        if($advertisingset){
            $nowdaytime= strtotime(date("Y-m-d",time()));
            $timerange = date("H:i:s",time());
            //查询此平台是否开启了去广告功能
            if(
                !$admin['advertising_close'] || 
                ( $admin['advertising_close'] && ($admin['advertising_starttime']>$nowdaytime || $admin['advertising_endtime']<$nowdaytime))
            ){
                $advertising = Db::name('advertising')->where('location',$location)->where('aid',1)->where('status',1)
                    ->where('timerange_starttime','<=',$timerange)->where('timerange_endtime','>=',$timerange)
                    ->where('starttime','<=',$nowdaytime)->where('endtime','>=',$nowdaytime)
                    ->whereRaw(Db::raw("find_in_set('".$platform."',platform)"))->find();
            }
        }
        if($advertising && $advertising['type'] == 0){
            $advertising['unitid']   = $advertising['wxunitid'];
            $advertising['adtype']   = $advertising['wxtype'];
            $advertising['bgcolor']  = '#FFFF';
            $advertising['margin_y'] = $advertising['margin_x'] = 0;
            $advertising['padding_y']= $advertising['padding_x']= 0;
        }
        return $advertising??'';
    }

    public static function dealHideDesigner($pageparams=[],$admin=[],$platform=''){
        //查询屏蔽设计页开屏广告是否开启了
        $advertisingset = Db::name('advertising_set')->where('aid',1)->where('status',1)->find();
        $hide_designer = $advertisingset && $advertisingset['hide_designer']?true:false;
        $advertising = '';
        if($hide_designer){
            $pageparams['showgg'] = 0;
            
            if($advertisingset){
                $nowdaytime= strtotime(date("Y-m-d",time()));
                $timerange = date("H:i:s",time());
                if(
                    !$admin['advertising_close'] || 
                    ( $admin['advertising_close'] && ($admin['advertising_starttime']>$nowdaytime || $admin['advertising_endtime']<$nowdaytime))
                ){
                    $advertising = Db::name('advertising')->where('location',9)->where('aid',1)->where('status',1)
                        ->where('starttime','<=',$nowdaytime)->where('endtime','>=',$nowdaytime)
                        ->whereRaw(Db::raw("find_in_set('".$platform."',platform)"))->find();
                }
            }
            if($advertising){
                $pageparams['cishu']  = $advertising['cishu'];//0：首次（默认） 1：每次
                //广告图+链接
                if($advertising['type'] == 1){
                    $pageparams['showgg']  = 1;
                    $pageparams['guanggao']= $advertising['pic'];
                    $pageparams['hrefurl'] = $advertising['pictourl'];
                //视频
                }else if($advertising['type'] == 2){
                    $pageparams['showgg']  = 2;
                    $pageparams['guanggao']= $advertising['video'];
                //小程序广告
                }else{
                    $pageparams['showgg']  = 4;
                    $advertising['unitid']   = $advertising['wxunitid'];
                    $advertising['adtype']   = $advertising['wxtype'];
                    $advertising['bgcolor']  = '#FFFF';
                    $advertising['margin_y'] = $advertising['margin_x'] = 0;
                    $advertising['padding_y']= $advertising['padding_x'] = 8;
                }
                $pageparams['ggrenqun'] =['-1'=>true,'-2'=>true,'0'=>true];
            }
        }
        return [
            'advertisingset'=>$advertisingset,
            'hide_designer'=>$hide_designer,
            'advertising'=>$advertising,
            'pageparams'=>$pageparams
        ];
    }
}
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

// +----------------------------------------------------------------------
// | 预定设置
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class RestaurantBookingSet extends Common
{
    public function initialize(){
		parent::initialize();
		//if(bid > 0) showmsg('无访问权限');
	}
    public function index(){
		$info = Db::name('restaurant_booking_sysset')->where('aid',aid)->where('bid',bid)->find();
		if(!$info){
            $default = [
                'aid'=>aid,
                'bid'=>bid,
                'prehour'=>2,
                'timedata'=>'[{"day":"1","hour":"12","minute":"0","hour2":"12","minute2":"30"},{"day":"1","hour":"18","minute":"0","hour2":"18","minute2":"30"},{"day":"2","hour":"12","minute":"0","hour2":"12","minute2":"30"},{"day":"2","hour":"18","minute":"0","hour2":"18","minute2":"30"}]',
            ];
			Db::name('restaurant_booking_sysset')->insert($default);
			$info = Db::name('restaurant_booking_sysset')->where('aid',aid)->where('bid',bid)->find();
		}
        View::assign('info',$info);

        $is_show = true;
        View::assign('is_show',$is_show);

		return View::fetch();
    }
	public function save(){
		$info = input('post.info/a');

        $timeday = input('post.timeday/a');
        $timehour = input('post.timehour/a');
        $timeminute = input('post.timeminute/a');
        $timehour2 = input('post.timehour2/a');
        $timeminute2 = input('post.timeminute2/a');
        $timedata = [];
        foreach($timeday as $k=>$v){
            $timedata[] = ['day'=>$v,'hour'=>$timehour[$k],'minute'=>$timeminute[$k],'hour2'=>$timehour2[$k],'minute2'=>$timeminute2[$k]];
        }
        $info['timedata'] = json_encode($timedata);
        Db::name('restaurant_booking_sysset')->where('aid',aid)->where('bid',bid)->update($info);
		return json(['status'=>1,'msg'=>'保存成功','url'=>(string)url('index')]);
	}
}
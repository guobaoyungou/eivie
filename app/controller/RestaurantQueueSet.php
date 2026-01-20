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
// | 排队设置
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class RestaurantQueueSet extends Common
{
    public function initialize(){
		parent::initialize();
		//if(bid > 0) showmsg('无访问权限');
	}
    public function index(){
		$info = Db::name('restaurant_queue_sysset')->where('aid',aid)->where('bid',bid)->find();
		if(!$info){
			Db::name('restaurant_queue_sysset')->insert(['aid'=>aid,'bid'=>bid,'screen_pic'=>PRE_URL.'/static/img/restaurant_queue_bg.jpg']);
			$info = Db::name('restaurant_queue_sysset')->where('aid',aid)->where('bid',bid)->find();
		}
		View::assign('info',$info);
        return View::fetch();
    }
	public function save(){
		$info = input('post.info/a');
        Db::name('restaurant_queue_sysset')->where('aid',aid)->where('bid',bid)->update($info);
		return json(['status'=>1,'msg'=>'保存成功','url'=>(string)url('index')]);
	}
}
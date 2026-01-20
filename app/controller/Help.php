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
// | 帮助中心
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class Help extends Common
{
    public function index(){
    	$where = [];
		$where[] = ['status','=',1];
		$where1 = '1=1';
		$where2 = '1=1';
		$helplist = Db::name('help')->where($where)->whereRaw($where1)->whereRaw($where2)->order('sort desc,id')->select()->toArray();
		View::assign('helplist',$helplist);
		return View::fetch();
    }
	public function detail(){
		$id = input('param.id/d');
		$where = [];
		$where[] = ['id','=',$id];
		$where[] = ['status','=',1];
		$where1 = '1=1';
		$where2 = '1=1';
		$info = Db::name('help')->where($where)->whereRaw($where1)->whereRaw($where2)->find();
		View::assign('info',$info);
		return View::fetch();
	}
}
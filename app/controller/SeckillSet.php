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
// | 秒杀 系统设置
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class SeckillSet extends Common
{
    public function initialize(){
		parent::initialize();
		if(bid > 0) showmsg('无访问权限');
	}
    public function index(){
		$info = Db::name('seckill_sysset')->where('aid',aid)->find();
		if(!$info){
			Db::name('seckill_sysset')->insert(array(
				'aid'=>aid,
				'timeset'=>'0,8,12,20',
				'duration'=>'12',
			));
			$info = Db::name('seckill_sysset')->where('aid',aid)->find();
		}
		$timeset = explode(',',$info['timeset']);
		View::assign('info',$info);
		View::assign('timeset',$timeset);
		return View::fetch();
    }
	public function save(){
		$info = input('post.info/a');
		$timeset = array();
		$timesetArr = input('post.timeset/a');
		//if(!$timesetArr) $timesetArr = array();
		foreach($timesetArr as $k=>$v){
			$timeset[] = $k;
		}
		if(!$info['status']) $info['status'] = 0;
		$info['aid'] = aid;
		$info['timeset'] = implode(',',$timeset);

		Db::name('seckill_sysset')->where('aid',aid)->update($info);
		\app\common\System::plog('秒杀系统设置');
		return json(['status'=>1,'msg'=>'保存成功','url'=>(string)url('index')]);
	}
}
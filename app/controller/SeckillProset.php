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
// | 秒杀 商品设置
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class SeckillProset extends Common
{
    public function index(){
		$sysset = Db::name('seckill_sysset')->where('aid',aid)->find();
		$systimeset = explode(',',$sysset['timeset']);
		View::assign('sysset',$sysset);
		View::assign('systimeset',$systimeset);
		return View::fetch();
    }
	public function save(){
		$proid = input('post.proid/d');
		$timeset = input('post.timeset/a');
		$dateset = input('post.dateset/a');
		$buymax = input('post.buymax/d');
		if($proid==0){ return json(['status'=>0,'msg'=>'请选择商品']);}
		if(!$timeset){ return json(['status'=>0,'msg'=>'请选择时间']);}
		if(!$dateset){ return json(['status'=>0,'msg'=>'请选择日期']);}
		foreach($dateset as $k=>$date){
			if(!$date) unset($dateset[$k]);
		}
		if(!$dateset){ return json(['status'=>0,'msg'=>'请选择日期']);}
		$gglist = array();
		foreach(input('post.option/a') as $k=>$v){
			$gglist[] = ['aid'=>aid,'bid'=>bid,'proid'=>$proid,'ggid'=>$k,'seckill_price'=>$v['seckill_price'],'sell_price'=>$v['sell_price'],'seckill_num'=>$v['seckill_num'],'createtime'=>time(),'buymax'=>$buymax];
		}
		$datalist = array();
		foreach($dateset as $date){
			foreach($timeset as $time){
				$rs = Db::name('seckill_prodata')->where('aid',aid)->where('proid',$proid)->where('seckill_date',$date)->where('seckill_time',$time)->find();
				if($rs){
					return json(['status'=>0,'msg'=>"该商品在{$date} ".($time<10?'0':'')."{$time}:00已设置秒杀活动,请勿重复设置"]);
				}
				foreach($gglist as $gg){
					$datalist[] = array_merge(['seckill_date'=>$date,'seckill_time'=>$time,'starttime'=>strtotime($date)+$time*3600],$gg);
				}
			}
		}
		foreach($datalist as $data){
			Db::name('seckill_prodata')->insert($data);
		}
		\app\common\System::plog('添加秒杀活动');
		return json(['status'=>1,'msg'=>'保存成功','url'=>(string)url('SeckillList/index')]);
	}
}
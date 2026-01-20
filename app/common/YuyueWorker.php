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

namespace app\common;
use think\facade\Db;
class YuyueWorker
{
	//加余额
	public static function addmoney($aid,$bid,$uid,$money,$remark,$addtotal=1){
		if($money==0) return ;
		$user = Db::name('yuyue_worker')->where('aid',$aid)->where('id',$uid)->find();
		if(!$user) return ['status'=>0,'msg'=>'服务人员不存在'];

		if($money > 0 && $addtotal==1){
			$totalmoney = $user['totalmoney'] + $money;
		}else{
			$totalmoney = $user['totalmoney'];
		}
		$after = $user['money'] + $money;
		Db::name('yuyue_worker')->where('aid',$aid)->where('id',$uid)->update(['totalmoney'=>$totalmoney,'money'=>$after]);
		
		$data = [];
		$data['aid'] = $aid;
		$data['uid'] = $uid;
		$data['bid'] = $bid;
		$data['money'] = $money;
		$data['after'] = $after;
		$data['createtime'] = time();
		$data['remark'] = $remark;
		Db::name('yuyue_worker_moneylog')->insert($data);
		return ['status'=>1,'msg'=>''];
	}
}
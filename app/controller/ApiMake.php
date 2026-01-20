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
// | 码科跑腿 订单状态通知
// +----------------------------------------------------------------------
namespace app\controller;
use app\BaseController;
use think\facade\Db;
class ApiMake extends BaseController
{
    public function initialize(){

	}
	public function notify(){
		
		//\think\facade\Log::write('-----param-----');
		//\think\facade\Log::write(input('param.'));
		$post = input('param.');
		
		$aid = $post['aid'];
		$token = $post['token'];
		$set = Db::name('peisong_set')->where('aid',$aid['aid'])->find();

		if($token != md5($set['make_appid'].$set['make_token'])){
			die('error');
		}
		
		$order = Db::name('peisong_order')->where('aid',$aid)->where('make_ordernum',$post['order_no'])->find();
		
		$time = $post['time'];
		$rider_name = $post['rider_name'];
		$rider_mobile = $post['rider_mobile'];
		$update = [];
		$update['make_rider_name'] = $rider_name;
		$update['make_rider_mobile'] = $rider_mobile;
		if($post['status'] == 'accepted'){ //接单
			$update['status'] = 1;
			$update['starttime'] = $time;
		}elseif($post['status'] == 'wait_to_shop'){ //到店
			$update['status'] = 2;
			$update['daodiantime'] = $time;
		}elseif($post['status'] == 'geted'){ //取货
			$update['status'] = 3;
			$update['quhuotime'] = $time;
		}elseif($post['status'] == 'gotoed'){ //完成
			$update['status'] = 4;
			$update['endtime'] = $time;
		}
		Db::name('peisong_order')->where('aid',$aid)->where('make_ordernum',$post['order_no'])->update($update);

		die('success');
	}
	
}
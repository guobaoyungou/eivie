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
// | 抖音开放平台 消息推送通知
// +----------------------------------------------------------------------
namespace app\controller;
use app\BaseController;
use think\facade\Db;
class ApiDouyin extends BaseController
{
    public function initialize(){

	}
	public function notify(){
		
		//\think\facade\Log::write('-----param-----');
		//\think\facade\Log::write(input('param.'));
		$param = input('param.');

		$aid = $param['aid'];
		$postStr = file_get_contents('php://input');
		
		$header = getallheaders();
		//\think\facade\Log::write(getallheaders());
		//\think\facade\Log::write($postStr);

		$appinfo = Db::name('douyin_sysset')->where('aid',$aid)->find();
		if(!$appinfo) die();

		$sign = md5($appinfo['app_id'].$postStr.$appinfo['app_secret']);
		//\think\facade\Log::write($sign);
		//\think\facade\Log::write($header['Event-Sign']);


		if($sign != $header['Event-Sign']){
			\think\facade\Log::write('抖音消息推送: 校验失败');
			die('校验失败');
		}
		$postData = json_decode($postStr,true);
		foreach($postData as $k=>$v){
			//\think\facade\Log::write($v);
			if($v['tag'] == '400'){
				$data = json_decode($v['data'],true);
				//\think\facade\Log::write($data);
				$product_id = $data['product_id'];
				$product = Db::name('shop_product')->where('douyin_product_id',$product_id)->find();
				if(!$product) continue;
				$douyin_status = $product['douyin_status'];

				if($data['event'] == '11' || $data['event'] == '4'){
					$douyin_status = 1;
				}
				$product = Db::name('shop_product')->where('douyin_product_id',$product_id)->update([
					'douyin_check_status'=>$data['event'],
					'douin_check_reason'=>$data['check_reject_reason'],
					'douyin_status'=>$douyin_status,
				]);
			}
		}
		
		die('{"code":0,"msg":"success"}');
	}
	
}
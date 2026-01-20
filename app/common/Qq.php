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
use app\common\System;
class Qq
{
	//获取access_token
	public static function access_token($aid){
		$appinfo = System::appinfo($aid,'qq');
		$appid = $appinfo['appid'];
		$appsecret = $appinfo['appsecret'];
		if(!$appid) return '';
		if($appinfo['access_token'] && $appinfo['expires_time'] > time()){
			return $appinfo['access_token'];
		}else{
			if(!$appsecret) return '';
			$url = "https://api.q.qq.com/api/getToken?grant_type=client_credential&appid={$appid}&secret={$appsecret}";
			$res = json_decode(request_get($url));
			$access_token = $res->access_token;
			if($access_token) {
				Db::name('admin_setapp_qq')->where('appid',$appid)->update(['access_token'=>$access_token,'expires_time'=>time()+7000]);
				return $access_token;
			}else{
				//\think\facade\Log::write($res);
				//return '';
				echojson(['status'=>0,'msg'=>$res->errmsg]);
			}
		}
	}
}
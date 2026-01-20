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
// | 首页
// +----------------------------------------------------------------------
namespace app\controller;
use app\BaseController;
use think\facade\View;
use think\facade\Db;

class W extends BaseController
{
    public function a(){
		$code = input('param.x');
		$info = Db::name('wx_url')->where('code',$code)->find();
		if(!$info) showmsg('链接已失效');
		if($info['endtime'] < time()) showmsg('链接已失效');

		$pathurl = explode('?',$info['path']);

		$url = 'https://api.weixin.qq.com/wxa/generate_urllink?access_token='.\app\common\Wechat::access_token($info['aid']);
		$postdata = [];
		$postdata['path'] = $pathurl[0];
		if($pathurl[1]){
			$postdata['query'] = $pathurl[1];
		}
		if(input('param.pid')){
			$postdata['query'] = $postdata['query'] ? $postdata['query'].'&pid='.input('param.pid') : 'pid='.input('param.pid');
		}
		$postdata['expire_type'] = '1';
		$postdata['expire_interval'] = '30';
		$rs = curl_post($url,jsonEncode($postdata));
		$rs = json_decode($rs,true);
		if($rs['errcode']!=0){
			 showmsg($rs['errmsg']);
			//return json(['status'=>0,'msg'=>$rs['errcode'].'：'.$rs['errmsg']]);
		}
		//die(curl_get($rs['url_link']));
		header('Location:'.$rs['url_link']);
    }
}

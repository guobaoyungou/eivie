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
// | 订阅消息设置
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class Wxtmpl extends Common
{	
    public function initialize(){
		parent::initialize();
		if(bid > 0) showmsg('无访问权限');
	}
	//模板消息设置
	public function tmplset(){
		if(request()->isPost()){
			$rs = Db::name('wx_tmplset')->where('aid',aid)->find();
			$info = input('post.info/a');
			if($rs){
				Db::name('wx_tmplset')->where('aid',aid)->update($info);
				\app\common\System::plog('设置小程序订阅消息');
			}else{
				$info['aid'] = aid;
				Db::name('wx_tmplset')->insert($info);
				\app\common\System::plog('设置小程序订阅消息');
			}
			return json(['status'=>1,'msg'=>'设置成功','url'=>(string)url()]);
		}
		$info = Db::name('wx_tmplset')->where('aid',aid)->find();
		if(!$info){
			Db::name('wx_tmplset')->insert(['aid'=>aid]);
			$info = Db::name('wx_tmplset')->where('aid',aid)->find();
		}
		View::assign('info',$info);
		return View::fetch();
	}
	//添加 获取模板ID
	public function gettmplid(){
		$template_no = input('post.template_no');

		$keywordArr = explode(',',input('post.keywords'));
		//dump($keywordArr);
		$kidList = [];
		foreach($keywordArr as $k=>$v){
			$kidList[] = intval($v);
		}
		$access_token = \app\common\Wechat::access_token(aid,'wx');
		$data = array();
		$data['tid'] = $template_no;
		$data['kidList'] = $kidList;
		$data['sceneDesc'] = '1';
        //文档 https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/mp-message-management/subscribe-message/addMessageTemplate.html
		$res = curl_form_post('https://api.weixin.qq.com/wxaapi/newtmpl/addtemplate?access_token='.$access_token,$data);	
		//dump($data);
		//dump(jsonEncode($data));
		//dump($res);
		$res = json_decode($res,true);
		if($res['errcode']!=0){
			if($res['errcode'] == 45026 || $res['errcode'] == 200012){
				return json(['status'=>0,'msg'=>'模板数量超过微信最大限制，请先删除一些再添加。操作路径：微信公众号后台-[基础功能]-[订阅消息]']);
			}
			return json(['status'=>0,'msg'=>$res['errcode'].'：'.$res['errmsg']]);
		}else{
			return json(['status'=>1,'data'=>$res['priTmplId'],'msg'=>'添加成功']);
		}
	}
}
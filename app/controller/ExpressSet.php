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
// | 配送设置
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class ExpressSet extends Common
{
	public function initialize(){
		parent::initialize();
		if(bid > 0) showmsg('无访问权限');
	}
	public function index(){
		$info = Db::name('express_sysset')->where('aid',aid)->find();
		if(!$info){
			$info = Db::name('express_sysset')->where('aid',aid)->find();
		}
		View::assign('info',$info);
		return View::fetch();
    }
	public function save(){
		$info = input('post.info/a');
		$pstimeday = input('post.pstimeday/a');
		$pstimehour = input('post.pstimehour/a');
		$pstimeminute = input('post.pstimeminute/a');
		$pstimehour2 = input('post.pstimehour2/a');
		$pstimeminute2 = input('post.pstimeminute2/a');
		$pstimedata = [];
		foreach($pstimeday as $k=>$v){
			$pstimedata[] = ['day'=>$v,'hour'=>$pstimehour[$k],'minute'=>$pstimeminute[$k],'hour2'=>$pstimehour2[$k],'minute2'=>$pstimeminute2[$k]];
		}
		$data['pstimedata'] = json_encode($pstimedata);
		$data['secret_key'] = $info['secret_key'];
		$data['secret_secret'] = $info['secret_secret'];
		$data['secret_code'] = $info['secret_code'];
		$data['key'] = $info['key'];
		$data['customer'] = $info['customer'];
		$data['psprehour'] = $info['psprehour'];
		$data['secret'] = $info['secret'];
		$data['secret_codep'] = $info['secret_codep'];
		if($info['id']){
			Db::name('express_sysset')->where('aid',aid)->where('bid',bid)->where('id',$info['id'])->update($data);
			\app\common\System::plog('查寄快递设置'.$info['id']);
		}else{
			$data['aid'] = aid;
			$data['bid'] = bid;
			$id = Db::name('express_sysset')->insertGetId($data);
			\app\common\System::plog('查寄快递设置'.$id);
		}
		return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
	}

}
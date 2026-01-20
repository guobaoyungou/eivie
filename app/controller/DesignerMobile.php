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
// | 移动端后台页面配置
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class DesignerMobile extends Common
{
	public function initialize(){
		parent::initialize();
		if(bid > 0) showmsg('无访问权限');
	}
    public function index(){

		$info = Db::name('designer_mobile')->where('aid',aid)->find();
		if(!$info){
			$info = array(
				'aid'=>aid,
				'updatetime'=>time(),
				'data'=>jsonEncode([
					"bgimg"     => PRE_URL.'/static/img/admin/headbgimg.png',
				])
			);
			$id = Db::name('designer_mobile')->insertGetId($info);
			$info['id'] = $id;
		}

		$data = json_decode($info['data'],true);
		View::assign('data',$data);
		View::assign('info',$info);

		return View::fetch();
    }
	public function save(){

		$info = input('post.info/a');
		$id = $info['id']?$info['id']:0;
		$info['data'] = jsonEncode($info['data']);
		$info['updatetime'] = time();

		unset($info['id']);
		if($id){
			Db::name('designer_mobile')->where('id',$id)->update($info);
		}else{
			Db::name('designer_mobile')->insert($info);
		}

		\app\common\System::plog('移动端后台设置');
		return json(['status'=>1,'msg'=>'保存成功','url'=>(string)url('index')]);
	}

}
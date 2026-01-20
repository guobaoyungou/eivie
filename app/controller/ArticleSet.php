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
// | 文章系统设置
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class ArticleSet extends Common
{
    public function initialize(){
		parent::initialize();
	}
    public function set(){
		$info = Db::name('article_set')->where('aid',aid)->where('bid',bid)->find();
		if(!$info){
			Db::name('article_set')->insert(['aid'=>aid,'bid'=>bid]);
			$info = Db::name('article_set')->where('aid',aid)->where('bid',bid)->find();
		}
		View::assign('info',$info);
		return View::fetch();
    }
	public function save(){
		$info = input('post.info/a');
		Db::name('article_set')->where('aid',aid)->where('bid',bid)->update($info);
		\app\common\System::plog('文章管理系统设置');
		return json(['status'=>1,'msg'=>'保存成功','url'=>true]);
	}
}

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
// | 控制台
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class WebCommon extends Common
{
	public $uid;
	public $user;
	public $menudata = [];
    public function initialize(){
		parent::initialize();
		$this->uid = session('BST_ID');
		$this->user = Db::name('admin_user')->where(['id'=>$this->uid])->find();
		if(!session('BST_ID') || !$this->user || $this->user['isadmin'] != 2){
			showmsg('无访问权限');
		}

		$menudata = \app\common\Menu::getdata2($this->uid);
		$this->menudata = $menudata;

		$childmenu = [];
		$menuname = '';
		$thispath = request()->controller() .'/'.request()->action();
        View::assign('thispath',$thispath);
		if(!request()->isAjax()){
			foreach($this->menudata as $v){
				if(!$v['child']) continue;
				foreach($v['child'] as $v2){
					if($v2['path'] == $thispath){
						$menuname = $v2['name'];
						foreach($v['child'] as $v_2){
							if(!$v_2['hide']) $childmenu[] = $v_2;
						}
						break;
					}
				}
			}
		}
		View::assign('childmenu',$childmenu);
	}
}
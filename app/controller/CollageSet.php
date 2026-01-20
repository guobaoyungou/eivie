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
// | 拼团商城 系统设置
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class CollageSet extends Common
{
	public function initialize(){
		parent::initialize();
		if(!getcustom('yx_collage_refund_to_continued')){
            if(bid > 0) showmsg('无操作权限');
        }
	}
    public function index(){
	    $where = [];
        $info = Db::name('collage_sysset')->where('aid',aid)->where($where)->find();
		if(!$info){
			$info = Db::name('collage_sysset')->where('aid',aid)->where($where)->find();
		}
		View::assign('info',$info);
		return View::fetch();
    }
	public function save(){
		$info = input('post.info/a');
        $where = [];
        Db::name('collage_sysset')->where('aid',aid)->where($where)->update($info);	
		\app\common\System::plog('拼团系统设置');
		return json(['status'=>1,'msg'=>'保存成功','url'=>(string)url('index')]);
	}
}
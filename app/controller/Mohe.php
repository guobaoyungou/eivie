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
// | 魔盒前端
// +----------------------------------------------------------------------
namespace app\controller;
use app\BaseController;
use think\facade\Cookie;
use think\facade\Session;
use think\facade\View;
use think\facade\Db;

class Mohe extends BaseController
{
	//首页框架
    public function index(){
        $aid = input('param.aid');
        $id = input('param.lid');
        $time = input('param.diandat');
        if(empty($time) || $time+3600 > time()) {
            $info = \db('mohe_link')->where('aid',$aid)->where('id',$id)->where('status',1)->find();
            // dd($info);
            header('Location:'.(string) $info['url']);die;
        }
       
        return View::fetch('mohe/index');
    }

}

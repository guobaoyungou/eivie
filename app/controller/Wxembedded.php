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
// | 小程序 打开半屏小程序
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;
use app\common\Wechat;

class Wxembedded extends Common
{	
    public function initialize(){
		parent::initialize();
		if(bid > 0) showmsg('无访问权限');
        $appinfo = \app\common\System::appinfo(aid,'wx');
        if($appinfo['authtype'] ==0){
            showmsg('无权限，手动接入的请到[微信公众平台-设置-第三方设置-半屏小程序管理]中进行申请');
        }
	}
	//列表
    public function index(){
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
            $count = input('param.count',0);
            //第一页 为了分页 查询所有的数量，其他页按照正常的条数查询
            $start = ($page -1) *$limit;
            $limit = $start==0?0:$limit;
            $list = Wechat::getEmbeddedList(aid,$start,$limit);
		    if($list && $start ==0){
		        $count = count($list);
                $list = array_slice($list,0,10);
            }
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$list]);
		}
		
		return View::fetch();
    }
	//编辑
	public function edit(){
		return View::fetch();
	}
	//保存
	public function save(){
		$info = input('post.info/a');
		if(!$info['appid']){
		    return json(['status'=> 0,'msg' => '请输入目标小程序appid']);
        }
	    $data = Wechat::addEmbedded(aid,$info['appid'],$info['apply_reason']);
		if($data['status'] ==0){
            return json(['status'=>0,'msg'=>$data['msg']]);
        }
        \app\common\System::plog('添加半屏小程序申请，AppId'.$info['appid']);
		return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
	}

}
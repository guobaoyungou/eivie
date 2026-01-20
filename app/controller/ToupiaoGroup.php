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
// | 投票分组
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class ToupiaoGroup extends Common
{
	//分组列表
    public function index(){
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
			if(input('param.field') && input('param.order')){
				$order = input('param.field').' '.input('param.order');
			}else{
				$order = 'sort desc,id desc';
			}
			$where = array();
			$where[] = ['aid','=',aid];
			if(input('param.name')) $where[] = ['name','like','%'.input('param.name').'%'];
			$count = 0 + Db::name('toupiao_group')->where($where)->count();
			$data = Db::name('toupiao_group')->where($where)->page($page,$limit)->order($order)->select()->toArray();
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
		return View::fetch();
    }
    public function getdetail(){
        $tmpinfo = ['id'=>0,'name'=>'','sort'=>0];
        if(empty(input('param.id/d'))){
            return json(['status'=>0,'data'=>$tmpinfo]);
        }
        $info = Db::name('toupiao_group')->where('aid',aid)->where('id',input('param.id/d'))->find();
        if(empty($info)) $info = $tmpinfo;
        return json(['status'=>0,'data'=>$info]);
    }
	//编辑
	public function edit(){
		if(input('param.id')){
			$info = Db::name('toupiao_group')->where('aid',aid)->where('id',input('param.id/d'))->find();
		}else{
			$info = array('id'=>'');
		}
		$userlist = Db::name('admin_user')->field('id,aid,bid,un')->where('aid',aid)->where('bid',bid)->order('id')->select()->toArray();
        View::assign('userlist',$userlist);
        View::assign('info',$info);
		return View::fetch();
	}
	//保存
	public function save(){
		$info = input('post.info/a');
        $exist = Db::name('toupiao_group')->where('aid',aid)->where('bid',bid)->where('name',$info['name'])->where('id','<>',$info['id'])->find();
        if($exist){
            return json(['status'=>0,'msg'=>'该名称已经存在']);
        }
		if($info['id']){
			Db::name('toupiao_group')->where('aid',aid)->where('id',$info['id'])->update($info);
			\app\common\System::plog('编辑投票活动分组'.$info['id']);
		}else{
			$info['aid'] = aid;
			$info['createtime'] = time();
			$id = Db::name('toupiao_group')->insertGetId($info);
			\app\common\System::plog('添加投票活动分组'.$id);
		}
		return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
	}
	//删除
	public function del(){
		$ids = input('post.ids/a');
		Db::name('toupiao_group')->where('aid',aid)->where('id','in',$ids)->delete();
		\app\common\System::plog('投票活动分组删除'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}
}
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
// | 用户论坛 帖子列表
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class Luntan extends Common
{
	public function initialize(){
		parent::initialize();
		if(bid > 0) showmsg('无操作权限');
	}
	//列表
	public function index(){
	    $this->defaultSet();
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
			if(input('param.field') && input('param.order')){
				$order = input('param.field').' '.input('param.order');
			}else{
				$order = 'is_top desc,id desc';
			}
			$where = array();
			$where[] = ['aid','=',aid];
			if(input('param.content')) $where[] = ['content','like','%'.input('param.content').'%'];
			if(input('?param.status') && input('param.status')!=='') $where[] = ['status','=',input('param.status')];

			if(input('param.cid')){
				if(false){}else{
					$where[] = ['cid','=',input('param.cid')];
				}
			} 

			if(input('param.ctime') ){
				$ctime = explode(' ~ ',input('param.ctime'));
				$where[] = ['createtime','>=',strtotime($ctime[0])];
				$where[] = ['createtime','<',strtotime($ctime[1]) + 86400];
			}
			$count = 0 + Db::name('luntan')->where($where)->count();
			$data = Db::name('luntan')->where($where)->page($page,$limit)->order($order)->select()->toArray();
			
			$clist = Db::name('luntan_category')->where('aid',aid)->select()->toArray();
			$cdata = array();
			foreach($clist as $c){
				$cdata[$c['id']] = $c['name'];
			}
			$admininfo = Db::name('admin_set')->where('aid',aid)->field('name,logo')->find();
			foreach($data as $k=>$v){
				$data[$k]['cname'] = $cdata[$v['cid']];
				$data[$k]['plcount'] = Db::name('luntan_pinglun')->where('sid',$v['id'])->count();
				if(!$v['mid']){
					$data[$k]['nickname'] = $admininfo['name'];
					$data[$k]['headimg'] = $admininfo['logo'];
				}
			}
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
		//分类
		$clist = Db::name('luntan_category')->Field('id,name')->where('aid',aid)->where('status',1)->where('pid',0)->order('sort desc,id')->select()->toArray(); 
		foreach($clist as $k=>$v){
			$clist[$k]['child'] = Db::name('luntan_category')->Field('id,name')->where('aid',aid)->where('status',1)->where('pid',$v['id'])->order('sort desc,id')->select()->toArray(); 
		}
		View::assign('clist',$clist);
		return View::fetch();
	}
	//编辑
	public function edit(){
		if(input('param.id')){
			$info = Db::name('luntan')->where('aid',aid)->where('id',input('param.id/d'))->find();
		}else{
			$info = array('id'=>'');
		}
		//分类
		$clist = Db::name('luntan_category')->Field('id,name')->where('aid',aid)->where('status',1)->where('pid',0)->order('sort desc,id')->select()->toArray(); 
		foreach($clist as $k=>$v){
			$clist[$k]['child'] = Db::name('luntan_category')->Field('id,name')->where('aid',aid)->where('status',1)->where('pid',$v['id'])->order('sort desc,id')->select()->toArray(); 
		}
		View::assign('clist',$clist);
		View::assign('info',$info);
		return View::fetch();
	}
	//保存
	public function save(){
		$info = input('post.info/a');
        $isAudit = 0;
        $mid = 0;
		if($info['id']){
			$oldinfo = Db::name('luntan')->where('aid',aid)->where('id',$info['id'])->find();
			if($oldinfo['mid'] != $info['mid']){
				$member = Db::name('member')->where('id',$info['mid'])->find();
				$info['nickname'] = $member['nickname'];
				$info['headimg'] = $member['headimg'];
			}
			if($oldinfo['status']==0){
			    $isAudit = 1;
			    $mid = $oldinfo['mid'];
            }
			if(input('post.oldreadcount') == $info['readcount']) unset($info['readcount']);
			if(input('post.oldzan') == $info['zan']) unset($info['zan']);
		}
		if($info['id']){
			Db::name('luntan')->where('aid',aid)->where('id',$info['id'])->update($info);
			}else{
			$member = Db::name('member')->where('id',$info['mid'])->find();
			$info['nickname'] = $member['nickname'];
			$info['headimg'] = $member['headimg'];
			$info['aid'] = aid;
			$info['createtime'] = time();
			$id = Db::name('luntan')->insertGetId($info);
		}
		return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
	}
	//删除
	public function del(){
		$ids = input('post.ids/a');
		Db::name('luntan')->where('aid',aid)->where('id','in',$ids)->delete();
		Db::name('luntan_pinglun')->where('aid',aid)->where('sid','in',$ids)->delete();
		Db::name('luntan_pinglun_reply')->where('aid',aid)->where('sid','in',$ids)->delete();

		// 同步删除表单
		\app\common\System::plog('删除论坛帖子'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}
	//设置状态
	public function setst(){
		$ids = input('post.ids/a');
		$st = input('post.st/d');
        $list = Db::name('luntan')->where('aid',aid)->where('id','in',$ids)->select()->toArray();
		Db::name('luntan')->where('aid',aid)->where('id','in',$ids)->update(['status'=>$st]);
		\app\common\System::plog('论坛帖子改状态'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'操作成功']);
	}
    //设置置顶状态
    public function setTop(){
        $ids = input('post.ids/a');
        $updata = [];
        $updata['is_top'] = input('post.st/d');
        Db::name('luntan')->where('aid',aid)->where('id','in',$ids)->update($updata);
        \app\common\System::plog('论坛帖子改置顶状态'.implode(',',$ids));
        return json(['status'=>1,'msg'=>'操作成功']);
    }
	
	//系统设置
	public function sysset(){
		if(request()->isPost()){
			$rs = Db::name('luntan_sysset')->where('aid',aid)->find();
			$info = input('post.info/a');
			$info['sendtj'] = implode(',',$info['sendtj']);
			if($rs){
				Db::name('luntan_sysset')->where('aid',aid)->update($info);
				\app\common\System::plog('用户论坛系统设置');
			}else{
				$info['aid'] = aid;
				Db::name('luntan_sysset')->insert($info);
			}
			return json(['status'=>1,'msg'=>'设置成功','url'=>true]);
		}
		$info = Db::name('luntan_sysset')->where('aid',aid)->find();
		if(!$info){
			Db::name('luntan_sysset')->insert(['aid'=>aid]);
			$info = Db::name('luntan_sysset')->where('aid',aid)->find();
		}
		$info['sendtj'] = explode(',',$info['sendtj']);
		$default_cid = Db::name('member_level_category')->where('aid',aid)->where('isdefault', 1)->value('id');
        $default_cid = $default_cid ? $default_cid : 0;
        $memberlevel = Db::name('member_level')->where('aid',aid)->where('cid', $default_cid)->order('sort,id')->select()->toArray();
		View::assign('memberlevel',$memberlevel);
		View::assign('info',$info);
		return View::fetch();
	}
    function defaultSet(){
        $set = Db::name('luntan_sysset')->where('aid',aid)->find();
        if(!$set){
            Db::name('luntan_sysset')->insert(['aid'=>aid]);
        }
    }
 
}
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
// | 墨盒点餐链接转换
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class MoheLink extends Common
{
	//列表
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
			if(bid==0){
				if(input('param.bid')){
					$where[] = ['bid','=',input('param.bid')];
				}elseif(input('param.showtype')==2){
					$where[] = ['bid','>',0];
				}else{
					$where[] = ['bid','=',0];
				}
			}else{
				$where[] = ['bid','=',bid];
			}

			if(input('param.pid')) $where[] = ['pid','=',input('param.pid/d')];
			if(input('param.name')) $where[] = ['name','like','%'.input('param.name').'%'];
			if(input('?param.status') && input('param.status')!=='') $where[] = ['status','=',input('param.status')];
			if(input('param.cid')) $where[] = ['cid','=',input('param.cid')];
			if(input('param.ctime') ){
				$ctime = explode(' ~ ',input('param.ctime'));
				$where[] = ['createtime','>=',strtotime($ctime[0])];
				$where[] = ['createtime','<',strtotime($ctime[1]) + 86400];
			}
			$count = 0 + Db::name('mohe_link')->where($where)->count();
			$data = Db::name('mohe_link')->where($where)->page($page,$limit)->order($order)->select()->toArray();

			foreach($data as $k=>$v){
				if($v['bid'] > 0){
					$data[$k]['bname'] = Db::name('business')->where('aid',aid)->where('id',$v['bid'])->value('name');
				}else{
					$data[$k]['bname'] = '平台';
				}
				$data[$k]['link'] = PRE_URL.(string)url('Mohe/index').'&aid='.aid.'&lid='.$v['id'];
			}
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
		return View::fetch();
	}
	//编辑文章
	public function edit(){
		if(input('param.id')){
			if(bid == 0){
				$info = Db::name('mohe_link')->where('aid',aid)->where('id',input('param.id/d'))->find();
			}else{
				$info = Db::name('mohe_link')->where('aid',aid)->where('bid',bid)->where('id',input('param.id/d'))->find();
			}
		}else{
			$info = ['id'=>'','canpl'=>1,'canplrp'=>1,'showname'=>1,'showreadcount'=>1,'showsendtime'=>1,'showauthor'=>1,'readcount'=>0,'pinglun_check'=>0];
			$set = Db::name('admin_set')->where('aid',aid)->find();
			$info['bid'] = bid;
		}
// 		if($info['bid'] != 0){
// 			$needcheck = Db::name('business_sysset')->where('aid',aid)->value('article_check');
// 		}else{
			$needcheck = 0;
// 		}
		View::assign('info',$info);
		View::assign('needcheck',$needcheck);
		return View::fetch();
	}
	//保存
	public function save(){
		$info = input('post.info/a');
		$info['content'] = \app\common\Common::geteditorcontent($info['content']);
		if($info['id']){
		
			Db::name('mohe_link')->where('aid',aid)->where('id',$info['id'])->update($info);
		}else{
			$info['aid'] = aid;
			$info['bid'] = bid;
			$info['createtime'] = time();
			
			Db::name('mohe_link')->insert($info);
		}
		return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
	}
	//删除
	public function del(){
		$ids = input('post.ids/a');
		if(bid == 0){
			Db::name('mohe_link')->where('aid',aid)->where('id','in',$ids)->delete();
		}else{
			Db::name('mohe_link')->where('aid',aid)->where('bid',bid)->where('id','in',$ids)->delete();
		}
		return json(['status'=>1,'msg'=>'删除成功']);
	}
	//设置状态
	public function setst(){
		$aid = $this->aid;
		$ids = input('post.ids/a');
		if(bid == 0){
			Db::name('mohe_link')->where('aid',aid)->where('id','in',$ids)->update(['status'=>input('post.st/d')]);
		}else{
			Db::name('mohe_link')->where('aid',aid)->where('bid',bid)->where('id','in',$ids)->update(['status'=>input('post.st/d')]);
		}
		return json(['status'=>1,'msg'=>'操作']);
	}
	//审核
	public function setcheckst(){
		if(bid!=0) return json(['status'=>0,'msg'=>'无权限操作']);
		$st = input('post.st/d');
		$id = input('post.id/d');
		$reason = input('post.reason');
		Db::name('mohe_link')->where('aid',aid)->where('id',$id)->update(['status'=>$st,'reason'=>$reason]);
		return json(['status'=>1,'msg'=>'操作成功']);
	}
	public function choosearticle(){
		if(request()->isPost()){
			$data = Db::name('article')->where('aid',aid)->where('bid',bid)->where('id',input('post.id/d'))->find();
			return json(['status'=>1,'msg'=>'查询成功','data'=>$data]);
		}
		//分类
		$clist = Db::name('article_category')->field('id,name')->where('aid',aid)->where('bid',bid)->where('status',1)->where('pid',0)->order('sort desc,id')->select()->toArray(); 
		foreach($clist as $k=>$v){
			$clist[$k]['child'] = Db::name('article_category')->field('id,name')->where('aid',aid)->where('bid',bid)->where('status',1)->where('pid',$v['id'])->order('sort desc,id')->select()->toArray(); 
		}
		View::assign('clist',$clist);
		return View::fetch();
	}
}
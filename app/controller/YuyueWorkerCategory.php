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
// | 服务人员分类
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class YuyueWorkerCategory extends Common
{
    public function initialize(){
		parent::initialize();
	}
	//列表
    public function index(){
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
			if(input('param.field') && input('param.order')){
				$order = input('param.field').' '.input('param.order');
			}else{
				$order = 'sort desc,id';
			}
			$where = array();
			$where[] = ['aid','=',aid];
			$where[] = ['bid','=',bid];
			$where[] = ['pid','=',0];
			if(input('param.realname')) $where[] = ['realname','like','%'.input('param.realname').'%'];
			if(input('?param.status') && input('param.status')!=='') $where[] = ['status','=',input('param.status')];
			$count = 0 + Db::name('yuyue_worker_category')->where($where)->count();
			$data = [];
			$cate0 = Db::name('yuyue_worker_category')->where($where)->order($order)->select()->toArray();
			foreach($cate0 as $c0){
				$c0['deep'] = 0;
				$data[] = $c0;
				$cate1 = Db::name('yuyue_worker_category')->where('aid',aid)->where('pid',$c0['id'])->order($order)->select()->toArray();
				foreach($cate1 as $k1=>$c1){
					if($k1 < count($cate1)-1){
						$c1['name'] = '<span style="color:#aaa">&nbsp;&nbsp;&nbsp;&nbsp;├ </span>'.$c1['name'];
					}else{
						$c1['name'] = '<span style="color:#aaa">&nbsp;&nbsp;&nbsp;&nbsp;└ </span>'.$c1['name'];
					}
					$c1['deep'] = 1;
					$data[] = $c1;
				}
			
			}
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
        $this->defaultSet();
		return View::fetch();
    }
	//编辑
	public function edit(){
		if(input('param.id')){
			$info = Db::name('yuyue_worker_category')->where('aid',aid)->where('bid',bid)->where('id',input('param.id/d'))->find();
		}else{
			$info = array('id'=>'');
		}
		$set = Db::name('yuyue_worker_category')->where('aid',aid)->where('bid',bid)->where('id',input('param.id/d'))->find();
		View::assign('info',$info);
		return View::fetch();
	}
	public function save(){
		$set = db('yuyue_set')->field('diyname')->where('aid',aid)->find();
		$info = input('post.info/a');
		if($info['id']){
			Db::name('yuyue_worker_category')->where('aid',aid)->where('bid',bid)->where('id',$info['id'])->update($info);
			\app\common\System::plog('编辑'.$set['diyname']?$set['diyname']:'人员'.$info['id']);
		}else{
			$info['aid'] = aid;
			$info['bid'] = bid;
			$info['createtime'] = time();
			$id = Db::name('yuyue_worker_category')->insertGetId($info);
			\app\common\System::plog('添加'.$set['diyname']?$set['diyname']:'人员'.$id);
		}
		return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
	}
	//改状态
	public function setst(){
		$st = input('post.st/d');
		$ids = input('post.ids/a');
		Db::name('yuyue_worker_category')->where('aid',aid)->where('bid',bid)->where('id','in',$ids)->update(['status'=>$st]);
		\app\common\System::plog($set['diyname']?$set['diyname']:'人员'.'改状态'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'操作成功']);
	}
	//删除
	public function del(){
		$ids = input('post.ids/a');
		Db::name('yuyue_worker_category')->where('aid',aid)->where('bid',bid)->where('id','in',$ids)->delete();
		\app\common\System::plog($set['diyname']?$set['diyname']:'人员'.'删除'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}
    function defaultSet(){
        $set = Db::name('yuyue_set')->where('aid',aid)->where('bid',bid)->find();
        if(!$set){
            Db::name('yuyue_set')->insert(['aid'=>aid,'bid' => bid]);
        }
    }
}

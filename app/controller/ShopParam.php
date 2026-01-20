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
// | 商城 商品参数
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class ShopParam extends Common
{
    public function initialize(){
		parent::initialize();
	}
	//参数列表
    public function index(){
        //分类
        $clist = Db::name('shop_category')->Field('id,name')->where('aid',aid)->where('pid',0)->order('sort desc,id')->select()->toArray();
        foreach($clist as $k=>$v){
            $child = Db::name('shop_category')->Field('id,name')->where('aid',aid)->where('pid',$v['id'])->order('sort desc,id')->select()->toArray();
            foreach($child as $k2=>$v2){
                $child2 = Db::name('shop_category')->Field('id,name')->where('aid',aid)->where('pid',$v2['id'])->order('sort desc,id')->select()->toArray();
                $child[$k2]['child'] = $child2;
            }
            $clist[$k]['child'] = $child;
        }
        $formatClist = Db::name('shop_category')->where('aid',aid)->column('name','id');

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
			if(input('param.name')) $where[] = ['name','like','%'.input('param.name').'%'];
			if(input('?param.status') && input('param.status')!=='') $where[] = ['status','=',input('param.status')];
            if(input('param.cid')) $where[] = Db::raw("find_in_set(".input('param.cid').",cid)");
			//dump($where);
			$count = 0 + Db::name('shop_param')->where($where)->count();
			$data = Db::name('shop_param')->where($where)->page($page,$limit)->order($order)->select()->toArray();
            foreach ($data as $k => $v){
                $cids = explode(',',$v['cid']);
                foreach($cids as $cid){
                    $data[$k]['catname'] .= $formatClist[$cid].' ';
                }
            }
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
        View::assign('clist',$clist);
		return View::fetch();
    }
	//编辑
	public function edit(){
		if(input('param.id')){
			$info = Db::name('shop_param')->where('aid',aid)->where('id',input('param.id/d'))->find();
			$info['params'] = json_decode($info['params'],true);
		}else{
			$info = array('id'=>'');
			$info['params'] = [];
		}
//		$pcatelist = Db::name('shop_param')->where('aid',aid)->order('sort desc,id')->select()->toArray();
        //分类
        $clist = Db::name('shop_category')->Field('id,name')->where('aid',aid)->where('pid',0)->order('sort desc,id')->select()->toArray();
        foreach($clist as $k=>$v){
            $child = Db::name('shop_category')->Field('id,name')->where('aid',aid)->where('pid',$v['id'])->order('sort desc,id')->select()->toArray();
            foreach($child as $k2=>$v2){
                $child2 = Db::name('shop_category')->Field('id,name')->where('aid',aid)->where('pid',$v2['id'])->order('sort desc,id')->select()->toArray();
                $child[$k2]['child'] = $child2;
            }
            $clist[$k]['child'] = $child;
        }

        $info['cid'] = explode(',',$info['cid']);
		View::assign('info',$info);
        View::assign('clist',$clist);
		return View::fetch();
	}
	//保存
	public function save(){
		$info = input('post.info/a');

		$info['params'] = json_encode(input('post.params/a'),JSON_UNESCAPED_UNICODE);
		if($info['id']){
			Db::name('shop_param')->where('aid',aid)->where('id',$info['id'])->update($info);
			\app\common\System::plog('编辑商品参数'.$info['id']);
		}else{
			$info['aid'] = aid;
			$info['bid'] = bid;
			$info['createtime'] = time();
			$id = Db::name('shop_param')->insertGetId($info);
			\app\common\System::plog('添加商品参数'.$id);
		}
		return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
	}
	//删除
	public function del(){
		$ids = input('post.ids/a');
		Db::name('shop_param')->where('aid',aid)->where('id','in',$ids)->delete();
		\app\common\System::plog('商品参数删除'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}
}
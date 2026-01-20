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
// | 论坛 分类管理
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class LuntanCategory extends Common
{
	public function initialize(){
		parent::initialize();
		if(bid > 0) showmsg('无操作权限');
		$this->defaultSet();
	}
	//分类列表
    public function index(){

    	$luntan_form_status = false;
 		if(request()->isAjax()){
			if(input('param.field') && input('param.order')){
				$order = input('param.field').' '.input('param.order');
			}else{
				$order = 'sort desc,id';
			}
			$page = input('param.page');
			$limit = input('param.limit');
			$data = [];
			$count = 0 + Db::name('luntan_category')->where('aid',aid)->where('pid',0)->count();
			$cate0 = Db::name('luntan_category')->where('aid',aid)->where('pid',0)->page($page,$limit)->order($order)->select()->toArray();
			foreach($cate0 as $c0){
				$data[] = $c0;
				$cate1 = Db::name('luntan_category')->where('aid',aid)->where('pid',$c0['id'])->order($order)->select()->toArray();
				foreach($cate1 as $k1=>$c1){
					if($k1 < count($cate1)-1){
						$c1['name'] = '<span style="color:#aaa">&nbsp;&nbsp;&nbsp;&nbsp;├ </span>'.$c1['name'];
					}else{
						$c1['name'] = '<span style="color:#aaa">&nbsp;&nbsp;&nbsp;&nbsp;└ </span>'.$c1['name'];
					}
					$data[] = $c1;
				}
			}
			if($luntan_form_status){
				foreach ($data as $key => $value) {
					if($value['formid']){
						$data[$key]['formname'] = Db::name('form')->where('aid',aid)->where('id',$value['formid'])->value('name');
					}
				}
			}

			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}

 		View::assign('luntan_form_status',$luntan_form_status);
		return View::fetch();
    }
	//编辑
	public function edit(){

		$luntan_form_status = false;
		if(input('param.id')){
			$info = Db::name('luntan_category')->where('aid',aid)->where('id',input('param.id/d'))->find();
			if($luntan_form_status){
				$info['formname'] = Db::name('form')->where('aid',aid)->where('id',$info['formid'])->value('name');
			}
		}else{
			$info = array('id'=>'');

		}
		if(input('param.pid')) $info['pid'] = input('param.pid');
		$pcatelist = Db::name('luntan_category')->where('aid',aid)->where('pid',0)->where('id','<>',$info['id'])->order('sort desc,id')->select()->toArray();
        View::assign('info',$info);
		View::assign('pcatelist',$pcatelist);

		View::assign('luntan_form_status',$luntan_form_status);
		return View::fetch();
	}
	//保存
	public function save(){
		$info = input('post.info/a');
		if($info['id']){
			Db::name('luntan_category')->where('aid',aid)->where('id',$info['id'])->update($info);
			\app\common\System::plog('编辑用户论坛分类'.$info['id']);
		}else{
			$info['aid'] = aid;
			$info['createtime'] = time();
			$id = Db::name('luntan_category')->insertGetId($info);
			\app\common\System::plog('添加用户论坛分类'.$id);
		}
		return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
	}
	//删除
	public function del(){
		$ids = input('post.ids/a');
		Db::name('luntan_category')->where('aid',aid)->where('id','in',$ids)->delete();
		\app\common\System::plog('删除用户论坛分类'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}
    function defaultSet(){
        $set = Db::name('luntan_sysset')->where('aid',aid)->find();
        if(!$set){
            Db::name('luntan_sysset')->insert(['aid'=>aid]);
        }
    }
}
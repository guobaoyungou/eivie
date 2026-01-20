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

//管理员中心 - 菜品管理
namespace app\controller;
use think\facade\Db;
class ApiAdminRestaurantCategory extends ApiAdmin
{	
	public function index(){
		$where = ['aid'=>aid,'bid'=>bid];

		$pernum = 20;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$datalist = Db::name('restaurant_product_category')->where($where)->page($pagenum,$pernum)->order('sort desc,id desc')->select();
		if(!$datalist) $datalist = array();

		$rdata = [];
        $rdata['status'] = 1;
		$rdata['datalist'] = $datalist;
		return $this->json($rdata);
	}

    //编辑
    public function edit(){
        if(input('param.id')){
            $info = Db::name('restaurant_product_category')->where('aid',aid)->where('bid',bid)->where('id',input('param.id/d'))->find();
        }else{
            $info = ['id'=>'','status'=>1, 'is_booking' => 1,'is_shop' =>1,'is_takeaway'=>1, 'sort' => 0];
        }

        $rdata = [];
        $rdata['info'] = $info;
        return $this->json($rdata);
    }

    //编辑
    public function save(){
        if(input('post.id')) $cate = Db::name('restaurant_product_category')->where('aid',aid)->where('bid',bid)->where('id',input('post.id/d'))->find();
        $info = input('post.info/a');
        $data = array();
        $data['name'] = $info['name'];
        $data['is_booking'] = $info['is_booking'];
        $data['is_shop'] = $info['is_shop'];
        $data['is_takeaway'] = $info['is_takeaway'];
        $data['sort'] = $info['sort'];
        $data['status'] = $info['status'];

        if($cate){
            Db::name('restaurant_product_category')->where('aid',aid)->where('bid',bid)->where('id',$cate['id'])->update($data);
            $id = $cate['id'];
            \app\common\System::plog('餐饮菜品分类编辑'.$id);
        }else{
            $data['aid'] = aid;
            $data['bid'] = bid;
            $data['create_time'] = time();
            $id = Db::name('restaurant_product_category')->insertGetId($data);
            \app\common\System::plog('餐饮菜品分类编辑'.$id);
        }

        return json(['status'=>1,'msg'=>'操作成功']);
    }

    //删除
    public function del(){
        $id = input('post.id/d');
        Db::name('restaurant_product_category')->where(['aid'=>aid,'bid'=>bid,'id'=>$id])->delete();
        Db::name('restaurant_product')->where(['aid'=>aid,'bid'=>bid])->whereFindInSet('cid', $id)->delete();
        return $this->json(['status'=>1,'msg'=>'操作成功']);
    }
}
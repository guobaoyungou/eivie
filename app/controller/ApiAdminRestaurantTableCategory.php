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

//管理员中心 - 餐桌分类
namespace app\controller;
use think\facade\Db;
class ApiAdminRestaurantTableCategory extends ApiAdmin
{
	public function index(){
		$where = ['aid'=>aid,'bid'=>bid];

		$pernum = 20;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
        if(input('post.status')) $where[] = ['status', input('post.status')];
		$datalist = Db::name('restaurant_table_category')->where($where)->page($pagenum,$pernum)->order('sort desc,id desc')->select();
		if(!$datalist) $datalist = array();

		$rdata = [];
        $rdata['status'] = 1;
		$rdata['datalist'] = $datalist;
		return $this->json($rdata);
	}

    //编辑
    public function edit(){
        if(input('param.id')){
            $info = Db::name('restaurant_table_category')->where('aid',aid)->where('bid',bid)->where('id',input('param.id/d'))->find();
        }else{
            $info = ['id'=>'','status'=>1, 'service_fee' => 0,'limit_fee' =>0,'booking_fee'=>0, 'sort' => 0];
        }

        $rdata = [];
        $rdata['info'] = $info;
        return $this->json($rdata);
    }

    //编辑
    public function save(){
        if(input('post.id')) $cate = Db::name('restaurant_table_category')->where('aid',aid)->where('bid',bid)->where('id',input('post.id/d'))->find();
        $info = input('post.info/a');
        $data = array();
        $data['name'] = $info['name'];
        $data['pic'] = $info['pic'];
        $data['service_fee'] = $info['service_fee'];
        $data['limit_fee'] = $info['limit_fee'];
        $data['booking_fee'] = $info['booking_fee'];
        $data['seat'] = $info['seat'];
        $data['sort'] = $info['sort'];
        $data['status'] = $info['status'];

        if($cate){
            $data['update_time'] = time();
            Db::name('restaurant_table_category')->where('aid',aid)->where('bid',bid)->where('id',$cate['id'])->update($data);
            $id = $cate['id'];
            \app\common\System::plog('餐饮餐桌分类编辑'.$id);
        }else{
            $data['aid'] = aid;
            $data['bid'] = bid;
            $data['create_time'] = time();
            $id = Db::name('restaurant_table_category')->insertGetId($data);
            \app\common\System::plog('餐饮餐桌分类编辑'.$id);
        }

        return json(['status'=>1,'msg'=>'操作成功']);
    }

    //删除
    public function del(){
        $id = input('post.id/d');
        Db::name('restaurant_table_category')->where(['aid'=>aid,'bid'=>bid,'id'=>$id])->delete();
        Db::name('restaurant_table')->where(['aid'=>aid,'bid'=>bid,'cid'=>$id])->delete();
        return $this->json(['status'=>1,'msg'=>'操作成功']);
    }
}
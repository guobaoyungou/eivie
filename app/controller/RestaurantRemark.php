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
// | 餐饮预置备注
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;
class RestaurantRemark extends Common
{
    //列表
    public function index(){
        if(request()->isAjax()){
            $page = input('param.page');
            $limit = input('param.limit');
            if(input('param.field') && input('param.order')){
                $order = input('param.field').' '.input('param.order');
            }else{
                $order = 'id desc';
            }
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            if(input('param.keyword')) $where[] = ['name','like','%'.input('param.keyword').'%'];
            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['createtime','>=',strtotime($ctime[0])];
                $where[] = ['createtime','<',strtotime($ctime[1]) + 86400];
            }
            $count = 0 + Db::name('restaurant_remark')->where($where)->count();
            $list = Db::name('restaurant_remark')->where($where)->page($page,$limit)->order($order)->select()->toArray();
         
            return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$list]);
        }
        return View::fetch();
    }
    public function getList(){
        $list = Db::name('restaurant_remark')->where('aid',aid)->where('bid',bid)->select()->toArray();
        return json(['status'=>1,'data'=>$list]);
    }
    //编辑
    public function edit(){
        if(input('param.id')){
            $where = [];
            $where[] = ['aid','=',aid];
            if(bid>0){
                $where[] = ['bid','=',bid];
            }
            $info = Db::name('restaurant_remark')->where($where)->where('id',input('param.id/d'))->find();
        }else {
            $info = array('id' => '');
        }
        $businesslist = [];
        if(bid==0){
            $businesslist = Db::name('business')->where('aid',aid)->select()->toArray();
        }
        View::assign('businesslist',$businesslist);
        View::assign('info',$info);
        View::assign('bid',bid);
        return View::fetch();
    }
    //保存
    public function save(){
        $info = input('post.info/a');
        if($info['id']){
            $where = [];
            $where[] = ['aid','=',aid];
            if(bid>0){
                $where[] = ['bid','=',bid];
            }
            $info['updatetime'] = time();
            $res = Db::name('restaurant_remark')->where($where)->where('id',$info['id'])->update($info);
            if($res){
                \app\common\System::plog('餐饮备注预置修改');
            }else{
                return json(['status'=>0,'msg'=>'您不能修改该数据']);
            }
        }else{
            $info['aid'] = aid;
            $info['bid'] = bid;
            $info['createtime'] = time();
            $id = Db::name('restaurant_remark')->insertGetId($info);
            \app\common\System::plog('餐饮备注预置添加');
        }
        return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
    }
    //删除
    public function del(){
        $ids = input('post.ids/a');
        $where = [];
        $where[] = ['aid','=',aid];
        if(bid>0){
            $where[] = ['bid','=',bid];
        }
        $res = Db::name('restaurant_remark')->where($where)->where('id','in',$ids)->delete();
        if($res){
            \app\common\System::plog('餐饮备注预置删除ids='.implode(',',$ids));
            return json(['status'=>1,'msg'=>'删除成功']);
        }else{
            return json(['status'=>0,'msg'=>'您不能删除该数据']);
        }
    }
}

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

//custom_file(wx_channels)
//视频号小店
namespace app\controller;

use app\common\System;
use think\facade\View;
use think\facade\Db;

class WxChannelsCategory extends Common
{
    public function initialize()
    {
        parent::initialize();
        if (!getcustom('wx_channels_business') && bid > 0) showmsg('无访问权限');
        $this->appid = \app\common\WxChannels::defaultApp(aid,bid);
    }


    public function index()
    {
        $type = input('type', 0);
        if (request()->isAjax()) {
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['appid','=',$this->appid];
            if ($type == 0) {
//                $list = \think\facade\Cache::remember('wxChannelsCategory_'.$this->appid, function () {
                $field = 'cat_id id,f_cat_id pid,name,need_to_apply,level deep';
                    $data = [];
                $data = Db::name('channels_category')->where($where)->field($field)->select()->toArray();
//                    foreach ($cate0 as $c0) {
//                        $c0['deep'] = 0;
//                        $data[] = $c0;
//                        $cate1 = Db::name('channels_category')->where($where)->where('f_cat_id', $c0['id'])->field($field)->select()->toArray();
//                        foreach ($cate1 as $k1 => $c1) {
//                            $c1['deep'] = 1;
//                            $data[] = $c1;
//                            $cate2 = Db::name('channels_category')->where($where)->where('f_cat_id', $c1['id'])->field($field)->select()->toArray();
//                            foreach ($cate2 as $k2 => $c2) {
//                                $c2['deep'] = 2;
//                                $data[] = $c2;
//                                $cate3 = Db::name('channels_category')->where($where)->where('f_cat_id', $c2['id'])->field($field)->select()->toArray();
//                                foreach ($cate3 as $k3 => $c3) {
//                                    $c3['deep'] = 3;
//                                    $data[] = $c3;
//                                }
//                            }
//                        }
//                    }
//                    return $data;
//                });
                return json(['code' => 0, 'msg' => '查询成功', 'count' => count($data), 'data' => $data?:[]]);
            } else {
                //平台全部类目
               // $list = \think\facade\Cache::remember('wxChannelsCategoryBasic', function () {
                $field = 'cat_id id,f_cat_id pid,name,need_to_apply,level deep';
                    $data = [];
                $data = Db::name('channels_category_basic')->where($where)->field($field)->select()->toArray();
//                    foreach ($cate0 as $c0) {
//                        $c0['deep'] = 0;
//                        $data[] = $c0;
//                        $cate1 = Db::name('channels_category_basic')->where($where)->where('f_cat_id', $c0['id'])->field($field)->select()->toArray();
//                        foreach ($cate1 as $k1 => $c1) {
//                            $c1['deep'] = 1;
//                            $data[] = $c1;
//                            $cate2 = Db::name('channels_category_basic')->where($where)->where('f_cat_id', $c1['id'])->field($field)->select()->toArray();
//                            foreach ($cate2 as $k2 => $c2) {
//                                $c2['deep'] = 2;
//                                $data[] = $c2;
//                            }
//                        }
//                    }
                   // return $data;
               // });
                return json(['code' => 0, 'msg' => '查询成功', 'count' => count($data), 'data' => $data]);
            }
        }
        return View::fetch('index', ['type' => $type]);
    }

    //同步可使用类目
    public function asyncCate()
    {
        Db::startTrans();
        try {
            Db::name("channels_category")->where('appid', $this->appid)->where('aid',aid)->where('bid',bid)->delete();
            $res = \app\common\WxChannels::availableSonCategories(aid,bid, $this->appid, 1);
            Db::commit();
            if($res['status']==1){
                $res['msg'] = '同步成功';
            }
            return json($res);
        } catch (\Throwable $t) {
            Db::rollback();
            return json(['status' => 0, 'msg' => $t->getMessage()]);
        }
    }

    //同步平台所有类目
    public function asyncAllCate()
    {
        Db::startTrans();
        Db::name("channels_category_basic")->where('appid', $this->appid)->where('aid',aid)->where('bid',bid)->delete();
        $res = \app\common\WxChannels::categoryAll(aid,bid, $this->appid, 1);
        Db::commit();
        if($res['status']==1){
            $res['msg'] = '同步成功';
        }
        return json($res);
    }
    public function updateAllCate()
    {
        Db::startTrans();
        try {
            $last_id = input('last_id');

            $last_info = Db::name("channels_category_basic")->where('appid', $this->appid)->where('id','>',$last_id)
                ->where('level','=',3)->where('aid',aid)->where('bid',bid)->order('id asc')->find();
            if(!$last_info && $last_id!=0){
                return json(['status' => 0, 'msg' => '同步完成']);
            }
            $res = \app\common\WxChannels::catDetail(aid,bid, $this->appid, $last_info['cat_id']);
            if($res['status'] == 0){
                return json($res);
            }
            $deposit = $res['attr']['deposit'];
            $data_u = [];
            $data_u['deposit'] = $deposit/100;
            Db::name('channels_category_basic')->where('id',$last_info['id'])->update($data_u);
            if($last_id==0){
                $last_info = Db::name("channels_category_basic")->where('appid', $this->appid)
                    ->where('level','=',3)->where('id','>',$last_id)->order('id asc')->where('aid',aid)->where('bid',bid)->find();
            }
            //echo Db::getLastSql();
            //dump($last_info);exit;
            Db::commit();
            return json(['status' => 1, 'msg' => '同步成功','last_id'=>$last_info['id'],'cat_name'=>$last_info['name']]);
        } catch (\Throwable $t) {
            Db::rollback();
            return json(['status' => 0, 'msg' => $t->getMessage()]);
        }
    }

    //查看类目信息
    public function view(){
        $id = input('id');
        $info = Db::name('channels_category_detail')->where('cat_id',$id)->find();
        if(!$info){
            $res = \app\common\WxChannels::catDetail(aid,bid, $this->appid, $id);
            if(!$res['status']){
                echojson(['status'=>0,'msg'=>$res['msg']?:'获取类目信息失败']);exit;
            }
            $info = [];
            $info['cat_id'] = $id;
            $info['info'] = jsonEncode($res['info']);
            $info['attr'] = jsonEncode($res['attr']);
            $info['product_qua_list'] = jsonEncode($res['product_qua_list']);
            $info['createtime'] = time();
            Db::name('channels_category_detail')->insert($info);
        }
        $basci_info = json_decode($info['info'],true);
        $attr = json_decode($info['attr'],true);
        $product_qua_list = json_decode($info['product_qua_list'],true);
        View::assign('basic_info',$basci_info);
        View::assign('attr',$attr);
        View::assign('product_qua_list',$product_qua_list);
        return View::fetch();
    }

    public function apply_lists()
    {
        $type = input('type')?:2;
        $cat_status_arr = \app\common\WxChannels::cat_status;
        if (request()->isAjax()) {
            $page = input('param.page');
            $limit = input('param.limit');
            if(input('param.field') && input('param.order')){
                $order = input('param.field').' '.input('param.order');
            }else{
                $order = 'id desc';
            }
            $where = array();
            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['record.createtime','>=',strtotime($ctime[0])];
                $where[] = ['record.createtime','<',strtotime($ctime[1]) + 86400];
            }
            if(input('name')){
                $where[] = ['cat_name','like','%'.input('name').'%'];
            }

            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['appid','=',$this->appid];
            if(input('?param.cid') && input('param.cid')!==''){
                $cid = input('param.cid');
                $where[] = ['level1|level2|level3','=',$cid];
            }
            $count = 0 + Db::name('channels_category_apply')->where($where)->count();
            $data = Db::name('channels_category_apply')->where($where)->page($page,$limit)->order($order)->select()->toArray();

            $cat_names = Db::name('channels_category_basic')->column('name','cat_id');
            foreach ($data as $k=>$v) {
                $cat_name = $cat_names[$v['level1']].'-'.$cat_names[$v['level2']].'-'.$cat_names[$v['level3']];
                $data[$k]['cat_name'] = $cat_name;
                $data[$k]['status_name'] = $cat_status_arr[$v['status']]??'审核中';
            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $count, 'data' => $data?:[]]);
        }

        View::assign('cat_status_arr',$cat_status_arr);

        //分类
        $clist = Db::name('channels_category')->Field('cat_id,name')
            ->where('aid',aid)
            ->where('appid',$this->appid)
            ->where('bid',bid)
            ->where('f_cat_id',0)->order('id desc')->select()->toArray();
        foreach($clist as $k=>$v){
            $child = Db::name('channels_category')->Field('cat_id,name')
                ->where('aid',aid)
                ->where('appid',$this->appid)
                ->where('bid',bid)
                ->where('f_cat_id',$v['cat_id'])->order('id desc')->select()->toArray();
            foreach($child as $k2=>$v2){
                $child2 = Db::name('channels_category')
                    ->Field('cat_id,name')
                    ->where('aid',aid)
                    ->where('appid',$this->appid)
                    ->where('bid',bid)
                    ->where('f_cat_id',$v2['cat_id'])->order('id desc')->select()->toArray();
                $child[$k2]['child'] = $child2;
            }
            $clist[$k]['child'] = $child;
        }
        View::assign('clist',$clist);
        return View::fetch();
    }

    //申请类目
    public function apply(){
        $id = input('id');
        $cat_info = Db::name('channels_category_detail')->where('cat_id',$id)->find();
        if(!$cat_info){
            $res = \app\common\WxChannels::catDetail(aid,bid, $this->appid, $id);
            if(!$res['status']){
                echojson(['status'=>0,'msg'=>$res['msg']?:'获取类目信息失败']);exit;
            }
            $cat_info = [];
            $cat_info['cat_id'] = $id;
            $cat_info['info'] = jsonEncode($res['info']);
            $cat_info['attr'] = jsonEncode($res['attr']);
            $cat_info['product_qua_list'] = jsonEncode($res['product_qua_list']);
            $cat_info['createtime'] = time();
            Db::name('channels_category_detail')->insert($cat_info);
        }
        $basic_info = json_decode($cat_info['info'],true);
        $attr = json_decode($cat_info['attr'],true);
        $product_qua_list = json_decode($cat_info['product_qua_list'],true);
        View::assign('basic_info',$basic_info);
        View::assign('attr',$attr);
        View::assign('product_qua_list',$product_qua_list);
        //资质类型
        $file_type = \app\common\WxChannels::category_file_type;
        View::assign('file_type',$file_type);
        //经营平台
        $category_plate = \app\common\WxChannels::category_plate;
        View::assign('category_plate',$category_plate);
        $info = Db::name('channels_category_apply')->where('level3',$id)->where('aid',aid)->where('appid',$this->appid)->where('bid',bid)->find();
        $brand_list = [];
        if($info && $info['brand_list']){
            $brand_ids = json_decode($info['brand_list'],true);
            $brand_list = Db::name('channels_brand_basic')->where('brand_id','in',$brand_ids)->select()->toArray();

            $license_field_list = json_decode($info['license_field_list'],true);
            $license_pics = json_decode($info['license_pics'],true);
        }
        View::assign('info',$info);
        View::assign('brand_list',$brand_list);
        View::assign('license_field_list',$license_field_list);
        View::assign('license_pics',$license_pics);

        $qua = Db::name('channels_category')->where('cat_id',$id)->where('aid',aid)->where('appid',$this->appid)->value('qua');
        $qua = json_decode($qua,true);
        //dump($qua);exit;
        View::assign('qua',$qua);
        return View::fetch();
    }
    public function save(){
        //dump(input());
        $info = input('info');
        $info['aid'] = aid;
        $info['bid'] = bid;
        $info['appid'] = $this->appid;
        $level3 = $info['cat_id'];
        $level2 = Db::name('channels_category_basic')->where('cat_id',$level3)->value('f_cat_id');
        $level1 = Db::name('channels_category_basic')->where('cat_id',$level2)->value('f_cat_id');
        $info['level1'] = $level1;
        $info['level2'] = $level2;
        $info['level3'] = $level3;
        $brand_list = $info['brand_list'];
        $info['brand_list'] = json_encode($brand_list);
        $info['createtime'] = time();

        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',bid];
        $where[] = ['appid','=',$this->appid];
        $where[] = ['cat_id','=',$level3];
        $cats = [];
        $category = Db::name('channels_category')->where($where)->find();
        $parent_id = $category['f_cat_id'];
        $cats[] = ['cat_id' => (int)$category['cat_id']];
        while ($parent_id>0){
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['appid','=',$this->appid];
            $where[] = ['cat_id','=',$parent_id];
            $category = Db::name('channels_category')->where($where)->find();
            $parent_id = $category['f_cat_id'];
            $cats[] = ['cat_id' => (int)$category['cat_id']];
        }
        $cats = array_reverse($cats);
        $data = [
//            'level1' => (int)$level1,
//            'level2' => (int)$level2,
//            'level3' => (int)$level3,
            'cats_v2' => $cats,
            'is_new_apply_cat' => true
        ];
        $qua = Db::name('channels_category')->where('cat_id',$level3)->where('aid',aid)->where('appid',$this->appid)->value('qua');
        $qua = json_decode($qua,true);
        $license_pics = input('license/a');
        $license_field_list_input = input('license_field_list/a');
        //dump($license_field_list_input);
        $license_group_list = [];
        foreach($qua['cert_group_list'] as $cert_group_list){
            $arr = [];
            $arr['license_group_id'] = $cert_group_list['license_group_id'];
            foreach($cert_group_list['license_list'] as $license_list){
                $field_ids = [];
                if(!empty($license_pics[$license_list['id']])){
                    $field_ids = Db::name('admin_upload')->where('url','in',$license_pics[$license_list['id']])->column('channels_file_id');
                }
                $license = [
                    "license_id" => $license_list['id'], // 	证照id
                    "file_id_list" => $field_ids,

                ];
                $license_field_values = $license_field_list_input[$license_list['id']];
                $license_field_list = [];
                foreach($license_list['license_field_list'] as $license_field_list_set){
                    $field_key = $license_field_list_set['field_key'];
                    $field_value = $license_field_values[$field_key];
                    //正则检测值的有效性
                    $rule = $license_field_list_set['info'];
                    if($rule){
                        if(!preg_match('/'.$rule.'/',$field_value)){
                            return json(['status'=>0,'msg'=>'请填写正确的'.$license_list['name'].$license_field_list_set['field_name']]);
                        }
                    }
                    $license_field_list[] = [
                        'key' => $field_key,
                        'value' => $field_value
                    ];
                }
                $license['license_field_list'] = $license_field_list;
                $arr['license'] = $license;
                $license_group_list[] = $arr;
            }
        }
        $data['license_group_list'] = $license_group_list;
//        $file_type = \app\common\WxChannels::category_file_type;
//        foreach($file_type as $field=>$field_name){
//            $field_ids = '';
//            $info[$field.'_ids'] = '';
//            if(!empty($info[$field])){
//                $field_ids = Db::name('admin_upload')->where('url','in',$info[$field])->column('channels_file_id');
//                $info[$field.'_ids'] = implode('',$field_ids);
//            }
//
//            if($field_ids){
//                $data[$field] = $field_ids;
//            }
//        }
        $brand_list_data = [];
       foreach($brand_list as $brand_id){
           $brand_list_data[] = ['brand_id'=>$brand_id];
       }
        $data['brand_list'] = $brand_list_data;
        $res = \app\common\WxChannels::applyCategory(aid,bid,$this->appid,$data);
        if($res['status']){
            $info['audit_id'] = $res['data'];
        }else{
            return json($res);
        }
        $info['license_field_list'] = jsonEncode($license_field_list_input);
        $info['license_pics'] = jsonEncode($license_pics);
        $info['status'] = 1;
        if($info['id']){
            Db::name('channels_category_apply')->where('id',$info['id'])->update($info);
        }else{
            Db::name('channels_category_apply')->insert($info);
        }
        \app\common\System::plog("申请视频号小店类目：【{$level3}】");
        return json(['status'=>1,'msg'=>'提交成功，请等待审核']);
    }

    //撤销类目申请
    public function cancel(){
        $id = input('id');
        $info = Db::name('channels_category_apply')->where('id',$id)->find();
        if(!$info){
            return json(['status'=>0,'msg'=>'参数错误']);
        }
        $audit_id = $info['audit_id'];
        $res = \app\common\WxChannels::cancelCategory(aid,bid,$this->appid,$audit_id);
        if($res['status']){
            Db::name('channels_category_apply')->where('id',$id)->update(['status'=>12]);
            \app\common\System::plog("撤销视频号小店类目申请：".$info['level3']);
            return json(['status'=>1,'msg'=>'提交成功']);
        }else{
            return json($res);
        }
    }
}
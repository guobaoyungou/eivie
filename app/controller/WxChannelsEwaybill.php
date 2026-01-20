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
//视频号小店 电子面单
namespace app\controller;

use app\common\System;
use think\facade\View;
use think\facade\Db;

class WxChannelsEwaybill extends Common
{
    public function initialize()
    {
        parent::initialize();
        if (!getcustom('wx_channels_business') && bid > 0) showmsg('无访问权限');
        $this->appid = \app\common\WxChannels::defaultApp(aid,bid);
        $childmenu = [
            [
                'path' => 'WxChannelsEwaybill/index',
                'name' => '快递公司'
            ],
            [
                'path' => 'WxChannelsEwaybillAccount/index',
                'name' => '网点账号'
            ],
            [
                'path' => 'WxChannelsEwaybill/template',
                'name' => '面单模板'
            ],
        ];
        View::assign('childmenu',$childmenu);
        $thispath = request()->controller().'/'.request()->action();
        View::assign('thispath',$thispath);
    }
    public function index()
    {
        if (request()->isAjax()) {
            $page = [
                "list_rows" => input('limit', 20),
                "page" => input('page', 1),
            ];
            if(input('param.field') && input('param.order')){
                $order = input('param.field').' '.input('param.order');
            }else{
                $order = 'id desc';
            }
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['appid','=',$this->appid];
            $list = Db::name("channels_ewaybill_delivery")
                ->where($where)
                ->order($order)
                ->select()
                ->toArray();
            $data = $list;
            foreach($data as $k=>$v){

            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => count($data), 'data' => $data]);
        }
        return View::fetch();
    }
    //同步快递公司
    public function asyncAllDelivery()
    {
        Db::startTrans();
        try {
            Db::execute('truncate table ddwx_channels_ewaybill_delivery');
            $status = input('status');
            $status = 0;
            //售后的单列表
            $res = \app\common\WxChannels::getEwabillDelivery(aid,bid, $this->appid, $status);
            if($res['status'] == 0 ){
                return json($res);
            }
            $lists = $res['data'];
            $shop_id = $res['shop_id'];
            foreach ($lists as $delivery) {
                $data = [
                    "aid" => aid,
                    "bid" => bid,
                    "appid" => $this->appid,
                    "shop_id" => $shop_id,
                    "delivery_id" => $delivery['delivery_id'],
                    "delivery_name" => $delivery['delivery_name'],
                ];
                Db::name('channels_ewaybill_delivery')->insert($data);
            }

            Db::commit();
            return json(['status' => 1, 'msg' => '同步成功','next_key'=>$res['next_key']]);
        } catch (\Throwable $t) {
            Db::rollback();
            return json(['status' => 0, 'msg' => $t->getMessage()]);
        }
    }

    //获取电子免单模板
    public function template()
    {
        $type = input('type')?:0;
        $brand_status_arr = \app\common\WxChannels::brand_status;
        if (request()->isAjax()) {
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
            $where[] = ['appid','=',$this->appid];

            $count = 0 + Db::name('channels_ewaybill_template')->where($where)->count();
            $data = Db::name('channels_ewaybill_template')->where($where)->page($page,$limit)->order($order)->select()->toArray();


            foreach ($data as $k=>$v) {
                $data[$k]['status_name'] = $brand_status_arr[$v['status']]??'审核中';
            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $count, 'data' => $data?:[]]);
        }

        View::assign('brand_status_arr',$brand_status_arr);
        return View::fetch();
    }

    //同步模板
    public function asyncAllTemplate()
    {
        Db::startTrans();
//        try {
            $status = input('status');
            $status = 3;
            //售后的单列表
            $res = \app\common\WxChannels::getEwabillTemplate(aid,bid, $this->appid);
            if($res['status'] == 0 ){
                return json($res);
            }
            $lists = $res['data'];
            foreach($lists as $template_data){
                foreach ($template_data['template_list'] as $template) {
                    $options = $template['options'];
                    foreach($options as $k=>$v){
                        $options[$k]['option_id'] = $k;
                        $options[$k]['font_size'] = (int)$v['font_size'];
                        $options[$k]['is_bold'] = $v['is_bold']==true?1:0;
                        $options[$k]['is_open'] = $v['is_open']==true?1:0;
                    }
                    $data = [
                        "aid" => aid,
                        "bid" => bid,
                        "appid" => $this->appid,
                        "is_default" => $template['is_default'],
                        "template_desc" => $template['template_desc'],
                        "template_id" => $template['template_id'],
                        "template_name" => $template['template_name'],
                        "template_type" => $template['template_type'],
                        "update_time" => $template['update_time'],
                        "create_time" => $template['create_time'],
                        "options" => json_encode($options),
                        "delivery_id" => $template_data['delivery_id']
                    ];
                    $exit = Db::name('channels_ewaybill_template')->where('template_id',$template['template_id'])->find();
                    if($exit){
                        Db::name('channels_ewaybill_template')->where('id',$exit['id'])->update($data);
                    }else{
                        Db::name('channels_ewaybill_template')->insert($data);
                    }

                }
            }
            Db::commit();
            return json(['status' => 1, 'msg' => '同步成功','next_key'=>$res['next_key']]);
//        } catch (\Throwable $t) {
//            Db::rollback();
//            return json(['status' => 0, 'msg' => $t->getMessage()]);
//        }
    }

    //获取电子免单模板
    public function template_config()
    {
        $type = input('type')?:0;
        $brand_status_arr = \app\common\WxChannels::brand_status;
        if (request()->isAjax()) {
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
            $where[] = ['appid','=',$this->appid];
            $count = 0 + Db::name('channels_ewaybill_template_config')->where($where)->count();
            $data = Db::name('channels_ewaybill_template_config')->where($where)->page($page,$limit)->order($order)->select()->toArray();


            foreach ($data as $k=>$v) {
                $data[$k]['status_name'] = $brand_status_arr[$v['status']]??'审核中';
            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $count, 'data' => $data?:[]]);
        }

        View::assign('brand_status_arr',$brand_status_arr);
        return View::fetch();
    }

    //同步模板
    public function asyncAllTemplateConfig()
    {
        Db::startTrans();
        try {
            //标准电子面单模板
            Db::name('channels_ewaybill_template_config')->where('aid',aid)->where('appid',$this->appid)->where('bid',bid)->delete();
            $res = \app\common\WxChannels::getEwabillTemplateConfig(aid,bid, $this->appid);
            if($res['status'] == 0 ){
                return json($res);
            }
            $lists = $res['data'];
            $shop_id = $res['shop_id'];
            foreach ($lists as $delivery_id=>$template_arr) {
                foreach($template_arr as $template){
                    $data = [
                        "aid" => aid,
                        "bid" => bid,
                        "appid" => $this->appid,
                        "delivery_id" => $delivery_id,
                        "type" => $template['type'],
                        "desc" => $template['desc'],
                        "width" => $template['width'],
                        "height" => $template['height'],
                        "url" => $template['url'],
                        "custom_config_width" => $template['custom_config']['width'],
                        "custom_config_height" => $template['custom_config']['height'],
                        "custom_config_left" => $template['custom_config']['left'],
                        "custom_config_top" => $template['custom_config']['top'],
                    ];
                    Db::name('channels_ewaybill_template_config')->insert($data);
                }

            }

            Db::commit();
            return json(['status' => 1, 'msg' => '同步成功','next_key'=>$res['next_key']]);
        } catch (\Throwable $t) {
            Db::rollback();
            return json(['status' => 0, 'msg' => $t->getMessage()]);
        }
    }
    //编辑模板
    public function edit_template(){
        $id = input('id');
        $template_info = Db::name('channels_ewaybill_template')->where('id',$id)->find();
        if(request()->isPost()){
            $input = input();
            $params = [];
            $params['template_name'] = $input['info']['template_name'];
            $params['template_desc'] = $input['info']['template_desc'];
            $params['template_type'] = $input['info']['template_type'];
            $options = $input['option'];
            foreach($options as $k=>$v){
                $options[$k]['option_id'] = $k;
                $options[$k]['font_size'] = (int)$v['font_size'];
                $options[$k]['is_bold'] = $v['is_bold']==1?true:false;
                $options[$k]['is_open'] = $v['is_open']==1?true:false;
            }
            $params['options'] = $options;
            if($template_info){
                $params['template_id'] = $template_info['template_id'];
                $res = \app\common\WxChannels::editEwabillTemplate(aid,bid,$this->appid,$input['info']['delivery_id'],$params);
                if(!$res['status']){
                    return json($res);
                }
                $template_id = $template_info['template_id'];
            }else{
                $res = \app\common\WxChannels::addEwabillTemplate(aid,bid,$this->appid,$input['info']['delivery_id'],$params);
                if(!$res['status']){
                    return json($res);
                }
                $template_id = $res['data'];
            }


            $data = $input['info'];
            $data['options'] = json_encode($input['option']);
            $data['template_id'] = $template_id;
            if($template_info){
                Db::name('channels_ewaybill_template')->where('id',$id)->update($data);
            }else{
                Db::name('channels_ewaybill_template')->insert($data);
            }
            return json(['status'=>1,'msg'=>'操作成功']);
        }else{
            $option_ids = \app\common\WxChannels::option_ids;
            View::assign('option_ids',$option_ids);

            $delivery_lists = Db::name('channels_ewaybill_delivery')
                ->where('aid',aid)
                ->where('appid',$this->appid)
                ->where('bid',bid)
                ->select()
                ->toArray();
            View::assign('delivery_lists',$delivery_lists);

            View::assign('info',$template_info);
            $options = json_decode($template_info['options'],true);
            View::assign('options',$options);
            return View::fetch();
        }
    }
    //删除模板
    public function delTemplate(){
        $ids = input('ids');
        Db::startTrans();
        $lists = Db::name('channels_ewaybill_template')->where('id','in',$ids)->select()->toArray();
        foreach($lists as $v){
            if($v['template_id']){
                $res = \app\common\WxChannels::delTemplate(aid,bid,$this->appid,$v['delivery_id'],$v['template_id']);
                if(!$res['status']){
                    return json($res);
                }
            }
            Db::name('channels_ewaybill_template')->where('id',$v['id'])->delete();
        }
        Db::commit();
        return json(['status'=>1,'msg'=>'删除成功']);
    }

    public function choosetemplate(){
        if(request()->isPost()){
            $id = input('id');
            $info = Db::name('channels_ewaybill_template')->where('id',$id)->find();
            return json(['status'=>1,'data'=>$info]);
        }else{
            View::assign('is_choose',1);
            return View::fetch('template');
        }

    }
}
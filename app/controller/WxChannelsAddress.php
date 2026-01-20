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
//视频号小店 地址关联
namespace app\controller;

use app\common\System;
use think\facade\View;
use think\facade\Db;

class WxChannelsAddress extends Common
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
            $page = input('param.page');
            $limit = input('param.limit');
            if(input('param.field') && input('param.order')){
                $order = input('param.field').' '.input('param.order');
            }else{
                $order = 'id asc';
            }
            $where = array();
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['appid','=',$this->appid];
            if(input('user_name')){
                $where[] = ['user_name','like','%'.input('user_name').'%'];
            }
            if(input('tel_number')){
                $where[] = ['tel_number','like','%'.input('tel_number').'%'];
            }
            if(input('address_id')){
                $where[] = ['address_id','=',input('address_id')];
            }
            $count = 0 + Db::name('channels_address')->where($where)->count();
            $data = Db::name('channels_address')->where($where)->page($page,$limit)->order($order)->select()->toArray();
            foreach ($data as $k=>$v) {

            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $count, 'data' => $data?:[]]);
        }
        return View::fetch('index', ['type' => $type]);
    }
    //同步地址
    public function asyncAddress()
    {
        Db::startTrans();
        try {
            $pagenum = input('pagenum');
            $pagelimit = input('pagelimit/d');

            $offset = bcmul($pagenum-1,$pagelimit);
            $res = \app\common\WxChannels::getAddressList(aid,bid, $this->appid, $offset,$pagelimit);
            if($res['status'] == 0){
                return json($res);
            }
            $data = $res['data'];
            $where = array();
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['appid','=',$this->appid];
            $address_ids = Db::name('channels_address')->where($where)->column('address_id');
            foreach ($data as $address_id) {
                if(!in_array($address_id,$address_ids)){
                    $insert_data = [
                        "aid" => aid,
                        "bid" => bid,
                        "appid" => $this->appid,
                        "address_id" => $address_id,
                    ];
                    Db::name('channels_address')->insert($insert_data);
                }
                $res2 =  \app\common\WxChannels::getAddressDetail(aid,bid, $this->appid, $address_id);
                if($res['status'] == 0){
                    return json($res);
                }
                $address_detail = $res2['data'];
                $address_info = $address_detail['address_info'];
                $data_u = [];
                $data_u['name'] = $address_detail['name'];
                $data_u['user_name'] = $address_info['user_name'];
                $data_u['postal_code'] = $address_info['postal_code'];
                $data_u['province_name'] = $address_info['province_name'];
                $data_u['city_name'] = $address_info['city_name'];
                $data_u['county_name'] = $address_info['county_name'];
                $data_u['detail_info'] = $address_info['detail_info'];
                $data_u['national_code'] = $address_info['national_code'];
                $data_u['tel_number'] = $address_info['tel_number'];
                $data_u['lat'] = $address_info['lat'];
                $data_u['lng'] = $address_info['lng'];
                $data_u['house_number'] = $address_info['house_number'];
                $data_u['landline'] = $address_detail['landline'];
                $data_u['send_addr'] = $address_detail['send_addr'];
                $data_u['recv_addr'] = $address_detail['recv_addr'];
                $data_u['default_send'] = $address_detail['default_send'];
                $data_u['default_recv'] = $address_detail['default_recv'];
                $data_u['create_time'] = $address_detail['create_time'];
                $data_u['update_time'] = $address_detail['update_time'];
                $data_u['same_city'] = $address_detail['address_type']['same_city'];
                $data_u['pickup'] = $address_detail['address_type']['pickup'];
                Db::name('channels_address')->where('address_id',$address_id)->update($data_u);
            }
            Db::commit();
            if(count($data)<$pagelimit){
                return json(['status' => 2, 'msg' => '全部同步成功','sucnum'=>count($data)]);
            }else{
                return json(['status' => 1, 'msg' => '同步成功','sucnum'=>count($data)]);
            }
        } catch (\Throwable $t) {
            Db::rollback();
            return json(['status' => 0, 'msg' => $t->getMessage()]);
        }
    }

    //添加编辑地址
    public function edit(){
        $id = input('id');
        $info = Db::name('channels_address')->where('id',$id)->find();
        if(request()->isPost()){
            $params_info = input('info');
            $address_info = input('addressinfo');
            $address_type = input('address_type');
            //提交微信
            $params =$params_info;
            $params['send_addr'] = $params_info['send_addr']==1?true:false;
            $params['default_send'] = $params_info['default_send']==1?true:false;
            $params['recv_addr'] = $params_info['recv_addr']==1?true:false;
            $params['default_recv'] = $params_info['default_recv']==1?true:false;
            $params['address_info'] = $address_info;
            $params['address_info']['lng'] = (float)$address_info['lng'];
            $params['address_info']['lat'] = (float)$address_info['lat'];
            $params['address_type']['same_city'] = $address_type['same_city']==1?1:0;
            $params['address_type']['pickup'] = $address_type['pickup']==1?1:0;
            if($info['address_id']){
                $params['address_id'] = $info['address_id'];
                $res = \app\common\WxChannels::updateAddress(aid,bid,$this->appid,$params);
                if(!$res['status']){
                    return json($res);
                }
                $address_id = $info['address_id'];
            }else{
                unset($params['address_id']);
                $res = \app\common\WxChannels::addAddress(aid,bid,$this->appid,$params);
                if(!$res['status']){
                    return json($res);
                }
                $address_id = $res['data'];
            }


            //插入数据库
            $data = $params_info;
            $data['aid'] = aid;
            $data['bid'] = bid;
            $data['appid'] = $this->appid;
            $data['address_id'] = $address_id;
            $data['user_name'] = $address_info['user_name'];
            $data['postal_code'] = $address_info['postal_code'];
            $data['province_name'] = $address_info['province_name'];
            $data['city_name'] = $address_info['city_name'];
            $data['county_name'] = $address_info['county_name'];
            $data['detail_info'] = $address_info['detail_info'];
            $data['national_code'] = $address_info['national_code'];
            $data['tel_number'] = $address_info['tel_number'];
            $data['lat'] = $address_info['lat'];
            $data['lng'] = $address_info['lng'];
            $data['house_number'] = $address_info['house_number'];
            $data['same_city'] = $address_type['same_city'];
            $data['pickup'] = $address_type['pickup'];
            if($info){
                Db::name('channels_address')->where('id',$info['id'])->update($data);
            }else{
                Db::name('channels_address')->insert($data);
            }
            \app\common\System::plog('修改小店地址'.$address_id);
            return json(['status'=>1,'msg'=>'添加成功']);
        }else{
            View::assign('info',$info);
            return View::fetch();
        }
    }
    //删除地址
    public function delAddress(){
        $ids = input('ids');
        $lists = Db::name('channels_address')->where('id','in',$ids)->select()->toArray();
        foreach($lists as $info){
            if($info['address_id']){
                $res = \app\common\WxChannels::delAddress(aid,bid,$this->appid,$info['address_id']);
                if(!$res['status']){
                    return json($res);
                }
            }
            Db::name('channels_address')->where('id',$info['id'])->delete();
            \app\common\System::plog('删除小店地址'.$info['address_id']);
        }
        return json(['status'=>1,'msg'=>'删除成功']);
    }
    //选择地址
    public function choosechanelsaddress(){
        return View::fetch();
    }
    public function getAddress(){
        $id = input('id');
        $info = Db::name('channels_address')->where('id',$id)->find();
        return json(['status'=>1,'msg'=>'','data'=>$info]);
    }
}
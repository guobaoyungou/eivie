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
//视频号小店 地址行政区域列表
namespace app\controller;

use app\common\System;
use think\facade\View;
use think\facade\Db;

class WxChannelsArea extends Common
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
                $order = 'level ,id asc';
            }
            $where = array();
            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['record.createtime','>=',strtotime($ctime[0])];
                $where[] = ['record.createtime','<',strtotime($ctime[1]) + 86400];
            }
            if(input('name')){
                $where[] = ['name','like','%'.input('name').'%'];
            }
            if(input('template_id')){
                $where[] = ['template_id','=',input('template_id')];
            }
            $count = 0 + Db::name('channels_area')->where($where)->count();
            $data = Db::name('channels_area')->where($where)->page($page,$limit)->order($order)->select()->toArray();
            foreach ($data as $k=>$v) {

            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $count, 'data' => $data?:[]]);
        }
        return View::fetch('index', ['type' => $type]);
    }
    //同步区域
    public function asyncArea(){
        set_time_limit(0);
        Db::startTrans();
        $addr_code = input('addr_code');
        if(!$addr_code){
            Db::execute('truncate table ddwx_channels_area');
            //未传区域的先清空整个表，更新省份，再挨个更新市
            $area_arr = Db::name('channels_area')->where('level',1)->select()->toArray();
            if(!$area_arr){
                $res =  \app\common\WxChannels::getarea(aid,bid, $this->appid, 0);
                if(!$res['status']){
                    return json($res);
                }
                $area_arr = $res['next_level_addrs'];
                foreach($area_arr as $area){
                    $data = [
                        'name' => $area['name'],
                        'code' => $area['code'],
                        'level' => $area['level']
                    ];
                    Db::name('channels_area')->insert($data);
                }
            }
            $addr_info = Db::name('channels_area')->where('id','>',0)->order('id asc')->find();
        }else{
            $addr_info = Db::name('channels_area')->where('code','=',$addr_code)->find();
        }
        $next_info = Db::name('channels_area')->where('id','>',$addr_info['id'])->where('level',1)->order('id asc')->find();
        $res =  \app\common\WxChannels::getarea(aid,bid, $this->appid, $addr_code);
        if(!$res['status']){
            return json($res);
        }
        $area_arr = $res['next_level_addrs'];
        $all_area_code = Db::name('channels_area')->where('1=1')->column('code');
        foreach($area_arr as $area){
            $data = [
                'name' => $area['name'],
                'code' => $area['code'],
                'level' => $area['level'],
                'parent_code' => $addr_code
            ];
            if(!in_array($area['code'],$all_area_code)){
                Db::name('channels_area')->insert($data);
            }else{
                Db::name('channels_area')->where('code',$area['code'])->update($data);
            }
            //获取市
            $res_childs = \app\common\WxChannels::getarea(aid,bid, $this->appid, $area['code']);
            if(!$res_childs['status']){
                return json($res_childs);
            }
            $area_arr2 = $res_childs['next_level_addrs'];
            foreach($area_arr2 as $area2){
                $data = [
                    'name' => $area2['name'],
                    'code' => $area2['code'],
                    'level' => $area2['level'],
                    'parent_code' => $area['code']
                ];
                if(!in_array($area2['code'],$all_area_code)){
                    Db::name('channels_area')->insert($data);
                }else{
                    Db::name('channels_area')->where('code',$area2['code'])->update($data);
                }
            }
        }
        Db::commit();
        if($next_info){
            $count = Db::name('channels_area')->where('level','=',1)->count();
            $percent = bcmul(bcdiv($next_info['id'],$count,2),100,2);
            $status = 1;
        }else{
            $percent = 100;
            $status = 2;
            $this->update_area_json();
        }
        return json(['status'=>$status,'msg'=>'全部同步成功','next_code'=>$next_info['code'],'province_name'=>$addr_info['name'],'percent'=>$percent]);
    }
    //更新区域数据json文件
    public function update_area_json(){
        set_time_limit(0);

        $province_arr = Db::name('channels_area')->where('level',1)->select()->toArray();
        $data = [];
        $data[] = [
            'name'=>'请选择省',
            'cityList'=>[
		        [
		            'name'=>'请选择市','areaList'=>['请选择地区']
                ]
		    ]
        ];
        //获取省
        foreach($province_arr as $province){
            $arr = [];
            $arr['name'] = $province['name'];
            //获取市
            $cityList = Db::name('channels_area')->where('parent_code',$province['code'])->where('level',2)->select()->toArray();
            $city_arr = [];
            foreach($cityList as $city){
                $areaList = Db::name('channels_area')->where('parent_code',$city['code'])->where('level',3)->select()->toArray();
                $count_arr = array_column($areaList,'name');
                $city_arr[] = ['name'=>$city['name'],'areaList'=>$count_arr];
            }
            $arr['cityList'] = $city_arr;
            $data[] = $arr;
        }
        file_put_contents(ROOT_PATH.'static/admin/js/channels_area.js','var provinceList='.jsonEncode($data));
        return json(['status'=>1,'msg'=>'操作成功！']);
    }
}
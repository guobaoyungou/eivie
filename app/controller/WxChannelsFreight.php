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
//视频号小店 运费模板
namespace app\controller;

use app\common\System;
use think\facade\View;
use think\facade\Db;

class WxChannelsFreight extends Common
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
                $order = 'id desc';
            }
            $where = array();
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['appid','=',$this->appid];
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
            $count = 0 + Db::name('channels_freight')->where($where)->count();
            $data = Db::name('channels_freight')->where($where)->page($page,$limit)->order($order)->select()->toArray();
            foreach ($data as $k=>$v) {
                $valuation_type = $v['valuation_type'];
                $data[$k]['valuation_type'] = \app\common\WxChannels::valuation_type[$v['valuation_type']];
                $data[$k]['send_time'] = \app\common\WxChannels::send_time[$v['send_time']];
                $data[$k]['delivery_type'] = \app\common\WxChannels::delivery_type[$v['delivery_type']];
                $data[$k]['shipping_method'] = \app\common\WxChannels::shipping_method[$v['shipping_method']];

                //计费方式
                $pricedatahtml = '';
                $pricedata = json_decode($v['all_freight_calc_method'],true);
                $pricedata = $pricedata['freight_calc_method_list']??[];
                if(!$pricedata) $pricedata = [];
                foreach($pricedata as $pv){
                    if($pv['address_infos']){
                        $province = implode(',',array_unique(array_column($pv['address_infos'],'province_name')));
                    }else{
                        $province = '全国';
                    }

                    $pricedatahtml .= "<b>".rtrim($province,';').":</b> <br> {$pv['first_val_amount']}".($valuation_type=='PIECE'?'件':'KG')."以下{$pv['first_price']}元，每超出{$pv['second_val_amount']}".($valuation_type=='PIECE'?'件':'KG')."加{$pv['second_price']}元<br>";
                }
                $data[$k]['all_freight_calc_method'] = $pricedatahtml;
                //不发货区域
                $not_send_area_str = '';
                $not_send_area = json_decode($v['not_send_area'],true);
                if($not_send_area){
                    $not_send_area_str = implode(',',array_unique(array_column($not_send_area['address_infos'],'province_name')));
                }
                $data[$k]['not_send_area_str'] = $not_send_area_str;
                //发货地址
                $address_info_str = '';
                $address_info = json_decode($v['address_info'],true);
                if($address_info){
                    $address_info_str = $address_info['user_name'].$address_info['province_name'].$address_info['city_name'].$address_info['county_name'];
                }
                $data[$k]['address_info_str'] = $address_info_str;
            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $count, 'data' => $data?:[]]);
        }
        return View::fetch('index', ['type' => $type]);
    }

    //获取运费模板列表
    public function asyncFreight()
    {
        Db::startTrans();
        try {
            $pagenum = input('pagenum');
            $pagelimit = input('pagelimit/d');
            $res = \app\common\WxChannels::getfreighttemplatelist(aid,bid, $this->appid, 0,100);
            $max_num = $pagelimit;
            if($res['status'] == 0){
                return json($res);
            }
            $max_num = count($res['data']);
            $offset = bcmul($pagenum-1,$pagelimit);
            $res = \app\common\WxChannels::getfreighttemplatelist(aid,bid, $this->appid, $offset,$pagelimit);
            if($res['status'] == 0){
                return json($res);
            }
            $data = $res['data'];
            $where = array();
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['appid','=',$this->appid];
            $template_ids = Db::name('channels_freight')->where($where)->column('template_id');
            foreach ($data as $template_id) {
                if(!in_array($template_id,$template_ids)){
                    $insert_data = [
                        "aid" => aid,
                        "bid" => bid,
                        "appid" => $this->appid,
                        "template_id" => $template_id,
                        "address_info" => '',
                        "all_condition_free_detail" => '',
                        "all_freight_calc_method" => '',
                        "not_send_area" => ''
                    ];
                    Db::name('channels_freight')->insert($insert_data);
                }
                $res2 =  \app\common\WxChannels::getfreighttemplatedetail(aid,bid, $this->appid, $template_id);
                if($res['status'] == 0){
                    return json($res);
                }
                $template_info = $res2['data'];
                //计费方法金额转元
                $all_freight_calc_method = $template_info['all_freight_calc_method'];
                foreach($all_freight_calc_method['freight_calc_method_list'] as $calc_k=>$calc_v){
                    $all_freight_calc_method['freight_calc_method_list'][$calc_k]['first_price'] = bcdiv($calc_v['first_price'],100,2);
                    $all_freight_calc_method['freight_calc_method_list'][$calc_k]['second_price'] = bcdiv($calc_v['second_price'],100,2);
                }
                //条件包邮金额转元
                $all_condition_free_detail = $template_info['all_condition_free_detail'];
                foreach($all_condition_free_detail['condition_free_detail_list'] as $free_k=>$free_v){
                    $all_condition_free_detail['condition_free_detail_list'][$free_k]['min_amount'] = bcdiv($free_v['min_amount'],100,2);
                }
                $data_u = [];
                $data_u['name'] = $template_info['name'];
                $data_u['valuation_type'] = $template_info['valuation_type'];
                $data_u['send_time'] = $template_info['send_time'];
                $data_u['address_info'] = json_encode($template_info['address_info']);
                $data_u['delivery_type'] = $template_info['delivery_type'];
                $data_u['delivery_id'] = $template_info['delivery_id'];
                $data_u['shipping_method'] = $template_info['shipping_method'];
                $data_u['all_condition_free_detail'] = json_encode($all_condition_free_detail);
                $data_u['all_freight_calc_method'] = json_encode($all_freight_calc_method);
                $data_u['create_time'] = $template_info['create_time'];
                $data_u['update_time'] = $template_info['update_time'];
                $data_u['is_default'] = $template_info['is_default'];
                $data_u['not_send_area'] = json_encode($template_info['not_send_area']);
                Db::name('channels_freight')->where('template_id',$template_id)->update($data_u);
            }
            Db::commit();
            if(count($data)<$pagelimit){
                return json(['status' => 2, 'msg' => '全部同步成功','sucnum'=>count($data),'percent'=>100]);
            }else{
                $percent = bcmul(bcdiv($pagenum*$pagelimit,$max_num,4),100,2);
                return json(['status' => 1, 'msg' => '同步成功','sucnum'=>count($data),'percent'=>floatval($percent)]);
            }
        } catch (\Throwable $t) {
            Db::rollback();
            return json(['status' => 0, 'msg' => $t->getMessage()]);
        }
    }

    //编辑
    public function edit(){
        if(input('param.id')){
            $info = Db::name('channels_freight')->where('aid',aid)->where('id',input('param.id/d'))->where('bid',bid)->find();
            $addressinfo = json_decode($info['address_info'],true);
            //计费方式
            $all_freight_calc_method = json_decode($info['all_freight_calc_method'],true);
            $freight_calc_method_list = $all_freight_calc_method['freight_calc_method_list'];
            foreach($freight_calc_method_list as $k=>$v){
                //地址信息转换为前端页面适用的格式
                $address_infos = $v['address_infos'];
                $region = $this->getregion($address_infos);
                $freight_calc_method_list[$k]['region'] = $region;
            }
            //包邮
            $all_condition_free_detail = json_decode($info['all_condition_free_detail'],true);
            $condition_free_detail_list = $all_condition_free_detail['condition_free_detail_list'];
            foreach($condition_free_detail_list as $k=>$v){
                //地址信息转换为前端页面适用的格式
                $address_infos = $v['address_infos'];
                $region = $this->getregion($address_infos);
                $condition_free_detail_list[$k]['region'] = $region;
            }
            $info['not_send_area'] = json_decode($info['not_send_area'],true);

            $not_send_area = $this->getregion($info['not_send_area']['address_infos']);
        }else{
            $addressinfo = [];
            $freight_calc_method_list = [];
            $condition_free_detail_list = [];
            $not_send_area = '';
        }
        $info['isedit']=1;
        View::assign('info',$info);
        $valuation_type = \app\common\WxChannels::valuation_type;//计费类型
        $send_time = \app\common\WxChannels::send_time;//发货时间期限
        $delivery_type = \app\common\WxChannels::delivery_type;//运输方式
        $shipping_method = \app\common\WxChannels::shipping_method;//计费方式
        View::assign('valuation_type',$valuation_type);
        View::assign('send_time',$send_time);
        View::assign('delivery_type',$delivery_type);
        View::assign('shipping_method',$shipping_method);

        View::assign('addressinfo',$addressinfo);
        View::assign('freight_calc_method_list',$freight_calc_method_list);
        View::assign('condition_free_detail_list',$condition_free_detail_list);
        View::assign('not_send_area',$not_send_area);

        //物流公司
        $delivery_lists = Db::name('channels_delivery')->where('aid',aid)->where('appid',$this->appid)->where('bid',bid)->select()->toArray();
        if(empty($delivery_lists)){
            $res = \app\common\WxChannels::getDeliveryLists(aid,bid, $this->appid);
            $delivery_lists = $res['data'];
            foreach($delivery_lists as $k=>$v){
                $data_d = [];
                $data_d['aid'] = aid;
                $data_d['appid'] = $this->appid;
                $data_d['delivery_id'] = $v['delivery_id'];
                $data_d['delivery_name'] = $v['delivery_name'];
                Db::name('channels_delivery')->insert($data_d);
            }
        }
        View::assign('delivery_lists',$delivery_lists);
        return View::fetch();
    }
    //保存
    public function save(){
        $info = input('post.info/a');
        $addressinfo = input('addressinfo');
        //包邮设置
        $condition_free_detail_list_arr = input('condition_free_detail_list/a');
        $condition_free_detail_list = [];
        foreach($condition_free_detail_list_arr['address_infos'] as $k=>$adr){
            //组装地址数据
            $address_infos = [];
            $adr_str_arr = explode(';',$adr);
            foreach($adr_str_arr as $adr_arr){
                if(empty($adr_arr)){
                    continue;
                }
                $province_arr = explode('[',$adr_arr);
                $province_name = $province_arr[0];
                preg_match_all('/\[([^\]]+)\]/', $adr_arr, $matches);
                $area_str = $matches[1][0];
                if($area_str=='全部地区'){
                    $address_infos[] = ['province_name'=>$province_name];
                }else{
                    $city_str_arr = explode(',',$area_str);
                    foreach($city_str_arr as $city_str){
                        $city_arr = explode('|',$city_str);
                        $city_name = $city_arr[0];
                        $county_str = $city_arr[1];
                        if($county_str=='全部县区'){
                            $address_infos[] = ['province_name'=>$province_name,'city_name'=>$city_name];
                        }else{
                            $county_arr = explode('-',$county_str);
                            foreach($county_arr as $county_name){
                                $address_infos[] = ['province_name'=>$province_name,'city_name'=>$city_name,'county_name'=>$county_name];
                            }
                        }
                    }
                }
            }
            $min_piece = $condition_free_detail_list_arr['min_piece'][$k]?:0;
            $min_weight = $condition_free_detail_list_arr['min_weight'][$k]?:0;
            $min_amount = $condition_free_detail_list_arr['min_amount'][$k]?:0;
            $valuation_flag = $condition_free_detail_list_arr['valuation_flag'][$k]?:0;
            $amount_flag = $condition_free_detail_list_arr['amount_flag'][$k]?:0;
            $condition_free_detail_list[] = [
                'address_infos' => $address_infos,
                'min_piece' => $min_piece,
                'min_weight' => $min_weight,
                'min_amount' => $min_amount,
                'valuation_flag' => $valuation_flag,
                'amount_flag' => $amount_flag,
                'region' => $adr
            ];
        }
        //计费方法
        $freight_calc_method_list_arr = input('freight_calc_method_list/a');
        $freight_calc_method_list = [];
        foreach($freight_calc_method_list_arr['address_infos'] as $k=>$adr){
            //组装地址数据
            $address_infos = [];
            $adr_str_arr = explode(';',$adr);
            foreach($adr_str_arr as $adr_arr){
                if(empty($adr_arr)){
                    continue;
                }
                $province_arr = explode('[',$adr_arr);
                $province_name = $province_arr[0];
                preg_match_all('/\[([^\]]+)\]/', $adr_arr, $matches);
                $area_str = $matches[1][0];
                if($area_str=='全部地区'){
                    $address_infos[] = ['province_name'=>$province_name];
                }else{
                    $city_str_arr = explode(',',$area_str);
                    foreach($city_str_arr as $city_str){
                        $city_arr = explode('|',$city_str);
                        $city_name = $city_arr[0];
                        $county_str = $city_arr[1];
                        if($county_str=='全部县区'){
                            $address_infos[] = ['province_name'=>$province_name,'city_name'=>$city_name];
                        }else{
                            $county_arr = explode('-',$county_str);
                            foreach($county_arr as $county_name){
                                $address_infos[] = ['province_name'=>$province_name,'city_name'=>$city_name,'county_name'=>$county_name];
                            }
                        }
                    }
                }
            }
            $delivery_id = $freight_calc_method_list_arr['delivery_id'][$k]?:'';
            $first_val_amount = $freight_calc_method_list_arr['first_val_amount'][$k]?:0;
            $first_price = $freight_calc_method_list_arr['first_price'][$k]?:0;
            $second_val_amount = $freight_calc_method_list_arr['second_val_amount'][$k]?:0;
            $second_price = $freight_calc_method_list_arr['second_price'][$k]?:0;
            $is_default = $freight_calc_method_list_arr['is_default'][$k]?true:false;
            $freight_calc_method_list[] = [
                'address_infos' => $address_infos,
                'is_default' => $is_default,
                'delivery_id' => $delivery_id,
                'first_val_amount' => floatval($first_val_amount),
                'first_price' => floatval($first_price),
                'second_val_amount' => floatval($second_val_amount),
                'second_price' => floatval($second_price),
                'region' => $adr
            ];
        }

        //不发货地区
        $not_send_area = $info['not_send_area'];
        //组装地址数据
        $not_send_area_new = [];
        $adr_str_arr = explode(';',$not_send_area);
        foreach($adr_str_arr as $adr_arr){
            if(empty($adr_arr)){
                continue;
            }
            $province_arr = explode('[',$adr_arr);
            $province_name = $province_arr[0];
            preg_match_all('/\[([^\]]+)\]/', $adr_arr, $matches);
            $area_str = $matches[1][0];
            if($area_str=='全部地区'){
                $not_send_area_new[] = ['province_name'=>$province_name];
            }else{
                $city_str_arr = explode(',',$area_str);
                foreach($city_str_arr as $city_str){
                    $city_arr = explode('|',$city_str);
                    $city_name = $city_arr[0];
                    $county_str = $city_arr[1];
                    if($county_str=='全部县区'){
                        $not_send_area_new[] = ['province_name'=>$province_name,'city_name'=>$city_name];
                    }else{
                        $county_arr = explode('-',$county_str);
                        foreach($county_arr as $county_name){
                            $not_send_area_new[] = ['province_name'=>$province_name,'city_name'=>$city_name,'county_name'=>$county_name];
                        }
                    }
                }
            }
        }
        $info['not_send_area'] = ['address_infos'=>$not_send_area_new];
        $info['address_info'] = $addressinfo;
        //计费方法金额转元
        $freight_calc_method_list_new = $freight_calc_method_list;
        foreach($freight_calc_method_list_new as $calc_k=>$calc_v){
            $freight_calc_method_list_new[$calc_k]['first_price'] = (int)bcmul($calc_v['first_price'],100,0);
            $freight_calc_method_list_new[$calc_k]['second_price'] = (int)bcmul($calc_v['second_price'],100,0);
        }
        //条件包邮金额转元
        $condition_free_detail_list_new = $condition_free_detail_list;
        foreach($condition_free_detail_list_new as $free_k=>$free_v){
            $condition_free_detail_list_new[$free_k]['min_amount'] = (int)bcmul($free_v['min_amount'],100,0);
        }

        $info['all_condition_free_detail'] = ['condition_free_detail'=>$condition_free_detail_list_new];
        $info['all_freight_calc_method'] = ['freight_calc_method_list'=>$freight_calc_method_list_new];
        //dump($info);exit;
        //传入微信
        if(empty($info['id']) || empty($info['template_id'])){
            $res = \app\common\WxChannels::addfreighttemplate(aid,bid, $this->appid, $info);
        }else{
            $res = \app\common\WxChannels::updatefreighttemplate(aid,bid, $this->appid, $info);
        }
        if(!$res['status']){
            return json($res);
        }
        $info['aid'] = aid;
        $info['appid'] = $this->appid;
        if(empty($info['template_id']) &&  $res['data']){
            $info['template_id'] = $res['data']?:'';
        }

        $info['address_info'] = jsonEncode($addressinfo);
        $info['all_condition_free_detail'] = jsonEncode(['condition_free_detail_list'=>$condition_free_detail_list]);
        $info['all_freight_calc_method'] = jsonEncode(['freight_calc_method_list'=>$freight_calc_method_list]);
        $info['not_send_area'] = jsonEncode($info['not_send_area'] );
        if($info['id']){
            Db::name('channels_freight')->where('aid',aid)->where('id',$info['id'])->where('bid',bid)->update($info);
            \app\common\System::plog('修改配送方式'.$info['id']);
        }else{
            $data['aid'] = aid;
            $data['bid'] = bid;
            $data['create_time'] = time();
            $id = Db::name('channels_freight')->insertGetId($info);
            \app\common\System::plog('添加配送方式'.$id);
        }
        return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
    }
    //删除
    public function del(){
        $ids = input('post.ids/a');
        if(!$ids) $ids = array(input('post.id/d'));
        $where = [];
        $where[] = ['aid','=',aid];
        $where[] = ['bid','=',bid];
        $where[] = ['id','in',$ids];
        Db::startTrans();
        Db::name('channels_freight')->where($where)->delete();
        Db::commit();
        \app\common\System::plog('视频号小店运费模板删除'.implode(',',$ids));
        return json(['status'=>1,'msg'=>'删除成功']);
    }

    //运费模板中的地址数据转换为适用前台的数据格式
    public function getregion($address_infos){
        if(empty($address_infos)){
            return '';
        }
        $region = '';
        $province_arr = [];
        foreach($address_infos as $v){
            $province_name = $v['province_name'];
            if(in_array($province_name,$province_arr)){
                continue;
            }
            if($v['province_name'] && empty($v['city_name'])){
                $region .= $province_name.'[全部地区];';
            }else{
                $citys = [];
                $city_arr = [];
                foreach($address_infos as $v2){
                    if($v2['province_name']!=$province_name){
                        continue;
                    }
                    $city_name = $v2['city_name'];
                    if(in_array($city_name,$city_arr)){
                        continue;
                    }
                    if(!empty($v2['county_name'])){
                        $counts = [];
                        foreach($address_infos as $v3){
                            if(empty($v3['county_name']) || ($v3['province_name']!=$province_name || $v3['city_name']!=$city_name)){
                                continue;
                            }
                            $counts[] = $v3['county_name'];
                        }
                        $city_name .= '|'.implode('-',$counts);
                    }else{
                        $city_name .= '|'.'全部县区';
                    }
                    $citys[] = $city_name;
                    $city_arr[] = $v2['city_name'];
                }
                $region .= $province_name.'['.implode(',',$citys).'];';
            }
            $province_arr[] = $province_name;
        }
        return $region;
    }
    //更新区域数据json文件
    public function update_area_json(){
        set_time_limit(0);
        $res =  \app\common\WxChannels::getarea(aid,bid, $this->appid, 0);
        $next_level_addrs = $res['next_level_addrs'];
        $data = [];
        //获取省
        foreach($next_level_addrs as $province){
            $arr = [];
            $arr['name'] = $province['name'];
            //获取市
            $res_childs = \app\common\WxChannels::getarea(aid,bid, $this->appid, $province['code']);
            $cityList = $res_childs['next_level_addrs'];
            $city_arr = [];
            foreach($cityList as $city){
                $res_childs = \app\common\WxChannels::getarea(aid,bid, $this->appid, $city['code']);
                $areaList = $res_childs['next_level_addrs'];
                $count_arr = array_column($areaList,'name');
                $city_arr[] = ['name'=>$city['name'],'areaList'=>$count_arr];
            }
            $arr['cityList'] = $city_arr;
            $data[] = $arr;
        }
        file_put_contents(ROOT_PATH.'static/admin/js/channels_area.json',jsonEncode($data));
    }


    //选择配送模板
    public function choosefreight(){
        return View::fetch();
    }

    public function getfreight(){
        $template_id = input('template_id');
        $freight = Db::name('channels_freight')->where('aid',aid)->where('appid',$this->appid)->where('template_id',$template_id)->where('bid',bid)->find();
        $freight['valuation_type'] = \app\common\WxChannels::valuation_type[$freight['valuation_type']];
        return json(['status'=>1,'data'=>$freight]);
    }
}
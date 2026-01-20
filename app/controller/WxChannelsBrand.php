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
//视频号小店 品牌
namespace app\controller;

use app\common\System;
use think\facade\View;
use think\facade\Db;

class WxChannelsBrand extends Common
{
    public function initialize()
    {
        parent::initialize();
        if (!getcustom('wx_channels_business') && bid > 0) showmsg('无访问权限');
        $this->appid = \app\common\WxChannels::defaultApp(aid,bid);
    }

    public function index()
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
            $where = array();
            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['record.createtime','>=',strtotime($ctime[0])];
                $where[] = ['record.createtime','<',strtotime($ctime[1]) + 86400];
            }
            if(input('name')){
                $where[] = ['ch_name|en_name','like','%'.input('name').'%'];
            }
            if($type==0){
                $where[] = ['aid','=',aid];
                $where[] = ['bid','=',bid];
                $where[] = ['appid','=',$this->appid];
                $count = 0 + Db::name('channels_brand')->where($where)->count();
                $data = Db::name('channels_brand')->where($where)->page($page,$limit)->order($order)->select()->toArray();
            }else{
                $count = 0 + Db::name('channels_brand_basic')->where($where)->count();
                $data = Db::name('channels_brand_basic')->where($where)->page($page,$limit)->order($order)->select()->toArray();
            }

            foreach ($data as $k=>$v) {
                $data[$k]['status_name'] = $brand_status_arr[$v['status']]??'审核中';
            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $count, 'data' => $data?:[]]);
        }

        View::assign('brand_status_arr',$brand_status_arr);
        return View::fetch('index', ['type' => $type]);
    }

    public function asyncAllBrand()
    {
        Db::startTrans();
        try {
            $next_key = input('next_key');
            $type = input('type');
            if($type==1){
                //平台品牌库列表
                if(!$next_key){
                    $next_key = Db::name('channels_brand_basic')->order('id desc')->value('next_key');
                }
                $res = \app\common\WxChannels::asyncBrandAll(aid,bid, $this->appid, $next_key);
                if($res['status'] == 0){
                    return json($res);
                }
                $exit_brand_ids = Db::name('channels_brand_basic')->column('brand_id');
                $data = $res['data'];
                foreach ($data as $brand) {
                    $insert_data = [
                        "brand_id" => $brand['brand_id'],
                        "ch_name" => removeEmoj($brand['ch_name']),
                        "en_name" => $brand['en_name'],
                        "next_key" => $next_key?:0,
                    ];
                    if(in_array($brand['brand_id'],$exit_brand_ids)){
                        Db::name('channels_brand_basic')->where('brand_id',$brand['brand_id'])->update($insert_data);
                    }else{
                        Db::name('channels_brand_basic')->insert($insert_data);
                    }
                    $exit_brand_ids[] = $brand['brand_id'];
                }
            }else{
                //生效中的品牌
                $res = \app\common\WxChannels::asyncBrandValid(aid,bid, $this->appid, $next_key);
                if($res['status'] == 0){
                    return json($res);
                }
                $exit_brand_ids = Db::name('channels_brand')->where('aid',aid)->where('appid',$this->appid)->where('bid',bid)->column('id','brand_id');
                $data = $res['data'];
                foreach ($data as $brand) {
                    $insert_data = [
                        "aid" => aid,
                        "bid" => bid,
                        "appid" => $this->appid,
                        "brand_id" => $brand['brand_id'],
                        "ch_name" => removeEmoj($brand['ch_name']),
                        "en_name" => $brand['en_name'],
                    ];
                    if(!empty($exit_brand_ids[$brand['brand_id']])){
                        Db::name('channels_brand')->where('id',$exit_brand_ids[$brand['brand_id']])->update($insert_data);
                    }else{
                        Db::name('channels_brand')->insert($insert_data);
                    }
                    $exit_brand_ids[] = $brand['brand_id'];
                }
            }
            Db::commit();
            return json(['status' => 1, 'msg' => '同步成功','next_key'=>$res['next_key'],'continue_flag'=>$res['continue_flag']]);
        } catch (\Throwable $t) {
            Db::rollback();
            return json(['status' => 0, 'msg' => $t->getMessage()]);
        }
    }

    //新增，修改品牌资质
    public function edit(){
        $brand_id = input('brand_id');
        $info = Db::name('channels_brand')->where('brand_id',$brand_id)->find();

        View::assign('info',$info);
        //资质类型
        $file_type = \app\common\WxChannels::brand_file_type;
        View::assign('file_type',$file_type);
        return View::fetch();
    }
    public function save(){
        $info = input('info');
        if(empty($info['brand_id'])){
            return json(['status'=>0,'msg'=>'请选择品牌']);
        }
        Db::startTrans();
        $info['aid'] = aid;
        $info['bid'] = bid;
        $info['appid'] = $this->appid;
        $brand_info = Db::name('channels_brand_basic')->where('brand_id',$info['brand_id'])->find();
        $info['ch_name'] = $brand_info['ch_name'];
        $info['en_name'] = $brand_info['en_name'];
        $info['start_time'] = strtotime($info['start_time'])?:0;
        $info['end_time'] = strtotime($info['end_time'])?:0;
        $info['grant_start_time'] = strtotime($info['grant_start_time'])?:0;
        $info['grant_end_time'] = strtotime($info['grant_end_time'])?:0;
        $info['acceptance_time'] = strtotime($info['acceptance_time'])?:0;
        $info['createtime'] = time();
        $file_type = \app\common\WxChannels::brand_file_type;
        foreach($file_type as $field=>$field_name){
            $info[$field.'_ids'] = '';
            if(!empty($info[$field])){
                $field_ids = Db::name('admin_upload')->where('url','in',$info[$field])->column('channels_file_id');
                $info[$field.'_ids'] = implode('',$field_ids);
            }
        }
        $exit = Db::name('channels_brand')->where('brand_id',$info['brand_id'])->where('aid',aid)->where('appid',$this->appid)->where('bid',bid)->find();

        //组装微信数据
        $data = [];
        $data['brand_id'] = $info['brand_id'];
        $data['ch_name'] = $info['ch_name'];
        $data['en_name'] = $info['en_name'];
        $data['classification_no'] = $info['classification_no'];
        $data['trade_mark_symbol'] = (int)$info['trade_mark_symbol'];
        $register_details = [];
        $application_details = [];
        if($data['trade_mark_symbol']==1){
            $register_details = [
                'registrant' => $info['registrant'],
                'register_no' => $info['register_no'],
                'start_time' => $info['start_time'],
                'end_time' => $info['end_time'],
                'is_permanent' => $info['is_permanent']==1?true:false,
                'register_certifications' => $info['register_certifications_ids']?explode(',',$info['register_certifications_ids']):[],
                'renew_certifications' => $info['renew_certifications_ids']?explode(',',$info['renew_certifications_ids']):[],
            ];
        }else{
            $application_details = [
                'acceptance_time' => $info['acceptance_time'],
                'acceptance_certification' => $info['acceptance_certification_ids']?explode(',',$info['acceptance_certification_ids']):[],
                'acceptance_no' => $info['acceptance_no'],
            ];
        }
        $empty_obj = new \stdClass();
        $data['register_details'] = $register_details?$register_details:$empty_obj;
        $data['application_details'] = $application_details?$application_details:$empty_obj;
        $data['grant_type'] = (int)$info['grant_type'];
        $grant_details = [];
        if($data['grant_type']==2){
            $grant_details = [
                'grant_certifications' => explode(',',$info['grant_certifications_ids']),
                'grant_level' => (int)$info['grant_level'],
                'start_time' => $info['start_time'],
                'end_time' => $info['end_time'],
                'is_permanent' => $info['is_permanent']==1?true:false,
                'brand_owner_id_photos' => explode(',',$info['brand_owner_id_photos_ids']),
            ];
        }
        $data['grant_details'] = $grant_details?:$empty_obj;

        if($exit && $exit['audit_id']){
            $res = \app\common\WxChannels::updateBrand(aid,bid,$this->appid,$data);
        }else{
            $res = \app\common\WxChannels::applyBrand(aid,bid,$this->appid,$data);
        }

        if($res['status']){
            $info['audit_id'] = $res['data'];
        }else{
            return json($res);
        }
        if($exit){
            Db::name('channels_brand')->where('aid',aid)->where('id',$exit['id'])->where('bid',bid)->update($info);
        }else{
            Db::name('channels_brand')->insert($info);
        }
        \app\common\System::plog("修改视频号小店品牌：".$info['brand_id']);
        Db::commit();
        return json(['status'=>1,'msg'=>'提交成功，请等待审核']);
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
        $brandlist = Db::name('channels_brand')->where($where)->select();
        foreach($brandlist as $item){
            if($item['brand_id']){
                $res = \app\common\WxChannels::deleteBrand(aid,bid,$this->appid,$item['brand_id']);
                if(!$res['status']){
                    return json($res);
                }
                Db::name('channels_brand')->where('aid',aid)->where('id',$item['id'])->where('bid',bid)->delete();
            }
        }
        Db::commit();
        \app\common\System::plog('视频号小店品牌删除'.implode(',',$ids));
        return json(['status'=>1,'msg'=>'删除成功']);
    }

    public function choosebrand(){
        $type = input('type')?:0;
        View::assign('type',$type);
        return View::fetch();
    }
    public function getbrandinfo(){
        $id = input('brand_id');
        $info = Db::name('channels_brand_basic')->where('brand_id',$id)->find();
        return json(['status'=>1,'data'=>$info]);
    }
}
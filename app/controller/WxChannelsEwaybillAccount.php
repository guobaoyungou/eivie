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
//视频号小店 电子面单账户
namespace app\controller;

use app\common\System;
use think\facade\View;
use think\facade\Db;

class WxChannelsEwaybillAccount extends Common
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
        $status_arr = \app\common\WxChannels::ewaybill_account_status;
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
            if(input('delivery_id')){
                $where[] = ['delivery_id','=',input('delivery_id')];
            }
            if(input('?param.status') && input('param.status')!==''){
                $where[] = ['status','=',input('status')];
            }
            $list = Db::name("channels_ewaybill_account")
                ->where($where)
                ->order($order)
                ->paginate($page)
                ->toArray();
            $data = $list['data'];
            foreach($data as $k=>$v){
                $data[$k]['status_str'] = $status_arr[$v['status']];
            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $list['total'], 'data' => $data]);
        }
        View::assign('status_arr',$status_arr);
        return View::fetch();
    }
    //同步账户信息
    public function asyncAllAccount()
    {
        Db::startTrans();
        try {
            $input = input();
            $ctime = explode(' ~ ',$input['create_time_range']);
            $start_time = strtotime($ctime[0]);
            $end_time = strtotime($ctime[1]);
            $next_key = input('next_key');
            $params = [];
            $params['offset'] = $input['pagenum'];
            $params['limit'] = 20;
            $params['need_balance'] = true;
            //账户信息列表
            $res = \app\common\WxChannels::ewaybillAccount(aid,bid, $this->appid, $params);
            if($res['status'] == 0 ){
                return json($res);
            }
            $account_lists = $res['data'];
            foreach ($account_lists as $account) {
                //账户信息详情
                $data = [];
                $data['aid'] = aid;
                $data['bid'] = bid;
                $data['appid'] = $this->appid;
                $data['site_name'] = $account['site_info']['site_name'];
                $data['site_code'] = $account['site_info']['site_code'];
                $data['delivery_id'] = $account['delivery_id'];
                $data['acct_type'] = $account['acct_type'];
                $data['company_type'] = $account['company_type'];
                $data['shop_id'] = $account['shop_id'];
                $data['acct_id'] = $account['acct_id'];
                $data['status'] = $account['status'];
                $data['available'] = $account['available'];
                $data['allocated'] = $account['allocated'];
                $data['recycled'] = $account['recycled'];
                $data['cancel'] = $account['cancel'];
                $data['monthly_card'] = $account['monthly_card'];
                $data['site_info'] = json_encode($account['site_info']);
                $data['sender_address'] = json_encode($account['sender_address']);
                $exit = Db::name('channels_ewaybill_account')->where('acct_id',$account['acct_id'])->find();
                if($exit){
                    Db::name('channels_ewaybill_account')->where('id',$exit['id'])->update($data);
                }else{
                    Db::name('channels_ewaybill_account')->insert($data);
                }

            }
            Db::commit();
            return json(['status' => 1, 'msg' => '同步成功','next_key'=>$res['next_key']]);
        } catch (\Throwable $t) {
            Db::rollback();
            return json(['status' => 0, 'msg' => $t->getMessage()]);
        }
    }
}
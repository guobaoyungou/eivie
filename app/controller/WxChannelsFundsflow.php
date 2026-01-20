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
//视频号小店 资金流水
namespace app\controller;

use app\common\System;
use think\facade\View;
use think\facade\Db;

class WxChannelsFundsflow extends Common
{
    public function initialize()
    {
        parent::initialize();
        if (!getcustom('wx_channels_business') && bid > 0) showmsg('无访问权限');
        $this->appid = \app\common\WxChannels::defaultApp(aid,bid);
        $childmenu = [
            [
                'path' => 'WxChannelsFundsflow/index',
                'name' => '资金流水'
            ],
            [
                'path' => 'WxChannelsFundsflow/withdraw_log',
                'name' => '提现记录'
            ],
        ];
        View::assign('childmenu',$childmenu);
        $thispath = request()->controller().'/'.request()->action();
        View::assign('thispath',$thispath);
    }
    public function index()
    {
        $funds_type_arr = \app\common\WxChannels::funds_type;
        $flow_type_arr = \app\common\WxChannels::flow_type;
        if (request()->isAjax()) {
            if(input('param.field') && input('param.order')){
                $order = input('param.field').' '.input('param.order');
            }else{
                $order = 'id desc';
            }
            $page = [
                "list_rows" => input('limit', 20),
                "page" => input('page', 1),
            ];
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['appid','=',$this->appid];

            if(input('param.funds_type')){
                $where[] = ['funds_type','=',input('param.funds_type')];
            }
            if(input('param.flow_type')){
                $where[] = ['flow_type','=',input('param.flow_type')];
            }
            if(input('param.id')){
                $where[] = ['id','=',input('id')];
            }
            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['bookkeeping_time','>=',strtotime($ctime[0])];
                $where[] = ['bookkeeping_time','<',strtotime($ctime[1]) + 86400];
            }
            $list = Db::name("channels_fundsflow")
                ->where($where)
                ->order($order)
                ->paginate($page)
                ->toArray();
            $data = $list['data'];
            foreach($data as $k=>$v){
                $data[$k]['flow_type_str'] = $flow_type_arr[$v['flow_type']];
                $data[$k]['funds_type_str'] = $funds_type_arr[$v['funds_type']];
            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $list['total'], 'data' => $data]);
        }
        view::assign('funds_type_arr',$funds_type_arr);
        view::assign('flow_type_arr',$flow_type_arr);
        return View::fetch();
    }
    //同步资金流水
    public function asyncAllFlow()
    {
        Db::startTrans();
        try {
            $input = input();
            $ctime = explode(' ~ ',$input['create_time_range']);
            $start_time = strtotime($ctime[0]);
            $end_time = strtotime($ctime[1]);
            $next_key = input('next_key');
            $params = [];
            $params['create_time_range'] = [
                'start_time' => $start_time,
                'end_time' => $end_time,
            ];

            if($next_key){
                $params['next_key'] = $next_key;
            }
            $params['page_size'] = 20;

            //售后的单列表
            $res = \app\common\WxChannels::getFundsflowlist(aid,bid, $this->appid, $params);
            if($res['status'] == 0 ){
                return json($res);
            }
            $all_flow_ids = Db::name('channels_fundsflow')->where('aid',aid)->where('appid',$this->appid)->where('bid',bid)->column('flow_id');
            $flow_ids = $res['data'];
            foreach ($flow_ids as $flow_id) {
                //售后单详情
                $res2 = \app\common\WxChannels::getFundsflowdetail(aid,bid, $this->appid, $flow_id);
                if($res2['status'] == 0 ){
                    return json($res2);
                }
                $funds_flow = $res2['data'];
                $data = [
                    "aid" => aid,
                    "bid" => bid,
                    "appid" => $this->appid,
                    "flow_id" => $funds_flow['flow_id'],
                    "funds_type" => $funds_flow['funds_type'],
                    "flow_type" => $funds_flow['flow_type'],
                    "amount" => $funds_flow['amount']/100,
                    "balance" => $funds_flow['balance']/100,
                    "related_info_list" => json_encode($funds_flow['related_info_list']),
                     "bookkeeping_time" => strtotime($funds_flow['bookkeeping_time']),
                    "remark" => $funds_flow['remark']
                ];
                if(in_array($flow_id,$all_flow_ids)){
                    Db::name('channels_fundsflow')->where('flow_id',$flow_id)->update($data);
                }else{
                    Db::name('channels_fundsflow')->insert($data);
                }
            }

            Db::commit();
            return json(['status' => 1, 'msg' => '同步成功','next_key'=>$res['next_key'],'has_more'=>$res['has_more']]);
        } catch (\Throwable $t) {
            Db::rollback();
            return json(['status' => 0, 'msg' => $t->getMessage()]);
        }
    }

    //提现记录
    public function withdraw_log()
    {
        $status_arr = \app\common\WxChannels::withdraw_status;
        $flow_type_arr = \app\common\WxChannels::flow_type;
        if (request()->isAjax()) {
            if(input('param.field') && input('param.order')){
                $order = input('param.field').' '.input('param.order');
            }else{
                $order = 'id desc';
            }
            $page = [
                "list_rows" => input('limit', 20),
                "page" => input('page', 1),
            ];
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['bid','=',bid];
            $where[] = ['appid','=',$this->appid];
            if(input('?param.status') && input('param.status')!==''){
                $where[] = ['status','=',input('status')];
            }
            if(input('param.id')){
                $where[] = ['id','=',input('id')];
            }
            if(input('param.ctime') ){
                $ctime = explode(' ~ ',input('param.ctime'));
                $where[] = ['create_time','>=',strtotime($ctime[0])];
                $where[] = ['create_time','<',strtotime($ctime[1]) + 86400];
            }
            $list = Db::name("channels_withdrawlog")
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
    //同步提现记录
    public function asyncAllWtithdraw()
    {
        Db::startTrans();
        try {
            $input = input();
            $ctime = explode(' ~ ',$input['create_time_range']);
            $start_time = strtotime($ctime[0]);
            $end_time = strtotime($ctime[1]);
            $params = [];
            if($start_time){
                $params['create_time_range'] = [
                    'start_time' => (int)$start_time,
                    'end_time' => (int)$end_time,
                ];
            }
            $params['page_num'] = (int)$input['pagenum'];
            $params['page_size'] = 20;

            //提现记录
            $res = \app\common\WxChannels::getWithdrawlist(aid,bid, $this->appid, $params);
            if($res['status'] == 0 ){
                return json($res);
            }
            $all_withdraw_ids = Db::name('channels_withdrawlog')->where('aid',aid)->where('appid',$this->appid)->where('bid',bid)->column('withdraw_id');
            $withdraw_ids = $res['data'];
            foreach ($withdraw_ids as $withdraw_id) {
                //提现详情
                $res = \app\common\WxChannels::getWithdrawdetail(aid,bid, $this->appid, $withdraw_id);
                if($res['status'] == 0 ){
                    return json($res);
                }
                $funds_flow = $res['data'];
                $data = [
                    "aid" => aid,
                    "bid" => bid,
                    "appid" => $this->appid,
                    "withdraw_id" => $withdraw_id,
                    "amount" => $funds_flow['amount'],
                    "create_time" => $funds_flow['create_time'],
                    "update_time" => $funds_flow['update_time'],
                    "reason" => $funds_flow['reason'],
                    "remark" => $funds_flow['remark'],
                    "bank_memo" => $funds_flow['bank_memo'],
                    "bank_name" => $funds_flow['bank_name'],
                    "bank_num" => $funds_flow['bank_num'],
                    "status" => $funds_flow['status'],
                ];
                if(in_array($withdraw_id,$all_withdraw_ids)){
                    Db::name('channels_withdrawlog')->insert($data);
                }else{
                    Db::name('channels_withdrawlog')->where('withdraw_id',$withdraw_id)->update($data);
                }
            }

            Db::commit();
            return json(['status' => 1, 'msg' => '同步成功','next_key'=>$res['next_key'],'has_more'=>$res['has_more']]);
        } catch (\Throwable $t) {
            Db::rollback();
            return json(['status' => 0, 'msg' => $t->getMessage()]);
        }
    }
    //提现
    public function withdraw(){
        $res = \app\common\WxChannels::getbalance(aid,bid,$this->appid);
        $available_amount = $res['data']['available_amount']??0;
        if(request()->isPost()){
            $info = input('info');
            if($info['amount']>$available_amount){
                return json(['status'=>0,'msg'=>'请输入正确的提现金额']);
            }
            $params = [
                'amount' => (int)bcmul($info['amount'],100,0),
                'remark' => $info['remark'],
                'bank_memo' => $info['bank_memo']
            ];
            $res = \app\common\WxChannels::withdraw(aid,bid,$this->appid,$params);
            if(!$res['status']){
                return json($res);
            }
            $bankacct_info = Db::name('channels_bankacct')->where('aid',aid)->where('appid',$this->appid)->where('bid',bid)->find();
            $data = [];
            $data['aid'] = aid;
            $data['bid'] = bid;
            $data['appid'] = $this->appid;
            $data['withdraw_id'] = $res['data'];
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['bank_name'] = $bankacct_info['bank_name'];
            $data['bank_num'] = $bankacct_info['account_number'];
            $data['status'] = 'CREATE_SUCCESS';
            $data['amount'] = $info['amount'];
            $data['remark'] = $info['remark'];
            $data['bank_memo'] = $info['bank_memo'];
            $rs = Db::name('channels_withdrawlog')->insert($data);
            \app\common\System::plog("视频号小店提现");
            return json(['status'=>1,'msg'=>'操作成功']);
        }else{
            View::assign('available_amount',$available_amount);
            return View::fetch();
        }
    }
}
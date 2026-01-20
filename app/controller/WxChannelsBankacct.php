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
//视频号小店 结算账户
namespace app\controller;

use app\common\System;
use think\facade\View;
use think\facade\Db;

class WxChannelsBankacct extends Common
{
    public function initialize()
    {
        parent::initialize();
        if (!getcustom('wx_channels_business') && bid > 0) showmsg('无访问权限');
        $this->appid = \app\common\WxChannels::defaultApp(aid,bid);
    }
    public function index(){
        //获取账户余额
        $res = \app\common\WxChannels::getbalance(aid,bid,$this->appid);
        $available_amount = $res['data']['available_amount']??0;
        $pending_amount = $res['data']['pending_amount']??0;
        $total_amount = bcadd($available_amount,$pending_amount,2);

        $lastDayStart = strtotime(date('Y-m-d',time()-86400));
        $lastDayEnd = $lastDayStart + 86400;
        $thisMonthStart = strtotime(date('Y-m-1'));

        //结算账户
        $bankacct = Db::name('channels_bankacct')->where('aid',aid)->where('appid',$this->appid)->where('bid',bid)->find();

        //订单金额
        $order_map = [];
        $order_map[] = ['aid','=',aid];
        $order_map[] = ['bid','=',bid];
        $order_map[] = ['appid','=',$this->appid];
        $order_map[] = ['status','in',[20,21,30,100]];
        $total_order_price = 0 + Db::name('channels_order')->where($order_map)->sum('order_price');
        $today_order_price = 0 + Db::name('channels_order')->where($order_map)->where('pay_time','>=',$lastDayEnd)->sum('order_price');
        $yesterday_order_price = 0 + Db::name('channels_order')->where($order_map)->where('pay_time','>=',$lastDayStart)
                ->where('pay_time','<',$lastDayEnd)
                ->sum('order_price');
        $month_order_price = 0 + Db::name('channels_order')->where($order_map)->where('pay_time','>=',$thisMonthStart)
                ->sum('order_price');

        //订单数量
        $order_map = [];
        $order_map[] = ['aid','=',aid];
        $order_map[] = ['bid','=',bid];
        $order_map[] = ['appid','=',$this->appid];
//        $order_map[] = ['status','in',[20,21,30,100]];
        $total_order_count = 0 + Db::name('channels_order')->where($order_map)->count();
        $today_order_count = 0 + Db::name('channels_order')->where($order_map)->where('create_time','>=',$lastDayEnd)->count();
        $yesterday_order_count = 0 + Db::name('channels_order')->where($order_map)->where('create_time','>=',$lastDayStart)
                ->where('pay_time','<',$lastDayEnd)
                ->count();
        $month_order_count = 0 + Db::name('channels_order')->where($order_map)->where('create_time','>=',$thisMonthStart)
                ->count();

        //售后订单
        $order_map = [];
        $order_map[] = ['aid','=',aid];
        $order_map[] = ['bid','=',bid];
        $order_map[] = ['appid','=',$this->appid];
//        $order_map[] = ['status','in',[20,21,30,100]];
        $total_aftersale_count = 0 + Db::name('channels_after_sales')->where($order_map)->count();
        $today_aftersale_count = 0 + Db::name('channels_after_sales')->where($order_map)->where('create_time','>=',$lastDayEnd)->count();
        $noaudit_aftersale_count = 0 + Db::name('channels_after_sales')->where($order_map)
                ->where('status','in',['MERCHANT_PROCESSING','MERCHANT_WAIT_RECEIPT'])
                ->count();
        $month_aftersale_count = 0 + Db::name('channels_after_sales')->where($order_map)->where('create_time','>=',$thisMonthStart)
                ->count();

        //商品数量
        $product_map = [];
        $product_map[] = ['aid','=',aid];
        $product_map[] = ['bid','=',bid];
        $product_map[] = ['appid','=',$this->appid];
        $productCount = 0 + Db::name('channels_product')->where($product_map)->count();
        $product0Count = 0 + Db::name('channels_product')->where($product_map)->where('status','<>',5)->count();
        $product1Count = 0 + Db::name('channels_product')->where($product_map)->where('status',5)->count();
        //echart数据
        $monthEnd = strtotime(date('Y-m-d',time()-86400));
        $monthStart = $monthEnd - 86400 * 29;
        $order_map = [];
        $order_map[] = ['aid','=',aid];
        $order_map[] = ['bid','=',bid];
        $order_map[] = ['appid','=',$this->appid];
        $order_map[] = ['status','in',[20,21,30,100]];
        $dataArr = [];
        $dateArr = [];
        for($i=0;$i<30;$i++){
            $thisDayStart = $monthStart + $i * 86400;
            $thisDayEnd = $monthStart + ($i+1) * 86400;
            $dateArr[] = date('m-d',$thisDayStart);
            $dataArr[] = 0 + Db::name('channels_order')->where($order_map)
                    ->where('pay_time','>=',$thisDayStart)
                    ->where('pay_time','<',$thisDayEnd)
                    ->sum('order_price');
        }

        $data = [
            //账户余额
            'available_amount' => bcdiv($available_amount,100,2),
            'pending_amount' => bcdiv($pending_amount,100,2),
            'total_amount' => bcdiv($total_amount,100,2),
            //订单金额
            'total_order_price' => $total_order_price?:0,
            'today_order_price' => $today_order_price?:0,
            'yesterday_order_price' => $yesterday_order_price?:0,
            'month_order_price' => $month_order_price?:0,
            //订单数量
            'total_order_count' => $total_order_count?:0,
            'today_order_count' => $today_order_count?:0,
            'yesterday_order_count' => $yesterday_order_count?:0,
            'month_order_count' => $month_order_count?:0,

            //售后数量
            'total_aftersale_count' => $total_aftersale_count,
            'today_aftersale_count' => $today_aftersale_count,
            'noaudit_aftersale_count' => $noaudit_aftersale_count,
            'month_aftersale_count' => $month_aftersale_count,
            //商品数量
            'productCount' => $productCount,
            'product0Count' => $product0Count,
            'product1Count' => $product1Count,
            //结算账户
            'bankacct' => $bankacct,
            //echart数据
            'dataArr' => $dataArr,
            'dateArr' => $dateArr,
        ];
        View::assign($data);
        return View::fetch();
    }
    public function set()
    {
        $info = Db::name('channels_bankacct')->where('aid',aid)->where('appid',$this->appid)->where('bid',bid)->find();
        if(!$info){
            //获取结算账户
            $res = \app\common\WxChannels::getBankAcct(aid,bid,$this->appid);
            if($res['status']){
                $account_info = $res['data'];
                $info = [];
                $info['aid'] = aid;
                $info['bid'] = bid;
                $info['appid'] = $this->appid;
                $info['bank_account_type'] = $account_info['bank_account_type'];
                $info['account_bank'] = $account_info['account_bank'];
                $info['bank_address_code'] = $account_info['bank_address_code'];
                $info['bank_branch_id'] = $account_info['bank_branch_id'];
                $info['bank_name'] = $account_info['bank_name'];
                $info['account_number'] = $account_info['account_number'];
                $info['account_name'] = $account_info['account_name'];
                $id = Db::name('channels_bankacct')->insertGetId($info);
                $info['id'] = $id;
            }

        }
        if (request()->isAjax()) {
            $data = input('info');
            $id = input('id');
            $params = [
                'bank_account_type' => $data['bank_account_type'],
                'account_bank' => $data['account_bank'],
                'bank_address_code' => $data['bank_address_code'],
                'bank_branch_id' => $data['bank_branch_id'],
                'bank_name' => $data['bank_name'],
                'account_number' => $data['account_number'],
                'account_bank4show' => '',
                'account_name' => $data['account_name'],
            ];
            $res =  \app\common\WxChannels::setBankAcct(aid,bid, $this->appid,$params);
            if(!$res['status']){
                return json($res);
            }
            if($id){
                Db::name('channels_bankacct')->where('id',$id)->update($data);
            }else{
                $data['aid'] = aid;
                $data['bid'] = bid;
                $data['appid'] = $this->appid;
                Db::name('channels_bankacct')->insert($data);
            }
            return json(['status'=>1,'msg'=>'操作成功！']);
        }
        View::assign('info',$info);
        return View::fetch();
    }
    //银行省市编码
    public function bankarea()
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
            if(input('name')){
                $where[] = ['name','like','%'.input('name').'%'];
            }
            if(input('level')){
                $where[] = ['level','=',input('level')];
            }
            $count = 0 + Db::name('channels_bankarea')->where($where)->count();
            $data = Db::name('channels_bankarea')->where($where)->page($page,$limit)->order($order)->select()->toArray();
            foreach ($data as $k=>$v) {

            }
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $count, 'data' => $data?:[]]);
        }
        return View::fetch();
    }
    //同步区域
    public function asyncArea(){
        set_time_limit(0);
        Db::startTrans();
        $addr_code = input('addr_code');
        if(!$addr_code){
            Db::execute('truncate table ddwx_channels_bankarea');
            //未传区域的先清空整个表，更新省份，再挨个更新市
            $area_arr = Db::name('channels_bankarea')->where('level',1)->select()->toArray();
            if(!$area_arr){
                $res =  \app\common\WxChannels::getBankProvince(aid,bid, $this->appid);
                if(!$res['status']){
                    return json($res);
                }
                $area_arr = $res['data'];
                foreach($area_arr as $area){
                    $data = [
                        'name' => $area['province_name'],
                        'code' => $area['province_code'],
                        'level' => 1,
                        'parent_code' => ''
                    ];
                    Db::name('channels_bankarea')->insert($data);
                }
            }
            $addr_info = Db::name('channels_bankarea')->where('id','>',0)->order('id asc')->find();
        }else{
            $addr_info = Db::name('channels_bankarea')->where('code','=',$addr_code)->find();
        }
        $next_info = Db::name('channels_bankarea')->where('id','>',$addr_info['id'])->where('level',1)->order('id asc')->find();
        $res =  \app\common\WxChannels::getBankCity(aid,bid, $this->appid, $addr_info['code']);
        if(!$res['status']){
            return json($res);
        }
        $area_arr = $res['data'];
        $all_area_code = Db::name('channels_bankarea')->where('1=1')->column('code');
        foreach($area_arr as $area){
            $data = [
                'name' => $area['city_name'],
                'code' => $area['city_code'],
                'level' => 2,
                'parent_code' => $addr_info['code'],
                'bank_address_code' => $area['bank_address_code']
            ];
            if(!in_array($area['code'],$all_area_code)){
                Db::name('channels_bankarea')->insert($data);
            }else{
                Db::name('channels_bankarea')->where('code',$area['code'])->update($data);
            }
        }
        Db::commit();
        if(!$next_info){
            return json(['status'=>2,'msg'=>'全部同步成功','next_code'=>'','province_name'=>$addr_info['name']]);
        }
        return json(['status'=>1,'msg'=>'全部同步成功','next_code'=>$next_info['code'],'province_name'=>$addr_info['name']]);
    }
    public function getBankAreaInfo(){
        $id = input('id');
        $info = Db::name('channels_bankarea')->where('id',$id)->find();
        $parent_name = '';
        if($info['parent_code']){
            $parent_name = Db::name('channels_bankarea')->where('code',$info['parent_code'])->value('name');
        }
        $info['parent_name'] = $parent_name;
        return json(['status'=>1,'data'=>$info]);
    }
    //根据银行卡号获取银行信息
    public function getBankByAccount(){
        $account_number = input('account_number');
        $res =  \app\common\WxChannels::getBankByAccount(aid,bid, $this->appid,$account_number);
        if(!$res['status']){
            return json($res);
        }
        $bank_arr = $res['data'];
        return json(['status'=>1,'msg'=>'查询成功','data'=>$bank_arr[0]]);
    }
    //银行列表
    public function banklists(){

        $key_words = input('key_words');
        $bank_type = input('bank_type')?:2;
        $page = input('param.page');
        $limit = 1000;
        $offset = ($page-1)*$limit;
        if (request()->isAjax()) {
            $params = [
                "key_words" => $key_words,
                "bank_type" => (int)$bank_type,
                "offset" => $offset,
                "limit" => (int)$limit
            ];
            $res =  \app\common\WxChannels::getBankLists(aid,bid, $this->appid,$params);
            if(!$res['status']){
                return json($res);
            }

            $count = $res['total_count'];
            $data = $res['data'];
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $count, 'data' => $data?:[]]);
        }
        return View::fetch();
    }
    //同步银行列表
//    public function asyncBank(){
//        set_time_limit(0);
//        Db::startTrans();
//        $page = input('param.page');
//        $limit = input('param.limit');
//        $offset = ($page-1)*$limit;
//        $params = [
//            "offset" => $offset,
//            "limit" => (int)$limit
//        ];
//        $res =  \app\common\WxChannels::getBankLists(aid,bid, $this->appid,$params);
//        if(!$res['status']){
//            return json($res);
//        }
//        $bank_arr = $res['data'];
//        $all_bank_code = Db::name('channels_banklist')->where('1=1')->column('bank_code');
//        foreach($bank_arr as $bank){
//            $data = [
//                'account_bank' => $bank['account_bank'],
//                'bank_code' => $bank['bank_code'],
//                'bank_id' => $bank['bank_id'],
//                'bank_name' => $bank['bank_name'],
//                'bank_type' => $bank['bank_type'],
//                'need_branch' => $bank['need_branch']
//            ];
//            if(!in_array($bank['bank_code'],$all_bank_code)){
//                Db::name('channels_bankarea')->insert($data);
//            }else{
//                Db::name('channels_bankarea')->where('code',$bank['bank_code'])->update($data);
//            }
//        }
//        Db::commit();
//        if(!$bank_arr){
//            return json(['status'=>2,'msg'=>'全部同步成功']);
//        }
//        return json(['status'=>1,'msg'=>'全部同步成功']);
//    }

    //支行列表
    public function bankbranch()
    {
        $bank_code = input('bank_code');
        $city_code = input('city_code');
        $page = input('param.page');
        $limit = input('param.limit');
        $offset = ($page-1)*$limit;
        if (request()->isAjax()) {
            $params = [
                "bank_code" => $bank_code,
                "city_code" => $city_code,
                "offset" => $offset,
                "limit" => (int)$limit
            ];
            $res =  \app\common\WxChannels::getBankBranch(aid,bid, $this->appid,$params);
            if(!$res['status']){
                return json($res);
            }

            $count = $res['total_count'];
            $data = $res['data'];
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $count, 'data' => $data?:[]]);
        }
        return View::fetch();
    }

    //echart数据
    public function getdata(){
        $monthEnd = strtotime(date('Y-m-d',time()-86400));
        $monthStart = $monthEnd - 86400 * 29;
        $order_map = [];
        $order_map[] = ['aid','=',aid];
        $order_map[] = ['bid','=',bid];
        $order_map[] = ['appid','=',$this->appid];
        $order_map[] = ['status','in',[20,21,30,100]];

        $dataArr = array();
        $dateArr = array();
        for($i=0;$i<30;$i++){
            $thisDayStart = $monthStart + $i * 86400;
            $thisDayEnd = $monthStart + ($i+1) * 86400;
            $dateArr[] = date('m-d',$thisDayStart);
            if($_POST['type']==4){//订单金额
                $dataArr[] = 0 + Db::name('channels_order')->where($order_map)
                        ->where('pay_time','>=',$thisDayStart)
                        ->where('pay_time','<',$thisDayEnd)
                        ->sum('order_price');
            }elseif($_POST['type']==5){//订单数
                $dataArr[] = 0 + Db::name('channels_order')->where($order_map)
                        ->where('pay_time','>=',$thisDayStart)
                        ->where('pay_time','<',$thisDayEnd)
                        ->count();
            }
        }
        return json(['dateArr'=>$dateArr,'dataArr'=>$dataArr]);
    }
}
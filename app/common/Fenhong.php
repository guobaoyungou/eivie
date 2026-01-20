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

//股东分红 团队分红
namespace app\common;
use think\facade\Db;
use think\facade\Log;
class Fenhong
{
    //异步脚本plantask中调用
    public static function jiesuanAll(){
        //临时过度使用：客户更新时一次性结算所有订单，以后都在创建订单时就结算，走jiesuan_single方法
        $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
        foreach($syssetlist as $sysset){
            $aid = $sysset['aid'];
            self::jiesuan($aid);
        }
    }
    //临时过度使用：客户更新后用于在后台分页结算老订单，以后都在创建订单时就结算，走jiesuan_single方法
    public static function jiesuan($aid,$starttime=0,$endtime=0){
        if($endtime == 0) $endtime = time();
        $sysset = Db::name('admin_set')->where('aid',$aid)->find();
        $limit = 10;
        $where = [];
        $where[] = ['og.aid','=',$aid];
        $where[] = ['og.isfenhong','=',0];
        $fail_where = [
          ["og.aid", "=", $aid],
          ["og.isfenhong", "=", 0],
          ["og.iszj", "=", 2],
          ['og.isjiqiren', '=', 0]
        ];
        if($sysset['fhjiesuanbusiness'] == 1){ //多商户的商品是否参与分红
            
        }else{
            $where[] = ['og.bid','=','0'];
            $fail_where[] = ['og.bid', '=', 0];
        }
//        if($sysset['fhjiesuantime_type'] == 1) { //分红结算时间类型 0收货后，1付款后
            $where[] = ['og.status','in',[1,2,3]];
            $where[] = ['og.createtime','>=',$starttime];
            $where[] = ['og.createtime','<',$endtime];
            $fail_where[] = ['og.status', '=', 4];
            $fail_where[] = ['og.createtime','>=',$starttime];
            $fail_where[] = ['og.createtime','<',$endtime];
            $where2 = $where;
//        }else{
//            $where[] = ['og.status','=','3'];
//            $where2 = $where;
//            $where[] = ['og.endtime','>=',$starttime];
//            $where[] = ['og.endtime','<',$endtime];
//            $where2[] = ['og.collect_time','>=',$starttime];
//            $where2[] = ['og.collect_time','<',$endtime];
//            $fail_where[] = ['og.status', '=', 4];
//        }
        //排除退款订单
        $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('shop_order o','o.id=og.orderid')
            ->where($where)->where('refund_num',0)->limit($limit)->select()->toArray();
        if($oglist){
            if(getcustom('fenhong_manual',$aid)){
                $update = ['og.isfenhong'=>1];
            }else{
                $update = ['og.isfenhong'=>1,'og.isteamfenhong'=>1];
            }
            $ids = array_column($oglist,'id');
            Db::name('shop_order_goods')->alias('og')->where('id','in',$ids)->update($update);
        }
        if(getcustom('yuyue_fenhong',$aid)){
            $yyorderlist = Db::name('yuyue_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m','m.id=og.mid')
                ->where($where2)->limit($limit)->select()->toArray();
            foreach($yyorderlist as $k=>$v){
                $v['name'] = $v['proname'];
                $v['real_totalprice'] = $v['totalprice'];
                $v['cost_price'] = $v['cost_price'] ?? 0;
                $v['module'] = 'yuyue';
                $oglist[] = $v;
            }
            if($yyorderlist){
                Db::name('yuyue_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m','m.id=og.mid')->where($where2)->update(['og.isfenhong'=>1]);
            }
        }
        if(getcustom('scoreshop_fenhong',$aid)){
            $scoreshopoglist = Db::name('scoreshop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')
                ->join('scoreshop_order o','o.id=og.orderid')->where($where)->limit($limit)->select()->toArray();
            foreach($scoreshopoglist as $v){
                $v['real_totalprice'] = $v['totalmoney'];
                $v['module'] = 'scoreshop';
                $oglist[] = $v;
            }

            if($scoreshopoglist){
                Db::name('scoreshop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('scoreshop_order o','o.id=og.orderid')->where($where)->update(['og.isfenhong'=>1]);
            }
        }
        if(getcustom('luckycollage_fenhong',$aid)){
            $lcorderlist = Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')
                ->join('member m','m.id=og.mid')->where($where2)->whereRaw('og.buytype=1 or og.iszj=1')->where('og.isjiqiren',0)->limit($limit)->select()->toArray();
            foreach($lcorderlist as $k=>$v){
                $v['name'] = $v['proname'];
                $v['real_totalprice'] = $v['totalprice'];
                $v['cost_price'] = 0;
                $v['module'] = 'lucky_collage';
                $oglist[] = $v;
            }

            if($lcorderlist){
                Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m','m.id=og.mid')->where($where2)->whereRaw('og.buytype=1 or og.iszj=1')->where('og.isjiqiren',0)->update(['og.isfenhong'=>1]);
            }
        }

        //幸运拼团失败者分红
        if (getcustom('luckycollage_fail_commission',$aid)) {
            $lcorderlist = Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m', 'm.id=og.mid')
                ->where($fail_where)
                ->limit($limit)
                ->select()
                ->toArray();
            foreach ($lcorderlist as $k => $v) {
                $v['name'] = $v['proname'];
                $v['real_totalprice'] = $v['totalprice'];
                $v['cost_price'] = 0;
                $v['module'] = 'lucky_collage';
                $oglist[] = $v;
            }
            if ($lcorderlist) {
                Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m', 'm.id=og.mid')
                    ->where($fail_where)
                    ->update(['og.isfenhong' => 1]);
            }
        }

        if((getcustom('maidan_fenhong',$aid) || getcustom('tuanzhang_fenhong',$aid)) && !getcustom('maidan_fenhong_new',$aid)){
            //买单
            $maidan_orderlist = Db::name('maidan_order')
                ->alias('og')
                ->join('member m','m.id=og.mid')
                ->where('og.aid',$aid)
                ->where('og.isfenhong',0)
                ->where('og.status',1)
                ->where('og.paytime','>=',$starttime)
                ->where('og.paytime','<',$endtime);
            if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                $maidan_orderlist = $maidan_orderlist
                    ->where('og.bid',0);
            }
            $maidan_orderlist = $maidan_orderlist
                ->field('og.*,m.nickname,m.headimg')
                ->order('og.id desc')
                ->limit($limit)
                ->select()
                ->toArray();
            if($maidan_orderlist){
                foreach($maidan_orderlist as $mdk=>$mdv){
                    $mdv['proid']            = 0;
                    $mdv['name']            = $mdv['title'];
                    $mdv['real_totalprice'] = $mdv['paymoney'];
                    $mdv['cost_price']      = 0;
                    $mdv['num']             = 1;
                    $mdv['module']          = 'maidan';
                    $oglist[]               = $mdv;
                    Db::name('maidan_order')->where('id',$mdv['id'])->update(['isfenhong'=>1]);
                }
                unset($mdv);
            }
        }
        //多商户买单订单，按商户地址结算区域分红
        if(getcustom('maidan_area_fenhong',$aid) && $sysset['fhjiesuanbusiness'] == 1){
            $maidanWhere = [];
            $maidanWhere[] = ['og.aid','=',$aid];
            $maidanWhere[] = ['og.bid','>',0];
            $maidanWhere[] = ['og.status','=',1];
            $maidanWhere[] = ['og.isfenhong','=',0];
            $maidanWhere[] = ['og.paytime','>=',$starttime];
            $maidanWhere[] = ['og.paytime','<',$endtime];
            $oglistM = Db::name('maidan_order')->alias('og')->field("og.*,og.paymoney real_totalprice,og.title name,'1' as num,'maidan' as module,0 proid")
                ->where($maidanWhere)->limit($limit)->select()->toArray();
            if($oglistM){
                $ids = array_column($oglistM,'id');
                Db::name('maidan_order')->where('id','in',$ids)->update(['isfenhong'=>1]);
                $oglist = array_merge($oglist,$oglistM);
            }
        }
        //多商户收银订单，按商户地址结算区域分红
        if(getcustom('cashier_area_fenhong',$aid) && $sysset['fhjiesuanbusiness'] == 1){
            $cashierWhere = [];
            $cashierWhere[] = ['og.aid','=',$aid];
            $cashierWhere[] = ['og.bid','>',0];
            $cashierWhere[] = ['o.status','=',1];
            $cashierWhere[] = ['og.isfenhong','=',0];
            $cashierWhere[] = ['o.paytime','>=',$starttime];
            $cashierWhere[] = ['o.paytime','<',$endtime];
            $oglistC = Db::name('cashier_order_goods')->alias('og')->field("og.*,o.paytime,'cashier' as module")
                ->join('cashier_order o','o.id=og.orderid')->where($cashierWhere)->limit($limit)->select()->toArray();
            if($oglistC){
                $ids = array_column($oglistC,'id');
                Db::name('cashier_order_goods')->where('id','in',$ids)->update(['isfenhong'=>1]);
                $oglist = array_merge($oglist,$oglistC);
            }
        }
        if(getcustom('maidan_fenhong_new',$aid) && $sysset['maidanfenhong'] && !getcustom('maidan_area_fenhong',$aid)){
            //买单
            $starttime_m = $starttime;
            if($sysset['maidanfenhong_start_time']>0 && $sysset['maidanfenhong_start_time']>$starttime_m){
                $starttime_m = $sysset['maidanfenhong_start_time'];
            }
            $maidan_orderlist = Db::name('maidan_order')
                ->alias('og')
                ->join('member m','m.id=og.mid')
                ->where('og.aid',$aid)
                ->where('og.isfenhong',0)
                ->where('og.status',1)
                ->where('og.paytime','>=',$starttime_m)
                ->where('og.paytime','<',$endtime);
            if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                $maidan_orderlist = $maidan_orderlist
                    ->where('og.bid',0);
            }
            $maidan_orderlist = $maidan_orderlist
                ->field('og.*,m.nickname,m.headimg')
                ->limit($limit)
                ->order('og.id desc')
                ->select()
                ->toArray();
            if($maidan_orderlist){
                foreach($maidan_orderlist as $mdk=>$mdv){
                    $mdv['proid']            = 0;
                    $mdv['name']            = $mdv['title'];
                    $mdv['real_totalprice'] = $mdv['paymoney'];
                    //买单分红结算方式
                    if($sysset['maidanfenhong_type'] == 1){
                        //按利润结算时直接把销售额改成利润
                        $mdv['real_totalprice'] = $mdv['paymoney'] - $mdv['cost_price'];
                    }elseif($sysset['maidanfenhong_type'] == 2){
                        //按抽佣计算
                        $mdv['real_totalprice'] = $mdv['order_fee'];
                    }
                    $mdv['cost_price']      = 0;
                    $mdv['num']             = 1;
                    $mdv['module']          = 'maidan';
                    $oglist[]               = $mdv;
                    Db::name('maidan_order')->where('id',$mdv['id'])->update(['isfenhong'=>1]);
                }
                unset($mdv);
            }
        }
        if(getcustom('restaurant_fenhong',$aid) && $sysset['restaurant_fenhong_status']){
            //点餐
            $diancan_oglist = Db::name('restaurant_shop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('restaurant_shop_order o','o.id=og.orderid')->where($where)->limit($limit)->select()->toArray();
            if($diancan_oglist){
                foreach($diancan_oglist as $dck=>$dcv){
                    $dcv['module'] = 'restaurant_shop';
                    $oglist[]      = $dcv;
                }
                Db::name('restaurant_shop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('restaurant_shop_order o','o.id=og.orderid')->where($where)->update(['og.isfenhong'=>1]);
            }
            //外卖
            $takeaway_oglist = Db::name('restaurant_takeaway_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('restaurant_takeaway_order o','o.id=og.orderid')->where($where)->limit($limit)->select()->toArray();
            if($takeaway_oglist){
                foreach($takeaway_oglist as $twk=>$twv){
                    $twv['module'] = 'restaurant_takeaway';
                    $oglist[]      = $twv;
                }
                Db::name('restaurant_takeaway_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('restaurant_takeaway_order o','o.id=og.orderid')->where($where)->update(['og.isfenhong'=>1]);
            }
        }
        if(getcustom('fenhong_times_coupon',$aid)){
            $cwhere[] =['og.isfenhong','=',0];
            $cwhere[] =['og.status','=',1]; 
            $cwhere[] =['og.paytime','>=',$starttime]; 
            $cwhere[] =['og.paytime','<',$endtime];
            if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                $cwhere[] =['og.bid','=',0];
            }
            
            $couponorderlist = Db::name('coupon_order')->alias('og')
                ->join('member m','m.id=og.mid')
                ->where($cwhere)
                ->field('og.*,m.nickname,m.headimg')
                ->order('og.id desc')
                ->limit($limit)
                ->select()
                ->toArray();
            
            foreach($couponorderlist as $k=>$v){
                $v['name'] = $v['title'];
                $v['real_totalprice'] = $v['price'];
                $v['cost_price'] = 0;
                $v['module'] = 'coupon';
                $oglist[] = $v;
            }
            if($couponorderlist){
                Db::name('coupon_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m','m.id=og.mid')->where($cwhere)->update(['og.isfenhong'=>1]);
            }
        }
        if(getcustom('fenhong_kecheng',$aid)){
            //课程直接支付，无区域分红
            $kwhere = [];
            $kwhere[] = ['og.aid','=',$aid];
            $kwhere[] = ['og.isfenhong','=',0];
            $kwhere[] = ['og.status','=',1];
            $kwhere[] = ['og.paytime','>=',$starttime];
            $kwhere[] = ['og.paytime','<',$endtime];
            if($sysset['fhjiesuanbusiness'] != 1){
                $kwhere[] = ['og.bid','=','0'];
            }
            $kechenglist = Db::name('kecheng_order')
                ->alias('og')
                ->join('member m','m.id=og.mid')
                ->where($kwhere)
                ->field('og.*," " as area2,m.nickname,m.headimg')
                ->limit($limit)
                ->select()
                ->toArray();
            if($kechenglist){
                foreach($kechenglist as $v){
                    $v['name']            = $v['title'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price']      = 0;
                    $v['module']          = 'kecheng';
                    $v['num']             = 1;
                    $oglist[]             = $v;
                    Db::name('kecheng_order')->where('id',$v['id'])->update(['isfenhong'=>1]);
                }
            }
        }
        if(getcustom('business_reward_member',$aid)){
            //商家打赏订单
            $rwhere = [];
            $rwhere[] = ['og.aid','=',$aid];
            $rwhere[] = ['og.isfenhong','=',0];
            $rwhere[] = ['og.status','=',1];
            $rwhere[] = ['og.paytime','>=',$starttime];
            $rwhere[] = ['og.paytime','<',$endtime];

            $reward_list = Db::name('business_reward_order')
                ->alias('og')
                ->join('member m','m.id=og.to_mid')
                ->where($rwhere)
                ->field('og.*," " as area2,m.nickname,m.headimg')
                ->limit($limit)
                ->select()
                ->toArray();
            if($reward_list){
                foreach($reward_list as $v){
                    $v['mid'] = $v['to_mid'];
                    $v['name']            = $v['name'];
                    $v['real_totalprice'] = bcsub($v['pay_money'],$v['to_commission'],2);
                    $v['cost_price']      = 0;
                    $v['module']          = 'business_reward';
                    $v['num']             = 1;
                    $oglist[]             = $v;
                    Db::name('business_reward_order')->where('id',$v['id'])->update(['isfenhong'=>1]);
                }
            }
        }
        if(getcustom('hotel',$aid)){
            $where3 = [];
            $where3[] = ['og.aid','=',$aid];
            $where3[] = ['og.isfenhong','=',0];
            if($sysset['fhjiesuanbusiness'] == 1){ //多商户的商品是否参与分红
                
            }else{
                $where3[] = ['og.bid','=','0'];
            }
            if($sysset['fhjiesuantime_type'] == 1) { //分红结算时间类型 0收货后，1付款后
                $where3[] = ['og.status','in',[2,3,4]];
                $where3[] = ['og.createtime','>=',$starttime];
                $where3[] = ['og.createtime','<',$endtime];
            }else{
                $where3[] = ['og.status','=','4'];
            }
            $hotelorderlist = Db::name('hotel_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m','m.id=og.mid')->where($where3)->limit($limit)->select()->toArray();
            foreach($hotelorderlist as $k=>$v){
                $v['name'] = $v['title'];
                $v['real_totalprice'] = $v['sell_price'];
                $v['cost_price'] = $v['cost_price'] ?? 0;
                $v['module'] = 'hotel';
                $v['num'] = $v['totalnum'];
                $oglist[] = $v;
            }
            if($hotelorderlist){
                Db::name('hotel_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m','m.id=og.mid')->where($where3)->update(['og.isfenhong'=>1]);
            }
        }
        if(getcustom('cashier_fenhong',$aid)){
            $cashierWhere = [];
            $cashierWhere[] = ['og.aid','=',$aid];
            if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                $where[] = ['og.bid','=','0'];
            }
            $cashierWhere[] = ['o.status','=',1];
            $cashierWhere[] = ['og.isfenhong','=',0];
            $oglistC = Db::name('cashier_order_goods')->alias('og')
                ->field("og.*,o.paytime,o.mid,'cashier' as module")->join('cashier_order o','o.id=og.orderid')->where($cashierWhere)->select()->toArray();
            if($oglistC){
                $ids = array_column($oglistC,'id');
                Db::name('cashier_order_goods')->where('id','in',$ids)->update(['isfenhong'=>1]);
                foreach($oglistC as $ck=>$cv){
                    $cv['module'] = 'cashier';
                    $oglist[]      = $cv;
                }
            }
        }
        self::gdfenhong($aid,$sysset,$oglist,$starttime,$endtime);

        self::teamfenhong($aid,$sysset,$oglist,$starttime,$endtime);
        if(getcustom('teamfenhong_jiandan',$aid)){
            self::teamfenhong_jiandan($aid,$sysset,$oglist,$starttime,$endtime);
        }
        self::areafenhong($aid,$sysset,$oglist,$starttime,$endtime);
        self::product_teamfenhong($aid,$sysset,$oglist,$starttime,$endtime);
        self::level_teamfenhong($aid,$sysset,$oglist,$starttime,$endtime);
        self::gongxian_fenhong($aid,$sysset,$oglist,$starttime,$endtime);
        self::touzi_fenhong($aid,$sysset,$oglist,$starttime,$endtime);

        if(getcustom('business_teamfenhong',$aid)){
            //商家团队分红
            //买单
            $maidan_orderlist = Db::name('maidan_order')
                ->alias('og')
                ->join('member m','m.id=og.mid')
                ->where('og.aid',$aid)
                ->where('og.isfenhong',0)
                ->where('og.status',1)
                ->where('og.paytime','>=',$starttime)
                ->where('og.paytime','<',$endtime)
                ->where('og.bid','<>',0)
                ->field('og.*,m.nickname,m.headimg')
                ->limit($limit)
                ->order('og.id desc')
                ->select()
                ->toArray();
            if($maidan_orderlist){
                foreach($maidan_orderlist as $mdk=>$mdv){
                    $mdv['proid']            = 0;
                    $mdv['name']            = $mdv['title'];
                    $mdv['real_totalprice'] = $mdv['paymoney'];
                    $mdv['cost_price']      = 0;
                    $mdv['num']             = 1;
                    $mdv['module']          = 'maidan';
                    $oglist[]               = $mdv;
                    Db::name('maidan_order')->where('id',$mdv['id'])->update(['isfenhong'=>1]);
                }
                unset($mdv);
            }
            self::business_teamfenhong($aid,$sysset,$oglist,$starttime,$endtime);
        }
        if(getcustom('teamfenhong_jicha',$aid)){
            self::teamfenhong_jicha($aid,$sysset,$oglist,$starttime,$endtime);
        }
        if(getcustom('team_leader_fh',$aid)){
            self::teamleader_fenhong($aid,$sysset,$oglist,$starttime,$endtime);
        }
        if(getcustom('team_jiandian',$aid)){
            self::team_jiandian($aid,$sysset,$oglist,$starttime,$endtime);
        }
        if(getcustom('team_fuchijin',$aid)){
            self::team_fuchijin($aid,$sysset,$oglist,$starttime,$endtime);
        }
        if(getcustom('tuanzhang_fenhong',$aid)){
            self::tuanzhang_fenhong($aid,$sysset,$oglist);
        }
        if(getcustom('extend_business_shareholder',$aid)){
            self::business_shareholder($aid,$sysset,$oglist);
        }
        if(!$oglist){
            return ['status'=>2,'msg'=>'全部结算完成','sucnum'=>count($oglist)];
        }
        return ['status'=>1,'msg'=>'结算完成'.count($oglist).'条','sucnum'=>count($oglist)];
    }
    //结算单个订单,创建订单就结算，统一通过app\common\Order::order_create_done调用到这里，只结算不发放
    public static function jiesuan_single($aid,$orderid,$module){
        $sysset = Db::name('admin_set')->where('aid',$aid)->find();

        $where = [];
        $where[] = ['og.aid','=',$aid];
        $where[] = ['og.isfenhong','=',0];
        $fail_where = [
            ["og.aid", "=", $aid],
            ["og.isfenhong", "=", 0],
            ["og.iszj", "=", 2],
            ['og.isjiqiren', '=', 0]
        ];
        if($sysset['fhjiesuanbusiness'] == 1){ //多商户的商品是否参与分红

        }else{
            $where[] = ['og.bid','=','0'];
            $fail_where[] = ['og.bid', '=', 0];
        }
        $oglist = [];
        switch ($module){
            case 'yuyue'://预约订单
                if(getcustom('yuyue_fenhong',$aid)) {
                    $yyorderlist = Db::name('yuyue_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m','m.id=og.mid')->where($where)->where('og.id', $orderid)->select()->toArray();
                    foreach($yyorderlist as $k=>$v){
                        $v['name'] = $v['proname'];
                        $v['real_totalprice'] = $v['totalprice'];
                        $v['cost_price'] = $v['cost_price'] ?? 0;
                        $v['module'] = 'yuyue';
                        $oglist[] = $v;
                    }
                    if($yyorderlist){
                        Db::name('yuyue_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m','m.id=og.mid')->where($where)->where('og.id', $orderid)->update(['og.isfenhong'=>1]);
                    }
                }
                break;
            case 'scoreshop'://积分商城订单
                if(getcustom('scoreshop_fenhong',$aid)){
                    $scoreshopoglist = Db::name('scoreshop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('scoreshop_order o','o.id=og.orderid')->where($where)->where('og.orderid', $orderid)->select()->toArray();
                    foreach($scoreshopoglist as $v){
                        $v['real_totalprice'] = $v['totalmoney'];
                        $v['module'] = 'scoreshop';
                        $oglist[] = $v;
                    }
                    if($scoreshopoglist){
                        Db::name('scoreshop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('scoreshop_order o','o.id=og.orderid')->where($where)->where('og.orderid', $orderid)->update(['og.isfenhong'=>1]);
                    }
                }
                break;
            case 'lucky_collage'://幸运拼团订单
                if(getcustom('luckycollage_fenhong',$aid)){
                    $lcorderlist = Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m','m.id=og.mid')->where($where)->where('og.teamid', $orderid)->whereRaw('og.buytype=1 or og.iszj=1')->where('og.isjiqiren',0)->select()->toArray();
                    foreach($lcorderlist as $k=>$v){
                        $v['name'] = $v['proname'];
                        $v['real_totalprice'] = $v['totalprice'];
                        $v['cost_price'] = 0;
                        $v['module'] = 'lucky_collage';
                        $oglist[] = $v;
                        Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m','m.id=og.mid')->where($where)->where('og.id', $v['id'])->whereRaw('og.buytype=1 or og.iszj=1')->where('og.isjiqiren',0)->update(['og.isfenhong'=>1]);
                    }
                }
                //幸运拼团失败者分红
                if (getcustom('luckycollage_fail_commission',$aid)) {
                    $lcorderlist = Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m', 'm.id=og.mid')
                        ->where($fail_where)
                        ->where('og.teamid', $orderid)
                        ->select()
                        ->toArray();
                    foreach ($lcorderlist as $k => $v) {
                        $v['name'] = $v['proname'];
                        $v['real_totalprice'] = $v['totalprice'];
                        $v['cost_price'] = 0;
                        $v['module'] = 'lucky_collage';
                        $oglist[] = $v;
                        Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m', 'm.id=og.mid')
                            ->where($fail_where)
                            ->where('og.id', $v['id'])
                            ->update(['og.isfenhong' => 1]);
                    }
                }
                break;
            case 'maidan'://买单
                if((getcustom('maidan_fenhong',$aid) || getcustom('tuanzhang_fenhong',$aid)) && !getcustom('maidan_fenhong_new',$aid)){
                    //买单
                    $maidan_orderlist = Db::name('maidan_order')
                        ->alias('og')
                        ->join('member m','m.id=og.mid')
                        ->where('og.aid',$aid)
                        ->where('og.id',$orderid)
                        ->where('og.isfenhong',0)
                        ->where('og.status',1);
                    if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                        $maidan_orderlist = $maidan_orderlist
                            ->where('og.bid',0);
                    }
                    $maidan_orderlist = $maidan_orderlist
                        ->field('og.*,m.nickname,m.headimg')
                        ->order('og.id desc')
                        ->select()
                        ->toArray();
                    if($maidan_orderlist){
                        foreach($maidan_orderlist as $mdk=>$mdv){
                            $mdv['proid']            = 0;
                            $mdv['name']            = $mdv['title'];
                            $mdv['real_totalprice'] = $mdv['paymoney'];
                            $mdv['cost_price']      = 0;
                            $mdv['num']             = 1;
                            $mdv['module']          = 'maidan';
                            $oglist[]               = $mdv;
                            Db::name('maidan_order')->where('id',$mdv['id'])->update(['isfenhong'=>1]);
                        }
                        unset($mdv);
                    }
                }
                //多商户买单订单，按商户地址结算区域分红
                if(getcustom('maidan_area_fenhong',$aid) && $sysset['fhjiesuanbusiness'] == 1){
                    $maidanWhere = [];
                    $maidanWhere[] = ['og.aid','=',$aid];
                    $maidanWhere[] = ['og.id','=',$orderid];
                    $maidanWhere[] = ['og.bid','>',0];
                    $maidanWhere[] = ['og.status','=',1];
                    $maidanWhere[] = ['og.isfenhong','=',0];
                    $oglistM = Db::name('maidan_order')->alias('og')->field("og.*,og.paymoney real_totalprice,og.title name,'1' as num,'maidan' as module,0 proid")->where($maidanWhere)->select()->toArray();
                    if($oglistM){
                        $ids = array_column($oglistM,'id');
                        Db::name('maidan_order')->where('id','in',$ids)->update(['isfenhong'=>1]);
                        $oglist = array_merge($oglist,$oglistM);
                    }
                }
                if(getcustom('maidan_fenhong_new',$aid) && $sysset['maidanfenhong'] && !getcustom('maidan_area_fenhong',$aid)){
                    //买单
                    $maidan_orderlist = Db::name('maidan_order')
                        ->alias('og')
                        ->join('member m','m.id=og.mid')
                        ->where('og.aid',$aid)
                        ->where('og.id',$orderid)
                        ->where('og.isfenhong',0)
                        ->where('og.status',1);
                    if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                        $maidan_orderlist = $maidan_orderlist
                            ->where('og.bid',0);
                    }
                    $maidan_orderlist = $maidan_orderlist
                        ->field('og.*,m.nickname,m.headimg')
                        ->order('og.id desc')
                        ->select()
                        ->toArray();
                    if($maidan_orderlist){
                        foreach($maidan_orderlist as $mdk=>$mdv){
                            $mdv['proid']            = 0;
                            $mdv['name']            = $mdv['title'];
                            $mdv['real_totalprice'] = $mdv['paymoney'];
                            //买单分红结算方式
                            if($sysset['maidanfenhong_type'] == 1){
                                //按利润结算时直接把销售额改成利润
                                $mdv['real_totalprice'] = $mdv['paymoney'] - $mdv['cost_price'];
                            }elseif($sysset['maidanfenhong_type'] == 2){
                                //按抽佣计算
                                $mdv['real_totalprice'] = $mdv['order_fee'];
                            }
                            $mdv['cost_price']      = 0;
                            $mdv['num']             = 1;
                            $mdv['module']          = 'maidan';
                            $oglist[]               = $mdv;
                            Db::name('maidan_order')->where('id',$mdv['id'])->update(['isfenhong'=>1]);
                        }
                        unset($mdv);
                    }
                }
                if(getcustom('business_teamfenhong',$aid)) {
                    //买单
                    $maidan_orderlist = Db::name('maidan_order')
                        ->alias('og')
                        ->join('member m', 'm.id=og.mid')
                        ->where('og.aid', $aid)
                        ->where('og.id', $orderid)
                        ->where('og.isfenhong', 0)
                        ->where('og.status', 1)
                        ->where('og.bid', '<>', 0)
                        ->field('og.*,m.nickname,m.headimg')
                        ->order('og.id desc')
                        ->select()
                        ->toArray();
                    if ($maidan_orderlist) {
                        foreach ($maidan_orderlist as $mdk => $mdv) {
                            $mdv['proid'] = 0;
                            $mdv['name'] = $mdv['title'];
                            $mdv['real_totalprice'] = $mdv['paymoney'];
                            $mdv['cost_price'] = 0;
                            $mdv['num'] = 1;
                            $mdv['module'] = 'maidan';
                            $oglist[] = $mdv;
                            Db::name('maidan_order')->where('id', $mdv['id'])->update(['isfenhong' => 1]);
                        }
                        unset($mdv);
                    }
                }
                break;
            case 'cashier'://收银台订单
                //多商户收银订单，按商户地址结算区域分红
                if(getcustom('cashier_area_fenhong',$aid) && $sysset['fhjiesuanbusiness'] == 1){
                    $cashierWhere = [];
                    $cashierWhere[] = ['og.aid','=',$aid];
                    $cashierWhere[] = ['og.orderid','=',$orderid];
                    $cashierWhere[] = ['og.bid','>',0];
                    $cashierWhere[] = ['o.status','=',1];
                    $cashierWhere[] = ['og.isfenhong','=',0];
                    $oglistC = Db::name('cashier_order_goods')->alias('og')->field("og.*,o.paytime,'cashier' as module")->join('cashier_order o','o.id=og.orderid')->where($cashierWhere)->select()->toArray();
                    if($oglistC){
                        $ids = array_column($oglistC,'id');
                        Db::name('cashier_order_goods')->where('id','in',$ids)->update(['isfenhong'=>1]);
                        $oglist = array_merge($oglist,$oglistC);
                    }
                }
                if(getcustom('cashier_fenhong',$aid)){
                    $cashierWhere = [];
                    $cashierWhere[] = ['og.aid','=',$aid];
                    $cashierWhere[] = ['og.orderid','=',$orderid];
                    if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                        $where[] = ['og.bid','=','0'];
                    }
                    $cashierWhere[] = ['o.status','=',1];
                    $cashierWhere[] = ['og.isfenhong','=',0];
                    $oglistC = Db::name('cashier_order_goods')->alias('og')->field("og.*,o.paytime,o.mid,'cashier' as module")->join('cashier_order o','o.id=og.orderid')->where($cashierWhere)->select()->toArray();
                    if($oglistC){
                        $ids = array_column($oglistC,'id');
                        Db::name('cashier_order_goods')->where('id','in',$ids)->update(['isfenhong'=>1]);
                        foreach($oglistC as $ck=>$cv){
                            $cv['module'] = 'cashier';
                            $oglist[]      = $cv;
                        }
                    }
                }
                break;
            case 'restaurant_shop'://点餐订单
                if(getcustom('restaurant_fenhong',$aid) && $sysset['restaurant_fenhong_status']){
                    //点餐
                    $diancan_oglist = Db::name('restaurant_shop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('restaurant_shop_order o','o.id=og.orderid')->where($where)->where('og.orderid',$orderid)->select()->toArray();
                    if($diancan_oglist){
                        foreach($diancan_oglist as $dck=>$dcv){
                            $dcv['module'] = 'restaurant_shop';
                            $oglist[]      = $dcv;
                        }
                        Db::name('restaurant_shop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('restaurant_shop_order o','o.id=og.orderid')->where($where)->where('og.orderid',$orderid)->update(['og.isfenhong'=>1]);
                    }
                }
                break;
            case 'restaurant_takeaway'://点餐订单
                if(getcustom('restaurant_fenhong',$aid) && $sysset['restaurant_fenhong_status']){
                    //外卖
                    $takeaway_oglist = Db::name('restaurant_takeaway_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('restaurant_takeaway_order o','o.id=og.orderid')->where($where)->where('og.orderid',$orderid)->select()->toArray();
                    if($takeaway_oglist){
                        foreach($takeaway_oglist as $twk=>$twv){
                            $twv['module'] = 'restaurant_takeaway';
                            $oglist[]      = $twv;
                        }
                        Db::name('restaurant_takeaway_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('restaurant_takeaway_order o','o.id=og.orderid')->where($where)->where('og.orderid',$orderid)->update(['og.isfenhong'=>1]);
                    }
                }
                break;
            case 'coupon'://优惠券订单
                if(getcustom('fenhong_times_coupon',$aid)){
                    $cwhere = [];
                    $cwhere[] =['og.aid','=',$aid];
                    $cwhere[] =['og.id','=',$orderid];
                    $cwhere[] =['og.isfenhong','=',0];
                    $cwhere[] =['og.status','=',1];
                    if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                        $cwhere[] =['og.bid','=',0];
                    }

                    $couponorderlist = Db::name('coupon_order')->alias('og')
                        ->join('member m','m.id=og.mid')
                        ->where($cwhere)
                        ->field('og.*,m.nickname,m.headimg')
                        ->order('og.id desc')
                        ->select()
                        ->toArray();

                    foreach($couponorderlist as $k=>$v){
                        $v['name'] = $v['title'];
                        $v['real_totalprice'] = $v['price'];
                        $v['cost_price'] = 0;
                        $v['module'] = 'coupon';
                        $oglist[] = $v;
                    }
                    if($couponorderlist){
                        Db::name('coupon_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m','m.id=og.mid')->where($cwhere)->update(['og.isfenhong'=>1]);
                    }
                }
                break;
            case 'kecheng'://课程订单
                if(getcustom('fenhong_kecheng',$aid)){
                    //课程直接支付，无区域分红
                    $kwhere = [];
                    $kwhere[] = ['og.aid','=',$aid];
                    $kwhere[] = ['og.id','=',$orderid];
                    $kwhere[] = ['og.isfenhong','=',0];
                    if($sysset['fhjiesuanbusiness'] != 1){
                        $kwhere[] = ['og.bid','=','0'];
                    }
                    $kechenglist = Db::name('kecheng_order')
                        ->alias('og')
                        ->join('member m','m.id=og.mid')
                        ->where($kwhere)
                        ->field('og.*," " as area2,m.nickname,m.headimg')
                        ->select()
                        ->toArray();
                    if($kechenglist){
                        foreach($kechenglist as $v){
                            $v['name']            = $v['title'];
                            $v['real_totalprice'] = $v['totalprice'];
                            $v['cost_price']      = 0;
                            $v['module']          = 'kecheng';
                            $v['num']             = 1;
                            $oglist[]             = $v;
                            Db::name('kecheng_order')->where('id',$v['id'])->update(['isfenhong'=>1]);
                        }
                    }
                }
                break;
            case 'business_reward'://商家打赏订单
                if(getcustom('business_reward_member',$aid)){
                    //商家打赏订单
                    $rwhere = [];
                    $rwhere[] = ['og.aid','=',$aid];
                    $rwhere[] = ['og.id','=',$orderid];
                    $rwhere[] = ['og.isfenhong','=',0];
                    $rwhere[] = ['og.status','=',1];
                    $reward_list = Db::name('business_reward_order')
                        ->alias('og')
                        ->join('member m','m.id=og.to_mid')
                        ->where($rwhere)
                        ->field('og.*," " as area2,m.nickname,m.headimg')
                        ->select()
                        ->toArray();
                    if($reward_list){
                        foreach($reward_list as $v){
                            $v['mid'] = $v['to_mid'];
                            $v['name']            = $v['name'];
                            $v['real_totalprice'] = bcsub($v['pay_money'],$v['to_commission'],2);
                            $v['cost_price']      = 0;
                            $v['module']          = 'business_reward';
                            $v['num']             = 1;
                            $oglist[]             = $v;
                            Db::name('business_reward_order')->where('id',$v['id'])->update(['isfenhong'=>1]);
                        }
                    }
                }
                break;
            case 'hotel'://酒店订单
                if(getcustom('hotel',$aid)){
                    $where3 = [];
                    $where3[] = ['og.aid','=',$aid];
                    $where3[] = ['og.id','=',$orderid];
                    $where3[] = ['og.isfenhong','=',0];
                    if($sysset['fhjiesuanbusiness'] == 1){ //多商户的商品是否参与分红

                    }else{
                        $where3[] = ['og.bid','=','0'];
                    }
                    $hotelorderlist = Db::name('hotel_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m','m.id=og.mid')->where($where3)->select()->toArray();
                    foreach($hotelorderlist as $k=>$v){
                        $v['name'] = $v['title'];
                        $v['real_totalprice'] = $v['sell_price'];
                        $v['cost_price'] = $v['cost_price'] ?? 0;
                        $v['module'] = 'hotel';
                        $v['num'] = $v['totalnum'];
                        $oglist[] = $v;
                    }
                    if($hotelorderlist){
                        Db::name('hotel_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m','m.id=og.mid')->where($where3)->update(['og.isfenhong'=>1]);
                    }
                }
                break;
            default:
                //排除退款订单
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('shop_order o','o.id=og.orderid')->where($where)->where('og.orderid',$orderid)->where('refund_num',0)->select()->toArray();
                if($oglist){
                    if(getcustom('fenhong_manual',$aid)){
                        $update = ['og.isfenhong'=>1];
                    }else{
                        $update = ['og.isfenhong'=>1,'og.isteamfenhong'=>1];
                    }
                    $ids = array_column($oglist,'id');
                    Db::name('shop_order_goods')->alias('og')->where('id','in',$ids)->update($update);
                }
                break;
        }
        self::gdfenhong($aid,$sysset,$oglist);

        self::teamfenhong($aid,$sysset,$oglist);
        if(getcustom('teamfenhong_jiandan',$aid)){
            self::teamfenhong_jiandan($aid,$sysset,$oglist);
        }
        if(getcustom('teamfenhong_freight_money',$aid)){
            self::teamfenhong_freight($aid,$sysset,$oglist);
        }
        self::areafenhong($aid,$sysset,$oglist);
        self::product_teamfenhong($aid,$sysset,$oglist);
        self::level_teamfenhong($aid,$sysset,$oglist);
        self::gongxian_fenhong($aid,$sysset,$oglist);
        self::touzi_fenhong($aid,$sysset,$oglist);

        if(getcustom('business_teamfenhong',$aid)){
            //商家团队分红
            self::business_teamfenhong($aid,$sysset,$oglist);
        }
        if(getcustom('teamfenhong_jicha',$aid)){
            self::teamfenhong_jicha($aid,$sysset,$oglist);
        }
        if(getcustom('team_leader_fh',$aid)){
            self::teamleader_fenhong($aid,$sysset,$oglist);
        }
        if(getcustom('team_jiandian',$aid)){
            self::team_jiandian($aid,$sysset,$oglist);
        }
        if(getcustom('team_fuchijin',$aid)){
            self::team_fuchijin($aid,$sysset,$oglist);
        }

        if(getcustom('commission_notice_twice',$aid) && $module == 'shop'){
            $ids = array_column($oglist,'id');

            //根据订单查询分红
            $fenhongList = Db::name('member_fenhonglog')
                ->field('mid,SUM(commission) as commission')
                ->where('aid',$aid)
                ->where('ogids','in',$ids)
                ->group('mid')
                ->select()
                ->toArray();
            if($fenhongList){

                //查询订单信息
                $orderdata = Db::name('shop_order')
                    ->field('id,ordernum,title,totalprice,createtime')
                    ->where('id',$orderid)
                    ->find();

                //发送模板消息
                $tmplcontentNew = [];
                $tmplcontentNew['character_string1'] = $orderdata['ordernum'];
                $tmplcontentNew['thing2'] = $orderdata['title'];
                $tmplcontentNew['amount3'] = $orderdata['totalprice'].'元';
                $tmplcontentNew['time4'] = date('Y-m-d H:i:s',$orderdata['createtime']);

                foreach ($fenhongList as $k => $v) {
                    $tmplcontentNew['amount5'] = dd_money_format($v['commission'],2).'元';
                    \app\common\Wechat::sendtmpl($aid,$v['mid'],'tmpl_commission_success',[],m_url('pages/my/usercenter',$aid),$tmplcontentNew);
                }
            }
        }
        if(getcustom('tuanzhang_fenhong',$aid)){
            self::tuanzhang_fenhong($aid,$sysset,$oglist);
        }
        if(getcustom('extend_business_shareholder',$aid)){
            self::business_shareholder($aid,$sysset,$oglist);
        }
    }
    //股东分红
    public static function gdfenhong($aid,$sysset,$oglist,$starttime=0,$endtime=0,$isyj=0,$yjmid=0){
        if($endtime == 0) $endtime = time();
        //系统设置定制字段
        $sysset_custom = Db::name('admin_set_custom')->where('aid',$aid)->find();
        $gdfenhong_jiesuantype = 0;
        if( getcustom('gdfenhong_jiesuantype',$aid)){
            //股东分红独立设置结算方式0按正常的会员等级设置发放 1系统设置单独设置统一分红比例不分级别 20241211
            $gdfenhong_jiesuantype = $sysset['gdfenhong_jiesuantype']?:0;
        }
        if($gdfenhong_jiesuantype==1){
            //系统设置单独设置统一分红比例的使用新方法gdfenhong_new处理
            return true;
        }
        if(getcustom('fenhong_business_item_switch',$aid)){
            //查找开启的多商户
            $bids = Db::name('business')->where('aid',$aid)->where('gdfenhong_status',1)->column('id');
            $bids = array_merge([0],$bids);
        }
        if(getcustom('maidan_fenhong_new',$aid)){
            $bids_maidan = Db::name('business')->where('maidan_gudong','>=',1)->column('id');
            $bids_maidan = array_merge([0],$bids_maidan);
        }
        //是否开启股东分红叠加
        $gdfenhong_add = 0;
        if(getcustom('gdfenhong_add',$aid) && $sysset['gdfenhong_add'] && empty($sysset['partner_jiaquan'])){
            //与股东加权分红冲突，如果开启了加权分红这里失效
            $gdfenhong_add = 1;
        }
        if($isyj == 1 && !$oglist){
            //多商户的商品是否参与分红
            if($sysset['fhjiesuanbusiness'] == 1){
                $bwhere = '1=1';
            }else{
                $bwhere = [['og.bid','=','0']];
            }
            if(getcustom('fenhong_business_item_switch',$aid)){
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')
                    ->where('og.bid','in',$bids)
                    ->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
            }else{
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
            }
            if(getcustom('yuyue_fenhong',$aid)){
                $yyorderlist = Db::name('yuyue_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($yyorderlist as $k=>$v){
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = $v['cost_price'] ?? 0;
                    $v['module'] = 'yuyue';
                    $oglist[] = $v;
                }
            }
            if(getcustom('scoreshop_fenhong',$aid)){
                $scoreshopoglist = Db::name('scoreshop_order_goods')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($scoreshopoglist as $v){
                    $v['real_totalprice'] = $v['totalmoney'];
                    $v['module'] = 'scoreshop';
                    $oglist[] = $v;
                }
            }
            if(getcustom('luckycollage_fenhong',$aid)){
                $lcorderlist = Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->whereRaw('og.buytype=1 or og.iszj=1')->where('og.isjiqiren',0)->order('og.id desc')->select()->toArray();
                foreach($lcorderlist as $k=>$v){
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'lucky_collage';
                    $oglist[] = $v;
                }
            }
            if(getcustom('maidan_fenhong',$aid) && !getcustom('maidan_fenhong_new',$aid)){
                //买单分红
                $maidan_orderlist = Db::name('maidan_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where('og.aid',$aid)
                    ->where('og.isfenhong',0)
                    ->where('og.status',1)
                    ->where($bwhere)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                if($maidan_orderlist){
                    foreach($maidan_orderlist as $mdk=>$mdv){
                        $mdv['proid']            = 0;
                        $mdv['name']             = $mdv['title'];
                        $mdv['real_totalprice']  = $mdv['paymoney'];
                        $mdv['cost_price']       = 0;
                        $mdv['num']              = 1;
                        $mdv['module']           = 'maidan';
                        $oglist[] = $mdv;
                    }
                    unset($mdv);
                }
            }
            if(getcustom('maidan_fenhong_new',$aid) && $sysset['maidanfenhong']){
                //买单分红
                $bwhere_maidan = [['og.bid', 'in', $bids_maidan]];
                $maidan_orderlist = Db::name('maidan_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where('og.aid',$aid)
                    ->where('og.isfenhong',0)
                    ->where('og.status',1)
                    ->where($bwhere_maidan)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                if($maidan_orderlist){
                    foreach($maidan_orderlist as $mdk=>$mdv){
                        $mdv['name']             = $mdv['title'];
                        $mdv['real_totalprice']  = $mdv['paymoney'];
                        //买单分红结算方式
                        if($sysset['maidanfenhong_type'] == 1){
                            //按利润结算时直接把销售额改成利润
                            $mdv['real_totalprice'] = $mdv['paymoney'] - $mdv['cost_price'];
                        }elseif($sysset['maidanfenhong_type'] == 2){
                            //按抽佣计算
                            $mdv['real_totalprice'] = $mdv['order_fee'];
                        }
                        $mdv['cost_price']       = 0;
                        $mdv['num']              = 1;
                        $mdv['module']           = 'maidan';
                        $oglist[] = $mdv;
                    }
                }
            }
            if(getcustom('restaurant_fenhong',$aid) && $sysset['restaurant_fenhong_status']){
                //点餐
                $diancan_oglist = Db::name('restaurant_shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('restaurant_shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                if($diancan_oglist){
                    foreach($diancan_oglist as $dck=>$dcv){
                        $dcv['module'] = 'restaurant_shop';
                        $oglist[]      = $dcv;
                    }
                    unset($dcv);
                }
                //外卖
                $takeaway_oglist = Db::name('restaurant_takeaway_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('restaurant_takeaway_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                if($takeaway_oglist){
                    foreach($takeaway_oglist as $twk=>$twv){
                        $twv['module'] = 'restaurant_takeaway';
                        $oglist[]      = $twv;
                    }
                    unset($twv);
                }
            }
            if(getcustom('fenhong_times_coupon',$aid)){
                $cwhere[] =['og.isfenhong','=',0];
                $cwhere[] =['og.status','=',1];
                $cwhere[] =['og.paytime','>=',$starttime];
                $cwhere[] =['og.paytime','<',$endtime];
                if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                    $cwhere[] =['og.bid','=',0];
                }
                $couponorderlist = Db::name('coupon_order')->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where($cwhere)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                foreach($couponorderlist as $k=>$v){
                    $v['name'] = $v['title'];
                    $v['real_totalprice'] = $v['price'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'coupon';
                    $v['num'] = 1;
                    $oglist[] = $v;
                }
            }
            if(getcustom('fenhong_kecheng',$aid)){
                $kwhere = [];
                $kwhere[] = ['og.aid','=',$aid];
                $kwhere[] = ['og.isfenhong','=',0];
                $kwhere[] = ['og.status','=',1];
                $kwhere[] = ['og.paytime','>=',$starttime];
                $kwhere[] = ['og.paytime','<',$endtime];
                if($sysset['fhjiesuanbusiness'] != 1){
                    $kwhere[] = ['og.bid','=','0'];
                }
                $kechenglist = Db::name('kecheng_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where($kwhere)
                    ->field('og.*," " as area2,m.nickname,m.headimg')
                    ->select()
                    ->toArray();
                if($kechenglist){
                    foreach($kechenglist as $v){
                        $v['name']            = $v['title'];
                        $v['real_totalprice'] = $v['totalprice'];
                        $v['cost_price']      = 0;
                        $v['module']          = 'kecheng';
                        $v['num']             = 1;
                        $oglist[]             = $v;
                    }
                }
            }
            if(getcustom('hotel',$aid)){
                $hotelorderlist = Db::name('hotel_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[2,3,4])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($hotelorderlist as $k=>$v){
                    $v['name'] = $v['title'];
                    $v['real_totalprice'] = $v['sell_price'];
                    $v['cost_price'] = $v['cost_price'] ?? 0;
                    $v['module'] = 'hotel';
                    $oglist[] = $v;
                }
            }
        }
        if(getcustom('gdfenhong_level',$aid)){
            //升级费用用于股东分红
            $gd_levelids = Db::name('member_level')->where('aid',$aid)->where('apply_paygudong',1)->column('id');
            if($gd_levelids){
                $level_orders = Db::name('member_levelup_order')
                    ->where('aid',$aid)
                    ->where('isfenhong',0)
                    ->where('status',2)
                    ->where('totalprice','>',0)
                    ->whereIn('levelid',$gd_levelids)->select()->toArray();
                if($level_orders){
                    foreach($level_orders as $v){
                        $v['name']            = $v['title'];
                        $v['real_totalprice'] = $v['totalprice'];
                        $v['cost_price']      = 0;
                        $v['module']          = 'member_levelup';
                        $v['num']             = 1;
                        $oglist[]             = $v;
                        if($isyj==0){
                            Db::name('member_levelup_order')->where('id',$v['id'])->update(['isfenhong'=>1]);
                        }
                    }
                }
            }

        }

        if(getcustom('fenhong_cashier_order',$aid) && $sysset['fenhong_cashier_order_money']){

            //收银台订单
            $cowhere = [];
            $cowhere[] = ['og.aid','=',$aid];
            $cowhere[] = ['og.bid','=',0];
            $cowhere[] = ['og.isfenhong','=',0];
            $cowhere[] = ['o.status','=',1];
            if($starttime) $cowhere[] = ['o.paytime','>=',$starttime];
            if($endtime) $cowhere[] = ['o.paytime','<',$endtime];
            $cashier_order_list = Db::name('cashier_order_goods')
                ->alias('og')
                // ->join('member m','m.id=og.mid')
                ->join('cashier_order o','o.id=og.orderid')
                ->where($cowhere)
                ->field('og.*')
                ->select()
                ->toArray();
            if($cashier_order_list){
                // 0商品价格1成交价格2按销售利润
                foreach($cashier_order_list as $v){
                    if($sysset['fxjiesuantype'] == 0){
                        $v['real_totalprice'] = $v['totalprice'];
                    }
                    $v['name']            = $v['proname'];
                    $v['module']          = 'cashier';
                    $oglist[]             = $v;
                    Db::name('cashier_order_goods')->where('id',$v['id'])->update(['isfenhong'=>1]);
                }
            }
        }

        if(!$oglist) return ['commissionyj'=>0,'oglist'=>[]];
        //参与股东分红的等级
        if($gdfenhong_jiesuantype){
            //独立设置结算方式
            $where_level = [];
            $where_level[] = ['id','in',$sysset['gdfh_levelids']];
            $fhlevellist = Db::name('member_level')->where('aid',$aid)->where($where_level)->order('sort desc,id desc')->column('*','id');
        }else{
            $where_level = [];
            $where_level[] = ['fenhong','>',0];
            if(getcustom('maidan_fenhong_new',$aid)){
                $where_level = [];
                $where_level[] = ['fenhong|fenhong_maidan_percent','>',0];
            }
            $fhlevellist = Db::name('member_level')->where('aid',$aid)->where($where_level)->order('sort desc,id desc')->column('*','id');
        }
        if(!$fhlevellist) return ['commissionyj'=>0,'oglist'=>[]];

        if(getcustom('business_reward_member',$aid)){
            //商家打赏订单
            $business_reward_set =Db::name('business_reward_set')->where('aid',$aid)->find();
        }
        //股东最大分红累加低级别的分红上限参数
        if(getcustom('fenhong_max',$aid) && !empty($sysset['fenhong_max_add'])){
            foreach($fhlevellist as $k=>$v){
                $fenhong_max = Db::name('member_level')
                    ->where('aid',$aid)
                    ->where('sort','<',$v['sort'])
                    ->sum('fenhong_max_money');
                $fhlevellist[$k]['fenhong_max_money'] = bcadd($v['fenhong_max_money'],$fenhong_max,2);
            }
        }
        
        $defaultCid = Db::name('member_level_category')->where('aid',$aid)->where('isdefault', 1)->value('id');
        if($defaultCid) {
            $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->where('cid', $defaultCid)->column('id');
        } else {
            $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->column('id');
        }

        $ogids = [];
        $midfhArr = [];
        $newoglist = [];
        $commissionyj = 0;
        $allfenhongprice = 0;

        $commission_weishu = 2;//佣金位数
        if(getcustom('fenhong_money_weishu',$aid)){
            $commission_weishu = $sysset['fenhong_money_weishu']??2;
        }
        foreach($oglist as $og){
            if(getcustom('maidan_fenhong_new',$aid) && $og['module']=='maidan'){
                if($og['bid'] > 0 && !in_array($og['bid'],$bids_maidan)){
                    continue;
                }
            }
            $levelid_only = 0;
            if(getcustom('partner_parent_only',$aid)){
                //股东分红仅奖励购买人上级等级
                if($sysset['partner_parent_only']){
                    $levelid_only = -1;
                    $pid_og = Db::name('member')->where('id',$og['mid'])->value('pid');
                    if($pid_og)
                        $levelid_only = Db::name('member')->where('id',$pid_og)->value('levelid');
                    else
                        continue;
                }
            }
            if(getcustom('commission2moneypercent',$aid) && $sysset['commission2moneypercent1'] > 0){
                //是否是首单
                $beforeorder = Db::name('shop_order')->where('aid',$aid)->where('mid',$og['mid'])->where('status','in','1,2,3')->where('paytime','<',$og['paytime'])->find();
                if(!$beforeorder){
                    $commissionpercent = 1 - $sysset['commission2moneypercent1'] * 0.01;
                    $moneypercent = $sysset['commission2moneypercent1'] * 0.01;
                }else{
                    $commissionpercent = 1 - $sysset['commission2moneypercent2'] * 0.01;
                    $moneypercent = $sysset['commission2moneypercent2'] * 0.01;
                }
            }else{
                $commissionpercent = 1;
                $moneypercent = 0;
            }

            if($og['module'] == 'yuyue'){
                $product = Db::name('yuyue_product')->where('id',$og['proid'])->find();
            }elseif($og['module'] == 'luckycollage' || $og['module'] == 'lucky_collage'){
                //luckycollage废弃，替换为lucky_collage 2.6.4
                $product = Db::name('lucky_collage_product')->where('id',$og['proid'])->find();
                if (getcustom('luckycollage_fail_commission',$aid)) {
                  if ($og['iszj'] == 2) {
                    $product['fenhongset'] = $product['fail_fenhongset'];
                    $product['gdfenhongset'] = $product['fail_gdfenhongset'];
                    $product['gdfenhongdata1'] = $product['fail_gdfenhongdata1'];
                    $product['gdfenhongdata2'] = $product['fail_gdfenhongdata2'];
                  }
                }
                if ($product['fenhongset'] == 0) {
                    $product['gdfenhongset'] = -1;
                }
            }elseif($og['module'] == 'coupon'){
                $product = Db::name('coupon')->where('id',$og['cpid'])->find();
            }elseif($og['module'] == 'scoreshop'){
                $product = Db::name('scoreshop_product')->where('id',$og['proid'])->find();
            }elseif($og['module'] == 'kecheng'){
                $product = Db::name('kecheng_list')->where('id',$og['kcid'])->find();
            }elseif($og['module'] == 'business_reward'){
                if(getcustom('business_reward_member',$aid)){
                    //商家打赏订单
                    $product = [
                        'gdfenhongset' => $business_reward_set['gdfenhongset'],
                        'gdfenhongdata1' => $business_reward_set['gdfenhongdata'],
                    ];
                }
            }elseif($og['module'] == 'hotel'){
                $product = Db::name('hotel_room')->where('id',$og['roomid'])->find();
            }elseif($og['module'] == 'cashier_order'){
                $product = Db::name('shop_product')->where('id', $og['proid'])->find();
            }elseif($og['module'] == 'cashier'){
                if(getcustom('cashier_fenhong',$aid)){
                    if($og['protype']==2){
                        //直接收款
                        $product = [
                            'gdfenhongset' => 0,
                        ];
                    }else{
                        $product = Db::name('shop_product')->where('id',$og['proid'])->find();
                    }
                }
            }else{
                $product = Db::name('shop_product')->where('id',$og['proid'])->find();
            }
            if(getcustom('maidan_fenhong',$aid) || getcustom('maidan_fenhong_new',$aid)){
                if($og['module'] == 'maidan'){
                    $product = [];
                    $product['gdfenhongset'] = 0;
                    if(getcustom('maidan_fenhong_new',$aid)){
                        $business = Db::name('business')->where('id',$og['bid'])->field('maidan_gudong,maidan_gudongfenhongdata1')->find();
                        if($business){
                            if($business['maidan_gudong'] == 2){
                                $product['gdfenhongset'] = 1;
                                $product['gdfenhongdata1'] = !empty($business['maidan_gudongfenhongdata1'])?$business['maidan_gudongfenhongdata1']:[];
                            }else if($business['maidan_gudong'] == 0){
                                $product['gdfenhongset'] = -1;
                            }
                        }
                    }
                }
            }
            $restaurant_fenhong_product_set_custom = getcustom('restaurant_fenhong_product_set',$aid);
            if(getcustom('restaurant_fenhong',$aid)){
                if($og['module'] == 'restaurant_shop' || $og['module'] == 'restaurant_takeaway'){
                    $product = [];
                    $product['gdfenhongset'] = 0;
                    if($restaurant_fenhong_product_set_custom){
                        $product =  Db::name('restaurant_product')->where('id',$og['proid'])->find();
                    }
                }
            }

            //分红结算方式：0按销售金额,1按销售利润;按销售金额结算即：销售价格×提成百分比，按销售利润即：（销售价格-商品成本）×提成百分比
            if($sysset['fhjiesuantype'] == 0){
                $fenhongprice = $og['real_totalprice'];
            }else{
                $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
            }
            if(getcustom('money_dec_commission_fenhong',$aid)){
                if($og['module'] == 'maidan' && $sysset['money_dec_fenxiao_fenhong'] == 1){
                    //买单分红 按照销售额、抵扣部分参与分销分红开启时 增加抵扣金额
                    $fenhongprice += $og['dec_money'];
                }elseif($og['module'] == 'shop' && $sysset['money_dec_fenxiao_fenhong'] == 0){
                    //商城分红 按照销售、抵扣部分参与分销分红关闭时 扣除抵扣金额
                    $fenhongprice -= $og['dec_money'];
                }
            }
            if(getcustom('yx_buyer_subsidy',$aid)){
                if($sysset['fhjiesuantype'] == 2){
                    //按抽成计算
                    $fenhongprice = $og['order_fee']??0 ;
                }
            }
            if(getcustom('baikangxie',$aid)){
                $fenhongprice = $og['cost_price'] * $og['num'];
            }
            if(getcustom('member_dedamount',$aid)){
                //如果是商城和买单订单，需要判断商家是否设置了让利
                if(!$og['module'] || $og['module'] == 'shop' || $og['module'] == 'maidan'){
                    if($og['bid'] && $og['paymoney_givepercent'] && $og['paymoney_givepercent']>0){
                        //重置分红金额为抵扣
                        $fenhongprice = $og['dedamount_dkmoney']??0;
                    }else{
                        //若开启了分销依赖抵扣金，则分红金额为0
                        if($sysset['dedamount_fenxiao'] == 1) $fenhongprice = 0;
                    }
                }
            }
            if($fenhongprice <= 0 && $product['gdfenhongset']!=2 && $product['gdfenhongset']!=3) continue;

            $ogids[] = $og['id'];
            $allfenhongprice = $allfenhongprice + $fenhongprice;
//          $member = Db::name('member')->where('id', $og['mid'])->find();
//          $member_extend = Db::name('member_level_record')->field('mid id,levelid')->where('aid', $aid)->where('mid', $og['mid'])->find();

            if($fhlevellist){
                $lastmidlist = [];
                $old_midlist = [];
                $old_mids = [];
                $all_midlist = [];//所有会员列表
                $level_count = count($fhlevellist);
                $k=0;
                foreach($fhlevellist as $fhlevel){
                    $k++;
                    if(getcustom('partner_parent_only',$aid)){
                        //股东分红仅奖励购买人上级等级
                        if($levelid_only && $fhlevel['id'] != $levelid_only)
                            continue;
                    }
                    if(getcustom('business_fenhong_memberlevel',$aid) && $og['bid'] > 0){
                        $business = Db::name('business')->where('id',$og['bid'])->find();
                        if($business && $business['fenhong_memberlevel']!='' && !in_array($fhlevel['id'],explode(',',$business['fenhong_memberlevel']))) continue;
                    }
                    $where = [];
                    $where[] = ['aid', '=', $aid];
                    $where[] = ['levelid', '=', $fhlevel['id']];
                    $where[] = ['levelstarttime', '<', $og['createtime']]; //判断升级时间
                    $where2 = [];
                    $where2[] = ['ml.aid', '=', $aid];
                    $where2[] = ['ml.levelid', '=', $fhlevel['id']];
                    $where2[] = ['ml.levelstarttime', '<', $og['createtime']];
                    if($fhlevel['fenhong_max_money'] > 0 && !getcustom('fenhong_max_add',$aid)) {
                        $where[] = ['total_fenhong_partner', '<', $fhlevel['fenhong_max_money']];
                        $where2[] = ['m.total_fenhong_partner', '<', $fhlevel['fenhong_max_money']];
                    }

                    if($defaultCid > 0 && $defaultCid != $fhlevel['cid']) {
                        //其他分组
                        if(getcustom('plug_sanyang',$aid)) {
                            if($fhlevel['fenhong_num'] > 0){
                                $midlist = Db::name('member_level_record')->alias('ml')->leftJoin('member m', 'm.id = ml.mid')
                                    ->where($where2)->order('ml.levelstarttime,id')->limit(intval($fhlevel['fenhong_num']))->column('m.id,m.total_fenhong_partner,m.levelstarttime','ml.mid');
                            } else {
                                $midlist = Db::name('member_level_record')->alias('ml')->leftJoin('member m', 'm.id = ml.mid')
                                    ->where($where2)->column('m.id,m.total_fenhong_partner,m.levelstarttime','ml.mid');
                            }
                        }
                    } else {
                        $field = 'id,total_fenhong_partner,levelstarttime,levelid';
                        if(getcustom('fenhong_max',$aid)){
                            $field .= ',fenhong_max';
                        }
                        if(getcustom('fenhong_max_add',$aid)){
                            //额外分红额度
                            $field .= ',fenhong_max_add';
                        }
                        //默认分组
                        if ($fhlevel['fenhong_num'] > 0) {
                            $midlist = Db::name('member')->where($where)->order('levelstarttime,id')->limit(intval($fhlevel['fenhong_num']))->column($field, 'id');
                        }else{
                            $midlist = Db::name('member')->where($where)->column($field,'id');
                        }
                    }
                    if($midlist){
                        foreach ($midlist as $mk => $memberarr){
                            //购买前最后一条升级记录，如果下单前等级不等于当前等级 则排除（当前等级不断变化，不是完全准确，所以需要对照升级记录表）
                            $levelup_last_log = Db::name('member_levelup_order')->where('aid',$aid)->where('status', 2)
                                ->where('levelup_time', '<', $og['createtime'])->where('mid',$memberarr['id'])->order('levelup_time', 'desc')->find();
                            if($levelup_last_log && $levelup_last_log['levelid'] != $memberarr['levelid']){
                                unset($midlist[$mk]);
                            }
                        }
                    }
                    $levelup_order_mids = Db::name('member_levelup_order')->where('aid',$aid)->where('levelid', $fhlevel['id'])->where('status', 2)
                        ->where('levelup_time', '<', $og['createtime'])->group('mid')->order('levelup_time', 'desc')->column('mid');
                    if($levelup_order_mids) {
                        $levelup_order_list = [];
                        foreach($levelup_order_mids as $lk => $item_lomid){
                            //最后一条记录等于当前等级才有价值
                            $lastlog = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $item_lomid)->where('status', 2)
                                ->where('levelup_time', '<', $og['createtime'])
                                ->order('levelup_time', 'desc')->find();
                            $levelup_order_list[$item_lomid] = $lastlog['levelid'];
                            if($lastlog['levelid']!=$fhlevel['id']){
                                unset($levelup_order_mids[$lk]);
                            }
                        }
                        $field = 'id,total_fenhong_partner,levelstarttime,levelid';
                        if(getcustom('fenhong_max',$aid)){
                            $field .= ',fenhong_max';
                        }
                        if(getcustom('fenhong_max_add',$aid)){
                            //额外分红额度
                            $field .= ',fenhong_max_add';
                        }
                        if($levelup_order_mids){
                            $levelup_order_member = Db::name('member')->whereIn('id',$levelup_order_mids)->column($field,'id');
                            $midlist = array_merge((array)$midlist, (array) $levelup_order_member );
                            $midlist = array_unique_map($midlist);
                        }
                    }
                    if($sysset['partner_jiaquan'] == 1){
                        //开启后高等级的股东也会参与低等级的股东分红
                        $oldmidlist = $midlist;
                        $midlist = array_merge((array)$lastmidlist,(array)$midlist);
                        $lastmidlist = array_merge((array)$lastmidlist,(array)$oldmidlist);
                    }
                    if(!$midlist) continue;
                    if(getcustom('fenhong_gudong_yeji',$aid)){
                        //检测业绩条件
                        $fenhong_yeji_lv = $fhlevel['fenhong_yeji_lv']??0;
                        $fenhong_yeji_num = $fhlevel['fenhong_yeji_num']??0;
                        if($fenhong_yeji_num>0){
                            foreach($midlist as $fk=>$fv){
                                $downmids = \app\common\Member::getdownmids($aid,$fv['id'],$fenhong_yeji_lv);
                                if(empty($downmids)){
                                    $yeji = 0;
                                }else{
                                    $yejiwhere = [];
                                    $yejiwhere[] = ['status','in','1,2,3'];
                                    $yejiwhere[] = ['mid','in',$downmids];
                                    $yeji = Db::name('shop_order')->where('aid',$aid)->where('mid','in',$downmids)->where($yejiwhere)->sum('totalprice');
                                }
                                if($yeji<$fenhong_yeji_num){
                                    unset($midlist[$fk]);
                                }
                            }
                            $midlist = array_values($midlist);
                        }
                    }

                    if(getcustom('fenhong_max_add',$aid)){
                        //会员分红额度
                        foreach($midlist as $fk=>$fv){
                            //$member_fenhong_max = bcadd($fhlevel['fenhong_max_money'],$fv['fenhong_max_add'],2);
                            //20250530改成升级就把级别分红额度累加到会员表里面了
                            $member_fenhong_max = $fv['fenhong_max_add'];
                            if($fv['total_fenhong_partner']>=$member_fenhong_max){
                                unset($midlist[$fk]);
                                continue;
                            }
                            //查询会员已获得股东分红
                            $where_fenhong = [];
                            $where_fenhong[] = ['mid', '=', $fv['id']];
                            $where_fenhong[] = ['type', '=', 'fenhong'];
                            $where_fenhong[] = ['status', 'in', [0, 1]];
                            $fenhong_total = Db::name('member_fenhonglog')
                                ->where($where_fenhong)
                                ->sum('commission');
                            $midlist[$fk]['gudong_total'] = bcmul($fenhong_total, 1, 2);
                        }
                        $midlist = array_values($midlist);
                    }

                    if($gdfenhong_jiesuantype==1){
                        //统一设置独立分红比例的，获取到所有会员之后再进行平均发放
                        foreach($midlist as $mk => $member){
                            $midlist[$mk]['levelid'] = $fhlevel['id'];
                        }
                        $all_midlist = array_merge((array)$all_midlist, (array) $midlist );
                        $all_midlist = array_unique_map($all_midlist);
                        if($k<$level_count){
                            continue;
                        }else{
                            $midlist = $all_midlist;
                        }
                    }
                    //股东贡献量分红 开启后可设置一定比例的分红金额按照股东的团队业绩量分红
                    $pergxcommon = 0;
                    if($sysset['partner_gongxian']==1 && $fhlevel['fenhong_gongxian_percent'] > 0){
                        $gongxian_percent = $fhlevel['fenhong'] * $fhlevel['fenhong_gongxian_percent']*0.01;
                        $fhlevel['fenhong'] = $fhlevel['fenhong'] * (1 - $fhlevel['fenhong_gongxian_percent']*0.01);
                        $gongxianCommissionTotal = $gongxian_percent * $fenhongprice * 0.01;
                        //总业绩
                        //$levelids = Db::name('member_level')->where('aid',$aid)->where('sort','<',$fhlevel['sort'])->column('id');
                        //$levelids = Db::name('member_level')->where('aid',$aid)->column('id');
                        $yejiwhere = [];
                        $yejiwhere[] = ['createtime','>=',$starttime];
                        $yejiwhere[] = ['createtime','<',$endtime];
                        $yejiwhere[] = ['isfenhong','=',0];
                        //if($sysset['fhjiesuantime_type'] == 1) {
                            $yejiwhere[] = ['status','in','1,2,3'];
                        //}else{
                        //  $yejiwhere[] = ['status','=','3'];
                        //}
                        $totalyeji = 0;
                        foreach($midlist as $kk=>$item){
                            $downmids = \app\common\Member::getteammids($aid,$item['id']);
                            $yeji = Db::name('shop_order')->where('aid',$aid)->where('mid','in',$downmids)->where($yejiwhere)->sum('totalprice');
                            $yeji2 = $yeji;
                            if($fhlevel['fenhong_gongxian_peraddnum'] > 0){ //下级每出现一个同级股东增加份额
                                $tjmembercount = Db::name('member')->where('aid',$aid)->where('levelid','=',$fhlevel['id'])->where('find_in_set('.$item['id'].',path)')->count();
                                if($tjmembercount > 0){
                                    $yeji2 = $yeji2 * (1+$tjmembercount*$fhlevel['fenhong_gongxian_peraddnum']);
                                }
                            }
                            $midlist[$kk]['yeji'] = $yeji;
                            $midlist[$kk]['yeji2'] = $yeji2;
                            $totalyeji += $yeji2;
                        }
                        if($totalyeji > 0){
                            $pergxcommon = $gongxianCommissionTotal / $totalyeji;
                        }else{
                            $pergxcommon = 0;
                        }
                    }

                    //$commission = $fhlevel['fenhong'] * $fenhongprice * 0.01 / count($midlist);//平均分给此等级的会员
                    $totalcommission = 0;
                    $totalscore = 0;
                    if($gdfenhong_jiesuantype==1){
                        //统一设置独立分红比例
                        $totalcommission = $sysset['gdfh_bili'] * $fenhongprice * 0.01;
                    }elseif($product['gdfenhongset']==1){//按比例
                        $fenhongdata = json_decode($product['gdfenhongdata1'],true);
                        if($fenhongdata){
                            $totalcommission = $fenhongdata[$fhlevel['id']]['commission'] * $fenhongprice * 0.01;
                        }
                    }elseif($product['gdfenhongset']==2){//按固定金额
                        $fenhongdata = json_decode($product['gdfenhongdata2'],true);
                        if($fenhongdata){
                            $totalcommission = $fenhongdata[$fhlevel['id']]['commission'] * $og['num'];
                        }
                    }elseif($product['gdfenhongset']==3){//按固定积分
                        $fenhongdata = json_decode($product['gdfenhongdata2'],true);
                        if($fenhongdata){
                            $totalscore = $fenhongdata[$fhlevel['id']]['score'] * $og['num'];
                        }
                    }elseif($product['gdfenhongset']==4){//按积分比例
                        $fenhongdata = json_decode($product['gdfenhongdata1'],true);
                        if($fenhongdata){
                            $totalscore = round($fenhongdata[$fhlevel['id']]['score'] * $fenhongprice * 0.01);
                        }
                    }elseif($product['gdfenhongset']==7){//提成比例+积分
                        if(getcustom('gdfenhong_percent_score',$aid)){
                            $fenhongdata = json_decode($product['gdfenhongdata1'],true);
                            if($fenhongdata){
                                $totalcommission = $fenhongdata[$fhlevel['id']]['commission'] * $fenhongprice * 0.01;
                            }
                            $fenhongdata = json_decode($product['gdfenhongdata2'],true);
                            if($fenhongdata){
                                $totalscore = $fenhongdata[$fhlevel['id']]['score'] * $og['num'];
                            }
                        }
                    }elseif($product['gdfenhongset']==8){//提成金额+积分
                        if(getcustom('gdfenhong_commission_score',$aid)){
                            $fenhongdata = json_decode($product['gdfenhongdata2'],true);
                            if($fenhongdata){
                                $totalcommission = $fenhongdata[$fhlevel['id']]['commission'] * $og['num'];
                            }
                            $fenhongdata = json_decode($product['gdfenhongdata2'],true);
                            if($fenhongdata){
                                $totalscore = $fenhongdata[$fhlevel['id']]['score'] * $og['num'];
                            }
                        }

                    }elseif($product['gdfenhongset'] == 0){

                        $totalcommission = $fhlevel['fenhong'] * $fenhongprice * 0.01;
                        if(getcustom('fenhong_maidan_percent',$aid) || getcustom('maidan_fenhong_new',$aid)){
                            if($og['module'] == 'maidan'){
                                //买单单独比例
                                if($fhlevel['fenhong_maidan_percent']>=0){
                                    $totalcommission = $fhlevel['fenhong_maidan_percent'] * $fenhongprice * 0.01;
                                }else{
                                    $totalcommission = 0;
                                }
                            }
                        }

                        if($fhlevel['fenhong_score_percent'] > 0){
                            $totalscore = round($fhlevel['fenhong_score_percent'] * $fenhongprice * 0.01);
                        }
                    }
                    if(getcustom('fenhong_removefenxiao',$aid) && $fhlevel['gdfenhong_removefenxiao'] == 1){
                        if($og['parent1'] && $og['parent1commission']){
                            $totalcommission = $totalcommission - $og['parent1commission'];
                        }
                        if($og['parent2'] && $og['parent2commission']){
                            $totalcommission = $totalcommission - $og['parent2commission'];
                        }
                        if($og['parent3'] && $og['parent3commission']){
                            $totalcommission = $totalcommission - $og['parent3commission'];
                        }
                        if($totalcommission <= 0) continue;
                    }

                    if($totalcommission == 0 && $totalscore==0) continue;

                    $jq_sendtime_yj = 0;
                    if(getcustom('fenhong_jiaquan_gudong',$aid) && $fhlevel['fenhong_jiaquan_gudong_pjbl'] > 0){
                        //股东加权平均分红
                        $fenhong_jiaquan_gudong_pjbl = $fhlevel['fenhong_jiaquan_gudong_pjbl'] / 100;
                        $commission = $totalcommission * $fenhong_jiaquan_gudong_pjbl / count($midlist);
                        //当前季度结束发放时间（下个季度的凌晨00:51）
                        $season = ceil((date('n', time()))/3);//当月是第几季度
                        $jq_sendtime_yj = mktime(23,59,0,$season*3,date('t',mktime(0, 0 , 0,$season*3,1,date("Y"))),date('Y')) + 52 * 60;
                    }else{
                        $commission = $totalcommission / count($midlist);
                    }
                    if(getcustom('fenhong_max_add',$aid)){
                        //本人待分配股东分红额度*（该级别总共分红金额/该级别全部人员待分配股东分红额度累加）
                        $all_fenhong_max = array_sum(array_column($midlist,'fenhong_max_add'));
                        $all_fenhong_total = array_sum(array_column($midlist,'gudong_total'));
                        $all_fenhong_remain = bcsub($all_fenhong_max,$all_fenhong_total,2);
                    }
                    $score = floor($totalscore / count($midlist));
                    
                    //下级每出现一个同级股东增加份额
                    if($fhlevel['fenhong_gongxian_peraddnum'] > 0){ 
                        $gxtotalnum = 0;
                        foreach($midlist as $kk=>$item){
                            $gxnum = 1;
                            $tjmembercount = Db::name('member')->where('aid',$aid)->where('levelid','=',$fhlevel['id'])->where('find_in_set('.$item['id'].',path)')->count();
                            if($tjmembercount > 0){
                                $gxnum += $tjmembercount*$fhlevel['fenhong_gongxian_peraddnum'];
                            }
                            $gxtotalnum += $gxnum;
                            $midlist[$kk]['gxnum'] = $gxnum;
                        }
                        $precommission = $totalcommission / $gxtotalnum;
                    }


                    if(!$midfhArr['level_'.$fhlevel['id']]) $midfhArr['level_'.$fhlevel['id']] = [];
                    $newcommission = 0;
                    if($gdfenhong_add){
                        //叠加股东分红，必须上面计算完平均值之后再合并会员,与股东加权分红冲突
                        if($old_midlist){
                            $old_mids = array_column($old_midlist,'id');
                            $midlist = array_merge($midlist,$old_midlist);
                        }
                        $old_midlist = $midlist;
                    }
                    $have_send_commission = 0;
                    $fenhong_k = 0;
                    foreach($midlist as $item){
                        $fhlevel_id = $fhlevel['id'];
                        if($gdfenhong_jiesuantype==1){
                            $fhlevel_id = $item['levelid'];
                        }
                        if($fhlevel['fenhong_gongxian_peraddnum'] > 0){
                            $commission = $precommission * $item['gxnum'];
                        }
                        $fenhong_max_money = $fhlevel['fenhong_max_money'];
                        if($gdfenhong_add){
                            //叠加股东分红，高等级使用自身级别的最大值
                            if($old_midlist && in_array($item['id'],$old_mids)){
                                $fenhong_max_money = $fhlevellist[$item['levelid']]['fenhong_max_money']??0;
                            }
                        }

                        if(getcustom('fenhong_max_add',$aid)){
                            //本人待分配股东分红额度*（该级别总共分红金额/该级别全部人员待分配股东分红额度累加）
                            if($sysset_custom['gdfenhong_cacl_type']==1){
                                $fenhong_k++;
                                $gudong_remain = bcsub($item['fenhong_max_add'],$item['gudong_total'],2);
                                $commission = bcmul($gudong_remain,bcdiv($totalcommission,$all_fenhong_remain,10),2);
                                //dump($gudong_remain.','.$item['gudong_total'].'/'.$all_fenhong_remain.'='.$commission);
                                if($fenhong_k>=count($midlist)){
                                    $commission = bcsub($totalcommission,$have_send_commission,2);
                                }
                                $have_send_commission = bcadd($have_send_commission,$commission,2);
                            }

                        }
                        //股东最大分红，优先使用会员列表单独设置的参数
                        if(getcustom('fenhong_max',$aid) && $item['fenhong_max']>0){
                            $fenhong_max_money = $item['fenhong_max'];
                        }
                        if(getcustom('fenhong_max_add',$aid)){
                            //额外增加的分红额度
                            //$fenhong_max_money = bcadd($fenhong_max_money,$item['fenhong_max_add']);
                            //20250530改成升级就把级别分红额度累加到会员表里面了
                            $fenhong_max_money = $item['fenhong_max_add'];
                        }
                        $mid = $item['id'];
                        if($isyj == 1 && $mid == $yjmid && $commission > 0){
                            $commissionyj += $commission;
                            $og['commission'] = round($commission,$commission_weishu);
                            $og['fhname'] = t('股东分红',$aid);
                            $newoglist[] = $og;
                            break;
                        }
                        $gxcommon = 0;
                        if($pergxcommon > 0){
                            if($item['yeji'] >= $fhlevel['fenhong_gongxian_minyeji']){
                                $gxcommon = $item['yeji2'] * $pergxcommon;
                            }
                        }
                        $newcommission = $commission + $gxcommon;
                        if($midfhArr['level_'.$fhlevel_id][$mid]){
                            if($fenhong_max_money > 0) {
                                if($midfhArr['level_'.$fhlevel_id][$mid]['totalcommission'] + $newcommission + $item['total_fenhong_partner'] >$fenhong_max_money) {
                                    //Log::write('大于最大分红金额'.$commission);
                                    $newcommission = $fenhong_max_money - $midfhArr['level_'.$fhlevel_id][$mid]['totalcommission'] - $item['total_fenhong_partner'];
                                }
                            }
                            if($commissionpercent != 1){
                                $fenhongcommission = round($newcommission*$commissionpercent,$commission_weishu);
                                $fenhongmoney = round($newcommission*$moneypercent,2);
                            }else{
                                $fenhongcommission = $newcommission;
                                $fenhongmoney = 0;
                            }
                            $midfhArr['level_'.$fhlevel_id][$mid]['totalcommission'] = $midfhArr['level_'.$fhlevel_id][$mid]['totalcommission'] + $newcommission;
                            $midfhArr['level_'.$fhlevel_id][$mid]['commission'] = $midfhArr['level_'.$fhlevel_id][$mid]['commission'] + $fenhongcommission;
                            $midfhArr['level_'.$fhlevel_id][$mid]['money'] = $midfhArr['level_'.$fhlevel_id][$mid]['money'] + $fenhongmoney;
                            $midfhArr['level_'.$fhlevel_id][$mid]['score'] = $score;
                            $midfhArr['level_'.$fhlevel_id][$mid]['levelid'] = $fhlevel_id;
                            $midfhArr['level_'.$fhlevel_id][$mid]['ogids'][] = $og['id'];
                            $midfhArr['level_'.$fhlevel_id][$mid]['jq_sendtime_yj'] = $jq_sendtime_yj ?? 0;
                        }else{
                            if($fenhong_max_money > 0) {
                                if($newcommission + $item['total_fenhong_partner'] > $fenhong_max_money) {
                                    $newcommission = $fenhong_max_money - $item['total_fenhong_partner'];
                                }
                            }
                            if($commissionpercent != 1){
                                $fenhongcommission = round($newcommission*$commissionpercent,$commission_weishu);
                                $fenhongmoney = round($newcommission*$moneypercent,2);
                            }else{
                                $fenhongcommission = $newcommission;
                                $fenhongmoney = 0;
                            }
                            $midfhArr['level_'.$fhlevel_id][$mid] = [
                                'totalcommission'=>$newcommission,
                                'commission'=>$fenhongcommission,
                                'money'=>$fenhongmoney,
                                'score'=>$score,
                                'ogids'=>[$og['id']],
                                'module'=>$og['module'] ?? 'shop',
                                'jq_sendtime_yj'=> $jq_sendtime_yj ?? 0,
                            ];
                        }
                        if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                            self::fhrecord($aid,$mid,$fenhongcommission,$score,$og['id'],$og['module'] ?? 'shop','fenhong',t('股东分红',$aid));
                        }
                    }
                }
            }

            if($midfhArr){
                foreach($midfhArr as $levelstr=>$midfhArr2){
                    $levelid = explode('_',$levelstr)[1];
                    $levelname = $fhlevellist[$levelid]['name'];
                    $remark = t('股东分红',$aid);
                    if(getcustom('partner_jiaquan',$aid)){
                        $remark = '['.$levelname.']'.t('股东分红',$aid);
                    }
                    self::fafang($aid,$midfhArr2,'fenhong',$remark,$og['mid'], [],1);
                }
                //根据分红奖团队收益
                if(getcustom('teamfenhong_shouyi',$aid)){
                    self::teamshouyi($aid,$sysset,$midfhArr,$oglist,$isyj,$yjmid,$commissionpercent,$moneypercent);
                }
            }
            $midfhArr = [];

        }
    }


    //按独立比例结算的股东分红，单独一个方法
    public static function jiesuan_gdfenhong($aid,$starttime=0,$endtime=0){
        if(getcustom('gdfenhong_jiesuantype',$aid)) {
            if ($endtime == 0) $endtime = time();
            $sysset = Db::name('admin_set')->where('aid', $aid)->find();
            $limit = 10;
            $where = [];
            $where[] = ['og.aid', '=', $aid];
            $where[] = ['og.isfenhong_gd', '=', 0];
            $fail_where = [
                ["og.aid", "=", $aid],
                ["og.isfenhong_gd", "=", 0],
                ["og.iszj", "=", 2],
                ['og.isjiqiren', '=', 0]
            ];
            if ($sysset['fhjiesuanbusiness'] == 1) { //多商户的商品是否参与分红

            } else {
                $where[] = ['og.bid', '=', '0'];
                $fail_where[] = ['og.bid', '=', 0];
            }
            if($sysset['gdfhjiesuantime_type'] == 1) { //分红结算时间类型 0收货后，1付款后
                $where[] = ['og.status','in',[1,2,3]];
                $where[] = ['og.createtime','>=',$starttime];
                $where[] = ['og.createtime','<',$endtime];
                $fail_where[] = ['og.status', '=', 4];
                $fail_where[] = ['og.createtime','>=',$starttime];
                $fail_where[] = ['og.createtime','<',$endtime];
                $where2 = $where;
            }else{
                $where[] = ['og.status','=','3'];
                $where2 = $where;
                $where[] = ['og.endtime','>=',$starttime];
                $where[] = ['og.endtime','<',$endtime];
                $where2[] = ['og.collect_time','>=',$starttime];
                $where2[] = ['og.collect_time','<',$endtime];
                $fail_where[] = ['og.status', '=', 4];
            }

            //排除退款订单
            $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('shop_order o', 'o.id=og.orderid')
                ->where($where)->where('refund_num', 0)->limit($limit)->select()->toArray();
            if ($oglist) {
                if (getcustom('fenhong_manual', $aid)) {
                    $update = ['og.isfenhong_gd' => 1];
                } else {
                    $update = ['og.isfenhong_gd' => 1];
                }
                $ids = array_column($oglist, 'id');
                Db::name('shop_order_goods')->alias('og')->where('id', 'in', $ids)->update($update);
            }
            if (getcustom('yuyue_fenhong', $aid)) {
                $yyorderlist = Db::name('yuyue_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m', 'm.id=og.mid')
                    ->where($where2)->limit($limit)->select()->toArray();
                foreach ($yyorderlist as $k => $v) {
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = $v['cost_price'] ?? 0;
                    $v['module'] = 'yuyue';
                    $oglist[] = $v;
                }
                if ($yyorderlist) {
                    Db::name('yuyue_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m', 'm.id=og.mid')->where($where2)->update(['og.isfenhong_gd' => 1]);
                }
            }
            if (getcustom('scoreshop_fenhong', $aid)) {
                $scoreshopoglist = Db::name('scoreshop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')
                    ->join('scoreshop_order o', 'o.id=og.orderid')->where($where)->limit($limit)->select()->toArray();
                foreach ($scoreshopoglist as $v) {
                    $v['real_totalprice'] = $v['totalmoney'];
                    $v['module'] = 'scoreshop';
                    $oglist[] = $v;
                }

                if ($scoreshopoglist) {
                    Db::name('scoreshop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('scoreshop_order o', 'o.id=og.orderid')->where($where)->update(['og.isfenhong_gd' => 1]);
                }
            }
            if (getcustom('luckycollage_fenhong', $aid)) {
                $lcorderlist = Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')
                    ->join('member m', 'm.id=og.mid')->where($where2)->whereRaw('og.buytype=1 or og.iszj=1')->where('og.isjiqiren', 0)->limit($limit)->select()->toArray();
                foreach ($lcorderlist as $k => $v) {
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'lucky_collage';
                    $oglist[] = $v;
                }

                if ($lcorderlist) {
                    Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m', 'm.id=og.mid')->where($where2)->whereRaw('og.buytype=1 or og.iszj=1')->where('og.isjiqiren', 0)->update(['og.isfenhong_gd' => 1]);
                }
            }

            //幸运拼团失败者分红
            if (getcustom('luckycollage_fail_commission', $aid)) {
                $lcorderlist = Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m', 'm.id=og.mid')
                    ->where($fail_where)
                    ->limit($limit)
                    ->select()
                    ->toArray();
                foreach ($lcorderlist as $k => $v) {
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'lucky_collage';
                    $oglist[] = $v;
                }
                if ($lcorderlist) {
                    Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m', 'm.id=og.mid')
                        ->where($fail_where)
                        ->update(['og.isfenhong_gd' => 1]);
                }
            }

            if (getcustom('maidan_fenhong', $aid) && !getcustom('maidan_fenhong_new', $aid)) {
                //买单
                $maidan_orderlist = Db::name('maidan_order')
                    ->alias('og')
                    ->join('member m', 'm.id=og.mid')
                    ->where('og.aid', $aid)
                    ->where('og.isfenhong_gd', 0)
                    ->where('og.status', 1)
                    ->where('og.paytime', '>=', $starttime)
                    ->where('og.paytime', '<', $endtime);
                if ($sysset['fhjiesuanbusiness'] != 1) { //多商户的商品是否参与分红
                    $maidan_orderlist = $maidan_orderlist
                        ->where('og.bid', 0);
                }
                $maidan_orderlist = $maidan_orderlist
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->limit($limit)
                    ->select()
                    ->toArray();
                if ($maidan_orderlist) {
                    foreach ($maidan_orderlist as $mdk => $mdv) {
                        $mdv['proid'] = 0;
                        $mdv['name'] = $mdv['title'];
                        $mdv['real_totalprice'] = $mdv['paymoney'];
                        $mdv['cost_price'] = 0;
                        $mdv['num'] = 1;
                        $mdv['module'] = 'maidan';
                        $oglist[] = $mdv;
                        Db::name('maidan_order')->where('id', $mdv['id'])->update(['isfenhong_gd' => 1]);
                    }
                    unset($mdv);
                }
            }
            //多商户买单订单，按商户地址结算区域分红
            if (getcustom('maidan_area_fenhong', $aid) && $sysset['fhjiesuanbusiness'] == 1) {
                $maidanWhere = [];
                $maidanWhere[] = ['og.aid', '=', $aid];
                $maidanWhere[] = ['og.bid', '>', 0];
                $maidanWhere[] = ['og.status', '=', 1];
                $maidanWhere[] = ['og.isfenhong_gd', '=', 0];
                $maidanWhere[] = ['og.paytime', '>=', $starttime];
                $maidanWhere[] = ['og.paytime', '<', $endtime];
                $oglistM = Db::name('maidan_order')->alias('og')->field("og.*,og.paymoney real_totalprice,og.title name,'1' as num,'maidan' as module,0 proid")
                    ->where($maidanWhere)->limit($limit)->select()->toArray();
                if ($oglistM) {
                    $ids = array_column($oglistM, 'id');
                    Db::name('maidan_order')->where('id', 'in', $ids)->update(['isfenhong_gd' => 1]);
                    $oglist = array_merge($oglist, $oglistM);
                }
            }
            //多商户收银订单，按商户地址结算区域分红
            if (getcustom('cashier_area_fenhong', $aid) && $sysset['fhjiesuanbusiness'] == 1) {
                $cashierWhere = [];
                $cashierWhere[] = ['og.aid', '=', $aid];
                $cashierWhere[] = ['og.bid', '>', 0];
                $cashierWhere[] = ['o.status', '=', 1];
                $cashierWhere[] = ['og.isfenhong_gd', '=', 0];
                $cashierWhere[] = ['o.paytime', '>=', $starttime];
                $cashierWhere[] = ['o.paytime', '<', $endtime];
                $oglistC = Db::name('cashier_order_goods')->alias('og')->field("og.*,o.paytime,'cashier' as module")
                    ->join('cashier_order o', 'o.id=og.orderid')->where($cashierWhere)->limit($limit)->select()->toArray();
                if ($oglistC) {
                    $ids = array_column($oglistC, 'id');
                    Db::name('cashier_order_goods')->where('id', 'in', $ids)->update(['isfenhong_gd' => 1]);
                    $oglist = array_merge($oglist, $oglistC);
                }
            }
            if (getcustom('maidan_fenhong_new', $aid) && $sysset['maidanfenhong'] && !getcustom('maidan_area_fenhong', $aid)) {
                //买单
                $maidan_orderlist = Db::name('maidan_order')
                    ->alias('og')
                    ->join('member m', 'm.id=og.mid')
                    ->where('og.aid', $aid)
                    ->where('og.isfenhong_gd', 0)
                    ->where('og.status', 1)
                    ->where('og.paytime', '>=', $starttime)
                    ->where('og.paytime', '<', $endtime);
                if ($sysset['fhjiesuanbusiness'] != 1) { //多商户的商品是否参与分红
                    $maidan_orderlist = $maidan_orderlist
                        ->where('og.bid', 0);
                }
                $maidan_orderlist = $maidan_orderlist
                    ->field('og.*,m.nickname,m.headimg')
                    ->limit($limit)
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                if ($maidan_orderlist) {
                    foreach ($maidan_orderlist as $mdk => $mdv) {
                        $mdv['proid'] = 0;
                        $mdv['name'] = $mdv['title'];
                        $mdv['real_totalprice'] = $mdv['paymoney'];
                        //买单分红结算方式
                        if ($sysset['maidanfenhong_type'] == 1) {
                            //按利润结算时直接把销售额改成利润
                            $mdv['real_totalprice'] = $mdv['paymoney'] - $mdv['cost_price'];
                        }elseif($sysset['maidanfenhong_type'] == 2){
                            //按抽佣计算
                            $mdv['real_totalprice'] = $mdv['order_fee'];
                        }
                        $mdv['cost_price'] = 0;
                        $mdv['num'] = 1;
                        $mdv['module'] = 'maidan';
                        $oglist[] = $mdv;
                        Db::name('maidan_order')->where('id', $mdv['id'])->update(['isfenhong_gd' => 1]);
                    }
                    unset($mdv);
                }
            }
            if (getcustom('restaurant_fenhong', $aid) && $sysset['restaurant_fenhong_status']) {
                //点餐
                $diancan_oglist = Db::name('restaurant_shop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('restaurant_shop_order o', 'o.id=og.orderid')->where($where)->limit($limit)->select()->toArray();
                if ($diancan_oglist) {
                    foreach ($diancan_oglist as $dck => $dcv) {
                        $dcv['module'] = 'restaurant_shop';
                        $oglist[] = $dcv;
                    }
                    Db::name('restaurant_shop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('restaurant_shop_order o', 'o.id=og.orderid')->where($where)->update(['og.isfenhong_gd' => 1]);
                }
                //外卖
                $takeaway_oglist = Db::name('restaurant_takeaway_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('restaurant_takeaway_order o', 'o.id=og.orderid')->where($where)->limit($limit)->select()->toArray();
                if ($takeaway_oglist) {
                    foreach ($takeaway_oglist as $twk => $twv) {
                        $twv['module'] = 'restaurant_takeaway';
                        $oglist[] = $twv;
                    }
                    Db::name('restaurant_takeaway_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('restaurant_takeaway_order o', 'o.id=og.orderid')->where($where)->update(['og.isfenhong_gd' => 1]);
                }
            }
            if (getcustom('fenhong_times_coupon', $aid)) {
                $cwhere[] = ['og.isfenhong_gd', '=', 0];
                $cwhere[] = ['og.status', '=', 1];
                $cwhere[] = ['og.paytime', '>=', $starttime];
                $cwhere[] = ['og.paytime', '<', $endtime];
                if ($sysset['fhjiesuanbusiness'] != 1) { //多商户的商品是否参与分红
                    $cwhere[] = ['og.bid', '=', 0];
                }

                $couponorderlist = Db::name('coupon_order')->alias('og')
                    ->join('member m', 'm.id=og.mid')
                    ->where($cwhere)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->limit($limit)
                    ->select()
                    ->toArray();

                foreach ($couponorderlist as $k => $v) {
                    $v['name'] = $v['title'];
                    $v['real_totalprice'] = $v['price'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'coupon';
                    $oglist[] = $v;
                }
                if ($couponorderlist) {
                    Db::name('coupon_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m', 'm.id=og.mid')->where($cwhere)->update(['og.isfenhong_gd' => 1]);
                }
            }
            if (getcustom('fenhong_kecheng', $aid)) {
                //课程直接支付，无区域分红
                $kwhere = [];
                $kwhere[] = ['og.aid', '=', $aid];
                $kwhere[] = ['og.isfenhong_gd', '=', 0];
                $kwhere[] = ['og.status', '=', 1];
                $kwhere[] = ['og.paytime', '>=', $starttime];
                $kwhere[] = ['og.paytime', '<', $endtime];
                if ($sysset['fhjiesuanbusiness'] != 1) {
                    $kwhere[] = ['og.bid', '=', '0'];
                }
                $kechenglist = Db::name('kecheng_order')
                    ->alias('og')
                    ->join('member m', 'm.id=og.mid')
                    ->where($kwhere)
                    ->field('og.*," " as area2,m.nickname,m.headimg')
                    ->limit($limit)
                    ->select()
                    ->toArray();
                if ($kechenglist) {
                    foreach ($kechenglist as $v) {
                        $v['name'] = $v['title'];
                        $v['real_totalprice'] = $v['totalprice'];
                        $v['cost_price'] = 0;
                        $v['module'] = 'kecheng';
                        $v['num'] = 1;
                        $oglist[] = $v;
                        Db::name('kecheng_order')->where('id', $v['id'])->update(['isfenhong_gd' => 1]);
                    }
                }
            }
            self::gdfenhong_new($aid, $sysset, $oglist, $starttime, $endtime);

            if (!$oglist) {
                return ['status' => 2, 'msg' => '全部结算完成', 'sucnum' => count($oglist)];
            }
            return ['status' => 1, 'msg' => '结算完成' . count($oglist) . '条', 'sucnum' => count($oglist)];
        }
    }
    //按独立比例结算的股东分红，所有会员等级统一使用一个比例
    public static function gdfenhong_new($aid,$sysset,$oglist,$starttime=0,$endtime=0,$isyj=0,$yjmid=0){
        if($endtime == 0) $endtime = time();

        $gdfenhong_jiesuantype = 0;
        if( getcustom('gdfenhong_jiesuantype',$aid)){
            //股东分红独立设置结算方式0按正常的会员等级设置发放 1系统设置单独设置统一分红比例不分级别 20241211
            $gdfenhong_jiesuantype = $sysset['gdfenhong_jiesuantype']?:0;
        }
        if(getcustom('fenhong_business_item_switch',$aid)){
            //查找开启的多商户
            $bids = Db::name('business')->where('aid',$aid)->where('gdfenhong_status',1)->column('id');
            $bids = array_merge([0],$bids);
        }
        if(getcustom('maidan_fenhong_new',$aid)){
            $bids_maidan = Db::name('business')->where('maidan_gudong','>=',1)->column('id');
            $bids_maidan = array_merge([0],$bids_maidan);
        }

        if($isyj == 1 && !$oglist){
            //多商户的商品是否参与分红
            if($sysset['fhjiesuanbusiness'] == 1){
                $bwhere = '1=1';
            }else{
                $bwhere = [['og.bid','=','0']];
            }
            if(getcustom('fenhong_business_item_switch',$aid)){
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')
                    ->where('og.bid','in',$bids)
                    ->where('og.aid',$aid)->where('og.isfenhong_gd',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
            }else{
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong_gd',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
            }
            if(getcustom('yuyue_fenhong',$aid)){
                $yyorderlist = Db::name('yuyue_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong_gd',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($yyorderlist as $k=>$v){
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = $v['cost_price'] ?? 0;
                    $v['module'] = 'yuyue';
                    $oglist[] = $v;
                }
            }
            if(getcustom('scoreshop_fenhong',$aid)){
                $scoreshopoglist = Db::name('scoreshop_order_goods')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong_gd',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($scoreshopoglist as $v){
                    $v['real_totalprice'] = $v['totalmoney'];
                    $v['module'] = 'scoreshop';
                    $oglist[] = $v;
                }
            }
            if(getcustom('luckycollage_fenhong',$aid)){
                $lcorderlist = Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong_gd',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->whereRaw('og.buytype=1 or og.iszj=1')->where('og.isjiqiren',0)->order('og.id desc')->select()->toArray();
                foreach($lcorderlist as $k=>$v){
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'lucky_collage';
                    $oglist[] = $v;
                }
            }
            if(getcustom('maidan_fenhong',$aid) && !getcustom('maidan_fenhong_new',$aid)){
                //买单分红
                $maidan_orderlist = Db::name('maidan_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where('og.aid',$aid)
                    ->where('og.isfenhong_gd',0)
                    ->where('og.status',1)
                    ->where($bwhere)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                if($maidan_orderlist){
                    foreach($maidan_orderlist as $mdk=>$mdv){
                        $mdv['proid']            = 0;
                        $mdv['name']             = $mdv['title'];
                        $mdv['real_totalprice']  = $mdv['paymoney'];
                        $mdv['cost_price']       = 0;
                        $mdv['num']              = 1;
                        $mdv['module']           = 'maidan';
                        $oglist[] = $mdv;
                    }
                    unset($mdv);
                }
            }
            if(getcustom('maidan_fenhong_new',$aid) && $sysset['maidanfenhong']){
                //买单分红
                $bwhere_maidan = [['og.bid', 'in', $bids_maidan]];
                $maidan_orderlist = Db::name('maidan_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where('og.aid',$aid)
                    ->where('og.isfenhong_gd',0)
                    ->where('og.status',1)
                    ->where($bwhere_maidan)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                if($maidan_orderlist){
                    foreach($maidan_orderlist as $mdk=>$mdv){
                        $mdv['name']             = $mdv['title'];
                        $mdv['real_totalprice']  = $mdv['paymoney'];
                        //买单分红结算方式
                        if($sysset['maidanfenhong_type'] == 1){
                            //按利润结算时直接把销售额改成利润
                            $mdv['real_totalprice'] = $mdv['paymoney'] - $mdv['cost_price'];
                        }elseif($sysset['maidanfenhong_type'] == 2){
                            //按抽佣计算
                            $mdv['real_totalprice'] = $mdv['order_fee'];
                        }
                        $mdv['cost_price']       = 0;
                        $mdv['num']              = 1;
                        $mdv['module']           = 'maidan';
                        $oglist[] = $mdv;
                    }
                }
            }
            if(getcustom('restaurant_fenhong',$aid) && $sysset['restaurant_fenhong_status']){
                //点餐
                $diancan_oglist = Db::name('restaurant_shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong_gd',0)->where('og.status','in',[1,2,3])->join('restaurant_shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                if($diancan_oglist){
                    foreach($diancan_oglist as $dck=>$dcv){
                        $dcv['module'] = 'restaurant_shop';
                        $oglist[]      = $dcv;
                    }
                    unset($dcv);
                }
                //外卖
                $takeaway_oglist = Db::name('restaurant_takeaway_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong_gd',0)->where('og.status','in',[1,2,3])->join('restaurant_takeaway_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                if($takeaway_oglist){
                    foreach($takeaway_oglist as $twk=>$twv){
                        $twv['module'] = 'restaurant_takeaway';
                        $oglist[]      = $twv;
                    }
                    unset($twv);
                }
            }
            if(getcustom('fenhong_times_coupon',$aid)){
                $cwhere[] =['og.isfenhong_gd','=',0];
                $cwhere[] =['og.status','=',1];
                $cwhere[] =['og.paytime','>=',$starttime];
                $cwhere[] =['og.paytime','<',$endtime];
                if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                    $cwhere[] =['og.bid','=',0];
                }
                $couponorderlist = Db::name('coupon_order')->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where($cwhere)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                foreach($couponorderlist as $k=>$v){
                    $v['name'] = $v['title'];
                    $v['real_totalprice'] = $v['price'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'coupon';
                    $v['num'] = 1;
                    $oglist[] = $v;
                }
            }
        }

        if(getcustom('fenhong_cashier_order',$aid) && $sysset['fenhong_cashier_order_money']){

            //收银台订单
            $cowhere = [];
            $cowhere[] = ['og.aid','=',$aid];
            $cowhere[] = ['og.bid','=',0];
            $cowhere[] = ['og.isfenhong_gd','=',0];
            $cowhere[] = ['o.status','=',1];
            if($starttime) $cowhere[] = ['o.paytime','>=',$starttime];
            if($endtime) $cowhere[] = ['o.paytime','<',$endtime];
            $cashier_order_list = Db::name('cashier_order_goods')
                ->alias('og')
                // ->join('member m','m.id=og.mid')
                ->join('cashier_order o','o.id=og.orderid')
                ->where($cowhere)
                ->field('og.*')
                ->select()
                ->toArray();
            if($cashier_order_list){
                // 0商品价格1成交价格2按销售利润
                foreach($cashier_order_list as $v){
                    if($sysset['fxjiesuantype'] == 0){
                        $v['real_totalprice'] = $v['totalprice'];
                    }
                    $v['name']            = $v['proname'];
                    $v['module']          = 'cashier';
                    $oglist[]             = $v;
                    Db::name('cashier_order_goods')->where('id',$v['id'])->update(['isfenhong_gd'=>1]);
                }
            }
        }

        if(!$oglist) return ['commissionyj'=>0,'oglist'=>[]];
        //参与股东分红的等级
        $where_level = [];
        if(strpos('-1',$sysset['gdfh_levelids'])===false){
            $where_level[] = ['id','in',$sysset['gdfh_levelids']];
        }
        $fhlevellist = Db::name('member_level')->where('aid',$aid)->where($where_level)->order('sort desc,id desc')->column('*','id');

        if(!$fhlevellist) return ['commissionyj'=>0,'oglist'=>[]];

        if(getcustom('business_reward_member',$aid)){
            //商家打赏订单
            $business_reward_set =Db::name('business_reward_set')->where('aid',$aid)->find();
        }
        //股东最大分红累加低级别的分红上限参数
        if(getcustom('fenhong_max',$aid) && !empty($sysset['fenhong_max_add'])){
            foreach($fhlevellist as $k=>$v){
                $fenhong_max = Db::name('member_level')
                    ->where('aid',$aid)
                    ->where('sort','<',$v['sort'])
                    ->sum('fenhong_max_money');
                $fhlevellist[$k]['fenhong_max_money'] = bcadd($v['fenhong_max_money'],$fenhong_max,2);
            }
        }

        $defaultCid = Db::name('member_level_category')->where('aid',$aid)->where('isdefault', 1)->value('id');

        //合并结算时先查询所有可拿奖会员
        if($sysset['gd_fhjiesuanhb']==1){
            $all_levelids = array_column($fhlevellist, 'id');
            $where = [];
            $where[] = ['aid', '=', $aid];
            $where[] = ['levelid', 'in', $all_levelids];
            $field = 'id,total_fenhong_partner,levelstarttime,levelid';
            if (getcustom('fenhong_max', $aid)) {
                $field .= ',fenhong_max';
            }
            $all_midlist = Db::name('member')->where($where)->column($field, 'id');
            if (!$all_midlist) {
                return true;
            }
        }

        if (getcustom('fenhong_gudong_yeji', $aid)) {
            //检测业绩条件
            $fenhong_yeji_lv = $fhlevel['fenhong_yeji_lv'] ?? 0;
            $fenhong_yeji_num = $fhlevel['fenhong_yeji_num'] ?? 0;
            if ($fenhong_yeji_num > 0) {
                foreach ($all_midlist as $fk => $fv) {
                    $downmids = \app\common\Member::getdownmids($aid, $fv['id'], $fenhong_yeji_lv);
                    if (empty($downmids)) {
                        $yeji = 0;
                    } else {
                        $yejiwhere = [];
                        $yejiwhere[] = ['status', 'in', '1,2,3'];
                        $yejiwhere[] = ['mid', 'in', $downmids];
                        $yeji = Db::name('shop_order')->where('aid', $aid)->where('mid', 'in', $downmids)->where($yejiwhere)->sum('totalprice');
                    }
                    if ($yeji < $fenhong_yeji_num) {
                        unset($all_midlist[$fk]);
                    }
                }
                $all_midlist = array_values($all_midlist);
            }
        }

        $ogids = [];
        $midfhArr = [];
        $newoglist = [];
        $commissionyj = 0;
        $allfenhongprice = 0;
        $og_ids = [];
        $og_k=0;
        foreach($oglist as $og){
            $og_k++;
            if(getcustom('maidan_fenhong_new',$aid) && $og['module']=='maidan'){
                if($og['bid'] > 0 && !in_array($og['bid'],$bids_maidan)){
                    continue;
                }
            }
            if(getcustom('fenhong_business_item_switch',$aid) && $og['module']!='maidan'){
                if($og['bid'] > 0 && !in_array($og['bid'],$bids)){
                    continue;
                }
            }
            if(getcustom('commission2moneypercent',$aid) && $sysset['commission2moneypercent1'] > 0){
                //是否是首单
                $beforeorder = Db::name('shop_order')->where('aid',$aid)->where('mid',$og['mid'])->where('status','in','1,2,3')->where('paytime','<',$og['paytime'])->find();
                if(!$beforeorder){
                    $commissionpercent = 1 - $sysset['commission2moneypercent1'] * 0.01;
                    $moneypercent = $sysset['commission2moneypercent1'] * 0.01;
                }else{
                    $commissionpercent = 1 - $sysset['commission2moneypercent2'] * 0.01;
                    $moneypercent = $sysset['commission2moneypercent2'] * 0.01;
                }
            }else{
                $commissionpercent = 1;
                $moneypercent = 0;
            }

            if($og['module'] == 'yuyue'){
                $product = Db::name('yuyue_product')->where('id',$og['proid'])->find();
            }elseif($og['module'] == 'luckycollage' || $og['module'] == 'lucky_collage'){
                //luckycollage废弃，替换为lucky_collage 2.6.4
                $product = Db::name('lucky_collage_product')->where('id',$og['proid'])->find();
                if (getcustom('luckycollage_fail_commission',$aid)) {
                    if ($og['iszj'] == 2) {
                        $product['fenhongset'] = $product['fail_fenhongset'];
                        $product['gdfenhongset'] = $product['fail_gdfenhongset'];
                        $product['gdfenhongdata1'] = $product['fail_gdfenhongdata1'];
                        $product['gdfenhongdata2'] = $product['fail_gdfenhongdata2'];
                    }
                }
                if ($product['fenhongset'] == 0) {
                    $product['gdfenhongset'] = -1;
                }
            }elseif($og['module'] == 'coupon'){
                $product = Db::name('coupon')->where('id',$og['cpid'])->find();
            }elseif($og['module'] == 'scoreshop'){
                $product = Db::name('scoreshop_product')->where('id',$og['proid'])->find();
            }elseif($og['module'] == 'kecheng'){
                $product = Db::name('kecheng_list')->where('id',$og['kcid'])->find();
            }elseif($og['module'] == 'business_reward'){
                if(getcustom('business_reward_member',$aid)){
                    //商家打赏订单
                    $product = [
                        'gdfenhongset' => $business_reward_set['gdfenhongset'],
                        'gdfenhongdata1' => $business_reward_set['gdfenhongdata'],
                    ];
                }
            }elseif($og['module'] == 'hotel'){
                $product = Db::name('hotel_room')->where('id',$og['roomid'])->find();
            }elseif($og['module'] == 'cashier_order'){
                $product = Db::name('shop_product')->where('id', $og['proid'])->find();
            }else{
                $product = Db::name('shop_product')->where('id',$og['proid'])->find();
            }
            if(getcustom('maidan_fenhong',$aid) || getcustom('maidan_fenhong_new',$aid)){
                if($og['module'] == 'maidan'){
                    $product = [];
                    $product['gdfenhongset'] = 0;
                }
            }
            if(getcustom('restaurant_fenhong',$aid)){
                if($og['module'] == 'restaurant_shop' || $og['module'] == 'restaurant_takeaway'){
                    $product = [];
                    $product['gdfenhongset'] = 0;
                }
            }

            if($sysset['fhjiesuantype'] == 0){
                $fenhongprice = $og['real_totalprice'];
            }else{
                $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
            }
            if(getcustom('money_dec_commission_fenhong',$aid)){
                if($og['module'] == 'maidan' && $sysset['money_dec_fenxiao_fenhong'] == 1){
                    //买单分红 按照销售额、抵扣部分参与分销分红开启时 增加抵扣金额
                    $fenhongprice += $og['dec_money'];
                }elseif($og['module'] == 'shop' && $sysset['money_dec_fenxiao_fenhong'] == 0){
                    //商城分红 按照销售、抵扣部分参与分销分红关闭时 扣除抵扣金额
                    $fenhongprice -= $og['dec_money'];
                }
            }
            if(getcustom('yx_buyer_subsidy',$aid)){
                if($sysset['fhjiesuantype'] == 2){
                    //按抽成计算
                    $fenhongprice = $og['order_fee']??0 ;
                }
            }
            if(getcustom('baikangxie',$aid)){
                $fenhongprice = $og['cost_price'] * $og['num'];
            }
            if($fenhongprice <= 0 ) continue;
            $ogids[] = $og['id'];
            $allfenhongprice = $allfenhongprice + $fenhongprice;


            if($sysset['gd_fhjiesuanhb']==0){
                //非合并结算根据订单创建时间再查询符合条件的会员
                $all_midlist = [];//所有会员列表
                $level_count = count($fhlevellist);
                $k=0;
                foreach($fhlevellist as $fhlevel){
                    $k++;
                    if(getcustom('business_fenhong_memberlevel',$aid) && $og['bid'] > 0){
                        $business = Db::name('business')->where('id',$og['bid'])->find();
                        if($business && $business['fenhong_memberlevel']!='' && !in_array($fhlevel['id'],explode(',',$business['fenhong_memberlevel']))) continue;
                    }
                    $where = [];
                    $where[] = ['aid', '=', $aid];
                    $where[] = ['levelid', '=', $fhlevel['id']];
                    $where[] = ['levelstarttime', '<', $og['createtime']]; //判断升级时间
                    $where2 = [];
                    $where2[] = ['ml.aid', '=', $aid];
                    $where2[] = ['ml.levelid', '=', $fhlevel['id']];
                    $where2[] = ['ml.levelstarttime', '<', $og['createtime']];
                    if($fhlevel['fenhong_max_money'] > 0) {
                        $where[] = ['total_fenhong_partner', '<', $fhlevel['fenhong_max_money']];
                        $where2[] = ['m.total_fenhong_partner', '<', $fhlevel['fenhong_max_money']];
                    }

                    if($defaultCid > 0 && $defaultCid != $fhlevel['cid']) {
                        //其他分组
                        if(getcustom('plug_sanyang',$aid)) {
                            if($fhlevel['fenhong_num'] > 0){
                                $midlist = Db::name('member_level_record')->alias('ml')->leftJoin('member m', 'm.id = ml.mid')
                                    ->where($where2)->order('ml.levelstarttime,id')->limit(intval($fhlevel['fenhong_num']))->column('m.id,m.total_fenhong_partner,m.levelstarttime','ml.mid');
                            } else {
                                $midlist = Db::name('member_level_record')->alias('ml')->leftJoin('member m', 'm.id = ml.mid')
                                    ->where($where2)->column('m.id,m.total_fenhong_partner,m.levelstarttime','ml.mid');
                            }
                        }
                    } else {
                        $field = 'id,total_fenhong_partner,levelstarttime,levelid';
                        if(getcustom('fenhong_max',$aid)){
                            $field .= ',fenhong_max';
                        }
                        //默认分组
                        if ($fhlevel['fenhong_num'] > 0) {
                            $midlist = Db::name('member')->where($where)->order('levelstarttime,id')->limit(intval($fhlevel['fenhong_num']))->column($field, 'id');
                        }else{
                            $midlist = Db::name('member')->where($where)->column($field,'id');
                        }
                    }
                    if($midlist){
                        foreach ($midlist as $mk => $memberarr){
                            //购买前最后一条升级记录，如果下单前等级不等于当前等级 则排除（当前等级不断变化，不是完全准确，所以需要对照升级记录表）
                            $levelup_last_log = Db::name('member_levelup_order')->where('aid',$aid)->where('status', 2)
                                ->where('levelup_time', '<', $og['createtime'])->where('mid',$memberarr['id'])->order('levelup_time', 'desc')->find();
                            if($levelup_last_log && $levelup_last_log['levelid'] != $memberarr['levelid']){
                                unset($midlist[$mk]);
                            }
                        }
                    }
                    $levelup_order_mids = Db::name('member_levelup_order')->where('aid',$aid)->where('levelid', $fhlevel['id'])->where('status', 2)
                        ->where('levelup_time', '<', $og['createtime'])->group('mid')->order('levelup_time', 'desc')->column('mid');
                    if($levelup_order_mids) {
                        $levelup_order_list = [];
                        foreach($levelup_order_mids as $lk => $item_lomid){
                            //最后一条记录等于当前等级才有价值
                            $lastlog = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $item_lomid)->where('status', 2)
                                ->where('levelup_time', '<', $og['createtime'])
                                ->order('levelup_time', 'desc')->find();
                            $levelup_order_list[$item_lomid] = $lastlog['levelid'];
                            if($lastlog['levelid']!=$fhlevel['id']){
                                unset($levelup_order_mids[$lk]);
                            }
                        }
                        $field = 'id,total_fenhong_partner,levelstarttime,levelid';
                        if(getcustom('fenhong_max',$aid)){
                            $field .= ',fenhong_max';
                        }
                        if($levelup_order_mids){
                            $levelup_order_member = Db::name('member')->whereIn('id',$levelup_order_mids)->column($field,'id');
                            $midlist = array_merge((array)$midlist, (array) $levelup_order_member );
                            $midlist = array_unique_map($midlist);
                        }
                    }

                    if(!$midlist) continue;
                    if(getcustom('fenhong_gudong_yeji',$aid)){
                        //检测业绩条件
                        $fenhong_yeji_lv = $fhlevel['fenhong_yeji_lv']??0;
                        $fenhong_yeji_num = $fhlevel['fenhong_yeji_num']??0;
                        if($fenhong_yeji_num>0){
                            foreach($midlist as $fk=>$fv){
                                $downmids = \app\common\Member::getdownmids($aid,$fv['id'],$fenhong_yeji_lv);
                                if(empty($downmids)){
                                    $yeji = 0;
                                }else{
                                    $yejiwhere = [];
                                    $yejiwhere[] = ['status','in','1,2,3'];
                                    $yejiwhere[] = ['mid','in',$downmids];
                                    $yeji = Db::name('shop_order')->where('aid',$aid)->where('mid','in',$downmids)->where($yejiwhere)->sum('totalprice');
                                }
                                if($yeji<$fenhong_yeji_num){
                                    unset($midlist[$fk]);
                                }
                            }
                            $midlist = array_values($midlist);
                        }
                    }

                    if($gdfenhong_jiesuantype==1){
                        //统一设置独立分红比例的，获取到所有会员之后再进行平均发放
                        foreach($midlist as $mk => $member){
                            $midlist[$mk]['levelid'] = $fhlevel['id'];
                        }
                        $all_midlist = array_merge((array)$all_midlist, (array) $midlist );
                        $all_midlist = array_unique_map($all_midlist);
                        if($k<$level_count){
                            continue;
                        }
                    }
                    //$commission = $fhlevel['fenhong'] * $fenhongprice * 0.01 / count($midlist);//平均分给此等级的会员
                    $totalcommission = 0;
                    $totalscore = 0;
                    if($gdfenhong_jiesuantype==1 && $product['gdfenhongset']!=-1){
                        //统一设置独立分红比例
                        $totalcommission = $sysset['gdfh_bili'] * $fenhongprice * 0.01;
                    }
                    if($totalcommission == 0 && $totalscore==0) continue;
                }
                //循环会员进行发放
                $commission = bcdiv($totalcommission , count($all_midlist),2);
                $score = floor($totalscore / count($all_midlist));
                $og_ids = [$og['id']];
            }

            if($sysset['gd_fhjiesuanhb']==1 ){
                //合并结算，订单循环到最后用总分红金额计算佣金)
                $og_ids[] = $og['id'];
                if($og_k==count($oglist)){
                    $totalcommission = $sysset['gdfh_bili'] * $allfenhongprice * 0.01;
                    //合并结算时先查询所有可拿奖会员
                    $all_levelids = array_column($fhlevellist, 'id');
                    $where = [];
                    $where[] = ['aid', '=', $aid];
                    $where[] = ['levelid', 'in', $all_levelids];
                    $field = 'id,total_fenhong_partner,levelstarttime,levelid';
                    if (getcustom('fenhong_max', $aid)) {
                        $field .= ',fenhong_max';
                    }
                    $all_midlist = Db::name('member')->where($where)->column($field, 'id');
                    $commission = bcdiv($totalcommission , count($all_midlist),2);
                    if (!$all_midlist) {
                        return true;
                    }
                }else{
                    continue;
                }
            }
            $midlist = $all_midlist;
            $newcommission = 0;
            foreach($midlist as $item){
                $fhlevel_id = $item['levelid'];
                $fhlevel = $fhlevellist[$fhlevel_id];
                if($gdfenhong_jiesuantype==1){
                    $fhlevel_id = $item['levelid'];
                }

                $fenhong_max_money = $fhlevel['fenhong_max_money'];
                //股东最大分红，优先使用会员列表单独设置的参数
                if(getcustom('fenhong_max',$aid) && $item['fenhong_max']>0){
                    $fenhong_max_money = $item['fenhong_max'];
                }
                $mid = $item['id'];
                if($isyj == 1 && $mid == $yjmid && $commission > 0){
                    $commissionyj += $commission;
                    $og['commission'] = round($commission,2);
                    $og['fhname'] = t('股东分红',$aid);
                    $newoglist[] = $og;
                    break;
                }
                $gxcommon = 0;

                $newcommission = $commission + $gxcommon;
                if($midfhArr['level_'.$fhlevel_id][$mid]){
                    if($fenhong_max_money > 0) {
                        if($midfhArr['level_'.$fhlevel_id][$mid]['totalcommission'] + $newcommission + $item['total_fenhong_partner'] >$fenhong_max_money) {
                            //Log::write('大于最大分红金额'.$commission);
                            $newcommission = $fenhong_max_money - $midfhArr['level_'.$fhlevel_id][$mid]['totalcommission'] - $item['total_fenhong_partner'];
                        }
                    }
                    if($commissionpercent != 1){
                        $fenhongcommission = round($newcommission*$commissionpercent,2);
                        $fenhongmoney = round($newcommission*$moneypercent,2);
                    }else{
                        $fenhongcommission = $newcommission;
                        $fenhongmoney = 0;
                    }
                    $midfhArr['level_'.$fhlevel_id][$mid]['totalcommission'] = $midfhArr['level_'.$fhlevel_id][$mid]['totalcommission'] + $newcommission;
                    $midfhArr['level_'.$fhlevel_id][$mid]['commission'] = $midfhArr['level_'.$fhlevel_id][$mid]['commission'] + $fenhongcommission;
                    $midfhArr['level_'.$fhlevel_id][$mid]['money'] = $midfhArr['level_'.$fhlevel_id][$mid]['money'] + $fenhongmoney;
                    $midfhArr['level_'.$fhlevel_id][$mid]['score'] = $score;
                    $midfhArr['level_'.$fhlevel_id][$mid]['levelid'] = $fhlevel_id;
                    $midfhArr['level_'.$fhlevel_id][$mid]['ogids'][] = $og['id'];
                }else{
                    if($fenhong_max_money > 0) {
                        if($newcommission + $item['total_fenhong_partner'] > $fenhong_max_money) {
                            $newcommission = $fenhong_max_money - $item['total_fenhong_partner'];
                        }
                    }
                    if($commissionpercent != 1){
                        $fenhongcommission = round($newcommission*$commissionpercent,2);
                        $fenhongmoney = round($newcommission*$moneypercent,2);
                    }else{
                        $fenhongcommission = $newcommission;
                        $fenhongmoney = 0;
                    }
                    $midfhArr['level_'.$fhlevel_id][$mid] = [
                        'totalcommission'=>$newcommission,
                        'commission'=>$fenhongcommission,
                        'money'=>$fenhongmoney,
                        'score'=>$score,
                        'ogids'=>$og_ids,
                        'module'=>$og['module'] ?? 'shop',
                    ];
                }
                if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                    self::fhrecord($aid,$mid,$fenhongcommission,$score,$og['id'],$og['module'] ?? 'shop','fenhong',t('股东分红',$aid));
                }
            }
            if($midfhArr){
                foreach($midfhArr as $levelstr=>$midfhArr2){
                    $levelid = explode('_',$levelstr)[1];
                    $levelname = $fhlevellist[$levelid]['name'];
                    $remark = t('股东分红',$aid);
                    self::fafang($aid,$midfhArr2,'fenhong',$remark,0, [],1);
                }
                //根据分红奖团队收益
                if(getcustom('teamfenhong_shouyi',$aid)){
                    self::teamshouyi($aid,$sysset,$midfhArr,$oglist,$isyj,$yjmid,$commissionpercent,$moneypercent);
                }
            }
            $midfhArr = [];
        }

    }
    //团队分红
    public static function teamfenhong($aid,$sysset,$oglist,$starttime=0,$endtime=0,$isyj=0,$yjmid=0){
        if($endtime == 0) $endtime = time();
        if(getcustom('fenhong_manual',$aid)) return ['commissionyj'=>0,'oglist'=>[]];
        $teamfenhong_pingji_single_bl_set = getcustom('teamfenhong_pingji_single_bl',$aid);
        $teamfenhong_money_product_status = getcustom('teamfenhong_money_product',$aid);//是否设置等级分销的团队分红每单金额参与产品的单独设置
        if(getcustom('fenhong_business_item_switch',$aid)){
            //查找开启的多商户
            $bids = Db::name('business')->where('aid',$aid)->where('teamfenhong_status',1)->column('id');
            $bids = array_merge([0],$bids);
        }
        if(getcustom('maidan_fenhong_new',$aid)){
            $bids_maidan = Db::name('business')->where('maidan_team','>=',1)->column('id');
            $bids_maidan = array_merge([0],$bids_maidan);
        }
        $teamfenhong_pingji_fenhong = 0;
        if(getcustom('teamfenhong_pingji_fenhong',$aid)){
            $teamfenhong_pingji_fenhong = $sysset['teamfenhong_pingji_fenhong']?:0;
        }
        //是否开启无限层级团队分红
        $teamfenhong_wuxian = getcustom('teamfenhong_wuxian',$aid)?:0;
        if($isyj == 1 && !$oglist){
            //多商户的商品是否参与分红
            if($sysset['fhjiesuanbusiness'] == 1){
                $bwhere = '1=1';
            }else{
                $bwhere = [['og.bid','=','0']];
            }
            if(getcustom('fenhong_business_item_switch',$aid)){
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')
                    ->where('og.bid','in',$bids)
                    ->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
            }else{
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
            }
                //            dump($oglist);
            if(!$oglist) $oglist = [];
            if(getcustom('yuyue_fenhong',$aid)){
                $yyorderlist = Db::name('yuyue_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($yyorderlist as $k=>$v){
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = $v['cost_price'] ?? 0;
                    $v['module'] = 'yuyue';
                    $oglist[] = $v;
                }
            }
            if(getcustom('scoreshop_fenhong',$aid)){
                $scoreshopoglist = Db::name('scoreshop_order_goods')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($scoreshopoglist as $v){
                    $v['real_totalprice'] = $v['totalmoney'];
                    $v['module'] = 'scoreshop';
                    $oglist[] = $v;
                }
            }
            if(getcustom('luckycollage_fenhong',$aid)){
                $lcorderlist = Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->whereRaw('og.buytype=1 or og.iszj=1')->where('og.isjiqiren',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($lcorderlist as $k=>$v){
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'lucky_collage';
                    $oglist[] = $v;
                }
            }
            if(getcustom('maidan_fenhong',$aid) && !getcustom('maidan_fenhong_new',$aid)){
                //买单分红
                $maidan_orderlist = Db::name('maidan_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where('og.aid',$aid)
                    ->where('og.isfenhong',0)
                    ->where('og.status',1)
                    ->where($bwhere)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                if($maidan_orderlist){
                    foreach($maidan_orderlist as $mdk=>$mdv){
                        $mdv['name']             = $mdv['title'];
                        $mdv['real_totalprice']  = $mdv['paymoney'];
                        $mdv['cost_price']       = 0;
                        $mdv['num']              = 1;
                        $mdv['module']           = 'maidan';
                        $oglist[] = $mdv;
                    }
                }
            }
            if(getcustom('maidan_fenhong_new',$aid) && $sysset['maidanfenhong']){
                //买单分红
                $bwhere_maidan = [['og.bid', 'in', $bids_maidan]];
                $maidan_orderlist = Db::name('maidan_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where('og.aid',$aid)
                    ->where('og.isfenhong',0)
                    ->where('og.status',1)
                    ->where($bwhere_maidan)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                if($maidan_orderlist){
                    foreach($maidan_orderlist as $mdk=>$mdv){
                        $mdv['name']             = $mdv['title'];
                        $mdv['real_totalprice']  = $mdv['paymoney'];
                        //买单分红结算方式
                        if($sysset['maidanfenhong_type'] == 1){
                            //按利润结算时直接把销售额改成利润
                            $mdv['real_totalprice'] = $mdv['paymoney'] - $mdv['cost_price'];
                        }elseif($sysset['maidanfenhong_type'] == 2){
                            //按抽佣计算
                            $mdv['real_totalprice'] = $mdv['order_fee'];
                        }
                        $mdv['cost_price']       = 0;
                        $mdv['num']              = 1;
                        $mdv['module']           = 'maidan';
                        $oglist[] = $mdv;
                    }
                }
            }
            if(getcustom('restaurant_fenhong',$aid) && $sysset['restaurant_fenhong_status']){
                //点餐
                $diancan_oglist = Db::name('restaurant_shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('restaurant_shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                if($diancan_oglist){
                    foreach($diancan_oglist as $dck=>$dcv){
                        $dcv['module'] = 'restaurant_shop';
                        $oglist[]      = $dcv;
                    }
                    unset($dcv);
                }
                //外卖
                $takeaway_oglist = Db::name('restaurant_takeaway_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('restaurant_takeaway_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                if($takeaway_oglist){
                    foreach($takeaway_oglist as $tak=>$tav){
                        $tav['module'] = 'restaurant_takeaway';
                        $oglist[]      = $tav;
                    }
                    unset($tav);
                }
            }
            if(getcustom('fenhong_times_coupon',$aid)){
                $cwhere[] =['og.isfenhong','=',0];
                $cwhere[] =['og.status','=',1];
                $cwhere[] =['og.paytime','>=',$starttime];
                $cwhere[] =['og.paytime','<',$endtime];
                if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                    $cwhere[] =['og.bid','=',0];
                }
                $couponorderlist = Db::name('coupon_order')->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where($cwhere)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                foreach($couponorderlist as $k=>$v){
                    $v['name'] = $v['title'];
                    $v['real_totalprice'] = $v['price'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'coupon';
                    $oglist[] = $v;
                }
            }
            if(getcustom('fenhong_kecheng',$aid)){
                $kwhere = [];
                $kwhere[] = ['og.aid','=',$aid];
                $kwhere[] = ['og.isfenhong','=',0];
                $kwhere[] = ['og.status','=',1];
                $kwhere[] = ['og.paytime','>=',$starttime];
                $kwhere[] = ['og.paytime','<',$endtime];
                if($sysset['fhjiesuanbusiness'] != 1){
                    $kwhere[] = ['og.bid','=','0'];
                }
                $kechenglist = Db::name('kecheng_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where($kwhere)
                    ->field('og.*," " as area2,m.nickname,m.headimg')
                    ->select()
                    ->toArray();
                if($kechenglist){
                    foreach($kechenglist as $v){
                        $v['name']            = $v['title'];
                        $v['real_totalprice'] = $v['totalprice'];
                        $v['cost_price']      = 0;
                        $v['module']          = 'kecheng';
                        $v['num']             = 1;
                        $oglist[]             = $v;
                    }
                }
            }
            if(getcustom('hotel',$aid)){
                $hotelorderlist = Db::name('hotel_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[2,3,4])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($hotelorderlist as $k=>$v){
                    $v['name'] = $v['title'];
                    $v['real_totalprice'] = $v['sell_price'];
                    $v['cost_price'] = $v['cost_price'] ?? 0;
                    $v['module'] = 'hotel';
                    $v['num']    = $v['totalnum'];
                    $oglist[] = $v;
                }
            }
        }
        if(getcustom('business_reward_member',$aid)){
            //商家打赏订单
            $business_reward_set =Db::name('business_reward_set')->where('aid',$aid)->find();
        }
        //        dump($oglist);
        //参与团队分红的等级
        $teamfhlevellist = Db::name('member_level')->where('aid',$aid)->where('teamfenhonglv','>','0')->column('*','id');
        if(!$teamfhlevellist) return ['commissionyj'=>0,'oglist'=>[]];

        if(getcustom('teamfenhong_pingji',$aid)){
            //如果产品存在单独设置团队分红平级奖奖励金额，那就取消级别设置的每单奖励
            foreach($oglist as $og){
                if(empty($og['module'])){
                    $product_teamfenhongpjset = Db::name('shop_product')->where('id',$og['proid'])->value('teamfenhongpjset');
                    if($product_teamfenhongpjset==2){
                        foreach($teamfhlevellist as $levelid=>$levelinfo){
                            $teamfhlevellist[$levelid]['teamfenhong_pingji_money'] = 0;
                        }
                        break;
                    }
                }
            }
        }
        if(getcustom('teamfenhong_pingji',$aid)){
            if(getcustom('hotel',$aid)){
                //如果存在单独设置团队分红平级奖奖励金额，那就取消级别设置的每单奖励
                foreach($oglist as $og){
                    if(empty($og['module'])){
                        $product_teamfenhongpjset = Db::name('hotel_room')->where('id',$og['roomid'])->value('teamfenhongpjset');
                        if($product_teamfenhongpjset==2){
                            foreach($teamfhlevellist as $levelid=>$levelinfo){
                                $teamfhlevellist[$levelid]['teamfenhong_pingji_money'] = 0;
                            }
                            break;
                        }
                    }
                }
            }
         
        }

        if(getcustom('luckycollage_teamfenhong',$aid)){
            if($sysset['fhjiesuanbusiness'] == 1){
                $bwhere2 = '1=1';
            }else{
                $bwhere2 = [['bid','=','0']];
            }
            if($sysset['fhjiesuantime_type'] == 1) {
                $lkorderlist = Db::name('lucky_collage_order')->where('isfenhong',0)->where('status','in',[1,2,3])->where('iszj',1)->where('isjiqiren',0)->where($bwhere2)->where('createtime','>=',$starttime)->where('createtime','<',$endtime)->select()->toArray();
                if($lkorderlist && $isyj ==0){
                    Db::name('lucky_collage_order')->where('isfenhong',0)->where('status','in',[1,2,3])->where('iszj',1)->where('isjiqiren',0)->where($bwhere2)->where('createtime','>=',$starttime)->where('createtime','<',$endtime)->update(['isfenhong'=>1]);
                }
            } else {
                $lkorderlist = Db::name('lucky_collage_order')->where('isfenhong',0)->where('status',3)->where('iszj',1)->where('isjiqiren',0)->where($bwhere2)->where('createtime','>=',$starttime)->where('createtime','<',$endtime)->select()->toArray();
                if($lkorderlist && $isyj ==0){
                    Db::name('lucky_collage_order')->where('isfenhong',0)->where('status',3)->where('iszj',1)->where('isjiqiren',0)->where($bwhere2)->where('createtime','>=',$starttime)->where('createtime','<',$endtime)->update(['isfenhong'=>1]);
                }
            }
            foreach($lkorderlist as $k=>$v){
                $v['name'] = $v['proname'];
                $v['real_totalprice'] = $v['totalprice'];
                if($isyj == 1){
                    $member = Db::name('member')->where('id',$v['mid'])->find();
                    $v['headimg'] = $member['headimg'];
                    $v['nickname'] = $member['nickname'];
                }
                $v['module'] = 'luckycollage2';
                $oglist[] = $v;
            }
        }
        if(!$oglist) return ['commissionyj'=>0,'oglist'=>[]];
        
        $defaultCid = Db::name('member_level_category')->where('aid',$aid)->where('isdefault', 1)->value('id');
        if($defaultCid) {
            $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->where('cid', $defaultCid)->column('id');
        } else {
            $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->column('id');
        }
        
        $isjicha = ($sysset['teamfenhong_differential'] == 1 ? true : false);
        $allfenhongprice = 0;
        $ogids = [];
        $midteamfhArr = [];
        $midteamfhArrNew = [];
        $midteamfhArrPj = [];//平级奖单独拆出
        $midteamfhArrBole = [];//伯乐奖单独拆出
        $teamfenhong_orderids = [];//按单奖励类 每单只发一次
        $teamfenhong_orderids_pj = [];
        $teamfenhong_orderids_cat = [];
        $newoglist = [];
        $commissionyj = 0;
        //团队分红平级奖仅限直推上级
        $yueji_limit = 0;
        if(getcustom('teamfenhong_yueji',$aid)){
            $yueji_limit = $sysset['teamfenhong_yueji']??0;
        }
        if(getcustom('teamfenhong_pingji_yueji',$aid)){
            //平级奖允许越级 1允许，0不允许
            $pingji_yueji_status = $sysset['teamfenhong_pingji_yueji']??0;
        }
        if(!getcustom('teamfenhong_pingji_yueji',$aid)){
            $pingji_yueji_status = 1;
        }
        $pingji_yueji_bonus_status = 0;
        if(getcustom('teamfenhong_pingji_yueji_bonus',$aid)){
            //平级奖允许越级 1允许，0不允许
            $pingji_yueji_bonus_status = $sysset['teamfenhong_pingji_yueji_bonus']??0;
        }
        $pingji_diji_bonus_status = 0;
        if(getcustom('teamfenhong_pingji_diji_bonus',$aid)){
            //平级奖 低级别拿高级别
            $pingji_diji_bonus_status = $sysset['teamfenhong_pingji_diji_bonus']??0;
        }
        //团队分红分钱包
        $teamfenhong_product_wallet = 0;
        if(getcustom('active_coin',$aid)){
            $teamfenhong_product_wallet = 1;
        }
        //团队分红级差同时减掉平级奖
        $teamfenhong_jicha_add_pj = 0;
        if(getcustom('teamfenhong_jicha_add_pj',$aid)){
            $teamfenhong_jicha_add_pj = $sysset['teamfenhong_jicha_add_pj']?1:0;
        }
        $teamfenhong_pingji_source = 0;//团队分红平级奖来源
        if(getcustom('teamfenhong_pingji_source',$aid)){
            $teamfenhong_pingji_source = $sysset['teamfenhong_pingji_source']??0;
        }

        $commission_weishu = 2;//佣金位数
        if(getcustom('fenhong_money_weishu',$aid)){
            $commission_weishu = $sysset['fenhong_money_weishu']??2;
        }
        //下单人的直推会员要拿平级奖
        $teamfenhong_pingji_zhitui = 0;
        if(getcustom('teamfenhong_pingji_zhitui',$aid)){
            $teamfenhong_pingji_zhitui = $sysset['teamfenhong_pingji_zhitui']?:0;
        }
        foreach($oglist as $og){
            $pj_nums = [];//记录每个级别已拿平级奖个数
            if(getcustom('maidan_fenhong_new',$aid) && $og['module']=='maidan'){
                if($og['bid'] > 0 && !in_array($og['bid'],$bids_maidan)){
                    continue;
                }
            }
            if(getcustom('fenhong_business_item_switch',$aid) && $og['module']!='maidan'){
                if($og['bid'] > 0 && !in_array($og['bid'],$bids)){
                    continue;
                }
            }
            $commissionyj_my = 0;
            if(getcustom('commission2moneypercent',$aid) && $sysset['commission2moneypercent1'] > 0){
                //是否是首单
                $beforeorder = Db::name('shop_order')->where('aid',$aid)->where('mid',$og['mid'])->where('status','in','1,2,3')->where('paytime','<',$og['paytime'])->find();
                if(!$beforeorder){
                    $commissionpercent = 1 - $sysset['commission2moneypercent1'] * 0.01;
                    $moneypercent = $sysset['commission2moneypercent1'] * 0.01;
                }else{
                    $commissionpercent = 1 - $sysset['commission2moneypercent2'] * 0.01;
                    $moneypercent = $sysset['commission2moneypercent2'] * 0.01;
                }
            }else{
                $commissionpercent = 1;
                $moneypercent = 0;
            }
            if($sysset['fhjiesuantype'] == 0){
                $fenhongprice = $og['real_totalprice'];
            }else{
                $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
            }
            if(getcustom('money_dec_commission_fenhong',$aid)){
                if($og['module'] == 'maidan' && $sysset['money_dec_fenxiao_fenhong'] == 1){
                    //买单分红 按照销售额、抵扣部分参与分销分红开启时 增加抵扣金额
                    $fenhongprice += $og['dec_money'];
                }elseif($og['module'] == 'shop' && $sysset['money_dec_fenxiao_fenhong'] == 0){
                    //商城分红 按照销售、抵扣部分参与分销分红关闭时 扣除抵扣金额
                    $fenhongprice -= $og['dec_money'];
                }
            }
            if(getcustom('yx_buyer_subsidy',$aid)){
                if($sysset['fhjiesuantype'] == 2){
                    //按抽成计算
                    $fenhongprice = $og['order_fee']??0 ;
                }
            }
            if(getcustom('baikangxie',$aid)){
                $fenhongprice = $og['cost_price'] * $og['num'];
            }
            if(getcustom('member_dedamount',$aid)){
                //如果是商城和买单订单，需要判断商家是否设置了让利
                if(!$og['module'] || $og['module'] == 'shop' || $og['module'] == 'maidan'){
                    if($og['bid'] && $og['paymoney_givepercent'] && $og['paymoney_givepercent']>0){
                        //重置分红金额为抵扣
                        $fenhongprice = $og['dedamount_dkmoney']??0;
                    }else{
                        //若开启了分销依赖抵扣金，则分红金额为0
                        if($sysset['dedamount_fenxiao'] == 1) $fenhongprice = 0;
                    }
                }
            }
            //无限层级团队分红使用产品单独设置的奖励金额
            //if($fenhongprice <= 0 && $teamfenhong_wuxian==0) continue;
            $ogids[] = $og['id'];
            $allfenhongprice = $allfenhongprice + $fenhongprice;
            $path_origin_state = false;
            $member = Db::name('member')->where('id', $og['mid'])->find();
            if(empty($member)){
                continue;
                // break;
            }
            //下单会员等级
            if($member['levelstarttime'] >= $og['createtime']) {
                $levelup_order_levelid = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $member['id'])->where('status', 2)
                    ->where('levelup_time', '<', $og['createtime'])->whereIn('levelid',$defaultLevelIds)->order('levelup_time', 'desc')->value('levelid');
                if($levelup_order_levelid) {
                    $member['levelid'] = $levelup_order_levelid;
                }
            }
            $memberLevel = Db::name('member_level')->where('aid',$aid)->where('id',$member['levelid'])->find();
            $member_extend = Db::name('member_level_record')->field('mid id,levelid')->where('aid', $aid)->where('mid', $og['mid'])->find();
            $member_levelid_buy = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $og['id'])->where('status', 2)
                ->where('levelup_time', '<', $og['createtime'])->whereIn('levelid',$defaultLevelIds)->order('levelup_time', 'desc')->value('levelid');//购买时等级id
            if($teamfhlevellist){
                //判断脱离时间
                if($member['change_pid_time'] && $member['change_pid_time'] >= $og['createtime']){
                    $pids = $member['path_origin'];
                    $path_origin_state = true;
                }else{
                    $pids = $member['path'];
                }

                if($pids){
                    $pids .= ','.$og['mid'];
                }else{
                    $pids = (string)$og['mid'];
                }
                if($pids){
                    $parentList = Db::name('member')->where('id','in',$pids)->order(Db::raw('field(id,'.$pids.')'))->select()->toArray();
                    $parentList = array_reverse($parentList);//父级从近到远，自己，上一级，上二级，上三级。。。
                    $hasfhlevelids = [];
                    $last_teamfenhongbl = 0;
                    $last_teamfenhongmoney = 0;
                    $last_teamfenhongmoney_pj_total = 0;
                    $last_teamfenhong_score_percent = 0;
                    $last_level_teamfenhongbl = 0;
                    $last_totalfenhongmoney = 0;//上次团队分红总额 金额+比例
                    $has_level_fhlevelids = [];
                    $haspingjinumArr = [];
                    //层级判断，如购买人等级未开启“包含自己teamfenhong_self“则购买人的上级为第一级，开启了则购买人为第一级
                    $level_i = 0;
                    $total_fafang_commission = 0;//总的要发放的佣金
                    $boleArr = [];
                    $boleStatus = true;
                    $bonus_i = 0;//已发奖人数
                    $last_plevelid = 0;

                    foreach($parentList as $k=>$parent){
                        //dump('会员ID'.$parent['id'].'开始');
                        //判断升级时间
                        $leveldata = $teamfhlevellist[$parent['levelid']];
                        $plevelid = $parent['levelid'];
                        $follow_leveldata = $leveldata;

                        if($parent['levelstarttime'] >= $og['createtime']) {
                            $levelup_order_levelid = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $parent['id'])->where('status', 2)
                                ->where('levelup_time', '<', $og['createtime'])->whereIn('levelid',$defaultLevelIds)->order('levelup_time', 'desc')->value('levelid');
                            if($levelup_order_levelid) {
                                $parent['levelid'] = $levelup_order_levelid;
                                $leveldata = $teamfhlevellist[$parent['levelid']];
                            }else{
                                //if($leveldata['teamfenhong_self'] != 1 || ($leveldata['teamfenhong_self'] == 1 && $parent['id'] != $og['mid']))
                                //不包含自己跳过
                                unset($parentList[$k]);
                                continue;
                            }
                        }
                        $level_i++;
                        $follow_teamfhStatus = true;
                        // 跟随等级
                        if(getcustom('teamfenhong_pingji_level_follow',$aid)){
                            if($leveldata['follow_levelid']){
                                $follow_leveldata = $teamfhlevellist[$leveldata['follow_levelid']];
                                $plevelid = $leveldata['follow_levelid'];
                            }
                            if($last_plevelid){
                                $lastleveldata = $teamfhlevellist[$last_plevelid];
                                if($lastleveldata['follow_levelid']){
                                    $last_plevelid = $lastleveldata['follow_levelid'];
                                }
                                if($plevelid == $last_plevelid){
                                    $level_i--;
                                    $follow_teamfhStatus = false;
                                }
                            }
                        }

                        $teamfhStatus = true;
                        if($parent['id'] == $og['mid'] && $leveldata['teamfenhong_self'] != 1) {
                            $level_i--;
                            if($teamfenhong_pingji_zhitui){
                                $teamfhStatus = false;
                            }else{
                                unset($parentList[$k]);
                                continue;
                            }
                        }//不包含自己则层级-1
                        //无限层级团队分红使用产品单独设置的层级
                        if(!$leveldata || ($level_i>$leveldata['teamfenhonglv'] && !$teamfenhong_wuxian)) continue;


                        if($leveldata['teamfenhongonly'] == 1 && in_array($parent['levelid'],$hasfhlevelids)){
                            //该等级设置了只给最近的上级分红,表示如果下单人有多个上级均符合分红条件则只给离他最近的上级分红，其他上级不分红
                            $teamfhStatus = false;
                        }
                        if($follow_teamfhStatus == false){
                            $teamfhStatus = false;
                        }
                        
                        if(getcustom('teamfenhong_not_send_cengji',$aid)){
                            //不发放层级
                            $teamfenhong_not_lv = explode(',',$leveldata['teamfenhong_not_lv']);
                            if($teamfenhong_not_lv && in_array($level_i,$teamfenhong_not_lv)){
                                   continue;
                            }
                        }
                        if(getcustom('teamfenhong_yejitj',$aid)){
                            //月初结算类型，且需要验证业绩大于0
                            if($sysset['fhjiesuantime'] == 1 && $leveldata['teamfenhong_yeji_money']>0){
                                //当前月份
                                $nowmonth = strtotime(date("Y-m"));
                                //查询是否有清零记录月份
                                $yejizerolog = Db::name('member_teamfenhong_yejizerolog')->where('mid',$parent['id'])->where('levelid',$parent['levelid'])->order('zero_month desc')->field('id,total_month,zero_month')->find();
                                $zero_month = $yejizerolog?$yejizerolog['zero_month']:0;//清零月份

                                //是否开启累积月份，并且累积月份大于0
                                if($leveldata['teamfenhong_yeji_total'] && $leveldata['teamfenhong_yeji_month']>0){
                                    //计算累积开始月份
                                    $stime = strtotime('-'.$leveldata['teamfenhong_yeji_month'].' month',$nowmonth);
                                    //判断用户是否有清零月份,有则清零月份及之前的月份数据不统计
                                    if($stime<=$zero_month){
                                        $stime = strtotime('+1 month',$zero_month);
                                        //如果统计月份大于等于当前月份则停止统计
                                        if($stime>=$nowmonth){
                                            continue;
                                        }
                                    }
                                }else{
                                    $stime = strtotime('-1 month',$nowmonth);
                                }
                                $etime = $nowmonth - 1;

                                //查询团队，业绩统计是否包含自己
                                if($leveldata['teamfenhong_yeji_self']){
                                    $mids =[$parent['id']];
                                }else{
                                    $mids =[];
                                }
                                $mids2 = \app\common\Member::getdownmids($aid,$parent['id']);
                                if($mids2){
                                    $mids = array_merge($mids,$mids2);
                                }
                                if($mids){
                                    //团队最早购买时间
                                    $mintime = Db::name('shop_order')->where('mid','in',$mids)->where('status','>=',1)->where('status','<=',3)->where('aid',$aid)->order('paytime asc')->value('paytime');
                                    $mintime = $mintime??0;

                                    //统计团队时间内所有业绩
                                    $allyejiorders = Db::name('shop_order')
                                        ->where('mid','in',$mids)->where('status','>=',1)->where('status','<=',3)->where('paytime','>=',$stime)->where('paytime','<=',$etime)->where('aid',$aid)
                                        ->order('paytime asc')->field('id,totalprice,refund_money,freight_price,freight_type,paytime')
                                        ->select()->toArray();
                                    $mintime2 = 0;//累积的最小月份
                                    $allyeji  = 0;//业绩总和
                                    if($allyejiorders){
                                        foreach($allyejiorders as $ak=>$av){
                                            if($ak == 0){
                                                $mintime2 = strtotime(date("Y-m",$av['paytime']));
                                            }
                                            //是否计算配方方式的运费、配送费
                                            //if(!$leveldata['teamfenhong_yeji_yunfee'] && ($av['freight_type'] == 0 || $av['freight_type'] == 2)){
                                            if(!$leveldata['teamfenhong_yeji_yunfee']){
                                                $allyeji = $av['totalprice'] - $av['refund_money'] - $av['freight_price'];
                                            }else{
                                                $allyeji = $av['totalprice'] - $av['refund_money'];
                                            }
                                        }
                                        unset($ak);
                                        unset($av);
                                        //如果业绩小于达标业绩，则不发放
                                        if($allyeji < $leveldata['teamfenhong_yeji_money']){
                                            //累积的需要判断用户业绩是否要清零
                                            if($leveldata['teamfenhong_yeji_total'] && $leveldata['teamfenhong_yeji_month']>0){
                                                //没有清零月份记录且开始计算时间大于等于最小支付月份时间，或者有清零月份且开始计算时间大于清零月份的
                                                if((!$zero_month && $stime>=$mintime) || ($zero_month && $stime>$zero_month)){
                                                    //添加清零记录
                                                    $log = [];
                                                    $log['aid']     = $aid;
                                                    $log['mid']     = $parent['id'];
                                                    $log['levelid'] = $parent['levelid'];
                                                    $log['total_month'] = $leveldata['teamfenhong_yeji_total'];
                                                    $log['zero_month']  = strtotime(date("Y-m",$etime));
                                                    $log['createtime']  = time();
                                                    Db::name('member_teamfenhong_yejizerolog')->insert($log);
                                                }
                                            }
                                            continue;
                                        }
                                    }else{
                                        //如果有购买记录时间，且开启了累积，需要判断用户业绩是否要清零
                                        if($mintime && $leveldata['teamfenhong_yeji_total'] && $leveldata['teamfenhong_yeji_month']>0){
                                            //没有清零月份记录且开始计算时间大于等于最小支付月份时间，或者有清零月份且开始计算时间大于清零月份的
                                            if((!$zero_month && $stime>=$mintime) || ($zero_month && $stime>$zero_month)){
                                                //添加清零记录
                                                $log = [];
                                                $log['aid']     = $aid;
                                                $log['mid']     = $parent['id'];
                                                $log['levelid'] = $parent['levelid'];
                                                $log['total_month'] = $leveldata['teamfenhong_yeji_total'];
                                                $log['zero_month']  = strtotime(date("Y-m",$etime));
                                                $log['createtime']  = time();
                                                Db::name('member_teamfenhong_yejizerolog')->insert($log);
                                            }
                                        }
                                        continue;
                                    }
                                }else{
                                    continue;
                                }
                            };
                        }

                        //var_dump($og['id']);
                        //var_dump($parent['id']);
                        $totalfenhongmoney = 0;
                        $totalfenhongscore = 0;
                        $leveldata['teamfenhong_money_dan'] = $leveldata['teamfenhong_money'];//每单奖励 230915
                        $leveldata['teamfenhong_pingji_money_dan'] = $leveldata['teamfenhong_pingji_money'];//每单奖励 230915
                        $leveldata['teamfenhong_money'] = 0;//重新赋值为0，否则按单奖励会重复计算
                        $leveldata['teamfenhong_pingji_money'] = 0;
                        if(getcustom('teamfenhong_removemax',$aid) && $k==1 && $leveldata['teamfenhong_removemax'] == 1){ //去掉一个直推业绩最高的
                            $downmemberids = Db::name('member')->where('pid',$parent['id'])->column('id');
                            $downmemberYeji = [];
                            foreach($oglist as $og2){
                                if(in_array($og2['mid'],$downmemberids)){
                                    if(!$downmemberYeji[$og2['mid']]) $downmemberYeji[$og2['mid']] = 0;
                                    $downmemberYeji[$og2['mid']] += $og2['real_totalprice'];
                                }
                            }
                            $maxyj2 = 0;
                            $maxmid2 = 0;
                            foreach($downmemberYeji as $mid2=>$yj2){
                                if($maxyj2 < $yj2){
                                    $maxmid2 = $mid2;
                                    $maxyj2 = $yj2;
                                }
                            }
                            if($maxmid2 == $og['mid']) continue;
                        }
                        if($og['module'] != 'luckycollage2'){

                            if($og['module'] == 'yuyue'){
                                $product = Db::name('yuyue_product')->where('id',$og['proid'])->find();
                            }elseif($og['module'] == 'coupon'){
                                $product = Db::name('coupon')->where('id',$og['cpid'])->find();
                            }elseif($og['module'] == 'luckycollage' || $og['module'] == 'lucky_collage'){
                                $product = Db::name('lucky_collage_product')->where('id',$og['proid'])->find();
                                if(getcustom('luckycollage_fail_commission',$aid)){
                                    if ($og['iszj'] == 2) {
                                        $product['fenhongset'] = $product['fail_fenhongset'];
                                        $product['teamfenhongset'] = $product['fail_teamfenhongset'];
                                        $product['teamfenhongdata1'] = $product['fail_teamfenhongdata1'];
                                        $product['teamfenhongdata2'] = $product['fail_teamfenhongdata2'];
                                    }
                                }
                                if ($product['fenhongset'] == 0) {
                                    $product['teamfenhongset'] = -1;
                                }
                            }elseif($og['module'] == 'scoreshop'){
                                $product = Db::name('scoreshop_product')->where('id',$og['proid'])->find();
                            }elseif($og['module'] == 'kecheng'){
                                $product = Db::name('kecheng_list')->where('id',$og['kcid'])->find();
                            }elseif($og['module'] == 'business_reward'){
                                if(getcustom('business_reward_member',$aid)){
                                    $product = [
                                        'teamfenhongpjset' => -1,
                                        'teamfenhongset' => $business_reward_set['teamfenhongset'],
                                        'teamfenhongdata1' => $business_reward_set['teamfenhongdata']
                                    ];
                                }
                            }elseif($og['module'] == 'hotel'){
                                $product = Db::name('hotel_room')->where('id',$og['roomid'])->find();
                            }elseif($og['module']=='cashier'){
                                if(getcustom('cashier_fenhong',$aid)){
                                    if($og['protype']==2){
                                        //直接收款
                                        $product = [
                                            'teamfenhongset' => 0,
                                            'teamfenhongpjset' => 0,
                                            'teamfenhongblset' => 0
                                        ];
                                    }else{
                                        $product = Db::name('shop_product')->where('id',$og['proid'])->find();
                                    }
                                }
                            }else{
                                $product = Db::name('shop_product')->where('id',$og['proid'])->find();
                            }
                            if(getcustom('maidan_fenhong',$aid) || getcustom('maidan_fenhong_new',$aid)){
                                if($og['module'] == 'maidan'){
                                    $product = [];
                                    $product['teamfenhongset']   = 0;
                                    $product['teamfenhongpjset'] = 0;
                                    if(getcustom('maidan_fenhong_new',$aid)){
                                        $business = Db::name('business')->where('id',$og['bid'])->field('maidan_team,maidan_teamfenhongdata1')->find();
                                        if($business){
                                            if($business['maidan_team'] == 2){
                                                $product['teamfenhongset'] = 1;
                                                $product['teamfenhongdata1'] = !empty($business['maidan_teamfenhongdata1'])?$business['maidan_teamfenhongdata1']:[];
                                            }else if($business['maidan_team'] == 0){
                                                $product['teamfenhongset'] = -1;
                                            }
                                        }
                                    }
                                }
                            }
                            $restaurant_fenhong_product_set_custom = getcustom('restaurant_fenhong_product_set',$aid);
                            if(getcustom('restaurant_fenhong',$aid)){
                                if($og['module'] == 'restaurant_shop' || $og['module'] == 'restaurant_takeaway'){
                                    $product = [];
                                    $product['teamfenhongset']   = 0;
                                    $product['teamfenhongpjset'] = 0;
                                    if($restaurant_fenhong_product_set_custom){
                                        $product =  Db::name('restaurant_product')->where('id',$og['proid'])->find();
                                    }
                                }
                            }
                            //商品团队分红独立设置时每单奖励也会发放
                            if($product['teamfenhongset'] == 1){ //按比例
                                $fenhongdata = json_decode($product['teamfenhongdata1'],true);
                                if($fenhongdata){
                                    $teamfenhongbl = $fenhongdata[$leveldata['id']]['commission'];
                                    if(getcustom('teamfenhong_jiantui',$aid)){
                                        //间推是否开启，开启则上二级及以上需要按间推参数计算
                                        if($k > 1 && $sysset['teamfenhong_jiantui'] && $sysset['teamfenhong_jiantui'] == 1){
                                            $teamfenhongbl = $fenhongdata[$leveldata['id']]['commission2'] >=0?$fenhongdata[$leveldata['id']]['commission2']:0;
                                        }
                                    }
                                    $leveldata['teamfenhongbl'] = $teamfenhongbl;
                                    $leveldata['teamfenhong_money'] = 0;
                                    $leveldata['teamfenhong_score_percent'] = 0;
                                }
                            }elseif($product['teamfenhongset'] == 2){ //按固定金额
                                $fenhongdata = json_decode($product['teamfenhongdata2'],true);
                                if($fenhongdata){
                                    $teamfenhong_money = $fenhongdata[$leveldata['id']]['commission'] * $og['num'];
                                    if(getcustom('teamfenhong_jiantui',$aid)){
                                        //间推是否开启，开启则上二级及以上需要按间推参数计算
                                        if($k > 1 && $sysset['teamfenhong_jiantui'] && $sysset['teamfenhong_jiantui'] == 1){
                                            $teamfenhong_money = $fenhongdata[$leveldata['id']]['commission2'] >=0?$fenhongdata[$leveldata['id']]['commission2'] * $og['num']:0;
                                        }
                                    }
                                    $leveldata['teamfenhongbl'] = 0;
                                    $leveldata['teamfenhong_money'] = $teamfenhong_money;
                                    $leveldata['teamfenhong_score_percent'] = 0;
                                }
                            }elseif($product['teamfenhongset'] == 4){ //按积分比例
                                $fenhongdata = json_decode($product['teamfenhongdata1'],true);
                                if($fenhongdata){
                                    $leveldata['teamfenhongbl'] = 0;
                                    $leveldata['teamfenhong_money'] = 0;
                                    $leveldata['teamfenhong_score_percent'] = $fenhongdata[$leveldata['id']]['score'];
                                }
                            }elseif($product['teamfenhongset'] == 6){ //按积分数量
                                $fenhongdata = json_decode($product['teamfenhongdata2'],true);
                                if($fenhongdata){
                                    $leveldata['teamfenhongbl'] = 0;
                                    $leveldata['teamfenhong_money'] = 0;
                                    $leveldata['teamfenhong_score_percent'] = 0;
                                    $leveldata['teamfenhong_score'] = $fenhongdata[$leveldata['id']]['score']* $og['num'];
                                }
                            }elseif($product['teamfenhongset'] == 7){ //提成比例+积分
                                if(getcustom('teamfenhong_percent_score',$aid)){
                                    // 提成比例
                                    $fenhongdata = json_decode($product['teamfenhongdata1'],true);
                                    if($fenhongdata){
                                        $teamfenhongbl = $fenhongdata[$leveldata['id']]['commission'];
                                        if(getcustom('teamfenhong_jiantui',$aid)){
                                            //间推是否开启，开启则上二级及以上需要按间推参数计算
                                            if($k > 1 && $sysset['teamfenhong_jiantui'] && $sysset['teamfenhong_jiantui'] == 1){
                                                $teamfenhongbl = $fenhongdata[$leveldata['id']]['commission2'] >=0?$fenhongdata[$leveldata['id']]['commission2']:0;
                                            }
                                        }
                                        $leveldata['teamfenhongbl'] = $teamfenhongbl;
                                        $leveldata['teamfenhong_money'] = 0;
                                        $leveldata['teamfenhong_score_percent'] = 0;
                                    }
                                    // 积分
                                    $fenhongdata = json_decode($product['teamfenhongdata2'],true);
                                    if($fenhongdata){
                                        // $leveldata['teamfenhongbl'] = 0;
                                        $leveldata['teamfenhong_money'] = 0;
                                        $leveldata['teamfenhong_score_percent'] = 0;
                                        $leveldata['teamfenhong_score'] = $fenhongdata[$leveldata['id']]['score']* $og['num'];
                                    }
                                }
                                
                            }elseif($product['teamfenhongset'] == 8){ //提成金额+积分

                                if(getcustom('teamfenhong_commission_score',$aid)){
                                    //提成金额
                                    $fenhongdata = json_decode($product['teamfenhongdata2'],true);
                                    if($fenhongdata){
                                        $teamfenhong_money = $fenhongdata[$leveldata['id']]['commission'] * $og['num'];
                                        if(getcustom('teamfenhong_jiantui',$aid)){
                                            //间推是否开启，开启则上二级及以上需要按间推参数计算
                                            if($k > 1 && $sysset['teamfenhong_jiantui'] && $sysset['teamfenhong_jiantui'] == 1){
                                                $teamfenhong_money = $fenhongdata[$leveldata['id']]['commission2'] >=0?$fenhongdata[$leveldata['id']]['commission2'] * $og['num']:0;
                                            }
                                        }
                                        $leveldata['teamfenhongbl'] = 0;
                                        $leveldata['teamfenhong_money'] = $teamfenhong_money;
                                        $leveldata['teamfenhong_score_percent'] = 0;
                                    }
                                    // 积分
                                    $fenhongdata = json_decode($product['teamfenhongdata2'],true);
                                    if($fenhongdata){
                                        $leveldata['teamfenhongbl'] = 0;
                                        // $leveldata['teamfenhong_money'] = 0;
                                        $leveldata['teamfenhong_score_percent'] = 0;
                                        $leveldata['teamfenhong_score'] = $fenhongdata[$leveldata['id']]['score']* $og['num'];
                                    }
                                }
                            }elseif($product['teamfenhongset'] == -1){
                                $leveldata['teamfenhongbl'] = 0;
                                $leveldata['teamfenhong_money'] = 0;
                                $leveldata['teamfenhong_score_percent'] = 0;
                            }else{
                                if(getcustom('teamfenhong_jiantui',$aid)){
                                    //间推是否开启，开启则上二级及以上需要按间推参数计算
                                    if($k > 1 && $sysset['teamfenhong_jiantui'] && $sysset['teamfenhong_jiantui'] == 1){
                                         $leveldata['teamfenhongbl'] = $leveldata['teamfenhong_jiantui_percent'];
                                    }
                                }
                            }
                            if($teamfenhong_wuxian){
                                //无限层级团队分红
                                if($product['teamfenhongset']==5){
                                    $fenhongdata = json_decode($product['teamfenhongdata1'],true);
                                    $fenhongprice = $fenhongdata['commission']*$og['num'];
                                    $leveldata['teamfenhongbl'] = $fenhongdata['bili'];
                                    $leveldata['teamfenhonglv'] = $fenhongdata['lv'];
                                }
                            }
                            $totalfenhongmoney += $leveldata['teamfenhong_money'];

                            $leveldata['teamfenhong_pingji_score'] = 0;
                            //平级独立设置
                            if($product['teamfenhongpjset'] == 1){ //按比例
                                $fenhongpjdata = json_decode($product['teamfenhongpjdata1'],true);
                                if($fenhongpjdata){
                                    $leveldata['teamfenhong_pingji_bl'] = $fenhongpjdata[$leveldata['id']]['commission'];
                                    $leveldata['teamfenhong_pingji_money'] = 0;
                                    $leveldata['teamfenhong_pingji_score_percent'] = 0;
                                }
                            }elseif($product['teamfenhongpjset'] == 2){ //按固定金额
                                $fenhongpjdata = json_decode($product['teamfenhongpjdata2'],true);
                                if($fenhongpjdata){
                                    $leveldata['teamfenhong_pingji_bl'] = 0;
                                    $leveldata['teamfenhong_pingji_money'] = $fenhongpjdata[$leveldata['id']]['commission'] * $og['num'];
                                    $leveldata['teamfenhong_pingji_score_percent'] = 0;
                                }
                            }elseif($product['teamfenhongpjset'] == 4){ //按积分比例
                                $fenhongpjdata = json_decode($product['teamfenhongpjdata1'],true);
                                if($fenhongpjdata){
                                    $leveldata['teamfenhong_pingji_bl'] = 0;
                                    $leveldata['teamfenhong_pingji_money'] = 0;
                                    $leveldata['teamfenhong_pingji_score_percent'] = $fenhongpjdata[$leveldata['id']]['score'];
                                }
                            }elseif($product['teamfenhongpjset'] == 3){ //按积分
                                if(getcustom('teamfenhong_pingji_score',$aid)){
                                    $fenhongpjdata = json_decode($product['teamfenhongpjdata2'],true);
                                    if($fenhongpjdata){
                                        $leveldata['teamfenhong_pingji_bl'] = 0;
                                        $leveldata['teamfenhong_pingji_money'] = 0;
                                        $leveldata['teamfenhong_pingji_score_percent'] = 0;
                                        $leveldata['teamfenhong_pingji_score'] = $fenhongpjdata[$leveldata['id']]['score'] * $og['num'];;
                                    }
                                }
                            }elseif($product['teamfenhongpjset'] == 7){ //提成比例+积分
                                if(getcustom('teamfenhong_pingji_percent_score',$aid)){
                                    $fenhongpjdata = json_decode($product['teamfenhongpjdata1'],true);
                                    if($fenhongpjdata){
                                        $leveldata['teamfenhong_pingji_bl'] = $fenhongpjdata[$leveldata['id']]['commission'];
                                        $leveldata['teamfenhong_pingji_money'] = 0;
                                        $leveldata['teamfenhong_pingji_score_percent'] = 0;
                                    }
                                    $fenhongpjdata2 = json_decode($product['teamfenhongpjdata2'],true);
                                    if($fenhongpjdata2){
                                        $leveldata['teamfenhong_pingji_score'] = $fenhongpjdata2[$leveldata['id']]['score'] * $og['num'];;
                                    }
                                }
                            }elseif($product['teamfenhongpjset'] == 8){ //提成金额+积分
                                if(getcustom('teamfenhong_pingji_commission_score',$aid)){
                                    $fenhongpjdata = json_decode($product['teamfenhongpjdata2'],true);
                                    if($fenhongpjdata){
                                        $leveldata['teamfenhong_pingji_bl'] = 0;
                                        $leveldata['teamfenhong_pingji_money'] = $fenhongpjdata[$leveldata['id']]['commission'] * $og['num'];
                                        $leveldata['teamfenhong_pingji_score_percent'] = 0;
                                    }
                                    $fenhongpjdata2 = json_decode($product['teamfenhongpjdata2'],true);
                                    if($fenhongpjdata2){
                                        $leveldata['teamfenhong_pingji_score'] = $fenhongpjdata2[$leveldata['id']]['score'] * $og['num'];;
                                    }
                                }
                            }elseif($product['teamfenhongpjset'] == -1){
                                $leveldata['teamfenhong_pingji_bl'] = 0;
                                $leveldata['teamfenhong_pingji_money'] = 0;
                                $leveldata['teamfenhong_pingji_score_percent'] = 0;
                            }
                            
                            //团队分红伯乐奖参数
                            if(getcustom('teamfenhong_bole',$aid)){
                                if($product['teamfenhongblset'] == 1){ //按比例
                                    $fenhongbldata = json_decode($product['teamfenhongbldata1'],true);
                                    if($fenhongbldata){
                                        $leveldata['teamfenhong_bole_bl'] = $fenhongbldata[$leveldata['id']]['commission'];
                                        $leveldata['teamfenhong_bole_bl_tuoli'] = $fenhongbldata[$leveldata['id']]['commission_tuoli'];
                                        $leveldata['teamfenhong_bole_money'] = 0;
                                        $leveldata['teamfenhong_bole_money_tuoli'] = 0;
                                    }
                                }elseif($product['teamfenhongblset'] == 2){ //按固定金额
                                    $fenhongbldata = json_decode($product['teamfenhongbldata2'],true);
                                    if($fenhongbldata){
                                        $leveldata['teamfenhong_bole_bl'] = 0;
                                        $leveldata['teamfenhong_bole_bl_tuoli'] = 0;
                                        $leveldata['teamfenhong_bole_money'] = $fenhongbldata[$leveldata['id']]['commission'] * $og['num'];
                                        $leveldata['teamfenhong_bole_money_tuoli'] = $fenhongbldata[$leveldata['id']]['commission_tuoli'] * $og['num'];
                                    }
                                }elseif($product['teamfenhongblset'] == -1){//不参与
                                    $leveldata['teamfenhong_bole_bl'] = 0;
                                    $leveldata['teamfenhong_bole_bl_tuoli'] = 0;
                                    $leveldata['teamfenhong_bole_money'] = 0;
                                    $leveldata['teamfenhong_bole_money_tuoli'] = 0;
                                }
                            }
                            $teamfenhong_bole_custom = getcustom('teamfenhong_bole',$aid);
                            if(getcustom('maidan_fenhong_new',$aid) && $og['module']=='maidan'){
                                if($product['teamfenhongset'] == 0){
                                    //买单分红单独设置的提成比例
                                    $leveldata['teamfenhongbl'] = $leveldata['teamfenhongbl_maidan'];
                                    $leveldata['teamfenhong_pingji_bl'] = $leveldata['teamfenhong_pingji_bl_maidan'];
                                    if(getcustom('teamfenhong_jiantui',$aid)){
                                        //间推是否开启，开启则上二级及以上需要按间推参数计算
                                        if($k > 1 && $sysset['teamfenhong_jiantui'] && $sysset['teamfenhong_jiantui'] == 1){
                                             $leveldata['teamfenhongbl'] = $leveldata['teamfenhong_jiantui_percent'];
                                        }
                                    }
                                }
                                if($teamfenhong_bole_custom){
                                    $leveldata['teamfenhong_bole_bl'] = $leveldata['teamfenhong_bole_bl_maidan'];
                                }
                            }
                            if(!getcustom('teamfenhong_pingji_level_follow',$aid)){
                                $follow_leveldata = $leveldata;
                            }
                        }
                        if(getcustom('teamfenhong_lv1_limit',$aid)){
                            //等级A/B/C直推等级D/E/F，购买人为D/E/F，等级A/B/C不发一级团队分红（其他二级三级等正常发）
                            if($product['teamfenhong_lv1_limit']==1){
                                if($bonus_i==0 && $product && $product['teamfenhonglimit']){
                                    $teamfenhonglimit_arr = json_decode($product['teamfenhonglimit'],true);
                                    $teamfenhonglimit = $teamfenhonglimit_arr[$parent['levelid']];
                                    $teamfenhonglimit_levelid = [];
                                    if($teamfenhonglimit){
                                        $teamfenhonglimit_levelid = explode(',',$teamfenhonglimit);
                                    }
                                    if(empty($teamfenhonglimit_levelid) || !in_array($member['levelid'],$teamfenhonglimit_levelid)){
                                        $bonus_i++;
                                        continue;
                                    }
                                }
                            }
                        }
                        $hasfhlevelids[] = $parent['levelid'];
                        $teamfenhong_money_dan_product = 1;
                        if($teamfenhong_money_product_status){
                            //产品单独设置的团队分红要判断级别是否设置了每单分红可参与
                            if($product['teamfenhongpjset']!=0){
                                $teamfenhong_money_dan_product = $leveldata['teamfenhong_money_product']?:0;
                            }
                        }

                        if($teamfhStatus){
                            //每单奖励
                            if($leveldata['teamfenhong_money_dan'] > 0 && !in_array($og['orderid'],$teamfenhong_orderids[$parent['id']]) && $teamfenhong_money_dan_product==1) {
                                $totalfenhongmoney += $leveldata['teamfenhong_money_dan'];
                                $teamfenhong_orderids[$parent['id']][] = $og['orderid'];
                            }

                            if($totalfenhongmoney < 0) $totalfenhongmoney = 0;
                            //分红金额可设置扣除分销佣金后进行分红
                            if(getcustom('fenhong_removefenxiao',$aid) && $leveldata['teamfenhong_removefenxiao'] == 1 && (($isjicha && $last_teamfenhongbl == 0) || !$isjicha)){
                                $fxcommission = 0;
                                if($og['parent1'] && $og['parent1commission']){
                                    $fxcommission = $fxcommission + $og['parent1commission'];
                                }
                                if($og['parent2'] && $og['parent2commission']){
                                    $fxcommission = $fxcommission + $og['parent2commission'];
                                }
                                if($og['parent3'] && $og['parent3commission']){
                                    $fxcommission = $fxcommission + $og['parent3commission'];
                                }
                                if($fxcommission > 0){
                                    if($isjicha){
                                        $last_teamfenhongbl = $fxcommission / $fenhongprice*100;
                                    }else{
                                        $leveldata['teamfenhongbl'] = $leveldata['teamfenhongbl'] - $fxcommission / $fenhongprice*100;
                                        if($leveldata['teamfenhongbl'] < 0) $leveldata['teamfenhongbl'] = 0;
                                    }
                                }
                            }
                            //var_dump('teamfenhongbl:'.$leveldata['teamfenhongbl']);
                            //var_dump('teamfenhong_money:'.$leveldata['teamfenhong_money']);
                            //var_dump('$totalfenhongmoney:'.$totalfenhongmoney);
                            //分红比例
                            if($leveldata['teamfenhongbl'] > 0) {
                                if(!$teamfenhong_wuxian){//无限层级团队分红
                                    $this_teamfenhongbl = $leveldata['teamfenhongbl'];
                                    if($this_teamfenhongbl <=0) $this_teamfenhongbl = 0;
                                    $last_teamfenhongbl = $last_teamfenhongbl + $this_teamfenhongbl;
                                    if(getcustom('teamfenhong_jiantui',$aid)){
                                        //间推是否开启，开启则上级及以上需要按间推参数计算：上二级之前极差为0 (k=0自己，k=1上级，k=2上二级)
                                        if($k <= 1 && $sysset['teamfenhong_jiantui'] && $sysset['teamfenhong_jiantui'] == 1){
                                            $last_teamfenhongbl = 0;
                                            $last_teamfenhongmoney = 0;
                                        }
                                    }
                                    $totalfenhongmoney = $totalfenhongmoney + $this_teamfenhongbl * $fenhongprice * 0.01;

                                }else{
                                    //无限级团队分红 奖励=（奖励金额-上级累计奖励）*比例
                                    $cacl_fenhongmoney = bcmul(bcsub($fenhongprice,$last_teamfenhongmoney,$commission_weishu),$leveldata['teamfenhongbl']/100,$commission_weishu);
                                    //$last_teamfenhongmoney = bcadd($last_teamfenhongmoney,$cacl_fenhongmoney,$commission_weishu);
                                    if($cacl_fenhongmoney<0.01 || ($leveldata['teamfenhonglv']>0 && $level_i>$leveldata['teamfenhonglv'])){
                                        continue;
                                    }
                                    $totalfenhongmoney = bcadd($totalfenhongmoney,$cacl_fenhongmoney,$commission_weishu);
                                }
                            }
                            //统一改成最后按总金额计算级差,不再计算比例的级差 20250424
                            if($isjicha){
                                $totalfenhongmoney = $totalfenhongmoney - $last_teamfenhongmoney;
                            }else{
                                $totalfenhongmoney = $totalfenhongmoney;
                            }
                            if($totalfenhongmoney<0){
                                $totalfenhongmoney = 0;
                            }
                            //分红积分比例
                            if($leveldata['teamfenhong_score_percent'] > 0) {
                                if($isjicha){
                                    $this_teamfenhong_score_percent = $leveldata['teamfenhong_score_percent'] - $last_teamfenhong_score_percent;
                                }else{
                                    $this_teamfenhong_score_percent = $leveldata['teamfenhong_score_percent'];
                                }
                                if($this_teamfenhong_score_percent <=0) $this_teamfenhong_score_percent = 0;
                                $last_teamfenhong_score_percent = $last_teamfenhong_score_percent + $this_teamfenhong_score_percent;

                                $totalfenhongscore = $totalfenhongscore + round($this_teamfenhong_score_percent * $fenhongprice * 0.01);
                            }
                            //分红积分数量
                            if($leveldata['teamfenhong_score'] > 0) {
                                if($isjicha){
                                    $teamfenhong_score = $leveldata['teamfenhong_score'] - $last_teamfenhong_score_percent;
                                }else{
                                    $teamfenhong_score = $leveldata['teamfenhong_score'];
                                }
                                if($teamfenhong_score <=0) $teamfenhong_score = 0;
                                $last_teamfenhong_score_percent = $last_teamfenhong_score_percent + $teamfenhong_score;

                                $totalfenhongscore = $totalfenhongscore + $teamfenhong_score;
                            }

                            //最后一次累计 极差计算用
                            $last_teamfenhongmoney = $last_teamfenhongmoney + $totalfenhongmoney;
                            if($totalfenhongmoney>0){
                                //减平级奖部分放在累计级差后面，累计级差只算团队分红的哪怕实际发放团队分红减掉了平级奖部分也要算进累计级差
                                $totalfenhongmoney = bcsub($totalfenhongmoney,$last_teamfenhongmoney_pj_total,$commission_weishu);
                                $last_teamfenhongmoney_pj_total = 0;//减完平级奖之后重置为0，防止上级所有的会员都重复减
                            }
                            if(getcustom('teamfenhong_jiantui',$aid)){
                                //间推是否开启，开启则上级及以上需要按间推参数计算，且上二级之前极差为0 (k=0自己，k=1上级，k=2上二级)
                                if($k <= 1 && $sysset['teamfenhong_jiantui'] && $sysset['teamfenhong_jiantui'] == 1){
                                    $last_teamfenhongmoney = 0;
                                }
                            }
                            $last_totalfenhongmoney = $totalfenhongmoney;

                            //var_dump('$totalfenhongmoney:'.$totalfenhongmoney);
                            if($totalfenhongmoney > 0 || $totalfenhongscore > 0){
                                $bonus_i++;
                                if($isyj == 1 && $yjmid == $parent['id']){
                                    $commissionyj_my += $totalfenhongmoney;
                                }
                                if($commissionpercent != 1){
                                    $fenhongcommission = round($totalfenhongmoney*$commissionpercent,$commission_weishu);
                                    $fenhongmoney = round($totalfenhongmoney*$moneypercent,2);
                                    $fenhongscore = $totalfenhongscore;
                                }else{
                                    $fenhongcommission = $totalfenhongmoney;
                                    $fenhongmoney = 0;
                                    $fenhongscore = $totalfenhongscore;
                                }
                                //分红最大不超过
                                if(getcustom('teamfenhong_max',$aid)){
                                    $total_fafang_commission = $total_fafang_commission+$fenhongcommission;
                                    $teamfenhong_max_type = Db::name('admin_set')->where('aid',$aid)->value('teamfenhong_max_type');
                                    if($teamfenhong_max_type==1 && $total_fafang_commission >=$og['real_totalprice']){//不超过订单金额
                                        continue;
                                    }
                                }
                                if($teamfenhong_product_wallet){
                                    //团队分红分钱包发放
                                    $product = Db::name('shop_product')->where('id',$og['proid'])->field('teamfenhongwalletset,teamfenhongwallet')->find();
                                    if($product['teamfenhongwalletset']){
                                        $teamfenhongwallet = json_decode($product['teamfenhongwallet'],true);
                                        $commission_wallet_bili = $teamfenhongwallet['commission']?:0;
                                        $score_wallet_bili = $teamfenhongwallet['score']?:0;
                                        $money_wallet_bili = $teamfenhongwallet['money']?:0;
                                        $fuchi_wallet_bili = $teamfenhongwallet['fuchi']?:0;
                                        $fenhongcommission = bcmul($totalfenhongmoney,$commission_wallet_bili/100,$commission_weishu);
                                        $fenhongscore = bcadd($fenhongscore,bcmul($totalfenhongmoney,$score_wallet_bili/100,2),2);
                                        $fenhongmoney = bcmul($totalfenhongmoney,$money_wallet_bili/100,2);
                                        $fenhongfuchi = bcmul($totalfenhongmoney,$fuchi_wallet_bili/100,2);
                                    }
                                }
                                //dump([$k,$member]);
                                if($midteamfhArr[$parent['id']]){
                                    $midteamfhArr[$parent['id']]['totalcommission'] = $midteamfhArr[$parent['id']]['totalcommission'] + $totalfenhongmoney;
                                    $midteamfhArr[$parent['id']]['commission'] = $midteamfhArr[$parent['id']]['commission'] + $fenhongcommission;
                                    $midteamfhArr[$parent['id']]['money'] = $midteamfhArr[$parent['id']]['money'] + $fenhongmoney;
                                    $midteamfhArr[$parent['id']]['score'] = $midteamfhArr[$parent['id']]['score'] + $fenhongscore;
                                    $midteamfhArr[$parent['id']]['ogids'][] = $og['id'];
                                    $midteamfhArr[$parent['id']]['levelid'] = $parent['levelid'];
                                    $midteamfhArr[$parent['id']]['downMember'] = $k > 1 ? $parentList[$k-1] : $member;
                                    if($teamfenhong_product_wallet){
                                        $midteamfhArr[$parent['id']]['fuchi'] = $midteamfhArr[$parent['id']]['fuchi'] + $fenhongfuchi;
                                    }
                                    $midteamfhArr[$parent['id']]['module'] = $og['module'] ?? 'shop';
                                }else{
                                    $midteamfhArr[$parent['id']] = [
                                        'totalcommission'=>$totalfenhongmoney,
                                        'commission'=>$fenhongcommission,
                                        'money'=>$fenhongmoney,
                                        'score'=>$fenhongscore,
                                        'ogids'=>[$og['id']],
                                        'module'=>$og['module'] ?? 'shop',
                                        'levelid' => $parent['levelid'],
                                        'type' => '团队分红',
                                        'downMember' => $k > 1 ? $parentList[$k-1] : $member
                                    ];
                                    if($teamfenhong_product_wallet){
                                        $midteamfhArr[$parent['id']]['fuchi'] = $fenhongfuchi;
                                    }
                                }
                                //dump($parent['id'].'('.$leveldata['name'].')获得团队分红'.$totalfenhongmoney);
                                if(getcustom('teamfenhong_share',$aid)){
                                    $member_orign_parent = self::get_pid_origin_bylog($aid,$member['id'],$parent['id'],$og['paytime'],$defaultLevelIds);
                                    if($member_orign_parent !== false) $midteamfhArr[$parent['id']]['downMember'] = $member_orign_parent;
                                }
                                if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                    self::fhrecord($aid,$parent['id'],$fenhongcommission,$fenhongscore,$og['id'],$og['module'] ?? 'shop','teamfenhong',t('团队分红',$aid));
                                }

                                $last_plevelid = $parent['levelid'];

                            }
                        }


                        //平级奖 找最近的上级
                        if(getcustom('teamfenhong_pingji',$aid)){

                            //teamfenhong_pingji_type:0按奖励金额,1按订单金额
                            $last_teamfenhongbl_pj = 0;
                            $last_teamfenhongmoney_pj = 0;
                            $last_teamfenhong_score_percent_pj = 0;
                            $last_teamfenhong_score_pj = 0;
                            $last_teamfenhong_pj = 0;//级差奖励金额，新增的teamfenhong_pingji_fenhong要累计前面的分红+平级奖用来计算，直接用级差比例的话会丢失奖励
                            $levelSort = [];
                            
                            if($follow_leveldata['teamfenhong_pingji_lv']>0 && ($follow_leveldata['teamfenhong_pingji_bl']>0 || $follow_leveldata['teamfenhong_pingji_money'] > 0 || $follow_leveldata['teamfenhong_pingji_money_dan'] > 0 || $follow_leveldata['teamfenhong_pingji_score_percent'] > 0  || $teamfenhong_pingji_single_bl_set>0 || $follow_leveldata['teamfenhong_pingji_score'] > 0) && ($totalfenhongmoney > 0 || $follow_leveldata['teamfenhong_pingji_type'] == 1)){
                                 $last_pingji_levelid = $plevelid;//上一个拿平级奖的会员id
                                $pingji_yueji_bonus = 0;
                                $parentList_pj = $parentList;

                                if(getcustom('teamfenhong_pingji_origin',$aid) && $follow_leveldata['teamfenhong_pingji_origin']>0){
                                     //平级奖发给原上级
                                     foreach($parentList_pj as $pj_k=>$pj_v){
                                         if($pj_v['id']=$parent['id']){
                                             $parent_origin = [];
                                             if($pj_v['pid_origin']){
                                                 $parent_origin = Db::name('member')->where('id',$pj_v['pid_origin'])->find();
                                             }
                                             if($follow_leveldata['teamfenhong_pingji_origin']==1){
                                                 //优先发放原上级
                                                 if($parent_origin){
                                                     $parentList_pj[$pj_k+1] = $parent_origin;
                                                 }
                                             }
                                             if($follow_leveldata['teamfenhong_pingji_origin']==2){
                                                 //仅发放原上级，没有原上级不发放
                                                 $parentList_pj[$pj_k+1] = $parent_origin;
                                             }
                                         }
                                     }
                                }
                                
                                $follow_i = 1;
                                foreach($parentList_pj as $k2=>$parent2){
                                    //dump('上级'.$parent['id'].'=>'.$parent2['id'].'平级奖开始');
                                    if($teamfenhong_pingji_zhitui){
                                        //平级奖只发给下单人的直推
                                        if($parent['id']!=$member['id'] || $k2>1){
                                            break;
                                        }
                                    }
                                    $parent2Level = $teamfhlevellist[$parent2['levelid']];
                                    $p2_levelid = $parent2['levelid'];
                                    // 跟随等级
                                    if(getcustom('teamfenhong_pingji_level_follow',$aid)){
                                        if($parent2Level['follow_levelid']){
                                            $p2_levelid = $parent2Level['follow_levelid'];
                                            $parent2Level = $teamfhlevellist[$parent2Level['follow_levelid']];
                                        }
                                        $jinsuo = true;
                                        // 紧缩关闭
                                        if($parent2Level['follow_level_jinsuo_status'] == 0){
                                            $jinsuo = false;
                                        }
                                    }
                                   
                                    $levelSort[] = $parent2Level['sort'];
                                    //开启越级限制，如果当前会员级别不等于上一个会员级别就不再拿奖
                                    if($k2 > $k){
                                        if($yueji_limit && $last_pingji_levelid>0 && $parent2['levelid']!=$last_pingji_levelid){
                                            break;
                                        }
                                    }

                                    if($k2 > $k && $pingji_yueji_bonus_status==1){
                                        //越级拿平级奖
                                        if($parent2Level['sort'] > $follow_leveldata['sort']){
                                            $pingji_yueji_bonus = 1;
                                        }
                                    }
                                    if($k2 > $k && $pingji_diji_bonus_status==1){
                                        //越级拿平级奖
                                        if($parent2Level['sort'] < $follow_leveldata['sort']){
                                            $pingji_yueji_bonus = 1;
                                        }
                                    }
                                    if(getcustom('teamfenhong_pingji_num',$aid)){
                                        //每个等级有限制平级奖拿奖人数，人数达到后该等级就不发放平级奖
                                        if(empty($pj_nums[$parent2['levelid']])){
                                            $pj_nums[$parent2['levelid']] = 0;
                                        }
                                        //dump($parent2['levelid'].'=>'.$parent2Level['teamfenhong_pingji_num'].'=>'.$pj_nums[$parent2['levelid']]);
                                        if($parent2Level['teamfenhong_pingji_num']<= $pj_nums[$parent2['levelid']]){
                                            continue;
                                        }
                                    }

                                    if($k2 > $k && ($parent2['levelid'] == $plevelid || $pingji_yueji_bonus==1 || $p2_levelid == $plevelid || (getcustom('teamfenhong_pingji_level_follow',$aid) && $jinsuo == false))){
                                        if($pingji_yueji_status != 1){
                                            //团队分红平级奖越级，关闭后团队中间有人越级不发奖
                                            rsort($levelSort);
                                            if($parent2Level['sort'] < $levelSort[0]){
                                                break;
                                            }
                                        }
                                        $teamfenhong_pingji_bl = $follow_leveldata['teamfenhong_pingji_bl'];
                                        $teamfenhong_pingji_money = $follow_leveldata['teamfenhong_pingji_money'];
                                        if($teamfenhong_pingji_single_bl_set){
                                            //独立设置团队分红平级奖
                                            if(empty($midteamfhArr[$parent['id']]['totalcommission']) && $parent['id']!=$og['mid'] && $sysset['teamfenhong_pingji_parent_limit']==1){
                                                //上级没有团队分红时不发放平级奖
                                                continue;
                                            }
                                            //单独设置平1、2、3级比例
                                            if($haspingjinumArr[$plevelid]){
                                                $lv = $haspingjinumArr[$plevelid] + 1;
                                            }else{
                                                $lv = 1;
                                            }

                                            $teamfenhong_pingji_single_bl = json_decode($follow_leveldata['teamfenhong_pingji_single_bl'],true);
                                            $teamfenhong_pingji_single_money = json_decode($follow_leveldata['teamfenhong_pingji_single_money'],true);
                                            if(!empty($teamfenhong_pingji_single_bl[$lv])){
                                                $teamfenhong_pingji_bl = $teamfenhong_pingji_single_bl[$lv];
                                            }
                                            if(!empty($teamfenhong_pingji_single_money[$lv])){
                                                $teamfenhong_pingji_money = $teamfenhong_pingji_single_money[$lv];
                                            }
                                        }

                                        //暂时关闭 等级比例优先级低于商品独立设置
                                        //if($isjicha && $sysset['teamfenhong_differential_pj'] == 1 && $teamfhlevellist[$parent2['levelid']]['teamfenhong_pingji_bl'] > $follow_leveldata['teamfenhong_pingji_bl']) break;
                                        if($isjicha && $sysset['teamfenhong_differential_pj'] == 1){
                                            if(!$teamfenhong_pingji_fenhong){
                                                $this_teamfenhongbl_pj = $teamfenhong_pingji_bl - $last_teamfenhongbl_pj;
                                            }else{
                                                $this_teamfenhongbl_pj = $teamfenhong_pingji_bl;
                                            }
                                            $this_teamfenhongmoney_pj = $teamfenhong_pingji_money - $last_teamfenhongmoney_pj;
                                            $this_teamfenhong_score_percent_pj = $follow_leveldata['teamfenhong_pingji_score_percent'] - $last_teamfenhong_score_percent_pj;
                                            $this_teamfenhongscore_pj = $follow_leveldata['teamfenhong_pingji_score'] - $last_teamfenhong_score_pj;
                                        }else{
                                            $this_teamfenhongbl_pj = $teamfenhong_pingji_bl;
                                            $this_teamfenhongmoney_pj = $teamfenhong_pingji_money;
                                            $this_teamfenhong_score_percent_pj = $follow_leveldata['teamfenhong_pingji_score_percent'];
                                            $this_teamfenhongscore_pj = $follow_leveldata['teamfenhong_pingji_score'];
                                        }
                                        
                                        if($this_teamfenhongbl_pj <=0) $this_teamfenhongbl_pj = 0;
                                        if($this_teamfenhongmoney_pj <=0) $this_teamfenhongmoney_pj = 0;
                                        if($this_teamfenhong_score_percent_pj <=0) $this_teamfenhong_score_percent_pj = 0;
                                        if($this_teamfenhongscore_pj <=0) $this_teamfenhongscore_pj = 0;
                                        if($this_teamfenhongbl_pj == 0 && $this_teamfenhongmoney_pj == 0 && $this_teamfenhong_score_percent_pj == 0 && $follow_leveldata['teamfenhong_pingji_money_dan']==0 && $this_teamfenhongscore_pj == 0) continue;
                                        $last_teamfenhongbl_pj = $last_teamfenhongbl_pj + $this_teamfenhongbl_pj;

                                        $last_teamfenhongmoney_pj = $last_teamfenhongmoney_pj + $this_teamfenhongmoney_pj;
                                        $last_teamfenhong_score_percent_pj = $last_teamfenhong_score_percent_pj + $this_teamfenhong_score_percent_pj;
                                        $last_teamfenhong_score_pj = $last_teamfenhong_score_pj + $this_teamfenhongscore_pj;

                                        $totalfenhongmoney_pj = 0;
                                        $totalfenhongscore_pj = 0;
                                        if($this_teamfenhongbl_pj>0){
                                            if($follow_leveldata['teamfenhong_pingji_type'] == 0){
                                                //按奖励金额
                                                if($teamfenhong_money_product_status && $teamfenhong_money_dan_product==1 ){
                                                    //等级分销单独设置的团队分红每单分红金额不参与平级奖计算
                                                    $totalfenhongmoney = bcsub($totalfenhongmoney,$follow_leveldata['teamfenhong_money_dan'],2);
                                                }
                                                $totalfenhongmoney_pj += $this_teamfenhongbl_pj * $totalfenhongmoney * 0.01;
                                            }else{
                                                //按订单金额
                                                $totalfenhongmoney_pj += $this_teamfenhongbl_pj * $fenhongprice * 0.01;
                                            }
                                        }
                                        if($isjicha && $sysset['teamfenhong_differential_pj'] == 1 && $teamfenhong_pingji_fenhong){
                                            $totalfenhongmoney_pj = bcsub($totalfenhongmoney_pj , $last_teamfenhong_pj,2);
                                        }

                                        if($this_teamfenhongbl_pj == 0 && $this_teamfenhongmoney_pj == 0 && $this_teamfenhong_score_percent_pj == 0 && $follow_leveldata['teamfenhong_pingji_money_dan']==0 && $this_teamfenhongscore_pj == 0) continue;
                                        if($isjicha && $sysset['teamfenhong_differential_pj'] == 1) {
                                            if($teamfenhong_pingji_fenhong){
                                                $last_teamfenhong_pj = $last_teamfenhong_pj + $totalfenhongmoney_pj;
                                            }
                                        }

                                        if($haspingjinumArr[$plevelid]){
                                            $haspingjinumArr[$plevelid]++;
                                        }else{
                                            $haspingjinumArr[$plevelid] = 1;
                                        }
                                        if($haspingjinumArr[$plevelid] > $follow_leveldata['teamfenhong_pingji_lv']) break;
                                        if($product['teamfenhongpjset'] == 0){
                                            //按会员等级，按总订单发放一次平级奖
                                            if(($this_teamfenhongmoney_pj > 0 || $follow_leveldata['teamfenhong_pingji_money_dan'] > 0) && !in_array($og['orderid'],$teamfenhong_orderids_pj[$parent2['id']])){
                                                $this_teamfenhongmoney_pj += $follow_leveldata['teamfenhong_pingji_money_dan'];//230915 每单奖励
                                                $totalfenhongmoney_pj += $this_teamfenhongmoney_pj;
                                                if($totalfenhongmoney_pj < 0) $totalfenhongmoney_pj = 0;
                                                $teamfenhong_orderids_pj[$parent2['id']][] = $og['orderid'];
                                            }
                                        }else{
                                            //产品单独设置参数时，按分订单发放多次平级奖
                                            if($this_teamfenhongmoney_pj > 0 && !in_array($og['id'],$teamfenhong_orderids_pj[$parent2['id']])){
                                                $totalfenhongmoney_pj += $this_teamfenhongmoney_pj;
                                                if($totalfenhongmoney_pj < 0) $totalfenhongmoney_pj = 0;
                                                $teamfenhong_orderids_pj[$parent2['id']][] = $og['id'];
                                            }
                                        }
                                        if($this_teamfenhong_score_percent_pj > 0){
                                            if($follow_leveldata['teamfenhong_pingji_type'] == 0){
                                                //按奖励金额
                                                $totalfenhongscore_pj = round($this_teamfenhong_score_percent_pj * $totalfenhongscore * 0.01);
                                            }else{
                                                //按订单金额
                                                $totalfenhongscore_pj = round($this_teamfenhong_score_percent_pj * $fenhongprice * 0.01);
                                            }
                                        }
                                        if($this_teamfenhongscore_pj > 0){
                                            $totalfenhongscore_pj = $this_teamfenhongscore_pj;
                                        }
                                        if($totalfenhongmoney_pj > 0 || $totalfenhongscore_pj > 0){
                                           
                                            if($teamfenhong_jicha_add_pj){
                                                //团队分红级差减掉平级奖 下一轮计算出团队分红减，代码位置在前面
                                                $last_teamfenhongmoney_pj_total = bcadd($last_teamfenhongmoney_pj_total,$totalfenhongmoney_pj,$commission_weishu);
                                            }
                                            if($isyj == 1 && $yjmid == $parent2['id']){
                                                $commissionyj_my += $totalfenhongmoney_pj;
                                                $og['pj_money'] = $totalfenhongmoney_pj;
                                            }
                                            if($commissionpercent != 1){
                                                $fenhongcommission = round($totalfenhongmoney_pj*$commissionpercent,$commission_weishu);
                                                $fenhongmoney = round($totalfenhongmoney_pj*$moneypercent,2);
                                                $fenhongscore = round($totalfenhongscore_pj*$commissionpercent);
                                            }else{
                                                $fenhongcommission = $totalfenhongmoney_pj;
                                                $fenhongmoney = 0;
                                                $fenhongscore = $totalfenhongscore_pj;
                                            }
                                            //平级奖独立发放
                                            if($midteamfhArrPj[$parent2['id']]){
                                                if(getcustom('teamfenhong_pingji_level_follow',$aid)){
                                                    continue;
                                                }
                                                $midteamfhArrPj[$parent2['id']]['totalcommission'] = $midteamfhArrPj[$parent2['id']]['totalcommission'] + $totalfenhongmoney_pj;
                                                $midteamfhArrPj[$parent2['id']]['commission'] = $midteamfhArrPj[$parent2['id']]['commission'] + $fenhongcommission;
                                                $midteamfhArrPj[$parent2['id']]['money'] = $midteamfhArrPj[$parent2['id']]['money'] + $fenhongmoney;
                                                $midteamfhArrPj[$parent2['id']]['score'] = $midteamfhArrPj[$parent2['id']]['score'] + $fenhongscore;
                                                $midteamfhArrPj[$parent2['id']]['ogids'][] = $og['id'];
                                                $midteamfhArrPj[$parent2['id']]['levelid'] = $parent2['levelid'];
                                                $midteamfhArrPj[$parent2['id']]['downMember'] = $k2 > 1 ? $parentList[$k2-1] : $member;
                                            }else{
                                                $midteamfhArrPj[$parent2['id']] = [
                                                    'totalcommission'=>$totalfenhongmoney_pj,
                                                    'commission'=>$fenhongcommission,
                                                    'money'=>$fenhongmoney,
                                                    'score'=>$fenhongscore,
                                                    'ogids'=>[$og['id']],
                                                    'module'=>$og['module'] ?? 'shop',
                                                    'levelid' => $parent2['levelid'],
                                                    'downMember' => $k2 > 1 ? $parentList[$k2-1] : $member
                                                ];
                                                 
                                                if(getcustom('teamfenhong_pingji_level_follow',$aid)){
                                                    $follow_num = numberToChinese($follow_i);
                                                    $remark = t('团队分红').'平'.$follow_num.'级奖';
                                                    $midteamfhArrPj[$parent2['id']]['remark'] = $remark;
                                                    $follow_i ++;

                                                    if($jinsuo == false && $p2_levelid != $plevelid){
                                                        unset($midteamfhArrPj[$parent2['id']]);
                                                        continue;
                                                    }
                                                }

                                            }
                                            if(getcustom('teamfenhong_pingji_num',$aid)){
                                                if(!empty($pj_nums[$parent2['levelid']])){
                                                    $pj_nums[$parent2['levelid']] = $pj_nums[$parent2['levelid']] + 1;
                                                }else{
                                                    $pj_nums[$parent2['levelid']] = 1;
                                                }
                                                if($parent2Level['teamfenhong_pingji_num_levelids']){
                                                    //级别合并，如：代理商、代理商2级合并则表示这两个级别一共拿奖个数不能超过设置的数量
                                                    $teamfenhong_pingji_num_levelids = explode(',',$parent2Level['teamfenhong_pingji_num_levelids']);
                                                    foreach($teamfenhong_pingji_num_levelids as $pj_num_levelid){
                                                        if(!empty($pj_nums[$pj_num_levelid])){
                                                            $pj_nums[$pj_num_levelid] = $pj_nums[$pj_num_levelid] + 1;
                                                        }else{
                                                            $pj_nums[$pj_num_levelid] = 1;
                                                        }
                                                    }
                                                }
                                            }

                                            if(getcustom('teamfenhong_share',$aid)){
                                                $member_orign_parent = self::get_pid_origin_bylog($aid,$member['id'],$parent2['id'],$og['paytime'],$defaultLevelIds);
                                                if($member_orign_parent !== false) $midteamfhArr[$parent2['id']]['downMember'] = $member_orign_parent;
                                            }
                                            $last_pingji_levelid = $parent2['levelid'];
                                            if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                                self::fhrecord($aid,$parent2['id'],$fenhongcommission,$fenhongscore,$og['id'],$og['module'] ?? 'shop','teamfenhong',t('团队分红',$aid));
                                            }
                                            //dump($parent['id'].'=>'.$parent2['id'].'('.$parent2Level['name'].')拿平级奖'.$totalfenhongmoney_pj);
                                            if($teamfenhong_pingji_fenhong){
                                                //计算平级奖时分红金额包含上一级拿到的平级奖
                                                $totalfenhongmoney = bcadd($totalfenhongmoney,$totalfenhongmoney_pj,$commission_weishu);
                                            }
                                            if($teamfenhong_pingji_source==1){
                                                //平级奖来源于下级团队分红
                                                $midteamfhArr[$parent['id']]['totalcommission'] = bcsub($midteamfhArr[$parent['id']]['totalcommission'],$totalfenhongmoney_pj,$commission_weishu);
                                                $midteamfhArr[$parent['id']]['commission'] = bcsub($midteamfhArr[$parent['id']]['commission'],$totalfenhongmoney_pj,$commission_weishu);
                                            }elseif($teamfenhong_pingji_source==2){
                                                //平级奖来源于上级（最近的高等级）团队分红
                                                if($teamfhlevellist[$parentList[$k2+1]['levelid']]['sort'] <= $teamfhlevellist[$parent2['levelid']]['sort']){
                                                    //上级等级不高，继续找
                                                    foreach ($parentList as $k2temp => $p2temp){
                                                        if($k2temp > $k2+1 && $teamfhlevellist[$p2temp['levelid']]['sort'] > $teamfhlevellist[$parent2['levelid']]['sort']){
                                                            $midteamfhArr[$p2temp['id']]['totalcommission'] = bcsub($midteamfhArr[$p2temp['id']]['totalcommission'],$totalfenhongmoney_pj,$commission_weishu);
                                                            $midteamfhArr[$p2temp['id']]['commission'] = bcsub($midteamfhArr[$p2temp['id']]['commission'],$totalfenhongmoney_pj,$commission_weishu);
                                                            break;
                                                        }
                                                    }
                                                }else{
                                                    $midteamfhArr[$parentList[$k2+1]['id']]['totalcommission'] = bcsub($midteamfhArr[$parentList[$k2+1]['id']]['totalcommission'],$totalfenhongmoney_pj,$commission_weishu);
                                                    $midteamfhArr[$parentList[$k2+1]['id']]['commission'] = bcsub($midteamfhArr[$parentList[$k2+1]['id']]['commission'],$totalfenhongmoney_pj,$commission_weishu);
                                                }
                                            }
                                            if($pingji_yueji_bonus_status==1 && $pingji_yueji_bonus==1){
                                                //越级拿平级奖后，上级的平级会员不再拿奖
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        //伯乐奖 找最近的上级，脱离后的新上级不算，发给原上级 （teamfenhong_bole_origin=1 仅发放原上级：新上级不发奖励，只给脱离过同时存在新上级和原上级的原上级发奖励）
                        //奖金参数优先使用产品单独设置的
                        $teamfenhong_bole_bl = $leveldata['teamfenhong_bole_bl'];//没脱离的比例
                        $teamfenhong_bole_bl_tuoli = $leveldata['teamfenhong_bole_bl_tuoli'];//脱离的比例
                        $teamfenhong_bole_money = $leveldata['teamfenhong_bole_money'];
                        $teamfenhong_bole_money_tuoli = $leveldata['teamfenhong_bole_money_tuoli'];
                        $wangyangjun = getcustom('plug_wangyangjun',$aid);

                        if($wangyangjun){
                            //update@24-4-6
                            //判断下单人和获得团队分红人的关系，不是原推荐人的情况不发
                            if($parent['id'] == $member['pid'] && empty($member['pid_origin'])){
                                $boleStatus = false;
                            }
                        }

                        if(getcustom('teamfenhong_bole',$aid) && $boleStatus && ($teamfenhong_bole_bl>0 || $teamfenhong_bole_money > 0 || $teamfenhong_bole_bl_tuoli > 0 || $teamfenhong_bole_money_tuoli > 0) && ($totalfenhongmoney > 0 || $leveldata['teamfenhong_bole_type'] == 1)){
                            if($leveldata['teamfenhong_bole_origin'] == 1){
                                //$leveldata['teamfenhong_bole_origin']=1 仅发放原上级：购买人脱离过的只给原上级或者没脱离过的当前上级发奖励
                                if($parent['pid_origin']) {
                                    $parent_bl = Db::name('member')->where('id','=',$parent['pid_origin'])->find();
                                }else{
                                    $parent_bl = $parentList[$k+1];
                                }
                            }else{
                                if($parent['pid_origin']) {
                                    $parent_bl = Db::name('member')->where('id','=',$parent['pid_origin'])->find();
                                }else{
                                    $parent_bl = $parentList[$k+1];
                                }
                            }

                            if($parent_bl){
                                $parent_bl_level = $teamfhlevellist[$parent_bl['levelid']];
                                if($parent_bl['levelstarttime'] >= $og['createtime']) {
                                    $parentbl_buy_levelid = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $parent_bl['id'])->where('status', 2)
                                        ->where('levelup_time', '<', $og['createtime'])->whereIn('levelid',$defaultLevelIds)->order('levelup_time', 'desc')->value('levelid');
                                    if($parentbl_buy_levelid) {
                                        $parent_bl['levelid'] = $parentbl_buy_levelid;
                                        $parent_bl_level = Db::name('member_level')->where('aid',$aid)->where('id',$parent_bl['levelid'])->find();
                                    }
                                }
                            }
                            Log::write([
                                'file'=>__FILE__.__LINE__,
                                'fun'=>__FUNCTION__,
                                '$parent'=>json_encode($parent),
                                '$parent_bl'=>json_encode($parent_bl),
                                '$parent_bl_level'=>json_encode($parent_bl_level),
                                '$leveldata'=>json_encode($leveldata)
                            ]);
                            $teamfenhong_bl_levelids = explode(',',$sysset['teamfenhong_bl_levelids']);
                            if(empty($teamfenhong_bl_levelids) || (!in_array($parent_bl['levelid'],$teamfenhong_bl_levelids) && !in_array(-1,$teamfenhong_bl_levelids))){
                                //级别要在系统设置的可参与伯乐奖的级别中才可以
                                unset($parent_bl);
                            }


                            //团队中伯乐奖只发一次
                            if($parent_bl &&
                                (($parent_bl_level['teamfenhong_bole_one'] && !in_array($parent_bl['levelid'],$boleArr['levelids'])) || empty($parent_bl_level['teamfenhong_bole_one']))
                            ){
                                $totalfenhongmoney = 0;
                                //dump('type:'.$leveldata['teamfenhong_bole_type'].',比例：'.$teamfenhong_bole_bl.'，分红金额：'.$fenhongprice.'，上次团队分红：'.$last_totalfenhongmoney);
                                if($leveldata['teamfenhong_bole_type'] == 0){
                                    //按奖励金额
                                    if($parent['pid_origin']) {
                                        $totalfenhongmoney += $teamfenhong_bole_bl_tuoli * $last_totalfenhongmoney * 0.01;
                                    }else{
                                        $totalfenhongmoney += $teamfenhong_bole_bl * $last_totalfenhongmoney * 0.01;
                                    }
                                }elseif($leveldata['teamfenhong_bole_type'] == 1){
                                    //按订单金额
                                    if($parent['pid_origin']) {
                                        $totalfenhongmoney += $teamfenhong_bole_bl_tuoli * $fenhongprice * 0.01;
                                    }else{
                                        $totalfenhongmoney += $teamfenhong_bole_bl * $fenhongprice * 0.01;
                                    }
                                }
                                //脱离过
                                if($parent['pid_origin']) {
                                    if($teamfenhong_bole_money > 0 ){
                                        $totalfenhongmoney += $teamfenhong_bole_money_tuoli;
                                        $og['bole_money'] = $teamfenhong_bole_money_tuoli;
                                        $teamfenhong_orderids[$parent_bl['id']][] = $og['orderid'];
                                    }
                                }else{
                                    if($teamfenhong_bole_money > 0 ){
                                        $totalfenhongmoney += $teamfenhong_bole_money;
                                        $og['bole_money'] = $teamfenhong_bole_money;
                                        $teamfenhong_orderids[$parent_bl['id']][] = $og['orderid'];
                                    }
                                }
                                //dump($parent_bl['id'].'伯乐奖进入'.$totalfenhongmoney);
                                if($totalfenhongmoney > 0){
                                    if($isyj == 1 && $yjmid == $parent_bl['id']){
                                        $commissionyj_my += $totalfenhongmoney;
                                    }
                                    if($commissionpercent != 1){
                                        $fenhongcommission = round($totalfenhongmoney*$commissionpercent,$commission_weishu);
                                        $fenhongmoney = round($totalfenhongmoney*$moneypercent,2);
                                    }else{
                                        $fenhongcommission = $totalfenhongmoney;
                                        $fenhongmoney = 0;
                                    }
                                    if($midteamfhArrBole[$parent_bl['id']]){
                                            $midteamfhArrBole[$parent_bl['id']]['totalcommission'] = $midteamfhArrBole[$parent_bl['id']]['totalcommission'] + $totalfenhongmoney;
                                            $midteamfhArrBole[$parent_bl['id']]['commission'] = $midteamfhArrBole[$parent_bl['id']]['commission'] + $fenhongcommission;
                                            $midteamfhArrBole[$parent_bl['id']]['money'] = $midteamfhArrBole[$parent_bl['id']]['money'] + $fenhongmoney;
                                            $midteamfhArrBole[$parent_bl['id']]['ogids'][] = $og['id'];
                                            $midteamfhArrBole[$parent_bl['id']]['levelid'] = $parent_bl['levelid'];
                                        }else{
                                            $midteamfhArrBole[$parent_bl['id']] = [
                                                'totalcommission'=>$totalfenhongmoney,
                                                'commission'=>$fenhongcommission,
                                                'money'=>$fenhongmoney,
                                                'ogids'=>[$og['id']],
                                                'module'=>$og['module'] ?? 'shop',
                                                'levelid' => $parent_bl['levelid']
                                            ];
                                        }


                                    $boleArr['levelids'][] = $parent_bl['levelid'];
                                    $boleArr['mids'][] = $parent_bl['id'];
                                    if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                        self::fhrecord($aid,$parent_bl['id'],$fenhongcommission,0,$og['id'],$og['module'] ?? 'shop','teamfenhong',t('团队分红',$aid));
                                    }
                                }
                            }
                           // foreach($parentList as $k_bl=>$parent_bl){
                           //     if($k_bl > $k){//&& $leveldata['sort'] < $levellist[$member['levelid']]['sort']
                           //         break;
                           //     }
                           // }

                        }
                    }
                    //其他分组等级
                    if(getcustom('plug_sanyang',$aid)) {
                        $catList = Db::name('member_level_category')->where('aid', $aid)->where('isdefault', 0)->select()->toArray();
                        foreach ($catList as $cat) {
                            $parentList = Db::name('member_level_record')->field('mid id,levelid')->where('aid', $aid)->where('cid', $cat['id'])->whereIn('mid', $pids)->select()->toArray();
                            $parentList = array_reverse($parentList);
                            $hasfhlevelids = [];
                            $last_teamfenhongbl = 0;
                            foreach($parentList as $k=>$parent){
                                //判断升级时间
                                $leveldata = $teamfhlevellist[$parent['levelid']];
                                if($parent['levelstarttime'] >= $og['createtime']) {
                                    $levelup_order_levelid = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $parent['id'])->where('status', 2)
                                        ->where('levelup_time', '<', $og['createtime'])->whereNotIn('levelid',$defaultLevelIds)->order('levelup_time', 'desc')->value('levelid');
                                    if($levelup_order_levelid) {
                                        $parent['levelid'] = $levelup_order_levelid;
                                        $leveldata = $teamfhlevellist[$parent['levelid']];
                                    }
                                }
                                if(!$leveldata || $k>=$leveldata['teamfenhonglv']) continue;
                                if($parent['id'] == $og['mid'] && $leveldata['teamfenhong_self'] != 1) continue;
                                //每单奖励
                                if($leveldata['teamfenhong_money'] > 0 && !in_array($og['orderid'],$teamfenhong_orderids_cat[$parent['id']])) {
                                    if($leveldata['teamfenhongonly'] == 1 && in_array($parent['levelid'],$hasfhlevelids)) continue; //该等级设置了只给最近的上级分红
                                    $hasfhlevelids[] = $parent['levelid'];
                                    $commission = $leveldata['teamfenhong_money'];
                                    
                                    if($isyj == 1 && $yjmid == $parent['id']){
                                        $commissionyj_my += $commission;
                                    }

                                    if($commissionpercent != 1){
                                        $fenhongcommission = round($commission*$commissionpercent,$commission_weishu);
                                        $fenhongmoney = round($commission*$moneypercent,2);
                                    }else{
                                        $fenhongcommission = $commission;
                                        $fenhongmoney = 0;
                                    }

                                    if($midteamfhArr[$parent['id']]){
                                        $midteamfhArr[$parent['id']]['totalcommission'] = $midteamfhArr[$parent['id']]['totalcommission'] + $commission;
                                        $midteamfhArr[$parent['id']]['commission'] = $midteamfhArr[$parent['id']]['commission'] + $fenhongcommission;
                                        $midteamfhArr[$parent['id']]['money'] = $midteamfhArr[$parent['id']]['money'] + $fenhongmoney;
                                        $midteamfhArr[$parent['id']]['ogids'][] = $og['id'];
                                        $midteamfhArr[$parent['id']]['levelid'] = $parent['levelid'];
                                    }else{
                                        $midteamfhArr[$parent['id']] = [
                                            'totalcommission'=>$commission,
                                            'commission'=>$fenhongcommission,
                                            'money'=>$fenhongmoney,
                                            'ogids'=>[$og['id']],
                                            'module'=>$og['module'] ?? 'shop',
                                            'levelid' => $parent['levelid']
                                        ];
                                    }
                                    if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                        self::fhrecord($aid,$parent['id'],$fenhongcommission,0,$og['id'],$og['module'] ?? 'shop','teamfenhong',t('团队分红',$aid));
                                    }

                                    $teamfenhong_orderids_cat[$parent['id']][] = $og['orderid'];
                                }
                                //分红比例
                                if($leveldata['teamfenhongbl'] > 0) {
                                    if($isjicha){
                                        $this_teamfenhongbl = $leveldata['teamfenhongbl'] - $last_teamfenhongbl;
                                    }else{
                                        $this_teamfenhongbl = $leveldata['teamfenhongbl'];
                                    }
                                    if($this_teamfenhongbl <=0) continue;
                                    $last_teamfenhongbl = $last_teamfenhongbl + $this_teamfenhongbl;
                                    if($leveldata['teamfenhongonly'] == 1 && in_array($parent['levelid'],$hasfhlevelids)) continue; //该等级设置了只给最近的上级分红
                                    $hasfhlevelids[] = $parent['levelid'];

                                    $commission = $this_teamfenhongbl * $fenhongprice * 0.01;
                                    
                                    if($isyj == 1 && $yjmid == $parent['id']){
                                        $commissionyj_my += $commission;
                                    }
                                    
                                    if($commissionpercent != 1){
                                        $fenhongcommission = round($commission*$commissionpercent,$commission_weishu);
                                        $fenhongmoney = round($commission*$moneypercent,2);
                                    }else{
                                        $fenhongcommission = $commission;
                                        $fenhongmoney = 0;
                                    }

                                    if($midteamfhArr[$parent['id']]){
                                        $midteamfhArr[$parent['id']]['totalcommission'] = $midteamfhArr[$parent['id']]['totalcommission'] + $commission;
                                        $midteamfhArr[$parent['id']]['commission'] = $midteamfhArr[$parent['id']]['commission'] + $fenhongcommission;
                                        $midteamfhArr[$parent['id']]['money'] = $midteamfhArr[$parent['id']]['money'] + $fenhongmoney;
                                        $midteamfhArr[$parent['id']]['ogids'][] = $og['id'];
                                        $midteamfhArr[$parent['id']]['levelid'] = $parent['levelid'];
                                    }else{
                                        $midteamfhArr[$parent['id']] = [
                                            'totalcommission'=>$commission,
                                            'commission'=>$fenhongcommission,
                                            'money'=>$fenhongmoney,
                                            'ogids'=>[$og['id']],
                                            'module'=>$og['module'] ?? 'shop',
                                            'levelid' => $parent['levelid']
                                        ];
                                    }
                                    if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                        self::fhrecord($aid,$parent['id'],$fenhongcommission,0,$og['id'],$og['module'] ?? 'shop','teamfenhong',t('团队分红',$aid));
                                    }

                                }
                            }
                        }
                    }
                }
            }
            //dump($midteamfhArr);exit;
            if($isyj == 1 && $commissionyj_my > 0){
                $commissionyj += $commissionyj_my;
                $og['commission'] = round($commissionyj_my,$commission_weishu);
                $og['fhname'] = t('团队分红',$aid);
                $newoglist[] = $og;
            }

            //todo 团队分红共享
//            dump($midteamfhArr);
            if(getcustom('teamfenhong_share',$aid)){
                $format = self::teamfenhong_share_format($aid,$midteamfhArr,$midteamfhArrNew);
                $midteamfhArr = $format['midteamfhArr'];
                $midteamfhArrNew = $format['midteamfhArrNew'];
            }
//            dd($format);

            $params = ['isjicha'=>$isjicha];
            self::fafang($aid,$midteamfhArr,'teamfenhong',t('团队分红',$aid),$og['mid'],$midteamfhArrNew,0,$params);
            if($midteamfhArrPj){
                self::fafang($aid,$midteamfhArrPj,'teamfenhong_pj',t('团队分红平级奖',$aid),$og['mid']);
            }
            if($midteamfhArrBole){
                self::fafang($aid,$midteamfhArrBole,'teamfenhong_bole',t('团队分红伯乐奖',$aid),$og['mid']);
            }
            //根据分红奖发放购车基金和旅游基金
            if(getcustom('teamfenhong_gouche',$aid)){
                self::goucheBonus($aid,$sysset,$midteamfhArr,$oglist,$isyj,$yjmid,$commissionpercent,$moneypercent);
            }
            if(getcustom('teamfenhong_lvyou',$aid)){
                self::lvyouBonus($aid,$sysset,$midteamfhArr,$oglist,$isyj,$yjmid,$commissionpercent,$moneypercent);
            }
            //根据分红奖团队收益
            if(getcustom('teamfenhong_shouyi',$aid)){
                self::teamshouyi($aid,$sysset,$midteamfhArr,$oglist,$isyj,$yjmid,$commissionpercent,$moneypercent);
            }
            //团队培育奖
            if(getcustom('teamfenhong_peiyujiang',$aid)){
                self::teampeiyujiang($aid,$sysset,$midteamfhArr,$oglist,$isyj,$yjmid,$commissionpercent,$moneypercent);
            }
            $midteamfhArr = [];
            $midteamfhArrPj = [];
            $midteamfhArrBole = [];

        }

    }

    private function teamfenhong_share_format($aid, $midteamfhArr,$midteamfhArrNew,$levellist = [])
    {
        if(getcustom('teamfenhong_share',$aid)){
            if(empty($midteamfhArr)) return $midteamfhArr;
            if(empty($levellist)){
                $levellist = Db::name('member_level')->where('aid',$aid)->column('*','id');
                if(empty($levellist)) return $midteamfhArr;
            }
//            $midteamfhArrNew = [];
//            'totalcommission'=>$totalfenhongmoney,
//            'commission'=>$fenhongcommission,
//            'money'=>$fenhongmoney,
//            'score'=>$fenhongscore,
//            'ogids'=>[$og['id']],
//            'module'=>$og['module'] ?? 'shop',
//            'levelid' => $parent['levelid'],
//            'type' => '团队分红',
//            'downMember' => $k > 0 ? $parentList[$k-1] : $member
            //团队分红共享奖
            foreach ($midteamfhArr as $mid =>$item){
                if($item['commission'] <= 0) continue;
                if(empty($item['downMember']['pid_origin'])) {
                    Log::write([
                        'file'=>__FILE__.__LINE__,
                        'fun'=>__FUNCTION__,
                        'item'=>$item,
                        'msg'=>'下级不存在原上级'
                    ]);
                    continue;
                }
                $level = $levellist[$item['levelid']];
                if(empty($level['teamfenhong_share_pid_origin_bl']) || $level['teamfenhong_share_pid_origin_bl'] < 0) { //原上级奖励比例(%)
                    Log::write([
                        'file'=>__FILE__.__LINE__,
                        'fun'=>__FUNCTION__,
                        'item'=>$item,
                        'level'=>$level,
                        'msg'=>'原上级奖励比例为空或小于0'
                    ]);
                    continue;
                }
                $down_levelid = explode(',',$level['teamfenhong_share_down_levelid']);//下级等级ID
                if($level['teamfenhong_share_down_levelid'] != 0 && !in_array($item['downMember']['levelid'],$down_levelid)) {
                    Log::write([
                        'file'=>__FILE__.__LINE__,
                        'fun'=>__FUNCTION__,
                        'level'=>$level,
                        'msg'=>'下级不在等级范围内'
                    ]);
                    continue;
                }
                $parentOrigin = Db::name('member')->where('aid',$aid)->where('id',$item['downMember']['pid_origin'])->find();
                if(empty($parentOrigin)) {
                    Log::write([
                        'file'=>__FILE__.__LINE__,
                        'fun'=>__FUNCTION__,
                        'item'=>$item,
                        'msg'=>'原上级不存在'
                    ]);
                    continue;
                }
                $pid_origin_levelid = explode(',',$level['teamfenhong_share_pid_origin_levelid']);//原上级等级ID
                if($level['teamfenhong_share_pid_origin_levelid'] != 0 && !in_array($parentOrigin['levelid'],$pid_origin_levelid)) {
                    Log::write([
                        'file'=>__FILE__.__LINE__,
                        'fun'=>__FUNCTION__,
                        'level'=>$level,
                        'parentOrigin'=>$parentOrigin,
                        'msg'=>'原上级等级不在范围内'
                    ]);
                    continue;
                }

                $money = $item['commission'];
                $moneyParentOrigin = round($money * $level['teamfenhong_share_pid_origin_bl'] * 0.01,2);
                $moneyParent = $money - $moneyParentOrigin;
                if($moneyParentOrigin > 0){
                    if($moneyParent){
                        if($midteamfhArrNew[$mid]){
                            $midteamfhArrNew[$mid]['totalcommission'] += $moneyParent;
                            $midteamfhArrNew[$mid]['commission'] += $moneyParent;
                            $midteamfhArrNew[$mid]['money'] += $midteamfhArr[$mid]['money'];
                            $midteamfhArrNew[$mid]['score'] += $midteamfhArr[$mid]['score'];
                        }else{
                            $midteamfhArrNew[$mid] = [
                                'totalcommission'=>$moneyParent,
                                'commission'=>$moneyParent,
                                'money'=>$item['money'],
                                'score'=>$item['score'],
                                'ogids'=>$item['ogids'],
                                'module'=>$item['module'],
                                'levelid' => $item['levelid'],
                                'type' => $item['type'],
                                'remark'=> t('团队分红共享奖',$aid)
                            ];
                        }
                    }
                    unset($midteamfhArr[$mid]);

                    if($midteamfhArrNew[$parentOrigin['id']]){
                        $midteamfhArrNew[$parentOrigin['id']]['totalcommission'] += $moneyParentOrigin;
                        $midteamfhArrNew[$parentOrigin['id']]['commission'] += $moneyParentOrigin;
                    }else{
                        $midteamfhArrNew[$parentOrigin['id']] = [
                            'totalcommission'=>$moneyParentOrigin,
                            'commission'=>$moneyParentOrigin,
                            'money'=>0,
                            'score'=>0,
                            'ogids'=>$item['ogids'],
                            'module'=>$item['module'],
                            'levelid' => $parentOrigin['levelid'],
                            'type' => '团队分红',
                            'remark'=> t('团队分红共享奖',$aid)
                        ];
                    }
                }
            }
            return ['midteamfhArr'=>$midteamfhArr,'midteamfhArrNew'=>$midteamfhArrNew];
        }
    }

    private function get_pid_origin_bylog($aid,$mid,$pid,$order_paytime=0,$defaultLevelIds=[]){
        $changelog = Db::name('member_pid_changelog')->where('aid',$aid)->where('mid',$mid)->where('pid',$pid)
            ->where('createtime','>=',$order_paytime)->order('createtime','desc')->find();
        if($changelog){
            $parent = Db::name('member')->where('id',$changelog['pid_origin'])->find();
            if(is_null($parent['pid_origin'])){
                //可能是回归了 没有原上级
                $plog = Db::name('member_pid_changelog')->where('aid',$aid)->where('mid',$parent['id'])->where('pid',$changelog['pid'])->order('createtime','desc')->find();
                if($plog['isback'] == 1){
                    $parent['pid_origin'] = $plog['pid_origin'];
                    $parent['levelid'] = Db::name('member_levelup_order')->where('aid', $aid)->where('mid', $parent['id'])->where('status', 2)
                        ->where('levelup_time', '<', $order_paytime)->whereIn('levelid', $defaultLevelIds)->order('levelup_time', 'desc')->value('levelid');
                }
            }
            return $parent ? $parent : [];
        }
        return false;
    }
    //团队见单分红
    public static function teamfenhong_jiandan($aid,$sysset,$oglist,$starttime=0,$endtime=0,$isyj=0,$yjmid=0){
        $teamfenhong_jiandan_custom = getcustom('teamfenhong_jiandan',$aid);
        if($teamfenhong_jiandan_custom) {
            if ($endtime == 0) $endtime = time();
            if(getcustom('maidan_fenhong_new',$aid)){
                $bids_maidan = Db::name('business')->where('maidan_team_jiandan',1)->column('id');
                $bids_maidan = array_merge([0],$bids_maidan);
            }
            if ($isyj == 1 && !$oglist) {
                //多商户的商品是否参与分红
                if ($sysset['fhjiesuanbusiness'] == 1) {
                    $bwhere = '1=1';
                } else {
                    $bwhere = [['og.bid', '=', '0']];
                }
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid', $aid)->where('og.isfenhong', 0)->where('og.status', 'in', [1, 2, 3])->where('og.refund_num',0)->join('shop_order o', 'o.id=og.orderid')->join('member m', 'm.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                if (!$oglist) $oglist = [];
                if(getcustom('maidan_fenhong_new',$aid) && $sysset['maidanfenhong']){
                    //买单分红
                    $bwhere_maidan = [['og.bid', 'in', $bids_maidan]];
                    $maidan_orderlist = Db::name('maidan_order')
                        ->alias('og')
                        ->join('member m','m.id=og.mid')
                        ->where('og.aid',$aid)
                        ->where('og.isfenhong',0)
                        ->where('og.status',1)
                        ->where($bwhere_maidan)
                        ->field('og.*,m.nickname,m.headimg')
                        ->order('og.id desc')
                        ->select()
                        ->toArray();
                    if($maidan_orderlist){
                        foreach($maidan_orderlist as $mdk=>$mdv){
                            $mdv['name']             = $mdv['title'];
                            $mdv['real_totalprice']  = $mdv['paymoney'];
                            //买单分红结算方式
                            if($sysset['maidanfenhong_type'] == 1){
                                //按利润结算时直接把销售额改成利润
                                $mdv['real_totalprice'] = $mdv['paymoney'] - $mdv['cost_price'];
                            }elseif($sysset['maidanfenhong_type'] == 2){
                                //按抽佣计算
                                $mdv['real_totalprice'] = $mdv['order_fee'];
                            }
                            $mdv['cost_price']       = 0;
                            $mdv['num']              = 1;
                            $mdv['module']           = 'maidan';
                            $oglist[] = $mdv;
                        }
                    }
                }
            }
            //参与团队分红的等级
            $teamfhlevellist = Db::name('member_level')->where('aid', $aid)->where('teamfenhong_jiandan_lv', '>', '0')->column('*', 'id');
            if (!$teamfhlevellist) return ['commissionyj' => 0, 'oglist' => []];

            if (!$oglist) return ['commissionyj' => 0, 'oglist' => []];

            $defaultCid = Db::name('member_level_category')->where('aid', $aid)->where('isdefault', 1)->value('id');
            if ($defaultCid) {
                $defaultLevelIds = Db::name('member_level')->where('aid', $aid)->where('cid', $defaultCid)->column('id');
            } else {
                $defaultLevelIds = Db::name('member_level')->where('aid', $aid)->column('id');
            }

            $isjicha = ($sysset['teamfenhong_jiandan_differential'] == 1 ? true : false);
            $allfenhongprice = 0;
            $ogids = [];
            $midteamfhArr = [];
            $teamfenhong_orderids = [];
            $teamfenhong_orderids_pj = [];
            $teamfenhong_orderids_cat = [];
            $newoglist = [];
            $commissionyj = 0;
            foreach ($oglist as $og) {
                if(getcustom('maidan_fenhong_new',$aid) && $og['module']=='maidan'){
                    if($og['bid'] > 0 && !in_array($og['bid'],$bids_maidan)){
                        continue;
                    }
                }
                $commissionyj_my = 0;

                $commissionpercent = 1;
                $moneypercent = 0;

                if ($sysset['fhjiesuantype'] == 0) {
                    $fenhongprice = $og['real_totalprice'];
                } else {
                    $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                }
                if(getcustom('yx_buyer_subsidy',$aid)){
                    if($sysset['fhjiesuantype'] == 2){
                        //按抽成计算
                        $fenhongprice = $og['order_fee']??0 ;
                    }
                }
                if ($fenhongprice <= 0) continue;
                $ogids[] = $og['id'];
                $allfenhongprice = $allfenhongprice + $fenhongprice;
                $member = Db::name('member')->where('id', $og['mid'])->find();
                if ($teamfhlevellist) {
                    //判断脱离时间
                    if ($member['change_pid_time'] && $member['change_pid_time'] >= $og['createtime']) {
                        $pids = $member['path_origin'];
                    } else {
                        $pids = $member['path'];
                    }

                    if ($pids) {
                        $pids .= ',' . $og['mid'];
                    } else {
                        $pids = (string)$og['mid'];
                    }
                    if ($pids) {
                        $parentList = Db::name('member')->where('id', 'in', $pids)->order(Db::raw('field(id,' . $pids . ')'))->select()->toArray();
                        $parentList = array_reverse($parentList);
                        $hasfhlevelids = [];
                        $last_teamfenhongbl = 0;
                        $last_teamfenhongmoney = 0;
                        //层级判断，如购买人等级未开启“包含自己teamfenhong_jiandan_self“则购买人的上级为第一级，开启了则购买人为第一级
                        $level_i = 0;
                        foreach ($parentList as $k => $parent) {
                            $ii++;
                            //判断升级时间
                            $leveldata = $teamfhlevellist[$parent['levelid']];
                            if ($parent['levelstarttime'] >= $og['createtime']) {
                                $levelup_order_levelid = Db::name('member_levelup_order')->where('aid', $aid)->where('mid', $parent['id'])->where('status', 2)
                                    ->where('levelup_time', '<', $og['createtime'])->whereIn('levelid', $defaultLevelIds)->order('levelup_time', 'desc')->value('levelid');
                                if ($levelup_order_levelid) {
                                    $parent['levelid'] = $levelup_order_levelid;
                                    $leveldata = $teamfhlevellist[$parent['levelid']];
                                } else {
                                    unset($parentList[$k]);
                                    continue;
                                }
                            }
                            $level_i++;
                            if ($parent['id'] == $og['mid'] && $leveldata['teamfenhong_jiandan_self'] != 1) {
                                $level_i--;
                                unset($parentList[$k]);
                                continue;
                            }//不包含自己则层级-1
                            if (!$leveldata || $level_i > $leveldata['teamfenhong_jiandan_lv']) continue;
                            if ($leveldata['teamfenhong_jiandan_only'] == 1 && in_array($parent['levelid'], $hasfhlevelids)) continue; //该等级设置了只给最近的上级分红
                            $hasfhlevelids[] = $parent['levelid'];
                            if($og['module'] == 'shop'){
                                $product = Db::name('shop_product')->where('id', $og['proid'])->find();
                            }
                            if(getcustom('maidan_fenhong_new',$aid)){
                                if($og['module'] == 'maidan'){
                                    $product = [];
                                    $product['teamfenhong_jiandan_bl']   = 0;
                                    $product['teamfenhong_jiandan_money'] = 0;
                                }
                            }
                            if ($product['teamfenhongjdset'] == 1) { //按比例
                                $fenhongdata = json_decode($product['teamfenhongjddata1'], true);
                                if ($fenhongdata) {
                                    $leveldata['teamfenhong_jiandan_bl'] = $fenhongdata[$leveldata['id']]['commission'];
                                    $leveldata['teamfenhong_jiandan_money'] = 0;
                                }
                            } elseif ($product['teamfenhongjdset'] == 2) { //按固定金额
                                $fenhongdata = json_decode($product['teamfenhongjddata2'], true);
                                if ($fenhongdata) {
                                    $leveldata['teamfenhong_jiandan_bl'] = 0;
                                    $leveldata['teamfenhong_jiandan_money'] = $fenhongdata[$leveldata['id']]['commission'] * $og['num'];
                                }
                            } elseif ($product['teamfenhongjdset'] == -1) {
                                $leveldata['teamfenhong_jiandan_bl'] = 0;
                                $leveldata['teamfenhong_jiandan_money'] = 0;
                            }
                            if(getcustom('maidan_fenhong_new',$aid) && $og['module'] == 'maidan'){
                                $leveldata['teamfenhong_jiandan_bl'] = $leveldata['teamfenhong_jiandan_bl_maidan'];
                            }
                            //每单奖励
                            $totalfenhongmoney = 0;
                            $totalfenhongscore = 0;
                            if ($leveldata['teamfenhong_jiandan_money'] > 0 && !in_array($og['orderid'], $teamfenhong_orderids[$parent['id']])) {
                                if ($isjicha) {
                                    $totalfenhongmoney = $totalfenhongmoney + $leveldata['teamfenhong_jiandan_money'] - $last_teamfenhongmoney;
                                } else {
                                    $totalfenhongmoney = $totalfenhongmoney + $leveldata['teamfenhong_jiandan_money'];
                                }
                                if ($totalfenhongmoney < 0) $totalfenhongmoney = 0;
                                $last_teamfenhongmoney = $last_teamfenhongmoney + $totalfenhongmoney;
                                $teamfenhong_orderids[$parent['id']][] = $og['orderid'];
                            }
                            //分红比例
                            if ($leveldata['teamfenhong_jiandan_bl'] > 0) {
                                if ($isjicha) {
                                    $this_teamfenhongbl = $leveldata['teamfenhong_jiandan_bl'] - $last_teamfenhongbl;
                                } else {
                                    $this_teamfenhongbl = $leveldata['teamfenhong_jiandan_bl'];
                                }
                                if ($this_teamfenhongbl <= 0) $this_teamfenhongbl = 0;
                                $last_teamfenhongbl = $last_teamfenhongbl + $this_teamfenhongbl;
                                $totalfenhongmoney = $totalfenhongmoney + $this_teamfenhongbl * $fenhongprice * 0.01;
                            }
                            if ($totalfenhongmoney > 0) {
                                if ($isyj == 1 && $yjmid == $parent['id']) {
                                    $commissionyj_my += $totalfenhongmoney;
                                }
                                if ($commissionpercent != 1) {
                                    $fenhongcommission = round($totalfenhongmoney * $commissionpercent, 2);
                                    $fenhongmoney = round($totalfenhongmoney * $moneypercent, 2);
                                } else {
                                    $fenhongcommission = $totalfenhongmoney;
                                    $fenhongmoney = 0;
                                }
                                if ($midteamfhArr[$parent['id']]) {
                                    $midteamfhArr[$parent['id']]['totalcommission'] = $midteamfhArr[$parent['id']]['totalcommission'] + $totalfenhongmoney;
                                    $midteamfhArr[$parent['id']]['commission'] = $midteamfhArr[$parent['id']]['commission'] + $fenhongcommission;
                                    $midteamfhArr[$parent['id']]['money'] = $midteamfhArr[$parent['id']]['money'] + $fenhongmoney;
                                    $midteamfhArr[$parent['id']]['ogids'][] = $og['id'];
                                    $midteamfhArr[$parent['id']]['levelid'] = $parent['levelid'];
                                } else {
                                    $midteamfhArr[$parent['id']] = [
                                        'totalcommission' => $totalfenhongmoney,
                                        'commission' => $fenhongcommission,
                                        'money' => $fenhongmoney,
                                        'ogids' => [$og['id']],
                                        'module' => $og['module'] ?? 'shop',
                                        'levelid' => $parent['levelid'],
                                        'type' => '团队见单分红',
                                    ];
                                }
                                if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                    self::fhrecord($aid,$parent['id'],$fenhongcommission,0,$og['id'],$og['module'] ?? 'shop','teamfenhong_jiandan',t('团队见单分红',$aid));
                                }
                            }
                        }
                    }
                }
                if ($isyj == 1 && $commissionyj_my > 0) {
                    $commissionyj += $commissionyj_my;
                    $og['commission'] = round($commissionyj_my, 2);
                    $og['fhname'] = t('团队见单分红', $aid);
                    $newoglist[] = $og;
                }

                self::fafang($aid, $midteamfhArr, 'teamfenhong_jiandan', t('团队见单分红', $aid),$og['mid']);
                //根据分红奖团队收益
                if(getcustom('teamfenhong_shouyi',$aid)){
                    self::teamshouyi($aid,$sysset,$midteamfhArr,$oglist,$isyj,$yjmid,$commissionpercent,$moneypercent);
                }
                $midteamfhArr = [];

            }

        }
    }
    //团队分红 运费
    public static function teamfenhong_freight($aid,$sysset,$oglist,$starttime=0,$endtime=0,$isyj=0,$yjmid=0){
        if(getcustom('teamfenhong_freight_money',$aid)){
            if ($endtime == 0) $endtime = time();
            if ($isyj == 1 && !$oglist) {
                if ($sysset['fhjiesuanbusiness'] == 1) {
                    $bwhere = '1=1';
                } else {
                    $bwhere = [['og.bid', '=', '0']];
                }
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
            }
            if(!$oglist) $oglist = [];
            //参与团队分红的等级
            $teamfhlevellist = Db::name('member_level')->where('aid', $aid)->where('teamfenhong_freight_lv', '>', '0')->column('*', 'id');
            if (!$teamfhlevellist) return ['commissionyj' => 0, 'oglist' => []];
            if (!$oglist) return ['commissionyj' => 0, 'oglist' => []];
            
            //查找系统设置的,-1:不参与分红
            $isjicha = ($sysset['teamfenhong_differential'] == 1 ? true : false);
            $midteamfhArr = [];
            $newoglist = [];
            $commissionyj = 0;
            foreach ($oglist as $og) {
                $commissionyj_my = 0;
                $pj_levelids = [];//已发等级
                $member = Db::name('member')->where('id', $og['mid'])->find();
                if ($teamfhlevellist) {
                    $pids = $member['path'];
                    if ($pids) {
                        $pids .= ',' . $og['mid'];
                    } else {
                        $pids = (string)$og['mid'];
                    }
                    if ($pids) {
                        $parentList = Db::name('member')->where('id', 'in', $pids)->order(Db::raw('field(id,' . $pids . ')'))->select()->toArray();
                        $parentList = array_reverse($parentList);
                        $level_i = 0;
                        $last_teamfenhongmoney = 0;
                        $last_teamfenhongmoney_pj_total = 0;
                        $last_totalfenhongmoney = 0;//上次团队分红总额 金额+比例
                        foreach ($parentList as $k => $parent){
                            $leveldata = $teamfhlevellist[$parent['levelid']];
                            $leveldata['teamfenhong_freight_money'] = $leveldata['teamfenhong_freight_money'];
                            $product = Db::name('shop_product')->where('id',$og['proid'])->find();
                            if (!$leveldata || $level_i > $leveldata['teamfenhong_freight_lv']) continue;
                            if ($product['teamfenhongfreightset'] ==2){ //按金额
                                $teamfenhongfreightdata2  = json_decode($product['teamfenhongfreightdata2'],true);
                                if($teamfenhongfreightdata2){
                                    $leveldata['teamfenhong_freight_money'] = $teamfenhongfreightdata2[$leveldata['id']]['commission'];
                                }
                            }elseif ($product['teamfenhongfreightset'] == -1) { //不参与
                                $leveldata['teamfenhong_freight_money'] = 0;
                            }
                            $totalfenhongmoney = 0;
                            if ($leveldata['teamfenhong_freight_money']){
                                $totalfenhongmoney = $totalfenhongmoney + $leveldata['teamfenhong_freight_money'] * $og['num'];
                            }
                            if($isjicha){
                                $totalfenhongmoney = $totalfenhongmoney - $last_teamfenhongmoney - $last_teamfenhongmoney_pj_total;
                            }else{
                                $totalfenhongmoney = $totalfenhongmoney;
                            }
                            if($totalfenhongmoney < 0) $totalfenhongmoney = 0;
                            //最后一次累计 极差计算用
                            $last_teamfenhongmoney = $last_teamfenhongmoney + $totalfenhongmoney;
                            if ($totalfenhongmoney > 0) {
                                if ($isyj == 1 && $yjmid == $parent['id']) {
                                    $commissionyj_my += $totalfenhongmoney;
                                }
                                $fenhongcommission = $totalfenhongmoney;
                                if ($midteamfhArr[$parent['id']]) {
                                    $midteamfhArr[$parent['id']]['totalcommission'] = $midteamfhArr[$parent['id']]['totalcommission'] + $totalfenhongmoney;
                                    $midteamfhArr[$parent['id']]['commission'] = $midteamfhArr[$parent['id']]['commission'] + $fenhongcommission;
                                    $midteamfhArr[$parent['id']]['money'] = $midteamfhArr[$parent['id']]['money'] + 0;
                                    $midteamfhArr[$parent['id']]['ogids'][] = $og['id'];
                                    $midteamfhArr[$parent['id']]['levelid'] = $parent['levelid'];
                                } else {
                                    $midteamfhArr[$parent['id']] = [
                                        'totalcommission' => $totalfenhongmoney,
                                        'commission' => $fenhongcommission,
                                        'money' => 0,
                                        'ogids' => [$og['id']],
                                        'module' => $og['module'] ?? 'shop',
                                        'levelid' => $parent['levelid'],
                                        'type' => '运费补贴',
                                    ];
                                }
                               
                            }
                           
                            //平级奖  找最近的上级，设置分红级数且
                            $last_teamfenhongbl_pj = 0;
                            $last_teamfenhongmoney_pj = 0;
                            $last_teamfenhong_pj = 0;//级差奖励金额，新增的teamfenhong_pingji_fenhong要累计前面的分红+平级奖用来计算，直接用级差比例的话会丢失奖励
                            $levelSort = [];
                            if($leveldata['teamfenhong_freight_pingji_lv']>0){
                                foreach($parentList as $k2=>$parent2){
                                    $parent2Level = $teamfhlevellist[$parent2['levelid']];
                                    $parentLevel = $teamfhlevellist[$parent['levelid']];

                                    $levelSort[] = $parent2Level['sort'];

                                    if($parent2Level['sort'] > $parentLevel['sort']){
                                        break;
                                    }
                                    if(in_array($parent2['levelid'],$pj_levelids)){
                                        //每个级别平级奖只发一次
                                        continue;
                                    }
                                    if($k2 > $k && $parent2['levelid'] == $parent['levelid']){
                                        $teamfenhong_pingji_money = $leveldata['teamfenhong_freight_pingji_money']* $og['num'];
                                        if ($product['teamfenhongfreightpjset'] ==2) { //按金额
                                            $teamfenhongpjdata2  = json_decode($product['teamfenhongfreightpjdata2'],true);
                                            if($teamfenhongpjdata2){
                                                $teamfenhong_pingji_money = $teamfenhongpjdata2[$leveldata['id']]['commission'] * $og['num'];
                                            }
                                        }elseif ($product['teamfenhongfreightpjset'] == -1) { //不参与
                                            $teamfenhong_pingji_money = 0;
                                        }
                                        if($isjicha && $sysset['teamfenhong_differential_pj'] == 1) {
                                            $this_teamfenhongmoney_pj = $teamfenhong_pingji_money - $last_teamfenhongmoney_pj;
                                        } else{
                                            $this_teamfenhongmoney_pj = $teamfenhong_pingji_money;
                                        }
                                        if($this_teamfenhongmoney_pj <=0) $this_teamfenhongmoney_pj = 0;
                                        
                                        $last_teamfenhongmoney_pj = $last_teamfenhongmoney_pj + $this_teamfenhongmoney_pj;
                                        $totalfenhongmoney_pj = $this_teamfenhongmoney_pj;
                                        if($isjicha && $sysset['teamfenhong_differential_pj'] == 1 ){
                                            $totalfenhongmoney_pj = bcsub($totalfenhongmoney_pj , $last_teamfenhong_pj,2);
                                            $last_teamfenhong_pj = $last_teamfenhong_pj + $totalfenhongmoney_pj;
                                        }
                                        if($totalfenhongmoney_pj > 0 ){
                                            if($isyj == 1 && $yjmid == $parent2['id']){
                                                $commissionyj_my += $totalfenhongmoney_pj;
                                            }
                                            $fenhongcommission = $totalfenhongmoney_pj;
                                            $fenhongmoney = 0;
                                            $fenhongscore = 0;
                                            if($midteamfhArr[$parent2['id']]){
                                                $midteamfhArr[$parent2['id']]['totalcommission'] = $midteamfhArr[$parent2['id']]['totalcommission'] + $totalfenhongmoney_pj;
                                                $midteamfhArr[$parent2['id']]['commission'] = $midteamfhArr[$parent2['id']]['commission'] + $fenhongcommission;
                                                $midteamfhArr[$parent2['id']]['money'] = $midteamfhArr[$parent2['id']]['money'] + $fenhongmoney;
                                                $midteamfhArr[$parent2['id']]['score'] = $midteamfhArr[$parent2['id']]['score'] + $fenhongscore;
                                                $midteamfhArr[$parent2['id']]['levelid'] = $parent2['levelid'];
                                            }else{
                                                $midteamfhArr[$parent2['id']] = [
                                                    'totalcommission'=>$totalfenhongmoney_pj,
                                                    'commission'=>$fenhongcommission,
                                                    'money'=>$fenhongmoney,
                                                    'score'=>$fenhongscore,
                                                    'ogids'=>[$og['id']],
                                                    'module'=>$og['module'] ?? 'shop',
                                                    'levelid' => $parent2['levelid']
                                                ];
                                            }
                                            $pj_levelids[] = $parent2['levelid'];
                                        }
                                    }
                                }
                            }
                           
                        }
                    }
                }
                
                if ($isyj == 1 && $commissionyj_my > 0) {
                    $commissionyj += $commissionyj_my;
                    $og['commission'] = round($commissionyj_my, 2);
                    $og['fhname'] = t('运费补贴', $aid);
                    $newoglist[] = $og;
                }

                self::fafang($aid, $midteamfhArr, 'teamfenhong_freight', t('运费补贴', $aid),$og['mid']);
                $midteamfhArr = [];
            }
        }
    }
    //区域代理分红
    //门店区域代理分红未发放原因有：1.门店资料的area为空（腾讯地图接口超限）
    public static function areafenhong($aid,$sysset,$oglist,$starttime=0,$endtime=0,$isyj=0,$yjmid=0,$is_hexiao=0){
        if($endtime == 0) $endtime = time();
        if(getcustom('fenhong_business_item_switch',$aid)){
            //查找开启的多商户
            $bids = Db::name('business')->where('aid',$aid)->where('areafenhong_status',1)->column('id');
            $bids = array_merge([0],$bids);
        }
        if(getcustom('maidan_fenhong_new',$aid)){
            $bids_maidan = Db::name('business')->where('maidan_area','>=',1)->column('id');
            $bids_maidan = array_merge([0],$bids_maidan);
        }
        if($isyj == 1 && !$oglist){
            //多商户的商品是否参与分红
            if($sysset['fhjiesuanbusiness'] == 1){
                $bwhere = '1=1';
            }else{
                $bwhere = [['og.bid','=','0']];
            }
            if(getcustom('fenhong_business_item_switch',$aid)){
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')
                    ->where('og.bid','in',$bids)
                    ->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
            }else{
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
            }
            if(getcustom('yuyue_fenhong',$aid)){
                $yyorderlist = Db::name('yuyue_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($yyorderlist as $k=>$v){
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = $v['cost_price'] ?? 0;
                    $v['module'] = 'yuyue';
                    $oglist[] = $v;
                }
            }
            if(getcustom('scoreshop_fenhong',$aid)){
                $scoreshopoglist = Db::name('scoreshop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('scoreshop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($scoreshopoglist as $v){
                    $v['real_totalprice'] = $v['totalmoney'];
                    $v['module'] = 'scoreshop';
                    $oglist[] = $v;
                }
            }
            if(getcustom('luckycollage_fenhong',$aid)){
                $lcorderlist = Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->whereRaw('og.buytype=1 or og.iszj=1')->where('og.isjiqiren',0)->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($lcorderlist as $k=>$v){
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'lucky_collage';
                    $oglist[] = $v;
                }
            }
            if(getcustom('fenhong_times_coupon',$aid)){
                $cwhere[] =['og.isfenhong','=',0];
                $cwhere[] =['og.status','=',1];
                $cwhere[] =['og.paytime','>=',$starttime];
                $cwhere[] =['og.paytime','<',$endtime];
                if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                    $cwhere[] =['og.bid','=',0];
                }
                $couponorderlist = Db::name('coupon_order')->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where($cwhere)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                foreach($couponorderlist as $k=>$v){
                    $v['name'] = $v['title'];
                    $v['real_totalprice'] = $v['price'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'coupon';
                    $oglist[] = $v;
                }
            }
            if(getcustom('fenhong_kecheng',$aid)){
                //课程直接支付，无区域分红
                $kwhere = [];
                $kwhere[] = ['og.aid','=',$aid];
                $kwhere[] = ['og.isfenhong','=',0];
                $kwhere[] = ['og.status','=',1];
                $kwhere[] = ['og.paytime','>=',$starttime];
                $kwhere[] = ['og.paytime','<',$endtime];
                if($sysset['fhjiesuanbusiness'] != 1){
                    $kwhere[] = ['og.bid','=','0'];
                }
                $kechenglist = Db::name('kecheng_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where($kwhere)
                    ->field('og.*," " as area2,m.nickname,m.headimg')
                    ->select()
                    ->toArray();
                if($kechenglist){
                    foreach($kechenglist as $v){
                        $v['name']            = $v['title'];
                        $v['real_totalprice'] = $v['totalprice'];
                        $v['cost_price']      = 0;
                        $v['module']          = 'kecheng';
                        $v['num']             = 1;
                        $oglist[]             = $v;
                    }
                }
            }
            if(getcustom('maidan_fenhong_new',$aid) && $sysset['maidanfenhong']){
                //买单分红
                $bwhere_maidan = [['og.bid', 'in', $bids_maidan]];
                $maidan_orderlist = Db::name('maidan_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where('og.aid',$aid)
                    ->where('og.isfenhong',0)
                    ->where('og.status',1)
                    ->where($bwhere_maidan)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                if($maidan_orderlist){
                    foreach($maidan_orderlist as $mdk=>$mdv){
                        $mdv['name']             = $mdv['title'];
                        $mdv['real_totalprice']  = $mdv['paymoney'];
                        //买单分红结算方式
                        if($sysset['maidanfenhong_type'] == 1){
                            //按利润结算时直接把销售额改成利润
                            $mdv['real_totalprice'] = $mdv['paymoney'] - $mdv['cost_price'];
                        }elseif($sysset['maidanfenhong_type'] == 2){
                            //按抽佣计算
                            $mdv['real_totalprice'] = $mdv['order_fee'];
                        }
                        $mdv['cost_price']       = 0;
                        $mdv['num']              = 1;
                        $mdv['module']           = 'maidan';
                        $oglist[] = $mdv;
                    }
                }
            }
            }
        if(!$oglist) return ['commissionyj'=>0,'oglist'=>[]];
        //参与区域代理分红的等级
        $areafhlevellist = Db::name('member_level')->where('aid',$aid)->where('areafenhong','>','0')->order('sort,id')->column('*','id');
        if(!$areafhlevellist) return ['commissionyj'=>0,'oglist'=>[]];
        if($sysset['areafenhong_jiaquan'] == 1){
            $largearealevelids = Db::name('member_level')->where('aid',$aid)->where('areafenhong','10')->where('areafenhongbl','>',0)->column('id');
            $provincelevelids = Db::name('member_level')->where('aid',$aid)->where('areafenhong','1')->where('areafenhongbl','>',0)->column('id');
            $citylevelids = Db::name('member_level')->where('aid',$aid)->where('areafenhong','2')->where('areafenhongbl','>',0)->column('id');
        }

        $defaultCid = Db::name('member_level_category')->where('aid',$aid)->where('isdefault', 1)->value('id');
        if($defaultCid) {
            $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->where('cid', $defaultCid)->column('id');
        } else {
            $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->column('id');
        }
        $field = 'id,levelid,areafenhong_province,areafenhong_city,areafenhong_area,areafenhongbl,areafenhong';
        if(getcustom('maidan_fenhong_new',$aid)){
            $field.=',areafenhongbl_maidan';
        }
        $memberlist1 = Db::name('member')->field($field)->where('aid',$aid)->where('areafenhong',1)->where('areafenhongbl','>',0)->select()->toArray();
        $memberlist2 = Db::name('member')->field($field)->where('aid',$aid)->where('areafenhong',2)->where('areafenhongbl','>',0)->select()->toArray();
        $memberlist3 = Db::name('member')->field($field)->where('aid',$aid)->where('areafenhong',3)->where('areafenhongbl','>',0)->select()->toArray();
        $field.= ',areafenhong_largearea';
        $memberlist10 = Db::name('member')->field($field)->where('aid',$aid)->where('areafenhong',10)->where('areafenhongbl','>',0)->select()->toArray();

        $areamemberlist = array_merge((array)$memberlist1,(array)$memberlist2,(array)$memberlist3,(array)$memberlist10);
        //其他分组等级
        $member_level_record = Db::name('member_level_record')->field('mid id,levelid,areafenhong_province,areafenhong_city,areafenhong_area,areafenhongbl,areafenhong')->where('aid',$aid)->whereIn('areafenhong',[1,2,3])->where('areafenhongbl','>',0)->select()->toArray();
        $areamemberlist = array_merge((array)$areamemberlist, (array)$member_level_record);



        $isjicha = ($sysset['areafenhong_differential'] == 1 ? true : false);
        $ogids = [];
        $midareafhArr = [];
        $midareafhArr2 = [];

        $newoglist = [];
        $commissionyj = 0;
        $businessArr = [];

        $commission_weishu = 2;//佣金位数
        if(getcustom('fenhong_money_weishu',$aid)){
            $commission_weishu = $sysset['fenhong_money_weishu']??2;
        }
        foreach($oglist as $og){
            if(getcustom('product_quanyi_areafenhong',$aid) && $is_hexiao==0 && $og['product_type']==8){
                //权益产品在核销时计算
                continue;
            }
            if(getcustom('maidan_fenhong_new',$aid) && $og['module']=='maidan'){
                if($og['bid'] > 0 && !in_array($og['bid'],$bids_maidan)){
                    continue;
                }
            }
            if(getcustom('fenhong_business_item_switch',$aid) && $og['module']!='maidan'){
                if($og['bid'] > 0 && !in_array($og['bid'],$bids)){
                    continue;
                }
            }
            if($og['module'] == 'hotel') continue;
            $commissionyj_my = 0;
            if(getcustom('commission2moneypercent',$aid) && $sysset['commission2moneypercent1'] > 0){
                //是否是首单
                $beforeorder = Db::name('shop_order')->where('aid',$aid)->where('mid',$og['mid'])->where('status','in','1,2,3')->where('paytime','<',$og['paytime'])->find();
                if(!$beforeorder){
                    $commissionpercent = 1 - $sysset['commission2moneypercent1'] * 0.01;
                    $moneypercent = $sysset['commission2moneypercent1'] * 0.01;
                }else{
                    $commissionpercent = 1 - $sysset['commission2moneypercent2'] * 0.01;
                    $moneypercent = $sysset['commission2moneypercent2'] * 0.01;
                }
            }else{
                $commissionpercent = 1;
                $moneypercent = 0;
            }
            if($sysset['fhjiesuantype'] == 0){
                $fenhongprice = $og['real_totalprice'];
            }else{
                $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
            }
            if(getcustom('money_dec_commission_fenhong',$aid)){
                if($og['module'] == 'maidan' && $sysset['money_dec_fenxiao_fenhong'] == 1){
                    //买单分红 按照销售额、抵扣部分参与分销分红开启时 增加抵扣金额
                    $fenhongprice += $og['dec_money'];
                }elseif($og['module'] == 'shop' && $sysset['money_dec_fenxiao_fenhong'] == 0){
                    //商城分红 按照销售、抵扣部分参与分销分红关闭时 扣除抵扣金额
                    $fenhongprice -= $og['dec_money'];
                }
            }
            if(getcustom('yx_buyer_subsidy',$aid)){
                if($sysset['fhjiesuantype'] == 2){
                    //按抽成计算
                    $fenhongprice = $og['order_fee']??0 ;
                }
            }
            if(getcustom('baikangxie',$aid)){
                $fenhongprice = $og['cost_price'] * $og['num'];
            }
			if(getcustom('member_dedamount',$aid)){
                //如果是商城和买单订单，需要判断商家是否设置了让利
                if(!$og['module'] || $og['module'] == 'shop' || $og['module'] == 'maidan'){
                    if($og['bid'] && $og['paymoney_givepercent'] && $og['paymoney_givepercent']>0){
                        //重置分红金额为抵扣
                        $fenhongprice = $og['dedamount_dkmoney']??0;
                    }else{
                        //若开启了分销依赖抵扣金，则分红金额为0
                        if($sysset['dedamount_fenxiao'] == 1) $fenhongprice = 0;
                    }
                }
            }
            if($fenhongprice <= 0) continue;
            $ogids[] = $og['id'];
            $allfenhongprice = $allfenhongprice + $fenhongprice;
//            $member = Db::name('member')->where('id', $og['mid'])->find();
//            $member_extend = Db::name('member_level_record')->field('mid id,levelid')->where('aid', $aid)->where('mid', $og['mid'])->find();
            
            if($og['module'] == 'yuyue'){
                $product = Db::name('yuyue_product')->where('id',$og['proid'])->find();
            }elseif($og['module'] == 'coupon'){
                $product = Db::name('coupon')->where('id',$og['cpid'])->find();
            }elseif($og['module'] == 'luckycollage' || $og['module'] == 'lucky_collage'){
                $product = Db::name('lucky_collage_product')->where('id',$og['proid'])->find();
                if (getcustom('luckycollage_fail_commission',$aid)) {
                    if ($og['iszj'] == 2) {
                        $product['fenhongset'] = $product['fail_fenhongset'];
                        $product['areafenhongset'] = $product['fail_areafenhongset'];
                        $product['areafenhongdata1'] = $product['fail_areafenhongdata1'];
                        $product['areafenhongdata2'] = $product['fail_areafenhongdata2'];

                    }
                }
                if ($product['fenhongset'] == 0) {
                    $product['areafenhongset'] = -1;
                }

            }elseif($og['module'] == 'scoreshop'){
                $product = Db::name('scoreshop_product')->where('id',$og['proid'])->find();
            }elseif($og['module'] == 'kecheng'){
                $product = Db::name('kecheng_list')->where('id',$og['kcid'])->find();
            //}elseif($og['module'] == 'hotel'){
            //  $product = Db::name('hotel_room')->where('id',$og['roomid'])->find();
            }elseif($og['module'] == 'maidan'){
                $product['areafenhongset'] = 0;//按会员等级
            }elseif($og['module'] == 'restaurant_takeaway'){
                $product['areafenhongset'] = 0;//按会员等级
            }elseif($og['module'] == 'cashier'){
                if(getcustom('cashier_fenhong',$aid)){
                    if($og['protype']==2){
                        //直接收款
                        $product = [
                            'areafenhongset' => 0,
                        ];
                    }else{
                        $product = Db::name('shop_product')->where('id',$og['proid'])->find();
                    }
                }
            }else{
                $product = Db::name('shop_product')->where('id',$og['proid'])->find();
            }
            $restaurant_fenhong_product_set_custom = getcustom('restaurant_fenhong_product_set',$aid);
            if(getcustom('restaurant_fenhong',$aid)){
                if($og['module'] == 'restaurant_shop' || $og['module'] == 'restaurant_takeaway'){
                    $product = [];
                    if($restaurant_fenhong_product_set_custom){
                        $product =  Db::name('restaurant_product')->where('id',$og['proid'])->find();
                    }
                }
            }
            if(getcustom('maidan_fenhong_new',$aid)){
                if($og['module'] == 'maidan'){
                    $product = [];
                    $product['areafenhongset']   = 0;
                    $product['areafenhongdata1'] = 0;
                    if($og['bid'] > 0){
                        if(isset($businessArr[$og['bid']])){
                            $business_info = $businessArr[$og['bid']];
                        }else{
                            $business_info = Db::name('business')->where('id',$og['bid'])->find();
                            $businessArr[$og['bid']] = $business_info;
                        }
                        $og['area2'] = $business_info['province'].','.$business_info['city'].','.$business_info['district'];
                    }else{
                        $og['area2'] = $sysset['province'].','.$sysset['city'].','.$sysset['district'];
                    }

                    //查询商家买单分红单独设置
                    $business = Db::name('business')->where('id',$og['bid'])->field('maidan_area,maidan_areafenhongdata1')->find();
                    if($business){
                        if($business['maidan_area'] == 2){
                            $product['areafenhongset'] = 1;
                            $product['areafenhongdata1'] = !empty($business['maidan_areafenhongdata1'])?$business['maidan_areafenhongdata1']:[];
                        }else if($business['maidan_area'] == 0){
                            $product['areafenhongset'] = -1;
                        }
                    }
                }
            }
            if(getcustom('ganer_fenxiao',$aid)){
                if(empty($og['area2'])){
                    if($og['bid'] > 0){
                        if(isset($businessArr[$og['bid']])){
                            $business_info = $businessArr[$og['bid']];
                        }else{
                            $business_info = Db::name('business')->where('id',$og['bid'])->find();
                            $businessArr[$og['bid']] = $business_info;
                        }
                        $og['area2'] = $business_info['province'].','.$business_info['city'].','.$business_info['district'];
                    }else{
                        $og['area2'] = $sysset['province'].','.$sysset['city'].','.$sysset['district'];
                    }
                }
            }
            if((getcustom('cashier_area_fenhong',$aid) || getcustom('maidan_area_fenhong',$aid))  && $og['bid']>0){
                if(isset($businessArr[$og['bid']])){
                    $business_info = $businessArr[$og['bid']];
                }else{
                    $business_info = Db::name('business')->where('id',$og['bid'])->find();
                    $businessArr[$og['bid']] = $business_info;
                }
                if(empty($og['area2'])){
                    $og['area2'] = $business_info['province'].','.$business_info['city'].','.$business_info['district'];
                }
            }
            if(getcustom('product_quanyi_areafenhong',$aid) && $og['product_type']==8 && $is_hexiao==1){
                //权益商品，分红数量要根据核销次数来计算
                $hexiao_num = Db::name('hexiao_shopproduct')->where('ogid',$og['id'])->order('createtime desc')->value('num');
                $mendian_uid = Db::name('hexiao_shopproduct')->where('orderid',$og['orderid'])->where('ogid',$og['id'])->order('createtime asc')->value('uid');
                $user = Db::name('admin_user')->where('id',$mendian_uid)->find();
                if($user['groupid']){
                    $group = Db::name('admin_user_group')->where('id',$user['groupid'])->find();
                    $user['mdid'] = $group['mdid'];
                }
                if($user['mdid'] > 0){
                    $mendian_info = Db::name('mendian')->where('id',$user['mdid'])->find();
                    $og['area2'] = $mendian_info['province'].','.$mendian_info['city'].','.$mendian_info['district'];
                }else{
                    $og['area2'] = '';
                }
            }

            $last_areafenhongbl = 0;
            $last_areafenhongmoney = 0;
            $last_areafenhong_score = 0;
            $last_areafenhong_score_percent = 0;
            $fenhong_manual_custom = getcustom('fenhong_manual',$aid);
            if(!$fenhong_manual_custom || $og['isfg']==1){
                //区域代理分红
                $areaArr = explode(',',$og['area2']);
                $province = $areaArr[0];
                $city = $areaArr[1];
                $area = $areaArr[2];
                foreach($areafhlevellist as $fhlevel){
                    $fhlevel['areafenhong_score'] = 0;
                    if($product['areafenhongset'] == 1){ //按比例
                        $fenhongdata = json_decode($product['areafenhongdata1'],true);
                        if($fenhongdata){
                            $fhlevel['areafenhongbl'] = $fenhongdata[$fhlevel['id']]['commission'];
                            $fhlevel['areafenhong_money'] = 0;
                        }
                    }elseif($product['areafenhongset'] == 2){ //按固定金额
                        $fenhongdata = json_decode($product['areafenhongdata2'],true);
                        if($fenhongdata){
                            $fhlevel['areafenhongbl'] = 0;
                            $fhlevel['areafenhong_money'] = $fenhongdata[$fhlevel['id']]['commission'] * $og['num'];
                        }
                    }elseif($product['areafenhongset'] == 4){ //按积分比例
                        $fenhongdata = json_decode($product['areafenhongdata1'],true);
                        if($fenhongdata){
                            $fhlevel['areafenhongbl'] = 0;
                            $fhlevel['areafenhong_money'] = 0;
                            $fhlevel['areafenhong_score_percent'] = $fenhongdata[$fhlevel['id']]['score'];
                        }
                    }elseif($product['areafenhongset'] == 3){ //按积分
                        if(getcustom('areafenhong_score',$aid)){
                            $fenhongdata2 = json_decode($product['areafenhongdata2'],true);
                            if($fenhongdata2){
                                $fhlevel['areafenhongbl'] = 0;
                                $fhlevel['areafenhong_money'] = 0;
                                $fhlevel['areafenhong_score'] = $fenhongdata2[$fhlevel['id']]['score'] * $og['num'];
                            }
                        }
                    }elseif($product['areafenhongset'] == 7){ //按提成比例+积分
                        if(getcustom('areafenhong_percent_score',$aid)){
                            $fenhongdata = json_decode($product['areafenhongdata1'],true);
                            if($fenhongdata){
                                $fhlevel['areafenhongbl'] = $fenhongdata[$fhlevel['id']]['commission'];
                                $fhlevel['areafenhong_money'] = 0;
                            }
                            $fenhongdata2 = json_decode($product['areafenhongdata2'],true);
                            if($fenhongdata2){
                                $fhlevel['areafenhong_score'] = $fenhongdata2[$fhlevel['id']]['score'] * $og['num'];
                            }
                        }
                    }elseif($product['areafenhongset'] == 8){ //按提成金额+积分
                        if(getcustom('areafenhong_commission_score',$aid)){
                            $fenhongdata = json_decode($product['areafenhongdata2'],true);
                            if($fenhongdata){
                                $fhlevel['areafenhongbl'] = 0;
                                $fhlevel['areafenhong_money'] = $fenhongdata[$fhlevel['id']]['commission'] * $og['num'];
                            }
                            $fenhongdata2 = json_decode($product['areafenhongdata2'],true);
                            if($fenhongdata2){
                                $fhlevel['areafenhong_score'] = $fenhongdata2[$fhlevel['id']]['score'] * $og['num'];
                            }
                        }
                    }elseif($product['areafenhongset'] == -1){
                        $fhlevel['areafenhongbl'] = 0;
                        $fhlevel['areafenhong_money'] = 0;
                    }else{
                        $fhlevel['areafenhong_money'] = 0;
                    }
                    if(getcustom('maidan_fenhong_new',$aid)){
                        if($og['module'] == 'maidan'){
                            if($product['areafenhongset'] == 0){
                                $fhlevel['areafenhongbl']   = $fhlevel['areafenhongbl_maidan'];
                            }
                        }
                    }

                    if($fhlevel['areafenhongbl']==0 && $fhlevel['areafenhong_money']==0 && $fhlevel['areafenhong_score_percent'] == 0 && $fhlevel['areafenhong_score_percent'] == 0  && $fhlevel['areafenhong_score'] == 0)
                        continue;
                    if($fhlevel['areafenhong'] == 3 && $province && $city && $area){
                        if(($city=='保定市' || $city=='雄安新区') && in_array($area,['雄县','容城县','安新县'])){
                            //兼容雄安新区
                            $memberlist = Db::name('member')->field('id,levelid,areafenhong_province,areafenhongbl,areafenhong')->where('levelid',$fhlevel['id'])->where('areafenhong',0)->where('areafenhong_province',$province)
                                ->where('areafenhong_city','in',['保定市','雄安新区'])->where('areafenhong_area',$area)->select()->toArray();
                        }else{
                            $memberlist = Db::name('member')->field('id,levelid,areafenhong_province,areafenhongbl,areafenhong')->where('levelid',$fhlevel['id'])->where('areafenhong',0)->where('areafenhong_province',$province)->where('areafenhong_city',$city)->where('areafenhong_area',$area)->select()->toArray();
                        }
                        if(getcustom('plug_sanyang',$aid)){
                            $memberlist_extend = Db::name('member_level_record')->field('mid id,levelid,areafenhong_province,areafenhongbl,areafenhong')->where('levelid',$fhlevel['id'])->where('areafenhong',0)->where('areafenhong_province',$province)->where('areafenhong_city',$city)->where('areafenhong_area',$area)->select()->toArray();
                        }
                        if($sysset['areafenhong_jiaquan'] == 1 && $largearealevelids){ //大区代理也参与
                            $largeareaList = Db::name('largearea')->where("find_in_set('{$province}',province)")->where('status',1)->column('name');
                            $memberlist10 = Db::name('member')->field('id,levelid,areafenhong_largearea,areafenhongbl,areafenhong')->where('levelid','in',$largearealevelids)->where('areafenhong',0)->where('areafenhong_largearea','in',$largeareaList)->select()->toArray();
                            $memberlist = array_merge((array)$memberlist, (array)$memberlist10);
                        }
                        if($sysset['areafenhong_jiaquan'] == 1 && ($provincelevelids || $citylevelids)){ 
                            if($provincelevelids){
                                $memberlist1 = Db::name('member')->field('id,levelid,areafenhong_province,areafenhongbl,areafenhong')->where('levelid','in',$provincelevelids)->where('areafenhong',0)->where('areafenhong_province',$province)->select()->toArray();
                            }else{
                                $memberlist1 = [];
                            }
                            if($citylevelids){
                                $memberlist2 = Db::name('member')->field('id,levelid,areafenhong_province,areafenhongbl,areafenhong')->where('levelid','in',$citylevelids)->where('areafenhong',0)->where('areafenhong_province',$province)->where('areafenhong_city',$city)->select()->toArray();
                            }else{
                                $memberlist2 = [];
                            }
                            $memberlist = array_merge((array)$memberlist, (array)$memberlist1, (array)$memberlist2);
                            //Log::write('$memberlist 3');
                            //Log::write($memberlist);
                        }
                    }
                    if($fhlevel['areafenhong'] == 2 && $province && $city){
                        $memberlist = Db::name('member')->field('id,levelid,areafenhong_province,areafenhongbl,areafenhong')->where('levelid', $fhlevel['id'])->where('areafenhong', 0)->where('areafenhong_province', $province)->where('areafenhong_city', $city)->select()->toArray();
                        if(getcustom('plug_sanyang',$aid)){
                            $memberlist_extend = Db::name('member_level_record')->field('mid id,levelid,areafenhong_province,areafenhongbl,areafenhong')->where('levelid',$fhlevel['id'])->where('areafenhong',0)->where('areafenhong_province',$province)->where('areafenhong_city',$city)->select()->toArray();
                        }
                        if($sysset['areafenhong_jiaquan'] == 1 && $largearealevelids){ //大区代理也参与
                            $largeareaList = Db::name('largearea')->where("find_in_set('{$province}',province)")->where('status',1)->column('name');
                            $memberlist10 = Db::name('member')->field('id,levelid,areafenhong_largearea,areafenhongbl,areafenhong')->where('levelid','in',$largearealevelids)->where('areafenhong',0)->where('areafenhong_largearea','in',$largeareaList)->select()->toArray();
                            $memberlist = array_merge((array)$memberlist, (array)$memberlist10);
                        }
                        if($sysset['areafenhong_jiaquan'] == 1 && $provincelevelids){ //省级代理也参与
                            $memberlist1 = Db::name('member')->field('id,levelid,areafenhong_province,areafenhongbl,areafenhong')->where('levelid','in',$provincelevelids)->where('areafenhong',0)->where('areafenhong_province',$province)->select()->toArray();
                            $memberlist = array_merge((array)$memberlist, (array)$memberlist1);
                            //Log::write('$memberlist 2');
                            //Log::write($memberlist);
                        }
                    }
                    if($fhlevel['areafenhong'] == 1 && $province){
                        $memberlist = Db::name('member')->field('id,levelid,areafenhong_province,areafenhongbl,areafenhong')->where('levelid',$fhlevel['id'])->where('areafenhong',0)->where('areafenhong_province',$province)->select()->toArray();
                        if(getcustom('plug_sanyang',$aid))
                        $memberlist_extend = Db::name('member_level_record')->field('mid id,levelid,areafenhong_province,areafenhongbl,areafenhong')->where('levelid',$fhlevel['id'])->where('areafenhong',0)->where('areafenhong_province',$province)->select()->toArray();
                        
                        if($sysset['areafenhong_jiaquan'] == 1 && $largearealevelids){ //大区代理也参与
                            $largeareaList = Db::name('largearea')->where("find_in_set('{$province}',province)")->where('status',1)->column('name');
                            $memberlist10 = Db::name('member')->field('id,levelid,areafenhong_largearea,areafenhongbl,areafenhong')->where('levelid','in',$largearealevelids)->where('areafenhong',0)->where('areafenhong_largearea','in',$largeareaList)->select()->toArray();
                            $memberlist = array_merge((array)$memberlist, (array)$memberlist10);
                        }
                    }
                    if($fhlevel['areafenhong'] == 10 && $province){
                        $largeareaList = Db::name('largearea')->where("find_in_set('{$province}',province)")->where('status',1)->column('name');
                        $memberlist = Db::name('member')->field('id,levelid,areafenhong_largearea,areafenhongbl,areafenhong')->where('levelid',$fhlevel['id'])->where('areafenhong',0)->where('areafenhong_largearea','in',$largeareaList)->select()->toArray();
                    }
                    if(getcustom('plug_sanyang',$aid)){
                        $memberlist = array_merge((array)$memberlist, (array)$memberlist_extend);
                    }
                    if($memberlist){
                        $this_areafenhongbl = $fhlevel['areafenhongbl'];
                        $this_areafenhong_score_percent = $fhlevel['areafenhong_score_percent'];
                        if(($this_areafenhongbl > 0 || $this_areafenhong_score_percent > 0) && $isjicha){
                            $this_areafenhongbl = $fhlevel['areafenhongbl'] - $last_areafenhongbl;
                            $this_areafenhong_score_percent = $fhlevel['areafenhong_score_percent'] - $last_areafenhong_score_percent;
                        }
                        $last_areafenhongbl = $last_areafenhongbl + $this_areafenhongbl;
                        $last_areafenhong_score_percent = $last_areafenhong_score_percent + $this_areafenhong_score_percent;
                        
                        $areafenhong_money = $fhlevel['areafenhong_money'];
                        if($fhlevel['areafenhong_money'] > 0 && $isjicha){
                            $areafenhong_money = $fhlevel['areafenhong_money'] - $last_areafenhongmoney;
                        }
                        $last_areafenhongmoney = $last_areafenhongmoney + $areafenhong_money;

                        $areafenhong_score = $fhlevel['areafenhong_score'];
                        if($areafenhong_score > 0 && $isjicha){
                            $areafenhong_score = $fhlevel['areafenhong_score'] - $last_areafenhong_score;
                        }
                        $last_areafenhong_score = $last_areafenhong_score + $areafenhong_score;

                        $jq_sendtime_yj = 0;
                        if(getcustom('fenhong_jiaquan_area',$aid) && $fhlevel['fenhong_jiaquan_area_pjbl'] > 0){
                            //区域代理加权平均分红
                            $fenhong_jiaquan_area_pjbl = $fhlevel['fenhong_jiaquan_area_pjbl'] / 100;
                            $commission = ($this_areafenhongbl * $fenhongprice * 0.01 + $areafenhong_money) * $fenhong_jiaquan_area_pjbl / count($memberlist);
                            //当前季度结束发放时间（下个季度的凌晨00:27）
                            $season = ceil((date('n', time()))/3);//当月是第几季度
                            $jq_sendtime_yj = mktime(23,59,0,$season*3,date('t',mktime(0, 0 , 0,$season*3,1,date("Y"))),date('Y')) + 28 * 60;
                        }else{
                            $commission = ($this_areafenhongbl * $fenhongprice * 0.01 + $areafenhong_money) / count($memberlist);
                        }
                        $commission_score = floor(($this_areafenhong_score_percent * $fenhongprice * 0.01) / count($memberlist));
                        $commission_score = $commission_score + $areafenhong_score;

                        if(getcustom('product_quanyi_areafenhong',$aid) && $og['product_type']==8 && $is_hexiao==1){
                            //权益商品，分红数量要根据核销次数来计算
                            $commission = bcmul($hexiao_num,bcdiv($commission,$og['hexiao_num_total'],4),$commission_weishu);
                            $commission_score = bcmul($hexiao_num,bcdiv($commission_score,$og['hexiao_num_total'],4),2);
                        }
                        if($commission <= 0 && $commission_score <= 0) continue;
                        //Log::write('$commission');
                        //Log::write($commission);
                        if(getcustom('fenhong_removefenxiao',$aid) && $fhlevel['areafenhong_removefenxiao'] == 1){
                            if($og['parent1'] && $og['parent1commission']){
                                $commission = $commission - $og['parent1commission'];
                            }
                            if($og['parent2'] && $og['parent2commission']){
                                $commission = $commission - $og['parent2commission'];
                            }
                            if($og['parent3'] && $og['parent3commission']){
                                $commission = $commission - $og['parent3commission'];
                            }
                            if($commission <= 0) continue;
                        }

                        if($commissionpercent != 1){
                            $fenhongcommission = round($commission*$commissionpercent,$commission_weishu);
                            $fenhongmoney = round($commission*$moneypercent,2);
                            $fenhongscore = round($commission_score*$commissionpercent);
                        }else{
                            $fenhongcommission = $commission;
                            $fenhongmoney = 0;
                            $fenhongscore = $commission_score;
                        }

                        foreach($memberlist as $member){
                            //重新给会员发放佣金赋值，以兼容会员发放佣金的重新计算
                            $midcommission = $commission;
                            $mid = $member['id'];
                            if($isyj == 1 && $yjmid == $mid){
                                $commissionyj_my += $midcommission;
                            }
                            if($midareafhArr[$mid]){
                                $midareafhArr[$mid]['totalcommission'] = $midareafhArr[$mid]['totalcommission'] + $midcommission;
                                $midareafhArr[$mid]['commission'] = $midareafhArr[$mid]['commission'] + $fenhongcommission;
                                $midareafhArr[$mid]['money'] = $midareafhArr[$mid]['money'] + $fenhongmoney;
                                $midareafhArr[$mid]['score'] = $midareafhArr[$mid]['score'] + $fenhongscore;
                                $midareafhArr[$mid]['ogids'][] = $og['id'];
                                $midareafhArr[$mid]['levelid'] = $member['levelid'];
                                $midareafhArr[$mid]['jq_sendtime_yj'] = $jq_sendtime_yj ?? 0;
                            }else{
                                $midareafhArr[$mid] = [
                                    'totalcommission'=>$midcommission,
                                    'commission'=>$fenhongcommission,
                                    'money'=>$fenhongmoney,
                                    'score'=>$fenhongscore,
                                    'ogids'=>[$og['id']],
                                    'levelid'=>$member['levelid'],
                                    'module'=>$og['module'] ?? 'shop',
                                    'jq_sendtime_yj'=>$jq_sendtime_yj ?? 0
                                ];
                            }
                            if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                self::fhrecord($aid,$mid,$fenhongcommission,$fenhongscore,$og['id'],$og['module'] ?? 'shop','areafenhong',t('区域代理分红',$aid));
                            }
                        }
                    }
                }

                //如果商品设置为不等于-1不参与分红，则先根据商品设置后根据会员设置发分红
                if($product['areafenhongset']!=-1){
                    //单独设置的区域代理（会员编辑 分红比例>0）
                    if($areamemberlist){
                        foreach($areamemberlist as $member){
                            if(
                                ($member['areafenhong']==1 && $member['areafenhong_province'] == $province) || 
                                ($member['areafenhong']==2 && $member['areafenhong_province'] == $province && $member['areafenhong_city'] == $city) || 
                                ($member['areafenhong']==3 && $member['areafenhong_province'] == $province && $member['areafenhong_city'] == $city && $member['areafenhong_area'] == $area) || 
                                ($member['areafenhong']==10 && in_array($member['areafenhong_largearea'],Db::name('largearea')->where("find_in_set('{$province}',province)")->where('status',1)->column('name')))
                            ){

                                $commission = 0;
                                $commission_score = 0;
                                if($product['areafenhongset'] == 1 || $product['areafenhongset'] == 2) {//商品设置分红，1单独设置分红比例,2单独设置分红金额
                                    $areafenhongbl = 0;
                                    $areafenhong_money = 0;
                                    $areafenhong_score_percent = 0;
                                    if ($product['areafenhongset'] == 1) { //按比例
                                        $fenhongdata = json_decode($product['areafenhongdata1'], true);
                                        if ($fenhongdata) {
                                            $areafenhongbl = $fenhongdata[$member['levelid']]['commission'];
                                            $areafenhong_money = 0;
                                        }
                                    } else { //按固定金额
                                        $fenhongdata = json_decode($product['areafenhongdata2'], true);
                                        if ($fenhongdata) {
                                            $areafenhongbl = 0;
                                            $areafenhong_money = $fenhongdata[$member['levelid']]['commission'] * $og['num'];
                                        }
                                    }
                                    $commission = ($areafenhongbl * $fenhongprice * 0.01 + $areafenhong_money);
                                }elseif($product['areafenhongset'] == 4){
                                    //单独设置分红积分比例
                                    $fenhongdata = json_decode($product['areafenhongdata1'], true);
                                    if ($fenhongdata) {
                                        $areafenhong_score_percent = $fenhongdata[$member['levelid']]['score'];
                                        $areafenhong_money = 0;
                                        $commission_score = round($areafenhong_score_percent * $fenhongprice * 0.01);
                                    }
                                }else{
                                    if(getcustom('maidan_fenhong_new',$aid) && $og['module']=='maidan'){
                                        $member['areafenhongbl'] = $member['areafenhongbl_maidan'];
                                    }
                                    $commission = $fenhongprice * 0.01 * $member['areafenhongbl'];
                                    //$commission_score = round($areafenhong_score_percent * $fenhongprice * 0.01);//暂无单独设置
                                }

                                if(getcustom('yuyue_areafenhong_removefx',$aid)){
                                    if($og['parent1'] && $og['parent1commission']){
                                        $commission = $commission - $og['parent1commission'];
                                    }
                                    if($og['parent2'] && $og['parent2commission']){
                                        $commission = $commission - $og['parent2commission'];
                                    }
                                    if($og['parent3'] && $og['parent3commission']){
                                        $commission = $commission - $og['parent3commission'];
                                    }
                                    if($commission <= 0) continue;
                                }

                                if(getcustom('product_quanyi_areafenhong',$aid) && $og['product_type']==8 && $is_hexiao==1){
                                    //权益商品，分红数量要根据核销次数来计算
                                    $commission = bcmul($hexiao_num,bcdiv($commission,$og['hexiao_num_total'],4),$commission_weishu);
                                    $commission_score = bcmul($hexiao_num,bcdiv($commission_score,$og['hexiao_num_total'],4),2);
                                }
                                $mid = $member['id'];
                                if($isyj == 1 && $yjmid == $mid){
                                    $commissionyj_my += $commission;
                                }
                                if($commissionpercent != 1){
                                    $fenhongcommission = round($commission*$commissionpercent,$commission_weishu);
                                    $fenhongmoney = round($commission*$moneypercent,2);
                                    $fenhongscore = $commission_score;
                                }else{
                                    $fenhongcommission = $commission;
                                    $fenhongmoney = 0;
                                    $fenhongscore = $commission_score;
                                }
                                if($midareafhArr[$mid]){
                                    $midareafhArr[$mid]['totalcommission'] = $midareafhArr[$mid]['totalcommission'] + $commission;
                                    $midareafhArr[$mid]['commission'] = $midareafhArr[$mid]['commission'] + $fenhongcommission;
                                    $midareafhArr[$mid]['money'] = $midareafhArr[$mid]['money'] + $fenhongmoney;
                                    $midareafhArr[$mid]['score'] = $midareafhArr[$mid]['score'] + $fenhongscore;
                                    $midareafhArr[$mid]['ogids'][] = $og['id'];
                                }else{
                                    $midareafhArr[$mid] = [
                                        'totalcommission'=>$commission,
                                        'commission'=>$fenhongcommission,
                                        'money'=>$fenhongmoney,
                                        'score'=>$fenhongscore,
                                        'ogids'=>[$og['id']],
                                        'levelid'=>$member['levelid'],
                                        'module'=>$og['module'] ?? 'shop'
                                    ];
                                }
                                if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                    self::fhrecord($aid,$mid,$fenhongcommission,$fenhongscore,$og['id'],$og['module'] ?? 'shop','areafenhong',t('区域代理分红',$aid));
                                }
                            }
                        }
                    }

                    //多区域代理分红独立设置金额
                    $member_area_agent_multi_price = getcustom('member_area_agent_multi_price',$aid)?:0;
                    $isjicha_multi = ($sysset['areafenhong_differential_multi'] == 1 ? true : false);
                    if(getcustom('member_area_agent_multi',$aid)  && $province){
                        $where2 = [['ma.areafenhongbl','>',0]];
                        $field = 'ma.*,m.levelid';
                        if($member_area_agent_multi_price){
                            //独立设置金额
                            $field = $field.',m.areafenhong_province_commission,m.areafenhong_city_commission,m.areafenhong_area_commission';
                            $where2 = 'ma.areafenhongbl>0 or m.areafenhong_province_commission>0 or m.areafenhong_city_commission>0 or m.areafenhong_area_commission>0';
                        }
                        $where3 = 'ma.areafenhong_province="'.$province.'"';
                        if($city){
                            $where3 .= ' or (ma.areafenhong_province="'.$province.'" and ma.areafenhong_city="'.$city.'")';
                        }
                        if($area){
                            $where3 .= ' or (ma.areafenhong_province="'.$province.'" and ma.areafenhong_city="'.$city.'" and ma.areafenhong_area="'.$area.'")';
                        }
                        $areamemberlist2 = Db::name('member_area_agent')->alias('ma')->leftJoin('member m', 'm.id = ma.mid')
                            ->where('ma.aid',$aid)->where('ma.areafenhong','in',[1,2,3])
                            ->where($where2)
                            ->where($where3)
                            ->fieldRaw($field)
                            ->order('ma.areafenhong desc,ma.id asc')
                            ->select()->toArray();
                    }

                    //单独设置的区域代理
                    if($areamemberlist2){
                        $last_area_fenhong = 0;
                        foreach($areamemberlist2 as $member){
                            if(
                                ($member['areafenhong']==1 && $member['areafenhong_province'] == $province) ||
                                ($member['areafenhong']==2 && $member['areafenhong_province'] == $province && $member['areafenhong_city'] == $city) ||
                                ($member['areafenhong']==3 && $member['areafenhong_province'] == $province && $member['areafenhong_city'] == $city && $member['areafenhong_area'] == $area)
                            ){
                                $commission = 0;
                                $commission_score = 0;
                                if($product['areafenhongset'] == 1 || $product['areafenhongset'] == 2){
                                    $areafenhongbl     = 0;
                                    $areafenhong_money = 0;
                                    if($product['areafenhongset'] == 1){ //按比例
                                        $fenhongdata = json_decode($product['areafenhongdata1'],true);
                                        if($fenhongdata){
                                            $areafenhongbl     = $fenhongdata[$member['levelid']]['commission'];
                                            $areafenhong_money = 0;
                                        }
                                    }else{ //按固定金额
                                        $fenhongdata = json_decode($product['areafenhongdata2'],true);
                                        if($fenhongdata){
                                            $areafenhongbl     = 0;
                                            $areafenhong_money = $fenhongdata[$member['levelid']]['commission'] * $og['num'];
                                        }
                                    }
                                    $commission = ($areafenhongbl * $fenhongprice * 0.01 + $areafenhong_money);
                                }elseif($product['areafenhongset'] == 4){
                                    //按积分比例
                                    $fenhongdata = json_decode($product['areafenhongdata1'], true);
                                    if ($fenhongdata) {
                                        $areafenhong_score_percent = $fenhongdata[$member['levelid']]['score'];
                                        $areafenhong_money = 0;
                                        $commission_score = round($areafenhong_score_percent * $fenhongprice * 0.01);
                                    }
                                }else{
                                    $commission = $fenhongprice * 0.01 * $member['areafenhongbl'];
                                    //$commission_score = round($fenhongprice * 0.01 * $member['areafenhongbl']);//暂无单独设置
                                }
                                $commission_price = 0;
                                if($member_area_agent_multi_price) {
                                    //独立设置分红金额
                                    if ($member['areafenhong'] == 1 && $member['areafenhong_province_commission'] > 0) {
                                        $commission_price = bcmul($member['areafenhong_province_commission'],$og['num'],$commission_weishu);
                                    }
                                    if ($member['areafenhong'] == 2 && $member['areafenhong_city_commission'] > 0) {
                                        $commission_price = bcmul($member['areafenhong_city_commission'],$og['num'],$commission_weishu);
                                    }
                                    if ($member['areafenhong'] == 3 && $member['areafenhong_area_commission'] > 0) {
                                        $commission_price = bcmul($member['areafenhong_area_commission'],$og['num'],$commission_weishu);
                                    }
                                }

                                if($member_area_agent_multi_price && $isjicha_multi){
                                    //区域代理分红级差
                                    $old_commission_price = $commission_price;
                                    $commission_price = bcsub($commission_price,$last_area_fenhong,$commission_weishu);
                                    $last_area_fenhong = $old_commission_price;
                                }
                                $commission = bcadd($commission,$commission_price,$commission_weishu);
                                if(getcustom('yuyue_areafenhong_removefx',$aid)){
                                    if($og['parent1'] && $og['parent1commission']){
                                        $commission = $commission - $og['parent1commission'];
                                    }
                                    if($og['parent2'] && $og['parent2commission']){
                                        $commission = $commission - $og['parent2commission'];
                                    }
                                    if($og['parent3'] && $og['parent3commission']){
                                        $commission = $commission - $og['parent3commission'];
                                    }
                                    if($commission <= 0) continue;
                                }

                                if(getcustom('product_quanyi_areafenhong',$aid) && $og['product_type']==8 && $is_hexiao==1){
                                    //权益商品，分红数量要根据核销次数来计算
                                    $commission = bcmul($hexiao_num,bcdiv($commission,$og['hexiao_num_total'],4),$commission_weishu);
                                    $commission_score = bcmul($hexiao_num,bcdiv($commission_score,$og['hexiao_num_total'],4),2);
                                }
                                $mid = $member['mid'];
                                if($isyj == 1 && $yjmid == $mid){
                                    $commissionyj_my += $commission;
                                }
                                if($commissionpercent != 1){
                                    $fenhongcommission = round($commission*$commissionpercent,$commission_weishu);
                                    $fenhongmoney = round($commission*$moneypercent,2);
                                    $fenhongscore = $commission_score;
                                }else{
                                    $fenhongcommission = $commission;
                                    $fenhongmoney = 0;
                                    $fenhongscore = $commission_score;
                                }
                                if($midareafhArr[$mid]){
                                    $midareafhArr[$mid]['totalcommission'] = $midareafhArr[$mid]['totalcommission'] + $commission;
                                    $midareafhArr[$mid]['commission'] = $midareafhArr[$mid]['commission'] + $fenhongcommission;
                                    $midareafhArr[$mid]['money'] = $midareafhArr[$mid]['money'] + $fenhongmoney;
                                    $midareafhArr[$mid]['score'] = $midareafhArr[$mid]['score'] + $fenhongscore;
                                    $midareafhArr[$mid]['ogids'][] = $og['id'];
                                }else{
                                    $midareafhArr[$mid] = [
                                        'totalcommission'=>$commission,
                                        'commission'=>$fenhongcommission,
                                        'money'=>$fenhongmoney,
                                        'score'=>$fenhongscore,
                                        'ogids'=>[$og['id']],
                                        'levelid'=>$member['levelid'],
                                        'module'=>$og['module'] ?? 'shop'
                                    ];
                                }
                                if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                    self::fhrecord($aid,$mid,$fenhongcommission,$fenhongscore,$og['id'],$og['module'] ?? 'shop','areafenhong',t('区域代理分红',$aid));
                                }
                            }
                        }
                    }
                }
                //多商户单独设置的区域分红
                if(getcustom('business_area_fenhong',$aid) && $og['bid']>0){
                    if(isset($businessArr[$og['bid']])){
                        $business_info = $businessArr[$og['bid']];
                    }else{
                        $business_info = Db::name('business')->where('id',$og['bid'])->find();
                        $businessArr[$og['bid']] = $business_info;
                    }
                    $b_province = $business_info['province'];
                    $b_province_bl = $business_info['areafenhong_province']??0;
                    $b_city = $business_info['city'];
                    $b_city_bl = $business_info['areafenhong_city']??0;
                    $b_area = $business_info['district'];
                    $b_area_bl = $business_info['areafenhong_district']??0;
                    if($b_province && $b_province_bl>0){
                        $memberProvinceB = Db::name('member')->where('aid',$aid)->where('areafenhong','1')->where('areafenhong_province',$b_province)->field('id,nickname')->select()->toArray();
                        //跟随会员等级的代理
                        $memberProvinceLevelist = Db::name('member_level')->where('aid',$aid)->where('areafenhong','1')->select()->toArray();
                        foreach ($memberProvinceLevelist as $fk=>$lv){
                            $areafenhongmaxnum  = 9999999;
                            if($lv['areafenhongmaxnum']>0) $areafenhongmaxnum = $lv['areafenhongmaxnum'];
                            $memberProvinceTmp = Db::name('member')->where('levelid',$lv['id'])->where('areafenhong',0)->limit($areafenhongmaxnum)->field('id,nickname')->select()->toArray();
                            if($memberProvinceTmp) $memberProvinceB = array_merge($memberProvinceB,$memberProvinceTmp);
                        }
                        if($memberProvinceB) {
                            $commissionProvinceB = ($b_province_bl * $fenhongprice * 0.01) / count($memberProvinceB);
                            if ($commissionProvinceB > 0) {
                                foreach ($memberProvinceB as $bk => $pbmember) {
                                    $mid = $pbmember['id'];
                                    if($midareafhArr2[$mid]){
                                        $midareafhArr2[$mid]['totalcommission'] = $midareafhArr2[$mid]['totalcommission'] + $commissionProvinceB;
                                        $midareafhArr2[$mid]['commission'] = $midareafhArr2[$mid]['commission'] + $commissionProvinceB;
                                        $midareafhArr2[$mid]['ogids'][] = $og['id'];
                                    }else{
                                        $midareafhArr2[$mid] = [
                                            'totalcommission' => $commissionProvinceB,
                                            'commission' => $commissionProvinceB,
                                            'money' => 0,
                                            'score' => 0,
                                            'ogids' => [$og['id']],
                                            'levelid'=>$pbmember['levelid'],
                                            'module' => $og['module'] ?? 'shop',
                                            'remark'=>'商户省级代理分红',
                                        ];
                                    }
                                }
                            }
                        }
                    }
                    if($b_city && $b_city_bl>0){
                        $memberCityB = Db::name('member')->where('aid',$aid)->where('areafenhong','2')->where('areafenhong_province',$b_province)->where('areafenhong_city',$b_city)->field('id,nickname')->select()->toArray();
                        //跟随会员等级的代理
                        $memberCityLevelist = Db::name('member_level')->where('aid',$aid)->where('areafenhong','2')->select()->toArray();
                        foreach ($memberCityLevelist as $fk=>$lv){
                            $areafenhongmaxnum  = 9999999;
                            if($lv['areafenhongmaxnum']>0) $areafenhongmaxnum = $lv['areafenhongmaxnum'];
                            $memberCityTmp = Db::name('member')->where('levelid',$lv['id'])->where('areafenhong',0)->limit($areafenhongmaxnum)->field('id,nickname')->select()->toArray();
                            if($memberCityTmp) $memberCityB = array_merge($memberCityB,$memberCityTmp);
                        }
                        if($memberCityB){
                            $commissionCityB = ($b_city_bl * $fenhongprice * 0.01) / count($memberCityB);
                            if($commissionCityB>0){
                                foreach ($memberCityB as $ck=>$cbmember){
                                    $mid = $cbmember['id'];
                                    if($midareafhArr2[$mid]){
                                        $midareafhArr2[$mid]['totalcommission'] = $midareafhArr2[$mid]['totalcommission'] + $commissionCityB;
                                        $midareafhArr2[$mid]['commission'] = $midareafhArr2[$mid]['commission'] + $commissionCityB;
                                        $midareafhArr2[$mid]['ogids'][] = $og['id'];
                                    }else{
                                        $midareafhArr2[$mid] = [
                                            'totalcommission' => $commissionCityB,
                                            'commission' => $commissionCityB,
                                            'money' => 0,
                                            'score' => 0,
                                            'ogids' => [$og['id']],
                                            'levelid'=>$cbmember['levelid'],
                                            'module' => $og['module'] ?? 'shop',
                                            'remark'=>'商户市级代理分红',
                                        ];
                                    }
                                }
                            }
                        }
                    }
                    if($b_area && $b_area_bl>0){
                        $memberAreaB = Db::name('member')->where('aid',$aid)->where('areafenhong','3')->where('areafenhong_province',$b_province)->where('areafenhong_city',$b_city)->where('areafenhong_area',$b_area)->field('id,nickname')->select()->toArray();

                        //跟随会员等级的代理
                        $memberAreaLevelist = Db::name('member_level')->where('aid',$aid)->where('areafenhong','3')->where('areafenhongbl','>',0)->select()->toArray();
                        foreach ($memberAreaLevelist as $fk=>$lv){
                            $areafenhongmaxnum  = 9999999;
                            if($lv['areafenhongmaxnum']>0) $areafenhongmaxnum = $lv['areafenhongmaxnum'];
                            $memberAreaTmp = Db::name('member')->where('levelid',$lv['id'])->where('areafenhong',0)->limit($areafenhongmaxnum)->field('id,nickname')->select()->toArray();
                            if($memberAreaTmp) $memberAreaB = array_merge($memberAreaB,$memberAreaTmp);
                        }
                        if($memberAreaB){
                            $commissionAreaB = ($b_area_bl * $fenhongprice * 0.01) / count($memberAreaB);
                            if($commissionAreaB>0){
                                foreach ($memberAreaB as $ak=>$abmember){
                                    $mid = $abmember['id'];
                                    if($midareafhArr2[$mid]){
                                        $midareafhArr2[$mid]['totalcommission'] = $midareafhArr2[$mid]['totalcommission'] + $commissionAreaB;
                                        $midareafhArr2[$mid]['commission'] = $midareafhArr2[$mid]['commission'] + $commissionAreaB;
                                        $midareafhArr2[$mid]['ogids'][] = $og['id'];
                                    }else{
                                        $midareafhArr2[$mid] = [
                                            'totalcommission' => $commissionAreaB,
                                            'commission' => $commissionAreaB,
                                            'money' => 0,
                                            'score' => 0,
                                            'ogids' => [$og['id']],
                                            'levelid'=>$abmember['levelid'],
                                            'module' => $og['module'] ?? 'shop',
                                            'remark'=>'商户县区级代理分红',
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if($isyj == 1 && $commissionyj_my > 0){
                $commissionyj += $commissionyj_my;
                $og['commission'] = round($commissionyj_my,2);
                $og['fhname'] = t('区域代理分红',$aid);
                $newoglist[] = $og;
            }

            self::fafang($aid,$midareafhArr,'areafenhong',t('区域代理分红',$aid),$og['mid']);
            if(getcustom('business_area_fenhong',$aid) && $midareafhArr2){
                self::fafang($aid,$midareafhArr2,'areafenhong',t('区域代理商户分红',$aid));
            }
            //根据分红奖团队收益
            if(getcustom('teamfenhong_shouyi',$aid)){
                self::teamshouyi($aid,$sysset,$midareafhArr,$oglist,$isyj,$yjmid,$commissionpercent,$moneypercent);
            }
            $midareafhArr = [];
            $midareafhArr2= [];
        }
    }
    //商品团队分红
    public static function product_teamfenhong($aid,$sysset,$oglist,$starttime=0,$endtime=0,$isyj=0,$yjmid=0){
        if($endtime == 0) $endtime = time();
        if(!getcustom('product_teamfenhong',$aid)) return ['commissionyj'=>0,'oglist'=>[]];
        if($isyj == 1 && !$oglist){
            //多商户的商品是否参与分红
            if($sysset['fhjiesuanbusiness'] == 1){
                $bwhere = '1=1';
            }else{
                $bwhere = [['og.bid','=','0']];
            }
            $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
        }
        if(!$oglist) return ['commissionyj'=>0,'oglist'=>[]];
        //参与商品团队分红的等级
        $product_fhlevellist = Db::name('member_level')->where('aid',$aid)->where('product_teamfenhonglv','>','0')->where('product_teamfenhong_money','>',0)->where('product_teamfenhong_ids','<>','')->column('id,cid,name,product_teamfenhonglv,product_teamfenhong_ids,product_teamfenhongonly,product_teamfenhong_money,product_teamfenhong_self','id');
        if(!$product_fhlevellist) return ['commissionyj'=>0,'oglist'=>[]];
        
        $defaultCid = Db::name('member_level_category')->where('aid',$aid)->where('isdefault', 1)->value('id');
        if($defaultCid) {
            $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->where('cid', $defaultCid)->column('id');
        } else {
            $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->column('id');
        }

        $isjicha = ($sysset['teamfenhong_differential'] == 1 ? true : false);
        $ogids = [];
        $mid_product_teamfhArr = [];
        
        $newoglist = [];
        $commissionyj = 0;
        foreach($oglist as $og){
            $commissionyj_my = 0;
            if(getcustom('commission2moneypercent',$aid) && $sysset['commission2moneypercent1'] > 0){
                //是否是首单
                $beforeorder = Db::name('shop_order')->where('aid',$aid)->where('mid',$og['mid'])->where('status','in','1,2,3')->where('paytime','<',$og['paytime'])->find();
                if(!$beforeorder){
                    $commissionpercent = 1 - $sysset['commission2moneypercent1'] * 0.01;
                    $moneypercent = $sysset['commission2moneypercent1'] * 0.01;
                }else{
                    $commissionpercent = 1 - $sysset['commission2moneypercent2'] * 0.01;
                    $moneypercent = $sysset['commission2moneypercent2'] * 0.01;
                }
            }else{
                $commissionpercent = 1;
                $moneypercent = 0;
            }
            if($sysset['fhjiesuantype'] == 0){
                $fenhongprice = $og['real_totalprice'];
            }else{
                $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
            }
            if(getcustom('money_dec_commission_fenhong',$aid)){
                if($og['module'] == 'maidan' && $sysset['money_dec_fenxiao_fenhong'] == 1){
                    //买单分红 按照销售额、抵扣部分参与分销分红开启时 增加抵扣金额
                    $fenhongprice += $og['dec_money'];
                }elseif($og['module'] == 'shop' && $sysset['money_dec_fenxiao_fenhong'] == 0){
                    //商城分红 按照销售、抵扣部分参与分销分红关闭时 扣除抵扣金额
                    $fenhongprice -= $og['dec_money'];
                }
            }
            if(getcustom('yx_buyer_subsidy',$aid)){
                if($sysset['fhjiesuantype'] == 2){
                    //按抽成计算
                    $fenhongprice = $og['order_fee']??0 ;
                }
            }
            if(getcustom('baikangxie',$aid)){
                $fenhongprice = $og['cost_price'] * $og['num'];
            }
            if($fenhongprice <= 0) continue;
            $ogids[] = $og['id'];
            $allfenhongprice = $allfenhongprice + $fenhongprice;
            $member = Db::name('member')->where('id', $og['mid'])->find();
            $member_extend = Db::name('member_level_record')->field('mid id,levelid')->where('aid', $aid)->where('mid', $og['mid'])->find();
            
            $prolevelids = [];
            foreach ($product_fhlevellist as $item_pl) {
                if(in_array($og['proid'],explode(',',$item_pl['product_teamfenhong_ids']))) {
                    $prolevelids[] = $item_pl['id'];
                }
            }
            $pids = Db::name('member')->where('id',$og['mid'])->value('path');
            if($pids){
                $pids .= ','.$og['mid']; 
            }else{
                $pids = (string)$og['mid'];
            }
            if($pids){
                $parentList = Db::name('member')->where('id','in',$pids)->where('levelid','in',$prolevelids)->order(Db::raw('field(id,'.$pids.')'))->select()->toArray();
                $parentList = array_reverse($parentList);
                $hasfhlevelids = [];
                $last_teamfenhongbl = 0;
                foreach($parentList as $k=>$parent){
                    $leveldata = $product_fhlevellist[$parent['levelid']];
                    if($parent['levelstarttime'] >= $og['createtime']) {
                        $levelup_order_levelid = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $parent['id'])->where('status', 2)
                            ->where('levelup_time', '<', $og['createtime'])->whereIn('levelid',$defaultLevelIds)->order('levelup_time', 'desc')->value('levelid');
                        if($levelup_order_levelid) {
                            $parent['levelid'] = $levelup_order_levelid;
                            $leveldata = $product_fhlevellist[$parent['levelid']];
                        }
                    }
                    if(!$leveldata || $k>=$leveldata['product_teamfenhonglv']) continue;
                    if($parent['id'] == $og['mid'] && $leveldata['product_teamfenhong_self'] != 1) continue;
                    if($leveldata['product_teamfenhongonly'] == 1 && in_array($parent['levelid'],$hasfhlevelids)) continue; //该等级设置了只给最近的上级分红
                    $hasfhlevelids[] = $parent['levelid'];
                    //每单奖励
                    if($leveldata['product_teamfenhong_money'] > 0) {
                        if($leveldata['product_teamfenhonglv'] == 1 && in_array($parent['levelid'],$hasfhlevelids)) continue; //该等级设置了只给最近的上级分红
                        $hasfhlevelids[] = $parent['levelid'];
                        $commission = $leveldata['product_teamfenhong_money'] * $og['num'];
                        if($isyj == 1 && $yjmid == $parent['id']){
                            $commissionyj_my += $commission;
                        }

                        if($commissionpercent != 1){
                            $fenhongcommission = round($commission*$commissionpercent,2);
                            $fenhongmoney = round($commission*$moneypercent,2);
                        }else{
                            $fenhongcommission = $commission;
                            $fenhongmoney = 0;
                        }

                        if($mid_product_teamfhArr[$parent['id']]){
                            $mid_product_teamfhArr[$parent['id']]['totalcommission'] = $mid_product_teamfhArr[$parent['id']]['totalcommission'] + $commission;
                            $mid_product_teamfhArr[$parent['id']]['commission'] = $mid_product_teamfhArr[$parent['id']]['commission'] + $fenhongcommission;
                            $mid_product_teamfhArr[$parent['id']]['money'] = $mid_product_teamfhArr[$parent['id']]['money'] + $fenhongmoney;
                            $mid_product_teamfhArr[$parent['id']]['ogids'][] = $og['id'];
                        }else{
                            $mid_product_teamfhArr[$parent['id']] = ['totalcommission'=>$commission,'commission'=>$fenhongcommission,'money'=>$fenhongmoney,'ogids'=>[$og['id']],'module'=>$og['module'] ?? 'shop'];
                        }
                        if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                            self::fhrecord($aid,$parent['id'],$fenhongcommission,0,$og['id'],$og['module'] ?? 'shop','product_teamfenhong',t('商品团队分红',$aid));
                        }
                    }
                }
                //其他分组等级
                if(getcustom('plug_sanyang',$aid)) {
                    $catList = Db::name('member_level_category')->where('aid', $aid)->where('isdefault', 0)->select()->toArray();
                    foreach ($catList as $cat) {
                        $parentList = Db::name('member_level_record')->field('mid id,levelid')->where('aid', $aid)->where('cid', $cat['id'])->whereIn('mid', $pids)->where('levelid','in',$prolevelids)->select()->toArray();
                        $parentList = array_reverse($parentList);
                        $hasfhlevelids = [];
                        $last_teamfenhongbl = 0;
                        foreach($parentList as $k=>$parent){
                            $leveldata = $product_fhlevellist[$parent['levelid']];
                            if($parent['levelstarttime'] >= $og['createtime']) {
                                $levelup_order_levelid = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $parent['id'])->where('status', 2)
                                    ->where('levelup_time', '<', $og['createtime'])->whereNotIn('levelid',$defaultLevelIds)->order('levelup_time', 'desc')->value('levelid');
                                if($levelup_order_levelid) {
                                    $parent['levelid'] = $levelup_order_levelid;
                                    $leveldata = $product_fhlevellist[$parent['levelid']];
                                }
                            }
                            if(!$leveldata || $k>=$leveldata['product_teamfenhonglv']) continue;
                            if($parent['id'] == $og['mid'] && $leveldata['product_teamfenhong_self'] != 1) continue;
                            //每单奖励
                            if($leveldata['product_teamfenhong_money'] > 0) {
                                if($leveldata['product_teamfenhonglv'] == 1 && in_array($parent['levelid'],$hasfhlevelids)) continue; //该等级设置了只给最近的上级分红
                                $hasfhlevelids[] = $parent['levelid'];
                                $commission = $leveldata['product_teamfenhong_money'] * $og['num'];
                                if($isyj == 1 && $yjmid == $parent['id']){
                                    $commissionyj_my += $commission;
                                }

                                if($commissionpercent != 1){
                                    $fenhongcommission = round($commission*$commissionpercent,2);
                                    $fenhongmoney = round($commission*$moneypercent,2);
                                }else{
                                    $fenhongcommission = $commission;
                                    $fenhongmoney = 0;
                                }

                                if($mid_product_teamfhArr[$parent['id']]){
                                    $mid_product_teamfhArr[$parent['id']]['totalcommission'] = $mid_product_teamfhArr[$parent['id']]['totalcommission'] + $commission;
                                    $mid_product_teamfhArr[$parent['id']]['commission'] = $mid_product_teamfhArr[$parent['id']]['commission'] + $fenhongcommission;
                                    $mid_product_teamfhArr[$parent['id']]['money'] = $mid_product_teamfhArr[$parent['id']]['money'] + $fenhongmoney;
                                    $mid_product_teamfhArr[$parent['id']]['ogids'][] = $og['id'];
                                }else{
                                    $mid_product_teamfhArr[$parent['id']] = ['totalcommission'=>$commission,'commission'=>$fenhongcommission,'money'=>$fenhongmoney,'ogids'=>[$og['id']],'module'=>$og['module'] ?? 'shop'];
                                }
                                if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                    self::fhrecord($aid,$parent['id'],$fenhongcommission,0,$og['id'],$og['module'] ?? 'shop','product_teamfenhong',t('商品团队分红',$aid));
                                }
                            }
                        }
                    }
                }
            }
            if($isyj == 1 && $commissionyj_my > 0){
                $commissionyj += $commissionyj_my;
                $og['commission'] = round($commissionyj_my,2);
                $og['fhname'] = t('商品团队分红',$aid);
                $newoglist[] = $og;
            }
            self::fafang($aid,$mid_product_teamfhArr,'product_teamfenhong',t('商品团队分红',$aid),$og['mid']);
            //根据分红奖团队收益
            if(getcustom('teamfenhong_shouyi',$aid)){
                self::teamshouyi($aid,$sysset,$mid_product_teamfhArr,$oglist,$isyj,$yjmid,$commissionpercent,$moneypercent);
            }
            $mid_product_teamfhArr = [];
        }
    }
    //等级团队分红
    public static function level_teamfenhong($aid,$sysset,$oglist,$starttime=0,$endtime=0,$isyj=0,$yjmid=0){
        if($endtime == 0) $endtime = time();
        if(getcustom('level_teamfenhong',$aid)){
            //查询此账号是否有等级分红权限
            $uinfo = db('admin_user')->where('aid',$aid)->where('bid',0)->where('isadmin','>=',1)->field('id,auth_type,auth_data')->find();
            if(!$uinfo){ return ['commissionyj'=>0,'oglist'=>[]]; }

            if($uinfo['auth_type'] !=1){
                if(empty($uinfo['auth_data'])){ return ['commissionyj'=>0,'oglist'=>[]]; }

                $auth_data =  json_decode($uinfo['auth_data'],true);
                if(!in_array('level_teamfenhong,level_teamfenhong',$auth_data)){ return ['commissionyj'=>0,'oglist'=>[]]; }
            }

            if($isyj == 1 && !$oglist){
                //多商户的商品是否参与分红
                if($sysset['fhjiesuanbusiness'] == 1){
                    $bwhere = '1=1';
                }else{
                    $bwhere = [['og.bid','=','0']];
                }
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
            }
            if(!$oglist) return ['commissionyj'=>0,'oglist'=>[]];

            //参与等级团队分红的等级
            $level_teamfhlevellist = Db::name('member_level')->where('aid',$aid)->where('level_teamfenhong_ids','<>','')->where('level_teamfenhonglv','>','0')->where(function ($query) {
                $query->where('level_teamfenhongbl','>',0)->whereOr('level_teamfenhong_money','>',0);
            })->column('id,cid,name,level_teamfenhong_ids,level_teamfenhonglv,level_teamfenhongbl,level_teamfenhongonly,level_teamfenhong_money,level_teamfenhongbl_type,level_surpass,level_jicha,sort','id');
            //if(!$level_teamfhlevellist) return ['commissionyj'=>0,'oglist'=>[]];

            $defaultCid = Db::name('member_level_category')->where('aid',$aid)->where('isdefault', 1)->value('id');
            if($defaultCid) {
                $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->where('cid', $defaultCid)->column('id');
            } else {
                $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->column('id');
            }

            $isjicha = ($sysset['teamfenhong_differential'] == 1 ? true : false);
            $ogids = [];
            $midlevel_teamfhArr = [];
            $level_teamfenhong_orderids = [];
            $level_teamfenhong_orderids_cat = [];

            $newoglist = [];
            $commissionyj = 0;
            foreach($oglist as $og){

                //判断是否是商城商品是否开启了等级分红配置
                if(!$og['module'] || empty($og['module']) || $og['module'] == 'shop'){
                    $product = Db::name('shop_product')->where('id',$og['proid'])->field('id,level_teamfenhongset,levelteamfenhongs')->find();
                    if($product){
                        //关闭了等级分红，则不走
                        if($product['level_teamfenhongset'] == -1){
                            continue;
                        //单独设置
                        }else if($product['level_teamfenhongset'] == 1){
                            if(!$product['levelteamfenhongs']){
                                continue;
                            }
                            $levelteamfenhongs = json_decode($product['levelteamfenhongs'],true);
                            if(!$levelteamfenhongs){
                                continue;
                            }

                            $level_teamfhlevellistnew = [];
                            foreach($levelteamfenhongs as $lk=>$lv){
                                //查询等级是否存在
                                $teamlevel = Db::name('member_level')->where('id',$lv['id'])->field('id,cid,name,sort')->find();
                                if(!$teamlevel) continue;
                                //如果团队等级ID为空 或者 分红级数小等于0 或者 分红提成比例或分红固定金额小于等于0且每单分红金额 则不走
                                if($lv['level_teamfenhong_ids'] == '' || $lv['level_teamfenhonglv']<=0 || ($lv['level_teamfenhongbl']<=0 && $lv['level_teamfenhong_money']<=0)){
                                    continue;
                                }
                                $lv['name'] = $teamlevel['name'];
                                $lv['sort'] = $teamlevel['sort'];
                                $level_teamfhlevellistnew[$lk]=$lv;
                            }
                        }else{
                            if(!$level_teamfhlevellist) continue;
                            $level_teamfhlevellistnew = $level_teamfhlevellist;
                        }
                    }else{
                        if(!$level_teamfhlevellist) continue;
                        $level_teamfhlevellistnew = $level_teamfhlevellist;
                    }
                }else{
                    if(!$level_teamfhlevellist) continue;
                    $level_teamfhlevellistnew = $level_teamfhlevellist;
                }

                if(getcustom('commission2moneypercent',$aid) && $sysset['commission2moneypercent1'] > 0){
                    //是否是首单
                    $beforeorder = Db::name('shop_order')->where('aid',$aid)->where('mid',$og['mid'])->where('status','in','1,2,3')->where('paytime','<',$og['paytime'])->find();
                    if(!$beforeorder){
                        $commissionpercent = 1 - $sysset['commission2moneypercent1'] * 0.01;
                        $moneypercent = $sysset['commission2moneypercent1'] * 0.01;
                    }else{
                        $commissionpercent = 1 - $sysset['commission2moneypercent2'] * 0.01;
                        $moneypercent = $sysset['commission2moneypercent2'] * 0.01;
                    }
                }else{
                    $commissionpercent = 1;
                    $moneypercent = 0;
                }

                if($sysset['fhjiesuantype'] == 0){
                    $fenhongprice = $og['real_totalprice'];
                }else{
                    $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                }
                if(getcustom('money_dec_commission_fenhong',$aid)){
                    if($og['module'] == 'maidan' && $sysset['money_dec_fenxiao_fenhong'] == 1){
                        //买单分红 按照销售额、抵扣部分参与分销分红开启时 增加抵扣金额
                        $fenhongprice += $og['dec_money'];
                    }elseif($og['module'] == 'shop' && $sysset['money_dec_fenxiao_fenhong'] == 0){
                        //商城分红 按照销售、抵扣部分参与分销分红关闭时 扣除抵扣金额
                        $fenhongprice -= $og['dec_money'];
                    }
                }
                if(getcustom('yx_buyer_subsidy',$aid)){
                    if($sysset['fhjiesuantype'] == 2){
                        //按抽成计算
                        $fenhongprice = $og['order_fee']??0 ;
                    }
                }
                if(getcustom('baikangxie',$aid)){
                    $fenhongprice = $og['cost_price'] * $og['num'];
                }
                if($fenhongprice <= 0) continue;
                $ogids[] = $og['id'];
                $allfenhongprice = $allfenhongprice + $fenhongprice;
                $member = Db::name('member')->where('id', $og['mid'])->find();
                $member_extend = Db::name('member_level_record')->field('mid id,levelid')->where('aid', $aid)->where('mid', $og['mid'])->find();
                
                $pids = Db::name('member')->where('id',$og['mid'])->value('path');
                if($pids){

                    //筛选上级条件:上级等级id集合
                    $plevelids = [];
                    foreach($level_teamfhlevellistnew as $level) {
                        array_push($plevelids,$level['id']);
                    }

                    //格式化pids数组
                    $pidsarr = explode(',',$pids);
                    //反转数组
                    $pidsarr = array_reverse($pidsarr);

                    //筛选上级
                    $parentList = Db::name('member')->where('id','in',$pids)->whereIn('levelid',$plevelids)->order(Db::raw('field(id,'.$pids.')'))->select()->toArray();
                    if(!count($parentList)) continue;
                    $parentList = array_reverse($parentList);

                    //标记上级的是此会员的多少层级，从0开始
                    foreach($pidsarr as $pk=>$pv){
                        foreach($parentList as &$pv2){
                            if($pv2['id'] == $pv){
                                $pv2['teamnum'] = $pk;
                            }
                        }
                        unset($pv2);
                    }

                    $hasfhlevelids = [];
                    $last_teamcommission   = 0;
                    foreach($parentList as $k=>$parent){

                        //获取上级等级信息
                        $leveldata = $level_teamfhlevellistnew[$parent['levelid']];
                        //判断升级最后时间
                        if($parent['levelstarttime'] >= $og['createtime']) {
                            $levelup_order_levelid = Db::name('member_levelup_order')->where('mid', $parent['id'])->where('status', 2)
                                ->where('levelup_time', '<', $og['createtime'])->where('aid',$aid)->order('levelup_time', 'desc')->value('levelid');
                            if($levelup_order_levelid) {
                                $parent['levelid'] = $levelup_order_levelid;
                                $leveldata = $level_teamfhlevellistnew[$parent['levelid']];
                            }
                        }

                        //等级不存在 或者团队级数超过他级数
                        if(!$leveldata || $parent['teamnum']>=$leveldata['level_teamfenhonglv']) continue;

                        //上级等级设置的团队等级ID
                        $level_teamfenhong_ids = $leveldata['level_teamfenhong_ids']?explode(',',$leveldata['level_teamfenhong_ids']):'';
                        if(!in_array($member['levelid'], $level_teamfenhong_ids)) continue;

                        //查询是否开启需要直属下级超越，开启则需要查询直属下级级别是否超越了此上级
                        if($leveldata['level_surpass'] == 1){
                            //如果上级团队层级是直属上级，则查询直接查本会员的等级
                            if($parent['teamnum']==0){
                                $mlevel = 0+Db::name('member_level')->where('id',$member['levelid'])->where('sort','>',$leveldata['sort'])->count('id');
                                if(!$mlevel) continue;
                            //其他层次上级，查询此层次上级的直属下级等级
                            }else{
                                //查询上级所处位置
                                $pos = array_search($parent['id'],$pidsarr);
                                if($pos>0){
                                    $lowpid = $pidsarr[$pos-1];
                                    //查询直属下级会员信息
                                    $lowparent = Db::name('member')->where('id',$lowpid)->field('levelid')->find();
                                    if(!$lowparent || empty($lowparent)) continue;

                                    //是否符合上级设置团队等级ID的范围
                                    //if(!in_array($lowparent['levelid'], $level_teamfenhong_ids)) continue;

                                    $lowlevel = 0+Db::name('member_level')->where('id',$lowparent['levelid'])->where('sort','>',$leveldata['sort'])->count('id');
                                    if(!$lowlevel) continue;
                                }else{
                                    continue;
                                }
                            }
                        }

                        //每单奖励
                        if($leveldata['level_teamfenhong_money'] > 0 && !in_array($og['orderid'], $level_teamfenhong_orderids[$parent['id']])) {
                            //该等级设置了只给最近的上级分红
                            if($leveldata['level_teamfenhongonly'] == 1 && in_array($parent['levelid'],$hasfhlevelids)) continue; 
                            $hasfhlevelids[] = $parent['levelid'];

                            $commission = $leveldata['level_teamfenhong_money'];
                            if($isyj == 1 && $yjmid == $parent['id']){
                                $commissionyj_my += $commission;
                            }
                            if($commissionpercent != 1){
                                $fenhongcommission = round($commission*$commissionpercent,2);
                                $fenhongmoney = round($commission*$moneypercent,2);
                            }else{
                                $fenhongcommission = $commission;
                                $fenhongmoney = 0;
                            }

                            if($midlevel_teamfhArr[$parent['id']]){
                                $midlevel_teamfhArr[$parent['id']]['totalcommission'] = $midlevel_teamfhArr[$parent['id']]['totalcommission'] + $commission;
                                $midlevel_teamfhArr[$parent['id']]['commission'] = $midlevel_teamfhArr[$parent['id']]['commission'] + $fenhongcommission;
                                $midlevel_teamfhArr[$parent['id']]['money'] = $midlevel_teamfhArr[$parent['id']]['money'] + $fenhongmoney;
                                $midlevel_teamfhArr[$parent['id']]['ogids'][] = $og['id'];
                            }else{
                                $midlevel_teamfhArr[$parent['id']] = ['totalcommission'=>$commission,'commission'=>$fenhongcommission,'money'=>$fenhongmoney,'ogids'=>[$og['id']],'module'=>$og['module'] ?? 'shop'];
                            }
                            if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                self::fhrecord($aid,$parent['id'],$fenhongcommission,0,$og['id'],$og['module'] ?? 'shop','level_teamfenhong',t('等级团队分红',$aid));
                            }
                            $level_teamfenhong_orderids[$parent['id']][] = $og['orderid'];
                        }

                        //分红比例
                        if($leveldata['level_teamfenhongbl'] > 0) {

                            //该等级设置了只给最近的上级分红
                            if($leveldata['level_teamfenhongonly'] == 1 && in_array($parent['levelid'],$hasfhlevelids)) continue;
                            $hasfhlevelids[] = $parent['levelid'];

                            //如果是分红类型是固定分红
                            if($leveldata['level_teamfenhongbl_type'] && $leveldata['level_teamfenhongbl_type'] == 1){
                                //查询是等级团队分红级差单独设置
                                if(!$leveldata['level_jicha']){
                                    if($isjicha){
                                        $commission   = $leveldata['level_teamfenhongbl'] - $last_teamcommission;
                                    }else{
                                        $commission   = $leveldata['level_teamfenhongbl'];
                                    }
                                }else{
                                    if($leveldata['level_jicha'] == 1){
                                        $commission   = $leveldata['level_teamfenhongbl'] - $last_teamcommission;
                                    }else{
                                        $commission   = $leveldata['level_teamfenhongbl'];
                                    }
                                }
                                if($commission <=0) continue;
                                $last_teamcommission = $last_teamcommission + $commission;

                            //如果是分红类型是比例
                            }else{
                                //查询是等级团队分红级差单独设置
                                if(!$leveldata['level_jicha']){
                                    if($isjicha){
                                        $commission = $leveldata['level_teamfenhongbl']* $fenhongprice * 0.01 - $last_teamcommission;
                                    }else{
                                        $commission = $leveldata['level_teamfenhongbl']* $fenhongprice * 0.01;
                                    }
                                }else{
                                    if($leveldata['level_jicha'] == 1){
                                        $commission = $leveldata['level_teamfenhongbl']* $fenhongprice * 0.01 - $last_teamcommission;
                                    }else{
                                        $commission = $leveldata['level_teamfenhongbl']* $fenhongprice * 0.01;
                                    }
                                }
                                if($commission <=0) continue;
                                $last_teamcommission = $last_teamcommission + $commission;
                            }
                            
                            if($isyj == 1 && $yjmid == $parent['id']){
                                $commissionyj_my += $commission;
                            }

                            if($commissionpercent != 1){
                                $fenhongcommission = round($commission*$commissionpercent,2);
                                $fenhongmoney = round($commission*$moneypercent,2);
                            }else{
                                $fenhongcommission = $commission;
                                $fenhongmoney = 0;
                            }

                            if($midlevel_teamfhArr[$parent['id']]){
                                $midlevel_teamfhArr[$parent['id']]['totalcommission'] = $midlevel_teamfhArr[$parent['id']]['totalcommission'] + $commission;
                                $midlevel_teamfhArr[$parent['id']]['commission'] = $midlevel_teamfhArr[$parent['id']]['commission'] + $fenhongcommission;
                                $midlevel_teamfhArr[$parent['id']]['money'] = $midlevel_teamfhArr[$parent['id']]['money'] + $fenhongmoney;
                                $midlevel_teamfhArr[$parent['id']]['ogids'][] = $og['id'];
                            }else{
                                $midlevel_teamfhArr[$parent['id']] = ['totalcommission'=>$commission,'commission'=>$fenhongcommission,'money'=>$fenhongmoney,'ogids'=>[$og['id']],'module'=>$og['module'] ?? 'shop'];
                            }
                            if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                self::fhrecord($aid,$parent['id'],$fenhongcommission,0,$og['id'],$og['module'] ?? 'shop','level_teamfenhong',t('等级团队分红',$aid));
                            }
                        }
                    }

                    //其他分组等级
                    if(getcustom('plug_sanyang',$aid)) {
                        $catList = [];//暂停使用
                        //$catList = Db::name('member_level_category')->where('aid', $aid)->where('isdefault', 0)->select()->toArray();
                        if($catList){
                            foreach ($catList as $cat) {
                                $parentList = Db::name('member_level_record')->field('mid id,levelid')->where('aid', $aid)->whereIn('levelid',$plevelids)->where('cid', $cat['id'])->whereIn('mid', $pids)->select()->toArray();
                                $parentList = array_reverse($parentList);
                                $hasfhlevelids = [];
                                $last_teamcommission   = 0;
                                foreach($parentList as $k=>$parent){

                                    $leveldata = $level_teamfhlevellistnew[$parent['levelid']];
                                    if($parent['levelstarttime'] >= $og['createtime']) {
                                        $levelup_order_levelid = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $parent['id'])->where('status', 2)
                                            ->where('levelup_time', '<', $og['createtime'])->whereNotIn('levelid',$defaultLevelIds)->order('levelup_time', 'desc')->value('levelid');
                                        if($levelup_order_levelid) {
                                            $parent['levelid'] = $levelup_order_levelid;
                                            $leveldata = $level_teamfhlevellistnew[$parent['levelid']];
                                        }
                                    }

                                    //等级不存在 或者团队级数超过他级数
                                    if(!$leveldata /*|| $k>=$leveldata['level_teamfenhonglv']*/) continue;
                                    //最近的上级分不分看当前会员是达到级别
                                    if(!in_array($member_extend['levelid'], explode(',',(string)$leveldata['level_teamfenhong_ids']))) continue;

                                    //每单奖励
                                    if($leveldata['level_teamfenhong_money'] > 0 && !in_array($og['orderid'], $level_teamfenhong_orderids_cat[$parent['id']])) {
                                        if($leveldata['level_teamfenhongonly'] == 1 && in_array($parent['levelid'],$hasfhlevelids)) continue; //该等级设置了只给最近的上级分红
                                        $hasfhlevelids[] = $parent['levelid'];
                                        $commission = $leveldata['level_teamfenhong_money'];
                                        if($isyj == 1 && $yjmid == $parent['id']){
                                            $commissionyj_my += $commission;
                                        }

                                        if($commissionpercent != 1){
                                            $fenhongcommission = round($commission*$commissionpercent,2);
                                            $fenhongmoney = round($commission*$moneypercent,2);
                                        }else{
                                            $fenhongcommission = $commission;
                                            $fenhongmoney = 0;
                                        }

                                        if($midlevel_teamfhArr[$parent['id']]){
                                            $midlevel_teamfhArr[$parent['id']]['totalcommission'] = $midlevel_teamfhArr[$parent['id']]['totalcommission'] + $commission;
                                            $midlevel_teamfhArr[$parent['id']]['commission'] = $midlevel_teamfhArr[$parent['id']]['commission'] + $fenhongcommission;
                                            $midlevel_teamfhArr[$parent['id']]['money'] = $midlevel_teamfhArr[$parent['id']]['money'] + $fenhongmoney;
                                            $midlevel_teamfhArr[$parent['id']]['ogids'][] = $og['id'];
                                        }else{
                                            $midlevel_teamfhArr[$parent['id']] = ['totalcommission'=>$commission,'commission'=>$fenhongcommission,'money'=>$fenhongmoney,'ogids'=>[$og['id']],'module'=>$og['module'] ?? 'shop'];
                                        }
                                        if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                            self::fhrecord($aid,$parent['id'],$fenhongcommission,0,$og['id'],$og['module'] ?? 'shop','level_teamfenhong',t('等级团队分红',$aid));
                                        }
                                        $level_teamfenhong_orderids_cat[$parent['id']][] = $og['orderid'];
                                    }

                                    //分红比例
                                    if($leveldata['level_teamfenhongbl'] > 0) {

                                        if($leveldata['level_teamfenhongonly'] == 1 && in_array($parent['levelid'],$hasfhlevelids)) continue; //该等级设置了只给最近的上级分红
                                        $hasfhlevelids[] = $parent['levelid'];

                                        //如果是分红类型是固定分红
                                        if($leveldata['level_teamfenhongbl_type'] && $leveldata['level_teamfenhongbl_type'] == 1){
                                            //查询是等级团队分红级差单独设置
                                            if(!$leveldata['level_jicha']){
                                                if($isjicha){
                                                    $commission   = $leveldata['level_teamfenhongbl'] - $last_teamcommission;
                                                }else{
                                                    $commission   = $leveldata['level_teamfenhongbl'];
                                                }
                                            }else{
                                                if($leveldata['level_jicha'] == 1){
                                                    $commission   = $leveldata['level_teamfenhongbl'] - $last_teamcommission;
                                                }else{
                                                    $commission   = $leveldata['level_teamfenhongbl'];
                                                }
                                            }
                                            if($commission <=0) continue;
                                            $last_teamcommission = $last_teamcommission + $commission;
                                        }else{
                                            //查询是等级团队分红级差单独设置
                                            if(!$leveldata['level_jicha']){
                                                if($isjicha){
                                                    $commission = $leveldata['level_teamfenhongbl']* $fenhongprice * 0.01 - $last_teamcommission;
                                                }else{
                                                    $commission = $leveldata['level_teamfenhongbl']* $fenhongprice * 0.01;
                                                }
                                            }else{
                                                if($leveldata['level_jicha'] == 1){
                                                    $commission = $leveldata['level_teamfenhongbl']* $fenhongprice * 0.01 - $last_teamcommission;
                                                }else{
                                                    $commission = $leveldata['level_teamfenhongbl']* $fenhongprice * 0.01;
                                                }
                                            }
                                            if($commission <=0) continue;
                                            $last_teamcommission = $last_teamcommission + $commission;
                                        }

                                        if($isyj == 1 && $yjmid == $parent['id']){
                                            $commissionyj_my += $commission;
                                        }

                                        if($commissionpercent != 1){
                                            $fenhongcommission = round($commission*$commissionpercent,2);
                                            $fenhongmoney = round($commission*$moneypercent,2);
                                        }else{
                                            $fenhongcommission = $commission;
                                            $fenhongmoney = 0;
                                        }

                                        if($midlevel_teamfhArr[$parent['id']]){
                                            $midlevel_teamfhArr[$parent['id']]['totalcommission'] = $midlevel_teamfhArr[$parent['id']]['totalcommission'] + $commission;
                                            $midlevel_teamfhArr[$parent['id']]['commission'] = $midlevel_teamfhArr[$parent['id']]['commission'] + $fenhongcommission;
                                            $midlevel_teamfhArr[$parent['id']]['money'] = $midlevel_teamfhArr[$parent['id']]['money'] + $fenhongmoney;
                                            $midlevel_teamfhArr[$parent['id']]['ogids'][] = $og['id'];
                                        }else{
                                            $midlevel_teamfhArr[$parent['id']] = ['totalcommission'=>$commission,'commission'=>$fenhongcommission,'money'=>$fenhongmoney,'ogids'=>[$og['id']],'module'=>$og['module'] ?? 'shop'];
                                        }
                                        if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                            self::fhrecord($aid,$parent['id'],$fenhongcommission,0,$og['id'],$og['module'] ?? 'shop','level_teamfenhong',t('等级团队分红',$aid));
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if($isyj == 1 && $commissionyj_my > 0){
                    $commissionyj += $commissionyj_my;
                    $og['commission'] = round($commissionyj_my,2);
                    $og['fhname'] = t('等级团队分红',$aid);
                    $newoglist[] = $og;
                }

                self::fafang($aid,$midlevel_teamfhArr,'level_teamfenhong',t('等级团队分红',$aid),$og['mid']);
                //根据分红奖团队收益
                if(getcustom('teamfenhong_shouyi',$aid)){
                    self::teamshouyi($aid,$sysset,$midlevel_teamfhArr,$oglist,$isyj,$yjmid,$commissionpercent,$moneypercent);
                }
                $midlevel_teamfhArr = [];
            }
        }else{
            return ['commissionyj'=>0,'oglist'=>[]];
        }
    }

    public static function gongxian_fenhong($aid,$sysset,$oglist,$starttime=0,$endtime=0,$isyj=0,$yjmid=0)
    {
        $member_gongxian_custom = getcustom('member_gongxian',$aid);
        if($member_gongxian_custom){
            $admin = Db::name('admin')->where('id',$aid)->find();
            if($admin['member_gongxian_status'] != 1)
                return ['commissionyj'=>0,'oglist'=>[]];
            if(empty($sysset['gongxian_percent']) || $sysset['gongxian_percent'] <= 0){
                return ['commissionyj'=>0,'oglist'=>[]];
            }

            //开启的多商户
            $bids = Db::name('business')->where('aid',$aid)->where('fenhong_member_gongxian',1)->column('id');
            $bids = array_merge([0],$bids);
            if(getcustom('maidan_fenhong_new',$aid)){
                $bids_maidan = Db::name('business')->where('maidan_gongxian',1)->column('id');
                $bids_maidan = array_merge([0],$bids_maidan);
            }
            if($endtime == 0) $endtime = time();
            if($isyj == 1 && !$oglist){
                //多商户的商品是否参与分红
                if($sysset['fhjiesuanbusiness'] == 1){
                    $bwhere = [['og.bid','in',$bids]];
                }else{
                    $bwhere = [['og.bid','=','0']];
                }
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                if(getcustom('maidan_fenhong_new',$aid) && $sysset['maidanfenhong']){
                    //买单分红
                    $bwhere_maidan = [['og.bid', 'in', $bids_maidan]];
                    $maidan_orderlist = Db::name('maidan_order')
                        ->alias('og')
                        ->join('member m','m.id=og.mid')
                        ->where('og.aid',$aid)
                        ->where('og.isfenhong',0)
                        ->where('og.status',1)
                        ->where($bwhere_maidan)
                        ->field('og.*,m.nickname,m.headimg')
                        ->order('og.id desc')
                        ->select()
                        ->toArray();
                    if($maidan_orderlist){
                        foreach($maidan_orderlist as $mdk=>$mdv){
                            $mdv['name']             = $mdv['title'];
                            $mdv['real_totalprice']  = $mdv['paymoney'];
                            //买单分红结算方式
                            if($sysset['maidanfenhong_type'] == 1){
                                //按利润结算时直接把销售额改成利润
                                $mdv['real_totalprice'] = $mdv['paymoney'] -  $mdv['cost_price'];
                            }elseif($sysset['maidanfenhong_type'] == 2){
                                //按抽佣计算
                                $mdv['real_totalprice'] = $mdv['order_fee'];
                            }
                            $mdv['cost_price']       = 0;
                            $mdv['num']              = 1;
                            $mdv['module']           = 'maidan';
                            $oglist[] = $mdv;
                        }
                    }
                }
            }
            if(!$oglist) return ['commissionyj'=>0,'oglist'=>[]];
            //参与分红的会员
            $gongxianTotal = Db::name('member')->where('aid',$aid)->where('gongxian','>',0)->sum('gongxian');
            if(!$gongxianTotal) return ['commissionyj'=>0,'oglist'=>[]];
//            $fhlevellist = Db::name('member_level')->where('aid',$aid)->where('fenhong','>','0')->order('sort desc,id desc')->column('*','id');
//            if(!$fhlevellist) return ['commissionyj'=>0,'oglist'=>[]];

            $ogids = [];
            $midfhArr = [];
            $newoglist = [];
            $commissionyj = 0;
            $allfenhongprice = 0;
            foreach($oglist as $og){
                if(getcustom('maidan_fenhong_new',$aid) && $og['module']=='maidan'){
                    if($og['bid'] > 0 && !in_array($og['bid'],$bids_maidan)){
                        continue;
                    }
                }
                if($og['bid'] > 0 && !in_array($og['bid'],$bids) && $og['module']!='maidan'){
                    continue;
                }
                $ogids[] = $og['id'];

                $commissionpercent = 1;
                $moneypercent = 0;
                if($sysset['fhjiesuantype'] == 0){
                    $fenhongprice = $og['real_totalprice'];
                }else{
                    $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                }
                if(getcustom('yx_buyer_subsidy',$aid)){
                    if($sysset['fhjiesuantype'] == 2){
                        //按抽成计算
                        $fenhongprice = $og['order_fee']??0 ;
                    }
                }
                if($fenhongprice <= 0) continue;
                $ogids[] = $og['id'];
                $allfenhongprice = $allfenhongprice + $fenhongprice;

                if($gongxianTotal){
                    $lastmidlist = [];
                    $where = [];
                    $where[] = ['aid', '=', $aid];
                    $where[] = ['gongxian', '>', 0]; //判断贡献值

                    $midlist = Db::name('member')->where($where)->column('id,gongxian,total_fenhong_partner,levelstarttime','id');
                    if(!$midlist) continue;

                    $commission = 0;
                    $totalscore = 0;

                    if(!$midfhArr) $midfhArr = [];
                    $newcommission = 0;
                    foreach($midlist as $item){
                        $mid = $item['id'];
                        if($mid == $og['mid'])
                        {
                            //会员自己的首单不分
                            $orderid_first = Db::name('shop_order')->where('mid',$mid)->whereIn('status',[1,2,3])->order('id','asc')->value('id');
                            if($og['orderid'] == $orderid_first) continue;
                        }
                        //分红规则：（本人的贡献值）÷（平台所有会员的全部贡献值总和(排除首单不分)）x（单个订单可分配的利润）=本人单个订单应分配金额
                        $commission = $item['gongxian'] / $gongxianTotal * $sysset['gongxian_percent'] * $fenhongprice * 0.01;
                        if($commission == 0) continue;
                        if($isyj == 1 && $mid == $yjmid && $commission > 0){
                            $commissionyj += $commission;
                            $og['commission'] = round($commission,2);
                            $og['fhname'] = t('贡献',$aid).'分红';
                            $newoglist[] = $og;
                            break;
                        }
                        if($midfhArr[$mid]){
                            $midfhArr[$mid]['totalcommission'] = $midfhArr[$mid]['totalcommission'] + $commission;
                            $midfhArr[$mid]['commission'] = $midfhArr[$mid]['commission'] + $commission;
                            $midfhArr[$mid]['money'] = 0;
                            $midfhArr[$mid]['ogids'][] = $og['id'];
                            $midfhArr[$mid]['score'] = 0;
                        }else{
                            $midfhArr[$mid] = [
                                'totalcommission'=>$commission,
                                'commission'=>$commission,
                                'money'=>0,
                                'score'=>0,
                                'ogids'=>[$og['id']],
                                'module'=>$og['module'] ?? 'shop',
                            ];
                        }
                        if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                            self::fhrecord($aid,$mid,$commission,0,$og['id'],$og['module'] ?? 'shop','gongxian_fenhong',t('贡献',$aid).'分红');
                        }
                    }
                }

                if($midfhArr){
                    $remark = t('贡献',$aid).'分红';
                    self::fafang($aid,$midfhArr,'gongxian_fenhong',$remark,$og['mid']);
                }
                $midfhArr = [];
            }
        }
    }

    public static function fafang($aid,$fhArr,$type,$remark,$frommid=0,$fhShareArr = [],$send_now=0,$params=['isjicha'=>false]){
        if(!$fhArr && !$fhShareArr) return [];
        $moeny_weishu = 2;
        if(getcustom('fenhong_money_weishu',$aid)){
            $moeny_weishu = Db::name('admin_set')->where('aid',$aid)->value('fenhong_money_weishu');
        }
        $moeny_weishu = $moeny_weishu?$moeny_weishu:2;
        $score_weishu = 0;

        $moeny_weishu2 = 2;
        if(getcustom('member_money_weishu',$aid)){
            $moeny_weishu2 = Db::name('admin_set')->where('aid',$aid)->value('member_money_weishu');
        }
        $moeny_weishu2 = $moeny_weishu2?$moeny_weishu2:2;
        if(getcustom('score_weishu',$aid)){
            $score_weishu = Db::name('admin_set')->where('aid',$aid)->value('score_weishu');
            $score_weishu = $score_weishu?$score_weishu:0;
        }
        $log_ids = [];
        if(getcustom('area_commission',$aid)){
            if($type=='areafenhong' || $type=='areafenhong_commission'){
                //区域分红及区域分红分销要判断是否冻结
                $area_commission_set = Db::name('area_commission_set')->where('aid',$aid)->find();
            }
        }
        foreach($fhArr as $mid=>$midfh){
            $totalcommission = dd_money_format($midfh['totalcommission'],$moeny_weishu);
            $commission = dd_money_format($midfh['commission'],$moeny_weishu);
            $money = dd_money_format($midfh['money'],$moeny_weishu2);
            $score = dd_money_format($midfh['score'],$score_weishu);
            $fuchi = dd_money_format($midfh['fuchi']);
            //  var_dump($midfh);
            //  var_dump($midfh['totalcommission']);
            if($totalcommission > 0 || $score>0) {
                $fhdata = [];
                $fhdata['aid'] = $aid;
                $fhdata['mid'] = $mid;
                $fhdata['frommid'] = $frommid;
                $fhdata['commission'] = $totalcommission;
                if(getcustom('gdfenhong_score',$aid)){
                    $fhdata['score'] = $score;
                }
                if(getcustom('gdfenhong_score',$aid) || getcustom('teamfenhong_score_percent',$aid) || getcustom('fenhong_score_percent',$aid)){
                    $fhdata['commission'] = $totalcommission+$score;
                }
                if(getcustom('fenhong_jiaquan_area',$aid) || getcustom('fenhong_jiaquan_gudong',$aid)){
                    if($midfh['jq_sendtime_yj']) $fhdata['jq_sendtime_yj'] = $midfh['jq_sendtime_yj'];
                    if($midfh['jq_send_info']) $fhdata['jq_send_info'] = $midfh['jq_send_info'];
                }
                if(getcustom('fenhong_area_zhitui_pingji',$aid)){
                    if($midfh['jq_send_info']) $fhdata['jq_send_info'] = $midfh['jq_send_info'];
                }
                $fhdata['remark'] = $midfh['remark'] ? $midfh['remark'] : $remark;
                $fhdata['type'] = $type;
                $fhdata['createtime'] = time();
                if(is_array($midfh['ogids'])){
                    $fhdata['ogids'] = implode(',',$midfh['ogids']);
                }else{
                    $fhdata['ogids'] = $midfh['ogids'];
                }
                $fhdata['module'] = $midfh['module'];
                $fhdata['send_commission'] = $commission;
                $fhdata['send_money'] = $money;
                $fhdata['send_score'] = $score;
                $fhdata['send_fuchi'] = $fuchi;
                $fhdata['status'] = 0;
                $fhdata['levelid'] = $midfh['levelid'];
                if(getcustom('yx_team_yeji_fenhong',$aid)){
                    $fhdata['yeji_total'] = $midfh['yeji_total']??0;
                }
                if(getcustom('teamfenhong_jichamoney',$aid)){
                    $fhdata['isjicha'] = $params['isjicha']??0;
                }
                if(getcustom('area_commission',$aid)){
                    if($type=='areafenhong' || $type=='areafenhong_commission'){
                        //区域分红及区域分红分销要判断是否冻结
                        if($area_commission_set['pro_num']>0 && !empty($area_commission_set['pro_ids'])){
                            $downmids = Db::name('member')->where('aid', $aid)->where('find_in_set('.$mid.',path) or id='.$mid)->column('id');
                            $pronum = Db::name('shop_order_goods')->where('aid',$aid)->where('status','in','1,2,3')
                                ->where('proid','in',$area_commission_set['pro_ids'])
                                ->where('mid','in',$downmids)->sum('num');
                            if($pronum<$area_commission_set['pro_num']){
                                //不满足条件要冻结
                                $fhdata['freeze_status'] = 1;
                            }
                        }
                    }
                }
                $log_id = Db::name('member_fenhonglog')->insertGetId($fhdata);
                $log_ids[] = $log_id;
            }

            if(getcustom('yx_offline_subsidies',$aid)){
                if ($type == 'areafenhong'){
                    \app\common\Member::offlineAreafenhong($aid,$mid,$totalcommission,$log_id);
                }
            }
        }
        if(getcustom('area_commission',$aid)){
            //区域分红分销
            if($type=='areafenhong' && $log_ids){
                self::area_commission($aid,$log_ids);
            }
        }

        if($fhShareArr){
            $fhArr = $fhShareArr;
            foreach($fhArr as $mid=>$midfh){
                $totalcommission = dd_money_format($midfh['totalcommission'],$moeny_weishu);
                $commission = dd_money_format($midfh['commission'],$moeny_weishu);
                $money = dd_money_format($midfh['money'],$moeny_weishu2);
                $score = dd_money_format($midfh['score'],$score_weishu);
                if($totalcommission > 0 || $score>0) {
                    $fhdata = [];
                    $fhdata['aid'] = $aid;
                    $fhdata['mid'] = $mid;
                    $fhdata['commission'] = $totalcommission;
                    if(getcustom('gdfenhong_score',$aid)){
                        $fhdata['score'] = $score;
                    }
                    if(getcustom('gdfenhong_score',$aid) || getcustom('teamfenhong_score_percent',$aid) || getcustom('fenhong_score_percent',$aid)){
                        $fhdata['commission'] = $totalcommission+$score;
                    }
                    if(getcustom('fenhong_jiaquan_area',$aid) || getcustom('fenhong_jiaquan_gudong',$aid)){
                        if($midfh['jq_sendtime_yj']) $fhdata['jq_sendtime_yj'] = $midfh['jq_sendtime_yj'];
                        if($midfh['jq_send_info']) $fhdata['jq_send_info'] = $midfh['jq_send_info'];
                    }
                    if(getcustom('fenhong_area_zhitui_pingji',$aid)){
                        if($midfh['jq_send_info']) $fhdata['jq_send_info'] = $midfh['jq_send_info'];
                    }
                    $fhdata['remark'] = $midfh['remark'] ? $midfh['remark'] : $remark;
                    $fhdata['type'] = $type;
                    $fhdata['createtime'] = time();
                    $fhdata['ogids'] = implode(',',$midfh['ogids']);
                    $fhdata['module'] = $midfh['module'];
                    $fhdata['send_commission'] = $commission??0;
                    $fhdata['send_money'] = $money??0;
                    $fhdata['send_score'] = $score??0;
                    $fhdata['send_fuchi'] = $fuchi??0;
                    $fhdata['status'] = 0;
                    $log_id = Db::name('member_fenhonglog')->insertGetId($fhdata);
                    $log_ids[] = $log_id;
                }
            }
        }
        
    }
    //区域代理分红分销
    public static function area_commission($aid,$log_ids){
        if(getcustom('area_commission',$aid)){
            $logs = Db::name('member_fenhonglog')->where('id','in',$log_ids)->where('type','areafenhong')->select()->toArray();
            $area_commission_set = Db::name('area_commission_set')->where('aid',$aid)->find();
            $commission_type = $area_commission_set['commissionset'];
            $commission_set_arr = json_decode($area_commission_set['commissiondata'],true);

            foreach($logs as $log){
                //先判断商品是否是活动商品
                $og = Db::name('shop_order_goods')->where('id',$log['ogids'])->find();
                $product = Db::name('shop_product')->where('id',$og['proid'])->find();
                if($area_commission_set['fwtype']==2){//指定商品可用
                    $productids = explode(',',$area_commission_set['productids']);
                    if(!in_array($product['id'],$productids)){
                        continue;
                    }
                }

                if($area_commission_set['fwtype']==1){//指定类目可用
                    $categoryids = explode(',',$area_commission_set['categoryids']);
                    $cids = explode(',',$product['cid']);
                    $clist = Db::name('shop_category')->where('pid','in',$categoryids)->select()->toArray();
                    foreach($clist as $vc){
                        $categoryids[] = $vc['id'];
                        $cate2 = Db::name('shop_category')->where('pid',$vc['id'])->find();
                        $categoryids[] = $cate2['id'];
                    }
                    if(!array_intersect($cids,$categoryids)){
                        continue;
                    }
                }
                //发放奖金
                $midareafhArr = [];
                $member = Db::name('member')->where('id',$log['mid'])->find();
                $parent = [];
                $parent2 = [];
                if($member['pid']){
                    $parent = Db::name('member')->where('id',$member['pid'])->find();
                }
                if($parent && $parent['pid']){
                    $parent2 = Db::name('member')->where('id',$parent['pid'])->find();
                }
                $commission_data = $commission_set_arr[$log['levelid']];
                $commission1 = $commission_data['commission1']?:0;//一级分销
                $commission2 = $commission_data['commission2']?:0;//二级分销
                $gettj = $commission_data['gettj'];//可以拿奖的等级
                if($parent && (in_array('-1',$gettj) || in_array($parent['levelid'],$gettj))){
                    if($commission_type==1){
                        $commission1_num = bcmul($log['commission'],$commission1/100,2);
                    }
                    if($commission_type==2){
                        $commission1_num = $commission1;
                    }
                    if($commission1_num>0){
                        if($midareafhArr[$parent['id']]){
                            $midareafhArr[$parent['id']]['totalcommission'] = $midareafhArr[$parent['id']]['totalcommission'] + $commission1_num;
                            $midareafhArr[$parent['id']]['commission'] = $midareafhArr[$parent['id']]['commission'] + $commission1_num;
                            $midareafhArr[$parent['id']]['ogids'][] = $log['ogids'];
                        }else{
                            $midareafhArr[$parent['id']] = [
                                'totalcommission' => $commission1_num,
                                'commission' => $commission1_num,
                                'money' => 0,
                                'score' => 0,
                                'ogids' => [$log['ogids']],
                                'module' => $log['module'] ?? 'shop',
                                'remark'=>'区级代理一级分销',
                            ];
                        }
                    }
                }
                if($parent2 && (in_array('-1',$gettj) || in_array($parent2['levelid'],$gettj))){
                    if($commission_type==1){
                        $commission2_num = bcmul($log['commission'],$commission2/100,2);
                    }
                    if($commission_type==2){
                        $commission2_num = $commission2;
                    }
                    if($commission2_num>0){
                        if($midareafhArr[$parent2['id']]){
                            $midareafhArr[$parent2['id']]['totalcommission'] = $midareafhArr[$parent2['id']]['totalcommission'] + $commission2_num;
                            $midareafhArr[$parent2['id']]['commission'] = $midareafhArr[$parent2['id']]['commission'] + $commission2_num;
                            $midareafhArr[$parent2['id']]['ogids'][] = $log['ogids'];
                        }else{
                            $midareafhArr[$parent2['id']] = [
                                'totalcommission' => $commission2_num,
                                'commission' => $commission2_num,
                                'money' => 0,
                                'score' => 0,
                                'ogids' => [$log['ogids']],
                                'module' => $log['module'] ?? 'shop',
                                'remark'=>'区级代理二级分销',
                            ];
                        }
                    }
                }
                self::fafang($aid,$midareafhArr,'areafenhong_commission',t('区域代理分销',$aid),$log['mid']);
            }
        }
    }
    public static function touzi_fenhong($aid,$sysset,$oglist,$starttime=0,$endtime=0,$isyj=0,$yjmid=0,$bid=0){
        $touzi_fenhong_custom = getcustom('touzi_fenhong',$aid);
        if($touzi_fenhong_custom){
            if(getcustom('maidan_fenhong_new',$aid)){
                $bids_maidan = Db::name('business')->where('maidan_touzi',1)->column('id');
                $bids_maidan = array_merge([0],$bids_maidan);
            }
            $admin = Db::name('admin')->where('id',$aid)->find();
            if($admin['shareholder_status'] != 1)
                return ['commissionyj'=>0,'oglist'=>[]];
            if(empty($sysset['touzi_fh_percent']) || $sysset['touzi_fh_percent'] <= 0){
                return ['commissionyj'=>0,'oglist'=>[]];
            }

            if($endtime == 0) $endtime = time();
            if($isyj == 1 && !$oglist){
                //多商户的商品是否参与分红
                if($sysset['fhjiesuanbusiness'] == 1){
                    $bwhere = '1=1';
                    if($bid >0){
                        $bwhere = [['og.bid','=',$bid]];
                    }
                }else{
                    $bwhere = [['og.bid','=','0']];
                }
              
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                if(getcustom('maidan_fenhong_new',$aid) && $sysset['maidanfenhong']){
                    //买单分红
                    $bwhere_maidan = [['og.bid', 'in', $bids_maidan]];
                    $maidan_orderlist = Db::name('maidan_order')
                        ->alias('og')
                        ->join('member m','m.id=og.mid')
                        ->where('og.aid',$aid)
                        ->where('og.isfenhong',0)
                        ->where('og.status',1)
                        ->where($bwhere_maidan)
                        ->field('og.*,m.nickname,m.headimg')
                        ->order('og.id desc')
                        ->select()
                        ->toArray();
                    if($maidan_orderlist){
                        foreach($maidan_orderlist as $mdk=>$mdv){
                            $mdv['name']             = $mdv['title'];
                            $mdv['real_totalprice']  = $mdv['paymoney'];
                            //买单分红结算方式
                            if($sysset['maidanfenhong_type'] == 1){
                                //按利润结算时直接把销售额改成利润
                                $mdv['real_totalprice'] = $mdv['paymoney'] - $mdv['cost_price'];
                            }elseif($sysset['maidanfenhong_type'] == 2){
                                //按抽佣计算
                                $mdv['real_totalprice'] = $mdv['order_fee'];
                            }
                            $mdv['cost_price']       = 0;
                            $mdv['num']              = 1;
                            $mdv['module']           = 'maidan';
                            $oglist[] = $mdv;
                        }
                    }
                }
            }
            if(!$oglist) return ['commissionyj'=>0,'oglist'=>[]];
            
            $ogids = [];
            $midfhArr = [];
            $newoglist = [];
            $commissionyj = 0;
            $allfenhongprice = 0;
            foreach($oglist as $og){
                $ogids[] = $og['id'];
                if($og['bid'] == 0){
                    //参与分红的股东（平台）
                    $touziTotal = Db::name('shareholder')->where('aid',$aid)->where('bid','=',0)->where('status',1)->where('money','>',0)->sum('money');
                    if(!$touziTotal) continue ;
                    if($sysset['touzi_fh_type'] == 0){
                        $fenhongprice = $og['real_totalprice'];
                    }else{
                        $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                    }
                    if($fenhongprice <= 0) continue;
                    $where = [];
                    $where[] = ['aid', '=', $aid];
                    $where[] = ['money', '>', 0]; //判断投资额
                    $where[] = ['bid', '=', 0];//bid=0是分平台的股东
                    //平台进行分红  和多商户的分红
                    $midlist = Db::name('shareholder')->where($where)->column('id,money,mid','id');
                    if(!$midlist) continue;
                    if(!$midfhArr) $midfhArr = [];
                    foreach($midlist as $item){
                        $mid = $item['mid'];
                        if($mid == $og['mid'])
                        {
                            //会员自己的首单不分
                            $orderCount = Db::name('shop_order')->where('mid',$mid)->whereIn('status',[1,2,3])->count();
                            if($orderCount <= 1) continue;
                        }
                        //分红规则：本人投资额÷所有人的投资额总和x订单销售额（或利润）x系统设置的分配比例
                        $commission = $item['money'] / $touziTotal * $sysset['touzi_fh_percent'] * $fenhongprice * 0.01;
                        if($commission == 0) continue;
                        if($isyj == 1 && $mid == $yjmid && $commission > 0){
                            $commissionyj += $commission;
                            $og['commission'] = round($commission,2);
                            $og['fhname'] = t('投资分红',$aid);
                            $newoglist[] = $og;
                            break;
                        }
                       
                        if($midfhArr[$mid]){
                            $midfhArr[$mid]['totalcommission'] = $midfhArr[$mid]['totalcommission'] + $commission;
                            $midfhArr[$mid]['commission'] = $midfhArr[$mid]['commission'] + $commission;
                            $midfhArr[$mid]['money'] = 0;
                            $midfhArr[$mid]['ogids'][] = $og['id'];
                            $midfhArr[$mid]['score'] = 0;
                        }else{
                            $midfhArr[$mid] = [
                                'totalcommission'=>$commission,
                                'commission'=>$commission,
                                'money'=>0,
                                'score'=>0,
                                'ogids'=>[$og['id']],
                                'module'=>$og['module'] ?? 'shop',
                            ];
                        }
                        if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                            self::fhrecord($aid,$mid,$commission,0,$og['id'],$og['module'] ?? 'shop','touzi_fenhong',t('投资分红',$aid));
                        }
                    }

                    if($midfhArr){
                        $remark = t('投资分红',$aid);
                        self::fafang($aid,$midfhArr,'touzi_fenhong',$remark,$og['mid']);
                    }
                    $midfhArr = [];
                }
                
                if($og['bid'] > 0){
                    if(getcustom('maidan_fenhong_new',$aid) && $og['module']=='maidan'){
                        if($og['bid'] > 0 && !in_array($og['bid'],$bids_maidan)){
                            continue;
                        }
                    }
                    $b_touziTotal = Db::name('shareholder')->where('aid',$aid)->where('bid',$og['bid'])->where('status',1)->where('money','>',0)->sum('money');
                    if(!$b_touziTotal) continue;
                    //查询多商户是否开始 投资分红
                    $business = Db::name('business')->where('id',$og['bid'])->find();
                    //判断商户是否开启了 投资分红 ，开启了投资还进行分红
                    if($business['shareholder_status'] ==1){
                        if($business['touzi_fh_type'] == 0){
                            $fenhongprice = $og['real_totalprice'];
                        }else{
                            $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                        }
                        if($fenhongprice <= 0) continue;
                        $bswhere = [];
                        $bswhere[] = ['aid', '=', $aid];
                        $bswhere[] = ['money', '>', 0]; //判断投资额
                        $bswhere[] = ['bid', '=', $og['bid']]; //查询当前商品的 多商户的股东
                        $bmidlist = Db::name('shareholder')->where($bswhere)->column('id,money,mid','id');
                        if(!$bmidlist) continue;
                       
                        if(!$midfhArr) $midfhArr = [];

                        foreach($bmidlist as $bitem){
                            $bmid = $bitem['mid'];
                            if($bmid == $og['mid'])
                            {
                                //会员自己的首单不分
                                $orderCount = Db::name('shop_order')->where('mid',$mid)->whereIn('status',[1,2,3])->count();
                                if($orderCount <= 1) continue;
                            }
                            //分红规则：本人投资额÷所有人的投资额总和x订单销售额（或利润）x系统设置的分配比例
                            $commission = $bitem['money'] / $b_touziTotal * $business['touzi_fh_percent'] * $fenhongprice * 0.01;
                            if($commission == 0) continue;
                            if($isyj == 1 && $bmid == $yjmid && $commission > 0){
                                $commissionyj += $commission;
                                $og['commission'] = round($commission,2);
                                $og['fhname'] = t('投资分红',$aid);
                                $newoglist[] = $og;
                                break;
                            }
                            if($midfhArr[$bmid]){
                                $midfhArr[$bmid]['totalcommission'] = $midfhArr[$bmid]['totalcommission'] + $commission;
                                $midfhArr[$bmid]['commission'] = $midfhArr[$bmid]['commission'] + $commission;
                                $midfhArr[$bmid]['money'] = 0;
                                $midfhArr[$bmid]['ogids'][] = $og['id'];
                                $midfhArr[$bmid]['score'] = 0;
                            }else{
                                $midfhArr[$bmid] = [
                                    'totalcommission'=>$commission,
                                    'commission'=>$commission,
                                    'money'=>0,
                                    'score'=>0,
                                    'ogids'=>[$og['id']],
                                    'module'=>$og['module'] ?? 'shop',
                                ];
                            }
                            if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                self::fhrecord($aid,$bmid,$commission,0,$og['id'],$og['module'] ?? 'shop','touzi_fenhong',t('投资分红',$aid));
                            }

                        }

                        if($midfhArr){
                            $remark = t('投资分红',$aid);
                            self::fafang($aid,$midfhArr,'touzi_fenhong',$remark,$og['mid']);
                        }
                        $midfhArr = [];
                    }

                }
                $allfenhongprice = $allfenhongprice + $fenhongprice;
            }
        }
    }

    //购车基金
    public static function goucheBonus($aid,$sysset,$midteamfhArr = [],$oglist = [],$isyj=0,$yjmid=0,$commissionpercent=1,$moneypercent=0){
        $gouche_bonus_able = getcustom('teamfenhong_gouche',$aid);
        if(getcustom('teamfenhong_gouche',$aid)){
            writeLog('购车基金进入','gouche_bonus.log');
            //dump($midteamfhArr);
            writeLog('团队分红数据'.json_encode($midteamfhArr),'gouche_bonus.log');
            if(empty($midteamfhArr)){
                return true;
            }
            //购车基金没开启的话直接返回
            if(!$gouche_bonus_able){
                writeLog('未开启购车基金','gouche_bonus.log');
                return true;
            }
            $midgoucheArr = [];//购车基金
            $newoglist = [];
            $oglist = array_column($oglist,null,'id');
            //循环处理拿团队分红奖的会员
            foreach($midteamfhArr as $t_mid=>$team){
                if($team['type']!='团队分红'){
                    continue;
                }
                writeLog('会员'.$t_mid.'购车基金开始','gouche_bonus.log');

                //拿奖人从mid开始顺着推荐网向上找最近符合条件的会员
                $member = Db::name('member')->where('id', $t_mid)->find();
                $pids = $member['path'];
                if($pids){
                    $pids .= ','.$t_mid;
                }else{
                    $pids = (string)$t_mid;
                }
                $parentList = Db::name('member')->where('id','in',$pids)->order(Db::raw('field(id,'.$pids.')'))->select()->toArray();
                $parentList = array_reverse($parentList);
                //判断会员是否满足购车基金条件
                $mid = 0;
                foreach($parentList as $parent){
                    $gouche_able = \app\common\Member::goucheAble($parent['id']);
                    if(!$gouche_able){
                        continue;
                    }else{
                        $mid = $parent['id'];
                        break;
                    }
                }
                if(!$mid){
                    continue;
                }
                $commissionyj = 0;
                //根据分红奖订单查询购车基金
                foreach($team['ogids'] as $ogid){
                    $commissionyj_my = 0;
                    $og = $oglist[$ogid]??'';
                    if(!$og){
                        //未查询到订单，跳过不处理
                        continue;
                    }
                    //购车基金计算
                    if($og['gouchebonusset']==1){
                        $gouchebonusdata = json_decode($og['gouchebonusdata1'],true);
                    }elseif($og['gouchebonusset']==2){
                        $gouchebonusdata = json_decode($og['gouchebonusdata2'],true);
                    }else{
                        $gouchebonusdata = [];
                    }
                    writeLog('会员'.$mid.'购车基金：订单'.$og['id'].'奖金参数'.$og['gouchebonusset'].'=>'.json_encode($gouchebonusdata),'gouche_bonus.log');
                    if(empty($gouchebonusdata)){
                        //未查询到设置的奖金数据，跳过不处理
                        continue;
                    }
                    //判断是使用订单金额还是利润计算奖金
                    if($sysset['fhjiesuantype'] == 0){
                        $fenhongprice = $og['real_totalprice'];
                    }else{
                        $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                    }
                    if(getcustom('yx_buyer_subsidy',$aid)){
                        if($sysset['fhjiesuantype'] == 2){
                            //按抽成计算
                            $fenhongprice = $og['order_fee']??0 ;
                        }
                    }

                    //计算奖金
                    $bonus = $gouchebonusdata[$team['levelid']]['commission']?:0;
                    if($og['gouchebonusset']==1){
                        //按比例计算
                        $gouche_bonus = bcmul($bonus/100,$fenhongprice,2);
                    }elseif($og['gouchebonusset']==2){
                        //按金额计算
                        $gouche_bonus = $bonus;
                    }
                    writeLog('会员'.$mid.'购车基金：订单'.$og['id'].'订单金额'.$fenhongprice.'级别'.$team['levelid'].'奖金=>'.$gouche_bonus,'gouche_bonus.log');
                    if($gouche_bonus<0){
                        continue;
                    }
                    if($isyj == 1 && $yjmid == $mid){
                        $commissionyj_my = $gouche_bonus;
                    }
                    if($commissionpercent != 1){
                        $fenhongcommission = round($gouche_bonus*$commissionpercent,2);
                        $fenhongmoney = round($gouche_bonus*$moneypercent,2);
                    }else{
                        $fenhongcommission = $gouche_bonus;
                        $fenhongmoney = 0;
                    }
                    //购车基金汇总
                    if($midgoucheArr[$mid]){
                        $midgoucheArr[$mid]['totalcommission'] = bcadd($midgoucheArr[$mid]['totalcommission'] , $gouche_bonus);
                        $midgoucheArr[$mid]['commission'] = bcadd($midgoucheArr[$mid]['commission'] , $fenhongcommission);
                        $midgoucheArr[$mid]['money'] = bcadd($midgoucheArr[$mid]['money'] , $fenhongmoney);
                        $midgoucheArr[$mid]['score'] = 0;
                        $midgoucheArr[$mid]['ogids'][] = $og['id'];
                        $midgoucheArr[$mid]['levelid'] = $team['levelid'];
                    }else{
                        $midgoucheArr[$mid] = [
                            'totalcommission' => $gouche_bonus,
                            'commission' => $fenhongcommission,
                            'money' => $fenhongmoney,
                            'score' => 0,
                            'ogids' => [$og['id']],
                            'module' => $og['module'] ?? 'shop',
                            'levelid' => $team['levelid']
                        ];
                    }
                    writeLog('会员'.$mid.'购车基金：订单'.$og['id'].'汇总'.json_encode($midgoucheArr),'gouche_bonus.log');
                    //dump($midgoucheArr);
                    if($isyj == 1 && $commissionyj_my > 0){
                        $commissionyj = bcadd($commissionyj,$commissionyj_my,2);

                        $newoglist[$og['id']] = $commissionyj_my;
                    }

                    self::fafang($aid,$midgoucheArr,'teamfenhong_gouche',t('购车基金',$aid));
                    $midgoucheArr = [];

                }
            }
        }
    }

    //旅游基金
    public static function lvyouBonus($aid,$sysset,$midteamfhArr = [],$oglist = [],$isyj=0,$yjmid=0,$commissionpercent=1,$moneypercent=0){
        $lvyou_bonus_able = getcustom('teamfenhong_lvyou',$aid);
        if(getcustom('teamfenhong_lvyou',$aid)){
            writeLog('旅游基金进入','lvyou_bonus.log');
            writeLog('团队分红数据'.json_encode($midteamfhArr),'lvyou_bonus.log');
            if(empty($midteamfhArr)){
                return true;
            }
            //旅游基金没开启的话直接返回
            if(!$lvyou_bonus_able){
                writeLog('未开启旅游基金','lvyou_bonus.log');
                return true;
            }
            $midlvyouArr = [];//旅游基金
            $newoglist = [];
            $oglist = array_column($oglist,null,'id');

            //循环处理拿团队分红奖的会员
            foreach($midteamfhArr as $t_mid=>$team){
                if($team['type']!='团队分红'){
                    continue;
                }
                writeLog('会员'.$t_mid.'旅游基金开始','lvyou_bonus.log');

                //拿奖人从mid开始顺着推荐网向上找最近符合条件的会员
                $member = Db::name('member')->where('id', $t_mid)->find();
                $pids = $member['path'];
                if($pids){
                    $pids .= ','.$t_mid;
                }else{
                    $pids = (string)$t_mid;
                }
                $parentList = Db::name('member')->where('id','in',$pids)->order(Db::raw('field(id,'.$pids.')'))->select()->toArray();
                $parentList = array_reverse($parentList);
                //判断会员是否满足拿奖条件
                $mid = 0;
                foreach($parentList as $parent){
                    $lvyou_able = \app\common\Member::lvyouAble($parent['id']);
                    if(!$lvyou_able){
                        continue;
                    }else{
                        $mid = $parent['id'];
                        break;
                    }
                }
                if(!$mid){
                    continue;
                }

                $commissionyj = 0;
                //根据分红奖订单查询旅游基金
                foreach($team['ogids'] as $ogid){
                    $commissionyj_my = 0;
                    $og = $oglist[$ogid]??'';
                    if(!$og){
                        //未查询到订单，跳过不处理
                        continue;
                    }
                    //旅游基金计算
                    if($og['lvyoubonusset']==1){
                        $lvyoubonusdata = json_decode($og['lvyoubonusdata1'],true);
                    }elseif($og['lvyoubonusset']==2){
                        $lvyoubonusdata = json_decode($og['lvyoubonusdata2'],true);
                    }else{
                        $lvyoubonusdata = [];
                    }
                    writeLog('会员'.$mid.'旅游基金：订单'.$og['id'].'奖金参数'.$og['lvyoubonusset'].'=>'.json_encode($lvyoubonusdata),'lvyou_bonus.log');
                    if(empty($lvyoubonusdata)){
                        //未查询到设置的奖金数据，跳过不处理
                        continue;
                    }
                    //判断是使用订单金额还是利润计算奖金
                    if($sysset['fhjiesuantype'] == 0){
                        $fenhongprice = $og['real_totalprice'];
                    }else{
                        $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                    }
                    if(getcustom('yx_buyer_subsidy',$aid)){
                        if($sysset['fhjiesuantype'] == 2){
                            //按抽成计算
                            $fenhongprice = $og['order_fee']??0 ;
                        }
                    }
                    //计算奖金
                    $bonus = $lvyoubonusdata[$team['levelid']]['commission']?:0;
                    if($og['lvyoubonusset']==1){
                        //按比例计算
                        $lvyou_bonus = bcmul($bonus/100,$fenhongprice,2);
                    }elseif($og['lvyoubonusset']==2){
                        //按金额计算
                        $lvyou_bonus = $bonus;
                    }
                    writeLog('会员'.$mid.'旅游基金：订单'.$og['id'].'订单金额'.$fenhongprice.'级别'.$team['levelid'].'奖金=>'.$lvyou_bonus,'lvyou_bonus.log');
                    if($isyj == 1 && $yjmid == $mid){
                        $commissionyj_my = $lvyou_bonus;
                    }
                    if($commissionpercent != 1){
                        $fenhongcommission = round($lvyou_bonus*$commissionpercent,2);
                        $fenhongmoney = round($lvyou_bonus*$moneypercent,2);
                    }else{
                        $fenhongcommission = $lvyou_bonus;
                        $fenhongmoney = 0;
                    }
                    //旅游基金汇总
                    if($midlvyouArr[$mid]){
                        $midlvyouArr[$mid]['totalcommission'] = bcadd($midlvyouArr[$mid]['totalcommission'] , $lvyou_bonus,2);
                        $midlvyouArr[$mid]['commission'] = bcadd($midlvyouArr[$mid]['commission'] , $fenhongcommission);
                        $midlvyouArr[$mid]['money'] = bcadd($midlvyouArr[$mid]['money'] , $fenhongmoney);
                        $midlvyouArr[$mid]['score'] = 0;
                        $midlvyouArr[$mid]['ogids'][] = $og['id'];
                        $midlvyouArr[$mid]['levelid'] = $team['levelid'];
                    }else{
                        $midlvyouArr[$mid] = [
                            'totalcommission' => $lvyou_bonus,
                            'commission' => $fenhongcommission,
                            'money' => $fenhongmoney,
                            'score' => 0,
                            'ogids' => [$og['id']],
                            'module' => $og['module'] ?? 'shop',
                            'levelid' => $team['levelid']
                        ];
                    }
                    if($isyj == 1 && $commissionyj_my > 0){
                        $commissionyj = bcadd($commissionyj,$commissionyj_my,2);
                        $newoglist[$og['id']] = $commissionyj_my;
                    }
                    writeLog('会员'.$mid.'旅游基金：订单'.$og['id'].'汇总'.json_encode($midlvyouArr),'lvyou_bonus.log');

                    self::fafang($aid,$midlvyouArr,'teamfenhong_lvyou',t('旅游基金',$aid));
                    $midlvyouArr = [];

                }
            }
        }
    }

    /**
     * 团队收益
     * 拿下级总佣金收益的百分比
     */
    public static function teamshouyi($aid,$sysset,$midteamfhArr = [],$oglist = [],$isyj=0,$yjmid=0,$commissionpercent=1,$moneypercent=0){
        if(getcustom('teamfenhong_shouyi',$aid)){
            writeLog('团队收益进入','teamfenhong_shouyi.log');
            //dump($midteamfhArr);
            writeLog('奖金数据'.json_encode($midteamfhArr),'teamfenhong_shouyi.log');
            if(empty($midteamfhArr)){
                return true;
            }
            $midshouyiArr = [];//团队收益
            $newoglist = [];
            $oglist = array_column($oglist,null,'id');
            //循环处理拿团队分红奖的会员
            foreach($midteamfhArr as $t_mid=>$team){

                writeLog('会员'.$t_mid.'团队收益开始,总收益'.$team['commission'],'teamfenhong_shouyi.log');

                //拿奖人从$t_mid开始顺着推荐网向上找最近符合条件的会员
                $member = Db::name('member')->where('id', $t_mid)->find();
                $pids = $member['path'];
                if(!$pids){
                    continue;
                }

                $parentList = Db::name('member')->where('id','in',$pids)->order(Db::raw('field(id,'.$pids.')'))->select()->toArray();
                $parentList = array_reverse($parentList);
                //循环上级发放团队收益
                $commissionyj = 0;
                $ceng = 1;//拿奖层级
                $commission_total = $team['commission'];
                $down_mid = $t_mid;
                foreach($parentList as $parent){
                    writeLog('会员'.$t_mid.',第'.$ceng.'层会员id'.$parent['id'].'开始','teamfenhong_shouyi.log');
                    $mid = $parent['id'];
                    $commissionyj_my = 0;
                    $level_data = Db::name('member_level')->where('id',$parent['levelid'])->find();
                    //判断会员是否满足购车基金条件
                    $teamshouyi_able = \app\common\Member::teamshouyiAble($parent['id'],$level_data);
                    if(!$teamshouyi_able){
                        continue;
                    }
                    //拿奖层级
                    $bonus_cengji = $level_data['team_shouyi_lv'];
                    //奖金比例
                    $bonus_bili = $level_data['team_shouyi'];
                    writeLog('会员'.$t_mid.',第'.$ceng.'层会员id'.$parent['id'].'拿奖层级'.$bonus_cengji.',拿奖比例'.$bonus_bili,'teamfenhong_shouyi.log');
                    if($bonus_cengji && $bonus_cengji>$ceng){
                        continue;
                    }
                    if($bonus_bili<=0){
                        continue;
                    }
                    $commission_total = bcmul($commission_total,$bonus_bili/100,2);
                    writeLog('会员'.$t_mid.',第'.$ceng.'层会员id'.$parent['id'].'拿奖金额'.$commission_total,'teamfenhong_shouyi.log');
                    $shouyi_min = $level_data['team_shouyi_min'];
                    if($commission_total<$shouyi_min){
                        writeLog('会员'.$t_mid.',第'.$ceng.'层会员id'.$parent['id'].'收益小于'.$shouyi_min,'teamfenhong_shouyi.log');
                        break;
                    }

                    if($isyj == 1 && $yjmid == $mid){
                        $commissionyj_my = $commission_total;
                    }
                    if($commissionpercent != 1){
                        $fenhongcommission = round($commission_total*$commissionpercent,2);
                        $fenhongmoney = round($commission_total*$moneypercent,2);
                    }else{
                        $fenhongcommission = $commission_total;
                        $fenhongmoney = 0;
                    }
                    //团队收益汇总
                    if($midshouyiArr[$mid]){
                        $midshouyiArr[$mid]['totalcommission'] = bcadd($midshouyiArr[$mid]['totalcommission'] , $commission_total);
                        $midshouyiArr[$mid]['commission'] = bcadd($midshouyiArr[$mid]['commission'] , $fenhongcommission);
                        $midshouyiArr[$mid]['money'] = bcadd($midshouyiArr[$mid]['money'] , $fenhongmoney);
                        $midshouyiArr[$mid]['score'] = 0;
                        $midshouyiArr[$mid]['ogids'][] = array_merage($midshouyiArr[$mid]['ogids'],$team['ogids']);
                        $midshouyiArr[$mid]['levelid'] = $parent['levelid'];
                    }else{
                        $midshouyiArr[$mid] = [
                            'totalcommission' => $commission_total,
                            'commission' => $fenhongcommission,
                            'money' => $fenhongmoney,
                            'score' => 0,
                            'ogids' => $team['ogids'],
                            'module' => $team['module'],
                            'levelid' => $parent['levelid']
                        ];
                    }
                    writeLog('会员'.$mid.'团队收益：下级会员'.$t_mid.'汇总'.json_encode($midshouyiArr),'teamfenhong_shouyi.log');
                    //dump($midgoucheArr);
                    if($isyj == 1 && $commissionyj_my > 0){
                        $commissionyj = bcadd($commissionyj,$commissionyj_my,2);

                        $newoglist[$t_mid] = $commissionyj_my;
                    }

                    self::fafang($aid,$midshouyiArr,'teamfenhong_shouyi',t('团队收益',$aid),$down_mid);
                    $midshouyiArr = [];
                    $down_mid = $mid;
                }
            }
        }
    }

    /**
     * 区域合伙人分红
     */
    public static function regionPartnerBonus($aid,$sysset){
        if(getcustom('region_partner',$aid)){
            //查询后台添加的所有区域
            $area_set = Db::name('region_partner_set')->where('aid',$aid)->select()->toArray();
            if(!$area_set){
                return true;
            }
            $midfhArr = [];
            foreach($area_set as $set){
                //查询该区域下参与分红的合伙人
                $map = [];
                $map[] = ['aid','=',$aid];
                $map[] = ['set_id','=',$set['id']];
//                $map[] = ['province','=',$set['province']];
//                $map[] = ['city','=',$set['city']];
//                $map[] = ['district','=',$set['district']];
                $map[] = ['status','=',1];
                $map[] = ['bonus_status','=',0];
                $lists = Db::name('region_partner_order')->where($map)->limit($set['fh_num'])->select()->toArray();
                if(!$lists){
                    continue;
                }
                //计算会员分红
                $count = count($lists);

                $bonus = bcdiv($set['day_fh'],$count,2);
                //dump($set['day_fh'].'=>'.$count.'=>'.$bonus);
                //分红汇总
                foreach($lists as $partner){
                    //判断分红金额
                    if($bonus>bcsub($partner['apply_money'],$partner['bonus'],2)){
                        $bonus = $partner['remain'];
                    }
                    $mid = $partner['mid'];
                    if($midfhArr[$mid]){
                        $midfhArr[$mid]['totalcommission'] = bcadd($midfhArr[$mid]['totalcommission'] , $bonus,2);
                        $midfhArr[$mid]['commission'] = bcadd($midfhArr[$mid]['commission'] , $bonus);
                        $midfhArr[$mid]['money'] = 0;
                        $midfhArr[$mid]['score'] = 0;
                        $midfhArr[$mid]['ogids'][] = $partner['id'];
                    }else{
                        $midfhArr[$mid] = [
                            'totalcommission' => $bonus,
                            'commission' => $bonus,
                            'money' => 0,
                            'score' => 0,
                            'ogids' => [$partner['id']],
                            'module' => 'region_partner',
                        ];
                    }
                    //if($sysset['fhjiesuanhb'] == 0) {
                        self::fafang($aid,$midfhArr,'region_partner',t('区域合伙人分红',$aid));
                        $midfhArr = [];
                    //}
                    //更新合伙人分红信息
                    $bonus_total = bcadd($partner['bonus'],$bonus);
                    $bonus_remain = bcsub($partner['apply_money'],$bonus_total,2);
                    $data_u = [];
                    $data_u['bonus'] = $bonus_total;
                    $data_u['remain'] = $bonus_remain;
                    if($bonus_total>=$partner['apply_money']){
                        $data_u['bonus_status'] = 1;
                    }
                    Db::name('region_partner_order')->where('id',$partner['id'])->update($data_u);
                }
            }
//            if($sysset['fhjiesuanhb'] == 1) {
//                self::fafang($aid,$midfhArr,'region_partner',t('区域合伙人分红',$aid));
//            }
        }
    }

    public static function jiesuan_gdfenhong_huiben($aid,$starttime=0,$endtime=0){
        if(getcustom('fenhong_gudong_huiben',$aid)){
            if($endtime == 0) $endtime = time();
            $sysset = Db::name('admin_set')->where('aid',$aid)->find();
            $where = [];
            $where[] = ['og.aid','=',$aid];
            $where[] = ['og.isfenhong_huiben','=',0];
            $fail_where = [
                ["og.aid", "=", $aid],
                ["og.isfenhong_huiben", "=", 0],
                ["og.isfenhong", "<>", 2],
                ["og.iszj", "=", 2],
                ['og.isjiqiren', '=', 0]
            ];
            if($sysset['fhjiesuanbusiness'] == 1){ //多商户的商品是否参与分红

            }else{
                $where[] = ['og.bid','=','0'];
                $fail_where[] = ['og.bid', '=', 0];
            }
            if($sysset['fhjiesuantime_type_huiben'] == 1) { //分红结算时间类型 0收货后，1付款后
                $where[] = ['og.status','in',[1,2,3]];
                $where[] = ['og.createtime','>=',$starttime];
                $where[] = ['og.createtime','<',$endtime];
                $fail_where[] = ['og.status', '=', 4];
                $fail_where[] = ['og.createtime','>=',$starttime];
                $fail_where[] = ['og.createtime','<',$endtime];
                $where2 = $where;
            }else{
                $where[] = ['og.status','=','3'];
                $where2 = $where;
                $where[] = ['og.endtime','>=',$starttime];
                $where[] = ['og.endtime','<',$endtime];
                $where2[] = ['og.collect_time','>=',$starttime];
                $where2[] = ['og.collect_time','<',$endtime];
                $fail_where[] = ['og.status', '=', 4];
            }
            //排除退款订单
            $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('shop_order o','o.id=og.orderid')
                ->where($where)->where('refund_num',0)->select()->toArray();
            if($oglist){
                $update = ['og.isfenhong_huiben'=>1];
                $ids = array_column($oglist,'id');
                Db::name('shop_order_goods')->alias('og')->where('id','in',$ids)->update($update);
            }
            if(getcustom('yuyue_fenhong',$aid)){
                $yyorderlist = Db::name('yuyue_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m','m.id=og.mid')
                    ->where($where2)->select()->toArray();
                foreach($yyorderlist as $k=>$v){
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = $v['cost_price'] ?? 0;
                    $v['module'] = 'yuyue';
                    $oglist[] = $v;
                }
                if($yyorderlist){
                    Db::name('yuyue_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m','m.id=og.mid')->where($where2)->update(['og.isfenhong_huiben'=>1]);
                }
            }
            if(getcustom('scoreshop_fenhong',$aid)){
                $scoreshopoglist = Db::name('scoreshop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')
                    ->join('scoreshop_order o','o.id=og.orderid')->where($where)->select()->toArray();
                foreach($scoreshopoglist as $v){
                    $v['real_totalprice'] = $v['totalmoney'];
                    $v['module'] = 'scoreshop';
                    $oglist[] = $v;
                }

                if($scoreshopoglist){
                    Db::name('scoreshop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('scoreshop_order o','o.id=og.orderid')->where($where)->update(['og.isfenhong_huiben'=>1]);
                }
            }
            if(getcustom('luckycollage_fenhong',$aid)){
                $lcorderlist = Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')
                    ->join('member m','m.id=og.mid')->where($where2)->whereRaw('og.buytype=1 or og.iszj=1')->where('og.isjiqiren',0)->select()->toArray();
                foreach($lcorderlist as $k=>$v){
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'lucky_collage';
                    $oglist[] = $v;
                }

                if($lcorderlist){
                    Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m','m.id=og.mid')->where($where2)->whereRaw('og.buytype=1 or og.iszj=1')->where('og.isjiqiren',0)->update(['og.isfenhong_huiben'=>1]);
                }
            }

            if(getcustom('maidan_fenhong',$aid) && !getcustom('maidan_fenhong_new',$aid)){
                //买单
                $maidan_orderlist = Db::name('maidan_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where('og.aid',$aid)
                    ->where('og.isfenhong_huiben',0)
                    ->where('og.status',1)
                    ->where('og.paytime','>=',$starttime)
                    ->where('og.paytime','<',$endtime);
                if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                    $maidan_orderlist = $maidan_orderlist
                        ->where('og.bid',0);
                }
                $maidan_orderlist = $maidan_orderlist
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                if($maidan_orderlist){
                    foreach($maidan_orderlist as $mdk=>$mdv){
                        $mdv['proid']            = 0;
                        $mdv['name']            = $mdv['title'];
                        $mdv['real_totalprice'] = $mdv['paymoney'];
                        $mdv['cost_price']      = 0;
                        $mdv['num']             = 1;
                        $mdv['module']          = 'maidan';
                        $oglist[]               = $mdv;
                        Db::name('maidan_order')->where('id',$mdv['id'])->update(['isfenhong_huiben'=>1]);
                    }
                    unset($mdv);
                }
            }
            if(getcustom('maidan_fenhong_new',$aid) && $sysset['maidanfenhong'] && !getcustom('maidan_area_fenhong',$aid)){
                //买单
                $maidan_orderlist = Db::name('maidan_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where('og.aid',$aid)
                    ->where('og.isfenhong_huiben',0)
                    ->where('og.status',1)
                    ->where('og.paytime','>=',$starttime)
                    ->where('og.paytime','<',$endtime);
                if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                    $maidan_orderlist = $maidan_orderlist
                        ->where('og.bid',0);
                }
                $maidan_orderlist = $maidan_orderlist
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                if($maidan_orderlist){
                    foreach($maidan_orderlist as $mdk=>$mdv){
                        $mdv['proid']            = 0;
                        $mdv['name']            = $mdv['title'];
                        $mdv['real_totalprice'] = $mdv['paymoney'];
                        //买单分红结算方式
                        if($sysset['maidanfenhong_type'] == 1){
                            //按利润结算时直接把销售额改成利润
                            $mdv['real_totalprice'] = $mdv['paymoney'] - $mdv['cost_price'];
                        }elseif($sysset['maidanfenhong_type'] == 2){
                            //按抽佣计算
                            $mdv['real_totalprice'] = $mdv['order_fee'];
                        }
                        $mdv['cost_price']      = 0;
                        $mdv['num']             = 1;
                        $mdv['module']          = 'maidan';
                        $oglist[]               = $mdv;
                        Db::name('maidan_order')->where('id',$mdv['id'])->update(['isfenhong_huiben'=>1]);
                    }
                    unset($mdv);
                }
            }
            if(getcustom('restaurant_fenhong',$aid) && $sysset['restaurant_fenhong_status']){
                //点餐
                $diancan_oglist = Db::name('restaurant_shop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('restaurant_shop_order o','o.id=og.orderid')->where($where)->select()->toArray();
                if($diancan_oglist){
                    foreach($diancan_oglist as $dck=>$dcv){
                        $dcv['module'] = 'restaurant_shop';
                        $oglist[]      = $dcv;
                    }
                    Db::name('restaurant_shop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('restaurant_shop_order o','o.id=og.orderid')->where($where)->update(['og.isfenhong_huiben'=>1]);
                }
                //外卖
                $takeaway_oglist = Db::name('restaurant_takeaway_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('restaurant_takeaway_order o','o.id=og.orderid')->where($where)->select()->toArray();
                if($takeaway_oglist){
                    foreach($takeaway_oglist as $twk=>$twv){
                        $twv['module'] = 'restaurant_takeaway';
                        $oglist[]      = $twv;
                    }
                    Db::name('restaurant_takeaway_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('restaurant_takeaway_order o','o.id=og.orderid')->where($where)->update(['og.isfenhong_huiben'=>1]);
                }
            }
            if(getcustom('fenhong_times_coupon',$aid)){
                $cwhere[] =['og.isfenhong_huiben','=',0];
                $cwhere[] =['og.status','=',1];
                $cwhere[] =['og.paytime','>=',$starttime];
                $cwhere[] =['og.paytime','<',$endtime];
                if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                    $cwhere[] =['og.bid','=',0];
                }

                $couponorderlist = Db::name('coupon_order')->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where($cwhere)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();

                foreach($couponorderlist as $k=>$v){
                    $v['name'] = $v['title'];
                    $v['real_totalprice'] = $v['price'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'coupon';
                    $oglist[] = $v;
                }
                if($couponorderlist){
                    Db::name('coupon_order')->alias('og')->field('og.*,m.nickname,m.headimg')->join('member m','m.id=og.mid')->where($cwhere)->update(['og.isfenhong_huiben'=>1]);
                }
            }
            if(getcustom('fenhong_kecheng',$aid)){
                //课程直接支付，无区域分红
                $kwhere = [];
                $kwhere[] = ['og.aid','=',$aid];
                $kwhere[] = ['og.isfenhong_huiben','=',0];
                $kwhere[] = ['og.status','=',1];
                $kwhere[] = ['og.paytime','>=',$starttime];
                $kwhere[] = ['og.paytime','<',$endtime];
                if($sysset['fhjiesuanbusiness'] != 1){
                    $kwhere[] = ['og.bid','=','0'];
                }
                $kechenglist = Db::name('kecheng_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where($kwhere)
                    ->field('og.*," " as area2,m.nickname,m.headimg')
                    ->select()
                    ->toArray();
                if($kechenglist){
                    foreach($kechenglist as $v){
                        $v['name']            = $v['title'];
                        $v['real_totalprice'] = $v['totalprice'];
                        $v['cost_price']      = 0;
                        $v['module']          = 'kecheng';
                        $v['num']             = 1;
                        $oglist[]             = $v;
                        Db::name('kecheng_order')->where('id',$v['id'])->update(['isfenhong_huiben'=>1]);
                    }
                }
            }
            
            self::gdfenhong_huiben($aid,$sysset,$oglist,$starttime,$endtime);
        }
    }
    //回本股东分红
    public static function gdfenhong_huiben($aid,$sysset,$oglist,$starttime=0,$endtime=0,$isyj=0,$yjmid=0){
        $fenhong_gudong_huiben = getcustom('fenhong_gudong_huiben',$aid);
        if(getcustom('maidan_fenhong_new',$aid)){
            $bids_maidan = Db::name('business')->where('maidan_fenhong_huiben','>=',1)->column('id');
            $bids_maidan = array_merge([0],$bids_maidan);
        }
        if($fenhong_gudong_huiben){
            if($endtime == 0) $endtime = time();
            if($isyj==1 && !$oglist){
                //多商户的商品是否参与分红
                if($sysset['fhjiesuanbusiness'] == 1){
                    $bwhere = '1=1';
                }else{
                    $bwhere = [['og.bid','=','0']];
                }
                $where = [];
                $where[] = ['og.aid','=',$aid];
                $where[] = ['og.isfenhong_huiben','=',0];
                $where[] = ['og.isfenhong','<>',2];
                $where[] = ['og.status','in',[1,2,3]];
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where($where)->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();

                if(getcustom('yuyue_fenhong',$aid)){
                    $yyorderlist = Db::name('yuyue_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where($where)->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                    foreach($yyorderlist as $k=>$v){
                        $v['name'] = $v['proname'];
                        $v['real_totalprice'] = $v['totalprice'];
                        $v['cost_price'] = $v['cost_price'] ?? 0;
                        $v['module'] = 'yuyue';
                        $oglist[] = $v;
                    }
                }
                if(getcustom('scoreshop_fenhong',$aid)){
                    $scoreshopoglist = Db::name('scoreshop_order_goods')->alias('og')->field('og.*,m.nickname,m.headimg')->where($where)->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                    foreach($scoreshopoglist as $v){
                        $v['real_totalprice'] = $v['totalmoney'];
                        $v['module'] = 'scoreshop';
                        $oglist[] = $v;
                    }
                }
                if(getcustom('luckycollage_fenhong',$aid)){
                    $lcorderlist = Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where($where)->join('member m','m.id=og.mid')->where($bwhere)->whereRaw('og.buytype=1 or og.iszj=1')->where('og.isjiqiren',0)->order('og.id desc')->select()->toArray();
                    foreach($lcorderlist as $k=>$v){
                        $v['name'] = $v['proname'];
                        $v['real_totalprice'] = $v['totalprice'];
                        $v['cost_price'] = 0;
                        $v['module'] = 'lucky_collage';
                        $oglist[] = $v;
                    }
                }
                if(getcustom('maidan_fenhong',$aid)){
                    //买单分红
                    $maidan_orderlist = Db::name('maidan_order')
                        ->alias('og')
                        ->join('member m','m.id=og.mid')
                        ->where($where)
                        ->where($bwhere)
                        ->field('og.*,m.nickname,m.headimg')
                        ->order('og.id desc')
                        ->select()
                        ->toArray();
                    if($maidan_orderlist){
                        foreach($maidan_orderlist as $mdk=>$mdv){
                            $mdv['proid']            = 0;
                            $mdv['name']             = $mdv['title'];
                            $mdv['real_totalprice']  = $mdv['paymoney'];
                            $mdv['cost_price']       = 0;
                            $mdv['num']              = 1;
                            $mdv['module']           = 'maidan';
                            $oglist[] = $mdv;
                        }
                        unset($mdv);
                    }
                }
                if(getcustom('restaurant_fenhong',$aid)){
                    //点餐
                    $diancan_oglist = Db::name('restaurant_shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong_huiben',0)->where('og.status','in',[1,2,3])->join('restaurant_shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                    if($diancan_oglist){
                        foreach($diancan_oglist as $dck=>$dcv){
                            $dcv['module'] = 'restaurant_shop';
                            $oglist[]      = $dcv;
                        }
                        unset($dcv);
                    }
                    //外卖
                    $takeaway_oglist = Db::name('restaurant_takeaway_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong_huiben',0)->where('og.status','in',[1,2,3])->join('restaurant_takeaway_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                    if($takeaway_oglist){
                        foreach($takeaway_oglist as $twk=>$twv){
                            $twv['module'] = 'restaurant_takeaway';
                            $oglist[]      = $twv;
                        }
                        unset($twv);
                    }
                }
                if(getcustom('fenhong_times_coupon',$aid)){
                    $cwhere[] =['og.isfenhong_huiben','=',0];
                    $cwhere[] =['og.status','=',1];
                    $cwhere[] =['og.paytime','>=',$starttime];
                    $cwhere[] =['og.paytime','<',$endtime];
                    if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                        $cwhere[] =['og.bid','=',0];
                    }
                    $couponorderlist = Db::name('coupon_order')->alias('og')
                        ->join('member m','m.id=og.mid')
                        ->where($cwhere)
                        ->field('og.*,m.nickname,m.headimg')
                        ->order('og.id desc')
                        ->select()
                        ->toArray();
                    foreach($couponorderlist as $k=>$v){
                        $v['name'] = $v['title'];
                        $v['real_totalprice'] = $v['price'];
                        $v['cost_price'] = 0;
                        $v['module'] = 'coupon';
                        $v['num'] = 1;
                        $oglist[] = $v;
                    }
                }
                if(getcustom('fenhong_kecheng',$aid)){
                    $kwhere = [];
                    $kwhere[] = ['og.aid','=',$aid];
                    $kwhere[] = ['og.isfenhong_huiben','=',0];
                    $kwhere[] = ['og.status','=',1];
                    $kwhere[] = ['og.paytime','>=',$starttime];
                    $kwhere[] = ['og.paytime','<',$endtime];
                    if($sysset['fhjiesuanbusiness'] != 1){
                        $kwhere[] = ['og.bid','=','0'];
                    }
                    $kechenglist = Db::name('kecheng_order')
                        ->alias('og')
                        ->join('member m','m.id=og.mid')
                        ->where($kwhere)
                        ->field('og.*," " as area2,m.nickname,m.headimg')
                        ->select()
                        ->toArray();
                    if($kechenglist){
                        foreach($kechenglist as $v){
                            $v['name']            = $v['title'];
                            $v['real_totalprice'] = $v['totalprice'];
                            $v['cost_price']      = 0;
                            $v['module']          = 'kecheng';
                            $v['num']             = 1;
                            $oglist[]             = $v;
                        }
                    }
                }
            }
            if(!$oglist) return ['commissionyj'=>0,'oglist'=>[]];
            //参与股东分红的等级
            $fhlevellist = Db::name('member_level')->where('aid',$aid)->where('fenhong_huiben','>','0')->order('sort asc,id asc')->column('*','id');
            if(!$fhlevellist) return ['commissionyj'=>0,'oglist'=>[]];

            $defaultCid = Db::name('member_level_category')->where('aid',$aid)->where('isdefault', 1)->value('id');
            if($defaultCid) {
                $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->where('cid', $defaultCid)->column('id');
            } else {
                $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->column('id');
            }

            $ogids = [];
            $midfhArr = [];
            $newoglist = [];
            $commissionyj = 0;
            $allfenhongprice = 0;
            foreach($oglist as $og){
                if(getcustom('maidan_fenhong_new',$aid) && $og['module']=='maidan'){
                    if($og['bid'] > 0 && !in_array($og['bid'],$bids_maidan)){
                        continue;
                    }
                }
                $levelid_only = 0;
                if(getcustom('commission2moneypercent',$aid) && $sysset['commission2moneypercent1'] > 0){
                    //是否是首单
                    $beforeorder = Db::name('shop_order')->where('aid',$aid)->where('mid',$og['mid'])->where('status','in','1,2,3')->where('paytime','<',$og['paytime'])->find();
                    if(!$beforeorder){
                        $commissionpercent = 1 - $sysset['commission2moneypercent1'] * 0.01;
                        $moneypercent = $sysset['commission2moneypercent1'] * 0.01;
                    }else{
                        $commissionpercent = 1 - $sysset['commission2moneypercent2'] * 0.01;
                        $moneypercent = $sysset['commission2moneypercent2'] * 0.01;
                    }
                }else{
                    $commissionpercent = 1;
                    $moneypercent = 0;
                }
                if($sysset['fhjiesuantype'] == 0){
                    $fenhongprice = $og['real_totalprice'];
                }else{
                    $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                }
                if(getcustom('yx_buyer_subsidy',$aid)){
                    if($sysset['fhjiesuantype'] == 2){
                        //按抽成计算
                        $fenhongprice = $og['order_fee']??0 ;
                    }
                }
                if(getcustom('baikangxie',$aid)){
                    $fenhongprice = $og['cost_price'] * $og['num'];
                }
                //if($fenhongprice <= 0) continue;
                $ogids[] = $og['id'];

                $ogids[] = $og['id'];
                $allfenhongprice = $allfenhongprice + $fenhongprice;
                if($fhlevellist){
                    $lastmidlist = [];
                    foreach($fhlevellist as $fhlevel){
                        //加权级别
                        $fenhong_levelids = Db::name('member_level')
                            ->where('aid',$aid)
                            ->where('find_in_set("'.$fhlevel['id'].'",fenhong_huiben_jiaquan_ids)')
                            ->column('id');
                        if($fenhong_levelids){
                            $fenhong_levelids[] = $fhlevel['id'];
                        }else{
                            $fenhong_levelids = [$fhlevel['id']];
                        }
                        $where = [];
                        $where[] = ['aid', '=', $aid];
                        $where[] = ['levelid', 'in', $fenhong_levelids];
                        $where[] = ['levelstarttime', '<', $og['createtime']]; //判断升级时间
                        if($fhlevel['fenhong_max_money_huiben'] > 0 && (empty($sysset['fenhong_huiben_max_status']) || $fhlevel['fenhong_huiben_max_bili']<=0)) {
                            $where[] = ['total_fenhong_huiben', '<', $fhlevel['fenhong_max_money_huiben']];
                        }
                        $where_str = '1=1';
                        if($sysset['fenhong_huiben_max_status']==1 && $fhlevel['fenhong_huiben_max_bili']>0){
                            //$where_str = '`huiben_maximum`>`total_fenhong_huiben2`';
                        }
                        $field = 'id,total_fenhong_huiben,levelstarttime,levelid,huiben_maximum,total_fenhong_huiben2';
                        $midlist = Db::name('member')->where($where)->whereRaw($where_str)->column($field,'id');
                        if($midlist){
                            foreach ($midlist as $mk => $memberarr){
                                //购买前最后一条升级记录，如果下单前等级不等于当前等级 则排除（当前等级不断变化，不是完全准确，所以需要对照升级记录表）
                                $levelup_last_log = Db::name('member_levelup_order')->where('aid',$aid)->where('status', 2)
                                    ->where('levelup_time', '<', $og['createtime'])->where('mid',$memberarr['id'])->order('levelup_time', 'desc')->find();
                                if($levelup_last_log && $levelup_last_log['levelid'] != $memberarr['levelid'] && $levelup_last_log['sort']<$fhlevel['sort']){
                                    //后台操作把高级别降到了低级别的情况要保留
                                    $last_level = $fhlevellist[$levelup_last_log['levelid']];
                                    if($last_level['sort']<$fhlevel['sort']){
                                        unset($midlist[$mk]);
                                    }
                                }
                            }
                        }
                        $levelup_order_mids = Db::name('member_levelup_order')->where('aid',$aid)->where('levelid', 'in',$fenhong_levelids)->where('status', 2)
                            ->where('levelup_time', '<', $og['createtime'])->group('mid')->order('levelup_time', 'desc')->column('mid');
                        if($levelup_order_mids) {
                            $levelup_order_list = [];
                            foreach($levelup_order_mids as $lk => $item_lomid){
                                //最后一条记录等于当前等级才有价值
                                $lastlog = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $item_lomid)->where('status', 2)
                                    ->where('levelup_time', '<', $og['createtime'])
                                    ->order('levelup_time', 'desc')->find();
                                $levelup_order_list[$item_lomid] = $lastlog['levelid'];
                                if(!in_array($lastlog['levelid'],$fenhong_levelids)){
                                    unset($levelup_order_mids[$lk]);
                                }
                            }
                            $field = 'id,total_fenhong_huiben,levelstarttime,levelid,huiben_maximum,total_fenhong_huiben2';
                            if($levelup_order_mids){
                                $where_str = '1=1';
                                if($sysset['fenhong_huiben_max_status']==1 && $fhlevel['fenhong_huiben_max_bili']>0){
                                    //$where_str = '`huiben_maximum`>`total_fenhong_huiben2`';
                                }
                                $levelup_order_member = Db::name('member')->whereIn('id',$levelup_order_mids)->whereRaw($where_str)->column($field,'id');
                                $midlist = array_merge((array)$midlist, (array) $levelup_order_member );
                                $midlist = array_unique_map($midlist);
                            }
                        }
                        if(!$midlist) continue;

                        //剔除累计分红已超出分红额度的会员
                        foreach($midlist as $mid_k=>$mid_item){
                            $fenhong_max_money_huiben = $fhlevellist[$mid_item['levelid']]['fenhong_max_money_huiben']??0;//加权分红时会员级别有可能不是当前级别
                            $fenhong_max_money = bcadd($fenhong_max_money_huiben, $mid_item['huiben_maximum'],2);
                            $midlist[$mid_k]['fenhong_max_money'] = $fenhong_max_money;
                            if($sysset['fenhong_huiben_max_status']==1 && $fhlevel['fenhong_huiben_max_bili']>0){
                                if($fenhong_max_money<=$mid_item['total_fenhong_huiben2']){
                                    unset($midlist[$mid_k]);
                                }
                            }
                        }


                        $pergxcommon = 0;

                        $totalcommission = 0;
                        $totalscore = 0;
                        if($og['module'] == 'yuyue'){
                            $product = Db::name('yuyue_product')->where('id',$og['proid'])->find();
                        }elseif($og['module'] == 'luckycollage' || $og['module'] == 'lucky_collage'){
                            $product = Db::name('lucky_collage_product')->where('id',$og['proid'])->find();
                        }elseif($og['module'] == 'coupon'){
                            $product = Db::name('coupon')->where('id',$og['cpid'])->find();
                        }elseif($og['module'] == 'scoreshop'){
                            $product = Db::name('scoreshop_product')->where('id',$og['proid'])->find();
                        }elseif($og['module'] == 'kecheng'){
                            $product = Db::name('kecheng_list')->where('id',$og['kcid'])->find();
                        }else{
                            $product = Db::name('shop_product')->where('id',$og['proid'])->find();
                        }
                        if(getcustom('maidan_fenhong',$aid) || getcustom('maidan_fenhong_new',$aid)){
                            if($og['module'] == 'maidan'){
                                $product = [];
                                $product['gdfenhongset'] = 0;
                            }
                        }
                        if(getcustom('restaurant_fenhong',$aid)){
                            if($og['module'] == 'restaurant_shop' || $og['module'] == 'restaurant_takeaway'){
                                $product = [];
                                $product['gdfenhongset'] = 0;
                            }
                        }
                        if($product['gdfenhongset_huiben']==1){//按比例
                            $fenhongdata = json_decode($product['gdfenhongdata1_huiben'],true);
                            if($fenhongdata){
                                $totalcommission = $fenhongdata[$fhlevel['id']]['commission'] * $fenhongprice * 0.01;
                            }
                        }elseif($product['gdfenhongset_huiben']==2){//按固定金额
                            $fenhongdata = json_decode($product['gdfenhongdata2_huiben'],true);
                            if($fenhongdata){
                                $totalcommission = $fenhongdata[$fhlevel['id']]['commission'] * $og['num'];
                            }
                        }elseif($product['gdfenhongset_huiben'] == 0){
                            $totalcommission = $fhlevel['fenhong_huiben'] * $fenhongprice * 0.01;
                        }
                        if(getcustom('fenhong_removefenxiao',$aid) && $fhlevel['gdfenhong_removefenxiao'] == 1){
                            if($og['parent1'] && $og['parent1commission']){
                                $totalcommission = $totalcommission - $og['parent1commission'];
                            }
                            if($og['parent2'] && $og['parent2commission']){
                                $totalcommission = $totalcommission - $og['parent2commission'];
                            }
                            if($og['parent3'] && $og['parent3commission']){
                                $totalcommission = $totalcommission - $og['parent3commission'];
                            }
                            if($totalcommission <= 0) continue;
                        }

                        if($totalcommission == 0 && $totalscore==0) continue;

                        $commission = $totalcommission / count($midlist);
                        $score = floor($totalscore / count($midlist));

                        if(!$midfhArr['level_'.$fhlevel['id']]) $midfhArr['level_'.$fhlevel['id']] = [];
                        $newcommission = 0;

                        foreach($midlist as $k=>$item){
                            $member_level = $fhlevellist[$item['levelid']];
                            $member_total = $item['total_fenhong_huiben2'];//累计已获得股东分红
                            //$fenhong_max_money = $fhlevel['fenhong_max_money_huiben'];
                            $fenhong_max_money = $item['fenhong_max_money'];
                            $mid = $item['id'];
                            //查询上一级别的封顶值
                            $last_fenhong_max_money = Db::name('fenhong_huiben')->where('mid',$mid)->where('level_sort','<',$fhlevel['sort'])->value('max');
                            if($last_fenhong_max_money>0){
                                $fenhong_max_money = bcsub($fenhong_max_money,$last_fenhong_max_money,2);
                            }
                            //已获得分红
                            $total_fenhong_huiben =  Db::name('fenhong_huiben')->where('mid',$mid)->where('levelid',$item['levelid'])->value('fenhong');
                            //累计获得分红
                            $member_total =  Db::name('fenhong_huiben')->where('mid',$mid)->value('fenhong');
                            $item['total_fenhong_huiben'] = $total_fenhong_huiben;
                            if($isyj == 1 && $mid == $yjmid && $commission > 0){
                                $commissionyj += $commission;
                                $og['commission'] = round($commission,2);
                                $og['fhname'] = t('回本股东分红',$aid);
                                $newoglist[] = $og;
                                break;
                            }
                            $fenhong_max_money2 = $item['huiben_maximum'];

                            $gxcommon = 0;
                            $newcommission = $commission + $gxcommon;
                            if($midfhArr['level_'.$fhlevel['id']][$mid]){

                                if($fenhong_max_money > 0) {
                                    if($midfhArr['level_'.$fhlevel['id']][$mid]['totalcommission'] + $newcommission + $item['total_fenhong_huiben'] >$fenhong_max_money) {
                                        //Log::write('大于最大分红金额'.$commission);
                                        $newcommission = $fenhong_max_money - $midfhArr['level_'.$fhlevel['id']][$mid]['totalcommission'] - $item['total_fenhong_huiben'];
                                    }
                                }
                                if($commissionpercent != 1){
                                    $fenhongcommission = round($newcommission*$commissionpercent,2);
                                    $fenhongmoney = round($newcommission*$moneypercent,2);
                                }else{
                                    $fenhongcommission = $newcommission;
                                    $fenhongmoney = 0;
                                }
                                $midfhArr['level_'.$fhlevel['id']][$mid]['totalcommission'] = $midfhArr['level_'.$fhlevel['id']][$mid]['totalcommission'] + $newcommission;
                                $midfhArr['level_'.$fhlevel['id']][$mid]['commission'] = $midfhArr['level_'.$fhlevel['id']][$mid]['commission'] + $fenhongcommission;
                                $midfhArr['level_'.$fhlevel['id']][$mid]['money'] = $midfhArr['level_'.$fhlevel['id']][$mid]['money'] + $fenhongmoney;
                                $midfhArr['level_'.$fhlevel['id']][$mid]['score'] = $score;
                                $midfhArr['level_'.$fhlevel['id']][$mid]['ogids'][] = $og['id'];
                            }else{
                                if($fenhong_max_money > 0) {
                                    if($newcommission + $item['total_fenhong_huiben'] > $fenhong_max_money) {
                                        $newcommission = $fenhong_max_money - $item['total_fenhong_huiben'];
                                    }
                                }
                                if($commissionpercent != 1){
                                    $fenhongcommission = round($newcommission*$commissionpercent,2);
                                    $fenhongmoney = round($newcommission*$moneypercent,2);
                                }else{
                                    $fenhongcommission = $newcommission;
                                    $fenhongmoney = 0;
                                }
                                $midfhArr['level_'.$fhlevel['id']][$mid] = [
                                    'totalcommission'=>$newcommission,
                                    'commission'=>$fenhongcommission,
                                    'money'=>$fenhongmoney,
                                    'score'=>$score,
                                    'ogids'=>[$og['id']],
                                    'module'=>$og['module'] ?? 'shop',
                                ];
                                
                            }
                            $member_total = bcadd($member_total, $newcommission,2);
                            if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                self::fhrecord($aid,$mid,$fenhongcommission,$score,$og['id'],$og['module'] ?? 'shop','fenhong_huiben',t('回本股东分红',$aid));
                            }
                            if($newcommission>0 && $isyj == 0){
                                //插入回本股东分红级别记录，用于计算封顶值级差
                                $exit = Db::name('fenhong_huiben')->where('aid',$aid)->where('mid',$mid)->where('levelid',$member_level['id'])->find();
                                if($exit){
                                    $fh_log = [];
                                    $fh_log['fenhong'] = bcadd($exit['fenhong'],$newcommission,2);
                                    $fh_log['max'] = $member_level['fenhong_max_money_huiben'];
                                    $fh_log['update_time'] = time();
                                    Db::name('fenhong_huiben')->where('id',$exit['id'])->update($fh_log);
                                }else{
                                    $fh_log = [];
                                    $fh_log['aid'] = $aid;
                                    $fh_log['mid'] = $mid;
                                    $fh_log['levelid'] = $member_level['id'];
                                    $fh_log['level_sort'] = $member_level['sort'];
                                    $fh_log['fenhong'] = $newcommission;
                                    $fh_log['update_time'] = time();
                                    $fh_log['maximum'] = $fenhong_max_money2;//分红额度
                                    $fh_log['max'] = $member_level['fenhong_max_money_huiben'];
                                    Db::name('fenhong_huiben')->insert($fh_log);
                                }
                            }

                        }
                    }
                }
                if($isyj == 0 && $sysset['fhjiesuanhb'] == 0) {
                    if($midfhArr){
                        foreach($midfhArr as $levelstr=>$midfhArr2){
                            $levelid = explode('_',$levelstr)[1];
                            $levelname = $fhlevellist[$levelid]['name'];
                            $remark = '【'.$levelname.'】'.t('回本股东分红',$aid);
                            self::fafang($aid,$midfhArr2,'fenhong_huiben',$remark,0,[],1);
                        }
                        //根据分红奖团队收益
                        if(getcustom('teamfenhong_shouyi',$aid)){
                            self::teamshouyi($aid,$sysset,$midfhArr,$oglist,$isyj,$yjmid,$commissionpercent,$moneypercent);
                        }
                    }
                    $midfhArr = [];
                }
            }

            if($isyj == 0 && $sysset['fhjiesuanhb'] == 1) {
                if($midfhArr){
                    foreach($midfhArr as $levelstr=>$midfhArr2){
                        $levelid = explode('_',$levelstr)[1];
                        $remark = t('回本股东分红',$aid);
                        self::fafang($aid,$midfhArr2,'fenhong_huiben',$remark,0,[],1);
                    }
                    //根据分红奖团队收益
                    if(getcustom('teamfenhong_shouyi',$aid)){
                        self::teamshouyi($aid,$sysset,$midfhArr,$oglist,$isyj,$yjmid,$commissionpercent,$moneypercent);
                    }
                }
            }
            if($isyj == 1){
                //计算团队收益预收益
                if(getcustom('teamfenhong_shouyi',$aid)){
                    self::teamshouyi($aid,$sysset,$midfhArr,$oglist,$isyj,$yjmid,$commissionpercent,$moneypercent);
                    if(!empty($res_lvyou['commissionyj']) && $res_lvyou['commissionyj']>0){
                        $res_lvyou_commissionyj = $res_lvyou['commissionyj']??0;
                        $commissionyj = bcadd($commissionyj,$res_lvyou_commissionyj,2);
                    }
                }
                return ['commissionyj'=>round($commissionyj,2),'oglist'=>$newoglist];
            }
            //回本分红结算完立即发放
            $log_ids = Db::name('member_fenhonglog')->where('aid',$aid)->where('status',0)->where('type','fenhong_huiben')->column('id');
            if($log_ids){
                \app\common\Fenhong::send($aid,0,0,$log_ids);
            }
        }

    }

    //加权分红结算
    static public function JiesuanJiaquanFenhongByDay($aid='',$date='',$iszx=false)
    {
        if (getcustom('fenhong_jiaquan_bylevel',$aid)) {
            $amap = [];
            if($aid){
                $amap[] = ['aid','=',$aid];
            }
            if($date){
                $curtime = strtotime($date);
            }else{
                $curtime = time();
            }
            writeLog('-----------jiaquan: '.date('Ymd H:i:s',$curtime).'---------');
            $adminsetlist = Db::name('admin_set')->where('fenhong_jqjs_rate', '>', 0)->where($amap)->field('id,aid,fenhong_jqjs_time,fenhong_jqjs_rate')->select()->toArray();
            foreach ($adminsetlist as $k => $adminset) {
                $totalprice = 0;
                $aid = $adminset['aid'];
                //如果未设置，则每天1点执行结算
                $rate = $adminset['fenhong_jqjs_rate'];
                if(empty($rate) || $rate<=0){
                    continue;
                }
                $time = $adminset['fenhong_jqjs_time'] ? intval($adminset['fenhong_jqjs_time']) : '1';
                $zxtime = $curtime+$time*3600;//执行时间
                if (!$iszx && intval(date('H')) < $time) {
                    continue;
                }
                $done = Db::name('fenhong_jiaquan_log')->where('aid',$aid)->where('date',date('Ymd',$curtime))->find();
                if(!$iszx && $done){
                    continue;
                }
                $jiesuanEndtime = strtotime(date('Y-m-d 00:00:00', $curtime));//上一天结束时间
                $jiesuanStarttime = $jiesuanEndtime-86400;

                $jisuandate = date('Ymd',$jiesuanEndtime-10);
                //所有的等级份数
                $totalCopies = Db::name('member')
                    ->where('aid', $aid)
                    ->where('fhcopies', '<>',0)
                    ->sum('fhcopies');
                if (empty($totalCopies) || $totalCopies<0) {
                    continue;
                }
                $js_type = $adminset['fhjiesuantime_type']??0;
                $logicType = 0;
                //总金额=商城金额+ 收银台金额 + 门店金额
                if(getcustom('fenhong_jiaquan_copies',$aid)){
                    $logicType = 1;//二次定制，会员等级的订单统计时间
                    //按会员等级的有效日期汇总金额
                    $mendianTotalprice = Db::name('mendian_shop_order')->where('aid', $aid)->where('status', 0)->where('date', $jisuandate)->sum('totalprice');
                    writeLog('门店总金额0：'.$mendianTotalprice);
                    $totalprice = round($totalprice+$mendianTotalprice,2);
                    //统计开始
                    $awhere = $effectWhere= [];
                    $awhere[] = ['aid','=',$aid];
                    if($js_type==1){
                        $afield = 'paytime';
                        $awhere[] = ['status','in',[1,2,3]];
                    }else{
                        $afield = 'collect_time';
                        $awhere[] = ['status','=',3];
                    }
                    $awhere[] = [$afield,'between',[$jiesuanStarttime,$jiesuanEndtime]];
                    //商城
                    $shopTotalpriceTmp = round(Db::name('shop_order')->where($awhere)->sum('totalprice'),2);
                    //收银台
                    $cashierTotalpriceTmp = round(Db::name('cashier_order')->where('aid', $aid)->where('status', '=', 1)->where('paytime', 'between',[$jiesuanStarttime,$jiesuanEndtime])->sum('totalprice'),2);
                    //买单
                    $maidanTotalprice = round(Db::name('maidan_order')->where('aid', $aid)->where('status', 1)->where('paytime', 'between',[$jiesuanStarttime,$jiesuanEndtime])->sum('paymoney'),2);
                    //外卖
                    $restaurantTakeawayTotalpriceTmp = round(Db::name('restaurant_takeaway_order')->where($awhere)->sum('totalprice'),2);
                    //点餐
                    $restaurantShopTotalpriceTmp = round(Db::name('restaurant_shop_order')->where($awhere)->sum('totalprice'),2);
                    //非全额退款后关闭的订单
                    //预约
                    $yuyueTotalpriceTmp = round(Db::name('yuyue_order')->where($awhere)->sum('totalprice'),2);
                    $totalprice = round($totalprice + $shopTotalpriceTmp + $cashierTotalpriceTmp + $restaurantShopTotalpriceTmp + $restaurantTakeawayTotalpriceTmp + $yuyueTotalpriceTmp + $maidanTotalprice,2);
                    writeLog('商城总金额：'.$shopTotalpriceTmp);
                    writeLog('收银台总金额：'.$cashierTotalpriceTmp);
                    writeLog('买单总金额：'.$maidanTotalprice);
                    writeLog('点餐总金额：'.$restaurantShopTotalpriceTmp);
                    writeLog('外卖总金额：'.$restaurantTakeawayTotalpriceTmp);
                    writeLog('预约总金额：'.$yuyueTotalpriceTmp);
                }
                if($logicType==0){
                    //确认收货的【已完成的】
                    $shopTotalprice = Db::name('shop_order_goods')->where('aid', $aid)->where('endtime', 'between',[$jiesuanStarttime,$jiesuanEndtime])->where('status', 'in', [3])->sum('totalprice');
                    $cashierTotalprice = Db::name('cashier_order')
                        ->where('aid', $aid)->where('status', 1)->where('paytime', 'between',[$jiesuanStarttime,$jiesuanEndtime])->sum('totalprice');
                    $mendianTotalprice = Db::name('mendian_shop_order')->where('aid', $aid)->where('status', 0)->where('date', $jisuandate)->sum('totalprice');
                    writeLog('商城总金额：'.$shopTotalprice);
                    writeLog('收银台总金额：'.$cashierTotalprice);
                    writeLog('门店总金额：'.$mendianTotalprice);
                    $totalprice = round($shopTotalprice + $cashierTotalprice + $mendianTotalprice, 2);
                }
                $jiesuanTotalprice = round($totalprice * $rate * 0.01,2);
                writeLog('平台总金额：'.$totalprice);
                writeLog('结算总金额：'.$jiesuanTotalprice);
                writeLog('平台总份数：'.$totalCopies);
                //计算每一份的金额
                $oneCopiePrice = $jiesuanTotalprice / $totalCopies;
                writeLog('每份数金额：'.$oneCopiePrice);
                //会员数据发放
                $mfield = 'm.id,m.fhcopies,m.aid,m.levelid';
                if(getcustom('fenhong_jiaquan_copies',$aid)) {
                    $mfield.=',l.fenhong_jiaquan_maxmoney,l.fenhong_jiaquan_maxmoney';
                }
                $memberlist = Db::name('member')->alias('m')->join('member_level l','m.levelid=l.id')->where('m.aid', $aid)->where('m.fhcopies', '>',0)->field($mfield)->select()->toArray();
                foreach ($memberlist as $k => $member) {
                    $memberTotalCopies = $member['fhcopies'];
                    writeLog('会员mid='.$member['id'].'&份数='.$memberTotalCopies);
                    //会员获得份数 * 一份多少钱
                    $fenhongMoney = round($memberTotalCopies * $oneCopiePrice, 2);
                    if ($fenhongMoney > 0) {
                        //设置了分红上限
                        if(getcustom('fenhong_jiaquan_copies',$aid)) {
                            if ($member['levelid']>0 && $member['fenhong_jiaquan_maxmoney']!=-1){
                                $fenhongMaxmoney = $member['fenhong_jiaquan_maxmoney']??0;
                                $logWhere = [];
                                $logWhere[] = ['aid','=',$aid];
                                $logWhere[] = ['mid','=',$member['id']];
                                $logWhere[] = ['module','=','shop_jiaquan'];
                                $logWhere[] = ['type','=','copies_fenhong'];
                                $alreadyGetFenhongMoney = round(Db::name('member_fenhonglog')->where($logWhere)->sum('commission'),2);
                                //超过分红限制不在发放
                                if($alreadyGetFenhongMoney>=$fenhongMaxmoney){
                                    writeLog('mid='.$member['id'].'超过分红限制不在发放'.'，上限:'.$member['fenhong_jiaquan_maxmoney'].'已发放:'.$alreadyGetFenhongMoney);
                                    continue;
                                }
                                //剩余额度小于当前将要发放的分红
                                $remainFenhongMoney  = round($fenhongMaxmoney - $alreadyGetFenhongMoney,2);
                                if($remainFenhongMoney<$fenhongMoney){
                                    $fenhongMoney = $remainFenhongMoney;
                                }
                            }
                        }
                        writeLog('会员mid='.$member['id'].'&份数='.$memberTotalCopies.'&money='.$fenhongMoney);
                        $remark = $jisuandate . t('加权分红',$aid).'结算';
                        \app\common\Member::addcommission($aid, $member['id'], 0, $fenhongMoney, $remark);
                        Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$member['id'],'frommid'=>0,'orderid'=>0,'ogid'=>0,'type'=>'fenhong_copies','commission'=>$fenhongMoney,'copies'=>$memberTotalCopies,'remark'=>$remark,'createtime'=>time(),'status'=>1]);
                        $fhdata = [];
                        $fhdata['aid'] = $aid;
                        $fhdata['mid'] = $member['id'];
                        $fhdata['commission'] = $fenhongMoney;
                        $fhdata['remark'] = $remark;
                        $fhdata['type'] = 'copies_fenhong';
                        $fhdata['createtime'] = $zxtime;
                        $fhdata['copies'] = $memberTotalCopies;
                        $fhdata['module'] = 'shop_jiaquan';
                        Db::name('member_fenhonglog')->insert($fhdata);
                    }
                }
                Db::name('member_fenhong_jiaquan')->where('aid', $aid)->where('status',1)->where('effect_time', '<', $jiesuanEndtime)->update(['status'=>2,'jiesuan_time'=>$zxtime]);
                Db::name('mendian_shop_order')->where('aid', $aid)->where('status', 0)->where('date', $jisuandate)->update(['status'=>1]);
                Db::name('fenhong_jiaquan_log')->insert(['aid'=>$aid,'date'=>date('Ymd',$curtime),'createtime'=>$zxtime]);
            }
        }
    }
    //加权分红状态修改
    static public function updateJiaquanCopies2member($orderid)
    {
        }

    //商家推荐人团队分红
    public static function business_teamfenhong($aid,$sysset,$oglist,$starttime=0,$endtime=0,$isyj=0,$yjmid=0){
        if(getcustom('business_teamfenhong',$aid)){
            if($endtime == 0) $endtime = time();
            if($isyj == 1 && !$oglist){
                $bwhere = [['og.bid','<>','0']];
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                if(!$oglist) $oglist = [];

                //买单分红
                $maidan_orderlist = Db::name('maidan_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where('og.aid',$aid)
                    ->where('og.isfenhong',0)
                    ->where('og.status',1)
                    ->where($bwhere)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                if($maidan_orderlist){
                    foreach($maidan_orderlist as $mdk=>$mdv){
                        $mdv['name']             = $mdv['title'];
                        $mdv['real_totalprice']  = $mdv['paymoney'];
                        $mdv['cost_price']       = 0;
                        $mdv['num']              = 1;
                        $mdv['module']           = 'maidan';
                        $oglist[] = $mdv;
                    }
                }
            }
//        dump($oglist);
            //参与商家推荐人团队分红的等级
            $teamfhlevellist = Db::name('member_level')->where('aid',$aid)->where('business_teamfenhonglv','>','0')->column('*','id');
//      dump($teamfhlevellist);
            if(!$teamfhlevellist) return ['commissionyj'=>0,'oglist'=>[]];

            if(!$oglist) return ['commissionyj'=>0,'oglist'=>[]];

            $defaultCid = Db::name('member_level_category')->where('aid',$aid)->where('isdefault', 1)->value('id');
            if($defaultCid) {
                $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->where('cid', $defaultCid)->column('id');
            } else {
                $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->column('id');
            }
            $ogids = [];
            $midteamfhArr = [];
            $newoglist = [];
            $commissionyj = 0;

            foreach($oglist as $og){
                $commissionyj_my = 0;
                $commissionpercent = 1;
                $moneypercent = 0;

//                if($sysset['fhjiesuantype'] == 0){
//                    $fenhongprice = $og['real_totalprice'];
//                }else{
//                    $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
//                }
                $feepercent = Db::name('business')->where('id',$og['bid'])->value('feepercent');
                $fenhongprice = bcmul($og['real_totalprice'],$feepercent/100,2);

                if($fenhongprice <= 0) continue;
                $ogids[] = $og['id'];
                $uinfo = Db::name('admin_user')->where('aid',$aid)->where('bid',$og['bid'])->where('isadmin',1)->find();
                $member = Db::name('member')->where('id',$uinfo['mid'])->field('id,pid,levelid,path,path_origin')->find();
                if($teamfhlevellist){
                    //判断脱离时间
                    if($member['change_pid_time'] && $member['change_pid_time'] >= $og['createtime']){
                        $pids = $member['path_origin'];
                    }else{
                        $pids = $member['path'];
                    }

                    if($pids){
                        $parentList = Db::name('member')->where('id','in',$pids)->order(Db::raw('field(id,'.$pids.')'))->select()->toArray();
                        $parentList = array_reverse($parentList);
                        $hasfhlevelids = [];
                        //层级判断，如购买人等级未开启“包含自己teamfenhong_self“则购买人的上级为第一级，开启了则购买人为第一级
                        $level_i = 0;
                        $haspingjinumArr = [];
                        foreach($parentList as $k=>$parent){
                            //判断升级时间
                            $leveldata = $teamfhlevellist[$parent['levelid']];
                            if($parent['levelstarttime'] >= $og['createtime']) {
                                $levelup_order_levelid = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $parent['id'])->where('status', 2)
                                    ->where('levelup_time', '<', $og['createtime'])->whereIn('levelid',$defaultLevelIds)->order('levelup_time', 'desc')->value('levelid');
                                if($levelup_order_levelid) {
                                    $parent['levelid'] = $levelup_order_levelid;
                                    $leveldata = $teamfhlevellist[$parent['levelid']];
                                }else{
                                    //不包含自己跳过
                                    unset($parentList[$k]);
                                    continue;
                                }
                            }

                            $level_i++;
//                            if($parent['id'] == $og['mid']) { $level_i--; unset($parentList[$k]);continue;}//不包含自己则层级-1
                            if(!$leveldata || $level_i>$leveldata['business_teamfenhonglv']) continue;

                            $hasfhlevelids[] = $parent['levelid'];
                            $totalfenhongmoney = 0;

                            //分红比例
                            if($leveldata['business_teamfenhongbl'] > 0) {
                                $this_teamfenhongbl = $leveldata['business_teamfenhongbl'];
                                $totalfenhongmoney =  $this_teamfenhongbl * $fenhongprice * 0.01;
                            }

//                        var_dump('$totalfenhongmoney:'.$totalfenhongmoney);
                            if($totalfenhongmoney > 0 ){
                                if($isyj == 1 && $yjmid == $parent['id']){
                                    $commissionyj_my += $totalfenhongmoney;
                                }
                                if($commissionpercent != 1){
                                    $fenhongcommission = round($totalfenhongmoney*$commissionpercent,2);
                                    $fenhongmoney = round($totalfenhongmoney*$moneypercent,2);
                                }else{
                                    $fenhongcommission = $totalfenhongmoney;
                                    $fenhongmoney = 0;
                                }
                                if($midteamfhArr[$parent['id']]){
                                    $midteamfhArr[$parent['id']]['totalcommission'] = $midteamfhArr[$parent['id']]['totalcommission'] + $totalfenhongmoney;
                                    $midteamfhArr[$parent['id']]['commission'] = $midteamfhArr[$parent['id']]['commission'] + $fenhongcommission;
                                    $midteamfhArr[$parent['id']]['money'] = $midteamfhArr[$parent['id']]['money'] + $fenhongmoney;
                                    $midteamfhArr[$parent['id']]['score'] = $midteamfhArr[$parent['id']]['score'] + 0;
                                    $midteamfhArr[$parent['id']]['ogids'][] = $og['id'];
                                    $midteamfhArr[$parent['id']]['levelid'] = $parent['levelid'];
                                }else{
                                    $midteamfhArr[$parent['id']] = [
                                        'totalcommission'=>$totalfenhongmoney,
                                        'commission'=>$fenhongcommission,
                                        'money'=>$fenhongmoney,
                                        'score'=>0,
                                        'ogids'=>[$og['id']],
                                        'module'=>$og['module'] ?? 'shop',
                                        'levelid' => $parent['levelid'],
                                        'type' => '商家团队分红',
                                    ];
                                }
                                if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                    self::fhrecord($aid,$parent['id'],$fenhongcommission,0,$og['id'],$og['module'] ?? 'shop','business_teamfenhong',t('商家团队分红',$aid));
                                }
                            }
                            //平级奖 找最近的上级
                            if(getcustom('business_teamfenhong_pj',$aid)){
                                //teamfenhong_pingji_type:0按奖励金额,1按订单金额
                                $last_teamfenhongbl_pj = 0;
                                $levelSort = [];
                                if($leveldata['business_teamfenhonglv_pj']>0 && $leveldata['business_teamfenhongbl_pj']>0 ){
                                    $last_pingji_levelid = $parent['levelid'];//上一个拿平级奖的会员id
                                    foreach($parentList as $k2=>$parent2){
                                        //dump($parent2['id'].'平级开始');
                                        $parent2Level = $teamfhlevellist[$parent2['levelid']];
                                        $levelSort[] = $parent2Level['sort'];
                                        if($k2 > $k && $parent2['levelid'] == $parent['levelid']){
                                            //暂时关闭 等级比例优先级低于商品独立设置
                                            $this_teamfenhongbl_pj = $leveldata['business_teamfenhongbl_pj'];
                                            if($this_teamfenhongbl_pj <=0) $this_teamfenhongbl_pj = 0;
                                            if($this_teamfenhongbl_pj == 0 ) continue;

                                            if($haspingjinumArr[$parent['levelid']]){
                                                $haspingjinumArr[$parent['levelid']]++;
                                            }else{
                                                $haspingjinumArr[$parent['levelid']] = 1;
                                            }
                                            if($haspingjinumArr[$parent['levelid']] > $leveldata['business_teamfenhonglv_pj']) break;
                                            $totalfenhongmoney_pj = 0;
                                            if($this_teamfenhongbl_pj>0){
                                                if($leveldata['business_teamfenhong_pingji_type'] == 0){
                                                    //按奖励金额
                                                    $totalfenhongmoney_pj += $this_teamfenhongbl_pj * $totalfenhongmoney * 0.01;
                                                }else{
                                                    //按订单金额
                                                    $totalfenhongmoney_pj += $this_teamfenhongbl_pj * $fenhongprice * 0.01;
                                                }
                                            }
                                            if($totalfenhongmoney_pj > 0 ){
                                                if($isyj == 1 && $yjmid == $parent2['id']){
                                                    $commissionyj_my += $totalfenhongmoney_pj;
                                                }
                                                if($commissionpercent != 1){
                                                    $fenhongcommission = round($totalfenhongmoney_pj*$commissionpercent,2);
                                                    $fenhongmoney = round($totalfenhongmoney_pj*$moneypercent,2);
                                                }else{
                                                    $fenhongcommission = $totalfenhongmoney_pj;
                                                    $fenhongmoney = 0;
                                                }

                                                if($midteamfhArr[$parent2['id']]){
                                                    $midteamfhArr[$parent2['id']]['totalcommission'] = $midteamfhArr[$parent2['id']]['totalcommission'] + $totalfenhongmoney_pj;
                                                    $midteamfhArr[$parent2['id']]['commission'] = $midteamfhArr[$parent2['id']]['commission'] + $fenhongcommission;
                                                    $midteamfhArr[$parent2['id']]['money'] = $midteamfhArr[$parent2['id']]['money'] + $fenhongmoney;
                                                    $midteamfhArr[$parent2['id']]['score'] = $midteamfhArr[$parent2['id']]['score'] + 0;
                                                    $midteamfhArr[$parent2['id']]['ogids'][] = $og['id'];
                                                    $midteamfhArr[$parent2['id']]['levelid'] = $parent2['levelid'];
                                                }else{
                                                    $midteamfhArr[$parent2['id']] = [
                                                        'totalcommission'=>$totalfenhongmoney_pj,
                                                        'commission'=>$fenhongcommission,
                                                        'money'=>$fenhongmoney,
                                                        'score'=>0,
                                                        'ogids'=>[$og['id']],
                                                        'module'=>$og['module'] ?? 'shop',
                                                        'levelid' => $parent2['levelid']
                                                    ];
                                                }
                                                if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                                    self::fhrecord($aid,$parent2['id'],$fenhongcommission,0,$og['id'],$og['module'] ?? 'shop','business_teamfenhong',t('商家团队分红',$aid));
                                                }
                                                $last_pingji_levelid = $parent2['levelid'];
                                                //dump($parent2['id'].'获得平级奖'.$fenhongcommission);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
//            dump($midteamfhArr);
                if($isyj == 1 && $commissionyj_my > 0){
                    $commissionyj += $commissionyj_my;
                    $og['commission'] = round($commissionyj_my,2);
                    $og['fhname'] = t('商家团队分红',$aid);
                    $newoglist[] = $og;
                }


                self::fafang($aid,$midteamfhArr,'business_teamfenhong',t('商家团队分红',$aid),$og['mid']);
                $midteamfhArr = [];
            }
        }

    }
    /**
     * 团队培育奖
     * 拿下级佣金收益的百分比
     */
    public static function teampeiyujiang($aid,$sysset,$midteamfhArr = [],$oglist = [],$isyj=0,$yjmid=0,$commissionpercent=1,$moneypercent=0){
        if(getcustom('teamfenhong_peiyujiang',$aid)){
            writeLog('团队培育奖','teamfenhong_peiyujiang.log');
            //dump($midteamfhArr);
            writeLog('奖金数据'.json_encode($midteamfhArr),'teamfenhong_peiyujiang.log');
            if(empty($midteamfhArr)){
                return true;
            }
            $midshouyiArr = [];//团队培育奖
            $newoglist = [];
            $oglist = array_column($oglist,null,'id');
            //循环处理拿团队分红奖的会员
            foreach($midteamfhArr as $t_mid=>$team){

                writeLog('会员'.$t_mid.'团队培育奖开始,总收益'.$team['commission'],'teamfenhong_peiyujiang.log');

                //拿奖人从$t_mid开始顺着推荐网向上找最近符合条件的会员
                $member = Db::name('member')->where('id', $t_mid)->find();
                $pid = $member['pid'];
                if(!$pid){
                    continue;
                }
                $parent = Db::name('member')->where('id','=',$pid)->find();
                $level_data = Db::name('member_level')->where('id',$parent['levelid'])->find();
                //奖金比例
                $bonus_bili = $level_data['teamfenhong_peiyujiang_bl'];
                if($bonus_bili <=0){
                    continue;
                }
                $commissionyj = 0;
                $down_mid = $t_mid;
                $mid = $parent['id'];
                $commission_total = $team['commission'];
                $commission_total = bcmul($commission_total,$bonus_bili/100,2);

                if($isyj == 1 && $yjmid == $mid){
                    $commissionyj_my = $commission_total;
                }
                if($commissionpercent != 1){
                    $fenhongcommission = round($commission_total*$commissionpercent,2);
                    $fenhongmoney = round($commission_total*$moneypercent,2);
                }else{
                    $fenhongcommission = $commission_total;
                    $fenhongmoney = 0;
                }
                //团队收益汇总
                if($midshouyiArr[$mid]){
                    $midshouyiArr[$mid]['totalcommission'] = bcadd($midshouyiArr[$mid]['totalcommission'] , $commission_total);
                    $midshouyiArr[$mid]['commission'] = bcadd($midshouyiArr[$mid]['commission'] , $fenhongcommission);
                    $midshouyiArr[$mid]['money'] = bcadd($midshouyiArr[$mid]['money'] , $fenhongmoney);
                    $midshouyiArr[$mid]['score'] = 0;
                    $midshouyiArr[$mid]['ogids'][] = $team['ogids'];
                    $midshouyiArr[$mid]['levelid'] = $parent['levelid'];
                }else{
                    $midshouyiArr[$mid] = [
                        'totalcommission' => $commission_total,
                        'commission' => $fenhongcommission,
                        'money' => $fenhongmoney,
                        'score' => 0,
                        'ogids' => $team['ogids'],
                        'module' => $team['module'],
                        'levelid' => $parent['levelid']
                    ];
                }
                if($isyj == 1 && $commissionyj_my > 0){
                    $commissionyj = bcadd($commissionyj,$commissionyj_my,2);

                    $newoglist[$t_mid] = $commissionyj_my;
                }

                self::fafang($aid,$midshouyiArr,'teamfenhong_peiyujiang',t('团队培育奖',$aid),$down_mid);
                writeLog('会员'.$t_mid.'团队培育奖写入'.$team['commission'],'teamfenhong_peiyujiang.log');
                $midshouyiArr = [];
            }
        }
    }

    //团队分红级差
    public static function teamfenhong_jicha($aid,$sysset,$oglist,$starttime=0,$endtime=0,$isyj=0,$yjmid=0){
        if(!getcustom('teamfenhong_jicha',$aid)) return ['commissionyj'=>0,'oglist'=>[]];
//        dump('团队级差分红进入');
        if($endtime == 0) $endtime = time();
        if($isyj == 1 && !$oglist){
            //多商户的商品是否参与分红
            if($sysset['fhjiesuanbusiness'] == 1){
                $bwhere = '1=1';
            }else{
                $bwhere = [['og.bid','=','0']];
            }
            $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
//            dump($oglist);
            if(!$oglist) $oglist = [];
            if(getcustom('yuyue_fenhong',$aid)){
                $yyorderlist = Db::name('yuyue_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($yyorderlist as $k=>$v){
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = $v['cost_price'] ?? 0;
                    $v['module'] = 'yuyue';
                    $oglist[] = $v;
                }
            }
            if(getcustom('scoreshop_fenhong',$aid)){
                $scoreshopoglist = Db::name('scoreshop_order_goods')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($scoreshopoglist as $v){
                    $v['real_totalprice'] = $v['totalmoney'];
                    $v['module'] = 'scoreshop';
                    $oglist[] = $v;
                }
            }
            if(getcustom('luckycollage_fenhong',$aid)){
                $lcorderlist = Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->whereRaw('og.buytype=1 or og.iszj=1')->where('og.isjiqiren',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($lcorderlist as $k=>$v){
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'lucky_collage';
                    $oglist[] = $v;
                }
            }
            if(getcustom('maidan_fenhong',$aid) && !getcustom('maidan_fenhong_new',$aid)){
                //买单分红
                $maidan_orderlist = Db::name('maidan_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where('og.aid',$aid)
                    ->where('og.isfenhong',0)
                    ->where('og.status',1)
                    ->where($bwhere)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                if($maidan_orderlist){
                    foreach($maidan_orderlist as $mdk=>$mdv){
                        $mdv['name']             = $mdv['title'];
                        $mdv['real_totalprice']  = $mdv['paymoney'];
                        $mdv['cost_price']       = 0;
                        $mdv['num']              = 1;
                        $mdv['module']           = 'maidan';
                        $oglist[] = $mdv;
                    }
                }
            }

            if(getcustom('restaurant_fenhong',$aid)){
                //点餐
                $diancan_oglist = Db::name('restaurant_shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('restaurant_shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                if($diancan_oglist){
                    foreach($diancan_oglist as $dck=>$dcv){
                        $dcv['module'] = 'restaurant_shop';
                        $oglist[]      = $dcv;
                    }
                    unset($dcv);
                }
                //外卖
                $takeaway_oglist = Db::name('restaurant_takeaway_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('restaurant_takeaway_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                if($takeaway_oglist){
                    foreach($takeaway_oglist as $tak=>$tav){
                        $tav['module'] = 'restaurant_takeaway';
                        $oglist[]      = $tav;
                    }
                    unset($tav);
                }
            }
            if(getcustom('fenhong_times_coupon',$aid)){
                $cwhere[] =['og.isfenhong','=',0];
                $cwhere[] =['og.status','=',1];
                $cwhere[] =['og.paytime','>=',$starttime];
                $cwhere[] =['og.paytime','<',$endtime];
                if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                    $cwhere[] =['og.bid','=',0];
                }
                $couponorderlist = Db::name('coupon_order')->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where($cwhere)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                foreach($couponorderlist as $k=>$v){
                    $v['name'] = $v['title'];
                    $v['real_totalprice'] = $v['price'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'coupon';
                    $oglist[] = $v;
                }
            }
            if(getcustom('fenhong_kecheng',$aid)){
                $kwhere = [];
                $kwhere[] = ['og.aid','=',$aid];
                $kwhere[] = ['og.isfenhong','=',0];
                $kwhere[] = ['og.status','=',1];
                $kwhere[] = ['og.paytime','>=',$starttime];
                $kwhere[] = ['og.paytime','<',$endtime];
                if($sysset['fhjiesuanbusiness'] != 1){
                    $kwhere[] = ['og.bid','=','0'];
                }
                $kechenglist = Db::name('kecheng_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where($kwhere)
                    ->field('og.*," " as area2,m.nickname,m.headimg')
                    ->select()
                    ->toArray();
                if($kechenglist){
                    foreach($kechenglist as $v){
                        $v['name']            = $v['title'];
                        $v['real_totalprice'] = $v['totalprice'];
                        $v['cost_price']      = 0;
                        $v['module']          = 'kecheng';
                        $v['num']             = 1;
                        $oglist[]             = $v;
                    }
                }
            }
        }
        //        dump($oglist);
        //参与团队分红的等级
        $teamfhlevellist = Db::name('member_level')->where('aid',$aid)->column('*','id');
//      dump($teamfhlevellist);
        if(!$teamfhlevellist) return ['commissionyj'=>0,'oglist'=>[]];
        if(!$oglist) return ['commissionyj'=>0,'oglist'=>[]];
        $defaultCid = Db::name('member_level_category')->where('aid',$aid)->where('isdefault', 1)->value('id');
        if($defaultCid) {
            $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->where('cid', $defaultCid)->column('id');
        } else {
            $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->column('id');
        }

        $isjicha = true;
        $allfenhongprice = 0;
        $ogids = [];
        $midteamfhArr = [];
        $teamfenhong_orderids = [];//按单奖励类 每单只发一次
        $teamfenhong_orderids_pj = [];
        $newoglist = [];
        $commissionyj = 0;
        foreach($oglist as $og){
            $commissionyj_my = 0;
            $pj_levelids = [];
            if($sysset['fhjiesuantype'] == 0){
                $fenhongprice = $og['real_totalprice'];
            }else{
                $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
            }
            if(getcustom('yx_buyer_subsidy',$aid)){
                if($sysset['fhjiesuantype'] == 2){
                    //按抽成计算
                    $fenhongprice = $og['order_fee']??0 ;
                }
            }
            if($fenhongprice <= 0) continue;
            $ogids[] = $og['id'];
            $allfenhongprice = $allfenhongprice + $fenhongprice;
            $member = Db::name('member')->where('id', $og['mid'])->find();
            if($teamfhlevellist){
                //判断脱离时间
                if($member['change_pid_time'] && $member['change_pid_time'] >= $og['createtime']){
                    $pids = $member['path_origin'];
                }else{
                    $pids = $member['path'];
                }
                if($pids){
                    $pids .= ','.$og['mid'];
                }else{
                    $pids = (string)$og['mid'];
                }
                if($pids){
                    $parentList = Db::name('member')->where('id','in',$pids)->order(Db::raw('field(id,'.$pids.')'))->select()->toArray();
                    $parentList = array_reverse($parentList);
                    $hasfhlevelids = [];
                    $last_teamfenhongbl = 0;
                    $last_teamfenhongmoney = 0;
                    //层级判断，如购买人等级未开启“包含自己teamfenhong_self“则购买人的上级为第一级，开启了则购买人为第一级
                    $level_i = 0;

                    foreach($parentList as $k=>$parent){
//                        dump('上级会员'.$parent['id'].'开始');
                        //判断升级时间
                        $leveldata = $teamfhlevellist[$parent['levelid']];
                        if($parent['levelstarttime'] >= $og['createtime']) {
                            $levelup_order_levelid = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $parent['id'])->where('status', 2)
                                ->where('levelup_time', '<', $og['createtime'])->whereIn('levelid',$defaultLevelIds)->order('levelup_time', 'desc')->value('levelid');
                            if($levelup_order_levelid) {
                                $parent['levelid'] = $levelup_order_levelid;
                                $leveldata = $teamfhlevellist[$parent['levelid']];
                            }else{
//                                if($leveldata['teamfenhong_self'] != 1 || ($leveldata['teamfenhong_self'] == 1 && $parent['id'] != $og['mid']))
                                //不包含自己跳过
                                unset($parentList[$k]);
                                continue;
                            }
                        }
                        if($parent['id'] == $og['mid']) { unset($parentList[$k]);continue;}//不包含自己则层级-1
                        $level_i++;


                        $hasfhlevelids[] = $parent['levelid'];
                        $totalfenhongmoney = 0;
                        $leveldata['teamjicha_money_dan'] = $leveldata['teamjicha_money'];//每单奖励 230915
                        $leveldata['teamjicha_pingji_money_dan'] = $leveldata['teamjicha_pingji_money'];//每单奖励 230915
                        $leveldata['teamjicha_money'] = 0;//重新赋值为0，否则按单奖励会重复计算
                        $leveldata['teamjicha_pingji_money'] = 0;

                        if($og['module'] != 'luckycollage2'){
                            if($og['module'] == 'yuyue'){
                                $product = Db::name('yuyue_product')->where('id',$og['proid'])->find();
                            }elseif($og['module'] == 'coupon'){
                                $product = Db::name('coupon')->where('id',$og['cpid'])->find();
                            }elseif($og['module'] == 'luckycollage' || $og['module'] == 'lucky_collage'){
                                $product = Db::name('lucky_collage_product')->where('id',$og['proid'])->find();
                            }elseif($og['module'] == 'scoreshop'){
                                $product = Db::name('scoreshop_product')->where('id',$og['proid'])->find();
                            }elseif($og['module'] == 'kecheng'){
                                $product = Db::name('kecheng_list')->where('id',$og['kcid'])->find();
                            }else{
                                $product = Db::name('shop_product')->where('id',$og['proid'])->find();
                            }
                            //商品团队分红独立设置时每单奖励也会发放
                            if($product['teamjichaset'] == 1){ //按比例
                                $fenhongdata = json_decode($product['teamjichadata1'],true);
                                if($fenhongdata){
                                    $leveldata['teamjichabl'] = $fenhongdata[$leveldata['id']]['commission'];
                                    $leveldata['teamjicha_money'] = 0;
                                }
                            }elseif($product['teamjichaset'] == 2){ //按固定金额
                                $fenhongdata = json_decode($product['teamjichadata2'],true);
                                if($fenhongdata){
                                    $leveldata['teamjichabl'] = 0;
                                    $leveldata['teamjicha_money'] = $fenhongdata[$leveldata['id']]['commission'] * $og['num'];
                                }
                            }elseif($product['teamjichaset'] == -1){
                                $leveldata['teamjichabl'] = 0;
                                $leveldata['teamjicha_money'] = 0;
                            }
                            $totalfenhongmoney += $leveldata['teamjicha_money'];

                            //平级独立设置
                            if($product['teamjichapjset'] == 1){ //按比例
                                $fenhongpjdata = json_decode($product['teamjichapjdata1'],true);
                                if($fenhongpjdata){
                                    $leveldata['teamjicha_pingji_bl'] = $fenhongpjdata[$leveldata['id']]['commission'];
                                    $leveldata['teamjicha_pingji_money'] = 0;
                                }
                            }elseif($product['teamjichapjset'] == 2){ //按固定金额
                                $fenhongpjdata = json_decode($product['teamjichapjdata2'],true);
                                if($fenhongpjdata){
                                    $leveldata['teamjicha_pingji_bl'] = 0;
                                    $leveldata['teamjicha_pingji_money'] = $fenhongpjdata[$leveldata['id']]['commission'] * $og['num'];
                                }
                            }elseif($product['teamjichapjset'] == -1){
                                $leveldata['teamjicha_pingji_bl'] = 0;
                                $leveldata['teamjicha_pingji_money'] = 0;
                            }
                        }
                       // dump($product['teamjichapjset'].'=>'.$leveldata['id'].'=>'.$leveldata['teamjicha_pingji_money'].'=>'. $leveldata['teamjicha_pingji_money_dan']);

                        //每单奖励
                        if($leveldata['teamjicha_money_dan'] > 0 && !in_array($og['orderid'],$teamfenhong_orderids[$parent['id']])) {
                            $totalfenhongmoney += $leveldata['teamjicha_money_dan'];
                            $teamfenhong_orderids[$parent['id']][] = $og['orderid'];
                        }
                        if($isjicha){
                            $totalfenhongmoney = $totalfenhongmoney - $last_teamfenhongmoney;
                        }else{
                            $totalfenhongmoney = $totalfenhongmoney;
                        }
                        if($totalfenhongmoney < 0) $totalfenhongmoney = 0;
                        //分红比例
                        if($leveldata['teamjichabl'] > 0) {
                            if($isjicha){
                                $this_teamfenhongbl = $leveldata['teamjichabl'] - $last_teamfenhongbl;
                            }else{
                                $this_teamfenhongbl = $leveldata['teamjichabl'];
                            }
                            if($this_teamfenhongbl <=0) $this_teamfenhongbl = 0;
                            $last_teamfenhongbl = $last_teamfenhongbl + $this_teamfenhongbl;
                            $totalfenhongmoney = $totalfenhongmoney + $this_teamfenhongbl * $fenhongprice * 0.01;
                        }
                        $last_teamfenhongmoney = $last_teamfenhongmoney + $totalfenhongmoney;
                        //最后一次累计 极差计算用
                        if($totalfenhongmoney > 0 && $parent['id'] != $og['mid'] && (!in_array($parent['levelid'],$pj_levelids) )){
                            //1、下单人自身向上查找平级，但是自身不拿奖；2、已拿平级奖的不拿分红
                            if($isyj == 1 && $yjmid == $parent['id']){
                                $commissionyj_my += $totalfenhongmoney;
                            }
                            $fenhongcommission = $totalfenhongmoney;
                            $fenhongmoney = 0;
                            if($midteamfhArr[$parent['id']]){
                                $midteamfhArr[$parent['id']]['totalcommission'] = $midteamfhArr[$parent['id']]['totalcommission'] + $totalfenhongmoney;
                                $midteamfhArr[$parent['id']]['commission'] = $midteamfhArr[$parent['id']]['commission'] + $fenhongcommission;
                                $midteamfhArr[$parent['id']]['money'] = $midteamfhArr[$parent['id']]['money'] + $fenhongmoney;
                                $midteamfhArr[$parent['id']]['score'] = 0;
                                $midteamfhArr[$parent['id']]['ogids'][] = $og['id'];
                                $midteamfhArr[$parent['id']]['levelid'] = $parent['levelid'];
                            }else{
                                $midteamfhArr[$parent['id']] = [
                                    'totalcommission'=>$totalfenhongmoney,
                                    'commission'=>$fenhongcommission,
                                    'money'=>$fenhongmoney,
                                    'score'=>0,
                                    'ogids'=>[$og['id']],
                                    'module'=>$og['module'] ?? 'shop',
                                    'levelid' => $parent['levelid'],
                                    'type' => '团队级差分红',
                                ];
                            }
                            if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                self::fhrecord($aid,$parent['id'],$fenhongcommission,0,$og['id'],$og['module'] ?? 'shop','teamfenhong',t('团队级差分红',$aid));
                            }
//                            dump($parent['id'].'拿奖'.$totalfenhongmoney);
                        }

                        //平级奖 找最近的上级
                        //teamfenhong_pingji_type:0按奖励金额,1按订单金额
                        $levelSort = [];

//                        dump('平级奖开始');
                        if(($leveldata['teamjicha_pingji_bl']>0 || $leveldata['teamjicha_pingji_money'] > 0 || $leveldata['teamjicha_pingji_money_dan'] > 0)
                            || ($totalfenhongmoney > 0 || $leveldata['teamjicha_pingji_type'] == 1) ){
                                foreach($parentList as $k2=>$parent2){
                                    $parent2Level = $teamfhlevellist[$parent2['levelid']];
                                    $parentLevel = $teamfhlevellist[$parent['levelid']];
                                    $levelSort[] = $parent2Level['sort'];
                                    //dump($parent2Level['id'].'=>'.$parentLevel['id']);
                                    if($parent2Level['sort'] > $parentLevel['sort']){
                                        break;
                                    }
                                    if(in_array($parent2['levelid'],$pj_levelids)){
                                        //每个级别平级奖只发一次
                                        continue;
                                    }
                                    if($k2<=1){
                                        //直推会员不拿平级奖
                                        continue;
                                    }
                                    if($k2 > $k && $parent2['levelid'] == $parent['levelid']){
                                        $this_teamfenhongbl_pj = $leveldata['teamjicha_pingji_bl'];
                                        $this_teamfenhongmoney_pj = $leveldata['teamjicha_pingji_money'];
                                        if($this_teamfenhongbl_pj <=0) $this_teamfenhongbl_pj = 0;
                                        if($this_teamfenhongmoney_pj <=0) $this_teamfenhongmoney_pj = 0;
                                        if($this_teamfenhongbl_pj == 0 && $this_teamfenhongmoney_pj == 0 && $leveldata['teamjicha_pingji_money_dan']==0) continue;

                                        $totalfenhongmoney_pj = 0;
                                        if($this_teamfenhongbl_pj>0){
                                            if($leveldata['teamjicha_pingji_type'] == 0){
                                                //按奖励金额
                                                $totalfenhongmoney_pj += $this_teamfenhongbl_pj * $totalfenhongmoney * 0.01;
                                            }else{
                                                //按订单金额
                                                $totalfenhongmoney_pj += $this_teamfenhongbl_pj * $fenhongprice * 0.01;
                                            }
                                        }
                                        if($product['teamjichapjset'] == 0){
                                            //按会员等级，按总订单发放一次平级奖
                                            if(($this_teamfenhongmoney_pj > 0 || $leveldata['teamjicha_pingji_money_dan'] > 0) && !in_array($og['orderid'],$teamfenhong_orderids_pj[$parent2['id']])){

                                                $this_teamfenhongmoney_pj += $leveldata['teamjicha_pingji_money_dan'];//230915 每单奖励
                                                $totalfenhongmoney_pj += $this_teamfenhongmoney_pj;
                                                if($totalfenhongmoney_pj < 0) $totalfenhongmoney_pj = 0;
                                                $teamfenhong_orderids_pj[$parent2['id']][] = $og['orderid'];
                                            }
                                        }else{
                                            //产品单独设置参数时，按分订单发放多次平级奖
                                            if($this_teamfenhongmoney_pj > 0 && !in_array($og['id'],$teamfenhong_orderids_pj[$parent2['id']])){
                                                $totalfenhongmoney_pj += $this_teamfenhongmoney_pj;
                                                if($totalfenhongmoney_pj < 0) $totalfenhongmoney_pj = 0;
                                                $teamfenhong_orderids_pj[$parent2['id']][] = $og['id'];
                                            }
                                        }

                                        if($totalfenhongmoney_pj > 0 ){
                                            if($isyj == 1 && $yjmid == $parent2['id']){
                                                $commissionyj_my += $totalfenhongmoney_pj;
                                            }
                                            $fenhongcommission = $totalfenhongmoney_pj;
                                            $fenhongmoney = 0;
                                            $fenhongscore = 0;


                                            if($midteamfhArr[$parent2['id']]){
                                                $midteamfhArr[$parent2['id']]['totalcommission'] = $midteamfhArr[$parent2['id']]['totalcommission'] + $totalfenhongmoney_pj;
                                                $midteamfhArr[$parent2['id']]['commission'] = $midteamfhArr[$parent2['id']]['commission'] + $fenhongcommission;
                                                $midteamfhArr[$parent2['id']]['money'] = $midteamfhArr[$parent2['id']]['money'] + $fenhongmoney;
                                                $midteamfhArr[$parent2['id']]['score'] = $midteamfhArr[$parent2['id']]['score'] + $fenhongscore;
                                                $midteamfhArr[$parent2['id']]['ogids'][] = $og['id'];
                                                $midteamfhArr[$parent2['id']]['levelid'] = $parent2['levelid'];
                                            }else{
                                                $midteamfhArr[$parent2['id']] = [
                                                    'totalcommission'=>$totalfenhongmoney_pj,
                                                    'commission'=>$fenhongcommission,
                                                    'money'=>$fenhongmoney,
                                                    'score'=>$fenhongscore,
                                                    'ogids'=>[$og['id']],
                                                    'module'=>$og['module'] ?? 'shop',
                                                    'levelid' => $parent2['levelid']
                                                ];
                                            }
                                            if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                                self::fhrecord($aid,$parent['id'],$fenhongcommission,0,$og['id'],$og['module'] ?? 'shop','teamfenhong',t('团队级差分红',$aid));
                                            }
                                            $pj_levelids[] = $parent2['levelid'];
//                                            dump($parent2['id'].'拿平级奖'.$totalfenhongmoney_pj);
                                        }
                                    }
                                }
                        }
                    }
                }
            }
//            dump($midteamfhArr);
            if($isyj == 1 && $commissionyj_my > 0){
                $commissionyj += $commissionyj_my;
                $og['commission'] = round($commissionyj_my,2);
                $og['fhname'] = t('团队级差分红',$aid);
                $newoglist[] = $og;
            }

            self::fafang($aid,$midteamfhArr,'teamfenhong',t('团队级差分红',$aid),$og['mid']);
            $midteamfhArr = [];
        }
    }

    //团队长分红
    public static function teamleader_fenhong($aid,$sysset,$oglist,$starttime=0,$endtime=0,$isyj=0,$yjmid=0){
        //dump('团队长分红进入');
        if($endtime == 0) $endtime = time();
        if(getcustom('fenhong_manual',$aid)) return ['commissionyj'=>0,'oglist'=>[]];

        if(getcustom('fenhong_business_item_switch',$aid)){
            //查找开启的多商户
            $bids = Db::name('business')->where('aid',$aid)->where('teamfenhong_status',1)->column('id');
            $bids = array_merge([0],$bids);
        }
        if(getcustom('maidan_fenhong_new',$aid)){
            $bids_maidan = Db::name('business')->where('maidan_team','>=',1)->column('id');
            $bids_maidan = array_merge([0],$bids_maidan);
        }
        if($isyj == 1 && !$oglist){
            //多商户的商品是否参与分红
            if($sysset['fhjiesuanbusiness'] == 1){
                $bwhere = '1=1';
            }else{
                $bwhere = [['og.bid','=','0']];
            }
            if(getcustom('fenhong_business_item_switch',$aid)){
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')
                    ->where('og.bid','in',$bids)
                    ->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
            }else{
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
            }
//            dump($oglist);
            if(!$oglist) $oglist = [];
            if(getcustom('yuyue_fenhong',$aid)){
                $yyorderlist = Db::name('yuyue_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($yyorderlist as $k=>$v){
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = $v['cost_price'] ?? 0;
                    $v['module'] = 'yuyue';
                    $oglist[] = $v;
                }
            }
            if(getcustom('scoreshop_fenhong',$aid)){
                $scoreshopoglist = Db::name('scoreshop_order_goods')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($scoreshopoglist as $v){
                    $v['real_totalprice'] = $v['totalmoney'];
                    $v['module'] = 'scoreshop';
                    $oglist[] = $v;
                }
            }
            if(getcustom('luckycollage_fenhong',$aid)){
                $lcorderlist = Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->whereRaw('og.buytype=1 or og.iszj=1')->where('og.isjiqiren',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($lcorderlist as $k=>$v){
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'lucky_collage';
                    $oglist[] = $v;
                }
            }
            if(getcustom('maidan_fenhong',$aid) && !getcustom('maidan_fenhong_new',$aid)){
                //买单分红
                $maidan_orderlist = Db::name('maidan_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where('og.aid',$aid)
                    ->where('og.isfenhong',0)
                    ->where('og.status',1)
                    ->where($bwhere)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                if($maidan_orderlist){
                    foreach($maidan_orderlist as $mdk=>$mdv){
                        $mdv['name']             = $mdv['title'];
                        $mdv['real_totalprice']  = $mdv['paymoney'];
                        $mdv['cost_price']       = 0;
                        $mdv['num']              = 1;
                        $mdv['module']           = 'maidan';
                        $oglist[] = $mdv;
                    }
                }
            }
            if(getcustom('maidan_fenhong_new',$aid) && $sysset['maidanfenhong']){
                //买单分红
                $bwhere_maidan = [['og.bid', 'in', $bids_maidan]];
                $maidan_orderlist = Db::name('maidan_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where('og.aid',$aid)
                    ->where('og.isfenhong',0)
                    ->where('og.status',1)
                    ->where($bwhere_maidan)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                if($maidan_orderlist){
                    foreach($maidan_orderlist as $mdk=>$mdv){
                        $mdv['name']             = $mdv['title'];
                        $mdv['real_totalprice']  = $mdv['paymoney'];
                        //买单分红结算方式
                        if($sysset['maidanfenhong_type'] == 1){
                            //按利润结算时直接把销售额改成利润
                            $mdv['real_totalprice'] = $mdv['paymoney'] - $mdv['cost_price'];
                        }elseif($sysset['maidanfenhong_type'] == 2){
                            //按抽佣计算
                            $mdv['real_totalprice'] = $mdv['order_fee'];
                        }
                        $mdv['cost_price']       = 0;
                        $mdv['num']              = 1;
                        $mdv['module']           = 'maidan';
                        $oglist[] = $mdv;
                    }
                }
            }
            if(getcustom('restaurant_fenhong',$aid)){
                //点餐
                $diancan_oglist = Db::name('restaurant_shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('restaurant_shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                if($diancan_oglist){
                    foreach($diancan_oglist as $dck=>$dcv){
                        $dcv['module'] = 'restaurant_shop';
                        $oglist[]      = $dcv;
                    }
                    unset($dcv);
                }
                //外卖
                $takeaway_oglist = Db::name('restaurant_takeaway_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('restaurant_takeaway_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                if($takeaway_oglist){
                    foreach($takeaway_oglist as $tak=>$tav){
                        $tav['module'] = 'restaurant_takeaway';
                        $oglist[]      = $tav;
                    }
                    unset($tav);
                }
            }
            if(getcustom('fenhong_times_coupon',$aid)){
                $cwhere[] =['og.isfenhong','=',0];
                $cwhere[] =['og.status','=',1];
                $cwhere[] =['og.paytime','>=',$starttime];
                $cwhere[] =['og.paytime','<',$endtime];
                if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                    $cwhere[] =['og.bid','=',0];
                }
                $couponorderlist = Db::name('coupon_order')->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where($cwhere)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                foreach($couponorderlist as $k=>$v){
                    $v['name'] = $v['title'];
                    $v['real_totalprice'] = $v['price'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'coupon';
                    $oglist[] = $v;
                }
            }
            if(getcustom('fenhong_kecheng',$aid)){
                $kwhere = [];
                $kwhere[] = ['og.aid','=',$aid];
                $kwhere[] = ['og.isfenhong','=',0];
                $kwhere[] = ['og.status','=',1];
                $kwhere[] = ['og.paytime','>=',$starttime];
                $kwhere[] = ['og.paytime','<',$endtime];
                if($sysset['fhjiesuanbusiness'] != 1){
                    $kwhere[] = ['og.bid','=','0'];
                }
                $kechenglist = Db::name('kecheng_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where($kwhere)
                    ->field('og.*," " as area2,m.nickname,m.headimg')
                    ->select()
                    ->toArray();
                if($kechenglist){
                    foreach($kechenglist as $v){
                        $v['name']            = $v['title'];
                        $v['real_totalprice'] = $v['totalprice'];
                        $v['cost_price']      = 0;
                        $v['module']          = 'kecheng';
                        $v['num']             = 1;
                        $oglist[]             = $v;
                    }
                }
            }
        }
        //        dump($oglist);
        //参与团队分红的等级
        $teamfhlevellist = Db::name('member_level')->where('aid',$aid)->where('teamleader_fenhonglv','>','0')->column('*','id');
//      dump($teamfhlevellist);
        if(!$teamfhlevellist) return ['commissionyj'=>0,'oglist'=>[]];

        if(getcustom('luckycollage_teamfenhong',$aid)){
            if($sysset['fhjiesuanbusiness'] == 1){
                $bwhere2 = '1=1';
            }else{
                $bwhere2 = [['bid','=','0']];
            }
            if($sysset['fhjiesuantime_type'] == 1) {
                $lkorderlist = Db::name('lucky_collage_order')->where('isfenhong',0)->where('status','in',[1,2,3])->where('iszj',1)->where('isjiqiren',0)->where($bwhere2)->where('createtime','>=',$starttime)->where('createtime','<',$endtime)->select()->toArray();
                if($lkorderlist && $isyj ==0){
                    Db::name('lucky_collage_order')->where('isfenhong',0)->where('status','in',[1,2,3])->where('iszj',1)->where('isjiqiren',0)->where($bwhere2)->where('createtime','>=',$starttime)->where('createtime','<',$endtime)->update(['isfenhong'=>1]);
                }
            } else {
                $lkorderlist = Db::name('lucky_collage_order')->where('isfenhong',0)->where('status',3)->where('iszj',1)->where('isjiqiren',0)->where($bwhere2)->where('createtime','>=',$starttime)->where('createtime','<',$endtime)->select()->toArray();
                if($lkorderlist && $isyj ==0){
                    Db::name('lucky_collage_order')->where('isfenhong',0)->where('status',3)->where('iszj',1)->where('isjiqiren',0)->where($bwhere2)->where('createtime','>=',$starttime)->where('createtime','<',$endtime)->update(['isfenhong'=>1]);
                }
            }
            foreach($lkorderlist as $k=>$v){
                $v['name'] = $v['proname'];
                $v['real_totalprice'] = $v['totalprice'];
                if($isyj == 1){
                    $member = Db::name('member')->where('id',$v['mid'])->find();
                    $v['headimg'] = $member['headimg'];
                    $v['nickname'] = $member['nickname'];
                }
                $v['module'] = 'luckycollage2';
                $oglist[] = $v;
            }
        }
        if(!$oglist) return ['commissionyj'=>0,'oglist'=>[]];

        $defaultCid = Db::name('member_level_category')->where('aid',$aid)->where('isdefault', 1)->value('id');
        if($defaultCid) {
            $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->where('cid', $defaultCid)->column('id');
        } else {
            $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->column('id');
        }
        $allfenhongprice = 0;
        $ogids = [];
        $midteamfhArr = [];
        $teamfenhong_orderids = [];//按单奖励类 每单只发一次
        $newoglist = [];
        $commissionyj = 0;

        foreach($oglist as $og){
            //dump($og['orderid'].'开始');
            if(getcustom('maidan_fenhong_new',$aid) && $og['module']=='maidan'){
                if($og['bid'] > 0 && !in_array($og['bid'],$bids_maidan)){
                    continue;
                }
            }
            if(getcustom('fenhong_business_item_switch',$aid) && $og['module']!='maidan'){
                if($og['bid'] > 0 && !in_array($og['bid'],$bids)){
                    continue;
                }
            }
            $commissionyj_my = 0;
            if(getcustom('commission2moneypercent',$aid) && $sysset['commission2moneypercent1'] > 0){
                //是否是首单
                $beforeorder = Db::name('shop_order')->where('aid',$aid)->where('mid',$og['mid'])->where('status','in','1,2,3')->where('paytime','<',$og['paytime'])->find();
                if(!$beforeorder){
                    $commissionpercent = 1 - $sysset['commission2moneypercent1'] * 0.01;
                    $moneypercent = $sysset['commission2moneypercent1'] * 0.01;
                }else{
                    $commissionpercent = 1 - $sysset['commission2moneypercent2'] * 0.01;
                    $moneypercent = $sysset['commission2moneypercent2'] * 0.01;
                }
            }else{
                $commissionpercent = 1;
                $moneypercent = 0;
            }
            if($sysset['fhjiesuantype'] == 0){
                $fenhongprice = $og['real_totalprice'];
            }else{
                $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
            }
            if(getcustom('yx_buyer_subsidy',$aid)){
                if($sysset['fhjiesuantype'] == 2){
                    //按抽成计算
                    $fenhongprice = $og['order_fee']??0 ;
                }
            }
            if(getcustom('baikangxie',$aid)){
                $fenhongprice = $og['cost_price'] * $og['num'];
            }
            if($fenhongprice <= 0) continue;
            $ogids[] = $og['id'];
            $allfenhongprice = $allfenhongprice + $fenhongprice;
            $member = Db::name('member')->where('id', $og['mid'])->find();
            //dump('下单人会员id'.$member['id'].'级别id'.$member['levelid']);
            $member_level = Db::name('member_level')->where('id',$member['levelid'])->find();
            $member_extend = Db::name('member_level_record')->field('mid id,levelid')->where('aid', $aid)->where('mid', $og['mid'])->find();
            if($teamfhlevellist){
                //判断脱离时间
                if($member['change_pid_time'] && $member['change_pid_time'] >= $og['createtime']){
                    $pids = $member['path_origin'];
                }else{
                    $pids = $member['path'];
                }

                if($pids){
                    $pids .= ','.$og['mid'];
                }else{
                    $pids = (string)$og['mid'];
                }
                if($pids){
                    $parentList = Db::name('member')->where('id','in',$pids)->order(Db::raw('field(id,'.$pids.')'))->select()->toArray();
                    $parentList = array_reverse($parentList);
                    $hasfhlevelids = [];

                    //层级判断，如购买人等级未开启“包含自己teamfenhong_self“则购买人的上级为第一级，开启了则购买人为第一级
                    $level_i = 0;
                    $total_fafang_commission = 0;//总的要发放的佣金

                    foreach($parentList as $k=>$parent){
                        //dump('会员'.$parent['id'].'级别'.$parent['levelid'].'开始');
                        //判断升级时间
                        $leveldata = $teamfhlevellist[$parent['levelid']];
                        if($parent['levelstarttime'] >= $og['createtime']) {
                            $levelup_order_levelid = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $parent['id'])->where('status', 2)
                                ->where('levelup_time', '<', $og['createtime'])->whereIn('levelid',$defaultLevelIds)->order('levelup_time', 'desc')->value('levelid');
                            if($levelup_order_levelid) {
                                $parent['levelid'] = $levelup_order_levelid;
                                $leveldata = $teamfhlevellist[$parent['levelid']];
                            }else{
//                                if($leveldata['teamfenhong_self'] != 1 || ($leveldata['teamfenhong_self'] == 1 && $parent['id'] != $og['mid']))
                                //不包含自己跳过
                                unset($parentList[$k]);
                                continue;
                            }
                        }

                        $level_i++;
                        if($parent['id'] == $og['mid'] && $leveldata['teamleader_fenhong_self'] != 1) {
                            //不包含自己则层级-1
                            $level_i--;
                            unset($parentList[$k]);continue;
                        }
                        if(!$leveldata || $level_i>$leveldata['teamleader_fenhonglv']){
                            //dump('超出级数跳过');
                            continue;
                        }
                        if($leveldata['teamleader_fenhong_money']<=0 && $leveldata['teamleader_fenhongbl']<=0){
                            //dump('未设置拿奖条件跳过');
                            //未设置拿奖条件跳过
                            continue;
                        }
                        if($leveldata['sort']<$member_level['sort']){
                            //dump('小于下单人级别跳过');
                            //上级会员级别小于下单人级别，跳过
                            continue;
                        }
                        $hasfhlevelids[] = $parent['levelid'];
                        $totalfenhongmoney = 0;
                        $totalfenhongscore = 0;
                        $leveldata['teamleader_fenhong_money_dan'] = $leveldata['teamleader_fenhong_money'];//每单奖励 230915
                        $leveldata['teamleader_fenhong_money'] = 0;//重新赋值为0，否则按单奖励会重复计算
                        if($og['module'] != 'luckycollage2'){
                            if($og['module'] == 'yuyue'){
                                $product = Db::name('yuyue_product')->where('id',$og['proid'])->find();
                            }elseif($og['module'] == 'coupon'){
                                $product = Db::name('coupon')->where('id',$og['cpid'])->find();
                            }elseif($og['module'] == 'luckycollage' || $og['module'] == 'lucky_collage'){
                                $product = Db::name('lucky_collage_product')->where('id',$og['proid'])->find();
                            }elseif($og['module'] == 'scoreshop'){
                                $product = Db::name('scoreshop_product')->where('id',$og['proid'])->find();
                            }elseif($og['module'] == 'kecheng'){
                                $product = Db::name('kecheng_list')->where('id',$og['kcid'])->find();
                            }else{
                                $product = Db::name('shop_product')->where('id',$og['proid'])->find();
                            }
                            if(getcustom('maidan_fenhong',$aid) || getcustom('maidan_fenhong_new',$aid)){
                                if($og['module'] == 'maidan'){
                                    $product = [];
                                    $product['teamleader_fenhongset']   = 0;
                                }
                            }
                            if(getcustom('restaurant_fenhong',$aid)){
                                if($og['module'] == 'restaurant_shop' || $og['module'] == 'restaurant_takeaway'){
                                    $product = [];
                                    $product['teamleader_fenhongset']   = 0;
                                }
                            }
                            //商品团队分红独立设置时每单奖励也会发放
                            if($product['teamleader_fenhongset'] == 1){ //按比例
                                $fenhongdata = json_decode($product['teamleader_fenhongdata1'],true);
                                if($fenhongdata){
                                    $leveldata['teamleader_fenhongbl'] = $fenhongdata[$leveldata['id']]['commission'];
                                    $leveldata['teamleader_fenhong_money'] = 0;
                                }
                            }elseif($product['teamleader_fenhongset'] == 2){ //按固定金额
                                $fenhongdata = json_decode($product['teamleader_fenhongdata2'],true);
                                if($fenhongdata){
                                    $leveldata['teamleader_fenhongbl'] = 0;
                                    $leveldata['teamleader_fenhong_money'] = $fenhongdata[$leveldata['id']]['commission'] * $og['num'];
                                }
                            }elseif($product['teamleader_fenhongset'] == -1){
                                $leveldata['teamleader_fenhongbl'] = 0;
                                $leveldata['teamleader_fenhong_money'] = 0;
                            }
                            $totalfenhongmoney += $leveldata['teamleader_fenhong_money'];
                        }
                        //每单奖励
                        if($leveldata['teamleader_fenhong_money_dan'] > 0 && !in_array($og['orderid'],$teamfenhong_orderids[$parent['id']])) {
                            $totalfenhongmoney += $leveldata['teamleader_fenhong_money_dan'];
                            $teamfenhong_orderids[$parent['id']][] = $og['orderid'];
                        }

                        //dump($totalfenhongmoney);
                        $totalfenhongmoney = $totalfenhongmoney;
                        if($totalfenhongmoney < 0) $totalfenhongmoney = 0;
                        //分红比例
                        if($leveldata['teamleader_fenhongbl'] > 0) {
                            $this_teamfenhongbl = $leveldata['teamleader_fenhongbl'];
                            if($this_teamfenhongbl <=0) $this_teamfenhongbl = 0;
                            $totalfenhongmoney = $totalfenhongmoney + $this_teamfenhongbl * $fenhongprice * 0.01;
                        }

                        if($totalfenhongmoney > 0 || $totalfenhongscore > 0){
                            if($isyj == 1 && $yjmid == $parent['id']){
                                $commissionyj_my += $totalfenhongmoney;
                            }
                            if($commissionpercent != 1){
                                $fenhongcommission = round($totalfenhongmoney*$commissionpercent,2);
                                $fenhongmoney = round($totalfenhongmoney*$moneypercent,2);
                                $fenhongscore = $totalfenhongscore;
                            }else{
                                $fenhongcommission = $totalfenhongmoney;
                                $fenhongmoney = 0;
                                $fenhongscore = $totalfenhongscore;
                            }

                            if($midteamfhArr[$parent['id']]){
                                $midteamfhArr[$parent['id']]['totalcommission'] = $midteamfhArr[$parent['id']]['totalcommission'] + $totalfenhongmoney;
                                $midteamfhArr[$parent['id']]['commission'] = $midteamfhArr[$parent['id']]['commission'] + $fenhongcommission;
                                $midteamfhArr[$parent['id']]['money'] = $midteamfhArr[$parent['id']]['money'] + $fenhongmoney;
                                $midteamfhArr[$parent['id']]['score'] = $midteamfhArr[$parent['id']]['score'] + $fenhongscore;
                                $midteamfhArr[$parent['id']]['ogids'][] = $og['id'];
                                $midteamfhArr[$parent['id']]['levelid'] = $parent['levelid'];
                            }else{
                                $midteamfhArr[$parent['id']] = [
                                    'totalcommission'=>$totalfenhongmoney,
                                    'commission'=>$fenhongcommission,
                                    'money'=>$fenhongmoney,
                                    'score'=>$fenhongscore,
                                    'ogids'=>[$og['id']],
                                    'module'=>$og['module'] ?? 'shop',
                                    'levelid' => $parent['levelid'],
                                    'type' => '团队长分红',
                                ];
                            }
                            if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                self::fhrecord($aid,$parent['id'],$fenhongcommission,$fenhongscore,$og['id'],$og['module'] ?? 'shop','teamfenhong',t('团队长分红',$aid));
                            }
                            //dump($parent['id'].'获得团队长分红'.$fenhongcommission);
                            break;
                        }
                    }
                }
            }
//            dump($midteamfhArr);
            if($isyj == 1 && $commissionyj_my > 0){
                $commissionyj += $commissionyj_my;
                $og['commission'] = round($commissionyj_my,2);
                $og['fhname'] = t('团队长分红',$aid);
                $newoglist[] = $og;
            }


            self::fafang($aid,$midteamfhArr,'teamfenhong',t('团队长分红',$aid),$og['mid']);
            $midteamfhArr = [];

        }
    }
    /**
     * 
     * 分红发放记录
     */

    public static function fhrecord($aid,$mid,$commission,$score,$ogid,$module,$type,$remark){
        if(getcustom('commission_orderrefund_deduct',$aid)){
            $record = Db::name('member_fenhong_record')->where('mid',$mid)->where('module',$module)->where('type',$type)->where('ogid',$ogid)->find();
            if($record){
                return ;
            }
            if($commission > 0 || $score>0) {
                $fhdata = [];
                $fhdata['aid'] = $aid;
                $fhdata['mid'] = $mid;
                $fhdata['commission'] = $commission;
                if(getcustom('gdfenhong_score',$aid)){
                    $fhdata['score'] = $score;
                }
                $fhdata['remark'] = $remark;
                $fhdata['type'] = $type;
                $fhdata['createtime'] = time();
                $fhdata['ogid'] = $ogid;
                $fhdata['module'] = $module;
                Db::name('member_fenhong_record')->insert($fhdata);
            }
        }        
    }

    //团队见点奖
    public static function team_jiandian($aid,$sysset,$oglist,$starttime=0,$endtime=0,$isyj=0,$yjmid=0){
        $team_jiandian_custom = getcustom('team_jiandian',$aid);
        if($team_jiandian_custom) {
            if ($endtime == 0) $endtime = time();

            if ($isyj == 1 && !$oglist) {
                //多商户的商品是否参与分红
                $bwhere = '1=1';
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid', $aid)->where('og.isfenhong', 0)->where('og.status', 'in', [1, 2, 3])->where('og.refund_num',0)->join('shop_order o', 'o.id=og.orderid')->join('member m', 'm.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                if (!$oglist) $oglist = [];
            }
            //参与团队分红的等级
            $teamfhlevellist = Db::name('member_level')->where('aid', $aid)->column('*', 'id');
            if (!$teamfhlevellist) return ['commissionyj' => 0, 'oglist' => []];

            if (!$oglist) return ['commissionyj' => 0, 'oglist' => []];

            $defaultCid = Db::name('member_level_category')->where('aid', $aid)->where('isdefault', 1)->value('id');
            if ($defaultCid) {
                $defaultLevelIds = Db::name('member_level')->where('aid', $aid)->where('cid', $defaultCid)->column('id');
            } else {
                $defaultLevelIds = Db::name('member_level')->where('aid', $aid)->column('id');
            }

            $allfenhongprice = 0;
            $ogids = [];
            $midteamfhArr = [];
            $teamfenhong_orderids = [];
            $newoglist = [];
            $commissionyj = 0;

            foreach ($oglist as $og) {
                $commissionyj_my = 0;

                $commissionpercent = 1;
                $moneypercent = 0;

                if ($sysset['fhjiesuantype'] == 0) {
                    $fenhongprice = $og['real_totalprice'];
                } else {
                    $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                }
                if(getcustom('yx_buyer_subsidy',$aid)){
                    if($sysset['fhjiesuantype'] == 2){
                        //按抽成计算
                        $fenhongprice = $og['order_fee']??0 ;
                    }
                }
                if ($fenhongprice <= 0) continue;
                $ogids[] = $og['id'];
                $allfenhongprice = $allfenhongprice + $fenhongprice;
                $member = Db::name('member')->where('id', $og['mid'])->find();
                $member_leveldata = $teamfhlevellist[$member['levelid']];
                if($member_leveldata['team_jiandian_status']!=1){
                    continue;
                }
                $max_sendnum = $member_leveldata['team_jiandian_people'];
                if ($teamfhlevellist) {
                    //判断脱离时间
                    if ($member['change_pid_time'] && $member['change_pid_time'] >= $og['createtime']) {
                        $pids = $member['path_origin'];
                    } else {
                        $pids = $member['path'];
                    }

                    if ($pids) {
                        $pids .= ',' . $og['mid'];
                    } else {
                        $pids = (string)$og['mid'];
                    }
                    if ($pids) {
                        $parentList = Db::name('member')->where('id', 'in', $pids)->order(Db::raw('field(id,' . $pids . ')'))->select()->toArray();
                        $parentList = array_reverse($parentList);
                        $hasfhlevelids = [];
                        $last_teamfenhongbl = 0;
                        $last_teamfenhongmoney = 0;
                        $level_i = 0;
                        foreach ($parentList as $k => $parent) {
                            if($parent['id']==$og['mid']){
                                continue;
                            }
                            $ii++;
                            //判断升级时间
                            $leveldata = $teamfhlevellist[$parent['levelid']];
                            if ($parent['levelstarttime'] >= $og['createtime']) {
                                $levelup_order_levelid = Db::name('member_levelup_order')->where('aid', $aid)->where('mid', $parent['id'])->where('status', 2)
                                    ->where('levelup_time', '<', $og['createtime'])->whereIn('levelid', $defaultLevelIds)->order('levelup_time', 'desc')->value('levelid');
                                if ($levelup_order_levelid) {
                                    $parent['levelid'] = $levelup_order_levelid;
                                    $leveldata = $teamfhlevellist[$parent['levelid']];
                                } else {
                                    unset($parentList[$k]);
                                    continue;
                                }
                            }

                            $hasfhlevelids[] = $parent['levelid'];
                            if($og['module'] == 'shop' || $og['module']==''){
                                $product = Db::name('shop_product')->where('id', $og['proid'])->find();
                            }
                            if ($product['teamjiandianset'] == 1) { //按比例
                                $fenhongdata = json_decode($product['teamjiandiandata1'], true);
                                if ($fenhongdata) {
                                    $leveldata['team_jiandan_bl'] = $fenhongdata[$leveldata['id']]['commission'];
                                    $leveldata['team_jiandian_money'] = 0;
                                }
                            } elseif ($product['teamjiandianset'] == 2) { //按固定金额
                                $fenhongdata = json_decode($product['teamjiandiandata2'], true);
                                if ($fenhongdata) {
                                    $leveldata['team_jiandan_bl'] = 0;
                                    $leveldata['team_jiandian_money'] = $fenhongdata[$leveldata['id']]['commission'] * $og['num'];
                                }
                            } elseif ($product['teamjiandianset'] == -1) {
                                $leveldata['team_jiandan_bl'] = 0;
                                $leveldata['team_jiandian_money'] = 0;
                            }

                            if (!$leveldata || $level_i >= $max_sendnum) continue;
                            //每单奖励
                            $totalfenhongmoney = 0;
                            if ($leveldata['team_jiandian_money'] > 0 && !in_array($og['orderid'], $teamfenhong_orderids[$parent['id']])) {
                                $totalfenhongmoney = $totalfenhongmoney + $leveldata['team_jiandian_money'];
                                if ($totalfenhongmoney < 0) $totalfenhongmoney = 0;
                                $last_teamfenhongmoney = $last_teamfenhongmoney + $totalfenhongmoney;
                                $teamfenhong_orderids[$parent['id']][] = $og['orderid'];
                            }
                            //分红比例
                            if ($leveldata['team_jiandan_bl'] > 0) {
                                $this_teamfenhongbl = $leveldata['team_jiandan_bl'];
                                if ($this_teamfenhongbl <= 0) $this_teamfenhongbl = 0;
                                $last_teamfenhongbl = $last_teamfenhongbl + $this_teamfenhongbl;
                                $totalfenhongmoney = $totalfenhongmoney + $this_teamfenhongbl * $fenhongprice * 0.01;
                            }
                            if ($totalfenhongmoney > 0) {
                                $level_i++;
                                if ($isyj == 1 && $yjmid == $parent['id']) {
                                    $commissionyj_my += $totalfenhongmoney;
                                }
                                if ($commissionpercent != 1) {
                                    $fenhongcommission = round($totalfenhongmoney * $commissionpercent, 2);
                                    $fenhongmoney = round($totalfenhongmoney * $moneypercent, 2);
                                } else {
                                    $fenhongcommission = $totalfenhongmoney;
                                    $fenhongmoney = 0;
                                }
                                if ($midteamfhArr[$parent['id']]) {
                                    $midteamfhArr[$parent['id']]['totalcommission'] = $midteamfhArr[$parent['id']]['totalcommission'] + $totalfenhongmoney;
                                    $midteamfhArr[$parent['id']]['commission'] = $midteamfhArr[$parent['id']]['commission'] + $fenhongcommission;
                                    $midteamfhArr[$parent['id']]['money'] = $midteamfhArr[$parent['id']]['money'] + $fenhongmoney;
                                    $midteamfhArr[$parent['id']]['ogids'][] = $og['id'];
                                    $midteamfhArr[$parent['id']]['levelid'] = $parent['levelid'];
                                } else {
                                    $midteamfhArr[$parent['id']] = [
                                        'totalcommission' => $totalfenhongmoney,
                                        'commission' => $fenhongcommission,
                                        'money' => $fenhongmoney,
                                        'ogids' => [$og['id']],
                                        'module' => $og['module'] ?? 'shop',
                                        'levelid' => $parent['levelid'],
                                        'type' => t('团队见点奖', $aid),
                                    ];
                                }
                                if(getcustom('commission_orderrefund_deduct',$aid) && $isyj == 0){
                                    self::fhrecord($aid,$parent['id'],$fenhongcommission,0,$og['id'],$og['module'] ?? 'shop','team_jiandian',t('团队见点奖',$aid));
                                }
                            }
                        }
                    }
                }
                if ($isyj == 1 && $commissionyj_my > 0) {
                    $commissionyj += $commissionyj_my;
                    $og['commission'] = round($commissionyj_my, 2);
                    $og['fhname'] = t('团队见点奖', $aid);
                    $newoglist[] = $og;
                }

                self::fafang($aid, $midteamfhArr, 'team_jiandian', t('团队见点奖', $aid),$og['mid']);
                //根据分红奖团队收益
                if(getcustom('teamfenhong_shouyi',$aid)){
                    self::teamshouyi($aid,$sysset,$midteamfhArr,$oglist,$isyj,$yjmid,$commissionpercent,$moneypercent);
                }
                $midteamfhArr = [];

            }
        }
    }

    public static function team_fuchijin($aid,$sysset,$oglist,$starttime=0,$endtime=0,$isyj=0,$yjmid=0){
        $team_fuchijin_custom = getcustom('team_fuchijin',$aid);
        if($team_fuchijin_custom) {
            if ($endtime == 0) $endtime = time();

            if ($isyj == 1 && !$oglist) {
                //多商户的商品是否参与分红
                $bwhere = '1=1';
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid', $aid)->where('og.isfenhong', 0)->where('og.status', 'in', [1, 2, 3])->where('og.refund_num',0)->join('shop_order o', 'o.id=og.orderid')->join('member m', 'm.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                if (!$oglist) $oglist = [];
            }
            //参与团队分红的等级
            $teamfhlevellist = Db::name('member_level')->where('aid', $aid)->where('team_fuchijin_lv','>',0)->column('*', 'id');
            if (!$teamfhlevellist) return ['commissionyj' => 0, 'oglist' => []];

            if (!$oglist) return ['commissionyj' => 0, 'oglist' => []];

            $defaultCid = Db::name('member_level_category')->where('aid', $aid)->where('isdefault', 1)->value('id');
            if ($defaultCid) {
                $defaultLevelIds = Db::name('member_level')->where('aid', $aid)->where('cid', $defaultCid)->column('id');
            } else {
                $defaultLevelIds = Db::name('member_level')->where('aid', $aid)->column('id');
            }

            $allfenhongprice = 0;
            $ogids = [];
            $midteamfhArr = [];
            $teamfenhong_orderids = [];
            $newoglist = [];
            $commissionyj = 0;

            foreach ($oglist as $og) {
                $commissionyj_my = 0;

                $commissionpercent = 1;
                $moneypercent = 0;

                if ($sysset['fhjiesuantype'] == 0) {
                    $fenhongprice = $og['real_totalprice'];
                } else {
                    $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                }
                if(getcustom('yx_buyer_subsidy',$aid)){
                    if($sysset['fhjiesuantype'] == 2){
                        //按抽成计算
                        $fenhongprice = $og['order_fee']??0 ;
                    }
                }
                if ($fenhongprice <= 0) continue;
                $ogids[] = $og['id'];
                $allfenhongprice = $allfenhongprice + $fenhongprice;
                $member = Db::name('member')->where('id', $og['mid'])->find();
                $member_leveldata = $teamfhlevellist[$member['levelid']];

                if ($teamfhlevellist) {
                    //判断脱离时间
                    if ($member['change_pid_time'] && $member['change_pid_time'] >= $og['createtime']) {
                        $pids = $member['path_origin'];
                    } else {
                        $pids = $member['path'];
                    }
                    if ($pids) {
                        $pids .= ',' . $og['mid'];
                    } else {
                        $pids = (string)$og['mid'];
                    }

                    if ($pids) {
                        foreach($teamfhlevellist as $k=>$teamfhlevel) {
                            $parentList = Db::name('member')->where('id', 'in', $pids)->where('levelid', $teamfhlevel['id'])->order(Db::raw('field(id,' . $pids . ')'))->select()->toArray();
                            $count = count($parentList);
                            if($count<=0){
                                continue;
                            }
                            if($count>=$teamfhlevel['team_fuchijin_lv']){
                                $count = $teamfhlevel['team_fuchijin_lv'];
                            }
                            $bonus_total = bcmul($fenhongprice, $teamfhlevel['team_fuchijin_bl'] / 100, 2);
                            $avg_bonus = bcdiv($bonus_total, $count, 2);
                            $i = 0;
                            $parentList = array_reverse($parentList);
                            foreach ($parentList as $k => $parent) {
                                if ($avg_bonus > 0) {
                                    $i++;
                                    if($i>$teamfhlevel['team_fuchijin_lv']){
                                        break;
                                    }
                                    if ($isyj == 1 && $yjmid == $parent['id']) {
                                        $commissionyj_my += $avg_bonus;
                                    }
                                    if ($commissionpercent != 1) {
                                        $fenhongcommission = round($avg_bonus * $commissionpercent, 2);
                                        $fenhongmoney = round($avg_bonus * $moneypercent, 2);
                                    } else {
                                        $fenhongcommission = $avg_bonus;
                                        $fenhongmoney = 0;
                                    }
                                    if ($midteamfhArr[$parent['id']]) {
                                        $midteamfhArr[$parent['id']]['totalcommission'] = $midteamfhArr[$parent['id']]['totalcommission'] + $avg_bonus;
                                        $midteamfhArr[$parent['id']]['commission'] = $midteamfhArr[$parent['id']]['commission'] + $fenhongcommission;
                                        $midteamfhArr[$parent['id']]['money'] = $midteamfhArr[$parent['id']]['money'] + $fenhongmoney;
                                        $midteamfhArr[$parent['id']]['ogids'][] = $og['id'];
                                        $midteamfhArr[$parent['id']]['levelid'] = $parent['levelid'];
                                    } else {
                                        $midteamfhArr[$parent['id']] = [
                                            'totalcommission' => $avg_bonus,
                                            'commission' => $fenhongcommission,
                                            'money' => $fenhongmoney,
                                            'ogids' => [$og['id']],
                                            'module' => $og['module'] ?? 'shop',
                                            'levelid' => $parent['levelid'],
                                            'type' => t('团队扶持金', $aid),
                                        ];
                                    }
                                    if (getcustom('commission_orderrefund_deduct', $aid) && $isyj == 0) {
                                        self::fhrecord($aid, $parent['id'], $fenhongcommission, 0, $og['id'], $og['module'] ?? 'shop', 'team_fuchijin', t('团队扶持金', $aid));
                                    }
                                }
                            }
                        }
                    }
                }
                if ($isyj == 1 && $commissionyj_my > 0) {
                    $commissionyj += $commissionyj_my;
                    $og['commission'] = round($commissionyj_my, 2);
                    $og['fhname'] = t('团队扶持金', $aid);
                    $newoglist[] = $og;
                }

                self::fafang($aid, $midteamfhArr, 'team_fuchijin', t('团队扶持金', $aid),$og['mid']);
                //根据分红奖团队收益
                if(getcustom('team_fuchijin',$aid)){
                    self::teamshouyi($aid,$sysset,$midteamfhArr,$oglist,$isyj,$yjmid,$commissionpercent,$moneypercent);
                }
                $midteamfhArr = [];

            }
        }
    }

    //团队业绩加权分红
    public static function teamyejiweight($aid,$sysset){
        if(getcustom('yx_team_yeji_weight',$aid)){
            if(!$sysset || $sysset['status'] == 0){//未开启
                return true;
            }
            $gettj = explode(',',$sysset['gettj']);
            $map = [];
            $map[] = ['aid','=',$aid];
            if($gettj && !in_array('-1',$gettj)){
                $map[] = ['levelid','in',$gettj];
            }
            $members = Db::name('member')->where($map)->select()->toArray();
            if($sysset['jiesuan_type'] == 1) {//按月
                $s_time = strtotime(date('Y-m-01 00:00:00'));
                $e_time  = strtotime(date('Y-m-t 23:59:59'));
            }elseif($sysset['jiesuan_type'] == 2) {//按季度
                $s_time = strtotime(date('Y-m-01 00:00:00',strtotime('-3 month')));
                $e_time = strtotime(date('Y-m-t 23:59:59',strtotime('-1 month')));
            } elseif($sysset['jiesuan_type'] == 3) {//按年
                $s_time = strtotime(date('Y') . '-01-01 00:00:00');
                $e_time = strtotime(date('Y') . '-12-31 23:59:59');
            }
            $data = [];
            foreach($members as $member){
                $downmids = \app\common\Member::getdownmids($aid,$member['id']);
                if(!$downmids){
                    $downmids = [];
                }
                array_push($downmids,$member['id']);
                $order_yeji = 0;
                $last_yeji = $member['last_weight_yeji'];
                if($downmids){
                    $map = [];
                    $map[] = ['aid','=',$aid];
                    $map[] = ['mid','in',$downmids];
                    $map[] = ['createtime','between',[$s_time,$e_time]];
                    $map[] = ['status','in',[1,2,3]];
                    $proids = [];
                    if($sysset['fwtype']==1){
                        //指定类目
                        $categoryids = explode(',',$sysset['categoryids']);
                        $clist = Db::name('shop_category')->where('pid','in',$categoryids)->select()->toArray();
                        foreach($clist as $kc=>$vc){
                            $categoryids[] = $vc['id'];
                            $cate2 = Db::name('shop_category')->where('pid',$vc['id'])->find();
                            if($cate2) $categoryids[] = $cate2['id'];
                        }
                        $proids = Db::name('shop_goods')->where('categoryid','in',$categoryids)->column('id');
                        if($proids){
                            $map[] = ['proid','in',$proids];
                            $order_yeji = Db::name('shop_order_goods')->where($map)->sum('real_totalprice');
                        }else{
                            $order_yeji = 0;
                        }
                    }elseif($sysset['fwtype']==2){
                        //指定商品
                        $proids = explode(',',$sysset['productids']);
                        if($proids){
                            $map[] = ['proid','in',$proids];
                            $order_yeji = Db::name('shop_order_goods')->where($map)->sum('real_totalprice');
                        }else{
                            $order_yeji = 0;
                        }
                    }else{
                        $order_yeji = Db::name('shop_order_goods')->where($map)->sum('real_totalprice');
                    }
                }
                $member_yeji = bcadd($order_yeji,$last_yeji,2);
                $weight_num = round($member_yeji,$sysset['yeji'],2);
                if($weight_num>0){
                    Db::name('member')->where('id',$member['id'])->update(['last_weight_yeji'=>0]);
                }else if($sysset['yeji_next']){
                    //当期不足业绩累计到下月
                    Db::name('member')->where('id',$member['id'])->inc('last_weight_yeji',$order_yeji)->update();
                }
                $data[] = [
                    'mid' => $member['id'],
                    'weight_num' => $weight_num>0?$weight_num:0,
                    'yeji' => $member_yeji,
                    'order_yeji' => $order_yeji,
                    'last_yeji' => $last_yeji,
                    'levelid' => $member['levelid'],
                ];
            }
            //本期所有业绩
            //$yeji_total = array_sum(array_column($data,'order_yeji'));
            $map_order = [];
            $map_order[] = ['aid','=',$aid];
            $map_order[] = ['createtime','between',[$s_time,$e_time]];
            $map_order[] = ['status','in',[1,2,3]];
            if($sysset['fwtype']==1 || $sysset['fwtype']==2){
                if($proids){
                    $map_order[] = ['proid','in',$proids];
                    $plate_yeji = Db::name('shop_order_goods')->where($map_order)->sum('real_totalprice');
                }else{
                    $plate_yeji = 0;
                }
            }else{
                $plate_yeji = Db::name('shop_order_goods')->where($map_order)->sum('real_totalprice');
            }
            //所有会员的权重
            $weight_total = array_sum(array_column($data,'weight_num'));
            //总奖金数量
            $bonus_total = bcmul($plate_yeji,$sysset['bili']/100,2);
            //每份权重的奖金
            $avg_bonus = bcdiv($bonus_total,$weight_total,2);
            $midteamfhArr = [];
            foreach($data as $k=>$v){
                $member_bonus = bcmul($v['weight_num'],$avg_bonus,2);
                $data[$k]['bonus'] = $member_bonus;
                $commission = $sysset['wallet_type']==0?$member_bonus:0;
                $money = $sysset['wallet_type']==1?$member_bonus:0;
                $score = $sysset['wallet_type']==2?$member_bonus:0;

                //插入团队业绩加权分红记录
                $log = [];
                $log['aid'] = $aid;
                $log['mid'] = $v['mid'];
                $log['levelid'] = $v['levelid'];
                $log['commission'] = $commission;
                $log['money'] = $money;
                $log['score'] = $score;
                $log['jiesuan_type'] = $sysset['jiesuan_type'];
                $log['yeji'] = $v['yeji'];
                $log['order_yeji'] = $v['order_yeji'];
                $log['last_yeji'] = $v['last_yeji'];
                $log['weight_num'] = $v['weight_num'];
                $log['plate_yeji'] = $plate_yeji;
                $log['is_fenhong'] = $member_bonus>0?1:0;
                $log['createtime'] = time();
                $log_id = Db::name('team_yeji_weight_log')->insertGetId($log);
                //记录分红数据
                if($member_bonus>0){
                    $midteamfhArr[$v['mid']] = [
                        'totalcommission' => $member_bonus,
                        'commission' => $commission,
                        'money' => $money,
                        'score' => $score,
                        'ogids' => $log_id,
                        'module' =>'team_yeji_weight_log',
                        'levelid' => $v['levelid'],
                        'type' => t('业绩加权奖',$aid),
                    ];
                }

            }
            if($midteamfhArr){
                //发放分红
                self::fafang($aid,$midteamfhArr,'teamfenhong',t('业绩加权奖',$aid));
            }
        }
    }

    //团长分红
    public static function tuanzhang_fenhong($aid,$sysset,$oglist,$starttime=0,$endtime=0,$isyj=0,$yjmid=0){
        if(getcustom('tuanzhang_fenhong',$aid)){
            if($endtime == 0) $endtime = time();
            if(getcustom('fenhong_manual',$aid)) return ['commissionyj'=>0,'oglist'=>[]];

            if($isyj == 1 && !$oglist){
                //多商户的商品是否参与分红
                if($sysset['fhjiesuanbusiness'] == 1){
                    $bwhere = '1=1';
                }else{
                    $bwhere = [['og.bid','=','0']];
                }
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();

                //            dump($oglist);
                if(!$oglist) $oglist = [];
                if(getcustom('yuyue_fenhong',$aid)){
                    $yyorderlist = Db::name('yuyue_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                    foreach($yyorderlist as $k=>$v){
                        $v['name'] = $v['proname'];
                        $v['real_totalprice'] = $v['totalprice'];
                        $v['cost_price'] = $v['cost_price'] ?? 0;
                        $v['module'] = 'yuyue';
                        $oglist[] = $v;
                    }
                }
                if(getcustom('restaurant_fenhong',$aid) && $sysset['restaurant_fenhong_status']){
                    //点餐
                    $diancan_oglist = Db::name('restaurant_shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('restaurant_shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                    if($diancan_oglist){
                        foreach($diancan_oglist as $dck=>$dcv){
                            $dcv['module'] = 'restaurant_shop';
                            $oglist[]      = $dcv;
                        }
                        unset($dcv);
                    }
                    //外卖
                    $takeaway_oglist = Db::name('restaurant_takeaway_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('restaurant_takeaway_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                    if($takeaway_oglist){
                        foreach($takeaway_oglist as $tak=>$tav){
                            $tav['module'] = 'restaurant_takeaway';
                            $oglist[]      = $tav;
                        }
                        unset($tav);
                    }
                }

            }
            //        dump($oglist);
            //参与团队分红的等级
            $teamfhlevellist = Db::name('member_level')->where('aid',$aid)->where('tuanzhang_fenhong_bl','>','0')->column('*','id');
            if(!$teamfhlevellist) return ['commissionyj'=>0,'oglist'=>[]];
            //最高分红比例等级（用于判断循环到最高级别会员后就不循环了）
            $max_levelid = Db::name('member_level')->where('aid',$aid)->where('tuanzhang_fenhong_bl','>','0')->order('tuanzhang_fenhong_bl desc')->value('id');
            if(!$oglist) return ['commissionyj'=>0,'oglist'=>[]];

            $defaultCid = Db::name('member_level_category')->where('aid',$aid)->where('isdefault', 1)->value('id');
            if($defaultCid) {
                $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->where('cid', $defaultCid)->column('id');
            } else {
                $defaultLevelIds = Db::name('member_level')->where('aid',$aid)->column('id');
            }

            $allfenhongprice = 0;
            $ogids = [];
            $midteamfhArr = [];
            $newoglist = [];
            $commissionyj = 0;
            //团队分红分钱包
            foreach($oglist as $og){
                $path_arr = [];
                if(empty($og['module'])){
                    $og['module'] = 'shop';
                }
                if(!in_array($og['module'],['shop','yuyue','restaurant_shop','restaurant_takeaway','maidan'])){
                    continue;
                }
                $total_og_fenhong = 0;
                $commissionyj_my = 0;
                if($sysset['fhjiesuantype'] == 0){
                    $fenhongprice = $og['real_totalprice'];
                }else{
                    $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                }
                if(getcustom('yx_buyer_subsidy',$aid)){
                    if($sysset['fhjiesuantype'] == 2){
                        //按抽成计算
                        $fenhongprice = $og['order_fee']??0 ;
                    }
                }
                $ogids[] = $og['id'];
                $allfenhongprice = $allfenhongprice + $fenhongprice;
                $member = Db::name('member')->where('id', $og['mid'])->find();
                if(empty($member)){
                    continue;
                    // break;
                }
                //下单会员等级
                if($member['levelstarttime'] >= $og['createtime']) {
                    $levelup_order_levelid = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $member['id'])->where('status', 2)
                        ->where('levelup_time', '<', $og['createtime'])->whereIn('levelid',$defaultLevelIds)->order('levelup_time', 'desc')->value('levelid');
                    if($levelup_order_levelid) {
                        $member['levelid'] = $levelup_order_levelid;
                    }
                }
                if($teamfhlevellist){
                    //判断脱离时间
                    if($member['change_pid_time'] && $member['change_pid_time'] >= $og['createtime']){
                        $pids = $member['path_origin'];
                    }else{
                        $pids = $member['path'];
                    }
                    if($pids){
                        $pids .= ','.$og['mid'];
                    }else{
                        $pids = (string)$og['mid'];
                    }
                    if($pids){
                        $parentList = Db::name('member')->where('id','in',$pids)->order(Db::raw('field(id,'.$pids.')'))->select()->toArray();
                        $parentList = array_reverse($parentList);//父级从近到远，自己，上一级，上二级，上三级。。。
                        $last_teamfenhongbl = 0;
                        //层级判断，如购买人等级未开启“包含自己teamfenhong_self“则购买人的上级为第一级，开启了则购买人为第一级
                        $level_i = 0;
                        foreach($parentList as $k=>$parent){
                            $leveldata = $teamfhlevellist[$parent['levelid']];
                            if($parent['id'] == $og['mid'] && $leveldata['tuanzhang_fenhong_self'] != 1) {
                                //不包含自己
                                //记录path
                                $path_arr[] = [
                                    'mid'=>$parent['id'],
                                    'levelid'=>$parent['levelid'],
                                    'bili'=>0,
                                    'fenhong'=>0,
                                    'pid'=>$parent['pid'],
                                    'path'=>$parent['path']
                                ];
                                unset($parentList[$k]);continue;
                            }
                            //判断升级时间

                            if($parent['levelstarttime'] >= $og['createtime']) {
                                $levelup_order_levelid = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $parent['id'])->where('status', 2)
                                    ->where('levelup_time', '<', $og['createtime'])->whereIn('levelid',$defaultLevelIds)->order('levelup_time', 'desc')->value('levelid');
                                if($levelup_order_levelid) {
                                    $parent['levelid'] = $levelup_order_levelid;
                                    $leveldata = $teamfhlevellist[$parent['levelid']];
                                }else{
                                    //if($leveldata['teamfenhong_self'] != 1 || ($leveldata['teamfenhong_self'] == 1 && $parent['id'] != $og['mid']))
                                    //不包含自己跳过
                                    unset($parentList[$k]);
                                    //记录path
                                    $path_arr[] = [
                                        'mid'=>$parent['id'],
                                        'levelid'=>$parent['levelid'],
                                        'bili'=>0,
                                        'fenhong'=>0,
                                        'pid'=>$parent['pid'],
                                        'path'=>$parent['path']
                                    ];
                                    continue;
                                }
                            }
                            $totalfenhongmoney = 0;
                            if($og['module'] != 'maidan'){
                                if($og['module'] == 'yuyue'){
                                    $product = Db::name('yuyue_product')->where('id',$og['proid'])->find();
                                }elseif($og['module'] == 'restaurant_shop' || $og['module'] == 'restaurant_takeaway'){
                                    $product =  Db::name('restaurant_product')->where('id',$og['proid'])->find();
                                }else{
                                    $product = Db::name('shop_product')->where('id',$og['proid'])->find();
                                }
                                //商品团队分红独立设置时每单奖励也会发放
                                if($product['tuanzhang_fenhong_set'] == -1){
                                    $leveldata['tuanzhang_fenhong_bl'] = 0;
                                }
                            }
                            if($parent['tuanzhang_fenhong_bl']>0){
                                //会员单独设置了团长分红比例
                                $leveldata['tuanzhang_fenhong_bl'] = $parent['tuanzhang_fenhong_bl'];
                            }
                            //分红比例
                            $is_tuanzhang = 0;
                            $this_teamfenhongbl = 0;
                            if($leveldata['tuanzhang_fenhong_bl'] > 0) {
                                $is_tuanzhang = 1;
                                $this_teamfenhongbl = $leveldata['tuanzhang_fenhong_bl'] - $last_teamfenhongbl;
                                if($this_teamfenhongbl <=0) $this_teamfenhongbl = 0;
                                $last_teamfenhongbl = $last_teamfenhongbl + $this_teamfenhongbl;
                                $totalfenhongmoney = $totalfenhongmoney + $this_teamfenhongbl * $fenhongprice * 0.01;
                            }
                            //dump($fenhongprice.'=>'.$this_teamfenhongbl);
                            if($totalfenhongmoney > 0){
                                $level_i++;
                                if($isyj == 1 && $yjmid == $parent['id']){
                                    $commissionyj_my += $totalfenhongmoney;
                                }
                                $fenhongcommission = $totalfenhongmoney;
                                $fenhongmoney = 0;
                                $fenhongscore = 0;
                                $total_og_fenhong = bcadd($total_og_fenhong,$totalfenhongmoney,2);
                                //dump([$k,$member]);
                                if($midteamfhArr[$parent['id']]){
                                    $midteamfhArr[$parent['id']]['totalcommission'] = $midteamfhArr[$parent['id']]['totalcommission'] + $totalfenhongmoney;
                                    $midteamfhArr[$parent['id']]['commission'] = $midteamfhArr[$parent['id']]['commission'] + $fenhongcommission;
                                    $midteamfhArr[$parent['id']]['money'] = $midteamfhArr[$parent['id']]['money'] + $fenhongmoney;
                                    $midteamfhArr[$parent['id']]['score'] = $midteamfhArr[$parent['id']]['score'] + $fenhongscore;
                                    $midteamfhArr[$parent['id']]['ogids'][] = $og['id'];
                                    $midteamfhArr[$parent['id']]['levelid'] = $parent['levelid'];
                                    $midteamfhArr[$parent['id']]['downMember'] = $k > 1 ? $parentList[$k-1] : $member;
                                    $midteamfhArr[$parent['id']]['module'] = $og['module'] ?? 'shop';
                                }else{
                                    $midteamfhArr[$parent['id']] = [
                                        'totalcommission'=>$totalfenhongmoney,
                                        'commission'=>$fenhongcommission,
                                        'money'=>$fenhongmoney,
                                        'score'=>$fenhongscore,
                                        'ogids'=>[$og['id']],
                                        'module'=>$og['module'] ?? 'shop',
                                        'levelid' => $parent['levelid'],
                                        'type' => t('团长分红',$aid),
                                        'downMember' => $k > 1 ? $parentList[$k-1] : $member
                                    ];
                                }
                            }
                            //记录path
                            $path_arr[] = [
                                'mid'=>$parent['id'],
                                'levelid'=>$parent['levelid'],
                                'bili'=>$this_teamfenhongbl,
                                'fenhong'=>$totalfenhongmoney,
                                'is_tuanzhang'=>$is_tuanzhang,
                                'pid'=>$parent['pid'],
                                'path'=>$parent['path']
                            ];
                            if($parent['levelid']==$max_levelid){
                                //发到最高级之后就不发了，再发也是级差后就为0了
                                break;
                            }
                        }
                    }
                    if($path_arr){
                        $log = [
                            'aid' => $aid,
                            'bid' => $og['bid']??0,
                            'mid' => $member['id'],
                            'levelid' => $member['levelid'],
                            'path' => json_encode($path_arr),
                            'createtime' => time(),
                            'orderid' => $og['orderid'],
                            'ogid' => $og['id'],
                            'ordernum' => $og['ordernum'],
                            'module' => $og['module'] ?? 'shop',
                            'total_fenhong' => $total_og_fenhong
                        ];
                        Db::name('tuanzhang_fenhong_log')->insert($log);
                    }
                }
                //dump($midteamfhArr);exit;
                if($isyj == 1 && $commissionyj_my > 0){
                    $commissionyj += $commissionyj_my;
                    $og['commission'] = round($commissionyj_my,2);
                    $og['fhname'] = t('团长分红',$aid);
                    $newoglist[] = $og;
                }
                self::fafang($aid,$midteamfhArr,'tuanzhang_fenhong',t('团长分红',$aid),$og['mid']);
                $midteamfhArr = [];
            }
        }

    }

    public static function send($aid,$starttime=0,$endtime=0,$log_ids=[],$fenhong_type=''){
        if($endtime == 0) $endtime = time();
        //Db::startTrans();
        //查询未发放的分红列表
        $map = [];
        $map[] = ['aid','=',$aid];
        $map[] = ['status','=',0];
        if($log_ids){
            $map[] = ['id','in',$log_ids];
        }else{
            $map[] = ['createtime','between',[$starttime,$endtime]];
        }
        if(getcustom('yx_team_yeji_fenhong',$aid)){
            //阶梯分红独立发放时间
            if($fenhong_type){
                $map[] = ['type','=','team_yeji_fenhong'];
            }else{
                $map[] = ['type','<>','team_yeji_fenhong'];
            }
        }
        if(getcustom('area_fenhong_time',$aid)){
            //区域分红独立发放时间
            if($fenhong_type=='areafenhong'){
                $map[] = ['type','=','areafenhong'];
            }else{
                $map[] = ['type','<>','areafenhong'];
            }
        }
        if(getcustom('fenhong_jiaquan_area',$aid) || getcustom('fenhong_jiaquan_gudong',$aid)){
            $map[] = ['jq_sendtime_yj','<=',time()];
        }
        $plate_set = Db::name('sysset')->where('name','webinfo')->find();
        $webinfo = json_decode($plate_set['value'],true);
        if($webinfo['jiesuan_fenhong_type']==1){
            //异步结算时，将该次查询数据发放时间更新为当前，然后通过异步的plantask->jiesuanall方法再查询这些数据进行发放
             $map[] = ['sendtime_yj','=',0];//20250115新增，分红设置支付就结算时会频繁更新这个时间，导致plantask里面一直查不到符合条件的数据
             Db::name('member_fenhonglog')->where($map)->update(['sendtime_yj'=>time()]);
             return true;
        }else{
             $lists = Db::name('member_fenhonglog')->where($map)->select()->toArray();
             self::send_now($aid,$lists);
        }
        //Db::commit();
        return true;
    }
    public static function send_now($aid,$lists=[]){
        $set = Db::name('admin_set')->where('aid',$aid)->find();
        $fxfh_send_wallet = 0;
        $fxfh_send_wallet_levelids = '';
        if(getcustom('commission_send_wallet',$aid) && $set['commission_send_wallet']){
            //定制分销分红发放钱包 0到佣金 1到余额
            $fxfh_send_wallet = $set['commission_send_wallet'];
            $fxfh_send_wallet_levelids = explode(',',$set['commission_send_wallet_levelids']);
            $fxfh_send_wallet_levelids = array_filter($fxfh_send_wallet_levelids);
            if(empty($fxfh_send_wallet_levelids)){
                $fxfh_send_wallet_levelids = ['-1'];
            }
        }
        $moeny_weishu = 2;
        if(getcustom('fenhong_money_weishu',$aid)){
            $moeny_weishu = Db::name('admin_set')->where('aid',$aid)->value('fenhong_money_weishu');
        }
        $moeny_weishu = $moeny_weishu?$moeny_weishu:2;
        $score_weishu = 0;

        $moeny_weishu2 = 2;
        if(getcustom('member_money_weishu',$aid)){
            $moeny_weishu2 = Db::name('admin_set')->where('aid',$aid)->value('member_money_weishu');
        }
        $moeny_weishu2 = $moeny_weishu2?$moeny_weishu2:2;
        if(getcustom('score_weishu',$aid)){
            $score_weishu = Db::name('admin_set')->where('aid',$aid)->value('score_weishu');
            $score_weishu = $score_weishu?$score_weishu:0;
        }
        //股东分红单独结算方式
        $gdfenhong_jiesuantype = 0;
        if( getcustom('gdfenhong_jiesuantype',$aid)){
            //股东分红独立设置结算方式0按正常的会员等级设置发放 1系统设置单独设置统一分红比例不分级别 20241211
            $gdfenhong_jiesuantype = $set['gdfenhong_jiesuantype']?:0;
        }
        if(getcustom('yx_team_yeji_fenhong',$aid)){
            //团队业绩阶梯分红独立结算设置
            $yejifenhong_jiesuantype = Db::name('team_yeji_fenhong_set')->where('aid',$aid)->value('fhjiesuantime_type');
        }
        if(getcustom('area_commission',$aid)){
            //区域分红及区域分红分销要判断是否冻结
            $area_commission_set = Db::name('area_commission_set')->where('aid',$aid)->find();
        }
        if($lists){
            if(getcustom('business_kouchu_fenhong',$aid)){
                //商家扣除分红金额
                $businessKouchus = [];
            }
            $last_levelid = 0;
            $sendinfo = [];
            foreach($lists as $val){
                if(getcustom('area_commission',$aid)){
                    if(($val['type']=='areafenhong' || $val['type']=='areafenhong_commission') && $val['freeze_status']==1){
                        //区域分红及区域分红分销要判断是否冻结
                        if($area_commission_set['pro_num']>0 && !empty($area_commission_set['pro_ids'])){
                            $downmids = Db::name('member')->where('aid', $aid)->where('find_in_set('.$val['mid'].',path) or id='.$val['mid'])->column('id');
                            $pronum = Db::name('shop_order_goods')->where('aid',$aid)->where('status','in','1,2,3')
                                ->where('proid','in',$area_commission_set['pro_ids'])
                                ->where('mid','in',$downmids)->sum('num');
                            if($pronum<$area_commission_set['pro_num']){
                                //不满足条件要继续冻结，不发放
                                continue;
                            }
                        }
                        Db::name('member_fenhonglog')->where('aid',$aid)->where('id',$val['id'])->update(['freeze_status'=>0]);
                    }
                }
                $orderstatus = 3;
                $allowstatus = [1,2,3];
                if($val['ogids']){
                    if(empty($val['module'])){
                        continue;
                    }
                    if(in_array($val['module'],['cashier','maidan','team_yeji_weight_log','business_reward'])){
                        $orderstatus = 3;//不需要发货的订单，默认支付就是已发货
                    }elseif(in_array($val['module'],['shop','restaurant_shop','scoreshop','restaurant_takeaway'])){
                        $orderstatus = Db::name($val['module'].'_order_goods')->where('id',$val['ogids'])->value('status');
                        if($val['module'] =='restaurant_takeaway'){
                            $allowstatus[] = 12;
                        }
                    }elseif($val['module']=='luckycollage' || $val['module']=='lucky_collage'){
                        //luckycollage废弃，替换为lucky_collage 2.6.4
                        //幸运拼团订单由于有失败订单奖励，所以要根据中奖订单状态来判断
                        $order = Db::name('lucky_collage_order')->where('id',$val['ogids'])->find();
                        if($order['buytype']==1){
                            $orderstatus = $order['status'];
                        }else{
                            $teamid = $order['teamid'];
                            $orderstatus = Db::name('lucky_collage_order')->where('teamid',$teamid)->where('iszj',1)->value('status');
                        }
                    }else if(!in_array($val['module'],['member','member_levelup'])){
                        $orderstatus = Db::name($val['module'].'_order')->where('id',$val['ogids'])->value('status');
                    }
                }
                if(!in_array($orderstatus,$allowstatus)){
                    if(getcustom('fenhong_fafang_type',$aid)){
                        if($val['is_examine'])return ['status'=>0,'msg' => '订单状态不符合'];
                    }
                    continue;
                }

                if($val['type']=='fenhong_huiben'){
                    if($set['fhjiesuantime_type_huiben']==0 && $orderstatus!=3){
                        //确认收货后发放
                        if(getcustom('fenhong_fafang_type',$aid)){
                            if($val['is_examine'])return ['status'=>0,'msg' => '确认收货后发放'];
                        }
                        continue;
                    }
                }elseif($val['type']=='team_yeji_fenhong'){
                    //团队业绩阶梯分红独立结算设置
                    if($yejifenhong_jiesuantype==0 && $orderstatus!=3){
                        continue;
                    }
                }else{
                    if($gdfenhong_jiesuantype==1 && $val['type']=='fenhong'){
                        //股东分红单独结算方式
                        if(in_array($val['module'],['shop','restaurant_shop','scoreshop','restaurant_takeaway'])){
                            //TODO (范琦项目中把未完成订单和已完成订单合并一起给结算了，加这个判断过度一下，后期可删掉)
                            $orderstatus_arr = Db::name($val['module'].'_order_goods')->where('id','in',$val['ogids'])->column('status');
                            if(in_array(1,$orderstatus_arr)){
                                $orderstatus = 1;
                            }elseif(in_array(2,$orderstatus_arr)){
                                $orderstatus = 2;
                            }else{
                                $orderstatus = 3;
                            }
                        }
                        if($set['gdfhjiesuantime_type']==0 && $orderstatus!=3){
                            //确认收货后发放
                            if(getcustom('fenhong_fafang_type',$aid)){
                                if($val['is_examine'])return ['status'=>0,'msg' => '确认收货后发放'];
                            }
                            continue;
                        }
                    }
                    if($set['fhjiesuantime_type']==0 && $orderstatus!=3){
                        //确认收货后发放
                        if(getcustom('fenhong_fafang_type',$aid)){
                            if($val['is_examine'])return ['status'=>0,'msg' => '确认收货后发放'];
                        }
                        continue;
                    }
                }

                $send_remark = '';
                if($val['type']=='fenhong'){
                    //股东分红有最大限制
                    $member = Db::name('member')->where('id',$val['mid'])->find();
                    if(getcustom('fenhong_max',$aid) && $member['fenhong_max']>0){
                        $fenhong_max_money = $member['fenhong_max'];
                        if($fenhong_max_money > 0) {
                            $sysset = Db::name('admin_set')->where('aid',$aid)->find();
                            if(getcustom('commission2moneypercent',$aid) && $sysset['commission2moneypercent1'] > 0){
                                //是否是首单
                                $beforeorder = Db::name('shop_order')->where('aid',$aid)->where('mid',$val['mid'])->where('status','in','1,2,3')->where('paytime','<',$val['createtime'])->find();
                                if(!$beforeorder){
                                    $commissionpercent = 1 - $sysset['commission2moneypercent1'] * 0.01;
                                    $moneypercent = $sysset['commission2moneypercent1'] * 0.01;
                                }else{
                                    $commissionpercent = 1 - $sysset['commission2moneypercent2'] * 0.01;
                                    $moneypercent = $sysset['commission2moneypercent2'] * 0.01;
                                }
                            }else{
                                $commissionpercent = 1;
                                $moneypercent = 0;
                            }

                            $newcommission = $val['commission'];
                            if($newcommission + $member['total_fenhong_partner'] > $fenhong_max_money) {
                                $newcommission = $fenhong_max_money - $member['total_fenhong_partner'];
                                $send_remark = '分红金额超过最大限制'.$fenhong_max_money.'，实际分红金额'.$newcommission;
                                $val['send_commission'] = round($newcommission*$commissionpercent,$moeny_weishu);
                                $val['send_money'] = round($newcommission*$moneypercent,2);
                            }
                        }
                    }
                }
                $commission = dd_money_format($val['send_commission'],$moeny_weishu);
                $money = dd_money_format($val['send_money'],$moeny_weishu2);
                $score = dd_money_format($val['send_score'],$score_weishu);
                $fuchi = dd_money_format($val['send_fuchi']);
                if($commission > 0){
                    $levelid = 0;
                    if(getcustom('commission_frozen_level',$aid)){
                        $levelid = $val['levelid']??0;
                    }
                    if(getcustom('teamfenhong_jichamoney',$aid)){
                        //是否进入团队分红级差账户
                        if($set['teamfenhong_jichamoney_status'] && $val['type'] == 'teamfenhong' && $val['isjicha'] == 1){
                            \app\common\Member::addjichamoney($aid,$val['mid'],$commission,t('级差奖励',$aid),$val['frommid']);
                            Db::name('member_fenhonglog')->where('id',$val['id'])->update(['isjicha'=>2]);
                            $commission = 0;
                        }
                    }
                    $fxfh_send_money = 0;
                    if($fxfh_send_wallet==1){
                        //分销分红发放钱包等级限制
                        $member_levelid = Db::name('member')->where('id',$val['mid'])->value('levelid');
                        if(empty($fxfh_send_wallet_levelids) || in_array('-1',$fxfh_send_wallet_levelids) || in_array($member_levelid,$fxfh_send_wallet_levelids)){
                            $fxfh_send_money = 1;
                        }
                    }
                    if($commission > 0){
                        if($fxfh_send_money==1){
                            \app\common\Member::addmoney($aid,$val['mid'],$commission,$val['remark']);
                        }else{
                            \app\common\Member::addcommission($aid,$val['mid'],$val['frommid'],$commission,$val['remark'],1,$val['type'],$levelid,'',$val['id']);
                        }
                    }
                    // 团队分红极差式加速 
                    if((getcustom('yx_cashback_time_teamfenhong_speed',$aid) || getcustom('yx_cashback_time_gdfenhong_speed',$aid)) && $commission>0 && ($val['type']=='teamfenhong' || $val['type']=='fenhong')){

                        if($val['type']=='teamfenhong'){
                            if(!isset($sendinfo[$val['ogids'].'-'.$val['frommid']])){
                                $last_levelid = 0;
                            }
                        }

                        \app\custom\OrderCustom::dealFhspeed($val,$last_levelid);
                        if($val['type']=='teamfenhong'){
                            $sendinfo[$val['ogids'].'-'.$val['frommid']] = $val['levelid'];
                            $last_levelid = $val['levelid'];
                        }
                        
                    }

                    //分红到账通知
                    if(getcustom('fenhong_send_tmpl',$aid)){
                        $member = Db::name('member')->where('aid',$aid)->where('id',$val['mid'])->field('id,nickname,realname')->find();
                        $tmplcontent = [];
                        $tmplcontent['first'] = '分红到账提醒';
                        $tmplcontent['remark'] = '请更加努力！';
                        $tmplcontent['keyword1'] = '';//商品名称
                        $tmplcontent['keyword2'] = $member['nickname']??'';  //兑换用户
                        $tmplcontent['keyword3'] = $commission.'元';  //结算佣金
                        $tmplcontent['keyword4'] = '';  //订单编号
                        $tmplcontent['keyword5'] = date('Y-m-d H:i:s',time());  //结算时间
                        //商品名称和订单编号获取：如果是多个订单一起结算，则统一发一条通知
                        $ogids = implode(',',$val['ogids']);
                        if($ogids){
                            $orderlist = Db::name('shop_order_goods')->alias('og')
                                ->join('shop_order o','o.id=og.orderid')
                                ->where('og.aid',$aid)->where('og.id','in',$ogids)
                                ->field('og.orderid,o.ordernum,o.title')
                                ->group('og.orderid')
                                ->select()->toArray();
                            if($orderlist){
                                if(count($orderlist)>1){
                                    $tmplcontent['keyword1'] = $orderlist[0]['title']; //商品名称
                                    $tmplcontent['keyword4'] = $orderlist[0]['ordernum'].'等';  //订单编号
                                }else{
                                    $tmplcontent['keyword1'] = $orderlist[0]['title']; //商品名称
                                    $tmplcontent['keyword4'] = $orderlist[0]['ordernum'];  //订单编号
                                }
                            }
                        }
                        $rs = \app\common\Wechat::sendtmpl($aid,$val['mid'],'tmpl_fenhong',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    }

                    //分销成功提醒
                    if(getcustom('commission_notice_twice',$aid)){
                        $tmplcontent = [];
                        $tmplcontent['first'] = '恭喜您，成功分销商品获得分红：￥'.$commission;
                        $tmplcontent['remark'] = '点击进入查看~';

                        $orderlist = Db::name('shop_order_goods')
                            ->alias('og')
                            ->join('shop_order o','o.id=og.orderid')
                            ->where('og.aid',$aid)
                            ->where('og.id',$val['ogids'])
                            ->field('og.orderid,o.ordernum,o.title,og.sell_price')
                            ->select()
                            ->toArray();

                        if($orderlist){
                            if(count($orderlist)>1){
                                $tmplcontent['keyword1'] = $orderlist[0]['title']; //商品名称
                            }else{
                                $tmplcontent['keyword1'] = $orderlist[0]['title']; //商品名称
                            }
                            $tmplcontent['keyword2'] = (string) $orderlist[0]['sell_price'];//商品单价
                        }
                        $tmplcontent['keyword3'] = $commission.'元';//商品佣金
                        $tmplcontent['keyword4'] = date('Y-m-d H:i:s',time());//分销时间
                        \app\common\Wechat::sendtmpl($aid,$val['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
                    }
                }
                if($money > 0){
                    \app\common\Member::addmoney($aid,$val['mid'],$money,$val['remark']);
                }

                if($score > 0){
                    \app\common\Member::addscore($aid,$val['mid'],$score,$val['remark']);
                }
                if($fuchi > 0){
                    \app\common\Member::addFuchi($aid,$val['mid'],$val['frommid'],$fuchi,$val['remark']);
                }
                Db::name('member_fenhonglog')->where('aid',$aid)->where('id',$val['id'])->update(['status'=>1,'send_remark'=>$send_remark]);

                if(getcustom('business_kouchu_fenhong',$aid)){
                    //商家扣除分红金额
                    $businessKouchus = self::businessKouchufenhong($aid,$val['id'],$businessKouchus);
                }
            }
            if(getcustom('business_kouchu_fenhong',$aid)){
                if($businessKouchus){
                    //商家扣除分红金额
                    foreach($businessKouchus as $kcv){
                        \app\common\Business::addmoney($kcv['aid'],$kcv['bid'],-$kcv['commission'],$kcv['remark']);
                    }
                    unset($kcv);
                }
            }
        }
        return true;
    }

    /**
     * 会员扫描门店二维支付升级后给门店绑定人分红
     * 详细见文档功能3、4 https://doc.weixin.qq.com/sheet/e3_AV4AYwbFACwhK9lmw4HTpWYpjlp8K?scode=AHMAHgcfAA0dXW7gYuAeYAOQYKALU&tab=BB08J2
     * @param $aid
     * @param $orderid
     * @author: liud
     * @time: 2024/12/27 上午10:47
     */
    public static function mendian_member_levelup_fenhong($aid,$orderid)
    {
        if(getcustom('mendian_member_levelup_fenhong',$aid)){

            $sysset = Db::name('admin_set')->where('aid',$aid)->find();

            if($sysset['mendian_member_levelup_fenhong'] != 1){
                return ['status'=>0,'msg' =>'系统门店扫码升级分红未开启'];
            }

            if(!$order = Db::name('member_levelup_order')->where('aid',$aid)->where('id',$orderid)->find()){
                return ['status'=>0,'msg' =>'升级订单不存在'];
            }

            if(!$mendian = Db::name('mendian')->where('aid',$aid)->where('id',$order['mdid'])->find()){
                return ['status'=>0,'msg' =>'门店信息不存在'];
            }

            if($mendian['status'] != 1){
                return ['status'=>0,'msg' =>'门店已关闭'];
            }

            if($mendian['member_levelup_fenhong'] != 1){
                return ['status'=>0,'msg' =>'门店会员扫码升级分红未开启'];
            }

            if(!$mendian['member_levelup_fenhong_mid']){
                return ['status'=>0,'msg' =>'门店未设置扫码升级分红人'];
            }

            //分红人信息
            if(!$fhinfo = Db::name('member')->where('aid',$aid)->where('id',$mendian['member_levelup_fenhong_mid'])->find()){
                return ['status'=>0,'msg' =>'分红人信息不存在'];
            }

            //计算分红金额
            $commission = 0;
            if($mendian['member_levelup_fenhong_money_type'] == 1){
                //按金额
                $commission = $mendian['member_levelup_fenhong_money'];
            }else{
                //按比例
                $bi = $mendian['member_levelup_fenhong_money'] / 100;
                $commission = round(bcmul ($order['totalprice'],$bi,3),2);
            }

            if($commission > 0){
                $midteamfhArr[$fhinfo['id']] = [
                    'totalcommission'=>$commission,
                    'commission'=>$commission,
                    'module'=>'member_levelup',
                    'levelid' => $fhinfo['levelid'],
                    'type' => 'fenhong',
                    'ogids' => $order['id'],
                ];
                //分红发放
                self::fafang($aid,$midteamfhArr,'fenhong',t('会员').'(ID:'.$order['mid'].')扫门店码升级分红',$order['mdid']);
            }

            return ['status'=>1,'msg' =>'操作成功'];
        }
    }

    /**
     * 区域代理加权分红，每季度一结算
     * 需求文档：https://doc.weixin.qq.com/doc/w3_AT4AYwbFACwvMK9JCBLTLy2fHPYm1?scode=AHMAHgcfAA0ooVbfpTAeYAOQYKALU
     * @author: liud
     * @time: 2025/1/11 下午4:24
     */
    public static function fenhong_jiaquan_area($aid,$stime=0,$etime=0)
    {
        $fenhong_jiaquan_area = getcustom('fenhong_jiaquan_area',$aid);
        if($fenhong_jiaquan_area) {
            $sysset = Db::name('admin_set')->where('aid',$aid)->find();

            //获取上个季度开始时间和结束时间
            $season = ceil((date('n', time()))/3)-1;
            $starttime = mktime(0, 0, 0,$season*3-3+1,1,date('Y'));
            $endtime = mktime(23,59,59,$season*3,date('t',mktime(0, 0 , 0,$season*3,1,date("Y"))),date('Y'));

            if($stime && $etime){
                $starttime = $stime;
                $endtime = $etime;
            }

            if(getcustom('fenhong_business_item_switch',$aid)){
                //查找开启的多商户
                $bids = Db::name('business')->where('aid',$aid)->where('areafenhong_status',1)->column('id');
                $bids = array_merge([0],$bids);
            }
            if(getcustom('maidan_fenhong_new',$aid)){
                $bids_maidan = Db::name('business')->where('maidan_area','>=',1)->column('id');
                $bids_maidan = array_merge([0],$bids_maidan);
            }

            $bwhere = [];
            $bwhere[] = ['og.createtime','between',[$starttime,$endtime]];
            //多商户的商品是否参与分红
            if($sysset['fhjiesuanbusiness'] == 1){
            }else{
                $bwhere[] = ['og.bid','=','0'];
            }
            if(getcustom('fenhong_business_item_switch',$aid)){
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')
                    ->where('og.bid','in',$bids)
                    ->where('og.aid',$aid)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->where('og.is_area_jqfenhong_jd',0)->order('og.id desc')->select()->toArray();
            }else{
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->where('og.is_area_jqfenhong_jd',0)->order('og.id desc')->select()->toArray();
            }
            if(getcustom('yuyue_fenhong',$aid)){
                $yyorderlist = Db::name('yuyue_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($yyorderlist as $k=>$v){
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = $v['cost_price'] ?? 0;
                    $v['module'] = 'yuyue';
                    $oglist[] = $v;
                }
            }
            if(getcustom('scoreshop_fenhong',$aid)){
                $scoreshopoglist = Db::name('scoreshop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.status','in',[1,2,3])->join('scoreshop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($scoreshopoglist as $v){
                    $v['real_totalprice'] = $v['totalmoney'];
                    $v['module'] = 'scoreshop';
                    $oglist[] = $v;
                }
            }
            if(getcustom('luckycollage_fenhong',$aid)){
                $lcorderlist = Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.status','in',[1,2,3])->whereRaw('og.buytype=1 or og.iszj=1')->where('og.isjiqiren',0)->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($lcorderlist as $k=>$v){
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'lucky_collage';
                    $oglist[] = $v;
                }
            }
            if(getcustom('fenhong_times_coupon',$aid)){
                $cwhere[] =['og.status','=',1];
                $cwhere[] =['og.paytime','>=',$starttime];
                $cwhere[] =['og.paytime','<',$endtime];
                if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                    $cwhere[] =['og.bid','=',0];
                }
                $couponorderlist = Db::name('coupon_order')->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where($cwhere)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                foreach($couponorderlist as $k=>$v){
                    $v['name'] = $v['title'];
                    $v['real_totalprice'] = $v['price'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'coupon';
                    $oglist[] = $v;
                }
            }
            if(getcustom('fenhong_kecheng',$aid)){
                //课程直接支付，无区域分红
                $kwhere = [];
                $kwhere[] = ['og.aid','=',$aid];
                $kwhere[] = ['og.status','=',1];
                $kwhere[] = ['og.paytime','>=',$starttime];
                $kwhere[] = ['og.paytime','<',$endtime];
                if($sysset['fhjiesuanbusiness'] != 1){
                    $kwhere[] = ['og.bid','=','0'];
                }
                $kechenglist = Db::name('kecheng_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where($kwhere)
                    ->field('og.*," " as area2,m.nickname,m.headimg')
                    ->select()
                    ->toArray();
                if($kechenglist){
                    foreach($kechenglist as $v){
                        $v['name']            = $v['title'];
                        $v['real_totalprice'] = $v['totalprice'];
                        $v['cost_price']      = 0;
                        $v['module']          = 'kecheng';
                        $v['num']             = 1;
                        $oglist[]             = $v;
                    }
                }
            }
            if(getcustom('maidan_fenhong_new',$aid) && $sysset['maidanfenhong']){
                //买单分红
                $bwhere_maidan = [['og.bid', 'in', $bids_maidan]];
                $maidan_orderlist = Db::name('maidan_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where('og.aid',$aid)
                    ->where('og.status',1)
                    ->where($bwhere_maidan)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                if($maidan_orderlist){
                    foreach($maidan_orderlist as $mdk=>$mdv){
                        $mdv['name']             = $mdv['title'];
                        $mdv['real_totalprice']  = $mdv['paymoney'];
                        //买单分红结算方式
                        if($sysset['maidanfenhong_type'] == 1){
                            //按利润结算时直接把销售额改成利润
                            $mdv['real_totalprice'] = $mdv['paymoney'] - $mdv['cost_price'];
                        }elseif($sysset['maidanfenhong_type'] == 2){
                            //按抽佣计算
                            $mdv['real_totalprice'] = $mdv['order_fee'];
                        }
                        $mdv['cost_price']       = 0;
                        $mdv['num']              = 1;
                        $mdv['module']           = 'maidan';
                        $oglist[] = $mdv;
                    }
                }
            }

            if(!$oglist) return ['commissionyj'=>0,'oglist'=>[]];
            //参与区域代理分红的等级
            $areafhlevellist = Db::name('member_level')->where('aid',$aid)->where('areafenhong','>','0')->where('fenhong_jiaquan_area_jqbl','>','0')->order('sort,id')->column('*','id');
            if(!$areafhlevellist) return ['commissionyj'=>0,'oglist'=>[]];
            if($sysset['areafenhong_jiaquan'] == 1){
                $largearealevelids = Db::name('member_level')->where('aid',$aid)->where('areafenhong','10')->where('areafenhongbl','>',0)->column('id');
                $provincelevelids = Db::name('member_level')->where('aid',$aid)->where('areafenhong','1')->where('areafenhongbl','>',0)->column('id');
                $citylevelids = Db::name('member_level')->where('aid',$aid)->where('areafenhong','2')->where('areafenhongbl','>',0)->column('id');
            }

            $field = 'id,levelid,areafenhong_province,areafenhong_city,areafenhong_area,areafenhongbl,areafenhong';
            if(getcustom('maidan_fenhong_new',$aid)){
                $field.=',areafenhongbl_maidan';
            }
            $memberlist1 = Db::name('member')->field($field)->where('aid',$aid)->where('areafenhong',1)->where('areafenhongbl','>',0)->select()->toArray();
            $memberlist2 = Db::name('member')->field($field)->where('aid',$aid)->where('areafenhong',2)->where('areafenhongbl','>',0)->select()->toArray();
            $memberlist3 = Db::name('member')->field($field)->where('aid',$aid)->where('areafenhong',3)->where('areafenhongbl','>',0)->select()->toArray();
            $field.= ',areafenhong_largearea';
            $memberlist10 = Db::name('member')->field($field)->where('aid',$aid)->where('areafenhong',10)->where('areafenhongbl','>',0)->select()->toArray();

            $areamemberlist = array_merge((array)$memberlist1,(array)$memberlist2,(array)$memberlist3,(array)$memberlist10);
            //其他分组等级
            $member_level_record = Db::name('member_level_record')->field('mid id,levelid,areafenhong_province,areafenhong_city,areafenhong_area,areafenhongbl,areafenhong')->where('aid',$aid)->whereIn('areafenhong',[1,2,3])->where('areafenhongbl','>',0)->select()->toArray();
            $areamemberlist = array_merge((array)$areamemberlist, (array)$member_level_record);

            $isjicha = ($sysset['areafenhong_differential'] == 1 ? true : false);
            $ogids = [];
            $midareafhArr = [];
            $midareafhArr2 = [];
            $businessArr = [];
            $allfenhongprice = 0;
            foreach($oglist as $ogk => $og){
                if(getcustom('maidan_fenhong_new',$aid) && $og['module']=='maidan'){
                    if($og['bid'] > 0 && !in_array($og['bid'],$bids_maidan)){
                        continue;
                    }
                }
                if(getcustom('fenhong_business_item_switch',$aid) && $og['module']!='maidan'){
                    if($og['bid'] > 0 && !in_array($og['bid'],$bids)){
                        continue;
                    }
                }
                if($og['module'] == 'hotel') continue;
                $commissionyj_my = 0;
                if(getcustom('commission2moneypercent',$aid) && $sysset['commission2moneypercent1'] > 0){
                    //是否是首单
                    $beforeorder = Db::name('shop_order')->where('aid',$aid)->where('mid',$og['mid'])->where('status','in','1,2,3')->where('paytime','<',$og['paytime'])->find();
                    if(!$beforeorder){
                        $commissionpercent = 1 - $sysset['commission2moneypercent1'] * 0.01;
                        $moneypercent = $sysset['commission2moneypercent1'] * 0.01;
                    }else{
                        $commissionpercent = 1 - $sysset['commission2moneypercent2'] * 0.01;
                        $moneypercent = $sysset['commission2moneypercent2'] * 0.01;
                    }
                }else{
                    $commissionpercent = 1;
                    $moneypercent = 0;
                }
                if($sysset['fhjiesuantype'] == 0){
                    $fenhongprice = $og['real_totalprice'];
                }else{
                    $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                }
                if(getcustom('yx_buyer_subsidy',$aid)){
                    if($sysset['fhjiesuantype'] == 2){
                        //按抽成计算
                        $fenhongprice = $og['order_fee']??0 ;
                    }
                }
                if(getcustom('baikangxie',$aid)){
                    $fenhongprice = $og['cost_price'] * $og['num'];
                }
                if(getcustom('member_dedamount',$aid)){
                    //如果是商城和买单订单，需要判断商家是否设置了让利
                    if(!$og['module'] || $og['module'] == 'shop' || $og['module'] == 'maidan'){
                        if($og['bid'] && $og['paymoney_givepercent'] && $og['paymoney_givepercent']>0){
                            //重置分红金额为抵扣
                            $fenhongprice = $og['dedamount_dkmoney']??0;
                        }else{
                            //若开启了分销依赖抵扣金，则分红金额为0
                            if($sysset['dedamount_fenxiao'] == 1) $fenhongprice = 0;
                        }
                    }
                }
                if($fenhongprice <= 0) continue;
                $ogids[] = $og['id'];
                $allfenhongprice = $allfenhongprice + $fenhongprice;

                if($og['module'] == 'yuyue'){
                    $product = Db::name('yuyue_product')->where('id',$og['proid'])->find();
                }elseif($og['module'] == 'coupon'){
                    $product = Db::name('coupon')->where('id',$og['cpid'])->find();
                }elseif($og['module'] == 'luckycollage' || $og['module'] == 'lucky_collage'){
                    $product = Db::name('lucky_collage_product')->where('id',$og['proid'])->find();
                    if (getcustom('luckycollage_fail_commission',$aid)) {
                        if ($og['iszj'] == 2) {
                            $product['fenhongset'] = $product['fail_fenhongset'];
                            $product['areafenhongset'] = $product['fail_areafenhongset'];
                            $product['areafenhongdata1'] = $product['fail_areafenhongdata1'];
                            $product['areafenhongdata2'] = $product['fail_areafenhongdata2'];

                        }
                    }
                    if ($product['fenhongset'] == 0) {
                        $product['areafenhongset'] = -1;
                    }

                }elseif($og['module'] == 'scoreshop'){
                    $product = Db::name('scoreshop_product')->where('id',$og['proid'])->find();
                }elseif($og['module'] == 'kecheng'){
                    $product = Db::name('kecheng_list')->where('id',$og['kcid'])->find();
                }elseif($og['module'] == 'maidan'){
                    $product['areafenhongset'] = 0;//按会员等级
                }elseif($og['module'] == 'restaurant_takeaway'){
                    $product['areafenhongset'] = 0;//按会员等级
                }else{
                    $product = Db::name('shop_product')->where('id',$og['proid'])->find();
                }
                $restaurant_fenhong_product_set_custom = getcustom('restaurant_fenhong_product_set',$aid);
                if(getcustom('restaurant_fenhong',$aid)){
                    if($og['module'] == 'restaurant_shop' || $og['module'] == 'restaurant_takeaway'){
                        $product = [];
                        if($restaurant_fenhong_product_set_custom){
                            $product =  Db::name('restaurant_product')->where('id',$og['proid'])->find();
                        }
                    }
                }
                if(getcustom('maidan_fenhong_new',$aid)){
                    if($og['module'] == 'maidan'){
                        $product = [];
                        $product['areafenhongset']   = 0;
                        $product['areafenhongdata1'] = 0;
                        if($og['bid'] > 0){
                            if(isset($businessArr[$og['bid']])){
                                $business_info = $businessArr[$og['bid']];
                            }else{
                                $business_info = Db::name('business')->where('id',$og['bid'])->find();
                                $businessArr[$og['bid']] = $business_info;
                            }
                            $og['area2'] = $business_info['province'].','.$business_info['city'].','.$business_info['district'];
                        }else{
                            $og['area2'] = $sysset['province'].','.$sysset['city'].','.$sysset['district'];
                        }

                        //查询商家买单分红单独设置
                        $business = Db::name('business')->where('id',$og['bid'])->field('maidan_area,maidan_areafenhongdata1')->find();
                        if($business){
                            if($business['maidan_area'] == 2){
                                $product['areafenhongset'] = 1;
                                $product['areafenhongdata1'] = !empty($business['maidan_areafenhongdata1'])?$business['maidan_areafenhongdata1']:[];
                            }else if($business['maidan_area'] == 0){
                                $product['areafenhongset'] = -1;
                            }
                        }
                    }
                }
                if(getcustom('ganer_fenxiao',$aid)){
                    if(empty($og['area2'])){
                        if($og['bid'] > 0){
                            if(isset($businessArr[$og['bid']])){
                                $business_info = $businessArr[$og['bid']];
                            }else{
                                $business_info = Db::name('business')->where('id',$og['bid'])->find();
                                $businessArr[$og['bid']] = $business_info;
                            }
                            $og['area2'] = $business_info['province'].','.$business_info['city'].','.$business_info['district'];
                        }else{
                            $og['area2'] = $sysset['province'].','.$sysset['city'].','.$sysset['district'];
                        }
                    }
                }
                if((getcustom('cashier_area_fenhong',$aid) || getcustom('maidan_area_fenhong',$aid))  && $og['bid']>0){
                    if(isset($businessArr[$og['bid']])){
                        $business_info = $businessArr[$og['bid']];
                    }else{
                        $business_info = Db::name('business')->where('id',$og['bid'])->find();
                        $businessArr[$og['bid']] = $business_info;
                    }
                    if(empty($og['area2'])){
                        $og['area2'] = $business_info['province'].','.$business_info['city'].','.$business_info['district'];
                    }
                }

                $last_areafenhongbl = 0;
                $last_areafenhongmoney = 0;
                $last_areafenhong_score_percent = 0;
                $fenhong_manual_custom = getcustom('fenhong_manual',$aid);
                if(!$fenhong_manual_custom || $og['isfg']==1){
                    //区域代理分红
                    $areaArr = explode(',',$og['area2']);
                    $province = $areaArr[0];
                    $city = $areaArr[1];
                    $area = $areaArr[2];
                    foreach($areafhlevellist as $fhlevel){
                        if($fhlevel['fenhong_jiaquan_area_jqbl'] <= 0){
                            continue;
                        }
                        if($product['areafenhongset'] == 1){ //按比例
                            $fenhongdata = json_decode($product['areafenhongdata1'],true);
                            if($fenhongdata){
                                $fhlevel['areafenhongbl'] = $fenhongdata[$fhlevel['id']]['commission'];
                                $fhlevel['areafenhong_money'] = 0;
                            }
                        }elseif($product['areafenhongset'] == 2){ //按固定金额
                            $fenhongdata = json_decode($product['areafenhongdata2'],true);
                            if($fenhongdata){
                                $fhlevel['areafenhongbl'] = 0;
                                $fhlevel['areafenhong_money'] = $fenhongdata[$fhlevel['id']]['commission'] * $og['num'];
                            }
                        }elseif($product['areafenhongset'] == 4){ //按积分比例
                            $fenhongdata = json_decode($product['areafenhongdata1'],true);
                            if($fenhongdata){
                                $fhlevel['areafenhongbl'] = 0;
                                $fhlevel['areafenhong_money'] = 0;
                                $fhlevel['areafenhong_score_percent'] = $fenhongdata[$fhlevel['id']]['score'];
                            }
                        }elseif($product['areafenhongset'] == -1){
                            $fhlevel['areafenhongbl'] = 0;
                            $fhlevel['areafenhong_money'] = 0;
                        }else{
                            $fhlevel['areafenhong_money'] = 0;
                        }
                        if(getcustom('maidan_fenhong_new',$aid)){
                            if($og['module'] == 'maidan'){
                                if($product['areafenhongset'] == 0){
                                    $fhlevel['areafenhongbl']   = $fhlevel['areafenhongbl_maidan'];
                                }
                            }
                        }

                        if($fhlevel['areafenhongbl']==0 && $fhlevel['areafenhong_money']==0 && $fhlevel['areafenhong_score_percent'] == 0 && $fhlevel['areafenhong_score_percent'] == 0 ) continue;
                        if($fhlevel['areafenhong'] == 3 && $province && $city && $area){
                            $memberlist = Db::name('member')->field('id,levelid,areafenhong_province,areafenhong_city,areafenhong_area,areafenhongbl,areafenhong')->where('levelid',$fhlevel['id'])->where('areafenhong',0)->where('areafenhong_province',$province)->where('areafenhong_city',$city)->where('areafenhong_area',$area)->select()->toArray();
                            if(getcustom('plug_sanyang',$aid)){
                                $memberlist_extend = Db::name('member_level_record')->field('mid id,levelid,areafenhong_province,areafenhongbl,areafenhong')->where('levelid',$fhlevel['id'])->where('areafenhong',0)->where('areafenhong_province',$province)->where('areafenhong_city',$city)->where('areafenhong_area',$area)->select()->toArray();
                            }
                            if($sysset['areafenhong_jiaquan'] == 1 && $largearealevelids){ //大区代理也参与
                                $largeareaList = Db::name('largearea')->where("find_in_set('{$province}',province)")->where('status',1)->column('name');
                                $memberlist10 = Db::name('member')->field('id,levelid,areafenhong_province,areafenhong_city,areafenhong_area,areafenhong_largearea,areafenhongbl,areafenhong')->where('levelid','in',$largearealevelids)->where('areafenhong',0)->where('areafenhong_largearea','in',$largeareaList)->select()->toArray();
                                $memberlist = array_merge((array)$memberlist, (array)$memberlist10);
                            }
                            if($sysset['areafenhong_jiaquan'] == 1 && ($provincelevelids || $citylevelids)){
                                if($provincelevelids){
                                    $memberlist1 = Db::name('member')->field('id,levelid,areafenhong_province,areafenhong_city,areafenhong_area,areafenhongbl,areafenhong')->where('levelid','in',$provincelevelids)->where('areafenhong',0)->where('areafenhong_province',$province)->select()->toArray();
                                }else{
                                    $memberlist1 = [];
                                }
                                if($citylevelids){
                                    $memberlist2 = Db::name('member')->field('id,levelid,areafenhong_province,areafenhong_city,areafenhong_area,areafenhong_province,areafenhong_city,areafenhong_area,areafenhong_province,areafenhong_city,areafenhong_area,areafenhongbl,areafenhong')->where('levelid','in',$citylevelids)->where('areafenhong',0)->where('areafenhong_province',$province)->where('areafenhong_city',$city)->select()->toArray();
                                }else{
                                    $memberlist2 = [];
                                }
                                $memberlist = array_merge((array)$memberlist, (array)$memberlist1, (array)$memberlist2);
                                //Log::write('$memberlist 3');
                                //Log::write($memberlist);
                            }
                        }
                        if($fhlevel['areafenhong'] == 2 && $province && $city){
                            $memberlist = Db::name('member')->field('id,levelid,areafenhong_province,areafenhong_city,areafenhong_area,areafenhongbl,areafenhong')->where('levelid',$fhlevel['id'])->where('areafenhong',0)->where('areafenhong_province',$province)->where('areafenhong_city',$city)->select()->toArray();
                            if(getcustom('plug_sanyang',$aid)){
                                $memberlist_extend = Db::name('member_level_record')->field('mid id,levelid,areafenhong_province,areafenhongbl,areafenhong')->where('levelid',$fhlevel['id'])->where('areafenhong',0)->where('areafenhong_province',$province)->where('areafenhong_city',$city)->select()->toArray();
                            }
                            if($sysset['areafenhong_jiaquan'] == 1 && $largearealevelids){ //大区代理也参与
                                $largeareaList = Db::name('largearea')->where("find_in_set('{$province}',province)")->where('status',1)->column('name');
                                $memberlist10 = Db::name('member')->field('id,levelid,areafenhong_province,areafenhong_city,areafenhong_area,areafenhong_largearea,areafenhongbl,areafenhong')->where('levelid','in',$largearealevelids)->where('areafenhong',0)->where('areafenhong_largearea','in',$largeareaList)->select()->toArray();
                                $memberlist = array_merge((array)$memberlist, (array)$memberlist10);
                            }
                            if($sysset['areafenhong_jiaquan'] == 1 && $provincelevelids){ //省级代理也参与
                                $memberlist1 = Db::name('member')->field('id,levelid,areafenhong_province,areafenhong_city,areafenhong_area,areafenhongbl,areafenhong')->where('levelid','in',$provincelevelids)->where('areafenhong',0)->where('areafenhong_province',$province)->select()->toArray();
                                $memberlist = array_merge((array)$memberlist, (array)$memberlist1);
                                //Log::write('$memberlist 2');
                                //Log::write($memberlist);
                            }
                        }
                        if($fhlevel['areafenhong'] == 1 && $province){
                            $memberlist = Db::name('member')->field('id,levelid,areafenhong_province,areafenhong_city,areafenhong_area,areafenhongbl,areafenhong')->where('levelid',$fhlevel['id'])->where('areafenhong',0)->where('areafenhong_province',$province)->select()->toArray();
                            if(getcustom('plug_sanyang',$aid))
                                $memberlist_extend = Db::name('member_level_record')->field('mid id,levelid,areafenhong_province,areafenhongbl,areafenhong')->where('levelid',$fhlevel['id'])->where('areafenhong',0)->where('areafenhong_province',$province)->select()->toArray();

                            if($sysset['areafenhong_jiaquan'] == 1 && $largearealevelids){ //大区代理也参与
                                $largeareaList = Db::name('largearea')->where("find_in_set('{$province}',province)")->where('status',1)->column('name');
                                $memberlist10 = Db::name('member')->field('id,levelid,areafenhong_province,areafenhong_city,areafenhong_area,areafenhong_largearea,areafenhongbl,areafenhong')->where('levelid','in',$largearealevelids)->where('areafenhong',0)->where('areafenhong_largearea','in',$largeareaList)->select()->toArray();
                                $memberlist = array_merge((array)$memberlist, (array)$memberlist10);
                            }
                        }
                        if($fhlevel['areafenhong'] == 10 && $province){
                            $largeareaList = Db::name('largearea')->where("find_in_set('{$province}',province)")->where('status',1)->column('name');
                            $memberlist = Db::name('member')->field('id,levelid,areafenhong_province,areafenhong_city,areafenhong_area,areafenhong_largearea,areafenhongbl,areafenhong')->where('levelid',$fhlevel['id'])->where('areafenhong',0)->where('areafenhong_largearea','in',$largeareaList)->select()->toArray();
                        }
                        if(getcustom('plug_sanyang',$aid)){
                            $memberlist = array_merge((array)$memberlist, (array)$memberlist_extend);
                        }

                        //要分红得用户
                        if($memberlist){

                            $this_areafenhongbl = $fhlevel['areafenhongbl'];
                            $this_areafenhong_score_percent = $fhlevel['areafenhong_score_percent'];
                            if(($this_areafenhongbl > 0 || $this_areafenhong_score_percent > 0) && $isjicha){
                                $this_areafenhongbl = $fhlevel['areafenhongbl'] - $last_areafenhongbl;
                                $this_areafenhong_score_percent = $fhlevel['areafenhong_score_percent'] - $last_areafenhong_score_percent;
                            }
                            $last_areafenhongbl = $last_areafenhongbl + $this_areafenhongbl;
                            $last_areafenhong_score_percent = $last_areafenhong_score_percent + $this_areafenhong_score_percent;

                            $areafenhong_money = $fhlevel['areafenhong_money'];
                            if($fhlevel['areafenhong_money'] > 0 && $isjicha){
                                $areafenhong_money = $fhlevel['areafenhong_money'] - $last_areafenhongmoney;
                            }
                            $last_areafenhongmoney = $last_areafenhongmoney + $areafenhong_money;

                            foreach($memberlist as $mv){

                                //获取个人贡献值
                                $gxz = self::getareafenhonggxz($aid,$mv['id'],$starttime,$endtime);

                                //区域代理加权平均分红
                                $fenhong_jiaquan_area_jqbl = $fhlevel['fenhong_jiaquan_area_jqbl'] / 100;
                                $ys_commission = ($this_areafenhongbl * $fenhongprice * 0.01 + $areafenhong_money) * $fenhong_jiaquan_area_jqbl;

                                //个人区域代理加权分红奖励 = 区域代理加权分红的资金池x个人贡献值
                                $commission = round(bcmul($ys_commission,$gxz['gxz'],3),2);

                                if($commission <= 0 ) continue;
                                //Log::write('$commission');
                                //Log::write($commission);
                                if(getcustom('fenhong_removefenxiao',$aid) && $fhlevel['areafenhong_removefenxiao'] == 1){
                                    if($og['parent1'] && $og['parent1commission']){
                                        $commission = $commission - $og['parent1commission'];
                                    }
                                    if($og['parent2'] && $og['parent2commission']){
                                        $commission = $commission - $og['parent2commission'];
                                    }
                                    if($og['parent3'] && $og['parent3commission']){
                                        $commission = $commission - $og['parent3commission'];
                                    }
                                    if($commission <= 0) continue;
                                }
                                $commission_score = 0;
                                if($commissionpercent != 1){
                                    $fenhongcommission = round($commission*$commissionpercent,2);
                                    $fenhongmoney = round($commission*$moneypercent,2);
                                    $fenhongscore = round($commission_score*$commissionpercent);
                                }else{
                                    $fenhongcommission = $commission;
                                    $fenhongmoney = 0;
                                    $fenhongscore = $commission_score;
                                }

                                $mid = $mv['id'];
                                $midareafhArr[$mid] = [
                                    'totalcommission'=>$commission,
                                    'commission'=>$fenhongcommission,
                                    'money'=>$fenhongmoney,
                                    'score'=>$fenhongscore,
                                    'ogids'=>[$og['id']],
                                    'module'=>$og['module'] ?? 'shop',
                                    'jq_send_info'=>'原始分红金额：'.$ys_commission.',贡献值：'.$gxz['gxz'].',个人区域代理团队业绩：'.$gxz['z_team_areayeji'].',同区域代理业绩总和：'.$gxz['z_tong_team_areayeji'],
                                ];

                                //记录贡献值
                                $gxzremark = '贡献值：'.$gxz['gxz'].',个人区域代理团队业绩：'.$gxz['z_team_areayeji'].',同区域代理业绩总和：'.$gxz['z_tong_team_areayeji'];
                                self::savefenhongjiaquangxz($aid,$mid,$gxz['gxz'],$starttime,$endtime,1,$gxzremark);
                            }
                        }
                    }
                }

                self::fafang($aid,$midareafhArr,'areafenhong_jiaquan',t('区域代理加权分红',$aid));
                $midareafhArr = [];
                //订单商品表记录已发放
                Db::name('shop_order_goods')->where('aid',$aid)->where('id',$og['id'])->update(['is_area_jqfenhong_jd' =>1]);
            }

            //加权分红结算完立即发放
            $log_ids = Db::name('member_fenhonglog')->where('aid',$aid)->where('status',0)->where('type','areafenhong_jiaquan')->column('id');
            \app\common\Fenhong::send($aid,0,0,$log_ids);
        }
    }

    /**
     * 获取区域代理加权分红贡献值
     * @author: liud
     * @time: 2025/1/14 上午9:32
     */
    public static function getareafenhonggxz($aid,$mid,$starttime,$endtime){
        if(getcustom('fenhong_jiaquan_area',$aid)){

            if(!$mv = Db::name('member')->where('id',$mid)->where('aid',$aid)->field('id,levelid,areafenhong,areafenhong_province,areafenhong_city,areafenhong_area')->find()){
                return ['gxz' => 0];
            }

            //获取代理类型
            $mv['areafenhong_type'] = Db::name('member_level')->where('id',$mv['levelid'])->where('aid',$aid)->value('areafenhong');

            //个人区域代理业绩
            $mv['self_areayeji'] = self::getSelfAreaYeji($aid,$mv,$starttime,$endtime,$mv['areafenhong_province'],$mv['areafenhong_city'],$mv['areafenhong_area'],1);

            /*************个人区域代理团队业绩************************/
            //获取直推团队人员
            $downmember = \app\common\Member::getteammids($aid,$mv['id'],999);

            //直推团队业绩。
            $mv['team_areayeji'] = 0;
            foreach($downmember as $k2 => $v2){
                //个人区域业绩
                $ztye = self::getSelfAreaYeji($aid,$v2,$starttime,$endtime,$mv['areafenhong_province'],$mv['areafenhong_city'],$mv['areafenhong_area']);

                $mv['team_areayeji'] += $ztye;
            }

            //个人区域代理团队业绩=个人区域代理业绩+直推团队区域代理业绩。
            $mv['z_team_areayeji'] = $mv['self_areayeji'] + $mv['team_areayeji'];

            /*************同区域代理业绩总和************************/
            //这个区域下面的所有代理团队业绩总和
            $mv['z_tong_team_areayeji'] = 0;
            //获取这个区域下面所有的代理
            if($getareamarr = self::getareamarr($aid,$mv['areafenhong_type'],$mv['areafenhong_province'],$mv['areafenhong_city'],$mv['areafenhong_area'])){

                foreach ($getareamarr as $v4) {

                    //个人区域代理业绩
                    if($xhgr = Db::name('member')->where('id',$v4)->where('aid',$aid)->field('id,levelid,areafenhong,areafenhong_province,areafenhong_city,areafenhong_area')->find()){
                        //获取代理类型
                        $xhgr['areafenhong_type'] = Db::name('member_level')->where('id',$xhgr['levelid'])->where('aid',$aid)->value('areafenhong');
                        $gryj_pp = self::getSelfAreaYeji($aid,$xhgr,$starttime,$endtime,$mv['areafenhong_province'],$mv['areafenhong_city'],$mv['areafenhong_area'],1);

                        //获取同区域直推和间推区域代理团队人员
                        //$t_downmids = \app\common\Member::getteamareamarr($aid,$v4,999,[],[],0,$mv['areafenhong_type'],$mv['areafenhong_province'],$mv['areafenhong_city'],$mv['areafenhong_area']);
                        //获取直推团队人员
                        $t_downmids = \app\common\Member::getteammids($aid,$v4,999);

                        //团队区域代理业绩
                        $ztye_pp = 0;
                        foreach($t_downmids as $k3 => $v3){
                            //个人区域代理业绩
                            $ztye = self::getSelfAreaYeji($aid,$v3,$starttime,$endtime,$mv['areafenhong_province'],$mv['areafenhong_city'],$mv['areafenhong_area']);

                            $ztye_pp += $ztye;
                        }

                        //这个区域下面的所有代理团队业绩总和
                        $mv['z_tong_team_areayeji'] += $gryj_pp + $ztye_pp;
                    }
                }
            }

            //个人贡献值=个人区域代理团队业绩/同区域代理业绩总和
            $gxz = round(bcdiv($mv['z_team_areayeji'],$mv['z_tong_team_areayeji'],5),4);

            return ['gxz' => $gxz ?? 0,'z_team_areayeji' => $mv['z_team_areayeji'],'z_tong_team_areayeji' => $mv['z_tong_team_areayeji']];
        }
    }

    /**
     * 获取个人区域代理业绩
     * @author: liud
     * @time: 2025/2/19 下午5:25
     */
    public static function getSelfAreaYeji($aid,$v2,$starttime,$endtime,$areafenhong_province='',$areafenhong_city='',$areafenhong_area='',$is_dl=0)
    {
        if(!$v2){
            return 0;
        }

        $team_yjwhere = [];
        $mid = 0;

        if($is_dl == 1){//必须是代理
            $mid = $v2['id'];
            if($v2['areafenhong_type'] == 1 && $areafenhong_province){
                $team_yjwhere[] = ['o.area2','like','%'.$areafenhong_province.'%'];
            }else if($v2['areafenhong_type'] == 2 && $areafenhong_province && $areafenhong_city){
                $team_yjwhere[] = ['o.area2','like','%'.$areafenhong_province.','.$areafenhong_city.'%'];
            }else if($v2['areafenhong_type'] == 3 && $areafenhong_province && $areafenhong_city && $areafenhong_area){
                $team_yjwhere[] = ['o.area2','like','%'.$areafenhong_province.','.$areafenhong_city.','.$areafenhong_area.'%'];
            }
        }else{//无需是代理
            $mid = $v2;
            if($areafenhong_province){
                $team_yjwhere[] = ['o.area2','like','%'.$areafenhong_province.'%'];
            }else if($areafenhong_province && $areafenhong_city){
                $team_yjwhere[] = ['o.area2','like','%'.$areafenhong_province.','.$areafenhong_city.'%'];
            }else if($areafenhong_province && $areafenhong_city && $areafenhong_area){
                $team_yjwhere[] = ['o.area2','like','%'.$areafenhong_province.','.$areafenhong_city.','.$areafenhong_area.'%'];
            }
        }

        if($team_yjwhere){
            $ztye = Db::name('shop_order_goods')->alias('og')
                ->where('og.aid',$aid)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->where($team_yjwhere)->where('og.createtime','between',[$starttime,$endtime])->where('og.mid',$mid)->sum('og.real_totalprice');
        }

        return $ztye ?? 0;
    }
    /**
     * 获取区域下面所有代理
     * @author: liud
     * @time: 2025/1/13 下午6:05
     */
    public static function getareamarr($aid,$areatype=0,$province='',$city='',$area=''){
        if(getcustom('fenhong_jiaquan_area',$aid)) {
            $areamids = [];
            $where = [];
            $where[] = ['m.aid','=',$aid];
            $where[] = ['m.areafenhong','<',4];
            $dowmids = Db::name('member')->alias('m')
                ->leftJoin('member_level l','l.id = m.levelid')
                ->field('m.id,m.areafenhong as m_areafenhong,m.areafenhong_province,m.areafenhong_city,m.areafenhong_area,l.areafenhong')
                ->where($where)
                ->select()->toArray();
            if($dowmids){
                foreach($dowmids as $downmid){
                    if($downmid['m_areafenhong'] > 0 || $downmid['areafenhong'] > 0){
                        $areafenhong = $downmid['m_areafenhong'];
                        if($downmid['m_areafenhong'] == 0){
                            $areafenhong = $downmid['areafenhong'];
                        }
                        if($areatype > 0){
                            //同区域判断
                            $istqu = false;
                            if($areatype == $areafenhong && $downmid['areafenhong_province'] == $province){
                                $istqu = true;
                            }elseif($areatype == $areafenhong && $downmid['areafenhong_province'] == $province && $downmid['areafenhong_city'] == $city){
                                $istqu = true;
                            }elseif($areatype == $areafenhong && $downmid['areafenhong_province'] == $province && $downmid['areafenhong_city'] == $city && $downmid['areafenhong_area'] == $area){
                                $istqu = true;
                            }
                            if($istqu){
                                $areamids[] = $downmid['id'];
                            }
                        }
                    }
                }
            }
            return $areamids;
        }
    }

    /**
     * 区域代理分红直推平级奖（给区域代理的直推上级发）
     * 需求文档：https://doc.weixin.qq.com/doc/w3_AT4AYwbFACwvMK9JCBLTLy2fHPYm1?scode=AHMAHgcfAA0ooVbfpTAeYAOQYKALU
     * @author: liud
     * @time: 2025/1/11 下午4:24
     */
    public static function fenhong_area_zhitui_pingji($aid,$stime,$etime)
    {
        $fenhong_area_zhitui_pingji = getcustom('fenhong_area_zhitui_pingji', $aid);
        if ($fenhong_area_zhitui_pingji) {

            //获取上个季度开始时间和结束时间
            $season = ceil((date('n', time()))/3)-1;
            $starttime = mktime(0, 0, 0,$season*3-3+1,1,date('Y'));
            $endtime = mktime(23,59,59,$season*3,date('t',mktime(0, 0 , 0,$season*3,1,date("Y"))),date('Y'));

            if($stime && $etime){
                $starttime = $stime;
                $endtime = $etime;
            }

            $where = [];
            $where[] = ['m.aid','=',$aid];
            $where[] = ['m.areafenhong','<',4];
            $where[] = ['m.pid','>',0];
            $dowmids = Db::name('member')->alias('m')
                ->leftJoin('member_level l','l.id = m.levelid')
                ->field('m.id,m.levelid,m.pid,m.areafenhong as m_areafenhong,m.areafenhong_province,m.areafenhong_city,m.areafenhong_area,l.areafenhong')
                ->where($where)
                ->select()->toArray();
            $afangArr = [];
            $midareafhArr = [];
            if($dowmids){
                foreach($dowmids as $downmid){
                    if($downmid['m_areafenhong'] > 0 || $downmid['areafenhong'] > 0){
                        //上级信息
                        $p_info = Db::name('member')->alias('m')
                            ->leftJoin('member_level l','l.id = m.levelid')
                            ->field('m.id,m.levelid,m.areafenhong as m_areafenhong,m.areafenhong_province,m.areafenhong_city,m.areafenhong_area,l.areafenhong,l.fenhong_area_zhitui_pingjibl')
                            ->where('m.aid',$aid)
                            ->where('m.id',$downmid['pid'])
                            ->find();

                        if(!$p_info || ($p_info['m_areafenhong'] == 0 && $p_info['areafenhong'] == 0) || ($p_info['levelid'] != $downmid['levelid']) || ($p_info['fenhong_area_zhitui_pingjibl'] <= 0)){
                            continue;
                        }

                        //区域代理分红直推平级奖比例
                        $p_zhitui_pingjibl = $p_info['fenhong_area_zhitui_pingjibl'] / 100;

                        //区域代理产生的分红收入
                        $area_fenhong = Db::name('member_fenhonglog')->where('aid',$aid)->where('mid',$downmid['id'])->where('status',1)->where('createtime','between',[$starttime,$endtime])->where('type','like','areafenhong%')->sum('commission');

                        //分销体系产生的直推和间推收入总和
                        $area_commission = Db::name('member_commission_record')->where('aid',$aid)->where('mid',$downmid['id'])->where('status',1)->where('createtime','between',[$starttime,$endtime])->whereRaw('remark LIKE "下级%" or remark LIKE "下二级%"')->sum('commission');

                        //收入统计
                        $shouru = round(bcadd($area_fenhong,$area_commission,3),2);

                        //区域代理分红直推平级奖
                        $zhitui_pingji = round(bcmul($shouru,$p_zhitui_pingjibl,3),2);
                        if($zhitui_pingji <= 0){
                            continue;
                        }

                        $midareafhArr[$p_info['id']] = [
                            'totalcommission'=>$zhitui_pingji,
                            'commission'=>$zhitui_pingji,
                            'frommid'=>$downmid['id'],
                            'module'=>'shop',
                            'levelid'=>$p_info['levelid'],
                            'jq_send_info'=>'下级ID:'.$downmid['id'].' 区域代理分红收入：'.$area_fenhong.' 分销体系收入：'.$area_commission.' 发奖比例：'.$p_info['fenhong_area_zhitui_pingjibl'],
                        ];
                        $afangArr[] = $midareafhArr;
                    }
                }
            }

            if($afangArr){
                foreach ($afangArr as $v){
                    //发放
                    self::fafang($aid,$v,'areafenhong_zhitui_pj',t('区域代理分红直推平级奖',$aid));
                }
            }

            //区域代理分红直推平级奖结算完立即发放
            $log_ids = Db::name('member_fenhonglog')->where('aid',$aid)->where('status',0)->where('type','areafenhong_zhitui_pj')->column('id');
            \app\common\Fenhong::send($aid,0,0,$log_ids);
        }
    }

    /**
     * 股东加权分红，每季度一结算
     * 需求文档：https://doc.weixin.qq.com/doc/w3_AT4AYwbFACwvMK9JCBLTLy2fHPYm1?scode=AHMAHgcfAA0ooVbfpTAeYAOQYKALU
     * @author: liud
     * @time: 2025/1/11 下午4:24
     */
    public static function fenhong_jiaquan_gudong($aid,$stime=0,$etime=0)
    {
        $fenhong_jiaquan_gudong = getcustom('fenhong_jiaquan_gudong',$aid);
        if($fenhong_jiaquan_gudong) {
            $sysset = Db::name('admin_set')->where('aid',$aid)->find();

            //获取上个季度开始时间和结束时间
            $season = ceil((date('n', time()))/3)-1;
            $starttime = mktime(0, 0, 0,$season*3-3+1,1,date('Y'));
            $endtime = mktime(23,59,59,$season*3,date('t',mktime(0, 0 , 0,$season*3,1,date("Y"))),date('Y'));

            if($stime && $etime){
                $starttime = $stime;
                $endtime = $etime;
            }

            $gdfenhong_jiesuantype = 0;
            $isyj = 0;
            if(getcustom('fenhong_business_item_switch',$aid)){
                //查找开启的多商户
                $bids = Db::name('business')->where('aid',$aid)->where('gdfenhong_status',1)->column('id');
                $bids = array_merge([0],$bids);
            }
            if(getcustom('maidan_fenhong_new',$aid)){
                $bids_maidan = Db::name('business')->where('maidan_gudong','>=',1)->column('id');
                $bids_maidan = array_merge([0],$bids_maidan);
            }
            //是否开启股东分红叠加
            $gdfenhong_add = 0;
            if(getcustom('gdfenhong_add',$aid) && $sysset['gdfenhong_add'] && empty($sysset['partner_jiaquan'])){
                //与股东加权分红冲突，如果开启了加权分红这里失效
                $gdfenhong_add = 1;
            }

            $bwhere = [];
            $bwhere[] = ['og.createtime','between',[$starttime,$endtime]];
            //$bwhere[] = ['og.id','=',10648];
            //多商户的商品是否参与分红
            if($sysset['fhjiesuanbusiness'] == 1){
            }else{
                $bwhere[] = ['og.bid','=','0'];
            }
            if(getcustom('fenhong_business_item_switch',$aid)){
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')
                    ->where('og.bid','in',$bids)
                    ->where('og.aid',$aid)->where('og.is_gd_jqfenhong_jd',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
            }else{
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.is_gd_jqfenhong_jd',0)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
            }

            if(getcustom('yuyue_fenhong',$aid)){
                $yyorderlist = Db::name('yuyue_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($yyorderlist as $k=>$v){
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = $v['cost_price'] ?? 0;
                    $v['module'] = 'yuyue';
                    $oglist[] = $v;
                }
            }
            if(getcustom('scoreshop_fenhong',$aid)){
                $scoreshopoglist = Db::name('scoreshop_order_goods')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($scoreshopoglist as $v){
                    $v['real_totalprice'] = $v['totalmoney'];
                    $v['module'] = 'scoreshop';
                    $oglist[] = $v;
                }
            }
            if(getcustom('luckycollage_fenhong',$aid)){
                $lcorderlist = Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('member m','m.id=og.mid')->where($bwhere)->whereRaw('og.buytype=1 or og.iszj=1')->where('og.isjiqiren',0)->order('og.id desc')->select()->toArray();
                foreach($lcorderlist as $k=>$v){
                    $v['name'] = $v['proname'];
                    $v['real_totalprice'] = $v['totalprice'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'lucky_collage';
                    $oglist[] = $v;
                }
            }
            if(getcustom('maidan_fenhong',$aid) && !getcustom('maidan_fenhong_new',$aid)){
                //买单分红
                $maidan_orderlist = Db::name('maidan_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where('og.aid',$aid)
                    ->where('og.isfenhong',0)
                    ->where('og.status',1)
                    ->where($bwhere)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                if($maidan_orderlist){
                    foreach($maidan_orderlist as $mdk=>$mdv){
                        $mdv['proid']            = 0;
                        $mdv['name']             = $mdv['title'];
                        $mdv['real_totalprice']  = $mdv['paymoney'];
                        $mdv['cost_price']       = 0;
                        $mdv['num']              = 1;
                        $mdv['module']           = 'maidan';
                        $oglist[] = $mdv;
                    }
                    unset($mdv);
                }
            }
            if(getcustom('maidan_fenhong_new',$aid) && $sysset['maidanfenhong']){
                //买单分红
                $bwhere_maidan = [['og.bid', 'in', $bids_maidan]];
                $maidan_orderlist = Db::name('maidan_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where('og.aid',$aid)
                    ->where('og.isfenhong',0)
                    ->where('og.status',1)
                    ->where($bwhere_maidan)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                if($maidan_orderlist){
                    foreach($maidan_orderlist as $mdk=>$mdv){
                        $mdv['name']             = $mdv['title'];
                        $mdv['real_totalprice']  = $mdv['paymoney'];
                        //买单分红结算方式
                        if($sysset['maidanfenhong_type'] == 1){
                            //按利润结算时直接把销售额改成利润
                            $mdv['real_totalprice'] = $mdv['paymoney'] - $mdv['cost_price'];
                        }elseif($sysset['maidanfenhong_type'] == 2){
                            //按抽佣计算
                            $mdv['real_totalprice'] = $mdv['order_fee'];
                        }
                        $mdv['cost_price']       = 0;
                        $mdv['num']              = 1;
                        $mdv['module']           = 'maidan';
                        $oglist[] = $mdv;
                    }
                }
            }
            if(getcustom('restaurant_fenhong',$aid) && $sysset['restaurant_fenhong_status']){
                //点餐
                $diancan_oglist = Db::name('restaurant_shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('restaurant_shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                if($diancan_oglist){
                    foreach($diancan_oglist as $dck=>$dcv){
                        $dcv['module'] = 'restaurant_shop';
                        $oglist[]      = $dcv;
                    }
                    unset($dcv);
                }
                //外卖
                $takeaway_oglist = Db::name('restaurant_takeaway_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[1,2,3])->join('restaurant_takeaway_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                if($takeaway_oglist){
                    foreach($takeaway_oglist as $twk=>$twv){
                        $twv['module'] = 'restaurant_takeaway';
                        $oglist[]      = $twv;
                    }
                    unset($twv);
                }
            }
            if(getcustom('fenhong_times_coupon',$aid)){
                $cwhere[] =['og.isfenhong','=',0];
                $cwhere[] =['og.status','=',1];
                $cwhere[] =['og.paytime','>=',$starttime];
                $cwhere[] =['og.paytime','<',$endtime];
                if($sysset['fhjiesuanbusiness'] != 1){ //多商户的商品是否参与分红
                    $cwhere[] =['og.bid','=',0];
                }
                $couponorderlist = Db::name('coupon_order')->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where($cwhere)
                    ->field('og.*,m.nickname,m.headimg')
                    ->order('og.id desc')
                    ->select()
                    ->toArray();
                foreach($couponorderlist as $k=>$v){
                    $v['name'] = $v['title'];
                    $v['real_totalprice'] = $v['price'];
                    $v['cost_price'] = 0;
                    $v['module'] = 'coupon';
                    $v['num'] = 1;
                    $oglist[] = $v;
                }
            }
            if(getcustom('fenhong_kecheng',$aid)){
                $kwhere = [];
                $kwhere[] = ['og.aid','=',$aid];
                $kwhere[] = ['og.isfenhong','=',0];
                $kwhere[] = ['og.status','=',1];
                $kwhere[] = ['og.paytime','>=',$starttime];
                $kwhere[] = ['og.paytime','<',$endtime];
                if($sysset['fhjiesuanbusiness'] != 1){
                    $kwhere[] = ['og.bid','=','0'];
                }
                $kechenglist = Db::name('kecheng_order')
                    ->alias('og')
                    ->join('member m','m.id=og.mid')
                    ->where($kwhere)
                    ->field('og.*," " as area2,m.nickname,m.headimg')
                    ->select()
                    ->toArray();
                if($kechenglist){
                    foreach($kechenglist as $v){
                        $v['name']            = $v['title'];
                        $v['real_totalprice'] = $v['totalprice'];
                        $v['cost_price']      = 0;
                        $v['module']          = 'kecheng';
                        $v['num']             = 1;
                        $oglist[]             = $v;
                    }
                }
            }
            if(getcustom('hotel',$aid)){
                $hotelorderlist = Db::name('hotel_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where('og.aid',$aid)->where('og.isfenhong',0)->where('og.status','in',[2,3,4])->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                foreach($hotelorderlist as $k=>$v){
                    $v['name'] = $v['title'];
                    $v['real_totalprice'] = $v['sell_price'];
                    $v['cost_price'] = $v['cost_price'] ?? 0;
                    $v['module'] = 'hotel';
                    $oglist[] = $v;
                }
            }

            if(getcustom('gdfenhong_level',$aid)){
                //升级费用用于股东分红
                $gd_levelids = Db::name('member_level')->where('aid',$aid)->where('apply_paygudong',1)->column('id');
                if($gd_levelids){
                    $level_orders = Db::name('member_levelup_order')
                        ->where('aid',$aid)
                        ->where('isfenhong',0)
                        ->where('status',2)
                        ->where('totalprice','>',0)
                        ->whereIn('levelid',$gd_levelids)->select()->toArray();
                    if($level_orders){
                        foreach($level_orders as $v){
                            $v['name']            = $v['title'];
                            $v['real_totalprice'] = $v['totalprice'];
                            $v['cost_price']      = 0;
                            $v['module']          = 'member_levelup';
                            $v['num']             = 1;
                            $oglist[]             = $v;
                        }
                    }
                }

            }

            if(getcustom('fenhong_cashier_order',$aid) && $sysset['fenhong_cashier_order_money']){

                //收银台订单
                $cowhere = [];
                $cowhere[] = ['og.aid','=',$aid];
                $cowhere[] = ['og.bid','=',0];
                $cowhere[] = ['og.isfenhong','=',0];
                $cowhere[] = ['o.status','=',1];
                if($starttime) $cowhere[] = ['o.paytime','>=',$starttime];
                if($endtime) $cowhere[] = ['o.paytime','<',$endtime];
                $cashier_order_list = Db::name('cashier_order_goods')
                    ->alias('og')
                    // ->join('member m','m.id=og.mid')
                    ->join('cashier_order o','o.id=og.orderid')
                    ->where($cowhere)
                    ->field('og.*')
                    ->select()
                    ->toArray();
                if($cashier_order_list){
                    // 0商品价格1成交价格2按销售利润
                    foreach($cashier_order_list as $v){
                        if($sysset['fxjiesuantype'] == 0){
                            $v['real_totalprice'] = $v['totalprice'];
                        }
                        $v['name']            = $v['proname'];
                        $v['module']          = 'cashier';
                        $oglist[]             = $v;
                        Db::name('cashier_order_goods')->where('id',$v['id'])->update(['isfenhong'=>1]);
                    }
                }
            }

            if(!$oglist) return ['commissionyj'=>0,'oglist'=>[]];
            //参与股东分红的等级
            if($gdfenhong_jiesuantype){
                //独立设置结算方式
                $where_level = [];
                $where_level[] = ['id','in',$sysset['gdfh_levelids']];
                $fhlevellist = Db::name('member_level')->where('aid',$aid)->where($where_level)->order('sort desc,id desc')->column('*','id');
            }else{
                $where_level = [];
                $where_level[] = ['fenhong','>',0];
                if(getcustom('maidan_fenhong_new',$aid)){
                    $where_level = [];
                    $where_level[] = ['fenhong|fenhong_maidan_percent','>',0];
                }
                $fhlevellist = Db::name('member_level')->where('aid',$aid)->where($where_level)->order('sort desc,id desc')->column('*','id');
            }
            if(!$fhlevellist) return ['commissionyj'=>0,'oglist'=>[]];

            if(getcustom('business_reward_member',$aid)){
                //商家打赏订单
                $business_reward_set =Db::name('business_reward_set')->where('aid',$aid)->find();
            }
            //股东最大分红累加低级别的分红上限参数
            if(getcustom('fenhong_max',$aid) && !empty($sysset['fenhong_max_add'])){
                foreach($fhlevellist as $k=>$v){
                    $fenhong_max = Db::name('member_level')
                        ->where('aid',$aid)
                        ->where('sort','<',$v['sort'])
                        ->sum('fenhong_max_money');
                    $fhlevellist[$k]['fenhong_max_money'] = bcadd($v['fenhong_max_money'],$fenhong_max,2);
                }
            }

            $defaultCid = Db::name('member_level_category')->where('aid',$aid)->where('isdefault', 1)->value('id');

            $ogids = [];
            $midfhArr = [];
            $newoglist = [];
            $commissionyj = 0;
            $allfenhongprice = 0;
            foreach($oglist as $og){
                if(getcustom('maidan_fenhong_new',$aid) && $og['module']=='maidan'){
                    if($og['bid'] > 0 && !in_array($og['bid'],$bids_maidan)){
                        continue;
                    }
                }
                if(getcustom('fenhong_business_item_switch',$aid) && $og['module']!='maidan'){
                    if($og['bid'] > 0 && !in_array($og['bid'],$bids)){
                        continue;
                    }
                }
                $levelid_only = 0;
                if(getcustom('partner_parent_only',$aid)){
                    //股东分红仅奖励购买人上级等级
                    if($sysset['partner_parent_only']){
                        $levelid_only = -1;
                        $pid_og = Db::name('member')->where('id',$og['mid'])->value('pid');
                        if($pid_og)
                            $levelid_only = Db::name('member')->where('id',$pid_og)->value('levelid');
                        else
                            continue;
                    }
                }
                if(getcustom('commission2moneypercent',$aid) && $sysset['commission2moneypercent1'] > 0){
                    //是否是首单
                    $beforeorder = Db::name('shop_order')->where('aid',$aid)->where('mid',$og['mid'])->where('status','in','1,2,3')->where('paytime','<',$og['paytime'])->find();
                    if(!$beforeorder){
                        $commissionpercent = 1 - $sysset['commission2moneypercent1'] * 0.01;
                        $moneypercent = $sysset['commission2moneypercent1'] * 0.01;
                    }else{
                        $commissionpercent = 1 - $sysset['commission2moneypercent2'] * 0.01;
                        $moneypercent = $sysset['commission2moneypercent2'] * 0.01;
                    }
                }else{
                    $commissionpercent = 1;
                    $moneypercent = 0;
                }

                if($og['module'] == 'yuyue'){
                    $product = Db::name('yuyue_product')->where('id',$og['proid'])->find();
                }elseif($og['module'] == 'luckycollage' || $og['module'] == 'lucky_collage'){
                    //luckycollage废弃，替换为lucky_collage 2.6.4
                    $product = Db::name('lucky_collage_product')->where('id',$og['proid'])->find();
                    if (getcustom('luckycollage_fail_commission',$aid)) {
                        if ($og['iszj'] == 2) {
                            $product['fenhongset'] = $product['fail_fenhongset'];
                            $product['gdfenhongset'] = $product['fail_gdfenhongset'];
                            $product['gdfenhongdata1'] = $product['fail_gdfenhongdata1'];
                            $product['gdfenhongdata2'] = $product['fail_gdfenhongdata2'];
                        }
                    }
                    if ($product['fenhongset'] == 0) {
                        $product['gdfenhongset'] = -1;
                    }
                }elseif($og['module'] == 'coupon'){
                    $product = Db::name('coupon')->where('id',$og['cpid'])->find();
                }elseif($og['module'] == 'scoreshop'){
                    $product = Db::name('scoreshop_product')->where('id',$og['proid'])->find();
                }elseif($og['module'] == 'kecheng'){
                    $product = Db::name('kecheng_list')->where('id',$og['kcid'])->find();
                }elseif($og['module'] == 'business_reward'){
                    if(getcustom('business_reward_member',$aid)){
                        //商家打赏订单
                        $product = [
                            'gdfenhongset' => $business_reward_set['gdfenhongset'],
                            'gdfenhongdata1' => $business_reward_set['gdfenhongdata'],
                        ];
                    }
                }elseif($og['module'] == 'hotel'){
                    $product = Db::name('hotel_room')->where('id',$og['roomid'])->find();
                }elseif($og['module'] == 'cashier_order'){
                    $product = Db::name('shop_product')->where('id', $og['proid'])->find();
                }else{
                    $product = Db::name('shop_product')->where('id',$og['proid'])->find();
                }
                if(getcustom('maidan_fenhong',$aid) || getcustom('maidan_fenhong_new',$aid)){
                    if($og['module'] == 'maidan'){
                        $product = [];
                        $product['gdfenhongset'] = 0;
                        if(getcustom('maidan_fenhong_new',$aid)){
                            $business = Db::name('business')->where('id',$og['bid'])->field('maidan_gudong,maidan_gudongfenhongdata1')->find();
                            if($business){
                                if($business['maidan_gudong'] == 2){
                                    $product['gdfenhongset'] = 1;
                                    $product['gdfenhongdata1'] = !empty($business['maidan_gudongfenhongdata1'])?$business['maidan_gudongfenhongdata1']:[];
                                }else if($business['maidan_gudong'] == 0){
                                    $product['gdfenhongset'] = -1;
                                }
                            }
                        }
                    }
                }
                $restaurant_fenhong_product_set_custom = getcustom('restaurant_fenhong_product_set',$aid);
                if(getcustom('restaurant_fenhong',$aid)){
                    if($og['module'] == 'restaurant_shop' || $og['module'] == 'restaurant_takeaway'){
                        $product = [];
                        $product['gdfenhongset'] = 0;
                        if($restaurant_fenhong_product_set_custom){
                            $product =  Db::name('restaurant_product')->where('id',$og['proid'])->find();
                        }
                    }
                }

                //分红结算方式：0按销售金额,1按销售利润;按销售金额结算即：销售价格×提成百分比，按销售利润即：（销售价格-商品成本）×提成百分比
                if($sysset['fhjiesuantype'] == 0){
                    $fenhongprice = $og['real_totalprice'];
                }else{
                    $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                }
                if(getcustom('yx_buyer_subsidy',$aid)){
                    if($sysset['fhjiesuantype'] == 2){
                        //按抽成计算
                        $fenhongprice = $og['order_fee']??0 ;
                    }
                }
                if(getcustom('baikangxie',$aid)){
                    $fenhongprice = $og['cost_price'] * $og['num'];
                }
                if(getcustom('member_dedamount',$aid)){
                    //如果是商城和买单订单，需要判断商家是否设置了让利
                    if(!$og['module'] || $og['module'] == 'shop' || $og['module'] == 'maidan'){
                        if($og['bid'] && $og['paymoney_givepercent'] && $og['paymoney_givepercent']>0){
                            //重置分红金额为抵扣
                            $fenhongprice = $og['dedamount_dkmoney']??0;
                        }else{
                            //若开启了分销依赖抵扣金，则分红金额为0
                            if($sysset['dedamount_fenxiao'] == 1) $fenhongprice = 0;
                        }
                    }
                }
                if($fenhongprice <= 0 && $product['gdfenhongset']!=2 && $product['gdfenhongset']!=3) continue;

                $ogids[] = $og['id'];
                $allfenhongprice = $allfenhongprice + $fenhongprice;

                if($fhlevellist){
                    $lastmidlist = [];
                    $old_midlist = [];
                    $old_mids = [];
                    $all_midlist = [];//所有会员列表
                    $level_count = count($fhlevellist);
                    $k=0;
                    foreach($fhlevellist as $fhlevel){
                        $k++;
                        if($fhlevel['fenhong_jiaquan_gudong_jqbl'] <= 0){
                            continue;
                        }
                        if(getcustom('partner_parent_only',$aid)){
                            //股东分红仅奖励购买人上级等级
                            if($levelid_only && $fhlevel['id'] != $levelid_only)
                                continue;
                        }
                        if(getcustom('business_fenhong_memberlevel',$aid) && $og['bid'] > 0){
                            $business = Db::name('business')->where('id',$og['bid'])->find();
                            if($business && $business['fenhong_memberlevel']!='' && !in_array($fhlevel['id'],explode(',',$business['fenhong_memberlevel']))) continue;
                        }
                        $where = [];
                        $where[] = ['aid', '=', $aid];
                        $where[] = ['levelid', '=', $fhlevel['id']];
                        $where[] = ['levelstarttime', '<', $og['createtime']]; //判断升级时间
                        $where2 = [];
                        $where2[] = ['ml.aid', '=', $aid];
                        $where2[] = ['ml.levelid', '=', $fhlevel['id']];
                        $where2[] = ['ml.levelstarttime', '<', $og['createtime']];
                        if($fhlevel['fenhong_max_money'] > 0) {
                            $where[] = ['total_fenhong_partner', '<', $fhlevel['fenhong_max_money']];
                            $where2[] = ['m.total_fenhong_partner', '<', $fhlevel['fenhong_max_money']];
                        }

                        if($defaultCid > 0 && $defaultCid != $fhlevel['cid']) {
                            //其他分组
                            if(getcustom('plug_sanyang',$aid)) {
                                if($fhlevel['fenhong_num'] > 0){
                                    $midlist = Db::name('member_level_record')->alias('ml')->leftJoin('member m', 'm.id = ml.mid')
                                        ->where($where2)->order('ml.levelstarttime,id')->limit(intval($fhlevel['fenhong_num']))->column('m.id,m.total_fenhong_partner,m.levelstarttime','ml.mid');
                                } else {
                                    $midlist = Db::name('member_level_record')->alias('ml')->leftJoin('member m', 'm.id = ml.mid')
                                        ->where($where2)->column('m.id,m.total_fenhong_partner,m.levelstarttime','ml.mid');
                                }
                            }
                        } else {
                            $field = 'id,total_fenhong_partner,levelstarttime,levelid';
                            if(getcustom('fenhong_max',$aid)){
                                $field .= ',fenhong_max';
                            }
                            //默认分组
                            if ($fhlevel['fenhong_num'] > 0) {
                                $midlist = Db::name('member')->where($where)->order('levelstarttime,id')->limit(intval($fhlevel['fenhong_num']))->column($field, 'id');
                            }else{
                                $midlist = Db::name('member')->where($where)->column($field,'id');
                            }
                        }

                        if($midlist){
                            foreach ($midlist as $mk => $memberarr){
                                //购买前最后一条升级记录，如果下单前等级不等于当前等级 则排除（当前等级不断变化，不是完全准确，所以需要对照升级记录表）
                                $levelup_last_log = Db::name('member_levelup_order')->where('aid',$aid)->where('status', 2)
                                    ->where('levelup_time', '<', $og['createtime'])->where('mid',$memberarr['id'])->order('levelup_time', 'desc')->find();
                                if($levelup_last_log && $levelup_last_log['levelid'] != $memberarr['levelid']){
                                    unset($midlist[$mk]);
                                }
                            }
                        }
                        $levelup_order_mids = Db::name('member_levelup_order')->where('aid',$aid)->where('levelid', $fhlevel['id'])->where('status', 2)
                            ->where('levelup_time', '<', $og['createtime'])->group('mid')->order('levelup_time', 'desc')->column('mid');
                        if($levelup_order_mids) {
                            $levelup_order_list = [];
                            foreach($levelup_order_mids as $lk => $item_lomid){
                                //最后一条记录等于当前等级才有价值
                                $lastlog = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $item_lomid)->where('status', 2)
                                    ->where('levelup_time', '<', $og['createtime'])
                                    ->order('levelup_time', 'desc')->find();
                                $levelup_order_list[$item_lomid] = $lastlog['levelid'];
                                if($lastlog['levelid']!=$fhlevel['id']){
                                    unset($levelup_order_mids[$lk]);
                                }
                            }
                            $field = 'id,total_fenhong_partner,levelstarttime,levelid';
                            if(getcustom('fenhong_max',$aid)){
                                $field .= ',fenhong_max';
                            }
                            if($levelup_order_mids){
                                $levelup_order_member = Db::name('member')->whereIn('id',$levelup_order_mids)->column($field,'id');
                                $midlist = array_merge((array)$midlist, (array) $levelup_order_member );
                                $midlist = array_unique_map($midlist);
                            }
                        }
                        if($sysset['partner_jiaquan'] == 1){
                            //开启后高等级的股东也会参与低等级的股东分红
                            $oldmidlist = $midlist;
                            $midlist = array_merge((array)$lastmidlist,(array)$midlist);
                            $lastmidlist = array_merge((array)$lastmidlist,(array)$oldmidlist);
                        }
                        if(!$midlist) continue;
                        if(getcustom('fenhong_gudong_yeji',$aid)){
                            //检测业绩条件
                            $fenhong_yeji_lv = $fhlevel['fenhong_yeji_lv']??0;
                            $fenhong_yeji_num = $fhlevel['fenhong_yeji_num']??0;
                            if($fenhong_yeji_num>0){
                                foreach($midlist as $fk=>$fv){
                                    $downmids = \app\common\Member::getdownmids($aid,$fv['id'],$fenhong_yeji_lv);
                                    if(empty($downmids)){
                                        $yeji = 0;
                                    }else{
                                        $yejiwhere = [];
                                        $yejiwhere[] = ['status','in','1,2,3'];
                                        $yejiwhere[] = ['mid','in',$downmids];
                                        $yeji = Db::name('shop_order')->where('aid',$aid)->where('mid','in',$downmids)->where($yejiwhere)->sum('totalprice');
                                    }
                                    if($yeji<$fenhong_yeji_num){
                                        unset($midlist[$fk]);
                                    }
                                }
                                $midlist = array_values($midlist);
                            }
                        }

                        if($gdfenhong_jiesuantype==1){
                            //统一设置独立分红比例的，获取到所有会员之后再进行平均发放
                            foreach($midlist as $mk => $member){
                                $midlist[$mk]['levelid'] = $fhlevel['id'];
                            }
                            $all_midlist = array_merge((array)$all_midlist, (array) $midlist );
                            $all_midlist = array_unique_map($all_midlist);
                            if($k<$level_count){
                                continue;
                            }else{
                                $midlist = $all_midlist;
                            }
                        }
                        //股东贡献量分红 开启后可设置一定比例的分红金额按照股东的团队业绩量分红
                        $pergxcommon = 0;
                        if($sysset['partner_gongxian']==1 && $fhlevel['fenhong_gongxian_percent'] > 0){
                            $gongxian_percent = $fhlevel['fenhong'] * $fhlevel['fenhong_gongxian_percent']*0.01;
                            $fhlevel['fenhong'] = $fhlevel['fenhong'] * (1 - $fhlevel['fenhong_gongxian_percent']*0.01);
                            $gongxianCommissionTotal = $gongxian_percent * $fenhongprice * 0.01;

                            $yejiwhere = [];
                            $yejiwhere[] = ['createtime','>=',$starttime];
                            $yejiwhere[] = ['createtime','<',$endtime];
                            $yejiwhere[] = ['isfenhong','=',0];
                            $yejiwhere[] = ['status','in','1,2,3'];

                            $totalyeji = 0;
                            foreach($midlist as $kk=>$item){
                                $downmids = \app\common\Member::getteammids($aid,$item['id']);
                                $yeji = Db::name('shop_order')->where('aid',$aid)->where('mid','in',$downmids)->where($yejiwhere)->sum('totalprice');
                                $yeji2 = $yeji;
                                if($fhlevel['fenhong_gongxian_peraddnum'] > 0){ //下级每出现一个同级股东增加份额
                                    $tjmembercount = Db::name('member')->where('aid',$aid)->where('levelid','=',$fhlevel['id'])->where('find_in_set('.$item['id'].',path)')->count();
                                    if($tjmembercount > 0){
                                        $yeji2 = $yeji2 * (1+$tjmembercount*$fhlevel['fenhong_gongxian_peraddnum']);
                                    }
                                }
                                $midlist[$kk]['yeji'] = $yeji;
                                $midlist[$kk]['yeji2'] = $yeji2;
                                $totalyeji += $yeji2;
                            }
                            if($totalyeji > 0){
                                $pergxcommon = $gongxianCommissionTotal / $totalyeji;
                            }else{
                                $pergxcommon = 0;
                            }
                        }

                        $totalcommission = 0;
                        $totalscore = 0;
                        if($gdfenhong_jiesuantype==1){
                            //统一设置独立分红比例
                            $totalcommission = $sysset['gdfh_bili'] * $fenhongprice * 0.01;
                        }elseif($product['gdfenhongset']==1){//按比例
                            $fenhongdata = json_decode($product['gdfenhongdata1'],true);
                            if($fenhongdata){
                                $totalcommission = $fenhongdata[$fhlevel['id']]['commission'] * $fenhongprice * 0.01;
                            }
                        }elseif($product['gdfenhongset']==2){//按固定金额
                            $fenhongdata = json_decode($product['gdfenhongdata2'],true);
                            if($fenhongdata){
                                $totalcommission = $fenhongdata[$fhlevel['id']]['commission'] * $og['num'];
                            }
                        }elseif($product['gdfenhongset']==3){//按固定积分
                            $fenhongdata = json_decode($product['gdfenhongdata2'],true);
                            if($fenhongdata){
                                $totalscore = $fenhongdata[$fhlevel['id']]['score'] * $og['num'];
                            }
                        }elseif($product['gdfenhongset']==4){//按积分比例
                            $fenhongdata = json_decode($product['gdfenhongdata1'],true);
                            if($fenhongdata){
                                $totalscore = round($fenhongdata[$fhlevel['id']]['score'] * $fenhongprice * 0.01);
                            }
                        }elseif($product['gdfenhongset'] == 0){

                            $totalcommission = $fhlevel['fenhong'] * $fenhongprice * 0.01;
                            if(getcustom('fenhong_maidan_percent',$aid) || getcustom('maidan_fenhong_new',$aid)){
                                if($og['module'] == 'maidan'){
                                    //买单单独比例
                                    if($fhlevel['fenhong_maidan_percent']>=0){
                                        $totalcommission = $fhlevel['fenhong_maidan_percent'] * $fenhongprice * 0.01;
                                    }else{
                                        $totalcommission = 0;
                                    }
                                }
                            }

                            if($fhlevel['fenhong_score_percent'] > 0){
                                $totalscore = round($fhlevel['fenhong_score_percent'] * $fenhongprice * 0.01);
                            }
                        }
                        if(getcustom('fenhong_removefenxiao',$aid) && $fhlevel['gdfenhong_removefenxiao'] == 1){
                            if($og['parent1'] && $og['parent1commission']){
                                $totalcommission = $totalcommission - $og['parent1commission'];
                            }
                            if($og['parent2'] && $og['parent2commission']){
                                $totalcommission = $totalcommission - $og['parent2commission'];
                            }
                            if($og['parent3'] && $og['parent3commission']){
                                $totalcommission = $totalcommission - $og['parent3commission'];
                            }
                            if($totalcommission <= 0) continue;
                        }

                        if($totalcommission == 0 && $totalscore==0) continue;

                        $score = 0;

                        //下级每出现一个同级股东增加份额
                        if($fhlevel['fenhong_gongxian_peraddnum'] > 0){
                            $gxtotalnum = 0;
                            foreach($midlist as $kk=>$item){
                                $gxnum = 1;
                                $tjmembercount = Db::name('member')->where('aid',$aid)->where('levelid','=',$fhlevel['id'])->where('find_in_set('.$item['id'].',path)')->count();
                                if($tjmembercount > 0){
                                    $gxnum += $tjmembercount*$fhlevel['fenhong_gongxian_peraddnum'];
                                }
                                $gxtotalnum += $gxnum;
                                $midlist[$kk]['gxnum'] = $gxnum;
                            }
                            $precommission = $totalcommission / $gxtotalnum;
                        }

                        $newcommission = 0;
                        if($gdfenhong_add){
                            //叠加股东分红，必须上面计算完平均值之后再合并会员,与股东加权分红冲突
                            if($old_midlist){
                                $old_mids = array_column($old_midlist,'id');
                                $midlist = array_merge($midlist,$old_midlist);
                            }
                            $old_midlist = $midlist;
                        }

                        foreach($midlist as $item){

                            //获取个人贡献值
                            $gxz = self::getgdfenhonggxz($aid,$item['id'],$starttime,$endtime,$midlist);

                            //股东加权分红
                            $fenhong_jiaquan_gudong_jqbl = $fhlevel['fenhong_jiaquan_gudong_jqbl'] / 100;
                            $ys_commission = $totalcommission * $fenhong_jiaquan_gudong_jqbl;

                            //股东的加权分红公式：股东加权分红的总资金池x贡献值=个人的加权分红奖励
                            $commission = round(bcmul($ys_commission,$gxz['gxz'],3),2);

                            $fhlevel_id = $fhlevel['id'];
                            if($gdfenhong_jiesuantype==1){
                                $fhlevel_id = $item['levelid'];
                            }
                            if($fhlevel['fenhong_gongxian_peraddnum'] > 0){
                                $commission = $precommission * $item['gxnum'];
                            }
                            if($gdfenhong_add){
                                //叠加股东分红，高等级使用自身级别的最大值
                                if($old_midlist && in_array($item['id'],$old_mids)){
                                    $fenhong_max_money = $fhlevellist[$item['levelid']]['fenhong_max_money']??0;
                                }
                            }
                            //股东最大分红，优先使用会员列表单独设置的参数
                            if(getcustom('fenhong_max',$aid) && $item['fenhong_max']>0){
                                $fenhong_max_money = $item['fenhong_max'];
                            }
                            $mid = $item['id'];
                            $gxcommon = 0;
                            if($pergxcommon > 0){
                                if($item['yeji'] >= $fhlevel['fenhong_gongxian_minyeji']){
                                    $gxcommon = $item['yeji2'] * $pergxcommon;
                                }
                            }
                            $newcommission = $commission + $gxcommon;

                            if($newcommission <= 0){
                                continue;
                            }

                            if($fenhong_max_money > 0) {
                                if($newcommission + $item['total_fenhong_partner'] > $fenhong_max_money) {
                                    $newcommission = $fenhong_max_money - $item['total_fenhong_partner'];
                                }
                            }
                            if($commissionpercent != 1){
                                $fenhongcommission = round($newcommission*$commissionpercent,2);
                                $fenhongmoney = round($newcommission*$moneypercent,2);
                            }else{
                                $fenhongcommission = $newcommission;
                                $fenhongmoney = 0;
                            }
                            $midfhArr[$mid] = [
                                'totalcommission'=>$newcommission,
                                'commission'=>$fenhongcommission,
                                'money'=>$fenhongmoney,
                                'score'=>$score,
                                'ogids'=>[$og['id']],
                                'module'=>$og['module'] ?? 'shop',
                                'jq_send_info'=> '原始分红金额：'.$ys_commission.',贡献值：'.$gxz['gxz'].',股东个人业绩：'.$gxz['self_yeji'].',各股东个人业绩总和：'.$gxz['gudongyeji'],
                            ];

                            //记录贡献值
                            $gxzremark = '贡献值：'.$gxz['gxz'].',股东个人业绩：'.$gxz['self_yeji'].',各股东个人业绩总和：'.$gxz['gudongyeji'];
                            self::savefenhongjiaquangxz($aid,$mid,$gxz['gxz'],$starttime,$endtime,2,$gxzremark);
                        }
                    }
                }
                $remark = t('股东加权分红',$aid);
                self::fafang($aid,$midfhArr,'fenhong_gdjiaquan',$remark);

                $midfhArr = [];
                //订单商品表记录已发放
                Db::name('shop_order_goods')->where('aid',$aid)->where('id',$og['id'])->update(['is_gd_jqfenhong_jd' =>1]);
            }

            //加权分红结算完立即发放
            $log_ids = Db::name('member_fenhonglog')->where('aid',$aid)->where('status',0)->where('type','fenhong_gdjiaquan')->column('id');
            \app\common\Fenhong::send($aid,0,0,$log_ids);
        }
    }

    /**
     * 获取股东加权分红贡献值
     * @author: liud
     * @time: 2025/1/14 上午9:32
     */
    public static function getgdfenhonggxz($aid,$mid,$starttime,$endtime,$midlist){
        if(getcustom('fenhong_jiaquan_gudong',$aid)){
            //个人业绩
            $mv['self_yeji'] = self::getgdfenhonselfyeji($aid,$mid,$starttime,$endtime);

            if($mv['self_yeji'] <= 0){
                return ['gxz' => 0];
            }

            if(!$midlist){
                $midlist = self::get_fenhong_gudongarr($aid);
            }

            //各股东业绩综合
            $mv['gudongyeji'] = 0;
            if($midlist){
                foreach ($midlist as $vv) {
                    $mv['gudongyeji'] += self::getgdfenhonselfyeji($aid,$vv['id'],$starttime,$endtime);
                }
            }

            //个人贡献值=股东个人业绩÷各股东个人业绩总和
            $gxz = round(bcdiv($mv['self_yeji'],$mv['gudongyeji'],5),4);

            return ['gxz' => $gxz ?? 0,'self_yeji' => $mv['self_yeji'],'gudongyeji' => $mv['gudongyeji']];
        }
    }

    /**
     * 获取股东个人业绩
     * @author: liud
     * @time: 2025/1/15 上午10:44
     */
    public static function getgdfenhonselfyeji($aid,$mid,$starttime,$endtime)
    {
        if (getcustom('fenhong_jiaquan_gudong', $aid)) {
            if(!$mv = Db::name('member')->where('id',$mid)->where('aid',$aid)->field('id,levelid,areafenhong,areafenhong_province,areafenhong_city,areafenhong_area')->find()){
                return 0;
            }

            //获取代理类型
            $member_level = Db::name('member_level')->where('id',$mv['levelid'])->where('aid',$aid)->field('areafenhong,fenhong')->find();
            if($member_level['fenhong'] <= 0){
                return 0;
            }
            $mv['level_areafenhong'] = $member_level['areafenhong'];

            //个人分销业绩
            $mv['self_yeji'] = Db::name('shop_order_goods')->where('aid',$aid)->where('mid',$mv['id'])->where('status','in',[1,2,3])->where('refund_num',0)->where('createtime','between',[$starttime,$endtime])->sum('real_totalprice');

            //判断是否是区域代理
            if(($mv['areafenhong'] > 0 && $mv['areafenhong'] < 4) || ($mv['areafenhong'] == 0 && $mv['level_areafenhong'] > 0)){
                //个人区域代理业绩
                $yjwhere = [];
                if(($mv['areafenhong'] == 1 || $mv['level_areafenhong'] == 1 ) && $mv['areafenhong_province']){
                    $yjwhere[] = ['o.area2','like','%'.$mv['areafenhong_province'].'%'];
                }elseif(($mv['areafenhong'] == 2 || $mv['level_areafenhong'] == 2 ) && $mv['areafenhong_province'] && $mv['areafenhong_city']){
                    $yjwhere[] = ['o.area2','like','%'.$mv['areafenhong_province'].','.$mv['areafenhong_city'].'%'];
                }elseif(($mv['areafenhong'] == 3 || $mv['level_areafenhong'] == 3 ) && $mv['areafenhong_province'] && $mv['areafenhong_city'] && $mv['areafenhong_area']){
                    $yjwhere[] = ['o.area2','like','%'.$mv['areafenhong_province'].','.$mv['areafenhong_city'].','.$mv['areafenhong_area'].'%'];
                }
                if($yjwhere){
                    $mv['self_areayeji'] = Db::name('shop_order_goods')->alias('og')
                        ->where('og.aid',$aid)->where('og.status','in',[1,2,3])->where('og.refund_num',0)->where('og.createtime','between',[$starttime,$endtime])->join('shop_order o','o.id=og.orderid')->where($yjwhere)->where('og.mid',$mv['id'])->sum('og.real_totalprice');

                    $mv['self_yeji'] = bcadd($mv['self_yeji'],$mv['self_areayeji'],5);
                }
            }

            return $mv['self_yeji'] ?? 0;
        }
    }

    /**
     * 获取分红股东列表
     * @author: liud
     * @time: 2025/1/18 下午2:02
     */
    public static function get_fenhong_gudongarr($aid)
    {
        $sysset = Db::name('admin_set')->where('aid',$aid)->find();

        //参与股东分红的等级
        $where_level = [];
        $where_level[] = ['fenhong','>',0];
        if(getcustom('maidan_fenhong_new',$aid)){
            $where_level = [];
            $where_level[] = ['fenhong|fenhong_maidan_percent','>',0];
        }
        $fhlevellist = Db::name('member_level')->where('aid',$aid)->where($where_level)->order('sort desc,id desc')->column('*','id');

        if(!$fhlevellist) return ['commissionyj'=>0,'oglist'=>[]];

        //股东最大分红累加低级别的分红上限参数
        if(getcustom('fenhong_max',$aid) && !empty($sysset['fenhong_max_add'])){
            foreach($fhlevellist as $k=>$v){
                $fenhong_max = Db::name('member_level')
                    ->where('aid',$aid)
                    ->where('sort','<',$v['sort'])
                    ->sum('fenhong_max_money');
                $fhlevellist[$k]['fenhong_max_money'] = bcadd($v['fenhong_max_money'],$fenhong_max,2);
            }
        }

        $defaultCid = Db::name('member_level_category')->where('aid',$aid)->where('isdefault', 1)->value('id');

        $og['createtime'] = time();
        if($fhlevellist){
            $lastmidlist = [];
            $k=0;
            foreach($fhlevellist as $fhlevel){
                $k++;
                $where = [];
                $where[] = ['aid', '=', $aid];
                $where[] = ['levelid', '=', $fhlevel['id']];
                $where[] = ['levelstarttime', '<', $og['createtime']]; //判断升级时间
                $where2 = [];
                $where2[] = ['ml.aid', '=', $aid];
                $where2[] = ['ml.levelid', '=', $fhlevel['id']];
                $where2[] = ['ml.levelstarttime', '<', $og['createtime']];
                if($fhlevel['fenhong_max_money'] > 0) {
                    $where[] = ['total_fenhong_partner', '<', $fhlevel['fenhong_max_money']];
                    $where2[] = ['m.total_fenhong_partner', '<', $fhlevel['fenhong_max_money']];
                }

                if($defaultCid > 0 && $defaultCid != $fhlevel['cid']) {
                    //其他分组
                    if(getcustom('plug_sanyang',$aid)) {
                        if($fhlevel['fenhong_num'] > 0){
                            $midlist = Db::name('member_level_record')->alias('ml')->leftJoin('member m', 'm.id = ml.mid')
                                ->where($where2)->order('ml.levelstarttime,id')->limit(intval($fhlevel['fenhong_num']))->column('m.id,m.total_fenhong_partner,m.levelstarttime','ml.mid');
                        } else {
                            $midlist = Db::name('member_level_record')->alias('ml')->leftJoin('member m', 'm.id = ml.mid')
                                ->where($where2)->column('m.id,m.total_fenhong_partner,m.levelstarttime','ml.mid');
                        }
                    }
                } else {
                    $field = 'id,total_fenhong_partner,levelstarttime,levelid';
                    if(getcustom('fenhong_max',$aid)){
                        $field .= ',fenhong_max';
                    }
                    //默认分组
                    if ($fhlevel['fenhong_num'] > 0) {
                        $midlist = Db::name('member')->where($where)->order('levelstarttime,id')->limit(intval($fhlevel['fenhong_num']))->column($field, 'id');
                    }else{
                        $midlist = Db::name('member')->where($where)->column($field,'id');
                    }
                }

                if($midlist){
                    foreach ($midlist as $mk => $memberarr){
                        //购买前最后一条升级记录，如果下单前等级不等于当前等级 则排除（当前等级不断变化，不是完全准确，所以需要对照升级记录表）
                        $levelup_last_log = Db::name('member_levelup_order')->where('aid',$aid)->where('status', 2)
                            ->where('levelup_time', '<', $og['createtime'])->where('mid',$memberarr['id'])->order('levelup_time', 'desc')->find();
                        if($levelup_last_log && $levelup_last_log['levelid'] != $memberarr['levelid']){
                            unset($midlist[$mk]);
                        }
                    }
                }
                $levelup_order_mids = Db::name('member_levelup_order')->where('aid',$aid)->where('levelid', $fhlevel['id'])->where('status', 2)
                    ->where('levelup_time', '<', $og['createtime'])->group('mid')->order('levelup_time', 'desc')->column('mid');
                if($levelup_order_mids) {
                    $levelup_order_list = [];
                    foreach($levelup_order_mids as $lk => $item_lomid){
                        //最后一条记录等于当前等级才有价值
                        $lastlog = Db::name('member_levelup_order')->where('aid',$aid)->where('mid', $item_lomid)->where('status', 2)
                            ->where('levelup_time', '<', $og['createtime'])
                            ->order('levelup_time', 'desc')->find();
                        $levelup_order_list[$item_lomid] = $lastlog['levelid'];
                        if($lastlog['levelid']!=$fhlevel['id']){
                            unset($levelup_order_mids[$lk]);
                        }
                    }
                    $field = 'id,total_fenhong_partner,levelstarttime,levelid';
                    if(getcustom('fenhong_max',$aid)){
                        $field .= ',fenhong_max';
                    }
                    if($levelup_order_mids){
                        $levelup_order_member = Db::name('member')->whereIn('id',$levelup_order_mids)->column($field,'id');
                        $midlist = array_merge((array)$midlist, (array) $levelup_order_member );
                        $midlist = array_unique_map($midlist);
                    }
                }
                if($sysset['partner_jiaquan'] == 1){
                    //开启后高等级的股东也会参与低等级的股东分红
                    $oldmidlist = $midlist;
                    $midlist = array_merge((array)$lastmidlist,(array)$midlist);
                    $lastmidlist = array_merge((array)$lastmidlist,(array)$oldmidlist);
                }
                if(!$midlist) continue;
                if(getcustom('fenhong_gudong_yeji',$aid)){
                    //检测业绩条件
                    $fenhong_yeji_lv = $fhlevel['fenhong_yeji_lv']??0;
                    $fenhong_yeji_num = $fhlevel['fenhong_yeji_num']??0;
                    if($fenhong_yeji_num>0){
                        foreach($midlist as $fk=>$fv){
                            $downmids = \app\common\Member::getdownmids($aid,$fv['id'],$fenhong_yeji_lv);
                            if(empty($downmids)){
                                $yeji = 0;
                            }else{
                                $yejiwhere = [];
                                $yejiwhere[] = ['status','in','1,2,3'];
                                $yejiwhere[] = ['mid','in',$downmids];
                                $yeji = Db::name('shop_order')->where('aid',$aid)->where('mid','in',$downmids)->where($yejiwhere)->sum('totalprice');
                            }
                            if($yeji<$fenhong_yeji_num){
                                unset($midlist[$fk]);
                            }
                        }
                        $midlist = array_values($midlist);
                    }
                }
            }
        }

        return $midlist ?? [];
    }

    /**
     * 记录加权分红贡献值
     * @author: liud
     * @time: 2025/1/17 下午4:25
     */
    public static function savefenhongjiaquangxz($aid,$mid,$gxz,$starttime,$endtime,$type,$remark){
        if(getcustom('fenhong_jiaquan_gudong',$aid) || getcustom('fenhong_jiaquan_area',$aid)){
            $season = ceil((date('n', $starttime))/3);//季度

            $year = date('Y',$starttime);

            if(!Db::name('member_fenhong_jiaquan_gxz')->where('aid',$aid)->where('mid',$mid)->where('type',$type)->where('starttime',$starttime)->where('endtime',$endtime)->find()){
                Db::name('member_fenhong_jiaquan_gxz')->insert([
                    'aid' => $aid,
                    'mid' => $mid,
                    'year' => $year,
                    'year_jd' => $season,
                    'starttime' => $starttime,
                    'endtime' => $endtime,
                    'gxz' => $gxz,
                    'type' => $type,
                    'remark' => $remark,
                    'createtime' => time()
                ]);
            }
        }
    }

    public static function business_shopbuyfenhong($aid,$orderid,$ogdatas=[],$type='shop'){
        if(getcustom('level_business_shopbuyfenhong',$aid)){
            //店铺分红，多商家商品购买分红，确认收货后直接按利润方式结算
            if(!$ogdatas) return ['commissionyj'=>0,'oglist'=>[]];
            $adminset = Db::name('admin_set')->where('aid',$aid)->field('id,fhjiesuantype')->find();
            $midfhArr = [];
            foreach($ogdatas as $og){
                //需要是多商户订单才可分红
                if($og['bid']<=0) continue;
                //查询商户绑定的会员
                $member =  Db::name('member')->alias('m')
                    ->join('business b','m.id = b.mid')
                    ->where('b.id',$og['bid'])->field('m.*')->find();
                if(!$member || $member['pid']<=0) continue;

                //查询商户的推荐人
                $parent = Db::name('member')->where('id',$member['pid'])->find();
                if(!$parent) continue;


                //分红结算方式 0销售额 1 利润
                if($adminset['fhjiesuantype'] == 1){
                    $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                }else{
                    $fenhongprice = $og['real_totalprice'];
                }
                if(getcustom('yx_buyer_subsidy',$aid)){
                    if($sysset['fhjiesuantype'] == 2){
                        //按抽成计算
                        $fenhongprice = $og['order_fee']??0 ;
                    }
                }
                if($fenhongprice>0){
                    //计算推荐人及推荐人上级分红
                    //推荐人
                    $plevel = Db::name('member_level')->where('id',$parent['levelid'])->field('id,business_shopbuy_fenhongbl,business_shopbuy_fenhongbl2')->find();
                    if($plevel && $plevel['business_shopbuy_fenhongbl']>0){
                        $fenhongcommission = $fenhongprice * $plevel['business_shopbuy_fenhongbl']/100;
                        if($fenhongcommission>0){
                            if($midfhArr[$parent['id']]){
                                $midfhArr[$parent['id']]['totalcommission'] += $fenhongcommission;
                                $midfhArr[$parent['id']]['commission'] += $fenhongcommission;
                                $midfhArr[$parent['id']]['ogids'][] = $og['id'];
                            }else{
                                $midfhArr[$parent['id']] = [
                                    'totalcommission'=>$fenhongcommission,
                                    'commission'=>$fenhongcommission,
                                    'money'=>0,
                                    'score'=>0,
                                    'ogids'=>[$og['id']],
                                    'module'=>$og['module'] ?? 'shop',
                                    'levelid' => $parent['levelid'],
                                    'type' => '店铺分红',
                                    'downMember' =>$member
                                ];
                            }
                        }
                    }
                    //推荐人上级
                    if($parent['pid']){
                        //推荐人上级
                        $parent2 = Db::name('member')->where('id',$parent['pid'])->field('id,pid,levelid')->find();
                        if($parent2){
                            $plevel2 = Db::name('member_level')->where('id',$parent2['levelid'])->field('id,business_shopbuy_fenhongbl,business_shopbuy_fenhongbl2')->find();
                            if($plevel2 && $plevel2['business_shopbuy_fenhongbl2']>0){
                                $fenhongcommission2 = $fenhongprice * $plevel2['business_shopbuy_fenhongbl2']/100;
                                if($fenhongcommission2>0){
                                    if($midfhArr[$parent2['id']]){
                                        $midfhArr[$parent2['id']]['totalcommission'] += $fenhongcommission2;
                                        $midfhArr[$parent2['id']]['commission'] += $fenhongcommission2;
                                        $midfhArr[$parent2['id']]['ogids'][] = $og['id'];
                                    }else{
                                        $midfhArr[$parent2['id']] = [
                                            'totalcommission'=>$fenhongcommission2,
                                            'commission'=>$fenhongcommission2,
                                            'money'=>0,
                                            'score'=>0,
                                            'ogids'=>[$og['id']],
                                            'module'=>$og['module'] ?? 'shop',
                                            'levelid' => $parent2['levelid'],
                                            'type' => '店铺分红',
                                            'downMember' =>$parent
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if($midfhArr){
                $moeny_weishu = 2;
                if(getcustom('fenhong_money_weishu',$aid)){
                    $moeny_weishu = Db::name('admin_set')->where('aid',$aid)->value('fenhong_money_weishu');
                }
                $moeny_weishu = $moeny_weishu?$moeny_weishu:2;
                foreach($midfhArr as $mid=>$midfh){
                    $totalcommission = dd_money_format($midfh['totalcommission'],$moeny_weishu);
                    $commission = dd_money_format($midfh['commission'],$moeny_weishu);
                    $fhdata = [];
                    $fhdata['aid'] = $aid;
                    $fhdata['mid'] = $mid;
                    $fhdata['frommid'] = 0;
                    $fhdata['commission'] = $totalcommission;
                    $fhdata['remark'] = '店铺分红';
                    $fhdata['type']   = 'business_shopbuyfenhong';
                    $fhdata['createtime'] = time();
                    $fhdata['ogids'] = implode(',',$midfh['ogids']);
                    $fhdata['module'] = $midfh['module'];
                    $fhdata['send_commission'] = $commission;
                    $fhdata['send_money'] = 0;
                    $fhdata['send_score'] = 0;
                    $fhdata['send_fuchi'] = 0;
                    $fhdata['status'] = 1;
                    $fhdata['levelid'] = $midfh['levelid'];
                    $log_id = Db::name('member_fenhonglog')->insertGetId($fhdata);
                    if($log_id){
                        //发放
                        if($fhdata['send_commission'] > 0){
                            \app\common\Member::addcommission($aid,$fhdata['mid'],$fhdata['frommid'],$fhdata['send_commission'],$fhdata['remark'],1,$fhdata['type'],$fhdata['levelid'],'',$log_id);
                        }
                    }
                }
            }
        }
    }

    public static function businessKouchufenhong($aid,$id,$businessKouchus=[]){
        if(getcustom('business_kouchu_fenhong',$aid)){
            //分红发放时商家扣除余额 
            $totalcommission = 0;
            //权限
            $showKouchufenhong = true;
            //平台权限
            $admin_user = Db::name('admin_user')->where('aid',$aid)->where('isadmin','>',0)->field('auth_type,auth_data')->find();
            if($admin_user){
                if($admin_user['auth_type'] !=1){
                    $admin_auth = !empty($admin_user['auth_data'])?json_decode($admin_user['auth_data'],true):[];
                    if(!in_array('BusinessKouchuFenhong,BusinessKouchuFenhong',$admin_auth)){
                        $showKouchufenhong = false;
                    }
                }
            }else{
                $showKouchufenhong = false;
            }
            if(!$showKouchufenhong) return [];

            $commission_kouchu_fenhong = Db::name('business_sysset')->where('id',$aid)->value('commission_kouchu_fenhong');
            if(!$commission_kouchu_fenhong || $commission_kouchu_fenhong !=1) return [];

            //再次查询分红记录
            $log = Db::name('member_fenhonglog')->where('id',$id)->where('status',1)->find();
            if(!$log || $log['commission']<=0) return $businessKouchus;
            if(!$log['ogids'] || empty('ogids')) return $businessKouchus;

            $types = ['teamfenhong','teamfenhong_pj','fenhong','fenhong_huiben','areafenhong'];
            if(!in_array($log['type'],$types)) return $businessKouchus;

            $ogids = explode(',',$log['ogids']);
            $ids = [];
            foreach($ogids as $ogid){
                if(!in_array($ogid,$ids)){
                    $ids[] = $ogid;
                }
            }
            if(!$ids) return $businessKouchus;
            $idstr = implode(',',$ids);

            $where = [];
            if(count($ids)>1){
                $where[] = ['id','in',$ids];
            }else{
                $where[] = ['id','=',$ids[0]];
            }
            $where[] = ['aid','=',$aid];

            $tablename = $log['module'];
            $ordername = \app\common\Order::getOrderTypeName($tablename);
            if($tablename == 'shop_hb' || $tablename == 'scoreshop_hb' || $tablename == 'restaurant_takeaway_hb'){
                if($tablename == 'shop_hb'){
                    $tablename = 'shop';
                }else if($tablename == 'scoreshop_hb'){
                    $tablename = 'scoreshop';
                }else{
                    $tablename = 'restaurant_takeaway';
                }
                $oglist = Db::name($tablename.'_order_goods')->where($where)->select()->toArray();
            }else{
                if(\app\common\Order::hasOrderGoodsTable($tablename)){
                    $oglist =  Db::name($tablename.'_order_goods')->where($where)->select()->toArray();
                }else{
                    $oglist =  Db::name($tablename.'_order')->where($where)->select()->toArray();
                }
            }
            if(!$oglist) return $businessKouchus;

            //统计是否是单一的多商户，只有单一的多商户才会减去分红
            $bids = [];
            $ordernums = [];
            foreach($oglist as $og){
                if(!in_array($og['bid'],$bids)){
                    $bids[] = $og['bid'];
                    if($og['bid'] <=0) break;
                }
                if(!in_array($og['ordernum'],$ordernums)){
                    $ordernums[] = $og['ordernum'];
                }
            }
            if(!$bids || count($bids)>1 || $bids[0]<=0) return $businessKouchus;
            $bid = $bids[0];

            $ordernumstr = $ordernums?implode(',',$ordernums):'';
            $remark = $ordername.'：'.$ordernumstr.'分红，扣除减少';
            //是否增加
            $addstatus = true;
            if($businessKouchus){
                foreach($businessKouchus as $k=>&$kcv){
                    if($kcv['aid'] == $aid && $kcv['bid'] == $bid && $kcv['module'] == $log['module'] && $kcv['ordernums'] == $ordernumstr){
                        $kcv['commission'] += $log['commission'];
                        $addstatus = false;
                        break;
                    }
                }
                unset($kcv);

            }
            if($addstatus){
                $businessKouchus[] = [
                    'aid'=>$aid,
                    'bid'=>$bid,
                    'commission'=>$log['commission'],
                    'module'=>$log['module'],
                    'ordernums'=>$ordernumstr,
                    'ids'=>$idstr,
                    'remark'=>$remark,
                ];
            }
            return $businessKouchus;
        }
    }


    //结算团队业绩阶梯分红
    public static function jiesuan_yeji_fenhong($aid,$orderid=0,$type='shop'){
        $custom = getcustom('yx_team_yeji_fenhong',$aid);
        if($custom){
            $sysset = Db::name('team_yeji_fenhong_set')->where('aid',$aid)->find();
            $limit = 10;
            $where = [];
            $where[] = ['og.aid','=',$aid];
            $where[] = ['og.yeji_fenhong','=',0];
            if($orderid){
                $where[] = ['og.orderid','=',$orderid];
            }

            if($sysset['fhjiesuanbusiness'] == 0){ //多商户的商品是否参与分红
                $where[] = ['og.bid','=','0'];
            }

            //排除退款订单
            $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,o.paytime')->join('shop_order o','o.id=og.orderid')
                ->where($where)->where('refund_num',0)->limit($limit)->order('id desc')->select()->toArray();
            if($oglist){
                $update = ['og.yeji_fenhong'=>1];
                $ids = array_column($oglist,'id');
                Db::name('shop_order_goods')->alias('og')->where('id','in',$ids)->update($update);
            }

            self::team_yeji_fenhong($aid,$sysset,$oglist);

            if(!$oglist){
                return ['status'=>2,'msg'=>'全部结算完成','sucnum'=>count($oglist)];
            }
            return ['status'=>1,'msg'=>'结算完成'.count($oglist).'条','sucnum'=>count($oglist)];
        }
    }
    //团队业绩阶梯分红
    public static function team_yeji_fenhong($aid,$sysset,$oglist,$isyj=0,$yjmid=0){
        $levelids = explode(',', $sysset['levelids']);
        //对奖励设置按业绩降序排序
        $config_data = json_decode($sysset['config_data'],true);
        $yeji_arr = array_column($config_data, 'yeji');
        array_multisort($yeji_arr, SORT_DESC , $config_data);
        $custom = getcustom('yx_team_yeji_fenhong',$aid);
        if($custom) {
            if ($isyj == 1 && !$oglist) {
                //多商户的商品是否参与分红
                $bwhere = '1=1';
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')
                    ->where('og.aid', $aid)->where('og.isfenhong', 0)->where('og.status', 'in', [1, 2, 3])
                    ->where('og.refund_num',0)->join('shop_order o', 'o.id=og.orderid')->join('member m', 'm.id=og.mid')->where($bwhere)->order('og.id desc')->select()->toArray();
                if (!$oglist) $oglist = [];
            }
            //参与团队分红的等级
            $teamfhlevellist = Db::name('member_level')->where('aid', $aid)->where('id','in',$sysset['levelids'])->column('*', 'id');
            if (!$teamfhlevellist) return ['commissionyj' => 0, 'oglist' => []];
            if (!$oglist) return ['commissionyj' => 0, 'oglist' => []];
            $defaultCid = Db::name('member_level_category')->where('aid', $aid)->where('isdefault', 1)->value('id');
            if ($defaultCid) {
                $defaultLevelIds = Db::name('member_level')->where('aid', $aid)->where('cid', $defaultCid)->column('id');
            } else {
                $defaultLevelIds = Db::name('member_level')->where('aid', $aid)->column('id');
            }
            $allfenhongprice = 0;
            $ogids = [];
            $midteamfhArr = [];
            $newoglist = [];
            $commissionyj = 0;

            foreach ($oglist as $og) {
//                dump('下单会员'.$og['mid'].'订单'.$og['id'].'开始');
                writeLog('下单会员'.$og['mid'].'订单'.$og['id'].'开始','team_yeji_fenhong');
                if(!empty($og['module']) && $og['module']!='shop'){
                    continue;
                }
                $commissionyj_my = 0;
                $commissionpercent = 1;
                $moneypercent = 0;
                if ($sysset['fhjiesuantype'] == 0) {
                    $fenhongprice = $og['real_totalprice'];
                } else {
                    $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                }
                if(getcustom('yx_buyer_subsidy',$aid)){
                    if($sysset['fhjiesuantype'] == 2){
                        //按抽成计算
                        $fenhongprice = $og['order_fee']??0 ;
                    }
                }
                if ($fenhongprice <= 0) continue;
                $ogids[] = $og['id'];
                $allfenhongprice = $allfenhongprice + $fenhongprice;
                $member = Db::name('member')->where('id', $og['mid'])->find();

                if ($teamfhlevellist) {
                    //判断脱离时间
                    if ($member['change_pid_time'] && $member['change_pid_time'] >= $og['createtime']) {
                        $pids = $member['path_origin'];
                    } else {
                        $pids = $member['path'];
                    }
                    if ($pids) {
                        $pids .= ',' . $og['mid'];
                    } else {
                        $pids = (string)$og['mid'];
                    }
                    if ($pids) {
                        $parentList = Db::name('member')
                            ->where('id', 'in', $pids)
                            ->where('levelid','in',$sysset['levelids'])
                            ->order(Db::raw('field(id,' . $pids . ')'))
                            ->select()->toArray();
                        $parentList = array_reverse($parentList);
                        $hasfhlevelids = [];
                        $last_teamfenhongbl = 0;
                        $level_i = 0;
                        foreach ($parentList as $k => $parent) {
                            if($parent['id']==$og['mid']){
                                continue;
                            }
                            $ii++;
                            //判断升级时间
                            $leveldata = $teamfhlevellist[$parent['levelid']];
                            if ($parent['levelstarttime'] >= $og['createtime']) {
                                $levelup_order_levelid = Db::name('member_levelup_order')->where('aid', $aid)->where('mid', $parent['id'])->where('status', 2)
                                    ->where('levelup_time', '<', $og['createtime'])->whereIn('levelid', $defaultLevelIds)->order('levelup_time', 'desc')->value('levelid');
                                if ($levelup_order_levelid) {
                                    $parent['levelid'] = $levelup_order_levelid;
                                    $leveldata = $teamfhlevellist[$parent['levelid']];
                                } else {
                                    unset($parentList[$k]);
                                    continue;
                                }
                            }
                            $hasfhlevelids[] = $parent['levelid'];
                            if(!in_array($parent['levelid'],$levelids)){
                                //会员级别是否在可参与的分红级别范围内
                                continue;
                            }
                            //根据会员的业绩判断会员的分红比例
                            $this_teamfenhongbl = 0;
                            //统计该笔订单之前的业绩
                            $yeji_total= $parent['team_yeji'];
                            if($sysset['include_self']){
                                $yeji_total = bcadd($yeji_total,$parent['self_yeji'],2);
                            }
                            foreach($config_data as $c_v){
                                if($yeji_total>=$c_v['yeji']){
                                    $this_teamfenhongbl = $c_v['ratio'];
                                    break;
                                }
                            }
                            //dump('会员ID'.$parent['id'].'业绩'.$yeji_total.'分红比例'.$this_teamfenhongbl);
                            writeLog('会员ID'.$parent['id'].'业绩'.$yeji_total.'分红比例'.$this_teamfenhongbl,'team_yeji_fenhong');
                            //分红比例
                            if ($this_teamfenhongbl > 0) {
                                if($sysset['is_jicha']){
                                    //开启级差
                                    $this_teamfenhongbl = $this_teamfenhongbl - $last_teamfenhongbl;
                                }
                                if ($this_teamfenhongbl <= 0) $this_teamfenhongbl = 0;
                                $last_teamfenhongbl = $last_teamfenhongbl + $this_teamfenhongbl;
                                $totalfenhongmoney =$this_teamfenhongbl * $fenhongprice * 0.01;
                            }
                            //dump('会员ID'.$parent['id'].'获得分红'.$totalfenhongmoney);
                            writeLog('会员ID'.$parent['id'].'获得分红'.$totalfenhongmoney,'team_yeji_fenhong');
                            if ($totalfenhongmoney > 0) {
                                $level_i++;
                                if ($isyj == 1 && $yjmid == $parent['id']) {
                                    $commissionyj_my += $totalfenhongmoney;
                                }
                                if ($commissionpercent != 1) {
                                    $fenhongcommission = round($totalfenhongmoney * $commissionpercent, 2);
                                    $fenhongmoney = round($totalfenhongmoney * $moneypercent, 2);
                                } else {
                                    $fenhongcommission = $totalfenhongmoney;
                                    $fenhongmoney = 0;
                                }
                                if ($midteamfhArr[$parent['id']]) {
                                    $midteamfhArr[$parent['id']]['totalcommission'] = $midteamfhArr[$parent['id']]['totalcommission'] + $totalfenhongmoney;
                                    $midteamfhArr[$parent['id']]['commission'] = $midteamfhArr[$parent['id']]['commission'] + $fenhongcommission;
                                    $midteamfhArr[$parent['id']]['money'] = $midteamfhArr[$parent['id']]['money'] + $fenhongmoney;
                                    $midteamfhArr[$parent['id']]['ogids'][] = $og['id'];
                                    $midteamfhArr[$parent['id']]['levelid'] = $parent['levelid'];
                                    $midteamfhArr[$parent['id']]['yeji_total'] = $yeji_total;
                                } else {
                                    $midteamfhArr[$parent['id']] = [
                                        'totalcommission' => $totalfenhongmoney,
                                        'commission' => $fenhongcommission,
                                        'money' => $fenhongmoney,
                                        'ogids' => [$og['id']],
                                        'module' => $og['module'] ?? 'shop',
                                        'levelid' => $parent['levelid'],
                                        'yeji_total' => $yeji_total,
                                        'type' => t('团队业绩阶梯分红', $aid),
                                    ];
                                }
                            }
                        }
                    }
                }
                if ($isyj == 1 && $commissionyj_my > 0) {
                    $commissionyj += $commissionyj_my;
                    $og['commission'] = round($commissionyj_my, 2);
                    $og['fhname'] = t('团队业绩阶梯分红', $aid);
                    $newoglist[] = $og;
                }
                self::fafang($aid, $midteamfhArr, 'team_yeji_fenhong', t('团队业绩阶梯分红', $aid),$og['mid']);
                $midteamfhArr = [];

            }
        }
    }

	//根据商品和当前用户计算团队分红佣金
	public static function productExpectedTeamFenhong($product, $userlevel, $sysset) {
		if(getcustom('product_promotion_tag',$aid)){
			$commission = 0;
			$price = $product['sell_price']; //按照销售金额
			if($sysset['fhjiesuantype'] == 1){
				$price = $product['sell_price'] - $product['cost_price']; //按照销售利润
			}
			switch ($product['teamfenhongset']) {
				case 0: // 按会员等级设置
					if($userlevel['teamfenhonglv'] > 0){
						$isFenhong = $userlevel['teamfenhongonly'] ? $userlevel['teamfenhong_self'] : true;
						if($isFenhong){
							$commission += dd_money_format($userlevel['teamfenhongbl'] * $price * 0.01);
							$commission += $userlevel['teamfenhong_money'];
						}
					}
					break;

				case 1: // 独立分红比例
					$teamFenhongData = json_decode($product['teamfenhongdata1'] ?? '[]', true);
					$commission = ($teamFenhongData[$userlevel['id']]['commission'] ?? 0) * $price * 0.01;
					$commission = dd_money_format($commission);
					break;

				case 2: // 固定分红金额
					$teamFenhongData = json_decode($product['teamfenhongdata2'] ?? '[]', true);
					$commission = $teamFenhongData[$userlevel['id']]['commission'] ?? 0;
					break;

				default: // 不参与分红
					$commission = 0;
			}

			return max($commission, 0);
		}
	}

    public static function business_shareholder($aid,$sysset,$oglist){
        if(getcustom('extend_business_shareholder',$aid)) {

            $commission_weishu = 2;//佣金位数
            if(getcustom('fenhong_money_weishu',$aid)){
                $commission_weishu = $sysset['fenhong_money_weishu']??2;
            }

            //查询活动是否开启
            $set = Db::name('business_shareholder_set')->where('aid',$aid)->find();
            if(!$set || $set['radio']<=0) return;

            foreach ($oglist as $og) {
                $og['module'] = $og['module'] ?? 'shop';
                if($og['module'] != 'shop' && $og['module'] != 'maidan' && $og['module'] != 'cashier') continue;

                //分红结算方式：0按销售金额,1按销售利润;按销售金额结算即：销售价格×提成百分比，按销售利润即：（销售价格-商品成本）×提成百分比
                if($sysset['fhjiesuantype'] == 0){
                    $fenhongprice = $og['real_totalprice'];
                }else{
                    $fenhongprice = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                }
                if(getcustom('money_dec_commission_fenhong',$aid)){
                    if($og['module'] == 'maidan' && $sysset['money_dec_fenxiao_fenhong'] == 1){
                        //买单分红 按照销售额、抵扣部分参与分销分红开启时 增加抵扣金额
                        $fenhongprice += $og['dec_money'];
                    }elseif($og['module'] == 'shop' && $sysset['money_dec_fenxiao_fenhong'] == 0){
                        //商城分红 按照销售、抵扣部分参与分销分红关闭时 扣除抵扣金额
                        $fenhongprice -= $og['dec_money'];
                    }
                }
                if(getcustom('yx_buyer_subsidy',$aid)){
                    if($sysset['fhjiesuantype'] == 2){
                        //按抽成计算
                        $fenhongprice = $og['order_fee']??0 ;
                    }
                }
                if($fenhongprice <= 0) continue;

                //查询此商户的共享股东
                $records = Db::name('business_shareholder_record')->alias('bsr')
                    ->join('business_shareholder bs','bs.id = bsr.shid')
                    ->where('bsr.aid',$aid)->where('bsr.status',1)->where('bsr.endstatus',1)->where('bsr.type',0)->where('bsr.bid',$og['bid'])->field('bsr.*')->select()->toArray();
                if(!$records) continue;

                //计算发起人和入股人分红
                foreach($records as $record){
                    //拿订单创建时间不早于众筹完成时间的、参与分红且未生成分红记录的订单分红
                    if($og['createtime']<$record['endtime']) continue;
                    //发起人分红
                    $startcommission = $fenhongprice * $set['radio']/100 * $record['startradio']/100;
                    $startcommission = round($startcommission,$commission_weishu);
                    if($startcommission>0){
                        $countmid = Db::name('member')->where('id',$record['mid'])->where('aid',$aid)->count('id');
                        if($countmid){
                            $midteamfhArr = [];
                            $midteamfhArr[$record['mid']] = [
                                'totalcommission'=>$startcommission,
                                'commission'=>$startcommission,
                                'money'=>0,
                                'score'=>0,
                                'ogids'=>[$og['id']],
                                'module'=>$og['module'] ?? 'shop',
                                'type' => '共享股东',
                            ];
                            self::fafang($aid,$midteamfhArr,'business_shareholder','共享股东',$og['mid']);
                        }
                    }

                    //入股人分红
                    $childs = Db::name('business_shareholder_record')->alias('bsr')
                        ->join('member m','m.id = bsr.mid')
                        ->where('bsr.pid',$record['id'])->where('bsr.status',1)->where('bsr.type',1)->field('bsr.*')->select()->toArray();
                    foreach($childs as $child){
                        //发起人分红
                        $joincommission = $fenhongprice * $set['radio']/100 * $child['joineveryradio']/100 * $child['num'];
                        $joincommission = round($joincommission,$commission_weishu);
                        if($joincommission>0){
                            $midteamfhArr = [];
                            $midteamfhArr[$child['mid']] = [
                                'totalcommission'=>$joincommission,
                                'commission'=>$joincommission,
                                'money'=>0,
                                'score'=>0,
                                'ogids'=>[$og['id']],
                                'module'=>$og['module'] ?? 'shop',
                                'type' => '共享股东',
                            ];
                            self::fafang($aid,$midteamfhArr,'business_shareholder','共享股东',$og['mid']);
                        }
                    }
                }
            }
        }
    }
}
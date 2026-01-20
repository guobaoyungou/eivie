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

namespace app\custom;
use think\facade\Db;
class OrderCustom
{
    public function deal_first_cashback($aid,$mid,$back_price,$og,$v,$type = 0,$canshtype = 'shop',$order_mid=0)
    {

        if(getcustom('yx_cashback_time') || getcustom('yx_cashback_stage')){
            //自定义第一次发放
            //参数为 ：back_price总返回数额 og订单商品 v购物返现设置 type发放类型 0:立即发放（前面已发放过） 1：自定义发放（需要在这发放）canshtype 记录类型

            //返现类型 1、余额 2、佣金 3、积分 小数位数
            $money_weishu = 2;$commission_weishu = 2;$score_weishu = 0;
            if(getcustom('member_money_weishu',$aid)){
                $money_weishu = Db::name('admin_set')->where('aid',$aid)->value('member_money_weishu');
            }
            if(getcustom('fenhong_money_weishu',$aid)){
                $commission_weishu = Db::name('admin_set')->where('aid',$aid)->value('fenhong_money_weishu');
            }
            if(getcustom('score_weishu',$aid)){
                $score_weishu = Db::name('admin_set')->where('aid',$aid)->value('score_weishu');
            }

            $return_type = $v['return_type'];//返现时间类型
            if($type != $return_type){
                return;
            }

            $return_day    = 0;//返现天数
            $delaysend_day = 0;//延迟天数
            $ave_num       = 0;//平均每次发放数值

            if($return_type == 1){
                if(getcustom('yx_cashback_time',$aid)){
                    //再次验证自定义天数返现
                    $return_day = $v['return_day'];//返现天数
                    if(getcustom('yx_cashback_time_delaysend')){
                        $delaysend_day = $v['delaysend_day'];//延迟天数
                    }
                    //如果天数小于2，则直接发放
                    if($return_day<=1){
                        $status   = 2;
                        $ave_num  = $back_price;
                        $send_num = $back_price;
                    }else{
                        $status   = 1;
                        //计算平均发放数值
                        $ave_num = $back_price/$return_day;
                        if(getcustom('yx_cashback_jiange_day')){
                            if(!empty($v['jiange_day']) && $v['jiange_day'] > 0){
                                $ave_num = $ave_num*$v['jiange_day'];
                            }
                        }

                        //计算此次发放数值
                        if($v['back_type'] == 1){
                            $send_num = $ave_num = dd_money_format($ave_num,$money_weishu);
                        }else if($v['back_type'] == 2){
                            $send_num = $ave_num = dd_money_format($ave_num,$commission_weishu);
                        }else{
                            $ave_num  = dd_money_format($ave_num,$score_weishu);
                            if($score_weishu ==0 && $ave_num<=1){
                                $ave_num = 1;
                            }
                            $send_num = $ave_num;
                        }
                        //处理特殊情况
                        if($send_num>=$back_price){
                            $send_num = $back_price;
                            $status   = 2;
                        }
                    }
                }
            }else if($return_type == 2){
                if(getcustom('yx_cashback_stage',$aid)){
                    //再次验证阶梯数值返现
                    $stagedata  = json_decode($v['stagedata'],true);
                    if(!$stagedata){
                        return;
                    }

                    $status     = 1;
                    $stageratio = 0;//获取阶梯返还比例
                    foreach($stagedata as $stage){
                        if($stage['stageday']<=1 && 1<=$stage['stageday2']){
                            $stageratio = $stage['stageratio'];
                        }
                        //获取最大返现天数
                        if($stage['stageday2']>$return_day){
                            $return_day = $stage['stageday2'];
                        }
                    }
                    if($return_day<=0){
                        return;
                    }

                    //计算此次发放数值
                    $send_num = $back_price*$stageratio*0.01;
                    if($v['back_type'] == 1){
                        $send_num = dd_money_format($send_num,$money_weishu);
                    }else if($v['back_type'] == 2){
                        $send_num = dd_money_format($send_num,$commission_weishu);
                    }else{
                        $send_num2  = dd_money_format($send_num,$score_weishu);
                        if($score_weishu ==0 && $send_num>0 && $send_num<=1){
                            $send_num = 1;
                        }else{
                            $send_num = $send_num2;
                        }
                    }

                    //处理特殊情况：如果返还天数小于2，或者此次发放数额大等于全部发放数额，则直接发放
                    if($return_day<=1 || $send_num>=$back_price){
                        $send_num = $back_price;
                        $status   = 2;
                    }
                }
            }else{
                $status   = 2;
                $ave_num  = $back_price;
                $send_num = $back_price;
            }
            if($type >= 1){
                //是否发放返现 -1 不发放也不记录发放值 0 : 不发放但记录发放值 1：发放 
                $sendstatus = 1;
            }else{
                $sendstatus = 0;
            }
            if(getcustom('yx_cashback_log',$aid)){
                if($sendstatus==1){
                    //第一次释放时先增加总额
                    self::cashback_log($aid,$mid,$v['id'],$og['id'],$back_price,'购物订单ID-'.$og['orderid'].'商品ID'.$og['proid'].'新增');
                }
            }
            $data = [];
            $data['cashback_id'] = $v['id'];//活动id
            //判定限额
            $cashback_member_check = Db::name('cashback_member')->where('aid',$aid)->where('mid',$mid)->where(['cashback_id'=>$v['id'],'pro_id'=>$og['proid'],'type'=>$canshtype])->find();
            if(getcustom('yx_cashback_time_delaysend',$aid)){
                //选项为自定义天数的延迟发放
                if($return_type == 1 && $delaysend_day>0){
                    $data['delaysend_day'] = $delaysend_day;
                    $nowday = strtotime(date("Y-m-d"));
                    $data['delaysend_starttime'] = strtotime(" +".$data['delaysend_day']." day",$nowday);
                    $status     = 1;
                    $sendstatus = -1;
                }
            }
            $remark = '每日释放';
            if($v['back_type'] == 1){//余额
                $data['moneystatus']    = $status;
                $data['allmoney']       = $back_price;//总返回数值
                $data['moneyave']       = $ave_num;//平均每次发放是数值
                $data['moneyday']       = $return_day;//发放多少天
                $data['money_sendtime'] = time();//发放时间
                $data['money_name']     = $v['name'];;//返现名称
                if($sendstatus >=0){
                    $data['money']         = $send_num;//已发放的数值
                    $data['money_sendnum'] = 1;//发放次数
                    if($sendstatus == 1){
                        if(getcustom('cashback_max')){
                            if($v['goods_multiple_max'] > 0){
                                if($cashback_member_check['cashback_money_max'] > $cashback_member_check['cashback_money']){
                                    //最大可追加金额
                                    $cashback_money_max = $cashback_member_check['cashback_money_max'] - $cashback_member_check['cashback_money'];
                                    if($cashback_money_max < $send_num){
                                        $send_num = $cashback_money_max;
                                    }    
                                }else{
                                   $send_num = 0;
                                }
                                if($send_num <=0){
                                    return;
                                }
                            }
                        }
                        //需要发放第一次
                        $receive_type = 0;//默认自动
                        if(getcustom('yx_cashback_addup_return')){
                            if($v['receive_type'] ==1){//手动领取
                                $receive_type =1;
                                \app\custom\OrderCustom::setCashbackAddupRecord($aid,$mid,$v['id'],$send_num,$v['back_type'],$remark);
                            }
                        }
                        if($receive_type ==0){
                            \app\common\Member::addmoney($aid,$mid,$send_num,$v['name'],0,'','',['type'=>'cashback']);
                        }
                        //累计到参与人统计表
                        Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('cashback_money',$send_num)->update();
                        //写入发放日志
                        $res_cashback_do_log = self::cashbackMemerDoLog($aid,$mid,$v,$og,$send_num,$remark);
                    }
                }
            }else if($v['back_type'] == 2){//佣金
                $data['commissionstatus']    = $status;
                $data['allcommission']       = $back_price;
                $data['commissionave']       = $ave_num;
                $data['commissionday']       = $return_day;
                $data['commission_sendtime'] = time();
                $data['commission_name']     = $v['name'];;//返现名称
                if($sendstatus >=0){
                    $data['commission']         = $send_num;//已发放的数值
                    $data['commission_sendnum'] = 1;//发放次数
                    if($sendstatus == 1){
                        if(getcustom('cashback_max')){
                            if($v['goods_multiple_max'] > 0){
                                if($cashback_member_check['cashback_money_max'] > $cashback_member_check['commission']){
                                    //最大可追加金额
                                    $cashback_money_max = $cashback_member_check['cashback_money_max'] - $cashback_member_check['commission'];
                                    if($cashback_money_max < $send_num){
                                        $send_num = $cashback_money_max;
                                    }    
                                            
                                }else{
                                    $send_num = 0;
                                }
                                if($send_num <=0){
                                    return ;
                                }
                            }
                        }
                        $receive_type = 0;//默认自动
                        //需要发放第一次
                        if(getcustom('yx_cashback_addup_return')){
                            if($v['receive_type'] ==1){//手动领取
                                $receive_type =1;
                                \app\custom\OrderCustom::setCashbackAddupRecord($aid,$mid,$v['id'],$send_num,$v['back_type'],$remark);
                            }
                        }
                        
                        if($receive_type ==0){
                            \app\common\Member::addcommission($aid,$mid,$mid,$send_num,$v['name']);
                        }
                        //累计到参与人统计表
                        Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('commission',$send_num)->update();
                        //写入发放日志
                        $res_cashback_do_log = self::cashbackMemerDoLog($aid,$mid,$v,$og,$send_num,$remark);
                    }
                }
            }else if($v['back_type'] == 3){//积分
                $data['scorestatus']    = $status;
                $data['allscore']       = $back_price;
                $data['scoreave']       = $ave_num;
                $data['scoreday']       = $return_day;
                $data['score_sendtime'] = time();
                $data['score_name']     = $v['name'];;//返现名称
                if($sendstatus >=0){
                    $data['score']         = $send_num;//已发放的数值
                    $data['score_sendnum'] = 1;//发放次数
                    if($sendstatus == 1){
                        if(getcustom('cashback_max')){
                            if($v['goods_multiple_max'] > 0){
                                if($cashback_member_check['cashback_money_max'] > $cashback_member_check['score']){
                                    //最大可追加金额
                                    $cashback_money_max = $cashback_member_check['cashback_money_max'] - $cashback_member_check['score'];
                                    if($cashback_money_max < $send_num){
                                        $send_num = $cashback_money_max;
                                    }    
                                    
                                }else{
                                    $send_num = 0;
                                }
                                if($send_num <=0){
                                    return ;
                                }
                            }
                        }
                        //需要发放第一次
                        $receive_type = 0;//默认自动
                        if(getcustom('yx_cashback_addup_return')){
                            if($v['receive_type'] ==1){//手动领取
                                $receive_type =1;
                                \app\custom\OrderCustom::setCashbackAddupRecord($aid,$mid,$v['id'],$send_num,$v['back_type'],$remark);
                            }
                        }
                        if($receive_type ==0){
                            \app\common\Member::addscore($aid,$mid,$send_num,$v['name']);
                        }
                        //累计到参与人统计表
                        Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('score',$send_num)->update();
                        //写入发放日志
                        $res_cashback_do_log = self::cashbackMemerDoLog($aid,$mid,$v,$og,$send_num,$remark);
                    }
                }
            }

            if(getcustom('yx_cashback_yongjin')){
                //记录累计返现金额
                Db::name('member')->where('id',$mid)->inc('cashback_total',$send_num)->update();
            }
            //查询是否有此商品返现表
            $goods_cashback = Db::name('shop_order_goods_cashback')->where('mid',$mid)->where('sog_id',$og['id'])->where('pro_id',$og['proid'])->where('back_type',$v['back_type'])->where('canshtype',$canshtype)->where('cashback_id',$v['id'])->field('id')->find();
            if($goods_cashback){
                $data['updatetime'] = time();
                Db::name('shop_order_goods_cashback')->where('id',$goods_cashback['id'])->update($data);
                $gcid = $goods_cashback['id'];
            }else{
                $data['aid']    = $og['aid'];
                $data['bid']    = $og['bid'];
                $data['mid']    = $mid;
                $data['sog_id'] = $og['id'];//订单商品id
                $data['pro_id'] = $og['proid'];//订单商品id
                $data['back_type'] = $v['back_type'];//返回类型 1：余额 2：佣金 3：积分
                $data['canshtype'] = $canshtype;//购物返回类型 如商城 shop

                //后补充的return_type 2024.12.10添加
                if(getcustom('yx_cashback_time') || getcustom('yx_cashback_stage')){
                    $data['return_type'] = $v['return_type'];
                }

                if(getcustom('yx_cashback_jiange_day',$aid)){
                    $data['jiange_day'] = $v['jiange_day'];
                    $data['return_type'] = $v['return_type'];
                }
                if(getcustom('yx_cashback_yongjin',$aid)){
                    $data['cashback_yongjin'] = $v['cashback_yongjin'];//抵扣返现
                }
                if(getcustom('yx_cashback_stage',$aid)){
                    //阶梯性返现
                    if($return_type == 2){
                        $data['return_type'] = 2;
                        $data['stagedata']   = $v['stagedata'];
                    }
                }
                $data['createtime'] = time();//后补加的
                if(getcustom('yx_cashback_pid',$aid)) {
                    $data['order_mid'] = $order_mid;
                }
                $gcid = Db::name('shop_order_goods_cashback')->insertGetId($data);
            }

            if(getcustom('yx_cashback_time_tjspeed') || getcustom('yx_cashback_time_teamspeed')){
                //返现时间自定义的加速返现
                if($return_type == 1 && $status == 1){
                    //再次查询
                    $where = [];
                    $where[] = ['id','=',$gcid];
                    if($v['back_type'] == 1){
                        $where[] = ['moneystatus','=',$status];
                    }else if($v['back_type'] == 2){
                        $where[] = ['commissionstatus','=',$status];
                    }else if($v['back_type'] == 3){
                        $where[] = ['scorestatus','=',$status];
                    }
                    if(getcustom('yx_cashback_time_tjspeed')){
                        //用户加速余额加速
                        $cashback_speed_num = Db::name('member')->where('id',$mid)->value('cashback_speed_num');
                        if($cashback_speed_num>0){
                            $mv = Db::name('shop_order_goods_cashback')->where($where)->find();
                            if($mv){
                                $res = self::deal_cashbacklist($mv,['isspeed'=>3,'cashback_speed'=>$cashback_speed_num,'remark'=>'加速余额释放','nocheckstatus'=>true]);
                                if($res && $res['status'] == 1){
                                    if($res['param']['cashback_speed']<=0){
                                        Db::name('member')->where('id',$mid)->dec('cashback_speed_num',$cashback_speed_num)->update();
                                    }else{
                                        $decnum = $cashback_speed_num - $res['param']['cashback_speed'];
                                        Db::name('member')->where('id',$mid)->dec('cashback_speed_num',$decnum)->update();
                                    }
                                }
                            }
                        }
                    }
                    if(getcustom('yx_cashback_time_teamspeed')){
                        //团队业绩达标加速
                        if(!empty($v['teamspeeddata'])){
                            $mv = Db::name('shop_order_goods_cashback')->where($where)->find();
                            if($mv){
                                $teamspeeddata = json_decode($v['teamspeeddata'],true);
                                $downmids = \app\common\Member::getteammids($aid,$mid);
                                if($downmids && $teamspeeddata){
                                    //查询团队业绩
                                    $yejiwhere = [];
                                    $yejiwhere[] = ['status','=','3'];
                                    $teamyeji = Db::name('shop_order_goods')->where('aid',$aid)->where('mid','in',$downmids)->where($yejiwhere)->sum('real_totalprice');

                                    $teamspeed_money  = 0;
                                    $team_speed       = 0;
                                    foreach($teamspeeddata as $tv){
                                        if($teamyeji>=$teamspeed_money && $teamyeji>=$tv['money']){
                                            $teamspeed_money = $tv['money'];
                                            $team_speed      = $back_price*$tv['speed']/100;
                                            $team_speed      = round($team_speed,2);
                                        }
                                    }
                                    unset($tv);
                                    if($team_speed>0){
                                        $res = self::deal_cashbacklist($mv,['isspeed'=>2,'team_speed'=>$team_speed,'teamspeed'=>$team_speed,'teamspeed_yeji'=>$teamyeji,'teamspeed_money'=>$teamspeed_money,'remark'=>'团队业绩达标加速','nocheckstatus'=>true]);
                                        if($res && $res['status'] == 1 && $res['param']['team_speed']>0){
                                            //增加加速余额
                                            Db::name('member')->where('id',$mid)->inc('cashback_speed_num',$res['param']['team_speed'])->update();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // 释放加速
            if(getcustom('yx_cashback_time',$aid) && !empty($send_num)){
                self::shifangJiasu($aid,$mid,$send_num,$v['back_type']);
            }
        }
    }

     //aid为0 定时任务查询（分页查询），其他的则查询全部 ；type 0：未查询过 1：已查询过
    public function deal_autocashback($aid=0,$type=0)
    {
        if(getcustom('yx_cashback_time') || getcustom('yx_cashback_stage')){
            // 自定义每天返现
            //读取配置
            if($aid){
                $syssetlist = Db::name('admin_set')->where('aid',$aid)->field('id,aid')->select()->toArray();
            }else{
                $syssetlist = Db::name('admin_set')->where('1=1')->field('id,aid')->select()->toArray();
            }
            if(!$syssetlist) return;

            //今天
            $today = strtotime(date("Y-m-d",time()));
            if($aid){
                foreach($syssetlist as $sv){
                    //余额返现
                    self::deal_moneycashback($sv,$today);
                    //佣金返现
                    self::deal_commissioncashback($sv,$today);
                    //积分返现
                    self::deal_scorecashback($sv,$today);
                }
            }else{
                //定时任务
                foreach($syssetlist as $sv){
                    //分页查询，读取上一次查询的最后记录
                    $logid = cache('sales_aid_money_'.$sv['aid'])??'';
                    if(!$logid) $logid = 0;
                    $todayend = cache('sales_aid_money_'.$sv['aid'].'_todayend_'.$today)??'';
                    if(!$todayend){
                        //余额返现
                        self::deal_moneycashback($sv,$today,1,$logid);
                    }
                    //分页查询，读取上一次查询的最后记录
                    $logid2 = cache('sales_aid_commission_'.$sv['aid'])??'';
                    if(!$logid2) $logid2 = 0;
                    $todayend2 = cache('sales_aid_commission_'.$sv['aid'].'_todayend_'.$today)??'';
                    if(!$todayend2){
                        //佣金返现
                        self::deal_commissioncashback($sv,$today,1,$logid2);
                    }
                    //分页查询，读取上一次查询的最后记录
                    $logid3 = cache('sales_aid_score_'.$sv['aid'])??'';
                    if(!$logid3) $logid3 = 0;
                    $todayend2 = cache('sales_aid_score_'.$sv['aid'].'_todayend_'.$today)??'';
                    if(!$todayend2){
                        //积分返现
                        self::deal_scorecashback($sv,$today,1,$logid3);
                    }
                }
            }
        }
    }

    //倍增返现只要业绩达到增长比例就返现，不用非得每天一次所以和上面的区分两个方法
    public function deal_autocashback_multiply($aid=0)
    {
        if(getcustom('yx_cashback_multiply')){
            // 自定义每天返现
            //读取配置
            if($aid){
                $syssetlist = Db::name('admin_set')->where('aid',$aid)->select()->toArray();
            }else{
                $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();
            }
            if($syssetlist){
                //今天
                foreach($syssetlist as $sv){
                    //倍增方式返现
                    self::deal_multiplecashback($sv);
                }
            }
        }
    }

    //type 0：默认不限制查询数量 1 限制查询数量(定时任务使用)
    private static function deal_moneycashback($sv,$today,$type=0,$logid=0)
    {
        if(getcustom('yx_cashback_time') || getcustom('yx_cashback_stage')){
            //余额返现
            $where = [];
            if($logid>0){
                $where[] = ['id','>=',$logid];
            }
            $where[] = ['aid','=',$sv['aid']];
            $where[] = ['back_type','=',1];
            $where[] = ['moneystatus','=',1];
            $where[] = ['money_sendtime','<',$today];
            if(getcustom('yx_cashback_yongjin')){
                $where[] = ['cashback_yongjin','<>',2];
            }
            $moneylist = Db::name('shop_order_goods_cashback')
                ->where($where);
            if($type == 1){
                $moneylist = $moneylist->limit(80);
            }
            $moneylist = $moneylist->order('id asc')
                ->select()
                ->toArray();
            if($moneylist){
                foreach($moneylist as $mv){
                    //倍增返现单独处理
                    if($mv['return_type'] == 3) continue;

                    if(getcustom('yx_cashback_time_delaysend')){
                        //延迟发放
                        if($mv['delaysend_starttime']>time()){
                            //只更新发放时间
                            Db::name('shop_order_goods_cashback')->where('id',$mv['id'])->update(['money_sendtime'=>time()]);
                            continue;
                        }
                    }
                    // 间隔发放
                    if(getcustom('yx_cashback_time') && $mv['return_type'] == 1){
                        if(getcustom('yx_cashback_jiange_day') && !empty($mv['jiange_day']) && $mv['jiange_day'] > 0){
                            if($mv['money_sendtime'] + $mv['jiange_day']*86400 > time()){
                                continue;
                            }
                        }
                    }

                    //走统一的处理方法
                    $res = self::deal_cashbacklist($mv);
                    if($res['status'] == 0){
                        continue;
                    }
                }

                if($type == 1){
                    cache('sales_aid_money_'.$sv['aid'],$mv['id']);
                }
            }else{
                if($type == 1){
                    cache('sales_aid_money_'.$sv['aid'],0);
                    cache('sales_aid_money_'.$sv['aid'].'_todayend_'.$today,1);
                    return;
                }
            }
        }
    }

    //type 0：默认不限制查询数量 1 限制查询数量(定时任务使用)
    private static function deal_commissioncashback($sv,$today,$type =0,$logid=0)
    {
        if(getcustom('yx_cashback_time') || getcustom('yx_cashback_stage')){
            //佣金返现
            $where = [];
            if($logid>0){
                $where[] = ['id','>=',$logid];
            }
            $where[] = ['aid','=',$sv['aid']];
            $where[] = ['back_type','=',2];
            $where[] = ['commissionstatus','=',1];
            $where[] = ['commission_sendtime','<',$today];
            if(getcustom('yx_cashback_yongjin')){
                $where[] = ['cashback_yongjin','<>',2];
            }
            $commissionlist = Db::name('shop_order_goods_cashback')
                ->where($where);
            if($type == 1){
                $commissionlist = $commissionlist->limit(80);
            }
            $commissionlist = $commissionlist->order('id asc')
                ->select()
                ->toArray();
            if($commissionlist){
                foreach($commissionlist as $mv){
                    if($mv['return_type'] == 3) continue;

                    if(getcustom('yx_cashback_time_delaysend')){
                        //延迟发放
                        if($mv['delaysend_starttime']>time()){
                            //只更新发放时间
                            Db::name('shop_order_goods_cashback')->where('id',$mv['id'])->update(['commission_sendtime'=>time()]);
                            continue;
                        }
                    }
                    // 间隔发放
          
                    if(getcustom('yx_cashback_time') && $mv['return_type'] == 1){
                        if(getcustom('yx_cashback_jiange_day') && !empty($mv['jiange_day']) && $mv['jiange_day'] > 0){
                            if($mv['commission_sendtime'] + $mv['jiange_day']*86400 > time()){
                                continue;
                            }
                        }
                    }

                    //走统一的处理方法
                    $res = self::deal_cashbacklist($mv);
                    if($res['status'] == 0){
                        continue;
                    }
                }

                if($type == 1){
                    cache('sales_aid_commission_'.$sv['aid'],$mv['id']);
                }
            }else{
                if($type == 1){
                    cache('sales_aid_commission_'.$sv['aid'],0);
                    cache('sales_aid_commission_'.$sv['aid'].'_todayend_'.$today,1);
                    return;
                }
            }
        }
    }

    //type 0：默认不限制查询数量 1 限制查询数量(定时任务使用)
    private static function deal_scorecashback($sv,$today,$type =0,$logid=0)
    {
        if(getcustom('yx_cashback_time') || getcustom('yx_cashback_stage')){
            //积分返现
            $where = [];
            if($logid>0){
                $where[] = ['id','>=',$logid];
            }
            $where[] = ['aid','=',$sv['aid']];
            $where[] = ['back_type','=',3];
            $where[] = ['scorestatus','=',1];
            $where[] = ['score_sendtime','<',$today];
            if(getcustom('yx_cashback_yongjin')){
                $where[] = ['cashback_yongjin','<>',2];
            }
            $scorelist = Db::name('shop_order_goods_cashback')
                ->where($where);
            if($type == 1){
                $scorelist = $scorelist->limit(80);
            }
            $scorelist = $scorelist ->order('id asc')
                ->select()
                ->toArray();
            if($scorelist){
                foreach($scorelist as $mv){
                    if($mv['return_type'] == 3) continue;

                    if(getcustom('yx_cashback_time_delaysend')){
                        //延迟发放
                        if($mv['delaysend_starttime']>time()){
                            //只更新发放时间
                            Db::name('shop_order_goods_cashback')->where('id',$mv['id'])->update(['score_sendtime'=>time()]);
                            continue;
                        }
                    }
                    // 间隔发放
                    if(getcustom('yx_cashback_time') && $mv['return_type'] == 1){
                        if(getcustom('yx_cashback_jiange_day') && !empty($mv['jiange_day']) && $mv['jiange_day'] > 0){
                            if($mv['score_sendtime'] + $mv['jiange_day']*86400 > time()){
                                continue;
                            }
                        }
                    }
                    //走统一的处理方法
                    $res = self::deal_cashbacklist($mv);
                    if($res['status'] == 0){
                        continue;
                    }
                }

                if($type == 1){
                    cache('sales_aid_score_'.$sv['aid'],$mv['id']);
                }
            }else{
                if($type == 1){
                    cache('sales_aid_score_'.$sv['aid'],0);
                    cache('sales_aid_score_'.$sv['aid'].'_todayend_'.$today,1);
                    return;
                }
            }
        }
    }
    public static function deal_multiplecashback($sv,$sog_id=0)
    {
        if(getcustom('yx_cashback_multiply') ){
            //积分返现
            $where = [];
            $where[] = ['aid','=',$sv['aid']];
            $where[] = ['status','<>',2];
            $where[] = ['return_type','=',3];//倍增返现单独处理
            if(getcustom('yx_cashback_yongjin')){
                $where[] = ['cashback_yongjin','<>',2];
            }
            if($sog_id){
                $where[] = ['sog_id','=',$sog_id];
            }
            $scorelist = Db::name('shop_order_goods_cashback')
                ->where($where)
                ->order('id asc')
                ->select()
                ->toArray();
            if($scorelist){
                //获取当期平台利润作为业绩
                $res_lirun = self::getPlateLirun($sv['aid']);
                foreach($scorelist as $mv){
                    //走统一的处理方法
                    $res = self::deal_cashbacklist($mv,$res_lirun);
                    if($res['status'] == 0){
                        continue;
                    }
                }
            }
        }
    }

    public static function deal_collagecashback($aid,$order)
    {
        if(getcustom('yx_cashback_collage',$aid)){
            //购物返现
            $cashbacklist = Db::name('cashback')
                ->where('aid',$aid)
                ->where('bid',0)
                ->where('fwtype',3)
                ->where('starttime','<',$order['paytime'])
                ->where('endtime','>',$order['paytime'])
                ->order('sort desc')
                ->select()->toArray();

            //查询购买用户
            $member = Db::name('member')->where('id',$order['mid'])->find();
            if($member && $cashbacklist){
                //返现类型 1、余额 2、佣金 3、积分 小数位数
                $money_weishu = 2;$commission_weishu = 2;$score_weishu = 0;
                if(getcustom('member_money_weishu')){
                    $money_weishu = Db::name('admin_set')->where('aid',$aid)->value('member_money_weishu');
                }
                if(getcustom('fenhong_money_weishu')){
                    $commission_weishu = Db::name('admin_set')->where('aid',$aid)->value('fenhong_money_weishu');
                }
                if(getcustom('score_weishu')){
                    $score_weishu = Db::name('admin_set')->where('aid',$aid)->value('score_weishu');
                }
                $product = Db::name('collage_product')->where('id',$order['proid'])->field('id')->find();
                if($product){
                    foreach($cashbacklist as $v){

                        if(getcustom('yx_cashback_collage_moneyreturn')){
                            //判断余额支付返还情况
                            if($order['paytypeid'] == 1){
                                if($order['buytype'] == 1){
                                    //单独 若单购余额返设置为关闭
                                    if($v['alone_moneyreturn'] != 1){
                                        continue;
                                    }
                                }else if($order['buytype'] == 2){
                                    //拼团 团长 若拼团余额返还设置为关闭
                                    if(!$v['team_moneyreturn']){
                                        continue;
                                    }
                                }else{
                                    //拼团 团员 若拼团余额返还设置不是全都返还
                                    if($v['team_moneyreturn'] != 1){
                                        continue;
                                    }
                                }
                            }
                        }

                        $gettj = explode(',',$v['gettj']);
                        if(!in_array('-1',$gettj) && !in_array($member['levelid'],$gettj)){ //不是所有人
                            continue;
                        }

                        $collageids = explode(',',$v['collageids']);
                        if(!in_array($product['id'],$collageids)){
                            continue;
                        }

                        $back_ratio = $v['back_ratio'];//返现利率
                        //如果返现利率大于0
                        if($back_ratio>0){
                            //计算返现
                            $back_price = $back_ratio*$order['totalprice']/100;

                            //返现类型 1、余额 2、佣金 3、积分
                            if($v['back_type'] == 1 ){
                                $back_price = dd_money_format($back_price,$money_weishu);
                            }else if($v['back_type']== 2){
                                $back_price = dd_money_format($back_price,$commission_weishu);
                            }else if($v['back_type'] == 3){
                                $back_price = dd_money_format($back_price,$score_weishu);
                            }

                            $return_type = 0;//发放状态
                            if(getcustom('yx_cashback_time') || getcustom('yx_cashback_stage')){
                                $return_type = $v['return_type'];
                            }

                            //构建商品信息
                            $og = [];
                            $og['aid']   = $order['aid'];
                            $og['bid']   = $order['bid'];
                            $og['mid']   = $order['mid'];
                            $og['id']    = $order['id'];
                            $og['proid'] = $order['proid'];
                            $og['ordertype']  = 'collage';

                            //记录参与的会员
                            $cashback_member_check = Db::name('cashback_member')->where(['cashback_id'=>$v['id'],'pro_id'=>$og['proid'],'type'=>'collage'])->where('mid',$order['mid'])->where('aid',$order['aid'])->find();
                            if(!$cashback_member_check){
                                $cashback_member = [];
                                $cashback_member['aid']          = $order['aid'];
                                $cashback_member['mid']          = $order['mid'];
                                $cashback_member['cashback_id']  = $v['id'];
                                $cashback_member['back_type']    = $v['back_type'];
                                $cashback_member['pro_id']       = $order['proid'];
                                $cashback_member['pro_num']      = $order['num'];
                                $cashback_member['type']               = 'collage';
                                $cashback_member['cashback_money_max'] = 0;
                                $cashback_member['create_time']        = time();
                                $insert = Db::name('cashback_member')->insert($cashback_member);
                                $cashback_member_check = Db::name('cashback_member')->where(['cashback_id'=>$v['id'],'pro_id'=>$og['proid'],'type'=>'collage'])->where('mid',$order['mid'])->where('aid',$order['aid'])->find();
                            }else{
                                Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('pro_num',$order['num'])->update();
                            }

                            if(!$return_type){
                                if($back_price>0){
                                    if($v['back_type'] == 1 ){
                                        \app\common\Member::addmoney($aid,$order['mid'],$back_price,$v['name'],0,'','',['type'=>'cashback']);
                                        //累计到参与人统计表
                                        Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('cashback_money',$back_price)->update();
                                    }
                                    if($v['back_type'] == 2){
                                        \app\common\Member::addcommission($aid,$order['mid'],$order['mid'],$back_price,$v['name']);
                                        //累计到参与人统计表
                                        Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('commission',$back_price)->update();
                                    }
                                    if($v['back_type'] == 3){
                                        \app\common\Member::addscore($aid,$order['mid'],$back_price,$v['name']);
                                        //累计到参与人统计表
                                        Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('score',$back_price)->update();
                                    }
                                    if(getcustom('yx_cashback_time')){
                                        //直接发放
                                        \app\custom\OrderCustom::deal_first_cashback($aid,$order['mid'],$back_price,$og,$v,0,'collage');
                                    }
                                    //写入发放日志
                                    \app\custom\OrderCustom::cashbackMemerDoLog($order['aid'],$order['mid'],$v,$og,$back_price);
                                }
                            }else{
                                if($back_price>0){
                                    if(getcustom('yx_cashback_time') || getcustom('yx_cashback_stage')){
                                        //处理自定义第一次发放
                                        \app\custom\OrderCustom::deal_first_cashback($aid,$order['mid'],$back_price,$og,$v,$return_type,'collage');
                                    }
                                }
                            }
                        }

                    }
                }

            }
        }
    }

    public static function deal_invitecashback($aid,$order,$oglist,$member){
        if(getcustom('yx_invite_cashback',$aid)){
            //邀请返现
            //查询上级是否有分销权限
            $parent = Db::name('member')
                ->alias('m')
                ->join('member_level ml','ml.id = m.levelid')
                ->where('m.id',$member['pid'])
                ->where('ml.can_agent','>',0)
                ->where('m.aid',$aid)
                ->field('m.id,m.levelid')
                ->find();
            if($parent){
                foreach($oglist as $og){

                    //查询商品
                    $product = Db::name('shop_product')
                        ->where('id',$og['proid'])
                        ->field('id,bid,cid')
                        ->find();
                    if($product){

                        //查询邀请返现设置
                        $iclist = Db::name('invite_cashback')
                            ->where('aid',$aid)
                            ->where('bid',0)
                            ->where('starttime','<',$order['paytime'])
                            ->where('endtime','>',$order['paytime'])
                            ->order('sort desc')
                            ->select()
                            ->toArray();
                        if($iclist){

                            //能发放奖励的列表
                            $new_iclist = [];
                            //查询是否有单独商品设置 若有则只发单独设置的返现设置
                            $alone   = false;

                            foreach($iclist as $v){
                                //返现设置是否存在
                                $v['invite_cashbak_data']  = $v['invite_cashbak_data'] ? json_decode($v['invite_cashbak_data'],true) : [];
                                if(empty($v['invite_cashbak_data'])){
                                    continue;
                                }

                                $gettj = explode(',',$v['gettj']);
                                if(!in_array('-1',$gettj) && !in_array($parent['levelid'],$gettj)){ //不是所有人
                                    continue;
                                }

                                if($v['fwtype']>=3){
                                    //其他类型不适应
                                    continue;
                                }

                                //指定商品:若指定商品，则不走所有商品及指定类目设置
                                if($v['fwtype']==2){
                                    $productids = explode(',',$v['productids']);
                                    if(!in_array($product['id'],$productids)){
                                        continue;
                                    }
                                    $alone = true;

                                    //如果需上级购买商品
                                    if($v['needbuy'] == 1){
                                        //查询上级是否买过此商品
                                        $ogoods = Db::name('shop_order_goods')
                                            ->alias('og')
                                            ->join('shop_order o','o.id=og.orderid')
                                            ->where('og.proid',$og['proid'])
                                            ->where('o.mid',$parent['id'])
                                            ->where('o.status','in','1,2,3')
                                            ->where('o.aid',$aid)
                                            ->field('og.id')
                                            //->order('o.status desc')
                                            ->find();
                                        if(!$ogoods){
                                            continue;
                                        }
                                    }

                                }else{
                                    //若指定商品，则不走所有商品及指定类目设置
                                    if($alone){
                                        continue;
                                    }

                                    if($v['fwtype']==1){//指定类目可用
                                        $categoryids = explode(',',$v['categoryids']);
                                        $cids  = explode(',',$product['cid']);
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

                                    //如果需上级购买商品
                                    if($v['needbuy'] == 1){
                                        $where = [];
                                        $where[] = ['o.mid','=',$parent['id']];
                                        $where[] = ['o.status','in','1,2,3'];

                                        if($v['fwtype']==1){
                                            $whereCid = [];
                                            foreach($categoryids as $cid){
                                                $whereCid[] = "find_in_set({$cid},p.cid)";
                                            }
                                            $where[] = Db::raw(implode(' or ',$whereCid));
                                        }

                                        $where[] = ['o.aid','=',$aid];
                                        //查询上级是否买过商品
                                        $ogoods = Db::name('shop_order_goods')
                                            ->alias('og')
                                            ->join('shop_order o','o.id=og.orderid')
                                            ->join('shop_product p','p.id=og.proid')
                                            ->where($where)
                                            ->field('og.id')
                                            //->order('o.status desc')
                                            ->find();
                                        if(!$ogoods){
                                            continue;
                                        }
                                    }
                                }

                                //是否开启复购：若没开启，则每个邀请人一种商品只能发一次，开启则无邀请人数限制
                                if(!$v['isagain']){
                                    //查询此用户此商品是否已给上级记录过，记录过则不在发放
                                    $count = Db::name('member_invite_cashback_log')
                                        ->where('order_mid',$member['id'])
                                        ->where('mid',$parent['id'])
                                        ->where('cashback_id',$v['id']);
                                    if($v['fwtype']==2){
                                        $count = $count->where('proid',$og['proid']);
                                    }
                                    $count = $count
                                        ->where('status','>=',0)
                                        ->count();
                                    if($count){
                                        continue;
                                    }
                                }

                                //查询上级已返现过的次数
                                $pnum = Db::name('member_invite_cashback_log')
                                    ->where('mid',$parent['id']);
                                if($v['fwtype']==2){
                                    $pnum = $pnum->where('proid',$og['proid']);
                                }
                                $pnum = $pnum
                                    ->where('cashback_id',$v['id'])
                                    ->count();
                                $v['pnum'] = 0 + $pnum;

                                //查询设置的推N返一次数
                                $v['icnum'] = count($v['invite_cashbak_data']);

                                //若没有开启循环，则只按顺序发一次
                                if(!$v['iscycle'] && $v['pnum']>=$v['icnum']){
                                    continue;
                                }

                                //查询已循环的次数
                                $num = floor($v['pnum'] / $v['icnum']);
                                //如果开启循环，且开启了循环次数限制，则需要查看循环几次后固定发奖设置
                                if($v['iscycle'] && $v['cyclenum']>0 && $num>=$v['cyclenum']){
                                    //若固定发奖未设置则跳过发放
                                    if($v['cyclemoney'] <=0 && $v['cyclescore']<=0 && $v['cyclecommission']<=0 && $v['cyclemoney2'] <=0 && $v['cyclescore2']<=0 && $v['cyclecommission2']<=0){
                                        continue;
                                    }
                                }

                                array_push($new_iclist,$v);
                            }
                            unset($v);

                            //如果可以发放
                            if($new_iclist){
                                foreach($new_iclist as $v){
                                    //若有单独设置则只发单独设置的返现设置
                                    if($alone){
                                        if($v['fwtype']==2){
                                            //返现金额
                                            $back_price = $og['real_totalprice'];
                                            if($back_price>0){
                                                //发放返回
                                                $mid_order_gid = $ogoods && $ogoods['id']?$ogoods['id']:0;
                                                //self::deal_sendback($aid,$order,$og,$parent,$v,$back_price,$mid_order_gid);
                                                self::deal_sendbackdata($aid,$order,$og,$parent,$v,$back_price,1,$params=['mid_order_gid'=>$mid_order_gid]);
                                            }
                                        }
                                    }else{
                                        //返现金额
                                        $back_price = $og['real_totalprice'];
                                        if($back_price>0){
                                            //发放返回
                                            $mid_order_gid = $ogoods && $ogoods['id']?$ogoods['id']:0;
                                            //self::deal_sendback($aid,$order,$og,$parent,$v,$back_price,$mid_order_gid);
                                            self::deal_sendbackdata($aid,$order,$og,$parent,$v,$back_price,1,$params=['mid_order_gid'=>$mid_order_gid]);
                                        }
                                    }
                                }
                                unset($v);
                            }
                        }
                        
                    }
                }
            }
        }
    }

    public static function deal_ictips($aid,$mid,$type=0,$prodoucts=[]){
        if(getcustom('yx_invite_cashback',$aid)){

            if($type == 1){
                $payorder = $prodoucts;
                if($payorder['type'] == 'shop'){
                    $proids = Db::name('shop_order_goods')->where('orderid',$payorder['orderid'])->where('aid',$aid)->column('proid');
                }else{
                    $proids = Db::name('shop_order_goods')->where('ordernum', 'like',  $payorder['ordernum'] . '_%' )->where('aid',$aid)->column('proid');
                }
                if($proids){
                    $where   = [];
                    $where[] = ['id','in',$proids];
                    $where[] = ['aid','=',$aid];
                    $nowtime   = time();
                    $nowhm     = date('H:i');
                    $where[] = Db::raw("`status`=1 or (`status`=2 and unix_timestamp(start_time)<=$nowtime and unix_timestamp(end_time)>=$nowtime) or (`status`=3 and ((start_hours<end_hours and start_hours<='$nowhm' and end_hours>='$nowhm') or (start_hours>=end_hours and (start_hours<='$nowhm' or end_hours>='$nowhm'))) )");
                    $where[] = ['ischecked','=',1];
                    $prodoucts = Db::name('shop_product')->where($where)->field('id,aid,bid,cid,pic,name')->select()->toArray();
                }else{
                    $prodoucts = [];
                }
            }

            $member = Db::name('member')
                ->alias('m')
                ->join('member_level ml','ml.id = m.levelid')
                ->where('m.id',$mid)
                ->where('ml.can_agent','>',0)
                ->where('m.aid',$aid)
                ->field('m.id,m.levelid')
                ->find();

            $data = [];
            $data['ictips']  = '';
            $data['proid']   = '';
            $data['propic']  = '';
            $data['proname'] = '';
            if($prodoucts){
                //查询邀请返现
                $iclist = Db::name('invite_cashback')
                    ->where('aid',$aid)
                    ->where('bid',0)
                    ->where('starttime','<',time())
                    ->where('endtime','>',time())
                    ->order('sort desc')
                    ->select()
                    ->toArray();

                if($iclist){
                    //能发放奖励的列表
                    $new_iclist = [];
                    //查询是否有单独商品设置 若有则只发单独设置的返现设置
                    $alone   = false;
                    foreach($prodoucts as $product){
                        if($data['ictips']){
                            break;
                        }
                        foreach($iclist as $v){

                            //返现设置是否存在
                            $v['invite_cashbak_data']  = $v['invite_cashbak_data'] ? json_decode($v['invite_cashbak_data'],true) : [];
                            if(empty($v['invite_cashbak_data'])){
                                continue;
                            }

                            $gettj = explode(',',$v['gettj']);
                            if(!in_array('-1',$gettj) && !in_array($member['levelid'],$gettj)){ //不是所有人
                                continue;
                            }

                            if($v['fwtype']>=3){
                                //其他类型不适应
                                continue;
                            }

                            if($v['fwtype']==2){//指定商品可用
                                $productids = explode(',',$v['productids']);
                                if(!in_array($product['id'],$productids)){
                                    continue;
                                }
                                $alone = true;
                            }else if($v['fwtype']==1){//指定类目可用
                                $categoryids = explode(',',$v['categoryids']);
                                $cids  = explode(',',$product['cid']);
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
                            array_push($new_iclist,$v);
                        }
                        //如果可以发放
                        if($new_iclist){
                            foreach($new_iclist as $v){
                                //若有单独设置则只发单独设置的返现设置
                                if($alone){
                                    if($v['fwtype']==2){
                                        //计算N数值
                                        $icnum = count($v['invite_cashbak_data']);
                                        if($icnum>0){
                                            $ictips = '';
                                            if($v['tiptype'] && $v['tiptype'] == 1){
                                                $ictips = !empty($v['tiptext'])?$v['tiptext']:'';
                                            }else{
                                                $ictips = '推荐'.$icnum.'个人消费全返';
                                            }
                                            if($type){
                                                $ictips = $ictips.'，点击分享好友';
                                            }
                                            $data['ictips']  = $ictips;
                                            $data['proid']   = $product['id'];
                                            $data['propic']  = $product['pic'];
                                            $data['proname'] = $product['name'];
                                        }
                                    }
                                }else{
                                    //计算N数值
                                    $icnum = count($v['invite_cashbak_data']);
                                    if($icnum>0){
                                        $ictips = '';
                                        if($v['tiptype'] && $v['tiptype'] == 1){
                                            $ictips = !empty($v['tiptext'])?$v['tiptext']:'';
                                        }else{
                                            $ictips = '推荐'.$icnum.'个人消费全返';
                                        }
                                        if($type){
                                            $ictips = $ictips.'，点击分享好友';
                                        }
                                        $data['ictips']  = $ictips;
                                        $data['proid']   = $product['id'];
                                        $data['propic']  = $product['pic'];
                                        $data['proname'] = $product['name'];
                                    }
                                }
                            }
                            unset($v);
                        }
                    }
                    
                }
            }
            return $data;
        }
    }

    public static function deal_invitecashback2($aid,$order,$oglist,$member){
        if(getcustom('yx_invite_cashback',$aid)){
            //处理邀请邀请返现
            foreach($oglist as $og){

                //查询商品
                $product = Db::name('shop_product')
                    ->where('id',$og['proid'])
                    ->field('id,bid,cid')
                    ->find();
                if(!$product){
                    Db::name('member_invite_cashback_log')->where('proid',$og['proid'])->where('status',0)->update(['status'=>-1,'update_time'=>time(),'cancel_time'=>time(),'reason'=>'商品不存在']);
                    continue;
                }

                //查询记录是否存在
                $loglist = Db::name('member_invite_cashback_log')
                    ->where('order_id',$order['id'])
                    ->where('proid',$og['proid'])
                    ->where('order_mid',$order['mid'])
                    ->where('status',0)
                    ->select()
                    ->toArray();
                if($loglist){

                    foreach($loglist as $lv){

                        //查询上级是否有分销权限
                        $parent = Db::name('member')
                            ->alias('m')
                            ->join('member_level ml','ml.id = m.levelid')
                            ->where('m.id',$lv['mid'])
                            ->where('ml.can_agent','>',0)
                            ->where('m.aid',$aid)
                            ->field('m.id,m.levelid')
                            ->find();
                        if(!$parent){
                            Db::name('member_invite_cashback_log')->where('id',$lv['id'])->where('status',0)->update(['status'=>-1,'update_time'=>time(),'cancel_time'=>time(),'reason'=>'上级不存在或无分销权限']);
                            continue;
                        }

                        //查询邀请返现
                        $set = Db::name('invite_cashback')
                            ->where('id',$lv['cashback_id'])
                            ->where('aid',$aid)
                            ->where('bid',0)
                            ->where('starttime','<',$order['paytime'])
                            ->where('endtime','>',$order['paytime'])
                            ->find();
                        if(!$set){
                            Db::name('member_invite_cashback_log')->where('id',$lv['id'])->where('status',0)->update(['status'=>-1,'update_time'=>time(),'cancel_time'=>time(),'reason'=>'邀请返现活动不存在或已结束']);
                            continue;
                        }

                        if(getcustom('yx_invite_cashback_ordertj')){
                            //判断是否是确认收货后发放,不是则不发放
                            if($set['ordertj'] || $set['ordertj'] == 1){
                                continue;
                            }
                        }

                        if($set['fwtype']==2){
                            $productids = explode(',',$set['productids']);
                            if(!in_array($product['id'],$productids)){
                                Db::name('member_invite_cashback_log')->where('id',$lv['id'])->where('status',0)->update(['status'=>-1,'update_time'=>time(),'cancel_time'=>time(),'reason'=>'邀请活动已失效']);
                                continue;
                            }

                            //如果需上级购买商品
                            if($set['needbuy'] == 1){
                                //再验证上级是否买过此商品
                                $ogoods = Db::name('shop_order_goods')
                                    ->alias('og')
                                    ->join('shop_order o','o.id=og.orderid')
                                    ->where('og.proid',$og['proid'])
                                    ->where('o.mid',$lv['mid'])
                                    ->where('o.status',3)
                                    ->where('o.aid',$aid)
                                    ->field('og.id')
                                    ->find();
                                if(!$ogoods){
                                    Db::name('member_invite_cashback_log')->where('id',$lv['id'])->where('status',0)->update(['status'=>-1,'update_time'=>time(),'cancel_time'=>time(),'reason'=>'上级购买此商品记录不存在或订单未完成']);
                                    continue;
                                }
                            }
                            $alone = true;
                        }else{
                            if($set['fwtype']==1){//指定类目可用
                                $categoryids = explode(',',$set['categoryids']);
                                $cids  = explode(',',$product['cid']);
                                $clist = Db::name('shop_category')->where('pid','in',$categoryids)->select()->toArray();
                                foreach($clist as $vc){
                                    $categoryids[] = $vc['id'];
                                    $cate2 = Db::name('shop_category')->where('pid',$vc['id'])->find();
                                    $categoryids[] = $cate2['id'];
                                }
                                if(!array_intersect($cids,$categoryids)){
                                    Db::name('member_invite_cashback_log')->where('id',$lv['id'])->where('status',0)->update(['status'=>-1,'update_time'=>time(),'cancel_time'=>time(),'reason'=>'邀请活动已失效']);
                                    continue;
                                }
                            }

                            //如果需上级购买商品
                            if($set['needbuy'] == 1){
                                $where = [];
                                $where[] = ['o.mid','=',$lv['mid']];
                                $where[] = ['o.status','=',3];

                                if($set['fwtype']==1){
                                    $whereCid = [];
                                    foreach($categoryids as $cid){
                                        $whereCid[] = "find_in_set({$cid},p.cid)";
                                    }
                                    $where[] = Db::raw(implode(' or ',$whereCid));
                                }

                                $where[] = ['o.aid','=',$aid];
                                //再验证上级是否买过商品
                                $ogoods = Db::name('shop_order_goods')
                                    ->alias('og')
                                    ->join('shop_order o','o.id=og.orderid')
                                    ->join('shop_product p','p.id=og.proid')
                                    ->where($where)
                                    ->field('og.id')
                                    ->find();
                                if(!$ogoods){
                                    Db::name('member_invite_cashback_log')->where('id',$lv['id'])->where('status',0)->update(['status'=>-1,'update_time'=>time(),'cancel_time'=>time(),'reason'=>'上级购买记录不存在或订单未完成']);
                                    continue;
                                }
                            }
                        } 

                        //返现设置数据是否存在
                        $set['invite_cashbak_data']  = $set['invite_cashbak_data'] ? json_decode($set['invite_cashbak_data'],true) : [];
                        if(empty($set['invite_cashbak_data'])){
                            Db::name('member_invite_cashback_log')->where('id',$lv['id'])->update(['status'=>-1,'update_time'=>time(),'cancel_time'=>time(),'reason'=>'邀请返现活动已失效']);
                            continue;
                        }

                        $gettj = explode(',',$set['gettj']);
                        if(!in_array('-1',$gettj) && !in_array($parent['levelid'],$gettj)){ //不是所有人
                            Db::name('member_invite_cashback_log')->where('id',$lv['id'])->update(['status'=>-1,'update_time'=>time(),'cancel_time'=>time(),'reason'=>'上级等级不符']);
                            continue;
                        }

                        if($set['fwtype']>=3){
                            //其他类型不适应
                            Db::name('member_invite_cashback_log')->where('id',$lv['id'])->update(['status'=>-1,'update_time'=>time(),'cancel_time'=>time(),'reason'=>'邀请返现类型错误']);
                            continue;
                        }

                        //是否开启复购，若没开启，则每个邀请人只能发一次，开启则无邀请人数限制
                        if(!$set['isagain']){
                            //查询此用户此商品是否已给上级记录过，记录过则不在发放
                            $count = Db::name('member_invite_cashback_log')
                                ->where('order_mid',$member['id'])
                                ->where('mid',$parent['id'])
                                ->where('cashback_id',$set['id']);
                            if($set['fwtype']==2){
                                 $count = $count->where('proid',$og['proid']);
                            }
                            $count = $count
                                ->where('status','in',[1,2])
                                ->count();
                            if($count){
                                Db::name('member_invite_cashback_log')->where('id',$lv['id'])->update(['status'=>-1,'update_time'=>time(),'cancel_time'=>time(),'reason'=>'此邀请人此商品已给上级发放过返现']);
                                continue;
                            }
                        }

                        //查询上级已返现过的次数
                        $pnum = Db::name('member_invite_cashback_log')
                            ->where('mid',$parent['id']);
                        if($set['fwtype']==2){
                            $pnum = $pnum->where('proid',$og['proid']);
                        }
                        $pnum = $pnum
                            ->where('cashback_id',$set['id'])
                            ->where('status','in',[1,2])
                            ->count();
                        $set['pnum'] = 0+$pnum;

                        //查询设置的推N返一次数
                        $set['icnum'] = count($set['invite_cashbak_data']);

                        //若没有开启循环，则只按顺序发一次
                        if(!$set['iscycle'] && $set['pnum']>=$set['icnum']){
                            Db::name('member_invite_cashback_log')->where('id',$lv['id'])->update(['status'=>-1,'update_time'=>time(),'cancel_time'=>time(),'reason'=>'此邀请返现活动上级奖励已全部发放完毕']);
                            continue;
                        }

                        //查询已循环的次数
                        $num = floor($set['pnum'] / $set['icnum']);
                        //如果开启循环，且开启了循环次数限制，则需要查看循环几次后固定发奖设置
                        if($set['iscycle'] && $set['cyclenum']>0 && $num>= $set['cyclenum']){
                            //若固定发奖未设置则跳过发放
                            if($set['cyclemoney'] <=0 && $set['cyclescore']<=0 && $set['cyclecommission']<=0 && $set['cyclemoney2'] <=0 && $set['cyclescore2']<=0 && $set['cyclecommission2']<=0){
                                Db::name('member_invite_cashback_log')->where('id',$lv['id'])->update(['status'=>-1,'update_time'=>time(),'cancel_time'=>time(),'reason'=>'循环次数已满']);
                                continue;
                            }
                        }

                        //返现金额
                        $back_price = $og['real_totalprice'];
                        if(getcustom('money_dec')){
                            //如果是余额抵扣，则要加上余额部分
                            if($og['dec_money'] && $og['dec_money'] >0){
                                $back_price = $og['real_totalprice']+$og['dec_money'];
                            }
                        }
                        if($back_price>0){
                            //发放返回
                            $mid_order_gid = $ogoods && $ogoods['id']?$ogoods['id']:0;
                            //self::deal_sendback2($aid,$mid_order_gid,$lv['id'],$order,$og,$parent,$set,$back_price);
                            self::deal_sendbackdata($aid,$order,$og,$parent,$set,$back_price,2,$params=['mid_order_gid'=>$mid_order_gid,'logid'=>$lv['id']]);
                        }else{
                            Db::name('member_invite_cashback_log')->where('id',$lv['id'])->update(['status'=>-1,'update_time'=>time(),'cancel_time'=>time(),'reason'=>'订单商品实际金额小于等于0']);
                            continue;
                        }
                    }
                }
            }
        }
    }

    //统一处理邀请返现数据 type 1: 付款后 2：确认收货后
    public static function deal_sendbackdata($aid,$order,$og,$parent,$set,$back_price,$type=1,$params=['mid_order_gid'=>0,'logid'=>0]){
        if(getcustom('yx_invite_cashback',$aid)){
            //返现类型 1、余额 2、佣金 3、积分 小数位数
            $money_weishu = 2;$commission_weishu = 2;$score_weishu = 0;
            if(getcustom('member_money_weishu',$aid)){
                $money_weishu = Db::name('admin_set')->where('aid',$aid)->value('member_money_weishu');
            }
            if(getcustom('fenhong_money_weishu',$aid)){
                $commission_weishu = Db::name('admin_set')->where('aid',$aid)->value('fenhong_money_weishu');
            }
            if(getcustom('score_weishu',$aid)){
                $score_weishu = Db::name('admin_set')->where('aid',$aid)->value('score_weishu');
            }

            $mid_order_gid = $params['mid_order_gid']??0;//返回者购买订单商品id
            $logid = $params['logid']??0;//邀请返现记录表id

            //固定余额返现
            $money       = 0;
            //百分比余额返现
            $money2      = 0;
            //固定积分返现
            $score       = 0;
            //百分比积分返现
            $score2      = 0;
            //固定佣金返现
            $commission  = 0;
            //百分比佣金返现
            $commission2 = 0;

            $cycle_after = 0;

            $dealsendstatus = false;//是否需要处理发放数据及发放，默认否
            if($type == 1){
                if(getcustom('yx_invite_cashback_ordertj')){
                    //判断是否是付款后就发放
                    if($set['ordertj'] && $set['ordertj'] == 1){
                        $dealsendstatus = true;
                    }
                }
            }else{
                $dealsendstatus = true;
            }
            if($dealsendstatus){
                //如果开启循环，且开启了循环次数限制
                if($set['iscycle'] && $set['cyclenum']>0){
                    //查询已循环的次数
                    $num = floor($set['pnum'] / $set['icnum']);
                    //若循环次数超出，则需要按照循环次数后固定发奖设置
                    if($num>=$set['cyclenum']){
                        $cycle_after = 1;
                        //固定余额返现
                        $money       = $set['cyclemoney'];
                        //百分比
                        $money2      = $set['cyclemoney2']*$back_price/100/$og['num'];
                        //固定积分返现
                        $score       = $set['cyclescore'];
                        //百分比
                        $score2      = $set['cyclescore2']*$back_price/100/$og['num'];
                        //固定佣金返现
                        $commission  = $set['cyclecommission'];
                        //百分比
                        $commission2 = $set['cyclecommission2']*$back_price/100/$og['num'];
                    }else{
                        //查询余数 = 上级已返现过的次数%设置的推N返一次数
                        $ynum = $set['pnum'] % $set['icnum'];
                        foreach($set['invite_cashbak_data'] as $ik=>$iv){
                            if($ik == $ynum){
                                //固定余额返现
                                $money       = $iv['money'];
                                //百分比余额返现
                                $money2      = $iv['money2']*$back_price/100/$og['num'];
                                //固定积分返现
                                $score       = $iv['score'];
                                //百分比积分返现
                                $score2      = $iv['score2']*$back_price/100/$og['num'];
                                //固定佣金返现
                                $commission  = $iv['commission'];
                                //百分比佣金返现
                                $commission2 = $iv['commission2']*$back_price/100/$og['num'];
                            }
                        }
                        unset($ik);
                        unset($iv);
                    }
                }else{
                    //查询余数 = 上级已返现过的次数%设置的推N返一次数
                    $ynum = $set['pnum'] % $set['icnum'];
                    foreach($set['invite_cashbak_data'] as $ik=>$iv){
                        if($ik == $ynum){
                            //固定余额返现
                            $money       = $iv['money'];
                            //百分比余额返现
                            $money2      = $iv['money2']*$back_price/100/$og['num'];
                            //固定积分返现
                            $score       = $iv['score'];
                            //百分比积分返现
                            $score2      = $iv['score2']*$back_price/100/$og['num'];
                            //固定佣金返现
                            $commission  = $iv['commission'];
                            //百分比佣金返现
                            $commission2 = $iv['commission2']*$back_price/100/$og['num'];
                        }
                    }
                    unset($ik);
                    unset($iv);
                }

            }

            //总余额
            $allmoney = $money+$money2;
            $allmoney = dd_money_format($allmoney,$money_weishu);
            //总积分
            $allscore = $score+$score2;
            $allscore = dd_money_format($allscore,$score_weishu);
            //总佣金
            $allcommission = $commission+$commission2;
            $allcommission = dd_money_format($allcommission,$commission_weishu);

            //添加返回记录
            $log = [];
            if($type == 1){
                $log['aid'] = $aid;
                $log['mid'] = $parent['id'];//返回者
                $status = 0;
                if(getcustom('yx_invite_cashback_ordertj')){
                    if($dealsendstatus){
                        $log['mid_order_gid'] = $mid_order_gid;
                        $status = 1;
                    }
                }

                //商品信息
                $log['proid']        = $og['proid'];
                $log['order_id']     = $order['id'];//订单id
                $log['order_gid']    = $og['id'];//订单商品id
                $log['order_mid']    = $order['mid'];//订单用户id
                $log['cashback_id']  = $set['id'];//邀请返现设置id

                $log['productids']   = !empty($set['productids'])?$set['productids']:'';
                $log['categoryids']  = !empty($set['categoryids'])?$set['categoryids']:'';

                $log['create_time']  = time();
            }else{
                $log['mid_order_gid'] = $mid_order_gid;//返回者购买订单商品id
                $status = 1;
            }

            $log['num']          = $og['num'];
            $log['back_price']   = $back_price;

            $log['money']        = $money;
            $log['money2']       = $money2;
            $log['allmoney']     = $allmoney;
            $log['score']        = $score;
            $log['score2']       = $score2;
            $log['allscore']     = $allscore;
            $log['commission']   = $commission;
            $log['commission2']  = $commission2;
            $log['allcommission']= $allcommission;
            $log['status']       = $status;
            $log['update_time']  = time();

            $remark = '商品'.$og['name'].'邀请返还';
            $return_type = 0;//返现时间 0：立即发放 1：自定义
            if($dealsendstatus){
                //定义发放余额、积分、佣金的金额数量
                $sendmoney = $allmoney;
                $sendscore = $allscore;
                $sendcommission = $allcommission;

                if(getcustom('yx_invite_cashback_time',$aid)){
                    $log['order_gname'] = $og['name'];//订单商品名称
                    $return_type = $log['return_type'] = $set['return_type']??0; //返现时间 0：立即发放 1：自定义
                    if($return_type == 1){
                        $return_day = $log['return_day'] = $set['return_day']??0; //返回天数

                        //如果大于等于2天,才计算平均每天发放标准
                        if($return_day>=2){

                            //计算余额平均发放数值
                            if($allmoney>0){
                                $moneyave = $allmoney/$return_day;
                                $moneyave = dd_money_format($moneyave,$money_weishu);
                                //处理特殊情况
                                if($moneyave>=$allmoney){
                                    $moneyave = $allmoney;
                                    $log['moneystatus'] = 2;//余额返回状态 1：返回中 2：返回完成
                                }else{
                                    $log['moneystatus'] = 1;//余额返回状态 1：返回中 2：返回完成
                                }
                                $sendmoney = $log['sendmoney'] = $log['moneyave']= $moneyave;
                                $log['money_sendnum'] = 1;//发放次数
                            }else{
                                $log['moneystatus'] = 2;//余额返回状态 1：返回中 2：返回完成
                            }

                            //计算积分平均发放数值
                            if($allscore>0){
                                $scoreave = $allscore/$return_day;
                                $scoreave = dd_money_format($scoreave,$score_weishu);
                                if($score_weishu ==0 && $scoreave<=1){
                                    $scoreave = 1;
                                }
                                //处理特殊情况
                                if($scoreave>=$allscore){
                                    $scoreave = $allscore;
                                    $log['scorestatus'] = 2;//积分返回状态 1：返回中 2：返回完成
                                }else{
                                    $log['scorestatus'] = 1;//积分返回状态 1：返回中 2：返回完成
                                }
                                $sendscore = $log['sendscore'] = $log['scoreave'] = $scoreave;
                                $log['score_sendnum'] = 1;//发放次数
                            }else{
                                $log['scorestatus'] = 2;//积分返回状态 1：返回中 2：返回完成
                            }

                            //计算佣金平均发放数值
                            if($allcommission>0){
                                $commissionave = $allcommission/$return_day;
                                $commissionave = dd_money_format($commissionave,$commission_weishu);
                                //处理特殊情况
                                if($commissionave>=$allcommission){
                                    $commissionave = $allcommission;
                                    $log['commissionstatus'] = 2;//佣金返回状态 1：返回中 2：返回完成
                                }else{
                                    $log['commissionstatus'] = 1;//佣金返回状态 1：返回中 2：返回完成
                                }
                                $sendcommission = $log['sendcommission'] = $log['commissionave']= $commissionave;
                                $log['commission_sendnum'] = 1;//发放次数
                            }else{
                                $log['commissionstatus'] = 2;//佣金返回状态 1：返回中 2：返回完成
                            }

                            if($log['moneystatus'] == 2 && $log['scorestatus'] == 2 && $log['commissionstatus'] == 2){
                                $log['return_status'] = 2;//返回状态 1：返回中 2：返回完成
                            }else{
                                $log['return_status'] = 1;//返回状态 1：返回中 2：返回完成
                            }
                            $log['return_sendtime'] = $log['money_sendtime'] = $log['score_sendtime'] = $log['commission_sendtime'] = time();
                            $remark = '订单号：'.$og['ordernum'].'，商品'.$og['name'].'邀请返还，第1次返现';
                        //如果天数小于2，则直接发放全部
                        }else{
                            $log['sendmoney'] = $allmoney;
                            $log['sendscore'] = $allscore;
                            $log['sendcommission'] = $allcommission;
                            $log['return_status']  = $return_status = 2;//返回状态 1：返回中 2：返回完成
                            $log['return_sendtime'] = $log['money_sendtime'] = $log['score_sendtime'] = $log['commission_sendtime'] = time();
                            $log['money_sendnum'] = $log['score_sendnum'] = $log['commission_sendnum'] = 1;//发放次数
                        }
                    }
                }
            }

            $is_fafang = 1;//是否发放
            if($return_type == 0){
                //佣金返现天数
                if(getcustom('yx_invite_cashback_commission_day',$aid) && $set['back_commission_day'] > 1 && $cycle_after==1){
                    $log['back_commission_day'] = $set['back_commission_day'];
                    $log['status']= 0;
                }
                //$status==1，代表是付款后发放，否则是确认收货发放
                if(getcustom('yx_invite_cashback_video_receive',$aid) && $set['video_receive_st'] == 1 && $status==1){
                    $log['status']= 2; //开启观看视频领取，这里不能发放
                    $is_fafang = 0;//去领取
                }
            }

            if($type == 1){
                $logid = $insert = Db::name('member_invite_cashback_log')->insertGetId($log);
            }else{
                $insert = Db::name('member_invite_cashback_log')->where('id',$logid)->where('status',0)->update($log);
            }
            
            if($dealsendstatus && $insert && $is_fafang){
                if($sendmoney>0 ){
                    \app\common\Member::addmoney($aid,$parent['id'],$sendmoney,$remark,0,'invite_cashback');
                }
                if($sendscore){
                    \app\common\Member::addscore($aid,$parent['id'],$sendscore,$remark);
                }
                if($sendcommission>0 && !$log['back_commission_day']){
                    \app\common\Member::addcommission($aid,$parent['id'],$order['mid'],$sendcommission,$remark);
                }

                if(getcustom('yx_invite_cashback_time',$aid)){
                    //记录每个记录发放的记录
                    if($return_type == 1 && ($sendmoney>0 || $sendscore>0 || $sendcommission>0)){
                        $params = ['sendmoney' =>$sendmoney , 'sendscore'=>$sendscore , 'sendcommission'=>$sendcommission];
                        self::deal_sendlog($aid,$logid,$params);
                    }
                }
            }
        }
    }

    public static function deal_sendlog($aid,$logid,$params = ['sendmoney' =>0 , 'sendscore'=>0 , 'sendcommission'=>0],$cashbacklog = []){
        if(getcustom('yx_invite_cashback_time',$aid)){
            //记录每个记录发放的记录
            $sendmoney = $params['sendmoney']??0;
            $sendscore = $params['sendscore']??0;
            $sendcommission = $params['sendcommission']??0;

            if(!$cashbacklog){
                $cashbacklog = Db::name('member_invite_cashback_log')->where('id',$logid)->find();
            }
            if($cashbacklog){
                $sendlog = [];
                $sendlog['logid'] = $logid;
                $sendlog['aid'] = $cashbacklog['aid'];
                $sendlog['mid'] = $cashbacklog['mid'];
                $sendlog['cashback_id'] = $cashbacklog['cashback_id'];

                $sendlog['money'] = $sendmoney;
                $sendlog['score'] = $sendscore;
                $sendlog['commission'] = $sendcommission;
                $sendlog['createtime'] = time();

                Db::name('member_invite_cashback_log_sendlog')->insertGetId($sendlog);
            }else{
               \think\facade\Log::write('邀请返现记录'.$logid.'分时间发放失败:sendmoney'.$sendmoney.' sendscore'.$sendscore.' sendcommission'.$sendcommission);
            }
        }
    }

    //aid为0 定时任务查询（分页查询），其他的则查询全部 ；type 0：未查询过 1：已查询过
    public static function deal_autoinvitecashback($aid=0,$type=0)
    {
        if(getcustom('yx_invite_cashback_time')){
            // 自定义每天返现
            //读取配置
            if($aid){
                $admins = Db::name('admin')->where('id',$aid)->field('id')->select()->toArray();
            }else{
                $admins = Db::name('admin')->where('status',1)->field('id')->select()->toArray();
            }
            if(!$admins) return;

            //今天
            $today = strtotime(date("Y-m-d",time()));
            if($aid){
                foreach($admins as $admin){
                    $aid = $admin['id'];
                    //邀请返现
                    self::deal_autoinvitecashback2($aid,$today);
                }
            }else{
                //定时任务
                foreach($admins as $admin){
                    $aid = $admin['id'];
                    //分页查询，读取上一次查询的最后记录
                    $logid = cache('invite_aid_send_'.$aid)??'';
                    if(!$logid) $logid = 0;
                    if(!$type || ($type && $logid)){
                        //邀请返现
                        self::deal_autoinvitecashback2($aid,$today,1,$logid);
                    }
                }
            }
        }
    }

    //type 0：默认不限制查询数量 1 限制查询数量(定时任务使用)
    private static function deal_autoinvitecashback2($aid,$today,$type=0,$logid=0)
    {
        if(getcustom('yx_invite_cashback_time',$aid)){
            //读取自定义类型、返现时间大于等于2天的、未返现完、今日未返现的
            $where = [];
            if($logid>0){
                $where[] = ['id','>=',$logid];
            }
            $where[] = ['aid','=',$aid];
            $where[] = ['return_type','=',1];
            $where[] = ['return_day','>=',2];
            $where[] = ['return_status','=',1];
            $where[] = ['return_sendtime','<',$today];
            $loglist = Db::name('member_invite_cashback_log')
                ->where($where);
            if($type == 1){
                //分页查询
                $loglist = $loglist->limit(80);
            }
            $loglist = $loglist->order('id asc')
                ->select()
                ->toArray();
            if($loglist){
                foreach($loglist as $log){
                    //先更新发放时间
                    $uptime = Db::name('member_invite_cashback_log')->where('id',$log['id'])->where('return_sendtime','<',$today)->update(['return_sendtime'=>time()]);
                    if(!$uptime) continue;

                    //查询订单是否存在
                    $goods = Db::name('shop_order_goods')->alias('sog')
                        ->join('shop_order so','so.id = sog.orderid')
                        ->where('sog.id',$log['order_gid'])->where('so.id',$log['order_id'])->where('sog.aid',$aid)->where('sog.status','>=',1)->where('sog.status','<=',3)
                        ->where('so.status','>=',1)->where('so.status','<=',3)
                        //->where('so.delete',0)
                        ->field('sog.id,sog.ordernum,sog.name')
                        ->find();
                    if(!$goods){
                        //查询订单是否存在
                        $countorder = Db::name('shop_order')->where('id',$log['order_id'])->count();
                        if(!$countorder){
                            $return_remark = '订单不存在';
                        }else{
                            $countordergoods = Db::name('shop_order_goods')->where('id',$log['order_gid'])->count();
                            if(!$countordergoods){
                                $return_remark = '订单商品不存在';
                            }else{
                                $return_remark = '订单商品状态不符';
                            }
                        }
                        Db::name('member_invite_cashback_log')->where('id',$log['id'])->update(['return_status'=>-2,'return_remark'=>$return_remark]);
                        continue;
                    }

                    $remark = '订单号：'.$goods['ordernum'].'，商品'.$log['order_gname'].'邀请返还';
                    $sendmoney = 0;
                    //如果余额未返还完、且今日未返还
                    if($log['moneystatus'] == 1 && $log['money_sendtime'] < $today){
                        $updata = [];
                        $updata['money_sendtime'] = time();
                        if($log['moneyave']>0){
                            $sendallmoney = $log['sendmoney'] + $log['moneyave'];
                            if($sendallmoney>=$log['allmoney']){
                                $sendmoney = $log['allmoney'] - $log['sendmoney'];
                                $log['moneystatus'] = $updata['moneystatus'] = 2;
                            }else{
                                $sendmoney = $log['moneyave'];
                            }
                            $upmoney = Db::name('member_invite_cashback_log')
                                ->where('id',$log['id'])->where('status',1)->where('money_sendtime','<',$today)->where('sendmoney','<',$log['allmoney'])
                                ->inc('sendmoney',$sendmoney)->inc('money_sendnum',1)->update($updata);
                        }else{
                            $log['moneystatus'] = $updata['moneystatus'] = 2;
                            $upmoney = Db::name('member_invite_cashback_log')->where('id',$log['id'])->where('status',1)->where('money_sendtime','<',$today)->update($updata);
                        }

                        if($upmoney && $sendmoney>0){
                            $money_sendnum = $log['money_sendnum']+1;
                            $moneyremark = $remark.'，第'.$money_sendnum.'次返现';
                            \app\common\Member::addmoney($aid,$log['mid'],$sendmoney,$moneyremark,0,'invite_cashback');
                        }
                    }

                    $sendscore = 0;
                    //如果积分未返还完、且今日未返还
                    if($log['scorestatus'] == 1 && $log['score_sendtime'] < $today){

                        $updata = [];
                        $updata['score_sendtime'] = time();
                        if($log['scoreave']>0){
                            $sendallscore = $log['sendscore'] + $log['scoreave'];
                            if($sendallscore>=$log['allscore']){
                                $sendscore = $log['allscore'] - $log['sendscore'];
                                $log['scorestatus'] = $updata['scorestatus'] = 2;
                            }else{
                                $sendscore = $log['scoreave'];
                            }
                            $upscore = Db::name('member_invite_cashback_log')
                                ->where('id',$log['id'])->where('status',1)->where('score_sendtime','<',$today)->where('sendscore','<',$log['allscore'])
                                ->inc('sendscore',$sendscore)->inc('score_sendnum',1)->update($updata);
                        }else{
                            $log['scorestatus'] = $updata['scorestatus'] = 2;
                            $upscore = Db::name('member_invite_cashback_log')->where('id',$log['id'])->where('status',1)->where('score_sendtime','<',$today)->update($updata);
                        }

                        if($upscore && $sendscore>0){
                            $score_sendnum = $log['score_sendnum']+1;
                            $scoreremark = $remark.'，第'.$score_sendnum.'次返现';
                            \app\common\Member::addscore($aid,$log['mid'],$sendscore,$scoreremark);
                        }
                    }

                    $sendcommission = 0;
                    //如果佣金未返还完、且今日未返还
                    if($log['commissionstatus'] == 1 && $log['commission_sendtime'] < $today){

                        $updata = [];
                        $updata['commission_sendtime'] = time();
                        if($log['commissionave']>0){
                            $sendallcommission = $log['sendcommission'] + $log['commissionave'];
                            if($sendallcommission>=$log['allcommission']){
                                $sendcommission = $log['allcommission'] - $log['sendcommission'];
                                $log['commissionstatus'] = $updata['commissionstatus'] = 2;
                            }else{
                                $sendcommission = $log['commissionave'];
                            }
                            $upcommission = Db::name('member_invite_cashback_log')
                                ->where('id',$log['id'])->where('status',1)->where('commission_sendtime','<',$today)->where('sendcommission','<',$log['allcommission'])
                                ->inc('sendcommission',$sendcommission)->inc('commission_sendnum',1)->update($updata);
                        }else{
                            $log['commissionstatus'] = $updata['commissionstatus'] = 2;
                            $upcommission = Db::name('member_invite_cashback_log')->where('id',$log['id'])->where('status',1)->where('commission_sendtime','<',$today)->update($updata);
                        }

                        if($upcommission && $sendcommission>0){
                            $commission_sendnum = $log['commission_sendnum']+1;
                            $commissionremark = $remark.'，第'.$commission_sendnum.'次返现';
                            \app\common\Member::addcommission($aid,$log['mid'],$log['order_mid'],$sendcommission,$commissionremark);
                        }
                    }

                    //返回状态 2：返回完成
                    if($log['moneystatus'] == 2 && $log['scorestatus'] == 2 && $log['commissionstatus'] == 2){
                        $res = Db::name('member_invite_cashback_log')->where('id',$log['id'])->where('moneystatus',2)->where('scorestatus',2)->where('commissionstatus',2)->update(['return_status'=>2]);
                    }

                    //记录每个记录发放的记录
                    if($sendmoney>0 || $sendscore>0 || $sendcommission>0){
                        $params = ['sendmoney' =>$sendmoney , 'sendscore'=>$sendscore , 'sendcommission'=>$sendcommission];
                        self::deal_sendlog($aid,$log['id'],$params);
                    }
                }
                if($type == 1){
                    cache('invite_aid_send_'.$aid,$log['id']);
                }
            }else{
                if($type == 1){
                    cache('invite_aid_send_'.$aid,0);
                    return;
                }
            }
        }
    }

    public static function count_sendback($aid,$log){
        if(getcustom('yx_invite_cashback')){
            //计算未发放的记录奖励
            $status = $log['status'];

            //固定余额返现
            $money       = 0;
            //百分比余额返现
            $money2      = 0;
            //固定积分返现
            $score       = 0;
            //百分比积分返现
            $score2      = 0;
            //固定佣金返现
            $commission  = 0;
            //百分比佣金返现
            $commission2 = 0;

            $order = Db::name('shop_order')->where('id',$log['order_id'])->where('aid',$aid)->field('paytime')->find();
            if($order){
                //查询邀请返现
                $set = Db::name('invite_cashback')
                    ->where('id',$log['cashback_id'])
                    ->where('aid',$aid)
                    ->where('bid',0)
                    ->where('starttime','<',$order['paytime'])
                    ->where('endtime','>',$order['paytime'])
                    ->find();
                if($set){

                    $set['invite_cashbak_data'] = $set['invite_cashbak_data']?json_decode($set['invite_cashbak_data'],true):[];
                    if(!empty($set['invite_cashbak_data'])){
                        //查询上级已返现过的次数
                        $pnum1 = Db::name('member_invite_cashback_log')
                            ->where('id','<',$log['id'])
                            ->where('mid',$log['mid']);
                        if($set['fwtype']==2){
                            $pnum1 = $pnum1->where('proid',$log['proid']);
                        }
                        $pnum1 = $pnum1
                            ->where('cashback_id',$set['id'])
                            ->where('status',0)
                            ->count();
                        $pnum1 = 0+$pnum1;

                        $pnum2 = Db::name('member_invite_cashback_log')
                            ->where('mid',$log['mid']);
                        if($set['fwtype']==2){
                            $pnum2 = $pnum2->where('proid',$log['proid']);
                        }
                        $pnum2 = $pnum2
                            ->where('cashback_id',$set['id'])
                            ->where('status',1)
                            ->count();
                        $pnum2 = 0+$pnum2;

                        $set['pnum'] =  $pnum1+$pnum2;
                        //查询设置的推N返一次数
                        $set['icnum'] = count($set['invite_cashbak_data']);

                        //若没有开启循环，则只按顺序发一次
                        if(!$set['iscycle'] && $set['pnum']>=$set['icnum']){
                            Db::name('member_invite_cashback_log')->where('id',$log['id'])->update(['status'=>-1,'cancel_time'=>time(),'reason'=>'此邀请返现活动上级奖励已全部发放完毕']);
                            $status = -1;
                        }else{
                            //查询已循环的次数
                            $num = floor($set['pnum'] / $set['icnum']);
                            //如果开启循环，且开启了循环次数限制，则需要查看循环几次后固定发奖设置
                            if($set['iscycle'] && $set['cyclenum']>0){
                                //若固定发奖未设置则跳过发放
                                if($num>= $set['cyclenum'] && $set['cyclemoney'] <=0 && $set['cyclescore']<=0 && $set['cyclecommission']<=0 && $set['cyclemoney2'] <=0 && $set['cyclescore2']<=0 && $set['cyclecommission2']<=0){
                                    Db::name('member_invite_cashback_log')->where('id',$log['id'])->update(['status'=>-1,'update_time'=>time(),'cancel_time'=>time(),'reason'=>'循环次数已满']);
                                    $status = -1;
                                }else{
                                    //若循环次数超出，则需要按照循环次数后固定发奖设置
                                    if($num>=$set['cyclenum']){
                                        //固定余额返现
                                        $money       = $set['cyclemoney'];
                                        $money2      = $set['cyclemoney2']*$log['back_price']/100/$log['num'];
                                        //固定积分返现
                                        $score       = $set['cyclescore'];
                                        $score2      = $set['cyclescore2']*$log['back_price']/100/$log['num'];
                                        //固定佣金返现
                                        $commission  = $set['cyclecommission'];
                                        $commission2 = $set['cyclecommission2']*$log['back_price']/100/$log['num'];
                                    }else{
                                        //查询余数 = 上级已返现过的次数%设置的推N返一次数
                                        $ynum = $set['pnum'] % $set['icnum'];
                                        foreach($set['invite_cashbak_data'] as $ik=>$iv){
                                            if($ik == $ynum){
                                                //固定余额返现
                                                $money       = $iv['money'];
                                                //百分比余额返现
                                                $money2      = $iv['money2']*$log['back_price']/100/$log['num'];
                                                //固定积分返现
                                                $score       = $iv['score'];
                                                //百分比积分返现
                                                $score2      = $iv['score2']*$log['back_price']/100/$log['num'];
                                                //固定佣金返现
                                                $commission  = $iv['commission'];
                                                //百分比佣金返现
                                                $commission2 = $iv['commission2']*$log['back_price']/100/$log['num'];
                                            }
                                        }
                                        unset($ik);
                                        unset($iv);
                                    }
                                }
                            }else{
                                //查询余数 = 上级已返现过的次数%设置的推N返一次数
                                $ynum = $set['pnum'] % $set['icnum'];
                                foreach($set['invite_cashbak_data'] as $ik=>$iv){
                                    if($ik == $ynum){
                                        //固定余额返现
                                        $money       = $iv['money'];
                                        //百分比余额返现
                                        $money2      = $iv['money2']*$log['back_price']/100/$log['num'];
                                        //固定积分返现
                                        $score       = $iv['score'];
                                        //百分比积分返现
                                        $score2      = $iv['score2']*$log['back_price']/100/$log['num'];
                                        //固定佣金返现
                                        $commission  = $iv['commission'];
                                        //百分比佣金返现
                                        $commission2 = $iv['commission2']*$log['back_price']/100/$log['num'];
                                    }
                                }
                                unset($ik);
                                unset($iv);
                            }

                            //总余额
                            $allmoney = $money+$money2;
                            $allmoney = round($allmoney,2);
                            //总积分
                            $allscore = $score+$score2;
                            $allscore = intval($allscore);
                            //总佣金
                            $allcommission = $commission+$commission2;
                            $allcommission = round($allcommission,2);
                        }

                    }else{
                        Db::name('member_invite_cashback_log')->where('id',$log['id'])->update(['status'=>-1,'cancel_time'=>time(),'reason'=>'邀请返现活动已失效']);
                        $status = -1;
                    }
                    
                }else{
                    Db::name('member_invite_cashback_log')->where('id',$log['id'])->update(['status'=>-1,'cancel_time'=>time(),'reason'=>'邀请返现活动不存在或已结束']);
                    $status = -1;
                }
            }else{
                Db::name('member_invite_cashback_log')->where('id',$log['id'])->update(['status'=>-1,'cancel_time'=>time(),'reason'=>'下级订单不存在']);
                $status = -1;
            }

            $data = [];
            //商品信息
            $data['money']        = $money;
            $data['money2']       = $money2;
            $data['allmoney']     = $allmoney;
            $data['score']        = $score;
            $data['score2']       = $score2;
            $data['allscore']     = $allscore;
            $data['commission']   = $commission;
            $data['commission2']  = $commission2;
            $data['allcommission']= $allcommission;
            $data['allcommission']= $allcommission;
            $data['status']       = $status;

            return $data;
        }
    }

    public function cancel_invitecashbacklog($aid,$order,$reason=''){
        $open_cashback = 0;
        if(getcustom('commission_orderrefund_deduct')){
            $open_cashback = 1;
            $open_commission_orderrefund_deduct = Db::name('admin_set')->where('aid',$aid)->value('open_commission_orderrefund_deduct');
            if($open_commission_orderrefund_deduct !=1){
                $open_cashback = 0;
            }
        }
        if(getcustom('yx_invite_cashback')){
            //已发放佣金退回
            if($open_cashback == 1){
                //查询记录是否存在
                $loglist = Db::name('member_invite_cashback_log')
                ->where('order_id',$order['id'])
                ->where('order_mid',$order['mid'])
                ->where('status',1)
                ->select()
                ->toArray();
                foreach($loglist as $k=>$v){
                    $allmoney = $v['allmoney'];
                    $allscore = $v['allscore'];
                    $allcommission = $v['allcommission'];     
                    $remark = $reason.'扣除';
                    if($allmoney>0 ){
                       // \app\common\Member::addmoney($aid,$v['mid'],-1*$allmoney,$remark);
                    }
                    if($allscore){
                       // \app\common\Member::addscore($aid,$v['mid'],-1*$allscore,$remark);
                    }
                    if($allcommission>0){
                        \app\common\Member::addcommission($aid,$v['mid'],$order['mid'],-1*$allcommission,$remark);
                    }

                }
                $logs = Db::name('member_invite_cashback_log')
                ->where('order_id',$order['id'])
                ->where('order_mid',$order['mid'])
                ->where('aid',$aid)
                ->where('status',1)
                ->update(['status'=>-1,'cancel_time'=>time(),'reason'=>$reason]);
            }
            
            //订单取消返回
            $logs = Db::name('member_invite_cashback_log')
                ->where('order_id',$order['id'])
                ->where('order_mid',$order['mid'])
                ->where('aid',$aid)
                ->where('status',0)
                ->update(['status'=>-1,'cancel_time'=>time(),'reason'=>$reason]);
        }
    }
    /**
     * 所有参与活动的用户平均发放返现记录
     * aid 商家id
     * mid 会员id
     * cashback 购物返现活动
     * og 商品
     * back_price_total 返现数量
     */   
     
     public static function cashbackMemerDoLog($aid,$mid,$cashback,$og,$back_price_total=0,$remark='',$other=[]){
        $cashback_member = [];
        $cashback_member['aid'] = $aid;
        $cashback_member['mid'] = $mid;
        $cashback_member['cashback_id'] = $cashback['id'];
        $cashback_member['pro_id'] = $og['proid'];
        if($cashback['back_type'] == 1 ){
            $cashback_member['cashback_money'] = $back_price_total;
        }else if($cashback['back_type'] == 2 ){
            $cashback_member['commission'] = $back_price_total;
        }else if($cashback['back_type'] == 3 ){
            $cashback_member['score'] = $back_price_total;
        }
        $cashback_member['back_type']   = $cashback['back_type'];
        $cashback_member['type']        = $og['ordertype']??'shop';
        $cashback_member['create_time'] = time();
        if(getcustom('yx_cashback_time_speed',$aid)){
            $cashback_member['remark'] = $remark;
        }
         if(getcustom('yx_cashback_decay')){
             $cashback_member['cashback_member_id'] = $other['cashback_member_id']??0;
         }
        $insert = Db::name('cashback_member_log')->insert($cashback_member);
        if(getcustom('yx_cashback_log',$aid)){
            if(isset($cashback['return_type']) && $cashback['return_type']==0){
                //记录返现数据明细(立即释放的先加再减)
                self::cashback_log($aid,$mid,$cashback['id'],$og['id'],$back_price_total,'购物订单ID-'.$og['orderid'].'商品ID'.$og['proid'].'新增');
            }
            self::cashback_log($aid,$mid,$cashback['id'],$og['id'],-$back_price_total,$remark);
        }
        return $insert;
    }

    public static function deal_refund_combine($order,$money){
        if(getcustom('pay_money_combine')){
            //处理余额组合支付退款
            $data = [];
            $data['status'] = 1;
            $data['refund_combine_money']  = 0;//退余额部分
            $data['refund_combine_wxpay']  = 0;//退微信部分
            $data['refund_combine_alipay'] = 0;//退支付宝部分
            //先退微信或支付宝，后退余额
            if($order['combine_money']>0 || $order['combine_wxpay']>0 || $order['combine_alipay']>0){
                if($order['combine_wxpay']>0){
                    //统计是否已退完
                    $cha = $order['combine_wxpay'] - $order['refund_combine_wxpay'];
                    if($cha>0){
                        //判断能微信能退多少
                        $cha2 = $money-$cha;
                        if($cha2>=0){
                            $data['refund_combine_wxpay'] = $cha;
                        }else{
                            $data['refund_combine_wxpay'] = $money;
                        }
                    }
                }else if($order['combine_alipay']>0){
                    //统计是否已退完
                    $cha = $order['combine_alipay'] - $order['refund_combine_alipay'];
                    if($cha>0){
                        //判断能支付宝能退多少
                        $cha2 = $money-$cha;
                        if($cha2>=0){
                            $data['refund_combine_alipay'] = $cha;
                        }else{
                            $data['refund_combine_alipay'] = $money;
                        }
                    }
                }
                if($order['combine_money']>0){
                    //统计是否已退完
                    $cha2 = $order['combine_money'] - $order['refund_combine_money'];
                    if($cha2>0){
                        //计算组合余额应退
                        $data['refund_combine_money'] = $money-$data['refund_combine_wxpay']-$data['refund_combine_alipay'];
                    }
                }
                if($data['refund_combine_money']<=0 && $data['refund_combine_wxpay']<=0 && $data['refund_combine_alipay']<=0){
                    return ['status' => 0, 'msg' => '已退还全部'];
                }
            }
            return $data;
        }
    }

    public static function deal_refund_combine2($refund_money,$order,$payorder,$params,$type){
        if(getcustom('pay_money_combine')){
            //余额组合支付退款（仅商城、或者买单单独定制）refund_combine 1 走shop_refund_order形式退款(提交退款时已存入退款数据)； 2 走shop_order形式直接退款 3 走shop_order形式直接退款并清空组合支付数据(需未支付)
            $combinestatus = false;
            //暂只支持 $payorder['type'] == 'shop'
            if($payorder['type'] == 'shop' || $payorder['type'] == 'shop_hb' || $payorder['type'] == 'shop_fenqi'){
                $combinestatus = true;
            }
            if(getcustom('pay_money_combine_maidan')){
                if($payorder['type'] == 'maidan'){
                    $combinestatus = true;
                }
            }
            if($combinestatus && $params && $params['refund_combine'] && $params['refund_order']){
                $refund_order = $params['refund_order'];
                //判断是否支付了微信或支付宝部分，且有微信或支付宝退款，且没有退款过
                $refund_money = 0;
                if($order['combine_'.$type]>0){
                    //走shop_refund_order 形式退款
                    if($params['refund_combine'] == 1){
                        if($refund_order['refund_combine_'.$type]>0 && $refund_order['refundcombine'] == 0 ){
                            //计算可退多少
                            $cha = $order['combine_'.$type] - $order['refund_combine_'.$type];
                            if($cha>=$refund_order['refund_combine_'.$type]){
                                $refund_money  = $refund_order['refund_combine_'.$type];
                            }else{
                                $refund_money  = $cha;
                            }
                        }
                    //走shop_order形式直接退款
                    }else{
                        if($order['combine_'.$type] > $order['refund_combine_'.$type]){
                            $refund_money = $order['combine_'.$type] - $order['refund_combine_'.$type];
                        }
                    }
                }
            }
            return $refund_money;
        }
    }

    public static function deal_refund_combine3($refund_money,$order,$payorder,$params,$type,$mid,$rs,$paytype,$remark='',$reason=''){
        if(getcustom('pay_money_combine')){
            //余额组合支付退款（仅商城、或者买单单独定制）
            $combinestatus = false;
            //暂只支持 $payorder['type'] == 'shop'
            if($payorder['type'] == 'shop' || $payorder['type'] == 'shop_hb' || $payorder['type'] == 'shop_fenqi'){
                $combinestatus = true;
            }
            if(getcustom('pay_money_combine_maidan')){
                if($payorder['type'] == 'maidan'){
                    $combinestatus = true;
                }
            }
            //refund_combine 1 走shop_refund_order 形式退款(提交退款时已存入退款数据);2 走shop_order形式直接退款 3 走shop_order形式直接退款并清空组合支付数据(需未支付)
            if($combinestatus && $params && $params['refund_combine'] && $params['refund_order']){
                $refund_order = $params['refund_order'];
                if(!$rs || $rs['status'] !=1){
                    $msg = $rs && $rs['msg']?$rs['msg']:'退款错误';
                    return ['status'=>0,'msg'=>$msg];
                }

                //refund_combine 1 走shop_refund_order 形式退款;
                $refund_money2 = 0;
                if($params['refund_combine'] == 1){
                    //退款实际支付金额差额，差额多少即余额多退少退多少
                    $refund_money2 = $cha = $refund_order['refund_combine_'.$type] - $refund_money;
                    //更新退款实际支付
                    $updata = [];
                    $updata['refund_combine_money']  = $refund_order['refund_combine_money'] + $cha;
                    $updata['refund_combine_'.$type] = $refund_money;
                    Db::name($payorder['type'].'_refund_order')->where('id',$refund_order['id'])->update($updata);
                }else{
                    Db::name($payorder['type'].'_order')->where('id',$order['id'])->inc('refund_combine_'.$type,$refund_money)->update();
                }

                if($refund_money>0){
                    if($params['refund_combine'] == 1){
                        //更新组合退款状态
                        Db::name($payorder['type'].'_refund_order')->where('id',$refund_order['id'])->update(['refundcombine'=>$paytype]);
                    }
                }

                //判断是否支付了余额部分，且有余额退款，且没有退款完成
                if($order['combine_money']>0){
                    //走shop_refund_order 形式退款
                    if($params['refund_combine'] == 1){
                        //如果退款余额大于0，且退款支付类型不是余额支付，计算余额付款退款部分
                        if($refund_order['refund_combine_money']>0 && $refund_order['refundcombine'] !=1){
                            $refund_money2 += $refund_order['refund_combine_money'];
                        }
                    //走shop_order 形式直接退款
                    }else{
                        //如果余额支付部分大于已退款部分，计算余额付款退款部分
                        if($order['combine_money'] > $order['refund_combine_money']){
                            $refund_money2 += $order['combine_money'] - $order['refund_combine_money'];
                        }
                    }
                    if($refund_money2>0){
                        //退余额
                        $rs2 = \app\common\Member::addmoney($order['aid'],$mid,$refund_money2,$remark.' '.$reason);
                        if($rs2['status'] != 1){
                            return ['status'=>0,'msg'=>$rs2['msg']?$rs2['msg']:t('余额').'退款出错'];
                        }else{
                            if($params['refund_combine'] == 1){
                                //更新组合退款状态
                                Db::name($payorder['type'].'_refund_order')->where('id',$refund_order['id'])->update(['refundcombine'=>1]);
                            }

                            //清空组合支付数据(需未支付)
                            if($params['refund_combine'] == 3 && $order['status'] == 0){
                                Db::name($payorder['type'].'_order')->where('id',$order['id'])->update(['combine_money'=>0,'combine_wxpay'=>0,'combine_alipay'=>0,'refund_combine_money'=>0,'refund_combine_wxpay'=>0,'refund_combine_alipay'=>0]);
                            }else{
                                //增加组合支付余额退款部分
                                Db::name($payorder['type'].'_order')->where('id',$order['id'])->inc('refund_combine_money',$refund_money2)->update();
                            }
                        }
                    }
                } 
            }
            return ['status'=>1,'msg'=>''];
        }
    }

    public static function deal_cashbackspeed($member,$real_totalprice,$cashback){
        if(getcustom('yx_cashback_time_tjspeed')){
            //购物返现 直推、间推上级加速
            if($member['pid']>0 && $real_totalprice>0){
                //先执行今天发放奖励
                self::deal_autocashback($member['aid']);
                //上级、上上级加速
                $parent = Db::name('member')->where('id',$member['pid'])->where('aid',$member['aid'])->field('id,aid,pid')->find();
                if($parent){
                    if($cashback['parent_speed']>0){
                        self::deal_cashbackspeed2($real_totalprice,$cashback['parent_speed'],$parent,'直推下级订单完成加速');
                    }
                    if($parent['pid']>0 && $cashback['parent2_speed']>0){
                        $parent2 = Db::name('member')->where('id',$parent['pid'])->where('aid',$parent['aid'])->field('id,aid,pid')->find();
                        if($parent2){
                            self::deal_cashbackspeed2($real_totalprice,$cashback['parent2_speed'],$parent2,'间推订单完成级加速');
                        }
                    }
                }
            }
        }
    }
    public static function deal_cashbackteamspeed($member,$allreal_totalprice){
        if(getcustom('yx_cashback_time_teamspeed')){
            //购物返现 团队业绩达标上级加速
            if($member['pid']>0 && $allreal_totalprice>0){
                //先执行今天发放奖励
                self::deal_autocashback($member['aid']);
                //上级团队业绩达标加速
                self::deal_cashbackspeed3($member,$allreal_totalprice);
            }
        }
    }
    private static function deal_cashbackspeed2($real_totalprice,$parent_speed,$parent,$remark=''){
        if(getcustom('yx_cashback_time_tjspeed')){
            $parent_speed = $real_totalprice*$parent_speed/100;
            $parent_speed = round($parent_speed,2);
            if($parent_speed>0){
                $nowtime = strtotime(date("Y-m-d"));
                //查询上级未返完的记录
                $where = [];
                $where[] = ['mid','=',$parent['id']];
                $where[] = ['back_type','>=',1];
                $where[] = ['back_type','<=',3];
                $where[] = ['money_sendtime|commission_sendtime|score_sendtime','>=',$nowtime];
                $where[] = ['moneystatus|commissionstatus|scorestatus','=',1];
                if(getcustom('yx_cashback_yongjin')){
                    $where[] = ['cashback_yongjin','<>',2];
                }
                $where[] = ['aid','=',$parent['aid']];
                $cashbacklist = Db::name('shop_order_goods_cashback')
                    ->where($where)
                    ->order('id asc')
                    ->select()
                    ->toArray();
                //如果存在则加速返现活动，否则直接加入上级的待加速数额中
                if($cashbacklist){
                    foreach($cashbacklist as $mv){
                        if($parent_speed<=0){
                            break;
                        }
                        if(getcustom('yx_cashback_stage')){
                            //阶梯性返现不走加速
                            if(!empty($mv['return_type']) && $mv['return_type'] == 2){
                                continue;
                            }
                        }

                        //走统一的处理方法
                        $res = self::deal_cashbacklist($mv,['isspeed'=>1,'parent_speed'=>$parent_speed,'remark'=>$remark]);
                        if($res && $res['status'] == 1){
                            $parent_speed = $res['param']['parent_speed'];
                        }else{
                            continue;
                        }

                    }
                    unset($mv);
                    //有剩余则累计
                    if($parent_speed>0){
                        Db::name('member')->where('id',$parent['id'])->inc('cashback_speed_num',$parent_speed)->update();
                    }
                }else{
                    Db::name('member')->where('id',$parent['id'])->inc('cashback_speed_num',$parent_speed)->update();
                }
            }
        }
    }
    private static function deal_cashbackspeed3($member,$real_totalprice,$cashback=''){
        if(getcustom('yx_cashback_time_teamspeed')){
            $parents = Db::name('member')->where('id','in',$member['path'])->field('id,aid')->select()->toArray();
            if($parents){
                foreach($parents as $parent){

                    $nowtime = strtotime(date("Y-m-d"));
                    //查询上级未返完的记录
                    $where = [];
                    $where[] = ['mid','=',$parent['id']];
                    $where[] = ['back_type','>=',1];
                    $where[] = ['back_type','<=',3];
                    $where[] = ['money_sendtime|commission_sendtime|score_sendtime','>=',$nowtime];
                    $where[] = ['moneystatus|commissionstatus|scorestatus','=',1];
                    if(getcustom('yx_cashback_yongjin')){
                        $where[] = ['cashback_yongjin','<>',2];
                    }
                    $where[] = ['aid','=',$parent['aid']];
                    $cashbacklist = Db::name('shop_order_goods_cashback')
                        ->where($where)
                        ->order('id asc')
                        ->select()
                        ->toArray();

                    if($cashbacklist){
                        $cashback_speed_num = 0;//加速余额
                        foreach($cashbacklist as $mv){
                            if(getcustom('yx_cashback_stage')){
                                //阶梯性返现不走加速
                                if(!empty($mv['return_type']) && $mv['return_type'] == 2){
                                    continue;
                                }
                            }

                            //用户加速余额加速
                            if($cashback_speed_num>0){
                                $res = self::deal_cashbacklist($mv,['isspeed'=>3,'cashback_speed'=>$cashback_speed_num,'remark'=>'团队业绩达标加速余额释放']);
                                if($res && $res['status'] == 1){
                                    if($res['param']['cashback_speed']<=0){
                                        $cashback_speed_num = 0;
                                        //Db::name('member')->where('id',$parent['id'])->dec('cashback_speed_num',$cashback_speed_num)->update();
                                    }else{
                                        $cashback_speed_num = $res['param']['cashback_speed'];
                                    }
                                }
                            }

                            if($mv['back_type'] == 1){
                                //总返回数值
                                $back_price = $mv['allmoney'];
                            }else if($mv['back_type'] == 2){
                                //总返回数值
                                $back_price = $mv['allcommission'];
                            }else if($mv['back_type'] == 3){
                                //总返回数值
                                $back_price = $mv['allscore'];
                            }

                            //团队业绩达标加速
                            $teamspeeddata = Db::name('cashback')->where('id',$mv['cashback_id'])->value('teamspeeddata');
                            if($teamspeeddata && !empty($teamspeeddata)){
                                $teamspeeddata = json_decode($teamspeeddata,true);
                                //查询下级团队
                                $downmids = \app\common\Member::getteammids($mv['aid'],$mv['mid']);
                                if($downmids && $teamspeeddata){
                                    $teamyeji = Db::name('shop_order_goods')->where('mid','in',$downmids)->where('status',3)->where('aid',$mv['aid'])->sum('real_totalprice');
                                    $teamyeji += $real_totalprice;

                                    //如果业绩大于已发放业绩
                                    if($teamyeji > $mv['teamspeed_yeji']){
                                        $teamspeed_money = 0;
                                        $team_speed      = 0;
                                        foreach($teamspeeddata as $tv){
                                            //如果团队业绩大于当前达标业绩，且当前达标业绩大于之前当前达标业绩 且当前达标业绩大于已发放达标业绩
                                            if($teamyeji>=$tv['money'] && $tv['money']>$teamspeed_money && $tv['money']> $mv['teamspeed_money']){
                                                $teamspeed_money = $tv['money'];
                                                $team_speed      = $back_price*$tv['speed']/100;
                                                $team_speed      = round($team_speed,2);
                                            }
                                        }
                                        unset($tv);
                                        if($team_speed>0){
                                            $res = self::deal_cashbacklist($mv,['isspeed'=>2,'team_speed'=>$team_speed,'teamspeed'=>$team_speed,'teamspeed_yeji'=>$teamyeji,'teamspeed_money'=>$teamspeed_money,'remark'=>'团队业绩达标加速']);
                                            if($res && $res['status'] == 1){
                                                if($res['param']['team_speed']>0){
                                                    $cashback_speed_num += $res['param']['team_speed'];
                                                    //Db::name('member')->where('id',$parent['id'])->inc('cashback_speed_num',$res['param']['team_speed'])->update();
                                                }
                                            }else{
                                                continue;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        unset($mv);
                        if($cashback_speed_num>0){
                            Db::name('member')->where('id',$parent['id'])->inc('cashback_speed_num',$cashback_speed_num)->update();
                        }
                    }
                }
            }
        }
    }
    private static function deal_cashbacklist($mv,$param=[]){
        if(getcustom('yx_cashback_time',$mv['aid']) && (getcustom('yx_cashback_time_addup_speed',$mv['aid']) || getcustom('yx_cashback_time_team_speed',$mv['aid']) || getcustom('yx_cashback_time_gudong_speed',$mv['aid']))){
            $where = [];
            $where[] = ['aid','=',$mv['aid']];
            $where[] = ['id','=',$mv['id']];
            $mv = Db::name('shop_order_goods_cashback')->where($where)->find();

        }
        if(getcustom('yx_cashback_time',$mv['aid']) || getcustom('yx_cashback_stage',$mv['aid']) || getcustom('yx_cashback_multiply',$mv['aid'])){
            $isspeed       = 0;//是否加速释放(仅自定义天数的支持加速) 0:不加速 1：推荐加速 2：团队加速 3:余额加速 4:团队分红极差式加速 5:股东分红式加速 6一级分销加速 7:团队极差式加速 8:股东式加速 9:直推式加速
            $nocheckstatus = false;//不需要验证状态：默认需要验证
            $return_type = 1;//默认是 1 自定义返还 2、阶梯返还
            if(getcustom('yx_cashback_stage',$mv['aid'])){
                //计算此次阶梯返还发放的数值
                if(!empty($mv['return_type']) && $mv['return_type']==2){
                    $return_type = 2;
                }
            }
            if(getcustom('yx_cashback_time_tjspeed',$mv['aid']) || getcustom('yx_cashback_time_teamspeed',$mv['aid']) || getcustom('yx_cashback_time_teamfenhong_speed',$mv['aid']) || getcustom('yx_cashback_time_gdfenhong_speed',$mv['aid']) || getcustom('yx_cashback_time_fenxiao_speed',$mv['aid']) || getcustom('yx_cashback_time_addup_speed',$mv['aid']) || getcustom('yx_cashback_time_team_speed',$mv['aid']) || getcustom('yx_cashback_time_gudong_speed',$mv['aid'])){
                if($param && $param['isspeed']){
                    if($param['isspeed']>0 && $return_type != 1){
                        return;//仅自定义天数的支持加速
                    }
                    $isspeed       = $param['isspeed'];
                    $nocheckstatus = $param['nocheckstatus']?true:false;
                }
            }
            if(!$nocheckstatus){
                if($mv['canshtype'] == "shop"){
                    $count = Db::name('shop_order_goods')
                        ->alias('sog')
                        ->join('shop_order so','so.id = sog.orderid')
                        ->where('sog.id',$mv['sog_id'])
                        ->where('sog.aid',$mv['aid'])
                        ->where('sog.status','>=',1)
                        ->where('sog.status','<=',3)
                        ->where('so.status','>=',1)
                        ->where('so.status','<=',3)
                        //->where('so.delete',0)
                        ->count();
                    if(!$count){
                        return ['status'=>0,'param'=>$param];
                    }
                }else if($mv['canshtype'] == "collage"){
                    $count = Db::name('collage_order')
                        ->where('id',$mv['sog_id'])
                        ->where('aid',$mv['aid'])
                        ->where('status','>=',1)
                        ->where('status','<=',3)
                        //->where('delete',0)
                        ->count();
                    if(!$count){
                        return ['status'=>0,'param'=>$param];
                    }
                }else if($mv['canshtype'] == "maidan"){
                    $count = Db::name('maidan_order')
                        ->where('id',$mv['sog_id'])
                        ->where('aid',$mv['aid'])
                        ->where('status',1)
                        ->count();
                    if(!$count){
                        return ['status'=>0,'param'=>$param];
                    }
                }else{
                    return ['status'=>0,'param'=>$param];
                }
            }
            if(getcustom('yx_cashback_time_tjspeed',$mv['aid']) || getcustom('yx_cashback_time_teamspeed',$mv['aid'])){
                //如果是团队业绩加速，先更新业绩，防止多次业绩增速的产生
                if($isspeed == 2){
                    $teamdata = [];
                    $teamdata['teamspeed']       = $param['teamspeed'];
                    $teamdata['teamspeed_yeji']  = $param['teamspeed_yeji'];
                    $teamdata['teamspeed_money'] = $param['teamspeed_money'];
                    $upteam = Db::name('shop_order_goods_cashback')->where('id',$mv['id'])->where('teamspeed_yeji','<',$param['teamspeed_yeji'])->where('teamspeed_money','<',$param['teamspeed_money'])->update($teamdata);
                    if(!$upteam){
                        return ['status'=>0,'param'=>$param];
                    }
                }
            }

            if($mv['back_type'] == 1){
                //总返回数值
                $allmoney = $mv['allmoney'];
                //已发放的数值
                $money    = $mv['money'];
                //平均每次发放的数值
                $moneyave = $mv['moneyave'];
                //返回天数
                $day      = $mv['moneyday'];
                //发放次数
                $sendnum  = $mv['money_sendnum'];
            }else if($mv['back_type'] == 2){
                //总返回数值
                $allmoney = $mv['allcommission'];
                //已发放的数值
                $money    = $mv['commission'];
                //平均每次发放的数值
                $moneyave = $mv['commissionave'];
                //返回天数
                $day      = $mv['commissionday'];
                //发放次数
                $sendnum  = $mv['commission_sendnum'];
            }else if($mv['back_type'] == 3){
                //总返回数值
                $allmoney = $mv['allscore'];
                //已发放的数值
                $money    = $mv['score'];
                //平均每次发放的数值
                $moneyave = $mv['scoreave'];
                //返回天数
                $day      = $mv['scoreday'];
                //发放次数
                $sendnum  = $mv['score_sendnum'];
            }
            $send_money = $moneyave;//发放数值

            if(getcustom('yx_cashback_stage',$mv['aid'])){
                //计算此次阶梯返还发放的数值
                if($return_type==2){
                    //阶梯数值
                    $stagedata  = json_decode($mv['stagedata'],true);
                    //无数值，则结束
                    if(!$stagedata){
                        //结束
                        $updata = [];
                        if($mv['back_type'] == 1){
                            $updata['moneystatus']         = 2;
                            $updata['money_sendtime']      = time();
                        }else if($mv['back_type'] == 2){
                            $updata['commissionstatus']    = 2;
                            $updata['commission_sendtime'] = time();
                        }else if($mv['back_type'] == 3){
                            $updata['scorestatus']         = 2;
                            $updata['score_sendtime']      = time();
                        }
                        $updata['updatetime'] = time();
                        $up = Db::name('shop_order_goods_cashback')->where('id',$mv['id'])->update($updata);
                        return;
                    }

                    //计算创建记录到现在的天数
                    $nowday    = strtotime(date('Y-m-d',time()));
                    //createtime不存在查询下其他表找补下
                    if(!$mv['createtime'] || empty($mv['createtime'])){

                        $createday = '';
                        $type = $mv['canshtype'] && !empty($mv['canshtype'])?$mv['canshtype']:'shop';
                        if($type == 'shop'){
                            $order = Db::name('shop_order_goods')->where('id',$mv['sog_id'])->where('mid',$mv['mid'])->where('aid',$mv['aid'])->field('id,endtime')->find();
                            if($order && !empty($order['endtime'])){
                                $createday = strtotime(date('Y-m-d',$order['endtime']));
                            }
                        }elseif($type == 'collage'){
                            $order = Db::name('collage_order')->where('id',$mv['sog_id'])->where('mid',$mv['mid'])->where('aid',$mv['aid'])->field('id,collect_time')->find();
                            if($order && !empty($order['collect_time'])){
                                $createday = strtotime(date('Y-m-d',$order['collect_time']));
                            }
                        }else if($type == 'maidan'){
                            $order = Db::name('maidan_order')->where('id',$mv['sog_id'])->where('mid',$mv['mid'])->where('aid',$mv['aid'])->field('id,paytime')->find();
                            if($order && !empty($order['paytime'])){
                                $createday = strtotime(date('Y-m-d',$order['paytime']));
                            }
                        }else{
                            return;
                        }

                        if(!$createday){
                            $cashback_member = Db::name('cashback_member')->where('mid',$mv['mid'])->where(['cashback_id'=>$mv['cashback_id'],'pro_id'=>$mv['pro_id'],'type'=>$type])->where('aid',$mv['aid'])->field('create_time')->find();
                            if($cashback_member && $cashback_member['create_time']){
                                $createday = strtotime(date('Y-m-d',$cashback_member['create_time']));
                            }else{
                                return;
                            }
                        }
                    }else{
                        $createday = strtotime(date('Y-m-d',$mv['createtime']));
                    }

                    $chaday = intval(($nowday-$createday)/86400);
                    //超出返现天数，则结束
                    if($chaday>$day){
                        //结束
                        $updata = [];
                        if($mv['back_type'] == 1){
                            $updata['moneystatus']         = 2;
                            $updata['money_sendtime']      = time();
                        }else if($mv['back_type'] == 2){
                            $updata['commissionstatus']    = 2;
                            $updata['commission_sendtime'] = time();
                        }else if($mv['back_type'] == 3){
                            $updata['scorestatus']         = 2;
                            $updata['score_sendtime']      = time();
                        }
                        $updata['updatetime'] = time();
                        $up = Db::name('shop_order_goods_cashback')->where('id',$mv['id'])->update($updata);
                        return;
                    }

                    //获取阶梯返还比例
                    $stageratio = 0;
                    foreach($stagedata as $stage){
                        if($stage['stageday']<=$chaday && $chaday<=$stage['stageday2']){
                            $stageratio = $stage['stageratio'];
                        }
                    }

                    //返现类型 1、余额 2、佣金 3、积分 小数位数
                    $money_weishu = 2;$commission_weishu = 2;$score_weishu = 0;
                    if(getcustom('member_money_weishu')){
                        $money_weishu = Db::name('admin_set')->where('aid',$mv['aid'])->value('member_money_weishu');
                    }
                    if(getcustom('fenhong_money_weishu')){
                        $commission_weishu = Db::name('admin_set')->where('aid',$mv['aid'])->value('fenhong_money_weishu');
                    }
                    if(getcustom('score_weishu')){
                        $score_weishu = Db::name('admin_set')->where('aid',$mv['aid'])->value('score_weishu');
                    }

                    //计算发放值
                    $send_money = $allmoney*$stageratio*0.01;
                    if($mv['back_type'] ==1){
                        $send_money = dd_money_format($send_money,$money_weishu);
                    }else if($mv['back_type'] ==2){
                        $send_money = dd_money_format($send_money,$commission_weishu);
                    }else{
                        $send_money2  = dd_money_format($send_money,$score_weishu);
                        if($score_weishu == 0 && $send_money>0 && $send_money<=1){
                            $send_money = 1;
                        }else{
                            $send_money = $send_money2;
                        }
                    }
                }
            }
            if(getcustom('yx_cashback_multiply',$mv['aid'])){
                //dump($mv['id']);
                writeLog('计算'.$mv['id'].'开始','cashback_test');
                //返现类型 1、余额 2、佣金 3、积分 小数位数
                $money_weishu = 2;
                if(getcustom('member_money_weishu')){
                    $money_weishu = Db::name('admin_set')->where('aid',$mv['aid'])->value('member_money_weishu');
                }
                /*******倍增方式返现金额计算 start 20241206*********/
                if($mv['back_type'] == 1){
                    $sendtime = $mv['money_sendtime'];
                }else if($mv['back_type'] == 2){
                    $sendtime = $mv['commission_sendtime'];
                }else if($mv['back_type'] == 3){
                    $sendtime = $mv['score_sendtime'];
                }
                //倍增方式返现
                if($mv['return_type']==3){
                    writeLog('计算'.$mv['id'].'开始','cacl_cashback');
                    $return_type = $mv['return_type'];
                    $allmoney = $mv['back_price'];
                    //应达到的业绩增长
                    if($mv['send_circle']){
                        $now_circle_yeji = bcadd($mv['last_circle_yeji'],bcmul($mv['last_circle_yeji'],$mv['circle_add']/100,2),2);
                    }else{
                        $now_circle_yeji = $mv['last_circle_yeji'];
                    }
                    if($mv['yeji_type']==1){
                        $plate_lirun = $param['active_coin_total']??0;//当期平台业绩
                    }else{
                        $plate_lirun = $param['lirun']??0;//当期平台业绩
                    }
                    $circle_add = bcsub($plate_lirun,$mv['last_circle_yeji'],2);
                    if($circle_add<=0 && $mv['send_circle']>0){
                        //业绩没有增长就不发放
                        writeLog('没有业绩增长','cacl_cashback');
                        return;
                    }
                    $send_circle = $mv['send_circle']+1;
                    //计算增长比例
                    $real_add_bili = bcdiv($circle_add,$mv['last_circle_yeji'],8)*100;
                    $back_min_day = 0;
                    $back_max_day = 0;
                    if(getcustom('yx_cashback_multiply_days')){
                        //结算天数限制
                        $cashback = Db::name('cashback')->where('id',$mv['cashback_id'])->find();
                        $back_min_day = $cashback['back_min_day']??0;//最小发放天数
                        $back_max_day = $cashback['back_max_day']??0;//最高发放天数
                    }
                    $send_time_min = $sendtime+$back_min_day*86400;
                    $send_time_max = $sendtime+$back_max_day*86400;
                    //全公式=本期有效业绩可累计奖励-上期有效业绩可累计奖励
                    //=首期奖励*(1+10%)*(7120/6120-1)/10%-首期奖励*(1+10%)*(7038/6120-1)/10%
                    //$first_circle = Db::name('cashback_log')->where('mid',$mv['mid'])->where('og_id',$mv['sog_id'])->where('send_circle',1)->find();
                    //$last_circle = Db::name('cashback_log')->where('mid',$mv['mid'])->where('og_id',$mv['sog_id'])->where('send_circle',$mv['send_circle'])->find();
                    writeLog($real_add_bili.'>='.$mv['circle_add'],'cacl_cashback');
                    if($real_add_bili>=$mv['circle_add'] || $mv['send_circle']==0){
                        if($back_min_day>0 && $send_time_min>time() && $mv['send_circle']>0){
                            writeLog('未达到发放天数','cacl_cashback');
                            return ['status'=>0,'msg'=>'未达到发放天数'.$mv['back_min_day']];
                        }
                        //平台业绩达到增值要求(或者是首期发放),按增值比例计算返现金额
                        if($mv['circle_max']>0 && $send_circle>$mv['circle_max']){
                            //返现期数已超最大期数
                            $updata = [];
                            $updata['status']         = 2;
                            $updata['updatetime'] = time();
                            Db::name('shop_order_goods_cashback')->where('id',$mv['id'])->update($updata);
                            writeLog('已达到最大期数','cacl_cashback');
                            return true;
                        }
                        //根据增值比例计算返现金额
                        $send_money = bcmul($mv['totalprice'],$mv['first_circle']/100,$money_weishu);

                        if($send_circle>1){
                            //在上一期发放金额基础上增值发放
                            $send_money = bcmul($mv['last_circle_send'],1+$mv['circle_add']/100,$money_weishu);
                        }
                    }else{
                        //未达标时记录业绩为当前实际业绩，达标时记录业绩为按增长比例增加的业绩
                        $now_circle_yeji = $plate_lirun;
                        //增值比例未达到要求时判断时间，次月按实际增值比例发放
                        $s_time = strtotime(date('Y-m-1',$sendtime));
                        $e_time = strtotime(date('Y-m-t 23:59:59',$sendtime));
                        $back_count = Db::name('cashback_log')->where('mid',$mv['mid'])->where('og_id',$mv['sog_id'])->where('createtime','between',[$s_time,$e_time])->count();
                        //1，首期不作为判断次数
                        //2，当月如果发放超过1次，那么判断为下下月
                        //3，当月如果仅发放1次，那么判断为次月
                        //20250625 修改，首次发放也算判断次数
//                        $first_back_time = Db::name('cashback_log')->where('mid',$mv['mid'])->where('og_id',$mv['sog_id'])->order('id asc')->value('createtime');
//                        if($first_back_time>=$s_time){
//                            //第一次发放时间在本月内，减掉
//                            $back_count = $back_count-1;
//                        }
                        //默认下期发放时间是次月初
                        $check_time = strtotime(date('Y-m-t 24:00:00',$sendtime));
                        if($back_count>=1){
                            //当月已经发放超过一次，发放时间位下下月处
                            $check_time = strtotime(date('Y-m-t 24:00:00',$check_time+86400));
                        }
                        if(time()>=$check_time || ($back_max_day>0 && $send_time_max<time())){

                            if($mv['circle_add_fail']==0){
                                //未达到增长不发放
                                writeLog('未达到增长不发放','cacl_cashback');
                                return;
                            }
                            //$send_money = bcmul($mv['last_circle_send'],$real_add_bili/100,$money_weishu);
                            //本期理论应发
//                            $send_money = bcmul($mv['last_circle_send'],1+$mv['circle_add']/100,8);
//                            //按实际增长比例占比
//                            $add_rate = bcdiv($real_add_bili,$mv['circle_add'],8)*100;
//                            //本期实际应发
//                            $send_money = bcmul($send_money,$add_rate/100,$money_weishu);
                        }else{
                            //未达到天数
                            writeLog('未达到增长比例未达到天数','cacl_cashback');
                            return true;
                        }
                    }
                    //首期奖励
                    if($send_circle>1){
                        $first_circle_back_price = bcmul($mv['totalprice'],$mv['first_circle']/100,8);
                        writeLog($first_circle_back_price.'='.$mv['totalprice'].'*'.$mv['first_circle']/100,'cacl_cashback');
                        $first_circle_yeji = $mv['first_circle_yeji'];
                        $last_circle_yeji = $mv['last_circle_yeji'];
                        $send_money0 = bcmul($first_circle_back_price,(1+$mv['circle_add']/100),8);
                        $send_money1 = bcmul($send_money0,bcdiv(bcdiv($now_circle_yeji,$first_circle_yeji,8)-1,$mv['circle_add']/100,8),8);
                        //dump($send_money0.'*(('.$plate_lirun.'/'.$first_circle['circle_yeji'].')-1)/'.$mv['circle_add']/100);
                        writeLog($send_money0.'*(('.$now_circle_yeji.'/'.$first_circle_yeji.')-1)/'.$mv['circle_add']/100,'cacl_cashback');
                        //$send_money2 = abs($last_circle['back_price'])*(1+$mv['circle_add']/100)*($plate_lirun/$last_circle['circle_yeji']-1)/$mv['circle_add'];
                        $send_money2 = bcmul($send_money0,bcdiv(bcdiv($last_circle_yeji,$first_circle_yeji,8)-1,$mv['circle_add']/100,8),8);
                        //dump($send_money0.'*(('.$last_circle['circle_yeji'].'/'.$first_circle['circle_yeji'].')-1)/'.$mv['circle_add']/100);
                        writeLog($send_money0.'*(('.$last_circle_yeji.'/'.$first_circle_yeji.')-1)/'.$mv['circle_add']/100,'cacl_cashback');
                        $send_money = bcsub($send_money1,$send_money2,$money_weishu);
                        //dump($send_money0.'=>'.$send_money1.'=>'.$send_money2.'=>'.$send_money);
                        writeLog($send_money0.'=>'.$send_money1.'=>'.$send_money2.'=>'.$send_money,'cacl_cashback');
                    }
                }
                /*******倍增方式返现金额计算 end 20241206*********/
            }
            $send_all = $money+$send_money;//总发放数值
            $updata = [];
            if($money>=$allmoney){
                if($mv['back_type'] == 1){
                    $updata['moneystatus']         = 2;
                    $updata['money_sendtime']      = time();
                }else if($mv['back_type'] == 2){
                    $updata['commissionstatus']    = 2;
                    $updata['commission_sendtime'] = time();
                }else if($mv['back_type'] == 3){
                    $updata['scorestatus']         = 2;
                    $updata['score_sendtime']      = time();
                }
                if(getcustom('yx_cashback_multiply')){
                    $updata['status']         = 2;
                }
                $send_money = 0;
            }else{
                $new_sendnum = $sendnum+1;//发放次数
  

                if(getcustom('yx_cashback_time_tjspeed',$mv['aid']) || getcustom('yx_cashback_time_teamspeed',$mv['aid']) || getcustom('yx_cashback_time_teamfenhong_speed',$mv['aid']) || getcustom('yx_cashback_time_gdfenhong_speed',$mv['aid']) || getcustom('yx_cashback_time_fenxiao_speed',$mv['aid']) || getcustom('yx_cashback_time_addup_speed',$mv['aid']) || getcustom('yx_cashback_time_team_speed',$mv['aid']) || getcustom('yx_cashback_time_gudong_speed',$mv['aid'])){
                    //如果是加速
                    if($isspeed){
                        //1：推荐加速 2：团队加速 3、余额加速 4:团队分红极差式加速 5:股东分红式加速 6一级分销加速
                        if($isspeed == 1){
                            $send_money  = $param['parent_speed'];//发放数值
                            $send_all    = $money+$param['parent_speed'];//总发放数值
                        }else if($isspeed == 2){
                            $send_money  = $param['team_speed'];//发放数值
                            $send_all    = $money+$param['team_speed'];//总发放数值
                        }else if(in_array($isspeed, [4,5,6,7,8,9])){
                            $send_money  = $param['fenhong_speed'];//发放数值
                            $send_all    = $money+$param['fenhong_speed'];//总发放数值
                        }else {
                            $send_money  = $param['cashback_speed'];//发放数值
                            $send_all    = $money+$param['cashback_speed'];//总发放数值
                        }
                    }
                }
                if($send_all<$allmoney){
                    //如果是返还时间是自定义类型
                    if($return_type == 1){
                        $send_day = $day;
                        //发放次数大于等于返回天数，则这次结束，返回剩余全部
                        if(getcustom('yx_cashback_jiange_day')){
                            if(!empty($mv['jiange_day']) && $mv['jiange_day'] > 0){
                                $send_day = floor($day/$mv['jiange_day']);
                            }
                        }

                        if(getcustom('yx_cashback_time_teamfenhong_speed') || getcustom('yx_cashback_time_gdfenhong_speed') || getcustom('yx_cashback_time_fenxiao_speed') || getcustom('yx_cashback_time_addup_speed',$mv['aid']) || getcustom('yx_cashback_time_team_speed',$mv['aid']) || getcustom('yx_cashback_time_gudong_speed',$mv['aid'])){
                            //如果是加速
                            if(in_array($isspeed, [4,5,6,7,8,9])){
                                $yushu =  fmod($money, $moneyave);

                                if($yushu + $param['fenhong_speed'] >= $moneyave){
                                    $js_day = floor(($yushu + $param['fenhong_speed'])/$moneyave);
                                    $speednum = $js_day;
                                }else{
                                    $speednum = 0;
                                }

                                $new_sendnum = $sendnum+$speednum;//发放次数
                            }
                        }

                        if($new_sendnum >= $send_day){
                            $status     = 2;
                            $send_all   = $allmoney;
                            $send_money = $allmoney-$money;
                        }else{
                            $status  = 1;
                            if(getcustom('yx_cashback_time_tjspeed') || getcustom('yx_cashback_time_teamspeed')){
                                //如果是加速
                                if($isspeed){
                                    $speednum = $send_money/$moneyave;
                                    if($speednum<1){
                                        $speednum = 1;
                                    }else{
                                        $speednum = floor($speednum);
                                    }
                                    $new_sendnum = $sendnum+$speednum;//发放次数
                                }
                            }
                        }
                        if(getcustom('yx_cashback_time_tjspeed') || getcustom('yx_cashback_time_teamspeed') || getcustom('yx_cashback_time_teamfenhong_speed') || getcustom('yx_cashback_time_gdfenhong_speed') || getcustom('yx_cashback_time_fenxiao_speed') || getcustom('yx_cashback_time_addup_speed',$mv['aid']) || getcustom('yx_cashback_time_team_speed',$mv['aid']) || getcustom('yx_cashback_time_gudong_speed',$mv['aid'])){
                            //如果是加速
                            if($isspeed){
                                if($isspeed == 1){
                                    $param['parent_speed']  = 0;
                                }else if($isspeed == 2){
                                    $param['team_speed']    = 0;
                                }else if(in_array($isspeed, [4,5,6,7,8,9])){
                                    $param['fenhong_speed']    = 0;
                                }else{
                                    $param['cashback_speed'] = 0;
                                }
                            }
                        }
                    }else if($return_type == 2){
                        //创建记录到现在的天数大于等于最大天数，则这次结束
                        if($chaday>=$day){
                            $status  = 2;
                        }else{
                            $status  = 1;
                        }
                    }else if($return_type==3){
                        if($send_circle>=$mv['circle_max']){
                            $status = 2;
                        }else{
                            $status = 1;
                        }
                    }
                }else if($send_all >= $allmoney ){
                    $status  = 2;
                    if($send_all > $allmoney){
                        $send_all   = $allmoney;
                        $send_money = $allmoney-$money;
                        if(getcustom('yx_cashback_time_tjspeed') || getcustom('yx_cashback_time_teamspeed') || getcustom('yx_cashback_time_teamfenhong_speed') || getcustom('yx_cashback_time_gdfenhong_speed') || getcustom('yx_cashback_time_fenxiao_speed') || getcustom('yx_cashback_time_addup_speed',$mv['aid']) || getcustom('yx_cashback_time_team_speed',$mv['aid']) || getcustom('yx_cashback_time_gudong_speed',$mv['aid'])){
                            //如果是加速
                            if($isspeed){
                                if($isspeed == 1){
                                    $param['parent_speed'] -= $send_money;
                                }else if($isspeed == 2){
                                    $param['team_speed']   -= $send_money;
                                }else if(in_array($isspeed, [4,5,6,7,8,9])){
                                    $param['fenhong_speed']   -= $send_money;
                                }else{
                                    $param['cashback_speed']   -= $send_money;
                                }
                            }
                        }
                    }else{
                        if(getcustom('yx_cashback_time_tjspeed') || getcustom('yx_cashback_time_teamspeed') || getcustom('yx_cashback_time_teamfenhong_speed') || getcustom('yx_cashback_time_gdfenhong_speed')  || getcustom('yx_cashback_time_fenxiao_speed') || getcustom('yx_cashback_time_addup_speed',$mv['aid']) || getcustom('yx_cashback_time_team_speed',$mv['aid']) || getcustom('yx_cashback_time_gudong_speed',$mv['aid'])){
                            //如果是加速
                            if($isspeed){
                                if($isspeed == 1){
                                    $param['parent_speed']  = 0;
                                }else if($isspeed == 2){
                                    $param['team_speed']    = 0;
                                }else if(in_array($isspeed, [4,5,6,7,8,9])){
                                    $param['fenhong_speed']    = 0;
                                }else{
                                    $param['cashback_speed']= 0;
                                }
                            }
                        }
                    }
                }

                if($mv['back_type'] == 1){
                    $updata['moneystatus']        = $status;
                    $updata['money']              = $send_all;
                    $updata['money_sendnum']      = $new_sendnum;
                }else if($mv['back_type'] == 2){
                    $updata['commissionstatus']   = $status;
                    $updata['commission']         = $send_all;
                    $updata['commission_sendnum'] = $new_sendnum;
                }else if($mv['back_type'] == 3){
                    $updata['scorestatus']        = $status;
                    $updata['score']              = $send_all;
                    $updata['score_sendnum']      = $new_sendnum;
                }
                if(getcustom('yx_cashback_multiply',$mv['aid'])){
                    $updata['status']         = $status;
                }
            }
            if($send_money && $send_money>0){
                if($mv['back_type'] == 1){
                    $updata['money_sendtime']      = time();
                }else if($mv['back_type'] == 2){
                    $updata['commission_sendtime'] = time();
                }else if($mv['back_type'] == 3){
                    $updata['score_sendtime']      = time();
                }
                $cashback_member_check = Db::name('cashback_member')->where('aid',$mv['aid'])->where('mid',$mv['mid'])->where(['cashback_id'=>$mv['cashback_id'],'pro_id'=>$mv['pro_id'],'type'=>$mv['canshtype']])->find();
                if(getcustom('cashback_max',$mv['aid'])){
                    if($mv['cashback_id'] >0 && $mv['pro_id'] >0){
                        if($cashback_member_check){
                            //追加金额
                            if($mv['back_type'] == 1){
                                $money_max = $cashback_member_check['cashback_money'];
                            }else if($mv['back_type'] == 2){
                                $money_max = $cashback_member_check['commission'];
                            }else if($mv['back_type'] == 3){
                                $money_max = $cashback_member_check['score'];
                            }
                            if($cashback_member_check['cashback_money_max'] > 0){
                                if($cashback_member_check['cashback_money_max'] > $money_max){
                                //最大可追加金额
                                    $cashback_money_max = $cashback_member_check['cashback_money_max'] - $money_max;
                                    if($cashback_money_max < $send_money){
                                        $send_money = $cashback_money_max;
                                    }    
                                }else{
                                    $send_money = 0;
                                }
                                if($send_money <=0){
                                    return ;
                                }
                            }
                        }
                    }
                }

                if(getcustom('yx_cashback_multiply',$mv['aid'])){
                    if($return_type==3){
                        $updata['last_circle_yeji'] = $now_circle_yeji??0;
                        $updata['send_circle'] = $mv['send_circle'] + 1;
                        $updata['send_all'] = $send_all;
                        $updata['last_circle_send'] = $send_money;
                    }
                }
                //更新
                $updata['updatetime'] = time();
                $up = Db::name('shop_order_goods_cashback')->where('id',$mv['id'])->update($updata);
                if(!$up) return;
                $remark = '';
                if($mv['back_type'] == 1){
                    $remark = $isspeed?($mv['money_name'].$param['remark']):$mv['money_name'];
                    //发放返现
                    $receive_type = 0;//默认自动
                    if(getcustom('yx_cashback_addup_return')){
                        $cashback_receive_type = Db::name('cashback')->where('aid',$mv['aid'])->where('id',$mv['cashback_id'])->value('receive_type');
                         
                        if($cashback_receive_type ==1){//手动领取
                            $receive_type =1;
                            \app\custom\OrderCustom::setCashbackAddupRecord($mv['aid'],$mv['mid'],$mv['cashback_id'],$send_money,$mv['back_type'],$remark);
                        }
                    }
                    if($receive_type ==0){
                        \app\common\Member::addmoney($mv['aid'],$mv['mid'],$send_money,$remark,0,'','',['type'=>'cashback']);
                    }
                    //累计到参与人统计表
                    Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('cashback_money',$send_money)->update();
                }else if($mv['back_type'] == 2){
                    $remark = $isspeed?($mv['commission_name'].$param['remark']):$mv['commission_name'];
                    //发放返现
                   
                    $receive_type = 0;//默认自动
                    if(getcustom('yx_cashback_addup_return')){
                        $cashback_receive_type = Db::name('cashback')->where('aid',$mv['aid'])->where('id',$mv['cashback_id'])->value('receive_type');
                        
                        if($cashback_receive_type ==1){//手动领取
                            $receive_type =1;
                            \app\custom\OrderCustom::setCashbackAddupRecord($mv['aid'],$mv['mid'],$mv['cashback_id'],$send_money,$mv['back_type'],$remark);
                        }
                    }
                    if($receive_type ==0){
                        \app\common\Member::addcommission($mv['aid'],$mv['mid'],$mv['mid'],$send_money,$remark);
                    }
                    //累计到参与人统计表
                    Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('commission',$send_money)->update();
                }else if($mv['back_type'] == 3){
                    $remark = $isspeed?($mv['score_name'].$param['remark']):$mv['score_name'];
                    //发放返现
                    $receive_type = 0;//默认自动
                    if(getcustom('yx_cashback_addup_return')){
                        $cashback_receive_type = Db::name('cashback')->where('aid',$mv['aid'])->where('id',$mv['cashback_id'])->value('receive_type');
                       
                        if($cashback_receive_type ==1){//手动领取
                            $receive_type =1;
                            
                            \app\custom\OrderCustom::setCashbackAddupRecord($mv['aid'],$mv['mid'],$mv['cashback_id'],$send_money,$mv['back_type'],$remark);
                        }
                    }
                    if($receive_type ==0) {
                        \app\common\Member::addscore($mv['aid'], $mv['mid'], $send_money, $remark);
                    }
                    //累计到参与人统计表
                    Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('score',$send_money)->update();
                }

                // 释放加速
                if(getcustom('yx_cashback_time',$mv['aid']) && $isspeed == 0 && $send_money){
                    self::shifangJiasu($mv['aid'],$mv['mid'],$send_money,$mv['back_type']);
                }

                //写入发放日志
                $cashback = ['id'=>$mv['cashback_id'],'back_type'=>$mv['back_type']];
                $og       = ['proid'=>$mv['pro_id'],'ordertype'=>$mv['canshtype'],'id'=>$mv['sog_id']];
                if(getcustom('yx_cashback_multiply',$mv['aid'])){
                    //倍增返现独立日志备注
                    if($return_type==3 && $updata['send_circle']>0){
                        $remark = '第'.$updata['send_circle'].'期释放';
                    }
                }
                $res_cashback_do_log = self::cashbackMemerDoLog($mv['aid'],$mv['mid'],$cashback,$og,$send_money,$remark);
            }
            if(getcustom('yx_cashback_yongjin',$mv['aid'])){
                //记录累计返现金额
                Db::name('member')->where('id',$mv['mid'])->inc('cashback_total',$send_money)->update();
            }
            return ['status'=>1,'param'=>$param];
        }
    }

    public static function deal_maidancashback($aid,$order)
    {
        if(getcustom('yx_cashback_maidan')){
            //购物返现 买单返现
            $cashbacklist = Db::name('cashback')
                ->where('aid',$aid)
                ->where('bid',0)
                ->where('fwtype',4)
                ->where('maidan_minpaymoney','<=',$order['paymoney'])
                ->where('starttime','<',$order['paytime'])
                ->where('endtime','>',$order['paytime'])
                ->order('sort desc')
                ->select()->toArray();
            //查询购买用户
            $member = Db::name('member')->where('id',$order['mid'])->find();
            if($member && $cashbacklist){
                //返现类型 1、余额 2、佣金 3、积分 小数位数
                $money_weishu = 2;$commission_weishu = 2;$score_weishu = 0;
                if(getcustom('member_money_weishu')){
                    $money_weishu = Db::name('admin_set')->where('aid',$aid)->value('member_money_weishu');
                }
                if(getcustom('fenhong_money_weishu')){
                    $commission_weishu = Db::name('admin_set')->where('aid',$aid)->value('fenhong_money_weishu');
                }
                if(getcustom('score_weishu')){
                    $score_weishu = Db::name('admin_set')->where('aid',$aid)->value('score_weishu');
                }
                foreach($cashbacklist as $v){

                    //判断仅平台 还是仅商户
                    if(($v['maidan_type'] == 1 && $order['bid'] != 0) || ($v['maidan_type'] == 2 && $order['bid'] == 0)){
                        continue;
                    }

                    //判断是否超出最高支付额度
                    if($v['maidan_maxpaymoney']>0 && $order['paymoney'] > $v['maidan_maxpaymoney']){
                        continue;
                    }

                    $gettj = explode(',',$v['gettj']);
                    if(!in_array('-1',$gettj) && !in_array($member['levelid'],$gettj)){ //不是所有人
                        continue;
                    }

                    $back_ratio = $v['back_ratio'];//返现利率
                    //如果返现利率大于0
                    if($back_ratio>0){
                        //计算返现
                        $back_price = $back_ratio*$order['paymoney']/100;

                        //返现类型 1、余额 2、佣金 3、积分
                        if($v['back_type'] == 1 ){
                            $back_price = dd_money_format($back_price,$money_weishu);
                        }else if($v['back_type']== 2){
                            $back_price = dd_money_format($back_price,$commission_weishu);
                        }else if($v['back_type'] == 3){
                            $back_price = dd_money_format($back_price,$score_weishu);
                        }

                        $return_type = 0;//发放类型 0：立即发放 1、自定义 2、阶梯
                        if(getcustom('yx_cashback_time') || getcustom('yx_cashback_stage')){
                            $return_type = $v['return_type'];
                        }

                        //构建商品信息
                        $og = [];
                        $og['aid']   = $order['aid'];
                        $og['bid']   = $order['bid'];
                        $og['mid']   = $order['mid'];
                        $og['id']    = $order['id'];
                        $og['proid'] = 0;
                        $og['ordertype']  = 'maidan';

                        //记录参与的会员
                        $cashback_member_check = Db::name('cashback_member')->where(['cashback_id'=>$v['id'],'pro_id'=>$og['proid'],'type'=>'maidan'])->where('mid',$order['mid'])->where('aid',$order['aid'])->find();
                        if(!$cashback_member_check){
                            $cashback_member = [];
                            $cashback_member['aid']          = $order['aid'];
                            $cashback_member['mid']          = $order['mid'];
                            $cashback_member['cashback_id']  = $v['id'];
                            $cashback_member['back_type']    = $v['back_type'];
                            $cashback_member['pro_id']       = 0;
                            $cashback_member['pro_num']      = 0;
                            $cashback_member['type']         = 'maidan';
                            $cashback_member['cashback_money_max'] = 0;
                            $cashback_member['create_time']        = time();
                            $insert = Db::name('cashback_member')->insert($cashback_member);
                            $cashback_member_check = Db::name('cashback_member')->where(['cashback_id'=>$v['id'],'pro_id'=>$og['proid'],'type'=>'maidan'])->where('mid',$order['mid'])->where('aid',$order['aid'])->find();
                        }

                        if(!$return_type){
                            if($back_price>0){
                                if($v['back_type'] == 1 ){
                                    \app\common\Member::addmoney($aid,$order['mid'],$back_price,$v['name'],0,'','',['type'=>'cashback']);
                                    //累计到参与人统计表
                                    Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('cashback_money',$back_price)->update();
                                }
                                if($v['back_type'] == 2){
                                    \app\common\Member::addcommission($aid,$order['mid'],$order['mid'],$back_price,$v['name']);
                                    //累计到参与人统计表
                                    Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('commission',$back_price)->update();
                                }
                                if($v['back_type'] == 3){
                                    \app\common\Member::addscore($aid,$order['mid'],$back_price,$v['name']);
                                    //累计到参与人统计表
                                    Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('score',$back_price)->update();
                                }
                                if(getcustom('yx_cashback_time')){
                                    //直接发放
                                    \app\custom\OrderCustom::deal_first_cashback($aid,$order['mid'],$back_price,$og,$v,0,'maidan');
                                }
                                //写入发放日志
                                \app\custom\OrderCustom::cashbackMemerDoLog($order['aid'],$order['mid'],$v,$og,$back_price);
                            }
                        }elseif($return_type==4){//叠加递减
                            if(getcustom('yx_cashback_addup_return')){
                                if($back_price > 0){
                                    \app\custom\OrderCustom::cashbackAddup($aid,$order['mid'],$v['id'],$back_price,t('购物返现'));
                                }
                            }
                        }else{
                            if($back_price>0){
                                if(getcustom('yx_cashback_time') || getcustom('yx_cashback_stage')){
                                    //处理自定义第一次发放
                                    \app\custom\OrderCustom::deal_first_cashback($aid,$order['mid'],$back_price,$og,$v,$return_type,'maidan');
                                }
                            }
                        }
                    }

                }
            }
        }
    }

    //购物返现记录
    public static function cashback_log($aid,$mid,$cashback_id,$og_id,$back_price,$remark=''){
         if(getcustom('yx_cashback_log',$aid)){
             if($back_price==0) return ;
             $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
             if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];


             $cashback_og = Db::name('shop_order_goods_cashback')->where('cashback_id',$cashback_id)->where('sog_id',$og_id)->find();
             $after = $member['cashback_price'] + $back_price;
             if(!$cashback_og || $cashback_og['return_type']!=3){
                 $update_member = ['cashback_price'=>$after];
                 Db::name('member')->where('aid',$aid)->where('id',$mid)->update($update_member);
             }
             $log = [];
             $log['aid'] = $aid;
             $log['mid'] = $mid;
             $log['cashback_id'] = $cashback_id;
             $log['og_id'] = $og_id;
             $log['back_price'] = $back_price;
             $log['after'] = $after;
             $log['remark'] = $remark;
             $log['createtime'] = time();
             $log['return_type'] = $cashback_og['return_type']??0;
             if(getcustom('yx_cashback_multiply')){
                 if($cashback_og['send_circle']>0 && !$remark){
                     $remark = '第'.$cashback_og['send_circle'].'期释放';
                 }
                 if(!$remark){
                     $remark = '每日释放';
                 }
                 $log['remark'] = $remark;
                 $log['circle_yeji'] = $cashback_og['last_circle_yeji'];
                 $log['send_circle'] = $cashback_og['send_circle'];
             }

             Db::name('cashback_log')->insert($log);
             return true;
         }
    }

    //倍增方式购物返现
    public function deal_multiply_cashback($aid,$mid,$back_price,$og,$v,$type = 0,$canshtype = 'shop',$order_mid=0){
        if(getcustom('yx_cashback_multiply') ){
            //自定义第一次发放
            //参数为 ：back_price总返回数额 og订单商品 v购物返现设置 type发放类型 0:立即发放（前面已发放过） 1：自定义发放（需要在这发放）canshtype 记录类型

            //返现类型 1、余额 2、佣金 3、积分 小数位数
            $money_weishu = 2;$commission_weishu = 2;$score_weishu = 0;
            if(getcustom('member_money_weishu',$aid)){
                $money_weishu = Db::name('admin_set')->where('aid',$aid)->value('member_money_weishu');
            }
            if(getcustom('fenhong_money_weishu',$aid)){
                $commission_weishu = Db::name('admin_set')->where('aid',$aid)->value('fenhong_money_weishu');
            }
            if(getcustom('score_weishu',$aid)){
                $score_weishu = Db::name('admin_set')->where('aid',$aid)->value('score_weishu');
            }

            $return_type = $v['return_type'];//返现时间类型
            if($type != $return_type){
                return;
            }
            //计算平台总业绩
            $res_lirun = self::getPlateLirun($aid);
            $yeji_type = 0;
            if(getcustom('active_coin') && getcustom('yx_cashback_multiply_yejitype')){
                $yeji_type = Db::name('admin_set')->where('aid',$aid)->value('cashback_multiply_yeji_type');
            }
            if($yeji_type==1){
                $lirun = $res_lirun['active_coin_total'];
            }else{
                $lirun = $res_lirun['lirun']??0;
//                $this_lirun = $og['totalprice']-$og['cost_price']*$og['num'];
//                $lirun = bcadd($lirun,$this_lirun,2);
            }
            $data = [];
            $data['cashback_id'] = $v['id'];//活动id
            $data['totalprice'] = $og['totalprice'];//消费总金额
            $data['first_circle'] = $v['first_circle']??0;//首期返现比例
            $data['first_circle_yeji'] = $lirun??0;//首期平台业绩
            $data['last_circle_yeji'] = $lirun??0;//上期平台业绩
            $data['circle_add'] = $v['circle_add']??0;//增值比例
            $data['circle_max'] = $v['circle_max']??0;//返现总期数
            $data['back_price'] = $back_price;//返现总金额
            $data['send_circle'] = 0;//已返现期数
            $data['return_type'] = $return_type;//返现时间
            if(getcustom('yx_cashback_multiply_yejitype')){
                $data['yeji_type'] = $yeji_type;//业绩类型 0商品利润 1
            }
            $data['circle_add_fail'] = $v['circle_add_fail'];//业绩未达标 0不发放 1按实际增长发放

            //冻结余额
            $send_all = 0;
            $cashback_lock_status = 0;
            if(getcustom('yx_cashback_decmoney_lock',$aid)){
                $cashback_lock_status = Db::name('admin_set')->where('aid',$aid)->value('cashback_decmoney_lock');
            }
            if($cashback_lock_status){
                $member_lock = Db::name('member')->where('id', $mid)->value('cashback_lock');
                if($member_lock>$back_price){
                    $send_all = $back_price;
                    $data['status'] = 2;
                }else{
                    $send_all = $member_lock;
                }
                //扣除冻结余额
                \app\custom\MemberCustom::add_cashback_lock($aid,$mid,$send_all*-1,'返现扣除冻结余额');
                //增加余额
                \app\common\Member::addmoney($aid,$mid,$send_all,'返现'.t('冻结余额').'释放',0,'','',['type'=>'cashback']);
            }
            $data['send_all'] = $send_all;

            if($v['back_type'] == 1){//余额
                $data['moneystatus']    = 1;
                $data['allmoney']       = $back_price;//总返回数值
                $data['money_sendtime'] = time();//发放时间
                $data['money_name']     = $v['name'];;//返现名称
                $data['money']       = $send_all;//总返回数值
            }else if($v['back_type'] == 2){//佣金
                $data['commissionstatus']    = 1;
                $data['allcommission']       = $back_price;
                $data['commission_sendtime'] = time();
                $data['commission_name']     = $v['name'];;//返现名称
                $data['commission']       = $send_all;//总返回数值
            }else if($v['back_type'] == 3){//积分
                $data['scorestatus']    = 1;
                $data['allscore']       = $back_price;
                $data['score_sendtime'] = time();
                $data['score_name']     = $v['name'];;//返现名称
                $data['score']       = $send_all;//总返回数值
            }


            //查询是否有此商品返现表
            $goods_cashback = Db::name('shop_order_goods_cashback')->where('mid',$mid)->where('sog_id',$og['id'])->where('pro_id',$og['proid'])->where('back_type',$v['back_type'])->where('canshtype',$canshtype)->where('cashback_id',$v['id'])->field('id')->find();
            if($goods_cashback){
                $data['updatetime'] = time();
                Db::name('shop_order_goods_cashback')->where('id',$goods_cashback['id'])->update($data);
                $gcid = $goods_cashback['id'];
            }else{
                $data['aid']    = $og['aid'];
                $data['bid']    = $og['bid'];
                $data['mid']    = $mid;
                $data['sog_id'] = $og['id'];//订单商品id
                $data['pro_id'] = $og['proid'];//订单商品id
                $data['back_type'] = $v['back_type'];//返回类型 1：余额 2：佣金 3：积分
                $data['canshtype'] = $canshtype;//购物返回类型 如商城 shop
                $data['createtime'] = time();//后补加的
                if(getcustom('yx_cashback_pid',$aid)) {
                    $data['order_mid'] = $order_mid;
                }
                $gcid = Db::name('shop_order_goods_cashback')->insertGetId($data);
            }
            if(getcustom('yx_cashback_log',$aid)){
                //第一次释放时先增加总额
                self::cashback_log($aid,$mid,$v['id'],$og['id'],$back_price,'购物订单ID-'.$og['orderid'].'商品ID'.$og['proid'].'新增');
            }
            //首期立即发放
            $sysset = Db::name('admin_set')->where('aid',$aid)->find();
            self::deal_multiplecashback($sysset,$og['id']);
        }
    }
    //购物返现-叠加递减
    public static function cashbackAddup($aid,$mid,$cashback_id,$back_price,$remark='',$weishu = 2){
         if(getcustom('yx_cashback_addup_return')){
             //先查记录
            $addup = Db::name('cashback_addup')->where('aid',$aid)->where('mid',$mid)->find(); 
            if($addup){
                $after = $new_money = dd_money_format($addup['back_price'] + $back_price,$weishu);
                Db::name('cashback_addup')->where('aid',$aid)->where('mid',$mid)->update(['back_price' =>$new_money]);
            }else{
                $insert = [
                    'aid' => $aid,
                    'mid' => $mid,
                    'back_price' => $back_price,
                    'createtime' => time()
                ];
                Db::name('cashback_addup')->insert($insert);
                $after = $back_price;
            }
            //添加变动记录
            $addup_log = [
                'aid' => $aid,
                'mid' => $mid,
                'cashback_id' => $cashback_id,
                'back_price' =>$back_price,
                'after' => $after,
                'remark' => $remark??'购物返现',
                'createtime' => time()
            ]; 
            Db::name('cashback_addup_log')->insert($addup_log);
         }
    }
    //购物返现-叠加递减不过期，不进入总奖金池
    public static function setCashbackAddupRecord($aid,$mid,$cashback_id,$back_price,$back_type,$remark=''){
        if(getcustom('yx_cashback_addup_return')){
            $insert = [
                'aid' => $aid,
                'mid' => $mid,
                'back_price' => $back_price,
                'money' => $back_price,
                'type' => 1,
                'back_type' => $back_type,
                'cashback_id' => $cashback_id,
                'createtime' => time()
            ];
            Db::name('cashback_addup_record')->insert($insert);
        }
    }
    
    //获取平台毛利润
    public static function getPlateLirun($aid){
         if(getcustom('yx_cashback_multiply')){
             $map = [];
             $map[] = ['og.aid','=',$aid];
             $map[] = ['og.bid','=',0];
             $map[] = ['shop_order.status','in',[1,2,3]];
             $fields = 'og.proid,og.name,og.pic,og.ggname,sum(og.num) num,sum(og.refund_num) refund_num,sum(og.totalprice) totalprice,sum(og.totalprice)/sum(og.num) as avgprice,sum(og.cost_price*og.num) as chengben,sum(og.totalprice-og.cost_price*og.num) lirun,GROUP_CONCAT(",",shop_order.refund_status) as refund_status';
             $totaldata = Db::name('shop_order_goods')->alias('og')->join('shop_order','shop_order.id=og.orderid')->fieldRaw($fields)->where($map)->find();
             $refundList = Db::name('shop_refund_order')->alias('refund')
                 ->join('shop_order','shop_order.id=refund.orderid')
                 ->join('shop_order_goods og','shop_order.id=og.orderid')
                 ->where($map)
                 ->where('refund.refund_status',2)
                 ->group('shop_order.id')
                 ->column('refund.refund_money,sum(refund_num) refund_num');
             //成本，按退款比例计算
             if($refundList){
                 $refundMoneyAll = array_sum(array_column($refundList,'refund_money'));
                 $refundNumAll = array_sum(array_column($refundList,'refund_num'));
                 if($totaldata['totalprice'] > 0)
                     $rate = round($refundMoneyAll/$totaldata['totalprice'],4);
                 else
                     $rate = 0;
                 $totaldata['chengben'] = round($totaldata['chengben']*(1-$rate),2);
                 $totaldata['totalprice'] = round($totaldata['totalprice']-$refundMoneyAll,2);
                 $totaldata['lirun'] = round($totaldata['totalprice'] - $totaldata['chengben'],2);
                 $totaldata['num'] = intval($totaldata['num'] - $refundNumAll);
             }
             if(getcustom('active_coin')){
                 $active_coin_total = Db::name('member_activecoin_log')->where('aid',$aid)->where('value','>',0)->sum('value');
                 $active_coin_set = Db::name('active_coin_set')->where('aid',$aid)->find();
                 $active_coin_total = bcadd($active_coin_total,$active_coin_set['add_activecoin_num'],2);
                 $totaldata['active_coin_total'] = $active_coin_total;
             }
             return $totaldata;
         }
    }

    public static function deal_seckillcashback($aid,$order)
    {
        if(getcustom('yx_cashback_seckill')){
            //购物返现
            $cashbacklist = Db::name('cashback')
                ->where('aid',$aid)
                ->where('bid',0)
                ->where('seckill_join_status',1)
                ->where('starttime','<',$order['paytime'])
                ->where('endtime','>',$order['paytime'])
                ->order('sort desc')
                ->select()->toArray();
            //查询购买用户
            $member = Db::name('member')->where('id',$order['mid'])->find();
            if($member && $cashbacklist){
                //返现类型 1、余额 2、佣金 3、积分 小数位数
                $money_weishu = 2;$commission_weishu = 2;$score_weishu = 0;
                if(getcustom('member_money_weishu')){
                    $money_weishu = Db::name('admin_set')->where('aid',$aid)->value('member_money_weishu');
                }
                if(getcustom('fenhong_money_weishu')){
                    $commission_weishu = Db::name('admin_set')->where('aid',$aid)->value('fenhong_money_weishu');
                }
                if(getcustom('score_weishu')){
                    $score_weishu = Db::name('admin_set')->where('aid',$aid)->value('score_weishu');
                }
                $product = Db::name('seckill_product')->where('id',$order['proid'])->field('id')->find();
                if($product){
                    foreach($cashbacklist as $v){
                        $gettj = explode(',',$v['gettj']);
                        if(!in_array('-1',$gettj) && !in_array($member['levelid'],$gettj)){ //不是所有人
                            continue;
                        }
                        $back_ratio = $v['back_ratio'];//返现利率
                        //如果返现利率大于0
                        if($back_ratio>0){
                            //计算返现
                            $back_price = $back_ratio*$order['totalprice']/100;

                            //返现类型 1、余额 2、佣金 3、积分
                            if($v['back_type'] == 1 ){
                                $back_price = dd_money_format($back_price,$money_weishu);
                            }else if($v['back_type']== 2){
                                $back_price = dd_money_format($back_price,$commission_weishu);
                            }else if($v['back_type'] == 3){
                                $back_price = dd_money_format($back_price,$score_weishu);
                            }

                            $return_type = 0;//发放状态

                            //构建商品信息
                            $og = [];
                            $og['aid']   = $order['aid'];
                            $og['bid']   = $order['bid'];
                            $og['mid']   = $order['mid'];
                            $og['id']    = $order['id'];
                            $og['proid'] = $order['proid'];
                            $og['ordertype']  = 'seckill';

                            //记录参与的会员
                            $cashback_member_check = Db::name('cashback_member')->where(['cashback_id'=>$v['id'],'pro_id'=>$og['proid'],'type'=>'seckill'])->where('mid',$order['mid'])->where('aid',$order['aid'])->find();
                            if(!$cashback_member_check){
                                $cashback_member = [];
                                $cashback_member['aid']          = $order['aid'];
                                $cashback_member['mid']          = $order['mid'];
                                $cashback_member['cashback_id']  = $v['id'];
                                $cashback_member['back_type']    = $v['back_type'];
                                $cashback_member['pro_id']       = $order['proid'];
                                $cashback_member['pro_num']      = $order['num'];
                                $cashback_member['type']               = 'seckill';
                                $cashback_member['cashback_money_max'] = 0;
                                $cashback_member['create_time']        = time();
                                $insert = Db::name('cashback_member')->insert($cashback_member);
                                $cashback_member_check = Db::name('cashback_member')->where(['cashback_id'=>$v['id'],'pro_id'=>$og['proid'],'type'=>'seckill'])->where('mid',$order['mid'])->where('aid',$order['aid'])->find();
                            }else{
                                Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('pro_num',$order['num'])->update();
                            }

                            if(!$return_type){
                                if($back_price>0){
                                    if($v['back_type'] == 1 ){
                                        \app\common\Member::addmoney($aid,$order['mid'],$back_price,$v['name'].'购物返现',0,'','',['type'=>'cashback']);
                                        //累计到参与人统计表
                                        Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('cashback_money',$back_price)->update();
                                    }
                                    if($v['back_type'] == 2){
                                        \app\common\Member::addcommission($aid,$order['mid'],$order['mid'],$back_price,$v['name'].'购物返现');
                                        //累计到参与人统计表
                                        Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('commission',$back_price)->update();
                                    }
                                    if($v['back_type'] == 3){
                                        \app\common\Member::addscore($aid,$order['mid'],$back_price,$v['name'].'购物返现');
                                        //累计到参与人统计表
                                        Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('score',$back_price)->update();
                                    }
                                    if(getcustom('yx_cashback_time')){
                                        //直接发放
                                        \app\custom\OrderCustom::deal_first_cashback($aid,$order['mid'],$back_price,$og,$v,0,'seckill');
                                    }
                                    //写入发放日志
                                    \app\custom\OrderCustom::cashbackMemerDoLog($order['aid'],$order['mid'],$v,$og,$back_price);
                                }
                            }else{
                                if($back_price>0){
                                    if(getcustom('yx_cashback_time') || getcustom('yx_cashback_stage')){
                                        //处理自定义第一次发放
                                        \app\custom\OrderCustom::deal_first_cashback($aid,$order['mid'],$back_price,$og,$v,$return_type,'seckill');
                                    }
                                }
                            }
                        }

                    }
                }

            }
        }
    }

    public static function dec_cashback($aid,$mid,$money,$money_log_id=0,$remark=''){
         if(getcustom('yx_cashback_decmoney',$aid)){
             $cashback_decmoney_status = Db::name('admin_set')->where('aid',$aid)->value('cashback_decmoney_status');
             if($cashback_decmoney_status!=1){
                 //未开启功能
                 return true;
             }
             //余额有增加时扣除待返现余额
             if($money<=0){
                 return true;
             }
             $where = [];
             $where[] = ['aid','=',$aid];
             $where[] = ['mid','=',$mid];
             $where[] = ['status','<>',2];
             $where[] = ['moneystatus','<>',2];
             $where[] = ['commissionstatus','<>',2];
             $where[] = ['scorestatus','<>',2];
             $list = Db::name('shop_order_goods_cashback')
                 ->where($where)
                 ->order('id asc')
                 ->select()
                 ->toArray();
             if(!$list){
                 return true;
             }
             foreach($list as $mv){
                 if($money<=0){
                     continue;
                 }
                 if($mv['back_type']==1){
                    $remain = bcsub($mv['allmoney'],$mv['money'],2);
                 }
                 if($mv['back_type']==2){
                     $remain = bcsub($mv['allcommission'],$mv['commission'],2);
                 }
                 if($mv['back_type']==3){
                     $remain = bcsub($mv['allscore'],$mv['score'],2);
                 }
                 if($mv['return_type']==3){
                     //倍增返现
                     $remain = bcsub($mv['back_price'],$mv['send_all'],2);
                 }
                 $status = 1;
                 $updata = [];
                 $send_money = $money;
                 if($send_money>=$remain){
                     $send_money = $remain;
                     $status = 2;
                 }
                 $money = bcsub($money,$send_money,2);

                 if($mv['back_type']==1) {
                     $send_all = bcadd($mv['money'],$send_money,2);
                     $updata['moneystatus'] = $status;
                     $updata['money'] = $send_all;
                 }
                 if($mv['back_type']==2) {
                     $send_all = bcadd($mv['commission'],$send_money,2);
                     $updata['commissionstatus'] = $status;
                     $updata['commission'] = $send_all;
                 }
                 if($mv['back_type']==3) {
                     $send_all = bcadd($mv['score'],$send_money,2);
                     $updata['scorestatus'] = $status;
                     $updata['score'] = $send_all;
                 }
                 if($mv['return_type']==3){
                     $send_all = bcadd($mv['money'],$send_money,2);
                     //倍增返现
                     $updata['send_all'] = $send_all;
                     $updata['status'] = $status;
                 }
                 $updata['updatetime'] = time();
                 $up = Db::name('shop_order_goods_cashback')->where('id',$mv['id'])->update($updata);

                 $cashback_member_check = Db::name('cashback_member')->where('aid',$mv['aid'])->where('mid',$mv['mid'])->where(['cashback_id'=>$mv['cashback_id'],'pro_id'=>$mv['pro_id'],'type'=>$mv['canshtype']])->find();

                 //$new_remark = '增加余额扣除('.$remark.')，余额记录ID：'.$money_log_id;
                 $new_remark = '加速释放';
                 if($mv['back_type'] == 1){
                     //累计到参与人统计表
                     Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('cashback_money',$send_money)->update();
                 }else if($mv['back_type'] == 2){
                     //累计到参与人统计表
                     Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('commission',$send_money)->update();
                 }else if($mv['back_type'] == 3){
                     //累计到参与人统计表
                     Db::name('cashback_member')->where('id',$cashback_member_check['id'])->inc('score',$send_money)->update();
                 }
                 //写入发放日志
                 $cashback = ['id'=>$mv['cashback_id'],'back_type'=>$mv['back_type']];
                 $og       = ['proid'=>$mv['pro_id'],'ordertype'=>$mv['canshtype'],'id'=>$mv['sog_id']];

                 self::cashbackMemerDoLog($mv['aid'],$mv['mid'],$cashback,$og,$send_money,$new_remark);
             }
         }
    }

     // 一级分销加速记录
    public static function dealFxspeed($log){
        
        if(getcustom('yx_cashback_time_fenxiao_speed') && getcustom('yx_cashback_time') && $log){
            $time = $log['createtime'];
            $nowtime = strtotime(date("Y-m-d"));
            $aid = $log['aid'];
            $speed_type = 6;
            $remark = t('一级').'分销加速';

            //返现类型 1、余额 2、佣金 3、积分 小数位数
            $money_weishu = 2;$commission_weishu = 2;$score_weishu = 0;
            if(getcustom('member_money_weishu')){
                $money_weishu = Db::name('admin_set')->where('aid',$aid)->value('member_money_weishu');
            }
            if(getcustom('fenhong_money_weishu')){
                $commission_weishu = Db::name('admin_set')->where('aid',$aid)->value('fenhong_money_weishu');
            }
            if(getcustom('score_weishu')){
                $score_weishu = Db::name('admin_set')->where('aid',$aid)->value('score_weishu');
            }

            // //先执行今天发放奖励
            // self::deal_autocashback($log['aid']);
            // Db::startTrans();
            //购物返现
            $where = [];
            $where[] = ['aid','=',$log['aid']];
            $where[] = ['starttime','<',$time];
            $where[] = ['endtime','>',$time];
            if(getcustom('yx_cashback_time_speed')){
                $where[] = ['time_speed','=',1];
            }
            $lists = Db::name('cashback')
                ->where($where)
                ->order('sort desc')
                ->select()->toArray();

            foreach ($lists as $k => $v) {

                $fenxiaodata = json_decode($v['fenxiaodata'],true);
                $bili = $fenxiaodata[$log['levelid']];
                if($bili > 0){
                    $fenhong_speed = dd_money_format($bili*$log['commission']/100); 

                    if($fenhong_speed<=0){
                        continue;
                    }
                    //查询未返完的记录
                    $where = [];
                    $where[] = ['aid','=',$log['aid']];
                    $where[] = ['mid','=',$log['mid']];
                    $where[] = ['cashback_id','=',$v['id']];
                    $where[] = ['back_type','=',$v['back_type']];
                    $where[] = ['money_sendtime|commission_sendtime|score_sendtime','>=',$nowtime];
                    $where[] = ['moneystatus|commissionstatus|scorestatus','=',1];
                    $cashbacklist = Db::name('shop_order_goods_cashback')
                        ->where($where)
                        ->order('id asc')
                        ->select()
                        ->toArray();
                    if($cashbacklist){
                        foreach($cashbacklist as $mv){
                            //返现类型 1、余额 2、佣金 3、积分
                            if($v['back_type'] == 1 ){
                                $fenhong_speed = dd_money_format($fenhong_speed,$money_weishu);
                            }else if($v['back_type']== 2){
                                $fenhong_speed = dd_money_format($fenhong_speed,$commission_weishu);
                            }else if($v['back_type'] == 3){
                                $fenhong_speed = dd_money_format($fenhong_speed,$score_weishu);
                            }
                            if($fenhong_speed <= 0){
                                continue;
                            }
                            // dump($fenhong_speed);
                            $res = self::deal_cashbacklist($mv,['isspeed'=>$speed_type,'fenhong_speed'=>$fenhong_speed,'nocheckstatus'=>false,'remark'=>$remark]);
                            // dump($res);
                            if($res && $res['status'] == 1 && $res['param']['fenhong_speed']>0){
                                $fenhong_speed = $res['param']['fenhong_speed'];
                                
                            }else{
                                break;
                            }
                        }
                    }
                }
            }
             // Db::commit();
        }
    }

    // 团队分红,股东分红式 加速记录
    public static function dealFhspeed($log,$last_levelid){
        if((getcustom('yx_cashback_time_teamfenhong_speed') || getcustom('yx_cashback_time_gdfenhong_speed')) && getcustom('yx_cashback_time') && $log){
            $time = $log['createtime'];
            $nowtime = strtotime(date("Y-m-d"));

            $speed_type = $log['type']=='teamfenhong'?4:5;
            $remark = $log['type']=='teamfenhong'?t('团队分红').'加速':t('股东分红').'加速';

            //返现类型 1、余额 2、佣金 3、积分 小数位数
            $money_weishu = 2;$commission_weishu = 2;$score_weishu = 0;
            if(getcustom('member_money_weishu')){
                $money_weishu = Db::name('admin_set')->where('aid',$log['aid'])->value('member_money_weishu');
            }
            if(getcustom('fenhong_money_weishu')){
                $commission_weishu = Db::name('admin_set')->where('aid',$log['aid'])->value('fenhong_money_weishu');
            }
            if(getcustom('score_weishu')){
                $score_weishu = Db::name('admin_set')->where('aid',$log['aid'])->value('score_weishu');
            }

            //  //先执行今天发放奖励
            // self::deal_autocashback($log['aid']);

            // Db::startTrans();
            //购物返现
            $where = [];
            $where[] = ['aid','=',$log['aid']];
            $where[] = ['starttime','<',$time];
            $where[] = ['endtime','>',$time];
            if(getcustom('yx_cashback_time_speed')){
                $where[] = ['time_speed','=',1];
            }
            $lists = Db::name('cashback')
                ->where($where)
                ->order('sort desc')
                ->select()->toArray();
            foreach ($lists as $k => $v) {
                if(getcustom('yx_cashback_time_teamfenhong_speed') && $log['type']=='teamfenhong'){
                    $fenhongdata = $v['teamfenhongdata'];
                }
                if(getcustom('yx_cashback_time_gdfenhong_speed') && $log['type']=='fenhong'){
                    $fenhongdata = $v['gdfenhongdata'];
                }
                //团队分红式加速
                if(!empty($fenhongdata)){
                    $fenhongdata = json_decode($fenhongdata,true);
                    $bili = $fenhongdata[$log['levelid']];
                   

                    // 加速极差
                    $last_bili = 0;
                    if(getcustom('yx_cashback_time_teamfenhong_speed') && $log['type']=='teamfenhong' && $v['teamjicha_status'] == 1 && $last_levelid){
                        $last_bili = $fenhongdata[$last_levelid];
                        $bili -= $last_bili;
                    }
                    
                    $fenhong_speed = dd_money_format($bili*$log['commission']/100); 
                    if($fenhong_speed<=0){
                        continue;
                    }
                    //查询未返完的记录
                    $where = [];
                    $where[] = ['aid','=',$log['aid']];
                    $where[] = ['mid','=',$log['mid']];
                    $where[] = ['cashback_id','=',$v['id']];
                    $where[] = ['back_type','=',$v['back_type']];
                    $where[] = ['money_sendtime|commission_sendtime|score_sendtime','>=',$nowtime];
                    $where[] = ['moneystatus|commissionstatus|scorestatus','=',1];
                    $cashbacklist = Db::name('shop_order_goods_cashback')
                        ->where($where)
                        ->order('id asc')
                        ->select()
                        ->toArray();

                    if($cashbacklist){
                        foreach($cashbacklist as $mv){

                            //返现类型 1、余额 2、佣金 3、积分
                            if($v['back_type'] == 1 ){
                                $fenhong_speed = dd_money_format($fenhong_speed,$money_weishu);
                            }else if($v['back_type']== 2){
                                $fenhong_speed = dd_money_format($fenhong_speed,$commission_weishu);
                            }else if($v['back_type'] == 3){
                                $fenhong_speed = dd_money_format($fenhong_speed,$score_weishu);
                            }
                            if($fenhong_speed <= 0){
                                continue;
                            }

                            $res = self::deal_cashbacklist($mv,['isspeed'=>$speed_type,'fenhong_speed'=>$fenhong_speed,'nocheckstatus'=>false,'remark'=>$remark]);
                            // dump($res);
                            if($res && $res['status'] == 1 && $res['param']['fenhong_speed']>0){
                                $fenhong_speed = $res['param']['fenhong_speed'];
                            }else{
                                break;
                            }
                        }
                    }
                }
            }
            // Db::commit();
        }
    }

    // 自身释放加速上级/团队/股东释放
    private function shifangJiasu($aid,$mid,$send_money,$type){
        if(getcustom('yx_cashback_time',$aid) && $send_money>0){
            $time = time();
            $nowtime = strtotime(date("Y-m-d"));

            //返现类型 1、余额 2、佣金 3、积分 小数位数
            $money_weishu = 2;$commission_weishu = 2;$score_weishu = 0;
            if(getcustom('member_money_weishu')){
                $money_weishu = Db::name('admin_set')->where('aid',$aid)->value('member_money_weishu');
            }
            if(getcustom('fenhong_money_weishu')){
                $commission_weishu = Db::name('admin_set')->where('aid',$aid)->value('fenhong_money_weishu');
            }
            if(getcustom('score_weishu')){
                $score_weishu = Db::name('admin_set')->where('aid',$aid)->value('score_weishu');
            }

            // 直推式加速
            if(getcustom('yx_cashback_time_addup_speed')){
                $remark = t('一级').'加速';
                // 上级
                $pid = Db::name('member')->where('aid',$aid)->where('id',$mid)->value('pid');
                if($pid){
                    //查询未返完的记录
                    $where = [];
                    $where[] = ['aid','=',$aid];
                    $where[] = ['mid','=',$pid];
                    $where[] = ['back_type','=',$type];
                    if($type == 1){
                        $where[] = ['money_sendtime','>=',$nowtime];
                        $where[] = ['moneystatus','=',1];
                    }
                    if($type == 2){
                        $where[] = ['commission_sendtime','>=',$nowtime];
                        $where[] = ['commissionstatus','=',1];
                    }
                    if($type == 3){
                        $where[] = ['score_sendtime','>=',$nowtime];
                        $where[] = ['scorestatus','=',1];
                    }
                    
                    $cashbacklist = Db::name('shop_order_goods_cashback')
                        ->where($where)
                        ->order('id asc')
                        ->select()
                        ->toArray();
                    if($cashbacklist){
                        $up_remain_money = 0;
                        foreach($cashbacklist as $mv){

                            $where = [];
                            $where[] = ['aid','=',$aid];
                            $where[] = ['id','=',$mv['cashback_id']];
                            $where[] = ['starttime','<',$time];
                            $where[] = ['endtime','>',$time];
                            if(getcustom('yx_cashback_time_speed')){
                                $where[] = ['time_speed','=',1];
                            }
                            $cashback = Db::name('cashback')->where($where)->find();
                            if(!$cashback){
                                continue;
                            }
                            $addupdata = $cashback['addupdata'];
                            $addupdata = json_decode($addupdata,true);
        
                            $levelid = Db::name('member')->where('aid',$aid)->where('id',$pid)->value('levelid');
                            $bili = $addupdata[$levelid];
                           
                            $fenhong_speed = $bili*$send_money/100; 
                            if($up_remain_money > 0){
                                $fenhong_speed = $up_remain_money;
                            }

                            //返现类型 1、余额 2、佣金 3、积分
                            if($type == 1 ){
                                $fenhong_speed = dd_money_format($fenhong_speed,$money_weishu);
                            }else if($type == 2){
                                $fenhong_speed = dd_money_format($fenhong_speed,$commission_weishu);
                            }else if($type == 3){
                                $fenhong_speed = dd_money_format($fenhong_speed,$score_weishu);
                            }
                            if($fenhong_speed <= 0){
                                continue;
                            }

                            $res = self::deal_cashbacklist($mv,['isspeed'=>9,'fenhong_speed'=>$fenhong_speed,'nocheckstatus'=>false,'remark'=>$remark]);
                            // dump($res);die;
                            if($res && $res['status'] == 1 && $res['param']['fenhong_speed']>0){
                                $up_remain_money = $res['param']['fenhong_speed'];
                            }else{;
                                break;
                            }
                        }
                    }
                    
                }
            }
            if(getcustom('yx_cashback_time_team_speed')){
                $remark = t('团队分红').'加速';
                $default_cid = Db::name('member_level_category')->where('aid',$aid)->where('isdefault', 1)->value('id');
                $default_cid = $default_cid ? $default_cid : 0;
                $memberlevel = Db::name('member_level')->where('aid',$aid)->where('cid', $default_cid)->column('sort','id');
                
                // 团队
                $path = Db::name('member')->where('aid',$aid)->where('id',$mid)->value('path');
                if($path){
                    $parentList = Db::name('member')
                        ->where('aid',$aid)
                        ->where('id','in',$path)
                        ->field('id,levelid,path')
                        ->order(Db::raw('field(id,'.$path.')'))
                        ->select()
                        ->toArray();
                    if($parentList){
                        $last_levelid = 0;
                        $parentList = array_reverse($parentList);
                        foreach ($parentList as $key => $parent) {

                            //查询未返完的记录
                            $where = [];
                            $where[] = ['aid','=',$aid];
                            $where[] = ['mid','=',$parent['id']];
                            $where[] = ['back_type','=',$type];
                            if($type == 1){
                                $where[] = ['money_sendtime','>=',$nowtime];
                                $where[] = ['moneystatus','=',1];
                            }
                            if($type == 2){
                                $where[] = ['commission_sendtime','>=',$nowtime];
                                $where[] = ['commissionstatus','=',1];
                            }
                            if($type == 3){
                                $where[] = ['score_sendtime','>=',$nowtime];
                                $where[] = ['scorestatus','=',1];
                            }
                            $cashbacklist = Db::name('shop_order_goods_cashback')
                                ->where($where)
                                ->order('id asc')
                                ->select()
                                ->toArray();
                            if($cashbacklist){
                                $team_remain_money = 0;
                                foreach($cashbacklist as $mv){

                                    $where = [];
                                    $where[] = ['aid','=',$aid];
                                    $where[] = ['id','=',$mv['cashback_id']];
                                    $where[] = ['starttime','<',$time];
                                    $where[] = ['endtime','>',$time];
                                    if(getcustom('yx_cashback_time_speed')){
                                        $where[] = ['time_speed','=',1];
                                    }
                                    $cashback = Db::name('cashback')->where($where)->find();
                                    if(!$cashback){
                                        continue;
                                    }
                                    $teamdata = $cashback['teamdata'];
                                    $teamdata = json_decode($teamdata,true);

                                    $bili = $teamdata[$parent['levelid']];
                                    // 加速极差
                                    $last_bili = 0;
                                    if($cashback['teamjc_status'] == 1 && $last_levelid){
                                        $last_bili = $teamdata[$last_levelid]??0;
                                        $bili -= $last_bili;
                                    }

                                    $fenhong_speed = $bili*$send_money/100;
                                    if($team_remain_money > 0){
                                        $fenhong_speed = $team_remain_money;
                                    } 

                                    //返现类型 1、余额 2、佣金 3、积分
                                    if($type == 1 ){
                                        $fenhong_speed = dd_money_format($fenhong_speed,$money_weishu);
                                    }else if($type== 2){
                                        $fenhong_speed = dd_money_format($fenhong_speed,$commission_weishu);
                                    }else if($type == 3){
                                        $fenhong_speed = dd_money_format($fenhong_speed,$score_weishu);
                                    }
                                    if($fenhong_speed <= 0){
                                        continue;
                                    }

                                    $res = self::deal_cashbacklist($mv,['isspeed'=>7,'fenhong_speed'=>$fenhong_speed,'nocheckstatus'=>false,'remark'=>$remark]);
                                    // dump($res);
                                    if($res && $res['status'] == 1 && $res['param']['fenhong_speed']>0){
                                        $team_remain_money = $res['param']['fenhong_speed'];
                                    }else{
                                        break;
                                    }
                                }
                                if(!$last_levelid || $memberlevel[$parent['levelid']] > $memberlevel[$last_levelid]){
                                    $last_levelid = $parent['levelid'];
                                }
                            }
                        }
                    }
                }
            }
            if(getcustom('yx_cashback_time_gudong_speed')){
                $remark = t('股东分红').'加速';
                // 股东
                $default_cid = Db::name('member_level_category')->where('aid',$aid)->where('isdefault', 1)->value('id');
                $default_cid = $default_cid ? $default_cid : 0;
                $levelids = Db::name('member_level')->where('aid',$aid)->where('cid', $default_cid)->order('sort,id')->column('id');

                $where = [];
                $where[] = ['aid','=',$aid];
                $where[] = ['starttime','<',$time];
                $where[] = ['endtime','>',$time];
                if(getcustom('yx_cashback_time_speed')){
                    $where[] = ['time_speed','=',1];
                }

                $levelidarr = [];
                $cashbacks = Db::name('cashback')->where($where)->select()->toArray();
                if($cashbacks){
                    foreach ($cashbacks as $vo) {
                        if (!empty($vo['gudongdata'])) {
                            $gudongdatainfo = json_decode($vo['gudongdata'],true);
                            foreach ($gudongdatainfo as $levelidkey => $gudongval) {
                                if (!empty($gudongval)) {
                                    $levelidarr[] = $levelidkey;
                                }
                            }
                        }
                    }
                    if($levelidarr){
                        $levelidarr = array_unique($levelidarr);
                    }
                }
                if($levelidarr){
                    foreach ($levelidarr as $key => $levelid) {
                        $gdmids = Db::name('member')->where('aid',$aid)->where('levelid',$levelid)->column('id');
                        if(empty($gdmids)){
                            continue;
                        }
                        foreach ($gdmids as $gdmid) {
                            //查询未返完的记录
                            $where = [];
                            $where[] = ['aid','=',$aid];
                            $where[] = ['mid','=',$gdmid];
                            $where[] = ['back_type','=',$type];
                            if($type == 1){
                                $where[] = ['money_sendtime','>=',$nowtime];
                                $where[] = ['moneystatus','=',1];
                            }
                            if($type == 2){
                                $where[] = ['commission_sendtime','>=',$nowtime];
                                $where[] = ['commissionstatus','=',1];
                            }
                            if($type == 3){
                                $where[] = ['score_sendtime','>=',$nowtime];
                                $where[] = ['scorestatus','=',1];
                            }
                            $cashbacklist = Db::name('shop_order_goods_cashback')
                                ->where($where)
                                ->order('id asc')
                                ->select()
                                ->toArray();
                            if($cashbacklist){
                                $gd_remain_money = 0;
                                foreach($cashbacklist as $mv){

                                    $where = [];
                                    $where[] = ['aid','=',$aid];
                                    $where[] = ['id','=',$mv['cashback_id']];
                                    $where[] = ['starttime','<',$time];
                                    $where[] = ['endtime','>',$time];
                                    if(getcustom('yx_cashback_time_speed')){
                                        $where[] = ['time_speed','=',1];
                                    }
                                    $cashback = Db::name('cashback')->where($where)->find();
                                    if(!$cashback){
                                        continue;
                                    }

                                    $gudongdata = $cashback['gudongdata'];
                                    $gudongdata = json_decode($gudongdata,true);
                                    $bili = $gudongdata[$levelid];
                                    $fenhong_speed = $bili*$send_money/100; 
                                    if($gd_remain_money > 0){
                                        $fenhong_speed = $gd_remain_money;
                                    }

                                    //返现类型 1、余额 2、佣金 3、积分
                                    if($type == 1 ){
                                        $fenhong_speed = dd_money_format($fenhong_speed,$money_weishu);
                                    }else if($type== 2){
                                        $fenhong_speed = dd_money_format($fenhong_speed,$commission_weishu);
                                    }else if($type == 3){
                                        $fenhong_speed = dd_money_format($fenhong_speed,$score_weishu);
                                    }
                                    if($fenhong_speed <= 0){
                                        continue;
                                    }

                                    $res = self::deal_cashbacklist($mv,['isspeed'=>8,'fenhong_speed'=>$fenhong_speed,'nocheckstatus'=>false,'remark'=>$remark]);
                                    // dump($res);
                                    if($res && $res['status'] == 1 && $res['param']['fenhong_speed']>0){
                                        $gd_remain_money = $res['param']['fenhong_speed'];
                                    }else{
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public static function shifang($mv,$param=[]){
        self::deal_cashbacklist($mv);
    }
    //递减返现
    public static function deal_decay_cashback($cashback, $cmid) {
        if (getcustom('yx_cashback_decay')) {
            $cashback_member = Db::name('cashback_member')->where('id',$cmid)->find();
            $aid = $cashback_member['aid'];
            $mid = $cashback_member['mid'];
            $bid = $cashback['bid'] ?? 0;
            if ($cashback_member['decay_back_status'] > 1) return false;

            $money_weishu = 2; $commission_weishu = 2; $score_weishu = 0;
            if (getcustom('member_money_weishu', $aid)) {
                $money_weishu = Db::name('admin_set')->where('aid', $aid)->value('member_money_weishu');
            }
            if (getcustom('fenhong_money_weishu', $aid)) {
                $commission_weishu = Db::name('admin_set')->where('aid', $aid)->value('fenhong_money_weishu');
            }
            if (getcustom('score_weishu', $aid)) {
                $score_weishu = Db::name('admin_set')->where('aid', $aid)->value('score_weishu');
            }

            $weishu = 2;
            if ($cashback_member['return_type'] == 1) {
                $weishu = $money_weishu;
            } else if ($cashback_member['return_type'] == 2) {
                $weishu = $commission_weishu;
            } else if ($cashback_member['return_type'] == 3) {
                $weishu = $score_weishu;
            }

            $epsilon        = pow(10, -$weishu) / 10; //最小值 例如2位小数时为0.001 避免无限循环
            $current_remain = $cashback_member['back_after'];

            $back_ratio = $cashback['back_ratio'] / 100;
            $back_money = $current_remain * $back_ratio;
            if ($cashback['back_type'] == 3) {
                //积分为整数
                $back_money = round($back_money); //四舍五入取整
                $max_allow = (int)floor($current_remain); //返整数部分
                $back_money = max(0, min($back_money, $max_allow));
            } else {
                if ($cashback['back_type'] == 3) {
                    $back_money = (int)floor($current_remain);
                }
                $back_money = round($back_money, $weishu);
            }

            if ($back_money < $epsilon) {
                // 剩余太少，直接发完
                $back_money = $current_remain;
            }
            $back_money = min($back_money, $current_remain);
            $after_money = $current_remain - $back_money;
            $after_money = round($after_money, $weishu);

            if ($after_money < $epsilon) {
                $back_money  = $current_remain;
                $after_money = 0;
            }
            if ($back_money > 0) {
                if($cashback_member['type'] == 'shop'){
                    $ogwhere = [];
                    $ogwhere[] = ['aid','=',$aid];
                    $ogwhere[] = ['bid','=',$bid];
                    $ogwhere[] = ['mid','=',$mid];
                    $ogwhere[] = ['status','=',3];
                    $orwhere = [];
                    if($cashback['fwtype'] == 1){
                        //指定类目
                        $categoryids = explode(',',$cashback['categoryids']);
                        $clist = Db::name('shop_category')->where('pid','in',$categoryids)->select()->toArray();
                        foreach($clist as $vc){
                            $categoryids[] = $vc['id'];
                            $cate2 = Db::name('shop_category')->where('pid',$vc['id'])->find();
                            $categoryids[] = $cate2['id'];
                        }
                        foreach ($categoryids as $c){
                            if(empty($c)) continue;
                            $orwhere[] = Db::raw("find_in_set(".$c.",cid)");
                        }
                        if($orwhere){
                            $orwhere = implode(' or ',$orwhere);
                        }
                    }
                    if($cashback['fwtype'] == 2){
                        //指定商品
                        $productids = explode(',',$cashback['productids']);
                        foreach ($productids as $p){
                            if(empty($p)) continue;
                            $orwhere[] = Db::raw("find_in_set(".$p.",proid)");
                        }
                    }
                    //查询最后一条订单商品
                    $og = Db::name('shop_order_goods')->where($ogwhere)->where($orwhere)->order('createtime','desc')->find();
                    if(empty($og)){
                        $og = Db::name('shop_order_goods')->where('aid', $aid)->where('id', $cashback_member['ogid'])->find();
                    }
                }elseif($cashback_member['type'] == 'maidan'){
                    $maidanOrder = Db::name('maidan_order')->where('aid',$aid)->where('bid',$bid)->where('mid',$mid)->where('status',1)->order('createtime','desc')->find();
                    $og = [
                        'id' => 0,
                        'proid' => 0,
                        'ordertype' => $cashback_member['type'],
                        'orderid' => $cashback_member['orderid'],
                        'ordernum'=> $maidanOrder['ordernum'],
                        'name' => $maidanOrder['title'],
                        'real_totalprice' => $maidanOrder['paymoney'],
                        'createtime' => $maidanOrder['createtime']
                    ];
                }else{
                    return false;
                }
                if ($cashback['back_type'] == 1) {
                    \app\common\Member::addmoney($aid, $mid, $back_money, $cashback['name'] . '购物返现', 0, '', '', ['type' => 'cashback']);
                    //累计到参与人统计表
                    Db::name('cashback_member')->where('id', $cashback_member['id'])->dec('back_after', $back_money)->inc('cashback_money', $back_money)->update();
                }
                if ($cashback['back_type'] == 2) {
                    \app\common\Member::addcommission($aid, $mid, $mid, $back_money, $cashback['name'] . '购物返现');
                    //累计到参与人统计表
                    Db::name('cashback_member')->where('id', $cashback_member['id'])->dec('back_after', $back_money)->inc('commission', $back_money)->update();
                    if (getcustom('commission_notice_twice')) {
                        $tmplcontentNew = [];
                        $tmplcontentNew['character_string1'] = $og['ordernum'];
                        $tmplcontentNew['thing2'] = $og['name'];
                        $tmplcontentNew['amount3'] = $og['real_totalprice'] . '元';
                        $tmplcontentNew['time4'] = date('Y-m-d H:i:s', $og['createtime']);
                        $tmplcontentNew['amount5'] = $back_money . '元';
                        \app\common\Wechat::sendtmpl($aid, $mid, 'tmpl_commission_success', [], m_url('pages/my/usercenter', $aid), $tmplcontentNew);
                    }
                }
                if ($cashback['back_type'] == 3) {
                    \app\common\Member::addscore($aid, $mid, $back_money, $cashback['name'] . '购物返现');
                    //累计到参与人统计表
                    Db::name('cashback_member')->where('id', $cashback_member['id'])->dec('back_after', $back_money)->inc('score', $back_money)->update();
                }
                $other = ['cashback_member_id' => $cashback_member['id']];
                self::cashbackMemerDoLog($cashback_member['aid'], $mid, $cashback, $og, $back_money,'',$other);
            }

            if ($after_money <= $epsilon) {
                Db::name('cashback_member')->where('id', $cashback_member['id'])->update(['decay_back_status' => 2]);
            }
        }
    }

    //检查复购条件
    public static function check_decay_cashback($cashback,$cashback_member) {
        if(getcustom('yx_cashback_decay')) {
            $aid = $cashback_member['aid'];
            $mid = $cashback_member['mid'];
            //判断检测时间
            $checktime = $cashback_member['create_time'] + ($cashback['rebuy_days'] * 86400);
            if (time() < $checktime) return true;

            $starttime = strtotime(-$cashback['rebuy_days'] . ' days');
            $endtime   = time();

            $where   = [];
            $where[] = ['aid', '=', $cashback['aid']];
            $where[] = ['createtime', 'between time', [$starttime, $endtime]];

            $order_money  = Db::name('shop_order')->where($where)->where('mid', $mid)->where('status', 3)->sum('totalprice');
            $maidan_money = Db::name('maidan_order')->where($where)->where('mid', $mid)->where('status', 1)->sum('paymoney');
            $buy_money    = $order_money + $maidan_money;

            if ($buy_money >= $cashback['rebuy_min_money']) {
                return true;
            } else {
                //获取所有下级会员id
                $mids              = \app\common\Member::getdownmids($aid, $mid);
                $down_order_money  = Db::name('shop_order')->where($where)->where('mid', 'in', $mids)->where('status', 3)->sum('totalprice');
                $down_maidan_money = Db::name('maidan_order')->where($where)->where('mid', 'in', $mids)->where('status', 1)->sum('paymoney');
                $down_buy_money    = $down_order_money + $down_maidan_money;
                if ($down_buy_money >= $cashback['rebuy_min_money']) {
                    return true;
                }
            }
            return false;
        }
    }

    //购物返现买单递减
    public static function maidandealcashback($aid,$order,$member,$type){
        if(getcustom('yx_cashback_decay')){
            if($order && $member){
                $bid = $order['bid'];
                $where = [];
                $where[] = ['aid','=',$aid];
                $where[] = ['bid','=',$bid];
                $where[] = ['starttime','<',$order['paytime']];
                $where[] = ['endtime','>',$order['paytime']];
                if(getcustom('yx_cashback_sendtype')){
                    //付款后还是确认收货时发放
                    if($type=="collect"){
                        $where[] = ['sendtype','=',2];
                    }else if($type=="pay"){
                        $where[] = ['sendtype','=',1];
                    }
                }
                $cashbacklist = Db::name('cashback')->where($where)->order('sort desc')->select()->toArray();
                foreach($cashbacklist as $v){
                    $gettj = explode(',',$v['gettj']);
                    if(!in_array('-1',$gettj) && !in_array($member['levelid'],$gettj)){ //不是所有人
                        continue;
                    }
                    if($v['fwtype'] != 0)  continue;
                    if($v['return_type'] != 5)  continue;

                    //如果返现利率大于0
                    if($v['back_ratio']>0){
                        //计算返现
                        $paymoney = $order['paymoney'];
                        $cashback_mid = $order['mid'];

                        //参与记录
                        $map = [
                            'aid'         => $order['aid'],
                            'mid'         => $cashback_mid,
                            'cashback_id' => $v['id'],
                            'type'        => 'maidan'
                        ];
                        $cashback_member_check = Db::name('cashback_member')->where($map)->where('decay_back_status','in',[1,3])->find();
                        if(!$cashback_member_check){
                            $cashback_member = [];
                            $cashback_member['aid'] = $order['aid'];
                            $cashback_member['mid'] = $cashback_mid;
                            $cashback_member['cashback_id'] = $v['id'];
                            $cashback_member['pro_id'] = 0;
                            $cashback_member['pro_num'] = 1;
                            $cashback_member['cashback_money_max'] = $paymoney;
                            $cashback_member['back_type'] = $v['back_type'];
                            $cashback_member['type'] = 'maidan';
                            $cashback_member['create_time'] = time();
                            $cashback_member['ogid'] = 0;
                            $cashback_member['orderid'] = $order['id'];
                            $cashback_member['back_after'] = $paymoney;
                            $cashback_member['decay_back_status'] = 1;
                            $id = Db::name('cashback_member')->insertGetId($cashback_member);
                            $cashback_member_check = $cashback_member;
                            $cashback_member_check['id'] = $id;
                        }else{
                            $cashback_member = [];
                            $cashback_member['cashback_money_max']  = $cashback_member_check['cashback_money_max'] + $paymoney;
                            $cashback_member['back_after'] = $cashback_member_check['back_after'] + $paymoney;
                            Db::name('cashback_member')->where('id',$cashback_member_check['id'])->update($cashback_member);
                        }

                        \app\custom\OrderCustom::deal_decay_cashback($v,$cashback_member_check['id']);
                    }
                }
            }
        }
    }

}
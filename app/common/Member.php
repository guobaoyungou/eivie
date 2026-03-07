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

namespace app\common;
use think\facade\Db;
use think\facade\Log;


class Member
{
    //升级
    public static function uplv($aid,$mid,$type='shop',$params = []){
        if(getcustom('member_levelup_async')){
            //开启异步升级后，升级操作来源不是异步任务的先记录，然后等到异步任务处理
            $info = Db::name('sysset')->where('name','webinfo')->find();
            $webinfo = json_decode($info['value'],true);
            $from_async = $params['from_async']??0;
            if(!$from_async && $webinfo['member_levelup_type']==1){
                $log = [
                    'aid' => $aid,
                    'mid' => $mid,
                    'type' => $type,
                    'params' => json_encode($params),
                    'createtime' => time(),
                ];
                $last_id = Db::name('member_levelup_async_log')->insertGetId($log);
                //相同会员前面没执行的记录改掉状态，计划任务只让执行最新的记录就可以了
                Db::name('member_levelup_async_log')
                    ->where('aid',$aid)
                    ->where('mid',$mid)
                    ->where('status',0)
                    ->where('id','<',$last_id)
                    ->update(['status'=>2]);
                return true;
            }
        }
        $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->find();
        if(!$member['id']) return;
        if(getcustom('member_level_down_commission',$aid)){
            if($member['isauto_down']==1){
                return;
            }
        }
        if($member['path']) {
            //处理path异常问题
            $patharr = explode(',', $member['path']);
            $patharr = array_filter($patharr);
            $patharr = array_unique($patharr);
            $newpath = implode(',', $patharr);
            if($newpath != $member['path']) {
                Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['path'=>$newpath]);
            }
            $member['path'] = $newpath;
        }
        self::douplv($aid,$member,$type,$params);
        if(getcustom('fenhong_max_add')){
            //推荐用户增加上级分红额度
            self::addFenhongMax($aid,$mid);
            self::addFenhongMax($aid,$member['pid']);
        }
        if(getcustom('ciruikang_fenxiao',$aid)){
            if($params && $params['onebuy']){
                //如果是一次性购买，则上级没有此升级条件
                $params['onebuy_orderid'] = 0;
            }
        }
        //他的上级
        if($member['path']){
            $parentList = Db::name('member')->where('aid',$aid)->where('id','in',$member['path'])->order(Db::raw('field(id,'.$member['path'].')'))->select()->toArray();
            if($parentList){
                $parentList = array_reverse($parentList);
                foreach($parentList as $parent){
                    self::douplv($aid,$parent,$type,$params);
                    if(getcustom('fenhong_max_add')){
                        //推荐用户增加上级分红额度
                        self::addFenhongMax($aid,$parent['pid']);
                    }
                }
            }
        }
        if(getcustom('levelup_changepid_yeji',$aid)){
            //链动脱离的人触发原上级升级
            if($member['path_origin']){
                $member['path_origin'] = ltrim($member['path_origin'],',');
                $member['path_origin'] = rtrim($member['path_origin'],',');
                $parentList = Db::name('member')->where('aid',$aid)->where('id','in',$member['path_origin'])->order(Db::raw('field(id,'.$member['path_origin'].')'))->select()->toArray();
                if($parentList){
                    $parentList = array_reverse($parentList);
                    foreach($parentList as $parent){
                        self::douplv($aid,$parent,$type,$params);
                    }
                }
            }
        }
    }
    //处理升级逻辑
    public static function douplv($aid,$member,$type='shop',$params = []){
        $mid = $member['id'];
        //查询会有最后一次降级时间
        $downWhere = [];
        if(getcustom('refund_update_level')){
            $downWhere[] = ['title','<>','退款降级'];
        }
        $down_level_time = Db::name('member_levelup_order')->where('mid',$mid)->where($downWhere)->where('type',1)->order('createtime desc')->value('createtime');
        $down_level_time = $down_level_time?:0;
        $wxpaymoney = 0 + Db::name('wxpay_log')->where('aid',$aid)->where('mid',$mid)->where('createtime','>',$down_level_time)->sum('total_fee');
        if($type=='cashier'){
            $ordermoney = 0 + Db::name('cashier_order')->where('aid',$aid)->where('mid',$mid)->where('status',1)->where('paytime','>',$down_level_time)->sum('totalprice');
        }else{
            $ordermoney = 0 + Db::name('shop_order')->where('aid',$aid)->where('mid',$mid)->where('status','in','1,2,3')->where('paytime','>',$down_level_time)->sum('totalprice');
        }
        if(!empty($member['import_yeji'])){
            $ordermoney = bcadd($ordermoney,$member['import_yeji']);
        }
        $rechargemoney = 0 + Db::name('recharge_order')->where('aid',$aid)->where('mid',$mid)->where('status',1)->where('createtime','>',$down_level_time)->sum('money');
        if(getcustom('member_level_down_commission',$aid)){
            if($member['isauto_down']==1){
                return;
            }
        }
        self::upLevel($aid, $member, $member, $ordermoney, $wxpaymoney, $rechargemoney,0,$down_level_time,$params);

        //其他分组等级
        if(getcustom('plug_sanyang',$aid)) {
            $categoryList = Db::name('member_level_category')->where('aid',$aid)->where('isdefault', 0)->where('status', 1)->select()->toArray();
            if($categoryList) {
                foreach ($categoryList as $cat) {
                    $level_records = Db::name('member_level_record')->where('aid',$aid)->where('mid',$mid)->where('cid', $cat['id'])->find();
                    $level_records = $level_records ? $level_records : [];//无其他分组等级
                    self::upLevel($aid, $member, $level_records, $ordermoney, $wxpaymoney, $rechargemoney, $cat['id']);
                }
            }
        }
    }

    /**
     * @param $aid
     * @param $member
     * @param $levelInfo 等级信息 levelid,levelstarttime
     * @param $ordermoney
     * @param $wxpaymoney
     * @param $rechargemoney
     * @param $cid 其他分组等级为空时使用此字段
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function upLevel($aid, $member, $levelInfo, $ordermoney, $wxpaymoney, $rechargemoney, $cid = 0,$down_level_time=0,$params=[])
    {
        $sendSgReward = true;
        $mid = $member['id'];
        $nowlv = ['sort' => -1];
        if($levelInfo['levelid'])
            $nowlv = Db::name('member_level')->where('aid',$aid)->where('id',$levelInfo['levelid'])->find();
        $cid = $cid ? $cid : $nowlv['cid'];
        //等级列表
        $lvlist = Db::name('member_level')->where('aid',$aid)->where('cid', $cid)->where('can_up',1)->where('sort','>',$nowlv['sort'])->order('sort,id')->select();
        $shop_set = Db::name('shop_sysset')->where('aid',$aid)->find();
        $newlv = $nowlv;
        foreach($lvlist as $lv){
            if(getcustom('levelup_from_levelid',$aid)) {
                //升级前置等级条件
                $gettj = explode(',',$lv['gettj']);
                if ($lv['gettj'] && !in_array('-1', $gettj) && !in_array($nowlv['id'], $gettj)) {
                    continue;
                }
            }
            $condition_or = false;
            if($lv['maxnum'] > 0){
                $lvmembercount = Db::name('member')->where('aid',$aid)->where('levelid',$lv['id'])->count();
                if($lvmembercount >= $lv['maxnum']) continue;
            }
            if(getcustom('level_auto_up',$aid)){
                //自动降级的再自动升级
                if($lv['up_level_days']>0 && $lv['up_level_teamyeji']>0){
                    $able_starttime = time()-$lv['up_level_days']*86400;
                    $down_log = Db::name('member_levelup_order')
                        ->where('mid',$mid)->where('type',1)->where('beforelevelid',$lv['id'])
                        ->where('createtime','>',$able_starttime)->find();
                    if($down_log){
                        $downmids = self::getdownmids($aid,$mid);
                        if(!$downmids){
                            $downmids = [];
                        }
                        array_push($downmids,$member['id']);
                        $s_time = $down_log['createtime'];
                        $map = [];
                        $map[] = ['aid','=',$aid];
                        $map[] = ['mid','in',$downmids];
                        $map[] = ['createtime','>',$s_time];
                        $map[] = ['status','in',[1,2,3]];
                        $order_yeji = Db::name('shop_order_goods')->where($map)->sum('real_totalprice');
                        if($order_yeji>=$lv['up_level_teamyeji']){
                            $isup = 1;
                            $newlv = $lv;
                            continue;
                        }
                    }
                }
            }
            $tjor = false; //or条件 有一个满足就变成true 有and条件不满足直接continue跳过
            $hasor = false; //是否有or条件
            $hasand = false; //是否有and条件

            if($lv['up_wxpaymoney'] > 0){
                $hasor = true;
                if($wxpaymoney >= $lv['up_wxpaymoney']) $tjor = true;
            }
            if(!$tjor && $lv['up_ordermoney'] > 0){
                $hasor = true;
                if($ordermoney >= $lv['up_ordermoney']) $tjor = true;
            }
            if(!$tjor && $lv['up_rechargemoney'] > 0){
                $hasor = true;
                if($rechargemoney >= $lv['up_rechargemoney']) $tjor = true;
            }
            if(!$tjor && $lv['up_getmembercard']==1){
                $hasor = true;
                if($member['card_code']) $tjor = true;
            }

            if(!$tjor && getcustom('levelup_perpaymoney',$aid) && $lv['up_perpaymoney']>0){
                $hasor = true;
                $max_wxpaymoney = 0 + Db::name('wxpay_log')->where('aid',$aid)->where('mid',$mid)->where('createtime','>',$down_level_time)->max('total_fee');
                if($max_wxpaymoney >= $lv['up_perpaymoney']){
                    $tjor = true;
                }else{
                    $max_fxordermoney = Db::name('shop_order')->where('aid',$aid)->where('status','in','1,2,3')->where('mid',$mid)->where('createtime','>',$down_level_time)->max('totalprice');
                    if($max_fxordermoney >= $lv['up_perpaymoney']){
                        $tjor = true;
                    }else{
                        $max_fxordermoney = Db::name('maidan_order')->where('aid',$aid)->where('status','=',1)->where('mid',$mid)->where('createtime','>',$down_level_time)->max('money');
                        if($max_fxordermoney >= $lv['up_perpaymoney']){
                            $tjor = true;
                        }
                    }
                }
            }

            //hasor true tjor false 商城订单单笔金额满
            if (getcustom('member_levelup_orderprice',$aid) && ((!$tjor && $lv['up_orderprice_condition'] == 'or') || $lv['up_orderprice_condition'] == 'and') && $lv['up_orderprice'] > 0) {
                if($lv['up_orderprice_condition'] == "or")$hasor = true;
                $ismeet = false;
                $max_orderprice = Db::name('shop_order')->where('aid', $aid)->where('status', 'in', '1,2,3')->where('mid', $mid)->where('createtime', '>', $down_level_time)->max('totalprice');
                if ($max_orderprice >= $lv['up_orderprice']) {
                    $ismeet = true;
                }
                if($lv['up_orderprice_condition'] == 'or'){
                    if($ismeet) $tjor = true;
                }else{
                    if(!$ismeet) continue;
                    $hasand = true;
                }
            }

            //下级总订单金额满
            if(((!$tjor && $lv['up_fxorder_condition'] == 'or') || $lv['up_fxorder_condition'] == 'and') && $lv['up_fxordermoney'] > 0){
                if($lv['up_fxorder_condition'] == 'or') $hasor = true;
                $ismeet = false;
                if($lv['up_fxordermoney_removemax'] == 1){//剔除伞下最高业绩的下级
                    $downmids = self::getdownmids_removemax($aid,$mid,$lv['up_fxorderlevelnum'],$lv['up_fxorderlevelid']);
                }else{
                    $downmids = self::getdownmids($aid,$mid,$lv['up_fxorderlevelnum'],$lv['up_fxorderlevelid'],0,1,$down_level_time);
                }
                if(getcustom('levelup_fxordermoney_self',$aid) && $lv['up_fxordermoney_self'] == 1){
                    //下级总订单金额含自己，1开启
                    $downmids[] = $mid;
                }
                if($downmids){
                    $fxordermoney = 0 + Db::name('shop_order_goods')->where('status','in','1,2,3')->where('mid','in',$downmids)->where('createtime','>',$down_level_time)->sum('totalprice');
                    if(getcustom('refund_update_level')){
                        if(isset($params['refund_mode']) && $params['refund_mode']){
                            //退款降级 扣除退款
                            $refundMoney = Db::name('shop_order')->where('status','in','1,2,3')->where('mid','in',$downmids)->where('createtime','>',$down_level_time)->sum('refund_money');
                            $fxordermoney -= $refundMoney;
                        }
                    }
                    if(getcustom('member_import_dyzx',$aid) && !empty($member['import_yeji'])){
                        //东营中讯定制导入业绩算升级业绩
                        $fxordermoney = bcadd($fxordermoney,$member['import_yeji']);
                    }
                    // 餐饮订单计入团队业绩，参与升级条件统计
                    if(getcustom('restaurant_team_yeji',$aid)){
                        $restaurant_team_yeji_open = Db::name('admin_set')->where('aid',$aid)->value('restaurant_team_yeji_open');
                        if($restaurant_team_yeji_open){
                           // 外卖
                            $rtakeaway_fxordermoney = Db::name('restaurant_takeaway_order_goods')->where('status','in','1,2,3,12')->where('mid','in',$downmids)->where('createtime','>',$down_level_time)->sum('totalprice');
                            $fxordermoney += $rtakeaway_fxordermoney;
                            // 店内点餐
                            $rshop_fxordermoney = Db::name('restaurant_shop_order_goods')->where('status','in','1,2,3')->where('mid','in',$downmids)->where('createtime','>',$down_level_time)->sum('totalprice');
                            $fxordermoney += $rshop_fxordermoney; 
                        }
                    }
                    if($fxordermoney >= $lv['up_fxordermoney']){
                        $ismeet = true;
                    }
                }
                if($lv['up_fxorder_condition'] == 'or'){
                    if($ismeet) $tjor = true;
                }else{
                    if(!$ismeet) continue;
                    $hasand = true;
                }
            }

            if(!$tjor && $lv['up_fxordermoney_xiao'] > 0){
                $hasor = true;
                $downmids = self::getdownmids_xiao($aid,$mid,$lv['up_fxorderlevelnum_xiao'],$lv['up_fxorderlevelid_xiao']);
                if($downmids){
                    $fxordermoney = 0 + Db::name('shop_order_goods')->where('status','in','1,2,3')->where('mid','in',$downmids)->where('createtime','>',$down_level_time)->sum('totalprice');
                    if($fxordermoney >= $lv['up_fxordermoney_xiao']){
                       $tjor = true;
                    }
                }
            }
            if(!$tjor && ($lv['up_fxdowncount']>0 || $lv['up_fxdowncount2']>0 || $lv['up_fxdowncount3']>0)){
                $hasor = true;
                $downmidcount1 = 0;
                $downmidcount2 = 0;
                $downmidcount3 = 0;
                $up_fxdowncount = intval($lv['up_fxdowncount']);
                $up_fxdowncount2 = intval($lv['up_fxdowncount2']);
                $up_fxdowncount3 = intval($lv['up_fxdowncount3']);
                if($lv['up_fxdowncount'] > 0){
                    $downmids = self::getdownmids($aid,$mid,$lv['up_fxdownlevelnum'],$lv['up_fxdownlevelid'],$lv['up_with_origin'],$lv['up_with_new'],$down_level_time);
                    $downmidcount1 = count($downmids);
                }
                if($lv['up_fxdowncount2'] > 0){
                    $downmids2 = self::getdownmids($aid,$mid,$lv['up_fxdownlevelnum2'],$lv['up_fxdownlevelid2'],$lv['up_with_origin'],$lv['up_with_new'],$down_level_time);
                    $downmidcount2 = count($downmids2);
                }
                if($lv['up_fxdowncount3'] > 0){
                    $downmids3 = self::getdownmids($aid,$mid,$lv['up_fxdownlevelnum3'],$lv['up_fxdownlevelid3'],$lv['up_with_origin'],$lv['up_with_new'],$down_level_time);
                    $downmidcount3 = count($downmids3);
                }
                if($downmidcount1 >= $up_fxdowncount && $downmidcount2 >= $up_fxdowncount2 && $downmidcount3 >= $up_fxdowncount3){
                    $tjor = true;
                }
                
            }

            if(getcustom('up_downbuyprocount',$aid) && !$tjor && $lv['up_downbuypronum']>0){
                $hasor = true;
                $downmids = self::getdownmids($aid,$mid,$lv['up_downbuyprolvnum']);
                $downbuypronum = 0;
                if($downmids){
                    if($lv['up_downbuyproid']){
                        $downbuypronum = Db::name('shop_order_goods')->where('aid',$aid)->where('status','in','1,2,3')->where('mid','in',$downmids)->where('proid','in',$lv['up_downbuyproid'])->where('createtime','>',$down_level_time)->sum('num');
                    }else{
                        $downbuypronum = Db::name('shop_order_goods')->where('aid',$aid)->where('status','in','1,2,3')->where('mid','in',$downmids)->where('createtime','>',$down_level_time)->sum('num');
                    }
                    if($downbuypronum >= $lv['up_downbuypronum']){
                        $tjor = true;
                    }
                }
            }
            if(getcustom('up_cat_ordermoney',$aid)){
                if(!$tjor && ($lv['up_cat_ordermoney']>0 && $lv['up_catid']!='')){
                    $hasor = true;
                    //购买分类商品,单笔订单金额满
                    //查询最后一笔订单
                    $up_catids = explode(',',str_replace('，',',',$lv['up_catid']));
                    $last_order = Db::name('shop_order')->field('id,aid,bid,mid,ordernum,totalprice,status')->where('aid',$aid)->where('mid',$mid)->where('status','in','1,2,3')->order('id desc')->find();
                    if($last_order['totalprice'] >= $lv['up_cat_ordermoney']){
                        $oglist = Db::name('shop_order_goods')->field('id,aid,bid,mid,orderid,proid,cid,real_totalprice,status')->where('aid',$aid)->where('mid',$mid)->where('status','in','1,2,3')->where('orderid',$last_order['id'])->where('createtime','>',$down_level_time)->select()->toArray();
                        if($oglist){
                            $allcids=[];
                            $up_cat_total=0;
                            $cids = Db::name('shop_category')->where('id','in',$lv['up_catid'])->where('aid',$aid)->column('id');
                            if($cids){
                                $cids2 = Db::name('shop_category')->where('aid',$aid)->where('pid','in',$cids)->column('id');
                                if($cids2){
                                    $cids3 = Db::name('shop_category')->where('aid',$aid)->where('pid','in',$cids2)->column('id');
                                    if($cids3)
                                        $allcids = array_merge($cids,$cids2,$cids3);
                                    else
                                        $allcids = array_merge($cids,$cids2);
                                }else{
                                    $allcids = $cids;
                                }
                                foreach ($oglist as $og){
                                    $ogcid = explode(',',$og['cid']);
                                    if(empty($ogcid)) continue;
                                    if(array_intersect($allcids,$ogcid)){
                                        $up_cat_total += $og['real_totalprice'];
                                    }
                                }
                                if($up_cat_total >= $lv['up_cat_ordermoney']){
                                    $tjor = true;
                                }
                            }
                        }
                    }
                }
            }

            if(getcustom('ciruikang_fenxiao',$aid)){
                //注册满多少天内
                if(!$tjor && $lv['up_regtime_and']>0){
                    $up_regtime_and = $lv['up_regtime_and']*86400;
                    //查询此会员注册时间
                    $regtime = time()-$member['createtime'];
                    //如果超出，则不能升级此等级
                    if($regtime>$up_regtime_and){
                        continue;
                    }
                    $hasand = true;
                }
                //一次性购买升级(从未一次性升级过)
                if(!$member['crk_up_levelid'] && !empty($lv['up_proid2']) && !empty($lv['up_pronum2']) && $lv['up_pronum2']>0){
                    if($params && $params['onebuy'] && $params['onebuy_orderid']){
                        $hasor = true;
                        $deal_onebuy = \app\custom\CiruikangCustom::deal_onebuy($aid,$mid,$member,$lv,$params,$down_level_time,$tjor);
                        $lv   = $deal_onebuy['lv'];
                        $tjor = $deal_onebuy['tjor'];
                        if($deal_onebuy && $tjor && $lv['is_onebuy']){
                            $sendSgReward = false;
                        }
                    }
                }
            }

            //购买指定商品
            if(((!$tjor && $lv['up_buygoods_condition'] == 'or') || $lv['up_buygoods_condition'] == 'and') && ($lv['up_proid']!='0' && $lv['up_proid']!='')){
                if($lv['up_buygoods_condition'] == 'or') $hasor = true;
                $ismeet = false;
                $up_proids = explode(',',str_replace('，',',',$lv['up_proid']));
                $up_pronums = explode(',',str_replace('，',',',$lv['up_pronum']));

                $where = [];
                $up_pro_orderrange = 0;//统计订单范围 0:仅自己订单 1:自己及下级订单
                if(getcustom('ciruikang_fenxiao',$aid)){
                    $up_pro_orderrange = $lv['up_pro_orderrange'];
                }
                if(!$up_pro_orderrange){
                    $where[] = ['mid','=',$mid];
                }else{
                    $mids =[$mid];
                    $mids2 = self::getdownmids($aid,$mid);
                    if($mids2){
                        $mids = array_merge($mids,$mids2);
                    }
                    $where[] = ['mid','in',$mids];
                }

                $up_pro_orderstatus = 0;//统计订单状态 0:付款后所有订单 1:仅确认收货订单
                if(getcustom('ciruikang_fenxiao',$aid)){
                    $up_pro_orderstatus = $lv['up_pro_orderstatus'];
                }
                if(!$up_pro_orderstatus){
                    $where[] = ['status','in','1,2,3'];
                }else{
                    $where[] = ['status','=',3];
                }

                $where[] = ['aid','=',$aid];
                $where[] = ['createtime','>',$down_level_time];

                if(count($up_pronums) > 1) {
                    foreach($up_proids as $k=>$up_proid){
                        $pronum = $up_pronums[$k];
                        if(!$pronum) $pronum = 1;
                        if(getcustom('refund_update_level') && isset($params['refund_mode']) && $params['refund_mode']){
                            $orderGoods = Db::name('shop_order_goods')->field('sum(num - refund_num) as num')->where('proid',$up_proid)->where($where)->find();
                            $buynum = $orderGoods['num'];
                        }else{
                            $buynum = Db::name('shop_order_goods')->where('proid',$up_proid)->where($where)->sum('num');
                        }
                        if($buynum >= $pronum){
                            $ismeet = true;
                        }
                    }
                } else {
                    $pronum = $up_pronums[0];
                    if(!$pronum) $pronum = 1;
                    $buynum = 0;
                    foreach($up_proids as $k=>$up_proid){
                        if(getcustom('refund_update_level') && isset($params['refund_mode']) && $params['refund_mode']){
                            $orderGoods = Db::name('shop_order_goods')->field('sum(num - refund_num) as num')->where('proid',$up_proid)->where($where)->find();
                            $buynum += $orderGoods['num'];
                        }else{
                            $buynum += Db::name('shop_order_goods')->where('proid',$up_proid)->where($where)->sum('num');
                        }
                        if($buynum >= $pronum){
                            $ismeet = true;
                        }
                    }
                }

                if(getcustom('ciruikang_fenxiao',$aid)){
                    //是否有当前最低等级限制
                    if(!empty($lv['up_pro_minprelevelid'])){
                        //查询等级序号
                        $presort = Db::name('member_level')->where('id',$lv['up_pro_minprelevelid'])->value('sort');
                        $presort = $presort??0;
                        if($presort){
                            if($nowlv['sort']<$presort){
                                $ismeet = false;
                            }
                        }
                    }
                }

                if($lv['up_buygoods_condition'] == 'or'){
                    if($ismeet) $tjor = true;
                }else{
                    if(!$ismeet) continue;
                    $hasand = true;
                }
                
            }

            if(getcustom('levelup_small_market_yeji',$aid)){
                //是否符合小市场业绩限制 --统计的订单金额
                if(((!$tjor && $lv['up_small_market_yeji_condition'] == 'or') || $lv['up_small_market_yeji_condition'] == 'and') && $lv['up_small_market_yeji']>0){
                    if($lv['up_small_market_yeji_condition'] == 'or') $hasor = true;

                    $ismeet = false;//小市场条件是否符合
                    //获取直推人数
                    $ztmembers = Db::name('member')->where('pid',$mid)->where('aid',$aid)->field('id')->select()->toArray();
                    $num = count($ztmembers);
                    //去掉其中一条直推线上最大业绩，必须有两条直推线
                    if($num>1){
                        $maxteam = [];//最大业绩团队
                        $small_market_num = 0;//小市场业绩
                        //统计团队已确认收货的商品数量
                        foreach($ztmembers as &$zv){
                            $mids =[$zv['id']];
                            $mids2 = \app\common\Member::getdownmids($aid,$zv['id']);
                            if($mids2){
                                $mids = array_merge($mids,$mids2);
                            }
                            //是否有商品条件
                            $prowhere = [];
                            if(!empty($lv['up_small_market_yeji_proids'])){
                                $proarr = explode(',',$lv['up_small_market_yeji_proids']);

                                if($proarr){
                                    $prowhere[] = ['proid','in',$proarr];
                                }
                            }
                            //统计团队业绩--按订单金额统计
                            $real_totalprice = Db::name('shop_order_goods')->where('mid','in',$mids)->where('status','in',[1,2,3])->where($prowhere)->where('aid',$aid)->sum('real_totalprice');
                            $zv['real_totalprice'] = $real_totalprice ?? 0;
                            if($maxteam){
                                if($zv['real_totalprice'] > $maxteam['real_totalprice']){
                                    $maxteam = $zv;
                                }
                            }else{
                                $maxteam = $zv;
                            }
                            $small_market_num += $zv['real_totalprice'];
                        }
                        unset($zv);

                        $small_market_num -= $maxteam['real_totalprice'];
                        if($small_market_num>=$lv['up_small_market_yeji']){
                            $ismeet = true;
                        }
                    }

                    if($lv['up_small_market_yeji_condition'] == 'or'){
                        if($ismeet) $tjor = true;
                    }else{
                        if(!$ismeet) continue;
                        $hasand = true;
                    }
                }
            }

            if(getcustom('levelup_small_market_num_product',$aid)){
                //是否符合小市场业绩限制--按订单商品数量统计
                if(((!$tjor && $lv['up_small_market_num_condition'] == 'or') || $lv['up_small_market_num_condition'] == 'and') && $lv['up_small_market_num']>0){
                    if($lv['up_small_market_num_condition'] == 'or') $hasor = true;

                    $ismeet = false;//小市场条件是否符合
                    //获取直推人数
                    $ztmembers = Db::name('member')->where('pid',$mid)->where('aid',$aid)->field('id')->select()->toArray();
                    $num = count($ztmembers);
                    //去掉其中一条直推线上最大业绩，必须有两条直推线
                    if($num>1){
                        $maxteam = [];//最大业绩团队
                        $small_market_num = 0;//小市场业绩
                        //统计团队已确认收货的商品数量
                        foreach($ztmembers as &$zv){
                            $mids =[$zv['id']];
                            $mids2 = \app\common\Member::getdownmids($aid,$zv['id']);
                            if($mids2){
                                $mids = array_merge($mids,$mids2);
                            }
                            //是否有商品条件
                            $prowhere = [];
                            if(!empty($lv['up_small_market_num_proids'])){
                                $proarr = explode(',',$lv['up_small_market_num_proids']);

                                if($proarr){
                                    $prowhere[] = ['proid','in',$proarr];
                                }
                            }
                            //统计团队业绩--按订单商品数量统计
                            $goods = Db::name('shop_order_goods')->where('mid','in',$mids)->where('status','in',[1,2,3])->where($prowhere)->where('aid',$aid)->fieldRaw('sum(num) num,sum(refund_num) refund_num')->find();
                            if($goods){
                                $zv['gnum'] = $goods['num']-$goods['refund_num'];
                            }

                            if($maxteam){
                                if($zv['gnum'] > $maxteam['gnum']){
                                    $maxteam = $zv;
                                }
                            }else{
                                $maxteam = $zv;
                            }
                            $small_market_num += $zv['gnum'];
                        }
                        unset($zv);

                        $small_market_num -= $maxteam['gnum'];
                        if($small_market_num>=$lv['up_small_market_num']){
                            $ismeet = true;
                        }
                    }

                    if($lv['up_small_market_num_condition'] == 'or'){
                        if($ismeet) $tjor = true;
                    }else{
                        if(!$ismeet) continue;
                        $hasand = true;
                    }
                }
            }

            if(getcustom('levelup_selfanddown_order_num',$aid)){
                //增加自己和下级下单总数量满xx单--统计订单数量
                if(((!$tjor && $lv['up_selfanddown_order_num_condition'] == 'or') || $lv['up_selfanddown_order_num_condition'] == 'and') && $lv['up_selfanddown_order_num']>0){
                    if($lv['up_selfanddown_order_num_condition'] == 'or') $hasor = true;
                    $ismeet = false;//下单数量是否符合
                    //下级会员
                    $mids3 = \app\common\Member::getdownmids($aid,$mid);
                    //加入自己
                    $mids3[] = $mid;
                    //是否有商品条件
                    $prowhere = [];
                    if(!empty($lv['up_selfanddown_order_num_proids'])){
                        $proarr = explode(',',$lv['up_selfanddown_order_num_proids']);

                        if($proarr){
                            $prowhere[] = ['proid','in',$proarr];
                        }
                    }
                    //按订单数量
                    $order_num = Db::name('shop_order_goods')->where('aid',$aid)->where('mid','in',$mids3)->where($prowhere)->where('status','in',[1,2,3])->group('orderid')->count();
                    if($order_num >= $lv['up_selfanddown_order_num']){
                        $ismeet = true;
                    }
                    if($lv['up_selfanddown_order_num_condition'] == 'or'){
                        if($ismeet) $tjor = true;
                    }else{
                        if(!$ismeet) continue;
                        $hasand = true;
                    }
                }
            }

            $yeji_self_manually_product = getcustom('yeji_self_manually_product',$aid);
            if(getcustom('levelup_selfanddown_order_product_num',$aid)){
                //增加自己和下级下单商品总数量满xx件--统计订单商品总数量
                if(((!$tjor && $lv['up_selfanddown_order_product_num_condition'] == 'or') || $lv['up_selfanddown_order_product_num_condition'] == 'and') && $lv['up_selfanddown_order_product_num']>0){
                    if($lv['up_selfanddown_order_product_num_condition'] == 'or') $hasor = true;
                    $ismeet = false;//下单数量是否符合
                    //下级会员
                    $mids3 = \app\common\Member::getdownmids($aid,$mid);
                    //加入自己
                    $mids3[] = $mid;
                    //是否有商品条件
                    $prowhere = [];
                    if(!empty($lv['up_selfanddown_order_product_num_proids'])){
                        $proarr = explode(',',$lv['up_selfanddown_order_product_num_proids']);

                        if($proarr){
                            $prowhere[] = ['proid','in',$proarr];
                        }
                    }
                    //按订单商品数量
                    $goods = Db::name('shop_order_goods')->where('mid','in',$mids3)->where('status','in',[1,2,3])->where($prowhere)->where('aid',$aid)->fieldRaw('sum(num) num,sum(refund_num) refund_num')->find();
                    $pro_num = 0;
                    if($goods){
                        $pro_num = $goods['num']-$goods['refund_num'];
                    }
                    if($yeji_self_manually_product){
                        //手动增加的个人业绩
                        $yeji_self = Db::name('member')->where('aid',$aid)->where('id',$mid)->value('yeji_self_manually_product');
                        $pro_num = $pro_num + $yeji_self;
                    }
                    //var_dump($pro_num);exit;
                    if($pro_num >= $lv['up_selfanddown_order_product_num']){
                        $ismeet = true;
                    }
                    if($lv['up_selfanddown_order_product_num_condition'] == 'or'){
                        if($ismeet) $tjor = true;
                    }else{
                        if(!$ismeet) continue;
                        $hasand = true;
                    }
                }
            }

            if(getcustom('member_levelup_businessnum',$aid)){
                if( 
                    (
                        (!$tjor && (!$lv['up_businessnum_condition'] || $lv['up_businessnum_condition'] == 'or'))
                        || $lv['up_businessnum_condition'] == 'and'
                    )
                    && $lv['up_businessnum']>0
                ){
                    if(!$lv['up_businessnum_condition'] || $lv['up_businessnum_condition'] == 'or') $hasor = true;
                    $ismeet = false;
                    //查询他推荐的商户数量
                    $businessnum = 0+Db::name('business')
                        ->alias('b')
                        ->join('member m','m.id = b.mid')
                        ->where('b.aid',$aid)
                        ->where('b.status',1)
                        ->where('m.pid',$mid)
                        ->count();
                    if($businessnum >= $lv['up_businessnum']){
                        $ismeet = true;
                    }
                    if(!$lv['up_businessnum_condition'] || $lv['up_businessnum_condition'] == 'or'){
                        if($ismeet) $tjor = true;
                    }else{
                        if(!$ismeet) continue;
                        $hasand = true;
                    }
                }
            }

            if(((!$tjor && getcustom('up_fxdowncount_and_isor',$aid)) || !getcustom('up_fxdowncount_and_isor',$aid)) && ($lv['up_fxdowncount_and']>0 || $lv['up_fxdowncount2_and']>0)){
                if(getcustom('up_fxdowncount_and_isor',$aid)) $hasor = true;
                $ismeet = false;
                $downmidcount1 = 0;
                $downmidcount2 = 0;
                $up_fxdowncount = intval($lv['up_fxdowncount_and']);
                $up_fxdowncount2 = intval($lv['up_fxdowncount2_and']);
                if($lv['up_fxdowncount_and'] > 0){
                    $downmids = self::getdownmids($aid,$mid,$lv['up_fxdownlevelnum_and'],$lv['up_fxdownlevelid_and'],$lv['up_with_origin'],$lv['up_with_new'],$down_level_time);
                    $downmidcount1 = count($downmids);
                }
                if($lv['up_fxdowncount2_and'] > 0){
                    $downmids2 = self::getdownmids($aid,$mid,$lv['up_fxdownlevelnum2_and'],$lv['up_fxdownlevelid2_and'],$lv['up_with_origin'],$lv['up_with_new'],$down_level_time);
                    $downmidcount2 = count($downmids2);
                }
                if($downmidcount1 >= $up_fxdowncount && $downmidcount2 >= $up_fxdowncount2){
                    $ismeet = true;
                }
                if(getcustom('up_fxdowncount_and_isor',$aid)){
                    if($ismeet) $tjor = true;
                }else{
                    if(!$ismeet) continue;
                    $hasand = true;
                }
            }

            $levelup_shop_childre_number = getcustom('levelup_shop_childre_number',$aid);
            if($levelup_shop_childre_number){
                if(((!$tjor && $lv['up_shop_childre_number_condition'] == 'or') || $lv['up_shop_childre_number_condition'] == 'and')){
                    if($lv['up_shop_childre_number_condition'] == 'or') $hasor = true;

                    $ismeet = false;

                    //商城订单金额累计满
                    $scn_tj1 = 1;
                    if($lv['up_shopordermoney'] > 0){
                        $scn_tj1 = 0;
                        if($ordermoney >= $lv['up_shopordermoney']){
                            $scn_tj1 = 1;
                        }
                    }
//                    var_dump('a');
//                    var_dump($ordermoney);
                    //下级订单总金额满
                    $scn_tj2 = 1;
                    if($lv['up_childre_shopordermoney'] > 0){
                        $scn_tj2 = 0;
                        if($lv['up_fxordermoney_removemax'] == 1){//剔除伞下最高业绩的下级
                            $downmids = self::getdownmids_removemax($aid,$mid,$lv['up_childre_shopordermoney_num'],$lv['up_childre_shopordermoney_levelid']);
                        }else{
                            $downmids = self::getdownmids($aid,$mid,$lv['up_childre_shopordermoney_num'],$lv['up_childre_shopordermoney_levelid'],0,1,$down_level_time);
                        }
                        if(getcustom('levelup_fxordermoney_self',$aid) && $lv['up_fxordermoney_self'] == 1){
                            //下级总订单金额含自己，1开启
                            $downmids[] = $mid;
                        }

                        if($downmids){
                            $fxordermoney = 0 + Db::name('shop_order_goods')->where('status','in','1,2,3')->where('mid','in',$downmids)->where('createtime','>',$down_level_time)->sum('totalprice');var_dump($fxordermoney);
                            if(getcustom('refund_update_level')){
                                if(isset($params['refund_mode']) && $params['refund_mode']){
                                    //退款降级 扣除退款
                                    $refundMoney = Db::name('shop_order')->where('status','in','1,2,3')->where('mid','in',$downmids)->where('createtime','>',$down_level_time)->sum('refund_money');
                                    $fxordermoney -= $refundMoney;
                                }
                            }
                            if(getcustom('member_import_dyzx',$aid) && !empty($member['import_yeji'])){
                                //东营中讯定制导入业绩算升级业绩
                                $fxordermoney = bcadd($fxordermoney,$member['import_yeji']);
                            }
                            // 餐饮订单计入团队业绩，参与升级条件统计
                            if(getcustom('restaurant_team_yeji',$aid)){
                                $restaurant_team_yeji_open = Db::name('admin_set')->where('aid',$aid)->value('restaurant_team_yeji_open');
                                if($restaurant_team_yeji_open){
                                    // 外卖
                                    $rtakeaway_fxordermoney = Db::name('restaurant_takeaway_order_goods')->where('status','in','1,2,3,12')->where('mid','in',$downmids)->where('createtime','>',$down_level_time)->sum('totalprice');
                                    $fxordermoney += $rtakeaway_fxordermoney;
                                    // 店内点餐
                                    $rshop_fxordermoney = Db::name('restaurant_shop_order_goods')->where('status','in','1,2,3')->where('mid','in',$downmids)->where('createtime','>',$down_level_time)->sum('totalprice');
                                    $fxordermoney += $rshop_fxordermoney;
                                }
                            }
//                            var_dump('b');
//                            var_dump($fxordermoney);
                            if($fxordermoney >= $lv['up_childre_shopordermoney']){
                                $scn_tj2 = 1;
                            }
                        }
                    }

                    //下级总人数满
                    $scn_tj3 = 1;
                    if($lv['up_childre_number'] > 0){
                        $scn_tj3 = 0;
                        $up_childre_number = intval($lv['up_childre_number']);
                        $downmids_childre_number_arr = self::getdownmids($aid,$mid,$lv['up_childre_number_num'],$lv['up_childre_number_levelid'],$lv['up_with_origin'],$lv['up_with_new'],$down_level_time);
                        $downmidchildre_number = count($downmids_childre_number_arr);
//                        var_dump('c');
//                        var_dump($downmidchildre_number);
                        if( $downmidchildre_number >= $up_childre_number){
                            $scn_tj3 = 1;
                        }
                    }

                    if($scn_tj1 == 1 && $scn_tj2 == 1 && $scn_tj3 == 1){
                        $ismeet = true;
                    }

                    if($lv['up_shop_childre_number_condition'] == 'or'){
                        if($ismeet) $tjor = true;
                    }else{
                        if(!$ismeet) continue;
                        $hasand = true;
                    }
                }
            }

            if(getcustom('levelup_wx_channels',$aid)){
                //购买小店指定商品(商品ID)
                if(((!$tjor && $lv['up_wxchannels_buygoods_condition'] == 'or') || $lv['up_wxchannels_buygoods_condition'] == 'and') && ($lv['up_wxchannels_proid']!='0' && $lv['up_wxchannels_proid']!='')){
                    if($lv['up_wxchannels_buygoods_condition'] == 'or') $hasor = true;
                    $ismeet = false;
                    $up_wxchannels_proids = explode(',',str_replace('，',',',$lv['up_wxchannels_proid']));
                    $up_wxchannels_pronums = explode(',',str_replace('，',',',$lv['up_wxchannels_pronum']));

                    $where = [];
                    $up_pro_orderrange = 0;//统计订单范围 0:仅自己订单 1:自己及下级订单
                    if(!$up_pro_orderrange){
                        $where[] = ['co.mid','=',$mid];
                    }else{
                        $mids =[$mid];
                        $mids2 = self::getdownmids($aid,$mid);
                        if($mids2){
                            $mids = array_merge($mids,$mids2);
                        }
                        $where[] = ['co.mid','in',$mids];
                    }

                    $up_pro_orderstatus = 0;//统计订单状态 0:付款后所有订单 1:仅确认收货订单
                    if(!$up_pro_orderstatus){
                        $where[] = ['co.status','>=',20];
                        $where[] = ['co.status','<=',100];
                    }else{
                        $where[] = ['co.status','=',100];
                    }

                    $where[] = ['co.aid','=',$aid];
                    $where[] = ['co.create_time','>',$down_level_time];

                    if(count($up_wxchannels_pronums) > 1) {
                        foreach($up_wxchannels_proids as $k=>$up_wxchannels_proid){
                            $pronum = $up_wxchannels_pronums[$k];
                            if(!$pronum) $pronum = 1;
                            $buynum = Db::name('channels_order_goods')->alias('og')
                                ->join('channels_order co','co.order_id = og.order_id')
                                ->join('channels_product cp','cp.product_id = og.product_id')
                                ->where('cp.id',$up_wxchannels_proid)->where($where)->sum('og.sku_cnt');
                            if($buynum >= $pronum){
                                $ismeet = true;
                            }
                        }
                    } else {
                        $pronum = $up_wxchannels_pronums[0];
                        if(!$pronum) $pronum = 1;
                        $buynum = 0;
                        foreach($up_wxchannels_proids as $k=>$up_wxchannels_proid){
                            $buynum += Db::name('channels_order_goods')->alias('og')
                                ->join('channels_order co','co.order_id = og.order_id')
                                ->join('channels_product cp','cp.product_id = og.product_id')
                                ->where('cp.id',$up_wxchannels_proid)->where($where)->sum('og.sku_cnt');
                            if($buynum >= $pronum){
                                $ismeet = true;
                            }
                        }
                    }
                    if($lv['up_wxchannels_buygoods_condition'] == 'or'){
                        if($ismeet) $tjor = true;
                    }else{
                        if(!$ismeet) continue;
                        $hasand = true;
                    }
                    
                }
            }

            if(getcustom('levelup_biglittlearea_yeji',$aid)){
                //大小区业绩统计
                if(((!$tjor && $lv['up_biglittlearea_yeji_condition'] == 'or') || $lv['up_biglittlearea_yeji_condition'] == 'and') && ($lv['up_bigarea_yeji']>0 || $lv['up_littlearea_yeji']>0)){
                    if($lv['up_biglittlearea_yeji_condition'] == 'or') $hasor = true;
                    $ismeet = false;


                    //大区是否包含自己的已确认收货的业绩
                    $selfareayeji = 0;
                    if($lv['up_bigarea_yeji']>0 && $lv['up_bigarea_yeji_self'] == 1){
                        //团队业绩 0:统计下单时参与商品 1：统计现在订单参与商品
                        if($lv['up_biglittlearea_yeji_join'] == 1){
                            $selfareayeji = Db::name('shop_order_goods')->alias('og')
                                ->join('shop_product p','p.id = og.proid')
                                ->where('og.mid',$mid)->where('p.biglittlearea_yeji',1)->where('og.status',3)->sum('og.real_totalprice');
                        }else{
                            $selfareayeji = Db::name('shop_order_goods')->where('mid',$mid)->where('biglittlearea_yeji',1)->where('status',3)->sum('real_totalprice');
                        }
                    }

                    //分别统计各下级及伞下已确认收货的业绩
                    $allareayeji = 0;//全部业绩
                    $bigareayeji = 0;//最大区业绩
                    //查询自己的直推下级
                    $childmids = Db::name('member')->where('pid',$mid)->column('id');
                    if($childmids){
                        foreach($childmids as $cmid){
                            //查询下级的伞下
                            $downmids = self::getdownmids($aid,$cmid);
                            if($downmids){
                                $downmids[] = $cmid;
                            }else{
                                $downmids = [$cmid];
                            }
                            //团队业绩 0:统计下单时参与商品 1：统计现在订单参与商品
                            if($lv['up_biglittlearea_yeji_join'] == 1){
                                $childyeji = Db::name('shop_order_goods')->alias('og')
                                    ->join('shop_product p','p.id = og.proid')
                                    ->where('p.biglittlearea_yeji',1)->where('og.mid','in',$downmids)->where('og.status',3)->sum('og.real_totalprice');
                            }else{
                                $childyeji = Db::name('shop_order_goods')->where('mid','in',$downmids)->where('biglittlearea_yeji',1)->where('status',3)->sum('real_totalprice');
                            }
                            if($childyeji>$bigareayeji) $bigareayeji = $childyeji;
                            $allareayeji += $childyeji;
                        }
                        unset($cmid);
                    }
                    $littleareayeji = $allareayeji - $bigareayeji;//小区业绩
                    $bigareayeji += $selfareayeji;//大区业绩加上自己的业绩

                    //判断大小区业绩是否达标
                    if($bigareayeji>=$lv['up_bigarea_yeji'] && $littleareayeji>=$lv['up_littlearea_yeji']){
                        $ismeet = true;
                    }

                    if($lv['up_biglittlearea_yeji_condition'] == 'or'){
                        if($ismeet) $tjor = true;
                    }else{
                        if(!$ismeet) continue;
                        $hasand = true;
                    }
                    
                }
            }

            if($hasor && !$tjor){
                $isup = false;
            }elseif(!$hasor && !$tjor && !$hasand){
                $isup = false;
            }else{
                $isup = true;
            }

            if(getcustom('up_fxorder_condition_new',$aid)){
                /********************************新增升级条件start 20231104*******************************************/
                //四组下级人数任意搭配
                if($lv['up_fxdowncount_new']>0 || $lv['up_fxdowncount2_new']>0 || $lv['up_fxdowncount3_new']>0 || $lv['up_fxdowncount4_new']>0){
                    $downmidcount1 = 0;
                    $downmidcount2 = 0;
                    $downmidcount3 = 0;
                    $downmidcount4 = 0;
                    $up_fxdowncount = intval($lv['up_fxdowncount_new']);
                    $up_fxdowncount2 = intval($lv['up_fxdowncount2_new']);
                    $up_fxdowncount3 = intval($lv['up_fxdowncount3_new']);
                    $up_fxdowncount4 = intval($lv['up_fxdowncount4_new']);
                    if($lv['up_fxdowncount_new'] > 0){
                        $downmids = self::getdownmids($aid,$mid,$lv['up_fxdownlevelnum_new'],$lv['up_fxdownlevelid_new'],$lv['up_with_origin'],$lv['up_with_new'],$down_level_time);
                        $downmidcount1 = count($downmids);
                    }
                    if($lv['up_fxdowncount2_new'] > 0){
                        $downmids2 = self::getdownmids($aid,$mid,$lv['up_fxdownlevelnum2_new'],$lv['up_fxdownlevelid2_new'],$lv['up_with_origin'],$lv['up_with_new'],$down_level_time);
                        $downmidcount2 = count($downmids2);
                    }
                    if($lv['up_fxdowncount3_new'] > 0){
                        $downmids3 = self::getdownmids($aid,$mid,$lv['up_fxdownlevelnum3_new'],$lv['up_fxdownlevelid3_new'],$lv['up_with_origin'],$lv['up_with_new'],$down_level_time);
                        $downmidcount3 = count($downmids3);
                    }
                    if($lv['up_fxdowncount4_new'] > 0){
                        $downmids4 = self::getdownmids($aid,$mid,$lv['up_fxdownlevelnum4_new'],$lv['up_fxdownlevelid4_new'],$lv['up_with_origin'],$lv['up_with_new'],$down_level_time);
                        $downmidcount4 = count($downmids4);
                    }
                    $logic_str = $lv['up_fxorder_condition_new']=='and'?'&&':'||';
                    $logic_str2 = $lv['up_fxorder_condition2_new']=='and'?'&&':'||';
                    $logic_str3 = $lv['up_fxorder_condition3_new']=='and'?'&&':'||';
                    $logic_str4 = $lv['up_fxorder_condition4_new']=='and'?'&&':'||';
                    //依次根据上一个条件的逻辑判断结果进行下一个条件的判断
                    $isup_int = $isup==true?1:0;
                    if($up_fxdowncount>0){
                        $isup = eval("return ".$isup_int." ".$logic_str." ".$downmidcount1.">=".$up_fxdowncount.";");
                        //dump($isup_int." ".$logic_str." ".$downmidcount1.">=".$up_fxdowncount);
                        //dump('逻辑1判断',$isup);
                        $isup_int = $isup==true?1:0;
                    }
                    if($up_fxdowncount2>0){
                        $isup = eval("return ".$isup_int." ".$logic_str2." ".$downmidcount2.">=".$up_fxdowncount2.";");
                        //dump('逻辑2判断',$isup);
                        $isup_int = $isup==true?1:0;
                    }
                    if($up_fxdowncount3>0){
                        $isup = eval("return ".$isup_int." ".$logic_str3." ".$downmidcount3.">=".$up_fxdowncount3.";");
                        //dump('逻辑3判断',$isup);
                        $isup_int = $isup==true?1:0;
                    }
                    if($up_fxdowncount4>0){
                        $isup = eval("return ".$isup_int." ".$logic_str4." ".$downmidcount4.">=".$up_fxdowncount4.";");
                        //dump('逻辑4判断',$isup);
                    }

                }
                /********************************新增升级条件end 20231104*******************************************/
            }
            if(getcustom('up_level_teamorder',$aid)){
                //*****************************根据团队订单升级start 20231106*********************************************
                if($lv['up_teamorder_num']>0 || $lv['up_teamorder_small_num']>0){
                    $logic_str = $lv['up_teamorder_condition']=='and'?'&&':'||';
                    $logic_str2 = $lv['up_teamorder_small_condition']=='and'?'&&':'||';
                    //查询团队订单数量
                    $order_count = self::getTeamOrderNum($aid,$mid,$lv['up_teamorder_lv'],$lv['up_teamorder_levelid']);
                    $teamorder_count = $order_count['teamorder_count']??0;
                    //查询小区团队订单数量
                    $teamorder_small_count = $order_count['teamorder_small_count']??0;
                    //dump('团队订单数量'.$teamorder_count.'小区团队订单数量'.$teamorder_small_count);
                    //判断团队订单逻辑是否符合
                    $isup_int = $isup==true?1:0;
                    //dump('前面所有条件的判断'.$isup_int);
                    $isup = eval("return ".$isup_int." ".$logic_str." ".$teamorder_count.">=".$lv['up_teamorder_num'].";");
                    //dump('团队订单判断'.$isup_int);
                    //判断团队小区订单逻辑是否符合
                    $isup_int = $isup==true?1:0;
                    $isup = eval("return ".$isup_int." ".$logic_str2." ".$teamorder_small_count.">=".$lv['up_teamorder_small_num'].";");
                    //dump('小区团队订单判断'.$isup_int);
                }
                //*****************************根据团队订单升级end 20231106*********************************************
            }
            if(getcustom('levelup_teamnum_peoplenum',$aid)){
                if($lv['up_team_path_num'] >0){
                    $logic_fh = $lv['up_team_path_condition'] =='and'?'&&':'||';
                    //团队几条线
                    //有x条(y人拥有n等级)的线 ，有多少条满足条件的线
                   $teammids =\app\common\Member::getdownmids($aid,$mid,1,0);
                    $team_path_count= 0;//X
                    foreach($teammids as $pk=>$pv){
                        $thismid = [$pv];
                        $thisdownmids = \app\common\Member::getdownmids($aid,$pv);
                        if($thisdownmids){
                            $thisdownmids = array_merge($thismid,$thisdownmids); 
                        }else{
                            $thisdownmids = $thismid;
                        }
                        
                        $thismlist = Db::name('member')->where('aid',$aid)->where('id','in',$thisdownmids)->field('id,levelid')->select()->toArray();
                        
                        if(!$thismlist)continue;
                        $have_lv_num = 0;//Y满足所需等级的人数
                        $up_team_path_level =$lv['up_team_path_level']? explode(',',$lv['up_team_path_level']):[];
                        foreach($thismlist as $mk=>$mv){
                            //设置了等级ID，判断是否在设置的等级中，或者不设置等级
                           
                            if(($up_team_path_level && in_array($mv['levelid'],$up_team_path_level)) || !$up_team_path_level){
                                $have_lv_num++;
                            }
                           
                        }
                        //满足条件的数量和设置的对比
                        if($have_lv_num >= $lv['up_team_people_num']){
                            $team_path_count++;
                        }
                    }
                    Log::write([
                        'file' => __FILE__ . __LINE__,
                        '$team_path_count' => $team_path_count,
                        'up_team_people_num' => $lv['up_team_people_num'],
                    ]);
                    $isup_int = $isup==true?1:0;
                    $isup = eval("return ".$isup_int." ".$logic_fh." ".$team_path_count.">=".$lv['up_team_path_num'].";");
                }
            }
            if(getcustom('member_up_binding_tel',$aid)){
                if($lv['up_binding_tel'] ==1){
                    $logic_btel = $lv['up_binding_tel_condition'] =='and'?'&&':'||';
                    $isup_int = $isup==true?1:0;
                    $is_tel = 0;
                    if($member['tel']) $is_tel = 1;
                    $isup = eval("return ".$isup_int." ".$logic_btel." ".$is_tel.";");
                }
            }

            if(getcustom('levelup_changepid_yeji',$aid)){
                //链动脱离人员订单金额条件
                if($lv['levelup_changepid_yeji']>0){
                    $change_mids =  Db::name('member')->where('aid',$aid)->where('pid_origin','=',$mid)->column('id');
                    if($change_mids){
                        $changepid_yeji = Db::name('shop_order')->where('aid',$aid)
                            ->where('status','in','1,2,3')
                            ->where('mid','in',$change_mids)
                            ->where('createtime','>',$down_level_time)->sum('totalprice');
                    }else{
                        $changepid_yeji = 0;
                    }
                    $logic_str = $lv['levelup_changepid_yeji_con']=='and'?'&&':'||';
                    $isup_int = $isup==true?1:0;
                    $isup = eval("return ".$isup_int." ".$logic_str." ".$changepid_yeji.">=".$lv['levelup_changepid_yeji'].";");
                }
            }
            if(getcustom('levelup_team_yeji_front',$aid)){
                //根据规定时间段内的团队业绩升级
                if($lv['levelup_team_yeji_front_yeji']>0){
                    $member_level_sort = Db::name('member_level')->where('id',$member['levelid'])->value('sort');
                    $down_levelids = Db::name('member_level')->where('aid',$aid)->where('cid', $cid)->where('sort','<=',$member_level_sort)->column('id');
                    $downmids = self::getdownmids($aid,$mid,0,$down_levelids);
                    if($downmids){
                        $yeji_time = time()-$lv['levelup_team_yeji_front_day']*86400;
                        $front_yeji = 0 + Db::name('shop_order_goods')->where('status','in','1,2,3')->where('mid','in',$downmids)->where('createtime','>',$yeji_time)->sum('totalprice');
                        $logic_str = $lv['levelup_team_yeji_front_con']=='and'?'&&':'||';
                        $isup_int = $isup==true?1:0;
                        $isup = eval("return ".$isup_int." ".$logic_str." ".$front_yeji.">=".$lv['levelup_team_yeji_front_yeji'].";");
                    }
                }
            }
            if($isup) $newlv = $lv;
        }

        $handleUpLevel = true;//是否处理自动升级
        //开启升级协议先记录，等前台点击同意协议再进行升级
        if(getcustom('up_level_agree',$aid) && !empty($newlv['is_agree'])  || (getcustom('extend_tencent_qian',$aid) && !empty($newlv['tencent_qian'])) ){
            if($newlv && $newlv['id'] != $levelInfo['levelid']) {
                $handleUpLevel = false;

                $update = [];
                $update['aid'] = $aid;
                $update['mid'] = $mid;
                $update['newlv_id'] = $newlv['id'];
                $update['sort'] = $newlv['sort'];
                $update['cid'] = $cid;
                $update['w_time'] = time();

                $member_level_agree = true;//是否记录升级
                if((getcustom('extend_tencent_qian',$aid) && !empty($newlv['tencent_qian'])) ){
                    $update['type'] = 1;
                    $updata['createflowid'] = $newlv['tencent_qian_createflowid'];
                    //查询是否已签署过相同签署
                    $countqian = Db::name('member_tencent_qianlog')->where('mid',$mid)->where('createflowid',$newlv['tencent_qian_createflowid'])->where('status',2)->count();
                    //若签署过，则可直接升级
                    if(!empty($countqian) && $countqian>0){
                        $handleUpLevel = true;
                        $member_level_agree = false;
                    }
                }
                if($member_level_agree){
                    Db::name('member_level_agree')->insert($update);
                }
            }
        }
        if($handleUpLevel){
            if(getcustom('refund_update_level')){
                if(isset($params['refund_mode']) && $params['refund_mode']){
                    return $newlv;
                }
            }
            self::handleUpLevel($aid,$mid,$newlv,$levelInfo,$member,$cid,$params);
        }

        if(!$isup && $newlv['id'] == $nowlv['id']){
            if(getcustom('levelup_pro_extend_time',$aid) && $nowlv['up_pro_extend_time'] == 1 && $nowlv['up_pro_keep_time'] == 0){
                //不升级，判断是否延期，通过member_levelup_order的时间对比订单时间
                //注意两种方式不可同时开启
                if($nowlv['yxqdate'] > 0){
                    $isextend = false;
                    $last_levelup_order = Db::name('member_levelup_order')->where('aid',$aid)->where('mid',$mid)->where('levelid',$nowlv['id'])->order('createtime','desc')->find();
                    $have_orderids = Db::name('shop_order')->where('aid',$aid)->where('mid',$mid)->where('createtime','>',$last_levelup_order['createtime'])->whereIn('status',[1,2,3])->column('id');
                    if($have_orderids){
                        $up_proids = explode(',',str_replace('，',',',$nowlv['up_proid']));
                        $up_pronums = explode(',',str_replace('，',',',$nowlv['up_pronum']));
                        if(count($up_pronums) > 1) {
                            foreach($up_proids as $k=>$up_proid){
                                $pronum = $up_pronums[$k];
                                if(!$pronum) $pronum = 1;
                                $buynum = Db::name('shop_order_goods')->where('aid',$aid)->where('mid',$mid)->where('proid',$up_proid)->whereIn('orderid',$have_orderids)->where('status','in','1,2,3')->sum('num');
                                if($buynum >= $pronum){
                                    $isextend = true;
                                }
                            }
                        } else {
                            $pronum = $up_pronums[0];
                            if(!$pronum) $pronum = 1;
                            $buynum = 0;
                            foreach($up_proids as $k=>$up_proid){
                                $buynum += Db::name('shop_order_goods')->where('aid',$aid)->where('mid',$mid)->where('proid',$up_proid)->whereIn('orderid',$have_orderids)->where('status','in','1,2,3')->sum('num');
                                if($buynum >= $pronum){
                                    $isextend = true;
                                }
                            }
                        }
                        //满足条件，累加延长等级时间
                        if($isextend){
                            $levelendtime = $member['levelendtime'] + 86400 * $nowlv['yxqdate'];
                            //todo 判断是否默认分组
                            Db::name('member')->where('aid', $aid)->where('id', $mid)->update(['levelendtime' => $levelendtime]);
                            $order = [
                                'aid' => $aid,
                                'mid' => $mid,
                                'from_mid' => $mid,
                                'pid' => $member['pid'],
                                'levelid' => $nowlv['id'],
                                'title' => '复购延期',
                                'totalprice' => 0,
                                'createtime' => time(),
                                'levelup_time' => time(),
                                'beforelevelid' => $nowlv['id'],
                                'form0' => '类型^_^购买商品延长等级',
                                'platform' => platform,
                                'status' => 2
                            ];
                            Db::name('member_levelup_order')->insert($order);
                        }
                    }
                }
            }
            if(getcustom('levelup_pro_keep_time',$aid) && $nowlv['up_pro_keep_time'] == 1 && $nowlv['up_pro_extend_time'] == 0){
                //不升级，判断是否保持有效期（购买时间+有效期天数=过期时间），通过member_levelup_order的时间对比订单时间
                //注意两种方式不可同时开启
                if($nowlv['yxqdate'] > 0){
                    $isextend = false;
                    $last_levelup_order = Db::name('member_levelup_order')->where('aid',$aid)->where('mid',$mid)->where('levelid',$nowlv['id'])->order('createtime','desc')->find();
                    $have_orderids = Db::name('shop_order')->where('aid',$aid)->where('mid',$mid)->where('createtime','>',$last_levelup_order['createtime'])->whereIn('status',[1,2,3])->column('id');
                    if($have_orderids){
                        $up_proids = explode(',',str_replace('，',',',$nowlv['up_proid']));
                        $up_pronums = explode(',',str_replace('，',',',$nowlv['up_pronum']));
                        if(count($up_pronums) > 1) {
                            foreach($up_proids as $k=>$up_proid){
                                $pronum = $up_pronums[$k];
                                if(!$pronum) $pronum = 1;
                                $buynum = Db::name('shop_order_goods')->where('aid',$aid)->where('mid',$mid)->where('proid',$up_proid)->whereIn('orderid',$have_orderids)->where('status','in','1,2,3')->sum('num');
                                if($buynum >= $pronum){
                                    $isextend = true;
                                }
                            }
                        } else {
                            $pronum = $up_pronums[0];
                            if(!$pronum) $pronum = 1;
                            $buynum = 0;
                            foreach($up_proids as $k=>$up_proid){
                                $buynum += Db::name('shop_order_goods')->where('aid',$aid)->where('mid',$mid)->where('proid',$up_proid)->whereIn('orderid',$have_orderids)->where('status','in','1,2,3')->sum('num');
                                if($buynum >= $pronum){
                                    $isextend = true;
                                }
                            }
                        }
                        //满足条件，保持等级时间
                        if($isextend){
                            $levelendtime = time() + 86400 * $nowlv['yxqdate'];
                            //todo 判断是否默认分组
                            Db::name('member')->where('aid', $aid)->where('id', $mid)->update(['levelendtime' => $levelendtime]);
                            $order = [
                                'aid' => $aid,
                                'mid' => $mid,
                                'from_mid' => $mid,
                                'pid' => $member['pid'],
                                'levelid' => $nowlv['id'],
                                'title' => '复购延期',
                                'totalprice' => 0,
                                'createtime' => time(),
                                'levelup_time' => time(),
                                'beforelevelid' => $nowlv['id'],
                                'form0' => '类型^_^购买商品延长等级',
                                'platform' => platform,
                                'status' => 2
                            ];
                            Db::name('member_levelup_order')->insert($order);
                        }
                    }
                }
            }
        }
        if(getcustom('member_shougou_parentreward_wait',$aid) && $sendSgReward){
            $sendSgRewardList = Db::name('member_commission_record_wait')->where('type','shop')->where('orderid',$params['onebuy_orderid'])->select()->toArray();
            $sgRewardList = [];
            $wsids = [];
            foreach ($sendSgRewardList as $k=>$v){
                $wsids[] = $v['id'];
                unset($v['id']);
                $sgRewardList[] = $v;
            }
            if($sgRewardList){
                Db::name('member_commission_record_wait')->whereIn('id',$wsids)->update(['status'=>1]);
                Db::name('member_commission_record')->insertAll($sgRewardList);
            }
        }

        if(getcustom('transfer_order_parent_check')) {
            //用户升级判断级别减掉相应分销数据
            \app\common\Fenxiao::decUplevelTransferOrderCommissionTongji($aid, $mid);
        }
    }
    //处理升级操作
    public static function handleUpLevel($aid,$mid,$newlv,$levelInfo,$member,$cid,$params=[]){
        if($newlv && $newlv['id'] != $levelInfo['levelid']) {
            Log::write([
                'file'=>__FILE__,
                'line'=>__LINE__,
                'handleUpLevel member'=>jsonEncode($member),
                'newlevel'=>jsonEncode($newlv)
            ]);
            if ($newlv['yxqdate'] > 0) {
                $levelendtime = strtotime(date('Y-m-d')) + 86400 + 86400 * $newlv['yxqdate'];
            } else {
                $levelendtime = 0;
            }
            //判断是否默认分组
            if($newlv['cid'] > 0)
                $is_default = Db::name('member_level_category')->where('id', $newlv['cid'])->value('isdefault');
            if ($is_default || $newlv['cid'] == 0) {
                $update = ['levelid' => $newlv['id'], 'levelendtime' => $levelendtime,'levelstarttime' => time()];
               // if(getcustom('coupon_xianxia_buy')){
               //     $update['is_zt_up'] = 0; 
               // }
                if(getcustom('ciruikang_fenxiao',$aid)){
                    if($newlv['is_onebuy'] && $newlv['is_onebuy'] == 1){
                        $update['crk_up_pronum'] = $newlv['up_pronum2'];
                        $update['crk_up_levelid']= $newlv['id'];
                        $update['crk_up_onetime']= time();
                    }
                }
                Db::name('member')->where('aid', $aid)->where('id', $mid)->update($update);
            } else {
                if (getcustom('plug_sanyang',$aid)) {
                    $count = Db::name('member_level_record')->where('aid', $aid)->where('mid', $mid)->where('cid', $newlv['cid'])->count();
                    if($count) Db::name('member_level_record')->where('aid', $aid)->where('mid', $mid)->where('cid', $newlv['cid'])->update(['levelid' => $newlv['id'], 'levelendtime' => $levelendtime]);
                    else {
                        $record_data = ['levelid' => $newlv['id'], 'levelendtime' => $levelendtime];
                        $record_data['aid'] = $aid;
                        $record_data['mid'] = $mid;
                        $record_data['createtime'] = time();
                        $record_data['cid'] = $newlv['cid'];
                        Db::name('member_level_record')->insertGetId($record_data);
                    }
                    Db::name('member_level_record')->where('aid', $aid)->where('mid', $mid)->where('cid', $newlv['cid'])->update(['levelstarttime' => time()]);
                }
            }

            Wechat::updatemembercard($aid, $mid);
            //赠送积分
            if($newlv['up_give_score'] > 0) {
                self::addscore($aid, $mid, $newlv['up_give_score'], '升级奖励');
            }
            //奖励佣金
            if($newlv['up_give_commission'] > 0) {
                self::addcommission($aid,$mid,0,$newlv['up_give_commission'],'升级奖励');
            }
            //奖励余额
            if($newlv['up_give_money'] > 0) {
                self::addmoney($aid,$mid,$newlv['up_give_money'],'升级奖励');
            }
            //赠送上级佣金
            if($newlv['up_give_parent_money'] > 0 && $member['pid']) {
                if(getcustom('coupon_xianxia_buy',$aid)){
                    $next_level = Db::name('member_level')->where('aid',$aid)->where('sort','>',$newlv['sort'])->order('sort asc')->find();
                    if($next_level){
                        self::addcommission($aid, $member['pid'], $mid, $newlv['up_give_parent_money'], '直推奖');
                    }
                }else{
                    self::addcommission($aid, $member['pid'], $mid, $newlv['up_give_parent_money'], '直推奖');
                }
            }
            if(getcustom('coupon_pack')){
                if($newlv['up_give_couponpack']) {
                    \app\common\Coupon::send($aid,$mid,$newlv['up_give_couponpack'],true);
                }
            }

            if($newlv['up_give_parent_coupon_ids'] && $newlv['up_give_parent_coupon_nums'] && $member['pid']){
                $coupon_ids = explode(',',$newlv['up_give_parent_coupon_ids']);
                $coupon_nums = explode(',',$newlv['up_give_parent_coupon_nums']);
                if($coupon_ids){
                    foreach($coupon_ids as $ck=>$coupon_id){
                        if(!$coupon_nums[$ck]){
                            $coupon_nums[$ck] = 1;
                        }
                        for($i=0;$i<$coupon_nums[$ck];$i++){
                            \app\common\Coupon::send($aid,$member['pid'],$coupon_id,true);
                        }
                    }
                }
            }

            //升级赠送优惠券
            if(getcustom('up_give_coupon',$aid)){
                //商城优惠券赠送
                $shop_coupon = $newlv['up_give_coupon']?json_decode($newlv['up_give_coupon'],true):[];
                foreach($shop_coupon as $k=>$v){
                    if($v['num']<1){
                        continue;
                    }
                    for($i=0;$i<$v['num'];$i++){
                        \app\common\Coupon::send($aid,$member['id'],$v['id'],true);
                    }
                }
                //餐饮优惠券赠送
                $restaurant_coupon = $newlv['up_give_restaurant_coupon']?json_decode($newlv['up_give_restaurant_coupon'],true):[];
                foreach($restaurant_coupon as $k=>$v){
                    if($v['num']<1){
                        continue;
                    }
                    for($i=0;$i<$v['num'];$i++){
                        \app\common\Coupon::send($aid,$member['id'],$v['id'],true);
                    }
                }
            }
            $up_giveparent_prize = 0;//升级后 他直推的几个人留给他的上级的同时给上级发放见点奖
            if(getcustom('up_giveparent_prize',$aid)){
                $up_giveparent_prize = $newlv['up_giveparent_prize']?:0;
            }
            if(getcustom('up_giveparent',$aid) && $newlv['up_giveparent_num'] > 0 /*&& $member['pid']*/){ //升级后 他直推的几个人留给他的上级
                //文档 https://doc.weixin.qq.com/doc/w3_AT4AYwbFACwly20n4PhQXGIkzEGvk?scode=AHMAHgcfAA0QNhPwm4AT4AYwbFACw
                $whereup = [];
                $whereup[] = ['aid', '=', $aid];
                $whereup[] = ['pid', '=', $mid];
                if($newlv['up_giveparent_levelid']){
                    //指定等级,多个英文逗号分隔
                    $newlv['up_giveparent_levelid'] = explode(',',$newlv['up_giveparent_levelid']);
                    $whereup[] = ['levelid', 'in', $newlv['up_giveparent_levelid']];
                }
                $downmemberlist = Db::name('member')->where($whereup)->limit($newlv['up_giveparent_num'])->order('id')->select()->toArray();
                if($downmemberlist){
                    $newlv['up_giveparent_levelid_p'] = trim($newlv['up_giveparent_levelid_p']);
                    $newlv_up_giveparent_levelid_arr = explode(',',$newlv['up_giveparent_levelid_p']);
//                    Log::write([
//                        'file'=>__FILE__,
//                        'line'=>__LINE__,
//                        'levelidp'=>$newlv_up_giveparent_levelid_arr,
//                        'downmemberlist'=>jsonEncode($downmemberlist)
//                    ]);
                    foreach($downmemberlist as $downmember){
                        //升级后 他直推的几个人留给他的上级，原推荐人不改变
                        $downmember_level = Db::name('member_level')->where('id',$downmember['levelid'])->field('id,up_change_back')->find();
                        //如同时符合“升级给上级人数”和“升级后回归”，则不脱离，直接回归
                        if($downmember_level['up_change_back'] == 1) {
                            Log::write([
                                'file'=>__FILE__,
                                'error'=>'同时符合“升级给上级人数”和“升级后回归”，则不脱离，直接回归'
                            ]);
                            continue;
                        }
                        //指定上级是默认上级还是指定等级的上级
                        if(!empty($newlv['up_giveparent_levelid_p']) && !empty($newlv_up_giveparent_levelid_arr)){
                            //如果指定了等级，他的所有上级都不在指定的等级范围内，则留给平台
                            $newpid = 0;
                            if($member['path']){
                                $parentList = Db::name('member')->whereIn('levelid',$newlv_up_giveparent_levelid_arr)->where('aid',$aid)->where('id','in',$member['path'])->order(Db::raw('field(id,'.$member['path'].')'))->select()->toArray();
                                if($parentList){
                                    $nearP = end($parentList);
                                    $newpid = $nearP['id'];
                                }
                            }
                        }else{
                            $newpid = $member['pid']?:0;
                        }
                        $updatem = ['id'=>$downmember['id'],'pid'=>$newpid,'change_pid_time'=>time()];
                        if(!$downmember['pid_origin']){
                            $updatem['pid_origin'] = $downmember['pid'];
                            $updatem['path_origin'] = $downmember['path'];
                        }
                        \app\model\Member::edit($aid,$updatem);//todo
                        $insertLog = ['aid'=>$aid,'mid'=>$downmember['id'],'pid'=>$newpid,'createtime'=>time()];
                        if($downmember['pid_origin']){
                            $insertLog['pid_origin'] = $downmember['pid_origin'];
                            $insertLog['path_origin'] = $downmember['path_origin'];
                        }else{
                            $insertLog['pid_origin'] = $downmember['pid'];
                            $insertLog['path_origin'] = $downmember['path'];
                        }
                        Db::name('member_pid_changelog')->insert($insertLog);
                        //给上级发放见点奖
                        if($up_giveparent_prize>0 && $newpid){
                            \app\common\Member::addcommission($aid,$newpid,$downmember['id'],$up_giveparent_prize,t('见点奖',$aid));

                        }
                    }
                    if(getcustom('up_giveparent_help')){
                        //记录新上级的帮扶时间和状态，用于检测持续帮扶
                        $down_mids = implode(',',array_column($downmemberlist,'id'));
                        $data_l = [];
                        $data_l['aid'] = $aid;
                        $data_l['mid'] = $mid;
                        $data_l['pid'] = $newpid;
                        $data_l['give_mids'] = $down_mids;
                        $data_l['createtime'] = time();
                        Db::name('up_giveparent_log')->where('id',$newpid)->insert($data_l);
                    }

                }
            }

            if(getcustom('up_change_pid',$aid)){
                //升级后脱离上级，推荐人为空
                if($newlv['up_change_pid'] == 1 && $member['pid']) {
                    //原推荐人不改变
                    $updatem = ['id'=>$mid,'pid'=>0,'change_pid_time'=>time()];
                    if(!$member['pid_origin']){
                        $updatem['pid_origin'] = $member['pid'];
                        $updatem['path_origin'] = $member['path'];
                    }
                    Log::write([
                        'file'=>__FILE__,
                        'line'=>__LINE__,
                        'edit'=>$updatem
                    ]);
                    \app\model\Member::edit($aid,$updatem);
                    $insertLog = ['aid'=>$aid,'mid'=>$mid,'pid'=>0,'createtime'=>time()];
                    if($member['pid_origin']){
                        $insertLog['pid_origin'] = $member['pid_origin'];
                        $insertLog['path_origin'] = $member['path_origin'];
                    }else{
                        $insertLog['pid_origin'] = $member['pid'];
                        $insertLog['path_origin'] = $member['path'];
                    }
                    Db::name('member_pid_changelog')->insert($insertLog);
                }
                //升级后回归到以前的推荐人下面（仅脱离的人生效）
                if($newlv['up_change_back'] == 1 && $member['pid_origin']){
                    Log::write([
                        'file'=>__FILE__,
                        'line'=>__LINE__,
                        'member[pid_origin]'=>$member['pid_origin']
                    ]);
                    //230909 pid_origin=0改为pid_origin=null
                    \app\model\Member::edit($aid,['id'=>$mid,'pid'=>$member['pid_origin'],'pid_origin'=>null,'path_origin'=>'','change_pid_time'=>time()]);
                    Db::name('member_pid_changelog')->where('aid',$aid)->where('mid',$mid)->where('pid_origin',$member['pid_origin'])->update(['isback'=>1,'updatetime'=>time()]);
                    //回归后判断一下上级是否升级了，上面的$parentList查询会漏掉这种先脱离后回归的会员
                    $pid_origin = Db::name('member')->where('id',$member['pid_origin'])->find();
                    self::douplv($aid,$pid_origin);
                }
            }

            if(getcustom('commission_frozen',$aid)){
                //扶持金解冻冻结
                $admin = Db::name('admin')->where('id',$aid)->find();
                $unfrozen_mid_arr = [];
                $unfrozen1_mids = [];
                $unfrozen2_mids = [];
                $unfrozen3_mids = [];
                $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->find();
                if($admin['commission_frozen'] == 1 && ($member['pid'] || $member['pid_origin'])){
                    $set = Db::name('admin_set')->where('aid',$aid)->find();
                    $fuchi_unfrozen1_ceng = $set['fuchi_unfrozen1_ceng']??0;//伞下会员层级限制
                    $frozen_type = explode(',',$set['fuchi_unfrozen']);
                    //1 伞下x个,等级ID为x的会员（等级ID多个使用英文逗号间隔）
                    //2 直推脱离的会员等级ID升级为x（等级ID多个使用英文逗号间隔）
                    //3 直推会员等级ID升级为x（等级ID多个使用英文逗号间隔）
                    //解冻方式 fuchi_unfrozen_type 1全部(判断条件1和2)，2单线（判断条件1和3）
                    if($frozen_type){
                        $fuchi_unfrozen1_levelidArr = explode(',',$set['fuchi_unfrozen1_levelid']);
                        if(in_array(1,$frozen_type) && in_array($newlv['id'],$fuchi_unfrozen1_levelidArr) && $set['fuchi_unfrozen1_num'] > 0){
                            //存在多个上级同时解冻
                            $parentList = Db::name('member')->field('id,nickname,pid,path,commission_frozen_status')->where('id','in',$member['path'])->where('commission_frozen_status',0)->select()->toArray();
                            foreach($parentList as $parent){
                                $children = self::getdownmids($aid,$parent['id'],$fuchi_unfrozen1_ceng,$set['fuchi_unfrozen1_levelid']);
                                if(count($children) >= $set['fuchi_unfrozen1_num']) {
                                    $unfrozen1_mids[] = $parent['id'];
                                }
                            }
                        }

                        $fuchi_unfrozen2_levelidArr = explode(',',$set['fuchi_unfrozen2_levelid']);
                        //直推脱离的所有会员等级ID升级为x,原父级解冻
                        if($set['fuchi_unfrozen_type']==1 && in_array(2,$frozen_type) && in_array($newlv['id'],$fuchi_unfrozen2_levelidArr)){
                            if($member['pid_origin']){
                                $origin_commission_frozen_status = Db::name('member')->field('id,nickname,pid,path,commission_frozen_status')->where('id',$member['pid_origin'])->value('commission_frozen_status');
                                if(!$origin_commission_frozen_status){
                                    $children = Db::name('member')->where('aid',$aid)->where('pid_origin',$member['pid_origin'])->select()->toArray();
                                    $num_ok = 0;
                                    foreach ($children as $citem){
                                        if(in_array($citem['levelid'],$fuchi_unfrozen2_levelidArr)){
                                            $num_ok++;
                                        }
                                    }
                                    if(count($children) == $num_ok) $unfrozen2_mids[] = $member['pid_origin'];
                                }
                            }else{
                                //升级后回归的会员，230424增加记录表，如不兼容老数据可只判断member_pid_changelog表，无需member表
                                $changelog = Db::name('member_pid_changelog')->where('aid',$aid)->where('mid',$mid)->where('pid_origin',$member['pid'])->find();
                                if($changelog['isback'] == 1){
                                    $origin_commission_frozen_status = Db::name('member')->field('id,nickname,pid,path,commission_frozen_status')->where('id',$member['pid'])->value('commission_frozen_status');
                                    if(!$origin_commission_frozen_status){
                                        $children = Db::name('member')->where('aid',$aid)->where('pid_origin',$member['pid'])->select()->toArray();
                                        $childrenbackMids = Db::name('member_pid_changelog')->where('aid',$aid)->where('pid_origin',$member['pid'])->where('isback',1)->column('mid');
                                        $childrenback = Db::name('member')->where('aid',$aid)->whereIn('id',$childrenbackMids)->select()->toArray();
                                        $num_ok = 0;
                                        foreach ($children as $citem){
                                            if(in_array($citem['levelid'],$fuchi_unfrozen2_levelidArr)){
                                                $num_ok++;
                                            }
                                        }
                                        foreach ($childrenback as $citem){
                                            if(in_array($citem['levelid'],$fuchi_unfrozen2_levelidArr)){
                                                $num_ok++;
                                            }
                                        }
                                        if((count($children) + count($childrenback)) == $num_ok) $unfrozen2_mids[] = $member['pid'];
                                    }
                                }
                            }
                        }
                        $fuchi_unfrozen3_levelidArr = explode(',',$set['fuchi_unfrozen3_levelid']);
                        if($set['fuchi_unfrozen_type']==2 && in_array(3,$frozen_type) && in_array($newlv['id'],$fuchi_unfrozen3_levelidArr)){
                            $unfrozen_pid = $member['pid_origin'] ? $member['pid_origin'] : $member['pid'];
                            $unfrozen3_mids[] = $unfrozen_pid;
                        }
                        //解冻方式 fuchi_unfrozen_type 1全部(判断条件1和2)，2单线（判断条件1和3）
                        if($set['fuchi_unfrozen_type']==1){
                            if(in_array(1,$frozen_type) && in_array(2,$frozen_type)){
                                $unfrozen_mid_arr = array_intersect($unfrozen1_mids,$unfrozen2_mids);
                            }else{
                                if(in_array(1,$frozen_type)){
                                    $unfrozen_mid_arr = $unfrozen1_mids;
                                }
                                if(in_array(2,$frozen_type)){
                                    $unfrozen_mid_arr = $unfrozen2_mids;
                                }
                            }
                            //解冻
                            self::unfrozenMoney($aid,$unfrozen_mid_arr);
                        }elseif($set['fuchi_unfrozen_type']==2){
                            if(in_array(1,$frozen_type) && in_array(3,$frozen_type)){
                                $unfrozen_mid_arr = array_intersect($unfrozen1_mids,$unfrozen3_mids);
                            }else{
                                if(in_array(1,$frozen_type)){
                                    $unfrozen_mid_arr = $unfrozen1_mids;
                                }
                                if(in_array(3,$frozen_type)){
                                    $unfrozen_mid_arr = $unfrozen3_mids;
                                }
                            }
                            //解冻
                            self::unfrozenMoney($aid,$unfrozen_mid_arr, $mid);
                        }
                    }
                }
            }

            if(getcustom('fenhong_jiaquan_bylevel',$aid)){
                if($newlv['fenhong_copies']){
                    \app\common\Member::addfhcopies($aid,$mid,$newlv['fenhong_copies'],'升级奖励');
                    Db::name('member_fenhong_jiaquan')->insert([
                        'aid' => $aid,
                        'mid' => $mid,
                        'type' => 'uplevel',
                        'remark' => '升级奖励',
                        'createtime' => time(),
                        'effect_time' => time(),
                        'jiesuan_time' => time(),
                        'status' => 2,//已结算
                        'copies' => $newlv['fenhong_copies'],
                    ]);
                }
            }
            if(getcustom('yx_queue_duli_queue',$aid)){
                $duli_memebr = Db::name('member')->where('id',$mid)->where('aid',$aid)->find();
                self::duliQueue($aid,$duli_memebr,$newlv);
            }


            // 推荐人有效期
            if(getcustom('levelup_add_parent_time',$aid)){
                self::addParentTime($aid,$member['pid'],$newlv['id']);
            }
            
            Log::write([
                'file'=>__FILE__,
                'line'=>__LIne__,
                'mid'=>$mid,
                'newlvid'=>$newlv['id']
            ]);

            //升级记录
            $order = [
                'aid' => $aid,
                'mid' => $mid,
                'from_mid' => $mid,
                'pid' => $member['pid'],
                'levelid' => $newlv['id'] ,
                'title' => '自动升级',
                'totalprice' => 0,
                'createtime' => time(),
                'levelup_time' => time(),
                'beforelevelid' => $levelInfo['levelid'],
                'form0' => '类型^_^自动升级',
                'platform' => platform,
                'status' => 2,
            ];
            //自动降级，记录是否检测降级状态，并更新之前的升级记录不再检测
            if(getcustom('level_auto_down',$aid)){
                Db::name('member_levelup_order')->where('mid',$mid)->update(['check_down'=>1]);
                $order['check_down'] = 0;
            }
            Db::name('member_levelup_order')->insert($order);
            if(getcustom('network_slide',$aid)){
                //公排网滑落
                $res = self::net_slide($member['pid'],$mid,$newlv['id']);
            }
            $tmplcontent = [];
            $tmplcontent['first'] = '恭喜您成功升级为'.$newlv['name'];
            $tmplcontent['remark'] = '点击进入查看~';
            $tmplcontent['keyword1'] = $newlv['name']; //会员等级
            $tmplcontent['keyword2'] = '已生效';//审核状态
            $rs = \app\common\Wechat::sendtmpl($aid,$mid,'tmpl_uplv',$tmplcontent,m_url('pages/my/usercenter', $aid));
            //等级变更通知
            $tel = $member['tel']?$member['tel']:'';
            if($tel){
                //短信通知
                $oldlevelname = Db::name('member_level')->where('aid',$aid)->where('id',$levelInfo['levelid'])->value('name');
                $expiration_days = '永久';
                if($levelendtime > 0){
                    $expiration_days= getTimeDiff(time(),$levelendtime);
                }
                 \app\common\Sms::send($aid,$tel,'tmpl_member_uplevel',['old_level' => $oldlevelname,'new_level' => $newlv['name'],'expiration_days' => $expiration_days]);
            }
            
            if(getcustom('levelupifdownup',$aid) && $member['path']){
                $levelArr = Db::name('member_level')->where('aid',$aid)->where('cid', $cid)->order('sort,id')->column('sort','id');
                $parentList = Db::name('member')->where('id','in',$member['path'])->select()->toArray();
                foreach($parentList as $parent){
                    if($levelArr[$parent['levelid']] < $levelArr[$newlv['id']]){
                        Db::name('member')->where('aid', $aid)->where('id', $parent['id'])->update(['levelid' => $newlv['id'], 'levelendtime' => $levelendtime]);
                    }
                }
            }
            if(getcustom('ciruikang_fenxiao',$aid)){
                //一次性购买升级奖励上级
                if($member['pid'] && $params && $params['onebuy'] && $params['onebuy_orderid'] && $newlv['is_onebuy'] && $newlv['is_onebuy'] == 1){
                    \app\custom\CiruikangCustom::deal_onebuyup($aid,$mid,$member,$params,$levelInfo);
                }
            }
            if(getcustom('fenhong_max_add',$aid)){
                if($newlv['fenhong_max_money']>0){
                    self::addfenhongmaxnum($aid,$mid,$newlv['fenhong_max_money'],'升级'.$newlv['name'].'('.$newlv['id'].')'.'增加');
                }
            }
        }
    }
    public static function addFenhongMax($aid,$pid){
        if(getcustom('fenhong_max_add')) {
            $parent = Db::name('member')->where('id', $pid)->where('aid', $aid)->find();
            $level = Db::name('member_level')->where('aid', $aid)->where('id', $parent['levelid'])->find();
            //分红额度规则设置
            $fenhong_max_set = json_decode($level['fenhong_max_set'],true);
            $mid_arr = [];//记录增加分红额度的会员数据
            if($fenhong_max_set){
                $fenhong_max = 0;
                foreach($fenhong_max_set as $k=>$v){
                    if($v['tjnum']<=0 || $v['fenhong_max']<=0){
                        continue;
                    }
                    //推荐人数统计，满足条件，增加分红额度
                    $tj_mids = Db::name('member')
                        ->where('pid', $pid)
                        ->where('aid', $aid)
                        ->where('levelid', $v['levelid'])
                        ->where('fenhong_max_levelid','<>',$v['levelid'])
                        ->column('id');
                    if(count($tj_mids)>=$v['tjnum']){
                        $fenhong_max = bcadd($fenhong_max, $v['fenhong_max'], 2);
                        $mid_arr[] = ['mids'=>implode(',',$tj_mids), 'fenhong_max'=>$v['fenhong_max'], 'levelid'=>$v['levelid']];
                        //记录推荐人帮增加分红的升级等级，防止重复增加额度
                        Db::name('member')
                            ->where('id','in', $tj_mids)
                            ->update(['fenhong_max_levelid'=>$v['levelid']]);
                    }
                }
                if($fenhong_max>0){
                    //记录日志
                    foreach($mid_arr as $v){
                        self::addfenhongmaxnum($aid,$pid,$v['fenhong_max'],'',$v['mids'],$v['levelid']);
                    }
                }
            }
            return true;
        }
    }

    public static function unfrozenMoney($aid,$unfrozen_mid_arr,$from_mid=0)
    {
        if(getcustom('commission_frozen',$aid)){
            $unfrozen_mid_arr = array_unique($unfrozen_mid_arr);
            if($from_mid){
                //单线
                $children = self::getdownmids($aid,$from_mid,0);
                $children[] = $from_mid;
                $children[] = 0;
                foreach ($unfrozen_mid_arr as $mid){
                    $frozen_record = Db::name('member_fuchi_record')->where('aid',$aid)->where('mid',$mid)->where('frommid','in',$children)->where('status',0)->select()->toArray();
                    foreach ($frozen_record as $v){
                        self::addFuchi($aid,$v['mid'],$v['frommid'],$v['commission']*-1,'解冻');
                        self::addcommission($aid, $v['mid'], $v['frommid'], $v['commission'], '解冻',1,'unfrozen');
                    }
                    Db::name('member_fuchi_record')->where('aid',$aid)->where('mid',$mid)->where('frommid','in',[$from_mid,0])->where('status',0)->update(['status'=>1,'endtime'=>time()]);
                }
            }else{
                foreach ($unfrozen_mid_arr as $mid){
                    Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['commission_frozen_status'=>1]);
                    $frozen_record = Db::name('member_fuchi_record')->where('aid',$aid)->where('mid',$mid)->where('status',0)->select()->toArray();
                    if($frozen_record){
                        foreach ($frozen_record as $v){
                            self::addFuchi($aid,$v['mid'],$v['frommid'],$v['commission']*-1,'解冻');
                            self::addcommission($aid, $v['mid'], $v['frommid'], $v['commission'], '解冻',1,'unfrozen');
                        }
                    }
                    //非正常的冻结，没有冻结记录
                    else{
                        $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->find();
                        if($member && $member['fuchi_money'] > 0){
                            self::addFuchi($aid,$member['id'],0,$member['fuchi_money']*-1,'解冻');
                            self::addcommission($aid, $member['id'], 0, $member['fuchi_money'], '解冻',1,'unfrozen');
                        }
                    }
                    Db::name('member_fuchi_record')->where('aid',$aid)->where('mid',$mid)->where('status',0)->update(['status'=>1,'endtime'=>time()]);
                }
            }
        }
    }

    //加余额
    //新增$$params['canminus']=1的情况下可以扣成负数 2025-07-19
    public static function addmoney($aid,$mid,$money,$remark,$frommid=0,$paytype='',$rechargeid='',$params=[]){
        if($money==0) return ;
        $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
        if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];
        //冻结余额
        $cashback_lock = 0;
        if(getcustom('yx_cashback_decmoney_lock',$aid) && !getcustom('yx_new_score',$aid)){
            $cashback_lock = Db::name('admin_set')->where('aid',$aid)->value('cashback_decmoney_lock');
        }
        if($cashback_lock && $money>0){
            //冻结余额(排除购物返现自身释放的金额)
            if(empty($params['type']) || ($params['type']!='cashback' && $params['type']!='new_score')) {
                $res = \app\custom\MemberCustom::cashback_lock($aid, $mid, $money, $remark);
                $money = $res['money'];
                if ($money <= 0) {
                    return ['status'=>1,'msg'=>'转入'.t('冻结余额')];
                }
            }
        }
        /*****************新积分功能 余额触发加速释放 start**********************/
        $score_money = 0;
        if(getcustom('yx_new_score',$aid) && getcustom('yx_new_score_speed',$aid)){
            $score_money = Db::name('newscore_set')->where('aid',$aid)->value('release_speed');
        }
        if($score_money && $money>0){
            //冻结余额(排除新积分自身释放的金额)
            if(empty($params['type']) || $params['type']!='new_score') {
                $res = \app\custom\NewScore::speedRelease($aid, $mid, $money, $remark);
                $money = $res['money'];
                if ($money <= 0) {
                    return ['status'=>1,'msg'=>'转入'.t('新积分待返现')];
                }
            }
        }
        /*****************新积分功能 余额触发加速释放 end**********************/
        if(getcustom('w7moneyscore')) {
            $w7moneyscore = db('admin_set')->where(['aid'=>$aid])->value('w7moneyscore');
            if($w7moneyscore == 1){
                return self::addw7moneyscore($aid,$member,2,$money,$remark);
            }else{
                $after = $member['money'] + $money;
                Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['money'=>$after]);
            }
        } else {
            $money_weishu = 2;
            if(getcustom('member_money_weishu')){
                $money_weishu = Db::name('admin_set')->where('aid',$aid)->value('member_money_weishu');
            }
            $money = dd_money_format($money,$money_weishu);
            $after = $member['money'] + $money;
            //减后 余额为负数的情况
            if($after < 0 && $money < 0 && !$params['canminus']){
                return ['status'=>0,'msg'=>t('会员').'余额不足'];
            }
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['money'=>$after]);
        }

        $data = [];
        $data['aid'] = $aid;
        $data['mid'] = $mid;
        $data['money'] = $money;
        $data['after'] = $after;
        $data['createtime'] = time();
        $data['remark'] = $remark;
        $data['paytype'] = $paytype;
        if(getcustom('money_transfer') || getcustom('money_friend_transfer')) {
            $data['from_mid'] = $frommid;
        }
        if($rechargeid){
            $data['rechargeid'] = $rechargeid;
        }
        if($params){
            if(getcustom('scoreshop_otheradmin_buy',$aid)){
                //其他平台兑换总平台扣除其他平记录扣除来源aid
                if($params['optaid']){
                    $data['optaid'] = $params['optaid'];
                }
            }
            if(getcustom('moneylog_detail') || getcustom('money_pay_fee_rate',$aid)){
                $data['ordernum'] = $params['ordernum'];
                $data['type'] = $params['type'];
            }
            if(getcustom('recharge_use_mendian')){
                $data['mdid'] = $params['mdid']??0;
            }
            if(getcustom('yx_cashback_decmoney')){
                $data['type'] = $params['type']??'';
            }
        }
        $data['uid'] = defined('uid') && !empty(uid)?uid:0;//记录操作员ID 2024.9.13增加
        $log_id = Db::name('member_moneylog')->insertGetId($data);
        if(getcustom('yx_cashback_decmoney',$aid)){
            if(empty($data['type']) || $data['type']!='cashback'){
                \app\custom\OrderCustom::dec_cashback($aid,$mid,$money,$log_id,$remark);
            }
        }
        //20250707 因升级操作过于耗时注释掉
        //self::uplv($aid,$mid);
        Wechat::updatemembercard($aid,$mid);
        $is_send_tmpl = 1;
        if(getcustom('restaurant_finance_notice_switch')){
            if(!$member['is_receive_finance_tmpl']) $is_send_tmpl = 0;
        }
        if($is_send_tmpl){
            $tmplcontent = [];
            $tmplcontent['first'] = '您的'.t('余额').'发生变动，变动金额：'.$money;
            $tmplcontent['remark'] = '点击进入查看~';
            $tmplcontent['keyword1'] = date('Y-m-d H:i'); //变动时间
            $tmplcontent['keyword2'] = $remark;  //变动类型
            $tmplcontent['keyword3'] = (string) round($money,2);  //变动金额
            $tmplcontent['keyword4'] = (string) round($after,2);  //当前余额
            $tmplcontentNew = [];
            $tmplcontentNew['thing2'] = str_replace(',','',mb_substr($remark,0,5));//消费项目
            $tmplcontentNew['amount3'] = round($money,2);//消费金额
            $tmplcontentNew['amount4'] = round($after,2);//卡内余额
            $tmplcontentNew['time6'] = date('Y-m-d H:i'); //变动时间
            $rs = \app\common\Wechat::sendtmpl($aid,$mid,'tmpl_moneychange',$tmplcontent,m_url('pages/my/usercenter', $aid),$tmplcontentNew);
        }
        if(getcustom('hotel')){
            //小程序消息通知
            $text = \app\model\Hotel::gettext($aid);
            $tmplcontent = [];
            $tmplcontentNew = [];
            $tmplcontentNew['amount2'] = (string) round($after,2);
            $tmplcontentNew['amount1'] = (string) round($money,2);
            $tmplcontentNew['time3'] =  date('Y-m-d H:i');
            $tmplcontentNew['thing4'] = $remark;
            $tmplcontentNew['thing5'] =  '点击进入查看~';
            if($member['wxopenid']){
                //$aid=1,$mid,$tmpltype,$contentnew,$tourl='',$content
                \app\common\Wechat::sendwxtmpl($aid,$member['id'],'tmpl_moneychange',$tmplcontentNew,'pages/my/usercenter',$tmplcontentNew);
            }
        }
        if(getcustom('restaurant_finance_notice_switch')){
            if($member['tel'] && $money < 0 && $member['is_receive_finance_sms']){
                $rs = \app\common\Sms::send($aid,$member['tel'],'tmpl_money_change',['money'=>$money,'sy_money'=>$after]);
            }
        }
         //变动通知
        if(getcustom('sms_temp_money_use')){
            if($member['tel'] && $money < 0){
                $rs = \app\common\Sms::send($aid,$member['tel'],'tmpl_money_use',['money'=>$money,'real_money'=>$money,'sy_money'=>$after]);
            }
        }
		if(getcustom('sms_money_score_change')){
			if($member['tel'] && $money < 0){
				$tmplcontent = ['year' => date('Y'),'month' => date('m'),'day' => date('d'),'time' => date('H:i'),'money' => $money,'score' => 0,'sy_money' => $after,'sy_score' => $member['score']];
				\app\common\Sms::send($aid,$member['tel'],'tmpl_scorechange',$tmplcontent);
			}
		}
        return ['status'=>1,'msg'=>''];
    }
    //加积分
    //@update 22-7-21 增加渠道
    //params 参数数组 如 'canminus'=>true 可以为负 
    public static function addscore($aid,$mid,$score,$remark,$channel='',$bid=0,$frommid=0,$addtotal=1,$params = []){
        if($score==0) return ;
        $score_weishu = 0;
        if(getcustom('score_weishu',$aid)){
            $score_weishu = Db::name('admin_set')->where('aid',$aid)->value('score_weishu');
            $score = dd_money_format($score,$score_weishu);
        }
        if($score_weishu==0){
            $score = intval($score);
            if($score==0){
                return ;
            }
        }

        $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
        if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];
        if(getcustom('andun_jiuxuan',$aid) && $member['levelid'] == 5){
            return ['status'=>0,'msg'=>'该等级不能获取积分'];
        }

        $canminus = false;//可以为负
        if($params && $params['canminus']){
            $canminus = true;
        }

        if(getcustom('yx_score_freeze',$aid)){
            $score_freeze_set = Db::name('score_freeze_set')->where('aid',$aid)->where('bid',0)->find();
            //如果积分冻结开启，且是增加积分，且不是释放的时候 加入冻结
            if($score_freeze_set['status'] ==1){
                if( $score > 0 && !$params['is_release']){
                    if(getcustom('yx_score_freeze_release') && mb_strpos($remark, '退款') !== false){
                        // 退款的不进冻结
                    }else{
                        return  self::addscorefree($aid,$mid,$score,$remark,$channel,$bid);
                    }
                }else{
                    if(mb_strpos($remark, '退款') !== false){
                        return  self::addscorefree($aid,$mid,$score,$remark,$channel,$bid);
                    }
                }
                
            }
        }

        $member['score'] = self::getscore($member);
        if($score < 0 && $member['score'] < $score*-1) {
            if($remark == '过期扣除'){
                $score = $member['score'] *-1;
            }else{
                if(!$canminus){
                    return ['status'=>0,'msg'=>t('积分').'不足'];
                }
            }
        }

        $updata = [];
        $after = $member['score'] + $score;
        $updata['score'] = $after;

        $totalscore = $member['totalscore']?$member['totalscore']:0;
        //如果已定时任务执行统计过累计积分，且积分大于0，则走累计积分
        if($member['iscountscore'] && $score > 0 && $addtotal==1){
            $totalscore = $totalscore + $score;
        }
        $updata['totalscore'] = $totalscore;

        if(getcustom('member_tag_age',$aid) && $score < 0){
            $updata['tag_age_score'] = $member['tag_age_score'] + $score;
            if(!$updata['tag_age_score'] || $updata['tag_age_score'] <= 0){
                $updata['tag_age_score'] = 0;
            }
        }
        if(getcustom('score_to_money_and_commission',$aid)){
            //积分转余额和佣金
            $set = Db::name('admin_set')->where('aid',$aid)->field('score_to_money_and_commission,score_to_money_bili,score_to_money_ratio,score_to_commission_bili,score_to_commission_ratio')->find();
            if($set['score_to_money_and_commission'] == 1){
                $moneyRatio = $set['score_to_money_bili'] / 100;
                $commissionRatio = $set['score_to_commission_bili'] / 100;
                $totalRatio = $moneyRatio + $commissionRatio;

                //换算比例
                $money = $score * $moneyRatio * $set['score_to_money_ratio'];
                $commission = $score * $commissionRatio * $set['score_to_commission_ratio'];

                if($money > 0){
                    self::addmoney($aid, $mid, $money, t('积分').'转'.t('余额'));
                }
                if($commission > 0){
                    self::addcommission($aid, $mid, 0, $commission, t('积分').'转'.t('佣金'));
                }

                //总比例大于100的时候结束
                if ($totalRatio >= 1) return ['status' => 1, 'msg' => ''];

                //计算剩余积分
                $remainingScore = $score * (1 - $totalRatio);
                $score = $remainingScore;
                if($score <= 0) return ['status' => 1,'msg' => ''];
                $updata['score'] = $member['score'] + $score;
                $updata['totalscore'] = $member['totalscore'] + $score;
            }

        }
        if(getcustom('w7moneyscore',$aid)) {
            $w7moneyscore = db('admin_set')->where(['aid'=>$aid])->value('w7moneyscore');
            if($w7moneyscore == 1){
                return self::addw7moneyscore($aid,$member,1,$score,$remark);
            }else{
                Db::name('member')->where('aid',$aid)->where('id',$mid)->update($updata);
            }
        } else {
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update($updata);
        }

        $data = [];
        $data['aid'] = $aid;
        $data['bid'] = $bid;
        $data['mid'] = $mid;
        $data['score'] = $score;
        $data['after'] = $after;
        $data['createtime'] = time();
        $data['remark'] = $remark;
        $data['type'] = 1;
        $data['channel'] = $channel;
        $data['status'] = ($remark == '过期扣除') ? -1 : 0;
        $data['is_cancel'] = ($remark == '撤销操作') ? 1 : 0;
        if(getcustom('score_transfer',$aid) || getcustom('score_friend_transfer',$aid)) {
            $data['from_mid'] = $frommid;
        }
        if($params){
            if(getcustom('scoreshop_otheradmin_buy',$aid)){
                //其他平台兑换总平台扣除其他平记录扣除来源aid
                if($params['optaid']){
                    $data['optaid'] = $params['optaid'];
                }
            }
        }
        $data['uid'] = defined('uid') && !empty(uid)?uid:0;//记录操作员ID 2024.9.13增加
        Db::name('member_scorelog')->insert($data);
        Wechat::updatemembercard($aid,$mid,$remark);

        if($score < 0 && $remark != '撤销操作' && $remark != '过期扣除'){
            $score2 = $score*-1;
            $loglist = Db::name('member_scorelog')->where('aid',$aid)->where('mid',$mid)->where('score','>',0)
                ->where('status',0)->where('is_cancel',0)->order('createtime','asc')->select()->toArray();
            foreach ($loglist as $item){
                if($item['score'] - $item['used'] <= $score2){
                    Db::name('member_scorelog')->where('id',$item['id'])->update(['used'=>$item['score'],'status'=>1]);
                }else{
                    Db::name('member_scorelog')->where('id',$item['id'])->update(['used'=>$item['used']+$score2]);
                    break;
                }
                $score2 = $score2 - ($item['score'] - $item['used']);
            }
			//变动通知
			if(getcustom('sms_money_score_change')){
				if($member['tel']){
					$tmplcontent = ['year' => date('Y'),'month' => date('m'),'day' => date('d'),'time' => date('H:i'),'money' => 0,'score' => $score2,'sy_money' => $member['money'],'sy_score' => $after];
					\app\common\Sms::send($aid,$member['tel'],'tmpl_scorechange',$tmplcontent);
				}
			}
        }

        if(getcustom('business_selfscore',$aid) && $bid ==0){
            Db::name('admin')->where('id',$aid)->inc('score',-$score)->update();
            $data = [];
            $data['aid'] = $aid;
            $data['score'] = -$score;
            $data['after'] = Db::name('admin')->where('id',$aid)->value('score');
            $data['createtime'] = time();
            if($score > 0){
                $data['remark'] = '给用户'.$member['nickname'].'加'.t('积分');
            }else{
                $data['remark'] = '用户'.$member['nickname'].'花费'.t('积分');
            }
            Db::name('admin_scorelog')->insert($data);
        }

        if(getcustom('member_sync_xiaoe',$aid) && (strpos($remark, '小鹅通同步') === false)){//同步小鹅通
            $isadd = ($score < 0) ? 0 : 1;
            $xe_remark = '商城同步';
            if($remark){
                $xe_remark = '商城同步:'. $remark;
            }
            \app\custom\Xiaoe::updateScore($aid,$mid,$score,$xe_remark,$isadd);
        }

        return ['status'=>1,'msg'=>''];
    }

    //加金豆
    public static function addgoldbean($aid,$mid,$gold_bean,$remark,$channel='',$bid=0,$params = []){
        if(getcustom('gold_bean')) {
            if ($gold_bean == 0) return;

            $member = Db::name('member')->where('aid', $aid)->where('id', $mid)->lock(true)->find();
            if (!$member) return ['status' => 0, 'msg' => t('会员') . '不存在'];

            $canminus = true;//可以为负

            if ($gold_bean < 0 && $member['gold_bean'] < $gold_bean * -1) {
                if ($remark == '过期扣除') {
                    $gold_bean = $member['score'] * -1;
                } else {
                    if (!$canminus) {
                        return ['status' => 0, 'msg' => t('金豆') . '不足'];
                    }
                }
            }

            $updata = [];
            $after = $member['gold_bean'] + $gold_bean;
            $updata['gold_bean'] = $after;

            $total_gold_bean = $member['total_gold_bean'] ? $member['gold_bean'] : 0;
            $updata['total_gold_bean'] = $total_gold_bean;
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update($updata);


            $data = [];
            $data['aid'] = $aid;
            $data['bid'] = $bid;
            $data['mid'] = $mid;
            $data['gold_bean'] = $gold_bean;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            $data['type'] = 1;
            $data['channel'] = $channel;
            $data['status'] = ($remark == '过期扣除') ? -1 : 0;
            $data['is_cancel'] = ($remark == '撤销操作') ? 1 : 0;
            if ($params) {

            }
            $data['uid'] = defined('uid') && !empty(uid) ? uid : 0;//记录操作员ID 2024.9.13增加
            Db::name('member_gold_bean_log')->insert($data);
            Wechat::updatemembercard($aid, $mid, $remark);

            if ($gold_bean < 0 && $remark != '撤销操作' && $remark != '过期扣除') {
                $score2 = $gold_bean * -1;
                $loglist = Db::name('member_gold_bean_log')->where('aid', $aid)->where('mid', $mid)->where('gold_bean', '>', 0)
                    ->where('status', 0)->where('is_cancel', 0)->order('createtime', 'asc')->select()->toArray();
                foreach ($loglist as $item) {
                    if ($item['score'] - $item['used'] <= $score2) {
                        Db::name('member_gold_bean_log')->where('id', $item['id'])->update(['used' => $item['gold_bean'], 'status' => 1]);
                    } else {
                        Db::name('member_gold_bean_log')->where('id', $item['id'])->update(['used' => $item['used'] + $score2]);
                        break;
                    }
                    $score2 = $score2 - ($item['gold_bean'] - $item['used']);
                }
            }
            return ['status' => 1, 'msg' => ''];
        }
    }

    //增加冻结账户
    public static function addFreezeCredit($aid, $mid, $money, $remark, $exchange_id=0)
    {
        if (getcustom('yx_queue_free_freeze_account')) {
            if ($money == 0) return;
            $member = Db::name('member')->where('aid', $aid)->where('id', $mid)->lock(true)->find();
            if (!$member) return ['status' => 0, 'msg' => t('会员') . '不存在'];
            $after = $member['freeze_credit'] + $money;
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['freeze_credit'=>$after]);
            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['money'] = $money;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            $data['exchange_id'] = $exchange_id;
            Db::name('member_freeze_credit_log')->insert($data);
            $tmplcontent = [];
            $tmplcontent['first'] = '您的' . t('冻结账户') . '发生变动，变动数量：' . $money;
            $tmplcontent['remark'] = '点击进入查看~';
            $tmplcontent['keyword1'] = date('Y-m-d H:i'); //变动时间
            $tmplcontent['keyword2'] = $remark;  //变动类型
            $tmplcontent['keyword3'] = (string)round($money, 2);  //变动金额
            $tmplcontent['keyword4'] = (string)round($after, 2);  //当前余额
            $tmplcontentNew = [];
            $tmplcontentNew['thing2'] = str_replace(',', '', mb_substr($remark, 0, 5));//消费项目
            $tmplcontentNew['amount3'] = round($money, 2);//消费金额
            $tmplcontentNew['amount4'] = round($after, 2);//卡内余额
            $tmplcontentNew['time6'] = date('Y-m-d H:i'); //变动时间
            $rs = \app\common\Wechat::sendtmpl($aid, $mid, 'tmpl_moneychange', $tmplcontent, m_url('pages/my/usercenter', $aid), $tmplcontentNew);
        }
    }


    //积分过期
    public static function scoreExpire(){
        if(getcustom('score_expire')){
            $admin_setlist = Db::name('admin_set')->where('score_expire_status','=',1)->where('score_expire_days','>',0)->column('aid,score_expire_status,score_expire_days','aid');
            $time = time();
            foreach($admin_setlist as $set){
                $scoreloglist = Db::name('member_scorelog')->where('aid',$set['aid'])->where('status',0)->where('is_cancel',0)->where('score','>',0)
                    ->where('createtime','<',$time - $set['score_expire_days'] * 86400)->select()->toArray();
                foreach ($scoreloglist as $item){
                    Db::name('member_scorelog')->where('id',$item['id'])->update(['expire_time'=>$time,'status'=>-1]);
                    $expirScore = ($item['score']-$item['used']) * -1;//过期积分=获得积分-已用积分
                    self::addscore($item['aid'],$item['mid'],$expirScore,'过期扣除');
                }
            }
        }
    }

    public static function addgongxian($aid,$mid,$value,$remark,$channel='',$orderid=0){
        if(getcustom('member_gongxian')){
            if($value==0) return ;
            $value = intval($value);
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];

            if($value < 0 && $member['gongxian'] < $value*-1) return ['status'=>0,'msg'=>t('贡献').'不足'];

            $after = $member['gongxian'] + $value;
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['gongxian'=>$after]);

            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['value'] = $value;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            $data['channel'] = $channel;
            $data['orderid'] = $orderid;
            Db::name('member_gongxianlog')->insert($data);
            return ['status'=>1,'msg'=>''];
        }
    }
    //加提现积分
    public static function addscore_withdraw($aid,$mid,$score,$remark){
        if($score==0) return ;
        $score = intval($score);
        $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
        if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];
        if($score < 0 && $member['score_withdraw'] < $score*-1) return ['status'=>0,'msg'=>t('积分').'不足'];

        $after = $member['score_withdraw'] + $score;
        Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['score_withdraw'=>$after]);

        $data = [];
        $data['aid'] = $aid;
        $data['mid'] = $mid;
        $data['score'] = $score;
        $data['after'] = $after;
        $data['createtime'] = time();
        $data['remark'] = $remark;
        $data['type'] = 2;
        Db::name('member_scorelog')->insert($data);
        return ['status'=>1,'msg'=>''];
    }
    //加佣金

    /**
     * @param $aid
     * @param $mid
     * @param $frommid
     * @param $commission
     * @param $remark
     * @param $addtotal
     * @param $fhtype 类型 枚举值 'unfrozen'解冻,'withdraw_back'提现退回,admin管理员修改
     * @param $levelid 订单支付之前的会员级别，不传使用会员当前级别，用于判断扶持金条件
     * @param $oldlogid 原记录id,member_fenhonglog.id
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function addcommission($aid,$mid,$frommid=0,$commission=0,$remark='',$addtotal=1,$fhtype='',$levelid=0,$ispj=0,$oldlogid=0){
        if($commission==0) return ;
        $real_commission = $commission;
        $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();

        if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];

        $set = Db::name('admin_set')->where('aid',$aid)->find();
        $commission_weishu = 2;//佣金位数
        if(getcustom('fenhong_money_weishu',$aid)){
            $commission_weishu = $set['fenhong_money_weishu'];
        }
        if(getcustom('commission_service_fee',$aid)){
            if($set['commission_service_fee'] > 0 && $commission > 0 && !in_array($fhtype, ['unfrozen','withdraw_back','admin'])){
                //平台服务费
                $commission_service_fee = round($set['commission_service_fee'] * $commission / 100,2);
                $commission -= $commission_service_fee;
            }
        }

        if($commission > 0 && $set['commission2scorepercent'] > 0 && !in_array($fhtype, ['unfrozen','withdraw_back','admin'])){
            $oldcommission = $commission;
            $commission = round($commission * (1-$set['commission2scorepercent']*0.01),2);
            $score = $oldcommission - $commission;
            self::addscore($aid,$mid,$score,$remark);
        }

        if(getcustom('commission_frozen',$aid)){
            if($commission > 0 && !in_array($fhtype, ['unfrozen','withdraw_back'])){
                //是否仅支持团队分红
                $fuchi_only_teamfenhong = Db::name('admin_set')->where('aid',$aid)->value('fuchi_only_teamfenhong');
                if($fuchi_only_teamfenhong==0 || $fhtype=='teamfenhong'){
                    //扶持金冻结
                    $fuchi_levelids = explode(',',$set['fuchi_levelids']);
                    $member_levelid = $levelid>0?$levelid:$member['levelid'];
                    if(in_array(-1,$fuchi_levelids) || in_array($member_levelid,$fuchi_levelids)){
                        $admin = Db::name('admin')->where('id',$aid)->find();
                        if($admin['commission_frozen'] == 1 && $set['fuchi_percent'] > 0 && $set['fuchi_percent'] <= 100 && !$member['commission_frozen_status']){
                            $fuchi_money = round($set['fuchi_percent'] * $commission / 100,2);
                            $commission -= $fuchi_money;
                            self::addFuchi($aid,$mid,$frommid,$fuchi_money,$remark);
                            Db::name('member_fuchi_record')->insert(['aid'=>$aid,'mid'=>$mid,'frommid'=>$frommid,'orderid'=>0,'ogid'=>0,'type'=>'',
                                'commission'=>$fuchi_money,'score'=>0,'remark'=>$remark,'createtime'=>time()]);
                        }
                    }
                }
            }
        }
        if(getcustom('commission_xiaofei',$aid)){
            if($commission > 0 && !in_array($fhtype, ['unfrozen','withdraw_back'])){
                //佣金金冻结
                $frozen_levelids = explode(',',$set['xiaofei_levelids']);
                if(in_array(-1,$frozen_levelids) || in_array($member['levelid'],$frozen_levelids)){
                    if($set['xiaofei_percent'] > 0 && $set['xiaofei_percent'] <= 100){
                        $xiaofei_money = round($set['xiaofei_percent'] * $commission / 100,2);
                        $commission -= $xiaofei_money;
                        self::addXiaofei($aid,$mid,$frommid,$xiaofei_money,$remark);

                    }
                }
            }
        }
        // 等级限制 达到奖励上限不再发放任何佣金，0表示不限制
        if(getcustom('commission_max',$aid)){
            $m_level_com = Db::name('member_level')->where('id',$member['levelid'])->value('commission_max');
            if($m_level_com > 0){
                // totalcommission是字符型
                $m_total = floatval($member['totalcommission']);
                if($m_total >= $m_level_com){
                    return ['status'=>0,'msg'=>t('佣金').'已达上限！'];
                }
                $com = $m_level_com - $m_total;
                if($com <= $commission){
                    $commission = $com;
                }
            }
        }

        // 佣金上限限制 达到奖励上限不再发放任何佣金
        if(getcustom('member_commission_max',$aid)){
            $member_commission_max = floatval($member['commission_max']);
            if($set['member_commission_max'] && $commission>0){
//                $m_total = $member['totalcommission'];
//                if($m_total >= $member_commission_max){
//                    return ['status'=>0,'msg'=>t('佣金').'已达上限！'];
//                }
//                $com = $member_commission_max - $m_total;
//                if($com <= $commission){
//                    $commission = $com;
//                }
                if($member_commission_max <= $commission){
                    $commission = $member_commission_max;
                }
                if($commission<=0){
                    return ['status'=>0,'msg'=>t('佣金').'已达上限！'];
                }
            }
            if($fhtype!='admin'){
                $addtotal = 1;
            }
        }

        $totalcommission = $member['totalcommission'];

        //佣金发放到余额 0:关闭 1：开启
        $iscommission_send_money = 0;
        if(getcustom('commission_to_money',$aid)){
            //判断是否开启了佣金发放到余额功能，开启了则佣金数额发放到余额
            $commission_send_money   = Db::name('admin_set')->where('aid',$aid)->value('commission_send_money');
            $iscommission_send_money = $commission_send_money?1:0;
            //兼容判断，判断佣金是否自动打款，不是则减少的佣金不走发放到余额步骤
            if($iscommission_send_money == 1){
                if($commission<0){
                    // if(strpos($remark,'后台修改：') !== 0 ){
                        $iscommission_send_money = 0;
                    // }
                    if($set['commission_autowithdraw'] && $remark == '佣金打款成功'){
                        $iscommission_send_money = 1;
                        $commission_autowithdraw_back  = 1;
                    }
                }
            }


            //开启有效则会员佣金不变
            if($iscommission_send_money){

                if(isset($commission_autowithdraw_back) && $commission_autowithdraw_back == 1){
                    $fa_yue = $commission ;
                    $after           = $member['commission'];
                    $addtotal = 0;
                }else{
                    $commission_send_money_bili   = Db::name('admin_set')->where('aid',$aid)->value('commission_send_money_bili');
                    if($commission_send_money_bili >= 0){
                        if($commission_send_money_bili > 100){
                            $commission_send_money_bili = 100;
                        }
                        $fa_yue = dd_money_format($commission * $commission_send_money_bili * 0.01);
                        $commission -= $fa_yue;
                        $after           = $member['commission'] + $commission;
                        if($commission > 0 && $addtotal==1){
                            $totalcommission += $commission;
                        }
                    }

                    $has_log = 1;
                }
                $update_member   = ['totalcommission'=>$totalcommission,'commission'=>$after];
            }
        }

        // 佣金自动提现
        if(getcustom('commission_autowithdraw',$aid) &&  $iscommission_send_money == 0){
            $commission_autowithdraw = $set['commission_autowithdraw'];
            $iscommission_send_money = $commission_autowithdraw?1:0;
            if($iscommission_send_money  > 0){
                if($commission<0){
                    // if(strpos($remark,'后台修改：') !== 0 ){
                        $iscommission_send_money = 0;
                    // }
                    if($remark == '佣金打款成功'){
                        $iscommission_send_money = 1;
                    }
                }
                //开启有效则会员佣金不变
                if($iscommission_send_money){
                    $fa_yue = $commission;
                    $addtotal = 0;
                    $totalcommission = $member['totalcommission'];
                    $after           = $member['commission'];
                    $update_member   = ['totalcommission'=>$totalcommission,'commission'=>$after];
                }
            }
            
        }

        if(getcustom('commission_percent_to_parent',$aid)){
            //佣金百分比给上级
            $member_level = Db::name('member_level')->where('aid',$aid)->where('id',$member['levelid'])->find();
            //判断是否开启上级佣金分账
            if($member_level['commission_percent_to_parent_status'] == 1){
                //分佣金额
                $commission_pid_fee = 0;

                $fen_type = '';
                if('1' == $member_level['commission_percent_to_parent_type'] && (mb_strpos($fhtype, 'fenxiao') !== false)){
                    //如果是分销
                    $commission_pid_fee = round($member_level['commission_percent_to_parent'] / 100 * $commission,2);
                    $fen_type = '分销';
                }elseif('2' == $member_level['commission_percent_to_parent_type'] && (mb_strpos($fhtype, 'fenhong') !== false)){
                    //分红
                    $commission_pid_fee = round($member_level['commission_percent_to_parent'] / 100 * $commission,2);
                    $fen_type = '分红';
                }elseif('1,2' == $member_level['commission_percent_to_parent_type'] && (mb_strpos($fhtype, 'fenxiao') !== false) || (mb_strpos($fhtype, 'fenhong') !== false)){
                    $commission_pid_fee = round($member_level['commission_percent_to_parent'] / 100 * $commission,2);
                    if(mb_strpos($fhtype, 'fenxiao') !== false){
                        $fen_type = '分销';
                    }else if(mb_strpos($fhtype, 'fenhong') !== false){
                        $fen_type = '分红';
                    }
                }

                if($commission_pid_fee > 0){
                    $grant_mid = 0;//发放人id
                    $is_old = '';
                    //分佣条件 0未脱离  1脱离过上级
                    if($member_level['commission_percent_to_parent_condition'] == 0){
                        //如果是未脱离过的  直接分给现上级
                        if($member['pid'] > 0 && empty($member['pid_origin'])){
                            //if($member['pid'] > 0){
                            //如果是从上级扣
                            if($member_level['commission_percent_to_parent_divide_type'] == 1){
                                $commission -= $commission_pid_fee;
                            }
                            $grant_mid = $member['pid'];
                        }
                    }else if($member_level['commission_percent_to_parent_condition'] == 1){//如果条件是脱离过
                        //判断是否脱离过上级
                        if($member['pid_origin'] > 0){
                            //判断分佣目标 0原上级  1现上级
                            if($member_level['commission_percent_to_parent_target'] == 0){
                                //给原上级发
                                //如果是从上级扣
                                if($member_level['commission_percent_to_parent_divide_type'] == 1){
                                    $commission -= $commission_pid_fee;
                                }
                                $grant_mid = $member['pid_origin'];
                                $is_old = '原';
                            }elseif ($member_level['commission_percent_to_parent_target'] == 1 && $member['pid'] > 0){
                                //给现上级发
                                //如果是从上级扣
                                if($member_level['commission_percent_to_parent_divide_type'] == 1){
                                    $commission -= $commission_pid_fee;
                                }
                                $grant_mid = $member['pid'];
                            }
                        }
                    }

                    if($grant_mid > 0){
                        //给上级发
                        self::addcommission($aid,$grant_mid,$mid,$commission_pid_fee, $is_old.'下级'.$fen_type.'佣金分佣','','commission_percent_to_parent');

//                        writeLog($member_level['divide_type'],'yjbbb');
//                        writeLog($fen_type,'yjbbb');
//                        writeLog($oldlogid,'yjbbb');
                        //如果是从上级扣 增加一条减去的记录 防止对账有问题
                        if($member_level['commission_percent_to_parent_divide_type'] == 1){
                            if($fen_type == '分销' && $oldlogid > 0){
                                $old_log_info_arr = Db::name('member_commission_record')->where('aid',$aid)->where('id',$oldlogid)->find();
                                unset($old_log_info_arr['id']);
                                unset($old_log_info_arr['score']);
                                unset($old_log_info_arr['butie']);
                                unset($old_log_info_arr['copies']);
                                $old_log_info_arr['commission'] = $commission_pid_fee;
                                $old_log_info_arr['remark'] = '减去给'.$is_old.'上级(ID:'.$grant_mid.')的分佣';
                                $old_log_info_arr['createtime'] = time();
                                $old_log_info_arr['endtime'] = time();
                                $old_log_info_arr['status'] = 1;
                                //writeLog($old_log_info_arr,'yjbbb.log');
                                Db::name('member_commission_record')->insert($old_log_info_arr);
                                //writeLog(Db::name('member_commission_record')->getLastSql(),'yjbbb');
                            }

                            if($fen_type == '分红' && $oldlogid > 0){
                                $old_log_info_arr = Db::name('member_fenhonglog')->where('aid',$aid)->where('id',$oldlogid)->find();
                                unset($old_log_info_arr['id']);
                                unset($old_log_info_arr['send_commission']);
                                unset($old_log_info_arr['send_money']);
                                unset($old_log_info_arr['send_score']);
                                unset($old_log_info_arr['send_fuchi']);
                                unset($old_log_info_arr['score']);
                                unset($old_log_info_arr['copies']);
                                $old_log_info_arr['commission'] = $commission_pid_fee;
                                $old_log_info_arr['remark'] = $old_log_info_arr['send_remark'] = '减去给'.$is_old.'上级(ID:'.$grant_mid.')的分佣';
                                $old_log_info_arr['createtime'] = time();
                                $old_log_info_arr['status'] = 1;
                                Db::name('member_fenhonglog')->insert($old_log_info_arr);
                            }
                        }
                    }
                }
            }
        }

        if(getcustom('member_level_breedcommission',$aid)){
            //类型为培育奖，积分分红点分红不再继续发培育奖
            if($fhtype!='breedcommission' && $fhtype!='score_to_fenhongdian' && $commission >0 && !empty($member['pid'])){
                //上级发培育奖
                $plevel = Db::name('member_level')
                    ->alias('ml')
                    ->join('member m','m.levelid = ml.id')
                    ->where('m.id',$member['pid'])->where('ml.can_agent','>',0)->field('ml.id,ml.breedcommission')->find();
                if($plevel && $plevel['breedcommission']>0){
                    $breedcommission = $commission * $plevel['breedcommission']/100;
                    $breedcommission = dd_money_format($breedcommission,$commission_weishu);
                    if($breedcommission>0){
                        //给上级发佣金
                        self::addcommission($aid,$member['pid'],$member['mid'],$breedcommission,'发放培育奖',1,'breedcommission');
                    }
                }
            }
        }
        if(!$iscommission_send_money){
            $after = $member['commission'] + $commission;
             if($commission > 0 && $addtotal==1){
                $totalcommission += $commission;
             }
            $update_member = ['totalcommission'=>$totalcommission,'commission'=>$after];
        }

        if($fhtype == 'fenhong') {
            $update_member['total_fenhong_partner'] = $member['total_fenhong_partner'] + $commission;
            $update_member['total_fenhong'] = $member['total_fenhong'] + $commission;
        } elseif($fhtype == 'teamfenhong') {
            $update_member['total_fenhong_team'] = $member['total_fenhong_team'] + $commission;
            $update_member['total_fenhong'] = $member['total_fenhong'] + $commission;
        } elseif($fhtype == 'level_teamfenhong') {
            $update_member['total_fenhong_level_team'] = $member['total_fenhong_level_team'] + $commission;
            $update_member['total_fenhong'] = $member['total_fenhong'] + $commission;
        } elseif($fhtype == 'areafenhong') {
            $update_member['total_fenhong_area'] = $member['total_fenhong_area'] + $commission;
            $update_member['total_fenhong'] = $member['total_fenhong'] + $commission;
        }elseif($fhtype == 'touzi_fenhong') {
            $update_member['total_fenhong_touzi'] = $member['total_fenhong_touzi'] + $commission;
            $update_member['total_fenhong'] = $member['total_fenhong'] + $commission;
        }elseif($fhtype == 'gongxian_fenhong'){
            $update_member['total_fenhong_gongxian'] = $member['total_fenhong_gongxian'] + $commission;
            $update_member['total_fenhong'] = $member['total_fenhong'] + $commission;
        }elseif($fhtype == 'fenhong_huiben') {
            $update_member['total_fenhong_huiben'] = bcadd($member['total_fenhong_huiben'] , $real_commission,2);
            $update_member['total_fenhong_huiben2'] = bcadd($member['total_fenhong_huiben2'] , $real_commission,2);
            $update_member['total_fenhong'] = $member['total_fenhong'] + $commission;
        }elseif($fhtype == 'teamyejifenhong') {
            $update_member['total_team_yeji_fenhong'] = $member['total_team_yeji_fenhong'] + $commission;
            $update_member['total_fenhong'] = $member['total_fenhong'] + $commission;
        }
        if(getcustom('product_baodan',$aid)){
            //如果总佣金大于上限值,减去佣金，并且加 冻结
            if($update_member['totalcommission'] > $member['baodan_max'] && $addtotal==1  && $commission>0){
                $update_member['totalcommission'] = $update_member['totalcommission'] - $commission;
                $update_member['commission'] = $update_member['commission'] - $commission;
                //加冻结
                $totalfreeze =$member['baodan_freeze'] + $commission;
                $update_member['baodan_freeze']  = $totalfreeze;
                //加记录
                $baodan_data = [];
                $baodan_data['aid'] = $aid;
                $baodan_data['mid'] = $mid;
                $baodan_data['commission'] = $commission;
                $baodan_data['after'] = $totalfreeze;
                $baodan_data['createtime'] = time();
                $baodan_data['remark'] = $remark;
                Db::name('member_baodan_freeze_log')->insert($baodan_data);
            }
        }
        if(getcustom('commission_perc_to_score',$aid)){
            if($set['commission_perc_to_score']>0 && $commission>0){
                $scoreNum = round($set['commission_perc_to_score'] * $commission / 100,2);
                \app\common\Member::addscore($aid,$mid,$scoreNum,t('佣金').'到'.t('积分'));
            }
        }

        if(getcustom('commission_to_money',$aid) || getcustom('commission_autowithdraw',$aid)){
            //判断是否开启了佣金发放到余额功能，开启了则佣金数额发放到余额
            if($iscommission_send_money){
                if(strpos($remark,'后台修改：')==0){
                    $remark = str_replace('后台修改','后台修改佣金',$remark);
                }
                //佣金数额发放到余额
                if(!empty($fa_yue) && $fa_yue != 0){
                    $res = self::addmoney($aid,$mid,$fa_yue,$remark,$frommid,$fhtype);
                    if(!$res || $res['status'] != 1){
                        $msg = $res['msg']?$res['msg']:'操作失败';
                        return ['status'=>0,'msg'=>$msg];
                    } 
                }
                
            }
        }

        Db::name('member')->where('aid',$aid)->where('id',$mid)->update($update_member);

         if(!$iscommission_send_money || (isset($has_log) && $commission!=0)){
            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['frommid'] = $frommid;
            $data['commission'] = $commission;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            if($fhtype) $data['fhtype'] = $fhtype;
            $data['service_fee'] = $commission_service_fee > 0 ? $commission_service_fee : 0;
            $data['uid'] = defined('uid') && !empty(uid)?uid:0;//记录操作员ID 2024.9.13增加
            $data['fhid'] = $oldlogid;//记录来源分红数据id
            Db::name('member_commissionlog')->insert($data);
        }
        if(getcustom('member_commission_max',$aid)){
            if($commission>0 && $fhtype!='withdraw_back'){
                //减少会员佣金上限
                self::addcommissionmax($aid,$mid,-$commission,$remark,'',0,0);
            }
        }

        if(getcustom('commission_autowithdraw',$aid)){
            if(strpos($remark,'后台修改：')!==0){
                $commission_autowithdraw = $set['commission_autowithdraw'];
                if($commission_autowithdraw == 1){ //佣金自动打款
                    if(!empty($fa_yue) && $fa_yue > 0){
                        $rs = \app\common\Wxpay::transfers($aid,$mid,$fa_yue,'','',$remark);
                        if($rs && $rs['status']==1){ //打款成功
                            self::addcommission($aid,$mid,'',-$fa_yue,'佣金打款成功');
                        }else{
                            Log::write('自动打款失败--'.$fa_yue);
                            Log::write($rs);
                        } 
                    }
                    
                }
            }
        }
        
        if(getcustom('forcerebuy',$aid) && $commission > 0){
            $forcerebuyList = Db::name('forcerebuy')->where('aid',$aid)->where('type',0)->where('status',1)->where('commission','<=',$totalcommission)->where("find_in_set('-1',gettj) or find_in_set('".$member['levelid']."',gettj)")->select()->toArray();
            foreach($forcerebuyList as $forcerebuy){
                $orderwhere = [];
                $orderwhere[] = ['aid','=',$aid];
                $orderwhere[] = ['mid','=',$mid];
                $orderwhere[] = ['isfg','=',1];
                $orderwhere[] = ['status','in','1,2,3'];
                if($forcerebuy['fwtype'] == 1){
                    $orderwhere[] = ['cid','in',$forcerebuy['categoryids']];
                }elseif($forcerebuy['fwtype'] == 2){
                    $orderwhere[] = ['proid','in',$forcerebuy['productids']];
                }
                $totalprice = Db::name('shop_order_goods')->where($orderwhere)->sum('totalprice');
                if($totalprice < $forcerebuy['price']){
                    if($forcerebuy['wfgtype'] == 0){
                        Db::name('member')->where('id', $mid)->update(['commission_isfreeze' => 1]);
                    }else{
                        Db::name('member')->where('id', $member['id'])->update(['levelid' => $forcerebuy['wfglvid'], 'levelendtime' => 0]);
                    }
                }
            }
        }
        
        /*佣金累计达到X元降级*/
        if(getcustom('member_level_down_commission',$aid)){
            $member = Db::name('member')->field('id,totalcommission,levelid,levelendtime,pid')->where('aid',$aid)->where('id',$mid)->find();
            $memberlevel =  Db::name('member_level')->field('id,down_level_totalcommission,down_level_id2,recovery_level_proid')->where('id',$member['levelid'])->find();
            if(!$member['isauto_down'] && $memberlevel['down_level_totalcommission']>0 && ($member['totalcommission']-$member['down_commission'])>=$memberlevel['down_level_totalcommission']){
                self::level_autodown_commission($aid,$member,$memberlevel['down_level_id2']);
            }
        }
        if(getcustom('yx_queue_free_fanli_commission',$aid)){
            if($ispj == 0){
                self::sendPingji($aid,$mid,$commission);
            }
        }
        if(getcustom('active_coin_commission',$aid)){
            //佣金自动转激活币
            if($commission>0 && $fhtype!='withdraw_back'){
                $active_coin_set = Db::name('active_coin_set')->where('aid',$aid)->field('commission_to_activecoin,commission_to_money,commission_to_activecoin_levelids')->find();
                if($active_coin_set['commission_to_activecoin'] >0 || $active_coin_set['commission_to_money']>0){
                    $levelids = explode(',',$active_coin_set['commission_to_activecoin_levelids']);
                    $levelids = array_filter($levelids);
                    if(!$levelids || in_array($member['levelid'],$levelids)){
                        $active_coin_num = bcmul($commission , $active_coin_set['commission_to_activecoin']/100,2);
                        if($active_coin_num>0){
                            self::addactivecoin($aid,$mid,$active_coin_num,t('佣金').'自动转入');
                            self::addcommission($aid,$mid,0,-$active_coin_num,'自动转入'.t('激活币'));
                        }
                        $active_money = bcmul($commission,$active_coin_set['commission_to_money']/100,2);
                        if($active_money>0){
                            self::addmoney($aid,$mid,$active_money,t('佣金').'自动转入');
                            self::addcommission($aid,$mid,0,-$active_money,'自动转入'.t('余额'));
                        }
                    }
                }
            }
        }
        if(getcustom('yx_commission_to_lingqiantong',$aid)){
            if($commission > 0){
                $check = \app\custom\CommissionLingqiantong::checklingqiantongorder($aid,$mid,$commission,$remark);
                if($check['status'] == 1){
                    return $check;
                }
            }            
        }

        return ['status'=>1,'msg'=>''];
    }

    public static function sendPingji($aid,$mid,$commission=0){
        //$fromid 当前会员  $mid 直推  
        if(getcustom('yx_queue_free_fanli_commission')){
            if($commission > 0){
                $member = Db::name('member')->where('id',$mid)->field('id,pid,levelid')->find();
                if($member && $member['pid']){
                    $parent = Db::name('member')->where('id',$member['pid'])->where('aid',$aid)->field('id,pid,levelid')->find();
                    if($parent){
                        //如果出现平级
                        $queueset = Db::name('queue_free_set')->where('aid',$aid)->where('bid',0)->find();
                        if($member['levelid'] == $parent['levelid'] && $queueset['queue_free_commission_pj'] > 0){
                            $pj_money =dd_money_format($commission * $queueset['queue_free_commission_pj'] * 0.01);
                            Log::write([
                                'file' => __FILE__ . __LINE__,
                                '$member_id_levelid' => $member['id'].'---'.$member['levelid'],
                                '$parent_id_levelid' => $parent['id'].'---'.$parent['levelid'],
                                'queue_free_commission_pj' => $queueset['queue_free_commission_pj'],
                                '$commission' => $commission,
                                '$pj_money' => $pj_money,
                            ]);
                            if($pj_money > 0){
                                \app\common\Member::addcommission($aid,$parent['id'],$mid,$pj_money,'佣金平级奖',1,'',0,1);
                            }
                        }
                    }
                } 
            }
        }
    }
    //加扶持基金
    public static function addFuchi($aid,$mid,$frommid,$commission,$remark){
        if(getcustom('commission_frozen') || getcustom('member_level_paymoney_commissionfrozenset')){
            if($commission==0) return ;
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];

            $after = $member['fuchi_money'] + $commission;
            $update_member = ['fuchi_money'=>$after];
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update($update_member);

            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['frommid'] = $frommid;
            $data['commission'] = $commission;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            $id = Db::name('member_fuchi_log')->insertGetId($data);
            return ['status'=>1,'msg'=>'','id'=>$id];
        }
    }
    //加冻结佣金
    public static function addXiaofei($aid,$mid,$frommid,$commission,$remark){
        if(getcustom('commission_xiaofei')){
            if($commission==0) return ;
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];

            $after = $member['xiaofei_money'] + $commission;
            $update_member = ['xiaofei_money'=>$after];
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update($update_member);

            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['frommid'] = $frommid;
            $data['commission'] = $commission;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            Db::name('member_xiaofei_money_log')->insert($data);
            return ['status'=>1,'msg'=>''];
        }
    }

    //加通证
    public static function addtongzheng($aid,$mid,$money,$remark,$frommid=0,$paytype=''){
        if(getcustom('product_givetongzheng')) {
            if ($money == 0) return;
            $member = Db::name('member')->where('aid', $aid)->where('id', $mid)->lock(true)->find();
            if (!$member) return ['status' => 0, 'msg' => t('会员') . '不存在'];
            $after = $member['tongzheng'] + $money;
            Db::name('member')->where('aid', $aid)->where('id', $mid)->update(['tongzheng' => $after]);

            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['money'] = $money;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            $data['paytype'] = $paytype;
            $data['from_mid'] = $frommid;

            Db::name('member_tongzhenglog')->insert($data);

            $tmplcontent = [];
            $tmplcontent['first'] = '您的' . t('通证') . '发生变动，变动金额：' . $money;
            $tmplcontent['remark'] = '点击进入查看~';
            $tmplcontent['keyword1'] = date('Y-m-d H:i'); //变动时间
            $tmplcontent['keyword2'] = $remark;  //变动类型
            $tmplcontent['keyword3'] = (string)round($money, 2);  //变动金额
            $tmplcontent['keyword4'] = (string)round($after, 2);  //当前余额
            $tmplcontentNew = [];
            $tmplcontentNew['thing2'] = str_replace(',', '', mb_substr($remark, 0, 5));//消费项目
            $tmplcontentNew['amount3'] = round($money, 2);//消费金额
            $tmplcontentNew['amount4'] = round($after, 2);//卡内余额
            $tmplcontentNew['time6'] = date('Y-m-d H:i'); //变动时间
            $rs = \app\common\Wechat::sendtmpl($aid, $mid, 'tmpl_moneychange', $tmplcontent, m_url('pages/my/usercenter', $aid), $tmplcontentNew);
            //变动通知
            return ['status' => 1, 'msg' => ''];
        }
    }

    //佣金对碰提现积分
    public static function add_commission_withdraw_score($aid,$mid,$score,$remark,$from_id=0){
        if($score==0) return ;
        $score = intval($score);
        $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
        if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];
        if($score < 0 && $member['commission_withdraw_score'] < $score*-1) return ['status'=>0,'msg'=>'提现积分不足'];

        $after = $member['commission_withdraw_score'] + $score;
        Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['commission_withdraw_score'=>$after]);

        $data = [];
        $data['aid'] = $aid;
        $data['mid'] = $mid;
        $data['score'] = $score;
        $data['after'] = $after;
        $data['createtime'] = time();
        $data['remark'] = $remark;
        $data['from_mid'] = $from_id;
        Db::name('member_commission_withdraw_scorelog')->insert($data);
        return ['status'=>1,'msg'=>''];
    }
    //获取余额
    public static function getmoney($member){
        if(!$member || !$member['id']) return '0.00';
        $member = db('member')->where(['id'=>$member['id']])->find();
        if(getcustom('w7moneyscore')) {
            static $w7moneyscore = -1;
            if($w7moneyscore == -1){
                $w7moneyscore = db('admin_set')->where(['aid'=>$member['aid']])->value('w7moneyscore');
            }
            $w7uniacid = db('admin_set')->where(['aid'=>$member['aid']])->value('w7uniacid');
//            Log::write([
//                'file' => __FILE__,
//                'line' => __LINE__,
//                '$w7uniacid' => $w7uniacid
//            ]);
            if($w7moneyscore == 1 && $w7uniacid){
                $fansinfo = Db::connect('w7')->table('ims_mc_mapping_fans')->where("uniacid='{$w7uniacid}' and (openid='{$member['mpopenid']}' or (unionid!='' && unionid is not null && unionid='{$member['unionid']}') or (openid!='' && openid is not null && openid='{$member['wxopenid']}'))")->find();
//                Log::write([
//                    'file' => __FILE__,
//                    'line' => __LINE__,
//                    '$fansinfo' => $fansinfo
//                ]);
                $uid = $fansinfo['uid'];
                $mcmember = Db::connect('w7')->table('ims_mc_members')->where(['uid'=>$uid])->find();
//                Log::write([
//                    'file' => __FILE__,
//                    'line' => __LINE__,
//                    '$uid' => $uid,
//                    '$mcmember' => $mcmember
//                ]);
                if(!$uid || !$mcmember) return '0.00';
                return $mcmember['credit2'];
            }
        }

        $money_weishu = 2;
        if(getcustom('member_money_weishu',$member['aid'])){
            $money_weishu = Db::name('admin_set')->where('aid',$member['aid'])->value('member_money_weishu');
            $money_weishu = $money_weishu??2;
        }
        //取最小余额位数，防止五入超出最大值
        $tenpow = pow(10, $money_weishu);
        if($tenpow>0){
            $member['money'] = floor($member['money'] * $tenpow)/$tenpow;
        }else{
            $member['money'] = floor($member['money']);
        }
        return $member['money'];
    }
    //获取积分
    public static function getscore($member){
        if(!$member || !$member['id']) return '0';
        $member = db('member')->where(['id'=>$member['id']])->find();

        if(getcustom('w7moneyscore')) {
            static $w7moneyscore = -1;
            if($w7moneyscore == -1){
                $w7moneyscore = db('admin_set')->where(['aid'=>$member['aid']])->value('w7moneyscore');
            }
            $w7uniacid = db('admin_set')->where(['aid'=>$member['aid']])->value('w7uniacid');
            if($w7moneyscore == 1 && $w7uniacid){
                $fansinfo = Db::connect('w7')->table('ims_mc_mapping_fans')->where("uniacid='{$w7uniacid}' and (openid='{$member['mpopenid']}' or (unionid!='' && unionid is not null && unionid='{$member['unionid']}') or (openid!='' && openid is not null && openid='{$member['wxopenid']}'))")->find();
                $uid = $fansinfo['uid'];
                $mcmember = Db::connect('w7')->table('ims_mc_members')->where(['uid'=>$uid])->find();
                if(!$uid || !$mcmember) return '0';
                return intval($mcmember['credit1']);
            }
        }

        return $member['score'];
    }

    public static function addw7moneyscore($aid,$member,$type,$money,$remark){
        $w7uniacid = db('admin_set')->where(['aid'=>$aid])->value('w7uniacid');
        if(empty($w7uniacid)) {
            return ['status'=>0,'msg'=>'w7uniacid empty'];
        }
        $fansinfo = Db::connect('w7')->table('ims_mc_mapping_fans')->where("uniacid='{$w7uniacid}' and (openid='{$member['mpopenid']}' or (unionid!='' && unionid is not null && unionid='{$member['unionid']}') or (openid!='' && openid is not null && openid='{$member['wxopenid']}'))")->find();
//        \think\facade\Log::write([
//            'file' => __FILE__,
//            'line' => __LINE__,
//            '$member' => $member,
//            '$fansinfo' => $fansinfo,
//            'sql' =>  Db::connect('w7')->table('ims_mc_mapping_fans')->getLastSql()
//        ]);
        $openid = $member['mpopenid'];
        if(!$openid) $openid = $member['unionid'];
        if(!$openid) $openid = $member['wxopenid'];
        if(!$fansinfo){
            $rec = array();
            $rec['acid'] = $w7uniacid;
            $rec['uniacid'] = $w7uniacid;
            $rec['openid'] = '';//$openid
            $rec['nickname'] = $member['nickname'];
            $rec['unionid'] = $member['unionid'];
            $rec['follow'] = $member['subscribe'] ? 1 : 0;
            $rec['followtime'] = $member['subscribe_time'] ? $member['subscribe_time'] : $member['createtime'];
            $rec['tag'] = base64_encode(serialize([
                'openid'=>$openid,
                'nickname'=>$member['nickname'],
                'sex'=>$member['sex'],
                'province'=>$member['province'],
                'city'=>$member['city'],
                'country'=>$member['country'],
                'unionid'=>$member['unionid'],
                'subscribe'=>$member['subscribe'],
                'subscribe_time'=>$member['subscribe_time'],
            ]));
            $member2 = array();
            $member2['uniacid'] = $w7uniacid;
            $member2['email'] = md5($openid).'@we7.cc';
            $member2['salt'] = random(8);
            $default_groupid = Db::connect('w7')->table('ims_mc_groups')->where(['uniacid'=>$w7uniacid,'isdefault'=>1])->value('groupid');
            $member2['groupid'] = $default_groupid;
            $member2['createtime'] = time();
            $member2['nickname'] = $member['nickname'];
            $member2['avatar'] = $member['headimg'];
            $member2['nationality'] = $member['country'];
            $member2['resideprovince'] = $member['province'];
            $member2['residecity'] = $member['city'];
            $config = include(ROOT_PATH.'config.php');
            $member2['password'] = md5($openid . $member2['salt'] . $config['authkey']);
            $rec['uid'] = Db::connect('w7')->table('ims_mc_members')->insertGetId($member2);
            Db::connect('w7')->table('ims_mc_mapping_fans')->insertGetId($rec);
        }
        $fansinfo = Db::connect('w7')->table('ims_mc_mapping_fans')->where("uniacid='{$w7uniacid}' and (openid='{$member['mpopenid']}' or (unionid!='' && unionid is not null && unionid='{$member['unionid']}') or (openid!='' && openid is not null && openid='{$member['wxopenid']}'))")->find();
        $uid = $fansinfo['uid'];
        $mcmember = Db::connect('w7')->table('ims_mc_members')->where(['uid'=>$uid])->find();
        if($uid == 0 || !$mcmember){
            $member2 = array();
            $member2['uniacid'] = $w7uniacid;
            $member2['email'] = md5($openid).'@we7.cc';
            $member2['salt'] = random(8);
            $default_groupid = Db::connect('w7')->table('ims_mc_groups')->where(['uniacid'=>$w7uniacid,'isdefault'=>1])->value('groupid');
            $member2['groupid'] = $default_groupid;
            $member2['createtime'] = time();
            $member2['nickname'] = $member['nickname'];
            $member2['avatar'] = $member['headimg'];
            $member2['nationality'] = $member['country'];
            $member2['resideprovince'] = $member['province'];
            $member2['residecity'] = $member['city'];
            $config = include(ROOT_PATH.'config.php');
            $member2['password'] = md5($openid . $member2['salt'] . $config['authkey']);
            $uid = Db::connect('w7')->table('ims_mc_members')->insertGetId($member2);
            Db::connect('w7')->table('ims_mc_mapping_fans')->where(['fanid'=>$fansinfo['fanid']])->update(['uid'=>$uid]);
            $mcmember = Db::connect('w7')->table('ims_mc_members')->where(['uid'=>$uid])->find();
        }
        $after = $mcmember['credit'.$type] + $money;
        Db::connect('w7')->table('ims_mc_members')->where(['uid'=>$uid])->update(['credit'.$type=>$after]);
        $data = array(
            'uid' => $uid,
            'credittype' => 'credit'.$type,
            'uniacid' => $w7uniacid,
            'num' => $money,
            'createtime' => time(),
            'operator' => '',
            'module' => 'ddwx_shop',
            'clerk_id' => '',
            'store_id' => '',
            'clerk_type' => 1,
            'remark' => $remark,
            'real_uniacid' => $uid
        );
        Db::connect('w7')->table('ims_mc_credits_record')->insert($data);

        if($type == 2){
            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $member['id'];
            $data['money'] = $money;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            Db::name('member_moneylog')->insert($data);
            Db::name('member')->where(['aid'=>$aid,'id'=>$member['id']])->update(['money'=>$after]);
            Wechat::updatemembercard($aid,$member['id']);
        }else{
            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $member['id'];
            $data['score'] = $money;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            Db::name('member_scorelog')->insert($data);
            Db::name('member')->where(['aid'=>$aid,'id'=>$member['id']])->update(['score'=>$after]);
            Wechat::updatemembercard($aid,$member['id'],$remark);
        }

        return ['status'=>1,'msg'=>''];
    }
    //获取多少级以内的下级
    /**
     * @param $aid
     * @param $mid
     * @param $levelnum 层级
     * @param $levelid 指定等级id 可以逗号多个等级id
     * @param $with_origin 脱离后作为原上级的升级条件，0不包含 1包含
     * @param $with_new 脱离后作为新上级的升级条件，0不包含 1包含
     * @return array
     */
    public static function getdownmids($aid,$mid,$levelnum=0,$levelid=0,$with_origin = 0,$with_new = 1,$down_level_time=0){
        $downmids = [];
        if($levelid == 0){
            if($with_new)
                $memberlist = Db::name('member')->field('id,path')->where('aid',$aid)->where('find_in_set('.$mid.',path)')->where('createtime','>',$down_level_time)->select()->toArray();
            else
                $memberlist = Db::name('member')->field('id,path')->where('aid',$aid)->where('find_in_set('.$mid.',path)')->where(function ($query){
                    $query->whereNull('pid_origin')->whereOr('pid_origin','=',0);
                })->where('createtime','>',$down_level_time)->select()->toArray();
            //230909兼容原有回归会员 pid_origin=0的情况
            if($with_origin){
                $memberlistOrigin = Db::name('member')->field('id,path')->where('aid',$aid)->where('pid_origin',$mid)->where('createtime','>',$down_level_time)->select()->toArray();
            }
        }else{
            $levelid = str_replace('，',',',$levelid);
            if($with_new)
                $memberlist = Db::name('member')->field('id,path')->where('aid',$aid)->where('levelid','in',$levelid)->where('find_in_set('.$mid.',path)')->where('createtime','>',$down_level_time)->select()->toArray();
            else
                $memberlist = Db::name('member')->field('id,path')->where('aid',$aid)->where('levelid','in',$levelid)->where('find_in_set('.$mid.',path)')->where(function ($query){
                    $query->whereNull('pid_origin')->whereOr('pid_origin','=',0);
                })->where('createtime','>',$down_level_time)->select()->toArray();
            if (getcustom('plug_sanyang')) {
                $levelmids = Db::name('member_level_record')->where('aid', $aid)->where('levelid','in',$levelid)->column('mid');
                if(!empty($levelmids)) {
                    $levelmids = array_unique($levelmids);
                    $memberlist2 = Db::name('member')->field('id,path')->where('aid',$aid)->whereIn('id',$levelmids)->where('find_in_set('.$mid.',path)')->where('createtime','>',$down_level_time)->select()->toArray();
                    if(!empty($memberlist2)) {
                        $memberlist = array_merge($memberlist, $memberlist2);
                        $memberlist = array_unique($memberlist,SORT_REGULAR);
                    }
                }
            }
            if($with_origin){
                $memberlistOrigin = Db::name('member')->field('id,path')->where('aid',$aid)->where('levelid','in',$levelid)->where('pid_origin',$mid)->where('createtime','>',$down_level_time)->select()->toArray();
            }
        }
        foreach($memberlist as $member){
            if($levelnum == 0){
                $downmids[] = $member['id'];
            }else{
                $path = explode(',',$member['path']);
                $path = array_reverse($path);
                $key = array_search($mid,$path);
                if($key!==false && $key < $levelnum){
                    $downmids[] = $member['id'];
                }
            }
        }
        if($with_origin && $memberlistOrigin){
            foreach($memberlistOrigin as $member){
                $downmids[] = $member['id'];
            }
        }
        return $downmids;
    }

    //获取多少级以外的下级
    /**
     * @param $aid
     * @param $mid
     * @param $levelnum 层级
     * @param $levelid 指定等级id
     * @param $with_origin 脱离后作为原上级的升级条件，0不包含 1包含
     * @param $with_new 脱离后作为新上级的升级条件，0不包含 1包含
     * @param string $where 其他查询条件
     * @return array
     */
    public static function getdowntotalmids($aid,$mid,$levelnum=0,$levelid=0,$with_origin = 0,$with_new = 1,$where='1=1'){
        $downmids = [];
        if($levelid == 0){
            if($with_new){
                $memberlist = Db::name('member')->field('id,path')->where('aid',$aid)->where('find_in_set('.$mid.',path)')->where($where)->select()->toArray();
            } else{
                $memberlist = Db::name('member')->field('id,path')->where('aid',$aid)->where('find_in_set('.$mid.',path)')->where(function ($query){
                    $query->whereNull('pid_origin')->whereOr('pid_origin','=',0);
                })->where($where)->select()->toArray();
            }
            if($with_origin){
                $memberlistOrigin = Db::name('member')->field('id,path')->where('aid',$aid)->where('pid_origin',$mid)->where($where)->select()->toArray();
            }
        }else{
            $levelid = str_replace('，',',',$levelid);
            if($with_new)
                $memberlist = Db::name('member')->field('id,path')->where('aid',$aid)->where('levelid','in',$levelid)->where('find_in_set('.$mid.',path)')->where($where)->select()->toArray();
            else
                $memberlist = Db::name('member')->field('id,path')->where('aid',$aid)->where('levelid','in',$levelid)->where('find_in_set('.$mid.',path)')->where($where)->where(function ($query){
                    $query->whereNull('pid_origin')->whereOr('pid_origin','=',0);
                })->select()->toArray();
            if (getcustom('plug_sanyang')) {
                $levelmids = Db::name('member_level_record')->where('aid', $aid)->where('levelid','in',$levelid)->column('mid');
                if(!empty($levelmids)) {
                    $levelmids = array_unique($levelmids);
                    $memberlist2 = Db::name('member')->field('id,path')->where('aid',$aid)->whereIn('id',$levelmids)->where('find_in_set('.$mid.',path)')->select()->toArray();
                    if(!empty($memberlist2)) {
                        $memberlist = array_merge($memberlist, $memberlist2);
                        $memberlist = array_unique($memberlist,SORT_REGULAR);
                    }
                }
            }
            if($with_origin){
                $memberlistOrigin = Db::name('member')->field('id,path')->where('aid',$aid)->where('levelid','in',$levelid)->where('pid_origin',$mid)->where($where)->select()->toArray();
            }
        }
        foreach($memberlist as $member){
            if($levelnum == 0){
                $downmids[] = $member['id'];
            }else{
                $path = explode(',',$member['path']);
                $path = array_reverse($path);
                $key = array_search($mid,$path);
                if($key!==false && $key >= $levelnum){
                    $downmids[] = $member['id'];
                }
            }
        }
        if($with_origin && $memberlistOrigin){
            foreach($memberlistOrigin as $member){
                $downmids[] = $member['id'];
            }
        }
        return $downmids;
    }

    //获取多少级以内的下级 小区的(即除了人数最多的区的所有区)
    public static function getdownmids_xiao($aid,$mid,$levelnum=0,$levelid=0){
        $childList = Db::name('member')->field('id,path')->where('aid',$aid)->where('pid',$mid)->select()->toArray();

        $downmidsArr = [];
        foreach($childList as $cmember){
            $thisdownmids = self::getdownmids($aid,$cmember['id'],$levelnum,$levelid);
            if(!$thisdownmids){
                $thisdownmids = $cmember['id'];
            }else{
                $thisdownmids[] = $cmember['id'];
            }
            $downmidsArr[] = ['count'=>count($thisdownmids),'mids'=>$thisdownmids];
        }
        $counts = array_column($downmidsArr,'count');
        array_multisort($counts,SORT_DESC,$downmidsArr);

        $downmids = [];
        foreach($downmidsArr as $k=>$v){
            if($k > 0){
                $downmids = array_merge($downmids,$v['mids']);
            }
        }
        return $downmids;
    }

    //获取多少级以内的下级 去除业绩最高的一个
    public static function getdownmids_removemax($aid,$mid,$levelnum=0,$levelid=0){
        $childList = Db::name('member')->field('id,path')->where('aid',$aid)->where('pid',$mid)->select()->toArray();
        $downmidsArr = [];
        foreach($childList as $cmember){
            $thisdownmids = self::getdownmids($aid,$cmember['id'],$levelnum,$levelid);
            if(!$thisdownmids){
                $thisdownmids = [$cmember['id']];
            }else{
                $thisdownmids[] = $cmember['id'];
            }
            //\think\facade\Log::write($thisdownmids);
            $fxordermoney = 0 + Db::name('shop_order_goods')->where('status','in','1,2,3')->where('mid','in',$thisdownmids)->sum('totalprice');
            $downmidsArr[] = ['count'=>count($thisdownmids),'mids'=>$thisdownmids,'fxordermoney'=>$fxordermoney];
        }
        //\think\facade\Log::write($downmidsArr);
        $counts = array_column($downmidsArr,'fxordermoney');
        array_multisort($counts,SORT_DESC,$downmidsArr);
        //\think\facade\Log::write($downmidsArr);

        $downmids = [];
        foreach($downmidsArr as $k=>$v){
            if($k > 0){
                $downmids = array_merge($downmids,$v['mids']);
            }
        }
        return $downmids;
    }
    //获取团队的会员id集合 团队中有和他平级或超过他等级的就跳出
    private static $mids = [];
    public static function getteammids($aid,$mid,$deep=999,$levelids=[],$mids=[],$thisdeep=0){
        if($thisdeep == 0){
            self::$mids = [];
        }
        $thisdeep = $thisdeep+1;
        if($thisdeep > $deep) return self::$mids;
        $where = [];
        $where[] = ['aid','=',$aid];
        $where[] = ['pid','=',$mid];
        if(!empty($levelids)){
            $where[] = ['levelid','in',$levelids];
        }
        $dowmids = Db::name('member')->where($where)->column('id');
        if($dowmids){
            foreach($dowmids as $downmid){
                if(!in_array($downmid,self::$mids)){
                    self::$mids[] = $downmid;
                    $down2mids = self::getteammids($aid,$downmid,$deep,$levelids,$mids,$thisdeep);
                }
            }
        }
        return self::$mids;
    }
    //每条线查找到 某一个等级就停，不再继续查询
    private static $mids2 = [];
    public static function getteammidsByStoplevelid($aid,$mid,$deep=999,$mids2=[],$stoplevelid=0,$thisdeep=0,$levelnum=0,$find_mid=0){
        if($thisdeep == 0){
            self::$mids2 = [];
        }
        $thisdeep = $thisdeep+1;
        if($thisdeep > $deep) return self::$mids;
        $where = [];
        $where[] = ['aid','=',$aid];
        $where[] = ['pid','=',$mid];
        $dowmids = Db::name('member')->where($where)->column('id,levelid,path');
        if($dowmids){
            foreach($dowmids as $downmid){
                if($levelnum > 0 && $find_mid){
                    $path = explode(',',$downmid['path']);
                    $path = array_reverse($path);
                    $key = array_search($find_mid,$path);
                    if($key!==false && $key < $levelnum){
                        self::getteammidsByStoplevelid($aid,$downmid['id'],$deep,$mids2,$stoplevelid,$thisdeep,$levelnum,$find_mid);
                        continue;
                    }
                }
                //如果等级和 停止等级一样
                if(!in_array($downmid['id'],self::$mids2)){
                    self::$mids2[] = $downmid['id'];
                    if($downmid['levelid'] ==$stoplevelid ){
                        continue;
                    }
                    self::getteammidsByStoplevelid($aid,$downmid['id'],$deep,$mids2,$stoplevelid,$thisdeep,$levelnum,$find_mid);
                }
            }
        }
        return self::$mids2;
    }
    //获取团队的会员id集合 团队中有代理的就跳出
    private static $mids3 = [];
    public static function getteamareamids($aid,$mid,$deep=999,$mids3=[],$thisdeep=0){
        if($thisdeep == 0){
            self::$mids3 = [];
        }
        $thisdeep = $thisdeep+1;
        if($thisdeep > $deep) return self::$mids3;
        $where = [];
        $where[] = ['aid','=',$aid];
        $where[] = ['pid','=',$mid];
        $dowmids = Db::name('member')->where($where)->column('id');
        if($dowmids){
            foreach($dowmids as $downmid){
                if(!in_array($downmid,self::$mids3)){
                    $info = Db::name('member')->alias('m')
                    ->join('member_level l','l.id=m.levelid')
                    ->where('m.id',$downmid)
                    ->field('m.id,m.areafenhong,l.areafenhong areafenhong2')
                    ->find();
                    if(in_array($info['areafenhong'], [1,2,3]) || ($info['areafenhong']==0 && $info['areafenhong2']>0)){
                        continue;
                    }
                    self::$mids3[] = $downmid;
                    $down2mids = self::getteamareamids($aid,$downmid,$deep,$mids3,$thisdeep);
                }
            }
        }
        return self::$mids3;
    }
    
    /**
     * @param $aid
     * @param $path
     * @param $where
     * @param $sort true 由近到远；false 由远到近
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getParentsByPath($aid,$path,$where=[],$sort=true)
    {
        if(empty($path)) return [];
        $parentList = Db::name('member')->where('aid',$aid)->where('id','in',$path)->where($where)
            ->order(Db::raw('field(id,'.$path.')'))->select()->toArray();
        if($parentList){
            if($sort) $parentList = array_reverse($parentList);
            return $parentList;
        }
        return [];
    }

    public static function addHongbaoEverydayEdu($aid,$mid,$money,$remark,$ogid=0){
        if($money==0) return ;
        $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
        if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];

        $after = $member['hongbao_everyday_edu'] + $money;
        Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['hongbao_everyday_edu'=>$after]);

        $data = [];
        $data['aid'] = $aid;
        $data['mid'] = $mid;
        $data['money'] = $money;
        $data['ogid'] = $ogid;
        $data['createtime'] = time();
        $data['remark'] = $remark;
        Db::name('member_hbe_edu_record')->insert($data);
        return ['status'=>1,'msg'=>''];
    }

    public static function addHongbaoLog($aid,$mid,$money,$remark){

        if($money==0) return ;
        $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->find();
        if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];

        $afterTotal = $member['hongbao_ereryday_total'] + $money;

        $data = [];
        $data['aid'] = $aid;
        $data['mid'] = $mid;
        $data['money'] = $money;
        $data['after'] = $afterTotal;
        $data['createtime'] = time();
        $data['remark'] = $remark;
        Db::name('member_hbe_log')->insert($data);
        return ['status'=>1,'msg'=>''];
    }

    //加余额宝
    public static function addyuebaomoney($aid,$mid,$money,$remark,$type=0){

        if($money==0) return ;
        $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->find();
        if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];

        $after = $member['yuebao_money'] + $money;
        Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['yuebao_money'=>$after]);

        $data = [];
        $data['aid'] = $aid;
        $data['mid'] = $mid;
        $data['money'] = $money;
        $data['after'] = $after;
        $data['type']  = $type;
        $data['createtime'] = time();
        $data['remark'] = $remark;
        Db::name('member_yuebao_moneylog')->insert($data);

        return ['status'=>1,'msg'=>''];
    }
    //加元宝
    public static function addyuanbao($aid,$mid,$yuanbao,$remark){
        if(getcustom('pay_yuanbao')){
            if($yuanbao==0) return ;
            $yuanbao = round($yuanbao,2);
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];

            if($yuanbao < 0 && $member['yuanbao'] < $yuanbao*-1) return ['status'=>0,'msg'=>t('元宝').'不足'];

            $after = $member['yuanbao'] + $yuanbao;
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['yuanbao'=>$after]);

            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['yuanbao'] = $yuanbao;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            $data['type'] = 1;
            Db::name('member_yuanbaolog')->insert($data);
            return ['status'=>1,'msg'=>''];
        }
    }
    //加其他余额
    public static function addOtherMoney($aid,$mid,$type,$money,$remark){
        if(getcustom('other_money')){
            if($money==0) return ;
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];

            if($type == 'money2'){
                $type_name = t('余额2');
                $log_type  = 2;
                if($money < 0 && $member['money2'] < $money*-1){
                    return ['status'=>0,'msg'=>$type_name.'不足'];
                }
                $after = $member['money2'] + $money;
                $up = Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['money2'=>$after]);
            }else if($type == 'money3'){
                $type_name = t('余额3');
                $log_type  = 3;
                if($money < 0 && $member['money3'] < $money*-1){
                    return ['status'=>0,'msg'=>$type_name.'不足'];
                }
                $after = $member['money3'] + $money;
                $up = Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['money3'=>$after]);
            }else if($type == 'money4'){
                $type_name = t('余额4');
                $log_type  = 4;
                if($money < 0 && $member['money4'] < $money*-1){
                    return ['status'=>0,'msg'=>$type_name.'不足'];
                }
                $after = $member['money4'] + $money;
                $up = Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['money4'=>$after]);
            }else if($type == 'money5'){
                $type_name = t('余额5');
                $log_type  = 5;
                if($money < 0 && $member['money5'] < $money*-1){
                    return ['status'=>0,'msg'=>$type_name.'不足'];
                }
                $after = $member['money5'] + $money;
                $up = Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['money5'=>$after]);
            }else if($type == 'frozen_money'){
                $type_name = t('冻结金额');
                $log_type  = 0;
                if($money < 0 && $member['frozen_money'] < $money*-1){
                    return ['status'=>0,'msg'=>$type_name.'不足'];
                }
                $after = $member['frozen_money'] + $money;
                $up = Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['frozen_money'=>$after]);
            }else{
                return ['status'=>0,'msg'=>'操作类型错误'];
            }

            if(!$up){
                return ['status'=>0,'msg'=>'操作失败'];
            }

            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['type']= $log_type;
            $data['money'] = $money;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            Db::name('member_othermoneylog')->insert($data);

            $tmplcontent = [];
            $tmplcontent['first'] = '您的'.$type_name.'发生变动，变动金额：'.$money;
            $tmplcontent['remark'] = '点击进入查看~';
            $tmplcontent['keyword1'] = date('Y-m-d H:i'); //变动时间
            $tmplcontent['keyword2'] = $remark;  //变动类型
            $tmplcontent['keyword3'] = (string) round($money,2);  //变动金额
            $tmplcontent['keyword4'] = (string) round($after,2);  //当前余额
            $tmplcontentNew = [];
            $tmplcontentNew['thing2'] = $remark;//消费项目
            $tmplcontentNew['amount3'] = round($money,2);//消费金额
            $tmplcontentNew['amount4'] = round($after,2);//卡内余额
            $tmplcontentNew['time6'] = date('Y-m-d H:i'); //变动时间
            $rs = \app\common\Wechat::sendtmpl($aid,$mid,'tmpl_moneychange',$tmplcontent,m_url('pages/my/usercenter', $aid),$tmplcontentNew);
            return ['status'=>1,'msg'=>''];
        }
    }


    /**
     * 验证支付密码
     * @param $member 会员信息
     * @param $paypwd 输入的支付密码
     */
    public static function checkPayPwd($member,$paypwd){
        //设置过MD5加密的
        if($member['paypwd_rand']){
            if($member['paypwd'] == md5($paypwd.$member['paypwd_rand'])){
                return true;
            }else{
                return false;
            }
        }else{
            //未设置过MD5加密的
            if($member['paypwd'] == $paypwd){
                //验证通过后更新密码为加密后的
                $rand_str = make_rand_code(2,4);
                $paypwd = md5($paypwd.$rand_str);
                Db::name('member')->where('id',$member['id'])->update(['paypwd_rand'=>$rand_str,'paypwd'=>$paypwd]);
                return true;
            }else{
                return false;
            }
        }
    }
    //判断会员购车资金拿奖条件
    public static function goucheAble($mid){
        if(getcustom('teamfenhong_gouche')){
            $level_lists = Db::name('member_level')->column('gouche_down_num,gouche_levelid,gouche_bonus_total,sort','id');
            //判断会员是否满足拿奖条件
            $member_info = Db::name('member')->where('id',$mid)->field('id,levelid,gouche_able')->find();
            if(!$member_info['gouche_able']){
                //查询级别设置的拿奖条件
                $level_info = $level_lists[$member_info['levelid']];
                $level_limit = $level_lists[$level_info['gouche_levelid']]['sort'];//拿奖需要达到的级别
                //查询会员直推的前两个人
                $down_members = Db::name('member')->where('pid',$mid)
                    ->field('id,levelid')
                    ->order('createtime asc')
                    ->limit($level_info['gouche_down_num'])
                    ->select()->toArray();
                if(count($down_members)<$level_info['gouche_down_num']){
                    //dump($mid.'未达到购车基金资格1');
                    writeLog('会员'.$mid.'购车基金：直推下一级'.json_encode($down_members).'数量'.count($down_members).'<'.$level_info['gouche_down_num'].'未满足条件','gouche_bonus.log');
                    return false;
                }
                $gouche_able = 1;
                foreach($down_members as $k=>$down){
                    //判断级别是否达到条件
                    $level_sort = $level_lists[$down['levelid']]['sort'];
                    if($level_sort<$level_limit){
                        $gouche_able = 0;
                        writeLog('会员'.$mid.'购车基金：直推下一级会员'.$down['id'].'级别未达到'.$level_info['gouche_levelid'].'未满足条件','gouche_bonus.log');
                        break;
                    }
                    //判断累计收入是否达到条件
                    $down_commission = Db::name('member_commissionlog')->where('mid',$down['id'])->sum('commission');
                    $down_members[$k]['total_commission'] = $down_commission;
                    if(bccomp($down_commission,$level_info['gouche_bonus_total'],2)<0){
                        $gouche_able = 0;
                        writeLog('会员'.$mid.'购车基金：直推下一级会员'.$down['id'].'收入'.$down_commission.'<'.$level_info['gouche_bonus_total'].'未满足条件','gouche_bonus.log');
                        break;
                    }

                }
                //以上条件都能满足，更新会员购车基金资格
                if($gouche_able){
                    writeLog('会员'.$mid.'购车基金：直推下一级会员'.json_encode($down_members).'满足条件','gouche_bonus.log');
                    Db::name('member')->where('id',$mid)->update(['gouche_able'=>1]);
                    return true;
                }else{
                    writeLog('会员'.$mid.'购车基金：直推下一级会员'.json_encode($down_members).'未满足条件','gouche_bonus.log');
                    //dump($mid.'未达到购车基金资格2');
                    return false;
                }
            }
            writeLog('会员'.$mid.'购车基金：满足条件','gouche_bonus.log');
            return true;
        }
    }
    //判断会员旅游基金资格
    public static function lvyouAble($mid){
        if(getcustom('teamfenhong_lvyou')){
            $level_lists = Db::name('member_level')->column('lvyou_down_num,lvyou_levelid,lvyou_bonus_total,sort','id');
            $member_info = Db::name('member')->where('id',$mid)->field('id,levelid,lvyou_able')->find();
            if(!$member_info['lvyou_able']){
                //查询级别设置的拿奖条件
                $level_info = $level_lists[$member_info['levelid']];
                $level_limit = $level_lists[$level_info['lvyou_levelid']]['sort'];//拿奖需要达到的级别
                //查询会员直推的前两个人
                $down_members = Db::name('member')->where('pid',$mid)
                    ->field('id,levelid')
                    ->order('createtime asc')
                    ->limit($level_info['lvyou_down_num'])
                    ->select()->toArray();

                //查询下二级直推的前四个人
                $down_down_members = [];
                foreach($down_members as $down){
                    //查询下一级会员直推的前两个人
                    $down2_members = Db::name('member')->where('pid',$down['id'])
                        ->field('id,levelid')
                        ->order('createtime asc')
                        ->limit(2)
                        ->select()->toArray();
                    foreach($down2_members as $down2){
                        $down_down_members[] = $down2;
                    }
                    if(count($down_down_members)>=$level_info['lvyou_down_num']){
                        break;
                    }
                }
                if(count($down_down_members)<$level_info['lvyou_down_num']){
                    writeLog('会员'.$mid.'旅游基金：直推下二级'.json_encode($down_down_members).'数量'.count($down_down_members).'<'.$level_info['lvyou_down_num'].'未满足条件','lvyou_bonus.log');
                    return false;
                }
                $lvyou_able = 1;
                foreach($down_down_members as $k=>$down_down){
                    //判断级别是否达到条件
                    $level_sort = $level_lists[$down_down['levelid']]['sort'];
                    if($level_sort<$level_limit){
                        $lvyou_able = 0;
                        writeLog('会员'.$mid.'旅游基金：直推下二级会员'.$down_down['mid'].'级别'.$down_down['levelid'].'<'.$level_info['lvyou_levelid'].'未满足条件','lvyou_bonus.log');
                        break;
                    }
                    //判断累计收入是否达到条件
                    $down_commission = Db::name('member_commissionlog')->where('mid',$down_down['id'])->sum('commission');
                    $down_down_members[$k]['total_commission'] = $down_commission;
                    if(bccomp($down_commission,$level_info['lvyou_bonus_total'],2)<0){
                        $lvyou_able = 0;
                        writeLog('会员'.$mid.'旅游基金：直推下二级会员'.$down_down['mid'].'收入'.$down_commission.'<'.$level_info['lvyou_bonus_total'].'未满足条件','lvyou_bonus.log');
                        break;
                    }
                }
                //以上条件都能拿到，更新会员购车基金资格
                if($lvyou_able){
                    writeLog('会员'.$mid.'旅游基金：直推下二级会员'.json_encode($down_down_members).'满足条件','lvyou_bonus.log');
                    Db::name('member')->where('id',$mid)->update(['lvyou_able'=>1]);
                    return true;
                }else{
                    writeLog('会员'.$mid.'旅游基金：直推下二级会员'.json_encode($down_down_members).'未满足条件','lvyou_bonus.log');
                    return false;
                }
            }
            writeLog('会员'.$mid.'旅游基金：满足条件','lvyou_bonus.log');
            return true;
        }
    }

    public static function createMemberCode($aid,$mid)
    {
        if(getcustom('member_code')){
            $set = Db::name('member_code_set')->where('aid',$aid)->find();
            if($set['status'] == 1)
            {
                $last = Db::name('member')->where('aid',$aid)->where('member_code','<>','')->order('member_code','desc')->limit(1)->value('member_code');
                if($last){
                    $member_code = $last + 1;
                }else{
                    $member_code = $set['no_start'];
                }
                $member_code_img = createqrcode($member_code,'',$aid);
                Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['member_code'=>$member_code,'member_code_img'=>$member_code_img]);
                return ['status'=>1,'member_code'=>$member_code,'member_code_img'=>$member_code_img];
            }
            return ['status'=>0];
        }
    }

    /**
     * 自动降级
     */
    public function checkDownLevelCon($member,$level_data){
        if(getcustom('level_auto_down')){
            $is_down = 0;
            $date_start = $member['levelstarttime'];
            $date_end = $member['levelendtime'];
            //查询直推人数
            $is_down = 0;
            if($level_data['down_level_tjr']>0){
                $able_level_sort = Db::name('member_level')->where('id',$level_data['tjr_level_id'])->value('sort');
                $map = [];
                $map[] = ['sort','>=',$able_level_sort];
                $map[] = ['aid','=',$member['aid']];
                $able_level_ids = Db::name('member_level')->where($map)->column('id');
                $map = [];
                $map[] = ['pid','=',$member['id']];
                $map[] = ['createtime','between',[$date_start,$date_end]];
                $map[] = ['levelid','in',$able_level_ids];
                $tjnum = Db::name('member')->where($map)->count();

                if($tjnum<$level_data['down_level_tjr']){
                    $is_down = 1;
                    $desc = '直推人数'.$tjnum.',未达到'.$level_data['down_level_tjr'];
                }
            }
            //直推人数达到了，检测团队业绩
            if($is_down==0 && $level_data['down_level_teamyeji']>0){
                //团队业绩
                $yejiwhere = [];
                $yejiwhere[] = ['status','in','1,2,3'];
                $yejiwhere[] = ['createtime','between',[$date_start,$date_end]];
                $downmids = self::getteammids($member['aid'],$member['id']);
                $teamyeji = Db::name('shop_order_goods')->where('aid',$member['aid'])->where('mid','in',$downmids)->where($yejiwhere)->sum('real_totalprice');
                if($teamyeji<$level_data['down_level_teamyeji']){
                    $is_down = 1;
                    $desc = '团队业绩'.$teamyeji.',未达到'.$level_data['down_level_teamyeji'];
                }
            }
            if($level_data['down_level_tjr']<=0 && $level_data['down_level_teamyeji']<=0){
                //未设置检测条件，到期直接降级
                $is_down = 1;
                $desc = '级别到期';
            }
            return ['is_down'=>$is_down,'desc'=>$desc];

        }
    }

    //判断会员是否有团队收益的拿奖条件
    public static function teamshouyiAble($mid,$level_data){
        if(getcustom('teamfenhong_shouyi')){
            $order_money = Db::name('shop_order')
                ->where('mid','=',$mid)
                ->whereIn('status',[1,2,3])
                ->sum('totalprice');
            if($level_data['team_shouyi_ordermoney']>0 && $order_money<$level_data['team_shouyi_ordermoney']){
                writeLog('会员'.$mid.'团队收益：累计订单金额'.$order_money.'未达到'.$level_data['team_shouyi_ordermoney'],'teamfenhong_shouyi.log');
                return false;
            }
            writeLog('会员'.$mid.'团队收益：满足条件','teamfenhong_shouyi.log');
            return true;
        }
    }

    //信用额度钱包
    public static function addOverdraftMoney($aid,$mid,$money,$remark){
        if(getcustom('member_overdraft_money')) {
            if ($money == 0) return;
            $member = Db::name('member')->where('aid', $aid)->where('id', $mid)->lock(true)->find();
            if (!$member) return ['status' => 0, 'msg' => t('会员') . '不存在'];
            $after = $member['overdraft_money'] + $money;
            Db::name('member')->where('aid', $aid)->where('id', $mid)->update(['overdraft_money' => $after]);
            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['money'] = $money;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            Db::name('member_overdraft_moneylog')->insert($data);
            self::uplv($aid, $mid);
            Wechat::updatemembercard($aid, $mid);

            $tmplcontent = [];
            $tmplcontent['first'] = '您的'.t('信用额度').'发生变动，变动金额：' . $money;
            $tmplcontent['remark'] = '点击进入查看~';
            $tmplcontent['keyword1'] = date('Y-m-d H:i'); //变动时间
            $tmplcontent['keyword2'] = $remark;  //变动类型
            $tmplcontent['keyword3'] = (string)round($money, 2);  //变动金额
            $tmplcontent['keyword4'] = (string)round($after, 2);  //当前余额
            $tmplcontentNew = [];
            $tmplcontentNew['thing2'] = $remark;//消费项目
            $tmplcontentNew['amount3'] = round($money, 2);//消费金额
            $tmplcontentNew['amount4'] = round($after, 2);//卡内余额
            $tmplcontentNew['time6'] = date('Y-m-d H:i'); //变动时间
            $rs = \app\common\Wechat::sendtmpl($aid, $mid, 'tmpl_moneychange', $tmplcontent, m_url('pages/my/usercenter', $aid), $tmplcontentNew);
            return ['status' => 1, 'msg' => ''];
        }
        return ['status' => 0, 'msg' => 'failed'];
    }
    
    //线下优惠券直接升级 $member=》 领取的会员
    public static function xianxiaUpLevel($member,$persendnum,$form_mid=0){
        if(getcustom('coupon_xianxia_buy')){
            //1.根据优惠券数量进行升级 2.发放奖励
            $aid = $member['aid'];
            $newlv = $now_level = Db::name('member_level')->where('aid',$aid)->where('id',$member['levelid'])->find();
            $lvlist = Db::name('member_level')->where('aid',$aid)->where('cid', 0)->where('sort','>',$now_level['sort'])->order('sort,id')->select()->toArray();
            $isup = false;
            foreach($lvlist as $lv){
                //如果下个等级的优惠券数量  =  当前转入的数量 就进行升级
                if($persendnum >=$lv['up_get_couponnum'] && $lv['up_get_couponnum']>0){
                    $newlv =  $lv;
                    $isup = true;
                }
            }
            if($isup){
                if ($newlv['yxqdate'] > 0) {
                    $levelendtime = strtotime(date('Y-m-d')) + 86400 + 86400 * $newlv['yxqdate'];
                } else {
                    $levelendtime = 0;
                }
                //判断是否是升级到最高等级 且判断是直接升级
                $next_level = Db::name('member_level')->where('aid',$aid)->where('sort','>',$newlv['sort'])->order('sort asc')->find();
                //升级记录 
                $level_record = Db::name('member_levelup_order')->where('aid',$aid)->where('mid',$member['id'])->find();
                $is_zt_up = 0;
                if(!$next_level && !$level_record){
                     $is_zt_up = 1;
                }
                $order = [
                    'aid' => $aid,
                    'mid' => $member['id'],
                    'from_mid' => $form_mid,
                    'pid' => $member['pid'],
                    'levelid' => $newlv['id'],
                    'title' => '续费延期',
                    'totalprice' => 0,
                    'createtime' => time(),
                    'levelup_time' => time(),
                    'beforelevelid' => $now_level['id'],
                    'form0' => '类型^_^购买优惠券升级',
                    'platform' => platform,
                    'status' => 2
                ];
                Db::name('member_levelup_order')->insert($order);
                Db::name('member')->where('id',$member['id'])->update(['levelid' => $newlv['id'],'levelendtime' => $levelendtime]);
                if($member['pid']){
                    \app\common\Member::uplv($aid,$member['pid']);
                }
                //直推发放奖励                   
                if($newlv['up_give_parent_money'] > 0 && $member['pid'] && ($is_zt_up==1 || $next_level)) {
                    \app\common\Member::addcommission($aid, $member['pid'], $member['id'], $newlv['up_give_parent_money'], '直推奖');
                }
            }
            //发放每组优惠券奖励 推荐人链中 父级的最近团购和分公司
            $parent = Db::name('member')->where('aid',$aid)->field('id,levelid,path')->where('id',$member['pid'])->find();
            //查询关系链
            $path  =$parent['path'].','.$parent['id'];
            $parent_list = Db::name('member')->where('aid',$aid)->where('id','in',$path)->field('id,levelid')->select()->toArray();
            $parent_list = array_reverse($parent_list);
           
            //发放排序
            $sort = Db::name('member_level')->where('aid',$aid)->where('sort','>',$newlv['sort'])->order('sort asc')->where('isdefault','=',0)->column('id');
            if($newlv['xianxia_coupon_vip_tj'] && $member['pid']){
                $xianxia_coupon_tj  = json_decode($newlv['xianxia_coupon_vip_tj'],true);
                $jl_data = $xianxia_coupon_tj[$parent['levelid']]; 
                if($jl_data){
                    //循环关系链的父级的等级 是否有和设置中levelid 相同，相同  取出 佣金
                    $have_level = [];//已送佣金的等级，直送最近的，防止重复
                    foreach($parent_list as $key=>$val){
                        $nowlevelid =  $val['levelid'];
                        if($jl_data[$nowlevelid] && !in_array($nowlevelid,$have_level)){
                            $have_level[] = $nowlevelid;
                            $money = $jl_data[$val['levelid']];
                            $commission_money =  dd_money_format($money*  $persendnum);
                            if($commission_money > 0) {
                                $p_levelname =Db::name('member_level')->where('id',$val['levelid'])->value('name');
                                $remark = '团队'.$p_levelname.'('.$val['id'].')推荐'.$newlv['name'].'('.$member['id'].')奖励';
                                $give_mid = self::getXianxiaSortMid($parent_list,$sort);
                                if($give_mid ==0){
                                    \app\common\Member::addcommission($aid, $val['id'], $member['id'], $commission_money, $remark);
                                }else{
                                    self::addXianxiaCommissionLog($aid, $val['id'], $give_mid, $member['id'],$commission_money,$persendnum, $remark);
                                }
                            }
                        }
                    }
                }
            }
            
            $form_member = Db::name('member')->where('aid',$aid)->where('id',$form_mid)->find();
            $form_level = Db::name('member_level')->where('aid',$aid)->where('id',$form_member['levelid'])->find();
         
            if($form_level['xianxia_full'] && $form_member){
                $xianxia_full = json_decode($form_level['xianxia_full'],true);
                if($xianxia_full['levelid'] ==$form_member['levelid']){//推荐人的ID  = 设置的ID
                    $coupon_send = 0+Db::name('coupon_send')->where('aid',$aid)->where('from_mid',$form_member['id'])->count();
                    if($xianxia_full['num'] && $coupon_send > $xianxia_full['num']){
                        $commission_money =  dd_money_format($xianxia_full['money']*  $persendnum);
                        if($commission_money > 0){
                            \app\common\Member::addcommission($aid, $form_member['id'], $member['id'], $commission_money, '发券满'.$xianxia_full['num'].'组奖励');
                        }
                      
                    }
                } 
                
            }
        }
    }
    //线下优惠券 佣金应该谁发放
    public static function getXianxiaSortMid($parent_list,$sort){
        if(getcustom('coupon_xianxia_buy')) {
            $mid = 0;
            if($sort){
                foreach ($parent_list as $key=>$parent) {
                    if (in_array($parent['levelid'],$sort) && $key>0) {
                        $mid = $parent['id'];
                        break;
                    }
                }
            }
            return $mid;
        }
    }
    //添加线下优惠券 发放记录
    public static function addXianxiaCommissionLog($aid,$tomid,$give_mid,$frommid,$commission,$num,$remark=''){
        if(getcustom('coupon_xianxia_buy')) {
            $insert = [
                'aid' => $aid,
                'mid' => $give_mid,
                'tomid' => $tomid,
                'frommid' => $frommid,
                'commission' => $commission,
                'status' => 0,
                'remark' => $remark,
                'num' => $num,
                'createtime' => time()
            ];
            Db::name('xianxia_commission_log')->insert($insert);
        }
    }
    public static function xianxiaYeji($aid,$member,$yeji_reward_data,$month_start='',$month_end=''){
        //直推会员的支付金额
        $mids = Db::name('member')->where('aid',$aid)->where('pid',$member['id'])->column('id');
        $owhere = [];
        if($month_start && $month_end){
            $owhere[] = ['paytime','between',[$month_start,$month_end]];
        }
        $total_order_yeji =0+ Db::name('shop_order')->where('aid',$aid)->where('mid','in',$mids)->where('status','in',[1,2,3])->where($owhere)->sum('totalprice');
        //该用户转发的 =》 接收的用户的 等级 对应的商品的 会员价格，就是业绩
        $swhere = [];
        if($month_start && $month_end){
            $swhere[] = ['send_time','between',[$month_start,$month_end]];
        }
        $total_coupon_yeji = 0;   //(销售额)
        $my_total_coupon_yeji = 0;//按照自己的价格的销售额
        $sendcount = 0; //销售数量
        $shouyi = 0;    //收益额
        $coupon_send = Db::name('coupon_send')->where('aid',$aid)->where('from_mid',$member['id'])->where($swhere)->select()->toArray();
        foreach($coupon_send as $send){
            $total_coupon_yeji += $send['coupon_yeji'];
            $my_total_coupon_yeji += $send['from_coupon_yeji'];
            $shouyi +=$send['shouyi'];
            $sendcount++;
        }
        
        $total_yeji = dd_money_format($total_coupon_yeji + $total_order_yeji); //销售额 + 支付金额 = 总业绩
        $rewardkey = -1;
        foreach ($yeji_reward_data as $key => $reward){
            if($total_yeji >$reward['limit']){
                $rewardkey = $key;
            }
        }
        $get_commission = 0; //业绩奖励 
        if($yeji_reward_data[$rewardkey]){
            $get_commission = dd_money_format($yeji_reward_data[$rewardkey]['reward']/100 * $total_yeji);
        }
        
        
        //自己使用的也算
         $myuselist = Db::name('coupon_record')->alias('cr')
             ->join('coupon c','c.id = cr.couponid')
             ->where('cr.aid',$aid)->where('cr.mid',$member['id'])->where('cr.is_xianxia_buy',1)->where('status',1)->where('cr.from_mid','null')
             ->field('cr.*,c.productids')
             ->select()->toArray();
         $myuseryeji = 0;
        $myusecount = 0;
        foreach($myuselist as $record){
            $product_id =   explode(',',$record['productids']);
            $product = Db::name('shop_product')->where('aid',$aid)->where('id',$product_id[0])->find();
            if($product['lvprice']==1){
                $lvprice_data = json_decode($product['lvprice_data'],true);
                $myuseryeji += $lvprice_data[$member['levelid']];
            }else{
                $myuseryeji +=$product['sell_price'];
            }
            $myusecount++;
        }
        $total_coupon_yeji = $total_coupon_yeji + $myuseryeji;     
             
    
        //购买总数
        //目前我的优惠券数量
        $mycount =0+ Db::name('coupon_record')->where('aid',$aid)->where('mid',$member['id'])->where('is_xianxia_buy',1)->where('status',0)->count();
        
        //已发放下去的 （销售数）
        $mytoalcount =0+ Db::name('coupon_record')->where('aid',$aid)->where('mid',$member['id'])->where('is_xianxia_buy',1)->count();
        $total_count = $mytoalcount + $sendcount;
        $return =  [
            'totalyeji' => $total_yeji,//总业绩
            'order_yeji'  => $total_order_yeji,
            'coupon_yeji' => dd_money_format($total_coupon_yeji), //销售额，只出售优惠券额度
            'commission' => $get_commission,// 业绩奖励
            'shouyi' => $shouyi,          //收益额
            'mycount' => $mycount,        //目前剩余数量
            'sendcount' =>$sendcount + $myusecount,    //销售数
            'totalcount' => $total_count //总购买数
        ];
        return $return;
    }
    public function level_autodown_commission($aid,$member,$newlevelid){
        if(getcustom('member_level_down_commission')){
            $level_data = Db::name('member_level')->where('id',$newlevelid)->find();
            //var_dump($level_data);die;
            $data_u = [];
            $data_u['levelid'] = $newlevelid;
            if($level_data['yxqdate']>0){
                $data_u['levelendtime'] = $member['levelendtime']+86400 * $level_data['yxqdate'];
            }else{
                $data_u['levelendtime'] = 0;
            }
            $data_u['isauto_down']=1;
            $data_u['up_levelid']=$member['levelid'];
            $data_u['down_commission'] = $member['down_commission']+$member['totalcommission'];
            Db::name('member')->where('id', $member['id'])->update($data_u);
            
            //降级记录
            $order = [
                'aid' => $aid,
                'mid' => $member['id'],
                'from_mid' => $member['id'],
                'pid'=>$member['pid'],
                'levelid' => $newlevelid,
                'title' => '自动降级',
                'totalprice' => 0,
                'createtime' => time(),
                'levelup_time' => time(),
                'beforelevelid' => $member['levelid'],
                'form0' => '类型^_^' .'自动降级',
                'platform' => '',
                'status' => 2,
            ];
            Db::name('member_levelup_order')->insert($order);
        }
    }
    public function recovery_level($aid,$member){
        if(getcustom('member_level_down_commission')){
            $level_data = Db::name('member_level')->where('id',$member['up_levelid'])->find();
            $data_u = [];
            $data_u['levelid'] = $member['up_levelid'];
            if($level_data['yxqdate']>0){
                $data_u['levelendtime'] = $member['levelendtime']+86400 * $level_data['yxqdate'];
            }else{
                $data_u['levelendtime'] = 0;
            }
            $data_u['isauto_down'] = 0;
            $data_u['up_levelid'] = 0;
            Db::name('member')->where('id', $member['id'])->update($data_u);
            //升级记录
            $order = [
                'aid' => $member['aid'],
                'mid' => $member['id'],
                'from_mid' => $member['id'],
                'pid'=>$member['pid'],
                'levelid' =>  $member['levelid'],
                'title' => '自动升级',
                'totalprice' => 0,
                'createtime' => time(),
                'levelup_time' => time(),
                'beforelevelid' => $level_data['id'],
                'form0' => '类型^_^' .'恢复等级',
                'platform' => '',
                'status' => 2,
            ];
            Db::name('member_levelup_order')->insert($order);
        }
    }

    public function commission_to_score($sysset,$page=0,$limit=0,$last_mid=0,$commission_total=0){
        if(getcustom('commission_to_score',$sysset['aid'])){
            if($sysset['commission_to_score_status']!=1){
                return ['status'=>0,'msg'=>'未开启！'];
            }
            $score_weishu = $sysset['score_weishu']??0;
            $butie_num = 0;//补贴金额
            if($sysset['commission_to_score_type']==0){
                //按利润百分比计算补贴金额
                $where = [];
                $where[] = ['aid','=',$sysset['aid']];
                $where[] = ['status','in','1,2,3'];
                $where[] = ['refund_num','=',0];
                $e_time = strtotime(date('Y-m-d 00:00:00'));
                $s_time = $e_time-86400;
                $where[] = ['paytime','between',[$s_time,$e_time]];
                $orders = Db::name('shop_order_goods')->where($where)->field('sum(real_totalprice- cost_price * num) cost_price_total')->find();

                $butie_num = bcmul($orders['cost_price_total'],$sysset['commission_to_score_money']/100,2);
            }else{
                //按固定金额计算补贴金额
                $butie_num = $sysset['commission_to_score_money'];
            }
            if($butie_num<=0){
                return ['status'=>0,'msg'=>'补贴金额为0'];
            }

            $where = [];
            $where[] = ['aid','=',$sysset['aid']];
            $where[] = ['score','>',0];
            $where[] = ['id','>',$last_mid];
            //计算全网总佣金
            //$commission_total = Db::name('member')->where($where)->field('id,commission,score')->sum('score');
            $commission_total = dd_money_format($commission_total,$score_weishu);
            if($commission_total<0){
                if($butie_num<=0){
                    return ['status'=>0,'msg'=>'全网'.t('佣金',$sysset['aid']).'为0'];
                }
            }
            //查询会员，循环处理
            if($page>0 && $limit>0){
                $member_lists = Db::name('member')->where($where)->field('id,commission,score')->page(1,$limit)->order('id asc')->select()->toArray();
            }else{
                $member_lists = Db::name('member')->where($where)->field('id,commission,score')->order('id asc')->select()->toArray();
            }
            $sql = Db::getlastSql();
            writeLog('第'.$page.'页，'.$limit.',上一次最后一个会员ID'.$last_mid.',sql::'.$sql.'，共'.count($member_lists).'条','toscore.log');
            if(count($member_lists)<=0){
                return ['status'=>0,'msg'=>'全部处理完成！'];
            }
            $commission_to_score_bili = $sysset['commission_to_score_bili']>0?$sysset['commission_to_score_bili']:100;
            $sucnum = bcmul(($page-1),$limit);
            foreach($member_lists as $member){
                $sucnum ++;
                $last_mid = $member['id'];
                $num = bcmul(bcdiv($member['score'],$commission_total,4),$butie_num,$score_weishu);
                if($num<=0){
                    continue;
                }
                if($num>$member['score']){
                    $num = $member['score'];
                }
                $commission_num = bcmul($num,$commission_to_score_bili/100,2);
                if($num<=0 || $commission_num<=0){
                    continue;
                }
                //减少积分
                $res = self::addscore($sysset['aid'],$member['id'],-$num,'转入'.t('佣金',$sysset['aid']));
                //增加佣金
                $res = self::addcommission($sysset['aid'],$member['id'],0,$commission_num,t('积分',$sysset['aid']).'转入');
                //插入转换日志
                $log = [];
                $log['aid'] = $sysset['aid'];
                $log['mid'] = $member['id'];
                $log['commission_to_score_type'] = $sysset['commission_to_score_type'];
                $log['butie_num'] = $butie_num;
                $log['commission'] = $member['score'];
                $log['commission_total'] = $commission_total;
                $log['num'] = $num;
                $log['w_day'] = date('Ymd');
                $log['w_time'] = time();
                Db::name('commission_toscore_log')->insert($log);
            }
            if($sucnum<=0){
                return ['status'=>0,'msg'=>'全部处理完成！'];
            }
            return ['status'=>1,'msg'=>'操作成功！','sucnum'=>$sucnum,'last_mid'=>$last_mid];
        }
    }
    public function commission_to_score2($sysset,$page=0,$limit=0,$last_mid=0,$commission_total=0){
        if(getcustom('commission_to_score',$sysset['aid'])){
            //分仓库设置
            //查询积分范围所处设置
            $set_arr = Db::name('score_to_commission_set')
                ->where('aid',$sysset['aid'])
                ->where('status',1)
                ->column('*','id');
            if(empty($set_arr)){
                return ['status'=>0,'msg'=>'未设置分仓'];
            }
            $score_weishu = $sysset['score_weishu']??0;
            foreach($set_arr as $k=>$v){
                $score_total = Db::name('member')->where('aid',$sysset['aid'])->where('score>='.$v['min'].' and score<'.$v['max'])->sum('score');
                $score_total = dd_money_format($score_total,$score_weishu);
                $set_arr[$k]['score_total'] = $score_total?:0;
            }
            //按利润百分比计算补贴金额
            $where = [];
            $where[] = ['aid','=',$sysset['aid']];
            $where[] = ['status','in','1,2,3'];
            $where[] = ['refund_num','=',0];
            $e_time = strtotime(date('Y-m-d 00:00:00'));
            $s_time = $e_time-86400;
            $where[] = ['paytime','between',[$s_time,$e_time]];
            $orders = Db::name('shop_order_goods')->where($where)->field('sum(real_totalprice- cost_price * num) cost_price_total')->find();


            //查询会员，循环处理
            $where = [];
            $where[] = ['aid','=',$sysset['aid']];
            $where[] = ['score','>',0];
            $where[] = ['id','>',$last_mid];
            if($page>0 && $limit>0){
                $member_lists = Db::name('member')->where($where)->field('id,commission,score')->page(1,$limit)->order('id asc')->select()->toArray();
            }else{
                $member_lists = Db::name('member')->where($where)->field('id,commission,score')->order('id asc')->select()->toArray();
            }
            $sql = Db::getlastSql();
            writeLog('第'.$page.'页，'.$limit.',上一次最后一个会员ID'.$last_mid.',sql::'.$sql.'，共'.count($member_lists).'条','toscore.log');
            if(count($member_lists)<=0){
                return ['status'=>0,'msg'=>'全部处理完成！'];
            }
            $sucnum = bcmul(($page-1),$limit);
            foreach($member_lists as $member){
                $sucnum ++;
                $last_mid = $member['id'];
                //按分仓库发放
                $set = [];
                foreach($set_arr as $set_v){
                    if($member['score']>=$set_v['min'] && $member['score']<$set_v['max']){
                        $set = $set_v;
                        break;
                    }
                }

                if($set){
                    if($set['type']==0){
                        $butie_num = bcmul($orders['cost_price_total'],$set['butie']/100,2);
                    }else{
                        //按固定金额计算补贴金额
                        $butie_num = $set['butie'];
                    }

                    $num = bcmul(bcdiv($member['score'], $set['score_total'], 4), $butie_num, $score_weishu);
                    if ($num <= 0) {
                       continue;
                    }
                    if ($num > $member['score']) {
                        $num = $member['score'];
                    }
                    $commission_num = bcmul($num, $set['bili'] / 100, 2);
                    if ($num <= 0 || $commission_num <= 0) {
                        continue;
                    }
                    //减少积分
                    $res = self::addscore($sysset['aid'], $member['id'], -$num, '转入' . t('佣金', $sysset['aid']));
                    //增加佣金
                    $res = self::addcommission($sysset['aid'], $member['id'], 0, $commission_num, t('积分', $sysset['aid']) . '转入');
                    //插入转换日志
                    $log = [];
                    $log['aid'] = $sysset['aid'];
                    $log['mid'] = $member['id'];
                    $log['set_id'] = $set['id'];
                    $log['commission_to_score_type'] = $set['type'];
                    $log['butie_num'] = $butie_num;
                    $log['commission'] = $member['score'];
                    $log['commission_total'] = $set['score_total'];
                    $log['num'] = $num;
                    $log['w_day'] = date('Ymd');
                    $log['w_time'] = time();
                    Db::name('commission_toscore_log2')->insert($log);
                }


            }
            if($sucnum<=0){
                return ['status'=>0,'msg'=>'全部处理完成！'];
            }
            return ['status'=>1,'msg'=>'操作成功！','sucnum'=>$sucnum,'last_mid'=>$last_mid];
        }
    }

    public static function addfhcopies($aid,$mid,$copies=0,$remark='',$frommid=0){
        if(getcustom('fenhong_jiaquan_bylevel')) {
            if ($copies == 0) return;
            $copies = intval($copies);
            $member = Db::name('member')->where('aid', $aid)->where('id', $mid)->lock(true)->find();
            if (!$member) return ['status' => 0, 'msg' => t('会员') . '不存在'];

            $after = $member['fhcopies'] + $copies;
            Db::name('member')->where('aid', $aid)->where('id', $mid)->update(['fhcopies' => $after]);
            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['copies'] = $copies;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            Db::name('member_fhcopies_log')->insert($data);
            return ['status' => 1, 'msg' => ''];
        }
    }

    //查询团队订单和小区团队订单数量
    public static function getTeamOrderNum($aid,$mid,$levelnum=0,$levelid=0,$down_level_time=0){
        if(getcustom('up_level_teamorder')){
            //统计团队订单数量
            $childrens = Db::name('member')->where('aid',$aid)->where('pid',$mid)->column('id');
            $order_arr = [];
            foreach($childrens as $chid_mid){
                $downmids = self::getdownmids($aid,$chid_mid,$levelnum-1,$levelid);
                if($downmids){
                    $downmids[] = $chid_mid;
                }else{
                    $downmids = [$chid_mid];
                }
                $map = [];
                $map[] = ['aid','=',$aid];
                $map[] = ['mid','in',$downmids];
                $map[] = ['status','in',[1,2,3]];
                $map[] = ['createtime','>',$down_level_time];
                $teamorder_count = Db::name('shop_order')->where($map)->count();
                $order_arr[] = $teamorder_count?:0;
            }
            $max = max($order_arr);
            $teamorder_count = array_sum($order_arr);
            $teamorder_small_count = bcsub($teamorder_count,$max);
            return [
                'teamorder_count' => $teamorder_count?:0,
                'teamorder_small_count' => $teamorder_small_count?:0
            ];
        }

    }

    //分销补贴按期发放
    public static function commission_butie($aid,$ids=[]){
        //查询所有待发放补贴
        $now_time = time();
        if($ids){
            $lists = Db::name('member_commission_butie')->where('aid',$aid)->where('status',0)->whereIn('id',$ids)->select()->toArray();
        }else{
            $lists = Db::name('member_commission_butie')->where('aid',$aid)->where('status',0)->where('next_send_time','<=',$now_time)->select()->toArray();
        }
        if(!$lists){
            return true;
        }
        foreach($lists as $v){
            $can_send = 0;
            if($v['send_circle']==0){
                $can_send = 1;
            }else if($now_time>$v['next_send_time']){
                $can_send = 1;
            }
            if(!$can_send){
                continue;
            }
            $send_bonus = bcdiv($v['commission'],$v['fx_butie_circle'],2);
            //发放补贴
            $send_circle = $v['send_circle'] + 1;
            if($send_circle>=$v['fx_butie_circle']){
                //最后一期发放剩余所有的
                $send_bonus = $v['remain'];
            }
            $remark = '订单'.$v['orderid'].'产生的'.t('分销补贴',$aid).'第'.$send_circle.'期发放';
            \app\common\Member::addcommission($aid,$v['mid'],$v['frommid'],$send_bonus,$remark,1,'commission_butie');
            //更新记录
            $data_u = [];
            $data_u['have_send'] = bcadd($v['have_send'],$send_bonus,2);
            $data_u['remain'] = bcsub($v['commission'],$data_u['have_send'],2);
            $data_u['send_circle'] = $send_circle;
            if($send_circle>=$v['fx_butie_circle'] || $data_u['remain']<=0){
                $data_u['status'] = 1;
            }
            //判断下一次发放时间
            if($v['fx_butie_type']==1){
                //按周
                $send_day = $v['fx_butie_send_week'];
                $s_time = strtotime('+1 week last monday');
                $send_time = $s_time+$send_day*86400-86400;
            }else{
                //按月
                $s_time = strtotime(date('Y-m-t 23:59:59'))+86400;
                $t = date('t',$s_time);
                $send_day = $v['fx_butie_send_day'];
                if($t<$send_day){
                    //有可能设置31号，但是本月只到30号，取本月最后一天
                    $send_day = $t;
                }
                $send_time = strtotime(date('Y-m-'.$send_day,$s_time));
            }
            $data_u['next_send_time'] = $send_time;
            Db::name('member_commission_butie')->where('id',$v['id'])->update($data_u);
            //插入发放明细
            $data_log = [];
            $data_log['aid'] = $aid;
            $data_log['mid'] = $v['mid'];
            $data_log['pid'] = $v['id'];
            $data_log['send_num'] = $send_bonus;
            $data_log['send_circle'] = $send_circle;
            $data_log['send_time'] = time();
            Db::name('member_commission_butie_log')->insert($data_log);
        }
        return true;
    }

    //上级佣金按级差发放
    public static function parent_commission($leveldata,$parent,$mid,$aid){
        if(getcustom('member_levelup_parentcommission_jicha')){
            $levelup_parentcommission = json_decode($leveldata['levelup_parentcommission'],true);
            if($parent['pid']){
                $pids = $parent['path'];
                $parentList = Db::name('member')->where('id','in',$pids)->order(Db::raw('field(id,'.$pids.')'))->select()->toArray();
                $parentList = array_reverse($parentList);
                $jicha = $levelup_parentcommission[$parent['levelid']];
                foreach($parentList as $parent2){
                    $commission = $levelup_parentcommission[$parent2['levelid']];
                    $real_commission = bcsub($commission,$jicha,2);
                    $real_commission = $real_commission>0?$real_commission:0;
                    if($real_commission<=0){
                        continue;
                    }
                    $jicha = $commission;
                    \app\common\Member::addcommission($aid, $parent2['id'],$mid, $real_commission, '团队会员升级奖励');
                }
            }
            return true;
        }

    }

    //释放通证
    public static function release_tongzheng($sysset){
        if(getcustom('product_givetongzheng',$sysset['aid'])) {
            $aid = $sysset['aid'];
            $release_bili = $sysset['tongzheng_release_bili'];
            $lists = Db::name('tongzheng_order_log')->where('aid', $aid)->where('status', '=', 0)->select()->toArray();
            if(!$lists){
                return true;
            }
            foreach ($lists as $order) {
                $order_status = Db::name('shop_order')->where('id',$order['orderid'])->value('status');
                if(!$order_status || !in_array($order_status,[1,2,3])){
                    Db::name('tongzheng_order_log')->where('id',$order['id'])->update(['status'=>2]);
                    continue;
                }
                //计算释放数量
                $release_num = bcmul($order['tongzheng'], $release_bili / 100, 3);
                if ($release_num <= 0) {
                    continue;
                }
                $status = 0;
                if($release_num>$order['remain']){
                    $release_num = $order['remain'];
                    $status = 1;
                }
                $remark = date('Y-m-d H:i:s') . '订单id'.$order['orderid'].t('通证') . '释放';
                //增加通证
                \app\common\Member::addtongzheng($aid, $order['mid'], $release_num, $remark);
                //记录释放记录
                $log = [];
                $log['aid'] = $aid;
                $log['mid'] = $order['mid'];
                $log['pid'] = $order['id'];
                $log['tongzheng'] = $order['tongzheng'];
                $log['release_bili'] = $release_bili;
                $log['release_num'] = $release_num;
                $log['createtime'] = time();
                $log['remark'] = $remark;
                Db::name('tongzheng_release_log')->insert($log);
                //更新释放数量
                $data_u = [];
                $data_u['remain'] = bcsub($order['remain'],$release_num,3);
                $data_u['release_num'] = bcadd($order['release_num'],$release_num,3);
                $data_u['release_time'] = time();
                $data_u['status'] = $status;
                Db::name('tongzheng_order_log')->where('id',$order['id'])->update($data_u);
            }
            return true;
        }
    }

    //公排网滑落
    public static function net_slide($pid,$mid,$levelid){
        $downmember = Db::name('member')->where('id',$mid)->find();
        if(getcustom('network_slide',$downmember['aid'])){
            $member = Db::name('member')->where('id',$pid)->field('id,aid,levelid,pid_origin')->find();
            if($member['pid_origin']>0){
                //链动裂变过来的会有不滑落，不用处理
                return ['status'=>0,'msg'=>'链动裂变过来的会有不滑落'];
            }
            $aid = $member['aid'];
            $level_info = Db::name('member_level')
                ->where('aid',$aid)
                ->where('id',$member['levelid'])
                ->field('net_down_levelid,net_down_num,net_down_next_levelid,slide_down_levelid,slide_down_team')
                ->find();
            if($level_info['net_down_num']<=0){
                //未设置人数代表不滑落，不用处理
                return ['status'=>0,'msg'=>'滑落失败，未设置人数代表不滑落'];
            }
            if($levelid!=$level_info['net_down_next_levelid']){
                //当前升级id不是设置的滑落等级，不用处理
                return ['status'=>0,'msg'=>'滑落失败，当前升级id不是设置的滑落等级'];
            }
            //查找下级人数
            $net_down_levelid = explode(',',$level_info['net_down_levelid']);
            $net_down_num = Db::name('member')->where('aid',$aid)->where('pid',$pid)->where('id','<>',$mid)->where('levelid','in',$net_down_levelid)->count();
            if($net_down_num<$level_info['net_down_num']){
                //直推下级人数不满足滑落条件，不用处理
                return ['status'=>0,'msg'=>'滑落失败，直推下级人数不满足滑落条件'];
            }
            //查找滑落给予下级，顺序：链动脱离的人—自己直推的人—链动裂变过来的人—公排滑落下来的人
            $slide_member = self::getslidedown($downmember['aid'],$pid,$level_info['slide_down_levelid'],$level_info['slide_down_team'],$mid);
            if(!$slide_member){
                return ['status'=>0,'msg'=>'滑落失败，未查找到下级会员'];
            }
            //更改下级的推荐人
            $updatem = ['id'=>$mid,'pid'=>$slide_member['id'],'change_pid_time'=>time(),'is_slide'=>1];
            $updatem['pid_origin'] = $downmember['pid']?:'';
            $updatem['path_origin'] = $downmember['path']?:'';
            \app\model\Member::edit($aid,$updatem);//todo
            if(getcustom('network_slide_down_max')){
                Db::name('member')->where('id',$slide_member['id'])->inc('slide_num',1)->update();
            }
            $insertLog = ['aid'=>$aid,'mid'=>$downmember['id'],'pid'=>$slide_member['id'],'createtime'=>time()];
            $insertLog['pid_origin'] =  $updatem['pid_origin'];
            $insertLog['path_origin'] = $updatem['path_origin'];
            $insertLog['remark'] = '公排网滑落';
            Db::name('member_pid_changelog')->insert($insertLog);
            //滑落后触发上级升级
            self::uplv($aid,$slide_member['id']);
            return ['status'=>1,'msg'=>'滑落成功'];
        }
    }
    //获取滑落给予下级会员
    public static function getslidedown($aid,$pid,$levelids,$slide_down_team=0,$mid=0){
        if(getcustom('network_slide',$aid)){
            $levelids = explode(',',$levelids);
            foreach($levelids as $levelid){
                $slide_down_max = 0;
                if(getcustom('network_slide_down_max')){
                    $slide_down_max = Db::name('member_level')->where('id',$levelid)->value('slide_down_max');
                }
                if($slide_down_team==2){
                    //仅滑落给未脱离的直推或间推,优先找直推
                    $where = [];
                    $where[] = ['pid','=',$pid];
                    $where[] = ['levelid','=',$levelid];
                    $where[] = ['id','<>',$mid];
                    if($slide_down_max>0){
                        $where[] = ['slide_num','<',$slide_down_max];
                    }
                    $member = Db::name('member')->where($where)->find();
                    if($member){
                        return $member;
                    }
                    //直推不符合条件找间推
                    $pid2 = Db::name('member')->where('pid',$pid)
                        ->column('id');
                    if(!$pid2){
                        return [];
                    }
                    $where = [];
                    $where[] = ['pid','in',$pid2];
                    $where[] = ['levelid','=',$levelid];
                    $where[] = ['id','<>',$mid];
                    if($slide_down_max>0){
                        $where[] = ['slide_num','<',$slide_down_max];
                    }
                    $member = Db::name('member')->where($where)->find();
                    if($member){
                        return $member;
                    }else{
                        return [];
                    }
                }
                //查找链动脱离的人
                if($slide_down_team!=2){
                    $where = [];
                    $where[] = ['pid_origin','=',$pid];
                    $where[] = ['levelid','=',$levelid];
                    $where[] = ['is_slide','=',0];
                    $where[] = ['id','<>',$mid];
                    if($slide_down_max>0){
                        $where[] = ['slide_num','<',$slide_down_max];
                    }
                    $member = Db::name('member')->where($where)->find();
                    if($member){
                        return $member;
                    }
                    if($slide_down_team==1){
                        //仅滑落链动脱离的人
                        continue;
                    }
                }

                //查找自己直推的人
                $where = [];
                $where[] = ['levelid','=',$levelid];
                $where[] = ['is_slide','=',0];
                $where[] = ['id','<>',$mid];
                if($slide_down_max>0){
                    $where[] = ['slide_num','<',$slide_down_max];
                }
                $member = Db::name('member')->where('pid',$pid)
                    ->where('pid_origin=0 or pid_origin is null')
                    ->where($where)->find();
                if($member){
                    return $member;
                }
                //查找链动裂变过来的人
                $where = [];
                $where[] = ['pid','=',$pid];
                $where[] = ['pid_origin','>',0];
                $where[] = ['levelid','=',$levelid];
                $where[] = ['is_slide','=',0];
                $where[] = ['id','<>',$mid];
                if($slide_down_max>0){
                    $where[] = ['slide_num','<',$slide_down_max];
                }
                $member = Db::name('member')->where($where)->find();
                if($member){
                    return $member;
                }
                //查找公排滑落下来的人
                $where = [];
                $where[] = ['pid','=',$pid];
                $where[] = ['pid_origin','>',0];
                $where[] = ['levelid','=',$levelid];
                $where[] = ['is_slide','=',1];
                $where[] = ['id','<>',$mid];
                if($slide_down_max>0){
                    $where[] = ['slide_num','<',$slide_down_max];
                }
                $member = Db::name('member')->where($where)->find();
                if($member){
                    return $member;
                }
            }
            return [];
        }
    }
    public static function membercard_jiangli($aid,$mid,$membercard){
        if(getcustom('membercard_custom')){
            if($membercard['givemoney']>0){
                \app\common\Member::addmoney($aid,$mid,$membercard['givemoney'],'会员卡开卡赠送');
            }
            if($membercard['givescore']>0){
                \app\common\Member::addscore($aid,$mid,$membercard['givescore'],'会员卡开卡赠送');
            }
            if($membercard['coupon_ids']){
                $coupon_ids = explode(',',$membercard['coupon_ids']);
                $couponids = '';
                foreach ($coupon_ids as $couponid){
                    //查看是否发放完成 本次活动优惠券已派送完毕
                    $coupon = Db::name('coupon')->where('aid',$aid)->where('id',$couponid)->find();
                    if($coupon['stock']<=0){
                        $couponids.= $couponid;
                        continue;
                    }
                    \app\common\Coupon::send($aid,$mid,$couponid);
                }
            }
            //查询推荐人
            $member = Db::name('member')->field('id,pid,nickname,headimg')->where('id',$mid)->find();
            //赠送上级
            if($member['pid']){
                $pmember = Db::name('member')->where('id',$member['pid'])->find();
                if($pmember){
                    $share = Db::name('membercard_sharelog')->where('aid',$aid)->where('mid',$mid)->where('pid',$member['pid'])->where('card_id',$membercard['card_id'])->find();
                    if(!$share){

                        if($membercard['parent_givemoney']>0){
                            \app\common\Member::addmoney($aid,$member['pid'],$membercard['parent_givemoney'],'推荐开卡赠送');
                        }
                        if($membercard['parent_givescore']>0){
                            \app\common\Member::addscore($aid,$member['pid'],$membercard['parent_givescore'],'推荐开卡赠送');
                        }
                        if($membercard['parent_coupon_ids']){
                            $coupon_ids = explode(',',$membercard['parent_coupon_ids']);
                            $couponids = '';
                            $count = 0;
                            foreach ($coupon_ids as $couponid){
                                //查看是否发放完成 本次活动优惠券已派送完毕
                                $coupon = Db::name('coupon')->where('aid',$aid)->where('id',$couponid)->find();
                                if($coupon['stock']<=0){
                                    $couponids.= $couponid;
                                    continue;
                                }
                                $count++;
                                \app\common\Coupon::send($aid,$member['pid'],$couponid);
                            }
                        }
                        $record = Db::name('membercard_record')->where('aid',$aid)->where('mid',$member['pid'])->where('card_id',$membercard['card_id'])->find();
                        $data = [];
                        $data['totalmoney'] = $record['totalmoney']+$membercard['parent_givemoney'];
                        $data['totalscore'] = $record['totalscore']+$membercard['parent_givescore'];
                        $data['totalcoupon'] = $record['totalcoupon']+$count;
                        Db::name('membercard_record')->where('aid',$aid)->where('mid',$member['pid'])->where('card_id',$membercard['card_id'])->update($data);
                        $log = [];
                        $log['aid'] = $aid;
                        $log['mid'] = $mid;
                        $log['record_id'] = $record['id'];
                        $log['createtime'] = time();
                        $log['title'] = $membercard['title'];
                        $log['givemoney'] = $membercard['givemoney'];
                        $log['givescore'] = $membercard['givescore'];
                        $log['give_couponids'] = $membercard['coupon_ids'];
                        $log['pid'] = $member['pid'];
                        if($member['pid']>0){
                            $log['parent_givemoney'] = $membercard['parent_givemoney'];
                            $log['parent_givescore'] = $membercard['parent_givescore'];
                            $log['parent_couponids'] = $membercard['parent_coupon_ids'];
                            $log['card_id'] = $membercard['card_id'];
                        }
                        $log['nickname'] = $member['nickname'];
                        $log['headimg'] = $member['headimg'];
                        Db::name('membercard_sharelog')->insert($log);
                    }
                }
            }
        }
    }
    //排名分红记录
    public static function addpaimingfenhong($aid,$mid,$value,$remark,$type=0,$orderid=0){
        if(getcustom('shop_paiming_fenhong')){
            if($value==0) return ;
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];
            $after = $member['paiming_fenhong_money'] + $value;
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['paiming_fenhong_money'=>$after]);
            

            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['money'] = $value;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            $data['type'] = $type;
            $data['orderid'] = $orderid;
            Db::name('member_paiming_fenhong_log')->insert($data);
            return ['status'=>1,'msg'=>''];
        }
    }

    //增加会员佣金上限
    //$in_type 1平台修改 0自己获得
    public static function addcommissionmax($aid,$mid,$value,$remark,$channel='',$orderid=0,$in_type=0){
        $add_commission_max = getcustom('add_commission_max',$aid)?:0;
        if(getcustom('member_commission_max')){
            if($value==0) return ;
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];

            if($value < 0 && $member['commission_max'] < $value*-1) return ['status'=>0,'msg'=>t('佣金上限').'不足'];

            $after = $member['commission_max'] + $value;
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['commission_max'=>$after]);

            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['value'] = $value;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            $data['channel'] = $channel;
            $data['orderid'] = $orderid;
            if($add_commission_max){
                $data['in_type'] = $in_type?:0;
                if($value>0){
                    //增加佣金上限时根据选择的类型
                    if($in_type==1){
                        Db::name('member')->where('aid',$aid)->where('id',$mid)->inc('commission_max_plate',$value)->update();
                    }else{
                        Db::name('member')->where('aid',$aid)->where('id',$mid)->inc('commission_max_self',$value)->update();
                    }
                }else{
                    //减少佣金上限时优先减会员自己的佣金上限
                    $abs_value = abs($value);
                    $dec_self = $abs_value;
                    $dec_plate = 0;
                    if($abs_value>$member['commission_max_self']){
                        $dec_self = $member['commission_max_self']?:0;
                        $dec_plate = bcsub($abs_value , $dec_self,2);
                    }
                    Db::name('member')->where('aid',$aid)->where('id',$mid)->dec('commission_max_self',$dec_self)->update();
                    Db::name('member')->where('aid',$aid)->where('id',$mid)->dec('commission_max_plate',$dec_plate)->update();
                }
            }
            $log_id = Db::name('member_commissionmax_log')->insertGetId($data);
            if($value<0){
                if(getcustom('consumer_value_add',$aid) && getcustom('add_commission_max',$aid)){
                    $consumer_set = Db::name('consumer_set')->where('aid',$aid)->find();
                    if($consumer_set['maximum_set_type']==0){
                        //绿色积分封顶额度跟随佣金上限时，扣除绿色积分要同时扣除封顶额度
                        self::addmaximum($aid,$mid,$value,$remark,'member_commissionmax_log',$log_id);
                    }
                }
            }
            return ['status'=>1,'msg'=>''];
        }
    }
    //增加绿色积分
    public static function addgreenscore($aid,$mid,$value,$remark,$channel='',$orderid=0,$no_cal_price=0,$maximum_num=0,$jilu_id=0){
        if(getcustom('consumer_value_add',$aid)){
            if($value==0) return ;
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];


            if($value < 0 && $member['green_score'] < $value*-1) return ['status'=>0,'msg'=>t('绿色积分').'不足'];

            $set = Db::name('consumer_set')->where('aid',$aid)->find();
            $old_greenscore_price = $set['green_score_price'];
            if($value>0 && getcustom('green_score_new',$aid)){
                $plate_value = bcadd($set['green_score_total'],$value,2);
                if($plate_value>$set['max_green_score'] && $set['max_green_score']>0){
                    $value = bcsub($set['max_green_score'],$set['green_score_total'],2);
                }
                if($value<=0){
                    return ;
                }
            }
            if($value < 0 && $set['green_score_total'] < $value*-1) return ['status'=>0,'msg'=>t('绿色积分').'不足'];

            $after = bcadd($member['green_score'] , $value,2);
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['green_score'=>$after]);
            $green_score_total = bcadd($set['green_score_total'],$value,2);
            if(!$no_cal_price){
                if($green_score_total>0){
                    $green_price = bcdiv($set['bonus_pool_total'],$green_score_total,4);
                    writeLog('绿色积分变动：当期奖金池'.$set['bonus_pool_total'].'当前绿色积分'.$green_score_total.'当前绿色积分单价'.$green_price,'green_price_log');
                    if($green_price<$set['min_price']){
                        $green_price = $set['min_price'];
                    }
                }else{
                    $green_price = $set['min_price'];
                }
            }else{
                $green_price = $set['green_score_price']?:$set['min_price'];
            }
            //更新绿色积分单笔明细
            if(getcustom('greenscore_max',$aid)){
                if(!$maximum_num && $value>0){
                    $maximum_num = bcmul(bcmul($value,$set['green_score_price'],2),$set['maximum_set'],2);
                }
            }
            $res = \app\custom\GreenScore::greenscore_jilu($aid,$mid,$value,$remark,$orderid,$jilu_id,$maximum_num,$green_price);
            if($res['status']){
                $jilu_datas = $res['jilu_data'];
            }
            $new_after = $member['green_score'];
            foreach($jilu_datas as $jilu_data){
                $new_after = bcadd($new_after,$jilu_data['value'],2);
                $data = [];
                $data['aid'] = $aid;
                $data['mid'] = $mid;
                $data['value'] = $jilu_data['value'];
                $data['after'] = $new_after;
                $data['green_score_price'] = $green_price?:0;
                $data['createtime'] = time();
                $data['remark'] = $remark;
                $data['channel'] = $channel;
                $data['orderid'] = $orderid;
                $data['remain'] = $jilu_data['value']>0?$jilu_data['value']:0;
                if(getcustom('greenscore_max',$aid) && $jilu_data['value']>0){
                    //封顶额度
                    if(!$maximum_num){
                        $maximum_num = bcmul(bcmul($jilu_data['value'],$set['green_score_price'],2),$set['maximum_set'],2);
                    }
                    $data['maximum_num'] = $maximum_num??0;
                    self::addmaximum($aid,$mid,$maximum_num,$remark,$channel,$orderid);
                }

                $data['jilu_id'] = $jilu_data['jilu_id'];
                Db::name('member_greenscore_log')->insert($data);
            }

            Db::name('consumer_set')->where('aid',$aid)->inc('green_score_total',$value)->update(['green_score_price'=>$green_price]);
            if(!$no_cal_price){
                $set = Db::name('consumer_set')->where('aid',$aid)->find();
                \app\custom\GreenScore::autoWithdraw($aid,$set);
            }
            $green_score_total = bcadd($set['green_score_total'],$value,2);
            if($green_score_total<=0){
                //绿色积分总数量为0时清空“奖金池余额”重置绿色积分价格
                $set = Db::name('consumer_set')->where('aid',$aid)->find();
                $rs = \app\common\Member::addbonuspool($set['aid'], 0, -$set['bonus_pool_total'], t('绿色积分', $set['aid']) . '为0释放扣除', '', 0, 0);
                Db::name('consumer_set')->where('aid', $set['aid'])->update(['green_score_total' => 0, 'bonus_pool_total' => 0, 'green_score_price' => $set['min_price']]);
            }

            //记录绿色积分增幅记录 用于前端pagesB/my/greenscoreWithdraw页面展示
            $dif_price = bcsub($green_price,$old_greenscore_price,4);
            $log = [];
            $log['aid'] = $aid;
            $log['mid'] = $mid;
            $log['bonus_pool'] = 0;
            $log['bonus_pool_total'] = $set['bonus_pool_total'];
            $log['green_score'] = $value;
            $log['green_score_total'] = bcadd($set['green_score_total'],$value,2);
            $log['green_score_price'] = $green_price;
            $log['channel'] = $channel;
            $log['orderid'] = $orderid;
            $log['dif_price'] = $dif_price;
            $log['remark'] = $remark;
            $log['createtime'] = time();
            Db::name('greenscore_price_log')->insert($log);
            return ['status'=>1,'msg'=>''];
        }
    }
    //增加封顶额度
    public static function addmaximum($aid,$mid,$value,$remark,$channel='',$orderid=0,$no_cal_price=0,$log_id=0){
        if(getcustom('consumer_value_add',$aid)){
            if($value==0) return ;
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];
            if($value < 0 && $member['maximum'] < $value*-1) return ['status'=>0,'msg'=>t('绿色积分').'不足'];

            $after = $member['maximum'] + $value;
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['maximum'=>$after]);
            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['value'] = $value;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            $data['channel'] = $channel;
            $data['orderid'] = $orderid;
            $data['remain'] = $value>0?$value:0;
            Db::name('member_maximum_log')->insert($data);
            if($value<0){
                if($log_id){
                    Db::name('member_greenscore_jilu')->where('id',$log_id)->inc('dec_maximum',abs($value))->update();
                }else{
                    $where = [];
                    $where['aid'] = $aid;
                    $where['mid'] = $mid;
                    //根据记录顺序扣除记录中的剩余积分，不足的顺延扣除下一条
                    $log_list = Db::name('member_greenscore_jilu')->where($where)->where('(maximum_num-dec_maximum)','>',0)->order('id asc')->select()->toArray();
                    //根据记录顺序扣除记录中的封顶额度，不足的顺延扣除下一条
                    $money = abs($value);
                    foreach($log_list as $log){
                        $remain_maximum = bcsub($log['maximum_num'],$log['dec_maximum'],2);
                        if($money<$remain_maximum){
                            //直接减掉
                            Db::name('member_greenscore_jilu')->where('id',$log['id'])
                                ->inc('dec_maximum',$money)
                                ->update();
                        }else{
                            Db::name('member_greenscore_jilu')->where('id',$log['id'])->inc('dec_maximum',$remain_maximum)->update();
                            self::addgreenscore($aid, $mid, $log['value'] * -1, t('绿色积分', $aid) . '自动扣除', '', '', 1,0,$log['id']);
                        }
                        $money = bcsub($money,$remain_maximum,2);
                        if($money<=0){
                            break;
                        }
                    }
                }
            }
            return ['status'=>1,'msg'=>''];
        }
    }
    //增加激活币
    public static function addactivecoin($aid,$mid,$value,$remark,$channel='',$orderid=0){
        if(getcustom('active_coin',$aid)){
            if($value==0) return ;
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];
            if($value < 0 && $member['active_coin'] < $value*-1) return ['status'=>0,'msg'=>t('激活币').'不足'];
            if($value < 0 && $member['active_coin'] < $value*-1) return ['status'=>0,'msg'=>t('激活币').'不足'];
            $after = $member['active_coin'] + $value;
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['active_coin'=>$after]);
            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['value'] = $value;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            $data['channel'] = $channel;
            $data['orderid'] = $orderid;
            Db::name('member_activecoin_log')->insert($data);
            //自动下单
            //$remain = bcsub($after,$member['active_coin_order'],1);
            $remain = $after;
            $coin_set = Db::name('active_coin_set')->where('aid',$aid)->find();
            if($remain>=$coin_set['auto_order_num']){
                $num = intval(floor(bcdiv($remain,$coin_set['auto_order_num'],2)));
                if($num<=0){
                    $num = $num+1;
                }
                $pro_id = 0;
                if($member['activecoin_fugou_proid']){
                    $pro_id = $member['activecoin_fugou_proid'];
                    $product = Db::name('shop_product')->where('id',$pro_id)->find();
                    if($product['sell_price']>$remain){
                        $pro_id = 0;
                    }else{
                        $num = intval(floor(bcdiv($remain,$product['sell_price'],2)));
                        if($num<=0){
                            $num = $num+1;
                        }
                    }
                }
                if(!$pro_id && $coin_set['default_auto_proid']){
                    $pro_id = $coin_set['activecoin_fugou_proid'];
                    if($product['sell_price']>$remain){
                        $pro_id = 0;
                    }else{
                        $num = intval(floor(bcdiv($remain,$product['sell_price'],2)));
                        if($num<=0){
                            $num = $num+1;
                        }
                    }
                }
                if(!$pro_id){
                    //自动下单产品id
                    $auto_order_proids = $coin_set['auto_order_proids'];
                    $can_buy_proids = [];//可以购买的产品
                    if($auto_order_proids){
                        $auto_order_proids = explode(',',$auto_order_proids);
                        $products = Db::name('shop_product')->where('id','in',$auto_order_proids)->select()->toArray();
                        foreach($products as $k=>$v){
                            if($product['sell_price']>$remain){
                                continue;
                            }
                            $gettj = explode(',',$v['gettj']);
                            if(!$gettj || in_array('-1',$gettj) || in_array($member['levelid'],$gettj)){
                                $can_buy_proids[] = $v['id'];
                            }
                        }
                        $pro_id = $can_buy_proids[array_rand($can_buy_proids)];
                        $product = Db::name('shop_product')->where('id',$pro_id)->find();
                        $num = intval(floor(bcdiv($remain,$product['sell_price'],2)));
                        if($num<=0){
                            $num = $num+1;
                        }
                    }
                }
                if($pro_id){
                    $res = self::autoOrder($aid,$mid,$pro_id,$num);
                    if($res['status']){
                        $product = Db::name('shop_product')->where('id', $pro_id)->find();
                        $value = bcmul($product['sell_price'],$num,2);
                        $after = bcsub($after,$value,2);
                        $data = [];
                        $data['aid'] = $aid;
                        $data['mid'] = $mid;
                        $data['value'] = -$value;
                        $data['after'] = $after;
                        $data['createtime'] = time();
                        $data['remark'] = '自动下单扣除';
                        $data['channel'] = $channel;
                        $data['orderid'] = $res['oid'];
                        Db::name('member_activecoin_log')->insert($data);
                        Db::name('member')->where('aid',$aid)->where('id',$mid)->inc('active_coin_order',$coin_set['auto_order_num']*$num)->update(['active_coin'=>$after]);
                    }
                }
            }
            return ['status'=>1,'msg'=>''];
        }
    }
    public static function autoOrder($aid,$mid,$pro_id,$num=1){
        if(getcustom('active_coin',$aid)) {
            $product = Db::name('shop_product')->where('id', $pro_id)->find();
            $guige = Db::name('shop_guige')->where('proid', $pro_id)->find();
            if($guige['stock']<$num){
                return ['status'=>0,'msg'=>'库存不足'];
            }
            $sysset = Db::name('admin_set')->where('aid', $aid)->find();
            if (getcustom('consumer_value_add')) {
                $give_green_score = 0; //奖励绿色积分 确认收货后赠送
                $give_green_score2 = 0; //奖励绿色积分 付款后赠送
                $give_bonus_pool = 0; //奖励绿色积分 确认收货后赠送
                $give_bonus_pool2 = 0; //奖励绿色积分 付款后赠送
                $consumer_set = Db::name('consumer_set')->where('aid', $aid)->find();
                $green_score_price = $consumer_set['green_score_price'] > $consumer_set['min_price'] ? $consumer_set['green_score_price'] : $consumer_set['min_price'];
            }
            if (getcustom('consumer_value_add',$aid)) {
                $can_give_green_score = 1;
                if($consumer_set['fwtype']==2){//指定商品可用
                    $productids = explode(',',$consumer_set['productids']);
                    if(!in_array($product['id'],$productids)){
                        $can_give_green_score = 0;
                    }
                }

                if($consumer_set['fwtype']==1){//指定类目可用
                    $categoryids = explode(',',$consumer_set['categoryids']);
                    $cids = explode(',',$product['cid']);
                    $clist = Db::name('shop_category')->where('pid','in',$categoryids)->select()->toArray();
                    foreach($clist as $vc){
                        $categoryids[] = $vc['id'];
                        $cate2 = Db::name('shop_category')->where('pid',$vc['id'])->find();
                        $categoryids[] = $cate2['id'];
                    }
                    if(!array_intersect($cids,$categoryids)){
                        $can_give_green_score = 0;
                    }
                }
                if($can_give_green_score){
                    if ($guige['give_green_score'] <= 0) {
                        //$guige['give_green_score'] = bcmul($guige['sell_price'],$consumer_set['green_score_bili']/100,2);
                        $guige['give_green_score'] = bcdiv(bcmul($guige['sell_price'], $consumer_set['green_score_bili'] / 100, 4), $green_score_price, 2);
                    } else {
                        $guige['give_green_score'] = bcdiv($guige['give_green_score'], $green_score_price, 2);
                    }
                    if ($guige['give_bonus_pool'] <= 0) {
                        $guige['give_bonus_pool'] = bcmul($guige['sell_price'], $consumer_set['bonus_pool_bili'] / 100, 2);
                    }
                    if ($consumer_set['reward_time'] == 0) {
                        $give_green_score += $guige['give_green_score'] * $num; //奖励绿色积分 确认收货后赠送
                        $give_bonus_pool += $guige['give_bonus_pool'] * $num; //放入奖金池 确认收货后赠送
                    } else {
                        $give_green_score2 += $guige['give_green_score'] * $num; //奖励绿色积分 确认收货后赠送
                        $give_bonus_pool2 += $guige['give_bonus_pool'] * $num; //放入奖金池 确认收货后赠送
                    }
                }
            }

            $ordernum = \app\common\Common::generateOrderNo($aid);
            $orderdata = [];
            $orderdata['aid'] = $aid;
            $orderdata['mid'] = $mid;
            $orderdata['bid'] = $product['bid'] ?: 0;
            $orderdata['ordernum'] = $ordernum;
            $orderdata['title'] = $product['name'];

            $address = Db::name('member_address')->where('mid', $mid)->order('isdefault desc')->find();
            $orderdata['linkman'] = $address['name'];
            $orderdata['tel'] = $address['tel'];
            $orderdata['area'] = $address['province'] . $address['city'] . $address['district'];;
            $orderdata['area2'] = $address['province'] ? $address['province'] . ',' . $address['city'] . ',' . $address['district'] : '';
            $orderdata['address'] = $address['address'];
            $orderdata['totalprice'] = $product['sell_price'] * $num;
            $orderdata['product_price'] = $product['sell_price'];
            $orderdata['leveldk_money'] = 0;  //会员折扣
            $orderdata['scoredk_money'] = 0;    //积分抵扣
            $orderdata['scoredkscore'] = 0;    //抵扣掉的积分
            $orderdata['freight_price'] = 0; //运费
            $orderdata['message'] = '';
            $orderdata['freight_text'] = '';
            $orderdata['freight_id'] = '';
            $orderdata['freight_type'] = $product['freighttype']?:1;
            $orderdata['mdid'] = 0;
            $orderdata['platform'] = 'admin';
            $orderdata['hexiao_code'] = random(16);
            $orderdata['hexiao_qr'] = createqrcode(m_url('admin/hexiao/hexiao?type=shop&co=' . $orderdata['hexiao_code']));
            $orderdata['status'] = 1;
            $orderdata['paytype'] = 0;
            $orderdata['createtime'] = time();
            $orderdata['paytime'] = time();
            $remark = t('激活币') . '自动下单';

            $orderdata['remark'] = $remark;
            $orderdata['givescore'] = 0;
            $orderdata['givescore2'] = 0;
            if (getcustom('consumer_value_add')) {
                $orderdata['give_green_score'] = $give_green_score;
                $orderdata['give_bonus_pool'] = $give_bonus_pool;
                $orderdata['give_green_score2'] = $give_green_score2;
                $orderdata['give_bonus_pool2'] = $give_bonus_pool2;
            }
            $orderid = Db::name('shop_order')->insertGetId($orderdata);
            $ogdata = [];
            $ogdata['aid'] = $aid;
            $ogdata['bid'] = $product['bid'];
            $ogdata['mid'] = $mid;
            $ogdata['orderid'] = $orderid;
            $ogdata['ordernum'] = $orderdata['ordernum'];
            $ogdata['proid'] = $product['id'];
            $ogdata['name'] = $product['name'];
            $ogdata['pic'] = $guige['pic'] ? $guige['pic'] : $product['pic'];
            $ogdata['procode'] = $product['procode'];
            $ogdata['barcode'] = $product['barcode'];
            $ogdata['ggid'] = $guige['id'];
            $ogdata['ggname'] = $guige['name'];
            $ogdata['cid'] = $product['cid'];
            $ogdata['num'] = $num;
            $ogdata['cost_price'] = $guige['cost_price'];
            $ogdata['sell_price'] = $guige['sell_price'];
            $ogdata['totalprice'] = $num * $guige['sell_price'];
            $ogdata['real_totalprice'] = $ogdata['totalprice'];
            $ogdata['status'] = 1;
            $ogdata['createtime'] = time();
            if (getcustom('consumer_value_add',$aid)) {
                $can_give_green_score = 1;
                if($consumer_set['fwtype']==2){//指定商品可用
                    $productids = explode(',',$consumer_set['productids']);
                    if(!in_array($product['id'],$productids)){
                        $can_give_green_score = 0;
                    }
                }

                if($consumer_set['fwtype']==1){//指定类目可用
                    $categoryids = explode(',',$consumer_set['categoryids']);
                    $cids = explode(',',$product['cid']);
                    $clist = Db::name('shop_category')->where('pid','in',$categoryids)->select()->toArray();
                    foreach($clist as $vc){
                        $categoryids[] = $vc['id'];
                        $cate2 = Db::name('shop_category')->where('pid',$vc['id'])->find();
                        $categoryids[] = $cate2['id'];
                    }
                    if(!array_intersect($cids,$categoryids)){
                        $can_give_green_score = 0;
                    }
                }
                if($can_give_green_score){
                    if ($guige['give_green_score'] <= 0) {
                        //$guige['give_green_score'] = bcdiv(bcmul($guige['sell_price'],$consumer_set['green_score_bili']/100,4),$consumer_set['green_score_price'],2);

                        $guige['give_green_score'] = bcdiv(bcmul($guige['sell_price'], $consumer_set['green_score_bili'] / 100, 4), $green_score_price, 2);
                    } else {
                        $guige['give_green_score'] = bcdiv($guige['give_green_score'], $green_score_price, 2);
                    }
                    if ($guige['give_bonus_pool'] <= 0) {
                        $guige['give_bonus_pool'] = bcmul($guige['sell_price'], $consumer_set['bonus_pool_bili'] / 100, 2);
                    }
                    if ($consumer_set['reward_time'] == 0) {
                        $ogdata['give_green_score'] = $guige['give_green_score']; //奖励绿色积分 确认收货后赠送
                        $ogdata['give_bonus_pool'] = $guige['give_bonus_pool']; //放入奖金池 确认收货后赠送
                    } else {
                        $ogdata['give_green_score2'] = $guige['give_green_score']; //奖励绿色积分 确认收货后赠送
                        $ogdata['give_bonus_pool2'] = $guige['give_bonus_pool']; //放入奖金池 确认收货后赠送
                    }
                }
            }
            if ($product['fenhongset'] == 0) { //不参与分红
                $ogdata['isfenhong'] = 2;
            }
            $ogid = Db::name('shop_order_goods')->insertGetId($ogdata);

            //分销数据
            //计算佣金的商品金额
            $commission_totalprice = $ogdata['totalprice'];
            if($sysset['fxjiesuantype']==1){ //按成交价格
                $commission_totalprice = $ogdata['totalprice'];
            }
            if($sysset['fxjiesuantype']==2){ //按销售利润
                $commission_totalprice = $ogdata['totalprice'] - $guige['cost_price'] * $num;
            }
            if($commission_totalprice < 0) $commission_totalprice = 0;
            $istc1 = 0; //设置了按单固定提成时 只将佣金计算到第一个商品里
            $istc2 = 0;
            $istc3 = 0;
            $isfg  = 0;
            $member = Db::name('member')->where('id',$mid)->find();
            if(!getcustom('fenxiao_manage',$aid)){
                $sysset['fenxiao_manage_status'] = 0;
            }
            if($sysset['fenxiao_manage_status']){
                $commission_data = \app\common\Fenxiao::fenxiao_jicha($sysset,$member,$product,$num,$commission_totalprice);
            }else{
                $commission_data = \app\common\Fenxiao::fenxiao($sysset,$member,$product,$num,$commission_totalprice,$isfg,$istc1,$istc2,$istc3);
            }
            $ogupdate = [];
            $ogupdate['parent1'] = $commission_data['parent1']??0;
            $ogupdate['parent2'] = $commission_data['parent2']??0;
            $ogupdate['parent3'] = $commission_data['parent3']??0;
            $ogupdate['parent4'] = $commission_data['parent4']??0;
            $ogupdate['parent1commission'] = $commission_data['parent1commission']??0;
            $ogupdate['parent2commission'] = $commission_data['parent2commission']??0;
            $ogupdate['parent3commission'] = $commission_data['parent3commission']??0;
            $ogupdate['parent4commission'] = $commission_data['parent4commission']??0;
            $ogupdate['parent1score'] = $commission_data['parent1score']??0;
            $ogupdate['parent2score'] = $commission_data['parent2score']??0;
            $ogupdate['parent3score'] = $commission_data['parent3score']??0;
            //20250626新增 平级奖独立记录
            if(getcustom('commission_parent_pj',$aid)){
                $ogupdate['parent_pj1'] = $commission_data['parent_pj1']??0;
                $ogupdate['parent_pj2'] = $commission_data['parent_pj2']??0;
                $ogupdate['parent_pj3'] = $commission_data['parent_pj3']??0;
                $ogupdate['parent1commission_pj'] = $commission_data['parent1commission_pj']??0;
                $ogupdate['parent2commission_pj'] = $commission_data['parent2commission_pj']??0;
                $ogupdate['parent3commission_pj'] = $commission_data['parent3commission_pj']??0;
            }
            $istc1 = $commission_data['istc1']??0;
            $istc2 = $commission_data['istc2']??0;
            $istc3 = $commission_data['istc3']??0;
            if($ogupdate){
                Db::name('shop_order_goods')->where('id',$ogid)->update($ogupdate);
            }

            if($product['commissionset']!=4){
                if($ogupdate['parent1'] && ($ogupdate['parent1commission']>0 || $ogupdate['parent1score']>0)){
                    $data_c = ['aid'=>$aid,'mid'=>$ogupdate['parent1'],'frommid'=>$mid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent1commission'],'score'=>$ogupdate['parent1score'],'remark'=>t('下级').'购买商品奖励','createtime'=>time()];
                    Db::name('member_commission_record')->insert($data_c);
                }
                if($ogupdate['parent2'] && ($ogupdate['parent2commission']>0 || $ogupdate['parent2score']>0)){
                    $data_c = ['aid'=>$aid,'mid'=>$ogupdate['parent2'],'frommid'=>$mid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent2commission'],'score'=>$ogupdate['parent2score'],'remark'=>t('下二级').'购买商品奖励','createtime'=>time()];
                    Db::name('member_commission_record')->insert($data_c);
                }
                if($ogupdate['parent3'] && ($ogupdate['parent3commission']>0 || $ogupdate['parent3score']>0)){
                    $data_c = ['aid'=>$aid,'mid'=>$ogupdate['parent3'],'frommid'=>$mid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent3commission'],'score'=>$ogupdate['parent3score'],'remark'=>t('下三级').'购买商品奖励','createtime'=>time()];
                    Db::name('member_commission_record')->insert($data_c);
                }
                if($ogupdate['parent4'] && ($ogupdate['parent4commission']>0)){
                    $remark = '持续推荐奖励';
                    if(getcustom('commission_parent_pj_stop',$aid)){
                        $remark = '平级奖';
                    }
                    Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$ogupdate['parent4'],'frommid'=>$mid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent4commission'],'score'=>0,'remark'=>$remark,'createtime'=>time()]);
                }
                if(getcustom('commission_parent_pj',$aid)) {
                    if ($ogupdate['parent_pj1'] && ($ogupdate['parent1commission_pj'] > 0)) {
                        $remark = '平级一级奖励';
                        $data_c = ['aid' => $aid, 'mid' => $ogupdate['parent_pj1'], 'frommid' => $mid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent1commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                        Db::name('member_commission_record')->insert($data_c);
                    }
                    if ($ogupdate['parent_pj2'] && ($ogupdate['parent2commission_pj'] > 0)) {
                        $remark = '平级二级奖励';
                        $data_c = ['aid' => $aid, 'mid' => $ogupdate['parent_pj2'], 'frommid' => $mid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent2commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                        Db::name('member_commission_record')->insert($data_c);
                    }
                    if ($ogupdate['parent_pj3'] && ($ogupdate['parent3commission_pj'] > 0)) {
                        $remark = '平级三级奖励';
                        $data_c = ['aid' => $aid, 'mid' => $ogupdate['parent_pj3'], 'frommid' => $mid, 'orderid' => $orderid, 'ogid' => $ogid, 'type' => 'shop', 'commission' => $ogupdate['parent3commission_pj'], 'score' => 0, 'remark' => $remark, 'createtime' => time()];
                        Db::name('member_commission_record')->insert($data_c);
                    }
                }
            }

            Db::name('shop_guige')->where('aid', $aid)->where('id', $guige['id'])->update(['stock' => Db::raw("stock-$num"), 'sales' => Db::raw("sales+$num")]);
            Db::name('shop_product')->where('aid', $aid)->where('id', $product['id'])->update(['stock' => Db::raw("stock-$num"), 'sales' => Db::raw("sales+$num")]);
            if(getcustom('yx_liandong',$aid)){
                //新链动活动
                $og_list = Db::name('shop_order_goods')->where('orderid',$orderid)->select()->toArray();
                $pid = input('pid');
                foreach($og_list as $og){
                    \app\custom\Liandong::add_active_record($aid,$mid,$orderid,$og['id'],$og['proid'],$pid);
                }
            }
            if(getcustom('yx_network_help',$aid)){
                //新链动活动
                $og_list = Db::name('shop_order_goods')->where('orderid',$orderid)->select()->toArray();
                $pid = input('pid');
                foreach($og_list as $og){
                    \app\custom\NetworkHelp::add_active_record($aid,$mid,$orderid,$og['id'],$og['proid'],$pid);
                }

            }
            \app\model\Payorder::shop_pay($orderid);
            //发放分红
            \app\common\Order::order_create_done($aid,$orderid,'shop');

            return ['status' => true, 'oid' => $orderid];
        }
    }
    //增加奖金池
    public static function addbonuspool($aid,$mid,$value,$remark,$channel='',$orderid=0,$no_cal_price=0,$green_score=0){
        if(getcustom('consumer_value_add',$aid)){
            if($value==0) return ;
            $set = Db::name('consumer_set')->where('aid',$aid)->find();

            if($value < 0 && $set['bonus_pool_total'] < $value*-1) return ['status'=>0,'msg'=>t('奖金池').'不足'];

            $after = $set['bonus_pool_total'] + $value;
            if(!$no_cal_price) {
                if ($set['green_score_total'] > 0) {
                    $green_price = bcdiv($after, $set['green_score_total'], 4);
                    writeLog('绿色积分变动：当期奖金池'.$after.'当前绿色积分'.$set['green_score_total'].'当前绿色积分单价'.$green_price,'green_price_log');
                    if ($green_price < $set['min_price']) {
                        $green_price = $set['min_price'];
                    }
                } else {
                    $green_price = $set['min_price'];
                }
            }else{
                $green_price = $set['green_score_price'];
            }
            $dif_price = bcsub($green_price,$set['green_score_price'],4);
            Db::name('consumer_set')->where('aid',$aid)->inc('bonus_pool_total',$value)->update(['green_score_price'=>$green_price]);

            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['value'] = $value;
            $data['after'] = $after;
            $data['green_score_price'] = $green_price?:0;
            $data['dif_price'] = $dif_price;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            $data['channel'] = $channel;
            $data['orderid'] = $orderid;
            if(getcustom('greenscore_max',$aid)){
                $data['green_score'] = $green_score;
                $data['green_score_total'] = $set['green_score_total'];
            }
            Db::name('admin_bonuspool_log')->insert($data);
            //记录绿色积分增幅记录 用于前端pagesB/my/greenscoreWithdraw页面展示
            $log = [];
            $log['aid'] = $aid;
            $log['mid'] = $mid;
            $log['bonus_pool'] = $value;
            $log['bonus_pool_total'] = $after;
            $log['green_score'] = 0;
            $log['green_score_total'] = $set['green_score_total'];
            $log['green_score_price'] = $green_price;
            $log['channel'] = $channel;
            $log['orderid'] = $orderid;
            $log['dif_price'] = $dif_price;
            $log['remark'] = $remark;
            $log['createtime'] = time();
            Db::name('greenscore_price_log')->insert($log);
            return ['status'=>1,'msg'=>''];
        }
    }
    
    //独立排队返利
    public static function duliQueue($aid,$member,$level_data){
        if(getcustom('yx_queue_duli_queue',$aid)) {
            $set = Db::name('queue_free_set')->where('aid', $aid)->where('bid', 0)->find();
            if ($set['duli_queue_status']) {
                //1、当前等级是否开启独立排队 ,且没有进行的独立排队
                $queue_fee = Db::name('queue_free')->where('aid', $aid)->where('status', 0)->where('teamid', $member['id'])->find();
                $duli_levelid = Db::name('queue_free_set')->where('aid', $aid)->where('bid', 0)->value('duli_queue_levelid');
                $duli_queue_levelid = $duli_levelid ? explode(',', $duli_levelid) : [];
                Log::write([
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'msg' => '升级独立排队,已存在',
                    '$queue_fee' => $queue_fee,
                    '$duli_queue_levelid' => $duli_queue_levelid
                ]);
                if (in_array($member['levelid'], $duli_queue_levelid) && !$queue_fee) {
                    //2、查找是否排队，如果排队自己排在队伍最前 ，查找下级，把伞下进行重新独立排序
                    $downmids = \app\common\Member::getdownmids($aid, $member['id']);
                    $downmids[] = $member['id'];//包含自己的排队
                    Log::write([
                        'file' => __FILE__,
                        'line' => __LINE__,
                        'msg' => '升级独立排队',
                        '$downmids' => $downmids
                    ]);
                    $child_queue = [];
                    if ($downmids) {
                        $child_queue = Db::name('queue_free')->where('aid', $aid)->where('status', 0)->where('mid', 'in', $downmids)->order('queue_no asc,id asc')->select()->toArray();
                        //如果downmids中的会员存在独立排队，他的伞不再加入当前独立排队，
                        foreach ($child_queue as $ck => $cv) {
                            if ($cv['teamid'] > 0 && in_array($cv['teamid'], $downmids)) {
                                unset($child_queue[$ck]);
                            }
                        }
                    }
                    Log::write([
                        'file' => __FILE__,
                        'line' => __LINE__,
                        'msg' => '升级独立排队$child_queue',
                        '$child_queue' => $child_queue
                    ]);
                    //如果自身存在
                    $queue_no = 1;
                    if ($child_queue) {
                        foreach ($child_queue as $key => $val) {
                            Db::name('queue_free')->where('id', $val['id'])->update(['queue_no' => $queue_no, 'teamid' => $member['id']]);
                            $queue_no += 1;
                        }
                    }
                }
            }
        }
    }

    //增加服务费
    public static function addServiceFee($aid,$mid,$serviceFee,$remark=''){
        if(getcustom('product_service_fee',$aid)) {
            if($serviceFee==0) return ;
            $serviceFee = round($serviceFee,2);
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];

            if($serviceFee < 0 && $member['service_fee'] < $serviceFee*-1) return ['status'=>0,'msg'=>t('服务费').'不足'];

            $after = $member['service_fee'] + $serviceFee;
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['service_fee'=>$after]);

            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['service_fee'] = $serviceFee;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            $data['type'] = 1;
            Db::name('member_servicefee_log')->insert($data);
            return ['status'=>1,'msg'=>''];
        }
    }

    //释放绿色积分
    public static function release_green_score($set){
        if(getcustom('consumer_value_add', $set['aid'])) {
            $set_member_commission_max = 0;
            if (getcustom('member_commission_max', $set['aid'])) {
                //佣金上限
                $set_member_commission_max = Db::name('admin_set')->where('aid', $set['aid'])->value('member_commission_max');
            }
            if (getcustom('consumer_value_add', $set['aid'])) {
                $field = 'id,green_score';
                if ($set_member_commission_max) {
                    $field .= ',totalcommission,commission_max';
                }
                $member_lists = Db::name('member')->where('aid', $set['aid'])->where('green_score', '>', 0)->field($field)->select()->toArray();
                if (!$member_lists) {
                    return true;
                }
                $score_weishu = 0;
                if (getcustom('score_weishu', $set['aid'])) {
                    $score_weishu = Db::name('admin_set')->where('aid', $set['aid'])->value('score_weishu');
                }
                $green_score_price = $set['green_score_price'] > $set['min_price'] ? $set['green_score_price'] : $set['min_price'];
                foreach ($member_lists as $member) {
                    $score_total = $member['green_score'];
                    $money = bcmul($score_total, $green_score_price, 2);
                    if ($set_member_commission_max) {
                        //佣金上限
                        $m_total = $member['totalcommission'];
                        $member_commission_max = $member['commission_max'];
                        $commission_max = bcsub($member_commission_max, $m_total, 2);
                        if ($commission_max < $money) {
                            $money = $commission_max > 0 ? $commission_max : 0;
                        }
                    }
                    //提现分入三个钱包
                    $to_commission = bcmul($money, $set['to_commission'] / 100, 2);
                    $to_score = bcmul($money, $set['to_score'] / 100, $score_weishu);
                    $to_money = bcmul($money, $set['to_money'] / 100, 2);
                    if ($to_money > 0) {
                        $rs = \app\common\Member::addmoney($set['aid'], $member['id'], $to_money, t('绿色积分', $set['aid']) . '自动释放');
                    }
                    if ($to_commission > 0) {
                        $rs = \app\common\Member::addcommission($set['aid'], $member['id'], $member['id'], $to_commission, t('绿色积分', $set['aid']) . '自动释放');
                    }
                    if ($to_score > 0) {
                        $rs = \app\common\Member::addscore($set['aid'], $member['id'], $to_score, t('绿色积分', $set['aid']) . '自动释放');
                    }

                    //扣除会员绿色积分
                    //if ($rs['status'] == 1) {
                        \app\common\Member::addgreenscore($set['aid'], $member['id'], $score_total * -1, t('绿色积分', $set['aid']) . '自动释放扣除', '', 0, 1);
                    //}
                    if(getcustom('greenscore_max',$set['aid'])){
                        //扣除封顶额度
                        \app\common\Member::addmaximum($set['aid'], $member['id'], $money * -1, t('绿色积分', $set['aid']) . '自动释放扣除', '', 0, 1);
                        //扣除会员已领红包数量
                        Db::name('member')->where('aid', $set['aid'])->where('id',$member['id'])->update(['green_score_hb'=>0]);
                    }
                    //插入提现记录表
                    $log = [];
                    $log['aid'] = $set['aid'];
                    $log['mid'] = $member['id'];
                    $log['money'] = $money;
                    $log['fee'] = 0;
                    $log['green_score'] = $score_total;
                    $log['to_commission'] = $to_commission;
                    $log['to_money'] = $to_money;
                    $log['to_score'] = $to_score;
                    $log['to_pool'] = 0;
                    $log['remark'] = '自动释放';
                    $log['createtime'] = time();
                    Db::name('greenscore_withdraw_log')->insert($log);
                }
                //扣除平台奖金池
                $rs = \app\common\Member::addbonuspool($set['aid'], 0, -$set['bonus_pool_total'], t('绿色积分', $set['aid']) . '自动释放扣除', '', 0, 0);
                Db::name('consumer_set')->where('aid', $set['aid'])->update(['green_score_total' => 0, 'bonus_pool_total' => 0, 'green_score_price' => $set['min_price']]);
                if(getcustom('green_score_reserves')){
                    Db::name('consumer_set')->where('aid', $set['aid'])->update(['reserves_total'=>0]);
                }
                return true;
            }
        }
    }

    //加消费赠送积分记录
    public static function scoreinlog($aid,$bid,$mid,$type,$orderid,$ordernum,$score,$totalprice=0){
        if($score<=0) return;
        //查询是否有合并
        $typelen = strlen($type);
        $hbpos   = strpos($type,'_hb');
        //如果有合并，且合并'_hb'标识位于最后方
        if($hbpos && $hbpos == ($typelen-3)){
            //查询合并订单
            $ordertype = substr($type, 0,$hbpos);
            $orders = Db::name($ordertype.'_order')->where('ordernum','like',$ordernum.'%')->where('mid',$mid)->where('aid',$aid)->field('id,bid,ordernum,totalprice')->select()->toArray();
            if($orders){
                $klen = count($orders)-1;//key长度
                $allscore = $score;//赋值积分
                foreach($orders as $ok=>$order){
                    //如果循环到最后一个，则把剩余积分都归它
                    if($ok == $klen){
                        $givescore = $allscore;
                    }else{
                        //计算所占积分比例
                        $radio = $order['totalprice']/$totalprice;
                        $givescore = floor($radio*$score);
                    }
                    if($givescore<0){
                        $givescore = 0;
                    }
                    self::scoreinlog2($aid,$bid,$mid,$ordertype,$order['id'],$order['ordernum'],$givescore);
                    $allscore -= $givescore;
                }
                unset($order);
            }
        }else{
            self::scoreinlog2($aid,$bid,$mid,$type,$orderid,$ordernum,$score);
        }
    }
    //加消费赠送积分记录2
    public static function scoreinlog2($aid,$bid,$mid,$type,$orderid,$ordernum,$score){
        $data = [];
        $data['aid']      = $aid;
        $data['bid']      = $bid;
        $data['mid']      = $mid;
        $data['type']     = $type;
        $data['orderid']  = $orderid;
        $data['ordernum'] = $ordernum;
        $data['score']    = $score;//赠送积分
        $data['residue']  = $score;//剩余赠送积分（扣除时用）
        $data['createtime'] = time();
        Db::name('member_score_scoreinlog')->insert($data);
        return ['status'=>1,'msg'=>''];
    }
    //扣除消费赠送积分
    public static function decscorein($aid,$type,$orderid,$ordernum,$remark='订单退款扣除消费赠送'){

        // 开了积分释放功能，不再扣除
        if(getcustom('yx_score_freeze') && getcustom('yx_score_freeze_release')){
            $freeze_set = Db::name('score_freeze_set')->where('aid',$aid)->where('bid',0)->find();
            if($freeze_set['status']){
                return;
            }
        }

        $where = [];
        $where[] = ['orderid','=',$orderid];
        $where[] = ['ordernum','=',$ordernum];
        $where[] = ['type','=',$type];
        $where[] = ['residue','>',0];
        $where[] = ['score','>',0];
        $where[] = ['aid','=',$aid];
        $scoreinlogs = Db::name('member_score_scoreinlog')->where($where)->select()->toArray();
        if($scoreinlogs){
            foreach($scoreinlogs as $log){
                $res = self::addscore($aid,$log['mid'],-$log['residue'],$remark,$type,0,0,1,['canminus'=>true]);
                if($res && $res['status'] == 1){
                    //修改赠送记录
                    Db::name('member_score_scoreinlog')->where('id',$log['id'])->update(['residue'=>0,'updatetime'=>time()]);
                }
            }
            unset($log);
        }
    }
    //增加会员给下级升级数量
    public static function addMemberLevelupNum($aid,$mid,$team_levelup_data){
        if(getcustom('member_levelup_givechild',$aid)){
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->find();
            $where = [];
            $where[] = ['mid','=',$mid];
            $where[] = ['aid','=',$aid];
            
            if($team_levelup_data){
                $team_levelup_data = json_decode($team_levelup_data,true);
                foreach($team_levelup_data as $k=>$v){
                    $usenum = Db::name('member_levelup_uesnum')->where($where)->where('levelupid',$k)->find();
                    if($usenum){
                        $info = [];
                        $info['levelid'] = $member['levelid'];
                        $info['num'] = $v+$usenum['num'];
                        $res = Db::name('member_levelup_uesnum')->where('aid',$aid)->where('id',$usenum['id'])->update($info);
                    }else{
                        $info = [];
                        $info['aid'] = $aid;
                        $info['mid'] = $mid;
                        $info['levelid'] = $member['levelid'];
                        $info['levelupid'] = $k;
                        $info['num'] = $v;
                        $info['createtime'] = time();
                        $id = Db::name('member_levelup_uesnum')->insertGetId($info);
                    }
                }
            }
        }
    }

    public static function addstaffcommission($aid,$bid,$sid,$commission,$remark='',$params = []){
        if(getcustom('extend_staff',$aid)){
            //员工提成
            if($commission==0) return ;
            $staff = Db::name('staff')->where('id',$sid)->where('aid',$aid)->where('bid',$bid)->lock(true)->find();
            if(!$staff) return ['status'=>0,'msg'=>t('会员').'不存在'];

            $after = $staff['commission'] + $commission;
            Db::name('staff')->where('id',$sid)->update(['commission'=>$after]);

            $data = [];
            $data['aid'] = $aid;
            $data['bid'] = $bid??0;
            $data['sid'] = $sid;
            $data['commission'] = $commission;
            $data['after']      = $after;
            $data['remark']     = $remark;

            $data['commission_type'] = $params && $params['commission_type']?$params['commission_type']:0;
            $data['commission_data'] = $params && $params['commission_data']?$params['commission_data']:'';
            $data['commission_rate'] = $params && $params['commission_rate']?$params['commission_rate']:0;
            $data['orderid'] = $params && $params['orderid']?$params['orderid']:0;
            $data['type']    = $params && $params['type']?$params['type']:'';
            $data['uid']     = $params && $params['uid']?$params['uid']:0;
            $data['totalprice'] = $params && $params['totalprice']?$params['totalprice']:0;

            $data['createtime'] = time();
            Db::name('staff_commission_log')->insert($data);
            return ['status'=>1,'msg'=>''];
        }
    }

    public static function addgoldmoney($aid,$mid,$goldmoney,$remark='',$ordernum=0){
        if(getcustom('member_goldmoney_silvermoney',$aid)){
            //添加金值
            if($goldmoney==0) return ;
            $member = Db::name('member')->where('id',$mid)->where('aid',$aid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];

            $updata = [];
            $after = $member['goldmoney'] + $goldmoney;
            $updata['goldmoney'] = $after;
            $up = Db::name('member')->where('id',$mid)->where('aid',$aid)->update($updata);
            if(!$up) return ['status'=>0,'msg'=>'变动失败'];
            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['goldmoney']  = $goldmoney;
            $data['after']      = $after;
            $data['remark']     = $remark;
            $data['ordernum']   = $ordernum;
            $data['createtime'] = time();
            Db::name('member_goldmoneylog')->insert($data);
            return ['status'=>1,'msg'=>''];
        }
    }

    public static function addsilvermoney($aid,$mid,$silvermoney,$remark='',$ordernum=0){
        if(getcustom('member_goldmoney_silvermoney',$aid)){
            //添加金值
            if($silvermoney==0) return ;
            $member = Db::name('member')->where('id',$mid)->where('aid',$aid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];

            $updata = [];
            $after = $member['silvermoney'] + $silvermoney;
            $updata['silvermoney'] = $after;
            $up = Db::name('member')->where('id',$mid)->where('aid',$aid)->update($updata);
            if(!$up) return ['status'=>0,'msg'=>'变动失败'];
            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['silvermoney']= $silvermoney;
            $data['after']      = $after;
            $data['remark']     = $remark;
            $data['ordernum']   = $ordernum;
            $data['createtime'] = time();
            Db::name('member_silvermoneylog')->insert($data);
            return ['status'=>1,'msg'=>''];
        }
    }

    /**
     * 查询原上级，每一层都判断是否有原上级
     * @param $aid
     * @param $mid
     * @param $path
     */
    public static function queryOriginPath($aid,$mid,$max_ceng=1,$now_ceng=0,$parent_arr=[],$field='*'){
        if($max_ceng>=50){
            //限制一下最大50层，防止查询超时
            return $parent_arr;
        }
        $member = Db::name('member')->where('id',$mid)->where('aid',$aid)->find();
        if($member['pid_origin']){
            $parent = Db::name('member')->where('id',$member['pid_origin'])->where('aid',$aid)->field($field)->find();
        }else{
            $parent = Db::name('member')->where('id',$member['pid'])->where('aid',$aid)->field($field)->find();
        }
        $parent_arr[] = $parent;
        if(($parent['pid'] || $parent['pid_origin']) && $now_ceng<$max_ceng){
            $now_ceng++;
            return self::queryOriginPath($aid,$parent['id'],$max_ceng,$now_ceng,$parent_arr,$field);
        }else{
            return $parent_arr;
        }
    }
    //添加积分冻结
    public static function addscorefree($aid,$mid,$score,$remark,$channel='',$bid=0){
        if(getcustom('yx_score_freeze')){
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];
            if($score < 0 && $member['score_freeze'] < $score*-1) {
                return ['status'=>0,'msg'=>t('积分').'冻结不足'];
            }
            $updata = [];
            $after = $member['score_freeze'] + $score;
            $updata['score_freeze'] = $after;
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update($updata);
            
            $data = [];
            $data['aid'] = $aid;
            $data['bid'] = $bid;
            $data['mid'] = $mid;
            $data['score'] = $score;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            $data['channel'] = $channel;
            $data['status'] =  0;
            $data['is_cancel'] = 0;
            $data['uid'] = defined('uid') && !empty(uid)?uid:0;//记录操作员ID
            Db::name('score_freeze_log')->insert($data);
            return ['status'=>1,'msg'=>''];
        }
    }

    public static function autoReg($aid,$sessionid,$platform)
    {
        $member = Db::name('member')->where('aid',$aid)->where('session_id',$sessionid)->find();
        if(!$member){
            $data = [];
            $data['aid'] = $aid;
            $data['sex'] = 3;
            $data['nickname'] = '用户'.random(6);
            //$data['tel'] = time().random(1,1);
            $data['headimg'] = PRE_URL.'/static/img/touxiang.png';

            $data['createtime'] = time();
            $data['session_id'] = $sessionid;
            $data['last_visittime'] = time();
            $data['platform'] = $platform;

            $mid = \app\model\Member::add($aid,$data);
            Db::name('session')->where('aid',$aid)->where('session_id',$sessionid)->update([
                'mid' => $mid,
                'login_time' => time()
            ]);
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->find();
        }else{
            $mid = $member['id'];
            Db::name('session')->where('aid',$aid)->where('session_id',$sessionid)->update([
                'mid' => $mid,
                'login_time' => time()
            ]);
        }
        $sessionid_time = 7*86400;
        if(getcustom('system_nologin_day')){
            //后台设置的免登录天数
            $nologin_day = Db::name('admin_set')->where('aid',$aid)->value('nologin_day');
            if($nologin_day>0){
                $sessionid_time = $nologin_day*86400;
            }
        }
        cache($sessionid.'_mid',$mid,$sessionid_time);
        return $member;
    }

    public static function adddedamount($aid,$bid,$mid,$dedamount,$remark='',$params=['orderid'=>'','ordernum'=>'','from_mid'=>'','paytype'=>'','status'=>1]){
        if(getcustom('member_dedamount')){
            //抵扣金
            if($dedamount==0) return ['status'=>1,'msg'=>'','dedamount'=>$dedamount];

            $member = Db::name('member')->where('id',$mid)->where('aid',$aid)->field('id,dedamount')->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];

            $after = $member['dedamount'] + $dedamount;
            //减少抵扣金
            if($dedamount < 0){
                if($member['dedamount']<=0) return ['status'=>1,'msg'=>'','dedamount'=>0];

                if($after<0){
                    $dedamount = -$member['dedamount'];
                    $after = 0;
                }

                //return ['status'=>0,'msg'=>t('会员').'抵扣金不足'];
            }
            $up = Db::name('member')->where('id',$mid)->where('aid',$aid)->update(['dedamount'=>$after]);
            if(!$up){
                return ['status'=>0,'msg'=>t('会员').'抵扣金更新失败'];
            }
            if($dedamount>0){
                $orderid  = $params['orderid']??0;
                $ordernum = $params['ordernum']??'';
                $from_mid = $params['from_mid']??0;
                $paytype  = $params['paytype']??'';//来源类型
                $opttype  = $params['opttype']??'';//操作类型

                $data = [];
                $data['aid']        = $aid;
                $data['bid']        = $bid;
                $data['mid']        = $mid;
                $data['remark']     = $remark;
                $data['orderid']    = $orderid;
                $data['ordernum']   = $ordernum;
                $data['from_mid']   = $from_mid;
                $data['paytype']    = $paytype;
                $data['uid']        = defined('uid') && !empty(uid)?uid:0;//记录操作员ID 
                $data['type']       = 1;//类型 1：增加 2：减少
                $data['status']     = $params['status']??1;//支付类型 0：待支付 1：已支付
                $data['createtime'] = time();

                if($opttype == 'return'){
                    //查询是否已返还过
                    $count = Db::name('member_dedamountlog')->where('ordernum',$ordernum)->where('mid',$mid)->where('paytype',$paytype)->where('dedamount','>',0)->where('type',1)->count('id');
                    if($count) return ['status'=>0,'msg'=>'已经返还过'];

                    $alldedamount = $dedamount;//剩余待增加抵扣金
                    //查询所有此订单减少的抵扣金
                    $logs = Db::name('member_dedamountlog')->where('ordernum',$ordernum)->where('mid',$mid)->where('paytype',$paytype)->where('dedamount','<',0)->where('orderid',0)->where('type',2)->select()->toArray();
                    if($logs){
                        foreach($logs as $log){
                            if($alldedamount<=0) continue;

                            $cha = $alldedamount + $log['dedamount'];//判断剩余待增加抵扣金
                            if($cha<0){
                                $incdedamount = $alldedamount;//要增加的数值
                                $alldedamount = 0;//减少剩余待增加抵扣金
                            }else{
                                $incdedamount = -$log['dedamount'];//要增加的数值
                                $alldedamount += $log['dedamount'];//减少剩余待增加抵扣金
                            }

                            $data2 = [];
                            $data2 = $data;
                            $data2['pid']        = $log['pid'];
                            $data2['dedamount']  = $incdedamount;//固定抵扣金，不可变动
                            if(!$data2['pid']){
                                $data2['dedamount2']  = $incdedamount;//变动抵扣金，减少时变动
                                Db::name('member_dedamountlog')->insert($data2);
                            }else{
                                //增加
                                $incnum = Db::name('member_dedamountlog')->where('id',$log['pid'])->inc('dedamount2',$incdedamount)->update();
                                if($incnum){
                                    Db::name('member_dedamountlog')->insert($data2);
                                }
                            }
                        }
                    }

                    //若有未退回的则新增加
                    if($alldedamount>0){
                        $data2 = [];
                        $data2 = $data;
                        $data2['dedamount']= $data2['dedamount2'] = $alldedamount;
                        $data2['after']    = $after;
                        Db::name('member_dedamountlog')->insert($data2);
                    }
                }else{
                    $data2 = [];
                    $data2 = $data;
                    $data2['dedamount']  = $dedamount;//固定抵扣金，不可变动
                    $data2['dedamount2'] = $dedamount;//变动抵扣金，减少时变动
                    $data2['after']      = $after;
                    Db::name('member_dedamountlog')->insert($data2);
                }
            }else if($dedamount<0){
                 //减少抵扣金
                self::decdedamount($aid,$bid,$mid,$dedamount,$remark,$params);
            }
            return ['status'=>1,'msg'=>'','dedamount'=>$dedamount];
        }
    }

    public static function decdedamount($aid,$bid,$mid,$dedamount,$remark='',$params=['orderid'=>'','ordernum'=>'','from_mid'=>'','paytype'=>'','status'=>1]){
        if(getcustom('member_dedamount')){
            //减少抵扣金
            if($dedamount>=0) return;
            //抵扣金取正数
            $dedamount = abs($dedamount);

            $data = [];
            $data['aid']        = $aid;
            $data['bid']        = $bid;
            $data['mid']        = $mid;
            $data['remark']     = $remark;
            $data['orderid']    = $params['orderid']??0;
            $data['ordernum']   = $params['ordernum']??'';
            $data['paytype']    = $params['paytype']??'';
            $data['uid']        = defined('uid') && !empty(uid)?uid:0;//记录操作员ID
            $data['type']       = 2;//类型 1：增加 2：减少
            $data['status']     = $params['status']??1;//支付类型 0：待支付 1：已支付
            $data['createtime'] = time();
            //查询最早的可减少的抵扣金记录
            $log = Db::name('member_dedamountlog')->where('mid',$mid)->where('pid',0)->where('dedamount2','>',0)->where('aid',$aid)->field('id,dedamount,dedamount2')->order('id asc')->lock(true)->find();
            if($log){
                $data['pid'] = $log['id'];//减少的记录值
                if($log['dedamount2']>=$dedamount){
                    $decnum    = $dedamount;//减少的数额
                    $dedamount = 0;//剩余待减少抵扣金
                }else{
                    $decnum     = $log['dedamount2'];//减少的数额
                    $dedamount -= $log['dedamount2'];//剩余待减少抵扣金
                }
                $decnum    = round($decnum,2);
                $dedamount = round($dedamount,2);
                if($decnum>0){
                    $up = Db::name('member_dedamountlog')->where('id',$log['id'])->dec('dedamount2',$decnum)->update(['updatetime'=>time()]);
                    if($up){
                        //记录减少
                        $data['dedamount'] = -$decnum;//固定抵扣金，不可变动
                        Db::name('member_dedamountlog')->insert($data);
                    }
                }
                if($dedamount<=0) return;
                //继续减少
                self::decdedamount($aid,$bid,$mid,-$dedamount,$remark,$params);
            }else{
                if($dedamount>0){
                    //记录减少
                    $data['dedamount'] = -$dedamount;//固定抵扣金，不可变动
                    Db::name('member_dedamountlog')->insert($data);
                }
            }
        }
    }

    public static function deal_staydecdedamount($aid,$bid=0,$mid=0,$remark='',$params=['ordernum'=>'','paytype'=>'','type'=>'return','opttype'=>0]){
        if(getcustom('member_dedamount')){
            //处理待支付抵扣金记录
            $type = $params['type']??'return';//类型return 返还，pay 支付

            $where = [];
            $where[] = ['aid','=',$aid];
            if($bid ===0 || $bid>0){
                $where[] = ['bid','=',$bid];
            }
            if($mid){
                $where[] = ['mid','=',$mid];
            }
            $ordernum = $params['ordernum']??0;
            if($ordernum){
                $where[] = ['ordernum','=',$ordernum];
            }
            $paytype = $params['paytype']??0;
            if($paytype){
                $where[] = ['paytype','=',$paytype];
            }
            $where[] = ['status','=',0];
            $where[] = ['dedamount','<',0];
            $opttype = $params['opttype']??0;
            if($opttype == 1){
                //一个小时前
                $endtime = time()-3600;
                $where[] = ['createtime','<=',$endtime];
            }
            $logs =  Db::name('member_dedamountlog')->where($where)->order('id asc')->select()->toArray();
            if($logs){
                foreach($logs as $lv){
                    $up = Db::name('member_dedamountlog')->where('id',$lv['id'])->where('status',0)->update(['status'=>1]);
                    //进行返还
                    if($up){
                        if($type == 'return'){
                            $dedamount = abs($lv['dedamount']);
                            $params=['orderid'=>$lv['orderid'],'ordernum'=>$lv['ordernum'],'paytype'=>$lv['paytype']];
                            self::adddedamount($lv['aid'],$lv['bid'],$lv['mid'],$dedamount,$remark,$params);
                            if($lv['paytype'] == 'maidan' || $lv['paytype'] == 'maidan_new'){
                                Db::name($lv['paytype'].'_order')->where('ordernum',$lv['ordernum'])->where('status',0)->update(['status'=>-1]);
                            }
                        }
                    }
                }
                unset($lv);
            }
            
        }
    }
    //仅获取链动脱离人员的团队
    public static function getdowntotalmids_origin($aid,$mid,$where='1=1'){
        $downmids = [];
        $memberlistOrigin = Db::name('member')->field('id,path')->where('aid',$aid)->where('pid_origin',$mid)->where($where)->select()->toArray();
        if($memberlistOrigin){
            foreach($memberlistOrigin as $member){
                $downmids[] = $member['id'];
            }
        }
        return $downmids;
    }

    public static function addshopscore($aid,$mid,$shopscore,$remark,$params = []){
        if(getcustom('member_shopscore')){
            //产品积分
            if($shopscore==0) return ;

            $member = Db::name('member')->where('id',$mid)->where('aid',$aid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];

            if($shopscore < 0 && $member['shopscore'] < $shopscore*-1) {
                return ['status'=>0,'msg'=>t('产品积分').'不足'];
            }

            $updata = [];
            $after = $member['shopscore'] + $shopscore;
            $updata['shopscore'] = $after;
            $up = Db::name('member')->where('aid',$aid)->where('id',$mid)->update($updata);
            if($up){
                $data = [];
                $data['aid'] = $aid;
                $data['mid'] = $mid;
                $data['shopscore']   = $shopscore;
                $data['after']   = $after;
                $data['remark']  = $remark;
                $data['uid']     = defined('uid') && !empty(uid)?uid:0;//记录操作员ID

                $data['frommid']    = $params['frommid']??0;
                $data['orderid']    = $params['orderid']??0;
                $data['ordernum']   = $params['ordernum']??'';
                $data['paytype']    = $params['paytype']??'';
                $data['createtime'] = time();
                Db::name('member_shopscorelog')->insert($data);
                return ['status'=>1,'msg'=>''];
            }else{
                return ['status'=>0,'msg'=>'操作失败'];
            }
        }
    }
    public static function addupgradescore($aid,$mid,$upgradescore,$remark,$params = []){
        if(getcustom('member_upgradescore')){
            //升级积分
            if($upgradescore==0) return ;
            $text = \app\model\Hotel::gettext($aid);
            $upgradescorename = $text['升级积分'];

            $member = Db::name('member')->where('id',$mid)->where('aid',$aid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];

            if($upgradescore < 0 && $member['upgradescore'] < $upgradescore*-1) {
                return ['status'=>0,'msg'=>$upgradescorename.'不足'];
            }

            $updata = [];
            $after = $member['upgradescore'] + $upgradescore;
            $updata['upgradescore'] = $after;
            $up = Db::name('member')->where('aid',$aid)->where('id',$mid)->update($updata);
            if($up){
                $data = [];
                $data['aid'] = $aid;
                $data['mid'] = $mid;
                $data['upgradescore']   = $upgradescore;
                $data['after']   = $after;
                $data['remark']  = $remark;
                $data['uid']     = defined('uid') && !empty(uid)?uid:0;//记录操作员ID

                $data['frommid']    = $params['frommid']??0;
                $data['orderid']    = $params['orderid']??0;
                $data['ordernum']   = $params['ordernum']??'';
                $data['paytype']    = $params['paytype']??'';
                $data['createtime'] = time();
                Db::name('member_upgradescorelog')->insert($data);
                return ['status'=>1,'msg'=>''];
            }else{
                return ['status'=>0,'msg'=>'操作失败'];
            }
        }
    }
    // 推荐人，没有原上级，找现上级
    public static function getPids($aid,$sysset,$mid,$path=''){

        $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->find();
        
        if($sysset['gangwei_give_origin_status'] == 0){//现上级
            return $member['path'];
        }elseif($sysset['gangwei_give_origin_status'] == 1){//原上级
            return $member['path'];
        }else{//每层原上级
            if($member['pid_origin']){
                $member['pid'] = $member['pid_origin'];
            }
            if($member['pid']){
                $path = $path ? $member['pid'].','.$path : $member['pid'];
                return self::getPids($aid,$sysset,$member['pid'],$path);
            }
            return $path;
        }
        
    }

    //获取团队是区域代理的的会员集合
    private static $areamids = [];
    public static function getteamareamarr($aid,$mid,$deep=999,$levelids=[],$areamids=[],$thisdeep=0,$areatype=0,$province='',$city='',$area=''){
        if($thisdeep == 0){
            self::$areamids = [];
        }
        $thisdeep = $thisdeep+1;
        if($thisdeep > $deep) return self::$areamids;
        $where = [];
        $where[] = ['m.aid','=',$aid];
        $where[] = ['m.pid','=',$mid];
        $where[] = ['m.areafenhong','<',4];
        if(!empty($levelids)){
            $where[] = ['m.levelid','in',$levelids];
        }
        $dowmids = Db::name('member')->alias('m')
            ->leftJoin('member_level l','l.id = m.levelid')
            ->field('m.id,m.levelid,m.areafenhong as m_areafenhong,m.areafenhong_province,m.areafenhong_city,m.areafenhong_area,l.areafenhong')
            ->where($where)
            ->select()->toArray();
        if($dowmids){
            foreach($dowmids as $downmid){
                if(!in_array($downmid['id'],self::$areamids) && ($downmid['m_areafenhong'] > 0 || $downmid['areafenhong'] > 0)){
                    $areafenhong = $downmid['m_areafenhong'];
                    if($downmid['m_areafenhong'] == 0){
                        $areafenhong = $downmid['areafenhong'];
                    }
                    if($areatype > 0){
                        //同区域判断
                        $istqu = false;
                        if($areatype == 1 && $downmid['areafenhong_province'] == $province){
                            $istqu = true;
                        }elseif($areatype == 2 && $downmid['areafenhong_province'] == $province && $downmid['areafenhong_city'] == $city){
                            $istqu = true;
                        }elseif($areatype == 3 && $downmid['areafenhong_province'] == $province && $downmid['areafenhong_city'] == $city && $downmid['areafenhong_area'] == $area){
                            $istqu = true;
                        }
                        if($istqu){
                            self::$areamids[] = [
                                'id' => $downmid['id'],
                                'areafenhong_type' => $areafenhong,
                                'areafenhong_province' => $downmid['areafenhong_province'],
                                'areafenhong_city' => $downmid['areafenhong_city'],
                                'areafenhong_area' => $downmid['areafenhong_area'],
                                'levelid' => $downmid['levelid'],
                            ];
                        }
                    }else{
                        self::$areamids[] = [
                            'id' => $downmid['id'],
                            'areafenhong_type' => $areafenhong,
                            'areafenhong_province' => $downmid['areafenhong_province'],
                            'areafenhong_city' => $downmid['areafenhong_city'],
                            'areafenhong_area' => $downmid['areafenhong_area'],
                            'levelid' => $downmid['levelid'],
                        ];
                    }
                }
                self::getteamareamarr($aid,$downmid['id'],$deep,$levelids,$areamids,$thisdeep,$areatype,$province,$city,$area);
            }
        }
        return self::$areamids;
    }
    
    public static function addforzengxcommission($aid,$mid,$commission,$type='',$logid=0,$sendmonth=0){
        if(getcustom('member_forzengxcommission')){
            //冻结贡献值佣金，按月释放
            $log = [];
            $log['aid'] = $aid;
            $log['mid'] = $mid;
            $log['type'] = $type;
            $log['logid']= $logid;

            $log['commission'] = $commission;//固定不变
            $log['commission2']= $commission;//可变
            //计算每个月应发佣金
            if($log['commission']>0 && $sendmonth>0){
                $log['avgcommission'] = $log['commission']/$sendmonth;
            }
            $log['sendmonth']  = $sendmonth;//发放几个月
            $log['sendmonth2'] = 0;//已发放几个月
            $log['status'] = 0;//状态 0：未发放完 1：已发放完
            $log['createtime'] = time();
            $log['updatetime'] = time();
            Db::name('member_forzengxcommissionlog')->insert($log);
        }
    }

    /**
     * 即拼成团后自动生成新订单
     * @author: liud
     * @time: 2025/2/12 上午10:53
     */
    public static function addCollageJipinOrder($aid,$mid,$pro_id,$num=1,$hd_info){
        $yx_collage_jipin_optimize = getcustom('yx_collage_jipin_optimize',$aid);
        if(getcustom('yx_collage_jipin',$aid)) {
            $product = Db::name('shop_product')->where('aid', $aid)->where('id', $pro_id)->find();
            if(!$product){
                return 0;
            }
            $guige = Db::name('shop_guige')->where('aid', $aid)->where('proid', $pro_id)->find();
            $sysset = Db::name('admin_set')->where('aid', $aid)->find();

            $ordernum = \app\common\Common::generateOrderNo($aid);
            $orderdata = [];
            $orderdata['aid'] = $aid;
            $orderdata['mid'] = $mid;
            $orderdata['bid'] = $product['bid'] ?: 0;
            $orderdata['ordernum'] = $ordernum;
            $orderdata['title'] = $product['name'];

            $freight_type = $product['freighttype']?:1;
            if(!$address = Db::name('member_address')->where('aid', $aid)->where('mid', $mid)->order('isdefault desc')->find()){
                //查询最近的一个订单
                if($o_order = Db::name('shop_order')->where('aid', $aid)->where('mid', $mid)->where('freight_type',$freight_type)->order('id desc')->find()){
                    $address['tel'] = $o_order['tel'] ?? '';
                    $ssq = explode(',',$o_order['area2']);
                    $address['province'] = $ssq[0] ?? '';
                    $address['city'] = $ssq[1] ?? '';
                    $address['district'] = $ssq[2] ?? '';
                    if($freight_type == 0){//快递
                        $address['name'] = $o_order['linkman'] ?? '';
                        $address['address'] = $o_order['address'] ?? '';
                    }
                }
            }

            $status = 1;
            $is_jipin_show = 1;
            if($yx_collage_jipin_optimize){
                //隐藏
                if($hd_info['auto_order_show'] == 0){
                    $status = 3;
                    $is_jipin_show = 0;
                }
            }

            $freight_text = $o_order ? $o_order['freight_text'] : "普通快递(0元)";
            $area = $address['province'] ?? '' . $address['city'] ?? '' . $address['district']?? '' ;
            $area2 = $address['province'] ? $address['province'] . ',' . $address['city'] . ',' . $address['district'] : '';
            $orderdata['linkman'] = $address['name'] ?? '';
            $orderdata['tel'] = $address['tel'] ?? '';
            $orderdata['area'] = $freight_type ==1 ? '' : $area;
            $orderdata['area2'] = $area2;
            $orderdata['address'] = $address['address'] ?? '';
            $orderdata['totalprice'] = $product['sell_price'] * $num;
            $orderdata['product_price'] = $product['sell_price'];
            $orderdata['leveldk_money'] = 0;  //会员折扣
            $orderdata['scoredk_money'] = 0;    //积分抵扣
            $orderdata['scoredkscore'] = 0;    //抵扣掉的积分
            $orderdata['freight_price'] = 0; //运费
            $orderdata['message'] = '';
            $orderdata['freight_text'] = $freight_text;
            $orderdata['freight_id'] = '';
            $orderdata['freight_type'] = $freight_type;
            $orderdata['mdid'] = 0;
            $orderdata['platform'] = 'admin';
            $orderdata['hexiao_code'] = random(16);
            $orderdata['hexiao_qr'] = createqrcode(m_url('admin/hexiao/hexiao?type=shop&co=' . $orderdata['hexiao_code']));
            $orderdata['status'] = $status;
            $orderdata['paytype'] = '';
            $orderdata['createtime'] = time();
            $orderdata['paytime'] = time();
            $remark = '即拼出局后自动生成新订单';

            $orderdata['remark'] = $remark;
            $orderdata['givescore'] = 0;
            $orderdata['givescore2'] = 0;
            $orderdata['is_jipin_auto'] = 1;

            if($yx_collage_jipin_optimize){
                $orderdata['is_jipin_show'] = $is_jipin_show;
            }

            $orderid = Db::name('shop_order')->insertGetId($orderdata);
            $ogdata = [];
            $ogdata['aid'] = $aid;
            $ogdata['bid'] = $product['bid'];
            $ogdata['mid'] = $mid;
            $ogdata['orderid'] = $orderid;
            $ogdata['ordernum'] = $orderdata['ordernum'];
            $ogdata['proid'] = $product['id'];
            $ogdata['name'] = $product['name'];
            $ogdata['pic'] = $guige['pic'] ? $guige['pic'] : $product['pic'];
            $ogdata['procode'] = $product['procode'];
            $ogdata['barcode'] = $product['barcode'];
            $ogdata['ggid'] = $guige['id'];
            $ogdata['ggname'] = $guige['name'];
            $ogdata['cid'] = $product['cid'];
            $ogdata['num'] = $num;
            $ogdata['cost_price'] = $guige['cost_price'];
            $ogdata['sell_price'] = $guige['sell_price'];
            $ogdata['totalprice'] = $num * $guige['sell_price'];
            $ogdata['real_totalprice'] = $ogdata['totalprice'];
            $ogdata['status'] = $status;
            $ogdata['createtime'] = time();
            if ($product['fenhongset'] == 0) { //不参与分红
                $ogdata['isfenhong'] = 2;
            }
            $ogid = Db::name('shop_order_goods')->insertGetId($ogdata);

            //分销数据
            //计算佣金的商品金额
            $commission_totalprice = $ogdata['totalprice'];
            if($sysset['fxjiesuantype']==1){ //按成交价格
                $commission_totalprice = $ogdata['totalprice'];
            }
            if($sysset['fxjiesuantype']==2){ //按销售利润
                $commission_totalprice = $ogdata['totalprice'] - $guige['cost_price'] * $num;
            }
            if($commission_totalprice < 0) $commission_totalprice = 0;
            $istc1 = 0; //设置了按单固定提成时 只将佣金计算到第一个商品里
            $istc2 = 0;
            $istc3 = 0;
            $isfg  = 0;
            $member = Db::name('member')->where('aid', $aid)->where('id',$mid)->find();
            if(!getcustom('fenxiao_manage',$aid)){
                $sysset['fenxiao_manage_status'] = 0;
            }
            if($sysset['fenxiao_manage_status']){
                $commission_data = \app\common\Fenxiao::fenxiao_jicha($sysset,$member,$product,$num,$commission_totalprice);
            }else{
                $commission_data = \app\common\Fenxiao::fenxiao($sysset,$member,$product,$num,$commission_totalprice,$isfg,$istc1,$istc2,$istc3);
            }
            $ogupdate = [];
            $ogupdate['parent1'] = $commission_data['parent1']??0;
            $ogupdate['parent2'] = $commission_data['parent2']??0;
            $ogupdate['parent3'] = $commission_data['parent3']??0;
            $ogupdate['parent4'] = $commission_data['parent4']??0;
            $ogupdate['parent1commission'] = $commission_data['parent1commission']??0;
            $ogupdate['parent2commission'] = $commission_data['parent2commission']??0;
            $ogupdate['parent3commission'] = $commission_data['parent3commission']??0;
            $ogupdate['parent4commission'] = $commission_data['parent4commission']??0;
            $ogupdate['parent1score'] = $commission_data['parent1score']??0;
            $ogupdate['parent2score'] = $commission_data['parent2score']??0;
            $ogupdate['parent3score'] = $commission_data['parent3score']??0;
            $istc1 = $commission_data['istc1']??0;
            $istc2 = $commission_data['istc2']??0;
            $istc3 = $commission_data['istc3']??0;
            if($ogupdate){
                Db::name('shop_order_goods')->where('aid', $aid)->where('id',$ogid)->update($ogupdate);
            }

            if($product['commissionset']!=4){
                if($ogupdate['parent1'] && ($ogupdate['parent1commission']>0 || $ogupdate['parent1score']>0)){
                    $data_c = ['aid'=>$aid,'mid'=>$ogupdate['parent1'],'frommid'=>$mid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent1commission'],'score'=>$ogupdate['parent1score'],'remark'=>t('下级').'购买商品奖励','createtime'=>time()];
                    Db::name('member_commission_record')->insert($data_c);
                }
                if($ogupdate['parent2'] && ($ogupdate['parent2commission']>0 || $ogupdate['parent2score']>0)){
                    $data_c = ['aid'=>$aid,'mid'=>$ogupdate['parent2'],'frommid'=>$mid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent2commission'],'score'=>$ogupdate['parent2score'],'remark'=>t('下二级').'购买商品奖励','createtime'=>time()];
                    Db::name('member_commission_record')->insert($data_c);
                }
                if($ogupdate['parent3'] && ($ogupdate['parent3commission']>0 || $ogupdate['parent3score']>0)){
                    $data_c = ['aid'=>$aid,'mid'=>$ogupdate['parent3'],'frommid'=>$mid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent3commission'],'score'=>$ogupdate['parent3score'],'remark'=>t('下三级').'购买商品奖励','createtime'=>time()];
                    Db::name('member_commission_record')->insert($data_c);
                }
                if($ogupdate['parent4'] && ($ogupdate['parent4commission']>0)){
                    $remark = '持续推荐奖励';
                    if(getcustom('commission_parent_pj_stop',$aid)){
                        $remark = '平级奖';
                    }
                    Db::name('member_commission_record')->insert(['aid'=>$aid,'mid'=>$ogupdate['parent4'],'frommid'=>$mid,'orderid'=>$orderid,'ogid'=>$ogid,'type'=>'shop','commission'=>$ogupdate['parent4commission'],'score'=>0,'remark'=>$remark,'createtime'=>time()]);
                }
            }

            Db::name('shop_guige')->where('aid', $aid)->where('id', $guige['id'])->update(['stock' => Db::raw("stock-$num"), 'sales' => Db::raw("sales+$num")]);
            Db::name('shop_product')->where('aid', $aid)->where('id', $product['id'])->update(['stock' => Db::raw("stock-$num"), 'sales' => Db::raw("sales+$num")]);
            \app\model\Payorder::shop_pay($orderid);

            //订单创建完成后操作
            $orderids = \app\model\ShopOrder::after_create($aid,$orderid);
            return $orderid;
        }
    }

    /**
     * 自动检测赋予会员标签
     * 标签：member_tag_age
     * 开发文档，第7条：https://doc.weixin.qq.com/doc/w3_AT4AYwbFACwL9yuYq8HSTagBwsGZK?scode=AHMAHgcfAA0EtMV8gaAeYAOQYKALUhttps://doc.weixin.qq.com/doc/w3_AT4AYwbFACwL9yuYq8HSTagBwsGZK?scode=AHMAHgcfAA0EtMV8gaAeYAOQYKALU
     * @author: liud
     * @time: 2025/3/12 下午6:55
     */
    public static function checktagageapply($aid,$mid,$oid)
    {
        $member_tag_levelup = getcustom('member_tag_levelup');
        if (getcustom('member_tag_age')){
            $age_apply_order = Db::name('member_tag_age_apply_order')->where('aid',$aid)->where('mid',$mid)->where('id',$oid)->where('status',0)->find();
            if(!$age_apply_order){
                return ['status'=>0,'msg'=>'申请信息不存在'];
            }

            $tag_age = Db::name('member_tag')->where('aid',$aid)->where('id',$age_apply_order['tag_id'])->find();
            if(!$tag_age){
                return ['status'=>0,'msg'=>'标签信息不存在'];
            }

            if($tag_age['status'] != 1){
                return ['status'=>0,'msg'=>'标签['.$tag_age['name'].']已关闭!'];
            }

            $minfo = Db::name('member')->where('aid',$aid)->where('id',$mid)->find();
            $tags_arr = explode(',',$minfo['tags']);

            //会员等级排序
            $member_level_sort = Db::name('member_level')->where('aid',$aid)->where('id',$minfo['levelid'])->value('sort');
            $member_level_sort = $member_level_sort??0;

            //注册时间
            $regdatestatus=1;
            if($tag_age['regdatestatus']==1){
                $starttime = time() - $tag_age['maxdays']*86400;
                $endtime = time() - $tag_age['mindays']*86400;
                $regdatestatus=0;
                if($minfo['createtime'] >= $starttime && $minfo['createtime'] <= $endtime){
                    $regdatestatus = 1;
                }
            }

            //会员等级
            $levelstatus=1;
            if($tag_age['levelstatus']==1){
                $levelstatus=0;
                if($minfo['levelid'] = $tag_age['levelid']){
                    $levelstatus = 1;
                }
            }

            //消费次数
            $buystatus=1;
            if($tag_age['buystatus']==1){
                $buystatus=0;
                if($minfo['buynum'] >= $tag_age['buynum']){
                    $buystatus = 1;
                }
            }

            //消费金额
            $buymoneystatus=1;
            if($tag_age['buymoneystatus']==1){
                $buymoneystatus=0;
                if($minfo['buymoney'] >= $tag_age['buymoney']){
                    $buymoneystatus = 1;
                }
            }

            //指定商品
            $prostatus=1;
            if($tag_age['prostatus']==1){
                $proids = explode(',',$tag_age['productids']);
                $prostatus = Db::name('shop_order_goods')->where('aid',$aid)->where('mid',$mid)->where('proid','in',$proids)->where('status','in','1,2,3')->count();
            }

            //判断之前条件
            $zqtj = 0;
            if($tag_age['condition'] == 'or'){
                if($regdatestatus>0 || $levelstatus>0 || $buystatus>0 || $buymoneystatus>0 || $prostatus>0){
                    $zqtj = 1;
                }
            }else {
                if($regdatestatus>0 && $levelstatus>0 && $buystatus>0 && $buymoneystatus>0 && $prostatus>0){
                    $zqtj = 1;
                }
            }

            if($zqtj == 0){
                return ['status'=>0,'msg'=>'不满足条件设置！'];
            }

            $apply_give_tagid = 0;
            if($tag_age['tag_age_type'] == 0){
                //普通标签直接赋予
                if(!in_array($tag_age['id'],$tags_arr)){
                    Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['tags' => $minfo['tags'].','.$tag_age['id']]);
                    $apply_give_tagid = $tag_age['id'];

                    //判断赠送积分
                    if($tag_age['give_score'] > 0){
                        //增加积分记录
                        \app\common\Member::addscore($aid,$mid,$tag_age['give_score'],'获得会员标签['.$tag_age['name'].']赠送','',0,$tag_age['id']);
                    }

                    //标签等级信息
                    $tag_age_level_sort = Db::name('member_level')->where('aid',$aid)->where('id',$tag_age['up_levelid'])->value('sort');
                    $tag_age_level_sort = $tag_age_level_sort??0;

                    //获取标签后同时升级会员等级
                    if($member_tag_levelup && $tag_age['type'] == 3 && $tag_age['up_levelid'] > 0 && $tag_age_level_sort > $member_level_sort){

                        //升级
                        Db::name('member')->where('aid', $aid)->where('id', $minfo['id'])->update(['levelid' => $tag_age['up_levelid'], 'levelendtime' => 0,'levelstarttime' => time()]);

                        if($tag_age['up_levelid'] != $minfo['levelid'] && $minfo['pid'] > 0){
                            \app\common\Member::uplv($aid,$minfo['pid']);
                        }

                        if($tag_age['up_levelid'] != $minfo['levelid']){
                            //插入级别变动记录
                            $level_sort = Db::name('member_level')->where('aid',$aid)->column('sort','id');
                            $level_type = $level_sort[$tag_age['up_levelid']]>$level_sort[$minfo['levelid']]?'0':'1';
                            $remark =  $level_sort[$tag_age['up_levelid']]>$level_sort[$minfo['levelid']]?'获得标签同步升级':'获得标签同步降级';
                            $order = [
                                'aid' => $minfo['aid'],
                                'mid' => $minfo['id'],
                                'from_mid' => $minfo['id'],
                                'pid'=>$minfo['pid'],
                                'levelid' => $tag_age['up_levelid'],
                                'title' => t('会员').'标签同步获取',
                                'totalprice' => 0,
                                'createtime' => time(),
                                'levelup_time' => time(),
                                'beforelevelid' => $minfo['levelid'],
                                'form0' => '类型^_^' . $remark,
                                'platform' => '',
                                'status' => 2,
                                'type' => $level_type
                            ];
                            Db::name('member_levelup_order')->insert($order);
                        }
                    }
                }
            }else{
                //年龄标签
                //获取提交的出生日期
                if($age_apply_order['birthday']){
                    //获取年龄(岁)
                    // 计算两个时间戳之间的差异（秒）
                    $ageInSeconds = strtotime(date('Y-m-d')) - strtotime($age_apply_order['birthday']);

                    // 将秒转换为年（考虑到不是每一年都是365天，这里使用更精确的方法）
                    $ageInYears = floor($ageInSeconds / (60 * 60 * 24 * 365.25)); // 使用365.25考虑到闰年
                }


                if(!$ageInYears){
                    return ['status'=>0,'msg'=>'出生日期不正确!'];
                }

                //获取所有子标签
                if($zi_tag = Db::name('member_tag')->where('aid',$aid)->where('pid',$tag_age['id'])->where('status',1)->order('sort desc')->select()->toArray()){
                    $fhnl = 0;
                    foreach ($zi_tag as $vt) {
                        if ($ageInYears >= $vt['age_type_start'] && $ageInYears <= $vt['age_type_end']) {
                            // 找到符合年龄要求的子标签
                            if (!in_array($vt['id'], $tags_arr)) {
                                Db::name('member')->where('aid', $aid)->where('id', $mid)->update(['tags' => $minfo['tags']. ','. $vt['id']]);

                                //判断赠送积分
                                if($vt['give_score'] > 0){
                                    //增加积分记录
                                    \app\common\Member::addscore($aid,$mid,$vt['give_score'],'获得会员标签['.$vt['name'].']赠送','',0,$vt['id']);
                                }

                                //标签等级信息
                                $tag_age_level_sort = Db::name('member_level')->where('aid',$aid)->where('id',$vt['up_levelid'])->value('sort');
                                $tag_age_level_sort = $tag_age_level_sort??0;

                                //获取标签后同时升级会员等级
                                if($member_tag_levelup && $vt['type'] == 3 && $vt['up_levelid'] > 0 && $tag_age_level_sort > $member_level_sort){
                                    //升级
                                    Db::name('member')->where('aid', $aid)->where('id', $minfo['id'])->update(['levelid' => $vt['up_levelid'], 'levelendtime' => 0,'levelstarttime' => time()]);

                                    if($vt['up_levelid'] != $minfo['levelid'] && $minfo['pid'] > 0){
                                        \app\common\Member::uplv($aid,$minfo['pid']);
                                    }

                                    if($vt['up_levelid'] != $minfo['levelid']){
                                        //插入级别变动记录
                                        $level_sort = Db::name('member_level')->where('aid',$aid)->column('sort','id');
                                        $level_type = $level_sort[$vt['up_levelid']]>$level_sort[$minfo['levelid']]?'0':'1';
                                        $remark =  $level_sort[$vt['up_levelid']]>$level_sort[$minfo['levelid']]?'获得标签同步升级':'获得标签同步降级';
                                        $order = [
                                            'aid' => $minfo['aid'],
                                            'mid' => $minfo['id'],
                                            'from_mid' => $minfo['id'],
                                            'pid'=>$minfo['pid'],
                                            'levelid' => $vt['up_levelid'],
                                            'title' => t('会员').'标签同步获取',
                                            'totalprice' => 0,
                                            'createtime' => time(),
                                            'levelup_time' => time(),
                                            'beforelevelid' => $minfo['levelid'],
                                            'form0' => '类型^_^' . $remark,
                                            'platform' => '',
                                            'status' => 2,
                                            'type' => $level_type
                                        ];
                                        Db::name('member_levelup_order')->insert($order);
                                    }
                                }
                            }
                            $fhnl = 1;
                            $apply_give_tagid = $vt['id'];
                            break;
                        }
                    }

                    //如果没找符年龄的子标签-直接赋父标签
                    if($fhnl == 0){
                        if (!in_array($tag_age['id'], $tags_arr)) {
                            Db::name('member')->where('aid', $aid)->where('id', $mid)->update(['tags' => $minfo['tags']. ','. $tag_age['id']]);
                            $apply_give_tagid = $tag_age['id'];

                            //判断赠送积分
                            if($tag_age['give_score'] > 0){
                                //增加积分记录
                                \app\common\Member::addscore($aid,$mid,$tag_age['give_score'],'获得会员标签['.$tag_age['name'].']赠送','',0,$tag_age['id']);
                            }

                            //标签等级信息
                            $tag_age_level_sort = Db::name('member_level')->where('aid',$aid)->where('id',$tag_age['up_levelid'])->value('sort');
                            $tag_age_level_sort = $tag_age_level_sort??0;

                            //获取标签后同时升级会员等级
                            if($member_tag_levelup && $tag_age['type'] == 3 && $tag_age['up_levelid'] > 0 && $tag_age_level_sort > $member_level_sort){

                                //升级
                                Db::name('member')->where('aid', $aid)->where('id', $minfo['id'])->update(['levelid' => $tag_age['up_levelid'], 'levelendtime' => 0,'levelstarttime' => time()]);

                                if($tag_age['up_levelid'] != $minfo['levelid'] && $minfo['pid'] > 0){
                                    \app\common\Member::uplv($aid,$minfo['pid']);
                                }

                                if($tag_age['up_levelid'] != $minfo['levelid']){
                                    //插入级别变动记录
                                    $level_sort = Db::name('member_level')->where('aid',$aid)->column('sort','id');
                                    $level_type = $level_sort[$tag_age['up_levelid']]>$level_sort[$minfo['levelid']]?'0':'1';
                                    $remark =  $level_sort[$tag_age['up_levelid']]>$level_sort[$minfo['levelid']]?'获得标签同步升级':'获得标签同步降级';
                                    $order = [
                                        'aid' => $minfo['aid'],
                                        'mid' => $minfo['id'],
                                        'from_mid' => $minfo['id'],
                                        'pid'=>$minfo['pid'],
                                        'levelid' => $tag_age['up_levelid'],
                                        'title' => t('会员').'标签同步获取',
                                        'totalprice' => 0,
                                        'createtime' => time(),
                                        'levelup_time' => time(),
                                        'beforelevelid' => $minfo['levelid'],
                                        'form0' => '类型^_^' . $remark,
                                        'platform' => '',
                                        'status' => 2,
                                        'type' => $level_type
                                    ];
                                    Db::name('member_levelup_order')->insert($order);
                                }
                            }
                        }
                    }
                }
            }

            //修改申请单信息
            Db::name('member_tag_age_apply_order')->where('aid',$aid)->where('mid',$mid)->where('id',$oid)->update(['status' => 1,'apply_time' => time(),'apply_give_tagid' => $apply_give_tagid]);

            return ['status'=>1,'msg'=>''];
        }
    }

    //增加回本股东分红额度
    public static function addhuibenmaximum($aid,$mid,$value,$remark,$channel='',$orderid=0){
        if(getcustom('fenhong_gudong_huiben',$aid)){
            if($value==0) return ;
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];
            if($value < 0 && $member['huiben_maximum'] < $value*-1) return ['status'=>0,'msg'=>t('回本股东分红额度').'不足'];
            if($value < 0 && $member['huiben_maximum'] < $value*-1) return ['status'=>0,'msg'=>t('回本股东分红额度').'不足'];
            $after = $member['huiben_maximum'] + $value;
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['huiben_maximum'=>$after]);
            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['value'] = $value;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            $data['channel'] = $channel;
            $data['orderid'] = $orderid;
            Db::name('member_huibenmaximum_log')->insert($data);
            return ['status'=>1,'msg'=>''];
        }
    }

    //冻结账户
    public static function addFreezeMoney($aid,$mid,$money,$remark='',$ordernum=0){
        if(getcustom('freeze_money',$aid)){
            if($money==0) return ;
            $member = Db::name('member')->where('id',$mid)->where('aid',$aid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];

            $updata = [];
            $after = $member['freezemoney'] + $money;
            //减后 余额为负数的情况
            if($after < 0 && $money < 0){
                return ['status'=>0,'msg'=>t('冻结资金').'余额不足'];
            }
            $updata['freezemoney'] = $after;
            $up = Db::name('member')->where('id',$mid)->where('aid',$aid)->update($updata);
            if(!$up) return ['status'=>0,'msg'=>'变动失败'];
            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['freezemoney']  = $money;
            $data['after']      = $after;
            $data['remark']     = $remark;
            $data['ordernum']   = $ordernum;
            $data['createtime'] = time();
            $set_custom = Db::name('admin_set_custom')->where('aid',$aid)->find();
            if($money>0 && $set_custom['freeze_money_able_days']>0){
                $data['end_time'] = time()+$set_custom['freeze_money_able_days']*86400;
            }
            $data['remain'] = $money>0?$money:0;
            Db::name('member_freezemoneylog')->insert($data);
            if($money<0){
                //扣除冻结资金时按记录扣除
                $logs = Db::name('member_freezemoneylog')
                    ->where('mid',$mid)->where('aid',$aid)
                    ->where('remain','>',0)
                    ->where('end_time','>',0)
                    ->where('end_time','>',time())
                    ->order('id asc')
                    ->select()->toArray();
                if($logs){
                    $dec_money = abs($money);
                    foreach($logs as $log){
                        if($dec_money<=$log['remain']){
                            Db::name('member_freezemoneylog')->where('id',$log['id'])->dec('remain',$dec_money)->update();
                            break;
                        }else{
                            Db::name('member_freezemoneylog')->where('id',$log['id'])->dec('remain',$log['remain'])->update();
                            $dec_money -= $log['remain'];
                        }
                        if($dec_money<=0){
                            break;
                        }
                    }
                }
            }
            return ['status'=>1,'msg'=>''];
        }
    }

    public static function addjichamoney($aid,$mid,$money,$remark,$frommid=0,$paytype='',$params=[]){
        if(getcustom('teamfenhong_jichamoney')){
            if($money==0) return ;
            $member = Db::name('member')->where('id',$mid)->where('aid',$aid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];

            $money_weishu = 2;
            if(getcustom('fenhong_money_weishu')){
                //读取佣金位数
                $money_weishu = Db::name('admin_set')->where('aid',$aid)->value('fenhong_money_weishu');
            }
            $money = dd_money_format($money,$money_weishu);
            $after = $member['jichamoney'] + $money;
            //减后 余额为负数的情况
            if($after < 0 && $money < 0){
                return ['status'=>0,'msg'=>t('会员').t('级差奖励').'余额不足'];
            }
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['jichamoney'=>$after]);

            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['money']  = $money;
            $data['after']  = $after;
            $data['remark'] = $remark;
            $data['frommid']= $frommid;
            $data['paytype']= $paytype;
            $data['uid']    = defined('uid') && !empty(uid)?uid:0;
            $data['createtime'] = time();
            Db::name('member_jichamoneylog')->insert($data);
            return ['status'=>1,'msg'=>''];
        }
    }
    //增加推荐人有效期
    public static function addParentTime($aid = 0,$pid = 0,$newlvid = 0){
        if(getcustom('levelup_add_parent_time',$aid)){

            $plevelinfo = Db::name('member_level')->alias('l')
                ->join('member m','m.levelid=l.id')
                ->where('l.aid',$aid)
                ->where('m.id',$pid)
                ->field('l.id,l.upadd_pidtime_levelids,l.upadd_pidtime_num,m.levelendtime')
                ->find();

            if($plevelinfo['upadd_pidtime_num'] > 0 && $plevelinfo['upadd_pidtime_levelids'] !== '' && $plevelinfo['levelendtime']){
                $upadd_pidtime_levelids = explode(',', $plevelinfo['upadd_pidtime_levelids']);
                if(in_array(0, $upadd_pidtime_levelids) || in_array($newlvid, $upadd_pidtime_levelids)){
                    $paddtime = $plevelinfo['upadd_pidtime_num'] * 86400;
                    Db::name('member')->where('aid',$aid)->where('id',$pid)->inc('levelendtime',$paddtime)->update();
                }
            }
        }
    }

    //增加股东分红额度
    public static function addfenhongmaxnum($aid,$mid,$value,$remark,$mids='',$down_levelid=0){
        if(getcustom('fenhong_max',$aid) && getcustom('fenhong_max_add')){
            if($value==0) return ;
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];
            if($value < 0 && $member['fenhong_max_add'] < $value*-1) return ['status'=>0,'msg'=>'股东分红额度不足'];
            if($value < 0 && $member['fenhong_max_add'] < $value*-1) return ['status'=>0,'msg'=>'股东分红额度不足'];
            $after = $member['fenhong_max_add'] + $value;
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['fenhong_max_add'=>$after]);

            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['down_mids'] = $mids;
            $data['down_levelid'] = $down_levelid;
            $data['fenhong_max'] = $value;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            Db::name('member_fenhong_max_log')->insert($data);
            return ['status'=>1,'msg'=>''];
        }
    }

    /**
     * 生成线下补助
     * @param $order
     * @param $scene 奖励场景 shop商城  maidan买单
     * @param $cycle 计算周期选择：0支付后  1确认收货后
     * 需求文档：https://doc.weixin.qq.com/doc/w3_AT4AYwbFACwCNiyaR5sdcSSS4fsRZ?scode=AHMAHgcfAA0PWjob10AeYAOQYKALU
     * @author: liud
     * @time: 2025/8/8 11:10
     */
    public static function offlineSubsidiesLog($aid,$order,$scene,$cycle){
        if(getcustom('yx_offline_subsidies',$aid)){

            $set = Db::name('offline_subsidies_set')->where('aid',$aid)->find();

            $productids_arr = explode(',',$set['productids']);
            $scene_arr = explode(',',$set['scene']);

            $scene_name = '';
            $ordermoney = 0;
            if($scene == 'shop'){
                $scene_name = '商城订单';

                if(!in_array($scene,$scene_arr)){
                    return;
                }

                $order = Db::name('shop_order')->where('aid',$aid)->where('id',$order['id'])->where('status','in',[1,2,3])->find();
                $og_order = Db::name('shop_order_goods')->where('aid',$aid)->where('orderid',$order['id'])->where('status','in',[1,2,3])->select()->toArray();
                if($og_order){
                    foreach ($og_order as $og){
                        if($set['fwtype'] == 0 || in_array($og['proid'],$productids_arr)){
                            if($set['base'] == 0){
                                //成交金额
                                $ordermoney += $og['real_totalmoney'];
                            }else{
                                //成交利润
                                $ordermoney += $og['real_totalmoney'] - $og['cost_price'] * $og['num'];
                            }
                        }
                    }
                }

                //加上运费
                $ordermoney = bcadd($ordermoney,$order['freight_price'],2);
            }elseif($scene == 'maidan'){
                $scene_name = '买单订单';

                if(!in_array($scene,$scene_arr)){
                    return;
                }

                $order = Db::name('maidan_order')->where('aid',$aid)->where('id',$order['id'])->where('status','in',[1,2,3])->find();
                if($set['base'] == 0){
                    //成交金额
                    $ordermoney = $order['paymoney'];
                }else{
                    //成交利润
                    $ordermoney = $order['paymoney'] - $order['cost_price'];
                }
            }elseif($scene == 'levelup'){
                $scene_name = '升级订单';
                $order = Db::name('member_levelup_order')->where('aid',$aid)->where('id',$order['id'])->where('status','in',[2])->find();
                if($set['base'] == 0){
                    //成交金额
                    $ordermoney = $order['totalprice'];
                }else{
                    //成交利润
                    $ordermoney = $order['totalprice'];
                }
            }

            if(!$set || !$set['status'] || ($set['effective_ratio'] <= 0) || !$order || ($ordermoney <= 0)){
                return;
            }

            if (in_array($scene,['shop','maidan']) && ($set['cycle'] != $cycle)){
                return;
            }

            $cycle_name = $cycle == 0 ? '支付后' : '确认收货后';


            $mid = $order['mid'];

            //获取直推人
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->find();
            $pid = $member['pid'];

            //获取直推人信息
            $p_member = Db::name('member')->where('aid',$aid)->where('id',$pid)->find();

            //有效比例
            $effective_ratio = $set['effective_ratio'] / 100;

            /*****************升级奖励****************************/
            if($scene == 'levelup'){ //已弃用---

                $upgrade_levelid_arr = explode(',',$set['upgrade_levelid']);
                if(in_array('-1',$upgrade_levelid_arr) || in_array($p_member['levelid'],$upgrade_levelid_arr)){

                    //计算升级奖励 发给直推人计算公式:利润X80%X10%
                    $money = round(bcmul(bcmul($ordermoney,$effective_ratio,3),$set['upgrade_ratio'] / 100,3),2);

                    //判断是否发放过
                    $ifff = Db::name('member_offline_subsidies_log')->where('aid',$aid)->where('mid',$pid)->where('type',5)->where('scene',$scene)->where('ordernum',$order['ordernum'])->find();

                    if($money > 0 && !$ifff){

                        //判断获得过多少个升级奖励
                        $upgrad_count = Db::name('member_offline_subsidies_log')->where('aid',$aid)->where('mid',$pid)->where('type',5)->where('status',1)->count();

                        $remark = '(来自['.$member['nickname'].']会员ID:'.$member['id'].')'.'获得升级订单金额：'.$ordermoney.' x '.$set['upgrade_text'].'比例：'.$set['upgrade_ratio'].'% x 有效比例：'.$set['effective_ratio'].'%的'.$set['upgrade_text'].'：'.$money;

                        if($upgrad_count > $set['upgrade_num']){
                            $remark .= '，升级奖励已满'.$set['upgrade_num'].'个，升级奖励发放到爱心基金';
                        }

                        //生成线下补助记录
                        $data = array(
                            'aid' => $aid,
                            'mid' => $pid,
                            'frommid' => $mid,
                            'orderid' => $order['id'],
                            'ordernum' => $order['ordernum'],
                            'scene' => $scene,
                            'type' => 5,
                            'commission' => $money,
                            'remark' => $remark,
                            'createtime' => time(),
                            'status' => 1,
                            'order_money' => $ordermoney,
                            'offline_subsidies_set' => jsonEncode($set)
                        );
                        $offline_subsidies_log_id = Db::name('member_offline_subsidies_log')->insertGetId($data);

                        //当满x升级奖励，再次获得的升级奖励发放到爱心基金
                        if($upgrad_count > $set['upgrade_num']){
                            //发到爱心基金
                            \app\common\Member::addLoveFund($aid,$pid,$money,$remark,$scene,$mid,$offline_subsidies_log_id,$order['ordernum']);

                        }else{

                            //发到升级奖励
                            \app\common\Member::addUpgradeRewards($aid,$pid,$money,$remark,$scene,$mid,$offline_subsidies_log_id,$order['ordernum']);

                            //当满x升级奖励，自定升级到y等级
                            if($set['upgrade_auto_uplevelid'] && $upgrad_count >= $set['upgrade_num']){
                                //满足条件自动升级
                                if($set['upgrade_auto_uplevelid'] != $p_member['levelid']){

                                    Db::name('member')->where('aid', $aid)->where('id', $pid)->update(['levelid' => $set['upgrade_auto_uplevelid'], 'levelendtime' => 0,'levelstarttime' => time()]);

                                    if($set['upgrade_auto_uplevelid'] != $p_member['levelid'] && $p_member['pid'] > 0){
                                        \app\common\Member::uplv($aid,$p_member['pid']);
                                    }

                                    //插入级别变动记录
                                    $level_sort = Db::name('member_level')->where('aid',$aid)->column('sort','id');
                                    $level_type = $level_sort[$set['upgrade_auto_uplevelid']]>$level_sort[$p_member['levelid']]?'0':'1';
                                    $remark =  $level_sort[$set['upgrade_auto_uplevelid']]>$level_sort[$p_member['levelid']]?'线下补助-'.$set['upgrade_text'].' 同步升级':'线下补助-'.$set['upgrade_text'].' 同步降级';
                                    $levelup_order = [
                                        'aid' => $aid,
                                        'mid' => $pid,
                                        'from_mid' => $mid,
                                        'pid'=>$p_member['pid'],
                                        'levelid' => $set['upgrade_auto_uplevelid'],
                                        'title' => '线下补助-'.$set['upgrade_text'].' 同步获取',
                                        'totalprice' => 0,
                                        'createtime' => time(),
                                        'levelup_time' => time(),
                                        'beforelevelid' => $p_member['levelid'],
                                        'form0' => '类型^_^' . $remark,
                                        'platform' => '',
                                        'status' => 2,
                                        'type' => $level_type
                                    ];
                                    Db::name('member_levelup_order')->insert($levelup_order);
                                }
                            }
                        }
                    }
                }

                return;
            }

            /******************通证分红****************************/
            $pass_levelid_arr = explode(',',$set['pass_levelid']);
            if(in_array('-1',$pass_levelid_arr) || in_array($p_member['levelid'],$pass_levelid_arr)){

                //计算通证分红  发给直推人计算公式:利润X80%X10%
                $money = round(bcmul(bcmul($ordermoney,$effective_ratio,1),$set['pass_ratio'] / 100,1),0);

                //判断是否发放过
                $ifff = Db::name('member_offline_subsidies_log')->where('aid',$aid)->where('mid',$pid)->where('type',1)->where('scene',$scene)->where('ordernum',$order['ordernum'])->find();

                if($money > 0 && !$ifff){

                    $remark = '(来自['.$member['nickname'].']会员ID:'.$member['id'].')'.$scene_name.$cycle_name.'获得订单金额：'.$ordermoney.' x '.$set['pass_text'].'比例：'.$set['pass_ratio'].'% x 有效比例：'.$set['effective_ratio'].'%的'.$set['pass_text'].'：'.$money;

                    \app\common\Member::addscore($aid,$pid,$money,$remark,'',$order['bid'],$mid);

                    $data = array(
                        'aid' => $aid,
                        'mid' => $pid,
                        'frommid' => $mid,
                        'orderid' => $order['id'],
                        'ordernum' => $order['ordernum'],
                        'scene' => $scene,
                        'type' => 1,
                        'score' => $money,
                        'remark' => $remark,
                        'createtime' => time(),
                        'status' => 1,
                        'order_money' => $ordermoney,
                        'offline_subsidies_set' => jsonEncode($set)
                    );
                    Db::name('member_offline_subsidies_log')->insertGetId($data);
                }
            }

            /******************推荐奖励****************************/
            $recommend_levelid_arr = explode(',',$set['recommend_levelid']);
            if(in_array('-1',$recommend_levelid_arr) || in_array($p_member['levelid'],$recommend_levelid_arr)){

                //计算推荐奖励 发给直推人计算公式:利润X80%X10%
                $money = round(bcmul(bcmul($ordermoney,$effective_ratio,3),$set['recommend_ratio'] / 100,3),2);

                //判断是否发放过
                $ifff = Db::name('member_offline_subsidies_log')->where('aid',$aid)->where('mid',$pid)->where('type',2)->where('scene',$scene)->where('ordernum',$order['ordernum'])->find();

                if($money > 0 && !$ifff){

                    $remark = '(来自['.$member['nickname'].']会员ID:'.$member['id'].')'.$scene_name.$cycle_name.'获得订单金额：'.$ordermoney.' x '.$set['recommend_text'].'比例：'.$set['recommend_ratio'].'% x 有效比例：'.$set['effective_ratio'].'%的'.$set['recommend_text'].'：'.$money;

                    \app\common\Member::addcommission($aid,$pid,$mid,$money,$remark,1,'offline_subsidies');

                    $data = array(
                        'aid' => $aid,
                        'mid' => $pid,
                        'frommid' => $mid,
                        'orderid' => $order['id'],
                        'ordernum' => $order['ordernum'],
                        'scene' => $scene,
                        'type' => 2,
                        'commission' => $money,
                        'remark' => $remark,
                        'createtime' => time(),
                        'status' => 1,
                        'order_money' => $ordermoney,
                        'offline_subsidies_set' => jsonEncode($set)
                    );
                    Db::name('member_offline_subsidies_log')->insertGetId($data);
                }
            }

            /*****************讲师奖励****************************/
            $lecturer_levelid_arr = explode(',',$set['lecturer_levelid']);
            if(in_array('-1',$lecturer_levelid_arr) || in_array($p_member['levelid'],$lecturer_levelid_arr)){

                //计算讲师奖励 发给直推人计算公式:利润X80%X10%
                $money = round(bcmul(bcmul($ordermoney,$effective_ratio,3),$set['lecturer_ratio'] / 100,3),2);

                //判断是否发放过
                $ifff = Db::name('member_offline_subsidies_log')->where('aid',$aid)->where('mid',$pid)->where('type',3)->where('scene',$scene)->where('ordernum',$order['ordernum'])->find();

                if($money > 0 && !$ifff){

                    $remark = '(来自['.$member['nickname'].']会员ID:'.$member['id'].')'.$scene_name.$cycle_name.'获得订单金额：'.$ordermoney.' x '.$set['lecturer_text'].'比例：'.$set['lecturer_ratio'].'% x 有效比例：'.$set['effective_ratio'].'%的'.$set['lecturer_text'].'：'.$money;

                    \app\common\Member::addcommission($aid,$pid,$mid,$money,$remark,1,'offline_subsidies');

                    $data = array(
                        'aid' => $aid,
                        'mid' => $pid,
                        'frommid' => $mid,
                        'orderid' => $order['id'],
                        'ordernum' => $order['ordernum'],
                        'scene' => $scene,
                        'type' => 3,
                        'commission' => $money,
                        'remark' => $remark,
                        'createtime' => time(),
                        'status' => 1,
                        'order_money' => $ordermoney,
                        'offline_subsidies_set' => jsonEncode($set)
                    );
                    Db::name('member_offline_subsidies_log')->insertGetId($data);
                }
            }

            /*****************爱心基金****************************/
            $lovefund_levelid_arr = explode(',',$set['lovefund_levelid']);
            if(in_array('-1',$lovefund_levelid_arr) || in_array($p_member['levelid'],$lovefund_levelid_arr)){

                //计算爱心基金 发给直推人计算公式:利润X80%X10%
                $money = round(bcmul(bcmul($ordermoney,$effective_ratio,3),$set['lovefund_ratio'] / 100,3),2);

                //判断是否发放过
                $ifff = Db::name('member_offline_subsidies_log')->where('aid',$aid)->where('mid',$pid)->where('type',4)->where('scene',$scene)->where('ordernum',$order['ordernum'])->find();

                if($money > 0 && !$ifff){

                    $remark = '(来自['.$member['nickname'].']会员ID:'.$member['id'].')'.$scene_name.$cycle_name.'获得订单金额：'.$ordermoney.' x '.$set['lovefund_text'].'比例：'.$set['lovefund_ratio'].'% x 有效比例：'.$set['effective_ratio'].'%的'.$set['lovefund_text'].'：'.$money;

                    $data = array(
                        'aid' => $aid,
                        'mid' => $pid,
                        'frommid' => $mid,
                        'orderid' => $order['id'],
                        'ordernum' => $order['ordernum'],
                        'scene' => $scene,
                        'type' => 4,
                        'commission' => $money,
                        'remark' => $remark,
                        'createtime' => time(),
                        'status' => 1,
                        'order_money' => $ordermoney,
                        'offline_subsidies_set' => jsonEncode($set)
                    );
                    $offline_subsidies_log_id = Db::name('member_offline_subsidies_log')->insertGetId($data);

                    \app\common\Member::addLoveFund($aid,$pid,$money,$remark,$scene,$mid,$offline_subsidies_log_id,$order['ordernum']);
                }
            }

            /*****************升级奖励****************************/
            $upgrade_levelid_arr = explode(',',$set['upgrade_levelid']);
            if(in_array('-1',$upgrade_levelid_arr) || in_array($p_member['levelid'],$upgrade_levelid_arr)){

                //计算升级奖励 发给直推人计算公式:利润X80%X10%
                $money = round(bcmul(bcmul($ordermoney,$effective_ratio,3),$set['upgrade_ratio'] / 100,3),2);

                //判断是否发放过
                $ifff = Db::name('member_offline_subsidies_log')->where('aid',$aid)->where('mid',$pid)->where('type',5)->where('scene',$scene)->where('ordernum',$order['ordernum'])->find();

                if($money > 0 && !$ifff){

                    //判断获得过多少个升级奖励
                    //$upgrad_count = Db::name('member_offline_subsidies_log')->where('aid',$aid)->where('mid',$pid)->where('type',5)->where('status',1)->count();
                    $upgrad_count = Db::name('member_offline_subsidies_log')->where('aid',$aid)->where('mid',$pid)->where('type',5)->where('status',1)->sum('commission');

                    $remark = '(来自['.$member['nickname'].']会员ID:'.$member['id'].')'.$scene_name.$cycle_name.'获得订单金额：'.$ordermoney.' x '.$set['upgrade_text'].'比例：'.$set['upgrade_ratio'].'% x 有效比例：'.$set['effective_ratio'].'%的'.$set['upgrade_text'].'：'.$money;

                    if($upgrad_count > $set['upgrade_num']){
                        $remark .= '，升级奖励已满'.$set['upgrade_num'].'个，升级奖励发放到爱心基金';
                    }

                    //生成线下补助记录
                    $data = array(
                        'aid' => $aid,
                        'mid' => $pid,
                        'frommid' => $mid,
                        'orderid' => $order['id'],
                        'ordernum' => $order['ordernum'],
                        'scene' => $scene,
                        'type' => 5,
                        'commission' => $money,
                        'remark' => $remark,
                        'createtime' => time(),
                        'status' => 1,
                        'order_money' => $ordermoney,
                        'offline_subsidies_set' => jsonEncode($set)
                    );
                    $offline_subsidies_log_id = Db::name('member_offline_subsidies_log')->insertGetId($data);

                    //当满x升级奖励，再次获得的升级奖励发放到爱心基金
                    if($upgrad_count > $set['upgrade_num']){
                        //发到爱心基金
                        \app\common\Member::addLoveFund($aid,$pid,$money,$remark,$scene,$mid,$offline_subsidies_log_id,$order['ordernum']);

                    }else{

                        //发到升级奖励
                        \app\common\Member::addUpgradeRewards($aid,$pid,$money,$remark,$scene,$mid,$offline_subsidies_log_id,$order['ordernum']);

                        //判断获得过多少个升级奖励
                        //$upgrad_count2 = Db::name('member_offline_subsidies_log')->where('aid',$aid)->where('mid',$pid)->where('type',5)->where('status',1)->count();
                        $upgrad_count2 = Db::name('member_offline_subsidies_log')->where('aid',$aid)->where('mid',$pid)->where('type',5)->where('status',1)->sum('commission');

                        //当满x升级奖励，自定升级到y等级
                        if($set['upgrade_auto_uplevelid'] && $upgrad_count2 >= $set['upgrade_num']){
                            //满足条件自动升级
                            if($set['upgrade_auto_uplevelid'] != $p_member['levelid']){

                                Db::name('member')->where('aid', $aid)->where('id', $pid)->update(['levelid' => $set['upgrade_auto_uplevelid'], 'levelendtime' => 0,'levelstarttime' => time()]);

                                if($set['upgrade_auto_uplevelid'] != $p_member['levelid'] && $p_member['pid'] > 0){
                                    \app\common\Member::uplv($aid,$p_member['pid']);
                                }

                                //插入级别变动记录
                                $level_sort = Db::name('member_level')->where('aid',$aid)->column('sort','id');
                                $level_type = $level_sort[$set['upgrade_auto_uplevelid']]>$level_sort[$p_member['levelid']]?'0':'1';
                                $remark =  $level_sort[$set['upgrade_auto_uplevelid']]>$level_sort[$p_member['levelid']]?'线下补助-'.$set['upgrade_text'].' 同步升级':'线下补助-'.$set['upgrade_text'].' 同步降级';
                                $levelup_order = [
                                    'aid' => $aid,
                                    'mid' => $pid,
                                    'from_mid' => $mid,
                                    'pid'=>$p_member['pid'],
                                    'levelid' => $set['upgrade_auto_uplevelid'],
                                    'title' => '线下补助-'.$set['upgrade_text'].' 同步获取',
                                    'totalprice' => 0,
                                    'createtime' => time(),
                                    'levelup_time' => time(),
                                    'beforelevelid' => $p_member['levelid'],
                                    'form0' => '类型^_^' . $remark,
                                    'platform' => '',
                                    'status' => 2,
                                    'type' => $level_type
                                ];
                                Db::name('member_levelup_order')->insert($levelup_order);
                            }
                        }
                    }
                }
            }

            /*****************业绩奖励****************************/
            //累计消费
            $z_xiaofei = 0;
            if(in_array('shop',$scene_arr)){
                //计算商城消费
                $og_order_list = Db::name('shop_order_goods')->where('aid',$aid)->where('mid',$order['mid'])->where('status','in',[1,2,3])->field('real_totalmoney')->select()->toArray();
                if($og_order_list){
                    foreach ($og_order_list as $og){
                        if($set['fwtype'] == 0 || in_array($og['proid'],$productids_arr)){
                            $z_xiaofei += $og['real_totalmoney'];
                        }
                    }
                }
            }
            if(in_array('maidan',$scene_arr)){
                //计算买单消费
                $maidan_order_list = Db::name('maidan_order')->where('aid',$aid)->where('mid',$order['mid'])->where('status','in',[1,2,3])->field('paymoney')->select()->toArray();
                if($maidan_order_list){
                    foreach ($maidan_order_list as $og){
                        $z_xiaofei += $og['paymoney'];
                    }
                }
            }

            $yejireward_lj_ratio = 0;

            //取出符合累计业绩的阶梯配置
            $yejireward_limit_arr = json_decode($set['yejireward_limit'],true);
            if($yejireward_limit_arr){
                //把数组按照奖励最大的排序
                usort($yejireward_limit_arr, function($a, $b) {
                    if ($a['yejireward_lj'] == $b['yejireward_lj']) return 0;
                    return ($a['yejireward_lj'] > $b['yejireward_lj']) ? -1 : 1;
                });
                foreach ($yejireward_limit_arr as $vg){
                    if($vg['yejireward_lj'] && $vg['yejireward_lj_ratio'] && $z_xiaofei >= $vg['yejireward_lj']){
                        $yejireward_lj_ratio = $vg['yejireward_lj_ratio'];
                        break;
                    }
                }
            }

            //如果在阶梯配置中取到比例 才发奖励
            if($yejireward_lj_ratio > 0) {
                $yejireward_lj_ratio_gs = $yejireward_lj_ratio / 100;

                //计算业绩奖励
                $money = round(bcmul(bcmul(bcmul($ordermoney, $effective_ratio, 3), $set['yejireward_ratio'] / 100, 3), $yejireward_lj_ratio_gs, 3), 2);

                $remark = '(来自['.$member['nickname'].']会员ID:'.$member['id'].')'.$scene_name.$cycle_name.'获得订单金额：'.$ordermoney.' x '.$set['yejireward_text'].'比例：'.$set['yejireward_ratio'].'% x阶梯比例：'.$yejireward_lj_ratio.'% x 有效比例：'.$set['effective_ratio'].'%的'.$set['yejireward_text'].'：'.$money.'。业绩：'.$z_xiaofei;

                //判断是否发放过
                $ifff = Db::name('member_offline_subsidies_log')->where('aid',$aid)->where('mid',$pid)->where('type',6)->where('scene',$scene)->where('ordernum',$order['ordernum'])->find();

                if($money > 0 && !$ifff){

                    \app\common\Member::addcommission($aid,$pid,$mid,$money,$remark,1,'offline_subsidies');

                    $data = array(
                        'aid' => $aid,
                        'mid' => $pid,
                        'frommid' => $mid,
                        'orderid' => $order['id'],
                        'ordernum' => $order['ordernum'],
                        'scene' => $scene,
                        'type' => 6,
                        'commission' => $money,
                        'remark' => $remark,
                        'createtime' => time(),
                        'status' => 1,
                        'order_money' => $ordermoney,
                        'offline_subsidies_set' => jsonEncode($set)
                    );
                    Db::name('member_offline_subsidies_log')->insertGetId($data);
                }
            }

            /*******************线下补助*****************************/
            $offline_zhitui_levelid_arr = explode(',',$set['offline_zhitui_levelid']);
            if(in_array('-1',$offline_zhitui_levelid_arr) || in_array($p_member['levelid'],$offline_zhitui_levelid_arr)){

                //计算线下补助直推奖
                $money = round(bcmul(bcmul(bcmul($ordermoney,$effective_ratio,3),$set['offline_ratio'] / 100,3),$set['offline_zhitui_ratio'] / 100,3),2);

                //判断是否发放过
                $ifff = Db::name('member_offline_subsidies_log')->where('aid',$aid)->where('mid',$pid)->where('type',8)->where('scene',$scene)->where('ordernum',$order['ordernum'])->find();

                if($money > 0 && !$ifff){

                    $remark = '(来自['.$member['nickname'].']会员ID:'.$member['id'].')'.$scene_name.$cycle_name.'获得订单金额：'.$ordermoney.' x '.$set['offline_text'].'比例：'.$set['offline_ratio'].'% x直推奖比例：'.$set['offline_zhitui_ratio'].'% x 有效比例：'.$set['effective_ratio'].'%的'.$set['offline_text'].'直推奖：'.$money;

                    \app\common\Member::addcommission($aid,$pid,$mid,$money,$remark,1,'offline_subsidies');

                    $data = array(
                        'aid' => $aid,
                        'mid' => $pid,
                        'frommid' => $mid,
                        'orderid' => $order['id'],
                        'ordernum' => $order['ordernum'],
                        'scene' => $scene,
                        'type' => 8,
                        'commission' => $money,
                        'remark' => $remark,
                        'createtime' => time(),
                        'status' => 1,
                        'order_money' => $ordermoney,
                        'offline_subsidies_set' => jsonEncode($set)
                    );
                    Db::name('member_offline_subsidies_log')->insertGetId($data);
                }
            }

            /*******************公司运营*****************************/
            $money = round(bcmul(bcmul($ordermoney,$effective_ratio,3),$set['company_ratio'] / 100,3),2);

            //判断是否发放过
            $ifff = Db::name('member_offline_subsidies_log')->where('aid',$aid)->where('mid',0)->where('type',9)->where('scene',$scene)->where('ordernum',$order['ordernum'])->find();

            if($money > 0 && !$ifff){

                $remark = '(来自['.$member['nickname'].']会员ID:'.$member['id'].')'.$scene_name.$cycle_name.'获得订单金额：'.$ordermoney.' x '.$set['company_text'].'比例：'.$set['company_ratio'].'% x 有效比例：'.$set['effective_ratio'].'%的'.$set['company_text'].'：'.$money;

                $data = array(
                    'aid' => $aid,
                    'mid' => 0,
                    'frommid' => $mid,
                    'orderid' => $order['id'],
                    'ordernum' => $order['ordernum'],
                    'scene' => $scene,
                    'type' => 9,
                    'money' => $money,
                    'remark' => $remark,
                    'createtime' => time(),
                    'status' => 0,
                    'order_money' => $ordermoney,
                    'offline_subsidies_set' => jsonEncode($set)
                );
                Db::name('member_offline_subsidies_log')->insertGetId($data);
            }

            /*******************技术服务*****************************/
            $money = round(bcmul(bcmul($ordermoney,$effective_ratio,3),$set['technology_ratio'] / 100,3),2);

            //判断是否发放过
            $ifff = Db::name('member_offline_subsidies_log')->where('aid',$aid)->where('mid',0)->where('type',10)->where('scene',$scene)->where('ordernum',$order['ordernum'])->find();

            if($money > 0 && !$ifff){

                $remark = '(来自['.$member['nickname'].']会员ID:'.$member['id'].')'.$scene_name.$cycle_name.'获得订单金额：'.$ordermoney.' x '.$set['technology_text'].'比例：'.$set['technology_ratio'].'% x 有效比例：'.$set['effective_ratio'].'%的'.$set['technology_text'].'：'.$money;

                $data = array(
                    'aid' => $aid,
                    'mid' => 0,
                    'frommid' => $mid,
                    'orderid' => $order['id'],
                    'ordernum' => $order['ordernum'],
                    'scene' => $scene,
                    'type' => 10,
                    'money' => $money,
                    'remark' => $remark,
                    'createtime' => time(),
                    'status' => 0,
                    'order_money' => $ordermoney,
                    'offline_subsidies_set' => jsonEncode($set)
                );
                Db::name('member_offline_subsidies_log')->insertGetId($data);
            }


            return ['status'=>1,'msg'=>''];
        }
    }

    /**
     * 爱心基金变动
     * @author: liud
     * @time: 2025/8/8 16:25
     */
    public static function addLoveFund($aid,$mid,$value,$remark,$type,$from_mid=0,$offline_subsidies_log_id,$ordernum=''){
        if(getcustom('yx_offline_subsidies',$aid)){
            if($value==0) return ;
            $set = Db::name('offline_subsidies_set')->where('aid',$aid)->find();
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];
            if($value < 0 && $member['love_fund'] < $value*-1) return ['status'=>0,'msg'=>$set['lovefund_text'].'额度不足'];
            $after = $member['love_fund'] + $value;
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['love_fund'=>$after]);

            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['money'] = $value;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            $data['type'] = $type;
            $data['from_mid'] = $from_mid;
            $data['ordernum'] = $ordernum;
            $data['offline_subsidies_log_id'] = $offline_subsidies_log_id ??0;
            Db::name('member_love_fund_log')->insert($data);
            return ['status'=>1,'msg'=>''];
        }
    }

    /**
     * 升级奖励变动
     * @author: liud
     * @time: 2025/8/8 16:25
     */
    public static function addUpgradeRewards($aid,$mid,$value,$remark,$type,$from_mid=0,$offline_subsidies_log_id,$ordernum=''){
        if(getcustom('yx_offline_subsidies',$aid)){
            if($value==0) return ;
            $set = Db::name('offline_subsidies_set')->where('aid',$aid)->find();
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];
            if($value < 0 && $member['upgrade_rewards'] < $value*-1) return ['status'=>0,'msg'=>$set['upgrade_text'].'额度不足'];
            $after = $member['upgrade_rewards'] + $value;
            Db::name('member')->where('aid',$aid)->where('id',$mid)->update(['upgrade_rewards'=>$after]);

            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['money'] = $value;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            $data['type'] = $type;
            $data['from_mid'] = $from_mid;
            $data['ordernum'] = $ordernum;
            $data['offline_subsidies_log_id'] = $offline_subsidies_log_id ??0;
            Db::name('member_upgrade_rewards_log')->insert($data);
            return ['status'=>1,'msg'=>''];
        }
    }

    public static function offlineAreafenhong($aid,$mid,$ordermoney,$log_id){
        if(getcustom('yx_offline_subsidies',$aid)){
            //获取用户分红级别
            $where = [];
            $where[] = ['m.aid','=',$aid];
            $where[] = ['m.id','=',$mid];
            $where[] = ['m.areafenhong','<',4];
            $dowmids = Db::name('member')->alias('m')
                ->leftJoin('member_level l','l.id = m.levelid')
                ->field('m.id,m.areafenhong as m_areafenhong,m.areafenhong_province,m.areafenhong_city,m.areafenhong_area,l.areafenhong')
                ->where($where)
                ->find();
            if($dowmids){
                if($dowmids['m_areafenhong'] > 0){
                    $areafenhong = $dowmids['m_areafenhong'];
                }else if($dowmids['areafenhong'] > 0){
                    $areafenhong = $dowmids['areafenhong'];
                }
            }

            $set = Db::name('offline_subsidies_set')->where('aid',$aid)->find();
            if(!$set || !$set['status'] || ($set['effective_ratio'] <= 0)){
                return;
            }

            //有效比例
            $effective_ratio = $set['effective_ratio'] / 100;

            $offline_areafenhong_ratio = 0;
            $areafenhong_name = '';
            if($areafenhong == 1){
                $offline_areafenhong_ratio = $set['offline_provincefenhong_ratio'] ?? 0;
                $areafenhong_name = '省代分红';
            }elseif($areafenhong == 2){
                $offline_areafenhong_ratio = $set['offline_cityfenhong_ratio'] ?? 0;
                $areafenhong_name = '市代分红';
            }else if($areafenhong == 3){
                $offline_areafenhong_ratio = $set['offline_areafenhong_ratio'] ?? 0;
                $areafenhong_name = '区县分红';
            }

            /*******************线下补助*****************************/
            $money = round(bcmul(bcmul($ordermoney,$effective_ratio,3),$offline_areafenhong_ratio / 100,3),2);

            //判断是否发放过
            $ifff = Db::name('member_offline_subsidies_log')->where('aid',$aid)->where('mid',$mid)->where('type',8)->where('scene','area')->where('orderid',$log_id)->find();

            if($money > 0 && !$ifff){

                $remark = '获得'.$areafenhong_name.'：'.$ordermoney.' x '.'比例：'.$offline_areafenhong_ratio.'% x 有效比例：'.$set['effective_ratio'].'%的'.$set['offline_text'].'：'.$money;

                $data = array(
                    'aid' => $aid,
                    'mid' => $mid,
                    'frommid' => 0,
                    'orderid' => $log_id,
                    'scene' => 'area',
                    'type' => 8,
                    'money' => $money,
                    'remark' => $remark,
                    'createtime' => time(),
                    'status' => 0,
                    'order_money' => $ordermoney,
                    'offline_subsidies_set' => jsonEncode($set)
                );
                Db::name('member_offline_subsidies_log')->insertGetId($data);
            }
        }
    }
    //押金操作
    //$param 其他扩展字段
    public static function addDeposit($aid,$bid,$mid,$num,$deposit_id,$remark='',$param = []){
        if(getcustom('deposit')){
            if($num==0) return ;
            $member = Db::name('member')->where('aid',$aid)->where('id',$mid)->lock(true)->find();
            if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];
            
            $deposit = Db::name('deposit')->where('aid',$aid)->where('id',$deposit_id)->find();
            if(!$deposit) return ['status'=>0,'msg'=>t('押金').'不存在'];
            
            $deposit_member = Db::name('deposit_member')->where('aid',$aid)->where('mid',$mid)->where('deposit_id',$deposit_id)->find();
            if(!$deposit_member){
                if($num > 0){
                    $deposit_member = [
                        'aid' => $aid,
                        'bid' =>$bid,
                        'mid' => $mid,
                        'deposit_id' => $deposit_id,
                        'name' => $deposit['name'],
                        'num' => abs($num),
                        'money' => $deposit['money'],
                        'totalmoney' => $deposit['money'] * abs($num) ,
                        'createtime' => time()
                    ];
                    Db::name('deposit_member')->insertGetId($deposit_member);
                }else{
                     return false;
                }
            }else{
                $update_num = $deposit_member['num'] + $num;
                $totalmoney = $update_num * $deposit_member['money'];
                $update = [
                    'num' =>   $update_num,
                    'totalmoney' => $totalmoney
                ];
                Db::name('deposit_member')->where('aid',$aid)->where('mid',$mid)->where('id',$deposit_member['id'])->update($update);
            }
            
            //增加日志
            $log = [
                'aid' => $aid,
                'bid' => $bid??0,
                'mid' => $mid,
                'deposit_id' => $deposit_id,
                'name' => $deposit_member['name'],
                'num' => $num,
                'from_type' => $param['from_type']??'shop',
                'type' => $num > 0?'0':1,
                'money' => $deposit_member['money'],
                'totalmoney' => dd_money_format($num * $deposit_member['money']),
                'orderid' => $param['orderid']??0,
                'remark' => $remark,
                'status' => 1,
                'createtime' => time(),
            ];
            Db::name('deposit_log')->insertGetId($log);
        }
    }

    //退款降级
    public static function refundNextLevel($aid, $mid) {
        if (getcustom('refund_update_level')) {
            $member = Db::name('member')->where('aid', $aid)->where('id', $mid)->find();
            if (!$member || empty($member['id']) || empty($member['levelid'])) return false;

            // 判断是否手动升级
            $latestUpgrade = Db::name('member_levelup_order')
                ->where('aid', $aid)
                ->where('mid', $mid)
                ->where('levelid', $member['levelid'])
                ->where('type', 0)
                ->where('status', 2)
                ->order('createtime desc')
                ->find();

            if ($latestUpgrade && $latestUpgrade['title'] == t('后台修改')) {
                return false;
            }

            //获取最后一次升级时间 排除当前等级
            $down_level_time = Db::name('member_levelup_order')
                ->where('mid', $mid)
                ->where('type', 0)
                ->where('levelid','<>',$member['levelid'])
                ->order('createtime desc')
                ->value('createtime') ?: 0;

            //重新计算业绩 扣除退款订单金额
            $wxpaylog = Db::name('wxpay_log')
                    ->where('aid', $aid)->where('mid', $mid)
                    ->where('createtime', '>=', $down_level_time)
                    ->field('sum(total_fee - refund_money) as total_fee')
                    ->find();

            $wxpaymoney = 0 + ($wxpaylog['total_fee'] ?? 0);

            $shoporder = Db::name('shop_order')
                    ->where('aid', $aid)->where('mid', $mid)
                    ->where('status', 'in', [1,2,3])
                    ->where('paytime', '>=', $down_level_time)
                    ->field('sum(totalprice - refund_money) as totalprice')
                    ->find();
            $ordermoney = 0 + ($shoporder['totalprice'] ?? 0);

            if (!empty($member['import_yeji'])) {
                $ordermoney = bcadd($ordermoney, $member['import_yeji']);
            }

            $rechargemoney = 0 + Db::name('recharge_order')
                    ->where('aid', $aid)->where('mid', $mid)
                    ->where('status', 1)
                    ->where('createtime', '>=', $down_level_time)
                    ->sum('money');

            //查询最低等级
            $levelInfo = Db::name('member_level')->where('aid', $aid)->where('id',$member['levelid'])->find();
            $defaultMember = $member;
            $defaultLevel = Db::name('member_level')->where('aid', $aid)->where('isdefault', 1)->find();
            $defaultMember['levelid'] = $defaultLevel['id'];
            $newLevel = self::upLevel($aid, $member, $defaultMember, $ordermoney, $wxpaymoney, $rechargemoney, 0, 0, ['refund_mode' => true]);

            //判断是否需要降级
            $rs = false;
            if ($newLevel && $newLevel['sort'] < $levelInfo['sort'] && $newLevel['id'] != $member['levelid']) {
                $levelendtime = $newLevel['yxqdate'] > 0 ? (strtotime(date('Y-m-d')) + 86400 * ($newLevel['yxqdate'] + 1)) : 0;

                Db::name('member')->where('aid', $aid)->where('id', $mid)->update([
                    'levelid' => $newLevel['id'],
                    'levelstarttime' => time(),
                    'levelendtime' => $levelendtime
                ]);

                Db::name('member_levelup_order')->insert([
                    'aid'           => $aid,
                    'mid'           => $mid,
                    'from_mid'      => $mid,
                    'pid'           => $member['pid'],
                    'levelid'       => $newLevel['id'],
                    'beforelevelid' => $member['levelid'],
                    'title'         => '退款降级',
                    'totalprice'    => 0,
                    'createtime'    => time(),
                    'levelup_time'  => time(),
                    'type'          => 1,
                    'form0'         => '类型^_^退款降级',
                    'platform'      => '',
                    'status'        => 2
                ]);
                $rs = true;
            }
            if($member['pid']){
                self::refundNextLevel($aid,$member['pid']);
            }
            return $rs;
        }
    }
    //加消费赠送积分记录2
    public static function goldbeaninlog($aid,$bid,$mid,$type,$orderid,$ordernum,$score){
        if(getcustom('gold_bean')){
            $data = [];
            $data['aid']      = $aid;
            $data['bid']      = $bid;
            $data['mid']      = $mid;
            $data['type']     = $type;
            $data['orderid']  = $orderid;
            $data['ordernum'] = $ordernum;
            $data['gold_bean']    = $score;//赠送积分
            $data['residue']  = $score;//剩余赠送积分（扣除时用）
            $data['createtime'] = time();
            Db::name('member_gold_bean_inlog')->insert($data);
            return ['status'=>1,'msg'=>''];
        }
    }
    //扣除消费赠送积分
    public static function decgoldbeanin($aid,$type,$orderid,$ordernum,$remark='订单退款扣除消费赠送'){
       if(getcustom('gold_bean')) {
           // 开了积分释放功能，不再扣除
           $where = [];
           $where[] = ['orderid', '=', $orderid];
           $where[] = ['ordernum', '=', $ordernum];
           $where[] = ['type', '=', $type];
           $where[] = ['residue', '>', 0];
           $where[] = ['gold_bean', '>', 0];
           $where[] = ['aid', '=', $aid];
           $scoreinlogs = Db::name('member_gold_bean_inlog')->where($where)->select()->toArray();
           if ($scoreinlogs) {
               foreach ($scoreinlogs as $log) {
                   $res = self::addgoldbean($aid, $log['mid'], -$log['residue'], $remark, $type, 0, ['canminus' => true]);
                   if ($res && $res['status'] == 1) {
                       //修改赠送记录
                       Db::name('member_gold_bean_inlog')->where('id', $log['id'])->update(['residue' => 0, 'updatetime' => time()]);
                   }
               }
               unset($log);
           }
       }
    }
}
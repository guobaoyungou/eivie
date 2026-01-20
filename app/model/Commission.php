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


namespace app\model;

use think\Model;
use think\facade\Db;

class Commission extends Model
{
    //小市场业绩，去除业绩最高的一条线的业绩，其他的算小市场业绩
    public static function getMiniTeamCommission($aid,$mid,$starttime='',$endtime='')
    {
        if (getcustom('member_level_salary_bonus',$aid) || getcustom('team_minyeji_count',$aid)) {
            //直推会员
            $ztmembers = Db::name('member')->where('aid', $aid)->where('pid', $mid)->field('id,levelid,pid')->select()->toArray();
            //去除最高业绩的小部门业绩
            $maxYeji = 0;
            $totalYeji = 0;
            writeLog('-----'.$mid.'-----');
            foreach ($ztmembers as $ztk => $ztmember) {
                //直推部门业绩
                $yejiwhere = [];
                $yejiwhere[] = ['status', '=', 3];
                if($starttime || $endtime){
                    if($starttime){
                        $yejiwhere[] = ['collect_time', '>', $starttime];
                    }
                    if($endtime){
                        $yejiwhere[] = ['collect_time', '<', $endtime];
                    }
                }else{
                    $yejiwhere[] = ['collect_time', '<', time()];
                }
                $downmids = \app\common\Member::getteammids($aid, $ztmember['id']);
                $downmids[] = $ztmember['id'];
                if (empty($downmids)) {
                    continue;
                }
                $sumResult = Db::name('shop_order')->where('aid', $aid)->where('mid', 'in', $downmids)->where($yejiwhere)->field("sum(`totalprice`-`refund_money`) as totalamount")->find();
//                    dump(['amount'=>$sumResult['totalamount'],'mid'=>$ztmember['id']]);
                $teamYeji = $sumResult['totalamount'] ?round($sumResult['totalamount'],2): 0;
                writeLog('mid='.$ztmember['id'].'&amount='.$teamYeji);
                if ($teamYeji > $maxYeji) {
                    $maxYeji = $teamYeji;
                }
                $totalYeji = $totalYeji + $teamYeji;
            }
            $yejiAmount = round($totalYeji - $maxYeji,2);//去掉最大部门业绩算小部门业绩
            writeLog('-----'.$mid.'-----');
            return $yejiAmount;
        }
        return 0;
    }

    /**
     * 获取大、小市场业绩及团队业绩
     * 团队业绩包含会员自己
     */
    public static function getTeamYeji($aid,$mid,$starttime='',$endtime='')
    {
        if (getcustom('team_minyeji_count',$aid)) {
            $yejiwhere = [];
            $yejiwhere[] = ['status', 'in', [1,2,3]];
            if($starttime || $endtime){
                if($starttime){
                    $yejiwhere[] = ['createtime', '>', $starttime];
                }
                if($endtime){
                    $yejiwhere[] = ['createtime', '<', $endtime];
                }
            }else{
                $yejiwhere[] = ['createtime', '<', time()];
            }

            //直推会员
            $ztmembers = Db::name('member')->where('aid', $aid)->where('pid', $mid)->field('id,levelid,pid')->select()->toArray();
            //去除最高业绩的小部门业绩
            $maxYeji = 0;
            $min_yeji = 0;
            $yeji_arr = [];
            writeLog('-----'.$mid.'-----');
            foreach ($ztmembers as $ztk => $ztmember) {
                //直推部门业绩
                $downmids = \app\common\Member::getteammids($aid, $ztmember['id']);
                $downmids[] = $ztmember['id'];
                if (empty($downmids)) {
                    continue;
                }
                $totalamount = Db::name('shop_order_goods')->where('aid', $aid)->where('mid', 'in', $downmids)->where($yejiwhere)->sum('real_totalprice');
                $teamYeji = $totalamount?: 0;
                writeLog('mid='.$ztmember['id'].'&amount='.$teamYeji);
                $yeji_arr[] = $teamYeji;
            }
            if($yeji_arr){
                $maxYeji = max($yeji_arr);
                $totalYeji = array_sum($yeji_arr);
                $min_yeji = bcsub($totalYeji, $maxYeji,2);
            }
            //单独计算一次所有会员的团队业绩
            $downmids = \app\common\Member::getteammids($aid, $mid);
            $totalamount = Db::name('shop_order_goods')->where('aid', $aid)->where('mid', 'in', $downmids)->where($yejiwhere)->sum('real_totalprice');
//                    dump(['amount'=>$sumResult['totalamount'],'mid'=>$ztmember['id']]);
            $teamYeji = $totalamount?: 0;

            $self_yeji = Db::name('shop_order_goods')->where('aid', $aid)->where('mid', $mid)->where($yejiwhere)->sum('real_totalprice');
            $self_yeji = $self_yeji?: 0;
            $teamYeji = bcadd($teamYeji, $self_yeji,2);
            writeLog('-----'.$mid.'-----');
            return [
                'max_yeji' => $maxYeji,
                'total_yeji' => $teamYeji,
                'min_yeji' => $min_yeji,
                'self_yeji' => $self_yeji
            ];
        }
        return 0;
    }

    /**
     * 根据佣金记录表里的type获取相应的订单信息
     * $field 里增加需要的字段
     * @author: liud
     * @time: 2025/8/1 17:08
     */
    public static function getRecordTypeOrder($aid,$type,$orderid)
    {

        switch ($type){
            case 'shop':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('shop_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'car_hailing':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('car_hailing_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'cashier':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('cashier_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'channels':
                $field = 'order_id as ordernum';//要查询的数据
                $order = Db::name('channels_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'collage':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('collage_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'coupon':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('coupon_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'cycle':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('cycle_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'fishpond':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('fishpond_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'hotel':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('hotel_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'kanjia':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('kanjia_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'kecheng':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('kecheng_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'levelup':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('member_levelup_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'livepay':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('livepay_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'lucky_collage':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('lucky_collage_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'maidan':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('maidan_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'restaurant_shop':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('restaurant_shop_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'restaurant_takeaway':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('restaurant_takeaway_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'scoreshop':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('scoreshop_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'seckill':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('seckill_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'tuangou':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('tuangou_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'water_happyti':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('water_happyti_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
            case 'yuyue':
                $field = 'ordernum';//要查询的数据
                $order = Db::name('yuyue_order')->where('aid',$aid)->where('id',$orderid)->field($field)->find();
                break;
           default :
               $order = [];
        }

        return $order;
    }
}
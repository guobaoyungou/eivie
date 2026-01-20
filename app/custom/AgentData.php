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
use think\facade\Log;

/**
 * 前端我的佣金页面数据查询处理
 */
class AgentData
{

    private $show_now = 1;//0手动加载 1主动加载
    private $aid = 1;
    private $sysset = [];
    private $money_weishu = 2;
    private $commission_weishu = 2;
    private $score_weishu = 0;
    public function __construct($aid=1,$show_now,$sysset=[]){
        $this->aid = intval($aid);
        $this->show_now = (int)$show_now;
        if(!$sysset){
            $sysset = Db::name('admin_set')->where('id',$aid)->find();
        }
        $this->sysset = $sysset;
    }

    //获取佣金页面数据
    public function getCommissionPageData($aid,$mid,$field_name){
        if(!$this->show_now){
            return 0;
        }
        //累计提现
        if($field_name=='count3'){
            $count3 = 0 + Db::name('member_commission_withdrawlog')->where('aid',$aid)->where('mid',$mid)->where('status',3)->sum('txmoney');
            $count3 = number_format($count3,$this->commission_weishu,'.','');
            return $count3;
        }
    }
    //获取累计待结算佣金
    public function getCommissionYj($aid,$mid){
        if(!$this->show_now){
            return 0;
        }
        $commissionyj_collage1 = Db::name('collage_order')->where('aid',$aid)->where('parent1',$mid)->whereIn('status',[1,2,3])->where('iscommission',0)->sum('parent1commission');
        $commissionyj_collage2 = Db::name('collage_order')->where('aid',$aid)->where('parent2',$mid)->whereIn('status',[1,2,3])->where('iscommission',0)->sum('parent2commission');
        $commissionyj_collage3 = Db::name('collage_order')->where('aid',$aid)->where('parent3',$mid)->whereIn('status',[1,2,3])->where('iscommission',0)->sum('parent3commission');
        $commissionyj_score1 = Db::name('scoreshop_order_goods')->where('aid',$aid)->where('parent1',$mid)->whereIn('status',[1,2,3])->where('iscommission',0)->sum('parent1commission');
        $commissionyj_score2 = Db::name('scoreshop_order_goods')->where('aid',$aid)->where('parent2',$mid)->whereIn('status',[1,2,3])->where('iscommission',0)->sum('parent2commission');
        $commissionyj_score3 = Db::name('scoreshop_order_goods')->where('aid',$aid)->where('parent3',$mid)->whereIn('status',[1,2,3])->where('iscommission',0)->sum('parent3commission');
        //ddwx_seckill_order 不存在iscommission字段
        $commissionyj_yuyue1 = Db::name('yuyue_order')->where('aid',$aid)->where('parent1',$mid)->whereIn('status',[1,2,3])->where('iscommission',0)->sum('parent1commission');
        $commissionyj_yuyue2 = Db::name('yuyue_order')->where('aid',$aid)->where('parent2',$mid)->whereIn('status',[1,2,3])->where('iscommission',0)->sum('parent2commission');
        $commissionyj_yuyue3 = Db::name('yuyue_order')->where('aid',$aid)->where('parent3',$mid)->whereIn('status',[1,2,3])->where('iscommission',0)->sum('parent3commission');
        $commissionyj_ke1 = Db::name('kecheng_order')->where('aid',$aid)->where('parent1',$mid)->whereIn('status',[1,2,3])->where('iscommission',0)->sum('parent1commission');
        $commissionyj_ke2 = Db::name('kecheng_order')->where('aid',$aid)->where('parent2',$mid)->whereIn('status',[1,2,3])->where('iscommission',0)->sum('parent2commission');
        $commissionyj_ke3 = Db::name('kecheng_order')->where('aid',$aid)->where('parent3',$mid)->whereIn('status',[1,2,3])->where('iscommission',0)->sum('parent3commission');
        $commissionyj_tuan1 = Db::name('tuangou_order')->where('aid',$aid)->where('parent1',$mid)->whereIn('status',[1,2,3])->where('iscommission',0)->sum('parent1commission');
        $commissionyj_tuan2 = Db::name('tuangou_order')->where('aid',$aid)->where('parent2',$mid)->whereIn('status',[1,2,3])->where('iscommission',0)->sum('parent2commission');
        $commissionyj_tuan3 = Db::name('tuangou_order')->where('aid',$aid)->where('parent3',$mid)->whereIn('status',[1,2,3])->where('iscommission',0)->sum('parent3commission');
        //todo 未插入record的其他佣金
//        $recordyj = Db::name('member_commission_record')->where('aid',aid)->where('mid',mid)->where('type','<>','shop')->where('status',0)->sum('commission');
        $recordyjshop = Db::name('member_commission_record')->alias('r')->leftJoin('shop_order o','r.orderid=o.id')->where('r.aid',$aid)->where('r.mid',$mid)->where('r.type','shop')->where('r.status',0)->where('o.status','in',[1,2,3])->sum('r.commission');
        $commissionyj = 0 /*+ $commissionyj1[0]['c'] + $commissionyj2[0]['c'] + $commissionyj3[0]['c']*/
            + $commissionyj_collage1 + $commissionyj_collage2 + $commissionyj_collage3
            + $commissionyj_score1 + $commissionyj_score2 + $commissionyj_score3
            + $commissionyj_yuyue1 + $commissionyj_yuyue2 + $commissionyj_yuyue3
            + $commissionyj_ke1 + $commissionyj_ke2 + $commissionyj_ke3
            + $commissionyj_tuan1 + $commissionyj_tuan2 + $commissionyj_tuan3
//            + $recordyj
            + $recordyjshop;
        if(getcustom('wx_channels')){
            $channels_status = [20,21,30,100];
            $commissionyj_channels1 = Db::name('channels_order_goods')
                ->alias('og')
                ->join('channels_order o','og.order_id=o.order_id')
                ->where('o.aid',$aid)->where('og.parent1',$mid)->whereIn('o.status',$channels_status)->where('og.iscommission',0)->sum('og.parent1commission');
            $commissionyj_channels2 = Db::name('channels_order_goods')
                ->alias('og')
                ->join('channels_order o','og.order_id=o.order_id')
                ->where('o.aid',$aid)->where('og.parent2',$mid)->whereIn('o.status',$channels_status)->where('og.iscommission',0)->sum('og.parent2commission');
            $commissionyj_channels3 = Db::name('channels_order_goods')
                ->alias('og')
                ->join('channels_order o','og.order_id=o.order_id')
                ->where('o.aid',$aid)->where('og.parent3',$mid)->whereIn('o.status',$channels_status)->where('og.iscommission',0)->sum('og.parent3commission');
            $commissionyj = $commissionyj+$commissionyj_channels1+$commissionyj_channels2+$commissionyj_channels3;
        }
        return dd_money_format($commissionyj,$this->commission_weishu);
    }

    //获取团队业绩
    public function getTeamFenhongYeji($aid,$mid,$userinfo=[]){
        if(getcustom('team_fenhong_yeji',$aid)){
            if(!$this->show_now){
                return 0;
            }
            $downmids = \app\common\Member::getdownmids($aid,$mid);
            if($downmids){
                $yejiwhere = [];
                $yejiwhere[] = ['status','in','1,2,3'];
//                $yejiwhere[] = ['is_bonus','=',1];
                $teamyeji = Db::name('shop_order')->where('aid',$aid)->where('mid','in',$downmids)->where($yejiwhere)->sum('totalprice');
                $team_fenhong_yeji = bcadd($userinfo['import_yeji'],$teamyeji,2);
            }else{
                $team_fenhong_yeji = $userinfo['import_yeji'];
            }
            return dd_money_format($team_fenhong_yeji,$this->commission_weishu);
        }
    }

    //佣金统计
    public function getCommissionCount($aid,$mid,$day_type='today'){
        if(getcustom('member_commission_count',$aid)){
            if(!$this->show_now){
                return 0;
            }
            //今日佣金
            if($day_type=='today'){
                $total_commission = Db::name('member_commissionlog')->where('aid',$aid)->where('mid',$mid)->where('commission','>',0)
                    ->where('createtime','>',strtotime(date('Y-m-d')))->sum('commission');
            }
            //昨日佣金
            if($day_type=='yesterday') {
                $count_etime = strtotime(date('Y-m-d'));
                $count_stime = $count_etime - 86400;
                $total_commission = Db::name('member_commissionlog')->where('aid', $aid)->where('mid', $mid)->where('commission', '>', 0)
                    ->where('createtime', 'between', [$count_stime, $count_etime])->sum('commission');
            }
            //本月佣金
            if($day_type=='month') {
                $total_commission = Db::name('member_commissionlog')->where('aid', $aid)->where('mid', $mid)->where('commission', '>', 0)
                    ->where('createtime', '>', strtotime(date('Y-m-01')))->sum('commission');
            }
            //上月佣金
            if($day_type=='last_month') {
                $count_etime = strtotime(date('Y-m-01'));
                $count_stime = mktime(0, 0, 0, date('m') - 1, 1, date('Y'));
                $total_commission = Db::name('member_commissionlog')->where('aid', $aid)->where('mid', $mid)->where('commission', '>', 0)
                    ->where('createtime', 'between', [$count_stime, $count_etime])->sum('commission');
            }
            return dd_money_format($total_commission,$this->commission_weishu);
        }
    }

    //按类型统计佣金
    public function getCommissionCountBytype($aid,$mid,$commission_type=''){
        if(!$this->show_now){
            return 0;
        }
        if($commission_type=='zt_commission'){
            $commission = Db::name('member_commissionlog')->where('mid',$mid)->where('remark','like','直推奖%')->sum('commission');
        }
        if($commission_type=='yeji_commission'){
            $commission = Db::name('member_commissionlog')->where('mid',$mid)->where('remark','业绩奖')->sum('commission');
        }
        return dd_money_format($commission,$this->commission_weishu);
    }

    //已发分红统计
    public function getFenhongCount($aid,$mid,$fh_type='fenhong'){
        if(!$this->show_now){
            return 0;
        }
        $where_base = [];
        $where_base[] = ['aid','=',$aid];
        $where_base[] = ['mid','=',$mid];
        $where_base[] = ['status','=',1];
        //团队分红
        if($fh_type=='teamfenhong') {
            $fenhong = Db::name('member_fenhonglog')->where($where_base)->where('type', 'in', ['teamfenhong', 'teamfenhong_pj'])->sum('commission');
        }else{
            $fenhong = Db::name('member_fenhonglog')->where($where_base)->where('type', $fh_type)->sum('commission');
        }
        //股东分红
        return dd_money_format($fenhong,$this->commission_weishu);

    }

    //预计发放分红统计
    public function getYjFenhongCount($aid,$mid,$sysset,$fh_type='fenhong'){
        if(!$this->show_now){
            return 0;
        }
        $map = [];
        $map[] = ['aid','=',$aid];
        $map[] = ['mid','=',$mid];
        $map[] = ['status','=',0];
        if($fh_type=='gudong'){
            //股东分红
            $map[] = ['type','=','fenhong'];
            $fenhong_yj = Db::name('member_fenhonglog')->where($map)->sum('commission');
        }
        if($fh_type=='huiben'){
            //回本股东分红(延用老方法获取预收益)
            $rs = \app\common\Fenhong::gdfenhong_huiben($aid,$sysset,[],0,time(),1,$mid);
            $fenhong_yj = $rs['commissionyj'];
        }
        if($fh_type=='team'){
            //团队分红
            $map[] = ['type','in',['teamfenhong','teamfenhong_pj','teamfenhong_bole','product_teamfenhong','level_teamfenhong']];
            $fenhong_yj = Db::name('member_fenhonglog')->where($map)->sum('commission');
        }
        if($fh_type=='area'){
            //区域代理分红
            $map[] = ['type','=','areafenhong'];
            $fenhong_yj = Db::name('member_fenhonglog')->where($map)->sum('commission');
        }
        if($fh_type=='business_teamfenhong'){
            //商家团队分红
            $map[] = ['type','=','business_teamfenhong'];
            $fenhong_yj = Db::name('member_fenhonglog')->where($map)->sum('commission');
        }
        return dd_money_format($fenhong_yj,$this->commission_weishu);
    }

    //获取累计股东分红 已发+未发
    public function getGudongTotal($aid,$mid,$userinfo,$sysset,$fh_type='gudong'){
        if(!$this->show_now){
            return 0;
        }

        $userinfo['fenhong_max_show'] = 1;
        if($userinfo['fenhong_max']<=0){
            $level_info = Db::name('member_level')->where('id',$userinfo['levelid'])->where('aid',$aid)->find();
            $fenhong_max_money = $level_info['fenhong_max_money'];
            if(!empty($sysset['fenhong_max_add'])){
                $down_max_money = Db::name('member_level')
                    ->where('aid',$aid)
                    ->where('sort','<',$level_info['sort'])
                    ->sum('fenhong_max_money');
                $fenhong_max_money = bcadd($fenhong_max_money,$down_max_money,2);
            }
            $userinfo['fenhong_max'] = $fenhong_max_money;
        }
        if($userinfo['fenhong_max']<=0){
            $userinfo['fenhong_max_show'] = 0;
        }

        //查询会员已获得股东分红
        $where_fenhong = [];
        $where_fenhong[] = ['mid','=',$mid];
        $where_fenhong[] = ['type','=',$fh_type];
        $where_fenhong[] = ['status','in',[0,1]];
        $gudong_name = t('股东分红',$aid);
        $fenhong_total = Db::name('member_fenhonglog')
            ->where($where_fenhong)
            //->where('remark like "%'.$gudong_name.'%"')
            ->sum('commission');
        $gudong_total = bcmul($fenhong_total,1,2);
        $gudong_remain = bcsub($userinfo['fenhong_max'],$userinfo['gudong_total'],2);
        return [
            'gudong_total'=>$gudong_total,
            'gudong_remain'=>$gudong_remain>0?$gudong_remain:0,
            'fenhong_max'=>$userinfo['fenhong_max'],
            'fenhong_max_show'=>$userinfo['fenhong_max_show'],
        ];
    }

    //获取股东贡献量分红
    public function getGongxianFenhong($aid,$mid,$member,$sysset){
        if(!$this->show_now){
            return 0;
        }
        $orderwhere = [];
        $orderwhere[] = ['aid','=',$aid];
        $orderwhere[] = ['isfenhong','=',0];
        //if($set['fhjiesuantime_type'] == 1){
        //	$orderwhere[] = ['status','in','1,2,3'];
        //}else{
        //	$orderwhere[] = ['status','=','3'];
        //}
        $orderwhere[] = ['status','in','1,2,3'];
        $real_totalprice = Db::name('shop_order_goods')->where($orderwhere)->sum('real_totalprice');
        if($sysset['fhjiesuantime_type'] == 0){
            $fenhongprice = $real_totalprice;
        }else{
            $cost_price = Db::name('shop_order_goods')->where($orderwhere)->sum('cost_price');
            $num = Db::name('shop_order_goods')->where($orderwhere)->sum('num');
            $fenhongprice = $real_totalprice - $cost_price * $num;
        }

        $fhlevellist = Db::name('member_level')->where('aid',$aid)->where('fenhong','>','0')->order('sort desc,id desc')->column('id,cid,name,fenhong,fenhong_num,fenhong_max_money,sort,fenhong_gongxian_minyeji,fenhong_gongxian_percent','id');
        $lastmidlist = [];
        $total_fenhong_partner = $member['total_fenhong_partner'];
        $commission = 0;
        $defaultCid = Db::name('member_level_category')->where('aid',$aid)->where('isdefault', 1)->value('id');
        foreach($fhlevellist as $fhlevel){
            $where = [];
            $where[] = ['aid', '=', $aid];
            $where[] = ['levelid', '=', $fhlevel['id']];
            $where2 = [];
            $where2[] = ['ml.aid', '=', $aid];
            $where2[] = ['ml.levelid', '=', $fhlevel['id']];
            if ($fhlevel['fenhong_max_money'] > 0) {
                $where[] = ['total_fenhong_partner', '<', $fhlevel['fenhong_max_money']];
                $where2[] = ['m.total_fenhong_partner', '<', $fhlevel['fenhong_max_money']];
            }
            if ($defaultCid > 0 && $defaultCid != $fhlevel['cid']) {

            } else {
                if ($fhlevel['fenhong_num'] > 0) {
                    $midlist = Db::name('member')->where($where)->order('levelstarttime,id')->limit(intval($fhlevel['fenhong_num']))->column('id,total_fenhong_partner', 'id');
                } else {
                    $midlist = Db::name('member')->where($where)->column('id,total_fenhong_partner', 'id');
                }
            }

            if($sysset['partner_jiaquan'] == 1){
                $oldmidlist = $midlist;
                $midlist = array_merge((array)$lastmidlist,(array)$midlist);
                $lastmidlist = array_merge((array)$lastmidlist,(array)$oldmidlist);
            }

            if (!$midlist) continue;

            //股东贡献量分红 开启后可设置一定比例的分红金额按照股东的团队业绩量分红
            $pergxcommon = 0;
            if($sysset['partner_gongxian']==1 && $fhlevel['fenhong_gongxian_percent'] > 0){
                $gongxian_percent = $fhlevel['fenhong'] * $fhlevel['fenhong_gongxian_percent']*0.01;
                $fhlevel['fenhong'] = $fhlevel['fenhong'] * (1 - $fhlevel['fenhong_gongxian_percent']*0.01);
                $gongxianCommissionTotal = $gongxian_percent * $fenhongprice * 0.01;
                //总业绩
                //$levelids = Db::name('member_level')->where('aid',aid)->where('sort','<',$fhlevel['sort'])->column('id');
//						$levelids = Db::name('member_level')->where('aid',aid)->column('id');
                $yejiwhere = [];
                $yejiwhere[] = ['isfenhong','=',0];
                //if($fhjiesuantime_type == 1) {
                $yejiwhere[] = ['status','in','1,2,3'];
                //}else{
                //	$yejiwhere[] = ['status','=','3'];
                //}
                $totalyeji = 0;
                foreach($midlist as $kk=>$item){
                    $downmids = \app\common\Member::getteammids($aid,$item['id']);
                    $yeji = Db::name('shop_order')->where('aid',$aid)->where('mid','in',$downmids)->where($yejiwhere)->sum('totalprice');
                    $midlist[$kk]['yeji'] = $yeji;
                    $totalyeji += $yeji;
                }
                if($totalyeji > 0){
                    $pergxcommon = $gongxianCommissionTotal / $totalyeji;
                }else{
                    $pergxcommon = 0;
                }
            }

            $fenhongmoney = $fhlevel['fenhong'] * $fenhongprice * 0.01 / count($midlist);//平均分给此等级的会员
            foreach($midlist as $item){
                if($item['id'] == $mid){
                    $gxcommon = 0;
                    if($pergxcommon > 0){
                        if($item['yeji'] >= $fhlevel['fenhong_gongxian_minyeji']){
                            $gxcommon = $item['yeji'] * $pergxcommon;
                        }
                    }
                    $commission += $gxcommon;
                    if ($fhlevel['fenhong_max_money'] > 0) {
                        if ($fenhongmoney + $total_fenhong_partner > $fhlevel['fenhong_max_money']) {
                            $fenhongmoney = $fhlevel['fenhong_max_money'] - $total_fenhong_partner;
                        }
                        $total_fenhong_partner += $fenhongmoney;//总分红增加
                    }
                    //$commission += $fenhongmoney;
                    break;
                }
            }
        }
        return dd_money_format($commission,$this->commission_weishu);

    }

    //获取会员当月累计消费金额
    public function get_buymoney_thismonth($aid,$mid){
        if(!$this->show_now){
            return 0;
        }
        $yejiwhere = [];
        $yejiwhere[] = ['status','in','1,2,3'];
        $starttime = strtotime(date('Y-m-01'));
        $endtime = time();
        $yejiwhere[] = ['createtime','between',[$starttime,$endtime]];
        $buymoney_thismonth = Db::name('shop_order_goods')->where('aid',$aid)->where('mid',$mid)->where($yejiwhere)->sum('real_totalprice');
        return dd_money_format($buymoney_thismonth,2);
    }
    //分销补贴待发放
    public function getCommissionButie($aid,$mid){
        if(!$this->show_now){
            return 0;
        }
        //待发放补贴
        $butie_total = Db::name('member_commission_butie')->where('aid',$aid)->where('mid',$mid)->sum('remain');
        return dd_money_format($butie_total,2);
    }
    //分销补贴已发放
    public function getCommissionButieSend($aid,$mid){
        if(!$this->show_now){
            return 0;
        }
        //已发放补贴
        $butie_send = Db::name('member_commission_butie')->where('aid',$aid)->where('mid',$mid)->sum('have_send');
        return dd_money_format($butie_send,2);
    }
    //团队业绩阶梯奖
    public function getYxTeamYeji($aid,$mid,$member){
        }

    //团队业绩级差奖新增业绩
    public function getYxTeamYejiAddYeji($aid,$mid,$type=''){
        if(!$this->show_now){
            return 0;
        }
        if($type=='month'){
            //当月新增业绩
            $addyeji = Db::name('member_yeji_log')->where('aid',$aid)->where('mid',$mid)->whereTime('createtime','month')->sum('yeji');
        }
        if(empty($type)){
            //总业绩
            $addyeji = Db::name('member_yeji_log')->where('aid',$aid)->where('mid',$mid)->whereTime('createtime','month')->sum('yeji');
        }
        return $addyeji;
    }

    public function getXianxiaYeji($aid,$mid,$member,$level_info=[]){
        if(!$this->show_now){
            return [];
        }
        }
}
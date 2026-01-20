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

/*分销*/
namespace app\controller;
use pay\wechatpay\WxPayV3;
use think\facade\Db;
use app\custom\AgentData;
class ApiAgent extends ApiCommon
{
	public function initialize(){
		parent::initialize();
		$this->checklogin();
	}
	public function commissionSurvey(){
        //1主动加载数据 0需要手动点击每个数据再查询并展示数据
        $show_now = 1;
        if(getcustom('commission_pagedata_show_type')){
            $show_now = $this->sysset['commission_pagedata_show_type'];
        }
        $AgentData = new AgentData(aid,$show_now,$this->sysset);
        $field = 'id,headimg,nickname,pid,commission,totalcommission,buymoney,levelid';
        if(getcustom('product_baodan')){
             $field .= ',baodan_freeze';
         }
        if(getcustom('fenhong_max')){
             $field .= ',fenhong_max';
         }
        if(getcustom('fenhong_max_add')){
	         $field .= ',fenhong_max_add';
         }
        if(getcustom('business_fenxiao')){
            $field .= ',disable_withdraw';
        }
        if(getcustom('yx_team_yeji')){
            $field .= ',total_team_yeji_fenhong';
        }
        if(getcustom('member_import_dyzx')){
            $field .= ',import_yeji';
        }
		$userinfo = Db::name('member')->field($field)->where('aid',aid)->where('id',mid)->find();
        //获取累计提现
		$count3 = $AgentData->getCommissionPageData(aid,mid,'count3');
		if($userinfo['pid']){
			$userinfo['pnickname'] = Db::name('member')->where('aid',aid)->where('id',$userinfo['pid'])->value('nickname');
		}
		$count3 = number_format($count3,2,'.','');

		$set = Db::name('admin_set')->where('aid',aid)->find();

		//是否显示分红订单
		$levelinfo = Db::name('member_level')->where('id',$this->member['levelid'])->find();
		$levelRecord = Db::name('member_level_record')->where('mid',mid)->select();
        $levelids = $levelRecord->column('levelid');
        if($levelids){
            $levelExtend = Db::name('member_level')->whereIn('id',$levelids);
        }

		$showfenhong = false;
		$hasfenhong = false;
		$hasteamfenhong = false;
		$hasareafenhong = false;
        $hasYeji = false;
		if($levelinfo['fenhong'] > 0){ //有股东分红
            $showfenhong = true;
			$hasfenhong = true;
		}
        if($levelids && $levelExtend->where('fenhong', '>', 0)->count()) {
            $showfenhong = true;
			$hasfenhong = true;
        }
        $hasfenhong_huiben = false;
        if(getcustom('fenhong_gudong_huiben')){
//            if($levelinfo['fenhong_huiben'] > 0){ //有股东分红
//                $showfenhong = true;
//                $hasfenhong_huiben = true;
//            }
//            if($levelids && $levelExtend->where('fenhong_huiben', '>', 0)->count()) {
//                $showfenhong = true;
//                $hasfenhong_huiben = true;
//            }
        }
		if($levelinfo['teamfenhonglv'] > 0 || ($levelinfo['teamfenhongbl'] > 0 || $levelinfo['teamfenhong_money'] > 0)){ //有团队分红
			$showfenhong = true;
			$hasteamfenhong = true;
		}
        if($levelinfo['teamfenhong_pingji_lv'] > 0 && ($levelinfo['teamfenhong_pingji_bl'] > 0 || $levelinfo['teamfenhong_pingji_money'] > 0)){ //有团队分红
            $showfenhong = true;
            $hasteamfenhong = true;
        }
        if(getcustom('zhitui_pj')){
            if($levelinfo['teamfenhonglv'] > 0){
                $showfenhong = true;
                $hasteamfenhong = true;
            }
        }
        if(getcustom('teamfenhong_jicha')){
            $showfenhong = true;
            $hasteamfenhong = true;
        }
        if(getcustom('team_leader_fh')){
            $showfenhong = true;
            $hasteamfenhong = true;
        }
        if($levelinfo['product_teamfenhong_ids'] != '' && $levelinfo['product_teamfenhonglv'] > 0 && ($levelinfo['product_teamfenhong_money'] > 0)){ //有商品团队分红
            $showfenhong = true;
            $hasteamfenhong = true;
        }
        if(!empty($levelinfo['level_teamfenhong_ids']) && $levelinfo['level_teamfenhonglv'] > 0 && ($levelinfo['level_teamfenhongbl'] > 0 || $levelinfo['level_teamfenhong_money'] > 0)){ //有等级团队分红
            $showfenhong = true;
            $hasteamfenhong = true;
        }
        if(!empty($levelinfo['tongji_yeji']) && $levelinfo['tongji_yeji'] == 1) {
            $hasYeji = true;
        }
        if($levelids){
            $countfenhong = $levelExtend->where('teamfenhonglv', '>', 0)->where(function ($query) {
                $query->where('teamfenhongbl','>',0)->whereOr('teamfenhong_money','>',0)->whereOr('teamfenhong_pingji_bl','>',0)->whereOr('teamfenhong_pingji_money','>',0);
            })->count();
            if($countfenhong) {
                $showfenhong = true;
                $hasteamfenhong = true;
            }
        }
        if(getcustom('teamfenhong_shouyi')){
            $hasteamfenhong = true;
        }

		if($levelinfo['areafenhong'] > 0 && $levelinfo['areafenhongbl'] > 0){ //有区域代理分红
			$showfenhong = true;
			$hasareafenhong = true;
		}
        if($levelids && $levelExtend->where('areafenhong', '>', 0)->where('areafenhongbl', '>', 0)->count()) {
            $showfenhong = true;
			$hasareafenhong = true;
        }
		if($this->member['areafenhong'] > 0 && $this->member['areafenhongbl'] > 0){ //有单独设置的区域代理分红
			$showfenhong = true;
			$hasareafenhong = true;
		}
        if($levelRecord->where('areafenhong', '>', 0)->where('areafenhongbl', '>', 0)->count()) {
            $showfenhong = true;
			$hasareafenhong = true;
        }
        if(getcustom('plug_zhiming')) {
            $mendian_ids = Db::name('admin_user')->where('aid', aid)->where('mid', mid)->where('mdid', '>', 0)->column('mdid');
            if($mendian_ids) {
                $showMendianOrder = true;
            }
        }
        if(getcustom('member_show_buymoney')) {
            $set['show_myyeji'] = 1;
            $userinfo['buymoney_thismonth'] = $AgentData->get_buymoney_thismonth(aid,mid);
        }
        if(getcustom('fenhong_jiaquan_bylevel') && !$showfenhong) {
            $showfenhong = true;
        }
        if(getcustom('mendian_dianzhan_commission') && !$showfenhong) {
            $showfenhong = true;
        }

        //获取累计待结算佣金
        $commissionyj = $AgentData->getCommissionYj(aid,mid);

		$userinfo['fenhong'] = $AgentData->getFenhongCount(aid,mid,'fenhong');
		$userinfo['areafenhong'] = $AgentData->getFenhongCount(aid,mid,'areafenhong');
		$userinfo['teamfenhong'] = $AgentData->getFenhongCount(aid,mid,'teamfenhong');

		$userinfo['commission_yj'] = $commissionyj;

        $hastouzifenhong = false;
        if(getcustom('touzi_fenhong')) {
            //投资分红
            $shareholder = Db::name('shareholder')->where('aid',aid)->where('status',1)->where('mid',mid)->find();
            $touzimoney = 0+ Db::name('shareholder')->where('aid',aid)->where('status',1)->where('mid',mid)-> sum('money');
            $userinfo['touzimoney'] =$touzimoney;
            if($shareholder ){
                $hastouzifenhong = true;
            }
            if($shareholder){
                $userinfo['touzifenhong'] = $AgentData->getFenhongCount(aid,mid,'touzifenhong');
            }
        }

        if($set['teamyeji_show']==1 || $set['teamnum_show']==1){
            //$user_downmids = \app\common\Member::getteammids(aid,mid);
            $user_downmids = Db::name('member')->where('aid',aid)->where('find_in_set('.mid.',path)')->column('id');
        }
        $custom_product_num = getcustom('levelup_selfanddown_order_product_num');
		if($set['teamyeji_show']==1){ //团队业绩
			//总业绩
			$yejiwhere = [];
            $yejiwhere[] = ['status','in','1,2,3'];
            $yejiwheregoods = $yejiwhere;
            if($custom_product_num){
                if($levelinfo['up_selfanddown_order_product_num_proids']){
                    $yejiproids = explode(',',$levelinfo['up_selfanddown_order_product_num_proids']);
                    $yejiwheregoods[] = ['proid','in',$yejiproids];
                }
            }

			$totalyeji = 0;
			$downmids = $user_downmids;
			//使用where('mid','in',$downmids)时如果$downmids数据很大 mysql会出现1390 Prepared statement contains too many placeholders
            if($downmids){
                $mid_sql = 'mid in ('.implode(',',$downmids).')';
            }else{
                $mid_sql = 'mid=-1';
            }
			$teamyeji = Db::name('shop_order_goods')->where('aid',aid)->where($mid_sql)->where($yejiwheregoods)->sum('real_totalprice');
            $teamOrderCount = Db::name('shop_order')->where('aid',aid)->where($mid_sql)->where($yejiwhere)->count();
            if($custom_product_num){
                $userinfo['teamyeji_prosum'] = Db::name('shop_order_goods')->where('aid',aid)->where($mid_sql)->where($yejiwheregoods)->sum('num');
            }

            if(getcustom('member_show_teamyeji')){
                $set['show_teamyeji_search'] = 1;
                $starttime = strtotime(date('Y-m-01'));
                $endtime = time();
                $yejiwheregoods[] = ['createtime','between',[$starttime,$endtime]];
                $userinfo['teamyeji_thismonth'] = Db::name('shop_order_goods')->where('aid',aid)->where($mid_sql)->where($yejiwheregoods)->sum('real_totalprice');
            }
		}
		if($set['teamnum_show']==1){ //团队总人数
			if($set['teamyeji_show']!=1){
				$downmids = $user_downmids;
			}
			$teamnum = count($downmids);
		}
		$gongxianfenhong_show = 0;
		if($set['gongxianfenhong_show'] == 1){
			if($set['partner_gongxian']==1 && $levelinfo['fenhong'] > 0 && $levelinfo['fenhong_gongxian_percent'] > 0){
				$gongxianfenhong_show = 1;
                $commission = $AgentData->getGongxianFenhong(aid,mid,$this->member,$this->sysset);
				$gongxianfenhong = $commission;
			}
		}
        $moeny_weishu = 2;
        if(getcustom('fenhong_money_weishu')){
            $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
        }
        $moeny_weishu = $moeny_weishu?$moeny_weishu:2;
        $userinfo['fenhong'] = dd_money_format($userinfo['fenhong'],$moeny_weishu);
        $userinfo['areafenhong'] = dd_money_format($userinfo['areafenhong'],$moeny_weishu);
        $userinfo['teamfenhong'] = dd_money_format($userinfo['teamfenhong'],$moeny_weishu);
        if(getcustom('touzi_fenhong')) {
            $userinfo['touzifenhong'] = dd_money_format($userinfo['touzifenhong'], $moeny_weishu);
        }
        $userinfo['fenhong_yj'] = dd_money_format($userinfo['fenhong_yj'],$moeny_weishu);
        $userinfo['areafenhong_yj'] =dd_money_format($userinfo['areafenhong_yj'],$moeny_weishu);
        $userinfo['teamfenhong_yj'] =dd_money_format($userinfo['teamfenhong_yj'],$moeny_weishu);
        $userinfo['touzifenhong_yj'] = dd_money_format($userinfo['touzifenhong_yj'],$moeny_weishu);
        $userinfo['commission_yj'] =  dd_money_format($userinfo['commission_yj'],$moeny_weishu);
        $userinfo['commission'] =  dd_money_format($userinfo['commission'],$moeny_weishu);
        $userinfo['totalcommission'] =  dd_money_format($userinfo['totalcommission'],$moeny_weishu);
        if(getcustom('member_totalcommission')){
            $userinfo['show_totalcommission'] =true;
        }
        if(getcustom('product_baodan')){
            $userinfo['baodan_freeze'] =  dd_money_format($userinfo['baodan_freeze'],$moeny_weishu);
        }
        //分红上限
        $userinfo['fenhong_max_show'] = 0;

        if(getcustom('fenhong_max') ){
            //查询会员已获得股东分红
            $fenhong_total_res = $AgentData->getGudongTotal(aid,mid,$userinfo,$this->sysset,'gudong');
            $userinfo['gudong_total'] = $fenhong_total_res['gudong_total'];
            $userinfo['gudong_remain'] = $fenhong_total_res['gudong_remain'];
            $userinfo['fenhong_max'] = $fenhong_total_res['fenhong_max'];
            $userinfo['fenhong_max_show'] = $fenhong_total_res['fenhong_max_show'];
        }
		//卡片
        $rsset = [];
        if(getcustom('agent_card')){
            if($set['agent_card'] == 1){
                $agentCard2 = Db::name('admin')->where('id',aid)->value('agent_card');
                if($agentCard2 == 1){
                    $count = \db('member_levelup_order')->where('aid',aid)->where('mid',mid)->where('status',2)->count();
                    if($count)
                        $rsset['agent_card'] = 1;
                }
            }
        }
        if(getcustom('product_price_rate')){
            $rsset['product_price_rate'] = 1;
        }

        if(getcustom('agent_card') && getcustom('member_level_price_rate')){
            $agentCard = Db::name('admin_set')->where('aid',aid)->value('agent_card');
            if($agentCard == 1) {
                $agentCard2 = Db::name('admin')->where('id', aid)->value('agent_card');
                if ($agentCard2 == 1) {
                    //并且申请过会员等级
                    $lorder = Db::name('member_levelup_order')->where('aid',aid)->where('mid',mid)->where('status',2)->count();
                    if($lorder){
                        $rsset['member_level_price_rate'] = 1;
                    }
                }
            }
        }

        if(!getcustom('agent_card') && getcustom('member_level_price_rate')){
            //并且申请过会员等级
            $lorder = Db::name('member_levelup_order')->where('aid',aid)->where('mid',mid)->where('status',2)->count();
            if($lorder){
                $rsset['member_level_price_rate'] = 1;
            }
        }
        if(getcustom('commission_transfer_business_money')){
            if($set['commission_transfer_business_money']){
                $rsset['tobusinessmoney'] = 1;
            }
        }
        $rsset['teamshouyi_show'] = 0;
        $userinfo['teamshouyi'] = 0;
        $hasteamshouyi = false;
        if(getcustom('teamfenhong_shouyi')){
            $hasteamshouyi = true;
            $rsset['teamshouyi_show'] = 1;
            $userinfo['teamshouyi'] = $AgentData->getFenhongCount(aid,mid,'teamfenhong_shouyi');
        }
        $showxiaoshouyeji = false;
        if(getcustom('coupon_xianxia_buy')){
            $showxiaoshouyeji = true;
            $level_info = Db::name('member_level')->where('id',$this->member['levelid'])->find();
            $res = $AgentData->getXianxiaYeji(aid,mid,$this->member,$level_info);
           
            $userinfo['totalyeji'] = $res['totalyeji']??0;
            $userinfo['shouyi'] = $res['shouyi']??0;
            $userinfo['coupon_yeji'] = $res['coupon_yeji']??0;//销售额
            $zt_commission = $AgentData->getCommissionCountBytype(aid,mid,'zt_commission');
            $yeji_commission = $AgentData->getCommissionCountBytype(aid,mid,'yeji_commission');
            $userinfo['showxiaoshouyeji'] = $showxiaoshouyeji;
            $userinfo['zt_commission'] = $zt_commission;
            $userinfo['yeji_commission'] = $yeji_commission;
        }
        if(getcustom('member_forzengxcommission')){
            //冻结贡献值佣金
            $forzengxcommission = Db::name('member_forzengxcommissionlog')->where('mid',mid)->where('status',0)->where('aid',aid)->sum('commission2');
            $userinfo['forzengxcommissionshow'] = true;
            $userinfo['forzengxcommission'] = dd_money_format($forzengxcommission,2);
        }
        $userinfo['show_teamfenhongyeji'] = 0;
        if(getcustom('team_fenhong_yeji')){
            $userinfo['show_teamfenhongyeji'] = 1;
            //获取团队业绩
            $team_fenhong_yeji = $AgentData->getTeamFenhongYeji(aid,mid,$userinfo);
            $userinfo['team_fenhong_yeji'] = $team_fenhong_yeji;
        }

        $rsset['parent_show'] = $set['parent_show'];
        $rsset['teamfenhong_show'] = $set['teamfenhong_show'];
        $rsset['commissionlog_show'] = $set['commissionlog_show'];
        $rsset['commissionrecord_show'] = $set['commissionrecord_show'];
        $rsset['fhorder_show'] = $set['fhorder_show'];
        $rsset['fhlog_show'] = $set['fhlog_show'];
        $rsset['show_myyeji'] = $set['show_myyeji'];
        $rsset['show_teamyeji_search'] = $set['show_teamyeji_search'];
		$rsset['tjbusiness_show'] = $set['tjbusiness_show'];
		$rsset['fxorder_show'] = $set['fxorder_show'];

        $rsset['business_teamfenhong_show'] = false;

        if($hasfenhong_huiben){
            $userinfo['fenhong_huiben'] = $AgentData->getFenhongCount(aid,mid,'fenhong_huiben');
        }
        $hasbusinessteamfenhong = false;
        if(getcustom('business_teamfenhong') || getcustom('business_teamfenhong_pj')){
            if($levelinfo['business_teamfenhonglv'] > 0 && $levelinfo['business_teamfenhongbl']){ //有商家团队分红
                $hasbusinessteamfenhong = true;
            }
            if($levelinfo['business_teamfenhonglv_pj'] > 0 && $levelinfo['business_teamfenhongbl_pj']){ //有商家团队分红
                $hasbusinessteamfenhong = true;
            }
            $rsset['business_teamfenhong_show'] = $set['business_teamfenhong_show'];
            $userinfo['business_teamfenhong'] = $AgentData->getFenhongCount(aid,mid,'business_teamfenhong');
        }
        if(getcustom('business_fenxiao') && $userinfo['disable_withdraw']){
            //已冻结佣金提现
            $set['comwithdraw'] = 0;
            $set['commission2money'] = 0;
        }
        if(getcustom('yx_commission_to_lingqiantong')){
            $rsset['yx_commission_to_lingqiantong'] = 1;
        }

		$rdata = [];
		$rdata['count'] = $count;
		$rdata['count3'] = dd_money_format($count3,$moeny_weishu);
		$rdata['comwithdraw'] = $set['comwithdraw'];
		$rdata['comwithdrawmin'] = $set['comwithdrawmin'];
		$rdata['commission2money'] = $set['commission2money'];
        if(getcustom('commission_tomoney_need_score') && $set['comtomoney_need_score']){
            $rdata['commission_money_exchange_num'] = $set['commission_money_exchange_num']??0;
        }
        if(getcustom('commission_index_hide_withdraw')){
            $rdata['hide_withdraw_btn'] = true;
        }
        $rdata['commission_to_money_rate'] = '';
        if(getcustom('commission_to_money_rate') && $set['commission_to_money_rate'] > 0){
            $rdata['commission_to_money_rate'] = '(费率:'.$set['commission_to_money_rate'].'%)';
        }

        $fenhongmax_to_score = 0;//股东分红额度转积分
        $fenhongmax_to_gupiao = 0;//股东分红额度转股票
        if(getcustom('fenhong_max_add')){
            //股东分红额度
            $fenhongmax_to_score = $this->sysset_custom['fenhongmax_to_score']?:0;
            $fenhongmax_to_gupiao = $this->sysset_custom['fenhongmax_to_gupiao']?:0;
            $userinfo['fenhongmax_to_gupiao_url'] = $this->sysset_custom['fenhongmax_to_gupiao_url'];
        }
        $userinfo['fenhongmax_to_score'] = $fenhongmax_to_score;
        $userinfo['fenhongmax_to_gupiao'] = $fenhongmax_to_gupiao;
        $showunit = 1;
        if(getcustom('commission_unit_show')){
            //是否展示佣金单位
            $showunit = $this->sysset_custom['commission_unit_show']?:0;
        }
        $rdata['showunit'] = $showunit;
        $rdata['fxjiesuantime'] = $set['fxjiesuantime'];
		$rdata['showfenhong'] = $showfenhong;
		$rdata['hasfenhong'] = $hasfenhong;
        $rdata['hasfenhong_huiben'] = $hasfenhong_huiben;
		$rdata['hasteamfenhong'] = $hasteamfenhong;
		$rdata['hasareafenhong'] = $hasareafenhong;
        $rdata['showMendianOrder'] = $showMendianOrder;
        $rdata['hastouzifenhong'] = $hastouzifenhong;
        $rdata['hasteamshouyi'] = $hasteamshouyi;
        $rdata['hasYeji'] = $hasYeji;
        $rdata['teamyeji_show'] = getcustom('teamyeji_show') ? $set['teamyeji_show'] : 0;
        $rdata['set'] = $rsset;
		if($rdata['teamyeji_show'] == 1){
			$userinfo['teamyeji'] = round($teamyeji,2);
            $userinfo['teamOrderCount'] = $teamOrderCount;
		}
        $rdata['teamnum_show'] = $set['teamnum_show'];
		if($rdata['teamnum_show'] == 1){
			$userinfo['teamnum'] = $teamnum;
		}
        $rdata['gongxianfenhong_show'] = $gongxianfenhong_show;
		if($rdata['gongxianfenhong_show']==1){
			$userinfo['gongxianfenhong'] = round($gongxianfenhong,2);
			$userinfo['gongxianfenhong_txt'] = $set['gongxianfenhong_txt'];
		}
        $rdata['commission_butie'] = false;
        if(getcustom('commission_butie')){
            $rdata['commission_butie'] = true;
            //待发放补贴
            $butie_total = $AgentData->getCommissionButie(aid,mid);
            //已发放补贴
            $butie_send = $AgentData->getCommissionButieSend(aid,mid);
            $userinfo['butie_total'] = $butie_total?:0;
            $userinfo['butie_send'] = $butie_send?:0;
        }
       
       
        if(getcustom('yx_team_yeji')){
            $team_yeji_res = $AgentData->getYxTeamYeji(aid,mid,$this->member);
            $userinfo['team_yeji_fenhong_yj'] = $team_yeji_res['team_yeji_fenhong_yj']??0;

            $userinfo['show_team_yeji_fenhong_yj'] = $team_yeji_res['show_team_yeji_fenhong_yj']??0;
            $userinfo['show_team_yeji_fenhong'] = $team_yeji_res['show_team_yeji_fenhong']??0;
        }
        if(getcustom('yx_team_yeji_jc')){
            //当月新增业绩
            $month_addyeji = $AgentData->getYxTeamYejiAddYeji(aid,mid,'month');
            $total_addyeji = $AgentData->getYxTeamYejiAddYeji(aid,mid);
            $userinfo['team_yeji_month_addyeji'] = $month_addyeji;
            $userinfo['team_yeji_total_addyeji'] = $total_addyeji;
            $userinfo['show_team_yeji_fenhong_jc'] = 1;
        }
        $show_teamfenhong_freight = false;
        if(getcustom('teamfenhong_freight_money')){
            if($levelinfo['teamfenhong_freight_lv'] > 0){
                $show_teamfenhong_freight = true;
                $userinfo['teamfenhong_freight'] = $AgentData->getFenhongCount(aid,mid,'teamfenhong_freight');
                $userinfo['teamfenhong_freight_yj'] = $AgentData->getYjFenhongCount(aid,mid,'teamfenhong_freight');
            }
        }
        $userinfo['show_teamfenhong_freight'] = $show_teamfenhong_freight;

        $userinfo['fenhong_max_status'] = false;
        if(getcustom('fenhong_max')){
            //查询会员已获得股东分红
            $where_fenhong = [];
            $where_fenhong[] = ['mid','=',mid];
            $where_fenhong[] = ['type','=','fenhong'];
            $where_fenhong[] = ['status','in',[0,1]];
            $fenhong_total = Db::name('member_fenhonglog')
                ->where($where_fenhong)
                ->sum('commission');
            $userinfo['gudong_total'] = bcmul($fenhong_total,1,2);
            if($fenhong_total>0 && $hasfenhong){
                $userinfo['fenhong_max_status'] = true;
            }
        }

        //会员佣金统计
        $userinfo['show_commission_count'] = 0;
        if(getcustom('member_commission_count')){
            //异步方法 getCommissionCount 中获取数值
            $userinfo['show_commission_count'] = 1;
            $userinfo['today_commission'] = '计算中';
            $userinfo['yesterday_commission'] = '计算中';
            $userinfo['month_commission'] = '计算中';
            $userinfo['lastmonth_commission'] = '计算中';
        }
        if(getcustom('extend_business_shareholder')){
            $userinfo['showShareholder'] = 1;

        }

		$rdata['userinfo'] = $userinfo;
		$rdata['hasbusinessteamfenhong'] = $hasbusinessteamfenhong;
		
		$rdata['uplv_agree'] = 0;
		if(getcustom('up_level_agree2') && $levelinfo['is_agree'] == 1){
            $level_agree = Db::name('member_level_agree')->where('aid',aid)->where('mid',mid)->where('newlv_id',$levelinfo['id'])->find();
			if(!$level_agree || $level_agree['status'] == 0){
				$rdata['uplv_agree'] = 1;
				$rdata['agree_content'] = $levelinfo['agree_content'];
			}
		}
		//需要展示的分红类型
        $fhtype_arr = [];
		if($hasfenhong){
		    //股东分红
            $fhtype_arr[] = 'gudong';
        }
		if($hasfenhong_huiben){
		    //回本股东分红
            //$fhtype_arr[] = 'huiben';
        }
        if($hasteamfenhong && $set['teamfenhong_show']){
            //团队分红
            $fhtype_arr[] = 'team';
        }
        if($hasbusinessteamfenhong && $set['business_teamfenhong_show']){
            //商家团队分红
            $fhtype_arr[] = 'business_teamfenhong';
        }
        if($hasareafenhong){
            //区域代理分红
            $fhtype_arr[] = 'area';
        }
        $rdata['fhtype_arr'] = $fhtype_arr;
        $rdata['show_now'] = $show_now;//0主动加载数据 1需要手动点击每个数据再查询并战术数据
		return $this->json($rdata);
	}
    public function getCommissionCount(){
        if(getcustom('member_commission_count')){
            $show_now = 1;
            if(getcustom('commission_pagedata_show_type')){
                $show_now = $this->sysset['commission_pagedata_show_type'];
            }
            $AgentData = new AgentData(aid,$show_now);
            //今日佣金
            $today_commission = $AgentData->getCommissionCount(aid,mid,'today');
            //昨日佣金
            $yesterday_commission = $AgentData->getCommissionCount(aid,mid,'yesterday');
            //本月佣金
            $month_commission = $AgentData->getCommissionCount(aid,mid,'month');
            //上月佣金
            $lastmonth_commission = $AgentData->getCommissionCount(aid,mid,'last_month');

            $rdata = [];
            $rdata['status'] = 1;
            $rdata['today_commission'] = bcadd(0,$today_commission,2);
            $rdata['yesterday_commission'] = bcadd(0,$yesterday_commission,2);
            $rdata['month_commission'] = bcadd(0,$month_commission,2);
            $rdata['last_month_commission'] = bcadd(0,$lastmonth_commission,2);
            return $this->json($rdata);
        }
    }
	//用于前端异步获取分红数据
    public function get_fenhong(){
	    set_time_limit(0);
        $show_now = 1;
        if(getcustom('commission_pagedata_show_type')){
            $show_now = $this->sysset['commission_pagedata_show_type'];
        }
        $AgentData = new AgentData(aid,$show_now);
	    $data = [];
	    $fhtype = input('fhtype');
        $fenhong_yj = $AgentData->getYjFenhongCount(aid,mid,$this->sysset,$fhtype);
        $data['fenhong_yj'] = $fenhong_yj;
        $rdata = [
            'status' => 1,
            'data' => $data
        ];
        return $this->json($rdata);
	}
	//佣金转余额
	public function commission2money(){
        try{
            Db::startTrans();
            $post = input('post.');
            $set = Db::name('admin_set')->where('aid',aid)->find();
            if($set['commission2money'] !=1){
                return $this->json(['status'=>0,'msg'=>'该功能未启用']);
            }
            $money = floatval($post['money']);
            $member = Db::name('member')->where('aid',aid)->where('id',mid)->lock(true)->find();
            if($money <= 0 || $money > $member['commission']){
                return $this->json(['status'=>0,'msg'=>'转入金额不正确']);
            }

            //要减掉的金额
            $c_money = $money;

            //是不是需要消耗积分
            if(getcustom('commission_tomoney_need_score') && $set['comtomoney_need_score']){
                $needScoreNum = $set['commission_money_exchange_num']?$set['commission_money_exchange_num']*$money:0;
                if($needScoreNum>0){
                    if($needScoreNum>$member['score']){
                        Db::rollback();
                        return $this->json(['status'=>0,'msg'=>t('积分').'不足']);
                    }
                    \app\common\Member::addscore(aid,mid,-$needScoreNum,t('佣金').'转'.t('余额').'消耗');
                }
            }
            //费率
            if(getcustom('commission_to_money_rate') && $set['commission_to_money_rate'] > 0){
                $commission_to_money_rate = $set['commission_to_money_rate'] / 100;
                if($commission_to_money_rate>0){
                    //计算费用
                    $rate_money = bcmul($money,$commission_to_money_rate,3);
                    //减去费用
                    $money = round(bcsub($money, $rate_money, 3),2);
                    $money = ($money <= 0) ? 0 : $money;
                    $remark = t('佣金').'转'.t('余额').'(费率:'.$set['commission_to_money_rate'].'%)';
                }
            }
            if(empty($remark)){
                $remark = t('佣金').'转'.t('余额');
            }
            \app\common\Member::addcommission(aid,mid,0,-$c_money,$remark);
            \app\common\Member::addmoney(aid,mid,$money,$remark);
            Db::commit();
        }catch(Exception $e){
            Db::rollback();
        }
		
		return $this->json(['status'=>1,'msg'=>'转入成功']);
	}
	//佣金明细
	public function commissionlog(){
		$st = input('param.st');
        $type = input('param.type');
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['mid','=',mid];
		if(input('param.keyword')) $where[] = ['remark', 'like', '%'.input('param.keyword').'%'];
		$pernum = 20;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		if($st ==1){//提现记录
			$datalist = Db::name('member_commission_withdrawlog')->field("id,money,txmoney,`status`,from_unixtime(createtime)createtime,reason,wx_state")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		}else{ //佣金明细
            if($type ==1) {
                $where[] = ['remark','=','投资分红'];
            }
            $moeny_weishu =2;
            if(getcustom('fenhong_money_weishu')){
                $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
            }
            $moeny_weishu =$moeny_weishu?$moeny_weishu:2;
			$datalist = Db::name('member_commissionlog')->field("id,commission money,`after`,from_unixtime(createtime)createtime,remark,service_fee,fhid,frommid")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            foreach ($datalist as $key=>$val){
                $datalist[$key]['commission'] = dd_money_format($val['commission'],$moeny_weishu);
                $datalist[$key]['money'] = dd_money_format($val['money'],$moeny_weishu);
                $datalist[$key]['after'] = dd_money_format($val['after'],$moeny_weishu);
                $datalist[$key]['service_fee'] = dd_money_format($val['service_fee'],$moeny_weishu);
                if(getcustom('commission_record_with_order') && $val['fhid']){
                    $log = db('member_fenhonglog')->where('aid',aid)->where('id',$val['fhid'])->find();
                    if($log['module']){
                        if(\app\common\Order::hasOrderGoodsTable($log['module'])){
                            $orderlist = db($log['module'].'_order_goods')->where('aid',aid)->where('id','in',$log['ogids'])->select()->toArray();
                        }else{
                            $orderlist = db($log['module'].'_order')->where('aid',aid)->where('id',$log['ogids'])->select()->toArray();
                        }
                        if($orderlist){
                            $totalprice = 0;
                            foreach ($orderlist as $order){
                                if($order['bid']>0){
                                    $business = Db::name('business')->where('aid',aid)->where('id',$order['bid'])->field('id,name,logo')->find();
                                } else {
                                    $business = Db::name('admin_set')->where('aid',aid)->field('name,logo')->find();
                                }
                                $totalprice += $order['totalprice'] ? $order['totalprice'] : $order['paymoney'];
                            }
                            $datalist[$key]['order'] = [
                                'totalprice'=>$totalprice,
                                'member'=> db('member')->field('id,nickname,headimg')->where('aid',aid)->where('id',$order['mid'])->find(),
                                'business'=>$business??[],
                                'bid'=>$order['bid']
                            ];
                        }
                    }
                }
                if(getcustom('fenhong_show_from_member')) {
                    if ($val['frommid']) {
                        $from_member = Db::name('member')->where('id', $val['frommid'])->find();
                        $datalist[$key]['from_headimg'] = $from_member['headimg'];
                        $datalist[$key]['from_nickname'] = $from_member['nickname'];
                    } else {
                        $datalist[$key]['from_headimg'] = '';
                        $datalist[$key]['from_nickname'] = '';
                    }
                }
            }
		}
		if(!$datalist) $datalist = [];

        if($pagenum == 1) {
            $field = 'aid,name,logo';
            if(getcustom('commission_service_fee')) {
                $field .= ',commission_service_fee_show';
            }

            $set = Db::name('admin_set')->field($field)->where('aid', aid)->find();
        }
        //是否展示分红来源会员信息
        $show_from_member = 0;
        if(getcustom('fenhong_show_from_member')){
            $show_from_member = 1;
        }

		return $this->json(['status'=>1,'data'=>$datalist,'set'=>$set?$set:[],'show_from_member'=>$show_from_member]);
	}
	
	//佣金记录
    public function commissionrecord(){
		$pernum = 20;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;

		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['mid','=',mid];
		$st = input('param.st');
		if($st == 0){
			$where[] = ['status','in','0,1'];
		}elseif($st == 1){
			$where[] = ['status','=',1];
		}elseif($st == 2){
			$where[] = ['status','=',0];
		}

        $commission_weishu = 2;
        if(getcustom('fenhong_money_weishu')){
            $commission_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
        }
        $score_weishu = 0;
        if(getcustom('score_weishu')){
            $score_weishu = Db::name('admin_set')->where('aid',aid)->value('score_weishu');
            $score_weishu = $score_weishu?$score_weishu:0;
        }

		$datalist = Db::name('member_commission_record')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = [];
		foreach($datalist as $k=>$v){
			if($v['type'] == 'levelup'){
				$datalist[$k]['orderstatus'] = 1;
			}elseif ($v['type']=='fenhong_copies'){
                $datalist[$k]['orderstatus'] = 3;

            }elseif($v['type'] =='channels'){
                if(getcustom('wx_channels')){
                    $channels_order = Db::name('channels_order')->where('id',$v['orderid'])->where('aid',$v['aid'])->field('status,create_time')->find();
                    $orderstatus = '';
                    if($channels_order['status'] == 10){
                        $orderstatus = 0;
                    }else if($channels_order['status'] == 20 ){
                        $orderstatus = 1;
                    }else if($channels_order['status'] == 21 || $channels_order['status'] == 30){
                        $orderstatus = 2;
                    }else if($channels_order['status'] == 100){
                        $orderstatus = 3;
                    }else if($channels_order['status'] == 200 || $channels_order['status'] == 250){
                        $orderstatus = 4;
                    }
                    $datalist[$k]['orderstatus'] = $orderstatus;
                }
            }else{
				$datalist[$k]['orderstatus'] = Db::name($v['type'].'_order')->where('id',$v['orderid'])->value('status');
			}
			if($v['frommid']){
				$frommember = Db::name('member')->where('id',$v['frommid'])->find();
				if($frommember){
					$datalist[$k]['fromheadimg'] = $frommember['headimg'];
					$datalist[$k]['fromnickname'] = $frommember['nickname'];
				}else{
					$datalist[$k]['fromheadimg'] = '';
					$datalist[$k]['fromnickname'] = '';
				}
			}

			if(is_null($v['commission'])){
				if(!is_null($v['score'])){
					$commission = dd_money_format($v['score'],$score_weishu);
				}else{
					$commission = '0';
				}
			}else{
				$commission = dd_money_format($v['commission'],$commission_weishu);
			}
			$datalist[$k]['commission'] = $commission;
            $goods = [];
            if($v['type'] =='car_hailing'){
                if(getcustom('car_hailing')){
                    $goods = Db::name('car_hailing_product')->where('aid',$v['aid'])->where('id',$v['ogid'])->field('name,pic')->find();
                    $order = Db::name('car_hailing_order')->where('aid',$v['aid'])->where('id',$v['orderid'])->field('createtime,num,product_price')->find();
                    $goods['createtime'] = date('Y-m-d H:i',$order['createtime']);
                    $goods['num'] = $order['num'];
                    $goods['sell_price'] = $order['product_price'];
                }
            }elseif(in_array($v['type'],['collage','lucky_collage'])){
                $goods = Db::name($v['type'].'_product')->where('aid',$v['aid'])->where('id',$v['ogid'])->field('name,pic')->find();
                $order = Db::name($v['type'].'_order')->where('aid',$v['aid'])->where('id',$v['orderid'])->field('createtime,num,sell_price')->find();
                $goods['createtime'] = date('Y-m-d H:i',$order['createtime']);
                $goods['num'] = $order['num'];
                $goods['sell_price'] = $order['sell_price'];
            }elseif(in_array($v['type'],['shop','restaurant_shop','restaurant_takeaway'])){
                $order_goods = Db::name($v['type'].'_order_goods')
                    ->where('aid',$v['aid'])
                    ->where('id',$v['ogid'])
                    ->field('createtime,num,proid,sell_price')
                    ->find();
                $product_tablename = $v['type'].'_product';
                if($v['type'] =='restaurant_shop' || $v['type'] =='restaurant_takeaway'){
                    $product_tablename = 'restaurant_product';
                }
                if($order_goods){
                    $goods = Db::name($product_tablename)->where('aid',$v['aid'])->where('id',$order_goods['proid'])->field('name,pic')->find();
                    $goods['createtime'] = date('Y-m-d H:i',$order_goods['createtime']);
                    $goods['num'] = $order_goods['num'];
                    $goods['sell_price'] = $order_goods['sell_price'];
                }
            }elseif(in_array($v['type'],['scoreshop'])){
                $order_goods = Db::name($v['type'].'_order_goods')
                    ->where('aid',$v['aid'])
                    ->where('id',$v['ogid'])
                    ->field('createtime,num,proid,score_price,money_price')
                    ->find();
                $product_tablename = $v['type'].'_product';
                if($order_goods){
                    $goods = Db::name($product_tablename)->where('aid',$v['aid'])->where('id',$order_goods['proid'])->field('name,pic')->find();
                    $goods['createtime'] = date('Y-m-d H:i',$order_goods['createtime']);
                    $goods['num'] = $order_goods['num'];
                    $goods['sell_price'] = $order_goods['money_price'] .' + ' . $order_goods['score_price'].t('积分');
                }
            }elseif($v['type'] =='channels'){
                if(getcustom('wx_channels')){
                    $order_goods = Db::name('channels_order_goods')
                        ->where('id',$v['ogid'])
                        ->where('aid',$v['aid'])
                        ->field('sku_cnt,product_id,sale_price')
                        ->find();
                    if($order_goods){
                        $goods = Db::name('channels_product')->where('aid',$v['aid'])->where('id',$order_goods['product_id'])->field('name,pic')->find();
                        $goods['num'] = $order_goods['sku_cnt'];
                        $goods['sell_price'] = $order_goods['sale_price'];
                        $order = Db::name('channels_order')->where('id',$v['orderid'])->where('aid',$v['aid'])->field('create_time')->find();
                        $goods['createtime'] = date('Y-m-d H:i',$order['create_time']);
                    }
                }
            }elseif($v['type'] =='hanglvfeike'){
                // if(getcustom('extend_hanglvfeike')){
                //     $order_goods = Db::name('hanglvfeike_order_goods')
                //         ->where('id',$v['ogid'])
                //         ->where('aid',$v['aid'])
                //         ->field('totalprice')
                //         ->find();
                //     if($order_goods){
                //         $goods = [];
                //         $goods['name'] = '机票商品';
                //         $goods['pic'] = '';
                //         $goods['num'] = 1;
                //         $goods['sell_price'] = $order_goods['totalprice'];
                //         $order = Db::name('hanglvfeike_order')->where('id',$v['orderid'])->where('aid',$v['aid'])->field('createtime')->find();
                //         $goods['createtime'] = date('Y-m-d H:i',$order['createtime']);
                //     }
                // }
            }
            $datalist[$k]['goods'] = $goods;
		}
		return $this->json(['status'=>1,'data'=>$datalist]);
    }
	//佣金提现
	public function commissionWithdraw(){
		$field = 'withdraw_autotransfer,comwithdraw,comwithdrawmin,comwithdrawfee,comwithdrawbl,comwithdrawdate,withdraw_weixin,withdraw_aliaccount,withdraw_bankcard,withdraw_desc,comwithdrawmax,commissionrecord_withdrawlog_show,day_withdraw_num,wx_transfer_type';
		if(getcustom('alipay_auto_transfer')){
			$field .= ',ali_withdraw_autotransfer';
		}
        if(getcustom('pay_adapay')){
            $field .= ',withdraw_adapay';
        }
        if(getcustom('commission_withdraw_need_score')){
            $field .= ',comwithdraw_need_score,commission_score_exchange_num';
        }
        if(getcustom('transfer_farsion')){
            $field .= ',withdraw_bankcard_xiaoetong,withdraw_aliaccount_xiaoetong';
        }
        if(getcustom('pay_huifu')){
            $field .= ',withdraw_huifu';
        }
		if(getcustom('commission_withdraw_freeze')){
			$field .= ',comwithdraw_freeze,comwithdraw_totalmoney';
		}
		if(getcustom('commission_duipeng_score_withdraw')){
			$field .= ',comwithdraw_integer_type,comwithdraw_duipeng_score_bili';
		}
		if(getcustom('commission_withdraw_level_sxf')){
			$field .= ',comwithdrawfee_level';
		}
		if(getcustom('product_givetongzheng')){
		    $field .= ',commissionwithdraw2tongzheng';
        }

        if(getcustom('withdraw_mul') && !getcustom('money_withdraw_mul')){
          $field .= ',comwithdrawmul';
        }
        if(getcustom('extend_linghuoxin')){
            $field .= ',withdraw_aliaccount_linghuoxin,withdraw_bankcard_linghuoxin';
        }
        // if(getcustom('pay_allinpay')){
        //     $field .= ',withdraw_bankcard_allinpayYunst';
        // }
        if(getcustom('withdraw_paycode')){
            $field .= ',withdraw_paycode';
        }
        if(getcustom('money_commission_withdraw_fenxiao')){
            //手续费 分销
            $field .= ',commission_withdrawfee_fenxiao,commission_withdrawfee_commissiondata';
        }
        if(getcustom('withdraw_custom')){
            //自定义提现方式
            $field .= ',custom_status,custom_name';
        }
        if(getcustom('commission_withdraw_score_conversion')){
            //佣金提现到账比例
            $field .= ',commission_conversion,score_conversion,score_doubling';
        }
        if(getcustom('commission_withdraw_buy_product')){
            //提现购买商品
            $field .= ',withdraw_buy_proid,withdraw_buy_pro_num';
        }
        if(getcustom('commission_withdraw_limit')){
            //佣金提现额度
            $field .= ',commission_withdraw_limit_set';
        }
        if(getcustom('commission_withdrawfee_fundpool')){
            $field .= ',commission_withdrawfee_fundpool';
        }
        if(getcustom('commission_withdraw_upload_invoice')){
            $field.= ',commission_withdraw_upload_invoice';
        }
        if(getcustom('commission_withdraw_limit_adset')){
            $field.= ',commission_withdraw_adset_st,commission_withdraw_adset_limit,commission_withdraw_adpid';
        }
        if(getcustom('withdraw_max_limit_text')){
            $field .= ',max_withdraw_limit_text';
        }
        if(getcustom('withdraw_wxpay_limit')){
            $field .= ',wxpay_withdraw_limit';
        }
		$set = Db::name('admin_set')->where('aid',aid)->field($field)->find();
        if($set['comwithdraw'] == 0){
            return $this->json(['status'=>-4,'msg'=>t('佣金').'提现功能未开启']);
        }

        $memberlevel = Db::name('member_level')->where('id',$this->member['levelid'])->find();
        if($memberlevel && $memberlevel['comwithdraw'] == 0){
            return $this->json(['status'=>-4,'msg'=>t('佣金').'提现未开启','url'=>'/activity/commission/index']);
        }

        if(getcustom('member_realname_verify')) {
            $realname_set = Db::name('member_realname_set')->where('aid', aid)->find();
            if ($realname_set['status'] == 1 && $realname_set['withdraw_status'] == 0 && $this->member['realname_status'] != 1){
                return $this->json(['status'=>-4,'msg'=>'未实名认证不可提现','url'=>'/pagesExt/my/setrealname']);
            }
        }
        if(getcustom('transfer_farsion') && ($set['withdraw_bankcard_xiaoetong'] == 1 || $set['withdraw_aliaccount_xiaoetong'] == 1)){
			$xetService = new  \app\common\Xiaoetong(aid);
			$res_sign = $xetService->getXiaoetongSigning(mid);
			if ($res_sign['status'] == 0){
                return $this->json(['status'=>-4,'msg'=>'需要签约才可提现','url'=>'/pagesA/my/withdrawXiaoetong']);
            }
		}
        $field = 'id,headimg,nickname,money,score,commission,aliaccount,bankname,bankcarduser,bankcardnum,realname';
        $show_cash_count = 0;
        if(getcustom('yx_cashback_yongjin')){
            $field .= ',cash_yongji_total,cashback_total';
            $show_cash_count = 1;
        }
        if(getcustom('business_fenxiao')){
            $field .= ',disable_withdraw';
        }
        if(getcustom('pay_huifu')){
            $field .= ',realname,usercard,usercard_begin_date,bank_province_code,bank_city_code,tel';
        }
		if(getcustom('commission_withdraw_freeze')){
			$field .= ',iscomwithdraw_freeze';
		}
		if(getcustom('member_lock')){
			$field .= ',lock_withdraw_givemoney';
		}
        if(getcustom('commission_duipeng_score_withdraw')){
			$field .= ',commission_withdraw_score';
		}
        if(getcustom('withdraw_custom')){
            //自定义提现方式
            $field .= ',customaccountname,customaccount,customtel';
        }
        if(getcustom('commission_withdraw_limit')){
            //佣金提现额度
            $field .= ',commission_withdraw_limit,commission_withdraw_limit_infinite';
        }
        $userinfo = Db::name('member')->where('id',mid)->field($field)->find();
        if(getcustom('business_fenxiao')){
            $bid = input('bid');
            $map = [];
            $map[] = ['aid','=',aid];
            $map[] = ['mid','=',mid];
            if($bid){
                $map[] = ['bid','=',$bid];
                $bonus_total = Db::name('business_fenxiao_bonus_total')->where($map)->value('remain');
                if($bonus_total>$userinfo['commission']){
                    $bonus_total = $userinfo['commission'];
                }
                $userinfo['commission'] = $bonus_total?:0;
            }else{
                $bonus_total = Db::name('business_fenxiao_bonus_total')->where($map)->sum('remain');
                $bonus_total = $bonus_total?:0;
                $userinfo['commission'] = bcsub($userinfo['commission'],$bonus_total,2);
            }
            if($userinfo['disable_withdraw']){
                //已冻结佣金提现
                $userinfo['commission'] = 0;
            }
        }

		if(getcustom('commission_withdraw_level_sxf')){
			$comwithdrawfee_level = json_decode($set['comwithdrawfee_level'],true);
			$set['comwithdrawfee'] = $comwithdrawfee_level[$memberlevel['id']]['sxf'];
		}

        $set['commission_withdraw_limit'] = false;
        if(getcustom('commission_withdraw_limit') && $set['commission_withdraw_limit_set'] == 1){
            $set['commission_withdraw_limit'] = ($userinfo['commission_withdraw_limit_infinite'] == 1) ? '无限' : round($userinfo['commission_withdraw_limit'],2);
        }

		if(request()->isPost()){
			$post = input('post.');
            Db::startTrans();
            $userinfo = Db::name('member')->where('id',mid)->field($field)->lock(true)->find();
            //验证今天提现了几次
            $nowtime = strtotime(date("Y-m-d",time()));//今日时间戳
            $daywithdrawnum   = 'daywithdrawnum'.mid.$nowtime;//会员今日时间参数
            $day_withdraw_num = cache($daywithdrawnum);//获取会员提现次数
            if($set['day_withdraw_num']<0){
                return $this->json(['status'=>0,'msg'=>'暂时不可提现']);
            }else if($set['day_withdraw_num']>0){
                if($day_withdraw_num && !empty($day_withdraw_num)){
                    $daynum = $day_withdraw_num+1;
                    if($daynum>$set['day_withdraw_num']){
                        return $this->json(['status'=>0,'msg'=>'今日申请提现次数已满，请明天继续申请提现']);
                    }
                }
            }
            if(getcustom('member_lock') && $userinfo['lock_withdraw_givemoney'] == 1){
				return $this->json(['status'=>0,'msg'=>'账号已锁定，请联系管理员处理！']);
			}

			if(getcustom('business_fenxiao') && $userinfo['disable_withdraw']){
                //已冻结佣金提现
                return $this->json(['status'=>0,'msg'=>'提现功能被冻结，请联系管理员处理！']);
            }
            if(getcustom('commission_withdraw_freeze') && $userinfo['iscomwithdraw_freeze']==1){
                //已冻结佣金提现
                return $this->json(['status'=>0,'msg'=>'提现已冻结，请购买商品解冻']);
            }

			if(getcustom('comwithdrawdate') && $set['comwithdrawdate'] && $set['comwithdrawdate']!='0'){
				$comwithdrawdate = explode(',',$set['comwithdrawdate']);
				$indate = false;
				$nowdata = date('d');
				foreach($comwithdrawdate as $date){
					if($date == $nowdata || '0'.$date == $nowdata){
						$indate = true; 
						break;
					}
				}
				if(!$indate) return $this->json(['status'=>0,'msg'=>'不在可提现日期内']);
			}
			
			if(getcustom('forcerebuy')){
				if($this->member['commission_isfreeze'] == 1){
					$forcerebuy = Db::name('forcerebuy')->where('aid',aid)->where('wfgtype',0)->where('status',1)->where("find_in_set('-1',gettj) or find_in_set('".$this->member['levelid']."',gettj)")->find();
					if($forcerebuy){
						return $this->json(['status'=>0,'msg'=>$forcerebuy['wfgtxtips']]);
					}else{
						return $this->json(['status'=>0,'msg'=>'对不起！你需要复购，才能解冻']);
					}
				}
			}

            if(getcustom('commission_withdraw_buy_product')){
                if($set['withdraw_buy_proid']) {
                    $isbuy = false;
                    $proname = '';
                    $buy_proid = explode(',',str_replace('，',',',$set['withdraw_buy_proid']));
                    $buy_pro_num = explode(',',str_replace('，',',',$set['withdraw_buy_pro_num']));

                    //查询上次提现日期作为查询条件
                    $last_withdraw_time = Db::name('member_commission_withdrawlog')
                        ->where('mid',mid)
                        ->where('aid',aid)
                        ->where('status','<>',2) //已驳回
                        ->order('createtime desc')
                        ->value('createtime');

                    $buywhere = [];
                    $buywhere[] = ['mid','=',mid];
                    $buywhere[] = ['aid','=',aid];
                    $buywhere[] = ['status','in','1,2,3'];
                    if($last_withdraw_time){
                        $buywhere[] = ['createtime','>=',$last_withdraw_time];
                    }
                    if(count($buy_pro_num) > 1) {
                        foreach($buy_proid as $bkey => $bproid){
                            //只要购买其中一个就满足提现条件
                            if($isbuy) break;
                            $pronum = $buy_pro_num[$bkey];
                            if(!$pronum) $pronum = 1;
                            $buynum = Db::name('shop_order_goods')->where('proid',$bproid)->where($buywhere)->sum('num');
                            if($buynum >= $pronum){
                                $isbuy = true;
                            }
                            $proname = Db::name('shop_product')->where('id',$bproid)->value('name');
                        }
                    }else {
                        $pronum = $buy_pro_num[0];
                        if(!$pronum) $pronum = 1;
                        $buynum = 0;
                        foreach($buy_proid as $bkey => $bproid){
                            //只要购买其中一个就满足提现条件
                            if($isbuy) break;
                            $buynum += Db::name('shop_order_goods')->where('proid',$bproid)->where($buywhere)->sum('num');
                            if($buynum >= $pronum){
                                $isbuy = true;
                            }
                            $proname = Db::name('shop_product')->where('id',$bproid)->value('name');
                        }
                    }
                    if(!$isbuy){
                        return $this->json(['status'=>0,'msg'=>'未购买【'.$proname.'】商品，无法提现']);
                    }
                }
            }
            if(empty($post['paytype'])){
				return $this->json(['status'=>0,'msg'=>'请选择提现方式']);
			}
            if(getcustom('transfer_farsion')){
				if($post['paytype']=='小额通支付宝'){
					if(!$this->member['aliaccount'] || !$this->member['aliaccountname']){
						return $this->json(['status'=>0,'msg'=>'请先设置支付宝账号']);
					}
				}
				if($post['paytype']=='小额通银行卡' && ($this->member['bankname']==''||$this->member['bankcarduser']==''||$this->member['bankcardnum']=='')){
					if($set['withdraw_bankcard'] == 0)
						return $this->json(['status'=>0,'msg'=>'银行卡提现功能未开启']);
						return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
				}
			}
            if(getcustom('extend_linghuoxin')){
                if($post['paytype']=='灵活薪支付宝' || $post['paytype']=='灵活薪银行卡'){
                    if($post['paytype']=='灵活薪支付宝'){
                        if($set['withdraw_aliaccount_linghuoxin'] != 1){
                            return $this->json(['status'=>0,'msg'=>'灵活薪支付宝提现功能未开启']);
                        }
                        if(empty($this->member['aliaccount']) || empty($this->member['aliaccountname'])){
                            return $this->json(['status'=>0,'msg'=>'请先设置支付宝账号']);
                        }
                    }
                    if($post['paytype']=='灵活薪银行卡'){
                        if($set['withdraw_bankcard_linghuoxin'] != 1){
                            return $this->json(['status'=>0,'msg'=>'灵活薪银行卡提现功能未开启']);
                        }
                        if(empty($this->member['bankname']) || empty($this->member['bankcarduser'])|| empty($this->member['bankcardnum'])){
                            return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
                        }
                    }
                    //查看是否签约
                    if(!empty($this->member['usercard'])){
                        $getchecksign = \app\custom\LinghuoxinCustom::getchecksign(aid,0,$this->member['usercard']);
                        if($getchecksign && $getchecksign['status'] == 1){
                            if($getchecksign['data']['status'] == 0){
                                return $this->json(['status'=>-4,'msg'=>'需要签约才可提现','url'=>'/pagesB/my/linghuoxinsign']);
                            }else if($getchecksign['data']['status'] == 1){
                                return $this->json(['status'=>0,'msg'=>'已实名认证，等待签约中']);
                            }
                        }else{
                            //return $this->json($getchecksign);
                            return $this->json(['status'=>-4,'msg'=>'需要签约才可提现','url'=>'/pagesB/my/linghuoxinsign']);
                        }
                    }else{
                        return $this->json(['status'=>-4,'msg'=>'需要签约才可提现','url'=>'/pagesB/my/linghuoxinsign']);
                    }
                }
            }
            if(getcustom('pay_allinpay')){
                if($post['paytype']=='通联支付银行卡'){
                    if($set['withdraw_bankcard_allinpayYunst'] != 1){
                        return $this->json(['status'=>0,'msg'=>'通联支付银行卡提现功能未开启']);
                    }
                    //通联支付 云商通
                    $yunstuser = Db::name('member_allinpay_yunst_user')->where('mid',mid)->where('aid',aid)->where('memberType',3)->find();
                    if(!$yunstuser){
                        return $this->json(['status'=>-4,'msg'=>'未创建通联会员，请前去创建','url'=>'/pagesC/allinpay/yunstMember']);
                    }
                    //查看是否实名认证并绑定银行卡
                    if(empty($yunstuser['identityNo'])){
                        return $this->json(['status'=>-4,'msg'=>'请先实名认证','url'=>'/pagesC/allinpay/yunstMember']);
                    }
                    if(empty($yunstuser['cardNo'])){
                        return $this->json(['status'=>-4,'msg'=>'请先绑定银行卡','url'=>'/pagesC/allinpay/yunstMember']);
                    }
                }
            }
            if(getcustom('withdraw_paycode')){
                if($post['paytype']=='收款码'){
                    if(!$this->member['wxpaycode'] && !$this->member['alipaycode']){
                        return $this->json(['status'=>0,'msg'=>'请先设置一个收款码']);
                    }
                }
            }
            if(getcustom('withdraw_custom')){
                //自定义提现方式
                if($post['paytype'] == $set['custom_name']){
                    if($set['custom_status'] == 0){
                        return $this->json(['status'=>0,'msg'=>'自定义提现方式未开启']);
                    }

                    if(!$this->member['customaccountname'] || $this->member['customaccount'] == '' || $this->member['customtel'] == ''){
                        return $this->json(['status'=>0,'msg'=>'请先设置'.$set['custom_name'].'账户信息']);
                    }
                }
            }
			if($post['paytype']=='支付宝' && $this->member['aliaccount']==''){
				return $this->json(['status'=>0,'msg'=>'请先设置支付宝账号']);
			}

			if(getcustom('yx_gift_pack')){
				$bank = Db::name('member_bank')->where('aid',aid)->where('mid',mid)->where('isdefault',1)->find();
				if($post['paytype']=='银行卡' && !$bank){
					return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
				}
				$this->member['bankname'] = $bank['bankname'];
				$this->member['bankcarduser'] = $bank['bankcarduser'];
				$this->member['bankcardnum'] = $bank['bankcardnum'];
				$this->member['bankaddress'] = $bank['bankaddress'];
			}else{
				if($post['paytype']=='银行卡' && ($this->member['bankname']==''||$this->member['bankcarduser']==''||$this->member['bankcardnum']=='')){
					return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
				}
			}
            if($post['paytype']=='银行卡' && $set['withdraw_huifu'] == 1 && ($this->member['realname']==''||$this->member['tel']==''||$this->member['usercard']==''||$this->member['huifu_id']==''||$this->member['bankname']==''||$this->member['bankcarduser']==''||$this->member['bankcardnum']=='')){
                return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
            }
            if(getcustom('pay_huifu')) {
                if ($post['paytype'] == '银行卡' && $set['withdraw_huifu'] == 1 && $this->member['huifu_token_no'] == '') {
                    return $this->json(['status' => 0, 'msg' => '请完善汇付进件资料']);
                }
            }

			$money = $post['money'];
            if($money<=0 || $money < $set['comwithdrawmin']){
                return $this->json(['status'=>0,'msg'=>'提现金额必须大于'.($set['comwithdrawmin']?$set['comwithdrawmin']:0)]);
            }
            if(getcustom('withdraw_mul') && !getcustom('money_withdraw_mul')){
                if($set['comwithdrawmul']>0 && !isMulInt($money, $set['comwithdrawmul'])){
                  return $this->json(['status'=>0,'msg'=>'提现金额必须为'.$set['comwithdrawmul'].'整数倍']);
                }
            }
			if($money > $userinfo['commission']){
				return $this->json(['status'=>0,'msg'=>'可提现'.t('余额').'不足']);
			}

            if(getcustom('commission_withdraw_limit') && $set['commission_withdraw_limit_set'] == 1){
                if($userinfo['commission_withdraw_limit_infinite'] == 0 && $money > $userinfo['commission_withdraw_limit']){
                    return $this->json(['status'=>0,'msg'=>t('佣金提现额度').'不足，剩余额度：'.round($userinfo['commission_withdraw_limit'],2)]);
                }
            }

            //如果需要消耗积分的 则取积分可兑换的最小值
            $needScoreNum = 0;
            if(getcustom('commission_withdraw_need_score') && $set['comwithdraw_need_score']==1){
                $exchange_score_num = $set['commission_score_exchange_num']??1;
                $scoreExchangeCommission = $this->member['score']/$exchange_score_num;
                $canWithdrawNum = min($scoreExchangeCommission,$this->member['commission']);
                if($money>$canWithdrawNum){
                    return $this->json(['status'=>0,'msg'=>t('积分').'不足，最多可提现'.$canWithdrawNum]);
                }
                $needScoreNum = $money*$exchange_score_num;
            }
            if(getcustom('withdraw_wxpay_limit')){
                if ($post['paytype'] == '微信钱包' && $set['wxpay_withdraw_limit'] > 0) {
                    $set['comwithdrawmax'] = $set['wxpay_withdraw_limit']; //微信提现 覆盖 提现最高金额
                }
            }
            if($set['comwithdrawmax']>0 && $money > $set['comwithdrawmax']){
                if(getcustom('withdraw_max_limit_text') && $set['max_withdraw_limit_text']){
                    return $this->json(['status'=>-4,'msg'=>$set['max_withdraw_limit_text']]);
                }
				return $this->json(['status'=>0,'msg'=>'提现金额过大，单笔'.t('余额').'提现最高金额为'.$set['comwithdrawmax'].'元']);
			}

            //对碰同比减少提现积分
            if(getcustom('commission_duipeng_score_withdraw')){
                if ($set['comwithdraw_integer_type']) {
                    $comwithdraw_integer_type = $set['comwithdraw_integer_type']; 
                    if($comwithdraw_integer_type == 1 && floor($money) != $money){			  
                        return $this->json(['status'=>0,'msg'=>'请输入整数']);
                    }else if($comwithdraw_integer_type == 2 && ($money % 10) != 0){
                        return $this->json(['status'=>0,'msg'=>'请输入10整倍整数']);
                    }else if($comwithdraw_integer_type == 3 && ($money % 100) != 0){
                        return $this->json(['status'=>0,'msg'=>'请输入100整倍整数']);
                    }else if($comwithdraw_integer_type == 4 && ($money % 1000) != 0){
                        return $this->json(['status'=>0,'msg'=>'请输入1000整倍整数']);
                    }
                }
                if($set['comwithdraw_duipeng_score_bili'] > 0){
                    $commission_withdraw_score = $userinfo['commission_withdraw_score'];
                    $use_commission_withdraw_score = $commission_withdraw_score / $set['comwithdraw_duipeng_score_bili'];
                    $need_commission_withdraw_score = ceil($money * $set['comwithdraw_duipeng_score_bili']);
                    if($use_commission_withdraw_score < $money){
                        $use_money = $money;
                        if($comwithdraw_integer_type == 1){
                            $use_money =  floor($use_commission_withdraw_score);
                        }else if($comwithdraw_integer_type == 2){
                            $use_money =  floor($use_commission_withdraw_score/10) * 10;
                        }else if($comwithdraw_integer_type == 3){
                            $use_money =  floor($use_commission_withdraw_score/100) * 100;
                        }else if($comwithdraw_integer_type == 4){
                            $use_money =  floor($use_commission_withdraw_score/1000) * 1000;
                        }
                        return $this->json(['status'=>0,'msg'=>'提现积分不足，最高可提现金额为'.$use_money.'元']);
                      }
                }
            }
            if(getcustom('commission_withdraw_upload_invoice') && $set['commission_withdraw_upload_invoice'] == 2){
                if(!$post['invoice']){
                    return $this->json(['status'=>0,'msg'=>'请上传发票']);
                }
            }
			//验证小数点后两位
            $money_arr = explode('.',$money);
            if($money_arr && $money_arr[1]){
                $dot_len = strlen($money_arr[1]);
                if($dot_len>2){
                    return $this->json(['status'=>0,'msg'=>'提现金额最小位数为小数点后两位']);
                }
            }

			$ordernum = date('ymdHis').aid.rand(1000,9999);
			$record['aid'] = aid;
			$record['mid'] = mid;
			$record['createtime']= time();

            $real_money = dd_money_format($money*(1-$set['comwithdrawfee']*0.01)) ;
			if($real_money <= 0) {
                return $this->json(['status'=>0,'msg'=>'提现金额有误']);
            }
			if(getcustom('yx_cashback_yongjin')){
                //提现金额减掉已返现金额
                //查询返现数据，按时间顺序每次查一条
                $cashback = Db::name('shop_order_goods_cashback')->where(['mid'=>mid,'cashback_yongjin'=>1])->order('id asc')->find();
                $record['cashback_id'] = $cashback['id']?:0;
                $back_money = 0;
                if($cashback){
                    if($cashback['back_type']==1){
                        $back_money = bcmul($cashback['moneyave'],$cashback['money_sendnum'],2);
                    }
                    if($cashback['back_type']==2){
                        $back_money = bcmul($cashback['commissionave'],$cashback['commission_sendnum'],2);
                    }
                    if($cashback['back_type']==3){
                        $back_money = bcmul($cashback['scoreave'],$cashback['score_sendnum'],2);
                    }
                }
                $real_money = bcsub($real_money,$back_money,2);
                if($real_money<=0){
                    return $this->json(['status'=>0,'msg'=>'提现金额有误,可提现佣金小于返现金额']);
                }
                //终止正在返现的商品
                Db::name('shop_order_goods_cashback')->where('id',$cashback['id'])->update(['cashback_yongjin'=>2]);
            }
			//提现到账通证
            if(getcustom('product_givetongzheng')){
                $commissionwithdraw2tongzheng = $set['commissionwithdraw2tongzheng'];
                $tongzheng_num = bcmul($money,$commissionwithdraw2tongzheng/100,3);
                $real_money = bcsub($real_money,$tongzheng_num,2);
                if($real_money <= 0) {
                    return $this->json(['status'=>0,'msg'=>'提现金额有误']);
                }
                $record['tongzheng'] = $tongzheng_num;
            }

            if(getcustom('commission_withdraw_score_conversion')){
                //积分提现到账比例
                if($set['score_conversion'] > 0){
                    $conversion_money = dd_money_format($real_money*($set['score_conversion'] * 0.01));
                    //积分翻倍
                    if($set['score_doubling'] > 0){
                        $conversion_money *= $set['score_doubling'];
                    }
                    $record['conversion_score'] = $conversion_money; //1:1转换积分
                }

                //佣金提现到账比例
                if($set['commission_conversion'] > 0){
                    $real_money = dd_money_format($real_money*($set['commission_conversion'] * 0.01));
                }
            }
            $record['money'] = $real_money;
			$record['txmoney'] = $money;
			if($post['paytype']=='支付宝'){
				$record['aliaccountname'] = $this->member['aliaccountname'];
				$record['aliaccount'] = $this->member['aliaccount'];
			}
			if($post['paytype']=='银行卡'){
				$record['bankname'] = $this->member['bankname'].($this->member['bankaddress'] ? ' '.$this->member['bankaddress'] : '');;
				$record['bankcarduser'] = $this->member['bankcarduser'];
				$record['bankcardnum'] = $this->member['bankcardnum'];
			}
            if($post['paytype']=='银行卡' && $set['withdraw_huifu'] == 1){
                $record['huifu_id'] = $this->member['huifu_id'];
            }
            if(getcustom('transfer_farsion')){
                $account_no = '';
                $xiaoetong_type = 1;
				if($post['paytype']=='小额通支付宝'){
					$record['aliaccountname'] = $this->member['aliaccountname'];
					$record['aliaccount'] = $this->member['aliaccount'];
                    $account_no = $record['aliaccount'];
                    $xiaoetong_type = 2;
				}
				if($post['paytype']=='小额通银行卡'){
					$record['bankname'] = $this->member['bankname'] . ($this->member['bankaddress'] ? ' '.$this->member['bankaddress'] : '');
					$record['bankcarduser'] = $this->member['bankcarduser'];
					$record['bankcardnum'] = $this->member['bankcardnum'];
                    $account_no = $record['bankcardnum'];
                    $xiaoetong_type = 1;
				}
			}
            if(getcustom('extend_linghuoxin')){
                if($post['paytype']=='灵活薪支付宝' || $post['paytype']=='灵活薪银行卡'){
                    //查看账号余额
                    // $getbalance = \app\custom\LinghuoxinCustom::getbalance(aid);
                    // if($getbalance['status'] == 0){
                    //     return $this->json($getbalance);
                    // }
                    // if($record['money']>$getbalance['data']['availableBalance']){
                    //     return $this->json(['status'=>0,'msg'=>'灵活薪账号金额不足，请减少提现金额后重试']);
                    // }
                    if($post['paytype']=='灵活薪支付宝'){
                        $record['aliaccountname'] = $this->member['aliaccountname'];
                        $record['aliaccount'] = $this->member['aliaccount'];
                    }
                    if($post['paytype']=='灵活薪银行卡'){
                        $record['bankname']    = $this->member['bankname'] . ($this->member['bankaddress'] ? ' '.$this->member['bankaddress'] : '');
                        $record['bankcarduser']= $this->member['bankcarduser'];
                        $record['bankcardnum'] = $this->member['bankcardnum'];
                    }
                }
            }
            if(getcustom('pay_allinpay')){
                if($post['paytype']=='通联支付银行卡'){
                    $record['bankname']    = '';
                    $record['bankcarduser']= $yunstuser['name'];
                    $record['bankcardnum'] = $yunstuser['cardNo'];
                }
            }
            if(getcustom('withdraw_paycode')){
                if($post['paytype']=='收款码'){
                    if($this->member['wxpaycode']){
                        $record['wxpaycode'] = $this->member['wxpaycode'];
                    }
                    if($this->member['alipaycode']){
                        $record['alipaycode'] = $this->member['alipaycode'];
                    }
                }
            }
            if(getcustom('withdraw_custom')){
                //自定义提现方式
                if($post['paytype'] == $set['custom_name']){
                    $record['customaccountname'] = $this->member['customaccountname'];
                    $record['customaccount'] = $this->member['customaccount'];
                    $record['customtel'] = $this->member['customtel'];
                    $record['customwithdraw'] = 1;
                }
            }
			$record['ordernum'] = $ordernum;
			$record['paytype'] = $post['paytype'];
			$record['platform'] = platform;
            if(getcustom('commission_withdraw_need_score')){
                $record['need_score'] = $needScoreNum;
            }
            if(getcustom('commission_duipeng_score_withdraw')){
                $record['need_commission_withdraw_score'] = $need_commission_withdraw_score;
            }
            if(getcustom('business_fenxiao')){
                $bid = input('bid')?:0;
                $record['bid'] = $bid;
            }
			if(getcustom('commission_withdraw_freeze')){
				//查看已申请提现金额
				$comwithdrawmoney = Db::name('member_commission_withdrawlog')->where('aid',aid)->where('mid',mid)->where('status',3)->sum('txmoney');
				$totalmoney = $comwithdrawmoney+$money;
				if(in_array('1',explode(',',$set['comwithdraw_freeze'])) && $set['comwithdraw_totalmoney']>0 && $totalmoney>=$set['comwithdraw_totalmoney']){
					 Db::name('member')->where('id',mid)->update(['iscomwithdraw_freeze'=>1]);
				}
			}
            if(getcustom('fengdanjiangli')){
                $record['money'] = dd_money_format($record['money']);
                $comwithdrawbl = Db::name('admin_set')->where('aid',aid)->value('comwithdrawbl');
                
                if($comwithdrawbl > 0 && $comwithdrawbl < 100){
                    $paymoney = round($record['money'] * $comwithdrawbl * 0.01,2);
                    $tomoney = round($record['money'] - $paymoney,2);//可提现比例不是100%时,不可提现部分在提现时直接转换为余额
                }else{
                    $paymoney = $record['money'];
                    $tomoney = 0;
                }
                $record['paymoney'] =  $paymoney;
                $record['tomoney'] =  $tomoney;
            }
            if(getcustom('money_commission_withdraw_fenxiao')){
                $commission_totalprice = dd_money_format($money -  $real_money);
                $ogupdate = \app\common\Fenxiao::withdrawfeeFenxiao($set,$this->member,$commission_totalprice,'commission_withdraw');
                $record['parent1'] = $ogupdate['parent1'];
                $record['parent2'] = $ogupdate['parent2'];
                $record['parent3'] = $ogupdate['parent3'];
                $record['parent4'] = $ogupdate['parent4'];
                $record['parent1commission'] = $ogupdate['parent1commission'];
                $record['parent2commission'] = $ogupdate['parent2commission'];
                $record['parent3commission'] = $ogupdate['parent3commission'];
                $record['parent4commission'] = $ogupdate['parent4commission'];
            }

            if(getcustom('commission_withdraw_limit') && $set['commission_withdraw_limit_set'] == 1 && $userinfo['commission_withdraw_limit_infinite'] == 0){
                //使用的提现额度
                $record['commission_withdraw_limit'] = $money;
            }
            if(getcustom('commission_withdraw_upload_invoice')){
                $record['invoice'] = $post['invoice'];
            }

            $res = \app\common\Member::addcommission(aid,mid,0,-$money,t('佣金').'提现');
            if(!$res || ($res && $res['status'] !=1)){
                \think\facade\Log::write('commissionwithdrawfail_'.mid.'_'.$money);
                return json(['status'=>0,'msg'=>'提现失败']);
            }

			$recordid = Db::name('member_commission_withdrawlog')->insertGetId($record);
            if($recordid){
                //记录今天提现了几次
                if(!$day_withdraw_num || empty($day_withdraw_num)){
                    cache($daywithdrawnum,1,86400);
                }else{
                    $daynum = $day_withdraw_num+1;
                    cache($daywithdrawnum,$daynum,86400);
                }

                if(getcustom('commission_withdrawfee_fundpool')){
                    if($set['commission_withdrawfee_fundpool'] && $set['commission_withdrawfee_fundpool']>0){
                        //基金池
                        $commission_totalprice = dd_money_format($money -  $real_money);
                        $fundpool = $commission_totalprice * $set['commission_withdrawfee_fundpool'] * 0.01;
                        if($fundpool>0){
                            //增加基金池记录
                            $params = ['aid'=>aid,'status'=>0,
                            'fundpool'=>$fundpool,'logid'=>$recordid,'fee'=>$commission_totalprice,'ratio'=>$set['commission_withdrawfee_fundpool'],
                            'frommid'=>mid,'type'=>'commission_withdrawfee','remark'=>t('佣金').'提现增加'];
                            \app\common\Admin::addfundpool($params);
                        }
                    }
                }
            }

            if($needScoreNum>0){
                \app\common\Member::addscore(aid,mid,-$needScoreNum,t('佣金').'提现消耗');
            }
            if(getcustom('commission_duipeng_score_withdraw')){
                if($need_commission_withdraw_score>0){
                    \app\common\Member::add_commission_withdraw_score(aid,mid,-$need_commission_withdraw_score,t('佣金').'提现消耗');
                }
            }
            if(getcustom('business_fenxiao')){
                $bid = input('bid');
                if($bid>0){
                    Db::name('business_fenxiao_bonus_total')
                        ->where('mid',mid)
                        ->where('bid',$bid)
                        ->dec('remain',$money)
                        ->inc('withdraw',$money)
                        ->update();
                }
            }
            if(getcustom('money_commission_withdraw_fenxiao')){
                $record['id'] = $recordid;
                if($ogupdate){
                    \app\common\Fenxiao::addWithdrawCommissionRecord($ogupdate,$record,'commission_withdraw');
                }
            }

            //减掉用户可提现额度
            if(getcustom('commission_withdraw_limit') && $set['commission_withdraw_limit_set'] == 1 && $userinfo['commission_withdraw_limit'] > 0 && $userinfo['commission_withdraw_limit_infinite'] == 0){
                Db::name('member')->where('aid', aid)->where('id', mid)->update(['commission_withdraw_limit' => Db::raw("commission_withdraw_limit-" . $money)]);
            }
            if(getcustom('commission_withdraw_limit_adset')){
                $is_play_adset = input('param.is_play_adset');
                if($is_play_adset){
                    Db::name('member')->where('aid', aid)->where('id', mid)->update(['commission_withdraw_limit' => Db::raw("commission_withdraw_limit+" . $set['commission_withdraw_adset_limit'])]); 
                }
            }
			Db::commit();
			$tmplcontent = array();
			$tmplcontent['first'] = '有客户申请'.t('佣金').'提现';
			$tmplcontent['remark'] = '点击进入查看~';
			$tmplcontent['keyword1'] = $this->member['nickname'];
			$tmplcontent['keyword2'] = date('Y-m-d H:i');
			$tmplcontent['keyword3'] = $money.'元';
			$tmplcontent['keyword4'] = $post['paytype'];
			\app\common\Wechat::sendhttmpl(aid,0,'tmpl_withdraw',$tmplcontent,m_url('admin/finance/comwithdrawlog'));

			$tmplcontent = [];
			$tmplcontent['name3'] = $this->member['nickname'];
			$tmplcontent['amount1'] = $money.'元';
			$tmplcontent['date2'] = date('Y-m-d H:i');
			$tmplcontent['thing4'] = '提现到'.$post['paytype'];
			\app\common\Wechat::sendhtwxtmpl(aid,0,'tmpl_withdraw',$tmplcontent,'admin/finance/comwithdrawlog');

            //小额通提现
			if(getcustom('transfer_farsion')){
				if($set['withdraw_autotransfer'] &&  ($post['paytype'] == '小额通支付宝' || $post['paytype'] == '小额通银行卡' )){
					$xetService = new  \app\common\Xiaoetong(aid);
					//导入数据
                    $record['id'] = $recordid;
					$xet_res = $xetService->sendData($record,$this->member,'佣金提现');	
					//print_r($res);die;
					if($xet_res['code'] == 0){
						Db::name('member_commission_withdrawlog')->where('aid',aid)->where('id',$recordid)->update(['status'=>1]);			
						return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款','data'=>[]]);
					}else{
						\app\common\Member::addcommission(aid,mid,0,$money,t('佣金').'提现返还');                        
						Db::name('member_commission_withdrawlog')->where('id',$recordid)->update(['status' => 2,'reason'=>'快商小额通推送失败'.$xet_res['msg']]);
						return $this->json(['status'=>1,'msg'=>'提现失败'.$xet_res['msg'],'data'=>[]]);
					}
				}
            }
            if(getcustom('extend_linghuoxin')){
                //灵活薪提现
                if($set['withdraw_autotransfer'] && ($post['paytype'] == '灵活薪支付宝' || $post['paytype'] == '灵活薪银行卡' )){
                    $gopay = \app\custom\LinghuoxinCustom::gopay(aid,0,$this->member,$recordid,$record,$post['paytype'],2);
                    if($gopay && $gopay['status'] == 1){
                        $updata = [];
                        $updata['status']   = 1;
                        $updata['taskNo']   = $gopay['data']['taskNo'];
                        $updata['taskdata'] = json_encode($gopay['data']);
                        Db::name('member_commission_withdrawlog')->where('id',$recordid)->update($updata);
                        return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款','data'=>[]]);
                    }else{
                        $msg = $gopay && $gopay['msg']?$gopay['msg']:'';
                        Db::name('member_commission_withdrawlog')->where('id',$recordid)->update(['reason'=>'灵活薪推送失败'.$msg]);
                        return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
                        // $msg = $gopay && $gopay['msg']?$gopay['msg']:'';
                        // \app\common\Member::addcommission(aid,mid,0,$money,t('佣金').'提现返还');
                        // Db::name('member_commission_withdrawlog')->where('id',$recordid)->update(['status' => 2,'reason'=>'灵活薪推送失败'.$msg]);
                        // return $this->json(['status'=>0,'msg'=>'提现失败','data'=>[]]);
                    }
                }
            }
            if(getcustom('pay_allinpay')){
                //通联支付 云商通
                if($set['withdraw_autotransfer'] && $post['paytype'] == '通联支付银行卡'){
                    $withdrawApply = \app\custom\AllinpayYunst::withdrawApply(aid,$this->member,$recordid,$record,2);
                    if($withdrawApply && $withdrawApply['status'] == 1){
                        $updata = [];
                        $updata['status']   = 1;
                        $updata['allinpayorderNo'] = $withdrawApply['data']['orderNo'];
                        Db::name('member_withdrawlog')->where('id',$recordid)->update($updata);
                        return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款','data'=>[]]);
                    }else{
                        $msg = $withdrawApply && $withdrawApply['msg']?$withdrawApply['msg']:'';
                        Db::name('member_withdrawlog')->where('id',$recordid)->update(['reason'=>'通联推送失败'.$msg]);
                        return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
                    }
                }
            }
            $need_confirm = 0;//是否需要用户主动确认
			if($set['withdraw_autotransfer'] && ($post['paytype'] == '微信钱包' || $post['paytype'] == '银行卡')){
                if(getcustom('fengdanjiangli')){
                    $paymoney = $record['paymoney'];
                    $tomoney = $record['tomoney'];
                }else{
                    $paymoney = $record['money'];
                    $tomoney = 0;
                }
                $paymoney = dd_money_format($paymoney);
                if($set['wx_transfer_type']==1 && $post['paytype'] == '微信钱包'){
                    //使用了新版的商家转账功能
                    $paysdk = new WxPayV3(aid,mid,platform);
                    $rs = $paysdk->transfer($record['ordernum'],$paymoney,'',t('佣金').'提现','member_commission_withdrawlog',$recordid);
                    if($rs['status']==1){
                        $data = [
                            'status' => '4',//状态改为处理中，用户确认收货后再改为已打款
                            'wx_package_info' => $rs['data']['package_info'],//用户确认页面的信息
                            'wx_state' => $rs['data']['state'],//转账状态
                            'wx_transfer_bill_no' => $rs['data']['transfer_bill_no'],//微信单号
                        ];
                        Db::name('member_commission_withdrawlog')->where('id',$recordid)->update($data);
                        $need_confirm = 1;
                    }else{
                        $data = [
                            'wx_transfer_msg' => $rs['msg'],
                        ];
                        Db::name('member_commission_withdrawlog')->where('id',$recordid)->update($data);
                        if(strpos($rs['msg'],'资金不足')!==false){
                            $rs['msg'] = '商户资金不足，请联系管理员';
                        }
                        return $this->json(['status'=>0,'msg'=>'提现失败【'.$rs['msg'].'】']);
                    }
                }else{
                    $rs = \app\common\Wxpay::transfers(aid,mid,$paymoney,$record['ordernum'],platform,t('佣金').'提现');
                    if($rs['status']==1){
                        Db::name('member_commission_withdrawlog')->where('aid',aid)->where('id',$recordid)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['resp']['payment_no']]);
                    }
                }
				if($rs['status']==0){
					return json(['status'=>1,'msg'=>'提交成功,请等待打款']);
				}else{
                    if($tomoney > 0){
                        \app\common\Member::addmoney(aid,mid,$tomoney,t('佣金').'提现');
                    }
					//记录用户累计提现
                    if(getcustom('yx_cashback_yongjin')){
                        Db::name('member')->where('id',mid)->inc('cash_yongji_total',$record['money'])->update();
                    }
					//提现成功通知
					$tmplcontent = [];
					$tmplcontent['first'] = '您的提现申请已打款，请留意查收';
					$tmplcontent['remark'] = '请点击查看详情~';
					$tmplcontent['money'] = (string) round($record['money'],2);
					$tmplcontent['timet'] = date('Y-m-d H:i',$record['createtime']);
                    $tempconNew = [];
                    $tempconNew['amount2'] = (string) $record['money'];//提现金额
                    $tempconNew['time3'] = date('Y-m-d H:i',$record['createtime']);//提现时间
					\app\common\Wechat::sendtmpl(aid,$record['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'),$tempconNew);
					//订阅消息
					$tmplcontent = [];
					$tmplcontent['amount1'] = $record['money'];
					$tmplcontent['thing3'] = '微信打款';
					$tmplcontent['time5'] = date('Y-m-d H:i');
					
					$tmplcontentnew = [];
					$tmplcontentnew['amount3'] = $record['money'];
					$tmplcontentnew['phrase9'] = '微信打款';
					$tmplcontentnew['date8'] = date('Y-m-d H:i');
					\app\common\Wechat::sendwxtmpl(aid,$record['mid'],'tmpl_tixiansuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
					//短信通知
					if($this->member['tel']){
						\app\common\Sms::send(aid,$this->member['tel'],'tmpl_tixiansuccess',['money'=>$record['money']]);
					}
					return json(['status'=>1,'msg'=>$rs['msg'],'need_confirm'=>$need_confirm,'id'=>$recordid]);
				}
			}
			if(getcustom('alipay_auto_transfer')){
				if($set['ali_withdraw_autotransfer'] && $post['paytype'] == '支付宝'){
	                if($set['comwithdrawbl'] > 0 && $set['comwithdrawbl'] < 100){
	                    $paymoney = round($record['money'] * $set['comwithdrawbl'] * 0.01,2);
	                    $tomoney = round($record['money'] - $paymoney,2);
	                }else{
	                    $paymoney = $record['money'];
	                    $tomoney = 0;
	                }
	                $rs = \app\common\Alipay::transfers(aid,$record['ordernum'],$paymoney,t('佣金').'提现',$record['aliaccount'],$record['aliaccountname'],t('佣金').'提现');
	                if($rs['status']==0){
	                    $sub_msg = $rs['sub_msg']?$rs['sub_msg']:'';
	                    if($sub_msg){
	                        Db::name('member_commission_withdrawlog')->where('aid',aid)->where('id',$recordid)->update(['reason'=>$sub_msg]);
	                    }
	                    return json(['status'=>1,'msg'=>'提交成功,请等待打款']);
	                }else{
	                    if($tomoney > 0){
	                        \app\common\Member::addmoney(aid,mid,$tomoney,t('佣金').'提现');
	                    }
	                    Db::name('member_commission_withdrawlog')->where('aid',aid)->where('id',$recordid)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['pay_fund_order_id']]);
	                    //提现成功通知
	                    $tmplcontent = [];
	                    $tmplcontent['first'] = '您的提现申请已打款，请留意查收';
	                    $tmplcontent['remark'] = '请点击查看详情~';
	                    $tmplcontent['money'] = (string) round($record['money'],2);
	                    $tmplcontent['timet'] = date('Y-m-d H:i',$record['createtime']);
                        $tempconNew = [];
                        $tempconNew['amount2'] = (string) $record['money'];//提现金额
                        $tempconNew['time3'] = date('Y-m-d H:i',$record['createtime']);//提现时间
	                    \app\common\Wechat::sendtmpl(aid,$record['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'),$tempconNew);
	                    //订阅消息
	                    $tmplcontent = [];
	                    $tmplcontent['amount1'] = $record['money'];
	                    $tmplcontent['thing3'] = '支付宝打款';
	                    $tmplcontent['time5'] = date('Y-m-d H:i');
	                    
	                    $tmplcontentnew = [];
	                    $tmplcontentnew['amount3'] = $record['money'];
	                    $tmplcontentnew['phrase9'] = '支付宝打款';
	                    $tmplcontentnew['date8'] = date('Y-m-d H:i');
	                    \app\common\Wechat::sendwxtmpl(aid,$record['mid'],'tmpl_tixiansuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
	                    //短信通知
	                    if($this->member['tel']){
	                        \app\common\Sms::send(aid,$this->member['tel'],'tmpl_tixiansuccess',['money'=>$record['money']]);
	                    }
	                    return json(['status'=>1,'msg'=>$rs['msg']]);
	                }
	            }
			}
			if(getcustom('pay_adapay')){
                if($set['withdraw_autotransfer'] && $post['paytype'] == '汇付天下银行卡'){
                    $adapay = Db::name('adapay_member')->where('aid',aid)->where('mid',mid)->find();
                    if($set['comwithdrawbl'] > 0 && $set['comwithdrawbl'] < 100){
                        $paymoney = round($record['money'] * $set['comwithdrawbl'] * 0.01,2);
                        $tomoney = round($record['money'] - $paymoney,2);
                    }else{
                        $paymoney = $record['money'];
                        $tomoney = 0;
                    }
                    $paymoney = dd_money_format($paymoney);
                    $rs = \app\custom\AdapayPay::balancePay(aid,'h5',$adapay['member_id'],$record['ordernum'],$paymoney);
                    if($rs['status'] == 0){
                        $sub_msg = $rs['msg']?$rs['msg']:'';
                        if($sub_msg){
                            Db::name('member_commission_withdrawlog')->where('aid',aid)->where('id',$recordid)->update(['reason'=>$sub_msg]);     
                        }
                        return json(['status'=>1,'msg'=>'提交成功,请等待打款']);
                    }else{
                        //从用户余额中进行提现到银行卡
                        $drs = \app\custom\AdapayPay::drawcash(aid,'h5',$adapay['member_id'],$record['ordernum'],$paymoney);
                        if($drs['status'] == 0){
                            Db::name('member_commission_withdrawlog')->where('aid',aid)->where('id',$record['id'])->update(['reason'=>$drs['msg']]);
                            return json(['status'=>0,'msg'=>$drs['msg']]);
                        }
                        if($tomoney > 0){
                            \app\common\Member::addmoney(aid,mid,$tomoney,t('佣金').'提现');
                        }
                        Db::name('member_commission_withdrawlog')->where('aid',aid)->where('id',$recordid)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['resp']['payment_no']]);
                        //提现成功通知
                        $tmplcontent = [];
                        $tmplcontent['first'] = '您的提现申请已打款，请留意查收';
                        $tmplcontent['remark'] = '请点击查看详情~';
                        $tmplcontent['money'] = (string) round($record['money'],2);
                        $tmplcontent['timet'] = date('Y-m-d H:i',$record['createtime']);
                        $tempconNew = [];
                        $tempconNew['amount2'] = (string) $record['money'];//提现金额
                        $tempconNew['time3'] = date('Y-m-d H:i',$record['createtime']);//提现时间
                        \app\common\Wechat::sendtmpl(aid,$record['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'),$tempconNew);
                        //订阅消息
                        $tmplcontent = [];
                        $tmplcontent['amount1'] = $record['money'];
                        $tmplcontent['thing3'] = '汇付天下打款';
                        $tmplcontent['time5'] = date('Y-m-d H:i');

                        $tmplcontentnew = [];
                        $tmplcontentnew['amount3'] = $record['money'];
                        $tmplcontentnew['phrase9'] = '汇付天下打款';
                        $tmplcontentnew['date8'] = date('Y-m-d H:i');
                        \app\common\Wechat::sendwxtmpl(aid,$record['mid'],'tmpl_tixiansuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
                        //短信通知
                        if($this->member['tel']){
                            \app\common\Sms::send(aid,$this->member['tel'],'tmpl_tixiansuccess',['money'=>$record['money']]);
                        }
                        return json(['status'=>1,'msg'=>$rs['msg']]);
                    }
                }
            }
            if(getcustom('pay_allinpay')){
                //通联支付提现
                if($set['withdraw_autotransfer'] && ($post['paytype'] == '通联支付' )){
                    $gopay = \app\custom\Allinpay::gopay(aid,0,$this->member,$recordid,$record,$post['paytype'],2);
                    if($gopay && $gopay['status'] == 1){
                        $updata = [];
                        $updata['status']   = 1;
                        $updata['taskNo']   = $gopay['data']['taskNo'];
                        $updata['taskdata'] = json_encode($gopay['data']);
                        Db::name('member_commission_withdrawlog')->where('id',$recordid)->update($updata);
                        return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款','data'=>[]]);
                    }else{
                        $msg = $gopay && $gopay['msg']?$gopay['msg']:'';
                        Db::name('member_commission_withdrawlog')->where('id',$recordid)->update(['reason'=>'灵活薪推送失败'.$msg]);
                        return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
                    }
                }
            }
			return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
		}

		//订阅消息
		$wx_tmplset = Db::name('wx_tmplset')->where('aid',aid)->find();
		$tmplids = [];
		if($wx_tmplset['tmpl_tixiansuccess_new']){
			$tmplids[] = $wx_tmplset['tmpl_tixiansuccess_new'];
		}elseif($wx_tmplset['tmpl_tixiansuccess']){
			$tmplids[] = $wx_tmplset['tmpl_tixiansuccess'];
		}
		if($wx_tmplset['tmpl_tixianerror_new']){
			$tmplids[] = $wx_tmplset['tmpl_tixianerror_new'];
		}elseif($wx_tmplset['tmpl_tixianerror']){
			$tmplids[] = $wx_tmplset['tmpl_tixianerror'];
		}
		$rdata = [];


        $userinfo['show_cash_count'] = $show_cash_count;
        //如果需要消耗积分的 则取积分可兑换的最小值
        if(getcustom('commission_withdraw_need_score') && $set['comwithdraw_need_score']==1){
            $exchange_score_num = $set['commission_score_exchange_num']??1;
            $scoreExchangeCommission = $userinfo['score']/$exchange_score_num;
            $userinfo['commission'] = min($userinfo['commission'],$scoreExchangeCommission);
        }
        $moeny_weishu = 2;
        if(getcustom('fenhong_money_weishu')){
            $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
        }
        $moeny_weishu = $moeny_weishu?$moeny_weishu:2;
        $userinfo['commission'] = dd_money_format($userinfo['commission'],$moeny_weishu);
        if(getcustom('pay_adapay')){
            $adapay = Db::name('adapay_member')->where('aid',aid)->where('mid',mid)->find();
            $userinfo['to_set_adapay'] = 0;
            if(!$adapay ||  !$adapay['settle_account_id']){
                $userinfo['to_set_adapay'] = 1;
            }
        }

		$selectbank = false;
		if(getcustom('yx_gift_pack')){
			$selectbank = true;
			//选择默认银行卡
			$bank = Db::name('member_bank')->where('aid',aid)->where('mid',mid)->where('isdefault',1)->find();
			if($bank) $bank['bankcardnum'] = substr($bank['bankcardnum'],0,3).'******'.substr($bank['bankcardnum'],-4);
			$rdata['bank'] = $bank;
		}
        $set['wx_max_money'] = 2000;//微信暂时定义的提现金额大于2000需要填写姓名
		$rdata['selectbank'] =  $selectbank;	
		$rdata['userinfo'] =  $userinfo ;
		$rdata['sysset'] = $set;
		$rdata['tmplids'] = $tmplids;

		return $this->json($rdata);
	}
	public function getBusinesscommission(){
	    if(getcustom('touzi_fenhong')){
            $bid = input('param.bid/d');
            if(!$bid){
                $bid = 0;
            }
            
            $rs = \app\common\Fenhong::touzi_fenhong(aid,$this->sysset,[],0,time(),1,mid,$bid);
            $newoglist = $rs['oglist'];
            $commissionyj = $rs['commissionyj'];
          
            //已结算
            $where = [];
            $where[] = ['mf.aid','=',aid];
            $where[] = ['mf.mid','=',mid];
            $where[] = ['mf.type','=','touzi_fenhong'];
            $where[] = ['mf.status','=',1];
            $where[] = ['og.bid','=',$bid];
            $pernum = 20;
            $pagenum = input('param.pagenum');
            if(!$pagenum) $pagenum = 1;
            $commission = Db::name('member_fenhonglog')->alias('mf')
                ->join('shop_order_goods og','og.id = mf.ogids')
                ->where($where)->page($pagenum,$pernum)
                ->sum('mf.commission');
            $touzimoney = 0+ Db::name('shareholder')->where('aid',aid)->where('bid',$bid)->where('status',1)->where('mid',mid)-> sum('money');
            $rdata = [];
            $rdata['userinfo']['commissionyj'] = round($commissionyj,2);
            $rdata['userinfo']['commission'] = round($commission,2);
            $rdata['userinfo']['touzimoney'] =$touzimoney;
            return $this->json($rdata);
        }
    }
    //扶持基金明细
    public function fuchiLog()
    {
        if(getcustom('commission_frozen')){
            $st = input('param.st');
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            if(input('param.keyword')) $where[] = ['remark', 'like', '%'.input('param.keyword').'%'];
            $pernum = 20;
            $pagenum = input('post.pagenum');
            if(!$pagenum) $pagenum = 1;
            if($st ==1){//提现记录
//            $datalist = Db::name('member_commission_withdrawlog')->field("id,money,txmoney,`status`,from_unixtime(createtime)createtime")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            }else{ //佣金明细
                $datalist = Db::name('member_fuchi_log')->field("id,commission money,`after`,from_unixtime(createtime)createtime,remark")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            }
            if(!$datalist) $datalist = [];
            return $this->json(['status'=>1,'data'=>$datalist]);
        }
    }
    //我的团队
	public function team(){
	    $mid = input('mid')?input('mid/d'):0;
	    if(!$mid){
	        $mid = mid;
        }
	    $date_start = 0;
        $date_end = 0;
	    if(input('date_start') && input('date_end')){
            $date_start = strtotime(input('date_start'));
            $date_end = strtotime(input('date_end'));
        }
	    $checkLevelid = input('checkLevelid')?input('checkLevelid/d'):0;

//		$admin_set = Db::name('admin_set')->where('aid',aid)->find();
		$field = 'id,nickname,headimg,levelid,last_visittime';
		if(getcustom('team_minyeji_count')){
            $field .= ',team_minyeji';
        }
		$userinfo = Db::name('member')->field($field)->where('aid',aid)->where('id',$mid)->find();
		$userlevel = Db::name('member_level')->where('aid',aid)->where('id',$userinfo['levelid'])->find();

		$downdeep = input('param.st/d');
		$pernum = 20;
		$pagenum = input('post.pagenum');
		$keyword = input('post.keyword');
        $order = "id desc";
        if(getcustom('team_show_visittime')){
            $order = "last_visittime desc,id desc";
        }

		$where2 = "1=1";
		$query_params = [];//query查询条件
		if($keyword){
            $where2 = "(nickname like :keyword or realname like :keyword2 or tel like :keyword3 or id like :keyword4 )";
            $query_params['keyword'] = $query_params['keyword2'] = $query_params['keyword3']= $query_params['keyword4'] = '%'.$keyword.'%';  
        } 
		if($date_start && $date_end){
		    $where_date = "createtime>=:date_start and createtime<=:date_end";
            $query_params['date_start'] = $date_start;
            $query_params['date_end']   = $date_end;
            if($where2=='1=1'){
                $where2 = $where_date;
            }else{
                $where2 = $where2.' and '.$where_date;
            }
        }
		if($checkLevelid){
            $where_level = 'levelid='.$checkLevelid;
            if($where2=='1=1'){
                $where2 = $where_level;
            }else{
                $where2 = $where2.' and '.$where_level;
            }
        }
		if(!$pagenum) $pagenum = 1;
		if(!$downdeep) $downdeep = 1;
		if(!$mid){
			$datalist = [];
		}else{
            $field = 'id,nickname,headimg,tel,pid,score,totalcommission,from_unixtime(createtime)createtime,levelid,last_visittime';
			if(getcustom('register_fields')){
                $field .= ',form_record_id';
            }
            if(getcustom('change_down_user')){
                $field .= ',change_pid_time';
            }

            if(getcustom('team_list_level_search') && $userlevel['team_show_level_tab'] == 0) {
                $downdeep = '';
                $datalist = Db::name('member')->field($field)->where("aid",aid)->where("find_in_set('".$mid."',path)")->whereRaw($where2,$query_params)->page($pagenum,$pernum)->order($order)->select()->toArray();
            }

            if($downdeep == 1){
				$datalist = Db::name('member')->field($field)->where("aid",aid)->where("pid",$mid)->whereRaw($where2,$query_params)->page($pagenum,$pernum)->order($order)->select()->toArray();
			}elseif($downdeep == 2){
                $query_params['pid'] = $mid;
				$datalist = Db::query("select $field from ".table_name('member')." where aid=".aid." and $where2 and pid in(select id from ".table_name('member')." where pid= :pid) order by {$order} limit ".($pagenum*$pernum-$pernum).','.$pernum, $query_params);
			}elseif($downdeep == 3){
                $query_params['pid'] = $mid;
				$datalist = Db::query("select $field from ".table_name('member')." where aid=".aid." and pid in(select id from ".table_name('member')." where aid=".aid." and $where2 and pid in(select id from ".table_name('member')." where pid=:pid)) order by {$order} limit ".($pagenum*$pernum-$pernum).','.$pernum, $query_params);
			}elseif($downdeep == 4){
			    //团队
                $downmids =  \app\common\Member::getdowntotalmids(aid,$mid,$userlevel['can_agent']);
                if($downmids){
                    $downmids = implode(',',$downmids);
                    $datalist = Db::query("select $field from ".table_name('member')." where aid=".aid." and $where2 and id in(".$downmids.") order by {$order} limit ".($pagenum*$pernum-$pernum).','.$pernum, $query_params);
                }
			}
		}
		if(!$datalist) $datalist = [];
        if(getcustom('register_fields_extend')) {
            $registerForm = Db::name('register_form')->field('content,savetype')->where('aid', aid)->find();
        }
        foreach($datalist as $k=>$v){
			//if($downdeep==1){
			//	$commission = Db::name('shop_order_goods')->where('aid',aid)->where('mid',$v['id'])->where('parent1',mid)->where('status',3)->sum('parent1commission');
			//}
			//if($downdeep==2){
			//	$commission = Db::name('shop_order_goods')->where('aid',aid)->where('mid',$v['id'])->where('parent2',mid)->where('status',3)->sum('parent2commission');
			//}
			//if($downdeep==3){
			//	$commission = Db::name('shop_order_goods')->where('aid',aid)->where('mid',$v['id'])->where('parent3',mid)->where('status',3)->sum('parent3commission');
			//}

            $commission_where = [];
            $commission_where[] = ['aid','=',aid];
            $commission_where[] = ['mid','=',$mid];
            $commission_where[] = ['frommid','=',$v['id']];
            $commission_where[] = ['status','=',1];
            if($date_start && $date_end){
                $commission_where[] = ['createtime','between',[$date_start,$date_end]];
            }
			$commission = Db::name('member_commission_record')
                ->where($commission_where)
                ->sum('commission');

			$datalist[$k]['commission'] = 0 + dd_money_format($commission,2);

            //一级直推人数
            if(getcustom('team_view_zhitui_member_num')){
                $team_view_zhitui_member_num = Db::name('member')->where('aid',aid)->where('pid',$v['id'])->count();
            }
            $datalist[$k]['team_view_zhitui_member_num'] = $team_view_zhitui_member_num ?? 0;

			$downcount_where = [];
            $downcount_where[] = ['aid','=',aid];
            $downcount_where[] = ['pid','=',$v['id']];
            if($date_start && $date_end){
                $downcount_where[] = ['createtime','between',[$date_start,$date_end]];
            }
			$datalist[$k]['downcount'] = 0 + Db::name('member')->where($downcount_where)->count();

			$level = Db::name('member_level')->where('aid',aid)->where('id',$v['levelid'])->find();
            $datalist[$k]['levelname'] = $level['name'];
            $datalist[$k]['levelsort'] = $level['sort'];
            $datalist[$k]['last_visittime'] = $v['last_visittime']?date('Y-m-d H:i:s',$v['last_visittime']):'';
			if($userlevel['team_showtel'] == 0){
				$datalist[$k]['tel'] = '';
			}
            if(getcustom('team_tel_hide_middle_four') && $userlevel['team_tel_hide_middle_four'] == 1 && !empty($datalist[$k]['tel'])){
                //隐藏会员电话号码中间4位
                $datalist[$k]['tel'] = hidePhoneNumber($datalist[$k]['tel']);
            }
            if(getcustom('team_auth')){
                //团队业绩
                $datalist[$k]['team_down_total'] = '计算中';
                $datalist[$k]['teamyeji'] = '计算中';
                $datalist[$k]['selfyeji'] = '计算中';
            }
			if(getcustom('yx_gift_pack')){
				$packcount = 0+Db::name('gift_pack_order')->where('aid',aid)->where('mid',$v['id'])->count();
				$datalist[$k]['packcount'] = $packcount;
				$totalpackmoney = 0+Db::name('gift_pack_order')->where('aid',aid)->where('mid',$v['id'])->sum('sell_price');
				$datalist[$k]['totalpackmoney'] = $totalpackmoney;
			}
            if(getcustom('coupon_xianxia_buy')){
                $xianxia_sales =0+ Db::name('coupon_record')->where('aid',aid)->where('from_mid',$v['id'])->where('is_xianxia_buy',1)->count();
                $datalist[$k]['xianxia_sales'] = $xianxia_sales;
            }
            if(getcustom('register_fields_extend')){
                $record = Db::name('register_form_record')->where('id',$v['form_record_id'])->find();
                if($record){
                    if($registerForm && $registerForm['savetype'] == 2){
                        $content = json_decode($registerForm['content'],true);
                    }else{
                        $content = json_decode($record['content'],true);
                    }

                    $recordData = [];
                    foreach ($content as $k1 => $v1){
                        if($v1['val7'] == 1){
                            $val = $record['form'.$k1];
                            if($val == null){
                                $val = '';
                            }elseif($v1['key'] == 'sex'){
                                $val = $val == 1 ? '男' : '女';
                            }
                            $recordData[] = [
                                'key'   => $v1['key'],
                                'field' => $v1['val1'],
                                'value' => $val
                            ];
                        }
                    }
                    $datalist[$k]['custom_field'] = $recordData;
                }
            }
            $datalist[$k]['can_change_pid'] = 0;
            if(getcustom('change_down_user')){
                //是否可以链动换位
                if($userlevel['change_down_user']==1 && $v['change_pid_time']==0){
                    $datalist[$k]['can_change_pid'] = true;
                }
            }
            if(getcustom('team_show_down_order') ){
                if(input("post.first_mid") || $userlevel['team_show_down_order'] == 1){
                    $datalist[$k]['team_order_count'] = '计算中';
                    $datalist[$k]['team_order_money'] = '计算中';
                }
            }
        }
        $month_yeji_show = false;
        if(getcustom('yx_team_yeji')){
            $month_yeji_show = true;
        }
        $userinfo['month_yeji_show'] = $month_yeji_show;
        $team_show_down_order = 0;
        if(getcustom('team_show_down_order') ){
            $first_mid = input('post.first_mid',0);
            if($first_mid){
                $userlevel['team_show_down_order'] = 1;
            }
            if($userlevel['team_show_down_order'] == 1){
                $userinfo['team_order_count'] = '计算中';
                $userinfo['team_order_money'] = '计算中';
                $userinfo['team_refund_order'] = '计算中';
                $team_show_down_order = $userlevel['team_show_down_order'];
            }
        }
        $userlevel['team_show_down_order'] = $team_show_down_order;
		//距离下个等级
        $userinfo['next_ordermoney_show'] = false;
        if(getcustom('team_next_up_ordermoney')){
            $userinfo['next_ordermoney_show'] = true;
        }
		if($pagenum == 1){
			//我的团队
            $team_where = [];
            $team_where[] = ['aid','=',aid];
            $team_where[] = ['pid','=',$mid];
            if($date_start && $date_end){
                $team_where[] = ['createtime','between',[$date_start,$date_end]];
            }
			$userinfo['myteamCount1'] = 0 + Db::name('member')->where($team_where)->count();
            $team_where = '1=1';
            $query_params2 = [];
            if($date_start && $date_end){
                $team_where = 'createtime>=:date_start and '.'createtime<=:date_end';
                $query_params2['date_start'] = $date_start;
                $query_params2['date_end']   = $date_end;
            }
			$myteamCount2 = Db::query("select count(1)c from ".table_name('member')." where aid=".aid." and pid in(select id from ".table_name('member')." where pid=".$mid.") and ".$team_where,$query_params2);
			$userinfo['myteamCount2'] = 0 + $myteamCount2[0]['c'];
			$myteamCount3 = Db::query("select count(1)c from ".table_name('member')." where aid=".aid." and pid in(select id from ".table_name('member')." where aid=".aid." and pid in(select id from ".table_name('member')." where pid=".$mid.")) and ".$team_where,$query_params2);
			$userinfo['myteamCount3'] = 0 + $myteamCount3[0]['c'];
			$userinfo['myteamCount'] = $userinfo['myteamCount1'] + $userinfo['myteamCount2'];
			//三级以后的团队人数
            $where_count = '1=1';
            if($date_start && $date_end){
                $where_count = 'createtime between '.$date_start.' and '.$date_end;
            }
            $downmids =  \app\common\Member::getdowntotalmids(aid,$mid,$userlevel['can_agent'],'','',1,$where_count);
            $userinfo['myteamCount4'] = count($downmids);

			//分销订单
			$userinfo['fxorderCount'] = 0 + Db::name("shop_order_goods")->whereRaw("aid=".aid." and (parent1=".$mid." or parent2=".$mid." or parent3=".$mid.") and ".$team_where,$query_params2)->count();
            $levelList = Db::name('member_level')->field('id,sort,name')->where('aid',aid)->where('sort','<',$userlevel['sort'])->order('sort', 'asc')->select();
            //$levelList = Db::name('member_level')->field('id,sort,name')->where('aid',aid)->where('id', 'in', explode(',',$userlevel['team_levelup_id']))->order('sort', 'asc')->select();
            $newKey = 'id';
            $levelList = $levelList->dictionary(null, $newKey);
		}
        //小部门业绩[只统计确认收货且减掉退款的]
        $userinfo['miniyeji_show'] = false;
        $userinfo['maxyeji_show'] = false;
        if (getcustom('member_level_salary_bonus')) {
            $userinfo['miniyeji_show'] = true;
        }
        $custom = ['_t'=>time()];//默认
        if(getcustom('team_show_visittime')){
            $custom['team_show_visittime'] = true;
        }
        if(getcustom('team_member_history')){
            $custom['team_member_history'] = true;
        }
		if(getcustom('yx_gift_pack')){
            $custom['yx_gift_pack'] = true;
        }
        if(getcustom('yeji_with_pronum')){
            $custom['yeji_with_pronum'] = true;
        }
        if(getcustom('team_minyeji_count')){
            $userinfo['miniyeji_show'] = true;
            $userinfo['team_miniyeji_total'] = $userinfo['team_minyeji']?:0;

            $userinfo['maxyeji_show'] = true;
            $userinfo['team_maxyeji_total'] = 0;
        }


		if($pagenum==1){
            $userinfo['team_yeji_total'] = 0;
            $userinfo['team_miniyeji_total'] = 0;
            $userinfo['next_up_ordermoney'] = 0;
            $userinfo['now_month_yeji'] = 0;
            $version = input('version');
            if(!$version || $version!='2.6.3'){
                $yeji_data = $this->get_team_yeji(1);
                $userinfo['team_yeji_total'] = $yeji_data['team_yeji_total']??0;
                $userinfo['team_miniyeji_total'] = $yeji_data['team_miniyeji_total']??0;
                $userinfo['next_up_ordermoney'] = $yeji_data['next_up_ordermoney']??0;
                $userinfo['now_month_yeji'] = $yeji_data['now_month_yeji']??0;
                if(getcustom('team_minyeji_count')){
                    $userinfo['miniyeji_show'] = true;
                    $userinfo['maxyeji_show'] = true;
                    $userinfo['team_maxyeji_total'] = $yeji_data['team_maxyeji_total'];
                }
            }

            if(getcustom('team_yeji_current_month')){
                //当月的团队业绩
                if($userlevel['team_yeji_current_month'] == 1){
                    $month_start = date('Y-m-01 00:00:00');
                    $month_end  = date('Y-m-t 23:59:59');
                    $team_yeji_current_month_cengji = $userlevel['team_yeji_current_month_cengji'];
                    if($team_yeji_current_month_cengji <= 0){
                        $team_yeji_current_month_cengji = 999;
                    }
                    $current_month_yeji_data = $this->get_team_yeji(1,$team_yeji_current_month_cengji,$month_start,$month_end);
                    $userinfo['team_yeji_current_month'] = $current_month_yeji_data['team_yeji_total']??0;
                }
            }
        }
		$rdata = [];
		$rdata['datalist'] = $datalist;
		$rdata['userinfo'] = $userinfo;
		$rdata['custom'] = $custom;

		if(getcustom('member_levelup_givechild')){
            
            if($userlevel['team_levelup'] == 1){
                $usenum = Db::name('member_levelup_uesnum')->where('aid',aid)->where('mid',mid)->find();
                if(!$usenum){
                    \app\common\Member::addMemberLevelupNum(aid,mid,$userlevel['team_levelup_data']);
                }else{
                    if($usenum['levelid'] !=$userlevel['id']){
                        $olduserlevel = Db::name('member_level')->where('aid',aid)->where('id',$usenum['levelid'])->find();
                        if(!$olduserlevel || $olduserlevel['sort'] < $userlevel['sort']){
                            \app\common\Member::addMemberLevelupNum(aid,mid,$userlevel['team_levelup_data']);
                        }                        
                    }
                }
            }
        }
		if(getcustom('yx_shortvideo_jindubag')){
        	if($userlevel && $userlevel['team_showtel']){
        		$userlevel['team_callphone'] = 1;
        	}
        }
        //会员等级直推人数
        if(getcustom('member_level_zhitui_number_limit')){
            //0 无限制
            if($userlevel['zt_member_limit'] == 0){
                $userlevel['zt_member_limit'] = '无限制';
            }
        }
        //显示直推、间推业绩
        $userlevel['team_yeji_zhitui'] = $userlevel['team_yeji_zhitui']??0;
        $userlevel['team_yeji_jiantui'] = $userlevel['team_yeji_jiantui']??0;

		$rdata['userlevel'] = $userlevel;
		$rdata['levelList'] = $levelList;
		$rdata['st'] = $downdeep;
        $rdata['team_auth'] = getcustom('team_auth')||getcustom('member_levelup_givechild')?1:0;
        if(getcustom('team_auth') || getcustom('team_list_level_search')){
            //所有级别
            $all_level = Db::name('member_level')->field('id,sort,name')->where('aid',aid)->select()->toArray();
            $rdata['all_level'] = $all_level;
        }
        $month_item = []; 
        if(getcustom('yx_team_yeji')){
            //今年的月份
            for($i=1;$i<= date('m');$i++){
                $month_item[] = $i.'月';
            }
        }
        $rdata['month_item'] = $month_item;
        $levelup_uesnum = [];
        if(getcustom('member_levelup_givechild')){
            $levelup_uesnum = Db::name('member_levelup_uesnum')->alias('ue')
                ->join('member_level ml','ml.id = ue.levelupid')
                ->field('ue.*,ml.name')
                ->where('ue.aid',aid)
                ->where('ue.mid',mid)
                ->select()->toArray();
        }
        $rdata['levelup_uesnum']= $levelup_uesnum;

        $rdata['showlevel'] = true;	
        $rdata['level_tab'] = true; 
        if(getcustom('team_list_level_search')) {
        	$rdata['showlevel'] = false;		
            if($userlevel['team_show_level_tab'] == 0){
                $rdata['level_tab'] = false;
            }						
        }

        $rdata['show_business'] = 0;
        if(getcustom('team_auth_tjbusiness') && getcustom('business_agent')){
            //显示团队推荐商家
            $rdata['show_business'] = 1;
            $tjbusinessCount = \app\custom\MemberCustom::gettjbusinessCount(aid,$mid);
            $rdata['tjbusinessCount'] = $tjbusinessCount?:0;
        }
		return $this->json($rdata);
	}
	//用于前端异步请求业绩数据
    public function get_team_yeji($is_sync=0,$deep=999,$date_start='',$date_end=''){
        $mid = input('mid')?input('mid/d'):0;
        if(!$mid){
            $mid = mid;
        }

        if($date_start && $date_end){
            $date_start = strtotime($date_start);
            $date_end = strtotime($date_end);
        }else{
            $date_start = 0;
            $date_end = 0;
            if(input('date_start') && input('date_end')){
                $date_start = strtotime(input('date_start'));
                $date_end = strtotime(input('date_end'));
            }
        }

        $field = 'id,nickname,headimg,levelid,last_visittime';
        if(getcustom('team_minyeji_count')){
            $field .= ',team_minyeji';
        }
        if(getcustom('yeji_self_manually_product')){
            $field .= ',yeji_self_manually_product';
        }
        $userinfo = Db::name('member')->field($field)->where('aid',aid)->where('id',$mid)->find();
        $userlevel = Db::name('member_level')->where('aid',aid)->where('id',$userinfo['levelid'])->find();
        $admin_set = Db::name('admin_set')->where('aid',aid)->find();
        $data = [];
        //团队业绩 team_yeji_total
        $team_yeji_total = 0;

        $teamyeji_hide_pingji = 0;
        if(getcustom('teamyeji_hide_pingji')){
            //是否隐藏平级业绩
            $teamyeji_hide_pingji = cache('teamyeji_hide_pingji_'.aid)?:0;
        }

        if(getcustom('team_yeji_current_month')){
            $team_yeji_current_month_cengji = $userlevel['team_yeji_current_month_cengji'];
            if($userlevel['team_yeji_current_month'] == 1 && $team_yeji_current_month_cengji > 0){
                $deep = $team_yeji_current_month_cengji;
            }
        }

        if($teamyeji_hide_pingji==1){
            //去除平级之后的团队
            $downmids = \app\common\Member::getteammidsByStoplevelid(aid,$mid,$deep,[],$userinfo['levelid']);
        }else{
            $downmids = \app\common\Member::getteammids(aid,$mid,$deep);
        }
        //业绩包含自身
        $is_include_self = 0;
        //两个定制冲突都是控制一项内容
        if(getcustom('yx_team_yeji_include_self')){
            if($admin_set['teamyeji_include_self']) $is_include_self = 1;
        }
        if(getcustom('teamyeji_show') && $admin_set['teamyeji_self']==1){
            $is_include_self = 1;
        }
        if($is_include_self){
            //包含自己 系统设置中
            $downmids[] = $mid;
        }
        if($downmids){
            $yejiwherewm = $yejiwhere = [];
            $yejiwhere[] = ['status','in','1,2,3'];
            $yejiwherewm[] = ['status','in','1,2,3,12'];
            $yejiwherewm[] = $yejiwhere[] = ['mid','in',$downmids];
            if(getcustom('yx_team_yeji_jicha')){
                $levelup_time = 0;
                $yeji_set = Db::name('team_yeji_set')->where('aid',aid)->find();
                if($yeji_set['status'] ==1 && $yeji_set['is_jicha'] ==1) {
                    $yejiconfig = json_decode($yeji_set['config_data'], true);
                    $show_levelid = array_keys($yejiconfig);
                    if (in_array($this->member['levelid'], $show_levelid)) {
                        $levelup_order = Db::name('member_levelup_order')
                            ->where('aid', aid)
                            ->where('mid', $mid)
                            ->where('levelid', $this->member['levelid'])
                            ->where('status', 2)
                            ->order('levelup_time desc')
                            ->find();
                        $levelup_time = $levelup_order['levelup_time'];
                    }
                }
            }
            if($date_start && $date_end){
                if(getcustom('yx_team_yeji_jicha')){
                    if($yeji_set['status'] ==1 && $yeji_set['is_jicha'] ==1){
                        if (!$levelup_time) $date_start = 0;  $date_end = 0;
                    }
                }
                $yejiwherewm[] = $yejiwhere[] = ['createtime','between',[$date_start,$date_end]];
            }else{
                if(getcustom('yx_team_yeji_jicha')){
                    if($yeji_set['status'] ==1 && $yeji_set['is_jicha'] ==1){
                        $nowtime = time();
                        if(!$levelup_time)$nowtime = 0;
                        $yejiwherewm[] = $yejiwhere[] = ['createtime','between',[$levelup_time,$nowtime]];
                    }
                }
            }

            if(getcustom('product_yeji_level')){
            	$team_yeji_total = Db::name('shop_order_goods')->where('aid',aid)->where($yejiwhere)->sum('yeji')?:0;
            }else{
            	$team_yeji_total = Db::name('shop_order_goods')->where('aid',aid)->where($yejiwhere)->sum('real_totalprice')?:0;
            }
            
            // 餐饮订单计入团队业绩，参与升级条件统计
            if(getcustom('restaurant_team_yeji')){
                $restaurant_team_yeji_open = Db::name('admin_set')->where('aid',aid)->value('restaurant_team_yeji_open');
                if($restaurant_team_yeji_open){
                    // 外卖
                    $rtakeaway_fxordermoney = Db::name('restaurant_takeaway_order_goods')->where('aid',aid)->where($yejiwherewm)->sum('totalprice')?:0;
                    $team_yeji_total += $rtakeaway_fxordermoney;
                    // 店内点餐
                    $rshop_fxordermoney = Db::name('restaurant_shop_order_goods')->where('aid',aid)->where($yejiwhere)->sum('totalprice')?:0;
                    $team_yeji_total += $rshop_fxordermoney;
                }
            }
            $include_maidan_yeji = 0;
            if(getcustom('yx_team_yeji_maidan')){
                $include_maidan_yeji = Db::name('team_yeji_set')->where('aid',aid)->value('include_maidan_yeji');//团队业绩奖-业绩将设置
            }
            if(getcustom('teamyeji_include_maidan')){
                //团队业绩是否包含买单
                $include_maidan_yeji = $admin_set['teamyeji_include_maidan'];
            }
            if($include_maidan_yeji) {
                $maidan_where = [];
                $maidan_where[] = ['aid', '=', aid];
                $maidan_where[] = ['status', '=', 1];
                $maidan_where[] = ['mid', 'in', $downmids];
                if ($date_start && $date_end) {
                    $maidan_where[] = ['createtime', 'between', [$date_start, $date_end]];
                }
                $maidan_yeji = 0 + Db::name('maidan_order')->where($maidan_where)->sum('paymoney');
                $team_yeji_total += $maidan_yeji;
            }
            if(getcustom('yx_team_yeji_cashdesk')){
                $include_cashdesk_yeji = Db::name('admin_set')->where('aid',aid)->value('include_cashdesk_yeji');//团队业绩奖-业绩将设置
                if($include_cashdesk_yeji) {
                    $cashdesk_where = [];
                    $cashdesk_where[] = ['aid', '=', aid];
                    $cashdesk_where[] = ['status', '=', 1];
                    $cashdesk_where[] = ['mid', 'in', $downmids];
                    if ($date_start && $date_end) {
                        $cashdesk_where[] = ['createtime', 'between', [$date_start, $date_end]];
                    }
                    $cashdesk_yeji = 0 + Db::name('cashier_order')->where($cashdesk_where)->sum('totalprice');
                    $team_yeji_total += $cashdesk_yeji;
                }
            }
            if(getcustom('scoreshop_teamyeji')){
                //是否统计团队业绩
                $jointeamyeji = Db::name('scoreshop_sysset')->where('aid',aid)->value('jointeamyeji');
                if($jointeamyeji && $jointeamyeji == 1){
                    $scoreshop_where = [];
                    $scoreshop_where[] = ['aid', '=', aid];
                    $scoreshop_where[] = ['mid', 'in', $downmids];
                    $scoreshop_where[] = ['status','in','1,2,3'];
                    if ($date_start && $date_end) {
                        $scoreshop_where[] = ['createtime', 'between', [$date_start, $date_end]];
                    }
                    $scoreshop_teamyeji = Db::name('scoreshop_order_goods')->where($scoreshop_where)->sum('totalmoney');
                    $team_yeji_total += $scoreshop_teamyeji;
                }
            }
        }
        $downmids = \app\common\Member::getteammids(aid,$mid);
        $data['team_down_total'] = count($downmids);
        $data['team_yeji_total'] = dd_money_format($team_yeji_total);
        //小市场业绩 team_miniyeji_total
        if (getcustom('member_level_salary_bonus')) {
            $data['team_miniyeji_total'] = \app\model\Commission::getMiniTeamCommission(aid,$mid,$date_start,$date_end);
        }
        if (getcustom('team_minyeji_count')) {
            //统计小市场业绩，东营中讯定制(统计已支付订单的实际支付金额real_totalprice)
            $team_yeji_arr = \app\model\Commission::getTeamYeji(aid,$mid,$date_start,$date_end);
            $data['team_miniyeji_total'] = $team_yeji_arr['min_yeji'];
            $data['team_maxyeji_total'] = $team_yeji_arr['max_yeji'];
            $data['team_yeji_total'] = $team_yeji_arr['total_yeji'];
        }
        //距升级{{t('团队业绩')}} next_up_ordermoney
        if(getcustom('team_next_up_ordermoney')){
            $nextlevel = Db::name('member_level')->where('aid',aid)->where('sort','>',$userlevel['sort'])->order('sort,id')->find();
            $next_up_ordermoney = 0;
            if($nextlevel['can_up'] ==1){
                $up_fxordermoney = $nextlevel['up_fxordermoney'];
                $next_up_ordermoney = dd_money_format( $up_fxordermoney - $data['team_yeji_total'] );
            }
            $data['next_up_ordermoney'] = $next_up_ordermoney <0?0:$next_up_ordermoney;//距离升级的业绩
        }
        if(getcustom('yeji_with_pronum')){
            //后台会员列表页面、前端我的团队页面 显示指定商品的数量个人和团队，不显示金额
            $yejiwhere = [];
            if($userlevel['up_selfanddown_order_product_num_proids']){
                $yejiproids = explode(',',$userlevel['up_selfanddown_order_product_num_proids']);
                $yejiwhere[] = ['proid','in',$yejiproids];
            }

            $yejiwhere[] = ['status','in','1,2,3'];
            $data['yeji_pronum'] = Db::name('shop_order_goods')->where('aid',aid)->where($yejiwhere)->where('mid',mid)->sum('num')?:0;

            if(getcustom('yeji_self_manually_product')){
                //手动增加的个人业绩(双)
                $data['yeji_pronum'] = $data['yeji_pronum'] + $userinfo['yeji_self_manually_product'];
            }

            if($downmids){
                $yejiwhere[] = ['mid','in',$downmids];
                $data['team_yeji_pronum'] = Db::name('shop_order_goods')->where('aid',aid)->where($yejiwhere)->sum('num')?:0;
            }else{
                $data['team_yeji_pronum'] = 0;
            }
        }
        //当月团队总业绩 now_month_yeji
        $yx_team_yeji_maidan = getcustom('yx_team_yeji_maidan');
        $yx_team_yeji_cashdesk = getcustom('yx_team_yeji_cashdesk');
        if(getcustom('yx_team_yeji')){
            $yeji_set = Db::name('team_yeji_set')->where('aid',aid)->find();

            //当月 (业绩 + 虚拟业绩)
            if(input('month_search')){
                $month = rtrim(input('month_search'),'月');
                $month = $month<10?'0'.$month:$month;
                $month_start = strtotime(date('Y-'.$month.'-01 00:00:00'));
                $month_end  = strtotime(date('Y-m-t 23:59:59',$month_start));
                $now_month = date('Y-'.$month);
            }else{
                $month_start = strtotime(date('Y-m-01 00:00:00'));
                $month_end  = strtotime(date('Y-m-t 23:59:59'));
                $now_month = date('Y-m');
            }
            $yejiwhere = [];
            if(getcustom('yx_team_yeji_jicha')){
                if($yeji_set['status'] ==1 && $yeji_set['is_jicha'] ==1){
                    if(!$levelup_time){
                        $month_start = 0;  $month_end = 0;
                    }else{
                        if($levelup_time > $month_start ){
                            $month_start =  $levelup_time;
                        }
                    }
                }
            }
            $yejiwhere[] = ['createtime','between',[$month_start,$month_end]];
            $yejiwhere[] = ['status','in','1,2,3'];

            $shopwhere = '1=1';
            if(getcustom('yx_team_yeji_type')){
                //是否有商品限制
                if($yeji_set['fwtype']==1){
                    if(empty($yeji_set['productids'])){
                        $shopwhere = 'id = 0';
                    }else{
                        $shopwhere = 'proid in ('.$yeji_set['productids'].')';
                    }
                }
            }

            $downmids = \app\common\Member::getteammids(aid,$mid);
            if($is_include_self){
                //包含自己 系统设置中
                $downmids[] = $mid;
            }
            //业绩类型 0 按价格（默认） 1:按数量
            if(!$yeji_set['yeji_type']){
                if(getcustom('product_yeji_level')){
                    $month_teamyeji =0+ Db::name('shop_order_goods')->where('aid',aid)->where('mid','in',$downmids)->where($yejiwhere)->where($shopwhere)->sum('yeji');//totalprice real_totalprice
                }else{
                    $month_teamyeji =0+ Db::name('shop_order_goods')->where('aid',aid)->where('mid','in',$downmids)->where($yejiwhere)->where($shopwhere)->sum('real_totalprice');
                }

                $xuni_yeji =  0 +Db::name('tem_yeji_xuni')->where('aid',aid)->where('mid',$mid)->where('yeji_month',$now_month)->value('yeji');
                $now_month_yeji = dd_money_format($xuni_yeji + $month_teamyeji);

                //是否有商品限制 0：没有 1：有
                if(!$yeji_set['fwtype']){
                    if($yx_team_yeji_maidan){
                        $include_maidan_yeji = Db::name('team_yeji_set')->where('aid',aid)->value('include_maidan_yeji');
                        if($include_maidan_yeji){
                            $maidan_yeji =0+ Db::name('maidan_order')->where('aid',aid)->where('status',1)->where('mid','in',$downmids)->where('createtime','between',[$month_start,$month_end])->sum('paymoney');
                            $now_month_yeji += $maidan_yeji;
                        }
                       
                    }
                    if($yx_team_yeji_cashdesk){
                        $include_cashdesk_yeji = Db::name('admin_set')->where('aid',aid)->value('include_cashdesk_yeji');
                        if($include_cashdesk_yeji){
                            $cashdesk_yeji =0+ Db::name('cashier_order')->where('aid',aid)->where('status',1)->where('mid','in',$downmids)->where('createtime','between',[$month_start,$month_end])->sum('totalprice');
                            $now_month_yeji += $cashdesk_yeji;
                        }
                    }
                    if(getcustom('scoreshop_teamyeji')){
                        //是否统计团队业绩
                        $jointeamyeji = Db::name('scoreshop_sysset')->where('aid',aid)->value('jointeamyeji');
                        if($jointeamyeji && $jointeamyeji == 1){
                            $scoreshop_month_teamyeji = Db::name('scoreshop_order_goods')->where('aid',aid)->where('mid','in',$downmids)->where('status','in','1,2,3')->where('createtime','between',[$month_start,$month_end])->sum('totalmoney');
                            $now_month_yeji += $scoreshop_month_teamyeji;
                        }
                    }
                }
            }else{
                // $month_teamyeji =0+ Db::name('shop_order_goods')->where('aid',aid)->where('mid','in',$downmids)->where($yejiwhere)->where($shopwhere)->sum('num');
                // $now_month_yeji = dd_money_format($month_teamyeji);
                $now_month_yeji = 0;//按数量的暂不显示此业绩参数
            }
            $data['now_month_yeji'] = dd_money_format($now_month_yeji);

        }
        //成交订单数、成交金额
        if(getcustom('team_show_down_order')){
            if(input('first_mid') || $userlevel['team_show_down_order'] == 1) {
                $sdate = input('sdate');
                $edate = input('edate');

                $dmids = $downmids;
                $dmids[] = $mid; //包含自己
                $owhere = [];
                $owhere[] = ['aid', '=', aid];
                $owhere[] = ['mid', 'in', $dmids];
                if ($sdate && $edate) {
                    $sdate = strtotime($sdate);
                    $edate = strtotime($edate . ' 23:59:59');
                    $owhere[] = ['createtime', 'between', [$sdate, $edate]];
                }

                if(getcustom('product_yeji_level')){
                	$data['team_order_money'] = Db::name('shop_order')->where($owhere)->where('status','in','1,2,3')->sum('yeji');
                }else{
                	$data['team_order_money'] = Db::name('shop_order')->where($owhere)->where('status','in','1,2,3')->sum('totalprice');
                }
                $data['team_order_money'] = dd_money_format($data['team_order_money']);
                $data['team_order_count'] = Db::name('shop_order')->where($owhere)->where('status','in','1,2,3')->count();
	            $data['team_refund_order'] = Db::name('shop_refund_order')->where($owhere)->where('status','in','1,2,4,41')->count();
            }
        }
        if($is_sync){
            return $data;
        }
        $team_yeji_zhitui = 0;
        $team_yeji_jiantui = 0;
        if(getcustom('team_view_zhitui_yeji')){
            if($userlevel['team_yeji_zhitui']){
                //直推业绩
                $yejiwhere = [];
                $yejiwhere[] = ['aid','=',aid];
                $yejiwhere[] = ['status','in','1,2,3'];
                if($date_start && $date_end){
                    $yejiwhere[] = ['createtime','between',[$date_start,$date_end]];
                }
                $zhitui_mids = Db::name('member')->where('pid',$mid)->column('id');
                if($zhitui_mids){
                    $yejiwhere[] = ['mid','in',$zhitui_mids];
                    $team_yeji_zhitui = Db::name('shop_order_goods')->where('aid',aid)->where($yejiwhere)->sum('real_totalprice')?:0;
                }
            }
            if($userlevel['team_yeji_jiantui'] && $zhitui_mids){
                //间推业绩
                $jiantui_mids = Db::name('member')->where('pid','in',$zhitui_mids)->column('id');
                $yejiwhere = [];
                $yejiwhere[] = ['aid','=',aid];
                $yejiwhere[] = ['status','in','1,2,3'];
                if($date_start && $date_end){
                    $yejiwhere[] = ['createtime','between',[$date_start,$date_end]];
                }
                if($jiantui_mids){
                    $yejiwhere[] = ['mid','in',$jiantui_mids];
                    $team_yeji_jiantui = Db::name('shop_order_goods')->where('aid',aid)->where($yejiwhere)->sum('real_totalprice')?:0;
                }
            }
        }
        $data['team_yeji_zhitui'] = $team_yeji_zhitui;
        $data['team_yeji_jiantui'] = $team_yeji_jiantui;
        $rdata = [
            'status' => 1,
            'data' => $data,
        ];
        return $this->json($rdata);
    }
    //用于前端异步请求团队会员的团队业绩
    public function get_member_yeji(){
	    $mid = input('mid');
        $date_start = 0;
        $date_end = 0;
        if(input('date_start') && input('date_end')){
            $date_start = strtotime(input('date_start'));
            $date_end = strtotime(input('date_end'));
        }
        $admin_set = Db::name('admin_set')->where('aid',aid)->find();
        $yejiwhere = [];
        $yejiwhere[] = ['status','in','1,2,3'];
        if($date_start && $date_end){
            $yejiwhere[] = ['createtime','between',[$date_start,$date_end]];
        }
        $downmids = \app\common\Member::getteammids(aid,$mid);
        $data = [];
        //下级人数
        $team_down_total = count($downmids);
        $data['team_down_total'] = $team_down_total;
        if(getcustom('product_yeji_level')){
            $yejiwhere[] = ['yeji','>',0];
        }
        if($downmids){
            $teamyeji = Db::name('shop_order_goods')->where('aid',aid)->where('mid','in',$downmids)->where($yejiwhere)->sum('real_totalprice');
        }
            //个人业绩
        $self_yeji = Db::name('shop_order_goods')->where('aid',aid)->where('mid',$mid)->where($yejiwhere)->sum('real_totalprice');
        //业绩包含自身
        if(getcustom('teamyeji_show') && $admin_set['teamyeji_self']==1){
            $teamyeji = bcadd($teamyeji,$self_yeji,2);
        }
        $data['teamyeji'] = $teamyeji?:0;
        $data['selfyeji'] = $self_yeji;
        if(getcustom('team_show_down_order')){
            $sdate = input('sdate');
            $edate = input('edate');

            $dmids = $downmids;
            $dmids[] = $mid; //包含自己
            $owhere = [];
            $owhere[] = ['aid','=',aid];
            $owhere[] = ['mid','in',$dmids];
            if($sdate && $edate){
                $sdate = strtotime($sdate);
                $edate = strtotime($edate.' 23:59:59');
                $owhere[] = ['createtime','between',[$sdate,$edate]];
            }
            if(getcustom('product_yeji_level')){
	            $data['team_order_money'] =  Db::name('shop_order')->where($owhere)->where('status','in','1,2,3')->sum('yeji');
            }else{
            	$data['team_order_money'] =  Db::name('shop_order')->where($owhere)->where('status','in','1,2,3')->sum('totalprice');
            }
        	$data['team_order_count'] = Db::name('shop_order')->where($owhere)->where('status','in','1,2,3')->count();
            $data['team_order_money'] = dd_money_format($data['team_order_money']);
            $data['team_refund_order'] = Db::name('shop_refund_order')->where($owhere)->where('status','in','1,2,4,41')->count();

        }
        $rdata = [
            'status' => 1,
            'data' => $data,
        ];
        return $this->json($rdata);
    }
    public function getTeamyejiPv(){
	    if(getcustom('teamyeji_pv')){
	        $date_start = input('param.date_start');
	        $date_end = input('param.date_end');
            $downmids = \app\common\Member::getdownmids(aid,mid);
            $pv_where = [];
            $pv_where[] = ['og.aid','=',aid];
            $pv_where[] = ['og.status','in','1,2,3'];
            $pv_where[] = ['og.mid','in',$downmids];
            if($date_start && $date_end){
                $pv_where[] = ['createtime','between',[strtotime($date_start),strtotime($date_end)]];
            }
            $teamyeji_og = Db::name('shop_order_goods')->alias('og')
                ->join('shop_guige g','g.id = og.ggid')
                ->where($pv_where)
                ->field('g.cost_price,g.market_price,og.num')
                ->select()->toArray();
            $pv = 0;
            foreach($teamyeji_og as $tk=>$tv){
                $pvv =   ($tv['market_price'] - $tv['cost_price'])*$tv['num'] ;
                $pv+=$pvv;
            }
            //我的业绩pv
            $teamyeji_og = Db::name('shop_order_goods')->alias('og')
                ->join('shop_guige g','g.id = og.ggid')
                ->field('g.cost_price,g.market_price,og.num')
                ->where('og.aid',aid)
                ->where('og.status','in','1,2,3')
                ->where('og.mid','=',mid)
                ->select()->toArray();
            $mypv = 0;
            foreach($teamyeji_og as $mtk=>$mtv){
                $mypvv =   ($mtv['market_price'] - $mtv['cost_price'])*$mtv['num'] ;
                $mypv+=$mypvv;
            }
            $data['teamyeji_pv'] = dd_money_format($pv);
            $data['my_teamyeji_pv'] = dd_money_format($mypv);
            return $this->json(['status' => 1,'data' => $data]);
        }
    }
	//分销订单
	public function agorder(){
		//拼团的佣金订单
		$pernum = 20;
		$pagenum = input('param.pagenum') ? input('param.pagenum/d') :1;
		$where = '';
		$st = input('param.st/d');
		if(!$st) $st = 0;
		if($st==1){//待付款
			$where = " and o.status=0";
		}
		if($st==2){//已付款
			$where = " and (o.status=1 or o.status=2)";
		}
		if($st==3){//已完成
			$where = " and o.status=3";
		}
		if($st==5){//售后
			$where = " and o.refund_money>0";
		}
		$module = input('param.module');
		if($module =='cashdesk'){
            if(!$st) $st = 0;
            
		    $where = [];
		    if($st ==0){
                $where[] = Db::raw('o.status =1 or o.refund_money > 0');
            }
            //已付款
            if($st==2)$where[] = ['o.status','=',1];
            //售后
            if($st==5)$where[] = ['o.refund_money','>',0];
            $field = 'og.id,o.mid,o.ordernum,o.status,o.createtime,o.paytime,og.proname name,og.proid,og.propic pic,og.num,og.parent1,og.parent2,og.parent3,og.parent1commission,og.parent2commission,og.parent3commission,og.parent1score,og.parent2score,og.parent3score,og.real_totalprice totalprice,m.nickname,m.headimg';
            $datalist = Db::name('cashier_order_goods')->alias('og')
                ->join('cashier_order o','o.id = og.orderid','left')
                ->join('member m','m.id = o.mid','left')
                ->where($where)
                ->field($field)
                ->page($pagenum,$pernum)
                ->where('og.parent1|og.parent2|og.parent3',mid)
                ->order('og.id desc')
                ->select()->toArray();
            if(!$datalist) $datalist = [];
            $newdatalist = [];
            foreach($datalist as $k=>$v){
                $data = $v;
                if($v['parent1'] == mid){
                    $data['dengji'] = t('一级');
                    if($v['parent1score'] > 0){
                        $v['parent1score'] = dd_money_format($v['parent1score'],$this->score_weishu);
                        $data['commission'] = $v['parent1score'].t('积分');
                    }else{
                        $data['commission'] = $v['parent1commission'].'元';
                    }
                }
                if($v['parent2'] == mid){
                    $data['dengji'] = t('二级');
                    if($v['parent2score'] > 0){
                        $v['parent2score'] = dd_money_format($v['parent2score'],$this->score_weishu);
                        $data['commission'] = $v['parent2score'].t('积分');
                    }else{
                        $data['commission'] = $v['parent2commission'].'元';
                    }
                }
                if($v['parent3'] == mid){
                    $data['dengji'] = t('三级');
                    if($v['parent3score'] > 0){
                        $v['parent2score'] = dd_money_format($v['parent2score'],$this->score_weishu);
                        $data['commission'] = $v['parent3score'].t('积分');
                    }else{
                        $data['commission'] = $v['parent3commission'].'元';
                    }
                }
                $data['createtime'] = date('Y-m-d H:i',$data['createtime']);
                $data['order_info'] = false;
                $newdatalist[] = $data;
            }
            if(request()->isPost()){
                return $this->json(['data'=>$newdatalist]);
            }
            $count = 0 + Db::name('cashier_order_goods')->alias('og')
                    ->join('cashier_order o','o.id = og.orderid','left')
                    ->where('og.aid',aid)
                    ->where('og.parent1|og.parent2|og.parent3',mid)
                ->count();
            $count1 = 0 + Db::name('cashier_order_goods')->alias('og')
                    ->join('cashier_order o','o.id = og.orderid','left')
                    ->where('og.aid',aid)
                    ->where('o.status',0)
                    ->where('og.parent1|og.parent2|og.parent3',mid)
                    ->count();
            $count2 =  0 + Db::name('cashier_order_goods')->alias('og')
                ->join('cashier_order o','o.id = og.orderid','left')
                ->where('og.aid',aid)
                ->where('o.status','in',[1,2])
                ->where('og.parent1|og.parent2|og.parent3',mid)
                ->count();
            $count3 =  0 + Db::name('cashier_order_goods')->alias('og')
                    ->join('cashier_order o','o.id = og.orderid','left')
                    ->where('og.aid',aid)
                    ->where('o.status','=',3)
                    ->where('og.parent1|og.parent2|og.parent3',mid)
                    ->count();
            $commissionyj1 =  0+Db::name('cashier_order_goods')->alias('og')
                ->join('cashier_order o','o.id = og.orderid','left')
                ->where('og.aid',aid)
                ->where('o.status','in',[1,2])
                ->where('og.parent1',mid)
                ->sum('parent1commission');
            $commissionyj2 =  0+Db::name('cashier_order_goods')->alias('og')
                    ->join('cashier_order o','o.id = og.orderid','left')
                    ->where('og.aid',aid)
                    ->where('o.status','in',[1,2])
                    ->where('og.parent2',mid)
                    ->sum('parent2commission');
            $commissionyj3 =  0+Db::name('cashier_order_goods')->alias('og')
                    ->join('cashier_order o','o.id = og.orderid','left')
                    ->where('og.aid',aid)
                    ->where('o.status','in',[1,2])
                    ->where('og.parent3',mid)
                    ->sum('parent3commission');
            $commissionyj = 0 + $commissionyj1 + $commissionyj2 + $commissionyj3;
        }
		else{
		    $field = 'og.id,og.mid,o.ordernum,o.status,o.createtime,o.paytime,og.name,og.proid,og.pic,og.num,og.parent1,og.parent2,og.parent3,og.parent1commission,og.parent2commission,og.parent3commission,og.parent1score,og.parent2score,og.parent3score,og.totalprice,og.real_totalprice';
            if(getcustom('commission_money_percent')){
                $field .= ',parent1money,parent2money,parent3money';
            }
		    $datalist = Db::query("select ".$field." from ".table_name('shop_order_goods')." og left join ".table_name('shop_order')." o on o.id=og.orderid where og.aid=".aid." $where and  (og.parent1=".mid." or og.parent2=".mid." or og.parent3=".mid.") order by og.id desc limit ".($pagenum*$pernum-$pernum).','.$pernum);
            if(!$datalist) $datalist = [];
            $newdatalist = [];
            foreach($datalist as $k=>$v){
                $data = [];
                $data['ordernum'] = $v['ordernum'];
                $data['name'] = $v['name'];
                $data['pic'] = $v['pic'];
                $data['num'] = $v['num'];
                $data['totalprice'] = $v['real_totalprice'];
                $data['createtime'] = date('Y-m-d H:i',$v['createtime']);
                $data['status'] = $v['status'];
                $memberinfo = Db::name('member')->where('id',$v['mid'])->field('nickname,headimg')->find();
                $data['nickname'] = $memberinfo['nickname'];
                $data['headimg'] = $memberinfo['headimg'];

                if($v['parent1'] == mid){
                    $data['dengji'] = t('一级');
                    if($v['parent1score'] > 0){
                        $v['parent1score'] = dd_money_format($v['parent1score'],$this->score_weishu);
                        $data['commission'] = $v['parent1score'].t('积分');
                    }else{
                        $data['commission'] = $v['parent1commission'].'元';
                    }
                    $data['dannum'] = 0;
                    if(getcustom('fengdanjiangli') && in_array($v['status'],[1,2,3])){ //是第几单
                        $dannum = Db::name('shop_order_goods')->alias('og')->join('shop_order o ','o.id=og.orderid')->where('og.aid',aid)->where('og.proid',$v['proid'])->where('o.status','in','1,2,3')->where("og.parent1",mid)->where('o.paytime','<',$v['paytime'])->sum('og.num');
                        $data['dannum'] = $dannum + 1;
                    }
                    if(getcustom('commission_money_percent')){
                        if($v['parent1money'] > 0){
                            $data['commission'] = $data['commission'].'+'.$v['parent1money'].t('余额');
                        }
                    }
                }
                if($v['parent2'] == mid){
                    $data['dengji'] = t('二级');
                    if($v['parent2score'] > 0){
                        $v['parent2score'] = dd_money_format($v['parent2score'],$this->score_weishu);
                        $data['commission'] = $v['parent2score'].t('积分');
                    }else{
                        $data['commission'] = $v['parent2commission'].'元';
                    }
                    $data['dannum'] = 0;
                    if(getcustom('fengdanjiangli') && in_array($v['status'],[1,2,3])){ //是第几单
                        $dannum = Db::name('shop_order_goods')->alias('og')->join('shop_order o ','o.id=og.orderid')->where('og.aid',aid)->where('og.proid',$v['proid'])->where('o.status','in','1,2,3')->where("og.parent2",mid)->where('o.paytime','<',$v['paytime'])->sum('og.num');
                        $data['dannum'] = $dannum + 1;
                    }
                    if(getcustom('commission_money_percent')){
                        if($v['parent2money'] > 0){
                            $data['commission'] = $data['commission'].'+'.$v['parent2money'].t('余额');
                        }
                    }
                }
                if($v['parent3'] == mid){
                    $data['dengji'] = t('三级');
                    if($v['parent3score'] > 0){
                        $v['parent2score'] = dd_money_format($v['parent2score'],$this->score_weishu);
                        $data['commission'] = $v['parent3score'].t('积分');
                    }else{
                        $data['commission'] = $v['parent3commission'].'元';
                    }
                    $data['dannum'] = 0;
                    if(getcustom('fengdanjiangli') && in_array($v['status'],[1,2,3])){ //是第几单
                        $dannum = Db::name('shop_order_goods')->alias('og')->join('shop_order o ','o.id=og.orderid')->where('og.aid',aid)->where('og.proid',$v['proid'])->where('o.status','in','1,2,3')->where("og.parent3",mid)->where('o.paytime','<',$v['paytime'])->sum('og.num');
                        $data['dannum'] = $dannum + 1;
                    }
                    if(getcustom('commission_money_percent')){
                        if($v['parent3money'] > 0){
                            $data['commission'] = $data['commission'].'+'.$v['parent3money'].t('余额');
                        }
                    }
                }
                if(getcustom('showdownorderinfo')){
                    $data['order_info'] = Db::name('shop_order')->field('id,aid,bid,mid,ordernum,title,totalprice,express_com,express_no,express_content,express_type')->where('ordernum',$v['ordernum'])->find();
                }else{
                    $data['order_info'] = false;
                }
                $newdatalist[] = $data;
            }
            if(request()->isPost()){
                return $this->json(['data'=>$newdatalist]);
            }

            $count = Db::query("select count(1)c from ".table_name('shop_order_goods')." og left join ".table_name('shop_order')." o on o.id=og.orderid where og.aid=".aid." and (og.parent1=".mid." or og.parent2=".mid." or og.parent3=".mid.")");
            $count = 0 + $count[0]['c'];

            $count1 = Db::query("select count(1)c from ".table_name('shop_order_goods')." og left join ".table_name('shop_order')." o on o.id=og.orderid where og.aid=".aid." and o.status=0 and (og.parent1=".mid." or og.parent2=".mid." or og.parent3=".mid.")");
            $count1 = 0 + $count1[0]['c'];

            $count2 = Db::query("select count(1)c from ".table_name('shop_order_goods')." og left join ".table_name('shop_order')." o on o.id=og.orderid where og.aid=".aid." and (o.status=1 or o.status=2) and (og.parent1=".mid." or og.parent2=".mid." or og.parent3=".mid.")");
            $count2 = 0 + $count2[0]['c'];

            $count3 = Db::query("select count(1)c from ".table_name('shop_order_goods')." og left join ".table_name('shop_order')." o on o.id=og.orderid where og.aid=".aid." and o.status=3 and (og.parent1=".mid." or og.parent2=".mid." or og.parent3=".mid.")");
            $count3 = 0 + $count3[0]['c'];

            $commissionyj1 = Db::query("select sum(parent1commission)c from ".table_name('shop_order_goods')." og left join ".table_name('shop_order')." o on o.id=og.orderid where og.aid=".aid." and (o.status=1 or o.status=2) and og.parent1=".mid."");
            $commissionyj2 = Db::query("select sum(parent2commission)c from ".table_name('shop_order_goods')." og left join ".table_name('shop_order')." o on o.id=og.orderid where og.aid=".aid." and (o.status=1 or o.status=2) and og.parent2=".mid."");
            $commissionyj3 = Db::query("select sum(parent3commission)c from ".table_name('shop_order_goods')." og left join ".table_name('shop_order')." o on o.id=og.orderid where og.aid=".aid." and (o.status=1 or o.status=2) and og.parent3=".mid."");
            $commissionyj = 0 + $commissionyj1[0]['c'] + $commissionyj2[0]['c'] + $commissionyj3[0]['c'];
            if(getcustom('commission_money_percent')){
                $commissionyj1_money = Db::query("select sum(parent1money)c from ".table_name('shop_order_goods')." og left join ".table_name('shop_order')." o on o.id=og.orderid where og.aid=".aid." and (o.status=1 or o.status=2) and og.parent1=".mid."");
                $commissionyj2_money = Db::query("select sum(parent2money)c from ".table_name('shop_order_goods')." og left join ".table_name('shop_order')." o on o.id=og.orderid where og.aid=".aid." and (o.status=1 or o.status=2) and og.parent2=".mid."");
                $commissionyj3_money = Db::query("select sum(parent3money)c from ".table_name('shop_order_goods')." og left join ".table_name('shop_order')." o on o.id=og.orderid where og.aid=".aid." and (o.status=1 or o.status=2) and og.parent3=".mid."");

                $commissionyj = $commissionyj + $commissionyj1_money[0]['c'] + $commissionyj2_money[0]['c'] + $commissionyj3_money[0]['c'];
            }
        }
		
		
		$rdata = [];
		$rdata['count'] = $count;
		$rdata['count1'] = $count1;
		$rdata['count2'] = $count2;
		$rdata['count3'] = $count3;
		$rdata['commissionyj'] = round($commissionyj,2);
		$rdata['datalist'] = $newdatalist;
		$rdata['st'] = $st;

        $is_cashdesk_commission = 0;
        if(getcustom('cashdesk_commission')){
            $is_cashdesk_commission = 1;
        }
        $rdata['is_cashdesk_commission'] = $is_cashdesk_commission;
		return $this->json($rdata);
	}

	//股东分红
	public function fenhong(){
		$pernum = 20;
		$pagenum = input('param.pagenum') ? input('param.pagenum') :1;
		$where = '';
		$st = input('param.st/d');
		if(!$st) $st = 1;

		//待结算
		$newoglist = [];
		if($st == 1){
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $where[] = ['type','=','fenhong'];
            $where[] = ['status','=',0];
            $commissionyj = Db::name('member_fenhonglog')->where($where)->sum('commission');
            $count = 0;
            $fhlist = Db::name('member_fenhonglog')->where($where)->order('id desc')->select()->toArray();
            if(!$fhlist) $fhlist = [];
            foreach($fhlist as $k=>$v){
                //根据分红记录获取分红订单信息
                $og_item = $this->getFhOrder($v);
                if($og_item){
                    $newoglist[] = $og_item;
                }
            }
		}
        if($st==2){
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $where[] = ['type','=','fenhong'];
            $where[] = ['status','=',1];
            $pernum = 20;
            $pagenum = input('param.pagenum');
            if(!$pagenum) $pagenum = 1;
            $count = Db::name('member_fenhonglog')->where($where)->count();
            $datalist = Db::name('member_fenhonglog')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            foreach($datalist as $k=>$v){
                if($v['ogids']){
                    if($v['module'] == 'yuyue'){
                        $oglist = Db::name('yuyue_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.proname name,og.propic pic,og.num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                    }elseif($v['module'] == 'luckycollage' || $v['module'] == 'lucky_collage'){
                        $oglist = Db::name('lucky_collage_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.proname name,og.propic pic,og.num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                    }elseif($v['module'] == 'scoreshop'){
                        $oglist = Db::name('scoreshop_order_goods')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.name,og.pic,og.num,og.totalmoney real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                    }elseif($v['module'] == 'kecheng'){
                        $oglist = Db::name('kecheng_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.title as name,og.pic,"1" as num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                    }elseif($v['module'] == 'maidan'){
                        $oglist = Db::name('maidan_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.title as name,"" as pic,"1" as num,og.paymoney real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                    }elseif($v['module'] == 'shop' || empty($v['module'])){
                        $oglist = Db::name('shop_order_goods')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.name,og.pic,og.num,og.real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                    }else{
                        $oglist = [];
                    }
                }else{
                    $oglist = [];
                }
                $datalist[$k]['oglist'] = $oglist;
            }
        }
		$rdata = [];
		$rdata['count'] = $count + count($newoglist);
		$rdata['commissionyj'] = round($commissionyj,2);
        if($st == 1){
            $rdata['datalist'] = $newoglist;
        }else{
            $rdata['datalist'] = $datalist;
        }
		$rdata['st'] = $st;
		return $this->json($rdata);
	}
	//团队分红
	public function teamfenhong(){
		$pernum = 20;
		$pagenum = input('param.pagenum') ? input('param.pagenum') :1;
		$where = '';
		$st = input('param.st/d');
		if(!$st) $st = 1;
        $moeny_weishu = 2;
        if(getcustom('fenhong_money_weishu')){
            $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
        }
		//待结算
		$newoglist = [];
		if($st == 1){
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $where[] = ['type','in',['teamfenhong','teamfenhong_pj','teamfenhong_bole','product_teamfenhong','level_teamfenhong']];
            $where[] = ['status','=',0];
            $commissionyj = Db::name('member_fenhonglog')->where($where)->sum('commission');

            $pernum = 20;
            $pagenum = input('param.pagenum');
            if(!$pagenum) $pagenum = 1;
            $count = Db::name('member_fenhonglog')->where($where)->count();
            $fhlist = Db::name('member_fenhonglog')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            foreach($fhlist as $k=>$v) {
                //根据分红记录获取分红订单信息
                $og_item = $this->getFhOrder($v);
                if($og_item){
                    $newoglist[] = $og_item;
                }
            }
		}
        if($st==2) {
            $where = [];
            $where[] = ['aid', '=', aid];
            $where[] = ['mid', '=', mid];
            $where[] = ['type','in',['teamfenhong','teamfenhong_pj','teamfenhong_bole','product_teamfenhong','level_teamfenhong']];
            $where[] = ['status', '=', 1];
            $pernum = 20;
            $pagenum = input('param.pagenum');
            if (!$pagenum) $pagenum = 1;
            $count = Db::name('member_fenhonglog')->where($where)->count();
            $datalist = Db::name('member_fenhonglog')->where($where)->page($pagenum, $pernum)->order('id desc')->select()->toArray();
            if (!$datalist) $datalist = [];
            foreach ($datalist as $k => $v) {
                if ($v['ogids']) {
                    if ($v['module'] == 'yuyue') {
                        $oglist = Db::name('yuyue_order')->alias('og')->join('member m', 'og.mid=m.id')->field('og.ordernum,og.proname name,og.propic pic,og.num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id', 'in', $v['ogids'])->select()->toArray();
                    } elseif ($v['module'] == 'luckycollage' || $v['module'] == 'lucky_collage') {
                        $oglist = Db::name('lucky_collage_order')->alias('og')->join('member m', 'og.mid=m.id')->field('og.ordernum,og.proname name,og.propic pic,og.num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id', 'in', $v['ogids'])->select()->toArray();
                    } elseif ($v['module'] == 'scoreshop') {
                        $oglist = Db::name('scoreshop_order_goods')->alias('og')->join('member m', 'og.mid=m.id')->field('og.ordernum,og.name,og.pic,og.num,og.totalmoney real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id', 'in', $v['ogids'])->select()->toArray();
                    } elseif ($v['module'] == 'kecheng') {
                        $oglist = Db::name('kecheng_order')->alias('og')->join('member m', 'og.mid=m.id')->field('og.ordernum,og.title as name,og.pic,"1" as num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id', 'in', $v['ogids'])->select()->toArray();
                    } elseif ($v['module'] == 'maidan') {
                        $oglist = Db::name('maidan_order')->alias('og')->join('member m', 'og.mid=m.id')->field('og.ordernum,og.title as name,"" as pic,"1" as num,og.paymoney real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id', 'in', $v['ogids'])->select()->toArray();
                    } elseif ($v['module'] == 'shop' || empty($v['module'])) {
                        $oglist = Db::name('shop_order_goods')->alias('og')->join('member m', 'og.mid=m.id')->field('og.ordernum,og.name,og.pic,og.num,og.real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id', 'in', $v['ogids'])->select()->toArray();
                    } else {
                        $oglist = [];
                    }
                } else {
                    $oglist = [];
                }
                $datalist[$k]['oglist'] = $oglist;
            }
        }

		$rdata = [];
		$rdata['count'] = $count;
        
		$rdata['commissionyj'] = dd_money_format($commissionyj,$moeny_weishu);

        if($st == 1){
            $rdata['datalist'] = $newoglist;
        }else{
            $rdata['datalist'] = $datalist;
        }
		$rdata['st'] = $st;
		return $this->json($rdata);
	}
    //运费补贴
    public function teamfenhongfreight(){
	    if (getcustom('teamfenhong_freight_money')){
            $pernum = 20;
            $pagenum = input('param.pagenum') ? input('param.pagenum') :1;
    
            $st = input('param.st/d');
            if(!$st) $st = 1;
            $moeny_weishu = 2;
            if(getcustom('fenhong_money_weishu')){
                $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
            }
            //待结算
            $datalist = [];
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $where[] = ['type','=','teamfenhong_freight'];
            if($st==1)$where[] = ['status','=',0];
            if($st==2) $where[] = ['status', '=', 1];
            $count = Db::name('member_fenhonglog')->where($where)->count();
            $fhlist = Db::name('member_fenhonglog')->where($where)->page($pagenum, $pernum)->order('id desc')->select()->toArray();
            if (!$fhlist) $fhlist = [];
            if($st == 1){
                foreach($fhlist as $k=>$v) {
                    //根据分红记录获取分红订单信息
                    $og_item = $this->getFhOrder($v);
                    if($og_item){
                        $datalist[] = $og_item;
                    }
                }
            }
            if($st == 2){
                $datalist =  $fhlist;
                foreach($datalist as $k=>$v) {
                    $oglist = Db::name('shop_order_goods')->alias('og')->join('member m', 'og.mid=m.id')->field('og.ordernum,og.name,og.pic,og.num,og.real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id', 'in', $v['ogids'])->select()->toArray();
                    $datalist[$k]['oglist'] = $oglist;
                }
            } 
            
            $rdata = [];
            $rdata['count'] = $count;
            $commissionyj = Db::name('member_fenhonglog')->where('status',0)->where($where)->sum('commission');
            $rdata['commissionyj'] = dd_money_format($commissionyj,$moeny_weishu);
            $rdata['datalist'] = $datalist;
            $rdata['st'] = $st;
            return $this->json($rdata);
        }
    }
	//区域代理分红
	public function areafenhong(){
		$pernum = 20;
		$pagenum = input('param.pagenum') ? input('param.pagenum') :1;
		$where = '';
		$st = input('param.st/d');
		if(!$st) $st = 1;
        $moeny_weishu = 2;
        if(getcustom('fenhong_money_weishu')){
            $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
        }
		//待结算
		$newoglist = [];
		if($st == 1){
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $where[] = ['type','=','areafenhong'];
            $where[] = ['status','=',0];
            $commissionyj = Db::name('member_fenhonglog')->where($where)->sum('commission');
            $fhlist = Db::name('member_fenhonglog')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$fhlist) $fhlist = [];
            foreach($fhlist as $k=>$v){
                //根据分红记录获取分红订单信息
                $og_item = $this->getFhOrder($v);
                if($og_item){
                    $newoglist[] = $og_item;
                }
            }
		}
        if($st==2){
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $where[] = ['type','=','areafenhong'];
            $where[] = ['status','=',1];
            $pernum = 20;
            $pagenum = input('param.pagenum');
            if(!$pagenum) $pagenum = 1;
            $count = Db::name('member_fenhonglog')->where($where)->count();
            $datalist = Db::name('member_fenhonglog')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            foreach($datalist as $k=>$v){
                if($v['ogids']){
                    if($v['module'] == 'yuyue'){
                        $oglist = Db::name('yuyue_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.proname name,og.propic pic,og.num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                    }elseif($v['module'] == 'luckycollage' || $v['module'] == 'lucky_collage'){
                        $oglist = Db::name('lucky_collage_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.proname name,og.propic pic,og.num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                    }elseif($v['module'] == 'scoreshop'){
                        $oglist = Db::name('scoreshop_order_goods')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.name,og.pic,og.num,og.totalmoney real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                    }elseif($v['module'] == 'kecheng'){
                        $oglist = Db::name('kecheng_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.title as name,og.pic,"1" as num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                    }elseif($v['module'] == 'maidan'){
                        $oglist = Db::name('maidan_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.title as name,"" as pic,"1" as num,og.paymoney real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                    }elseif($v['module'] == 'shop' || empty($v['module'])){
                        $oglist = Db::name('shop_order_goods')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.name,og.pic,og.num,og.real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                    }else{
                        $oglist = [];
                    }
                }else{
                    $oglist = [];
                }
                $datalist[$k]['oglist'] = $oglist;
                $datalist[$k]['commission'] = dd_money_format($v['commission'],$moeny_weishu);
            }
        }
		$rdata = [];
		$rdata['count'] = $count + count($newoglist);
		$rdata['commissionyj'] = dd_money_format($commissionyj,$moeny_weishu);
        if($st == 1){
            $rdata['datalist'] = $newoglist;
        }else{
            $rdata['datalist'] = $datalist;
        }
		$rdata['st'] = $st;
		return $this->json($rdata);
	}

	//分红订单
	public function fhorder(){
		$pernum = 20;
		$pagenum = input('param.pagenum') ? input('param.pagenum') :1;
		$where = '';
		$st = input('param.st/d');
		if(!$st) $st = 1;

		$fhjiesuanbusiness = $this->sysset['fhjiesuanbusiness'];
        $fhjiesuantype = $this->sysset['fhjiesuantype'];
		$fhjiesuantime_type = $this->sysset['fhjiesuantime_type'];
        $moeny_weishu = 2;
        if(getcustom('fenhong_money_weishu')){
            $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
        }
		//待结算
		$newoglist = [];
		if($st == 1){
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
//            $where[] = ['type','=','teamfenhong'];
            $where[] = ['status','=',0];
            $commissionyj = Db::name('member_fenhonglog')->where($where)->sum('commission');

            $pernum = 20;
            $pagenum = input('param.pagenum');
            if(!$pagenum) $pagenum = 1;
            $count = Db::name('member_fenhonglog')->where($where)->count();
            $fhlist = Db::name('member_fenhonglog')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            foreach($fhlist as $k=>$v) {
                //根据分红记录获取分红订单信息
                $og_item = $this->getFhOrder($v);
                if($og_item){
                    $newoglist[] = $og_item;
                }
            }
            if(getcustom('car_hailing')){
                $cg_rs1 = \app\common\CarhailingFenhong::gdfenhong(aid,$this->sysset,[],0,time(),1,mid);
                if($cg_rs1 && $cg_rs1['oglist']){
                    foreach($cg_rs1['oglist'] as &$val){
                        $val['name'] = $val['title'];
                        $val['real_totalprice'] = $val['totalprice'];
                    }
                    $newoglist = array_merge($newoglist,$cg_rs1['oglist']);
                    $commissionyj += $cg_rs1['commissionyj'];
                }
                $cg_rs2 = \app\common\CarhailingFenhong::teamfenhong(aid,$this->sysset,[],0,time(),1,mid);
                if($cg_rs2 && $cg_rs2['oglist']){
                    foreach($cg_rs2['oglist'] as &$val){
                        $val['name'] = $val['title'];
                        $val['real_totalprice'] = $val['totalprice'];
                    }
                    $newoglist = array_merge($newoglist,$cg_rs2['oglist']);
                    $commissionyj += $cg_rs2['commissionyj'];
                }
            }
		}

		if($st==2){//已结算
			$where = [];
			$where[] = ['aid','=',aid];
			$where[] = ['mid','=',mid];
            $where[] = ['status','=',1];
			$pernum = 20;
			$pagenum = input('param.pagenum');
			if(!$pagenum) $pagenum = 1;
			$count = Db::name('member_fenhonglog')->where($where)->count();
			$datalist = Db::name('member_fenhonglog')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
			if(!$datalist) $datalist = [];
			foreach($datalist as $k=>$v){
				if($v['ogids']){
					if($v['module'] == 'yuyue'){
						$oglist = Db::name('yuyue_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.proname name,og.propic pic,og.num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
					}elseif($v['module'] == 'luckycollage' || $v['module'] == 'lucky_collage'){
						$oglist = Db::name('lucky_collage_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.proname name,og.propic pic,og.num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                    }elseif($v['module'] == 'scoreshop'){
                        $oglist = Db::name('scoreshop_order_goods')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.name,og.pic,og.num,og.totalmoney real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                    }elseif($v['module'] == 'kecheng'){
                        $oglist = Db::name('kecheng_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.title as name,og.pic,"1" as num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                    }elseif($v['module'] == 'maidan'){
                        $oglist = Db::name('maidan_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.title as name,"" as pic,"1" as num,og.paymoney real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                    }elseif($v['module'] == 'car_hailing'){
					    if(getcustom('car_hailing')){
                            $oglist = Db::name('car_hailing_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.title as name,"" as pic,"1" as num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                        }
                    }elseif($v['module'] == 'shop' || empty($v['module'])){
						$oglist = Db::name('shop_order_goods')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.name,og.pic,og.num,og.real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
					}elseif($v['module'] == 'cashier_order'){
                        if(getcustom('fenhong_cashier_order',aid)){
                            $oglist = Db::name('cashier_order_goods')
                                ->alias('og')
                                ->join('cashier_order o','og.orderid=o.id')
                                ->join('member m','o.mid=m.id')
                                ->field('o.ordernum,og.proname as name,og.propic as pic,og.num,og.real_totalprice,og.createtime,o.status,m.nickname,m.headimg')
                                ->where('og.id','in',$v['ogids'])
                                ->select()
                                ->toArray();
                        }
                    }else{
						$oglist = [];
					}
				}else{
					$oglist = [];
				}
				$datalist[$k]['oglist'] = $oglist;
                $datalist[$k]['commission'] = dd_money_format($v['commission'],$moeny_weishu);
            }
		}
		$rdata = [];
		$rdata['count'] = $count ;
		$rdata['commissionyj'] = dd_money_format($commissionyj,$moeny_weishu);
		if($st == 1){
			$rdata['datalist'] = $newoglist;
		}else{
			$rdata['datalist'] = $datalist;
		}
		$rdata['st'] = $st;
		return $this->json($rdata);
	}
    //股东分红
    public function touzifenhong(){
	    if(getcustom('touzi_fenhong')){
            
            $st = input('param.st/d');
            if(!$st) $st = 1;
            $moeny_weishu = 2;
            if(getcustom('fenhong_money_weishu')){
                $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
            }
            //待结算
            $newoglist = [];
            if($st == 1){
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['mid','=',mid];
                $where[] = ['type','=','touzi_fenhong'];
                $where[] = ['status','=',0];
                $pernum = 20;
                $pagenum = input('param.pagenum');
                if(!$pagenum) $pagenum = 1;
                $commissionyj = Db::name('member_fenhonglog')->where($where)->sum('commission');
                $count = Db::name('member_fenhonglog')->where($where)->count();
                $fhlist = Db::name('member_fenhonglog')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
                if(!$fhlist) $fhlist = [];
                foreach($fhlist as $k=>$v){
                    //根据分红记录获取分红订单信息
                    $og_item = $this->getFhOrder($v);
                    if($og_item){
                        $newoglist[] = $og_item;
                    }
                }
            }

            if($st==2){//已结算
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['mid','=',mid];
                $where[] = ['type','=','touzi_fenhong'];
                $where[] = ['status','=',1];
                $pernum = 20;
                $pagenum = input('param.pagenum');
                if(!$pagenum) $pagenum = 1;
                $count = Db::name('member_fenhonglog')->where($where)->count();
                $datalist = Db::name('member_fenhonglog')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
                if(!$datalist) $datalist = [];
                foreach($datalist as $k=>$v){
                    if($v['ogids']){
                        $oglist = Db::name('shop_order_goods')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.name,og.pic,og.num,og.real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                    }else{
                        $oglist = [];
                    }
                    $datalist[$k]['oglist'] = $oglist;
                    $datalist[$k]['commission'] = dd_money_format($v['commission'],$moeny_weishu);
                }
            }
            $rdata = [];
            $rdata['count'] = $count;
            $rdata['commissionyj'] = dd_money_format($commissionyj,$moeny_weishu);
            if($st == 1){
                $rdata['datalist'] = $newoglist;
            }else{
                $rdata['datalist'] = $datalist;
            }
            $rdata['st'] = $st;
            return $this->json($rdata);
        }
       
    }
    //根据分红记录获取分红订单信息
    public function getFhOrder($v){
        $moeny_weishu = 2;
        if(getcustom('fenhong_money_weishu')){
            $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
        }
        if ($v['ogids']) {
            if ($v['module'] == 'yuyue') {
                $oglist = Db::name('yuyue_order')->alias('og')->join('member m', 'og.mid=m.id')->field('og.ordernum,og.proname name,og.propic pic,og.num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id', 'in', $v['ogids'])->find();
            } elseif ($v['module'] == 'luckycollage' || $v['module'] == 'lucky_collage') {
                $oglist = Db::name('lucky_collage_order')->alias('og')->join('member m', 'og.mid=m.id')->field('og.buytype,og.teamid,og.ordernum,og.proname name,og.propic pic,og.num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id', 'in', $v['ogids'])->find();
                if($oglist['buytype']!=1){
                    $teamid = $oglist['teamid'];
                    $oglist = Db::name('lucky_collage_order')->alias('og')->join('member m', 'og.mid=m.id')->where('teamid',$teamid)->where('iszj',1)->field('og.ordernum,og.proname name,og.propic pic,og.num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->find();
                }
            } elseif ($v['module'] == 'scoreshop') {
                $oglist = Db::name('scoreshop_order_goods')->alias('og')->join('member m', 'og.mid=m.id')->field('og.ordernum,og.name,og.pic,og.num,og.totalmoney real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id', 'in', $v['ogids'])->find();
            } elseif ($v['module'] == 'kecheng') {
                $oglist = Db::name('kecheng_order')->alias('og')->join('member m', 'og.mid=m.id')->field('og.ordernum,og.title as name,og.pic,"1" as num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id', 'in', $v['ogids'])->find();
            } elseif ($v['module'] == 'maidan') {
                $oglist = Db::name('maidan_order')->alias('og')->join('member m', 'og.mid=m.id')->field('og.ordernum,og.title as name,"" as pic,"1" as num,og.paymoney real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id', 'in', $v['ogids'])->find();
            } elseif($v['module'] == 'cashier'){
                $oglist = Db::name('cashier_order_goods')
                    ->alias('og')
                    ->join('cashier_order o','og.orderid=o.id')
                    ->join('member m','o.mid=m.id')
                    ->field('o.ordernum,og.proname as name,og.propic as pic,og.num,og.real_totalprice,og.createtime,o.status,m.nickname,m.headimg')
                    ->where('og.id','in',$v['ogids'])->find();
            }elseif($v['module'] == 'restaurant_shop'){
                $oglist = Db::name('restaurant_shop_order_goods')->alias('og')->join('member m', 'og.mid=m.id')->field('og.ordernum,og.name,og.pic,og.num,og.real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id', 'in', $v['ogids'])->find();
            }elseif($v['module'] == 'restaurant_takeaway'){
                $oglist = Db::name('restaurant_takeaway_order_goods')->alias('og')->join('member m', 'og.mid=m.id')->field('og.ordernum,og.name,og.pic,og.num,og.real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id', 'in', $v['ogids'])->find();
            }elseif ($v['module'] == 'shop' || empty($v['module'])) {
                $oglist = Db::name('shop_order_goods')->alias('og')->join('member m', 'og.mid=m.id')->field('og.ordernum,og.name,og.pic,og.num,og.real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id', 'in', $v['ogids'])->find();
                if(getcustom('mendian_dianzhan_commission')) {
                    $products = Db::name('shop_order_goods')->alias('og')->join('member m', 'og.mid=m.id')->field('og.name,og.num,og.real_totalprice')->where('og.id', 'in', $v['ogids'])->select()->toArray();
                    $oglist['products'] = $products;
                }
            }elseif ($v['module'] == 'coupon') {
                $oglist = Db::name('coupon_order')->alias('og')->join('member m', 'og.mid=m.id')->field('og.ordernum,og.title name,0 as pic,1 as num,og.price real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id', 'in', $v['ogids'])->find();
            }  else {
                $oglist = [];
            }
        } else {
            $oglist = [];
        }
        if($oglist){
            $oglist['commission'] = round($v['commission'],$moeny_weishu);
        }
        return $oglist;
    }

    //门店服务订单
    public function orderMendian(){
        if(getcustom('plug_zhiming')) {
            $pernum = 20;
            $pagenum = input('param.pagenum') ? input('param.pagenum') :1;
            $st = input('param.st/d');
            if(!$st) $st = 0;
            $admin_user_ids = Db::name('admin_user')->where('aid', aid)->where('mid', mid)->where('mdid', '>', 0)->column('id');

            $where[] = ['order.aid','=',aid];
//            $where[] = ['order.bid','=',];
            $where[] = ['order.uid', 'in', $admin_user_ids];
            $order = 'id desc';
            if(input('param.keyword')) $where[] = ['member.nickname','like','%'.trim(input('param.keyword')).'%'];
            if(input('?param.status') && input('param.status')!=='') $where[] = ['member_moneylog.status','=',input('param.status')];
            $datalist = Db::name('hexiao_order')->alias('order')->field('member.nickname,member.headimg,order.*')->leftJoin('member member','member.id=order.mid')
                ->where($where)->page($pagenum,$pernum)->order($order)->select()->toArray();
            if(!$datalist) $datalist = [];
            $newdatalist = [];
            foreach($datalist as $k=>$v){
                $data = [];
                $data['ordernum'] = $v['ordernum'];
                $data['name'] = $v['title'];
                $data['pic'] = $v['pic'];
                $data['num'] = $v['num'];
                $data['createtime'] = date('Y-m-d H:i',$v['createtime']);
                $data['status'] = $v['status'];
                $data['nickname'] = $v['nickname'];
                $data['headimg'] = $v['headimg'];
                $newdatalist[] = $data;
            }
            if(request()->isPost()){
                return $this->json(['data'=>$newdatalist]);
            }

            $rdata = [];
            $rdata['count'] = 0;
            $rdata['commissionyj'] = 0;
            $rdata['datalist'] = $newdatalist;
            $rdata['st'] = $st;
            return $this->json($rdata);
        }

        return $this->json([]);
    }
	
	//分红记录
	public function fhlog(){
		$st = input('param.st');
        $module = input('param.module');
        if (input('date_start') && input('date_end')) {
            $date_start = strtotime(input('date_start'));
            $date_end = strtotime(input('date_end')) + 86399;
        }
		$where = [];
		$where[] = ['aid','=',aid];
		$where[] = ['mid','=',mid];
        $where[] = ['status','=',1];
		$pernum = 20;
		$pagenum = input('post.pagenum');
        if(getcustom('mendian_member_levelup_fenhong')){
            if($module){
                $where[] = ['module','=',$module];
            }
            if(input('param.mdid')){
                $where[] = ['frommid','=',input('param.mdid')];
            }
            if ($date_start && $date_end) {
                $where[] = ['createtime', 'between', [$date_start, $date_end]];
            }
        }
		if(!$pagenum) $pagenum = 1;
		$datalist = Db::name('member_fenhonglog')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
		if(!$datalist) $datalist = [];
        $moeny_weishu = 2;
        if(getcustom('fenhong_money_weishu')){
            $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
        }
        foreach ($datalist as $key=>$val){
            $datalist[$key]['commission'] = dd_money_format($val['commission'],$moeny_weishu);
            if(getcustom('fenhong_show_from_member')) {
                if ($val['frommid']) {
                    $from_member = Db::name('member')->where('id', $val['frommid'])->find();
                    $datalist[$key]['from_headimg'] = $from_member['headimg'];
                    $datalist[$key]['from_nickname'] = $from_member['nickname'];
                } else {
                    $datalist[$key]['from_headimg'] = '';
                    $datalist[$key]['from_nickname'] = '';
                }
            }
        }
        $mendian_member_levelup_fenhong = false;
        if(getcustom('mendian_member_levelup_fenhong')){
            $mendian_member_levelup_fenhong = true;
        }
        //是否展示分红来源会员信息
        $show_from_member = 0;
        if(getcustom('fenhong_show_from_member')){
            $show_from_member = 1;
        }
		return $this->json(['status'=>1,'data'=>$datalist,'mendian_member_levelup_fenhong' =>$mendian_member_levelup_fenhong,'show_from_member'=>$show_from_member]);
	}
	//分享海报
	public function poster(){

        if(getcustom('member_realname_verify')) {
            $realname_set = Db::name('member_realname_set')->where('aid', aid)->find();
            if ($realname_set['status'] == 1 && $realname_set['view_poster'] == 0 && $this->member['realname_status'] != 1){
                return $this->json(['status'=>-4,'msg'=>'请先实名认证','url'=>'/pagesExt/my/setrealname']);
            }
        }

		$member = $this->member;
		$platform = platform;
		$page = $this->indexurl;
		$scene = 'pid_'.$this->member['id'];
		//if($platform == 'mp' || $platform == 'h5' || $platform == 'app'){
		//	$page = PRE_URL .'/h5/'.aid.'.html#'. $page;
		//}

		if(input('param.posterid')){
			$posterset = Db::name('admin_set_poster')->where('aid',aid)->where('type','index')->where('platform',$platform)->where('id',input('param.posterid'))->find();
		}else{
			$posterset = Db::name('admin_set_poster')->where('aid',aid)->where('type','index')->where('platform',$platform)->order('id')->find();
		}
		$posterdata = Db::name('member_poster')->where('aid',aid)->where(['mid'=>mid,'posterid'=>$posterset['id']])->find();
        $getposter = false;//是否需要生成海报
        if(!$posterdata) $getposter =true;

        $field = 'id,name,logo,desc';
        if(getcustom('reg_invite_code')){
            $field .= ',reg_invite_code_type';
        }
		$sysset = Db::name('admin_set')->field($field)->where('aid',aid)->find();
		if(getcustom('app_reg') && $platform == 'app'){
			$page = 'pages/index/reg';
			$scene.='-fromapp_1'; 
		}
        if(getcustom('product_price_rate')){
            $scene.='-priceRate_'.$member['price_rate'];
        }
        if(getcustom('member_dedamount')){
            //标记类型为分享海报
            $scene .= '-ispr_1';
            if($posterdata && (empty($posterdata['scene']) || !strpos($posterdata['scene'],'-ispr_1')) ){
                $getposter = true;
            }
        }
		if($getposter){
            $yqcode = '';
            if(getcustom('reg_invite_code')){
                if($sysset['reg_invite_code_type'] == 1){
                    $yqcode = !empty($member['yqcode'])?$member['yqcode']:'';
                }else{
                    $yqcode = !empty($member['tel'])?$member['tel']:'';
                }
            }
			$textReplaceArr = [
				'[头像]'=>$member['headimg'],
				'[昵称]'=>$member['nickname'],
				'[姓名]'=>$member['realname'],
				'[手机号]'=>$member['tel'],
				'[商城名称]'=>$sysset['name'],
                '[邀请码]'=>$yqcode ,
			];
			$poster = $this->_getposter(aid,0,$platform,$posterset['content'],$page,$scene,$textReplaceArr);
			$data = [];
			$data['scene']  = $scene;
			$data['page']   = $page;
			$data['poster'] = $poster;
			$data['posterid'] = $posterset['id'];
            if(!$posterdata){
                $data['aid'] = aid;
                $data['mid'] = $member['id'];
                $data['createtime'] = time();
                Db::name('member_poster')->insert($data);
            }else{
                Db::name('member_poster')->where('id',$posterdata['id'])->update($data);
            }
            $posterdata['poster'] = $data['poster'];
		}

		$posterlist = Db::name('admin_set_poster')->field('id')->where('aid',aid)->where('type','index')->where('platform',$platform)->order('id')->select()->toArray();
        if(getcustom('coupon_xianxia_buy')){
            $is_show_title = 0;
        }
		$rdata = [];
        $rdata['is_show_title'] = $is_show_title;
		$rdata['poster'] = $posterdata['poster'];
		$rdata['guize'] = $posterset['guize'];
		$rdata['posterid'] = $posterset['id'];
		$rdata['posterlist'] = $posterlist;
		$rdata['postercount'] = count($posterlist);
		return $this->json($rdata);
	}
	//给余额
	public function givemoney(){
		$userlevel = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->find();
		if($userlevel['team_givemoney'] == 0) return $this->json(['status'=>0,'msg'=>'给下级转账功能未开启']);

        if(getcustom('member_lock')){
            $field = 'lock_withdraw_givemoney';
            $userinfo = Db::name('member')->where('id',mid)->field($field)->find();
            if($userinfo['lock_withdraw_givemoney'] == 1){                
                return $this->json(['status'=>0,'msg'=>'账号已锁定，请联系管理员处理！']);
            }            
        }
		$id = input('param.id/d');
		$money = input('param.money/f');
		if($money <= 0) return $this->json(['status'=>0,'msg'=>'转账金额必须大于0']);
		$tomember = Db::name('member')->where('aid',aid)->where('id',$id)->find();
		if(!$tomember) return $this->json(['status'=>0,'msg'=>'用户不存在']);
		if($money > $this->member['money']) return $this->json(['status'=>0,'msg'=>t('余额').'不足']);
		\app\common\Member::addmoney(aid,$id,$money,$this->member['nickname'].'-转账');
		\app\common\Member::addmoney(aid,mid,-$money,'转账给'.$tomember['nickname']);
		return $this->json(['status'=>1,'msg'=>'转账成功']);
	}
	//给积分
	public function givescore(){
		$userlevel = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->find();
		if($userlevel['team_givescore'] == 0) return $this->json(['status'=>0,'msg'=>'给下级转账功能未开启']);
        
        if(getcustom('member_lock')){
            $field = 'lock_withdraw_givemoney';
            $userinfo = Db::name('member')->where('id',mid)->field($field)->find();
            if($userinfo['lock_withdraw_givemoney'] == 1){                
                return $this->json(['status'=>0,'msg'=>'账号已锁定，请联系管理员处理！']);
            }            
        }
		$id = input('param.id/d');
		$score = input('param.score/d');
		if($score <= 0) return $this->json(['status'=>0,'msg'=>'转账数量必须大于0']);
		$tomember = Db::name('member')->where('aid',aid)->where('id',$id)->find();
		if(!$tomember) return $this->json(['status'=>0,'msg'=>'用户不存在']);
		if($score > $this->member['score']) return $this->json(['status'=>0,'msg'=>t('积分').'不足']);
		\app\common\Member::addscore(aid,$id,$score,$this->member['nickname'].'-转账');
		\app\common\Member::addscore(aid,mid,-$score,'转账给'.$tomember['nickname']);
		return $this->json(['status'=>1,'msg'=>'转账成功']);
	}

	//升级（分销商给下级升级
	public function levelUp()
    {
        if(getcustom('member_levelup_givechild')) {
            // if(!getcustom('plug_zhiming')) {
            //     return $this->json(['status'=>0,'msg'=>'操作失败']);
            // }
            $mid = input('param.mid/d');
            $levelId = input('param.levelId/d');            
            $tomember = Db::name('member')->where('aid',aid)->where('id',$mid)->find();
            if(!$tomember) return $this->json(['status'=>0,'msg'=>'用户不存在']);
            if(!in_array(mid,explode(',',$tomember['path']))) {
                return $this->json(['status'=>0,'msg'=>'无权限操作此用户']);
            }

            $memberlevel = Db::name('member_level')->where('aid',aid)->where('id',$tomember['levelid'])->find();
            $tolevel = Db::name('member_level')->where('aid',aid)->where('id',$levelId)->find();
            if($tolevel['sort'] <= $memberlevel['sort']) {
                return $this->json(['status'=>0,'msg'=>'只能为用户升级']);
            }

            $userlevel = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->find();
            if($userlevel['sort'] <= $tolevel['sort']) return $this->json(['status'=>0,'msg'=>'给下级升级需低于推广员当前等级']);
            if($userlevel['team_levelup'] == 0) return $this->json(['status'=>0,'msg'=>'给下级升级功能未开启']);
            
            if($userlevel['team_levelup'] == 1){
                $usenum = Db::name('member_levelup_uesnum')->where('aid',aid)->where('mid',mid)->find();
                if(!$usenum){
                    \app\common\Member::addMemberLevelupNum(aid,mid,$userlevel['team_levelup_data']);
                }else{
                    if($usenum['levelid'] !=$userlevel['levelid']){
                        $olduserlevel = Db::name('member_level')->where('aid',aid)->where('id',$usenum['levelid'])->find();
                        if(!$olduserlevel || $olduserlevel['sort'] < $userlevel['sort']){
                            \app\common\Member::addMemberLevelupNum(aid,mid,$userlevel['team_levelup_data']);
                        }                        
                    }
                }
            }
            $usenum = Db::name('member_levelup_uesnum')->where('aid',aid)->where('mid',mid)->where('levelupid',$levelId)->find();
            if(!$usenum || $usenum['num'] <= 0){
                return $this->json(['status'=>0,'msg'=>'给下级升级数量不足']);
            }
            // if($userlevel['team_levelup_num'] <= 0) return $this->json(['status'=>0,'msg'=>'给下级升级数量不足']);

            // $count = Db::name('member_levelup_order')->where('aid', aid)->where('from_mid', mid)->count();
            // if($count >= $userlevel['team_levelup_num']) {
            //     return $this->json(['status'=>0,'msg'=>'给下级升级数量不足']);
            // }

            $order = [
                'aid' => aid,
                'mid' => $mid,
                'from_mid' => mid,
                'pid' => $this->member['pid'],
                'levelid' => $levelId,
                'title' => '推广员为下级升级，推广员：'.$this->member['nickname'],
                'totalprice' => 0,
                'createtime' => time(),
                'beforelevelid' => $tomember['levelid'],
                'form0' => '类型^_^推广员为下级升级',
                'form1' => '推广员^_^'.$this->member['nickname'],
                'platform' => platform,
                'status' => 2
            ];
            Db::name('member_levelup_order')->insert($order);

            $newlv = Db::name('member_level')->where('aid',aid)->where('id',$levelId)->find();
            if($newlv['yxqdate'] > 0){
                $levelendtime = strtotime(date('Y-m-d')) + 86400 + 86400 * $newlv['yxqdate'];
            }else{
                $levelendtime = 0;
            }
            Db::name('member')->where('id',$mid)->update(['levelid'=>$newlv['id'],'levelstarttime'=>time(),'levelendtime'=>$levelendtime]);
            Db::name('member_levelup_uesnum')->where('aid',aid)->where('mid',mid)->where('levelupid',$levelId)->dec('num',1)->update();
            if($newlv['team_levelup'] == 1){
                \app\common\Member::addMemberLevelupNum(aid,$mid,$newlv['team_levelup_data']);
            }
            

            \app\common\Wechat::updatemembercard(aid,$mid);

            $tmplcontent = [];
            $tmplcontent['first'] = '恭喜您成功升级为'.$newlv['name'];
            $tmplcontent['remark'] = '点击进入查看~';
            $tmplcontent['keyword1'] = $newlv['name']; //会员等级
            $tmplcontent['keyword2'] = '已生效';//审核状态
            \app\common\Wechat::sendtmpl(aid,$mid,'tmpl_uplv',$tmplcontent,m_url('pages/my/usercenter'));

            return $this->json(['status'=>1,'msg'=>'升级成功']);
        }
    }


    //佣金明细
    public function orderYeji(){
        $st = input('param.st');
        $level = Db::name('member_level')->where('id', $this->member['levelid'])->find();
        if(empty($level['tongji_yeji']) || $level['tongji_yeji'] != 1) {
            $datalist = [];
            return $this->json(['status'=>1,'data'=>$datalist]);
        }
        $pernum = 15;
        $pagenum = input('post.pagenum');
        if(!$pagenum) $pagenum = 1;
        $childrenmids = \app\common\Member::getdownmids(aid,mid);
        $childrenmids[] = mid;
        $date = strtotime(date('Y-m-d'));

        for ($i=($pagenum-1)*$pernum;$i<$pagenum*$pernum;$i++) {
            $start = $date-$i*86400;
            $end = $start + 86399;
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','in',$childrenmids];
            $where[] = ['status','in',[1,2,3]];
            $where[] = ['createtime','between',[$start,$end]];
            $num = Db::name('shop_order_goods')->where($where)->sum('num');
            $datalist[] = [
                'i'=>$i,
                'date' => date('Y-m-d',$start),
                'num' => $num
            ];
        }

        if(!$datalist) $datalist = [];
        return $this->json(['status'=>1,'data'=>$datalist]);
    }


	//我的上下级
	public function teamline(){
		
		$userinfo = Db::name('member')->field('id,nickname,headimg,levelid,path')->where('aid',aid)->where('id',mid)->find();
		$userlevel = Db::name('member_level')->where('aid',aid)->where('id',$userinfo['levelid'])->find();

		$pernum = 20;
		$pagenum = input('post.pagenum');
		$keyword = input('post.keyword');
		$where2 = "1=1";
        $query_params = [];//query查询条件
		if($keyword){
            $where2 = "(nickname like :keyword or realname like :keyword2 or tel like :keyword3)";
            $query_params['keyword'] = $query_params['keyword2'] = $query_params['keyword3'] = '%'.$keyword.'%';
        }
		if(!$pagenum) $pagenum = 1;
		if(!mid){
			$datalist = [];
		}else{
			$datalist = Db::name('member')->where('aid',aid)->where('find_in_set('.mid.',path)'.($userinfo['path'] ? ' or id in ('.$userinfo['path'].')' : ''))->whereRaw($where2,$query_params)->order('id')->page($pagenum,$pernum)->select()->toArray();
			//echo Db::getlastsql();
		}

		foreach($datalist as $k=>$v){
			$level = Db::name('member_level')->where('aid',aid)->where('id',$v['levelid'])->find();
            $datalist[$k]['levelname'] = $level['name'];
            $datalist[$k]['levelsort'] = $level['sort'];
			if($userlevel['team_showtel'] == 0){
				$datalist[$k]['tel'] = '';
			}
		}
		
		$rdata = [];
		$rdata['datalist'] = $datalist;
		$rdata['userinfo'] = $userinfo;
		$rdata['userlevel'] = $userlevel;
		return $this->json($rdata);
	}
	//我的同等级会员
	public function sameline(){
		$userinfo = Db::name('member')->field('id,nickname,headimg,levelid,path')->where('aid',aid)->where('id',mid)->find();
		$userlevel = Db::name('member_level')->where('aid',aid)->where('id',$userinfo['levelid'])->find();

		$pernum = 20;
		$pagenum = input('post.pagenum');
		$keyword = input('post.keyword');
		$where2 = "1=1";
        $query_params = [];//query查询条件
		if($keyword){
            $where2 = "(nickname like :keyword or realname like :keyword2 or tel like :keyword3)";
            $query_params['keyword'] = $query_params['keyword2'] = $query_params['keyword3'] = '%'.$keyword.'%';
        } 
		if(!$pagenum) $pagenum = 1;
		if(!mid){
			$datalist = [];
		}else{
			$datalist = Db::name('member')->where('aid',aid)->where('levelid',$userinfo['levelid'])->whereRaw($where2,$query_params)->order('id')->page($pagenum,$pernum)->select()->toArray();
			//echo Db::getlastsql();
		}

		foreach($datalist as $k=>$v){
			$level = Db::name('member_level')->where('aid',aid)->where('id',$v['levelid'])->find();
            $datalist[$k]['levelname'] = $level['name'];
            $datalist[$k]['levelsort'] = $level['sort'];
			if($userlevel['team_showtel'] == 0){
				$datalist[$k]['tel'] = '';
			}
		}
		
		$rdata = [];
		$rdata['datalist'] = $datalist;
		$rdata['userinfo'] = $userinfo;
		$rdata['userlevel'] = $userlevel;
		return $this->json($rdata);
	}

	//佣金排行
	public function commissionrank(){
		$pernum = 10;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
		$sysset = $this->sysset;
		if($sysset['rank_type']){
			$sysset['rank_type'] = explode(',',$sysset['rank_type']);
		}
		if($sysset['rank_status']==0){
			$data = [];
			$data['status'] = 0;
			return $this->json(['status'=>0,'data'=>$data]);
		}
		$rdata = [];
		$rdata['totalcommission'] = $this->member['totalcommission'];
		$rdata['commission'] = $this->member['commission'];
        if(getcustom('yx_queue_free')){
            if($sysset['rank_queue_free_status'] ==1){
                $rdata['commission'] =  dd_money_format($this->member['commission'] + $this->member['totalqueuecommission']);
            }
        }
		$rdata['sysset'] = $sysset;

		$ranktype = input('post.ranktype');
		$where = [];
		
		$ranknum = $sysset['rank_people'];
		$totalpage = ceil($ranknum/$pernum);//2

		$quyu = $pernum;
		if($pernum * $pagenum > $ranknum && $ranknum > 0) $quyu = $ranknum - $pernum * ($pagenum-1);
	
		if($ranknum>0){
			//$quyu = $ranknum%$pernum;
			//if($pagenum==$totalpage && $quyu>0){
			//	$pernum = $quyu;
			//}
			if($pagenum>$totalpage){
				$rdata['data'] = [];
				return $this->json(['status'=>0,'data'=>$rdata]);
			}
		}
		$where[] = ['o.aid','=',aid];
		// $where[] = ['o.status','in','1,2,3'];
		if($sysset['rank_date'] == 2){
			$beginWeek = mktime(0,0,0,date("m"),date("d")-date("w")+1,date("Y"));
			$where[] = ['o.createtime','>=',$beginWeek];
            if(getcustom('yx_queue_free'))$queue_where[] = ['createtime','>=',$beginWeek];
		}
		if($sysset['rank_date'] == 3){
			$date = time();
			$firstDay = strtotime(date('Y-m-01',$date));
			$where[] = ['o.createtime','>=',$firstDay];
            if(getcustom('yx_queue_free'))$queue_where[] = ['createtime','>=',$firstDay];
		}
		if($sysset['rank_date'] == 4){
			$begindate = strtotime('-7 days');
			$enddate =time();
			$where[] = ['o.createtime','between',[$begindate,$enddate]];
            if(getcustom('yx_queue_free'))$queue_where[] = ['createtime','between',[$begindate,$enddate]];
		}

		if($sysset['rank_date'] == 5){
			$begindate = strtotime("-1 month");
			$enddate =time();
			$where[] = ['o.createtime','between',[$begindate,$enddate]];
			if(getcustom('yx_queue_free'))$queue_where[]= ['createtime','between',[$begindate,$enddate]];
		}
		if($ranktype==1){

            // 先跳过
		    $field ='o.*,sum(o.commission) sumcommission,m.headimg,m.nickname';
            // $datalist = Db::name('member')->field($field)->alias('m')->join('member_commission_record o','o.mid=m.id')->where($where)->limit(($pagenum-1)*$pernum,$quyu)->order('sumcommission desc')->group('o.mid')->select()->toArray();

            // 暂时处理
            $where[] = ['o.commission','>',0];

            $datalist = Db::name('member')->field($field)->alias('m')->join('member_commissionlog o','o.mid=m.id')->where($where)->limit(($pagenum-1)*$pernum,$quyu)->order('sumcommission desc')->group('o.mid')->select()->toArray();
            if($datalist){
                foreach ($datalist as $kk => $vv) {
                    $datalist[$kk]['sumcommission'] = number_format($vv['sumcommission'],2);
                }
            }
            if(getcustom('yx_queue_free')){
                if($sysset['rank_queue_free_status'] ==1){
                    $queue_where[] = Db::raw('(totalcommission > 0 or totalqueuecommission > 0)');
                    $memberlist=Db::name('member')->where('aid',aid)->field('id,aid,totalqueuecommission,headimg,nickname')->where($queue_where)->select()->toArray();
                    foreach($memberlist as $key=>$val){
                        $sumcommission = 0+Db::name('member_commissionlog')->where('aid',aid)->where('mid',$val['id'])->sum('commission');
                        $total_commission = dd_money_format( $sumcommission+$val['totalqueuecommission']);
                        if($total_commission <= 0){
                                 unset($memberlist[$key]);
                                 continue;
                        }
                        $memberlist[$key]['sumcommission'] = $total_commission;
                    }
                    //重新排行
                    $sumcommission = [];
                    foreach ($memberlist as $key => $row)
                    {
                        $sumcommission[$key]  = $row['sumcommission'];
                    }
                    array_multisort($sumcommission,SORT_DESC,$memberlist);
                    $datalist = [];
                    foreach($memberlist as $key=>$val){
                        if($key +1 > $ranknum && $ranknum>0){
                            break;
                        }
                        $datalist[] = $val;
                    }
                    if($pagenum >1){
                        $datalist = [];
                    }
                }
            }
			//$datalist = Db::name('member')->where($where)->page($pagenum,$pernum)->order('totalcommission desc')->select()->toArray();



			if(!$datalist) $datalist = [];
			$rdata['data'] = $datalist;
		}elseif($ranktype==2){
            $where[] = ['o.status','in','1,2,3'];
			$datalist = Db::name('member')->field('o.*,sum(o.totalprice) sumtotalprice,m.headimg,m.nickname')->alias('m')->leftJoin('shop_order o','o.mid=m.id')->where($where)->limit(($pagenum-1)*$pernum,$quyu)->order('sumtotalprice desc')->group('o.mid')->select()->toArray();
			foreach($datalist as &$d){
				$d['sumtotalprice'] = number_format($d['sumtotalprice'],2);
			}
			if(!$datalist) $datalist = [];
			$rdata['data'] = $datalist;
		}
		return $this->json(['status'=>1,'data'=>$rdata]);
    }

    //佣金排行
    public function fenhongrank(){
        $pernum = 10;
        $pagenum = input('post.pagenum');
        if(!$pagenum) $pagenum = 1;
        $sysset = $this->sysset;
        if($sysset['fenhong_rank_status']==0){
            $data = [];
            $data['status'] = 0;
            return $this->json(['status'=>0,'data'=>$data]);
        }
        $rdata = [];
//        $rdata['totalcommission'] = $this->member['total_fenhong'];
        $rdata['sysset'] = $sysset;
        $where = [];

        $ranknum = $sysset['fenhong_rank_people'];
        $totalpage = ceil($ranknum/$pernum);//2

        $quyu = $pernum;
        if ($pernum * $pagenum > $ranknum && $ranknum > 0) {
          $quyu = $ranknum - $pernum * ($pagenum - 1);
          $quyu = max($quyu, 0);
        }
        if($ranknum>0){
            //$quyu = $ranknum%$pernum;
            //if($pagenum==$totalpage && $quyu>0){
            //	$pernum = $quyu;
            //}
            if($pagenum>$totalpage){
                $rdata['data'] = [];
                return $this->json(['status'=>0,'data'=>$rdata]);
            }
        }
        $rank_type = explode(',', $sysset['fenhong_rank_type']);
        $where[] = ['o.aid','=',aid];
        if($sysset['fenhong_rank_date'] == 2){
            $beginWeek = mktime(0,0,0,date("m"),date("d")-date("w")+1,date("Y"));
            $where[] = ['o.createtime','>=',$beginWeek];
        }
        if($sysset['fenhong_rank_date'] == 3){
            $date = time();
            $firstDay = strtotime(date('Y-m-01',$date));
            $where[] = ['o.createtime','>=',$firstDay];
        }
        if($sysset['fenhong_rank_date'] == 4){
            $begindate = strtotime('-7 days');
            $enddate =time();
            $where[] = ['o.createtime','between',[$begindate,$enddate]];
        }

        if($sysset['fenhong_rank_date'] == 5){
            $begindate = strtotime("-1 month");
            $enddate =time();
            $where[] = ['o.createtime','between',[$begindate,$enddate]];
        }

        $datalist = [];
        if(count($rank_type) == 2){
          //分红和分销合并
          $commission1 = Db::name('member_commission_record')->where('status', 1)->where('aid', aid)->where('mid', mid)->sum('commission');
          $commission2 = Db::name('member_fenhonglog')->where('aid', aid)->where('mid', mid)->where('status',1)->sum('commission');
          $rdata['totalcommission'] = $commission1+$commission2;
          $aliasql = Db::name('member_commission_record')
            ->where('status', 1)
            ->where('aid', aid)
            ->field('aid, mid, commission')
            ->union(function ($query) {
              $query->field('aid, mid, commission')->name('member_fenhonglog');
            }, 'all')
            ->buildSql();
          $datalist = Db::name('member')->field('o.*,sum(o.commission) sumcommission,m.headimg,m.nickname')->alias('m')->leftJoin($aliasql." o",'o.mid=m.id')->where($where)->limit(($pagenum-1)*$pernum,$quyu)->order('sumcommission desc')->group('o.mid')->select()->toArray();
        }else{
          if(in_array(1, $rank_type)){
            //分红
            $commission1 = 0;
            $commission2 = Db::name('member_fenhonglog')->where('aid', aid)->where('mid', mid)->where('status',1)->sum('commission');
            $rdata['totalcommission'] = $commission1+$commission2;
            $datalist = Db::name('member')->field('o.*,sum(o.commission) sumcommission,m.headimg,m.nickname')->alias('m')->leftJoin('member_fenhonglog o','o.mid=m.id')->where($where)->limit(($pagenum-1)*$pernum,$quyu)->order('sumcommission desc')->group('o.mid')->select()->toArray();
          }
          if(in_array(2, $rank_type)){
            //分销
            $commission1 = Db::name('member_commission_record')->where('status', 1)->where('aid', aid)->where('mid', mid)->sum('commission');
            $commission2 = 0;
            $rdata['totalcommission'] = $commission1+$commission2;
            $datalist = Db::name('member')->field('o.*,sum(o.commission) sumcommission,m.headimg,m.nickname')->alias('m')->leftJoin('member_commission_record o','o.mid=m.id')->where($where)->limit(($pagenum-1)*$pernum,$quyu)->order('sumcommission desc')->group('o.mid')->select()->toArray();
          }
        }



        if(!$datalist) $datalist = [];
        $moeny_weishu = 2;
        if(getcustom('fenhong_money_weishu')){
            $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
        }
        foreach ($datalist as $key=>$val){
            $datalist[$key]['commission'] = dd_money_format($val['commission'],$moeny_weishu);
            $datalist[$key]['sumcommission'] = dd_money_format($val['sumcommission'],$moeny_weishu);
        }
        $rdata['totalcommission'] =   dd_money_format($rdata['totalcommission'],$moeny_weishu);
        $rdata['data'] = $datalist;
        return $this->json(['status'=>1,'data'=>$rdata]);
    }

    //团队业绩 
    public function teamyjrank(){
	    if(getcustom('team_yeji_ranking')){
            $pernum = 10;
            $pagenum = input('post.pagenum');
            if(!$pagenum) $pagenum = 1;
            $sysset = $this->sysset;
            if($sysset['teamyj_rank_status']==0){
                $data = [];
                $data['status'] = 0;
                return $this->json(['status'=>0,'data'=>$data]);
            }
            $my_team_yeji_total = 0;
            $downmids = \app\common\Member::getteammids(aid,mid);
            if($downmids){
                $yejiwhere = [];
                $yejiwhere[] = ['status','in','1,2,3'];
                $yejiwhere[] = ['mid','in',$downmids];
                $my_team_yeji_total = Db::name('shop_order_goods')->where('aid',aid)->where('mid','in',$downmids)->where($yejiwhere)->sum('real_totalprice');
            }
            //包含自己
            $my_self_yeji =0+ Db::name('shop_order_goods')->where('aid',aid)->where('status','in','1,2,3')->where('mid',mid)->sum('real_totalprice');
            $my_team_yeji_total  =dd_money_format($my_team_yeji_total +$my_self_yeji);
            if(getcustom('yx_team_yeji_maidan')){
                $include_maidan_yeji = Db::name('team_yeji_set')->where('aid',aid)->value('include_maidan_yeji');
                if($include_maidan_yeji) {
                    $maidan_where = [];
                    $maidan_where[] = ['aid', '=', aid];
                    $maidan_where[] = ['status', '=', 1];
                    $maidan_where[] = ['mid', 'in', $downmids];
                    $maidan_yeji = 0 + Db::name('maidan_order')->where($maidan_where)->sum('paymoney');
                    $my_team_yeji_total = dd_money_format( $maidan_yeji+$my_team_yeji_total);
                }
            }

            $rdata = [];
            $rdata['total_yeji'] = $my_team_yeji_total;
            $rdata['sysset'] = $sysset;
            
            $ranknum = $sysset['teamyj_rank_people'];
            $totalpage = ceil($ranknum/$pernum);//2
            $quyu = $pernum;
            if($pernum * $pagenum > $ranknum) $quyu = $ranknum - $pernum * ($pagenum-1);
            if($ranknum>0){
                if($pagenum>$totalpage){
                    $rdata['data'] = [];
                    return $this->json(['status'=>0,'data'=>$rdata]);
                }
            }
            $where = [];
            $where[] = ['aid','=',aid];
            $datalist = Db::name('member')
                ->field('id,nickname,headimg')
                ->where($where)->select()->toArray();
            if(!$datalist) $datalist = [];
            $yejirank = [];
            $newdatalist = [];
            $i = 0;
            foreach($datalist as $key=>$val){
                $haveteam = Db::name('member')->where('aid',aid)->where('pid',$val['id'])->value('id');
                if(!$haveteam)continue;
                $team_yeji_total = 0;
                $downmids = \app\common\Member::getteammids(aid,$val['id']);
                if($downmids){
                    $yejiwhere = [];
                    $yejiwhere[] = ['aid','=',aid];
                    $yejiwhere[] = ['status','in','1,2,3'];
                    $yejiwhere[] = ['mid','in',$downmids];
                    $team_yeji_total = Db::name('shop_order_goods')->where($yejiwhere)->sum('real_totalprice');
                }
                //自身业绩
                $self_yeji =0+ Db::name('shop_order_goods')->where('aid',aid)->where('status','in','1,2,3')->where('mid',$val['id'])->sum('real_totalprice');
                $team_yeji_total  =dd_money_format($team_yeji_total +$self_yeji);
                if(getcustom('yx_team_yeji_maidan')){
                    $include_maidan_yeji = Db::name('team_yeji_set')->where('aid',aid)->value('include_maidan_yeji');
                    if($include_maidan_yeji) {
                        $maidan_where = [];
                        $maidan_where[] = ['aid', '=', aid];
                        $maidan_where[] = ['status', '=', 1];
                        $maidan_where[] = ['mid', 'in', $downmids];
                        $maidan_yeji = 0 + Db::name('maidan_order')->where($maidan_where)->sum('paymoney');
                        $team_yeji_total = dd_money_format( $maidan_yeji+$team_yeji_total);
                    }
                }
                if(getcustom('yx_team_yeji_cashdesk')){
                    $include_cashdesk_yeji = Db::name('admin_set')->where('aid',aid)->value('include_cashdesk_yeji');
                    if($include_cashdesk_yeji) {
                        $cashdesk_where = [];
                        $cashdesk_where[] = ['aid', '=', aid];
                        $cashdesk_where[] = ['status', '=', 1];
                        $cashdesk_where[] = ['mid', 'in', $downmids];
                        $cashdesk_yeji = 0 + Db::name('cashier_order')->where($cashdesk_where)->sum('totalprice');
                        $team_yeji_total = dd_money_format( $cashdesk_yeji+$team_yeji_total);
                    }
                }
                if(getcustom('scoreshop_teamyeji')){
                    //是否统计积分团队业绩
                    $jointeamyeji = Db::name('scoreshop_sysset')->where('aid',aid)->value('jointeamyeji');
                    if($jointeamyeji && $jointeamyeji == 1){
                        $scoreshop_where = [];
                        $scoreshop_where[] = ['aid', '=', aid];
                        $scoreshop_where[] = ['mid', 'in', $downmids];
                        $scoreshop_where[] = ['status','in','1,2,3'];
                        $scoreshop_teamyeji = Db::name('scoreshop_order_goods')->where($scoreshop_where)->sum('totalmoney');
                        $team_yeji_total += $scoreshop_teamyeji;
                        $team_yeji_total = dd_money_format($team_yeji_total);
                    }
                }

                if($team_yeji_total <=0)continue;
                $val['totalyeji'] = $team_yeji_total;
                $newdatalist[$i] =$val;
                $yejirank[$i] =  $team_yeji_total;
                $i++;
            }
            //排序
            array_multisort($yejirank,SORT_DESC,$newdatalist);
          
            //排序后 限制条数
            if($ranknum > 0){
                foreach ($newdatalist as $ak=>$av){
                    if($ak +1 >$ranknum ){
                        unset($newdatalist[$ak]);
                    }
                }
            }
            $rdata['data'] = $newdatalist;
            return $this->json(['status'=>1,'data'=>$rdata]);
        }
    }
    
    public function myyeji()
    {
        if(getcustom('member_show_buymoney')){
            $yejiwhere = [];
            $yejiwhere[] = ['status','in','1,2,3'];
            $starttime = strtotime(input('param.start_time'));
            $endtime = strtotime(input('param.end_time')) + 86399;
            $yejiwhere[] = ['createtime','between',[$starttime,$endtime]];
            $rdata['yeji'] = Db::name('shop_order_goods')->where('aid',aid)->where('mid',mid)->where($yejiwhere)->sum('real_totalprice');
        }

        return $this->json(['status'=>1,'data'=>$rdata]);
    }

    public function teamyeji()
    {
        if(getcustom('member_show_teamyeji')){
            $yejiwhere = [];
            $yejiwhere[] = ['status','in','1,2,3'];
            $starttime = strtotime(input('param.start_time'));
            $endtime = strtotime(input('param.end_time')) + 86399;
            $yejiwhere[] = ['createtime','between',[$starttime,$endtime]];
            $downmids = \app\common\Member::getteammids(aid,mid);
            $rdata['yeji'] = Db::name('shop_order_goods')->where('aid',aid)->where('mid','in',$downmids)->where($yejiwhere)->sum('real_totalprice');
        }

        return $this->json(['status'=>1,'data'=>$rdata]);
    }
    //报单佣金冻结记录
    public function baodanCommissionLog(){
	    if (getcustom('product_baodan')){
            $pernum = 20;
            $pagenum = input('post.pagenum');
            if(!$pagenum) $pagenum = 1;
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $datalist = Db::name('member_baodan_freeze_log')->page($pagenum,$pernum)->order('id desc')->where($where)->select()->toArray();
            if(!$datalist) $datalist = [];
            $moeny_weishu =2;
            if(getcustom('fenhong_money_weishu')){
                $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
            }
            $moeny_weishu =$moeny_weishu?$moeny_weishu:2;
            foreach ($datalist as $key=>$val){
                $datalist[$key]['commission'] = dd_money_format($val['commission'],$moeny_weishu);
                $datalist[$key]['after'] = dd_money_format($val['after'],$moeny_weishu);
                $datalist[$key]['createtime'] = date('Y-m-d H:i:s',$val['createtime']);
            }
            return $this->json(['status'=>1,'data'=>$datalist]);
        }
    }


    //商户分享海报
    public function business_poster(){
        $platform = platform;
        $bid = input('bid')?:0;
        $page = '/pagesExt/business/index';
        $scene = 'pid_'.$this->member['id'].'-id_'.$bid;
        if(getcustom('member_business')){
            //商户注册会员
            $scene .= '-regbid_'.$bid;
        }
        //if($platform == 'mp' || $platform == 'h5' || $platform == 'app'){
        //	$page = PRE_URL .'/h5/'.aid.'.html#'. $page;
        //}

        $member = $this->member;
        if(input('param.posterid')){
            $posterset = Db::name('business_set_poster')
                ->where('aid',aid)
                ->where('bid',$bid)
                ->where('type','index')
                ->where('platform',$platform)->where('id',input('param.posterid'))->find();
        }else{
            $posterset = Db::name('business_set_poster')
                ->where('aid',aid)
                ->where('bid',$bid)
                ->where('type','index')->where('platform',$platform)->order('id')->find();
        }
        $sysset = Db::name('admin_set')->field('id,name,logo,desc')->where('aid',aid)->find();
        if(getcustom('app_reg') && $platform == 'app'){
            $page = 'pages/index/reg';
            $scene.='-fromapp_1';
        }
        $poster = $posterset['poster'];
        $business = Db::name('business')->where('aid',aid)->where('id',$bid)->find();
        $pic = '';
        if($business && $business['pics']){
            $pic = explode(',',$business['pics'])[0];
        }
        //if(!$poster){
            $textReplaceArr = [
                '[商户LOGO]'=>$business['logo'],
                '[商户名称]'=>$business['name'],
                '[商户电话]'=>$business['tel'],
                '[商城名称]'=>$sysset['name'],
                '[商户图片]'=>$pic
            ];
            $poster = $this->_getposter(aid,$bid,$platform,$posterset['content'],$page,$scene,$textReplaceArr);
            Db::name('business_set_poster')->where('id',$posterset['id'])->update(['poster'=>$poster]);
        //}

        $posterlist = Db::name('business_set_poster')->field('id')
            ->where('aid',aid)
            ->where('bid',$bid)
            ->where('type','index')->where('platform',$platform)->order('id')->select()->toArray();

        $rdata = [];
        $rdata['poster'] = $poster;
        $rdata['guize'] = $posterset['guize'];
        $rdata['posterid'] = $posterset['id'];
        $rdata['posterlist'] = $posterlist;
        $rdata['postercount'] = count($posterlist);
        $rdata['business_name'] = $business['name'];
        $rdata['business_logo'] = $business['logo'];
        return $this->json($rdata);
    }

    public function shortvideolog(){
    	if(getcustom('yx_shortvideo_jindubag')){
	    	if(request()->isPost()){
	    		$mid = input('param.mid')?input('param.mid/d'):0;
	    		if(!$mid){
	    			return $this->json(['status'=>0,'msg'=>'请选择要查看的下级']);
	    		}
	    		$member = Db::name('member')->where('id',$mid)->where('aid',aid)->field('id,path')->find();
	    		if(!$member || !$member['path']){
	    			return $this->json(['status'=>0,'msg'=>'下级不存在']);
	    		}
	    		//查询是否是他的下级
	    		$patharr = explode(',',$member['path']);
	    		if(!$patharr){
	    			return $this->json(['status'=>0,'msg'=>'下级不存在']);
	    		}
	    		if(!in_array(mid, $patharr)){
	    			return $this->json(['status'=>0,'msg'=>'下级不存在']);
	    		}
		        $where = [];
		        $where[] = ['log.aid','=',aid];
		        $where[] = ['log.mid','=',$mid];
		        $where[] = ['log.is_del','=',0];
		        $pernum = 20;
		        $pagenum = input('post.pagenum');
		        if(!$pagenum) $pagenum = 1;
		        $field = "log.id,log.num,log.vid,log.jindu,video.coverimg as pic,video.name";

		        $datalist = Db::name('shortvideo_looklog')
		        	->alias('log')
		        	->join('shortvideo video','video.id=log.vid')
		        	->where($where)
		        	->field($field)
		        	->page($pagenum,$pernum)
		        	->order('log.id desc')
		        	->select()
		        	->toArray();
		        if(!$datalist) $datalist = array();

		        $rdata = [];
		        $rdata['status']   = 1;
		        $rdata['datalist'] = $datalist;
		        return $this->json($rdata);
	    	}
    	}
    }
	//推荐商家
	public function tjblist(){
		$level =  Db::name('member_level')->where('id',$this->member['levelid'])->find();
		$pernum = 10;
		$pagenum = input('post.pagenum');
		if(!$pagenum) $pagenum = 1;
        $where_area = [];
        //区域筛选店铺
        if(getcustom('business_agent_search')) {
            $province = input('province');
            $city = input('city');
            $district = input('district');
            if ($province && $province!='请选择') {
                $where_area[] = ['b.province','=',$province];
            }
            if ($city && $city!='请选择') {
                $where_area[] = ['b.city','=',$city];
            }
            if ($district && $district!='请选择') {
                $where_area[] = ['b.district','=',$district];
            }
        }

        $query = Db::name('member')->alias('m')->field('m.pid,b.*')->where('m.aid',aid)->where('m.pid',mid)->where('b.status',1)
            ->join('business b','m.id=b.mid')->where($where_area);

        //独立推荐关系
        if (getcustom('business_agent_referrer_independent')) {
            $independent = Db::name('business_sysset')->where('aid',aid)->value('referrer_independent') ?: 0;
            if($independent){
                $independentQuery = Db::name('business_referrer_independent')
                    ->alias('bri')
                    ->field('m.pid,b.*')
                    ->join('business b', 'b.id = bri.bid')
                    ->join('member m', 'm.id = bri.mid')
                    ->where('bri.aid', aid)
                    ->where('bri.mid', mid)
                    ->where('b.recommend_type', 2)
                    ->where($where_area)
                    ->where('b.status', 1);

                $baseSql = $query->field('b.id as bid')->where('b.recommend_type',1)->buildSql();
                $independentSql = $independentQuery->field('b.id as bid')->buildSql();

                $datalist = Db::query("SELECT * FROM (SELECT base.* FROM ($baseSql) as base UNION ALL SELECT independent.* FROM ($independentSql) as independent ) AS combined GROUP BY bid ORDER BY bid DESC LIMIT " . ($pagenum-1)*$pernum . "," . $pernum);
                $countQuery = Db::query("SELECT COUNT(*) as total FROM (SELECT bid FROM ($baseSql) as base UNION ALL SELECT bid FROM ($independentSql) as independent ) AS combined GROUP BY bid");
                $count = count($countQuery);

//                return $this->json(['status' => 1, 'data' => $datalist ?: [], 'count' => $count ?: 0]);
            }
        }
        if(!getcustom('business_agent_referrer_independent')){
            $count = $query->count();
            $datalist = $query->order('b.id desc')->page($pagenum,$pernum)->select()->toArray();
        }
        $show_order_price = 0;

        if(getcustom('business_agent_count') || getcustom('business_agent_search')){
            //日期筛选店铺业绩
            $rangeType = input('rangeType');
            if($rangeType==1 && input('year')){
                $year = input('year');
                $s_time = strtotime($year.'-01-01');
                $e_time = strtotime($year.'-12-31');
            }elseif ($rangeType==2){
                $month = input('month');
                $s_time = strtotime($month.'-01');
                $e_time = strtotime(date('Y-m-t 23:59:59',$s_time));
            }elseif ($rangeType==3){
                $start_date = input('start_date');
                $end_date = input('end_date');
                $s_time = strtotime($start_date);
                $e_time = strtotime($end_date);
            }
            $where_time = [];
            if($rangeType && $s_time){
                $where_time[] = ['createtime','>=',$s_time];
                $where_time[] = ['createtime','<=',$e_time];
            }
            $show_order_price = 1;
            foreach($datalist as $k=>$v){
                //统计商家营业额
                $shop_order_month = 0+ Db::name('shop_order_goods')
                        ->where('aid',aid)
                        ->where('bid',$v['id'])
                        ->where('status','in',[1,2,3])
                        ->where('refund_num','=',0)
                        ->where($where_time)
                        ->sum('real_totalprice');
                $maidan_order_month = 0+ Db::name('maidan_order')
                        ->where('aid',aid)
                        ->where('bid',$v['id'])
                        ->where('status',1)
                        ->where($where_time)
                        ->sum('paymoney');
                $order_price_total = bcadd($shop_order_month,$maidan_order_month,2);
                $datalist[$k]['order_price_total'] = $order_price_total;
            }
        }
        $show_search = 0;
        if(getcustom('business_agent_search')){
            //展示店铺搜索条件
            $show_search = 1;
        }
		return $this->json(['status'=>1,'data'=>$datalist?:[],'count'=>$count?:0,'show_order_price'=>$show_order_price,'show_search'=>$show_search]);
	}

    public function priceRate(){
        if(getcustom('product_price_rate')){
            if(request()->isPost()) {
                $rate = input('param.rate') ? input('param.rate') : 1;
                Db::name('member')->where('aid',aid)->where('id',mid)->update(['price_rate'=>$rate]);
                $posterset = Db::name('admin_set_poster')->where('aid',aid)->where('type','index')->where('platform',platform)->order('id')->find();
                Db::name('member_poster')->where('aid',aid)->where(['mid'=>mid,'posterid'=>$posterset['id']])->delete();
                return $this->json(['status'=>1,'msg'=>'设置成功']);
            }
            $member = Db::name('member')->field('id,pid,price_rate')->where('aid',aid)->where('id',mid)->find();
            return $this->json(['status'=>1,'price_rate'=>$member['price_rate']]);
        }
    }

    public function memberPriceRate(){
        if(getcustom('member_level_price_rate')){
            if(request()->isPost()) {
                $levelid = input('param.levelid');
                Db::name('member')->where('aid',aid)->where('id',mid)->update(['levelid_price_rate'=>$levelid]);
                return $this->json(['status'=>1,'msg'=>'设置成功']);
            }
            $levellist = Db::name('member_level')->where('aid',aid)->order('sort asc,id desc')->field('id,name,price_rate,sort')->select()->toArray();
            foreach ($levellist as $k => $item){
                if($this->member['levelid'] == $item['id']) $levelsort = $item['sort'];
                if($levelsort && $item['sort'] > 1 && $item['sort'] >= $levelsort) unset($levellist[$k]);
            }

            return $this->json(['status'=>1,'levellist'=>array_values($levellist),'levelid_price_rate'=>$this->member['levelid_price_rate']]);
        }
    }

    //团队收益记录
    public function teamfenhong_shouyi(){
        if (getcustom('teamfenhong_shouyi')){
            $pernum = 20;
            $pagenum = input('post.pagenum');
            if(!$pagenum) $pagenum = 1;
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $where[] = ['type','=','teamfenhong_shouyi'];
            $where[] = ['status','=',1];
            $datalist = Db::name('member_fenhonglog')->page($pagenum,$pernum)->order('id desc')->where($where)->select()->toArray();
            if(!$datalist) $datalist = [];
            $moeny_weishu =2;
            if(getcustom('fenhong_money_weishu')){
                $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
            }
            $moeny_weishu =$moeny_weishu?$moeny_weishu:2;
            foreach ($datalist as $key=>$val){
                $datalist[$key]['commission'] = dd_money_format($val['commission'],$moeny_weishu);
                $datalist[$key]['createtime'] = date('Y-m-d H:i:s',$val['createtime']);
            }
            return $this->json(['status'=>1,'data'=>$datalist]);
        }
    }
    
    //奖励金 提现
    public function gongxianWithdraw(){
	    if(getcustom('product_bonus_pool')){
            $field = 'withdraw_autotransfer,comwithdraw,comwithdrawmin,comwithdrawfee,comwithdrawbl,comwithdrawdate,withdraw_weixin,withdraw_aliaccount,withdraw_bankcard,withdraw_desc';

            $set = Db::name('admin_set')->where('aid',aid)->field($field)->find();
            if(request()->isPost()){
                $post = input('post.');
                if($set['comwithdraw'] == 0){
                    return $this->json(['status'=>0,'msg'=>'提现功能未开启']);
                }
                $memberlevel = Db::name('member_level')->where('id',$this->member['levelid'])->find();
                if($memberlevel && $memberlevel['comwithdraw'] == 0){
                    return $this->json(['status'=>0,'msg'=>'提现功能未开启']);
                }

                if($post['paytype']=='支付宝'){
                    if(!$this->member['aliaccount'] || !$this->member['aliaccountname']){
                        return $this->json(['status'=>0,'msg'=>'请先设置支付宝账号']);
                    }
                }

                if($post['paytype']=='支付宝' && $this->member['aliaccount']==''){
                    return $this->json(['status'=>0,'msg'=>'请先设置支付宝账号']);
                }
                if($post['paytype']=='银行卡' && ($this->member['bankname']==''||$this->member['bankcarduser']==''||$this->member['bankcardnum']=='')){
                    return $this->json(['status'=>0,'msg'=>'请先设置完整银行卡信息']);
                }

                $money = $post['money'];
                if($money<=0 || $money < $set['comwithdrawmin']){
                    return $this->json(['status'=>0,'msg'=>'提现金额必须大于'.($set['comwithdrawmin']?$set['comwithdrawmin']:0)]);
                }
                if($money > $this->member['bonus_pool_money']){
                    return $this->json(['status'=>0,'msg'=>'可提现'.t('贡献值').'不足']);
                }

                //验证小数点后两位
                $money_arr = explode('.',$money);
                if($money_arr && $money_arr[1]){
                    $dot_len = strlen($money_arr[1]);
                    if($dot_len>2){
                        return $this->json(['status'=>0,'msg'=>'提现金额最小位数为小数点后两位']);
                    }
                }

                $ordernum = date('ymdHis').aid.rand(1000,9999);
                $record['aid'] = aid;
                $record['mid'] = mid;
                $record['createtime']= time();
                $real_money =  $money;
                if($post['paytype'] != '余额' ){
                    $real_money = dd_money_format($money*(1-$set['comwithdrawfee']*0.01)) ;
                }
                if($real_money <= 0) {
                    return $this->json(['status'=>0,'msg'=>'提现金额有误']);
                }
                $record['money'] = $real_money;
                $record['txmoney'] = $money;
                if($post['paytype']=='支付宝'){
                    $record['aliaccountname'] = $this->member['aliaccountname'];
                    $record['aliaccount'] = $this->member['aliaccount'];
                }
                if($post['paytype']=='银行卡'){
                    $record['bankname'] = $this->member['bankname'].($this->member['bankaddress'] ? ' '.$this->member['bankaddress'] : '');;
                    $record['bankcarduser'] = $this->member['bankcarduser'];
                    $record['bankcardnum'] = $this->member['bankcardnum'];
                }
                $record['ordernum'] = $ordernum;
                $record['paytype'] = $post['paytype'];
                $record['platform'] = platform;
                $recordid = Db::name('member_bonus_pool_withdrawlog')->insertGetId($record);
                
                $bonus_pool_money = dd_money_format($this->member['bonus_pool_money'] -$money );
                $log = [
                    'aid' =>aid,
                    'mid' =>mid,
                    'frommid' => 0,
                    'commission' => -$money,
                    'after' => $bonus_pool_money,
                    'createtime' => time(),
                    'remark' => t('贡献值').'提现'
                ];
                Db::name('member_bonus_pool_log') ->insert($log);
                //查找
                $bonus_pool_max_money = dd_money_format($this->member['bonus_pool_max_money'] -$money);
                $bonus_pool_max_money = $bonus_pool_max_money<=0?0:$bonus_pool_max_money;
                Db::name('member')->where('id',mid)->update(['bonus_pool_money' => $bonus_pool_money,'bonus_pool_max_money' => $bonus_pool_max_money]);
                
                
                $tmplcontent = array();
                $tmplcontent['first'] = '有客户申请'.t('贡献值').'提现';
                $tmplcontent['remark'] = '点击进入查看~';
                $tmplcontent['keyword1'] = $this->member['nickname'];
                $tmplcontent['keyword2'] = date('Y-m-d H:i');
                $tmplcontent['keyword3'] = $money.'元';
                $tmplcontent['keyword4'] = $post['paytype'];
                \app\common\Wechat::sendhttmpl(aid,0,'tmpl_withdraw',$tmplcontent,m_url('admin/finance/comwithdrawlog'));

                $tmplcontent = [];
                $tmplcontent['name3'] = $this->member['nickname'];
                $tmplcontent['amount1'] = $money.'元';
                $tmplcontent['date2'] = date('Y-m-d H:i');
                $tmplcontent['thing4'] = '提现到'.$post['paytype'];
                \app\common\Wechat::sendhtwxtmpl(aid,0,'tmpl_withdraw',$tmplcontent,'admin/finance/comwithdrawlog');
                if($post['paytype'] == '余额' ){
                    $paymoney = dd_money_format($record['money']);
                    \app\common\Member::addmoney(aid,mid,$paymoney,t('贡献值').'提现');
                    Db::name('member_bonus_pool_withdrawlog')->where('aid',aid)->where('id',$recordid)->update(['status'=>3,'paytime'=>time()]);
                    //提现成功通知
                    $tmplcontent = [];
                    $tmplcontent['first'] = '您的提现申请已打款，请留意查收';
                    $tmplcontent['remark'] = '请点击查看详情~';
                    $tmplcontent['money'] = (string) round($record['money'],2);
                    $tmplcontent['timet'] = date('Y-m-d H:i',$record['createtime']);
                    $tempconNew = [];
                    $tempconNew['amount2'] = (string) $record['money'];//提现金额
                    $tempconNew['time3'] = date('Y-m-d H:i',$record['createtime']);//提现时间
                    \app\common\Wechat::sendtmpl(aid,$record['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'),$tempconNew);
                    //订阅消息
                    $tmplcontent = [];
                    $tmplcontent['amount1'] = $record['money'];
                    $tmplcontent['thing3'] = '余额打款';
                    $tmplcontent['time5'] = date('Y-m-d H:i');

                    $tmplcontentnew = [];
                    $tmplcontentnew['amount3'] = $record['money'];
                    $tmplcontentnew['phrase9'] = '余额打款';
                    $tmplcontentnew['date8'] = date('Y-m-d H:i');
                    \app\common\Wechat::sendwxtmpl(aid,$record['mid'],'tmpl_tixiansuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
                    //短信通知
                    if($this->member['tel']){
                        \app\common\Sms::send(aid,$this->member['tel'],'tmpl_tixiansuccess',['money'=>$record['money']]);
                    }
                    return json(['status'=>1,'msg'=>'提现成功']);
                }
                if($set['withdraw_autotransfer'] && ($post['paytype'] == '微信钱包' || $post['paytype'] == '银行卡')){
                    if($set['comwithdrawbl'] > 0 && $set['comwithdrawbl'] < 100){
                        $paymoney = round($record['money'] * $set['comwithdrawbl'] * 0.01,2);
                        $tomoney = round($record['money'] - $paymoney,2);
                    }else{
                        $paymoney = $record['money'];
                        $tomoney = 0;
                    }
                    $paymoney = dd_money_format($paymoney);
                    $rs = \app\common\Wxpay::transfers(aid,mid,$paymoney,$record['ordernum'],platform,t('佣金').'提现');
                    if($rs['status']==0){
                        return json(['status'=>1,'msg'=>'提交成功,请等待打款']);
                    }else{
                        if($tomoney > 0){
                            \app\common\Member::addmoney(aid,mid,$tomoney,t('佣金').'提现');
                        }
                        Db::name('member_bonus_pool_withdrawlog')->where('aid',aid)->where('id',$recordid)->update(['status'=>3,'paytime'=>time(),'paynum'=>$rs['resp']['payment_no']]);
                        
                        //提现成功通知
                        $tmplcontent = [];
                        $tmplcontent['first'] = '您的提现申请已打款，请留意查收';
                        $tmplcontent['remark'] = '请点击查看详情~';
                        $tmplcontent['money'] = (string) round($record['money'],2);
                        $tmplcontent['timet'] = date('Y-m-d H:i',$record['createtime']);
                        $tempconNew = [];
                        $tempconNew['amount2'] = (string) $record['money'];//提现金额
                        $tempconNew['time3'] = date('Y-m-d H:i',$record['createtime']);//提现时间
                        \app\common\Wechat::sendtmpl(aid,$record['mid'],'tmpl_tixiansuccess',$tmplcontent,m_url('pages/my/usercenter'),$tempconNew);
                        //订阅消息
                        $tmplcontent = [];
                        $tmplcontent['amount1'] = $record['money'];
                        $tmplcontent['thing3'] = '微信打款';
                        $tmplcontent['time5'] = date('Y-m-d H:i');

                        $tmplcontentnew = [];
                        $tmplcontentnew['amount3'] = $record['money'];
                        $tmplcontentnew['phrase9'] = '微信打款';
                        $tmplcontentnew['date8'] = date('Y-m-d H:i');
                        \app\common\Wechat::sendwxtmpl(aid,$record['mid'],'tmpl_tixiansuccess',$tmplcontentnew,'pages/my/usercenter',$tmplcontent);
                        //短信通知
                        if($this->member['tel']){
                            \app\common\Sms::send(aid,$this->member['tel'],'tmpl_tixiansuccess',['money'=>$record['money']]);
                        }
                        return json(['status'=>1,'msg'=>$rs['msg']]);
                    }
                }
                return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
            }

            if($set['comwithdraw'] == 0){
                return $this->json(['status'=>-4,'msg'=>t('佣金').'提现功能未开启']);
            }
            $memberlevel = Db::name('member_level')->where('id',$this->member['levelid'])->find();
            if($memberlevel && $memberlevel['comwithdraw'] == 0){
                return $this->json(['status'=>-4,'msg'=>t('佣金').'提现未开启','url'=>'/activity/commission/index']);
            }
            //订阅消息
            $wx_tmplset = Db::name('wx_tmplset')->where('aid',aid)->find();
            $tmplids = [];
            if($wx_tmplset['tmpl_tixiansuccess_new']){
                $tmplids[] = $wx_tmplset['tmpl_tixiansuccess_new'];
            }elseif($wx_tmplset['tmpl_tixiansuccess']){
                $tmplids[] = $wx_tmplset['tmpl_tixiansuccess'];
            }
            if($wx_tmplset['tmpl_tixianerror_new']){
                $tmplids[] = $wx_tmplset['tmpl_tixianerror_new'];
            }elseif($wx_tmplset['tmpl_tixianerror']){
                $tmplids[] = $wx_tmplset['tmpl_tixianerror'];
            }
            $rdata = [];
            $field = 'id,headimg,nickname,money,score,commission,aliaccount,bankname,bankcarduser,bankcardnum,bonus_pool_money,bonus_pool_max_money';
            $show_cash_count = 0;
        
            $userinfo = Db::name('member')->where('id',mid)->field($field)->find();
            $userinfo['commission'] = dd_money_format($userinfo['bonus_pool_money'],2);
            $userinfo['show_cash_count'] = $show_cash_count;
            $rdata['userinfo'] =  $userinfo ;
            $rdata['sysset'] = $set;
            $rdata['tmplids'] = $tmplids;

            return $this->json($rdata); 
        }
    }

    //佣金明细
    public function gongxiancommissionlog(){
        if(getcustom('product_bonus_pool')){
            $st = input('param.st');
            $type = input('param.type');
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            if(input('param.keyword')) $where[] = ['remark', 'like', '%'.input('param.keyword').'%'];
            $pernum = 20;
            $pagenum = input('post.pagenum');
            if(!$pagenum) $pagenum = 1;
            if($st ==1){//提现记录
                $datalist = Db::name('member_bonus_pool_withdrawlog')->field("id,money,txmoney,`status`,from_unixtime(createtime)createtime")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            }else{ //佣金明细
                $moeny_weishu =2;
                $datalist = Db::name('member_bonus_pool_log')->field("id,commission money,`after`,from_unixtime(createtime)createtime,remark")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
                foreach ($datalist as $key=>$val){
                    $datalist[$key]['commission'] = dd_money_format($val['commission'],$moeny_weishu);
                    $datalist[$key]['money'] = dd_money_format($val['money'],$moeny_weishu);
                    $datalist[$key]['after'] = dd_money_format($val['after'],$moeny_weishu);
                }
            }
            if(!$datalist) $datalist = [];
            return $this->json(['status'=>1,'data'=>$datalist]);
        }
    }
    
    //区域代理排行
    public function regionagentrank(){
	    if(getcustom('areafenhong_region_ranking')){
            $pernum = 10;
            $pagenum = 1;
            
//            if(!$pagenum) $pagenum = 1;
            $sysset = $this->sysset;
            if($sysset['region_rank_status']==0){
                $data = [];
                $data['status'] = 0;
                return $this->json(['status'=>0,'data'=>$data,'msg' =>'排行榜未开启']);
            }
            if(!$sysset['region_show_type']){
                return $this->json(['status'=>0,'data'=>[],'msg' =>'排行榜未开启']);
            }
            $gettj = explode(',',$sysset['region_rank_levelids']);
            if(!in_array('-1',$gettj) && !in_array($this->member['levelid'],$gettj)) { //不是所有人
                $data = [];
                $data['status'] = 0;
                return $this->json(['status'=>0,'data'=>$data,'msg' =>'您没有查看权限']);
            }
            
            $rdata = [];
            $rdata['sysset'] = $sysset;
    
            $ranktype = input('post.ranktype');

            $ranknum = $sysset['region_rank_people'];
            $totalpage = ceil($ranknum/$pernum);//2
    
            $quyu = $pernum;
            if($pernum * $pagenum > $ranknum) $quyu = $ranknum - $pernum * ($pagenum-1);
            if($ranknum>0){
                if($pagenum>$totalpage){
                    $rdata['data'] = [];
                    return $this->json(['status'=>0,'data'=>$rdata]);
                }
            }
            $sort_type = input('param.sorttype',1);
            $rdata['data'] = [];
            if(in_array($ranktype,[0,1,2])){
                $province = [];
                $city = [];
                $area =[];
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['area2','<>',''];
                $where[] = Db::raw(" area2 IS NOT NULL and  area2 !=',,'");
                $area2 =  Db::name('shop_order')->where($where)->group('area2')->field('area2')->select()->toArray();
                foreach($area2 as $key=>$val){
                    $area2data = explode(',',$val['area2']);
                    if($area2data[0]){
                        $province[] = $area2data[0];
                    }
                    if($area2data[1]){
                        $city[] = $area2data[1];
                    }
                    if($area2data[2]){
                        $area[] = $area2data[2];
                    }
                }
                $province =  array_unique($province);
                $city =  array_unique($city);
                $area =  array_unique($area);
            }
            $start_time = '';
            $end_time = '';
            if($sysset['region_ctime']){
                $ctime = explode('~',$sysset['region_ctime']);
                $start_time = strtotime($ctime[0]);
                $end_time = strtotime($ctime[1]);
            }
           
            if($ranktype==0){//省
                $list = [];
                foreach($province as $key=>$val){
                    $where = [];
                    $list[$key]['title'] = $val;
                    $where[] = ['aid','=',aid];
                    $where[] = Db::raw("find_in_set('".$val."',area2)");
//                    $where[] = ['area2','like',$val.'%'];
                    if($start_time && $end_time){
                        $where[] = ['paytime','between',[$start_time,$end_time]];
                    }
                    $where[] = ['status','in',[1,2,3]];
                    $totalprice = 0+Db::name('shop_order')->where($where)->sum('totalprice');
                    $list[$key]['totalprice'] = dd_money_format($totalprice);
                    $orderids =   Db::name('shop_order')->where($where)->column('id');
                    $num =  Db::name('shop_order_goods')->where('aid',aid)->where('orderid','in',$orderids)->sum('num');
                    $list[$key]['num'] = $num;
                }
                $totalprice = [];
                $num = [];
                foreach ($list as $key => $row)
                {
                    $totalprice[$key]  = $row['totalprice'];
                    $num[$key]  = $row['num'];
                }
                if($sort_type ==1){
                    array_multisort($totalprice,SORT_DESC,$list);
                } else{
                    array_multisort($num,SORT_DESC,$list); 
                }
              
                $datalist = [];
                if($ranknum > 0){
                    $ranknum = $ranknum >=count($list)?count($list): $ranknum;
	 				for($i=0;$i<$ranknum;$i++){
	                   $datalist[] = $list[$i];
	               	}
                }else{
                	for($i=0;$i<count($list);$i++){
	                   $datalist[] = $list[$i];
	               	}
                }
              
                $rdata['data'] = $datalist;
            }
            elseif($ranktype==1){//市
                $list = [];
                foreach($city as $key=>$val){
                    $where = [];
                    $list[$key]['title'] = $val;
                    $where[] = ['aid','=',aid];
                    $where[] = Db::raw("find_in_set('".$val."',area2)");
//                    $where[] = ['area2','like','%'.$val.'%'];
                    if($start_time && $end_time){
                        $where[] = ['paytime','between',[$start_time,$end_time]];
                    }
                    $where[] = ['status','in',[1,2,3]];
                    $totalprice = 0+Db::name('shop_order')->where($where)->sum('totalprice');
                    $list[$key]['totalprice'] = dd_money_format($totalprice);
                    $orderids =   Db::name('shop_order')->where($where)->column('id');
                    $num =  Db::name('shop_order_goods')->where('aid',aid)->where('orderid','in',$orderids)->sum('num');
                    $list[$key]['num'] = $num;
                }
              
                $totalprice = [];
                $num = [];
                foreach ($list as $key => $row)
                {
                    $totalprice[$key]  = $row['totalprice'];
                    $num[$key]  = $row['num'];
                }
                if($sort_type ==1){
                    array_multisort($totalprice,SORT_DESC,$list);
                } else{
                    array_multisort($num,SORT_DESC,$list);
                }
                $datalist = [];
               	if($ranknum > 0){
                    $ranknum = $ranknum >=count($list)?count($list): $ranknum;
	 				for($i=0;$i<$ranknum;$i++){
	                   $datalist[] = $list[$i];
	               	}
                }else{
                	for($i=0;$i<count($list);$i++){
	                   $datalist[] = $list[$i];
	               	}
                }
                $rdata['data'] = $datalist;
                
            }
            elseif($ranktype==2){  //县区
                $list = [];
                foreach($area as $key=>$val){
                    $where = [];
                    $list[$key]['title'] = $val;
                    $where[] = ['aid','=',aid];
                    $where[] = Db::raw("find_in_set('".$val."',area2)");
//                    $where[] = ['area2','like','%'.$val.'%'];
                    if($start_time && $end_time){
                        $where[] = ['paytime','between',[$start_time,$end_time]];
                    }
                    $where[] = ['status','in',[1,2,3]];
                    $totalprice = 0+Db::name('shop_order')->where($where)->sum('totalprice');
                    $list[$key]['totalprice'] = dd_money_format($totalprice);
                    $orderids =   Db::name('shop_order')->where($where)->column('id');
                    $num =  Db::name('shop_order_goods')->where('aid',aid)->where('orderid','in',$orderids)->sum('num');
                    $list[$key]['num'] = $num;
                }
                $totalprice = [];
                $num = [];
                foreach ($list as $key => $row)
                {
                    $totalprice[$key]  = $row['totalprice'];
                    $num[$key]  = $row['num'];
                }
                if($sort_type ==1){
                    array_multisort($totalprice,SORT_DESC,$list);
                } else{
                    array_multisort($num,SORT_DESC,$list);
                }
                $datalist = [];
                if($ranknum > 0){
                    $ranknum = $ranknum >=count($list)?count($list): $ranknum;
	 				for($i=0;$i<$ranknum;$i++){
	                   $datalist[] = $list[$i];
	               	}
                }else{
                	for($i=0;$i<count($list);$i++){
	                   $datalist[] = $list[$i];
	               	}
                }
                $rdata['data'] = $datalist;
            }
            else{   //个人
                $where = [];
                $where[] = ['aid','=',aid];
                $memberlist = Db::name('member')->where($where)->field('id,nickname,headimg,path')->select()->toArray();
                foreach($memberlist as $key=>$member){
                    $downmids = [];
                    $downmids[] = $member['id'];
                    $downmids_x = \app\common\Member::getteammids($sysset['aid'],$member['id']);
                    $downmids = array_merge($downmids_x,$downmids);
                    if($sort_type ==1){
                        $totalprice =0+ Db::name('shop_order')->where('aid',aid)->where('mid','in',$downmids)->where('status','in',[1,2,3])->sum('totalprice');
                        $memberlist[$key]['totalprice'] = dd_money_format($totalprice);
                       
                    }else{
                        $orderids =   Db::name('shop_order')->where('aid',aid)->where('mid','in',$downmids)->where('status','in',[1,2,3])->column('id');
                        $num =0+ Db::name('shop_order_goods')->where('aid',aid)->where('orderid','in',$orderids)->sum('num');

                        $memberlist [$key]['num'] = $num;
                    }
                }
                $totalprice = [];
                $num = [];
                foreach ($memberlist as $key => $row)
                {
                    $totalprice[$key]  = $row['totalprice'];
                    $num[$key]  = $row['num'];
                }
                if($sort_type ==1){
                    array_multisort($totalprice,SORT_DESC,$memberlist);
                } else{
                    array_multisort($num,SORT_DESC,$memberlist);
                }
                $datalist = [];
              
                if($ranknum > 0){
                    $ranknum = $ranknum >=count($memberlist)?count($memberlist): $ranknum;
                   
	 				for($i=0;$i<$ranknum;$i++){
	                   $datalist[] = $memberlist[$i];
	               	}
                }else{
                	for($i=0;$i<count($memberlist);$i++){
	                   $datalist[] = $memberlist[$i];
	               	}
                }
             
               $rdata['data'] = $datalist;
                
            }

            return $this->json(['status'=>1,'data'=>$rdata,'region_ctime' => $sysset['region_ctime'],'region_show_type' =>explode(',',$sysset['region_show_type']) ]);
        }
    }

    //回本股东分红
    public function fenhong_huiben(){
	    if(getcustom('fenhong_gudong_huiben')){
            $pernum = 20;
            $pagenum = input('param.pagenum') ? input('param.pagenum') :1;
            $where = '';
            $st = input('param.st/d');
            if(!$st) $st = 1;

            //待结算
            $newoglist = [];
            if($st == 1){
                //查询订单
                $sysset = $this->sysset;
                $aid = aid;
                $starttime = 1;
                $endtime = time();
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
                $oglist = Db::name('shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')->where($where)->where('og.refund_num',0)
                    ->join('shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')
                    ->page($pagenum,$pernum)->select()->toArray();

                if(getcustom('yuyue_fenhong',$aid)){
                    $yyorderlist = Db::name('yuyue_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where($where)
                        ->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->page($pernum,$pernum)->select()->toArray();
                    foreach($yyorderlist as $k=>$v){
                        $v['name'] = $v['proname'];
                        $v['real_totalprice'] = $v['totalprice'];
                        $v['cost_price'] = $v['cost_price'] ?? 0;
                        $v['module'] = 'yuyue';
                        $oglist[] = $v;
                    }
                }
                if(getcustom('scoreshop_fenhong',$aid)){
                    $scoreshopoglist = Db::name('scoreshop_order_goods')->alias('og')->field('og.*,m.nickname,m.headimg')->where($where)
                        ->join('member m','m.id=og.mid')->where($bwhere)->order('og.id desc')->page($pernum,$pernum)->select()->toArray();
                    foreach($scoreshopoglist as $v){
                        $v['real_totalprice'] = $v['totalmoney'];
                        $v['module'] = 'scoreshop';
                        $oglist[] = $v;
                    }
                }
                if(getcustom('luckycollage_fenhong',$aid)){
                    $lcorderlist = Db::name('lucky_collage_order')->alias('og')->field('og.*,m.nickname,m.headimg')->where($where)
                        ->join('member m','m.id=og.mid')->where($bwhere)->whereRaw('og.buytype=1 or og.iszj=1')->where('og.isjiqiren',0)
                        ->order('og.id desc')->page($pernum,$pernum)->select()->toArray();
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
                        ->page($pernum,$pernum)
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
                    $diancan_oglist = Db::name('restaurant_shop_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')
                        ->where('og.aid',$aid)->where('og.isfenhong_huiben',0)->where('og.status','in',[1,2,3])
                        ->join('restaurant_shop_order o','o.id=og.orderid')->join('member m','m.id=og.mid')
                        ->where($bwhere)->order('og.id desc')->page($pernum,$pernum)->select()->toArray();
                    if($diancan_oglist){
                        foreach($diancan_oglist as $dck=>$dcv){
                            $dcv['module'] = 'restaurant_shop';
                            $oglist[]      = $dcv;
                        }
                        unset($dcv);
                    }
                    //外卖
                    $takeaway_oglist = Db::name('restaurant_takeaway_order_goods')->alias('og')->field('og.*,o.area2,m.nickname,m.headimg')
                        ->where('og.aid',$aid)->where('og.isfenhong_huiben',0)->where('og.status','in',[1,2,3])
                        ->join('restaurant_takeaway_order o','o.id=og.orderid')->join('member m','m.id=og.mid')
                        ->where($bwhere)->order('og.id desc')->page($pernum,$pernum)->select()->toArray();
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
                        ->page($pernum,$pernum)
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
                        ->page($pernum,$pernum)
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

                if($oglist){
                    $rs = \app\common\Fenhong::gdfenhong_huiben(aid,$this->sysset,$oglist,0,time(),1,mid);
                }
                $newoglist = $rs['oglist'];
                $commissionyj = $rs['commissionyj'];
            }

            if($st==2){//已结算
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['mid','=',mid];
                $where[] = ['type','=','fenhong_huiben'];
                $pernum = 20;
                $pagenum = input('param.pagenum');
                if(!$pagenum) $pagenum = 1;
                $count = Db::name('member_fenhonglog')->where($where)->count();
                $datalist = Db::name('member_fenhonglog')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
                if(!$datalist) $datalist = [];
                foreach($datalist as $k=>$v){
                    if($v['ogids']){
                        if($v['module'] == 'yuyue'){
                            $oglist = Db::name('yuyue_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.proname name,og.propic pic,og.num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                        }elseif($v['module'] == 'luckycollage' || $v['module'] == 'lucky_collage'){
                            $oglist = Db::name('lucky_collage_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.proname name,og.propic pic,og.num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                        }elseif($v['module'] == 'scoreshop'){
                            $oglist = Db::name('scoreshop_order_goods')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.name,og.pic,og.num,og.totalmoney real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                        }elseif($v['module'] == 'kecheng'){
                            $oglist = Db::name('kecheng_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.title as name,og.pic,"1" as num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                        }elseif($v['module'] == 'maidan'){
                            $oglist = Db::name('maidan_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.title as name,"" as pic,"1" as num,og.paymoney real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                        }elseif($v['module'] == 'shop' || empty($v['module'])){
                            $oglist = Db::name('shop_order_goods')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.name,og.pic,og.num,og.real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                        }else{
                            $oglist = [];
                        }
                    }else{
                        $oglist = [];
                    }
                    $datalist[$k]['oglist'] = $oglist;
                }
            }
            $rdata = [];
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $where[] = ['type','=','fenhong_huiben'];
            $where[] = ['status','=',1];
            $count = Db::name('member_fenhonglog')->where($where)->count();
            $commissionyj = Db::name('member_fenhonglog')->where($where)->sum('commission');
            $rdata['count'] = $count;
            $rdata['commissionyj'] = round($commissionyj,2);
            if($st == 1){
                $rdata['datalist'] = $newoglist?:[];
            }else{
                $rdata['datalist'] = $datalist?:[];
            }
            $member = Db::name('member')->where('id',mid)->find();
            $huiben_maximum = $member['huiben_maximum']?:'0';
            //获取当前等级分红上限
            $level_maximum = Db::name('member_level')->where('aid',aid)->where('id',$member['levelid'])->value('fenhong_max_money_huiben');
            $huiben_maximum = bcadd($huiben_maximum,$level_maximum,2);
            $rdata['huiben_maximum'] = $huiben_maximum?:'0';
            $huiben_maximum_status = Db::name('admin_set')->where('aid',aid)->value('fenhong_huiben_max_status');
            $rdata['huiben_maximum_status'] = $huiben_maximum_status?:0;
            $rdata['st'] = $st;
            return $this->json($rdata);
        }

    }

    //商家团队分红
    public function business_teamfenhong(){
	    if(getcustom('business_teamfenhong')){
            $pernum = 20;
            $pagenum = input('param.pagenum') ? input('param.pagenum') :1;
            $where = '';
            $st = input('param.st/d');
            if(!$st) $st = 1;
            $moeny_weishu = 2;
            if(getcustom('fenhong_money_weishu')){
                $moeny_weishu = Db::name('admin_set')->where('aid',aid)->value('fenhong_money_weishu');
            }
            //待结算
            $newoglist = [];
            if($st == 1){
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['mid','=',mid];
                $where[] = ['type','=','business_teamfenhong'];
                $where[] = ['status','=',0];
                $commissionyj = Db::name('member_fenhonglog')->where($where)->sum('commission');
                $datalist = Db::name('member_fenhonglog')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
                if(!$datalist) $datalist = [];
                foreach($datalist as $k=>$v) {
                    //根据分红记录获取分红订单信息
                    $og_item = $this->getFhOrder($v);
                    $newoglist[] = $og_item;
                }
            }
            if($st==2){
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['mid','=',mid];
                $where[] = ['type','=','business_teamfenhong'];
                $where[] = ['status','=',1];
                $pernum = 20;
                $pagenum = input('param.pagenum');
                if(!$pagenum) $pagenum = 1;
                $count = Db::name('member_fenhonglog')->where($where)->count();
                $datalist = Db::name('member_fenhonglog')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
                if(!$datalist) $datalist = [];
                foreach($datalist as $k=>$v){
                    if($v['ogids']){
                        if($v['module'] == 'yuyue'){
                            $oglist = Db::name('yuyue_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.proname name,og.propic pic,og.num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                        }elseif($v['module'] == 'luckycollage' || $v['module'] == 'lucky_collage'){
                            $oglist = Db::name('lucky_collage_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.proname name,og.propic pic,og.num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                        }elseif($v['module'] == 'scoreshop'){
                            $oglist = Db::name('scoreshop_order_goods')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.name,og.pic,og.num,og.totalmoney real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                        }elseif($v['module'] == 'kecheng'){
                            $oglist = Db::name('kecheng_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.title as name,og.pic,"1" as num,og.totalprice real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                        }elseif($v['module'] == 'maidan'){
                            $oglist = Db::name('maidan_order')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.title as name,"" as pic,"1" as num,og.paymoney real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                        }elseif($v['module'] == 'shop' || empty($v['module'])){
                            $oglist = Db::name('shop_order_goods')->alias('og')->join('member m','og.mid=m.id')->field('og.ordernum,og.name,og.pic,og.num,og.real_totalprice,og.createtime,og.status,m.nickname,m.headimg')->where('og.id','in',$v['ogids'])->select()->toArray();
                        }else{
                            $oglist = [];
                        }
                    }else{
                        $oglist = [];
                    }
                    $datalist[$k]['oglist'] = $oglist;
                    $datalist[$k]['commission'] = dd_money_format($v['commission'],$moeny_weishu);
                }
            }

            $rdata = [];
            $rdata['count'] = $count + count($newoglist);

            $rdata['commissionyj'] = dd_money_format($commissionyj,$moeny_weishu);
            if($st == 1){
                    $rdata['datalist'] = $newoglist;
                }else{
                    $rdata['datalist'] = $datalist;
                }
            $rdata['st'] = $st;
            return $this->json($rdata);
        }

    }

    //分销补贴
    public function commissionbutie(){
	    if(getcustom('commission_butie')){
            $pernum = 20;
            $pagenum = input('post.pagenum');
            if(!$pagenum) $pagenum = 1;

            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $st = input('param.st');
            if($st == 0){
                $where[] = ['status','in','0,1'];
            }elseif($st == 1){
                $where[] = ['status','=',1];
            }elseif($st == 2){
                $where[] = ['status','=',0];
            }

            $datalist = Db::name('member_commission_butie')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            foreach($datalist as $k=>$v){
                if($v['type']){
                    $datalist[$k]['orderstatus'] = Db::name($v['type'].'_order')->where('id',$v['orderid'])->value('status');
                }
                if($v['frommid']){
                    $frommember = Db::name('member')->where('id',$v['frommid'])->find();
                    if($frommember){
                        $datalist[$k]['fromheadimg'] = $frommember['headimg'];
                        $datalist[$k]['fromnickname'] = $frommember['nickname'];
                    }else{
                        $datalist[$k]['fromheadimg'] = '';
                        $datalist[$k]['fromnickname'] = '';
                    }
                }
                if($v['fx_butie_type']==1){
                    //按周
                    $circle_str = '每周'.$v['fx_butie_send_week'].'发放';
                }else{
                    $circle_str = '每月'.$v['fx_butie_send_day'].'号发放';
                }
                $datalist[$k]['circle_str'] = $circle_str;
            }
            return $this->json(['status'=>1,'data'=>$datalist]);
        }
    }
    //分销补贴发放记录
    public function commissionbutielog(){
        if(getcustom('commission_butie')){
            $pernum = 20;
            $pagenum = input('post.pagenum');
            if(!$pagenum) $pagenum = 1;

            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            if(input('pid')){
                $where[] = ['pid','=',input('pid')];
            }

            $datalist = Db::name('member_commission_butie_log')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            if(!$datalist) $datalist = [];
            foreach($datalist as $k=>$v){

            }
            return $this->json(['status'=>1,'data'=>$datalist]);
        }
    }

    public function scorerank(){
        if(getcustom('score_ranking')){
            $pernum = 10;
            $pagenum = input('post.pagenum');
            if(!$pagenum) $pagenum = 1;
            $sysset = $this->sysset;
            if($sysset['score_rank_status']==0){
                $data = [];
                $data['status'] = 0;
                return $this->json(['status'=>0,'data'=>$data]);
            }
            $totalscore = $this->member['totalscore'];
            $rdata = [];
            $rdata['totalscore'] = $totalscore;
            $rdata['sysset'] = $sysset;
            $where = [];
            $ranknum = $sysset['score_rank_people'];
            $totalpage = ceil($ranknum/$pernum);//2

            $quyu = $pernum;
            if($pernum * $pagenum > $ranknum) $quyu = $ranknum - $pernum * ($pagenum-1);
            if($ranknum>0){
                if($pagenum>$totalpage){
                    $rdata['data'] = [];
                    return $this->json(['status'=>0,'data'=>$rdata]);
                }
            }
            $where[] = ['o.aid','=',aid];
            if($sysset['score_rank_date'] == 2){
                $beginWeek = mktime(0,0,0,date("m"),date("d")-date("w")+1,date("Y"));
                $where[] = ['o.createtime','>=',$beginWeek];
            }
            if($sysset['score_rank_date'] == 3){
                $date = time();
                $firstDay = strtotime(date('Y-m-01',$date));
                $where[] = ['o.createtime','>=',$firstDay];
            }
            if($sysset['score_rank_date'] == 4){
                $begindate = strtotime('-7 days');
                $enddate =time();
                $where[] = ['o.createtime','between',[$begindate,$enddate]];
            }

            if($sysset['score_rank_date'] == 5){
                $begindate = strtotime("-1 month");
                $enddate =time();
                $where[] = ['o.createtime','between',[$begindate,$enddate]];
            }
            $datalist = Db::name('member')->field('o.*,sum(o.score) sumscore,m.headimg,m.nickname')->alias('m')->leftJoin('member_scorelog o','o.mid=m.id')->where($where);
            if($ranknum == 0){
                $datalist = $datalist->page($pagenum,$pernum);
            }else{
                $datalist = $datalist->limit(($pagenum-1)*$pernum,$quyu);
            }
            $datalist = $datalist->order('sumscore desc')->group('o.mid')->select()->toArray();
            if(!$datalist) $datalist = [];
            $moeny_weishu = 2;
            foreach ($datalist as $key=>$val){
                $datalist[$key]['score']    = $val['score'];
                $datalist[$key]['sumscore'] = $val['sumscore'];
            }
            $rdata['data'] = $datalist;
            return $this->json(['status'=>1,'data'=>$rdata]);
        }
        
    }
	public function signature(){
		$signatureurl = input('param.signatureurl');
		$levelinfo = Db::name('member_level')->where('id',$this->member['levelid'])->find();
		$level_agree = Db::name('member_level_agree')->where('aid',aid)->where('mid',mid)->where('newlv_id',$levelinfo['id'])->find();
		if(!$level_agree){
			$id = Db::name('member_level_agree')->insertGetId(['aid'=>aid,'mid'=>mid,'newlv_id'=>$levelinfo['id'],'w_time'=>time()]);
		}else{
			$id = $level_agree['id'];
		}
		Db::name('member_level_agree')->where('id',$id)->update(['status'=>1,'signatureurl'=>$signatureurl]);
		return $this->json(['status'=>1,'msg'=>'操作成功']);
	}

    public function ranking(){
        if(getcustom('areafenhong_region_ranking')){
            $pagenum = input('post.pagenum');
            $st      = input('post.st');
            $rdata = \app\custom\AgentCustom::ranking(aid,$pagenum,$st);
            return $this->json($rdata);
        }
    }
    //区域代理排行详情
    public function rankingdetial(){
        if(getcustom('areafenhong_region_ranking')){
            $id = input('id')?input('id/d'):'';
            if(!$id){
                return $this->json(['status'=>0,'msg' =>'请选排行榜活动']);
            }
            $ranktype = input('ranktype')?input('ranktype/d'):'';
            $pagenum  = input('pagenum')?input('pagenum/d'):1;
            $sorttype = input('sorttype')?input('sorttype/d'):1;
            $rdata = \app\custom\AgentCustom::rankingdetial(aid,$this->member,$id,$ranktype,$pagenum,$sorttype);
            return $this->json($rdata);
        }
    }
    //区域代理排行活动订单
    public function rankingorder(){
        if(getcustom('areafenhong_region_ranking')){
            $id = input('id')?input('id/d'):'';
            if(!$id){
                return $this->json(['status'=>0,'msg' =>'请选排行榜活动']);
            }
            $pagenum  = input('pagenum')?input('pagenum/d'):1;
            $sorttype = input('sorttype')?input('sorttype/d'):1;
            $rdata = \app\custom\AgentCustom::rankingorder(aid,$this->member,$id,$pagenum,$sorttype);
            return $this->json($rdata);
        }
    }
    //甘尔定制奖金池数据
    public function prize_pool(){
	    if(getcustom('ganer_fenxiao')){
            $aid = aid;
            $set = Db::name('prize_pool_set')->where('aid',$aid)->find();
            $pool_num = $set['pool_num'];
            if($set['show_pool']==1){
                $pool_num =  bcmul($set['pool_num'],$set['send_bili']/100,2);
            }
            $level_ids = json_decode($set['levelids'],true);
            $level_arr = Db::name('member_level')->where('aid',$aid)->field('id,name')->select()->toArray();
            foreach($level_arr as $k=>$level){
                //本月将分红总金额
                $level_bili = $level_ids[$level['id']]??0;
                if($level_bili<=0){
                    unset($level_arr[$k]);
                    continue;
                }
                $prize_total = bcmul($set['pool_num'],$set['send_bili']/100,2);
                $level_prize = bcmul($prize_total,$level_bili/100,2);
                //上月分红总金额
                $e_time = strtotime(date('Y-m-01'));
                $s_time = strtotime(date('Y-m-01',$e_time-86400));
                $map = [];
                $map[] = ['aid','=',$aid];
                $map[] = ['createtime','between',[$s_time,$e_time]];
                $last_month_prize = Db::name('prize_pool_send_log')->where($map)->sum('level_prize_total');
                //分红人数
                $map = [];
                $map[] = ['levelid', '=', $level['id']];
                $map[] = ['aid', '=', $aid];
                $member_count = Db::name('member')->where($map)->count();
                //人均分红
                $avg_prize = bcdiv($level_prize, $member_count, 2);

                $level_arr[$k]['level_prize'] = $level_prize?:0;
                $level_arr[$k]['last_month_prize'] = $last_month_prize?:0;
                $level_arr[$k]['member_count'] = $member_count?:0;
                $level_arr[$k]['avg_prize'] = $avg_prize?:0;

            }
            return json(['status'=>1,'pool_num'=>$pool_num,'level_arr'=>$level_arr,'title'=>'奖金池分红']);
        }
    }

    public function shoporderranking(){
        if(getcustom('shoporder_ranking')){
            //商城订单消费排行榜
            $st = input('st')?input('st/d'):0;
            $rdata = \app\custom\AgentCustom::shoporderranking(aid,$this->member,$st);
            return $this->json($rdata);
        }
    }
    public function shoporderrankinglog(){
        if(getcustom('shoporder_ranking')){
            //商城订单消费排行榜记录
            $pagenum = input('pagenum')?input('pagenum/d'):1;
            $rdata = \app\custom\AgentCustom::shoporderrankinglog(aid,$this->member,$pagenum);
            return $this->json($rdata);
        }
    }
    public function shoporderrankingdetail(){
        if(getcustom('shoporder_ranking')){
            //商城订单消费排行榜记录详情
            $id = input('id')?input('id/d'):0;
            $st = input('st')?input('st/d'):0;
            $rdata = \app\custom\AgentCustom::shoporderrankingdetail(aid,$this->member,$id,$st);
            return $this->json($rdata);
        }
    }

    //视频号小店分销订单
    public function agorder_wxchannels(){
	    if(getcustom('wx_channels')){
            $pernum = 20;
            $pagenum = input('param.pagenum') ? input('param.pagenum') :1;
            $where = '';
            if(input('param.bid')){
                $where = " and o.bid=".input('param.bid/d');
            }
            $st = input('param.st/d');
            if(!$st) $st = 0;
            if($st==10){//待付款
                $where = " and o.status=10";
            }
            if($st==20){//已付款
                $where = " and (o.status=20 or o.status=21 or o.status=30 )";
            }
            if($st==100){//已完成
                $where = " and o.status=100";
            }
            if($st==5){//售后
                $where = " and og.finish_aftersale_sku_cnt>0";
            }
            $field = 'og.id,o.unionid,o.order_id,o.status,o.create_time,o.pay_time,og.finish_aftersale_sku_cnt,og.title,og.product_id,og.thumb_img,og.sku_cnt,og.parent1,og.parent2,og.parent3,og.parent1commission,og.parent2commission,og.parent3commission,og.parent1score,og.parent2score,og.parent3score,og.real_price';
            $datalist = Db::query("select ".$field." from ".table_name('channels_order_goods')." og left join ".table_name('channels_order')." o on o.order_id=og.order_id where og.aid=".aid." $where and  (og.parent1=".mid." or og.parent2=".mid." or og.parent3=".mid.") order by og.id desc limit ".($pagenum*$pernum-$pernum).','.$pernum);
            if(!$datalist) $datalist = [];
            $newdatalist = [];
            foreach($datalist as $k=>$v){
                $data = [];
                $data['ordernum'] = $v['order_id'];
                $data['name'] = $v['title'];
                $data['pic'] = $v['thumb_img'];
                $data['num'] = $v['sku_cnt'];
                $data['totalprice'] = $v['real_price'];
                $data['createtime'] = date('Y-m-d H:i',$v['create_time']);
                $data['status'] = $v['status'];
                $memberinfo = Db::name('member')->where('unionid',$v['unionid'])->field('nickname,headimg')->find();
                $data['nickname'] = $memberinfo['nickname'];
                $data['headimg'] = $memberinfo['headimg'];

                if($v['parent1'] == mid){
                    $data['dengji'] = t('一级');
                    if($v['parent1score'] > 0){
                        $v['parent1score'] = dd_money_format($v['parent1score'],$this->score_weishu);
                        $data['commission'] = $v['parent1score'].t('积分');
                    }else{
                        $data['commission'] = $v['parent1commission'].'元';
                    }
                    $data['dannum'] = 0;
                }
                if($v['parent2'] == mid){
                    $data['dengji'] = t('二级');
                    if($v['parent2score'] > 0){
                        $v['parent2score'] = dd_money_format($v['parent2score'],$this->score_weishu);
                        $data['commission'] = $v['parent2score'].t('积分');
                    }else{
                        $data['commission'] = $v['parent2commission'].'元';
                    }
                    $data['dannum'] = 0;
                }
                if($v['parent3'] == mid){
                    $data['dengji'] = t('三级');
                    if($v['parent3score'] > 0){
                        $v['parent2score'] = dd_money_format($v['parent2score'],$this->score_weishu);
                        $data['commission'] = $v['parent3score'].t('积分');
                    }else{
                        $data['commission'] = $v['parent3commission'].'元';
                    }
                    $data['dannum'] = 0;
                }
                $data['order_info'] = false;
                $newdatalist[] = $data;
            }
            if(request()->isPost()){
                return $this->json(['data'=>$newdatalist]);
            }

            $count = Db::query("select count(1)c from ".table_name('channels_order_goods')." og left join ".table_name('channels_order')." o on o.order_id=og.order_id where og.aid=".aid." and (og.parent1=".mid." or og.parent2=".mid." or og.parent3=".mid.")");
            $count = 0 + $count[0]['c'];

            $count1 = Db::query("select count(1)c from ".table_name('channels_order_goods')." og left join ".table_name('channels_order')." o on o.order_id=og.order_id where og.aid=".aid." and o.status=10 and (og.parent1=".mid." or og.parent2=".mid." or og.parent3=".mid.")");
            $count1 = 0 + $count1[0]['c'];

            $count2 = Db::query("select count(1)c from ".table_name('channels_order_goods')." og left join ".table_name('channels_order')." o on o.order_id=og.order_id where og.aid=".aid." and (o.status=20 or o.status=21 or o.status=30) and (og.parent1=".mid." or og.parent2=".mid." or og.parent3=".mid.")");
            $count2 = 0 + $count2[0]['c'];

            $count3 = Db::query("select count(1)c from ".table_name('channels_order_goods')." og left join ".table_name('channels_order')." o on o.order_id=og.order_id where og.aid=".aid." and o.status=100 and (og.parent1=".mid." or og.parent2=".mid." or og.parent3=".mid.")");
            $count3 = 0 + $count3[0]['c'];

            $commissionyj1 = Db::query("select sum(parent1commission)c from ".table_name('channels_order_goods')." og left join ".table_name('channels_order')." o on o.order_id=og.order_id where og.aid=".aid." and (o.status=10 or o.status=100 or o.status=20 or o.status=21 or o.status=30) and og.parent1=".mid."");
            $commissionyj2 = Db::query("select sum(parent2commission)c from ".table_name('channels_order_goods')." og left join ".table_name('channels_order')." o on o.order_id=og.order_id where og.aid=".aid." and (o.status=10 or o.status=100 or o.status=20 or o.status=21 or o.status=30) and og.parent2=".mid."");
            $commissionyj3 = Db::query("select sum(parent3commission)c from ".table_name('channels_order_goods')." og left join ".table_name('channels_order')." o on o.order_id=og.order_id where og.aid=".aid." and (o.status=10 or o.status=100 or o.status=20 or o.status=21 or o.status=30) and og.parent3=".mid."");

            $commissionyj = 0 + $commissionyj1[0]['c'] + $commissionyj2[0]['c'] + $commissionyj3[0]['c'];

            $rdata = [];
            $rdata['count'] = $count;
            $rdata['count1'] = $count1;
            $rdata['count2'] = $count2;
            $rdata['count3'] = $count3;
            $rdata['commissionyj'] = round($commissionyj,2);
            $rdata['datalist'] = $newdatalist;
            $rdata['st'] = $st;
            return $this->json($rdata);
        }
    }
    
    public function  getteamsaleyejilog(){
        $is_include_self = getcustom('yx_team_yeji_include_self');
        $is_jicha_custom = getcustom('yx_team_yeji_jicha');
        $product_is_join_custom = getcustom('teamyeji_product_is_join');
        $yx_team_yeji_jc = getcustom('yx_team_yeji_jc');
	   if(getcustom('yx_team_yeji')){
	       $st = input('param.st');
           $pagenum = input('param.pagenum');
           if(!$pagenum) $pagenum = 1;
           
           $yejiwhere = [];
           $yeji_set = Db::name('team_yeji_set')->where('aid',aid)->find();
           $now_month = date('Y-m',strtotime('-1 month'));
           $levelup_time = 0;
           $not_join_where = [];//不参与的商品
           if($is_jicha_custom){
               $yeji_set_config =json_decode($yeji_set['config_data'],true);
               $show_levelid = array_keys($yeji_set_config);
               if(in_array($this->member['levelid'],$show_levelid)){
                   $levelup_order = Db::name('member_levelup_order')
                       ->where('aid',aid)
                       ->where('mid',mid)
                       ->where('levelid',$this->member['levelid'])
                       ->where('status',2)
                       ->order('levelup_time desc')
                       ->find();
                   $levelup_time = $levelup_order['levelup_time'];
               }
           }
		   $createfield = 'createtime'; //创建时间
           if($yx_team_yeji_jc && $yeji_set['jc_fenhong']){
               $createfield = 'endtime'; //按收货时间
           }
	       if($st ==1){//待结算
               if($yeji_set['jiesuan_type'] == 1){//按月
                   $month_start = strtotime(date('Y-m-01 00:00:00'));
                   $month_end  = strtotime(date('Y-m-t 23:59:59'));
                   if($is_jicha_custom){
                       if($levelup_time && $levelup_time > $month_start )$month_start =   $levelup_time;
                   }
                   $yejiwhere[] = [$createfield,'between',[$month_start,$month_end]];
                   $not_join_where[] = ['og.'.$createfield,'between',[$month_start,$month_end]];
                   //虚拟业绩 yeji_type业绩类型 0 统计价格（默认） 1:统计数量 不统计虚拟业绩
                   if(!$yeji_set['yeji_type']) $xuni_yeji = 0 +Db::name('tem_yeji_xuni')->where('aid',aid)->where('mid',mid)->where('yeji_month',$now_month)->value('yeji');
               }elseif($yeji_set['jiesuan_type'] == 2){//按年
                   $year_start=strtotime(date('Y') . '-01-01 00:00:00');
                   $year_end=strtotime(date('Y') . '-12-31 23:59:59');
                   if($is_jicha_custom){
                       if($levelup_time && $levelup_time > $year_start )$year_start =   $levelup_time;
                   }
                   $yejiwhere[] = [$createfield,'between',[$year_start,$year_end]];
                   $not_join_where[] = ['og.'.$createfield,'between',[$year_start,$year_end]];
               }elseif($yeji_set['jiesuan_type'] == 3){//按季度
                   $season_start=strtotime(date('Y-m-01 00:00:00'));
                   $season_end=strtotime(date('Y-m-t 23:59:59',strtotime('+3 month')));
                   if($is_jicha_custom){
                       if($levelup_time && $levelup_time > $season_start )$season_start =   $levelup_time;
                   }
                   $yejiwhere[] = [$createfield,'between',[$season_start,$season_end]];
                   $not_join_where[] = ['og.'.$createfield,'between',[$season_start,$season_end]];
               }elseif($yeji_set['jiesuan_type'] == 4){//按天
                    if(getcustom('yx_team_yeji_day')){
                        $day_start = strtotime(date('Y-m-d'));
                        $day_end   = time();
                        $yejiwhere[] = [$createfield,'between',[$day_start,$day_end]];
                        $not_join_where[] = ['og.'.$createfield,'between',[$day_start,$day_end]];
                    }
                }
           }
	       else{//已结算
               if($yeji_set['jiesuan_type'] == 1){//按月
                   $month_start = strtotime(date('Y-m-01 00:00:00',strtotime('-1 month')));
                   $month_end  = strtotime(date('Y-m-t 23:59:59',strtotime('-1 month')));
                   if($is_jicha_custom){
                       //升级时间大于结束时间，无业绩
                       if($levelup_time > $month_end){
                           $month_start = $month_end;
                       }elseif ($levelup_time > $month_start && $levelup_time < $month_end){
                           $month_start =  $levelup_time;
                       }
                   }
                   $yejiwhere[] = ['createtime','between',[$month_start,$month_end]];
                   $not_join_where[] = ['og.createtime','between',[$month_start,$month_end]];
               }elseif($yeji_set['jiesuan_type'] == 2){//按年
                   $year_start=strtotime((date('Y')-1) . '-01-01 00:00:00');
                   $year_end=strtotime((date('Y')-1) . '-12-31 23:59:59');
                   if($is_jicha_custom){
                       if($levelup_time > $year_end){
                           $year_start = $year_end;
                       }elseif ($levelup_time > $year_start && $levelup_time < $year_end){
                           $year_start =  $levelup_time;
                       }
                   }
                   $yejiwhere[] = ['createtime','between',[$year_start,$year_end]];
                   $not_join_where[] = ['og.createtime','between',[$year_start,$year_end]];
               }elseif($yeji_set['jiesuan_type'] == 3){//按季度
                   $season_start=strtotime(date('Y-m-01 00:00:00',strtotime('-3 month')));
                   $season_end=strtotime(date('Y-m-t 23:59:59',strtotime('-1 month')));
                   if($is_jicha_custom){
                       if($levelup_time > $season_end){
                           $season_start =$season_end ;
                       }elseif ($levelup_time > $season_start && $levelup_time < $season_end){
                           $season_start =  $levelup_time;
                       }
                   }
                   $yejiwhere[] = [$createfield,'between',[$season_start,$season_end]];
                   $not_join_where[] = ['og.'.$createfield,'between',[$season_start,$season_end]];
               }elseif($yeji_set['jiesuan_type'] == 4){//按天
                    if(getcustom('yx_team_yeji_day')){
                        $day_end = strtotime(date('Y-m-d 00:00:00'));
                        $day_start  = $day_end-86400;
                        $yejiwhere[] = [$createfield,'between',[$day_start,$day_end]];
                        $not_join_where[] = ['og.'.$createfield,'between',[$day_start,$day_end]];
                    }
                }
           }
           if($yx_team_yeji_jc && $yeji_set['jc_fenhong']){
               $yejiwhere[] = ['status','=',3];
               $not_join_where[] = ['og.status','=',3];
           }else{
           $yejiwhere[] = ['status','in','1,2,3'];
           $not_join_where[] = ['og.status','in','1,2,3'];
           }
           $down_where = [];
           $down_where[] = ['aid','=',aid];
           $down_where[] = ['id','<>',mid];
           $down_where[] = Db::raw('find_in_set('.mid.',path)');
           $downmids = Db::name('member')->where($down_where)->column('id');
           if($is_include_self){
               if($yeji_set['include_self']) $downmids[] = mid;
           }
           if($product_is_join_custom){
               //查询参与的商品id
               $join_proid = Db::name('shop_order_goods')->alias('og')
                   ->join('shop_product p','p.id = og.proid')
                   ->where('og.aid',aid)
                   ->where('og.mid','in',$downmids)
                   ->where('p.teamyeji_join_st',1)
                   ->where($not_join_where)
                   ->column('og.proid');
               if($join_proid){
                   $yejiwhere[] = ['proid','in',$join_proid];
               }
           }
           $count = Db::name('shop_order_goods')->where('aid',aid)->where('mid','in',$downmids)->where($yejiwhere)->count();
           $datalist = Db::name('shop_order_goods')->where('aid',aid)->where('mid','in',$downmids)->where($yejiwhere)->page($pagenum,20)->select()->toArray();
            foreach($datalist as $key=>&$val){
                $member = Db::name('member')->where('aid',aid)->where('id',$val['mid'])->field('headimg,nickname')->find();
                $val['nickname'] = $member['nickname']??'';
                $val['headimg'] = $member['headimg']??'';
            }
           if(!$datalist) $datalist = [];
           return $this->json(['status'=>1,'datalist'=>$datalist,'count' => $count]);
       } 
    }

    //链动换位
    public function change_down_user(){
	    if(getcustom('change_down_user')){
	        if(request()->isPost()){
	            Db::startTrans();
                $old_down = input('old_down');//已经脱离的会员ID
                $old_downmember = Db::name('member')->where('aid',aid)->where('id',$old_down)->find();
                $new_down = input('new_down');//当前下级的会员ID
                $new_downmember = Db::name('member')->where('aid',aid)->where('id',$new_down)->find();

                //原下级更改为新下级
                \app\model\Member::edit(aid,['id'=>$old_down,'pid'=>mid,'pid_origin'=>null,'path_origin'=>'','change_pid_time'=>time()]);
                Db::name('member_pid_changelog')
                    ->where('aid',aid)
                    ->where('mid',$old_down)
                    ->where('pid_origin',mid)
                    ->update(['change_status'=>1,'updatetime'=>time(),'mid'=>$new_down]);
                //现下级脱离
                $updatem = ['id'=>$new_downmember['id'],'pid'=>$old_downmember['pid'],'change_pid_time'=>time()];
                $updatem['pid_origin'] = $old_downmember['pid_origin'];
                $updatem['path_origin'] = $old_downmember['path_origin'];
                \app\model\Member::edit(aid,$updatem);//todo

                Db::commit();
                return $this->json(['status'=>1,'msg'=>'操作成功']);
            }else{
                $now_down = input('now_down');
                $member = Db::name('member')->where('aid',aid)->where('id',$now_down)->find();
                $change_downs = Db::name('member_pid_changelog')
                    ->where('aid',aid)
                    ->where('pid_origin',mid)
                    ->where('change_status',0)->select()->toArray();
                foreach($change_downs as $key=>$val){
                    $change_downs[$key]['nickname'] = Db::name('member')->where('aid',aid)->where('id',$val['mid'])->value('nickname');
                }
                return $this->json(['status'=>1,'msg'=>'操作成功','member'=>$member,'change_downs'=>$change_downs]);
            }

	    }
    }

    //下级商城订单
    public function downshoporder(){
	    if(getcustom('member_level_downorder_collect')) {
            $st = input('param.st');
            if (!input('?param.st') || $st === '') {
                $st = 'all';
            }
            
            $where = [];
            $where[] = ['aid', '=', aid];
            $mid = mid;
            //下级
            $userinfo = Db::name('member')->field('id,nickname,headimg,levelid,last_visittime')->where('aid',aid)->where('id',$mid)->find();
            $userlevel = Db::name('member_level')->where('aid',aid)->where('id',$userinfo['levelid'])->find();
            $downmids =  \app\common\Member::getdowntotalmids(aid,$mid,$userlevel['can_agent']);
            //一级 二级  三级
            $parent1 = Db::name('member')->where('aid',aid)->where('pid',$mid)->column('id');
//            echo     Db::name('member')->getLastSql();
            $parent2 = Db::name('member')->where('aid',aid)->where('pid','in',$parent1)->column('id');
            
            $parent3 = Db::name('member')->where('aid',aid)->where('pid','in',$parent2)->column('id');
            $where[] = ['mid', 'in', $downmids];
            if (input('param.keyword')) {
                $keywords = input('param.keyword');
                $orderids = Db::name('shop_order_goods')->where($where)->where('name', 'like', '%' . input('param.keyword') . '%')->column('orderid');
                if (!$orderids) {
                    $where[] = ['ordernum|title', 'like', '%' . $keywords . '%'];
                }
            }

            if ($st == 'all') {

            } elseif ($st == '0') {
                $where[] = ['status', '=', 0];
            } elseif ($st == '1') {
                $where[] = ['status', '=', 1];
            } elseif ($st == '2') {
                $where[] = ['status', '=', 2];
            } elseif ($st == '3') {
                $where[] = ['status', '=', 3];
            } elseif ($st == '10') {
                $where[] = ['refund_status', '>', 0];
            }

            $pernum = 10;
            $pagenum = input('post.pagenum');
            if (!$pagenum) $pagenum = 1;
            $datalist = Db::name('shop_order')->where($where);
            if ($orderids) {
                $datalist->where(function ($query) use ($orderids, $keywords) {
                    $query->whereIn('id', $orderids)->whereOr('ordernum|title', 'like', '%' . $keywords . '%');
                });
            }
            $datalist = $datalist->page($pagenum, $pernum)->order('id desc')->select()->toArray();
            if (!$datalist) $datalist = array();
            $userlevel = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->find();
            foreach ($datalist as $key => $v) {
                $datalist[$key]['prolist'] = [];
                $prolist = Db::name('shop_order_goods')->where('orderid', $v['id'])->select()->toArray();
                if ($prolist) $datalist[$key]['prolist'] = $prolist;
                $datalist[$key]['procount'] = Db::name('shop_order_goods')->where('orderid', $v['id'])->sum('num');
                $datalist[$key]['member'] = Db::name('member')->field('id,headimg,nickname')->where('id', $v['mid'])->find();
                if (!$datalist[$key]['member']) $datalist[$key]['member'] = [];
                
                if(in_array($v['mid'],$parent1)){
                    $dengji = '一级';
                }elseif(in_array($v['mid'],$parent2)){
                    $dengji = '二级';
                }  elseif(in_array($v['mid'],$parent3)){
                    $dengji = '三级';
                }else{
                    $dengji = '';
                }

                $datalist[$key]['dengji'] = $dengji;
                $datalist[$key]['downorder_collect'] = $userlevel['downorder_collect'];
            }
            $count_all = Db::name('shop_order')->where('aid',aid)->where('mid', 'in', $downmids)->count();
            $count0 = Db::name('shop_order')->where('aid',aid)->where('mid', 'in', $downmids)->where('status',0)->count();
            $count1 = Db::name('shop_order')->where('aid',aid)->where('mid', 'in', $downmids)->where('status',1)->count();
            $count2 = Db::name('shop_order')->where('aid',aid)->where('mid', 'in', $downmids)->where('status',2)->count();
            $count3 = Db::name('shop_order')->where('aid',aid)->where('mid', 'in', $downmids)->where('status',3)->count();
            $countdata = [
                'count_all' => $count_all,
                'count0' => $count0,
                'count1' => $count1,
                'count2' => $count2,
                'count3' => $count3,
            ];
            $rdata = [];
            $rdata['datalist'] = $datalist;
            $rdata['codtxt'] = Db::name('shop_sysset')->where('aid', aid)->value('codtxt');
            $rdata['st'] = $st;
            $rdata['countdata'] = $countdata;
            return $this->json($rdata);
        }
    }
    function  downorderCollect(){ //确认收货
        if(getcustom('member_level_downorder_collect')) {
            $post = input('post.');
            $orderid = intval($post['orderid']);
            $order = Db::name('shop_order')->where('aid', aid)->where('id', $orderid)->find();
            if (bid != 0 && $order['paytypeid'] != 4) {
                return $this->json(['status' => 0, 'msg' => '无操作权限']);
            }
            if (!$order || ($order['status'] != 2)) {
                return $this->json(['status' => 0, 'msg' => '订单状态不符合收货要求']);
            }
            $order_member = Db::name('member')->where('aid',aid)->where('id',$order['mid'])->where('find_in_set('.mid.',path)')->find();
            if(!$order_member)  return $this->json(['status' => 0, 'msg' => '非下级订单']);
            $refundOrder = Db::name('shop_refund_order')->where('refund_status', 'in', [1, 4])->where('aid', aid)->where('orderid', $orderid)->count();
            if ($refundOrder) {
                return $this->json(['status' => 0, 'msg' => '有正在进行的退款，无法确认收货']);
            }
            try {
                Db::startTrans();
                $rs = \app\common\Order::collect($order, 'shop', $this->user['mid']);
                if ($rs['status'] == 0) return $this->json($rs);
                Db::name('shop_order')->where('aid', aid)->where('id', $orderid)->update(['status' => 3, 'collect_time' => time()]);
                Db::name('shop_order_goods')->where('aid', aid)->where('orderid', $orderid)->update(['status' => 3, 'endtime' => time()]);

                if (getcustom('ciruikang_fenxiao')) {
                    //一次购买升级
                    \app\common\Member::uplv(aid, $order['mid'], 'shop', ['onebuy' => 1, 'onebuy_orderid' => $order['id']]);
                } else {
                    \app\common\Member::uplv(aid, $order['mid']);
                }

                if (getcustom('member_shougou_parentreward')) {
                    //首购解锁
                    Db::name('member_commission_record')->where('orderid', $order['id'])->where('type', 'shop')->where('status', 0)->where('islock', 1)->where('aid', $order['aid'])->where('remark', 'like', '%首购奖励')->update(['islock' => 1]);
                }

                if (getcustom('transfer_order_parent_check')) {
                    //确认收货增加有效金额
                    \app\common\Fenxiao::addTotalOrderNum(aid, $order['mid'], $order['id'], 3);
                }

                //即拼
                if (getcustom('yx_collage_jipin')) {
                    \app\common\Order::collageJipinOrder(aid, $orderid);
                }
                //即拼7人成团
                if (getcustom('yx_collage_jipin2')) {
                    \app\common\Order::jipin(aid, $order, 3);
                }
                Db::commit();
                return $this->json(['status' => 1, 'msg' => '确认收货成功']);
            } catch (\Exception $e) {
                Db::rollback();
                return $this->json(['status' => 0, 'msg' => '系统繁忙', 'error' => $e->getMessage()]);
            }
        }
    }
    //商城订单详情
    public function downshoporderdetail(){
        if(getcustom('member_level_downorder_collect')) {
            $detail = Db::name('shop_order')->where('id', input('param.id/d'))->where('aid', aid)->find();
            //是否是下级
            $order_member = Db::name('member')->where('aid',aid)->where('id',$detail['mid'])->where('find_in_set('.mid.',path)')->find();
            if (!$detail || !$order_member) $this->json(['status' => 0, 'msg' => '订单不存在']);
            $detail['createtime'] = $detail['createtime'] ? date('Y-m-d H:i:s', $detail['createtime']) : '';
            $detail['collect_time'] = $detail['collect_time'] ? date('Y-m-d H:i:s', $detail['collect_time']) : '';
            $detail['paytime'] = $detail['paytime'] ? date('Y-m-d H:i:s', $detail['paytime']) : '';
            $detail['refund_time'] = $detail['refund_time'] ? date('Y-m-d H:i:s', $detail['refund_time']) : '';
            $detail['send_time'] = $detail['send_time'] ? date('Y-m-d H:i:s', $detail['send_time']) : '';
            $detail['formdata'] = \app\model\Freight::getformdata($detail['id'], 'shop_order');

            $member = Db::name('member')->where('id', $detail['mid'])->field('id,nickname,headimg')->find();
            $detail['nickname'] = $member['nickname'];
            $detail['headimg'] = $member['headimg'];
            $canFahuo = 0;
            if ($detail['status'] == 1) {
                $canFahuo = 1;
            }
            if (getcustom('erp_wangdiantong')) {
                if ($detail['status'] == 1 && $detail['wdt_status'] == 2) {
                    $canFahuo = 1;
                } elseif ($detail['status'] == 1 && $detail['wdt_status'] == 1) {
                    $canFahuo = 0;//由ERP进行发货
                }
            }
            if (getcustom('supply_zhenxin')) {
                if ($detail['issource'] == 1 && $detail['source'] == 'supply_zhenxin') {
                    $canFahuo = 0;
                }
            }
            $detail['can_fahuo'] = $canFahuo;
            $storeinfo = [];
            $storelist = [];
            $detail['hidefahuo'] = false;
            if ($detail['freight_type'] == 1) {
                if ($detail['mdid'] == -1) {
                    $freight = Db::name('freight')->where('id', $detail['freight_id'])->find();
                    if ($freight && $freight['hxbids']) {
                        if ($detail['longitude'] && $detail['latitude']) {
                            $orderBy = Db::raw("({$detail['longitude']}-longitude)*({$detail['longitude']}-longitude) + ({$detail['latitude']}-latitude)*({$detail['latitude']}-latitude) ");
                        } else {
                            $orderBy = 'sort desc,id';
                        }
                        $storelist = Db::name('business')->where('aid', $freight['aid'])->where('id', 'in', $freight['hxbids'])->where('status', 1)->field('id,name,logo pic,longitude,latitude,address')->order($orderBy)->select()->toArray();
                        foreach ($storelist as $k2 => $v2) {
                            if ($detail['longitude'] && $detail['latitude'] && $v2['longitude'] && $v2['latitude']) {
                                $v2['juli'] = '距离' . getdistance($detail['longitude'], $detail['latitude'], $v2['longitude'], $v2['latitude'], 2) . '千米';
                            } else {
                                $v2['juli'] = '';
                            }
                            $storelist[$k2] = $v2;
                        }
                    }
                } else {
                    $storeinfo = Db::name('mendian')->where('id', $detail['mdid'])->field('id,name,address,longitude,latitude')->find();
                }
                if (getcustom('freight_selecthxbids')) {
                    $detail['hidefahuo'] = true;
                }
            }

            $prolist = Db::name('shop_order_goods')->where('orderid', $detail['id'])->select()->toArray();
            $isjici = 0;
            foreach ($prolist as $pk => $pv) {
                $prolist[$pk]['is_quanyi'] = (isset($pv['product_type']) && $pv['product_type'] == 8) ? 1 : 0;
            }
            $shopsetfield = 'comment,autoclose,canrefund';

            if (getcustom('probgcolor')) {
                $shopsetfield .= ',order_detail_toppic';
            }

            if (getcustom('product_service_fee')) {
                $shopsetfield .= ',show_shd_remark';
            }

            if (getcustom('product_thali')) {
                $shopsetfield .= ',product_shop_school';
            }

            $shopset = Db::name('shop_sysset')->where('aid', aid)->field($shopsetfield)->find();

            if ($detail['status'] == 0 && $shopset['autoclose'] > 0) {
                $lefttime = strtotime($detail['createtime']) + $shopset['autoclose'] * 60 - time();
                if ($lefttime < 0) $lefttime = 0;
            } else {
                $lefttime = 0;
            }
            $peisong_set = Db::name('peisong_set')->where('aid', aid)->find();
            if ($peisong_set['status'] == 1 && bid > 0 && $peisong_set['businessst'] == 0 && $peisong_set['make_status'] == 0) $peisong_set['status'] = 0;
            $detail['canpeisong'] = ($detail['freight_type'] == 2 && $peisong_set['status'] == 1) ? true : false;
            $detail['express_wx_status'] = $peisong_set['express_wx_status'] == 1 ? true : false;

            if (getcustom('express_maiyatian')) {
                $detail['myt_status'] = $peisong_set['myt_status'] == 1 ? true : false;
                $detail['myt_set'] = true;
                $detail['myt_shop'] = false;
                $detail['myt_shoplist'] = [];
                if ($detail['myt_shop']) {
                    $detail['myt_shoplist'] = Db::name('peisong_myt_shop')->where('aid', aid)->where('bid', $detail['bid'])->where('is_del', 0)->order('id asc')->field('id,origin_id,name')->select()->toArray();
                    if (!$detail['myt_shoplist']) {
                        $detail['myt_shoplist'] = [['id' => 0, 'origin_id' => 0, 'name' => '无门店可选择']];
                    }
                }
            }

            if ($detail['freight_type'] == 2) {
                $peisong = Db::name('peisong_order')->where('orderid', $detail['id'])->where('type', 'shop_order')->field('id,psid')->find();
                if ($peisong) {
                    $detail['psid'] = $peisong['psid'];
                }
            }
            if ($detail['checkmemid']) {
                $detail['checkmember'] = Db::name('member')->field('id,nickname,headimg,realname,tel')->where('id', $detail['checkmemid'])->find();
            } else {
                $detail['checkmember'] = [];
            }

            if (getcustom('product_service_fee') && $shopset['show_shd_remark'] == 1) {
                foreach ($prolist as &$vv) {
                    $vv['shd_remark'] = Db::name('shop_product')->where('id', $vv['proid'])->value('shd_remark');
                }
                unset($vv);
            }
            $detail['message'] = \app\model\ShopOrder::checkOrderMessage($detail['id'], $detail);
            if (getcustom('pay_money_combine')) {
                if ($detail['combine_money'] && $detail['combine_money'] > 0) {
                    if (!empty($detail['paytype'])) {
                        $detail['paytype'] .= ' + ' . t('余额') . '支付';
                    } else {
                        $detail['paytype'] .= t('余额') . '支付';
                    }
                }
            }
            if (getcustom('product_pickup_device')) {
                if ($detail['dgid'] && $detail['freight_type'] == 1) {
                    $f_device = Db::name('product_pickup_device_goods')->alias('dg')
                        ->join('product_pickup_device d', 'd.id = dg.device_id')
                        ->where('dg.aid', aid)->where('dg.id', $detail['dgid'])->field('d.address,d.name')->find();
                    $storeinfo['address'] = $f_device['address'];
                    $storeinfo['name'] = $f_device['name'];
                }
            }
            $detail['is_quanyi'] = 0;
            if (getcustom('product_quanyi') && $detail['product_type'] == 8) {
                $detail['hexiao_num_remain'] = $detail['hexiao_num_total'] - $detail['hexiao_num_used'];
                $detail['is_quanyi'] = 1;
            }

            //如果是兑换订单显示兑换码
            if ($detail['lipin_dhcode'] && $detail['paytype'] == '兑换码兑换') {
                $detail['paytype'] = $detail['paytype'] . "(" . $detail['lipin_dhcode'] . ")";
            }

            $detail['product_thali'] = false;
            if (getcustom('product_thali') && $shopset['product_shop_school'] == 1) {
                $detail['product_thali'] = true;
            }

            $shopset['send_show'] = 1;
            $shopset['hexiao_show'] = 1;
            if ($detail['bid'] > 0) {
                $business_sysset = \db('business_sysset')->where('aid', aid)->find();
                if (getcustom('business_shop_order_send_show')) {
                    $shopset['send_show'] = $business_sysset['shop_order_send_show'];
                }
                if (getcustom('business_shop_order_hexiao_show')) {
                    $shopset['hexiao_show'] = $business_sysset['shop_order_hexiao_show'];
                }
            }
            if (getcustom('shop_giveorder')) {
                if ($detail['usegiveorder'] && $detail['giveordermid'] > 0) {
                    $givemember = Db::name('member')->where('id', $detail['giveordermid'])->field('id,nickname,headimg,tel,realname')->find();
                }
                $detail['givemember'] = $givemember ?? '';
            }
            $userlevel = Db::name('member_level')->where('aid', aid)->where('id', $this->member['levelid'])->field('id,downorder_collect')->find();
            $rdata = [];
            $rdata['detail'] = $detail;
            $rdata['prolist'] = $prolist;
            $rdata['shopset'] = $shopset;
            $rdata['storeinfo'] = $storeinfo;
            $rdata['lefttime'] = $lefttime;
            $rdata['expressdata'] = array_keys(express_data(['aid' => aid, 'bid' => $detail['bid']]));
            $rdata['codtxt'] = Db::name('shop_sysset')->where('aid', aid)->value('codtxt');
            $rdata['storelist'] = $storelist;
            $rdata['userlevel'] = $userlevel;

            return $this->json($rdata);
        }
    }

    //佣金转商户余额
    public function commissionToBusiness(){
        if(getcustom('commission_transfer_business_money')){
            $set = Db::name('admin_set')->where('aid',aid)->find();
            if($set['commission_transfer_business_money'] != 1) {
                return $this->json(['status'=>0,'msg'=>'未开启此功能']);
            }
            if(request()->isPost()){
                $bid = input('post.bid/d');
                $money = input('post.money/f');
                if (input('?post.bid') && $bid > 0) {
                    $business = Db::name('business')->where('aid', aid)->where('id', $bid)->find();
                }
                if(!$business) return $this->json(['status'=>0,'msg'=>'未找到该商户']);

                if ($money > $this->member['commission']){
                    return $this->json(['status'=>0,'msg'=>'您的'.t('佣金').'不足']);
                }

                //扣除会员自身的佣金
                $dec_money = $money;
                $to_money = $money;
                $fee = 0;
                if($set['commission_transfer_business_rate']>0){
                    $fee = bcmul($money,$set['commission_transfer_business_rate']/100,2);
                    $to_money = bcsub($money,$fee,2);
                }

                $toMidMsg = sprintf("来自%s的".t('佣金')."转账", $this->member["nickname"]);
                $midMsg = sprintf("转给商户：%s，手续费：%s。", $business['name'], $fee);

                $rs = \app\common\Member::addcommission(aid,mid,0,$dec_money * -1 ,$midMsg);
                if ($rs['status'] == 1) {
                    \app\common\Business::addmoney(aid,$bid,$to_money,$toMidMsg);
                }else{
                    return $this->json(['status'=>0, 'msg' => '转账失败']);
                }
                return $this->json(['status'=>1, 'msg' => '转账成功', 'url'=>'/pages/my/usercenter']);
            }
            $rdata['status'] = 1;
            $rdata['mycommission'] = dd_money_format($this->member['commission'],2);
            $rdata['transfer_rate'] = $set['commission_transfer_business_rate'];
            return $this->json($rdata);
        }
    }
    //待返金额明细和返现记录
    public function cashbackadduprecord(){
	    if(getcustom('yx_cashback_addup_return')){
            $st = input('param.st');
            $pernum = 20;
            $pagenum = input('post.pagenum');
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
			//设置
			$cashback_set = Db::name('cashback_sysset')->where('aid',aid)->find();
            if($st ==1) {//待返明细
                $datalist = Db::name('cashback_addup_log')->field("id,back_price money,`after`,from_unixtime(createtime)createtime,remark")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
            }else{ //返现记录
				if($cashback_set['show_expired_record'] == 0){
					$where[] = ['status','<',2];
				}
                $datalist = Db::name('cashback_addup_record')->field("id,money,status,type,cashback_id,from_unixtime(createtime)createtime,from_unixtime(expiretime) expiretime,from_unixtime(collecttime) collecttime")->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
				foreach($datalist as $key=>$val){
				    $cashback_name = '';
				    if($val['cashback_id']){
                        $cashback_name= Db::name('cashback')->where('aid',aid)->where('id',$val['cashback_id'])->value('name');
                    }
                    $datalist[$key]['cashback_name'] =  $cashback_name;
                }
            }
            //待返
            $back_price = Db::name('cashback_addup')->where('aid',aid)->where('mid',mid)->value('back_price');
            //已返
            $total_money = Db::name('cashback_addup_record')->where('aid',aid)->where('mid',mid)->where('status',1)->sum('money');

            //利用缓存实现每一次都是不同的视频，多个视频循环使用
            $video_list = explode(',',$cashback_set['video_url']);
            $cache_video_index = cache('cashback_addup_return_video_'.aid)?:0;
            if(!isset($video_list[$cache_video_index])){
                cache('cashback_addup_return_video_'.aid,0,null);
                $cache_video_index = 0;
            }
            $video_url = $video_list[$cache_video_index];
            $rdata = [];
            $rdata['total_back_price'] =  0+$back_price;
            $rdata['total_money'] =  0+$total_money;
            $rdata['adpid'] =  $cashback_set['adpid'];
            $rdata['rewardedvideoad'] =  $cashback_set['rewardedvideoad'];
            $rdata['video_url'] =  $video_url;
            $rdata['play_seconds'] =  $cashback_set['play_seconds'];
			if(getcustom('yx_cashback_addup_return_sign_send')){
				$send_condition = $cashback_set['send_condition'];
				if($send_condition == 1){
					$rdata['send_condition'] = true;
					$hasSigned = Db::name('cashback_addup_sign')->where('mid',mid)->where('signdate',date("Y-m-d"))->count();
					if($hasSigned > 0){
						$rdata['signing'] = true;
					}
				}
			}
			$rdata['money_text'] = $cashback_set['money_text'];
            return $this->json(['status'=>1,'data'=>$datalist,'rdata'=>$rdata]);

        }
    }
    //领取返现金额
    public function receivebackprice(){
	    if(getcustom('yx_cashback_addup_return')){
	        $id = input('param.id');
	        $record = Db::name('cashback_addup_record')->where('aid',aid)->where('id',$id)->find();
	        if(!$record)return $this->json(['status'=>0, 'msg' => '记录不存在']);
	        $cashback_back_type = Db::name('cashback_sysset')->where('aid',aid)->value('back_type');
            if($record['type'] ==1){ //1:自定义类型
                $cashback_back_type = $record['back_type'];
            }
	        if($cashback_back_type ==1){
                \app\common\Member::addmoney(aid,$record['mid'],$record['money'],t('购物返现').'领取');
            } else if($cashback_back_type == 2){
                \app\common\Member::addcommission(aid,$record['mid'],$record['mid'],$record['money'],'购物返现领取');
            } else if($cashback_back_type == 3){
                \app\common\Member::addscore(aid,$record['mid'],$record['money'],'购物返现领取');
            }
            Db::name('cashback_addup_record')->where('aid',aid)->where('id',$id)->update(['status' => 1,'collecttime' => time()]);
            $cache_video_index = cache('cashback_addup_return_video_'.aid)?:0;
            cache('cashback_addup_return_video_'.aid,$cache_video_index+1,null);
            return $this->json(['status' => 1, 'msg' => '领取成功']);
        }
    }

    //股东分红额度转积分
    public function fenhongmaxToScore(){
	    if(getcustom('fenhong_max_add')){
            $set = $this->sysset_custom;
            if($set['fenhongmax_to_score'] == 0){
                return $this->json(['status'=>-4,'msg'=>'转换功能未开启']);
            }
            $field = 'id,headimg,nickname,score,fenhong_max_add';
            $userinfo = Db::name('member')->where('id',mid)->field($field)->find();

            //查询会员已获得股东分红
            $where_fenhong = [];
            $where_fenhong[] = ['mid','=',mid];
            $where_fenhong[] = ['type','=','fenhong'];
            $where_fenhong[] = ['status','in',[0,1]];
            $fenhong_total = Db::name('member_fenhonglog')
                ->where($where_fenhong)
                ->sum('commission');
            $userinfo['gudong_total'] = bcmul($fenhong_total,1,2);
            $gudong_remain = bcsub($userinfo['fenhong_max_add'],$userinfo['gudong_total'],2);
            $userinfo['gudong_remain'] = $gudong_remain>0?$gudong_remain:0;

            if(request()->isPost()){
                $post = input('post.');
                Db::startTrans();
                $money = $post['money'];
                if($money<=0){
                    return $this->json(['status'=>0,'msg'=>'兑换额度必须大于0']);
                }
                if($money > $userinfo['gudong_remain']){
                    return $this->json(['status'=>0,'msg'=>'可兑换额度不足']);
                }
                $fenhongmax_to_score_bili = $set['fenhongmax_to_score_bili']?:1;
                //转换积分
                $score = bcmul($money,$fenhongmax_to_score_bili,$this->score_weishu) ;
                if($score<=0){
                    return $this->json(['status'=>0,'msg'=>'兑换数量错误']);
                }
                //扣除分红额度
                \app\common\Member::addfenhongmaxnum(aid,mid,-$money,'兑换'.t('积分'));

                //增加积分数量
                \app\common\Member::addscore(aid,mid,$score,t('股东分红额度').'兑换');

                Db::commit();


                return $this->json(['status'=>1,'msg'=>'提交成功,请等待打款']);
            }

            $rdata = [];
            $rdata['status'] = 1;
            $rdata['userinfo'] =  $userinfo ;
            $rdata['sysset'] = $set;

            return $this->json($rdata);
        }
    }

    //推荐商家
    public function tjbcount(){
	    if(getcustom('business_agent_count')){
            $pernum = 10;
            $pagenum = input('pagenum');
            if(!$pagenum) $pagenum = 1;
            $st = input('st')?:1;
            $where = [];
            if (getcustom('business_agent_referrer_independent')) {
                $where[] = ['b.recommend_type','=',1];
            }
            //推荐的所有商家
            $bids =  Db::name('member')->alias('m')->field('m.id,m.pid,b.id,b.logo')->where('m.aid',aid)
                ->where('m.pid',mid)->where('b.status',1)->where($where)
                ->join('business b','m.id=b.mid')->column('b.id');

            //独立推荐关系
            if (getcustom('business_agent_referrer_independent')) {
                $independent = Db::name('business_sysset')->where('aid',aid)->value('referrer_independent') ?: 0;
                if($independent){
                    //获取独立推荐的商家IDs
                    $independentBids = Db::name('business_referrer_independent')
                        ->alias('bri')
                        ->join('business b', 'b.id = bri.bid')
                        ->join('member m', 'm.id = bri.mid')
                        ->where('bri.aid', aid)
                        ->where('bri.mid', mid)
                        ->where('b.recommend_type', 2)
                        ->where('b.status', 1)
                        ->column('b.id');

                    // 合并并去重
                    $bids = array_unique(array_merge($bids, $independentBids));
                }
            }
            if($pagenum==1){
                //统计商家数量
                $count = count($bids);
                if(!$bids){
                    $bids = [-1];
                }
                //统计商家营业额
                $shop_order_month = 0+ Db::name('shop_order_goods')
                        ->where('aid',aid)
                        ->where('bid','in',$bids)
                        ->where('status','in',[1,2,3])
                        ->where('refund_num','=',0)
                        ->sum('real_totalprice');
                $maidan_order_month = 0+ Db::name('maidan_order')
                        ->where('aid',aid)
                        ->where('bid','in',$bids)
                        ->where('status',1)
                        ->sum('paymoney');
                $order_price_total = bcadd($shop_order_month,$maidan_order_month,2);
                //统计今日收益
                $commission_today = Db::name('member_commissionlog')
                    ->where('mid',mid)
                    ->where('fhtype','=','business_agent')
                    ->where('createtime','>',strtotime(date('Y-m-d')))
                    ->sum('commission');

                //统计累计收益
                $commission_total = Db::name('member_commissionlog')
                    ->where('mid',mid)
                    ->where('fhtype','=','business_agent')
                    ->sum('commission');
                $total_count = [
                    'count' => $count,
                    'order_price_total' => $order_price_total,
                    'commission_today' => $commission_today,
                    'commission_total' => $commission_total,
                ];
            }

            if($st==2){
                //已结算
                $datalist = Db::name('member_commissionlog')
                    ->where('aid',aid)
                    ->where('mid',mid)
                    ->where('fhtype','=','business_agent')
                    ->order('createtime desc')
                    ->page($pagenum,$pernum)
                    ->select()->toArray();
                foreach($datalist as $k=>$v){

                }
            }elseif($st==1){
                //待结算
                $datalist = Db::name('shop_order_goods')
                    ->where('aid',aid)
                    ->where('mid',mid)
                    ->where('status','in',[0,1,2])
                    ->where('bid','in',$bids)
                    ->order('createtime desc')
                    ->page($pagenum,$pernum)
                    ->select()->toArray();
                $aid = aid;
                foreach($datalist as $k=>$order){
                    $business = Db::name('business')->where('id',$order['bid'])->find();
                    $datalist[$k]['b_logo'] = $business['logo'];
                    $datalist[$k]['b_name'] = $business['name'];
                }
            }
            return $this->json(['status'=>1,'datalist'=>$datalist,'total_count'=>$total_count]);
        }
    }

    //会员推荐商家海报
    public function memberBusinessPoster(){
        if(getcustom('member_business_poster')){
            $member = $this->member;
            $platform = platform;
            $page = '/pagesExt/business/apply';
            $scene = 'pid_'.$this->member['id'];
            if(input('param.posterid')){
                $posterset = Db::name('admin_set_poster')->where('aid',aid)->where('type','recommend_business')->where('platform',$platform)->where('id',input('param.posterid'))->find();
            }else{
                $posterset = Db::name('admin_set_poster')->where('aid',aid)->where('type','recommend_business')->where('platform',$platform)->order('id')->find();
            }
            $posterdata = Db::name('member_poster')->where('aid',aid)->where(['mid'=>mid,'posterid'=>$posterset['id']])->find();
            $getposter = false;//是否需要生成海报
            if(!$posterdata) $getposter =true;
            if($getposter){
                $textReplaceArr = [
                    '[头像]'=>$member['headimg'],
                    '[昵称]'=>$member['nickname'],
                    '[姓名]'=>$member['realname'],
                    '[手机号]'=>$member['tel'],
                ];
                $poster = $this->_getposter(aid,0,$platform,$posterset['content'],$page,$scene,$textReplaceArr);
                $data = [];
                $data['scene']  = $scene;
                $data['page']   = $page;
                $data['poster'] = $poster;
                $data['posterid'] = $posterset['id'];
                if(!$posterdata){
                    $data['aid'] = aid;
                    $data['mid'] = $member['id'];
                    $data['type'] = 'recommend_business';
                    $data['createtime'] = time();
                    Db::name('member_poster')->insert($data);
                }else{
                    Db::name('member_poster')->where('id',$posterdata['id'])->update($data);
                }
                $posterdata['poster'] = $data['poster'];
            }
            $rdata = [];
            $rdata['poster'] = $posterdata['poster'];
            $rdata['posterid'] = $posterset['id'];
            return $this->json($rdata);
        }
    }

    //推荐商家让利统计
    public function tjbusiness(){
        if(getcustom('business_tj_count',aid)){
            $pernum = 10;
            $st = input('st') ?: 1;
            $date = input('date','today');
            $pagenum = input('pagenum') ?: 1;

            $dateRanges = [
                'today' => [strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59'))],
                'week' => [strtotime('-7 days'), time()],
                'month' => [strtotime(date('Y-m-01 00:00:00')), strtotime(date('Y-m-t 23:59:59'))],
            ];

            $where = [];  //订单
            $bwhere = []; //业绩
            //日期筛选 业绩、订单、推广收益
            if (isset($dateRanges[$date])) {
                $where[] = ['createtime', 'between', $dateRanges[$date]];
                $bwhere[] = ['b.createtime', 'between', $dateRanges[$date]];
            }
            
            $count = ['rangli_count' => 0,'business_count' => 0,'commission_count' => 0,'order_count' => 0];
            $query = Db::name('business')->alias('b')->join('member m', 'm.id=b.mid')->where('m.pid',mid)->where('b.aid', aid)->where('b.status', 1);
            $bids = [];
            //独立推荐商家
            $independent = 0;
            if (getcustom('business_agent_referrer_independent',aid)) {
                $independent = Db::name('business_sysset')->where('aid',aid)->value('referrer_independent')?:0;
                if($independent){
                    $bisBids = $query->column('b.id');
                    $indBids = Db::name('business_referrer_independent')
                        ->alias('bri')
                        ->join('business b', 'b.id = bri.bid')
                        ->join('member m', 'm.id = bri.mid')
                        ->where('bri.aid', aid)
                        ->where('bri.mid', mid)
                        ->where('b.recommend_type', 2)
                        ->column('b.id');

                    $bids = array_unique(array_merge($bisBids,$indBids));
                }
            }
            if($independent == 0){
                $bids = $query->column('b.id');
            }
            if(!empty($bids)) {
                //订单数量
                $shopOrder = Db::name('shop_order')
                    ->field('COUNT(*) as count')
                    ->where('aid', aid)
                    ->where('bid', 'in', $bids)
                    ->where($where)
                    ->where('status', 'in', '1,2,3')
                    ->find();
                $maidanOrder = Db::name('maidan_order')
                    ->field('COUNT(*) as count')
                    ->where('aid', aid)
                    ->where('bid', 'in', $bids)
                    ->where($where)
                    ->where('status', 1)
                    ->find();
                $count['order_count'] = ($shopOrder['count'] ?? 0) + ($maidanOrder['count'] ?? 0);
                $count['business_count'] = count($bids);
                $count['rangli_count'] = Db::name('business')
                    ->alias('b')
                    ->where('aid',aid)
                    ->where('id','in',$bids)
                    ->where('status',1)
                    ->where($bwhere)
                    ->sum('newscore_total');
            }
            $score_weishu = 0;
            if(getcustom('score_weishu',aid)){
                $score_weishu = Db::name('admin_set')->where('aid',aid)->value('score_weishu');
                $score_weishu = $score_weishu?$score_weishu:0;
            }
            $count['rangli_count'] = dd_money_format($count['rangli_count'],$score_weishu);
            //推广收益（佣金）
            $count['commission_count'] = Db::name('member_commissionlog')
                ->where('fhtype','business_agent')
                ->where('aid',aid)
                ->where('mid',mid)
                ->where($where)
                ->sum('commission');

            //列表
            $order = $st == 2 ? 'newscore_ratio desc' : 'newscore_total desc';
            $datalist = [];
            if(!empty($bids)){
                $datalist = Db::name('business')->field('id,logo,name,sales,address,latitude,longitude,comment_score,tel,newscore_ratio,newscore_total')->where('aid',aid)->where('id','in',$bids)->order($order)->page($pagenum,$pernum)->select()->toArray();
            }
            $data['status'] = 1;
            $data['count'] = $count;
            $data['datalist'] = $datalist;
            $data['invite_business'] = '/pages/my/usercenter';
            if(getcustom('member_business_poster',aid)){
                $data['invite_business'] = '/pagesD/recommendbusiness/poster';
            }
            return $this->json($data);
        }
    }

    //区域推荐商家让利统计
    public function areatjbusiness(){
        if(getcustom('business_area_tj_count',aid) || getcustom('member_business_area_tj_count',aid)){
            $pernum = 10;
            $st = input('st') ?: 1;
            $date = input('date','today');
            $pagenum = input('pagenum') ?: 1;

            $opttype = input('?param.opttype')?input('param.opttype'):'';//操作类型
            if($opttype && $opttype == 'member_business_area'){
                if(getcustom('member_business_area_tj_count')){
                    //会员商家区域数据
                    $business_areadata = !empty($this->member['business_areadata'])?json_decode($this->member['business_areadata'],true):[];
                    if(!$business_areadata) return $this->json(['status'=>0,'msg'=>'无可查看的区域']);

                    $provincename = input('?param.provincename')?trim(input('param.provincename')):'';
                    $cityname     = input('?param.cityname')?trim(input('param.cityname')):'';
                    $districtname = input('?param.districtname')?trim(input('param.districtname')):'';
                    //获取可查看的区域
                    $dealareas = $this->dealareas($business_areadata,$provincename,$cityname,$districtname);
                    if(!$dealareas || $dealareas['status'] == 0){
                        $msg = $dealareas && $dealareas['msg']?$dealareas['msg']:'';
                        return $this->json(['status'=>0,'msg'=>$msg]);
                    }
                    $newareas = $dealareas['newareas'];
                    if(!$newareas) return $this->json(['status'=>0,'msg'=>'无可查看的区域']);
                    $provinceArray = $dealareas['provinceArray'];$cityArray = $dealareas['cityArray'];$districtArray = $dealareas['districtArray'];
                    $provincename = $dealareas['provincename'];$cityname = $dealareas['cityname'];$districtname = $dealareas['districtname'];
                }
            }

            $count = ['rangli_count' => 0,'business_count' => 0,'commission_count' => 0,'order_count' => 0];

            $dateRanges = [
                'today' => [strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59'))],
                'week' => [strtotime('-7 days'), time()],
                'month' => [strtotime(date('Y-m-01 00:00:00')), strtotime(date('Y-m-t 23:59:59'))],
            ];
            if($opttype && $opttype == 'member_business_area'){
                $dateRanges['year'] = [strtotime(date('Y-01-01 00:00:00')), time()];
            }

            $where = []; //业绩、订单
            $rwhere = []; //让利
            if(isset($dateRanges[$date])){
                $where[] = ['createtime', 'between', $dateRanges[$date]];
                $rwhere[] = ['createtime', 'between', $dateRanges[$date]];
            }

            //区域代理
            $bids = [];
            if($opttype && $opttype == 'member_business_area'){
                if(getcustom('member_business_area_tj_count')){
                    $bwhere = [
                        ['b.aid', '=', aid],
                        ['b.status', '=', 1]
                    ];
                    if(!$provincename)  return $this->json(['status'=>0,'msg'=>'参数错误']);
                    $bwhere[] = ['b.province','=',$provincename];

                    if(!$cityname)  return $this->json(['status'=>0,'msg'=>'参数错误']);
                    $bwhere[] = ['b.city','=',$cityname];

                    if($districtname)  $bwhere[] = ['b.district','=',$districtname];
                    //筛选当前区域的商家
                    $bids = Db::name('business')->alias('b')->where($bwhere)->column('b.id');
                    if(!input('param.districtname')){
                        //筛选最新的10个订单
                        $neworders = Db::name('shop_order')->alias('so')
                            ->join('business b','so.bid = b.id')
                            ->where($bwhere)
                            ->where('so.aid', aid)->where('so.status', 'in', '1,2,3')->limit(10)->field('so.id,so.totalprice,b.name')->select()->toArray();
                        $ordernum = count($neworders);
                        if($ordernum<10){
                            $maidanlimit = 10 - $ordernum;
                            $neworders2 = Db::name('maidan_order')->alias('mo')
                                ->join('business b','mo.bid = b.id')
                                ->where($bwhere)
                                ->where('mo.aid', aid)->where('mo.status',1)->limit($maidanlimit)->field('mo.id,mo.paymoney as totalprice,b.name')->select()->toArray();
                            if($neworders){
                                $neworders = array_merge($neworders,$neworders2);
                            }
                        }
                    }
                }
            }else{
                $areaname = '-';
                $areafenhong = $this->member['areafenhong'];
                $bwhere = [
                    ['aid', '=', aid],
                    ['status', '=', 1]
                ];
                if($areafenhong > 0 && $areafenhong < 4){
                    $areadata = [
                        1 => ['field' => 'province', 'source' => 'areafenhong_province'],
                        2 => ['field' => 'city', 'source' => 'areafenhong_city'],
                        3 => ['field' => 'district', 'source' => 'areafenhong_area']
                    ];

                    $area = $areadata[$areafenhong];
                    $areaname = $this->member[$area['source']];
                    $bwhere[] = [$area['field'], '=', $areaname];
                    //筛选当前区域的商家
                    $query = Db::name('business')->where($bwhere);
                    $bids = $query->column('id');
                }
            }
            $count['rangli_count'] = Db::name('business')
                ->alias('b')
                ->where('aid',aid)
                ->where('id','in',$bids)
                ->where('status',1)
                ->where($rwhere)
                ->sum('newscore_total');
            $count['business_count'] = count($bids);
            if(!empty($bids)) {
                //订单数量
                $shopOrder = Db::name('shop_order')
                    ->field('COUNT(*) as count')
                    ->where('aid', aid)
                    ->where('bid', 'in', $bids)
                    ->where($where)
                    ->where('status', 'in', '1,2,3')
                    ->find();
                $maidanOrder = Db::name('maidan_order')
                    ->field('COUNT(*) as count')
                    ->where('aid', aid)
                    ->where('bid', 'in', $bids)
                    ->where($where)
                    ->where('status', 1)
                    ->find();
                $count['order_count'] = ($shopOrder['count'] ?? 0) + ($maidanOrder['count'] ?? 0);
            }
            $score_weishu = 0;
            if(getcustom('score_weishu',aid)){
                $score_weishu = Db::name('admin_set')->where('aid',aid)->value('score_weishu');
                $score_weishu = $score_weishu?$score_weishu:0;
            }
            $count['rangli_count'] = dd_money_format($count['rangli_count'],$score_weishu);
            //推广收益（区域分红）
            $mid = mid;
            $count['commission_count'] = Db::name('member_fenhonglog')
                ->where('type','areafenhong')
                ->where('aid',aid)
                ->where(function ($query) use ($mid) {
                    $query->whereOr('mid', $mid);
                })
                ->where($where)
                ->sum('commission');
            //列表
            $order = $st == 2 ? 'newscore_ratio desc' : 'newscore_total desc';
            $datalist = [];
            if($opttype && $opttype == 'member_business_area' && !$districtname){
                if(getcustom('member_business_area_tj_count')){
                    foreach($districtArray as $dv){
                        $newscore_total = 0;
                        if(!empty($bids)){
                             $newscore_total = Db::name('business')->alias('b')
                                 ->join('member m','m.id = b.mid','left')
                                 ->where('b.aid',aid)->where('b.district',$dv)->where('b.id','in',$bids)
                                 ->sum('b.newscore_total');
                        }
                        $datalist[] = ['name'=>$dv,'newscore_total'=>$newscore_total??0];
                    }
                    unset($dv);
                }
            }else{
                if(!empty($bids)){
                    $field = 'b.id,b.logo,b.name,b.sales,b.address,b.latitude,b.longitude,b.comment_score,b.newscore_ratio,b.newscore_total,m.id as pid,m.nickname';
                    if (getcustom('business_agent_referrer_independent',aid)) {
                        $field .= ',b.recommend_type';
                    }
                    $datalist = Db::name('business')
                        ->alias('b')
                        ->field($field)
                        ->join('member m','m.id = b.mid','left')
                        ->where('b.aid',aid)
                        ->where('b.id','in',$bids)
                        ->order($order)
                        ->page($pagenum,$pernum)
                        ->select()
                        ->toArray();

                    if (getcustom('business_agent_referrer_independent',aid)) {
                        $independent = Db::name('business_sysset')->where('aid',aid)->value('referrer_independent')?:0;
                        if($independent){
                            foreach ($datalist as $k => $v) {
                                $indeMid = Db::name('business_referrer_independent')->where('aid',aid)->where('bid', $v['id'])->value('mid');
                                if ($indeMid && $v['recommend_type'] == 2) {
                                    $referrerInfo = Db::name('member')->where('id', $indeMid)->field('id,nickname')->find();
                                    if ($referrerInfo) {
                                        $datalist[$k]['pid'] = $referrerInfo['id'];
                                        $datalist[$k]['nickname'] = $referrerInfo['nickname'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $data['status'] = 1;
            $data['count'] = $count;
            $data['areaname'] = $areaname;
            $data['datalist'] = $datalist;

            if($opttype && $opttype == 'member_business_area'){
                if(getcustom('member_business_area_tj_count')){
                    if(input('param.districtname')){
                        $data['areaname'] = $provincename.'-'.$cityname.'-'.$districtname;
                    }else{
                        $data['neworders'] = $neworders?$neworders:[];
                        $data['items'] = $newareas?$newareas:[];
                        $data['citydata'] = $data['items']?$data['items'][0]['children']:[];
                        $data['citydata'] = $data['items']?$data['items'][0]['children']:[];
                        $data['provinceArray'] = $provinceArray;
                        $data['cityArray'] = $cityArray;
                        $data['districtArray']= $districtArray;
                        $data['provincename'] = $provincename;
                        $data['cityname']     = $cityname;
                        //$data['districtname'] = $districtname;
                        $data['neworders']    = $neworders;
                    }
                }
            }
            return $this->json($data);
        }
    }

    /**
     * 股东分红/消费统计
     * @author: liud
     * @time: 2025/8/4 14:58
     */
    public function fenhongExpend(){
        if(getcustom('fenhong_gudong_expend_judge')){
            $pernum = 20;
            $pagenum = input('param.pagenum') ? input('param.pagenum') :1;
            $where = '';
            $st = input('param.st/d');
            if(!$st) $st = 1;
            $moeny_weishu = 2;

            //股东分红金额明细
            $newoglist = [];
            if($st == 1){
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['mid','=',mid];
                $where[] = ['remark','=','股东分红'];
                $where[] = ['module','=','shop'];
                $where[] = ['status','=',1];

                $count = Db::name('member_fenhonglog')->where($where)->count();
                $fhlist = Db::name('member_fenhonglog')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();
                foreach($fhlist as $k=>$v) {
                    //根据分红记录获取分红订单信息
                    $ordernum =  Db::name('shop_order_goods')->alias('og')->field('og.ordernum')->where('og.id', 'in', $v['ogids'])->find();
                    $fhlist[$k]['ordernum'] = $ordernum['ordernum'];
                }
            }

            if($st==2){//消费金额明细
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['mid','=',mid];

                $count = Db::name('shop_order')->where($where)->whereRaw('status in(1,2,3) or refund_status in(1,2)')->count();
                $datalist = Db::name('shop_order')->where($where)->whereRaw('status in(1,2,3) or refund_status in(1,2)')->page($pagenum,$pernum)->order('id desc')->field('ordernum,totalprice,createtime')->select()->toArray();
                if(!$datalist) $datalist = [];
                foreach($datalist as $k=>$v){

                }
            }

            //消费金额
            $expend_money = Db::name('shop_order')->where('mid',mid)->where('aid',aid)->whereRaw('status in(1,2,3) or refund_status in(1,2)')->sum('totalprice');
            //股东分红金额
            $fenhong_money = Db::name('member_fenhonglog')->where('mid',mid)->where('aid',aid)->where('status',1)->where('module','shop')->where('remark','股东分红')->sum('commission');

            $rdata = [];
            $rdata['expend_money'] = dd_money_format($expend_money,$moeny_weishu);
            $rdata['fenhong_money'] = dd_money_format($fenhong_money,$moeny_weishu);
            if($st == 1){
                $rdata['datalist'] = $fhlist;
            }else{
                $rdata['datalist'] = $datalist;
            }
            $rdata['count'] = $count;
            $rdata['st'] = $st;
            return $this->json($rdata);
        }
    }

    public function dealareas($business_areadata,$paramProvincename,$paramCityname,$paramDistrictname){
        if(getcustom('member_business_area_tj_count',aid)){
            $newareas = [];
            $areastr = file_get_contents(ROOT_PATH.'static/area.json');
            if($areastr){
                $areas = json_decode($areastr,true);

                $allprovinces = [];//是否添加过整个省
                $allcitys = [];//是否添加过整个市

                //获取整个省，用以排除重复省市区范围
                $dealBusinessAreadata = $this->dealBusinessAreadata($business_areadata,$areas,$newareas,$allprovinces,[],1);
                $allprovinces = $dealBusinessAreadata['allprovinces'];
                $business_areadata = $dealBusinessAreadata['business_areadata'];
                $newareas = $dealBusinessAreadata['newareas'];

                //获取整个市，用以排除重复市区范围
                $dealBusinessAreadata = $this->dealBusinessAreadata($business_areadata,$areas,$newareas,$allprovinces,$allcitys,2);
                $allcitys = $dealBusinessAreadata['allcitys'];
                $business_areadata = $dealBusinessAreadata['business_areadata'];
                $newareas = $dealBusinessAreadata['newareas'];

                //获取区县，排除重复区县范围
                $dealBusinessAreadata = $this->dealBusinessAreadata($business_areadata,$areas,$newareas,$allprovinces,$allcitys,3);
                $business_areadata = $dealBusinessAreadata['business_areadata'];
                $newareas = $dealBusinessAreadata['newareas'];
            }

            $provinceArray = $cityArray = $districtArray = [];
            $provincename = $paramProvincename;$cityname = $paramCityname;$districtname = $paramDistrictname;
            $haveprovince = $havecity = $havedistrict = false; 
            foreach($newareas as $nk => $nv){
                if($paramProvincename == ''){
                    //添加省份
                    $provinceArray[] = $nv['text'];
                    if($nk == 0) $provincename = $nv['text'];
                }
                if($nv['text'] == $provincename){
                    $haveprovince = true;
                    if($nv['children']){
                        foreach($nv['children'] as $nk2 => $nv2){
                            if($paramCityname == ''){
                                //添加城市
                                $cityArray[] = $nv2['text'];
                                if($nk2 == 0) $cityname = $nv2['text'];
                            }

                            if($nv2['text'] == $cityname){
                                $havecity = true;

                                if($nv2['children']){
                                    foreach($nv2['children'] as $nk3 => $nv3){
                                        //添加区县
                                        $districtArray[] = $nv3['text'];
                                        if($districtname && $nv3['text'] == $districtname){
                                            $havedistrict = true;
                                        }
                                    }
                                    unset($nk3);unset($nv3);
                                }

                                if($paramProvincename) break;
                            }
                        }
                        unset($nk2);unset($nv2);
                    }

                    if($paramProvincename) break;
                }
            }
            unset($nk);unset($nv);
            if(!$haveprovince || !$havecity){
                if($paramProvincename){
                    return ['status'=>0,'msg'=>'无查看此区域的权限'];
                }else{
                    return ['status'=>0,'msg'=>'无可查看的区域'];
                }
            }
            if($districtname && !$havedistrict){
                return ['status'=>0,'msg'=>'无查看此区域的权限'];
            }
            return [
                'status'=>1,'newareas'=>$newareas,
                'provinceArray' => $provinceArray,'cityArray' => $cityArray,'districtArray' => $districtArray,
                'provincename' => $provincename,'cityname' => $cityname,'districtname' => $districtname
            ];
        }
    }

    public function dealBusinessAreadata($business_areadata,$areas,$newareas,$allprovinces,$allcitys,$type=1){
        if(getcustom('member_business_area_tj_count',aid)){
            $addprovinces = $addcitys = [];//添加过的省份、添加过的城市
            foreach($business_areadata as $bak=>$bav){
                if(!$bav) continue;
                $areaArr = explode('-',$bav);
                $province = $areaArr[0]??'';
                if(!$province) continue;
                $city = $areaArr[1]??'';
                $district = $areaArr[2]??'';

                if($type == 1){
                    if(!$city && !$district){
                        //此省份未添加进全部省份中，则进行添加
                        if(!in_array($province,$allprovinces)){
                            foreach($areas as $av){
                                if($av['text'] == $province){
                                    $newareas[] = $av;
                                    break;
                                }
                            }
                            unset($av);
                            $allprovinces[] = $province;
                        }else{
                            unset($business_areadata[$bak]);
                            continue;
                        }
                    }else{
                        continue;
                    }
                }else if($type == 2){
                    if($city && !$district){
                        //若有全省份的，则直接跳过此区域
                        if(in_array($province,$allprovinces)){
                            unset($business_areadata[$bak]);
                            continue;
                        }

                        $hasprovince = false;
                        foreach($areas as $av){
                            if($av['text'] == $province){
                                $children = $av['children']??[];
                                if(!$children) continue;

                                $hasprovince = true;
                                //若未添加过省份，则直接添加
                                if(!in_array($province,$addprovinces)){
                                    $addprovinces[] = $province;

                                    $newareasdata = [];
                                    $newareasdata['text'] = $av['text'];
                                    $newareasdata['value'] = $av['value'];
                                    $newareasdata['children'] = [];

                                    $hascity = false;
                                    //城市列表
                                    foreach($children as $cv){
                                        //此市未添加进全部市中，则进行添加
                                        if($cv['text'] == $city){
                                            $hascity = true;
                                            $newareasdata['children'][] = $cv;
                                            $addcitys[] = $province.'-'.$city;
                                            break;
                                        }
                                    }
                                    unset($cv);
                                    if($hascity){
                                        $newareas[] = $newareasdata;
                                    }
                                }else{
                                    $hascity = false;
                                    foreach($newareas as &$nv){
                                        if($nv['text'] == $province){
                                            //城市列表
                                            foreach($children as $cv){
                                                //此市未添加进全部市中，则进行添加
                                                if($cv['text'] == $city && !in_array($province.'-'.$city,$allcitys)){
                                                    $nv['children'][] = $cv;

                                                    $hascity = true;
                                                    $addcitys[] = $province.'-'.$city;
                                                    break;
                                                }
                                            }
                                            unset($cv);
                                            break;
                                        }
                                    }
                                    unset($nv);
                                }
                                break;
                            }
                        }
                        unset($av);

                        if(!$hasprovince){
                            unset($business_areadata[$bak]);
                            continue;
                        }
                    }else{
                        continue;
                    }
                }else if($type == 3){
                    if($city && $district){
                        //若有全省份的，则直接跳过此区域
                        if(in_array($province,$allprovinces)){
                            unset($business_areadata[$bak]);
                            continue;
                        }
                        //若有全市份的，则直接跳过此区域
                        if(in_array($province.'-'.$city,$allcitys)){
                            unset($business_areadata[$bak]);
                            continue;
                        }

                        //查询此省、市、区域数据是否已添加
                        $hasprovince = $hascity = $hasdistrict = false;
                        foreach($newareas as &$nv){
                            if($nv['text'] == $province){
                                $addprovinces[] = $province;
                                $hasprovince = true;

                                foreach($nv['children'] as $nv2){
                                    if($nv2['text'] == $city){
                                        $hascity =  true;

                                        foreach($nv2['children'] as $nv3){
                                            if($nv3['text'] == $district){
                                                $hasdistrict = true;
                                                break;
                                            }
                                        }
                                        unset($nv3);
                                        break;
                                    }
                                }
                                unset($nv2);
                                break;
                            }
                        }
                        unset($nv);

                        //若已添加此取消，则直接跳过
                        if($hasdistrict){
                            unset($business_areadata[$bak]);
                            continue;
                        }

                        foreach($areas as $ak=>$av){
                            if($av['text'] == $province){
                                $children = $av['children']??[];
                                if(!$children) continue;

                                //城市列表
                                foreach($children as $ck=>$cv){
                                    if($cv['text'] == $city){
                                        $children2 = $cv['children']??[];
                                        if(!$children2) continue;

                                        //区域列表
                                        foreach($children2 as $ck2=>$cv2){
                                            if($cv2['text'] == $district){
                                                //未添加此区域，直接添加此区域
                                                if(!$hasprovince){
                                                    $newareadata = [];
                                                    $newareadata['text']    = $areas[$ak]['text'];
                                                    $newareadata['value']   = $areas[$ak]['value'];
                                                    $newareadata['children']= [
                                                        [
                                                            'text'  => $children[$ck]['text'],
                                                            'value' => $children[$ck]['value'],
                                                            'children' => [
                                                                [
                                                                    'text'  => $cv2['text'],
                                                                    'value' => $cv2['value'],
                                                                ]
                                                            ]
                                                        ]
                                                    ];
                                                    $newareas[] = $newareadata;

                                                    $hasprovince = true;
                                                }else if(!$hascity){
                                                    foreach($newareas as &$nv){
                                                        if($nv['text'] == $province){
                                                            $newareadata = [];
                                                            $newareadata['text']    = $children[$ck]['text'];
                                                            $newareadata['value']   = $children[$ck]['value'];
                                                            $newareadata['children']= [
                                                                [
                                                                    'text'  => $cv2['text'],
                                                                    'value' => $cv2['value'],
                                                                ]
                                                            ];
                                                            $nv['children'][] = $newareadata;
                                                            break;
                                                        }
                                                    }
                                                    unset($nv);

                                                    $hascity = true;
                                                }else{
                                                    foreach($newareas as &$nv){
                                                        if($nv['text'] == $province){

                                                            $cityChildren = $nv['children'];
                                                            foreach($cityChildren as &$nv2){
                                                                if($nv2['text'] == $city){
                                                                    $newareadata = [];
                                                                    $newareadata['text']    = $cv2['text'];
                                                                    $newareadata['value']   = $cv2['value'];
                                                                    $nv2['children'][] = $newareadata;
                                                                    break;
                                                                }
                                                            }
                                                            unset($nv2);

                                                            $nv['children'] = $cityChildren;
                                                            break;
                                                        }
                                                    }
                                                    unset($nv);
                                                }
                                                break;
                                            }
                                        }
                                        unset($cl2);unset($cv2);
                                        break;
                                    }
                                }
                                unset($ck);unset($cv);

                                break;
                            }
                        }
                        unset($ak);unset($av);

                        if(!$hasprovince){
                            unset($business_areadata[$bak]);
                            continue;
                        }
                    }else{
                        continue;
                    }
                }
            }
            unset($bak);unset($bav);
            return ['allprovinces'=>$allprovinces,'allcitys'=>$allcitys,'business_areadata'=>$business_areadata,'newareas'=>$newareas];
        }
    }
    
    //团队业绩活动列表
    public function getTeamYejiActivityList(){
        if(getcustom('yx_team_yeji_activity')){
            $pagenum = input('post.pagenum');
            if (!$pagenum) $pagenum = 1;
            $pernum = 20;
            $activity_list = Db::name('team_yeji_activity')->where('aid',aid)->where('status',1)
                ->page($pagenum, $pernum)->order('id desc')->select()->toArray();
            foreach($activity_list as $key => $yeji_set){
                $activitdata = \app\common\Order::getTeamYejiJiangiActivity(aid,$this->member,$yeji_set,1);
                $activity_list[$key]['fenhong'] = dd_money_format($activitdata['fenhong']);
                $activity_list[$key]['yeji'] = dd_money_format($activitdata['yeji']);
                $activity_list[$key]['starttime'] = date('Y-m-d H:i',$yeji_set['starttime']);
                $activity_list[$key]['endtime'] = date('Y-m-d H:i',$yeji_set['endtime']);
                $fafang_fenhong = 0 + Db::name('tem_yeji_activity_log')->where('aid',aid)->where('mid',mid)->where('activity_id',$yeji_set['id'])->sum('money');
                $activity_list[$key]['fafang_fenhong'] = $fafang_fenhong;
            }
            return $this->json(['status'=>1,'datalist'=>$activity_list]);
        }
    }

    public function getMemberTeamYejiLog(){
        if(getcustom('yx_team_yeji_jc')) {
            $pagenum = input('post.pagenum/d', 1);
            $pernum  = 20;
            $data    = Db::name('member_team_yeji_log')
                ->where('aid', aid)
                ->where('mid', mid)
                ->page($pagenum, $pernum)
                ->order('id desc')
                ->select()
                ->toArray();
            $total['month_yeji'] = 0 + Db::name('member_yeji_log')->where('aid',aid)->where('mid',mid)->whereTime('createtime','month')->sum('yeji');
            $total['total_yeji'] = 0 + Db::name('member_yeji_log')->where('aid',aid)->where('mid',mid)->sum('yeji');
            return $this->json(['status' => 1, 'data' => $data, 'total' => $total]);
        }
    }

    //获取我的佣金页面数据
    public function getCommissionPageData(){
        $AgentData = new AgentData(aid,1);
        $field_name = input('field_name');
        $data = [];
        //已提现佣金
        if($field_name=='count3'){
            $count3 = $AgentData->getCommissionPageData(aid,mid,'count3');
            $data['count3'] = $count3;
        }
        //待结算佣金
        if($field_name=='commission_yj'){
            $commission_yj = $AgentData->getCommissionYj(aid,mid);
            $data['commission_yj'] = $commission_yj;
        }
        //团队业绩
        if($field_name=='team_fenhong_yeji'){
            $team_fenhong_yeji = $AgentData->getTeamFenhongYeji(aid,mid,$this->member);
            $data['team_fenhong_yeji'] = $team_fenhong_yeji;
        }
        //今日佣金
        if($field_name=='today_commission'){
            $today_commission = $AgentData->getCommissionCount(aid,mid,'today');
            $data['today_commission'] = $today_commission;
        }
        //昨日佣金
        if($field_name=='yesterday_commission'){
            $yesterday_commission = $AgentData->getCommissionCount(aid,mid,'yesterday');
            $data['yesterday_commission'] = $yesterday_commission;
        }
        //本月佣金
        if($field_name=='month_commission'){
            $month_commission = $AgentData->getCommissionCount(aid,mid,'month');
            $data['month_commission'] = $month_commission;
        }
        //上月佣金
        if($field_name=='last_month_commission'){
            $last_month_commission = $AgentData->getCommissionCount(aid,mid,'last_month');
            $data['last_month_commission'] = $last_month_commission;
        }
        //累计股东分红
        if($field_name=='fenhong'){
            $fenhong = $AgentData->getFenhongCount(aid,mid,'gudong');
            $data['last_month_commission'] = $fenhong;
        }
        //累计待结算股东分红
        if($field_name=='fenhong_yj'){
            $fenhong = $AgentData->getYjFenhongCount(aid,mid,$this->sysset,'gudong');
            $data['last_month_commission'] = $fenhong;
        }
        //已发股东分红
        if($field_name=='gudong_total' && in_array($field_name,['gudong_total','gudong_remain','fenhong_max'])){
            $fenhong_total_res = $AgentData->getGudongTotal(aid,mid,$this->member,$this->sysset,'gudong');
            $data['gudong_total'] = $fenhong_total_res['gudong_total'];
            $data['gudong_remain'] = $fenhong_total_res['gudong_remain'];
            $data['fenhong_max'] = $fenhong_total_res['fenhong_max'];
        }
        //预计股东贡献量分红
        if($field_name=='gongxianfenhong'){
            $fenhong =  $AgentData->getGongxianFenhong(aid,mid,$this->member,$this->sysset);
            $data['gongxianfenhong'] = $fenhong;
        }
        //累计回本股东分红
        if($field_name=='fenhong_huiben'){
            $fenhong =  $AgentData->getFenhongCount(aid,mid,'fenhong_huiben');
            $data['fenhong_huiben'] = $fenhong;
        }
        //累计团队分红
        if($field_name=='teamfenhong'){
            $teamfenhong =  $AgentData->getFenhongCount(aid,mid,'teamfenhong');
            $data['teamfenhong'] = $teamfenhong;
        }
        //待结算团队分红
        if($field_name=='teamfenhong_yj'){
            $teamfenhong_yj =  $AgentData->getYjFenhongCount(aid,mid,'teamfenhong_yj');
            $data['teamfenhong_yj'] = $teamfenhong_yj;
        }
        //累计商家团队分红
        if($field_name=='business_teamfenhong'){
            $business_teamfenhong =  $AgentData->getFenhongCount(aid,mid,'business_teamfenhong');
            $data['business_teamfenhong'] = $business_teamfenhong;
        }
        //待结算商家团队分红
        if($field_name=='business_teamfenhong_yj'){
            $business_teamfenhong_yj =  $AgentData->getYjFenhongCount(aid,mid,'business_teamfenhong');
            $data['business_teamfenhong_yj'] = $business_teamfenhong_yj;
        }
        //累计团队收益
        if($field_name=='teamshouyi'){
            $teamshouyi =  $AgentData->getFenhongCount(aid,mid,'teamfenhong_shouyi');
            $data['teamshouyi'] = $teamshouyi;
        }
        //累计区域代理分红
        if($field_name=='areafenhong'){
            $areafenhong =  $AgentData->getFenhongCount(aid,mid,'areafenhong');
            $data['areafenhong'] = $areafenhong;
        }
        //待结算区域代理分红
        if($field_name=='areafenhong_yj'){
            $areafenhong =  $AgentData->getYjFenhongCount(aid,mid,'areafenhong');
            $data['areafenhong'] = $areafenhong;
        }
        //本月业绩
        if($field_name=='buymoney_thismonth'){
            $buymoney_thismonth =  $AgentData->get_buymoney_thismonth(aid,mid);
            $data['buymoney_thismonth'] = $buymoney_thismonth;
        }
        //直推奖
        if($field_name=='zt_commission'){
            $zt_commission = $AgentData->getCommissionCountBytype(aid,mid,'zt_commission');
            $data['zt_commission'] = $zt_commission;
        }
        //业绩奖
        if($field_name=='yeji_commission'){
            $yeji_commission = $AgentData->getCommissionCountBytype(aid,mid,'yeji_commission');
            $data['yeji_commission'] = $yeji_commission;
        }
        //销售总业绩、销售额、销售业绩收益
        if(in_array($field_name,['totalyeji','coupon_yeji','shouyi'])){
            $level_info = Db::name('member_level')->where('id',$this->member['level_id'])->find();
            $res = $AgentData->getXianxiaYeji(aid,mid,$this->member,$level_info);
            $data['totalyeji'] = $res['totalyeji']??0;
            $data['shouyi'] = $res['shouyi']??0;
            $data['coupon_yeji'] = $res['coupon_yeji']??0;//销售额
        }
        //累计投资分红
        if($field_name=='touzifenhong'){
            $touzifenhong =  $AgentData->getFenhongCount(aid,mid,'touzifenhong');
            $data['touzifenhong'] = $touzifenhong;
        }
        //待结算投资分红
        if($field_name=='touzifenhong_yj'){
            $touzifenhong_yj =  $AgentData->getYjFenhongCount(aid,mid,'touzifenhong_yj');
            $data['touzifenhong_yj'] = $touzifenhong_yj;
        }
        //待发放补贴
        if($field_name=='butie_total') {
            $butie_total = $AgentData->getCommissionButie(aid, mid);
            $data['butie_total'] = $butie_total;
        }
        //已发放补贴
        if($field_name=='butie_send') {
            $butie_send = $AgentData->getCommissionButieSend(aid, mid);
            $data['butie_send'] = $butie_send;
        }
        //团队业绩阶梯奖待结算分红
        if($field_name=='team_yeji_fenhong_yj') {
            $team_yeji_res = $AgentData->getYxTeamYeji(aid, mid, $this->member);
            $data['team_yeji_fenhong_yj'] = $team_yeji_res['team_yeji_fenhong_yj'] ?? 0;
        }
        //当月新增业绩
        if($field_name=='team_yeji_month_addyeji'){
            $month_addyeji = $AgentData->getYxTeamYejiAddYeji(aid,mid,'month');
            $data['month_addyeji'] = $month_addyeji ?? 0;
        }
        //团队业绩阶梯奖总业绩
        if($field_name=='team_yeji_total_addyeji') {
            $total_addyeji = $AgentData->getYxTeamYejiAddYeji(aid, mid);
            $data['total_addyeji'] = $total_addyeji ?? 0;
        }
        //运费补贴累计分红
        if($field_name=='teamfenhong_freight'){
            $teamfenhong_freight =  $AgentData->getFenhongCount(aid,mid,'teamfenhong_freight');
            $data['teamfenhong_freight'] = $teamfenhong_freight;
        }
        //待结算运费补贴
        if($field_name=='teamfenhong_freight_yj'){
            $teamfenhong_freight_yj =  $AgentData->getYjFenhongCount(aid,mid,'teamfenhong_freight_yj');
            $data['teamfenhong_freight_yj'] = $teamfenhong_freight_yj;
        }

        $rdata = [];
        $rdata['status'] = 1;
        $rdata['data'] = $data;
        return $this->json($rdata);
    }

    //获取区域地址
    public function getAreaAddress(){
        if(getcustom('business_agent_search')) {
            $province = input('province') ?: "北京市";
            $city = input('city') ?: "北京市";
            $area_json = file_get_contents(ROOT_PATH . '/static/area.json');
            $area = json_decode($area_json, true);
            $province_arr = array_column($area, 'text');
            $city_arr = [];
            $area_arr = [];
            if ($province) {
                foreach ($area as $k => $v) {
                    if ($v['text'] == $province) {
                        $city_arr = array_column($v['children'], 'text');
                        if ($city) {
                            foreach ($v['children'] as $k1 => $v1) {
                                if ($v1['text'] == $city) {
                                    $area_arr = array_column($v1['children'], 'text');
                                    break;
                                }
                            }
                        }
                        break;
                    }
                }
            }
            array_unshift($province_arr, '请选择');
            array_unshift($city_arr, '请选择');
            array_unshift($area_arr, '请选择');
            $rdata = [];
            $rdata['status'] = 1;
            $rdata['province_arr'] = $province_arr;
            $rdata['city_arr'] = $city_arr;
            $rdata['area_arr'] = $area_arr;

            return $this->json($rdata);
        }
    }

    public function teamManage(){
        if(getcustom('team_manage')){
            $mid = input('?param.mid')?input('param.mid/d'):mid;
            //查询查看下级等级权限
            $team_showtel = Db::name('member_level')->where('id',$this->member['levelid'])->where('aid',aid)->value('team_showtel');
            if(request()->isPost()){
                $pernum = 20;
                $pagenum = input('post.pagenum');
                if(!$pagenum) $pagenum = 1;

                $where = [];
                $where[] = ['pid','=',$mid];
                $teamid = input('?param.teamid')?input('param.teamid/d'):0;
                if($teamid) $where[] = ['id','=',$teamid];
                $teamname = input('?param.teamname')?input('param.teamname'):'';
                if($teamname) $where[] = ['nickname','like','%'.$teamname.'%'];
                $checkLevelid = input('?param.checkLevelid')?input('param.checkLevelid/d'):0;
                if($checkLevelid) $where[] = ['levelid','=',$checkLevelid];
                $where[] = ['aid','=',aid];
                $datalist = Db::name('member')->where($where)->page($pagenum,$pernum)->field('id,headimg,nickname,realname,tel,pid,levelid,createtime')->order('id desc')->select()->toArray();
                if($datalist){
                    $levels = Db::name('member_level')->where('aid',aid)->column('name','id');
                    foreach($datalist as &$dv){
                        if(!$team_showtel) $dv['tel'] = '';
                        $dv['levelname'] = $levels[$dv['levelid']]??'';
                        $dv['createtime'] = date('Y-m-d H:i:s',$dv['createtime']);
                    }
                    unset($dv);
                }
                $rdata = [];
                $rdata['status'] = 1;
                $rdata['datalist'] = $datalist;
                return $this->json($rdata);
            }else{

                $userinfo = Db::name('member')->where('id',$mid)->where('aid',aid)->field('id,headimg,nickname,realname,tel,pid,levelid,createtime,path')->find();
                if(!$userinfo) return $this->json(['status'=>0,'msg'=>'用户不存在']);
                if(!$team_showtel) $userinfo['tel'] = '';
                
                $pathArr = !empty($userinfo['path'])?explode(',',$userinfo['path']):[];
                if($mid != mid && !in_array(mid,$pathArr))  return $this->json(['status'=>0,'msg'=>'无权限查看此用户']);
                $userinfo['createtime'] = date('Y-m-d H:i:s',$userinfo['createtime']);

                $levelname = Db::name('member_level')->where('id',$userinfo['levelid'])->value('name');
                $userinfo['levelname'] = $levelname??'';

                //直属用户
                $userinfo['childnum'] = Db::name('member')->where('pid',$mid)->where('aid',aid)->count();
                $userinfo['childOrderpirce'] = Db::name('shop_order')->alias('so')
                    ->join('member m','m.id = so.mid')
                    ->where('m.pid',$mid)->where('so.aid',aid)->where('so.status','>=',1)->where('so.status','<=',3)->sum('so.totalprice');
                $userinfo['childOrderpirce'] = round($userinfo['childOrderpirce'],2);
                //总用户
                $downmids =  \app\common\Member::getdowntotalmids(aid,$mid);
                $userinfo['childnum2'] = count($downmids);
                $userinfo['childOrderpirce2'] = 0;
                if($downmids){
                    $userinfo['childOrderpirce2'] = Db::name('shop_order')->alias('so')
                        ->join('member m','m.id = so.mid')
                        ->where('m.id','in',$downmids)->where('so.aid',aid)->where('so.status','>=',1)->where('so.status','<=',3)->sum('so.totalprice');
                    $userinfo['childOrderpirce2'] = round($userinfo['childOrderpirce2'],2);
                }

                //今日佣金
                $userinfo['todayCommission'] = Db::name('member_commissionlog')->where('aid',aid)->where('mid',$mid)->where('commission','>',0)
                    ->where('createtime','>',strtotime(date('Y-m-d')))->sum('commission');
                $userinfo['todayCommission'] = round($userinfo['todayCommission'],2);

                //所有级别
                $allLevel = Db::name('member_level')->field('id,sort,name')->where('aid',aid)->select()->toArray();

                if($mid !=mid ){
                    //待结算
                    $userinfo['commission0'] = Db::name('member_commission_record')->where('mid',$mid)->where('status',0)->where('commission','>',0)->where('aid',aid)->sum('commission');
                    //已结算
                    $userinfo['commission1'] = Db::name('member_commission_record')->where('mid',$mid)->where('status',1)->where('commission','>',0)->where('aid',aid)->sum('commission');
                    //总提成
                    $userinfo['commission2'] = $userinfo['commission0'] + $userinfo['commission1'];

                    //完成订单数
                    $orderEndnum = 0;
                    $where = [];
                    $where[] = ['mid', '=', $mid];
                    $where[] = ['status', '=', 3];
                    $where[] = ['aid', '=', aid];
                    $orderEndnum += Db::name('shop_order')->where($where)->count();
                    // $orderEndnum += Db::name('collage_order')->where($where)->count();
                    // $orderEndnum += Db::name('seckill_order')->where($where)->count();
                    // $orderEndnum += Db::name('tuangou_order')->where($where)->count();
                    // $orderEndnum += Db::name('kanjia_order')->where($where)->count();
                    // $orderEndnum += Db::name('yuyue_order')->where($where)->count();
                    // $orderEndnum += Db::name('restaurant_takeaway_order')->where($where)->count();
                    // $orderEndnum += Db::name('restaurant_shop_order')->where($where)->count();
                    // $orderEndnum += Db::name('cycle_order')->where($where)->count();
                    // $orderEndnum += Db::name('lucky_collage_order')->where($where)->count();
                    // $orderEndnum += Db::name('scoreshop_order')->where($where)->count();

                    // $orderEndnum += Db::name('maidan_order')->where('mid',$mid)->where('status',1)->where('aid',aid)->count();
                    // if(getcustom('maidan_new')){
                    //     $orderEndnum += Db::name('maidan_new_order')->where('mid',$mid)->where('status',1)->where('aid',aid)->count();
                    // }
                    $userinfo['orderEndnum'] = $orderEndnum;

                    //订单总金额
                    $orderTotalprice = 0;
                    $where = [];
                    $where[] = ['mid', '=', $mid];
                    $where[] = ['status', '=', 3];
                    $where[] = ['aid', '=', aid];
                    $orderTotalprice += Db::name('shop_order')->where($where)->sum('totalprice');
                    // $orderTotalprice += Db::name('collage_order')->where($where)->sum('totalprice');
                    // $orderTotalprice += Db::name('seckill_order')->where($where)->sum('totalprice');
                    // $orderTotalprice += Db::name('tuangou_order')->where($where)->sum('totalprice');
                    // $orderTotalprice += Db::name('kanjia_order')->where($where)->sum('totalprice');
                    // $orderTotalprice += Db::name('yuyue_order')->where($where)->sum('totalprice');
                    // $orderTotalprice += Db::name('restaurant_takeaway_order')->where($where)->sum('totalprice');
                    // $orderTotalprice += Db::name('restaurant_shop_order')->where($where)->sum('totalprice');
                    // $orderTotalprice += Db::name('cycle_order')->where($where)->sum('totalprice');
                    // $orderTotalprice += Db::name('lucky_collage_order')->where($where)->sum('totalprice');
                    // $orderTotalprice += Db::name('scoreshop_order')->where($where)->sum('totalprice');

                    // $orderTotalprice += Db::name('maidan_order')->where('mid',$mid)->where('status',1)->where('aid',aid)->sum('paymoney');
                    // if(getcustom('maidan_new')){
                    //     $orderTotalprice += Db::name('maidan_new_order')->where('mid',$mid)->where('status',1)->where('aid',aid)->sum('paymoney');
                    // }
                    $userinfo['orderTotalprice'] = round($orderTotalprice,2);

                }

                $rdata = [];
                $rdata['status'] = 1;
                $rdata['userinfo'] = $userinfo??[];
                $rdata['allLevel'] = $allLevel??[];
                return $this->json($rdata);
            }
        }
    }

    public function myIncome()
    {
        if(getcustom('team_manage')){
            if(request()->isPost()){
                $range = input('param.range/d',1);//时间范围，1今天 2昨天 3本月 4上月
                switch ($range){
                    case 5://自定义
                        $rangeType = input('param.rangType/d',1);
                        if($rangeType==1){
                            $month = input('param.month','');
                            if(empty($month)){
                                return $this->json(['status'=>0,'msg'=>'请选择月份']);
                            }
                            $endtime = strtotime(date($month.'-01 00:00:00'));
                            $days = date('t',$endtime-10);
                            $starttime = $endtime - 86400 * $days;
                            $step = 86400*5;
                        }else{
                            $start_date = input('param.start_date');
                            $end_date = input('param.end_date');
                            if(empty($start_date) || empty($end_date)){
                                return $this->json(['status'=>0,'msg'=>'请选择开始和结束时间']);
                            }
                            $starttime = strtotime($start_date);
                            $endtime = strtotime($end_date)+86400;
                            if($endtime>time()){
                                $endtime = time();
                            }
                            if($endtime<$starttime){
                                return $this->json(['status'=>0,'msg'=>'结束时间不得小于开始日期']);
                            }
                            if($endtime-$starttime<=86400){
                                $step = 3600*4;
                            }elseif ($endtime-$starttime<=7*86400){
                                $step = 86400;
                            }else{
                                $step = ceil(($endtime - $starttime)/12);
                            }
                        }
                        break;
                    case 4:
                        $endtime = strtotime(date('Y-m-01 00:00:00'));
                        $days = date('t',$endtime-10);
                        $starttime = $endtime - 86400 * $days;
                        $step = 86400*5;
                        break;
                    case 3:
                        $starttime = strtotime(date('Y-m-01 00:00:00'));
                        $endtime = strtotime(date('Y-m-d 24:00:00',time()));
                        $days = ($endtime-$starttime)/86400;
                        $step = ceil($days/6) * 86400;
                        break;
                    case 2:
                        $endtime = strtotime(date('Y-m-d 00:00:00'));
                        $starttime = $endtime - 86400;
                        $step = 3600*4;
                        break;
                    default:
                        $starttime = strtotime(date('Y-m-d 00:00:00'));
                        $endtime = strtotime(date('Y-m-d H:i:s',time()));
                        $hours = ($endtime - $starttime)/3600;
                        $step = ceil($hours/6) * 3600;
                        break;
                }
                $where = [];
                $where[] = ['aid','=',aid];
                $where[] = ['mid','=',mid];
                $where[] = ['commission','>',0];
                $where[] = ['createtime','between',[$starttime,$endtime]];
                $totalCommission= round(Db::name('member_commissionlog')->where($where)->sum('commission'),2);

                $xData = $yData = [];
                for($time=$starttime;$time<=$endtime;$time=$time+$step){

                    $endfor = false;
                    $nowtime = $time+$step;
                    //本月
                    if($range == 3 || $range == 5){
                        if($nowtime>time()){
                            $nowtime = time();
                            $endfor = true;
                        } 
                    //上个月
                    }else if($range == 4){
                        if($nowtime>=$endtime){
                            $nowtime = $endtime-1;
                            $endfor = true;
                        } 
                    }
                    $whereT = [];
                    $whereT[] = ['aid','=',aid];
                    $whereT[] = ['mid','=',mid];
                    $whereT[] = ['commission','>',0];
                    $whereT[] = ['createtime','between',[$starttime,$nowtime]];
                    $commissionlogs = Db::name('member_commissionlog')->where($whereT)->select()->toArray();
                    $times = 0;
                    $commission = 0;
                    foreach ($commissionlogs as $k=>$log){
                        $commission = $commission+$log['commission'];
                        $times++;
                    }
                    if($step>3600*4){
                        $xData[] = date('m/d',$nowtime);
                    }else{
                        $xData[] = date('H:i',$nowtime);
                    }
                    $yData[] = round($commission,2);
                    $yData1[] = $times;

                    if($endfor) break;
                }
                $chartdata = [
                    'xData'=>$xData,//日期
                    'yData'=>$yData,//收益金额
                ];
                return $this->json(['status'=>1,'totalCommission'=>$totalCommission,'chartdata'=>$chartdata,'curdate'=>date('Y-m-d',time())]);
            }
        }
    }
}
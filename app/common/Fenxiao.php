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
class Fenxiao
{
    public static function fenxiao($sysset,$member,$product,$num,$commission_totalprice,$isfg=0,$istc1=0,$istc2=0,$istc3=0,$commission_totalprice_pj=0,$params=[]){
        $aid = $sysset['aid'];
        $ogupdate = [];
        if($product['commissionset']!=-1){
            if(getcustom('extend_planorder')){
                //如果是排队系统里的店铺下单，分销仅给店铺人员，其他人员不发奖
                if($params['poshopid'] && $params['poshopid']>0){
                    $member['pid']  = $member['path'] = $params['poshopmid'];
                }
            }
            if(getcustom('shop_product_commission_memberset')){
                //商品分销员ID，若有分销员ID，给分销员及分销员上级发奖
                if($params['procommissionmid'] && $params['procommissionmid']>0){
                    $member['pid']  = $params['procommissionmid'];
                    $member['path'] = '';
                    //查询分销员上级path
                    $path = Db::name('member')->where('id',$member['pid'])->where('aid',$aid)->value('path');
                    if($path && !empty($path)){
                        $member['path'] = $path.','.$member['pid'];
                    }else{
                        $member['path'] = $member['pid'];
                    }
                }
            }
            //开启紧缩，团队分红极差作为开关
            if(getcustom('commission_jinsuo') && $sysset['fx_jinsuo']==1){
                //var_dump($member['path']);
                if($member['path']){
                    $parentList = Db::name('member')->where('id','in',$member['path'])->order(Db::raw('field(id,'.$member['path'].')'))->select()->toArray();
                    if($parentList){
                        $parentList = array_reverse($parentList);
                        $level_lists = Db::name('member_level')->where('aid',$aid)->column('*','id');
                        $parent_arr = [];
                        $agleveldata_arr = [];
                        //循环推荐网体，按代数查找提成比例大于0的上级，提成为0的紧缩掉不算一代
                        $dai = 1;
                        foreach($parentList as $k=>$parent){
                            if($dai>3){
                                break;
                            }
                            $level_data = $level_lists[$parent['levelid']]??[];
                            //没级别 紧缩掉
                            if(!$level_data){
                                //var_dump($parent['id'].'紧缩1');
                                continue;
                            }
                            //未开启分销权限，或下单会员不在指定等级ID中紧缩掉
                            if($level_data['can_agent']==0 || ($level_data['commission_appointlevelid'] && !in_array($member['levelid'],explode(',',$level_data['commission_appointlevelid'])))){
                                //var_dump($parent['id'].'紧缩2');
                                continue;
                            }
                            //提成比例为0的紧缩掉(应客户要求，只按一代比例做判断)
                            //$commission_dai = $level_data['commission'.$dai];//(此处为按会员当前所处层级的拿奖比例做判断)
                            $commission_dai = $level_data['commission1'];
                            $commission_socre = 0;
                            if(getcustom('maidan_commission_score')){
                                $commission_socre = $level_data['maidan_commission_score1'];
                            }

                            if($commission_dai<=0 && $commission_socre<=0){
                                //var_dump($parent['id'].'紧缩3');
                                continue;
                            }

                            $parent_arr[$dai] = $parent;
                            $agleveldata_arr[$dai] = $level_data;
                            $dai++;
                        }
                        //上一代会员
                        $parent1 = $parent_arr[1]??'';
                        $agleveldata1 = $agleveldata_arr[1]??'';
                        $ogupdate['parent1'] = $parent1['id']??0;
                        $ogupdate['parent1_levelid'] = $parent1['levelid']??0;
                        //上二代会员
                        $parent2 = $parent_arr[2]??'';
                        $agleveldata2 = $agleveldata_arr[2]??'';
                        $ogupdate['parent2'] = $parent2['id']??0;
                        $ogupdate['parent2_levelid'] = $parent2['levelid']??0;
                        //上三代会员
                        $parent3 = $parent_arr[3]??'';
                        $agleveldata3 = $agleveldata_arr[3]??'';
                        $ogupdate['parent3'] = $parent3['id']??0;
                        $ogupdate['parent3_levelid'] = $parent3['levelid']??0;

                    }
                }
            }else{
                if(getcustom('commission_product_self_buy')){
                    //商品中开启了 自购佣金，会员等级中自己一级佣金就不再生效
                    if($product['commissionselfbuyset'] !=-1){
                        //ApiShop(大概line9750)中，如果会员等级开启了自己拿一级佣金，pid被重置为下单人id，开启自购后，一级佣金不生效，所以给返回为原pid
                        $selfbuy_pid = Db::name('member')->where('aid',$aid)->where('id',$member['id'])->value('pid');
                        $member['pid'] = $selfbuy_pid;
                    }
                }
                if($member['pid']){
                    $parent1 = Db::name('member')->where('aid',$aid)->where('id',$member['pid'])->find();
                    if($parent1){
                        $agleveldata1 = Db::name('member_level')->where('aid',$aid)->where('id',$parent1['levelid'])->find();
                        if($agleveldata1['can_agent']!=0 && (!$agleveldata1['commission_appointlevelid'] || in_array($member['levelid'],explode(',',$agleveldata1['commission_appointlevelid'])))){
                            $ogupdate['parent1'] = $parent1['id'];
                            $ogupdate['parent1_levelid'] = $parent1['levelid']??0;
                        }
                    }
                }

                if(getcustom('extend_planorder')){
                    //如果是排队系统里的店铺下单，分销仅给店铺人员，其他人员不发奖
                    if($params['poshopid'] && $params['poshopid']>0){
                        if($parent1) $parent1['pid']  = 0;
                    }
                }

                if(getcustom('commission_gangwei') && $sysset['gangwei_give_origin_status'] == 2){
                    if($parent1['pid_origin']){
                        $parent1['pid'] = $parent1['pid_origin'];
                    }
                }

                if($parent1['pid']){
                    $parent2 = Db::name('member')->where('aid',$aid)->where('id',$parent1['pid'])->find();
                    if($parent2){
                        $agleveldata2 = Db::name('member_level')->where('aid',$aid)->where('id',$parent2['levelid'])->find();
                        if(($agleveldata2['can_agent']>1 || $agleveldata2['commission_parent']>0) && (!$agleveldata2['commission_appointlevelid'] || in_array($member['levelid'],explode(',',$agleveldata2['commission_appointlevelid'])))){
                            $ogupdate['parent2'] = $parent2['id'];
                            $ogupdate['parent2_levelid'] = $parent2['levelid']??0;
                        }
                    }
                }
                if(getcustom('commission_gangwei') && $sysset['gangwei_give_origin_status'] == 2){
                    if($parent2['pid_origin']){
                        $parent2['pid'] = $parent2['pid_origin'];
                    }
                }
                if($parent2['pid']){
                    $parent3 = Db::name('member')->where('aid',$aid)->where('id',$parent2['pid'])->find();
                    if($parent3){
                        $agleveldata3 = Db::name('member_level')->where('aid',$aid)->where('id',$parent3['levelid'])->find();
                        if(($agleveldata3['can_agent']>2 || $agleveldata3['commission_parent']>0) && (!$agleveldata3['commission_appointlevelid'] || in_array($member['levelid'],explode(',',$agleveldata3['commission_appointlevelid'])))){
                            $ogupdate['parent3'] = $parent3['id'];
                            $ogupdate['parent3_levelid'] = $parent3['levelid']??0;
                        }
                    }
                }
                if(getcustom('agent_to_origin')){
                    //一级分销发放给原推荐人
                    $member_level = Db::name('member_level')->where('aid',$aid)->where('id',$member['levelid'])->find();
                    if($member['pid_origin']>0 && $member_level['agent_to_origin']==1){
                        $parent1 = Db::name('member')->where('aid',$aid)->where('id',$member['pid_origin'])->find();
                        if($parent1){
                            $agleveldata1 = Db::name('member_level')->where('aid',$aid)->where('id',$parent1['levelid'])->find();
                            if($agleveldata1['can_agent']!=0 && (!$agleveldata1['commission_appointlevelid'] || in_array($member['levelid'],explode(',',$agleveldata1['commission_appointlevelid'])))){
                                $ogupdate['parent1'] = $parent1['id'];
                            }
                        }
                    }
                }

                $up_change_pid = getcustom('up_change_pid',$aid);
                if(getcustom('yx_collage_jipin_optimize',$aid)){
                    //即拼活动判断一级分销发给现推荐人还是原推荐人
                    //查询商品所属活动
                    if(db('collage_jipin_set')->where('aid',$aid)->where('status',1)->find()){
                        if(!$hd_info = db('collage_jipin_set')->where('aid',$aid)->where('fwtype',0)->where('status',1)->where('find_in_set('.$product['id'].',productids)')->find()){
                            $cid_arr = explode(',', $product['cid']);
                            if($cid_arr){
                                //先循环这个商品的每个分类
                                foreach ($cid_arr as $ck => $cv){
                                    //查询所有分类活动
                                    if($cv){
                                        if($hd_info = db('collage_jipin_set')->where('aid',$aid)->where('fwtype',1)->where('status',1)->where('find_in_set('.$cv.',categoryids)')->find()){
                                            break;
                                        }
                                    }
                                }
                            }
                        }

                        if($hd_info){
                            $ogupdate['collage_jipin_id'] = $hd_info['id'] ?? 0;
                            $jinp_pid = 0;

                            if(!$up_change_pid){
                                $hd_info['commissionse_touser'] = 0;
                            }

                            //开团直推奖发放人 0优先现上级 1优先原上级
                            if($hd_info['commissionse_touser'] == 0){
                                if($member['pid']){
                                    $jinp_pid = $member['pid'];
                                }elseif ($member['pid_origin']){
                                    $jinp_pid = $member['pid_origin'];
                                }
                            }else if($hd_info['commissionse_touser'] == 1){
                                if($member['pid_origin']){
                                    $jinp_pid = $member['pid_origin'];
                                }elseif ($member['pid']){
                                    $jinp_pid = $member['pid'];
                                }
                            }

                            if($jinp_pid > 0){
                                //发放一级分销
                                $parent1 = Db::name('member')->where('aid',$aid)->where('id',$jinp_pid)->find();
                                if($parent1){
                                    $agleveldata1 = Db::name('member_level')->where('aid',$aid)->where('id',$parent1['levelid'])->find();
                                    if($agleveldata1['can_agent']!=0 && (!$agleveldata1['commission_appointlevelid'] || in_array($member['levelid'],explode(',',$agleveldata1['commission_appointlevelid'])))){
                                        $ogupdate['parent1'] = $parent1['id'];
                                    }
                                }
                            }
                        }
                    }

                }
            }
            if(getcustom('commission_gangwei') && $sysset['gangwei_give_origin_status'] == 2){
                if($parent3['pid_origin']){
                    $parent3['pid'] = $parent3['pid_origin'];
                }
            }
            if($parent3['pid']){
                $parent4 = Db::name('member')->where('aid',$aid)->where('id',$parent3['pid'])->find();
                if($parent4){
                    $agleveldata4 = Db::name('member_level')->where('aid',$aid)->where('id',$parent4['levelid'])->find();
                    if($product['commissionpingjiset'] != 0){
                        if($product['commissionpingjiset'] == 1){
                            $commissionpingjidata1 = json_decode($product['commissionpingjidata1'],true);
                            $agleveldata4['commission_parent_pj'] = $commissionpingjidata1[$agleveldata4['id']];
                        }elseif($product['commissionpingjiset'] == 2){
                            $commissionpingjidata2 = json_decode($product['commissionpingjidata2'],true);
                            $agleveldata4['commission_parent_pj'] = $commissionpingjidata2[$agleveldata4['id']];
                        }else{
                            $agleveldata4['commission_parent_pj'] = 0;
                        }
                    }
                    //持续推荐奖励
                    if($agleveldata4['can_agent'] > 0 && ($agleveldata4['commission_parent'] > 0 || ($parent4['levelid']==$parent3['levelid'] && $agleveldata4['commission_parent_pj'] > 0))){
                        $ogupdate['parent4'] = $parent4['id'];
                    }
                }
            }

            if(getcustom('commission_down_firstone_order',$aid)){
                //判断下级是否是首单才发分销奖励
                //查询用户有效订单数量
                $yyforder = Db::name('shop_order')->where('aid',$aid)->where('mid',$member['id'])->where('status','in',[1,2,3])->count();

                if($agleveldata1 && $agleveldata1['commission_first_order'] == 1 && $yyforder >= 1){
                    $ogupdate['parent1'] = $ogupdate['parent1_levelid'] = 0;
                }

                if($agleveldata2 && $agleveldata2['commission_first_order'] == 1 && $yyforder >= 1){
                    $ogupdate['parent2'] = $ogupdate['parent2_levelid'] = 0;
                }

                if($agleveldata3 && $agleveldata3['commission_first_order'] == 1 && $yyforder >= 1){
                    $ogupdate['parent3'] = $ogupdate['parent2_levelid'] = 0;
                }

                if($agleveldata4 && $agleveldata4['commission_first_order'] == 1 && $yyforder >= 1){
                    $ogupdate['parent4'] = $ogupdate['parent2_levelid'] = 0;
                }
            }

            if(getcustom('commission_teamnum')){
                //未成组推荐奖 10人一组，未成组的单独一个奖金比例，仅针对会员等级设置
                if($agleveldata1['commission_teamnum']>0){
                    if($parent1 && $agleveldata1 && $agleveldata1['commission1']!=$agleveldata1['commission1_team']){
                        $lv1_teamnum = Db::name('member')->where('pid',$parent1['id'])->count();
                        $sort1_num = Db::name('member')->where('pid',$parent1['id'])->where('createtime','<=',$member['createtime'])->count();
                        $is_group = self::check_teamgroup($agleveldata1['commission_teamnum'], $lv1_teamnum,$sort1_num);
                        if(!$is_group){
                            //未成组
                            $agleveldata1['commission1'] = $agleveldata1['commission1_team'];
                        }
                    }
                    if($parent2 && $agleveldata2 && $agleveldata2['commission2']!=$agleveldata2['commission2_team']){
                        $lv2_mids = \app\common\Member::getdownmids($aid,$parent2['id'],2);
                        $lv2_teamnum = count($lv2_mids);
                        $sort2_num = Db::name('member')->where('id','in',$lv2_mids)->where('createtime','<=',$member['createtime'])->count();
                        $is_group = self::check_teamgroup($agleveldata2['commission_teamnum'], $lv2_teamnum,$sort2_num);
                        if(!$is_group){
                            //未成组
                            $agleveldata2['commission2'] = $agleveldata2['commission2_team'];
                        }
                    }
                    if($parent3 && $agleveldata3 && $agleveldata3['commission2']!=$agleveldata3['commission2_team']){
                        $lv3_mids = \app\common\Member::getdownmids($aid,$parent3['id'],3);
                        $lv3_teamnum = count($lv3_mids);
                        $sort3_num = Db::name('member')->where('id','in',$lv3_mids)->where('createtime','<=',$member['createtime'])->count();
                        $is_group = self::check_teamgroup($agleveldata3['commission_teamnum'], $lv3_teamnum,$sort3_num);
                        if($is_group){
                            //未成组
                            $agleveldata3['commission3'] = $agleveldata3['commission3_team'];
                        }
                    }
                }
            }
            $order_member_commission = 0;//记录下单人作为一级分销时的佣金，仅仅是作为记录处理级差使用，实际下单人不拿奖
            if(getcustom('commission_jicha_ordermember') && $sysset['commission_jicha_ordermember'] == 1) {
                //一级要大于下单人级别才可以拿分销佣金
                $order_member_level = Db::name('member_level')->where('aid', $aid)->where('id', $member['levelid'])->find();
                $order_member_levelsort = $order_member_level['sort'];
            }
            if($product['commissionset']==1){//按商品设置的分销比例
                $commissiondata = json_decode($product['commissiondata1'],true);
                if($commissiondata){
                    if($agleveldata1) $ogupdate['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * $commission_totalprice * 0.01;
                    if($agleveldata2) $ogupdate['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * $commission_totalprice * 0.01;
                    if($agleveldata3) $ogupdate['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * $commission_totalprice * 0.01;
                    if(getcustom('commission_butie')){
                        $commissionbutie = json_decode($product['commissionbutie2'],true);
                        if($agleveldata1) $ogupdate['parent1commission_butie'] = $commissionbutie[$agleveldata1['id']]['commission1'] * $ogupdate['parent1commission'] * 0.01;
                        if($agleveldata2) $ogupdate['parent2commission_butie'] = $commissionbutie[$agleveldata2['id']]['commission2'] * $ogupdate['parent2commission'] * 0.01;
                        if($agleveldata3) $ogupdate['parent3commission_butie'] = $commissionbutie[$agleveldata3['id']]['commission3'] * $ogupdate['parent3commission'] * 0.01;
                    }
                    if(getcustom('commission_jicha_ordermember') && $sysset['commission_jicha_ordermember'] == 1) {
                        //记录下单人作为一级分销时的佣金，仅仅是作为记录处理级差使用，实际下单人不拿奖
                        $order_member_commission = $commissiondata[$member['levelid']]['commission1'] * $commission_totalprice * 0.01;
                    }
                }
            }elseif($product['commissionset']==2){//按固定金额
                $commissiondata = json_decode($product['commissiondata2'],true);
                if($commissiondata){
                    if(getcustom('fengdanjiangli') && $product['fengdanjiangli']){

                    }else{
                        if($agleveldata1) $ogupdate['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * $num;
                        if($agleveldata2) $ogupdate['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * $num;
                        if($agleveldata3) $ogupdate['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * $num;
                        if(getcustom('commission_butie')){
                            $commissionbutie = json_decode($product['commissionbutie'],true);
                            if($agleveldata1) $ogupdate['parent1commission_butie'] = $commissionbutie[$agleveldata1['id']]['commission1'];
                            if($agleveldata2) $ogupdate['parent2commission_butie'] = $commissionbutie[$agleveldata2['id']]['commission2'];
                            if($agleveldata3) $ogupdate['parent3commission_butie'] = $commissionbutie[$agleveldata3['id']]['commission3'];
                        }
                    }
                    if(getcustom('commission_jicha_ordermember') && $sysset['commission_jicha_ordermember'] == 1) {
                        //记录下单人作为一级分销时的佣金，仅仅是作为记录处理级差使用，实际下单人不拿奖
                        $order_member_commission = $commissiondata[$member['levelid']]['commission1'] * $num;
                    }
                }
            }elseif($product['commissionset']==3){//提成是积分
                $commissiondata = json_decode($product['commissiondata3'],true);
                if($commissiondata){
                    if($agleveldata1) $ogupdate['parent1score'] = $commissiondata[$agleveldata1['id']]['commission1'] * $num;
                    if($agleveldata2) $ogupdate['parent2score'] = $commissiondata[$agleveldata2['id']]['commission2'] * $num;
                    if($agleveldata3) $ogupdate['parent3score'] = $commissiondata[$agleveldata3['id']]['commission3'] * $num;
                }
                if(getcustom('commission_jicha_ordermember') && $sysset['commission_jicha_ordermember'] == 1) {
                    //记录下单人作为一级分销时的佣金，仅仅是作为记录处理级差使用，实际下单人不拿奖
                    $order_member_score = $commissiondata[$member['levelid']]['commission1'] * $num;
                }
            }elseif($product['commissionset']==5){//提成比例+积分
                $commissiondata = json_decode($product['commissiondata1'],true);
                if($commissiondata){
                    if($agleveldata1) $ogupdate['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * $commission_totalprice * 0.01;
                    if($agleveldata2) $ogupdate['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * $commission_totalprice * 0.01;
                    if($agleveldata3) $ogupdate['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * $commission_totalprice * 0.01;
                    if(getcustom('commission_butie')){
                        $commissionbutie = json_decode($product['commissionbutie2'],true);
                        if($agleveldata1) $ogupdate['parent1commission_butie'] = $commissionbutie[$agleveldata1['id']]['commission1'] * $ogupdate['parent1commission'] * 0.01;
                        if($agleveldata2) $ogupdate['parent2commission_butie'] = $commissionbutie[$agleveldata2['id']]['commission2'] * $ogupdate['parent2commission'] * 0.01;
                        if($agleveldata3) $ogupdate['parent3commission_butie'] = $commissionbutie[$agleveldata3['id']]['commission3'] * $ogupdate['parent3commission'] * 0.01;
                    }
                }
                $commissiondata = json_decode($product['commissiondata3'],true);
                if($commissiondata){
                    if($agleveldata1) $ogupdate['parent1score'] = $commissiondata[$agleveldata1['id']]['commission1'] * $num;
                    if($agleveldata2) $ogupdate['parent2score'] = $commissiondata[$agleveldata2['id']]['commission2'] * $num;
                    if($agleveldata3) $ogupdate['parent3score'] = $commissiondata[$agleveldata3['id']]['commission3'] * $num;
                }
                if(getcustom('commission_jicha_ordermember',$aid) && $sysset['commission_jicha_ordermember'] == 1) {
                    //记录下单人作为一级分销时的佣金，仅仅是作为记录处理级差使用，实际下单人不拿奖
                    $order_member_score = $commissiondata[$member['levelid']]['commission1'] * $num;
                }
            }elseif($product['commissionset']==6){//提成金额+积分
                $commissiondata = json_decode($product['commissiondata2'],true);
                if($commissiondata){
                    if(getcustom('fengdanjiangli') && $product['fengdanjiangli']){

                    }else{
                        if($agleveldata1) $ogupdate['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * $num;
                        if($agleveldata2) $ogupdate['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * $num;
                        if($agleveldata3) $ogupdate['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * $num;
                        if(getcustom('commission_butie')){
                            $commissionbutie = json_decode($product['commissionbutie'],true);
                            if($agleveldata1) $ogupdate['parent1commission_butie'] = $commissionbutie[$agleveldata1['id']]['commission1'];
                            if($agleveldata2) $ogupdate['parent2commission_butie'] = $commissionbutie[$agleveldata2['id']]['commission2'];
                            if($agleveldata3) $ogupdate['parent3commission_butie'] = $commissionbutie[$agleveldata3['id']]['commission3'];
                        }
                    }
                }
                $commissiondata = json_decode($product['commissiondata3'],true);
                if($commissiondata){
                    if($agleveldata1) $ogupdate['parent1score'] = $commissiondata[$agleveldata1['id']]['commission1'] * $num;
                    if($agleveldata2) $ogupdate['parent2score'] = $commissiondata[$agleveldata2['id']]['commission2'] * $num;
                    if($agleveldata3) $ogupdate['parent3score'] = $commissiondata[$agleveldata3['id']]['commission3'] * $num;
                }
                if(getcustom('commission_jicha_ordermember') && $sysset['commission_jicha_ordermember'] == 1) {
                    //记录下单人作为一级分销时的佣金，仅仅是作为记录处理级差使用，实际下单人不拿奖
                    $order_member_commission = $commissiondata[$member['levelid']]['commission1'] * $num;
                    $order_member_score = $commissiondata[$member['levelid']]['commission1'] * $num;
                }
            }elseif($product['commissionset']==7){//提成积分比例
                $commissiondata = json_decode($product['commissiondata4'],true);
                if($commissiondata){
                    if($agleveldata1) $ogupdate['parent1score'] = round($commissiondata[$agleveldata1['id']]['commission1'] * $commission_totalprice * 0.01);
                    if($agleveldata2) $ogupdate['parent2score'] = round($commissiondata[$agleveldata2['id']]['commission2'] * $commission_totalprice * 0.01);
                    if($agleveldata3) $ogupdate['parent3score'] = round($commissiondata[$agleveldata3['id']]['commission3'] * $commission_totalprice * 0.01);
                }
                if(getcustom('commission_jicha_ordermember') && $sysset['commission_jicha_ordermember'] == 1) {
                    //记录下单人作为一级分销时的佣金，仅仅是作为记录处理级差使用，实际下单人不拿奖
                    $order_member_score = $commissiondata[$member['levelid']]['commission1'] * $commission_totalprice * 0.01;
                }
            }elseif($product['commissionset']==8){//提成比例+提成余额比例
                $commissiondata = json_decode($product['commissiondata1'],true);
                if($commissiondata){
                    if($agleveldata1) $ogupdate['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * $commission_totalprice * 0.01;
                    if($agleveldata2) $ogupdate['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * $commission_totalprice * 0.01;
                    if($agleveldata3) $ogupdate['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * $commission_totalprice * 0.01;
                    if(getcustom('commission_butie')){
                        $commissionbutie = json_decode($product['commissionbutie2'],true);
                        if($agleveldata1) $ogupdate['parent1commission_butie'] = $commissionbutie[$agleveldata1['id']]['commission1'] * $ogupdate['parent1commission'] * 0.01;
                        if($agleveldata2) $ogupdate['parent2commission_butie'] = $commissionbutie[$agleveldata2['id']]['commission2'] * $ogupdate['parent2commission'] * 0.01;
                        if($agleveldata3) $ogupdate['parent3commission_butie'] = $commissionbutie[$agleveldata3['id']]['commission3'] * $ogupdate['parent3commission'] * 0.01;
                    }
                }
                $commissiondata = json_decode($product['commissiondata4'],true);
                if($commissiondata){
                    if($agleveldata1) $ogupdate['parent1money'] = round($commissiondata[$agleveldata1['id']]['commission1'] * $commission_totalprice * 0.01);
                    if($agleveldata2) $ogupdate['parent2money'] = round($commissiondata[$agleveldata2['id']]['commission2'] * $commission_totalprice * 0.01);
                    if($agleveldata3) $ogupdate['parent3money'] = round($commissiondata[$agleveldata3['id']]['commission3'] * $commission_totalprice * 0.01);
                }
                if(getcustom('commission_jicha_ordermember') && $sysset['commission_jicha_ordermember'] == 1) {
                    //记录下单人作为一级分销时的佣金，仅仅是作为记录处理级差使用，实际下单人不拿奖
                    $order_member_commission = $commissiondata[$member['levelid']]['commission1'] * $commission_totalprice * 0.01;
                    $order_member_money = $commissiondata[$member['levelid']]['commission1'] * $commission_totalprice * 0.01;
                }
            }elseif($product['commissionset']==9){//提成比例+提成现金比例
                if(getcustom('commission_xianjin_percent')){
                    $commissiondata = json_decode($product['commissiondata1'],true);
                    if($commissiondata){
                        if($agleveldata1) $ogupdate['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * $commission_totalprice * 0.01;
                        if($agleveldata2) $ogupdate['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * $commission_totalprice * 0.01;
                        if($agleveldata3) $ogupdate['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * $commission_totalprice * 0.01;
                        if(getcustom('commission_butie')){
                            $commissionbutie = json_decode($product['commissionbutie2'],true);
                            if($agleveldata1) $ogupdate['parent1commission_butie'] = $commissionbutie[$agleveldata1['id']]['commission1'] * $ogupdate['parent1commission'] * 0.01;
                            if($agleveldata2) $ogupdate['parent2commission_butie'] = $commissionbutie[$agleveldata2['id']]['commission2'] * $ogupdate['parent2commission'] * 0.01;
                            if($agleveldata3) $ogupdate['parent3commission_butie'] = $commissionbutie[$agleveldata3['id']]['commission3'] * $ogupdate['parent3commission'] * 0.01;
                        }
                    }
                    $commissiondata = json_decode($product['commissiondata5'],true);
                    if($commissiondata){
                        if($agleveldata1) $ogupdate['parent1xianjin'] = round($commissiondata[$agleveldata1['id']]['commission1'] * $commission_totalprice * 0.01);
                        if($agleveldata2) $ogupdate['parent2xianjin'] = round($commissiondata[$agleveldata2['id']]['commission2'] * $commission_totalprice * 0.01);
                        if($agleveldata3) $ogupdate['parent3xianjin'] = round($commissiondata[$agleveldata3['id']]['commission3'] * $commission_totalprice * 0.01);
                    }
                }
            }
            elseif($product['commissionset']==10){//提成比例+提成现金比例
                if(getcustom('yx_buyer_subsidy')){
                    $commissiondata = json_decode($product['commissiondata1'],true);
                    if($commissiondata){
                        if($agleveldata1) $ogupdate['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * $commission_totalprice * 0.01;
                        if($agleveldata2) $ogupdate['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * $commission_totalprice * 0.01;
                        if($agleveldata3) $ogupdate['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * $commission_totalprice * 0.01;
                        if(getcustom('commission_butie')){
                            $commissionbutie = json_decode($product['commissionbutie2'],true);
                            if($agleveldata1) $ogupdate['parent1commission_butie'] = $commissionbutie[$agleveldata1['id']]['commission1'] * $ogupdate['parent1commission'] * 0.01;
                            if($agleveldata2) $ogupdate['parent2commission_butie'] = $commissionbutie[$agleveldata2['id']]['commission2'] * $ogupdate['parent2commission'] * 0.01;
                            if($agleveldata3) $ogupdate['parent3commission_butie'] = $commissionbutie[$agleveldata3['id']]['commission3'] * $ogupdate['parent3commission'] * 0.01;
                        }
                    }
                    $commissiondata = json_decode($product['commissiondata6'],true);
                    if($commissiondata){
                        if($agleveldata1) $ogupdate['parent1subsidyscore'] = round($commissiondata[$agleveldata1['id']]['commission1'] * $commission_totalprice * 0.01);
                        if($agleveldata2) $ogupdate['parent2subsidyscore'] = round($commissiondata[$agleveldata2['id']]['commission2'] * $commission_totalprice * 0.01);
                        if($agleveldata3) $ogupdate['parent3subsidyscore'] = round($commissiondata[$agleveldata3['id']]['commission3'] * $commission_totalprice * 0.01);
                    }
                }
            }
            elseif($product['commissionset']==11){//提成金额+种子数量
                if(getcustom('yx_farm',$aid)){
                    $commissiondata = json_decode($product['commissiondata2'],true);
                    if($commissiondata){
                        if($agleveldata1) $ogupdate['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * $num;
                        if($agleveldata2) $ogupdate['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * $num;
                        if($agleveldata3) $ogupdate['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * $num;
                    }
                    $commissiondata = json_decode($product['commissiondata6'],true);
                    if($commissiondata){
                        if($agleveldata1) $ogupdate['parent1farmseed'] = $commissiondata[$agleveldata1['id']]['commission1'] * $num;
                        if($agleveldata2) $ogupdate['parent2farmseed'] = $commissiondata[$agleveldata2['id']]['commission2'] * $num;
                        if($agleveldata3) $ogupdate['parent3farmseed'] = $commissiondata[$agleveldata3['id']]['commission3'] * $num;
                    }
                }
            }
            else{ //按会员等级设置的分销比例
                if ($agleveldata1) {
                    if (getcustom('commission_fugou') && $isfg == 1) {
                        $agleveldata1['commission1'] = $agleveldata1['commission4'];
                    }
                    if ($agleveldata1['commissiontype'] == 1) { //固定金额按单
                        if ($istc1 == 0) {
                            $ogupdate['parent1commission'] = $agleveldata1['commission1'];
                            $istc1 = 1;
                        }
                    } else {
                        $ogupdate['parent1commission'] = $agleveldata1['commission1'] * $commission_totalprice * 0.01;
                    }

                    //省代特殊奖
                    if (getcustom('commission_shengdai_special') && $sysset['commission_shengdai_special'] == 1) {
                        $shengdai_special_commission1 = Db::name('member_level')->where('aid',$aid)->where('id',$member['levelid'])->field('commissiontype,shengdai_special_commission1,can_agent')->find();
                        if($shengdai_special_commission1['shengdai_special_commission1'] > 0){
                            if ($shengdai_special_commission1['commissiontype'] == 1) { //固定金额按单
                                if ($istc1 == 0) {
                                    $ogupdate['parent1commission'] = $shengdai_special_commission1['shengdai_special_commission1'];
                                    $istc1 = 1;
                                }
                            } else {
                                $ogupdate['parent1commission'] = $shengdai_special_commission1['shengdai_special_commission1'] * $commission_totalprice * 0.01;
                            }
                        }

                    }
                }
                if ($agleveldata2) {
                    if (getcustom('commission_fugou') && $isfg == 1) {
                        $agleveldata2['commission2'] = $agleveldata2['commission5'];
                    }
                    if ($agleveldata2['commissiontype'] == 1) {
                        if ($istc2 == 0) {
                            $ogupdate['parent2commission'] = $agleveldata2['can_agent']>1?$agleveldata2['commission2']:0;
                            $istc2 = 1;
                        }
                    } else {
                        $ogupdate['parent2commission'] = $agleveldata2['can_agent']>1?($agleveldata2['commission2'] * $commission_totalprice * 0.01):0;
                    }

                    //省代特殊奖
                    if (getcustom('commission_shengdai_special') && $sysset['commission_shengdai_special'] == 1) {
                        $shengdai_special_commission2 = Db::name('member_level')->where('aid',$aid)->where('id',$member['levelid'])->field('shengdai_special_commission2,commissiontype,can_agent')->find();
                        if($shengdai_special_commission2['shengdai_special_commission2'] > 0){
                            if ($shengdai_special_commission2['commissiontype'] == 1) {
                                if ($istc2 == 0) {
                                    $ogupdate['parent2commission'] = $shengdai_special_commission2['can_agent']>1?$shengdai_special_commission2['shengdai_special_commission2']:0;
                                    $istc2 = 1;
                                }
                            } else {
                                $ogupdate['parent2commission'] = $shengdai_special_commission2['can_agent']>1?($shengdai_special_commission2['shengdai_special_commission2'] * $commission_totalprice * 0.01):0;
                            }
                        }
                    }
                }
                if ($agleveldata3) {
                    if (getcustom('commission_fugou') && $isfg == 1) {
                        $agleveldata3['commission3'] = $agleveldata3['commission6'];
                    }

                    if ($agleveldata3['commissiontype'] == 1) {
                        if ($istc3 == 0) {
                            $ogupdate['parent3commission'] = $agleveldata3['can_agent']>2?$agleveldata3['commission3']:0;
                            $istc3 = 1;
                        }
                    } else {
                        $ogupdate['parent3commission'] = $agleveldata3['can_agent']>2?($agleveldata3['commission3'] * $commission_totalprice * 0.01):0;
                    }

                    //省代特殊奖
                    if (getcustom('commission_shengdai_special') && $sysset['commission_shengdai_special'] == 1) {
                        $shengdai_special_commission3 = Db::name('member_level')->where('aid',$aid)->where('id',$member['levelid'])->field('shengdai_special_commission3,commissiontype,can_agent')->find();
                        if($shengdai_special_commission3['shengdai_special_commission3'] > 0){
                            if ($shengdai_special_commission3['commissiontype'] == 1) {
                                if ($istc3 == 0) {
                                    $ogupdate['parent3commission'] = $shengdai_special_commission3['can_agent']>2?$shengdai_special_commission3['shengdai_special_commission3']:0;
                                    $istc3 = 1;
                                }
                            } else {
                                $ogupdate['parent3commission'] = $shengdai_special_commission3['can_agent']>2?($shengdai_special_commission3['shengdai_special_commission3'] * $commission_totalprice * 0.01):0;
                            }
                        }
                    }
                }
                if(getcustom('commission_jicha_ordermember',$aid) && $sysset['commission_jicha_ordermember'] == 1) {
                    //记录下单人作为一级分销时的佣金，仅仅是作为记录处理级差使用，实际下单人不拿奖
                    if ($order_member_level['commissiontype'] == 1) { //固定金额按单
                        $order_member_commission = $order_member_level['commission1'];
                    } else {
                        $order_member_commission = $order_member_level['commission1'] * $commission_totalprice * 0.01;
                    }
                }
            }
            //级差
            if((($product['fx_differential'] == -1 && $sysset['fx_differential'] == 1) || $product['fx_differential'] == 1)  && in_array($product['commissionset'],[0,1,2,5,6,10,11])){
                if(getcustom('commission_jicha_ordermember',$aid) && $sysset['commission_jicha_ordermember'] == 1){
                    //分销的比例要大于下单人才可以拿奖
                    if($agleveldata1['sort']<=$order_member_levelsort){
                        $ogupdate['parent1commission'] = 0;
                    }
                    if($agleveldata2['sort']<=$order_member_levelsort){
                        $ogupdate['parent2commission'] = 0;
                    }
                    if($agleveldata3['sort']<=$order_member_levelsort){
                        $ogupdate['parent3commission'] = 0;
                    }
                    $cha2_0 = $ogupdate['parent1commission'] - $order_member_commission;
                    $ogupdate['parent1commission'] = $cha2_0 > 0 ? $cha2_0 : 0;
                }
                if($ogupdate['parent2commission'] > 0) {
                    $cha2_1 = $ogupdate['parent2commission'] - $ogupdate['parent1commission']-$order_member_commission;
                    $ogupdate['parent2commission'] = $cha2_1 > 0 ? $cha2_1 : 0;
                    if(getcustom('commission_xianjin_percent')){
                        $cha2_1_xianjin = $ogupdate['parent2xianjin'] - $ogupdate['parent1xianjin'];
                        $ogupdate['parent2xianjin'] = $cha2_1_xianjin > 0 ? $cha2_1_xianjin : 0;
                    }
                    if(getcustom('yx_buyer_subsidy')){
                        $cha2_1_subsidyscore = $ogupdate['parent2subsidyscore'] - $ogupdate['parent1subsidyscore'];
                        $ogupdate['parent2subsidyscore'] = $cha2_1_subsidyscore > 0 ? $cha2_1_subsidyscore : 0;
                    }
                    if(getcustom('yx_farm')){
                        $cha2_1_farmseed = $ogupdate['parent2farmseed'] - $ogupdate['parent1farmseed'];
                        $ogupdate['parent2farmseed'] = $cha2_1_farmseed > 0 ? $cha2_1_farmseed : 0;
                    }
                }
                if($ogupdate['parent3commission'] > 0) {
                    $cha3_1 = $ogupdate['parent3commission'] - $ogupdate['parent2commission'] - $ogupdate['parent1commission']-$order_member_commission;
                    $ogupdate['parent3commission'] = $cha3_1 > 0 ? $cha3_1 : 0;
                    if(getcustom('commission_xianjin_percent')){
                        $cha3_1_xianjin = $ogupdate['parent3xianjin'] - $ogupdate['parent2xianjin'] - $ogupdate['parent1xianjin'];
                        $ogupdate['parent3xianjin'] = $cha3_1_xianjin > 0 ? $cha3_1_xianjin : 0;
                    }
                    if(getcustom('yx_buyer_subsidy')){
                        $cha3_1_subsidyscore = $ogupdate['parent3subsidyscore'] - $ogupdate['parent2subsidyscore'] - $ogupdate['parent1subsidyscore'];
                        $ogupdate['parent3subsidyscore'] = $cha3_1_subsidyscore > 0 ? $cha3_1_subsidyscore : 0;
                    }
                    if(getcustom('yx_farm')){
                        $cha3_1_farmseed = $ogupdate['parent3farmseed'] - $ogupdate['parent2farmseed'] - $ogupdate['parent1farmseed'];
                        $ogupdate['parent3farmseed'] = $cha3_1_farmseed > 0 ? $cha3_1_farmseed : 0;
                    }
                }
                if(getcustom('commission_butie',$aid)){
                    //分销补贴也跟随级差
                    if($ogupdate['parent2commission_butie'] > 0) {
                        $cha2_1_butie = $ogupdate['parent2commission_butie'] - $ogupdate['parent1commission_butie'];
                        $ogupdate['parent2commission_butie'] = $cha2_1_butie > 0 ? $cha2_1_butie : 0;
                    }
                    if($ogupdate['parent3commission_butie'] > 0) {
                        $cha3_1_butie = $ogupdate['parent3commission_butie'] - $ogupdate['parent2commission_butie'] - $ogupdate['parent1commission_butie'];
                        $ogupdate['parent3commission_butie'] = $cha3_1_butie > 0 ? $cha3_1_butie : 0;
                    }
                }
            }
            //计算完级差之后再计算持续推荐奖
            if($product['commissionset']==0){
                if ($agleveldata2) {
                    if ($agleveldata2['commissiontype'] == 1) {
                        //持续推荐奖励
                        if ($agleveldata2['commission_parent'] > 0 && $ogupdate['parent1']) {
                            //持续推荐奖单独放一个参数，再上一级计算持续推荐奖时只用下级的分销提成
                            $ogupdate['parent2commission_parent'] = $agleveldata2['commission_parent'];
                        }
                    } else {
                        //持续推荐奖励
                        if ($agleveldata2['commission_parent'] > 0 && $ogupdate['parent1commission'] > 0 && $ogupdate['parent1']) {
                            //持续推荐奖单独放一个参数，再上一级计算持续推荐奖时只用下级的分销提成
                            $ogupdate['parent2commission_parent'] = $ogupdate['parent1commission'] * $agleveldata2['commission_parent'] * 0.01;
                        }
                    }

                }
                if ($agleveldata3) {
                    if ($agleveldata3['commissiontype'] == 1) {
                        //持续推荐奖励
                        if ($agleveldata3['commission_parent'] > 0 && $ogupdate['parent2']) {
                            $ogupdate['parent3commission_parent'] = $agleveldata3['commission_parent'];
                        }
                    } else {
                        //持续推荐奖励
                        if ($agleveldata3['commission_parent'] > 0 && $ogupdate['parent2commission'] > 0 && $ogupdate['parent2']) {
                            $ogupdate['parent3commission_parent'] = $ogupdate['parent2commission'] * $agleveldata3['commission_parent'] * 0.01;
                        }
                    }
                }
                //持续推荐奖励
                if ($agleveldata4['commission_parent'] > 0 && $ogupdate['parent3']) {
                    if ($agleveldata3['commissiontype'] == 1) {
                        $ogupdate['parent4commission'] = $agleveldata4['commission_parent'];
                    } else {
                        $ogupdate['parent4commission'] = $ogupdate['parent3commission'] * $agleveldata4['commission_parent'] * 0.01;
                    }
                }
            }
            $pj3 = 0;
            $pj2 = 0;
            $pj1 = 0;

            if(getcustom('commission_parent_pj')){
                //获取平级会员
                $res_pj = self::getparentpj($aid,$sysset,$member,$parent1,$parent2,$parent3);
                $parent_pj1 = $res_pj['parent_pj1'];
                $parent_pj2 = $res_pj['parent_pj2'];
                $parent_pj3 = $res_pj['parent_pj3'];
                $level_pj1 = $res_pj['level_pj1'];
                $level_pj2 = $res_pj['level_pj2'];
                $level_pj3 = $res_pj['level_pj3'];

                $ogupdate['parent_pj1'] = $parent_pj1['id']??0;
                $ogupdate['parent_pj2'] = $parent_pj2['id']??0;
                $ogupdate['parent_pj3'] = $parent_pj3['id']??0;
            }

            //平级奖
            if(getcustom('commission_parent_pj') && !getcustom('commission_parent_pj_stop') && !getcustom('commission_parent_pj_by_buyermid') && !getcustom('commission_parent_pj_send_once') && !getcustom('commission_parent_pj_send_once')){
                if($parent_pj3  && ($ogupdate['parent3commission'] > 0 || $sysset['commission_parent_pj_no_limit']==1)){
                    $level_pj3['commissionpingjitype'] = $level_pj3['commissiontype'];
                    if($product['commissionpingjiset'] != 0){
                        if($product['commissionpingjiset'] == 1){
                            $commissionpingjidata1 = json_decode($product['commissionpingjidata1'],true);
                            $level_pj3['commission_parent_pj'] = $commissionpingjidata1[$level_pj3['id']]['commission'];
                        }elseif($product['commissionpingjiset'] == 2){
                            $commissionpingjidata2 = json_decode($product['commissionpingjidata2'],true);
                            $level_pj3['commission_parent_pj'] = $commissionpingjidata2[$level_pj3['id']]['commission'];
                            if($product['commissionpingjiset_num']==1){
                                //按商品数量
                                $level_pj3['commission_parent_pj'] = bcmul($level_pj3['commission_parent_pj'],$num,2);
                            }
                            $level_pj3['commissionpingjitype'] = 1;
                        }else{
                            $level_pj3['commission_parent_pj'] = 0;
                        }
                    }
                    if($level_pj3['commission_parent_pj'] > 0) {
                        if($level_pj3['commissionpingjitype']==0){
                            if(getcustom('commission_parent_pj_jiesuantype') && $sysset['fxjiesuantype_pj']>0){
                                //单独设置了平级奖结算方式
                                $pj3 =  $commission_totalprice_pj * $level_pj3['commission_parent_pj'] * 0.01;
                            }else{
                                $pj3 = $ogupdate['parent3commission'] * $level_pj3['commission_parent_pj'] * 0.01;
                            }
                        } else {
                            $pj3 = $level_pj3['commission_parent_pj'];
                        }
                    }
                }
                if($parent_pj2  && ($ogupdate['parent2commission'] > 0  || $sysset['commission_parent_pj_no_limit']==1 || $commission_totalprice_pj>0) ){
                    $level_pj2['commissionpingjitype'] = $level_pj2['commissiontype'];
                    if($product['commissionpingjiset'] != 0){
                        if($product['commissionpingjiset'] == 1){
                            $commissionpingjidata1 = json_decode($product['commissionpingjidata1'],true);
                            $level_pj2['commission_parent_pj'] = $commissionpingjidata1[$level_pj2['id']]['commission'];
                        }elseif($product['commissionpingjiset'] == 2){
                            $commissionpingjidata2 = json_decode($product['commissionpingjidata2'],true);
                            $level_pj2['commission_parent_pj'] = $commissionpingjidata2[$level_pj2['id']]['commission'];
                            if($product['commissionpingjiset_num']==1){
                                //按商品数量
                                $level_pj2['commission_parent_pj'] = bcmul($level_pj2['commission_parent_pj'],$num,2);
                            }
                            $level_pj2['commissionpingjitype'] = 1;
                        }else{
                            $level_pj2['commission_parent_pj'] = 0;
                        }
                    }
                    if($level_pj2['commission_parent_pj'] > 0){
                        if(!$ogupdate['parent3']){
                            $ogupdate['parent3commission'] = 0;
                            $ogupdate['parent3'] = $parent3['id'];
                        }
                        if($level_pj2['commissionpingjitype'] == 0){
                            if(getcustom('commission_parent_pj_jiesuantype') && $sysset['fxjiesuantype_pj']>0){
                                //单独设置了平级奖结算方式
                                $pj2 = $commission_totalprice_pj * $level_pj2['commission_parent_pj'] * 0.01;;
                            }else{
                                $pj2 = $ogupdate['parent2commission'] * $level_pj2['commission_parent_pj'] * 0.01;
                            }
                        }else{
                            $pj2 = $level_pj2['commission_parent_pj'];
                        }
                    }
                }
                if($parent_pj1  && ($ogupdate['parent1commission'] > 0 || $sysset['commission_parent_pj_no_limit']==1 || $commission_totalprice_pj>0) ){
                    $level_pj1['commissionpingjitype'] = $level_pj1['commissiontype'];
                    if($product['commissionpingjiset'] != 0){
                        if($product['commissionpingjiset'] == 1){
                            $commissionpingjidata1 = json_decode($product['commissionpingjidata1'],true);
                            $level_pj1['commission_parent_pj'] = $commissionpingjidata1[$level_pj1['id']]['commission'];
                        }elseif($product['commissionpingjiset'] == 2){
                            $commissionpingjidata2 = json_decode($product['commissionpingjidata2'],true);
                            $level_pj1['commission_parent_pj'] = $commissionpingjidata2[$level_pj1['id']]['commission'];
                            if($product['commissionpingjiset_num']==1){
                                //按商品数量
                                $level_pj1['commission_parent_pj'] = bcmul($level_pj1['commission_parent_pj'],$num,2);
                            }
                            $level_pj1['commissionpingjitype'] = 1;
                        }else{
                            $level_pj1['commission_parent_pj'] = 0;
                        }
                    }
                    if($level_pj1['commission_parent_pj'] > 0){
                        if(!$ogupdate['parent2']){
                            $ogupdate['parent2commission'] = 0;
                            $ogupdate['parent2'] = $parent2['id'];
                        }
                        if($level_pj1['commissionpingjitype'] == 0){
                            if(getcustom('commission_parent_pj_jiesuantype') && $sysset['fxjiesuantype_pj']>0) {
                                //单独设置了平级奖结算方式
                                $pj1 = $commission_totalprice_pj * $level_pj1['commission_parent_pj'] * 0.01;
                            }else{
                                $pj1 = $ogupdate['parent1commission'] * $level_pj1['commission_parent_pj'] * 0.01;
                            }
                        }else{
                            $pj1 = $level_pj1['commission_parent_pj'];
                        }
                    }
                }
                //平级奖只发一次
                $commission_parent_pj_once = 0;
                if(getcustom('commission_parent_pj_once')){
                    $commission_parent_pj_once = Db::name('admin_set_custom')->where('aid',$aid)->value('commission_parent_pj_once');
                }
                if($commission_parent_pj_once==1){
                    if($pj1>0){
                        $pj2 = 0;
                        $pj3 = 0;
                    }elseif($pj2>0){
                        $pj3=0;
                    }
                }

                if(getcustom('commission_jicha_ordermember') && $sysset['commission_jicha_ordermember'] == 1){
                    //重新计算平级奖
                    $pj3 = 0;
                    $pj2 = 0;
                    $pj1 = 0;
                    $ogupdate['parent_pj1'] = $ogupdate['parent1']??0;
                    $ogupdate['parent_pj2'] = $ogupdate['parent2']??0;
                    $ogupdate['parent_pj3'] = $ogupdate['parent3']??0;
                    //一级分销和下单人相同时拿平级奖
                    if($agleveldata1['can_agent']>=1){
                        if($agleveldata1['sort']==$order_member_levelsort ){
                            $pj1 = $commission_totalprice_pj * $agleveldata1['commission_parent_pj'] * 0.01;
                        }
                    }
                    if($agleveldata1['can_agent']>=2) {
                        //二级分销和一级分销相同且大于下单人级别拿平级奖
                        if ($agleveldata2['sort'] == $agleveldata1['sort'] && $agleveldata2['sort'] >= $order_member_levelsort) {
                            $pj2 = $commission_totalprice_pj * $agleveldata1['commission_parent_pj'] * 0.01;
                        } elseif ($agleveldata2['sort'] == $order_member_levelsort && $agleveldata1['sort'] <= $order_member_levelsort) {
                            //二级分销和下单人级别相同且一级分销小于下单人时拿平级奖
                            $pj2 = $commission_totalprice_pj * $agleveldata1['commission_parent_pj'] * 0.01;
                        }
                    }
                    if($agleveldata1['can_agent']>=3) {
                        //三级分销和二级分销相同且大于下单人级别拿平级奖
                        if ($agleveldata3['sort'] == $agleveldata2['sort'] && $agleveldata3['sort'] >= $order_member_levelsort) {
                            $pj3 = $commission_totalprice_pj * $agleveldata1['commission_parent_pj'] * 0.01;
                        } elseif ($agleveldata3['sort'] == $order_member_levelsort && $agleveldata1['sort'] <= $order_member_levelsort && $agleveldata1['sort'] <= $order_member_levelsort) {
                            //三级分销和下单人级别相同且一级、二级分销小于下单人时拿平级奖
                            $pj3 = $commission_totalprice_pj * $agleveldata1['commission_parent_pj'] * 0.01;
                        }
                    }
                }
                //分销平级奖级差
                $commission_parent_pj_differential = 0;
                if(getcustom('commission_parent_pj_differential')){
                    $commission_parent_pj_differential = $sysset['commission_parent_pj_differential']?:0;
                }
                if($commission_parent_pj_differential){
                    //开启平级级差
                    $pj2 = bcsub($pj2,$pj2,2);
                    if($pj2<=0){
                        $pj2 = 0;
                    }
                    $pj3 = bcsub($pj3,bcsub($pj2,$pj1,2),2);
                    if($pj3<=0){
                        $pj3 = 0;
                    }

                }
                if($pj3>0 && $ogupdate['parent_pj3']){
                    $ogupdate['parent3commission_pj'] = bcadd($ogupdate['parent3commission_pj'],$pj3,2);
                }
                if($pj2>0 && $ogupdate['parent_pj2']){
                    $ogupdate['parent2commission_pj'] = bcadd($ogupdate['parent2commission_pj'],$pj2,2);
                }
                if($pj1>0 && $ogupdate['parent_pj1']){
                    $ogupdate['parent1commission_pj'] = bcadd($ogupdate['parent1commission_pj'],$pj1,2);
                }
            }
            //记录平级奖
            $total_pj = bcadd(bcadd($pj3, $pj2, 2),$pj1,2);
            $ogupdate['total_pj'] = $total_pj;
            /******************************平级后只拿平级奖，不再向上发放分销奖金 start **************************************/
            $commission_parent_pj_stop_product = getcustom('commission_parent_pj_stop_product');//产品单独设置平级奖参数
            if(getcustom('commission_parent_pj_stop') && $member['path']){
                //重新计算平级奖
                $pids = $member['path'];
                $parentList = Db::name('member')->where('id','in',$pids)->order(Db::raw('field(id,'.$pids.')'))->select()->toArray();
                $parentList = array_reverse($parentList);
                $last_level_data = Db::name('member_level')->where('id',$member['levelid'])->find();
                $level_arr = Db::name('member_level')->where('aid',$sysset['aid'])->column('*','id');
                //产品单独设置平级奖参数
                if($commission_parent_pj_stop_product){
                    if($product['commission_parent_pj_status']==1){
                        //单独设置
                        foreach($level_arr as $k=>$v){
                            $level_arr[$k]['commission_parent_pj_status'] = 1;
                            $level_arr[$k]['commission_parent_pj_lv'] = $product['commission_parent_pj_lv'];
                            $level_arr[$k]['commission_parent_pj'] = $product['commission_parent_pj']*$num;
                            $level_arr[$k]['commission_parent_pj_order'] = $product['commission_parent_pj_order'];
                        }
                    }elseif ($product['commission_parent_pj_status']==-1){
                        //关闭平级奖
                        foreach($level_arr as $k=>$v){
                            $level_arr[$k]['commission_parent_pj_status'] = 0;
                        }
                    }
                }
                $i = 0;
                $pjlevelids = [];//已拿平级奖的会员
                foreach($parentList as $parentpj){
                    $i++;
                    if($i==1){
                        $commission_field = 'parent1commission';
                        $parent_field = 'parent1';
                    }elseif($i==2){
                        $commission_field = 'parent2commission';
                        $parent_field = 'parent2';
                    }elseif($i==3){
                        $commission_field = 'parent3commission';
                        $parent_field = 'parent3';
                    }else{
                        $commission_field = 'parent4commission';
                        $parent_field = 'parent4';
                    }
                    $level_data = $level_arr[$parentpj['levelid']];
                    if($level_data['commission_parent_pj_lv']>0 && $level_data['commission_parent_pj_lv']<$i){
                        //超出层级限制
                        if($level_data['id']==$last_level_data['id']){
                            $ogupdate[$commission_field] = 0;
                        }
                        //超出层级限制，跳过
                        continue;
                    }
//                    dump($i.'=>'.$parentpj['id'].'开始,级别'.$last_level_data['id'].'=>'.$level_data['id']);
                    if($level_data['commission_parent_pj_status']==1 && !in_array($level_data['id'],$pjlevelids)){
                        if($level_data['id']==$last_level_data['id']){
                            $pj_bonus = $level_data['commission_parent_pj'];//固定金额
                            $pj_bonus_order = bcmul($level_data['commission_parent_pj_order']/100,$commission_totalprice,2);
                            $pj_bonus = bcadd($pj_bonus,$pj_bonus_order,2);
                            $ogupdate[$commission_field] = $pj_bonus;
                            $ogupdate[$parent_field] = $parentpj['id'];
                            $pjlevelids = [$level_data['id']];
                            if($i>=4){
                                //平级奖只发最近的一个
                                break;
                            }
                        }
                        if($level_data['sort']<$last_level_data['sort']){
                            //设置了平级奖，如果上级会员级别小于下级，那么不发放分销佣金
                            $ogupdate[$commission_field] = 0;
                            continue;
                        }
                    }
                    if($i<4){
                        $last_level_data = $level_data;
                    }
                }
            }
            /******************************平级后只拿平级奖，不再向上发放分销奖金 stop **************************************/
            if(getcustom('commission_parent_pj_by_buyermid')){
                if($member['path']) {
                    $parentList = Db::name('member')->where('id','<>',$member['id'])->where('id', 'in', $member['path'])->order(Db::raw('field(id,' . $member['path'] . ')'))
                        ->where('levelid',$member['levelid'])->select()->toArray();
                    if ($parentList) {
                        $parentList = array_reverse($parentList);
                        $pjMember = $parentList[0];
                        $memberlevel = Db::name('member_level')->where('aid',$aid)->where('id',$member['levelid'])->find();
                        if($memberlevel['commission_parent_pj'] > 0){
                            $memberlevel['commissionpingjitype'] = $memberlevel['commissiontype'];
                            if($product['commissionpingjiset'] != 0){
                                if($product['commissionpingjiset'] == 1){
                                    //单独设置比例
                                    $commissionpingjidata1 = json_decode($product['commissionpingjidata1'],true);
                                    $memberlevel['commission_parent_pj'] = $commissionpingjidata1[$memberlevel['id']]['commission'];
                                    $memberlevel['commissionpingjitype'] = 0;
                                }elseif($product['commissionpingjiset'] == 2){
                                    //单独设置金额
                                    $commissionpingjidata2 = json_decode($product['commissionpingjidata2'],true);
                                    $memberlevel['commission_parent_pj'] = $commissionpingjidata2[$memberlevel['id']]['commission'];
                                    $memberlevel['commissionpingjitype'] = 1;
                                }else{
                                    $memberlevel['commission_parent_pj'] = 0;
                                }
                            }
                            if($memberlevel['commissionpingjitype'] == 0){
                                $pjMemberCommission = $memberlevel['commission_parent_pj'] * $memberlevel['commission_parent_pj'] * 0.01;
                            }else{
                                $pjMemberCommission = $memberlevel['commission_parent_pj'];
                            }
                            $ogupdate['parent4commission'] = $pjMemberCommission;
                            $ogupdate['parent4'] = $pjMember['id'];
                        }
                    }
                }
            }
        }

        if(isset($ogupdate['parent2commission_parent'])){
            $ogupdate['parent2commission'] = bcadd($ogupdate['parent2commission'],$ogupdate['parent2commission_parent'],2);
            $ogupdate['parent2commission_parent'] = 0;
        }
        if(isset($ogupdate['parent3commission_parent'])){
            $ogupdate['parent3commission'] = bcadd($ogupdate['parent3commission'],$ogupdate['parent3commission_parent'],2);
            $ogupdate['parent3commission_parent'] = 0;
        }
        if(getcustom('commission_max_times')){
            //分销份数限制
            if($product['commission_max_times_status']==1){
                $commission_max_times = json_decode($product['commission_max_times'],true);
                if($ogupdate['parent1'] && $agleveldata1){
                   $max_times1 = $commission_max_times[$agleveldata1['id']]['commission1']??0;
                   if($max_times1>0){
                       $where = [];
                       $where[] = ['mid','=',$ogupdate['parent1']];
                       $where[] = ['proid','=',$product['id']];
                       $where[] = ['level','=',1];
                       $where[] = ['status','in',[0,1]];
                       $have_count = Db::name('member_commission_record')->where($where)->count();
                       if($have_count>=$max_times1){
                           $ogupdate['parent1'] = 0;
                           $ogupdate['parent1commission'] = 0;
                           $ogupdate['parent1score'] = 0;
                       }
                   }
                }
                if($ogupdate['parent2'] && $agleveldata2){
                    $max_times2 = $commission_max_times[$agleveldata2['id']]['commission2']??0;
                    if($max_times2>0){
                        $where = [];
                        $where[] = ['mid','=',$ogupdate['parent2']];
                        $where[] = ['proid','=',$product['id']];
                        $where[] = ['level','=',2];
                        $where[] = ['status','in',[0,1]];
                        $have_count = Db::name('member_commission_record')->where($where)->count();
                        if($have_count>=$max_times2){
                            $ogupdate['parent2'] = 0;
                            $ogupdate['parent2commission'] = 0;
                            $ogupdate['parent2score'] = 0;
                        }
                    }
                }
            }
        }
        //分销伯乐奖
        if(getcustom('commission_bole')){
            $bole_parent2 = [];
            $bole_parent3 = [];
            $bole_parent4 = [];
            if($parent1 && $agleveldata1){
                if($agleveldata1['commission_bole_origin'] && $parent1['pid_origin']){
                    $bole_parent2 = Db::name('member')->where('aid',$aid)->where('id',$parent1['pid_origin'])->find();
                }else{
                    $bole_parent2 = Db::name('member')->where('aid',$aid)->where('id',$parent1['pid'])->find();
                }
                $bole_agleveldata2 = Db::name('member_level')->where('id',$bole_parent2['levelid'])->find();
            }
            if($parent2 && $agleveldata2){
                $parent2 = Db::name('member')->where('aid',$aid)->where('id',$ogupdate['parent2'])->find();
                if($agleveldata2['commission_bole_origin'] && $parent2['pid_origin']){
                    $bole_parent3 = Db::name('member')->where('aid',$aid)->where('id',$parent2['pid_origin'])->find();
                }else{
                    $bole_parent3 = Db::name('member')->where('aid',$aid)->where('id',$parent2['pid'])->find();
                }
                $bole_agleveldata3 = Db::name('member_level')->where('id',$bole_parent3['levelid'])->find();
            }
            if($parent3 && $agleveldata3){
                if($agleveldata3['commission_bole_origin'] && $parent3['pid_origin']){
                    $bole_parent4 = Db::name('member')->where('aid',$aid)->where('id',$parent3['pid_origin'])->find();
                }else{
                    $bole_parent4 = Db::name('member')->where('aid',$aid)->where('id',$parent3['pid'])->find();
                }
                $bole_agleveldata4 = Db::name('member_level')->where('id',$bole_parent4['levelid'])->find();
            }
            if($product['commissionboleset']==1){//按商品设置的分销比例
                $commissionboledata1 = json_decode($product['commissionboledata1'],true);
                if($commissionboledata1){
                    if($bole_agleveldata2){
                        $bole_agleveldata2['giveup_percent'] = $commissionboledata1[$bole_agleveldata2['id']]['commission']??0;
                        $bole_agleveldata2['giveup_commission'] = 0;
                    }
                    if($bole_agleveldata3){
                        $bole_agleveldata3['giveup_percent'] = $commissionboledata1[$bole_agleveldata3['id']]['commission']??0;
                        $bole_agleveldata3['giveup_commission'] = 0;
                    }
                    if($bole_agleveldata4){
                        $bole_agleveldata4['giveup_percent'] = $commissionboledata1[$bole_agleveldata4['id']]['commission']??0;
                        $bole_agleveldata4['giveup_commission'] = 0;
                    }
                }
            }elseif($product['commissionboleset']==2){
                $commissionboledata2 = json_decode($product['commissionboledata2'],true);
                if($commissionboledata2){
                    if($bole_agleveldata2) {
                        $bole_agleveldata2['giveup_percent'] = 0;
                        $bole_agleveldata2['giveup_commission'] = $commissionboledata2[$bole_agleveldata2['id']]['commission']??0;
                    }
                    if($bole_agleveldata3){
                        $bole_agleveldata3['giveup_percent'] = 0;
                        $bole_agleveldata3['giveup_commission'] = $commissionboledata2[$bole_agleveldata3['id']]['commission']??0;
                    }
                    if($bole_agleveldata4){
                        $bole_agleveldata4['giveup_percent'] = 0;
                        $bole_agleveldata4['giveup_commission'] = $commissionboledata2[$bole_agleveldata4['id']]['commission']??0;
                    }
                }
            }
            $commossionbole2 = 0;
            $commossionbole3 = 0;
            $commossionbole4 = 0;
            if($product['commissionset']!=-1) {
                if ($bole_agleveldata2 && $bole_parent2) {
                    $commossionbole2 = $bole_agleveldata2['giveup_commission'];
                    if ($bole_agleveldata2['giveup_percent'] > 0 && $ogupdate['parent1commission'] > 0) {
                        $commossionbole2 = $commossionbole2 + $ogupdate['parent1commission'] * $bole_agleveldata2['giveup_percent'] * 0.01;
                    }
                }
                if ($bole_agleveldata3 && $bole_parent3) {
                    $commossionbole3 = $bole_agleveldata3['giveup_commission'];
                    if ($bole_agleveldata3['giveup_percent'] > 0 && $ogupdate['parent2commission'] > 0 ) {
                        $commossionbole3 = $commossionbole3 + $ogupdate['parent2commission'] * $bole_agleveldata3['giveup_percent'] * 0.01;
                    }
                }
                if ($bole_agleveldata4 > 0 && $bole_parent4) {
                    $commossionbole4 = $bole_agleveldata4['giveup_commission'];
                    if ($bole_agleveldata4['giveup_percent'] > 0 && $ogupdate['parent3commission'] > 0 ) {
                        $commossionbole4 = $commossionbole4 + $ogupdate['parent3commission'] * $bole_agleveldata4['giveup_percent'] * 0.01;
                    }
                }
            }
            $ogupdate['parent2_bole'] = $bole_parent2['id']??0;
            $ogupdate['parent3_bole'] = $bole_parent3['id']??0;
            $ogupdate['parent4_bole'] = $bole_parent4['id']??0;
            $ogupdate['parent2commission_bole'] = $commossionbole2??0;
            $ogupdate['parent3commission_bole'] = $commossionbole3??0;
            $ogupdate['parent4commission_bole'] = $commossionbole4??0;
        }
        if(getcustom('commission_product_self_buy')){
            //商品中开启了 自购佣金
            if($product['commissionselfbuyset'] !=-1){
                $selfbuy_commission =0;
                if($product['commissionselfbuyset'] ==1){//比例
                    $commissionselfbuydata1 = json_decode($product['commissionselfbuydata1'],true);
                    if($commissionselfbuydata1){
                        $selfbuy_ratio= $commissionselfbuydata1[$member['levelid']]['commission'];
                        $selfbuy_commission = $commission_totalprice * $selfbuy_ratio * 0.01;
                    }
                }elseif ($product['commissionselfbuyset'] ==2){ //金额
                    $commissionpingjidata2 = json_decode($product['commissionselfbuydata2'],true);
                    $selfbuy_commission =  $commissionpingjidata2[$member['levelid']]['commission'] * $num;
                }
                $ogupdate['selfbuy_commission'] = $selfbuy_commission; 
            }
          
        }
        $ogupdate['istc1'] = $istc1;
        $ogupdate['istc2'] = $istc2;
        $ogupdate['istc3'] = $istc3;
        return $ogupdate;
    }

    public static function fenxiao_jicha($sysset,$member,$product,$num,$commission_totalprice,$commission_totalprice_pj=0,$params=[]){
        if(getcustom('fenxiao_manage')){
            $aid = $sysset['aid'];
        $fenxiao_set = Db::name('fenxiao_manage')->where('aid',$aid)->column('*','key');
        $commission1_bili = 0;
        $commission2_bili = 0;
        $commission3_bili = 0;
        $ogupdate = [];
        if(getcustom('extend_planorder')){
            //如果是排队系统里的店铺下单，分销仅给店铺人员，其他人员不发奖
            if($params['poshopid'] && $params['poshopid']>0){
                $member['pid']  = $member['path'] = $params['poshopmid'];
            }
        }
        if(getcustom('shop_product_commission_memberset')){
            //商品分销员ID，若有分销员ID，给分销员及分销员上级发奖
            if($params['procommissionmid'] && $params['procommissionmid']>0){
                $member['pid']  = $params['procommissionmid'];
                $member['path'] = '';
                //查询分销员上级path
                $path = Db::name('member')->where('id',$member['pid'])->where('aid',$aid)->value('path');
                if($path && !empty($path)){
                    $member['path'] = $path.','.$member['pid'];
                }else{
                    $member['path'] = $member['pid'];
                }
            }
        }

        if($member['pid']){
            $parent1 = Db::name('member')->where('aid',$aid)->where('id',$member['pid'])->find();
            if($parent1){
                $agleveldata1 = Db::name('member_level')->where('aid',$aid)->where('id',$parent1['levelid'])->find();
                if($agleveldata1['can_agent']!=0 ){
                    $ogupdate['parent1'] = $parent1['id'];
                    $ogupdate['parent1_levelid'] = $parent1['levelid']??0;
                    $set_key = $parent1['levelid'].','.$member['levelid'];
                    $commission1_bili = $fenxiao_set[$set_key]['commission1'];
                    //dump($set_key.'=>'.$commission1_bili);
                }
            }
        }
        if(getcustom('extend_planorder')){
            //如果是排队系统里的店铺下单，分销仅给店铺人员，其他人员不发奖
            if($params['poshopid'] && $params['poshopid']>0){
                if($parent1) $parent1['pid']  = 0;
            }
        }

        if(getcustom('commission_gangwei') && $sysset['gangwei_give_origin_status'] == 2){
            if($parent1['pid_origin']){
                $parent1['pid'] = $parent1['pid_origin'];
            }
        }
        if($parent1['pid']){
            $parent2 = Db::name('member')->where('aid',$aid)->where('id',$parent1['pid'])->find();
            if($parent2){
                $agleveldata2 = Db::name('member_level')->where('aid',$aid)->where('id',$parent2['levelid'])->find();
                if($agleveldata2['can_agent']>1){
                    $ogupdate['parent2'] = $parent2['id'];
                    $ogupdate['parent2_levelid'] = $parent2['levelid']??0;
                    $set_key = $parent2['levelid'].','.$member['levelid'];
                    $commission2_bili = $fenxiao_set[$set_key]['commission2'];
                    //dump($set_key.'=>'.$commission2_bili);
                }
            }
        }
        if(getcustom('commission_gangwei') && $sysset['gangwei_give_origin_status'] == 2){
            if($parent2['pid_origin']){
                $parent2['pid'] = $parent2['pid_origin'];
            }
        }
        if($parent2['pid']){
            $parent3 = Db::name('member')->where('aid',$aid)->where('id',$parent2['pid'])->find();
            if($parent3){
                $agleveldata3 = Db::name('member_level')->where('aid',$aid)->where('id',$parent3['levelid'])->find();
                if($agleveldata3['can_agent']>2 ){
                    $ogupdate['parent3'] = $parent3['id'];
                    $ogupdate['parent3_levelid'] = $parent3['levelid']??0;
                    $set_key = $parent3['levelid'].','.$member['levelid'];
                    $commission3_bili = $fenxiao_set[$set_key]['commission3'];
                    //dump($set_key.'=>'.$commission3_bili);
                }
            }
        }
        if(getcustom('commission_gangwei') && $sysset['gangwei_give_origin_status'] == 2){
            if($parent3['pid_origin']){
                $parent3['pid'] = $parent3['pid_origin'];
            }
        }
        if($parent3['pid']){
            $parent4 = Db::name('member')->where('aid',$aid)->where('id',$parent3['pid'])->find();
            if($parent4){
                $agleveldata4 = Db::name('member_level')->where('aid',$aid)->where('id',$parent4['levelid'])->find();
                if($product['commissionpingjiset'] != 0){
                    if($product['commissionpingjiset'] == 1){
                        $commissionpingjidata1 = json_decode($product['commissionpingjidata1'],true);
                        $agleveldata4['commission_parent_pj'] = $commissionpingjidata1[$agleveldata4['id']];
                    }elseif($product['commissionpingjiset'] == 2){
                        $commissionpingjidata2 = json_decode($product['commissionpingjidata2'],true);
                        $agleveldata4['commission_parent_pj'] = $commissionpingjidata2[$agleveldata4['id']];
                    }else{
                        $agleveldata4['commission_parent_pj'] = 0;
                    }
                }
                //持续推荐奖励
                if($agleveldata4['can_agent'] > 0){
                    $ogupdate['parent4'] = $parent4['id'];
                }
            }
        }
        $last_commission_bili = 0;//记录上一次分销奖金比例，计算级差使用
        //按会员等级设置的分销比例
        if ($agleveldata1) {
            $commission_bili = bcsub($commission1_bili,$last_commission_bili,2);
            $last_commission_bili = $commission_bili;
            //dump('parent1commission'.'=>'.$commission_bili);
            $ogupdate['parent1commission'] = bcmul($commission_bili/100,$commission_totalprice,2);
        }
        if ($agleveldata2) {
            $commission_bili = bcsub($commission2_bili,$last_commission_bili,2);
            if($commission_bili<0){
                $commission_bili = 0;
            }else{
                $last_commission_bili = $commission2_bili;
            }
            //dump('parent2commission'.'=>'.$commission2_bili.'=>'.$commission_bili);
            $ogupdate['parent2commission'] = bcmul($commission_bili/100,$commission_totalprice,2);

        }
        if ($agleveldata3) {
            $commission_bili = bcsub($commission3_bili,$last_commission_bili,2);
            if($commission_bili<0){
                $commission_bili = 0;
            }else{
                $last_commission_bili = $commission3_bili;
            }
            //dump('parent2commission'.'=>'.$commission3_bili.'=>'.$commission_bili);
            $ogupdate['parent3commission'] = bcmul($commission_bili/100,$commission_totalprice,2);
        }

            //级差
            if((($product['fx_differential'] == -1 && $sysset['fx_differential'] == 1) || $product['fx_differential'] == 1)  && in_array($product['commissionset'],[0,1,2,5,6])){
                if($ogupdate['parent2commission'] > 0) {
                    $cha2_1 = $ogupdate['parent2commission'] - $ogupdate['parent1commission'];
                    $ogupdate['parent2commission'] = $cha2_1 > 0 ? $cha2_1 : 0;
                }
                if($ogupdate['parent3commission'] > 0) {
                    $cha3_1 = $ogupdate['parent3commission'] - $ogupdate['parent2commission'] - $ogupdate['parent1commission'];
                    $ogupdate['parent3commission'] = $cha3_1 > 0 ? $cha3_1 : 0;
                }
                if(getcustom('commission_butie')){
                    //分销补贴也跟随级差
                    if($ogupdate['parent2commission_butie'] > 0) {
                        $cha2_1_butie = $ogupdate['parent2commission_butie'] - $ogupdate['parent1commission_butie'];
                        $ogupdate['parent2commission_butie'] = $cha2_1_butie > 0 ? $cha2_1_butie : 0;
                    }
                    if($ogupdate['parent3commission_butie'] > 0) {
                        $cha3_1_butie = $ogupdate['parent3commission_butie'] - $ogupdate['parent2commission_butie'] - $ogupdate['parent1commission_butie'];
                        $ogupdate['parent3commission_butie'] = $cha3_1_butie > 0 ? $cha3_1_butie : 0;
                    }
                }
            }

        //平级奖
        if(getcustom('commission_parent_pj') && !getcustom('commission_parent_pj_stop') && !getcustom('commission_parent_pj_by_buyermid')){
            if($agleveldata4 && $ogupdate['parent3'] && $ogupdate['parent3commission'] > 0 && $agleveldata3['id'] == $agleveldata4['id']){
                $agleveldata4['commissionpingjitype'] = $agleveldata4['commissiontype'];
                if($product['commissionpingjiset'] != 0){
                    if($product['commissionpingjiset'] == 1){
                        $commissionpingjidata1 = json_decode($product['commissionpingjidata1'],true);
                        $agleveldata4['commission_parent_pj'] = $commissionpingjidata1[$agleveldata4['id']]['commission'];
                    }elseif($product['commissionpingjiset'] == 2){
                        $commissionpingjidata2 = json_decode($product['commissionpingjidata2'],true);
                        $agleveldata4['commission_parent_pj'] = $commissionpingjidata2[$agleveldata4['id']]['commission'];
                        $agleveldata4['commissionpingjitype'] = 1;
                    }else{
                        $agleveldata4['commission_parent_pj'] = 0;
                    }
                }
                if($agleveldata4['commission_parent_pj'] > 0) {
                    if($agleveldata4['commissionpingjitype']==0){
                        if(getcustom('commission_parent_pj_jiesuantype') && $sysset['fxjiesuantype_pj']>0){
                            //单独设置了平级奖结算方式
                            $ogupdate['parent4commission'] = $commission_totalprice_pj * $agleveldata4['commission_parent_pj'] * 0.01;
                        }else{
                            $ogupdate['parent4commission'] = $ogupdate['parent3commission'] * $agleveldata4['commission_parent_pj'] * 0.01;
                        }
                    } else {
                        $ogupdate['parent4commission'] = $agleveldata4['commission_parent_pj'];
                    }
                    $ogupdate['parent4'] = $parent4['id'];
                }
            }
            if($agleveldata3 && $ogupdate['parent2'] && ($ogupdate['parent2commission'] > 0 || $commission_totalprice_pj>0) && $agleveldata2['id'] == $agleveldata3['id']){
                $agleveldata3['commissionpingjitype'] = $agleveldata3['commissiontype'];
                if($product['commissionpingjiset'] != 0){
                    if($product['commissionpingjiset'] == 1){
                        $commissionpingjidata1 = json_decode($product['commissionpingjidata1'],true);
                        $agleveldata3['commission_parent_pj'] = $commissionpingjidata1[$agleveldata3['id']]['commission'];
                    }elseif($product['commissionpingjiset'] == 2){
                        $commissionpingjidata2 = json_decode($product['commissionpingjidata2'],true);
                        $agleveldata3['commission_parent_pj'] = $commissionpingjidata2[$agleveldata3['id']]['commission'];
                        $agleveldata3['commissionpingjitype'] = 1;
                    }else{
                        $agleveldata3['commission_parent_pj'] = 0;
                    }
                }
                if($agleveldata3['commission_parent_pj'] > 0){
                    if(!$ogupdate['parent3']){
                        $ogupdate['parent3commission'] = 0;
                        $ogupdate['parent3'] = $parent3['id'];
                    }
                    if($agleveldata3['commissionpingjitype'] == 0){
                        if(getcustom('commission_parent_pj_jiesuantype') && $sysset['fxjiesuantype_pj']>0) {
                            //单独设置了平级奖结算方式
                            $ogupdate['parent3commission'] = $ogupdate['parent3commission'] + $commission_totalprice_pj * $agleveldata3['commission_parent_pj'] * 0.01;
                        }else{
                            $ogupdate['parent3commission'] = $ogupdate['parent3commission'] + $ogupdate['parent2commission'] * $agleveldata3['commission_parent_pj'] * 0.01;
                        }
                    }else{
                        $ogupdate['parent3commission'] = $ogupdate['parent3commission'] + $agleveldata3['commission_parent_pj'];
                    }
                }
            }
            if($agleveldata2 && $ogupdate['parent1'] && ($ogupdate['parent1commission'] > 0 || $commission_totalprice_pj>0) && $agleveldata1['id'] == $agleveldata2['id']){
                $agleveldata2['commissionpingjitype'] = $agleveldata2['commissiontype'];
                if($product['commissionpingjiset'] != 0){
                    if($product['commissionpingjiset'] == 1){
                        $commissionpingjidata1 = json_decode($product['commissionpingjidata1'],true);
                        $agleveldata2['commission_parent_pj'] = $commissionpingjidata1[$agleveldata2['id']]['commission'];
                    }elseif($product['commissionpingjiset'] == 2){
                        $commissionpingjidata2 = json_decode($product['commissionpingjidata2'],true);
                        $agleveldata2['commission_parent_pj'] = $commissionpingjidata2[$agleveldata2['id']]['commission'];
                        $agleveldata2['commissionpingjitype'] = 1;
                    }else{
                        $agleveldata2['commission_parent_pj'] = 0;
                    }
                }
                if($agleveldata2['commission_parent_pj'] > 0){
                    if(!$ogupdate['parent2']){
                        $ogupdate['parent2commission'] = 0;
                        $ogupdate['parent2'] = $parent2['id'];
                    }
                    if($agleveldata2['commissionpingjitype'] == 0){
                        if(getcustom('commission_parent_pj_jiesuantype') && $sysset['fxjiesuantype_pj']>0) {
                            //单独设置了平级奖结算方式
                            $ogupdate['parent2commission'] = $ogupdate['parent2commission'] + $commission_totalprice_pj * $agleveldata2['commission_parent_pj'] * 0.01;
                        }else{
                            $ogupdate['parent2commission'] = $ogupdate['parent2commission'] + $ogupdate['parent1commission'] * $agleveldata2['commission_parent_pj'] * 0.01;
                        }
                    }else{
                        $ogupdate['parent2commission'] = $ogupdate['parent2commission'] + $agleveldata2['commission_parent_pj'];
                    }
                }
            }
        }
        /******************************平级后只拿平级奖，不再向上发放分销奖金 start **************************************/
        if(getcustom('commission_parent_pj_stop') && $member['path']){
            //重新计算平级奖
            $pids = $member['path'];
            $parentList = Db::name('member')->where('id','in',$pids)->order(Db::raw('field(id,'.$pids.')'))->select()->toArray();
            $parentList = array_reverse($parentList);
            $last_level_data = Db::name('member_level')->where('id',$member['levelid'])->find();
            $i = 0;
            $pjlevelids = [];//已拿平级奖的会员
            foreach($parentList as $parentpj){
                $i++;
                if($i==1){
                    $commission_field = 'parent1commission';
                    $parent_field = 'parent1';
                }elseif($i==2){
                    $commission_field = 'parent2commission';
                    $parent_field = 'parent2';
                }elseif($i==3){
                    $commission_field = 'parent3commission';
                    $parent_field = 'parent3';
                }else{
                    $commission_field = 'parent4commission';
                    $parent_field = 'parent4';
                }
                $level_data = Db::name('member_level')->where('id',$parentpj['levelid'])->find();
                if($level_data['commission_parent_pj_lv']>0 && $level_data['commission_parent_pj_lv']<$i){
                    //超出层级限制
                    if($level_data['id']==$last_level_data['id']){
                        $ogupdate[$commission_field] = 0;
                    }
                    //超出层级限制，跳过
                    continue;
                }
                //dump($i.'=>'.$parentpj['id'].'开始,级别'.$last_level_data['id'].'=>'.$level_data['id']);
                if($level_data['commission_parent_pj_status']==1 && !in_array($level_data['id'],$pjlevelids)){
                    if($level_data['id']==$last_level_data['id']){
                        $pj_bonus = $level_data['commission_parent_pj'];//固定金额
                        $pj_bonus_order = bcmul($level_data['commission_parent_pj_order']/100,$commission_totalprice,2);
                        $pj_bonus = bcadd($pj_bonus,$pj_bonus_order,2);
                        $ogupdate[$commission_field] = $pj_bonus;
                        $ogupdate[$parent_field] = $parentpj['id'];
                        $pjlevelids = [$level_data['id']];
                        if($i>=4){
                            //平级奖只发最近的一个
                            break;
                        }
                    }
                    if($level_data['sort']<$last_level_data['sort']){
                        //设置了平级奖，如果上级会员级别小于下级，那么不发放分销佣金
                        $ogupdate[$commission_field] = 0;
                        continue;
                    }
                }
                if($i<4){
                    $last_level_data = $level_data;
                }
            }
        }
        /******************************平级后只拿平级奖，不再向上发放分销奖金 stop **************************************/
//                    dump($ogupdate);exit;
        return $ogupdate;
        }
    }
    //退款退还分销佣金
    public static function refundFenxiao($aid,$orderid,$type){
        if(getcustom('commission_orderrefund_deduct')){
            $open_commission_orderrefund_deduct = Db::name('admin_set')->where('aid',$aid)->value('open_commission_orderrefund_deduct');
            if($open_commission_orderrefund_deduct !=1){
                return;
            }
        }
        writeLog('订单退款扣除分销佣金orderid:'.$orderid.'type:'.$type,'commissionrefund');
        $commission_record_list = Db::name('member_commission_record')->where('aid',$aid)->where('orderid',$orderid)->where('status',1)->where('type',$type)->select()->toArray();
        foreach($commission_record_list as $k=>$commission_record){
            if($commission_record['commission'] > 0){
                \app\common\Member::addcommission($aid,$commission_record['mid'],$commission_record['frommid'],-1*$commission_record['commission'],'订单退款扣除'.$commission_record['remark']);
                Db::name('member_commission_record')->where('id',$commission_record['id'])->update(['status'=>2,'endtime'=>time()]);
                    // $tmplcontent = [];
                    // $tmplcontent['first'] = '分销佣金退回'.t('佣金').'：￥'.$commission_record['commission'];
                    // $tmplcontent['remark'] = '点击进入查看~';
                    // $tmplcontent['keyword1'] = '订单退款'; //商品信息
                    // $tmplcontent['keyword2'] = 0;//商品单价
                    // $tmplcontent['keyword3'] = $commission_record['commission'].'元';//商品佣金
                    // $tmplcontent['keyword4'] = date('Y-m-d H:i:s',$commission_record['createtime']);//分销时间
                    // $rs = \app\common\Wechat::sendtmpl($aid,$commission_record['mid'],'tmpl_fenxiaosuccess',$tmplcontent,m_url('pages/my/usercenter', $aid));
            }
        }
        
    }

    //升级给上级发放佣金
    public static function tuiguang_bonus($orderid){
        if(getcustom('ganer_fenxiao')) {
            $order = Db::name('member_levelup_order')->where('id', $orderid)->find();
            $aid = $order['aid'];
            $mid = $order['mid'];
            $member = Db::name('member')->where('id', $order['mid'])->find();
            $parent = [];
            if ($member['pid']) {
                $parent = Db::name('member')->where('id', $member['pid'])->find();
            }
            if (!$parent) {
                return ['status' => 1, 'msg' => '不存在上级'];
            }
            /******************************推广奖 start ***********************************/
            $commission = Db::name('tuiguang_manage')->where('aid', $aid)->where('key', $parent['levelid'] . ',' . $member['levelid'])->value('commission1');
            if ($commission > 0) {
                $data = [
                    'aid' => $aid,
                    'mid' => $parent['id'],
                    'frommid' => $mid,
                    'orderid' => $orderid,
                    'ogid' => $orderid,
                    'type' => 'member_levelup',
                    'commission' => $commission,
                    'score' => 0,
                    'remark' => '下级升级奖励',
                    'createtime' => time(),
                    'status' => 1
                ];
                Db::name('member_commission_record')->insert($data);
                \app\common\Member::addcommission($aid, $parent['id'], $mid, $commission, '下级升级奖励');
            }
            /******************************推广奖 end ***********************************/

            /******************************区域开店奖励 start ***********************************/
            $province = $order['areafenhong_province'];
            $city = $order['areafenhong_city'];
            $area = $order['areafenhong_area'];
            if ($area) {
                //区县代理，查找市级代理
                $levelids = Db::name('member_level')->where('aid', $aid)->where('areafenhong', 'in', [1, 2])->column('id');
                $map = [];
                $map[] = ['areafenhong_province', '=', $province];
                $map[] = ['areafenhong_city', '=', $city];
                //$map[] = ['areafenhong_area','=',$area];
                $map[] = ['levelid', 'in', $levelids];
                $area_members = Db::name('member')->where($map)->select()->toArray();
                if ($area_members) {
                    foreach ($area_members as $area_member) {
                        //dump($area_member['id']);
                        $commission = Db::name('tuiguang_manage')->where('aid', $aid)->where('key', $area_member['levelid'] . ',' . $member['levelid'])->value('commission2');
                        //echo Db::getLastSql();
                        if ($commission > 0) {
                            $data = [
                                'aid' => $aid,
                                'mid' => $area_member['id'],
                                'frommid' => $mid,
                                'orderid' => $orderid,
                                'ogid' => $orderid,
                                'type' => 'member_levelup',
                                'commission' => $commission,
                                'score' => 0,
                                'remark' => '区域邀请奖励',
                                'createtime' => time(),
                                'status' => 1
                            ];
                            Db::name('member_commission_record')->insert($data);
                            \app\common\Member::addcommission($aid, $area_member['id'], $mid, $commission, '区域邀请奖励');
                        }
                    }
                }
            }
            /******************************区域开店奖励 end ***********************************/

            /******************************团队开店奖励 start ***********************************/
            $pids = $member['path'];
            $levelids = Db::name('member_level')->where('aid', $aid)->where('areafenhong', 'in', [1, 2])->column('id');
            $map = [];
            $map[] = ['id', 'in', $pids];
            //$map[] = ['areafenhong_province', '=', $province];
            //$map[] = ['areafenhong_city', '=', $city];
            //$map[] = ['levelid', 'in', $levelids];
            $parentList = Db::name('member')
                ->where($map)
                ->order(Db::raw('field(id,' . $pids . ')'))->select()->toArray();
            $parentList = array_reverse($parentList);
            if ($parentList) {
                $jicha = 0;
                foreach ($parentList as $parent2) {
                    $old_commission = Db::name('tuiguang_manage')->where('aid', $aid)->where('key', $parent2['levelid'] . ',' . $member['levelid'])->value('commission3');
                    $commission = bcsub($old_commission,$jicha,2);
                    if ($commission > 0) {
                        $data = [
                            'aid' => $aid,
                            'mid' => $parent2['id'],
                            'frommid' => $mid,
                            'orderid' => $orderid,
                            'ogid' => $orderid,
                            'type' => 'member_levelup',
                            'commission' => $commission,
                            'score' => 0,
                            'remark' => '团队邀请奖励',
                            'createtime' => time(),
                            'status' => 1
                        ];
                        Db::name('member_commission_record')->insert($data);
                        \app\common\Member::addcommission($aid, $parent2['id'], $mid, $commission, '团队邀请奖励');
                        $jicha = $old_commission;
                    }
                }
            }
            /******************************团队开店奖励 end ***********************************/
            return ['status' => 1, 'msg' => '发放成功！'];
        }
    }

    //统计奖金池
    public static function bonus_poul($orderid,$type){
        if(getcustom('ganer_fenxiao')){
            if($type=='shop'){
                $order = Db::name('shop_order')->where('id',$orderid)->find();
            }else{
                return true;
            }
            $aid = $order['aid'];
            $mid = $order['mid'];

            $have = Db::name('prize_pool_log')->where('aid',$aid)->where('orderid',$orderid)->where('type',$type)->find();
            if($have){
                //已经统计过了
                return true;
            }
            $type = $type;
            $oglist = Db::name('shop_order_goods')->where('orderid',$orderid)->select()->toArray();
            $pool_num = 0;
            foreach($oglist as $og){
                $og_pool_num = $og['real_totalprice'] - $og['cost_price'] * $og['num'];
                $pool_num = bcadd($pool_num,$og_pool_num,2);
            }
            //奖金池总记录
            $set =  Db::name('prize_pool_set')->where('aid',$aid)->find();
            if($set){
                Db::name('prize_pool_set')->where('id',$set['id'])->inc('pool_num',$pool_num)->update();
            }else{
                $data = [];
                $data['aid'] = $aid;
                $data['pool_num'] = $pool_num;
                Db::name('prize_pool_set')->insert($data);
            }
            //奖金池明细
            $log = [];
            $log['num'] = $pool_num;
            $log['mid'] = $mid;
            $log['orderid'] = $orderid;
            $log['type'] = $type;
            $log['aid'] = $aid;
            $log['createtime'] = time();
            $log['remark'] = '商城订单新增';
            Db::name('prize_pool_log')->insert($log);
        }

    }
    //发放奖金
    public function send_prize_pool($levelids,$send_bili,$aid,$send_type=0){
        if(getcustom('ganer_fenxiao')) {
            $pool_set = Db::name('prize_pool_set')->where('aid', $aid)->find();
            if (!$pool_set || $pool_set['pool_num'] <= 0) {
                return ['status' => 0, 'msg' => '奖金池金额为0'];
            }
            $prize_total = bcmul($pool_set['pool_num'], $send_bili / 100, 2);
            if ($prize_total <= 0) {
                return ['status' => 0, 'msg' => '发放奖金为0'];
            }
            foreach($levelids as $levelid=>$bonus_bili){
                $level_prize = bcmul($prize_total,$bonus_bili/100,4);
                $map = [];
                $map[] = ['levelid', '=', $levelid];
                $map[] = ['aid', '=', $aid];
                $member_lists = Db::name('member')->where($map)->select()->toArray();
                if (count($member_lists) <= 0) {
                    //return ['status' => 0, 'msg' => '会员数量为0'];
                    continue;
                }
                $member_count = count($member_lists);
                $avg_prize = bcdiv($level_prize, $member_count, 2);
                if ($avg_prize <= 0) {
                    //return ['status' => 0, 'msg' => '平均奖金为0'];
                    continue;
                }
                //扣除奖金池总量
                Db::name('prize_pool_set')->where('id', $pool_set['id'])->dec('pool_num', $prize_total)->update();

                //发放奖金
                foreach ($member_lists as $member) {
                    \app\common\Member::addcommission($aid, $member['id'], 0, $avg_prize, '奖金池分红');
                    //奖金池明细
                    $log = [];
                    $log['num'] = -$avg_prize;
                    $log['mid'] = $member['id'];
                    $log['orderid'] = 0;
                    $log['type'] = '';
                    $log['aid'] = $aid;
                    $log['levelid'] = $levelid;
                    $log['createtime'] = time();
                    $log['remark'] = '奖金池发放';
                    Db::name('prize_pool_log')->insert($log);
                }
                //记录奖金池发放日志
                $send_log = [];
                $send_log['aid'] = $aid;
                $send_log['pool_num'] = $pool_set['pool_num'];
                $send_log['send_bili'] = $send_bili;
                $send_log['prize_total'] = $prize_total;
                $send_log['levelid'] = $levelid;
                $send_log['level_bili'] = $bonus_bili;
                $send_log['level_prize_total'] = $level_prize;
                $send_log['member_count'] = $member_count;
                $send_log['send_type'] = $send_type;//0手动发放 1自动发放
                $send_log['createtime'] = time();
                Db::name('prize_pool_send_log')->insert($send_log);
            }

            return ['status' => 1, 'msg' => '发放成功'];
        }
    }
   /*
    * 余额或佣金 提现分销 
    *   $type  money_withdraw:余额 commission_withdraw:佣金
    */
    public static function withdrawfeeFenxiao($sysset,$member,$commission_totalprice=0,$type='money_withdraw',$istc1=0,$istc2=0,$istc3=0){
        if(getcustom('money_commission_withdraw_fenxiao')){
            $aid = $member['aid'];
            if($type =='money_withdraw'){
                $withdrawfee_status = $sysset['money_withdrawfee_fenxiao'];
            }else{
                $withdrawfee_status = $sysset['commission_withdrawfee_fenxiao'];
            }
            if($withdrawfee_status  ==-1)  return false;//-1 不参与
            $ogupdate = [];
            if($member['pid']){
                $parent1 = Db::name('member')->where('aid',$aid)->where('id',$member['pid'])->find();

                if($parent1){
                    $agleveldata1 = Db::name('member_level')->where('aid',$aid)->where('id',$parent1['levelid'])->find();
                    if($agleveldata1['can_agent']!=0 && (!$agleveldata1['commission_appointlevelid'] || in_array($member['levelid'],explode(',',$agleveldata1['commission_appointlevelid'])))){
                        $ogupdate['parent1'] = $parent1['id'];
                    }
                }
            }

            if($parent1['pid']){
                $parent2 = Db::name('member')->where('aid',$aid)->where('id',$parent1['pid'])->find();
                if($parent2){
                    $agleveldata2 = Db::name('member_level')->where('aid',$aid)->where('id',$parent2['levelid'])->find();
                    if(($agleveldata2['can_agent']>1 || $agleveldata2['commission_parent']>0) && (!$agleveldata2['commission_appointlevelid'] || in_array($member['levelid'],explode(',',$agleveldata2['commission_appointlevelid'])))){
                        $ogupdate['parent2'] = $parent2['id'];
                    }
                }
            }
            if($parent2['pid']){
                $parent3 = Db::name('member')->where('aid',$aid)->where('id',$parent2['pid'])->find();
                if($parent3){
                    $agleveldata3 = Db::name('member_level')->where('aid',$aid)->where('id',$parent3['levelid'])->find();
                    if(($agleveldata3['can_agent']>2 || $agleveldata3['commission_parent']>0) && (!$agleveldata3['commission_appointlevelid'] || in_array($member['levelid'],explode(',',$agleveldata3['commission_appointlevelid'])))){
                        $ogupdate['parent3'] = $parent3['id'];
                    }
                }
            }
            if($parent3['pid']){
                $parent4 = Db::name('member')->where('aid',$aid)->where('id',$parent3['pid'])->find();
                if($parent4){
                    $agleveldata4 = Db::name('member_level')->where('aid',$aid)->where('id',$parent4['levelid'])->find();
                    //持续推荐奖励
                    if($agleveldata4['can_agent'] > 0 && ($agleveldata4['commission_parent'] > 0 || ($parent4['levelid']==$parent3['levelid'] && $agleveldata4['commission_parent_pj'] > 0))){
                        $ogupdate['parent4'] = $parent4['id'];
                    }
                }
            }

            if(getcustom('agent_to_origin')){
                //一级分销发放给原推荐人
                $member_level = Db::name('member_level')->where('aid',$aid)->where('id',$member['levelid'])->find();
                if($member['pid_origin']>0 && $member_level['agent_to_origin']==1){
                    $parent1 = Db::name('member')->where('aid',$aid)->where('id',$member['pid_origin'])->find();
                    if($parent1){
                        $agleveldata1 = Db::name('member_level')->where('aid',$aid)->where('id',$parent1['levelid'])->find();
                        if($agleveldata1['can_agent']!=0 && (!$agleveldata1['commission_appointlevelid'] || in_array($member['levelid'],explode(',',$agleveldata1['commission_appointlevelid'])))){
                            $ogupdate['parent1'] = $parent1['id'];
                        }
                    }
                }
            }
           
            if($withdrawfee_status==0){ //按照会员等级
                if ($agleveldata1) {
                    if ($agleveldata1['commissiontype'] == 1) { //固定金额按单
                        if ($istc1 == 0) {
                            $ogupdate['parent1commission'] = $agleveldata1['commission1'];
                            $istc1 = 1;
                        }
                    } else {
                        $ogupdate['parent1commission'] = $agleveldata1['commission1'] * $commission_totalprice * 0.01;
                    }
                }
                if ($agleveldata2) {
                    if ($agleveldata2['commissiontype'] == 1) {
                        if ($istc2 == 0) {
                            $ogupdate['parent2commission'] = $agleveldata2['can_agent']>1?$agleveldata2['commission2']:0;
                            $istc2 = 1;
                        }
                    } else {
                        $ogupdate['parent2commission'] = $agleveldata2['can_agent']>1?($agleveldata2['commission2'] * $commission_totalprice * 0.01):0;
                    }
                }
                if ($agleveldata3) {
                    if ($agleveldata3['commissiontype'] == 1) {
                        if ($istc3 == 0) {
                            $ogupdate['parent3commission'] = $agleveldata3['can_agent']>2?$agleveldata3['commission3']:0;
                            $istc3 = 1;
                        }
                    } else {
                        $ogupdate['parent3commission'] = $agleveldata3['can_agent']>2?($agleveldata3['commission3'] * $commission_totalprice * 0.01):0;
                    }
                }
               
            }elseif($withdrawfee_status==1){ //单独设置提成比例
                if($type =='money_withdraw'){
                    $commissiondata = json_decode($sysset['money_withdrawfee_commissiondata'],true);
                }else{
                    $commissiondata = json_decode($sysset['commission_withdrawfee_commissiondata'],true);
                }
                if($commissiondata){
                    if($agleveldata1) $ogupdate['parent1commission'] = $commissiondata[$agleveldata1['id']]['commission1'] * $commission_totalprice * 0.01;
                    if($agleveldata2) $ogupdate['parent2commission'] = $commissiondata[$agleveldata2['id']]['commission2'] * $commission_totalprice * 0.01;
                    if($agleveldata3) $ogupdate['parent3commission'] = $commissiondata[$agleveldata3['id']]['commission3'] * $commission_totalprice * 0.01;
                }
            }
            $ogupdate['istc1'] = $istc1;
            $ogupdate['istc2'] = $istc2;
            $ogupdate['istc3'] = $istc3;
            return $ogupdate;
        }
    }
    /*
    * 余额或佣金 提现分销 添加记录 
    *   $type  money_withdraw:余额 commission_withdraw:佣金
    */
    public static function addWithdrawCommissionRecord($ogupdate,$order,$type='money_withdraw'){
        $remark_custom = getcustom('commission_log_remark_custom');
        if($remark_custom){
            $nickname = Db::name('member')->where('aid',$order['aid'])->where('id',$order['mid'])->value('nickname');
            $commission_totalprice = dd_money_format($order['txmoney'] -  $order['money']);
        }
        if(getcustom('money_commission_withdraw_fenxiao')){
            $type_text = $type=='money_withdraw'?'余额提现':'佣金提现';
            $ordermid = $order['mid'];
            $orderid = $order['id'];
            $totalcommission = 0;
            if($ogupdate['parent1'] && ($ogupdate['parent1commission']>0 || $ogupdate['parent1score']>0)){
                $remark1 = t('下级').$type_text.'奖励';
                if($remark_custom ){
                    $remark1=t('下级').$nickname.'提现'.$order['txmoney'].'元手续费'.$commission_totalprice.'元';
                }
                $data_c = ['aid'=>$order['aid'],'mid'=>$ogupdate['parent1'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>0,'type'=>$type,'commission'=>$ogupdate['parent1commission'],'score'=>$ogupdate['parent1score'],'remark'=>$remark1,'createtime'=>time()];
                
                Db::name('member_commission_record')->insert($data_c);
                $totalcommission += $ogupdate['parent1commission'];
            }
            if($ogupdate['parent2'] && ($ogupdate['parent2commission']>0 || $ogupdate['parent2score']>0)){
                $remark2 = t('下二级').$type_text.'奖励';
                if($remark_custom ){
                    $remark2=t('下二级').$nickname.'提现'.$order['txmoney'].'元手续费'.$commission_totalprice.'元';
                }
                $data_c = ['aid'=>$order['aid'],'mid'=>$ogupdate['parent2'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>0,'type'=>$type,'commission'=>$ogupdate['parent2commission'],'score'=>$ogupdate['parent2score'],'remark'=>$remark2,'createtime'=>time()];
                Db::name('member_commission_record')->insert($data_c);
                $totalcommission += $ogupdate['parent2commission'];
            }
            if($ogupdate['parent3'] && ($ogupdate['parent3commission']>0 || $ogupdate['parent3score']>0)){
                $remark3 = t('下三级').$type_text.'奖励';
                if($remark_custom ){
                    $remark3=t('下三级').$nickname.'提现'.$order['txmoney'].'元手续费'.$commission_totalprice.'元';
                }
                $data_c = ['aid'=>$order['aid'],'mid'=>$ogupdate['parent3'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>0,'type'=>$type,'commission'=>$ogupdate['parent3commission'],'score'=>$ogupdate['parent3score'],'remark'=>$remark3,'createtime'=>time()];
                Db::name('member_commission_record')->insert($data_c);
                $totalcommission += $ogupdate['parent3commission'];
            }
            if($ogupdate['parent4'] && ($ogupdate['parent4commission']>0)){
                $remark = '持续推荐奖励';
                Db::name('member_commission_record')->insert(['aid'=>$order['aid'],'mid'=>$ogupdate['parent4'],'frommid'=>$ordermid,'orderid'=>$orderid,'ogid'=>0,'type'=>$type,'commission'=>$ogupdate['parent4commission'],'score'=>0,'remark'=>$remark,'createtime'=>time()]);
                $totalcommission += $ogupdate['parent4commission'];
            }
            
        }
    }
    public static function jiesuanWithdrawCommission($aid,$order=[],$type='money_withdraw'){
        if(getcustom('money_commission_withdraw_fenxiao')){
            $where = [];
            $where[] = ['aid', '=', $aid];
            $where[] = ['status', '=', 0];
            $where[] = ['orderid', '=', $order['id']];
            $where[] = ['type', '=', $type];
            $recordList = Db::name('member_commission_record')->where($where)->select()->toArray();
            if(!$recordList)return;
            foreach($recordList as $k=>$record){
                \app\common\Order::giveCommission($order,$record['type']);
            }
        }
    }

    /**
     * 增加分销数据统计-总单量、销售金额、有效金额
     * 分销数据统计 需求文档：https://doc.weixin.qq.com/doc/w3_AT4AYwbFACw3Bb6Vgs5S2KsqP7gW3?scode=AHMAHgcfAA0clWIGk9AeYAOQYKALU
     * 定制标记：transfer_order_parent_check
     * @author: liud
     * @time: 2024/11/26 下午3:29
     */
    public static function addTotalOrderNum($aid,$mid,$orderid,$type){
        if(getcustom('transfer_order_parent_check')){
            if(!$minfo = Db::name('member')->where('aid',$aid)->where('id',$mid)->field('id,path,levelid')->find()){
                return ['status' => 0, 'msg' => '用户不存在'];
            }

            if(!$order = Db::name('shop_order')->where('aid',$aid)->where('mid',$mid)->where('id',$orderid)->find()){
                return ['status' => 0, 'msg' => '订单不存在'];
            }

            //查询当前用户的所有上级用户
            $pmids = $minfo['id'];
            if($minfo['path']){
                $pmids = $minfo['path'].','.$minfo['id'];
            }
            $pmid_arr = array_reverse(explode(',',$pmids));
            //$pmid_arr = explode(',',$pmids);

            $total_order_num = 0;
            $sales_amount = 0;
            $effective_amount = 0;

            if($type==1){//下单
                $total_order_num = 1;
            }elseif($type==2){//订单支付之后
                //销售金额-订单支付之后就进行累加
                $sales_amount = $order['totalprice'];

                if($sales_amount <= 0){
                    return;
                }
            }elseif($type==3){//订单确认收货后

                //二次修改 有效金额不统计
                return;

                //有效金额（订单确认收货后进行累加
                $effective_amount = $order['totalprice'];

                if($effective_amount <= 0){
                    return;
                }
            }

            foreach ($pmid_arr as $kk => $vv){
                //当前循环用户信息
                $vv_info = Db::name('member')->where('aid',$aid)->where('id',$vv)->field('id,levelid')->find();

                //如果上级比下单人小
                if($kk > 0 && $vv_info['levelid'] < $minfo['levelid']){
                    continue;
                }

                if(Db::name('transfer_order_parent_check_tongji_log')->where('aid',$aid)->where('orderid',$orderid)->where('tomid',$vv_info['id'])->where('type',$type)->where('is_add', 1)->find()){
                    continue;
                }

                //查询当前人数据统计信息
                if(!$tongji = Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$vv_info['id'])->find()){
                    $tongji = [
                        'aid' => $aid,
                        'mid' => $vv_info['id'],
                        'total_order_num' => $total_order_num,
                        'sales_amount' => $sales_amount,
                        'effective_amount' => $effective_amount,
                        'levelid' => $vv_info['levelid'],
                        'creattime' => time(),
                    ];
                    $tongjiid = Db::name('transfer_order_parent_check_tongji')->insertGetId($tongji);
                }else{
                    $tongjiid = $tongji['id'];
                    $tongji = [
                        'total_order_num' => $tongji['total_order_num'] + $total_order_num,
                        'sales_amount' => $tongji['sales_amount'] + $sales_amount,
                        'effective_amount' => $tongji['effective_amount'] + $effective_amount,
                        'levelid' => $vv_info['levelid'],
                    ];
                    Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$vv_info['id'])->update($tongji);
                }

                //增加详情表
                Db::name('transfer_order_parent_check_tongji_log')->insert([
                    'aid' => $aid,
                    'mid' => $order['mid'],
                    'orderid' => $orderid,
                    'tomid' => $vv_info['id'],
                    'total_order_num' => $total_order_num,
                    'sales_amount' => $sales_amount,
                    'effective_amount' => $effective_amount,
                    'createtime' => time(),
                    'type' => $type,
                    'order_createtime' => $order['createtime'],
                    'order_paytime' => $order['paytime'] ?? 0,
                    'transfer_order_parent_check_tongji_id' => $tongjiid,
                ]);
            }

            return ['status' => 1, 'msg' => '操作成功'];
        }
    }

    /**
     * 增加分销数据统计-上交金额、差价
     * 分销数据统计 需求文档：https://doc.weixin.qq.com/doc/w3_AT4AYwbFACw3Bb6Vgs5S2KsqP7gW3?scode=AHMAHgcfAA0clWIGk9AeYAOQYKALU
     * 定制标记：transfer_order_parent_check
     * @author: liud
     * @time: 2024/11/26 下午3:29
     */
    public static function addDifferential($aid,$mid,$orderid){
        if(getcustom('transfer_order_parent_check')){
            if(!$minfo = Db::name('member')->where('aid',$aid)->where('id',$mid)->field('id,levelid,pid,path')->find()){
                return ['status' => 0, 'msg' => '用户不存在'];
            }

            if(!$order = Db::name('shop_order')->where('aid',$aid)->where('mid',$mid)->where('id',$orderid)->find()){
                return ['status' => 0, 'msg' => '订单不存在'];
            }

            $oginfo = [];
            //商品会员价格
            $oginfo = Db::name('shop_order_goods')->alias('og')
                ->leftJoin('shop_guige sg', 'sg.id = og.ggid')
                ->leftJoin('shop_product g', 'g.id = og.proid')
                ->field('og.num,og.orderid,og.proid,sg.sell_price,sg.cost_price,sg.lvprice_data,g.lvprice')
                ->where('og.aid', $aid)
                ->where('og.orderid', $order['id'])
                ->select()->toArray();

            if(!$oginfo){
                return;
            }

            //查询当前用户的所有上级用户
            $pmids = $minfo['id'];
            if($minfo['path']){
                $pmids = $minfo['path'].','.$minfo['id'];
            }
            $pmid_arr = array_reverse(explode(',',$pmids));

            $last_minfo = '';
            foreach ($pmid_arr as $kk => $vv) {
                //当前循环用户信息
                $vv_info = Db::name('member')->where('aid', $aid)->where('id', $vv)->field('id,levelid')->find();

                if($last_minfo){
                    //如上级级别比下级低则跳过
                    if($vv_info['levelid'] < $last_minfo['levelid']){
                        continue;
                    }
                }

                $z_differential = 0;//自己差价
                $p_differential = 0;//上级和自己差价
                $submission_amount = 0;//自己上交金额

                foreach ($oginfo as $ogk => $ogv){
                    if($ogv['lvprice'] == 1){
                        //开启了会员价格
                        $lvprice_data = json_decode($ogv['lvprice_data'],true);

                        //上交金额（就是自己这个等级购买这个订单商品多少钱
                        $hujg = $lvprice_data[$vv_info['levelid']] ?? $ogv['sell_price'];
                        $submission_amount += ($hujg * $ogv['num']);

                        //自己差价
                        $hujg = $lvprice_data[$minfo['levelid']] ?? $ogv['sell_price'];
                        $onehujg = $lvprice_data[1] ?? $ogv['sell_price'];
                        $zjcj = $onehujg - $hujg;
                        $zjcj = ($zjcj<0) ? 0 : $zjcj;
                        $z_differential += ($zjcj * $ogv['num']);

                        //上级购买这个商品的价格和我购买的差价
                        if($last_minfo){
                            //下级的价格
                            $x_hujg = $lvprice_data[$last_minfo['levelid']] ?? $ogv['sell_price'];
                            //上级的价格
                            $s_hujg = $lvprice_data[$vv_info['levelid']] ?? $ogv['sell_price'];
                            $p_zjcj = abs($x_hujg - $s_hujg);
                            $p_zjcj = ($p_zjcj<0) ? 0 : $p_zjcj;
                            $p_differential += ($p_zjcj * $ogv['num']);
                        }
                    }
                }

                //记录上交金额
                if($submission_amount > 0){
                    if (!Db::name('transfer_order_parent_check_tongji_log')->where('aid', $aid)->where('orderid', $orderid)->where('tomid', $vv_info['id'])->where('type', 4)->where('is_add', 1)->find()) {
                        //增加详情表
                        Db::name('transfer_order_parent_check_tongji_log')->insert([
                            'aid' => $aid,
                            'mid' => $order['mid'],
                            'orderid' => $orderid,
                            'tomid' => $vv_info['id'],
                            'submission_amount' => $submission_amount,
                            'createtime' => time(),
                            'type' => 4,
                            'order_createtime' => $order['createtime'],
                            'order_paytime' => $order['paytime'] ?? 0,
                        ]);

                        //查询当前人数据统计信息
                        if(!$tongji = Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$vv_info['id'])->find()){
                            $tongji = [
                                'aid' => $aid,
                                'mid' => $vv_info['id'],
                                'submission_amount' => $submission_amount,
                                'levelid' => $vv_info['levelid'],
                                'creattime' => time(),
                            ];
                            $tongjiid = Db::name('transfer_order_parent_check_tongji')->insertGetId($tongji);
                        }else{
                            $tongjiid = $tongji['id'];
                            $tongji = [
                                'submission_amount' => $tongji['submission_amount'] + $submission_amount,
                                'levelid' => $vv_info['levelid'],
                            ];
                            Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$vv_info['id'])->update($tongji);
                        }
                    }
                }

                //记录自己的差价
                if ($kk == 0 && $z_differential > 0) {
                    if(!Db::name('transfer_order_parent_check_tongji_log')->where('aid', $aid)->where('orderid', $orderid)->where('tomid', $minfo['id'])->where('type', 5)->where('is_add', 1)->find()){
                        //增加详情表
                        Db::name('transfer_order_parent_check_tongji_log')->insert([
                            'aid' => $aid,
                            'mid' => $order['mid'],
                            'orderid' => $orderid,
                            'tomid' => $minfo['id'],
                            'differential' => $z_differential,
                            'createtime' => time(),
                            'type' => 5,
                            'order_createtime' => $order['createtime'],
                            'order_paytime' => $order['paytime'] ?? 0,
                        ]);

                        //查询当前人数据统计信息
                        if(!$tongji = Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$minfo['id'])->find()){
                            $tongji = [
                                'aid' => $aid,
                                'mid' => $minfo['id'],
                                'differential' => $z_differential,
                                'levelid' => $minfo['levelid'],
                                'creattime' => time(),
                            ];
                            $tongjiid = Db::name('transfer_order_parent_check_tongji')->insertGetId($tongji);
                        }else{
                            $tongjiid = $tongji['id'];
                            $tongji = [
                                'differential' => $tongji['differential'] + $z_differential,
                                'levelid' => $minfo['levelid'],
                            ];
                            Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$minfo['id'])->update($tongji);
                        }
                    }
                }

                //记录下级和自己的差价
                if ($kk > 0 && $p_differential > 0) {
                    if(!Db::name('transfer_order_parent_check_tongji_log')->where('aid', $aid)->where('orderid', $orderid)->where('tomid', $vv_info['id'])->where('type', 6)->where('is_add', 1)->find()){
                        //增加详情表
                        Db::name('transfer_order_parent_check_tongji_log')->insert([
                            'aid' => $aid,
                            'mid' => $order['mid'],
                            'orderid' => $orderid,
                            'tomid' => $vv_info['id'],
                            'xia_differential' => $p_differential,
                            'createtime' => time(),
                            'type' => 6,
                            'order_createtime' => $order['createtime'],
                            'order_paytime' => $order['paytime'] ?? 0,
                        ]);

                        //查询当前人数据统计信息
                        if(!$tongji = Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$vv_info['id'])->find()){
                            $tongji = [
                                'aid' => $aid,
                                'mid' => $vv_info['id'],
                                'xia_differential' => $p_differential,
                                'levelid' => $vv_info['levelid'],
                                'creattime' => time(),
                            ];
                            $tongjiid = Db::name('transfer_order_parent_check_tongji')->insertGetId($tongji);
                        }else{
                            $tongjiid = $tongji['id'];
                            $tongji = [
                                'xia_differential' => $tongji['xia_differential'] + $p_differential,
                                'levelid' => $vv_info['levelid'],
                            ];
                            Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$vv_info['id'])->update($tongji);
                        }
                    }
                }

                $last_minfo = $vv_info;
            }
            return ['status' => 1, 'msg' => '操作成功'];
        }
    }

    /**
     * 退款或关闭订单减掉分销数据
     * 分销数据统计 需求文档：https://doc.weixin.qq.com/doc/w3_AT4AYwbFACw3Bb6Vgs5S2KsqP7gW3?scode=AHMAHgcfAA0clWIGk9AeYAOQYKALU
     * 定制标记：transfer_order_parent_check
     * @author: liud
     * @time: 2024/11/26 下午3:29
     */
    public static function decTransferOrderCommissionTongji($aid,$mid,$orderid,$type=1){
        if(getcustom('transfer_order_parent_check')){

            if(!$order = Db::name('shop_order')->where('aid',$aid)->where('mid',$mid)->where('id',$orderid)->find()){
                return ['status' => 0, 'msg' => '订单不存在'];
            }

            //判读订单是否是已退款
//            if($order['status'] >= 0 && $order['status'] <= 3){
//                return ['status' => 0, 'msg' => '订单状态未关闭或未退款'];
//            }

            $where = [];
            if($type == 1){
                //$where[] = ['type','=',1];
            }

            //查出这个订单的所有增加记录
            if(!$tongjilog = Db::name('transfer_order_parent_check_tongji_log')->where('aid', $aid)->where('mid', $mid)->where('orderid', $orderid)->where('is_add', 1)->where($where)->select()->toArray()){
                return ['status' => 0, 'msg' => '暂无记录'];
            }

            foreach ($tongjilog as $k => $v){
                //关闭订单减掉单量
                if($type== 1){
                    //修改主表
                    if($v['type'] == 1){
                        Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->dec('total_order_num',$v['total_order_num'])->update();
                    }elseif ($v['type'] == 4){
                        //减上交金额
                        Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->dec('submission_amount',$v['submission_amount'])->update();
                    }elseif ($v['type'] == 5){
                        //减差价
                        Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->dec('differential',$v['differential'])->update();
                    }elseif ($v['type'] == 6){
                        //下级差价
                        Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->dec('xia_differential',$v['xia_differential'])->update();
                    }
                    //修改记录表
                    Db::name('transfer_order_parent_check_tongji_log')->where('aid',$aid)->where('id',$v['id'])->update(['is_add' => 0]);
                }else{
                    //退款减
                    if($v['type'] == 1){
                        Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->dec('total_order_num',$v['total_order_num'])->update();
                    }else if($v['type'] == 2){
                        //减销售金额
                        Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->dec('sales_amount',$v['sales_amount'])->update();
                    }elseif ($v['type'] == 3){
                        //减有效金额
//                        Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->dec('effective_amount',$v['effective_amount'])->update();
                    }elseif ($v['type'] == 4){
                        //减上交金额
                        Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->dec('submission_amount',$v['submission_amount'])->update();
                    }elseif ($v['type'] == 5){
                        //减差价
                        Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->dec('differential',$v['differential'])->update();
                    }elseif ($v['type'] == 6){
                        //下级差价
                        Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->dec('xia_differential',$v['xia_differential'])->update();
                    }
                    //修改记录表
                    Db::name('transfer_order_parent_check_tongji_log')->where('aid',$aid)->where('id',$v['id'])->update(['is_add' => 0]);
                }
            }
        }
    }

    /**
     * 用户升级判断级别减掉相应分销数据
     * 分销数据统计 需求文档：https://doc.weixin.qq.com/doc/w3_AT4AYwbFACw3Bb6Vgs5S2KsqP7gW3?scode=AHMAHgcfAA0clWIGk9AeYAOQYKALU
     * 定制标记：transfer_order_parent_check
     * @author: liud
     * @time: 2024/11/26 下午3:29
     */
    public static function decUplevelTransferOrderCommissionTongji($aid,$mid){
        if(getcustom('transfer_order_parent_check')){

            if(!$minfo = Db::name('member')->where('aid',$aid)->where('id',$mid)->field('id,levelid,pid,path')->find()){
                return ['status' => 0, 'msg' => '用户不存在'];
            }

            //查询当前用户的所有上级用户
            $pmids = $minfo['id'];
            if($minfo['path']){
                $pmids = $minfo['path'].','.$minfo['id'];
            }
            $pmid_arr = array_reverse(explode(',',$pmids));

            foreach ($pmid_arr as $kk => $vv) {
                if($kk==0) continue;

                //当前循环用户信息
                $vv_info = Db::name('member')->where('aid', $aid)->where('id', $vv)->field('id,levelid')->find();

                //如上级级别比下级低.
                if ($vv_info['levelid'] < $minfo['levelid']) {

                    //判断记录表里有没有给这个上级加的数据
                    if($tongjilog = Db::name('transfer_order_parent_check_tongji_log')->where('aid', $aid)->where('mid', $mid)->where('tomid', $vv_info['id'])->where('is_add', 1)->select()->toArray()){
                        //循环减掉数据
                        foreach ($tongjilog as $k => $v){
                            if($v['type'] == 1){
                                Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->dec('total_order_num',$v['total_order_num'])->update();
                            }else if($v['type'] == 2){
                                //减销售金额
                                Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->dec('sales_amount',$v['sales_amount'])->update();
                            }elseif ($v['type'] == 3){
                                //减有效金额
//                                Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->dec('effective_amount',$v['effective_amount'])->update();
                            }elseif ($v['type'] == 4){
                                //减上交金额
                                Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->dec('submission_amount',$v['submission_amount'])->update();
                            }elseif ($v['type'] == 5){
                                //减差价
                                Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->dec('differential',$v['differential'])->update();
                            }elseif ($v['type'] == 6){
                                //下级差价
                                Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->dec('xia_differential',$v['xia_differential'])->update();
                            }
                            //修改记录表
                            Db::name('transfer_order_parent_check_tongji_log')->where('aid',$aid)->where('id',$v['id'])->update(['is_add' => 0,'is_up'=>1]);
                        }
                    }
                }else if ($vv_info['levelid'] >= $minfo['levelid']) {//如上级级别大于等于自己级别并且有剪掉的记录
                    //判断记录表里有没有给这个上级减的数据
                    if($tongjilog1 = Db::name('transfer_order_parent_check_tongji_log')->where('aid', $aid)->where('mid', $mid)->where('tomid', $vv_info['id'])->where('is_add', 0)->where('is_up', 1)->select()->toArray()){
                        //循环减掉数据
                        foreach ($tongjilog1 as $k => $v){
                            if($v['type'] == 1){
                                Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->inc('total_order_num',$v['total_order_num'])->update();
                            }else if($v['type'] == 2){
                                //减销售金额
                                Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->inc('sales_amount',$v['sales_amount'])->update();
                            }elseif ($v['type'] == 3){
                                //减有效金额
//                                Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->inc('effective_amount',$v['effective_amount'])->update();
                            }elseif ($v['type'] == 4){
                                //减上交金额
                                Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->inc('submission_amount',$v['submission_amount'])->update();
                            }elseif ($v['type'] == 5){
                                //减差价
                                Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->inc('differential',$v['differential'])->update();
                            }elseif ($v['type'] == 6){
                                //下级差价
                                Db::name('transfer_order_parent_check_tongji')->where('aid',$aid)->where('mid',$v['tomid'])->inc('xia_differential',$v['xia_differential'])->update();
                            }
                            //修改记录表
                            Db::name('transfer_order_parent_check_tongji_log')->where('aid',$aid)->where('id',$v['id'])->update(['is_add' => 1,'is_up'=>1]);
                        }
                    }
                }
            }
        }
    }

	//根据商品和当前用户计算预计佣金
	public static function productExpectedCommission($product, $userlevel, $sysset) {
		if(getcustom('product_promotion_tag')) {
			if ($userlevel['can_agent'] == 0) {
				//当前等级无分销权限
				return 0;
			}

			$price      = $product['sell_price'] - ($sysset['fxjiesuantype'] == 2 ? $product['cost_price'] : 0);
			$commission = 0;

			switch ($product['commissionset']) {
				case 1: // 按比例
					$data       = json_decode($product['commissiondata1'], true);
					$commission = $data[$userlevel['id']]['commission1'] * $price * 0.01;
					break;

				case 2: // 按固定金额
					$data       = json_decode($product['commissiondata2'], true);
					$commission = $data[$userlevel['id']]['commission1'];
					break;

				case 5: // 提成比例+积分
					$data       = json_decode($product['commissiondata1'], true);
					$commission = $data[$userlevel['id']]['commission1'] * $price * 0.01;
					break;

				case 6: // 提成金额+积分
					$data       = json_decode($product['commissiondata2'], true);
					$commission = $data[$userlevel['id']]['commission1'];
					break;

				case 0: // 按会员等级
					$commission = ($userlevel['commissiontype'] == 1) ? $userlevel['commission1'] : $userlevel['commission1'] * $price * 0.01;
					break;
			}
			return $commission;
		}
	}

    /**
     * 检测是否成组
     * @param $commission_teamnum 成组人数
     * @param $teamnum_total 总人数
     * @param $sort 当前会员所在位置
     */
    public static function check_teamgroup($commission_teamnum, $teamnum_total,$sort){
        if(getcustom('commission_teamnum')) {
            $is_group = 1;
            //总共多少组
            $team_group = bcdiv($teamnum_total, $commission_teamnum);
            if ($team_group > 0) {
                $team_group = $team_group + 1;
            }
            //当前会员所在组
            $sort_group = bcdiv($sort, $commission_teamnum);
            if ($sort_group > 0) {
                $sort_group = $sort_group + 1;
            }
            //判断是否成组
            if ($commission_teamnum>0 && ($sort % $commission_teamnum) != 0 && $sort_group >= $team_group) {
                //未成组
                $is_group = 0;
            }
            return $is_group;
        }
    }

    //获取可以拿平级奖的会员
    public static function getparentpj($aid,$sysset,$member,$parent1,$parent2,$parent3){

        $sysset_custom = Db::name('admin_set_custom')->where('aid',$aid)->find();
        //分销平级奖紧缩
        $commission_parent_pj_jinsuo = 0;
        //平级奖层级限制（正常是查到第四层，定制开启后可以无限级向上找到最近的一个平级）
        $commission_parent_pj_lv_limit = 0;
        if(getcustom('commission_parent_pj_jinsuo')){
            $commission_parent_pj_jinsuo = $sysset_custom['commission_parent_pj_jinsuo']?:0;
            $commission_parent_pj_lv_limit = $sysset_custom['commission_parent_pj_lv_limit']?:0;
        }

        //平级奖只发一次
        $commission_parent_pj_once = 0;
        if(getcustom('commission_parent_pj_once')){
            $commission_parent_pj_once = $sysset_custom['commission_parent_pj_once']?:0;
        }

        //平级奖计算起始会员（正常是从下单人的上级开始算，定制开启后从下单人自身开始算）
        $commission_parent_pj_member = 0;
        if(getcustom('commission_parent_pj_member')){
            $commission_parent_pj_member = $sysset_custom['commission_parent_pj_member']?:0;
        }

        //正常的平级会员级别，分别是每一级分销的上一级
        $pj1_levelid = $parent1['levelid'];
        $pj1_lv = 2;//第2层和第1层平级是是一级分销的平级
        $pj2_levelid = $parent2['levelid'];
        $pj2_lv = 3;//第3层和第2层平级是是一级分销的平级
        $pj3_levelid = $parent3['levelid'];
        $pj3_lv = 4;//第4层和第3层平级是是一级分销的平级
        if($commission_parent_pj_member==1){
            //平级从下单人开始算
            $pj1_levelid = $member['levelid'];
            $pj1_lv = 1;//第1层和下单人平级是是2级平级
            $pj2_levelid = $parent1['levelid'];
            $pj2_lv = 2;//第2层和第1层平级是是2级平级
            $pj3_levelid = $parent2['levelid'];
            $pj3_lv = 3;//第3层和第2层平级是是3级平级
        }

        $parent_pj1 = [];
        $level_pj1 = [];
        $parent_pj2 = [];
        $level_pj2 = [];
        $parent_pj3 = [];
        $level_pj3 = [];
        $pids = $member['path'];
        if($pids){
            $parentList = Db::name('member')->where('id','in',$pids)->field('id,pid,path,levelid')->order(Db::raw('field(id,'.$pids.')'))->select()->toArray();
            $parentList = array_reverse($parentList);//父级从近到远，自己，上一级，上二级，上三级。。。
            foreach($parentList as $pk=>$pv){
                $lv = $pk+1;
                if($commission_parent_pj_jinsuo) {
                    if(!$commission_parent_pj_lv_limit && $lv>4){
                        //限制了只查四层
                        break;
                    }
                    //开启分销紧缩后，按紧缩找到最近的一个平级
                    //查找平级1级
                    if ($lv >= $pj1_lv && !$parent_pj1 && $pv['levelid'] == $pj1_levelid) {
                        $parent_pj1 = $pv;
                        $level_pj1 = Db::name('member_level')->where('aid',$aid)->where('id',$pv['levelid'])->find();
                    }
                    //查找平级2级
                    if ($lv >= $pj2_lv && !$parent_pj2 && $pv['levelid'] == $pj2_levelid) {
                        $parent_pj2 = $pv;
                        $level_pj2 = Db::name('member_level')->where('aid',$aid)->where('id',$pv['levelid'])->find();
                    }
                    //查找平级3级
                    if ($lv >= $pj3_lv && !$parent_pj3 && $pv['levelid'] == $pj3_levelid) {
                        $parent_pj3 = $pv;
                        $level_pj3 = Db::name('member_level')->where('aid',$aid)->where('id',$pv['levelid'])->find();
                    }
                }else{
                    //未开启紧缩
                    //查找平级1级
                    if ($lv == $pj1_lv && !$parent_pj1 && $pv['levelid'] == $pj1_levelid) {
                        $parent_pj1 = $pv;
                        $level_pj1 = Db::name('member_level')->where('aid',$aid)->where('id',$pv['levelid'])->find();
                    }
                    //查找平级2级
                    if ($lv == $pj2_lv && !$parent_pj2 && $pv['levelid'] == $pj2_levelid) {
                        $parent_pj2 = $pv;
                        $level_pj2 = Db::name('member_level')->where('aid',$aid)->where('id',$pv['levelid'])->find();
                    }
                    //查找平级3级
                    if ($lv == $pj3_lv && !$parent_pj3 && $pv['levelid'] == $pj3_levelid) {
                        $parent_pj3 = $pv;
                        $level_pj3 = Db::name('member_level')->where('aid',$aid)->where('id',$pv['levelid'])->find();
                    }
                }
            }
        }


        return [
            'parent_pj1'=>$parent_pj1,
            'level_pj1'=>$level_pj1,
            'parent_pj2'=>$parent_pj2,
            'level_pj2'=>$level_pj2,
            'parent_pj3'=>$parent_pj3,
            'level_pj3'=>$level_pj3,
        ];
    }
}
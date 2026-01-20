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
class Business
{
	//加余额
	public static function addmoney($aid,$bid,$money,$remark,$addparentcommission=false,$type='',$ordernum='',$params=[]){
		//分佣
		if($addparentcommission){
			if(getcustom('business_agent')){
                if(isset($params['business_lirun'])){
                    $business_lirun = $params['business_lirun'];
                }else{
                    $business_lirun = 0;
                }
				self::addparentcommission2($aid,$bid,$money,$params['platformMoney'],$business_lirun);
			}else{
				if($money > 0){
					self::addparentcommission($aid,$bid,$money);
				}
			}
		}

		if($money==0) return ;
		$business = Db::name('business')->where('aid',$aid)->where('id',$bid)->lock(true)->find();
		if(!$business) return ['status'=>0,'msg'=>'商家不存在'];

		$after = $business['money'] + $money;
		if($type == 'withdraw' && $after<0){
            return ['status'=>0,'msg'=>'余额不足'];
        }
		Db::name('business')->where('aid',$aid)->where('id',$bid)->inc('money',$money)->update();

		$data = [];
		$data['aid'] = $aid;
		$data['bid'] = $bid;
		$data['money'] = $money;
		$data['after'] = $after;
		$data['createtime'] = time();
		$data['remark'] = $remark;
		$data['type'] = $type;
        $data['ordernum'] = $ordernum;
		Db::name('business_moneylog')->insert($data);
		return ['status'=>1,'msg'=>''];
	}
	//加积分
	public static function addscore($aid,$bid,$score,$remark,$addplat=0){
		if($score==0) return ['status'=>0,'msg'=>''];
		$business = Db::name('business')->where('aid',$aid)->where('id',$bid)->find();
		if(!$business) return ['status'=>0,'msg'=>'商家不存在'];
		if($score < 0 && $business['score'] < -$score){
			$business_selfscore_minus = $business['business_selfscore_minus'];
			if($business_selfscore_minus == -1){
				$bset = Db::name('business_sysset')->where('aid',$aid)->find();
				$business_selfscore_minus = $bset['business_selfscore_minus'];
			}
			if($business_selfscore_minus == 0 ) return ['status'=>0,'msg'=>'商家'.t('积分').'不足'];
		}
		Db::name('business')->where('aid',$aid)->where('id',$bid)->inc('score',$score)->update();
		
		$data = [];
		$data['aid'] = $aid;
		$data['bid'] = $bid;
		$data['score'] = $score;
		$data['after'] = $business['score'] + $score;
		$data['createtime'] = time();
		$data['remark'] = $remark;
		Db::name('business_scorelog')->insert($data);

		if($addplat){
			Db::name('admin')->where('id',$aid)->inc('score',-$score)->update();
			$data = [];
			$data['aid'] = $aid;
			$data['score'] = -$score;
			$data['after'] = Db::name('admin')->where('id',$aid)->value('score');
			$data['createtime'] = time();
			if($score > 0){
				$data['remark'] = '给商家'.$business['name'].'加'.t('积分');
			}else{
				$data['remark'] = '商家'.$business['name'].'花费'.t('积分');
			}
			Db::name('admin_scorelog')->insert($data);
		}

		return ['status'=>1,'msg'=>''];
	}

	//加会员积分
	public static function addmemberscore($aid,$bid,$mid,$score,$remark,$decbscore=1){
		if($score==0) return ;
		$business = Db::name('business')->where('aid',$aid)->where('id',$bid)->find();
		if(!$business) return ['status'=>0,'msg'=>'商家不存在'];
		$member = Db::name('member')->where('aid',$aid)->where('id',$mid)->find();
		if(!$member) return ['status'=>0,'msg'=>t('会员').'不存在'];
		$bset = Db::name('business_sysset')->where('aid',$aid)->find();
		if(getcustom('business_score') && $bset['business_selfscore'] == 1 && $bset['business_selfscore2'] == 1){
			$memberscore = 0;
			$memberscoreinfo = Db::name('business_memberscore')->where('aid',$aid)->where('bid',$bid)->where('mid',$mid)->find();
			if($memberscoreinfo) $memberscore = $memberscoreinfo['score'];
			if($score < 0 && $memberscore < -$score){
				return ['status'=>0,'msg'=>t('积分').'不足'];
			}
			if($decbscore == 1){
				if($score > 0){
					$rs = self::addscore($aid,$bid,-$score,'给'.t('用户').$member['nickname'].'加'.t('积分'));
				}else{
					$rs = self::addscore($aid,$bid,-$score,t('用户').$member['nickname'].'花费'.t('积分'));
				}
				if($rs['status'] == 0) return $rs;
			}
			Db::name('member')->where('aid',$aid)->where('id',$mid)->inc('bscore',$score)->update();

			if(!$memberscoreinfo){
				Db::name('business_memberscore')->insert(['aid'=>$aid,'bid'=>$bid,'mid'=>$mid,'score'=>$score]);
			}else{
				Db::name('business_memberscore')->where('id',$memberscoreinfo['id'])->inc('score',$score)->update();
			}
		}else{
			if($decbscore == 1){
				if($score > 0){
					$rs = self::addscore($aid,$bid,-$score,'给'.t('用户').$member['nickname'].'加'.t('积分'));
				}else{
					$rs = self::addscore($aid,$bid,-$score,t('用户').$member['nickname'].'花费'.t('积分'));
				}
				if($rs['status'] == 0) return $rs;
			}
			$rs = \app\common\Member::addscore($aid,$mid,$score,$remark,'',$bid);
			if($rs['status'] == 0) return $rs;
		}

		$data = [];
		$data['aid'] = $aid;
		$data['bid'] = $bid;
		$data['mid'] = $mid;
		$data['score'] = $score;
		$data['after'] = ($memberscoreinfo ? $memberscoreinfo['score'] : 0) + $score;
		$data['createtime'] = time();
		$data['remark'] = $remark;
		Db::name('business_member_scorelog')->insert($data);
		return ['status'=>1,'msg'=>''];
	}

	public static function addparentcommission($aid,$bid,$money){
		$bset = Db::name('business_sysset')->where('aid',$aid)->find();
		if($bset['parentcommission'] > 0){
			$business = Db::name('business')->where('id',$bid)->find();
			$buser = Db::name('admin_user')->where('aid',$aid)->where('bid',$bid)->where('isadmin',1)->find();
			if($buser && $buser['mid']){
				$member = Db::name('member')->where('id',$buser['mid'])->find();
                if(getcustom('business_agent_referrer_independent')){
                    //商户推荐人关系独立
                    if($bset['referrer_independent'] == 1 && $business['recommend_type'] == 2){
                        $buiness_pid = Db::name('business_referrer_independent')->where('aid',$aid)->where('bid',$bid)->value('mid');
                        if($buiness_pid){
                            $member['pid'] = $buiness_pid;
                        }
                    }
                }
				if($member && $member['pid']){
					$commission = round($money * $bset['parentcommission'] * 0.01,2);
					if($commission > 0){
						if(getcustom('shoptongji3')){ //商家推荐商家提成
							$isadminuser = Db::name('admin_user')->where('aid',$aid)->where('bid','>',0)->where('mid',$member['pid'])->where('isadmin',1)->find();
							if($isadminuser && $isadminuser['bid'] != $bid){
								\app\common\Business::addmoney($aid,$isadminuser['bid'],$commission,'商户['.$business['name'].']营业提成');
							}
						}else{
							\app\common\Member::addcommission($aid,$member['pid'],$member['id'],$commission,'商户['.$business['name'].']营业提成');
						}
					}
				}
			}
		}
	}
    //todo 收银台退款 扣除佣金
	public static function addparentcommission2($aid,$bid,$businessMoney,$platformMoney=0,$business_lirun=0){
        $business_jt_jicha_custom = getcustom('business_agent_jt_jinsuo');
        $independent = 0; //商户独立推荐关系
        if (getcustom('business_agent_referrer_independent')) {
            $independent = Db::name('business_sysset')->where('aid', $aid)->value('referrer_independent') ?: 0;
        }
	    if(getcustom('business_agent')){
           $businessField = 'id,name';
           if($independent == 1){
               $businessField .= ',recommend_type';
           }
           $business = Db::name('business')->where('id',$bid)->field($businessField)->find();
			if($business){
				$buser = Db::name('admin_user')->where('aid',$aid)->where('bid',$bid)->where('isadmin',1)->find();
				if($buser && $buser['mid']){
					$member = Db::name('member')->where('id',$buser['mid'])->field('id,pid,levelid')->find();
                    if($independent == 1 && isset($business['recommend_type']) && $business['recommend_type'] == 2){
                        $business_pid = Db::name('business_referrer_independent')->where('aid',$aid)->where('bid',$bid)->value('mid');
                        if($business_pid){
                            $member['pid'] = $business_pid;
                        }
                    }
					if($member && $member['pid']){
	                    $money = $businessMoney;
	                    $admin_set = Db::name('admin_set')->where('aid',$aid)->find();
	                    //推荐商家结算方式：0按结算金额,1按平台抽成金额，2按利润进行结算
	                    if($admin_set['tjbusiness_jiesuan_type'] == 1){
	                        $money = $platformMoney;
	                    }
	                    if($admin_set['tjbusiness_jiesuan_type'] == 2){
	                        $money = $business_lirun >= 0 ? $business_lirun : 0;
	                    }
	                    if($money<=0) return;

						//查询上级信息
						$parent = Db::name('member')->where('id',$member['pid'])->where('aid',$aid)->field('id,pid,levelid')->find();
						if($parent){
                            //直推和间推 紧缩+级差

                            if($business_jt_jicha_custom){
                                self::agentParentJtJinsuo($aid,$money,$member['id'],$member,$business,$independent);
                                return;
                            }
							//查询上级等级信息
							$plevel = Db::name('member_level')->where('id',$parent['levelid'])->where('can_agent','>',0)->where('aid',$aid)->field('business_zt_ratio')->find();
							if($plevel && $plevel['business_zt_ratio']>0){
								//发直推商家分成
								$parentcommission = round($money * $plevel['business_zt_ratio'] * 0.01,2);
								if($parentcommission > 0){
									\app\common\Member::addcommission($aid,$member['pid'],$member['id'],$parentcommission,'商户['.$business['name'].']营业提成',0,'business_agent');
								}
							}
							if($parent['pid']>0 ){
								//查询上上级信息
								$parent2 = Db::name('member')->where('id',$parent['pid'])->where('aid',$aid)->field('id,levelid')->find();
								if($parent2){
									//查询上上级等级信息
									$plevel2 = Db::name('member_level')->where('id',$parent2['levelid'])->where('can_agent','>',0)->where('aid',$aid)->field('business_jt_ratio')->find();
									if($plevel2 && $plevel2['business_jt_ratio']>0){
										//发间推商家分成
										$parent2commission = round($money * $plevel2['business_jt_ratio'] * 0.01,2);
										if($parent2commission > 0){
											\app\common\Member::addcommission($aid,$parent['pid'],$member['id'],$parent2commission,'商户['.$business['name'].']营业间推提成',0,'business_agent');
										}
									}
								}
							}
						}
					}
				}
			}
		}
        if(getcustom('business_agent_jicha_pj')){
            $business = Db::name('business')->where('id',$bid)->field('id,name')->find();
            if($business){
                $buser = Db::name('admin_user')->where('aid',$aid)->where('bid',$bid)->where('isadmin',1)->find();
                if($buser && $buser['mid']){
                    if($business_jt_jicha_custom){
                        //开启紧缩
                        return;
                    }
                    $member = Db::name('member')->where('id',$buser['mid'])->field('id,pid,levelid')->find();
                    $money = $businessMoney;
                    $admin_set = Db::name('admin_set')->where('aid',$aid)->find();
                    //推荐商家结算方式：0按结算金额,1按平台抽成金额2按利润进行结算
                    if($admin_set['tjbusiness_jiesuan_type'] == 1){
                        $money = $platformMoney;
                    }
                    if($admin_set['tjbusiness_jiesuan_type'] == 2 && $business_lirun !='no'){
                        $money = $business_lirun;
                    }
                    if($independent == 1){
                        $member['pid'] = Db::name('business_referrer_independent')->where('aid',$aid)->where('bid',$bid)->value('mid') ?: 0;
                    }
                    if($money<=0) return;

                    $parent = Db::name('member')->where('id',$member['pid'])->where('aid',$aid)->field('id,pid,levelid')->find();
                    if($parent){
                        $plevel = Db::name('member_level')->where('id',$parent['levelid'])->where('can_agent','>',0)->where('aid',$aid)->field('business_zt_ratio,business_pj_ratio,sort,name')->find();
                        if($parent['pid']>0){
                            //查询上上级信息
                            $parent2 = Db::name('member')->where('id',$parent['pid'])->where('aid',$aid)->field('id,levelid')->find();
                            if($parent2){
                                $plevel2 = Db::name('member_level')->where('id',$parent2['levelid'])->where('can_agent','>',0)->where('aid',$aid)->field('business_zt_ratio,business_pj_ratio,sort,name,business_jicha_status')->find();

                                //发平级奖  给间推发
                                if($parent2['levelid'] == $parent['levelid'] && $plevel2['business_pj_ratio'] > 0){
                                    $pj_money =  $money * $plevel2['business_pj_ratio'] * 0.01;
                                    if($pj_money > 0){
                                        \app\common\Member::addcommission($aid,$parent2['id'],$member['id'],$pj_money,'商户['.$business['name'].']]平级奖营业提成');
                                    }
                                }
                                $member_level =  Db::name('member_level')->where('id',$member['levelid'])->where('can_agent','>',0)->where('aid',$aid)->field('sort')->find();
                                if($plevel['sort'] >  $member_level['sort']   && $plevel2['sort'] > $plevel['sort'] && $plevel2['business_jicha_status'] ==1){
                                    //间推等级 高于 直推 发级差 给间推
                                    $this_bl =  $plevel2['business_zt_ratio'] - $plevel['business_zt_ratio'];
                                    if($this_bl > 0) {
                                        $jicha_money =  $money * $this_bl * 0.01;
                                        if($jicha_money){
                                            \app\common\Member::addcommission($aid,$parent2['id'],$member['id'],$jicha_money,'商户['.$business['name'].']]级差奖营业提成',0,'business_agent');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

        }
	}
//    代理直推1%，平级10%（开启极差+紧缩）
//    老板直推3%，间推3%，平级10%（开启极差+紧缩）
//    
//    A老板-B老板-C代理-D代理-E商家
//    消费者在E商家消费1000元
//    
//    D获得直推奖1000*1%=10元
//    C获得直推平级奖D的10*10%=1元
//    B获得间推奖1000*3%减去A的1000*1%=20元
//    A获得间推平级奖B的20*10%=2元
    public static function agentParentJtJinsuo($aid,$money,$mid,$member,$business,$independent=0){
	    if(getcustom('business_agent_jt_jinsuo') && getcustom('business_agent') && getcustom('business_agent_jicha_pj')){
	        //我的上级
	        if($independent == 1) {
                //多商户独立推荐人
                $bpid = Db::name('business_referrer_independent')->where('aid',$aid)->where('bid',$business['id'])->value('mid');
                if($bpid){
                    $bparent_path = Db::name('member')->where('aid',$aid)->where('id',$bpid)->value('path');
                    $parent_path = $bparent_path ? $bparent_path.','.$bpid :(string) $bpid;
                }else{
                    $parent_path =Db::name('member')->where('id',$mid)->where('aid',$aid)->value('path');
                }
            }else{
                $parent_path =Db::name('member')->where('id',$mid)->where('aid',$aid)->value('path');
            }
            $pids =$parent_path;
            $parentList = Db::name('member')->alias('m')
                ->join('member_level ml','m.levelid = ml.id')
                ->where('m.id','in',$pids)
                ->where('ml.can_agent','>',0)
                ->field('m.id,m.levelid,m.pid,ml.business_zt_ratio,ml.business_jt_ratio,ml.business_pj_ratio,ml.business_zt_pj_ratio,ml.business_jt_pj_ratio')
                ->order(Db::raw('field(m.id,'.$pids.')'))->select()->toArray();
            $parentList = array_reverse($parentList);//父级从近到远，，上一级，上二级，上三级。。。
            $is_zt = 0;//是否已经发放直推
            $zt_commission = 0;
            $jt_levelid = 0;
            $jt_commission = 0;
            foreach($parentList as $key=>$pmember){
               $ppmember = $parentList[$key+1];
               if($pmember['business_zt_ratio'] > 0 && $is_zt==0){
                   $zt_commission = dd_money_format($money * $pmember['business_zt_ratio'] * 0.01);
                   \app\common\Member::addcommission($aid,$pmember['id'],$member['id'],$zt_commission,'商户['.$business['name'].']营业直推提成',0,'business_agent');
                   //直推平级奖
                   if($ppmember &&  $ppmember['levelid'] == $pmember['levelid']){
                       $zt_pj_commission = dd_money_format($zt_commission * $ppmember['business_zt_pj_ratio'] * 0.01);
                       if($zt_pj_commission > 0){
                           \app\common\Member::addcommission($aid,$ppmember['id'],$member['id'],$zt_pj_commission,'商户['.$business['name'].']营业直推团队收益');
                       }
                   }
                   $is_zt = 1;
               }
               if($pmember['business_jt_ratio'] > 0 && $jt_commission ==0){
                   //间推
                  $jt_commission = dd_money_format( $pmember['business_jt_ratio'] * $money * 0.01);
                   $jt_commission =dd_money_format( $jt_commission - $zt_commission,2);
                  if($jt_commission){
                      \app\common\Member::addcommission($aid,$pmember['id'],$member['id'],$jt_commission,'商户['.$business['name'].']营业间推提成',0,'business_agent');
                      $jt_levelid = $pmember['levelid'];
                  }
                  continue;
               }
                //出现平级，就发平级
               if($pmember['levelid'] == $jt_levelid && $jt_commission > 0 ){
                   $jt_pj_commission = dd_money_format($pmember['business_jt_pj_ratio'] * $jt_commission * 0.01);
                   if($jt_pj_commission > 0){
                       \app\common\Member::addcommission($aid,$pmember['id'],$member['id'],$jt_pj_commission,'商户['.$business['name'].']营业间推团队收益');
                   }
                   break;
               }
            }
        }
    }
    public static function update_expire_status(){
        Db::name('business')->where('status',1)->where('endtime','>',0)->where('endtime','<', time())->update(['status'=>-1]);
    }

    public static function updateDeposit($aid,$bid,$money,$remark,$type='',$ordernum='')
    {
        if(getcustom('business_deposit')){
            if($money==0) return ;
            $business = Db::name('business')->where('aid',$aid)->where('id',$bid)->find();
            if(!$business) return ['status'=>0,'msg'=>'商家不存在'];
            Db::name('business')->where('aid',$aid)->where('id',$bid)->inc('deposit',$money)->update();

            $data = [];
            $data['aid'] = $aid;
            $data['bid'] = $bid;
            $data['money'] = $money;
            $data['after'] = $business['deposit'] + $money;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            $data['type'] = $type;
            $data['ordernum'] = $ordernum;
            Db::name('business_depositlog')->insert($data);

            return ['status'=>1,'msg'=>''];
        }
    }

    //店铺分销(手动结算)
    public static function business_fenxiao($sysset,$type=0,$ids=[]){
	    if(getcustom('business_fenxiao')){
            //type 0结算自动统计线上订单的 1结算手动录入营业额的
            bcscale(2);
            $aid = $sysset['aid'];
            $now_time = time();
            $yesterday = date('Ymd',$now_time-86400);
            $where = [];
            $where[] = ['aid','=',$aid];
            $where[] = ['type','=',$type];
            $where[] = ['status','=',0];
//            $where[] = ['jiesuan_day','=',$yesterday];
            if($ids){
                $where[] = ['id','in',$ids];
            }
            $lists = Db::name('business_fenxiao')->where($where)->select();

            writeLog('aid'.$aid.'分销进入,可结算数据'.count($lists).'条','business_fenxiao');
            foreach($lists as $v){
                $bid = $v['bid'];
                $yeji = $v['yeji'];
                writeLog('数据ID'.$v['id'].'开始处理','business_fenxiao');
                //dump('数据ID'.$v['id'].'开始处理');
                $butie_yeji = 0;
                $business = Db::name('business')->where('id',$bid)->find();
                $yeji_total = Db::name('business_fenxiao')->where('bid',$bid)->sum('yeji');
                //是否开启保护期
                $protect_end = $business['createtime'] + $business['protect_day']*86400;
                if($yeji_total<$business['mature_yeji'] && $business['protect_status'] && $protect_end>$now_time){
                    //保护期内业绩不足的系统自动补足
                    if($yeji<$business['protect_yeji']){
                        $butie_yeji = bcsub($business['protect_yeji'],$yeji,2);
                    }
                    //成熟期
                    $cost_bili = $business['protect_cost_bili'];//成本比例
                    $plate_bili = $business['protect_plate_bili'];
                    $business_bili = $business['protect_business_bili'];//店铺比例
                    $business_send_bili = $business['protect_business_send_bili'];//发放比例
                    writeLog('商户'.$bid.'处于保护期，补贴业绩'.$butie_yeji.'总业绩'.bcadd($yeji,$butie_yeji),'business_fenxiao');
                    //dump('商户'.$bid.'处于保护期，补贴业绩'.$butie_yeji.'总业绩'.bcadd($yeji,$butie_yeji));
                    $stage = 1;
                }else{
                    //成熟期
                    $cost_bili = $business['mature_cost_bili'];//成本比例
                    $plate_bili = $business['mature_plate_bili'];
                    $business_bili = $business['mature_business_bili'];//店铺比例
                    $business_send_bili = $business['mature_business_send_bili'];//发放比例
                    writeLog('商户'.$bid.'处于成熟期，总业绩'.$yeji,'business_fenxiao');
                    //dump('商户'.$bid.'处于成熟期，总业绩'.$yeji);
                    $stage = 2;
                }
                $yeji = bcadd($yeji,$butie_yeji);
                $business_yeji = bcmul($yeji,$business_bili/100);
                //计算利润
                $cost = bcmul($yeji,$cost_bili/100);
                $plate_price = bcmul($yeji,$plate_bili/100);
                $lirun = bcsub($yeji,bcadd($cost,$plate_price,4));
                $bonus_price = bcmul($lirun,$business_send_bili/100);
                if($bonus_price<=0){
                    continue;
                }
                //1、计算发起人推荐人奖金
                $promoter_arr = json_decode($business['promoter'],true);
                if($promoter_arr){
                    foreach($promoter_arr as $promoter_mid=>$promoter_bili){
                        //计算发起人奖金
                        $promoter_bonus = bcmul($bonus_price,$promoter_bili['promoter_bili']/100);
                        writeLog('商户'.$bid.'发起人ID'.$promoter_mid.'，奖金'.$promoter_bonus,'business_fenxiao');
                        //dump('商户'.$bid.'发起人ID'.$promoter_mid.'，奖金'.$promoter_bonus);
                        if($promoter_bonus>0 && $promoter_mid){
                            \app\common\Member::addcommission($aid,$promoter_mid,0,$promoter_bonus,'店铺发起人奖励',1,'business_fenxiao_promoter');
                            self::bonuslog($promoter_mid,$promoter_bonus,$bid,'business_fenxiao_promoter',$aid,$v['yeji'],$butie_yeji,$v['jiesuan_time']);
                        }
                        //计算发起人推荐人奖金
                        $promoter_tj_mid = Db::name('member')->where('id',$promoter_mid)->value('pid');
                        if($promoter_tj_mid){
                            $promoter_tj_bonus = bcmul($bonus_price,$promoter_bili['promoter_tj_bili']/100);
                        }else{
                            $promoter_tj_bonus = 0;
                        }
                        writeLog('商户'.$bid.'发起人ID'.$promoter_mid.'推荐人ID'.$promoter_tj_mid.'，推荐人奖金'.$promoter_tj_bonus,'business_fenxiao');
                        //dump('商户'.$bid.'发起人ID'.$promoter_mid.'推荐人ID'.$promoter_tj_mid.'，推荐人奖金'.$promoter_tj_bonus);
                        if($promoter_tj_bonus>0 && $promoter_tj_mid){
                            \app\common\Member::addcommission($aid,$promoter_tj_mid,$promoter_mid,$promoter_tj_bonus,'店铺发起人推荐奖励',1,'business_fenxiao_promoter_tj');
                            self::bonuslog($promoter_tj_mid,$promoter_tj_bonus,$bid,'business_fenxiao_promoter_tj',$aid,$v['yeji'],$butie_yeji,$v['jiesuan_time']);
                        }


                    }
                }

                //2、合伙人推荐人奖金
                $partner_tj_bonus = bcmul($bonus_price,$business['partner_tj_bili']/100);
                $partner_arr = json_decode($business['partner'],true);
                $partner_tj_mids = '';
                if($partner_arr){
                    $fenshu_total = array_sum(array_column($partner_arr,'num'));
                    foreach($partner_arr as $k2=>$v2){
                        $partner_pid = Db::name('member')->where('id',$v2['id'])->value('pid');
                        $partner_tj_mids .= ','.$partner_pid;
                        $partner_tj_bonus_avg = bcmul(bcdiv($partner_tj_bonus,$fenshu_total,4),$v2['num'],4);
                        writeLog('商户'.$bid.'合伙人ID'.$v2['id'].'推荐人ID'.$partner_pid.'占份额'.$v2['num'].'，合伙人推荐人奖金'.$partner_tj_bonus_avg,'business_fenxiao');
                        //dump('商户'.$bid.'合伙人推荐人总奖金'.$partner_tj_bonus.',合伙人ID'.$v2['id'].'推荐人ID'.$partner_pid.'占份额'.$v2['num'].'，合伙人推荐人奖金'.$partner_tj_bonus_avg);
                        if($partner_tj_bonus_avg>0 && $partner_pid){
                            \app\common\Member::addcommission($aid,$partner_pid,$v2['id'],$partner_tj_bonus_avg,'店铺合伙人推荐奖励',1,'business_fenxiao_partner_tj');
                            self::bonuslog($partner_pid,$partner_tj_bonus_avg,$bid,'business_fenxiao_partner_tj',$aid,$v['yeji'],$butie_yeji,$v['jiesuan_time']);
                        }
                    }
                }

                //3、净利润
                //$lirun = bcsub($bonus_price,bcadd($promoter_tj_bonus,$partner_tj_bonus));
               // $lirun = $bonus_price;
                writeLog('商户'.$bid.'净利润'.$lirun,'business_fenxiao');
                //dump('商户'.$bid.'净利润'.$bonus_price_new);
                //$plate_price = bcmul($lirun,$plate_bili/100);
                //再根据设置的门店比例计算
                //$business_yeji = bcmul($lirun,$business_bili/100);
                //再根据设置的发放比例计算
                //$bonus_price_new = bcmul($business_yeji,$business_send_bili/100);
                $bonus_price_new = $bonus_price;
                writeLog('商户'.$bid.'平台获得'.$plate_price.'，发放基数业绩'.$bonus_price_new,'business_fenxiao');
                //4、计算发起人奖金
//                $promoter_bonus = bcmul($bonus_price_new,$business['promoter_bili']/100);
//                writeLog('商户'.$bid.'发起人ID'.$promoter_mid.'，奖金'.$promoter_bonus,'business_fenxiao');
//                //dump('商户'.$bid.'发起人ID'.$promoter_mid.'，奖金'.$promoter_bonus);
//                if($promoter_bonus>0 && $promoter_mid){
//                    \app\common\Member::addcommission($aid,$promoter_mid,0,$promoter_bonus,'店铺发起人奖励',1,'business_fenxiao_promoter');
//                    self::bonuslog($promoter_mid,$promoter_bonus,$bid,'business_fenxiao_promoter',$aid,$v['yeji'],$butie_yeji,$v['jiesuan_time']);
//                }
                //5、计算合伙人奖金
                $partner_bonus = bcmul($bonus_price_new,$business['partner_bili']/100);
                $partner_mids = '';
                if($partner_arr){
                    $fenshu_total = array_sum(array_column($partner_arr,'num'));
                    foreach($partner_arr as $k3=>$v3){
                        $partner_mids .= ','.$v3['id'];
                        $partner_bonus_avg = bcmul(bcdiv($partner_bonus,$fenshu_total,4),$v3['num']);
                        writeLog('商户'.$bid.'合伙人总奖金'.$partner_bonus.',合伙人ID'.$v3['id'].'占份额'.$v3['num'].'，奖金'.$partner_bonus_avg,'business_fenxiao');
                        //dump('商户'.$bid.'合伙人总奖金'.$partner_bonus.',合伙人ID'.$v3['id'].'占份额'.$v3['num'].'，奖金'.$partner_bonus_avg);
                        if($partner_bonus_avg>0 && $v3['id']){
                            \app\common\Member::addcommission($aid,$v3['id'],0,$partner_bonus_avg,'店铺合伙人奖励',1,'business_fenxiao_partner');
                            self::bonuslog($v3['id'],$partner_bonus_avg,$bid,'business_fenxiao_partner',$aid,$v['yeji'],$butie_yeji,$v['jiesuan_time']);
                        }
                    }
                }
                //更新分销记录
                $data_u = [];
                $data_u['butie_yeji'] = $butie_yeji;
                $data_u['yeji'] = bcadd($v['yeji'],$butie_yeji);
                $data_u['cost'] = $cost;
                $data_u['lirun'] = $lirun;
                $data_u['business'] = $business_yeji;
                $data_u['plate'] = $plate_price;
                $data_u['business_send'] = $bonus_price_new;
                $data_u['promoter_mid'] = $promoter_mid;
                $data_u['promoter'] = $promoter_bonus;
                $data_u['promoter_tj_mid'] = $promoter_tj_mid;
                $data_u['promoter_tj'] = $promoter_tj_bonus;
                $data_u['partner_mids'] = ltrim($partner_mids,',');
                $data_u['partner'] = $partner_bonus;
                $data_u['partner_tj_mids'] = ltrim($partner_tj_mids,',');
                $data_u['partner_tj'] = $partner_tj_bonus;
                $data_u['sendtime'] = time();
                $data_u['status'] = 1;
                $data_u['stage'] = $stage;
                Db::name('business_fenxiao')->where('id',$v['id'])->update($data_u);
                //插入分销奖励记录，用于前台统计展示

            }
            return true;
        }
    }
    public static function bonuslog($mid,$bonus,$bid,$type,$aid,$yeji,$butie_yeji,$jiesuan_time){
        if(getcustom('business_fenxiao')) {
            $data = [];
            $data['aid'] = $aid;
            $data['mid'] = $mid;
            $data['bonus'] = $bonus;
            $data['bid'] = $bid;
            $data['type'] = $type;
            $data['createtime'] = time();
            $data['yeji'] = $yeji;
            $data['butie_yeji'] = $butie_yeji;
            $data['jiesuan_time'] = $jiesuan_time;
            Db::name('business_fenxiao_bonus')->insert($data);
            //记录会员各门店获得佣金汇总
            $map = [];
            $map[] = ['aid','=',$aid];
            $map[] = ['mid','=',$mid];
            $map[] = ['bid','=',$bid];
            $exit = Db::name('business_fenxiao_bonus_total')->where($map)->find();
            if($exit){
                $data_t = [];
                $data_t['bonus_total'] = bcadd($exit['bonus_total'],$bonus,2);
                $data_t['remain'] = bcadd($exit['remain'],$bonus,2);
                Db::name('business_fenxiao_bonus_total')->where('id',$exit['id'])->update($data_t);
            }else{
                $data_t = [];
                $data_t['aid'] = $aid;
                $data_t['mid'] = $mid;
                $data_t['bid'] = $bid;
                $data_t['bonus_total'] = $bonus;
                $data_t['remain'] = $bonus;
                Db::name('business_fenxiao_bonus_total')->insert($data_t);
            }
            return true;
        }
    }
    //统计线上店铺每日营业额
    public static function countBusinessYeji($payorder){
	    if(getcustom('business_fenxiao')){
	        if($payorder['business_fenxiao']){
	            return true;
            }
            $aid = $payorder['aid'];
            $bid = $payorder['bid'];
            $yeji = $payorder['money'];
            $jiesuan_day = date('Ymd',$payorder['paytime']);
            $exit = Db::name('business_fenxiao')->where('bid',$bid)->where('type',0)->where('status',0)->where('jiesuan_day',$jiesuan_day)->find();
            if($exit){
                $data = [];
                $data['yeji'] = bcadd($exit['yeji'],$yeji,2);
                Db::name('business_fenxiao')->where('id',$exit['id'])->update($data);
            }else{
                $data = [];
                $data['aid'] = $aid;
                $data['bid'] = $bid;
                $data['yeji'] = $yeji;
                $data['type'] = 0;
                $data['jiesuan_time'] = $payorder['paytime']?:time();
                $data['jiesuan_day'] = date('Ymd',$payorder['paytime']);
                Db::name('business_fenxiao')->insert($data);
            }
            Db::name('payorder')->where('id',$payorder['id'])->update(['business_fenxiao'=>1]);
            return true;
        }
    }

    public static function getUserAgentBids($aid,$user=[]){
        if(getcustom('user_area_agent') && $user['isadmin']==3) {
            $agentLevel = [
                '1' => [
                    'user_field' => 'agent_province',
                    'business_field' => 'province',
                ],
                '2' => [
                    'user_field' => 'agent_province',
                    'business_field' => 'province',
                ],
                '3' => [
                    'user_field' => 'agent_province',
                    'business_field' => 'province',
                ],
            ];
            $where = [];
            $where[] = ['aid','=',$aid];
            if($user['agent_level']>0){
                if ($user['agent_level'] > 0 ) {
                    $where[] = ['province','=',$user['agent_province']];
                }
                if ($user['agent_level'] > 1 ) {
                    $where[] = ['city','=',$user['agent_city']];
                }
                if ($user['agent_level'] > 2 ) {
                    $where[] = ['area','=',$user['agent_area']];
                }
                $areaBids = Db::name('business')->where($where)->column('id');
                $areaBids[] = 0;//平台
            }else{
                $areaBids = [0];
            }
            return $areaBids;
        }
    }


	//加销售额度
	public static function addsalesquota($aid,$bid,$money,$remark,$orderid){
		if($money==0) return ;
		$business = Db::name('business')->where('aid',$aid)->where('id',$bid)->find();
		if(!$business) return ['status'=>0,'msg'=>'商家不存在'];
		Db::name('business')->where('aid',$aid)->where('id',$bid)->inc('total_sales_quota',$money)->update();
		
		$data = [];
		$data['aid'] = $aid;
		$data['bid'] = $bid;
		$data['money'] = $money;
		$data['after'] = $business['total_sales_quota'] + $money;
		$data['createtime'] = time();
		$data['remark'] = $remark;
        $data['orderid'] = $orderid;
		Db::name('business_salesquota_log')->insert($data);
		return ['status'=>1,'msg'=>''];
	}


    public static function totalTurnover($aid, $bid)
    {
        //店铺总销售额   包含订单、买单
        $total = 0;
        $total += Db::name("maidan_order")->where('aid', $aid)->where('bid', $bid)->where('status', 1)->sum('money');
        $total += Db::name("lucky_collage_order")->where('aid', $aid)->where('bid', $bid)->where('status', 'in', [1, 2, 3])->sum('totalprice');
        $total += Db::name("collage_order")->where('aid', $aid)->where('bid', $bid)->where('status', 'in', [1, 2, 3])->sum('totalprice');
        $total += Db::name("kanjia_order")->where('aid', $aid)->where('bid', $bid)->where('status', 'in', [1, 2, 3])->sum('totalprice');
        $total += Db::name("shop_order")->where('aid', $aid)->where('bid', $bid)->where('status', 'in', [1, 2, 3])->sum('totalprice');
        $total += Db::name("tuangou_order")->where('aid', $aid)->where('bid', $bid)->where('status', 'in', [1, 2, 3])->sum('totalprice');
        $total += Db::name("seckill_order")->where('aid', $aid)->where('bid', $bid)->where('status', 'in', [1, 2, 3])->sum('totalprice');
        return round($total, 2);
    }


    //获取管理员所管理的商户id集合
    public static function get_auth_bids($user){
        if(getcustom('user_auth_province',$user['aid'])){
            $where = 'all';
            $bids = 'all';
            //管理员省市权限
            if($user['isadmin']!=1 && $user['provinces']){
                $bids = [];
                $where = [];
                $city_arr = [];
                $province_arr = [];
                $county_arr = [];
                $regionlist = explode('];',$user['provinces']);
                foreach($regionlist as $j=>$regiondata){
                    if(!$regiondata) continue;
                    $regiondata = explode('[',$regiondata);
                    $province = $regiondata[0];
                    $citys = explode(',',$regiondata[1]);
                    foreach($citys as $city_str){
                        $city_str_arr = explode('|',$city_str);
                        $city = $city_str_arr[0];
                        $county_str = $city_str_arr[1];
                        $county_arr = [];
                        if($county_str!='全部县区' && !empty($county_str)){
                            $county_arr = explode('-',$county_str);
                        }
                        $map = [];
                        $map[] = ['aid','=',$user['aid']];
                        $map[] = ['province','=',$province];
                        $map[] = ['city','=',$city];
                        if($county_arr){
                            $map[] = ['district','in',$county_arr];
                        }
                        $area_bids = Db::name('business')->where($map)->column('id');
                        $bids = array_merge($bids,$area_bids);
                    }
                    $city_arr = array_merge($city_arr,$citys);
                    array_push($province_arr,$regiondata[0]);
                }
                //$bids = Db::name('business')->where('aid',$user['aid'])->where('province','in',$province_arr)->where('city','in',$city_arr)->column('id');
                if(!$bids){
                    $bids = ['-1'];
                }
            }
            return $bids;
        }
    }


    //商家买单拉新奖励记录
    public static function maidan_invite_log($aid,$order,$invitemoney){
	    if(getcustom('maidan_invite')){
	        $log = [
	            'aid' => $aid,
                'bid' => $order['bid'],
                'invitemoney' => $invitemoney,
                'mid' => $order['mid'],
                'orderid' => $order['id'],
                'createtime' => time(),
            ];
            Db::name('maidan_invite_log')->insert($log);
        }
    }
    
    //商户押金操作
    public static function addDeposit($aid,$bid,$money=0,$deposit_id=0,$remark='',$type='shop'){
	    if(getcustom('deposit') && getcustom('deposit_business')){
            if($money==0) return ;
            $business = Db::name('business')->where('aid',$aid)->where('id',$bid)->lock(true)->find();
            if(!$business) return ['status'=>0,'msg'=>'商家不存在'];
            $after = $business['deposit_money'] + $money;
            Db::name('business')->where('aid',$aid)->where('id',$bid)->inc('deposit_money',$money)->update();
            $deposit_name = Db::name('deposit')->where('aid',$aid)->where('id',$deposit_id)->value('name');

            $data = [];
            $data['aid'] = $aid;
            $data['bid'] = $bid;
            $data['name'] = $deposit_name??'';
            $data['deposit_id'] = $deposit_id;
            $data['money'] = $money;
            $data['after'] = $after;
            $data['createtime'] = time();
            $data['remark'] = $remark;
            $data['type'] = $money<0?1:0;
          
            Db::name('business_deposit_log')->insert($data);
            return ['status'=>1,'msg'=>''];
        }
    }


    //添加商户默认数据
    public static function addDefaultData($aid,$bid){
        //暂时只有分类，后续有需求可以继续加,前台的ApiBusiness/apply、后台的Business/edit 会调用这里
        Db::name('shop_category2')->insertGetId(['aid'=>$aid,'bid'=>$bid,'name'=>'分类一','pic'=>PRE_URL.'/static/imgsrc/picture-1.jpg']);
        Db::name('shop_category2')->insertGetId(['aid'=>$aid,'bid'=>$bid,'name'=>'分类二','pic'=>PRE_URL.'/static/imgsrc/picture-2.jpg']);
        Db::name('shop_category2')->insertGetId(['aid'=>$aid,'bid'=>$bid,'name'=>'分类三','pic'=>PRE_URL.'/static/imgsrc/picture-3.jpg']);
    }
}
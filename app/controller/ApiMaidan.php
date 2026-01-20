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

//买单
namespace app\controller;
use think\facade\Db;
class ApiMaidan extends ApiCommon
{
	public function initialize(){
		parent::initialize();
        $bid = input('?param.bid') ? input('param.bid') : 0;
        //记录接口访问请求的bid
        if($bid > 0) cache($this->sessionid.'_api_bid',$bid,3600);
		$action = request()->action();
		$maidan_login = $this->sysset['maidan_login'];//收款强制登录 1开启 0关闭
		if($action!='maidan' && $action!='maidanlog' && $action!="dealdata"){
            $this->checklogin();
        }

        if($action=='maidan' && ($maidan_login==1 || !in_array(platform,['wx','alipay','mp']))){
            $params = [];
            //买单页面后台开启了强制登录
            $this->checklogin(0,$params);
        }
	}
	//买单收款
	public function maidan(){
		$adminset = $this->sysset;
		$adminset_custom = $this->sysset_custom;
		$scoredkmaxpercent = $adminset['scoredkmaxpercent'];
		$bid = input('param.bid') ? input('param.bid') : 0;
        if($bid > 0){
            $business = Db::name('business')->where('aid',aid)->where('id', $bid)->find();
            if(empty($business)){
                return $this->json(['status'=>0,'msg'=>'商家不存在']);
            }
            if($business['status'] != 1){
                return $this->json(['status'=>0,'msg'=>'商家状态异常']);
            }
			}
        if(request()->isGet()){
        	if($this->member){
	        	//进入买单页面时，返还那些待支付的抵扣金
	    		\app\common\Order::deal_decreturn(aid,$bid,mid,0,'maidan');
	    	}
        }
        if($this->member){
        	//再次查询余额，有返回的数据
	        $member_money = Db::name('member')->where('id',mid)->value('money');

	        $money_weishu = 2;
	        //取最小余额位数，防止五入超出最大值
			$tenpow = pow(10, $money_weishu);
			if($tenpow>0){
				$member_money = floor($member_money * $tenpow)/$tenpow;
			}else{
				$member_money = floor($member_money);
			}
	        $this->member['money'] = $member_money;
	    }
		//第一个静默登录，第二次必须绑定手机号
		$freezemoneydec = false;
        $freezemoney_dec_rate = 0;
        if(request()->isPost()){
			$post = input('post.');
			$money = floatval($post['money']);
			if($money <= 0){
				return $this->json(['status'=>0,'msg'=>'支付金额必须大于0']);
			}
			$paymoney = $money;
			//会员折扣
			$disprice = 0;
			$userlevel = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->find();
			if($userlevel && $userlevel['discount']>0 && $userlevel['discount']<10){
				$disprice = round($paymoney * (1 - $userlevel['discount'] * 0.1), 2);
			}
			$paymoney = $paymoney - $disprice;
            //买单项目
            //优惠券
			if($post['couponrid'] > 0){
				$couponrid = $post['couponrid'];
				$couponrecord = Db::name('coupon_record')->where('aid',aid)->where('mid',mid)->where('id',$couponrid)->find();
				if(!$couponrecord){
					return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'不存在']);
				}elseif($couponrecord['status']!=0){
					return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'已使用过了']);
				}elseif($couponrecord['starttime'] > time()){
					return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'尚未开始使用']);	
				}elseif($couponrecord['endtime'] < time()){
					return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'已过期']);	
				}elseif($couponrecord['minprice'] > $money){
					return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'不符合条件']);
				}elseif(! in_array($couponrecord['type'],[1,10])){
					return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'不符合条件']);	
				}
                if($couponrecord['type']==10) {//折扣券    
                    $couponmoney = $paymoney * (100 - $couponrecord['discount']) * 0.01;
                    if ($couponmoney > $paymoney) $couponmoney = $paymoney;
                }else{
                    $couponmoney = $couponrecord['money'];
                    if($couponmoney > $money) $couponmoney = $money;
                }

                }else{
				$couponmoney = 0;
			}
			$paymoney = $paymoney - $couponmoney;
			if($paymoney < 0) $paymoney = 0;
			//积分抵扣
			if($post['usescore']==1){
				$score2money = $adminset['score2money'];
				$scoredkmaxpercent = $adminset['scoredkmaxpercent'];
				$scoredk = $this->member['score'] * $score2money;
				if($scoredk > $paymoney) $scoredk = $paymoney;
				if($scoredkmaxpercent >= 0 && $scoredkmaxpercent <= 100 && $scoredk > 0 && $scoredk > $paymoney * $scoredkmaxpercent * 0.01){
					$scoredk = $paymoney * $scoredkmaxpercent * 0.01;
				}
				$paymoney = $paymoney - $scoredk;
				$paymoney = round($paymoney*100)/100;
				if($paymoney < 0) $paymoney = 0;
				if($scoredk > 0){
					$decscore = $scoredk / $score2money;
				}else{
					$decscore = 0;
				}
			}else{
				$scoredk = 0;
				$decscore = 0;
			}

			$score_weishu = 0;
			$decscore = dd_score_format($decscore,$score_weishu);

			//计算冻结资金抵扣
            //$mendian = Db::name('mendian')->where('id',$post['mdid'])->find();
			if($bid > 0){
				$bname = $business['name'];
			}else{
				$bname = $adminset['name'];
			}
			//创建订单
			$order = [];
			$order['ordernum'] = date('ymdHis').aid.rand(1000,9999);
			$order['aid'] = aid;
			$order['bid'] = $bid;
			$order['mid'] = mid;
			$order['title'] = '付款给'.$bname;
			$order['money'] = $money;
			$order['paymoney'] = $paymoney;
			$order['disprice'] = $disprice;
			$order['scoredk'] = $scoredk;
			$order['decscore'] = $decscore;
			$order['couponrid'] = $couponrid;
			$order['couponmoney'] = $couponmoney; //优惠券抵扣
			$order['createtime'] = time();
			$order['platform'] = platform;
			$order['status'] = 0;
			$order['mdid'] = $post['mdid'];
			$order['remark'] = $post['remark']?$post['remark']:'';
			$money_dec_commission_fenhong = 0;
            //多商户商品不参与分红时
            $orderid = Db::name('maidan_order')->insertGetId($order);

			$payorderid = \app\model\Payorder::createorder(aid,$order['bid'],$order['mid'],'maidan',$orderid,$order['ordernum'],$order['title'],$order['paymoney'],$order['decscore']);

			return $this->json(['status'=>1,'payorderid'=>$payorderid]);
		}

		$userlevel = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->find();
		$userinfo = [];
		$userinfo['discount'] = $userlevel['discount'];
		$userinfo['score'] = $this->member['score'];
		$userinfo['score2money'] = $adminset['score2money'];
		$userinfo['dkmoney'] = round($userinfo['score'] * $userinfo['score2money'],2);
		$userinfo['scoredkmaxpercent'] = $scoredkmaxpercent;
		$userinfo['money'] = $this->member['money'];
        $userinfo['maidan_getlocation'] = $adminset['maidan_getlocation'];
        if($this->member['paypwd']==''){
			$userinfo['haspwd'] = 0;
		}else{
			$userinfo['haspwd'] = 1;
		}
		if($bid > 0){
			$bname = $business['name'];
			$bcids = $business['cid'] ? explode(',',$business['cid']) : [];
		}else{
			$bcids = [];
		}
		if($bcids){
			$whereCid = [];
			foreach($bcids as $bcid){
				$whereCid[] = "find_in_set({$bcid},canused_bcids)";
			}
			$whereCids = implode(' or ',$whereCid);
		}else{
			$whereCids = '0=1';
		}

        $couponList = Db::name('coupon_record')
			->where('aid',aid)->where('mid',mid)->where('type','in',[1,10])->where('status',0)->where('starttime','<=',time())->where('endtime','>',time())
			->whereRaw("bid=-1 or bid=".$bid." or (bid=0 and (canused_bids='all' or find_in_set(".$bid.",canused_bids) or ($whereCids)))")
			->order('id desc')->select()->toArray();

		if(!$couponList) $couponList = [];
		$newcouponlist = [];
		foreach($couponList as $k=>$v){
			//$couponList[$k]['starttime'] = date('m-d H:i',$v['starttime']);
			//$couponList[$k]['endtime'] = date('m-d H:i',$v['endtime']);
            $v['bname'] = $bname;
			$couponinfo = Db::name('coupon')->where('aid',aid)->where('id',$v['couponid'])->find();
			$fwtype = [0];
			if(!in_array($couponinfo['fwtype'],$fwtype)){//全部可用 
				continue;
			}
			//适用场景
			$fwscene = [0];
            if(!in_array($couponinfo['fwscene'],$fwscene)){//全部可用 
                continue;
            }
            if($couponinfo['isgive']==2 && !$v['from_mid']){//仅赠送
                continue;
            }
			$newcouponlist[] = $v;
		}
		$couponList = $newcouponlist;
		//门店
		$whereM = [];
		$whereM[] = ['aid','=',aid];
		$whereM[] = ['status','=',1];
	
		if($bid>0){
			$whereM[] = ['bid','=',$bid];
		}else{
            $bids = [0];
			$whereM[] = ['bid','in',$bids];
		}
        //是不是置顶
		$mdlist = Db::name('mendian')->where($whereM)->order('id')->select()->toArray();
		if(!$mdlist) $mdlist = [];
		if($bid > 0){
			$adminset['name'] = $business['name'];
			$adminset['logo'] = $business['logo'];
		}
		$rdata = [];
        //买单广告
        $adlist = [];
        $sysset = [];
        $rdata['sysset'] = $sysset??[];
        $rdata['adlist'] = $adlist;
		$rdata['userinfo'] = $userinfo;
		$rdata['couponList'] = $couponList;
		$rdata['wxpayst'] = $adminset['wxpay'];
		$rdata['alipay'] = $adminset['alipay'];
		$rdata['moneypay'] = $adminset['moneypay'];
		$rdata['name'] = $adminset['name'];
		$rdata['logo'] = $adminset['logo'];
		$rdata['mdlist'] = $mdlist;
		//判断是否登录返回给前端，没登录的话前端静默注册
        $mid = mid;
		$need_login = 0;
		$have_login = 1;
		$login_tip = '';
		if(!$mid){
		    $need_login = 1;
            }
		$rdata['need_login'] = $need_login;
        $rdata['have_login'] = $have_login;
        $rdata['login_tip'] = $login_tip;

        $alipayapp = \app\common\System::appinfo(aid,'alipay');
        $rdata['ali_appid'] = $alipayapp['appid'];
        // 剪切板

        //冻结资金抵扣
        $rdata['freezemoneydec']       = $freezemoneydec;
        $rdata['freezemoney_dec_rate'] = $freezemoney_dec_rate;

        //激活币比例
        $activecoin_bili = 0;
        $rdata['activecoin_bili'] = $activecoin_bili;

        //新积分比例
        $newscore_ratio = 0;
        $newscore_pack_ratio = 0;
        $newscore_ratio_business = 0;
        $rdata['newscore_ratio'] = $newscore_ratio;
        $rdata['newscore_pack_ratio'] = $newscore_pack_ratio;
        $rdata['newscore_ratio_business'] = $newscore_ratio_business;
        return $this->json($rdata);
	}

	public function maidanlog(){
		$pagenum = input('post.pagenum');
        $st = input('post.st');
		if(!$pagenum) $pagenum = 1;
		$pernum = 20;
		if(input('param.keyword')) $where[] = ['ordernum|paynum','like','%'.input('param.keyword').'%'];
        if(false){}else{
            $where = [];
            $where[] = ['aid','=',aid];
            $where[] = ['mid','=',mid];
            $where[] = ['status','=',1];
        }

        $datalist = Db::name('maidan_order')->field('*,from_unixtime(paytime)paytime')->where($where)->page($pagenum,$pernum)->order('id desc')->select()->toArray();

		if($datalist){
			foreach($datalist as &$dv){
				}
			unset($dv);
		}else{
			$datalist = [];
		}
        $canrefund = false;
        if(request()->isPost()){
			return $this->json(['status'=>1,'data'=>$datalist,'canrefund'=>$canrefund]);
		}
		$count = Db::name('maidan_order')->where($where)->count();
		$rdata = [];
		$rdata['count'] = $count;
		$rdata['datalist'] = $datalist;
		$rdata['pernum'] = $pernum;
		$rdata['st'] = $st;
        $rdata['canrefund'] = $canrefund;
		return $this->json($rdata);
	}
	public function maidandetail(){
		$id = input('param.id/d');
        if(!$id)
            return $this->json(['status'=>0,'msg'=>'参数错误']);
		$detail = Db::name('maidan_order')->where('aid',aid)->where('mid',mid)->where('id',$id)->find();
		$detail['paytime'] = date('Y-m-d H:i:s',$detail['paytime']);
		if($detail['couponrid']){
			$couponrecord = Db::name('coupon_record')->where('aid',aid)->where('mid',mid)->where('id',$detail['couponrid'])->find();
		}else{
			$couponrecord = false;
		}
		if($detail['mdid']){
			$mendian = Db::name('mendian')->field('id,name')->where('aid',aid)->where('id',$detail['mdid'])->find();
		}else{
			$mendian = false;
		}
        if($detail['paynum']){
            $detail['paynum'] = '';
        }
        $detail['canrefund'] = false;
        $rdata = [];
		$rdata['detail'] = $detail;
		$rdata['couponrecord'] = $couponrecord;
		$rdata['mendian'] = $mendian;
		return $this->json($rdata);
	}

	//延迟处理一些数据，如：未支付返回此页面时，余额抵扣需要返还等
    public function dealdata(){
        if(request()->isGet()){
            $bid = input('param.bid') ? input('param.bid') : 0;
            if($this->member){
            	//延迟一秒，防止和maidan()方法冲突
            	sleep(1);
                if(getcustom('maidan_money_dec') || getcustom('pay_money_combine_maidan') || getcustom('member_shopscore') || getcustom('member_dedamount')){
                    //获取今日次数，限制10次，防止频繁操作
                    $nowtime = strtotime(date("Y-m-d",time()));
                    $maidan_dealdatanum  = 'maidan_dealdatanum_'.aid.'_'.$bid.'_'.mid.'_'.$nowtime;
                    $can = true;
                    $dealdatanum = cache($maidan_dealdatanum);
                    if(!$dealdatanum){
                        cache($maidan_dealdatanum,1);
                    }else{
                        if($dealdatanum >=10){
                            $can = false;
                        }else{
                            $dealdatanum ++;
                            cache($maidan_dealdatanum,$dealdatanum);
                        }
                    }
                    if($can){
                        //进入买单页面时，返还那些待支付的抵扣金
                        \app\common\Order::deal_decreturn(aid,$bid,mid,0,'maidan');
                    }
                }
            }
        }
    }
    // 设置
    public function set(){
        }

    public function getPredictNewScore(){
        $maidan_orderadd_mobile_paytransfer = getcustom('maidan_orderadd_mobile_paytransfer');
        $yx_new_score = getcustom('yx_new_score');
        if($maidan_orderadd_mobile_paytransfer && $yx_new_score){
            $bid = input('param.bid') ? input('param.bid') : 0;
            $money = input('param.money') ? input('param.money') : 0;
            $mid = input('param.mid') ? input('param.mid') : 0;

            if(!$bid){
                return $this->json(['status'=>0,'msg'=>'商户ID不能为空']);
            }
            $aid = aid;

            $member = Db::name('member')->where('id',$mid)->where('aid',$aid)->find();

            $set = Db::name('newscore_set')->where('aid',$aid)->find();
            $business_set = Db::name('business_sysset')->where('aid',$aid)->find();
            $business = Db::name('business')->where('aid',$aid)->where('id',$bid)->find();

            if($bid>0){
                //商户订单按商户独立设置比例
                $binfo = Db::name('business')->where('aid',$aid)->where('id',$bid)
                    ->field('mid,newscore_ratio,member_newscore_ratio,business_newscore_ratio,province, city,district')->find();
            }else{
                $binfo = Db::name('admin_set')->where('aid',$aid)->field('province, city,district')->find();
            }

            //会员折扣
//            $disprice = 0;
//            $userlevel = Db::name('member_level')->where('aid',$aid)->where('id',$member['levelid'])->find();
//            if($userlevel && $userlevel['discount']>0 && $userlevel['discount']<10){
//                $disprice = $money * (1 - $userlevel['discount'] * 0.1);
//            }
//            $money = $money - $disprice;

            $order_goods = [
                    'id' => 0,
                    'real_totalprice' => $money,
                    'cost_price' => 0,
                    'dec_money' => 0,
                    'num' => 1,
                    'proid' => 0,
                    'bid' => $bid,
            ];
            $order['status'] = 3;
            $area = [
                $binfo['province'],
                $binfo['city'],
                $binfo['district']
            ];

            $og = [
                'status' => 1,
                'mid' => $mid,
                'bid' => $bid,
                'order' => $order,
                'order_goods' => $order_goods,
                'area' => $area,
                'real_totalprice' => $order_goods['real_totalprice'],
            ];

            $res_score = \app\custom\NewScore::getProductNewScore($aid,$set,$og,0,'maidan');

            $newscore_m =  $res_score['newscore_m'];
            $newscore_b =  $res_score['newscore_b'];

            //计算推荐人佣金
            $isCommissionScore = 0;
            //买单分销 -1关闭 0:跟随系统更加（默认） 1:单独设置
            $commissionset = 0;
            $commissiondata1 = [];//1:单独设置时，金额提成比例
            $commissiondata2 = [];//2:单独设置时，固定金额
            $fenxiao_paymoney = $money;
            $ogdata = [];
            //是否积分提成
            $ogdata['isparent1score'] = 0;
            $ogdata['isparent2score'] = 0;
            $ogdata['isparent3score'] = 0;
            if($member['pid']){
                $parent1 = Db::name('member')->where('aid',$aid)->where('id',$member['pid'])->find();
                if($parent1){
                    $agleveldata1 = Db::name('member_level')->where('aid',$aid)->where('id',$parent1['levelid'])->find();
                    if($agleveldata1['can_agent']!=0){
                        $ogdata['parent1'] = $parent1['id'];
                        if($isCommissionScore && $agleveldata1['maidan_commission_score1']>0){
                            $ogdata['isparent1score'] = 1;
                            $ogdata['parent1score'] = round($agleveldata1['maidan_commission_score1'] * $fenxiao_paymoney * 0.01);
                        }
                    }
                }
            }

            //买单分销
            if($commissionset != -1){
                //单独设置
                if($commissionset == 1){
                    //提成比例
                    if($commissiondata1){
                        //if($agleveldata1) $ogdata['parent1commission'] = $commissiondata1[$agleveldata1['id']]['commission1'] * $fenxiao_paymoney * 0.01;
                        if($agleveldata1) $ogdata['parent1commission'] = $commissiondata1[$agleveldata1['id']]['commission1'] * $newscore_m * 0.01;
                    }
                }else if($commissionset == 2){
                    //固定金额
                    if($commissiondata2){
                        if($agleveldata1) $ogdata['parent1commission'] = $commissiondata2[$agleveldata1['id']]['commission1'];
                    }
                }else if($commissionset == 0){
                    //会员等级 分销设置
                    if($agleveldata1['commissiontype']==1){ //固定金额按单
                        $ogdata['parent1commission'] = $agleveldata1['commission1'];
                    }else{
                        //$ogdata['parent1commission'] = $agleveldata1['commission1'] * $fenxiao_paymoney * 0.01;
                        $ogdata['parent1commission'] = $agleveldata1['commission1'] * $newscore_m * 0.01;
                    }
                }
            }

            //需要支付的金额“订单金额 X 该商家让利比例”
            $newscore_ratio_business = $business['newscore_ratio']/100;

            $info = [
                'newscore_m' => $newscore_m ? sprintf('%g',$newscore_m) : 0,
                'newscore_b' => $newscore_b ? sprintf('%g',$newscore_b) : 0,
                'newscore_ratio_business' => $newscore_ratio_business ?? 0,
                'parent1commission' => !$member['pid'] ? '无邀请人信息' : sprintf('%g',$ogdata['parent1commission']),
            ];

            return $this->json(['status'=>1,'data'=>$info,'msg'=>'查询成功']);
        }
    }
    public function maidan_paytransfer(){
        $maidan_orderadd_mobile_paytransfer = getcustom('maidan_orderadd_mobile_paytransfer');
        if($maidan_orderadd_mobile_paytransfer){
            $adminset = $this->sysset;
            $adminset_custom = $this->sysset_custom;
            $scoredkmaxpercent = $adminset['scoredkmaxpercent'];
            $bid = input('param.bid') ? input('param.bid') : 0;
            $mid = input('param.mid') ? input('param.mid') : 0;
            if($bid > 0){
                $business = Db::name('business')->where('aid',aid)->where('id', $bid)->find();
                if(empty($business)){
                    return $this->json(['status'=>0,'msg'=>'商家不存在']);
                }
                if($business['status'] != 1){
                    return $this->json(['status'=>0,'msg'=>'商家状态异常']);
                }
                }
            if(request()->isGet()){
                if($this->member){
                    //进入买单页面时，返还那些待支付的抵扣金
                    \app\common\Order::deal_decreturn(aid,$bid,$mid,0,'maidan');
                }
            }
            if($this->member){
                //再次查询余额，有返回的数据
                $member_money = Db::name('member')->where('id',$mid)->value('money');

                $money_weishu = 2;
                //取最小余额位数，防止五入超出最大值
                $tenpow = pow(10, $money_weishu);
                if($tenpow>0){
                    $member_money = floor($member_money * $tenpow)/$tenpow;
                }else{
                    $member_money = floor($member_money);
                }
                $this->member['money'] = $member_money;
            }
            //第一个静默登录，第二次必须绑定手机号
            $freezemoneydec = false;
            $freezemoney_dec_rate = 0;
            if(request()->isPost()){
                $post = input('post.');
                $money = floatval($post['money']);
                if($money <= 0){
                    return $this->json(['status'=>0,'msg'=>'支付金额必须大于0']);
                }
                if(empty($mid)){
                    return $this->json(['status'=>0,'msg'=>'用户信息不存在']);
                }
                $paymoney = $money;
                //会员折扣
//                $disprice = 0;
//                $userlevel = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->find();
//                if($userlevel && $userlevel['discount']>0 && $userlevel['discount']<10){
//                    $disprice = $paymoney * (1 - $userlevel['discount'] * 0.1);
//                }
//                $paymoney = $paymoney - $disprice;
                //买单项目
                //优惠券
                if($post['couponrid'] > 0){
                    $couponrid = $post['couponrid'];
                    $couponrecord = Db::name('coupon_record')->where('aid',aid)->where('mid',$mid)->where('id',$couponrid)->find();
                    if(!$couponrecord){
                        return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'不存在']);
                    }elseif($couponrecord['status']!=0){
                        return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'已使用过了']);
                    }elseif($couponrecord['starttime'] > time()){
                        return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'尚未开始使用']);
                    }elseif($couponrecord['endtime'] < time()){
                        return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'已过期']);
                    }elseif($couponrecord['minprice'] > $money){
                        return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'不符合条件']);
                    }elseif(! in_array($couponrecord['type'],[1,10])){
                        return $this->json(['status'=>0,'msg'=>'该'.t('优惠券').'不符合条件']);
                    }
                    if($couponrecord['type']==10) {//折扣券
                        $couponmoney = $paymoney * (100 - $couponrecord['discount']) * 0.01;
                        if ($couponmoney > $paymoney) $couponmoney = $paymoney;
                    }else{
                        $couponmoney = $couponrecord['money'];
                        if($couponmoney > $money) $couponmoney = $money;
                    }

                    }else{
                    $couponmoney = 0;
                }
                $paymoney = $paymoney - $couponmoney;
                if($paymoney < 0) $paymoney = 0;
                //积分抵扣
                if($post['usescore']==1){
                    $score2money = $adminset['score2money'];
                    $scoredkmaxpercent = $adminset['scoredkmaxpercent'];
                    $scoredk = $this->member['score'] * $score2money;
                    if($scoredk > $paymoney) $scoredk = $paymoney;
                    if($scoredkmaxpercent >= 0 && $scoredkmaxpercent <= 100 && $scoredk > 0 && $scoredk > $paymoney * $scoredkmaxpercent * 0.01){
                        $scoredk = $paymoney * $scoredkmaxpercent * 0.01;
                    }
                    $paymoney = $paymoney - $scoredk;
                    $paymoney = round($paymoney*100)/100;
                    if($paymoney < 0) $paymoney = 0;
                    if($scoredk > 0){
                        $decscore = $scoredk / $score2money;
                    }else{
                        $decscore = 0;
                    }
                }else{
                    $scoredk = 0;
                    $decscore = 0;
                }

                $score_weishu = 0;
                $decscore = dd_score_format($decscore,$score_weishu);

                //计算冻结资金抵扣
                //$mendian = Db::name('mendian')->where('id',$post['mdid'])->find();
                if($bid > 0){
                    $bname = $business['name'];
                }else{
                    $bname = $adminset['name'];
                }

                if($business['newscore_ratio']){
                    $money_transfer = bcmul($money,($business['newscore_ratio']/100),2);
                }

                //创建订单
                $order = [];
                $order['ordernum'] = date('ymdHis').aid.rand(1000,9999);
                $order['aid'] = aid;
                $order['bid'] = $bid;
                $order['mid'] = $mid;
                $order['title'] = '付款给'.$bname;
                $order['money'] = $money;
                $order['paymoney'] = $paymoney;
                $order['disprice'] = $disprice ?? 0;
                $order['scoredk'] = $scoredk;
                $order['decscore'] = $decscore;
                $order['couponrid'] = $couponrid;
                $order['couponmoney'] = $couponmoney; //优惠券抵扣
                $order['createtime'] = time();
                $order['platform'] = platform;
                $order['status'] = 0;
                $order['mdid'] = $post['mdid'];
                $order['remark'] = $post['remark']?$post['remark']:'';
                $order['money_transfer'] = $money_transfer ?? 0;
                $money_dec_commission_fenhong = 0;
                //多商户商品不参与分红时
                if(!$post['contract_pic']){
                    return $this->json(['status'=>0,'msg'=>'请上传买卖双方合同']);
                }

                if(!$post['payment_voucher_pic']){
                    return $this->json(['status'=>0,'msg'=>'请上传买方付款凭证']);
                }

                if($post['invoice'] == 1 && !$post['invoice_pic']){
                    return $this->json(['status'=>0,'msg'=>'请上传买卖双方发票']);
                }

                if($post['shipping_method'] == 1 && !$post['express_no']){
                    return $this->json(['status'=>0,'msg'=>'请填写快递单号']);
                }

                if(!$post['delivery_voucher_pic']){
                    return $this->json(['status'=>0,'msg'=>'请上传发货凭证']);
                }

                if(!checkTel(aid,$post['notice_tel'])){
                    return $this->json(['status'=>0,'msg'=>'手机号格式不正确']);
                }

                $order['transfer_check'] = 0;
                $order['invoice'] = $post['invoice'];
                $order['contract_pic'] = $post['contract_pic'] ? implode(',',$post['contract_pic']) : '';
                $order['payment_voucher_pic'] = $post['payment_voucher_pic'] ? implode(',',$post['payment_voucher_pic']) : '';
                $order['invoice_pic'] = $post['invoice_pic'] ? implode(',',$post['invoice_pic']) : '';
                $order['shipping_method'] = $post['shipping_method'];
                $order['express_no'] = $post['express_no'];
                $order['delivery_voucher_pic'] = $post['delivery_voucher_pic'] ? implode(',',$post['delivery_voucher_pic']) : '';
                $order['notice_tel'] = $post['notice_tel'];
                $orderid = Db::name('maidan_order')->insertGetId($order);

                $payorderid = \app\model\Payorder::createorder(aid,$order['bid'],$order['mid'],'maidan',$orderid,$order['ordernum'],$order['title'],$order['paymoney'],$order['decscore']);

                return $this->json(['status'=>1,'payorderid'=>$payorderid]);
            }

            $userlevel = Db::name('member_level')->where('aid',aid)->where('id',$this->member['levelid'])->find();
            $userinfo = [];
            $userinfo['discount'] = $userlevel['discount'];
            $userinfo['score'] = $this->member['score'];
            $userinfo['score2money'] = $adminset['score2money'];
            $userinfo['dkmoney'] = round($userinfo['score'] * $userinfo['score2money'],2);
            $userinfo['scoredkmaxpercent'] = $scoredkmaxpercent;
            $userinfo['money'] = $this->member['money'];
            $userinfo['maidan_getlocation'] = $adminset['maidan_getlocation'];
            if($this->member['paypwd']==''){
                $userinfo['haspwd'] = 0;
            }else{
                $userinfo['haspwd'] = 1;
            }
            if($bid > 0){
                $bname = $business['name'];
                $bcids = $business['cid'] ? explode(',',$business['cid']) : [];
            }else{
                $bcids = [];
            }
            if($bcids){
                $whereCid = [];
                foreach($bcids as $bcid){
                    $whereCid[] = "find_in_set({$bcid},canused_bcids)";
                }
                $whereCids = implode(' or ',$whereCid);
            }else{
                $whereCids = '0=1';
            }

            $couponList = Db::name('coupon_record')
                ->where('aid',aid)->where('mid',$mid)->where('type','in',[1,10])->where('status',0)->where('starttime','<=',time())->where('endtime','>',time())
                ->whereRaw("bid=-1 or bid=".$bid." or (bid=0 and (canused_bids='all' or find_in_set(".$bid.",canused_bids) or ($whereCids)))")
                ->order('id desc')->select()->toArray();

            if(!$couponList) $couponList = [];
            $newcouponlist = [];
            foreach($couponList as $k=>$v){
                //$couponList[$k]['starttime'] = date('m-d H:i',$v['starttime']);
                //$couponList[$k]['endtime'] = date('m-d H:i',$v['endtime']);
                $v['bname'] = $bname;
                $couponinfo = Db::name('coupon')->where('aid',aid)->where('id',$v['couponid'])->find();
                $fwtype = [0];
                if(!in_array($couponinfo['fwtype'],$fwtype)){//全部可用
                    continue;
                }
                //适用场景
                $fwscene = [0];
                if(!in_array($couponinfo['fwscene'],$fwscene)){//全部可用
                    continue;
                }
                if($couponinfo['isgive']==2 && !$v['from_mid']){//仅赠送
                    continue;
                }
                $newcouponlist[] = $v;
            }
            $couponList = $newcouponlist;
            //门店
            $whereM = [];
            $whereM[] = ['aid','=',aid];
            $whereM[] = ['status','=',1];

            if($bid>0){
                $whereM[] = ['bid','=',$bid];
            }else{
                $bids = [0];
                $whereM[] = ['bid','in',$bids];
            }
            //是不是置顶
            $mdlist = Db::name('mendian')->where($whereM)->order('id')->select()->toArray();
            if(!$mdlist) $mdlist = [];
            if($bid > 0){
                $adminset['name'] = $business['name'];
                $adminset['logo'] = $business['logo'];
            }
            $rdata = [];
            //买单广告
            $adlist = [];
            $sysset = [];
            $rdata['sysset'] = $sysset??[];
            $rdata['adlist'] = $adlist;
            $rdata['userinfo'] = $userinfo;
            $rdata['couponList'] = $couponList;
            $rdata['wxpayst'] = $adminset['wxpay'];
            $rdata['alipay'] = $adminset['alipay'];
            $rdata['moneypay'] = $adminset['moneypay'];
            $rdata['name'] = $adminset['name'];
            $rdata['logo'] = $adminset['logo'];
            $rdata['mdlist'] = $mdlist;
            //判断是否登录返回给前端，没登录的话前端静默注册
            $mid = mid;
            $need_login = 0;
            $have_login = 1;
            $login_tip = '';
            if(!$mid){
                $need_login = 1;
                }
            $rdata['need_login'] = $need_login;
            $rdata['have_login'] = $have_login;
            $rdata['login_tip'] = $login_tip;

            $alipayapp = \app\common\System::appinfo(aid,'alipay');
            $rdata['ali_appid'] = $alipayapp['appid'];
            // 剪切板

            //冻结资金抵扣
            $rdata['freezemoneydec']       = $freezemoneydec;
            $rdata['freezemoney_dec_rate'] = $freezemoney_dec_rate;

            //激活币比例
            $activecoin_bili = 0;
            $rdata['activecoin_bili'] = $activecoin_bili;

            //新积分比例
            $newscore_ratio = 0;
            $newscore_pack_ratio = 0;
            $rdata['newscore_ratio'] = $newscore_ratio;
            $rdata['newscore_pack_ratio'] = $newscore_pack_ratio;
            return $this->json($rdata);
        }
    }
}
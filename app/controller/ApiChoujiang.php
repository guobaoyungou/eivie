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

namespace app\controller;
use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;

/**
 * 小程序红包:https://pay.weixin.qq.com/wiki/doc/api/tools/cash_coupon_xcx.php?chapter=18_4&index=1
 * 注意余额充足，每人领取上限
 * 用户领取红包后，资金到达用户微信支付零钱账户，和零钱包的其他资金有一样的使用出口；若用户未领取，资金将会在24小时后退回商户的微信支付账户中。目前小程序红包仅支持用户微信扫码打开小程序，进行红包领取。（场景值1011,1025,1047,1124，小程序场景值详情参见文档）
 */
class ApiChoujiang extends ApiCommon
{
	public function initialize(){
		parent::initialize();
		$this->checklogin();
	}

	public function index(){
		$hid = input('param.id/d');
        $bid = input('param.bid/d',0);
		Db::startTrans();
        $hdWhere = [];
        $hdWhere['aid'] = aid;
        $hdWhere['id'] = $hid;
        $hd = Db::name('choujiang')->where($hdWhere)->lock(true)->find();
        $hd['usescore'] = dd_money_format($hd['usescore'],$this->score_weishu);
		if(!$hd) return $this->json(['status'=>0,'msg'=>'活动不存在']);
		if($hd['status']==0) return $this->json(['status'=>0,'msg'=>'活动未开启']);
		$member = Db::name('member')->where('aid',aid)->where('id',mid)->find();
		if(!$member){
            return $this->json(['status'=>0,'msg'=>'请先登录']);
			//showmsg('未找到会员信息');
			//剩余总机会 每人参与的总次数
			$remaintimes = intval($hd['pertotal']);
			//今天剩余机会 每人每天可参与次数
			$remaindaytimes = intval($hd['perday']);
			if($remaindaytimes > $remaintimes) $remaindaytimes = $remaintimes;
		}else{
            $member['score'] = dd_money_format($member['score'],$this->score_weishu);
			$gettj = explode(',',$hd['gettj']);
			if(!in_array('-1',$gettj) && !in_array($member['levelid'],$gettj)){ //不是所有人
				if(in_array('0',$gettj)){ //关注用户才能领
					if($member['subscribe']!=1){
						$appinfo = \app\common\System::appinfo(aid,'mp');
						return $this->json(['status'=>0,'msg'=>'请先关注'.$appinfo['nickname'].'公众号']);
					}
				}else{
					return $this->json(['status'=>0,'msg'=>'您没有参与权限']);
				}
			}
			//总共已经参加次数
			$totaltimes = Db::name('choujiang_record')->where('mid',mid)->where('hid',$hid)->count();
			//今天已经参加次数
			$todaytimes = Db::name('choujiang_record')->where('mid',mid)->where('hid',$hid)->where('createdate',date('Y-m-d'))->count();

			//分享增加的次数
            $shareWhere = [];
            $shareWhere['aid'] = aid;
            $shareWhere['mid'] = mid;
            $shareWhere['hid'] = $hid;
            $sharelog = Db::name('choujiang_sharelog')->where($shareWhere)->find();
			if($sharelog){
				if($sharelog['updatetime'] != date('Y-m-d')) $sharelog['adddaytimes'] = 0;//今天增加的次数
			}else{
				$sharelog = ['adddaytimes'=>0,'addtimes'=>0,'extratimes'=>0];//extratimes 支付赠送次数
			}
			//剩余总机会,pertotal每人参与的总次数,addtimes增加的次数，累计数值包含之前分享增加的,extratimes购买支付增加的次数
			$remaintimes = intval($hd['pertotal']) - $totaltimes + $sharelog['addtimes'] + $sharelog['extratimes'];
			//今天剩余机会,perday每人每天可参与次数,adddaytimes今天增加的次数,
			$remaindaytimes = intval($hd['perday']) - $todaytimes + $sharelog['adddaytimes'] + $sharelog['extratimes'];
			//通过扫描抽奖码获得机会
			if($remaindaytimes > $remaintimes) $remaindaytimes = $remaintimes;
		}
		//转盘设置
		$jxarr = [];
		$colors = array();
		if($hd['j1mc']){ $jxarr[] = ['jx'=>1,'mc'=>$this->getmc($hd['j1tp'],$hd['j1mc']),'tp'=>$hd['j1tp'],'pic'=>$hd['j1pic']];}
		if($hd['j2mc']){ $jxarr[] = ['jx'=>2,'mc'=>$this->getmc($hd['j2tp'],$hd['j2mc']),'tp'=>$hd['j2tp'],'pic'=>$hd['j2pic']];}
		if($hd['j3mc']){ $jxarr[] = ['jx'=>3,'mc'=>$this->getmc($hd['j3tp'],$hd['j3mc']),'tp'=>$hd['j3tp'],'pic'=>$hd['j3pic']];}
		if($hd['j4mc']){ $jxarr[] = ['jx'=>4,'mc'=>$this->getmc($hd['j4tp'],$hd['j4mc']),'tp'=>$hd['j4tp'],'pic'=>$hd['j4pic']];}
		if($hd['j5mc']){ $jxarr[] = ['jx'=>5,'mc'=>$this->getmc($hd['j5tp'],$hd['j5mc']),'tp'=>$hd['j5tp'],'pic'=>$hd['j5pic']];}
		if($hd['j6mc']){ $jxarr[] = ['jx'=>6,'mc'=>$this->getmc($hd['j6tp'],$hd['j6mc']),'tp'=>$hd['j6tp'],'pic'=>$hd['j6pic']];}
		if($hd['j7mc']){ $jxarr[] = ['jx'=>7,'mc'=>$this->getmc($hd['j7tp'],$hd['j7mc']),'tp'=>$hd['j7tp'],'pic'=>$hd['j7pic']];}
		if($hd['j8mc']){ $jxarr[] = ['jx'=>8,'mc'=>$this->getmc($hd['j8tp'],$hd['j8mc']),'tp'=>$hd['j8tp'],'pic'=>$hd['j8pic']];}
		if($hd['j9mc']){ $jxarr[] = ['jx'=>9,'mc'=>$this->getmc($hd['j9tp'],$hd['j9mc']),'tp'=>$hd['j9tp'],'pic'=>$hd['j9pic']];}
		if($hd['j10mc']){ $jxarr[] = ['jx'=>10,'mc'=>$this->getmc($hd['j10tp'],$hd['j10mc']),'tp'=>$hd['j10tp'],'pic'=>$hd['j10pic']];}
		if($hd['j11mc']){ $jxarr[] = ['jx'=>11,'mc'=>$this->getmc($hd['j11tp'],$hd['j11mc']),'tp'=>$hd['j11tp'],'pic'=>$hd['j11pic']];}
		if($hd['j12mc']){ $jxarr[] = ['jx'=>12,'mc'=>$this->getmc($hd['j12tp'],$hd['j12mc']),'tp'=>$hd['j12tp'],'pic'=>$hd['j12pic']];}
		if($hd['j0mc']){ $jxarr[] = ['jx'=>0,'mc'=>$hd['j0mc'],'tp'=>1,'pic'=>$hd['j0pic']];}

		if(count($jxarr) <= 4){
			$jxarr = array_merge($jxarr,$jxarr);
		}
		if(count($jxarr) <= 4){
			$jxarr = array_merge($jxarr,$jxarr);
		}
		//if(count($restaraunts)%2==1){
		//	if($hd->j0ms){ $restaraunts[] = $hd->j0ms;$colors[] = '#f4d330';}
		//}
        $businessCj = getcustom('business_choujiang');
		if(input('post.op') == 'getjx'){
			if($hd['starttime'] > time()){
				return $this->json(['status'=>0,'msg'=>'活动未开始']);
			}
			if($hd['endtime'] < time()){
				return $this->json(['status'=>0,'msg'=>'活动已结束']);
			}
			if($remaindaytimes <=0){
				return $this->json(['status'=>0,'msg'=>'机会已经用完了']);
			}

			//抽奖消耗类型 1、积分 2、余额
			if($hd['use_type'] ==1){
				if($hd['usescore'] > 0 && $member['score'] < $hd['usescore']){
					return $this->json(['status'=>0,'msg'=>'您的'.t('积分').'不足']);
				}
			}else if($hd['use_type'] ==2){
				if($hd['usemoney'] > 0 && $member['money'] < $hd['usemoney']){
					return $this->json(['status'=>0,'msg'=>'您的'.t('余额').'不足']);
				}
			}else{
			//	return $this->json(['status'=>0,'msg'=>'抽奖消耗类型错误']);
			}

			if($hd['fanwei'] == 1){
				$juli = getdistance(input('post.longitude'),input('post.latitude'),$hd['fanwei_lng'],$hd['fanwei_lat'],2);
				if($juli > $hd['fanwei_range']/1000){
					return $this->json(['status'=>0,'msg'=>'超出活动范围']);
				}
			}
		
			$jxmc = $hd['j0mc'];
			$jxms = $hd['j0ms'];
			$jx = '0';
			$jxtp = 1;

			if($hd['j0yj'] > $hd['j0sl']) $hd['j0yj'] = $hd['j0sl'];
			if($hd['j1yj'] > $hd['j1sl']) $hd['j1yj'] = $hd['j1sl'];
			if($hd['j2yj'] > $hd['j2sl']) $hd['j2yj'] = $hd['j2sl'];
			if($hd['j3yj'] > $hd['j3sl']) $hd['j3yj'] = $hd['j3sl'];
			if($hd['j4yj'] > $hd['j4sl']) $hd['j4yj'] = $hd['j4sl'];
			if($hd['j5yj'] > $hd['j5sl']) $hd['j5yj'] = $hd['j5sl'];
			if($hd['j6yj'] > $hd['j6sl']) $hd['j6yj'] = $hd['j6sl'];
			if($hd['j7yj'] > $hd['j7sl']) $hd['j7yj'] = $hd['j7sl'];
			if($hd['j8yj'] > $hd['j8sl']) $hd['j8yj'] = $hd['j8sl'];
			if($hd['j9yj'] > $hd['j9sl']) $hd['j9yj'] = $hd['j9sl'];
			if($hd['j10yj'] > $hd['j10sl']) $hd['j10yj'] = $hd['j10sl'];
			if($hd['j11yj'] > $hd['j11sl']) $hd['j11yj'] = $hd['j11sl'];
			if($hd['j12yj'] > $hd['j12sl']) $hd['j12yj'] = $hd['j12sl'];
			$count = ($hd['j0sl'] - $hd['j0yj']) + ($hd['j1sl'] - $hd['j1yj']) + ($hd['j2sl'] - $hd['j2yj']) + ($hd['j3sl'] - $hd['j3yj']) + ($hd['j4sl'] - $hd['j4yj']) + ($hd['j5sl'] - $hd['j5yj']) + ($hd['j6sl'] - $hd['j6yj']) + ($hd['j7sl'] - $hd['j7yj']) + ($hd['j8sl'] - $hd['j8yj']) + ($hd['j9sl'] - $hd['j9yj']) + ($hd['j10sl'] - $hd['j10yj']) + ($hd['j11sl'] - $hd['j11yj']) + ($hd['j12sl'] - $hd['j12yj']);

			if($count>0){
				$jparr = [
					($hd['j0sl'] - $hd['j0yj']),
					($hd['j1sl'] - $hd['j1yj']),
					($hd['j2sl'] - $hd['j2yj']),
					($hd['j3sl'] - $hd['j3yj']),
					($hd['j4sl'] - $hd['j4yj']),
					($hd['j5sl'] - $hd['j5yj']),
					($hd['j6sl'] - $hd['j6yj']),
					($hd['j7sl'] - $hd['j7yj']),
					($hd['j8sl'] - $hd['j8yj']),
					($hd['j9sl'] - $hd['j9yj']),
					($hd['j10sl'] - $hd['j10yj']),
					($hd['j11sl'] - $hd['j11yj']),
					($hd['j12sl'] - $hd['j12yj']),
				];
				$rands = rand(1,$count);
				$qian = 0;
				foreach ($jparr as $k=>$v) {
					if($rands > $qian && $rands <= $qian + $v){
						$jx = $k;
						$jxmc = $hd["j{$jx}mc"];
						$jxtp = $hd["j{$jx}tp"];
						break;
					}
					$qian += $v;
				}
			}
			$update = [];
			$update["j{$jx}yj"] = Db::raw("j{$jx}yj+1");
			$update['cjnum'] = Db::raw('cjnum+1');
			if($jx!=0){
				$update['zjnum'] = Db::raw('zjnum+1');
			}
			Db::name('choujiang')->where('id',$hid)->update($update);
			Db::commit(); //解锁

			//抽奖记录
			$data = [];
			$data['aid'] = aid;
			$data['mid'] = mid;
			$data['hid'] = $hid;
			$data['name'] = $hd['name'];
			$data['headimg'] = $member['headimg'];
			$data['nickname'] = $member['nickname'];
			$data['jx'] = $jx;
			$data['jxtp'] = $jxtp;
			$data['jxmc'] = $jxmc;
			$data['createtime'] = time();
			$data['createdate'] = date('Y-m-d');
			$data['code'] = random(16);
			$data['hexiaoqr'] = createqrcode(m_url('admin/hexiao/hexiao?type=choujiang&co='.$data['code']));
			$rid = Db::name('choujiang_record')->insertGetId($data);


			//抽奖消耗类型 1、积分 2、余额
			if($hd['use_type'] ==1){
				if($hd['usescore'] > 0){
					\app\common\Member::addscore(aid,mid,-$hd['usescore'],t('积分').'抽奖');
				}
			}else if($hd['use_type'] ==2){
				if($hd['usemoney'] > 0){
					\app\common\Member::addmoney(aid,mid,-$hd['usemoney'],t('余额').'抽奖');
				}
			}else{
			//	return $this->json(['status'=>0,'msg'=>'抽奖消耗类型错误']);
			}

            $oldjxmc = $jxmc;
            $jxmc = $this->getmc($jxtp,$jxmc);
			//微信红包
			$spdata = false;
			if($jxtp == 2){
				srand(microtime(true) * 1000);
				$moneyArr = explode('-',str_replace('~','-',$oldjxmc));
				if(!$moneyArr[1]) $moneyArr[1] = $moneyArr[0];
				$ss = rand($moneyArr[0]*100,$moneyArr[1]*100).PHP_EOL;
				$money = number_format($ss/100, 2, '.', '');
				$rs = \app\common\Wxpay::sendredpackage(aid,mid,platform,$money,mb_substr($hd['name'],0,10),'微信红包','恭喜发财','微信红包',$hd['scene_id']);
                if($rs['status']==0){ //发放失败
                    Log::error([
                        'file'=>__FILE__,
                        '$rs'=>$rs
                    ]);
					Db::name('choujiang_record')->where('id',$rid)->update(['jxmc'=>$jxmc,'remark'=>$rs['msg']]);
				}else{
					Db::name('choujiang_record')->where('id',$rid)->update(['jxmc'=>$jxmc,'status'=>1,'remark'=>'发放成功']);
					if(platform == 'wx'){//小程序红包
						$appinfo = \app\common\System::appinfo(aid,platform);
						$appid = $appinfo['appid'];
						$mchkey = $appinfo['wxpay_mchkey'];
						$spdata = [];
						$spdata['appId'] = $appid;
						$spdata['timeStamp'] = strval(time());
						$spdata['nonceStr'] = random(16);
						$spdata['package'] = urlencode($rs['resp']['package']);
						ksort($spdata, SORT_STRING);
						$string1 = '';
						foreach ($spdata as $key => $v) {
							if (empty($v)) {
								continue;
							}
							$string1 .= "{$key}={$v}&";
						}
						$string1 .= "key={$mchkey}";
						$spdata['signType'] = 'MD5';
						$spdata['paySign'] = md5($string1);
					}
				}
			}
			//优惠券
			if($jxtp==3){
				$rs = \app\common\Coupon::send(aid,mid,$oldjxmc);
				if($rs['status']==0){ //发放失败
					Db::name('choujiang_record')->where('id',$rid)->update(['jxmc'=>$jxmc,'remark'=>$rs['msg']]);
				}else{
					Db::name('choujiang_record')->where('id',$rid)->update(['jxmc'=>$jxmc,'status'=>1,'remark'=>'发放成功']);
				}
			}
			//积分
			if($jxtp==4){
				$rs = \app\common\Member::addscore(aid,mid,$oldjxmc,$hd['name']);
				if($rs['status']==0){ //发放失败
					Db::name('choujiang_record')->where('id',$rid)->update(['jxmc'=>$jxmc,'remark'=>$rs['msg']]);
				}else{
					Db::name('choujiang_record')->where('id',$rid)->update(['jxmc'=>$jxmc,'status'=>1,'remark'=>'发放成功']);
				}
			}
			//余额
			if($jxtp==5){
				$rs = \app\common\Member::addmoney(aid,mid,$oldjxmc,$hd['name']);
				if($rs['status']==0){ //发放失败
					Db::name('choujiang_record')->where('id',$rid)->update(['jxmc'=>$jxmc,'remark'=>$rs['msg']]);
				}else{
					Db::name('choujiang_record')->where('id',$rid)->update(['jxmc'=>$jxmc,'status'=>1,'remark'=>'发放成功']);
				}
			}
            //微信零钱
            if($jxtp==6){
                $moneyArr = explode('-',str_replace('~','-',$oldjxmc));
                if($moneyArr[1])
                    $ss = rand($moneyArr[0]*100,$moneyArr[1]*100).PHP_EOL;
                else
                    $ss = $moneyArr[0]*100;
                $money = number_format($ss/100, 2, '.', '');
                $ordernum = \app\common\Common::generateOrderNo(aid);
                $rs = \app\common\Wxpay::transfers(aid,mid,$money,$ordernum,platform,mb_substr($hd['name'],0,10));
                Log::write([
                    'file'=>__FILE__,
                    'transfers'=>$rs
                ]);
                if($rs['status']==0){ //发放失败
                    Db::name('choujiang_record')->where('id',$rid)->update(['jxmc'=>$jxmc,'remark'=>$rs['msg']]);
                }else{
                    Db::name('choujiang_record')->where('id',$rid)->update(['jxmc'=>$jxmc,'status'=>1,'remark'=>'发放成功']);
                }
            }
			$jxindex = 0;
			foreach($jxarr as $k=>$v){
				if($v['jx']==$jx){
					$jxindex = $k;break;
				}
			}
			$score = Db::name('member')->where('id',mid)->value('score');
			$money = Db::name('member')->where('id',mid)->value('money');

			return $this->json(['status'=>1,'msg'=>'','id'=>$rid,'jx'=>$jx,'jxcount'=>count($jxarr),'jxindex'=>$jxindex,'jxmc'=>$jxmc,'score'=>$score,'money'=>$money,'jxtp'=>$jxtp,'spdata'=>$spdata,'info'=>$hd]);
		}

		//最新中奖10个
		$zjlist = Db::name('choujiang_record')->where('hid',$hid)->where('jx','<>',0)->order('id desc')->limit(10)->select()->toArray();
		foreach($zjlist as $k=>$v){
			$zjlist[$k]['tel'] = substr($v['tel'], 0,3).'****'.substr($v['tel'],-4);
		}
		if($remaintimes < 0) $remaintimes = 0;
		if($remaindaytimes < 0) $remaindaytimes = 0;

		$isinfo = false;
		$record = [];
		$hd['isend'] = false;
		if($hd['endtime'] < time()) $hd['isend']= true;
		$hd['isbegin'] = false;
		if($hd['starttime'] > time()) $hd['isbegin'] = true;

        $hd['choujiang_after_tourl'] = '';
        $rdata = [];
		$rdata['info'] 		= $hd;
		$rdata['remaintimes'] 	 = $remaintimes;
		$rdata['remaindaytimes'] = $remaindaytimes;
		$rdata['jxarr'] 	= $jxarr;
		$rdata['zjlist'] 	= $zjlist;
		$rdata['isinfo'] 	= $isinfo;
		$rdata['record'] 	= $record;
		$rdata['member'] = ['realname'=>$member['realname'],'tel'=>$member['tel'],'score'=>$member['score'],'money'=>$member['money']];
		return $this->json($rdata);
	}

	//获取奖项名称
	public function getmc($jxtp,$jxmc){
		if($jxtp==1){
			return $jxmc;
		}elseif($jxtp==2){
			return $jxmc.'元红包';
		}elseif($jxtp==3){
			$coupon = Db::name('coupon')->where('aid',aid)->where('id',$jxmc)->find();
			return $coupon['name'] ? $coupon['name'] : '';
		}elseif($jxtp==4){
            $jxmc = dd_money_format($jxmc,$this->score_weishu);
            return $jxmc.t('积分');
        }elseif($jxtp==5){
            return $jxmc.'元'.t('余额');
        }elseif($jxtp==6) {
            return $jxmc . '元微信零钱';
        }else{
            return $jxmc ? $jxmc : '';
        }
	}
	//我的奖品
	public function myprize(){
		$hid = input('param.hid/d');
		$hd = Db::name('choujiang')->where('aid',aid)->where('id',$hid)->find();
		$datalist = Db::name('choujiang_record')->where('hid',$hid)->where('mid',mid)->where('jx','>',0)->order('id desc')->select()->toArray();
		foreach($datalist as $k=>$v){
			$datalist[$k]['createtime'] = date('Y-m-d H:i',$v['createtime']);
		}
        //红包补发
        $hbdata = [];
        if($datalist[0]['jxtp'] == 2 && platform == 'wx'){
            $log = Db::name('sendredpack_log')->where('aid',aid)->where('openid','=',$this->member['wxopenid'])->where('status',1)->order('id','desc')->find();
            if($log) $info = \app\common\Wxpay::gethbinfo($log);
            /**
             * SENDING:发放中
            SENT:已发放待领取
            FAILED：发放失败
            RECEIVED:已领取
            RFUND_ING:退款中
            REFUND:已退款
             */
            if($info['result_code'] == 'SUCCESS' && $info['return_code'] == 'SUCCESS' && ($info['status'] == 'SENDING' || $info['status'] == 'SENT')){
                $rs = \app\common\Wxpay::sendredpackage(aid,mid,platform,$log['money']*100,mb_substr($hd['name'],0,10),'微信红包','恭喜发财','微信红包',$hd['scene_id'],$log);
                if($rs['status']==0){ //发放失败
                    Log::error([
                        'file'=>__FILE__,
                        '$rs'=>$rs
                    ]);
                }else{
                    if(platform == 'wx'){//小程序红包
                        $appinfo = \app\common\System::appinfo(aid,platform);
                        $appid = $appinfo['appid'];
                        $mchkey = $appinfo['wxpay_mchkey'];
                        $hbdata['appId'] = $appid;
                        $hbdata['timeStamp'] = strval(time());
                        $hbdata['nonceStr'] = random(16);
                        $hbdata['package'] = urlencode($rs['resp']['package']);
                        ksort($hbdata, SORT_STRING);
                        $string1 = '';
                        foreach ($hbdata as $key => $v) {
                            if (empty($v)) {
                                continue;
                            }
                            $string1 .= "{$key}={$v}&";
                        }
                        $string1 .= "key={$mchkey}";
                        $hbdata['signType'] = 'MD5';
                        $hbdata['paySign'] = md5($string1);
                    }
                }
            }
        }
		$rdata = [];
		$rdata['info'] = $hd;
		$rdata['datalist'] = $datalist;
        $rdata['hbdata'] = $hbdata;
		return $this->json($rdata);
	}
	//完善中奖信息
	public function subinfo(){
		//dump(input('post.formcontent/a'));
		$id = input('param.rid/d');
		$data = [];
		$data['linkman'] = input('post.linkman');
		$data['tel'] = input('post.tel');
		$data['formdata'] = jsonEncode(input('post.formcontent/a'));
		Db::name('choujiang_record')->where('id',$id)->where('mid',mid)->update($data);
		$record = Db::name('choujiang_record')->where('id',$id)->where('mid',mid)->find();
		if(!$record) return $this->json(['status'=>0,'msg'=>'未找到该中奖记录']);
		return $this->json(['status'=>1,'msg'=>'提交成功','record'=>$record]);
	}
	//分享
	public function share(){
		$hid = input('param.hid/d');
		$hd = Db::name('choujiang')->where('aid',aid)->where('id',$hid)->find();
		if(!$hd) return $this->json(['status'=>0,'msg'=>'活动不存在']);
		if($hd['status']==0) return $this->json(['status'=>0,'msg'=>'活动未开启']);
        $shareWhere = [];
        $shareWhere['aid'] = aid;
        $shareWhere['hid'] = $hid;
        $shareWhere['mid'] = mid;
        $sharelog = Db::name('choujiang_sharelog')->where($shareWhere)->find();
		$status = 0;
		if($sharelog){
			if($hd['shareaddnum']>0){
                //分享增加抽奖次数>0
				$update = [];
				if($sharelog['updatetime'] != date('Y-m-d')){
					$update['updatetime'] = date('Y-m-d');
					$update['sharedaytimes'] = 1;//当天分享次数
					$update['adddaytimes'] = 0;
                    //sharetimes最多分享总次数,0为不限制，分享次数多于此设置不再增加抽奖次数
					if($hd['sharetimes']==0 || $hd['sharetimes'] > $sharelog['sharecounttimes']){
						$update['addtimes'] = $sharelog['addtimes'] + $hd['shareaddnum'];//增加的次数，累计数值包含之前分享增加的
						$update['adddaytimes'] = $hd['shareaddnum'];//今天增加的次数
						$status = 1;
					}
					$update['sharecounttimes'] = $sharelog['sharecounttimes'] + 1;//分享总次数
				}else{
					if(($hd['sharedaytimes']==0 || $hd['sharedaytimes'] > $sharelog['sharedaytimes']) && ($hd['sharetimes']==0 || $hd['sharetimes'] > $sharelog['sharecounttimes'])){
						$update['addtimes'] = $sharelog['addtimes'] + $hd['shareaddnum'];//增加的次数，累计数值包含之前分享增加的
						$update['adddaytimes'] = $sharelog['adddaytimes'] + $hd['shareaddnum'];//今天增加的次数
						$status = 1;
					}
					$update['sharedaytimes'] = $sharelog['sharedaytimes'] + 1;//当天分享次数
					$update['sharecounttimes'] = $sharelog['sharecounttimes'] + 1;//分享总次数
				}
				Db::name('choujiang_sharelog')->where('id',$sharelog['id'])->update($update);
			}
		}else{
			$data = [];
			$data['aid'] = aid;
			$data['hid'] = $hid;
			$data['mid'] = mid;
			$data['updatetime'] = date('Y-m-d');
			$data['sharedaytimes'] = 1;
			$data['sharecounttimes'] = 1;
			if($hd['shareaddnum'] > 0 ){
				$data['addtimes'] = $hd['shareaddnum'];//增加的次数，累计数值包含之前分享增加的
				$data['adddaytimes'] = $hd['shareaddnum'];//今天增加的次数
				$status = 1;
			}
            Db::name('choujiang_sharelog')->insert($data);
		}
		if($hd['shareaddnum'] <=0) $status = 2;
		return $this->json(['status'=>$status,'msg'=>'']);
	}


	public function savememberinfo(){
		//dump(input('post.formcontent/a'));
		$hid = input('post.hid/d');
		$data = [];
		$data['formdata'] = jsonEncode(input('post.formcontent/a'));
		$data['hid'] = $hid;
		$data['aid'] = aid;
		$data['mid'] = mid;
		$data['createtime'] = time();
		$record = Db::name('choujiang_memberinfo')->where('hid',$hid)->where('mid',mid)->find();
		if(!$record){
			Db::name('choujiang_memberinfo')->insert($data);
		}
		return $this->json(['status'=>1,'msg'=>'提交成功']);
	}

	//抽奖码增加抽奖次数
	public function qrcode_addtimes(){
		$this->checklogin();
		}

}
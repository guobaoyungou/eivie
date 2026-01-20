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

// +----------------------------------------------------------------------
// | 买单记录
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class BusinessMaidan extends Common
{
	public function initialize(){
		parent::initialize();
	}
	//明细
    public function index(){
		$mdArr = Db::name('mendian')->where('aid',aid)->where('bid',bid)->column('name','id');
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
			if(input('param.field') && input('param.order')){
				$order = 'maidan_order.'.input('param.field').' '.input('param.order');
			}else{
				$order = 'maidan_order.id desc';
			}
			$where = [];
			$where[] = ['maidan_order.aid','=',aid];
            if(bid==0){
                if(input('param.bid')){
                    $where[] = ['maidan_order.bid','=',input('param.bid')];
                }elseif(input('param.showtype')==2){
                    $where[] = ['maidan_order.bid','<>',0];
                }elseif(input('param.showtype')=='all'){
                    $where[] = ['maidan_order.bid','>=',0];
                }else{
                    $where[] = ['maidan_order.bid','=',0];
                }
            }else{
                $where[] = ['maidan_order.bid','=',bid];
            }

            $where[] = ['maidan_order.status','=',1];

			if(input('param.mdid')) $where[] = ['maidan_order.mdid','=',input('param.mdid')];
			if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
			if(input('param.mid')) $where[] = ['maidan_order.mid','=',trim(input('param.mid'))];
			if(input('param.ordernum')) $where[] = ['maidan_order.ordernum','=',trim(input('param.ordernum'))];
            if(input('param.orderid')) $where[] = ['maidan_order.id','=',input('param.orderid')];
			if(input('?param.status') && input('param.status')!=='') $where[] = ['maidan_order.status','=',input('param.status')];
			if(input('param.ctime') ){
				$ctime = explode(' ~ ',input('param.ctime'));
				$where[] = ['maidan_order.paytime','>=',strtotime($ctime[0])];
				$where[] = ['maidan_order.paytime','<',strtotime($ctime[1])];
			}
			$count = 0 + Db::name('maidan_order')->alias('maidan_order')->field('member.nickname,member.headimg,maidan_order.*')->join('member member','member.id=maidan_order.mid','left')->where($where)->count();
			$data = Db::name('maidan_order')->alias('maidan_order')->field('member.nickname,member.headimg,maidan_order.*')->join('member member','member.id=maidan_order.mid','left')->where($where)->page($page,$limit)->order($order)->select()->toArray();
            if(!$data) $data = [];
			foreach($data as $k=>$v){
				 $data[$k]['mdname'] = '';
				if($v['mdid']) $data[$k]['mdname'] = $mdArr[$v['mdid']];
               $data[$k]['can_refund_money'] =dd_money_format($v['paymoney'] - $v['refund_money'],2);
                $data[$k]['refund_reason'] = Db::name('maidan_refund_order')->where('orderid',$v['id'])->value('refund_reason');
			}
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
        $logdel_auth = true;
		View::assign('logdel_auth',$logdel_auth);
		View::assign('mdArr',$mdArr);

        //是否可以操作退款
        $is_can_refund = 1;
        View::assign('is_can_refund',$is_can_refund);
		return View::fetch();
    }
	//明细导出
	public function excel(){
		if(input('param.field') && input('param.order')){
			$order = 'maidan_order.'.input('param.field').' '.input('param.order');
		}else{
			$order = 'maidan_order.id desc';
		}
		$where = [];
		$where[] = ['maidan_order.aid','=',aid];
		$where[] = ['maidan_order.bid','=',bid];
		$where[] = ['maidan_order.status','=',1];
		if(input('param.mdid')) $where[] = ['maidan_order.mdid','=',input('param.mdid')];
		if(input('param.nickname')) $where[] = ['member.nickname','like','%'.trim(input('param.nickname')).'%'];
		if(input('param.mid')) $where[] = ['maidan_order.mid','=',trim(input('param.mid'))];
		if(input('param.ordernum')) $where[] = ['maidan_order.ordernum','=',trim(input('param.ordernum'))];
		if(input('?param.status') && input('param.status')!=='') $where[] = ['maidan_order.status','=',input('param.status')];
		if(input('param.ctime') ){
			$ctime = explode(' ~ ',input('param.ctime'));
			$where[] = ['maidan_order.paytime','>=',strtotime($ctime[0])];
			$where[] = ['maidan_order.paytime','<',strtotime($ctime[1]) + 86400];
		}
		$list = Db::name('maidan_order')->alias('maidan_order')->field('member.nickname,member.headimg,maidan_order.*')->join('member member','member.id=maidan_order.mid','left')->where($where)->order($order)->select()->toArray();
		$title = array();
		$title[] = '订单号';
		$title[] = t('会员').'信息';
		$title[] = '付款金额';
		$title[] = '实付金额';
		$title[] = t('会员').'折扣';
		$title[] = t('积分').'抵扣';
		$title[] = t('优惠券').'抵扣';
		$title[] = '付款时间';
		//$title[] = '备注';
		$data = array();
		foreach($list as $v){
			$tdata = array();
			$tdata[] = "'".$v['ordernum'];
			$tdata[] = $v['nickname'].'('.t('会员').'ID:'.$v['mid'].')';
			$tdata[] = $v['money'];
			$tdata[] = $v['paymoney'];
			$tdata[] = $v['disprice'];
			$tdata[] = $v['scoredk'];
			$tdata[] = $v['couponmoney'];
			$tdata[] = date('Y-m-d H:i:s',$v['paytime']);
			$data[] = $tdata;
		}
		$this->export_excel($title,$data);
	}
	public function logdel(){
		$ids = input('post.ids/a');
		Db::name('maidan_order')->where('aid',aid)->where('bid',bid)->where('id','in',$ids)->delete();
		\app\common\System::plog('删除买单记录'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}
	public function getopenid($platform,$auth_code){
		
		$appinfo = \app\common\System::appinfo(aid,$platform);
		$appid = $appinfo['appid'];
		$pars = [];
		if($appinfo['wxpay_type']==0){
			$pars['appid'] = $appid;
			$pars['mch_id'] = $appinfo['wxpay_mchid'];
			$mchkey = $appinfo['wxpay_mchkey'];
		}else{
			$dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
			$dbwxpayset = json_decode($dbwxpayset,true);
			if(!$dbwxpayset){
				return ['status'=>0,'msg'=>'未配置服务商微信支付信息'];
			}
			$pars['appid'] = $dbwxpayset['appid'];
			$pars['sub_appid'] = $appid;
			$pars['mch_id'] = $dbwxpayset['mchid'];
			$pars['sub_mch_id'] = $appinfo['wxpay_sub_mchid'];
			$mchkey = $dbwxpayset['mchkey'];
		}
		$pars['auth_code'] = $auth_code;
		$pars['nonce_str'] = random(8);
		ksort($pars, SORT_STRING);
		$string1 = '';
		foreach ($pars as $key => $v){
			if (empty($v)) {
				continue;
			} 
			$string1 .= "{$key}={$v}&";
		}
		$string1 .= "key=".$mchkey;
		$pars['sign'] = strtoupper(md5($string1));
		//dump($pars);
		$dat = array2xml($pars);
		//dump($dat);
		$response = request_post('https://api.mch.weixin.qq.com/tools/authcodetoopenid', $dat);
		//dump($response);
		$response = @simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
		if (strval($response->return_code) == 'FAIL') {
			return ['status'=>0,'msg'=>strval($response->return_msg)];
		} 
		if (strval($response->result_code) == 'FAIL') {
			return ['status'=>0,'msg'=>strval($response->err_code_des)];
		}
		if($appinfo['wxpay_type']==0){
			$openid = strval($response->openid);
		}else{
			$openid = strval($response->sub_openid);
		}
		if(!$openid) return ['status'=>0,'msg'=>'获取'.t('会员').'信息失败'];
		return ['status'=>1,'openid'=>$openid];
	}
	//通过支付授权码获取会员信息
	public function getmember(){
		$auth_code = input('post.auth_code');
		$set = Db::name('admin_set')->where('aid',aid)->find();
		$appinfo = \app\common\System::appinfo(aid,'mp');
		$appid = $appinfo['appid'];
		if($appid){
			$rs = $this->getopenid('mp',$auth_code);
			if($rs['status']==0) return json($rs);
			$openid = $rs['openid'];
			$member = Db::name('member')->where('aid',aid)->where('mpopenid',$openid)->find();
		}
		if(!$member){
			$appinfo = \app\common\System::appinfo(aid,'wx');
			$appid = $appinfo['appid'];
			$rs = $this->getopenid('wx',$auth_code);
			//dump($appid);
			//dump($rs);
			if($rs['status']==1){
				$openid = $rs['openid'];
				$member = Db::name('member')->where('aid',aid)->where('wxopenid',$openid)->find();
			}
		}
		if(!$member){
			return json(['status'=>2,'msg'=>'未查找到'.t('会员').'信息']);
		}
		$userlevel = Db::name('member_level')->where('aid',aid)->where('id',$member['levelid'])->find();
		$member['levelname'] = $userlevel['name'];
		if($userlevel && $userlevel['discount']>0 && $userlevel['discount']<10){
			//$disprice = $paymoney * (1 - $userlevel['discount'] * 0.1);
			$discount = $userlevel['discount'];
		}else{
			$discount = 10;
		}
		return json(['member'=>$member,'discount'=>$discount,'openid'=>$openid]);
	}
	//扫码收款
	public function shoukuan(){
		$auth_code = input('post.auth_code');
		$skmoney = input('post.skmoney');
		$mid = input('post.mid');
		$openid = input('post.openid');
		$realmoney = input('post.realmoney');
		$usemoney = input('post.usemoney');

		$decmoney = 0; //余额抵扣
		if($usemoney && $mid){
			$member = Db::name('member')->where('aid',aid)->where('id',$mid)->find();
			if($member['money'] >= $realmoney){
				$decmoney = $realmoney;
			}else{
				$decmoney = $member['money'];
			}
		}
		$wxpaymoney = $realmoney - $decmoney;
		$ordernum = date('ymdHis').aid.rand(1000,9999);
		$time = time();
		
		$fenzhangmoney = 0;
		$set = Db::name('admin_set')->where('aid',aid)->find();
        if($wxpaymoney > 0){
			if(in_array('mp',$this->platform)){
				$appinfo = \app\common\System::appinfo(aid,'mp');
			}else{
				$appinfo = \app\common\System::appinfo(aid,'wx');
			}
			$appid = $appinfo['appid'];
			$pars = [];
			if($appinfo['wxpay_type']==0){
				$pars['appid'] = $appid;
				$pars['mch_id'] = $appinfo['wxpay_mchid'];
				$mchkey = $appinfo['wxpay_mchkey'];
			}else{
				$dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
				$dbwxpayset = json_decode($dbwxpayset,true);
				if(!$dbwxpayset){
					return json(['status'=>0,'msg'=>'未配置服务商微信支付信息']);
				}
				$pars['appid'] = $dbwxpayset['appid'];
				//$pars['sub_appid'] = $appid;
				$pars['mch_id'] = $dbwxpayset['mchid'];
				$pars['sub_mch_id'] = $appinfo['wxpay_sub_mchid'];
				$mchkey = $dbwxpayset['mchkey'];

				$chouchengmoney = 0;
				$admindata = Db::name('admin')->where('id',aid)->find();
				if($admindata['chouchengset']==0){ //默认抽成
					if($dbwxpayset && $dbwxpayset['chouchengset']!=0){
						if($dbwxpayset['chouchengset'] == 1){
							$chouchengmoney = floatval($dbwxpayset['chouchengrate']) * 0.01 * $wxpaymoney;
							if($dbwxpayset['chouchengmin'] && $chouchengmoney < floatval($dbwxpayset['chouchengmin'])){
								$chouchengmoney = floatval($dbwxpayset['chouchengmin']);
							}
						}else{
							$chouchengmoney = floatval($dbwxpayset['chouchengmoney']);
						}
					}
				}elseif($admindata['chouchengset']==1){ //按比例抽成
					$chouchengmoney = floatval($admindata['chouchengrate']) * 0.01 * $wxpaymoney;
					if($chouchengmoney < floatval($admindata['chouchengmin'])){
						$chouchengmoney = floatval($admindata['chouchengmin']);
					}
				}elseif($admindata['chouchengset']==2){ //按固定金额抽成
					$chouchengmoney = floatval($admindata['chouchengmoney']);
				}
				if($chouchengmoney > 0 && $wxpaymoney*0.3 >= $chouchengmoney){ //需要分账
					$pars['profit_sharing'] = 'Y';
					$fenzhangmoney = $chouchengmoney;
				}

			}
			$pars['body'] = $set['name'].'-付款码付款';
			$pars['out_trade_no'] = $ordernum;
			$pars['total_fee'] = $wxpaymoney*100;
			$pars['spbill_create_ip'] = request()->ip();
			$pars['auth_code'] = $auth_code;
			$pars['nonce_str'] = random(8);
			ksort($pars, SORT_STRING);
			$string1 = '';
			foreach ($pars as $key => $v){
				if (empty($v)) {
					continue;
				} 
				$string1 .= "{$key}={$v}&";
			}
			$string1 .= "key=".$mchkey;
			$pars['sign'] = strtoupper(md5($string1));
			//dump($pars);
			$dat = array2xml($pars);
			//dump($dat);
			$response = request_post('https://api.mch.weixin.qq.com/pay/micropay', $dat);
			//dump($response);
			$response = @simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
			if (strval($response->return_code) == 'FAIL' && $response->err_code != 'USERPAYING') {
				return json(['status'=>0,'msg'=>strval($response->return_msg)]);
			} 
			if (strval($response->result_code) == 'FAIL') {
				if ($response->err_code == 'USERPAYING') {
					$result = false;
					for($i=0;$i<10;$i++){
						$pars2          = array();
						if($appinfo['wxpay_type']==0){
							$pars2['appid'] = $appid;
							$pars2['mch_id'] = $appinfo['wxpay_mchid'];
							$mchkey = $appinfo['wxpay_mchkey'];
						}else{
							$dbwxpayset = Db::name('sysset')->where('name','wxpayset')->value('value');
							$dbwxpayset = json_decode($dbwxpayset,true);
							if(!$dbwxpayset){
								return json(['status'=>0,'msg'=>'未配置服务商微信支付信息']);
							}
							$pars2['appid'] = $dbwxpayset['appid'];
							$pars2['mch_id'] = $dbwxpayset['mchid'];
							$pars2['sub_mch_id'] = $appinfo['wxpay_sub_mchid'];
							$mchkey = $dbwxpayset['mchkey'];
						}
						$pars2['out_trade_no'] = $ordernum;
						$pars2['nonce_str'] = random(8);
						ksort($pars2, SORT_STRING);
						$string2 = '';
						foreach ($pars2 as $key => $v){
							if (empty($v)) {
								continue;
							} 
							$string2 .= "{$key}={$v}&";
						}
						$string2 .= "key=".$mchkey;
						$pars2['sign'] = strtoupper(md5($string2));
						$dat2 = array2xml($pars2);
						$response2 = request_post('https://api.mch.weixin.qq.com/pay/orderquery', $dat2);
						$response2 = @simplexml_load_string($response2, 'SimpleXMLElement', LIBXML_NOCDATA);
						//var_dump($response2);
						if ($response2->return_code=='SUCCESS' && $response2->return_code == 'SUCCESS' && $response2->trade_state=="SUCCESS") {
							$result = true;
							$transaction_id = $response2->transaction_id;
							break;
						}elseif($response2->trade_state != 'USERPAYING'){
							return json(['status' => 0, 'msg' => strval($response2->trade_state_desc)]);
						}
						sleep(3);
					}
					if($result==false){
						return json(['status' => 0, 'msg' => '支付超时']);
					}
				} else {
					return json(['status' => 0, 'msg' => strval($response->err_code_des)]);
				}
			}else{
				$transaction_id = $response->transaction_id;
			}
			//dump($response);
		}
		if($decmoney > 0){
			\app\common\Member::addmoney(aid,$mid,-$decmoney,'付款码付款');
		}
		$order = [];
		$order['aid'] = aid;
		$order['bid'] = bid;
		$order['mid'] = $mid;
		$order['ordernum'] = $ordernum;
		$order['title'] = '付款码付款';
		$order['money'] = $skmoney;
		$order['paymoney'] = $realmoney;
		$order['disprice'] = $skmoney - $realmoney;
		$order['decmoney'] = $decmoney;
		$order['createtime'] = $time;
		$order['status'] = 1;
		if($decmoney > 0 && $wxpaymoney > 0){
			$order['paytype'] = '付款码,'.t('余额').'支付￥'.$decmoney.',微信支付￥'.$wxpaymoney;
		}elseif($decmoney > 0){
			$order['paytype'] = '付款码,'.t('余额').'支付￥'.$decmoney;
		}else{
			$order['paytype'] = '付款码,微信支付￥'.$wxpaymoney;
		}
		$order['paynum'] = $transaction_id;
		$order['paytime'] = time();
		$order['uid'] = $this->uid;
		$order['mdid'] = $this->mdid;
        //多商户商品不参与分红时
        $id = Db::name('maidan_order')->insertGetId($order);
		
		$order['id'] = $id;
		\app\common\Order::collect($order,'maidan');
        $iszs = true;
        //消费送积分
        if($set['scorein_money']>0 && $set['scorein_score']>0 && $iszs){
            $paymoney = $wxpaymoney;
            if($set['score_from_moneypay'] == 1){//余额支付送积分0不送，1送
                $paymoney += $decmoney;
            }
            $givescore = floor($paymoney / $set['scorein_money']) * $set['scorein_score'];
            $res = \app\common\Member::addscore(aid,$mid,$givescore,'消费送'.t('积分'));
            if($res && $res['status'] == 1){
				//记录消费赠送积分记录
				\app\common\Member::scoreinlog(aid,0,$mid,'maidan',$id,$ordernum,$givescore,$paymoney);
			}
        }
		if($wxpaymoney > 0){
			//记录
			$data = array();
			$data['aid'] = aid;
			$data['mid'] = $mid;
			$data['openid'] = $openid;
			$data['tablename'] = 'maidan_order';
			$data['givescore'] = 0;
			$data['ordernum'] = $ordernum;
			$data['mch_id'] = $appinfo['wxpay_mchid'];
			$data['transaction_id'] = $transaction_id;
			$data['total_fee'] = $wxpaymoney;
			$data['createtime'] = time();
			$data['fenzhangmoney'] = $fenzhangmoney;
			Db::name('wxpay_log')->insert($data);
		}
        \app\common\Member::uplv(aid,$mid);
		\app\common\System::plog('买单收款'.$id);
		return json(['status'=>1,'msg'=>'收款成功','url'=>(string)url('index')]);
	}

    public function add()
    {
        if(request()->isPost()){
            $post = input('post.');
            $money = floatval($post['money']);
            if($money <=0){
                return json(['status'=>0,'msg'=>'支付金额必须大于0']);
            }
            $paymoney = $money;
            //会员折扣
            if(empty($post['tel'])) return json(['status'=>0,'msg'=>'请输入手机号']);
            $member = Db::name('member')->where('aid',aid)->where('tel',$post['tel'])->field('id,nickname,money,levelid')->find();
            if(empty($member))
                return json(['status'=>0,'msg'=>'未找到用户']);
            $disprice = 0;
            $userlevel = Db::name('member_level')->where('aid',aid)->where('id',$member['levelid'])->find();
            if($userlevel && $userlevel['discount']>0 && $userlevel['discount']<10){
                $disprice = round($paymoney * (1 - $userlevel['discount'] * 0.1), 2);
            }
            $paymoney = $paymoney - $disprice;
            //优惠券
            if($post['couponrid'] > 0){
                $couponrid = $post['couponrid'];
                $couponrecord = Db::name('coupon_record')->where('aid',aid)->where('mid',$member['id'])->where('id',$couponrid)->find();
                if(!$couponrecord){
                    return json(['status'=>0,'msg'=>'该'.t('优惠券').'不存在']);
                }elseif($couponrecord['status']!=0){
                    return json(['status'=>0,'msg'=>'该'.t('优惠券').'已使用过了']);
                }elseif($couponrecord['starttime'] > time()){
                    return json(['status'=>0,'msg'=>'该'.t('优惠券').'尚未开始使用']);
                }elseif($couponrecord['endtime'] < time()){
                    return json(['status'=>0,'msg'=>'该'.t('优惠券').'已过期']);
                }elseif($couponrecord['minprice'] > $money){
                    return json(['status'=>0,'msg'=>'该'.t('优惠券').'不符合条件']);
                }elseif($couponrecord['type']!=1){
                    return json(['status'=>0,'msg'=>'该'.t('优惠券').'不符合条件']);
                }
                $couponmoney = $couponrecord['money'];
                if($couponmoney > $money) $couponmoney = $money;
            }else{
                $couponmoney = 0;
            }
            $paymoney = $paymoney - $couponmoney;
            if($paymoney < 0) $paymoney = 0;
            //积分抵扣
            if($post['usescore']==1){
                $adminset = Db::name('admin_set')->where('aid',aid)->find();
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
                    $decscore = dd_score_format($scoredk / $score2money,$this->score_weishu);
                }
            }else{
                $scoredk = 0;
                $decscore = 0;
            }

            if($paymoney > $member['money'])  return json(['status'=>0,'msg'=>t('余额').'不足']);

            $bname = Db::name('business')->where('id',bid)->value('name');
            $title = '后台买单收款：'.$bname;
            $ordernum = date('ymdHis').aid.rand(1000,9999);
            $rs = \app\common\Member::addmoney(aid,$member['id'],$paymoney*-1,$title.$ordernum);
            if($rs['status'] == 1) {
                //创建订单
                $order = [];
                $order['ordernum'] = $ordernum;
                $order['aid'] = aid;
                $order['bid'] = bid;
                $order['mid'] = $member['id'];
                $order['title'] = $title;
                $order['money'] = $money;
                $order['paymoney'] = $paymoney;
                $order['disprice'] = $disprice;
                $order['scoredk'] = $scoredk;
                $order['decscore'] = $decscore;
                $order['couponrid'] = $couponrid;
                $order['couponmoney'] = $couponmoney; //优惠券抵扣
                $order['createtime'] = time();
                $order['paytime'] = time();
                $order['paytypeid'] = 1;
                $order['paytype'] = t('余额').'支付';
                $order['platform'] = platform;
                $order['status'] = 1;
                $order['mdid'] = $post['mdid'] ? $post['mdid'] : 0;
                $order['uid'] = $this->uid??0;
                $orderid = Db::name('maidan_order')->insertGetId($order);
                \app\common\System::plog('后台买单收款'.$orderid);
                //为了增加商家贷款金额
                $payorderid = \app\model\Payorder::createorder(aid,$order['bid'],$order['mid'],'maidan',$orderid,$order['ordernum'],$order['title'],$order['paymoney'],$order['decscore']);
                Db::name('payorder')->whereOr('aid',aid)->where('bid',$order['bid'])->where('id',$payorderid)->update(['status'=>1,'paytime'=>time(),'paytype'=>t('余额').'支付','paytypeid'=>1]);
                \app\common\Order::collect($order,'maidan');
                return json(['status'=>1,'paymoney'=>$paymoney,'msg'=>'实付：'.$paymoney]);
            }

            return json($rs);
        }

        $mendian = Db::name('mendian')->where('aid',aid)->where('bid',bid)->select()->toArray();
        View::assign('mendian',$mendian);

        return View::fetch();
    }

    public function getMemberByTel()
    {
        $tel = input('param.tel');

        if(empty($tel)) return json(['status'=>0,'msg'=>'请输入手机号']);
        $member = Db::name('member')->where('aid',aid)->where('tel',$tel)->field('id,nickname,money')->find();
        if(empty($member))
            return json(['status'=>0,'msg'=>'未找到用户']);
        return json(['status'=>1,'data'=>$member]);
    }

    public function set(){
        $field = 'maidan_payaftertourl';
        if(getcustom('reg_invite_code_business'))$field .= ',maidan_reg_invite_code';
        $admin_set =  Db::name('business')->where('aid',aid)->where('id',bid)->field($field)->find();
		View::assign('admin',$admin_set);

		$showtourl = true;//是否展示支付后跳转
        View::assign('showtourl',$showtourl);
        return View::fetch();
    }
    public function save(){
        $data = [];
        $data['maidan_payaftertourl'] = input('maidan_payaftertourl')?:'';
        if(getcustom('reg_invite_code_business'))$data['maidan_reg_invite_code'] = input('maidan_reg_invite_code')?:'';
        Db::name('business')->where('aid',aid)->where('id',bid)->update($data);
        return json(['status'=>1,'msg'=>'设置成功']);
    }
    //退款
    public function refund(){
      
        if (request()->isPost()) {
            $id = input('param.id/d', 0);
            $refund_money = input('param.money', 0);
            $remark = input('param.reason', '');
            if (empty($refund_money) || $refund_money < 0) {
                return json(['status' => 0, 'msg' => '退款金额有误']);
            }
            $where = [];
            $where[] = ['aid', '=', aid];
            $where[] = ['bid', '=', bid];
            $where[] = ['id', '=', $id];
            $order = Db::name('maidan_order')->where($where)->find();
            if ($refund_money > $order['paymoney'] - $order['refund_money']) {
                return json(['status' => 0, 'msg' => '可退款金额不足']);
            }
            $data = [
                'aid' => $order['aid'],
                'bid' => $order['bid'],
                'mdid' => $order['mdid'],
                'mid' => $order['mid'],
                'orderid' => $order['id'],
                'ordernum' => $order['ordernum'],
                'title' => $order['title'],
                'refund_type' => 'refund',//退款
                'refund_ordernum' => '' . date('ymdHis') . rand(100000, 999999),
                'refund_money' => $refund_money,
                'refund_reason' => $remark,
                'createtime' => time(),
                'refund_time' => time(),
                'status' => 1,
                'refund_status' => 0,//退款成功2
                'platform' => platform,
                'uid' => $this->uid
            ];
            $refund_id = Db::name('maidan_refund_order')->insertGetId($data);

            $rs = \app\common\Order::refund($order, $refund_money, $remark);

            if ($rs && $rs['status'] == 1) {
                //退款成功
                Db::name('maidan_refund_order')->where('id', $refund_id)->update(['refund_status' => 2]);
                Db::name('maidan_order')->where('id', $order['id'])->inc('refund_money', $refund_money)->update();
                $status = 1;
                $msg = '退款成功';
                if($order['bid'] > 0){
                    $log = Db::name('business_moneylog')->where('aid',$order['aid'])->where('bid',$order['bid'])->where('type','maidan')->where('ordernum',$order['ordernum'])->find();
                    $business_refund_money = dd_money_format($log['money'] / $order['paymoney'] * $refund_money);
                    if($log['money'] > 0 && $business_refund_money > 0){
                        \app\common\Business::addmoney($order['aid'],$order['bid'],-$business_refund_money,'买单退款，订单号：'.$order['ordernum'],true,'maidan',$order['ordernum']);
                    }
                }
            } else {
                Db::name('maidan_refund_order')->where('id', $refund_id)->update(['refund_status' => 5, 'refund_checkremark' => $rs['msg'] ?? '']);//退款失败
                $status = 0;
                $msg = '退款失败';
            }
            return json(['status' => $status, 'msg' => $msg]);
        }
    }

    //买单项目
    public function itemList(){
        }

    //编辑买单项目
    public function item_edit(){
        }

    //删除买单项目
    public function itemDel(){
        }
}

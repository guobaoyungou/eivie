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
class Alipay
{
	//支付宝小程序支付
	function build_alipay($aid,$bid,$mid,$title,$ordernum,$price,$tablename,$notify_url='',$return_url='',$more=1,$alih5=false,$trade_component_order_id='',$openid='',$openid_new=''){
		if(!$notify_url) $notify_url = PRE_URL.'/notify.php';
		$appinfo = \app\common\System::appinfo($aid,'alipay');
		$member = Db::name('member')->where('id',$mid)->find();
		$isbusinesspay = false;
		if($appinfo['sxpay']==1){
			if($bid > 0){
				$business = Db::name('business')->where('id',$bid)->find();
				if($business['sxpay_mno']){
					$rs = \app\custom\Sxpay::build_alipay($aid,$bid,$mid,$title,$ordernum,$price,$tablename);
					return $rs;
				}
			}
			$rs = \app\custom\Sxpay::build_alipay($aid,$bid,$mid,$title,$ordernum,$price,$tablename);
			return $rs;
		}
        $huifu_fenzhang_custom= getcustom('pay_huifu_fenzhang');
        if(getcustom('pay_huifu')){
            if($appinfo['huifu'] ==1) {
                //4汇付天下斗拱 如果商户开启独立收款
                $huifuparams = [];
                if($huifu_fenzhang_custom){
                    $huifu_fenzhang = [];//扩展配置，分账类型和分账对象
                    if($bid > 0 && in_array($tablename,['shop','maidan'])){
                        $business = Db::name('business')->where('aid',$aid)->where('id',$bid)->find();
                        //开启独立收款 使用服务商的信息
                        if($business['huifu_business_status'] ==1){
                            //平台的费率的分账对象
                            $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                            //使用延迟分账时，获取分账体
                            $chouchengmoney = 0;
                            if($business['delay_acct_flag'] ==1){
                                if($business['feepercent'] > 0){
                                    $chouchengmoney = floatval($business['feepercent']) * 0.01 * $price;
                                }
                                $commission = 0;
                                if($bset['commission_kouchu'] == 1){
                                    $commission = Wxpay::getcommission($tablename,$ordernum);
                                }

                                $chouchengmoney = dd_money_format($chouchengmoney + $commission);
                                //汇付的最高可到80% 不需要判断30%
                                if($chouchengmoney < $price*0.8 && $chouchengmoney > 0){
                                    $huifu_fenzhang[] = ['huifu_id' => $appinfo['huifu_sys_id'],'div_amt' => $chouchengmoney];
                                }
                            }
                            
                            
                            $appinfo['huifu_id'] = $business['huifu_id'];//商户号
                            //使用服务商的信息

                            $huifuset = Db::name('sysset')->where('name','huifuset')->find();
                            if(getcustom('pay_huifu_fenzhang_backstage')){
                                //后台独立设置服务商
                                $huifuset_backstage = Db::name('admin_set')->where('aid',$aid)->value('huifuset');
                                if($huifuset_backstage){
                                    $huifuset = ['value'=>$huifuset_backstage];
                                }
                            }
                            if($huifuset){
                                $huifu_appinfo = json_decode($huifuset['value'],true);
                                $appinfo['huifu_sys_id'] = $huifu_appinfo['huifu_sys_id'];//渠道商的huifu_id
                                $appinfo['huifu_id'] = $business['huifu_id'];//商户的huifu_id
                                $appinfo['huifu_product_id'] = $huifu_appinfo['huifu_product_id'];
                                $appinfo['huifu_merch_private_key'] = $huifu_appinfo['huifu_merch_private_key'];
                                $appinfo['huifu_public_key'] =$huifu_appinfo['huifu_public_key'];
                            }
                            //获取分账信息
                            $huifuparams['delay_acct_flag'] = 'N';
                            //使用延时分账时
                            if($business['delay_acct_flag'] ==1) {
                                $huifu = new \app\custom\Huifu([], $aid, $bid);
                                $commission_fenzhang = [];
                                if($business['huifu_send_commission']==1) {
                                    $commission_fenzhang = $huifu->getHuifuFenzhangData($aid, $bid, $ordernum, $tablename);
                                }
                                //商户独立收款也作为分账体，减去抽成，减去佣金部分才是商户所得
                                $totalcommission = 0;
                                foreach ($commission_fenzhang as $cf) {
                                    $totalcommission += $cf['div_amt'];
                                }
                                $business_price = dd_money_format($price - $chouchengmoney - $totalcommission);
                                if ($business_price > 0) {
                                    $huifu_fenzhang[] = ['huifu_id' => $business['huifu_id'], 'div_amt' => $business_price];
                                }
                                if ($commission_fenzhang) $huifu_fenzhang = array_merge($huifu_fenzhang, $commission_fenzhang);
                                $huifuparams['fenzhangdata'] = json_encode($huifu_fenzhang, JSON_UNESCAPED_UNICODE);
                                $huifuparams['delay_acct_flag'] = 'Y';
                            }
                        }
                    }
                }
                $huifu = new \app\custom\Huifu($appinfo,$aid,$bid,$mid,$title,$ordernum,$price,$tablename,$notify_url,$huifuparams);
                $huifu->setPayType('alipay');
                $huifu->setTradeType(platform);
                $rs = $huifu->jspay();
                return $rs;
            }
        }
		if(getcustom('pay_qilinshuzi')){
			if($appinfo['qilin_status']==1){
				$params = [
					'aid'       => $aid,
					'mid'       => $mid,
					'bid'       => $bid,
					'platform'  => platform,
					'tablename' => $tablename,
					'title'     => $title,
					'ordernum'  => $ordernum,
					'money'     => $price,
					'paytype'   => 'ALIPAY',
					'userid'    => $member['alipayopenid']
				];
				$rs = \app\custom\QilinshuziPay::miniProgramPay($appinfo,$params);
				if($rs['status'] == 1){
					$rs['data']['trade_no'] = $rs['data']['tradeNo'];
				}
				return $rs;
			}
		}
		require_once(ROOT_PATH.'/extend/aop/AopClient.php');
		require_once(ROOT_PATH.'/extend/aop/AopCertification.php');
		require_once(ROOT_PATH.'/extend/aop/request/AlipayTradeCreateRequest.php');
        $app_auth_token = null;
        if(getcustom('alipay_fenzhang')){
            if($bid>0){
                $business = Db::name('business')->where('id',$bid)->find();
                $alifw_status = Db::name('business_sysset')->where('aid',$aid)->value('alifw_status');
                if($alifw_status && $business['alipayst']==1 ){
                    $app_auth_token =  $business['alipay_app_auth_token'];
                    $syset = Db::name('sysset')->where('name','alipayisv')->find();
                    $isv_sysset = json_decode($syset['value'],true);
                    $appinfo['appid'] = $isv_sysset['appid'];
                    $appinfo['appsecret'] = $isv_sysset['appsecret'];
                    $appinfo['publickey'] = $isv_sysset['publickey'];
                }
            }
        }
		$aop = new \AopClient();
		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$aop->appId = $appinfo['appid'];
		$aop->rsaPrivateKey = $appinfo['appsecret'];
		$aop->alipayrsaPublicKey = $appinfo['publickey'];
		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset = 'utf-8';
		$aop->format = 'json';
		


		$request = new \AlipayTradeCreateRequest ();
		$bizcontent = [];
		//兼容 openid 新规则
        if($member){
            if($appinfo['openid_set'] =='userid'){
                $bizcontent['buyer_id'] = $member['alipayopenid'];
            }else{
                $bizcontent['buyer_open_id'] = $member['alipayopenid_new'];
            }
        }else{
            if($appinfo['openid_set'] =='openid'){
                $bizcontent['buyer_open_id'] = $openid_new;
            }else{
                $bizcontent['buyer_id'] = $openid;
            }
        }

		$bizcontent['subject'] = mb_substr($title,0,42);
		$bizcontent['op_app_id'] = $appinfo['appid'];
		$bizcontent['out_trade_no'] = ''.$ordernum;
		$bizcontent['total_amount'] = $price;
		$bizcontent['product_code'] = 'JSAPI_PAY';
		$bizcontent['passback_params'] = urlencode($aid.':'.$tablename.':alipay:1:'.$bid);
        $extend_params = [];
        //交易组件order_id
        if($trade_component_order_id){
            $extend_params['trade_component_order_id'] = $trade_component_order_id;
        }
        if($extend_params){
            $bizcontent['extend_params'] = $extend_params;
        }
		if($tablename == 'shop'){
			$oglist = Db::name('shop_order_goods')->where('aid',$aid)->where('ordernum',$ordernum)->select()->toArray();
			if($oglist){
				$goodsDetail = [];
				foreach($oglist as $og){
					$goodsDetail[] = [
						'goods_id'=>$og['proid'].'_'.$og['ggid'],
						'goods_name'=>$og['name'].'('.$og['ggname'].')',
						'quantity'=>$og['num'],
						'price'=>$og['sell_price'],
					];
				}
				$bizcontent['goodsDetail'] = $goodsDetail;
			}
		}
		if($appinfo['pay_mode'] == 2 && !$app_auth_token){
			$bizcontent['sub_merchant'] = ['merchant_id' => $appinfo['msid']];
			$bizcontent['settle_info'] = [
				'settle_detail_infos' => [[
					'trans_in_type' => 'defaultSettle', //直付通进件 默认结算
					'amount' => $price,
				]]
			];
		}
        writeLog(json_encode(['appid'=>$appinfo['appid']]));
        writeLog(json_encode($bizcontent,JSON_UNESCAPED_UNICODE));
		$request->setBizContent(jsonEncode($bizcontent));

		$request->setNotifyUrl($notify_url);
		$result = $aop->execute ( $request,null,$app_auth_token);
		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;
        writeLog('-----order.trade-----');
        writeLog(json_encode([
            'code4'=>$result->$responseNode->code,
            'msg'=>$result->$responseNode->sub_msg,
            'sub_code'=>$result->$responseNode->sub_code,
        ]));
        writeLog('-----order.trade-----');
		if(!empty($resultCode)&&$resultCode == 10000){
			return ['status'=>1,'data'=>$result->$responseNode];
		} else {
			return ['status'=>0,'msg'=>$result->$responseNode->sub_msg];
		}
	}
	//支付宝H5支付
	function build_h5($aid,$bid,$mid,$title,$ordernum,$price,$tablename,$notify_url='',$return_url='',$more=1,$alih5=false,$trade_component_order_id='',$openid='',$openid_new=''){
		if(!$notify_url) $notify_url = PRE_URL.'/notify.php';
		if(!$return_url) $return_url = m_url('pages/my/usercenter', $aid);
		$appinfo = \app\common\System::appinfo($aid,'h5');
		if(getcustom('pay_adapay')){
		     if($appinfo['alipay_type'] ==1){
                 $rs = \app\custom\AdapayPay::build_alipay_h5($aid,$bid,$mid,$title,$ordernum,$price,$tablename,$notify_url);
                 return $rs; 
             }
        }
        if(getcustom('sxpay_h5')){
        	if($alih5 && $appinfo['alipay_type'] ==3){
		  		if($bid > 0){
					$business = Db::name('business')->where('id',$bid)->find();
					if($business['sxpay_mno']){
						$rs = \app\custom\Sxpay::build_alipay($aid,$bid,$mid,$title,$ordernum,$price,$tablename,'','h5');
						return $rs;
					}
				}
				$rs = \app\custom\Sxpay::build_alipay($aid,$bid,$mid,$title,$ordernum,$price,$tablename,'','h5');
				return $rs;
			}
		}
        $huifu_fenzhang_custom= getcustom('pay_huifu_fenzhang');
        if(getcustom('pay_huifu')){
            if($appinfo['alipay_type'] == 4) {
                $huifuparams = [];
                if($huifu_fenzhang_custom){
                    $huifu_fenzhang = [];//扩展配置，分账类型和分账对象
                    if($bid > 0 && in_array($tablename,['shop','maidan'])){
                        $business = Db::name('business')->where('aid',$aid)->where('id',$bid)->find();
                        //开启独立收款 使用服务商的信息
                        if($business['huifu_business_status'] ==1){
                            //平台的费率的分账对象
                            $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                            //使用延迟分账时，获取分账体
                            $chouchengmoney = 0;
                            if($business['delay_acct_flag'] ==1){
                                if($business['feepercent'] > 0){
                                    $chouchengmoney = floatval($business['feepercent']) * 0.01 * $price;
                                }
                                $commission = 0;
                                if($bset['commission_kouchu'] == 1){
                                    $commission = Wxpay::getcommission($tablename,$ordernum);
                                }

                                $chouchengmoney = dd_money_format($chouchengmoney + $commission);
                                //汇付的最高可到80% 不需要判断30%
                                if($chouchengmoney < $price*0.8 && $chouchengmoney > 0){
                                    $huifu_fenzhang[] = ['huifu_id' => $appinfo['huifu_sys_id'],'div_amt' => $chouchengmoney];
                                }
                            }


                            $appinfo['huifu_id'] = $business['huifu_id'];//商户号
                            //使用服务商的信息

                            $huifuset = Db::name('sysset')->where('name','huifuset')->find();
                            if(getcustom('pay_huifu_fenzhang_backstage')){
                                //后台独立设置服务商
                                $huifuset_backstage = Db::name('admin_set')->where('aid',$aid)->value('huifuset');
                                if($huifuset_backstage){
                                    $huifuset = ['value'=>$huifuset_backstage];
                                }
                            }
                            if($huifuset){
                                $huifu_appinfo = json_decode($huifuset['value'],true);
                                $appinfo['huifu_sys_id'] = $huifu_appinfo['huifu_sys_id'];//渠道商的huifu_id
                                $appinfo['huifu_id'] = $business['huifu_id'];//商户的huifu_id
                                $appinfo['huifu_product_id'] = $huifu_appinfo['huifu_product_id'];
                                $appinfo['huifu_merch_private_key'] = $huifu_appinfo['huifu_merch_private_key'];
                                $appinfo['huifu_public_key'] =$huifu_appinfo['huifu_public_key'];
                            }
                            //获取分账信息
                            $huifuparams['delay_acct_flag'] = 'N';
                            //使用延时分账时
                            if($business['delay_acct_flag'] ==1) {
                                $huifu = new \app\custom\Huifu([], $aid, $bid);
                                $commission_fenzhang = [];
                                if($business['huifu_send_commission']==1) {
                                    $commission_fenzhang = $huifu->getHuifuFenzhangData($aid, $bid, $ordernum, $tablename);
                                }
                                //商户独立收款也作为分账体，减去抽成，减去佣金部分才是商户所得
                                $totalcommission = 0;
                                foreach ($commission_fenzhang as $cf) {
                                    $totalcommission += $cf['div_amt'];
                                }
                                $business_price = dd_money_format($price - $chouchengmoney - $totalcommission);
                                if ($business_price > 0) {
                                    $huifu_fenzhang[] = ['huifu_id' => $business['huifu_id'], 'div_amt' => $business_price];
                                }
                                if ($commission_fenzhang) $huifu_fenzhang = array_merge($huifu_fenzhang, $commission_fenzhang);
                                $huifuparams['fenzhangdata'] = json_encode($huifu_fenzhang, JSON_UNESCAPED_UNICODE);
                                $huifuparams['delay_acct_flag'] = 'Y';
                            }
                        }
                    }
                }
                $huifu = new \app\custom\Huifu($appinfo,$aid,$bid,$mid,$title,$ordernum,$price,$tablename,$notify_url,$huifuparams);
                $huifu->setPayType('alipay');
                $huifu->setTradeType(platform);
                $rs = $huifu->jspay();
                return $rs;
            }
        }
        if(getcustom('pay_allinpay')){
            if($appinfo['alipay_type']==6){
                //通联支付 云商通
                $yunstuser = Db::name('member_allinpay_yunst_user')->where('mid',$mid)->where('aid',$aid)->where('memberType',3)->find();
                if(!$yunstuser){
                    //通联支付 云商通 无感自动注册会员(暂时仅微信小程序、微信公众号)
                    $autocreateuser = \app\custom\AllinpayYunst::autocreateuser($aid,$mid,'alipayh5');
                    if(!$autocreateuser || $autocreateuser['status'] != 1){
                        $msg = $autocreateuser && $autocreateuser['msg']?$autocreateuser['msg']:'操作失败';
                        return ['status'=>0,'msg'=>$msg];
                    }
                	return ['status'=>0,'msg'=>'请先绑定支付手机号','url'=>'/pagesC/allinpay/yunstMemberBindphone'];
                }else{
                	if(empty($yunstuser['phone'])){
                		return ['status'=>0,'msg'=>'请先绑定支付手机号','url'=>'/pagesC/allinpay/yunstMemberBindphone'];
                	}
                }
                $params = [
                    'aid'=>$aid,'bid'=>$bid,'mid'=>$mid,'platform'=>'alipayh5',
                    'title'=>$title,'ordernum'=>$ordernum,'tablename'=>$tablename,
                    'payerId'=>$yunstuser['bizUserId'],
                    'amount'=>$price,
                ];
                $rs = \app\custom\AllinpayYunst::consumeApply($params);
                return $rs;
            }
        }
		if(getcustom('pay_qilinshuzi')){
			if($appinfo['alipay_type']==10){
				$member = Db::name('member')->where('id',$mid)->find();
				$params = [
					'aid'       => $aid,
					'mid'       => $mid,
					'bid'       => $bid,
					'platform'  => platform,
					'tablename' => $tablename,
					'title'     => $title,
					'ordernum'  => $ordernum,
					'money'     => $price,
					'paytype'   => 'ALIPAY',
					'userid'    => $member['alipayopenid']
				];
				return \app\custom\QilinshuziPay::h5pay($appinfo,$params);
			}
		}
        $app_auth_token = null;
        if(getcustom('alipay_fenzhang')){
            if($bid>0){
                $business = Db::name('business')->where('id',$bid)->find();
                $alifw_status = Db::name('business_sysset')->where('aid',$aid)->value('alifw_status');
                if($alifw_status && $business['alipayst']==1 ){
                    $app_auth_token =  $business['alipay_app_auth_token'];
                    $syset = Db::name('sysset')->where('name','alipayisv')->find();
                    $isv_sysset = json_decode($syset['value'],true);
                    $appinfo['ali_appid'] = $isv_sysset['appid'];
                    $appinfo['ali_privatekey'] = $isv_sysset['appsecret'];
                    $appinfo['ali_publickey'] = $isv_sysset['publickey'];
                }
            }
        }
		require_once(ROOT_PATH.'/extend/aop/AopClient.php');
		require_once(ROOT_PATH.'/extend/aop/AopCertification.php');
		require_once(ROOT_PATH.'/extend/aop/request/AlipayTradeWapPayRequest.php');

		$aop = new \AopClient();
		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		if($more ==1 ){
			$aop->appId = $appinfo['ali_appid'];
			$aop->rsaPrivateKey = $appinfo['ali_privatekey'];
			$aop->alipayrsaPublicKey = $appinfo['ali_publickey'];
		}else{
			$aop->appId = $appinfo['ali_appid'.$more];
			$aop->rsaPrivateKey = $appinfo['ali_privatekey'.$more];
			$aop->alipayrsaPublicKey = $appinfo['ali_publickey'.$more];
		}
		
		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset = 'utf-8';
		$aop->format = 'json';

		$request = new \AlipayTradeWapPayRequest ();
		$bizcontent = [];
		$bizcontent['body'] = mb_substr($title,0,42);
		$bizcontent['subject'] = mb_substr($title,0,42);
		$bizcontent['out_trade_no'] = ''.$ordernum;
		$bizcontent['total_amount'] = $price;
		$bizcontent['product_code'] = 'QUICK_WAP_WAY';
		$bizcontent['quit_url'] = $return_url;
		$bizcontent['passback_params'] = urlencode($aid.':'.$tablename.':h5:'.$more.':'.$bid);

		if($tablename == 'shop'){
			$oglist = Db::name('shop_order_goods')->where('aid',$aid)->where('ordernum',$ordernum)->select()->toArray();
			if($oglist){
				$goodsDetail = [];
				foreach($oglist as $og){
					$goodsDetail[] = [
						'goods_id'=>$og['proid'].'_'.$og['ggid'],
						'goods_name'=>$og['name'].'('.$og['ggname'].')',
						'quantity'=>$og['num'],
						'price'=>$og['sell_price'],
					];
				}
				$bizcontent['goodsDetail'] = $goodsDetail;
			}
		}
		
		//echo $notify_url;die;
		$request->setBizContent(jsonEncode($bizcontent));
		$request->setNotifyUrl($notify_url);
		$request->setReturnUrl($return_url);
		$result = $aop->pageExecute($request,'POST',$app_auth_token);
        
		return ['status'=>1,'data'=>$result];
	}
	//支付宝APP支付
	function build_app($aid,$bid,$mid,$title,$ordernum,$price,$tablename,$notify_url='',$return_url='',$more=1,$alih5=false,$trade_component_order_id='',$openid='',$openid_new=''){
		if(!$notify_url) $notify_url = PRE_URL.'/notify.php';
		$appinfo = \app\common\System::appinfo($aid,'app');
		require_once(ROOT_PATH.'/extend/aop/AopClient.php');
		require_once(ROOT_PATH.'/extend/aop/AopCertification.php');
		require_once(ROOT_PATH.'/extend/aop/request/AlipayTradeAppPayRequest.php');

		$aop = new \AopClient();
		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';

		if($more ==1 ){
			$aop->appId = $appinfo['ali_appid'];
			$aop->rsaPrivateKey = $appinfo['ali_privatekey'];
			$aop->alipayrsaPublicKey = $appinfo['ali_publickey'];
		}else{
			$aop->appId = $appinfo['ali_appid'.$more];
			$aop->rsaPrivateKey = $appinfo['ali_privatekey'.$more];
			$aop->alipayrsaPublicKey = $appinfo['ali_publickey'.$more];
		}

		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset = 'utf-8';
		$aop->format = 'json';

		$request = new \AlipayTradeAppPayRequest();
		$bizcontent = [];
		$bizcontent['body'] = mb_substr($title,0,42);
		$bizcontent['subject'] = mb_substr($title,0,42);
		$bizcontent['out_trade_no'] = ''.$ordernum;
		$bizcontent['total_amount'] = $price;
		$bizcontent['product_code'] = 'QUICK_MSECURITY_PAY';
		$bizcontent['passback_params'] = urlencode($aid.':'.$tablename.':app:'.$more);

		if($tablename == 'shop'){
			$oglist = Db::name('shop_order_goods')->where('aid',$aid)->where('ordernum',$ordernum)->select()->toArray();
			if($oglist){
				$goodsDetail = [];
				foreach($oglist as $og){
					$goodsDetail[] = [
						'goods_id'=>$og['proid'].'_'.$og['ggid'],
						'goods_name'=>$og['name'].'('.$og['ggname'].')',
						'quantity'=>$og['num'],
						'price'=>$og['sell_price'],
					];
				}
				$bizcontent['goodsDetail'] = $goodsDetail;
			}
		}

		$request->setBizContent(jsonEncode($bizcontent));
		$request->setNotifyUrl($notify_url);
		$result = $aop->sdkExecute($request);
		return ['status'=>1,'data'=>$result];
	}
	//支付宝退款
	public static function refund($aid,$platform,$ordernum,$totalprice,$refundmoney,$refund_desc='退款',$bid=0,$payorder=[],$refundOrder=[],$otherparams=[]){
		if(!$refund_desc) $refund_desc = '退款';
        if($platform =='cashdesk' || $platform =='restaurant_cashdesk'){
            $appinfo = Db::name('admin_setapp_'.$platform)->where('aid',$aid)->where('bid',0)->find();
            if($bid > 0){
                $bappinfo = Db::name('admin_setapp_'.$platform)->where('aid',$aid)->where('bid',$bid)->find();
                if($platform =='cashdesk') {
                    $restaurant_sysset = Db::name('business_sysset')->where('aid', $aid)->find();
                }else{
                    $restaurant_sysset = Db::name('restaurant_admin_set')->where('aid',$aid)->find();
                }
                if(!$restaurant_sysset || $restaurant_sysset['business_cashdesk_alipay_type'] ==0){
                    return ['status'=>0,'msg'=>'支付宝收款已禁用'];
                }
                //3：独立收款
                if($restaurant_sysset['business_cashdesk_alipay_type'] ==3){
                    $appinfo['ali_appid'] = $bappinfo['ali_appid'];
                    $appinfo['ali_privatekey'] = $bappinfo['ali_privatekey'];
                    $appinfo['ali_publickey'] = $bappinfo['ali_publickey'];
                }
            }
        }else{
            $appinfo = \app\common\System::appinfo($aid,$platform);
            if($appinfo['sxpay']){
                if(!$payorder){
                    $payorder = Db::name('payorder')->where('ordernum',$ordernum)->where('status',1)->where('aid',$aid)->find();
                }
                if($payorder){
                    //检查支付流水表是否存在当前已支付的订单
                    $pay_transaction = Db::name('pay_transaction')->where(['aid'=>$aid,'ordernum'=>$ordernum,'type'=>$payorder['type'],'status'=>1])->order('id desc')->find();
                    if($pay_transaction){
                        //如果有数据取流水单号发起退款
                        $ordernum = $pay_transaction['transaction_num'];
                    }
                }
                $sxpayrs = \app\custom\Sxpay::refund($aid,$platform,$ordernum,$totalprice,$refundmoney,$refund_desc,$bid);
                return  $sxpayrs;
            }
        }
		if($platform == 'h5' || $platform == 'app' ){
			$appinfo['appid'] = $appinfo['ali_appid'];
			$appinfo['appsecret'] = $appinfo['ali_privatekey'];
			$appinfo['publickey'] = $appinfo['ali_publickey'];
		}
        if(getcustom('pay_adapay')){
            if($platform == 'h5'){
                $adapay_log = Db::name('adapay_log')->where('aid',$aid)->where('ordernum',$ordernum)->find();
                if($adapay_log){
                    $res = \app\custom\AdapayPay::refund($aid,$platform,$ordernum,$totalprice,$refundmoney,$refund_desc);
                    return $res;
                }
            }
        }
        if(getcustom('pay_huifu')){
            if($platform == 'h5' || $platform == 'alipay' ){
                $huifupay_log = Db::name('huifu_log')->where('aid',$aid)->where('ordernum',$ordernum)->find();
                if($huifupay_log){
                    $huifu = new \app\custom\Huifu($appinfo,$aid,$bid,0,$refund_desc,$ordernum);
                    $huifu->setTradeType($platform);
                    $huifu->setPayType('alipay');
                    $rs = $huifu->refund($refundmoney,$payorder);
                    return $rs;
                }
            }
        }
		if(getcustom('pay_qilinshuzi')){
			if($platform == 'h5' || $platform == 'alipay' ){
				$qilin_log = Db::name('qilinshuzi_log')->where('aid',$aid)->where('ordernum',$ordernum)->where('pay_status',1)->find();
				if($qilin_log){
					$refundInfo = $refundOrder;
					if(empty($refundInfo)){
						$refundInfo = [
							'refund_ordernum' => $ordernum .'T'.rand(10000,99999),
							'ordernum' => $ordernum,
							'refund_reason' => $refund_desc,
							'refund_money' => $refundmoney
						];
					}
					return \app\custom\QilinshuziPay::refund($appinfo,$refundInfo);
				}
			}
		}
		if($platform == 'cashdesk' || $platform =='restaurant_cashdesk'){
            $appinfo['appid'] = $appinfo['ali_appid'];
            $appinfo['appsecret'] = $appinfo['ali_privatekey'];
            $appinfo['publickey'] = $appinfo['ali_publickey'];
        }
		if(getcustom('plug_more_alipay') && ($platform == 'h5' || $platform == 'app')){
			$alipay_log = Db::name('alipay_log')->where('aid',$aid)->where('ordernum',$ordernum)->find();
			if($alipay_log && $alipay_log['mch_id']){
				for($i=2;$i<=30;$i++){
					if($alipay_log['mch_id'] == $appinfo['ali_appid'.$i]){
						$appinfo['appid'] = $appinfo['ali_appid'.$i];
						$appinfo['appsecret'] = $appinfo['ali_privatekey'.$i];
						$appinfo['publickey'] = $appinfo['ali_publickey'.$i];
						break;
					}
				}
			}
		}
		require_once(ROOT_PATH.'/extend/aop/AopClient.php');
		require_once(ROOT_PATH.'/extend/aop/AopCertification.php');
		require_once(ROOT_PATH.'/extend/aop/request/AlipayTradeRefundRequest.php');

		$aop = new \AopClient();
		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$aop->appId = $appinfo['appid'];
		$aop->rsaPrivateKey = $appinfo['appsecret'];
		$aop->alipayrsaPublicKey = $appinfo['publickey'];
		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset = 'utf-8';
		$aop->format = 'json';

		$request = new \AlipayTradeRefundRequest();
		$bizcontent = [];
		$bizcontent['out_trade_no'] = $ordernum;
		$bizcontent['out_request_no'] = $ordernum. '_' . rand(1000, 9999);
		$bizcontent['refund_amount'] = $refundmoney*100/100;
		$bizcontent['refund_reason'] = $refund_desc;
		if(getcustom('alipay_fenzhang')){
            //是否有分账 分账回退
            $paylog = Db::name('alipay_log')->where('aid',$aid)->where('ordernum',$ordernum)->find();
            if($paylog && ($paylog['fenzhangmoney'] > 0) && $paylog['isfenzhang'] == 1){
                $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                // 设置退分账明细信息
                $refundRoyaltyParameters = array();
                $refundRoyaltyParameters0 = array();
                $refundRoyaltyParameters0['trans_in'] = '2088731891281170';
                $refundRoyaltyParameters0['trans_in_type'] = 'userId';
                $refundRoyaltyParameters0['trans_out'] = $bset['alifw_userId'];
                $refundRoyaltyParameters0['trans_out_type'] = 'userId';
                $refundRoyaltyParameters0['royalty_type'] = "transfer";
                $refundRoyaltyParameters0['amount'] = number_format($paylog['fenzhangmoney'],2);
                $refundRoyaltyParameters[] = $refundRoyaltyParameters0;
                $bizcontent['refund_royalty_parameters'] = $refundRoyaltyParameters;
            }
        }
		$request->setBizContent(jsonEncode($bizcontent));
		$result = $aop->execute($request);
		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;
		if(!empty($resultCode)&&$resultCode == 10000){
			return ['status'=>1,'msg'=>'退款成功'];
		}else{
			return ['status'=>0,'msg'=>$result->$responseNode->sub_msg];
		}
	}
    //支付宝付款码付款
    public static function build_scan($aid,$bid,$mid,$title,$ordernum,$price,$tablename,$notify_url='',$auth_code='',$platform='cashdesk'){      
        if(!$notify_url) $notify_url = PRE_URL.'/notify.php';
        if($platform =='cashdesk' || $platform =='restaurant_cashdesk'){
            $appinfo = Db::name('admin_setapp_'.$platform)->where('aid',$aid)->where('bid',0)->find();
            if($bid > 0){
                $bappinfo = Db::name('admin_setapp_'.$platform)->where('aid',$aid)->where('bid',$bid)->find();
                if($platform =='cashdesk'){
                    $restaurant_sysset = Db::name('business_sysset')->where('aid',$aid)->find();
                }else{
                    $restaurant_sysset = Db::name('restaurant_admin_set')->where('aid',$aid)->find();
                }
                if(!$restaurant_sysset || $restaurant_sysset['business_cashdesk_alipay_type'] ==0){
                    return ['status'=>0,'msg'=>'支付宝收款已禁用'];
                }
                //3：独立收款
                if($restaurant_sysset['business_cashdesk_alipay_type'] ==3){
                    $appinfo['ali_appid'] = $bappinfo['ali_appid'];
                    $appinfo['ali_privatekey'] = $bappinfo['ali_privatekey'];
                    $appinfo['ali_publickey'] = $bappinfo['ali_publickey'];
                }
            }
        } else{
            $appinfo = \app\common\System::appinfo($aid,$platform);
        }
        require_once(ROOT_PATH.'/extend/aop/AopClient.php');
        require_once(ROOT_PATH.'/extend/aop/AopCertification.php');
        require_once(ROOT_PATH.'/extend/aop/request/AlipayTradePayRequest.php');
        $aop = new \AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $appinfo['ali_appid'];
        $aop->rsaPrivateKey = $appinfo['ali_privatekey'];
        $aop->alipayrsaPublicKey=$appinfo['ali_publickey'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='utf-8';
        $aop->format='json';
        $object = new \stdClass();
        $object->out_trade_no = $ordernum;
        $object->total_amount = $price;
        $object->subject = mb_substr($title,0,42);
        $object->scene ='bar_code';
        $object->auth_code = $auth_code;
        
//        $bizcontent['passback_params'] = urlencode($aid.':'.$tablename.':app');
      ;
        $json = json_encode($object,JSON_UNESCAPED_UNICODE);
        $request = new \AlipayTradePayRequest();
        $request->setBizContent($json);
        $result = $aop->execute($request);
        \think\facade\Log::write('支付宝扫码支付日志：');
        \think\facade\Log::write(json_encode($result,JSON_UNESCAPED_UNICODE));
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            $return = [
                'trade_no' => $result->$responseNode->trade_no,
                'buyer_open_id' => $result->$responseNode->buyer_open_id,
                'buyer_user_id' => $result->$responseNode->buyer_user_id,
            ];
            return ['status'=>1,'msg'=>'付款成功','data' =>  $return];
        } else {
            return ['status'=>0,'msg'=>$result->$responseNode->sub_msg];
        }
    }
    public static function build_scan_query($aid,$ordernum,$trade_no){
        $appinfo = \app\common\System::appinfo($aid,'app');
        require_once(ROOT_PATH.'/extend/aop/AopClient.php');
        require_once(ROOT_PATH.'/extend/aop/AopCertification.php');
        require_once(ROOT_PATH.'/extend/aop/request/AlipayTradeQueryRequest.php');
        $aop = new \AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $appinfo['ali_appid'];
        $aop->rsaPrivateKey = $appinfo['ali_privatekey'];
        $aop->alipayrsaPublicKey=$appinfo['ali_publickey'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='utf-8';
        $aop->format='json';
        $request = new \AlipayTradeQueryRequest ();
        $bizcontent = [];
        $bizcontent['out_trade_no'] =''.$ordernum;
        $request->setBizContent(jsonEncode($bizcontent));
        $result = $aop->execute ( $request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            $return = [
                'trade_no' => $result->$responseNode->trade_no,
            ];
            return ['status'=>1,'msg'=>'成功','data' =>  $return];
        } else {
            return ['status'=>0,'msg'=>$result->$responseNode->sub_msg];
        }
    }

    public static function transfers($aid,$ordernum,$money,$order_title,$identity,$name,$remark='账户提现'){
        //原开通产品：转账到支付宝账户 ，现开通产品：商家转账
        //接口参数一样：开发 > 服务端 > 营销产品 > 红包 > API 列表 > “B2C”现金红包 > 单笔转账接口 https://opendocs.alipay.com/open/02byvi?pathHash=b367173b
    	if(getcustom('alipay_auto_transfer')){
	        require_once(ROOT_PATH.'/extend/aop/AopClient.php');
	        //require_once(ROOT_PATH.'/extend/aop/AopCertClient.php');
	        require_once(ROOT_PATH.'/extend/aop/AopCertClientNew.php');//使用新AopCertClient
	        require_once(ROOT_PATH.'/extend/aop/AopCertification.php');
	        require_once(ROOT_PATH.'/extend/aop/AlipayConfig.php');
	        require_once(ROOT_PATH.'/extend/aop/request/AlipayFundTransUniTransferRequest.php');

	        //查询支付宝配置
	        $set = Db::name('admin_set')->where('aid',$aid)->find();
	        // if($set['ali_withdraw_autotransfer'] != 1){
	        //     return ['status'=>0,'msg'=>'未开启'];
	        // }

	        if(!$set['ali_appid'] || !$set['ali_privatekey'] || !$set['ali_apppublickey'] || !$set['ali_publickey'] || !$set['ali_rootcert']){
	            return ['status'=>0,'msg'=>'支付宝自动打款配置错误'];
	        }

	        $alipayConfig = new \AlipayConfig();
	        $alipayConfig->setPrivateKey($set['ali_privatekey']);//应用私钥
	        $alipayConfig->setServerUrl("https://openapi.alipay.com/gateway.do");
	        $alipayConfig->setAppId($set['ali_appid']);//AppId
	        $alipayConfig->setCharset("UTF-8");
	        $alipayConfig->setSignType("RSA2");
	        //$alipayConfig->setEncryptKey("");
	        $alipayConfig->setFormat("json");
	        // $alipayConfig->setAppCertContent($set['ali_apppublickey']);//应用公钥证书内容字符串
	        // $alipayConfig->setAlipayPublicCertContent($set['ali_publickey']);//支付宝公钥证书内容字符串
	        // $alipayConfig->setRootCertContent($set['ali_rootcert']);//支付宝根证书内容字符串

	        //处理域名
	        $pos1 = strpos($set['ali_apppublickey'],"upload");
	        if($pos1){
	            //截取
	            $ali_apppublickey = ROOT_PATH.substr($set['ali_apppublickey'],$pos1);
	        }else{
	            $ali_apppublickey = $set['ali_apppublickey'];
	        }

	        $pos2 = strpos($set['ali_publickey'],"upload");
	        if($pos2){
	            //截取
	            $ali_publickey = ROOT_PATH.substr($set['ali_publickey'],$pos1);
	        }else{
	            $ali_publickey = $set['ali_publickey'];
	        }

	        $pos3 = strpos($set['ali_rootcert'],"upload");
	        if($pos3){
	            //截取
	            $ali_rootcert = ROOT_PATH.substr($set['ali_rootcert'],$pos1);
	        }else{
	            $ali_rootcert = $set['ali_rootcert'];
	        }

	        $alipayConfig->setAppCertPath($ali_apppublickey);//应用公钥证书内容字符串
	        $alipayConfig->setAlipayPublicCertPath($ali_publickey);//支付宝公钥证书内容字符串
	        $alipayConfig->setRootCertPath($ali_rootcert);//支付宝根证书内容字符串
	        
	        $alipayClient = new \AopCertClientNew($alipayConfig);
	        //$alipayClient->isCheckAlipayPublicCert = true;
	        $request = new \AlipayFundTransUniTransferRequest();

            $model = array();
            // 设置商家侧唯一订单号
            $model['out_biz_no']  = $ordernum;
            // 设置订单总金额
            $model['trans_amount']= $money;
            // 设置描述特定的业务场景
            $model['biz_scene']   = "DIRECT_TRANSFER";
            // 设置业务产品码
            $model['product_code']= "TRANS_ACCOUNT_NO_PWD";
            // 设置转账业务的标题
            $model['order_title'] = $order_title;
            // 设置收款方信息
            $payeeInfo = array();
            $payeeInfo['identity'] = $identity;
            $payeeInfo['name']     = $name;
            $payeeInfo['identity_type'] = 'ALIPAY_LOGON_ID';
            $model['payee_info'] = $payeeInfo;
            // 设置业务备注
            $model['remark'] = $remark;
            $bizContent = json_encode($model,JSON_UNESCAPED_UNICODE);
            $request->setBizContent($bizContent);

	        $responseResult  = $alipayClient->execute($request);
	        $responseApiName = str_replace(".","_",$request->getApiMethodName())."_response";
	        $response = $responseResult->$responseApiName;
	        if(!empty($response->code)&&$response->code==10000){
	            $pay_fund_order_id = $response->pay_fund_order_id?$response->pay_fund_order_id:'';
	            return ['status'=>1,'msg'=>'提现成功','pay_fund_order_id'=>$pay_fund_order_id];
	        }else{
	            $sub_msg = $response->sub_msg?$response->sub_msg:'';
	            \think\facade\Log::write([
	                'file'=>__FILE__.__LINE__,
	                'name'=> '支付宝自动打款',
	                'ordernum' => $ordernum,
	                'money' => $money,
	                'sub_msg' =>$sub_msg,
	            ]);
	            return ['status'=>0,'msg'=>'提现失败','sub_msg'=>$sub_msg];
	        }
	    }
    }

    /*+++++++++++++++++++++++++++++支付宝交易组件接口 Start+++++++++++++++++++++++++++++++++++++++*/
    //支付宝交易组件订单创建
    //https://opendocs.alipay.com/mini/54f80876_alipay.open.mini.order.create?pathHash=b9743ab7&ref=api&scene=common
    public static function pluginOrderCreate($aid,$orderid,$mid,$title,$ordernum,$price,$tablename,$source_id=''){
        $appinfo = \app\common\System::appinfo($aid,'alipay');
        $member = Db::name('member')->where('id',$mid)->find();
        require_once(ROOT_PATH.'/extend/aop/AopClient.php');
        require_once(ROOT_PATH.'/extend/aop/request/AlipayOpenMiniOrderCreateRequest.php');

        $bizcontent = [];
        //兼容 openid 新规则
        if($member['alipayopenid']){
            $bizcontent['buyer_id'] = $member['alipayopenid'];
        }else{
//            $bizcontent['buyer_open_id'] = $member['alipayopenid_new'];
        }
        $bizcontent['out_order_id'] = ''.$ordernum;;
        $bizcontent['title'] = $title;
        $bizcontent['merchant_biz_type'] = 'KX_SHOPPING';
        if($source_id){
            $bizcontent['source_id'] = $source_id;
        }
        $bizcontent['path'] = "/pagesExt/order/detail?id={$orderid}";//小程序订单详情链接
        $goodsDetail = [];
        if($tablename == 'shop' || $tablename=='shop_hb'){
            $oglist = Db::name('shop_order_goods')->where('aid',$aid)->where('ordernum',$ordernum)->select()->toArray();
            if($oglist){
                foreach($oglist as $og){
                    $goodsDetail[] = [
                        'goods_id'=>$og['proid'].'_'.$og['ggid'],
                        'goods_name'=>$og['name'].'('.$og['ggname'].')',
                        'item_cnt'=>$og['num'],
                        'sale_price'=>$og['sell_price'],
                    ];
                }
            }
        }
        $orderdetail = [];
        $orderdetail['item_infos'] = $goodsDetail;
        $orderdetail['price_info'] = ['order_price'=>$price];
        $bizcontent['order_detail'] = $orderdetail;//订单信息
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $appinfo['appid'];
        $aop->rsaPrivateKey = $appinfo['appsecret'];
        $aop->alipayrsaPublicKey = $appinfo['publickey'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset = 'utf-8';
        $aop->format = 'json';
        $request = new \AlipayOpenMiniOrderCreateRequest();
        writeLog('--------plugin order biz----------');
        writeLog(jsonEncode($bizcontent));
        writeLog('--------plugin order biz----------');
        $request->setBizContent(jsonEncode($bizcontent));
        $responseResult = $aop->execute($request);
        $responseApiName = str_replace(".","_",$request->getApiMethodName())."_response";
        $response = $responseResult->$responseApiName;
        writeLog('--------------plugin order result-----------');
        writeLog(json_encode([
            'code'=>$response->code,
            'sub_msg'=>$response->sub_msg??$response->msg,
            'order_id'=>$response->order_id,
            'out_order_id'=>$response->out_order_id,
        ]));
        writeLog('--------------plugin order result-----------');
        if(!empty($response->code)&&$response->code==10000){
            return ['status'=>1,'msg'=>'ok','order_id'=>$response->order_id,'out_order_id'=>$response->out_order_id];
        }else{
            $sub_msg = $response->sub_msg?$response->sub_msg:'';
            return ['status'=>0,'msg'=>'组件订单创建失败','sub_msg'=>$sub_msg];
        }
    }

    //交易组件订单发货同步
    public static function pluginOrderSend($orderid,$tablename='shop'){
        $order = Db::name('shop_order')->where('id',$orderid)->find();
        $aid = $order['aid'];
        $mid = $order['mid'];
        $ordernum = $order['ordernum'];
        if(strpos($ordernum, '_')!==false){
            $ordernum = explode('-',$ordernum)[0];
        }
        $appinfo = \app\common\System::appinfo($aid,'alipay');
        $member = Db::name('member')->where('id',$mid)->find();
        require_once(ROOT_PATH.'/extend/aop/AopClient.php');
        require_once(ROOT_PATH.'/extend/aop/AopCertification.php');
        require_once(ROOT_PATH.'/extend/aop/request/AlipayOpenMiniOrderDeliverySendRequest.php');
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $appinfo['appid'];
        $aop->rsaPrivateKey = $appinfo['appsecret'];
        $aop->alipayrsaPublicKey = $appinfo['publickey'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset = 'utf-8';
        $aop->format = 'json';

        $bizcontent = [];
        //兼容 openid 新规则
        if($member['alipayopenid']){
            $bizcontent['user_id'] = $member['alipayopenid'];
        }else{
//            $bizcontent['buyer_open_id'] = $member['alipayopenid_new'];
        }
        $goodsDetail = [];
        if($tablename == 'shop' || $tablename=='shop_hb'){
            $oglist = Db::name('shop_order_goods')->where('aid',$aid)->where('id',$orderid)->select()->toArray();
            if($oglist){
                foreach($oglist as $og){
                    $goodsDetail[] = [
                        'out_item_id'=>$og['proid'].'_'.$og['ggid'],
                        'out_sku_id'=>$og['ggid'],
                        'item_cnt'=>$og['num'],
                        'goods_id'=>$og['proid'],
                    ];
                }
            }
        }
        $delivery = [];
        //'express_com'=>$express_com,'express_no'=>$express_no,'express_ogids'=>$express_ogids,'express_content'=>$express_content,'express_isbufen'=>$express_isbufen

        $delivery[] = [
            'delivery_id'=>getExpressTag($order['express_com']),//快递公司ID
            'waybill_id'=>$order['express_no'],//快递单号
            'item_info_list'=>$goodsDetail
        ];

        $bizcontent['out_order_id'] = ''.$ordernum;
        $bizcontent['finish_all_delivery'] = $order['express_isbufen']?0:1;//0: 未发完, 1:已发完
        $bizcontent['ship_done_time'] = date('Y-m-d H:i:s',time());//发货时间
        $bizcontent['delivery_list'] = $delivery;
        writeLog('--------plugin send--------');
        writeLog(json_encode($bizcontent,JSON_UNESCAPED_UNICODE));
        writeLog('--------plugin send--------');
        $request = new \AlipayOpenMiniOrderDeliverySendRequest();
        $request->setBizContent(jsonEncode($bizcontent));
        $result = $aop->execute( $request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $response = $result->$responseNode;
        $resultCode = $response->code;
        writeLog('--------------plugin order send result-----------');
        writeLog(json_encode([
            'code'=>$response->code,
            'sub_msg'=>$response->sub_msg??$response->msg
        ]));
        writeLog('--------------plugin order send result-----------');
        if(!empty($resultCode) && $resultCode == 10000){
            return ['status'=>1,'data'=>$response];
        } else {
            return ['status'=>0,'msg'=>$response->sub_msg?$response->sub_msg:''];
        }
    }

    //交易组件订单确认收货
    public static function pluginOrderConfirm($aid,$mid,$ordernum){
        $appinfo = \app\common\System::appinfo($aid,'alipay');
        $member = Db::name('member')->where('id',$mid)->find();
        require_once(ROOT_PATH.'/extend/aop/AopClient.php');
        require_once(ROOT_PATH.'/extend/aop/AopCertification.php');
        require_once(ROOT_PATH.'/extend/aop/request/AlipayOpenMiniOrderDeliveryReceiveRequest.php');
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $appinfo['appid'];
        $aop->rsaPrivateKey = $appinfo['appsecret'];
        $aop->alipayrsaPublicKey = $appinfo['publickey'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset = 'utf-8';
        $aop->format = 'json';

        $bizcontent = [];
        //兼容 openid 新规则
        if($member['alipayopenid']){
            $bizcontent['user_id'] = $member['alipayopenid'];
        }else{
            //如需启用，需使用$appinfo['openid_set'] 进行判断
//            $bizcontent['buyer_open_id'] = $member['alipayopenid_new'];
        }
        $bizcontent['out_order_id'] = ''.$ordernum;
        writeLog('--------plugin receive--------');
        writeLog(json_encode($bizcontent,JSON_UNESCAPED_UNICODE));
        writeLog('--------plugin receive--------');
        $request = new \AlipayOpenMiniOrderDeliveryReceiveRequest();
        $request->setBizContent(jsonEncode($bizcontent));
        $result = $aop->execute( $request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $response = $result->$responseNode;
        $resultCode = $response->code;
        writeLog('--------------plugin order send result-----------');
        writeLog(json_encode([
            'code'=>$response->code,
            'sub_msg'=>$response->sub_msg??$response->msg,
        ]));
        writeLog('--------------plugin order send result-----------');
        if(!empty($resultCode) && $resultCode == 10000){
            return ['status'=>1,'data'=>$response];
        } else {
            return ['status'=>0,'msg'=>$response->sub_msg?$response->sub_msg:''];
        }
    }

    //交易组件订单状态改变
    public static function pluginOrderStatusChange($aid,$mid){

    }
    //交易组件订单查询
    public static function pluginOrderQuery($aid,$mid,$ordernum){
        $appinfo = \app\common\System::appinfo($aid,'alipay');
        $member = Db::name('member')->where('id',$mid)->find();
        require_once(ROOT_PATH.'/extend/aop/AopClient.php');
        require_once(ROOT_PATH.'/extend/aop/AopCertification.php');
        require_once(ROOT_PATH.'/extend/aop/request/AlipayOpenMiniOrderQueryRequest.php');
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $appinfo['appid'];
        $aop->rsaPrivateKey = $appinfo['appsecret'];
        $aop->alipayrsaPublicKey = $appinfo['publickey'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset = 'utf-8';
        $aop->format = 'json';

        $bizcontent = [];
        //兼容 openid 新规则
        if($member['alipayopenid']){
            $bizcontent['user_id'] = $member['alipayopenid'];
        }else{
//            $bizcontent['buyer_open_id'] = $member['alipayopenid_new'];
        }
        $bizcontent['out_order_id'] = ''.$ordernum;
        writeLog('--------plugin order query--------');
        writeLog(json_encode($bizcontent,JSON_UNESCAPED_UNICODE));
        writeLog('--------plugin order query--------');
        $request = new \AlipayOpenMiniOrderQueryRequest();
        $request->setBizContent(jsonEncode($bizcontent));
        $result = $aop->execute( $request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode) && $resultCode == 10000){
            return ['status'=>1,'data'=>$result->$responseNode];
        } else {
            return ['status'=>0,'msg'=>$result->$responseNode->sub_msg];
        }
    }
    /*+++++++++++++++++++++++++++++支付宝交易组件接口 End+++++++++++++++++++++++++++++++++++++++*/

    /*+++++++++++++++++++++++++++++支付宝消息通知 Start+++++++++++++++++++++++++++++++++++++++*/
    public static function sendTemplateMessage($aid,$mid,$templatecontent =[]){
        if(getcustom('restaurant_take_food')){
            $appinfo = \app\common\System::appinfo($aid,'alipay');
            $member = Db::name('member')->where('id',$mid)->find();
            require_once(ROOT_PATH.'/extend/aop/AopClient.php');
            require_once(ROOT_PATH.'/extend/aop/AopCertClient.php');
            require_once(ROOT_PATH.'/extend/aop/AopCertification.php');
            require_once(ROOT_PATH.'/extend/aop/AlipayConfig.php');
            require_once(ROOT_PATH.'/extend/aop/request/AlipayOpenAppMiniTemplatemessageSendRequest.php');
            $aop = new \AopClient();
            $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
            $aop->appId = $appinfo['appid'];
            $aop->rsaPrivateKey = $appinfo['appsecret'];
            $aop->alipayrsaPublicKey = $appinfo['publickey'];
            $aop->apiVersion = '1.0';
            $aop->signType = 'RSA2';
            $aop->postCharset = 'utf-8';
            $aop->format = 'json';
            $request = new \AlipayOpenAppMiniTemplatemessageSendRequest();
            $bizcontent = [];
            if($appinfo['openid_set'] =='userid'){
                $bizcontent['to_user_id'] = $member['alipayopenid'];
            }else{
                $bizcontent['to_open_id'] = $member['alipayopenid_new'];
            }
            $template_id = Db::name('admin_setapp_alipay')->where('aid',$aid)->value('tmpl_take_food');
            $tplcontent = [];
            $i =1;
            foreach ($templatecontent as  $val){    
                $keyword = 'keyword'.$i;
                $tplcontent[$keyword] = ['value' => $val];
                $i++;
            }
            $bizcontent['data'] =json_encode($tplcontent);//模板内容
            $bizcontent['user_template_id'] = $template_id;//模板ID
            $bizcontent['page'] = "pages/my/usercenter";
            $request->setBizContent(json_encode($bizcontent,JSON_UNESCAPED_UNICODE));
            $result = $aop->execute ( $request);
            $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            $resultCode = $result->$responseNode->code;
            if(!empty($resultCode)&&$resultCode == 10000){
                return true;
            } else {
                return false;
            }
        }
    }
    /*+++++++++++++++++++++++++++++支付宝消息通知 End+++++++++++++++++++++++++++++++++++++++*/

    /*+++++++++++++++++++++++++++++支付宝服务商代商家管理和分账 Start+++++++++++++++++++++++++++++++++++++++*/
    /**
     * @Title 第三方应用链授权链接生成  isv代指第三方应用
     * @Params  $appid:需要授权的appid 
     * @Params  isvAppId:第三方应用appid 
     * @Params  appTypes限制类型：例如：["TINYAPP","WEBAPP"]MOBILEAPP（移动应用）    WEBAPP（网页应用） PUBLICAPP（生活号）TINYAPP（小程序）BASEAPP（基础应用）  
     * @Params redirectUri:回调链接    必须与第三方应用配置的 授权回调地址 一致
     * https://opendocs.alipay.com/isv/04h3ue?pathHash=0fec5099
     */
    public static function isvAuthorizationUrl($aid=0,$bid=0,$appid=''){
        if(getcustom('alipay_fenzhang')){
            $syset = Db::name('sysset')->where('name','alipayisv')->find();
            $isv_sysset = json_decode($syset['value'],true);
            $redirectUri = PRE_URL.'/notify.php';
            $param = [
                'isvAppId' =>$isv_sysset['appid'],//三方应用 APPID
                'appTypes' =>['PUBLICAPP','MOBILEAPP','WEBAPP','TINYAPP','BASEAPP'],//应用的限制类型
                'redirectUri' =>urlencode($redirectUri) ,
                'state'=>base64_encode($aid.':'.$bid.':'.$appid)
            ];
            $bizdata = [
                'platformCode' => 'O',//大写字母O不是数字0
                'taskType' => 'INTERFACE_AUTH',
                'agentOpParam' =>  $param
            ];
            $bizdata = json_encode($bizdata);
            //方式一：pc端跳转
            //$url = 'https://b.alipay.com/page/message/tasksDetail?bizData='.$bizdata;
            // return $url;
            //方式二：手机支付宝客户端访问该链接 ,appId为支付宝固定的值
            $qrcode_content = "alipays://platformapi/startapp?appId=2018090561299510&page=pages%2Fmessage%2Fauthorize%2Findex%3FbizData%3D".urlencode($bizdata);
            $qrcode = createqrcode($qrcode_content);
            return  $qrcode;
          
        }
    }
    
   //app_auth_code 换取app_auth_token
    //$grant_type  => authorization_code  取应用授权令牌/refresh_token 使用app_refresh_token刷新获取新授权令牌
    public static function appauthcodeToAppauthtoken($aid,$bid,$appid,$param,$refresh_token='',$grant_type='authorization_code'){
        if(getcustom('alipay_fenzhang')){
            $syset = Db::name('sysset')->where('name','alipayisv')->find();
            $isv_sysset = json_decode($syset['value'],true);
            require_once ROOT_PATH.'/extend/aop/AopClient.php';
            require_once ROOT_PATH.'/extend/aop/AopCertClient.php';
            require_once ROOT_PATH.'/extend/aop/AopCertification.php';
            require_once ROOT_PATH.'/extend/aop/AlipayConfig.php';
            require_once ROOT_PATH.'/extend/aop/request/AlipayOpenAuthTokenAppRequest.php';
            // 初始化SDK
            $aop = new \AopClient();
            $aop->gatewayUrl =  'https://openapi.alipay.com/gateway.do';
            $aop->appId = $isv_sysset['appid'];
            $aop->rsaPrivateKey = $isv_sysset['appsecret'];
            $aop->alipayrsaPublicKey = $isv_sysset['publickey'];
            $aop->format='json';
            $aop->postCharset='UTF-8';
            $aop->signType='RSA2';
            $model = array();
            //根据传过来的appid 和当前商户对比是否是一个，同一个为刷新
            $business = Db::name('business')->where('aid',$aid)->where('id',$bid)->field('alipayappid,alipay_app_auth_token')->find();
            if($business['appid'] && $business['appid'] !=$appid){
                $grant_type ='refresh_token';
                $refresh_token = $business['appid'];
            }
            if($grant_type =='authorization_code'){
                $model['grant_type'] = "authorization_code";
                $model['code'] = $param['app_auth_code'];
            }
            if($grant_type =='refresh_token'){
                $model['grant_type'] =  "refresh_token";
                $model['refresh_token'] = $refresh_token;
            }
            
            $request = new \AlipayOpenAuthTokenAppRequest();
            $request->setBizContent(json_encode($model,JSON_UNESCAPED_UNICODE));
            $responseResult = $aop->execute($request);
    
            $responseApiName = str_replace(".","_",$request->getApiMethodName())."_response";
            $response = $responseResult->$responseApiName;
            if(!empty($response->code)&&$response->code==10000){
                Db::name('business')->where('aid',$aid)->where('id',$bid)->update(['alipay_app_auth_token' => $response->app_auth_token]);
                return ['status'=>1,'msg'=>'成功'];
            }
            else{
                return ['status'=>0];
            }
        }
    }
    /*
     * 支付宝分账功能
     * */
    public static function orderSettle($aid,$bid,$userId,$trans_in,$amount){
        if(getcustom('alipay_fenzhang')) {
            $syset = Db::name('sysset')->where('name', 'alipayisv')->find();
            $isv_sysset = json_decode($syset['value'], true);
            require_once ROOT_PATH . '/extend/aop/AopClient.php';
            require_once ROOT_PATH . '/extend/aop/AopCertClient.php';
            require_once ROOT_PATH . '/extend/aop/AopCertification.php';
            require_once ROOT_PATH . '/extend/aop/AlipayConfig.php';
            require_once ROOT_PATH . '/extend/aop/request/AlipayTradeOrderSettleRequest.php';
            // 初始化SDK
            $aop = new \AopClient();
            $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
            $aop->appId = $isv_sysset['appid'];
            $aop->rsaPrivateKey = $isv_sysset['appsecret'];
            $aop->alipayrsaPublicKey = $isv_sysset['publickey'];
            $aop->format = 'json';
            $aop->postCharset = 'UTF-8';
            $aop->signType = 'RSA2';
            // 构造请求参数以调用接口
            $request = new \AlipayTradeOrderSettleRequest();
            $model = array();
            // 设置结算请求流水号
            $ordernum = \app\common\Common::generateOrderNo($aid);
            $model['out_request_no'] = $ordernum;
            // 设置支付宝订单号
            $model['trade_no'] = $trans_in;

            // 设置分账明细信息
            $royaltyParameters = array();
            $royaltyParameters0 = array();
            $royaltyParameters0['royalty_type'] = "transfer";
            $royaltyParameters0['trans_in_type'] = "userId";
            $royaltyParameters0['trans_in'] = $userId;
            $royaltyParameters0['amount'] = $amount;
            $royaltyParameters0['desc'] = "分账给" . $userId;
            $royaltyParameters[] = $royaltyParameters0;
            $model['royalty_parameters'] = $royaltyParameters;

            $request->setBizContent(json_encode($model, JSON_UNESCAPED_UNICODE));
            $app_auth_token = null;
            if (getcustom('alipay_fenzhang')) {
                if ($bid > 0) {
                    $business = Db::name('business')->where('id', $bid)->find();
                    $alifw_status = Db::name('business_sysset')->where('aid', $aid)->value('alifw_status');
                    if ($alifw_status && $business['alipayst'] == 1) {
                        $app_auth_token = $business['alipay_app_auth_token'];
                    }
                }
            }
            $responseResult = $aop->execute($request, null, $app_auth_token);
            $responseApiName = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            $response = $responseResult->$responseApiName;
            if (!empty($response->code) && $response->code == 10000) {
                return ['status' => 1, 'msg' => '成功'];
            } else {
                return ['status' => 0, 'msg' => $response->sub_msg];
            }
        }
    }
    public static function DoFenzhang(){
        if(getcustom('alipay_fenzhang')){
            $alipaylog = Db::name('alipay_log')->whereRaw('fenzhangmoney>0')->where('isfenzhang',0)->where('createtime','<',time()-60)->select()->toArray();
            if($alipaylog){
                foreach($alipaylog as $v){
                    $aid = $v['aid'];
                    $bid = Db::name($v['tablename'].'_order')->where('ordernum',$v['ordernum'])->where('aid',$aid)->value('bid');
                    //设置的接收方账户
                    $bset = Db::name('business_sysset')->where('aid',$aid)->find();
                    if($bset['alifw_status']==1 && $bset['alifw_userId']){
                        $rs = self::orderSettle($aid,$bid, $bset['alifw_userId'],$v['transaction_id'],$v['fenzhangmoney']);
                        if($rs['status'] == 0){
                            Db::name('alipay_log')->where('id',$v['id'])->update(['isfenzhang'=>2,'fz_errmsg'=>$rs['msg']]);
                        }else{
                            Db::name('alipay_log')->where('id',$v['id'])->update(['isfenzhang'=>1,'fz_errmsg'=>$rs['msg'],'fz_ordernum'=>$rs['settle_no']]);
                        }
                    }
                }
            }
        }
    }

    /**
     * 支付宝小程序码
     * @param $aid
     * @param $page 跳转小程序的页面路径
     * @param $describe 对应的二维码描述
     * @return array|int[]|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: liud
     * @time: 2024/12/10 上午10:52
     */
    public static function getQRCode($aid,$page='',$describe=''){
        $page = explode("?",$page);
        $appinfo = \app\common\System::appinfo($aid,'alipay');
        $admin_set = Db::name('admin_set')->where('aid',$aid)->find();
        require_once(ROOT_PATH.'/extend/aop/AopClient.php');
        require_once(ROOT_PATH.'/extend/aop/AopCertClient.php');
        require_once(ROOT_PATH.'/extend/aop/AopCertification.php');
        require_once(ROOT_PATH.'/extend/aop/AlipayConfig.php');
        require_once(ROOT_PATH.'/extend/aop/request/AlipayOpenAppQrcodeCreateRequest.php');
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $appinfo['appid'];
        $aop->rsaPrivateKey = $appinfo['appsecret'];
        $aop->alipayrsaPublicKey = $appinfo['publickey'];
        //$aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset = 'utf-8';
        $aop->format = 'json';
        $request = new \AlipayOpenAppQrcodeCreateRequest ();
        $model = [];
        // 设置跳转小程序的页面路径
        $model['url_param'] = !empty($page[0]) ? $page[0] : 'pages/index/index';
        // 设置小程序的启动参数
        $model['query_param'] = !empty($page[1]) ? $page[1] : 'aid='.$aid;
        // 设置码描述
        $model['describe'] = !empty($describe) ? $describe : $admin_set['name'];
        $request->setBizContent(json_encode($model,JSON_UNESCAPED_UNICODE));
        // 执行操作  请求对应的接口
        $responseResult = $aop->execute($request);
        $responseApiName = str_replace(".","_",$request->getApiMethodName())."_response";
        $response = $responseResult->$responseApiName;
        //var_dump($response);
        if(!empty($response->code)&&$response->code==10000){
            return ['status'=>1,'msg'=>'成功','url'=>$response->qr_code_url_circle_blue];
        }else{
            return ['status'=>0,'msg'=>$response->sub_msg?$response->sub_msg:''];
        }
    }

    /**
     * 解密 https://opendocs.alipay.com/common/02mse3
     * @param $aesKey AES密钥
     * @param $encryptedData 加密字符串
     * @return void
     */
    public static function openSign($aesKey,$encryptedData)
    {
        $result=openssl_decrypt(base64_decode($encryptedData), 'AES-128-CBC', base64_decode($aesKey),OPENSSL_RAW_DATA);
        $v = json_decode($result, true);
        return $v;
    }
}